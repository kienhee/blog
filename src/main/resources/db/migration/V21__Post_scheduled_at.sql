-- When a SCHEDULED post goes live. ScheduledPostPublisher publishes due posts with one conditional UPDATE
-- (status SCHEDULED and scheduled_at <= now), so the index covers exactly that lookup.
-- Existing SCHEDULED posts get no time: they stay unpublished until someone picks one in the editor.
ALTER TABLE posts ADD COLUMN scheduled_at DATETIME NULL AFTER published_at;
CREATE INDEX idx_posts_status_scheduled ON posts (status, scheduled_at);
