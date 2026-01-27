#!/bin/bash

# Create the shared app network for WordPress and Micro-apps communication

NETWORK_NAME="app-network"

echo "🌐 Creating app shared network..."

# Check if network already exists
if docker network ls | grep -q "$NETWORK_NAME"; then
    echo "✅ Network '$NETWORK_NAME' already exists"
else
    docker network create "$NETWORK_NAME"
    echo "✅ Network '$NETWORK_NAME' created successfully"
fi

# Show network details
echo ""
echo "📊 Network Information:"
docker network inspect "$NETWORK_NAME" --format '{{json .}}' | jq '{Name: .Name, Driver: .Driver, Scope: .Scope, IPAM: .IPAM}'

echo ""
echo "✅ Network setup complete!"
echo ""
echo "Next steps:"
echo "1. Run: docker-compose down && docker-compose up -d"
echo "2. Create your micro-app with the provided template"
echo "3. Both services will communicate over 'app-network'"
