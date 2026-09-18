package com.kienhee.blog;

import com.kienhee.blog.entity.Media;
import com.kienhee.blog.entity.MediaFolder;
import com.kienhee.blog.entity.Role;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.MediaFolderRepository;
import com.kienhee.blog.repository.MediaRepository;
import com.kienhee.blog.repository.RoleRepository;
import com.kienhee.blog.repository.StorageBlobRepository;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.repository.UserStorageQuotaRepository;
import com.kienhee.blog.service.MediaService;
import com.kienhee.blog.service.impl.MediaStorageLayout;
import com.kienhee.blog.storage.FilesystemStorage;
import com.kienhee.blog.storage.StoragePath;
import com.kienhee.blog.support.MediaFolderCleanup;
import com.kienhee.blog.support.TestAuth;
import com.kienhee.blog.support.TestLocale;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Nested;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.mock.web.MockMultipartFile;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.MvcResult;
import org.springframework.test.web.servlet.request.RequestPostProcessor;
import org.springframework.test.web.servlet.setup.MockMvcBuilders;
import org.springframework.web.context.WebApplicationContext;

import javax.imageio.ImageIO;
import java.awt.image.BufferedImage;
import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.util.ArrayList;
import java.util.List;
import java.util.Optional;

import static org.hamcrest.Matchers.notNullValue;
import static org.junit.jupiter.api.Assertions.*;
import static org.springframework.security.test.web.servlet.request.SecurityMockMvcRequestPostProcessors.csrf;
import static org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers.springSecurity;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.multipart;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

/**
 * End-to-end CRUD for the media library, driven through the real controllers exactly as the
 * explorer UI calls them, and checked against both the database and the directory tree on disk.
 *
 * <p>Repo convention: real database, every row, directory and file created here is removed again.
 */
@SpringBootTest
@DisplayName("Media library CRUD (folders + items)")
class MediaCrudTests {

    @Autowired private WebApplicationContext context;
    @Autowired private MediaService mediaService;
    @Autowired private MediaRepository mediaRepository;
    @Autowired private MediaFolderRepository folderRepository;
    @Autowired private StorageBlobRepository blobRepository;
    @Autowired private UserRepository userRepository;
    @Autowired private RoleRepository roleRepository;
    @Autowired private UserStorageQuotaRepository quotaRepository;
    @Autowired private MediaStorageLayout layout;
    @Autowired private FilesystemStorage storage;

    private MockMvc mockMvc;
    private User user;
    private RequestPostProcessor owner;
    private String tag;
    private final List<Long> folderIds = new ArrayList<>();

    @BeforeEach
    void setUp() {
        mockMvc = MockMvcBuilders.webAppContextSetup(context).apply(springSecurity())
                .defaultRequest(TestLocale.englishByDefault()).build();
        Role role = roleRepository.findAll().stream().findFirst().orElseThrow();
        tag = "Crud" + System.nanoTime();
        user = userRepository.save(User.builder()
                .fullName("CRUD Test User")
                .email(tag.toLowerCase() + "@test.com")
                .password("{noop}irrelevant")
                .role(role)
                .build());
        owner = TestAuth.owner(user.getEmail());
    }

    @AfterEach
    void cleanUp() {
        userRepository.findById(user.getId()).ifPresent(u -> {
            u.setAvatarUrl(null);
            userRepository.save(u);
        });
        for (Media media : mediaRepository.findAll()) {
            if (media.getUploadedBy() != null && media.getUploadedBy().getId().equals(user.getId())) {
                try {
                    mediaService.deleteMedia(media.getId());
                } catch (RuntimeException ignored) {
                    // already trashed
                }
                try {
                    mediaService.purgeMedia(media.getId());
                } catch (RuntimeException ignored) {
                    // already gone
                }
            }
        }
        MediaFolderCleanup.deleteFoldersAndDirectories(folderIds, folderRepository, layout, storage);
        quotaRepository.findByUserId(user.getId()).ifPresent(quotaRepository::delete);
        userRepository.deleteById(user.getId());
    }

    // ---------------------------------------------------------------- helpers

    private byte[] png(String seed) throws IOException {
        BufferedImage image = new BufferedImage(24, 24, BufferedImage.TYPE_INT_RGB);
        for (int x = 0; x < 24; x++) {
            for (int y = 0; y < 24; y++) {
                image.setRGB(x, y, (x * 31 + y * 17 + seed.hashCode()) & 0xFFFFFF);
            }
        }
        ByteArrayOutputStream out = new ByteArrayOutputStream();
        ImageIO.write(image, "png", out);
        return out.toByteArray();
    }

