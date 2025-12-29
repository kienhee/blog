# Hướng Dẫn Cấu Hình Queue và Scheduled Tasks trên VPS Ubuntu

## 📋 Tổng Quan

Dự án sử dụng:
- **Queue**: Database driver (jobs table)
- **Scheduled Tasks**: 
  - `posts:publish-scheduled` - Chạy mỗi phút
  - `sitemap:generate` - Chạy hàng ngày

## 🔧 Yêu Cầu

- Ubuntu Server (20.04+)
- PHP 8.1+
- Composer
- Supervisor (để chạy queue workers)
- Cron (để chạy scheduled tasks)

---

## 1. Cài Đặt Supervisor

### 1.1. Cài đặt Supervisor

```bash
sudo apt update
sudo apt install supervisor -y
```

### 1.2. Kiểm tra Supervisor đã cài đặt

```bash
sudo systemctl status supervisor
```

---

## 2. Cấu Hình Queue Worker với Supervisor

### 2.1. Tạo file cấu hình Supervisor

Tạo file cấu hình cho queue worker:

```bash
sudo nano /etc/supervisor/conf.d/blog-queue-worker.conf
```

### 2.2. Nội dung file cấu hình

**Lưu ý**: Thay `/path/to/your/project` bằng đường dẫn thực tế đến project của bạn.

```ini
[program:blog-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/queue-worker.log
stopwaitsecs=3600
```

**Giải thích các tham số**:
- `process_name`: Tên process (sẽ có suffix _00, _01 nếu numprocs > 1)
- `command`: Lệnh chạy queue worker
  - `--sleep=3`: Đợi 3 giây giữa các lần check queue
  - `--tries=3`: Retry tối đa 3 lần nếu job fail
  - `--max-time=3600`: Worker tự restart sau 1 giờ (tránh memory leak)
- `autostart=true`: Tự động start khi Supervisor start
- `autorestart=true`: Tự động restart nếu worker crash
- `user=www-data`: Chạy với user www-data (thay đổi nếu cần)
- `numprocs=2`: Chạy 2 worker processes (tăng nếu cần)
- `stdout_logfile`: File log cho queue worker

### 2.3. Cập nhật Supervisor và khởi động worker

```bash
# Reload Supervisor config
sudo supervisorctl reread
sudo supervisorctl update

# Start queue worker
sudo supervisorctl start blog-queue-worker:*

# Kiểm tra status
sudo supervisorctl status
```

### 2.4. Các lệnh quản lý Queue Worker

```bash
# Xem status
sudo supervisorctl status blog-queue-worker:*

# Start
sudo supervisorctl start blog-queue-worker:*

# Stop
sudo supervisorctl stop blog-queue-worker:*

# Restart
sudo supervisorctl restart blog-queue-worker:*

# Xem logs
tail -f /path/to/your/project/storage/logs/queue-worker.log
```

---

## 3. Cấu Hình Scheduled Tasks với Cron

### 3.1. Mở crontab

```bash
sudo crontab -e
```

### 3.2. Thêm cron job

Thêm dòng sau vào cuối file (thay `/path/to/your/project` bằng đường dẫn thực tế):

```cron
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

**Giải thích**:
- `* * * * *`: Chạy mỗi phút
- `cd /path/to/your/project`: Di chuyển vào thư mục project
- `php artisan schedule:run`: Chạy Laravel scheduler
- `>> /dev/null 2>&1`: Redirect output (có thể thay bằng file log)

### 3.3. Cấu hình với log (Khuyến nghị)

Để log scheduled tasks, thay dòng trên bằng:

```cron
* * * * * cd /path/to/your/project && php artisan schedule:run >> /path/to/your/project/storage/logs/scheduler.log 2>&1
```

### 3.4. Kiểm tra cron job

```bash
# Xem crontab hiện tại
sudo crontab -l

# Kiểm tra cron service
sudo systemctl status cron
```

---

## 4. Cấu Hình Nâng Cao

### 4.1. Queue Worker với nhiều queues

Nếu bạn có nhiều queues (ví dụ: `default`, `emails`, `notifications`):

```ini
[program:blog-queue-worker-default]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work database --queue=default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/queue-default.log

[program:blog-queue-worker-emails]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work database --queue=emails --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/queue-emails.log
```

### 4.2. Queue Worker với Redis (Nếu dùng Redis)

Nếu bạn muốn chuyển sang Redis (nhanh hơn database):

1. Cài đặt Redis:
```bash
sudo apt install redis-server -y
```

2. Cập nhật `.env`:
```env
QUEUE_CONNECTION=redis
```

3. Cập nhật Supervisor config:
```ini
command=php /path/to/your/project/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
```

### 4.3. Giám sát Queue

#### Xem số lượng jobs trong queue:

```bash
php artisan queue:monitor database:default
```

#### Xem failed jobs:

```bash
php artisan queue:failed
```

#### Retry failed jobs:

```bash
php artisan queue:retry all
```

---

## 5. Kiểm Tra và Troubleshooting

### 5.1. Kiểm tra Queue Worker

```bash
# Xem process đang chạy
ps aux | grep queue:work

