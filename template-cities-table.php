<?php
/**
 * Template Name: Cities Table
 *
 * Custom template for displaying countries, cities, and temperatures.
 *
 * @package storefront-child
 */

get_header(); ?>

    <div id="primary" class="content-area">
        <main id="main" class="site-main" role="main">

            <h1><?php the_title(); ?></h1>

            <input type="text" id="city-search" placeholder="<?php esc_attr_e( 'Search cities...', 'storefront-child' ); ?>">

            <?php
            /**
             * Action hook before the table.
             */
            do_action( 'before_cities_table' );
            ?>

            <table id="cities-table">
                <thead>
                <tr>
                    <th><?php _e( 'Country', 'storefront-child' ); ?></th>
                    <th><?php _e( 'City', 'storefront-child' ); ?></th>
                    <th><?php _e( 'Temperature (°C)', 'storefront-child' ); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php
                global $wpdb;
                
                $per_page = 10;
                $page = isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 1;
                $offset = ( $page - 1 ) * $per_page;

                $total_cache_key = 'cities_total_count';
                $total = get_transient( $total_cache_key );
                if ( false === $total ) {
                    $total = $wpdb->get_var(
                        "SELECT COUNT(p.ID)
                        FROM {$wpdb->posts} p
                        LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
                        LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                        WHERE p.post_type = 'cities' AND p.post_status = 'publish' AND tt.taxonomy = 'countries'"
                    );
                    set_transient( $total_cache_key, $total, HOUR_IN_SECONDS );
                }

                $total_pages = ceil( $total / $per_page );

                // Cache key for the query results (include page for pagination)
                $cache_key = 'cities_table_results_page_' . $page;
                $results = get_transient( $cache_key );

                if ( false === $results ) {
                    // Optimized query with LIMIT and OFFSET
                    $results = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT 
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
                            ORDER BY t.name, p.post_title
                            LIMIT %d OFFSET %d",
                            $per_page,
                            $offset
                        )
                    );
                    set_transient( $cache_key, $results, HOUR_IN_SECONDS );
                }

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
                ?>
                </tbody>
            </table>
            
            <div class="pagination">
                <?php
                $pagination_args = array(
                    'base' => add_query_arg( 'paged', '%#%' ),
                    'format' => '',
                    'current' => $page,
                    'total' => $total_pages,
                    'prev_text' => '&laquo; Prev',
                    'next_text' => 'Next &raquo;',
                );
                echo paginate_links( $pagination_args );
                ?>
            </div>

            <?php
            /**
             * Action hook after the table.
             */
            do_action( 'after_cities_table' );
            ?>

        </main>
    </div>

<?php get_footer(); ?>