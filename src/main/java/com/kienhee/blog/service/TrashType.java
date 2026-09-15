package com.kienhee.blog.service;

import java.util.Arrays;
import java.util.Optional;

/** What can be in the shared Trash, and which permission manages each kind (media has its own trash page). */
public enum TrashType {

    POSTS("posts", "Posts", "post", "posts", "posts:delete"),
    CATEGORIES("categories", "Categories", "category", "categories", "categories:delete"),
    HASHTAGS("hashtags", "Hashtags", "hashtag", "hashtags", "hashtags:delete"),
    COMMENTS("comments", "Comments", "comment", "comments", "comments:delete");

    private final String slug;
    private final String label;
    private final String singular;
    private final String plural;
    private final String permission;

    TrashType(String slug, String label, String singular, String plural, String permission) {
        this.slug = slug;
        this.label = label;
        this.singular = singular;
        this.plural = plural;
        this.permission = permission;
    }

    public String getSlug() {
        return slug;
    }

    public String getLabel() {
        return label;
    }

    public String getSingular() {
        return singular;
    }

    public String getPlural() {
        return plural;
    }

    /** Moving to the trash, restoring and deleting permanently all need this permission. */
    public String getPermission() {
        return permission;
    }

    public static Optional<TrashType> fromSlug(String slug) {
        return Arrays.stream(values()).filter(t -> t.slug.equals(slug)).findFirst();
    }
}
