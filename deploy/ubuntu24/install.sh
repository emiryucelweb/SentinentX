#!/bin/bash

# SentinentX Ubuntu 24.04 LTS Installation Script
# Comprehensive deployment automation with security hardening

set -euo pipefail
IFS=$'\n\t'

# Script metadata
readonly SCRIPT_VERSION="1.0.0"
readonly UBUNTU_VERSION="24.04"
readonly PHP_VERSION="8.3"
readonly NODE_VERSION="20"
readonly POSTGRES_VERSION="16"
readonly REDIS_VERSION="7"

# Configuration
readonly INSTALL_DIR="/var/www/sentinentx"
readonly LOG_FILE="/var/log/sentinentx-install.log"
readonly SYSTEM_USER="www-data"
readonly SYSTEM_GROUP="www-data"

# Colors for output
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly BLUE='\033[0;34m'
readonly CYAN='\033[0;36m'
readonly BOLD='\033[1m'
readonly NC='\033[0m'

# Error handling
handle_error() {
    local exit_code=$1
    local line_number=$2
    echo -e "${RED}ERROR: Installation failed at line $line_number with exit code $exit_code${NC}"
    echo "Check installation log: $LOG_FILE"
    exit $exit_code
}

trap 'handle_error $? $LINENO' ERR

# Logging functions
log() {
    echo "$(date -Iseconds) $*" | tee -a "$LOG_FILE"
}

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

# Check if running as root
check_root() {
    if [[ $EUID -ne 0 ]]; then
        log_error "This script must be run as root"
        exit 1
    fi
}

# Verify Ubuntu version
check_ubuntu_version() {
    log_step "Checking Ubuntu version..."
    
    if [[ ! -f /etc/os-release ]]; then
        log_error "Cannot determine OS version"
        exit 1
    fi
    
    source /etc/os-release
    
    if [[ "$ID" != "ubuntu" ]]; then
        log_error "This script is designed for Ubuntu only"
        exit 1
    fi
    
    if [[ "$VERSION_ID" != "$UBUNTU_VERSION" ]]; then
        log_warn "This script is optimized for Ubuntu $UBUNTU_VERSION, but detected $VERSION_ID"
        read -p "Continue anyway? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
    
    log_success "Ubuntu version check passed"
}

# Update system packages
update_system() {
    log_step "Updating system packages..."
    
    export DEBIAN_FRONTEND=noninteractive
    
    apt-get update
    apt-get upgrade -y
    apt-get install -y \
        software-properties-common \
        apt-transport-https \
        ca-certificates \
        curl \
        wget \
        gnupg \
        lsb-release \
        unzip \
        git \
        htop \
        tree \
        jq \
        bc \
        sqlite3 \
        zip \
        unzip \
        supervisor \
        ufw \
        fail2ban
    
    log_success "System packages updated"
}

# Install PHP 8.3
install_php() {
    log_step "Installing PHP $PHP_VERSION..."
    
    # Add Ondrej's PHP repository for latest PHP versions
    add-apt-repository -y ppa:ondrej/php
    apt-get update
    
    # Install PHP and required extensions
    apt-get install -y \
        php${PHP_VERSION} \
        php${PHP_VERSION}-fpm \
        php${PHP_VERSION}-cli \
        php${PHP_VERSION}-common \
        php${PHP_VERSION}-mysql \
        php${PHP_VERSION}-pgsql \
        php${PHP_VERSION}-redis \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-json \
        php${PHP_VERSION}-zip \
        php${PHP_VERSION}-gd \
        php${PHP_VERSION}-intl \
        php${PHP_VERSION}-bcmath \
        php${PHP_VERSION}-soap \
        php${PHP_VERSION}-sqlite3 \
        php${PHP_VERSION}-opcache \
        php${PHP_VERSION}-readline \
        php${PHP_VERSION}-tokenizer \
        php${PHP_VERSION}-fileinfo \
        php${PHP_VERSION}-ctype \
        php${PHP_VERSION}-dom \
        php${PHP_VERSION}-simplexml \
        php${PHP_VERSION}-xmlwriter \
        php${PHP_VERSION}-xmlreader
    
    # Configure PHP for production
    configure_php_production
    
    log_success "PHP $PHP_VERSION installed and configured"
}

