package com.kienhee.blog;

import com.kienhee.blog.entity.Category;
import com.kienhee.blog.entity.Media;
import com.kienhee.blog.entity.Post;
import com.kienhee.blog.entity.PostStatus;
import com.kienhee.blog.entity.Role;
import com.kienhee.blog.entity.StorageBlob;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.CategoryRepository;
import com.kienhee.blog.repository.MediaRepository;
import com.kienhee.blog.repository.PostRepository;
import com.kienhee.blog.repository.RoleRepository;
import com.kienhee.blog.repository.StorageBlobRepository;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.service.MediaBlobBackfillService;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.boot.test.context.SpringBootTest;

import javax.imageio.ImageIO;
import java.awt.image.BufferedImage;
import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.UUID;

import static org.junit.jupiter.api.Assertions.*;

/**
 * Backfill blob cho cac media row co truoc V13 (sha256 / blob_id = NULL).
 *
 * <p>Cac kich ban theo tung row goi thang {@link MediaBlobBackfillService#processRow} de KHONG
 * dung vao nhung row legacy that su dang nam trong DB dev; chi cac assert ve so lieu tong
 * moi goi {@code backfill(true)} — dry-run, khong ghi mot dong nao.
 *
 * <p>Theo dung convention cua repo: DB that, va don sach moi row/file do test tao ra.
 */
@SpringBootTest
@DisplayName("Media blob backfill")
class MediaBlobBackfillTests {

    @Autowired
    private MediaBlobBackfillService backfillService;
    @Autowired
    private MediaRepository mediaRepository;
    @Autowired
    private StorageBlobRepository storageBlobRepository;
    @Autowired
    private PostRepository postRepository;
    @Autowired
    private UserRepository userRepository;
    @Autowired
    private RoleRepository roleRepository;
    @Autowired
    private CategoryRepository categoryRepository;

    @Value("${app.upload.dir:./uploads}")
    private String uploadDir;

    private User owner;
    private final List<Long> createdPostIds = new ArrayList<>();
    private final List<Long> createdMediaIds = new ArrayList<>();
    private final List<String> createdFiles = new ArrayList<>();

    @BeforeEach
    void setUp() {
        Role role = roleRepository.findAll().stream().findFirst().orElseThrow();
        owner = userRepository.save(User.builder()
                .fullName("Backfill Test User")
                .email("backfill-" + System.nanoTime() + "@test.com")
                .password("{noop}irrelevant")
                .role(role)
                .build());
    }

    @AfterEach
    void cleanUp() {
        createdPostIds.forEach(id -> postRepository.findById(id).ifPresent(postRepository::delete));
        createdPostIds.clear();

        List<Long> blobIds = new ArrayList<>();
        for (Long id : createdMediaIds) {
            mediaRepository.findById(id).ifPresent(m -> {
                if (m.getBlob() != null) {
                    blobIds.add(m.getBlob().getId());
                }
                mediaRepository.delete(m);
            });
        }
        mediaRepository.flush();
        createdMediaIds.clear();
        blobIds.stream().distinct()
                .forEach(id -> storageBlobRepository.findById(id).ifPresent(storageBlobRepository::delete));

        for (String name : createdFiles) {
            try {
                Files.deleteIfExists(uploadRoot().resolve(name));
            } catch (IOException ignored) {
                // best effort
            }
        }
        createdFiles.clear();
        userRepository.deleteById(owner.getId());
    }

    private Path uploadRoot() {
        return Paths.get(uploadDir).toAbsolutePath().normalize();
    }

    /** Bytes PNG deterministic: cung mot {@code seed} luon cho ra cung mot noi dung. */
    private byte[] pngBytes(int seed) throws IOException {
        BufferedImage image = new BufferedImage(24, 24, BufferedImage.TYPE_INT_RGB);
        for (int x = 0; x < 24; x++) {
            for (int y = 0; y < 24; y++) {
                image.setRGB(x, y, (x * 31 + y * 17 + seed * 7919) & 0xFFFFFF);
            }
        }
        ByteArrayOutputStream out = new ByteArrayOutputStream();
        ImageIO.write(image, "png", out);
        return out.toByteArray();
    }

