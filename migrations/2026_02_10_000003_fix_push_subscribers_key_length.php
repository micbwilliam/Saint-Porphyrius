<?php
/**
 * Migration: Fix Push Subscribers Table Key Length
 * 
 * Fixes the onesignal_player_id column from varchar(255) to varchar(191)
 * to comply with the 767-byte InnoDB index limit on utf8mb4 databases.
 * 
 * If the table failed to create entirely (production), it recreates it
 * with the correct column size. If it already exists (dev), it alters
 * the column to be safe.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Migration_Fix_Push_Subscribers_Key_Length {
    
    public function up() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table = $wpdb->prefix . 'sp_push_subscribers';
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        
        if ($table_exists) {
            // Table exists (e.g. local dev) — alter column to varchar(191) for safety
            $col = $wpdb->get_row("SHOW COLUMNS FROM $table LIKE 'onesignal_player_id'");
            if ($col && strpos($col->Type, '255') !== false) {
                $wpdb->query("ALTER TABLE $table MODIFY onesignal_player_id varchar(191) NOT NULL");
            }
        } else {
            // Table doesn't exist (production failed) — create with varchar(191)
            $wpdb->query("CREATE TABLE $table (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                onesignal_player_id varchar(191) NOT NULL,
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
    }
    
    public function down() {
        // No down — the original migration handles the DROP
    }
}
