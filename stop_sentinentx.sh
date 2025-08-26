#!/bin/bash

# SentinentX Complete Stop Script
# Safely stops all SentinentX services and processes

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
STOP_LOG="/var/log/sentinentx_stop.log"

# Logging functions
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1" | tee -a "$STOP_LOG"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1" | tee -a "$STOP_LOG"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1" | tee -a "$STOP_LOG"
}

log_step() {
    echo -e "${BLUE}[STEP]${NC} $1" | tee -a "$STOP_LOG"
}

log_success() {
    echo -e "${CYAN}[SUCCESS]${NC} $1" | tee -a "$STOP_LOG"
}

# Create log file
touch "$STOP_LOG"

# Header
echo "🛑 SentinentX Complete Stop"
echo "==========================="
echo "Timestamp: $(date '+%Y-%m-%d %H:%M:%S')"
echo "Stop Log: $STOP_LOG"
echo ""

# Check if running as root
if [[ $EUID -ne 0 ]]; then
    log_error "This script must be run as root"
    exit 1
fi

# Stop mode selection
STOP_MODE="graceful"
if [[ "${1:-}" == "--force" ]] || [[ "${1:-}" == "-f" ]]; then
    STOP_MODE="force"
    log_warn "Force stop mode enabled"
elif [[ "${1:-}" == "--emergency" ]] || [[ "${1:-}" == "-e" ]]; then
    STOP_MODE="emergency"
    log_warn "Emergency stop mode enabled"
fi

# Function to stop service gracefully
stop_service_graceful() {
    local service_name="$1"
    local timeout="${2:-30}"
    
    log_info "Stopping service gracefully: $service_name"
    
    if systemctl is-active --quiet "$service_name"; then
        # Send SIGTERM first
        systemctl stop "$service_name" &
        local stop_pid=$!
        
        # Wait for graceful shutdown
        local count=0
        while systemctl is-active --quiet "$service_name" && [[ $count -lt $timeout ]]; do
            sleep 1
            ((count++))
        done
        
        # Check if stop completed
        wait $stop_pid 2>/dev/null || true
        
        if systemctl is-active --quiet "$service_name"; then
            log_warn "Service $service_name did not stop gracefully within ${timeout}s"
            return 1
        else
            log_success "Service $service_name stopped gracefully"
            return 0
        fi
    else
        log_info "Service $service_name was not running"
        return 0
    fi
}

# Function to stop service forcefully
stop_service_force() {
    local service_name="$1"
    
    log_warn "Force stopping service: $service_name"
    
    # Kill all related processes
    pkill -f "$service_name" 2>/dev/null || true
    
    # Force stop with systemctl
    systemctl stop "$service_name" 2>/dev/null || true
    systemctl kill "$service_name" 2>/dev/null || true
    
    # Wait a moment and verify
    sleep 3
    
    if systemctl is-active --quiet "$service_name"; then
        log_error "Failed to force stop $service_name"
        return 1
    else
        log_success "Service $service_name force stopped"
        return 0
    fi
}

# Emergency stop function
emergency_stop() {
    log_error "EMERGENCY STOP INITIATED"
    
    # Kill all PHP processes
    log_warn "Killing all PHP processes..."
    pkill -9 -f "php" 2>/dev/null || true
    
    # Kill all Laravel queue workers
    log_warn "Killing Laravel queue workers..."
    pkill -9 -f "queue:work" 2>/dev/null || true
    
    # Kill all SentinentX processes
    log_warn "Killing all SentinentX processes..."
    pkill -9 -f "sentinentx" 2>/dev/null || true
    
    # Force stop all systemd services
    local services=("sentinentx-queue" "sentinentx-telegram" "nginx" "php8.2-fpm")
    for service in "${services[@]}"; do
        systemctl kill "$service" 2>/dev/null || true
        systemctl stop "$service" 2>/dev/null || true
    done
    
    log_success "Emergency stop completed"
}

# Main stop logic
if [[ "$STOP_MODE" == "emergency" ]]; then
    emergency_stop
    exit 0
fi

# Step 1: Stop SentinentX services
log_step "Step 1/4: Stopping SentinentX services..."

SENTINENTX_SERVICES=("sentinentx-telegram" "sentinentx-queue")
failed_stops=()

for service in "${SENTINENTX_SERVICES[@]}"; do
    if [[ "$STOP_MODE" == "force" ]]; then
        if ! stop_service_force "$service"; then
            failed_stops+=("$service")
        fi
    else
        if ! stop_service_graceful "$service" 30; then
            log_warn "Graceful stop failed for $service, trying force stop..."
            if ! stop_service_force "$service"; then
                failed_stops+=("$service")
            fi
        fi
    fi
