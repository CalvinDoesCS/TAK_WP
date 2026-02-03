<?php
/**
 * Plugin Name: Octane Core
 * Plugin URI: https://octanedesktop.io
 * Description: WordPress glue plugin for Octane Desktop micro-frontend ecosystem with WASM support
 * Version: 1.0.0
 * Author: Calvin Tran
 * License: MIT
 * Text Domain: octane-core
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('OCTANE_VERSION', '1.0.0');
define('OCTANE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OCTANE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OCTANE_ROOMS_DIR', OCTANE_PLUGIN_DIR . 'rooms/');
define('OCTANE_ROOMS_URL', OCTANE_PLUGIN_URL . 'rooms/');

/**
 * Add WASM MIME type support
 */
function octane_add_wasm_mime_types($mimes) {
    $mimes['wasm'] = 'application/wasm';
    return $mimes;
}
add_filter('upload_mimes', 'octane_add_wasm_mime_types');

/**
 * Allow WASM files in media library
 */
function octane_check_wasm_filetype($data, $file, $filename, $mimes) {
    $filetype = wp_check_filetype($filename, $mimes);
    
    if ($filetype['ext'] === 'wasm') {
        return [
            'ext' => 'wasm',
            'type' => 'application/wasm',
            'proper_filename' => $data['proper_filename']
        ];
    }
    
    return $data;
}
add_filter('wp_check_filetype_and_ext', 'octane_check_wasm_filetype', 10, 4);

/**
 * Extract user identity from X-Forwarded-User header
 */
function octane_get_forwarded_user() {
    // Check for X-Forwarded-User header (AWS IAP/Proxy)
    if (isset($_SERVER['HTTP_X_FORWARDED_USER'])) {
        return sanitize_text_field($_SERVER['HTTP_X_FORWARDED_USER']);
    }
    
    // Fallback to WordPress user if authenticated
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        return $current_user->user_login;
    }
    
    return 'anonymous';
}

/**
 * Generate a room-specific token
 */
function octane_generate_room_token($room_name, $user_identity) {
    $secret = defined('OCTANE_SECRET_KEY') ? OCTANE_SECRET_KEY : wp_salt('nonce');
    return hash_hmac('sha256', $room_name . '|' . $user_identity . '|' . time(), $secret);
}

/**
 * Enqueue WASM loader script with identity bridge
 */
function octane_enqueue_room_loader($room_name) {
    $user_identity = octane_get_forwarded_user();
    $room_token = octane_generate_room_token($room_name, $user_identity);
    
    // Check if JS loader exists
    $loader_path = OCTANE_ROOMS_DIR . $room_name . '.js';
    $wasm_path = OCTANE_ROOMS_DIR . $room_name . '.wasm';
    
    if (!file_exists($loader_path)) {
        return false;
    }
    
    if (!file_exists($wasm_path)) {
        return false;
    }
    
    // Enqueue the ES module loader
    $handle = 'octane-room-' . sanitize_title($room_name);
    wp_enqueue_script(
        $handle,
        OCTANE_ROOMS_URL . $room_name . '.js',
        [], // No dependencies - ES module
        OCTANE_VERSION,
        true // Load in footer
    );
    
    // Add module type attribute
    add_filter('script_loader_tag', function($tag, $script_handle) use ($handle) {
        if ($script_handle === $handle) {
            return str_replace(' src', ' type="module" src', $tag);
        }
        return $tag;
    }, 10, 2);
    
    // Localize script with identity and config
    $config = [
        'identity' => $user_identity,
        'roomName' => $room_name,
        'roomToken' => $room_token,
        'wasmUrl' => OCTANE_ROOMS_URL . $room_name . '.wasm',
        'apiEndpoint' => rest_url('octane/v1/'),
        'graphqlEndpoint' => home_url('/graphql'),
        'timestamp' => time(),
    ];
    
    wp_localize_script($handle, 'OctaneConfig', $config);
    
    return true;
}

/**
 * Main shortcode: [octane_room name="xyz"]
 */
