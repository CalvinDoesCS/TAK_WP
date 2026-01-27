#!/bin/bash
# Compile Rust to WebAssembly

set -e

echo "🔨 Building WASM modules..."

# Check if wasm-pack is installed
if ! command -v wasm-pack &> /dev/null; then
    echo "Installing wasm-pack..."
    curl https://rustwasm.org/wasm-pack/installer/init.sh -sSf | sh
fi

# Build for web target
wasm-pack build --target web --release

# Copy to WordPress public directory for serving
DEST="/home/calvin/Documents/TAK_WP/headless-wordpress/wordpress/wp-content/wasm"
mkdir -p "$DEST"

cp pkg/*.wasm "$DEST/"
cp pkg/*.js "$DEST/"
cp pkg/*.d.ts "$DEST/" 2>/dev/null || true

echo "✅ WASM build complete!"
echo "📦 Files copied to: $DEST"
echo ""
echo "Generated files:"
ls -la pkg/
