#!/bin/bash

# SentinentX Ultra-Fixed VDS Installation Script
# Optimized for Ubuntu 24.04 LTS x64 with NVIDIA & dependency fixes
# Run with: curl -sSL https://raw.githubusercontent.com/emiryucelweb/SentinentX/main/quick_vds_install_fixed.sh | bash

set -euo pipefail
IFS=$'\n\t'

echo "🚀 SentinentX Ultra-Fixed VDS Installation"
echo "=========================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

# Global variables
SCRIPT_DIR="/tmp/sentinentx_install"
LOG_FILE="$SCRIPT_DIR/install.log"
CONFIG_FILE="$SCRIPT_DIR/config"
UBUNTU_VERSION=$(lsb_release -rs)
UBUNTU_CODENAME=$(lsb_release -sc)
MAX_RETRIES=3
RETRY_DELAY=5

# Create working directory
mkdir -p "$SCRIPT_DIR"
touch "$LOG_FILE" "$CONFIG_FILE"

# Enhanced logging functions
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1" | tee -a "$LOG_FILE"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1" | tee -a "$LOG_FILE"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1" | tee -a "$LOG_FILE"
}

log_debug() {
    echo -e "${BLUE}[DEBUG]${NC} $1" | tee -a "$LOG_FILE"
}

log_success() {
    echo -e "${CYAN}[SUCCESS]${NC} $1" | tee -a "$LOG_FILE"
}

# Error handling
trap 'handle_error $? $LINENO' ERR

handle_error() {
    local exit_code=$1
    local line_number=$2
    log_error "Script failed at line $line_number with exit code $exit_code"
    log_error "Check log: $LOG_FILE"
    exit $exit_code
}

# Retry function
retry_command() {
    local max_attempts=$1
    local delay=$2
    local command="$3"
    local attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        log_debug "Attempt $attempt/$max_attempts: $command"
        if eval "$command"; then
            return 0
        else
            if [ $attempt -eq $max_attempts ]; then
                log_error "Command failed after $max_attempts attempts: $command"
                return 1
            else
                log_warn "Attempt $attempt failed, retrying in ${delay}s..."
                sleep $delay
            fi
        fi
        ((attempt++))
    done
}

