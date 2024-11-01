<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly


if (!class_exists('Deliveree_WC_Custom_Shipping_Methods_Core')) :

    class Deliveree_WC_Custom_Shipping_Methods_Core
    {
        /**
         * Constructor.
         *
         * @version 1.5.2
         * @since   1.0.0
         */
        function __construct()
        {
            if ('yes' === get_option('deliveree_shipping_method_service_setting_shipping_enable', 'yes')) {
                // Init
                add_action('init', array($this, 'init_custom_shipping'));
                add_filter('woocommerce_cart_shipping_method_full_label', array($this, 'woocommerce_cart_shipping_method_full_label'), PHP_INT_MAX - 1, 2);
                add_filter('wc_get_template', array($this, 'wc_get_template_customize'), PHP_INT_MAX - 1, 5);
                add_filter('woocommerce_ship_to_different_address_checked', array($this, 'set_default_customer_shipping_address'), PHP_INT_MAX - 1);
                add_filter('woocommerce_checkout_fields', array($this, 'rearrange_woocommerce_checkout_fields'), PHP_INT_MAX - 1, 1);
                add_filter('woocommerce_checkout_get_value', array($this, 'lat_long_woocommerce_checkout_get_value'), PHP_INT_MAX - 1, 2);
                add_filter('woocommerce_default_address_fields', array($this, 'custom_default_address_fields'), 20, 1);
                add_filter('woocommerce_cart_shipping_packages', array($this, 'add_location_cart_shipping'), 20, 1);
                add_filter('woocommerce_order_shipping_to_display_shipped_via', array($this, 'woocommerce_order_shipping_to_display_shipped_via'), 20, 2);

                // Trigger checkout update script
                add_action('wp_enqueue_scripts', array($this, 'enqueue_checkout_script'));
                // Hook to enqueue scripts & styles assets.
                add_action('admin_enqueue_scripts', array($this, 'enqueue_backend_deliveree_assets'), 999);
                add_action('woocommerce_calculated_shipping', array($this, 'calculate_shipping_add_address'), PHP_INT_MAX - 1);
                add_action('woocommerce_checkout_update_order_review', array($this, 'checkout_update_order_review'), PHP_INT_MAX - 1);
                add_action('wp_footer', array($this, 'billing_country_update_checkout'), PHP_INT_MAX - 1);

                add_action('woocommerce_after_shipping_rate', array($this, 'woocommerce_after_shipping_rate'), 10, 2);
            }
        }


        public function woocommerce_order_shipping_to_display_shipped_via($shipping, $order)
        {
            $names = array();
            foreach ($order->get_shipping_methods() as $shipping_method) {
                $shipping_method_name = $shipping_method->get_name();
                if ('deliveree_actual_shipping_method' == $shipping_method->get_method_id()) {
                    // $estimated_delivery_days = $shipping_method->get_meta('estimated_delivery_days', true);
                    $shipping_method_name = '<img class="logo_shipping_methods" src="' . DELIEVEREE_URL . 'assets/images/' . DELIEVEREE_NAME . '.png' . '" />' . $shipping_method_name;
                    // $shipping_method_name .= '<p>' . $estimated_delivery_days . '</p>';
                }
                $names[] = $shipping_method_name;
            }

            $name_str = implode(', ', $names);
            $shipping = '&nbsp;<small class="shipped_via">' . sprintf(__('via %s', 'deliveree-same-day'), $name_str) . '</small>';
            return $shipping;
        }

        public function woocommerce_after_shipping_rate($method, $index)
        {
            // if ('deliveree_actual_shipping_method' == $method->id) {
            //     $html = '<p>' . $method->meta_data['estimated_delivery_days'] . '</p>';
            //     echo wp_kses_post($html);
            // }
        }

        /**
         * Enqueue backend scripts.
         *
         * @param string $hook Passed screen ID in admin area.
         */
        public function enqueue_backend_deliveree_assets($hook = null)
        {
            if (false === strpos($hook, 'wc-settings')) {
                return;
            }
        }


        /**
         * enqueue_checkout_script.
         *
         * @version 1.4.0
         * @since   1.4.0
         */
        function enqueue_checkout_script()
        {

            if (function_exists('is_cart') && is_cart()) {
                wp_enqueue_script(
                    'alg-wc-custom-shipping-methods-checkout',
                    deliveree_wc_custom_shipping_methods()->plugin_url() . '/includes/js/deliveree-shipping-methods-cart.js',
                    array('jquery'),
                    deliveree_wc_custom_shipping_methods()->version,
                    true
                );
            }

            if (function_exists('is_checkout') && is_checkout()) {
                wp_enqueue_script(
                    'alg-wc-custom-shipping-methods-checkout',
                    deliveree_wc_custom_shipping_methods()->plugin_url() . '/includes/js/deliveree-shipping-methods-checkout.js',
                    array('jquery'),
                    deliveree_wc_custom_shipping_methods()->version,
                    true
                );
            }
        }


        /**
         * get_order_item_shipping_prop.
         *
         * @version 1.5.3
         * @since   1.5.3
         */
        function get_order_item_shipping_prop($order, $prop)
        {
            if ($order && is_a($order, 'WC_Order') && method_exists($order, 'get_shipping_methods')) {
                foreach ($order->get_shipping_methods() as $order_item_shipping) {
                    if (
                        is_a($order_item_shipping, 'WC_Order_Item_Shipping') &&
                        method_exists($order_item_shipping, 'get_method_id') && 'deliveree_shipping_method' === $order_item_shipping->get_method_id() &&
                        method_exists($order_item_shipping, 'get_instance_id') && ($instance_id = $order_item_shipping->get_instance_id()) &&
                        class_exists('WC_Shipping_Deliveree')
                    ) {
                        $shipping = new WC_Shipping_Deliveree($instance_id);
                        if ($shipping && '' != $shipping->{$prop}) {
                            return $shipping->{$prop};
                        }
                    }
                }
            }
            return false;
        }

        function woocommerce_cart_shipping_method_full_label($label, $method)
        {

            if (isset($method->method_id) && 'deliveree_shipping_method' == $method->method_id && 0 == $method->cost) {
                $label = $method->get_label() . ': ' . $method->cost . ' ' . get_woocommerce_currency_symbol();
            } else if (isset($method->method_id) && 'deliveree_actual_shipping_method' == $method->method_id) {
                $label = '<img class="logo_shipping_methods" src="' . DELIEVEREE_URL . 'assets/images/' . DELIEVEREE_NAME . '.png' . '" />' . $label;
            }

            return $label;
        }

        /**
         * work out rounding (shortcode).
         */
        function round($atts, $content = '')
        {
            $content = do_shortcode($content);
            $content = WC_Eval_Math::evaluate($content);
            if (is_numeric($content)) {
                $type = (isset($atts['type']) ? $atts['type'] : 'normal');
                switch ($type) {
                    case 'up':
                        $content = ceil($content);
                        break;
                    case 'down':
                        $content = floor($content);
                        break;
                    default: // 'normal'
                        $content = round($content, (isset($atts['precision']) ? $atts['precision'] : 2));
                }
            }
            return $content;
        }


        /**
         * get items volume in package.
         *
         * @param array $package
         * @since   1.0.0
         * @version 1.0.0
         */
        function get_package_item_volume($package)
        {
            return $this->get_products_volume($package['contents']);
        }

        /**
         * get_products_volume.
         */
        function get_products_volume($products)
        {
            $total_volume = 0;
            foreach ($products as $item_id => $values) {
                if ($values['data']->needs_shipping() && $values['data']->get_height() && $values['data']->get_width() && $values['data']->get_length()) {
                    $total_volume += $values['data']->get_height() * $values['data']->get_width() * $values['data']->get_length() * $values['quantity'];
                }
            }
            return $total_volume;
        }

        /**
         * get items weight in package.
         */
        function get_package_item_weight($package)
        {
            return $this->get_products_weight($package['contents']);
        }

        /**
         * get_products_weight.
         */
        function get_products_weight($products)
        {
            $total_weight = 0;
            foreach ($products as $item_id => $values) {
                if ($values['data']->needs_shipping() && $values['data']->get_weight()) {
                    $total_weight += $values['data']->get_weight() * $values['quantity'];
                }
            }
            return $total_weight;
        }

        /*
         * init_custom_shipping.
         */
        function init_custom_shipping()
        {
            if (class_exists('WC_Shipping_Method')) {
                // require_once('class-wc-shipping-deliveree.php');
                require_once('class-wc-shipping-deliveree-actual.php');
                add_filter('woocommerce_shipping_methods', array($this, 'add_custom_shipping'));

                require_once('class-wc-deliveree-orders.php');
            }
        }

        /*
         * add_custom_shipping.
         */
        function add_custom_shipping($methods)
        {
            $methods['deliveree_actual_shipping_method'] = 'WC_Shipping_Deliveree_Actual';
            // $methods['deliveree_shipping_method'] = 'WC_Shipping_Deliveree';
            return $methods;
        }


        function wc_get_template_customize($template, $template_name, $args, $template_path, $default_path)
        {

            if (file_exists(get_template_directory() . '/woocommerce/' . $template_name)) {
                return get_template_directory() . '/woocommerce/' . $template_name;
            }

            if (file_exists(DELIEVEREE_PATH . 'woocommerce/' . $template_name)) {
                return DELIEVEREE_PATH . 'woocommerce/' . $template_name;
            }

            return $template;
        }

        function calculate_shipping_add_address()
        {
            $address = array();
            $address['shipping_address_1']  = isset($_POST['calc_shipping_address_1']) ? sanitize_text_field(wc_clean(wp_unslash($_POST['calc_shipping_address_1']))) : '';
            $address['shipping_address_2']  = isset($_POST['calc_shipping_address_2']) ? sanitize_text_field(wc_clean(wp_unslash($_POST['calc_shipping_address_2']))) : '';
            $wc_customer_shipping_latitude  = isset($_POST['calc_shipping_latitude']) ? sanitize_text_field(wc_clean(wp_unslash($_POST['calc_shipping_latitude']))) : '';
            $wc_customer_shipping_longitude  = isset($_POST['calc_shipping_longitude']) ? sanitize_text_field(wc_clean(wp_unslash($_POST['calc_shipping_longitude']))) : '';

            WC()->customer->set_shipping_address_1($address['shipping_address_1']);
            WC()->customer->set_shipping_address_2($address['shipping_address_2']);
            WC()->customer->save();

            if (WC()->customer->get_id()) {
                update_user_meta(WC()->customer->get_id(), 'wc_customer_shipping_latitude', $wc_customer_shipping_latitude);
                update_user_meta(WC()->customer->get_id(), 'wc_customer_shipping_longitude', $wc_customer_shipping_longitude);
            } else {
                WC()->session->set('wc_customer_shipping_latitude', $wc_customer_shipping_latitude);
                WC()->session->set('wc_customer_shipping_longitude', $wc_customer_shipping_longitude);
            }
        }

        function checkout_update_order_review($post_data)
        {
            parse_str($post_data, $params);
            $wc_customer_shipping_latitude  = isset($params['ship_to_different_address']) ? wc_clean(wp_unslash($params['shipping_calc_shipping_latitude'])) : wc_clean(wp_unslash($params['billing_calc_shipping_latitude']));
            $wc_customer_shipping_longitude  = isset($params['ship_to_different_address']) ? wc_clean(wp_unslash($params['shipping_calc_shipping_longitude'])) : wc_clean(wp_unslash($params['billing_calc_shipping_longitude']));


            if (WC()->customer->get_id()) {
                update_user_meta(WC()->customer->get_id(), 'wc_customer_shipping_latitude', $wc_customer_shipping_latitude);
                update_user_meta(WC()->customer->get_id(), 'wc_customer_shipping_longitude', $wc_customer_shipping_longitude);
            } else {
                WC()->session->set('wc_customer_shipping_latitude', $wc_customer_shipping_latitude);
                WC()->session->set('wc_customer_shipping_longitude', $wc_customer_shipping_longitude);
            }
        }

        function add_location_cart_shipping($packages)
        {
            if (isset($packages[0]['destination'])) {
                // NEXT -> var_dump(2);
                if (WC()->customer->get_id()) {
                    $wc_customer_shipping_latitude = get_user_meta(WC()->customer->get_id(),  'wc_customer_shipping_latitude', true);
                    $wc_customer_shipping_longitude = get_user_meta(WC()->customer->get_id(),  'wc_customer_shipping_longitude', true);
                } else {
                    $wc_customer_shipping_latitude =  WC()->session->get('wc_customer_shipping_latitude');
                    $wc_customer_shipping_longitude = WC()->session->get('wc_customer_shipping_longitude');
                }
                $packages[0]['destination']['wc_customer_shipping_latitude'] = $wc_customer_shipping_latitude;
                $packages[0]['destination']['wc_customer_shipping_longitude'] = $wc_customer_shipping_longitude;
            }


            return $packages;
        }


        function set_default_customer_shipping_address()
        {
            return 0;
        }

        function rearrange_woocommerce_checkout_fields($fields)
        {
            ## ---- 1. REORDERING BILLING FIELDS ---- ##
            $fieldsets = ['billing', 'shipping'];

            foreach ($fieldsets as $key => $fieldset) {
                // Set the order of the fields
                $orders = array(
                    $fieldset . '_first_name',
                    $fieldset . '_last_name',
                    $fieldset . '_company',
                    $fieldset . '_phone',
                    $fieldset . '_email',
                    $fieldset . '_address_1',
                    $fieldset . '_address_2',
                    $fieldset . '_postcode',
                    $fieldset . '_city',
                    $fieldset . '_state',
                    $fieldset . '_country',
                    $fieldset . '_calc_shipping_latitude',
                    $fieldset . '_calc_shipping_longitude',

                );
                if ($fieldset == 'shipping') {
                    unset($orders[3]); //_phone
                    unset($orders[4]); //_email
                }

                $priority = 10;

                $fields[$fieldset][$fieldset . '_state'] = [
                    'label' => __('Province', 'deliveree-same-day'),
                    'required' => true,
                    'class' => array('form-row-wide'),
                    'priority'    => 55,
                    'type' => 'text',
                    'id' => $fieldset . '_state_input'
                ];

                $fields[$fieldset][$fieldset . '_country']['class'] = ['hidden'];
                $fields[$fieldset][$fieldset . '_calc_shipping_latitude']['class'] = ['hidden'];
                $fields[$fieldset][$fieldset . '_calc_shipping_longitude']['class'] = ['hidden'];

                // Updating the 'priority' argument
                foreach ($orders as $key => $field_name) {
                    $fields[$fieldset][$field_name]['priority'] = $key * $priority;
                }
            }

            return $fields;
        }

        function lat_long_woocommerce_checkout_get_value($a, $input)
        {
            if (WC()->customer->get_id()) {
                $wc_customer_shipping_latitude = get_user_meta(WC()->customer->get_id(),  'wc_customer_shipping_latitude', true);
                $wc_customer_shipping_longitude = get_user_meta(WC()->customer->get_id(),  'wc_customer_shipping_longitude', true);
            } else {
                $wc_customer_shipping_latitude =  WC()->session->get('wc_customer_shipping_latitude');
                $wc_customer_shipping_longitude = WC()->session->get('wc_customer_shipping_longitude');
            }
            if ($input == 'shipping_calc_shipping_latitude') {
                return $wc_customer_shipping_latitude;
            }
            if ($input == 'shipping_calc_shipping_longitude') {
                return $wc_customer_shipping_longitude;
            }
        }


        function billing_country_update_checkout()
        {
            if (!is_checkout()) return;
?>
            <script type="text/javascript">
                jQuery(function($) {
                    $('#billing_address_1, #billing_address_2').on('change', function(e) {
                        e.preventDefault();
                        $('#billing_calc_shipping_latitude').val('');
                        $('#billing_calc_shipping_longitude').val('');

                    });

                    $('#shipping_address_1, #shipping_address_2').on('change', function(e) {
                        e.preventDefault();
                        $('#shipping_calc_shipping_latitude').val('');
                        $('#shipping_calc_shipping_longitude').val('');
                    });

                });
            </script>
<?php
        }

        function custom_default_address_fields($fields)
        {
            $sorted_fields = array('first_name', 'last_name', 'company', 'address_1', 'address_2', 'country', 'postcode', 'city', 'state');
            $new_fields = array();
            $priority = 0;
            // Reordering billing and shipping fields
            foreach ($sorted_fields as $key_field) {
                $priority += 10;
                $new_fields[$key_field] = $fields[$key_field];
                $new_fields[$key_field]['priority'] = $priority;
            }

            $new_fields['postcode']['required'] = false;

            return $new_fields;
        }



        // add the filter
    }

endif;

return new Deliveree_WC_Custom_Shipping_Methods_Core();