    private MvcResult postForm(String url, String... params) throws Exception {
        var request = post(url).with(owner).with(csrf());
        for (int i = 0; i + 1 < params.length; i += 2) {
            request.param(params[i], params[i + 1]);
        }
        return mockMvc.perform(request).andExpect(status().is3xxRedirection()).andReturn();
    }

    private MediaFolder createFolder(String name, Long parentId) throws Exception {
        if (parentId == null) {
            postForm("/admin/media/folders", "name", name);
        } else {
            postForm("/admin/media/folders", "name", name, "parentId", String.valueOf(parentId));
        }
        MediaFolder folder = findFolder(name, parentId).orElseThrow(() -> new AssertionError("folder not created: " + name));
        folderIds.add(folder.getId());
        return folder;
    }

    private Optional<MediaFolder> findFolder(String name, Long parentId) {
        return folderRepository.findAll().stream()
                .filter(f -> f.getName().equals(name))
                .filter(f -> parentId == null
                        ? f.getParent() == null
                        : f.getPath().startsWith(folderRepository.findById(parentId).orElseThrow().getPath()) && f.getDepth() > 0)
                .findFirst();
    }

    private MediaFolder reloadFolder(Long id) {
        return folderRepository.findById(id).orElseThrow();
    }

    private Media upload(String filename, Long folderId) throws Exception {
        var request = multipart("/admin/media")
                .file(new MockMultipartFile("files", filename, "image/png", png(filename + System.nanoTime())))
                .with(owner).with(csrf());
        if (folderId != null) {
            request.param("folderId", String.valueOf(folderId));
        }
        mockMvc.perform(request).andExpect(status().is3xxRedirection());
        return mediaRepository.findAll().stream()
                .filter(m -> filename.equals(m.getOriginalFilename()))
                .filter(m -> m.getUploadedBy() != null && m.getUploadedBy().getId().equals(user.getId()))
                .reduce((first, second) -> second)
                .orElseThrow(() -> new AssertionError("upload not stored: " + filename));
    }

    private Media reload(Long id) {
        return mediaRepository.findById(id).orElseThrow();
    }

    private boolean fileOnDisk(Media media) {
        return storage.fileExists(StoragePath.of(media.getStoragePath()));
    }

    private boolean dirOnDisk(MediaFolder folder) {
        return storage.directoryExists(layout.directoryOf(folder));
    }

    // ================================================================ FOLDERS

    @Nested
    @DisplayName("Folder CRUD")
    class Folders {

        @Test
        @DisplayName("create at Home creates a real directory")
        void createAtHome() throws Exception {
            MediaFolder root = createFolder(tag + " Root", null);
            assertEquals(0, root.getDepth());
            assertEquals("/" + root.getId() + "/", root.getPath());
            assertTrue(dirOnDisk(root));
        }

        @Test
        @DisplayName("create inside a folder nests it on disk and redirects back into the parent")
        void createNested() throws Exception {
            MediaFolder root = createFolder(tag + " Root", null);
            mockMvc.perform(post("/admin/media/folders").param("name", tag + " Child")
                            .param("parentId", String.valueOf(root.getId())).with(owner).with(csrf()))
                    .andExpect(redirectedUrl("/admin/media?folder=" + root.getId()));
            MediaFolder child = findFolder(tag + " Child", root.getId()).orElseThrow();
            folderIds.add(child.getId());

            assertEquals(1, child.getDepth());
            assertEquals(root.getPath() + child.getId() + "/", child.getPath());
            assertTrue(dirOnDisk(child));
        }

        @Test
        @DisplayName("duplicate name in the same parent is rejected; the same name elsewhere is allowed")
        void duplicateNames() throws Exception {
            MediaFolder root = createFolder(tag + " Root", null);
            createFolder(tag + " Same", root.getId());

            mockMvc.perform(post("/admin/media/folders").param("name", tag + " Same")
                            .param("parentId", String.valueOf(root.getId())).with(owner).with(csrf()))
                    .andExpect(flash().attribute("errorMessage", notNullValue()));
            assertEquals(1, folderRepository.findAll().stream().filter(f -> f.getName().equals(tag + " Same")).count());

            createFolder(tag + " Same", null);
            assertEquals(2, folderRepository.findAll().stream().filter(f -> f.getName().equals(tag + " Same")).count());
        }

        @Test
        @DisplayName("a name shorter than two characters is rejected")
        void nameTooShort() throws Exception {
            long before = folderRepository.count();
            mockMvc.perform(post("/admin/media/folders").param("name", "x").with(owner).with(csrf()))
                    .andExpect(flash().attribute("errorMessage", notNullValue()));
            assertEquals(before, folderRepository.count());
        }

