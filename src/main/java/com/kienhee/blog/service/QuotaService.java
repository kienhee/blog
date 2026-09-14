package com.kienhee.blog.service;

/**
 * Quản lý hạn mức dung lượng lưu trữ theo user (bảng {@code user_storage_quota}).
 *
 * <p>Theo C9.3 của docs/media-module-architecture.md: quota tính theo <b>logical size</b>
 * — hai user cùng dedupe chung một blob thì mỗi người vẫn bị tính đủ dung lượng.
 *
 * <p><b>Hợp đồng bắt buộc với caller (compensating action):</b> luồng upload phải
 * {@link #reserve(Long, long)} TRƯỚC khi ghi file, và nếu bất kỳ bước nào sau đó thất bại
 * (ghi storage lỗi, validate lỗi, insert DB lỗi, transaction rollback) thì caller
 * <b>bắt buộc</b> gọi {@link #release(Long, long)} với đúng số byte đã reserve, trong
 * khối {@code catch}/{@code finally}. Reserve chạy trong transaction riêng của nó và
 * không tự rollback theo transaction của caller, nên thiếu bước release sẽ để lại
 * dung lượng "ma" chiếm quota của user vĩnh viễn.
 */
public interface QuotaService {

    /**
     * Giữ trước {@code bytes} dung lượng cho user bằng một câu UPDATE có điều kiện
     * nguyên tử. Thành công thì {@code used_bytes} đã tăng ngay trong DB.
     *
     * @param userId id user
     * @param bytes  số byte cần giữ (>= 0; 0 là no-op)
     * @throws QuotaExceededException nếu vượt hạn mức
     * @see #release(Long, long) phải được gọi bù nếu upload thất bại sau khi reserve
     */
    void reserve(Long userId, long bytes);

    /**
     * Trả lại {@code bytes} dung lượng (khi xoá file, hoặc bù lại cho một
     * {@link #reserve(Long, long)} mà upload sau đó thất bại).
     * Giá trị {@code used_bytes} được kẹp sàn ở 0.
     */
    void release(Long userId, long bytes);

    /** Số liệu sử dụng hiện tại của user; tự tạo row mặc định nếu chưa có. */
    QuotaUsage getUsage(Long userId);

    /**
     * Đảm bảo user có row trong {@code user_storage_quota}, tạo row mặc định
     * ({@code used_bytes = 0}, {@code quota_bytes} = mặc định hệ thống) nếu chưa có.
     * Idempotent — gọi nhiều lần vô hại.
     */
    void ensureQuotaRow(Long userId);

    /**
     * Ảnh chụp dung lượng của một user.
     *
     * @param usedBytes      đã dùng
     * @param quotaBytes     hạn mức
     * @param remainingBytes còn lại (không âm)
     */
    record QuotaUsage(long usedBytes, long quotaBytes, long remainingBytes) {

        public static QuotaUsage of(long usedBytes, long quotaBytes) {
            return new QuotaUsage(usedBytes, quotaBytes, Math.max(quotaBytes - usedBytes, 0L));
        }

        /** Phần trăm đã dùng, 0..100 (làm tròn xuống theo double). */
        public double percentage() {
            if (quotaBytes <= 0) {
                return 0d;
            }
            return Math.min(100d, (usedBytes * 100d) / quotaBytes);
        }
    }
}
