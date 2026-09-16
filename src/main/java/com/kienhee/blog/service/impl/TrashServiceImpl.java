package com.kienhee.blog.service.impl;

import com.kienhee.blog.entity.Media;
import com.kienhee.blog.entity.MediaFolder;
import com.kienhee.blog.service.MediaFolderService;
import com.kienhee.blog.service.MediaService;
import com.kienhee.blog.service.TrashService;
import com.kienhee.blog.service.TrashType;
import lombok.RequiredArgsConstructor;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.jdbc.core.RowMapper;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDateTime;
import java.util.List;

/**
 * Native SQL on purpose: trashed rows are hidden from JPA by {@code @SQLRestriction}. Runs inside the JPA
 * transaction (JdbcTemplate shares its connection), so a refused restore/purge changes nothing.
 *
 * <p>The media types are the exception: every call for them is forwarded to the media services, which own the
 * filesystem and quota side. Nothing about media is reimplemented here.
 */
@Service
@RequiredArgsConstructor
@Transactional
public class TrashServiceImpl implements TrashService {

    private final JdbcTemplate jdbc;
    private final MediaService mediaService;
    private final MediaFolderService mediaFolderService;

    private static final RowMapper<TrashItem> ITEM = (rs, i) -> new TrashItem(
            rs.getLong("id"), rs.getString("name"), rs.getString("detail"),
            rs.getObject("deleted_at", LocalDateTime.class));

    @Override
    @Transactional(readOnly = true)
    public List<TrashItem> list(TrashType type) {
        if (type.isMedia()) {
            return listMedia(type);
        }
        String sql = switch (type) {
            case POSTS -> "select p.id, p.title as name, concat(coalesce(c.name, 'No category'), ' · ', p.status) as detail, p.deleted_at "
                    + "from posts p left join categories c on c.id = p.category_id where p.deleted_at is not null";
            case CATEGORIES -> "select id, name, concat('/', slug) as detail, deleted_at from categories where deleted_at is not null";
            case HASHTAGS -> "select id, name, concat('#', slug) as detail, deleted_at from hashtags where deleted_at is not null";
            case COMMENTS -> "select c.id, c.author_name as name, concat(left(c.content, 120), ' — on ', p.title) as detail, c.deleted_at "
                    + "from comments c join posts p on p.id = c.post_id where c.deleted_at is not null";
            case MEDIA_FILES, MEDIA_FOLDERS -> throw new IllegalStateException("handled above");
        };
        return jdbc.query(sql + " order by deleted_at desc, id desc", ITEM);
    }

    @Override
    @Transactional(readOnly = true)
    public long count(TrashType type) {
        if (type.isMedia()) {
            MediaService.TrashSummary summary = mediaService.getTrashSummary();
            return type == TrashType.MEDIA_FILES ? summary.fileCount() : summary.folderCount();
        }
        Long n = jdbc.queryForObject("select count(*) from " + table(type) + " where deleted_at is not null", Long.class);
        return n == null ? 0 : n;
    }

    @Override
    public void restore(TrashType type, Long id) {
        if (type.isMedia()) {
            // A file whose folder is gone comes back at the root; the media services report that and handle it.
            if (type == TrashType.MEDIA_FILES) {
                mediaService.restoreMedia(id);
            } else {
                mediaFolderService.restoreFolder(id);
            }
            return;
        }
        requireInTrash(type, id);
        switch (type) {
            case POSTS -> {
                if (isTrue("select c.deleted_at is not null from posts p join categories c on c.id = p.category_id where p.id = ?", id)) {
                    throw new IllegalArgumentException("Its category is in the trash. Restore the category first.");
                }
                jdbc.update("update posts set deleted_at = null where id = ?", id);
            }
            case CATEGORIES -> {
                if (isTrue("select p.deleted_at is not null from categories c join categories p on p.id = c.parent_id where c.id = ?", id)) {
                    throw new IllegalArgumentException("Its parent category is in the trash. Restore the parent first.");
                }
                jdbc.update("update categories set deleted_at = null where id = ?", id);
            }
            case HASHTAGS -> jdbc.update("update hashtags set deleted_at = null where id = ?", id);
            case COMMENTS -> {
                if (isTrue("select p.deleted_at is not null from comments c join posts p on p.id = c.post_id where c.id = ?", id)) {
                    throw new IllegalArgumentException("Its post is in the trash. Restore the post first.");
                }
                if (isTrue("select p.deleted_at is not null from comments c join comments p on p.id = c.parent_id where c.id = ?", id)) {
                    throw new IllegalArgumentException("It replies to a comment that is in the trash. Restore that comment first.");
                }
                LocalDateTime trashedAt = jdbc.queryForObject("select deleted_at from comments where id = ?", LocalDateTime.class, id);
                // Replies trashed together with this comment (same timestamp) come back with it.
                jdbc.update("update comments set deleted_at = null where id = ? or (parent_id = ? and deleted_at = ?)", id, id, trashedAt);
            }
            case MEDIA_FILES, MEDIA_FOLDERS -> throw new IllegalStateException("handled above");
        }
    }