        @Test
        @DisplayName("the library API returns a folder and its subfolder")
        void readTree() throws Exception {
            MediaFolder root = createFolder(tag + " Root", null);
            MediaFolder child = createFolder(tag + " Child", root.getId());
            mockMvc.perform(get("/admin/api/media/library").with(owner))
                    .andExpect(status().isOk())
                    .andExpect(jsonPath("$.folders[?(@.id == " + child.getId() + ")].parentId")
                            .value(org.hamcrest.Matchers.hasItem(root.getId().intValue())))
                    .andExpect(jsonPath("$.folders[?(@.id == " + child.getId() + ")].depth")
                            .value(org.hamcrest.Matchers.hasItem(1)));
        }

        @Test
        @DisplayName("rename twice moves the directory and rewrites storage_path AND storage_key of files inside")
        void renameTwice() throws Exception {
            MediaFolder root = createFolder(tag + " Root", null);
            Media file = upload("inside.png", root.getId());

            postForm("/admin/media/folders/" + root.getId() + "/rename", "name", tag + " Renamed");
            postForm("/admin/media/folders/" + root.getId() + "/rename", "name", tag + " Renamed Again");

            MediaFolder renamed = reloadFolder(root.getId());
            Media moved = reload(file.getId());
            assertEquals(tag + " Renamed Again", renamed.getName());
            assertTrue(dirOnDisk(renamed));
            assertTrue(moved.getStoragePath().startsWith(renamed.getSlug() + "/"), moved.getStoragePath());
            assertTrue(fileOnDisk(moved));
            assertEquals(moved.getStoragePath(), blobRepository.findById(moved.getBlob().getId()).orElseThrow().getStorageKey());
            assertEquals(file.getUrl(), moved.getUrl(), "public URL must not change on rename");
        }

        @Test
        @DisplayName("rename onto a sibling's name is rejected")
        void renameCollision() throws Exception {
            createFolder(tag + " Taken", null);
            MediaFolder other = createFolder(tag + " Other", null);
            mockMvc.perform(post("/admin/media/folders/" + other.getId() + "/rename").param("name", tag + " Taken")
                            .with(owner).with(csrf()))
                    .andExpect(flash().attribute("errorMessage", notNullValue()));
            assertEquals(tag + " Other", reloadFolder(other.getId()).getName());
        }

        @Test
        @DisplayName("move re-parents the subtree on disk and in the database")
        void moveFolder() throws Exception {
            MediaFolder a = createFolder(tag + " A", null);
            MediaFolder b = createFolder(tag + " B", null);
            MediaFolder bChild = createFolder(tag + " B Child", b.getId());
            Media file = upload("deep.png", bChild.getId());

            postForm("/admin/media/folders/" + b.getId() + "/move", "parentId", String.valueOf(a.getId()));

            MediaFolder movedB = reloadFolder(b.getId());
            MediaFolder movedChild = reloadFolder(bChild.getId());
            assertEquals(1, movedB.getDepth());
            assertEquals(2, movedChild.getDepth());
            assertTrue(movedChild.getPath().startsWith(a.getPath()));
            Media movedFile = reload(file.getId());
            assertTrue(movedFile.getStoragePath().startsWith(a.getSlug() + "/" + b.getSlug() + "/"), movedFile.getStoragePath());
            assertTrue(fileOnDisk(movedFile));
        }

        @Test
        @DisplayName("moving a folder into its own subfolder is rejected")
        void moveIntoOwnDescendant() throws Exception {
            MediaFolder root = createFolder(tag + " Root", null);
            MediaFolder child = createFolder(tag + " Child", root.getId());
            mockMvc.perform(post("/admin/media/folders/" + root.getId() + "/move")
                            .param("parentId", String.valueOf(child.getId())).with(owner).with(csrf()))
                    .andExpect(flash().attribute("errorMessage", notNullValue()));
            assertEquals(0, reloadFolder(root.getId()).getDepth());
        }

        @Test
        @DisplayName("delete sends the folder, its subfolders and their files to the trash; restore brings them back")
        void deleteCascadesAndRestores() throws Exception {
            MediaFolder root = createFolder(tag + " Root", null);
            MediaFolder child = createFolder(tag + " Child", root.getId());
            Media inRoot = upload("in-root.png", root.getId());
            Media inChild = upload("in-child.png", child.getId());

            mockMvc.perform(post("/admin/media/folders/" + root.getId() + "/delete").with(owner).with(csrf()))
                    .andExpect(flash().attribute("successMessage", notNullValue()));

            assertEquals(MediaFolder.Status.TRASHED, reloadFolder(root.getId()).getStatus());
            assertEquals(MediaFolder.Status.TRASHED, reloadFolder(child.getId()).getStatus());
            assertEquals(Media.Status.TRASHED, reload(inRoot.getId()).getStatus());
            assertEquals(Media.Status.TRASHED, reload(inChild.getId()).getStatus());
            assertTrue(fileOnDisk(reload(inChild.getId())), "a soft delete never touches the disk");

            postForm("/admin/media/folders/" + root.getId() + "/restore");

            assertEquals(MediaFolder.Status.ACTIVE, reloadFolder(root.getId()).getStatus());
            assertEquals(MediaFolder.Status.ACTIVE, reloadFolder(child.getId()).getStatus());
            assertEquals(Media.Status.ACTIVE, reload(inRoot.getId()).getStatus());
            assertEquals(Media.Status.ACTIVE, reload(inChild.getId()).getStatus());
        }

