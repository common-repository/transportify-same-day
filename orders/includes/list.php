<?php

/**
 * admin_menu hook implementation, will add pages to list persons and to add new one
 */
require_once(DELIEVEREE_PATH  . '/api/BookingApi.php');

add_action("wp_ajax_createOrderMultiple", "createOrderMultiple");
add_action("wp_ajax_nopriv_createOrderMultiple", "createOrderMultiple");
function createOrderMultiple()
{
    $order_ids = isset($_REQUEST['order_ids']) ? array_map('sanitize_text_field', $_REQUEST['order_ids']) : array();

    $data = [
        'order_ids' => $order_ids,
        'time_type' => sanitize_text_field($_REQUEST['time_type']),
        'pickup_time' => sanitize_text_field($_REQUEST['pickup_time']),
        'vehicle_type_id' => sanitize_text_field($_REQUEST['vehicle_type_id']),
        'extra_services' => map_deep($_REQUEST['extra_services'], 'sanitize_text_field'),
        'vehicle_type' => array_map('sanitize_text_field', $_REQUEST['vehicle_type']),
        'quick_choice_id' => sanitize_text_field($_REQUEST['quick_choice_id']),
        'optimize_route' => sanitize_text_field($_REQUEST['optimize_route']),
        'quick_choice' => ($_REQUEST['quick_choice_id'] != '') ? true : false,
    ];

    $bookingApi = new BookingApi();
    $data =  $bookingApi->createBoookingByBoookingId($data);
    die(json_encode($data));
}

add_action("wp_ajax_getQuoteByBoookingOrders", "getQuoteByBoookingOrders");
add_action("wp_ajax_nopriv_getQuoteByBoookingOrders", "getQuoteByBoookingOrders");
function getQuoteByBoookingOrders()
{
    global $wpdb;
    $booking_ids_arr = isset($_REQUEST['booking_ids_arr']) ? array_map('sanitize_text_field', $_REQUEST['booking_ids_arr']) : array();

    $listItems = [];
    if (is_array($booking_ids_arr) && !empty($booking_ids_arr)) {
        $booking_ids_arr = implode(',', $booking_ids_arr);
        $table_name = $wpdb->prefix . DELIEVEREE_BOOKING_TABLE_NAME;
        $orderby = 'FIELD (id, ' . $booking_ids_arr . ' )';
        $sql = $wpdb->prepare("SELECT * FROM $table_name WHERE ( booking_confirm = 0 AND id IN($booking_ids_arr) ) ORDER BY $orderby asc");
        $items = $wpdb->get_results($sql, ARRAY_A);
        if ($items) {
            $bookingApi = new BookingApi();
            $listItems = $bookingApi->getQuoteByBoookingOrders($items);
        }
    }

    die(json_encode($listItems));
}

function cancelled_order($booking_ids_str)
{
    global $wpdb;
    $table_name = $wpdb->prefix . DELIEVEREE_BOOKING_TABLE_NAME;
    $items = $wpdb->get_results("SELECT * FROM $table_name WHERE id IN($booking_ids_str)", ARRAY_A);

    foreach ($items as $key => $item) {
        $order_id = $item['order_id'];
        $order    = new WC_Order($order_id);
        $order->update_status('cancelled');
    }
}

add_action("wp_ajax_deleteBoookingOrders", "deleteBoookingOrders");
add_action("wp_ajax_nopriv_deleteBoookingOrders", "deleteBoookingOrders");
function deleteBoookingOrders()
{
    global $wpdb;
    $table_name = $wpdb->prefix . DELIEVEREE_BOOKING_TABLE_NAME;
    $booking_ids_arr = isset($_REQUEST['booking_ids_arr']) ? array_map('sanitize_text_field', $_REQUEST['booking_ids_arr']) : array();

    if (is_array($booking_ids_arr) && !empty($booking_ids_arr)) {
        $booking_ids_str = implode(',', $booking_ids_arr);
        cancelled_order($booking_ids_str);
        $wpdb->query("DELETE FROM $table_name WHERE id IN($booking_ids_str)");
    }

    die(json_encode([]));
}

