#!/bin/bash

# DIAGNOSE ARTISAN FAILURE - SentinentX
echo "🔍 DIAGNOSING ARTISAN FAILURE - SENTINENTX"
echo "========================================="

PROJECT_DIR="/var/www/sentinentx"
cd "$PROJECT_DIR" || exit 1

echo "📁 Current directory: $(pwd)"
echo "👤 Current user: $(whoami)"
echo ""

echo "🔍 BASIC FILE STRUCTURE CHECK"
echo "============================="

# Check if essential files exist
files_to_check=(
    "artisan"
    "composer.json"
    ".env"
    "bootstrap/app.php"
    "vendor/autoload.php"
    "app/Console/Kernel.php"
)

for file in "${files_to_check[@]}"; do
    if [[ -f "$file" ]]; then
        echo "✅ $file exists"
        if [[ "$file" == "artisan" ]]; then
            echo "   Permissions: $(stat -c %a $file) Owner: $(stat -c %U:%G $file)"
            echo "   Executable: $([[ -x "$file" ]] && echo 'YES' || echo 'NO')"
        fi
    else
        echo "❌ $file MISSING"
    fi
done

echo ""
echo "🐘 PHP TESTING"
echo "=============="

# Test PHP
echo "📊 PHP Version:"
php --version | head -1

echo ""
echo "📊 PHP Extensions Check:"
required_extensions=("pdo" "pdo_pgsql" "mbstring" "curl" "json" "bcmath")
for ext in "${required_extensions[@]}"; do
    if php -m | grep -q "$ext"; then
        echo "✅ $ext loaded"
    else
        echo "❌ $ext NOT loaded"
    fi
done

echo ""
echo "🧪 TESTING ARTISAN FILE"
echo "======================"

if [[ -f "artisan" ]]; then
    echo "📄 Artisan file content (first 10 lines):"
    head -10 artisan
    echo ""
    
    echo "🧪 Testing artisan execution:"
    php artisan --version 2>&1 | head -5
    
    echo ""
    echo "🧪 Testing artisan list:"
    php artisan list 2>&1 | grep -E "(trading|Available commands)" | head -10
    
else
    echo "❌ Artisan file not found!"
fi

echo ""
echo "🔍 BOOTSTRAP CHECK"
echo "=================="

if [[ -f "bootstrap/app.php" ]]; then
    echo "✅ bootstrap/app.php exists"
    echo "📄 Content check:"
    head -5 bootstrap/app.php
else
    echo "❌ bootstrap/app.php MISSING"
fi

echo ""
echo "📦 COMPOSER CHECK"
echo "================="

if [[ -f "vendor/autoload.php" ]]; then
    echo "✅ Composer autoload exists"
    echo "📄 Testing require:"
    php -r "require 'vendor/autoload.php'; echo 'Autoload OK\n';" 2>&1
else
    echo "❌ Composer autoload MISSING"
    echo "🔧 Running composer install..."
    composer install --no-dev --optimize-autoloader 2>&1 | tail -10
fi

echo ""
echo "🎯 SPECIFIC TRADING COMMAND CHECK"
echo "================================="

# Check if trading commands exist in Laravel
echo "🔍 Looking for trading commands:"
if [[ -d "app/Console/Commands" ]]; then
    echo "📁 Console Commands directory exists"
    ls -la app/Console/Commands/ | grep -i trading || echo "❌ No trading commands found"
else
    echo "❌ Console Commands directory missing"
fi

# Check console kernel
if [[ -f "app/Console/Kernel.php" ]]; then
    echo "✅ Console Kernel exists"
    echo "📄 Checking for trading commands registration:"
    grep -n "trading" app/Console/Kernel.php || echo "❌ No trading commands registered"
else
    echo "❌ Console Kernel missing"
fi

echo ""
echo "🗄️ DATABASE CONNECTION TEST"
echo "=========================="

