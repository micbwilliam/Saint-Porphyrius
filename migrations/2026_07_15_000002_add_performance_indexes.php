<?php
/**
 * Migration: Performance indexes, and stop autoloading the big options
 *
 * Indexes for the queries the app runs constantly. Each is guarded through
 * information_schema so the migration stays re-runnable.
 *
 * The important one is sp_points_log (user_id, points). The leaderboard runs
 *
 *     SELECT user_id, SUM(points) FROM sp_points_log GROUP BY user_id ...
 *
 * and the single-column KEY(user_id) does not help it: MySQL still has to visit
 * every row to read `points`. With (user_id, points) the index *covers* the query
 * -- it can group by the leading column and read the summed column straight out of
 * the index, without touching the table at all. The same index also covers the
 * "SELECT SUM(points) ... WHERE user_id = %d FOR UPDATE" that SP_Points::add()
 * runs on every single award.
 *
 * Autoload: WordPress reads every autoload='yes' option out of wp_options on
 * *every* request, used or not. sp_github_release_backup holds the entire GitHub
 * releases JSON -- tens of kilobytes of changelog text and asset URLs -- and was
 * being loaded on every page view of the whole site to serve an update check that
 * runs twice a day. It does not need to be autoloaded, and neither do the two
 * updater flags.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Migration_Add_Performance_Indexes {

    public function up() {
        global $wpdb;

        $this->add_index($wpdb->prefix . 'sp_points_log', 'user_points', '(user_id, points)');
        $this->add_index($wpdb->prefix . 'sp_points_log', 'user_created', '(user_id, created_at)');
        $this->add_index($wpdb->prefix . 'sp_attendance', 'user_status', '(user_id, status, points_awarded)');
        $this->add_index($wpdb->prefix . 'sp_pending_users', 'status', '(status)');
        $this->add_index($wpdb->prefix . 'sp_bus_templates', 'is_active', '(is_active)');
        $this->add_index($wpdb->prefix . 'sp_quiz_categories', 'active_sort', '(is_active, sort_order)');

        $this->stop_autoloading(array(
            'sp_github_release_backup',
            'sp_was_active_before_update',
            'sp_flush_rewrite_rules',
        ));
    }

    public function down() {
        global $wpdb;

        $this->drop_index($wpdb->prefix . 'sp_points_log', 'user_points');
        $this->drop_index($wpdb->prefix . 'sp_points_log', 'user_created');
        $this->drop_index($wpdb->prefix . 'sp_attendance', 'user_status');
        $this->drop_index($wpdb->prefix . 'sp_pending_users', 'status');
        $this->drop_index($wpdb->prefix . 'sp_bus_templates', 'is_active');
        $this->drop_index($wpdb->prefix . 'sp_quiz_categories', 'active_sort');
    }

    /**
     * Add an index only if the table exists and the index does not.
     * Both guards matter: sp_pending_users is created outside the migration runner,
     * and this has to survive being run twice.
     */
    private function add_index($table, $index, $columns) {
        global $wpdb;

        if (!$this->table_exists($table) || $this->index_exists($table, $index)) {
            return;
        }

        $wpdb->query("ALTER TABLE $table ADD KEY $index $columns");
    }

    private function drop_index($table, $index) {
        global $wpdb;

        if ($this->table_exists($table) && $this->index_exists($table, $index)) {
            $wpdb->query("ALTER TABLE $table DROP INDEX $index");
        }
    }

    private function table_exists($table) {
        global $wpdb;

        return (bool) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    }

    private function index_exists($table, $index) {
        global $wpdb;

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND INDEX_NAME = %s",
            DB_NAME,
            $table,
            $index
        ));
    }

    /**
     * Flip options out of the autoload set.
     *
     * wp_set_option_autoload() is WP 6.4+; fall back to a direct UPDATE on older
     * cores. Either way the alloptions cache has to be dropped afterwards, or the
     * current request keeps serving the stale autoload set.
     */
    private function stop_autoloading($option_names) {
        global $wpdb;

        if (function_exists('wp_set_option_autoload')) {
            foreach ($option_names as $option_name) {
                wp_set_option_autoload($option_name, false);
            }

            return;
        }

        $placeholders = implode(', ', array_fill(0, count($option_names), '%s'));

        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->options} SET autoload = 'no'
             WHERE autoload = 'yes' AND option_name IN ($placeholders)",
            $option_names
        ));

        wp_cache_delete('alloptions', 'options');
    }
}
