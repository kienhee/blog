package com.kienhee.blog.config;

import org.springframework.context.annotation.Configuration;
import org.springframework.scheduling.annotation.EnableAsync;
import org.springframework.scheduling.annotation.EnableScheduling;

@Configuration
@EnableAsync
// Scheduling lives here too so there is exactly one place that turns these on.
// The only scheduled job today is the media trash retention sweep, and that job is
// itself gated by app.media.trash.auto-purge-enabled (default false).
@EnableScheduling
public class AsyncConfig {
}
