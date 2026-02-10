<?php
/**
 * Migration: Create Quiz System Tables
 * Tables for categorized Christian quizzes with AI generation
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Migration_Create_Quiz_System_Tables {
    
    public function up() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // 1. Quiz Categories table
        $categories_table = $wpdb->prefix . 'sp_quiz_categories';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$categories_table'");
        if (!$table_exists) {
            $wpdb->query("CREATE TABLE $categories_table (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                name_ar varchar(255) NOT NULL,
                name_en varchar(255) DEFAULT '',
                description_ar text DEFAULT NULL,
                icon varchar(50) DEFAULT '📖',
                color varchar(20) DEFAULT '#3B82F6',
                sort_order int(11) DEFAULT 0,
                is_active tinyint(1) DEFAULT 1,
                created_by bigint(20) unsigned NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset_collate ENGINE=InnoDB");
        }
        
        // 2. Quiz Content table (admin-created content that AI processes)
        $content_table = $wpdb->prefix . 'sp_quiz_content';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$content_table'");
        if (!$table_exists) {
            $wpdb->query("CREATE TABLE $content_table (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                category_id bigint(20) unsigned NOT NULL,
                title_ar varchar(500) NOT NULL,
                title_en varchar(500) DEFAULT '',
                content_type enum('text','youtube','bible','mixed') NOT NULL DEFAULT 'text',
                raw_input longtext NOT NULL COMMENT 'Original admin input (text, YouTube URL, Bible reference, etc.)',
                youtube_url varchar(500) DEFAULT NULL,
                youtube_transcript longtext DEFAULT NULL COMMENT 'Extracted YouTube transcript',
                ai_formatted_content longtext DEFAULT NULL COMMENT 'AI-processed formatted content',
                ai_model varchar(100) DEFAULT NULL COMMENT 'AI model used for generation',
                ai_generation_prompt text DEFAULT NULL COMMENT 'Custom prompt used for AI generation',
                admin_notes text DEFAULT NULL COMMENT 'Admin instructions for AI enhancement',
                max_points int(11) NOT NULL DEFAULT 100,
                status enum('draft','ai_processing','ai_ready','approved','published','archived') NOT NULL DEFAULT 'draft',
                approved_by bigint(20) unsigned DEFAULT NULL,
                approved_at datetime DEFAULT NULL,
                created_by bigint(20) unsigned NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY category_id (category_id),
                KEY status (status),
                CONSTRAINT fk_quiz_content_category FOREIGN KEY (category_id) REFERENCES $categories_table(id) ON DELETE CASCADE
            ) $charset_collate ENGINE=InnoDB");
        }
        
        // 3. Quiz Questions table (AI-generated, admin-reviewed)
        $questions_table = $wpdb->prefix . 'sp_quiz_questions';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$questions_table'");
        if (!$table_exists) {
            $wpdb->query("CREATE TABLE $questions_table (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                content_id bigint(20) unsigned NOT NULL,
                question_text text NOT NULL,
                question_type enum('multiple_choice','true_false') NOT NULL DEFAULT 'multiple_choice',
                options longtext NOT NULL COMMENT 'JSON array of option objects [{text, is_correct}]',
                correct_answer_index int(11) NOT NULL DEFAULT 0,
                explanation text DEFAULT NULL COMMENT 'Explanation shown after answering',
                difficulty enum('easy','medium','hard') DEFAULT 'medium',
                sort_order int(11) NOT NULL DEFAULT 0,
                is_active tinyint(1) DEFAULT 1,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY content_id (content_id),
                CONSTRAINT fk_quiz_questions_content FOREIGN KEY (content_id) REFERENCES $content_table(id) ON DELETE CASCADE
            ) $charset_collate ENGINE=InnoDB");
        }
        
        // 4. Quiz Attempts table (user quiz submissions)
        $attempts_table = $wpdb->prefix . 'sp_quiz_attempts';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$attempts_table'");
        if (!$table_exists) {
            $wpdb->query("CREATE TABLE $attempts_table (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                content_id bigint(20) unsigned NOT NULL,
                score int(11) NOT NULL DEFAULT 0 COMMENT 'Number of correct answers',
                total_questions int(11) NOT NULL DEFAULT 0,
                percentage decimal(5,2) NOT NULL DEFAULT 0.00,
                points_awarded int(11) NOT NULL DEFAULT 0,
                answers longtext DEFAULT NULL COMMENT 'JSON of user answers [{question_id, selected_index, is_correct}]',
                started_at datetime DEFAULT CURRENT_TIMESTAMP,
                completed_at datetime DEFAULT NULL,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY content_id (content_id),
                KEY user_content (user_id, content_id),
                CONSTRAINT fk_quiz_attempts_content FOREIGN KEY (content_id) REFERENCES $content_table(id) ON DELETE CASCADE
            ) $charset_collate ENGINE=InnoDB");
        }
        
        // 5. AI Generation Log table (track all AI calls)
        $ai_log_table = $wpdb->prefix . 'sp_quiz_ai_log';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$ai_log_table'");
        if (!$table_exists) {
            $wpdb->query("CREATE TABLE $ai_log_table (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                content_id bigint(20) unsigned NOT NULL,
                action enum('format_content','generate_questions','regenerate') NOT NULL,
                prompt_used longtext DEFAULT NULL,
                response longtext DEFAULT NULL,
                model_used varchar(100) DEFAULT NULL,
                tokens_used int(11) DEFAULT 0,
                status enum('success','error') NOT NULL DEFAULT 'success',
                error_message text DEFAULT NULL,
                created_by bigint(20) unsigned NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY content_id (content_id)
            ) $charset_collate ENGINE=InnoDB");
        }
        
        // Add default settings
        $default_settings = get_option('sp_quiz_settings', array());
        if (empty($default_settings)) {
            update_option('sp_quiz_settings', array(
                'openai_api_key' => '',
                'ai_model' => 'gpt-4o',
                'questions_per_quiz' => 50,
                'default_max_points' => 100,
                'passing_percentage' => 60,
                'enabled' => 1,
            ));
        }
        
        return true;
    }
    
    public function down() {
        global $wpdb;
        
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sp_quiz_ai_log");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sp_quiz_attempts");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sp_quiz_questions");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sp_quiz_content");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sp_quiz_categories");
        
        delete_option('sp_quiz_settings');
        
        return true;
    }
}
