<?php
/**
 * Plugin widget class to list top rated media.
 *
 * @package psmt-media-rating
 */

// Exit if file access directly over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *  PSMT rating widget for displaying
 *  Top n media of a user
 *  Top n photos of a user
 *  Top n media last week, month, year
 */

/**
 * Class PSMT_Rating_Widget
 */
class PSMT_Rating_Widget extends WP_Widget {

	/**
	 * PSMT_Rating_Widget constructor.
	 */
	public function __construct() {

		$widget_ops = array(
			'description' => __( 'Ein Widget zum Anzeigen der Top-Medien des Benutzers, Bilder des Benutzers, Woche, Monat, Jahr ', 'psmt-media-rating' ),
		);

		parent::__construct( false, _x( 'PSM Bestbewertete Medien', 'widget name', 'psmt-media-rating' ), $widget_ops );
	}

	/**
	 * Render widget on frontend using saved settings.
	 *
	 * @param array $args Array of values.
	 * @param array $instance Current value of widget settings.
	 *
	 * @return string
	 */
	public function widget( $args, $instance ) {
		// For widget call using the_widget function.
		if ( empty( $instance ) ) {
			return '';
		}

		if ( 'loggedin' == $instance['user_type'] && ! is_user_logged_in() ) {
			return '';
		} elseif ( 'displayed' == $instance['user_type'] && ! bp_is_user() ) {
			return '';
		}

		$media_args = array(
			'component'   => $instance['component'],
			'status'      => $instance['status'],
			'type'        => $instance['type'],
			'post_status' => 'inherit',
		);

		$component_id = '';
		if ( 'members' == $instance['component'] ) {
			if ( 'loggedin' == $instance['user_type'] ) {
				$component_id = get_current_user_id();
			} elseif ( 'displayed' == $instance['user_type'] ) {
				$component_id = bp_displayed_user_id();
			}
		}

		$media_args['component_id'] = $component_id;

		$rated_media = psmt_rating_get_top_rated_media( $media_args, $instance['interval'], $instance['max_to_list'] );

		echo $args['before_widget'];
		echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title'];

		?>
		<div class="psmt-container psmt-widget-container psmt-media-widget-container psmt-media-video-widget-container">
		<?php if ( $rated_media ) : ?>
			<?php foreach ( $rated_media as $media_id ): ?>
				<div class='psmt-g psmt-item-list psmt-media-list psmt-<?php echo $instance['type'] ?>-list'>
					<div class="<?php echo psmt_get_media_class( 'psmt-widget-item psmt-widget-' . $instance['type'] . '-item ' . psmt_get_grid_column_class( 1 ), $media_id ); ?>">
						<div class="psmt-item-entry psmt-media-entry psmt-photo-entry">
							<a href="<?php psmt_media_permalink( $media_id ); ?>" <?php psmt_media_html_attributes( array(
								'class'            => 'psmt-item-thumbnail psmt-media-thumbnail psmt-' . $instance['type'] . '-thumbnail',
								'data-psmt-context' => 'widget',
							) ); ?>>
								<img src="<?php psmt_media_src( 'thumbnail', $media_id ); ?>"
									 alt="<?php echo esc_attr( psmt_get_media_title( $media_id ) ); ?> "/>
							</a>
						</div>
						<a href="<?php psmt_media_permalink( $media_id ); ?>" <?php echo psmt_get_media_html_attributes( array(
							'class'            => "psmt-item-title psmt-media-title",
							'data-psmt-context' => 'widget',
							'media'            => $media_id,
						) ); ?> >
							<?php psmt_media_title( $media_id ); ?>
						</a>
						<div class="psmt-item-meta psmt-media-meta psmt-media-widget-item-meta psmt-media-meta-bottom psmt-media-widget-item-meta-bottom">
							<?php echo psmt_rating_get_rating_html( $media_id, 1 ); ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		<?php else: ?>
			<?php _e( 'Nichts zu zeigen', 'psmt-media-rating' ); ?>
		<?php endif; ?>
		</div>

		<?php
		echo $args['after_widget'];
	}

