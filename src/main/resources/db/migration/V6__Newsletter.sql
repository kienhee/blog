-- Self-hosted newsletter: double opt-in subscribers and the issues sent to them (mail goes out through MailService).
-- The subscribers:view|send|delete permissions are part of the catalog in V1__Auth.sql.
CREATE TABLE IF NOT EXISTS subscribers (
    id                 BIGINT       NOT NULL AUTO_INCREMENT PRIMARY KEY,
    email              VARCHAR(150) NOT NULL,
    status             VARCHAR(20)  NOT NULL,          -- PENDING, CONFIRMED, UNSUBSCRIBED
    confirm_token_hash CHAR(64)     NULL,              -- SHA-256 of the emailed confirmation token
    confirm_sent_at    DATETIME     NULL,
    unsubscribe_token  CHAR(43)     NOT NULL,          -- random, long-lived: printed in every issue's unsubscribe link
    created_at         DATETIME     NOT NULL,
    confirmed_at       DATETIME     NULL,
    unsubscribed_at    DATETIME     NULL,
    CONSTRAINT uq_subscribers_email UNIQUE (email),
    CONSTRAINT uq_subscribers_unsubscribe_token UNIQUE (unsubscribe_token),
    CONSTRAINT uq_subscribers_confirm_token UNIQUE (confirm_token_hash),
    INDEX idx_subscribers_status (status)
);

CREATE TABLE IF NOT EXISTS newsletter_issues (
    id         BIGINT       NOT NULL AUTO_INCREMENT PRIMARY KEY,
    subject    VARCHAR(200) NOT NULL,
    body       TEXT         NOT NULL,
    sent_by    BIGINT       NULL,
    recipients INT          NOT NULL,
    sent_at    DATETIME     NOT NULL,
    CONSTRAINT fk_newsletter_issues_sender FOREIGN KEY (sent_by) REFERENCES users (id) ON DELETE SET NULL,
    INDEX idx_newsletter_issues_sent_at (sent_at)
);
