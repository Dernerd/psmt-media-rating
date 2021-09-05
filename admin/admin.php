<?php

class PSMT_Media_Rating_Admin {

    public function __construct() {
        //setup hooks
	    add_action( 'psmt_admin_register_settings', array( $this, 'register_settings' ) );
    }

    /**
    * 
    * @param PSMT_Admin_Settings_Page $page
    */
    
    public function register_settings( $page ) {

		$panel = $page->get_panel( 'addons' );

	    $rateable_components = psmt_rating_get_rateable_components();

	    $who_can_rate = psmt_rating_get_rating_permissions();

	    $rateable_types = array();
	    $active_types = psmt_get_active_types();

	    if ( ! empty( $active_types ) ) {
		    foreach ( $active_types as $type => $value ) {
			    $rateable_types[ $type ]  = $value->label;
		    }
	    }

		$fields = array(
			array(
				'name'		=> 'psmt-rating-rateable-components',
				'label'		=> __( 'Aktiviert für Komponenten', 'psmt-media-rating' ),
				'type'		=> 'multicheck',
				'options'	=> $rateable_components
			),
			array(
				'name'		=> 'psmt-rating-rateable-types',
				'label'		=> __( 'Aktiviert für Typen', 'psmt-media-rating' ),
				'type'		=> 'multicheck',
				'options'	=> $rateable_types
			),
			array(
				'name'		=> 'psmt-rating-required-permission',
				'label'		=> __( 'Wer kann bewerten', 'psmt-media-rating' ),
				'type'		=> 'radio',
				'options'	=> $who_can_rate
			),
			array(
				'name'		=> 'psmt-rating-appearance',
				'label'		=> __( 'Aussehen', 'psmt-media-rating' ),
				'type'		=> 'multicheck',
				'options'	=> array(
					'single_media'  => __( 'Einzelne Medienseite', 'psmt-media-rating' ),
					'light_box'     => __( 'LightBox', 'psmt-media-rating' ),
					'single_gallery'=> __( 'Einzelne Galerie', 'psmt-media-rating' )
				)
			)

		);
        
        $panel->add_section( 'rating-settings', __( 'Medienbewertungseinstellung', 'psmt-media-rating' ) )->add_fields( $fields );
        	
    }
}

new PSMT_Media_Rating_Admin();

