package com.kienhee.blog.service;

import org.springframework.stereotype.Component;

import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.security.DigestInputStream;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;

/**
 * Tính SHA-256 cho nội dung file, trả về chuỗi hex thường 64 ký tự — khớp đúng
 * kiểu cột {@code CHAR(64)} của {@code storage_blob.sha256} và {@code media.sha256}.
 *
 * <p>Component thuần, không phụ thuộc DB, không giữ trạng thái nào giữa các lần gọi.
 *
 * <p><b>Lưu ý về thread-safety:</b> {@link MessageDigest} KHÔNG thread-safe, nên mỗi
 * lần gọi đều tạo instance mới thay vì cache vào field — bean này là singleton và
 * sẽ bị nhiều request upload gọi đồng thời.
 *
 * <p>Theo C10.4 của docs/media-module-architecture.md, hash phải được tính
 * <b>ngay trong lúc stream</b> file lên storage (một lượt đọc duy nhất), không
 * được ghi file xong rồi đọc lại lần hai. Dùng {@link #wrap(InputStream)} cho việc đó.
 */
@Component
public class FileHasher {

    public static final String ALGORITHM = "SHA-256";

    /** Độ dài chuỗi hex của SHA-256 — khớp CHAR(64). */
    public static final int HEX_LENGTH = 64;

    private static final char[] HEX_DIGITS = "0123456789abcdef".toCharArray();

    /**
     * Tính SHA-256 của một mảng byte đã nằm sẵn trong bộ nhớ.
     * Chỉ dùng cho dữ liệu nhỏ (đã buffer); file lớn phải đi đường stream.
     *
     * @param content nội dung cần hash (không null; mảng rỗng là hợp lệ)
     * @return chuỗi hex thường 64 ký tự
     */
    public String hash(byte[] content) {
        if (content == null) {
            throw new IllegalArgumentException("content must not be null");
        }
        return toHex(newDigest().digest(content));
    }

    /**
     * Bọc một {@link InputStream} bằng {@link DigestInputStream} để hash được tính
     * dần theo từng byte được đọc ra — nghĩa là caller copy stream sang storage như
     * bình thường, và khi copy xong thì hash đã có sẵn, không phải đọc file lần hai.
     *
     * <p>Cách dùng:
     * <pre>{@code
     * try (HashingStream hs = fileHasher.wrap(multipartFile.getInputStream())) {
     *     storageAdapter.write(storageKey, hs.getStream()); // đọc hết stream
     *     String sha256 = hs.digestHex();                   // gọi SAU khi đọc xong
     * }
     * }</pre>
     *
     * @param source stream nguồn (không null)
     * @return wrapper mang theo stream đã bọc và hàm lấy digest
     */
    public HashingStream wrap(InputStream source) {
        if (source == null) {
            throw new IllegalArgumentException("source must not be null");
        }
        return new HashingStream(new DigestInputStream(source, newDigest()));
    }

    /**
     * Tiện ích: đọc cạn stream và trả về hash. Dùng khi caller không cần ghi nội dung
     * đi đâu cả (ví dụ endpoint check-duplicate phía server). Stream KHÔNG bị đóng —
     * quyền đóng thuộc về caller.
     *
     * @param source stream nguồn
     * @return chuỗi hex thường 64 ký tự
     * @throws IOException nếu đọc stream lỗi
     */
    public String hash(InputStream source) throws IOException {
        try (HashingStream hs = wrap(source)) {
            byte[] buffer = new byte[8192];
            while (hs.getStream().read(buffer) != -1) {
                // DigestInputStream tự cập nhật digest theo từng lần read
            }
            return hs.digestHex();
        }
    }

    /**
     * Copy toàn bộ {@code source} sang {@code target} đúng MỘT lượt đọc, đồng thời
     * tính SHA-256 của dữ liệu đã copy. Không đóng stream nào — caller quản lý vòng đời.
     *
     * @return kết quả gồm hash hex và số byte đã ghi
     */
    public HashedCopyResult copyAndHash(InputStream source, OutputStream target) throws IOException {
        if (source == null || target == null) {
            throw new IllegalArgumentException("source and target must not be null");
        }
        MessageDigest digest = newDigest();
        byte[] buffer = new byte[8192];
        long total = 0L;
        int read;
        while ((read = source.read(buffer)) != -1) {
            digest.update(buffer, 0, read);
            target.write(buffer, 0, read);
            total += read;
        }
        target.flush();
        return new HashedCopyResult(toHex(digest.digest()), total);
    }

    /** Chuyển mảng byte digest sang chuỗi hex thường. */
    public static String toHex(byte[] digest) {
        char[] out = new char[digest.length * 2];
        for (int i = 0; i < digest.length; i++) {
            int b = digest[i] & 0xFF;
            out[i * 2] = HEX_DIGITS[b >>> 4];
            out[i * 2 + 1] = HEX_DIGITS[b & 0x0F];
        }
        return new String(out);
    }

    /**
     * Instance mới mỗi lần gọi — MessageDigest không thread-safe, tuyệt đối không
     * cache vào field của bean singleton này.
     */
    private static MessageDigest newDigest() {
        try {
            return MessageDigest.getInstance(ALGORITHM);
        } catch (NoSuchAlgorithmException e) {
            // SHA-256 là thuật toán bắt buộc có trong mọi JVM -> không bao giờ xảy ra
            throw new IllegalStateException("SHA-256 not available in this JVM", e);
        }
    }

    /**
     * Stream đã được bọc digest. Đọc hết {@link #getStream()} rồi mới gọi
     * {@link #digestHex()}; gọi sớm sẽ ra hash của phần đã đọc, không phải cả file.
     */
    public static final class HashingStream implements AutoCloseable {

        private final DigestInputStream stream;
        private String cachedHex;

        private HashingStream(DigestInputStream stream) {
            this.stream = stream;
        }

        /** Stream để caller đọc/copy như stream gốc. */
        public InputStream getStream() {
            return stream;
        }

        /**
         * Chốt và trả về hash của toàn bộ dữ liệu đã đọc qua stream này.
         * Gọi nhiều lần trả cùng một giá trị (digest chỉ được chốt một lần).
         */
        public String digestHex() {
            if (cachedHex == null) {
                cachedHex = toHex(stream.getMessageDigest().digest());
            }
            return cachedHex;
        }

        @Override
        public void close() throws IOException {
            stream.close();
        }
    }

    /** Kết quả của {@link #copyAndHash(InputStream, OutputStream)}. */
    public record HashedCopyResult(String sha256, long bytesWritten) {
    }
}
