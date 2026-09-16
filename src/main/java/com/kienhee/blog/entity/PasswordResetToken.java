package com.kienhee.blog.entity;

import jakarta.persistence.*;
import lombok.*;

import java.time.LocalDateTime;

/** A single-use password reset link. See {@code V1__Auth.sql}. */
@Entity
@Table(name = "password_reset_tokens")
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class PasswordResetToken {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "user_id", nullable = false)
    private User user;

    /** Hex SHA-256 of the token in the emailed link; the token itself is never stored. */
    @Column(name = "token_hash", nullable = false, length = 64, columnDefinition = "CHAR(64)")
    private String tokenHash;

    @Column(name = "expires_at", nullable = false)
    private LocalDateTime expiresAt;

    /** Set when the link is used, or when a newer request / a successful reset invalidates it. */
    @Column(name = "used_at")
    private LocalDateTime usedAt;

    @Column(name = "created_at", nullable = false, updatable = false)
    private LocalDateTime createdAt;

    @Column(name = "request_ip", length = 45)
    private String requestIp;
}
