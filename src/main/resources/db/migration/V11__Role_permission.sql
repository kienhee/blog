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

-- Fixed permission catalog: 7 resources x 4 actions. Admins assign these to roles,
-- they never create new permission rows from the UI.
INSERT INTO permissions (resource, action, code, label) VALUES
    ('posts', 'view', 'posts:view', 'Posts — view'),
    ('posts', 'create', 'posts:create', 'Posts — create'),
    ('posts', 'edit', 'posts:edit', 'Posts — edit'),
    ('posts', 'delete', 'posts:delete', 'Posts — delete'),
    ('categories', 'view', 'categories:view', 'Categories — view'),
    ('categories', 'create', 'categories:create', 'Categories — create'),
    ('categories', 'edit', 'categories:edit', 'Categories — edit'),
    ('categories', 'delete', 'categories:delete', 'Categories — delete'),
    ('hashtags', 'view', 'hashtags:view', 'Hashtags — view'),
    ('hashtags', 'create', 'hashtags:create', 'Hashtags — create'),
    ('hashtags', 'edit', 'hashtags:edit', 'Hashtags — edit'),
    ('hashtags', 'delete', 'hashtags:delete', 'Hashtags — delete'),
    ('media', 'view', 'media:view', 'Media — view'),
    ('media', 'create', 'media:create', 'Media — create'),
    ('media', 'edit', 'media:edit', 'Media — edit'),
    ('media', 'delete', 'media:delete', 'Media — delete'),
    ('comments', 'view', 'comments:view', 'Comments — view'),
    ('comments', 'create', 'comments:create', 'Comments — create'),
    ('comments', 'edit', 'comments:edit', 'Comments — edit'),
    ('comments', 'delete', 'comments:delete', 'Comments — delete'),
    ('users', 'view', 'users:view', 'Users — view'),
    ('users', 'create', 'users:create', 'Users — create'),
    ('users', 'edit', 'users:edit', 'Users — edit'),
    ('users', 'delete', 'users:delete', 'Users — delete'),
    ('settings', 'view', 'settings:view', 'Settings — view'),
    ('settings', 'create', 'settings:create', 'Settings — create'),
    ('settings', 'edit', 'settings:edit', 'Settings — edit'),
    ('settings', 'delete', 'settings:delete', 'Settings — delete');

-- Seed roles. "Owner" is a system role: full access, cannot be deleted or stripped
-- of permissions, so the instance can never be locked out of its own admin panel.
INSERT INTO roles (name, slug, description, system_role) VALUES
    ('Owner', 'owner', 'Full access to everything.', TRUE),
    ('Editor', 'editor', 'Manages content but cannot manage users or settings.', FALSE),
    ('Author', 'author', 'Creates and edits content, cannot delete.', FALSE),
    ('Viewer', 'viewer', 'Read-only access.', FALSE);

-- Owner: every permission.
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.slug = 'owner';

-- Editor: everything on content, view-only on users/settings.
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
  ON (p.resource IN ('posts', 'categories', 'hashtags', 'media', 'comments')
      OR (p.resource IN ('users', 'settings') AND p.action = 'view'))
WHERE r.slug = 'editor';

-- Author: view/create/edit on content, no delete, no users/settings.
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
  ON p.resource IN ('posts', 'categories', 'hashtags', 'media', 'comments')
     AND p.action IN ('view', 'create', 'edit')
WHERE r.slug = 'author';

-- Viewer: view only, everywhere.
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.action = 'view'
WHERE r.slug = 'viewer';
