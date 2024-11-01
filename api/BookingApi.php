<?php

class BookingApi
{
    protected $apiKey;
    protected $apiUrl;
    protected $distance_api_key;
    protected $origin_lat;
    protected $origin_lng;
    protected $origin_address;

    public function __construct()
    {
        $this->apiKey = get_option('deliveree_api_key', '');
        $this->apiUrl = DELIEVEREE_API_URL_MODE[get_option('deliveree_api_method', '')] ?? '';
        $this->distance_api_key = get_option('deliveree_google_api_key', '');
        $this->origin_lat = get_option('deliveree_general_setting_origin_lat', '');
        $this->origin_lng = get_option('deliveree_general_setting_origin_lng', '');
        $this->origin_address = get_option('deliveree_general_setting_origin_address', '');
    }

    /**
     * @return array
     */
    public function getVehicleTypes()
    {
        $data = [];
        try {
            $header = [
                'headers' => [
                    'Authorization' => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept-Language' => 'en',
                ]
            ];
            add_filter('http_request_timeout', array($this, 'bump_request_timeout'));
            $response = wp_remote_get($this->apiUrl . '/vehicle_types', $header);


            if (is_array($response) && !is_wp_error($response)) {
                $body = json_decode($response['body'], true);
                if (isset($body['data'])) {
                    // order by: cargo_weight ASC
                    usort($body['data'], function ($a,  $b): int {
                        return $a['cargo_weight'] <=> $b['cargo_weight'];
                    });

                    foreach ($body['data'] as $item) {
                        $data[$item['id']] = $item;
                    }
                }
            }
            return $data;
        } catch (Exception $e) {
            return $data;
        }
    }

    /**
     * @param $body
     * @param $accept_language
     * @return array
     */
    public function getQuote($body, $accept_language)
    {

        $result = [
            'status' => false,
            'message' => ''
        ];
        try {
            $address_data_type = get_option('deliveree_general_setting_origin_data_type', '');
            $option_bestfit_rule = get_option('deliveree_services_best_fit_calculation');

            $pickup[] = $body['pickup'];
            if (
                isset($pickup[0]) &&
                ($pickup[0]['latitude'] == '' ||
                    $pickup[0]['latitude'] == null ||
                    ($address_data_type == 'address' &&
                        $this->distance_api_key == ''
                    )
                )
            ) {
                unset($pickup[0]['latitude']);
                unset($pickup[0]['longitude']);
            }

            $destinations = $body['destinations'];

            foreach ($destinations as $key => $destination) {
                if (isset($destination) && ($destination['latitude'] == ''  ||  $destination['latitude'] == null)) {
                    unset($destinations[$key]['latitude']);
                    unset($destinations[$key]['longitude']);
                }
            }



            $args = [
                'headers' => [
                    'Authorization' => $this->apiKey,
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Accept-Language' => $accept_language,
                ],
                'body' => json_encode(array_merge([
                    "time_type" => $body['time_type'],
                    "pickup_time" => $body['pickup_time'],
                    "locations" => array_merge($pickup, $destinations)
                ], ($option_bestfit_rule && 'yes' == $option_bestfit_rule) ?
                    (isset($body['packs']) ? ["packs" => $body["packs"]] : []) :
                    (isset($body['vehicle_type_id']) ? ["vehicle_type_id" => $body['vehicle_type_id']] : [])
                ), JSON_UNESCAPED_UNICODE)
            ];

            add_filter('http_request_timeout', array($this, 'bump_request_timeout'));
            $response = wp_remote_post($this->apiUrl . '/deliveries/get_quote', $args);
            if (is_array($response) && !is_wp_error($response)) {
                $body = json_decode($response['body'], true);

                if (isset($body['data'])) {
                    $data = [];
                    $value = [];
                    foreach ($body['data'] as $item) {
                        $data[$item['vehicle_type_id']] = $item['vehicle_type_name'];
                        $value[$item['vehicle_type_id']] = $item;
                    }
                    $result['response'] = $value;
                    $result['data'] = $data;
                    $result['status'] = true;
                } else if (isset($body['message'])) {
                    if ($body['message'] == 'Sorry but this address is out of our service area. Please contact us for assistance.') {
                        $result['message'] = 'Sorry but this address (' . $pickup[0]['address'] . ') is out of our service area. Please contact us for assistance.';
                    } else {
                        $result['message'] = $body['message'];
                    }
                }
            }

            return $result;
        } catch (Exception $e) {
            return $result;
        }
    }

