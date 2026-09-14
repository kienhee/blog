package com.kienhee.blog.service.impl;

import org.springframework.stereotype.Component;

import java.time.Duration;
import java.time.Instant;
import java.util.ArrayDeque;
import java.util.Deque;
import java.util.HashMap;
import java.util.Map;

/**
 * In-memory sliding window for public comments, keyed by client IP (same single-instance caveat as
 * {@link UploadRateLimiter}).
 */
@Component
public class CommentRateLimiter {

    static final int MAX_COMMENTS = 5;
    static final Duration WINDOW = Duration.ofMinutes(10);

    private final Map<String, Deque<Instant>> hits = new HashMap<>();

    public synchronized boolean tryAcquire(String key) {
        String k = key == null ? "unknown" : key;
        Instant now = Instant.now();
        Deque<Instant> timestamps = hits.computeIfAbsent(k, x -> new ArrayDeque<>());
        while (!timestamps.isEmpty() && Duration.between(timestamps.peekFirst(), now).compareTo(WINDOW) > 0) {
            timestamps.pollFirst();
        }
        if (timestamps.size() >= MAX_COMMENTS) {
            return false;
        }
        timestamps.addLast(now);
        return true;
    }
}
