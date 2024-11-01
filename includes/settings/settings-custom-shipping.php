<?php

if (!defined('ABSPATH')) {
    exit;
}

$googleApiKey = get_option('deliveree_google_api_key', '');
$googleApiKeyPicker = get_option('deliveree_google_api_key_picker', '');

$description = '';
if (empty($googleApiKey) || empty($googleApiKeyPicker)) {
    $description = '<p><a href="https://deliveree.com" target="_blank">click here </a>for more info about ' . DELIEVEREE_NAME . '.</p>';
}

$settings = array(
    'service_setting' => array(
        'title' => __('Service Settings', 'deliveree-same-day'),
        'type' => 'title',
        'default' => '',
        'description' => sprintf(__('Click <a href="%s" target="_blank">here</a> for more info about ' . DELIEVEREE_NAME . '.', 'deliveree-same-day'), 'https://deliveree.com'),
    ),
    'service_setting_shipping_enable' => array(
        'title' => __('Enable', 'deliveree-same-day'),
        'desc' => '',
        'desc_tip' => '',
        'id' => '',
        'default' => 'yes',
        'type' => 'checkbox',
    ),
    'title' => array(
        'title' => __('Label', 'deliveree-same-day'),
        'type' => 'text',
        'description' => __('This controls the title which the user sees during checkout.', 'deliveree-same-day'),
        'default' => __(DELIEVEREE_NAME . ' shipping method', 'deliveree-same-day'),
        'desc_tip' => true,
    ),
    'method_description' => array(
        'title' => __('Description', 'deliveree-same-day'),
        'type' => 'text',
        'description' => '',
        'default' => __('Select the right size vehicle to fit your delivery. Adjust your settings from a wide range of vehicles and trucks in each market.', 'deliveree-same-day'),

    ),
    'service_setting_shipping_delivery_type' => array(
        'title' => __('Delivery type', 'deliveree-same-day'),
        'description' => '',
        'type' => 'select',
        'default' => 'FTL',
        'options' => array(
            'FTL' => __('Full Truck Load', 'deliveree-same-day'),
        ),
    ),

    'service_setting_shipping_cargo_length_min' => array(
        'title' => __('Cargo length', 'deliveree-same-day'),
        'description' => '',
        'type' => 'text',
        'default' => '',
        'class' => 'cargo_length validate_number_decimal input_min',
        'custom_attributes' => array(
            'data-after' => 'min',
            'data-before' => 'cm',
            'data-class' => 'fieldset',
            'data-move' => 'cargo_length'
        ),
    ),
    'service_setting_shipping_cargo_length_max' => array(
        'title' => __('Max', 'deliveree-same-day'),
        'description' => '',
        'type' => 'text',
        'default' => '',
        'class' => 'validate_number_decimal input_max',
        'custom_attributes' => array(
            'data-after' => 'max',
            'data-before' => 'cm',
            'data-class' => 'fieldset',
            'data-group-input-move' => 'cargo_length',
            'data-move' => 'move_to'
        ),
    ),

    'service_setting_shipping_cargo_width_min' => array(
        'title' => __('Cargo width', 'deliveree-same-day'),
        'description' => '',
        'type' => 'text',
        'default' => '',
        'class' => 'validate_number_decimal input_min',
        'custom_attributes' => array(
            'data-after' => 'min',
            'data-before' => 'cm',
            'data-class' => 'fieldset',
            'data-move' => 'cargo_width'
        ),
    ),
    'service_setting_shipping_cargo_width_max' => array(
        'title' => __('Max', 'deliveree-same-day'),
        'description' => '',
        'type' => 'text',
        'default' => '',
        'class' => 'validate_number_decimal input_max',
        'custom_attributes' => array(
            'data-after' => 'max',
            'data-before' => 'cm',
            'data-class' => 'fieldset',
            'data-group-input-move' => 'cargo_width',
            'data-move' => 'move_to'
        ),
    ),

    'service_setting_shipping_cargo_height_min' => array(
        'title' => __('Cargo height', 'deliveree-same-day'),
        'description' => '',
        'type' => 'text',
        'default' => '',
        'class' => 'validate_number_decimal input_min',
        'custom_attributes' => array(
            'data-after' => 'min',
            'data-before' => 'cm',
            'data-class' => 'fieldset',
            'data-move' => 'cargo_height'
        ),
    ),
    'service_setting_shipping_cargo_height_max' => array(
        'title' => __('Max', 'deliveree-same-day'),
        'description' => '',
        'type' => 'text',
        'default' => '',
        'class' => 'validate_number_decimal input_max',
        'custom_attributes' => array(
            'data-after' => 'max',
            'data-before' => 'cm',
            'data-class' => 'fieldset',
            'data-group-input-move' => 'cargo_height',
            'data-move' => 'move_to'
        ),
    ),

    'service_setting_shipping_cargo_weight_min' => array(
        'title' => __('Cargo weight', 'deliveree-same-day'),
        'description' => '',
        'type' => 'text',
        'default' => '',
        'class' => 'validate_number_decimal input_min',
        'custom_attributes' => array(
            'data-after' => 'min',
            'data-before' => 'kg',
            'data-class' => 'fieldset',
            'data-move' => 'cargo_weight'
        ),
    ),

    'service_setting_shipping_cargo_weight_max' => array(
        'title' => __('Max', 'deliveree-same-day'),
        'description' => '',
        'type' => 'text',
        'default' => '',
        'class' => 'validate_number_decimal input_max',
        'custom_attributes' => array(
            'data-after' => 'max',
            'data-before' => 'kg',
            'data-class' => 'fieldset',
            'data-group-input-move' => 'cargo_weight',
            'data-move' => 'move_to'
        ),
    ),

    'service_setting_shipping_cargo_dimension_min' => array(
        'title' => __('Cargo dimension', 'deliveree-same-day'),
        'description' => '',
        'type' => 'text',
        'default' => '',
        'class' => 'validate_number_decimal input_min',
        'custom_attributes' => array(
            'data-after' => 'min',
            'data-before' => 'cbm',
            'data-class' => 'fieldset',
            'data-move' => 'cargo_dimension'
        ),
    ),

    'service_setting_shipping_cargo_dimension_max' => array(
        'title' => __('Max', 'deliveree-same-day'),
        'description' => '',
        'type' => 'text',
        'default' => '',
        'class' => 'validate_number_decimal input_max',
        'custom_attributes' => array(
            'data-after' => 'max',
            'data-before' => 'cbm',
            'data-class' => 'fieldset',
            'data-group-input-move' => 'cargo_dimension',
            'data-move' => 'move_to'
        ),
    ),

    'service_setting_shipping_maximum_distance' => array(
        'title' => __('Maximum distance', 'deliveree-same-day'),
        'description' => '',
        'type' => 'text',
        'default' => '',
        'class' => 'validate_number_decimal',
        'custom_attributes' => array(
            'data-after' => 'km',
            'data-class' => 'fieldset'
        ),
    ),
    'service_setting_shipping_minimum_price' => array(
        'title' => __('Minimum price', 'deliveree-same-day'),
        'description' => '',
        'type' => 'text',
        'default' => '',
        'class' => 'validate_number',
    ),
    'service_setting_shipping_starting_price' => array(
        'title' => __('Starting price', 'deliveree-same-day'),
        'description' => '',
        'type' => 'text',
        'default' => '',
        'class' => 'validate_number',
    ),
    'service_setting_shipping_price_per_group_km' => array(
        'title' => __('Price Per KM', 'deliveree-same-day'),
        'description' => '',
        'type' => 'hidden',
        'default' => '',
        'class' => 'validate_number start_price_group',
    ),
    'js_template' => array(
        'type' => 'js_template',
    ),
);
return $settings;
