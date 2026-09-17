# Deploy lên VPS (Ubuntu 24.04)

Hướng dẫn đưa blog lên một VPS Linux, từ máy trắng đến khi chạy HTTPS trên tên miền thật.
Kiến trúc cuối cùng:

```
Internet ──443/80──▶ Nginx (TLS, Let's Encrypt) ──127.0.0.1:8080──▶ Spring Boot (systemd, profile prod)
                                                                      ├── MySQL 8 (localhost:3306)
                                                                      └── /opt/blog/uploads (file media)
```

Các giá trị mẫu trong tài liệu — thay bằng của bạn:

| Mẫu | Ý nghĩa |
|---|---|
| `blog.example.com` | Tên miền |
| `203.0.113.10` | IP của VPS |
| `deploy` | User đăng nhập SSH (có sudo) |
| `blog` | User hệ thống chạy app (không đăng nhập được) |
| `/opt/blog` | Thư mục cài app |

---

## 0. Yêu cầu

- VPS Ubuntu 22.04/24.04, tối thiểu **1 vCPU / 2 GB RAM / 20 GB disk** (JVM + MySQL trên 1 GB RAM sẽ rất chật; nếu chỉ có 1 GB thì bắt buộc tạo swap ở bước 1.4).
- Tên miền đã trỏ bản ghi **A** (và `www` nếu cần) về IP VPS. Kiểm tra: `nslookup blog.example.com`.
- Trên máy dev: build được bằng `mvnw.cmd clean package`.
- (Tuỳ chọn) Tài khoản Gmail có **App password** để gửi mail, TinyPNG API key để nén ảnh.

---

## 1. Chuẩn bị server

### 1.1. Đăng nhập và cập nhật

```bash
ssh root@203.0.113.10
apt update && apt upgrade -y
```

### 1.2. Tạo user deploy, tắt đăng nhập root

```bash
adduser deploy
usermod -aG sudo deploy
mkdir -p /home/deploy/.ssh
cp ~/.ssh/authorized_keys /home/deploy/.ssh/      # nếu root đang dùng SSH key
chown -R deploy:deploy /home/deploy/.ssh && chmod 700 /home/deploy/.ssh
```

Mở một cửa sổ terminal **mới**, thử `ssh deploy@203.0.113.10` được rồi mới làm tiếp:

```bash
sudo nano /etc/ssh/sshd_config
#   PermitRootLogin no
#   PasswordAuthentication no     # chỉ khi đã đăng nhập bằng key được
sudo systemctl restart ssh
```

### 1.3. Firewall

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'   # chạy lại sau khi cài nginx nếu báo không có profile
sudo ufw enable
sudo ufw status
```

Cổng **8080 và 3306 không được mở ra ngoài** — app chỉ nghe qua Nginx, MySQL chỉ localhost.

### 1.4. Swap (VPS ≤ 2 GB RAM)

```bash
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile && sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

### 1.5. Múi giờ

Bài hẹn giờ (`SCHEDULED`) dùng **múi giờ của server** (editor hiện múi giờ này). Đặt đúng múi giờ của bạn:

```bash
sudo timedatectl set-timezone Asia/Ho_Chi_Minh
timedatectl
```

### 1.6. Cài Java 17, MySQL, Nginx

```bash
sudo apt install -y openjdk-17-jre-headless mysql-server nginx certbot python3-certbot-nginx
java -version     # phải là 17.x
```

---

## 2. MySQL

### 2.1. Bảo mật cài đặt

```bash
sudo mysql_secure_installation
```

Chọn: bật kiểm tra mật khẩu (tuỳ), xoá anonymous user, cấm root đăng nhập từ xa, xoá database test.

### 2.2. Tạo database và user riêng

Profile `prod` **không** có `createDatabaseIfNotExist`, nên phải tạo database trước:

```bash
sudo mysql
```

