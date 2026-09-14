package com.kienhee.blog.storage;

import java.io.InputStream;
import java.util.List;

/**
 * A {@link StorageAdapter} that additionally supports resumable/chunked uploads.
 *
 * <p><b>Why this is a separate interface (architecture A.2 / P2).</b> S3 has a native Multipart
 * Upload API: parts are uploaded straight to S3 and assembled there. If chunked upload were
 * built on top of plain {@link StorageAdapter#put} only, the app server would have to buffer
 * every chunk on local disk and then re-upload the merged file — double the bandwidth and double
 * the disk. Declaring the seam now means the P2 chunked-upload feature can be written against
 * this interface and gain native S3 behaviour for free later.</p>
 *
 * <p>At P0 nothing implements this: it exists so callers and schema (upload sessions) can be
 * designed around it. Code that needs chunking should inject {@link StorageAdapter} and check
 * {@code instanceof MultipartStorageAdapter}, falling back to a single {@code put} otherwise.</p>
 */
public interface MultipartStorageAdapter extends StorageAdapter {

    /**
     * Begins a multipart upload for {@code key}.
     *
     * @param contentType validated content type of the final object
     * @return provider upload id, to be persisted on the upload session row
     */
    String initMultipart(String key, String contentType);

    /**
     * Uploads a single part.
     *
     * @param uploadId  id returned by {@link #initMultipart}
     * @param partNumber 1-based part index; parts may arrive out of order
     * @param in        part bytes; the caller owns and closes the stream
     * @param sizeBytes part size in bytes
     * @return an opaque part tag (S3 ETag) that must be passed back to {@link #completeMultipart}
     */
    String uploadPart(String key, String uploadId, int partNumber, InputStream in, long sizeBytes);

    /**
     * Assembles previously uploaded parts into the final object at {@code key}.
     *
     * @param partTags part tags in ascending part-number order, as returned by {@link #uploadPart}
     * @return metadata of the assembled object
     */
    StoredObject completeMultipart(String key, String uploadId, List<String> partTags);

    /**
     * Discards an in-flight multipart upload and any parts already stored.
     * Must be idempotent so an expired-session sweeper can retry safely.
     */
    void abortMultipart(String key, String uploadId);
}
