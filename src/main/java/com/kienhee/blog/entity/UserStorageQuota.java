package com.kienhee.blog.entity;

import jakarta.persistence.*;
import lombok.*;

import java.time.LocalDateTime;

/**
 * Per-user storage budget. Exactly one row per user (created by migration V13 for existing
 * accounts). {@code usedBytes} is moved only through the atomic reserve/release statements
 * in {@code UserStorageQuotaRepository}.
 */
@Entity
@Table(name = "user_storage_quota")
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class UserStorageQuota {

    /** 1 GB. */
    public static final long DEFAULT_QUOTA_BYTES = 1_073_741_824L;

    @Id
    @Column(name = "user_id")
    private Long userId;

    @OneToOne(fetch = FetchType.LAZY)
    @MapsId
    @JoinColumn(name = "user_id")
    private User user;

    @Column(name = "quota_bytes", nullable = false)
    @Builder.Default
    private long quotaBytes = DEFAULT_QUOTA_BYTES;

    @Column(name = "used_bytes", nullable = false)
    private long usedBytes;

    /** Maintained by MySQL (ON UPDATE CURRENT_TIMESTAMP). */
    @Column(name = "updated_at", insertable = false, updatable = false)
    private LocalDateTime updatedAt;
}
