<?php
/**
 * Migration: Bus approval + audit tables.
 *
 *  - sp_bus_seat_offers : when a seat frees and the engine picks the next eligible
 *    person in the waiting list, it creates a PENDING OFFER (it no longer auto-books).
 *    An admin then Accepts / Rejects (removes from list) / Skips (next person). While an
 *    offer is 'pending' the seat is HELD and cannot be booked by anyone else.
 *
 *  - sp_bus_audit_log : full history of every bus action (book, cancel, check-in,
 *    join/leave queue, auto-offer, approve, reject, skip, move, admin edits) with who
 *    did it and when — so the admin can see the timeline per seat and per person.
 */

class SP_Migration_Create_Bus_Approval_Audit_Tables {

    public function up() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $offers_table = $wpdb->prefix . 'sp_bus_seat_offers';
        $audit_table  = $wpdb->prefix . 'sp_bus_audit_log';

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // --- Pending seat offers (admin approval queue) ---
        $sql_offers = "CREATE TABLE $offers_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_id bigint(20) NOT NULL,
            waiting_id bigint(20) NOT NULL DEFAULT 0,
            user_id bigint(20) NOT NULL,
            event_bus_id bigint(20) NOT NULL,
            seat_row int(11) NOT NULL,
            seat_number int(11) NOT NULL,
            seat_label varchar(10) NOT NULL DEFAULT '',
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            resolved_at datetime DEFAULT NULL,
            resolved_by bigint(20) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY event_id (event_id),
            KEY user_id (user_id),
            KEY status (status),
            KEY seat (event_bus_id,seat_row,seat_number)
        ) $charset_collate;";

        $sql_offers_fallback = "CREATE TABLE IF NOT EXISTS $offers_table (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `event_id` bigint(20) NOT NULL,
            `waiting_id` bigint(20) NOT NULL DEFAULT 0,
            `user_id` bigint(20) NOT NULL,
            `event_bus_id` bigint(20) NOT NULL,
            `seat_row` int(11) NOT NULL,
            `seat_number` int(11) NOT NULL,
            `seat_label` varchar(10) NOT NULL DEFAULT '',
            `status` varchar(20) NOT NULL DEFAULT 'pending',
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `resolved_at` datetime DEFAULT NULL,
            `resolved_by` bigint(20) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `event_id` (`event_id`),
            KEY `user_id` (`user_id`),
            KEY `status` (`status`),
            KEY `seat` (`event_bus_id`,`seat_row`,`seat_number`)
        ) $charset_collate;";

        // --- Audit log (full history) ---
        $sql_audit = "CREATE TABLE $audit_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_id bigint(20) NOT NULL DEFAULT 0,
            user_id bigint(20) NOT NULL DEFAULT 0,
            actor_id bigint(20) NOT NULL DEFAULT 0,
            action varchar(30) NOT NULL DEFAULT '',
            event_bus_id bigint(20) NOT NULL DEFAULT 0,
            seat_row int(11) NOT NULL DEFAULT 0,
            seat_number int(11) NOT NULL DEFAULT 0,
            seat_label varchar(10) NOT NULL DEFAULT '',
            details text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY event_id (event_id),
            KEY user_id (user_id),
            KEY action (action),
            KEY created_at (created_at)
        ) $charset_collate;";

        $sql_audit_fallback = "CREATE TABLE IF NOT EXISTS $audit_table (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `event_id` bigint(20) NOT NULL DEFAULT 0,
            `user_id` bigint(20) NOT NULL DEFAULT 0,
            `actor_id` bigint(20) NOT NULL DEFAULT 0,
            `action` varchar(30) NOT NULL DEFAULT '',
            `event_bus_id` bigint(20) NOT NULL DEFAULT 0,
            `seat_row` int(11) NOT NULL DEFAULT 0,
            `seat_number` int(11) NOT NULL DEFAULT 0,
            `seat_label` varchar(10) NOT NULL DEFAULT '',
            `details` text,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `event_id` (`event_id`),
            KEY `user_id` (`user_id`),
            KEY `action` (`action`),
            KEY `created_at` (`created_at`)
        ) $charset_collate;";

        @dbDelta($sql_offers);
        @dbDelta($sql_audit);

        if ($wpdb->get_var("SHOW TABLES LIKE '$offers_table'") !== $offers_table) {
            $wpdb->query($sql_offers_fallback);
        }
        if ($wpdb->get_var("SHOW TABLES LIKE '$audit_table'") !== $audit_table) {
            $wpdb->query($sql_audit_fallback);
        }
    }

    public function down() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sp_bus_seat_offers");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sp_bus_audit_log");
    }
}
