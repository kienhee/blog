package com.kienhee.blog.repository;

import com.kienhee.blog.entity.PasswordResetToken;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Modifying;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

import java.time.LocalDateTime;
import java.util.List;
import java.util.Optional;

public interface PasswordResetTokenRepository extends JpaRepository<PasswordResetToken, Long> {

    @Query("select t from PasswordResetToken t join fetch t.user where t.tokenHash = :hash")
    Optional<PasswordResetToken> findByTokenHashWithUser(@Param("hash") String hash);

    List<PasswordResetToken> findByUser_IdOrderByIdAsc(Long userId);

    /** Marks every still-unused link of the user as used (a newer request or a completed reset). */
    @Modifying(flushAutomatically = true, clearAutomatically = true)
    @Query("update PasswordResetToken t set t.usedAt = :now where t.user.id = :userId and t.usedAt is null")
    int invalidateUnused(@Param("userId") Long userId, @Param("now") LocalDateTime now);
}