# Configure PHP for production
configure_php_production() {
    log_step "Configuring PHP for production..."
    
    local php_ini="/etc/php/${PHP_VERSION}/fpm/php.ini"
    local cli_ini="/etc/php/${PHP_VERSION}/cli/php.ini"
    
    # Backup original files
    cp "$php_ini" "${php_ini}.backup"
    cp "$cli_ini" "${cli_ini}.backup"
    
    # Production optimizations
    sed -i 's/memory_limit = .*/memory_limit = 512M/' "$php_ini"
    sed -i 's/max_execution_time = .*/max_execution_time = 300/' "$php_ini"
    sed -i 's/max_input_time = .*/max_input_time = 300/' "$php_ini"
    sed -i 's/post_max_size = .*/post_max_size = 50M/' "$php_ini"
    sed -i 's/upload_max_filesize = .*/upload_max_filesize = 50M/' "$php_ini"
    sed -i 's/expose_php = .*/expose_php = Off/' "$php_ini"
    sed -i 's/display_errors = .*/display_errors = Off/' "$php_ini"
    sed -i 's/log_errors = .*/log_errors = On/' "$php_ini"
    
    # OPcache optimization
    cat >> "$php_ini" << 'EOF'

; OPcache optimization for SentinentX
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=64
opcache.max_accelerated_files=32531
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1
EOF
    
    # CLI optimizations
    sed -i 's/memory_limit = .*/memory_limit = 1G/' "$cli_ini"
    
    # Restart PHP-FPM
    systemctl restart php${PHP_VERSION}-fpm
    systemctl enable php${PHP_VERSION}-fpm
    
    log_success "PHP configured for production"
}

# Install Composer
install_composer() {
    log_step "Installing Composer..."
    
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    
    # Verify installation
    if ! composer --version &>/dev/null; then
        log_error "Composer installation failed"
        exit 1
    fi
    
    log_success "Composer installed"
}

# Install Node.js
install_nodejs() {
    log_step "Installing Node.js $NODE_VERSION..."
    
    curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash -
    apt-get install -y nodejs
    
    # Install global packages
    npm install -g npm@latest
    npm install -g pm2
    
    log_success "Node.js $NODE_VERSION installed"
}

# Install PostgreSQL
install_postgresql() {
    log_step "Installing PostgreSQL $POSTGRES_VERSION..."
    
    # Add PostgreSQL official repository
    wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | apt-key add -
    echo "deb http://apt.postgresql.org/pub/repos/apt/ $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list
    
    apt-get update
    apt-get install -y postgresql-${POSTGRES_VERSION} postgresql-client-${POSTGRES_VERSION} postgresql-contrib-${POSTGRES_VERSION}
    
    # Configure PostgreSQL
    configure_postgresql
    
    log_success "PostgreSQL $POSTGRES_VERSION installed and configured"
}

# Configure PostgreSQL
configure_postgresql() {
    log_step "Configuring PostgreSQL..."
    
    # Start and enable PostgreSQL
    systemctl start postgresql
    systemctl enable postgresql
    
    # Create database and user for SentinentX
    sudo -u postgres createdb sentinentx 2>/dev/null || true
    sudo -u postgres psql -c "CREATE USER sentinentx WITH PASSWORD 'sentinentx_secure_password';" 2>/dev/null || true
    sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE sentinentx TO sentinentx;" 2>/dev/null || true
    sudo -u postgres psql -c "ALTER USER sentinentx CREATEDB;" 2>/dev/null || true
    
    # Performance tuning for PostgreSQL
    local pg_conf="/etc/postgresql/${POSTGRES_VERSION}/main/postgresql.conf"
    local pg_hba="/etc/postgresql/${POSTGRES_VERSION}/main/pg_hba.conf"
    
    # Backup original files
    cp "$pg_conf" "${pg_conf}.backup"
    cp "$pg_hba" "${pg_hba}.backup"
    
    # Performance optimizations
    cat >> "$pg_conf" << 'EOF'

# SentinentX optimizations
shared_buffers = 256MB
effective_cache_size = 1GB
maintenance_work_mem = 64MB
checkpoint_completion_target = 0.9
wal_buffers = 16MB
default_statistics_target = 100
random_page_cost = 1.1
effective_io_concurrency = 200
work_mem = 4MB
min_wal_size = 1GB
max_wal_size = 4GB
max_worker_processes = 8
max_parallel_workers_per_gather = 4
max_parallel_workers = 8
max_parallel_maintenance_workers = 4
EOF
    
    # Allow local connections
    echo "host sentinentx sentinentx 127.0.0.1/32 md5" >> "$pg_hba"
    
    # Restart PostgreSQL
    systemctl restart postgresql
    
    log_success "PostgreSQL configured"
}

# Install Redis
install_redis() {
    log_step "Installing Redis $REDIS_VERSION..."
    
    apt-get install -y redis-server
    
    # Configure Redis
    configure_redis
    
    log_success "Redis installed and configured"
}

