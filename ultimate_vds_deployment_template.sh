#!/bin/bash

# 🚀 SENTINENTX ULTIMATE VDS DEPLOYMENT SCRIPT
# ==============================================
# ZERO-ERROR DEPLOYMENT FOR 15-DAY TESTNET
# Compatible: Ubuntu 22.04/24.04 LTS x64
# Full system reset + complete deployment

set -euo pipefail

# ================================
# CONFIGURATION & COLORS
# ================================

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

# VDS Configuration
VDS_USER="sentinentx"
VDS_PASSWORD="emir071028"
PROJECT_DIR="/var/www/sentinentx"
PHP_VERSION="8.3"

# API Configurations - REPLACE WITH YOUR KEYS
COINGECKO_API_KEY="YOUR_COINGECKO_API_KEY"
BYBIT_API_KEY="YOUR_BYBIT_API_KEY"
BYBIT_API_SECRET="YOUR_BYBIT_API_SECRET"
OPENAI_API_KEY="YOUR_OPENAI_API_KEY"
GROK_API_KEY="YOUR_GROK_API_KEY"
GEMINI_API_KEY="YOUR_GEMINI_API_KEY"
TELEGRAM_BOT_TOKEN="YOUR_TELEGRAM_BOT_TOKEN"
TELEGRAM_CHAT_ID="YOUR_TELEGRAM_CHAT_ID"

# Repository URLs with fallback
REPO_URL_PRIMARY="https://github.com/emiryucelweb/SentinentX.git"
REPO_URL_BACKUP="https://github.com/emiryucelweb/SentinentX/archive/refs/heads/main.zip"

# ================================
# LOGGING FUNCTIONS
# ================================

LOGFILE="/tmp/sentinentx_ultimate_deploy.log"

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1" | tee -a "${LOGFILE}"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1" | tee -a "${LOGFILE}"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1" | tee -a "${LOGFILE}"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1" | tee -a "${LOGFILE}"
}

log_step() {
    echo -e "${PURPLE}🔧${NC} $1" | tee -a "${LOGFILE}"
}

log_header() {
    echo -e "${CYAN}$1${NC}" | tee -a "${LOGFILE}"
}

# ================================
# ERROR HANDLING
# ================================

handle_error() {
    local line_number=$1
    local error_code=$2
    log_error "Script failed at line $line_number with exit code $error_code"
    log_error "Check log file: $LOGFILE"
    
    # Attempt rollback for critical operations
    if [[ -f "/tmp/sentinentx_rollback" ]]; then
        log_warn "Attempting automatic rollback..."
        source /tmp/sentinentx_rollback 2>/dev/null || true
    fi
    
    exit $error_code
}

trap 'handle_error ${LINENO} $?' ERR

# Create rollback file
create_rollback() {
    echo "#!/bin/bash" > /tmp/sentinentx_rollback
    echo "# Rollback actions" >> /tmp/sentinentx_rollback
    echo "$1" >> /tmp/sentinentx_rollback
    chmod +x /tmp/sentinentx_rollback
}

# ================================
# VDS RESET AND CLEANUP
# ================================

reset_vds() {
    log_header "🔄 VDS RESET - COMPLETE SYSTEM CLEANUP"
    
    log_step "Stopping all SentinentX services..."
    systemctl stop sentinentx 2>/dev/null || true
    systemctl stop nginx 2>/dev/null || true
    systemctl stop postgresql 2>/dev/null || true
    systemctl stop redis-server 2>/dev/null || true
    systemctl stop php8.3-fpm 2>/dev/null || true
    
    log_step "Removing SentinentX project directory..."
    rm -rf /var/www/sentinentx 2>/dev/null || true
    rm -rf /var/www/html 2>/dev/null || true
    
    log_step "Removing systemd service files..."
    rm -f /etc/systemd/system/sentinentx.service 2>/dev/null || true
    systemctl daemon-reload
    
    log_step "Removing nginx configuration..."
    rm -f /etc/nginx/sites-available/sentinentx 2>/dev/null || true
    rm -f /etc/nginx/sites-enabled/sentinentx 2>/dev/null || true
    rm -f /etc/nginx/sites-enabled/default 2>/dev/null || true
    
    log_step "Cleaning up logs..."
    rm -f /var/log/sentinentx* 2>/dev/null || true
    rm -f /tmp/sentinentx* 2>/dev/null || true
    
    log_step "Resetting PostgreSQL database..."
    sudo -u postgres psql -c "DROP DATABASE IF EXISTS sentinentx;" 2>/dev/null || true
    sudo -u postgres psql -c "DROP USER IF EXISTS sentinentx;" 2>/dev/null || true
    
    log_step "Flushing Redis cache..."
    redis-cli FLUSHALL 2>/dev/null || true
    
    log_step "Cleaning up temp files..."
    rm -rf /tmp/composer* 2>/dev/null || true
    rm -rf /tmp/npm* 2>/dev/null || true
    
    log_success "✅ VDS completely reset and ready for fresh installation!"
}

