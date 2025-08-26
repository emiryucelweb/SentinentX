#!/bin/bash

# 🚀 VDS Quick Fix Application
# ===========================
# Downloads and applies the complete deployment fix on VDS

set -euo pipefail

echo "🚀 APPLYING SENTINENTX DEPLOYMENT FIX ON VDS"
echo "============================================="

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   echo "❌ This script must be run as root (use: sudo su)"
   exit 1
fi

# Go to the SentinentX directory
cd /var/www/sentinentx || {
    echo "❌ SentinentX directory not found at /var/www/sentinentx"
    exit 1
}

echo "📂 Working directory: $(pwd)"

# Download the latest fix script
echo "⬇️ Downloading latest deployment fix..."
curl -sSL --max-time 30 --retry 3 \
    --header "Cache-Control: no-cache, no-store, must-revalidate" \
    --header "Pragma: no-cache" \
    --header "Expires: 0" \
    "https://raw.githubusercontent.com/emiryucelweb/SentinentX/main/fix_complete_deployment.sh?nocache=$(date +%s%N)" \
    -o fix_complete_deployment.sh

# Make it executable
chmod +x fix_complete_deployment.sh

echo "✅ Fix script downloaded successfully"

# Instructions for adding API keys
echo ""
echo "🔑 IMPORTANT: ADD YOUR API KEYS BEFORE RUNNING!"
echo "=============================================="
echo ""
echo "Edit the fix_complete_deployment.sh file and replace:"
echo "- YOUR_OPENAI_API_KEY_HERE    → Your OpenAI API key"
echo "- YOUR_GEMINI_API_KEY_HERE    → Your Gemini API key"  
echo "- YOUR_GROK_API_KEY_HERE      → Your Grok API key"
echo "- YOUR_TELEGRAM_BOT_TOKEN_HERE → Your Telegram bot token"
echo "- YOUR_TELEGRAM_CHAT_ID_HERE  → Your Telegram chat ID"
echo "- YOUR_COINGECKO_API_KEY_HERE → Your CoinGecko API key"
echo ""
echo "Quick commands to set your keys:"
echo 'sed -i "s/YOUR_OPENAI_API_KEY_HERE/your_actual_openai_key/" fix_complete_deployment.sh'
echo 'sed -i "s/YOUR_GEMINI_API_KEY_HERE/your_actual_gemini_key/" fix_complete_deployment.sh'
echo 'sed -i "s/YOUR_GROK_API_KEY_HERE/your_actual_grok_key/" fix_complete_deployment.sh'
echo 'sed -i "s/YOUR_TELEGRAM_BOT_TOKEN_HERE/your_actual_bot_token/" fix_complete_deployment.sh'
echo 'sed -i "s/YOUR_TELEGRAM_CHAT_ID_HERE/your_actual_chat_id/" fix_complete_deployment.sh'
echo 'sed -i "s/YOUR_COINGECKO_API_KEY_HERE/your_actual_coingecko_key/" fix_complete_deployment.sh'
echo ""
echo "Then run the fix:"
echo "./fix_complete_deployment.sh"
echo ""
echo "🎉 DOWNLOAD COMPLETE! Configure your API keys and run the fix!"
