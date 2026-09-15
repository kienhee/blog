package com.kienhee.blog.service.impl;

/**
 * Reads the pixel size of a WebP image from its header. The JDK's ImageIO has no WebP decoder,
 * so without this WebP uploads have no dimensions and edited WebP images could not be saved.
 *
 * <p>Handles the three bitstream layouts of the RIFF container:
 * <ul>
 *   <li>{@code VP8 } (lossy): 14-bit width/height after the {@code 9d 01 2a} start code</li>
 *   <li>{@code VP8L} (lossless): signature {@code 0x2f}, then 14-bit (width-1) and (height-1)</li>
 *   <li>{@code VP8X} (extended: alpha, animation, metadata): 24-bit (width-1) and (height-1)</li>
 * </ul>
 */
public final class WebpDimensions {

    public record Size(int width, int height) {
    }

    private WebpDimensions() {
    }

    /** The image size, or null when the bytes are not a WebP header this understands. */
    public static Size read(byte[] b) {
        if (b == null || b.length < 30 || !ascii(b, 0, "RIFF") || !ascii(b, 8, "WEBP")) {
            return null;
        }
        int width;
        int height;
        if (ascii(b, 12, "VP8 ")) {
            if ((b[23] & 0xFF) != 0x9D || (b[24] & 0xFF) != 0x01 || (b[25] & 0xFF) != 0x2A) {
                return null;
            }
            width = le16(b, 26) & 0x3FFF;
            height = le16(b, 28) & 0x3FFF;
        } else if (ascii(b, 12, "VP8L")) {
            if ((b[20] & 0xFF) != 0x2F) {
                return null;
            }
            int bits = le32(b, 21);
            width = (bits & 0x3FFF) + 1;
            height = ((bits >>> 14) & 0x3FFF) + 1;
        } else if (ascii(b, 12, "VP8X")) {
            width = le24(b, 24) + 1;
            height = le24(b, 27) + 1;
        } else {
            return null;
        }
        return width > 0 && height > 0 ? new Size(width, height) : null;
    }

    private static boolean ascii(byte[] b, int offset, String expected) {
        for (int i = 0; i < expected.length(); i++) {
            if (b[offset + i] != (byte) expected.charAt(i)) {
                return false;
            }
        }
        return true;
    }

    private static int le16(byte[] b, int o) {
        return (b[o] & 0xFF) | (b[o + 1] & 0xFF) << 8;
    }

    private static int le24(byte[] b, int o) {
        return le16(b, o) | (b[o + 2] & 0xFF) << 16;
    }

    private static int le32(byte[] b, int o) {
        return le24(b, o) | (b[o + 3] & 0xFF) << 24;
    }
}
