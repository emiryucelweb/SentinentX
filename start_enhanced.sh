#!/bin/bash

# SentinentX Service Starter - Enhanced Version
# Enhanced with comprehensive error handling, retry logic, and monitoring

set -euo pipefail
IFS=$'\n\t'

# Trap for error handling
trap 'handle_error $? $LINENO' ERR
trap 'cleanup' EXIT

# Configuration
readonly SCRIPT_VERSION="2.0.0"
readonly PROJECT_DIR="$(pwd)"
readonly LOG_DIR="$PROJECT_DIR/storage/logs"
readonly PID_DIR="$PROJECT_DIR/storage/pids"
readonly START_LOG="$LOG_DIR/service_start.log"
readonly RETRY_MAX=3
readonly RETRY_DELAY=5
readonly SERVICE_TIMEOUT=30

# Colors
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly BLUE='\033[0;34m'
readonly CYAN='\033[0;36m'
readonly NC='\033[0m'

# Services configuration
declare -A SERVICES=(
    ["queue"]="php artisan queue:work --sleep=3 --tries=3 --max-time=3600"
    ["scheduler"]="php artisan schedule:work"
    ["horizon"]="php artisan horizon"
    ["websocket"]="php artisan websockets:serve"
)

# Error handler
handle_error() {
    local exit_code=$1
    local line_number=$2
    log_error "Service starter failed at line $line_number with exit code $exit_code"
    echo -e "${RED}❌ Service startup failed${NC}"
    
    # Attempt cleanup of partially started services
    cleanup_failed_services
    
    exit $exit_code
}

# Cleanup function
cleanup() {
    local exit_code=$?
    
    if [[ $exit_code -eq 0 ]]; then
        log_success "Service startup completed successfully"
    else
        log_error "Service startup completed with errors"
        cleanup_failed_services
    fi
    
    return $exit_code
}

# Cleanup failed services
cleanup_failed_services() {
    log_warn "Cleaning up failed service startup attempts"
    
    # Kill any services that may have started
    for service in "${!SERVICES[@]}"; do
        local pid_file="$PID_DIR/${service}.pid"
        if [[ -f "$pid_file" ]]; then
            local pid=$(cat "$pid_file" 2>/dev/null || echo "")
            if [[ -n "$pid" ]] && kill -0 "$pid" 2>/dev/null; then
                log_info "Stopping failed service: $service (PID: $pid)"
                kill "$pid" 2>/dev/null || true
                sleep 2
                kill -9 "$pid" 2>/dev/null || true
            fi
            rm -f "$pid_file"
        fi
    done
}

# Structured logging functions
log_info() {
    local message="$1"
    local timestamp=$(date -Iseconds)
    echo -e "${GREEN}[INFO]${NC} $message"
    echo "[$timestamp] [INFO] $message" >> "$START_LOG"
}

log_warn() {
    local message="$1"
    local timestamp=$(date -Iseconds)
    echo -e "${YELLOW}[WARN]${NC} $message"
    echo "[$timestamp] [WARN] $message" >> "$START_LOG"
}

log_error() {
    local message="$1"
    local timestamp=$(date -Iseconds)
    echo -e "${RED}[ERROR]${NC} $message"
    echo "[$timestamp] [ERROR] $message" >> "$START_LOG"
}

log_success() {
    local message="$1"
    local timestamp=$(date -Iseconds)
    echo -e "${CYAN}[SUCCESS]${NC} $message"
    echo "[$timestamp] [SUCCESS] $message" >> "$START_LOG"
}

# Retry function with exponential backoff
retry_with_backoff() {
    local cmd="$1"
    local description="$2"
    local max_attempts="${3:-$RETRY_MAX}"
    local base_delay="${4:-$RETRY_DELAY}"
    
    local attempt=1
    
    while [[ $attempt -le $max_attempts ]]; do
        log_info "Attempting $description (attempt $attempt/$max_attempts)"
        
        if eval "$cmd"; then
            log_success "$description succeeded"
            return 0
        fi
        
        if [[ $attempt -lt $max_attempts ]]; then
            local delay=$((base_delay * attempt))
            log_warn "$description failed, retrying in ${delay}s..."
            sleep $delay
        fi
        
        ((attempt++))
    done
    
    log_error "$description failed after $max_attempts attempts"
    return 1
}

# Check if service is already running
is_service_running() {
    local service_name="$1"
    local pid_file="$PID_DIR/${service_name}.pid"
    
    if [[ -f "$pid_file" ]]; then
        local pid=$(cat "$pid_file" 2>/dev/null || echo "")
        if [[ -n "$pid" ]] && kill -0 "$pid" 2>/dev/null; then
            return 0
        else
            # Stale PID file
            rm -f "$pid_file"
            return 1
        fi
    fi
    
    return 1
}

