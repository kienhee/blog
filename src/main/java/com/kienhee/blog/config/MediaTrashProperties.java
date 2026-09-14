package com.kienhee.blog.config;

import lombok.Getter;
import lombok.Setter;
import org.springframework.boot.context.properties.ConfigurationProperties;
import org.springframework.stereotype.Component;

/**
 * Settings for the media trash (soft delete) retention policy.
 *
 * <p>Auto purge is <b>off by default on purpose</b>: starting the app for development
 * must never silently destroy files that are only sitting in the trash. Turning it on is
 * an explicit deployment decision.
 */
@Component
@ConfigurationProperties(prefix = "app.media.trash")
@Getter
@Setter
public class MediaTrashProperties {

    /** Days an item stays in the trash before the sweep is allowed to purge it. */
    private int retentionDays = 30;

    /** Master switch for the scheduled retention sweep. */
    private boolean autoPurgeEnabled = false;

    /** Cron for the sweep; only consulted when {@link #autoPurgeEnabled} is true. */
    private String purgeCron = "0 30 3 * * *";
}
