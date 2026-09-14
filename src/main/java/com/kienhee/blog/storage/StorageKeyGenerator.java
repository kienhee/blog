package com.kienhee.blog.storage;

import org.springframework.stereotype.Component;

import java.util.Locale;
import java.util.Set;
import java.util.UUID;

/**
 * Generates provider-neutral storage keys.
 *
 * <p><b>Rule 1 — never build a key from user input.</b> The name part of a key is always a
 * freshly generated UUID (hex, no dashes). The original filename is display metadata only and
 * lives in the database; letting it reach the filesystem invites traversal, case-collision,
 * reserved-name (Windows {@code CON}, {@code NUL}) and encoding bugs.</p>
 *
 * <p><b>Rule 2 — shard inside the key, not inside the adapter.</b> The key is
 * {@code <xx>/<yy>/<uuid>.<ext>} where {@code xx}/{@code yy} are the first two pairs of hex
 * characters of the UUID, giving 256 x 256 buckets so no single directory ever holds hundreds of
 * thousands of entries (ext4/NTFS lookups degrade badly there, and so does {@code ls} during
 * ops work). The sharding is part of the key itself rather than a local-adapter detail on
 * purpose: {@code storage_blob.storage_key} is what a future S3 adapter passes verbatim as the
 * object key, so if the adapter invented the directory layout locally, the same row would point
 * at a different path per provider and per-blob migration (C9.2) would break. Hex-prefix
 * sharding is preferred over the {@code yyyy/MM} scheme sketched in C9.1 because upload traffic
 * is bursty — a viral month would still concentrate in one directory — whereas UUID prefixes
 * are uniform by construction.</p>
 */
@Component
public class StorageKeyGenerator {

    /**
     * Extensions allowed to survive into a key. Mirrors the media module's accepted types
     * (images + documents). Anything else is stored without an extension — content type is
     * authoritative and is persisted on {@code storage_blob.content_type} anyway.
     */
    public static final Set<String> ALLOWED_EXTENSIONS = Set.of(
            "jpg", "jpeg", "png", "webp", "gif", "svg",
            "pdf", "doc", "docx", "xls", "xlsx", "zip", "txt"
    );

    /**
     * Generates a new unique key, preserving the whitelisted extension of {@code originalFilename}.
     *
     * @param originalFilename user-supplied filename; used <b>only</b> to read its extension
     * @return a key such as {@code ab/12/ab12cd....jpg}
     */
    public String generate(String originalFilename) {
        return generateWithExtension(extractExtension(originalFilename));
    }

    /**
     * Generates a new unique key with an already-determined extension (may be {@code null}).
     */
    public String generateWithExtension(String extension) {
        String uuid = UUID.randomUUID().toString().replace("-", "");
        String name = (extension == null || extension.isEmpty()) ? uuid : uuid + "." + extension;
        return shardPrefix(uuid) + name;
    }

    /**
     * @return {@code "ab/12/"} for a uuid starting with {@code ab12}
     */
    public String shardPrefix(String uuid) {
        return uuid.substring(0, 2) + "/" + uuid.substring(2, 4) + "/";
    }

    /**
     * Extracts the lower-cased extension of a filename if it is whitelisted, else {@code null}.
     * Only the segment after the last dot of the last path element is considered, so inputs like
     * {@code "../../evil.txt/x.jpg"} cannot smuggle separators into the key.
     */
    public String extractExtension(String originalFilename) {
        if (originalFilename == null) {
            return null;
        }
        String name = originalFilename.replace('\\', '/');
        int slash = name.lastIndexOf('/');
        if (slash >= 0) {
            name = name.substring(slash + 1);
        }
        int dot = name.lastIndexOf('.');
        if (dot < 0 || dot == name.length() - 1) {
            return null;
        }
        String ext = name.substring(dot + 1).toLowerCase(Locale.ROOT);
        return ALLOWED_EXTENSIONS.contains(ext) ? ext : null;
    }
}
