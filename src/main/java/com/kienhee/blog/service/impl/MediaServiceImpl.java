package com.kienhee.blog.service.impl;

import com.kienhee.blog.config.TinifyProperties;
import com.kienhee.blog.dto.MediaUpdateRequest;
import com.kienhee.blog.entity.Media;
import com.kienhee.blog.entity.MediaFolder;
import com.kienhee.blog.entity.StorageBlob;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.MediaFolderRepository;
import com.kienhee.blog.repository.MediaRepository;
import com.kienhee.blog.repository.PostRepository;
import com.kienhee.blog.repository.StorageBlobRepository;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.service.FileHasher;
import com.kienhee.blog.service.MediaOptimizationService;
import com.kienhee.blog.service.MediaService;
import com.kienhee.blog.service.QuotaService;
import com.kienhee.blog.service.validation.FileValidationChain;
import com.kienhee.blog.service.validation.MediaTypeCatalog;
import com.kienhee.blog.service.validation.UploadValidationContext;
import com.kienhee.blog.storage.StorageException;
import com.kienhee.blog.storage.StoragePath;
import com.kienhee.blog.storage.StorageTransactionHelper;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.multipart.MultipartFile;

import javax.imageio.ImageIO;
import java.awt.Graphics2D;
import java.awt.RenderingHints;
import java.awt.image.BufferedImage;
import java.io.ByteArrayInputStream;
import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.Comparator;
import java.util.HashSet;
import java.util.List;
import java.util.Locale;
import java.util.Set;

@Slf4j
@Service
@RequiredArgsConstructor
public class MediaServiceImpl implements MediaService {

    private static final int THUMBNAIL_MAX_DIMENSION = 320;

    private final MediaRepository mediaRepository;
    private final MediaFolderRepository mediaFolderRepository;
    private final PostRepository postRepository;
    private final UserRepository userRepository;
    private final StorageBlobRepository storageBlobRepository;
    private final MediaOptimizationService mediaOptimizationService;
    private final UploadRateLimiter uploadRateLimiter;
    private final TinifyProperties tinifyProperties;
    private final FileValidationChain fileValidationChain;
    private final FileHasher fileHasher;
    private final QuotaService quotaService;
    private final MediaStorageLayout layout;
    private final StorageTransactionHelper storageTx;

    @Override
    @Transactional(readOnly = true)
    public List<Media> getAllMedia() {
        return mediaRepository.findAllWithUploader();
    }

    @Override
    @Transactional(readOnly = true)
    public Set<String> getUsedMediaUrls() {
        Set<String> used = new HashSet<>(postRepository.findDistinctCoverImages());
        used.addAll(userRepository.findDistinctAvatarUrls());
        return used;
    }

    @Override
    @Transactional
    public Media uploadMedia(MultipartFile file, String uploaderEmail, Long folderId) {
        if (!uploadRateLimiter.tryAcquire(uploaderEmail)) {
            throw new IllegalArgumentException("Upload limit reached. Please wait a few minutes and try again.");
        }
        if (file == null) {
            throw new IllegalArgumentException("Please choose a file to upload.");
        }

        byte[] original;
        try {
            original = file.getBytes();
        } catch (IOException e) {
            throw new IllegalArgumentException("Could not read the uploaded file.");
        }

        // Size / content-type whitelist / magic bytes all live in the validator chain now,
        // so a new rule (virus scan, per-folder limits) is a new @Component, not an edit here.
        fileValidationChain.validate(UploadValidationContext.builder()
                .originalFilename(file.getOriginalFilename())
                .declaredContentType(file.getContentType())
                .sizeBytes(file.getSize())
                .bytes(original)
                .uploaderEmail(uploaderEmail)
                .folderId(folderId)
                .build());

        String contentType = MediaTypeCatalog.normalize(file.getContentType());

        User uploader = userRepository.findByEmail(uploaderEmail)
                .orElseThrow(() -> new IllegalArgumentException("Uploader not found."));

        MediaFolder folder = null;
        if (folderId != null) {
            folder = mediaFolderRepository.findById(folderId)
                    .orElseThrow(() -> new IllegalArgumentException("Folder not found."));
        }

        // Reserve runs in its own transaction and survives a rollback of this one, so every
        // failure path below MUST hand the bytes back — see the try/catch wrapping the rest.
        quotaService.ensureQuotaRow(uploader.getId());
        quotaService.reserve(uploader.getId(), original.length);
        boolean reservationHeld = true;
        try {
            Media saved = storeUploadedFile(file, original, contentType, uploader, folder);
            reservationHeld = false;
            return saved;
        } finally {
            if (reservationHeld) {
                quotaService.release(uploader.getId(), original.length);
            }
        }
    }

