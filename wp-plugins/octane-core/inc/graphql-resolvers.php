<?php
/**
 * WPGraphQL Integration for Octane Core
 * Registers custom GraphQL fields for room metadata
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register custom GraphQL field: octaneRoomData
 */
function octane_register_graphql_fields() {
    // Register field on RootQuery
    register_graphql_field('RootQuery', 'octaneRoomData', [
        'type' => 'String',
        'description' => __('Get Octane room metadata as JSON string', 'octane-core'),
        'args' => [
            'roomName' => [
                'type' => 'String',
                'description' => __('Name of the Octane room', 'octane-core'),
            ],
        ],
        'resolve' => function($source, $args, $context, $info) {
            $room_name = isset($args['roomName']) ? sanitize_file_name($args['roomName']) : '';
            
            if (empty($room_name)) {
                return json_encode([
                    'error' => 'Room name is required',
                    'success' => false,
                ]);
            }
            
            // Get room metadata from WordPress options
            $metadata = get_option('octane_room_' . $room_name, []);
            
            // If no metadata exists, try to get from post meta
            // (assuming rooms might be stored as custom post types)
            if (empty($metadata)) {
                $room_posts = get_posts([
                    'post_type' => 'octane_room',
                    'name' => $room_name,
                    'posts_per_page' => 1,
                    'post_status' => 'publish',
                ]);
                
                if (!empty($room_posts)) {
                    $room_post = $room_posts[0];
                    $metadata = [
                        'id' => $room_post->ID,
                        'title' => $room_post->post_title,
                        'content' => $room_post->post_content,
                        'excerpt' => $room_post->post_excerpt,
                        'meta' => get_post_meta($room_post->ID),
                    ];
                }
            }
            
            // Add system metadata
            $response = [
                'success' => true,
                'room' => $room_name,
                'metadata' => $metadata,
                'wasmUrl' => OCTANE_ROOMS_URL . $room_name . '.wasm',
                'timestamp' => time(),
            ];
            
            return json_encode($response);
        }
    ]);
    
    // Register field on Page type
    register_graphql_field('Page', 'octaneRooms', [
        'type' => ['list_of' => 'String'],
        'description' => __('List of Octane rooms embedded in this page', 'octane-core'),
        'resolve' => function($post, $args, $context, $info) {
            $content = get_post_field('post_content', $post->ID);
            
            // Extract all [octane_room name="xyz"] shortcodes
            preg_match_all('/\[octane_room\s+name=["\']([^"\']+)["\']/i', $content, $matches);
            
            return isset($matches[1]) ? array_unique($matches[1]) : [];
        }
    ]);
    
    // Register custom post type for rooms if it doesn't exist
    if (!post_type_exists('octane_room')) {
        register_post_type('octane_room', [
            'labels' => [
                'name' => __('Octane Rooms', 'octane-core'),
                'singular_name' => __('Octane Room', 'octane-core'),
            ],
            'public' => true,
            'show_in_graphql' => true,
            'graphql_single_name' => 'octaneRoom',
            'graphql_plural_name' => 'octaneRooms',
            'supports' => ['title', 'editor', 'custom-fields'],
            'menu_icon' => 'dashicons-desktop',
        ]);
    }
}
add_action('graphql_register_types', 'octane_register_graphql_fields');

/**
 * Add custom meta fields to GraphQL
 */
function octane_register_graphql_meta_fields() {
    // Register common room configuration fields
    $meta_fields = [
        'octane_room_config' => 'String',
        'octane_room_permissions' => 'String',
        'octane_room_version' => 'String',
        'octane_room_dependencies' => 'String',
    ];
    
    foreach ($meta_fields as $meta_key => $type) {
        register_graphql_field('OctaneRoom', str_replace('octane_room_', '', $meta_key), [
            'type' => $type,
            'description' => sprintf(__('Room %s metadata', 'octane-core'), str_replace('_', ' ', $meta_key)),
            'resolve' => function($post) use ($meta_key) {
                return get_post_meta($post->ID, $meta_key, true);
            }
        ]);
    }
}
add_action('graphql_register_types', 'octane_register_graphql_meta_fields');

/**
 * Add custom mutation to update room metadata
 */
function octane_register_graphql_mutations() {
    register_graphql_mutation('updateOctaneRoomData', [
        'inputFields' => [
            'roomName' => [
                'type' => ['non_null' => 'String'],
                'description' => __('Name of the room to update', 'octane-core'),
            ],
            'metadata' => [
                'type' => 'String',
                'description' => __('JSON string of metadata to store', 'octane-core'),
            ],
        ],
        'outputFields' => [
            'success' => [
                'type' => 'Boolean',
                'description' => __('Whether the update was successful', 'octane-core'),
            ],
            'message' => [
                'type' => 'String',
                'description' => __('Status message', 'octane-core'),
            ],
        ],
        'mutateAndGetPayload' => function($input, $context, $info) {
            // Check user permissions
            if (!current_user_can('edit_posts')) {
                return [
                    'success' => false,
                    'message' => 'Unauthorized: You do not have permission to update room data',
                ];
            }
            
            $room_name = sanitize_file_name($input['roomName']);
            $metadata = isset($input['metadata']) ? json_decode($input['metadata'], true) : [];
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'message' => 'Invalid JSON metadata',
                ];
            }
            
            // Update room metadata
            $updated = update_option('octane_room_' . $room_name, $metadata);
            
            return [
                'success' => $updated,
                'message' => $updated ? 'Room metadata updated successfully' : 'Failed to update room metadata',
            ];
        }
    ]);
}
add_action('graphql_register_types', 'octane_register_graphql_mutations');
