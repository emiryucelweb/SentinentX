#!/bin/bash

# SentinentX 15-Day Testnet Background Starter
echo "🚀 SENTINENTX 15-DAY TESTNET BACKGROUND STARTER"
echo "=============================================="

PROJECT_DIR="/var/www/sentinentx"
LOG_DIR="/var/log/sentinentx"
PID_FILE="/var/run/sentinentx.pid"

cd "$PROJECT_DIR" || exit 1

# Create log directory
sudo mkdir -p "$LOG_DIR"
sudo chown www-data:www-data "$LOG_DIR"
sudo chmod 755 "$LOG_DIR"

echo "📁 Log Directory: $LOG_DIR"
echo "📊 Project Directory: $PROJECT_DIR"
echo "🆔 PID File: $PID_FILE"

# Function to create systemd service
create_systemd_service() {
    echo "🔧 Creating systemd service..."
    
    sudo tee /etc/systemd/system/sentinentx.service > /dev/null << 'EOF'
[Unit]
Description=SentinentX AI Trading Bot - 15 Day Testnet
After=network.target postgresql.service redis-server.service
Requires=postgresql.service redis-server.service
StartLimitIntervalSec=60
StartLimitBurst=3

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/sentinentx
Environment=PATH=/usr/bin:/usr/local/bin
Environment=LARAVEL_ENV=production

# Main command
ExecStart=/usr/bin/php /var/www/sentinentx/artisan trading:start --testnet --duration=15days

# Logging
StandardOutput=append:/var/log/sentinentx/trading.log
StandardError=append:/var/log/sentinentx/error.log

# Restart policy
Restart=always
RestartSec=10
KillMode=process
TimeoutStopSec=30

# Resource limits
LimitNOFILE=65536
PrivateTmp=true
ProtectSystem=strict
ReadWritePaths=/var/log/sentinentx /var/www/sentinentx/storage

[Install]
WantedBy=multi-user.target
EOF

    echo "✅ Systemd service created"
}

