<?php
/**
 * Migration: Alter Events Table - Replace lat/lng with map_url
 */

class SP_Migration_Alter_Events_Add_Map_Url {
    
    public function up() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sp_events';
        
        // Add location_map_url column if it doesn't exist
        $column_exists = $wpdb->get_var("SHOW COLUMNS FROM {$table_name} LIKE 'location_map_url'");
        if (!$column_exists) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN location_map_url varchar(500) DEFAULT '' AFTER location_address");
        }
        
        // Check if location_lat and location_lng columns exist
        $lat_exists = $wpdb->get_var("SHOW COLUMNS FROM {$table_name} LIKE 'location_lat'");
        $lng_exists = $wpdb->get_var("SHOW COLUMNS FROM {$table_name} LIKE 'location_lng'");
        
        // Migrate existing lat/lng data to Google Maps URLs if columns exist
        if ($lat_exists && $lng_exists) {
            $events = $wpdb->get_results("SELECT id, location_lat, location_lng FROM $table_name WHERE location_lat IS NOT NULL AND location_lng IS NOT NULL");
            
            foreach ($events as $event) {
                if ($event->location_lat && $event->location_lng) {
                    $map_url = 'https://www.google.com/maps?q=' . $event->location_lat . ',' . $event->location_lng;
                    $wpdb->update(
                        $table_name,
                        array('location_map_url' => $map_url),
                        array('id' => $event->id),
                        array('%s'),
                        array('%d')
                    );
                }
            }
            
            // Drop old columns only if they exist
            if ($lat_exists) {
                $wpdb->query("ALTER TABLE $table_name DROP COLUMN location_lat");
            }
            if ($lng_exists) {
                $wpdb->query("ALTER TABLE $table_name DROP COLUMN location_lng");
            }
        }
    }
    
    public function down() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sp_events';
        
        // Restore old columns
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN location_lat decimal(10, 8) DEFAULT NULL AFTER location_address");
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN location_lng decimal(11, 8) DEFAULT NULL AFTER location_lat");
        
        // Drop new column
        $wpdb->query("ALTER TABLE $table_name DROP COLUMN location_map_url");
    }
}
