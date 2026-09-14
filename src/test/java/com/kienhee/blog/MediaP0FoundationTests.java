package com.kienhee.blog;

import com.kienhee.blog.entity.Media;
import com.kienhee.blog.entity.Role;
import com.kienhee.blog.entity.StorageBlob;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.MediaRepository;
import com.kienhee.blog.repository.RoleRepository;
import com.kienhee.blog.repository.StorageBlobRepository;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.repository.UserStorageQuotaRepository;
import com.kienhee.blog.service.MediaService;
import com.kienhee.blog.service.QuotaService;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Nested;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.mock.web.MockMultipartFile;

import javax.imageio.ImageIO;
import java.awt.image.BufferedImage;
import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.util.ArrayList;
import java.util.List;

import static org.junit.jupiter.api.Assertions.*;

/**
 * Covers the P0 foundation wired into the upload path: one blob per upload (dedupe was
 * switched off in V16), ref-counted physical deletes, and per-user quota accounting.
 *
 * <p>Follows the repo convention of hitting a real database and cleaning up every row and
 * file the test created.
 */
@SpringBootTest
@DisplayName("Media P0 foundation")
class MediaP0FoundationTests {

    @Autowired
    private MediaService mediaService;
    @Autowired
    private QuotaService quotaService;
    @Autowired
    private MediaRepository mediaRepository;
    @Autowired
    private StorageBlobRepository storageBlobRepository;
    @Autowired
    private UserRepository userRepository;
    @Autowired
    private RoleRepository roleRepository;
    @Autowired
    private UserStorageQuotaRepository userStorageQuotaRepository;

    @Value("${app.upload.dir:./uploads}")
    private String uploadDir;

    private User uploader;
    private final List<Long> createdMediaIds = new ArrayList<>();

    @BeforeEach
    void createUploader() {
        Role role = roleRepository.findAll().stream().findFirst().orElseThrow();
        uploader = userRepository.save(User.builder()
                .fullName("P0 Test Uploader")
                .email("p0-upload-" + System.currentTimeMillis() + "@test.com")
                .password("{noop}irrelevant")
                .role(role)
                .build());
    }

    @AfterEach
    void cleanUp() {
        for (Long id : createdMediaIds) {
            try {
                hardDelete(id);
            } catch (RuntimeException ignored) {
                // already deleted by the test itself
            }
        }
        createdMediaIds.clear();
        userStorageQuotaRepository.findByUserId(uploader.getId())
                .ifPresent(userStorageQuotaRepository::delete);
        userRepository.deleteById(uploader.getId());
    }

    private MockMultipartFile pngFile(String filename, int size) throws IOException {
        BufferedImage image = new BufferedImage(size, size, BufferedImage.TYPE_INT_RGB);
        for (int x = 0; x < size; x++) {
            for (int y = 0; y < size; y++) {
                image.setRGB(x, y, (x * 31 + y * 17) & 0xFFFFFF);
            }
        }
        ByteArrayOutputStream out = new ByteArrayOutputStream();
        ImageIO.write(image, "png", out);
        return new MockMultipartFile("file", filename, "image/png", out.toByteArray());
    }

    private Media upload(MockMultipartFile file) {
        Media media = mediaService.uploadMedia(file, uploader.getEmail(), null);
        createdMediaIds.add(media.getId());
        return media;
    }

    /**
     * deleteMedia() is a soft delete since V15 (trash), so anything that has to actually
     * leave the disk goes trash -> purge. These tests are about the physical/ref-count
     * layer, which now lives behind purgeMedia().
     */
    private void hardDelete(Long id) {
        mediaService.deleteMedia(id);
        mediaService.purgeMedia(id);
    }

    private Path onDisk(String storagePath) {
        return Paths.get(uploadDir).toAbsolutePath().normalize().resolve(storagePath);
    }

    @Nested
    @DisplayName("No dedupe (V16): every upload owns its own file")
    class Dedupe {