    /**
     * Second half of {@link #uploadMedia}: everything that happens once the file is known to be
     * valid and the quota reserved. Split out so the compensating quota release above has a
     * single, obvious boundary to guard.
     */
    private Media storeUploadedFile(MultipartFile file, byte[] original, String contentType,
                                    User uploader, MediaFolder folder) {
        // Hash is still computed and stored - to detect duplicates, never to share a file:
        // since V16 every upload owns its own blob and physical file (ref_count = 1).
        String sha256 = fileHasher.hash(original);

        // Disk mirrors the folder tree: uploads/<ancestor slugs>/<sanitised original name>,
        // with a case-insensitive " (2)" suffix when the name is already taken there.
        StoragePath dir = layout.directoryOf(folder);
        String filename = layout.uniqueFilename(dir, withExtension(file.getOriginalFilename(), contentType));
        StoragePath path = dir.resolve(filename);
        layout.requireLength(path);

        // Disk first, DB second: the helper deletes the file again if this transaction rolls back.
        try {
            storageTx.writeFile(path, original);
        } catch (StorageException e) {
            log.warn("Could not store upload {}: {}", path, e.getMessage());
            throw new IllegalArgumentException("Could not save the uploaded file.");
        }

        StorageBlob blob = storageBlobRepository.save(StorageBlob.builder()
                .sha256(sha256)
                .storageKey(path.toString())
                .storageProvider(MediaStorageLayout.PROVIDER_CODE)
                .sizeBytes(original.length)
                .contentType(contentType)
                .refCount(1)
                .build());

        boolean isRasterImage = MediaTypeCatalog.isRasterImage(contentType);
        BufferedImage decoded = null;
        Integer width = null;
        Integer height = null;
        if (isRasterImage) {
            try {
                decoded = ImageIO.read(new ByteArrayInputStream(original));
                if (decoded != null) {
                    width = decoded.getWidth();
                    height = decoded.getHeight();
                }
            } catch (IOException e) {
                log.debug("Could not read image dimensions for {}: {}", path, e.getMessage());
            }
        }
        if (width == null && "image/webp".equalsIgnoreCase(contentType)) {
            // No WebP decoder in the JDK: take the size from the header (no thumbnail is generated).
            WebpDimensions.Size size = WebpDimensions.read(original);
            if (size != null) {
                width = size.width();
                height = size.height();
            }
        }

        // The stable URL embeds the id, which only exists after the INSERT: save, then set the
        // URL (and the thumbnail, whose file name also uses the id), then save again - all in
        // this transaction, so no other reader ever sees the placeholder.
        Media media = mediaRepository.save(Media.builder()
                .originalFilename(file.getOriginalFilename() != null ? file.getOriginalFilename() : filename)
                .storedFilename(filename)
                .storagePath(path.toString())
                .url("pending")
                .contentType(contentType)
                .sizeBytes(original.length)
                .width(width)
                .height(height)
                .optimized(false)
                .sha256(sha256)
                .blob(blob)
                .status(Media.Status.ACTIVE)
                .uploadedBy(uploader)
                .folder(folder)
                .build());

        media.setUrl(MediaStorageLayout.publicUrl(media.getId(), filename));
        if (MediaTypeCatalog.isImage(contentType) && generateThumbnail(decoded, media.getId(), contentType)) {
            media.setThumbnailUrl(MediaStorageLayout.thumbnailUrl(media.getId()));
        }
        Media saved = mediaRepository.save(media);

        if (tinifyProperties.isEnabled() && isRasterImage) {
            Long id = saved.getId();
            // After commit: the async worker must be able to see the row.
            storageTx.runAfterCommit("optimize media " + id, () -> mediaOptimizationService.optimizeAsync(id));
        }
        return saved;
    }

