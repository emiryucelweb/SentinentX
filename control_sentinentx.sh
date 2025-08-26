#!/bin/bash

# SentinentX Control Panel
echo "🎮 SENTINENTX CONTROL PANEL"
echo "=========================="

PROJECT_DIR="/var/www/sentinentx"
LOG_DIR="/var/log/sentinentx"

# Function to show status
show_status() {
    echo "🔍 SENTINENTX STATUS"
    echo "==================="
    
    # Service status
    if systemctl is-active sentinentx &>/dev/null; then
        echo "✅ Service Status: RUNNING"
        echo "🕐 Running since: $(systemctl show sentinentx --property=ActiveEnterTimestamp --value | cut -d' ' -f2-3)"
    else
        echo "❌ Service Status: STOPPED"
    fi
    
    # Process info
    if pgrep -f "trading:start" &>/dev/null; then
        echo "✅ Trading Process: ACTIVE"
        echo "🆔 Process ID: $(pgrep -f 'trading:start')"
    else
        echo "❌ Trading Process: NOT FOUND"
    fi
    
    # Log sizes
    if [[ -f "$LOG_DIR/trading.log" ]]; then
        echo "📄 Trading Log: $(du -h $LOG_DIR/trading.log | cut -f1)"
    else
        echo "📄 Trading Log: NOT FOUND"
    fi
    
    if [[ -f "$LOG_DIR/error.log" ]]; then
        echo "❌ Error Log: $(du -h $LOG_DIR/error.log | cut -f1)"
    else
        echo "❌ Error Log: NOT FOUND"
    fi
    
    # Recent activity
    echo ""
    echo "📊 RECENT ACTIVITY (Last 3 lines):"
    if [[ -f "$LOG_DIR/trading.log" ]]; then
        tail -3 "$LOG_DIR/trading.log" | while read line; do
            echo "  📄 $line"
        done
    else
        echo "  📄 No recent activity"
    fi
}

# Function to start service
start_service() {
    echo "🚀 STARTING SENTINENTX SERVICE"
    echo "=============================="
    
    if systemctl is-active sentinentx &>/dev/null; then
        echo "⚠️ Service is already running!"
        return
    fi
    
    echo "🔧 Starting systemd service..."
    sudo systemctl start sentinentx
    
    sleep 3
    
    if systemctl is-active sentinentx &>/dev/null; then
        echo "✅ Service started successfully!"
        show_status
    else
        echo "❌ Failed to start service. Checking logs..."
        sudo journalctl -u sentinentx --since "1 minute ago" --no-pager
    fi
}

# Function to stop service
stop_service() {
    echo "🛑 STOPPING SENTINENTX SERVICE"
    echo "=============================="
    
    if ! systemctl is-active sentinentx &>/dev/null; then
        echo "⚠️ Service is not running!"
        return
    fi
    
    echo "🔧 Stopping systemd service..."
    sudo systemctl stop sentinentx
    
    sleep 3
    
    if ! systemctl is-active sentinentx &>/dev/null; then
        echo "✅ Service stopped successfully!"
    else
        echo "⚠️ Service may still be running. Force stopping..."
        sudo systemctl kill sentinentx
        sleep 2
        echo "✅ Service force stopped!"
    fi
}

# Function to restart service
restart_service() {
    echo "🔄 RESTARTING SENTINENTX SERVICE"
    echo "==============================="
    
    echo "🛑 Stopping service..."
    sudo systemctl stop sentinentx
    sleep 3
    
    echo "🚀 Starting service..."
    sudo systemctl start sentinentx
    sleep 3
    
    if systemctl is-active sentinentx &>/dev/null; then
        echo "✅ Service restarted successfully!"
        show_status
    else
        echo "❌ Failed to restart service. Checking logs..."
        sudo journalctl -u sentinentx --since "1 minute ago" --no-pager
    fi
}

