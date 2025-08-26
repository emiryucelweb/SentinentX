#!/bin/bash

# URGENT TELEGRAM FIX - SentinentX
echo "🚨 URGENT TELEGRAM FIX - SENTINENTX"
echo "=================================="

PROJECT_DIR="/var/www/sentinentx"
LOG_DIR="/var/log/sentinentx"

cd "$PROJECT_DIR" || exit 1

echo "🔍 TELEGRAM ISSUE DIAGNOSIS"
echo "=========================="

# Check if service is running
echo "📊 Service Status:"
if systemctl is-active sentinentx &>/dev/null; then
    echo "✅ SentinentX service is running"
else
    echo "❌ SentinentX service is NOT running"
fi

# Check recent errors
echo ""
echo "🔍 Recent Errors (Last 20 lines):"
if [[ -f "$LOG_DIR/error.log" ]]; then
    tail -20 "$LOG_DIR/error.log" | while read line; do
        if echo "$line" | grep -i "telegram" &>/dev/null; then
            echo "🚨 TELEGRAM ERROR: $line"
        elif echo "$line" | grep -i "webhook" &>/dev/null; then
            echo "🚨 WEBHOOK ERROR: $line"
        elif echo "$line" | grep -i "bot" &>/dev/null; then
            echo "🚨 BOT ERROR: $line"
        else
            echo "❌ ERROR: $line"
        fi
    done
else
    echo "❌ No error log found"
fi

# Check Telegram configuration
echo ""
echo "🤖 Telegram Configuration Check:"
if [[ -f ".env" ]]; then
    if grep -q "TELEGRAM_BOT_TOKEN" .env; then
        if grep "TELEGRAM_BOT_TOKEN" .env | grep -q "your-telegram-bot-token"; then
            echo "❌ TELEGRAM_BOT_TOKEN is placeholder value!"
        else
            echo "✅ TELEGRAM_BOT_TOKEN is configured"
        fi
    else
        echo "❌ TELEGRAM_BOT_TOKEN not found in .env"
    fi
    
    if grep -q "TELEGRAM_WEBHOOK_URL" .env; then
        webhook_url=$(grep "TELEGRAM_WEBHOOK_URL" .env | cut -d'=' -f2)
        echo "📡 TELEGRAM_WEBHOOK_URL: $webhook_url"
    else
        echo "❌ TELEGRAM_WEBHOOK_URL not found in .env"
    fi
    
    if grep -q "TELEGRAM_ALLOWED_USERS" .env; then
        echo "✅ TELEGRAM_ALLOWED_USERS is configured"
    else
        echo "❌ TELEGRAM_ALLOWED_USERS not found in .env"
    fi
else
    echo "❌ .env file not found!"
fi

# Check webhook route
echo ""
echo "🔗 Webhook Route Check:"
if [[ -f "routes/web.php" ]]; then
    if grep -q "telegram/webhook" routes/web.php; then
        echo "✅ Telegram webhook route exists"
    else
        echo "❌ Telegram webhook route missing"
    fi
else
    echo "❌ routes/web.php not found"
fi

# Check controller
echo ""
echo "🎮 Controller Check:"
if [[ -f "app/Http/Controllers/TelegramWebhookController.php" ]]; then
    echo "✅ TelegramWebhookController exists"
    
    # Check for common issues
    if grep -q "verifyTelegramWebhook" app/Http/Controllers/TelegramWebhookController.php; then
        echo "✅ Webhook verification method exists"
    else
        echo "❌ Webhook verification method missing"
    fi
    
    if grep -q "handleTelegramUpdate" app/Http/Controllers/TelegramWebhookController.php; then
        echo "✅ Update handler method exists"
    else
        echo "❌ Update handler method missing"
    fi
else
    echo "❌ TelegramWebhookController not found"
fi

# Test webhook URL accessibility
echo ""
echo "🌐 Webhook URL Test:"
webhook_url=$(grep "TELEGRAM_WEBHOOK_URL" .env 2>/dev/null | cut -d'=' -f2 | tr -d '"')
if [[ -n "$webhook_url" ]]; then
    echo "📡 Testing: $webhook_url"
    response=$(curl -s -o /dev/null -w "%{http_code}" "$webhook_url" 2>/dev/null || echo "FAILED")
    if [[ "$response" == "200" ]]; then
        echo "✅ Webhook URL is accessible (200 OK)"
    elif [[ "$response" == "405" ]]; then
        echo "⚠️ Webhook URL accessible but method not allowed (405) - This might be normal for GET requests"
    elif [[ "$response" == "FAILED" ]]; then
        echo "❌ Webhook URL is NOT accessible (connection failed)"
    else
        echo "⚠️ Webhook URL returned HTTP $response"
    fi
