package com.kienhee.blog;

import com.kienhee.blog.service.impl.WebpDimensions;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Test;

import java.nio.charset.StandardCharsets;

import static org.junit.jupiter.api.Assertions.*;

/** WebP header parsing (the JDK cannot decode WebP, so dimensions come from the header). */
@DisplayName("WebP dimensions")
class WebpDimensionsTests {

    /** RIFF/WEBP container with a VP8L (lossless) header — the layout Chrome's canvas encoder produces for lossless. */
    static byte[] vp8l(int width, int height) {
        byte[] b = container("VP8L");
        b[20] = 0x2F;
        int bits = (width - 1) | ((height - 1) << 14);
        b[21] = (byte) bits;
        b[22] = (byte) (bits >>> 8);
        b[23] = (byte) (bits >>> 16);
        b[24] = (byte) (bits >>> 24);
        return b;
    }

    static byte[] vp8(int width, int height) {
        byte[] b = container("VP8 ");
        b[23] = (byte) 0x9D;
        b[24] = 0x01;
        b[25] = 0x2A;
        b[26] = (byte) width;
        b[27] = (byte) (width >>> 8);
        b[28] = (byte) height;
        b[29] = (byte) (height >>> 8);
        return b;
    }

    static byte[] vp8x(int width, int height) {
        byte[] b = container("VP8X");
        int w = width - 1;
        int h = height - 1;
        b[24] = (byte) w;
        b[25] = (byte) (w >>> 8);
        b[26] = (byte) (w >>> 16);
        b[27] = (byte) h;
        b[28] = (byte) (h >>> 8);
        b[29] = (byte) (h >>> 16);
        return b;
    }

    private static byte[] container(String chunk) {
        byte[] b = new byte[40];
        System.arraycopy("RIFF".getBytes(StandardCharsets.US_ASCII), 0, b, 0, 4);
        b[4] = 32; // RIFF size (not checked)
        System.arraycopy("WEBP".getBytes(StandardCharsets.US_ASCII), 0, b, 8, 4);
        System.arraycopy(chunk.getBytes(StandardCharsets.US_ASCII), 0, b, 12, 4);
        b[16] = 20; // chunk size (not checked)
        return b;
    }

    @Test
    @DisplayName("reads lossy, lossless and extended headers")
    void readsAllLayouts() {
        assertEquals(new WebpDimensions.Size(960, 540), WebpDimensions.read(vp8(960, 540)));
        assertEquals(new WebpDimensions.Size(1200, 900), WebpDimensions.read(vp8l(1200, 900)));
        assertEquals(new WebpDimensions.Size(16383, 1), WebpDimensions.read(vp8l(16383, 1)));
        assertEquals(new WebpDimensions.Size(4000, 3000), WebpDimensions.read(vp8x(4000, 3000)));
    }

    @Test
    @DisplayName("returns null for anything that is not a WebP header")
    void rejectsGarbage() {
        assertNull(WebpDimensions.read(null));
        assertNull(WebpDimensions.read(new byte[10]));
        assertNull(WebpDimensions.read("definitely not a webp image at all, no".getBytes()));
        byte[] badSignature = vp8l(10, 10);
        badSignature[20] = 0;
        assertNull(WebpDimensions.read(badSignature));
        byte[] unknownChunk = vp8l(10, 10);
        unknownChunk[15] = 'Z';
        assertNull(WebpDimensions.read(unknownChunk));
    }
}