# Xem logs
tail -f /path/to/your/project/storage/logs/queue-worker.log
```

### 5.2. Kiểm tra Scheduled Tasks

```bash
# Xem scheduled tasks
php artisan schedule:list

# Test chạy scheduled tasks
php artisan schedule:run

# Xem logs
tail -f /path/to/your/project/storage/logs/scheduler.log
```

### 5.3. Kiểm tra Database Jobs Table

```bash
# Vào MySQL/MariaDB
mysql -u your_user -p your_database

# Xem jobs
SELECT * FROM jobs;

# Xem failed_jobs
SELECT * FROM failed_jobs;
```

### 5.4. Các vấn đề thường gặp

#### Queue Worker không chạy

```bash
# Kiểm tra Supervisor status
sudo supervisorctl status

# Kiểm tra logs
sudo tail -f /var/log/supervisor/supervisord.log

# Restart Supervisor
sudo systemctl restart supervisor
```

#### Scheduled Tasks không chạy

```bash
# Kiểm tra cron service
sudo systemctl status cron

# Kiểm tra cron logs
sudo grep CRON /var/log/syslog

# Test chạy thủ công
cd /path/to/your/project && php artisan schedule:run
```

#### Permission issues

```bash
# Đảm bảo user có quyền
sudo chown -R www-data:www-data /path/to/your/project/storage
sudo chmod -R 775 /path/to/your/project/storage
```

---

## 6. Script Tự Động Hóa

### 6.1. Script kiểm tra và restart

Tạo file `/path/to/your/project/scripts/check-queue.sh`:

```bash
#!/bin/bash

# Kiểm tra queue worker
if ! pgrep -f "queue:work" > /dev/null; then
    echo "Queue worker không chạy, đang restart..."
    sudo supervisorctl restart blog-queue-worker:*
fi

# Kiểm tra scheduled tasks (cron)
if ! pgrep -f "schedule:run" > /dev/null; then
    echo "Cron service có vấn đề, kiểm tra lại..."
    sudo systemctl status cron
fi
```

Cấp quyền thực thi:
```bash
chmod +x /path/to/your/project/scripts/check-queue.sh
```

Thêm vào crontab để chạy mỗi 5 phút:
```cron
*/5 * * * * /path/to/your/project/scripts/check-queue.sh >> /path/to/your/project/storage/logs/queue-check.log 2>&1
```

---

## 7. Monitoring và Alerts

### 7.1. Setup email alerts khi queue worker fail

Tạo script `/path/to/your/project/scripts/queue-alert.sh`:

```bash
#!/bin/bash

QUEUE_STATUS=$(sudo supervisorctl status blog-queue-worker:* | grep -c "RUNNING")

if [ "$QUEUE_STATUS" -eq 0 ]; then
    echo "Queue worker đã dừng!" | mail -s "Alert: Queue Worker Down" your-email@example.com
fi
```

### 7.2. Monitor queue size

Tạo script `/path/to/your/project/scripts/monitor-queue-size.sh`:

```bash
#!/bin/bash

cd /path/to/your/project
QUEUE_SIZE=$(php artisan queue:monitor database:default --max=100 2>&1 | grep -oP '\d+' | head -1)

if [ "$QUEUE_SIZE" -gt 100 ]; then
    echo "Queue size: $QUEUE_SIZE (quá lớn!)" | mail -s "Alert: Queue Size High" your-email@example.com
fi
```

---

## 8. Checklist Deployment

- [ ] Cài đặt Supervisor
- [ ] Tạo file cấu hình Supervisor cho queue worker
- [ ] Cấu hình cron job cho scheduled tasks
- [ ] Test queue worker hoạt động
- [ ] Test scheduled tasks chạy đúng
- [ ] Setup logs và monitoring
- [ ] Cấu hình alerts (nếu cần)
- [ ] Document đường dẫn và cấu hình cho team

---

## 9. Tài Liệu Tham Khảo

- [Laravel Queue Documentation](https://laravel.com/docs/queues)
- [Laravel Task Scheduling](https://laravel.com/docs/scheduling)
- [Supervisor Documentation](http://supervisord.org/)
- [Cron Documentation](https://manpages.ubuntu.com/manpages/focal/man5/crontab.5.html)

