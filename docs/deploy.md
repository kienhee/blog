# Hướng dẫn Deploy Laravel với Cloudflare Full Strict SSL (PHP 8.4 + Node.js 22)

## 1. Cập nhật hệ thống

```bash
sudo apt update && sudo apt upgrade -y
```

## 2. Cài đặt PHP 8.4 và các extension cần thiết

```bash
# Thêm repository PHP
sudo apt install software-properties-common -y
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Cài PHP 8.4 và extensions
sudo apt install php8.4 php8.4-fpm php8.4-cli php8.4-common php8.4-mysql \
php8.4-zip php8.4-gd php8.4-mbstring php8.4-curl php8.4-xml \
php8.4-bcmath php8.4-intl php8.4-redis -y

# Kiểm tra version
php -v
```

## 3. Cài đặt Nginx

```bash
sudo apt install nginx -y

# Khởi động và enable Nginx
sudo systemctl start nginx
sudo systemctl enable nginx

# Kiểm tra status
sudo systemctl status nginx
```

## 4. Cài đặt MySQL

```bash
# Cài MySQL Server
sudo apt install mysql-server -y

# Chạy script bảo mật
sudo mysql_secure_installation

# Đăng nhập MySQL
sudo mysql

# Tạo database và user
CREATE DATABASE laravel_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'laravel_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON laravel_db.* TO 'laravel_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 5. Cài đặt Composer

```bash
# Download và cài Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Kiểm tra
composer --version
```

## 6. Cài đặt Node.js 22 và NPM

```bash
# Cài Node.js 22.x
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install nodejs -y

# Kiểm tra
node -v
npm -v
```

## 7. Cài đặt SSL Certificate (Let's Encrypt cho Cloudflare Full Strict)

```bash
# Cài Certbot
sudo apt install certbot python3-certbot-nginx -y

# Tạo SSL certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Certbot sẽ tự động cấu hình Nginx với SSL
```

**Quan trọng**: Với Cloudflare Full Strict, bạn cần:
- Certificate hợp lệ trên Origin Server (Let's Encrypt hoặc Cloudflare Origin Certificate)
- Cloudflare sẽ verify certificate này

## 8. Hoặc sử dụng Cloudflare Origin Certificate (Khuyến nghị)

### Cách 1: Sử dụng Cloudflare Origin Certificate

```bash
# Tạo thư mục cho certificate
sudo mkdir -p /etc/ssl/cloudflare

# Tạo file certificate
sudo nano /etc/ssl/cloudflare/cert.pem
# Paste nội dung Origin Certificate từ Cloudflare

# Tạo file private key
sudo nano /etc/ssl/cloudflare/key.pem
# Paste nội dung Private Key từ Cloudflare

# Set permissions
sudo chmod 600 /etc/ssl/cloudflare/key.pem
sudo chmod 644 /etc/ssl/cloudflare/cert.pem
```

**Để lấy Cloudflare Origin Certificate:**
1. Vào Cloudflare Dashboard → SSL/TLS → Origin Server
2. Click "Create Certificate"
3. Chọn "Generate private key and CSR with Cloudflare"
4. Validity: 15 years
5. Copy cả Certificate và Private Key

## 9. Chuẩn bị project Laravel

```bash
# Tạo thư mục cho project
sudo mkdir -p /var/www/laravel
cd /var/www/laravel

# Clone hoặc upload code vào đây
# git clone your-repo .

# Cài dependencies
composer install --optimize-autoloader --no-dev

# Copy file .env
cp .env.example .env

# Chỉnh sửa .env
nano .env
```

Cấu hình `.env`:
```env
APP_NAME=Laravel
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=your_password

QUEUE_CONNECTION=database

# Quan trọng cho Cloudflare
ASSET_URL=https://your-domain.com
```

```bash
# Generate app key
php artisan key:generate

# Chạy migration
php artisan migrate --force

# Build assets nếu có
npm install
npm run build

