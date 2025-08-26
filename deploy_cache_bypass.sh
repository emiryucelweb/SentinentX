#!/bin/bash

# SentinentX Cache Bypass Deployment
echo "🚀 SentinentX Cache Bypass Deployment"
echo "====================================="

# Get latest commit hash
COMMIT_HASH="e98970a"
UNIQUE_ID=$(date +%s%N)

echo "Using commit: $COMMIT_HASH"
echo "Cache bypass ID: $UNIQUE_ID"
echo ""

# Multiple cache bypass methods
CACHE_BYPASS_URL="https://raw.githubusercontent.com/emiryucelweb/SentinentX/$COMMIT_HASH/one_command_deploy.sh"

echo "📡 Downloading with multiple cache bypass methods..."

# Method 1: Direct commit hash + cache headers + unique param
curl -sSL --max-time 60 --retry 3 \
     --header "Cache-Control: no-cache, no-store, must-revalidate, max-age=0" \
     --header "Pragma: no-cache" \
     --header "Expires: 0" \
     --header "If-None-Match: *" \
     --header "If-Modified-Since: Thu, 01 Jan 1970 00:00:00 GMT" \
     "${CACHE_BYPASS_URL}?nocache=${UNIQUE_ID}&bypass=true&force=1" | bash

echo ""
echo "✅ Deployment completed with cache bypass!"