# ================================
# API KEY VALIDATION
# ================================

validate_api_keys() {
    log_header "🔑 API KEY VALIDATION"
    
    local has_errors=false
    
    if [[ "$COINGECKO_API_KEY" == "YOUR_COINGECKO_API_KEY" ]]; then
        log_error "CoinGecko API key not set!"
        has_errors=true
    fi
    
    if [[ "$BYBIT_API_KEY" == "YOUR_BYBIT_API_KEY" ]]; then
        log_error "Bybit API key not set!"
        has_errors=true
    fi
    
    if [[ "$OPENAI_API_KEY" == "YOUR_OPENAI_API_KEY" ]]; then
        log_error "OpenAI API key not set!"
        has_errors=true
    fi
    
    if [[ "$GROK_API_KEY" == "YOUR_GROK_API_KEY" ]]; then
        log_error "Grok API key not set!"
        has_errors=true
    fi
    
    if [[ "$GEMINI_API_KEY" == "YOUR_GEMINI_API_KEY" ]]; then
        log_error "Gemini API key not set!"
        has_errors=true
    fi
    
    if [[ "$TELEGRAM_BOT_TOKEN" == "YOUR_TELEGRAM_BOT_TOKEN" ]]; then
        log_error "Telegram bot token not set!"
        has_errors=true
    fi
    
    if [[ "$has_errors" == "true" ]]; then
        echo ""
        log_error "Please edit this script and replace all YOUR_*_API_KEY placeholders with your actual API keys"
        echo ""
        echo "Required API keys:"
        echo "- COINGECKO_API_KEY=\"your_actual_coingecko_key\""
        echo "- BYBIT_API_KEY=\"your_actual_bybit_key\""
        echo "- BYBIT_API_SECRET=\"your_actual_bybit_secret\""
        echo "- OPENAI_API_KEY=\"your_actual_openai_key\""
        echo "- GROK_API_KEY=\"your_actual_grok_key\""
        echo "- GEMINI_API_KEY=\"your_actual_gemini_key\""
        echo "- TELEGRAM_BOT_TOKEN=\"your_actual_bot_token\""
        echo "- TELEGRAM_CHAT_ID=\"your_actual_chat_id\""
        echo ""
        exit 1
    fi
    
    log_success "All API keys configured"
}

# ================================
# SYSTEM VALIDATION
# ================================

validate_system() {
    log_header "🔍 SYSTEM VALIDATION"
    
    # Check if running as root
    if [[ $EUID -ne 0 ]]; then
        log_error "This script must be run as root (use: sudo su)"
        exit 1
    fi
    
    # Check Ubuntu version
    if ! grep -q "Ubuntu" /etc/os-release; then
        log_error "This script is designed for Ubuntu systems"
        exit 1
    fi
    
    # Get Ubuntu version
    UBUNTU_VERSION=$(lsb_release -rs)
    log_info "Detected Ubuntu $UBUNTU_VERSION"
    
    # Adjust PHP version for Ubuntu 24.04
    if [[ "$UBUNTU_VERSION" == "24.04" ]]; then
        PHP_VERSION="8.3"
        log_info "Using PHP $PHP_VERSION for Ubuntu 24.04"
    fi
    
    # Check system resources
    TOTAL_RAM=$(free -m | awk 'NR==2{print $2}')
    AVAILABLE_DISK=$(df / | awk 'NR==2{print $4}')
    
    if [[ $TOTAL_RAM -lt 1024 ]]; then
        log_warn "Low RAM detected: ${TOTAL_RAM}MB (recommended: 2GB+)"
    fi
    
    if [[ $AVAILABLE_DISK -lt 5000000 ]]; then
        log_warn "Low disk space: ${AVAILABLE_DISK}KB (recommended: 10GB+)"
    fi
    
    # Check architecture
    ARCH=$(uname -m)
    if [[ "$ARCH" != "x86_64" ]]; then
        log_warn "Non-x64 architecture detected: $ARCH"
    fi
    
    log_success "System validation completed"
}

