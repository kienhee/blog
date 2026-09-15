package com.kienhee.blog.config;

import lombok.Getter;
import lombok.Setter;
import org.springframework.boot.context.properties.ConfigurationProperties;
import org.springframework.stereotype.Component;

/**
 * Retention for the shared Trash (posts, categories, hashtags, comments). Same policy as
 * {@link MediaTrashProperties}: auto purge is off by default so a dev start never destroys anything;
 * the prod profile turns it on.
 */
@Component
@ConfigurationProperties(prefix = "app.trash")
@Getter
@Setter
public class TrashProperties {

    /** Days an item stays in the trash before the sweep may delete it permanently. */
    private int retentionDays = 30;

    /** Master switch for the scheduled sweep. */
    private boolean autoPurgeEnabled = false;

    /** Cron for the sweep; only consulted when {@link #autoPurgeEnabled} is true. */
    private String purgeCron = "0 45 3 * * *";
}