    /**
     * @param $body
     * @param $accept_language
     * @return array|string
     */
    public function createBooking($body, $accept_language)
    {
        $data = [
            'status' => false,
            'message' => ''
        ];

        try {
            $deliveree_user_type = get_option('deliveree_user_type', '');
            $address_data_type = get_option('deliveree_general_setting_origin_data_type', '');

            if ('bp_account' != $deliveree_user_type) {
                $body['pickup']['is_payer'] = true;
            }

            $locations[] = $body['pickup'];
            if (
                isset($locations[0]) &&
                ($locations[0]['latitude'] == '' ||
                    $locations[0]['latitude'] == null ||
                    ($address_data_type == 'address' &&
                        $this->distance_api_key == ''
                    )
                )
            ) {
                unset($locations[0]['latitude']);
                unset($locations[0]['longitude']);
            }

            $destinations = $body['destinations'];

            foreach ($destinations as $key => $destination) {
                if (isset($destination) && ($destination['latitude'] == ''  ||  $destination['latitude'] == null)) {
                    unset($destinations[$key]['latitude']);
                    unset($destinations[$key]['longitude']);
                }
            }

            $jobOrderNumber = $body['driver']['job_order_number'];
            if (isset($body['job_order_number'])) {
                $jobOrderNumber = $body['job_order_number'];
            }
            $body['pickup_time'] = str_replace("/", "-", $body['pickup_time']);

            $extra_services = (isset($body['extra_services']) &&  is_array($body['extra_services'])) ? $body['extra_services'] : [];

            $post_data = [
                "vehicle_type_id" => $body['vehicle_type_id'],
                "note" => $body['driver']['note'],
                "time_type" => $body['time_type'],
                "pickup_time" => $body['pickup_time'],
                "job_order_number" => $jobOrderNumber,
                "locations" => array_merge($locations, $destinations),
                "extra_services" => $extra_services,
                "quick_choice_id" => (int)$body['quick_choice_id'],
                "quick_choice" => $body['quick_choice'],
                "optimize_route" => $body['optimize_route']
            ];

            $args = [
                'headers' => [
                    'Authorization' => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept-Language' => $accept_language,
                ],
                'body' => json_encode($post_data)
            ];


            add_filter('http_request_timeout', array($this, 'bump_request_timeout'));
            $response = wp_remote_post($this->apiUrl . '/deliveries', $args);

            if (is_array($response) && !is_wp_error($response)) {
                $body = json_decode($response['body'], true);

                if (isset($body['id'])) {
                    $data['message'] = 'The booking has been create successfully';
                    $data['status'] = true;
                } else if (isset($body['message'])) {
                    if ($body['message'] == 'Sorry but this address is out of our service area. Please contact us for assistance.') {
                        $data['message'] = 'Sorry but this address (' . $locations[0]['address'] . ') is out of our service area. Please contact us for assistance.';
                    } else {
                        $data['message'] = $body['message'];
                    }
                }
            }
            return $data;
        } catch (Exception $e) {
            return $data['message'] = $e->getMessage();
        }
    }

