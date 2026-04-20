<?php
/**
 * Migration: Create birthday gifts tables
 * - sp_birthday_gifts: Admin-defined gift options (points, money, custom)
 * - sp_birthday_gift_claims: Tracks which user claimed which gift
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Migration_Create_Birthday_Gifts_Tables {
    public function up() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        // Gift options defined by admin
        $gifts_table = $wpdb->prefix . 'sp_birthday_gifts';
        $sql_gifts = "CREATE TABLE {$gifts_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(191) NOT NULL,
            description text,
            gift_type varchar(50) NOT NULL DEFAULT 'points',
            icon varchar(10) NOT NULL DEFAULT '🎁',
            value varchar(191) NOT NULL DEFAULT '',
            is_active tinyint(1) NOT NULL DEFAULT 1,
            sort_order int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_active_sort (is_active, sort_order)
        ) {$charset};";

        // Track which gift each user claimed per year
        $claims_table = $wpdb->prefix . 'sp_birthday_gift_claims';
        $sql_claims = "CREATE TABLE {$claims_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            gift_id bigint(20) unsigned NOT NULL,
            claim_year varchar(4) NOT NULL,
            claimed_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_user_year (user_id, claim_year),
            KEY idx_gift_id (gift_id)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_gifts);
        dbDelta($sql_claims);
    }

    public function down() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sp_birthday_gift_claims");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sp_birthday_gifts");
    }
}
