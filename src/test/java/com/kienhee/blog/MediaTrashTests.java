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
import com.kienhee.blog.repository.UserStorageQuotaRepository;
import com.kienhee.blog.service.MediaFolderService;
import com.kienhee.blog.service.MediaService;
import com.kienhee.blog.service.QuotaService;
import com.kienhee.blog.service.impl.MediaStorageLayout;
import com.kienhee.blog.storage.FilesystemStorage;
import com.kienhee.blog.support.MediaFolderCleanup;
import com.kienhee.blog.support.TestAuth;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.Assumptions;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Nested;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.mock.web.MockMultipartFile;
import org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.setup.MockMvcBuilders;
import org.springframework.web.context.WebApplicationContext;

import javax.imageio.ImageIO;
import java.awt.image.BufferedImage;
import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.List;

import static org.junit.jupiter.api.Assertions.*;
import static org.springframework.security.test.web.servlet.request.SecurityMockMvcRequestPostProcessors.csrf;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

/**
 * Media trash: soft delete, restore, purge, empty trash and the retention sweep.
 *
 * <p>The rule the whole feature hangs on: a soft delete touches nothing physical — the
 * file stays on disk, the blob keeps its ref_count and the uploader keeps paying quota
 * for it. Only a purge frees anything.
 *
 * <p>Repo convention: real database, every row and file created here is removed again.
 */
@SpringBootTest
@DisplayName("Media trash")
class MediaTrashTests {

    @Autowired
    private MediaService mediaService;
    @Autowired
    private MediaFolderService folderService;
    @Autowired
    private QuotaService quotaService;
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
    private PostRepository postRepository;
    @Autowired
    private CategoryRepository categoryRepository;
    @Autowired
    private UserStorageQuotaRepository userStorageQuotaRepository;
    @Autowired
    private WebApplicationContext context;
    @Autowired
    private MediaStorageLayout layout;
    @Autowired
    private FilesystemStorage storage;

    @Value("${app.upload.dir:./uploads}")
    private String uploadDir;

    private MockMvc mockMvc;
    private User uploader;
    private final List<Long> createdMediaIds = new ArrayList<>();
    private final List<Long> createdFolderIds = new ArrayList<>();
    private final List<Long> createdPostIds = new ArrayList<>();

    @BeforeEach
    void setUp() {
        mockMvc = MockMvcBuilders.webAppContextSetup(context)
                .apply(SecurityMockMvcConfigurers.springSecurity())
                .build();
        Role role = roleRepository.findAll().stream().findFirst().orElseThrow();
        uploader = userRepository.save(User.builder()
                .fullName("Trash Test Uploader")
                .email("trash-" + System.nanoTime() + "@test.com")
                .password("{noop}irrelevant")
                .role(role)
                .build());
    }

    @AfterEach
    void cleanUp() {
        createdPostIds.forEach(id -> postRepository.findById(id).ifPresent(postRepository::delete));
        createdPostIds.clear();
        // Detach any avatar reference so the purge below is not refused.
        userRepository.findById(uploader.getId()).ifPresent(u -> {
            u.setAvatarUrl(null);
            userRepository.save(u);
        });
        for (Long id : createdMediaIds) {
            try {
                mediaService.deleteMedia(id);
                mediaService.purgeMedia(id);
            } catch (RuntimeException ignored) {
                // already gone
            }
        }
        createdMediaIds.clear();
        MediaFolderCleanup.deleteFoldersAndDirectories(createdFolderIds, folderRepository, layout, storage);
        createdFolderIds.clear();
        userStorageQuotaRepository.findByUserId(uploader.getId())
                .ifPresent(userStorageQuotaRepository::delete);
        userRepository.deleteById(uploader.getId());
    }

