package com.kienhee.blog;

import com.kienhee.blog.entity.Media;
import com.kienhee.blog.entity.MediaFolder;
import com.kienhee.blog.entity.Role;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.MediaFolderRepository;
import com.kienhee.blog.repository.MediaRepository;
import com.kienhee.blog.repository.RoleRepository;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.repository.UserStorageQuotaRepository;
import com.kienhee.blog.service.MediaService;
import com.kienhee.blog.service.impl.MediaStorageLayout;
import com.kienhee.blog.storage.FilesystemStorage;
import com.kienhee.blog.storage.StoragePath;
import com.kienhee.blog.support.MediaFolderCleanup;
import com.kienhee.blog.support.TestAuth;
import com.jayway.jsonpath.JsonPath;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Nested;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.mock.web.MockMultipartFile;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.request.RequestPostProcessor;
import org.springframework.test.web.servlet.setup.MockMvcBuilders;
import org.springframework.web.context.WebApplicationContext;

import javax.imageio.ImageIO;
import java.awt.image.BufferedImage;
import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.util.ArrayList;
import java.util.List;

import static org.hamcrest.Matchers.containsInAnyOrder;
import static org.hamcrest.Matchers.containsString;
import static org.hamcrest.Matchers.everyItem;
import static org.hamcrest.Matchers.hasItem;
import static org.hamcrest.Matchers.is;
import static org.hamcrest.Matchers.not;
import static org.hamcrest.Matchers.notNullValue;
import static org.junit.jupiter.api.Assertions.*;
import static org.springframework.security.test.web.servlet.request.SecurityMockMvcRequestPostProcessors.csrf;
import static org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers.springSecurity;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.multipart;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

/**
 * The JSON API behind the MediaExplorer component (the Media library page and every picker).
 *
 * <p>Repo convention: real database; every row, directory and file created here is removed again.
 */
@SpringBootTest
@DisplayName("Media JSON API (/admin/api/media)")
class MediaApiControllerTests {

    private static final String API = "/admin/api/media";

