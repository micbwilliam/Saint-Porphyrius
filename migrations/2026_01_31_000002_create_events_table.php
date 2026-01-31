<?php
/**
 * Migration: Create Events Table
 */

class SP_Migration_Create_Events_Table {
    
    public function up() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'sp_events';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_type_id bigint(20) NOT NULL,
            title_ar varchar(255) NOT NULL,
            title_en varchar(255) DEFAULT '',
            description text,
            event_date date NOT NULL,
            start_time time NOT NULL,
            end_time time DEFAULT NULL,
            location_name varchar(255) DEFAULT '',
            location_address text,
            location_lat decimal(10, 8) DEFAULT NULL,
            location_lng decimal(11, 8) DEFAULT NULL,
            attendance_points int(11) DEFAULT NULL,
            absence_penalty int(11) DEFAULT NULL,
            is_mandatory tinyint(1) DEFAULT 0,
            max_attendees int(11) DEFAULT NULL,
            status enum('draft', 'published', 'cancelled', 'completed') DEFAULT 'draft',
            created_by bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_type_id (event_type_id),
            KEY event_date (event_date),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function down() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sp_events';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
}
