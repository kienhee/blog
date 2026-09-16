package com.kienhee.blog.repository;

import org.springframework.data.jpa.repository.Modifying;
import com.kienhee.blog.entity.Post;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.Pageable;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import org.springframework.stereotype.Repository;

import java.time.LocalDateTime;
import java.util.Optional;
import java.util.List;

@Repository
public interface PostRepository extends JpaRepository<Post, Long> {

    /** Counts trashed rows too: a slug held by something in the trash is still taken (the column is unique). */
    @Query(value = "select count(*) from posts where slug = :slug", nativeQuery = true)
    long countAllBySlug(@Param("slug") String slug);

    @Query(value = "select count(*) from posts where slug = :slug and id <> :id", nativeQuery = true)
    long countAllBySlugExcluding(@Param("slug") String slug, @Param("id") Long id);

    default boolean existsBySlug(String slug) {
        return countAllBySlug(slug) > 0;
    }

    default boolean existsBySlugAndIdNot(String slug, Long id) {
        return countAllBySlugExcluding(slug, id) > 0;
    }

    boolean existsByCoverImage(String coverImage);

    @Query("select distinct p.coverImage from Post p where p.coverImage is not null")
    List<String> findDistinctCoverImages();

    @Query("select distinct p from Post p " +
            "left join fetch p.category " +
            "left join fetch p.author " +
            "left join fetch p.hashtags " +
            "order by p.updatedAt desc")
    List<Post> findAllWithDetails();

    @Query("select distinct p from Post p " +
            "left join fetch p.category " +
            "left join fetch p.author " +
            "left join fetch p.hashtags " +
            "where p.id = :id")
    Optional<Post> findWithDetailsById(@Param("id") Long id);

    // ---- Public site: only PUBLISHED posts (DRAFT/SCHEDULED/ARCHIVED are never shown), newest first ----

    @Query(value = "select p from Post p join fetch p.category join fetch p.author " +
            "where p.status = com.kienhee.blog.entity.PostStatus.PUBLISHED order by p.publishedAt desc, p.id desc",
            countQuery = "select count(p) from Post p where p.status = com.kienhee.blog.entity.PostStatus.PUBLISHED")
    Page<Post> findPublished(Pageable pageable);

    @Query(value = "select p from Post p join fetch p.category join fetch p.author " +
            "where p.status = com.kienhee.blog.entity.PostStatus.PUBLISHED and p.category.id = :categoryId " +
            "order by p.publishedAt desc, p.id desc",
            countQuery = "select count(p) from Post p " +
                    "where p.status = com.kienhee.blog.entity.PostStatus.PUBLISHED and p.category.id = :categoryId")
    Page<Post> findPublishedByCategory(@Param("categoryId") Long categoryId, Pageable pageable);

    @Query(value = "select p from Post p join fetch p.category join fetch p.author " +
            "where p.status = com.kienhee.blog.entity.PostStatus.PUBLISHED and p.author.id = :authorId " +
            "order by p.publishedAt desc, p.id desc",
            countQuery = "select count(p) from Post p " +
                    "where p.status = com.kienhee.blog.entity.PostStatus.PUBLISHED and p.author.id = :authorId")
    Page<Post> findPublishedByAuthor(@Param("authorId") Long authorId, Pageable pageable);

    /**
     * Case-insensitive substring match (LIKE %q%) on title / excerpt / SEO description / content.
     * {@code q} is matched literally: the caller escapes % and _ (PublicBlogServiceImpl.escapeLike).
     */
    @Query(value = "select p from Post p join fetch p.category join fetch p.author " +
            "where p.status = com.kienhee.blog.entity.PostStatus.PUBLISHED and (" +
            "lower(p.title) like lower(concat('%', :q, '%')) or lower(p.excerpt) like lower(concat('%', :q, '%')) " +
            "or lower(p.seoDescription) like lower(concat('%', :q, '%')) " +
            "or lower(p.content) like lower(concat('%', :q, '%')) " +
            "or exists (select h from p.hashtags h where h.active = true and (" +
            "lower(h.name) like lower(concat('%', :q, '%')) or lower(h.slug) like lower(concat('%', :q, '%'))))) " +
            "order by p.publishedAt desc, p.id desc",
            countQuery = "select count(p) from Post p where p.status = com.kienhee.blog.entity.PostStatus.PUBLISHED and (" +
                    "lower(p.title) like lower(concat('%', :q, '%')) or lower(p.excerpt) like lower(concat('%', :q, '%')) " +
                    "or lower(p.seoDescription) like lower(concat('%', :q, '%')) " +
                    "or lower(p.content) like lower(concat('%', :q, '%')) " +
                    "or exists (select h from p.hashtags h where h.active = true and (" +
                    "lower(h.name) like lower(concat('%', :q, '%')) or lower(h.slug) like lower(concat('%', :q, '%')))))")
    Page<Post> searchPublished(@Param("q") String q, Pageable pageable);