    /** Mot media row "kieu cu": co file that tren dia nhung sha256 / blob_id deu NULL. */
    private Media legacyRow(byte[] content) throws IOException {
        String storedFilename = "backfill-test-" + UUID.randomUUID() + ".png";
        Files.createDirectories(uploadRoot());
        Files.write(uploadRoot().resolve(storedFilename), content);
        createdFiles.add(storedFilename);
        return saveLegacyRow(storedFilename, content.length);
    }

    /** Mot media row tro toi file KHONG ton tai tren dia (orphan record). */
    private Media legacyRowWithoutFile() {
        return saveLegacyRow("backfill-test-missing-" + UUID.randomUUID() + ".png", 1234);
    }

    private Media saveLegacyRow(String storedFilename, long sizeBytes) {
        Media media = mediaRepository.save(Media.builder()
                .originalFilename(storedFilename)
                .storedFilename(storedFilename)
                .url("/uploads/" + storedFilename)
                .contentType("image/png")
                .sizeBytes(sizeBytes)
                .optimized(false)
                .uploadedBy(owner)
                .status(Media.Status.ACTIVE)
                .build());
        createdMediaIds.add(media.getId());
        return media;
    }

    private MediaBlobBackfillService.RowOutcome apply(Media media) {
        return backfillService.processRow(media.getId(), false, new HashMap<>());
    }

    private Media reload(Media media) {
        return mediaRepository.findById(media.getId()).orElseThrow();
    }

    @Test
    @DisplayName("row cu binh thuong: tao blob moi, gan sha256/blob_id, ref_count = 1")
    void linksPlainLegacyRow() throws IOException {
        Media legacy = legacyRow(pngBytes(1));
        assertNull(legacy.getSha256(), "precondition: row phai la kieu cu");

        MediaBlobBackfillService.RowOutcome outcome = apply(legacy);

        assertTrue(outcome.blobCreated());
        assertTrue(outcome.rowLinked());
        assertFalse(outcome.duplicateMerged());

        Media after = reload(legacy);
        assertNotNull(after.getSha256(), "sha256 phai duoc gan");
        assertNotNull(after.getBlob(), "blob_id phai duoc gan");
        StorageBlob blob = storageBlobRepository.findById(after.getBlob().getId()).orElseThrow();
        assertEquals(after.getSha256(), blob.getSha256());
        assertEquals(legacy.getStoredFilename(), blob.getStorageKey(),
                "blob moi lay chinh stored_filename cua row lam storage_key");
        assertEquals(1, blob.getRefCount());
    }

    @Test
    @DisplayName("hai row cu trung noi dung: gop 1 blob ref_count=2, row thu hai doi url va post cover theo")
    void mergesDuplicateContentAndRepointsPostCover() throws IOException {
        byte[] content = pngBytes(2);
        Media first = legacyRow(content);
        Media second = legacyRow(content);
        assertNotEquals(first.getStoredFilename(), second.getStoredFilename(),
                "precondition: hai file vat ly khac nhau, noi dung y het");

        Category category = categoryRepository.findAll().stream().findFirst().orElseThrow();
        Post post = postRepository.save(Post.builder()
                .title("Backfill cover test")
                .slug("backfill-cover-" + System.nanoTime())
                .content("body")
                .coverImage(second.getUrl())
                .status(PostStatus.DRAFT)
                .category(category)
                .author(owner)
                .build());
        createdPostIds.add(post.getId());
        String oldUrl = second.getUrl();

        apply(first);
        MediaBlobBackfillService.RowOutcome merged = apply(second);

        assertTrue(merged.duplicateMerged(), "row thu hai phai duoc gop");
        assertFalse(merged.blobCreated(), "sha256 la UNIQUE — khong duoc tao blob thu hai");
        assertEquals(second.getStoredFilename(), merged.orphanedFile(),
                "file vat ly du ra phai duoc bao cao, khong bi xoa");

        Media firstAfter = reload(first);
        Media secondAfter = reload(second);
        assertEquals(firstAfter.getBlob().getId(), secondAfter.getBlob().getId(), "cung mot blob");
        assertEquals(2, storageBlobRepository.findById(firstAfter.getBlob().getId())
                .orElseThrow().getRefCount());
        assertEquals(firstAfter.getStoredFilename(), secondAfter.getStoredFilename(),
                "row thu hai phai tro sang file canonical");
        assertEquals(firstAfter.getUrl(), secondAfter.getUrl(), "url cung phai tro sang canonical");

        Post postAfter = postRepository.findById(post.getId()).orElseThrow();
        assertNotEquals(oldUrl, postAfter.getCoverImage(), "cover_image phai duoc cap nhat");
        assertEquals(secondAfter.getUrl(), postAfter.getCoverImage(),
                "cover_image phai tro sang url canonical, khong duoc thanh broken link");

        assertTrue(Files.exists(uploadRoot().resolve(second.getStoredFilename())),
                "file du ra TUYET DOI khong duoc tu xoa — chi bao cao lai");
    }

