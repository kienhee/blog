package com.kienhee.blog.service.impl;

import com.kienhee.blog.config.UploadProperties;
import com.kienhee.blog.entity.Media;
import com.kienhee.blog.entity.StorageBlob;
import com.kienhee.blog.repository.MediaRepository;
import com.kienhee.blog.repository.StorageBlobRepository;
import com.kienhee.blog.service.FileHasher;
import com.kienhee.blog.service.MediaBlobBackfillService;
import com.kienhee.blog.storage.StorageAdapter;
import jakarta.persistence.EntityManager;
import jakarta.persistence.PersistenceContext;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.beans.factory.ObjectProvider;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Propagation;
import org.springframework.transaction.annotation.Transactional;

import java.io.IOException;
import java.io.InputStream;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

/**
 * Xem javadoc cua {@link MediaBlobBackfillService} cho hop dong va cac bao dam an toan.
 *
 * <p>Vong lap nam NGOAI transaction; moi row di qua
 * {@link #processRow(Long, boolean, Map)} voi transaction rieng ({@code REQUIRES_NEW}), nen mot
 * file hong chi lam hong dung row do.
 */
@Slf4j
@Service
@RequiredArgsConstructor
public class MediaBlobBackfillServiceImpl implements MediaBlobBackfillService {

    private final MediaRepository mediaRepository;
    private final StorageBlobRepository storageBlobRepository;
    private final FileHasher fileHasher;
    private final UploadProperties uploadProperties;
    private final StorageAdapter storageAdapter;

    /**
     * Chinh bean nay, nhung di qua proxy Spring (lay lazy de tranh vong phu thuoc luc khoi tao).
     * Bat buoc: goi thang {@code this.processRow(...)} se bo qua {@code @Transactional} va ca
     * luot chay se nam trong mot transaction duy nhat.
     */
    private final ObjectProvider<MediaBlobBackfillService> selfProvider;

    @PersistenceContext
    private EntityManager entityManager;

    @Override
    public BackfillReport backfill(boolean dryRun) {
        MediaBlobBackfillService self = selfProvider.getObject();
        List<Long> candidates = findCandidateIds();

        int blobsCreated = 0;
        int rowsLinked = 0;
        int duplicatesMerged = 0;
        List<String> missingFiles = new ArrayList<>();
        List<String> orphanedFiles = new ArrayList<>();
        List<String> errors = new ArrayList<>();

        // Chi co y nghia o dry-run: blob "vua tao" chua nam trong DB nen row sau phai tra o day
        // moi nhan ra no la ban trung noi dung.
        Map<String, String> plannedBlobKeys = new HashMap<>();

        for (Long mediaId : candidates) {
            try {
                RowOutcome outcome = self.processRow(mediaId, dryRun, plannedBlobKeys);
                if (outcome.blobCreated()) {
                    blobsCreated++;
                    if (outcome.sha256() != null) {
                        plannedBlobKeys.put(outcome.sha256(), outcome.storageKey());
                    }
                }
                if (outcome.rowLinked()) {
                    rowsLinked++;
                }
                if (outcome.duplicateMerged()) {
                    duplicatesMerged++;
                }
                if (outcome.missingFile() != null) {
                    missingFiles.add(outcome.missingFile());
                }
                if (outcome.orphanedFile() != null) {
                    orphanedFiles.add(outcome.orphanedFile());
                }
            } catch (RuntimeException e) {
                log.warn("Media backfill failed for media id {}: {}", mediaId, e.toString());
                errors.add("media id " + mediaId + ": " + e.getMessage());
            }
        }

        BackfillReport report = new BackfillReport(dryRun, candidates.size(), blobsCreated,
                rowsLinked, duplicatesMerged, missingFiles, orphanedFiles, errors);
        log.info("{}", format(report));
        return report;
    }

    /** Ung vien = moi row con thieu hash hoac blob. Day chinh la dieu lam backfill idempotent. */
    private List<Long> findCandidateIds() {
        return entityManager.createQuery(
                        "select m.id from Media m where m.sha256 is null or m.blob is null order by m.id asc",
                        Long.class)
                .getResultList();
    }

