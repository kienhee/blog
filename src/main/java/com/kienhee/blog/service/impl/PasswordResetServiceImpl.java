package com.kienhee.blog.service.impl;

import com.kienhee.blog.config.AppMailProperties;
import com.kienhee.blog.entity.PasswordResetToken;
import com.kienhee.blog.entity.User;
import com.kienhee.blog.repository.PasswordResetTokenRepository;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.service.MailService;
import com.kienhee.blog.service.PasswordResetService;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.transaction.support.TransactionSynchronization;
import org.springframework.transaction.support.TransactionSynchronizationManager;

import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.security.SecureRandom;
import java.time.Duration;
import java.time.LocalDateTime;
import java.util.Base64;
import java.util.HexFormat;
import java.util.Locale;
import java.util.Map;
import java.util.Optional;
import java.util.regex.Pattern;

@Slf4j
@Service
@RequiredArgsConstructor
public class PasswordResetServiceImpl implements PasswordResetService {

    static final Duration TOKEN_TTL = Duration.ofMinutes(30);
    private static final Pattern TOKEN_FORMAT = Pattern.compile("^[A-Za-z0-9_-]{43}$");

    private final PasswordResetTokenRepository tokenRepository;
    private final UserRepository userRepository;
    private final PasswordEncoder passwordEncoder;
    private final MailService mailService;
    private final AppMailProperties mailProperties;

    // Throttle quietly: a throttled request looks exactly like a successful one to the caller.
    private final SlidingWindowLimiter perEmail = new SlidingWindowLimiter(3, Duration.ofMinutes(15));
    private final SlidingWindowLimiter perIp = new SlidingWindowLimiter(10, Duration.ofMinutes(15));
    private final SecureRandom random = new SecureRandom();

    @Override
    @Transactional
    public void requestReset(String email, String ipAddress) {
        String normalized = email == null ? "" : email.trim().toLowerCase(Locale.ROOT);
        if (normalized.isEmpty()) {
            return;
        }
        if (!perIp.tryAcquire(ipAddress) || !perEmail.tryAcquire(normalized)) {
            log.info("Password reset request throttled");
            return;
        }
        Optional<User> found = userRepository.findByEmail(normalized);
        if (found.isEmpty()) {
            return;
        }
        User user = found.get();
        LocalDateTime now = LocalDateTime.now();

        tokenRepository.invalidateUnused(user.getId(), now);
        String token = newToken();
        tokenRepository.save(PasswordResetToken.builder()
                .user(userRepository.getReferenceById(user.getId()))
                .tokenHash(sha256(token))
                .expiresAt(now.plus(TOKEN_TTL))
                .createdAt(now)
                .requestIp(ipAddress != null && ipAddress.length() > 45 ? ipAddress.substring(0, 45) : ipAddress)
                .build());

        String resetUrl = mailProperties.getBaseUrl().replaceAll("/+$", "") + "/auth/reset?token=" + token;
        Map<String, Object> variables = Map.of(
                "name", user.getFullName() != null ? user.getFullName() : "there",
                "resetUrl", resetUrl,
                "minutes", TOKEN_TTL.toMinutes(),
                "siteName", mailProperties.getFromName());
        String to = user.getEmail();
        // Only mail a link whose token row actually committed.
        TransactionSynchronizationManager.registerSynchronization(new TransactionSynchronization() {
            @Override
            public void afterCommit() {
                mailService.send(to, "Reset your " + mailProperties.getFromName() + " password", "password-reset", variables);
            }
        });
    }

    @Override
    @Transactional(readOnly = true)
    public Optional<User> findUserForToken(String token) {
        return usableToken(token).map(PasswordResetToken::getUser);
    }

    @Override
    @Transactional
    public void resetPassword(String token, String newPassword) {
        PasswordResetToken resetToken = usableToken(token)
                .orElseThrow(() -> new IllegalArgumentException("This reset link is invalid or has expired. Request a new one."));
        User user = userRepository.findById(resetToken.getUser().getId())
                .orElseThrow(() -> new IllegalArgumentException("This reset link is invalid or has expired. Request a new one."));
        user.setPassword(passwordEncoder.encode(newPassword));
        userRepository.save(user);
        // Uses up this link and every other open link of the user.
        tokenRepository.invalidateUnused(user.getId(), LocalDateTime.now());
        log.info("Password reset completed for user {}", user.getId());
    }

    private Optional<PasswordResetToken> usableToken(String token) {
        if (token == null || !TOKEN_FORMAT.matcher(token).matches()) {
            return Optional.empty();
        }
        LocalDateTime now = LocalDateTime.now();
        return tokenRepository.findByTokenHashWithUser(sha256(token))
                .filter(t -> t.getUsedAt() == null && t.getExpiresAt().isAfter(now));
    }

    /** 32 random bytes, URL-safe Base64 without padding: always 43 characters. */
    private String newToken() {
        byte[] bytes = new byte[32];
        random.nextBytes(bytes);
        return Base64.getUrlEncoder().withoutPadding().encodeToString(bytes);
    }

    static String sha256(String value) {
        try {
            return HexFormat.of().formatHex(MessageDigest.getInstance("SHA-256").digest(value.getBytes(StandardCharsets.UTF_8)));
        } catch (NoSuchAlgorithmException e) {
            throw new IllegalStateException(e);
        }
    }
}