    @Override
    @Transactional
    public Media replaceImage(Long id, MultipartFile file, String editorEmail) {
        if (!uploadRateLimiter.tryAcquire(editorEmail)) {
            throw new IllegalArgumentException("Upload limit reached. Please wait a few minutes and try again.");
        }
        if (file == null || file.isEmpty()) {
            throw new IllegalArgumentException("Please choose an image.");
        }
        Media media = mediaRepository.findById(id)
                .orElseThrow(() -> new IllegalArgumentException("Media not found with id: " + id));
        if (media.getStatus() != Media.Status.ACTIVE) {
            throw new IllegalArgumentException("Restore the file from the trash before editing it.");
        }
        if (!MediaTypeCatalog.isRasterImage(media.getContentType())) {
            throw new IllegalArgumentException("Only JPG, PNG, WEBP and GIF images can be edited.");
        }

        byte[] bytes;
        try {
            bytes = file.getBytes();
        } catch (IOException e) {
            throw new IllegalArgumentException("Could not read the edited image.");
        }
        String contentType = MediaTypeCatalog.normalize(file.getContentType());
        if (!media.getContentType().equalsIgnoreCase(contentType)) {
            // Changing the format would change the file name, and with it the public URL.
            throw new IllegalArgumentException("Replacing keeps the original format ("
                    + MediaTypeCatalog.defaultExtension(media.getContentType()).replace(".", "").toUpperCase(Locale.ROOT)
                    + "). Save it as a copy to change the format.");
        }

        fileValidationChain.validate(UploadValidationContext.builder()
                .originalFilename(media.getOriginalFilename())
                .declaredContentType(file.getContentType())
                .sizeBytes(bytes.length)
                .bytes(bytes)
                .uploaderEmail(editorEmail)
                .folderId(media.getFolder() != null ? media.getFolder().getId() : null)
                .build());

        BufferedImage decoded;
        try {
            decoded = ImageIO.read(new ByteArrayInputStream(bytes));
        } catch (IOException e) {
            decoded = null;
        }
        Integer newWidth = decoded != null ? decoded.getWidth() : null;
        Integer newHeight = decoded != null ? decoded.getHeight() : null;
        if (decoded == null && "image/webp".equalsIgnoreCase(contentType)) {
            // No WebP decoder in the JDK: the header still gives the size; the file serves as its own thumbnail.
            WebpDimensions.Size size = WebpDimensions.read(bytes);
            if (size != null) {
                newWidth = size.width();
                newHeight = size.height();
            }
        }
        if (newWidth == null) {
            throw new IllegalArgumentException("Could not read the edited image.");
        }

        // The file's owner pays for its bytes, whoever edits it. Growth is reserved up front
        // (and handed back on failure); shrinkage is released only once the change has committed.
        Long ownerId = media.getUploadedBy() != null ? media.getUploadedBy().getId() : null;
        long delta = bytes.length - media.getSizeBytes();
        boolean reservationHeld = false;
        if (ownerId != null && delta > 0) {
            quotaService.ensureQuotaRow(ownerId);
            quotaService.reserve(ownerId, delta);
            reservationHeld = true;
        }
        try {
            StoragePath path = layout.effectivePath(media);
            // Never overwrite in place: move the original aside (moved back on rollback), write the
            // new bytes (deleted on rollback), and drop the old copy only after commit.
            StoragePath backup = StoragePath.of(path + ".edit-" + System.nanoTime() + ".bak");
            storageTx.moveFile(path, backup);
            storageTx.writeFile(path, bytes);
            storageTx.deleteFileAfterCommit(backup);

            StoragePath oldThumb = layout.existingThumbnail(id);
            if (oldThumb != null) {
                StoragePath thumbBackup = StoragePath.of(oldThumb + ".edit-" + System.nanoTime() + ".bak");
                storageTx.moveFile(oldThumb, thumbBackup);
                storageTx.deleteFileAfterCommit(thumbBackup);
            }
            boolean hasThumb = generateThumbnail(decoded, id, contentType);

            String sha256 = fileHasher.hash(bytes);
            StorageBlob blob = media.getBlob();
            if (blob != null) {
                blob.setSha256(sha256);
                blob.setSizeBytes(bytes.length);
                storageBlobRepository.save(blob);
            }
            media.setSizeBytes(bytes.length);
            media.setOriginalSizeBytes(null);
            media.setOptimized(false);
            media.setWidth(newWidth);
            media.setHeight(newHeight);
            media.setSha256(sha256);
            media.setThumbnailUrl(hasThumb ? MediaStorageLayout.thumbnailUrl(id) : null);
            Media saved = mediaRepository.save(media);

            if (ownerId != null && delta < 0) {
                long freed = -delta;
                storageTx.runAfterCommit("release quota after image edit " + id, () -> quotaService.release(ownerId, freed));
            }
            if (tinifyProperties.isEnabled()) {
                storageTx.runAfterCommit("optimize media " + id, () -> mediaOptimizationService.optimizeAsync(id));
            }
            reservationHeld = false;
            return saved;
        } catch (StorageException e) {
            log.warn("Could not replace image {}: {}", id, e.getMessage());
            throw new IllegalArgumentException("Could not save the edited image.");
        } finally {
            if (reservationHeld) {
                quotaService.release(ownerId, delta);
            }
        }
    }

