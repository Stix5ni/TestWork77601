<?php
/**
 * Custom Taxonomy: Countries.
 *
 * @package storefront-child
 */

/**
 * Register Countries taxonomy for Cities CPT.
 */
function register_taxonomy_countries() {
    $labels = array(
        'name'              => __( 'Countries', 'storefront-child' ),
        'singular_name'     => __( 'Country', 'storefront-child' ),
        'search_items'      => __( 'Search Countries', 'storefront-child' ),
        'all_items'         => __( 'All Countries', 'storefront-child' ),
        'parent_item'       => __( 'Parent Country', 'storefront-child' ),
        'parent_item_colon' => __( 'Parent Country:', 'storefront-child' ),
        'edit_item'         => __( 'Edit Country', 'storefront-child' ),
        'update_item'       => __( 'Update Country', 'storefront-child' ),
        'add_new_item'      => __( 'Add New Country', 'storefront-child' ),
        'new_item_name'     => __( 'New Country Name', 'storefront-child' ),
        'menu_name'         => __( 'Countries', 'storefront-child' ),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'countries' ),
    );

    register_taxonomy( 'countries', array( 'cities' ), $args );
}
add_action( 'init', 'register_taxonomy_countries' );
?>