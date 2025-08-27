#!/bin/bash

# SentinentX Control Panel - Enhanced Version
# Enhanced with error handling, retry logic, and structured logging

set -euo pipefail
IFS=$'\n\t'

# Trap for error handling
trap 'handle_error $? $LINENO' ERR
trap 'cleanup' EXIT

# Configuration
PROJECT_DIR="/var/www/sentinentx"
LOG_DIR="/var/log/sentinentx"
CONTROL_LOG="/var/log/sentinentx_control.log"
RETRY_MAX=3
RETRY_DELAY=5

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

# Error handler
handle_error() {
    local exit_code=$1
    local line_number=$2
    log_error "Control script failed at line $line_number with exit code $exit_code"
    echo -e "${RED}❌ Control panel operation failed${NC}"
    exit $exit_code
}

# Cleanup function
cleanup() {
    # Perform any necessary cleanup
    return 0
}

# Structured logging functions
log_info() {
    local message="$1"
    echo -e "${GREEN}[INFO]${NC} $message" | tee -a "$CONTROL_LOG"
}

log_warn() {
    local message="$1"
    echo -e "${YELLOW}[WARN]${NC} $message" | tee -a "$CONTROL_LOG"
}

log_error() {
    local message="$1"
    echo -e "${RED}[ERROR]${NC} $message" | tee -a "$CONTROL_LOG"
}

log_success() {
    local message="$1"
    echo -e "${CYAN}[SUCCESS]${NC} $message" | tee -a "$CONTROL_LOG"
}

# Retry function with exponential backoff
retry_with_backoff() {
    local cmd="$1"
    local description="$2"
    local attempt=1
    
    while [[ $attempt -le $RETRY_MAX ]]; do
        log_info "Attempting $description (attempt $attempt/$RETRY_MAX)"
        
        if eval "$cmd"; then
            log_success "$description succeeded"
            return 0
        fi
        
        if [[ $attempt -lt $RETRY_MAX ]]; then
            local delay=$((RETRY_DELAY * attempt))
            log_warn "$description failed, retrying in ${delay}s..."
            sleep $delay
        fi
        
        ((attempt++))
    done
    
    log_error "$description failed after $RETRY_MAX attempts"
    return 1
}

# Service health check
check_service_health() {
    local service_name="$1"
    
    if systemctl is-active --quiet "$service_name" 2>/dev/null; then
        return 0
    else
        return 1
    fi
}

# Wait for service to be ready
wait_for_service() {
    local service_name="$1"
    local timeout="${2:-30}"
    local count=0
    
    log_info "Waiting for $service_name to be ready (timeout: ${timeout}s)"
    
    while [[ $count -lt $timeout ]]; do
        if check_service_health "$service_name"; then
            log_success "$service_name is ready"
            return 0
        fi
        sleep 1
        ((count++))
    done
    
    log_error "$service_name not ready after ${timeout}s"
    return 1
}

# Initialize log file
mkdir -p "$(dirname "$CONTROL_LOG")"
touch "$CONTROL_LOG"

