<?php
/**
 * Migration: Create QR Attendance Tokens Table
 * Stores secure QR tokens for attendance verification
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Migration_Create_Qr_Attendance_Tokens_Table {
    
    public function up() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sp_qr_attendance_tokens';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            token varchar(64) NOT NULL,
            event_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            expires_at datetime NOT NULL,
            used_at datetime DEFAULT NULL,
            used_by bigint(20) UNSIGNED DEFAULT NULL,
            attendance_status varchar(20) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY token (token),
            KEY event_user (event_id, user_id),
            KEY expires_at (expires_at),
            KEY used_at (used_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function down() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sp_qr_attendance_tokens';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
}
