<?php
/**
 * Plugin Name: Ncmaz ACF Fields
 * Description: Registers ACF field groups required by the Ncmaz Faust GraphQL schema.
 * Version: 0.1.0
 * Author: Local
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('graphql_is_introspection_allowed', '__return_true');

add_action('graphql_register_types', function () {
    if (!function_exists('register_graphql_field')) {
        return;
    }

    // Ensure ACF relationship nodes in ncmazfaustMenu expose an id field.
    register_graphql_field('MenuItem_Ncmazfaustmenu_Posts_Nodes', 'id', [
        'type' => 'ID',
        'description' => 'ID for menu-related nodes (fallback to database ID).',
        'resolve' => function ($root) {
            $database_id = null;
            if (is_object($root) && isset($root->ID)) {
                $database_id = $root->ID;
            } elseif (is_array($root) && isset($root['ID'])) {
                $database_id = $root['ID'];
            } elseif (is_object($root) && isset($root->databaseId)) {
                $database_id = $root->databaseId;
            } elseif (is_array($root) && isset($root['databaseId'])) {
                $database_id = $root['databaseId'];
            }

            return $database_id !== null ? (string) $database_id : null;
        },
    ]);
}, 100);

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    // Category meta: ncTaxonomyMeta
    acf_add_local_field_group([
        'key' => 'group_nc_taxonomy_meta',
        'title' => 'Nc Taxonomy Meta',
        'fields' => [
            [
                'key' => 'field_nc_taxonomy_color',
                'label' => 'Color',
                'name' => 'color',
                'type' => 'text',
            ],
            [
                'key' => 'field_nc_taxonomy_featured_image',
                'label' => 'Featured Image',
                'name' => 'featuredImage',
                'type' => 'image',
                'return_format' => 'id',
                'preview_size' => 'thumbnail',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'taxonomy',
                    'operator' => '==',
                    'value' => 'category',
                ],
            ],
        ],
        'show_in_graphql' => 1,
        'graphql_field_name' => 'ncTaxonomyMeta',
        'graphql_types' => ['Category'],
    ]);

    // Menu item meta: ncmazfaustMenu
    acf_add_local_field_group([
        'key' => 'group_ncmazfaust_menu',
        'title' => 'Ncmaz Faust Menu',
        'fields' => [
            [
                'key' => 'field_ncmaz_menu_is_mega',
                'label' => 'Is Mega Menu',
                'name' => 'isMegaMenu',
                'type' => 'true_false',
                'ui' => 1,
                'graphql_field_name' => 'isMegaMenu',
            ],
            [
                'key' => 'field_ncmaz_menu_columns',
                'label' => 'Number of Menu Columns',
                'name' => 'numberOfMenuColumns',
                'type' => 'number',
                'min' => 0,
                'step' => 1,
                'graphql_field_name' => 'numberOfMenuColumns',
            ],
            [
                'key' => 'field_ncmaz_menu_posts_group',
                'label' => 'Posts',
                'name' => 'posts',
                'type' => 'group',
                'graphql_field_name' => 'posts',
                'sub_fields' => [
                    [
                        'key' => 'field_ncmaz_menu_posts_nodes',
                        'label' => 'Nodes',
                        'name' => 'nodes',
                        'type' => 'relationship',
                        'post_type' => ['post'],
                        'return_format' => 'object',
                        'max' => 50,
                        'graphql_field_name' => 'nodes',
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'nav_menu_item',
                    'operator' => '==',
                    'value' => 'all',
                ],
            ],
        ],
        'show_in_graphql' => 1,
        'graphql_field_name' => 'ncmazfaustMenu',
        'graphql_types' => ['MenuItem'],
    ]);

    // Post meta: ncPostMetaData
    acf_add_local_field_group([
        'key' => 'group_nc_post_meta',
        'title' => 'Nc Post Meta',
        'fields' => [
            [
                'key' => 'field_nc_post_views',
                'label' => 'Views Count',
                'name' => 'viewsCount',
                'type' => 'number',
                'min' => 0,
                'graphql_field_name' => 'viewsCount',
            ],
            [
                'key' => 'field_nc_post_reading_time',
                'label' => 'Reading Time',
                'name' => 'readingTime',
                'type' => 'number',
                'min' => 0,
                'graphql_field_name' => 'readingTime',
            ],
            [
                'key' => 'field_nc_post_likes',
                'label' => 'Likes Count',
                'name' => 'likesCount',
                'type' => 'number',
                'min' => 0,
                'graphql_field_name' => 'likesCount',
            ],
            [
                'key' => 'field_nc_post_saves',
                'label' => 'Saveds Count',
                'name' => 'savedsCount',
                'type' => 'number',
                'min' => 0,
                'graphql_field_name' => 'savedsCount',
            ],
            [
                'key' => 'field_nc_post_sidebar',
                'label' => 'Show Right Sidebar',
                'name' => 'showRightSidebar',
                'type' => 'true_false',
                'ui' => 1,
                'graphql_field_name' => 'showRightSidebar',
            ],
            [
                'key' => 'field_nc_post_template',
                'label' => 'Template',
                'name' => 'template',
                'type' => 'select',
                'choices' => [
                    'style1' => 'Style 1',
                    'style2' => 'Style 2',
                    'style3' => 'Style 3',
                    'style4' => 'Style 4',
                    'style5' => 'Style 5',
                ],
                'multiple' => 0,
                'allow_null' => 1,
                'graphql_field_name' => 'template',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'post',
                ],
            ],
        ],
        'show_in_graphql' => 1,
        'graphql_field_name' => 'ncPostMetaData',
        'graphql_types' => ['Post'],
    ]);

    // Post media URLs
    acf_add_local_field_group([
        'key' => 'group_nc_media_urls',
        'title' => 'Nc Media URLs',
        'fields' => [
            [
                'key' => 'field_nc_video_url',
                'label' => 'Video URL',
                'name' => 'videoUrl',
                'type' => 'url',
            ],
            [
                'key' => 'field_nc_audio_url',
                'label' => 'Audio URL',
                'name' => 'audioUrl',
                'type' => 'url',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'post',
                ],
            ],
        ],
        'show_in_graphql' => 1,
        'graphql_field_name' => 'ncmazVideoUrl',
        'graphql_types' => ['Post'],
    ]);

    acf_add_local_field_group([
        'key' => 'group_nc_audio_urls',
        'title' => 'Nc Audio URLs',
        'fields' => [
            [
                'key' => 'field_nc_audio_url_only',
                'label' => 'Audio URL',
                'name' => 'audioUrl',
                'type' => 'url',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'post',
                ],
            ],
        ],
        'show_in_graphql' => 1,
        'graphql_field_name' => 'ncmazAudioUrl',
        'graphql_types' => ['Post'],
    ]);

    // Post gallery images: ncmazGalleryImgs
    $gallery_fields = [];
    for ($i = 1; $i <= 8; $i++) {
        $gallery_fields[] = [
            'key' => 'field_nc_gallery_image_' . $i,
            'label' => 'Image ' . $i,
            'name' => 'image' . $i,
            'type' => 'image',
            'return_format' => 'id',
            'preview_size' => 'thumbnail',
        ];
    }

    acf_add_local_field_group([
        'key' => 'group_nc_gallery_imgs',
        'title' => 'Nc Gallery Images',
        'fields' => $gallery_fields,
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'post',
                ],
            ],
        ],
        'show_in_graphql' => 1,
        'graphql_field_name' => 'ncmazGalleryImgs',
        'graphql_types' => ['Post'],
    ]);

    // User meta: ncUserMeta
    acf_add_local_field_group([
        'key' => 'group_nc_user_meta',
        'title' => 'Nc User Meta',
        'fields' => [
            [
                'key' => 'field_nc_user_bio',
                'label' => 'Bio',
                'name' => 'ncBio',
                'type' => 'textarea',
                'graphql_field_name' => 'ncBio',
            ],
            [
                'key' => 'field_nc_user_featured_image',
                'label' => 'Featured Image',
                'name' => 'featuredImage',
                'type' => 'image',
                'return_format' => 'id',
                'preview_size' => 'thumbnail',
                'graphql_field_name' => 'featuredImage',
            ],
            [
                'key' => 'field_nc_user_background_image',
                'label' => 'Background Image',
                'name' => 'backgroundImage',
                'type' => 'image',
                'return_format' => 'id',
                'preview_size' => 'thumbnail',
                'graphql_field_name' => 'backgroundImage',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'user_form',
                    'operator' => '==',
                    'value' => 'all',
                ],
            ],
        ],
        'show_in_graphql' => 1,
        'graphql_field_name' => 'ncUserMeta',
        'graphql_types' => ['User'],
    ]);

    // User reaction fields
    acf_add_local_field_group([
        'key' => 'group_nc_user_reaction_fields',
        'title' => 'User Reaction Fields',
        'fields' => [
            [
                'key' => 'field_nc_user_liked_posts',
                'label' => 'Liked Posts',
                'name' => 'likedPosts',
                'type' => 'textarea',
                'graphql_field_name' => 'likedPosts',
            ],
            [
                'key' => 'field_nc_user_saved_posts',
                'label' => 'Saved Posts',
                'name' => 'savedPosts',
                'type' => 'textarea',
                'graphql_field_name' => 'savedPosts',
            ],
            [
                'key' => 'field_nc_user_viewed_posts',
                'label' => 'Viewed Posts',
                'name' => 'viewedPosts',
                'type' => 'textarea',
                'graphql_field_name' => 'viewedPosts',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'user_form',
                    'operator' => '==',
                    'value' => 'all',
                ],
            ],
        ],
        'show_in_graphql' => 1,
        'graphql_field_name' => 'userReactionFields',
        'graphql_types' => ['User'],
    ]);

    // Page meta: ncPageMeta
    acf_add_local_field_group([
        'key' => 'group_nc_page_meta',
        'title' => 'Nc Page Meta',
        'fields' => [
            [
                'key' => 'field_nc_page_full_width',
                'label' => 'Is Full Width Page',
                'name' => 'isFullWithPage',
                'type' => 'true_false',
                'ui' => 1,
                'graphql_field_name' => 'isFullWithPage',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'page',
                ],
            ],
        ],
        'show_in_graphql' => 1,
        'graphql_field_name' => 'ncPageMeta',
        'graphql_types' => ['Page'],
    ]);
});
