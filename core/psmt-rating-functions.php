<?php
/**
 * File contains core plugin functions
 *
 * @package psmt-media-rating
 */

// Exit if file accessed directly over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get average rating for the given media
 *
 * @param int $media_id Media id.
 *
 * @return int|null
 */
function psmt_rating_get_average_rating( $media_id ) {

	global $wpdb;

	$table_name = psmt_rating_get_table_name();

	if ( ! $media_id ) {
		return;
	}

	$query   = $wpdb->prepare( "SELECT AVG(rating) FROM {$table_name} WHERE media_id = %d", $media_id );
	$average = $wpdb->get_var( $query );

	if ( is_null( $average ) ) {
		$average = 0;
	}

	return absint( $average );
}

/**
 * Check if current user can rate
 *
 * @return bool
 */
function psmt_rating_current_user_can_rate() {

	$allow  = false;
	$who_can_rate   = psmt_get_option( 'psmt-rating-required-permission' );

	if ( 'any' == $who_can_rate ) {
		$allow = true;
	} elseif ( 'loggedin' == $who_can_rate && is_user_logged_in() ) {
		$allow = true;
	}

	return apply_filters( 'psmt_rating_current_user_can_rate', $allow );
}

/**
 * Is given media type rateable or not based on media component/type
 *
 * @param int $media_id Media id.
 *
 * @return bool
 */
function psmt_rating_is_media_rateable( $media_id ) {

	if ( ! $media_id ) {
		return false;
	}

	$media = psmt_get_media( $media_id );

	if ( is_null( $media ) ) {
		return false;
	}

	$can_be_rated = true;

	$component_can_be_rated = (array) psmt_get_option( 'psmt-rating-rateable-components' );
	$type_can_be_rated      = (array) psmt_get_option( 'psmt-rating-rateable-types' );

	if ( ! $component_can_be_rated || ! $type_can_be_rated ) {
		$can_be_rated = false;
	} elseif ( ! in_array( $media->component, $component_can_be_rated ) ) {
		$can_be_rated = false;
	} elseif ( ! in_array( $media->type, $type_can_be_rated ) ) {
		$can_be_rated = false;
	}

	return apply_filters( 'psmt_rating_is_media_rateable', $can_be_rated );
}

/**
 * Check if user has rated on media or not.
 *
 * @param int $user_id  User Id.
 * @param int $media_id Media Id.
 *
 * @return bool
 */
function psmt_rating_has_user_rated( $user_id, $media_id ) {

	global $wpdb;

	if ( ! $user_id || ! $media_id ) {
		return false;
	}

	$table_name = psmt_rating_get_table_name();

	$query = $wpdb->prepare( "SELECT id FROM {$table_name} WHERE user_id = %d AND media_id = %d", $user_id, $media_id );

	$result = $wpdb->get_row( $query );

	if ( is_null( $result ) ) {
		return false;
	}

	return true;
}

/**
 * Get media rating table
 *
 * @return string
 */
function psmt_rating_get_table_name() {
	global $wpdb;
	return $wpdb->prefix . 'psmt_media_rating';
}

/**
 * Check if media is read only rating i.e. user already rated on this media
 *
 * @param int $media_id Media id.
 *
 * @return bool|null
 */
function psmt_rating_is_read_only_media_rating( $media_id ) {

	if ( ! $media_id ) {
		return;
	}

	if ( ! psmt_rating_current_user_can_rate() || psmt_rating_has_user_rated( get_current_user_id(), $media_id ) ) {
		return true;
	}

	return false;
}

/**
 * Get media ids
 *
 * @param array $args Array values.
 *
 * @return array
 */
function psmt_rating_get_media_ids( $args ) {
	$args = wp_parse_args( $args, array(
		'component'    => 'members',
		'component_id' => false,
		'status'       => '',
		'type'         => 'photo',
	) );

	$args['post_status'] = 'inherit';

	$media_ids = psmt_get_object_ids( $args, psmt_get_media_post_type() );

	return $media_ids;
}

