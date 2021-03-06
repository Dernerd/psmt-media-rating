<?php
/**
 * BuddyPress notifications integration.
 *
 * @package psmt-media-rating
 */

// Exit if file accessed directly over web
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BP_PSMT_RATING_NOTIFIER_SLUG', 'psmt_rating_notifier' );

/**
 * Class PSMT_Rating_Notifications
 */
Class PSMT_Rating_Notifications {

	/**
	 * PSMT_Rating_Notifications constructor.
	 */
	public function __construct() {
		add_action( 'psmt_media_rated', array( $this, 'send_notifications' ), 10, 3 );
		add_action( 'bp_setup_globals', array( $this, 'setup_globals' ) );
		add_action( 'bp_template_redirect', array( $this, 'mark_notification_read' ) );
		add_action( 'psmt_media_deleted', array( $this, 'clear_all_media_notification' ) );
	}

	/**
	 * Send notifications.
	 *
	 * @param int $media_id Media id.
	 * @param int $user_id  User id.
	 * @param int $vote     Total vote rated by user.
	 */
	public function send_notifications( $media_id, $user_id, $vote ) {

		$bp = buddypress();

		$media = psmt_get_media( $media_id );

		if ( is_null( $media ) || ! function_exists( 'buddypress' ) || ! bp_is_active( 'notifications' ) ) {
			return;
		}

		if ( bp_loggedin_user_id() == $media->user_id ) {
			return;
		}

		$notification_id = bp_notifications_add_notification( array(
			'item_id'           => $media->id,
			'user_id'           => $media->user_id,
			'component_name'    => $bp->psmt_rating_notifier->id,
			'component_action'  => 'new_psmt_ratings_' . $media->id,
			'secondary_item_id' => $user_id,
		) );

		if ( $notification_id ) {
			bp_notifications_add_meta( $notification_id, '_user_vote', $vote );
		}

		return;
	}

	/**
	 * Setup fake component to send just notification
	 */
	public function setup_globals() {

		$bp = buddypress();

		if ( ! bp_is_active( 'notifications' ) ) {
			return;
		}

		$bp->psmt_rating_notifier                               = new stdClass();
		$bp->psmt_rating_notifier->id                           = 'psmt_rating_notifier';
		$bp->psmt_rating_notifier->slug                         = BP_PSMT_RATING_NOTIFIER_SLUG;
		$bp->psmt_rating_notifier->notification_callback        = array( $this, 'format_notifications' );
		$bp->active_components[ $bp->psmt_rating_notifier->id ] = 1;// $bp->psmt_rating_notifier->id;
	}

	/**
	 * Format notification. Callback to notification by component
	 *
	 * @param string $action            Notification action.
	 * @param int $item_id              Item id.
	 * @param int $secondary_item_id    Secondary item id.
	 * @param int $total_items          Total items.
	 * @param string $format            Format information.
	 * @param int $notification_id      Notification id.
	 *
	 * @return string|null
	 */
	public function format_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string', $notification_id ) {

		if ( $action != 'new_psmt_ratings_' . $item_id ) {
			return;
		}

		//$media = psmt_get_media( $item_id );

		$vote_given = bp_notifications_get_meta( $notification_id, '_user_vote' );

		$name = bp_core_get_user_displayname( $secondary_item_id );

		$name = ( $name ) ? $name : __( 'Anonymous User', 'psmt-media-rating' );

		$link  = psmt_get_media_permalink( $item_id );
		$title = psmt_get_media_title( $item_id );

		$text = sprintf( __( '<a href="%s"> %s hat %s mit %d Sternen bewertet</a>', 'psmt-media-rating' ), $link, $name, $title, $vote_given );

		return $text;
	}

	/**
	 * Mark notification read when user click on it.
	 */
	public function mark_notification_read() {

		$bp = buddypress();

		if ( ! is_user_logged_in() || ! psmt_is_single_media() || ! bp_is_active( 'notifications' ) ) {
			return;
		}

		$component_action = 'new_psmt_ratings_' . psmt_get_current_media_id();

		bp_notifications_mark_notifications_by_type( bp_loggedin_user_id(), $bp->psmt_rating_notifier->id, $component_action, false );
	}

	/**
	 * Clear all media notification. When media is deleted.
	 *
	 * @param $item_id
	 */
	public function clear_all_media_notification( $item_id ) {

		$bp = buddypress();

		if ( ! function_exists( 'buddypress' ) || ! bp_is_active( 'notifications' ) ) {
			return;
		}

		$component_action = 'new_psmt_ratings_' . $item_id;

		bp_notifications_delete_notifications_by_item_id( false, $item_id, $bp->psmt_rating_notifier->id, $component_action );
	}
}

new PSMT_Rating_Notifications();

