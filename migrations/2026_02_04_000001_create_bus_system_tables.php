<?php
/**
 * Migration: Create Bus System Tables
 * Includes: Bus Types, Event Buses, Seat Bookings
 */

class SP_Migration_Create_Bus_System_Tables {
    
    public function up() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // 1. Bus Templates Table - defines different bus configurations
        $bus_templates_table = $wpdb->prefix . 'sp_bus_templates';
        $sql_templates = "CREATE TABLE IF NOT EXISTS $bus_templates_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name_ar varchar(255) NOT NULL,
            name_en varchar(255) DEFAULT '',
            capacity int(11) NOT NULL,
            rows int(11) NOT NULL DEFAULT 10,
            seats_per_row int(11) NOT NULL DEFAULT 4,
            aisle_position int(11) NOT NULL DEFAULT 2,
            layout_config text,
            icon varchar(50) DEFAULT '🚌',
            color varchar(20) DEFAULT '#3B82F6',
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // 2. Event Buses Table - links buses to events
        $event_buses_table = $wpdb->prefix . 'sp_event_buses';
        $sql_event_buses = "CREATE TABLE IF NOT EXISTS $event_buses_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_id bigint(20) NOT NULL,
            bus_template_id bigint(20) NOT NULL,
            bus_name varchar(255) DEFAULT '',
            bus_number int(11) NOT NULL DEFAULT 1,
            departure_time time DEFAULT NULL,
            departure_location varchar(255) DEFAULT '',
            return_time time DEFAULT NULL,
            notes text,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY bus_template_id (bus_template_id)
        ) $charset_collate;";
        
        // 3. Seat Bookings Table - tracks who booked which seat
        $seat_bookings_table = $wpdb->prefix . 'sp_bus_seat_bookings';
        $sql_bookings = "CREATE TABLE IF NOT EXISTS $seat_bookings_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_bus_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            seat_row int(11) NOT NULL,
            seat_number int(11) NOT NULL,
            seat_label varchar(10) NOT NULL,
            status enum('booked', 'cancelled', 'checked_in') DEFAULT 'booked',
            booked_at datetime DEFAULT CURRENT_TIMESTAMP,
            cancelled_at datetime DEFAULT NULL,
            checked_in_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_seat (event_bus_id, seat_row, seat_number),
            KEY event_bus_id (event_bus_id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";
        
        // 4. Add bus_booking_enabled column to events table
        $events_table = $wpdb->prefix . 'sp_events';
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $events_table LIKE 'bus_booking_enabled'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $events_table ADD COLUMN bus_booking_enabled tinyint(1) DEFAULT 0 AFTER expected_attendance_enabled");
        }
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_templates);
        dbDelta($sql_event_buses);
        dbDelta($sql_bookings);
        
        // Fallback: if dbDelta didn't create tables, run direct queries
        $table_check = $wpdb->get_var("SHOW TABLES LIKE '$bus_templates_table'");
        if (empty($table_check)) {
            $wpdb->query($sql_templates);
        }
        
        $table_check = $wpdb->get_var("SHOW TABLES LIKE '$event_buses_table'");
        if (empty($table_check)) {
            $wpdb->query($sql_event_buses);
        }
        
        $table_check = $wpdb->get_var("SHOW TABLES LIKE '$seat_bookings_table'");
        if (empty($table_check)) {
            $wpdb->query($sql_bookings);
        }
        
        // Seed default bus templates (only if table exists)
        $this->seed_default_templates();
    }
    
    public function down() {
        global $wpdb;
        
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sp_bus_seat_bookings");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sp_event_buses");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sp_bus_templates");
        
        // Remove column from events table
        $events_table = $wpdb->prefix . 'sp_events';
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $events_table LIKE 'bus_booking_enabled'");
        if (!empty($column_exists)) {
            $wpdb->query("ALTER TABLE $events_table DROP COLUMN bus_booking_enabled");
        }
    }
    
    private function seed_default_templates() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sp_bus_templates';
        
        // Make sure table exists before seeding
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (empty($table_exists)) {
            return; // Table doesn't exist, skip seeding
        }
        
        // Check if already seeded
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($count > 0) {
            return;
        }
        
        $templates = array(
            // Mini Van (14 seats: 3 rows of 4 + driver row of 2)
            array(
                'name_ar' => 'ميني فان (14 راكب)',
                'name_en' => 'Mini Van (14 passengers)',
                'capacity' => 14,
                'rows' => 4,
                'seats_per_row' => 4,
                'aisle_position' => 2,
                'layout_config' => json_encode(array(
                    'type' => 'minivan',
                    'rows' => array(
                        array('seats' => 2, 'type' => 'front'),  // Driver + 1
                        array('seats' => 4, 'type' => 'normal'),
                        array('seats' => 4, 'type' => 'normal'),
                        array('seats' => 4, 'type' => 'normal'),
                    ),
                    'disabled_seats' => array('1A')  // Driver seat
                )),
                'icon' => '🚐',
                'color' => '#10B981',
            ),
            // Small Bus (25 seats)
            array(
                'name_ar' => 'باص صغير (25 راكب)',
                'name_en' => 'Small Bus (25 passengers)',
                'capacity' => 25,
                'rows' => 7,
                'seats_per_row' => 4,
                'aisle_position' => 2,
                'layout_config' => json_encode(array(
                    'type' => 'small_bus',
                    'rows' => array(
                        array('seats' => 2, 'type' => 'front'),  // Driver area
                        array('seats' => 4, 'type' => 'normal'),
                        array('seats' => 4, 'type' => 'normal'),
                        array('seats' => 4, 'type' => 'normal'),
                        array('seats' => 4, 'type' => 'normal'),
                        array('seats' => 4, 'type' => 'normal'),
                        array('seats' => 5, 'type' => 'back'),   // Back row with 5
                    ),
                    'disabled_seats' => array('1A')
                )),
                'icon' => '🚌',
                'color' => '#3B82F6',
            ),
            // Medium Bus (35 seats)
            array(
                'name_ar' => 'باص متوسط (35 راكب)',
                'name_en' => 'Medium Bus (35 passengers)',
                'capacity' => 35,
                'rows' => 9,
                'seats_per_row' => 4,
                'aisle_position' => 2,
                'layout_config' => json_encode(array(
                    'type' => 'medium_bus',
                    'rows' => array(
                        array('seats' => 2, 'type' => 'front'),
                        array('seats' => 4, 'type' => 'normal'),
                        array('seats' => 4, 'type' => 'normal'),
                        array('seats' => 4, 'type' => 'normal'),
                        array('seats' => 4, 'type' => 'normal'),
                        array('seats' => 4, 'type' => 'normal'),
                        array('seats' => 4, 'type' => 'normal'),
                        array('seats' => 4, 'type' => 'normal'),
                        array('seats' => 5, 'type' => 'back'),
                    ),
                    'disabled_seats' => array('1A')
                )),
                'icon' => '🚌',
                'color' => '#6366F1',
            ),
            // Large Bus (50 seats)
            array(
                'name_ar' => 'باص كبير (50 راكب)',
                'name_en' => 'Large Bus (50 passengers)',
                'capacity' => 50,
                'rows' => 13,
                'seats_per_row' => 4,
                'aisle_position' => 2,
                'layout_config' => json_encode(array(
                    'type' => 'large_bus',
                    'rows' => array(
                        array('seats' => 2, 'type' => 'front'),
                        array('seats' => 4, 'type' => 'normal'),
                        array('seats' => 4, 'type' => 'normal'),
                        array('seats' => 4, 'type' => 'normal'),
                        array('seats' => 4, 'type' => 'normal'),
                        array('seats' => 4, 'type' => 'normal'),
                        array('seats' => 4, 'type' => 'normal'),
                        array('seats' => 4, 'type' => 'normal'),
                        array('seats' => 4, 'type' => 'normal'),
                        array('seats' => 4, 'type' => 'normal'),
                        array('seats' => 4, 'type' => 'normal'),
                        array('seats' => 4, 'type' => 'normal'),
                        array('seats' => 5, 'type' => 'back'),
                    ),
                    'disabled_seats' => array('1A')
                )),
                'icon' => '🚎',
                'color' => '#8B5CF6',
            ),
            // Double Decker (70 seats)
            array(
                'name_ar' => 'باص طابقين (70 راكب)',
                'name_en' => 'Double Decker (70 passengers)',
                'capacity' => 70,
                'rows' => 18,
                'seats_per_row' => 4,
                'aisle_position' => 2,
                'layout_config' => json_encode(array(
                    'type' => 'double_decker',
                    'has_upper_deck' => true,
                    'lower_rows' => 9,
                    'upper_rows' => 9,
                    'disabled_seats' => array('L1A', 'U1A')  // Driver and stairs
                )),
                'icon' => '🚍',
                'color' => '#EC4899',
            ),
        );
        
        foreach ($templates as $template) {
            $wpdb->insert($table_name, $template);
        }
    }
}
