<?php
/**
 * Migration: Add Extended User Profile Fields
 * Adds address details, gender, whatsapp, google maps URL, phone verification
 */

class SP_Migration_Add_Extended_User_Fields {
    
    public function up() {
        global $wpdb;
        
        $pending_table = $wpdb->prefix . 'sp_pending_users';
        
        // Check and add gender column
        $col_exists = $wpdb->get_results("SHOW COLUMNS FROM $pending_table LIKE 'gender'");
        if (empty($col_exists)) {
            $wpdb->query("ALTER TABLE $pending_table ADD COLUMN gender enum('male', 'female') NOT NULL DEFAULT 'male' AFTER last_name");
        }
        
        // Check and add whatsapp_number column
        $col_exists = $wpdb->get_results("SHOW COLUMNS FROM $pending_table LIKE 'whatsapp_number'");
        if (empty($col_exists)) {
            $wpdb->query("ALTER TABLE $pending_table ADD COLUMN whatsapp_number varchar(50) DEFAULT '' AFTER phone");
        }
        
        // Check and add whatsapp_same_as_phone column
        $col_exists = $wpdb->get_results("SHOW COLUMNS FROM $pending_table LIKE 'whatsapp_same_as_phone'");
        if (empty($col_exists)) {
            $wpdb->query("ALTER TABLE $pending_table ADD COLUMN whatsapp_same_as_phone tinyint(1) DEFAULT 1 AFTER whatsapp_number");
        }
        
        // Replace home_address with detailed address fields
        // First add the new columns
        $col_exists = $wpdb->get_results("SHOW COLUMNS FROM $pending_table LIKE 'address_area'");
        if (empty($col_exists)) {
            $wpdb->query("ALTER TABLE $pending_table ADD COLUMN address_area varchar(255) NOT NULL DEFAULT '' AFTER home_address");
        }
        
        $col_exists = $wpdb->get_results("SHOW COLUMNS FROM $pending_table LIKE 'address_street'");
        if (empty($col_exists)) {
            $wpdb->query("ALTER TABLE $pending_table ADD COLUMN address_street varchar(255) NOT NULL DEFAULT '' AFTER address_area");
        }
        
        $col_exists = $wpdb->get_results("SHOW COLUMNS FROM $pending_table LIKE 'address_building'");
        if (empty($col_exists)) {
            $wpdb->query("ALTER TABLE $pending_table ADD COLUMN address_building varchar(100) NOT NULL DEFAULT '' AFTER address_street");
        }
        
        $col_exists = $wpdb->get_results("SHOW COLUMNS FROM $pending_table LIKE 'address_floor'");
        if (empty($col_exists)) {
            $wpdb->query("ALTER TABLE $pending_table ADD COLUMN address_floor varchar(50) NOT NULL DEFAULT '' AFTER address_building");
        }
        
        $col_exists = $wpdb->get_results("SHOW COLUMNS FROM $pending_table LIKE 'address_apartment'");
        if (empty($col_exists)) {
            $wpdb->query("ALTER TABLE $pending_table ADD COLUMN address_apartment varchar(50) NOT NULL DEFAULT '' AFTER address_floor");
        }
        
        $col_exists = $wpdb->get_results("SHOW COLUMNS FROM $pending_table LIKE 'address_landmark'");
        if (empty($col_exists)) {
            $wpdb->query("ALTER TABLE $pending_table ADD COLUMN address_landmark varchar(255) DEFAULT '' AFTER address_apartment");
        }
        
        $col_exists = $wpdb->get_results("SHOW COLUMNS FROM $pending_table LIKE 'address_maps_url'");
        if (empty($col_exists)) {
            $wpdb->query("ALTER TABLE $pending_table ADD COLUMN address_maps_url text DEFAULT '' AFTER address_landmark");
        }
        
        // Add phone verification columns
        $col_exists = $wpdb->get_results("SHOW COLUMNS FROM $pending_table LIKE 'phone_verified'");
        if (empty($col_exists)) {
            $wpdb->query("ALTER TABLE $pending_table ADD COLUMN phone_verified tinyint(1) DEFAULT 0 AFTER phone");
        }
        
        $col_exists = $wpdb->get_results("SHOW COLUMNS FROM $pending_table LIKE 'phone_verification_code'");
        if (empty($col_exists)) {
            $wpdb->query("ALTER TABLE $pending_table ADD COLUMN phone_verification_code varchar(10) DEFAULT '' AFTER phone_verified");
        }
        
        $col_exists = $wpdb->get_results("SHOW COLUMNS FROM $pending_table LIKE 'phone_verification_expires'");
        if (empty($col_exists)) {
            $wpdb->query("ALTER TABLE $pending_table ADD COLUMN phone_verification_expires datetime DEFAULT NULL AFTER phone_verification_code");
        }
        
        // Create phone verification log table
        $verification_table = $wpdb->prefix . 'sp_phone_verification';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $verification_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            phone varchar(50) NOT NULL,
            code varchar(10) NOT NULL,
            type enum('registration', 'profile_update') DEFAULT 'registration',
            user_id bigint(20) DEFAULT NULL,
            pending_user_id bigint(20) DEFAULT NULL,
            verified tinyint(1) DEFAULT 0,
            attempts int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime NOT NULL,
            verified_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY phone (phone),
            KEY code (code)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function down() {
        global $wpdb;
        
        $pending_table = $wpdb->prefix . 'sp_pending_users';
        $verification_table = $wpdb->prefix . 'sp_phone_verification';
        
        // Remove new columns from pending_users
        $columns_to_remove = array(
            'gender', 'whatsapp_number', 'whatsapp_same_as_phone',
            'address_area', 'address_street', 'address_building', 
            'address_floor', 'address_apartment', 'address_landmark', 'address_maps_url',
            'phone_verified', 'phone_verification_code', 'phone_verification_expires'
        );
        
        foreach ($columns_to_remove as $col) {
            $col_exists = $wpdb->get_results("SHOW COLUMNS FROM $pending_table LIKE '$col'");
            if (!empty($col_exists)) {
                $wpdb->query("ALTER TABLE $pending_table DROP COLUMN $col");
            }
        }
        
        // Drop verification table
        $wpdb->query("DROP TABLE IF EXISTS $verification_table");
    }
}
