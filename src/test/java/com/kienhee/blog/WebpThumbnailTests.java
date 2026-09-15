package com.kienhee.blog;

import com.jayway.jsonpath.JsonPath;
import com.kienhee.blog.entity.Media;
import com.kienhee.blog.entity.Role;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.MediaRepository;
import com.kienhee.blog.repository.RoleRepository;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.repository.UserStorageQuotaRepository;
import com.kienhee.blog.service.MediaService;
import com.kienhee.blog.support.TestAuth;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.core.io.ClassPathResource;
import org.springframework.mock.web.MockMultipartFile;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.setup.MockMvcBuilders;
import org.springframework.web.context.WebApplicationContext;

import javax.imageio.ImageIO;
import java.awt.image.BufferedImage;
import java.io.ByteArrayInputStream;

import static org.junit.jupiter.api.Assertions.*;
import static org.springframework.security.test.web.servlet.request.SecurityMockMvcRequestPostProcessors.csrf;
import static org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers.springSecurity;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.multipart;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.status;

/**
 * WebP is decoded through the TwelveMonkeys ImageIO plugin, so WebP uploads get real dimensions and a
 * thumbnail like JPG/PNG. The fixture is a 640x400 WebP encoded by Chrome's canvas (VP8X).
 */
@SpringBootTest
@DisplayName("WebP decoding and thumbnails")
class WebpThumbnailTests {

    @Autowired private WebApplicationContext context;
    @Autowired private MediaService mediaService;
    @Autowired private MediaRepository mediaRepository;
    @Autowired private UserRepository userRepository;
    @Autowired private RoleRepository roleRepository;
    @Autowired private UserStorageQuotaRepository quotaRepository;

    private MockMvc mockMvc;
    private User user;

    @BeforeEach
    void setUp() {
        mockMvc = MockMvcBuilders.webAppContextSetup(context).apply(springSecurity()).build();
        Role role = roleRepository.findBySlug("admin").orElseThrow();
        user = userRepository.save(User.builder().fullName("Webp Tester").email("webp" + System.nanoTime() + "@test.com")
                .password("{noop}irrelevant").role(role).build());
    }

    @AfterEach
    void cleanUp() {
        for (Media media : mediaRepository.findAll()) {
            if (media.getUploadedBy() != null && media.getUploadedBy().getId().equals(user.getId())) {
                try { mediaService.deleteMedia(media.getId()); } catch (RuntimeException ignored) { /* already trashed */ }
                try { mediaService.purgeMedia(media.getId()); } catch (RuntimeException ignored) { /* already gone */ }
            }
        }
        quotaRepository.findByUserId(user.getId()).ifPresent(quotaRepository::delete);
        userRepository.deleteById(user.getId());
    }

    private static byte[] fixture() throws Exception {
        return new ClassPathResource("media/sample-640x400.webp").getInputStream().readAllBytes();
    }

    @Test
    @DisplayName("ImageIO can read WebP")
    void imageIoReadsWebp() throws Exception {
        assertTrue(ImageIO.getImageReadersByMIMEType("image/webp").hasNext(), "a WebP ImageReader is registered");
        BufferedImage image = ImageIO.read(new ByteArrayInputStream(fixture()));
        assertNotNull(image);
        assertEquals(640, image.getWidth());
        assertEquals(400, image.getHeight());
    }

    @Test
    @DisplayName("a WebP upload gets its dimensions and a generated thumbnail")
    void uploadGetsThumbnail() throws Exception {
        String json = mockMvc.perform(multipart("/admin/api/media/files")
                        .file(new MockMultipartFile("files", "hero.webp", "image/webp", fixture()))
                        .with(TestAuth.owner(user.getEmail())).with(csrf()))
                .andExpect(status().isOk())
                .andReturn().getResponse().getContentAsString();
        long id = ((Number) JsonPath.read(json, "$.files[0].id")).longValue();

        Media media = mediaRepository.findById(id).orElseThrow();
        assertEquals(640, media.getWidth());
        assertEquals(400, media.getHeight());
        assertNotNull(media.getThumbnailUrl(), "images over 320px get a separate thumbnail");

        byte[] thumb = mockMvc.perform(get("/media/" + id + "/thumb").with(TestAuth.owner(user.getEmail())))
                .andExpect(status().isOk()).andReturn().getResponse().getContentAsByteArray();
        BufferedImage decoded = ImageIO.read(new ByteArrayInputStream(thumb));
        assertNotNull(decoded);
        assertTrue(decoded.getWidth() <= 320 && decoded.getHeight() <= 320);
    }
}
