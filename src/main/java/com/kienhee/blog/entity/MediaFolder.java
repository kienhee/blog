package com.kienhee.blog.entity;

import jakarta.persistence.*;
import lombok.*;

import java.time.LocalDateTime;

@Entity
@Table(
        name = "media_folders",
        uniqueConstraints = @UniqueConstraint(
                name = "uq_folder_parent_slug",
                columnNames = {"parent_id", "slug"}
        )
)
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class MediaFolder {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "name", nullable = false, length = 150)
    private String name;

    /** Unique within the parent folder only — NOT globally (see uq_folder_parent_slug). */
    @Column(name = "slug", nullable = false, length = 170)
    private String slug;

    /** NULL means a root folder. */
    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "parent_id")
    private MediaFolder parent;

    /**
     * Materialized path including this folder's own id, e.g. {@code /1/17/}.
     * Subtree queries are {@code path LIKE '/1/17/%'} against idx_folder_path.
     */
    @Column(name = "path", nullable = false, length = 500)
    @Builder.Default
    private String path = "/";

    /** Number of ancestors; a root folder is 0. */
    @Column(name = "depth", nullable = false)
    private int depth;

    @Enumerated(EnumType.STRING)
    @Column(name = "status", nullable = false, length = 20)
    @Builder.Default
    private Status status = Status.ACTIVE;

    @Column(name = "deleted_at")
    private LocalDateTime deletedAt;

    @Column(name = "created_at", updatable = false)
    private LocalDateTime createdAt;

    @PrePersist
    protected void onCreate() {
        this.createdAt = LocalDateTime.now();
        if (this.status == null) {
            this.status = Status.ACTIVE;
        }
        if (this.path == null) {
            this.path = "/";
        }
    }

    /** Soft-delete lifecycle. Stored as its name (see {@code media_folders.status VARCHAR(20)}). */
    public enum Status {
        ACTIVE,
        TRASHED
    }
}
