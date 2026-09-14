package com.kienhee.blog;

import com.kienhee.blog.entity.SiteSetting;
import com.kienhee.blog.repository.SiteSettingRepository;
import com.kienhee.blog.service.SettingService;
import com.kienhee.blog.support.TestAuth;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Nested;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.setup.MockMvcBuilders;
import org.springframework.web.context.WebApplicationContext;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import static org.hamcrest.Matchers.containsString;
import static org.hamcrest.Matchers.hasKey;
import static org.hamcrest.Matchers.not;
import static org.hamcrest.Matchers.startsWith;
import static org.junit.jupiter.api.Assertions.*;
import static org.springframework.security.test.web.servlet.request.SecurityMockMvcRequestPostProcessors.csrf;
import static org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers.springSecurity;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

/**
 * Settings module: generic key/value storage behind /admin/settings.
 *
 * <p>Repo convention: real database; every value changed here is restored and every key created
 * here is deleted again.
 */
@SpringBootTest
@DisplayName("Settings (key/value)")
class SettingCrudTests {

    @Autowired private WebApplicationContext context;
    @Autowired private SettingService settingService;
    @Autowired private SiteSettingRepository repository;

    private MockMvc mockMvc;
    private Map<String, String> originals;
    private final List<String> createdKeys = new ArrayList<>();

    @BeforeEach
    void setUp() {
        mockMvc = MockMvcBuilders.webAppContextSetup(context).apply(springSecurity()).build();
        originals = new HashMap<>(settingService.getAll());
    }

    @AfterEach
    void restore() {
        createdKeys.forEach(repository::deleteById);
        repository.findAll().forEach(setting -> {
            if (originals.containsKey(setting.getKey()) && !originals.get(setting.getKey()).equals(setting.getValue())) {
                setting.setValue(originals.get(setting.getKey()));
                repository.save(setting);
            }
        });
    }

    private String newKey(String prefix) {
        String key = prefix + "." + System.nanoTime();
        createdKeys.add(key);
        return key;
    }

    @Nested
    @DisplayName("Page")
    class Page {

        @Test
        @DisplayName("renders the seeded settings into fields named settings[<key>]")
        void rendersSeededValues() throws Exception {
            mockMvc.perform(get("/admin/settings").with(TestAuth.owner()))
                    .andExpect(status().isOk())
                    .andExpect(view().name("admin/setting/settings"))
                    .andExpect(model().attribute("settings", hasKey("site.title")))
                    .andExpect(content().string(containsString("name=\"settings[site.title]\"")))
                    .andExpect(content().string(containsString("name=\"settings[blog.posts_per_page]\"")))
                    .andExpect(content().string(containsString("Save settings")));
        }

        @Test
        @DisplayName("a view-only user sees read-only fields and no save button")
        void viewOnly() throws Exception {
            mockMvc.perform(get("/admin/settings").with(TestAuth.withPermissions("viewer@test.com", "settings:view")))
                    .andExpect(status().isOk())
                    .andExpect(content().string(containsString("readonly")))
                    .andExpect(content().string(not(containsString("Save settings"))));
        }

        @Test
        @DisplayName("without settings:view the page is forbidden")
        void noPermission() throws Exception {
            mockMvc.perform(get("/admin/settings").with(TestAuth.withPermissions("nobody@test.com", "posts:view")))
                    .andExpect(status().isForbidden());
        }
    }

    @Nested
    @DisplayName("Save")
    class Save {

        @Test
        @DisplayName("saves existing keys and creates new ones just by posting settings[<key>]")
        void savesAnyKey() throws Exception {
            String brandNew = newKey("test.feature");

            mockMvc.perform(post("/admin/settings")
                            .param("settings[site.title]", "  Kienhee Test  ")
                            .param("settings[" + brandNew + "]", "on")
                            .param("unrelated", "ignored")
                            .with(TestAuth.owner()).with(csrf()))
                    .andExpect(status().is3xxRedirection())
                    .andExpect(redirectedUrl("/admin/settings"))
                    .andExpect(flash().attribute("successMessage", "Settings saved."));

            assertEquals("Kienhee Test", settingService.get("site.title").orElseThrow(), "values are trimmed");
            assertEquals("on", settingService.get(brandNew).orElseThrow(), "a new key needs no schema change");
            assertFalse(repository.existsById("_csrf"), "non settings[...] parameters are never stored");
            assertFalse(repository.existsById("unrelated"));
        }

