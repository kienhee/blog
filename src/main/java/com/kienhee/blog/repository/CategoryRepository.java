package com.kienhee.blog.repository;

import java.time.LocalDateTime;
import org.springframework.data.repository.query.Param;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.jpa.repository.Modifying;
import com.kienhee.blog.entity.Category;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

import java.util.List;
import java.util.Optional;

@Repository
public interface CategoryRepository extends JpaRepository<Category, Long> {

    /** Counts trashed rows too: a slug held by something in the trash is still taken (the column is unique). */
    @Query(value = "select count(*) from categories where slug = :slug", nativeQuery = true)
    long countAllBySlug(@Param("slug") String slug);

    @Query(value = "select count(*) from categories where slug = :slug and id <> :id", nativeQuery = true)
    long countAllBySlugExcluding(@Param("slug") String slug, @Param("id") Long id);

    default boolean existsBySlug(String slug) {
        return countAllBySlug(slug) > 0;
    }

    default boolean existsBySlugAndIdNot(String slug, Long id) {
        return countAllBySlugExcluding(slug, id) > 0;
    }

    /** Includes trashed subcategories, which still point at their parent. */
    @Query(value = "select count(*) from categories where parent_id = :parentId", nativeQuery = true)
    long countAllByParent(@Param("parentId") Long parentId);

    default boolean existsByParentId(Long parentId) {
        return countAllByParent(parentId) > 0;
    }

    Optional<Category> findBySlugAndVisibleTrue(String slug);

    List<Category> findByVisibleTrueOrderByNameAsc();

    /** Moves the row to the Trash (the entity is filtered, so this is native). */
    @Modifying(flushAutomatically = true, clearAutomatically = true)
    @Query(value = "update categories set deleted_at = :now where id = :id and deleted_at is null", nativeQuery = true)
    int moveToTrash(@Param("id") Long id, @Param("now") LocalDateTime now);
}
