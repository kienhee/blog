-- ============================================================================
-- Auth module: roles, the fixed permission catalog, accounts, password resets.
--
-- Permissions are a closed catalog (35 rows), one per real function of an admin
-- module. The UI assigns them to roles; it never creates permission rows.
-- "Admin" is a system role: full access, cannot be edited or deleted, so the
-- instance can never be locked out of its own panel.
-- ============================================================================

CREATE TABLE IF NOT EXISTS roles (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description VARCHAR(255),
    system_role BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS permissions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    resource VARCHAR(50) NOT NULL,
    action VARCHAR(20) NOT NULL,
    code VARCHAR(80) NOT NULL UNIQUE,
    label VARCHAR(120) NOT NULL,
    CONSTRAINT uq_permission_resource_action UNIQUE (resource, action)
);

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id BIGINT NOT NULL,
    permission_id BIGINT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS users (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address VARCHAR(255),
    bio TEXT,
    avatar_url VARCHAR(500) NULL,
    role_id BIGINT NULL,
    -- PENDING = waiting for an admin, ACTIVE = can sign in, DISABLED = blocked without deleting.
    status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL,
    INDEX idx_users_status (status)
);

-- Single-use password reset links. Only the SHA-256 of the token is stored, so a database leak
-- cannot be turned into working reset links. Deleting a user removes their tokens.
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id          BIGINT      NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT      NOT NULL,
    token_hash  CHAR(64)    NOT NULL,
    expires_at  DATETIME    NOT NULL,
    used_at     DATETIME    NULL,
    created_at  DATETIME    NOT NULL,
    request_ip  VARCHAR(45) NULL,
    CONSTRAINT uq_password_reset_token_hash UNIQUE (token_hash),
    CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_password_reset_user (user_id, used_at)
);

-- ---------------------------------------------------------------------------
-- Permission catalog (35 rows). Display order on the Roles page: see RoleController.
--   dashboard   view
--   posts       view, create, edit, publish, delete   (publish = Published / Scheduled / Archived)
--   categories  view, create, edit, delete
--   hashtags    view, create, edit, delete
--   media       view, create, edit, delete, purge     (delete = to trash, purge = permanent)
--   comments    view, edit, delete                    (edit = moderate: approve, unapprove, spam)
--   users       view, create, edit, delete
--   roles       view, create, edit, delete            (edit = rename + change permissions)
--   settings    view, edit
--   subscribers view, send, delete
-- ---------------------------------------------------------------------------
INSERT INTO permissions (resource, action, code, label) VALUES
    ('dashboard',   'view',    'dashboard:view',     'View the dashboard'),
    ('posts',       'view',    'posts:view',         'View the post list and open posts'),
    ('posts',       'create',  'posts:create',       'Write new posts'),
    ('posts',       'edit',    'posts:edit',         'Edit posts'),
    ('posts',       'publish', 'posts:publish',      'Publish, schedule or archive posts'),
    ('posts',       'delete',  'posts:delete',       'Delete posts'),
    ('categories',  'view',    'categories:view',    'View categories'),
    ('categories',  'create',  'categories:create',  'Create categories'),
    ('categories',  'edit',    'categories:edit',    'Edit categories'),
    ('categories',  'delete',  'categories:delete',  'Delete categories'),
    ('hashtags',    'view',    'hashtags:view',      'View hashtags'),
    ('hashtags',    'create',  'hashtags:create',    'Create hashtags'),
    ('hashtags',    'edit',    'hashtags:edit',      'Edit hashtags'),
    ('hashtags',    'delete',  'hashtags:delete',    'Delete hashtags'),
    ('media',       'view',    'media:view',         'Browse the media library and trash'),
    ('media',       'create',  'media:create',       'Upload files, create folders, save edited copies'),
    ('media',       'edit',    'media:edit',         'Rename, move, edit images, restore from trash'),
    ('media',       'delete',  'media:delete',       'Move files and folders to the trash'),
    ('media',       'purge',   'media:purge',        'Delete permanently and empty the trash'),
    ('comments',    'view',    'comments:view',      'View comments'),
    ('comments',    'edit',    'comments:edit',      'Moderate comments (approve, unapprove, spam)'),
    ('comments',    'delete',  'comments:delete',    'Delete comments'),
    ('users',       'view',    'users:view',         'View users'),
    ('users',       'create',  'users:create',       'Add users'),
    ('users',       'edit',    'users:edit',         'Edit users, their role and password'),
    ('users',       'delete',  'users:delete',       'Delete users'),
    ('roles',       'view',    'roles:view',         'View roles and their permissions'),
    ('roles',       'create',  'roles:create',       'Create roles'),
    ('roles',       'edit',    'roles:edit',         'Rename roles and change their permissions'),
    ('roles',       'delete',  'roles:delete',       'Delete roles'),
    ('settings',    'view',    'settings:view',      'View settings'),
    ('settings',    'edit',    'settings:edit',      'Change settings'),
    ('subscribers', 'view',    'subscribers:view',   'View newsletter subscribers and past issues'),
    ('subscribers', 'send',    'subscribers:send',   'Send a newsletter issue to confirmed subscribers'),
    ('subscribers', 'delete',  'subscribers:delete', 'Remove subscribers');

INSERT INTO roles (name, slug, description, system_role) VALUES
    ('Admin', 'admin', 'Full access to every module. System role: cannot be edited or deleted.', TRUE),
    ('User',  'user',  'Contributor: writes and edits posts (not publish or delete), uploads media, reads categories, hashtags and comments.', FALSE);

-- Admin: every permission (the UI shows it fully ticked and locked).
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.slug = 'admin';

-- User: a fixed contributor set.
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
    ON p.code IN ('dashboard:view',
                  'posts:view', 'posts:create', 'posts:edit',
                  'categories:view', 'hashtags:view',
                  'media:view', 'media:create',
                  'comments:view')
WHERE r.slug = 'user';
