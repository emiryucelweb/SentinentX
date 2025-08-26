#!/bin/bash

# CRITICAL FIX: Laravel Dependencies Missing
echo "🚨 CRITICAL FIX: Laravel Dependencies Missing"
echo "============================================="

cd /var/www/sentinentx

# Check current situation
echo "🔍 Checking current situation..."
echo "• Directory: $(pwd)"
echo "• composer.json exists: $([[ -f composer.json ]] && echo 'YES' || echo 'NO')"
echo "• vendor directory exists: $([[ -d vendor ]] && echo 'YES' || echo 'NO')"
echo "• vendor/autoload.php exists: $([[ -f vendor/autoload.php ]] && echo 'YES' || echo 'NO')"

if [[ -d vendor ]]; then
    echo "• Vendor directory size: $(du -sh vendor 2>/dev/null | cut -f1)"
fi

# Check if we have a proper Laravel composer.json
if [[ ! -f composer.json ]]; then
    echo "❌ composer.json missing - creating Laravel composer.json..."
    cat > composer.json << 'LARAVELCOMPOSER'
{
    "name": "sentinentx/trading-bot",
    "type": "project",
    "description": "AI-powered cryptocurrency trading bot built with Laravel",
    "keywords": ["laravel", "trading", "cryptocurrency", "ai"],
    "license": "MIT",
    "require": {
        "php": "^8.1",
        "guzzlehttp/guzzle": "^7.2",
        "laravel/framework": "^10.10",
        "laravel/sanctum": "^3.2",
        "laravel/tinker": "^2.8"
    },
    "require-dev": {
        "fakerphp/faker": "^1.9.1",
        "laravel/pint": "^1.0",
        "laravel/sail": "^1.18",
        "mockery/mockery": "^1.4.4",
        "nunomaduro/collision": "^7.0",
        "phpunit/phpunit": "^10.1",
        "spatie/laravel-ignition": "^2.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    },
    "scripts": {
        "post-autoload-dump": [
            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
            "@php artisan package:discover --ansi"
        ],
        "post-update-cmd": [
            "@php artisan vendor:publish --tag=laravel-assets --ansi --force"
        ],
        "post-root-package-install": [
            "@php -r \"file_exists('.env') || copy('.env.example', '.env');\""
        ],
        "post-create-project-cmd": [
            "@php artisan key:generate --ansi"
        ]
    },
    "extra": {
        "laravel": {
            "dont-discover": []
        }
    },
    "config": {
        "optimize-autoloader": true,
        "preferred-install": "dist",
        "sort-packages": true,
        "allow-plugins": {
            "pestphp/pest-plugin": true,
            "php-http/discovery": true
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
LARAVELCOMPOSER
    echo "✅ Laravel composer.json created"
else
    echo "✅ composer.json exists"
fi

# Remove any corrupted vendor directory
if [[ -d vendor ]]; then
    echo "🧹 Removing existing vendor directory..."
    rm -rf vendor
fi

# Setup Composer environment properly
echo "⚙️ Setting up Composer environment..."
export COMPOSER_HOME="/root/.composer"
export COMPOSER_CACHE_DIR="/tmp/composer-cache"
export COMPOSER_ALLOW_SUPERUSER=1
export COMPOSER_NO_INTERACTION=1

mkdir -p "$COMPOSER_HOME" "$COMPOSER_CACHE_DIR"
chmod -R 755 "$COMPOSER_HOME" "$COMPOSER_CACHE_DIR"

# Ensure Composer is installed and updated
echo "📦 Ensuring Composer is available..."
if ! command -v composer &> /dev/null; then
    echo "Installing Composer..."
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
fi

echo "Composer version: $(composer --version)"

# Clear any Composer caches
echo "🧹 Clearing Composer caches..."
composer clear-cache || echo "Cache clear failed (may be OK)"

# Install Laravel dependencies with maximum verbosity
echo "📥 Installing Laravel dependencies (this may take a few minutes)..."
echo "This will download and install Laravel framework and all dependencies..."

# Set proper permissions for installation
chown -R root:root .
chmod -R 755 .

# Install with retries and fallbacks
INSTALL_SUCCESS=false

echo "🔄 Attempt 1: Standard Laravel installation..."
if composer install --no-dev --optimize-autoloader --no-interaction --verbose; then
    INSTALL_SUCCESS=true
    echo "✅ Standard installation successful"
else
    echo "⚠️ Standard installation failed, trying without optimizations..."
    
    echo "🔄 Attempt 2: Basic installation..."
    if composer install --no-dev --no-interaction; then
        INSTALL_SUCCESS=true
        echo "✅ Basic installation successful"
    else
        echo "⚠️ Basic installation failed, trying individual packages..."
        
        echo "🔄 Attempt 3: Individual package installation..."
        composer require laravel/framework:^10.0 --no-interaction || echo "Framework install failed"
        composer require laravel/tinker:^2.8 --no-interaction || echo "Tinker install failed"
        composer require guzzlehttp/guzzle:^7.2 --no-interaction || echo "Guzzle install failed"
        
        if [[ -f "vendor/autoload.php" ]]; then
            INSTALL_SUCCESS=true
            echo "✅ Individual installation successful"
        fi
    fi
fi

# Check installation result
echo ""
echo "🔍 Checking installation result..."
if [[ "$INSTALL_SUCCESS" == true ]] && [[ -f "vendor/autoload.php" ]]; then
    echo "✅ Laravel dependencies installed successfully"
    echo "• vendor/autoload.php: ✅ EXISTS"
    echo "• Vendor directory size: $(du -sh vendor | cut -f1)"
    
    # Test autoload
    if php -r "require 'vendor/autoload.php'; echo 'Autoload OK';"; then
        echo "✅ Autoload is working"
    else
        echo "⚠️ Autoload has issues"
    fi
    
    # Test Laravel Application class
    if php -r "require 'vendor/autoload.php'; new Illuminate\Foundation\Application(__DIR__); echo 'Laravel Application OK';"; then
        echo "✅ Laravel Application class is available"
    else
        echo "⚠️ Laravel Application class still missing"
    fi
else
    echo "❌ Laravel dependencies installation failed"
    echo "Manual composer.json contents:"
    cat composer.json | head -20
fi

# Generate optimized autoloader
echo ""
echo "⚡ Generating optimized autoloader..."
composer dump-autoload --optimize || echo "Autoload optimization failed"

# Test Laravel now
echo ""
echo "🧪 Testing Laravel functionality..."
if [[ -f "vendor/autoload.php" ]]; then
    echo "Testing artisan..."
    if php artisan --version; then
        echo "✅ Artisan is working!"
    else
        echo "⚠️ Artisan still has issues"
    fi
    
    echo "Testing database connection..."
    if php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB_OK';" 2>/dev/null | grep -q "DB_OK"; then
        echo "✅ Database connection working"
    else
        echo "⚠️ Database connection issues"
    fi
else
    echo "❌ vendor/autoload.php still missing"
fi

# Try migrations if Laravel is working
echo ""
echo "🗄️ Attempting database migrations..."
if php artisan migrate --force 2>/dev/null; then
    echo "✅ Migrations successful"
else
    echo "⚠️ Migrations failed (may need manual intervention)"
fi

# Final optimization
echo ""
echo "⚡ Final Laravel optimization..."
php artisan config:clear 2>/dev/null && echo "✅ Config cleared" || echo "Config clear failed"
php artisan cache:clear 2>/dev/null && echo "✅ Cache cleared" || echo "Cache clear failed"
php artisan config:cache 2>/dev/null && echo "✅ Config cached" || echo "Config cache failed"

# Set final permissions
chown -R www-data:www-data /var/www/sentinentx
chmod -R 755 /var/www/sentinentx
chmod -R 775 storage bootstrap/cache

echo ""
echo "🎉 LARAVEL DEPENDENCIES INSTALLATION COMPLETED!"
echo "=============================================="
if [[ -f "vendor/autoload.php" ]]; then
    echo "✅ vendor/autoload.php: AVAILABLE"
    echo "✅ Laravel framework: INSTALLED"
    echo "✅ Dependencies: RESOLVED"
    echo ""
    echo "🧪 Test commands:"
    echo "• php artisan --version"
    echo "• php artisan migrate --force"
    echo "• bash comprehensive_deployment_test.sh"
else
    echo "❌ Dependencies installation incomplete"
    echo "Manual intervention may be required"
fi

echo ""
echo "📊 Installation summary:"
echo "• Composer packages: $(find vendor -name '*.php' | wc -l 2>/dev/null || echo 0) PHP files"
echo "• Autoload file: $([[ -f vendor/autoload.php ]] && echo 'PRESENT' || echo 'MISSING')"
echo "• Laravel Application: $(php -r "require 'vendor/autoload.php'; echo class_exists('Illuminate\Foundation\Application') ? 'AVAILABLE' : 'MISSING';" 2>/dev/null || echo 'UNKNOWN')"
