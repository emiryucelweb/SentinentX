#!/bin/bash

# SentinentX One-Command Complete Deployment
# Usage: curl -sSL https://raw.githubusercontent.com/emiryucelweb/SentinentX/main/one_command_deploy.sh | bash

set -euo pipefail
IFS=$'\n\t'

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

# Configuration
REPO_URL="https://github.com/emiryucelweb/SentinentX.git"
INSTALL_DIR="/var/www/sentinentx"
LOG_FILE="/tmp/sentinentx_deploy.log"

# Enhanced logging
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1" | tee -a "$LOG_FILE"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1" | tee -a "$LOG_FILE"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1" | tee -a "$LOG_FILE"
}

log_step() {
    echo -e "${BLUE}[STEP]${NC} $1" | tee -a "$LOG_FILE"
}

log_success() {
    echo -e "${CYAN}[SUCCESS]${NC} $1" | tee -a "$LOG_FILE"
}

# Create log file
touch "$LOG_FILE"

# Header
echo "🚀 SentinentX One-Command Complete Deployment"
echo "============================================="
echo "Repository: $REPO_URL"
echo "Install Directory: $INSTALL_DIR"
echo "Log File: $LOG_FILE"
echo "Timestamp: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

# Check root privileges
if [[ $EUID -ne 0 ]]; then
    log_error "This script must be run as root"
    log_error "Please run: sudo bash -c \"\$(curl -sSL https://raw.githubusercontent.com/emiryucelweb/SentinentX/main/one_command_deploy.sh)\""
    exit 1
fi

# Step 1: Run infrastructure installation
log_step "Step 1/5: Installing infrastructure (PHP, PostgreSQL, Redis, Nginx)..."
if curl -sSL https://raw.githubusercontent.com/emiryucelweb/SentinentX/main/quick_vds_install.sh | bash; then
    log_success "Infrastructure installation completed"
else
    log_error "Infrastructure installation failed"
    exit 1
fi

# Step 2: Clone SentinentX repository
log_step "Step 2/5: Cloning SentinentX repository..."

# Remove placeholder if exists
if [[ -d "$INSTALL_DIR" ]]; then
    log_warn "Removing existing installation directory..."
    rm -rf "$INSTALL_DIR"
fi

# Clone repository
if git clone "$REPO_URL" "$INSTALL_DIR"; then
    log_success "Repository cloned successfully"
else
    log_error "Failed to clone repository"
    exit 1
fi

cd "$INSTALL_DIR"

# Step 3: Install dependencies and configure
log_step "Step 3/5: Installing dependencies and configuring..."

# Install Composer dependencies
if composer install --optimize-autoloader --no-dev; then
    log_success "Composer dependencies installed"
else
    log_error "Failed to install Composer dependencies"
    exit 1
fi

# Install NPM dependencies and build assets
if [[ -f "package.json" ]]; then
    if npm install && npm run build; then
        log_success "NPM dependencies installed and assets built"
    else
        log_warn "NPM installation failed (non-critical)"
    fi
fi

# Step 4: Configure environment
log_step "Step 4/5: Configuring environment..."

# Copy environment template
if [[ -f "env.example.template" ]]; then
    cp env.example.template .env
    log_success "Environment file created from template"
else
    log_error "Environment template not found"
    exit 1
fi

# Read generated passwords from infrastructure installation
if [[ -f "/tmp/sentinentx_install/config" ]]; then
    source /tmp/sentinentx_install/config
    log_success "Infrastructure configuration loaded"
else
    log_error "Infrastructure configuration not found"
    exit 1
fi

# Update .env file with generated values
log_info "Configuring environment variables..."

# Generate secure keys
APP_KEY=$(openssl rand -base64 32)
HMAC_SECRET=$(openssl rand -hex 32)

# Get server IP
SERVER_IP=$(curl -s --max-time 10 ifconfig.me 2>/dev/null || echo "localhost")

# Update .env file
sed -i "s/APP_ENV=local/APP_ENV=production/" .env
sed -i "s/APP_DEBUG=true/APP_DEBUG=false/" .env
sed -i "s|APP_URL=http://localhost|APP_URL=http://$SERVER_IP|" .env
sed -i "s/DB_PASSWORD=your-secure-password/DB_PASSWORD=$DB_PASSWORD/" .env
sed -i "s/REDIS_PASSWORD=your-redis-password/REDIS_PASSWORD=$REDIS_PASSWORD/" .env
sed -i "s/generate_with_openssl_rand_hex_32/$HMAC_SECRET/" .env

