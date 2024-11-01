<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

define('DELIEVEREE_BOOKING_FILE', __FILE__);
define('DELIEVEREE_BOOKING_PATH', plugin_dir_path(DELIEVEREE_BOOKING_FILE));
define('DELIEVEREE_BOOKING_URL', plugin_dir_url(DELIEVEREE_BOOKING_FILE));

//status
define('DELIEVEREE_BOOKING_STATUS_CONFIRMED', 'confirmed');
define('DELIEVEREE_BOOKING_STATUS_DONE', 'delivery_completed');
define('DELIEVEREE_BOOKING_STATUS_IN_PROCESS', 'in_process');
define('DELIEVEREE_BOOKING_STATUS_CANCELED', 'canceled');
define('DELIEVEREE_BOOKING_STATUS_LOCATING', 'locating');
define('DELIEVEREE_BOOKING_STATUS_TIME_OUT', 'timeout');
define('DELIEVEREE_BOOKING_STATUS_EN_ROUTE', 'en_route');


/**
 * PART 2. Core page
 * ============================================================================
 */
require_once('includes/BookingDeliveree.php');

/**
 * PART 3. Admin page
 * ============================================================================
 */
require_once('includes/list.php');

/**
 * PART 4. Form for adding or editing row
 * ============================================================================
 */
require_once('includes/create_booking.php');
