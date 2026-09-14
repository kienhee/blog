package com.kienhee.blog.storage;

import java.util.ArrayList;
import java.util.List;
import java.util.Objects;

/**
 * A path <b>relative to the storage root</b> ({@code uploads/}), e.g.
 * {@code anh-du-lich/bien/hoang-hon.jpg}.
 *
 * <p>Invariants enforced at construction (see the filesystem-mirror contract, "Bảo mật"):</p>
 * <ul>
 *   <li>always {@code /}-separated, never {@code \\} (a Windows-authored value is normalised);</li>
 *   <li>never absolute — no leading {@code /} and no drive letter;</li>
 *   <li>never contains a {@code ..} segment, so a path can never escape the root <em>before</em>
 *       it even reaches the filesystem layer;</li>
 *   <li>no empty or {@code .} segments (collapsed), no NUL characters.</li>
 * </ul>
 *
 * <p>The empty path ({@link #root()}) denotes the storage root itself and is the only value whose
 * {@link #toString()} is {@code ""}. It is a legal argument to directory operations
 * ({@code listDirectory}, {@code createDirectory}) and an illegal one to file operations —
 * enforcing that is the storage implementation's job, not this value object's.</p>
 *
 * <p>Immutable and safe to use as a map key.</p>
 */
public final class StoragePath {

    private static final StoragePath ROOT = new StoragePath("");

    /** Normalised, {@code /}-separated, never starting or ending with a separator. */
    private final String value;

    private StoragePath(String value) {
        this.value = value;
    }

    /** @return the path denoting the storage root itself (empty string). */
    public static StoragePath root() {
        return ROOT;
    }

    /**
     * Parses a relative path.
     *
     * @param raw {@code null} or blank yields {@link #root()}; {@code \\} is treated as a separator
     * @throws StorageException if the path is absolute, contains {@code ..}, or contains NUL
     */
    public static StoragePath of(String raw) {
        if (raw == null || raw.isBlank()) {
            return ROOT;
        }
        String unified = raw.replace('\\', '/');
        if (unified.indexOf('\0') >= 0) {
            throw new StorageException("Storage path contains a NUL character");
        }
        if (unified.startsWith("/")) {
            throw new StorageException("Storage path must be relative, got: " + raw);
        }
        // Windows drive letter / UNC style absolutes, e.g. "C:/x" or "C:x".
        if (unified.length() >= 2 && unified.charAt(1) == ':') {
            throw new StorageException("Storage path must be relative, got: " + raw);
        }
        List<String> segments = new ArrayList<>();
        for (String segment : unified.split("/")) {
            if (segment.isEmpty() || ".".equals(segment)) {
                continue;
            }
            if ("..".equals(segment)) {
                throw new StorageException("Storage path must not contain '..', got: " + raw);
            }
            segments.add(segment);
        }
        if (segments.isEmpty()) {
            return ROOT;
        }
        return new StoragePath(String.join("/", segments));
    }

    /**
     * Builds a path from already-sanitised segments (see {@link FilenameSanitizer}). Each segment
     * is still validated, so this is not a way around the {@code ..} check.
     */
    public static StoragePath ofSegments(String... segments) {
        return of(String.join("/", segments));
    }

    /** @return {@code true} for the storage root itself. */
    public boolean isRoot() {
        return value.isEmpty();
    }

    /** @return the last segment ({@code hoang-hon.jpg}), or {@code ""} for the root. */
    public String filename() {
        int slash = value.lastIndexOf('/');
        return slash < 0 ? value : value.substring(slash + 1);
    }

    /** @return the containing directory; the root's parent is the root itself. */
    public StoragePath parent() {
        int slash = value.lastIndexOf('/');
        if (slash < 0) {
            return ROOT;
        }
        return new StoragePath(value.substring(0, slash));
    }

    /**
     * @param child one or more further segments, {@code /}-separated
     * @throws StorageException if {@code child} is absolute or contains {@code ..}
     */
    public StoragePath resolve(String child) {
        StoragePath relative = of(child);
        if (relative.isRoot()) {
            return this;
        }
        if (isRoot()) {
            return relative;
        }
        return new StoragePath(value + "/" + relative.value);
    }

    /**
     * @return {@code true} if {@code other} is this path or lies underneath it. Compares whole
     * segments, so {@code a/b} is <em>not</em> an ancestor of {@code a/bc}.
     */
    public boolean isAncestorOf(StoragePath other) {
        Objects.requireNonNull(other, "other");
        if (isRoot()) {
            return true;
        }
        return other.value.equals(value) || other.value.startsWith(value + "/");
    }

    /** @return the number of segments; {@code 0} for the root. */
    public int depth() {
        return isRoot() ? 0 : (int) value.chars().filter(c -> c == '/').count() + 1;
    }

    @Override
    public String toString() {
        return value;
    }

    @Override
    public boolean equals(Object o) {
        if (this == o) {
            return true;
        }
        return (o instanceof StoragePath other) && value.equals(other.value);
    }

    @Override
    public int hashCode() {
        return value.hashCode();
    }
}
