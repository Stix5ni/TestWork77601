<?php
/**
 * Widget: City Weather.
 *
 * @package storefront-child
 */

/**
 * City Weather Widget class.
 */
class City_Weather_Widget extends WP_Widget {

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(
            'city_weather_widget',
            __( 'City Weather', 'storefront-child' ),
            array( 'description' => __( 'Displays city name and current temperature.', 'storefront-child' ) )
        );
    }

    /**
     * Widget frontend output.
     *
     * @param array $args     Widget args.
     * @param array $instance Widget instance.
     */
    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        if ( ! empty( $instance['city_id'] ) ) {
            $city = get_post( $instance['city_id'] );
            if ( $city ) {
                $lat = get_post_meta( $city->ID, '_city_latitude', true );
                $lon = get_post_meta( $city->ID, '_city_longitude', true );
                $temp = get_temperature( $lat, $lon );

                echo '<h2>' . esc_html( $city->post_title ) . '</h2>';
                echo '<p>' . __( 'Temperature: ', 'storefront-child' ) . esc_html( $temp ) . '</p>';
            }
        }

        echo $args['after_widget'];
    }

    /**
     * Widget backend form.
     *
     * @param array $instance Widget instance.
     */
    public function form( $instance ) {
        $city_id = ! empty( $instance['city_id'] ) ? $instance['city_id'] : '';

        $cities = get_posts( array(
            'post_type'      => 'cities',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );

        echo '<p>';
        echo '<label for="' . esc_attr( $this->get_field_id( 'city_id' ) ) . '">' . __( 'Select City:', 'storefront-child' ) . '</label>';
        echo '<select id="' . esc_attr( $this->get_field_id( 'city_id' ) ) . '" name="' . esc_attr( $this->get_field_name( 'city_id' ) ) . '">';
        echo '<option value="">' . __( '-- Select --', 'storefront-child' ) . '</option>';
        foreach ( $cities as $city ) {
            echo '<option value="' . esc_attr( $city->ID ) . '" ' . selected( $city_id, $city->ID ) . '>' . esc_html( $city->post_title ) . '</option>';
        }
        echo '</select>';
        echo '</p>';
    }

    /**
     * Update widget instance.
     *
     * @param array $new_instance New instance.
     * @param array $old_instance Old instance.
     * @return array Updated instance.
     */
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['city_id'] = ! empty( $new_instance['city_id'] ) ? sanitize_text_field( $new_instance['city_id'] ) : '';
        return $instance;
    }
}

/**
 * Register the widget.
 */
function register_city_weather_widget() {
    register_widget( 'City_Weather_Widget' );
}
add_action( 'widgets_init', 'register_city_weather_widget' );
?>