    private MockMultipartFile pngFile(String filename, int size) throws IOException {
        BufferedImage image = new BufferedImage(size, size, BufferedImage.TYPE_INT_RGB);
        for (int x = 0; x < size; x++) {
            for (int y = 0; y < size; y++) {
                image.setRGB(x, y, (x * 37 + y * 11 + filename.hashCode()) & 0xFFFFFF);
            }
        }
        ByteArrayOutputStream out = new ByteArrayOutputStream();
        ImageIO.write(image, "png", out);
        return new MockMultipartFile("file", filename, "image/png", out.toByteArray());
    }

    private Media upload(String name, Long folderId) throws IOException {
        Media media = mediaService.uploadMedia(pngFile(name, 40), uploader.getEmail(), folderId);
        createdMediaIds.add(media.getId());
        return media;
    }

    private MediaFolder folder(String name, Long parentId) {
        MediaFolder created = folderService.createFolder(MediaFolderCreateRequest.builder()
                .name(name)
                .parentId(parentId)
                .build());
        createdFolderIds.add(created.getId());
        return created;
    }

    private Path onDisk(String storedFilename) {
        return Paths.get(uploadDir).toAbsolutePath().normalize().resolve(storedFilename);
    }

    private Media reload(Long id) {
        return mediaRepository.findById(id).orElseThrow();
    }

    @Nested
    @DisplayName("Soft delete")
    class SoftDelete {

        @Test
        @DisplayName("leaves the file on disk and touches neither ref_count nor quota")
        void softDeleteIsNonDestructive() throws IOException {
            Media media = upload("soft-delete.png", null);
            Path file = onDisk(media.getStoragePath());
            StorageBlob blob = storageBlobRepository.findById(media.getBlob().getId()).orElseThrow();
            int refBefore = blob.getRefCount();
            long usedBefore = quotaService.getUsage(uploader.getId()).usedBytes();

            mediaService.deleteMedia(media.getId());

            Media trashed = reload(media.getId());
            assertEquals(Media.Status.TRASHED, trashed.getStatus());
            assertNotNull(trashed.getDeletedAt(), "deleted_at must be stamped");
            assertTrue(Files.exists(file), "a soft delete must not touch the physical file");
            assertEquals(refBefore,
                    storageBlobRepository.findById(blob.getId()).orElseThrow().getRefCount(),
                    "ref_count must be unchanged by a soft delete");
            assertEquals(usedBefore, quotaService.getUsage(uploader.getId()).usedBytes(),
                    "a file in the trash still occupies quota");
        }

        @Test
        @DisplayName("a trashed file disappears from the library grid and from the Media Picker API")
        void trashedItemIsHiddenEverywhere() throws Exception {
            Media media = upload("hidden.png", null);
            assertTrue(mediaService.getAllMedia().stream().anyMatch(m -> m.getId().equals(media.getId())),
                    "precondition: the file is in the grid while active");

            mediaService.deleteMedia(media.getId());

            assertFalse(mediaService.getAllMedia().stream().anyMatch(m -> m.getId().equals(media.getId())),
                    "the grid query must filter out TRASHED rows");
            mockMvc.perform(get("/admin/api/media/library").with(TestAuth.owner()))
                    .andExpect(status().isOk())
                    .andExpect(content().string(org.hamcrest.Matchers.not(
                            org.hamcrest.Matchers.containsString("\"id\":" + media.getId() + ","))));
            assertTrue(mediaService.getTrashedMedia().stream().anyMatch(m -> m.getId().equals(media.getId())),
                    "it must show up in the trash instead");
        }

        @Test
        @DisplayName("refuses a file that is a user's profile photo")
        void blockedWhenUsedAsAvatar() throws IOException {
            Media media = upload("avatar.png", null);
            User owner = userRepository.findById(uploader.getId()).orElseThrow();
            owner.setAvatarUrl(media.getUrl());
            userRepository.save(owner);

            IllegalArgumentException error = assertThrows(IllegalArgumentException.class,
                    () -> mediaService.deleteMedia(media.getId()));
            assertEquals("Cannot delete: this file is used as a user's profile photo.", error.getMessage());
            assertEquals(Media.Status.ACTIVE, reload(media.getId()).getStatus());
        }