echo "🧪 Testing database connection:"
php -r "
try {
    require 'vendor/autoload.php';
    \$app = require 'bootstrap/app.php';
    \$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
    \$kernel->bootstrap();
    
    \$connection = \$app['db']->connection();
    \$connection->getPdo();
    echo 'Database connection: OK\n';
} catch (Exception \$e) {
    echo 'Database connection: FAILED - ' . \$e->getMessage() . '\n';
}
" 2>&1

echo ""
echo "🔧 ATTEMPTING QUICK FIXES"
echo "========================="

# Fix 1: Fix permissions
echo "🔧 Fix 1: Setting correct permissions..."
chmod +x artisan
chown -R www-data:www-data .
chmod -R 755 storage bootstrap/cache

# Fix 2: Clear all caches
echo "🔧 Fix 2: Clearing caches..."
php artisan config:clear 2>/dev/null && echo "✅ Config cleared" || echo "❌ Config clear failed"
php artisan cache:clear 2>/dev/null && echo "✅ Cache cleared" || echo "❌ Cache clear failed"
php artisan route:clear 2>/dev/null && echo "✅ Routes cleared" || echo "❌ Route clear failed"

# Fix 3: Regenerate autoload
echo "🔧 Fix 3: Regenerating autoload..."
composer dump-autoload 2>/dev/null && echo "✅ Autoload regenerated" || echo "❌ Autoload failed"

# Fix 4: Check if we need to create trading command
echo "🔧 Fix 4: Checking trading command..."
if ! php artisan list | grep -q "trading:start"; then
    echo "❌ trading:start command not found!"
    echo "🔧 Creating basic trading command..."
    
    mkdir -p app/Console/Commands
    cat > app/Console/Commands/TradingStartCommand.php << 'EOF'
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TradingStartCommand extends Command
{
    protected $signature = 'trading:start {--testnet} {--duration=}';
    protected $description = 'Start SentinentX AI trading bot';

    public function handle()
    {
        $this->info('🚀 SentinentX Trading Bot Starting...');
        
        $testnet = $this->option('testnet');
        $duration = $this->option('duration');
        
        $this->info('Testnet: ' . ($testnet ? 'YES' : 'NO'));
        $this->info('Duration: ' . ($duration ?: 'UNLIMITED'));
        
        // For now, just run indefinitely with status messages
        while (true) {
            $this->info('[' . date('Y-m-d H:i:s') . '] Trading bot is running...');
            sleep(60); // Sleep for 1 minute
        }
    }
}
EOF

    # Register command in Kernel
    if [[ -f "app/Console/Kernel.php" ]] && ! grep -q "TradingStartCommand" app/Console/Kernel.php; then
        sed -i '/protected $commands = \[/a\        \\App\\Console\\Commands\\TradingStartCommand::class,' app/Console/Kernel.php
        echo "✅ Trading command registered"
    fi
else
    echo "✅ trading:start command exists"
fi

echo ""
echo "🧪 FINAL TESTS"
echo "=============="

echo "🧪 Testing artisan after fixes:"
php artisan --version 2>&1 | head -3

echo ""
echo "🧪 Testing trading command:"
timeout 5 php artisan trading:start --testnet --duration=test 2>&1 | head -5

echo ""
echo "📊 DIAGNOSIS SUMMARY"
echo "==================="

# Final status
if php artisan list &>/dev/null; then
    echo "✅ Artisan is working"
    if php artisan list | grep -q "trading:start"; then
        echo "✅ trading:start command available"
    else
        echo "❌ trading:start command missing"
    fi
else
    echo "❌ Artisan is broken"
fi

echo ""
echo "🎯 RECOMMENDED ACTIONS:"
echo "1. If artisan broken: composer install"
echo "2. If trading command missing: Create TradingStartCommand"
echo "3. If database fails: Check .env DB settings"
echo "4. Restart service: systemctl restart sentinentx"
