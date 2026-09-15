package com.kienhee.blog.entity;

public enum SubscriberStatus {
    /** Signed up, confirmation email sent, link not clicked yet. Receives nothing. */
    PENDING,
    /** Clicked the confirmation link: receives issues. */
    CONFIRMED,
    /** Left through an unsubscribe link. Receives nothing until they sign up and confirm again. */
    UNSUBSCRIBED
}
