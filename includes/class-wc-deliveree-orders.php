<?php
add_action('woocommerce_checkout_order_created', 'create_order_with_shipping_deliveree', 10, 1);
function create_order_with_shipping_deliveree($order)
{
    try {
        $shippingMethod = @array_shift($order->get_shipping_methods());
        if (
            $shippingMethod &&
            ($shippingMethod->get_method_id() === 'deliveree_shipping_method' ||
                $shippingMethod->get_method_id() === 'deliveree_actual_shipping_method')
        ) {

            $shippingMethodData = getShippingMethodData($shippingMethod, $order);
            insertBoookingOrdersDeliveree($shippingMethodData, $order, $shippingMethod);
            autoAssignBookingMode($shippingMethodData, $order);
        }
        return $order;
    } catch (Exception $e) {
        //does not thing
        return $order;
    }
}


function getShippingMethodData($shippingMethod, $order)
{
    $delivery_type = $shippingMethod->get_meta('delivery_type');
    $serviceLabel = $shippingMethod->get_name();
    $customer = $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name();

    $address_1 = $order->get_shipping_address_1();
    $address_2 = $order->get_shipping_address_2() ? $order->get_shipping_address_2() . ', ' : '';
    $city = $order->get_shipping_city();
    $state = $order->get_shipping_state();
    $country_code = $order->get_shipping_country();
    $postCode = $order->get_shipping_postcode() ? ' ' . $order->get_shipping_postcode() : '';

    $WC_Countries = new WC_Countries();
    $countries = $WC_Countries->get_countries();
    $country = $countries[$country_code] ?? $country_code;

    $deliveree_google_api_key = get_option('deliveree_google_api_key', '');
    if ($deliveree_google_api_key == '') {
        $shippingAddress =  $address_1 . ', ' . $address_2 . $city . ', ' . $state . $postCode . ', ' . $country;
    } else {
        $shippingAddress = $address_1;
    }


    $shippingCity = $order->get_shipping_city();
    $shippingPostCode = $order->get_shipping_postcode();
    $purchaseDate = $order->get_date_created()->format('Y-m-d H:i:s');
    $purchaseDate = get_date_from_gmt($purchaseDate, DateTime::ATOM);

    $paidShipping = $order->get_shipping_total();
    $googleData = $shippingMethod->get_meta('google') ?: [];
    $vehicle_type = $shippingMethod->get_meta('vehicle_type') ?: [];
    $adjustments = $shippingMethod->get_meta('adjustments') ?: [];
    $adjustments_amount = $shippingMethod->get_meta('adjustments_amount') ?: 0;

    $phone = '';
    if (method_exists($order, 'get_billing_phone')) {
        $phone = $order->get_billing_phone();
    }

    if (method_exists($order, 'get_shipping_phone')) {
        $phone = $order->get_shipping_phone() != '' ? $order->get_shipping_phone() : $order->get_billing_phone();
    }

    return [
        'delivery_type' => $delivery_type,
        'serviceLabel' => $serviceLabel,
        'customer' => $customer,
        'shippingAddress' => $shippingAddress,
        'shippingCity' => $shippingCity,
        'shippingPostCode' => $shippingPostCode,
        'purchaseDate' => $purchaseDate,
        'paidShipping' => $paidShipping,
        'googleData' => $googleData,
        'vehicle_type' => $vehicle_type,
        'adjustments' => $adjustments,
        'adjustments_amount' => $adjustments_amount,
        'phone' => $phone,
    ];
}


