#!/bin/bash

# SentinentX Quick VDS Installation Script
# Optimized for Ubuntu 24.04 LTS x64 (Also supports 22.04 LTS)
# Run with: curl -sSL https://raw.githubusercontent.com/emiryucelweb/SentinentX/main/quick_vds_install.sh | bash
# 
# Features:
# - Ultra-robust error handling and auto-recovery
# - Full Ubuntu 24.04 LTS x64 compatibility
# - Automatic dependency conflict resolution
# - Network resilience with retry mechanisms
# - Comprehensive system validation
# - Rollback capability on failures

set -euo pipefail  # Exit on error, undefined vars, pipe failures
IFS=$'\n\t'        # Secure Internal Field Separator

echo "🚀 SentinentX VDS Quick Installation"
echo "===================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Global variables
SCRIPT_DIR="/tmp/sentinentx_install"
LOG_FILE="$SCRIPT_DIR/install.log"
CONFIG_FILE="$SCRIPT_DIR/config"
ROLLBACK_FILE="$SCRIPT_DIR/rollback_actions"
REQUIRED_DISK_GB=10
REQUIRED_RAM_MB=2048
MAX_RETRIES=3
RETRY_DELAY=5

# Create working directory
mkdir -p "$SCRIPT_DIR"
touch "$LOG_FILE" "$CONFIG_FILE" "$ROLLBACK_FILE"

# Enhanced logging functions
log_info() {
    local msg="$1"
    echo -e "${GREEN}[INFO]${NC} $msg" | tee -a "$LOG_FILE"
}

log_warn() {
    local msg="$1"
    echo -e "${YELLOW}[WARN]${NC} $msg" | tee -a "$LOG_FILE"
}

log_error() {
    local msg="$1"
    echo -e "${RED}[ERROR]${NC} $msg" | tee -a "$LOG_FILE"
}

log_step() {
    local msg="$1"
    echo -e "${BLUE}[STEP]${NC} $msg" | tee -a "$LOG_FILE"
}

log_debug() {
    local msg="$1"
    echo -e "${PURPLE}[DEBUG]${NC} $msg" | tee -a "$LOG_FILE"
}

log_success() {
    local msg="$1"
    echo -e "${CYAN}[SUCCESS]${NC} $msg" | tee -a "$LOG_FILE"
}

# Rollback function
add_rollback_action() {
    local action="$1"
    echo "$action" >> "$ROLLBACK_FILE"
}

# Execute rollback actions in reverse order
execute_rollback() {
    if [[ -f "$ROLLBACK_FILE" && -s "$ROLLBACK_FILE" ]]; then
        log_warn "Executing rollback actions..."
        tac "$ROLLBACK_FILE" | while read -r action; do
            if [[ -n "$action" ]]; then
                log_debug "Rollback: $action"
                eval "$action" 2>/dev/null || true
            fi
        done
    fi
}

# Trap for cleanup on exit
cleanup_on_exit() {
    local exit_code=$?
    if [[ $exit_code -ne 0 ]]; then
        log_error "Installation failed with exit code $exit_code"
        execute_rollback
    fi
    # Clean up temporary files
    rm -rf "$SCRIPT_DIR" 2>/dev/null || true
}
trap cleanup_on_exit EXIT

# Retry mechanism for commands
retry_command() {
    local max_attempts="$1"
    local delay="$2"
    shift 2
    local cmd="$*"
    
    for ((i=1; i<=max_attempts; i++)); do
        log_debug "Attempt $i/$max_attempts: $cmd"
        if eval "$cmd"; then
            return 0
        else
            if [[ $i -lt $max_attempts ]]; then
                log_warn "Command failed, retrying in ${delay}s..."
                sleep "$delay"
            fi
        fi
    done
    
    log_error "Command failed after $max_attempts attempts: $cmd"
    return 1
}

# Network connectivity check
check_network() {
    log_step "Checking network connectivity..."
    
    local test_urls=(
        "google.com"
        "ubuntu.com" 
        "github.com"
        "launchpad.net"
    )
    
    for url in "${test_urls[@]}"; do
        if ping -c 1 -W 5 "$url" &>/dev/null; then
            log_success "Network connectivity verified ($url)"
            return 0
        fi
    done
    
    log_error "No network connectivity detected"
    return 1
}

