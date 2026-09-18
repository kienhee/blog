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
import com.kienhee.blog.service.QuotaService;
import com.kienhee.blog.support.TestAuth;
import com.kienhee.blog.support.TestLocale;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
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
import java.util.Random;

import static org.hamcrest.Matchers.containsString;
import static org.hamcrest.Matchers.is;
import static org.hamcrest.Matchers.not;
import static org.junit.jupiter.api.Assertions.*;
import static org.springframework.security.test.web.servlet.request.SecurityMockMvcRequestPostProcessors.csrf;
import static org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers.springSecurity;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.multipart;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

/**
 * Image editor endpoints: replace an image in place (same id/URL/format) or save the edit as a copy.
 * Every file, row and quota row created here is removed again.
 */
@SpringBootTest
@DisplayName("Media image editing")
class MediaImageEditTests {

    private static final String API = "/admin/api/media";

    @Autowired private WebApplicationContext context;
    @Autowired private MediaService mediaService;
    @Autowired private MediaRepository mediaRepository;
    @Autowired private UserRepository userRepository;
    @Autowired private RoleRepository roleRepository;
    @Autowired private UserStorageQuotaRepository quotaRepository;
    @Autowired private QuotaService quotaService;

    private MockMvc mockMvc;
    private User user;
    private RequestPostProcessor owner;

