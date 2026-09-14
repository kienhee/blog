package com.kienhee.blog.storage;

import java.io.InputStream;
import java.time.Duration;

/**
 * Provider-agnostic binary object store.
 *
 * <p>This is the single seam between the media service layer and the physical location of the
 * bytes. Swapping local disk for S3/GCS must be a matter of publishing a different
 * implementation bean ({@code app.storage.provider=LOCAL|S3}) — no service code changes.
 * Therefore implementations must never leak provider types ({@code Path}, {@code S3Object}, ...)
 * through this interface, and must normalise all failures into {@link StorageException}.</p>
 *
 * <p><b>Keys.</b> A key is an opaque, server-generated, provider-neutral identifier produced by
 * {@link StorageKeyGenerator} (e.g. {@code ab/12/ab12....jpg}). It is never derived from a
 * user-supplied filename. Implementations must treat a key as untrusted input anyway and reject
 * anything that could escape their storage root (see {@code ../} traversal).</p>
 *
 * <p><b>URLs.</b> Callers must always obtain a URL from the adapter rather than building
 * {@code /uploads/...} strings themselves. Use {@link #presignedUrl(String, Duration)} for
 * private content and {@link #publicUrl(String)} for content that is safe to expose forever.
 * Keeping {@code presignedUrl} in the interface from day one is deliberate (architecture A.2):
 * without it, moving to S3 would still force every byte to be streamed through the app server.</p>
 */
public interface StorageAdapter {

    /**
     * Writes an object at {@code key}, replacing any existing object at that key.
     *
     * <p>Implementations must make the write <b>atomic from a reader's point of view</b>: a
     * concurrent {@link #get(String)} either sees the previous object or the complete new one,
     * never a partially written file.</p>
     *
     * @param key         provider-neutral object key, see {@link StorageKeyGenerator}
     * @param in          source stream; the adapter reads it fully but does <b>not</b> close it
     *                    (the caller owns the stream, which may be a multipart part being hashed)
     * @param sizeBytes   expected size in bytes, or a negative value if unknown; used by cloud
     *                    providers that require a content length up front
     * @param contentType validated content type to record with the object
     * @return metadata about the object that was written
     * @throws StorageException on I/O failure, insufficient space, or an unsafe key
     */
    StoredObject put(String key, InputStream in, long sizeBytes, String contentType);

    /**
     * Opens the object at {@code key} for reading. The caller owns and must close the stream.
     *
     * @throws StorageException if the object does not exist or cannot be read
     */
    InputStream get(String key);

    /**
     * Deletes the object at {@code key}.
     *
     * <p><b>Idempotent:</b> deleting a missing key is a no-op and must not throw. This matters
     * because deletion is driven by {@code storage_blob.ref_count} reaching zero and may be
     * retried by a background sweeper.</p>
     *
     * @return {@code true} if an object was actually removed, {@code false} if nothing was there
     * @throws StorageException only if an object exists but cannot be deleted
     */
    boolean delete(String key);

    /**
     * @return {@code true} if an object currently exists at {@code key}
     */
    boolean exists(String key);

    /**
     * Returns a time-limited URL granting direct read access to the object, so clients can
     * download from the storage provider without proxying bytes through the app server.
     *
     * @param key object key
     * @param ttl how long the URL should remain valid; implementations that cannot expire URLs
     *            ignore it (see the local adapter's javadoc)
     * @throws StorageException if a URL cannot be produced
     */
    String presignedUrl(String key, Duration ttl);

    /**
     * Returns a stable, non-expiring URL for content that is intentionally public
     * (e.g. a post cover image). Prefer {@link #presignedUrl(String, Duration)} for anything
     * access-controlled.
     *
     * @throws StorageException if a URL cannot be produced
     */
    String publicUrl(String key);

    /**
     * @return the provider code stored in {@code storage_blob.storage_provider}, e.g. {@code LOCAL}
     */
    String providerCode();
}