# Function to show status with health checks
show_status() {
    echo -e "${BLUE}🔍 SENTINENTX STATUS${NC}"
    echo "==================="
    
    local overall_health="healthy"
    
    # Service status with health checks
    log_info "Checking service status..."
    
    if check_service_health "sentinentx" 2>/dev/null; then
        echo -e "✅ Service Status: ${GREEN}RUNNING${NC}"
        local uptime=$(systemctl show sentinentx --property=ActiveEnterTimestamp --value 2>/dev/null | cut -d' ' -f2-3)
        [[ -n "$uptime" ]] && echo "🕐 Running since: $uptime"
    else
        echo -e "❌ Service Status: ${RED}STOPPED${NC}"
        overall_health="unhealthy"
    fi
    
    # Process info with error handling
    if pgrep -f "trading:start" &>/dev/null; then
        echo -e "✅ Trading Process: ${GREEN}ACTIVE${NC}"
        local pid=$(pgrep -f 'trading:start' 2>/dev/null | head -1)
        [[ -n "$pid" ]] && echo "🆔 Process ID: $pid"
    else
        echo -e "❌ Trading Process: ${RED}NOT FOUND${NC}"
        overall_health="unhealthy"
    fi
    
    # Log sizes with error handling
    if [[ -f "$LOG_DIR/trading.log" ]]; then
        local log_size=$(du -h "$LOG_DIR/trading.log" 2>/dev/null | cut -f1)
        echo "📄 Trading Log: $log_size"
    else
        echo "📄 Trading Log: NOT FOUND"
    fi
    
    if [[ -f "$LOG_DIR/error.log" ]]; then
        local error_size=$(du -h "$LOG_DIR/error.log" 2>/dev/null | cut -f1)
        echo "❌ Error Log: $error_size"
        
        # Check for recent errors
        local recent_errors=$(tail -100 "$LOG_DIR/error.log" 2>/dev/null | grep -c "ERROR\|CRITICAL" || echo "0")
        if [[ $recent_errors -gt 0 ]]; then
            echo -e "⚠️ Recent errors: ${YELLOW}$recent_errors${NC}"
            overall_health="warning"
        fi
    else
        echo "❌ Error Log: NOT FOUND"
    fi
    
    # System resource checks
    echo ""
    echo "💾 System Resources:"
    
    # Memory usage
    if command -v free &>/dev/null; then
        local mem_usage=$(free | awk 'NR==2{printf "%.1f%%", $3*100/$2}')
        echo "💾 Memory: $mem_usage"
        
        # Warning on high memory usage
        if (( $(echo "$mem_usage > 85.0" | bc -l 2>/dev/null || echo "0") )); then
            echo -e "⚠️ ${YELLOW}High memory usage detected${NC}"
            overall_health="warning"
        fi
    fi
    
    # Disk usage
    local disk_usage=$(df -h "$PROJECT_DIR" 2>/dev/null | awk 'NR==2 {print $5}' | sed 's/%//')
    if [[ -n "$disk_usage" ]]; then
        echo "💽 Disk: ${disk_usage}%"
        
        if [[ $disk_usage -gt 80 ]]; then
            echo -e "⚠️ ${YELLOW}High disk usage detected${NC}"
            overall_health="warning"
        fi
    fi
    
    # Recent activity with safe log reading
    echo ""
    echo "📊 RECENT ACTIVITY (Last 3 lines):"
    if [[ -f "$LOG_DIR/trading.log" ]]; then
        tail -3 "$LOG_DIR/trading.log" 2>/dev/null | while IFS= read -r line; do
            echo "  📄 $line"
        done || echo "  📄 Unable to read recent activity"
    else
        echo "  📄 No recent activity"
    fi
    
    # Overall health status
    echo ""
    case "$overall_health" in
        "healthy")
            echo -e "🟢 Overall Status: ${GREEN}HEALTHY${NC}"
            ;;
        "warning")
            echo -e "🟡 Overall Status: ${YELLOW}WARNING${NC}"
            ;;
        "unhealthy")
            echo -e "🔴 Overall Status: ${RED}UNHEALTHY${NC}"
            ;;
    esac
}

# Function to start service with retry logic
start_service() {
    echo -e "${BLUE}🚀 STARTING SENTINENTX SERVICE${NC}"
    echo "=============================="
    
    # Check if already running (idempotent)
    if check_service_health "sentinentx"; then
        log_warn "Service is already running!"
        show_status
        return 0
    fi
    
    log_info "Starting systemd service..."
    
    # Start with retry logic
    if retry_with_backoff "systemctl start sentinentx" "service start"; then
        # Wait for service to be ready
        if wait_for_service "sentinentx" 30; then
            log_success "Service started successfully!"
            show_status
        else
            log_error "Service started but not responding properly"
            log_info "Checking service logs..."
            journalctl -u sentinentx --since "2 minutes ago" --no-pager | tail -10
            return 1
        fi
    else
        log_error "Failed to start service after $RETRY_MAX attempts"
        log_info "Checking service logs..."
        journalctl -u sentinentx --since "2 minutes ago" --no-pager | tail -10
        return 1
    fi
}

# Function to stop service with retry logic
stop_service() {
    echo -e "${BLUE}🛑 STOPPING SENTINENTX SERVICE${NC}"
    echo "=============================="
    
    # Check if not running (idempotent)
    if ! check_service_health "sentinentx"; then
        log_warn "Service is not running!"
        return 0
    fi
    
    log_info "Stopping systemd service..."
    
    # Stop with retry logic
    if retry_with_backoff "systemctl stop sentinentx" "service stop"; then
        # Verify it stopped
        if ! check_service_health "sentinentx"; then
            log_success "Service stopped successfully!"
        else
            log_warn "Service may still be running. Force stopping..."
            if retry_with_backoff "systemctl kill sentinentx" "force stop"; then
                sleep 2
                log_success "Service force stopped!"
            else
                log_error "Failed to force stop service"
                return 1
            fi
        fi
    else
        log_error "Failed to stop service gracefully, attempting force stop..."
        if retry_with_backoff "systemctl kill sentinentx" "force stop"; then
            sleep 2
            log_success "Service force stopped!"
        else
            log_error "Failed to stop service after all attempts"
            return 1
        fi
    fi
}

