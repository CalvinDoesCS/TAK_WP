#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== WordPress Setup Script ===${NC}\n"

# Install essential plugins
echo -e "${BLUE}Installing required plugins...${NC}"
docker exec headless-wp-cli wp plugin install \
  jwt-authentication-for-rest-api \
  rest-api-head-tags \
  rest-api-custom-endpoints \
  --activate

echo -e "${GREEN}✓ Plugins installed${NC}\n"

# Enable REST API
echo -e "${BLUE}Configuring REST API...${NC}"
docker exec headless-wp-cli wp option update rest_api_enabled 1

# Set JWT configuration options
echo -e "${BLUE}Setting JWT authentication...${NC}"
docker exec headless-wp-cli wp option update jwt_auth_enable_cors 1
docker exec headless-wp-cli wp option update jwt_auth_cors_allow_headers 'X-Requested-With,Content-Type,Authorization'

echo -e "${GREEN}✓ JWT configuration complete${NC}\n"

# Create an API test user (optional - for testing)
echo -e "${BLUE}Creating API test user...${NC}"
docker exec headless-wp-cli wp user create apiuser apiuser@example.com \
  --user_pass=apipassword \
  --role=editor \
  --porcelain || echo "User may already exist"

echo -e "${GREEN}✓ Setup complete!${NC}\n"

echo -e "${BLUE}Next steps:${NC}"
echo "1. Go to http://localhost:8080/wp-admin"
echo "2. Configure JWT Secret Key in plugin settings"
echo "3. Test API endpoint: curl http://localhost:8080/wp-json/"
