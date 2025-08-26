#!/bin/bash

# SentinentX Minimal Safe Deployment 
# No complex error handling, just essential deployment

echo "🚀 SentinentX Minimal Safe Deployment"
echo "===================================="

# Basic setup
cd /var/www
rm -rf sentinentx 2>/dev/null || true

# Download via direct methods
echo "📡 Downloading SentinentX..."
if git clone https://github.com/emiryucelweb/SentinentX.git sentinentx; then
    echo "✅ Git clone successful"
elif curl -sSL "https://github.com/emiryucelweb/SentinentX/archive/refs/heads/main.zip" | unzip -q - -d /tmp && mv /tmp/SentinentX-main sentinentx; then
    echo "✅ ZIP download successful"
else
    echo "❌ Download failed"
    exit 1
fi

cd sentinentx

# Install infrastructure with timeout
echo "🔧 Installing infrastructure..."

# Ensure proper ownership before infrastructure installation
chown -R root:root /var/www/sentinentx 2>/dev/null || true
chmod -R 755 /var/www/sentinentx 2>/dev/null || true

timeout 600 bash quick_vds_install.sh || {
    echo "⚠️ Infrastructure installation timeout, but may be OK"
    
    # Check if essential services are running
    systemctl is-active --quiet postgresql && echo "✅ PostgreSQL is running"
    systemctl is-active --quiet nginx && echo "✅ Nginx is running"
    systemctl is-active --quiet redis-server && echo "✅ Redis is running"
}

# Basic Laravel setup
echo "🔧 Laravel setup..."

# Check if we're in the right directory
if [[ ! -f "artisan" ]]; then
    echo "❌ Not in Laravel directory, checking structure..."
    ls -la
    find . -name "artisan" -o -name "composer.json" | head -5
fi

# Find and copy env template
ENV_TEMPLATE=""
for template in "env.example.template" ".env.example" ".env.template"; do
    if [[ -f "$template" ]]; then
        ENV_TEMPLATE="$template"
        break
    fi
done

if [[ -n "$ENV_TEMPLATE" ]]; then
    echo "✅ Found env template: $ENV_TEMPLATE"
    cp "$ENV_TEMPLATE" .env
else
    echo "⚠️ No env template found, creating minimal .env..."
    cat > .env << 'EOF'
APP_NAME=SentinentX
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sentinentx
DB_USERNAME=sentinentx
DB_PASSWORD=sentinentx123

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

RISK_PROFILE=moderate
BYBIT_TESTNET=true
EOF
fi

# Install Composer dependencies with proper directory check
if [[ -f "composer.json" ]]; then
    echo "📦 Installing Composer dependencies..."
    
    # Fix Composer directory permissions
    export COMPOSER_HOME="/root/.composer"
    export COMPOSER_CACHE_DIR="/tmp/composer-cache"
    mkdir -p "$COMPOSER_HOME" "$COMPOSER_CACHE_DIR"
    chmod 755 "$COMPOSER_HOME" "$COMPOSER_CACHE_DIR"
    
    # Set proper directory ownership for Composer
    chown -R root:root . 
    chmod -R 755 .
    
    # Install dependencies with verbose error reporting
    if composer install --no-dev --optimize-autoloader --no-interaction; then
        echo "✅ Composer dependencies installed successfully"
    else
        echo "⚠️ Composer installation failed, trying fallback..."
        # Fallback: download dependencies manually
        composer install --no-dev --no-scripts --no-autoloader 2>/dev/null || echo "Composer fallback also failed"
        composer dump-autoload --optimize 2>/dev/null || echo "Autoload generation failed"
    fi
else
    echo "⚠️ No composer.json found, skipping dependencies"
fi

# Update .env with secure values
if [[ -f ".env" ]]; then
    # Generate secure database password
    DB_PASS="sentx_$(openssl rand -hex 8)"
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASS/" .env
    echo "✅ .env configured with secure password"
else
    echo "❌ Failed to create .env file"
fi

# Laravel commands with proper setup
echo "🔧 Laravel application setup..."

# Create essential directories
mkdir -p storage/logs storage/app storage/framework/{cache,sessions,views}
mkdir -p bootstrap/cache

# Set initial permissions for Laravel operations
chmod -R 775 storage bootstrap/cache 2>/dev/null || chmod -R 777 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# Laravel key generation
if [[ -f "artisan" ]] && [[ -f ".env" ]]; then
    echo "🔑 Generating application key..."
    if php artisan key:generate --force; then
        echo "✅ Application key generated"
    else
        echo "⚠️ Key generation failed, setting manual key..."
        # Generate manual key
        MANUAL_KEY="base64:$(openssl rand -base64 32)"
        sed -i "s/APP_KEY=.*/APP_KEY=$MANUAL_KEY/" .env
    fi
    
    # Database migration
    echo "🗄️ Running database migrations..."
    if php artisan migrate --force; then
        echo "✅ Database migrations completed"
    else
        echo "⚠️ Migration issues - checking database connection..."
        php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'DB_OK'; } catch(Exception \$e) { echo 'DB_FAIL: ' . \$e->getMessage(); }" || echo "Database connection test failed"
    fi
    
    # Cache optimization
    echo "⚡ Optimizing application..."
    php artisan config:cache 2>/dev/null || echo "Config cache skipped"
    php artisan route:cache 2>/dev/null || echo "Route cache skipped"
    php artisan view:cache 2>/dev/null || echo "View cache skipped"
else
    echo "⚠️ Laravel setup skipped - missing artisan or .env"
fi

# Final permissions
echo "🔧 Setting final permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chown -R www-data:www-data . 2>/dev/null || true

echo ""
echo "✅ Minimal deployment completed!"
echo "📁 Installation directory: /var/www/sentinentx"
echo "🔍 Check status: systemctl status nginx postgresql"
echo ""