```sql
CREATE DATABASE blog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'blog'@'localhost' IDENTIFIED BY 'DOI_MAT_KHAU_MANH_O_DAY';
GRANT ALL PRIVILEGES ON blog.* TO 'blog'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Flyway cần quyền tạo/sửa bảng nên cấp `ALL` trên riêng database `blog` (không phải `*.*`).

Kiểm tra: `mysql -u blog -p blog -e "SELECT 1"`.

MySQL trên Ubuntu mặc định chỉ nghe `127.0.0.1` (`bind-address` trong `/etc/mysql/mysql.conf.d/mysqld.cnf`) — giữ nguyên.

---

## 3. Build và upload app

### 3.1. Build trên máy dev

```powershell
mvnw.cmd clean package          # chạy cả test; cần MySQL local
# hoặc nhanh: mvnw.cmd clean package -DskipTests
```

Kết quả: `target/blog-0.0.1-SNAPSHOT.jar` (fat jar, đã gồm Tomcat, template, static, migration).

### 3.2. Tạo cấu trúc thư mục trên server

```bash
sudo useradd --system --home /opt/blog --shell /usr/sbin/nologin blog
sudo mkdir -p /opt/blog/{releases,uploads,logs,config}
sudo chown -R blog:blog /opt/blog
sudo chmod 750 /opt/blog
```

| Đường dẫn | Nội dung |
|---|---|
| `/opt/blog/releases/` | Các bản jar theo ngày, để rollback |
| `/opt/blog/app.jar` | Symlink tới bản đang chạy |
| `/opt/blog/uploads/` | **Dữ liệu media** — phải backup, không bao giờ xoá khi deploy |
| `/opt/blog/blog.env` | Biến môi trường chứa secret |

`app.upload.dir` mặc định là `./uploads` (tương đối với thư mục làm việc) — vì service chạy với `WorkingDirectory=/opt/blog` nên file sẽ nằm ở `/opt/blog/uploads`.

### 3.3. Upload jar

Từ máy dev:

```powershell
scp target/blog-0.0.1-SNAPSHOT.jar deploy@203.0.113.10:/tmp/blog.jar
```

Trên server:

```bash
REL=/opt/blog/releases/blog-$(date +%Y%m%d-%H%M).jar
sudo mv /tmp/blog.jar $REL
sudo ln -sfn $REL /opt/blog/app.jar
sudo chown -h blog:blog $REL /opt/blog/app.jar
```

---

## 4. Cấu hình (biến môi trường)

Mọi secret đi qua biến môi trường, **không** sửa file yaml trong jar và không commit secret.

```bash
sudo nano /opt/blog/blog.env
```

```ini
SPRING_PROFILES_ACTIVE=prod

# Database
DB_URL=jdbc:mysql://localhost:3306/blog?useUnicode=true&characterEncoding=utf-8&serverTimezone=Asia/Ho_Chi_Minh
DB_USERNAME=blog
DB_PASSWORD=DOI_MAT_KHAU_MANH_O_DAY

# URL công khai — dùng cho link trong email (reset mật khẩu, xác nhận newsletter)
APP_BASE_URL=https://blog.example.com

# Mail (Gmail SMTP). Chưa có thì đặt MAIL_ENABLED=false: email chỉ được ghi log.
MAIL_ENABLED=true
MAIL_USERNAME=you@gmail.com
MAIL_PASSWORD=xxxxxxxxxxxxxxxx
MAIL_FROM=you@gmail.com
# MAIL_HOST=smtp.gmail.com
# MAIL_PORT=587

# Nén ảnh TinyPNG (tuỳ chọn; để trống = bỏ qua, dùng ảnh gốc)
TINIFY_API_KEY=

