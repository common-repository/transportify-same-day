<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists('Settings_Deliveree_Services_Settings_Section') ) :

class Settings_Deliveree_Services_Settings_Section {

	/**
	 * Constructor.
	 */
	function __construct() {
		add_filter( 'woocommerce_get_sections_settings_deliveree_services',              array( $this, 'settings_section' ) );
		add_filter( 'woocommerce_get_settings_settings_deliveree_services_' . $this->id, array( $this, 'get_settings' ), PHP_INT_MAX );
	}

	/**
	 * settings_section.
	 */
	function settings_section( $sections ) {
		$sections[ $this->id ] = $this->desc;
		return $sections;
	}
}

endif;

