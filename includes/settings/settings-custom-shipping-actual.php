<?php

if (!defined('ABSPATH')) {
    exit;
}


$country = get_option('deliveree_general_setting_origin_country', 'ID');

switch ($country) {
    case 'ID':
        $link = 'https://www.deliveree.com/id/en/smart-trucking/fleet-prices/'; //Indonesia 
        $tile_country = ' in Indonesia';

        break;
    case 'TH':
        $link = 'https://www.deliveree.com/th/en/delivery/fleet-price/bangkok-metro/'; //Thailand 
        $tile_country = ' in Thailand';

        break;
    case 'PH':
        $link = 'https://www.transportify.com.ph/trucking-solutions/fleet-price/'; //Philippines 
        $tile_country = ' in Philippines';

        break;
    default:
        $link = 'https://www.deliveree.com';
        $tile_country = '';

        break;
}


return [
    'service_setting' => [
        'title' => __('Service Settings', 'deliveree-same-day'),
        'type' => 'title',
        'default' => '',
        'description' => sprintf(__('Click <a href="%s" target="_blank">here</a> for ' . DELIEVEREE_NAME . '\'s fleet choices' . $tile_country . '.', 'deliveree-same-day'), $link),
    ],
    'title' => [
        'title' => __('Label', 'deliveree-same-day'),
        'type' => 'text_with_logo',
        'default' => __(DELIEVEREE_NAME, 'deliveree-same-day'),
        'class' => 'input-width'
    ],
    'select_default_vehicle' => [
        'title' => __('Default vehicle', 'deliveree-same-day'),
        'type' => 'select_default_vehicle',
        'default' => __(DELIEVEREE_NAME, 'deliveree-same-day'),
        'class' => 'input-width'
    ],
    'service_setting_shipping_delivery_actual_adjustment' => [
        'title' => __('Add an adjustment to ' . DELIEVEREE_NAME . ' price', 'deliveree-same-day'),
        'id' => 'service_setting_shipping_delivery_actual_adjustment',
        'type' => 'radio',
        'description' => '',
        'desc_tip' => true,
        'is_required' => true,
        'class' => 'deliveree_radio deliveree_radio_js_method',
        'style' => '',
        'options' => [
            '' => __('None', 'deliveree-same-day'),
            'premium' => __('Premium', 'deliveree-same-day'),
            'discount' => __('Discount', 'deliveree-same-day'),
        ],
    ],

];
