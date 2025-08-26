#!/bin/bash

# SentinentX Service Stopper
# Updated: 2025-01-20

echo "🛑 Stopping SentinentX Services..."

# Function to stop service by PID file
stop_service() {
    local service_name=$1
    local pid_file=$2
    
    if [ -f "$pid_file" ]; then
        local pid=$(cat "$pid_file")
        if kill -0 "$pid" 2>/dev/null; then
            echo "🔄 Stopping $service_name (PID: $pid)..."
            kill "$pid"
            sleep 2
            
            # Force kill if still running
            if kill -0 "$pid" 2>/dev/null; then
                echo "⚠️ Force stopping $service_name..."
                kill -9 "$pid"
            fi
            
            echo "✅ $service_name stopped"
        else
            echo "⚠️ $service_name PID file exists but process not running"
        fi
        rm -f "$pid_file"
    else
        echo "ℹ️ $service_name PID file not found"
    fi
}

# Stop services by PID files
stop_service "Queue Worker" "storage/queue.pid"
stop_service "Scheduler" "storage/scheduler.pid"
stop_service "Horizon" "storage/horizon.pid"
stop_service "Web Server" "storage/server.pid"

# Stop any remaining Laravel processes
echo "🔍 Checking for remaining Laravel processes..."

# Stop queue workers
QUEUE_PIDS=$(pgrep -f "artisan queue:work" || true)
if [ ! -z "$QUEUE_PIDS" ]; then
    echo "🔄 Stopping remaining queue workers..."
    echo "$QUEUE_PIDS" | xargs kill
    sleep 1
    # Force kill if still running
    QUEUE_PIDS=$(pgrep -f "artisan queue:work" || true)
    if [ ! -z "$QUEUE_PIDS" ]; then
        echo "$QUEUE_PIDS" | xargs kill -9
    fi
    echo "✅ Queue workers stopped"
fi

# Stop scheduler
SCHEDULER_PIDS=$(pgrep -f "artisan schedule:work" || true)
if [ ! -z "$SCHEDULER_PIDS" ]; then
    echo "🔄 Stopping scheduler..."
    echo "$SCHEDULER_PIDS" | xargs kill
    sleep 1
    # Force kill if still running
    SCHEDULER_PIDS=$(pgrep -f "artisan schedule:work" || true)
    if [ ! -z "$SCHEDULER_PIDS" ]; then
        echo "$SCHEDULER_PIDS" | xargs kill -9
    fi
    echo "✅ Scheduler stopped"
fi

# Stop Horizon
HORIZON_PIDS=$(pgrep -f "artisan horizon" || true)
if [ ! -z "$HORIZON_PIDS" ]; then
    echo "🔄 Stopping Horizon..."
    echo "$HORIZON_PIDS" | xargs kill
    sleep 1
    # Force kill if still running
    HORIZON_PIDS=$(pgrep -f "artisan horizon" || true)
    if [ ! -z "$HORIZON_PIDS" ]; then
        echo "$HORIZON_PIDS" | xargs kill -9
    fi
    echo "✅ Horizon stopped"
fi

# Stop development server
SERVER_PIDS=$(pgrep -f "artisan serve" || true)
if [ ! -z "$SERVER_PIDS" ]; then
    echo "🔄 Stopping development server..."
    echo "$SERVER_PIDS" | xargs kill
    sleep 1
    # Force kill if still running
    SERVER_PIDS=$(pgrep -f "artisan serve" || true)
    if [ ! -z "$SERVER_PIDS" ]; then
        echo "$SERVER_PIDS" | xargs kill -9
    fi
    echo "✅ Development server stopped"
fi

# Clear any remaining PID files
rm -f storage/*.pid

echo ""
echo "🎉 All SentinentX services stopped successfully!"
echo ""
echo "📊 Final Status:"
echo "  Queue Worker: $(pgrep -f "artisan queue:work" > /dev/null && echo "❌ Still Running" || echo "✅ Stopped")"
echo "  Scheduler: $(pgrep -f "artisan schedule:work" > /dev/null && echo "❌ Still Running" || echo "✅ Stopped")"
echo "  Horizon: $(pgrep -f "artisan horizon" > /dev/null && echo "❌ Still Running" || echo "✅ Stopped")"
echo "  Web Server: $(pgrep -f "artisan serve" > /dev/null && echo "❌ Still Running" || echo "✅ Stopped")"
echo ""
echo "📋 To restart services: ./start.sh"
