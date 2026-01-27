#!/bin/bash

echo "Testing WordPress Headless Setup..."
echo ""
echo "1. Testing GraphQL endpoint..."
echo ""

# Test GraphQL query
GRAPHQL_RESPONSE=$(curl -s -X POST http://localhost:8080/graphql \
  -H "Content-Type: application/json" \
  -d '{"query":"query { generalSettings { title url description } }"}')

echo "GraphQL Response:"
echo "$GRAPHQL_RESPONSE" | jq '.' 2>/dev/null || echo "$GRAPHQL_RESPONSE"
echo ""

echo "2. Testing REST API..."
echo ""

# Test REST API
REST_RESPONSE=$(curl -s http://localhost:8080/wp-json/wp/v2/posts?per_page=1)

echo "REST API Response:"
echo "$REST_RESPONSE" | jq '.[0] | {id, title, date}' 2>/dev/null || echo "$REST_RESPONSE"
echo ""

echo "3. Testing WordPress info..."
echo ""

# Test basic WordPress info
docker-compose exec -T wpcli wp core version
docker-compose exec -T wpcli wp plugin list --status=active --field=name

echo ""
echo "✅ Testing complete!"
echo ""
echo "Access points:"
echo "  - Admin: http://localhost:8080/wp-admin"
echo "  - GraphQL: http://localhost:8080/graphql"
echo "  - REST API: http://localhost:8080/wp-json"
