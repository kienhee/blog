package com.kienhee.blog.config;

import org.springframework.beans.factory.annotation.Value;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.web.servlet.LocaleResolver;
import org.springframework.web.servlet.i18n.CookieLocaleResolver;

import java.time.Duration;
import java.util.Locale;

/**
 * Language selection: a cookie plus the switcher in the header / topbar ({@code POST /lang},
 * see {@code LocaleController}).
 *
 * <p>There is deliberately <b>no</b> {@code LocaleChangeInterceptor}: it would leave {@code ?lang=}
 * on every URL the reader copies or shares, and on the canonical URL of an article. And
 * {@code Accept-Language} is deliberately ignored — the site answers in Vietnamese until the
 * visitor asks for English, so the same URL renders the same way for crawlers.</p>
 */
@Configuration
public class I18nConfig {

    /**
     * Cookie-backed resolver that accepts <b>only</b> {@code vi} and {@code en}. The cookie can be
     * edited in the browser, so anything else is ignored and the default applies (a bogus locale
     * would otherwise reach {@code MessageSource} and the date formatters).
     */
    @Bean
    public LocaleResolver localeResolver(@Value("${app.i18n.cookie-secure:false}") boolean cookieSecure) {
        CookieLocaleResolver resolver = new CookieLocaleResolver(I18n.COOKIE) {
            @Override
            protected Locale parseLocaleValue(String localeValue) {
                return I18n.parse(localeValue);
            }
        };
        resolver.setDefaultLocale(I18n.DEFAULT);
        resolver.setCookiePath("/");
        resolver.setCookieMaxAge(Duration.ofDays(365));
        resolver.setCookieSameSite("Lax");
        resolver.setCookieSecure(cookieSecure);
        // Read by the layout only through the server, never by scripts.
        resolver.setCookieHttpOnly(true);
        // An invalid cookie must not turn every page into a 500 — fall back to the default instead.
        resolver.setRejectInvalidCookies(false);
        return resolver;
    }
}
