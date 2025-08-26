#!/bin/bash

# SentinentX Direct Enhanced Deployment (Cache Bypass)
echo "🚀 SentinentX Direct Enhanced Deployment (Cache Bypass)"
echo "====================================================="

# Get latest commit hash
COMMIT_HASH="dbc92aa"
UNIQUE_ID=$(date +%s%N)

echo "Using commit: $COMMIT_HASH"
echo "Cache bypass ID: $UNIQUE_ID"
echo ""

# Direct download of enhanced minimal script
ENHANCED_URL="https://raw.githubusercontent.com/emiryucelweb/SentinentX/$COMMIT_HASH/minimal_deploy.sh"

echo "📡 Downloading enhanced script with cache bypass..."

# Download with maximum cache bypass
curl -sSL --max-time 60 --retry 3 \
     --header "Cache-Control: no-cache, no-store, must-revalidate, max-age=0" \
     --header "Pragma: no-cache" \
     --header "Expires: 0" \
     --header "If-None-Match: *" \
     --header "If-Modified-Since: Thu, 01 Jan 1970 00:00:00 GMT" \
     "${ENHANCED_URL}?nocache=${UNIQUE_ID}&bypass=true&force=1&v=$(date +%s)" | bash

echo ""
echo "✅ Enhanced deployment completed with cache bypass!"
