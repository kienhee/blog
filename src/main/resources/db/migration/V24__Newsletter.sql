-- Self-hosted newsletter: double opt-in subscribers and the issues sent to them (mail goes out through MailService).
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

-- Newsletter permissions (catalog grows from 32 to 35). Admin gets them; the user role does not.
INSERT INTO permissions (resource, action, code, label) VALUES
    ('subscribers', 'view',   'subscribers:view',   'View newsletter subscribers and past issues'),
    ('subscribers', 'send',   'subscribers:send',   'Send a newsletter issue to confirmed subscribers'),
    ('subscribers', 'delete', 'subscribers:delete', 'Remove subscribers')
AS new_row
ON DUPLICATE KEY UPDATE label = new_row.label;

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.resource = 'subscribers' WHERE r.slug = 'admin';