        @Test
        @DisplayName("refuses a file that is a post cover image")
        void blockedWhenUsedAsCover() throws IOException {
            Category category = categoryRepository.findAll().stream().findFirst().orElse(null);
            Assumptions.assumeTrue(category != null, "needs at least one seeded category");

            Media media = upload("cover.png", null);
            Post post = postRepository.save(Post.builder()
                    .title("Trash cover test " + System.nanoTime())
                    .slug("trash-cover-" + System.nanoTime())
                    .content("body")
                    .coverImage(media.getUrl())
                    .status(PostStatus.DRAFT)
                    .category(category)
                    .author(uploader)
                    .build());
            createdPostIds.add(post.getId());

            IllegalArgumentException error = assertThrows(IllegalArgumentException.class,
                    () -> mediaService.deleteMedia(media.getId()));
            assertEquals("Cannot delete: this file is used as a cover image on one or more posts.",
                    error.getMessage());
        }
    }

    @Nested
    @DisplayName("Restore")
    class Restore {

        @Test
        @DisplayName("brings the file back to ACTIVE in its original folder")
        void restoreReactivates() throws IOException {
            MediaFolder home = folder("Trash restore " + System.nanoTime(), null);
            Media media = upload("restore.png", home.getId());
            mediaService.deleteMedia(media.getId());

            MediaService.RestoreResult result = mediaService.restoreMedia(media.getId());

            assertFalse(result.movedToRoot(), "the folder is still there, no re-homing expected");
            Media restored = reload(media.getId());
            assertEquals(Media.Status.ACTIVE, restored.getStatus());
            assertNull(restored.getDeletedAt());
            assertTrue(mediaService.getAllMedia().stream().anyMatch(m -> m.getId().equals(media.getId())));
        }

        @Test
        @DisplayName("falls back to Home when the original folder is gone, and says so")
        void restoreToRootWhenFolderPurged() throws IOException {
            MediaFolder parent = folder("Trash gone " + System.nanoTime(), null);
            Media media = upload("orphan.png", parent.getId());

            folderService.deleteFolder(parent.getId());   // folder + its file go to the trash
            folderService.purgeFolder(parent.getId());    // folder row gone; FK unfiles the file

            MediaService.RestoreResult result = mediaService.restoreMedia(media.getId());

            assertTrue(result.movedToRoot() || reload(media.getId()).getFolder() == null,
                    "with the folder purged the file can only come back at Home");
            assertEquals(Media.Status.ACTIVE, reload(media.getId()).getStatus());
        }

        @Test
        @DisplayName("a file that is not in the trash cannot be restored")
        void restoreRejectsActiveFile() throws IOException {
            Media media = upload("active.png", null);
            assertThrows(IllegalArgumentException.class, () -> mediaService.restoreMedia(media.getId()));
        }
    }

    @Nested
    @DisplayName("Purge")
    class Purge {

        @Test
        @DisplayName("only a purge removes the file from disk and gives the quota back")
        void purgeFreesEverything() throws IOException {
            long before = quotaService.getUsage(uploader.getId()).usedBytes();
            Media media = upload("purge.png", null);
            Path file = onDisk(media.getStoragePath());
            Long blobId = media.getBlob().getId();

            mediaService.deleteMedia(media.getId());
            assertTrue(Files.exists(file), "still on disk while only trashed");
            assertNotEquals(before, quotaService.getUsage(uploader.getId()).usedBytes(),
                    "quota is still consumed while the file sits in the trash");

            mediaService.purgeMedia(media.getId());
            createdMediaIds.remove(media.getId());

            assertFalse(Files.exists(file), "purge must remove the physical file");
            assertFalse(mediaRepository.findById(media.getId()).isPresent(), "row must be gone");
            assertFalse(storageBlobRepository.existsById(blobId), "unreferenced blob must be gone");
            assertEquals(before, quotaService.getUsage(uploader.getId()).usedBytes(),
                    "purge must release the quota");
        }

