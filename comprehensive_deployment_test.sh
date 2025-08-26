#!/bin/bash

# COMPREHENSIVE DEPLOYMENT TEST SUITE
# Tests ALL functions and components of SentinentX
echo "🧪🔥 COMPREHENSIVE SENTINENTX DEPLOYMENT TEST SUITE 🔥🧪"
echo "========================================================="

TESTS_PASSED=0
TOTAL_TESTS=0
CRITICAL_FAILURES=0

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

log_test() {
    echo -e "${BLUE}[TEST $((++TOTAL_TESTS))]${NC} $1"
}

log_pass() {
    echo -e "${GREEN}[PASS]${NC} $1"
    ((TESTS_PASSED++))
}

log_fail() {
    echo -e "${RED}[FAIL]${NC} $1"
    [[ "$2" == "CRITICAL" ]] && ((CRITICAL_FAILURES++))
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_info() {
    echo -e "${CYAN}[INFO]${NC} $1"
}

# ============================================================================
# SECTION 1: INFRASTRUCTURE TESTS
# ============================================================================
echo ""
echo "🏗️ SECTION 1: INFRASTRUCTURE TESTS"
echo "=================================="

# Test 1: System Services
log_test "System Services Status"
SERVICES=("postgresql" "nginx" "redis-server" "php8.3-fpm")
for service in "${SERVICES[@]}"; do
    if systemctl is-active --quiet "$service"; then
        log_pass "$service is running"
    else
        log_fail "$service is NOT running" "CRITICAL"
    fi
done

# Test 2: Port Accessibility
log_test "Port Accessibility"
PORTS=("80:HTTP" "5432:PostgreSQL" "6379:Redis")
for port_info in "${PORTS[@]}"; do
    port=$(echo $port_info | cut -d: -f1)
    service=$(echo $port_info | cut -d: -f2)
    if netstat -tlnp | grep -q ":$port "; then
        log_pass "Port $port ($service) is listening"
    else
        log_fail "Port $port ($service) is NOT listening" "CRITICAL"
    fi
done

# Test 3: Web Server Response
log_test "Web Server HTTP Response"
HTTP_RESPONSE=$(curl -s -I http://localhost 2>/dev/null | head -1 || echo "FAILED")
if echo "$HTTP_RESPONSE" | grep -q "200\|301\|302"; then
    log_pass "HTTP response: $HTTP_RESPONSE"
else
    log_fail "HTTP response failed: $HTTP_RESPONSE" "CRITICAL"
fi

# ============================================================================
# SECTION 2: LARAVEL APPLICATION TESTS
# ============================================================================
echo ""
echo "🚀 SECTION 2: LARAVEL APPLICATION TESTS"
echo "======================================="

cd /var/www/sentinentx || {
    log_fail "Cannot access /var/www/sentinentx directory" "CRITICAL"
    exit 1
}

# Test 4: Laravel Structure
log_test "Laravel Directory Structure"
REQUIRED_DIRS=("app" "config" "database" "storage" "bootstrap" "public" "routes")
for dir in "${REQUIRED_DIRS[@]}"; do
    if [[ -d "$dir" ]]; then
        log_pass "Directory '$dir' exists"
    else
        log_fail "Directory '$dir' is missing" "CRITICAL"
    fi
done

# Test 5: Essential Files
log_test "Essential Laravel Files"
REQUIRED_FILES=("artisan" "composer.json" ".env")
for file in "${REQUIRED_FILES[@]}"; do
    if [[ -f "$file" ]]; then
        log_pass "File '$file' exists"
    else
        log_fail "File '$file' is missing" "CRITICAL"
    fi
done

# Test 6: File Permissions
log_test "File Permissions"
WRITABLE_DIRS=("storage" "bootstrap/cache")
for dir in "${WRITABLE_DIRS[@]}"; do
    if [[ -w "$dir" ]]; then
        log_pass "Directory '$dir' is writable"
    else
        log_fail "Directory '$dir' is NOT writable" "CRITICAL"
    fi
done

# Test 7: Environment Configuration
log_test "Environment Configuration"
if [[ -f ".env" ]]; then
    ENV_VARS=("APP_NAME" "APP_KEY" "DB_CONNECTION" "DB_DATABASE" "DB_USERNAME" "DB_PASSWORD")
    for var in "${ENV_VARS[@]}"; do
        if grep -q "^${var}=" .env && ! grep -q "^${var}=$" .env; then
            log_pass "Environment variable '$var' is set"
        else
            log_fail "Environment variable '$var' is missing or empty"
        fi
    done
else
    log_fail ".env file is missing" "CRITICAL"
fi

# Test 8: Application Key
log_test "Laravel Application Key"
if grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    APP_KEY=$(grep "APP_KEY=" .env | cut -d'=' -f2)
    log_pass "Application key is configured: ${APP_KEY:0:20}..."
else
    log_fail "Application key is not properly configured" "CRITICAL"
fi

# ============================================================================
# SECTION 3: DATABASE TESTS
# ============================================================================
echo ""
echo "🗄️ SECTION 3: DATABASE TESTS"
echo "============================="

# Test 9: PostgreSQL Connection
log_test "PostgreSQL Service Connection"
DB_PASSWORD=$(grep "DB_PASSWORD=" .env | cut -d'=' -f2)
DB_USER=$(grep "DB_USERNAME=" .env | cut -d'=' -f2)
DB_NAME=$(grep "DB_DATABASE=" .env | cut -d'=' -f2)

if PGPASSWORD="$DB_PASSWORD" psql -h 127.0.0.1 -U "$DB_USER" -d "$DB_NAME" -c "SELECT 1;" &>/dev/null; then
    log_pass "PostgreSQL connection successful"
else
    log_fail "PostgreSQL connection failed" "CRITICAL"
fi

# Test 10: Laravel Database Connection
log_test "Laravel Database Connection"
if command -v php &>/dev/null && [[ -f "artisan" ]]; then
    DB_TEST=$(php artisan tinker --execute="
        try { 
            DB::connection()->getPdo(); 
            echo 'DB_OK'; 
        } catch(Exception \$e) { 
            echo 'DB_FAIL: ' . \$e->getMessage(); 
        }" 2>/dev/null)
    
    if echo "$DB_TEST" | grep -q "DB_OK"; then
        log_pass "Laravel database connection successful"
    else
        log_fail "Laravel database connection failed: $DB_TEST"
    fi
else
    log_fail "Cannot test Laravel database connection - PHP or artisan missing"
fi

# Test 11: Database Tables
log_test "Database Tables Check"
if PGPASSWORD="$DB_PASSWORD" psql -h 127.0.0.1 -U "$DB_USER" -d "$DB_NAME" -c "\dt" &>/dev/null; then
    TABLE_COUNT=$(PGPASSWORD="$DB_PASSWORD" psql -h 127.0.0.1 -U "$DB_USER" -d "$DB_NAME" -t -c "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public';" 2>/dev/null | tr -d ' ')
    if [[ "$TABLE_COUNT" -gt 0 ]]; then
        log_pass "Database has $TABLE_COUNT tables"
    else
        log_warn "Database has no tables (migrations may not have run)"
    fi
else
    log_fail "Cannot check database tables"
fi

# ============================================================================
# SECTION 4: REDIS TESTS  
# ============================================================================
echo ""
echo "🔴 SECTION 4: REDIS TESTS"
echo "========================="

# Test 12: Redis Connection
log_test "Redis Connection"
if redis-cli ping 2>/dev/null | grep -q "PONG"; then
    log_pass "Redis connection successful"
else
    log_fail "Redis connection failed" "CRITICAL"
fi

# Test 13: Redis Write/Read Test
log_test "Redis Write/Read Operations"
TEST_KEY="sentinentx_test_$(date +%s)"
if redis-cli set "$TEST_KEY" "test_value" &>/dev/null && \
   [[ "$(redis-cli get "$TEST_KEY" 2>/dev/null)" == "test_value" ]]; then
    redis-cli del "$TEST_KEY" &>/dev/null
    log_pass "Redis read/write operations successful"
else
    log_fail "Redis read/write operations failed"
fi

# ============================================================================
# SECTION 5: PHP & COMPOSER TESTS
# ============================================================================
echo ""
echo "🐘 SECTION 5: PHP & COMPOSER TESTS"
echo "=================================="

# Test 14: PHP Version
log_test "PHP Version & Extensions"
if command -v php &>/dev/null; then
    PHP_VERSION=$(php -v | head -1)
    log_pass "PHP available: $PHP_VERSION"
    
    # Check essential PHP extensions
    PHP_EXTENSIONS=("pdo" "pdo_pgsql" "redis" "curl" "mbstring" "bcmath")
    for ext in "${PHP_EXTENSIONS[@]}"; do
        if php -m | grep -q "$ext"; then
            log_pass "PHP extension '$ext' is loaded"
        else
            log_fail "PHP extension '$ext' is missing"
        fi
    done
else
    log_fail "PHP is not available" "CRITICAL"
fi

# Test 15: Composer
log_test "Composer & Dependencies"
if command -v composer &>/dev/null; then
    COMPOSER_VERSION=$(composer --version 2>/dev/null)
    log_pass "Composer available: $COMPOSER_VERSION"
    
    if [[ -d "vendor" ]] && [[ -f "vendor/autoload.php" ]]; then
        log_pass "Composer dependencies are installed"
    else
        log_warn "Composer dependencies may not be fully installed"
    fi
else
    log_fail "Composer is not available"
fi

# ============================================================================
# SECTION 6: SENTINENTX SPECIFIC TESTS
# ============================================================================
echo ""
echo "🤖 SECTION 6: SENTINENTX SPECIFIC TESTS"
echo "======================================="

# Test 16: SentinentX Configuration Files
log_test "SentinentX Configuration Files"
SENTX_CONFIGS=("config/ai.php" "config/trading.php" "config/exchange.php" "config/lab.php")
for config in "${SENTX_CONFIGS[@]}"; do
    if [[ -f "$config" ]]; then
        log_pass "Config file '$config' exists"
    else
        log_warn "Config file '$config' is missing"
    fi
done

# Test 17: Trading Configuration
log_test "Trading Configuration in .env"
TRADING_VARS=("RISK_PROFILE" "BYBIT_TESTNET" "HMAC_SECRET")
for var in "${TRADING_VARS[@]}"; do
    if grep -q "^${var}=" .env; then
        VALUE=$(grep "^${var}=" .env | cut -d'=' -f2)
        log_pass "Trading config '$var' = $VALUE"
    else
        log_warn "Trading config '$var' is missing"
    fi
done

# Test 18: AI Provider Configuration
log_test "AI Provider Configuration"
AI_VARS=("OPENAI_API_KEY" "GEMINI_API_KEY" "GROK_API_KEY")
AI_CONFIGURED=0
for var in "${AI_VARS[@]}"; do
    if grep -q "^${var}=" .env && ! grep -q "^${var}=$" .env && ! grep -q "your-.*-key" .env; then
        log_pass "AI provider '$var' appears configured"
        ((AI_CONFIGURED++))
    else
        log_warn "AI provider '$var' needs configuration"
    fi
done

if [[ $AI_CONFIGURED -gt 0 ]]; then
    log_pass "$AI_CONFIGURED AI provider(s) configured"
else
    log_warn "No AI providers appear to be configured with real keys"
fi

# ============================================================================
# SECTION 7: SECURITY TESTS
# ============================================================================
echo ""
echo "🔒 SECTION 7: SECURITY TESTS"
echo "============================"

# Test 19: File Ownership
log_test "File Ownership & Security"
OWNER=$(stat -c %U /var/www/sentinentx 2>/dev/null)
if [[ "$OWNER" == "www-data" ]] || [[ "$OWNER" == "root" ]]; then
    log_pass "Main directory owner: $OWNER"
else
    log_warn "Unusual directory owner: $OWNER"
fi

# Test 20: .env File Security
log_test ".env File Security"
ENV_PERMS=$(stat -c %a .env 2>/dev/null)
if [[ "$ENV_PERMS" =~ ^[67][0-4][0-4]$ ]]; then
    log_pass ".env file permissions: $ENV_PERMS (secure)"
else
    log_warn ".env file permissions: $ENV_PERMS (may be too permissive)"
fi

# Test 21: Secret Configuration
log_test "Secret Configuration Security"
if grep -q "your-.*-key\|your_.*_key\|changeme\|secret\|password123" .env; then
    log_warn "Default/placeholder values detected in .env - update for production"
else
    log_pass "No obvious placeholder values in .env"
fi

# ============================================================================
# SECTION 8: PERFORMANCE TESTS
# ============================================================================
echo ""
echo "⚡ SECTION 8: PERFORMANCE TESTS"
echo "==============================="

# Test 22: Laravel Response Time
log_test "Laravel Response Time"
if command -v curl &>/dev/null; then
    RESPONSE_TIME=$(curl -o /dev/null -s -w "%{time_total}" http://localhost 2>/dev/null || echo "999")
    if (( $(echo "$RESPONSE_TIME < 2.0" | bc -l) )); then
        log_pass "Response time: ${RESPONSE_TIME}s (good)"
    elif (( $(echo "$RESPONSE_TIME < 5.0" | bc -l) )); then
        log_warn "Response time: ${RESPONSE_TIME}s (acceptable)"
    else
        log_fail "Response time: ${RESPONSE_TIME}s (slow)"
    fi
else
    log_warn "Cannot test response time - curl not available"
fi

# Test 23: Memory Usage
log_test "System Memory Usage"
MEM_TOTAL=$(free -m | awk 'NR==2{printf "%.1f", $3*100/$2 }')
if (( $(echo "$MEM_TOTAL < 80" | bc -l) )); then
    log_pass "Memory usage: ${MEM_TOTAL}% (good)"
else
    log_warn "Memory usage: ${MEM_TOTAL}% (high)"
fi

# Test 24: Disk Space
log_test "Disk Space"
DISK_USAGE=$(df /var/www | awk 'NR==2 {print $5}' | sed 's/%//')
if [[ $DISK_USAGE -lt 80 ]]; then
    log_pass "Disk usage: ${DISK_USAGE}% (good)"
else
    log_warn "Disk usage: ${DISK_USAGE}% (high)"
fi

# ============================================================================
# FINAL REPORT
# ============================================================================
echo ""
echo "📊 COMPREHENSIVE TEST REPORT"
echo "=============================="

echo "Tests Completed: $TOTAL_TESTS"
echo "Tests Passed: $TESTS_PASSED"
echo "Tests Failed: $((TOTAL_TESTS - TESTS_PASSED))"
echo "Critical Failures: $CRITICAL_FAILURES"

SUCCESS_RATE=$(( TESTS_PASSED * 100 / TOTAL_TESTS ))
echo "Success Rate: ${SUCCESS_RATE}%"

echo ""
if [[ $CRITICAL_FAILURES -eq 0 ]] && [[ $SUCCESS_RATE -ge 90 ]]; then
    echo -e "${GREEN}🎉 DEPLOYMENT STATUS: EXCELLENT! 🎉${NC}"
    echo -e "${GREEN}✅ Production ready with ${SUCCESS_RATE}% success rate${NC}"
elif [[ $CRITICAL_FAILURES -eq 0 ]] && [[ $SUCCESS_RATE -ge 75 ]]; then
    echo -e "${YELLOW}⚠️ DEPLOYMENT STATUS: GOOD${NC}"
    echo -e "${YELLOW}✅ Functional with minor issues (${SUCCESS_RATE}% success)${NC}"
elif [[ $CRITICAL_FAILURES -eq 0 ]]; then
    echo -e "${YELLOW}⚠️ DEPLOYMENT STATUS: FUNCTIONAL${NC}"
    echo -e "${YELLOW}⚠️ Working but needs attention (${SUCCESS_RATE}% success)${NC}"
else
    echo -e "${RED}❌ DEPLOYMENT STATUS: NEEDS FIXES${NC}"
    echo -e "${RED}❌ ${CRITICAL_FAILURES} critical issues need resolution${NC}"
fi

echo ""
echo "🔧 NEXT STEPS:"
if [[ $CRITICAL_FAILURES -gt 0 ]]; then
    echo "1. Fix critical failures listed above"
    echo "2. Re-run this test suite"
    echo "3. Configure AI provider API keys for trading"
elif [[ $SUCCESS_RATE -lt 90 ]]; then
    echo "1. Review warnings and fix minor issues"
    echo "2. Configure remaining AI provider API keys"
    echo "3. Run production optimization"
else
    echo "1. Configure AI provider API keys for trading"
    echo "2. Set up monitoring and alerts"
    echo "3. Begin testnet trading operations"
fi

echo ""
echo "🚀 COMPREHENSIVE TESTING COMPLETED!"
echo "Deployment quality: $SUCCESS_RATE% with $CRITICAL_FAILURES critical issues"
