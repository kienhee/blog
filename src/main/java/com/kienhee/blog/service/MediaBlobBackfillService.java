package com.kienhee.blog.service;

import java.util.List;
import java.util.Map;

/**
 * Backfill một lần cho các {@code media} row có TRƯỚC migration V13: những row đó có
 * {@code sha256 = NULL} và {@code blob_id = NULL}, nên chúng nằm ngoài cơ chế dedupe và
 * ref-count mà toàn bộ module Media từ P1 trở đi giả định là luôn tồn tại.
 *
 * <p><b>Idempotent:</b> chỉ những row còn thiếu {@code sha256} hoặc {@code blob_id} mới được
 * quét, nên chạy lại nhiều lần là an toàn — lần chạy thứ hai (trên dữ liệu lành) quét 0 row.
 *
 * <p><b>Không bao giờ xoá gì:</b> row trỏ tới file đã biến mất được giữ nguyên và chỉ báo cáo
 * trong {@link BackfillReport#missingFiles()}; file vật lý dư ra sau khi gộp trùng nội dung
 * cũng chỉ được liệt kê trong {@link BackfillReport#orphanedFiles()} để người vận hành tự
 * quyết định. Mất dữ liệu không cứu được là rủi ro nghiêm trọng nhất của module này.
 */
public interface MediaBlobBackfillService {

    /**
     * Quét và backfill toàn bộ media row còn thiếu blob.
     *
     * @param dryRun {@code true} = tính toán và trả về báo cáo ĐẦY ĐỦ y hệt bản thật nhưng
     *               không ghi một dòng nào vào DB và không đụng vào đĩa
     * @return báo cáo tổng hợp
     */
    BackfillReport backfill(boolean dryRun);

    /**
     * Xử lý đúng MỘT media row trong transaction riêng của nó, để một file hỏng không kéo
     * cả lượt chạy rollback. Việc gộp duplicate (đổi {@code stored_filename}/{@code url} của
     * media + cập nhật {@code posts.cover_image} và {@code users.avatar_url}) nằm trọn trong
     * transaction này.
     *
     * <p>Nằm trên interface vì {@link #backfill(boolean)} phải gọi nó qua proxy Spring —
     * gọi trực tiếp trong cùng bean sẽ bỏ qua {@code @Transactional}. Không dùng từ nơi khác.
     *
     * @param plannedBlobKeys map {@code sha256 -> storage_key} tích luỹ trong lượt chạy; chỉ có
     *                        tác dụng ở chế độ dry-run, nơi blob "vừa tạo" chưa thực sự nằm
     *                        trong DB nhưng row sau vẫn phải nhìn thấy nó để báo cáo đúng
     */
    RowOutcome processRow(Long mediaId, boolean dryRun, Map<String, String> plannedBlobKeys);

    /** Kết quả tổng hợp của một lượt backfill. */
    record BackfillReport(
            boolean dryRun,
            int scanned,
            int blobsCreated,
            int rowsLinked,
            int duplicatesMerged,
            List<String> missingFiles,
            List<String> orphanedFiles,
            List<String> errors) {
    }

    /** Kết quả xử lý một row, để vòng lặp bên ngoài cộng dồn vào báo cáo. */
    record RowOutcome(
            boolean blobCreated,
            boolean rowLinked,
            boolean duplicateMerged,
            String missingFile,
            String orphanedFile,
            String sha256,
            String storageKey) {

        public static RowOutcome skipped() {
            return new RowOutcome(false, false, false, null, null, null, null);
        }

        public static RowOutcome missing(String storedFilename) {
            return new RowOutcome(false, false, false, storedFilename, null, null, null);
        }
    }
}
