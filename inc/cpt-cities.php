<?php
/**
 * Custom Post Type: Cities.
 *
 * @package storefront-child
 */

/**
 * Register Cities CPT.
 */
function register_cpt_cities() {
    $labels = array(
        'name'               => __( 'Cities', 'storefront-child' ),
        'singular_name'      => __( 'City', 'storefront-child' ),
        'menu_name'          => __( 'Cities', 'storefront-child' ),
        'name_admin_bar'     => __( 'City', 'storefront-child' ),
        'add_new'            => __( 'Add New', 'storefront-child' ),
        'add_new_item'       => __( 'Add New City', 'storefront-child' ),
        'new_item'           => __( 'New City', 'storefront-child' ),
        'edit_item'          => __( 'Edit City', 'storefront-child' ),
        'view_item'          => __( 'View City', 'storefront-child' ),
        'all_items'          => __( 'All Cities', 'storefront-child' ),
        'search_items'       => __( 'Search Cities', 'storefront-child' ),
        'not_found'          => __( 'No cities found.', 'storefront-child' ),
        'not_found_in_trash' => __( 'No cities found in Trash.', 'storefront-child' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'cities' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array( 'title', 'editor', 'thumbnail' ),
    );

    register_post_type( 'cities', $args );
}
add_action( 'init', 'register_cpt_cities' );
?>