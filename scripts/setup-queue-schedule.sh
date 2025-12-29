#!/bin/bash

# Script tự động setup Queue và Scheduled Tasks trên VPS Ubuntu
# Usage: sudo ./setup-queue-schedule.sh

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Get project path
PROJECT_PATH=$(pwd)
echo -e "${GREEN}Project path: $PROJECT_PATH${NC}"

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Please run as root (use sudo)${NC}"
    exit 1
fi

# 1. Install Supervisor
echo -e "${YELLOW}Installing Supervisor...${NC}"
apt update
apt install supervisor -y

# 2. Create Supervisor config
echo -e "${YELLOW}Creating Supervisor config...${NC}"
cat > /etc/supervisor/conf.d/blog-queue-worker.conf <<EOF
[program:blog-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $PROJECT_PATH/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=$PROJECT_PATH/storage/logs/queue-worker.log
stopwaitsecs=3600
EOF

# 3. Setup cron job
echo -e "${YELLOW}Setting up cron job...${NC}"
CRON_JOB="* * * * * cd $PROJECT_PATH && php artisan schedule:run >> $PROJECT_PATH/storage/logs/scheduler.log 2>&1"

# Check if cron job already exists
if crontab -l 2>/dev/null | grep -q "schedule:run"; then
    echo -e "${YELLOW}Cron job already exists, skipping...${NC}"
else
    (crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -
    echo -e "${GREEN}Cron job added successfully${NC}"
fi

# 4. Ensure storage/logs directory exists and has correct permissions
echo -e "${YELLOW}Setting up storage/logs directory...${NC}"
mkdir -p "$PROJECT_PATH/storage/logs"
chown -R www-data:www-data "$PROJECT_PATH/storage"
chmod -R 775 "$PROJECT_PATH/storage"

# 5. Reload Supervisor
echo -e "${YELLOW}Reloading Supervisor...${NC}"
supervisorctl reread
supervisorctl update

# 6. Start queue worker
echo -e "${YELLOW}Starting queue worker...${NC}"
supervisorctl start blog-queue-worker:*

# 7. Check status
echo -e "${GREEN}Setup completed!${NC}"
echo -e "${YELLOW}Checking status...${NC}"
supervisorctl status
echo ""
echo -e "${GREEN}Cron jobs:${NC}"
crontab -l | grep schedule:run

echo ""
echo -e "${GREEN}✅ Setup completed successfully!${NC}"
echo -e "${YELLOW}Useful commands:${NC}"
echo "  - Check queue worker: sudo supervisorctl status blog-queue-worker:*"
echo "  - Restart queue worker: sudo supervisorctl restart blog-queue-worker:*"
echo "  - View queue logs: tail -f $PROJECT_PATH/storage/logs/queue-worker.log"
echo "  - View scheduler logs: tail -f $PROJECT_PATH/storage/logs/scheduler.log"

