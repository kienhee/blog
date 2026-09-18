package com.kienhee.blog;

import com.kienhee.blog.dto.MediaFolderCreateRequest;
import com.kienhee.blog.entity.Media;
import com.kienhee.blog.entity.MediaFolder;
import com.kienhee.blog.entity.Role;
import com.kienhee.blog.entity.StorageBlob;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.MediaFolderRepository;
import com.kienhee.blog.repository.MediaRepository;
import com.kienhee.blog.repository.RoleRepository;
import com.kienhee.blog.repository.StorageBlobRepository;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.repository.UserStorageQuotaRepository;
import com.kienhee.blog.service.MediaFolderService;
import com.kienhee.blog.service.MediaService;
import com.kienhee.blog.service.impl.MediaStorageLayout;
import com.kienhee.blog.storage.FilesystemStorage;
import com.kienhee.blog.storage.StoragePath;
import com.kienhee.blog.storage.StorageTransactionHelper;
import com.kienhee.blog.support.MediaFolderCleanup;
import com.kienhee.blog.support.TestAuth;
import com.kienhee.blog.support.TestLocale;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Nested;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.mock.web.MockMultipartFile;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.MvcResult;
import org.springframework.test.web.servlet.setup.MockMvcBuilders;
import org.springframework.transaction.PlatformTransactionManager;
import org.springframework.transaction.support.TransactionTemplate;
import org.springframework.web.context.WebApplicationContext;

import javax.imageio.ImageIO;
import java.awt.image.BufferedImage;
import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.net.URI;
import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.util.ArrayList;
import java.util.List;
import java.util.concurrent.atomic.AtomicInteger;

import static org.junit.jupiter.api.Assertions.assertArrayEquals;
import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertFalse;
import static org.junit.jupiter.api.Assertions.assertNotEquals;
import static org.junit.jupiter.api.Assertions.assertThrows;
import static org.junit.jupiter.api.Assertions.assertTrue;
import static org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers.springSecurity;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.content;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.status;

/**
 * The filesystem mirror: uploads land in {@code uploads/<folder slugs>/<original name>}, folder
 * rename/move carries files along (disk + storage_path + storage_key), and bytes are served only
 * through {@code /media/{id}/{filename}}.
 *
 * <p>Repo convention: real DB and real upload directory; every row, file and directory created
 * here is removed again.</p>
 */
@SpringBootTest
@DisplayName("Media filesystem layout")
class MediaFilesystemLayoutTests {

    private static final AtomicInteger SEED = new AtomicInteger();

    @Autowired
    private MediaService mediaService;
    @Autowired
    private MediaFolderService folderService;
    @Autowired
    private MediaRepository mediaRepository;
    @Autowired
    private MediaFolderRepository folderRepository;
    @Autowired
    private StorageBlobRepository storageBlobRepository;
    @Autowired
    private UserRepository userRepository;
    @Autowired
    private RoleRepository roleRepository;
    @Autowired
    private UserStorageQuotaRepository userStorageQuotaRepository;
    @Autowired
    private MediaStorageLayout layout;
    @Autowired
    private FilesystemStorage storage;
    @Autowired
    private StorageTransactionHelper storageTx;
    @Autowired
    private PlatformTransactionManager transactionManager;
    @Autowired
    private WebApplicationContext context;

    @Value("${app.upload.dir:./uploads}")
    private String uploadDir;

    private MockMvc mockMvc;
    private User uploader;
    private String tag;
    private final List<Long> createdMediaIds = new ArrayList<>();
    private final List<Long> createdFolderIds = new ArrayList<>();

    @BeforeEach
    void setUp() {
        mockMvc = MockMvcBuilders.webAppContextSetup(context).apply(springSecurity())
                .defaultRequest(TestLocale.englishByDefault()).build();
        tag = "fs" + System.nanoTime();
        Role role = roleRepository.findAll().stream().findFirst().orElseThrow();
        uploader = userRepository.save(User.builder()
                .fullName("FS Layout Uploader")
                .email(tag + "@test.com")
                .password("{noop}irrelevant")
                .role(role)
                .build());
    }

