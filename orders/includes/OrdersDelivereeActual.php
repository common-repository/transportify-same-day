<?php

/**
 * OrdersDelivereeActual class that will display our custom table
 * records in nice table
 */

class OrdersDelivereeActual extends WP_List_Table
{
    protected $configuration;
    public $booking_confirm = 0;
    public $search = true;
    public $fillter_delivery_type;
    public $user_type;
    protected $except_ids;
    public $currency = '';


    /**
     * OrdersDelivereeActual constructor.
     */
    function __construct($except_ids = [])
    {
        parent::__construct(array(
            'singular' => 'orders_deliveree',
            'plural' => 'orders_deliveree',
        ));

        $this->except_ids = $except_ids;
    }

    /**
     * @return array
     */
    function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'order_id' => __('Order', 'deliveree-same-day'),
            'vehicle_type_name' => __('Vehicle Type', 'deliveree-same-day'),
            // 'delivery_type' => __('Delivery Type', 'deliveree-same-day'),
            'customer' => __('Customer Name', 'deliveree-same-day'),
            'shipping_address' => __('Address', 'deliveree-same-day'),
            'city' => __('City', 'deliveree-same-day'),
            // 'postal_code' => __('Postal Code', 'deliveree-same-day'),
            'purchase_date' => __('Purchase Date', 'deliveree-same-day'),
            'item' => __('Items', 'deliveree-same-day'),
            // 'dimensions' => __('Dimensions (cm)', 'deliveree-same-day'),
            // 'volume' => __('Volume (cbm)', 'deliveree-same-day'),
            'weight' => __('Weight (kg)', 'deliveree-same-day'),
            'adjustments' => __('Adjustments', 'deliveree-same-day'),
            'paid_shipping' => __('Customer Pays', 'deliveree-same-day'),
            'action' => __('Actions', 'deliveree-same-day'),
        );
        return $columns;
    }

    /**
     * [REQUIRED] this is how checkbox column renders
     *
     * @param $item - row (key, value array)
     * @return HTML
     */
    function column_cb($item)
    {
        if (isset($item['row_selected'])) {
            return sprintf('<input class="checkbox-confirm-booking" checked="checked" type="checkbox" name="id[]" value="%s" />', $item['id']);
        }

        if (isset($item['booking_confirm']) && $item['booking_confirm'] == 2) {
            return sprintf('<input class="undo-delete" type="checkbox" name="id[]" value="%s" />', $item['id']);
        }

        return sprintf('<input class="checkbox-confirm-booking" type="checkbox" name="id[]" value="%s" />', $item['id']);
    }

    public function column_vehicle_type_name($item)
    {
        return $item['vehicle_type_name'];
    }
    public function column_city($item)
    {
        return $item['city'];
    }
    public function column_postal_code($item)
    {
        return $item['postal_code'];
    }

    public function column_purchase_date($item)
    {

        if (isset($item['purchase_date']) && $item['purchase_date'] != '') {

            $purchase_date = str_replace('01/01/1970 00:00', '', $item['purchase_date']);

            if (is_string($purchase_date) && $purchase_date != '') {
                $purchase_date =  date('d-m-Y H:i', strtotime(($item['purchase_date'])));
            }
            return $purchase_date;
        }
    }

    public function column_paid_shipping($item)
    {
        if (isset($item['paid_shipping']) && $item['paid_shipping'] != '') {

            return '<input class="hidden-price" type="hidden" data-currency_symbol="' . $this->currency . '" data-price="' . $item['paid_shipping'] . '" >' . $this->currency . ' ' . $this->formatPrice($item['paid_shipping']);
        }
    }


    public function column_shipping_address($item)
    {
        $locations = json_decode($item['locations'], true);
        $shipping_address = [];
        if (is_array($locations) && isset($locations['destinations'])) {
            foreach ($locations['destinations'] as $key => $location) {
                if ($location['address'] == $item['shipping_address']) {
                    $shipping_address = $location;
                }
            }
        }


        $google_map_link = '';
        $shipping_address_string = '';

        if (count($shipping_address) && $shipping_address['latitude'] && $shipping_address['longitude']) {
            $google_map_link = '<a target="_blank" href="https://maps.google.com/?q=' . $shipping_address['latitude'] . ',' . $shipping_address['longitude'] . '" >' .  $shipping_address['address'] . '<a>';
            $shipping_address_string = $shipping_address['address'];
        } else if (isset($item['shipping_address']) && isset($item['shipping_address'])) {
            $google_map_link =  '<a target="_blank" href="https://maps.google.com/?q=' . $item['shipping_address'] . '" >' . $item['shipping_address'] . '<a>';
            $shipping_address_string = $item['shipping_address'];
        }

        $copy_icon = '<span data-shipping-address="' . $shipping_address_string . '" data-tooltip="Click to copy" class="woocommerce-help-tip warraper-copy-icon  warraper-copy-icon-js"><img class="copy-icon" src="' . DELIEVEREE_URL . 'assets/images/copy.svg' . '" width="10px" /></span>';

        return $google_map_link . $copy_icon;
    }

    public function formatPrice($price)
    {
        return  number_format($price, wc_get_price_decimals(), '.', ',');
    }

    public function getMaxDimensionFromMixString($string)
    {
        $prepare_str = str_replace(array('cm', 'kg'), '', $string);
        $items = explode(',', $prepare_str);
        if (count($items) === 1) {
            return $prepare_str;
        }
        $max_cbm = 0;
        $return_str = '';
        foreach ($items as $item) {
            try {
                $cbm_array = explode('×', html_entity_decode($item));
                $cbm = intval(trim($cbm_array[0])) * intval(trim($cbm_array[1])) * intval(trim($cbm_array[2]));
                if ($cbm > $max_cbm) {
                    $max_cbm = $cbm;
                    $return_str = $item;
                }
            } catch (Exception $e) {
            }
        }
        return $return_str;
    }

    /**
     * @param object $item
     * @param string $column_name
     */
    function column_default($item, $column_name)
    {
        if (isset($item[$column_name])) {
            return $item[$column_name];
        }
    }

    /**
     * @param $item
     * @return string|string[]
     */
    function column_dimensions($item)
    {
        if (isset($item['dimensions'])) {
            return str_replace(array('cm', 'kg'), '', $item['dimensions']);
        }
    }

    /**
     * @param $item
     * @return string|string[]
     */
    function column_item($item)
    {
        if (isset($item['item'])) {
            return str_replace(',', ', ', $item['item']);
        }
        return '';
    }

    public function column_adjustments($item)
    {
        $adjustments = json_decode($item['adjustments'], true);
        $adjustments_amount = $item['adjustments_amount'] ?: 0;

        if (is_array($adjustments) && !empty($adjustments)) {
            $order = wc_get_order($item['order_id']);
            if ($order) {
                $currency_code = $order->get_currency();
                $this->currency = get_woocommerce_currency_symbol($currency_code);
            } else {
                $this->currency = $this->getCurrencyByAdjustments($item);
            }
        } else $this->currency = get_woocommerce_currency_symbol();
        $adjustments_amount = $this->currency . ' ' . $this->formatPrice($adjustments_amount);

        if ('bp_account' != $this->user_type && $item['adjustments_amount'] == 0) {
            $adjustments_amount = $this->currency . ' ' . 0;
        }

        return $adjustments_amount;
    }

    public function getCurrencyByAdjustments($item)
    {
        $adjustments = json_decode($item['adjustments'], true);
        $currency = '';

        if (is_array($adjustments) && !empty($adjustments)) {
            $currency = isset($adjustments['data']) ? $adjustments['data']['currency'] :  $adjustments['currency'];
            $currency = ($currency != '%') ? $currency : $adjustments['max_cap_currency'];
            switch ($currency) {
                case 'IDR':
                case 'Rp':
                    $currency = 'Rp';
                    break;
                case 'PHP':
                case '₱':
                case 'u20b1':
                    $currency = '₱';
                    break;
                case 'THB':
                case '฿':
                    $currency = '฿';
                    break;
                default:
                    $currency = get_woocommerce_currency_symbol();
                    break;
            }
        }
        return $currency;
    }


    function column_delete_item($item)
    {
        if (!isset($item['row_selected'])) {
            if (isset($item['booking_confirm']) && $item['booking_confirm'] == 2) {
                return '<a  data-id="' . $item['id'] . '" href="javascript:;" class="delete-order-item" ><span class="dashicons dashicons-trash"></span></a>';
            }
            return '<a  data-id="' . $item['id'] . '" href="javascript:;" class="move-to-trash" ><span class="dashicons dashicons-trash"></span></a>';
        }
    }

    function column_action($item)
    {
        $options = [];
        $booking_confirm = $item['booking_confirm'];

        switch ($booking_confirm) {
            case 2:
                $options[] = '<a data-id="' . $item['id'] . '"   class="restore-boooking-orders dropdown-item ">Restore order</a>';
                $options[] = '<a data-id="' . $item['id'] . '" data-order-id="' . $item['order_id'] . '"   class="delete-order-item dropdown-item ">Delete permanently</a>';
                $more_button = '<div class="btn-group button-dropdown"><button type="button" class="button">More</button><div class="dropdown-menu dropdown-menu-right">' . implode('', $options) . '</div></div>';
                $action =  $more_button;
                break;
            default:
                $confirm_button = "<div><button data-data='" . json_encode($item) . "' type='button' class='get_order_data_" . $item['id'] . " button btn-green track-btn add-to-group-booking-js'>Add to Group</button></div>";
                $options[] = '<a data-id="' . $item['id'] . '"   class="move-to-trash dropdown-item ">Move to trash</a>';
                $more_button = '<div class="btn-group button-dropdown"><button type="button" class="button">More</button><div class="dropdown-menu dropdown-menu-right">' . implode('', $options) . '</div></div>';
                $action = $confirm_button . $more_button;
                break;
        }

        return $action;
    }

    public function search_box($text, $input_id)
    {
        $input_id = $input_id . '-search-input';

        if (!empty($_REQUEST['orderby'])) {
            echo '<input type="hidden" name="orderby" value="' . esc_attr($_REQUEST['orderby']) . '" />';
        }
        if (!empty($_REQUEST['order'])) {
            echo '<input type="hidden" name="order" value="' . esc_attr($_REQUEST['order']) . '" />';
        }
        if (!empty($_REQUEST['post_mime_type'])) {
            echo '<input type="hidden" name="post_mime_type" value="' . esc_attr($_REQUEST['post_mime_type']) . '" />';
        }
        if (!empty($_REQUEST['detached'])) {
            echo '<input type="hidden" name="detached" value="' . esc_attr($_REQUEST['detached']) . '" />';
        }
?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo esc_attr($input_id); ?>"><?php echo esc_html($text); ?>:</label>
            <input type="search" id="<?php echo esc_attr($input_id); ?>" name="s" value="<?php _admin_search_query(); ?>" />
            <?php submit_button($text, '', '', false, array('id' => 'search-submit')); ?>
        </p>
<?php
    }

    function get_bulk_actions()
    {
        $actions = array();
        return $actions;
    }


    public function has_items()
    {
        return !empty($this->items);
    }

    public function display_rows_or_placeholder()
    {

        if ($this->has_items()) {
            $this->display_rows();
        } else {
            echo wp_kses_post('<tr class="no-items"><td class="colspanchange" colspan="' . $this->get_column_count() . '">');
            $this->no_items();
            echo wp_kses_post('</td></tr>');
        }
    }

    protected function get_sortable_columns()
    {
        return  array(
            'order_id' => array('order_id', 'asc'),
            'postal_code' => array('postal_code', true),
            'purchase_date' =>  array('purchase_date', 'asc'),
            'volume' => array('volume', true),
            'weight' => array('weight', true),
            'adjustments' => array('adjustments', true),
            'paid_shipping' => array('paid_shipping', true),
            'customer' => array('customer', true),
            'vehicle_type' => array('vehicle_type', true),
            'city' => array('city', true),
            'item' => array('item', true),
        );
    }

    /**
     * [REQUIRED] This is the most important method
     *
     * It will get rows from database and prepare them to be showed in table
     * @throws Exception
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


            global $wpdb;
            $table_name = $wpdb->prefix . DELIEVEREE_BOOKING_TABLE_NAME;
            $per_page = 100;

            $columns = $this->get_columns();
            $hidden = array();
            $sortable = $this->get_sortable_columns();

            // here we configure table headers, defined in our methods
            $this->_column_headers = array($columns, $hidden, $sortable);

            $current_page = isset($_REQUEST['paged']) && is_numeric($_REQUEST['paged']) ? intval($_REQUEST['paged'])  : 0;

            // prepare query params, as usual current page, order by and order direction
            $paged = max(1, $current_page);


            $where = [];
            $where[] = "shipping_method_id =  %s";
            $param[] = 'deliveree_actual_shipping_method';
            $where[] = "booking_confirm <> %d ";
            $param[] = 1;


            if (!empty($this->except_ids)) {
                $not_in_ids = implode(' , ', $this->except_ids);
                $where[] = 'id NOT IN ( ' . $not_in_ids . ' )';
            }

            $delivery_type = (isset($_REQUEST['delivery_type'])) ? array_map('sanitize_text_field', $_REQUEST['delivery_type']) : [];
            $fillter_sql_delivery_type = [];
            $get_trash = 0;
            foreach ($delivery_type as $key => $value) {
                switch ($value) {
                    case 'Other':
                        $fillter_sql_delivery_type[] = "delivery_type IS NULL";
                        $fillter_sql_delivery_type[] = "delivery_type = %s";
                        $param[] = '';
                        break;
                    case 'Trash':
                        $fillter_sql_delivery_type[] = "booking_confirm = 2 ";
                        $get_trash = 1;
                        break;
                    default:
                        $fillter_sql_delivery_type[] = "delivery_type = %s ";
                        $param[] = $value;
                        break;
                }
            }

            if (!empty($fillter_sql_delivery_type)) {
                $fillter_sql_delivery_type = implode(' OR ', $fillter_sql_delivery_type);
                $where[] = ' ( ' . $fillter_sql_delivery_type . ' )';
            }

            if (!$get_trash) {
                $where[] = "booking_confirm = %d";
                $param[] = $this->booking_confirm;
            }

            if ($this->search) {
                $search = (isset($_REQUEST['s'])) ? sanitize_text_field(trim($_REQUEST['s'])) : '';
                if ($search) {
                    $where[] = "full_text_search LIKE %s";
                    $param[] =  '%' . $wpdb->esc_like($search) . '%';
                }
            }

            if (isset($_REQUEST['filter_date']) && $_REQUEST['filter_date'] != '') {
                $filter_date = DateTime::createFromFormat('d-M-Y', sanitize_text_field($_REQUEST['filter_date']))->format('Y-m-d');
                $filter_date_from = sanitize_text_field($_REQUEST['filter_date_from']) ? $filter_date . ' ' . sanitize_text_field($_REQUEST['filter_date_from']) : $filter_date . ' 00:00:00';
                $filter_date_to = sanitize_text_field($_REQUEST['filter_date_to']) ? $filter_date . ' ' . sanitize_text_field($_REQUEST['filter_date_to']) : $filter_date . ' 23:59:59';
                $where[] = "purchase_date BETWEEN %s AND %s";
                $param[] = $filter_date_from;
                $param[] = $filter_date_to;
            }

            $sql = '';

            if ($where) {
                $offset = ($paged - 1) * $per_page;
                $where = implode(' AND ', $where);

                $current_order = 'desc';
                $orderby = 'id';

                if (isset($_GET['orderby']) &&  isset($_GET['order'])) {
                    if ('asc' === $_GET['order']) {
                        $current_order = 'asc';
                    }
                    $orderby = sanitize_text_field(wp_unslash($_GET['orderby']));
                    $columns = ['city', 'postal_code', 'order_id', 'volume', 'weight', 'paid_shipping', 'customer', 'delivery_type', 'items', 'vehicle_type', 'adjustments'];
                    if (in_array($orderby, $columns)) {
                        switch ($orderby) {
                            case 'paid_shipping':
                                $orderby = 'cast(paid_shipping as unsigned)';
                                break;
                            case 'weight':
                                $orderby = 'cast(weight as unsigned)';
                                break;
                            case 'adjustments':
                                $orderby = 'cast(adjustments_amount as signed)';
                                break;
                            default:
                                # code...
                                break;
                        }
                    }
                }

                $current_orderby = 'ORDER BY ' . $orderby;

                $param[] = $per_page;
                $param[] = $offset;

                // https://stackoverflow.com/questions/53831586/using-like-statement-with-wpdb-prepare-showing-hashes-where-wildcard-character
                // $wpdb->remove_placeholder_escape
                $sql = $wpdb->prepare("SELECT *
                FROM $table_name
                WHERE ($where) " . $current_orderby . " " . $current_order . " LIMIT %d
                OFFSET %d", $param);

                $count_sql = $wpdb->prepare("SELECT COUNT(id) as count_items
                FROM $table_name
                WHERE ($where)", $param);
            }

            $this->items = $wpdb->get_results($sql, ARRAY_A);
            $this->fillter_delivery_type = $this->getFillterDeliveryType();

            $total_items = 0;
            if (!empty($this->items)) {
                $count_items = $wpdb->get_results($count_sql, ARRAY_A);
                $total_items = $count_items[0]['count_items'];
            }

            // [REQUIRED] configure pagination
            if ($total_items > 0) {
                $this->set_pagination_args(array(
                    'total_items' => $total_items, // total items defined above
                    'per_page' => $per_page, // per page constant defined at top of method
                    'total_pages' => ceil($total_items / $per_page) // calculate pages count
                ));
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


    function  getCount()
    {
        $items = is_array($this->items) ? $this->items : [];
        return count($items);
    }

    function  getFillterDeliveryType()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . DELIEVEREE_BOOKING_TABLE_NAME;
        $sql = "SELECT COUNT(id) as Trash  FROM $table_name WHERE (booking_confirm = 2 ) AND shipping_method_id = 'deliveree_actual_shipping_method' ";
        $trash = $wpdb->get_results($sql, ARRAY_A);

        //---

        $sql = "SELECT delivery_type  FROM $table_name WHERE (booking_confirm = 0 ) AND shipping_method_id = 'deliveree_actual_shipping_method'";
        $delivery_type = $wpdb->get_results($sql, ARRAY_A);
        $delivery_type = array_column($delivery_type, 'delivery_type');
        $new_delivery_type = array_replace($delivery_type, array_fill_keys(array_keys($delivery_type, null), 'Other')); //convert null to 'Other'
        $delivery_type = array_count_values($new_delivery_type);
        $fillter_delivery_type = array_merge($delivery_type, $trash[0]);

        return $fillter_delivery_type;
    }


    function print_column_headers($with_id = true)
    {
        if (!$with_id) return null;
        return parent::print_column_headers($with_id);
    }
}
