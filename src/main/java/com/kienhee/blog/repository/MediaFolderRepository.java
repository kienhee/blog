package com.kienhee.blog.repository;

import com.kienhee.blog.entity.MediaFolder;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import org.springframework.stereotype.Repository;

import java.time.LocalDateTime;
import java.util.List;
import java.util.Optional;

@Repository
public interface MediaFolderRepository extends JpaRepository<MediaFolder, Long> {

    boolean existsBySlug(String slug);

    List<MediaFolder> findAllByOrderByNameAsc();

    Optional<MediaFolder> findByNameIgnoreCase(String name);

    /**
     * Root-scoped name lookup. Plain {@code findByNameIgnoreCase} throws
     * NonUniqueResultException once the same name exists in two branches of the tree.
     */
    Optional<MediaFolder> findFirstByParentIsNullAndNameIgnoreCase(String name);

    /**
     * Same lookup, but only live folders. A trashed folder still owns its (parent, slug)
     * row in the unique index, so it must not be handed back as a reusable target — the
     * caller creates a fresh one (with a uniquified slug) instead.
     */
    Optional<MediaFolder> findFirstByParentIsNullAndNameIgnoreCaseAndStatus(
            String name, MediaFolder.Status status);

    // ---- nested tree (V13) --------------------------------------------------

    /** Direct children of a folder. Pass null for the root level. */
    List<MediaFolder> findByParentIdOrderByNameAsc(Long parentId);

    List<MediaFolder> findByParentIsNullOrderByNameAsc();

    /** Slug uniqueness is scoped to the parent, so both parts are needed. */
    Optional<MediaFolder> findByParentIdAndSlug(Long parentId, String slug);

    Optional<MediaFolder> findByParentIsNullAndSlug(String slug);

    boolean existsByParentIdAndSlug(Long parentId, String slug);

    boolean existsByParentIsNullAndSlug(String slug);

    boolean existsByParentIdAndSlugAndIdNot(Long parentId, String slug, Long id);

    boolean existsByParentIsNullAndSlugAndIdNot(String slug, Long id);

    /**
     * Whole subtree under (and including) {@code pathPrefix}, via the materialized path
     * index. Callers pass the ancestor's own path, e.g. {@code "/1/17/"}.
     */
    @Query("SELECT f FROM MediaFolder f WHERE f.path LIKE CONCAT(:pathPrefix, '%') "
            + "ORDER BY f.path ASC")
    List<MediaFolder> findSubtree(@Param("pathPrefix") String pathPrefix);

    /** Subtree excluding the folder itself. */
    @Query("SELECT f FROM MediaFolder f WHERE f.path LIKE CONCAT(:pathPrefix, '%') "
            + "AND f.id <> :folderId ORDER BY f.path ASC")
    List<MediaFolder> findDescendants(@Param("pathPrefix") String pathPrefix,
                                      @Param("folderId") Long folderId);

    long countByParentId(Long parentId);

    long countByParentIdAndStatus(Long parentId, MediaFolder.Status status);

    /**
     * open-in-view is off and {@code parent} is LAZY, so any listing rendered in a template
     * that touches {@code folder.parent} must come from here.
     */
    @Query("SELECT f FROM MediaFolder f LEFT JOIN FETCH f.parent WHERE f.status = :status "
            + "ORDER BY f.path ASC")
    List<MediaFolder> findAllWithParent(@Param("status") MediaFolder.Status status);

    // ---- trash (V15) --------------------------------------------------------

    List<MediaFolder> findByStatusOrderByDeletedAtDesc(MediaFolder.Status status);

    List<MediaFolder> findByStatus(MediaFolder.Status status);

    List<MediaFolder> findByStatusOrderByNameAsc(MediaFolder.Status status);

    long countByStatus(MediaFolder.Status status);

    List<MediaFolder> findByStatusAndDeletedAtBefore(MediaFolder.Status status, LocalDateTime cutoff);

    /** Subtree restricted to one status — used to block deleting a folder that still has live children. */
    @Query("SELECT f FROM MediaFolder f WHERE f.path LIKE CONCAT(:pathPrefix, '%') "
            + "AND f.status = :status ORDER BY f.path ASC")
    List<MediaFolder> findSubtreeByStatus(@Param("pathPrefix") String pathPrefix,
                                          @Param("status") MediaFolder.Status status);
}
