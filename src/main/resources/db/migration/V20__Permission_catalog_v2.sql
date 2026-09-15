-- Permission catalog v2 + two seeded roles (admin, user).
--
-- Permissions now follow what each admin module actually does (see RoleController for display order):
--   dashboard  view
--   posts      view, create, edit, publish, delete     (publish = set Published / Scheduled / Archived)
--   categories view, create, edit, delete
--   hashtags   view, create, edit, delete
--   media      view, create, edit, delete, purge       (delete = to trash, purge = permanent / empty trash)
--   comments   view, edit, delete                      (edit = moderate: approve, unapprove, spam)
--   users      view, create, edit, delete
--   roles      view, create, edit, delete              (edit = rename + change permissions)
--   settings   view, edit
-- 32 permissions in total. The UI assigns them to roles; it never creates permission rows.

-- 1) Permissions no feature uses. Their grants disappear with them (ON DELETE CASCADE).
DELETE FROM permissions WHERE code IN ('comments:create', 'settings:create', 'settings:delete');

-- 2) Functions that had no permission of their own.
INSERT INTO permissions (resource, action, code, label) VALUES
    ('dashboard', 'view',    'dashboard:view', 'View the dashboard'),
    ('posts',     'publish', 'posts:publish',  'Publish, schedule or archive posts'),
    ('media',     'purge',   'media:purge',    'Delete permanently and empty the trash'),
    ('roles',     'view',    'roles:view',     'View roles and their permissions'),
    ('roles',     'create',  'roles:create',   'Create roles'),
    ('roles',     'edit',    'roles:edit',     'Rename roles and change their permissions'),
    ('roles',     'delete',  'roles:delete',   'Delete roles')
AS new_row
ON DUPLICATE KEY UPDATE label = new_row.label;

-- 3) Labels that say what the permission lets someone do.
UPDATE permissions SET label = CASE code
    WHEN 'posts:view'        THEN 'View the post list and open posts'
    WHEN 'posts:create'      THEN 'Write new posts'
    WHEN 'posts:edit'        THEN 'Edit posts'
    WHEN 'posts:delete'      THEN 'Delete posts'
    WHEN 'categories:view'   THEN 'View categories'
    WHEN 'categories:create' THEN 'Create categories'
    WHEN 'categories:edit'   THEN 'Edit categories'
    WHEN 'categories:delete' THEN 'Delete categories'
    WHEN 'hashtags:view'     THEN 'View hashtags'
    WHEN 'hashtags:create'   THEN 'Create hashtags'
    WHEN 'hashtags:edit'     THEN 'Edit hashtags'
    WHEN 'hashtags:delete'   THEN 'Delete hashtags'
    WHEN 'media:view'        THEN 'Browse the media library and trash'
    WHEN 'media:create'      THEN 'Upload files, create folders, save edited copies'
    WHEN 'media:edit'        THEN 'Rename, move, edit images, restore from trash'
    WHEN 'media:delete'      THEN 'Move files and folders to the trash'
    WHEN 'comments:view'     THEN 'View comments'
    WHEN 'comments:edit'     THEN 'Moderate comments (approve, unapprove, spam)'
    WHEN 'comments:delete'   THEN 'Delete comments'
    WHEN 'users:view'        THEN 'View users'
    WHEN 'users:create'      THEN 'Add users'
    WHEN 'users:edit'        THEN 'Edit users, their role and password'
    WHEN 'users:delete'      THEN 'Delete users'
    WHEN 'settings:view'     THEN 'View settings'
    WHEN 'settings:edit'     THEN 'Change settings'
    ELSE label
END;

-- 4) Roles. The Owner row becomes Admin in place (same id), so its users keep full access.
UPDATE roles
SET name = 'Admin', slug = 'admin', system_role = TRUE,
    description = 'Full access to every module. System role: cannot be edited or deleted.'
WHERE slug = 'owner';

INSERT INTO roles (name, slug, description, system_role)
SELECT 'User', 'user',
       'Contributor: writes and edits posts (not publish or delete), uploads media, reads categories, hashtags and comments.',
       FALSE
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE slug = 'user');

-- Accounts on the retired roles move to User, then those roles go (their grants cascade).
UPDATE users
SET role_id = (SELECT id FROM roles WHERE slug = 'user')
WHERE role_id IN (SELECT id FROM roles WHERE slug IN ('editor', 'author', 'viewer'));

DELETE FROM roles WHERE slug IN ('editor', 'author', 'viewer');

-- 5) Grants. Admin: everything (it is a system role; the UI shows it fully ticked and locked).
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.slug = 'admin';

-- User: a fixed contributor set, rebuilt so re-running on an edited role is predictable.
DELETE rp FROM role_permissions rp JOIN roles r ON r.id = rp.role_id WHERE r.slug = 'user';

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
    ON p.code IN ('dashboard:view',
                  'posts:view', 'posts:create', 'posts:edit',
                  'categories:view', 'hashtags:view',
                  'media:view', 'media:create',
                  'comments:view')
WHERE r.slug = 'user';