done

# Step 2: Stop web services
log_step "Step 2/4: Stopping web services..."

WEB_SERVICES=("nginx" "php8.2-fpm")

for service in "${WEB_SERVICES[@]}"; do
    if [[ "$STOP_MODE" == "force" ]]; then
        if ! stop_service_force "$service"; then
            failed_stops+=("$service")
        fi
    else
        if ! stop_service_graceful "$service" 15; then
            log_warn "Graceful stop failed for $service, trying force stop..."
            if ! stop_service_force "$service"; then
                failed_stops+=("$service")
            fi
        fi
    fi
done

# Step 3: Clean up processes
log_step "Step 3/4: Cleaning up remaining processes..."

# Kill any remaining Laravel artisan processes
log_info "Cleaning up Laravel processes..."
pkill -f "artisan queue:work" 2>/dev/null || true
pkill -f "artisan schedule:run" 2>/dev/null || true
pkill -f "artisan telegram:polling" 2>/dev/null || true

# Clean up any SentinentX related processes
log_info "Cleaning up SentinentX processes..."
pkill -f "sentinentx" 2>/dev/null || true

# Wait for processes to terminate
sleep 5

# Step 4: Disable monitoring and cron jobs
log_step "Step 4/4: Disabling monitoring and scheduled tasks..."

# Remove cron jobs
log_info "Removing SentinentX cron jobs..."
(crontab -l 2>/dev/null | grep -v "sentinentx" | crontab -) || true

# Disable systemd services
log_info "Disabling SentinentX services..."
for service in "${SENTINENTX_SERVICES[@]}"; do
    systemctl disable "$service" 2>/dev/null || true
    log_info "Service $service disabled"
done

# Final verification
log_step "Verifying stop status..."

all_stopped=true
for service in "${SENTINENTX_SERVICES[@]}" "${WEB_SERVICES[@]}"; do
    if systemctl is-active --quiet "$service"; then
        log_error "Service $service is still running"
        all_stopped=false
    else
        log_success "Service $service is stopped"
    fi
done

# Check for remaining processes
remaining_processes=$(pgrep -f "sentinentx\|queue:work\|telegram:polling" | wc -l)
if [[ $remaining_processes -gt 0 ]]; then
    log_warn "$remaining_processes SentinentX processes still running"
    log_info "Remaining processes:"
    pgrep -f "sentinentx\|queue:work\|telegram:polling" | xargs ps -p 2>/dev/null || true
    all_stopped=false
fi

# Create stop summary
cat > /root/sentinentx_stop_summary.txt << EOF
🛑 SentinentX Stop Summary
=========================
Stop Date: $(date '+%Y-%m-%d %H:%M:%S')
Stop Mode: $STOP_MODE
Stop Log: $STOP_LOG

📊 Service Status:
$(for service in "${SENTINENTX_SERVICES[@]}" "${WEB_SERVICES[@]}"; do
    status=$(systemctl is-active "$service" 2>/dev/null || echo "inactive")
    echo "- $service: $status"
done)

🔍 Process Check:
- Remaining SentinentX processes: $remaining_processes
- Failed service stops: ${failed_stops[*]:-none}

📝 Notes:
$(if [[ "$all_stopped" == true ]]; then
    echo "- All services stopped successfully"
else
    echo "- Some services or processes may still be running"
    echo "- Check logs: $STOP_LOG"
    echo "- Use --force or --emergency options if needed"
fi)

🔄 To restart SentinentX:
- Start 15-day test: $INSTALL_DIR/start_15day_testnet.sh
- Or restart services: systemctl start sentinentx-queue sentinentx-telegram
EOF

# Final status
echo ""
if [[ "$all_stopped" == true ]]; then
    echo "✅ SentinentX stopped successfully!"
    echo "=================================="
    echo ""
    echo "📊 Status: All services and processes stopped"
    echo "📝 Summary: cat /root/sentinentx_stop_summary.txt"
    echo "🔄 To restart: $INSTALL_DIR/start_15day_testnet.sh"
    echo ""
    log_success "SentinentX stop completed successfully"
else
    echo "⚠️  SentinentX partially stopped"
    echo "=============================="
    echo ""
    echo "📊 Status: Some services may still be running"
    echo "📝 Summary: cat /root/sentinentx_stop_summary.txt"
    echo "🚨 Force stop: $0 --force"
    echo "🆘 Emergency: $0 --emergency"
    echo ""
    log_warn "SentinentX stop completed with warnings"
fi

# Show usage for next time
echo ""
echo "💡 Stop Options:"
echo "  • Normal stop: $0"
echo "  • Force stop: $0 --force"
echo "  • Emergency stop: $0 --emergency"
echo ""