# Function to create log rotation
create_log_rotation() {
    echo "🔄 Setting up log rotation..."
    
    sudo tee /etc/logrotate.d/sentinentx > /dev/null << 'EOF'
/var/log/sentinentx/*.log {
    daily
    missingok
    rotate 15
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload sentinentx || true
    endscript
}
EOF

    echo "✅ Log rotation configured"
}

# Function to create monitoring scripts
create_monitoring_scripts() {
    echo "📊 Creating monitoring scripts..."
    
    # Real-time log viewer
    sudo tee /usr/local/bin/sentinentx-logs > /dev/null << 'EOF'
#!/bin/bash
echo "🔍 SENTINENTX REAL-TIME LOGS"
echo "=========================="
echo "📊 Trading Log:"
tail -f /var/log/sentinentx/trading.log | grep --line-buffered -E "(POSITION|PNL|ENTRY|EXIT|AI_DECISION|PROFIT|LOSS)" | while read line; do
    echo "$(date '+%H:%M:%S') | $line"
done
EOF

    # Position tracker
    sudo tee /usr/local/bin/sentinentx-positions > /dev/null << 'EOF'
#!/bin/bash
echo "📈 SENTINENTX POSITION TRACKER"
echo "=============================="
cd /var/www/sentinentx
php artisan trading:positions --show-active --format=table
EOF

    # Backtest viewer
    sudo tee /usr/local/bin/sentinentx-backtest > /dev/null << 'EOF'
#!/bin/bash
echo "📊 SENTINENTX BACKTEST DATA"
echo "=========================="
cd /var/www/sentinentx
php artisan trading:backtest --show-recent --limit=50 --format=detailed
EOF

    # Stats viewer
    sudo tee /usr/local/bin/sentinentx-stats > /dev/null << 'EOF'
#!/bin/bash
echo "📊 SENTINENTX TRADING STATISTICS"
echo "==============================="
cd /var/www/sentinentx
echo "📈 Today's Performance:"
php artisan trading:stats --period=today
echo ""
echo "📊 Total Performance:"
php artisan trading:stats --period=total
echo ""
echo "🤖 AI Decisions (Last 24h):"
php artisan ai:decisions --period=24h --summary
EOF

    sudo chmod +x /usr/local/bin/sentinentx-*
    echo "✅ Monitoring scripts created"
}

# Function to create dashboard script
create_dashboard() {
    echo "📊 Creating live dashboard..."
    
    sudo tee /usr/local/bin/sentinentx-dashboard > /dev/null << 'EOF'
#!/bin/bash

while true; do
    clear
    echo "🚀 SENTINENTX LIVE DASHBOARD - $(date)"
    echo "========================================="
    
    # Service status
    echo "🔧 SERVICE STATUS:"
    systemctl is-active sentinentx && echo "✅ SentinentX: RUNNING" || echo "❌ SentinentX: STOPPED"
    
    # System resources
    echo ""
    echo "💻 SYSTEM RESOURCES:"
    echo "CPU: $(top -bn1 | grep 'Cpu(s)' | awk '{print $2}' | cut -d'%' -f1)%"
    echo "Memory: $(free | grep Mem | awk '{printf "%.1f%%", $3/$2 * 100.0}')"
    echo "Disk: $(df / | tail -1 | awk '{print $5}')"
    
    # Recent positions
    echo ""
    echo "📈 RECENT POSITIONS (Last 5):"
    cd /var/www/sentinentx
    php artisan trading:positions --show-recent --limit=5 --format=compact 2>/dev/null || echo "No recent positions"
    
    # Today's stats
    echo ""
    echo "💰 TODAY'S PERFORMANCE:"
    php artisan trading:stats --period=today --format=compact 2>/dev/null || echo "No data yet"
    
    # Log tail
    echo ""
    echo "📄 RECENT ACTIVITY:"
    tail -3 /var/log/sentinentx/trading.log 2>/dev/null | cut -c1-80 || echo "No recent activity"
    
    echo ""
    echo "🔄 Refreshing in 10 seconds... (Ctrl+C to exit)"
    sleep 10
done
EOF

    sudo chmod +x /usr/local/bin/sentinentx-dashboard
    echo "✅ Live dashboard created"
}

# Main execution
echo "🚀 Setting up background execution..."

# Create systemd service
create_systemd_service

# Create log rotation
create_log_rotation

# Create monitoring scripts
create_monitoring_scripts

# Create dashboard
create_dashboard

# Enable and start service
echo "🔧 Enabling and starting service..."
sudo systemctl daemon-reload
sudo systemctl enable sentinentx
sudo systemctl start sentinentx

# Check status
echo ""
echo "📊 SERVICE STATUS:"
sudo systemctl status sentinentx --no-pager -l

echo ""
echo "🎉 SENTINENTX 15-DAY TESTNET STARTED!"
echo "===================================="
echo ""
echo "📊 MONITORING COMMANDS:"
echo "• Live Dashboard: sentinentx-dashboard"
echo "• Real-time Logs: sentinentx-logs"
echo "• Position Tracker: sentinentx-positions"
echo "• Backtest Data: sentinentx-backtest"
echo "• Trading Stats: sentinentx-stats"
echo ""
echo "🔧 SERVICE COMMANDS:"
echo "• Check Status: systemctl status sentinentx"
echo "• Stop Service: systemctl stop sentinentx"
echo "• Restart Service: systemctl restart sentinentx"
echo "• View Logs: journalctl -u sentinentx -f"
echo ""
echo "📁 LOG FILES:"
echo "• Trading Log: /var/log/sentinentx/trading.log"
echo "• Error Log: /var/log/sentinentx/error.log"
echo "• System Log: journalctl -u sentinentx"
echo ""
echo "🚀 15-DAY TESTNET IS NOW RUNNING IN BACKGROUND!"
