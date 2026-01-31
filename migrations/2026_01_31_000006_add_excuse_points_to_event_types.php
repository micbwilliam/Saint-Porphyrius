<?php
/**
 * Migration: Add Excuse Points columns to Event Types Table
 * Excuse costs vary based on days before event
 */

class SP_Migration_Add_Excuse_Points_To_Event_Types {
    
    public function up() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sp_event_types';
        
        // Add columns for excuse points at different day intervals
        // Days before event: 7+, 6, 5, 4, 3, 2, 1, 0 (same day)
        $columns = array(
            'excuse_points_7plus' => "ALTER TABLE $table_name ADD COLUMN excuse_points_7plus int(11) DEFAULT 2 COMMENT 'Excuse cost 7+ days before event'",
            'excuse_points_6' => "ALTER TABLE $table_name ADD COLUMN excuse_points_6 int(11) DEFAULT 3 COMMENT 'Excuse cost 6 days before event'",
            'excuse_points_5' => "ALTER TABLE $table_name ADD COLUMN excuse_points_5 int(11) DEFAULT 4 COMMENT 'Excuse cost 5 days before event'",
            'excuse_points_4' => "ALTER TABLE $table_name ADD COLUMN excuse_points_4 int(11) DEFAULT 5 COMMENT 'Excuse cost 4 days before event'",
            'excuse_points_3' => "ALTER TABLE $table_name ADD COLUMN excuse_points_3 int(11) DEFAULT 6 COMMENT 'Excuse cost 3 days before event'",
            'excuse_points_2' => "ALTER TABLE $table_name ADD COLUMN excuse_points_2 int(11) DEFAULT 7 COMMENT 'Excuse cost 2 days before event'",
            'excuse_points_1' => "ALTER TABLE $table_name ADD COLUMN excuse_points_1 int(11) DEFAULT 8 COMMENT 'Excuse cost 1 day before event'",
            'excuse_points_0' => "ALTER TABLE $table_name ADD COLUMN excuse_points_0 int(11) DEFAULT 10 COMMENT 'Excuse cost same day of event'",
        );
        
        foreach ($columns as $column => $sql) {
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE '$column'");
            if (empty($column_exists)) {
                $wpdb->query($sql);
            }
        }
    }
    
    public function down() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sp_event_types';
        
        $columns = array(
            'excuse_points_7plus',
            'excuse_points_6',
            'excuse_points_5',
            'excuse_points_4',
            'excuse_points_3',
            'excuse_points_2',
            'excuse_points_1',
            'excuse_points_0',
        );
        
        foreach ($columns as $column) {
            $wpdb->query("ALTER TABLE $table_name DROP COLUMN IF EXISTS $column");
        }
    }
}
