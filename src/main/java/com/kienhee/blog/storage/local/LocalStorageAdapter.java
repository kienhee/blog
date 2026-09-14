package com.kienhee.blog.storage.local;

import com.kienhee.blog.config.UploadProperties;
import com.kienhee.blog.storage.StorageAdapter;
import com.kienhee.blog.storage.StorageException;
import com.kienhee.blog.storage.StoredObject;
import lombok.extern.slf4j.Slf4j;
import org.springframework.boot.autoconfigure.condition.ConditionalOnProperty;
import org.springframework.stereotype.Component;

import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.nio.file.AtomicMoveNotSupportedException;
import java.nio.file.Files;
import java.nio.file.InvalidPathException;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.nio.file.StandardCopyOption;
import java.time.Duration;
import java.time.Instant;

/**
 * {@link StorageAdapter} backed by the local filesystem, rooted at {@code app.upload.dir}
 * (see {@link UploadProperties}). Files under that root are already served by the
 * {@code /uploads/**} resource handler, so {@link #publicUrl(String)} simply maps a key onto
 * that path.
 *
 * <p>Registered as the default provider; a future S3 adapter would declare
 * {@code app.storage.provider=S3} and this bean would back off.</p>
 */
@Slf4j
@Component
@ConditionalOnProperty(name = "app.storage.provider", havingValue = "LOCAL", matchIfMissing = true)
public class LocalStorageAdapter implements StorageAdapter {

    public static final String PROVIDER_CODE = "LOCAL";

    /** URL prefix served by {@code WebConfig}'s resource handler for the upload directory. */
    private static final String URL_PREFIX = "/uploads/";

    private static final String TEMP_SUFFIX = ".part";

    private final Path root;

    public LocalStorageAdapter(UploadProperties uploadProperties) {
        this.root = Paths.get(uploadProperties.getDir()).toAbsolutePath().normalize();
    }

    @Override
    public StoredObject put(String key, InputStream in, long sizeBytes, String contentType) {
        Path target = resolveSafely(key);
        Path tmp;
        long written;
        try {
            Files.createDirectories(target.getParent());
            // Write to a sibling temp file first, then move it into place. A reader therefore
            // never observes a half-written file at the real key, and a crash mid-upload leaves
            // only a *.part file for the sweeper instead of a corrupt "successful" object.
            tmp = Files.createTempFile(target.getParent(), target.getFileName().toString(), TEMP_SUFFIX);
        } catch (IOException e) {
            throw new StorageException("Cannot prepare storage location for key: " + key, e);
        }

        try {
            try (OutputStream out = Files.newOutputStream(tmp)) {
                written = in.transferTo(out);
            }
            moveIntoPlace(tmp, target);
        } catch (IOException e) {
            deleteQuietly(tmp);
            throw new StorageException("Failed to write object for key: " + key, e);
        } catch (RuntimeException e) {
            deleteQuietly(tmp);
            throw e;
        }

        return StoredObject.builder()
                .key(key)
                .sizeBytes(written)
                .contentType(contentType)
                .provider(PROVIDER_CODE)
                .storedAt(Instant.now())
                .build();
    }

    @Override
    public InputStream get(String key) {
        Path target = resolveSafely(key);
        try {
            return Files.newInputStream(target);
        } catch (IOException e) {
            throw new StorageException("Object not readable for key: " + key, e);
        }
    }

    @Override
    public boolean delete(String key) {
        Path target = resolveSafely(key);
        try {
            // Idempotent: a missing object is a successful no-op, so a ref_count sweeper can retry.
            return Files.deleteIfExists(target);
        } catch (IOException e) {
            throw new StorageException("Failed to delete object for key: " + key, e);
        }
    }

    @Override
    public boolean exists(String key) {
        return Files.isRegularFile(resolveSafely(key));
    }

    /**
     * Local storage has no signing mechanism, so this returns the same non-expiring URL as
     * {@link #publicUrl(String)}: <b>the {@code ttl} argument is ignored and the link does not
     * actually expire.</b> The method exists so callers can be written against a single API and
     * gain real, time-limited links the moment an S3 adapter is dropped in. Do not treat local
     * "presigned" URLs as an access-control mechanism.
     */
    @Override
    public String presignedUrl(String key, Duration ttl) {
        return publicUrl(key);
    }

    @Override
    public String publicUrl(String key) {
        resolveSafely(key);
        return URL_PREFIX + key;
    }

    @Override
    public String providerCode() {
        return PROVIDER_CODE;
    }

    /**
     * Resolves {@code key} against the storage root and proves the result stays inside it.
     *
     * <p>Hard security requirement: keys are treated as untrusted even though they are
     * server-generated, because a corrupted or hand-edited {@code storage_blob.storage_key} row
     * must never be able to read or overwrite files outside the upload directory
     * (e.g. {@code ../../application.yaml}).</p>
     */
    private Path resolveSafely(String key) {
        if (key == null || key.isBlank()) {
            throw new StorageException("Storage key must not be blank");
        }
        if (key.indexOf('\0') >= 0 || key.indexOf('\\') >= 0) {
            throw new StorageException("Illegal storage key: " + key);
        }
        Path resolved;
        try {
            Path relative = Paths.get(key);
            if (relative.isAbsolute() || relative.getRoot() != null) {
                throw new StorageException("Storage key must be relative: " + key);
            }
            resolved = root.resolve(relative).normalize();
        } catch (InvalidPathException e) {
            throw new StorageException("Illegal storage key: " + key, e);
        }
        if (!resolved.startsWith(root) || resolved.equals(root)) {
            throw new StorageException("Storage key escapes the storage root: " + key);
        }
        return resolved;
    }

    private void moveIntoPlace(Path tmp, Path target) throws IOException {
        try {
            Files.move(tmp, target, StandardCopyOption.REPLACE_EXISTING, StandardCopyOption.ATOMIC_MOVE);
        } catch (AtomicMoveNotSupportedException e) {
            // Some filesystems (certain network/container mounts) reject ATOMIC_MOVE even for a
            // same-directory rename; fall back to a plain replace rather than failing the upload.
            log.debug("Atomic move unsupported for {}, falling back to non-atomic replace", target);
            Files.move(tmp, target, StandardCopyOption.REPLACE_EXISTING);
        }
    }

    private void deleteQuietly(Path path) {
        try {
            Files.deleteIfExists(path);
        } catch (IOException ignored) {
            log.warn("Could not remove temporary upload file {}", path);
        }
    }
}
