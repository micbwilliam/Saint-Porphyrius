<?php
/**
 * Migration: Create Expected Attendance Table
 * Tracks users who plan to attend events
 */

class SP_Migration_Create_Expected_Attendance_Table {
    
    public function up() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'sp_expected_attendance';
        $events_table = $wpdb->prefix . 'sp_events';
        
        // Create expected attendance table
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            order_number int(11) NOT NULL DEFAULT 0,
            registered_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY event_user (event_id, user_id),
            KEY event_id (event_id),
            KEY user_id (user_id),
            KEY order_number (order_number)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Add expected_attendance_enabled column to events table if not exists
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $events_table LIKE 'expected_attendance_enabled'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $events_table ADD COLUMN expected_attendance_enabled tinyint(1) DEFAULT 1 AFTER forbidden_enabled");
        }
    }
    
    public function down() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sp_expected_attendance';
        $events_table = $wpdb->prefix . 'sp_events';
        
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
        
        // Remove column from events table
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $events_table LIKE 'expected_attendance_enabled'");
        if (!empty($column_exists)) {
            $wpdb->query("ALTER TABLE $events_table DROP COLUMN expected_attendance_enabled");
        }
    }
}
