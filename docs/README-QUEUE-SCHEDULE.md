# Hướng Dẫn Nhanh: Queue & Scheduled Tasks trên VPS Ubuntu

## 🚀 Setup Tự Động (Khuyến nghị)

```bash
cd /path/to/your/project
sudo ./scripts/setup-queue-schedule.sh
```

Script sẽ tự động:
- ✅ Cài đặt Supervisor
- ✅ Tạo config cho queue worker
- ✅ Setup cron job cho scheduled tasks
- ✅ Khởi động queue worker

## 📝 Setup Thủ Công

### 1. Cài đặt Supervisor

```bash
sudo apt update
sudo apt install supervisor -y
```

### 2. Tạo Supervisor Config

Copy file example và chỉnh sửa:

```bash
sudo cp supervisor/blog-queue-worker.conf.example /etc/supervisor/conf.d/blog-queue-worker.conf
sudo nano /etc/supervisor/conf.d/blog-queue-worker.conf
```

**Thay đổi**:
- `/path/to/your/project` → Đường dẫn thực tế đến project

### 3. Khởi động Queue Worker

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start blog-queue-worker:*
```

### 4. Setup Cron Job

```bash
sudo crontab -e
```

Thêm dòng:
```cron
* * * * * cd /path/to/your/project && php artisan schedule:run >> /path/to/your/project/storage/logs/scheduler.log 2>&1
```

## 🔍 Kiểm Tra

```bash
# Queue worker status
sudo supervisorctl status blog-queue-worker:*

# Scheduled tasks
php artisan schedule:list

# Logs
tail -f storage/logs/queue-worker.log
tail -f storage/logs/scheduler.log
```

## 📚 Xem Hướng Dẫn Chi Tiết

Xem file: `docs/vps-ubuntu-queue-schedule-setup.md`

