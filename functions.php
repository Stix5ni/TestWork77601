<?php
/**
 * Functions for Storefront Child theme.
 *
 * @package storefront-child
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue parent and child theme styles.
 */
function storefront_child_enqueue_styles() {
    wp_enqueue_style( 'storefront-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'storefront-child-style', get_stylesheet_directory_uri() . '/style.css', array( 'storefront-style' ) );
}
add_action( 'wp_enqueue_scripts', 'storefront_child_enqueue_styles' );

/**
 * Add database indexes on theme activation.
 */
function add_db_indexes_on_activation() {
    global $wpdb;
    if ( ! get_option( 'cities_table_indexes_added' ) ) {
        $queries = array(
            "CREATE INDEX IF NOT EXISTS idx_post_title ON {$wpdb->posts} (post_title)",
            "CREATE INDEX IF NOT EXISTS idx_meta_key ON {$wpdb->postmeta} (meta_key)",
            "CREATE INDEX IF NOT EXISTS idx_taxonomy ON {$wpdb->term_taxonomy} (taxonomy)"
        );
        foreach ( $queries as $query ) {
            $result = $wpdb->query( $query );
            if ( false === $result ) {
                error_log( "Failed to create index: $query. Error: {$wpdb->last_error}" );
            }
        }
        update_option( 'cities_table_indexes_added', true );
        error_log( "Database indexes created successfully" );
    }
}
add_action( 'after_switch_theme', 'add_db_indexes_on_activation' );

/**
 * Clear cities table cache when a city is saved or taxonomy is updated.
 *
 * @param int $post_id The post ID.
 */
function clear_cities_table_cache( $post_id ) {
    if ( 'cities' === get_post_type( $post_id ) ) {
        clear_cities_cache();
    }
}

/**
 * Clear cities cache when taxonomy relationships are updated.
 *
 * @param int    $object_id Object ID.
 * @param array  $terms     An array of object terms.
 * @param array  $tt_ids    An array of term taxonomy IDs.
 * @param string $taxonomy  Taxonomy slug.
 */
function clear_cities_cache_on_taxonomy_update( $object_id, $terms, $tt_ids, $taxonomy ) {
    if ( 'countries' === $taxonomy && 'cities' === get_post_type( $object_id ) ) {
        clear_cities_cache();
    }
}

/**
 * Clear cities cache when a city is deleted.
 *
 * @param int $post_id The post ID.
 */
function clear_cities_cache_on_delete( $post_id ) {
    if ( 'cities' === get_post_type( $post_id ) ) {
        clear_cities_cache();
    }
}

/**
 * Clear cities cache when taxonomy terms are updated.
 *
 * @param int    $term_id  Term ID.
 * @param int    $tt_id    Term taxonomy ID.
 * @param string $taxonomy Taxonomy slug.
 */
function clear_cities_cache_on_term_update( $term_id, $tt_id, $taxonomy ) {
    if ( 'countries' === $taxonomy ) {
        clear_cities_cache();
    }
}

/**
 * Actually clear all cities-related cache.
 */
function clear_cities_cache() {
    global $wpdb;

    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cities_table_results%'" );
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cities_total_count%'" );
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cities_ajax_results%'" );


    error_log( 'Cities cache cleared successfully' );
}

add_action( 'save_post', 'clear_cities_table_cache' );
add_action( 'wp_set_object_terms', 'clear_cities_cache_on_taxonomy_update', 10, 4 );
add_action( 'delete_post', 'clear_cities_cache_on_delete' );
add_action( 'edit_term', 'clear_cities_cache_on_term_update', 10, 3 );
add_action( 'delete_term', 'clear_cities_cache_on_term_update', 10, 3 );

// Include custom files
require_once get_stylesheet_directory() . '/inc/cpt-cities.php';
require_once get_stylesheet_directory() . '/inc/taxonomy-countries.php';
require_once get_stylesheet_directory() . '/inc/metabox-cities.php';
require_once get_stylesheet_directory() . '/inc/helpers.php';
require_once get_stylesheet_directory() . '/inc/widget-city-weather.php';
require_once get_stylesheet_directory() . '/inc/ajax-search.php';

/**
 * Flush rewrite rules on theme activation (only once).
 */
function storefront_child_flush_rewrite_rules() {
    if ( ! get_option( 'storefront_child_rewrite_flushed' ) ) {
        flush_rewrite_rules();
        update_option( 'storefront_child_rewrite_flushed', true );
    }
}
add_action( 'after_switch_theme', 'storefront_child_flush_rewrite_rules' );

/**
 * Reset rewrite flush flag on theme deactivation.
 */
function storefront_child_deactivation() {
    delete_option( 'storefront_child_rewrite_flushed' );
    delete_option( 'cities_table_indexes_added' );
}
add_action( 'switch_theme', 'storefront_child_deactivation' );
?>