        @Test
        @DisplayName("delete is refused as a whole when a file deep inside is someone's avatar")
        void deleteBlockedByAvatarDeepInside() throws Exception {
            MediaFolder root = createFolder(tag + " Root", null);
            MediaFolder child = createFolder(tag + " Child", root.getId());
            Media avatar = upload("avatar.png", child.getId());
            User u = userRepository.findById(user.getId()).orElseThrow();
            u.setAvatarUrl(avatar.getUrl());
            userRepository.save(u);

            mockMvc.perform(post("/admin/media/folders/" + root.getId() + "/delete").with(owner).with(csrf()))
                    .andExpect(flash().attribute("errorMessage", notNullValue()));

            assertEquals(MediaFolder.Status.ACTIVE, reloadFolder(root.getId()).getStatus());
            assertEquals(MediaFolder.Status.ACTIVE, reloadFolder(child.getId()).getStatus(), "nothing may be half-deleted");
            assertEquals(Media.Status.ACTIVE, reload(avatar.getId()).getStatus());
        }

        @Test
        @DisplayName("permanently deleting a trashed folder removes its whole subtree and the directories")
        void purgeCascades() throws Exception {
            MediaFolder root = createFolder(tag + " Root", null);
            MediaFolder child = createFolder(tag + " Child", root.getId());
            StoragePath rootDir = layout.directoryOf(root);

            postForm("/admin/media/folders/" + root.getId() + "/delete");
            mockMvc.perform(post("/admin/media/folders/" + root.getId() + "/delete-permanent").with(owner).with(csrf()))
                    .andExpect(flash().attribute("successMessage", notNullValue()));

            assertTrue(folderRepository.findById(root.getId()).isEmpty());
            assertTrue(folderRepository.findById(child.getId()).isEmpty());
            assertFalse(storage.directoryExists(rootDir));
        }
    }

    // ================================================================ ITEMS

    @Nested
    @DisplayName("Item CRUD")
    class Items {

        @Test
        @DisplayName("upload into a folder stores the file at the folder path and stays in that folder")
        void uploadIntoFolder() throws Exception {
            MediaFolder folder = createFolder(tag + " Photos", null);
            mockMvc.perform(multipart("/admin/media")
                            .file(new MockMultipartFile("files", "photo.png", "image/png", png("photo")))
                            .param("folderId", String.valueOf(folder.getId()))
                            .with(owner).with(csrf()))
                    .andExpect(redirectedUrl("/admin/media?folder=" + folder.getId()));
            Media media = mediaRepository.findAll().stream()
                    .filter(m -> "photo.png".equals(m.getOriginalFilename()) && m.getUploadedBy().getId().equals(user.getId()))
                    .findFirst().orElseThrow();
            assertEquals(folder.getSlug() + "/photo.png", media.getStoragePath());
            assertTrue(fileOnDisk(media));
            assertEquals(1, blobRepository.findById(media.getBlob().getId()).orElseThrow().getRefCount());
        }

        @Test
        @DisplayName("same name in the same folder becomes 'name (2)' and never overwrites")
        void sameNameGetsSuffix() throws Exception {
            MediaFolder folder = createFolder(tag + " Dup", null);
            Media first = upload("dup.png", folder.getId());
            Media second = upload("dup.png", folder.getId());
            assertNotEquals(first.getStoragePath(), second.getStoragePath());
            assertTrue(second.getStoragePath().endsWith("dup (2).png"), second.getStoragePath());
            assertTrue(fileOnDisk(reload(first.getId())));
        }

        @Test
        @DisplayName("disallowed type and a PNG disguised as PDF are both rejected")
        void rejectsBadFiles() throws Exception {
            long before = mediaRepository.count();
            mockMvc.perform(multipart("/admin/media")
                            .file(new MockMultipartFile("files", "evil.exe", "application/x-msdownload", new byte[]{'M', 'Z', 0, 0}))
                            .with(owner).with(csrf()))
                    .andExpect(flash().attribute("errorMessage", notNullValue()));
            mockMvc.perform(multipart("/admin/media")
                            .file(new MockMultipartFile("files", "fake.pdf", "application/pdf", png("fake")))
                            .with(owner).with(csrf()))
                    .andExpect(flash().attribute("errorMessage", notNullValue()));
            assertEquals(before, mediaRepository.count());
        }

