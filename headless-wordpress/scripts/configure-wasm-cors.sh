#!/bin/bash

# Configure WordPress for CORS and Wasm support

echo "🔧 Configuring WordPress for Wasm Micro-Apps..."

# Add CORS headers to WordPress
docker-compose exec -T wpcli wp config set --raw WP_CORS_ENABLED true

# Add custom CORS headers via wp-config.php
docker-compose exec -T wpcli bash -c 'cat >> /var/www/html/wp-config.php << '\''EOF'\''

// CORS Configuration for Octane Wasm Apps
if (!defined('\''WP_CORS_ENABLED'\'')) {
    define('\''WP_CORS_ENABLED'\'', true);
}

if (WP_CORS_ENABLED) {
    // Allow specific origins
    $allowed_origins = array(
        '\''http://localhost:3000'\'',
        '\''http://octane-micro-app-api:3000'\'',
        '\''http://localhost:8080'\''
    );
    
    $origin = isset($_SERVER['\''HTTP_ORIGIN'\'']) ? $_SERVER['\''HTTP_ORIGIN'\''] : '\'\'';
    
    if (in_array($origin, $allowed_origins)) {
        header("Access-Control-Allow-Origin: $origin");
        header('\''Access-Control-Allow-Credentials: true'\'');
        header('\''Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS'\'');
        header('\''Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce, X-Requested-With'\'');
    }
    
    // Handle preflight requests
    if ($_SERVER['\''REQUEST_METHOD'\''] === '\''OPTIONS'\'') {
        http_response_code(200);
        exit();
    }
}

// Add Wasm MIME type support
add_filter('\''upload_mimes'\'', function($mimes) {
    $mimes['\''wasm'\''] = '\''application/wasm'\'';
    return $mimes;
});

// Allow Wasm files in media library
add_filter('\''wp_check_filetype_and_ext'\'', function($data, $file, $filename, $mimes) {
    $filetype = wp_check_filetype($filename, $mimes);
    return [
        '\''ext'\'' => $filetype['\''ext'\''],
        '\''type'\'' => $filetype['\''type'\''],
        '\''proper_filename'\'' => $data['\''proper_filename'\''']
    ];
}, 10, 4);
EOF'

echo "✅ CORS and Wasm configuration added!"
echo ""
echo "Configured for:"
echo "  - http://localhost:3000 (Micro-app API)"
echo "  - http://localhost:8080 (WordPress)"
echo "  - Wasm file uploads enabled"
