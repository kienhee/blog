package com.kienhee.blog.support;

import org.springframework.test.web.servlet.request.RequestPostProcessor;

import static org.springframework.security.test.web.servlet.request.SecurityMockMvcRequestPostProcessors.user;

/**
 * Test authentication helpers. Controllers are guarded with @PreAuthorize on permission
 * codes, so a plain {@code user("...")} post-processor (no authorities) now gets 403 —
 * which is the correct production behaviour. Tests that want to exercise the happy path
 * must authenticate as someone holding the relevant permissions.
 */
public final class TestAuth {

    /** Every permission in the seeded catalog (V20) — equivalent to the Admin role. */
    public static final String[] ALL_PERMISSIONS = {
            "dashboard:view",
            "posts:view", "posts:create", "posts:edit", "posts:publish", "posts:delete",
            "categories:view", "categories:create", "categories:edit", "categories:delete",
            "hashtags:view", "hashtags:create", "hashtags:edit", "hashtags:delete",
            "media:view", "media:create", "media:edit", "media:delete", "media:purge",
            "comments:view", "comments:edit", "comments:delete",
            "users:view", "users:create", "users:edit", "users:delete",
            "roles:view", "roles:create", "roles:edit", "roles:delete",
            "settings:view", "settings:edit",
            "subscribers:view", "subscribers:send", "subscribers:delete"
    };

    private TestAuth() {
    }

    /** Signed in as the seeded admin, holding every permission. */
    public static RequestPostProcessor owner() {
        return owner("test-owner@kienhee.test");
    }

    public static RequestPostProcessor owner(String email) {
        return user(email).authorities(authorities(ALL_PERMISSIONS));
    }

    /** Signed in holding only the given permission codes — for testing denials. */
    public static RequestPostProcessor withPermissions(String email, String... codes) {
        return user(email).authorities(authorities(codes));
    }

    private static org.springframework.security.core.GrantedAuthority[] authorities(String... codes) {
        org.springframework.security.core.GrantedAuthority[] result =
                new org.springframework.security.core.authority.SimpleGrantedAuthority[codes.length];
        for (int i = 0; i < codes.length; i++) {
            result[i] = new org.springframework.security.core.authority.SimpleGrantedAuthority(codes[i]);
        }
        return result;
    }
}
