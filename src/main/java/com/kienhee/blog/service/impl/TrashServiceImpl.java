package com.kienhee.blog.service.impl;

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
 */
@Service
@RequiredArgsConstructor
@Transactional
public class TrashServiceImpl implements TrashService {

    private final JdbcTemplate jdbc;

    private static final RowMapper<TrashItem> ITEM = (rs, i) -> new TrashItem(
            rs.getLong("id"), rs.getString("name"), rs.getString("detail"),
            rs.getObject("deleted_at", LocalDateTime.class));

    @Override
    @Transactional(readOnly = true)
    public List<TrashItem> list(TrashType type) {
        String sql = switch (type) {
            case POSTS -> "select p.id, p.title as name, concat(coalesce(c.name, 'No category'), ' · ', p.status) as detail, p.deleted_at "
                    + "from posts p left join categories c on c.id = p.category_id where p.deleted_at is not null";
            case CATEGORIES -> "select id, name, concat('/', slug) as detail, deleted_at from categories where deleted_at is not null";
            case HASHTAGS -> "select id, name, concat('#', slug) as detail, deleted_at from hashtags where deleted_at is not null";
            case COMMENTS -> "select c.id, c.author_name as name, concat(left(c.content, 120), ' — on ', p.title) as detail, c.deleted_at "
                    + "from comments c join posts p on p.id = c.post_id where c.deleted_at is not null";
        };
        return jdbc.query(sql + " order by deleted_at desc, id desc", ITEM);
    }

    @Override
    @Transactional(readOnly = true)
    public long count(TrashType type) {
        Long n = jdbc.queryForObject("select count(*) from " + table(type) + " where deleted_at is not null", Long.class);
        return n == null ? 0 : n;
    }

    @Override
    public void restore(TrashType type, Long id) {
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
        }
    }

    @Override
    public void purge(TrashType type, Long id) {
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
        };
    }

    @Override
    public int purgeExpired(int retentionDays) {
        LocalDateTime cutoff = LocalDateTime.now().minusDays(Math.max(retentionDays, 1));
        int purged = 0;
        // Comments and posts first, so a category whose posts expire together can go in the same sweep.
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
