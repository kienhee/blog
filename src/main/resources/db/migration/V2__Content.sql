-- ============================================================================
-- Content module: categories, hashtags, posts.
--
-- Posts, categories and hashtags are soft-deleted: a row with deleted_at set is in the trash.
-- The entities carry @SQLRestriction("deleted_at IS NULL"), so every JPA query (admin lists,
-- public site, dashboard) skips it; only TrashService reads, restores and purges it (native SQL).
-- ============================================================================

CREATE TABLE IF NOT EXISTS categories (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(170) NOT NULL UNIQUE,
    description TEXT,
    parent_id BIGINT NULL,
    visible BOOLEAN NOT NULL DEFAULT TRUE,
    deleted_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_categories_deleted_at (deleted_at)
);

CREATE TABLE IF NOT EXISTS hashtags (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description TEXT,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    deleted_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_hashtags_deleted_at (deleted_at)
);

-- scheduled_at: when a SCHEDULED post goes live. ScheduledPostPublisher publishes due posts with one
-- conditional UPDATE (status SCHEDULED and scheduled_at <= now), which idx_posts_status_scheduled covers.
CREATE TABLE IF NOT EXISTS posts (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    excerpt VARCHAR(500),
    content LONGTEXT NOT NULL,
    cover_image VARCHAR(500),
    status VARCHAR(20) NOT NULL DEFAULT 'DRAFT',
    category_id BIGINT NOT NULL,
    author_id BIGINT NOT NULL,
    seo_title VARCHAR(60),
    seo_description VARCHAR(160),
    published_at TIMESTAMP NULL,
    scheduled_at DATETIME NULL,
    deleted_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_posts_category FOREIGN KEY (category_id) REFERENCES categories(id),
    CONSTRAINT fk_posts_author FOREIGN KEY (author_id) REFERENCES users(id),
    INDEX idx_posts_status_scheduled (status, scheduled_at),
    INDEX idx_posts_deleted_at (deleted_at)
);

CREATE TABLE IF NOT EXISTS post_hashtags (
    post_id BIGINT NOT NULL,
    hashtag_id BIGINT NOT NULL,
    PRIMARY KEY (post_id, hashtag_id),
    CONSTRAINT fk_post_hashtags_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_post_hashtags_hashtag FOREIGN KEY (hashtag_id) REFERENCES hashtags(id) ON DELETE CASCADE
);

-- Article view counts: one row per post per day, incremented with an upsert. No IPs, cookies or user
-- ids are stored; bots, prefetches and signed-in staff are not counted (see ViewCountingPolicy).
CREATE TABLE IF NOT EXISTS post_view_daily (
    post_id BIGINT  NOT NULL,
    day     DATE    NOT NULL,
    views   INT     NOT NULL DEFAULT 0,
    PRIMARY KEY (post_id, day),
    CONSTRAINT fk_post_view_daily_post FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE,
    INDEX idx_post_view_daily_day (day)
);
