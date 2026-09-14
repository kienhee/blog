package com.kienhee.blog.service;

/**
 * Ném khi một thao tác upload làm vượt hạn mức dung lượng của user.
 *
 * <p><b>Vì sao extends {@link IllegalArgumentException}:</b> repo quy ước tầng service
 * ném {@code IllegalArgumentException} cho mọi vi phạm business rule, và các controller
 * hiện có đã bắt sẵn kiểu này để dịch thành lỗi {@code BindingResult} / flash message.
 * Kế thừa từ đó giúp exception này rơi vào đúng đường xử lý sẵn có mà không phải sửa
 * controller nào; đồng thời handler nào muốn trả HTTP 507 riêng vẫn có thể bắt đúng
 * kiểu con này trước.
 *
 * <p>Mang theo used/quota/requested để tầng trên render được thông báo tử tế
 * ("Bạn đã dùng 950MB/1GB, file này 120MB").
 */
public class QuotaExceededException extends IllegalArgumentException {

    private final long usedBytes;
    private final long quotaBytes;
    private final long requestedBytes;

    public QuotaExceededException(long usedBytes, long quotaBytes, long requestedBytes) {
        super("Storage quota exceeded: used=" + usedBytes
                + " bytes, quota=" + quotaBytes
                + " bytes, requested=" + requestedBytes + " bytes");
        this.usedBytes = usedBytes;
        this.quotaBytes = quotaBytes;
        this.requestedBytes = requestedBytes;
    }

    public long getUsedBytes() {
        return usedBytes;
    }

    public long getQuotaBytes() {
        return quotaBytes;
    }

    public long getRequestedBytes() {
        return requestedBytes;
    }

    /** Dung lượng còn lại tại thời điểm bị từ chối (không âm). */
    public long getRemainingBytes() {
        return Math.max(quotaBytes - usedBytes, 0L);
    }
}