    /** Published posts carrying one active hashtag, newest first — the "#tag" link on an article. */
    @Query(value = "select distinct p from Post p join fetch p.category join fetch p.author join p.hashtags h " +
            "where p.status = com.kienhee.blog.entity.PostStatus.PUBLISHED and h.active = true " +
            "and lower(h.slug) = lower(:slug) order by p.publishedAt desc, p.id desc",
            countQuery = "select count(distinct p) from Post p join p.hashtags h " +
                    "where p.status = com.kienhee.blog.entity.PostStatus.PUBLISHED and h.active = true " +
                    "and lower(h.slug) = lower(:slug)")
    Page<Post> findPublishedByHashtag(@Param("slug") String slug, Pageable pageable);

    @Query("select distinct p from Post p join fetch p.category join fetch p.author left join fetch p.hashtags " +
            "where p.slug = :slug and p.status = com.kienhee.blog.entity.PostStatus.PUBLISHED")
    Optional<Post> findPublishedBySlug(@Param("slug") String slug);

    /** Older neighbour for the article pager: pass {@code PageRequest.of(0, 1)}. */
    @Query("select p from Post p join fetch p.category where p.status = com.kienhee.blog.entity.PostStatus.PUBLISHED " +
            "and (p.publishedAt < :publishedAt or (p.publishedAt = :publishedAt and p.id < :id)) " +
            "order by p.publishedAt desc, p.id desc")
    List<Post> findPublishedBefore(@Param("publishedAt") LocalDateTime publishedAt, @Param("id") Long id, Pageable pageable);

    /** Newer neighbour for the article pager: pass {@code PageRequest.of(0, 1)}. */
    @Query("select p from Post p join fetch p.category where p.status = com.kienhee.blog.entity.PostStatus.PUBLISHED " +
            "and (p.publishedAt > :publishedAt or (p.publishedAt = :publishedAt and p.id > :id)) " +
            "order by p.publishedAt asc, p.id asc")
    List<Post> findPublishedAfter(@Param("publishedAt") LocalDateTime publishedAt, @Param("id") Long id, Pageable pageable);

    /** Rows of [categoryId, publishedPostCount]. */
    @Query("select p.category.id, count(p) from Post p where p.status = com.kienhee.blog.entity.PostStatus.PUBLISHED " +
            "group by p.category.id")
    List<Object[]> countPublishedByCategory();

    @Query("select count(distinct p.category.id) from Post p " +
            "where p.status = com.kienhee.blog.entity.PostStatus.PUBLISHED and p.author.id = :authorId")
    long countPublishedCategoriesByAuthor(@Param("authorId") Long authorId);

    /** Author ids ordered by how many posts they have published (most first). */
    @Query("select p.author.id from Post p where p.status = com.kienhee.blog.entity.PostStatus.PUBLISHED " +
            "group by p.author.id order by count(p) desc")
    List<Long> findPublishingAuthorIds(Pageable pageable);

    /** Includes trashed posts: they still reference the author (posts.author_id is NOT NULL). */
    @Query(value = "select count(*) from posts where author_id = :authorId", nativeQuery = true)
    long countAllByAuthor(@Param("authorId") Long authorId);

    /** Includes trashed posts: they still reference the category (posts.category_id is NOT NULL). */
    @Query(value = "select count(*) from posts where category_id = :categoryId", nativeQuery = true)
    long countAllByCategory(@Param("categoryId") Long categoryId);

    default boolean existsByAuthor_Id(Long authorId) {
        return countAllByAuthor(authorId) > 0;
    }

    default boolean existsByCategory_Id(Long categoryId) {
        return countAllByCategory(categoryId) > 0;
    }

    /**
     * Publishes every SCHEDULED post whose time has come, in one conditional UPDATE: safe to run repeatedly
     * or on several instances. published_at becomes the scheduled time, so ordering reflects the plan.
     */
    @Modifying(flushAutomatically = true, clearAutomatically = true)
    @Query("update Post p set p.status = com.kienhee.blog.entity.PostStatus.PUBLISHED, p.publishedAt = p.scheduledAt, " +
            "p.updatedAt = :now where p.status = com.kienhee.blog.entity.PostStatus.SCHEDULED " +
            "and p.scheduledAt is not null and p.scheduledAt <= :now")
    int publishDue(@Param("now") LocalDateTime now);

    /** Moves the row to the Trash (the entity is filtered, so this is native). */
    @Modifying(flushAutomatically = true, clearAutomatically = true)
    @Query(value = "update posts set deleted_at = :now where id = :id and deleted_at is null", nativeQuery = true)
    int moveToTrash(@Param("id") Long id, @Param("now") LocalDateTime now);
}
