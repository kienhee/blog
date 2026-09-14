package com.kienhee.blog.service.validation;

import lombok.Builder;
import lombok.Getter;

import java.nio.charset.StandardCharsets;
import java.util.Locale;

/**
 * Everything a {@link FileValidator} may need about a single upload attempt.
 *
 * <p>Immutable and framework-free (no {@code MultipartFile}, no HTTP types) so
 * rules stay unit-testable without Spring. A future rule that needs extra input
 * (quota, scanner verdict, destination folder...) gets a new field here rather
 * than a new parameter on every validator.
 */
@Getter
@Builder
public class UploadValidationContext {

    /** Original client-supplied filename; may be {@code null}. */
    private final String originalFilename;

    /** Content type as declared by the client; may be {@code null}. */
    private final String declaredContentType;

    /** Size in bytes as reported by the upload. */
    private final long sizeBytes;

    /**
     * File bytes — either the whole file or at least its leading header bytes.
     * May be {@code null} when the caller validates before reading the body;
     * the size and whitelist rules do not need it.
     */
    private final byte[] bytes;

    /** Email of the authenticated uploader (used by quota / audit rules). */
    private final String uploaderEmail;

    /** Id of the destination folder, or {@code null} for the root listing. */
    private final Long folderId;

    /** Declared content type lowercased, or {@code null}. Matches the existing service behaviour. */
    public String normalizedContentType() {
        return declaredContentType == null ? null : declaredContentType.toLowerCase(Locale.ROOT);
    }

    public byte[] bytesOrEmpty() {
        return bytes == null ? new byte[0] : bytes;
    }

    /** Lowercased text sniff of the first {@code maxLength} bytes, for text-based formats (SVG). */
    public String headAsText(int maxLength) {
        byte[] data = bytesOrEmpty();
        int length = Math.min(data.length, maxLength);
        return new String(data, 0, length, StandardCharsets.UTF_8).toLowerCase(Locale.ROOT);
    }
}