        @Test
        @DisplayName("refuses to purge a file that is not in the trash")
        void purgeRequiresTrashedState() throws IOException {
            Media media = upload("not-trashed.png", null);
            IllegalArgumentException error = assertThrows(IllegalArgumentException.class,
                    () -> mediaService.purgeMedia(media.getId()));
            assertTrue(error.getMessage().contains("move the file to the trash first"));
            assertTrue(Files.exists(onDisk(media.getStoragePath())));
        }

        @Test
        @DisplayName("empty trash purges everything that is in it and nothing that is not")
        void emptyTrashPurgesOnlyTrashedItems() throws IOException {
            Media keep = upload("keep.png", null);
            Media drop = upload("drop.png", null);
            mediaService.deleteMedia(drop.getId());

            MediaService.BulkDeleteResult result = mediaService.emptyTrash();

            assertTrue(result.deletedCount() >= 1);
            assertFalse(mediaRepository.findById(drop.getId()).isPresent());
            createdMediaIds.remove(drop.getId());
            assertTrue(mediaRepository.findById(keep.getId()).isPresent(), "active files must survive");
        }
    }

    @Nested
    @DisplayName("Auto purge (retention sweep)")
    class AutoPurge {

        @Test
        @DisplayName("purges only items whose deleted_at is older than the retention window")
        void onlyExpiredItemsArePurged() throws IOException {
            Media old = upload("expired.png", null);
            Media fresh = upload("fresh.png", null);
            mediaService.deleteMedia(old.getId());
            mediaService.deleteMedia(fresh.getId());

            // Backdate one of them past a 30-day retention window.
            Media stale = reload(old.getId());
            stale.setDeletedAt(LocalDateTime.now().minusDays(45));
            mediaRepository.save(stale);

            int purged = mediaService.purgeExpired(30);

            assertTrue(purged >= 1);
            assertFalse(mediaRepository.findById(old.getId()).isPresent(),
                    "the item past retention must be gone");
            createdMediaIds.remove(old.getId());
            Media survivor = reload(fresh.getId());
            assertEquals(Media.Status.TRASHED, survivor.getStatus(),
                    "a recently trashed item must stay in the trash");
        }
    }

    @Nested
    @DisplayName("Folders")
    class Folders {

        @Test
        @DisplayName("deleting a folder trashes it together with its files, and restoring brings both back")
        void folderCascadesAndRestores() throws IOException {
            MediaFolder box = folder("Trash box " + System.nanoTime(), null);
            Media inside = upload("inside.png", box.getId());

            folderService.deleteFolder(box.getId());

            assertEquals(MediaFolder.Status.TRASHED, folderRepository.findById(box.getId()).orElseThrow().getStatus());
            assertEquals(Media.Status.TRASHED, reload(inside.getId()).getStatus(),
                    "files must follow the folder into the trash, never stay behind invisibly");
            assertTrue(folderService.getFolderTree().stream()
                            .noneMatch(n -> n.folder().getId().equals(box.getId())),
                    "a trashed folder must not appear in the sidebar tree");
            assertTrue(Files.exists(onDisk(reload(inside.getId()).getStoragePath())),
                    "nothing physical happens when a folder is trashed");

            MediaFolderService.FolderRestoreResult result = folderService.restoreFolder(box.getId());

            assertFalse(result.movedToRoot());
            assertEquals(MediaFolder.Status.ACTIVE, folderRepository.findById(box.getId()).orElseThrow().getStatus());
            assertEquals(Media.Status.ACTIVE, reload(inside.getId()).getStatus());
        }

