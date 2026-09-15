package com.kienhee.blog.security;

import org.springframework.stereotype.Component;

import java.time.Duration;
import java.time.Instant;
import java.util.ArrayDeque;
import java.util.Deque;
import java.util.HashMap;
import java.util.Locale;
import java.util.Map;

/**
 * Brute-force protection for the sign-in form: failed attempts are counted per email and per client IP
 * in a sliding window. Once a limit is reached, {@link LoginThrottleFilter} refuses further attempts
 * for that email / IP until old failures age out — even with the right password.
 * In-memory, so single-instance only (same caveat as the upload and comment limiters).
 */
@Component
public class LoginAttemptService {

    static final int MAX_FAILURES_PER_EMAIL = 10;
    static final int MAX_FAILURES_PER_IP = 30;
    static final Duration WINDOW = Duration.ofMinutes(10);

    private final Map<String, Deque<Instant>> failures = new HashMap<>();

    public synchronized boolean isBlocked(String email, String ip) {
        return count(emailKey(email)) >= MAX_FAILURES_PER_EMAIL || count(ipKey(ip)) >= MAX_FAILURES_PER_IP;
    }

    public synchronized void recordFailure(String email, String ip) {
        Instant now = Instant.now();
        failures.computeIfAbsent(emailKey(email), k -> new ArrayDeque<>()).addLast(now);
        failures.computeIfAbsent(ipKey(ip), k -> new ArrayDeque<>()).addLast(now);
    }

    /** A successful sign-in clears that email's failures (the IP count keeps aging out on its own). */
    public synchronized void recordSuccess(String email) {
        failures.remove(emailKey(email));
    }

    private int count(String key) {
        Deque<Instant> timestamps = failures.get(key);
        if (timestamps == null) {
            return 0;
        }
        Instant cutoff = Instant.now().minus(WINDOW);
        while (!timestamps.isEmpty() && timestamps.peekFirst().isBefore(cutoff)) {
            timestamps.pollFirst();
        }
        if (timestamps.isEmpty()) {
            failures.remove(key);
            return 0;
        }
        return timestamps.size();
    }

    private static String emailKey(String email) {
        return "email:" + (email == null ? "" : email.trim().toLowerCase(Locale.ROOT));
    }

    private static String ipKey(String ip) {
        return "ip:" + (ip == null ? "unknown" : ip);
    }
}