        @Test
        @DisplayName("read: the public URL serves the bytes and the picker API lists the image")
        void readFile() throws Exception {
            Media media = upload("readable.png", null);
            byte[] body = mockMvc.perform(get(media.getUrl()))
                    .andExpect(status().isOk())
                    .andExpect(content().contentType("image/png"))
                    .andReturn().getResponse().getContentAsByteArray();
            assertEquals((byte) 0x89, body[0]);
            mockMvc.perform(get("/admin/api/media/library").param("kind", "image").with(owner))
                    .andExpect(status().isOk())
                    .andExpect(content().string(org.hamcrest.Matchers.containsString("\"id\":" + media.getId() + ",")));
        }

        @Test
        @DisplayName("update: rename, alt text and folder change move the file but keep the URL")
        void editFile() throws Exception {
            MediaFolder source = createFolder(tag + " Source", null);
            MediaFolder target = createFolder(tag + " Target", null);
            Media media = upload("edit-me.png", source.getId());

            mockMvc.perform(post("/admin/media/" + media.getId() + "/edit")
                            .param("displayName", "Edited Name.png").param("altText", "alt")
                            .param("folderId", String.valueOf(target.getId()))
                            .param("returnFolder", String.valueOf(source.getId()))
                            .with(owner).with(csrf()))
                    .andExpect(redirectedUrl("/admin/media?folder=" + source.getId()));

            Media edited = reload(media.getId());
            assertEquals("Edited Name.png", edited.getOriginalFilename());
            assertEquals("alt", edited.getAltText());
            assertEquals(target.getId(), edited.getFolder().getId());
            assertTrue(edited.getStoragePath().startsWith(target.getSlug() + "/"), edited.getStoragePath());
            assertTrue(fileOnDisk(edited));
            assertEquals(media.getUrl(), edited.getUrl());
        }

        @Test
        @DisplayName("update with a blank name is rejected")
        void editBlankName() throws Exception {
            Media media = upload("keep-name.png", null);
            mockMvc.perform(post("/admin/media/" + media.getId() + "/edit").param("displayName", "")
                            .with(owner).with(csrf()))
                    .andExpect(flash().attribute("errorMessage", notNullValue()));
            assertEquals("keep-name.png", reload(media.getId()).getOriginalFilename());
        }

        @Test
        @DisplayName("bulk move files into a folder where a same-named file already exists")
        void bulkMoveWithClash() throws Exception {
            MediaFolder target = createFolder(tag + " Bulk", null);
            Media existing = upload("clash.png", target.getId());
            Media a = upload("clash.png", null);
            Media b = upload("other.png", null);

            postForm("/admin/media/bulk-move", "ids", String.valueOf(a.getId()), "ids", String.valueOf(b.getId()),
                    "folderId", String.valueOf(target.getId()));

            Media movedA = reload(a.getId());
            assertEquals(target.getId(), movedA.getFolder().getId());
            assertEquals(target.getId(), reload(b.getId()).getFolder().getId());
            assertNotEquals(existing.getStoragePath(), movedA.getStoragePath(), "move must not overwrite");
            assertTrue(fileOnDisk(reload(existing.getId())));
            assertTrue(fileOnDisk(movedA));
        }

        @Test
        @DisplayName("delete -> trash keeps the file; restore brings it back")
        void softDeleteAndRestore() throws Exception {
            Media media = upload("trash-me.png", null);
            mockMvc.perform(post("/admin/media/" + media.getId() + "/delete").with(owner).with(csrf()))
                    .andExpect(flash().attribute("successMessage", notNullValue()));
            assertEquals(Media.Status.TRASHED, reload(media.getId()).getStatus());
            assertTrue(fileOnDisk(reload(media.getId())));
            mockMvc.perform(get(media.getUrl())).andExpect(status().isNotFound());

            postForm("/admin/media/" + media.getId() + "/restore");
            assertEquals(Media.Status.ACTIVE, reload(media.getId()).getStatus());
            mockMvc.perform(get(media.getUrl())).andExpect(status().isOk());
        }

        @Test
        @DisplayName("bulk delete then permanent delete removes rows, files and blobs")
        void bulkDeleteAndPurge() throws Exception {
            Media a = upload("gone-a.png", null);
            Media b = upload("gone-b.png", null);
            String pathA = a.getStoragePath();
            Long blobA = a.getBlob().getId();

            postForm("/admin/media/bulk-delete", "ids", String.valueOf(a.getId()), "ids", String.valueOf(b.getId()));
            assertEquals(Media.Status.TRASHED, reload(a.getId()).getStatus());
            assertEquals(Media.Status.TRASHED, reload(b.getId()).getStatus());

            postForm("/admin/media/" + a.getId() + "/delete-permanent");
            postForm("/admin/media/" + b.getId() + "/delete-permanent");

            assertTrue(mediaRepository.findById(a.getId()).isEmpty());
            assertTrue(mediaRepository.findById(b.getId()).isEmpty());
            assertFalse(storage.fileExists(StoragePath.of(pathA)));
            assertTrue(blobRepository.findById(blobA).isEmpty());
        }