function octane_room_shortcode($atts) {
    $atts = shortcode_atts([
        'name' => '',
        'width' => '100%',
        'height' => '600px',
    ], $atts, 'octane_room');
    
    $room_name = sanitize_file_name($atts['name']);
    
    if (empty($room_name)) {
        return '<div class="octane-error">Error: Room name is required</div>';
    }
    
    // Try to enqueue the room loader
    $loaded = octane_enqueue_room_loader($room_name);
    
    if (!$loaded) {
        return sprintf(
            '<div class="octane-room-maintenance" style="width:%s;height:%s;display:flex;align-items:center;justify-content:center;background:#f5f5f5;border:2px dashed #ccc;border-radius:8px;">
                <div style="text-align:center;padding:2rem;">
                    <h3 style="color:#666;margin:0 0 1rem 0;">🔧 Room Under Maintenance</h3>
                    <p style="color:#999;margin:0;">The <strong>%s</strong> room is currently unavailable.</p>
                    <p style="color:#999;font-size:0.9em;margin:0.5rem 0 0 0;">Please check back later or contact support.</p>
                </div>
            </div>',
            esc_attr($atts['width']),
            esc_attr($atts['height']),
            esc_html($room_name)
        );
    }
    
    // Generate container ID
    $container_id = 'octane-room-' . sanitize_title($room_name);
    
    return sprintf(
        '<div id="%s" class="octane-room-container" data-room="%s" style="width:%s;height:%s;"></div>',
        esc_attr($container_id),
        esc_attr($room_name),
        esc_attr($atts['width']),
        esc_attr($atts['height'])
    );
}
add_shortcode('octane_room', 'octane_room_shortcode');

/**
 * Register REST API endpoints
 */
function octane_register_rest_routes() {
    // Get room metadata
    register_rest_route('octane/v1', '/room/(?P<name>[a-zA-Z0-9_-]+)', [
        'methods' => 'GET',
        'callback' => 'octane_get_room_data',
        'permission_callback' => '__return_true',
    ]);
    
    // Serve WASM file
    register_rest_route('octane/v1', '/wasm/(?P<name>[a-zA-Z0-9_-]+)', [
        'methods' => 'GET',
        'callback' => 'octane_serve_wasm',
        'permission_callback' => '__return_true',
    ]);
    
    // List all available rooms
    register_rest_route('octane/v1', '/rooms', [
        'methods' => 'GET',
        'callback' => 'octane_list_rooms',
        'permission_callback' => '__return_true',
    ]);

    // Provide WordPress data for Ncmaz-Faust WASM app
    register_rest_route('octane/v1', '/ncmaz-data', [
        'methods' => 'GET',
        'callback' => 'octane_get_ncmaz_data',
        'permission_callback' => '__return_true',
    ]);
}
add_action('rest_api_init', 'octane_register_rest_routes');

/**
 * REST endpoint to get room metadata
 */
function octane_get_room_data($request) {
    $room_name = sanitize_file_name($request['name']);
    
    // Get room metadata from WordPress options or custom fields
    $metadata = get_option('octane_room_' . $room_name, []);
    
    return rest_ensure_response([
        'success' => true,
        'room' => $room_name,
        'metadata' => $metadata,
        'timestamp' => time(),
    ]);
}

/**
 * REST endpoint to serve WASM files
 */
function octane_serve_wasm($request) {
    $room_name = sanitize_file_name($request['name']);
    $wasm_file = OCTANE_ROOMS_DIR . $room_name . '.wasm';
    
    if (!file_exists($wasm_file)) {
        return new WP_Error('not_found', 'WASM file not found', ['status' => 404]);
    }
    
    // Read the WASM file
    $wasm_content = file_get_contents($wasm_file);
    
    if ($wasm_content === false) {
        return new WP_Error('read_error', 'Failed to read WASM file', ['status' => 500]);
    }
    
    // Send raw binary data (bypass WordPress JSON encoding)
    // Clear any existing output
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set headers
    header('Content-Type: application/wasm');
    header('Content-Length: ' . filesize($wasm_file));
    header('Cache-Control: public, max-age=31536000');
    header('Access-Control-Allow-Origin: *');
    
    // Output raw binary
    echo $wasm_content;
    exit;
}

/**
 * REST endpoint to provide WordPress data for Ncmaz-Faust
 */