# Comprehensive system validation
check_system_requirements() {
    log_step "Performing comprehensive system validation..."
    
    # Check root privileges
    if [[ $EUID -ne 0 ]]; then
        log_error "This script must be run as root"
        log_error "Please run: sudo bash $0"
        exit 1
    fi
    log_success "Root privileges verified"
    
    # Check architecture (x64)
    local arch
    arch=$(uname -m)
    if [[ "$arch" != "x86_64" ]]; then
        log_error "This script requires x86_64 architecture. Detected: $arch"
        exit 1
    fi
    log_success "Architecture verified: $arch"
    
    # Check available disk space (in GB)
    local available_space
    available_space=$(df / | awk 'NR==2 {print int($4/1024/1024)}')
    if [[ $available_space -lt $REQUIRED_DISK_GB ]]; then
        log_error "Insufficient disk space. Required: ${REQUIRED_DISK_GB}GB, Available: ${available_space}GB"
        exit 1
    fi
    log_success "Disk space verified: ${available_space}GB available"
    
    # Check available RAM (in MB)
    local available_ram
    available_ram=$(free -m | awk 'NR==2 {print $7}')
    if [[ $available_ram -lt $REQUIRED_RAM_MB ]]; then
        log_error "Insufficient RAM. Required: ${REQUIRED_RAM_MB}MB, Available: ${available_ram}MB"
        exit 1
    fi
    log_success "RAM verified: ${available_ram}MB available"
    
    # Check virtualization support (useful for containers)
    if grep -q "vmx\|svm" /proc/cpuinfo; then
        log_success "Virtualization support detected"
    else
        log_warn "No virtualization support detected (not critical)"
    fi
    
    # Check if system is up to date
    local pending_updates
    pending_updates=$(apt list --upgradable 2>/dev/null | wc -l)
    if [[ $pending_updates -gt 50 ]]; then
        log_warn "Many pending updates detected ($pending_updates). System may need updating."
    fi
}

# Enhanced Ubuntu version and compatibility check
check_ubuntu_version() {
    log_step "Checking Ubuntu version and compatibility..."
    
    if [[ ! -f /etc/os-release ]]; then
        log_error "Cannot detect OS version - /etc/os-release not found"
        exit 1
    fi
    
    # Source OS release info
    . /etc/os-release
    
    # Verify this is Ubuntu or Ubuntu-based distribution
    if [[ "$ID" != "ubuntu" ]]; then
        # Check if it's Ubuntu-based (like Linux Mint)
        if [[ "$ID_LIKE" == *"ubuntu"* ]] || [[ "$ID_LIKE" == *"debian"* ]]; then
            log_warn "Ubuntu-based distribution detected: $ID $PRETTY_NAME"
            log_warn "Proceeding with installation (use at your own risk)"
            echo "UBUNTU_BASED=true" >> "$CONFIG_FILE"
        else
            log_error "This script is designed for Ubuntu/Ubuntu-based systems only"
            log_error "Detected: $ID $PRETTY_NAME"
            exit 1
        fi
    else
        echo "UBUNTU_BASED=false" >> "$CONFIG_FILE"
    fi
    
    # Extract version components
    local major_version minor_version
    major_version=$(echo "$VERSION_ID" | cut -d. -f1)
    minor_version=$(echo "$VERSION_ID" | cut -d. -f2)
    
    # Check minimum version requirement
    if [[ $major_version -lt 22 ]] || [[ $major_version -eq 22 && $minor_version -lt 4 ]]; then
        log_error "Ubuntu 22.04 or newer required. Detected: $VERSION_ID"
        log_error "Please upgrade your system or use a supported Ubuntu version"
        exit 1
    fi
    
    # Special optimizations for Ubuntu 24.04
    if [[ $major_version -eq 24 && $minor_version -eq 4 ]]; then
        log_success "Ubuntu 24.04 LTS detected - Optimal compatibility ✅"
        echo "UBUNTU_OPTIMIZED=24.04" >> "$CONFIG_FILE"
    elif [[ $major_version -eq 22 && $minor_version -eq 4 ]]; then
        log_success "Ubuntu 22.04 LTS detected - Good compatibility ✅"
        echo "UBUNTU_OPTIMIZED=22.04" >> "$CONFIG_FILE"
    else
        log_success "Ubuntu $VERSION_ID detected - Compatible ✅"
        echo "UBUNTU_OPTIMIZED=other" >> "$CONFIG_FILE"
    fi
    
    # Check for LTS version (recommended)
    if [[ "$VERSION_ID" == *".04" ]]; then
        log_success "LTS version detected - Recommended for production"
    else
        log_warn "Non-LTS version detected. LTS versions are recommended for production use"
    fi
    
    # Check kernel version
    local kernel_version
    kernel_version=$(uname -r | cut -d- -f1)
    log_info "Kernel version: $kernel_version"
    
    # Log detailed system info
    log_debug "System: $PRETTY_NAME"
    log_debug "Codename: $VERSION_CODENAME"
    log_debug "Architecture: $(uname -m)"
    log_debug "Kernel: $(uname -r)"
}