# Wait for service to be ready
wait_for_service_ready() {
    local service_name="$1"
    local timeout="${2:-$SERVICE_TIMEOUT}"
    local pid_file="$PID_DIR/${service_name}.pid"
    
    log_info "Waiting for $service_name to be ready (timeout: ${timeout}s)"
    
    local count=0
    while [[ $count -lt $timeout ]]; do
        if [[ -f "$pid_file" ]]; then
            local pid=$(cat "$pid_file" 2>/dev/null || echo "")
            if [[ -n "$pid" ]] && kill -0 "$pid" 2>/dev/null; then
                # Additional health checks based on service type
                case "$service_name" in
                    "queue")
                        # Check if queue worker is processing
                        if pgrep -f "queue:work" &>/dev/null; then
                            log_success "$service_name is ready and processing"
                            return 0
                        fi
                        ;;
                    "scheduler")
                        # Check if scheduler is running
                        if pgrep -f "schedule:work" &>/dev/null; then
                            log_success "$service_name is ready"
                            return 0
                        fi
                        ;;
                    *)
                        log_success "$service_name is ready"
                        return 0
                        ;;
                esac
            fi
        fi
        
        sleep 1
        ((count++))
    done
    
    log_error "$service_name not ready after ${timeout}s"
    return 1
}

# Start individual service
start_service() {
    local service_name="$1"
    local service_command="${SERVICES[$service_name]}"
    
    log_info "Starting service: $service_name"
    
    # Check if already running (idempotent)
    if is_service_running "$service_name"; then
        log_warn "Service $service_name is already running"
        return 0
    fi
    
    # Start service in background
    local pid_file="$PID_DIR/${service_name}.pid"
    local log_file="$LOG_DIR/${service_name}.log"
    
    # Create log file if it doesn't exist
    touch "$log_file"
    
    # Start service with nohup and redirect output
    if nohup $service_command > "$log_file" 2>&1 & then
        local pid=$!
        echo "$pid" > "$pid_file"
        
        log_info "Service $service_name started with PID: $pid"
        
        # Wait for service to be ready
        if wait_for_service_ready "$service_name"; then
            log_success "Service $service_name is running and ready"
            return 0
        else
            log_error "Service $service_name failed to become ready"
            
            # Clean up failed service
            if kill -0 "$pid" 2>/dev/null; then
                kill "$pid" 2>/dev/null || true
                sleep 2
                kill -9 "$pid" 2>/dev/null || true
            fi
            rm -f "$pid_file"
            
            return 1
        fi
    else
        log_error "Failed to start service $service_name"
        return 1
    fi
}

# Check prerequisites
check_prerequisites() {
    log_info "Checking prerequisites"
    
    # Check if .env exists
    if [[ ! -f ".env" ]]; then
        log_error ".env file not found. Run installation first"
        return 1
    fi
    
    # Source environment variables safely
    set +u  # Temporarily allow undefined variables
    source .env
    set -u
    
    # Check required environment variables
    local required_vars=("APP_KEY" "DB_CONNECTION" "REDIS_HOST")
    for var in "${required_vars[@]}"; do
        if [[ -z "${!var:-}" ]]; then
            log_error "Required environment variable $var is not set"
            return 1
        fi
    done
    
    # Check database connection
    log_info "Testing database connection..."
    if ! retry_with_backoff "php artisan migrate:status >/dev/null 2>&1" "database connection test" 2 3; then
        log_error "Database connection failed. Check DB_* settings in .env"
        return 1
    fi
    
    # Check Redis connection
    log_info "Testing Redis connection..."
    if ! retry_with_backoff "php artisan tinker --execute=\"cache()->put('startup_test', 'ok'); echo cache()->get('startup_test');\" 2>/dev/null | grep -q 'ok'" "Redis connection test" 2 3; then
        log_error "Redis connection failed. Check REDIS_* settings in .env"
        return 1
    fi
    
    log_success "All prerequisites satisfied"
    return 0
}

# Create necessary directories
create_directories() {
    log_info "Creating necessary directories"
    
    local dirs=("$LOG_DIR" "$PID_DIR" "storage/framework/cache" "storage/framework/sessions" "storage/framework/views")
    
    for dir in "${dirs[@]}"; do
        if ! mkdir -p "$dir"; then
            log_error "Failed to create directory: $dir"
            return 1
        fi
    done
    
    # Set proper permissions
    if ! chmod -R 775 storage bootstrap/cache 2>/dev/null; then
        log_warn "Could not set storage permissions - may need manual adjustment"
    fi
    
    log_success "Directories created and configured"
}

# Optimize application performance
optimize_application() {
    log_info "Optimizing application performance"
    
    local optimization_commands=(
        "php artisan config:cache"
        "php artisan route:cache"
        "php artisan view:cache"
        "php artisan event:cache"
    )
    
    for cmd in "${optimization_commands[@]}"; do
        if retry_with_backoff "$cmd >/dev/null 2>&1" "optimization: $cmd" 2 2; then
            log_info "Completed: $cmd"
        else
            log_warn "Failed optimization: $cmd"
        fi
    done
    
    log_success "Application optimization completed"
}

