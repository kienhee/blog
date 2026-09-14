package com.kienhee.blog.storage;

import org.springframework.stereotype.Component;

import java.text.Normalizer;
import java.util.Locale;
import java.util.Set;
import java.util.function.Predicate;
import java.util.regex.Pattern;

/**
 * Turns a user-supplied filename into one that is safe to place on disk, on <b>any</b> OS.
 *
 * <p>Since the filesystem-mirror change, real filenames (not UUIDs) reach the filesystem, so this
 * class is the module's primary attack surface. It defends against, in order:</p>
 * <ol>
 *   <li>path traversal — every {@code /}, {@code \\} and {@code ..} is stripped, only the last
 *       path element survives;</li>
 *   <li>control characters and NUL (truncation tricks against native syscalls);</li>
 *   <li>characters Windows forbids in a filename: {@code < > : " | ? *};</li>
 *   <li>Windows device names ({@code CON}, {@code NUL}, {@code COM1}...), which are reserved even
 *       with an extension ({@code CON.txt});</li>
 *   <li>trailing dots/spaces, which Windows silently trims — meaning {@code "a.txt "} and
 *       {@code "a.txt"} would collide after the collision check had already passed;</li>
 *   <li>over-long names, truncated on the <em>base</em> so the extension is preserved.</li>
 * </ol>
 *
 * <p>Unicode is <b>kept</b>, not transliterated: Vietnamese filenames must survive. They are
 * normalised to NFC so that macOS (NFD by default) and Windows/Linux agree on the bytes — without
 * this, {@code "cá.jpg"} written on one OS is a different filename on another and lookups miss.</p>
 */
@Component
public class FilenameSanitizer {

    /** Fallback used when nothing usable is left after sanitising. */
    public static final String FALLBACK_NAME = "file";

    /** Max length of the produced filename, extension included. */
    public static final int MAX_LENGTH = 150;

    /** Windows device names, reserved regardless of extension and case. */
    private static final Set<String> RESERVED_NAMES = Set.of(
            "CON", "PRN", "AUX", "NUL",
            "COM1", "COM2", "COM3", "COM4", "COM5", "COM6", "COM7", "COM8", "COM9",
            "LPT1", "LPT2", "LPT3", "LPT4", "LPT5", "LPT6", "LPT7", "LPT8", "LPT9");

    /** Characters illegal on Windows plus the separators; control chars handled separately. */
    private static final Pattern ILLEGAL = Pattern.compile("[<>:\"|?*\\\\/]");

    /**
     * Sanitises {@code rawName} into a single safe filename.
     *
     * @param rawName anything the client sent (may include a full path, may be {@code null})
     * @return a non-empty name, at most {@link #MAX_LENGTH} chars, NFC-normalised, never a
     * reserved Windows name, never starting or ending with a dot or space
     */
    public String sanitize(String rawName) {
        if (rawName == null) {
            return FALLBACK_NAME;
        }
        // 1. Strip any directory part: keep only the last element under either separator.
        String name = rawName.replace('\\', '/');
        int slash = name.lastIndexOf('/');
        if (slash >= 0) {
            name = name.substring(slash + 1);
        }

        // 2. Normalise to NFC before any length work, so a decomposed "ế" counts as one char.
        name = Normalizer.normalize(name, Normalizer.Form.NFC);

        // 3. Drop control characters (includes NUL, CR, LF, TAB) and illegal Windows characters.
        StringBuilder cleaned = new StringBuilder(name.length());
        for (int i = 0; i < name.length(); i++) {
            char c = name.charAt(i);
            if (c < 0x20 || c == 0x7F) {
                continue;
            }
            cleaned.append(c);
        }
        name = ILLEGAL.matcher(cleaned).replaceAll("");

        // 4. Collapse whitespace runs and trim. A name of only dots/spaces dies here.
        name = name.replaceAll("\\s+", " ").trim();
        // Leading dots would create a hidden file (and ".."/"." are path aliases).
        name = name.replaceAll("^\\.+", "").trim();
        if (name.isEmpty()) {
            return FALLBACK_NAME;
        }

        String base = baseOf(name);
        String extension = extensionOf(name);

        // 5. Windows reserved device names, with or without extension.
        if (RESERVED_NAMES.contains(base.toUpperCase(Locale.ROOT))) {
            base = "_" + base;
        }

        // 6. Windows trims trailing dots and spaces; do it ourselves so the stored name is the
        //    name that will actually exist on disk.
        base = stripTrailingDotsAndSpaces(base);
        extension = stripTrailingDotsAndSpaces(extension);
        if (base.isEmpty()) {
            base = FALLBACK_NAME;
        }

        // 7. Length cap: truncate the base, never the extension.
        String suffix = extension.isEmpty() ? "" : "." + extension;
        if (suffix.length() >= MAX_LENGTH) {
            // Pathological extension; drop it rather than losing the whole name.
            suffix = "";
        }
        int maxBase = MAX_LENGTH - suffix.length();
        if (base.length() > maxBase) {
            base = stripTrailingDotsAndSpaces(base.substring(0, maxBase));
            if (base.isEmpty()) {
                base = FALLBACK_NAME;
            }
        }
        return base + suffix;
    }

