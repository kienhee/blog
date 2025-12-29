#!/bin/bash
# Laravel Queue + Scheduler Setup Script (PRODUCTION SAFE)

set -e

# ===== CONFIG =====
PROJECT_PATH=$(pwd)
PHP_BIN=$(which php)
APP_USER=www-data
APP_GROUP=www-data

# ===== COLORS =====
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${GREEN}🚀 Laravel Queue & Scheduler Setup${NC}"
echo -e "${GREEN}Project path: $PROJECT_PATH${NC}"

# ===== CHECK ROOT =====
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}❌ Please run as root: sudo ./setup-queue-schedule.sh${NC}"
    exit 1
fi

# ===== INSTALL SERVICES =====
echo -e "${YELLOW}📦 Installing Supervisor & Cron...${NC}"
apt update
apt install supervisor cron -y

systemctl enable supervisor cron
systemctl start supervisor cron

# ===== FIX PERMISSIONS =====
echo -e "${YELLOW}🔐 Fixing permissions...${NC}"
mkdir -p $PROJECT_PATH/storage/logs
mkdir -p $PROJECT_PATH/bootstrap/cache

chown -R $APP_USER:$APP_GROUP $PROJECT_PATH/storage $PROJECT_PATH/bootstrap/cache
find $PROJECT_PATH/storage $PROJECT_PATH/bootstrap/cache -type d -exec chmod 775 {} \;
find $PROJECT_PATH/storage $PROJECT_PATH/bootstrap/cache -type f -exec chmod 664 {} \;

# ===== SUPERVISOR CONFIG =====
echo -e "${YELLOW}⚙️ Creating Supervisor config...${NC}"

SUPERVISOR_CONF="/etc/supervisor/conf.d/blog-queue-worker.conf"

cat > $SUPERVISOR_CONF <<EOF
[program:blog-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=$PHP_BIN $PROJECT_PATH/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
user=$APP_USER
numprocs=2
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
redirect_stderr=true
stdout_logfile=$PROJECT_PATH/storage/logs/queue-worker.log
stopwaitsecs=3600
EOF

# ===== RELOAD SUPERVISOR =====
echo -e "${YELLOW}🔄 Reloading Supervisor...${NC}"
supervisorctl reread
supervisorctl update
supervisorctl restart blog-queue-worker:* || supervisorctl start blog-queue-worker:*

# ===== SETUP CRON (WWW-DATA) =====
echo -e "${YELLOW}⏱ Setting up Laravel Scheduler (www-data)...${NC}"

CRON_JOB="* * * * * cd $PROJECT_PATH && $PHP_BIN artisan schedule:run >> $PROJECT_PATH/storage/logs/scheduler.log 2>&1"

sudo -u $APP_USER crontab -l 2>/dev/null | grep -q "schedule:run" || \
( sudo -u $APP_USER crontab -l 2>/dev/null; echo "$CRON_JOB" ) | sudo -u $APP_USER crontab -

# ===== FINAL CHECK =====
echo ""
echo -e "${GREEN}✅ SETUP COMPLETED SUCCESSFULLY${NC}"
echo ""
echo -e "${YELLOW}Supervisor status:${NC}"
supervisorctl status blog-queue-worker:*
echo ""
echo -e "${YELLOW}Scheduler cron (www-data):${NC}"
sudo -u $APP_USER crontab -l | grep schedule:run

echo ""
echo -e "${GREEN}📌 Useful commands:${NC}"
echo "  Queue status:        sudo supervisorctl status blog-queue-worker:*"
echo "  Restart queue:       sudo supervisorctl restart blog-queue-worker:*"
echo "  Queue logs:          tail -f storage/logs/queue-worker.log"
echo "  Scheduler logs:      tail -f storage/logs/scheduler.log"
echo "  Test scheduler:      sudo -u www-data php artisan schedule:run"
