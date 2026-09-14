package com.kienhee.blog.service.impl;

import com.kienhee.blog.config.MediaTrashProperties;
import com.kienhee.blog.service.MediaService;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Component;

/**
 * Retention sweep for the media trash: permanently deletes anything that has been in the
 * trash longer than {@code app.media.trash.retention-days}.
 *
 * <p>The bean is always registered but the sweep is a no-op unless
 * {@code app.media.trash.auto-purge-enabled} is true (default false). That default is
 * deliberate: booting the app for development must never destroy files on its own.
 */
@Slf4j
@Component
@RequiredArgsConstructor
public class MediaTrashPurgeJob {

    private final MediaService mediaService;
    private final MediaTrashProperties properties;

    @Scheduled(cron = "${app.media.trash.purge-cron:0 30 3 * * *}")
    public void sweep() {
        if (!properties.isAutoPurgeEnabled()) {
            return;
        }
        int purged = mediaService.purgeExpired(properties.getRetentionDays());
        if (purged > 0) {
            log.info("Media trash sweep purged {} item(s) older than {} day(s).",
                    purged, properties.getRetentionDays());
        }
    }
}
