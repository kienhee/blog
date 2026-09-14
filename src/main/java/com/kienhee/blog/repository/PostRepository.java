package com.kienhee.blog.repository;

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

    boolean existsBySlug(String slug);

    boolean existsBySlugAndIdNot(String slug, Long id);

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

    /** Case-insensitive match on title / excerpt / SEO description. {@code q} is matched literally (no wildcards). */
    @Query(value = "select p from Post p join fetch p.category join fetch p.author " +
            "where p.status = com.kienhee.blog.entity.PostStatus.PUBLISHED and (" +
            "lower(p.title) like lower(concat('%', :q, '%')) or lower(p.excerpt) like lower(concat('%', :q, '%')) " +
            "or lower(p.seoDescription) like lower(concat('%', :q, '%'))) order by p.publishedAt desc, p.id desc",
            countQuery = "select count(p) from Post p where p.status = com.kienhee.blog.entity.PostStatus.PUBLISHED and (" +
                    "lower(p.title) like lower(concat('%', :q, '%')) or lower(p.excerpt) like lower(concat('%', :q, '%')) " +
                    "or lower(p.seoDescription) like lower(concat('%', :q, '%')))")
    Page<Post> searchPublished(@Param("q") String q, Pageable pageable);

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
}
