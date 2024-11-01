<?php

/**
 * BookingDeliveree class that will display our custom table
 * records in nice table
 */

require_once(DELIEVEREE_PATH  . '/api/BookingApi.php');

class BookingDeliveree extends WP_List_Table
{
    public $listItems = [];
    public $loadedItems = [];
    public $counterItems = [];
    public $bookingItems = [];
    public $user_type;
    public $currency = '';


    /**
     * BookingDeliveree constructor.
     */
    function __construct()
    {
        global $status, $page;
        parent::__construct(array(
            'singular' => 'bookings_deliveree',
            'plural' => 'bookings_deliveree',
        ));
    }

    /**
     * [REQUIRED] this is a default column renderer
     *
     * @param $item - row (key, value array)
     * @param $column_name - string (key)
     * @return HTML
     */
    function column_default($item, $column_name)
    {
        if (isset($item[$column_name])) {
            return $item[$column_name];
        }
    }

    function column_pickup_time($item)
    {

        if (in_array($item['id'], $this->loadedItems)) {
            return '';
        }

        if (isset($item['pickup_time'])) {
            return $item['pickup_time'];
        }
    }

    /**
     * @return array
     */
    function get_columns()
    {


        $columns = array(
            'id' => __('Deliveree ID', 'deliveree-same-day'),
            'job_order_number' => __('Order #', 'deliveree-same-day'),
            // 'adjustments' => __('Adjustments', 'deliveree-same-day'),
            // 'shipping_methods' => __('Shipping Methods', 'deliveree-same-day'),
            'time_type' => __('Time Type', 'deliveree-same-day'),
            'vehicle_type' => __('Vehicle', 'deliveree-same-day'),
            'pickup_time' => __('Pickup Time', 'deliveree-same-day'),
            'customer_name' => __('Customer Name', 'deliveree-same-day'),
            'shipping_address' => __('Shipping Address', 'deliveree-same-day'),
            'items' => __('Item(s)', 'deliveree-same-day'),
            'status' => __('Status', 'deliveree-same-day'),
            'action' => __('Action', 'deliveree-same-day'),
        );
        return $columns;
    }

    function column_id($item)
    {
        $delivereeLinkbooking = DELIEVEREE_BOOKING_URL_MODE[get_option('deliveree_api_method', '')] ?? '';

        if (in_array($item['id'], $this->loadedItems)) {
            return '';
        }

        if ($item['id']) {
            if (isset($item['tracking_url'])) {
                // return '<a target="_balnk" href="'.$item['tracking_url'].'">'.$item['id'].'</a>';
                return '<a target="_blank" href="' . $delivereeLinkbooking . $item['id'] . '">' . $item['id'] . '</a>';
            }
            return $item['id'];
        }
    }


    function column_shipping_methods($item)
    {
        if (in_array($item['id'], $this->loadedItems)) {
            return '';
        }

        $shipping_method = 'n/a';

        if (isset($item['shipping_method_id'])) {

            switch ($item['shipping_method_id']) {
                case 'deliveree_actual_shipping_method':
                    $shipping_method = DELIEVEREE_NAME;
                    break;
                    // case 'deliveree_shipping_method':
                    //     $shipping_method = DELIEVEREE_NAME . " fixed";
                    //     break;
            }
        }
        return $shipping_method;
    }


    function column_time_type($item)
    {
        if (in_array($item['id'], $this->loadedItems)) {
            return '';
        }

        if (isset($item['time_type'])) {
            if ($item['time_type'] == 'now') {
                $item['time_type'] = 'Immediate';
            }
            return  ucfirst($item['time_type']);
        }
        return null;
    }

    function column_vehicle_type($item)
    {
        if (in_array($item['id'], $this->loadedItems)) {
            return '';
        }

        if (isset($item['vehicle_type'])) {
            return $item['vehicle_type'];
        }
        return null;
    }

    function column_job_order_number($item)
    {
        return $item['order_id'];
    }

    // public function column_adjustments($item)
    // {
    //     $adjustments = json_decode($item['adjustments'], true);
    //     $adjustments_amount = '';

    //     if (is_array($adjustments) && !empty($adjustments)) {
    //         $order = wc_get_order($item['order_id']);
    //         $currency_code = $order->get_currency();
    //         $this->currency = get_woocommerce_currency_symbol($currency_code);