    /** Appends an extension derived from the content type when the client name has none. */
    private String withExtension(String originalFilename, String contentType) {
        String name = originalFilename == null || originalFilename.isBlank() ? "file" : originalFilename;
        String lastSegment = name.replace('\\', '/');
        lastSegment = lastSegment.substring(lastSegment.lastIndexOf('/') + 1);
        int dot = lastSegment.lastIndexOf('.');
        boolean hasExtension = dot > 0 && dot < lastSegment.length() - 1;
        return hasExtension ? name : name + extractExtension(null, contentType);
    }

    /**
     * Generates a small local thumbnail (no network call) at {@code .thumbnails/<id>_thumb.<ext>}.
     * Returns false when no separate thumbnail is needed (SVG, or already small enough).
     */
    private boolean generateThumbnail(BufferedImage source, Long mediaId, String contentType) {
        if (source == null) {
            return false;
        }
        int w = source.getWidth();
        int h = source.getHeight();
        if (w <= THUMBNAIL_MAX_DIMENSION && h <= THUMBNAIL_MAX_DIMENSION) {
            return false;
        }

        double scale = Math.min((double) THUMBNAIL_MAX_DIMENSION / w, (double) THUMBNAIL_MAX_DIMENSION / h);
        int tw = Math.max(1, (int) Math.round(w * scale));
        int th = Math.max(1, (int) Math.round(h * scale));

        boolean isJpeg = "image/jpeg".equalsIgnoreCase(contentType);
        int imageType = isJpeg ? BufferedImage.TYPE_INT_RGB : BufferedImage.TYPE_INT_ARGB;
        String writeFormat = isJpeg ? "jpg" : "png";

        BufferedImage thumb = new BufferedImage(tw, th, imageType);
        Graphics2D g = thumb.createGraphics();
        g.setRenderingHint(RenderingHints.KEY_INTERPOLATION, RenderingHints.VALUE_INTERPOLATION_BILINEAR);
        g.setRenderingHint(RenderingHints.KEY_RENDERING, RenderingHints.VALUE_RENDER_QUALITY);
        g.drawImage(source, 0, 0, tw, th, null);
        g.dispose();

        try {
            ByteArrayOutputStream out = new ByteArrayOutputStream();
            ImageIO.write(thumb, writeFormat, out);
            storageTx.writeFile(layout.thumbnailPath(mediaId, writeFormat), out.toByteArray());
            return true;
        } catch (IOException | StorageException e) {
            log.debug("Could not write thumbnail for media {}: {}", mediaId, e.getMessage());
            return false;
        }
    }

    @Override
    @Transactional
    public Media updateMedia(Long id, MediaUpdateRequest request) {
        Media media = mediaRepository.findById(id)
                .orElseThrow(() -> new IllegalArgumentException("Media not found with id: " + id));

        media.setOriginalFilename(request.getDisplayName().trim());
        media.setAltText(request.getAltText() != null && !request.getAltText().isBlank() ? request.getAltText().trim() : null);

        MediaFolder folder = null;
        if (request.getFolderId() != null) {
            folder = mediaFolderRepository.findById(request.getFolderId())
                    .orElseThrow(() -> new IllegalArgumentException("Folder not found."));
        }
        // Moves the physical file too (free name first, compensated on rollback).
        layout.relocate(media, folder);

        return mediaRepository.save(media);
    }

