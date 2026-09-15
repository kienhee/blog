-- Single-use password reset links. Only the SHA-256 of the token is stored, so a database leak
-- cannot be turned into working reset links. Deleting a user removes their tokens.
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id          BIGINT      NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT      NOT NULL,
    token_hash  CHAR(64)    NOT NULL,
    expires_at  DATETIME    NOT NULL,
    used_at     DATETIME    NULL,
    created_at  DATETIME    NOT NULL,
    request_ip  VARCHAR(45) NULL,
    CONSTRAINT uq_password_reset_token_hash UNIQUE (token_hash),
    CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_password_reset_user (user_id, used_at)
);
