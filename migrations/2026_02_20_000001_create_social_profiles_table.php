<?php
/**
 * Migration: Create Social Profiles Table
 * Stores cover image and profile image for social profiles
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Migration_Create_Social_Profiles_Table {
    
    public function up() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table = $wpdb->prefix . 'sp_social_profiles';
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            cover_image varchar(500) DEFAULT '',
            profile_image varchar(500) DEFAULT '',
            bio text DEFAULT '',
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function down() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sp_social_profiles");
    }
}
