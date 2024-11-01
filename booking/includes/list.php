<?php
require_once(DELIEVEREE_PATH  . '/api/BookingApi.php');


function bookings_deliveree_page_handler()
{
    $table = new BookingDeliveree();
    $table->prepare_items();
    $status = (isset($_REQUEST['status'])) ? array_map( 'sanitize_text_field', $_REQUEST['status'] ) : null;

    $message = '';
    if (isset($_REQUEST['create-booking'])) {
        $message = '<div class="updated below-h2" id="message"><p>The booking has been created successfully</p></div>';
    }

    // $locale = get_locale();

    if (get_option('deliveree_country_code') == '') {
        $bookingApi = new BookingApi();
        $data =  $bookingApi->getUserProfile();
        update_option('deliveree_country_code', $data['country_code']);
    } else {
        $data['country_code'] = get_option('deliveree_country_code');
    }

    if ('deliveree.com' == DELIEVEREE_DOMAIN) {
        // $locale = ($locale == 'ph' || $locale == 'th') ? $locale : 'id';
        $strLocale = '?area_id=';
        if ('ph' == strtolower($data['country_code'])) $strLocale .= '5';
        elseif ('th' == strtolower($data['country_code'])) $strLocale .= '2';
        else $strLocale .= '3';

        $contact_cs_link   = 'https://webapp.' . DELIEVEREE_DOMAIN . '/' . $strLocale . '&open=chat';
    } else {
        $contact_cs_link   = 'https://webapp.' . DELIEVEREE_DOMAIN . '/?open=chat';
    }

?>
    <input type="hidden" id="urlForm" value="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=bookings_deliveree'); ?>">
    <div class="wrap" id="list-bookings">
        <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
        <div>
            <h2 style="display: inline-block;">
                <?php _e('Bookings', 'deliveree-same-day') ?>
            </h2>
            <a href="<?php echo esc_url($contact_cs_link); ?>" target="_blank" class="button alignright button-contactcs">Contact CS</a>
        </div>
        <?php echo wp_kses_post($message); ?>
        <form id="persons-table" action="<?php echo get_admin_url(get_current_blog_id(), 'admin.php'); ?>" method="GET">
            <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']) ?>" />
            <?php if (isset($_REQUEST['paged'])) : ?>
                <input type="hidden" name="paged" value="<?php echo esc_attr($_REQUEST['paged']) ?>" />
            <?php endif; ?>

            <select id='select_status' name="status[]" multiple data-total='<?php echo esc_attr($table->getCountByStatus()) ?>' data-selected='<?php echo esc_attr(json_encode($status)) ?>'>
                <option value='<?php echo DELIEVEREE_BOOKING_STATUS_LOCATING ?>'>Locating driver (<?php echo esc_html($table->getCountByStatus(DELIEVEREE_BOOKING_STATUS_LOCATING)); ?>)</option>
                <option value='<?php echo DELIEVEREE_BOOKING_STATUS_TIME_OUT ?>'>Time out (<?php echo esc_html($table->getCountByStatus(DELIEVEREE_BOOKING_STATUS_TIME_OUT)); ?>)</option>
                <option value='<?php echo DELIEVEREE_BOOKING_STATUS_CONFIRMED ?>'>Driver accept booking (<?php echo esc_html($table->getCountByStatus(DELIEVEREE_BOOKING_STATUS_CONFIRMED)); ?>)</option>
                <option value='<?php echo DELIEVEREE_BOOKING_STATUS_IN_PROCESS ?>'>Delivery in progress (<?php echo esc_html($table->getCountByStatus(DELIEVEREE_BOOKING_STATUS_IN_PROCESS)) ?>)</option>
                <option value='<?php echo DELIEVEREE_BOOKING_STATUS_DONE ?>'>Delivery completed(<?php echo esc_html($table->getCountByStatus(DELIEVEREE_BOOKING_STATUS_DONE)) ?>)</option>
                <option value='<?php echo DELIEVEREE_BOOKING_STATUS_CANCELED ?>'>Canceled (<?php echo esc_html($table->getCountByStatus(DELIEVEREE_BOOKING_STATUS_CANCELED))  ?>) </option>
            </select>

            <div class="wrapper-input-register-customers">
                <input class="datetimepicker" id="filter_datetimepicker" name="d" type="text" <?php echo (isset($_GET['d']) && esc_html($_GET['d']) != "none") ? 'value="' . esc_attr($_GET['d']) . '"' : ''; ?> placeholder="<?php esc_html_e('Filter date', 'deliveree-same-day'); ?>" autocomplete="off" />
                <span data-clear="d" class="clear-search-input clear <?php echo esc_html($_GET['d']) != "none" ? 'clear-show' : ''; ?>">x</span>
            </div>

            <div class="wrapper-input-register-customers">
                <input type="text" name="s" placeholder="<?php esc_html_e('Keyword...', 'deliveree-same-day'); ?>" id="register-customers" <?php echo isset($_GET['s']) ? 'value="' . esc_attr($_GET['s']) . '"' : ''; ?> autocomplete="off" />
                <span data-clear="s" class="clear-search-input clear <?php echo (isset($_GET['s']) && esc_html($_GET['s'] != '')) ? 'clear-show' : ''; ?>">x</span>
            </div>

            <input id="btn-bookings-search" type="submit" class="button" value="<?php esc_html_e('Search', 'deliveree-same-day'); ?>" />
        </form>






        <form id="persons-table" method="GET">
            <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']) ?>" />

            <?php if (isset($_REQUEST['paged'])) : ?>
                <input type="hidden" name="paged" value="<?php echo esc_attr($_REQUEST['paged']) ?>" />
            <?php endif; ?>

            <?php $table->display() ?>
        </form>
    </div>

<?php
}