    /**
     * Sanitises {@code rawName} and, if that name is already taken, appends a Windows-style
     * counter: {@code anh.jpg} -> {@code anh (2).jpg} -> {@code anh (3).jpg}.
     *
     * <p>Takes a predicate rather than touching the disk so callers can test against a
     * directory listing, a DB uniqueness query, or a real filesystem, and so this is unit
     * testable without I/O.</p>
     *
     * @param rawName user-supplied name
     * @param taken   returns {@code true} if a candidate name already exists in the target
     *                directory. Must be side-effect free; it is called repeatedly.
     * @return a sanitised name for which {@code taken} returned {@code false}
     * @throws StorageException if no free name is found after 10000 attempts
     */
    public String sanitizeUnique(String rawName, Predicate<String> taken) {
        String candidate = sanitize(rawName);
        if (!taken.test(candidate)) {
            return candidate;
        }
        String base = baseOf(candidate);
        String extension = extensionOf(candidate);
        String suffix = extension.isEmpty() ? "" : "." + extension;

        for (int counter = 2; counter <= 10_000; counter++) {
            String marker = " (" + counter + ")";
            String trimmedBase = base;
            // Keep the whole thing within MAX_LENGTH by shrinking the base, not the marker.
            int maxBase = MAX_LENGTH - suffix.length() - marker.length();
            if (maxBase < 1) {
                maxBase = 1;
            }
            if (trimmedBase.length() > maxBase) {
                trimmedBase = stripTrailingDotsAndSpaces(trimmedBase.substring(0, maxBase));
                if (trimmedBase.isEmpty()) {
                    trimmedBase = FALLBACK_NAME;
                }
            }
            String next = trimmedBase + marker + suffix;
            if (!taken.test(next)) {
                return next;
            }
        }
        throw new StorageException("Could not find a free filename for: " + rawName);
    }

    /** @return the part before the last dot, or the whole name if it has no usable extension. */
    private String baseOf(String name) {
        int dot = name.lastIndexOf('.');
        return (dot <= 0 || dot == name.length() - 1) ? name : name.substring(0, dot);
    }

    /** @return the extension without the dot, or {@code ""}. */
    private String extensionOf(String name) {
        int dot = name.lastIndexOf('.');
        return (dot <= 0 || dot == name.length() - 1) ? "" : name.substring(dot + 1);
    }

    private String stripTrailingDotsAndSpaces(String s) {
        int end = s.length();
        while (end > 0) {
            char c = s.charAt(end - 1);
            if (c == '.' || c == ' ') {
                end--;
            } else {
                break;
            }
        }
        return s.substring(0, end);
    }
}
