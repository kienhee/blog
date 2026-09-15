package com.kienhee.blog.service.impl;

import java.time.Duration;
import java.time.Instant;
import java.util.ArrayDeque;
import java.util.Deque;
import java.util.HashMap;
import java.util.Map;

/**
 * Small in-memory sliding-window limiter (single instance only, like {@link UploadRateLimiter}).
 * Keys that go quiet are dropped when their window empties, so the map doesn't grow forever.
 */
public class SlidingWindowLimiter {

    private final int max;
    private final Duration window;
    private final Map<String, Deque<Instant>> hits = new HashMap<>();

    public SlidingWindowLimiter(int max, Duration window) {
        this.max = max;
        this.window = window;
    }

    public synchronized boolean tryAcquire(String key) {
        String k = key == null ? "unknown" : key;
        Instant now = Instant.now();
        Deque<Instant> timestamps = hits.computeIfAbsent(k, x -> new ArrayDeque<>());
        while (!timestamps.isEmpty() && Duration.between(timestamps.peekFirst(), now).compareTo(window) > 0) {
            timestamps.pollFirst();
        }
        if (timestamps.size() >= max) {
            return false;
        }
        timestamps.addLast(now);
        return true;
    }
}
