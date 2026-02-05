#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WP_WASM_DIR="/home/calvin/Documents/TAK_WP/headless-wordpress/wordpress/wp-content/wasm/ncmaz_faust"

cd "$ROOT_DIR"

echo "[1/4] Running GraphQL codegen"
npm run codegen

echo "[2/4] Building Ncmaz static export"
npm run build

echo "[3/4] Building wasm sidecar"
cd "$ROOT_DIR/wasm-sidecar"
wasm-pack build --target web --dev

echo "[4/4] Copying wasm artifacts to WordPress"
sudo mkdir -p "$WP_WASM_DIR"
sudo cp "$ROOT_DIR/wasm-sidecar/pkg/"ncmaz_faust_sidecar.* "$WP_WASM_DIR/"

echo "Done."
