<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('Settings_Deliveree_Services')) :

	require_once(DELIEVEREE_PATH . '/api/BookingApi.php');

	class Settings_Deliveree_Services extends WC_Settings_Page
	{
		/**
		 * Constructor.
		 */
		function __construct()
		{
			$this->id    = 'settings_deliveree_services';
			$this->label = __(DELIEVEREE_NAME . ' Services', 'deliveree-same-day');

			parent::__construct();
			add_filter('woocommerce_admin_settings_sanitize_option', array($this, 'maybe_unsanitize_option'), PHP_INT_MAX, 3);

			// Sections
			require_once('class-settings-deliveree-services-settings-section.php');
			require_once('class-settings-deliveree-services-settings-general.php');
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

			$settings = $this->get_settings();
			WC_Admin_Settings::save_fields( $settings );
		}
	}

endif;

return new Settings_Deliveree_Services();
