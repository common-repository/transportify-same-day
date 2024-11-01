<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists('Deliveree_WC_Custom_Shipping_Methods_Settings_Section') ) :

class Deliveree_WC_Custom_Shipping_Methods_Settings_Section {

	/**
	 * Constructor.
	 */
	function __construct() {
		add_filter( 'woocommerce_get_sections_deliveree_custom_shipping_methods',              array( $this, 'settings_section' ) );
		add_filter( 'woocommerce_get_settings_deliveree_custom_shipping_methods_' . $this->id, array( $this, 'get_settings' ), PHP_INT_MAX );
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
