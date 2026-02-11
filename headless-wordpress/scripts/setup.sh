#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Setting up Headless WordPress...${NC}"

# Wait for WordPress to be ready
echo -e "${YELLOW}Waiting for WordPress to initialize...${NC}"
sleep 20

# Install WordPress core CHANGE  URL
echo -e "${YELLOW}Installing WordPress core...${NC}"
docker-compose exec -T wpcli wp core install \
  --url="http://localhost:8080" \ 
  --title="Headless WordPress" \
  --admin_user="admin" \
  --admin_password="admin123" \
  --admin_email="admin@example.com" \
  --skip-email

# Install WPGraphQL plugin
echo -e "${YELLOW}Installing WPGraphQL plugin...${NC}"
docker-compose exec -T wpcli wp plugin install wp-graphql --activate

# Install WPGraphQL JWT Authentication
echo -e "${YELLOW}Installing WPGraphQL JWT Authentication...${NC}"
docker-compose exec -T wpcli wp plugin install wp-graphql-jwt-authentication --activate

# Install and activate ACF (Advanced Custom Fields)
echo -e "${YELLOW}Installing Advanced Custom Fields...${NC}"
docker-compose exec -T wpcli wp plugin install advanced-custom-fields --activate

# Install WPGraphQL for ACF
echo -e "${YELLOW}Installing WPGraphQL for ACF...${NC}"
docker-compose exec -T wpcli wp plugin install wpgraphql-acf --activate

# Set permalink structure
echo -e "${YELLOW}Setting permalink structure...${NC}"
docker-compose exec -T wpcli wp rewrite structure '/%postname%/' --hard

# Enable CORS
echo -e "${YELLOW}Configuring CORS settings...${NC}"
docker-compose exec -T wpcli wp config set GRAPHQL_JWT_AUTH_SECRET_KEY "your-secret-key-change-this-in-production" --raw

# Create sample content
echo -e "${YELLOW}Creating sample content...${NC}"
docker-compose exec -T wpcli wp post create \
  --post_type=post \
  --post_title='Welcome to Headless WordPress' \
  --post_content='This is a sample post for your headless WordPress setup using WPGraphQL.' \
  --post_status=publish

# Flush rewrite rules
echo -e "${YELLOW}Flushing rewrite rules...${NC}"
docker-compose exec -T wpcli wp rewrite flush

echo -e "${GREEN}✓ WordPress setup complete!${NC}"
echo -e "${GREEN}Access your WordPress admin at: http://localhost:8080/wp-admin${NC}"
echo -e "${GREEN}Username: admin${NC}"
echo -e "${GREEN}Password: admin123${NC}"
echo -e "${GREEN}GraphQL endpoint: http://localhost:8080/graphql${NC}"