function insertBoookingOrdersDeliveree($shippingMethodData, $order, $shippingMethod)
{
    global $wpdb;

    list($items, $weight, $dimensions, $volume, $totalOrder, $dataOrder) = getProductData($order);
    $settings = get_option("woocommerce_deliveree_shipping_method_" . $shippingMethod->get_instance_id() . "_settings");
    $origin_lat = get_option('deliveree_general_setting_origin_lat', '');
    $origin_lng = get_option('deliveree_general_setting_origin_lng', '');
    $origin_address = get_option('deliveree_general_setting_origin_address', '');

    $locations = [
        'pickup' => [
            "address" => $origin_address,
            "latitude" => $origin_lat,
            "longitude" => $origin_lng,
            "note" => "",
            "recipient_name" => $settings['title'],
            "recipient_phone" => $shippingMethodData['phone']
        ],
        'destinations' => [
            [
                "address" => $shippingMethodData['shippingAddress'],
                "latitude" => $shippingMethodData['googleData']['origin_lat'],
                "longitude" => $shippingMethodData['googleData']['origin_lng'],
                "note" => "",
                "recipient_name" => $shippingMethodData['customer'],
                "recipient_phone" => $shippingMethodData['phone']
            ]
        ],
    ];

    $table_name = $wpdb->prefix . DELIEVEREE_BOOKING_TABLE_NAME;
    $dataInsert = array(
        'id' => 0,
        'order_id' => $order->get_id(),
        'drop_no' => '',
        'delivery_type' => $shippingMethodData['delivery_type'],
        'service_label' => $shippingMethodData['serviceLabel'],
        'customer' => $shippingMethodData['customer'],
        'shipping_address' => $shippingMethodData['shippingAddress'],
        'purchase_date' => $shippingMethodData['purchaseDate'],
        'item' => $items,
        'total_order' => $totalOrder,
        'dimensions' => $dimensions,
        'volume' => $volume,
        'weight' => $weight,
        'paid_shipping' => $shippingMethodData['paidShipping'],
        'google_data' => json_encode($shippingMethodData['googleData']),
        'booking_confirm' => 0,
        'locations' => json_encode($locations, JSON_UNESCAPED_UNICODE),
        'data_order' => json_encode($dataOrder, JSON_UNESCAPED_UNICODE),
        'shipping_method_id' => $shippingMethod->get_method_id(),
        'city' =>  $shippingMethodData['shippingCity'],
        'postal_code' => $shippingMethodData['shippingPostCode'],
        'vehicle_type' => json_encode($shippingMethodData['vehicle_type']),
        'adjustments' => json_encode($shippingMethodData['adjustments']),
        'vehicle_type_name' => $shippingMethodData['vehicle_type']['vehicle_type_name'],
        'adjustments_amount' => $shippingMethodData['adjustments_amount'],

    );

    $dataInsert['full_text_search'] = implode(' ', $dataInsert);

    $wpdb->insert($table_name, $dataInsert);
}

function autoAssignBookingMode($shippingMethodData, $order)
{
    $deliveree_services_booking_mode = get_option('deliveree_services_booking_mode', '');

    if ($deliveree_services_booking_mode == 'auto_assign_booking_mode') {
        $deliveree_services_pickup_hours = get_option('deliveree_services_pickup_hours', '');
        $option_value = wc_parse_relative_date_option($deliveree_services_pickup_hours);
        $time_type = 'now';

        $now_format_date = current_time('Y-m-d');
        $now_format_hours = current_time('H:i:s');
        $hours_value_start = ($option_value['start']) ? $option_value['start'] . ':00' : '00:00:00';
        $hours_value_end = ($option_value['end']) ? $option_value['end'] . ':59' : '23:59:59';

        $time_now = strtotime($now_format_hours);
        $time_open = strtotime($hours_value_start);
        $time_close = strtotime($hours_value_end);


        $quick_choice_id = '';
        $quick_choice = false;

        $now_format_date_time = current_time('Y-m-d H:i:s');
        $pickup_time = new DateTime($now_format_date_time, wp_timezone());

        $quick_choices = $shippingMethodData['vehicle_type']['vehicle_type']['quick_choices'];
        if (!empty($quick_choices)) {
            usort($quick_choices, function ($a, $b) {
                return $a['schedule_time'] - $b['schedule_time'];
            });

            $quick_choice_id = $quick_choices[0]['id'];
            $quick_choice = true;

            $pickup_time->modify('+' . $quick_choices[0]['schedule_time'] . ' minutes');
        }

        $bookingApi = new BookingApi();

        if ('custom_time' == $option_value['type'] && ($time_now < $time_open || $time_now > $time_close)) {
            $time_type = 'schedule';
            $time_quick_choice = $pickup_time->format('H:i:s');
            $time_quick_choice = strtotime($time_quick_choice);

            if ($time_quick_choice > $time_open && $time_quick_choice <  $time_close) {
                $quick_choice = false;
            } else {
                $pickup_time = new DateTime($now_format_date . ' ' .  $hours_value_start, wp_timezone());
            }

            if ($time_now > $time_close) {
                $pickup_time->modify('+1 day');
            }
        }


        $pickup_time = ($pickup_time != '') ? $pickup_time->format(DateTime::ATOM) : '';


        $data = [
            'order_ids' => [$order->get_id()],
            'time_type' => $time_type,
            'pickup_time' =>  $pickup_time,
            'vehicle_type_id' => $shippingMethodData['vehicle_type']['vehicle_type_id'],
            'vehicle_type' => $shippingMethodData['vehicle_type'],
            'quick_choice_id' => $quick_choice_id,
            'quick_choice' => $quick_choice,
        ];

        $data =  $bookingApi->createBoookingByBoookingId($data);
    }
}


