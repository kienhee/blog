package com.kienhee.blog.repository;

import com.kienhee.blog.entity.StorageBlob;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Modifying;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import org.springframework.stereotype.Repository;

import java.util.List;
import java.util.Optional;

@Repository
public interface StorageBlobRepository extends JpaRepository<StorageBlob, Long> {

    /**
     * Blobs with these bytes. Since V16 sha256 is NOT unique (dedupe is off), so this is only
     * safe where at most one match is expected (legacy backfill).
     */
    Optional<StorageBlob> findBySha256(String sha256);

    boolean existsBySha256(String sha256);

    Optional<StorageBlob> findByStorageKey(String storageKey);

    /** Janitor candidates: blobs nothing points at any more. */
    List<StorageBlob> findByRefCountLessThanEqual(int refCount);

    /**
     * Atomic ref-count move. Read-modify-write in Java would lose updates whenever two
     * uploads or deletes touch the same blob concurrently.
     *
     * @param delta +1 when a media row starts pointing at the blob, -1 when it stops
     * @return rows affected (0 = blob no longer exists)
     */
    @Modifying(clearAutomatically = true, flushAutomatically = true)
    @Query(value = "UPDATE storage_blob SET ref_count = ref_count + :delta WHERE id = :id",
            nativeQuery = true)
    int addRefCount(@Param("id") Long id, @Param("delta") int delta);

    /**
     * Decrement that refuses to go negative — guards against a double release.
     *
     * @return rows affected (0 = already at zero, or gone)
     */
    @Modifying(clearAutomatically = true, flushAutomatically = true)
    @Query(value = "UPDATE storage_blob SET ref_count = ref_count - 1 "
            + "WHERE id = :id AND ref_count > 0", nativeQuery = true)
    int decrementRefCount(@Param("id") Long id);

    /**
     * Keeps {@code storage_key} (UNIQUE, = media.storage_path) in step with a directory move.
     * Must run in the same transaction as {@link MediaRepository#rewriteStoragePathPrefix}.
     */
    @Modifying(clearAutomatically = true, flushAutomatically = true)
    @Query(value = "UPDATE storage_blob SET storage_key = CONCAT(:newPrefix, SUBSTRING(storage_key, CHAR_LENGTH(:oldPrefix) + 1)) "
            + "WHERE CAST(LEFT(storage_key, CHAR_LENGTH(:oldPrefix)) AS BINARY) = CAST(:oldPrefix AS BINARY)",
            nativeQuery = true)
    int rewriteStorageKeyPrefix(@Param("oldPrefix") String oldPrefix, @Param("newPrefix") String newPrefix);
}
