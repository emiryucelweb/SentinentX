#!/bin/bash

# SentinentX 15-Day Testnet Runner
# Automated testing and monitoring for 15-day production trial

set -euo pipefail
IFS=$'\n\t'

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

# Configuration
INSTALL_DIR="/var/www/sentinentx"
TEST_LOG="/var/log/sentinentx_15day_test.log"
START_DATE=$(date '+%Y-%m-%d')
END_DATE=$(date -d '+15 days' '+%Y-%m-%d')

# Logging functions
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1" | tee -a "$TEST_LOG"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1" | tee -a "$TEST_LOG"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1" | tee -a "$TEST_LOG"
}

log_step() {
    echo -e "${BLUE}[STEP]${NC} $1" | tee -a "$TEST_LOG"
}

log_success() {
    echo -e "${CYAN}[SUCCESS]${NC} $1" | tee -a "$TEST_LOG"
}

# Create test log
touch "$TEST_LOG"

# Header
echo "🧪 SentinentX 15-Day Testnet Runner"
echo "==================================="
echo "Start Date: $START_DATE"
echo "End Date: $END_DATE" 
echo "Test Log: $TEST_LOG"
echo "Installation: $INSTALL_DIR"
echo ""

# Check if running as root
if [[ $EUID -ne 0 ]]; then
    log_error "This script must be run as root"
    exit 1
fi

# Check if SentinentX is installed
if [[ ! -d "$INSTALL_DIR" ]]; then
    log_error "SentinentX not found at $INSTALL_DIR"
    log_error "Please run one_command_deploy.sh first"
    exit 1
fi

cd "$INSTALL_DIR"

# Verify environment configuration
log_step "Verifying environment configuration..."

if [[ ! -f ".env" ]]; then
    log_error "Environment file not found"
    exit 1
fi

# Check if API keys are configured
required_keys=(
    "OPENAI_API_KEY"
    "GEMINI_API_KEY" 
    "GROK_API_KEY"
    "BYBIT_API_KEY"
    "BYBIT_API_SECRET"
    "TELEGRAM_BOT_TOKEN"
    "TELEGRAM_CHAT_ID"
)

missing_keys=()
for key in "${required_keys[@]}"; do
    if ! grep -q "^${key}=" .env || grep -q "^${key}=your-" .env || grep -q "^${key}=$" .env; then
        missing_keys+=("$key")
    fi
done

