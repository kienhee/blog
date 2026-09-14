package com.kienhee.blog;

import com.kienhee.blog.entity.Media;
import com.kienhee.blog.entity.MediaFolder;
import com.kienhee.blog.repository.MediaFolderRepository;
import com.kienhee.blog.repository.MediaRepository;
import com.kienhee.blog.service.MediaService;
import com.kienhee.blog.service.impl.MediaStorageLayout;
import com.kienhee.blog.storage.FilesystemStorage;
import com.kienhee.blog.support.MediaFolderCleanup;
import com.kienhee.blog.support.TestAuth;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.mock.web.MockMultipartFile;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.setup.MockMvcBuilders;
import org.springframework.web.context.WebApplicationContext;

import javax.imageio.ImageIO;
import java.awt.image.BufferedImage;
import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.util.ArrayList;
import java.util.List;

import static org.junit.jupiter.api.Assertions.*;
import static org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers.springSecurity;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.multipart;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.redirectedUrl;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.status;
import static org.springframework.security.test.web.servlet.request.SecurityMockMvcRequestPostProcessors.csrf;

/**
 * Acting on a file while standing inside a folder must leave the explorer in that folder.
 * Every one of these redirects used to drop the user back at Home.
 */
@SpringBootTest
@DisplayName("Media explorer keeps its folder context")
class MediaFolderContextRedirectTests {

    @Autowired
    private WebApplicationContext context;
    @Autowired
    private MediaFolderRepository mediaFolderRepository;
    @Autowired
    private MediaRepository mediaRepository;
    @Autowired
    private MediaService mediaService;
    @Autowired
    private MediaStorageLayout layout;
    @Autowired
    private FilesystemStorage storage;

    private static final String OWNER_EMAIL = "admin@kienhee.com";

    private MockMvc mockMvc;
    private MediaFolder folder;
    private final List<Long> createdMediaIds = new ArrayList<>();

    @BeforeEach
    void setUp() {
        mockMvc = MockMvcBuilders.webAppContextSetup(context).apply(springSecurity()).build();
        folder = mediaFolderRepository.save(MediaFolder.builder()
                .name("Redirect Test " + System.currentTimeMillis())
                .slug("redirect-test-" + System.currentTimeMillis())
                .path("/")
                .depth(0)
                .build());
        folder.setPath("/" + folder.getId() + "/");
        folder = mediaFolderRepository.save(folder);
    }

    @AfterEach
    void cleanUp() {
        for (Long id : createdMediaIds) {
            try {
                mediaService.deleteMedia(id);
                mediaService.purgeMedia(id);
            } catch (RuntimeException ignored) {
                // best effort
            }
        }
        createdMediaIds.clear();
        mediaRepository.findAll().stream()
                .filter(m -> m.getFolder() != null && m.getFolder().getId().equals(folder.getId()))
                .forEach(m -> mediaRepository.deleteById(m.getId()));
        MediaFolderCleanup.deleteFoldersAndDirectories(
                List.of(folder.getId()), mediaFolderRepository, layout, storage);
    }

    private byte[] png() throws IOException {
        BufferedImage image = new BufferedImage(24, 24, BufferedImage.TYPE_INT_RGB);
        for (int x = 0; x < 24; x++) {
            for (int y = 0; y < 24; y++) {
                image.setRGB(x, y, (x * 7919 + y * 104729 + (int) (System.nanoTime() % 1000)) & 0xFFFFFF);
            }
        }
        ByteArrayOutputStream out = new ByteArrayOutputStream();
        ImageIO.write(image, "png", out);
        return out.toByteArray();
    }

    @Test
    @DisplayName("upload into a folder redirects back into that folder, not Home")
    void uploadStaysInFolder() throws Exception {
        mockMvc.perform(multipart("/admin/media")
                        .file(new MockMultipartFile("files", "in-folder.png", "image/png", png()))
                        .param("folderId", String.valueOf(folder.getId()))
                        .with(TestAuth.owner()).with(csrf()))
                .andExpect(status().is3xxRedirection())
                .andExpect(redirectedUrl("/admin/media?folder=" + folder.getId()));

        // and the file really is in the folder, not unfiled
        Media stored = mediaRepository.findAll().stream()
                .filter(m -> "in-folder.png".equals(m.getOriginalFilename()))
                .findFirst().orElseThrow();
        createdMediaIds.add(stored.getId());
        assertNotNull(stored.getFolder(), "uploaded file must be filed");
        assertEquals(folder.getId(), stored.getFolder().getId());
    }

    @Test
    @DisplayName("upload at Home still redirects to Home")
    void uploadAtHomeStaysHome() throws Exception {
        mockMvc.perform(multipart("/admin/media")
                        .file(new MockMultipartFile("files", "at-home.png", "image/png", png()))
                        .with(TestAuth.owner()).with(csrf()))
                .andExpect(redirectedUrl("/admin/media"));

        mediaRepository.findAll().stream()
                .filter(m -> "at-home.png".equals(m.getOriginalFilename()))
                .findFirst()
                .ifPresent(m -> createdMediaIds.add(m.getId()));
    }

    @Test
    @DisplayName("deleting a file from inside a folder stays in that folder")
    void deleteStaysInFolder() throws Exception {
        Media media = mediaService.uploadMedia(
                new MockMultipartFile("file", "to-delete.png", "image/png", png()),
                OWNER_EMAIL, folder.getId());
        createdMediaIds.add(media.getId());

        mockMvc.perform(post("/admin/media/" + media.getId() + "/delete")
                        .param("returnFolder", String.valueOf(folder.getId()))
                        .with(TestAuth.owner()).with(csrf()))
                .andExpect(redirectedUrl("/admin/media?folder=" + folder.getId()));
    }

    @Test
    @DisplayName("bulk delete from inside a folder stays in that folder")
    void bulkDeleteStaysInFolder() throws Exception {
        Media media = mediaService.uploadMedia(
                new MockMultipartFile("file", "bulk.png", "image/png", png()),
                OWNER_EMAIL, folder.getId());
        createdMediaIds.add(media.getId());

        mockMvc.perform(post("/admin/media/bulk-delete")
                        .param("ids", String.valueOf(media.getId()))
                        .param("returnFolder", String.valueOf(folder.getId()))
                        .with(TestAuth.owner()).with(csrf()))
                .andExpect(redirectedUrl("/admin/media?folder=" + folder.getId()));
    }

    @Test
    @DisplayName("renaming a folder from inside a folder stays put")
    void renameFolderStaysInFolder() throws Exception {
        mockMvc.perform(post("/admin/media/folders/" + folder.getId() + "/rename")
                        .param("name", "Renamed " + System.currentTimeMillis())
                        .param("returnFolder", String.valueOf(folder.getId()))
                        .with(TestAuth.owner()).with(csrf()))
                .andExpect(redirectedUrl("/admin/media?folder=" + folder.getId()));
    }

    @Test
    @DisplayName("a validation error on edit does not lose the folder either")
    void failedEditStaysInFolder() throws Exception {
        Media media = mediaService.uploadMedia(
                new MockMultipartFile("file", "edit-me.png", "image/png", png()),
                OWNER_EMAIL, folder.getId());
        createdMediaIds.add(media.getId());

        mockMvc.perform(post("/admin/media/" + media.getId() + "/edit")
                        .param("displayName", "")   // blank -> validation failure
                        .param("returnFolder", String.valueOf(folder.getId()))
                        .with(TestAuth.owner()).with(csrf()))
                .andExpect(redirectedUrl("/admin/media?folder=" + folder.getId()));
    }
}