function getProductData($order)
{
    $items = [];
    $weight = [];
    $dimensions = [];
    $volume = 0;
    $totalOrder = 0;
    $totalWeight = 0;
    $dataOrder = [];
    if ($orderItems = $order->get_items()) {
        foreach ($orderItems as $item) {
            $product = $item->get_product();
            if ($product) {
                $items[] = $product->get_name();
                $dimensions[] = $item->get_product()->get_dimensions();
                $weight[] = $item->get_product()->get_weight();
                $dataDimension = $item->get_product()->get_dimensions(false);
                $qty = $item->get_quantity();
                $totalWeight += ($item->get_product()->get_weight() * $qty);
                $dataOrder[] = [
                    'product_id' => $product->get_id(),
                    'qty' => $item->get_quantity(),
                ];
                if ($dataDimension) {
                    $length = (isset($dataDimension['length'])) ? $dataDimension['length'] : 0;
                    $width = (isset($dataDimension['width'])) ? $dataDimension['width'] : 0;
                    $height = (isset($dataDimension['height'])) ? $dataDimension['height'] : 0;
                    $volume += ($length * $width * $height) * $qty;
                }
                $totalOrder += 1;
            }
        }

        //tot
        $volume =  ($volume / 1000000);
    }
    return [
        implode(',', $items),
        $totalWeight,
        implode(',', $dimensions),
        $volume,
        $totalOrder,
        $dataOrder
    ];
}

//handle shipping method
add_action('woocommerce_shipping_zone_method_status_toggled', 'deliveree_shipping_zone_method_status_toggled', 10, 1);
function deliveree_shipping_zone_method_status_toggled()
{
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'woocommerce_shipping_zone_methods_save_changes') {
        global $wpdb;
        $changes = wp_unslash($_POST['changes']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if (isset($changes['methods'])) {
            foreach ($changes['methods'] as $instance_id => $data) {
                $method_id = $wpdb->get_var($wpdb->prepare("SELECT method_id FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE instance_id = %d", $instance_id));
                $method_data = array_intersect_key(
                    $data,
                    array(
                        'method_order' => 1,
                        'enabled'      => 1,
                    )
                );

                if ($method_id == 'deliveree_shipping_method') {
                    if (isset($method_data['enabled'])) {
                        $is_enabled = ('yes' === $method_data['enabled']) ? 'yes' : 'no';
                        $options = get_option("woocommerce_deliveree_shipping_method_{$instance_id}_settings");
                        if ($options && isset($options['service_setting_shipping_enable'])) {
                            $options['service_setting_shipping_enable'] = $is_enabled;
                            update_option("woocommerce_deliveree_shipping_method_{$instance_id}_settings", $options);
                        }
                    }
                }
            }
        }
    }
}

add_filter('woocommerce_cart_no_shipping_available_html', 'change_cart_no_shipping_available_html');
function change_cart_no_shipping_available_html($string)
{
    return esc_html__('There’s no shipping option available for this location, please check your address again to checkout.', 'deliveree-same-day');
}


add_filter('woocommerce_validate_postcode', 'validate_postcode', 10, 3);
function validate_postcode($valid, $postcode, $country)
{

    switch ($country) {
        case 'ID':
            $valid = (bool) preg_match('/^([0-9]{1,7})$/', $postcode);
            break;
    }

    return  $valid;
}


function ra_change_translate_text($translated_text, $untranslated_text, $domain)
{
    if ($domain == "woocommerce") {
        switch ($translated_text) {
            case 'Please enter a valid postcode / ZIP.':
                $translated_text = 'Please enter a valid postal code.';
                break;
            default:
                # code...
                break;
        }
    }

    return $translated_text;
}
add_filter('gettext', 'ra_change_translate_text', 10, 3);


$this->origin_lat = get_option('deliveree_general_setting_origin_lat', '');
$this->origin_lng = get_option('deliveree_general_setting_origin_lng', '');