# Set permissions
sudo chown -R www-data:www-data /var/www/laravel
sudo chmod -R 755 /var/www/laravel
sudo chmod -R 775 /var/www/laravel/storage
sudo chmod -R 775 /var/www/laravel/bootstrap/cache
```

## 10. Cấu hình Nginx với SSL

```bash
# Tạo file config
sudo nano /etc/nginx/sites-available/laravel
```

### Nếu dùng Let's Encrypt:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com www.your-domain.com;
    root /var/www/laravel/public;

    # SSL Configuration - Let's Encrypt (Certbot tự động thêm)
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;

    # SSL Settings
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Cloudflare Real IP
    set_real_ip_from 103.21.244.0/22;
    set_real_ip_from 103.22.200.0/22;
    set_real_ip_from 103.31.4.0/22;
    set_real_ip_from 104.16.0.0/13;
    set_real_ip_from 104.24.0.0/14;
    set_real_ip_from 108.162.192.0/18;
    set_real_ip_from 131.0.72.0/22;
    set_real_ip_from 141.101.64.0/18;
    set_real_ip_from 162.158.0.0/15;
    set_real_ip_from 172.64.0.0/13;
    set_real_ip_from 173.245.48.0/20;
    set_real_ip_from 188.114.96.0/20;
    set_real_ip_from 190.93.240.0/20;
    set_real_ip_from 197.234.240.0/22;
    set_real_ip_from 198.41.128.0/17;
    set_real_ip_from 2400:cb00::/32;
    set_real_ip_from 2606:4700::/32;
    set_real_ip_from 2803:f800::/32;
    set_real_ip_from 2405:b500::/32;
    set_real_ip_from 2405:8100::/32;
    set_real_ip_from 2c0f:f248::/32;
    set_real_ip_from 2a06:98c0::/29;
    real_ip_header CF-Connecting-IP;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_param HTTPS on;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Nếu dùng Cloudflare Origin Certificate:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com www.your-domain.com;
    root /var/www/laravel/public;

    # SSL Configuration - Cloudflare Origin Certificate
    ssl_certificate /etc/ssl/cloudflare/cert.pem;
    ssl_certificate_key /etc/ssl/cloudflare/key.pem;

    # SSL Settings
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Cloudflare Real IP
    set_real_ip_from 103.21.244.0/22;
    set_real_ip_from 103.22.200.0/22;
    set_real_ip_from 103.31.4.0/22;
    set_real_ip_from 104.16.0.0/13;
    set_real_ip_from 104.24.0.0/14;
    set_real_ip_from 108.162.192.0/18;
    set_real_ip_from 131.0.72.0/22;
    set_real_ip_from 141.101.64.0/18;
    set_real_ip_from 162.158.0.0/15;
    set_real_ip_from 172.64.0.0/13;
    set_real_ip_from 173.245.48.0/20;
    set_real_ip_from 188.114.96.0/20;
    set_real_ip_from 190.93.240.0/20;
    set_real_ip_from 197.234.240.0/22;
    set_real_ip_from 198.41.128.0/17;
    set_real_ip_from 2400:cb00::/32;
    set_real_ip_from 2606:4700::/32;
    set_real_ip_from 2803:f800::/32;
    set_real_ip_from 2405:b500::/32;
    set_real_ip_from 2405:8100::/32;
    set_real_ip_from 2c0f:f248::/32;
    set_real_ip_from 2a06:98c0::/29;
    real_ip_header CF-Connecting-IP;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_param HTTPS on;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/laravel /etc/nginx/sites-enabled/

# Remove default site
sudo rm /etc/nginx/sites-enabled/default

# Test config
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
```

## 11. Cấu hình Laravel để nhận Real IP từ Cloudflare

Sửa file `app/Http/Middleware/TrustProxies.php`:

```php
<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = '*'; // Trust all proxies (Cloudflare)

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
```

## 12. Cài đặt Supervisor cho Queue Workers

```bash
# Cài Supervisor
sudo apt install supervisor -y

# Tạo file config cho Laravel queue
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

Nội dung file:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/laravel/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/laravel/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Reload Supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*

# Kiểm tra status
sudo supervisorctl status
```

## 13. Cấu hình Laravel Schedule (Cron)

```bash
# Mở crontab
sudo crontab -e -u www-data
```

Thêm dòng sau:

```cron
* * * * * cd /var/www/laravel && php artisan schedule:run >> /dev/null 2>&1
```

## 14. Cấu hình Cloudflare Dashboard

### SSL/TLS Settings:
1. **Encryption mode**: Full (strict)
2. **Edge Certificates**: Enable "Always Use HTTPS"
3. **TLS 1.3**: Enabled
4. **Automatic HTTPS Rewrites**: Enabled
5. **Minimum TLS Version**: TLS 1.2

### Speed Settings:
1. **Auto Minify**: Enable HTML, CSS, JS
2. **Brotli**: Enabled

### Caching:
1. **Caching Level**: Standard
2. **Browser Cache TTL**: Respect Existing Headers

### Page Rules (Optional):
```
*your-domain.com/*
- SSL: Full (strict)
- Always Use HTTPS: On
- Cache Level: Standard
```

## 15. Firewall

```bash
# Enable UFW
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable

# Kiểm tra status
sudo ufw status
```

## 16. Các lệnh hữu ích

```bash
# Restart các services
sudo systemctl restart nginx
sudo systemctl restart php8.4-fpm
sudo supervisorctl restart all

# Clear cache Laravel
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Xem log Supervisor
sudo tail -f /var/www/laravel/storage/logs/worker.log

# Xem log Nginx
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/nginx/access.log

# Test SSL
curl -I https://your-domain.com
```

## 17. Kiểm tra hoàn tất

1. ✅ Truy cập `https://your-domain.com` (phải https)
2. ✅ Kiểm tra SSL: https://www.ssllabs.com/ssltest/
3. ✅ Kiểm tra queue: `sudo supervisorctl status`
4. ✅ Kiểm tra Real IP trong Laravel logs
5. ✅ Test redirect HTTP → HTTPS
6. ✅ Cloudflare SSL Mode: Full (strict) ✓

**Lưu ý quan trọng với Cloudflare Full Strict:**
- Origin server (VPS của bạn) PHẢI có SSL certificate hợp lệ
- Cloudflare sẽ verify certificate này
- Nếu certificate không hợp lệ → Error 526 (Invalid SSL Certificate)

Xong! Laravel của bạn đã chạy an toàn với Cloudflare Full Strict SSL mode!