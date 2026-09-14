ALTER TABLE users ADD COLUMN role_id BIGINT NULL AFTER avatar_url;
ALTER TABLE users ADD CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL;

-- Every existing account becomes an Owner, otherwise turning permission checks on
-- would immediately lock everyone (including the only admin) out of the panel.
UPDATE users SET role_id = (SELECT id FROM roles WHERE slug = 'owner') WHERE role_id IS NULL;
