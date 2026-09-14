-- ============================================================================
-- V13 — Media module P0 foundation
--   1. storage_blob        : content-addressable physical storage (dedupe base)
--   2. media               : + blob_id / sha256 / status / deleted_at (soft delete)
--   3. media_folders       : flat -> nested (materialized path), soft delete,
--                            slug uniqueness scoped to the parent folder
--   4. user_storage_quota  : per-user byte budget, backfilled from existing media
-- Old columns are intentionally kept so the current MediaService/MediaController
-- keeps working unchanged.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 1. storage_blob
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS storage_blob (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    sha256 CHAR(64) NOT NULL,
    storage_key VARCHAR(255) NOT NULL,
    storage_provider VARCHAR(30) NOT NULL DEFAULT 'LOCAL',
    size_bytes BIGINT NOT NULL,
    content_type VARCHAR(100) NOT NULL,
    ref_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_blob_sha256 UNIQUE (sha256),
    CONSTRAINT uq_blob_storage_key UNIQUE (storage_key),
    INDEX idx_blob_refcount (ref_count)
);

-- ---------------------------------------------------------------------------
-- 2. media — additive only
-- ---------------------------------------------------------------------------
ALTER TABLE media ADD COLUMN blob_id BIGINT NULL AFTER folder_id;
ALTER TABLE media ADD COLUMN sha256 CHAR(64) NULL AFTER blob_id;
ALTER TABLE media ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE' AFTER sha256;
ALTER TABLE media ADD COLUMN deleted_at TIMESTAMP NULL AFTER status;

ALTER TABLE media ADD CONSTRAINT fk_media_blob FOREIGN KEY (blob_id) REFERENCES storage_blob(id);
CREATE INDEX idx_media_sha256 ON media (sha256);
CREATE INDEX idx_media_status ON media (status, deleted_at);

-- Existing rows predate soft delete; DEFAULT already gave them 'ACTIVE',
-- this is only a guard in case the column was created some other way.
UPDATE media SET status = 'ACTIVE' WHERE status IS NULL OR status = '';

-- ---------------------------------------------------------------------------
-- 3. media_folders — flat -> nested
-- ---------------------------------------------------------------------------
ALTER TABLE media_folders ADD COLUMN parent_id BIGINT NULL AFTER slug;
ALTER TABLE media_folders ADD COLUMN path VARCHAR(500) NOT NULL DEFAULT '/' AFTER parent_id;
ALTER TABLE media_folders ADD COLUMN depth INT NOT NULL DEFAULT 0 AFTER path;
ALTER TABLE media_folders ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE' AFTER depth;
ALTER TABLE media_folders ADD COLUMN deleted_at TIMESTAMP NULL AFTER status;

ALTER TABLE media_folders
    ADD CONSTRAINT fk_media_folder_parent FOREIGN KEY (parent_id)
    REFERENCES media_folders(id) ON DELETE RESTRICT;

CREATE INDEX idx_folder_path ON media_folders (path);
CREATE INDEX idx_folder_parent ON media_folders (parent_id);

-- Backfill: every pre-existing folder was flat, so it becomes a root folder
-- whose materialized path is its own id: '/<id>/', depth 0.
UPDATE media_folders SET path = CONCAT('/', id, '/'), depth = 0, status = 'ACTIVE';

-- slug was declared UNIQUE inline in V9, so MySQL named the index `slug`.
-- Nested folders make a globally unique slug wrong: /photos/2025 and
-- /docs/2025 are both legitimate. Scope uniqueness to the parent instead.
ALTER TABLE media_folders DROP INDEX slug;
ALTER TABLE media_folders ADD CONSTRAINT uq_folder_parent_slug UNIQUE (parent_id, slug);

-- ---------------------------------------------------------------------------
-- 4. user_storage_quota
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_storage_quota (
    user_id BIGINT NOT NULL PRIMARY KEY,
    quota_bytes BIGINT NOT NULL DEFAULT 1073741824,
    used_bytes BIGINT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_storage_quota_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
);

-- One row per user. LEFT JOIN so accounts that never uploaded still get a row
-- (with 0) — the reserve query updates rows, it never inserts them.
INSERT INTO user_storage_quota (user_id, quota_bytes, used_bytes)
SELECT u.id, 1073741824, COALESCE(SUM(m.size_bytes), 0)
FROM users u
LEFT JOIN media m ON m.uploaded_by = u.id
GROUP BY u.id
ON DUPLICATE KEY UPDATE used_bytes = VALUES(used_bytes);
