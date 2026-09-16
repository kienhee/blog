package com.kienhee.blog.service;

import java.time.LocalDateTime;
import java.util.List;

/**
 * The Trash behind {@code /admin/trash}. For posts, categories, hashtags and comments, deleting anywhere in the
 * admin only sets {@code deleted_at}; those entities are annotated {@code @SQLRestriction("deleted_at IS NULL")},
 * so trashed rows are invisible to every JPA query and this service is the only place that sees them, through
 * native SQL.
 *
 * <p>The two media types are different: they are <b>delegated</b> to {@code MediaService} /
 * {@code MediaFolderService}, which already own the parts that plain SQL cannot do — removing the bytes on disk
 * through {@code StorageTransactionHelper}, giving quota back, restoring a folder together with the exact batch
 * of files trashed with it. This service only gives them one shared UI.
 */
public interface TrashService {

    /**
     * One row of a trash tab. {@code sizeBytes} and {@code previewUrl} are only set for media files; the other
     * types leave them null and the table simply renders no such column.
     */
    record TrashItem(Long id, String name, String detail, LocalDateTime deletedAt, Long sizeBytes, String previewUrl) {

        public TrashItem(Long id, String name, String detail, LocalDateTime deletedAt) {
            this(id, name, detail, deletedAt, null, null);
        }
    }

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
     *
     * <p>Media is deliberately not swept here: it keeps its own retention window ({@code app.media.trash.*}),
     * because trashed files hold disk space and quota and are usually cleared sooner.
     */
    int purgeExpired(int retentionDays);
}
