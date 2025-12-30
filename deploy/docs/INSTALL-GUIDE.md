# Hướng Dẫn Cài Đặt Server - Ubuntu 24.04

## Yêu Cầu
- Ubuntu 24.04 (Noble)
- User có quyền sudo
- Domain đã trỏ về server
- Cloudflare account

---

## 1. Cài Đặt PHP 8.4

```bash
# Thêm PPA Ondrej
sudo apt update
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Cài đặt PHP và extensions
sudo apt install -y \
    php8.4 \
    php8.4-fpm \
    php8.4-cli \
    php8.4-mysql \
    php8.4-xml \
    php8.4-curl \
    php8.4-zip \
    php8.4-gd \
    php8.4-mbstring \
    php8.4-bcmath \
    php8.4-intl \
    php8.4-opcache

# Khởi động PHP-FPM
sudo systemctl start php8.4-fpm
sudo systemctl enable php8.4-fpm

# Kiểm tra
php -v
```

---

## 2. Cài Đặt MySQL

```bash
# Cài đặt MySQL
sudo apt install -y mysql-server

# Bảo mật MySQL
sudo mysql_secure_installation
# Trả lời: Y cho tất cả, đặt password mạnh

# Tạo database và user
sudo mysql -u root -p
```

**Trong MySQL console:**

```sql
CREATE DATABASE blog_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'blog_user'@'localhost' IDENTIFIED BY 'your_strong_password_here';
GRANT ALL PRIVILEGES ON blog_db.* TO 'blog_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 3. Cài Đặt Node.js

```bash
# Cài đặt Node.js 22.x LTS
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs

# Kiểm tra
node -v
npm -v
```

---

## 4. Cài Đặt Nginx

```bash
# Cài đặt Nginx
sudo apt install -y nginx

# Khởi động
sudo systemctl start nginx
sudo systemctl enable nginx

# Firewall
sudo ufw allow 'Nginx Full'
```

---

## 5. Phân Quyền Thư Mục

```bash
# Lấy tên user hiện tại
CURRENT_USER=$(whoami)
PROJECT_DIR="/var/www/blog"

# Tạo thư mục nếu chưa có
sudo mkdir -p $PROJECT_DIR

# Phân quyền thư mục project
sudo chown -R $CURRENT_USER:www-data $PROJECT_DIR
sudo chmod -R 755 $PROJECT_DIR

# Phân quyền storage và cache (Laravel)
sudo chown -R www-data:www-data $PROJECT_DIR/storage
sudo chown -R www-data:www-data $PROJECT_DIR/bootstrap/cache
sudo chmod -R 775 $PROJECT_DIR/storage
sudo chmod -R 775 $PROJECT_DIR/bootstrap/cache

# Thêm user vào group www-data
sudo usermod -a -G www-data $CURRENT_USER

# Logout và login lại để áp dụng group mới
# Hoặc chạy: newgrp www-data
```

---

## 6. Cấu Hình Cloudflare Full (Strict)

### 6.1. Tạo Origin Certificate trên Cloudflare

1. Vào **Cloudflare Dashboard** → **SSL/TLS** → **Origin Server**
2. Click **"Create Certificate"**
3. Chọn:
   - **Private key type**: RSA (2048)
   - **Hostnames**: `your-domain.com`, `*.your-domain.com`
   - **Certificate Validity**: 15 years
4. Copy **Origin Certificate** và **Private Key**

### 6.2. Lưu Certificate trên Server

```bash
# Tạo thư mục
sudo mkdir -p /etc/nginx/ssl

# Lưu Origin Certificate
sudo nano /etc/nginx/ssl/cloudflare-origin.crt
# Paste Origin Certificate vào (bao gồm BEGIN và END)

# Lưu Private Key
sudo nano /etc/nginx/ssl/cloudflare-origin.key
# Paste Private Key vào (bao gồm BEGIN và END)

# Phân quyền
sudo chmod 600 /etc/nginx/ssl/cloudflare-origin.key
sudo chmod 644 /etc/nginx/ssl/cloudflare-origin.crt
sudo chown root:root /etc/nginx/ssl/cloudflare-origin.*
```

### 6.3. Copy Cloudflare IPs Config

```bash
# Copy file config Cloudflare IPs
sudo cp $PROJECT_DIR/deploy/nginx/cloudflare-ips.conf /etc/nginx/snippets/cloudflare-ips.conf
```

### 6.4. Copy và Cấu Hình Nginx

```bash
# Copy config từ project
sudo cp $PROJECT_DIR/deploy/nginx/blog.conf /etc/nginx/sites-available/blog

