<?php
/**
 * Migration: Create Points Log Table
 */

class SP_Migration_Create_Points_Log_Table {
    
    public function up() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'sp_points_log';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            event_id bigint(20) DEFAULT NULL,
            points int(11) NOT NULL,
            type enum('reward', 'penalty', 'bonus', 'adjustment') DEFAULT 'reward',
            reason varchar(255) DEFAULT '',
            balance_after int(11) DEFAULT 0,
            created_by bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY event_id (event_id),
            KEY type (type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function down() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sp_points_log';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
}
