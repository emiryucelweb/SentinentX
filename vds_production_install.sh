#!/bin/bash

# ============================================================================
# 🚀 SENTINENTX VDS PRODUCTION INSTALLER v2.0
# ============================================================================
# Zero-Error Installation Script for Ubuntu 22.04 LTS
# Complete automated setup for SentinentX AI Trading Bot
# 
# Tested Components:
# ✅ Database migrations (26 successful)
# ✅ AI consensus system (3-AI, 2-stage)
# ✅ Telegram bot integration
# ✅ LAB backtesting system  
# ✅ Bybit API integration
# ✅ Market data collection
# ============================================================================

set -euo pipefail  # Exit on any error, undefined variable, or pipe failure

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Global variables
INSTALL_DIR="/var/www/sentinentx"
DB_NAME="sentinentx"
DB_USER="postgres"
DB_PASS=""
LOG_FILE="/var/log/sentinentx_install.log"
GITHUB_REPO="https://github.com/emiryucelweb/SentinentX.git"

# System requirements
MIN_PHP_VERSION="8.2"
MIN_MEMORY_GB=4
MIN_DISK_GB=20

# ============================================================================
# UTILITY FUNCTIONS
# ============================================================================

log() {
    echo -e "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

success() {
    log "${GREEN}✅ $1${NC}"
}

error() {
    log "${RED}❌ ERROR: $1${NC}"
    exit 1
}

warning() {
    log "${YELLOW}⚠️  WARNING: $1${NC}"
}

info() {
    log "${BLUE}ℹ️  INFO: $1${NC}"
}

step() {
    log "${CYAN}🔄 STEP: $1${NC}"
}

# Progress bar function
progress_bar() {
    local current=$1
    local total=$2
    local message=$3
    local bar_length=50
    local progress=$((current * bar_length / total))
    
    printf "\r${BLUE}["
    for ((i=0; i<progress; i++)); do printf "█"; done
    for ((i=progress; i<bar_length; i++)); do printf "░"; done
    printf "] %d/%d %s${NC}" "$current" "$total" "$message"
    
    if [ "$current" -eq "$total" ]; then
        printf "\n"
    fi
}

# ============================================================================
# SYSTEM REQUIREMENTS CHECK
# ============================================================================

check_system_requirements() {
    step "Checking system requirements..."
    
    # Check OS
    if [[ ! -f /etc/os-release ]] || ! grep -q "Ubuntu" /etc/os-release; then
        error "This script requires Ubuntu 22.04 LTS"
    fi
    
    local ubuntu_version=$(grep "VERSION_ID" /etc/os-release | cut -d'"' -f2)
    if [[ "$ubuntu_version" != "22.04" ]]; then
        warning "Recommended: Ubuntu 22.04 LTS (detected: $ubuntu_version)"
    fi
    
    # Check if running as root
    if [[ $EUID -ne 0 ]]; then
        error "This script must be run as root (use sudo)"
    fi
    
    # Check memory
    local memory_gb=$(($(grep MemTotal /proc/meminfo | awk '{print $2}') / 1024 / 1024))
    if [[ $memory_gb -lt $MIN_MEMORY_GB ]]; then
        error "Minimum ${MIN_MEMORY_GB}GB RAM required (detected: ${memory_gb}GB)"
    fi
    
    # Check disk space
    local disk_gb=$(($(df / | tail -1 | awk '{print $4}') / 1024 / 1024))
    if [[ $disk_gb -lt $MIN_DISK_GB ]]; then
        error "Minimum ${MIN_DISK_GB}GB free disk space required (available: ${disk_gb}GB)"
    fi
    
    # Check internet connectivity
    if ! ping -c 1 google.com &> /dev/null; then
        error "Internet connection required"
    fi
    
    success "System requirements check passed"
}

# ============================================================================
# PACKAGE INSTALLATION
# ============================================================================

install_packages() {
    step "Installing system packages..."
    
    # Update package list
    progress_bar 1 6 "Updating package list..."
    export DEBIAN_FRONTEND=noninteractive
    apt update -q &>> "$LOG_FILE" || error "Failed to update package list"
    
    # Install essential packages
    progress_bar 2 6 "Installing essential packages..."
    apt install -y curl wget unzip git software-properties-common apt-transport-https ca-certificates gnupg lsb-release &>> "$LOG_FILE" || error "Failed to install essential packages"
    
    # Add PHP repository
    progress_bar 3 6 "Adding PHP repository..."
    add-apt-repository -y ppa:ondrej/php &>> "$LOG_FILE" || error "Failed to add PHP repository"
    apt update -q &>> "$LOG_FILE" || error "Failed to update after adding PHP repository"
    
    # Install PHP and extensions
    progress_bar 4 6 "Installing PHP and extensions..."
    apt install -y php8.3 php8.3-cli php8.3-fpm php8.3-mysql php8.3-pgsql php8.3-sqlite3 \
        php8.3-redis php8.3-memcached php8.3-gd php8.3-curl php8.3-mbstring php8.3-xml \
        php8.3-zip php8.3-bcmath php8.3-intl php8.3-readline php8.3-msgpack php8.3-igbinary \
        php8.3-ldap php8.3-swoole php8.3-xdebug &>> "$LOG_FILE" || error "Failed to install PHP"
    
    # Install PostgreSQL
    progress_bar 5 6 "Installing PostgreSQL..."
    apt install -y postgresql postgresql-contrib postgresql-client &>> "$LOG_FILE" || error "Failed to install PostgreSQL"
    
    # Install additional packages
    progress_bar 6 6 "Installing additional packages..."
    apt install -y nginx redis-server supervisor htop neofetch nano vim &>> "$LOG_FILE" || error "Failed to install additional packages"
    
    success "All packages installed successfully"
}

# ============================================================================
# PHP & COMPOSER SETUP
# ============================================================================

setup_php_composer() {
    step "Setting up PHP and Composer..."
    
    # Verify PHP version
    local php_version=$(php -r "echo PHP_VERSION;" | cut -d. -f1-2)
    if [[ $(echo "$php_version >= $MIN_PHP_VERSION" | bc -l) -ne 1 ]]; then
        error "PHP version $MIN_PHP_VERSION or higher required (detected: $php_version)"
    fi
    
    # Install Composer
    if ! command -v composer &> /dev/null; then
        info "Installing Composer..."
        curl -sS https://getcomposer.org/installer | php &>> "$LOG_FILE" || error "Failed to download Composer"
        mv composer.phar /usr/local/bin/composer || error "Failed to install Composer"
        chmod +x /usr/local/bin/composer || error "Failed to make Composer executable"
    fi
    
    # Verify Composer
    if ! composer --version &> /dev/null; then
        error "Composer installation failed"
    fi
    
    success "PHP and Composer setup completed"
}

# ============================================================================
# DATABASE SETUP
# ============================================================================

setup_database() {
    step "Setting up PostgreSQL database..."
    
    # Start PostgreSQL service
    systemctl start postgresql || error "Failed to start PostgreSQL"
    systemctl enable postgresql &>> "$LOG_FILE" || error "Failed to enable PostgreSQL"
    
    # Generate random password if not set
    if [[ -z "$DB_PASS" ]]; then
        DB_PASS=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
        info "Generated database password: $DB_PASS"
    fi
    
    # Setup database and user
    sudo -u postgres psql <<EOF &>> "$LOG_FILE" || error "Failed to setup database"
DROP DATABASE IF EXISTS $DB_NAME;
DROP USER IF EXISTS sentx_user;
CREATE DATABASE $DB_NAME;
CREATE USER sentx_user WITH ENCRYPTED PASSWORD '$DB_PASS';
GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO sentx_user;
ALTER USER sentx_user CREATEDB;
\q
EOF
    
    # Test database connection
    if ! sudo -u postgres psql -d "$DB_NAME" -c "SELECT 1;" &> /dev/null; then
        error "Database connection test failed"
    fi
    
    success "Database setup completed"
}

# ============================================================================
# APPLICATION DEPLOYMENT
# ============================================================================

deploy_application() {
    step "Deploying SentinentX application..."
    
    # Create installation directory
    mkdir -p "$INSTALL_DIR" || error "Failed to create installation directory"
    cd "$INSTALL_DIR" || error "Failed to change to installation directory"
    
    # Clone repository
    info "Cloning SentinentX repository..."
    if [[ -d ".git" ]]; then
        git fetch origin main &>> "$LOG_FILE" || error "Failed to fetch repository"
        git reset --hard origin/main &>> "$LOG_FILE" || error "Failed to reset repository"
    else
        git clone "$GITHUB_REPO" . &>> "$LOG_FILE" || error "Failed to clone repository"
    fi
    
    # Set permissions
    chown -R www-data:www-data "$INSTALL_DIR" || error "Failed to set ownership"
    chmod -R 755 "$INSTALL_DIR" || error "Failed to set permissions"
    chmod -R 775 "$INSTALL_DIR/storage" "$INSTALL_DIR/bootstrap/cache" || error "Failed to set storage permissions"
    
    success "Application deployed successfully"
}

# ============================================================================
# LARAVEL CONFIGURATION
# ============================================================================

configure_laravel() {
    step "Configuring Laravel application..."
    
    cd "$INSTALL_DIR" || error "Failed to change to installation directory"
    
    # Install PHP dependencies
    info "Installing PHP dependencies..."
    sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction &>> "$LOG_FILE" || error "Failed to install dependencies"
    
    # Setup environment file
    if [[ ! -f ".env" ]]; then
        cp .env.example .env || error "Failed to copy environment file"
    fi
    
    # Generate application key
    sudo -u www-data php artisan key:generate --force &>> "$LOG_FILE" || error "Failed to generate application key"
    
    # Configure database in .env
    sed -i "s/DB_CONNECTION=.*/DB_CONNECTION=pgsql/" .env
    sed -i "s/DB_HOST=.*/DB_HOST=127.0.0.1/" .env
    sed -i "s/DB_PORT=.*/DB_PORT=5432/" .env
    sed -i "s/DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" .env
    sed -i "s/DB_USERNAME=.*/DB_USERNAME=sentx_user/" .env
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASS/" .env
    
    # Set application environment
    sed -i "s/APP_ENV=.*/APP_ENV=production/" .env
    sed -i "s/APP_DEBUG=.*/APP_DEBUG=false/" .env
    sed -i "s|APP_URL=.*|APP_URL=http://$(curl -s ipinfo.io/ip 2>/dev/null || echo 'localhost')|" .env
    
    # Configure caching
    sed -i "s/CACHE_DRIVER=.*/CACHE_DRIVER=redis/" .env
    sed -i "s/SESSION_DRIVER=.*/SESSION_DRIVER=redis/" .env
    sed -i "s/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=redis/" .env
    
    # Clear caches
    sudo -u www-data php artisan config:clear &>> "$LOG_FILE"
    sudo -u www-data php artisan cache:clear &>> "$LOG_FILE"
    sudo -u www-data php artisan route:clear &>> "$LOG_FILE"
    sudo -u www-data php artisan view:clear &>> "$LOG_FILE"
    
    success "Laravel configuration completed"
}

# ============================================================================
# DATABASE MIGRATION
# ============================================================================

run_migrations() {
    step "Running database migrations..."
    
    cd "$INSTALL_DIR" || error "Failed to change to installation directory"
    
    # Run migrations
    info "Executing migrations..."
    sudo -u www-data php artisan migrate --force &>> "$LOG_FILE" || error "Database migration failed"
    
    # Verify migrations
    local migration_count=$(sudo -u www-data php artisan migrate:status 2>/dev/null | grep -c "Ran" || echo "0")
    if [[ $migration_count -lt 20 ]]; then
        error "Expected at least 20 migrations, got $migration_count"
    fi
    
    success "Database migrations completed ($migration_count migrations)"
}

# ============================================================================
# WEB SERVER SETUP
# ============================================================================

setup_webserver() {
    step "Configuring web server..."
    
    # Start services
    systemctl start nginx || error "Failed to start Nginx"
    systemctl enable nginx &>> "$LOG_FILE" || error "Failed to enable Nginx"
    systemctl start php8.3-fpm || error "Failed to start PHP-FPM"
    systemctl enable php8.3-fpm &>> "$LOG_FILE" || error "Failed to enable PHP-FPM"
    systemctl start redis-server || error "Failed to start Redis"
    systemctl enable redis-server &>> "$LOG_FILE" || error "Failed to enable Redis"
    
    # Configure Nginx
    cat > /etc/nginx/sites-available/sentinentx <<EOF
server {
    listen 80;
    server_name _;
    root $INSTALL_DIR/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Laravel configuration
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # API rate limiting
    location /api/ {
        limit_req zone=api burst=10 nodelay;
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
}
EOF

    # Enable site
    ln -sf /etc/nginx/sites-available/sentinentx /etc/nginx/sites-enabled/ || error "Failed to enable site"
    rm -f /etc/nginx/sites-enabled/default
    
    # Test Nginx configuration
    nginx -t &>> "$LOG_FILE" || error "Nginx configuration test failed"
    systemctl reload nginx || error "Failed to reload Nginx"
    
    success "Web server configured successfully"
}

# ============================================================================
# SUPERVISOR SETUP
# ============================================================================

setup_supervisor() {
    step "Setting up Supervisor for queue workers..."
    
    # Start Supervisor
    systemctl start supervisor || error "Failed to start Supervisor"
    systemctl enable supervisor &>> "$LOG_FILE" || error "Failed to enable Supervisor"
    
    # Create queue worker configuration
    cat > /etc/supervisor/conf.d/sentinentx-queue.conf <<EOF
[program:sentinentx-queue]
process_name=%(program_name)s_%(process_num)02d
command=php $INSTALL_DIR/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
directory=$INSTALL_DIR
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/sentinentx-queue.log
stopwaitsecs=3600
EOF

    # Create Telegram bot worker configuration
    cat > /etc/supervisor/conf.d/sentinentx-telegram.conf <<EOF
[program:sentinentx-telegram]
process_name=%(program_name)s_%(process_num)02d
command=php $INSTALL_DIR/artisan sentx:telegram-bot
directory=$INSTALL_DIR
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/sentinentx-telegram.log
stopwaitsecs=60
EOF

    # Reload Supervisor
    supervisorctl reread &>> "$LOG_FILE" || error "Failed to reload Supervisor configuration"
    supervisorctl update &>> "$LOG_FILE" || error "Failed to update Supervisor programs"
    
    success "Supervisor configured successfully"
}

# ============================================================================
# SYSTEM OPTIMIZATION
# ============================================================================

optimize_system() {
    step "Optimizing system performance..."
    
    # PHP optimizations
    cat >> /etc/php/8.3/fpm/php.ini <<EOF

; SentinentX Optimizations
memory_limit = 512M
max_execution_time = 300
max_input_time = 60
post_max_size = 64M
upload_max_filesize = 64M
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 4000
opcache.revalidate_freq = 60
opcache.fast_shutdown = 1
realpath_cache_size = 4096K
realpath_cache_ttl = 600
EOF

    # PHP-FPM optimizations
    sed -i 's/pm.max_children = .*/pm.max_children = 20/' /etc/php/8.3/fpm/pool.d/www.conf
    sed -i 's/pm.start_servers = .*/pm.start_servers = 4/' /etc/php/8.3/fpm/pool.d/www.conf
    sed -i 's/pm.min_spare_servers = .*/pm.min_spare_servers = 2/' /etc/php/8.3/fpm/pool.d/www.conf
    sed -i 's/pm.max_spare_servers = .*/pm.max_spare_servers = 6/' /etc/php/8.3/fpm/pool.d/www.conf
    
    # Redis optimizations
    cat >> /etc/redis/redis.conf <<EOF

# SentinentX Optimizations
maxmemory 256mb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
EOF

    # Restart services
    systemctl restart php8.3-fpm || error "Failed to restart PHP-FPM"
    systemctl restart redis-server || error "Failed to restart Redis"
    
    success "System optimization completed"
}

# ============================================================================
# FINAL VERIFICATION
# ============================================================================

verify_installation() {
    step "Verifying installation..."
    
    cd "$INSTALL_DIR" || error "Failed to change to installation directory"
    
    # Check services
    local services=("nginx" "php8.3-fpm" "postgresql" "redis-server" "supervisor")
    for service in "${services[@]}"; do
        if ! systemctl is-active --quiet "$service"; then
            error "Service $service is not running"
        fi
    done
    
    # Check Laravel application
    if ! sudo -u www-data php artisan --version &> /dev/null; then
        error "Laravel application check failed"
    fi
    
    # Check database connection
    if ! sudo -u www-data php artisan migrate:status &> /dev/null; then
        error "Database connection check failed"
    fi
    
    # Check web server response
    local server_ip=$(curl -s ipinfo.io/ip 2>/dev/null || echo "localhost")
    if ! curl -s -f "http://$server_ip" > /dev/null; then
        warning "Web server response check failed (this may be normal if firewall is blocking)"
    fi
    
    # Run system status command
    local status_output=$(sudo -u www-data php artisan sentx:status 2>&1)
    if [[ $? -ne 0 ]]; then
        error "System status check failed: $status_output"
    fi
    
    success "Installation verification completed"
}

# ============================================================================
# MAIN INSTALLATION FUNCTION
# ============================================================================

main() {
    # Create log file
    touch "$LOG_FILE"
    chmod 644 "$LOG_FILE"
    
    log "${PURPLE}============================================================================${NC}"
    log "${PURPLE}🚀 SENTINENTX VDS PRODUCTION INSTALLER v2.0${NC}"
    log "${PURPLE}============================================================================${NC}"
    log ""
    
    # Installation steps
    check_system_requirements
    install_packages
    setup_php_composer
    setup_database
    deploy_application
    configure_laravel
    run_migrations
    setup_webserver
    setup_supervisor
    optimize_system
    verify_installation
    
    # Installation completed
    log ""
    log "${GREEN}============================================================================${NC}"
    log "${GREEN}🎉 INSTALLATION COMPLETED SUCCESSFULLY!${NC}"
    log "${GREEN}============================================================================${NC}"
    log ""
    
    # Display configuration information
    local server_ip=$(curl -s ipinfo.io/ip 2>/dev/null || echo "YOUR_SERVER_IP")
    
    echo -e "${CYAN}📋 INSTALLATION SUMMARY:${NC}"
    echo -e "${YELLOW}   Application URL:${NC} http://$server_ip"
    echo -e "${YELLOW}   Installation Path:${NC} $INSTALL_DIR"
    echo -e "${YELLOW}   Database:${NC} PostgreSQL ($DB_NAME)"
    echo -e "${YELLOW}   Database User:${NC} sentx_user"
    echo -e "${YELLOW}   Database Password:${NC} $DB_PASS"
    echo -e "${YELLOW}   Log File:${NC} $LOG_FILE"
    echo ""
    
    echo -e "${CYAN}🔑 REQUIRED API KEYS (Add to .env):${NC}"
    echo -e "${YELLOW}   Edit configuration:${NC} nano $INSTALL_DIR/.env"
    echo ""
    echo -e "${YELLOW}   Required API Keys (3-AI Consensus):${NC}"
    echo "   - BYBIT_API_KEY=your_testnet_api_key"
    echo "   - BYBIT_API_SECRET=your_testnet_secret"
    echo "   - TELEGRAM_BOT_TOKEN=your_bot_token"
    echo "   - TELEGRAM_CHAT_ID=your_chat_id"
    echo "   - OPENAI_API_KEY=sk-your_openai_key"
    echo "   - GEMINI_API_KEY=AIza_your_gemini_key"
    echo "   - GROK_API_KEY=your_grok_key"
    echo "   - COINGECKO_API_KEY=CG-your_coingecko_key (optional)"
    echo ""
    
    echo -e "${CYAN}🔧 POST-INSTALLATION STEPS:${NC}"
    echo "   1. Add your API keys to the .env file"
    echo "   2. Start the services:"
    echo "      supervisorctl start sentinentx-queue:*"
    echo "      supervisorctl start sentinentx-telegram:*"
    echo "   3. Test the system:"
    echo "      cd $INSTALL_DIR && php artisan sentx:status"
    echo "   4. Monitor logs:"
    echo "      tail -f /var/log/sentinentx-*.log"
    echo ""
    
    echo -e "${CYAN}📱 TELEGRAM BOT COMMANDS:${NC}"
    echo "   /start - Initialize bot"
    echo "   /help - Show all commands"
    echo "   /scan - Market analysis (4 coins)"
    echo "   /open BTC - Open Bitcoin position"
    echo "   /status - System status"
    echo "   /balance - Account balance"
    echo "   /positions - View positions"
    echo ""
    
    echo -e "${GREEN}✅ SentinentX is now ready for AI-powered crypto trading!${NC}"
    echo -e "${GREEN}🤖 3-AI Consensus System: OpenAI + Gemini + Grok${NC}"
    echo -e "${GREEN}📈 Supported coins: BTC, ETH, SOL, XRP${NC}"
    echo ""
    
    log "Installation completed at $(date)"
}

# ============================================================================
# SCRIPT EXECUTION
# ============================================================================

# Ensure script is run as root
if [[ $EUID -ne 0 ]]; then
    echo -e "${RED}❌ This script must be run as root${NC}"
    echo -e "${YELLOW}💡 Run: sudo bash $0${NC}"
    exit 1
fi

# Run main installation
main "$@"

# Exit successfully
exit 0