        @Test
        @DisplayName("identical bytes get their own blob and their own physical file")
        void identicalBytesGetSeparateBlobs() throws IOException {
            MockMultipartFile bytes = pngFile("first.png", 40);
            Media first = upload(bytes);
            Media second = upload(new MockMultipartFile(
                    "file", "second-name.png", "image/png", bytes.getBytes()));

            assertNotNull(first.getSha256(), "hash must be recorded on upload");
            assertEquals(first.getSha256(), second.getSha256(), "hash still identifies the duplicate");
            assertNotEquals(first.getBlob().getId(), second.getBlob().getId(),
                    "dedupe is off: identical bytes must NOT share a blob");
            assertNotEquals(first.getStoragePath(), second.getStoragePath());
            assertTrue(Files.exists(onDisk(first.getStoragePath())));
            assertTrue(Files.exists(onDisk(second.getStoragePath())), "a second physical file must be written");
            assertEquals("second-name.png", second.getOriginalFilename());

            assertEquals(1, storageBlobRepository.findById(first.getBlob().getId()).orElseThrow().getRefCount());
            assertEquals(1, storageBlobRepository.findById(second.getBlob().getId()).orElseThrow().getRefCount());
        }

        @Test
        @DisplayName("different bytes get their own blob")
        void differentBytesDoNotShare() throws IOException {
            Media a = upload(pngFile("a.png", 40));
            Media b = upload(pngFile("b.png", 41));

            assertNotEquals(a.getSha256(), b.getSha256());
            assertNotEquals(a.getBlob().getId(), b.getBlob().getId());
            assertNotEquals(a.getStoragePath(), b.getStoragePath());
        }
    }

    @Nested
    @DisplayName("Ref-counted delete")
    class RefCountedDelete {

        @Test
        @DisplayName("purging one of two identical uploads removes only its own file and blob")
        void purgeRemovesOnlyOwnFile() throws IOException {
            MockMultipartFile bytes = pngFile("shared.png", 40);
            Media first = upload(bytes);
            Media second = upload(new MockMultipartFile(
                    "file", "shared-copy.png", "image/png", bytes.getBytes()));
            Path firstFile = onDisk(first.getStoragePath());
            Path secondFile = onDisk(second.getStoragePath());
            Long firstBlobId = first.getBlob().getId();
            assertTrue(Files.exists(firstFile), "precondition: file was written");

            hardDelete(first.getId());
            createdMediaIds.remove(first.getId());

            assertFalse(Files.exists(firstFile), "ref_count 1 -> 0: its own file must be removed");
            assertFalse(storageBlobRepository.existsById(firstBlobId), "its blob row must be gone too");
            assertTrue(Files.exists(secondFile), "the identical twin keeps its own file");
            assertEquals(1, storageBlobRepository.findById(second.getBlob().getId()).orElseThrow().getRefCount());
        }
    }

    @Nested
    @DisplayName("Quota accounting")
    class Quota {

        @Test
        @DisplayName("upload reserves and delete releases the same byte count")
        void usageTracksUploadAndDelete() throws IOException {
            quotaService.ensureQuotaRow(uploader.getId());
            long before = quotaService.getUsage(uploader.getId()).usedBytes();

            Media media = upload(pngFile("quota.png", 40));
            long after = quotaService.getUsage(uploader.getId()).usedBytes();
            assertEquals(before + media.getSizeBytes(), after, "upload must reserve exactly the file size");

            hardDelete(media.getId());
            createdMediaIds.remove(media.getId());

            assertEquals(before, quotaService.getUsage(uploader.getId()).usedBytes(),
                    "delete must give the bytes back");
        }

        @Test
        @DisplayName("a rejected upload leaks no quota")
        void rejectedUploadReleasesReservation() throws IOException {
            quotaService.ensureQuotaRow(uploader.getId());
            long before = quotaService.getUsage(uploader.getId()).usedBytes();

            MockMultipartFile spoofed = new MockMultipartFile(
                    "file", "not-really.pdf", "application/pdf", pngFile("x.png", 20).getBytes());
            assertThrows(IllegalArgumentException.class,
                    () -> mediaService.uploadMedia(spoofed, uploader.getEmail(), null));

            assertEquals(before, quotaService.getUsage(uploader.getId()).usedBytes(),
                    "a failed upload must not consume quota");
        }
    }
}
