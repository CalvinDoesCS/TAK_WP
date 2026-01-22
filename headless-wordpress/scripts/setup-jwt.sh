#!/bin/bash

# Enhanced setup script for JWT authentication
echo "Installing JWT Authentication for WP REST API..."

# Install the alternative JWT plugin
docker-compose exec -T wpcli wp plugin install jwt-authentication-for-wp-rest-api --activate

# Configure JWT secret in wp-config.php if not already set
docker-compose exec -T wpcli wp config set JWT_AUTH_SECRET_KEY '"jwt-secret-key-change-in-production"' --type=constant --add

# Add CORS headers to .htaccess for REST API
docker-compose exec -T wpcli bash -c 'cat >> /var/www/html/.htaccess << EOF

# Enable CORS
<IfModule mod_headers.c>
    SetEnvIf Origin "http(s)?://(localhost|127.0.0.1)(:[0-9]+)?$" AccessControlAllowOrigin=\$0
    Header add Access-Control-Allow-Origin %{AccessControlAllowOrigin}e env=AccessControlAllowOrigin
    Header set Access-Control-Allow-Credentials true
    Header set Access-Control-Allow-Headers "Authorization, Content-Type"
    Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
</IfModule>
EOF'

echo "JWT Authentication setup complete!"
