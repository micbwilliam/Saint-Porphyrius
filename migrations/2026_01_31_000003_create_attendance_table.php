<?php
/**
 * Migration: Create Attendance Table
 */

class SP_Migration_Create_Attendance_Table {
    
    public function up() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'sp_attendance';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            status enum('attended', 'absent', 'excused', 'late') DEFAULT 'attended',
            check_in_time datetime DEFAULT NULL,
            notes text,
            points_awarded int(11) DEFAULT 0,
            points_processed tinyint(1) DEFAULT 0,
            marked_by bigint(20) DEFAULT NULL,
            marked_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY event_user (event_id, user_id),
            KEY event_id (event_id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function down() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sp_attendance';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
}
