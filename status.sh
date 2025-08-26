#!/bin/bash

# SentinentX Status Checker
# Updated: 2025-01-20

echo "📊 SentinentX System Status"
echo "=========================="
echo ""

# Check if .env exists
if [ ! -f .env ]; then
    echo "❌ .env file not found"
    exit 1
fi

# Source environment variables
source .env

echo "🏗️ Environment: $APP_ENV"
echo "🔧 Debug Mode: $([ "$APP_DEBUG" = "true" ] && echo "ON" || echo "OFF")"
echo ""

# Database Status
echo "🗄️ Database Connection:"
if php artisan migrate:status &> /dev/null; then
    echo "  ✅ Connected to PostgreSQL"
    PENDING_MIGRATIONS=$(php artisan migrate:status | grep -c "Pending" || echo "0")
    if [ "$PENDING_MIGRATIONS" -gt 0 ]; then
        echo "  ⚠️ $PENDING_MIGRATIONS pending migrations"
    else
        echo "  ✅ All migrations up to date"
    fi
else
    echo "  ❌ Database connection failed"
fi

# Redis Status
echo ""
echo "🔴 Redis Connection:"
if php artisan tinker --execute="cache()->put('status_test', 'ok'); echo cache()->get('status_test');" 2>/dev/null | grep -q "ok"; then
    echo "  ✅ Connected and working"
else
    echo "  ❌ Redis connection failed"
fi

# Services Status
echo ""
echo "🚀 Services Status:"

# Queue Worker
if pgrep -f "artisan queue:work" > /dev/null; then
    QUEUE_PID=$(pgrep -f "artisan queue:work")
    echo "  ✅ Queue Worker (PID: $QUEUE_PID)"
    
    # Check queue health
    FAILED_JOBS=$(php artisan queue:failed --json 2>/dev/null | jq length 2>/dev/null || echo "0")
    if [ "$FAILED_JOBS" -gt 0 ]; then
        echo "    ⚠️ $FAILED_JOBS failed jobs"
    fi
else
    echo "  ❌ Queue Worker"
fi

# Scheduler
if pgrep -f "artisan schedule:work" > /dev/null; then
    SCHEDULER_PID=$(pgrep -f "artisan schedule:work")
    echo "  ✅ Scheduler (PID: $SCHEDULER_PID)"
else
    echo "  ❌ Scheduler"
fi

# Horizon (if available)
if php artisan horizon:status &> /dev/null; then
    if pgrep -f "artisan horizon" > /dev/null; then
        HORIZON_PID=$(pgrep -f "artisan horizon")
        echo "  ✅ Horizon (PID: $HORIZON_PID)"
    else
        echo "  ❌ Horizon"
    fi
fi

# Web Server (development)
if [ "$APP_ENV" = "local" ]; then
    if pgrep -f "artisan serve" > /dev/null; then
        SERVER_PID=$(pgrep -f "artisan serve")
        echo "  ✅ Web Server (PID: $SERVER_PID)"
        echo "    🌐 http://localhost:8000"
    else
        echo "  ❌ Web Server"
    fi
fi

# Application Health
echo ""
echo "🏥 Application Health:"

# Check storage permissions
if [ -w storage/logs ]; then
    echo "  ✅ Storage writable"
else
    echo "  ❌ Storage not writable"
fi

# Check log files
if [ -f storage/logs/laravel.log ]; then
    LOG_SIZE=$(du -h storage/logs/laravel.log | cut -f1)
    echo "  📄 Laravel log: $LOG_SIZE"
    
    # Check for recent errors
    RECENT_ERRORS=$(tail -100 storage/logs/laravel.log | grep -c "ERROR\|CRITICAL" || echo "0")
    if [ "$RECENT_ERRORS" -gt 0 ]; then
        echo "    ⚠️ $RECENT_ERRORS recent errors"
    fi
else
    echo "  ℹ️ No Laravel log file"
fi

# Check API health (if available)
echo ""
echo "🌐 API Health:"
if command -v curl &> /dev/null; then
    if curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/health 2>/dev/null | grep -q "200"; then
        echo "  ✅ Health endpoint responding"
    else
        echo "  ❌ Health endpoint not responding"
    fi
else
    echo "  ℹ️ curl not available for API health check"
fi

# Resource Usage
echo ""
echo "💾 Resource Usage:"

# Memory usage
if command -v free &> /dev/null; then
    MEMORY_USAGE=$(free | grep Mem | awk '{printf "%.1f%%", $3/$2 * 100.0}')
    echo "  💾 Memory: $MEMORY_USAGE"
fi

# Disk usage
DISK_USAGE=$(df -h . | tail -1 | awk '{print $5}')
echo "  💽 Disk: $DISK_USAGE"

# CPU load
if [ -f /proc/loadavg ]; then
    LOAD_AVG=$(cat /proc/loadavg | awk '{print $1}')
    echo "  🖥️ Load Average: $LOAD_AVG"
fi

# Recent Activity
echo ""
echo "📈 Recent Activity:"

# Check queue jobs
if [ -f storage/logs/queue.log ]; then
    RECENT_JOBS=$(tail -50 storage/logs/queue.log | grep -c "Processing" || echo "0")
    echo "  🔄 Recent queue jobs: $RECENT_JOBS"
fi

# Check AI requests (if logged)
if [ -f storage/logs/laravel.log ]; then
    RECENT_AI_REQUESTS=$(tail -100 storage/logs/laravel.log | grep -c "AI decision\|Consensus" || echo "0")
    echo "  🤖 Recent AI requests: $RECENT_AI_REQUESTS"
fi

echo ""
echo "📋 Management Commands:"
echo "  Start services: ./start.sh"
echo "  Stop services: ./stop.sh"
echo "  View logs: tail -f storage/logs/laravel.log"
echo "  Clear cache: php artisan cache:clear"
echo "  Run migrations: php artisan migrate"
echo "  Telegram bot: php artisan telegram:bot"