# System requirements check
# Complete VDS reset and cleanup
reset_vds_completely() {
    log_info "🧹 COMPLETE VDS RESET - Cleaning all existing installations..."
    
    # Stop all services that might interfere
    local services_to_stop=(
        "nginx" "apache2" "httpd"
        "php8.3-fpm" "php8.2-fpm" "php8.1-fpm" "php8.0-fpm" "php7.4-fpm"
        "postgresql" "mysql" "mariadb"
        "redis-server" "redis"
        "sentinentx-queue" "sentinentx-telegram"
    )
    
    for service in "${services_to_stop[@]}"; do
        systemctl stop "$service" 2>/dev/null || true
        systemctl disable "$service" 2>/dev/null || true
    done
    
    # Remove all web servers
    log_debug "Removing web servers..."
    DEBIAN_FRONTEND=noninteractive apt-get remove --purge -y \
        nginx nginx-common nginx-core \
        apache2 apache2-common apache2-utils \
        lighttpd \
        2>/dev/null || true
    
    # Remove all PHP versions
    log_debug "Removing all PHP versions..."
    DEBIAN_FRONTEND=noninteractive apt-get remove --purge -y \
        'php*' \
        2>/dev/null || true
    
    # Remove databases
    log_debug "Removing databases..."
    DEBIAN_FRONTEND=noninteractive apt-get remove --purge -y \
        postgresql postgresql-* \
        mysql-server mysql-client mysql-common \
        mariadb-server mariadb-client \
        2>/dev/null || true
    
    # Remove Redis
    log_debug "Removing Redis..."
    DEBIAN_FRONTEND=noninteractive apt-get remove --purge -y \
        redis-server redis-tools \
        2>/dev/null || true
    
    # Remove Node.js
    log_debug "Removing Node.js..."
    DEBIAN_FRONTEND=noninteractive apt-get remove --purge -y \
        nodejs npm \
        2>/dev/null || true
    
    # Clean configuration directories
    log_debug "Cleaning configuration directories..."
    rm -rf /etc/nginx /var/log/nginx /var/lib/nginx
    rm -rf /etc/apache2 /var/log/apache2
    rm -rf /etc/php /var/lib/php
    rm -rf /etc/postgresql /var/lib/postgresql
    rm -rf /etc/mysql /var/lib/mysql
    rm -rf /etc/redis /var/lib/redis
    rm -rf /var/www/html /var/www/sentinentx
    rm -rf /usr/local/bin/composer
    
    # Remove systemd services
    rm -f /etc/systemd/system/sentinentx-*.service
    rm -f /etc/systemd/system/nginx.service.d/*
    systemctl daemon-reload
    
    # Clean package lists and caches
    rm -f /etc/apt/sources.list.d/php.list
    rm -f /etc/apt/sources.list.d/nginx.list
    rm -f /etc/apt/sources.list.d/postgresql.list
    rm -f /etc/apt/sources.list.d/redis.list
    rm -f /etc/apt/sources.list.d/nodesource.list
    rm -f /usr/share/keyrings/php-archive-keyring.gpg
    rm -f /usr/share/keyrings/nginx-archive-keyring.gpg
    rm -f /usr/share/keyrings/postgresql-archive-keyring.gpg
    
    # Complete package cleanup
    apt-get clean
    apt-get autoclean
    apt-get autoremove --purge -y
    
    # Fix broken packages
    DEBIAN_FRONTEND=noninteractive apt-get install -f -y
    
    # Kill any remaining processes
    pkill -f nginx 2>/dev/null || true
    pkill -f apache 2>/dev/null || true
    pkill -f php-fpm 2>/dev/null || true
    pkill -f postgresql 2>/dev/null || true
    pkill -f redis 2>/dev/null || true
    
    log_success "VDS completely reset and cleaned"
}

check_system_requirements() {
    log_info "Checking system requirements..."
    
    # Check if root
    if [[ $EUID -ne 0 ]]; then
        log_error "This script must be run as root"
        exit 1
    fi
    
    # Check Ubuntu version
    if [[ "$UBUNTU_VERSION" != "24.04" ]] && [[ "$UBUNTU_VERSION" != "22.04" ]]; then
        log_warn "Unsupported Ubuntu version: $UBUNTU_VERSION (supported: 22.04, 24.04)"
    fi
    
    # Check architecture
    if [[ "$(uname -m)" != "x86_64" ]]; then
        log_error "Only x86_64 architecture is supported"
        exit 1
    fi
    
    # Check available disk space (10GB)
    local available_space=$(df / | awk 'NR==2 {print $4}')
    local required_space=10485760 # 10GB in KB
    
    if [[ $available_space -lt $required_space ]]; then
        log_error "Insufficient disk space. Required: 10GB, Available: $((available_space/1024/1024))GB"
        exit 1
    fi
    
    # Check RAM (2GB)
    local available_ram=$(free -k | awk 'NR==2{print $2}')
    local required_ram=2097152 # 2GB in KB
    
    if [[ $available_ram -lt $required_ram ]]; then
        log_error "Insufficient RAM. Required: 2GB, Available: $((available_ram/1024/1024))GB"
        exit 1
    fi
    
    log_success "System requirements check passed"
    log_info "Ubuntu $UBUNTU_VERSION ($UBUNTU_CODENAME) x86_64 detected"
}

# Fix NVIDIA and package conflicts
fix_package_conflicts() {
    log_info "Fixing package conflicts and NVIDIA issues..."
    
    # Remove problematic packages that cause loops
    local problematic_packages=(
        "nvidia-*"
        "dh-dlopenlibdeps"
        "far2l-wx"
        "lsp-plugins-vst3"
        "python3-sphinxcontrib-globalsubs"
    )
    
    for pkg_pattern in "${problematic_packages[@]}"; do
        log_debug "Removing packages matching: $pkg_pattern"
        apt-mark unhold $pkg_pattern 2>/dev/null || true
        DEBIAN_FRONTEND=noninteractive apt-get remove --purge -y $pkg_pattern 2>/dev/null || true
    done
    
    # Clean package cache
    apt-get clean
    apt-get autoclean
    apt-get autoremove -y
    
    # Fix broken packages
    DEBIAN_FRONTEND=noninteractive apt-get install -f -y
    
    log_success "Package conflicts resolved"
}

# Update system packages
update_system() {
    log_info "Updating system packages..."
    
    # Clean sources first
    rm -f /etc/apt/sources.list.d/php.list 2>/dev/null || true
    rm -f /usr/share/keyrings/php-archive-keyring.gpg 2>/dev/null || true
    
    # Update with retry
    retry_command $MAX_RETRIES $RETRY_DELAY "DEBIAN_FRONTEND=noninteractive apt-get update"
    
    # Upgrade essential packages only
    DEBIAN_FRONTEND=noninteractive apt-get upgrade -y \
        --no-install-recommends \
        --no-install-suggests \
        -o Dpkg::Options::="--force-confdef" \
        -o Dpkg::Options::="--force-confold"
    
    log_success "System updated successfully"
}

# Install essential packages
install_essential_packages() {
    log_info "Installing essential packages..."
    
    local essential_packages=(
        "curl"
        "wget"
        "gnupg"
        "lsb-release"
        "ca-certificates"
        "software-properties-common"
        "apt-transport-https"
        "unzip"
        "git"
        "htop"
        "nano"
        "ufw"
        "lsof"
        "net-tools"
    )
    
    for package in "${essential_packages[@]}"; do
        log_debug "Installing: $package"
        retry_command $MAX_RETRIES $RETRY_DELAY "DEBIAN_FRONTEND=noninteractive apt-get install -y $package"
    done
    
    log_success "Essential packages installed"
}

# Install PHP (Ubuntu native version)
install_php() {
    log_info "Installing PHP..."
    
    # Determine PHP version based on Ubuntu
    if [[ "$UBUNTU_CODENAME" == "noble" ]]; then
        PHP_VERSION="8.3"
        log_info "Using Ubuntu 24.04 native PHP 8.3"
    else
        PHP_VERSION="8.2"
        log_info "Using PHP 8.2 for Ubuntu $UBUNTU_VERSION"
        
        # Only add Sury repository for older Ubuntu versions
        log_debug "Adding Ondrej PHP repository..."
        curl -fsSL https://packages.sury.org/php/apt.gpg | gpg --dearmor -o /usr/share/keyrings/php-archive-keyring.gpg
        echo "deb [signed-by=/usr/share/keyrings/php-archive-keyring.gpg] https://packages.sury.org/php/ $UBUNTU_CODENAME main" > /etc/apt/sources.list.d/php.list
        retry_command $MAX_RETRIES $RETRY_DELAY "apt-get update"
    fi
    
    # PHP packages
    local php_packages=(
        "php${PHP_VERSION}"
        "php${PHP_VERSION}-cli"
        "php${PHP_VERSION}-fpm"
        "php${PHP_VERSION}-common"
        "php${PHP_VERSION}-mysql"
        "php${PHP_VERSION}-pgsql"
        "php${PHP_VERSION}-redis"
        "php${PHP_VERSION}-xml"
        "php${PHP_VERSION}-mbstring"
        "php${PHP_VERSION}-curl"
        "php${PHP_VERSION}-zip"
        "php${PHP_VERSION}-gd"
        "php${PHP_VERSION}-bcmath"
        "php${PHP_VERSION}-intl"
        "php${PHP_VERSION}-soap"
        "php${PHP_VERSION}-opcache"
    )
    
    for package in "${php_packages[@]}"; do
        log_debug "Installing: $package"
        retry_command $MAX_RETRIES $RETRY_DELAY "DEBIAN_FRONTEND=noninteractive apt-get install -y $package"
    done
    
    # Configure PHP-FPM
    systemctl enable php${PHP_VERSION}-fpm
    systemctl start php${PHP_VERSION}-fpm
    
    log_success "PHP ${PHP_VERSION} installed and configured"
}

# Install Composer
install_composer() {
    log_info "Installing Composer..."
    
    # Download and install Composer
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
    
    # Verify installation
    if composer --version &>/dev/null; then
        log_success "Composer installed successfully"
    else
        log_error "Composer installation failed"
        exit 1
    fi
}

# Install PostgreSQL
install_postgresql() {
    log_info "Installing PostgreSQL..."
    
    retry_command $MAX_RETRIES $RETRY_DELAY "DEBIAN_FRONTEND=noninteractive apt-get install -y postgresql postgresql-contrib"
    
    systemctl enable postgresql
    systemctl start postgresql
    
    # Generate secure password
    DB_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
    
    # Create database and user
    sudo -u postgres psql -c "CREATE DATABASE sentinentx;" 2>/dev/null || true
    sudo -u postgres psql -c "CREATE USER sentinentx WITH ENCRYPTED PASSWORD '$DB_PASSWORD';" 2>/dev/null || true
    sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE sentinentx TO sentinentx;" 2>/dev/null || true
    
    # Save password to config
    echo "DB_PASSWORD=$DB_PASSWORD" >> "$CONFIG_FILE"
    
    log_success "PostgreSQL installed and configured"
}

# Install Redis
install_redis() {
    log_info "Installing Redis..."
    
    retry_command $MAX_RETRIES $RETRY_DELAY "DEBIAN_FRONTEND=noninteractive apt-get install -y redis-server"
    
    # Generate secure password
    REDIS_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
    
    # Configure Redis with password
    echo "requirepass $REDIS_PASSWORD" >> /etc/redis/redis.conf
    
    systemctl enable redis-server
    systemctl restart redis-server
    
    # Save password to config
    echo "REDIS_PASSWORD=$REDIS_PASSWORD" >> "$CONFIG_FILE"
    
    log_success "Redis installed and configured"
}

# Install Nginx with enhanced error handling
install_nginx() {
    log_info "Installing Nginx..."
    
    # Ensure no conflicting web servers
    systemctl stop apache2 2>/dev/null || true
    systemctl disable apache2 2>/dev/null || true
    
    # Install nginx with enhanced checks
    log_debug "Installing nginx package..."
    if ! retry_command $MAX_RETRIES $RETRY_DELAY "DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends nginx"; then
        log_error "Failed to install nginx package"
        
        # Try alternative installation method
        log_info "Trying alternative nginx installation..."
        DEBIAN_FRONTEND=noninteractive apt-get install -y nginx-core nginx-common
        
        if ! command -v nginx &> /dev/null; then
            log_error "Nginx installation completely failed"
            exit 1
        fi
    fi
    
    # Verify nginx binary exists
    if ! command -v nginx &> /dev/null; then
        log_error "Nginx binary not found after installation"
        exit 1
    fi
    
    # Ensure nginx directories exist and fix configuration issues
    mkdir -p /etc/nginx/sites-available /etc/nginx/sites-enabled
    
    # Fix any potential configuration issues
    nginx -t 2>/dev/null || {
        log_warn "Nginx configuration test failed, fixing..."
        
        # Remove any conflicting configs
        rm -f /etc/nginx/sites-enabled/default
        
        # Create minimal working config
        cat > /etc/nginx/sites-available/default << 'NGINXEOF'
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    
    root /var/www/html;
    index index.html index.htm;
    
    server_name _;
    
    location / {
        try_files $uri $uri/ =404;
    }
    
    location ~ /\.ht {
        deny all;
    }
}
NGINXEOF
        
        # Enable the site
        ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default
        
        # Test again
        if ! nginx -t; then
            log_error "Still can't fix nginx config, showing nginx.conf:"
            cat /etc/nginx/nginx.conf
            exit 1
        fi
    }
    
    # Create web directory
    mkdir -p /var/www/html
    echo "<h1>SentinentX Server Ready</h1>" > /var/www/html/index.html
    chown -R www-data:www-data /var/www/html
    
    # Test configuration again
    if ! nginx -t; then
        log_error "Nginx configuration is invalid"
        cat /etc/nginx/nginx.conf | head -20
        exit 1
    fi
    
    # Enable and start with retry
    systemctl enable nginx || {
        log_warn "Failed to enable nginx, trying manual enable..."
        systemctl daemon-reload
        systemctl enable nginx
    }
    
    # Start nginx with specific checks
    if ! systemctl start nginx; then
        log_error "Failed to start nginx, checking logs..."
        journalctl -u nginx --no-pager --lines=10
        
        # Try to fix common issues
        log_info "Attempting to fix nginx startup issues..."
        
        # Kill any processes using port 80
        lsof -ti:80 | xargs kill -9 2>/dev/null || true
        
        # Check if there are any competing services
        systemctl stop apache2 2>/dev/null || true
        systemctl stop lighttpd 2>/dev/null || true
        
        # Try starting again
        sleep 2
        if ! systemctl start nginx; then
            log_error "Nginx still failing to start"
            exit 1
        fi
    fi
    
    # Verify nginx is running
    if ! systemctl is-active --quiet nginx; then
        log_error "Nginx is not running after installation"
        systemctl status nginx --no-pager
        exit 1
    fi
    
    # Configure basic Nginx for SentinentX
    cat > /etc/nginx/sites-available/sentinentx << EOF
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    
    root /var/www/sentinentx/public;
    index index.php index.html;
    
    server_name _;
    
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.ht {
        deny all;
    }
}
EOF
    
    # Enable site
    ln -sf /etc/nginx/sites-available/sentinentx /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default
    
    # Test and reload Nginx
    nginx -t && systemctl reload nginx
    
    log_success "Nginx installed and configured"
}

# Install Node.js
install_nodejs() {
    log_info "Installing Node.js..."
    
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    retry_command $MAX_RETRIES $RETRY_DELAY "DEBIAN_FRONTEND=noninteractive apt-get install -y nodejs"
    
    log_success "Node.js $(node --version) installed"
}

# Create systemd services
create_systemd_services() {
    log_info "Creating systemd services..."
    
    # SentinentX Queue Service
    cat > /etc/systemd/system/sentinentx-queue.service << 'EOF'
[Unit]
Description=SentinentX Queue Worker
After=redis.service postgresql.service

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
RestartSec=5
WorkingDirectory=/var/www/sentinentx
ExecStart=/usr/bin/php /var/www/sentinentx/artisan queue:work --sleep=3 --tries=3 --timeout=90

[Install]
WantedBy=multi-user.target
EOF

    # SentinentX Telegram Service
    cat > /etc/systemd/system/sentinentx-telegram.service << 'EOF'
[Unit]
Description=SentinentX Telegram Bot
After=redis.service postgresql.service

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
RestartSec=5
WorkingDirectory=/var/www/sentinentx
ExecStart=/usr/bin/php /var/www/sentinentx/artisan telegram:polling

[Install]
WantedBy=multi-user.target
EOF

    # Reload systemd
    systemctl daemon-reload
    
    log_success "Systemd services created"
}

# Configure firewall
configure_firewall() {
    log_info "Configuring firewall..."
    
    ufw --force enable
    ufw default deny incoming
    ufw default allow outgoing
    ufw allow ssh
    ufw allow 80/tcp
    ufw allow 443/tcp
    
    log_success "Firewall configured"
}

# Final setup
final_setup() {
    log_info "Performing final setup..."
    
    # Create placeholder directory
    mkdir -p /var/www/sentinentx
    chown -R www-data:www-data /var/www/sentinentx
    
    # Save installation info
    echo "INSTALLATION_DATE=$(date)" >> "$CONFIG_FILE"
    echo "PHP_VERSION=$PHP_VERSION" >> "$CONFIG_FILE"
    echo "UBUNTU_VERSION=$UBUNTU_VERSION" >> "$CONFIG_FILE"
    
    # Copy config to persistent location
    cp "$CONFIG_FILE" /var/log/sentinentx_install_config
    
    log_success "Installation completed successfully!"
}

# Main installation function
main() {
    log_info "Starting SentinentX VDS installation..."
    
    reset_vds_completely
    check_system_requirements
    fix_package_conflicts
    update_system
    install_essential_packages
    install_php
    install_composer
    install_postgresql
    install_redis
    install_nginx
    install_nodejs
    create_systemd_services
    configure_firewall
    final_setup
    
    echo ""
    echo "🎉 SentinentX VDS Installation Completed!"
    echo "======================================="
    echo "• PHP Version: $PHP_VERSION"
    echo "• Database: PostgreSQL with auto-generated password"
    echo "• Cache: Redis with auto-generated password"
    echo "• Web Server: Nginx"
    echo "• Config File: /var/log/sentinentx_install_config"
    echo ""
    echo "Next steps:"
    echo "1. Run the SentinentX deployment script"
    echo "2. Configure API keys"
    echo "3. Start 15-day testnet"
    echo ""
}

# Run main function
main "$@"