# Configure Redis
configure_redis() {
    log_step "Configuring Redis..."
    
    local redis_conf="/etc/redis/redis.conf"
    
    # Backup original file
    cp "$redis_conf" "${redis_conf}.backup"
    
    # Security and performance optimizations
    sed -i 's/# maxmemory <bytes>/maxmemory 512mb/' "$redis_conf"
    sed -i 's/# maxmemory-policy noeviction/maxmemory-policy allkeys-lru/' "$redis_conf"
    sed -i 's/save 900 1/# save 900 1/' "$redis_conf"
    sed -i 's/save 300 10/# save 300 10/' "$redis_conf"
    sed -i 's/save 60 10000/# save 60 10000/' "$redis_conf"
    
    # Add custom configuration
    cat >> "$redis_conf" << 'EOF'

# SentinentX optimizations
tcp-keepalive 300
timeout 300
tcp-backlog 511
databases 16
stop-writes-on-bgsave-error no
rdbcompression yes
rdbchecksum yes
EOF
    
    # Start and enable Redis
    systemctl restart redis-server
    systemctl enable redis-server
    
    log_success "Redis configured"
}

# Install Nginx
install_nginx() {
    log_step "Installing Nginx..."
    
    apt-get install -y nginx
    
    # Configure Nginx for SentinentX
    configure_nginx
    
    log_success "Nginx installed and configured"
}

# Configure Nginx
configure_nginx() {
    log_step "Configuring Nginx..."
    
    # Remove default site
    rm -f /etc/nginx/sites-enabled/default
    
    # Create SentinentX site configuration
    cat > /etc/nginx/sites-available/sentinentx << 'EOF'
server {
    listen 80;
    server_name _;
    root /var/www/sentinentx/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';" always;

    # Hide server version
    server_tokens off;

    # File size limits
    client_max_body_size 50M;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;

    # Main location block
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM configuration
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Security
        fastcgi_hide_header X-Powered-By;
    }

    # Static files with caching
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    location ~ /\.env {
        deny all;
        access_log off;
        log_not_found off;
    }

    # API endpoint optimization
    location /api/ {
        try_files $uri $uri/ /index.php?$query_string;
        
        # Rate limiting
        limit_req zone=api burst=10 nodelay;
    }

    # WebSocket proxy (if needed)
    location /ws {
        proxy_pass http://127.0.0.1:6001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }
}
EOF

    # Enable site
    ln -sf /etc/nginx/sites-available/sentinentx /etc/nginx/sites-enabled/

    # Configure rate limiting
    cat > /etc/nginx/conf.d/rate-limiting.conf << 'EOF'
# Rate limiting zones
limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
limit_req_zone $binary_remote_addr zone=login:10m rate=1r/s;

# Connection limiting
limit_conn_zone $binary_remote_addr zone=addr:10m;
limit_conn addr 10;
EOF

    # Test and restart Nginx
    nginx -t
    systemctl restart nginx
    systemctl enable nginx
    
    log_success "Nginx configured"
}

# Setup systemd services
setup_systemd_services() {
    log_step "Setting up systemd services..."
    
    # Copy service files
    cp "${INSTALL_DIR}/deploy/ubuntu24/sentinentx.service" /etc/systemd/system/
    cp "${INSTALL_DIR}/deploy/ubuntu24/sentinentx-scheduler.service" /etc/systemd/system/
    cp "${INSTALL_DIR}/deploy/ubuntu24/sentinentx-queue.service" /etc/systemd/system/
    cp "${INSTALL_DIR}/deploy/ubuntu24/sentinentx-ws.service" /etc/systemd/system/
    
    # Make scripts executable
    chmod +x "${INSTALL_DIR}/deploy/ubuntu24/scripts/"*.sh
    
    # Create required directories
    mkdir -p /run/sentinentx /var/log/sentinentx
    chown $SYSTEM_USER:$SYSTEM_GROUP /run/sentinentx /var/log/sentinentx
    
    # Reload systemd
    systemctl daemon-reload
    
    # Enable services
    systemctl enable sentinentx.service
    systemctl enable sentinentx-scheduler.service
    systemctl enable sentinentx-queue.service
    systemctl enable sentinentx-ws.service
    
    log_success "Systemd services configured"
}

# Configure firewall
configure_firewall() {
    log_step "Configuring firewall..."
    
    # Reset UFW
    ufw --force reset
    
    # Default policies
    ufw default deny incoming
    ufw default allow outgoing
    
    # SSH access
    ufw allow ssh
    
    # HTTP/HTTPS
    ufw allow 80/tcp
    ufw allow 443/tcp
    
    # Application specific ports
    ufw allow 6001/tcp comment "SentinentX WebSocket"
    
    # Database (local only)
    ufw allow from 127.0.0.1 to any port 5432
    
    # Redis (local only)
    ufw allow from 127.0.0.1 to any port 6379
    
    # Enable UFW
    ufw --force enable
    
    log_success "Firewall configured"
}

