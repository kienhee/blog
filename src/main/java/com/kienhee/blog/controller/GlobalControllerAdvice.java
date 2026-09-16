package com.kienhee.blog.controller;

import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.service.SettingService;
import jakarta.servlet.http.HttpServletRequest;
import lombok.RequiredArgsConstructor;
import org.springframework.security.core.Authentication;
import org.springframework.security.core.GrantedAuthority;
import org.springframework.security.core.context.SecurityContextHolder;
import org.springframework.web.bind.annotation.ControllerAdvice;
import org.springframework.web.bind.annotation.ModelAttribute;

import java.security.Principal;
import java.util.Collections;
import java.util.Set;
import java.util.stream.Collectors;

@ControllerAdvice
@RequiredArgsConstructor
public class GlobalControllerAdvice {

    private final UserRepository userRepository;
    private final SettingService settingService;

    /** Rows per page for admin DataTables ({@code blog.posts_per_page} setting), exposed as a meta tag. */
    @ModelAttribute("postsPerPage")
    public int postsPerPage() {
        int value = settingService.getInt("blog.posts_per_page", 10);
        return value >= 1 && value <= 100 ? value : 10;
    }

    /**
     * Site domain from Settings ({@code site.domain}), for admin templates that show a public URL —
     * e.g. the prefix in front of the post slug field. Displayed as typed, without a scheme.
     */
    @ModelAttribute("siteDomain")
    public String siteDomain() {
        String domain = settingService.get("site.domain", "");
        if (domain.isBlank()) {
            return "";
        }
        return domain.trim().replaceFirst("^https?://", "").replaceAll("/+$", "");
    }

    @ModelAttribute("currentUri")
    public String currentUri(HttpServletRequest request) {
        String uri = request.getRequestURI();
        if (uri == null) {
            return "/";
        }
        String contextPath = request.getContextPath();
        if (contextPath != null && !contextPath.isEmpty() && uri.startsWith(contextPath)) {
            uri = uri.substring(contextPath.length());
        }
        return (uri.isEmpty()) ? "/" : uri;
    }

    @ModelAttribute("currentUser")
    public User currentUser(Principal principal) {
        if (principal == null || principal.getName() == null) {
            return null;
        }
        // With role fetched: templates (sidebar, profile) may read currentUser.role,
        // and open-in-view is off so a lazy proxy would blow up during rendering.
        return userRepository.findByEmailWithRole(principal.getName().trim().toLowerCase()).orElse(null);
    }

    /**
     * Permission codes of the signed-in user ("posts:create", ...), for hiding buttons
     * the user can't use: {@code th:if="${perms.contains('posts:create')}"}.
     * This is presentation only — the real enforcement is @PreAuthorize on the controllers.
     */
    @ModelAttribute("perms")
    public Set<String> permissions() {
        Authentication auth = SecurityContextHolder.getContext().getAuthentication();
        if (auth == null || !auth.isAuthenticated()) {
            return Collections.emptySet();
        }
        return auth.getAuthorities().stream()
                .map(GrantedAuthority::getAuthority)
                .collect(Collectors.toSet());
    }
}