# Function to restart service
restart_service() {
    echo -e "${BLUE}🔄 RESTARTING SENTINENTX SERVICE${NC}"
    echo "==============================="
    
    log_info "Stopping service..."
    if ! stop_service; then
        log_error "Failed to stop service for restart"
        return 1
    fi
    
    sleep 3
    
    log_info "Starting service..."
    if ! start_service; then
        log_error "Failed to start service after restart"
        return 1
    fi
    
    log_success "Service restarted successfully!"
}

# Function to view logs with safe access
view_logs() {
    echo -e "${BLUE}📄 LOG VIEWER${NC}"
    echo "============="
    echo "1. Real-time Trading Log"
    echo "2. Real-time Error Log"
    echo "3. System Service Log"
    echo "4. Last 50 Trading Log Lines"
    echo "5. Last 20 Error Log Lines"
    echo "6. Control Panel Log"
    echo "7. Back to main menu"
    echo ""
    read -r -p "Select log option (1-7): " log_choice
    
    case $log_choice in
        1)
            echo -e "${BLUE}📄 Real-time Trading Log (Ctrl+C to exit):${NC}"
            echo "=========================================="
            if [[ -f "$LOG_DIR/trading.log" ]]; then
                tail -f "$LOG_DIR/trading.log" 2>/dev/null || {
                    log_error "Cannot access trading log"
                    return 1
                }
            else
                log_warn "Trading log not found"
            fi
            ;;
        2)
            echo -e "${BLUE}❌ Real-time Error Log (Ctrl+C to exit):${NC}"
            echo "======================================="
            if [[ -f "$LOG_DIR/error.log" ]]; then
                tail -f "$LOG_DIR/error.log" 2>/dev/null || {
                    log_error "Cannot access error log"
                    return 1
                }
            else
                log_warn "Error log not found"
            fi
            ;;
        3)
            echo -e "${BLUE}🔧 System Service Log (Ctrl+C to exit):${NC}"
            echo "======================================"
            journalctl -u sentinentx -f 2>/dev/null || {
                log_error "Cannot access service log"
                return 1
            }
            ;;
        4)
            echo -e "${BLUE}📄 Last 50 Trading Log Lines:${NC}"
            echo "============================"
            if [[ -f "$LOG_DIR/trading.log" ]]; then
                tail -50 "$LOG_DIR/trading.log" 2>/dev/null || log_error "Cannot read trading log"
            else
                log_warn "Trading log not found"
            fi
            ;;
        5)
            echo -e "${BLUE}❌ Last 20 Error Log Lines:${NC}"
            echo "========================="
            if [[ -f "$LOG_DIR/error.log" ]]; then
                tail -20 "$LOG_DIR/error.log" 2>/dev/null || log_error "Cannot read error log"
            else
                log_warn "Error log not found"
            fi
            ;;
        6)
            echo -e "${BLUE}🎮 Control Panel Log:${NC}"
            echo "==================="
            if [[ -f "$CONTROL_LOG" ]]; then
                tail -30 "$CONTROL_LOG" 2>/dev/null || log_error "Cannot read control log"
            else
                log_warn "Control log not found"
            fi
            ;;
        7)
            return 0
            ;;
        *)
            log_error "Invalid option"
            view_logs
            ;;
    esac
}

# Function to emergency stop with enhanced safety
emergency_stop() {
    echo -e "${RED}🚨 EMERGENCY STOP${NC}"
    echo "================="
    echo -e "⚠️ This will force stop all SentinentX processes!"
    echo -e "⚠️ Only use this if normal stop methods fail!"
    echo ""
    read -r -p "Are you sure? Type 'YES' to confirm: " confirm
    
    if [[ "$confirm" == "YES" ]]; then
        log_warn "Emergency stop initiated by user"
        
        log_info "Force stopping service..."
        systemctl stop sentinentx 2>/dev/null || true
        systemctl kill sentinentx 2>/dev/null || true
        
        log_info "Killing remaining processes..."
        pkill -f "trading:start" 2>/dev/null || true
        pkill -f "sentinentx" 2>/dev/null || true
        
        # Wait for processes to die
        sleep 3
        
        # Verify emergency stop
        if ! pgrep -f "trading:start\|sentinentx" &>/dev/null; then
            log_success "Emergency stop completed!"
        else
            log_warn "Some processes may still be running"
            pgrep -f "trading:start\|sentinentx" | while read -r pid; do
                local cmd=$(ps -p "$pid" -o comm= 2>/dev/null || echo "unknown")
                log_warn "Still running: PID $pid ($cmd)"
            done
        fi
    else
        log_info "Emergency stop cancelled"
    fi
}

