package com.kienhee.blog.service;

import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Nested;
import org.junit.jupiter.api.Test;

import java.io.ByteArrayInputStream;
import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.nio.charset.StandardCharsets;
import java.util.Random;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertThrows;
import static org.junit.jupiter.api.Assertions.assertTrue;

/**
 * Unit test thuần cho {@link FileHasher} — không {@code @SpringBootTest}, không DB.
 */
class FileHasherTests {

    /** SHA-256 của chuỗi rỗng (vector chuẩn NIST). */
    private static final String SHA256_EMPTY =
            "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855";

    /** SHA-256 của "abc" (vector chuẩn NIST FIPS 180-4). */
    private static final String SHA256_ABC =
            "ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad";

    private final FileHasher fileHasher = new FileHasher();

    @Nested
    @DisplayName("hash(byte[])")
    class ByteArrayHashing {

        @Test
        @DisplayName("khớp vector chuẩn của chuỗi rỗng")
        void emptyInputMatchesKnownVector() {
            assertEquals(SHA256_EMPTY, fileHasher.hash(new byte[0]));
        }

        @Test
        @DisplayName("khớp vector chuẩn của \"abc\"")
        void abcMatchesKnownVector() {
            assertEquals(SHA256_ABC, fileHasher.hash("abc".getBytes(StandardCharsets.UTF_8)));
        }

        @Test
        @DisplayName("luôn trả 64 ký tự hex thường (khớp CHAR(64))")
        void alwaysReturnsLowercase64HexChars() {
            String hex = fileHasher.hash("Xin chào".getBytes(StandardCharsets.UTF_8));
            assertEquals(FileHasher.HEX_LENGTH, hex.length());
            assertTrue(hex.matches("[0-9a-f]{64}"), "expected lowercase hex, got: " + hex);
        }

        @Test
        @DisplayName("ném IllegalArgumentException khi content null")
        void rejectsNull() {
            assertThrows(IllegalArgumentException.class, () -> fileHasher.hash((byte[]) null));
        }
    }

    @Nested
    @DisplayName("đường stream")
    class StreamHashing {

        @Test
        @DisplayName("wrap() cho cùng kết quả với hash(byte[]) trên chuỗi rỗng")
        void wrapMatchesEmptyVector() throws IOException {
            assertEquals(SHA256_EMPTY, drain(new byte[0]));
        }

        @Test
        @DisplayName("wrap() cho cùng kết quả với hash(byte[]) trên \"abc\"")
        void wrapMatchesAbcVector() throws IOException {
            assertEquals(SHA256_ABC, drain("abc".getBytes(StandardCharsets.UTF_8)));
        }

        @Test
        @DisplayName("stream và byte[] cho cùng hash trên dữ liệu lớn nhiều buffer")
        void streamAndByteArrayAgreeOnLargePayload() throws IOException {
            byte[] payload = new byte[512 * 1024 + 137]; // cố tình lệch bội số buffer 8KB
            new Random(42).nextBytes(payload);
            assertEquals(fileHasher.hash(payload), drain(payload));
        }

        @Test
        @DisplayName("digestHex() gọi nhiều lần trả cùng giá trị")
        void digestHexIsIdempotent() throws IOException {
            try (FileHasher.HashingStream hs =
                         fileHasher.wrap(new ByteArrayInputStream("abc".getBytes(StandardCharsets.UTF_8)))) {
                hs.getStream().readAllBytes();
                assertEquals(hs.digestHex(), hs.digestHex());
                assertEquals(SHA256_ABC, hs.digestHex());
            }
        }

        @Test
        @DisplayName("hash(InputStream) khớp hash(byte[])")
        void inputStreamOverloadMatchesByteArray() throws IOException {
            byte[] payload = "media module p0".getBytes(StandardCharsets.UTF_8);
            try (InputStream in = new ByteArrayInputStream(payload)) {
                assertEquals(fileHasher.hash(payload), fileHasher.hash(in));
            }
        }

        @Test
        @DisplayName("copyAndHash() ghi đúng nội dung và trả đúng hash + số byte")
        void copyAndHashWritesContentAndReportsHash() throws IOException {
            byte[] payload = "stream once, hash once".getBytes(StandardCharsets.UTF_8);
            ByteArrayOutputStream target = new ByteArrayOutputStream();

            FileHasher.HashedCopyResult result =
                    fileHasher.copyAndHash(new ByteArrayInputStream(payload), target);

            assertEquals(fileHasher.hash(payload), result.sha256());
            assertEquals(payload.length, result.bytesWritten());
            assertEquals(new String(payload, StandardCharsets.UTF_8),
                    target.toString(StandardCharsets.UTF_8));
        }

        private String drain(byte[] payload) throws IOException {
            try (FileHasher.HashingStream hs = fileHasher.wrap(new ByteArrayInputStream(payload))) {
                byte[] buffer = new byte[8192];
                while (hs.getStream().read(buffer) != -1) {
                    // đọc cạn để digest cập nhật hết
                }
                return hs.digestHex();
            }
        }
    }
}