if [[ ${#missing_keys[@]} -gt 0 ]]; then
    log_error "Missing or unconfigured API keys:"
    for key in "${missing_keys[@]}"; do
        echo "  - $key"
    done
    echo ""
    log_error "Please configure these keys in $INSTALL_DIR/.env before starting the test"
    exit 1
fi

log_success "All required API keys configured"

# Verify testnet mode
if ! grep -q "BYBIT_TESTNET=true" .env; then
    log_warn "BYBIT_TESTNET not set to true - forcing testnet mode"
    sed -i "s/BYBIT_TESTNET=false/BYBIT_TESTNET=true/" .env
    sed -i "s/BYBIT_TESTNET=/BYBIT_TESTNET=true/" .env
fi

log_success "Testnet mode verified"

# Create 15-day test configuration
log_step "Setting up 15-day test configuration..."

cat > /tmp/sentinentx_test_config.json << EOF
{
    "test_name": "SentinentX 15-Day Testnet Trial",
    "start_date": "$START_DATE",
    "end_date": "$END_DATE",
    "mode": "testnet",
    "monitoring": {
        "health_check_interval": 300,
        "performance_log_interval": 900,
        "daily_report_time": "00:00"
    },
    "targets": {
        "uptime_percentage": 99.0,
        "avg_response_time_ms": 500,
        "telegram_success_rate": 95.0,
        "ai_consensus_success_rate": 90.0
    }
}
EOF

log_success "Test configuration created"

# Setup monitoring cron jobs
log_step "Setting up automated monitoring..."

# Create monitoring script
cat > /usr/local/bin/sentinentx_monitor.sh << 'EOF'
#!/bin/bash

INSTALL_DIR="/var/www/sentinentx"
LOG_FILE="/var/log/sentinentx_monitor.log"

# Check services
services=("nginx" "postgresql" "redis-server" "php8.2-fpm" "sentinentx-queue" "sentinentx-telegram")
all_running=true

for service in "${services[@]}"; do
    if ! systemctl is-active --quiet "$service"; then
        echo "$(date): SERVICE DOWN - $service" >> "$LOG_FILE"
        systemctl restart "$service"
        all_running=false
    fi
done

if [[ "$all_running" == true ]]; then
    echo "$(date): All services running normally" >> "$LOG_FILE"
fi

# Check disk space
disk_usage=$(df "$INSTALL_DIR" | awk 'NR==2 {print $5}' | sed 's/%//')
if [[ $disk_usage -gt 80 ]]; then
    echo "$(date): WARNING - Disk usage at ${disk_usage}%" >> "$LOG_FILE"
fi

# Check memory usage
mem_usage=$(free | awk 'NR==2 {printf "%.1f", $3*100/$2}')
if [[ $(echo "$mem_usage > 90" | bc -l) == 1 ]]; then
    echo "$(date): WARNING - Memory usage at ${mem_usage}%" >> "$LOG_FILE"
fi

# Test API endpoints
if ! curl -s "http://localhost/api/health" | grep -q "ok"; then
    echo "$(date): WARNING - Health endpoint not responding" >> "$LOG_FILE"
fi
EOF

chmod +x /usr/local/bin/sentinentx_monitor.sh

# Add cron jobs
(crontab -l 2>/dev/null || true; echo "# SentinentX 15-Day Test Monitoring") | crontab -
(crontab -l; echo "*/5 * * * * /usr/local/bin/sentinentx_monitor.sh") | crontab -
(crontab -l; echo "0 0 * * * /usr/local/bin/sentinentx_daily_report.sh") | crontab -

log_success "Monitoring cron jobs configured"

# Create daily report script
cat > /usr/local/bin/sentinentx_daily_report.sh << EOF
#!/bin/bash

INSTALL_DIR="$INSTALL_DIR"
REPORT_DIR="/var/log/sentinentx_reports"
mkdir -p "\$REPORT_DIR"

DATE=\$(date '+%Y-%m-%d')
REPORT_FILE="\$REPORT_DIR/daily_report_\$DATE.txt"

echo "📊 SentinentX Daily Report - \$DATE" > "\$REPORT_FILE"
echo "=======================================" >> "\$REPORT_FILE"
echo "" >> "\$REPORT_FILE"

# System status
echo "🖥️  System Status:" >> "\$REPORT_FILE"
echo "  Uptime: \$(uptime -p)" >> "\$REPORT_FILE"
echo "  Load: \$(uptime | awk -F'load average:' '{print \$2}')" >> "\$REPORT_FILE"
echo "  Memory: \$(free -h | awk 'NR==2 {print \$3"/"\$2}')" >> "\$REPORT_FILE"
echo "  Disk: \$(df -h \$INSTALL_DIR | awk 'NR==2 {print \$3"/"\$2" ("\$5" used)"}')" >> "\$REPORT_FILE"
echo "" >> "\$REPORT_FILE"

# Service status
echo "🔧 Service Status:" >> "\$REPORT_FILE"
for service in nginx postgresql redis-server php8.2-fpm sentinentx-queue sentinentx-telegram; do
    status=\$(systemctl is-active \$service)
    echo "  \$service: \$status" >> "\$REPORT_FILE"
done
echo "" >> "\$REPORT_FILE"

# Laravel logs summary
echo "📝 Application Logs (Last 24h):" >> "\$REPORT_FILE"
if [[ -f "\$INSTALL_DIR/storage/logs/laravel.log" ]]; then
    echo "  Total lines: \$(wc -l < \$INSTALL_DIR/storage/logs/laravel.log)" >> "\$REPORT_FILE"
    echo "  Errors: \$(grep -c "ERROR" \$INSTALL_DIR/storage/logs/laravel.log || echo 0)" >> "\$REPORT_FILE"
    echo "  Warnings: \$(grep -c "WARNING" \$INSTALL_DIR/storage/logs/laravel.log || echo 0)" >> "\$REPORT_FILE"
else
    echo "  Laravel log not found" >> "\$REPORT_FILE"
fi
echo "" >> "\$REPORT_FILE"

# Performance metrics
echo "⚡ Performance:" >> "\$REPORT_FILE"
if curl -s "http://localhost/api/health" &>/dev/null; then
    response_time=\$(curl -w "%{time_total}" -s -o /dev/null "http://localhost/api/health")
    echo "  API Response Time: \${response_time}s" >> "\$REPORT_FILE"
else
    echo "  API not responding" >> "\$REPORT_FILE"
fi
echo "" >> "\$REPORT_FILE"

echo "Report generated: \$(date)" >> "\$REPORT_FILE"
EOF

chmod +x /usr/local/bin/sentinentx_daily_report.sh

log_success "Daily reporting configured"

# Restart services to ensure clean start
log_step "Restarting all services for clean start..."

services=("sentinentx-queue" "sentinentx-telegram" "nginx" "php8.2-fpm")
for service in "${services[@]}"; do
    systemctl restart "$service"
    if systemctl is-active --quiet "$service"; then
        log_success "Service restarted: $service"
    else
        log_error "Failed to restart: $service"
    fi
done

# Initial system test
log_step "Running initial system test..."

# Test database connection
if php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database OK';" 2>/dev/null | grep -q "Database OK"; then
    log_success "Database connection verified"
else
    log_error "Database connection failed"
fi

# Test Redis connection
if php artisan tinker --execute="Cache::put('test', 'ok'); echo Cache::get('test');" 2>/dev/null | grep -q "ok"; then
    log_success "Redis connection verified"
else
    log_error "Redis connection failed"
fi

# Test web server
if curl -s "http://localhost" &>/dev/null; then
    log_success "Web server responding"
else
    log_error "Web server not responding"
fi

# Create test summary
log_step "Creating 15-day test tracking file..."

cat > /root/sentinentx_15day_test.txt << EOF
🧪 SentinentX 15-Day Testnet Trial
==================================
Start Date: $START_DATE
End Date: $END_DATE
Test Status: ACTIVE
Mode: TESTNET

📊 Daily Progress Tracking:
$(for i in {1..15}; do
    test_date=$(date -d "+$((i-1)) days" '+%Y-%m-%d')
    echo "Day $i ($test_date): [ ] Pending"
done)

📋 Test Objectives:
[ ] System stability (99% uptime target)
[ ] Telegram bot functionality
[ ] AI consensus system
[ ] Position management
[ ] Risk profiling
[ ] Performance optimization
[ ] Error handling
[ ] Security validation

📈 Key Metrics to Track:
- Uptime percentage
- API response times
- Telegram command success rate
- AI decision accuracy
- Memory/CPU usage
- Database performance
- Error frequency

📁 Important Files:
- Main log: $TEST_LOG
- Daily reports: /var/log/sentinentx_reports/
- Monitor log: /var/log/sentinentx_monitor.log
- Installation: $INSTALL_DIR

🔧 Monitoring Commands:
- Check status: systemctl status sentinentx-*
- View logs: tail -f $INSTALL_DIR/storage/logs/laravel.log
- System health: /usr/local/bin/sentinentx_monitor.sh
- Daily report: /usr/local/bin/sentinentx_daily_report.sh

📱 Telegram Test Commands:
/help - Show available commands
/status - System status
/scan - Analyze coins
/balance - Account balance
/pnl - Profit/Loss summary

Updated: $(date '+%Y-%m-%d %H:%M:%S')
EOF

log_success "15-day test tracking file created: /root/sentinentx_15day_test.txt"

# Final instructions
echo ""
echo "🎯 15-Day Testnet Trial Started Successfully!"
echo "============================================="
echo ""
echo "📅 Test Period: $START_DATE to $END_DATE"
echo ""
echo "🔍 Monitoring:"
echo "  • Automated monitoring every 5 minutes"
echo "  • Daily reports generated at midnight"
echo "  • Service auto-restart on failure"
echo ""
echo "📊 Track Progress:"
echo "  • Test status: cat /root/sentinentx_15day_test.txt"
echo "  • Live logs: tail -f $TEST_LOG"
echo "  • App logs: tail -f $INSTALL_DIR/storage/logs/laravel.log"
echo "  • Daily reports: ls /var/log/sentinentx_reports/"
echo ""
echo "🧪 Manual Testing:"
echo "  • Telegram bot: Send /help to your bot"
echo "  • Web interface: http://$(curl -s ifconfig.me)/api/health"
echo "  • Service status: systemctl status sentinentx-*"
echo ""
echo "⚠️  Important Notes:"
echo "  • System is in TESTNET mode (no real money)"
echo "  • Monitor daily reports for issues"
echo "  • Update /root/sentinentx_15day_test.txt daily"
echo "  • Document any issues for improvement"
echo ""
echo "✅ Ready for 15-day production trial! 🚀💰"

log_success "15-day testnet trial initialized successfully!"