add_action("wp_ajax_trashBoookingOrders", "trashBoookingOrders");
add_action("wp_ajax_nopriv_trashBoookingOrders", "trashBoookingOrders");
function trashBoookingOrders()
{
    global $wpdb;
    $table_name = $wpdb->prefix . DELIEVEREE_BOOKING_TABLE_NAME;
    $ids = isset($_REQUEST['ids']) ? array_map('sanitize_text_field', $_REQUEST['ids']) : array();

    if (is_array($ids) && !empty($ids)) {
        $ids = implode(',', $ids);
        $wpdb->query("UPDATE $table_name SET booking_confirm = 2 WHERE id IN($ids)");
    }

    die(json_encode([]));
}

add_action("wp_ajax_restoreBoookingOrders", "restoreBoookingOrders");
add_action("wp_ajax_nopriv_restoreBoookingOrders", "restoreBoookingOrders");
function restoreBoookingOrders()
{
    global $wpdb;
    $table_name = $wpdb->prefix . DELIEVEREE_BOOKING_TABLE_NAME;
    $ids = isset($_REQUEST['ids']) ? array_map('sanitize_text_field', $_REQUEST['ids']) : array();

    if (is_array($ids) && !empty($ids)) {
        $ids = implode(',', $ids);
        $wpdb->query("UPDATE $table_name SET booking_confirm = 0 WHERE id IN($ids)");
    }

    die(json_encode([]));
}


function initScript()
{
    wp_enqueue_style('wp-jquery-ui-dialog');
    wp_enqueue_style('ui-booking-datetimepicker', DELIEVEREE_URL . 'assets/css/datetimepicker.min.css', array(), null, false);
    wp_enqueue_style('ui-booking-select2', DELIEVEREE_URL . 'assets/css/select2.min.css', array(), null, false);
    wp_enqueue_style('ui-booking-darktooltip', DELIEVEREE_URL . 'assets/css/darktooltip.css', array(), null, false);
    wp_enqueue_style('orders-list-deliveree-backend', DELIEVEREE_ORDER_URL . 'assets/css/orders-list.css', array(), '1.8.1', false);
    wp_enqueue_style('orders-list-deliveree-confirm', DELIEVEREE_ORDER_URL . 'assets/css/confirm-msc-style.css', array(), null, false);

    wp_enqueue_script('jquery-ui-dialog');
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('booking-moment', DELIEVEREE_URL . 'assets/js/moment.min.js');
    wp_enqueue_script('booking-select2', DELIEVEREE_URL . 'assets/js/select2.min.js');
    wp_enqueue_script('booking-darktooltip', DELIEVEREE_URL . 'assets/js/jquery.darktooltip.js');
    wp_enqueue_script('booking-loading', DELIEVEREE_BOOKING_URL . 'assets/js/loadingoverlay.min.js');
    wp_enqueue_script('booking-datetimepicker', DELIEVEREE_URL . 'assets/js/datetimepicker.full.min.js');
    wp_enqueue_script('deliveree-backend-js-core', DELIEVEREE_ORDER_URL . 'assets/js/orders-list.js?v=3.0.4');
    wp_enqueue_script('deliveree-backend-js-confirm', DELIEVEREE_ORDER_URL . 'assets/js/confirm-msc-script.js');
}


function orders_deliveree_page_handler()
{
    initScript();
?>
    <input type="hidden" id="urlForm" data-admin-url-ajax="<?php echo admin_url('admin-ajax.php'); ?>" value="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=orders_deliveree'); ?>">
    <div class="order-list-tab">
        <a href="#" class="show_tab_method active " data-tab="deliverree_actual_list"><?php echo DELIEVEREE_NAME ?></a>
    </div>
<?php
    $group_booking_ids = sanitize_text_field(isset($_REQUEST['group_booking_ids']) ? trim($_REQUEST['group_booking_ids']): '');
    $group_booking_ids = $group_booking_ids != '' ? explode('-', $group_booking_ids) : [];
    deliverree_group_booking($group_booking_ids);
    deliverree_actual_list($group_booking_ids);
}


