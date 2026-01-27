#!/bin/bash

# Octane Core Plugin Installation Script
# Installs and activates the plugin in the WordPress Docker container

set -e

PLUGIN_DIR="/home/calvin/Documents/TAK_WP/wp-plugins/octane-core"
CONTAINER_NAME="headless-wp"
CLI_CONTAINER="headless-wp-cli"

echo "🚀 Installing Octane Core Plugin..."

# Check if plugin directory exists
if [ ! -d "$PLUGIN_DIR" ]; then
    echo "❌ Error: Plugin directory not found at $PLUGIN_DIR"
    exit 1
fi

# Copy plugin to WordPress container
echo "📦 Copying plugin files to WordPress..."
docker cp "$PLUGIN_DIR" "$CONTAINER_NAME:/var/www/html/wp-content/plugins/"

# Fix permissions
echo "🔒 Setting correct permissions..."
docker exec -t "$CONTAINER_NAME" chown -R www-data:www-data \
    /var/www/html/wp-content/plugins/octane-core

# Enable Apache mod_headers (required for .htaccess WASM rules)
echo "🔧 Enabling Apache mod_headers..."
docker exec -t "$CONTAINER_NAME" a2enmod headers 2>/dev/null || true

# Activate the plugin
echo "✨ Activating plugin..."
docker exec -t "$CLI_CONTAINER" wp plugin activate octane-core

# Restart Apache to apply module changes
echo "🔄 Restarting Apache..."
docker restart "$CONTAINER_NAME" > /dev/null
sleep 2

# Verify installation
echo ""
echo "✅ Plugin installed successfully!"
echo ""
docker exec -t "$CLI_CONTAINER" wp plugin list | grep octane-core

echo ""
echo "📖 Quick Start:"
echo "   1. Add your .wasm and .js files to: $PLUGIN_DIR/rooms/"
echo "   2. Use the shortcode: [octane_room name=\"your-room\"]"
echo "   3. See README.md for full documentation"
echo ""
