package com.kienhee.blog;

import com.kienhee.blog.dto.MediaFolderCreateRequest;
import com.kienhee.blog.entity.Category;
import com.kienhee.blog.entity.Media;
import com.kienhee.blog.entity.MediaFolder;
import com.kienhee.blog.entity.Post;
import com.kienhee.blog.entity.PostStatus;
import com.kienhee.blog.entity.Role;
import com.kienhee.blog.entity.StorageBlob;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.CategoryRepository;
import com.kienhee.blog.repository.MediaFolderRepository;
import com.kienhee.blog.repository.MediaRepository;
import com.kienhee.blog.repository.PostRepository;
import com.kienhee.blog.repository.RoleRepository;
import com.kienhee.blog.repository.StorageBlobRepository;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.service.MediaFilesystemLayoutMigrationService;
import com.kienhee.blog.service.MediaFilesystemLayoutMigrationService.MigrationPlan;
import com.kienhee.blog.service.MediaFilesystemLayoutMigrationService.RowOutcome;
import com.kienhee.blog.service.MediaFilesystemLayoutMigrationService.RowStatus;
import com.kienhee.blog.service.MediaFolderService;
import com.kienhee.blog.service.impl.MediaStorageLayout;
import com.kienhee.blog.storage.FilesystemStorage;
import com.kienhee.blog.support.MediaFolderCleanup;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.boot.test.context.SpringBootTest;

import java.io.IOException;
import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.util.ArrayList;
import java.util.HashSet;
import java.util.List;
import java.util.Set;
import java.util.UUID;

import static org.junit.jupiter.api.Assertions.assertArrayEquals;
import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertFalse;
import static org.junit.jupiter.api.Assertions.assertNotEquals;
import static org.junit.jupiter.api.Assertions.assertNull;
import static org.junit.jupiter.api.Assertions.assertTrue;

/**
 * Flat pre-V16 uploads -> filesystem mirror. Every scenario runs {@code processRow} (and the sweep
 * scoped to its own file names) on fixtures it creates: the full-table job is never run for real
 * here, because it would touch the real legacy rows in the dev DB.
 */
@SpringBootTest
@DisplayName("Media filesystem layout migration")
class MediaFilesystemLayoutMigrationTests {

    @Autowired
    private MediaFilesystemLayoutMigrationService migration;
    @Autowired
    private MediaRepository mediaRepository;
    @Autowired
    private StorageBlobRepository blobRepository;
    @Autowired
    private MediaFolderRepository folderRepository;
    @Autowired
    private MediaFolderService folderService;
    @Autowired
    private UserRepository userRepository;
    @Autowired
    private RoleRepository roleRepository;
    @Autowired
    private PostRepository postRepository;
    @Autowired
    private CategoryRepository categoryRepository;
    @Autowired
    private MediaStorageLayout layout;
    @Autowired
    private FilesystemStorage storage;

    @Value("${app.upload.dir:./uploads}")
    private String uploadDir;

    private String tag;
    private User owner;
    private final List<Long> mediaIds = new ArrayList<>();
    private final Set<Long> blobIds = new HashSet<>();
    private final List<Long> folderIds = new ArrayList<>();
    private final List<Long> userIds = new ArrayList<>();
    private final List<Long> postIds = new ArrayList<>();
    private final List<String> legacyFiles = new ArrayList<>();

    @BeforeEach
    void setUp() {
        tag = "mig" + System.nanoTime();
        Role role = roleRepository.findAll().stream().findFirst().orElseThrow();
        owner = userRepository.save(User.builder().fullName("Migration Owner").email(tag + "@test.com")
                .password("{noop}x").role(role).build());
        userIds.add(owner.getId());
    }

    @AfterEach
    void cleanUp() throws IOException {
        postIds.forEach(id -> postRepository.findById(id).ifPresent(postRepository::delete));
        List<String> newPaths = new ArrayList<>();
        for (Long id : mediaIds) {
            mediaRepository.findById(id).ifPresent(m -> {
                if (m.getBlob() != null) {
                    blobIds.add(m.getBlob().getId());
                }
                if (m.getStoragePath() != null) {
                    newPaths.add(m.getStoragePath());
                }
                mediaRepository.delete(m);
            });
            // test-owned copies only
            for (var thumb : layout.thumbnailCandidates(id)) {
                storage.deleteFile(thumb);
            }
        }
        mediaRepository.flush();
        blobIds.forEach(id -> blobRepository.findById(id).ifPresent(blobRepository::delete));
        for (String p : newPaths) {
            Files.deleteIfExists(root().resolve(p));
        }
        for (String name : legacyFiles) {
            Files.deleteIfExists(root().resolve(name));
            Files.deleteIfExists(root().resolve(".orphaned").resolve(name));
        }
        try {
            storage.deleteDirectoryIfEmpty(com.kienhee.blog.storage.StoragePath.of(".orphaned"));
        } catch (RuntimeException ignored) {
            // not empty (holds real files): leave it
        }
        MediaFolderCleanup.deleteFoldersAndDirectories(folderIds, folderRepository, layout, storage);
        userIds.forEach(userRepository::deleteById);
    }