        @Test
        @DisplayName("a file used as a profile photo cannot be deleted")
        void avatarCannotBeDeleted() throws Exception {
            Media avatar = upload("me.png", null);
            User u = userRepository.findById(user.getId()).orElseThrow();
            u.setAvatarUrl(avatar.getUrl());
            userRepository.save(u);

            mockMvc.perform(post("/admin/media/" + avatar.getId() + "/delete").with(owner).with(csrf()))
                    .andExpect(flash().attribute("errorMessage", notNullValue()));
            assertEquals(Media.Status.ACTIVE, reload(avatar.getId()).getStatus());
        }
    }

    // ================================================================ JSON DELETES

    @Nested
    @DisplayName("JSON deletes (explorer removes tiles without a reload)")
    class JsonDeletes {

        @Test
        @DisplayName("single file: 200 with the trashed id")
        void deleteFile() throws Exception {
            Media media = upload("json-one.png", null);
            mockMvc.perform(post("/admin/api/media/files/" + media.getId() + "/delete").with(owner).with(csrf()))
                    .andExpect(status().isOk())
                    .andExpect(jsonPath("$.mediaIds").value(org.hamcrest.Matchers.hasItem(media.getId().intValue())))
                    .andExpect(jsonPath("$.message").value(notNullValue()));
            assertEquals(Media.Status.TRASHED, reload(media.getId()).getStatus());
        }

        @Test
        @DisplayName("single file in use as an avatar: 422 with the reason, nothing trashed")
        void deleteAvatarRefused() throws Exception {
            Media avatar = upload("json-avatar.png", null);
            User u = userRepository.findById(user.getId()).orElseThrow();
            u.setAvatarUrl(avatar.getUrl());
            userRepository.save(u);

            mockMvc.perform(post("/admin/api/media/files/" + avatar.getId() + "/delete").with(owner).with(csrf()))
                    .andExpect(status().isUnprocessableContent())
                    .andExpect(jsonPath("$.message").value(org.hamcrest.Matchers.containsString("profile photo")));
            assertEquals(Media.Status.ACTIVE, reload(avatar.getId()).getStatus());
        }

        @Test
        @DisplayName("bulk: returns only the ids that were really trashed, plus the errors")
        void bulkPartial() throws Exception {
            Media ok = upload("json-ok.png", null);
            Media avatar = upload("json-busy.png", null);
            User u = userRepository.findById(user.getId()).orElseThrow();
            u.setAvatarUrl(avatar.getUrl());
            userRepository.save(u);

            mockMvc.perform(post("/admin/api/media/files/delete")
                            .param("ids", String.valueOf(ok.getId()), String.valueOf(avatar.getId()))
                            .with(owner).with(csrf()))
                    .andExpect(status().isOk())
                    .andExpect(jsonPath("$.mediaIds.length()").value(1))
                    .andExpect(jsonPath("$.mediaIds[0]").value(ok.getId().intValue()))
                    .andExpect(jsonPath("$.errors.length()").value(1));
            assertEquals(Media.Status.TRASHED, reload(ok.getId()).getStatus());
            assertEquals(Media.Status.ACTIVE, reload(avatar.getId()).getStatus());
        }

        @Test
        @DisplayName("bulk with no ids: 422")
        void bulkEmpty() throws Exception {
            mockMvc.perform(post("/admin/api/media/files/delete").with(owner).with(csrf()))
                    .andExpect(status().isUnprocessableContent());
        }

        @Test
        @DisplayName("folder: returns every trashed folder and file id in the subtree")
        void deleteFolderSubtree() throws Exception {
            MediaFolder root = createFolder(tag + " Json Root", null);
            MediaFolder child = createFolder(tag + " Json Child", root.getId());
            Media deep = upload("json-deep.png", child.getId());

            mockMvc.perform(post("/admin/api/media/folders/" + root.getId() + "/delete").with(owner).with(csrf()))
                    .andExpect(status().isOk())
                    .andExpect(jsonPath("$.folderIds").value(org.hamcrest.Matchers.containsInAnyOrder(
                            root.getId().intValue(), child.getId().intValue())))
                    .andExpect(jsonPath("$.mediaIds").value(org.hamcrest.Matchers.hasItem(deep.getId().intValue())));
            assertEquals(MediaFolder.Status.TRASHED, reloadFolder(child.getId()).getStatus());
            assertEquals(Media.Status.TRASHED, reload(deep.getId()).getStatus());
        }