# Setup application
setup_application() {
    log_step "Setting up SentinentX application..."
    
    # Clone or ensure application is in place
    if [[ ! -d "$INSTALL_DIR" ]]; then
        log_error "Application directory $INSTALL_DIR not found"
        log_error "Please deploy the application first"
        exit 1
    fi
    
    cd "$INSTALL_DIR"
    
    # Set proper ownership
    chown -R $SYSTEM_USER:$SYSTEM_GROUP "$INSTALL_DIR"
    
    # Set proper permissions
    find "$INSTALL_DIR" -type f -exec chmod 644 {} \;
    find "$INSTALL_DIR" -type d -exec chmod 755 {} \;
    chmod -R 775 storage bootstrap/cache
    chmod +x deploy/ubuntu24/scripts/*.sh
    
    # Install Composer dependencies
    sudo -u $SYSTEM_USER composer install --no-dev --optimize-autoloader
    
    # Install Node dependencies (if package.json exists)
    if [[ -f "package.json" ]]; then
        sudo -u $SYSTEM_USER npm ci --production
        sudo -u $SYSTEM_USER npm run build
    fi
    
    # Generate app key if not exists
    if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
        sudo -u $SYSTEM_USER php artisan key:generate
    fi
    
    # Cache configuration
    sudo -u $SYSTEM_USER php artisan config:cache
    sudo -u $SYSTEM_USER php artisan route:cache
    sudo -u $SYSTEM_USER php artisan view:cache
    
    # Run migrations
    sudo -u $SYSTEM_USER php artisan migrate --force
    
    log_success "Application setup completed"
}

# Final system optimization
optimize_system() {
    log_step "Optimizing system for production..."
    
    # Kernel parameters
    cat >> /etc/sysctl.conf << 'EOF'

# SentinentX optimizations
vm.swappiness = 10
vm.dirty_ratio = 15
vm.dirty_background_ratio = 5
net.core.somaxconn = 1024
net.ipv4.tcp_keepalive_time = 600
net.ipv4.tcp_keepalive_intvl = 60
net.ipv4.tcp_keepalive_probes = 3
fs.file-max = 65536
EOF

    # Apply sysctl changes
    sysctl -p
    
    # Set system limits
    cat >> /etc/security/limits.conf << 'EOF'

# SentinentX limits
www-data soft nofile 65536
www-data hard nofile 65536
www-data soft nproc 32768
www-data hard nproc 32768
EOF

    log_success "System optimization completed"
}

# Main installation function
main() {
    echo -e "${BOLD}${BLUE}🚀 SentinentX Ubuntu 24.04 LTS Installation Script v$SCRIPT_VERSION${NC}"
    echo "=================================================================="
    echo ""
    
    # Create log directory
    mkdir -p "$(dirname "$LOG_FILE")"
    
    log "Starting SentinentX installation on Ubuntu 24.04 LTS"
    
    # Run installation steps
    check_root
    check_ubuntu_version
    update_system
    install_php
    install_composer
    install_nodejs
    install_postgresql
    install_redis
    install_nginx
    setup_application
    setup_systemd_services
    configure_firewall
    optimize_system
    
    echo ""
    echo -e "${GREEN}${BOLD}✅ SentinentX Installation Completed Successfully!${NC}"
    echo "============================================================"
    echo ""
    echo -e "${BLUE}📊 Installation Summary:${NC}"
    echo "  • PHP: $PHP_VERSION"
    echo "  • Node.js: $NODE_VERSION"
    echo "  • PostgreSQL: $POSTGRES_VERSION"
    echo "  • Redis: $REDIS_VERSION"
    echo "  • Nginx: $(nginx -v 2>&1 | cut -d' ' -f3)"
    echo ""
    echo -e "${CYAN}🔧 Next Steps:${NC}"
    echo "  1. Configure your .env file: nano $INSTALL_DIR/.env"
    echo "  2. Start services: systemctl start sentinentx"
    echo "  3. Check status: systemctl status sentinentx"
    echo "  4. View logs: journalctl -f -u sentinentx"
    echo ""
    echo -e "${YELLOW}⚠️ Important:${NC}"
    echo "  • Change default database password"
    echo "  • Configure SSL certificates for production"
    echo "  • Review firewall rules"
    echo "  • Set up monitoring and backups"
    echo ""
    echo -e "${GREEN}🎉 Happy Trading with SentinentX! 🚀${NC}"
    
    log_success "SentinentX installation completed successfully"
}

# Run main function
main "$@"