	/**
	 * Update widget settings
	 *
	 * @param array $new_instance New instance of settings.
	 * @param array $old_instance Old instance of settings.
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {

		$instance                = $old_instance;
		$instance['title']       = strip_tags( $new_instance['title'] );
		$instance['component']   = $new_instance['component'];
		$instance['status']      = $new_instance['status'];
		$instance['type']        = $new_instance['type'];
		$instance['max_to_list'] = $new_instance['max_to_list'];
		$instance['user_type']   = $new_instance['user_type'];
		$instance['interval']    = $new_instance['interval'];

		return $instance;
	}

	/**
	 * Render widget settings form
	 *
	 * @param array $instance Current instance of settings.
	 */
	public function form( $instance ) {

		$defaults = array(
			'title'       => __( 'Bewertungen', 'psmt-media-rating' ),
			'component'   => 'members',
			'status'      => '',
			'type'        => 'photo',
			'max_to_list' => 5,
			'user_type'   => 'displayed',
			'interval'    => 'lweek',
		);

		$instance            = wp_parse_args( (array) $instance, $defaults );
		$title               = strip_tags( $instance['title'] );
		$component           = $instance['component'];
		$status              = $instance['status'];
		$type                = $instance['type'];
		$max_to_list         = strip_tags( $instance['max_to_list'] );
		$user_type           = $instance['user_type'];
		$interval            = $instance['interval'];
		$active_types        = psmt_get_active_types();
		$active_statuses     = psmt_get_active_statuses();
		$rateable_components = psmt_rating_get_rateable_components();
		$intervals           = psmt_rating_get_intervals();
		$media_of            = psmt_rating_show_media_of();

		?>

		<p>
			<label>
				<?php _e( 'Titel:', 'psmt-media-rating' ); ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
					   name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
					   value="<?php echo esc_attr( $title ); ?>"/>
			</label>
		</p>
		<p>
			<?php _e( 'Wähle Komponente: ', 'psmt-media-rating' ); ?>
			<?php foreach ( $rateable_components as $key => $label ) : ?>
				<label>
					<input class="widefat" name="<?php echo $this->get_field_name( 'component' ); ?>" type="radio"
						   value="<?php echo $key; ?>" <?php checked( $component, $key ); ?>/>
					<?php echo $label; ?>
				</label>
			<?php endforeach; ?>
		</p>

		<p>
			<?php _e( 'Typ auswählen: ', 'psmt-media-rating' ); ?>
			<?php if ( ! empty( $active_types ) ) : ?>
				<select name="<?php echo $this->get_field_name( 'type' ); ?>">
					<?php foreach ( $active_types as $key => $label ) : ?>
						<option value="<?php echo $key ?>" <?php selected( $type, $key ) ?>>
							<?php echo $label->label; ?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php else: ?>
				<?php _e( 'Kein aktiver Medientyp!', 'psmt-media-rating' ); ?>
			<?php endif; ?>
		</p>

		<p>
			<?php _e( 'Status auswählen: ', 'psmt-media-rating' ); ?>
			<?php if ( ! empty( $active_statuses ) ): ?>
				<select name="<?php echo $this->get_field_name( 'status' ); ?>">
                    <option value="" <?php selected( $status, '' ) ?>><?php _e( 'Alle', 'psmt-media-rating' );?></option>
					<?php foreach ( $active_statuses as $key => $label ) : ?>
						<option value="<?php echo $key ?>" <?php selected( $status, $key ) ?>>
							<?php echo $label->label; ?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>
		</p>

		<p>
			<label>
				<?php _e( 'Max Medien anzeigen', 'psmt-media-rating' ) ?>
				<input type="number" name="<?php echo $this->get_field_name( 'max_to_list' ); ?>"
					   value="<?php echo esc_attr( $max_to_list ); ?>"/>
			</label>
		</p>

		<p>
			<?php _e( 'Intervall: ', 'psmt-media-rating' ) ?>
			<select name="<?php echo $this->get_field_name( 'interval' ); ?>">
				<?php foreach ( $intervals as $key => $label ) : ?>
					<option value="<?php echo $key ?>" <?php selected( $interval, $key ) ?>>
						<?php echo $label; ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>

		<p>
			<?php _e( 'Liste Medien von: ', 'psmt-media-rating' ) ?>
			<?php foreach ( $media_of as $key => $label ): ?>
				<label>
					<input name="<?php echo $this->get_field_name( 'user_type' ); ?>" type="radio"
						   value="<?php echo $key; ?>" <?php checked( $key, $user_type ); ?>/>
					<?php echo $label; ?>
				</label>
			<?php endforeach; ?>
		</p>

		<?php
	}
}
