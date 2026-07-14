<?php
/**
 * Migration: Create the push notification queue
 *
 * Push notifications were sent inline, from the request that triggered them:
 * SP_Points::add() ended in a wp_remote_post() to OneSignal with a 30-second
 * timeout. SP_Attendance::process_event_points() calls add() once per attendance
 * record, so completing a 200-member mandatory event fired up to 200 sequential
 * blocking HTTP calls -- which exhausts max_execution_time long before it finishes,
 * leaving points awarded to some members and not others.
 *
 * Sends now become a row here, and a cron job drains them. The triggering request
 * pays one INSERT instead of an HTTP round-trip.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Migration_Create_Push_Queue_Table {

    public function up() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table = $wpdb->prefix . 'sp_push_queue';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");

        if (!$table_exists) {
            $wpdb->query("CREATE TABLE $table (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                title varchar(500) NOT NULL DEFAULT '',
                message text NOT NULL,
                url varchar(500) NOT NULL DEFAULT '',
                target varchar(20) NOT NULL DEFAULT 'users' COMMENT 'all | users',
                user_ids longtext DEFAULT NULL COMMENT 'JSON array of WP user ids when target = users',
                trigger_type varchar(50) NOT NULL DEFAULT 'manual',
                status varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending | sending | sent | failed',
                attempts tinyint(3) unsigned NOT NULL DEFAULT 0,
                last_error text DEFAULT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                sent_at datetime DEFAULT NULL,
                PRIMARY KEY (id),
                KEY status_created (status, created_at)
            ) $charset_collate ENGINE=InnoDB");
        }
    }

    public function down() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sp_push_queue");
    }
}
