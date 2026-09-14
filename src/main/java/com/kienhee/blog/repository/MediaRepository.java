package com.kienhee.blog.repository;

import com.kienhee.blog.entity.Media;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Modifying;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import org.springframework.stereotype.Repository;

import java.time.LocalDateTime;
import java.util.List;
import java.util.Optional;

@Repository
public interface MediaRepository extends JpaRepository<Media, Long> {

    @Query("select m from Media m left join fetch m.uploadedBy left join fetch m.folder "
            + "where m.status = com.kienhee.blog.entity.Media$Status.ACTIVE order by m.createdAt desc")
    List<Media> findAllWithUploader();

    /**
     * The trash listing. Join-fetches the same associations as the grid query: the trash
     * page renders uploader and folder names and open-in-view is off.
     */
    @Query("select m from Media m left join fetch m.uploadedBy left join fetch m.folder "
            + "where m.status = com.kienhee.blog.entity.Media$Status.TRASHED "
            + "order by m.deletedAt desc")
    List<Media> findTrashedWithUploader();

    boolean existsByFolderId(Long folderId);

    /** Dedupe twin lookup: any live row already backed by the same bytes. */
    Optional<Media> findFirstBySha256AndStatus(String sha256, Media.Status status);

    // ---- filesystem mirror (V16) ---------------------------------------------

    /** Every row (any status) filed directly in a folder — used when the folder row goes away. */
    List<Media> findByFolderId(Long folderId);

    /**
     * Storage paths beginning with {@code prefix} ({@code ""} = all). The caller narrows to one
     * directory in Java; used to reserve names that a row already claims (any status).
     */
    @Query("select m.storagePath from Media m where m.storagePath like concat(:prefix, '%')")
    List<String> findStoragePathsStartingWith(@Param("prefix") String prefix);

    /**
     * Re-points every file under a moved/renamed directory. Compared as bytes so the
     * case/accent-insensitive default collation can never match a neighbouring directory.
     *
     * @param oldPrefix old directory path with trailing {@code /}
     * @param newPrefix new directory path with trailing {@code /}
     */
    @Modifying(clearAutomatically = true, flushAutomatically = true)
    @Query(value = "UPDATE media SET storage_path = CONCAT(:newPrefix, SUBSTRING(storage_path, CHAR_LENGTH(:oldPrefix) + 1)) "
            + "WHERE CAST(LEFT(storage_path, CHAR_LENGTH(:oldPrefix)) AS BINARY) = CAST(:oldPrefix AS BINARY)",
            nativeQuery = true)
    int rewriteStoragePathPrefix(@Param("oldPrefix") String oldPrefix, @Param("newPrefix") String newPrefix);

    // ---- trash (V15) --------------------------------------------------------

    List<Media> findByStatus(Media.Status status);

    List<Media> findByFolderIdAndStatus(Long folderId, Media.Status status);

    long countByStatus(Media.Status status);

    /** Retention sweep candidates: trashed strictly longer than the cutoff. */
    List<Media> findByStatusAndDeletedAtBefore(Media.Status status, LocalDateTime cutoff);

    /**
     * Total logical bytes sitting in the trash. Trashed files still count against the
     * uploader's quota (only a purge gives bytes back), so the UI has to be able to show
     * this number — it is the single most misread part of the feature.
     */
    @Query("select coalesce(sum(m.sizeBytes), 0) from Media m where m.status = :status")
    long sumSizeBytesByStatus(@Param("status") Media.Status status);
}