        @Test
        @DisplayName("a view-only user gets 403 on every JSON delete")
        void viewOnlyForbidden() throws Exception {
            Media media = upload("json-guarded.png", null);
            MediaFolder folder = createFolder(tag + " Json Guarded", null);
            RequestPostProcessor viewer = TestAuth.withPermissions(user.getEmail(), "media:view");

            mockMvc.perform(post("/admin/api/media/files/" + media.getId() + "/delete").with(viewer).with(csrf()))
                    .andExpect(status().isForbidden());
            mockMvc.perform(post("/admin/api/media/files/delete").param("ids", String.valueOf(media.getId()))
                            .with(viewer).with(csrf()))
                    .andExpect(status().isForbidden());
            mockMvc.perform(post("/admin/api/media/folders/" + folder.getId() + "/delete").with(viewer).with(csrf()))
                    .andExpect(status().isForbidden());
            assertEquals(Media.Status.ACTIVE, reload(media.getId()).getStatus());
        }
    }

    // ================================================================ TRASH MULTI-SELECT

    @Nested
    @DisplayName("Trash multi-select (JSON)")
    class TrashBulk {

        @Test
        @DisplayName("restore a folder and a file together: removed ids include what came back with the folder")
        void restoreSelected() throws Exception {
            MediaFolder root = createFolder(tag + " Bin Root", null);
            MediaFolder child = createFolder(tag + " Bin Child", root.getId());
            Media deep = upload("bin-deep.png", child.getId());
            Media loose = upload("bin-loose.png", null);
            postForm("/admin/media/folders/" + root.getId() + "/delete");
            postForm("/admin/media/" + loose.getId() + "/delete");

            mockMvc.perform(post("/admin/media/api/trash/restore")
                            .param("folderIds", String.valueOf(root.getId()))
                            .param("mediaIds", String.valueOf(loose.getId()))
                            .with(owner).with(csrf()))
                    .andExpect(status().isOk())
                    .andExpect(jsonPath("$.removedFolderIds").value(org.hamcrest.Matchers.containsInAnyOrder(
                            root.getId().intValue(), child.getId().intValue())))
                    .andExpect(jsonPath("$.removedMediaIds").value(org.hamcrest.Matchers.hasItems(
                            deep.getId().intValue(), loose.getId().intValue())))
                    .andExpect(jsonPath("$.fileCount").isNumber());

            assertEquals(MediaFolder.Status.ACTIVE, reloadFolder(child.getId()).getStatus());
            assertEquals(Media.Status.ACTIVE, reload(deep.getId()).getStatus());
            assertEquals(Media.Status.ACTIVE, reload(loose.getId()).getStatus());
        }

        @Test
        @DisplayName("permanently delete several selected files at once")
        void purgeSelected() throws Exception {
            Media a = upload("bin-a.png", null);
            Media b = upload("bin-b.png", null);
            postForm("/admin/media/bulk-delete", "ids", String.valueOf(a.getId()), "ids", String.valueOf(b.getId()));

            mockMvc.perform(post("/admin/media/api/trash/delete-permanent")
                            .param("mediaIds", String.valueOf(a.getId()), String.valueOf(b.getId()))
                            .with(owner).with(csrf()))
                    .andExpect(status().isOk())
                    .andExpect(jsonPath("$.removedMediaIds").value(org.hamcrest.Matchers.containsInAnyOrder(
                            a.getId().intValue(), b.getId().intValue())));

            assertTrue(mediaRepository.findById(a.getId()).isEmpty());
            assertTrue(mediaRepository.findById(b.getId()).isEmpty());
        }

        @Test
        @DisplayName("permanently delete a selected parent and child folder together")
        void purgeParentAndChild() throws Exception {
            MediaFolder root = createFolder(tag + " Bin Purge Root", null);
            MediaFolder child = createFolder(tag + " Bin Purge Child", root.getId());
            postForm("/admin/media/folders/" + root.getId() + "/delete");

            mockMvc.perform(post("/admin/media/api/trash/delete-permanent")
                            .param("folderIds", String.valueOf(root.getId()), String.valueOf(child.getId()))
                            .with(owner).with(csrf()))
                    .andExpect(status().isOk())
                    .andExpect(jsonPath("$.errors.length()").value(0));

            assertTrue(folderRepository.findById(root.getId()).isEmpty());
            assertTrue(folderRepository.findById(child.getId()).isEmpty());
        }