    @AfterEach
    void cleanUp() {
        for (Long id : createdMediaIds) {
            try {
                mediaService.deleteMedia(id);
            } catch (RuntimeException ignored) {
                // already trashed or gone
            }
            try {
                mediaService.purgeMedia(id);
            } catch (RuntimeException ignored) {
                // already gone
            }
        }
        createdMediaIds.clear();
        MediaFolderCleanup.deleteFoldersAndDirectories(createdFolderIds, folderRepository, layout, storage);
        createdFolderIds.clear();
        userStorageQuotaRepository.findByUserId(uploader.getId()).ifPresent(userStorageQuotaRepository::delete);
        userRepository.deleteById(uploader.getId());
    }

    // ---- helpers --------------------------------------------------------------

    private byte[] png(int size) throws IOException {
        int seed = SEED.incrementAndGet() * 7919 + (int) (System.nanoTime() % 100_000);
        BufferedImage image = new BufferedImage(size, size, BufferedImage.TYPE_INT_RGB);
        for (int x = 0; x < size; x++) {
            for (int y = 0; y < size; y++) {
                image.setRGB(x, y, (x * 31 + y * 17 + seed) & 0xFFFFFF);
            }
        }
        ByteArrayOutputStream out = new ByteArrayOutputStream();
        ImageIO.write(image, "png", out);
        return out.toByteArray();
    }

    private Media upload(String name, byte[] bytes, String contentType, Long folderId) {
        Media media = mediaService.uploadMedia(
                new MockMultipartFile("file", name, contentType, bytes), uploader.getEmail(), folderId);
        createdMediaIds.add(media.getId());
        return media;
    }

    private Media uploadPng(String name, Long folderId) throws IOException {
        return upload(name, png(24), "image/png", folderId);
    }

    private MediaFolder folder(String name, Long parentId) {
        MediaFolder created = folderService.createFolder(MediaFolderCreateRequest.builder()
                .name(name).parentId(parentId).build());
        createdFolderIds.add(created.getId());
        return created;
    }

    private Path root() {
        return Paths.get(uploadDir).toAbsolutePath().normalize();
    }

    private Media reload(Long id) {
        return mediaRepository.findById(id).orElseThrow();
    }

    private MediaFolder reloadFolder(Long id) {
        return folderRepository.findById(id).orElseThrow();
    }

    private StorageBlob blobOf(Media media) {
        return storageBlobRepository.findById(media.getBlob().getId()).orElseThrow();
    }

    /** storage_path, storage_key and the real file must all agree. */
    private void assertStoredAt(Long mediaId, String expectedPath) {
        Media media = reload(mediaId);
        assertEquals(expectedPath, media.getStoragePath());
        assertEquals(expectedPath, blobOf(media).getStorageKey(), "storage_key must follow storage_path");
        assertTrue(Files.isRegularFile(root().resolve(expectedPath)), "file must be on disk at " + expectedPath);
    }

    // ---- tests ----------------------------------------------------------------

    @Nested
    @DisplayName("Upload")
    class Upload {

        @Test
        @DisplayName("a file uploaded two folders deep lands at uploads/<a>/<b>/<original name>")
        void nestedUploadMirrorsTree() throws IOException {
            MediaFolder a = folder(tag + " Outer", null);
            MediaFolder b = folder("Inner Box", a.getId());

            Media media = uploadPng("sunset.png", b.getId());

            String expected = a.getSlug() + "/" + b.getSlug() + "/sunset.png";
            assertEquals(tag + "-outer/inner-box/sunset.png", expected);
            assertStoredAt(media.getId(), expected);
            assertEquals("sunset.png", media.getStoredFilename());
            assertTrue(Files.isDirectory(root().resolve(a.getSlug()).resolve(b.getSlug())));
        }

