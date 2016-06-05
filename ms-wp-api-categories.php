<?php
/*
Plugin Name: WP REST API Categories
Plugin URI:  http://www.makeandship.co.uk/
Description: Adds categories to WP-API
Version:     0.1
Author:      Make and Ship Limited
Author URI:  http://www.makeandship.co.uk/
License:     MIT
License URI: https://opensource.org/licenses/MIT
*/

# Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MS_WP_API_Categories {

    function __construct() {
        add_action( 'rest_api_init', array( $this, 'add_categories_to_posts') );
        add_action( 'rest_api_init', array( $this, 'add_all_categories_route') );
    }

    function add_all_categories_route() {
        register_rest_route( 'wp/v2/categories', '/all', array(
            'methods' => array(
                WP_REST_Server::READABLE
            ),
            'callback' => array( $this, 'add_all_categories_route_callback' )
        ) );
    }

    function add_all_categories_route_callback( WP_REST_Request $request ) {
        $prepared_args = array(
            'exclude'    => $request['exclude'],
            'include'    => $request['include'],
            'order'      => $request['order'],
            'orderby'    => $request['orderby'],
            'post'       => $request['post'],
            'hide_empty' => $request['hide_empty'],
            'search'     => $request['search'],
            'slug'       => $request['slug'],
        );

        if ( $request['parent'] ) {
            $prepared_args['parent'] = $request['parent'];
        }

        $query_result = get_terms( get_taxonomies(), $prepared_args );

        $response = array();

        // format in the same way as rest-api plugin
        foreach ( $query_result as $item ) {
            $data = array(
                'id'           => (int) $item->term_id,
                'count'        => (int) $item->count,
                'description'  => $item->description,
                'link'         => get_term_link( $item ),
                'name'         => $item->name,
                'slug'         => $item->slug,
                'taxonomy'     => $item->taxonomy,
                'parent'       => $item->parent,
            );
            $response[] = $data;
        }

        $response = rest_ensure_response( $response );

        return $response;
    }

    function add_categories_to_posts() {
        // Posts
        register_rest_field( 'post',
            'category_ids',
            array(
                'get_callback'    => array( $this, 'add_categories_callback' ),
                'update_callback' => array( $this, 'update_categories_callback' ),
                'schema'          => null,
            )
        );

        // Pages
        register_rest_field( 'page',
            'category_ids',
            array(
                'get_callback'    => array( $this, 'add_categories_callback' ),
                'update_callback' => array( $this, 'update_categories_callback' ),
                'schema'          => null,
            )
        );

        // Public custom post types
        $types = get_post_types(array(
            'public' => true,
            '_builtin' => false
        ));
        foreach($types as $key => $type) {
            register_rest_field( $type,
                'category_ids',
                array(
                    'get_callback'    => array( $this, 'add_categories_callback' ),
                    'update_callback' => array( $this, 'update_categories_callback' ),
                    'schema'          => null,
                )
            );
        }
    }

    function add_categories_callback($object, $fieldName, $request) {
        $post_categories = wp_get_post_categories($object['id']);
        return $post_categories;
    }

    function update_categories_callback($categories, $object, $field_name) {
        if (empty($categories) || !$categories) {
            return;
        }
        if (is_string($categories)) {
            $categories = explode(",",$categories);
        }
        return wp_set_post_categories($object->ID,$categories);
    }
}

$MS_WP_API_Categories = new MS_WP_API_Categories();
