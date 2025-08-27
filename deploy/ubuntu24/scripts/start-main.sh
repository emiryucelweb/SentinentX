#!/bin/bash

# SentinentX Main Service Starter for Ubuntu 24.04 LTS
# Handles the main application initialization and health monitoring

set -euo pipefail
IFS=$'\n\t'

# Configuration
readonly INSTALL_DIR="/var/www/sentinentx"
readonly LOG_FILE="/var/log/sentinentx/main-service.log"
readonly PID_FILE="/run/sentinentx/main.pid"
readonly HEALTH_PORT=8080

# Logging function
log() {
    echo "$(date -Iseconds) [$$] $*" | tee -a "$LOG_FILE"
}

# Error handling
handle_error() {
    local exit_code=$1
    local line_number=$2
    log "ERROR: Main service startup failed at line $line_number with exit code $exit_code"
    exit $exit_code
}

trap 'handle_error $? $LINENO' ERR

# Create necessary directories
mkdir -p "$(dirname "$LOG_FILE")" "$(dirname "$PID_FILE")"

log "Starting SentinentX main service..."

# Change to application directory
cd "$INSTALL_DIR"

# Validate environment
if [[ ! -f ".env" ]]; then
    log "ERROR: .env file not found"
    exit 1
fi

# Pre-flight checks
log "Running pre-flight checks..."

# Database connectivity
if ! php artisan migrate:status &>/dev/null; then
    log "ERROR: Database connection failed"
    exit 1
fi

# Redis connectivity  
if ! php artisan tinker --execute="cache()->put('service_check', 'ok'); echo cache()->get('service_check');" 2>/dev/null | grep -q "ok"; then
    log "ERROR: Redis connection failed"
    exit 1
fi

# Start health monitoring endpoint
log "Starting health monitoring endpoint on port $HEALTH_PORT..."
nohup php artisan serve --host=127.0.0.1 --port=$HEALTH_PORT &>/dev/null &
HEALTH_PID=$!
echo "$HEALTH_PID" > "${PID_FILE}.health"

# Start main application processes
log "Starting main application processes..."

# Application cache warming
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start Laravel Octane if available, otherwise use built-in server
if php artisan octane:status &>/dev/null; then
    log "Starting with Laravel Octane..."
    nohup php artisan octane:start --host=127.0.0.1 --port=8000 &>/dev/null &
    MAIN_PID=$!
else
    log "Starting with built-in server..."
    nohup php artisan serve --host=127.0.0.1 --port=8000 &>/dev/null &
    MAIN_PID=$!
fi

# Store main PID
echo "$MAIN_PID" > "$PID_FILE"

# Wait for services to be ready
log "Waiting for services to be ready..."
sleep 5

# Verify services are running
if ! kill -0 "$MAIN_PID" 2>/dev/null; then
    log "ERROR: Main process failed to start"
    exit 1
fi

if ! kill -0 "$HEALTH_PID" 2>/dev/null; then
    log "ERROR: Health monitoring failed to start"
    exit 1
fi

# Health check
if ! curl -s --max-time 5 "http://127.0.0.1:$HEALTH_PORT/api/health" &>/dev/null; then
    log "WARNING: Health endpoint not responding immediately (may be normal during startup)"
fi

log "SentinentX main service started successfully"
log "Main PID: $MAIN_PID"
log "Health PID: $HEALTH_PID"

# Keep script running for systemd
wait "$MAIN_PID"