    @Override
    @Transactional
    public void deleteMedia(Long id) {
        Media media = mediaRepository.findById(id)
                .orElseThrow(() -> new IllegalArgumentException("Media not found with id: " + id));

        if (media.getStatus() == Media.Status.TRASHED) {
            return; // already in the trash - deleting twice is a no-op, not an error
        }
        assertNotInUse(media);

        // Soft delete only: no disk I/O, no ref_count change, no quota release.
        // The bytes stay reserved against the uploader until the item is purged.
        media.setStatus(Media.Status.TRASHED);
        media.setDeletedAt(LocalDateTime.now());
        mediaRepository.save(media);
    }

    /**
     * A file wired into a published page is refused at soft-delete time, not at purge
     * time: pulling it out of the library already leaves a broken image on the public
     * site, even though the bytes are technically still on disk.
     */
    private void assertNotInUse(Media media) {
        if (postRepository.existsByCoverImage(media.getUrl())) {
            throw new IllegalArgumentException("Cannot delete: this file is used as a cover image on one or more posts.");
        }
        if (userRepository.existsByAvatarUrl(media.getUrl())) {
            throw new IllegalArgumentException("Cannot delete: this file is used as a user's profile photo.");
        }
    }

    @Override
    @Transactional(readOnly = true)
    public List<Media> getTrashedMedia() {
        return mediaRepository.findTrashedWithUploader();
    }

    @Override
    @Transactional
    public RestoreResult restoreMedia(Long id) {
        Media media = mediaRepository.findById(id)
                .orElseThrow(() -> new IllegalArgumentException("Media not found with id: " + id));
        if (media.getStatus() != Media.Status.TRASHED) {
            throw new IllegalArgumentException("This file is not in the trash.");
        }

        // The folder may have been purged (the FK already nulled the column) or may itself
        // be sitting in the trash. Either way the file has nowhere to go but the root.
        boolean movedToRoot = false;
        MediaFolder folder = media.getFolder();
        if (folder != null && folder.getStatus() != MediaFolder.Status.ACTIVE) {
            layout.relocate(media, null);
            movedToRoot = true;
        }

        media.setStatus(Media.Status.ACTIVE);
        media.setDeletedAt(null);
        return new RestoreResult(mediaRepository.save(media), movedToRoot);
    }

    @Override
    @Transactional
    public void purgeMedia(Long id) {
        Media media = mediaRepository.findById(id)
                .orElseThrow(() -> new IllegalArgumentException("Media not found with id: " + id));
        if (media.getStatus() != Media.Status.TRASHED) {
            throw new IllegalArgumentException(
                    "Cannot permanently delete: move the file to the trash first.");
        }
        hardDelete(media);
    }

    /**
     * The irreversible half: ref_count down, physical file gone once nothing references it
     * any more, row gone, quota given back. Only ever reached from a purge.
     */
    private void hardDelete(Media media) {
        Long id = media.getId();
        StorageBlob blob = media.getBlob();
        // Pre-V13 rows have no blob and were never shared, so they keep the old behaviour:
        // the row owns its file outright.
        boolean lastReference = true;
        if (blob != null) {
            storageBlobRepository.decrementRefCount(blob.getId());
            lastReference = storageBlobRepository.findById(blob.getId())
                    .map(b -> b.getRefCount() <= 0)
                    .orElse(true);
        }

        if (lastReference) {
            // Irreversible, so deferred until the row deletion has committed.
            try {
                storageTx.deleteFileAfterCommit(layout.effectivePath(media));
                layout.thumbnailCandidates(id).forEach(storageTx::deleteFileAfterCommit);
                String legacyThumb = media.getThumbnailUrl();
                if (legacyThumb != null && legacyThumb.startsWith("/uploads/")) {
                    storageTx.deleteFileAfterCommit(StoragePath.of(legacyThumb.substring("/uploads/".length())));
                }
            } catch (StorageException e) {
                log.warn("Could not schedule file deletion for media {}: {}", id, e.getMessage());
            }
            if (blob != null) {
                storageBlobRepository.delete(blob);
            }
        }

        Long uploaderId = media.getUploadedBy() != null ? media.getUploadedBy().getId() : null;
        long freedBytes = media.getSizeBytes();

        mediaRepository.delete(media);

        // Quota is released only here: a file in the trash still costs the user its bytes.
        if (uploaderId != null) {
            quotaService.release(uploaderId, freedBytes);
        }
    }

