-- Tài khoản tác giả demo (mật khẩu: admin123). Bài viết demo nằm ở V9 trở đi.
-- Seed demo chạy sau mọi migration schema nên phải gán role ở đây: tài khoản không có role thì không vào được trang quản trị.
INSERT INTO users (full_name, email, password, role_id, status)
SELECT 'Admin User', 'admin@kienhee.com', '$2a$10$3VolJvGzHsoZn3MseGEfQebqRezmpfj4nNErmXxGFwLvIKCHbIwIq',
       (SELECT id FROM roles WHERE slug = 'admin'), 'ACTIVE'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@kienhee.com');
