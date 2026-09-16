-- Generic key/value site settings.
-- The settings page saves every field it posts as settings[<key>], so adding a setting later needs
-- no migration: only the defaults for the fields that exist today are seeded here.
-- (`key` is a reserved word in MySQL, hence setting_key / setting_value.)
CREATE TABLE IF NOT EXISTS site_settings (
    setting_key   VARCHAR(100) NOT NULL PRIMARY KEY,
    setting_value TEXT         NULL,
    updated_at    TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES
    ('site.title', 'Kienhee'),
    ('site.domain', 'kienhee.com'),
    ('site.meta_description', 'AI guides and news, written and tested by one person.'),
    ('blog.posts_per_page', '10'),
    -- New guest comments wait for approval unless this is switched off in Settings.
    ('comments.moderation', 'true');
