-- Trash for posts, categories, hashtags and comments (media has had its own trash since V14).
-- A row with deleted_at set is in the trash: entities carry @SQLRestriction("deleted_at IS NULL"), so every JPA
-- query (admin lists, public site, dashboard) skips it. Only TrashService reads, restores and purges it (native SQL).
ALTER TABLE posts      ADD COLUMN deleted_at DATETIME NULL, ADD INDEX idx_posts_deleted_at (deleted_at);
ALTER TABLE categories ADD COLUMN deleted_at DATETIME NULL, ADD INDEX idx_categories_deleted_at (deleted_at);
ALTER TABLE hashtags   ADD COLUMN deleted_at DATETIME NULL, ADD INDEX idx_hashtags_deleted_at (deleted_at);
ALTER TABLE comments   ADD COLUMN deleted_at DATETIME NULL, ADD INDEX idx_comments_deleted_at (deleted_at);
