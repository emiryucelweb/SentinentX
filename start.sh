#!/bin/bash

# SentinentX Service Starter
# Updated: 2025-01-20

set -e

echo "🚀 Starting SentinentX Services..."

# Check if .env exists
if [ ! -f .env ]; then
    echo "❌ .env file not found. Run ./install.sh first"
    exit 1
fi

# Source environment variables
source .env

# Check database connection
echo "🔍 Checking database connection..."
if ! php artisan migrate:status &> /dev/null; then
    echo "❌ Database connection failed. Check your DB_* settings in .env"
    exit 1
fi

# Check Redis connection
echo "🔍 Checking Redis connection..."
if ! php artisan tinker --execute="cache()->put('test', 'ok'); echo cache()->get('test');" | grep -q "ok"; then
    echo "❌ Redis connection failed. Check your REDIS_* settings in .env"
    exit 1
fi

# Start services in background
echo "🔄 Starting background services..."

# Laravel Queue Worker
if ! pgrep -f "artisan queue:work" > /dev/null; then
    echo "▶️ Starting queue worker..."
    nohup php artisan queue:work --sleep=3 --tries=3 --max-time=3600 > storage/logs/queue.log 2>&1 &
    echo $! > storage/queue.pid
    echo "✅ Queue worker started (PID: $(cat storage/queue.pid))"
else
    echo "✅ Queue worker already running"
fi

# Laravel Scheduler
if ! pgrep -f "artisan schedule:work" > /dev/null; then
    echo "▶️ Starting scheduler..."
    nohup php artisan schedule:work > storage/logs/scheduler.log 2>&1 &
    echo $! > storage/scheduler.pid
    echo "✅ Scheduler started (PID: $(cat storage/scheduler.pid))"
else
    echo "✅ Scheduler already running"
fi

# Horizon (if installed)
if php artisan horizon:status &> /dev/null; then
    if ! pgrep -f "artisan horizon" > /dev/null; then
        echo "▶️ Starting Horizon..."
        nohup php artisan horizon > storage/logs/horizon.log 2>&1 &
        echo $! > storage/horizon.pid
        echo "✅ Horizon started (PID: $(cat storage/horizon.pid))"
    else
        echo "✅ Horizon already running"
    fi
fi

# Start web server (development)
if [ "$APP_ENV" = "local" ] && ! pgrep -f "artisan serve" > /dev/null; then
    echo "▶️ Starting development server..."
    nohup php artisan serve --host=0.0.0.0 --port=8000 > storage/logs/server.log 2>&1 &
    echo $! > storage/server.pid
    echo "✅ Development server started at http://localhost:8000 (PID: $(cat storage/server.pid))"
fi

echo ""
echo "🎉 SentinentX is now running!"
echo ""
echo "📊 Service Status:"
echo "  Queue Worker: $(pgrep -f "artisan queue:work" > /dev/null && echo "✅ Running" || echo "❌ Stopped")"
echo "  Scheduler: $(pgrep -f "artisan schedule:work" > /dev/null && echo "✅ Running" || echo "❌ Stopped")"
if php artisan horizon:status &> /dev/null; then
    echo "  Horizon: $(pgrep -f "artisan horizon" > /dev/null && echo "✅ Running" || echo "❌ Stopped")"
fi
if [ "$APP_ENV" = "local" ]; then
    echo "  Web Server: $(pgrep -f "artisan serve" > /dev/null && echo "✅ Running" || echo "❌ Stopped")"
fi
echo ""
echo "📋 Management Commands:"
echo "  Stop services: ./stop.sh"
echo "  Check status: ./status.sh"
echo "  View logs: tail -f storage/logs/laravel.log"
echo "  Telegram bot: php artisan telegram:bot"
echo "  Run LAB test: php artisan lab:run"
echo ""
echo "🔧 Configuration files:"
echo "  Main config: .env"
echo "  AI settings: config/ai.php"
echo "  Risk profiles: config/risk_profiles.php"
echo "  Trading: config/trading.php"
