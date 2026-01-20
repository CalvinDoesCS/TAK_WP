#!/bin/bash

# Build Script with Swap Memory Management
# This script helps build the application on servers with limited RAM

echo "=== Open Core Business Suite - Memory-Safe Build ==="
echo ""

# Check current memory
echo "📊 Checking system memory..."
free -h 2>/dev/null || echo "Memory check not available"
echo ""

# Check if swap is needed
TOTAL_MEM=$(free -m 2>/dev/null | awk '/^Mem:/{print $2}')
if [ -n "$TOTAL_MEM" ] && [ "$TOTAL_MEM" -lt 8192 ]; then
    echo "⚠️  Total RAM: ${TOTAL_MEM}MB (recommended: 8GB+)"
    echo "💡 Consider enabling swap space if build fails"
    echo ""
fi

# Clear previous build
echo "🧹 Cleaning previous build..."
rm -rf public/build/*
echo "✅ Cleaned"
echo ""

# Build with memory limits
echo "🔨 Starting build with memory optimizations..."
echo "   Using config: vite.config.ultra-lean.js"
echo "   Node memory limit: 4GB"
echo ""

# Run build with reduced memory allocation
NODE_OPTIONS='--max-old-space-size=4096' node ./node_modules/vite/bin/vite.js build --config vite.config.ultra-lean.js

BUILD_EXIT_CODE=$?

if [ $BUILD_EXIT_CODE -eq 0 ]; then
    echo ""
    echo "✅ Build completed successfully!"
    exit 0
else
    echo ""
    echo "❌ Build failed with exit code: $BUILD_EXIT_CODE"

    if [ $BUILD_EXIT_CODE -eq 137 ]; then
        echo ""
        echo "💀 Process was killed by OOM (Out of Memory)"
        echo ""
        echo "📋 Solutions:"
        echo "   1. Enable swap space: sudo fallocate -l 4G /swapfile && sudo chmod 600 /swapfile && sudo mkswap /swapfile && sudo swapon /swapfile"
        echo "   2. Build locally and upload: npm run build (local) then rsync to server"
        echo "   3. Increase server RAM"
        echo "   4. Disable some modules temporarily in modules_statuses.json"
        echo ""
    fi

    exit $BUILD_EXIT_CODE
fi
