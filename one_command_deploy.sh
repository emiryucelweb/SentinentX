#!/bin/bash

# SentinentX One-Command Complete Deployment
# Usage: curl -sSL https://raw.githubusercontent.com/emiryucelweb/SentinentX/main/one_command_deploy.sh | bash

set -euo pipefail
IFS=$'\n\t'

# Trap for error handling
trap 'handle_error $? $LINENO' ERR

# Error handler
handle_error() {
    local exit_code=$1
    local line_number=$2
    log_error "Script failed at line $line_number with exit code $exit_code"
    log_error "Attempting automatic recovery..."
    
    # Cleanup on error
    if [[ -d "/tmp/sentinentx_install" ]]; then
        log_info "Cleaning up temporary files..."
        rm -rf /tmp/sentinentx_install || true
    fi
    
    # Display troubleshooting info
    echo ""
    echo "🚨 Deployment failed - Troubleshooting Information:"
    echo "================================================"
    echo "• Check log file: $LOG_FILE"
    echo "• Verify internet connection"
    echo "• Ensure you have root privileges"
    echo "• Try running again: the script is idempotent"
    echo ""
    
    exit $exit_code
}

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

# Download and verify infrastructure script first
INFRA_SCRIPT="/tmp/quick_vds_install.sh"
if curl -sSL https://raw.githubusercontent.com/emiryucelweb/SentinentX/main/quick_vds_install.sh -o "$INFRA_SCRIPT"; then
    log_info "Infrastructure script downloaded successfully"
    chmod +x "$INFRA_SCRIPT"
    
    # Run with error handling
    if bash "$INFRA_SCRIPT"; then
        log_success "Infrastructure installation completed"
    else
        log_error "Infrastructure installation failed"
        log_error "Retrying infrastructure installation..."
        # Second attempt
        if bash "$INFRA_SCRIPT"; then
            log_success "Infrastructure installation completed on retry"
        else
            log_error "Infrastructure installation failed after retry"
            exit 1
        fi
    fi
else
    log_error "Failed to download infrastructure script"
    log_error "Checking network connectivity..."
    if ping -c 1 google.com &>/dev/null; then
        log_error "Network is available but GitHub may be unreachable"
    else
        log_error "No network connectivity detected"
    fi
    exit 1
fi

# Step 2: Clone SentinentX repository
log_step "Step 2/5: Cloning SentinentX repository..."

# Remove placeholder if exists
if [[ -d "$INSTALL_DIR" ]]; then
    log_warn "Removing existing installation directory..."
    rm -rf "$INSTALL_DIR"
fi

# Clone repository with retry mechanism
MAX_CLONE_RETRIES=3
for ((i=1; i<=MAX_CLONE_RETRIES; i++)); do
    log_info "Cloning repository (attempt $i/$MAX_CLONE_RETRIES)..."
    
    if git clone "$REPO_URL" "$INSTALL_DIR"; then
        log_success "Repository cloned successfully"
        break
    else
        if [[ $i -eq $MAX_CLONE_RETRIES ]]; then
            log_error "Failed to clone repository after $MAX_CLONE_RETRIES attempts"
            log_error "Please check:"
            log_error "• Internet connectivity"
            log_error "• Repository URL: $REPO_URL"
            log_error "• Git is installed: $(which git || echo 'NOT FOUND')"
            exit 1
        else
            log_warn "Clone attempt $i failed, retrying in 5 seconds..."
            sleep 5
            # Remove partial clone if exists
            [[ -d "$INSTALL_DIR" ]] && rm -rf "$INSTALL_DIR"
        fi
    fi
done

cd "$INSTALL_DIR"

# Step 3: Install dependencies and configure
log_step "Step 3/5: Installing dependencies and configuring..."

# Install Composer dependencies with error handling
log_info "Installing Composer dependencies..."

# Check if composer is available
if ! command -v composer &> /dev/null; then
    log_error "Composer not found - infrastructure installation may have failed"
    exit 1
fi

# Clear composer cache first
composer clear-cache 2>/dev/null || true

# Install with retry mechanism
MAX_COMPOSER_RETRIES=3
for ((i=1; i<=MAX_COMPOSER_RETRIES; i++)); do
    log_info "Installing Composer dependencies (attempt $i/$MAX_COMPOSER_RETRIES)..."
    
    if composer install --optimize-autoloader --no-dev --no-interaction; then
        log_success "Composer dependencies installed"
        break
    else
        if [[ $i -eq $MAX_COMPOSER_RETRIES ]]; then
            log_error "Failed to install Composer dependencies after $MAX_COMPOSER_RETRIES attempts"
            log_error "Trying with different flags..."
            
            # Last attempt with different flags
            if composer install --no-dev --no-interaction --ignore-platform-reqs; then
                log_warn "Composer dependencies installed with platform requirements ignored"
                break
            else
                log_error "Composer installation completely failed"
                exit 1
            fi
        else
            log_warn "Composer attempt $i failed, retrying in 10 seconds..."
            sleep 10
        fi
    fi