else
    echo "❌ No webhook URL configured"
fi

# Check Telegram API connectivity
echo ""
echo "🤖 Telegram API Test:"
bot_token=$(grep "TELEGRAM_BOT_TOKEN" .env 2>/dev/null | cut -d'=' -f2 | tr -d '"')
if [[ -n "$bot_token" ]] && [[ "$bot_token" != "your-telegram-bot-token" ]]; then
    echo "🔑 Testing bot token..."
    api_response=$(curl -s "https://api.telegram.org/bot$bot_token/getMe" 2>/dev/null)
    if echo "$api_response" | grep -q '"ok":true'; then
        bot_username=$(echo "$api_response" | grep -o '"username":"[^"]*"' | cut -d'"' -f4)
        echo "✅ Bot token is valid - Bot username: @$bot_username"
    else
        echo "❌ Bot token is INVALID or Telegram API unreachable"
        echo "Response: $api_response"
    fi
else
    echo "❌ No valid bot token configured"
fi

# Quick fixes
echo ""
echo "🔧 APPLYING QUICK FIXES"
echo "======================="

# Fix 1: Restart service
echo "🔄 Restarting SentinentX service..."
systemctl stop sentinentx 2>/dev/null
sleep 2
systemctl start sentinentx 2>/dev/null
sleep 3

if systemctl is-active sentinentx &>/dev/null; then
    echo "✅ Service restarted successfully"
else
    echo "❌ Service restart failed"
fi

# Fix 2: Clear Laravel cache
echo "🧹 Clearing Laravel caches..."
php artisan config:clear 2>/dev/null && echo "✅ Config cache cleared"
php artisan route:clear 2>/dev/null && echo "✅ Route cache cleared"
php artisan view:clear 2>/dev/null && echo "✅ View cache cleared"

# Fix 3: Test webhook registration
echo "🔗 Testing webhook registration..."
if [[ -n "$bot_token" ]] && [[ "$bot_token" != "your-telegram-bot-token" ]]; then
    if [[ -n "$webhook_url" ]]; then
        echo "📡 Setting webhook: $webhook_url"
        webhook_result=$(curl -s "https://api.telegram.org/bot$bot_token/setWebhook?url=$webhook_url" 2>/dev/null)
        if echo "$webhook_result" | grep -q '"ok":true'; then
            echo "✅ Webhook set successfully"
        else
            echo "❌ Webhook setting failed: $webhook_result"
        fi
    else
        echo "❌ No webhook URL to set"
    fi
else
    echo "❌ No valid bot token for webhook setting"
fi

echo ""
echo "📊 POST-FIX STATUS"
echo "=================="

# Check service status again
if systemctl is-active sentinentx &>/dev/null; then
    echo "✅ Service Status: RUNNING"
else
    echo "❌ Service Status: STOPPED"
    echo "🔍 Checking why service failed..."
    journalctl -u sentinentx --since "2 minutes ago" --no-pager | tail -10
fi

# Check recent log activity
echo ""
echo "📄 Recent Activity (Last 5 lines):"
if [[ -f "$LOG_DIR/trading.log" ]]; then
    tail -5 "$LOG_DIR/trading.log" | while read line; do
        echo "  📄 $line"
    done
else
    echo "  📄 No recent activity"
fi

echo ""
echo "🎯 RECOMMENDED ACTIONS:"
echo "======================"
echo "1. Check bot token configuration: grep TELEGRAM_BOT_TOKEN .env"
echo "2. Verify webhook URL is accessible from internet"
echo "3. Check Telegram allowed users: grep TELEGRAM_ALLOWED_USERS .env"
echo "4. Test manual bot command in Telegram"
echo "5. Monitor logs: tail -f $LOG_DIR/error.log"
echo ""
echo "🚨 If still broken, run: systemctl status sentinentx"
