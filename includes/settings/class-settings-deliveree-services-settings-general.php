<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('Settings_Deliveree_Services_Settings_General')) :

    class Settings_Deliveree_Services_Settings_General extends Settings_Deliveree_Services_Settings_Section
    {
        /**
         * Constructor.
         */
        function __construct()
        {
            $this->id = '';
            $this->desc = __('General', 'deliveree-same-day');
            parent::__construct();
            add_action('woocommerce_admin_field_input_toggle',              array($this, 'generate_input_toggle_html'));
            add_action('woocommerce_admin_field_radio_pickup_hours',              array($this, 'generate_radio_pickup_hours_html'));
            add_action('woocommerce_admin_field_radio_booking_mode',              array($this, 'generate_radio_booking_mode_html'));
            add_filter('woocommerce_admin_settings_sanitize_option',              array($this, 'woocommerce_admin_settings_sanitize_option'), 10, 3);
        }

        /**
         * @return array
         */
        function get_settings()
        {
            $admin_settings = array(
                array(
                    'title' => __(DELIEVEREE_NAME . ' Services ', 'deliveree-same-day'),
                    'type' => 'title',
                    'desc' => ''
                ),
                array(
                    'title' => __('Booking mode', 'deliveree-same-day'),
                    'id' => 'deliveree_services_booking_mode',
                    'type' => 'radio_booking_mode',
                    'class' => 'input_radio_booking_mode',
                    'desc_tip' => __('Auto-assign will auto-create a booking that best fits the customer\'s order. You may configure this with a time buffer and extra services.', 'deliveree-same-day'),
                    'options' => [
                        'manual_booking_mode' => 'Manual assign',
                        'auto_assign_booking_mode' => 'Auto assign'
                    ],
                    'default' => 'manual_booking_mode',
                ),
                array(
                    'title' => __('Pickup hours', 'deliveree-same-day'),
                    'id' => 'deliveree_services_pickup_hours',
                    'type' => 'radio_pickup_hours',
                    'options' => [
                        'anytime' => 'Anytime',
                        'custom_time' => 'Custom time'
                    ],
                ),
                array(
                    'title' => __('Extra Services', 'deliveree-same-day'),
                    'type' => 'input_toggle',
                    'id' => 'deliveree_services_extra_services',
                ),
                array(
                    'title' => __('Best Fit calculation', 'deliveree-same-day'),
                    'type' => 'input_toggle',
                    'id' => 'deliveree_services_best_fit_calculation',
                ),
                /* PHASE 2
                array(
                    'title' => __('POD', 'deliveree-same-day'),
                    'type' => 'input_toggle',
                    'id' => 'deliveree_services_pod',
                ),
                array(
                    'title' => __(DELIEVEREE_NAME.' tracking', 'deliveree-same-day'),
                    'type' => 'input_toggle',
                    'id' => 'deliveree_services_deliveree_tracking',
                    'description' => __('Add a live tracking link to your order confirmation', 'deliveree-same-day'),

                ),
                */
                array(
                    'type' => 'sectionend',
                ),
            );
            return array_merge($admin_settings);
        }

        /**
         * Helper function to get the formatted description and tip HTML for a
         * given form field. Plugins can call this when implementing their own custom
         * settings types.
         *
         * @param  array $value The form field value array.
         * @return array The description and tip as a 2 element array.
         */
        public static function get_field_description($value)
        {
            $description  = '';
            $tooltip_html = '';

            if (true === $value['desc_tip']) {
                $tooltip_html = $value['desc'];
            } elseif (!empty($value['desc_tip'])) {
                $description  = $value['desc'];
                $tooltip_html = $value['desc_tip'];
            } elseif (!empty($value['desc'])) {
                $description = $value['desc'];
            }

            if ($description && in_array($value['type'], array('textarea', 'radio'), true)) {
                $description = '<p style="margin-top:0">' . wp_kses_post($description) . '</p>';
            } elseif ($description && in_array($value['type'], array('checkbox'), true)) {
                $description = wp_kses_post($description);
            } elseif ($description) {
                $description = '<p class="description">' . wp_kses_post($description) . '</p>';
            }

            if ($tooltip_html && in_array($value['type'], array('checkbox'), true)) {
                $tooltip_html = '<p class="description">' . $tooltip_html . '</p>';
            } elseif ($tooltip_html) {
                $tooltip_html = wc_help_tip($tooltip_html);
            }

            return array(
                'description'  => $description,
                'tooltip_html' => $tooltip_html,
            );
        }

        public function woocommerce_admin_settings_sanitize_option($value, $option, $raw_value)
        {
            switch ($option['type']) {
                case 'input_toggle':
                    $value = '1' === $raw_value || 'yes' === $raw_value ? 'yes' : 'no';
                    break;
            }

            return  $value;
        }

        function generate_radio_pickup_hours_html($value)
        {
            $deliveree_services_booking_mode = get_option('deliveree_services_booking_mode', '');
            $active =  ($deliveree_services_booking_mode == 'auto_assign_booking_mode') ? 'active' : '';
            $option_value = wc_parse_relative_date_option($value['value']);
            $option_value['type'] = ($option_value['type'] === null) ? 'anytime' : $option_value['type'];

?>

            <tr valign="top" class="tr_radio_pickup_hours <?php echo esc_html($active) ?>">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr($value['id']); ?>"><?php echo esc_html($value['title']); ?></label>
                </th>
                <td class="forminp forminp-radio forminp-<?php echo esc_attr(sanitize_title($value['type'])); ?>">
                    <fieldset>
                        <?php echo esc_html($value['description']);
                        ?>
                        <ul>
                            <?php
                            foreach ($value['options'] as $key => $val) {

                            ?>
                                <li>
                                    <label>
                                        <input name="<?php echo esc_attr($value['id']); ?>[type]" value="<?php echo esc_attr($key); ?>" type="radio" style="<?php echo esc_attr($value['css']); ?>" class="<?php echo esc_attr($value['class']); ?>" <?php checked($key, $option_value['type']); ?> />
                                        <?php echo esc_html($val); ?>
                                    </label>
                                </li>
                            <?php
                            }
                            ?>
                            <li class="wrapper_pickup_hours">
                                <input id="pickup_hours_start" name="<?php echo esc_attr($value['id']); ?>[start]" value="<?php echo esc_attr($option_value['start']); ?>" type="input" style="<?php echo esc_attr($value['css']); ?>" class="pickup_hours <?php echo esc_attr($value['class']); ?>" />
                                <span class="pickup_hours_dot">-</span>
                                <input id="pickup_hours_end" name="<?php echo esc_attr($value['id']); ?>[end]" value="<?php echo esc_attr($option_value['end']); ?>" type="input" style="<?php echo esc_attr($value['css']); ?>" class="pickup_hours <?php echo esc_attr($value['class']); ?>" />
                            </li>

                        </ul>
                    </fieldset>
                </td>
            </tr>
        <?php
        }

        function generate_radio_booking_mode_html($value)
        {
            $option_value = $value['value'];

            // Description handling.
            $field_description = self::get_field_description($value);
            $description       = $field_description['description'];
            $tooltip_html      = $field_description['tooltip_html'];

            // Custom attribute handling.
            $custom_attributes = array();

            if (!empty($value['custom_attributes']) && is_array($value['custom_attributes'])) {
                foreach ($value['custom_attributes'] as $attribute => $attribute_value) {
                    $custom_attributes[] = esc_attr($attribute) . '="' . esc_attr($attribute_value) . '"';
                }
            }
        ?>

            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr($value['id']); ?>"><?php echo esc_html($value['title']); ?> </label>
                </th>
                <td class="forminp forminp-radio  forminp-<?php echo esc_attr(sanitize_title($value['type'])); ?>">
                    <fieldset>
                        <?php echo esc_html($description);
                        ?>
                        <ul>
                            <?php
                            foreach ($value['options'] as $key => $val) {
                            ?>
                                <li>
                                    <label><input name="<?php echo esc_attr($value['id']); ?>" value="<?php echo esc_attr($key); ?>" type="radio" style="<?php echo esc_attr($value['css']); ?>" class="<?php echo esc_attr($value['class']); ?>" <?php echo implode(' ', $custom_attributes); // WPCS: XSS ok.
                                                                                                                                                                                                                                                    ?> <?php checked($key, $option_value); ?> /> <?php echo esc_html($val); ?></label>
                                </li>
                            <?php
                            }
                            ?>
                        </ul>
                        <?php echo wp_kses_post($tooltip_html);
                        ?>
                    </fieldset>

                </td>
            </tr>
        <?php
        }


        function generate_input_toggle_html($value)
        {
            $checked = $value['value'] == '' || $value['value'] == 'yes' ? 'checked' : '';

            $deliveree_services_booking_mode = get_option('deliveree_services_booking_mode', '');
            $hidden =  ($value['id'] == 'deliveree_services_extra_services' && $deliveree_services_booking_mode == 'auto_assign_booking_mode') ? '' : 'active';

        ?>
            <style>
                input.input-toggle {
                    display: none;
                }

                input.input-toggle+label {
                    height: 16px;
                    width: 32px;
                    display: inline-block;
                    text-indent: -9999px;
                    border-radius: 10em;
                    position: relative;
                    margin-top: -1px;
                    vertical-align: text-top;
                    background-color: #999;
                    right: auto;
                    left: 0;
                    border: 2px solid #999;
                }

                input.input-toggle+label::before {
                    content: "";
                    display: block;
                    width: 16px;
                    height: 16px;
                    background: #fff;
                    position: absolute;
                    top: 0;
                    border-radius: 100%;
                }

                input.input-toggle:checked+label {
                    border-color: #999;
                    background-color: #935687;
                }

                input.input-toggle:checked+label::before {
                    right: 0;
                    left: auto;
                }
            </style>
            <tr valign="top" class="tr_input_toggle_<?php echo esc_attr($value['id']); ?>  <?php echo esc_attr($hidden) ?> ">
                <th scope="row" class="titledesc">
                    <label><?php echo esc_html($value['title']); ?> </label>
                </th>
                <td class="forminp">
                    <div>
                        <input class="input-toggle" value="1" <?php echo $checked; ?> <?php /*checked($value['value'], 'yes');*/ ?> name="<?php echo esc_attr($value['id']); ?>" id="<?php echo esc_attr($value['id']); ?>" type="checkbox">
                        OFF &nbsp;&nbsp;<label for="<?php echo esc_attr($value['id']); ?>">></label>&nbsp;&nbsp; ON
                    </div>

                    <div>
                        <i><?php echo esc_html($value['description']); ?></i>
                    </div>
                </td>
            </tr>
<?php
        }
    }

endif;

return new Settings_Deliveree_Services_Settings_General();
