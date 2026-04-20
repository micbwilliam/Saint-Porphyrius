<?php
/**
 * Migration: Create birthday congratulations table
 * Tracks member-to-member birthday point gifts
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Migration_Create_Birthday_Congratulations_Table {
    public function up() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $table = $wpdb->prefix . 'sp_birthday_congratulations';
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            sender_id bigint(20) unsigned NOT NULL,
            recipient_id bigint(20) unsigned NOT NULL,
            points int(11) NOT NULL,
            message varchar(191) DEFAULT '',
            gift_year varchar(4) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_sender_recipient_year (sender_id, recipient_id, gift_year),
            KEY idx_recipient_year (recipient_id, gift_year)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function down() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sp_birthday_congratulations");
    }
}
