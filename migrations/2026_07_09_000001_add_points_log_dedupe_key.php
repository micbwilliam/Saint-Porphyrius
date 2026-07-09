<?php
/**
 * Migration: Add dedupe_key to points log
 *
 * Gives sp_points_log a database-enforced idempotency key so a repeated
 * request (double-tap, browser refresh of a POST, retried AJAX call) can
 * never insert the same award twice. NULL keys are allowed to repeat, so
 * genuinely repeatable awards (manual adjustments) keep working.
 *
 * Also backfills sp_attendance.points_processed for rows whose points were
 * already granted by SP_Attendance::mark(). Without this, completing an old
 * event would re-award every attendance record a second time.
 */

class SP_Migration_Add_Points_Log_Dedupe_Key {

    public function up() {
        global $wpdb;

        $points_log = $wpdb->prefix . 'sp_points_log';
        $attendance = $wpdb->prefix . 'sp_attendance';

        // 1. Add the dedupe_key column if it isn't there yet.
        $has_column = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'dedupe_key'",
            DB_NAME,
            $points_log
        ));

        if (!$has_column) {
            $wpdb->query("ALTER TABLE $points_log ADD COLUMN dedupe_key varchar(64) DEFAULT NULL AFTER created_by");
        }

        // 2. Clear any pre-existing duplicate keys before the unique index goes on.
        //    (Nothing can have a key yet, but keep the migration re-runnable.)
        $has_index = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND INDEX_NAME = 'dedupe_key'",
            DB_NAME,
            $points_log
        ));

        if (!$has_index) {
            $wpdb->query("ALTER TABLE $points_log ADD UNIQUE KEY dedupe_key (dedupe_key)");
        }

        // 3. Widen `type` to a varchar. It was an enum of 6 values while the code writes 25
        //    (attendance, birthday_reward, bus_booking_refund, …), so every other value was
        //    silently coerced to ''. That broke the dedup guard in refund_bus_booking_fee(),
        //    which looks for type = 'bus_booking_refund' and never matched.
        $wpdb->query("ALTER TABLE $points_log MODIFY COLUMN type varchar(40) NOT NULL DEFAULT 'reward'");

        // 4. Backfill points_processed on attendance rows that already had their
        //    points written to the log. SP_Attendance::mark() awards immediately
        //    but historically never set this flag, so complete_event() would grant
        //    the same points a second time via process_event_points().
        $wpdb->query(
            "UPDATE $attendance a
             SET a.points_processed = 1
             WHERE a.points_processed = 0
               AND a.points_awarded <> 0
               AND EXISTS (
                   SELECT 1 FROM $points_log p
                   WHERE p.user_id = a.user_id
                     AND p.event_id = a.event_id
               )"
        );
    }

    public function down() {
        global $wpdb;

        $points_log = $wpdb->prefix . 'sp_points_log';

        $wpdb->query("ALTER TABLE $points_log DROP INDEX dedupe_key");
        $wpdb->query("ALTER TABLE $points_log DROP COLUMN dedupe_key");
    }
}
