<?php
/**
 * Actions helper class
 *
 * @package psmt-media-rating
 */

// Exit if the file is accessed directly over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PSMT_Media_Rating_Actions_Helper
 */
class PSMT_Media_Rating_Actions_Helper {

	/**
	 * Class instance
	 *
	 * @var PSMT_Media_Rating_Actions_Helper
	 */
	private static $instance = null;

	/**
	 * PSMT_Media_Rating_Actions_Helper constructor.
	 */
	private function __construct() {
		$this->setup();
	}

	/**
	 * Action callbacks
	 */
	public function setup() {

		//add to the media entry in the loop
		add_action( 'psmt_media_meta', array( $this, 'add_stars' ), 999 );

		//add to media entry in lightbox
		add_action( 'psmt_lightbox_media_meta', array( $this, 'add_lightbox_stars' ), 999 );

		//add_action( 'psmt_gallery_meta', array( $this, 'gallery_rating_star' ) );
		//add_action( 'psmt_lightbox_gallery_meta', array( $this, 'gallery_rating_star' ) );
		add_action( 'psmt_after_lightbox_media', array( $this, 'execute_script' ) );
	}

	/**
	 * Get class instance
	 *
	 * @return PSMT_Media_Rating_Actions_Helper
	 */
	public static function get_instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Add stars interface
	 *
	 * @param null|PSMT_Media $media Media object.
	 */
	public function add_stars( $media = null ) {

		$media = psmt_get_media( $media );

		if ( is_null( $media ) || ! psmt_is_valid_media( $media->id ) ) {
			return;
		}

		$appearance = (array) psmt_get_option( 'psmt-rating-appearance' );

		if ( psmt_is_single_media() && in_array( 'single_media', $appearance ) ) {
			$this->add_interface( $media->id );
		} elseif ( psmt_is_single_gallery() && in_array( 'single_gallery', $appearance ) ) {
			$this->add_interface( $media->id );
		}
	}

	/**
	 * Add interface in the lightbox view
	 *
	 * @param null|PSMT_Media $media Media object.
	 */
	public function add_lightbox_stars( $media = null ) {

		$media = psmt_get_media( $media );

		if ( is_null( $media ) || ! psmt_is_valid_media( $media->id ) ) {
			return;
		}

		$appearance = (array) psmt_get_option( 'psmt-rating-appearance' );

		if ( in_array( 'light_box', $appearance ) ) {
			$this->add_interface( $media->id );
		}
	}

	/**
	 * Render rating html using media id
	 *
	 * @param $media_id
	 */
	public function add_interface( $media_id ) {

		if ( ! psmt_rating_is_media_rateable( $media_id ) ) {
			return;
		}

		echo psmt_rating_get_rating_html( $media_id, psmt_rating_is_read_only_media_rating( $media_id ) );
	}

	/**
	 * Execute script
	 *
	 * @param null|PSMT_Media $media Media object
	 */
	public function execute_script( $media = null ) {

		?>
        <script type="text/javascript">

            jQuery(".psmt-media-rating").rateit({resetable: false});

            jQuery('.psmt-media-rating').bind('rated', function (event, value) {

                var $this = jQuery(this),
                    media_id = $this.attr('data-media-id');

                $this.rateit('readonly', true);

                var data = {
                    action: 'psmt_rate_media',
                    media_id: media_id,
                    _nonce: _nonce,
                    rating: value
                };

                jQuery.post(url, data, function (resp) {

                    if (resp.type == 'error') {

                    } else if (resp.type == 'success') {
                        jQuery($this).rateit('value', resp.message.average_rating);
                    }

                }, 'json');

            });

        </script>

		<?php
	}
}

PSMT_Media_Rating_Actions_Helper::get_instance();
