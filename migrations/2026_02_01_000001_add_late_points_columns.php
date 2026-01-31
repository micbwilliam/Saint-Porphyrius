<?php
/**
 * Migration: Add late_points to event types and events
 */

class SP_Migration_Add_Late_Points_Columns {
    public function up() {
        global $wpdb;

        $types_table = $wpdb->prefix . 'sp_event_types';
        $events_table = $wpdb->prefix . 'sp_events';

        // Add late_points to event types if not exists
        $col = $wpdb->get_var("SHOW COLUMNS FROM {$types_table} LIKE 'late_points'");
        if (!$col) {
            $wpdb->query("ALTER TABLE {$types_table} ADD COLUMN late_points int(11) DEFAULT NULL AFTER attendance_points");
            // Backfill late_points as floor(attendance_points / 2)
            $wpdb->query("UPDATE {$types_table} SET late_points = FLOOR(attendance_points / 2) WHERE late_points IS NULL");
        }

        // Add late_points to events if not exists
        $col = $wpdb->get_var("SHOW COLUMNS FROM {$events_table} LIKE 'late_points'");
        if (!$col) {
            $wpdb->query("ALTER TABLE {$events_table} ADD COLUMN late_points int(11) DEFAULT NULL AFTER attendance_points");
            // Backfill events.late_points: prefer event-specific attendance_points if present, otherwise use type late_points
            $wpdb->query("UPDATE {$events_table} e JOIN {$types_table} t ON e.event_type_id = t.id SET e.late_points = IF(e.attendance_points IS NOT NULL, FLOOR(e.attendance_points / 2), t.late_points) WHERE e.late_points IS NULL");
        }

        return true;
    }

    public function down() {
        global $wpdb;

        $types_table = $wpdb->prefix . 'sp_event_types';
        $events_table = $wpdb->prefix . 'sp_events';

        $col = $wpdb->get_var("SHOW COLUMNS FROM {$events_table} LIKE 'late_points'");
        if ($col) {
            $wpdb->query("ALTER TABLE {$events_table} DROP COLUMN late_points");
        }

        $col = $wpdb->get_var("SHOW COLUMNS FROM {$types_table} LIKE 'late_points'");
        if ($col) {
            $wpdb->query("ALTER TABLE {$types_table} DROP COLUMN late_points");
        }

        return true;
    }
}
