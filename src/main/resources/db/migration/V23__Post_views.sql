-- Article view counts: one row per post per day, incremented with an upsert. No IPs, cookies or user ids are
-- stored; bots, prefetches and signed-in staff are not counted (see ViewCountingPolicy).
CREATE TABLE IF NOT EXISTS post_view_daily (
    post_id BIGINT  NOT NULL,
    day     DATE    NOT NULL,
    views   INT     NOT NULL DEFAULT 0,
    PRIMARY KEY (post_id, day),
    CONSTRAINT fk_post_view_daily_post FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE,
    INDEX idx_post_view_daily_day (day)
);
