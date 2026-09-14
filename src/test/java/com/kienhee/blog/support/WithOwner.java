package com.kienhee.blog.support;

import org.springframework.security.test.context.support.WithMockUser;

import java.lang.annotation.ElementType;
import java.lang.annotation.Retention;
import java.lang.annotation.RetentionPolicy;
import java.lang.annotation.Target;

/**
 * Signed in as the seeded admin holding every permission — the annotation-based
 * equivalent of {@link TestAuth#owner()}.
 */
@Retention(RetentionPolicy.RUNTIME)
@Target({ElementType.METHOD, ElementType.TYPE})
@WithMockUser(username = "admin@kienhee.com", authorities = {
        "ROLE_OWNER",
        "posts:view", "posts:create", "posts:edit", "posts:delete",
        "categories:view", "categories:create", "categories:edit", "categories:delete",
        "hashtags:view", "hashtags:create", "hashtags:edit", "hashtags:delete",
        "media:view", "media:create", "media:edit", "media:delete",
        "comments:view", "comments:create", "comments:edit", "comments:delete",
        "users:view", "users:create", "users:edit", "users:delete",
        "settings:view", "settings:create", "settings:edit", "settings:delete"
})
public @interface WithOwner {
}
