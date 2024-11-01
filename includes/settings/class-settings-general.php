<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('Deliveree_WC_Custom_Shipping_Methods_Settings_General')) :

    class Deliveree_WC_Custom_Shipping_Methods_Settings_General extends Deliveree_WC_Custom_Shipping_Methods_Settings_Section
    {
        /**
         * Constructor.
         */
        function __construct()
        {
            $this->id = '';
            $this->desc = __('General', 'deliveree-same-day');
            parent::__construct();

            add_action('woocommerce_admin_field_hiden_text',              array($this, 'generate_hiden_text_html'));
        }

        /**
         * @return array
         */
        function get_settings()
        {
            $admin_settings = array(
                array(
                    'title' => __(DELIEVEREE_NAME . ' API configuration', 'deliveree-same-day'),
                    'type' => 'title',
                    'desc' => ''
                ),
                array(
                    'title' => __(DELIEVEREE_NAME . ' system selection ', 'deliveree-same-day'),
                    'id' => 'deliveree_api_method',
                    'type' => 'radio',
                    'class' => 'deliveree_api_method_radio',
                    'desc_tip' => __('Switch between Test mode (simulate bookings) and Live mode (real bookings).', 'deliveree-same-day'),
                    'options' => [
                        'test_mode' => 'Test mode',
                        'live_mode' => 'Live mode'
                    ],
                ),
                array(
                    'title' => __(DELIEVEREE_NAME . ' API key', 'deliveree-same-day'),
                    'id' => 'deliveree_api_key',
                    'type' => 'text',
                    'orig_type' => 'text',
                    'default' => '',
                    'is_required' => true,
                    'class' => 'deliveree-api-key-input validate_api_key_input',
                    'desc_tip' => __('This is required to go live. To get your API key, contact <a>business@'.DELIEVEREE_DOMAIN.'</a>.'),
                ),
                array(
                    'type' => 'sectionend',
                ),

                //Google
                array(
                    'title' => __('Google API  (optional)', 'deliveree-same-day'),
                    'type' => 'title',
                    'id' => 'deliveree_custom_shipping_methods_admin_options',
                    'desc' => ''
                ),
                array(
                    'title' => __('Distance calculator API key', 'deliveree-same-day'),
                    'id' => 'deliveree_google_api_key',
                    'type' => 'text',
                    'orig_type' => 'text',
                    'desc_tip' => __('API key used to calculate the shipping address distance. Required Google API service: Distance matrix API.', 'deliveree-same-day'),
                    'description' => '',
                    'default' => '',
                    'is_required' => true,
                    'class' => 'deliveree-api-key-input',
                ),
                array(
                    'title' => __('Location picker API key', 'deliveree-same-day'),
                    'id' => 'deliveree_google_api_key_picker',
                    'type' => 'text',
                    'orig_type' => 'text',
                    'desc_tip' => __('API key used to render the location picker map. Required Google API services: Maps JavaScript API, Geocoding API, Places API.', 'deliveree-same-day'),
                    'description' => '',
                    'default' => '',
                    'is_required' => true,
                    'class' => 'deliveree-api-key-input',
                ),
                array(
                    'type' => 'sectionend',
                ),

                //Store Location Settings
                array(
                    'title' => __('Store location settings', 'deliveree-same-day'),
                    'type' => 'title',
                    'desc' => ''
                ),
                array(
                    'title' => __('Store origin data type', 'deliveree-same-day'),
                    'id' => 'deliveree_general_setting_origin_data_type',
                    'type' => 'radio',
                    'description' => __('Store Origin Data Type', 'deliveree-same-day'),
                    'desc_tip' => true,
                    'default' => 'coordinate',
                    'is_required' => true,
                    'class' => 'deliveree_origin_data_type deliveree_api_method_radio',
                    'options' => array(
                        'coordinate' => __('Enter geo coordinates', 'deliveree-same-day'),
                        'address' => __('Enter address', 'deliveree-same-day'),
                    ),
                ),
                array(
                    'title' => __('Store location latitude', 'deliveree-same-day'),
                    'id' => 'deliveree_general_setting_origin_lat',
                    'type' => 'text',
                    'orig_type' => 'text',
                    'description' => __('Store Location Latitude', 'deliveree-same-day'),
                    'desc_tip' => true,
                    'default' => '',
                    'is_required' => true,
                    'class' => 'deliveree-field--origin',
                    'custom_attributes' => array(
                        'data-link' => 'location_picker',
                    ),
                ),
                array(
                    'title' => __('Store location longitude', 'deliveree-same-day'),
                    'id' => 'deliveree_general_setting_origin_lng',
                    'type' => 'text',
                    'orig_type' => 'text',
                    'description' => __('Store location longitude', 'deliveree-same-day'),
                    'desc_tip' => true,
                    'default' => '',
                    'is_required' => true,
                    'class' => 'deliveree-field--origin',
                    'custom_attributes' => array(
                        'data-link' => 'location_picker'
                    ),
                ),
                array(
                    'title' => __('Store location address', 'deliveree-same-day'),
                    'id' => 'deliveree_general_setting_origin_address',
                    'type' => 'textarea',
                    'orig_type' => 'text',
                    'description' => __('Store location full address', 'deliveree-same-day'),
                    'desc_tip' => true,
                    'default' => '',
                    'class' => 'deliveree-field--origin origin_address deliveree-origin_address',
                    'custom_attributes' => array(
                        'data-link' => 'location_picker',
                        'rows' => 3
                    ),
                ),
                array(
                    'title' => __('Coutry', 'deliveree-same-day'),
                    'id' => 'deliveree_general_setting_origin_country',
                    'type' => 'hiden_text',
                ),
                array(
                    'type' => 'sectionend',
                ),
            );
            return $admin_settings;
        }

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


        function generate_hiden_text_html($value)
        {
            // Description handling.
            $field_description = self::get_field_description($value);
            $description       = $field_description['description'];
            $tooltip_html      = $field_description['tooltip_html'];
            $option_value = $value['value'];

?>
            <tr valign="top" style="display: none;">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr($value['id']); ?>"><?php echo esc_html($value['title']); ?> <?php echo wp_kses_post($tooltip_html); 
                                                                                                                ?></label>
                </th>
                <td class="forminp forminp-<?php echo esc_attr(sanitize_title($value['type'])); ?>">
                    <input name="<?php echo esc_attr($value['id']); ?>" id="<?php echo esc_attr($value['id']); ?>" type="<?php echo esc_attr($value['type']); ?>" style="<?php echo esc_attr($value['css']); ?>" value="<?php echo esc_attr($option_value); ?>" class="<?php echo esc_attr($value['class']); ?>" placeholder="<?php echo esc_attr($value['placeholder']); ?>" />
                    <?php
                    echo esc_html($value['suffix']);
                    ?>
                    <?php
                    echo esc_html($description); 
                    ?>
                </td>
            </tr>
<?php
        }
    }

endif;

return new Deliveree_WC_Custom_Shipping_Methods_Settings_General();
