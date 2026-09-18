package com.kienhee.blog.controller;

import com.kienhee.blog.config.I18n;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.servlet.LocaleResolver;

import java.util.Locale;

/**
 * The language switcher ({@code fragments/lang-switch}) posts here.
 *
 * <p>A form post rather than a {@code ?lang=} link, so the choice never ends up in a shared URL,
 * and so CSRF applies like it does to every other write in this app.</p>
 */
@Controller
@RequiredArgsConstructor
public class LocaleController {

    private final LocaleResolver localeResolver;

    @PostMapping("/lang")
    public String change(@RequestParam(name = "code", required = false) String code,
                         @RequestParam(name = "redirect", required = false) String redirect,
                         HttpServletRequest request,
                         HttpServletResponse response) {
        Locale locale = I18n.parse(code);
        if (locale != null) {
            localeResolver.setLocale(request, response, locale);
        }
        return "redirect:" + safeRedirect(redirect);
    }

    /**
     * Only a path on this site is allowed back. Anything else — an absolute URL, a
     * protocol-relative {@code //evil.com}, a backslash or a header-splitting newline — sends the
     * visitor home instead, so the switcher can't be used as an open redirect.
     */
    static String safeRedirect(String target) {
        if (target == null || target.isBlank()) {
            return "/";
        }
        String path = target.trim();
        boolean unsafe = !path.startsWith("/")
                || path.startsWith("//")
                || path.contains("\\")
                || path.contains("\r")
                || path.contains("\n");
        return unsafe ? "/" : path;
    }
}