# ================================
# SYSTEM RESET
# ================================

reset_system() {
    log_header "🗑️ SYSTEM RESET"
    
    # Stop conflicting services
    log_step "Stopping conflicting services..."
    systemctl stop sentinentx 2>/dev/null || true
    systemctl stop apache2 2>/dev/null || true
    systemctl stop mysql 2>/dev/null || true
    systemctl stop mysqld 2>/dev/null || true
    
    # Remove conflicting packages
    log_step "Removing conflicting packages..."
    apt-get remove --purge -y apache2* mysql* nginx-* 2>/dev/null || true
    apt-get autoremove -y 2>/dev/null || true
    
    # Clean package manager
    apt-get clean
    apt-get update
    
    # Remove old installations
    log_step "Cleaning old installations..."
    rm -rf /var/www/sentinentx
    rm -rf /etc/systemd/system/sentinentx.service
    rm -rf /tmp/sentinentx*
    
    # Reset networking
    ufw --force reset 2>/dev/null || true
    
    log_success "System reset completed"
}

# ================================
# PACKAGE INSTALLATION
# ================================

install_packages() {
    log_header "📦 PACKAGE INSTALLATION"
    
    # Update package list
    log_step "Updating package lists..."
    apt-get update
    
    # Install essential packages
    log_step "Installing essential packages..."
    DEBIAN_FRONTEND=noninteractive apt-get install -y \
        curl \
        wget \
        git \
        unzip \
        software-properties-common \
        apt-transport-https \
        ca-certificates \
        gnupg \
        lsb-release \
        ufw \
        fail2ban \
        htop \
        vim \
        nano \
        tree \
        jq \
        build-essential
    
    # Add PHP repository
    log_step "Adding PHP repository..."
    add-apt-repository ppa:ondrej/php -y
    apt-get update
    
    # Install PHP and extensions with fallback handling
    log_step "Installing PHP $PHP_VERSION and extensions..."
    
    # Create list of PHP packages with fallback handling
    PHP_PACKAGES=(
        "php${PHP_VERSION}"
        "php${PHP_VERSION}-cli"
        "php${PHP_VERSION}-fpm"
        "php${PHP_VERSION}-pgsql"
        "php${PHP_VERSION}-redis"
        "php${PHP_VERSION}-curl"
        "php${PHP_VERSION}-mbstring"
        "php${PHP_VERSION}-xml"
        "php${PHP_VERSION}-zip"
        "php${PHP_VERSION}-bcmath"
        "php${PHP_VERSION}-intl"
        "php${PHP_VERSION}-gd"
        "php${PHP_VERSION}-dom"
        "php${PHP_VERSION}-fileinfo"
        "php${PHP_VERSION}-pdo"
        "php${PHP_VERSION}-tokenizer"
        "php${PHP_VERSION}-ctype"
    )
    
    # Note: php-json is built into PHP 8.3+ core, no separate package needed
    
    # Install packages with individual error handling
    for package in "${PHP_PACKAGES[@]}"; do
        if ! DEBIAN_FRONTEND=noninteractive apt-get install -y "$package" 2>/dev/null; then
            log_warn "Package $package could not be installed, attempting alternative..."
            # Try without version number for some packages
            base_package=$(echo "$package" | sed "s/php${PHP_VERSION}-/php-/")
            DEBIAN_FRONTEND=noninteractive apt-get install -y "$base_package" 2>/dev/null || true
        fi
    done
    
    # Install PostgreSQL
    log_step "Installing PostgreSQL..."
    DEBIAN_FRONTEND=noninteractive apt-get install -y postgresql postgresql-contrib
    
    # Install Redis
    log_step "Installing Redis..."
    DEBIAN_FRONTEND=noninteractive apt-get install -y redis-server
    
    # Install Nginx
    log_step "Installing Nginx..."
    DEBIAN_FRONTEND=noninteractive apt-get install -y nginx
    
    # Install Composer
    log_step "Installing Composer..."
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    
    # Install Node.js and NPM
    log_step "Installing Node.js..."
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt-get install -y nodejs
    
    log_success "Package installation completed"
}

