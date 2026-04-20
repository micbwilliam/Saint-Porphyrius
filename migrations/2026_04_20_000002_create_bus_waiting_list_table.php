<?php
/**
 * Migration: Create Bus Waiting List Table
 * Tracks users waiting for a seat when all buses for an event are full
 */

class SP_Migration_Create_Bus_Waiting_List_Table {
    
    public function up() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'sp_bus_waiting_list';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            position int(11) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'waiting',
            notified_at datetime DEFAULT NULL,
            resolved_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_event_user (event_id, user_id),
            KEY event_id (event_id),
            KEY user_id (user_id),
            KEY status (status),
            KEY position (position)
        ) $charset_collate;";
        
        $sql_fallback = "CREATE TABLE IF NOT EXISTS $table_name (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `event_id` bigint(20) NOT NULL,
            `user_id` bigint(20) NOT NULL,
            `position` int(11) NOT NULL,
            `status` varchar(20) NOT NULL DEFAULT 'waiting',
            `notified_at` datetime DEFAULT NULL,
            `resolved_at` datetime DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_event_user` (`event_id`, `user_id`),
            KEY `event_id` (`event_id`),
            KEY `user_id` (`user_id`),
            KEY `status` (`status`),
            KEY `position` (`position`)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Fallback if dbDelta fails
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            $wpdb->query($sql_fallback);
        }
    }
    
    public function down() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sp_bus_waiting_list';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
}

return new SP_Migration_Create_Bus_Waiting_List_Table();
