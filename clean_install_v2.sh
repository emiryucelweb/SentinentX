#!/bin/bash

# SentinentX Clean Installation Script
# Bu script önce eski kalıntıları temizler, sonra yeni kurulum yapar

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${CYAN}🧹 SENTINENTX CLEAN INSTALLATION${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# Check root privileges
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}❌ Bu script root olarak çalıştırılmalı!${NC}"
   echo "sudo $0 kullan"
   exit 1
fi

echo -e "${YELLOW}⚠️  Bu script VDS'teki tüm SentinentX kalıntılarını temizleyecek!${NC}"

# Check if running via pipe (no stdin available)
if [ -t 0 ]; then
    echo -e "${YELLOW}⚠️  Devam etmek istediğinden emin misin?${NC}"
    read -p "Devam etmek için 'yes' yaz: " confirm
    if [[ $confirm != "yes" ]]; then
        echo -e "${RED}❌ İşlem iptal edildi.${NC}"
        exit 1
    fi
else
    echo -e "${YELLOW}⚠️  Pipe ile çalışıyor, otomatik devam ediyor...${NC}"
    echo -e "${GREEN}✅ Auto-confirmed via pipe${NC}"
    sleep 2
fi

echo ""
echo -e "${CYAN}🗑️  PHASE 1: CLEANING OLD INSTALLATIONS${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# Step 1: Stop all SentX services
echo -e "${YELLOW}🛑 Step 1/8: Stopping SentX services...${NC}"
systemctl stop sentx-queue 2>/dev/null || true
systemctl stop sentx-telegram 2>/dev/null || true
systemctl disable sentx-queue 2>/dev/null || true
systemctl disable sentx-telegram 2>/dev/null || true
killall -9 php 2>/dev/null || true
killall -9 artisan 2>/dev/null || true
killall -9 composer 2>/dev/null || true
echo -e "${GREEN}✅ Services stopped${NC}"

# Step 2: Remove project directory
echo -e "${YELLOW}📁 Step 2/8: Removing project directory...${NC}"
rm -rf /var/www/sentinentx 2>/dev/null || true
rm -rf /var/www/SentinentX 2>/dev/null || true
echo -e "${GREEN}✅ Project directory removed${NC}"

# Step 3: Remove systemd services
echo -e "${YELLOW}⚙️  Step 3/8: Removing systemd services...${NC}"
rm -f /etc/systemd/system/sentx-*.service 2>/dev/null || true
systemctl daemon-reload
echo -e "${GREEN}✅ Systemd services removed${NC}"

# Step 4: Clean database
echo -e "${YELLOW}🗄️  Step 4/8: Cleaning database...${NC}"
sudo -u postgres psql -c "DROP DATABASE IF EXISTS sentx;" 2>/dev/null || true
sudo -u postgres psql -c "DROP USER IF EXISTS sentx;" 2>/dev/null || true
echo -e "${GREEN}✅ Database cleaned${NC}"

# Step 5: Clean Redis cache
echo -e "${YELLOW}🧽 Step 5/8: Cleaning Redis cache...${NC}"
redis-cli --scan --pattern "sentx:*" | xargs -r redis-cli del 2>/dev/null || true
redis-cli --scan --pattern "laravel_*" | xargs -r redis-cli del 2>/dev/null || true
echo -e "${GREEN}✅ Redis cache cleaned${NC}"

# Step 6: Remove logs
echo -e "${YELLOW}📝 Step 6/8: Removing logs...${NC}"
rm -rf /var/log/sentx* 2>/dev/null || true
rm -rf /var/log/laravel* 2>/dev/null || true
echo -e "${GREEN}✅ Logs removed${NC}"

# Step 7: Clean temporary files
echo -e "${YELLOW}🗂️  Step 7/8: Cleaning temporary files...${NC}"
rm -rf /tmp/composer-* 2>/dev/null || true
rm -rf /tmp/php* 2>/dev/null || true
rm -rf /home/*/sentinentx 2>/dev/null || true
rm -rf /root/.composer/cache 2>/dev/null || true
echo -e "${GREEN}✅ Temporary files cleaned${NC}"

# Step 8: Reset permissions
echo -e "${YELLOW}🔐 Step 8/8: Resetting permissions...${NC}"
# Recreate www-data user if not exists
id -u www-data &>/dev/null || useradd -r -s /bin/false www-data
# Create directory structure
mkdir -p /var/www
chown root:root /var/www
chmod 755 /var/www
echo -e "${GREEN}✅ Permissions reset${NC}"

echo ""
echo -e "${GREEN}🎉 CLEANUP COMPLETED!${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# Wait a moment
sleep 2

echo ""
echo -e "${CYAN}🚀 PHASE 2: FRESH INSTALLATION${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# Download and run the main installation script
echo -e "${YELLOW}📥 Downloading fresh installation script...${NC}"

# Try multiple methods to ensure we get the latest version
if curl -H 'Cache-Control: no-cache' -H 'Pragma: no-cache' -sSL "https://raw.githubusercontent.com/emiryucelweb/SentinentX/main/install.sh?t=$(date +%s)" -o /tmp/sentx_install.sh; then
    echo -e "${GREEN}✅ Installation script downloaded${NC}"
    
    chmod +x /tmp/sentx_install.sh
    
    echo -e "${YELLOW}🏃 Starting fresh installation...${NC}"
    echo ""
    
    # Run the installation script
    /tmp/sentx_install.sh
    
    # Clean up
    rm -f /tmp/sentx_install.sh
    
else
    echo -e "${RED}❌ Failed to download installation script!${NC}"
    echo -e "${YELLOW}💡 Try manual installation:${NC}"
    echo "wget https://raw.githubusercontent.com/emiryucelweb/SentinentX/main/install.sh"
    echo "chmod +x install.sh"
    echo "./install.sh"
    exit 1
fi

echo ""
echo -e "${GREEN}🎉 SENTINENTX CLEAN INSTALLATION COMPLETED!${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
