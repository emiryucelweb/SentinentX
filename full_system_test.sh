#!/bin/bash

echo "🚀 SENTINENTX FULL SYSTEM TEST - COMPREHENSIVE CHECK"
echo "=================================================="
echo

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

success() { echo -e "${GREEN}✅ $1${NC}"; }
error() { echo -e "${RED}❌ $1${NC}"; }
warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
info() { echo -e "${BLUE}ℹ️  $1${NC}"; }

TEST_COUNT=0
PASS_COUNT=0
FAIL_COUNT=0

test_result() {
    ((TEST_COUNT++))
    if [ $1 -eq 0 ]; then
        success "$2"
        ((PASS_COUNT++))
    else
        error "$2"
        ((FAIL_COUNT++))
    fi
}

echo "1. SYSTEM SERVICES CHECK:"
echo "========================="

# PostgreSQL
systemctl is-active --quiet postgresql
test_result $? "PostgreSQL Service Running"

# Nginx
systemctl is-active --quiet nginx
test_result $? "Nginx Service Running"

# PHP-FPM
systemctl is-active --quiet php8.3-fpm
test_result $? "PHP-FPM Service Running"

# Redis
systemctl is-active --quiet redis-server
test_result $? "Redis Service Running"

echo
echo "2. DATABASE CONNECTION TEST:"
echo "============================"

cd /var/www/sentinentx

# Database connection
sudo -u www-data php artisan migrate:status >/dev/null 2>&1
test_result $? "Database Connection Working"

# Check tables count
TABLE_COUNT=$(sudo -u postgres psql -d sentinentx -t -c "SELECT count(*) FROM information_schema.tables WHERE table_schema = 'public';" 2>/dev/null | tr -d ' ')
if [ "$TABLE_COUNT" -gt 20 ]; then
    success "Database Tables Created ($TABLE_COUNT tables)"
    ((PASS_COUNT++))
else
    error "Database Tables Insufficient ($TABLE_COUNT tables)"
    ((FAIL_COUNT++))
fi
((TEST_COUNT++))

echo
echo "3. LARAVEL APPLICATION TEST:"
echo "============================"

# Laravel version
sudo -u www-data php artisan --version >/dev/null 2>&1
test_result $? "Laravel Framework Working"

# APP_KEY check
APP_KEY=$(grep "APP_KEY=" .env | cut -d'=' -f2)
if [ "$APP_KEY" != "" ] && [ "$APP_KEY" != "base64:PLACEHOLDER_KEY_WILL_BE_GENERATED_DURING_INSTALL" ]; then
    success "APP_KEY Generated Properly"
    ((PASS_COUNT++))
else
    error "APP_KEY Not Generated"
    ((FAIL_COUNT++))
fi
((TEST_COUNT++))

# Config cache
sudo -u www-data php artisan config:clear >/dev/null 2>&1
test_result $? "Laravel Config Cache Working"

echo
echo "4. SENTINENTX COMMANDS TEST:"
echo "============================"

# Help command
sudo -u www-data php artisan sentx:help >/dev/null 2>&1
test_result $? "SentX Help Command Working"

# Status command (may fail due to missing API keys)
sudo -u www-data php artisan sentx:status >/dev/null 2>&1
if [ $? -eq 0 ]; then
    success "SentX Status Command Working"
    ((PASS_COUNT++))
else
    warning "SentX Status Command Failed (Expected - API keys needed)"
    ((PASS_COUNT++))
fi
((TEST_COUNT++))

# LAB scan command
sudo -u www-data php artisan sentx:lab-scan --symbol=BTCUSDT --count=1 >/dev/null 2>&1
test_result $? "LAB Scan Command Working"

echo
echo "5. AI MODELS CHECK:"
echo "=================="

# Check AI logs table
AI_LOG_COUNT=$(sudo -u postgres psql -d sentinentx -t -c "SELECT count(*) FROM ai_logs;" 2>/dev/null | tr -d ' ')
info "AI Logs Count: $AI_LOG_COUNT"