        @Test
        @DisplayName("deleting a folder trashes its subfolders and their files; restoring brings the batch back")
        void subtreeCascadesAndRestores() throws IOException {
            MediaFolder parent = folder("Trash parent " + System.nanoTime(), null);
            MediaFolder child = folder("Trash child " + System.nanoTime(), parent.getId());
            Media deep = upload("deep.png", child.getId());

            folderService.deleteFolder(parent.getId());

            assertEquals(MediaFolder.Status.TRASHED, folderRepository.findById(child.getId()).orElseThrow().getStatus());
            assertEquals(Media.Status.TRASHED, reload(deep.getId()).getStatus());

            folderService.restoreFolder(parent.getId());

            assertEquals(MediaFolder.Status.ACTIVE, folderRepository.findById(child.getId()).orElseThrow().getStatus());
            assertEquals(Media.Status.ACTIVE, reload(deep.getId()).getStatus());
        }

        @Test
        @DisplayName("restoring a folder leaves a file that was trashed on its own earlier in the trash")
        void restoreKeepsIndividuallyTrashedFile() throws Exception {
            MediaFolder box = folder("Trash solo " + System.nanoTime(), null);
            Media earlier = upload("earlier.png", box.getId());
            Media later = upload("later.png", box.getId());
            mediaService.deleteMedia(earlier.getId());
            // push the individual delete clearly outside the folder's batch window
            Media e = reload(earlier.getId());
            e.setDeletedAt(e.getDeletedAt().minusMinutes(5));
            mediaRepository.save(e);

            folderService.deleteFolder(box.getId());
            folderService.restoreFolder(box.getId());

            assertEquals(Media.Status.ACTIVE, reload(later.getId()).getStatus());
            assertEquals(Media.Status.TRASHED, reload(earlier.getId()).getStatus(),
                    "a file deleted on its own before the folder must stay in the trash");
        }
    }

    @Nested
    @DisplayName("Controller & permissions")
    class ControllerTests {

        @Test
        @DisplayName("GET /admin/media/trash renders for a media viewer")
        void trashPageRenders() throws Exception {
            mockMvc.perform(get("/admin/media/trash").with(TestAuth.owner()))
                    .andExpect(status().isOk())
                    .andExpect(view().name("admin/media/trash"))
                    .andExpect(model().attributeExists("trashedMedia", "trashSummary", "retentionDays"));
        }

        @Test
        @DisplayName("POST delete then restore round-trips through the controller")
        void deleteAndRestoreViaController() throws Exception {
            Media media = upload("controller.png", null);

            mockMvc.perform(post("/admin/media/" + media.getId() + "/delete")
                            .with(TestAuth.owner()).with(csrf()))
                    .andExpect(status().is3xxRedirection());
            assertEquals(Media.Status.TRASHED, reload(media.getId()).getStatus());

            mockMvc.perform(post("/admin/media/" + media.getId() + "/restore")
                            .with(TestAuth.owner()).with(csrf()))
                    .andExpect(status().is3xxRedirection())
                    .andExpect(redirectedUrl("/admin/media/trash"));
            assertEquals(Media.Status.ACTIVE, reload(media.getId()).getStatus());
        }

        @Test
        @DisplayName("permanent delete needs more than media:delete")
        void purgeNeedsElevatedPermission() throws Exception {
            Media media = upload("perm.png", null);
            mediaService.deleteMedia(media.getId());

            mockMvc.perform(post("/admin/media/" + media.getId() + "/delete-permanent")
                            .with(TestAuth.withPermissions("editor@test.com", "media:view", "media:delete"))
                            .with(csrf()))
                    .andExpect(status().isForbidden());
            assertTrue(mediaRepository.findById(media.getId()).isPresent(), "nothing may be destroyed");

            mockMvc.perform(post("/admin/media/" + media.getId() + "/delete-permanent")
                            .with(TestAuth.owner()).with(csrf()))
                    .andExpect(status().is3xxRedirection());
            assertFalse(mediaRepository.findById(media.getId()).isPresent());
            createdMediaIds.remove(media.getId());
        }
    }
}