# Start all services
start_all_services() {
    log_info "Starting all SentinentX services"
    
    local services_to_start=()
    local failed_services=()
    
    # Determine which services to start
    for service in "${!SERVICES[@]}"; do
        case "$service" in
            "horizon")
                # Only start Horizon if it's available
                if php artisan horizon:status &>/dev/null; then
                    services_to_start+=("$service")
                else
                    log_info "Horizon not available, skipping"
                fi
                ;;
            "websocket")
                # Only start WebSocket if configured
                if grep -q "WEBSOCKET_ENABLED=true" .env 2>/dev/null; then
                    services_to_start+=("$service")
                else
                    log_info "WebSocket not enabled, skipping"
                fi
                ;;
            *)
                services_to_start+=("$service")
                ;;
        esac
    done
    
    # Start services with dependency order
    local start_order=("queue" "scheduler" "horizon" "websocket")
    
    for service in "${start_order[@]}"; do
        if [[ " ${services_to_start[*]} " =~ " ${service} " ]]; then
            if retry_with_backoff "start_service $service" "service startup: $service"; then
                log_success "Service $service started successfully"
            else
                failed_services+=("$service")
                log_error "Failed to start service: $service"
            fi
        fi
    done
    
    # Report results
    local successful_count=$((${#services_to_start[@]} - ${#failed_services[@]}))
    log_info "Service startup summary: $successful_count/${#services_to_start[@]} services started"
    
    if [[ ${#failed_services[@]} -eq 0 ]]; then
        log_success "All services started successfully!"
        return 0
    else
        log_error "Failed to start services: ${failed_services[*]}"
        return 1
    fi
}

# Display service status
show_service_status() {
    echo ""
    echo -e "${BLUE}📊 Service Status:${NC}"
    echo "=================="
    
    for service in "${!SERVICES[@]}"; do
        if is_service_running "$service"; then
            local pid=$(cat "$PID_DIR/${service}.pid" 2>/dev/null || echo "unknown")
            echo -e "  ✅ $service: ${GREEN}Running${NC} (PID: $pid)"
        else
            echo -e "  ❌ $service: ${RED}Stopped${NC}"
        fi
    done
    
    # Additional system checks
    echo ""
    echo -e "${BLUE}🔧 System Status:${NC}"
    echo "=================="
    
    # Check web server (if in development mode)
    if [[ "${APP_ENV:-}" == "local" ]] && pgrep -f "artisan serve" &>/dev/null; then
        local server_pid=$(pgrep -f "artisan serve")
        echo -e "  ✅ Web Server: ${GREEN}Running${NC} (PID: $server_pid)"
        echo -e "    🌐 ${CYAN}http://localhost:8000${NC}"
    fi
    
    # Resource usage
    if command -v free &>/dev/null; then
        local mem_usage=$(free | awk 'NR==2{printf "%.1f%%", $3*100/$2}')
        echo -e "  💾 Memory Usage: $mem_usage"
    fi
    
    local disk_usage=$(df -h . | tail -1 | awk '{print $5}')
    echo -e "  💽 Disk Usage: $disk_usage"
}

# Show management information
show_management_info() {
    echo ""
    echo -e "${BLUE}📋 Management Commands:${NC}"
    echo "======================="
    echo "  Stop services: ./stop.sh"
    echo "  Check status: ./status.sh"
    echo "  View logs: tail -f storage/logs/laravel.log"
    echo "  Monitor queue: php artisan queue:monitor"
    echo "  Restart service: systemctl restart sentinentx"
    echo ""
    echo -e "${BLUE}🔧 Configuration files:${NC}"
    echo "======================="
    echo "  Main config: .env"
    echo "  AI settings: config/ai.php"
    echo "  Risk profiles: config/risk_profiles.php"
    echo "  Trading: config/trading.php"
    echo ""
    echo -e "${BLUE}📊 Monitoring:${NC}"
    echo "=============="
    echo "  Service logs: ls -la storage/logs/"
    echo "  PID files: ls -la storage/pids/"
    echo "  Health check: php artisan sentx:health-check"
}

# Main execution
main() {
    echo -e "${BLUE}🚀 SentinentX Service Starter v$SCRIPT_VERSION${NC}"
    echo "============================================="
    echo ""
    
    # Initialize logging
    mkdir -p "$LOG_DIR" "$PID_DIR"
    touch "$START_LOG"
    
    log_info "Service startup initiated"
    
    # Check prerequisites
    if ! check_prerequisites; then
        log_error "Prerequisites check failed"
        exit 1
    fi
    
    # Create directories
    if ! create_directories; then
        log_error "Directory setup failed"
        exit 1
    fi
    
    # Optimize application
    optimize_application
    
    # Start services
    if start_all_services; then
        echo ""
        echo -e "${GREEN}🎉 SentinentX is now running!${NC}"
        echo "============================="
        
        show_service_status
        show_management_info
        
        log_success "SentinentX startup completed successfully"
        exit 0
    else
        echo ""
        echo -e "${RED}❌ SentinentX startup failed!${NC}"
        echo "=========================="
        
        show_service_status
        
        echo ""
        echo -e "${YELLOW}🔍 Troubleshooting:${NC}"
        echo "==================="
        echo "  • Check logs: tail -f $START_LOG"
        echo "  • Review individual service logs in storage/logs/"
        echo "  • Verify .env configuration"
        echo "  • Check system resources"
        
        log_error "SentinentX startup failed"
        exit 1
    fi
}

# Execute main function
main "$@"
