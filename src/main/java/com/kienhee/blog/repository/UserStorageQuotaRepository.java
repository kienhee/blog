package com.kienhee.blog.repository;

import com.kienhee.blog.entity.UserStorageQuota;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Modifying;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import org.springframework.stereotype.Repository;

import java.util.Optional;

@Repository
public interface UserStorageQuotaRepository extends JpaRepository<UserStorageQuota, Long> {

    Optional<UserStorageQuota> findByUserId(Long userId);

    /**
     * Reserve {@code bytes} of the user's budget in one atomic statement: the quota check
     * lives in the WHERE clause, so two concurrent uploads can never both squeeze past the
     * limit (which a read-then-write check in Java would allow).
     *
     * @return 1 when the reservation succeeded, 0 when it would exceed the quota
     *         (or the user has no quota row)
     */
    @Modifying(clearAutomatically = true, flushAutomatically = true)
    @Query(value = "UPDATE user_storage_quota SET used_bytes = used_bytes + :bytes "
            + "WHERE user_id = :userId AND used_bytes + :bytes <= quota_bytes",
            nativeQuery = true)
    int reserve(@Param("userId") Long userId, @Param("bytes") long bytes);

    /**
     * Give bytes back after a delete or a failed upload. Clamped at 0 so a double release
     * cannot drive the counter negative.
     *
     * @return rows affected
     */
    @Modifying(clearAutomatically = true, flushAutomatically = true)
    @Query(value = "UPDATE user_storage_quota "
            + "SET used_bytes = GREATEST(used_bytes - :bytes, 0) WHERE user_id = :userId",
            nativeQuery = true)
    int release(@Param("userId") Long userId, @Param("bytes") long bytes);
}
