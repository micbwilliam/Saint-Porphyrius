<?php
/**
 * Migration: Create User Notifications (Inbox) Table
 * In-app notification inbox for users to view all notifications
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Migration_Create_User_Notifications_Table {
    
    public function up() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $table = $wpdb->prefix . 'sp_user_notifications';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (!$table_exists) {
            $wpdb->query("CREATE TABLE $table (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL DEFAULT 0 COMMENT '0 = all users',
                title varchar(500) NOT NULL,
                message text NOT NULL,
                body_html longtext DEFAULT NULL COMMENT 'Full page content for custom notification pages',
                icon varchar(50) DEFAULT '🔔',
                type varchar(50) DEFAULT 'custom' COMMENT 'custom, event, quiz, system, registration, points, reminder',
                link_type varchar(50) DEFAULT NULL COMMENT 'event, quiz, page, url',
                link_id bigint(20) unsigned DEFAULT NULL COMMENT 'Event ID or Quiz Content ID',
                url varchar(500) DEFAULT '',
                push_log_id bigint(20) unsigned DEFAULT NULL COMMENT 'Reference to sp_push_notifications_log',
                is_read tinyint(1) DEFAULT 0,
                created_by bigint(20) unsigned DEFAULT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY type (type),
                KEY is_read (is_read),
                KEY created_at (created_at),
                KEY user_read (user_id, is_read)
            ) $charset_collate ENGINE=InnoDB");
        }
    }
    
    public function down() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sp_user_notifications");
    }
}
