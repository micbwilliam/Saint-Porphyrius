<?php
/**
 * Saint Porphyrius - Database Migrator
 * Version-controlled database migrations
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Migrator {
    
    private static $instance = null;
    private $migrations_table;
    private $migrations_path;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->migrations_table = $wpdb->prefix . 'sp_migrations';
        $this->migrations_path = SP_PLUGIN_DIR . 'migrations/';
    }
    
    /**
     * Initialize migrations table
     */
    public function init() {
        global $wpdb;
        
        // Check if table already exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->migrations_table}'");
        if ($table_exists) {
            return true; // Table already exists
        }
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // IMPORTANT: dbDelta requires specific formatting:
        // - Two spaces after PRIMARY KEY
        // - Each field on its own line
        // - KEY must have a name
        // - varchar(191) is max for UTF-8 UNIQUE keys (avoid 767 byte limit)
        $sql = "CREATE TABLE {$this->migrations_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            migration varchar(191) NOT NULL,
            batch int(11) NOT NULL DEFAULT 1,
            executed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY migration (migration)
        ) $charset_collate ENGINE=InnoDB;";
        
        // Use dbDelta for proper table creation
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Verify table was created
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->migrations_table}'");
        
        if (!$table_exists) {
            // Fallback: try direct query
            $wpdb->query("CREATE TABLE IF NOT EXISTS {$this->migrations_table} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                migration varchar(191) NOT NULL,
                batch int(11) NOT NULL DEFAULT 1,
                executed_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY migration (migration)
            ) $charset_collate ENGINE=InnoDB");
            
            // Check again
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->migrations_table}'");
            
            if (!$table_exists && defined('WP_DEBUG') && WP_DEBUG) {
                error_log('SP Migrator: Failed to create migrations table. Last error: ' . $wpdb->last_error);
            }
        }
        
        return !empty($table_exists);
    }
    
    /**
     * Force create migrations table (for manual trigger)
     */
    public function force_create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $errors = array();
        
        // Suppress errors temporarily to capture them
        $wpdb->suppress_errors(true);
        $wpdb->show_errors = false;
        
        // Drop if exists and recreate
        $drop_result = $wpdb->query("DROP TABLE IF EXISTS {$this->migrations_table}");
        if ($wpdb->last_error) {
            $errors[] = "Drop: " . $wpdb->last_error;
        }
        
        // varchar(191) is the max safe length for UTF-8 UNIQUE keys in MySQL (avoids 767 byte limit)
        $sql = "CREATE TABLE {$this->migrations_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            migration varchar(191) NOT NULL,
            batch int(11) NOT NULL DEFAULT 1,
            executed_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY migration (migration)
        ) $charset_collate ENGINE=InnoDB";
        
        $create_result = $wpdb->query($sql);
        if ($wpdb->last_error) {
            $errors[] = "Create: " . $wpdb->last_error;
        }
        
        // Re-enable error display
        $wpdb->suppress_errors(false);
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->migrations_table}'");
        
        if (!$table_exists && empty($errors)) {
            // Try with dbDelta as fallback
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $sql_dbdelta = "CREATE TABLE {$this->migrations_table} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                migration varchar(191) NOT NULL,
                batch int(11) NOT NULL,
                executed_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                UNIQUE KEY migration (migration)
            ) $charset_collate;";
            dbDelta($sql_dbdelta);
            
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->migrations_table}'");
        }
        
        $error_msg = !empty($errors) ? implode('; ', $errors) : 'Unknown error - check MySQL user permissions';
        
        return array(
            'success' => !empty($table_exists),
            'message' => $table_exists 
                ? 'Migrations table created successfully (' . $this->migrations_table . ')' 
                : 'Failed to create table "' . $this->migrations_table . '". ' . $error_msg,
            'table_name' => $this->migrations_table,
            'sql' => $sql
        );
    }
    
    /**
     * Run all pending migrations
     */
    public function run() {
        $this->init();
        
        $pending = $this->get_pending_migrations();
        
        if (empty($pending)) {
            return array('success' => true, 'message' => 'No pending migrations.');
        }
        
        $batch = $this->get_next_batch_number();
        $executed = array();
        $failed = array();
        
        foreach ($pending as $migration) {
            $result = $this->run_migration($migration, $batch);
            if (!empty($result['success'])) {
                $executed[] = $migration;
            } else {
                $failed[] = $result;
                return array(
                    'success' => false,
                    'message' => sprintf('Migration failed: %s', $result['message'] ?? $migration),
                    'executed' => $executed,
                    'failed' => $failed
                );
            }
        }
        
        return array(
            'success' => true,
            'message' => sprintf('Executed %d migrations.', count($executed)),
            'migrations' => $executed
        );
    }
    
    /**
     * Get list of pending migrations
     */
    public function get_pending_migrations() {
        $all_migrations = $this->get_all_migration_files();
        $executed = $this->get_executed_migrations();
        
        return array_diff($all_migrations, $executed);
    }
    
    /**
     * Get all migration files
     */
    private function get_all_migration_files() {
        if (!is_dir($this->migrations_path)) {
            return array();
        }
        
        $files = glob($this->migrations_path . '*.php');
        $migrations = array();
        
        foreach ($files as $file) {
            $migrations[] = basename($file, '.php');
        }
        
        sort($migrations);
        return $migrations;
    }
    
    /**
     * Get executed migrations
     */
    private function get_executed_migrations() {
        global $wpdb;
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->migrations_table}'");
        if (!$table_exists) {
            return array();
        }
        
        $results = $wpdb->get_col("SELECT migration FROM {$this->migrations_table}");
        return $results ? $results : array();
    }
    
    /**
     * Get next batch number
     */
    private function get_next_batch_number() {
        global $wpdb;
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->migrations_table}'");
        if (!$table_exists) {
            return 1;
        }
        
        $max = $wpdb->get_var("SELECT MAX(batch) FROM {$this->migrations_table}");
        return $max ? (int) $max + 1 : 1;
    }
    
    /**
     * Run a single migration
     */
    private function run_migration($migration, $batch) {
        global $wpdb;
        
        $file = $this->migrations_path . $migration . '.php';
        
        if (!file_exists($file)) {
            return array(
                'success' => false,
                'migration' => $migration,
                'message' => 'Migration file not found.'
            );
        }
        
        require_once $file;
        
        $class_name = $this->get_class_name($migration);
        
        if (!class_exists($class_name)) {
            return array(
                'success' => false,
                'migration' => $migration,
                'message' => 'Migration class not found.'
            );
        }
        
        try {
            $instance = new $class_name();
            
            $wpdb->suppress_errors(true);
            $wpdb->show_errors = false;
            $wpdb->last_error = '';
            
            if (method_exists($instance, 'up')) {
                $instance->up();
            }
            
            $last_error = $wpdb->last_error;
            $wpdb->suppress_errors(false);
            
            if (!empty($last_error)) {
                return array(
                    'success' => false,
                    'migration' => $migration,
                    'message' => $last_error
                );
            }
            
            // Record migration
            $wpdb->insert(
                $this->migrations_table,
                array(
                    'migration' => $migration,
                    'batch' => $batch,
                    'executed_at' => current_time('mysql')
                ),
                array('%s', '%d', '%s')
            );
            
            if (!empty($wpdb->last_error)) {
                return array(
                    'success' => false,
                    'migration' => $migration,
                    'message' => 'Failed to record migration: ' . $wpdb->last_error
                );
            }
            
            return array(
                'success' => true,
                'migration' => $migration
            );
        } catch (Exception $e) {
            error_log('SP Migration Error: ' . $migration . ' - ' . $e->getMessage());
            return array(
                'success' => false,
                'migration' => $migration,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Rollback last batch
     */
    public function rollback() {
        global $wpdb;
        
        $batch = $wpdb->get_var("SELECT MAX(batch) FROM {$this->migrations_table}");
        
        if (!$batch) {
            return array('success' => false, 'message' => 'Nothing to rollback.');
        }
        
        $migrations = $wpdb->get_col($wpdb->prepare(
            "SELECT migration FROM {$this->migrations_table} WHERE batch = %d ORDER BY id DESC",
            $batch
        ));
        
        $rolled_back = array();
        
        foreach ($migrations as $migration) {
            $file = $this->migrations_path . $migration . '.php';
            
            if (file_exists($file)) {
                require_once $file;
                $class_name = $this->get_class_name($migration);
                
                if (class_exists($class_name)) {
                    $instance = new $class_name();
                    if (method_exists($instance, 'down')) {
                        $instance->down();
                    }
                }
            }
            
            $wpdb->delete($this->migrations_table, array('migration' => $migration));
            $rolled_back[] = $migration;
        }
        
        return array(
            'success' => true,
            'message' => sprintf('Rolled back %d migrations.', count($rolled_back)),
            'migrations' => $rolled_back
        );
    }
    
    /**
     * Convert migration filename to class name
     */
    private function get_class_name($migration) {
        // Remove date prefix (e.g., 2026_01_31_000001_)
        $parts = explode('_', $migration);
        
        // Skip first 4 parts (date and time)
        if (count($parts) > 4) {
            array_splice($parts, 0, 4);
        }
        
        // Convert to class name
        $class_parts = array_map('ucfirst', $parts);
        return 'SP_Migration_' . implode('_', $class_parts);
    }
    
    /**
     * Get migration status
     */
    public function status() {
        $all = $this->get_all_migration_files();
        $executed = $this->get_executed_migrations();
        $pending = $this->get_pending_migrations();
        
        return array(
            'total' => count($all),
            'executed' => count($executed),
            'pending' => count($pending),
            'pending_list' => array_values($pending)
        );
    }
    
    /**
     * Get detailed migration info
     */
    public function get_detailed_status() {
        global $wpdb;
        
        $all = $this->get_all_migration_files();
        
        // Get executed migrations with details
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->migrations_table}'");
        $executed_details = array();
        
        if ($table_exists) {
            $results = $wpdb->get_results("SELECT migration, batch, executed_at FROM {$this->migrations_table} ORDER BY id ASC");
            foreach ($results as $row) {
                $executed_details[$row->migration] = array(
                    'batch' => $row->batch,
                    'executed_at' => $row->executed_at
                );
            }
        }
        
        // Build full migration list
        $migrations = array();
        foreach ($all as $migration) {
            $is_executed = isset($executed_details[$migration]);
            $migrations[] = array(
                'name' => $migration,
                'status' => $is_executed ? 'executed' : 'pending',
                'batch' => $is_executed ? $executed_details[$migration]['batch'] : null,
                'executed_at' => $is_executed ? $executed_details[$migration]['executed_at'] : null,
            );
        }
        
        return array(
            'migrations' => $migrations,
            'total' => count($all),
            'executed' => count($executed_details),
            'pending' => count($all) - count($executed_details),
            'table_exists' => (bool) $table_exists,
            'current_batch' => !empty($executed_details) ? max(array_column(array_values($executed_details), 'batch')) : 0,
        );
    }
    
    /**
     * Check if migrations table exists
     */
    public function table_exists() {
        global $wpdb;
        return (bool) $wpdb->get_var("SHOW TABLES LIKE '{$this->migrations_table}'");
    }
    
    /**
     * Get complete expected schema from migrations
     * This defines the expected database structure based on all migrations
     */
    public function get_expected_schema() {
        global $wpdb;
        $prefix = $wpdb->prefix;
        
        return array(
            'sp_migrations' => array(
                'table' => $prefix . 'sp_migrations',
                'migration' => 'system',
                'columns' => array('id', 'migration', 'batch', 'executed_at'),
                'description' => 'Migration tracking table',
            ),
            'sp_event_types' => array(
                'table' => $prefix . 'sp_event_types',
                'migration' => '2026_01_31_000001_create_event_types_table',
                'columns' => array(
                    'id', 'name_ar', 'name_en', 'slug', 'description', 'icon', 'color',
                    'attendance_points', 'late_points', 'absence_penalty', 'is_active',
                    'excuse_points_7plus', 'excuse_points_6', 'excuse_points_5', 'excuse_points_4',
                    'excuse_points_3', 'excuse_points_2', 'excuse_points_1', 'excuse_points_0',
                    'created_at', 'updated_at'
                ),
                'description' => 'Event type definitions',
            ),
            'sp_events' => array(
                'table' => $prefix . 'sp_events',
                'migration' => '2026_01_31_000002_create_events_table',
                'columns' => array(
                    'id', 'event_type_id', 'title_ar', 'title_en', 'description',
                    'event_date', 'start_time', 'end_time', 'location_name', 'location_address',
                    'location_map_url', 'attendance_points', 'late_points', 'absence_penalty',
                    'is_mandatory', 'forbidden_enabled', 'expected_attendance_enabled', 'bus_booking_enabled',
                    'bus_booking_fee', 'max_attendees', 'status', 'created_by', 'created_at', 'updated_at'
                ),
                'description' => 'Events list',
            ),
            'sp_attendance' => array(
                'table' => $prefix . 'sp_attendance',
                'migration' => '2026_01_31_000003_create_attendance_table',
                'columns' => array(
                    'id', 'event_id', 'user_id', 'status', 'check_in_time', 'notes',
                    'points_awarded', 'points_processed', 'marked_by', 'marked_at',
                    'created_at', 'updated_at'
                ),
                'description' => 'Attendance records',
            ),
            'sp_points_log' => array(
                'table' => $prefix . 'sp_points_log',
                'migration' => '2026_01_31_000004_create_points_log_table',
                'columns' => array(
                    'id', 'user_id', 'event_id', 'points', 'type', 'reason',
                    'balance_after', 'created_by', 'created_at'
                ),
                'description' => 'Points transaction log',
            ),
            'sp_excuses' => array(
                'table' => $prefix . 'sp_excuses',
                'migration' => '2026_01_31_000007_create_excuses_table',
                'columns' => array(
                    'id', 'event_id', 'user_id', 'excuse_text', 'points_deducted',
                    'days_before_event', 'status', 'admin_id', 'admin_notes',
                    'reviewed_at', 'created_at', 'updated_at'
                ),
                'description' => 'Excuse submissions',
            ),
            'sp_qr_attendance_tokens' => array(
                'table' => $prefix . 'sp_qr_attendance_tokens',
                'migration' => '2026_02_01_000003_create_qr_attendance_tokens_table',
                'columns' => array(
                    'id', 'token', 'event_id', 'user_id', 'expires_at', 'used_at',
                    'used_by', 'attendance_status', 'ip_address', 'user_agent', 'created_at'
                ),
                'description' => 'QR code attendance tokens',
            ),
            'sp_expected_attendance' => array(
                'table' => $prefix . 'sp_expected_attendance',
                'migration' => '2026_02_01_000004_create_expected_attendance_table',
                'columns' => array(
                    'id', 'event_id', 'user_id', 'order_number', 'registered_at'
                ),
                'description' => 'Expected attendance registrations',
            ),
            'sp_forbidden_status' => array(
                'table' => $prefix . 'sp_forbidden_status',
                'migration' => '2026_02_01_000002_add_forbidden_system',
                'columns' => array(
                    'id', 'user_id', 'forbidden_remaining', 'consecutive_absences',
                    'card_status', 'last_absence_event_id', 'blocked_at', 'unblocked_at',
                    'created_at', 'updated_at'
                ),
                'description' => 'Forbidden (محروم) status tracking',
            ),
            'sp_forbidden_history' => array(
                'table' => $prefix . 'sp_forbidden_history',
                'migration' => '2026_02_01_000002_add_forbidden_system',
                'columns' => array(
                    'id', 'user_id', 'event_id', 'action_type', 'details',
                    'created_by', 'created_at'
                ),
                'description' => 'Forbidden system history log',
            ),
            'sp_phone_verification' => array(
                'table' => $prefix . 'sp_phone_verification',
                'migration' => '2026_02_01_000005_add_extended_user_fields',
                'columns' => array(
                    'id', 'phone', 'code', 'type', 'user_id', 'pending_user_id',
                    'verified', 'attempts', 'created_at', 'expires_at', 'verified_at'
                ),
                'description' => 'Phone verification codes',
            ),
            'sp_bus_templates' => array(
                'table' => $prefix . 'sp_bus_templates',
                'migration' => '2026_02_04_000001_create_bus_system_tables',
                'columns' => array(
                    'id', 'name_ar', 'name_en', 'capacity', 'rows', 'seats_per_row',
                    'aisle_position', 'layout_config', 'icon', 'color', 'is_active',
                    'created_at', 'updated_at'
                ),
                'description' => 'Bus template configurations',
            ),
            'sp_event_buses' => array(
                'table' => $prefix . 'sp_event_buses',
                'migration' => '2026_02_04_000001_create_bus_system_tables',
                'columns' => array(
                    'id', 'event_id', 'bus_template_id', 'bus_name', 'bus_number',
                    'departure_time', 'departure_location', 'return_time', 'notes',
                    'is_active', 'created_at', 'updated_at'
                ),
                'description' => 'Event bus assignments',
            ),
            'sp_bus_seat_bookings' => array(
                'table' => $prefix . 'sp_bus_seat_bookings',
                'migration' => '2026_02_04_000001_create_bus_system_tables',
                'columns' => array(
                    'id', 'event_bus_id', 'user_id', 'seat_row', 'seat_number',
                    'seat_label', 'status', 'booked_at', 'cancelled_at', 'checked_in_at'
                ),
                'description' => 'Bus seat bookings',
            ),
            'sp_point_shares' => array(
                'table' => $prefix . 'sp_point_shares',
                'migration' => '2026_02_06_000001_create_point_shares_table',
                'columns' => array(
                    'id', 'sender_id', 'recipient_id', 'points', 'message',
                    'sender_balance_before', 'sender_balance_after',
                    'recipient_balance_before', 'recipient_balance_after',
                    'sender_rank_before', 'sender_rank_after', 'created_at'
                ),
                'description' => 'Point sharing transactions between members',
            ),
            'sp_pending_users' => array(
                'table' => $prefix . 'sp_pending_users',
                'migration' => 'system',
                'columns' => array(
                    'id', 'first_name', 'last_name', 'gender', 'birth_date', 'email', 'phone',
                    'phone_verified', 'phone_verification_code', 'phone_verification_expires',
                    'whatsapp_number', 'whatsapp_same_as_phone', 'home_address',
                    'address_area', 'address_street', 'address_building', 'address_floor',
                    'address_apartment', 'address_landmark', 'address_maps_url',
                    'password', 'status', 'created_at', 'approved_at', 'approved_by'
                ),
                'description' => 'Pending user registrations',
            ),
        );
    }

    /**
     * Get database tables status with full verification
     */
    public function get_tables_status() {
        global $wpdb;
        
        $expected = $this->get_expected_schema();
        $status = array();
        
        foreach ($expected as $key => $schema) {
            $table = $schema['table'];
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            $row_count = 0;
            $columns = array();
            $missing_columns = array();
            $extra_columns = array();
            $health = 'missing';
            
            if ($exists) {
                $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
                $cols = $wpdb->get_results("SHOW COLUMNS FROM $table");
                foreach ($cols as $col) {
                    $columns[] = $col->Field;
                }
                
                // Compare columns
                $missing_columns = array_diff($schema['columns'], $columns);
                $extra_columns = array_diff($columns, $schema['columns']);
                
                if (empty($missing_columns)) {
                    $health = 'ok';
                } else {
                    $health = 'incomplete';
                }
            }
            
            $status[$key] = array(
                'key' => $key,
                'table' => $table,
                'description' => $schema['description'],
                'migration' => $schema['migration'],
                'exists' => (bool) $exists,
                'rows' => (int) $row_count,
                'columns' => $columns,
                'expected_columns' => $schema['columns'],
                'missing_columns' => array_values($missing_columns),
                'extra_columns' => array_values($extra_columns),
                'health' => $health,
            );
        }
        
        return $status;
    }
    
    /**
     * Get comprehensive database health report
     */
    public function get_health_report() {
        $tables_status = $this->get_tables_status();
        $migration_status = $this->get_detailed_status();
        
        $total_tables = count($tables_status);
        $ok_tables = 0;
        $missing_tables = 0;
        $incomplete_tables = 0;
        $issues = array();
        $repairs_needed = array();
        
        foreach ($tables_status as $key => $table) {
            if ($table['health'] === 'ok') {
                $ok_tables++;
            } elseif ($table['health'] === 'missing') {
                $missing_tables++;
                $issues[] = sprintf('Table "%s" (%s) is missing', $table['table'], $table['description']);
                $repairs_needed[] = array(
                    'type' => 'create_table',
                    'table' => $key,
                    'migration' => $table['migration'],
                );
            } else {
                $incomplete_tables++;
                $issues[] = sprintf('Table "%s" missing columns: %s', $table['table'], implode(', ', $table['missing_columns']));
                $repairs_needed[] = array(
                    'type' => 'add_columns',
                    'table' => $key,
                    'columns' => $table['missing_columns'],
                    'migration' => $table['migration'],
                );
            }
        }
        
        // Determine overall health
        $overall_health = 'healthy';
        if ($missing_tables > 0 || $incomplete_tables > 0) {
            $overall_health = $missing_tables > 0 ? 'critical' : 'warning';
        }
        
        return array(
            'overall_health' => $overall_health,
            'total_tables' => $total_tables,
            'ok_tables' => $ok_tables,
            'missing_tables' => $missing_tables,
            'incomplete_tables' => $incomplete_tables,
            'tables' => $tables_status,
            'migrations' => $migration_status,
            'issues' => $issues,
            'repairs_needed' => $repairs_needed,
        );
    }
    
    /**
     * Reset all migrations (dangerous - drops all plugin tables)
     */
    /*
    public function reset_all() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'sp_expected_attendance',
            $wpdb->prefix . 'sp_qr_attendance_tokens',
            $wpdb->prefix . 'sp_excuses',
            $wpdb->prefix . 'sp_points_log',
            $wpdb->prefix . 'sp_attendance',
            $wpdb->prefix . 'sp_events',
            $wpdb->prefix . 'sp_event_types',
            $wpdb->prefix . 'sp_migrations',
        );
        
        $dropped = array();
        foreach ($tables as $table) {
            $result = $wpdb->query("DROP TABLE IF EXISTS $table");
            if ($result !== false) {
                $dropped[] = $table;
            }
        }
        
        return array(
            'success' => true,
            'message' => sprintf('Dropped %d tables. Now run migrations again.', count($dropped)),
            'dropped' => $dropped
        );
    }
    */
    /**
     * Force run a specific migration
     */
    public function force_run_migration($migration_name) {
        global $wpdb;
        
        // Initialize migrations table first
        $this->init();
        
        $file = $this->migrations_path . $migration_name . '.php';
        
        if (!file_exists($file)) {
            return array(
                'success' => false,
                'message' => "Migration file not found: $migration_name"
            );
        }
        
        // Remove from migrations table if it exists (to re-run)
        $wpdb->delete($this->migrations_table, array('migration' => $migration_name));
        
        require_once $file;
        
        $class_name = $this->get_class_name($migration_name);
        
        if (!class_exists($class_name)) {
            return array(
                'success' => false,
                'message' => "Class not found: $class_name"
            );
        }
        
        // Suppress errors to capture them
        $wpdb->suppress_errors(true);
        $wpdb->show_errors = false;
        $wpdb->last_error = '';
        
        try {
            $instance = new $class_name();
            
            if (method_exists($instance, 'up')) {
                $instance->up();
            }
            
            $last_error = $wpdb->last_error;
            $wpdb->suppress_errors(false);
            
            if (!empty($last_error)) {
                return array(
                    'success' => false,
                    'message' => "Migration $migration_name failed: $last_error",
                    'error' => $last_error
                );
            }
            
            // Record migration
            $batch = $this->get_next_batch_number();
            $wpdb->insert(
                $this->migrations_table,
                array(
                    'migration' => $migration_name,
                    'batch' => $batch,
                    'executed_at' => current_time('mysql')
                ),
                array('%s', '%d', '%s')
            );
            
            if (!empty($wpdb->last_error)) {
                return array(
                    'success' => false,
                    'message' => "Failed to record migration: {$wpdb->last_error}",
                    'error' => $wpdb->last_error
                );
            }
            
            return array(
                'success' => true,
                'message' => "Migration $migration_name executed." . ($last_error ? " Warning: $last_error" : ''),
                'error' => $last_error
            );
        } catch (Exception $e) {
            $wpdb->suppress_errors(false);
            return array(
                'success' => false,
                'message' => "Migration $migration_name failed: " . $e->getMessage()
            );
        }
    }

    /**
     * Repair schema by re-running missing migrations
     */
    public function repair_schema() {
        global $wpdb;
        
        // First ensure migrations table exists
        $this->init();
        
        $health_report = $this->get_health_report();
        $tables = $health_report['tables'];
        
        // Build list of migrations to run based on health report
        $migrations_to_run = array();
        
        // Map of tables to their primary creation migration
        $table_migrations = array(
            'sp_event_types' => '2026_01_31_000001_create_event_types_table',
            'sp_events' => '2026_01_31_000002_create_events_table',
            'sp_attendance' => '2026_01_31_000003_create_attendance_table',
            'sp_points_log' => '2026_01_31_000004_create_points_log_table',
            'sp_excuses' => '2026_01_31_000007_create_excuses_table',
            'sp_qr_attendance_tokens' => '2026_02_01_000003_create_qr_attendance_tokens_table',
            'sp_expected_attendance' => '2026_02_01_000004_create_expected_attendance_table',
            'sp_forbidden_status' => '2026_02_01_000002_add_forbidden_system',
            'sp_forbidden_history' => '2026_02_01_000002_add_forbidden_system',
            'sp_phone_verification' => '2026_02_01_000005_add_extended_user_fields',
            'sp_bus_templates' => '2026_02_04_000001_create_bus_system_tables',
            'sp_event_buses' => '2026_02_04_000001_create_bus_system_tables',
            'sp_bus_seat_bookings' => '2026_02_04_000001_create_bus_system_tables',
            'sp_point_shares' => '2026_02_06_000001_create_point_shares_table',
        );
        
        // Check for missing tables
        foreach ($tables as $key => $table) {
            if ($table['health'] === 'missing' && isset($table_migrations[$key])) {
                $migrations_to_run[] = $table_migrations[$key];
            }
        }
        
        // Check for missing columns and identify which migrations to run
        foreach ($tables as $key => $table) {
            if ($table['health'] === 'incomplete' && !empty($table['missing_columns'])) {
                // Determine which migration(s) need to run based on missing columns
                $missing = $table['missing_columns'];
                
                if ($key === 'sp_event_types') {
                    if (in_array('late_points', $missing)) {
                        $migrations_to_run[] = '2026_02_01_000001_add_late_points_columns';
                    }
                    $excuse_cols = array('excuse_points_7plus', 'excuse_points_6', 'excuse_points_5', 'excuse_points_4',
                        'excuse_points_3', 'excuse_points_2', 'excuse_points_1', 'excuse_points_0');
                    if (count(array_intersect($missing, $excuse_cols)) > 0) {
                        $migrations_to_run[] = '2026_01_31_000006_add_excuse_points_to_event_types';
                    }
                }
                
                if ($key === 'sp_events') {
                    if (in_array('late_points', $missing)) {
                        $migrations_to_run[] = '2026_02_01_000001_add_late_points_columns';
                    }
                    if (in_array('location_map_url', $missing)) {
                        $migrations_to_run[] = '2026_01_31_000005_alter_events_add_map_url';
                    }
                    if (in_array('forbidden_enabled', $missing)) {
                        $migrations_to_run[] = '2026_02_01_000002_add_forbidden_system';
                    }
                    if (in_array('expected_attendance_enabled', $missing)) {
                        $migrations_to_run[] = '2026_02_01_000004_create_expected_attendance_table';
                    }
                    if (in_array('bus_booking_enabled', $missing) || in_array('bus_booking_fee', $missing)) {
                        $migrations_to_run[] = '2026_02_04_000001_create_bus_system_tables';
                    }
                }
                
                if ($key === 'sp_pending_users') {
                    $extended_fields = array('gender', 'birth_date', 'whatsapp_number', 'whatsapp_same_as_phone',
                        'address_area', 'address_street', 'address_building', 'address_floor', 
                        'address_apartment', 'address_landmark', 'address_maps_url',
                        'phone_verified', 'phone_verification_code', 'phone_verification_expires');
                    if (count(array_intersect($missing, $extended_fields)) > 0) {
                        $migrations_to_run[] = '2026_02_01_000005_add_extended_user_fields';
                    }
                    if (in_array('birth_date', $missing)) {
                        $migrations_to_run[] = '2026_02_02_000001_add_birthday_and_gamification';
                    }
                }
            }
        }
        
        // Remove duplicates and sort migrations in proper order
        $migrations_to_run = array_unique($migrations_to_run);
        sort($migrations_to_run);
        
        if (empty($migrations_to_run)) {
            return array(
                'success' => true,
                'message' => 'No schema repairs needed. Database is healthy.',
                'executed' => array(),
                'failed' => array()
            );
        }
        
        $executed = array();
        $failed = array();
        
        foreach ($migrations_to_run as $migration) {
            $result = $this->force_run_migration($migration);
            if (!empty($result['success'])) {
                $executed[] = $migration;
            } else {
                $failed[] = array(
                    'migration' => $migration,
                    'error' => $result['message'] ?? 'Unknown error'
                );
            }
        }
        
        $new_health = $this->get_health_report();
        
        return array(
            'success' => empty($failed),
            'message' => empty($failed)
                ? sprintf('Repaired schema by running %d migration(s). Database is now healthy.', count($executed))
                : sprintf('Schema repair completed with %d success and %d failure(s).', count($executed), count($failed)),
            'executed' => $executed,
            'failed' => $failed,
            'new_health' => $new_health['overall_health']
        );
    }
    
    /**
     * Diagnose database issues - comprehensive check
     */
    public function diagnose() {
        $health_report = $this->get_health_report();
        
        return array(
            'tables' => $health_report['tables'],
            'issues' => $health_report['issues'],
            'has_issues' => !empty($health_report['issues']),
            'overall_health' => $health_report['overall_health'],
            'repairs_needed' => $health_report['repairs_needed'],
        );
    }
    
    /**
     * Get list of all migration files with their descriptions
     */
    public function get_migrations_info() {
        $all_files = $this->get_all_migration_files();
        $executed = $this->get_executed_migrations();
        
        $migrations_info = array(
            '2026_01_31_000001_create_event_types_table' => 'Creates sp_event_types table for event type definitions',
            '2026_01_31_000002_create_events_table' => 'Creates sp_events table for event records',
            '2026_01_31_000003_create_attendance_table' => 'Creates sp_attendance table for attendance tracking',
            '2026_01_31_000004_create_points_log_table' => 'Creates sp_points_log table for points transactions',
            '2026_01_31_000005_alter_events_add_map_url' => 'Adds location_map_url column to events, removes lat/lng',
            '2026_01_31_000006_add_excuse_points_to_event_types' => 'Adds excuse point columns to event_types',
            '2026_01_31_000007_create_excuses_table' => 'Creates sp_excuses table for excuse submissions',
            '2026_02_01_000001_add_late_points_columns' => 'Adds late_points column to event_types and events',
            '2026_02_01_000002_add_forbidden_system' => 'Creates forbidden system tables and adds forbidden_enabled to events',
            '2026_02_01_000003_create_qr_attendance_tokens_table' => 'Creates sp_qr_attendance_tokens table',
            '2026_02_01_000004_create_expected_attendance_table' => 'Creates sp_expected_attendance table and adds column to events',
            '2026_02_01_000005_add_extended_user_fields' => 'Adds extended user fields to pending_users and creates phone verification table',
            '2026_02_02_000001_add_birthday_and_gamification' => 'Adds birth_date column and gamification settings',
            '2026_02_04_000001_create_bus_system_tables' => 'Creates bus system tables (templates, event_buses, seat_bookings)',
            '2026_02_06_000001_create_point_shares_table' => 'Creates point sharing table and extends points_log type enum',
        );
        
        $result = array();
        foreach ($all_files as $file) {
            $result[$file] = array(
                'name' => $file,
                'description' => $migrations_info[$file] ?? 'No description available',
                'executed' => in_array($file, $executed),
            );
        }
        
        return $result;
    }
}
