package com.kienhee.blog.service.impl;

import com.kienhee.blog.config.AppMailProperties;
import com.kienhee.blog.entity.NewsletterIssue;
import com.kienhee.blog.entity.Subscriber;
import com.kienhee.blog.entity.SubscriberStatus;
import com.kienhee.blog.repository.NewsletterIssueRepository;
import com.kienhee.blog.repository.SubscriberRepository;
import com.kienhee.blog.repository.UserRepository;
import com.kienhee.blog.service.MailService;
import com.kienhee.blog.service.NewsletterService;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.transaction.support.TransactionSynchronization;
import org.springframework.transaction.support.TransactionSynchronizationManager;

import java.security.SecureRandom;
import java.time.Duration;
import java.time.LocalDateTime;
import java.util.Arrays;
import java.util.Base64;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.Optional;
import java.util.regex.Pattern;

@Slf4j
@Service
@RequiredArgsConstructor
public class NewsletterServiceImpl implements NewsletterService {

    static final Duration CONFIRM_TTL = Duration.ofDays(7);
    private static final Pattern TOKEN = Pattern.compile("^[A-Za-z0-9_-]{43}$");

    private final SubscriberRepository subscriberRepository;
    private final NewsletterIssueRepository issueRepository;
    private final UserRepository userRepository;
    private final MailService mailService;
    private final AppMailProperties mailProperties;

    private final SlidingWindowLimiter perIp = new SlidingWindowLimiter(10, Duration.ofMinutes(15));
    private final SlidingWindowLimiter perEmail = new SlidingWindowLimiter(3, Duration.ofMinutes(15));
    private final SecureRandom random = new SecureRandom();

    @Override
    @Transactional
    public void subscribe(String email, String ipAddress) {
        String normalized = email == null ? "" : email.trim().toLowerCase(Locale.ROOT);
        if (normalized.isEmpty()) {
            return;
        }
        if (!perIp.tryAcquire(ipAddress) || !perEmail.tryAcquire(normalized)) {
            log.info("Newsletter signup throttled");
            return;
        }
        Subscriber subscriber = subscriberRepository.findByEmail(normalized).orElse(null);
        if (subscriber != null && subscriber.getStatus() == SubscriberStatus.CONFIRMED) {
            return; // already on the list: say nothing different, send nothing
        }

        LocalDateTime now = LocalDateTime.now();
        if (subscriber == null) {
            subscriber = Subscriber.builder().email(normalized).createdAt(now).unsubscribeToken(newToken()).build();
        }
        String token = newToken();
        subscriber.setStatus(SubscriberStatus.PENDING);
        subscriber.setConfirmTokenHash(PasswordResetServiceImpl.sha256(token));
        subscriber.setConfirmSentAt(now);
        subscriber.setUnsubscribedAt(null);
        subscriberRepository.save(subscriber);

        Map<String, Object> variables = Map.of(
                "confirmUrl", baseUrl() + "/subscribe/confirm?token=" + token,
                "siteName", mailProperties.getFromName(),
                "days", CONFIRM_TTL.toDays());
        afterCommit(() -> mailService.send(normalized, "Confirm your " + mailProperties.getFromName() + " subscription",
                "newsletter-confirm", variables));
    }

    @Override
    @Transactional
    public boolean confirm(String token) {
        if (token == null || !TOKEN.matcher(token).matches()) {
            return false;
        }
        LocalDateTime now = LocalDateTime.now();
        return subscriberRepository.findByConfirmTokenHash(PasswordResetServiceImpl.sha256(token))
                .filter(s -> s.getStatus() == SubscriberStatus.PENDING)
                .filter(s -> s.getConfirmSentAt() != null && s.getConfirmSentAt().isAfter(now.minus(CONFIRM_TTL)))
                .map(s -> {
                    s.setStatus(SubscriberStatus.CONFIRMED);
                    s.setConfirmedAt(now);
                    s.setConfirmTokenHash(null);
                    subscriberRepository.save(s);
                    return true;
                })
                .orElse(false);
    }