    @Override
    @Transactional
    public BulkDeleteResult emptyTrash() {
        int purged = 0;
        List<String> errors = new ArrayList<>();
        for (Media media : mediaRepository.findByStatus(Media.Status.TRASHED)) {
            try {
                hardDelete(media);
                purged++;
            } catch (RuntimeException e) {
                errors.add("#" + media.getId() + ": " + e.getMessage());
            }
        }
        // Folders after files, deepest first: purging a folder row only unfiles whatever is
        // left inside it, and the parent FK is ON DELETE RESTRICT.
        purged += purgeTrashedFolders(mediaFolderRepository.findByStatus(MediaFolder.Status.TRASHED), errors);
        return new BulkDeleteResult(purged, errors);
    }

    private int purgeTrashedFolders(List<MediaFolder> candidates, List<String> errors) {
        List<MediaFolder> folders = new ArrayList<>(candidates);
        folders.sort(Comparator.comparingInt(MediaFolder::getDepth).reversed());
        int purged = 0;
        for (MediaFolder folder : folders) {
            try {
                layout.purgeFolder(folder);
                purged++;
            } catch (RuntimeException e) {
                if (errors != null) {
                    errors.add("folder #" + folder.getId() + ": " + e.getMessage());
                } else {
                    log.warn("Auto purge skipped folder {}: {}", folder.getId(), e.getMessage());
                }
            }
        }
        return purged;
    }

    @Override
    @Transactional
    public int purgeExpired(int retentionDays) {
        LocalDateTime cutoff = LocalDateTime.now().minusDays(Math.max(retentionDays, 0));
        int purged = 0;
        for (Media media : mediaRepository.findByStatusAndDeletedAtBefore(Media.Status.TRASHED, cutoff)) {
            try {
                hardDelete(media);
                purged++;
            } catch (RuntimeException e) {
                log.warn("Auto purge skipped media {}: {}", media.getId(), e.getMessage());
            }
        }
        purged += purgeTrashedFolders(
                mediaFolderRepository.findByStatusAndDeletedAtBefore(MediaFolder.Status.TRASHED, cutoff), null);
        return purged;
    }

    @Override
    @Transactional(readOnly = true)
    public TrashSummary getTrashSummary() {
        return new TrashSummary(
                mediaRepository.countByStatus(Media.Status.TRASHED),
                mediaFolderRepository.countByStatus(MediaFolder.Status.TRASHED),
                mediaRepository.sumSizeBytesByStatus(Media.Status.TRASHED));
    }

    @Override
    @Transactional
    public BulkDeleteResult bulkDeleteMedia(List<Long> ids) {
        int deleted = 0;
        List<String> errors = new ArrayList<>();
        for (Long id : ids) {
            try {
                deleteMedia(id);
                deleted++;
            } catch (IllegalArgumentException e) {
                errors.add("#" + id + ": " + e.getMessage());
            }
        }
        return new BulkDeleteResult(deleted, errors);
    }

    @Override
    @Transactional
    public void bulkMoveToFolder(List<Long> ids, Long folderId) {
        MediaFolder folder = null;
        if (folderId != null) {
            folder = mediaFolderRepository.findById(folderId)
                    .orElseThrow(() -> new IllegalArgumentException("Folder not found."));
        }
        List<Media> mediaItems = mediaRepository.findAllById(ids);
        for (Media media : mediaItems) {
            layout.relocate(media, folder);
        }
        mediaRepository.saveAll(mediaItems);
    }

    private String extractExtension(String originalFilename, String contentType) {
        if (originalFilename != null && originalFilename.contains(".")) {
            String ext = originalFilename.substring(originalFilename.lastIndexOf('.')).toLowerCase(Locale.ROOT);
            if (ext.matches("\\.[a-z0-9]{2,5}")) {
                return ext;
            }
        }
        return switch (contentType.toLowerCase(Locale.ROOT)) {
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
