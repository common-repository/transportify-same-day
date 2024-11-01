<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('WC_Shipping_Deliveree_Actual')) :

    class WC_Shipping_Deliveree_Actual extends WC_Shipping_Method
    {
        protected $totalCost = 0;
        protected $vehicle_type;
        protected $des_lng;
        protected $des_lat;
        protected $service_setting_shipping_delivery_type;
        protected $adjustments;
        protected $adjustments_amount;
        protected $user_type;
        protected $show_default_vehicle_name;
        protected $vehicle_types;

        /**
         * WC_Shipping_Deliveree_Actual constructor.
         * @param int $instance_id
         */
        public function __construct(int $instance_id = 0)
        {
            $this->id = 'deliveree_actual_shipping_method';
            $this->instance_id = absint($instance_id);
            $this->method_title = __(DELIEVEREE_NAME, 'deliveree-same-day');
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
        public function init()
        {
            $bookingApi = new BookingApi();
            $this->instance_form_fields = include('settings/settings-custom-shipping-actual.php');
            // $this->method_description = __('Our plugin dispatches a vehicle that fits the goods for each order.', 'deliveree-same-day');
            $this->service_setting_shipping_delivery_type = $this->get_option('service_setting_shipping_delivery_type', 'Whole vehicle');
            $this->set_delivery_actual_adjustment_shipping_method();
            $this->set_title_shipping_method($bookingApi);
            $this->set_user_type_shipping_method($bookingApi);
        }

        public function set_title_shipping_method($bookingApi, $vehicle_name = '')
        {
            $logo = '<img class="logo_shipping_methods" src="' . DELIEVEREE_URL . '/assets/images/' . DELIEVEREE_NAME . '.png"/>';
            $title = $this->get_option('title', DELIEVEREE_NAME);

            if ($vehicle_name) {
                $this->show_default_vehicle_name =  ' - ' .  $vehicle_name;
            } else {
                $this->vehicle_types    = $bookingApi->getVehicleTypes();
                $this->select_default_vehicle_id    = $this->get_option('select_default_vehicle', 0);
                $select_default_vehicle_name = $this->select_default_vehicle_id && isset($this->vehicle_types[$this->select_default_vehicle_id]) ? $this->vehicle_types[$this->select_default_vehicle_id]['name'] : '';
                $this->show_default_vehicle_name = $select_default_vehicle_name ? ' - ' .  $select_default_vehicle_name : '';
            }

            $this->title = __($logo .  $title . ' (Same Day' .  $this->show_default_vehicle_name . ')', 'deliveree-same-day');
        }

        public function set_user_type_shipping_method($bookingApi)
        {
            if (get_option('deliveree_user_type') == '') {
                $userProfile =  $bookingApi->getUserProfile();
                if (isset($userProfile['user_type'])) {
                    $this->user_type =  $userProfile['user_type'];
                }
            } else $this->user_type =  get_option('deliveree_user_type');
        }

        public function set_delivery_actual_adjustment_shipping_method()
        {
            $oldData = get_option('service_setting_shipping_delivery_actual_adjustment');
            $oldAdjustment = ($this->instance_id && isset($oldData[$this->instance_id])) ? $oldData[$this->instance_id] : [];
            $oldAdjustment = $this->convertOldAdjustmentToNewAdjustment($oldAdjustment);

            $newAdjustment = $this->get_option('service_setting_shipping_delivery_actual_adjustment', '[]');
            $newAdjustment = json_decode($newAdjustment, true);

            $this->service_setting_shipping_delivery_actual_adjustment =  $newAdjustment ?: $oldAdjustment;
        }

        public function process_admin_options()
        {
            if (!$this->instance_id) {
                return parent::process_admin_options();
            }
            $post_data = $this->get_post_data();

            $dataStore = [];
            if (isset($post_data['woocommerce_deliveree_actual_shipping_method_service_setting_shipping_delivery_actual_adjustment'])) {
                $dataStore['adjustment'] = $post_data['woocommerce_deliveree_actual_shipping_method_service_setting_shipping_delivery_actual_adjustment'];
                $dataStore['premium'] = [
                    'currency' => $post_data['deliverree_premium_currency'],
                    'value'   => $post_data['deliverree_premium_value'],
                    'max_cap_currency'   => $post_data['deliverree_premium_maxcap_currency'],
                    'max_cap'   => $post_data['deliverree_premium_maxcap_value']
                ];
                $dataStore['discount'] = [
                    'currency' => $post_data['deliverree_discount_currency'],
                    'value'   => $post_data['deliverree_discount_value'],
                    'max_cap_currency'   => $post_data['deliverree_discount_maxcap_currency'],
                    'max_cap'   => $post_data['deliverree_discount_maxcap_value']
                ];
            }

            $post_data['woocommerce_deliveree_actual_shipping_method_service_setting_shipping_delivery_actual_adjustment'] = json_encode($dataStore);

            foreach ($this->get_instance_form_fields() as $key => $field) {
                if ('title' === $this->get_field_type($field)) {
                    continue;
                }
                try {
                    $this->instance_settings[$key] = $this->get_field_value($key, $field, $post_data);
                } catch (Exception $e) {
                    $this->add_error($e->getMessage());
                }
            }

            update_option($this->get_instance_option_key(), apply_filters('woocommerce_shipping_' . $this->id . '_instance_settings_values', $this->instance_settings, $this), 'yes');



            //OLD data
            // return update_option('service_setting_shipping_delivery_actual_adjustment', $dataStore);
        }


        public function calculate_shipping($package = [])
        {
            $rate = array(
                'id' => $this->id,
                'label' => $this->title,
                'cost' => $this->totalCost
            );

            $rate['meta_data'] = [
                'delivery_type' => $this->service_setting_shipping_delivery_type,
                'adjustments' => $this->adjustments,
                'vehicle_type' => $this->vehicle_type,
                'adjustments_amount' => $this->adjustments_amount,
                'select_default_vehicle_id' => $this->select_default_vehicle_id,
                // 'estimated_delivery_days' => $this->estimated_delivery_days,
                'google' => [
                    'origin_lat' => $this->des_lat,
                    'origin_lng' => $this->des_lng,
                    'distance' => ''
                ]
            ];

            $this->add_rate($rate);
        }

        /**
         * is this method available?
         */
        function is_available($package)
        {

            $available = parent::is_available($package);

            // var_dump($available);die;

            if ($available) {

                $available = $this->get_package_products_data($package);
            }

            return $available;
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
        function get_package_products_data($package)
        {
            $products = $package['contents'];
            $available = true;
            $packs = [];
            $use_default_vehicle_type = true;
            $option_bestfit_rule = get_option('deliveree_services_best_fit_calculation');

            if ($products) {
                foreach ($products as $item_id => $values) {
                    if ($values['data']->needs_shipping()) {
                        $length = ($values['data']->get_length() && $values['data']->get_length() > 0) ? $values['data']->get_length() : 0;
                        $width = ($values['data']->get_width() && $values['data']->get_width() > 0) ? $values['data']->get_width() : 0;
                        $height = ($values['data']->get_height() && $values['data']->get_height() > 0) ? $values['data']->get_height() : 0;
                        $weight = ($values['data']->get_weight() && $values['data']->get_weight() > 0) ? $values['data']->get_weight() : 0;
                        $qty = ($values['quantity'] && $values['quantity'] > 0) ? (int)$values['quantity'] : 0;

                        if ($length > 0 && $width > 0 && $height > 0) {
                            if ($option_bestfit_rule && 'yes' == $option_bestfit_rule) {
                                $use_default_vehicle_type = false;
                            } else {
                                $use_default_vehicle_type = true;
                            }
                        }

                        $packs[] = [
                            'dimensions' => [
                                round($length, 4),
                                round($width, 4),
                                round($height, 4),
                            ],
                            'weight' => round($weight, 4),
                            'quantity' => $qty
                        ];
                    } else {
                        $available = false;
                    }
                }

                $shipping_cost = $this->getShippingCostAPI($package, $packs, $use_default_vehicle_type);

                $this->calculateAdjustmentTotalCost($shipping_cost);
            }

            $available = ($shipping_cost == null) ? false : $available;

            return $available;
        }


        public function getShippingCostAPI($package, $packs, $use_default_vehicle_type)
        {
            $body = array();
            $timeType = 'now';
            $pickupTime = date("d/m/Y h:i");
            $default_fee = $default_vehicle_type = $min_vehicle_type = $min_fee = null;
            $bookingApi = new BookingApi();

            $body['pickup'] = [
                'address' => get_option('deliveree_general_setting_origin_address', ''),
                'latitude' => get_option('deliveree_general_setting_origin_lat', ''),
                'longitude' => get_option('deliveree_general_setting_origin_lng', ''),
                'note' => '',
                'recipient_name' => '',
                'recipient_phone' => '',
            ];

            $deliveree_google_api_key = get_option('deliveree_google_api_key', '');
            if ($deliveree_google_api_key == '') {
                $WC_Countries = new WC_Countries();
                $countries = $WC_Countries->get_countries();
                $country = $countries[$package['destination']['country']] ?? $package['destination']['country'];
                $postCode = $package['destination']['postcode'] ? ' ' . $package['destination']['postcode']  : '';
                $address =  $package['destination']['address_1'] . ', ' . $package['destination']['address_2'] . ', ' . $package['destination']['city'] . ', '  . $package['destination']['state'] . $postCode .  ', ' . $country;
                $latitude =  null;
                $longitude =  null;
            } else {
                $address =  $package['destination']['address'];
                $latitude =  $package['destination']['wc_customer_shipping_latitude'];
                $longitude =  $package['destination']['wc_customer_shipping_longitude'];
            }

            $body['destinations'] = [
                [
                    'address' =>  $address,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'note' => '',
                    'recipient_name' => '',
                    'recipient_phone' => '',
                ]
            ];
            $body['time_type'] = $timeType;
            $body['pickup_time'] = $pickupTime;
            $body['packs'] =  $packs;
            $body['vehicle_type_id'] = $this->select_default_vehicle_id;


            $option_bestfit_rule = get_option('deliveree_services_best_fit_calculation');
            $data = $bookingApi->getQuote($body, 'en');
            if ($data['status'] && $data['data'] && isset($data['response'])) {
                if ($option_bestfit_rule && 'yes' == $option_bestfit_rule) {
                    foreach ($data['response'] as $key => $response) {
                        $bookingFee = $response['total_fees'];
                        $vehicle_type_id = $response['vehicle_type_id'];

                        if ($bookingFee < $min_fee || $min_fee == null) {
                            $min_fee = $bookingFee;
                            $min_vehicle_type =  $response;
                        }

                        if ($this->select_default_vehicle_id == $vehicle_type_id) {
                            $default_fee = $bookingFee;
                            $default_vehicle_type =  $response;
                        }
                    }
                } else {
                    $default_vehicle_type = $data['response'][$this->select_default_vehicle_id];
                    $default_fee = $data['response'][$this->select_default_vehicle_id]['total_fees'];
                }
            }

            $this->des_lat = $body['destinations'][0]['latitude'];
            $this->des_lng =  $body['destinations'][0]['longitude'];
            $this->vehicle_type = ($use_default_vehicle_type && $default_vehicle_type != null) ? $default_vehicle_type : $min_vehicle_type;


            $this->set_title_shipping_method($bookingApi, $this->vehicle_type['vehicle_type_name']);
            $shipping_cost = ($use_default_vehicle_type && $default_fee != null) ? $default_fee : $min_fee;

            return $shipping_cost;
        }

        public function calculateAdjustmentTotalCost($shipping_cost)
        {

            $dataAdjustment = $this->service_setting_shipping_delivery_actual_adjustment;
            $adjustments_value = 0;
            $add_or_sub  =  1;
            $sign =  '';

            if ('bp_account' == $this->user_type && $shipping_cost != null) {
                $adjustments = $dataAdjustment['adjustment'] != '' ? $dataAdjustment[$dataAdjustment['adjustment']] : [];
                if ($adjustments['currency'] == '%') {
                    $percent = (($adjustments['value'] / 100) > 1) ? 1 : ($adjustments['value'] / 100);
                    $adjustments_value = ($shipping_cost * $percent);
                    $max_cap = $adjustments['max_cap'];
                    $adjustments_value = ($adjustments_value > $max_cap &&  $max_cap != '') ? $max_cap : $adjustments_value;
                } else {
                    $adjustments_value = $adjustments['value'];
                }

                if ($dataAdjustment['adjustment'] == 'discount') {
                    $add_or_sub = -1;
                    $sign = '-';
                    $adjustments_value = ($adjustments_value > $shipping_cost) ? $shipping_cost : $adjustments_value;
                }
            }

            $totalCost = $shipping_cost + ($add_or_sub * $adjustments_value);
            $adjustments_amount = $adjustments_value != 0 ? $sign . $adjustments_value : 0;

            $this->totalCost = $totalCost;
            $this->adjustments_amount = $adjustments_amount;
            $this->adjustments = $adjustments;
        }


        public function generate_radio_html($key, $data)
        {
            if ('bp_account' != $this->user_type) {
                return;
            }

            $field_key = $this->get_field_key($key);

            $defaults  = array(
                'title'             => '',
                'disabled'          => false,
                'class'             => '',
                'css'               => '',
                'placeholder'       => '',
                'type'              => 'text',
                'desc_tip'          => false,
                'description'       => '',
                'custom_attributes' => array(),
            );

            $data = wp_parse_args($data, $defaults);

            $data['icon']     = 'woocommerce_' . $this->id . '_icon';
            $data['value']     = $this->get_option($key);


            $dataAdjustment = $this->service_setting_shipping_delivery_actual_adjustment;


            if (empty($dataAdjustment)) {
                $dataAdjustment = [
                    'adjustment' => '',
                    'premium' => [
                        'currency' => '',
                        'value'   => '',
                        'max_cap'   => '',
                        'max_cap_currency'   => '',
                    ],
                    'discount' => [
                        'currency' => '',
                        'value'   => '',
                        'max_cap'   => '',
                        'max_cap_currency'   => '',
                    ],
                ];
            }
            ob_start();
?>
            <tr valign="top">

                <input type="hidden" name="deliveree_zone_id" value="<?php echo $this->instance_id ? esc_attr($this->instance_id) : '' ?>">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr($data['id']) ?>"><?php echo esc_html($data['title']); ?> </label>
                </th>
                <td class="forminp forminp-deliveree-<?php echo esc_attr($data['type']) ?>">
                    <fieldset>
                        <ul>
                            <?php
                            if (!empty($data['options'])) :
                                foreach ($data['options'] as $adjustment_type => $item) :
                                    $adjustment = $dataAdjustment[$adjustment_type] ?? [];
                                    $adjustment_type_checked = $dataAdjustment['adjustment'];
                                    $checked_radio = ($adjustment_type == $adjustment_type_checked) ? 'checked' : '';

                                    switch ($adjustment_type) {
                                        case 'discount':
                                            $tooltip = 'Discount will be deducted from the ' . DELIEVEREE_NAME . ' price. You pay Deliveree the difference.';
                                            break;
                                        case 'premium':
                                            $tooltip = 'Premium will be added to the ' . DELIEVEREE_NAME . ' price. You keep this premium.';
                                            break;
                                        default:
                                            $tooltip = '';
                                            break;
                                    }

                            ?>
                                    <li>
                                        <label><input name="<?php echo esc_attr($field_key); ?>" value="<?php echo esc_attr($adjustment_type) ?>" type="radio" style="<?php echo $data['style'] ? esc_attr($data['style']) : '' ?>" class="<?php echo $data['class'] ? esc_attr($data['class']) : '' ?>" <?php echo esc_attr($checked_radio) ?>> <?php echo esc_html($item) ?></label>
                                        <?php echo $this->get_tooltip_html(['desc_tip' => true, 'description' => $tooltip,]); ?>

                                        <?php if ($adjustment_type != '') : ?>
                                            <div style="width:100%">
                                                <div class="deliverree_<?php echo esc_attr($adjustment_type) ?>_currency_group_input" style="display: <?php echo $checked_radio ? 'inline-block' : 'none' ?>;">
                                                    <div class="adjustment-wrapper" style="display: flex; column-gap: 30px;">
                                                        <div style="display: flex;">
                                                            <div class="select-wrapper">
                                                                <select data-adjustment-type="<?php echo esc_attr($adjustment_type) ?>" name="deliverree_<?php echo esc_attr($adjustment_type) ?>_currency" class="adjustment-currency-select add-class-errors-<?php echo esc_attr($adjustment_type) ?> classic deliverree_<?php echo esc_attr($adjustment_type) ?>_currency_select  <?php echo $adjustment['value'] ? '' : 'red-border' ?>">
                                                                    <?php foreach (['IDR', 'THB', 'PHP', '%'] as $item) : ?>
                                                                        <option value="<?php echo esc_html($item) ?>" <?php echo ((esc_html($adjustment['currency']) ?? '') == $item) ? 'selected="selected"' : '' ?>><?php echo esc_html($item) ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <input data-message-required="Please add a <?php echo esc_attr($adjustment_type) ?>." name="deliverree_<?php echo esc_attr($adjustment_type) ?>_value" id="memory" type="text" data-class-errors="errors-<?php echo esc_attr($adjustment_type) ?>" data-val="<?php echo esc_attr($adjustment['value'])  ?>" data-max="<?php echo ($adjustment['currency'] === '%') ? '100' : '999999999' ?>" class="add-class-errors-<?php echo esc_attr($adjustment_type) ?> form-control deliverree_<?php echo esc_attr($adjustment_type) ?>_currency_value validate_number_decimal validate_number_max validate_required <?php echo $adjustment['value'] ? '' : 'red-border' ?>" value="<?php echo esc_attr($adjustment['value'])  ?>">
                                                        </div>

                                                        <div class="max-cap-wrapper-<?php echo esc_attr($adjustment_type) ?>" style="display: <?php echo ($adjustment['currency'] === '%') ? 'flex' : 'none' ?>;">
                                                            <label class="max-cap">Max cap</label>
                                                            <div class="select-wrapper">
                                                                <select name="deliverree_<?php echo esc_attr($adjustment_type) ?>_maxcap_currency" class="classic deliverree_<?php echo esc_attr($adjustment_type) ?>_currency_select">
                                                                    <?php foreach (['IDR', 'THB', 'PHP'] as $item) : ?>
                                                                        <option value="<?php echo esc_html($item) ?>" <?php echo ((esc_html($adjustment['max_cap_currency']) ?? '') == $item) ? 'selected="selected"' : '' ?>><?php echo esc_html($item) ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <input name="deliverree_<?php echo esc_attr($adjustment_type) ?>_maxcap_value" type="text" class="form-control deliverree_discount_currency_value validate_number_decimal " value="<?php echo esc_attr($adjustment['max_cap'])  ?>">
                                                        </div>
                                                    </div>

                                                    <div class="errors-form errors-<?php echo esc_attr($adjustment_type) ?>">
                                                        <span><?php echo $adjustment['value'] ? '' : 'Please add a ' . esc_html($adjustment_type) . '.'; ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </li>
                            <?php endforeach;
                            endif; ?>
                        </ul>
                    </fieldset>
                </td>
            </tr>

        <?php
            return ob_get_clean();
        }



        public function generate_readonly_html($key, $data)
        {
            $defaults  = array(
                'title'             => '',
                'disabled'          => false,
                'class'             => '',
                'css'               => '',
                'placeholder'       => '',
                'type'              => 'text',
                'desc_tip'          => false,
                'description'       => '',
                'custom_attributes' => array(),
            );

            $data = wp_parse_args($data, $defaults);

            ob_start();
        ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr($data['id']) ?>"><?php echo esc_html($data['title']); ?> </label>
                </th>
                <td class="forminp forminp-deliveree-<?php echo esc_attr($data['type']) ?>">
                    <fieldset>
                        <?php echo esc_html($data['description']); ?>
                    </fieldset>
                </td>
            </tr>

        <?php
            return ob_get_clean();
        }


        public function generate_text_with_logo_html($key, $data)
        {
            $field_key = $this->get_field_key($key);
            $defaults  = array(
                'title'             => '',
                'disabled'          => false,
                'class'             => '',
                'css'               => '',
                'placeholder'       => '',
                'type'              => 'text',
                'desc_tip'          => false,
                'description'       => '',
                'custom_attributes' => array(),
            );

            $data = wp_parse_args($data, $defaults);

            ob_start();
        ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr($field_key); ?>"><?php echo esc_html($data['title']); ?> </label>
                </th>

                <td class="forminp forminp-deliveree-<?php echo esc_attr($data['type']) ?>">
                    <fieldset style="position: relative; left: -29px;">
                        <img class="logo_shipping_methods" src="<?php echo esc_url(DELIEVEREE_URL . 'assets/images/' . DELIEVEREE_NAME . '.png') ?>" />
                        <legend class="screen-reader-text"><span><?php echo wp_kses_post($data['title']); ?></span></legend>
                        <input class="input-text regular-input <?php echo esc_attr($data['class']); ?>" type="text" name="<?php echo esc_attr($field_key); ?>" id="<?php echo esc_attr($field_key); ?>" style="<?php echo esc_attr($data['css']); ?>" value="<?php echo esc_attr($this->get_option($key)); ?>" placeholder="<?php echo esc_attr($data['placeholder']); ?>" <?php disabled($data['disabled'], true); ?> <?php echo $this->get_custom_attribute_html($data); ?> />
                        <?php echo $this->get_description_html($data); ?>
                    </fieldset>
                </td>
            </tr>

        <?php
            return ob_get_clean();
        }


        public function convertOldAdjustmentToNewAdjustment($oldAdjustment)
        {
            $newAdjustment = [];

            if ($oldAdjustment) {
                $adjustment_key_empty = $oldAdjustment['adjustment'] == 'premium' ? 'premium' : 'discount';
                $newAdjustment['adjustment'] = $oldAdjustment['adjustment'];
                $newAdjustment[$oldAdjustment['adjustment']] = $oldAdjustment['data'];
                $newAdjustment[$adjustment_key_empty] = [
                    'currency' => '',
                    'value'   => '',
                    'max_cap_currency'   => '',
                    'max_cap'   => '',
                ];
            }
            return $newAdjustment;
        }

        public function generate_select_default_vehicle_html($key, $data)
        {
            $field_key = $this->get_field_key($key);
            $defaults  = array(
                'title'             => '',
                'disabled'          => false,
                'class'             => '',
                'css'               => '',
                'placeholder'       => '',
                'type'              => 'text',
                'desc_tip'          => false,
                'description'       => '',
                'custom_attributes' => array(),
            );

            $data = wp_parse_args($data, $defaults);

            ob_start();
        ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr($field_key); ?>"><?php echo esc_html($data['title']); ?> </label>
                </th>
                <td class="forminp forminp-deliveree-<?php echo esc_attr($data['type']) ?>">
                    <fieldset>
                        <select class="select-default-vehicle input-text regular-input <?php echo esc_attr($data['class']); ?>" name="<?php echo esc_attr($field_key); ?>" id="<?php echo esc_attr($field_key); ?>" style="<?php echo esc_attr($data['css']); ?>">
                            <?php
                            $detail_selected = '';
                            foreach ($this->vehicle_types as $key_vehicle_types => $vehicle_type) {
                                $detail = ' Dimensions: ' . wp_kses_post($vehicle_type['cargo_length']) . ' cm x ' . wp_kses_post($vehicle_type['cargo_width']) .  ' cm x ' . wp_kses_post($vehicle_type['cargo_height']) . ' cm  &nbsp;&nbsp;&nbsp; Weight: ' . wp_kses_post($vehicle_type['cargo_weight']) . ' kg';
                                $selected =  '';

                                if ($detail_selected == '') {
                                    $detail_selected = $detail;
                                } else if ($this->get_option($key) == $key_vehicle_types) {
                                    $selected =    'selected';
                                    $detail_selected = $detail;
                                }

                                echo '<option ' . esc_attr($selected) . ' value="' . esc_attr($key_vehicle_types) . '">' . wp_kses_post($vehicle_type['name']) . '.' . $detail . ' </option>';
                            } ?>
                        </select>
                        <?php echo $this->get_description_html($data); ?>
                        <?php echo '<div id="selected-vehicle-default" style="margin-top: 5px">' . wp_kses_post($detail_selected) . '</div>'; ?>
                    </fieldset>
                </td>
            </tr>

<?php
            return ob_get_clean();
        }
    }
endif;

function deliverree_actual_validate_order($posted)
{
    $packages = WC()->shipping->get_packages();
    $chosen_methods = WC()->session->get('chosen_shipping_methods');

    if (is_array($chosen_methods) && in_array('deliverree_actual', $chosen_methods)) {

        foreach ($packages as $i => $package) {
            if ($chosen_methods[$i] != "deliveree_actual_shipping_method") {
                continue;
            }

            $shippingMethod = new WC_Shipping_Deliveree_Actual();
            $weightLimit = (int) $shippingMethod->settings['weight'];
            $weight = 0;

            foreach ($package['contents'] as $item_id => $values) {
                $_product = $values['data'];
                $weight = $weight + $_product->get_weight() * $values['quantity'];
            }

            $weight = wc_get_weight($weight, 'kg');

            if ($weight > $weightLimit) {
                $message = sprintf(__('Sorry, %d kg exceeds the maximum weight of %d kg for %s', 'deliveree-same-day'), $weight, $weightLimit, $shippingMethod->title);
                $messageType = "error";
                if (!wc_has_notice($message, $messageType)) {
                    wc_add_notice($message, $messageType);
                }
            }
        }
    }
}

add_action('woocommerce_review_order_before_cart_contents', 'deliverree_actual_validate_order', 10);
add_action('woocommerce_after_checkout_validation', 'deliverree_actual_validate_order', 10);
