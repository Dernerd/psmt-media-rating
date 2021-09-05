<?php
/**
 * Plugin shortcode file
 *
 * @package psmt-media-rating
 */

// Exit if file accessed directly over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Modify Psource Mediathek shortcode default array to list media
 *
 * @param array $default Array of supported attributes.
 *
 * @return array
 */
function psmt_rating_modify_default_args( $default ) {
	$default['top-rated'] = 0;
	$default['rating-interval'] = 7;
	return $default;
}

add_filter( 'psmt_shortcode_list_media_defaults', 'psmt_rating_modify_default_args' );

/**
 * Modify shortcode media query
 *
 * @param array $atts Array of attributes.
 *
 * @return array
 */
function psmt_rating_modify_media_args( $atts ) {

	if ( isset( $atts['top-rated'] ) && 1 == $atts['top-rated'] ) {

		$media_ids = psmt_rating_get_top_rated_media( array(
			'component'    => $atts['component'],
			'component_id' => $atts['component_id'],
			'status'       => $atts['status'],
			'type'         => $atts['type'],
		), $atts['rating-interval'], $atts['per_page'] );

		$media_ids       = ( $media_ids ) ? $media_ids : array( 0 );
		$atts['in']      = $media_ids;
		$atts['orderby'] = 'post__in';
	}

	return $atts;
}

add_filter( 'psmt_shortcode_list_media_query_args', 'psmt_rating_modify_media_args' );

/**
 * Add rating html in shortcode media list
 */
function psmt_rating_show_rating() {
	echo psmt_rating_get_rating_html( psmt_get_current_media_id(), 1 );
}

add_action( 'psmt_media_shortcode_item_meta', 'psmt_rating_show_rating' );
