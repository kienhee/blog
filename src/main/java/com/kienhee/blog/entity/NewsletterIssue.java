package com.kienhee.blog.entity;

import jakarta.persistence.*;
import lombok.*;

import java.time.LocalDateTime;

/** A newsletter issue that was sent. See {@code V24__Newsletter.sql}. */
@Entity
@Table(name = "newsletter_issues")
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class NewsletterIssue {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "subject", nullable = false, length = 200)
    private String subject;

    /** Plain text; blank lines separate paragraphs. Rendered escaped in the email. */
    @Column(name = "body", nullable = false, columnDefinition = "TEXT")
    private String body;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "sent_by")
    private User sentBy;

    @Column(name = "recipients", nullable = false)
    private int recipients;

    @Column(name = "sent_at", nullable = false)
    private LocalDateTime sentAt;
}
