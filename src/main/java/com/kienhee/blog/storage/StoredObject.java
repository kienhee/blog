package com.kienhee.blog.storage;

import lombok.Builder;
import lombok.Getter;

import java.time.Instant;

/**
 * Immutable description of an object after it has been successfully persisted by a
 * {@link StorageAdapter}. Contains only provider-neutral facts, so the service layer can
 * populate {@code storage_blob} rows identically whether the bytes landed on local disk or S3.
 */
@Getter
@Builder
public class StoredObject {

    /** Provider-independent object key, exactly as passed to {@link StorageAdapter#put}. */
    private final String key;

    /** Number of bytes actually written. */
    private final long sizeBytes;

    /** Content type recorded for the object (as declared by the caller after validation). */
    private final String contentType;

    /** Provider that owns the bytes; matches {@code storage_blob.storage_provider} (e.g. {@code LOCAL}, {@code S3}). */
    private final String provider;

    /** When the object was written. */
    private final Instant storedAt;
}
