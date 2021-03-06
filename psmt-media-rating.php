<?php
/**
 * Plugin Name: Psource Mediathek Medienbewertung
 * Plugin URI: https://n3rds.work/piestingtal_source/medienbewertung-fuer-psource-mediathek-plugin/
 * Version: 1.1.4
 * Description: Wird mit Psource Mediathek zur Bewertung von Medien verwendet.
 * Author: DerN3rd
 * Author URI: https://n3rds.work
 * License: GPL
 * Text Domain: psmt-media-rating
 *
 * @package psmt-media-rating
 **/

require 'psource/psource-plugin-update/psource-plugin-updater.php';
$MyUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://n3rds.work//wp-update-server/?action=get_metadata&slug=psmt-media-rating', 
	__FILE__, 
	'psmt-media-rating' 
);

// Exit if the file is accessed directly over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PSMT_Media_Rating_Helper
 */
class PSMT_Media_Rating_Helper {

	/**
	 * Class instance
	 *
	 * @var PSMT_Media_Rating_Helper
	 */
	private static $instance = null;

	/**
	 * Absolute plugin directory url that can be accessed over web to load plugin assets
	 *
	 * @var string
	 */
	private $url = '';

	/**
	 * Absolute plugin directory path
	 *
	 * @var string
	 */
	private $path = '';

	/**
	 * PSMT_Media_Rating_Helper constructor.
	 */
	private function __construct() {

		$this->url  = plugin_dir_url( __FILE__ );
		$this->path = plugin_dir_path( __FILE__ );

		$this->setup();
	}

	/**
	 * Get class instance
	 *
	 * @return PSMT_Media_Rating_Helper
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Setup application callbacks for necessary hooks
	 */
	public function setup() {

		register_activation_hook( __FILE__, array( $this, 'install' ) );

		add_action( 'psmt_init', array( $this, 'load_text_domain' ) );
		add_action( 'psmt_loaded', array( $this, 'load' ) );
		add_action( 'psmt_enqueue_scripts', array( $this, 'load_assets' ) );
		add_action( 'psmt_widgets_init', array( $this, 'register_widget' ) );
	}

	/**
	 * Load required files
	 */
	public function load() {

		$files = array(
			'core/psmt-rating-functions.php',
			'core/psmt-rating-actions.php',
			'core/psmt-rating-ajax-handler.php',
			'core/psmt-rating-widget.php',
			'core/psmt-rating-shortcode.php',
		);

		if ( function_exists( 'buddypress' ) ) {
			$files[] = 'core/psmt-rating-notifications.php';
		}

		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			$files[] = 'admin/admin.php';
		}

		foreach ( $files as $file ) {
			require_once $this->path . $file;
		}
	}

	/**
	 * Load plugin assets
	 */
	public function load_assets() {

		// Register/Load jQuery Rateit plugin.
		wp_register_style( 'rateit', $this->url . 'assets/css/rateit.css' );
		wp_enqueue_style( 'rateit' );

		wp_register_script( 'jquery-star-rating', $this->url . 'assets/js/jquery.rateit.min.js', array( 'jquery' ) );

		wp_register_script( 'jquery-cookie', $this->url . 'assets/js/jquery.cookie.js', array( 'jquery' ) );
		wp_register_script( 'psmt-media-rating-script', $this->url . 'assets/js/psmt-media-rating.js', array(
			'jquery-star-rating',
			'jquery-cookie',
		) );

		$data = array(
			'ajax_url'          => admin_url( 'admin-ajax.php' ),
			'_nonce'            => wp_create_nonce( 'psmt-media-rating' ),
			'is_user_can_vote'  => psmt_rating_current_user_can_rate(),
			'is_user_logged_in' => is_user_logged_in(),
		);

		wp_localize_script( 'psmt-media-rating-script', 'PSMT_RATING', $data );
		wp_enqueue_script( 'psmt-media-rating-script' );
	}

	/**
	 * Load plugin translations
	 */
	public function load_text_domain() {
		load_plugin_textdomain( 'psmt-media-rating', false, basename( dirname( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * On activate creates database table
	 */
	public function install() {

		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$charset_collate = ! empty( $wpdb->charset ) ? "DEFAULT CHARACTER SET {$wpdb->charset}" : '';

		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}psmt_media_rating (
	                    id bigint(20) NOT NULL AUTO_INCREMENT,
	                    media_id bigint(20) NOT NULL,
	                    user_id bigint(20) NOT NULL,
	                    rating tinyint(4) NOT NULL,
	                    date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	                    PRIMARY KEY (id)
	                ) {$charset_collate}";

		dbDelta( $sql );
	}

	/**
	 * Register plugin widget
	 */
	public function register_widget() {
		register_widget( 'PSMT_Rating_Widget' );
	}

	/**
	 * Get path of plugin directory
	 *
	 * @return string
	 */
	public function get_path() {
		return $this->path;
	}

	/**
	 * Get url of plugin directory
	 *
	 * @return string
	 */
	public function get_url() {
		return $this->url;
	}
}

/**
 * Get class instance
 *
 * @return PSMT_Media_Rating_Helper
 */
function psmt_media_rating() {
	return PSMT_Media_Rating_Helper::get_instance();
}

psmt_media_rating();


