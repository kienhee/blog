-- Account status. Existing accounts stay usable (ACTIVE). New sign-ups after the first account wait for an admin
-- (PENDING); an admin can also block an account without deleting it (DISABLED). Only ACTIVE accounts can sign in.
ALTER TABLE users ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE' AFTER role_id,
                  ADD INDEX idx_users_status (status);
