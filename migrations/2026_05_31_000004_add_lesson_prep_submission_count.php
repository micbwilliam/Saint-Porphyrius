<?php
/**
 * Migration: Add submission_count to lesson preparations
 *
 * Tracks how many times a preparation has been submitted, so the
 * `prep_max_submissions` config can actually be enforced.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Migration_Add_Lesson_Prep_Submission_Count {

    public function up() {
        global $wpdb;

        $table = $wpdb->prefix . 'sp_lesson_preparations';

        // Table may not exist yet on very fresh installs (the create migration
        // runs first via filename ordering, so this is just defensive).
        if (!$wpdb->get_var("SHOW TABLES LIKE '$table'")) {
            return;
        }

        $column = $wpdb->get_results("SHOW COLUMNS FROM $table LIKE 'submission_count'");
        if (empty($column)) {
            $wpdb->query("ALTER TABLE $table
                ADD COLUMN submission_count int(11) NOT NULL DEFAULT 0
                AFTER total_points_awarded");
        }
    }

    public function down() {
        global $wpdb;

        $table = $wpdb->prefix . 'sp_lesson_preparations';
        $column = $wpdb->get_results("SHOW COLUMNS FROM $table LIKE 'submission_count'");
        if (!empty($column)) {
            $wpdb->query("ALTER TABLE $table DROP COLUMN submission_count");
        }
    }
}
