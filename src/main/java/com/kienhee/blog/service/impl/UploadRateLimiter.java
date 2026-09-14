package com.kienhee.blog.service.impl;

import org.springframework.stereotype.Component;

import java.time.Duration;
import java.time.Instant;
import java.util.Deque;
import java.util.concurrent.ConcurrentHashMap;
import java.util.concurrent.ConcurrentLinkedDeque;

/**
 * Simple in-memory sliding-window rate limiter for uploads, keyed per uploader email.
 * Good enough for a single-instance deployment; would need a shared store (e.g. Redis)
 * behind a load balancer with multiple app instances.
 */
@Component
public class UploadRateLimiter {

    private static final int MAX_UPLOADS = 20;
    private static final Duration WINDOW = Duration.ofMinutes(10);

    private final ConcurrentHashMap<String, Deque<Instant>> hits = new ConcurrentHashMap<>();

    /**
     * Records an attempt for the given key and returns true if it is allowed
     * (fewer than MAX_UPLOADS recorded within the trailing WINDOW), false if the
     * caller should be rejected.
     */
    public synchronized boolean tryAcquire(String key) {
        if (key == null) {
            key = "anonymous";
        }
        Instant now = Instant.now();
        Deque<Instant> timestamps = hits.computeIfAbsent(key, k -> new ConcurrentLinkedDeque<>());

        while (!timestamps.isEmpty() && Duration.between(timestamps.peekFirst(), now).compareTo(WINDOW) > 0) {
            timestamps.pollFirst();
        }

        if (timestamps.size() >= MAX_UPLOADS) {
            return false;
        }

        timestamps.addLast(now);
        return true;
    }
}