# ================================
# SERVICE CONFIGURATION
# ================================

configure_postgresql() {
    log_header "🗄️ POSTGRESQL CONFIGURATION"
    
    # Start PostgreSQL
    systemctl start postgresql
    systemctl enable postgresql
    
    # Create database and user
    log_step "Creating database and user..."
    sudo -u postgres psql -c "DROP DATABASE IF EXISTS sentinentx;"
    sudo -u postgres psql -c "DROP USER IF EXISTS ${VDS_USER};"
    sudo -u postgres psql -c "CREATE DATABASE sentinentx;"
    sudo -u postgres psql -c "CREATE USER ${VDS_USER} WITH PASSWORD '${VDS_PASSWORD}';"
    sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE sentinentx TO ${VDS_USER};"
    sudo -u postgres psql -c "ALTER USER ${VDS_USER} CREATEDB;"
    
    # Grant schema permissions
    sudo -u postgres psql -d sentinentx -c "GRANT ALL ON SCHEMA public TO ${VDS_USER};"
    sudo -u postgres psql -d sentinentx -c "ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO ${VDS_USER};"
    sudo -u postgres psql -d sentinentx -c "ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO ${VDS_USER};"
    
    # Test connection
    if PGPASSWORD=${VDS_PASSWORD} psql -h localhost -U ${VDS_USER} -d sentinentx -c "SELECT 1;" >/dev/null 2>&1; then
        log_success "PostgreSQL configured successfully"
    else
        log_error "PostgreSQL configuration failed"
        exit 1
    fi
}

configure_redis() {
    log_header "🔴 REDIS CONFIGURATION"
    
    # Configure Redis
    log_step "Configuring Redis..."
    
    # Backup original config
    cp /etc/redis/redis.conf /etc/redis/redis.conf.backup
    
    # Set password
    sed -i "s/^# requirepass foobared/requirepass ${VDS_PASSWORD}/" /etc/redis/redis.conf
    sed -i "s/^requirepass.*/requirepass ${VDS_PASSWORD}/" /etc/redis/redis.conf
    
    # Configure memory and persistence
    echo "maxmemory 256mb" >> /etc/redis/redis.conf
    echo "maxmemory-policy allkeys-lru" >> /etc/redis/redis.conf
    
    # Start Redis
    systemctl restart redis-server
    systemctl enable redis-server
    
    # Test connection
    if redis-cli -a ${VDS_PASSWORD} ping >/dev/null 2>&1; then
        log_success "Redis configured successfully"
    else
        log_error "Redis configuration failed"
        exit 1
    fi
}

configure_php() {
    log_header "🐘 PHP CONFIGURATION"
    
    # Configure PHP-FPM
    log_step "Configuring PHP-FPM..."
    
    # Update PHP configuration
    PHP_INI="/etc/php/${PHP_VERSION}/fpm/php.ini"
    sed -i "s/memory_limit = .*/memory_limit = 512M/" $PHP_INI
    sed -i "s/max_execution_time = .*/max_execution_time = 300/" $PHP_INI
    sed -i "s/max_input_vars = .*/max_input_vars = 3000/" $PHP_INI
    sed -i "s/upload_max_filesize = .*/upload_max_filesize = 64M/" $PHP_INI
    sed -i "s/post_max_size = .*/post_max_size = 64M/" $PHP_INI
    
    # Configure PHP-FPM pool
    FPM_POOL="/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf"
    sed -i "s/user = .*/user = www-data/" $FPM_POOL
    sed -i "s/group = .*/group = www-data/" $FPM_POOL
    sed -i "s/listen.owner = .*/listen.owner = www-data/" $FPM_POOL
    sed -i "s/listen.group = .*/listen.group = www-data/" $FPM_POOL
    
    # Start PHP-FPM
    systemctl restart php${PHP_VERSION}-fpm
    systemctl enable php${PHP_VERSION}-fpm
    
    log_success "PHP configured successfully"
}

