package com.kienhee.blog.storage;

/**
 * Thrown when a storage operation fails for a reason the caller cannot recover from
 * at the storage layer: I/O failure, disk full, or a rejected (unsafe) storage key.
 *
 * <p>Deliberately a {@link RuntimeException} so the {@link StorageAdapter} contract stays
 * provider-agnostic: a future S3 adapter throws SDK exceptions, a local adapter throws
 * {@code IOException}; both are normalised into this type so the service layer never has
 * to know which provider is active.</p>
 *
 * <p>Note this is intentionally <em>not</em> an {@code IllegalArgumentException}: the repo
 * convention maps {@code IllegalArgumentException} to a user-facing form/business-rule error,
 * while a storage failure is an infrastructure fault (HTTP 500 / 507), not a bad form field.</p>
 */
public class StorageException extends RuntimeException {

    public StorageException(String message) {
        super(message);
    }

    public StorageException(String message, Throwable cause) {
        super(message, cause);
    }
}
