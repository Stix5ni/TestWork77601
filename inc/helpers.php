<?php
/**
 * Helper functions for Storefront Child theme.
 *
 * @package storefront-child
 */

/**
 * Get temperature for a given latitude and longitude.
 *
 * @param float $lat Latitude.
 * @param float $lon Longitude.
 * @return string Temperature in Celsius or error message.
 */
function get_temperature( $lat, $lon ) {
    if ( empty( $lat ) || empty( $lon ) || ! is_numeric( $lat ) || ! is_numeric( $lon ) ) {
        return 'N/A';
    }

    if ( $lat < -90 || $lat > 90 || $lon < -180 || $lon > 180 ) {
        return 'Invalid coordinates';
    }

    // Get API key from wp-config.php
    $api_key = defined( 'OPENWEATHER_API_KEY' ) ? OPENWEATHER_API_KEY : '';
    if ( empty( $api_key ) ) {
        return 'API key not configured';
    }

    $cache_key = 'weather_' . md5( $lat . '_' . $lon );
    $cached_temp = get_transient( $cache_key );

    // Return cached result (including error messages)
    if ( false !== $cached_temp ) {
        return $cached_temp;
    }

    $url = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&units=metric&appid={$api_key}";

    // Set timeout for API request
    $response = wp_remote_get( $url, array(
        'timeout' => 5,
        'sslverify' => false
    ) );

    if ( is_wp_error( $response ) ) {
        $error_result = 'Connection error';
        // Cache error for shorter time to retry sooner
        set_transient( $cache_key, $error_result, 5 * MINUTE_IN_SECONDS );
        error_log( 'Weather API Error: ' . $response->get_error_message() );
        return $error_result;
    }

    $response_code = wp_remote_retrieve_response_code( $response );
    $response_body = wp_remote_retrieve_body( $response );

    // Handle API errors (400, 401, 404, etc.)
    if ( $response_code !== 200 ) {
        $error_result = 'API Error (' . $response_code . ')';

        // Cache API errors for longer time since coordinates won't change
        set_transient( $cache_key, $error_result, HOUR_IN_SECONDS );

        error_log( "Weather API Error {$response_code} for coords {$lat},{$lon}: " . $response_body );
        return $error_result;
    }

    $data = json_decode( $response_body, true );

    if ( isset( $data['main']['temp'] ) ) {
        $temp = round( $data['main']['temp'] ) . '°C';
        // Cache successful result for 1 hour
        set_transient( $cache_key, $temp, HOUR_IN_SECONDS );
        return $temp;
    }

    $error_result = 'Invalid response';
    set_transient( $cache_key, $error_result, HOUR_IN_SECONDS );
    return $error_result;
}

/**
 * Batch get temperatures for multiple coordinates.
 * More efficient for loading tables with many cities.
 *
 * @param array $coordinates Array of ['lat' => x, 'lon' => y, 'id' => city_id]
 * @return array Array of temperatures keyed by city_id
 */
function get_temperatures_batch( $coordinates ) {
    $results = array();
    
    foreach ( $coordinates as $coord ) {
        $cache_key = 'weather_' . md5( $coord['lat'] . '_' . $coord['lon'] );
        $cached = get_transient( $cache_key );

        if ( false !== $cached ) {
            $results[ $coord['id'] ] = $cached;
        } else {
            // If not cached, get individual temperature
            $results[ $coord['id'] ] = get_temperature( $coord['lat'], $coord['lon'] );
        }
    }

    return $results;
}

/**
 * Clear weather cache for specific coordinates.
 *
 * @param float $lat Latitude.
 * @param float $lon Longitude.
 */
function clear_weather_cache( $lat, $lon ) {
    if ( ! empty( $lat ) && ! empty( $lon ) ) {
        $cache_key = 'weather_' . md5( $lat . '_' . $lon );
        delete_transient( $cache_key );
    }
}
?>