    /**
     * @param $paged
     * @return bool|mixed
     */
    public function getListBookings($paged)
    {
        try {
            $header = [
                'headers' => [
                    'Authorization' => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept-Language' => 'en',
                ]
            ];
            add_filter('http_request_timeout', array($this, 'bump_request_timeout'));
            $response = wp_remote_get($this->apiUrl . "/deliveries?page=$paged&per_page=50", $header);
            if (is_array($response) && !is_wp_error($response)) {
                $body = json_decode($response['body'], true);
                if ($body) {
                    return $body;
                }
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    function bump_request_timeout()
    {
        return 700000;
    }

    /**
     * @param $id
     * @param $accept_language
     * @return array
     */
    public function cancelBooking($id, $accept_language)
    {
        $data = [
            'status' => false,
            'message' => '',
            'data' => ''
        ];
        try {
            $header = [
                'headers' => [
                    'Authorization' => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept-Language' => $accept_language,
                ]
            ];

            $response = wp_remote_post($this->apiUrl . "/deliveries/$id/cancel", $header);
            if (is_array($response) && !is_wp_error($response)) {
                $body = json_decode($response['body'], true);
                if ($body && !isset($body['error'])) {
                    $data['data'] = $data;
                } elseif (isset($body['error'])) {
                    $data['message'] = $body['error'];
                } elseif (isset($body['message'])) {
                    $data['message'] = $body['message'];
                }
            }
            return $data;
        } catch (Exception $e) {
            return $data;
        }
    }

    protected function getGeoCodesForAddress($address)
    {
        $place_api_url_builder = 'https://maps.googleapis.com/maps/api/geocode/json?address='
            . urlencode($address)
            . '&key=' . $this->distance_api_key;
        $response          = wp_remote_get($place_api_url_builder);
        if (is_array($response) && !is_wp_error($response)) {
            $body = json_decode($response['body'], true);
            if ($body['status'] == 'OK') {
                $location = $body['results'][0]['geometry']['location'];
                if ($location) {
                    return [
                        'address' => $address,
                        'latitude' => $location['lat'],
                        'longitude' => $location['lng'],
                    ];
                }
            }
        }
        return null;
    }

    public function get_address_from_lat_lng($lat, $lng)
    {
        try {
            $place_api_url_builder = 'https://maps.googleapis.com/maps/api/geocode/json?latlng='
                . $lat . ',' . $lng
                . '&key=' . $this->distance_api_key;
            $response          = wp_remote_get($place_api_url_builder);
            if (is_array($response) && !is_wp_error($response)) {
                $body = json_decode($response['body'], true);
                if ($body['status'] == 'OK') {
                    return $body['results'][0]['formatted_address'];
                }
            }
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getQuoteByBoookingOrders($boookingOrders, $timeType = 'now', $pickupTime = '')
    {
        $listItems = $packs = $body = array();
        $body['destinations'] = [];
        $select_default_vehicle_id = 0;


        $body['pickup'] = [
            'address' => get_option('deliveree_general_setting_origin_address', ''),
            'latitude' => get_option('deliveree_general_setting_origin_lat', ''),
            'longitude' => get_option('deliveree_general_setting_origin_lng', ''),
            'note' => '',
            'recipient_name' => '',
            'recipient_phone' => '',
        ];

        foreach ($boookingOrders as $index => $item) {
            if (!$select_default_vehicle_id) {
                $order = wc_get_order($item['order_id']);
                $shippingMethods = $order->get_shipping_methods();
                $firstShippingMethod = array_shift($shippingMethods);
                $instance_id = $firstShippingMethod->get_instance_id();
                $method_instance = new WC_Shipping_Deliveree_Actual($instance_id);
                $select_default_vehicle_id = $method_instance->get_option('select_default_vehicle') ?: 0;
            }

            $data_order = json_decode($item['data_order'], true);
            $product_id = array_column($data_order, 'product_id');
            $args = array(
                'include' => $product_id,
            );
            $products = wc_get_products($args);
            foreach ($products as $key => $product) {
                $key = array_search($product->ID, array_column($data_order, 'product_id'));
                 $packs[] = [
                     'dimensions' => [
                         1, //round($product->length, 4),
                         1, //round($product->width, 4),
                         1, //round($product->height, 4),
                     ],
                     'weight' => 1, //round($product->weight, 4),
                     'quantity' => $data_order[$key]['qty']
                 ];
            }

            $locations = json_decode($item['locations'], true);
            if (!isset($body['pickup'])) {
                $body['pickup'] = $locations['pickup'];
            }

            $body['destinations'] = array_merge($body['destinations'], $locations['destinations']);
        }


        $body['time_type'] = $timeType;
        $body['pickup_time'] = $pickupTime;
        // $body['packs'] = $packs;
        // $body['vehicle_type_id'] = $select_default_vehicle_id;

        $data = $this->getQuote($body, 'en');



        if ($data['status'] && isset($data['response'])) {
            $bookingFee = 0;

            // order by: cargo_weight ASC
            usort($data['response'], function ($a,  $b): int {
                return $a['vehicle_type']['cargo_weight'] <=> $b['vehicle_type']['cargo_weight'];
            });

            foreach ($data['response'] as $key => $value) {
                $item['currency']  = '';
                $vehicle_type_id = $value['vehicle_type_id'];
                if (isset($value)) {
                    $bookingFee = $value['total_fees'];
                    $item['currency'] =  $value['currency'];
                }
                $item['vehicle_type_id'] = "<input type='hidden' value='$vehicle_type_id' class='select_vehicle_type_id' > " . $value['vehicle_type_name'];
                $item['booking_fee'] = $bookingFee;
                $item['pickup_time'] = (isset($_REQUEST['pickup_time'])) ? sanitize_text_field($_REQUEST['pickup_time']) : '';
                $item['time_type'] = ($timeType) ? ucfirst($timeType) : '';
                $item['total_distance'] = $value['total_distance'];
                $item['dimention_params']['cargo_cubic_meter'] = $value['vehicle_type']['cargo_cubic_meter'];
                $item['dimention_params']['cargo_length'] = $value['vehicle_type']['cargo_length'];
                $item['dimention_params']['cargo_height'] = $value['vehicle_type']['cargo_height'];
                $item['dimention_params']['cargo_width'] = $value['vehicle_type']['cargo_width'];
                $item['dimention_params']['cargo_weight'] = $value['vehicle_type']['cargo_weight'];
                $item['vehicle_type_response'] = $value;
                $item['is_default_vehicle_id'] = $select_default_vehicle_id == $vehicle_type_id;
                $listItems[] = $item;
            }
        }

        return $listItems;
    }
    /*
    *ids : string
    */
    public function createBoookingByBoookingId($data = [])
    {
        global $wpdb;

        $response = [
            'status' => null,
            'message' => false,
        ];

        if ($data['order_ids']) {
            $order_ids_str = '';

            if (is_array($data['order_ids']) && !empty($data['order_ids'])) {
                $order_ids_str = implode(',', $data['order_ids']);
            }

            $table_name = $wpdb->prefix . DELIEVEREE_BOOKING_TABLE_NAME;
            $orderby = 'FIELD (order_id, ' . $order_ids_str . ' )';
            $sql = "SELECT * FROM $table_name WHERE ( booking_confirm = 0 AND order_id IN($order_ids_str)) ORDER BY $orderby asc";
            $boookingOrders = $wpdb->get_results($sql, ARRAY_A);

            $job_order_number = [];
            $pickup = '';

            $destinations = [];
            foreach ($boookingOrders as $index => $item) {
                $locations = json_decode($item['locations'], true);
                $job_order_number[] = '#' . $item['order_id'];
                $pickup = $locations['pickup'];
                $destinations = array_merge($destinations, $locations['destinations']);
            }

            $job_order_number_str = implode(',', $job_order_number);

            $default = array(
                'vehicle_type_id' => '',
                'time_type' => '',
                'pickup_time' => '',
                'job_order_number' => $job_order_number_str,
                'pickup' => $pickup,
                'destinations' => $destinations,
                'extra_services' => [],
                'vehicle_type' => [],
                'quick_choice_id' => '',
                'quick_choice' => false,
                'optimize_route' => false,
            );

            $data['optimize_route'] = $data['optimize_route'] ? true : false;

            $body = shortcode_atts($default, $data);

            $response = $this->createBooking($body, 'en');

            if ($response['status'] && !empty($body['vehicle_type'])) {
                $table_name = $wpdb->prefix . DELIEVEREE_BOOKING_TABLE_NAME;
                $sql = "UPDATE $table_name SET booking_confirm = 1, vehicle_type = '" . sanitize_text_field(json_encode($body['vehicle_type'])) . "', vehicle_type_name = '" . sanitize_text_field($body['vehicle_type']['vehicle_type_name']) . "'  WHERE order_id IN(" . $order_ids_str . ") ";

                $wpdb->query($wpdb->prepare($sql));
            }
        } else {
            $response = [
                'status' => false,
                'message' => 'Please select at least one item',
            ];
        }

        $response['order_ids'] = $job_order_number_str;

        return $response;
    }

    /**
     * @param $paged
     * @return bool|mixed
     */
    public function getUserProfile()
    {
        try {
            $header = [
                'headers' => [
                    'Authorization' => $this->apiKey,
                ]
            ];
            add_filter('http_request_timeout', array($this, 'bump_request_timeout'));
            $response = wp_remote_post($this->apiUrl . "customers/user_profile", $header);

            if (is_array($response) && !is_wp_error($response)) {
                $body = json_decode($response['body'], true);
                if (isset($body['user_type'])) {
                    return $body;
                }
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
}