# JVM — chỉnh theo RAM (2 GB RAM: 512m là hợp lý)
JAVA_OPTS=-Xms256m -Xmx512m -XX:+UseG1GC -Duser.timezone=Asia/Ho_Chi_Minh
```

```bash
sudo chown root:blog /opt/blog/blog.env
sudo chmod 640 /opt/blog/blog.env
```

Ghi chú:
- **Gmail App password**: Google Account → Security → bật 2-Step Verification → App passwords → tạo 16 ký tự. Gmail giới hạn ~500 mail/ngày (ảnh hưởng gửi newsletter).
- `serverTimezone` trong `DB_URL` và `-Duser.timezone` nên trùng với múi giờ ở bước 1.5.
- Profile `prod` tự bật: cache template, `forward-headers-strategy` (đọc `X-Forwarded-*` từ Nginx), tự dọn Thùng rác sau 30 ngày (cả media).

---

## 5. Chạy bằng systemd

```bash
sudo nano /etc/systemd/system/blog.service
```

```ini
[Unit]
Description=Kienhee Blog (Spring Boot)
After=network-online.target mysql.service
Wants=network-online.target
Requires=mysql.service

[Service]
Type=simple
User=blog
Group=blog
WorkingDirectory=/opt/blog
EnvironmentFile=/opt/blog/blog.env
ExecStart=/bin/sh -c 'exec /usr/bin/java $JAVA_OPTS -Dserver.address=127.0.0.1 -Dserver.port=8080 -jar /opt/blog/app.jar'
SuccessExitStatus=143
Restart=on-failure
RestartSec=10
TimeoutStopSec=30

# Hardening
NoNewPrivileges=true
PrivateTmp=true
ProtectSystem=full
ProtectHome=true
ReadWritePaths=/opt/blog

[Install]
WantedBy=multi-user.target
```

`server.address=127.0.0.1` đảm bảo app không nhận kết nối trực tiếp từ Internet dù firewall có sai.

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now blog
sudo journalctl -u blog -f        # chờ dòng "Started BlogApplication in ..."
```

Lần chạy đầu Flyway tạo toàn bộ schema (`V1`…`V6`) và 35 permission. **Không có dữ liệu demo và không có tài khoản `admin@kienhee.com`** trong prod.

Kiểm tra nội bộ: `curl -I http://127.0.0.1:8080/` → `HTTP/1.1 200`.

---

## 6. Nginx + HTTPS

### 6.1. Virtual host

```bash
sudo nano /etc/nginx/sites-available/blog
```

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name blog.example.com www.blog.example.com;

    # Upload media: app cho phép 10MB/file, 40MB/request
    client_max_body_size 40M;

    gzip on;
    gzip_types text/css application/javascript application/json image/svg+xml application/xml text/xml;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Host              $host;
        proxy_set_header X-Real-IP         $remote_addr;
        proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host  $host;
        proxy_set_header X-Forwarded-Port  $server_port;
        proxy_read_timeout 120s;   # upload/xử lý ảnh lớn
    }
}
```

`X-Forwarded-*` là bắt buộc: RSS, sitemap và link trong email dựng URL tuyệt đối từ request; thiếu header sẽ ra `http://127.0.0.1:8080/...`.

```bash
sudo ln -s /etc/nginx/sites-available/blog /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
```

### 6.2. Chứng chỉ Let's Encrypt

```bash
sudo certbot --nginx -d blog.example.com -d www.blog.example.com --redirect -m you@gmail.com --agree-tos
sudo certbot renew --dry-run      # kiểm tra gia hạn tự động
```

Certbot tự thêm block `listen 443 ssl` và redirect 80 → 443. Sau đó (tuỳ chọn) thêm HSTS vào block 443:

```nginx
add_header Strict-Transport-Security "max-age=31536000" always;
```

---

## 7. Sau khi lên sóng

1. Mở `https://blog.example.com/auth/register` và **đăng ký ngay** — tài khoản đầu tiên trên database trống trở thành **Admin, ACTIVE**. Mọi đăng ký sau là `user`, trạng thái PENDING chờ admin duyệt. Đừng để lộ site trước khi làm bước này.
2. Vào `/admin/settings`: tên site, mô tả, số bài/trang, link mạng xã hội (`social.x`, `social.youtube`), bật/tắt kiểm duyệt bình luận.
3. Thử "Quên mật khẩu" với email của bạn để xác nhận SMTP chạy và link trong mail là `https://blog.example.com/...`.
4. Upload thử một ảnh ở `/admin/media`, mở URL `/media/{id}/...` của nó.
5. Kiểm tra `https://blog.example.com/rss.xml`, `/sitemap.xml`, `/robots.txt` có đúng domain `https`.
6. Khai báo sitemap trên Google Search Console.

