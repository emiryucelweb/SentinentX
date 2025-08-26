#!/bin/bash

# SentinentX Deployment Validation Test
# Comprehensive post-deployment validation

set -euo pipefail

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

INSTALL_DIR="/var/www/sentinentx"
TESTS_PASSED=0
TOTAL_TESTS=0

log_test() {
    echo -e "${BLUE}[TEST]${NC} $1"
    ((TOTAL_TESTS++))
}

log_pass() {
    echo -e "${GREEN}[PASS]${NC} $1"
    ((TESTS_PASSED++))
}

log_fail() {
    echo -e "${RED}[FAIL]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

echo "🧪 SentinentX Deployment Validation Test"
echo "======================================="
echo "Installation Directory: $INSTALL_DIR"
echo "Test Start: $(date)"
echo ""

# Test 1: Installation Directory
log_test "Installation directory exists"
if [[ -d "$INSTALL_DIR" ]]; then
    log_pass "Installation directory found"
else
    log_fail "Installation directory not found"
fi

# Test 2: Laravel Structure
log_test "Laravel project structure"
cd "$INSTALL_DIR" 2>/dev/null || exit 1
if [[ -f "artisan" ]] && [[ -f "composer.json" ]] && [[ -f ".env" ]]; then
    log_pass "Laravel structure complete"
else
    log_fail "Laravel structure incomplete"
    ls -la
fi

# Test 3: PHP Installation
log_test "PHP installation and version"
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -v | head -n1)
    log_pass "PHP found: $PHP_VERSION"
else
    log_fail "PHP not found"
fi

# Test 4: Composer Installation
log_test "Composer installation"
if command -v composer &> /dev/null; then
    COMPOSER_VERSION=$(composer --version)
    log_pass "Composer found: $COMPOSER_VERSION"
else
    log_fail "Composer not found"
fi

# Test 5: Dependencies
log_test "Composer dependencies"
if [[ -d "vendor" ]] && [[ -f "vendor/autoload.php" ]]; then
    log_pass "Composer dependencies installed"
else
    log_fail "Composer dependencies missing"
fi

# Test 6: Environment Configuration
log_test "Environment configuration"
if [[ -f ".env" ]]; then
    MISSING_VARS=()
    REQUIRED_VARS=("APP_KEY" "DB_CONNECTION" "DB_PASSWORD" "REDIS_PASSWORD")
    
    for var in "${REQUIRED_VARS[@]}"; do
        if ! grep -q "^${var}=" .env || grep -q "^${var}=$" .env; then
            MISSING_VARS+=("$var")
        fi
    done
    
    if [[ ${#MISSING_VARS[@]} -eq 0 ]]; then
        log_pass "Environment configuration complete"
    else
        log_fail "Missing environment variables: ${MISSING_VARS[*]}"
    fi
else
    log_fail ".env file not found"
fi

# Test 7: Database Connection
log_test "Database connection"
if php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'DB_OK'; } catch(Exception \$e) { echo 'DB_FAIL'; }" 2>/dev/null | grep -q "DB_OK"; then
    log_pass "Database connection successful"
else
    log_fail "Database connection failed"
fi

# Test 8: Redis Connection
log_test "Redis connection"
if php artisan tinker --execute="try { Cache::put('test', 'ok'); echo Cache::get('test'); } catch(Exception \$e) { echo 'FAIL'; }" 2>/dev/null | grep -q "ok"; then
    log_pass "Redis connection successful"
else
    log_fail "Redis connection failed"
fi

# Test 9: Artisan Commands
log_test "Artisan commands"
if php artisan --version &>/dev/null; then
    log_pass "Artisan commands working"
else
    log_fail "Artisan commands failed"
fi

# Test 10: File Permissions
log_test "File permissions"
if [[ -w "storage/logs" ]] && [[ -w "bootstrap/cache" ]]; then
    log_pass "File permissions correct"
else
    log_fail "File permissions incorrect"
fi

# Test 11: Web Server
log_test "Web server (Nginx)"
if systemctl is-active --quiet nginx; then
    log_pass "Nginx is running"
else
    log_fail "Nginx is not running"
fi

# Test 12: Web Response
log_test "Web server response"
if curl -s --max-time 10 "http://localhost" &>/dev/null; then
    log_pass "Web server responding"
else
    log_fail "Web server not responding"
fi

# Test 13: Services
log_test "SentinentX services"
SERVICES=("sentinentx-queue" "sentinentx-telegram")
SERVICE_FAILURES=()

for service in "${SERVICES[@]}"; do
    if systemctl is-active --quiet "$service" 2>/dev/null; then
        log_pass "$service is running"
    else
        SERVICE_FAILURES+=("$service")
    fi
done

if [[ ${#SERVICE_FAILURES[@]} -gt 0 ]]; then
    log_fail "Services not running: ${SERVICE_FAILURES[*]}"
else
    log_pass "All SentinentX services running"
fi

# Test 14: Log Files
log_test "Log files accessibility"
if [[ -f "storage/logs/laravel.log" ]] || touch "storage/logs/laravel.log" 2>/dev/null; then
    log_pass "Log files accessible"
else
    log_fail "Cannot access log files"
fi

# Test 15: Configuration Cache
log_test "Configuration optimization"
if [[ -f "bootstrap/cache/config.php" ]]; then
    log_pass "Configuration cached"
else
    log_warn "Configuration not cached (performance impact)"
    ((TESTS_PASSED++))  # Not critical
fi

echo ""
echo "🏆 Test Results Summary"
echo "======================"
echo "Tests Passed: $TESTS_PASSED/$TOTAL_TESTS"
echo "Success Rate: $(( TESTS_PASSED * 100 / TOTAL_TESTS ))%"
echo ""

if [[ $TESTS_PASSED -eq $TOTAL_TESTS ]]; then
    echo -e "${GREEN}✅ ALL TESTS PASSED - DEPLOYMENT SUCCESSFUL!${NC}"
    echo "🚀 SentinentX is ready for operation!"
    exit 0
elif [[ $TESTS_PASSED -ge $((TOTAL_TESTS * 80 / 100)) ]]; then
    echo -e "${YELLOW}⚠️  MOSTLY SUCCESSFUL - MINOR ISSUES DETECTED${NC}"
    echo "🔧 System is functional but may need attention"
    exit 0
else
    echo -e "${RED}❌ DEPLOYMENT FAILED - CRITICAL ISSUES DETECTED${NC}"
    echo "🚨 Please review and fix the issues above"
    exit 1
fi
