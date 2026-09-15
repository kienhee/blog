package com.kienhee.blog.service;

import java.time.LocalDateTime;
import java.util.List;

/**
 * The shared Trash for posts, categories, hashtags and comments. Deleting one of them anywhere in the admin only
 * sets {@code deleted_at}; entities are annotated {@code @SQLRestriction("deleted_at IS NULL")}, so trashed rows
 * are invisible to every JPA query. This service is the only place that sees them, through native SQL.
 */
public interface TrashService {

    record TrashItem(Long id, String name, String detail, LocalDateTime deletedAt) {}

    /** Newest first. */
    List<TrashItem> list(TrashType type);

    long count(TrashType type);

    /**
     * Brings an item back. A comment comes back with the replies that were trashed together with it.
     *
     * @throws IllegalArgumentException when it is not in the trash, or what it belongs to (category, parent
     *                                  category, post, parent comment) is itself in the trash
     */
    void restore(TrashType type, Long id);

    /**
     * Deletes an item for good. A post takes its comments and hashtag links with it; a comment its replies.
     *
     * @throws IllegalArgumentException when it is not in the trash, or a category is still used by posts or
     *                                  subcategories (including trashed ones)
     */
    void purge(TrashType type, Long id);

    /**
     * Deletes permanently whatever has been in the trash longer than {@code retentionDays}. Items that still
     * can't be purged (a category with posts) stay. Returns how many were deleted.
     */
    int purgeExpired(int retentionDays);
}