---

## 8. Backup

Hai thứ cần backup: **database** và **thư mục `uploads`** (DB lưu đường dẫn file; mất một trong hai là hỏng media). Thư mục `.thumbnails` có thể tạo lại nhưng backup luôn cho đơn giản.

```bash
sudo mkdir -p /var/backups/blog
sudo nano /usr/local/bin/blog-backup.sh
```

```bash
#!/bin/sh
set -e
TS=$(date +%Y%m%d-%H%M)
DIR=/var/backups/blog
set -a; . /opt/blog/blog.env; set +a

MYSQL_PWD="$DB_PASSWORD" mysqldump -u "$DB_USERNAME" --single-transaction --routines --triggers blog | gzip > "$DIR/db-$TS.sql.gz"
tar -czf "$DIR/uploads-$TS.tar.gz" -C /opt/blog uploads

# Giữ 14 ngày
find "$DIR" -type f -mtime +14 -delete
```

```bash
sudo chmod 700 /usr/local/bin/blog-backup.sh
sudo crontab -e
# 15 2 * * * /usr/local/bin/blog-backup.sh >> /var/log/blog-backup.log 2>&1
```

Backup nằm trên cùng VPS không cứu được khi VPS mất — đồng bộ thêm ra ngoài (rclone lên Google Drive/S3, hoặc `rsync` về máy khác) và **thử restore ít nhất một lần**.

### Restore

```bash
sudo systemctl stop blog
gunzip -c /var/backups/blog/db-YYYYMMDD-HHMM.sql.gz | mysql -u blog -p blog
sudo rm -rf /opt/blog/uploads && sudo tar -xzf /var/backups/blog/uploads-YYYYMMDD-HHMM.tar.gz -C /opt/blog
sudo chown -R blog:blog /opt/blog/uploads
sudo systemctl start blog
```

---

## 9. Cập nhật phiên bản mới

1. Trên máy dev: `mvnw.cmd clean package` (chạy test), `scp` jar lên `/tmp/blog.jar`.
2. **Backup trước** nếu bản mới có migration Flyway mới: `sudo /usr/local/bin/blog-backup.sh`.
3. Trên server:

```bash
REL=/opt/blog/releases/blog-$(date +%Y%m%d-%H%M).jar
sudo mv /tmp/blog.jar $REL && sudo chown blog:blog $REL
sudo ln -sfn $REL /opt/blog/app.jar
sudo systemctl restart blog
sudo journalctl -u blog -f
```

Site sẽ gián đoạn vài chục giây trong lúc khởi động lại (Nginx trả 502).

Có thể gói lại thành script `deploy.ps1` trên máy dev:

```powershell
.\mvnw.cmd clean package -DskipTests; if (-not $?) { exit 1 }
scp target/blog-0.0.1-SNAPSHOT.jar deploy@203.0.113.10:/tmp/blog.jar
ssh -t deploy@203.0.113.10 'REL=/opt/blog/releases/blog-$(date +%Y%m%d-%H%M).jar; sudo mv /tmp/blog.jar $REL && sudo chown blog:blog $REL && sudo ln -sfn $REL /opt/blog/app.jar && sudo systemctl restart blog'
```

### Rollback

```bash
ls -1t /opt/blog/releases/
sudo ln -sfn /opt/blog/releases/blog-<ban-truoc>.jar /opt/blog/app.jar
sudo systemctl restart blog
```

Lưu ý: Flyway **không tự rollback schema**. Nếu bản mới đã chạy migration thay đổi bảng, jar cũ có thể lỗi `validate` khi khởi động — lúc đó restore database từ backup ở bước 2. Dọn release cũ: `ls -1t /opt/blog/releases/*.jar | tail -n +6 | sudo xargs -r rm`.

