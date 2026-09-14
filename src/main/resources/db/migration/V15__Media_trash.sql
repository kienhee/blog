-- ============================================================================
-- V15 — Media trash (soft delete / restore / purge)
--
-- The columns themselves (media.status/deleted_at, media_folders.status/deleted_at)
-- already shipped in V13; this migration only adds what the trash *queries* need:
--   * an index for "everything currently in the trash", per resource
--   * an index for the retention sweep (auto purge scans deleted_at)
-- No data change: existing rows are ACTIVE with deleted_at NULL.
-- ============================================================================

CREATE INDEX idx_folder_status ON media_folders (status, deleted_at);
