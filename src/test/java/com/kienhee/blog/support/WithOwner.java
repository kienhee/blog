package com.kienhee.blog.support;

import org.springframework.security.test.context.support.WithMockUser;

import java.lang.annotation.ElementType;
import java.lang.annotation.Retention;
import java.lang.annotation.RetentionPolicy;
import java.lang.annotation.Target;

/**
 * Signed in as the seeded admin holding every permission — the annotation-based
 * equivalent of {@link TestAuth#owner()}. Keep in sync with {@link TestAuth#ALL_PERMISSIONS}.
 */
@Retention(RetentionPolicy.RUNTIME)
@Target({ElementType.METHOD, ElementType.TYPE})
@WithMockUser(username = "test-owner@kienhee.test", authorities = {
        "ROLE_ADMIN",
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
})
public @interface WithOwner {
}
