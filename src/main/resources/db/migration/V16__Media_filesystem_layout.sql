-- ============================================================================
-- V16 — Media stored as a real directory tree (filesystem mirror)
--
--   uploads/<folder-slug>/<child-slug>/<sanitised original name>
--
--   1. media.storage_path  : path relative to uploads/, '/'-separated. NULL only for
--                            rows created before this migration; the data migration
--                            (separate job) fills them. New code always writes it.
--   2. storage_blob.sha256 : no longer UNIQUE — dedupe is off, two uploads with the
--                            same bytes are two blobs with two physical files. The
--                            hash is still stored (and indexed) to *detect* duplicates.
--   3. storage_blob.storage_key : now equals media.storage_path, so it is widened to
--                            the same 700 chars. It stays UNIQUE on purpose: two rows
--                            can never claim the same physical path.
-- ============================================================================

ALTER TABLE media ADD COLUMN storage_path VARCHAR(700) NULL AFTER stored_filename;
CREATE INDEX idx_media_storage_path ON media (storage_path);

ALTER TABLE storage_blob DROP INDEX uq_blob_sha256;
CREATE INDEX idx_blob_sha256 ON storage_blob (sha256);

ALTER TABLE storage_blob MODIFY storage_key VARCHAR(700) NOT NULL;
