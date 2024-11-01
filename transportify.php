<?php
/*
Plugin Name: Transportify Same Day
Description: Ship with Transportify for delivery to your customer same day, next day, any day. We pickup at no extra cost.
Version: 1.1.2
Author: Transportify
Author URI: https://transportify.com.ph/
License: GPLv2 or later
Text Domain: transportify-same-day
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('Deliveree_WC_Custom_Shipping_Methods')) :

    // Define plugin main constants.
    define('DELIEVEREE_FILE', __FILE__);
    define('DELIEVEREE_PATH', plugin_dir_path(DELIEVEREE_FILE));
    define('DELIEVEREE_URL', plugin_dir_url(DELIEVEREE_FILE));
    define('DELIEVEREE_NAME', 'Transportify');  //Deliveree   //Transportify
    define('DELIEVEREE_DOMAIN', 'transportify.com.ph');  //deliveree.com   //transportify.com.ph


    //Google api
    define('DELIEVEREE_GOOGLE_GEOCODE', 'https://maps.google.com/maps/api/geocode/json');
    define('DELIEVEREE_GOOGLE_DISTANCE', 'https://maps.googleapis.com/maps/api/distancematrix/json');
    define('DELIEVEREE_API_URL_MODE', [
        'test_mode' => 'https://api.sandbox.deliveree.com/public_api/v1/', //'https://api.test.deliveree.com/api/v1/',//
        'live_mode' => 'https://api.deliveree.com/public_api/v1/'
    ]);

    define('DELIEVEREE_BOOKING_URL_MODE', [
        'test_mode' => 'https://webapp.sandbox.deliveree.com/bookings/',
        'live_mode' => 'https://webapp.deliveree.com/bookings/'
    ]);

    final class Deliveree_WC_Custom_Shipping_Methods
    {
        public $version = '1.1.2';

        protected static $_instance = null;

        protected $coreBooking;

        protected $coreOrders;

        /**
         * @return Deliveree_WC_Custom_Shipping_Methods|null
         */
        public static function instance()
        {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         * Alg_WC_Custom_Shipping_Methods constructor.
         */
        function __construct()
        {
            // Check for active plugins
            if (!$this->is_plugin_active('woocommerce/woocommerce.php')) {
                return;
            }

            add_action('admin_menu', array($this, 'add_deliveree_menu'));

            // Include required files
            $this->includes();

            // Admin
            if (is_admin()) {
                $this->admin();
                //init script
                add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles_and_js'));
            } else {
                add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_styles_and_js'));
            }
        }

        /**
         * Add menu to admin
         */
        function add_deliveree_menu()
        {
            add_menu_page(
                DELIEVEREE_NAME,
                DELIEVEREE_NAME,
                'activate_plugins',
                'deliveree',
                DELIEVEREE_NAME,
                deliveree_wc_custom_shipping_methods()->plugin_url() . '/assets/images/' . DELIEVEREE_NAME . '.png',
                1
            );

            add_submenu_page(
                'deliveree',
                'Orders',
                'Orders ' . $this->getTotalOrderConfirm(),
                'activate_plugins',
                'orders_deliveree',
                'orders_deliveree_page_handler'
            );

            add_submenu_page(
                'deliveree',
                'Bookings',
                'Bookings',
                'activate_plugins',
                'bookings_deliveree',
                'bookings_deliveree_page_handler'
            );
            // add_submenu_page(
            //     'deliveree',
            //     'Create',
            //     'Create',
            //     'activate_plugins',
            //     'bookings_deliveree_form',
            //     'bookings_deliveree_form_page_handler'
            // );
            // add_menu_page(
            //     'Help',
            //     'Help',
            //     'activate_plugins',
            //     'deliveree_help',
            //     'Help',
            //     '',
            //     1
            // );
            add_submenu_page(
                'deliveree',
                'Get Started',
                'Get Started',
                'activate_plugins',
                'deliveree_help_get_started',
                'deliveree_help_get_started_page_handler'
            );

            // remove_submenu_page('deliveree_help', 'deliveree_help');
            remove_submenu_page('deliveree', 'deliveree');
        }

        function enqueue_frontend_styles_and_js()
        {
            $origin_lat = get_option('deliveree_general_setting_origin_lat', '');
            $origin_lng = get_option('deliveree_general_setting_origin_lng', '');
            if ($origin_lat  && $origin_lng) {
                $delivereeConfig['delivereeStoreAdress'] = array(
                    'latitude' => "$origin_lat",
                    'longtitude' => "$origin_lng",
                );
            } else {
                $delivereeConfig['delivereeStoreAdress'] = array(
                    'latitude' => "-6.2608232",
                    'longtitude' => "106.7884168",
                );
            }

            $delivereeConfig['delivereeUserLogin'] = is_user_logged_in();
            $delivereeConfig['delivereeGoogleApiKey'] = get_option('deliveree_google_api_key', '');

?>
            <script languages="javascript">
                window.delivereeConfig = <?php echo json_encode(map_deep($delivereeConfig,'esc_html')); ?>;
            </script>
        <?php

            //google api
            wp_register_script('deliveree-woo-google', 'https://maps.googleapis.com/maps/api/js?libraries=geometry,places&key=' . get_option('deliveree_google_api_key', ''), array('jquery'));
            wp_enqueue_script('deliveree-woo-google');

            //popup modal
            wp_register_script('deliveree-woo-sweetalert_modal', DELIEVEREE_URL . 'assets/js/sweetalert2@9.js', array('jquery'), "1.0.0");
            wp_enqueue_script('deliveree-woo-sweetalert_modal');

            //google picker
            wp_register_script('deliveree-woo-locationpicker', DELIEVEREE_URL . 'assets/js/locationpicker.jquery.js', array('jquery'), "0.1.16");
            wp_enqueue_script('deliveree-woo-locationpicker');

            wp_enqueue_script('deliveree-woo-frontend-js-core', DELIEVEREE_URL . 'assets/js/frontend.js', [], '1.3.6');
            wp_enqueue_style('deliveree-woo-frontend-core', DELIEVEREE_URL . 'assets/css/frontend.css', array(), '1.0.8');
        } //enqueue_frontend_styles_and_js()



        function enqueue_admin_styles_and_js()
        {
            $origin_lat = get_option('deliveree_general_setting_origin_lat', '');
            $origin_lng = get_option('deliveree_general_setting_origin_lng', '');
            $delivereeConfig['delivereeApiKey'] = array(
                'api_url' => DELIEVEREE_API_URL_MODE[get_option('deliveree_api_method', 'test_mode')] ?? '',
                'api_key' => get_option('deliveree_api_key', ''),
            );
            $delivereeConfig['delivereeDateTimeFormat'] = 'd/m/Y H:i';
            $delivereeConfig['deliveree_name'] = DELIEVEREE_NAME;
            $delivereeConfig['delivereeGoogleApiKey'] = get_option('deliveree_google_api_key', '');


            if ($origin_lat  && $origin_lng) {
                $delivereeConfig['delivereeStoreAdress'] = array(
                    'latitude' => "$origin_lat",
                    'longtitude' => "$origin_lng",
                );
            } else {
                $delivereeConfig['delivereeStoreAdress'] = array(
                    'latitude' => "-6.2608232",
                    'longtitude' => "106.7884168",
                );
            }
            $delivereeConfig['delivereeIcon'] = [
                'map_marked_alt' => deliveree_wc_custom_shipping_methods()->plugin_url() . '/assets/images/map-marked-alt.svg',
            ];
        ?>
            <script languages="javascript">
                window.delivereeConfig = <?php echo json_encode(map_deep($delivereeConfig,'esc_html')); ?>;
            </script>
            <?php
            //global scripts

            //google api
            wp_register_script('deliveree-woo-google', 'https://maps.googleapis.com/maps/api/js?libraries=geometry,places&key=' . get_option('deliveree_google_api_key', ''), array('jquery'));
            wp_enqueue_script('deliveree-woo-google');

            //popup modal
            wp_register_script('deliveree-woo-sweetalert_modal', DELIEVEREE_URL . 'assets/js/sweetalert2@9.js', array('jquery'), "1.0.0");
            wp_enqueue_script('deliveree-woo-sweetalert_modal');

            //google picker
            wp_register_script('deliveree-woo-locationpicker', DELIEVEREE_URL . 'assets/js/locationpicker.jquery.js', array('jquery'), "0.1.16");
            wp_enqueue_script('deliveree-woo-locationpicker');
            ?>
            <script type="text/javascript" language="javascript">
                const $gmtoffset = <?php echo esc_html(get_option('gmt_offset')); ?>;
            </script>
<?php

            //css

            wp_enqueue_style('jquery-ui');
            wp_enqueue_style('jquery-ui-booking-datetimepicker', DELIEVEREE_URL . 'assets/css/datetimepicker.min.css', array(), null, false);
            wp_enqueue_style('deliveree-woo-backend-core', DELIEVEREE_URL . 'assets/css/backend.css', array(), '1.2.1', false);
            wp_enqueue_style('deliveree-woo-admin-styles', DELIEVEREE_URL . 'assets/css/admin.css', array(), '1.1.7');
            //date picker
            wp_enqueue_script('deliveree-woo-datetimepicker', DELIEVEREE_URL . 'assets/js/datetimepicker.full.min.js');
            wp_enqueue_script('deliveree-woo-inputmask', DELIEVEREE_URL . 'assets/js/jquery.inputmask.js');
            wp_enqueue_script('deliveree-woo-parsley', DELIEVEREE_URL . 'assets/js/parsley.js');
            //multiselect
            wp_enqueue_script('deliveree-woo-booking-js-multiselect', DELIEVEREE_URL . 'assets/js/multiselect.min.js', array('jquery'), '1.0.2', true);
            wp_enqueue_style('deliveree-woo-booking-css-multiselect', DELIEVEREE_URL . 'assets/css/multiselect.css', array(), '1.0.1', false);

            //loading
            wp_enqueue_script('deliveree-woo-loading', DELIEVEREE_BOOKING_URL . 'assets/js/loadingoverlay.min.js');
            //core
            wp_enqueue_script('deliveree-woo-backend-js-core', DELIEVEREE_URL . 'assets/js/backend.js', array(), '1.3.8');
            wp_enqueue_script('deliveree-woo-backend-js-setting-general', DELIEVEREE_URL . 'assets/js/general_settings.js', array('jquery'), '1.1.8');
            wp_enqueue_script('deliveree-woo-backend-js-generic', DELIEVEREE_URL . 'assets/js/generic.js', array('jquery'));

            //render script for page
            if (isset($_REQUEST['page'])) {
                switch ($_REQUEST['page']) {
                    case 'bookings_deliveree':
                    case 'bookings_deliveree_form':

                        wp_enqueue_script('deliveree-woo-moment-js-backend', DELIEVEREE_BOOKING_URL . 'assets/js/moment.min.js', array('jquery'), '1.1.1', true);
                        wp_enqueue_script('deliveree-woo-daterangepicker-js-backend', DELIEVEREE_BOOKING_URL . 'assets/js/daterangepicker.min.js', array('jquery'), '1.1.1', true);
                        wp_enqueue_style('deliveree-woo-daterangepicker-css-backend', DELIEVEREE_BOOKING_URL . 'assets/css/daterangepicker.css', array(), '1.0.6', false);
                        wp_enqueue_style('deliveree-woo-booking-css-backend', DELIEVEREE_BOOKING_URL . 'assets/css/booking.css', array(), '1.2.0', false);
                        wp_enqueue_script('deliveree-woo-booking-js-backend', DELIEVEREE_BOOKING_URL . 'assets/js/booking.js', array('jquery'), '1.2.7', true);
                        wp_enqueue_script('deliveree-woo-booking-js-backend');
                        break;
                    default:
                        break;
                }
            }
        } //enqueue_admin_styles_and_js()

        /**
         * is_plugin_active.
         */
        function is_plugin_active($plugin)
        {
            $result = (function_exists('is_plugin_active') ? is_plugin_active($plugin) : (in_array($plugin, apply_filters('active_plugins', (array)get_option('active_plugins', array()))) ||
                (is_multisite() && array_key_exists($plugin, (array)get_site_option('active_sitewide_plugins', array())))));
            return $result;
        }

        /**
         * Include required core files used in admin and on the frontend.
         */
        function includes()
        {
            $this->core = require_once('includes/class-deliveree-shipping-methods-core.php');
            $this->coreBooking = require_once('booking/Bookings.php');
            $this->coreOrders = require_once('orders/orders.php');
            $this->coreHelps = require_once('helps/Helps.php');
        }

        /**
         * admin.
         */
        function admin()
        {
            // Action links
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'action_links'));
            // Settings
            add_filter('woocommerce_get_settings_pages', array($this, 'add_woocommerce_settings_tab'));
        }

        function action_function_name_2047($value)
        {
            var_dump($value);
        }

        /**
         * Show action links on the plugin screen.
         */
        function action_links($links)
        {
            $custom_links = array();
            $custom_links[] = '<a href="' . admin_url('admin.php?page=wc-settings&tab=alg_wc_custom_shipping_methods') . '">' . __('Settings', 'deliveree-same-day') . '</a>';
            return array_merge($custom_links, $links);
        }

        /**
         * Add Custom Shipping Methods settings tab to WooCommerce settings.
         */
        function add_woocommerce_settings_tab($settings)
        {
            $settings[] = require_once('includes/settings/class-settings-shipping-methods.php');
            $settings[] = require_once('includes/settings/class-settings-deliveree-services.php');
            return $settings;
        }

        /**
         * Get the plugin url.
         */
        function plugin_url()
        {
            return untrailingslashit(plugin_dir_url(__FILE__));
        }

        /**
         * Get the plugin path.
         */
        function plugin_path()
        {
            return untrailingslashit(plugin_dir_path(__FILE__));
        }

        /**
         * Total item new order booking
         * @return string
         */
        function getTotalOrderConfirm()
        {
            return null;
            global $wpdb;
            $table_name = $wpdb->prefix . DELIEVEREE_BOOKING_TABLE_NAME;
            $totalItem = $wpdb->get_var("SELECT COUNT(id) FROM $table_name WHERE booking_confirm = 0");
            if ($totalItem > 0) {
                return '<span class="update-plugins"><span class="update-count">' . $totalItem . '</span></span>';
            }
        }
    }

endif;

if (!function_exists('deliveree_wc_custom_shipping_methods')) {
    function deliveree_wc_custom_shipping_methods()
    {
        return Deliveree_WC_Custom_Shipping_Methods::instance();
    }
}

deliveree_wc_custom_shipping_methods();