function deliverree_group_booking($group_booking_ids)
{
    $table = new OrdersGroupBooking($group_booking_ids);
    $table->prepare_items();
    $deliveryType = '';
    if (isset($_REQUEST['order_delivery_type']) && $_REQUEST['order_delivery_type']) {
        $deliveryType = sanitize_text_field(trim($_REQUEST['order_delivery_type']));
    }
    $message = '';
    if ('delete' === $table->current_action() && isset($_REQUEST['id'])) {
        $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('The order has been deleted ', 'deliveree-same-day'), esc_html($_REQUEST['id'])) . '</p></div>';
    }
?>
    <div class="wrap tab_method deliverree_actual_list active">
        <?php echo wp_kses_post($message); ?>
        <div style="margin-bottom: 10px;    display: flex; align-items: center; justify-content: space-between;">
            <h2>
                <strong>
                    Group booking
                    <span class="woocommerce-help-tip woocommerce-help-tip-info" data-tooltip="Group orders under a single vehicle as one booking."></span>
                </strong>
            </h2>
            <div>
                <a href="#" class="update-sort-booking-group update-sort-booking-group-js">Update sort order</a>
                <a href="#" class="clear-booking-group clear-booking-group-js">Clear group</a>
            </div>
        </div>

        <form id="group-booking-table" method="GET">
            <input type="hidden" name="page" value="<?php echo isset($_REQUEST['page']) ? esc_attr($_REQUEST['page']) : '' ?>" />
            <input type="hidden" name="paged" value="<?php echo isset($_REQUEST['paged']) ? esc_attr($_REQUEST['paged']) : '' ?>" />
            <input type="hidden" name="order_delivery_type" value="<?php echo isset($_REQUEST['order_delivery_type']) ? esc_attr($_REQUEST['order_delivery_type']) : '' ?>" />
            <?php if ($deliveryType) { ?>
                <input type="hidden" name="order_delivery_type" value="<?php echo esc_attr($deliveryType) ?>" />
            <?php } ?>

            <?php $table->display() ?>
            <div id="footer-group-booking-table">
                <div>
                    <p><strong>Total Orders: </strong> <?php echo esc_attr(count($table->items)) ?></p>
                    <p><strong>Total Weight:</strong> <span id="footer_group_total_weight_js"></span> </p>
                </div>
                <div>
                    <p><strong>Optimized Route: <span class="woocommerce-help-tip" data-tooltip="User can also use the optimized route."></span></strong> <input value="1" type="checkbox" name="optimize_route" id="optimize_route"> </p>
                </div>
                <div style="text-align: right;">
                    <p><strong>Total Customer(s) Pays:</strong> <span id="footer_group_total_customer_pay_js"></span> </p>
                    <p><button style="padding: 0 40px;" type="button" data-ids="" class="button btn-green next-group-booking-js">Next</button></p>
                </div>
            </div>
        </form>

        <form id="list_booking_order_select_paging">
            <input type="hidden" name="page" value="<?php echo isset($_REQUEST['page']) ? esc_attr($_REQUEST['page']) : '' ?>" />
            <input type="hidden" name="paged" value="<?php echo isset($_REQUEST['paged']) ? esc_attr($_REQUEST['paged']) : '' ?>" />
            <input type="hidden" name="order_delivery_type" value="<?php echo isset($_REQUEST['order_delivery_type']) ? esc_attr($_REQUEST['order_delivery_type']) : '' ?>" />
            <input type="hidden" name="order_selected" id="order_selected" value="<?php echo isset($_REQUEST['order_selected']) ? esc_attr($_REQUEST['order_selected']) : '' ?>" />
        </form>

    </div>
<?php

}


