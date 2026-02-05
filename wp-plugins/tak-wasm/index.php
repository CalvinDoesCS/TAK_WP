<?php
/**
 * Plugin Name: TAK WASM Endpoint  
 * Description: Serves WASM modules from wp-content/wasm with proper binary headers
 * Version: 1.0.2
 * Author: TAK_WP
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Serve WASM files directly - run at the earliest possible moment
 */
add_action('plugins_loaded', function() {
    // Parse the request URI
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    
    // Check if this is a WASM request matching /wp-json/tak/v1/wasm/{module}[/{submodule}]
    if (preg_match('#^/wp-json/tak/v1/wasm/(.+)$#', parse_url($request_uri, PHP_URL_PATH), $matches)) {
        $raw_path = $matches[1];
        $raw_path = preg_replace('#\.wasm$#', '', $raw_path);
        $parts = array_filter(explode('/', $raw_path));
        $safe_parts = array_map('sanitize_file_name', $parts);
        $relative_path = implode('/', $safe_parts);
        $wasm_dir = WP_CONTENT_DIR . '/wasm/';
        $wasm_file = $wasm_dir . $relative_path . '.wasm';

        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        if (empty($relative_path) || !file_exists($wasm_file)) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'code' => 'wasm_not_found',
                'message' => 'WASM module not found: ' . $raw_path,
                'data' => ['status' => 404]
            ]);
            exit;
        }

        // Set proper headers for WASM binary
        http_response_code(200);
        header('Content-Type: application/wasm');
        header('Content-Length: ' . filesize($wasm_file));
        header('Cache-Control: public, max-age=31536000, immutable');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        
        // Stream the binary file
        readfile($wasm_file);
        exit;
    }
}, -9999); // Highest priority (runs first)

/**
 * Handle CORS preflight for OPTIONS requests
 */
add_action('init', function() {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($request_uri, '/wp-json/tak/v1/wasm/') !== false) {
            status_header(200);
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
            header('Access-Control-Max-Age: 86400');
            exit;
        }
    }
}, 1);

/**
 * Add CORS headers to all WordPress REST API responses
 */
add_action('rest_api_init', function() {
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter('rest_pre_serve_request', function($value) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');
        return $value;
    });
}, 15);
