#!/bin/bash

# SentinentX Hotfix Deployment - Runtime Variable Fix
echo "🔧 SentinentX Hotfix Deployment (Runtime Variable Fix)"
echo "====================================================="

# Download the script and fix the variable reference on the fly
TEMP_SCRIPT="/tmp/sentinentx_fixed_deploy.sh"
UNIQUE_ID=$(date +%s%N)

echo "📡 Downloading and applying hotfix..."

# Download script with cache bypass
curl -sSL --max-time 60 --retry 3 \
     --header "Cache-Control: no-cache, no-store, must-revalidate" \
     --header "Pragma: no-cache" \
     --header "Expires: 0" \
     "https://raw.githubusercontent.com/emiryucelweb/SentinentX/main/one_command_deploy.sh?v=$UNIQUE_ID" > "$TEMP_SCRIPT"

if [[ ! -f "$TEMP_SCRIPT" ]]; then
    echo "❌ Failed to download script"
    exit 1
fi

echo "🔧 Applying variable reference fixes..."

# Fix all REPO_URL references to REPO_URL_PRIMARY
sed -i 's/\$REPO_URL[^_]/\$REPO_URL_PRIMARY/g' "$TEMP_SCRIPT"

# Add missing variable definitions at the top if they're missing
if ! grep -q "REPO_URL_PRIMARY=" "$TEMP_SCRIPT"; then
    echo "🔧 Adding missing variable definitions..."
    
    # Find the line after the shebang and add variables
    sed -i '2i\
\
# Variable definitions fix\
REPO_URL_PRIMARY="https://github.com/emiryucelweb/SentinentX.git"\
REPO_URL_SSH="git@github.com:emiryucelweb/SentinentX.git"\
REPO_URL_MIRROR="https://gitlab.com/emiryucelweb/SentinentX.git"\
' "$TEMP_SCRIPT"
fi

echo "✅ Hotfix applied, executing fixed script..."
echo ""

# Execute the fixed script
bash "$TEMP_SCRIPT"

# Cleanup
rm -f "$TEMP_SCRIPT"

echo ""
echo "✅ Hotfix deployment completed!"