function octane_get_ncmaz_data($request) {
    $posts = get_posts([
        'numberposts' => 5,
        'post_status' => 'publish',
    ]);

    $post_data = array_map(function ($post) {
        return [
            'id' => $post->ID,
            'title' => get_the_title($post),
            'excerpt' => get_the_excerpt($post),
            'link' => get_permalink($post),
            'date' => get_the_date('c', $post),
            'author' => get_the_author_meta('display_name', $post->post_author),
        ];
    }, $posts);

    $current_user = wp_get_current_user();
    $user_data = $current_user && $current_user->ID
        ? [
            'id' => $current_user->ID,
            'name' => $current_user->display_name,
            'email' => $current_user->user_email,
          ]
        : null;

    return rest_ensure_response([
        'success' => true,
        'timestamp' => time(),
        'site' => [
            'name' => get_bloginfo('name'),
            'url' => get_bloginfo('url'),
            'description' => get_bloginfo('description'),
        ],
        'user' => $user_data,
        'posts' => $post_data,
    ]);
}

/**
 * REST endpoint to list available rooms
 */
function octane_list_rooms($request) {
    $rooms = [];
    
    // Scan the rooms directory for .wasm files
    if (is_dir(OCTANE_ROOMS_DIR)) {
        $files = scandir(OCTANE_ROOMS_DIR);
        
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'wasm') {
                $room_name = pathinfo($file, PATHINFO_FILENAME);
                $js_file = OCTANE_ROOMS_DIR . $room_name . '.js';
                
                $rooms[] = [
                    'name' => $room_name,
                    'wasm_url' => OCTANE_ROOMS_URL . $file,
                    'js_url' => file_exists($js_file) ? OCTANE_ROOMS_URL . $room_name . '.js' : null,
                    'wasm_size' => filesize(OCTANE_ROOMS_DIR . $file),
                    'has_loader' => file_exists($js_file),
                    'rest_url' => rest_url('octane/v1/wasm/' . $room_name),
                ];
            }
        }
    }
    
    return rest_ensure_response([
        'success' => true,
        'count' => count($rooms),
        'rooms' => $rooms,
        'timestamp' => time(),
    ]);
}

/**
 * Load WPGraphQL integration if available
 */
function octane_load_graphql_integration() {
    if (class_exists('WPGraphQL')) {
        require_once OCTANE_PLUGIN_DIR . 'inc/graphql-resolvers.php';
    }
}
add_action('graphql_init', 'octane_load_graphql_integration');

/**
 * Add .htaccess rules for WASM MIME types on activation
 */
function octane_activation() {
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Try to add .htaccess rules for Apache
    if (function_exists('insert_with_markers')) {
        $htaccess_file = ABSPATH . '.htaccess';
        $rules = [
            '<IfModule mod_mime.c>',
            '    AddType application/wasm .wasm',
            '</IfModule>',
            '<FilesMatch "\\.wasm$">',
            '    Header set Content-Type "application/wasm"',
            '    Header set Cache-Control "public, max-age=31536000"',
            '</FilesMatch>',
        ];
        
        insert_with_markers($htaccess_file, 'Octane WASM', $rules);
    }
}
register_activation_hook(__FILE__, 'octane_activation');

/**
 * Clean up on deactivation
 */
function octane_deactivation() {
    flush_rewrite_rules();
    
    // Remove .htaccess rules
    if (function_exists('insert_with_markers')) {
        $htaccess_file = ABSPATH . '.htaccess';
        insert_with_markers($htaccess_file, 'Octane WASM', []);
    }
}
register_deactivation_hook(__FILE__, 'octane_deactivation');

/**
 * Add admin styles for error messages
 */
function octane_enqueue_styles() {
    if (!is_admin()) {
        wp_add_inline_style('wp-block-library', '
            .octane-error {
                padding: 1rem;
                background: #fee;
                border: 2px solid #fcc;
                border-radius: 4px;
                color: #c00;
                font-weight: bold;
            }
            
            .octane-room-container {
                position: relative;
                overflow: hidden;
            }
            
            .octane-room-maintenance {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            }
        ');
    }
}
add_action('wp_enqueue_scripts', 'octane_enqueue_styles');