# Chỉnh sửa config
sudo nano /etc/nginx/sites-available/blog
```

**Thay đổi trong file:**
- `your-domain.com` → domain của bạn (2 chỗ)
- `/var/www/blog` → đường dẫn project (nếu khác)
- `php8.4-fpm` → đúng version PHP (nếu khác)

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/blog /etc/nginx/sites-enabled/

# Xóa config mặc định
sudo rm /etc/nginx/sites-enabled/default

# Test config
sudo nginx -t

# Reload
sudo systemctl reload nginx
```

### 6.5. Set SSL Mode trên Cloudflare

1. Vào **Cloudflare Dashboard** → **SSL/TLS** → **Overview**
2. Set **SSL/TLS encryption mode** = **Full (Strict)**

---

## 7. Cài Đặt Composer

```bash
# Cài đặt Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Kiểm tra
composer --version
```

---

## 8. Cấu Hình Laravel

```bash
cd $PROJECT_DIR

# Cài đặt dependencies
composer install --no-dev --optimize-autoloader
npm install
npm run build

# Tạo .env
cp .env.example .env
nano .env
```

**Cấu hình .env:**

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=blog_db
DB_USERNAME=blog_user
DB_PASSWORD=your_password_here
```

```bash
# Generate key
php artisan key:generate

# Chạy migrations
php artisan migrate --force

# Cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Phân quyền lại sau khi chạy migrations
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## 9. Kiểm Tra

```bash
# PHP
php -v
php -m | grep mysql

# MySQL
mysql --version
sudo systemctl status mysql

# Node.js
node -v
npm -v

# Nginx
sudo nginx -t
sudo systemctl status nginx

# PHP-FPM
sudo systemctl status php8.4-fpm

# Test website
curl -I http://localhost
curl -I https://your-domain.com
```

---

## 10. Troubleshooting

### Lỗi permission khi ghi file

```bash
CURRENT_USER=$(whoami)
PROJECT_DIR="/var/www/blog"

sudo chown -R $CURRENT_USER:www-data $PROJECT_DIR
sudo chmod -R 755 $PROJECT_DIR
sudo chown -R www-data:www-data $PROJECT_DIR/storage $PROJECT_DIR/bootstrap/cache
sudo chmod -R 775 $PROJECT_DIR/storage $PROJECT_DIR/bootstrap/cache
sudo usermod -a -G www-data $CURRENT_USER
# Logout và login lại
```

### Nginx không chạy được

```bash
# Kiểm tra config
sudo nginx -t

# Xem logs
sudo tail -f /var/log/nginx/error.log

# Kiểm tra ports
sudo netstat -tulpn | grep :80
sudo netstat -tulpn | grep :443
```

### PHP-FPM không chạy

```bash
# Kiểm tra status
sudo systemctl status php8.4-fpm

# Xem logs
sudo tail -f /var/log/php8.4-fpm.log

# Kiểm tra socket
ls -la /var/run/php/php8.4-fpm.sock
```

### MySQL không kết nối được

```bash
# Kiểm tra status
sudo systemctl status mysql

# Test connection
mysql -u blog_user -p -e "SELECT 1;"

# Xem logs
sudo tail -f /var/log/mysql/error.log
```

### SSL Certificate lỗi

```bash
# Kiểm tra certificate
sudo openssl x509 -in /etc/nginx/ssl/cloudflare-origin.crt -text -noout

# Kiểm tra private key
sudo openssl rsa -in /etc/nginx/ssl/cloudflare-origin.key -check

# Kiểm tra permissions
ls -la /etc/nginx/ssl/
```

### Cloudflare Full (Strict) không hoạt động

1. **Kiểm tra SSL Mode trên Cloudflare**: Phải là **Full (Strict)**
2. **Kiểm tra Origin Certificate**: Phải đúng domain
3. **Kiểm tra Nginx config**: Phải có SSL config và Cloudflare IPs
4. **Kiểm tra firewall**: Port 443 phải mở

```bash
# Test SSL
curl -I https://your-domain.com

# Kiểm tra certificate
openssl s_client -connect your-domain.com:443 -servername your-domain.com
```

---

## 11. Cấu Hình Bổ Sung

