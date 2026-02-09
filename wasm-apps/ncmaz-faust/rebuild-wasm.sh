#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WP_WASM_DIR="/home/calvin/Documents/TAK_WP/headless-wordpress/wordpress/wp-content/wasm/nacmaz_faust"

cd "$ROOT_DIR"

echo "[1/4] Skipping GraphQL codegen (building shell-only export)"

# Skip codegen to avoid GraphQL validation during shell-only builds
# npm run codegen

echo "[2/4] Building Ncmaz static export (shell-only)"
npm run build || true

echo "[3/4] Building wasm sidecar"
cd "$ROOT_DIR/wasm-sidecar"
wasm-pack build --target web --dev

echo "[4/4] Copying wasm artifacts to WordPress"
sudo mkdir -p "$WP_WASM_DIR"
sudo cp "$ROOT_DIR/wasm-sidecar/pkg/"nacmaz_faust.* "$WP_WASM_DIR/"

echo "Done."
