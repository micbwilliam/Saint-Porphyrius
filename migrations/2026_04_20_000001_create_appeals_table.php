<?php
/**
 * Migration: Create Appeals Table
 * Stores point appeal requests from users who attended events but couldn't scan QR
 */

class SP_Migration_Create_Appeals_Table {
    
    public function up() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'sp_appeals';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            event_id bigint(20) NOT NULL,
            reason text NOT NULL,
            status enum('pending','full','partial_80','partial_50','denied','denied_penalty') DEFAULT 'pending',
            points_awarded int(11) NOT NULL DEFAULT 0,
            admin_id bigint(20) DEFAULT NULL,
            admin_notes text DEFAULT NULL,
            reviewed_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY event_id (event_id),
            KEY status (status),
            UNIQUE KEY user_event_appeal (user_id, event_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function down() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sp_appeals';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
}