    @Override
    public void purge(TrashType type, Long id) {
        if (type.isMedia()) {
            if (type == TrashType.MEDIA_FILES) {
                mediaService.purgeMedia(id);
            } else {
                mediaFolderService.purgeFolder(id);
            }
            return;
        }
        requireInTrash(type, id);
        if (type == TrashType.CATEGORIES) {
            Long posts = jdbc.queryForObject("select count(*) from posts where category_id = ?", Long.class, id);
            if (posts != null && posts > 0) {
                throw new IllegalArgumentException("Posts still use it (some may be in the trash). Delete those posts permanently first.");
            }
            Long children = jdbc.queryForObject("select count(*) from categories where parent_id = ?", Long.class, id);
            if (children != null && children > 0) {
                throw new IllegalArgumentException("It still has subcategories. Delete those permanently first.");
            }
        }
        // posts -> comments and post_hashtags, hashtags -> post_hashtags, comments -> replies: ON DELETE CASCADE.
        jdbc.update("delete from " + table(type) + " where id = ? and deleted_at is not null", id);
    }

    private void requireInTrash(TrashType type, Long id) {
        Long n = jdbc.queryForObject("select count(*) from " + table(type) + " where id = ? and deleted_at is not null", Long.class, id);
        if (n == null || n == 0) {
            throw new IllegalArgumentException("It is not in the trash (already restored or deleted).");
        }
    }

    private boolean isTrue(String sql, Long id) {
        List<Boolean> result = jdbc.query(sql, (rs, i) -> rs.getBoolean(1), id);
        return !result.isEmpty() && Boolean.TRUE.equals(result.get(0));
    }

    private static String table(TrashType type) {
        return switch (type) {
            case POSTS -> "posts";
            case CATEGORIES -> "categories";
            case HASHTAGS -> "hashtags";
            case COMMENTS -> "comments";
            case MEDIA_FILES, MEDIA_FOLDERS -> throw new IllegalArgumentException("Media is not stored in a trash table");
        };
    }

    /** Media rows come from the media services, already ordered newest deletion first. */
    private List<TrashItem> listMedia(TrashType type) {
        if (type == TrashType.MEDIA_FILES) {
            return mediaService.getTrashedMedia().stream()
                    .map(m -> new TrashItem(m.getId(), m.getOriginalFilename(), folderPath(m.getFolder()),
                            m.getDeletedAt(), m.getSizeBytes(), previewUrl(m)))
                    .toList();
        }
        return mediaFolderService.getTrashedFolders().stream()
                .map(f -> new TrashItem(f.getId(), f.getName(), folderPath(f.getParent()), f.getDeletedAt()))
                .toList();
    }

    private static String folderPath(MediaFolder folder) {
        return folder == null ? "Home" : folder.getName();
    }

    /** Images show a thumbnail in the table; everything else renders as a plain row. */
    private static String previewUrl(Media media) {
        if (media.getContentType() == null || !media.getContentType().startsWith("image/")) {
            return null;
        }
        return media.getThumbnailUrl() != null ? media.getThumbnailUrl() : media.getUrl();
    }

    @Override
    public int purgeExpired(int retentionDays) {
        LocalDateTime cutoff = LocalDateTime.now().minusDays(Math.max(retentionDays, 1));
        int purged = 0;
        // Comments and posts first, so a category whose posts expire together can go in the same sweep.
        // Media is not listed: it has its own retention window and its own job (app.media.trash.*).
        for (TrashType type : List.of(TrashType.COMMENTS, TrashType.POSTS, TrashType.HASHTAGS, TrashType.CATEGORIES)) {
            List<Long> expired = jdbc.queryForList(
                    "select id from " + table(type) + " where deleted_at is not null and deleted_at < ?", Long.class, cutoff);
            for (Long id : expired) {
                try {
                    purge(type, id);
                    purged++;
                } catch (IllegalArgumentException e) {
                    // Still referenced (or already gone with its post): leave it for now.
                }
            }
        }
        return purged;
    }
}