function deliverree_actual_list($group_booking_ids)
{
    //orders
    $table = new OrdersDelivereeActual($group_booking_ids);
    $table->prepare_items();
    $deliveryType = '';
    if (isset($_REQUEST['order_delivery_type']) && $_REQUEST['order_delivery_type']) {
        $deliveryType = sanitize_text_field(trim($_REQUEST['order_delivery_type']));
    }
    $selected_delivery_type = (isset($_REQUEST['delivery_type'])) ? array_map('sanitize_text_field', $_REQUEST['delivery_type']) : null;
    $delivery_types = $table->fillter_delivery_type;
    $message = '';
    if ('delete' === $table->current_action() && isset($_REQUEST['id'])) {
        $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('The order has been deleted ', 'deliveree-same-day'), esc_html($_REQUEST['id'])) . '</p></div>';
    }

    $deliveree_services_extra_services = get_option('deliveree_services_extra_services', '');
    $extra_services = '';
    if ('yes' == $deliveree_services_extra_services) {
        $extra_services = '<span id="deliveree_services_extra_services_on"></span>';
    }

    $get_site_url = '<span id="get_site_url" data-site-url="' . site_url() . '"></span>';
    $country = get_option('deliveree_general_setting_origin_country', 'ID');

    switch ($country) {
        case 'ID':
            $insurance_policy_link = 'https://www.deliveree.com/id/en/goods-insurance/?_ga=2.28559804.1207491715.1634556088-1941449037.1625042385'; //Indonesia
            break;
        case 'TH':
            $insurance_policy_link = 'https://www.deliveree.com/th/en/goods-insurance/?_ga=2.202327573.56046606.1634530166-1585941349.1618137675'; //Thailand
            break;
        case 'PH':
            $insurance_policy_link = 'https://www.transportify.com.ph/goods-insurance-bp/?_ga=2.172741987.1715619538.1634623477-447450890.1624813245'; //Philippines
            break;
        default:
            $insurance_policy_link = '#';
            break;
    }

    $insurance_policy_link = '<span id="insurance_policy_link" data-insurance-policy-link="' . $insurance_policy_link . '"></span>';