    @Override
    @Transactional(readOnly = true)
    public Optional<Subscriber> findByUnsubscribeToken(String token) {
        return token == null || !TOKEN.matcher(token).matches() ? Optional.empty() : subscriberRepository.findByUnsubscribeToken(token);
    }

    @Override
    @Transactional
    public boolean unsubscribe(String token) {
        return findByUnsubscribeToken(token).map(s -> {
            if (s.getStatus() != SubscriberStatus.UNSUBSCRIBED) {
                s.setStatus(SubscriberStatus.UNSUBSCRIBED);
                s.setUnsubscribedAt(LocalDateTime.now());
                s.setConfirmTokenHash(null);
                subscriberRepository.save(s);
            }
            return true;
        }).orElse(false);
    }

    @Override
    @Transactional(readOnly = true)
    public long confirmedCount() {
        return subscriberRepository.countByStatus(SubscriberStatus.CONFIRMED);
    }

    @Override
    @Transactional(readOnly = true)
    public List<Subscriber> allSubscribers() {
        return subscriberRepository.findAllByOrderByCreatedAtDesc();
    }

    @Override
    @Transactional(readOnly = true)
    public List<NewsletterIssue> issues() {
        return issueRepository.findAllWithSender();
    }

    @Override
    @Transactional
    public void deleteSubscriber(Long id) {
        Subscriber subscriber = subscriberRepository.findById(id)
                .orElseThrow(() -> new IllegalArgumentException("That subscriber no longer exists."));
        subscriberRepository.delete(subscriber);
    }

    @Override
    @Transactional
    public NewsletterIssue send(String subject, String body, String senderEmail) {
        String cleanSubject = subject == null ? "" : subject.trim();
        String cleanBody = body == null ? "" : body.replace("\r\n", "\n").trim();
        if (cleanSubject.length() < 3 || cleanSubject.length() > 200) {
            throw new IllegalArgumentException("The subject must be 3 to 200 characters.");
        }
        if (cleanBody.length() < 10 || cleanBody.length() > 20000) {
            throw new IllegalArgumentException("Write at least a few words (up to 20,000 characters).");
        }
        List<Subscriber> recipients = subscriberRepository.findByStatusOrderByIdAsc(SubscriberStatus.CONFIRMED);
        if (recipients.isEmpty()) {
            throw new IllegalArgumentException("There are no confirmed subscribers yet.");
        }

        NewsletterIssue issue = issueRepository.save(NewsletterIssue.builder()
                .subject(cleanSubject)
                .body(cleanBody)
                .sentBy(senderEmail == null ? null : userRepository.findByEmail(senderEmail.trim().toLowerCase(Locale.ROOT)).orElse(null))
                .recipients(recipients.size())
                .sentAt(LocalDateTime.now())
                .build());

        List<String> paragraphs = Arrays.stream(cleanBody.split("\\n\\s*\\n")).map(String::trim).filter(p -> !p.isEmpty()).toList();
        List<String[]> targets = recipients.stream().map(r -> new String[]{r.getEmail(), r.getUnsubscribeToken()}).toList();
        String base = baseUrl();
        String siteName = mailProperties.getFromName();
        afterCommit(() -> targets.forEach(target -> mailService.send(target[0], cleanSubject, "newsletter-issue", Map.of(
                "subject", cleanSubject,
                "paragraphs", paragraphs,
                "unsubscribeUrl", base + "/subscribe/unsubscribe?token=" + target[1],
                "siteName", siteName))));
        return issue;
    }

    private String baseUrl() {
        return mailProperties.getBaseUrl().replaceAll("/+$", "");
    }

    private String newToken() {
        byte[] bytes = new byte[32];
        random.nextBytes(bytes);
        return Base64.getUrlEncoder().withoutPadding().encodeToString(bytes);
    }

    /** Mail only what actually committed. */
    private static void afterCommit(Runnable work) {
        TransactionSynchronizationManager.registerSynchronization(new TransactionSynchronization() {
            @Override
            public void afterCommit() {
                work.run();
            }
        });
    }
}
