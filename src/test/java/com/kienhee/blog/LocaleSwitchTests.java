package com.kienhee.blog;

import com.kienhee.blog.config.I18n;
import com.kienhee.blog.support.TestLocale;
import jakarta.servlet.http.Cookie;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Nested;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.MvcResult;
import org.springframework.test.web.servlet.setup.MockMvcBuilders;
import org.springframework.web.context.WebApplicationContext;

import java.util.Locale;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertNotNull;
import static org.junit.jupiter.api.Assertions.assertNull;
import static org.springframework.security.test.web.servlet.request.SecurityMockMvcRequestPostProcessors.csrf;
import static org.springframework.security.test.web.servlet.setup.SecurityMockMvcConfigurers.springSecurity;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.model;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.redirectedUrl;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.status;

/**
 * The language switcher: {@code POST /lang} writes the cookie, only accepts the two languages we
 * speak, and can never be used as an open redirect.
 */
@SpringBootTest
@DisplayName("Language switcher")
class LocaleSwitchTests {

    @Autowired private WebApplicationContext context;

    private MockMvc mockMvc;

    @BeforeEach
    void setUp() {
        // No English default here on purpose: this class checks what the site does without a cookie.
        mockMvc = MockMvcBuilders.webAppContextSetup(context).apply(springSecurity()).build();
    }

    private static Cookie langCookie(MvcResult result) {
        return result.getResponse().getCookie(I18n.COOKIE);
    }

    @Nested
    @DisplayName("POST /lang")
    class Switching {

        @Test
        @DisplayName("sets the cookie and comes back to the page the visitor was on")
        void setsCookie() throws Exception {
            MvcResult result = mockMvc.perform(post("/lang")
                            .param("code", "en")
                            .param("redirect", "/news?page=2")
                            .with(csrf()))
                    .andExpect(status().is3xxRedirection())
                    .andExpect(redirectedUrl("/news?page=2"))
                    .andReturn();

            Cookie cookie = langCookie(result);
            assertNotNull(cookie, "the language cookie must be written");
            assertEquals("en", cookie.getValue());
            assertEquals("/", cookie.getPath());
        }

        @Test
        @DisplayName("an unsupported code changes nothing")
        void unsupportedCodeIgnored() throws Exception {
            MvcResult result = mockMvc.perform(post("/lang")
                            .param("code", "fr")
                            .param("redirect", "/")
                            .with(csrf()))
                    .andExpect(status().is3xxRedirection())
                    .andExpect(redirectedUrl("/"))
                    .andReturn();

            assertNull(langCookie(result), "a bogus code must not write a cookie");
        }

        @Test
        @DisplayName("a missing code changes nothing")
        void missingCodeIgnored() throws Exception {
            MvcResult result = mockMvc.perform(post("/lang").param("redirect", "/").with(csrf()))
                    .andExpect(status().is3xxRedirection())
                    .andReturn();

            assertNull(langCookie(result));
        }

        @Test
        @DisplayName("requires CSRF like every other write")
        void requiresCsrf() throws Exception {
            mockMvc.perform(post("/lang").param("code", "en").param("redirect", "/"))
                    .andExpect(status().isForbidden());
        }
    }

    @Nested
    @DisplayName("redirect target")
    class RedirectGuard {

        @Test
        @DisplayName("an off-site target is refused and the visitor goes home")
        void offSiteRefused() throws Exception {
            for (String target : new String[]{
                    "//evil.com", "http://evil.com", "https://evil.com/x",
                    "\\\\evil.com", "/news\\x", "/news\nLocation:%20/evil"}) {
                mockMvc.perform(post("/lang").param("code", "en").param("redirect", target).with(csrf()))
                        .andExpect(status().is3xxRedirection())
                        .andExpect(redirectedUrl("/"));
            }
        }

        @Test
        @DisplayName("a blank or absent target goes home")
        void blankGoesHome() throws Exception {
            mockMvc.perform(post("/lang").param("code", "vi").param("redirect", "  ").with(csrf()))
                    .andExpect(redirectedUrl("/"));
            mockMvc.perform(post("/lang").param("code", "vi").with(csrf()))
                    .andExpect(redirectedUrl("/"));
        }

        @Test
        @DisplayName("an admin path is allowed back")
        void adminPathAllowed() throws Exception {
            mockMvc.perform(post("/lang").param("code", "en").param("redirect", "/admin/posts").with(csrf()))
                    .andExpect(redirectedUrl("/admin/posts"));
        }
    }

    @Nested
    @DisplayName("rendering")
    class Rendering {

        @Test
        @DisplayName("without a cookie the site answers in Vietnamese")
        void defaultIsVietnamese() throws Exception {
            mockMvc.perform(get("/"))
                    .andExpect(status().isOk())
                    .andExpect(model().attribute("currentLang", "vi"));
            assertEquals("vi", I18n.DEFAULT.getLanguage());
        }

        @Test
        @DisplayName("the cookie selects the language of the page")
        void cookieSelectsLanguage() throws Exception {
            mockMvc.perform(get("/").with(TestLocale.en()))
                    .andExpect(status().isOk())
                    .andExpect(model().attribute("currentLang", "en"));
        }

        @Test
        @DisplayName("a hand-edited cookie falls back to Vietnamese instead of failing")
        void bogusCookieFallsBack() throws Exception {
            mockMvc.perform(get("/").with(TestLocale.of("zz-ZZ-nonsense")))
                    .andExpect(status().isOk())
                    .andExpect(model().attribute("currentLang", "vi"));
        }

        @Test
        @DisplayName("currentUriFull keeps the query string so switching stays on the same page")
        void currentUriFullKeepsQuery() throws Exception {
            // The query string must be in the URI: MockMvc's .param() does not populate it.
            mockMvc.perform(get("/search?q=ai"))
                    .andExpect(status().isOk())
                    .andExpect(model().attribute("currentUriFull", "/search?q=ai"))
                    .andExpect(model().attribute("currentUri", "/search"));
        }
    }

    @Nested
    @DisplayName("I18n")
    class Codes {

        @Test
        @DisplayName("only vi and en parse")
        void parsing() {
            assertEquals(I18n.VI, I18n.parse("vi"));
            assertEquals(I18n.EN, I18n.parse("EN"));
            assertNull(I18n.parse("fr"));
            assertNull(I18n.parse(""));
            assertNull(I18n.parse(null));
            assertEquals(I18n.DEFAULT, I18n.parseOrDefault("nonsense"));
            assertEquals("vi", I18n.codeOf(Locale.FRENCH));
            assertEquals("en", I18n.codeOf(Locale.US));
        }
    }
}
