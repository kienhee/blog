package com.kienhee.blog.repository;

import java.time.LocalDateTime;
import org.springframework.data.repository.query.Param;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.jpa.repository.Modifying;
import com.kienhee.blog.entity.Hashtag;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

@Repository
public interface HashtagRepository extends JpaRepository<Hashtag, Long> {

    /** Counts trashed rows too: a slug held by something in the trash is still taken (the column is unique). */
    @Query(value = "select count(*) from hashtags where slug = :slug", nativeQuery = true)
    long countAllBySlug(@Param("slug") String slug);

    @Query(value = "select count(*) from hashtags where slug = :slug and id <> :id", nativeQuery = true)
    long countAllBySlugExcluding(@Param("slug") String slug, @Param("id") Long id);

    default boolean existsBySlug(String slug) {
        return countAllBySlug(slug) > 0;
    }

    default boolean existsBySlugAndIdNot(String slug, Long id) {
        return countAllBySlugExcluding(slug, id) > 0;
    }

    /** Moves the row to the Trash (the entity is filtered, so this is native). */
    @Modifying(flushAutomatically = true, clearAutomatically = true)
    @Query(value = "update hashtags set deleted_at = :now where id = :id and deleted_at is null", nativeQuery = true)
    int moveToTrash(@Param("id") Long id, @Param("now") LocalDateTime now);
}
