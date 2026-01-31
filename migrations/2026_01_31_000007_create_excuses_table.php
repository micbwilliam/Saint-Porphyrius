<?php
/**
 * Migration: Create Excuses Table
 * Stores excuse submissions for mandatory events
 */

class SP_Migration_Create_Excuses_Table {
    
    public function up() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'sp_excuses';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            excuse_text text NOT NULL,
            points_deducted int(11) NOT NULL DEFAULT 0,
            days_before_event int(11) NOT NULL DEFAULT 0,
            status enum('pending','approved','denied') DEFAULT 'pending',
            admin_id bigint(20) DEFAULT NULL,
            admin_notes text DEFAULT NULL,
            reviewed_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY user_id (user_id),
            KEY status (status),
            UNIQUE KEY user_event (user_id, event_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function down() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sp_excuses';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
}
