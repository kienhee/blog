package com.kienhee.blog.service;

import com.kienhee.blog.entity.NewsletterIssue;
import com.kienhee.blog.entity.Subscriber;

import java.util.List;
import java.util.Optional;

/**
 * Self-hosted newsletter with double opt-in, sent through {@link MailService}. Gmail SMTP allows roughly 500
 * recipients a day, which is the practical ceiling for {@link #send} on the default setup.
 */
public interface NewsletterService {

    /** Shown for every signup, so the form never reveals whether an address is already on the list. */
    String SUBSCRIBE_MESSAGE = "Almost there: check your inbox and click the link to confirm your subscription.";

    /**
     * Sends a confirmation link (valid 7 days) unless the address is already confirmed. Throttled quietly per IP
     * and per address; behaves the same for new, pending, unsubscribed and confirmed addresses.
     */
    void subscribe(String email, String ipAddress);

    /** True when the token belonged to a pending signup that is still within its 7 days. */
    boolean confirm(String token);

    Optional<Subscriber> findByUnsubscribeToken(String token);

    /** True when the token is known (unsubscribing twice is fine). */
    boolean unsubscribe(String token);

    long confirmedCount();

    List<Subscriber> allSubscribers();

    List<NewsletterIssue> issues();

    /** @throws IllegalArgumentException when the subscriber doesn't exist */
    void deleteSubscriber(Long id);

    /**
     * Records the issue and emails it (after commit) to every confirmed subscriber, each with their own
     * unsubscribe link. The body is plain text; blank lines separate paragraphs.
     *
     * @throws IllegalArgumentException for an invalid subject/body or when nobody is confirmed yet
     */
    NewsletterIssue send(String subject, String body, String senderEmail);
}