    // ---- fixtures ---------------------------------------------------------------

    private Path root() {
        return Paths.get(uploadDir).toAbsolutePath().normalize();
    }

    private String legacyFile(String ext, byte[] content) throws IOException {
        String name = UUID.randomUUID() + ext;
        Files.createDirectories(root());
        Files.write(root().resolve(name), content);
        legacyFiles.add(name);
        return name;
    }

    private StorageBlob blob(String key, int refCount) {
        StorageBlob b = blobRepository.save(StorageBlob.builder().sha256("0".repeat(64)).storageKey(key)
                .storageProvider("LOCAL").sizeBytes(4).contentType("image/png").refCount(refCount).build());
        blobIds.add(b.getId());
        return b;
    }

    private Media legacyRow(String originalName, String storedName, MediaFolder folder, StorageBlob blob, String thumbName) {
        Media m = mediaRepository.save(Media.builder()
                .originalFilename(originalName).storedFilename(storedName).url("/uploads/" + storedName)
                .thumbnailUrl(thumbName == null ? null : "/uploads/" + thumbName)
                .contentType("image/png").sizeBytes(4).optimized(false).uploadedBy(owner)
                .folder(folder).blob(blob).sha256(blob == null ? null : blob.getSha256())
                .status(Media.Status.ACTIVE).build());
        mediaIds.add(m.getId());
        return m;
    }

    private MediaFolder folder(String name) {
        MediaFolder f = folderService.createFolder(MediaFolderCreateRequest.builder().name(name).build());
        folderIds.add(f.getId());
        return f;
    }

    private RowOutcome apply(Long id, MigrationPlan plan) {
        RowOutcome outcome = migration.processRow(id, false, plan);
        plan.record(outcome);
        return outcome;
    }

    private Media reload(Long id) {
        return mediaRepository.findById(id).orElseThrow();
    }

    private byte[] bytes(String s) {
        return s.getBytes(StandardCharsets.UTF_8);
    }

    // ---- scenarios --------------------------------------------------------------

    @Test
    @DisplayName("plain row lands in its folder directory with its original name, new URL, blob key")
    void plainRow() throws IOException {
        MediaFolder f = folder("Hei " + tag);
        String stored = legacyFile(".png", bytes("PLAIN"));
        StorageBlob b = blob(stored, 1);
        Media m = legacyRow("Ảnh Gốc " + tag + ".png", stored, f, b, null);

        RowOutcome out = apply(m.getId(), new MigrationPlan());

        assertEquals(RowStatus.MIGRATED, out.status());
        Media after = reload(m.getId());
        String expectedName = "Ảnh Gốc " + tag + ".png";
        assertEquals(layout.directoryOf(f).resolve(expectedName).toString(), after.getStoragePath());
        assertEquals(expectedName, after.getStoredFilename());
        assertEquals(MediaStorageLayout.publicUrl(m.getId(), expectedName), after.getUrl());
        assertTrue(after.getUrl().startsWith("/media/" + m.getId() + "/"));
        assertArrayEquals(bytes("PLAIN"), Files.readAllBytes(root().resolve(after.getStoragePath())));
        StorageBlob reloaded = blobRepository.findById(b.getId()).orElseThrow();
        assertEquals(after.getStoragePath(), reloaded.getStorageKey());
        assertEquals(1, reloaded.getRefCount());
        assertTrue(Files.exists(root().resolve(stored)), "source is never deleted by the row step");
    }

