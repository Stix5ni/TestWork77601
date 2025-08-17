<?php
/**
 * AJAX Search for Cities with Table Filtering.
 *
 * @package storefront-child
 */

/**
 * Enqueue JS for AJAX search.
 */
function enqueue_ajax_search_script() {
    if ( is_page_template( 'template-cities-table.php' ) ) {
        wp_enqueue_script( 'cities-ajax-search', get_stylesheet_directory_uri() . '/js/ajax-search.js', array( 'jquery' ), '1.4', true );
        wp_localize_script( 'cities-ajax-search', 'ajax_params', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'cities_search_nonce' ),
        ) );
    }
}
add_action( 'wp_enqueue_scripts', 'enqueue_ajax_search_script' );

/**
 * AJAX handler for city search and table filtering with pagination.
 */
function cities_ajax_search() {
    check_ajax_referer( 'cities_search_nonce', 'nonce' );

    $search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
    $per_page = 10;
    $page = isset( $_POST['paged'] ) ? intval( $_POST['paged'] ) : 1;
    $offset = ( $page - 1 ) * $per_page;

    global $wpdb;

    // Cache key for total count
    $total_cache_key = 'cities_total_count_' . md5( $search );
    $total = get_transient( $total_cache_key );
    if ( false === $total ) {
        $count_query = "
            SELECT COUNT(p.ID)
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE p.post_type = 'cities' AND p.post_status = 'publish' AND tt.taxonomy = 'countries'
        ";

        if ( ! empty( $search ) ) {
            $count_query .= $wpdb->prepare( " AND p.post_title LIKE %s", '%' . $wpdb->esc_like( $search ) . '%' );
        }

        $total = $wpdb->get_var( $count_query );
        set_transient( $total_cache_key, $total, 5 * MINUTE_IN_SECONDS );
    }

    $total_pages = ceil( $total / $per_page );

    // Cache key for query results
    $cache_key = 'cities_ajax_results_' . $page . '_' . md5( $search );
    $results = get_transient( $cache_key );

    if ( false === $results ) {
        $query = "
            SELECT 
                p.ID AS city_id,
                p.post_title AS city_name,
                t.name AS country_name,
                lat.meta_value AS latitude,
                lon.meta_value AS longitude
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} lat ON p.ID = lat.post_id AND lat.meta_key = '_city_latitude'
            LEFT JOIN {$wpdb->postmeta} lon ON p.ID = lon.post_id AND lon.meta_key = '_city_longitude'
            LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
            WHERE p.post_type = 'cities' AND p.post_status = 'publish' AND tt.taxonomy = 'countries'
        ";

        if ( ! empty( $search ) ) {
            $query .= $wpdb->prepare( " AND p.post_title LIKE %s", '%' . $wpdb->esc_like( $search ) . '%' );
        }

        $query .= " ORDER BY t.name, p.post_title LIMIT $per_page OFFSET $offset";

        $results = $wpdb->get_results( $query );
        set_transient( $cache_key, $results, 5 * MINUTE_IN_SECONDS );
    }

    ob_start();
    if ( $results ) {
        foreach ( $results as $row ) {
            $temp = get_temperature( $row->latitude, $row->longitude );
            echo '<tr>';
            echo '<td>' . esc_html( $row->country_name ) . '</td>';
            echo '<td>' . esc_html( $row->city_name ) . '</td>';
            echo '<td>' . esc_html( $temp ) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="3">' . __( 'No data found.', 'storefront-child' ) . '</td></tr>';
    }
    $table_rows = ob_get_clean();

    $pagination_args = array(
        'base' => add_query_arg( 'paged', '%#%' ),
        'format' => '',
        'current' => $page,
        'total' => $total_pages,
        'prev_text' => '&laquo; Prev',
        'next_text' => 'Next &raquo;',
    );
    ob_start();
    echo '<div class="pagination">';
    echo paginate_links( $pagination_args );
    echo '</div>';
    $pagination_html = ob_get_clean();
    
    wp_send_json( array(
        'table_rows' => $table_rows,
        'pagination' => $pagination_html,
    ) );

    wp_die();
}
add_action( 'wp_ajax_cities_search', 'cities_ajax_search' );
add_action( 'wp_ajax_nopriv_cities_search', 'cities_ajax_search' );
?>