?>
    <div class="wrap tab_method deliverree_actual_list active">
        <?php echo wp_kses_post($message); ?>
        <?php echo wp_kses_post($extra_services); ?>
        <?php echo wp_kses_post($get_site_url); ?>
        <?php echo wp_kses_post($insurance_policy_link); ?>
        <h2>
            <strong>
                <?php
                echo esc_html(
                    sprintf(
                        _n('Pending Order (%s)', 'Pending Orders (%s)', (array_sum($delivery_types) - $delivery_types['Trash'])),
                        number_format_i18n(array_sum($delivery_types) - $delivery_types['Trash'])
                    )
                ); ?>
            </strong>
        </h2>

        <div style="margin-bottom: 10px;    display: flex; align-items: center; justify-content: space-between;">
            <form id="order-deliveree-table" method="GET">
                <input type="hidden" name="page" value="<?php echo isset($_REQUEST['page']) ? esc_attr($_REQUEST['page']) : '' ?>" />
                <input type="hidden" name="paged" value="<?php echo isset($_REQUEST['paged']) ? esc_attr($_REQUEST['paged']) : '' ?>" />
                <div style="display: block;clear:both;">
                    <select id='select_delivery_type' name="delivery_type[]" multiple data-total='<?php echo esc_attr(array_sum($delivery_types)) ?>' data-selected='<?php echo esc_attr(json_encode($selected_delivery_type)) ?>'>
                        <?php
                        foreach ($delivery_types as $key => $count) {
                            echo "<option value='" . esc_attr($key) . "'>" . esc_html($key) . " (" . esc_html($count) . ")</option>";
                        }
                        ?>
                    </select>
                    <span>
                        <span class="icon-block"><i class="icon-calendar"></i></span> <input class="datetimepicker" id="filter_datetimepicker" name="filter_date" type="text" <?php echo isset($_GET['filter_date']) ? 'value="' . esc_attr($_GET['filter_date']) . '"' : ''; ?> placeholder="<?php esc_html_e('Filter date', 'deliveree-same-day'); ?>" autocomplete="off" />
                    </span>
                    <span class="wrapper_show_pickup_hours">
                        <span id="picktime_from_to" class="wrapper_pickup_hours  ">
                            <input id="pickup_hours_start" name="filter_date_from" placeholder="From" <?php echo isset($_GET['filter_date_from']) ? 'value="' . esc_attr($_GET['filter_date_from']) . '"' : ''; ?> type="input" class="pickup_hours " autocomplete="off" />
                            <span class="pickup_hours_dot">-</span>
                            <input id="pickup_hours_end" name="filter_date_to" placeholder="To" <?php echo isset($_GET['filter_date_to']) ? 'value="' . esc_attr($_GET['filter_date_to']) . '"' : ''; ?> type="input" class="pickup_hours " autocomplete="off" />
                        </span>
                        <span class="show_pickup_hours <?php echo (isset($_GET['filter_date']) || isset($_GET['filter_date_from']) || isset($_GET['filter_date_to'])) ? '' : 'disabled'; ?>"></span>
                    </span>
                    <div class="wrapper-input-search">
                        <input placeholder="Keyword… " value="<?php echo (isset($_GET['s'])) ? esc_attr($_GET['s']) : '' ?>" name="s" type="text" autocomplete="off" />
                        <span data-clear="s" class="clear-search-input clear <?php echo (isset($_GET['s']) && esc_html($_GET['s']) != "") ? 'clear-show' : ''; ?>">x</span>
                    </div>
                    <input style="margin-top: 5px;" type="submit" class="button action" value="Search">
                    <?php if ($deliveryType) { ?>
                        <input type="hidden" name="order_delivery_type" value="<?php echo esc_attr($deliveryType) ?>" />
                    <?php } ?>

                </div>
            </form>

            <div class="bulk-actions">
                <button id="bulk-actions-select" class="bulk-button ">Bulk Actions </button>
                <div class="bulk-option">
                    <p id="bulk-confirm-selected" class="bulk-confirm-selected-js">Add to group (<span>0</span>)</p>
                    <p id="bulk-deselect" class="bulk-deselect-js">Deselect all</p>
                </div>
            </div>
        </div>

        <form id="order-deliveree-table" method="GET">
            <input type="hidden" name="page" value="<?php echo isset($_REQUEST['page']) ? esc_attr($_REQUEST['page']) : '' ?>" />
            <input type="hidden" name="paged" value="<?php echo isset($_REQUEST['paged']) ? esc_attr($_REQUEST['paged']) : '' ?>" />
            <input type="hidden" name="order_delivery_type" value="<?php echo isset($_REQUEST['order_delivery_type']) ? esc_attr($_REQUEST['order_delivery_type']) : '' ?>" />
            <?php if ($deliveryType) { ?>
                <input type="hidden" name="order_delivery_type" value="<?php echo esc_attr($deliveryType) ?>" />
            <?php } ?>

            <?php $table->display() ?>
        </form>

        <form id="list_booking_order_select_paging">
            <input type="hidden" name="page" value="<?php echo isset($_REQUEST['page']) ? esc_attr($_REQUEST['page']) : '' ?>" />
            <input type="hidden" name="paged" value="<?php echo isset($_REQUEST['paged']) ? esc_attr($_REQUEST['paged']) : '' ?>" />
            <input type="hidden" name="order_delivery_type" value="<?php echo isset($_REQUEST['order_delivery_type']) ? esc_attr($_REQUEST['order_delivery_type']) : '' ?>" />
            <input type="hidden" name="order_selected" id="order_selected" value="<?php echo isset($_REQUEST['order_selected']) ? esc_attr($_REQUEST['order_selected']) : '' ?>" />
        </form>

    </div>
<?php

}
