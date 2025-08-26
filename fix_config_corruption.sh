#!/bin/bash

# SentinentX Config Corruption Fix
echo "🔧 SentinentX Config Corruption Fix"
echo "=================================="

# Clean up corrupted config files
echo "🧹 Cleaning corrupted configuration files..."

CORRUPTED_FILES=(
    "/var/log/sentinentx_install_config"
    "/tmp/sentinentx_config"
    "/tmp/sentinentx_deploy.log"
    "/var/log/sentinentx_deploy.log"
)

for file in "${CORRUPTED_FILES[@]}"; do
    if [[ -f "$file" ]]; then
        echo "Removing corrupted file: $file"
        rm -f "$file"
    fi
done

# Clean up any partial installations
echo "🧹 Cleaning partial installations..."
rm -rf /var/www/sentinentx 2>/dev/null || true

# Clear any cached environment variables
unset REPO_URL REPO_URL_PRIMARY REPO_URL_SSH INSTALL_DIR LOG_FILE

echo "✅ Corruption cleanup completed"
echo ""

# Download and run the clean deployment
echo "🚀 Starting clean deployment..."

TEMP_SCRIPT="/tmp/clean_deploy.sh"

# Download the fixed script
curl -sSL --max-time 60 --retry 3 \
     --header "Cache-Control: no-cache, no-store, must-revalidate" \
     --header "Pragma: no-cache" \
     --header "Expires: 0" \
     "https://raw.githubusercontent.com/emiryucelweb/SentinentX/44d28ba/one_command_deploy.sh?clean=$(date +%s%N)" > "$TEMP_SCRIPT"

if [[ ! -f "$TEMP_SCRIPT" ]]; then
    echo "❌ Failed to download clean script"
    exit 1
fi

# Fix any remaining variable issues
echo "🔧 Applying variable fixes..."
sed -i 's/\$REPO_URL[^_]/\$REPO_URL_PRIMARY/g' "$TEMP_SCRIPT"

# Ensure variables are defined at the top
if ! grep -q "REPO_URL_PRIMARY=" "$TEMP_SCRIPT"; then
    sed -i '2i\
\
# Fixed variable definitions\
REPO_URL_PRIMARY="https://github.com/emiryucelweb/SentinentX.git"\
REPO_URL_SSH="git@github.com:emiryucelweb/SentinentX.git"\
REPO_URL_MIRROR="https://gitlab.com/emiryucelweb/SentinentX.git"\
INSTALL_DIR="/var/www/sentinentx"\
LOG_FILE="/tmp/sentinentx_deploy.log"\
' "$TEMP_SCRIPT"
fi

echo "✅ Clean script prepared"
echo ""

# Execute the clean script
echo "🚀 Executing clean deployment..."
bash "$TEMP_SCRIPT"

# Cleanup
rm -f "$TEMP_SCRIPT"

echo ""
echo "✅ Clean deployment completed!"