# Function to backup logs with rotation
backup_logs() {
    echo -e "${BLUE}💾 BACKUP LOGS${NC}"
    echo "=============="
    
    local backup_dir="/var/backups/sentinentx"
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local backup_file="$backup_dir/sentinentx_logs_$timestamp.tar.gz"
    
    log_info "Creating backup directory..."
    mkdir -p "$backup_dir" || {
        log_error "Cannot create backup directory"
        return 1
    }
    
    if [[ -d "$LOG_DIR" ]]; then
        log_info "Creating backup..."
        
        # Create backup with retry logic
        if retry_with_backoff "tar -czf '$backup_file' -C '$LOG_DIR' ." "log backup"; then
            log_success "Logs backed up to: $backup_file"
            
            # Show backup size
            if [[ -f "$backup_file" ]]; then
                local backup_size=$(du -h "$backup_file" | cut -f1)
                echo "📊 Backup size: $backup_size"
            fi
            
            # Rotate old backups (keep last 10)
            log_info "Rotating old backups (keeping last 10)..."
            find "$backup_dir" -name "sentinentx_logs_*.tar.gz" -type f | sort -r | tail -n +11 | xargs rm -f 2>/dev/null || true
            
            local backup_count=$(find "$backup_dir" -name "sentinentx_logs_*.tar.gz" | wc -l)
            log_info "Total backups: $backup_count"
        else
            log_error "Backup failed after $RETRY_MAX attempts"
            return 1
        fi
    else
        log_error "Log directory not found: $LOG_DIR"
        return 1
    fi
}

# Main menu with error handling
show_menu() {
    echo ""
    echo -e "${PURPLE}🎮 CONTROL OPTIONS:${NC}"
    echo "=================="
    echo "1. Show Status"
    echo "2. Start Service"
    echo "3. Stop Service"
    echo "4. Restart Service"
    echo "5. View Logs"
    echo "6. Emergency Stop"
    echo "7. Backup Logs"
    echo "8. Open Monitor (New Window)"
    echo "9. Health Check"
    echo "10. Exit"
    echo ""
    read -r -p "Select option (1-10): " choice
    
    case $choice in
        1)
            show_status || log_error "Status check failed"
            show_menu
            ;;
        2)
            start_service || log_error "Service start failed"
            show_menu
            ;;
        3)
            stop_service || log_error "Service stop failed"
            show_menu
            ;;
        4)
            restart_service || log_error "Service restart failed"
            show_menu
            ;;
        5)
            view_logs || log_error "Log viewing failed"
            show_menu
            ;;
        6)
            emergency_stop || log_error "Emergency stop failed"
            show_menu
            ;;
        7)
            backup_logs || log_error "Log backup failed"
            show_menu
            ;;
        8)
            echo -e "${BLUE}🖥️ Opening monitor in new session...${NC}"
            local monitor_script="/usr/local/bin/monitor_trading_activity.sh"
            
            if [[ -f "$monitor_script" ]]; then
                if command -v screen &>/dev/null; then
                    screen -S sentinentx-monitor "$monitor_script" &
                    log_success "Monitor started in screen session 'sentinentx-monitor'"
                elif command -v tmux &>/dev/null; then
                    tmux new-session -d -s sentinentx-monitor "$monitor_script"
                    log_success "Monitor started in tmux session 'sentinentx-monitor'"
                    echo "🔗 Attach with: tmux attach -t sentinentx-monitor"
                else
                    log_error "Screen or tmux not available. Install with: sudo apt install screen tmux"
                fi
            else
                log_error "Monitor script not found: $monitor_script"
            fi
            show_menu
            ;;
        9)
            echo -e "${BLUE}🏥 Running health check...${NC}"
            if command -v php &>/dev/null && [[ -d "$PROJECT_DIR" ]]; then
                (cd "$PROJECT_DIR" && php artisan sentx:health-check 2>/dev/null) || log_error "Health check failed"
            else
                log_error "Cannot run health check - PHP or project directory not found"
            fi
            show_menu
            ;;
        10)
            log_info "User requested exit"
            echo -e "${GREEN}👋 Goodbye!${NC}"
            exit 0
            ;;
        *)
            log_error "Invalid option selected: $choice"
            echo -e "${RED}❌ Invalid option. Please try again.${NC}"
            show_menu
            ;;
    esac
}

# Permission check with helpful message
if [[ $EUID -ne 0 ]] && [[ "${1:-}" != "status" ]]; then
    echo -e "${YELLOW}⚠️ Some operations require root privileges${NC}"
    echo -e "${BLUE}💡 Run with 'sudo' for full functionality${NC}"
    echo ""
fi

# Main execution
log_info "SentinentX Control Panel started"

# Initial status check
show_status

# Show menu
show_menu