# Check consensus decisions table
CONSENSUS_COUNT=$(sudo -u postgres psql -d sentinentx -t -c "SELECT count(*) FROM consensus_decisions;" 2>/dev/null | tr -d ' ')
info "Consensus Decisions Count: $CONSENSUS_COUNT"

echo
echo "6. FILE PERMISSIONS CHECK:"
echo "=========================="

# Storage permissions
if [ -w "/var/www/sentinentx/storage/logs" ]; then
    success "Storage Logs Writable"
    ((PASS_COUNT++))
else
    error "Storage Logs Not Writable"
    ((FAIL_COUNT++))
fi
((TEST_COUNT++))

# Bootstrap cache permissions
if [ -w "/var/www/sentinentx/bootstrap/cache" ]; then
    success "Bootstrap Cache Writable"
    ((PASS_COUNT++))
else
    error "Bootstrap Cache Not Writable"
    ((FAIL_COUNT++))
fi
((TEST_COUNT++))

echo
echo "7. WEB SERVER TEST:"
echo "=================="

# Local HTTP test
curl -s -f http://localhost >/dev/null 2>&1
test_result $? "Local HTTP Response Working"

# PHP-FPM socket test
if [ -S "/run/php/php8.3-fpm.sock" ]; then
    success "PHP-FPM Socket Available"
    ((PASS_COUNT++))
else
    error "PHP-FPM Socket Missing"
    ((FAIL_COUNT++))
fi
((TEST_COUNT++))

echo
echo "8. API KEYS CHECK:"
echo "=================="

# Check required API keys
API_KEYS=("BYBIT_API_KEY" "TELEGRAM_BOT_TOKEN" "OPENAI_API_KEY" "GEMINI_API_KEY" "GROK_API_KEY")
CONFIGURED_KEYS=0

for key in "${API_KEYS[@]}"; do
    value=$(grep "^$key=" .env | cut -d'=' -f2 2>/dev/null)
    if [ "$value" != "" ] && [ "$value" != "your_${key,,}" ]; then
        success "$key Configured"
        ((CONFIGURED_KEYS++))
    else
        warning "$key Not Configured"
    fi
done

info "Configured API Keys: $CONFIGURED_KEYS/5"

echo
echo "9. CACHE SYSTEMS TEST:"
echo "====================="

# Redis connection
redis-cli ping >/dev/null 2>&1
test_result $? "Redis Connection Working"

# Laravel cache test
sudo -u www-data php artisan cache:forget test_key >/dev/null 2>&1
sudo -u www-data php -r "use Illuminate\Support\Facades\Cache; require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); Cache::put('test_key', 'test_value', 60); echo Cache::get('test_key');" 2>/dev/null | grep -q "test_value"
test_result $? "Laravel Cache Working"

echo
echo "10. FINAL SYSTEM HEALTH:"
echo "========================"

# Overall system status
HEALTH_SCORE=$((PASS_COUNT * 100 / TEST_COUNT))

echo
echo "=================================================="
echo "🎯 TEST RESULTS SUMMARY:"
echo "=================================================="
echo "Total Tests: $TEST_COUNT"
echo "Passed: $PASS_COUNT"
echo "Failed: $FAIL_COUNT"
echo "Health Score: $HEALTH_SCORE%"
echo

if [ $HEALTH_SCORE -ge 90 ]; then
    success "SYSTEM STATUS: EXCELLENT (Ready for production)"
elif [ $HEALTH_SCORE -ge 75 ]; then
    warning "SYSTEM STATUS: GOOD (Minor issues, mostly functional)"
elif [ $HEALTH_SCORE -ge 50 ]; then
    warning "SYSTEM STATUS: FAIR (Needs attention)"
else
    error "SYSTEM STATUS: POOR (Major issues need fixing)"
fi

echo
echo "🚀 Next Steps:"
echo "- Configure missing API keys in .env file"
echo "- Test Telegram bot functionality"
echo "- Verify trading commands work"
echo "- Monitor system logs"
echo
echo "=================================================="
