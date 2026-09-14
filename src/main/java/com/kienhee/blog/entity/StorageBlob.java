package com.kienhee.blog.entity;

import jakarta.persistence.*;
import lombok.*;

import java.time.LocalDateTime;

/**
 * Physical stored bytes. Since V16 dedupe is off: every {@link Media} row owns its own blob and
 * file, so {@code refCount} is always 1 while the row exists. {@code sha256} is still recorded
 * (non-unique) to detect duplicates; {@code storageKey} equals {@code media.storage_path}.
 */
@Entity
@Table(name = "storage_blob")
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class StorageBlob {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "sha256", nullable = false, columnDefinition = "CHAR(64)")
    private String sha256;

    /**
     * Same value as {@code media.storage_path}. UNIQUE: moving/renaming a folder must rewrite it
     * together with the media path in the same transaction.
     */
    @Column(name = "storage_key", nullable = false, unique = true, length = 700)
    private String storageKey;

    @Column(name = "storage_provider", nullable = false, length = 30)
    @Builder.Default
    private String storageProvider = "LOCAL";

    @Column(name = "size_bytes", nullable = false)
    private long sizeBytes;

    @Column(name = "content_type", nullable = false, length = 100)
    private String contentType;

    /** Maintained through atomic SQL updates, never read-modify-write in Java. */
    @Column(name = "ref_count", nullable = false)
    private int refCount;

    @Column(name = "created_at", updatable = false)
    private LocalDateTime createdAt;

    @PrePersist
    protected void onCreate() {
        this.createdAt = LocalDateTime.now();
    }
}
