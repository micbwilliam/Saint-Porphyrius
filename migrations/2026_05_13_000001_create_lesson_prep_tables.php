<?php
/**
 * Migration: Create Lesson Preparation System Tables
 * Tables for lesson delivery, quiz generation, preparation workflow, and AI detection
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Migration_Create_Lesson_Prep_Tables {

    public function up() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // 1. Lessons table
        $lessons_table = $wpdb->prefix . 'sp_lessons';
        if (!$wpdb->get_var("SHOW TABLES LIKE '$lessons_table'")) {
            $wpdb->query("CREATE TABLE $lessons_table (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                title_ar varchar(500) NOT NULL,
                title_en varchar(500) DEFAULT '',
                description_ar text DEFAULT NULL,
                description_en text DEFAULT NULL,
                event_id bigint(20) unsigned DEFAULT NULL COMMENT 'Linked event',
                grades varchar(500) NOT NULL DEFAULT '[]' COMMENT 'JSON array of grade numbers [1-6]',
                pdf_urls longtext DEFAULT NULL COMMENT 'JSON object {grade: url} or single url keyed as \"all\"',
                pdf_text longtext DEFAULT NULL COMMENT 'Extracted text from PDF for AI processing',
                quiz_config longtext DEFAULT NULL COMMENT 'JSON: {num_questions, points, allow_retake, question_types}',
                prep_points_config longtext DEFAULT NULL COMMENT 'JSON: per-section point values',
                ai_detection_config longtext DEFAULT NULL COMMENT 'JSON: {threshold, penalty_type, penalty_amount}',
                status enum('draft','published','archived') NOT NULL DEFAULT 'draft',
                created_by bigint(20) unsigned NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY event_id (event_id),
                KEY status (status)
            ) $charset_collate ENGINE=InnoDB");
        }

        // 2. Lesson Access (grade-level whitelist)
        $access_table = $wpdb->prefix . 'sp_lesson_access';
        if (!$wpdb->get_var("SHOW TABLES LIKE '$access_table'")) {
            $wpdb->query("CREATE TABLE $access_table (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                lesson_id bigint(20) unsigned NOT NULL,
                grade int(11) NOT NULL COMMENT 'Grade 1-6',
                user_id bigint(20) unsigned NOT NULL COMMENT 'Eligible user',
                created_by bigint(20) unsigned NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY lesson_grade_user (lesson_id, grade, user_id),
                KEY lesson_id (lesson_id),
                KEY user_id (user_id),
                KEY grade (grade),
                CONSTRAINT fk_lesson_access_lesson FOREIGN KEY (lesson_id) REFERENCES $lessons_table(id) ON DELETE CASCADE
            ) $charset_collate ENGINE=InnoDB");
        }

        // 3. Lesson Quiz Questions (per-lesson quiz questions)
        $questions_table = $wpdb->prefix . 'sp_lesson_quiz_questions';
        if (!$wpdb->get_var("SHOW TABLES LIKE '$questions_table'")) {
            $wpdb->query("CREATE TABLE $questions_table (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                lesson_id bigint(20) unsigned NOT NULL,
                question_text text NOT NULL,
                question_type enum('multiple_choice','true_false','short_answer') NOT NULL DEFAULT 'multiple_choice',
                options longtext DEFAULT NULL COMMENT 'JSON array of option objects [{text, is_correct}]',
                correct_answer_index int(11) NOT NULL DEFAULT 0,
                explanation text DEFAULT NULL,
                difficulty enum('easy','medium','hard') DEFAULT 'medium',
                sort_order int(11) NOT NULL DEFAULT 0,
                is_active tinyint(1) DEFAULT 1,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY lesson_id (lesson_id),
                CONSTRAINT fk_lqq_lesson FOREIGN KEY (lesson_id) REFERENCES $lessons_table(id) ON DELETE CASCADE
            ) $charset_collate ENGINE=InnoDB");
        }

        // 4. Lesson Quiz Attempts
        $attempts_table = $wpdb->prefix . 'sp_lesson_quiz_attempts';
        if (!$wpdb->get_var("SHOW TABLES LIKE '$attempts_table'")) {
            $wpdb->query("CREATE TABLE $attempts_table (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                lesson_id bigint(20) unsigned NOT NULL,
                score int(11) NOT NULL DEFAULT 0,
                total_questions int(11) NOT NULL DEFAULT 0,
                percentage decimal(5,2) NOT NULL DEFAULT 0.00,
                points_awarded int(11) NOT NULL DEFAULT 0,
                answers longtext DEFAULT NULL COMMENT 'JSON of user answers',
                started_at datetime DEFAULT CURRENT_TIMESTAMP,
                completed_at datetime DEFAULT NULL,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY lesson_id (lesson_id),
                KEY user_lesson (user_id, lesson_id),
                CONSTRAINT fk_lqa_lesson FOREIGN KEY (lesson_id) REFERENCES $lessons_table(id) ON DELETE CASCADE
            ) $charset_collate ENGINE=InnoDB");
        }

        // 5. Lesson Preparations (the 7-step wizard submission)
        $preparations_table = $wpdb->prefix . 'sp_lesson_preparations';
        if (!$wpdb->get_var("SHOW TABLES LIKE '$preparations_table'")) {
            $wpdb->query("CREATE TABLE $preparations_table (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                lesson_id bigint(20) unsigned NOT NULL,
                event_id bigint(20) unsigned DEFAULT NULL,
                grade int(11) NOT NULL DEFAULT 0,
                -- Section 1: اسم الدرس
                section_lesson_name longtext DEFAULT NULL,
                section_lesson_name_notes longtext DEFAULT NULL,
                section_lesson_name_points int(11) NOT NULL DEFAULT 0,
                -- Section 2: الهدف
                section_objective longtext DEFAULT NULL,
                section_objective_notes longtext DEFAULT NULL,
                section_objective_points int(11) NOT NULL DEFAULT 0,
                -- Section 3: الآية
                section_verse_ayah longtext DEFAULT NULL,
                section_verse_ayah_notes longtext DEFAULT NULL,
                section_verse_ayah_points int(11) NOT NULL DEFAULT 0,
                -- Section 4: التدريب
                section_training_exercises longtext DEFAULT NULL,
                section_training_exercises_notes longtext DEFAULT NULL,
                section_training_exercises_points int(11) NOT NULL DEFAULT 0,
                -- Section 5: وسيلة الإيضاح
                section_explanation_means longtext DEFAULT NULL,
                section_explanation_means_notes longtext DEFAULT NULL,
                section_explanation_means_points int(11) NOT NULL DEFAULT 0,
                -- Section 6: مقدمة الدرس
                section_lesson_introduction longtext DEFAULT NULL,
                section_lesson_introduction_notes longtext DEFAULT NULL,
                section_lesson_introduction_points int(11) NOT NULL DEFAULT 0,
                -- Section 7: كتابة الدرس (with AI detection)
                section_lesson_writing longtext DEFAULT NULL,
                section_lesson_writing_notes longtext DEFAULT NULL,
                section_lesson_writing_points int(11) NOT NULL DEFAULT 0,
                ai_detection_score decimal(5,2) DEFAULT NULL COMMENT 'AI probability 0-100',
                ai_detection_is_likely_ai tinyint(1) DEFAULT 0,
                ai_detection_details longtext DEFAULT NULL COMMENT 'JSON with detection breakdown',
                ai_penalty_applied int(11) NOT NULL DEFAULT 0 COMMENT 'Points deducted for AI usage',
                -- Overall
                total_points_awarded int(11) NOT NULL DEFAULT 0,
                submission_count int(11) NOT NULL DEFAULT 0 COMMENT 'Number of times submitted',
                admin_notes text DEFAULT NULL,
                status enum('draft','submitted','under_review','approved','needs_revision') NOT NULL DEFAULT 'draft',
                submitted_at datetime DEFAULT NULL,
                reviewed_at datetime DEFAULT NULL,
                reviewed_by bigint(20) unsigned DEFAULT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY lesson_id (lesson_id),
                KEY status (status),
                KEY user_lesson (user_id, lesson_id),
                CONSTRAINT fk_lp_lesson FOREIGN KEY (lesson_id) REFERENCES $lessons_table(id) ON DELETE CASCADE
            ) $charset_collate ENGINE=InnoDB");
        }

        // 6. Lesson Preparation Config (admin global settings)
        $config_table = $wpdb->prefix . 'sp_lesson_prep_config';
        if (!$wpdb->get_var("SHOW TABLES LIKE '$config_table'")) {
            $wpdb->query("CREATE TABLE $config_table (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                config_key varchar(191) NOT NULL,
                config_value longtext DEFAULT NULL,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY config_key (config_key)
            ) $charset_collate ENGINE=InnoDB");

            // Insert default config rows
            $defaults = array(
                'section_points' => wp_json_encode(array(
                    'lesson_name'          => 10,
                    'objective'            => 10,
                    'verse_ayah'           => 10,
                    'training_exercises'   => 15,
                    'explanation_means'    => 10,
                    'lesson_introduction'  => 15,
                    'lesson_writing'       => 30,
                )),
                'ai_detection' => wp_json_encode(array(
                    'enabled'          => true,
                    'threshold'        => 70,
                    'penalty_type'     => 'percentage',
                    'penalty_amount'   => 50,
                    'show_to_user'     => false,
                )),
                'quiz_defaults' => wp_json_encode(array(
                    'num_questions'    => 10,
                    'points'           => 50,
                    'allow_retake'     => false,
                    'passing_percent'  => 60,
                )),
                'prep_required_quiz' => '1',
                'prep_max_submissions' => '3',
                'prep_enabled' => '1',
            );

            foreach ($defaults as $key => $value) {
                $wpdb->insert($config_table, array(
                    'config_key'   => $key,
                    'config_value' => $value,
                ));
            }
        }

        // 7. AI Detection Log (immutable audit trail)
        $ai_log_table = $wpdb->prefix . 'sp_lesson_ai_log';
        if (!$wpdb->get_var("SHOW TABLES LIKE '$ai_log_table'")) {
            $wpdb->query("CREATE TABLE $ai_log_table (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                preparation_id bigint(20) unsigned NOT NULL,
                lesson_id bigint(20) unsigned NOT NULL,
                user_id bigint(20) unsigned NOT NULL,
                action_type enum('quiz_generation','ai_detection','pdf_extraction') NOT NULL,
                prompt_sent longtext DEFAULT NULL,
                response_received longtext DEFAULT NULL,
                ai_model varchar(100) DEFAULT NULL,
                tokens_used int(11) DEFAULT 0,
                status enum('success','error') NOT NULL DEFAULT 'success',
                error_message text DEFAULT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY preparation_id (preparation_id),
                KEY lesson_id (lesson_id)
            ) $charset_collate ENGINE=InnoDB");
        }
    }

    public function down() {
        global $wpdb;

        $tables = array(
            'sp_lesson_ai_log',
            'sp_lesson_prep_config',
            'sp_lesson_preparations',
            'sp_lesson_quiz_attempts',
            'sp_lesson_quiz_questions',
            'sp_lesson_access',
            'sp_lessons',
        );

        foreach ($tables as $table) {
            $full_table = $wpdb->prefix . $table;
            $wpdb->query("DROP TABLE IF EXISTS $full_table");
        }
    }
}
