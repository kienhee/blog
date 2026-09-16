-- ============================================================================
-- Media module: folders (nested, materialized path), files, physical blobs, per-user quota.
--
-- Files live under app.upload.dir at <ancestor folder slugs>/<sanitised original name>, which is
-- both media.storage_path and storage_blob.storage_key; thumbnails are flat in .thumbnails/.
-- Files and folders have their own trash: status TRASHED + a shared deleted_at per delete batch.
-- sha256 is stored and indexed to *detect* duplicates, but is not unique: two uploads of the same
-- bytes are two blobs with two physical files.
-- ============================================================================

CREATE TABLE IF NOT EXISTS storage_blob (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    sha256 CHAR(64) NOT NULL,
    storage_key VARCHAR(700) NOT NULL,
    storage_provider VARCHAR(30) NOT NULL DEFAULT 'LOCAL',
    size_bytes BIGINT NOT NULL,
    content_type VARCHAR(100) NOT NULL,
    ref_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- UNIQUE on purpose: two rows can never claim the same physical path.
    CONSTRAINT uq_blob_storage_key UNIQUE (storage_key),
    INDEX idx_blob_sha256 (sha256),
    INDEX idx_blob_refcount (ref_count)
);

-- Nested folders: path is the materialized path ('/1/17/'), depth its length. A slug is unique
-- within its parent only — /photos/2025 and /docs/2025 are both legitimate.
CREATE TABLE IF NOT EXISTS media_folders (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(170) NOT NULL,
    parent_id BIGINT NULL,
    path VARCHAR(500) NOT NULL DEFAULT '/',
    depth INT NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_folder_parent_slug UNIQUE (parent_id, slug),
    CONSTRAINT fk_media_folder_parent FOREIGN KEY (parent_id) REFERENCES media_folders(id) ON DELETE RESTRICT,
    INDEX idx_folder_path (path),
    INDEX idx_folder_parent (parent_id),
    INDEX idx_folder_status (status, deleted_at)
);

CREATE TABLE IF NOT EXISTS media (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    storage_path VARCHAR(700) NULL,
    url VARCHAR(500) NOT NULL,
    thumbnail_url VARCHAR(500) NULL,
    content_type VARCHAR(100) NOT NULL,
    size_bytes BIGINT NOT NULL,
    original_size_bytes BIGINT,
    width INT,
    height INT,
    alt_text VARCHAR(255),
    optimized BOOLEAN NOT NULL DEFAULT FALSE,
    uploaded_by BIGINT NOT NULL,
    folder_id BIGINT NULL,
    blob_id BIGINT NULL,
    sha256 CHAR(64) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_media_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users(id),
    CONSTRAINT fk_media_folder FOREIGN KEY (folder_id) REFERENCES media_folders(id) ON DELETE SET NULL,
    CONSTRAINT fk_media_blob FOREIGN KEY (blob_id) REFERENCES storage_blob(id),
    INDEX idx_media_stored_filename (stored_filename),
    INDEX idx_media_storage_path (storage_path),
    INDEX idx_media_sha256 (sha256),
    INDEX idx_media_status (status, deleted_at)
);

-- One row per user; the reserve query updates rows, it never inserts them (see QuotaService).
CREATE TABLE IF NOT EXISTS user_storage_quota (
    user_id BIGINT NOT NULL PRIMARY KEY,
    quota_bytes BIGINT NOT NULL DEFAULT 1073741824,
    used_bytes BIGINT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_storage_quota_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