### Tối Ưu PHP-FPM

```bash
sudo nano /etc/php/8.4/fpm/pool.d/www.conf
```

**Cho server 2GB RAM:**
```ini
pm = dynamic
pm.max_children = 30
pm.start_servers = 5
pm.min_spare_servers = 3
pm.max_spare_servers = 10
pm.max_requests = 500
```

**Cho server 4GB+ RAM:**
```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500
```

```bash
sudo systemctl reload php8.4-fpm
```

### Tối Ưu MySQL

```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

**Cho server 2GB RAM:**
```ini
innodb_buffer_pool_size = 512M
max_connections = 100
```

**Cho server 4GB+ RAM:**
```ini
innodb_buffer_pool_size = 1G
max_connections = 200
```

```bash
sudo systemctl restart mysql
```

### Tối Ưu PHP.ini

```bash
sudo nano /etc/php/8.4/fpm/php.ini
```

```ini
memory_limit = 256M
upload_max_filesize = 20M
post_max_size = 20M
date.timezone = Asia/Ho_Chi_Minh

; OPcache
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 2
opcache.fast_shutdown = 1
```

```bash
sudo systemctl reload php8.4-fpm
```

---

## 12. Lưu Ý Quan Trọng

### Cloudflare Full (Strict)

- ✅ **SSL Mode**: Phải set là **Full (Strict)** trên Cloudflare
- ✅ **Origin Certificate**: Cần renew trước khi hết hạn (15 năm)
- ✅ **Cloudflare IPs**: Cần update định kỳ từ https://www.cloudflare.com/ips/
- ✅ **Real IP**: Laravel sẽ nhận đúng IP từ `CF-Connecting-IP` header

### Phân Quyền

- ✅ **User hiện tại**: Sở hữu thư mục project để có thể deploy
- ✅ **www-data**: Sở hữu `storage` và `bootstrap/cache` để Laravel có thể ghi
- ✅ **Group**: User phải trong group `www-data` để có thể ghi vào storage

### Bảo Mật

- ✅ **MySQL**: Chỉ cho phép localhost connection
- ✅ **Firewall**: Chỉ mở port 80, 443, 22
- ✅ **SSL**: Sử dụng Cloudflare Origin Certificate
- ✅ **Nginx**: Chỉ accept requests từ Cloudflare IPs

---

## 13. Script Tự Động (Optional)

Tạo file `setup-server.sh`:

```bash
#!/bin/bash

set -e

CURRENT_USER=$(whoami)
PROJECT_DIR="/var/www/blog"

echo "=== Cài đặt PHP 8.4 ==="
sudo apt update
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.4 php8.4-fpm php8.4-cli php8.4-mysql php8.4-xml php8.4-curl php8.4-zip php8.4-gd php8.4-mbstring php8.4-bcmath php8.4-intl php8.4-opcache
sudo systemctl start php8.4-fpm
sudo systemctl enable php8.4-fpm

echo "=== Cài đặt MySQL ==="
sudo apt install -y mysql-server
sudo mysql_secure_installation

echo "=== Cài đặt Node.js ==="
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs

echo "=== Cài đặt Nginx ==="
sudo apt install -y nginx
sudo systemctl start nginx
sudo systemctl enable nginx
sudo ufw allow 'Nginx Full'

echo "=== Phân quyền thư mục ==="
sudo mkdir -p $PROJECT_DIR
sudo chown -R $CURRENT_USER:www-data $PROJECT_DIR
sudo chmod -R 755 $PROJECT_DIR
sudo usermod -a -G www-data $CURRENT_USER

echo "=== Hoàn tất! ==="
echo "Bước tiếp theo:"
echo "1. Tạo Origin Certificate trên Cloudflare"
echo "2. Lưu certificate vào /etc/nginx/ssl/"
echo "3. Copy và cấu hình Nginx config"
echo "4. Set SSL mode = Full (Strict) trên Cloudflare"
```

```bash
chmod +x setup-server.sh
./setup-server.sh
```

---

## Tài Liệu Tham Khảo

- [Cloudflare Origin CA](https://developers.cloudflare.com/ssl/origin-configuration/origin-ca/)
- [Cloudflare IP Ranges](https://www.cloudflare.com/ips/)
- [Nginx Documentation](https://nginx.org/en/docs/)
- [Laravel Deployment](https://laravel.com/docs/12.x/deployment)

