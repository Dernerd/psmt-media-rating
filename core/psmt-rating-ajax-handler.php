<?php
/**
 * Class handle all ajax request made by plugin
 *
 * @package psmt-media-rating
 */

// Exit if file accessed directly over web
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PSMT_Media_Rating_Ajax_Handler
 */
class PSMT_Media_Rating_Ajax_Handler {

	/**
	 * PSMT_Media_Rating_Ajax_Handler constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_psmt_rate_media', array( $this, 'rate' ) );
		add_action( 'wp_ajax_nopriv_psmt_rate_media', array( $this, 'rate' ) );
	}

	/**
	 * Do rating asynchronously
	 */
	public function rate() {

		$media_id = absint( $_POST['media_id'] );
		$vote     = absint( $_POST['rating'] );

		check_ajax_referer( 'psmt-media-rating', '_nonce' );

		if ( ! psmt_rating_current_user_can_rate() || ! psmt_rating_is_media_rateable( $media_id ) ) {
			wp_send_json_error( array(
				'message' => __( 'Ungültige Anfrage.', 'psmt-media-rating' ),
			) );
		}

		$this->save_rating( get_current_user_id(), $media_id, $vote );
		exit;
	}

	/**
	 * Save user rating
	 *
	 * @param int $user_id User id.
	 * @param int $media_id Media Id.
	 * @param int $rating Rating number.
	 */
	private function save_rating( $user_id, $media_id, $rating ) {

		global $wpdb;

		$table_name = psmt_rating_get_table_name();

		if ( psmt_rating_has_user_rated( $user_id, $media_id ) ) {
			wp_send_json_error( array(
				'message' => __( 'Du hast bereits bewertet.', 'psmt-media-rating' ),
			) );
		}

		$data = array(
			'media_id' => $media_id,
			'user_id'  => $user_id,
			'rating'   => $rating,
		);

		$data_format = array( '%d', '%d', '%d' );

		$insert = $wpdb->insert( $table_name, $data, $data_format );

		if ( is_null( $insert ) ) {
			wp_send_json_error( array(
				'message' => __( 'Kann nicht hinzufügen.', 'psmt-media-rating' ),
			) );
		}

		do_action( 'psmt_media_rated', $media_id, $user_id, $rating );

		$average_rating = psmt_rating_get_average_rating( $media_id );

		wp_send_json_success( array(
			'message' => array( 'average_rating' => $average_rating ),
		) );
	}
}

new PSMT_Media_Rating_Ajax_Handler();