# Function to view logs
view_logs() {
    echo "📄 LOG VIEWER"
    echo "============="
    echo "1. Real-time Trading Log"
    echo "2. Real-time Error Log"
    echo "3. System Service Log"
    echo "4. Last 50 Trading Log Lines"
    echo "5. Last 20 Error Log Lines"
    echo "6. Back to main menu"
    echo ""
    read -p "Select log option (1-6): " log_choice
    
    case $log_choice in
        1)
            echo "📄 Real-time Trading Log (Ctrl+C to exit):"
            echo "=========================================="
            tail -f "$LOG_DIR/trading.log" 2>/dev/null || echo "Trading log not found"
            ;;
        2)
            echo "❌ Real-time Error Log (Ctrl+C to exit):"
            echo "======================================="
            tail -f "$LOG_DIR/error.log" 2>/dev/null || echo "Error log not found"
            ;;
        3)
            echo "🔧 System Service Log (Ctrl+C to exit):"
            echo "======================================"
            sudo journalctl -u sentinentx -f
            ;;
        4)
            echo "📄 Last 50 Trading Log Lines:"
            echo "============================"
            tail -50 "$LOG_DIR/trading.log" 2>/dev/null || echo "Trading log not found"
            ;;
        5)
            echo "❌ Last 20 Error Log Lines:"
            echo "========================="
            tail -20 "$LOG_DIR/error.log" 2>/dev/null || echo "Error log not found"
            ;;
        6)
            return
            ;;
        *)
            echo "❌ Invalid option"
            view_logs
            ;;
    esac
}

# Function to emergency stop
emergency_stop() {
    echo "🚨 EMERGENCY STOP"
    echo "================="
    echo "⚠️ This will force stop all SentinentX processes!"
    read -p "Are you sure? (yes/no): " confirm
    
    if [[ $confirm == "yes" ]]; then
        echo "🛑 Force stopping service..."
        sudo systemctl stop sentinentx
        sudo systemctl kill sentinentx
        
        echo "🔍 Killing remaining processes..."
        sudo pkill -f "trading:start" || echo "No trading processes found"
        sudo pkill -f "sentinentx" || echo "No sentinentx processes found"
        
        echo "✅ Emergency stop completed!"
    else
        echo "❌ Emergency stop cancelled"
    fi
}

# Function to backup logs
backup_logs() {
    echo "💾 BACKUP LOGS"
    echo "=============="
    
    BACKUP_DIR="/var/backups/sentinentx"
    TIMESTAMP=$(date +%Y%m%d_%H%M%S)
    
    sudo mkdir -p "$BACKUP_DIR"
    
    if [[ -d "$LOG_DIR" ]]; then
        echo "📦 Creating backup..."
        sudo tar -czf "$BACKUP_DIR/sentinentx_logs_$TIMESTAMP.tar.gz" -C "$LOG_DIR" .
        echo "✅ Logs backed up to: $BACKUP_DIR/sentinentx_logs_$TIMESTAMP.tar.gz"
        
        # Show backup size
        echo "📊 Backup size: $(du -h $BACKUP_DIR/sentinentx_logs_$TIMESTAMP.tar.gz | cut -f1)"
    else
        echo "❌ Log directory not found: $LOG_DIR"
    fi
}

# Main menu
show_menu() {
    echo ""
    echo "🎮 CONTROL OPTIONS:"
    echo "=================="
    echo "1. Show Status"
    echo "2. Start Service"
    echo "3. Stop Service"
    echo "4. Restart Service"
    echo "5. View Logs"
    echo "6. Emergency Stop"
    echo "7. Backup Logs"
    echo "8. Open Monitor (New Window)"
    echo "9. Exit"
    echo ""
    read -p "Select option (1-9): " choice
    
    case $choice in
        1)
            show_status
            show_menu
            ;;
        2)
            start_service
            show_menu
            ;;
        3)
            stop_service
            show_menu
            ;;
        4)
            restart_service
            show_menu
            ;;
        5)
            view_logs
            show_menu
            ;;
        6)
            emergency_stop
            show_menu
            ;;
        7)
            backup_logs
            show_menu
            ;;
        8)
            echo "🖥️ Opening monitor in new session..."
            if command -v screen &> /dev/null; then
                screen -S sentinentx-monitor /usr/local/bin/monitor_trading_activity.sh
            elif command -v tmux &> /dev/null; then
                tmux new-session -d -s sentinentx-monitor '/usr/local/bin/monitor_trading_activity.sh'
                echo "✅ Monitor started in tmux session 'sentinentx-monitor'"
                echo "🔗 Attach with: tmux attach -t sentinentx-monitor"
            else
                echo "❌ Screen or tmux not available. Install with:"
                echo "   sudo apt install screen tmux"
            fi
            show_menu
            ;;
        9)
            echo "👋 Goodbye!"
            exit 0
            ;;
        *)
            echo "❌ Invalid option. Please try again."
            show_menu
            ;;
    esac
}

# Check if running as root for some operations
if [[ $EUID -ne 0 ]] && [[ "$1" != "status" ]]; then
    echo "⚠️ Some operations require root privileges"
    echo "💡 Run with 'sudo' for full functionality"
fi

# Initial status check
show_status
show_menu
