#!/bin/bash

# SentinentX Deep Clean Script
# Bu script VDS'i tamamen temizler (sadece temizlik, kurulum yapmaz)

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${RED}🧹 SENTINENTX DEEP CLEAN${NC}"
echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# Check root privileges
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}❌ Bu script root olarak çalıştırılmalı!${NC}"
   echo "sudo $0 kullan"
   exit 1
fi

echo -e "${YELLOW}⚠️  UYARI: Bu script VDS'teki TÜM SentinentX kalıntılarını silecek!${NC}"
echo -e "${YELLOW}⚠️  Bu işlem GERİ ALINAMAZ!${NC}"
echo -e "${YELLOW}⚠️  Devam etmek istediğinden EMİN MİSİN?${NC}"
echo ""
read -p "Devam etmek için 'DELETE-ALL' yaz: " confirm

if [[ $confirm != "DELETE-ALL" ]]; then
    echo -e "${RED}❌ İşlem iptal edildi.${NC}"
    exit 1
fi

echo ""
echo -e "${RED}🗑️  DEEP CLEANING STARTED...${NC}"
echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# Step 1: Kill all related processes
echo -e "${YELLOW}🔪 Step 1/15: Killing all related processes...${NC}"
pkill -f "sentx" 2>/dev/null || true
pkill -f "SentinentX" 2>/dev/null || true
pkill -f "php artisan" 2>/dev/null || true
killall -9 php 2>/dev/null || true
killall -9 artisan 2>/dev/null || true
killall -9 composer 2>/dev/null || true
killall -9 node 2>/dev/null || true
echo -e "${GREEN}✅ Processes killed${NC}"

# Step 2: Stop and disable services
echo -e "${YELLOW}🛑 Step 2/15: Stopping and disabling services...${NC}"
systemctl stop sentx-* 2>/dev/null || true
systemctl disable sentx-* 2>/dev/null || true
systemctl stop laravel-* 2>/dev/null || true
systemctl disable laravel-* 2>/dev/null || true
echo -e "${GREEN}✅ Services stopped${NC}"

# Step 3: Remove systemd services
echo -e "${YELLOW}⚙️  Step 3/15: Removing systemd services...${NC}"
rm -f /etc/systemd/system/sentx-*.service 2>/dev/null || true
rm -f /etc/systemd/system/laravel-*.service 2>/dev/null || true
systemctl daemon-reload
echo -e "${GREEN}✅ Systemd services removed${NC}"

# Step 4: Remove project directories
echo -e "${YELLOW}📁 Step 4/15: Removing project directories...${NC}"
rm -rf /var/www/sentinentx 2>/dev/null || true
rm -rf /var/www/SentinentX 2>/dev/null || true
rm -rf /var/www/sentx 2>/dev/null || true
rm -rf /var/www/html/sentinentx 2>/dev/null || true
rm -rf /opt/sentinentx 2>/dev/null || true
rm -rf /home/*/sentinentx 2>/dev/null || true
rm -rf /home/*/SentinentX 2>/dev/null || true
rm -rf /root/sentinentx 2>/dev/null || true
rm -rf /root/SentinentX 2>/dev/null || true
echo -e "${GREEN}✅ Project directories removed${NC}"

# Step 5: Clean databases
echo -e "${YELLOW}🗄️  Step 5/15: Cleaning databases...${NC}"
sudo -u postgres psql -c "DROP DATABASE IF EXISTS sentx;" 2>/dev/null || true
sudo -u postgres psql -c "DROP DATABASE IF EXISTS sentinentx;" 2>/dev/null || true
sudo -u postgres psql -c "DROP DATABASE IF EXISTS SentinentX;" 2>/dev/null || true
sudo -u postgres psql -c "DROP USER IF EXISTS sentx;" 2>/dev/null || true
sudo -u postgres psql -c "DROP USER IF EXISTS sentinentx;" 2>/dev/null || true
echo -e "${GREEN}✅ Databases cleaned${NC}"

# Step 6: Clean Redis completely
echo -e "${YELLOW}🧽 Step 6/15: Cleaning Redis...${NC}"
redis-cli FLUSHALL 2>/dev/null || true
systemctl restart redis 2>/dev/null || true
echo -e "${GREEN}✅ Redis cleaned${NC}"