    @Test
    @DisplayName("3 rows on one shared file -> 3 files, 3 blobs ref 1, avatar goes to the avatars-folder row, source only moved to .orphaned")
    void sharedFileSplit() throws IOException {
        MediaFolder hei = folder("Hei " + tag);
        MediaFolder avatars = folder("Avatars " + tag);
        String stored = legacyFile(".webp", bytes("SHARED"));
        StorageBlob shared = blob(stored, 3);
        String original = "original-" + tag + ".webp";
        Media home = legacyRow(original, stored, null, shared, null);
        Media inHei = legacyRow(original, stored, hei, shared, null);
        Media inAvatars = legacyRow(original, stored, avatars, shared, null);

        Role role = roleRepository.findAll().stream().findFirst().orElseThrow();
        User avatarUser = userRepository.save(User.builder().fullName("Avatar").email("av-" + tag + "@test.com")
                .password("{noop}x").role(role).avatarUrl("/uploads/" + stored).build());
        userIds.add(avatarUser.getId());
        Category category = categoryRepository.findAll().stream().findFirst().orElseThrow();
        Post post = postRepository.save(Post.builder().title("Mig").slug("mig-" + tag).content("body")
                .coverImage("/uploads/" + stored).status(PostStatus.DRAFT).category(category).author(owner).build());
        postIds.add(post.getId());

        MigrationPlan plan = new MigrationPlan();
        List<RowOutcome> outcomes = List.of(apply(home.getId(), plan), apply(inHei.getId(), plan), apply(inAvatars.getId(), plan));
        outcomes.forEach(o -> assertTrue(o.sharedSource()));

        Set<String> paths = new HashSet<>();
        Set<Long> blobs = new HashSet<>();
        for (Media m : List.of(home, inHei, inAvatars)) {
            Media after = reload(m.getId());
            paths.add(after.getStoragePath());
            blobs.add(after.getBlob().getId());
            StorageBlob b = blobRepository.findById(after.getBlob().getId()).orElseThrow();
            blobIds.add(b.getId());
            assertEquals(1, b.getRefCount());
            assertEquals(after.getStoragePath(), b.getStorageKey());
            assertArrayEquals(bytes("SHARED"), Files.readAllBytes(root().resolve(after.getStoragePath())));
        }
        assertEquals(3, paths.size());
        assertEquals(3, blobs.size());
        assertTrue(blobs.contains(shared.getId()), "old blob is reused by one row");
        assertEquals(original, StoragePathName.of(reload(home.getId()).getStoragePath()));

        // Avatar: avatars-folder row wins. Cover: no folder preference -> uploaded by author, lowest id.
        Media avatarsAfter = reload(inAvatars.getId());
        assertEquals(avatarsAfter.getUrl(), userRepository.findById(avatarUser.getId()).orElseThrow().getAvatarUrl());
        assertTrue(Files.exists(root().resolve(avatarsAfter.getStoragePath())));
        assertEquals(reload(home.getId()).getUrl(), postRepository.findById(post.getId()).orElseThrow().getCoverImage());

        // Sweep (scoped to this test's file) moves, never deletes.
        List<String> moved = migration.moveUnreferencedLegacyFiles(List.of(stored), false, plan);
        assertEquals(1, moved.size());
        assertFalse(Files.exists(root().resolve(stored)));
        assertArrayEquals(bytes("SHARED"), Files.readAllBytes(root().resolve(".orphaned").resolve(stored)));
    }

    @Test
    @DisplayName("sweep keeps a source still used by an unmigrated row")
    void sweepKeepsSourceInUse() throws IOException {
        String stored = legacyFile(".png", bytes("KEEP"));
        StorageBlob b = blob(stored, 2);
        Media first = legacyRow("keep-" + tag + ".png", stored, null, b, null);
        legacyRow("keep-" + tag + ".png", stored, null, b, null);
        MigrationPlan plan = new MigrationPlan();
        apply(first.getId(), plan);

        assertTrue(migration.moveUnreferencedLegacyFiles(List.of(stored), false, plan).isEmpty());
        assertTrue(Files.exists(root().resolve(stored)));
    }

    @Test
    @DisplayName("legacy thumbnail is copied to .thumbnails/<id>_thumb.jpg and thumbnail_url uses the new endpoint")
    void thumbnailMoved() throws IOException {
        String stored = legacyFile(".jpg", bytes("IMG"));
        String thumb = stored.replace(".jpg", "_thumb.jpg");
        Files.write(root().resolve(thumb), bytes("THUMB"));
        legacyFiles.add(thumb);
        Media m = legacyRow("t-" + tag + ".jpg", stored, null, blob(stored, 1), thumb);

        MigrationPlan plan = new MigrationPlan();
        RowOutcome out = apply(m.getId(), plan);

        assertTrue(out.thumbnailMoved());
        Media after = reload(m.getId());
        assertEquals(MediaStorageLayout.thumbnailUrl(m.getId()), after.getThumbnailUrl());
        Path newThumb = root().resolve(".thumbnails").resolve(m.getId() + "_thumb.jpg");
        assertArrayEquals(bytes("THUMB"), Files.readAllBytes(newThumb));
        List<String> moved = migration.moveUnreferencedLegacyFiles(List.of(stored, thumb), false, plan);
        assertEquals(2, moved.size());
        assertTrue(Files.exists(root().resolve(".orphaned").resolve(thumb)));
        assertTrue(Files.exists(root().resolve(".orphaned").resolve(stored)));
    }