        @Test
        @DisplayName("an item that is not in the trash is reported, the rest still goes through")
        void partialWithActiveItem() throws Exception {
            Media trashed = upload("bin-trashed.png", null);
            Media active = upload("bin-active.png", null);
            postForm("/admin/media/" + trashed.getId() + "/delete");

            mockMvc.perform(post("/admin/media/api/trash/restore")
                            .param("mediaIds", String.valueOf(trashed.getId()), String.valueOf(active.getId()))
                            .with(owner).with(csrf()))
                    .andExpect(status().isOk())
                    .andExpect(jsonPath("$.removedMediaIds.length()").value(1))
                    .andExpect(jsonPath("$.errors.length()").value(1));
            assertEquals(Media.Status.ACTIVE, reload(trashed.getId()).getStatus());
        }

        @Test
        @DisplayName("nothing selected: 422")
        void nothingSelected() throws Exception {
            mockMvc.perform(post("/admin/media/api/trash/restore").with(owner).with(csrf()))
                    .andExpect(status().isUnprocessableContent());
            mockMvc.perform(post("/admin/media/api/trash/delete-permanent").with(owner).with(csrf()))
                    .andExpect(status().isUnprocessableContent());
        }

        @Test
        @DisplayName("media:edit can restore selected items but not permanently delete them")
        void permissions() throws Exception {
            Media media = upload("bin-guarded.png", null);
            postForm("/admin/media/" + media.getId() + "/delete");
            RequestPostProcessor editor = TestAuth.withPermissions(user.getEmail(), "media:view", "media:edit");

            mockMvc.perform(post("/admin/media/api/trash/delete-permanent").param("mediaIds", String.valueOf(media.getId()))
                            .with(editor).with(csrf()))
                    .andExpect(status().isForbidden());
            assertTrue(mediaRepository.findById(media.getId()).isPresent());

            mockMvc.perform(post("/admin/media/api/trash/restore").param("mediaIds", String.valueOf(media.getId()))
                            .with(editor).with(csrf()))
                    .andExpect(status().isOk());
            assertEquals(Media.Status.ACTIVE, reload(media.getId()).getStatus());
        }

        @Test
        @DisplayName("the shared Trash page lists trashed files with select-all and row checkboxes")
        void pageRendersSelectionUi() throws Exception {
            Media media = upload("bin-render.png", null);
            postForm("/admin/media/" + media.getId() + "/delete");
            // Media moved into /admin/trash?type=media-files; /admin/media/trash only redirects there.
            mockMvc.perform(get("/admin/trash").param("type", "media-files").with(owner))
                    .andExpect(status().isOk())
                    .andExpect(content().string(org.hamcrest.Matchers.containsString("id=\"trash-check-all\"")))
                    .andExpect(content().string(org.hamcrest.Matchers.containsString("class=\"trash-check\" value=\"" + media.getId() + "\"")))
                    .andExpect(content().string(org.hamcrest.Matchers.containsString("bin-render.png")));
        }
    }

    // ================================================================ PERMISSIONS

    @Nested
    @DisplayName("Permissions")
    class Permissions {

        @Test
        @DisplayName("a view-only user cannot create, rename or delete")
        void viewOnlyIsForbidden() throws Exception {
            MediaFolder folder = createFolder(tag + " Guarded", null);
            RequestPostProcessor viewer = TestAuth.withPermissions(user.getEmail(), "media:view");

            mockMvc.perform(post("/admin/media/folders").param("name", tag + " Nope").with(viewer).with(csrf()))
                    .andExpect(status().isForbidden());
            mockMvc.perform(post("/admin/media/folders/" + folder.getId() + "/rename").param("name", "Nope")
                            .with(viewer).with(csrf()))
                    .andExpect(status().isForbidden());
            mockMvc.perform(post("/admin/media/folders/" + folder.getId() + "/delete").with(viewer).with(csrf()))
                    .andExpect(status().isForbidden());
            assertEquals(MediaFolder.Status.ACTIVE, reloadFolder(folder.getId()).getStatus());
        }

        @Test
        @DisplayName("media:delete alone can trash but cannot permanently delete")
        void purgeNeedsHigherPermission() throws Exception {
            Media media = upload("guarded.png", null);
            RequestPostProcessor deleter = TestAuth.withPermissions(user.getEmail(), "media:view", "media:delete");

            mockMvc.perform(post("/admin/media/" + media.getId() + "/delete").with(deleter).with(csrf()))
                    .andExpect(status().is3xxRedirection());
            assertEquals(Media.Status.TRASHED, reload(media.getId()).getStatus());

            mockMvc.perform(post("/admin/media/" + media.getId() + "/delete-permanent").with(deleter).with(csrf()))
                    .andExpect(status().isForbidden());
            assertTrue(mediaRepository.findById(media.getId()).isPresent());
        }
    }
}