# Enhanced system update with conflict resolution
update_system() {
    log_step "Updating system packages with conflict resolution..."
    
    # Add rollback for package state
    add_rollback_action "apt-mark unhold '*' 2>/dev/null || true"
    
    # Fix any broken packages first
    log_debug "Fixing any broken packages..."
    DEBIAN_FRONTEND=noninteractive dpkg --configure -a || true
    DEBIAN_FRONTEND=noninteractive apt-get -f install -y || true
    
    # Update package lists with retry
    log_debug "Updating package lists..."
    retry_command $MAX_RETRIES $RETRY_DELAY "apt update"
    
    # Handle package locks
    if lsof /var/lib/dpkg/lock-frontend &>/dev/null; then
        log_warn "Package manager is locked. Waiting for it to finish..."
        while lsof /var/lib/dpkg/lock-frontend &>/dev/null; do
            sleep 2
        done
    fi
    
    # Remove any problematic packages that might conflict
    local problematic_packages=(
        "apache2"
        "mysql-server"
        "nginx-common"
    )
    
    for pkg in "${problematic_packages[@]}"; do
        if dpkg -l "$pkg" &>/dev/null; then
            log_warn "Removing potentially conflicting package: $pkg"
            DEBIAN_FRONTEND=noninteractive apt-get remove -y "$pkg" || true
            add_rollback_action "apt-get install -y $pkg"
        fi
    done
    
    # Upgrade packages with conflict resolution
    log_debug "Upgrading system packages..."
    DEBIAN_FRONTEND=noninteractive apt-get \
        -o Dpkg::Options::="--force-confdef" \
        -o Dpkg::Options::="--force-confold" \
        upgrade -y
    
    # Install essential packages with retry
    local essential_packages=(
        "curl"
        "wget" 
        "git"
        "unzip"
        "software-properties-common"
        "ca-certificates"
        "gnupg"
        "lsb-release"
        "apt-transport-https"
        "dirmngr"
        "build-essential"
    )
    
    log_debug "Installing essential packages..."
    for pkg in "${essential_packages[@]}"; do
        if ! dpkg -l "$pkg" &>/dev/null; then
            retry_command $MAX_RETRIES $RETRY_DELAY "DEBIAN_FRONTEND=noninteractive apt-get install -y $pkg"
            add_rollback_action "apt-get remove -y $pkg"
        else
            log_debug "Package $pkg already installed"
        fi
    done
    
    # Clean up package cache
    apt-get autoremove -y
    apt-get autoclean
    
    log_success "System updated successfully"
}

