<?php
/**
 * Migration: Create Event Types Table
 */

class SP_Migration_Create_Event_Types_Table {
    
    public function up() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'sp_event_types';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name_ar varchar(191) NOT NULL,
            name_en varchar(191) NOT NULL,
            slug varchar(100) NOT NULL,
            description text,
            icon varchar(100) DEFAULT 'calendar',
            color varchar(20) DEFAULT '#6C9BCF',
            attendance_points int(11) DEFAULT 10,
            absence_penalty int(11) DEFAULT 5,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Insert default event types
        $this->seed_default_event_types();
    }
    
    public function down() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sp_event_types';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
    
    private function seed_default_event_types() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sp_event_types';
        
        $defaults = array(
            array(
                'name_ar' => 'القداس الإلهي',
                'name_en' => 'Divine Liturgy',
                'slug' => 'divine-liturgy',
                'description' => 'القداس الإلهي الأسبوعي',
                'icon' => 'church',
                'color' => '#6C9BCF',
                'attendance_points' => 15,
                'absence_penalty' => 10,
            ),
            array(
                'name_ar' => 'يوم خدمة القرية',
                'name_en' => 'Village Service Day',
                'slug' => 'village-service',
                'description' => 'يوم خدمة وزيارة القرية',
                'icon' => 'heart',
                'color' => '#96C291',
                'attendance_points' => 20,
                'absence_penalty' => 5,
            ),
            array(
                'name_ar' => 'اجتماع صلاة',
                'name_en' => 'Prayer Meeting',
                'slug' => 'prayer-meeting',
                'description' => 'اجتماع الصلاة الأسبوعي',
                'icon' => 'book',
                'color' => '#F2D388',
                'attendance_points' => 10,
                'absence_penalty' => 3,
            ),
        );
        
        foreach ($defaults as $event_type) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE slug = %s",
                $event_type['slug']
            ));
            
            if (!$exists) {
                $wpdb->insert($table_name, $event_type);
            }
        }
    }
}