    @Test
    @DisplayName("case-insensitive name collision gets a (2) suffix")
    void caseInsensitiveCollision() throws IOException {
        String a = legacyFile(".png", bytes("A"));
        String b = legacyFile(".png", bytes("B"));
        Media first = legacyRow("Dup-" + tag + ".PNG", a, null, blob(a, 1), null);
        Media second = legacyRow("dup-" + tag + ".png", b, null, blob(b, 1), null);
        MigrationPlan plan = new MigrationPlan();
        apply(first.getId(), plan);
        apply(second.getId(), plan);
        assertEquals("dup-" + tag + " (2).png", reload(second.getId()).getStoredFilename());
    }

    @Test
    @DisplayName("missing source file: no crash, row untouched")
    void missingFile() {
        String stored = UUID.randomUUID() + ".png";
        Media m = legacyRow("missing.png", stored, null, null, null);
        RowOutcome out = apply(m.getId(), new MigrationPlan());
        assertEquals(RowStatus.MISSING_FILE, out.status());
        Media after = reload(m.getId());
        assertNull(after.getStoragePath());
        assertEquals("/uploads/" + stored, after.getUrl());
    }

    @Test
    @DisplayName("dry-run changes neither DB nor disk but reports the same plan")
    void dryRun() throws IOException {
        MediaFolder f = folder("Dry " + tag);
        String stored = legacyFile(".png", bytes("DRY"));
        StorageBlob shared = blob(stored, 2);
        Media one = legacyRow("dry-" + tag + ".png", stored, f, shared, null);
        Media two = legacyRow("dry-" + tag + ".png", stored, f, shared, null);
        Role role = roleRepository.findAll().stream().findFirst().orElseThrow();
        User u = userRepository.save(User.builder().fullName("Dry").email("dry-" + tag + "@test.com")
                .password("{noop}x").role(role).avatarUrl("/uploads/" + stored).build());
        userIds.add(u.getId());

        MigrationPlan plan = new MigrationPlan();
        RowOutcome o1 = migration.processRow(one.getId(), true, plan);
        plan.record(o1);
        RowOutcome o2 = migration.processRow(two.getId(), true, plan);
        plan.record(o2);

        assertTrue(o1.blobCreated());
        assertFalse(o2.blobCreated(), "last row reuses the blob, same as the real run");
        assertNotEquals(o1.newPath(), o2.newPath(), "dry-run must see names planned earlier");
        assertEquals(1, o1.rewrittenUserIds().size() + o2.rewrittenUserIds().size());
        assertEquals(1, migration.moveUnreferencedLegacyFiles(List.of(stored), true, plan).size());

        for (Media m : List.of(one, two)) {
            Media after = reload(m.getId());
            assertNull(after.getStoragePath());
            assertEquals("/uploads/" + stored, after.getUrl());
        }
        assertEquals(2, blobRepository.findById(shared.getId()).orElseThrow().getRefCount());
        assertEquals("/uploads/" + stored, userRepository.findById(u.getId()).orElseThrow().getAvatarUrl());
        assertTrue(Files.exists(root().resolve(stored)));
        assertFalse(Files.exists(root().resolve(o1.newPath())));
        assertFalse(Files.exists(root().resolve(".orphaned").resolve(stored)));
    }

    @Test
    @DisplayName("second run does nothing")
    void secondRunIsNoop() throws IOException {
        String stored = legacyFile(".png", bytes("TWICE"));
        Media m = legacyRow("twice-" + tag + ".png", stored, null, blob(stored, 1), null);
        apply(m.getId(), new MigrationPlan());
        Media first = reload(m.getId());

        RowOutcome again = apply(m.getId(), new MigrationPlan());

        assertEquals(RowStatus.SKIPPED, again.status());
        Media second = reload(m.getId());
        assertEquals(first.getStoragePath(), second.getStoragePath());
        assertEquals(first.getUrl(), second.getUrl());
        assertEquals(first.getBlob().getId(), second.getBlob().getId());
    }

    /** Tiny helper so the test reads naturally. */
    private static final class StoragePathName {
        static String of(String path) {
            return com.kienhee.blog.storage.StoragePath.of(path).filename();
        }
    }
}
