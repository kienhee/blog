package com.kienhee.blog.service.validation;

import java.util.HashSet;
import java.util.Locale;
import java.util.Map;
import java.util.Set;

/**
 * Single source of truth for which MIME types the media library accepts and what
 * their file signatures look like. Lifted verbatim from {@code MediaServiceImpl}
 * so the validation chain and the service can never drift apart.
 */
public final class MediaTypeCatalog {

    private MediaTypeCatalog() {
    }

    public static final Set<String> ALLOWED_IMAGE_TYPES = Set.of(
            "image/jpeg", "image/png", "image/webp", "image/gif", "image/svg+xml"
    );

    public static final Set<String> ALLOWED_DOCUMENT_TYPES = Set.of(
            "application/pdf",
            "application/msword",
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            "application/vnd.ms-excel",
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "application/zip",
            "text/plain"
    );

    public static final Set<String> ALLOWED_CONTENT_TYPES;

    static {
        Set<String> all = new HashSet<>(ALLOWED_IMAGE_TYPES);
        all.addAll(ALLOWED_DOCUMENT_TYPES);
        ALLOWED_CONTENT_TYPES = Set.copyOf(all);
    }

    /** 10MB — unchanged from the original inline limit. */
    public static final long MAX_FILE_SIZE_BYTES = 10L * 1024 * 1024;

    public static final byte[] ZIP_MAGIC = {0x50, 0x4B, 0x03, 0x04};

    public static final byte[] OLE_MAGIC = {
            (byte) 0xD0, (byte) 0xCF, 0x11, (byte) 0xE0, (byte) 0xA1, (byte) 0xB1, 0x1A, (byte) 0xE1
    };

    /**
     * Fixed leading signatures per content type. Types absent from this map either
     * have no fixed prefix and are handled specially by {@code MagicByteValidator}
     * (WEBP: RIFF....WEBP; SVG: text sniff for "&lt;svg"; text/plain: trusted) or are
     * trusted outright, exactly as the original service did.
     */
    public static final Map<String, byte[]> MAGIC_BYTES = Map.ofEntries(
            Map.entry("image/jpeg", new byte[]{(byte) 0xFF, (byte) 0xD8, (byte) 0xFF}),
            Map.entry("image/png", new byte[]{(byte) 0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A}),
            Map.entry("image/gif", new byte[]{0x47, 0x49, 0x46, 0x38}),
            Map.entry("application/pdf", new byte[]{0x25, 0x50, 0x44, 0x46}),
            Map.entry("application/zip", ZIP_MAGIC),
            Map.entry("application/vnd.openxmlformats-officedocument.wordprocessingml.document", ZIP_MAGIC),
            Map.entry("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", ZIP_MAGIC),
            Map.entry("application/msword", OLE_MAGIC),
            Map.entry("application/vnd.ms-excel", OLE_MAGIC)
    );

    /** Number of leading bytes the SVG text sniff inspects. */
    public static final int SVG_SNIFF_LENGTH = 1000;

    public static boolean isAllowed(String contentType) {
        return contentType != null && ALLOWED_CONTENT_TYPES.contains(normalize(contentType));
    }

    /**
     * Same rule the service uses everywhere it splits images from documents:
     * a lowercased content type starting with {@code image/}.
     */
    public static boolean isImage(String contentType) {
        return contentType != null && normalize(contentType).startsWith("image/");
    }

    /** True for images ImageIO can decode — i.e. every allowed image except SVG. */
    public static boolean isRasterImage(String contentType) {
        return isImage(contentType) && !"image/svg+xml".equals(normalize(contentType));
    }

    public static boolean isDocument(String contentType) {
        return contentType != null && ALLOWED_DOCUMENT_TYPES.contains(normalize(contentType));
    }

    public static String normalize(String contentType) {
        return contentType == null ? null : contentType.toLowerCase(Locale.ROOT);
    }

    /** Default file extension for a content type, or {@code ""} when unknown. */
    public static String defaultExtension(String contentType) {
        if (contentType == null) {
            return "";
        }
        return switch (normalize(contentType)) {
            case "image/jpeg" -> ".jpg";
            case "image/png" -> ".png";
            case "image/webp" -> ".webp";
            case "image/gif" -> ".gif";
            case "image/svg+xml" -> ".svg";
            case "application/pdf" -> ".pdf";
            case "application/msword" -> ".doc";
            case "application/vnd.openxmlformats-officedocument.wordprocessingml.document" -> ".docx";
            case "application/vnd.ms-excel" -> ".xls";
            case "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" -> ".xlsx";
            case "application/zip" -> ".zip";
            case "text/plain" -> ".txt";
            default -> "";
        };
    }
}