        @Test
        @DisplayName("keys that are not posted keep their value")
        void untouchedKeysStay() throws Exception {
            String domain = settingService.get("site.domain").orElseThrow();
            mockMvc.perform(post("/admin/settings").param("settings[site.title]", "Only title")
                            .with(TestAuth.owner()).with(csrf()))
                    .andExpect(status().is3xxRedirection());
            assertEquals(domain, settingService.get("site.domain").orElseThrow());
        }

        @Test
        @DisplayName("an invalid key rejects the whole form: nothing is saved")
        void invalidKeySavesNothing() throws Exception {
            String title = settingService.get("site.title").orElseThrow();
            mockMvc.perform(post("/admin/settings")
                            .param("settings[site.title]", "Should not be saved")
                            .param("settings[Bad Key!]", "x")
                            .with(TestAuth.owner()).with(csrf()))
                    .andExpect(status().is3xxRedirection())
                    .andExpect(flash().attribute("errorMessage", startsWith("Invalid setting key")));
            assertEquals(title, settingService.get("site.title").orElseThrow());
            assertFalse(repository.existsById("Bad Key!"));
        }

        @Test
        @DisplayName("when a name is posted twice the last value wins (hidden false + checkbox pattern)")
        void lastValueWins() throws Exception {
            String flag = newKey("test.flag");
            mockMvc.perform(post("/admin/settings")
                            .param("settings[" + flag + "]", "false", "true")
                            .with(TestAuth.owner()).with(csrf()))
                    .andExpect(status().is3xxRedirection());
            assertEquals("true", settingService.get(flag).orElseThrow());
        }

        @Test
        @DisplayName("posting no settings fields is reported, not silently ignored")
        void nothingToSave() throws Exception {
            mockMvc.perform(post("/admin/settings").param("unrelated", "x").with(TestAuth.owner()).with(csrf()))
                    .andExpect(flash().attribute("errorMessage", "Nothing to save."));
        }

        @Test
        @DisplayName("posts per page reaches every admin DataTable page through the layout meta tag")
        void postsPerPageAppliedToDataTables() throws Exception {
            mockMvc.perform(post("/admin/settings").param("settings[blog.posts_per_page]", "25")
                            .with(TestAuth.owner()).with(csrf()))
                    .andExpect(status().is3xxRedirection());
            for (String page : List.of("/admin/users", "/admin/categories", "/admin/hashtags", "/admin/posts")) {
                mockMvc.perform(get(page).with(TestAuth.owner()))
                        .andExpect(status().isOk())
                        .andExpect(content().string(containsString("name=\"kh-posts-per-page\" content=\"25\"")))
                        .andExpect(content().string(containsString("/scripts/lib/lightbox.min.js")));
            }
        }

        @Test
        @DisplayName("settings:view alone cannot save")
        void viewerCannotSave() throws Exception {
            String title = settingService.get("site.title").orElseThrow();
            mockMvc.perform(post("/admin/settings").param("settings[site.title]", "Hacked")
                            .with(TestAuth.withPermissions("viewer@test.com", "settings:view")).with(csrf()))
                    .andExpect(status().isForbidden());
            assertEquals(title, settingService.get("site.title").orElseThrow());
        }

        @Test
        @DisplayName("a POST without a CSRF token is rejected")
        void csrfRequired() throws Exception {
            mockMvc.perform(post("/admin/settings").param("settings[site.title]", "No token").with(TestAuth.owner()))
                    .andExpect(status().isForbidden());
        }
    }

    @Nested
    @DisplayName("Service reads")
    class Reads {

        @Test
        @DisplayName("get / getInt fall back to the default for missing, blank or non-numeric values")
        void defaults() {
            String blank = newKey("test.blank");
            String text = newKey("test.text");
            repository.save(SiteSetting.builder().key(blank).value("   ").build());
            repository.save(SiteSetting.builder().key(text).value("abc").build());

            assertEquals("fallback", settingService.get("test.missing." + System.nanoTime(), "fallback"));
            assertEquals("fallback", settingService.get(blank, "fallback"));
            assertEquals(7, settingService.getInt(text, 7));
            String number = newKey("test.number");
            repository.save(SiteSetting.builder().key(number).value(" 42 ").build());
            assertEquals(42, settingService.getInt(number, 7));
        }
    }
}