    @Override
    @Transactional(propagation = Propagation.REQUIRES_NEW)
    public RowOutcome processRow(Long mediaId, boolean dryRun, Map<String, String> plannedBlobKeys) {
        Media media = mediaRepository.findById(mediaId).orElse(null);
        if (media == null) {
            return RowOutcome.skipped();
        }
        if (media.getSha256() != null && media.getBlob() != null) {
            return RowOutcome.skipped();
        }

        String storedFilename = media.getStoredFilename();
        Path file = uploadRoot().resolve(storedFilename).normalize();

        // Orphan record: KHONG xoa row, KHONG nem loi - chi bao cao lai cho nguoi van hanh.
        if (!Files.isRegularFile(file)) {
            log.warn("Media backfill: file missing on disk for media id {} ({}), row left untouched",
                    mediaId, storedFilename);
            return RowOutcome.missing(storedFilename);
        }

        String sha256;
        long sizeBytes;
        try (InputStream in = Files.newInputStream(file)) {
            sha256 = fileHasher.hash(in);
            sizeBytes = Files.size(file);
        } catch (IOException e) {
            throw new IllegalStateException("Cannot read file for media id " + mediaId
                    + " (" + storedFilename + "): " + e.getMessage(), e);
        }

        StorageBlob existing = storageBlobRepository.findBySha256(sha256).orElse(null);
        // Trong luot chay that, blob vua tao da nam trong DB nen findBySha256 luon thay no;
        // plannedBlobKeys chi duoc dung o dry-run, noi khong co gi duoc ghi xuong.
        String plannedKey = (existing == null && dryRun) ? plannedBlobKeys.get(sha256) : null;

        // --- Chua co blob nao mang hash nay -> row nay tro thanh canonical ------------------
        if (existing == null && plannedKey == null) {
            if (!dryRun) {
                StorageBlob blob = storageBlobRepository.save(StorageBlob.builder()
                        .sha256(sha256)
                        .storageKey(storedFilename)
                        .storageProvider(storageAdapter.providerCode())
                        .sizeBytes(sizeBytes)
                        .contentType(media.getContentType())
                        .refCount(1)
                        .build());
                media.setBlob(blob);
                media.setSha256(sha256);
                mediaRepository.save(media);
            }
            return new RowOutcome(true, true, false, null, null, sha256, storedFilename);
        }

        String canonicalKey = existing != null ? existing.getStorageKey() : plannedKey;

        // --- Da co blob, va row nay chinh la file canonical -> chi can noi day --------------
        if (canonicalKey.equals(storedFilename)) {
            if (!dryRun && existing != null) {
                media.setBlob(existing);
                media.setSha256(sha256);
                mediaRepository.save(media);
                storageBlobRepository.addRefCount(existing.getId(), 1);
            }
            return new RowOutcome(false, true, false, null, null, sha256, canonicalKey);
        }

        // --- Trung noi dung voi mot file vat ly KHAC -> gop ve blob canonical ---------------
        // url cua media dang duoc tham chieu o posts.cover_image va users.avatar_url; doi url
        // ma khong cap nhat hai cho do la tao broken link, nen ca ba thay doi phai nam trong
        // CUNG transaction nay.
        String oldUrl = media.getUrl();
        String newUrl = storageAdapter.publicUrl(canonicalKey);
        if (!dryRun && existing != null) {
            media.setStoredFilename(canonicalKey);
            media.setUrl(newUrl);
            media.setBlob(existing);
            media.setSha256(sha256);
            mediaRepository.save(media);
            storageBlobRepository.addRefCount(existing.getId(), 1);
            repointReferences(oldUrl, newUrl);
        }
        // File vat ly cu gio khong con media row nao tro toi - nhung TUYET DOI khong tu xoa.
        return new RowOutcome(false, true, true, null, storedFilename, sha256, canonicalKey);
    }

    /** Tro lai moi tham chieu url cua media sang url canonical. */
    private void repointReferences(String oldUrl, String newUrl) {
        if (oldUrl == null || oldUrl.equals(newUrl)) {
            return;
        }
        entityManager.flush();
        int posts = entityManager.createQuery(
                        "update Post p set p.coverImage = :newUrl where p.coverImage = :oldUrl")
                .setParameter("newUrl", newUrl)
                .setParameter("oldUrl", oldUrl)
                .executeUpdate();
        int users = entityManager.createQuery(
                        "update User u set u.avatarUrl = :newUrl where u.avatarUrl = :oldUrl")
                .setParameter("newUrl", newUrl)
                .setParameter("oldUrl", oldUrl)
                .executeUpdate();
        if (posts > 0 || users > 0) {
            log.info("Media backfill: repointed {} post cover(s) and {} avatar(s) from {} to {}",
                    posts, users, oldUrl, newUrl);
        }
    }

    private Path uploadRoot() {
        return Paths.get(uploadProperties.getDir()).toAbsolutePath().normalize();
    }

    /** Bao cao dang nhieu dong, doc duoc ngay trong log khoi dong. */
    public static String format(BackfillReport r) {
        StringBuilder sb = new StringBuilder("\n=== Media blob backfill report ===")
                .append("\n  mode              : ").append(r.dryRun() ? "DRY-RUN (nothing written)" : "APPLIED")
                .append("\n  scanned           : ").append(r.scanned())
                .append("\n  blobs created     : ").append(r.blobsCreated())
                .append("\n  rows linked       : ").append(r.rowsLinked())
                .append("\n  duplicates merged : ").append(r.duplicatesMerged());
        appendList(sb, "missing files (row kept, NOT deleted)", r.missingFiles());
        appendList(sb, "orphaned files (review by hand, NOT deleted)", r.orphanedFiles());
        appendList(sb, "errors", r.errors());
        return sb.append("\n=== end of report ===").toString();
    }

    private static void appendList(StringBuilder sb, String label, List<String> values) {
        sb.append("\n  ").append(label).append(" : ").append(values.size());
        for (String v : values) {
            sb.append("\n      - ").append(v);
        }
    }
}
