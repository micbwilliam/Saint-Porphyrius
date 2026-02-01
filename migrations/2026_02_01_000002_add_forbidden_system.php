<?php
/**
 * Migration: Add Forbidden System
 * 
 * Adds columns and tables for the محروم (forbidden) discipline system
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Migration_Add_Forbidden_System {
    
    public function up() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // 1. Add forbidden_enabled column to events table
        $events_table = $wpdb->prefix . 'sp_events';
        $column_exists = $wpdb->get_var("SHOW COLUMNS FROM {$events_table} LIKE 'forbidden_enabled'");
        
        if (!$column_exists) {
            $wpdb->query("ALTER TABLE {$events_table} ADD COLUMN `forbidden_enabled` TINYINT(1) DEFAULT 1 AFTER `is_mandatory`");
        }
        
        // 2. Create forbidden tracking table
        $forbidden_table = $wpdb->prefix . 'sp_forbidden_status';
        
        $sql = "CREATE TABLE IF NOT EXISTS {$forbidden_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            forbidden_remaining INT(11) DEFAULT 0 COMMENT 'Number of forbidden events remaining',
            consecutive_absences INT(11) DEFAULT 0 COMMENT 'Consecutive unexcused absences count',
            card_status ENUM('none', 'yellow', 'red') DEFAULT 'none',
            last_absence_event_id BIGINT(20) UNSIGNED NULL,
            blocked_at DATETIME NULL,
            unblocked_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            KEY card_status (card_status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // 3. Create forbidden history table for tracking
        $forbidden_history_table = $wpdb->prefix . 'sp_forbidden_history';
        
        $sql2 = "CREATE TABLE IF NOT EXISTS {$forbidden_history_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            event_id BIGINT(20) UNSIGNED NOT NULL,
            action_type ENUM('absence_recorded', 'forbidden_applied', 'forbidden_served', 'yellow_card', 'red_card', 'admin_reset', 'admin_unblock') NOT NULL,
            details TEXT NULL,
            created_by BIGINT(20) UNSIGNED NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY event_id (event_id),
            KEY action_type (action_type)
        ) $charset_collate;";
        
        dbDelta($sql2);
        
        // 4. Add forbidden settings option
        $default_settings = array(
            'forbidden_events_count' => 2,  // Number of events user is forbidden after absence
            'yellow_card_threshold' => 3,   // Consecutive absences for yellow card
            'red_card_threshold' => 6,      // Consecutive absences for red card (app blocked)
        );
        
        if (!get_option('sp_forbidden_settings')) {
            add_option('sp_forbidden_settings', $default_settings);
        }
        
        return true;
    }
    
    public function down() {
        global $wpdb;
        
        // Remove forbidden_enabled column from events
        $events_table = $wpdb->prefix . 'sp_events';
        $column_exists = $wpdb->get_var("SHOW COLUMNS FROM {$events_table} LIKE 'forbidden_enabled'");
        
        if ($column_exists) {
            $wpdb->query("ALTER TABLE {$events_table} DROP COLUMN `forbidden_enabled`");
        }
        
        // Drop forbidden tables
        $forbidden_table = $wpdb->prefix . 'sp_forbidden_status';
        $wpdb->query("DROP TABLE IF EXISTS {$forbidden_table}");
        
        $forbidden_history_table = $wpdb->prefix . 'sp_forbidden_history';
        $wpdb->query("DROP TABLE IF EXISTS {$forbidden_history_table}");
        
        // Remove settings
        delete_option('sp_forbidden_settings');
        
        return true;
    }
}
