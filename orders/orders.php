<?php

namespace Deliveree\Orders;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

define('DELIEVEREE_ORDER_FILE', __FILE__);
define('DELIEVEREE_ORDER_PATH', plugin_dir_path(DELIEVEREE_ORDER_FILE));
define('DELIEVEREE_ORDER_URL', plugin_dir_url(DELIEVEREE_ORDER_FILE));
define('DELIEVEREE_BOOKING_TABLE_NAME', 'boooking_orders_deliveree');

/**
 * PART 1. install db if exit
 * ============================================================================
 */
require_once('includes/install_db.php');

/**
 * PART 2. Core page
 * ============================================================================
 */
require_once('includes/OrdersDelivereeActual.php');
require_once('includes/OrdersGroupBooking.php');

/**
 * PART 3. Admin page
 * ============================================================================
 */
require_once('includes/list.php');