---

## 10. Vận hành và xử lý sự cố

| Việc | Lệnh |
|---|---|
| Trạng thái | `sudo systemctl status blog` |
| Log trực tiếp | `sudo journalctl -u blog -f` |
| Log 1 giờ qua | `sudo journalctl -u blog --since "1 hour ago"` |
| Log Nginx | `sudo tail -f /var/log/nginx/error.log` |
| Dung lượng | `df -h`, `sudo du -sh /opt/blog/uploads /var/backups/blog` |
| RAM | `free -h` |

Giới hạn dung lượng log của journald: trong `/etc/systemd/journald.conf` đặt `SystemMaxUse=500M`, rồi `sudo systemctl restart systemd-journald`.

| Triệu chứng | Nguyên nhân thường gặp |
|---|---|
| **502 Bad Gateway** | App chưa khởi động xong hoặc đã chết — xem `journalctl -u blog`. |
| `Access denied for user 'blog'` | Sai `DB_PASSWORD` trong `blog.env` / chưa `GRANT`. |
| `Unknown database 'blog'` | Chưa tạo database (bước 2.2). |
| `Schema-validation: missing table/column` | Migration chưa chạy hoặc jar cũ trên schema mới — xem log Flyway, xem mục Rollback. |
| `Migration checksum mismatch` | Đã sửa một file `V*.sql` đã chạy trên prod. Không sửa migration cũ; thêm file `V<n+1>`. |
| App bị kill, `OutOfMemoryError` / `oom-kill` trong `dmesg` | Thiếu RAM — thêm swap, giảm `-Xmx`, hoặc nâng VPS. |
| **413 Request Entity Too Large** khi upload | Thiếu `client_max_body_size 40M` trong Nginx. |
| Upload lỗi `Permission denied` | `/opt/blog/uploads` không thuộc user `blog`: `sudo chown -R blog:blog /opt/blog/uploads`. |
| Link trong email/RSS là `http://localhost:8080` | Thiếu `APP_BASE_URL` hoặc header `X-Forwarded-*`, hoặc không chạy profile `prod`. |
| Không gửi được mail | `MAIL_ENABLED=false`, sai App password, hoặc nhà cung cấp VPS chặn cổng 587 (`nc -vz smtp.gmail.com 587`). |
| Bài hẹn giờ đăng sai giờ | Múi giờ server / `serverTimezone` / `user.timezone` không khớp (bước 1.5, 4). |
| Đăng nhập báo bị khoá | Quá 10 lần sai/email hoặc 30/IP trong 10 phút — chờ hoặc restart app (bộ đếm trong RAM). |

---

## 11. Giới hạn cần biết

- **Chỉ chạy một instance.** Rate limit (đăng nhập, upload, bình luận, reset mật khẩu, newsletter) lưu trong RAM; chạy nhiều instance sau load balancer thì mỗi instance đếm riêng. File media nằm trên disk cục bộ.
- Restart app xoá các bộ đếm rate limit.
- Lịch đăng bài chạy mỗi 30 giây nên an toàn kể cả khi sau này chạy nhiều instance; hai job dọn thùng rác chạy lúc 03:30 và 03:45 hằng ngày.

## Checklist nhanh

- [ ] DNS trỏ về VPS
- [ ] User `deploy`, SSH key, tắt root login, UFW (22/80/443)
- [ ] Swap, múi giờ
- [ ] Java 17, MySQL, Nginx, Certbot
- [ ] Database `blog` + user `blog`
- [ ] `/opt/blog` + user `blog` + `blog.env` (chmod 640)
- [ ] `blog.service` enabled, log có `Started BlogApplication`
- [ ] Nginx proxy + `client_max_body_size` + HTTPS
- [ ] Đăng ký tài khoản admin đầu tiên
- [ ] Test mail, upload, RSS/sitemap
- [ ] Cron backup + đồng bộ ra ngoài + thử restore