    @Test
    @DisplayName("row tro toi file khong ton tai: khong crash, giu nguyen row, vao missingFiles")
    void reportsMissingFileWithoutTouchingRow() {
        Media orphanRecord = legacyRowWithoutFile();

        MediaBlobBackfillService.RowOutcome outcome =
                assertDoesNotThrow(() -> apply(orphanRecord));

        assertEquals(orphanRecord.getStoredFilename(), outcome.missingFile());
        assertFalse(outcome.rowLinked());

        Media after = reload(orphanRecord);
        assertNotNull(after, "row phai duoc giu lai, khong bi xoa");
        assertNull(after.getSha256());
        assertNull(after.getBlob());
    }

    @Test
    @DisplayName("dry-run: bao cao co so lieu nhung DB khong doi mot dong nao")
    void dryRunChangesNothing() throws IOException {
        byte[] content = pngBytes(3);
        Media a = legacyRow(content);
        Media b = legacyRow(content);
        long blobsBefore = storageBlobRepository.count();

        MediaBlobBackfillService.BackfillReport report = backfillService.backfill(true);

        assertTrue(report.dryRun());
        assertTrue(report.scanned() >= 2, "hai row vua tao phai nam trong pham vi quet");
        assertTrue(report.rowsLinked() >= 2, "dry-run van phai bao cao day du nhu ban that");
        assertTrue(report.duplicatesMerged() >= 1,
                "hai file trung noi dung phai duoc nhan ra ngay trong dry-run");

        assertEquals(blobsBefore, storageBlobRepository.count(), "dry-run khong duoc tao blob");
        assertNull(reload(a).getSha256(), "dry-run khong duoc ghi sha256");
        assertNull(reload(a).getBlob());
        assertNull(reload(b).getSha256());
        assertEquals(b.getStoredFilename(), reload(b).getStoredFilename(),
                "dry-run khong duoc doi stored_filename");
    }

    @Test
    @DisplayName("idempotent: chay lai lan hai khong con quet row nao cua minh nua")
    void secondRunScansNothingNew() throws IOException {
        long baseline = backfillService.backfill(true).scanned();

        Media a = legacyRow(pngBytes(4));
        Media b = legacyRow(pngBytes(5));
        assertEquals(baseline + 2, backfillService.backfill(true).scanned(),
                "precondition: hai row moi tao dang nam trong danh sach ung vien");

        apply(a);
        apply(b);

        assertEquals(baseline, backfillService.backfill(true).scanned(),
                "row da co blob khong bao gio duoc quet lai — chay lai la an toan");
        assertEquals(MediaBlobBackfillService.RowOutcome.skipped(),
                backfillService.processRow(a.getId(), false, new HashMap<>()),
                "xu ly lai chinh row do la no-op");
    }

    @Test
    @DisplayName("khong bao gio xoa: file du ra sau khi gop chi duoc liet ke trong orphanedFiles")
    void neverDeletesAnything() throws IOException {
        byte[] content = pngBytes(6);
        Media first = legacyRow(content);
        Media second = legacyRow(content);
        Media missing = legacyRowWithoutFile();

        apply(first);
        apply(second);
        apply(missing);

        assertTrue(Files.exists(uploadRoot().resolve(first.getStoredFilename())));
        assertTrue(Files.exists(uploadRoot().resolve(second.getStoredFilename())));
        assertTrue(mediaRepository.findById(missing.getId()).isPresent());
        StorageBlob blob = reload(first).getBlob();
        assertEquals(2, storageBlobRepository.findById(blob.getId()).orElseThrow().getRefCount());
    }
}