    //         $adjustments_amount = $this->currency . ' ' . $item['adjustments_amount'];
    //     }

    //     if ('bp_account' != $this->user_type && $item['adjustments_amount'] == 0) {
    //         $adjustments_amount = 'n/a';
    //     }

    //     return $adjustments_amount;
    // }

    public function column_customer_name($item)
    {
        if (isset($item['locations']) && isset($item['locations']['recipient_name'])) {
            return $item['locations']['recipient_name'];
        }
    }

    public function column_shipping_address($item)
    {
        if (isset($item['google_data'])) {
            $google_data = json_decode($item['google_data']);
        }

        $google_map_link = '';
        if (isset($item['google_data']) && $google_data->origin_lat && $google_data->origin_lng) {
            $google_map_link = '<a target="_blank" href="https://maps.google.com/?q=' . $google_data->origin_lat . ',' . $google_data->origin_lng . '" >' . $item['locations']['name'] . '<a>';
        } else if (isset($item['locations']) && isset($item['locations']['name'])) {
            $google_map_link = '<a target="_blank" href="https://maps.google.com/?q=' . $item['locations']['name'] . '" >' . $item['locations']['name'] . '<a>';
        }

        return $google_map_link;
    }

    public function column_items($item)
    {
        $productItems = [];
        if (isset($item['job_order_number']) && $item['job_order_number']) {
            $orderIds = explode(',', $item['job_order_number']);
            if ($orderIds) {
                //foreach($orderIds as $order_id){
                try {
                    if (count($this->counterItems) > 0 && isset($this->counterItems[$item['id']]) && isset($orderIds[$this->counterItems[$item['id']]])) {
                        $order_id = str_replace('#', '', trim($orderIds[$this->counterItems[$item['id']]]));
                    } else {
                        $order_id = str_replace('#', '', trim($orderIds[0]));
                    }
                    $order = wc_get_order($order_id);
                    if ($order) {
                        foreach ($order->get_items() as $item_key => $item) {
                            $product = $item->get_product();
                            if ($product) {
                                $productItems[] = $product->get_name() . '(' . $item->get_quantity() . ')';
                            }
                        }
                    }
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
                // }
            }
        }
        return implode(', ', $productItems);
    }

    function column_status($item)
    {
        if (in_array($item['id'], $this->loadedItems)) {
            return '';
        }


        if (isset($this->counterItems[$item['id']])) {
            $this->counterItems[$item['id']] = intval($this->counterItems[$item['id']]) + 1;
        } else {
            $this->counterItems[$item['id']] = 1;
        }

        if (isset($item['status'])) {
            $new_status = $this->conver_status($item['status']);
            return '<span class="status ' . $new_status['status'] . '">' . ucfirst($new_status['label']) . '</span>';
        }
    }

    function conver_status($status)
    {
        $label = str_replace('_', ' ', $status);

        switch ($status) {
            case 'delivery_completed':
                $label = 'Delivery completed';
                $status = DELIEVEREE_BOOKING_STATUS_DONE;
                break;
            case 'driver_accept_booking':
            case 'fleet_accept_booking':
                $label = 'Driver accept booking';
                $status = DELIEVEREE_BOOKING_STATUS_CONFIRMED;
                break;
            case 'locating_driver_timeout':
            case 'assigning_driver_timeout':
            case 'fleet_timeout':
                $label = 'Time out';
                $status = DELIEVEREE_BOOKING_STATUS_TIME_OUT;
                break;
            case 'canceled':
            case 'driver_declined_booking':
            case 'canceled_rebook':
            case 'cancel_to_edit':
                $label = 'Canceled';
                $status = DELIEVEREE_BOOKING_STATUS_CANCELED;
                break;
            case 'delivery_in_progress':
                $label = 'Delivery in progress';
                $status = DELIEVEREE_BOOKING_STATUS_IN_PROCESS;
                break;
            case 'cs_finding_driver':
            case 'assigning_driver':
            case 'locating_driver':
                $label = 'Locating Driver';
                $status = DELIEVEREE_BOOKING_STATUS_LOCATING;
                break;
            default:
                # code...
                break;
        }

        return [
            'status' => $status,
            'label' => $label,
        ];
    }



    function column_action($item)
    {
        if (in_array($item['id'], $this->loadedItems)) {
            return '';
        }

        $this->loadedItems[] = $item['id'];

        $new_status = $this->conver_status($item['status']);
        $options = [];

        switch ($new_status['status']) {
            case DELIEVEREE_BOOKING_STATUS_DONE:
            case DELIEVEREE_BOOKING_STATUS_CANCELED:
                $action = '';
                break;
            case DELIEVEREE_BOOKING_STATUS_TIME_OUT:
                $options[] = '<a class="dropdown-item cancel-booking" data-id="' . $item['id'] . '">Cancel booking</a>';
                $action = $this->getActionButton($item, $options);
                break;
            case DELIEVEREE_BOOKING_STATUS_LOCATING:
                $allow_track = false;
                $options[] = '<a class="dropdown-item cancel-booking" data-id="' . $item['id'] . '">Cancel booking</a>';
                $action = $this->getActionButton($item, $options, $allow_track);
                break;

            case DELIEVEREE_BOOKING_STATUS_IN_PROCESS:
            case DELIEVEREE_BOOKING_STATUS_CONFIRMED:
                $delivereeLinkbooking = DELIEVEREE_BOOKING_URL_MODE[get_option('deliveree_api_method', '')] ?? '';
                $options[] = '<a target="_blank" class="dropdown-item" href="' . $delivereeLinkbooking . $item['id'] . '/tracking?expand=true">Chat with driver</a>';
                $options[] = '<a class="dropdown-item cancel-booking" data-id="' . $item['id'] . '">Cancel booking</a>';
                $action = $this->getActionButton($item, $options);
                break;

            default:
                $action = '';
                break;
        }

        return $action;
    }

    private function getActionButton($item = [], $options = [], $allow_track = true)
    {
        $track_button =  (isset($item['tracking_url']) && $item['tracking_url'] != '' && $allow_track) ? '<div><a target="_blank" href="' . $item['tracking_url'] . '" class="" data-id="' . $item['id'] . '"><button type="button" class="button track-btn">Track</button></a></div>' : '';
        $more_button = '<div class="btn-group button-dropdown"><button type="button" class="button">More</button><div class="dropdown-menu dropdown-menu-right">' . implode('', $options) . '</div></div>';
        return $track_button . $more_button;
    }



    /**
     * Generate the table rows
     *
     * @since 3.1.0
     */
    public function display_rows()
    {
        foreach ($this->items as $item) {
            if (count($item['locations']) > 1) {
                unset($item['locations'][0]);
            }
            foreach ($item['locations'] as $index => $shippingAddress) {
                $item['locations'] = $shippingAddress;
                $item['order_id'] = $item['order_ids'][$index - 1];
                $this->single_row($item);
            }
        }
    }

    /**
     * [OPTIONAL] Return array of bult actions if has any
     *
     * @return array
     */
    function get_bulk_actions()
    {
        $actions = array();
        return $actions;
    }

    function get_sortable_columns()
    {
        return array(
            'id' => array(
                'id'
            ),
            'pickup_time' => array(
                'pickup_time'
            ),
            'job_order_number' => array(
                'job_order_number'
            ),
            'customer_name' => array(
                'customer_name'
            ),
            'vehicle_type_id' => array(
                'vehicle_type_id'
            ),
            'shipping_methods' => array(
                'shipping_methods'
            ),
        );
    }




    public function print_column_headers($with_id = true)
    {
        list($columns, $hidden, $sortable, $primary) = $this->get_column_info();

        $current_url = set_url_scheme('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

        if (isset($_GET['orderby'])) {
            $current_orderby = sanitize_text_field(wp_unslash($_GET['orderby']));
        } else {
            $current_orderby = '';
        }

        if (isset($_GET['order']) && 'desc' === $_GET['order']) {
            $current_order = 'desc';
        } else {
            $current_order = 'asc';
        }

        if (!empty($columns['cb'])) {
            static $cb_counter = 1;
            $columns['cb']     = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . __('Select All') . '</label>'
                . '<input id="cb-select-all-' . $cb_counter . '" type="checkbox" />';
            $cb_counter++;
        }

        foreach ($columns as $column_key => $column_display_name) {
            $class = array('manage-column', "column-$column_key");

            if (in_array($column_key, $hidden)) {
                $class[] = 'hidden';
            }

            if ('cb' === $column_key) {
                $class[] = 'check-column';
            } elseif (in_array($column_key, array('posts', 'comments', 'links'))) {
                $class[] = 'num';
            }

            if ($column_key === $primary) {
                $class[] = 'column-primary';
            }

            if (isset($sortable[$column_key])) {
                list($orderby) = $sortable[$column_key];

                if ($current_orderby === $orderby) {
                    $order   = 'asc' === $current_order ? 'desc' : 'asc';
                    $class[] = 'sorted';
                    $class[] = $current_order;
                } else {
                    $order   = 'asc';
                    $class[] = 'sortable';
                    $class[] = 'desc';
                }

                $column_display_name = '<a href="' . esc_url(add_query_arg(compact('orderby', 'order'), $current_url)) . '"><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';
            }

            $tag   = ('cb' === $column_key) ? 'td' : 'th';
            $scope = ('th' === $tag) ? 'scope="col"' : '';
            $id    = $with_id ? "id='$column_key'" : '';

            if (!empty($class)) {
                $class = "class='" . join(' ', $class) . "'";
            }

            echo wp_kses_post("<$tag $scope $id $class>$column_display_name</$tag>");
        }
    }

    /**
     * [REQUIRED] This is the most important method
     *
     * It will get rows from database and prepare them to be showed in table
     */
    function prepare_items()
    {
        try {
            if (get_option('deliveree_user_type') == '') {
                $bookingApi = new BookingApi();
                $data =  $bookingApi->getUserProfile();

                if (isset($data['user_type'])) {
                    $this->user_type =  $data['user_type'];
                    update_option('deliveree_user_type', $data['user_type']);
                }
            } else {
                $this->user_type =  get_option('deliveree_user_type');
            }

            $per_page = 10;
            $columns = $this->get_columns();
            $hidden = array();
            $sortable = $this->get_sortable_columns();
            $this->_column_headers = array($columns, $hidden, $sortable);

            //paging ,search, filter
            $paged                  = isset($_GET['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
            $search_key             = (isset($_REQUEST['s'])) ? sanitize_text_field($_REQUEST['s']) : false;
            $search_date            = (isset($_REQUEST['d'])) ? sanitize_text_field(trim($_REQUEST['d'])) : false;

            if (!$search_date && $search_date != 'none') {
                $start = $end = new DateTime();
                $end = $end->modify('+1 day')->format('d-M-Y');
                $start = $start->modify('-90 days')->format('d-M-Y');
                $search_date = $start . ' - ' . $end;
            }

            $search_date = $search_date == 'none' ? '' : $search_date;
            $search_status          = (isset($_REQUEST['status'])) ? array_map('sanitize_text_field', $_REQUEST['status']) : false;
            $bookingApi = new BookingApi();
            $items      = $bookingApi->getListBookings($paged + 1);
            $vehicle    = $bookingApi->getVehicleTypes();

            if ($items) {
                $this->items = isset($items['data']) ? $items['data'] : [];


                if ($this->items) {
                    $order_ids = [];
                    foreach ($this->items as $index => $item) {
                        $item_order_ids = [];
                        $this->items[$index]['vehicle_type_id'] = isset($vehicle[$item['vehicle_type_id']]['name']) ? $vehicle[$item['vehicle_type_id']]['name'] : 'n/a';
                        if (isset($this->items[$index]['pickup_time'])) {
                            $this->items[$index]['pickup_time'] = get_date_from_gmt($item['pickup_time'], 'd-M-Y H:i');
                        }

                        if (isset($item['job_order_number'])) {
                            $job_order_numbers = explode(',', $item['job_order_number']);
                            foreach ($job_order_numbers as $key => $order_id) {
                                $order_id = str_replace('#', '', $order_id);
                                $order_id = (int)$order_id;
                                if ($order_id) {
                                    $this->items[$index]['order_id'] =  $order_id;
                                    array_push($order_ids, $order_id);
                                    array_push($item_order_ids, $order_id);
                                }
                            }

                            $this->items[$index]['order_ids'] =  $item_order_ids;
                        }
                    }

                    global $wpdb;
                    $table_name = $wpdb->prefix . DELIEVEREE_BOOKING_TABLE_NAME;
                    $str_order_ids = implode(",", $order_ids);

                    $bookingItems = $wpdb->get_results("SELECT adjustments_amount,order_id,google_data,shipping_address,shipping_method_id,adjustments FROM $table_name WHERE order_id IN($str_order_ids)", ARRAY_A);
                }

                @$bookingItems = array_column($bookingItems, null, 'order_id');

                foreach ($this->items as $key => $item) {
                    if (isset($bookingItems[$item['order_id']])) {
                        $this->items[$key]['google_data'] =  $bookingItems[$item['order_id']]['google_data'];
                        $this->items[$key]['shipping_address'] =  $bookingItems[$item['order_id']]['shipping_address'];
                        $this->items[$key]['shipping_method_id'] =  $bookingItems[$item['order_id']]['shipping_method_id'];
                        $this->items[$key]['adjustments'] =  $bookingItems[$item['order_id']]['adjustments'];
                        $this->items[$key]['adjustments_amount'] =  $bookingItems[$item['order_id']]['adjustments_amount'];
                    }
                }

                $this->listItems = $this->items;

                //search
                if ($search_key || $search_date || $search_status) {
                    $this->items = $this->filter_table_data($this->items, $search_key, $search_date, $search_status);
                }

                $order = isset($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'desc';
                $orderBy = isset($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'id';


                if ($order == 'asc' || $order == 'desc') {

                    switch ($orderBy) {
                        case 'id':
                            usort($this->items, function ($a, $b) {
                                return  $a['id'] - $b['id'];
                            });
                            break;
                        case 'pickup_time':
                            usort($this->items, function ($a, $b) {
                                $ad = ($a['pickup_time'] !== null) ? DateTime::createFromFormat('d-M-Y H:i', $a['pickup_time'])->getTimestamp() : 0;
                                $bd = ($b['pickup_time'] !== null) ? DateTime::createFromFormat('d-M-Y H:i', $b['pickup_time'])->getTimestamp() : 0;

                                if ($ad == $bd) {
                                    return 0;
                                }

                                return $ad < $bd ? -1 : 1;
                            });
                            break;
                        case 'job_order_number':
                            usort($this->items, function ($a, $b) {
                                $a_job_order_number = 0;
                                $b_job_order_number = 0;

                                if (isset($a['job_order_number'])) {
                                    $orderIds = explode(',', $a['job_order_number']);
                                    if (isset($orderIds[$this->counterItems[$a['id']]])) {
                                        $a_job_order_number =  str_replace('#', '', $orderIds[$this->counterItems[$a['id']]]);
                                    }
                                    $a_job_order_number =  str_replace('#', '', $orderIds[0]);
                                }

                                if (isset($b['job_order_number'])) {
                                    $orderIds = explode(',', $b['job_order_number']);
                                    if (isset($orderIds[$this->counterItems[$b['id']]])) {
                                        $b_job_order_number =  str_replace('#', '', $orderIds[$this->counterItems[$b['id']]]);
                                    }

                                    $b_job_order_number =  str_replace('#', '', $orderIds[0]);
                                }
                                return $b_job_order_number - $a_job_order_number;
                            });
                            break;
                        case 'customer_name':
                            usort($this->items, function ($a, $b) {
                                $a_customer_name = '';
                                $b_customer_name = '';

                                if (isset($a['locations']) && isset($a['locations'][1]['recipient_name'])) {
                                    $a_customer_name = $a['locations'][1]['recipient_name'];
                                }

                                if (isset($b['locations']) && isset($b['locations'][1]['recipient_name'])) {
                                    $b_customer_name = $b['locations'][1]['recipient_name'];
                                }

                                return strcmp($a_customer_name, $b_customer_name);
                            });
                            break;
                        case 'vehicle_type_id':
                            usort($this->items, function ($a, $b) {
                                $a_vehicle_type = '';
                                $b_vehicle_type = '';

                                if (isset($a['vehicle_type_id'])) {
                                    $a_vehicle_type = $a['vehicle_type_id'];
                                }

                                if (isset($b['vehicle_type_id'])) {
                                    $b_vehicle_type = $b['vehicle_type_id'];
                                }

                                return strcmp($a_vehicle_type, $b_vehicle_type);
                            });
                            break;
                        case 'shipping_methods':
                            usort($this->items, function ($a, $b) {
                                $a_shipping_methods = '';
                                $b_shipping_methods = '';

                                if (isset($a['shipping_methods'])) {
                                    $a_shipping_methods = $a['shipping_methods'];
                                }

                                if (isset($b['shipping_methods'])) {
                                    $b_shipping_methods = $b['shipping_methods'];
                                }

                                return strcmp($a_shipping_methods, $b_shipping_methods);
                            });
                            break;
                        default:
                            # code...
                            break;
                    }

                    if ($order == 'desc') {
                        $this->items = array_reverse($this->items);
                    }
                }

                //pagination
                if (isset($items['pagination'])) {
                    $total_items = count($this->items);
                    $per_page = $items['pagination']['per_page'];
                }
            }

            $this->set_pagination_args(array(
                'total_items' => $total_items,
                'per_page' => $per_page,
                'total_pages' => ceil($total_items / $per_page)
            ));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * filter the table data based on the search key
     * @param $table_data
     * @param $search_key
     * @param $search_date
     * @param $search_status
     * @return array
     */
    public function filter_table_data($table_data, $search_key, $search_date, $search_status)
    {

        $filtered_table_data = array_values(array_filter($table_data, function ($row) use ($search_date, $search_key, $search_status) {
            $allow_search_key = true;
            $allow_search_status = true;
            $allow_search_date = true;

            if ($search_key) {
                $tmp = $row;
                $locations = [];
                if (isset($tmp['locations'])) {
                    foreach ($tmp['locations'] as $item) {
                        $locations[] = implode(' ', $item);
                    }
                }
                $tmp['locations'] = implode($locations);

                $stringSearch = implode(' ', $tmp);
                $allow_search_key = (stripos(strtolower($stringSearch), strtolower(trim($search_key))) !== false);
            }

            if ($search_status && isset($row['status'])) {
                $new_status = $this->conver_status($row['status']);
                $allow_search_status = in_array($new_status['status'], $search_status);
            }

            if ($search_date) {
                $search_date_array = explode(' - ', $search_date);
                $from_date = $search_date_array[0] . ' 00:00';
                $to_date = $search_date_array[1] . ' 23:59';

                $pickup_time = (isset($row['pickup_time']) && !empty($row['pickup_time']) && $row['pickup_time'] !== null) ? DateTime::createFromFormat('d-M-Y H:i', $row['pickup_time'])->getTimestamp() : 0;
                $from_date = ($from_date !== null) ? DateTime::createFromFormat('d-M-Y H:i', $from_date)->getTimestamp() : 0;
                $to_date = ($to_date !== null) ? DateTime::createFromFormat('d-M-Y H:i', $to_date)->getTimestamp() : 0;

                $allow_search_date = ($from_date <= $pickup_time && $pickup_time <= $to_date);
            }

            return ($allow_search_key && $allow_search_status &&  $allow_search_date);
        }));
        return $filtered_table_data;
    }

    function get_date_from_date_string($format_date, $date_string)
    {
        switch ($format_date) {
            case 'd/m/Y':
            default:
                return $date_string;
        }
    }

    function get_date_from_utc_date_string($date_string)
    {
        $timestamp = strtotime($date_string . ' ' . get_option('timezone_string'));
        return date('d/m/Y H:i', $timestamp);
    }

    function  getCountByStatus($status = null)
    {
        $items = [];
        if ($this->listItems) {
            if (empty($status)) {
                return count($this->listItems);
            }

            foreach ($this->listItems as $item) {
                $new_status = $this->conver_status($item['status']);
                if (isset($new_status['status']) && $new_status['status'] == $status) {
                    $items[] = $item;
                }
            }
        }

        return count($items);
    }

    function  getCountShippingMethod($shipping_method_id = null)
    {
        $items = [];
        if ($this->listItems) {
            if (empty($shipping_method_id)) {
                return count($this->listItems);
            }

            foreach ($this->listItems as $item) {
                if (isset($item['shipping_method_id']) && $item['shipping_method_id'] == $shipping_method_id) {
                    $items[] = $item;
                }
            }
        }

        return count($items);
    }
}
