<?php
/**
 * Migration: Create Performance Samples Table
 *
 * Backing store for SP_Perf. One row per sampled request: how many queries it
 * ran, how long it took, how much memory it peaked at.
 *
 * A table rather than an option on purpose. An option would be read-modify-written
 * in full on every sample, which is a lost-update race under concurrency and a
 * serialized blob that grows without bound. An INSERT is one cheap, concurrency-safe
 * write, and pruning is a DELETE with a date range.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Migration_Create_Perf_Samples_Table {

    public function up() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table = $wpdb->prefix . 'sp_perf_samples';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");

        if (!$table_exists) {
            $wpdb->query("CREATE TABLE $table (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                route varchar(64) NOT NULL DEFAULT '' COMMENT 'sp_app value, or ajax:<action>',
                queries smallint(5) unsigned NOT NULL DEFAULT 0,
                ms int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'wall time, whole milliseconds',
                mem int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'peak memory, bytes',
                cache_hits smallint(5) unsigned NOT NULL DEFAULT 0,
                cache_misses smallint(5) unsigned NOT NULL DEFAULT 0,
                forced tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = captured because it was slow, not by the dice',
                plugin_version varchar(20) NOT NULL DEFAULT '',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY route_created (route, created_at),
                KEY forced_created (forced, created_at),
                KEY created_at (created_at)
            ) $charset_collate ENGINE=InnoDB");
        }
    }

    public function down() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sp_perf_samples");
    }
}
