-- Reader comments on posts, one level of replies (a reply to a reply is stored under the top-level comment).
-- Guests leave a name + email (never shown publicly); signed-in staff are linked through user_id.
-- Deleting a post or a parent comment removes its comments/replies.
CREATE TABLE IF NOT EXISTS comments (
    id           BIGINT       NOT NULL AUTO_INCREMENT PRIMARY KEY,
    post_id      BIGINT       NOT NULL,
    parent_id    BIGINT       NULL,
    user_id      BIGINT       NULL,
    author_name  VARCHAR(100) NOT NULL,
    author_email VARCHAR(150) NOT NULL,
    content      TEXT         NOT NULL,
    status       VARCHAR(20)  NOT NULL,
    ip_address   VARCHAR(45)  NULL,
    user_agent   VARCHAR(255) NULL,
    created_at   DATETIME     NOT NULL,
    updated_at   DATETIME     NULL,
    CONSTRAINT fk_comments_post   FOREIGN KEY (post_id)   REFERENCES posts (id)    ON DELETE CASCADE,
    CONSTRAINT fk_comments_parent FOREIGN KEY (parent_id) REFERENCES comments (id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_user   FOREIGN KEY (user_id)   REFERENCES users (id)    ON DELETE SET NULL,
    INDEX idx_comments_post_status (post_id, status, created_at),
    INDEX idx_comments_status (status, created_at)
);

-- New guest comments wait for approval unless this is switched off in Settings.
INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES ('comments.moderation', 'true');
