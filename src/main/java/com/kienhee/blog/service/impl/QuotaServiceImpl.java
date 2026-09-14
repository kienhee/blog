package com.kienhee.blog.service.impl;

import com.kienhee.blog.entity.UserStorageQuota;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.repository.UserStorageQuotaRepository;
import com.kienhee.blog.service.QuotaExceededException;
import com.kienhee.blog.service.QuotaService;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Propagation;
import org.springframework.transaction.annotation.Transactional;

import java.util.Optional;

/**
 * Hiện thực quota theo C9.3: mọi thay đổi {@code used_bytes} đều đi qua một câu
 * UPDATE có điều kiện nguyên tử ở tầng DB, không bao giờ read-then-write ở Java.
 */
@Slf4j
@Service
@RequiredArgsConstructor
public class QuotaServiceImpl implements QuotaService {

    /** 1GB — trùng với DEFAULT của cột {@code quota_bytes} trong migration V13. */
    public static final long DEFAULT_QUOTA_BYTES = 1_073_741_824L;

    private final UserStorageQuotaRepository userStorageQuotaRepository;
    private final UserRepository userRepository;

    @Value("${app.media.default-quota-bytes:" + DEFAULT_QUOTA_BYTES + "}")
    private long defaultQuotaBytes = DEFAULT_QUOTA_BYTES;

    /**
     * {@inheritDoc}
     *
     * <p>Chạy trong transaction RIÊNG ({@code REQUIRES_NEW}) để phần dung lượng đã giữ
     * không bị cuốn theo rollback của transaction upload — đổi lại caller BẮT BUỘC
     * gọi {@link #release(Long, long)} bù khi upload thất bại sau bước này.
     */
    @Override
    @Transactional(propagation = Propagation.REQUIRES_NEW)
    public void reserve(Long userId, long bytes) {
        requireUserId(userId);
        if (bytes < 0) {
            throw new IllegalArgumentException("bytes must not be negative");
        }
        if (bytes == 0) {
            return;
        }
        ensureQuotaRow(userId);

        // Nguyên tử: điều kiện "used + bytes <= quota" nằm NGAY TRONG câu UPDATE, do DB
        // đánh giá dưới row lock. Nếu đọc used_bytes ra Java, so sánh rồi ghi lại thì hai
        // request upload đồng thời đều đọc được giá trị cũ và cùng ghi -> vượt quota.
        int affected = userStorageQuotaRepository.reserve(userId, bytes);
        if (affected == 0) {
            // 0 row = điều kiện quota không thoả (row chắc chắn tồn tại vì vừa ensure ở trên).
            QuotaUsage usage = readUsage(userId);
            throw new QuotaExceededException(usage.usedBytes(), usage.quotaBytes(), bytes);
        }
    }

    /**
     * {@inheritDoc}
     *
     * <p>Cũng chạy transaction riêng: release thường được gọi từ khối {@code catch} khi
     * transaction của caller đã bị đánh dấu rollback-only, nếu tham gia chung transaction
     * đó thì thao tác bù sẽ mất trắng.
     */
    @Override
    @Transactional(propagation = Propagation.REQUIRES_NEW)
    public void release(Long userId, long bytes) {
        requireUserId(userId);
        if (bytes <= 0) {
            return;
        }
        // GREATEST(used_bytes - :bytes, 0) ở tầng SQL: một bug logic (release hai lần,
        // release nhiều hơn đã reserve) chỉ làm số liệu lệch, không tạo used_bytes âm.
        int affected = userStorageQuotaRepository.release(userId, bytes);
        if (affected == 0) {
            // Không có row -> không có gì để trả lại. Không ném lỗi: release là thao tác bù,
            // ném ở đây sẽ che mất exception gốc đã làm upload thất bại.
            log.warn("release({} bytes) affected 0 rows: no quota row for user {}", bytes, userId);
        }
    }

    @Override
    @Transactional(readOnly = true)
    public QuotaUsage getUsage(Long userId) {
        requireUserId(userId);
        return readUsage(userId);
    }

    @Override
    @Transactional(propagation = Propagation.REQUIRES_NEW)
    public void ensureQuotaRow(Long userId) {
        requireUserId(userId);
        if (userStorageQuotaRepository.existsById(userId)) {
            return;
        }
        try {
            // getReferenceById: entity dùng @MapsId nên bắt buộc phải set association User,
            // nhưng chỉ cần proxy — không tốn thêm một câu SELECT users.
            userStorageQuotaRepository.saveAndFlush(UserStorageQuota.builder()
                    .user(userRepository.getReferenceById(userId))
                    .quotaBytes(defaultQuotaBytes)
                    .usedBytes(0L)
                    .build());
        } catch (RuntimeException e) {
            // Hai request đầu tiên của cùng một user chạy song song có thể cùng insert.
            // Row đã tồn tại là kết quả mong muốn -> nuốt lỗi, chỉ ném lại nếu thật sự chưa có.
            if (!userStorageQuotaRepository.existsById(userId)) {
                throw e;
            }
            log.debug("Concurrent quota row creation for user {} - row already present", userId);
        }
    }

    private QuotaUsage readUsage(Long userId) {
        Optional<UserStorageQuota> row = userStorageQuotaRepository.findById(userId);
        return row
                .map(q -> QuotaUsage.of(q.getUsedBytes(), q.getQuotaBytes()))
                .orElseGet(() -> QuotaUsage.of(0L, defaultQuotaBytes));
    }

    private static void requireUserId(Long userId) {
        if (userId == null) {
            throw new IllegalArgumentException("userId must not be null");
        }
    }
}