    @BeforeEach
    void setUp() {
        mockMvc = MockMvcBuilders.webAppContextSetup(context).apply(springSecurity())
                .defaultRequest(TestLocale.englishByDefault()).build();
        Role role = roleRepository.findAll().stream().findFirst().orElseThrow();
        String tag = "Edit" + System.nanoTime();
        user = userRepository.save(User.builder()
                .fullName("Image Edit User").email(tag.toLowerCase() + "@test.com")
                .password("{noop}irrelevant").role(role).build());
        owner = TestAuth.owner(user.getEmail());
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

    /** Noisy pixels, so the encoded size really grows with the dimensions. */
    private static byte[] image(int w, int h, String format, long seed) throws IOException {
        BufferedImage img = new BufferedImage(w, h, BufferedImage.TYPE_INT_RGB);
        Random random = new Random(seed);
        for (int x = 0; x < w; x++) {
            for (int y = 0; y < h; y++) {
                img.setRGB(x, y, random.nextInt(0xFFFFFF));
            }
        }
        ByteArrayOutputStream out = new ByteArrayOutputStream();
        ImageIO.write(img, format, out);
        return out.toByteArray();
    }

    private Long upload(String name, String type, byte[] bytes) throws Exception {
        String json = mockMvc.perform(multipart(API + "/files")
                        .file(new MockMultipartFile("files", name, type, bytes)).with(owner).with(csrf()))
                .andExpect(status().isOk())
                .andReturn().getResponse().getContentAsString();
        return ((Number) JsonPath.read(json, "$.files[0].id")).longValue();
    }

    private static MockMultipartFile edited(String name, String type, byte[] bytes) {
        return new MockMultipartFile("file", name, type, bytes);
    }

    @Test
    @DisplayName("replace keeps id, URL and name; updates bytes, size, dimensions, hash and thumbnail")
    void replaceInPlace() throws Exception {
        Long id = upload("photo.png", "image/png", image(400, 300, "png", 1));
        Media before = mediaRepository.findById(id).orElseThrow();
        assertNotNull(before.getThumbnailUrl(), "a 400px image gets a separate thumbnail");

        byte[] newBytes = image(120, 90, "png", 2);
        mockMvc.perform(multipart(API + "/files/" + id + "/image").file(edited("photo.png", "image/png", newBytes))
                        .with(owner).with(csrf()))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.message").value("Image updated."))
                .andExpect(jsonPath("$.file.id").value(is(id.intValue())))
                .andExpect(jsonPath("$.file.url").value(before.getUrl()))
                .andExpect(jsonPath("$.file.width").value(120))
                .andExpect(jsonPath("$.file.height").value(90))
                .andExpect(jsonPath("$.file.version").value(not(before.getSha256().substring(0, 12))));

        Media after = mediaRepository.findById(id).orElseThrow();
        assertEquals(before.getUrl(), after.getUrl());
        assertEquals(before.getStoredFilename(), after.getStoredFilename());
        assertEquals(newBytes.length, after.getSizeBytes());
        assertNotEquals(before.getSha256(), after.getSha256());
        assertNull(after.getThumbnailUrl(), "a 120px result needs no separate thumbnail");

        byte[] served = mockMvc.perform(get(after.getUrl()).with(owner))
                .andExpect(status().isOk()).andReturn().getResponse().getContentAsByteArray();
        assertArrayEquals(newBytes, served, "the public URL now serves the edited bytes");
        byte[] thumb = mockMvc.perform(get("/media/" + id + "/thumb").with(owner))
                .andExpect(status().isOk()).andReturn().getResponse().getContentAsByteArray();
        assertArrayEquals(newBytes, thumb, "the stale thumbnail is gone");
    }

    @Test
    @DisplayName("the owner's quota follows the new size")
    void quotaFollowsNewSize() throws Exception {
        Long id = upload("big.png", "image/png", image(300, 300, "png", 3));
        byte[] small = image(20, 20, "png", 4);
        mockMvc.perform(multipart(API + "/files/" + id + "/image").file(edited("big.png", "image/png", small))
                        .with(owner).with(csrf()))
                .andExpect(status().isOk());
        assertEquals(small.length, quotaService.getUsage(user.getId()).usedBytes());

        byte[] bigger = image(360, 360, "png", 5);
        mockMvc.perform(multipart(API + "/files/" + id + "/image").file(edited("big.png", "image/png", bigger))
                        .with(owner).with(csrf()))
                .andExpect(status().isOk());
        assertEquals(bigger.length, quotaService.getUsage(user.getId()).usedBytes());
    }

    @Test
    @DisplayName("replace refuses a different format, fake image content and non-images")
    void replaceRejections() throws Exception {
        Long id = upload("keep.png", "image/png", image(40, 40, "png", 6));
        String sha = mediaRepository.findById(id).orElseThrow().getSha256();

        mockMvc.perform(multipart(API + "/files/" + id + "/image").file(edited("keep.jpg", "image/jpeg", image(40, 40, "jpg", 7)))
                        .with(owner).with(csrf()))
                .andExpect(status().isUnprocessableEntity())
                .andExpect(jsonPath("$.message").value(containsString("Save it as a copy")));

        mockMvc.perform(multipart(API + "/files/" + id + "/image").file(edited("keep.png", "image/png", "not an image".getBytes()))
                        .with(owner).with(csrf()))
                .andExpect(status().isUnprocessableEntity());
        assertEquals(sha, mediaRepository.findById(id).orElseThrow().getSha256(), "nothing changed");

        Long doc = upload("notes.txt", "text/plain", "hello".getBytes());
        mockMvc.perform(multipart(API + "/files/" + doc + "/image").file(edited("notes.png", "image/png", image(10, 10, "png", 8)))
                        .with(owner).with(csrf()))
                .andExpect(status().isUnprocessableEntity())
                .andExpect(jsonPath("$.message").value(containsString("Only JPG, PNG, WEBP and GIF")));
    }

    @Test
    @DisplayName("save as copy creates a new file in the same folder and leaves the original alone")
    void saveAsCopy() throws Exception {
        Long id = upload("cover.png", "image/png", image(80, 60, "png", 9));
        Media original = mediaRepository.findById(id).orElseThrow();

        String json = mockMvc.perform(multipart(API + "/files/" + id + "/image-copy")
                        .file(edited("cover-edited.jpg", "image/jpeg", image(64, 48, "jpg", 10)))
                        .with(owner).with(csrf()))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.message").value("Saved as a new image."))
                .andExpect(jsonPath("$.file.name").value("cover-edited.jpg"))
                .andExpect(jsonPath("$.file.contentType").value("image/jpeg"))
                .andExpect(jsonPath("$.file.width").value(64))
                .andReturn().getResponse().getContentAsString();

        long copyId = ((Number) JsonPath.read(json, "$.file.id")).longValue();
        assertNotEquals(id.longValue(), copyId);
        Media copy = mediaRepository.findById(copyId).orElseThrow();
        assertNull(copy.getFolder(), "same folder as the source (Home)");
        assertEquals(original.getSha256(), mediaRepository.findById(id).orElseThrow().getSha256());
    }

    @Test
    @DisplayName("WEBP: upload records the size, replace works and PNG converts to WEBP as a copy")
    void webpConversion() throws Exception {
        // The JDK cannot decode WebP; the size must come from the header (WebpDimensions).
        Long id = upload("hero.webp", "image/webp", WebpDimensionsTests.vp8l(1200, 900));
        Media uploaded = mediaRepository.findById(id).orElseThrow();
        assertEquals(1200, uploaded.getWidth());
        assertEquals(900, uploaded.getHeight());

        mockMvc.perform(multipart(API + "/files/" + id + "/image")
                        .file(edited("hero.webp", "image/webp", WebpDimensionsTests.vp8x(960, 540)))
                        .with(owner).with(csrf()))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.file.width").value(960))
                .andExpect(jsonPath("$.file.height").value(540))
                .andExpect(jsonPath("$.file.url").value(uploaded.getUrl()));

        Long png = upload("shot.png", "image/png", image(64, 36, "png", 15));
        mockMvc.perform(multipart(API + "/files/" + png + "/image-copy")
                        .file(edited("shot-edited.webp", "image/webp", WebpDimensionsTests.vp8(64, 36)))
                        .with(owner).with(csrf()))
                .andExpect(status().isOk())
                .andExpect(jsonPath("$.file.name").value("shot-edited.webp"))
                .andExpect(jsonPath("$.file.contentType").value("image/webp"))
                .andExpect(jsonPath("$.file.width").value(64));

        mockMvc.perform(multipart(API + "/files/" + id + "/image")
                        .file(edited("hero.webp", "image/webp", "RIFF0000WEBPjunkjunkjunkjunkjunk".getBytes()))
                        .with(owner).with(csrf()))
                .andExpect(status().isUnprocessableEntity())
                .andExpect(jsonPath("$.message").value("Could not read the edited image."));
    }

    @Test
    @DisplayName("replace needs media:edit and copy needs media:create")
    void permissions() throws Exception {
        Long id = upload("perm.png", "image/png", image(30, 30, "png", 11));
        byte[] bytes = image(20, 20, "png", 12);

        mockMvc.perform(multipart(API + "/files/" + id + "/image").file(edited("perm.png", "image/png", bytes))
                        .with(TestAuth.withPermissions(user.getEmail(), "media:view", "media:create")).with(csrf()))
                .andExpect(status().isForbidden());
        mockMvc.perform(multipart(API + "/files/" + id + "/image-copy").file(edited("perm-edited.png", "image/png", bytes))
                        .with(TestAuth.withPermissions(user.getEmail(), "media:view", "media:edit")).with(csrf()))
                .andExpect(status().isForbidden());
        mockMvc.perform(multipart(API + "/files/" + id + "/image").file(edited("perm.png", "image/png", bytes)).with(owner))
                .andExpect(status().isForbidden());
    }

    @Test
    @DisplayName("files carry an ETag that changes when the image is edited")
    void etagRevalidation() throws Exception {
        Long id = upload("etag.png", "image/png", image(50, 50, "png", 13));
        String url = mediaRepository.findById(id).orElseThrow().getUrl();

        String etag = mockMvc.perform(get(url))
                .andExpect(status().isOk())
                .andExpect(header().string("Cache-Control", containsString("no-cache")))
                .andReturn().getResponse().getHeader("ETag");
        assertNotNull(etag);
        mockMvc.perform(get(url).header("If-None-Match", etag)).andExpect(status().isNotModified());

        mockMvc.perform(multipart(API + "/files/" + id + "/image").file(edited("etag.png", "image/png", image(50, 50, "png", 14)))
                        .with(owner).with(csrf()))
                .andExpect(status().isOk());

        String newEtag = mockMvc.perform(get(url).header("If-None-Match", etag))
                .andExpect(status().isOk())
                .andReturn().getResponse().getHeader("ETag");
        assertNotEquals(etag, newEtag);
    }
}