        @Test
        @DisplayName("a duplicate name becomes 'name (2).png' (case-insensitively) and never overwrites")
        void duplicateNameGetsSuffix() throws IOException {
            MediaFolder box = folder(tag + " Dupes", null);
            byte[] firstBytes = png(24);
            Media first = upload("photo.png", firstBytes, "image/png", box.getId());
            Media second = uploadPng("photo.png", box.getId());
            Media third = uploadPng("PHOTO.png", box.getId());

            assertEquals("photo.png", first.getStoredFilename());
            assertEquals("photo (2).png", second.getStoredFilename());
            assertEquals("PHOTO (3).png", third.getStoredFilename(),
                    "a name differing only in case is the same file on Windows/macOS");
            assertStoredAt(first.getId(), box.getSlug() + "/photo.png");
            assertStoredAt(second.getId(), box.getSlug() + "/photo (2).png");
            assertArrayEquals(firstBytes, Files.readAllBytes(root().resolve(first.getStoragePath())),
                    "the first file must be untouched");
        }

        @Test
        @DisplayName("two uploads with identical bytes are two separate files on disk (no dedupe)")
        void identicalContentIsTwoFiles() throws IOException {
            byte[] bytes = png(24);
            Media one = upload(tag + "-same.png", bytes, "image/png", null);
            Media two = upload(tag + "-same.png", bytes, "image/png", null);

            assertEquals(one.getSha256(), two.getSha256());
            assertNotEquals(one.getStoragePath(), two.getStoragePath());
            assertNotEquals(one.getBlob().getId(), two.getBlob().getId());
            assertTrue(Files.isRegularFile(root().resolve(one.getStoragePath())));
            assertTrue(Files.isRegularFile(root().resolve(two.getStoragePath())));
            assertEquals(1, blobOf(one).getRefCount());
            assertEquals(1, blobOf(two).getRefCount());
        }

        @Test
        @DisplayName("media.url is the stable /media/{id}/{filename}; a large image gets .thumbnails/<id>_thumb")
        void urlAndThumbnail() throws IOException {
            Media media = upload(tag + " big.png", png(400), "image/png", null);

            assertEquals("/media/" + media.getId() + "/" + tag + "%20big.png", media.getUrl());
            assertEquals("/media/" + media.getId() + "/thumb", media.getThumbnailUrl());
            assertTrue(Files.isRegularFile(root().resolve(".thumbnails").resolve(media.getId() + "_thumb.png")));
        }
    }

    @Nested
    @DisplayName("Folder operations")
    class FolderOps {

        @Test
        @DisplayName("renaming a folder renames the directory and rewrites storage_path + storage_key, twice in a row")
        void renameTwice() throws IOException {
            MediaFolder box = folder(tag + " First", null);
            Media media = uploadPng("inside.png", box.getId());
            String firstSlug = box.getSlug();

            MediaFolder renamed = folderService.renameFolder(box.getId(), tag + " Second");
            String secondSlug = renamed.getSlug();
            assertEquals(tag + "-second", secondSlug);
            assertFalse(Files.exists(root().resolve(firstSlug)), "old directory must be gone");
            assertStoredAt(media.getId(), secondSlug + "/inside.png");

            // Trap 1: forgetting storage_key only blows up on the SECOND rename.
            MediaFolder renamedAgain = folderService.renameFolder(box.getId(), tag + " Third");
            assertFalse(Files.exists(root().resolve(secondSlug)));
            assertStoredAt(media.getId(), renamedAgain.getSlug() + "/inside.png");

            assertEquals(media.getUrl(), reload(media.getId()).getUrl(), "the URL must not change on rename");
        }

