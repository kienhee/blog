package com.kienhee.blog.service.impl;

import com.kienhee.blog.config.TinifyProperties;
import com.kienhee.blog.storage.FilesystemStorage;
import com.kienhee.blog.entity.Media;
import com.kienhee.blog.repository.MediaRepository;
import com.kienhee.blog.service.MediaOptimizationService;
import com.kienhee.blog.service.TinifyClient;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.scheduling.annotation.Async;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;

@Slf4j
@Service
@RequiredArgsConstructor
public class MediaOptimizationServiceImpl implements MediaOptimizationService {

    private final MediaRepository mediaRepository;
    private final TinifyClient tinifyClient;
    private final TinifyProperties tinifyProperties;
    private final FilesystemStorage storage;
    private final MediaStorageLayout layout;

    @Override
    @Async
    @Transactional
    public void optimizeAsync(Long mediaId) {
        if (!tinifyProperties.isEnabled()) {
            return;
        }

        Media media = mediaRepository.findById(mediaId).orElse(null);
        if (media == null || media.isOptimized() || "image/svg+xml".equalsIgnoreCase(media.getContentType())) {
            return;
        }

        // Resolved through the storage layer: the file lives in its folder's directory now.
        Path target;
        try {
            target = storage.resolveAbsolute(layout.effectivePath(media));
        } catch (RuntimeException e) {
            log.warn("Unsafe or missing storage path for media {}: {}", mediaId, e.getMessage());
            return;
        }

        try {
            byte[] original = Files.readAllBytes(target);
            byte[] compressed = tinifyClient.compress(original, media.getContentType());

            Files.write(target, compressed);

            media.setOriginalSizeBytes((long) original.length);
            media.setSizeBytes(compressed.length);
            media.setOptimized(true);
            mediaRepository.save(media);

            log.info("Optimized media {} via Tinify: {} -> {} bytes", mediaId, original.length, compressed.length);
        } catch (IOException e) {
            log.warn("Could not read/write file on disk while optimizing media {}: {}", mediaId, e.getMessage());
        } catch (Exception e) {
            log.warn("Tinify optimization failed for media {}, keeping original file: {}", mediaId, e.getMessage());
        }
    }
}
