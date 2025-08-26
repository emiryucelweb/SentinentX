#!/bin/bash

# SentinentX Trading Activity Monitor
echo "📊 SENTINENTX TRADING ACTIVITY MONITOR"
echo "====================================="

LOG_DIR="/var/log/sentinentx"
PROJECT_DIR="/var/www/sentinentx"

# Function to show live position changes
monitor_positions() {
    echo "📈 MONITORING LIVE POSITION CHANGES"
    echo "=================================="
    
    # Monitor trading log for position events
    tail -f "$LOG_DIR/trading.log" | grep --line-buffered -E "(POSITION_OPENED|POSITION_CLOSED|ENTRY_PRICE|EXIT_PRICE|PNL|PROFIT|LOSS)" | while read line; do
        timestamp=$(echo "$line" | grep -o '\[.*\]' | head -1)
        content=$(echo "$line" | sed 's/\[.*\]//')
        
        if echo "$line" | grep -q "POSITION_OPENED"; then
            echo "🟢 $timestamp POSITION OPENED: $content"
        elif echo "$line" | grep -q "POSITION_CLOSED"; then
            echo "🔴 $timestamp POSITION CLOSED: $content"
        elif echo "$line" | grep -q "PROFIT"; then
            echo "💚 $timestamp PROFIT: $content"
        elif echo "$line" | grep -q "LOSS"; then
            echo "💔 $timestamp LOSS: $content"
        else
            echo "📊 $timestamp $content"
        fi
    done
}

# Function to show AI decisions
monitor_ai_decisions() {
    echo "🤖 MONITORING AI DECISIONS"
    echo "========================="
    
    tail -f "$LOG_DIR/trading.log" | grep --line-buffered -E "(AI_DECISION|CONFIDENCE|CONSENSUS|CHATGPT|GEMINI|GROK)" | while read line; do
        timestamp=$(echo "$line" | grep -o '\[.*\]' | head -1)
        content=$(echo "$line" | sed 's/\[.*\]//')
        
        if echo "$line" | grep -q "CONSENSUS"; then
            echo "🎯 $timestamp CONSENSUS: $content"
        elif echo "$line" | grep -q "CONFIDENCE"; then
            echo "📊 $timestamp CONFIDENCE: $content"
        else
            echo "🤖 $timestamp AI: $content"
        fi
    done
}

# Function to show backtest data in real-time
monitor_backtest() {
    echo "📊 MONITORING BACKTEST DATA"
    echo "=========================="
    
    cd "$PROJECT_DIR"
    
    while true; do
        echo "$(date '+%H:%M:%S') - Checking for new backtest entries..."
        
        # Show latest 5 backtest entries
        php artisan trading:backtest --show-recent --limit=5 --format=detailed 2>/dev/null
        
        echo "---"
        sleep 30  # Check every 30 seconds
    done
}

# Function to show comprehensive stats
show_comprehensive_stats() {
    echo "📊 COMPREHENSIVE TRADING STATISTICS"
    echo "=================================="
    
    cd "$PROJECT_DIR"
    
    while true; do
        clear
        echo "🚀 SENTINENTX COMPREHENSIVE STATS - $(date)"
        echo "=========================================="
        
        echo "📈 ACTIVE POSITIONS:"
        php artisan trading:positions --show-active --format=table 2>/dev/null || echo "No active positions"
        
        echo ""
        echo "💰 TODAY'S PERFORMANCE:"
        php artisan trading:stats --period=today 2>/dev/null || echo "No data for today"
        
        echo ""
        echo "📊 TOTAL PERFORMANCE:"
        php artisan trading:stats --period=total 2>/dev/null || echo "No total data"
        
        echo ""
        echo "🤖 AI DECISION SUMMARY (Last 24h):"
        php artisan ai:decisions --period=24h --summary 2>/dev/null || echo "No AI decisions"
        
        echo ""
        echo "📈 RECENT BACKTEST ENTRIES:"
        php artisan trading:backtest --show-recent --limit=10 --format=compact 2>/dev/null || echo "No backtest data"
        
        echo ""
        echo "🔄 Refreshing in 60 seconds... (Ctrl+C to exit)"
        sleep 60
    done
}

# Interactive menu
show_menu() {
    echo ""
    echo "🎯 MONITORING OPTIONS:"
    echo "====================="
    echo "1. Monitor Live Positions (Real-time)"
    echo "2. Monitor AI Decisions (Real-time)"
    echo "3. Monitor Backtest Data (Every 30s)"
    echo "4. Comprehensive Stats Dashboard (Every 60s)"
    echo "5. View Recent Trading Log"
    echo "6. View Recent Error Log"
    echo "7. Service Status"
    echo "8. Exit"
    echo ""
    read -p "Select option (1-8): " choice
    
    case $choice in
        1)
            monitor_positions
            ;;
        2)
            monitor_ai_decisions
            ;;
        3)
            monitor_backtest
            ;;
        4)
            show_comprehensive_stats
            ;;
        5)
            echo "📄 RECENT TRADING LOG (Last 50 lines):"
            echo "======================================"
            tail -50 "$LOG_DIR/trading.log" 2>/dev/null || echo "No trading log found"
            ;;
        6)
            echo "❌ RECENT ERROR LOG (Last 20 lines):"
            echo "==================================="
            tail -20 "$LOG_DIR/error.log" 2>/dev/null || echo "No error log found"
            ;;
        7)
            echo "🔧 SERVICE STATUS:"
            echo "=================="
            systemctl status sentinentx --no-pager -l
            ;;
        8)
            echo "👋 Exiting monitor..."
            exit 0
            ;;
        *)
            echo "❌ Invalid option. Please try again."
            show_menu
            ;;
    esac
}

# Check if logs exist
if [[ ! -d "$LOG_DIR" ]]; then
    echo "❌ Log directory not found: $LOG_DIR"
    echo "Please run the background starter first!"
    exit 1
fi

# Show current status
echo "🔍 CURRENT STATUS:"
echo "=================="
systemctl is-active sentinentx && echo "✅ SentinentX Service: RUNNING" || echo "❌ SentinentX Service: STOPPED"
echo "📁 Log Directory: $LOG_DIR"
echo "📊 Project Directory: $PROJECT_DIR"

# Show menu
show_menu
