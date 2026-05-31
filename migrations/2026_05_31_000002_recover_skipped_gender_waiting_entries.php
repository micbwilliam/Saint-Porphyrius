<?php
/**
 * Migration: Recover users wrongly dropped from the bus waiting list.
 *
 * Background: process_waiting_list() used to mark a waiting user as
 * 'skipped_gender' (with resolved_at) whenever no gender-compatible seat was
 * free *at that moment*. Because get_waiting_list() only returns
 * status = 'waiting', this silently removed them from the queue forever — even
 * though the notification promised "we'll try again when another seat is free".
 *
 * That destructive skip has now been removed from the code (skips are
 * non-destructive: the user keeps their place). This one-time migration heals
 * the rows that were already dropped before the fix:
 *
 *   - If the user has since obtained an active seat for that event, the stale
 *     row is marked 'booked' (it is genuinely resolved).
 *   - Otherwise the row is restored to 'waiting' (resolved_at / notified_at
 *     cleared) so the user re-enters the queue. The existing 5-minute cron
 *     (sp_process_bus_waiting_lists) will auto-assign them a seat as soon as a
 *     gender-compatible one is free — no notification spam from the migration.
 *
 * Finally, each affected event's queue is resequenced to contiguous 1..N
 * positions, preserving original order (position, then created_at).
 *
 * Idempotent: after it runs there are no 'skipped_gender' rows left, so a
 * second run is a no-op.
 */

class SP_Migration_Recover_Skipped_Gender_Waiting_Entries {

    public function up() {
        global $wpdb;

        $waiting_table  = $wpdb->prefix . 'sp_bus_waiting_list';
        $buses_table    = $wpdb->prefix . 'sp_event_buses';
        $bookings_table = $wpdb->prefix . 'sp_bus_seat_bookings';

        // Nothing to do if the waiting-list table was never created.
        if ($wpdb->get_var("SHOW TABLES LIKE '$waiting_table'") !== $waiting_table) {
            return;
        }

        // All rows that were dropped by the old gender-skip behaviour.
        $stuck = $wpdb->get_results(
            "SELECT id, event_id, user_id FROM $waiting_table WHERE status = 'skipped_gender'"
        );

        if (empty($stuck)) {
            return; // Idempotent: already clean.
        }

        $affected_events = array();

        foreach ($stuck as $row) {
            $affected_events[(int) $row->event_id] = true;

            // Does this user already hold an active seat for this event?
            $has_seat = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*)
                 FROM $bookings_table sb
                 INNER JOIN $buses_table eb ON sb.event_bus_id = eb.id
                 WHERE eb.event_id = %d AND sb.user_id = %d AND sb.status != 'cancelled'",
                $row->event_id, $row->user_id
            ));

            if ($has_seat > 0) {
                // Genuinely resolved — reflect that instead of re-queuing.
                $wpdb->update(
                    $waiting_table,
                    array('status' => 'booked', 'resolved_at' => current_time('mysql')),
                    array('id' => $row->id),
                    array('%s', '%s'),
                    array('%d')
                );
            } else {
                // Restore to the active queue; let the cron promote them.
                $wpdb->update(
                    $waiting_table,
                    array('status' => 'waiting', 'resolved_at' => null, 'notified_at' => null),
                    array('id' => $row->id),
                    array('%s', '%s', '%s'),
                    array('%d')
                );
            }
        }

        // Resequence each affected event's queue to 1..N, preserving order.
        foreach (array_keys($affected_events) as $event_id) {
            $entries = $wpdb->get_results($wpdb->prepare(
                "SELECT id FROM $waiting_table
                 WHERE event_id = %d AND status = 'waiting'
                 ORDER BY position ASC, created_at ASC, id ASC",
                $event_id
            ));

            $i = 1;
            foreach ($entries as $entry) {
                $wpdb->update(
                    $waiting_table,
                    array('position' => $i++),
                    array('id' => $entry->id),
                    array('%d'),
                    array('%d')
                );
            }
        }
    }

    public function down() {
        // No-op: a data heal cannot be safely reversed (we cannot know which
        // restored rows were originally 'skipped_gender'). Intentionally empty.
    }
}
