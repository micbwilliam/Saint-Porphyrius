<?php
/**
 * Migration: Create Point Shares Table + Extend Points Log Type Enum
 */

class SP_Migration_Create_Point_Shares_Table {

    public function up() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // 1. Create point shares table
        $table_name = $wpdb->prefix . 'sp_point_shares';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sender_id bigint(20) NOT NULL,
            recipient_id bigint(20) NOT NULL,
            points int(11) NOT NULL,
            message varchar(191) DEFAULT '',
            sender_balance_before int(11) NOT NULL,
            sender_balance_after int(11) NOT NULL,
            recipient_balance_before int(11) NOT NULL,
            recipient_balance_after int(11) NOT NULL,
            sender_rank_before int(11) DEFAULT NULL,
            sender_rank_after int(11) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY sender_id (sender_id),
            KEY recipient_id (recipient_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // 2. Extend points_log type enum to include sharing types
        $points_log_table = $wpdb->prefix . 'sp_points_log';
        $wpdb->query("ALTER TABLE $points_log_table MODIFY COLUMN type
            enum('reward','penalty','bonus','adjustment','point_share_sent','point_share_received')
            DEFAULT 'reward'");
    }

    public function down() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'sp_point_shares';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");

        // Revert enum
        $points_log_table = $wpdb->prefix . 'sp_points_log';
        $wpdb->query("ALTER TABLE $points_log_table MODIFY COLUMN type
            enum('reward','penalty','bonus','adjustment')
            DEFAULT 'reward'");
    }
}