/**
 * Get top rated media
 *
 * @param array $args     Media args.
 * @param int   $interval Interval.
 * @param int   $limit    Limit.
 *
 * @return array|bool
 */
function psmt_rating_get_top_rated_media( $args = array(), $interval = 7, $limit = 0 ) {
    global $wpdb;

    $ids = psmt_rating_get_media_ids( $args );
    if ( empty( $ids ) ) {
	    return false;
    }

	$interval    = absint( $interval );
	$ids         = join( ',', $ids );
	$limit_query = '';

	if ( ! empty( $limit ) ) {
		$limit_query = $wpdb->prepare( 'LIMIT 0 , %d', $limit );
    }

	$query     = $wpdb->prepare( "SELECT media_id FROM {$wpdb->prefix}psmt_media_rating WHERE 1 =1 AND ( date >= DATE(NOW()) - INTERVAL %d DAY ) AND media_id IN ( {$ids} ) GROUP BY media_id ORDER BY avg( rating ) DESC {$limit_query}", $interval );
	$media_ids = $wpdb->get_results( $query, 'ARRAY_A' );

	if ( empty( $media_ids ) ) {
		return false;
	}

	return wp_list_pluck( $media_ids, 'media_id' );
}

/**
 * Get component that can be rated
 *
 * @return array
 */
function psmt_rating_get_rateable_components() {

	$active_components = psmt_get_active_components();

	$rateable_components = array();

	foreach ( $active_components as $key => $component ) {
		$rateable_components[ $key ] = $component->label;
	}

	return apply_filters( 'psmt_rating_component_can_be_rated', $rateable_components );
}

/**
 * Get rating permissions.
 *
 * @return array
 */
function psmt_rating_get_rating_permissions() {

	$who_can_rate = array(
		'any'      => __( '??ffentlich', 'psmt-media-rating' ),
		'loggedin' => __( 'Eingeloggt', 'psmt-media-rating' ),
	);

	return apply_filters( 'psmt_rating_who_can_rate', $who_can_rate );
}

/**
 * Rating html
 *
 * @param int $media_id Media id.
 * @param int $readonly Read on mode.
 */
function psmt_rating_get_rating_html( $media_id, $readonly ) {

	$average = psmt_rating_get_average_rating( $media_id );

	?>
	<select id="psmt-rating-value-<?php echo $media_id; ?>" style="display: none">
		<option value="1" <?php selected( 1, $average )?>>1</option>
		<option value="2" <?php selected( 2, $average )?>>2</option>
		<option value="3" <?php selected( 3, $average )?>>3</option>
		<option value="4" <?php selected( 4, $average )?>>4</option>
		<option value="5" <?php selected( 5, $average )?>>5</option>
	</select>
	<div class="psmt-media-rating" data-rateit-readonly="<?php echo esc_attr( $readonly ); ?>" data-media-id="<?php echo $media_id; ?>" data-rateit-backingfld="#psmt-rating-value-<?php echo $media_id; ?>"></div>

	<?php
}

/**
 * List media of items like logged_in user or displayed user for BuddyPress
 *
 * @return array
 */
function psmt_rating_show_media_of() {

	$options = array(
		'loggedin' => __( 'Angemeldeter Benutzer', 'psmt-media-rating' ),
		'any'      => __( 'Jedermann', 'psmt-media-rating' ),
	);

	if ( function_exists( 'buddypress' ) ) {
		$options['displayed'] = __( 'Angezeigter Benutzer', 'psmt-media-rating' );
	}

	return $options;
}

/**
 * Get an associative array of time duration options
 *
 * @return array
 */
function psmt_rating_get_intervals() {

	$intervals = array(
		7   => __( 'Letzte Woche', 'psmt-media-rating' ),
		30  => __( 'Letzten Monat', 'psmt-media-rating' ),
		365 => __( 'Letztes Jahr', 'psmt-media-rating' ),
	);

	return apply_filters( 'psmt_rating_intervals', $intervals );
}