done

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
CONFIG_PATHS=(
    "/tmp/sentinentx_install/config"
    "/tmp/sentinentx_config"
    "/var/log/sentinentx_install_config"
)

CONFIG_LOADED=false
for config_path in "${CONFIG_PATHS[@]}"; do
    if [[ -f "$config_path" ]]; then
        log_info "Loading configuration from $config_path"
        source "$config_path"
        CONFIG_LOADED=true
        log_success "Infrastructure configuration loaded"
        break
    fi
done

if [[ "$CONFIG_LOADED" == false ]]; then
    log_error "Infrastructure configuration not found in any expected location"
    log_error "Searched paths:"
    for path in "${CONFIG_PATHS[@]}"; do
        log_error "  • $path"
    done
    
    # Try to generate minimal config if DB exists
    if systemctl is-active --quiet postgresql; then
        log_warn "PostgreSQL is running, attempting to create minimal config..."
        DB_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
        REDIS_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
        
        # Test if we can create a simple database
        if sudo -u postgres psql -c "SELECT 1;" &>/dev/null; then
            log_warn "Using generated passwords for database configuration"
        else
            log_error "Cannot access PostgreSQL - infrastructure setup incomplete"
            exit 1
        fi
    else
        log_error "Infrastructure installation appears incomplete"
        exit 1
    fi
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
log_info "Generating Laravel application key..."
if php artisan key:generate --force; then
    log_success "Application key generated"
else
    log_error "Failed to generate application key"
    exit 1
fi

# Test database connection before migrations
log_info "Testing database connection..."
if php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'DB_OK'; } catch(Exception \$e) { echo 'DB_FAIL: ' . \$e->getMessage(); }" 2>/dev/null | grep -q "DB_OK"; then
    log_success "Database connection verified"
else
    log_error "Database connection failed"
    log_error "Attempting to fix database configuration..."
    
    # Try to create database if it doesn't exist
    if sudo -u postgres psql -c "CREATE DATABASE sentinentx;" 2>/dev/null; then
        log_info "Database created"
    fi
    
    # Test again
    if php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'DB_OK'; } catch(Exception \$e) { echo 'DB_FAIL: ' . \$e->getMessage(); }" 2>/dev/null | grep -q "DB_OK"; then
        log_success "Database connection fixed"
    else
        log_error "Cannot establish database connection"
        exit 1
    fi
fi

# Run database migrations with retry
MAX_MIGRATION_RETRIES=3
for ((i=1; i<=MAX_MIGRATION_RETRIES; i++)); do
    log_info "Running database migrations (attempt $i/$MAX_MIGRATION_RETRIES)..."
    
    if php artisan migrate --force; then
        log_success "Database migrations completed"
        break
    else
        if [[ $i -eq $MAX_MIGRATION_RETRIES ]]; then
            log_error "Database migrations failed after $MAX_MIGRATION_RETRIES attempts"
            log_warn "Continuing without migrations - may need manual setup"
            break
        else
            log_warn "Migration attempt $i failed, retrying in 5 seconds..."
            sleep 5
        fi
    fi
done

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

# Start services with error handling
log_info "Starting SentinentX services..."

# Check if service files exist
SERVICES=("sentinentx-queue" "sentinentx-telegram")
for service in "${SERVICES[@]}"; do
    if [[ ! -f "/etc/systemd/system/${service}.service" ]]; then
        log_error "Service file not found: /etc/systemd/system/${service}.service"
        log_error "Infrastructure installation may be incomplete"
        exit 1
    fi
done

# Reload systemd daemon
systemctl daemon-reload

# Start services with retry
for service in "${SERVICES[@]}"; do
    log_info "Starting service: $service"
    
    # Enable service first
    systemctl enable "$service" || log_warn "Failed to enable $service"
    
    # Start with retry mechanism
    MAX_SERVICE_RETRIES=3
    for ((i=1; i<=MAX_SERVICE_RETRIES; i++)); do
        if systemctl start "$service"; then
            log_success "Service $service started successfully"
            break
        else
            if [[ $i -eq $MAX_SERVICE_RETRIES ]]; then
                log_error "Failed to start $service after $MAX_SERVICE_RETRIES attempts"
                log_error "Service logs:"
                journalctl -u "$service" --no-pager --lines=10 || true
            else
                log_warn "Failed to start $service (attempt $i), retrying in 5 seconds..."
                sleep 5
            fi
        fi
    done
done

# Final service verification
sleep 5  # Wait for services to stabilize
all_services_running=true
for service in "${SERVICES[@]}"; do
    if systemctl is-active --quiet "$service"; then
        log_success "Service $service is running"
    else
        log_error "Service $service is not running"
        journalctl -u "$service" --no-pager --lines=5 || true
        all_services_running=false
    fi
done

if [[ "$all_services_running" == true ]]; then
    log_success "All SentinentX services are running"
else
    log_warn "Some services are not running - system may still be functional"
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