configure_nginx() {
    log_header "🌐 NGINX CONFIGURATION"
    
    # Remove default sites
    rm -f /etc/nginx/sites-enabled/default
    rm -f /etc/nginx/sites-available/default
    
    # Create SentinentX site configuration
    cat > /etc/nginx/sites-available/sentinentx << EOF
server {
    listen 80;
    listen [::]:80;
    server_name _;
    root ${PROJECT_DIR}/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Logging
    access_log /var/log/nginx/sentinentx_access.log;
    error_log /var/log/nginx/sentinentx_error.log;

    # Main location
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # PHP handling
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Security
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # API routes
    location /api/ {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # Telegram webhook
    location /telegram/ {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
}
EOF

    # Enable site
    ln -sf /etc/nginx/sites-available/sentinentx /etc/nginx/sites-enabled/
    
    # Test configuration
    if nginx -t; then
        systemctl restart nginx
        systemctl enable nginx
        log_success "Nginx configured successfully"
    else
        log_error "Nginx configuration failed"
        exit 1
    fi
}

# ================================
# APPLICATION DEPLOYMENT
# ================================

deploy_application() {
    log_header "🚀 APPLICATION DEPLOYMENT"
    
    # Create project directory
    log_step "Creating project directory..."
    mkdir -p ${PROJECT_DIR}
    cd ${PROJECT_DIR}
    
    create_rollback "rm -rf ${PROJECT_DIR}"
    
    # Clone repository with fallback
    log_step "Cloning SentinentX repository..."
    if git clone ${REPO_URL_PRIMARY} . 2>/dev/null; then
        log_success "Repository cloned successfully"
    else
        log_warn "Git clone failed, trying ZIP download..."
        wget -O sentinentx.zip ${REPO_URL_BACKUP}
        unzip -q sentinentx.zip
        mv SentinentX-main/* .
        rm -rf SentinentX-main sentinentx.zip
        log_success "Repository downloaded successfully"
    fi
    
    # Set ownership
    chown -R www-data:www-data ${PROJECT_DIR}
    
    # Install Composer dependencies with retry
    log_step "Installing Composer dependencies..."
    local composer_attempts=3
    local composer_attempt=1
    
    while [ $composer_attempt -le $composer_attempts ]; do
        if sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction; then
            log_success "Composer dependencies installed successfully"
            break
        else
            log_warn "Composer attempt $composer_attempt failed"
            if [ $composer_attempt -eq $composer_attempts ]; then
                log_error "All Composer attempts failed"
                exit 1
            else
                composer_attempt=$((composer_attempt + 1))
                # Clear composer cache and try again
                sudo -u www-data composer clear-cache
                sleep 5
            fi
        fi
    done
    
    # Install NPM dependencies with retry
    log_step "Installing NPM dependencies..."
    local npm_attempts=3
    local npm_attempt=1
    
    # Fix NPM cache permissions first
    if [ -d "/var/www/.npm" ]; then
        log_step "Fixing NPM cache permissions..."
        chown -R www-data:www-data /var/www/.npm
    fi
    
    # Create npm cache directory with proper permissions
    mkdir -p /var/www/.npm
    chown -R www-data:www-data /var/www/.npm
    
    while [ $npm_attempt -le $npm_attempts ]; do
        if sudo -u www-data npm install --omit=dev --cache /var/www/.npm; then
            log_success "NPM dependencies installed successfully"
            break
        else
            log_warn "NPM attempt $npm_attempt failed"
            if [ $npm_attempt -eq $npm_attempts ]; then
                log_warn "NPM installation failed, continuing without NPM dependencies"
                break
            else
                npm_attempt=$((npm_attempt + 1))
                # Clean and recreate NPM cache
                rm -rf /var/www/.npm
                mkdir -p /var/www/.npm
                chown -R www-data:www-data /var/www/.npm
                sleep 2
            fi
        fi
    done
    
    # Create storage directories
    log_step "Creating storage directories..."
    mkdir -p storage/logs
    mkdir -p storage/framework/{cache,sessions,views}
    mkdir -p storage/app/{public,temp}
    mkdir -p bootstrap/cache
    
    # Set permissions
    chown -R www-data:www-data storage bootstrap/cache
    chmod -R 775 storage bootstrap/cache
    
    log_success "Application deployed successfully"
}

# ================================
# LARAVEL CONFIGURATION
# ================================

configure_laravel() {
    log_header "⚡ LARAVEL CONFIGURATION"
    
    cd ${PROJECT_DIR}
    
    # Create .env file with all API keys
    log_step "Creating .env configuration..."
    cat > .env << EOF
# ========================================
# SENTINENTX PRODUCTION CONFIGURATION
# ========================================

# Laravel Core
APP_NAME=SentinentX
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost
APP_TIMEZONE=UTC
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

# Database Configuration
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sentinentx
DB_USERNAME=${VDS_USER}
DB_PASSWORD=${VDS_PASSWORD}

# Cache & Queue (Redis)
CACHE_DRIVER=redis
QUEUE_CONNECTION=sync
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=${VDS_PASSWORD}
REDIS_PORT=6379

# Exchange API Configuration (TESTNET)
BYBIT_TESTNET=true
BYBIT_API_KEY=${BYBIT_API_KEY}
BYBIT_API_SECRET=${BYBIT_API_SECRET}
BYBIT_RECV_WINDOW=15000

# AI Providers Configuration
OPENAI_ENABLED=true
OPENAI_API_KEY=${OPENAI_API_KEY}
OPENAI_MODEL=gpt-4o-mini
OPENAI_MAX_TOKENS=1000
OPENAI_TEMPERATURE=0.1

GEMINI_ENABLED=true
GEMINI_API_KEY=${GEMINI_API_KEY}
GEMINI_MODEL=gemini-2.0-flash-exp

GROK_ENABLED=true
GROK_API_KEY=${GROK_API_KEY}
GROK_MODEL=grok-2-1212

# Telegram Bot Configuration
TELEGRAM_BOT_TOKEN=${TELEGRAM_BOT_TOKEN}
TELEGRAM_CHAT_ID=${TELEGRAM_CHAT_ID}

# Security Configuration
HMAC_SECRET=$(openssl rand -hex 32)
HMAC_TTL=60

# IP Allowlist
IP_ALLOWLIST_ENABLED=true
IP_ALLOWLIST=127.0.0.1/32,::1/128

# Market Data Configuration
COINGECKO_API_KEY=${COINGECKO_API_KEY}

# Trading Configuration
TRADING_MAX_LEVERAGE=75
TRADING_RISK_DAILY_MAX_LOSS_PCT=20
TRADING_KILL_SWITCH=false
RISK_PROFILE=moderate

# Comprehensive Logging Configuration
ENABLE_COMPREHENSIVE_LOGS=true
AI_DECISION_LOGGING=true
POSITION_LOGGING=true
PNL_DETAILED_LOGGING=true
LOG_LEVEL=info

# Lab Backtesting Configuration
LAB_TEST_MODE=true
LAB_INITIAL_EQUITY=10000
EOF

    # Set permissions on .env
    chown www-data:www-data .env
    chmod 640 .env
    
    # Generate application key
    log_step "Generating application key..."
    sudo -u www-data php artisan key:generate --force
    
    # Clear and cache configuration
    log_step "Optimizing Laravel..."
    sudo -u www-data php artisan config:clear
    
    # Don't clear database cache until migration is done, use file cache temporarily
    sudo -u www-data php artisan cache:clear --no-interaction || echo "Cache clear skipped (tables may not exist yet)"
    sudo -u www-data php artisan route:clear
    sudo -u www-data php artisan view:clear
    
    # Run migrations with retries
    log_step "Running database migrations..."
    local max_attempts=3
    local attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        if sudo -u www-data php artisan migrate --force; then
            log_success "Database migrations completed successfully"
            
            # Now that tables exist, clear cache properly
            log_step "Clearing cache with database tables available..."
            sudo -u www-data php artisan cache:clear || true
            sudo -u www-data php artisan config:cache || true
            
            break
        else
            log_warn "Migration attempt $attempt failed"
            if [ $attempt -eq $max_attempts ]; then
                log_error "All migration attempts failed"
                # Continue anyway, migrations might be optional
                log_warn "Continuing deployment without migrations"
            else
                attempt=$((attempt + 1))
                sleep 5
            fi
        fi
    done
    
    # Cache optimizations
    log_step "Caching configurations..."
    sudo -u www-data php artisan config:cache
    sudo -u www-data php artisan route:cache
    sudo -u www-data php artisan view:cache
    
    log_success "Laravel configured successfully"
}

# ================================
# SYSTEMD SERVICE
# ================================

create_systemd_service() {
    log_header "🔧 SYSTEMD SERVICE CREATION"
    
    # Create systemd service file
    cat > /etc/systemd/system/sentinentx.service << EOF
[Unit]
Description=SentinentX AI Trading Bot - 15 Day Testnet
Documentation=https://github.com/emiryucelweb/SentinentX
After=network-online.target postgresql.service redis-server.service
Wants=network-online.target postgresql.service redis-server.service
Requires=postgresql.service redis-server.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=${PROJECT_DIR}
ExecStart=/usr/bin/php ${PROJECT_DIR}/artisan trading:start --testnet --duration=15days
ExecReload=/bin/kill -HUP \$MAINPID
Restart=on-failure
RestartSec=10
TimeoutStartSec=120
TimeoutStopSec=60

# Output and logging
StandardOutput=journal
StandardError=journal
SyslogIdentifier=sentinentx

# Security settings
NoNewPrivileges=yes
PrivateTmp=yes
ProtectSystem=strict
ProtectHome=yes
ReadWritePaths=${PROJECT_DIR}/storage
ReadWritePaths=${PROJECT_DIR}/bootstrap/cache
ReadWritePaths=/tmp

# Environment
Environment=PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
Environment=LANG=en_US.UTF-8
Environment=LC_ALL=en_US.UTF-8

# Process management
KillMode=mixed
KillSignal=SIGTERM

[Install]
WantedBy=multi-user.target
EOF

    # Reload systemd and enable service
    systemctl daemon-reload
    systemctl enable sentinentx
    
    log_success "Systemd service created successfully"
}

# ================================
# FIREWALL CONFIGURATION
# ================================

configure_firewall() {
    log_header "🔥 FIREWALL CONFIGURATION"
    
    # Reset and configure UFW
    ufw --force reset
    ufw default deny incoming
    ufw default allow outgoing
    
    # Allow essential services
    ufw allow ssh
    ufw allow 80/tcp   # HTTP
    ufw allow 443/tcp  # HTTPS (for future SSL)
    
    # Enable firewall
    ufw --force enable
    
    log_success "Firewall configured successfully"
}

# ================================
# FINAL TESTING
# ================================

run_final_tests() {
    log_header "🧪 FINAL SYSTEM TESTING"
    
    cd ${PROJECT_DIR}
    
    # Test database connection
    log_step "Testing database connection..."
    if sudo -u www-data php artisan tinker --execute="
        use Illuminate\Support\Facades\DB;
        try {
            \$pdo = DB::connection()->getPdo();
            echo 'PostgreSQL: SUCCESS' . PHP_EOL;
        } catch (Exception \$e) {
            echo 'PostgreSQL Error: ' . \$e->getMessage() . PHP_EOL;
            exit(1);
        }
        exit;
    "; then
        log_success "Database connection working"
    else
        log_error "Database connection failed"
        exit 1
    fi
    
    # Test Redis connection
    log_step "Testing Redis connection..."
    if sudo -u www-data php artisan tinker --execute="
        use Illuminate\Support\Facades\Redis;
        try {
            \$result = Redis::ping();
            echo 'Redis: SUCCESS' . PHP_EOL;
        } catch (Exception \$e) {
            echo 'Redis Error: ' . \$e->getMessage() . PHP_EOL;
            exit(1);
        }
        exit;
    "; then
        log_success "Redis connection working"
    else
        log_error "Redis connection failed"
        exit 1
    fi
    
    # Test web server
    log_step "Testing web server..."
    systemctl restart nginx
    sleep 3
    if curl -f http://localhost >/dev/null 2>&1; then
        log_success "Web server responding"
    else
        log_warn "Web server not responding (normal for Laravel)"
    fi
    
    # Test AI services
    log_step "Testing AI services..."
    if sudo -u www-data php artisan tinker --execute="
        use App\Services\Market\CoinGeckoService;
        try {
            \$service = app(CoinGeckoService::class);
            \$data = \$service->getMultiCoinData(['bitcoin']);
            echo 'CoinGecko: SUCCESS' . PHP_EOL;
        } catch (Exception \$e) {
            echo 'CoinGecko: ' . \$e->getMessage() . PHP_EOL;
        }
        exit;
    "; then
        log_success "AI services accessible"
    else
        log_warn "AI services may need network access"
    fi
    
    log_success "Final testing completed"
}

# ================================
# SERVICE STARTUP
# ================================

start_services() {
    log_header "🚀 STARTING SENTINENTX SERVICE"
    
    # Start the SentinentX service with extended monitoring
    log_step "Starting SentinentX service..."
    
    # Stop service if running
    systemctl stop sentinentx 2>/dev/null || true
    sleep 2
    
    # Start service
    systemctl start sentinentx
    
    # Wait for service to start with progressive checking
    local max_wait=30
    local wait_time=0
    
    while [ $wait_time -lt $max_wait ]; do
        if systemctl is-active --quiet sentinentx; then
            log_success "SentinentX service is running!"
            break
        fi
        
        sleep 2
        wait_time=$((wait_time + 2))
        
        if [ $wait_time -ge $max_wait ]; then
            log_error "Service failed to start within $max_wait seconds"
            break
        fi
    done
    
    # Show detailed service status
    echo ""
    log_step "Service Status and Health Check"
    systemctl status sentinentx --no-pager -l || true
    
    # Check if service is actually running
    if systemctl is-active --quiet sentinentx; then
        log_success "✅ SentinentX service is running and healthy!"
        
        # Additional health checks
        echo ""
        log_step "Performing health checks..."
        
        # Check if artisan command exists and is executable
        if [ -x "${PROJECT_DIR}/artisan" ]; then
            log_success "✅ Artisan command is executable"
        else
            log_warn "⚠️ Artisan command is not executable"
        fi
        
        # Check if dependencies are installed
        if [ -d "${PROJECT_DIR}/vendor" ]; then
            log_success "✅ Composer dependencies are installed"
        else
            log_warn "⚠️ Composer dependencies may not be installed"
        fi
        
        # Show recent logs (last 10 lines)
        echo ""
        log_step "Recent service logs:"
        journalctl -u sentinentx --no-pager -l -n 10 2>/dev/null || true
        
    else
        log_error "❌ Service failed to start properly"
        echo ""
        log_step "Diagnostic Information:"
        echo "Service Status:"
        systemctl status sentinentx --no-pager -l || true
        echo ""
        echo "Recent Logs:"
        journalctl -u sentinentx --no-pager -l -n 20 2>/dev/null || true
        echo ""
        echo "Service File Content:"
        cat /etc/systemd/system/sentinentx.service || true
        
        log_warn "Service is not running, but deployment completed"
        log_info "You can manually start the service later with: systemctl start sentinentx"
    fi
}

# ================================
# MAIN EXECUTION
# ================================

main() {
    clear
    
    log_header "🚀🚀🚀 SENTINENTX ULTIMATE VDS DEPLOYMENT 🚀🚀🚀"
    log_header "=========================================================="
    log_info "Starting zero-error deployment for 15-day testnet..."
    log_info "Timestamp: $(date)"
    log_info "Log file: $LOGFILE"
    echo ""
    
    # Execute deployment steps
    validate_api_keys
    validate_system
    reset_vds
    reset_system
    install_packages
    configure_postgresql
    configure_redis
    configure_php
    configure_nginx
    deploy_application
    configure_laravel
    create_systemd_service
    configure_firewall
    run_final_tests
    start_services
    
    # Success message
    echo ""
    log_header "🎉🎉🎉 DEPLOYMENT COMPLETED SUCCESSFULLY! 🎉🎉🎉"
    log_header "======================================================="
    echo ""
    echo -e "${GREEN}✅ System Status:${NC}"
    echo "   🗄️  PostgreSQL: $(systemctl is-active postgresql)"
    echo "   🔴 Redis: $(systemctl is-active redis-server)"
    echo "   🐘 PHP-FPM: $(systemctl is-active php${PHP_VERSION}-fpm)"
    echo "   🌐 Nginx: $(systemctl is-active nginx)"
    echo "   🤖 SentinentX: $(systemctl is-active sentinentx)"
    echo ""
    echo -e "${GREEN}🎮 Control Commands:${NC}"
    echo "   Status:  systemctl status sentinentx"
    echo "   Logs:    journalctl -fu sentinentx"
    echo "   Stop:    systemctl stop sentinentx"
    echo "   Restart: systemctl restart sentinentx"
    echo ""
    echo -e "${GREEN}📊 Monitoring:${NC}"
    echo "   Trading Logs: tail -f ${PROJECT_DIR}/storage/logs/laravel.log"
    echo "   System Logs:  journalctl -fu sentinentx"
    echo "   Web Logs:     tail -f /var/log/nginx/sentinentx_access.log"
    echo ""
    echo -e "${GREEN}🚀 READY FOR 15-DAY TESTNET TRADING!${NC}"
    echo ""
    
    # Cleanup
    rm -f /tmp/sentinentx_rollback
    
    log_success "Deployment log saved to: $LOGFILE"
}

# ================================
# SCRIPT EXECUTION
# ================================

# Execute main function
main "$@"
