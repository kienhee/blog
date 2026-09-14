-- Content dedupe (P0): two media rows backed by identical bytes now share one physical
-- file, so `stored_filename` is no longer one-row-per-file. The UNIQUE index from V7 has
-- to go; uniqueness of the physical object lives in storage_blob.storage_key instead.
ALTER TABLE media DROP INDEX stored_filename;
CREATE INDEX idx_media_stored_filename ON media (stored_filename);
