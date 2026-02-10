<?php
/**
 * Migration: Create Push Notifications Tables
 * Tables for OneSignal push notification subscribers and notification log
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Migration_Create_Push_Notifications_Tables {
    
    public function up() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // 1. Push Notification Subscribers table
        $subscribers_table = $wpdb->prefix . 'sp_push_subscribers';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$subscribers_table'");
        if (!$table_exists) {
            $wpdb->query("CREATE TABLE $subscribers_table (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                onesignal_player_id varchar(255) NOT NULL,
                device_type varchar(50) DEFAULT 'web',
                browser varchar(100) DEFAULT '',
                subscribed_at datetime DEFAULT CURRENT_TIMESTAMP,
                last_active datetime DEFAULT CURRENT_TIMESTAMP,
                is_active tinyint(1) DEFAULT 1,
                points_awarded tinyint(1) DEFAULT 0 COMMENT 'Whether subscription points were awarded',
                PRIMARY KEY (id),
                UNIQUE KEY player_id (onesignal_player_id),
                KEY user_id (user_id),
                KEY is_active (is_active)
            ) $charset_collate ENGINE=InnoDB");
        }
        
        // 2. Push Notification Log table
        $log_table = $wpdb->prefix . 'sp_push_notifications_log';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$log_table'");
        if (!$table_exists) {
            $wpdb->query("CREATE TABLE $log_table (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                title varchar(500) NOT NULL,
                message text NOT NULL,
                url varchar(500) DEFAULT '',
                segment varchar(100) DEFAULT 'all' COMMENT 'all, subscribed_users, specific_users',
                target_users text DEFAULT NULL COMMENT 'JSON array of user IDs if segment=specific_users',
                onesignal_notification_id varchar(255) DEFAULT NULL,
                sent_count int(11) DEFAULT 0,
                delivered_count int(11) DEFAULT 0,
                clicked_count int(11) DEFAULT 0,
                trigger_type varchar(50) DEFAULT 'manual' COMMENT 'manual, auto_event, auto_points, auto_registration, auto_quiz',
                sent_by bigint(20) unsigned DEFAULT NULL,
                sent_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY trigger_type (trigger_type),
                KEY sent_at (sent_at)
            ) $charset_collate ENGINE=InnoDB");
        }
    }
    
    public function down() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sp_push_notifications_log");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sp_push_subscribers");
    }
}