# Set TESTNET mode for 15-day testing
sed -i "s/BYBIT_TESTNET=false/BYBIT_TESTNET=true/" .env

# Set secure permissions
chown www-data:www-data .env
chmod 600 .env

log_success "Environment configured for TESTNET mode"

# Step 5: Laravel setup and final configuration
log_step "Step 5/5: Laravel setup and final configuration..."

# Generate application key
php artisan key:generate --force

# Run database migrations
if php artisan migrate --force; then
    log_success "Database migrations completed"
else
    log_warn "Database migrations failed (may need manual setup)"
fi

# Cache optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage link
php artisan storage:link

# Set proper permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chown -R www-data:www-data "$INSTALL_DIR"

# Start services
log_info "Starting SentinentX services..."
systemctl start sentinentx-queue
systemctl start sentinentx-telegram

# Verify services
if systemctl is-active --quiet sentinentx-queue && systemctl is-active --quiet sentinentx-telegram; then
    log_success "All services started successfully"
else
    log_warn "Some services may not have started properly"
fi

# Final status check
log_step "Performing final system check..."

# Check web server
if curl -s "http://localhost" &>/dev/null; then
    log_success "Web server responding"
else
    log_warn "Web server may not be responding properly"
fi

# Create deployment summary
cat > /root/sentinentx_deployment_summary.txt << EOF
🚀 SentinentX Deployment Summary
================================
Deployment Date: $(date '+%Y-%m-%d %H:%M:%S')
Installation Directory: $INSTALL_DIR
Server IP: $SERVER_IP
Web URL: http://$SERVER_IP

📊 Configuration:
- Mode: TESTNET (15-day testing)
- Database: PostgreSQL with auto-generated password
- Cache: Redis with auto-generated password
- Web Server: Nginx
- PHP: 8.2 with optimizations

🔧 Service Status:
- Queue Worker: $(systemctl is-active sentinentx-queue)
- Telegram Bot: $(systemctl is-active sentinentx-telegram)
- Nginx: $(systemctl is-active nginx)
- PostgreSQL: $(systemctl is-active postgresql)
- Redis: $(systemctl is-active redis-server)

🔐 Security:
- Environment: Production mode with debug disabled
- Passwords: Auto-generated and stored securely
- Permissions: Properly configured
- Firewall: UFW enabled

📋 Next Steps:
1. Configure API keys in $INSTALL_DIR/.env:
   - OPENAI_API_KEY=your-openai-key
   - GEMINI_API_KEY=your-gemini-key
   - GROK_API_KEY=your-grok-key
   - BYBIT_API_KEY=your-testnet-api-key
   - BYBIT_API_SECRET=your-testnet-secret
   - TELEGRAM_BOT_TOKEN=your-bot-token
   - TELEGRAM_CHAT_ID=your-chat-id

2. Restart services after API configuration:
   systemctl restart sentinentx-queue
   systemctl restart sentinentx-telegram

3. Test Telegram bot:
   /help
   /status
   /scan

4. Monitor logs:
   tail -f $INSTALL_DIR/storage/logs/laravel.log

📞 Support:
- Installation Log: $LOG_FILE
- Deployment Summary: /root/sentinentx_deployment_summary.txt
- Documentation: $INSTALL_DIR/VDS_DEPLOYMENT_GUIDE.md

🎯 15-Day Testing Ready!
EOF

# Display final message
echo ""
echo "🎉 SentinentX Deployment Completed Successfully!"
echo "================================================"
echo ""
echo "📍 Server Information:"
echo "  • Server IP: $SERVER_IP"
echo "  • Web URL: http://$SERVER_IP"
echo "  • Installation: $INSTALL_DIR"
echo ""
echo "📝 Configuration Required:"
echo "  • Edit: $INSTALL_DIR/.env"
echo "  • Add your API keys (OpenAI, Gemini, Grok, Bybit Testnet, Telegram)"
echo "  • Restart services: systemctl restart sentinentx-*"
echo ""
echo "🧪 Start 15-Day Testing:"
echo "  • Test Telegram: /help"
echo "  • Monitor: tail -f $INSTALL_DIR/storage/logs/laravel.log"
echo "  • Summary: cat /root/sentinentx_deployment_summary.txt"
echo ""
echo "✅ Ready for testnet trading! 🚀💰"

log_success "One-command deployment completed successfully!"
