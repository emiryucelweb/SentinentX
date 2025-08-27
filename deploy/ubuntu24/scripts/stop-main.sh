#!/bin/bash

# SentinentX Main Service Stopper for Ubuntu 24.04 LTS
# Gracefully stops all main service processes

set -euo pipefail
IFS=$'\n\t'

# Configuration
readonly LOG_FILE="/var/log/sentinentx/main-service.log"
readonly PID_FILE="/run/sentinentx/main.pid"
readonly HEALTH_PID_FILE="${PID_FILE}.health"
readonly STOP_TIMEOUT=30

# Logging function
log() {
    echo "$(date -Iseconds) [$$] $*" | tee -a "$LOG_FILE"
}

log "Stopping SentinentX main service..."

# Function to stop process gracefully
stop_process() {
    local pid_file="$1"
    local process_name="$2"
    local timeout="${3:-$STOP_TIMEOUT}"
    
    if [[ -f "$pid_file" ]]; then
        local pid=$(cat "$pid_file")
        
        if kill -0 "$pid" 2>/dev/null; then
            log "Stopping $process_name (PID: $pid)..."
            
            # Send SIGTERM
            kill -TERM "$pid" 2>/dev/null || true
            
            # Wait for graceful shutdown
            local count=0
            while kill -0 "$pid" 2>/dev/null && [[ $count -lt $timeout ]]; do
                sleep 1
                ((count++))
            done
            
            # Force kill if still running
            if kill -0 "$pid" 2>/dev/null; then
                log "Force stopping $process_name..."
                kill -KILL "$pid" 2>/dev/null || true
                sleep 2
            fi
            
            if ! kill -0 "$pid" 2>/dev/null; then
                log "$process_name stopped successfully"
            else
                log "WARNING: $process_name may still be running"
            fi
        else
            log "$process_name was not running"
        fi
        
        # Remove PID file
        rm -f "$pid_file"
    else
        log "$process_name PID file not found"
    fi
}

# Stop health monitoring
stop_process "$HEALTH_PID_FILE" "health monitoring" 10

# Stop main process
stop_process "$PID_FILE" "main process" "$STOP_TIMEOUT"

# Additional cleanup - kill any remaining processes
log "Cleaning up remaining processes..."

# Kill any Laravel serve processes
pgrep -f "artisan serve" | while read -r pid; do
    if [[ -n "$pid" ]]; then
        log "Stopping remaining artisan serve process (PID: $pid)"
        kill -TERM "$pid" 2>/dev/null || true
    fi
done

# Kill any Octane processes
pgrep -f "artisan octane" | while read -r pid; do
    if [[ -n "$pid" ]]; then
        log "Stopping remaining Octane process (PID: $pid)"
        kill -TERM "$pid" 2>/dev/null || true
    fi
done

# Wait a moment for cleanup
sleep 2

log "SentinentX main service stopped"