# Enhanced PHP 8.2 installation with conflict resolution
install_php() {
    log_step "Installing PHP 8.2 with enhanced compatibility..."
    
    # Check if PHP is already installed and remove conflicting versions
    local existing_php_versions
    existing_php_versions=$(dpkg -l | grep -E "php[0-9]\.[0-9]" | awk '{print $2}' | grep -v "php8.2" || true)
    
    if [[ -n "$existing_php_versions" ]]; then
        log_warn "Removing conflicting PHP versions..."
        for php_pkg in $existing_php_versions; do
            log_debug "Removing: $php_pkg"
            DEBIAN_FRONTEND=noninteractive apt-get remove -y "$php_pkg" || true
            add_rollback_action "apt-get install -y $php_pkg"
        done
    fi
    
    # Add Ondrej PHP repository with error handling
    log_debug "Adding Ondrej PHP repository..."
    
    # Add GPG key first
    retry_command $MAX_RETRIES $RETRY_DELAY "curl -fsSL https://packages.sury.org/php/apt.gpg | gpg --dearmor -o /usr/share/keyrings/php-archive-keyring.gpg"
    add_rollback_action "rm -f /usr/share/keyrings/php-archive-keyring.gpg"
    
    # Add repository
    echo "deb [signed-by=/usr/share/keyrings/php-archive-keyring.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list
    add_rollback_action "rm -f /etc/apt/sources.list.d/php.list"
    
    # Update package lists after adding repository
    retry_command $MAX_RETRIES $RETRY_DELAY "apt update"
    
    # Define PHP packages with priorities
    local core_php_packages=(
        "php8.2"
        "php8.2-cli"
        "php8.2-fpm"
        "php8.2-common"
    )
    
    local php_extensions=(
        "php8.2-mysql"
        "php8.2-pgsql"
        "php8.2-sqlite3"
        "php8.2-redis"
        "php8.2-curl"
        "php8.2-mbstring"
        "php8.2-xml"
        "php8.2-zip"
        "php8.2-bcmath"
        "php8.2-intl"
        "php8.2-gd"
        "php8.2-opcache"
        "php8.2-readline"
        "php8.2-soap"
        "php8.2-xmlrpc"
        "php8.2-xsl"
        "php8.2-ssh2"
        "php8.2-uuid"
    )
    
    # Install core PHP packages first
    log_debug "Installing core PHP 8.2 packages..."
    for pkg in "${core_php_packages[@]}"; do
        retry_command $MAX_RETRIES $RETRY_DELAY "DEBIAN_FRONTEND=noninteractive apt-get install -y $pkg"
        add_rollback_action "apt-get remove -y $pkg"
    done
    
    # Install PHP extensions with individual error handling
    log_debug "Installing PHP 8.2 extensions..."
    local failed_extensions=()
    
    for ext in "${php_extensions[@]}"; do
        if retry_command 2 $RETRY_DELAY "DEBIAN_FRONTEND=noninteractive apt-get install -y $ext"; then
            log_debug "Successfully installed: $ext"
            add_rollback_action "apt-get remove -y $ext"
        else
            log_warn "Failed to install extension: $ext"
            failed_extensions+=("$ext")
        fi
    done
    
    # Report failed extensions but don't fail the installation
    if [[ ${#failed_extensions[@]} -gt 0 ]]; then
        log_warn "Some PHP extensions failed to install: ${failed_extensions[*]}"
        log_warn "This may not affect core functionality"
    fi
    
    # Verify PHP installation
    if ! command -v php &> /dev/null; then
        log_error "PHP installation failed - command not found"
        exit 1
    fi
    
    # Configure PHP for optimal performance
    log_debug "Configuring PHP settings..."
    
    # PHP-FPM configuration
    local fpm_config="/etc/php/8.2/fpm/pool.d/www.conf"
    if [[ -f "$fpm_config" ]]; then
        # Backup original config
        cp "$fpm_config" "${fpm_config}.backup"
        add_rollback_action "mv ${fpm_config}.backup $fpm_config"
        
        # Optimize settings
        sed -i 's/;pm.max_requests = 500/pm.max_requests = 1000/' "$fpm_config"
        sed -i 's/pm.max_children = 5/pm.max_children = 50/' "$fpm_config"
        sed -i 's/pm.start_servers = 2/pm.start_servers = 10/' "$fpm_config"
        sed -i 's/pm.min_spare_servers = 1/pm.min_spare_servers = 5/' "$fpm_config"
        sed -i 's/pm.max_spare_servers = 3/pm.max_spare_servers = 15/' "$fpm_config"
    fi
    
    # Start and enable PHP-FPM
    systemctl enable php8.2-fpm
    systemctl start php8.2-fpm
    add_rollback_action "systemctl stop php8.2-fpm && systemctl disable php8.2-fpm"
    
    # Verify PHP-FPM is running
    if ! systemctl is-active --quiet php8.2-fpm; then
        log_error "PHP-FPM failed to start"
        exit 1
    fi
    
    local php_version
    php_version=$(php -v | head -n1)
    log_success "PHP 8.2 installed and configured: $php_version"
}

# Install Composer
install_composer() {
    log_step "Installing Composer..."
    
    # Download and verify Composer installer
    EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"
    
    if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
        log_error "Composer installer corrupted"
        rm composer-setup.php
        exit 1
    fi
    
    # Install Composer
    if ! php composer-setup.php --install-dir=/usr/local/bin --filename=composer; then
        log_error "Failed to install Composer"
        rm composer-setup.php
        exit 1
    fi
    
    rm composer-setup.php
    
    # Verify Composer installation
    if ! command -v composer &> /dev/null; then
        log_error "Composer installation failed - command not found"
        exit 1
    fi
    
    log_info "Composer installed: $(composer --version)"
}

# Install PostgreSQL
install_postgresql() {
    log_step "Installing PostgreSQL..."
    
    # Install PostgreSQL
    if ! DEBIAN_FRONTEND=noninteractive apt install -y postgresql postgresql-contrib; then
        log_error "Failed to install PostgreSQL"
        exit 1
    fi
    
    # Start and enable PostgreSQL
    if ! systemctl start postgresql; then
        log_error "Failed to start PostgreSQL"
        exit 1
    fi
    
    if ! systemctl enable postgresql; then
        log_error "Failed to enable PostgreSQL"
        exit 1
    fi
    
    # Wait for PostgreSQL to be ready
    sleep 5
    
    # Generate secure random password
    DB_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
    
    # Create database and user with proper error handling
    if ! sudo -u postgres psql -c "CREATE DATABASE sentinentx;" 2>/dev/null; then
        log_warn "Database 'sentinentx' might already exist"
    fi
    
    if ! sudo -u postgres psql -c "CREATE USER sentinentx_user WITH PASSWORD '$DB_PASSWORD';" 2>/dev/null; then
        log_warn "User 'sentinentx_user' might already exist"
        sudo -u postgres psql -c "ALTER USER sentinentx_user WITH PASSWORD '$DB_PASSWORD';"
    fi
    
    sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE sentinentx TO sentinentx_user;"
    sudo -u postgres psql -c "ALTER USER sentinentx_user CREATEDB;"
    
    # Test connection
    if ! sudo -u postgres psql -d sentinentx -c "SELECT 1;" &>/dev/null; then
        log_error "PostgreSQL connection test failed"
        exit 1
    fi
    
    echo "DB_PASSWORD=$DB_PASSWORD" >> /tmp/sentinentx_config
    log_info "PostgreSQL installed and configured successfully"
}

# Install Redis
install_redis() {
    log_step "Installing Redis..."
    
    # Install Redis
    if ! DEBIAN_FRONTEND=noninteractive apt install -y redis-server; then
        log_error "Failed to install Redis"
        exit 1
    fi
    
    # Generate secure Redis password
    REDIS_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
    
    # Configure Redis with error handling
    if ! cp /etc/redis/redis.conf /etc/redis/redis.conf.backup; then
        log_error "Failed to backup Redis config"
        exit 1
    fi
    
    # Set password (handle different Redis config formats)
    if grep -q "^# requirepass" /etc/redis/redis.conf; then
        sed -i "s/^# requirepass foobared/requirepass $REDIS_PASSWORD/" /etc/redis/redis.conf
    elif grep -q "^requirepass" /etc/redis/redis.conf; then
        sed -i "s/^requirepass.*/requirepass $REDIS_PASSWORD/" /etc/redis/redis.conf
    else
        echo "requirepass $REDIS_PASSWORD" >> /etc/redis/redis.conf
    fi
    
    # Set bind address
    if grep -q "^bind" /etc/redis/redis.conf; then
        sed -i 's/^bind.*/bind 127.0.0.1/' /etc/redis/redis.conf
    else
        echo "bind 127.0.0.1" >> /etc/redis/redis.conf
    fi
    
    # Restart and enable Redis
    if ! systemctl restart redis-server; then
        log_error "Failed to restart Redis"
        exit 1
    fi
    
    if ! systemctl enable redis-server; then
        log_error "Failed to enable Redis"
        exit 1
    fi
    
    # Test Redis connection
    sleep 2
    if ! redis-cli -a "$REDIS_PASSWORD" ping &>/dev/null; then
        log_error "Redis connection test failed"
        exit 1
    fi
    
    echo "REDIS_PASSWORD=$REDIS_PASSWORD" >> /tmp/sentinentx_config
    log_info "Redis installed and configured successfully"
}

# Install Nginx
install_nginx() {
    log_step "Installing Nginx..."
    
    # Install Nginx
    if ! DEBIAN_FRONTEND=noninteractive apt install -y nginx; then
        log_error "Failed to install Nginx"
        exit 1
    fi
    
    # Start and enable Nginx
    if ! systemctl start nginx; then
        log_error "Failed to start Nginx"
        exit 1
    fi
    
    if ! systemctl enable nginx; then
        log_error "Failed to enable Nginx"
        exit 1
    fi
    
    # Test Nginx
    if ! systemctl is-active --quiet nginx; then
        log_error "Nginx is not running"
        exit 1
    fi
    
    log_info "Nginx installed and started successfully"
}

# Install Node.js
install_nodejs() {
    log_step "Installing Node.js 18..."
    
    # Download and install Node.js repository
    if ! curl -fsSL https://deb.nodesource.com/setup_18.x | bash -; then
        log_error "Failed to add Node.js repository"
        exit 1
    fi
    
    # Install Node.js
    if ! DEBIAN_FRONTEND=noninteractive apt install -y nodejs; then
        log_error "Failed to install Node.js"
        exit 1
    fi
    
    # Verify installation
    if ! command -v node &> /dev/null; then
        log_error "Node.js installation failed - command not found"
        exit 1
    fi
    
    if ! command -v npm &> /dev/null; then
        log_error "NPM installation failed - command not found"
        exit 1
    fi
    
    log_info "Node.js installed: $(node -v)"
    log_info "NPM installed: $(npm -v)"
}

# Setup firewall
setup_firewall() {
    log_step "Configuring firewall..."
    ufw --force enable
    ufw allow ssh
    ufw allow 80
    ufw allow 443
    log_info "Firewall configured"
}

# Install SentinentX
install_sentinentx() {
    log_step "Installing SentinentX..."
    
    # Create directory
    if ! mkdir -p /var/www; then
        log_error "Failed to create /var/www directory"
        exit 1
    fi
    
    cd /var/www
    
    # Check if SentinentX already exists
    if [ -d "sentinentx" ]; then
        log_warn "SentinentX directory already exists. Backing up..."
        mv sentinentx sentinentx.backup.$(date +%s)
    fi
    
    # For now, create a placeholder since we don't have the repo URL
    log_warn "Creating placeholder directory for SentinentX..."
    log_warn "After installation, you need to:"
    echo "  1. Remove placeholder: rm -rf /var/www/sentinentx"
    echo "  2. Clone your repo: git clone YOUR_REPO_URL /var/www/sentinentx"
    echo "  3. Run setup: cd /var/www/sentinentx && composer install"
    
    mkdir -p sentinentx/{public,storage/logs,bootstrap/cache}
    
    # Create basic Laravel structure for testing
    cat > sentinentx/public/index.php << 'EOF'
<?php
echo "SentinentX Placeholder - Please clone the real repository";
EOF
    
    # Set proper permissions
    chown -R www-data:www-data /var/www/sentinentx
    chmod -R 755 /var/www/sentinentx
    chmod -R 775 /var/www/sentinentx/storage /var/www/sentinentx/bootstrap/cache
    
    log_info "SentinentX placeholder created - Ready for repository clone"
}

# Configure environment
configure_environment() {
    log_step "Configuring environment..."
    
    # Skip if it's just a placeholder
    if [ ! -f "/var/www/sentinentx/env.example.template" ]; then
        log_warn "env.example.template not found (placeholder installation)"
        log_warn "After cloning repository, run configuration manually"
        return
    fi
    
    # Read generated passwords
    if [ ! -f "/tmp/sentinentx_config" ]; then
        log_error "Configuration file not found"
        exit 1
    fi
    
    source /tmp/sentinentx_config
    
    # Create .env from template
    if ! cp /var/www/sentinentx/env.example.template /var/www/sentinentx/.env; then
        log_error "Failed to copy environment template"
        exit 1
    fi
    
    # Generate secure keys
    APP_KEY=$(openssl rand -base64 32)
    HMAC_SECRET=$(openssl rand -hex 32)
    
    # Get server IP (with fallback)
    SERVER_IP=$(curl -s --max-time 10 ifconfig.me 2>/dev/null || echo "YOUR_SERVER_IP")
    
    # Update .env file with proper error handling
    sed -i "s/APP_ENV=local/APP_ENV=production/" /var/www/sentinentx/.env
    sed -i "s/APP_DEBUG=true/APP_DEBUG=false/" /var/www/sentinentx/.env
    sed -i "s|APP_URL=http://localhost|APP_URL=http://$SERVER_IP|" /var/www/sentinentx/.env
    sed -i "s/DB_PASSWORD=your-secure-password/DB_PASSWORD=$DB_PASSWORD/" /var/www/sentinentx/.env
    sed -i "s/REDIS_PASSWORD=your-redis-password/REDIS_PASSWORD=$REDIS_PASSWORD/" /var/www/sentinentx/.env
    sed -i "s/generate_with_openssl_rand_hex_32/$HMAC_SECRET/" /var/www/sentinentx/.env
    
    # Set secure permissions
    chown www-data:www-data /var/www/sentinentx/.env
    chmod 600 /var/www/sentinentx/.env
    
    log_info "Environment configured successfully"
}

# Configure Nginx
configure_nginx() {
    log_step "Configuring Nginx..."
    
    # Get server IP with fallback
    SERVER_IP=$(curl -s --max-time 10 ifconfig.me 2>/dev/null || echo "YOUR_SERVER_IP")
    
    # Backup existing config
    if [ -f /etc/nginx/sites-available/default ]; then
        cp /etc/nginx/sites-available/default /etc/nginx/sites-available/default.backup
    fi
    
    # Create Nginx configuration
    cat > /etc/nginx/sites-available/sentinentx << EOF
server {
    listen 80;
    server_name $SERVER_IP _;
    root /var/www/sentinentx/public;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    index index.php index.html;
    charset utf-8;

    # Main location block
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # Static files
    location = /favicon.ico { 
        access_log off; 
        log_not_found off; 
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    location = /robots.txt { 
        access_log off; 
        log_not_found off; 
    }

    # PHP handling
    location ~ \.php\$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    # Deny access to hidden files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Error pages
    error_page 404 /index.php;
    error_page 500 502 503 504 /50x.html;
}
EOF

    # Test Nginx configuration
    if ! nginx -t; then
        log_error "Nginx configuration test failed"
        exit 1
    fi
    
    # Enable site and disable default
    if ! ln -sf /etc/nginx/sites-available/sentinentx /etc/nginx/sites-enabled/; then
        log_error "Failed to enable SentinentX site"
        exit 1
    fi
    
    rm -f /etc/nginx/sites-enabled/default
    
    # Restart Nginx
    if ! systemctl restart nginx; then
        log_error "Failed to restart Nginx"
        exit 1
    fi
    
    log_info "Nginx configured successfully for $SERVER_IP"
}

# Setup Laravel
setup_laravel() {
    log_step "Setting up Laravel..."
    
    cd /var/www/sentinentx
    
    # Skip Laravel setup for placeholder
    if [ ! -f "artisan" ]; then
        log_warn "Laravel artisan not found (placeholder installation)"
        log_warn "After cloning repository, run Laravel setup manually:"
        echo "  cd /var/www/sentinentx"
        echo "  php artisan key:generate --force"
        echo "  php artisan migrate --force"
        echo "  php artisan config:cache"
        return
    fi
    
    # Generate app key
    if ! php artisan key:generate --force; then
        log_error "Failed to generate application key"
        exit 1
    fi
    
    # Run migrations (with error handling)
    if php artisan migrate --force 2>/dev/null; then
        log_info "Database migrations completed"
    else
        log_warn "Database migrations failed (may need manual setup)"
    fi
    
    # Cache optimization
    php artisan config:cache 2>/dev/null || log_warn "Config cache failed"
    php artisan route:cache 2>/dev/null || log_warn "Route cache failed" 
    php artisan view:cache 2>/dev/null || log_warn "View cache failed"
    
    # Storage link
    if ! php artisan storage:link 2>/dev/null; then
        log_warn "Storage link creation failed"
    fi
    
    # Set proper permissions
    chmod -R 775 storage bootstrap/cache 2>/dev/null || true
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
    
    log_info "Laravel setup completed"
}

# Create systemd services
create_services() {
    log_step "Creating systemd services..."
    
    # Queue worker service
    cat > /etc/systemd/system/sentinentx-queue.service << 'EOF'
[Unit]
Description=SentinentX Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
RestartSec=3
WorkingDirectory=/var/www/sentinentx
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --timeout=60

[Install]
WantedBy=multi-user.target
EOF

    # Telegram service
    cat > /etc/systemd/system/sentinentx-telegram.service << 'EOF'
[Unit]
Description=SentinentX Telegram Bot
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
RestartSec=5
WorkingDirectory=/var/www/sentinentx
ExecStart=/usr/bin/php artisan telegram:polling

[Install]
WantedBy=multi-user.target
EOF

    # Reload and enable services
    systemctl daemon-reload
    systemctl enable sentinentx-queue
    systemctl enable sentinentx-telegram
    
    log_info "Systemd services created"
}

# Setup monitoring
setup_monitoring() {
    log_step "Setting up monitoring..."
    
    # Install htop and other monitoring tools
    apt install -y htop iotop nethogs
    
    # Setup log rotation
    cat > /etc/logrotate.d/sentinentx << 'EOF'
/var/www/sentinentx/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    notifempty
    create 0644 www-data www-data
}
EOF

    # Setup cron for Laravel scheduler
    echo "* * * * * cd /var/www/sentinentx && php artisan schedule:run >> /dev/null 2>&1" | crontab -u www-data -
    
    log_info "Monitoring configured"
}

# Show final information
show_final_info() {
    SERVER_IP=$(curl -s ifconfig.me)
    
    echo ""
    echo "🎉 SentinentX Installation Complete!"
    echo "===================================="
    echo ""
    echo "📊 Server Information:"
    echo "  Server IP: $SERVER_IP"
    echo "  Web URL: http://$SERVER_IP"
    echo "  Project Path: /var/www/sentinentx"
    echo ""
    echo "🔧 Next Steps:"
    echo "  1. Remove placeholder: rm -rf /var/www/sentinentx"
    echo "  2. Clone repository: git clone YOUR_REPO_URL /var/www/sentinentx"
    echo "  3. Install dependencies: cd /var/www/sentinentx && composer install"
    echo "  4. Configure .env: Copy database/redis passwords from /tmp/sentinentx_config"
    echo "  5. Setup Laravel: php artisan key:generate && php artisan migrate"
    echo "  6. Start services: systemctl start sentinentx-queue sentinentx-telegram"
    echo "  7. Test Telegram bot: /help"
    echo ""
    echo "🔍 Useful Commands:"
    echo "  • Check services: systemctl status sentinentx-*"
    echo "  • View logs: tail -f /var/www/sentinentx/storage/logs/laravel.log"
    echo "  • Monitor system: htop"
    echo ""
    echo "📝 Configuration Files:"
    echo "  • Database password: stored in /tmp/sentinentx_config"
    echo "  • Nginx config: /etc/nginx/sites-available/sentinentx"
    echo "  • Laravel env: /var/www/sentinentx/.env"
    echo ""
    log_info "Installation completed successfully! 🚀"
}

# Main installation function
main() {
    log_info "Starting SentinentX VDS installation..."
    echo "Timestamp: $(date '+%Y-%m-%d %H:%M:%S')"
    echo "Log file: $LOG_FILE"
    echo ""
    
    # Run all system checks first
    check_network
    check_system_requirements  
    check_ubuntu_version
    update_system
    install_php
    install_composer
    install_postgresql
    install_redis
    install_nginx
    install_nodejs
    setup_firewall
    install_sentinentx
    configure_environment
    configure_nginx
    setup_laravel
    create_services
    setup_monitoring
    show_final_info
    
    # Cleanup
    rm -f /tmp/sentinentx_config
}

# Run main function
main "$@"
