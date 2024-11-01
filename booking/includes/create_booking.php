<?php
require_once(DELIEVEREE_PATH . '/api/BookingApi.php');

function bookings_deliveree_form_page_handler()
{
    // here we adding our custom meta box
    add_meta_box('booking_deliveree_form_meta_box', 'Create booking', 'booking_form_meta_box_handler', 'bookings_deliveree', 'normal', 'default');

    $go_to_webApp = 'https://webapp.' . DELIEVEREE_DOMAIN;


?>
    <form name="deliveree-create-booking" class="warping wrap">
        <h1 class="wp-heading-inline">
            <?php esc_html_e('Create Booking', 'deliveree-same-day'); ?>
        </h1>
        <a TARGET="_blank" href="<?php echo esc_url($go_to_webApp) ?>" class="button" style="margin-top: 10px"><?php esc_html_e('Go to WebApp', 'deliveree-same-day'); ?></a>

        <div class="wp-clearfix mt-1">
            <div class="alignleft">
                <label for="vehicle_type_id" class="screen-reader-text"><?php esc_html_e('Pickup area', 'deliveree-same-day'); ?></label>
                <select name="vehicle_type_id" id="vehicle_type_id">
                    <option value="1">Small Box</option>
                    <option value="2">Pickup area 2</option>
                </select>
            </div>

            <div class="alignleft">
                <label for="time_type" class="screen-reader-text"><?php esc_html_e('Choose Time', 'deliveree-same-day'); ?></label>
                <select name="time_type" id="time_type">
                    <option value="schedule"><?php esc_html_e('Schedule', 'deliveree-same-day'); ?></option>
                    <option value="now"><?php esc_html_e('Immediate', 'deliveree-same-day'); ?></option>
                </select>
            </div>

            <div class="alignleft">
                <label for="pickup_time" class="screen-reader-text"><?php esc_html_e('Choose Time', 'deliveree-same-day'); ?></label>
                <input class="datetimepicker" name="pickup_time" id="pickup_time_create" type="text" size="30" value="<?php echo get_date_from_gmt('now', 'd/m/Y H:i') ?>">
            </div>
        </div>

        <div class="table-responsive">
            <table class="widefat top create mt-1">
                <thead>
                    <tr>
                        <th width="600"><?php esc_html_e('Pickup', 'deliveree-same-day'); ?></th>
                        <th><?php esc_html_e('Location Notes', 'deliveree-same-day'); ?></th>
                        <th><?php esc_html_e('PIC Name', 'deliveree-same-day'); ?></th>
                        <th><?php esc_html_e('PIC Number', 'deliveree-same-day'); ?></th>
                        <th width="40"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="errors-group-0">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <div class="input-group-text primary">FR</div>
                                </div>
                                <input name="locations__address" readonly type="text" required data-parsley-errors-container=".errors-group-0" value="">
                                <input name="locations__latitude" class="hidden" type="text">
                                <input name="locations__longitude" class="hidden" type="text">
                            </div>
                        </td>
                        <td>
                            <input name="locations__note" type="text">
                        </td>
                        <td>
                            <input name="locations__recipient_name" type="text" required>
                        </td>
                        <td colspan="2">
                            <input name="locations__recipient_phone" type="tel" data-inputmask="'mask': '+(99) 9999-9999{1,3}'" required>
                        </td>
                    </tr>
                </tbody>
                <thead>
                    <tr>
                        <th><?php esc_html_e('Destination', 'deliveree-same-day'); ?></th>
                        <th><?php esc_html_e('Location Notes', 'deliveree-same-day'); ?></th>
                        <th><?php esc_html_e('PIC Name', 'deliveree-same-day'); ?></th>
                        <th><?php esc_html_e('PIC Number', 'deliveree-same-day'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="item-deliveree">
                    <tr class="item-deliveree-1">
                        <td class="errors-group-1">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <div class="input-group-text">1</div>
                                </div>
                                <input name="locations__address" readonly type="text" required data-parsley-errors-container=".errors-group-1">
                                <input name="locations__latitude" class="hidden" type="text">
                                <input name="locations__longitude" class="hidden" type="text">
                            </div>
                        </td>
                        <td>
                            <input name="locations__note" type="text">
                        </td>
                        <td>
                            <input name="locations__recipient_name" type="text" required>
                        </td>
                        <td>
                            <input name="locations__recipient_phone" type="tel" data-inputmask="'mask': '+(99) 9999-9999{1,3}'" required>
                        </td>
                        <td></td>
                    </tr>
                    <tr class="tr-deliveree-add-more">
                        <td class="alignright">
                            <button class="page-title-action deliveree-add-more" type="button">
                                <?php esc_html_e('Add more +', 'deliveree-same-day'); ?>
                            </button>
                        </td>
                        <td colspan="4"></td>
                    </tr>
                </tbody>
                <thead>
                    <tr>
                        <th><?php esc_html_e('Note to Driver', 'deliveree-same-day'); ?></th>
                        <th><?php esc_html_e('Order / Job Number', 'deliveree-same-day'); ?></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <input name="note" type="text" id="note" placeholder="<?php esc_html_e('', 'deliveree-same-day'); ?>">
                        </td>
                        <td>
                            <input name="job_order_number" id="job_order_number" type="text" placeholder="example: #1785" required>
                        </td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="alignright mt-1">
            <button type="submit" class="button button-primary">
                <?php esc_html_e('Confirm Booking', 'deliveree-same-day'); ?>
            </button>
        </div>

    </form>
<?php
}
