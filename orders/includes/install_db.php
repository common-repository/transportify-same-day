<?php

function booking_db_install()
{
    global $wpdb;
    $tableName = $wpdb->prefix . DELIEVEREE_BOOKING_TABLE_NAME;
    $sql = "CREATE TABLE $tableName (
              `id` INT (1) NOT NULL AUTO_INCREMENT,
              `order_id` VARCHAR (25),
              `drop_no` VARCHAR (25),
              `delivery_type` VARCHAR (255),
              `service_label` VARCHAR (255),
              `customer` VARCHAR (255),
              `shipping_address` VARCHAR (255),
              `purchase_date` DATETIME,
              `item` LONGTEXT,
              `total_order` VARCHAR (50),
              `dimensions` VARCHAR (255),
              `volume` VARCHAR (50),
              `weight` VARCHAR (50),
              `paid_shipping` VARCHAR (50),
              `google_data` LONGTEXT NULL,
              `full_text_search` LONGTEXT NULL,
               `booking_confirm` INT(1) DEFAULT 0 NULL, 
               `locations` LONGTEXT NULL,
               `data_order` LONGTEXT NULL,
               PRIMARY KEY (`id`)
        );";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    add_option('booking_deliveree_db_version', '1.0');
}

register_activation_hook(__FILE__, 'booking_install');

/**
 * Trick to update plugin database, see docs
 */
function booking_deliveree_update_db_check()
{

    if (get_site_option('booking_deliveree_db_version') === false) {
        booking_db_install();
        //Version 1.0
    }

    if (get_site_option('booking_deliveree_db_version') === '1.0') {
        booking_db_update_1_1();
        //Version 1.1
    }

    if (get_site_option('booking_deliveree_db_version') === '1.1') {
        booking_db_update_1_2();
        //Version 1.2
    }

    


    if (get_site_option('booking_deliveree_db_version') === '1.2') {

        booking_db_update_1_3();
        //Version 1.3
    }
}

add_action('plugins_loaded', 'booking_deliveree_update_db_check');

function dropTable()
{
    global $wpdb;
    $tableName = $wpdb->prefix . DELIEVEREE_BOOKING_TABLE_NAME;
    $sql = "DROP TABLE IF EXISTS $tableName";
    $wpdb->query($sql);
}



function booking_db_update_1_1()
{
    global $wpdb;
    $tableName = $wpdb->prefix . DELIEVEREE_BOOKING_TABLE_NAME;
    $sql = "ALTER TABLE $tableName 
    ADD COLUMN  `shipping_method_id` VARCHAR (255) DEFAULT 'deliveree_shipping_method',
    ADD COLUMN  `city` VARCHAR (255) DEFAULT NULL,
    ADD COLUMN  `postal_code` VARCHAR (255) DEFAULT NULL,
    ADD COLUMN  `adjustments` LONGTEXT DEFAULT NULL,
    ADD COLUMN  `vehicle_type` LONGTEXT DEFAULT NULL;";


    $wpdb->query($sql);

    update_option('booking_deliveree_db_version', '1.1');
}

function booking_db_update_1_2()
{
    global $wpdb;
    $tableName = $wpdb->prefix . DELIEVEREE_BOOKING_TABLE_NAME;
    $sql = "ALTER TABLE $tableName 
    ADD COLUMN  `vehicle_type_name` VARCHAR (255) DEFAULT NULL,
    ADD COLUMN  `adjustments_type` VARCHAR (255) DEFAULT NULL;";

    $wpdb->query($sql);
    update_option('booking_deliveree_db_version', '1.2');
}

function booking_db_update_1_3()
{

    global $wpdb;
    $tableName = $wpdb->prefix . DELIEVEREE_BOOKING_TABLE_NAME;
    $sql = "ALTER TABLE $tableName 
    CHANGE COLUMN `adjustments_type` `adjustments_amount` VARCHAR(255) NULL DEFAULT NULL;";

    $wpdb->query($sql);
    update_option('booking_deliveree_db_version', '1.3');
}
