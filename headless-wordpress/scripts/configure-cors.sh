#!/bin/bash

# Add CORS headers to wp-config.php
cat << 'EOF' >> ./wordpress/wp-config.php

// Enable CORS for headless setup
define('GRAPHQL_JWT_AUTH_SECRET_KEY', 'your-secret-key-change-this-in-production');

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    status_header(200);
    exit();
}
EOF

echo "CORS configuration added to wp-config.php"
