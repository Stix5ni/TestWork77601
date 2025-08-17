<?php
/**
 * Metabox for Cities: Latitude and Longitude.
 *
 * @package storefront-child
 */

/**
 * Add metabox to Cities edit screen.
 */
function cities_metabox_add() {
    add_meta_box(
        'cities_coords',
        __( 'City Coordinates', 'storefront-child' ),
        'cities_metabox_callback',
        'cities',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'cities_metabox_add' );

/**
 * Metabox callback: Render fields.
 *
 * @param WP_Post $post The post object.
 */
function cities_metabox_callback( $post ) {
    wp_nonce_field( 'cities_coords_nonce', 'cities_coords_nonce' );

    $latitude = get_post_meta( $post->ID, '_city_latitude', true );
    $longitude = get_post_meta( $post->ID, '_city_longitude', true );

    // Show validation errors if any
    $lat_error = get_transient( 'cities_lat_error_' . $post->ID );
    $lon_error = get_transient( 'cities_lon_error_' . $post->ID );

    if ( $lat_error ) {
        echo '<div class="notice notice-error inline"><p>' . esc_html( $lat_error ) . '</p></div>';
        delete_transient( 'cities_lat_error_' . $post->ID );
    }

    if ( $lon_error ) {
        echo '<div class="notice notice-error inline"><p>' . esc_html( $lon_error ) . '</p></div>';
        delete_transient( 'cities_lon_error_' . $post->ID );
    }

    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th><label for="city_latitude">' . __( 'Latitude:', 'storefront-child' ) . '</label></th>';
    echo '<td>';
    echo '<input type="text" id="city_latitude" name="city_latitude" value="' . esc_attr( $latitude ) . '" class="regular-text" />';
    echo '<p class="description">Широта должна быть от -90 до 90 градусов</p>';
    echo '</td>';
    echo '</tr>';

    echo '<tr>';
    echo '<th><label for="city_longitude">' . __( 'Longitude:', 'storefront-child' ) . '</label></th>';
    echo '<td>';
    echo '<input type="text" id="city_longitude" name="city_longitude" value="' . esc_attr( $longitude ) . '" class="regular-text" />';
    echo '<p class="description">Долгота должна быть от -180 до 180 градусов</p>';
    echo '</td>';
    echo '</tr>';
    echo '</table>';

    echo '<style>
        .notice.inline { margin: 5px 0 15px; padding: 5px 12px; }
        .form-table th { width: 150px; }
    </style>';
}

/**
 * Save metabox data with proper validation.
 *
 * @param int $post_id The post ID.
 */
function cities_metabox_save( $post_id ) {
    // Security checks
    if ( ! isset( $_POST['cities_coords_nonce'] ) || ! wp_verify_nonce( $_POST['cities_coords_nonce'], 'cities_coords_nonce' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $has_errors = false;

    // Validate and save latitude
    if ( isset( $_POST['city_latitude'] ) ) {
        $lat = sanitize_text_field( $_POST['city_latitude'] );

        if ( empty( $lat ) ) {
            delete_post_meta( $post_id, '_city_latitude' );
        } elseif ( ! is_numeric( $lat ) ) {
            set_transient( 'cities_lat_error_' . $post_id, 'Широта должна быть числом', 45 );
            $has_errors = true;
        } elseif ( $lat < -90 || $lat > 90 ) {
            set_transient( 'cities_lat_error_' . $post_id, 'Широта должна быть от -90 до 90 градусов', 45 );
            $has_errors = true;
        } else {
            update_post_meta( $post_id, '_city_latitude', $lat );
            // Clear any previous weather cache for this city
            $old_lon = get_post_meta( $post_id, '_city_longitude', true );
            if ( $old_lon ) {
                clear_weather_cache( $lat, $old_lon );
            }
        }
    }

    // Validate and save longitude
    if ( isset( $_POST['city_longitude'] ) ) {
        $lon = sanitize_text_field( $_POST['city_longitude'] );

        if ( empty( $lon ) ) {
            delete_post_meta( $post_id, '_city_longitude' );
        } elseif ( ! is_numeric( $lon ) ) {
            set_transient( 'cities_lon_error_' . $post_id, 'Долгота должна быть числом', 45 );
            $has_errors = true;
        } elseif ( $lon < -180 || $lon > 180 ) {
            set_transient( 'cities_lon_error_' . $post_id, 'Долгота должна быть от -180 до 180 градусов', 45 );
            $has_errors = true;
        } else {
            update_post_meta( $post_id, '_city_longitude', $lon );
            // Clear any previous weather cache for this city
            $old_lat = get_post_meta( $post_id, '_city_latitude', true );
            if ( $old_lat ) {
                clear_weather_cache( $old_lat, $lon );
            }
        }
    }

    if ( $has_errors ) {
        set_transient( 'cities_validation_errors_' . $post_id, true, 45 );
    }
}
add_action( 'save_post', 'cities_metabox_save' );

/**
 * Show admin notice after redirect if there were validation errors.
 */
function cities_show_validation_notice() {
    if ( isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) === 'cities' ) {
        $post_id = intval( $_GET['post'] );
        if ( get_transient( 'cities_validation_errors_' . $post_id ) ) {
            echo '<div class="notice notice-error"><p><strong>Внимание:</strong> Некоторые поля содержали ошибки и не были сохранены. Проверьте координаты ниже.</p></div>';
            delete_transient( 'cities_validation_errors_' . $post_id );
        }
    }
}
add_action( 'admin_notices', 'cities_show_validation_notice' );

/**
 * Add client-side validation for better UX.
 */
function cities_admin_scripts() {
    if ( get_current_screen() && get_current_screen()->post_type === 'cities' ) {
        ?>
        <script>
            jQuery(document).ready(function($) {
                function validateCoordinate(input, min, max, name) {
                    var value = parseFloat(input.val());
                    var errorMsg = '';

                    if (input.val() !== '' && (isNaN(value) || value < min || value > max)) {
                        errorMsg = name + ' должна быть числом от ' + min + ' до ' + max;
                        input.css('border-color', '#dc3232');
                    } else {
                        input.css('border-color', '');
                    }

                    input.next('.coord-error').remove();

                    if (errorMsg) {
                        input.after('<div class="coord-error" style="color: #dc3232; font-size: 12px; margin-top: 2px;">' + errorMsg + '</div>');
                    }

                    return !errorMsg;
                }

                $('#city_latitude').on('blur keyup', function() {
                    validateCoordinate($(this), -90, 90, 'Широта');
                });

                $('#city_longitude').on('blur keyup', function() {
                    validateCoordinate($(this), -180, 180, 'Долгота');
                });

                // Validate on form submit
                $('#post').on('submit', function(e) {
                    var latValid = validateCoordinate($('#city_latitude'), -90, 90, 'Широта');
                    var lonValid = validateCoordinate($('#city_longitude'), -180, 180, 'Долгота');

                    if (!latValid || !lonValid) {
                        e.preventDefault();
                        $('html, body').animate({
                            scrollTop: $('#cities_coords').offset().top - 100
                        }, 500);
                        alert('Пожалуйста, исправьте ошибки в координатах перед сохранением.');
                    }
                });
            });
        </script>
        <?php
    }
}
add_action( 'admin_footer', 'cities_admin_scripts' );
?>