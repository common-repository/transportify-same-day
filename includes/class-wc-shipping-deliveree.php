<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('WC_Shipping_Deliveree')) :

    class WC_Shipping_Deliveree extends WC_Shipping_Method
    {
        //google
        protected $distance_api_key = '';
        protected $origin_lng;
        protected $origin_lat;
        protected $des_lng;
        protected $des_lat;
        protected $distance = [];
        protected $urlApiDistance = 'https://maps.googleapis.com/maps/api/distancematrix/json';
        protected $urlGeocode = 'https://maps.google.com/maps/api/geocode/json';
        protected $pricePerKm = [];
        protected $total_dimension = 0;
        protected $totalCost = 0;
        protected $totalCostFLT = 0;

        /**
         * Constructor.
         */
        function __construct($instance_id = 0)
        {
            $this->id = 'deliveree_shipping_method';
            $this->instance_id = absint($instance_id);
            $this->method_title = __(DELIEVEREE_NAME, 'deliveree-same-day');
            $this->method_description = get_option('method_description');
            $this->supports = array(
                'shipping-zones',
                'instance-settings',
                'instance-settings-modal',
            );
            $this->init();

            add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
        }

        /**
         * init user set variables.
         */
        function init()
        {
            $this->instance_form_fields = include('settings/settings-custom-shipping.php');
            $this->title = $this->get_option('title');

            //google api
            $this->distance_api_key = get_option('deliveree_google_api_key', '');
            $this->origin_lat = get_option('deliveree_general_setting_origin_lat', '');
            $this->origin_lng = get_option('deliveree_general_setting_origin_lng', '');

            //shipping
            $this->service_setting_shipping_enable = $this->get_option('service_setting_shipping_enable', 0);
            $this->service_setting_shipping_label = $this->get_option('service_setting_shipping_label', DELIEVEREE_NAME . ' shipping');
            $this->method_description = $this->get_option('method_description');
            $this->service_setting_shipping_delivery_type = $this->get_option('service_setting_shipping_delivery_type', 'FTL');

            //cargo length
            $this->service_setting_shipping_cargo_length_min = $this->get_option('service_setting_shipping_cargo_length_min', 0);
            $this->service_setting_shipping_cargo_length_max = $this->get_option('service_setting_shipping_cargo_length_max', 0);

            //cargo width
            $this->service_setting_shipping_cargo_width_min = $this->get_option('service_setting_shipping_cargo_width_min', 0);
            $this->service_setting_shipping_cargo_width_max = $this->get_option('service_setting_shipping_cargo_width_max', 0);

            //cargo height
            $this->service_setting_shipping_cargo_height_min = $this->get_option('service_setting_shipping_cargo_height_min', 0);
            $this->service_setting_shipping_cargo_height_max = $this->get_option('service_setting_shipping_cargo_height_max', 0);

            //cargo weight
            $this->service_setting_shipping_cargo_weight_min = $this->get_option('service_setting_shipping_cargo_weight_min', 0);
            $this->service_setting_shipping_cargo_weight_max = $this->get_option('service_setting_shipping_cargo_weight_max', 0);

            //cargo dimension
            $this->service_setting_shipping_cargo_dimension_min = $this->get_option('service_setting_shipping_cargo_dimension_min', 0);
            $this->service_setting_shipping_cargo_dimension_max = $this->get_option('service_setting_shipping_cargo_dimension_max', 0);

            //maximum distance
            $this->service_setting_shipping_maximum_distance = $this->get_option('service_setting_shipping_maximum_distance', 0);

            //mininum price
            $this->service_setting_shipping_minimum_price = $this->get_option('service_setting_shipping_minimum_price', 0);

            //starting price
            $this->service_setting_shipping_starting_price = $this->get_option('service_setting_shipping_starting_price', 0);

            $this->initPricePerKm();

            add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
        }

        function process_admin_options()
        {
            if (!$this->instance_id) {
                return parent::process_admin_options();
            }

            // Check we are processing the correct form for this instance.
            if (!isset($_REQUEST['instance_id']) || absint($_REQUEST['instance_id']) !== $this->instance_id) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                return false;
            }

            if ($this->instance_id) {
                $post_data = $this->get_post_data();
                $startPrice = array();
                foreach ($post_data as $key => $val) {
                    if (strpos($key, 'price_per_km_start_') !== false) {
                        $index = str_replace('price_per_km_start_', '', $key);
                        $attrStart = "price_per_km_start_$index";
                        $attrEnd = "price_per_km_end_$index";
                        $attrPricePerKm = "price_per_km_price_$index";
                        $start = isset($post_data[$attrStart]) ? $post_data[$attrStart] : '';
                        $end = isset($post_data[$attrEnd]) ? $post_data[$attrEnd] : '';
                        $price = isset($post_data[$attrPricePerKm]) ? $post_data[$attrPricePerKm] : '';
                        if (isset($post_data[$attrStart]) && isset($post_data[$attrEnd]) && isset($post_data[$attrPricePerKm])) {
                            if ($end != '') {
                                $startPrice[] = array(
                                    'start' => $start,
                                    'end' => $end,
                                    'price' => $price
                                );
                            }
                            if (isset($post_data[$attrStart])) unset($post_data[$attrStart]);
                            if (isset($post_data[$attrEnd])) unset($post_data[$attrEnd]);
                            if (isset($post_data[$attrPricePerKm])) unset($post_data[$attrPricePerKm]);
                        }
                    }
                }

                $post_data['woocommerce_deliveree_shipping_method_service_setting_shipping_price_per_group_km'] = json_encode($startPrice);
            }

            foreach ($this->get_instance_form_fields() as $key => $field) {
                if ('title' === $this->get_field_type($field)) {
                    continue;
                }
                try {
                    $this->instance_settings[$key] = $this->get_field_value($key, $field, $post_data);
                    //check enable/disable method
                    if ('service_setting_shipping_enable' === $key) {
                        $is_enabled = $this->instance_settings[$key];
                        $this->enableDisableMethod($this->instance_id, $is_enabled);
                    }
                } catch (Exception $e) {
                    $this->add_error($e->getMessage());
                }
            }
            return update_option($this->get_instance_option_key(), apply_filters('woocommerce_shipping_' . $this->id . '_instance_settings_values', $this->instance_settings, $this), 'yes');
        }

        /**
         * @return $this
         */
        function initPricePerKm()
        {
            $pricePerKm = $this->get_option('service_setting_shipping_price_per_group_km', 0);
            if ($pricePerKm) {
                $this->pricePerKm = json_decode($pricePerKm, true);
            }
        }

        /**
         * @param int $min
         * @param int $max
         * @param $value
         * @param $available
         * @return bool
         */
        function compareData($min = 0, $max = 0, $value, $available)
        {
            if (!$available) return $available;
            if ($value < $min) return false;
            if ($value > $max) return false;
            return true;
        }

        /**
         * get_package_products_data
         */
        function get_package_products_data($products)
        {
            $available = true;
            if ($products) {
                $totalWeight = 0;
                $totalDimension = floatval(0);
                foreach ($products as $item_id => $values) {
                    if ($values['data']->needs_shipping()) {

                        $length = ($values['data']->get_length() && $values['data']->get_length() > 0) ? $values['data']->get_length() : 0;
                        $width = ($values['data']->get_width() && $values['data']->get_width() > 0) ? $values['data']->get_width() : 0;
                        $height = ($values['data']->get_height() && $values['data']->get_height() > 0) ? $values['data']->get_height() : 0;
                        $weight = ($values['data']->get_weight() && $values['data']->get_weight() > 0) ? $values['data']->get_weight() : 0;
                        $qty = ($values['quantity'] && $values['quantity'] > 0) ? (int)$values['quantity'] : 0;

                        //length
                        $available = $this->compareData(
                            $this->service_setting_shipping_cargo_length_min,
                            $this->service_setting_shipping_cargo_length_max,
                            $length,
                            $available
                        );

                        //width
                        $available = $this->compareData(
                            $this->service_setting_shipping_cargo_width_min,
                            $this->service_setting_shipping_cargo_width_max,
                            $width,
                            $available
                        );

                        //height
                        $available = $this->compareData(
                            $this->service_setting_shipping_cargo_height_min,
                            $this->service_setting_shipping_cargo_height_max,
                            $height,
                            $available
                        );

                        $totalWeight += ($weight * $qty);
                        $dimensionItem = ((floatval($height) / 100) * (floatval($width) / 100) * (floatval($length) / 100)) * $qty;
                        $totalDimension += $dimensionItem;
                        if ($available) {
                            $cost = $this->calculatorShippingPerItem($dimensionItem);
                            //check minimum price LTL

                            $this->totalCost += $cost;
                            $this->totalCostFLT = $cost;
                        }
                    } else {
                        $available = false;
                    }
                }

                if ($this->service_setting_shipping_delivery_type === 'LTL' && $this->totalCost < $this->service_setting_shipping_minimum_price) {
                    $this->totalCost = $this->service_setting_shipping_minimum_price;
                }

                //check total weight
                $available = $this->compareData(
                    $this->service_setting_shipping_cargo_weight_min,
                    $this->service_setting_shipping_cargo_weight_max,
                    $totalWeight,
                    $available
                );

                //So Max Weight >= (Weight of A x Quantity of A) + (Weight of B x Quantity of B)
                if ($totalWeight > $this->service_setting_shipping_cargo_weight_max) {
                    return false;
                }

                //Max Dimension >= (Dimension of A x Quantity of A) + (Dimension of B x Quantity of B)
                $maxCbm = floatval($this->service_setting_shipping_cargo_dimension_max);
                if ($totalDimension > $maxCbm) {
                    return false;
                }

                //Min Dimension if LTL default is 0
                $minCbm = floatval($this->service_setting_shipping_cargo_dimension_min);
                $shippingType = $this->service_setting_shipping_delivery_type;
                if ($shippingType === 'FTL' && $totalDimension < $minCbm) {
                    return false;
                }
            }
            return $available;
        }

        /**
         * @param $dimensionItem
         * @return float|int
         */
        protected function calculatorShippingPerItem($dimensionItem)
        {
            $startPrice = floatval($this->service_setting_shipping_starting_price);
            $evaluateCost = $this->evaluate_cost();
            $cost = $startPrice + $evaluateCost;
            if ($this->service_setting_shipping_delivery_type === 'LTL') {
                $maxCbm = is_numeric($this->service_setting_shipping_cargo_dimension_max) ? intval($this->service_setting_shipping_cargo_dimension_max) : 0;
                if ($maxCbm === 0) {
                    $cost = 0;
                } else {
                    $cost = $cost * ($dimensionItem / $maxCbm);
                }
            }
            return $cost;
        }

        /**
         * is this method available?
         */
        function is_available($package)
        {
            $available = parent::is_available($package);
            if ($available) {

                //check enable/disable shipping method
                if ($this->service_setting_shipping_enable == 'no') {
                    return false;
                }
                $deliveree_services_booking_mode = get_option('deliveree_services_booking_mode', '');

                //check enable/disable shipping method
                if ($deliveree_services_booking_mode == 'auto_assign_booking_mode') {
                    return false;
                }

                //check api key does not setting
                if (!$this->origin_lat || !$this->origin_lng) {
                    return false;
                }

                //calculator distance
                $maximumDistance = (int)$this->service_setting_shipping_maximum_distance;
                $distance = $this->calculateDistanceAddress($package['destination']);
                if ($distance <= 0 || ($distance > $maximumDistance)) {
                    $available = false;
                }

                if ($available) {
                    $available = $this->get_package_products_data($package['contents']);
                }
            }
            return $available;
        }

        /**
         * evaluate a cost from a sum/string.
         */
        function evaluate_cost()
        {
            $cost = 0;
            $distance = ($this->distance) ? (floatval($this->distance['distance']['value'])) : 0;
            if ($this->pricePerKm && $distance > 0) {
                foreach ($this->pricePerKm as $length_item) {
                    if ($length_item['start'] <= $distance) {
                        if ($length_item['end'] <= $distance) {
                            $cost += ($length_item['end'] - $length_item['start']) * floatval($length_item['price']);;
                        } else {
                            $cost += ($distance - $length_item['start']) * floatval($length_item['price']);;
                        }
                    }
                }
            }
            return $cost;
        }

        /**
         * @param $address
         * @return bool|string
         */
        function getGeoCodesForAddress($address)
        {
            try {
                $queryString = http_build_query([
                    'key' => $this->distance_api_key,
                    'components' => implode('|', ['country:' . $address['country'], 'locality:' . $address['city']]),
                    'address' => $address['address_1'] . ' ' . $address['address_2'],
                ]);

                $response = wp_remote_get($this->urlGeocode . '?' . $queryString);
                if (is_array($response) && !is_wp_error($response)) {
                    $body = json_decode($response['body'], true);
                    if ($body['status'] == 'OK') {
                        $location = $body['results'][0]['geometry']['location'];
                        if ($location) {
                            return implode(',', $location);
                        }
                    }
                }
                return false;
            } catch (Exception $e) {
                return false;
            }
        }

        /**
         * Calculate distance from google api
         * @param $dataAddress
         * @return bool|int
         */
        function calculateDistanceAddress($dataAddress)
        {
            if (isset($dataAddress['address'])) {

                if (isset($dataAddress['wc_customer_shipping_latitude']) && $dataAddress['wc_customer_shipping_latitude'] !== "") {
                    $destinationString = $dataAddress['wc_customer_shipping_latitude'] . ',' . $dataAddress['wc_customer_shipping_longitude'];
                } else {
                    $destinationString = $this->getGeoCodesForAddress($dataAddress);
                }
                $pos = explode(',', $destinationString);
                $this->des_lat = isset($pos[0]) ? $pos[0] : null;
                $this->des_lng = isset($pos[1]) ? $pos[1] : null;

                if ($destinationString) {
                    $queryString = http_build_query([
                        'key' => $this->distance_api_key,
                        'origins' => $this->origin_lat . ',' . $this->origin_lng,
                        'destinations' => $destinationString,
                        'mode' => 'DRIVING'
                    ]);

                    $response = wp_remote_get($this->urlApiDistance . '?' . $queryString);
                    if (is_array($response) && !is_wp_error($response)) {
                        $body = json_decode($response['body'], true);
                        if ($body['status'] !== 'OK' || $body['rows'][0]['elements'][0]['status'] === 'ZERO_RESULTS') {
                            return 0;
                        }
                        $this->distance = $body['rows'][0]['elements'][0];
                        if ($this->distance) {
                            /**
                             * 0.1 rounded to 1
                             * 0.5 rounded to 1
                             * convert value distance m => km
                             */
                            $value = ($this->distance['distance']['value'] / 1000);
                            if ($value - floor($value) > 0) {
                                $roundValue = intval($value) + 1;
                                $this->distance['distance']['base'] = $value;
                                $this->distance['distance']['value'] = $roundValue;
                                $this->distance['distance']['text']  = $roundValue . ' km';
                            } else {
                                $this->distance['distance']['value'] = $value;
                            }
                            return $this->distance['distance']['value'];
                        }
                    }
                }
            }
            return 0;
        }

        /**
         * calculate_shipping function.
         */
        function calculate_shipping($package = array())
        {

            $title = DELIEVEREE_NAME . ' - ' . $this->title;
            if (isset($this->distance['distance'])) {
                $title .= ' (' . $this->distance['distance']['text'] . ')';
            }

            $rate = array(
                'id' => $this->get_rate_id(),
                'label' => $title,
                'cost' => 0,
                'package' => $package,
            );

            $rate['meta_data'] = [
                'delivery_type' => $this->service_setting_shipping_delivery_type,
                'google' => [
                    'origin_lat' => $this->des_lat,
                    'origin_lng' => $this->des_lng,
                    'distance' => $this->distance
                ]
            ];
            if ($this->service_setting_shipping_delivery_type === 'LTL') {
                $rate['cost'] = $this->totalCost;
            } else {
                $rate['cost'] = $this->totalCostFLT;
            }


            $this->add_rate($rate);

            do_action('woocommerce_' . $this->id . '_shipping_add_rate', $this, $rate);
        }



        /**
         * Generate js_template field.
         */
        public function generate_js_template_html()
        {
            ob_start();
?>
            <script type="text/template" id="tmpl-deliveree-buttons">
                <div id="deliveree-buttons" class="deliveree-buttons">
                    <# if(data.btn_left) { #>
                    <button id="{{data.btn_left.id}}" class="button button-large">{{data.btn_left.label}}</button>
                    <# } #>
                    <# if(data.btn_right) { #>
                    <button id="deliveree-btn--{{data.btn_right.id}}" class="button button-primary button-large woograbexpress-buttons-item--right">{{data.btn_right.label}}</button>
                    <# } #>
                </div>
            </script>
<?php
            return ob_get_clean();
        }

        public function enableDisableMethod($instance_id, $is_enabled)
        {
            global $wpdb;
            $is_enabled = ($is_enabled == 'yes') ? 1 : 0;
            $wpdb->update("{$wpdb->prefix}woocommerce_shipping_zone_methods", array('is_enabled' => $is_enabled), array('instance_id' => absint($instance_id)));
        }
    }

endif;
