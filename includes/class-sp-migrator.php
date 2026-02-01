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
        
        foreach ($pending as $migration) {
            $result = $this->run_migration($migration, $batch);
            if ($result) {
                $executed[] = $migration;
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
            // Mark as executed even if file doesn't exist to avoid infinite loops
            $wpdb->insert(
                $this->migrations_table,
                array(
                    'migration' => $migration,
                    'batch' => $batch,
                    'executed_at' => current_time('mysql')
                ),
                array('%s', '%d', '%s')
            );
            return false;
        }
        
        require_once $file;
        
        $class_name = $this->get_class_name($migration);
        
        if (!class_exists($class_name)) {
            // Mark as executed even if class doesn't exist
            $wpdb->insert(
                $this->migrations_table,
                array(
                    'migration' => $migration,
                    'batch' => $batch,
                    'executed_at' => current_time('mysql')
                ),
                array('%s', '%d', '%s')
            );
            return false;
        }
        
        try {
            $instance = new $class_name();
            
            if (method_exists($instance, 'up')) {
                $instance->up();
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
            
            return true;
        } catch (Exception $e) {
            // Log error but mark as executed to avoid infinite loops
            error_log('SP Migration Error: ' . $migration . ' - ' . $e->getMessage());
            
            $wpdb->insert(
                $this->migrations_table,
                array(
                    'migration' => $migration,
                    'batch' => $batch,
                    'executed_at' => current_time('mysql')
                ),
                array('%s', '%d', '%s')
            );
            
            return false;
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
     * Get database tables status
     */
    public function get_tables_status() {
        global $wpdb;
        
        $tables = array(
            'sp_migrations' => $wpdb->prefix . 'sp_migrations',
            'sp_event_types' => $wpdb->prefix . 'sp_event_types',
            'sp_events' => $wpdb->prefix . 'sp_events',
            'sp_attendance' => $wpdb->prefix . 'sp_attendance',
            'sp_points_log' => $wpdb->prefix . 'sp_points_log',
            'sp_excuses' => $wpdb->prefix . 'sp_excuses',
            'sp_qr_attendance_tokens' => $wpdb->prefix . 'sp_qr_attendance_tokens',
            'sp_forbidden_users' => $wpdb->prefix . 'sp_forbidden_users',
            'sp_forbidden_entries' => $wpdb->prefix . 'sp_forbidden_entries',
        );
        
        $status = array();
        foreach ($tables as $key => $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            $row_count = 0;
            $columns = array();
            if ($exists) {
                $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
                $cols = $wpdb->get_results("SHOW COLUMNS FROM $table");
                foreach ($cols as $col) {
                    $columns[] = $col->Field;
                }
            }
            $status[$key] = array(
                'table' => $table,
                'exists' => (bool) $exists,
                'rows' => (int) $row_count,
                'columns' => $columns,
            );
        }
        
        return $status;
    }
    
    /**
     * Reset all migrations (dangerous - drops all plugin tables)
     */
    public function reset_all() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'sp_qr_attendance_tokens',
            $wpdb->prefix . 'sp_forbidden_entries',
            $wpdb->prefix . 'sp_forbidden_users',
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
        
        try {
            $instance = new $class_name();
            
            if (method_exists($instance, 'up')) {
                $instance->up();
            }
            
            $last_error = $wpdb->last_error;
            $wpdb->suppress_errors(false);
            
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
     * Diagnose database issues
     */
    public function diagnose() {
        global $wpdb;
        
        $issues = array();
        $tables_status = $this->get_tables_status();
        
        // Check sp_event_types columns
        if ($tables_status['sp_event_types']['exists']) {
            $required_cols = array('id', 'name_ar', 'name_en', 'slug', 'attendance_points', 'late_points', 'absence_penalty', 
                'excuse_points_7plus', 'excuse_points_6', 'excuse_points_5', 'excuse_points_4', 
                'excuse_points_3', 'excuse_points_2', 'excuse_points_1', 'excuse_points_0');
            $existing_cols = $tables_status['sp_event_types']['columns'];
            $missing = array_diff($required_cols, $existing_cols);
            if (!empty($missing)) {
                $issues[] = "sp_event_types missing columns: " . implode(', ', $missing);
            }
        } else {
            $issues[] = "sp_event_types table does not exist";
        }
        
        // Check sp_events columns
        if ($tables_status['sp_events']['exists']) {
            $required_cols = array('id', 'event_type_id', 'title_ar', 'event_date', 'start_time', 'status', 'late_points', 'map_url');
            $existing_cols = $tables_status['sp_events']['columns'];
            $missing = array_diff($required_cols, $existing_cols);
            if (!empty($missing)) {
                $issues[] = "sp_events missing columns: " . implode(', ', $missing);
            }
        } else {
            $issues[] = "sp_events table does not exist";
        }
        
        // Check migrations table
        if (!$tables_status['sp_migrations']['exists']) {
            $issues[] = "sp_migrations table does not exist";
        }
        
        return array(
            'tables' => $tables_status,
            'issues' => $issues,
            'has_issues' => !empty($issues)
        );
    }
}
