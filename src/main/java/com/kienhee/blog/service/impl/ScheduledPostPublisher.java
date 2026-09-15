package com.kienhee.blog.service.impl;

import com.kienhee.blog.service.PostService;
import lombok.extern.slf4j.Slf4j;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Component;

import java.time.LocalDateTime;

/**
 * Publishes SCHEDULED posts whose time has come. The work is a single conditional UPDATE
 * ({@link PostService#publishDuePosts}), so running it often, twice, or on several instances never
 * publishes a post twice. Posts go live at most {@code app.posts.publish-check-ms} after their time.
 * Test runs switch it off ({@code app.posts.auto-publish=false}) so it can't race the tests.
 */
@Slf4j
@Component
public class ScheduledPostPublisher {

    private final PostService postService;
    private final boolean enabled;

    public ScheduledPostPublisher(PostService postService,
                                  @Value("${app.posts.auto-publish:true}") boolean enabled) {
        this.postService = postService;
        this.enabled = enabled;
    }

    @Scheduled(fixedDelayString = "${app.posts.publish-check-ms:30000}",
               initialDelayString = "${app.posts.publish-initial-delay-ms:5000}")
    public void publishDuePosts() {
        if (!enabled) {
            return;
        }
        int published = postService.publishDuePosts(LocalDateTime.now());
        if (published > 0) {
            log.info("Published {} scheduled post(s).", published);
        }
    }
}
