package com.kienhee.blog.support;

import com.kienhee.blog.config.I18n;
import jakarta.servlet.http.Cookie;
import org.springframework.test.web.servlet.request.MockHttpServletRequestBuilder;
import org.springframework.test.web.servlet.request.MockMvcRequestBuilders;
import org.springframework.test.web.servlet.request.RequestPostProcessor;

/**
 * Language helpers for MockMvc requests.
 *
 * <p>The site answers in Vietnamese by default, so a test that asserts on rendered text must say
 * which language it expects — {@code .with(TestLocale.en())} — instead of depending on whatever
 * the default happens to be. Prefer asserting model attributes or message codes where you can.</p>
 */
public final class TestLocale {

    private TestLocale() {
    }

    /**
     * Default request for a MockMvc that asserts English copy:
     * {@code MockMvcBuilders.webAppContextSetup(wac).defaultRequest(TestLocale.englishByDefault())}.
     *
     * <p>The site answers in Vietnamese by default, so a test that quotes an English message says so
     * once, here, instead of on every {@code perform(...)}. The Vietnamese side is covered by
     * {@code MessageCatalogTests} and {@code LocaleSwitchTests}.</p>
     */
    public static MockHttpServletRequestBuilder englishByDefault() {
        return MockMvcRequestBuilders.get("/").with(en());
    }

    /** Render this request in English. */
    public static RequestPostProcessor en() {
        return of("en");
    }

    /** Render this request in Vietnamese (the default — only needed to be explicit). */
    public static RequestPostProcessor vi() {
        return of("vi");
    }

    public static RequestPostProcessor of(String code) {
        return request -> {
            // Append: another post-processor may already have put a cookie on the request.
            Cookie[] existing = request.getCookies();
            Cookie[] merged = new Cookie[(existing == null ? 0 : existing.length) + 1];
            if (existing != null) {
                System.arraycopy(existing, 0, merged, 0, existing.length);
            }
            merged[merged.length - 1] = new Cookie(I18n.COOKIE, code);
            request.setCookies(merged);
            return request;
        };
    }
}
