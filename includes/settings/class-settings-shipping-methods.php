<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('Deliveree_WC_Settings_Custom_Shipping_Methods')) :

	require_once(DELIEVEREE_PATH . '/api/BookingApi.php');

	class Deliveree_WC_Settings_Custom_Shipping_Methods extends WC_Settings_Page
	{
		/**
		 * Constructor.
		 */
		function __construct()
		{
			$this->id    = 'deliveree_custom_shipping_methods';
			$this->label = __(DELIEVEREE_NAME . ' API', 'deliveree-same-day');

			parent::__construct();
			add_filter('woocommerce_admin_settings_sanitize_option', array($this, 'maybe_unsanitize_option'), PHP_INT_MAX, 3);

			// Sections
			require_once('class-shipping-methods-settings-section.php');
			require_once('class-settings-general.php');
		}

		/**
		 * maybe_unsanitize_option.
		 */
		function maybe_unsanitize_option($value, $option, $raw_value)
		{
			return (!empty($option['alg_wc_csm_raw']) ? $raw_value : $value);
		}

		/**
		 * get_settings.`
		 */
		function get_settings()
		{
			global $current_section;
			return apply_filters('woocommerce_get_settings_' . $this->id . '_' . $current_section, array());
		}

		/**
		 * admin_notice_settings_reset.
		 */
		function admin_notice_settings_reset()
		{
			echo esc_html('<div class="notice notice-warning is-dismissible"><p><strong>' .
				__('Your settings have been reset.', 'deliveree-same-day') . '</strong></p></div>');
		}

		/**
		 * Save settings.
		 */
		function save()
		{
			parent::save();
			if (isset($_POST)) {
				$lat = isset($_POST['deliveree_general_setting_origin_lat']) ? sanitize_text_field($_POST['deliveree_general_setting_origin_lat'])  : null;
				$lng = isset($_POST['deliveree_general_setting_origin_lng']) ? sanitize_text_field($_POST['deliveree_general_setting_origin_lng'])  : null;
				$address = isset($_POST['deliveree_general_setting_origin_address']) ? sanitize_text_field($_POST['deliveree_general_setting_origin_address'])  : null;
				$bookingApi = new BookingApi();

				if ($lat & $lng) {
					if (empty($address)) {
						$address = $bookingApi->get_address_from_lat_lng($lat, $lng);
						if ($address) {
							update_option('deliveree_general_setting_origin_address', $address, 'yes');
						}
					}
				}

				$data =  $bookingApi->getUserProfile();

				if (isset($data['user_type'])) {
					update_option('deliveree_user_type', $data['user_type']);
				}

				if (isset($data['country_code'])) {
					update_option('deliveree_country_code', $data['country_code']);
				}
			}
		}
	}

endif;

return new Deliveree_WC_Settings_Custom_Shipping_Methods();
