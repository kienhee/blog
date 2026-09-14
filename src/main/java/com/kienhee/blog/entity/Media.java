package com.kienhee.blog.entity;

import jakarta.persistence.*;
import lombok.*;

import java.time.LocalDateTime;

@Entity
@Table(name = "media")
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class Media {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "original_filename", nullable = false)
    private String originalFilename;

    @Column(name = "stored_filename", nullable = false)
    private String storedFilename;

    /**
     * Path relative to the storage root ({@code uploads/}), {@code /}-separated, e.g.
     * {@code anh-du-lich/bien/hoang-hon.jpg}. Mirrors the folder tree. NULL only for rows that
     * predate V16 and have not been migrated yet; new uploads always set it.
     */
    @Column(name = "storage_path", length = 700)
    private String storagePath;

    /** Stable public URL {@code /media/{id}/{filename}}; never changes when the file moves. */
    @Column(name = "url", nullable = false, length = 500)
    private String url;

    @Column(name = "thumbnail_url", length = 500)
    private String thumbnailUrl;

    @Column(name = "content_type", nullable = false, length = 100)
    private String contentType;

    @Column(name = "size_bytes", nullable = false)
    private long sizeBytes;

    @Column(name = "original_size_bytes")
    private Long originalSizeBytes;

    @Column(name = "width")
    private Integer width;

    @Column(name = "height")
    private Integer height;

    @Column(name = "alt_text")
    private String altText;

    @Column(name = "optimized", nullable = false)
    private boolean optimized;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "uploaded_by", nullable = false)
    private User uploadedBy;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "folder_id")
    private MediaFolder folder;

    /** Physical bytes behind this media. Nullable: rows created before V13 have none. */
    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "blob_id")
    private StorageBlob blob;

    /** Denormalized from the blob for fast dedupe lookups. Nullable for pre-V13 rows. */
    @Column(name = "sha256", columnDefinition = "CHAR(64)")
    private String sha256;

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
    }

    /** Soft-delete lifecycle. Stored as its name (see {@code media.status VARCHAR(20)}). */
    public enum Status {
        ACTIVE,
        TRASHED
    }
}