        @Test
        @DisplayName("moving a folder carries its subtree's files along")
        void moveCarriesFiles() throws IOException {
            MediaFolder a = folder(tag + " A", null);
            MediaFolder b = folder(tag + " B", a.getId());
            MediaFolder deep = folder("Deep", b.getId());
            MediaFolder target = folder(tag + " Target", null);
            Media inB = uploadPng("in-b.png", b.getId());
            Media inDeep = uploadPng("in-deep.png", deep.getId());

            folderService.moveFolder(b.getId(), target.getId());

            assertStoredAt(inB.getId(), target.getSlug() + "/" + b.getSlug() + "/in-b.png");
            assertStoredAt(inDeep.getId(), target.getSlug() + "/" + b.getSlug() + "/deep/in-deep.png");
            assertFalse(Files.exists(root().resolve(a.getSlug()).resolve(b.getSlug())));

            // and back again, so a second move over already-rewritten keys works too
            folderService.moveFolder(b.getId(), null);
            assertStoredAt(inDeep.getId(), b.getSlug() + "/deep/in-deep.png");
            assertEquals(0, reloadFolder(b.getId()).getDepth());
        }

        @Test
        @DisplayName("moving a file into a folder that already has that name picks a free name (no overwrite)")
        void fileMoveAvoidsOverwrite() throws IOException {
            MediaFolder from = folder(tag + " From", null);
            MediaFolder to = folder(tag + " To", null);
            byte[] residentBytes = png(24);
            Media resident = upload("same.png", residentBytes, "image/png", to.getId());
            Media mover = uploadPng("same.png", from.getId());

            mediaService.bulkMoveToFolder(List.of(mover.getId()), to.getId());

            assertStoredAt(mover.getId(), to.getSlug() + "/same (2).png");
            assertStoredAt(resident.getId(), to.getSlug() + "/same.png");
            assertArrayEquals(residentBytes, Files.readAllBytes(root().resolve(to.getSlug()).resolve("same.png")));
            assertFalse(Files.exists(root().resolve(from.getSlug()).resolve("same.png")));
        }

        @Test
        @DisplayName("creating a folder creates its directory; purging it removes the emptied directory")
        void createAndPurgeDirectory() {
            MediaFolder box = folder(tag + " Purge me", null);
            Path dir = root().resolve(box.getSlug());
            assertTrue(Files.isDirectory(dir));

            folderService.deleteFolder(box.getId());
            assertTrue(Files.isDirectory(dir), "a soft delete must not touch the disk");

            folderService.purgeFolder(box.getId());
            assertFalse(Files.exists(dir));
        }
    }

    @Nested
    @DisplayName("Serving")
    class Serving {

        @Test
        @DisplayName("GET /media/{id}/{filename} returns the bytes inline, anonymously")
        void servesFile() throws Exception {
            byte[] bytes = png(24);
            Media media = upload(tag + " Hoàng hôn.png", bytes, "image/png", null);

            MvcResult result = mockMvc.perform(get(URI.create(media.getUrl())))
                    .andExpect(status().isOk())
                    .andExpect(content().contentType("image/png"))
                    .andExpect(content().bytes(bytes))
                    .andReturn();
            assertTrue(result.getResponse().getHeader("Content-Disposition").startsWith("inline"));
            assertEquals("nosniff", result.getResponse().getHeader("X-Content-Type-Options"));

            // The filename segment is cosmetic: any value resolves by id.
            mockMvc.perform(get("/media/" + media.getId() + "/whatever.png"))
                    .andExpect(status().isOk())
                    .andExpect(content().bytes(bytes));
            // Thumbnail endpoint falls back to the original for a small image.
            mockMvc.perform(get(media.getThumbnailUrl() != null ? media.getThumbnailUrl() : "/media/" + media.getId() + "/thumb"))
                    .andExpect(status().isOk());
        }

        @Test
        @DisplayName("an unknown id is 404")
        void unknownIdIs404() throws Exception {
            mockMvc.perform(get("/media/" + Long.MAX_VALUE + "/nope.png")).andExpect(status().isNotFound());
            mockMvc.perform(get("/media/" + Long.MAX_VALUE + "/thumb")).andExpect(status().isNotFound());
        }

