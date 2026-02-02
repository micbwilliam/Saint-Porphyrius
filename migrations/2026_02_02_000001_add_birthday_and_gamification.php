<?php
/**
 * Migration: Add Birthday Field and Gamification Features
 * Adds birthday/date of birth, profile completion tracking, story quiz tracking
 */

class SP_Migration_Add_Birthday_And_Gamification {
    
    public function up() {
        global $wpdb;
        
        $pending_table = $wpdb->prefix . 'sp_pending_users';
        
        // Add birth_date column to pending users
        $col_exists = $wpdb->get_results("SHOW COLUMNS FROM $pending_table LIKE 'birth_date'");
        if (empty($col_exists)) {
            $wpdb->query("ALTER TABLE $pending_table ADD COLUMN birth_date date DEFAULT NULL AFTER gender");
        }
        
        // Create gamification settings if not exists
        $settings = get_option('sp_gamification_settings', array());
        if (empty($settings)) {
            $defaults = array(
                'profile_completion_points' => 50,
                'birthday_points' => 20,
                'story_quiz_points' => 25,
                'profile_completion_enabled' => 1,
                'birthday_reward_enabled' => 1,
                'story_quiz_enabled' => 1,
            );
            update_option('sp_gamification_settings', $defaults);
        }
        
        return true;
    }
    
    public function down() {
        global $wpdb;
        
        $pending_table = $wpdb->prefix . 'sp_pending_users';
        
        // Remove birth_date column
        $col_exists = $wpdb->get_results("SHOW COLUMNS FROM $pending_table LIKE 'birth_date'");
        if (!empty($col_exists)) {
            $wpdb->query("ALTER TABLE $pending_table DROP COLUMN birth_date");
        }
        
        // Remove gamification settings
        delete_option('sp_gamification_settings');
        
        return true;
    }
}
