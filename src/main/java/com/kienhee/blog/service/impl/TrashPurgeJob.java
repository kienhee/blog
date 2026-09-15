package com.kienhee.blog.service.impl;

import com.kienhee.blog.config.TrashProperties;
import com.kienhee.blog.service.TrashService;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Component;

/** Daily retention sweep for the shared Trash; a no-op unless {@code app.trash.auto-purge-enabled}. */
@Slf4j
@Component
@RequiredArgsConstructor
public class TrashPurgeJob {

    private final TrashService trashService;
    private final TrashProperties properties;

    @Scheduled(cron = "${app.trash.purge-cron:0 45 3 * * *}")
    public void sweep() {
        if (!properties.isAutoPurgeEnabled()) {
            return;
        }
        int purged = trashService.purgeExpired(properties.getRetentionDays());
        if (purged > 0) {
            log.info("Trash sweep deleted {} item(s) older than {} day(s).", purged, properties.getRetentionDays());
        }
    }
}
