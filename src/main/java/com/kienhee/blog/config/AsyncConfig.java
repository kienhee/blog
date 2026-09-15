package com.kienhee.blog.config;

import org.springframework.context.annotation.Configuration;
import org.springframework.scheduling.annotation.EnableAsync;
import org.springframework.scheduling.annotation.EnableScheduling;

@Configuration
@EnableAsync
// Scheduling lives here too so there is exactly one place that turns these on.
// Scheduled jobs: ScheduledPostPublisher (every 30 s), MediaTrashPurgeJob and TrashPurgeJob (daily,
// both gated by their auto-purge-enabled flags, default false outside the prod profile).
@EnableScheduling
public class AsyncConfig {
}
