package com.kienhee.blog.service;

import java.util.Arrays;
import java.util.Optional;

/**
 * What can be in the Trash, and which permission manages each kind. Media used to have a separate trash page;
 * it is now two more tabs here, which is why a type carries a purge permission of its own: everything else
 * purges with the same {@code <module>:delete} it was trashed with, media needs {@code media:purge}.
 */
public enum TrashType {

    POSTS("posts", "Posts", "post", "posts", "posts:delete", "posts:delete"),
    CATEGORIES("categories", "Categories", "category", "categories", "categories:delete", "categories:delete"),
    HASHTAGS("hashtags", "Hashtags", "hashtag", "hashtags", "hashtags:delete", "hashtags:delete"),
    COMMENTS("comments", "Comments", "comment", "comments", "comments:delete", "comments:delete"),
    MEDIA_FILES("media-files", "Media files", "file", "files", "media:delete", "media:purge"),
    MEDIA_FOLDERS("media-folders", "Media folders", "folder", "folders", "media:delete", "media:purge");

    private final String slug;
    private final String label;
    private final String singular;
    private final String plural;
    private final String permission;
    private final String purgePermission;

    TrashType(String slug, String label, String singular, String plural, String permission, String purgePermission) {
        this.slug = slug;
        this.label = label;
        this.singular = singular;
        this.plural = plural;
        this.permission = permission;
        this.purgePermission = purgePermission;
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

    /** Seeing the tab, moving an item to the trash and restoring it all need this permission. */
    public String getPermission() {
        return permission;
    }

    /** Deleting permanently needs this one — the same as {@link #getPermission()} except for media. */
    public String getPurgePermission() {
        return purgePermission;
    }

    /** Media rows live in their own services (files on disk, quota), not in TrashService's native SQL. */
    public boolean isMedia() {
        return this == MEDIA_FILES || this == MEDIA_FOLDERS;
    }

    public static Optional<TrashType> fromSlug(String slug) {
        return Arrays.stream(values()).filter(t -> t.slug.equals(slug)).findFirst();
    }
}