# Step 7: Remove logs
echo -e "${YELLOW}📝 Step 7/15: Removing logs...${NC}"
rm -rf /var/log/sentx* 2>/dev/null || true
rm -rf /var/log/laravel* 2>/dev/null || true
rm -rf /var/log/php* 2>/dev/null || true
rm -rf /var/log/composer* 2>/dev/null || true
echo -e "${GREEN}✅ Logs removed${NC}"

# Step 8: Clean temporary files
echo -e "${YELLOW}🗂️  Step 8/15: Cleaning temporary files...${NC}"
rm -rf /tmp/composer-* 2>/dev/null || true
rm -rf /tmp/php* 2>/dev/null || true
rm -rf /tmp/sentx* 2>/dev/null || true
rm -rf /tmp/laravel* 2>/dev/null || true
echo -e "${GREEN}✅ Temporary files cleaned${NC}"

# Step 9: Clean user caches
echo -e "${YELLOW}🧹 Step 9/15: Cleaning user caches...${NC}"
rm -rf /root/.composer 2>/dev/null || true
rm -rf /root/.cache 2>/dev/null || true
rm -rf /root/.config/composer 2>/dev/null || true
rm -rf /home/*/.composer 2>/dev/null || true
rm -rf /home/*/.cache 2>/dev/null || true
rm -rf /home/*/.config/composer 2>/dev/null || true
echo -e "${GREEN}✅ User caches cleaned${NC}"

# Step 10: Clean PHP sessions
echo -e "${YELLOW}🔧 Step 10/15: Cleaning PHP sessions...${NC}"
rm -rf /var/lib/php/sessions/sess_* 2>/dev/null || true
echo -e "${GREEN}✅ PHP sessions cleaned${NC}"

# Step 11: Clean cron jobs
echo -e "${YELLOW}⏰ Step 11/15: Cleaning cron jobs...${NC}"
crontab -l 2>/dev/null | grep -v sentx | grep -v SentinentX | crontab - 2>/dev/null || true
echo -e "${GREEN}✅ Cron jobs cleaned${NC}"

# Step 12: Remove config files
echo -e "${YELLOW}⚙️  Step 12/15: Removing config files...${NC}"
rm -rf /etc/sentx* 2>/dev/null || true
rm -rf /etc/php/*/fpm/pool.d/sentx* 2>/dev/null || true
rm -rf /etc/nginx/sites-*/sentx* 2>/dev/null || true
rm -rf /etc/apache2/sites-*/sentx* 2>/dev/null || true
echo -e "${GREEN}✅ Config files removed${NC}"

# Step 13: Clean SSL certificates
echo -e "${YELLOW}🔒 Step 13/15: Cleaning SSL certificates...${NC}"
rm -rf /etc/letsencrypt/live/sentx* 2>/dev/null || true
rm -rf /etc/letsencrypt/archive/sentx* 2>/dev/null || true
rm -rf /etc/letsencrypt/renewal/sentx* 2>/dev/null || true
echo -e "${GREEN}✅ SSL certificates cleaned${NC}"

# Step 14: Clean package manager caches
echo -e "${YELLOW}📦 Step 14/15: Cleaning package caches...${NC}"
apt autoremove -y 2>/dev/null || true
apt autoclean 2>/dev/null || true
composer clear-cache 2>/dev/null || true
echo -e "${GREEN}✅ Package caches cleaned${NC}"

# Step 15: Final verification
echo -e "${YELLOW}🔍 Step 15/15: Final verification...${NC}"
if [ -d "/var/www/sentinentx" ] || [ -d "/var/www/SentinentX" ]; then
    echo -e "${RED}⚠️  Some directories still exist!${NC}"
else
    echo -e "${GREEN}✅ All directories removed${NC}"
fi

if systemctl list-units --type=service | grep -q sentx; then
    echo -e "${RED}⚠️  Some services still exist!${NC}"
else
    echo -e "${GREEN}✅ All services removed${NC}"
fi

echo ""
echo -e "${GREEN}🎉 DEEP CLEAN COMPLETED!${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${CYAN}💡 VDS tamamen temizlendi. Artık yeni kurulum yapabilirsin!${NC}"
echo ""
echo -e "${YELLOW}🚀 Yeni kurulum için:${NC}"
echo "curl -sSL https://raw.githubusercontent.com/emiryucelweb/SentinentX/main/clean_install.sh | bash"
echo ""