    @Autowired private WebApplicationContext context;
    @Autowired private MediaService mediaService;
    @Autowired private MediaRepository mediaRepository;
    @Autowired private MediaFolderRepository folderRepository;
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
        mockMvc = MockMvcBuilders.webAppContextSetup(context).apply(springSecurity()).build();
        Role role = roleRepository.findAll().stream().findFirst().orElseThrow();
        tag = "Api" + System.nanoTime();
        user = userRepository.save(User.builder()
                .fullName("API Test User")
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
        folderRepository.findAll().stream()
                .filter(f -> f.getName().startsWith(tag))
                .forEach(f -> folderIds.add(f.getId()));
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

    private Long uploadViaApi(String filename, Long folderId) throws Exception {
        var request = multipart(API + "/files")
                .file(new MockMultipartFile("files", filename, "image/png", png(filename + System.nanoTime())))
                .with(owner).with(csrf());
        if (folderId != null) {
            request.param("folderId", String.valueOf(folderId));
        }
        String json = mockMvc.perform(request)
                .andExpect(status().isOk())
                .andReturn().getResponse().getContentAsString();
        return ((Number) JsonPath.read(json, "$.files[0].id")).longValue();
    }

    private Long createFolderViaApi(String name, Long parentId) throws Exception {
        var request = post(API + "/folders").param("name", name).with(owner).with(csrf());
        if (parentId != null) {
            request.param("parentId", String.valueOf(parentId));
        }
        String json = mockMvc.perform(request)
                .andExpect(status().isOk())
                .andReturn().getResponse().getContentAsString();
        Long id = ((Number) JsonPath.read(json, "$.folder.id")).longValue();
        folderIds.add(id);
        return id;
    }

    private Media reload(Long id) {
        return mediaRepository.findById(id).orElseThrow();
    }

    private MediaFolder reloadFolder(Long id) {
        return folderRepository.findById(id).orElseThrow();
    }

    // ================================================================ LIBRARY

    @Nested
    @DisplayName("GET /library")
    class Library {

        @Test
        @DisplayName("returns folders, files, uploaders, trash summary and the user's permissions")
        void fullLibrary() throws Exception {
            Long folderId = createFolderViaApi(tag + " Lib", null);
            Long fileId = uploadViaApi("lib.png", folderId);

            mockMvc.perform(get(API + "/library").with(owner))
                    .andExpect(status().isOk())
                    .andExpect(jsonPath("$.folders[?(@.id == " + folderId + ")].name").value(hasItem(tag + " Lib")))
                    .andExpect(jsonPath("$.folders[?(@.id == " + folderId + ")].depth").value(hasItem(0)))
                    .andExpect(jsonPath("$.files[?(@.id == " + fileId + ")].folderId").value(hasItem(folderId.intValue())))
                    .andExpect(jsonPath("$.files[?(@.id == " + fileId + ")].kind").value(hasItem("image")))
                    .andExpect(jsonPath("$.files[?(@.id == " + fileId + ")].url").value(hasItem(containsString("/media/" + fileId + "/"))))
                    .andExpect(jsonPath("$.files[?(@.id == " + fileId + ")].uploaderName").value(hasItem("API Test User")))
                    .andExpect(jsonPath("$.uploaders[?(@.id == " + user.getId() + ")].name").value(hasItem("API Test User")))
                    .andExpect(jsonPath("$.trash.fileCount").isNumber())
                    .andExpect(jsonPath("$.permissions.create").value(true))
                    .andExpect(jsonPath("$.permissions.edit").value(true))
                    .andExpect(jsonPath("$.permissions.delete").value(true));
        }

        @Test
        @DisplayName("kind=image returns only images (what the cover-image picker asks for)")
        void onlyImages() throws Exception {
            uploadViaApi("pic.png", null);
            mockMvc.perform(multipart(API + "/files")
                            .file(new MockMultipartFile("files", "doc.txt", "text/plain", ("hello " + tag).getBytes()))
                            .with(owner).with(csrf()))
                    .andExpect(status().isOk());

            mockMvc.perform(get(API + "/library").param("kind", "image").with(owner))
                    .andExpect(status().isOk())
                    .andExpect(jsonPath("$.files[*].kind").value(everyItem(is("image"))))
                    .andExpect(jsonPath("$.files[*].name").value(not(hasItem("doc.txt"))));
        }

        @Test
        @DisplayName("a view-only user can read the library and sees no write permissions")
        void viewOnly() throws Exception {
            mockMvc.perform(get(API + "/library").with(TestAuth.withPermissions(user.getEmail(), "media:view")))
                    .andExpect(status().isOk())
                    .andExpect(jsonPath("$.permissions.create").value(false))
                    .andExpect(jsonPath("$.permissions.edit").value(false))
                    .andExpect(jsonPath("$.permissions.delete").value(false));
        }

        @Test
        @DisplayName("without media:view the API is forbidden")
        void noViewPermission() throws Exception {
            mockMvc.perform(get(API + "/library").with(TestAuth.withPermissions(user.getEmail(), "posts:view")))
                    .andExpect(status().isForbidden());
        }
    }

    // ================================================================ FILES

    @Nested
    @DisplayName("Files")
    class Files {

        @Test
        @DisplayName("upload into a folder returns the new file, stored under that folder on disk")
        void upload() throws Exception {
            Long folderId = createFolderViaApi(tag + " Up", null);
            Long id = uploadViaApi("up.png", folderId);

            Media media = reload(id);
            assertEquals(folderId, media.getFolder().getId());
            assertTrue(storage.fileExists(StoragePath.of(media.getStoragePath())));
        }

        @Test
        @DisplayName("a rejected upload is 422 with the reason, nothing stored")
        void uploadRejected() throws Exception {
            long before = mediaRepository.count();
            mockMvc.perform(multipart(API + "/files")
                            .file(new MockMultipartFile("files", "fake.pdf", "application/pdf", png("fake")))
                            .with(owner).with(csrf()))
                    .andExpect(status().isUnprocessableContent())
                    .andExpect(jsonPath("$.message").value(containsString("fake.pdf")));
            assertEquals(before, mediaRepository.count());
        }

        @Test
        @DisplayName("update renames, sets alt text and moves to another folder, URL unchanged")
        void update() throws Exception {
            Long target = createFolderViaApi(tag + " Target", null);
            Long id = uploadViaApi("edit.png", null);
            String url = reload(id).getUrl();

            mockMvc.perform(post(API + "/files/" + id)
                            .param("displayName", "Edited.png").param("altText", "alt text")
                            .param("folderId", String.valueOf(target))
                            .with(owner).with(csrf()))
                    .andExpect(status().isOk())
                    .andExpect(jsonPath("$.file.name").value("Edited.png"))
                    .andExpect(jsonPath("$.file.altText").value("alt text"))
                    .andExpect(jsonPath("$.file.folderId").value(target.intValue()))
                    .andExpect(jsonPath("$.file.url").value(url));

            Media media = reload(id);
            assertEquals(target, media.getFolder().getId());
            assertTrue(storage.fileExists(StoragePath.of(media.getStoragePath())));
        }

        @Test
        @DisplayName("update with a blank name is 422 (the DTO's own validation)")
        void updateInvalid() throws Exception {
            Long id = uploadViaApi("keep.png", null);
            mockMvc.perform(post(API + "/files/" + id).param("displayName", " ").with(owner).with(csrf()))
                    .andExpect(status().isUnprocessableContent())
                    .andExpect(jsonPath("$.message").value("Display name is required."));
            assertEquals("keep.png", reload(id).getOriginalFilename());
        }

        @Test
        @DisplayName("move several files into a folder, then back to Home")
        void move() throws Exception {
            Long folderId = createFolderViaApi(tag + " Move", null);
            Long a = uploadViaApi("move-a.png", null);
            Long b = uploadViaApi("move-b.png", null);

            mockMvc.perform(post(API + "/files/move")
                            .param("ids", String.valueOf(a), String.valueOf(b))
                            .param("folderId", String.valueOf(folderId))
                            .with(owner).with(csrf()))
                    .andExpect(status().isOk())
                    .andExpect(jsonPath("$.files[*].folderId").value(everyItem(is(folderId.intValue()))));
            assertEquals(folderId, reload(a).getFolder().getId());

            mockMvc.perform(post(API + "/files/move").param("ids", String.valueOf(a)).with(owner).with(csrf()))
                    .andExpect(status().isOk());
            assertNull(reload(a).getFolder());
        }

        @Test
        @DisplayName("delete one file, then several: they move to the trash")
        void delete() throws Exception {
            Long one = uploadViaApi("del-one.png", null);
            Long a = uploadViaApi("del-a.png", null);
            Long b = uploadViaApi("del-b.png", null);

            mockMvc.perform(post(API + "/files/" + one + "/delete").with(owner).with(csrf()))
                    .andExpect(status().isOk())
                    .andExpect(jsonPath("$.mediaIds").value(hasItem(one.intValue())));
            mockMvc.perform(post(API + "/files/delete").param("ids", String.valueOf(a), String.valueOf(b)).with(owner).with(csrf()))
                    .andExpect(status().isOk())
                    .andExpect(jsonPath("$.mediaIds").value(containsInAnyOrder(a.intValue(), b.intValue())));

            assertEquals(Media.Status.TRASHED, reload(one).getStatus());
            assertEquals(Media.Status.TRASHED, reload(a).getStatus());
            assertEquals(Media.Status.TRASHED, reload(b).getStatus());
        }

        @Test
        @DisplayName("deleting a file used as a profile photo is 422 and changes nothing")
        void deleteInUse() throws Exception {
            Long id = uploadViaApi("avatar.png", null);
            User u = userRepository.findById(user.getId()).orElseThrow();
            u.setAvatarUrl(reload(id).getUrl());
            userRepository.save(u);

            mockMvc.perform(post(API + "/files/" + id + "/delete").with(owner).with(csrf()))
                    .andExpect(status().isUnprocessableContent())
                    .andExpect(jsonPath("$.message").value(containsString("profile photo")));
            assertEquals(Media.Status.ACTIVE, reload(id).getStatus());
        }
    }

    // ================================================================ FOLDERS

    @Nested
    @DisplayName("Folders")
    class Folders {

        @Test
        @DisplayName("create at Home and inside a folder; the response carries the whole tree")
        void create() throws Exception {
            Long root = createFolderViaApi(tag + " Root", null);
            mockMvc.perform(post(API + "/folders").param("name", tag + " Child").param("parentId", String.valueOf(root))
                            .with(owner).with(csrf()))
                    .andExpect(status().isOk())
                    .andExpect(jsonPath("$.folder.parentId").value(root.intValue()))
                    .andExpect(jsonPath("$.folder.depth").value(1))
                    .andExpect(jsonPath("$.folders[?(@.id == " + root + ")]").isNotEmpty());
        }

        @Test
        @DisplayName("duplicate name in the same place and a too-short name are 422")
        void createInvalid() throws Exception {
            createFolderViaApi(tag + " Same", null);
            mockMvc.perform(post(API + "/folders").param("name", tag + " Same").with(owner).with(csrf()))
                    .andExpect(status().isUnprocessableContent())
                    .andExpect(jsonPath("$.message").value(notNullValue()));
            mockMvc.perform(post(API + "/folders").param("name", "x").with(owner).with(csrf()))
                    .andExpect(status().isUnprocessableContent());
        }

        @Test
        @DisplayName("rename twice keeps files reachable (storage path and key follow)")
        void renameTwice() throws Exception {
            Long root = createFolderViaApi(tag + " Rn", null);
            Long file = uploadViaApi("inside.png", root);

            mockMvc.perform(post(API + "/folders/" + root + "/rename").param("name", tag + " Rn2").with(owner).with(csrf()))
                    .andExpect(status().isOk())
                    .andExpect(jsonPath("$.folders[?(@.id == " + root + ")].name").value(hasItem(tag + " Rn2")));
            mockMvc.perform(post(API + "/folders/" + root + "/rename").param("name", tag + " Rn3").with(owner).with(csrf()))
                    .andExpect(status().isOk());

            Media media = reload(file);
            assertTrue(media.getStoragePath().startsWith(reloadFolder(root).getSlug() + "/"), media.getStoragePath());
            assertTrue(storage.fileExists(StoragePath.of(media.getStoragePath())));
        }

        @Test
        @DisplayName("move re-parents the subtree; moving into its own child is 422")
        void move() throws Exception {
            Long a = createFolderViaApi(tag + " A", null);
            Long b = createFolderViaApi(tag + " B", null);
            Long bChild = createFolderViaApi(tag + " B Child", b);

            mockMvc.perform(post(API + "/folders/" + b + "/move").param("parentId", String.valueOf(a)).with(owner).with(csrf()))
                    .andExpect(status().isOk())
                    .andExpect(jsonPath("$.folders[?(@.id == " + bChild + ")].depth").value(hasItem(2)));
            mockMvc.perform(post(API + "/folders/" + a + "/move").param("parentId", String.valueOf(bChild)).with(owner).with(csrf()))
                    .andExpect(status().isUnprocessableContent());
            assertEquals(0, reloadFolder(a).getDepth());
        }

        @Test
        @DisplayName("delete sends the subtree and its files to the trash and reports every id")
        void delete() throws Exception {
            Long root = createFolderViaApi(tag + " Del", null);
            Long child = createFolderViaApi(tag + " Del Child", root);
            Long deep = uploadViaApi("deep.png", child);

            mockMvc.perform(post(API + "/folders/" + root + "/delete").with(owner).with(csrf()))
                    .andExpect(status().isOk())
                    .andExpect(jsonPath("$.folderIds").value(containsInAnyOrder(root.intValue(), child.intValue())))
                    .andExpect(jsonPath("$.mediaIds").value(hasItem(deep.intValue())));
            assertEquals(MediaFolder.Status.TRASHED, reloadFolder(child).getStatus());
            assertEquals(Media.Status.TRASHED, reload(deep).getStatus());
        }
    }

    // ================================================================ PERMISSIONS & PAGES

    @Nested
    @DisplayName("Permissions and pages")
    class PermissionsAndPages {

        @Test
        @DisplayName("a view-only user gets 403 on every write")
        void viewOnlyWritesForbidden() throws Exception {
            Long file = uploadViaApi("guarded.png", null);
            Long folder = createFolderViaApi(tag + " Guarded", null);
            RequestPostProcessor viewer = TestAuth.withPermissions(user.getEmail(), "media:view");

            mockMvc.perform(multipart(API + "/files").file(new MockMultipartFile("files", "x.png", "image/png", png("x")))
                    .with(viewer).with(csrf())).andExpect(status().isForbidden());
            mockMvc.perform(post(API + "/files/" + file).param("displayName", "nope").with(viewer).with(csrf()))
                    .andExpect(status().isForbidden());
            mockMvc.perform(post(API + "/files/move").param("ids", String.valueOf(file)).with(viewer).with(csrf()))
                    .andExpect(status().isForbidden());
            mockMvc.perform(post(API + "/files/" + file + "/delete").with(viewer).with(csrf())).andExpect(status().isForbidden());
            mockMvc.perform(post(API + "/files/delete").param("ids", String.valueOf(file)).with(viewer).with(csrf()))
                    .andExpect(status().isForbidden());
            mockMvc.perform(post(API + "/folders").param("name", tag + " Nope").with(viewer).with(csrf()))
                    .andExpect(status().isForbidden());
            mockMvc.perform(post(API + "/folders/" + folder + "/rename").param("name", "Nope").with(viewer).with(csrf()))
                    .andExpect(status().isForbidden());
            mockMvc.perform(post(API + "/folders/" + folder + "/move").with(viewer).with(csrf())).andExpect(status().isForbidden());
            mockMvc.perform(post(API + "/folders/" + folder + "/delete").with(viewer).with(csrf())).andExpect(status().isForbidden());

            assertEquals(Media.Status.ACTIVE, reload(file).getStatus());
            assertEquals(MediaFolder.Status.ACTIVE, reloadFolder(folder).getStatus());
        }

        @Test
        @DisplayName("writes without a CSRF token are rejected")
        void csrfRequired() throws Exception {
            mockMvc.perform(post(API + "/folders").param("name", tag + " NoCsrf").with(owner))
                    .andExpect(status().isForbidden());
        }

        @Test
        @DisplayName("the Media page is just the component mount point, with the folder to open")
        void mediaPageMountPoint() throws Exception {
            Long folder = createFolderViaApi(tag + " Page", null);
            mockMvc.perform(get("/admin/media").param("folder", String.valueOf(folder)).with(owner))
                    .andExpect(status().isOk())
                    .andExpect(content().string(containsString("id=\"media-library\"")))
                    .andExpect(content().string(containsString("data-initial-folder=\"" + folder + "\"")))
                    .andExpect(content().string(containsString("/scripts/media/media-explorer.js")))
                    .andExpect(content().string(containsString("name=\"_csrf\"")));
            mockMvc.perform(get("/admin/media").param("folder", "999999999").with(owner))
                    .andExpect(status().isOk())
                    .andExpect(content().string(not(containsString("data-initial-folder="))));
        }

        @Test
        @DisplayName("the post editor mounts the same component for the cover image picker")
        void postEditorUsesComponent() throws Exception {
            mockMvc.perform(get("/admin/post/new").with(owner))
                    .andExpect(status().isOk())
                    .andExpect(content().string(containsString("id=\"media-picker-explorer\"")))
                    .andExpect(content().string(containsString("/scripts/media/media-explorer.js")));
        }
    }
}
