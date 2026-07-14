<?php
/**
 * Migration: Add dedupe_key to events
 *
 * Gives sp_events the same database-enforced idempotency key sp_points_log got
 * in 2026_07_09_000001. The events admin screen handles its POST inline and
 * re-renders, so a refresh or a double-tap replayed the body and SP_Events::create()
 * -- a bare INSERT with no unique constraint -- happily inserted the event twice
 * (and fired sp_event_created twice, duplicating the push notification).
 *
 * NULL keys are allowed to repeat, so a create from an older cached form that
 * carries no token still works rather than being silently dropped.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Migration_Add_Events_Dedupe_Key {

    public function up() {
        global $wpdb;

        $events = $wpdb->prefix . 'sp_events';

        $has_column = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'dedupe_key'",
            DB_NAME,
            $events
        ));

        if (!$has_column) {
            $wpdb->query("ALTER TABLE $events ADD COLUMN dedupe_key varchar(64) DEFAULT NULL AFTER created_by");
        }

        $has_index = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND INDEX_NAME = 'dedupe_key'",
            DB_NAME,
            $events
        ));

        if (!$has_index) {
            $wpdb->query("ALTER TABLE $events ADD UNIQUE KEY dedupe_key (dedupe_key)");
        }
    }

    public function down() {
        global $wpdb;

        $events = $wpdb->prefix . 'sp_events';

        $wpdb->query("ALTER TABLE $events DROP INDEX dedupe_key");
        $wpdb->query("ALTER TABLE $events DROP COLUMN dedupe_key");
    }
}
