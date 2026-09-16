package com.kienhee.blog.entity;

import jakarta.persistence.*;
import lombok.*;

import java.time.LocalDateTime;

/** A newsletter subscriber (double opt-in). See {@code V6__Newsletter.sql}. */
@Entity
@Table(name = "subscribers")
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class Subscriber {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "email", nullable = false, length = 150)
    private String email;

    @Enumerated(EnumType.STRING)
    @Column(name = "status", nullable = false, length = 20)
    private SubscriberStatus status;

    /** SHA-256 of the confirmation token; cleared once confirmed. */
    @Column(name = "confirm_token_hash", length = 64, columnDefinition = "CHAR(64)")
    private String confirmTokenHash;

    @Column(name = "confirm_sent_at")
    private LocalDateTime confirmSentAt;

    /** Random and long-lived: every issue links to /subscribe/unsubscribe?token=… */
    @Column(name = "unsubscribe_token", nullable = false, length = 43, columnDefinition = "CHAR(43)")
    private String unsubscribeToken;

    @Column(name = "created_at", nullable = false, updatable = false)
    private LocalDateTime createdAt;

    @Column(name = "confirmed_at")
    private LocalDateTime confirmedAt;

    @Column(name = "unsubscribed_at")
    private LocalDateTime unsubscribedAt;
}
