package com.kienhee.blog.controller;

import jakarta.servlet.http.HttpServletRequest;
import org.springframework.security.authentication.AnonymousAuthenticationToken;
import org.springframework.security.core.Authentication;
import org.springframework.security.core.context.SecurityContextHolder;

import java.util.Locale;
import java.util.regex.Pattern;

/**
 * Which article requests count as a view: real browser GETs only. Bots and tools (by User-Agent), link
 * previews, speculative prefetches, requests without a User-Agent and signed-in staff are ignored. Nothing about
 * the visitor is stored.
 */
public final class ViewCountingPolicy {

    private static final Pattern NOT_A_READER = Pattern.compile(
            "(?i)(bot|crawl|spider|slurp|facebookexternalhit|embedly|preview|headless|lighthouse|pingdom|monitor|"
                    + "curl|wget|python|httpclient|okhttp|java/|go-http|scrapy|axios|node-fetch|postman)");

    private ViewCountingPolicy() {
    }

    public static boolean isCountable(HttpServletRequest request) {
        if (!"GET".equalsIgnoreCase(request.getMethod())) {
            return false;
        }
        String userAgent = request.getHeader("User-Agent");
        if (userAgent == null || userAgent.isBlank() || NOT_A_READER.matcher(userAgent).find()) {
            return false;
        }
        String purpose = request.getHeader("Sec-Purpose") != null ? request.getHeader("Sec-Purpose") : request.getHeader("Purpose");
        if (purpose != null && purpose.toLowerCase(Locale.ROOT).contains("prefetch")) {
            return false;
        }
        return !isStaff(SecurityContextHolder.getContext().getAuthentication());
    }

    /** People who work on the site (can open the dashboard) don't inflate their own numbers. */
    static boolean isStaff(Authentication authentication) {
        return authentication != null
                && authentication.isAuthenticated()
                && !(authentication instanceof AnonymousAuthenticationToken)
                && authentication.getAuthorities().stream().anyMatch(a -> "dashboard:view".equals(a.getAuthority()));
    }
}