        @Test
        @DisplayName("SVG is served as an attachment, never inline")
        void svgIsAttachment() throws Exception {
            byte[] svg = "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"10\" height=\"10\"><rect width=\"10\" height=\"10\"/></svg>"
                    .getBytes(StandardCharsets.UTF_8);
            Media media = upload(tag + "-icon.svg", svg, "image/svg+xml", null);

            MvcResult result = mockMvc.perform(get(URI.create(media.getUrl())))
                    .andExpect(status().isOk())
                    .andExpect(content().bytes(svg))
                    .andReturn();
            assertTrue(result.getResponse().getHeader("Content-Disposition").startsWith("attachment"));
            assertTrue(result.getResponse().getHeader("Content-Security-Policy").contains("sandbox"));
        }

        @Test
        @DisplayName("a trashed file is 404 for the public but still previewable with media:view")
        void trashedVisibility() throws Exception {
            Media media = uploadPng(tag + "-trashed.png", null);
            mediaService.deleteMedia(media.getId());

            mockMvc.perform(get(URI.create(media.getUrl()))).andExpect(status().isNotFound());
            mockMvc.perform(get(URI.create(media.getUrl()))
                            .with(TestAuth.withPermissions("viewer@test.com", "posts:view")))
                    .andExpect(status().isNotFound());
            mockMvc.perform(get(URI.create(media.getUrl())).with(TestAuth.owner()))
                    .andExpect(status().isOk());
        }

        @Test
        @DisplayName("/uploads/** no longer serves files, even to a signed-in owner")
        void uploadsPathIsGone() throws Exception {
            Media media = uploadPng(tag + "-direct.png", null);
            assertTrue(Files.isRegularFile(root().resolve(media.getStoragePath())), "precondition: file exists");

            mockMvc.perform(get("/uploads/" + media.getStoragePath()).with(TestAuth.owner()))
                    .andExpect(status().isNotFound());
            int anonymous = mockMvc.perform(get("/uploads/" + media.getStoragePath()))
                    .andReturn().getResponse().getStatus();
            assertNotEquals(200, anonymous);
        }
    }

    @Nested
    @DisplayName("Rollback compensation")
    class Compensation {

        @Test
        @DisplayName("a rolled-back transaction deletes a written file and moves a moved file back")
        void rollbackUndoesDiskChanges() {
            TransactionTemplate tx = new TransactionTemplate(transactionManager);
            StoragePath dir = StoragePath.of(tag + "-rollback");
            StoragePath original = dir.resolve("keep.txt");
            StoragePath moved = dir.resolve("moved.txt");
            StoragePath written = dir.resolve("new.txt");
            storage.writeFile(original, new java.io.ByteArrayInputStream("x".getBytes()), 1);
            try {
                assertThrows(IllegalStateException.class, () -> tx.executeWithoutResult(status -> {
                    storageTx.moveFile(original, moved);
                    storageTx.writeFile(written, "y".getBytes());
                    assertTrue(storage.fileExists(moved));
                    assertTrue(storage.fileExists(written));
                    throw new IllegalStateException("boom");
                }));

                assertTrue(storage.fileExists(original), "move must be reversed");
                assertFalse(storage.fileExists(moved));
                assertFalse(storage.fileExists(written), "written file must be removed");
            } finally {
                storage.deleteFile(original);
                storage.deleteFile(moved);
                storage.deleteFile(written);
                storage.deleteDirectoryIfEmpty(dir);
            }
        }

        @Test
        @DisplayName("a failing folder move leaves disk, storage_path and storage_key untouched")
        void failedMoveRollsBack() throws IOException {
            MediaFolder a = folder(tag + " Src", null);
            MediaFolder b = folder(tag + " Dst", null);
            Media media = uploadPng("stay.png", a.getId());
            // A stray directory with the destination name makes the disk move fail.
            Path stray = root().resolve(b.getSlug()).resolve(a.getSlug());
            Files.createDirectories(stray);
            try {
                assertThrows(IllegalArgumentException.class, () -> folderService.moveFolder(a.getId(), b.getId()));
                assertStoredAt(media.getId(), a.getSlug() + "/stay.png");
                assertEquals("/" + a.getId() + "/", reloadFolder(a.getId()).getPath());
            } finally {
                Files.deleteIfExists(stray);
            }
        }
    }
}
