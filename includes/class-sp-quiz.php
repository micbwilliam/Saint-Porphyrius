<?php
/**
 * Saint Porphyrius - Quiz System Handler
 * Manages categorized Christian quizzes with AI-generated content
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Quiz {
    
    private static $instance = null;
    private $categories_table;
    private $content_table;
    private $questions_table;
    private $attempts_table;
    private $ai_log_table;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->categories_table = $wpdb->prefix . 'sp_quiz_categories';
        $this->content_table    = $wpdb->prefix . 'sp_quiz_content';
        $this->questions_table  = $wpdb->prefix . 'sp_quiz_questions';
        $this->attempts_table   = $wpdb->prefix . 'sp_quiz_attempts';
        $this->ai_log_table     = $wpdb->prefix . 'sp_quiz_ai_log';
    }
    
    // =========================================================================
    // SETTINGS
    // =========================================================================
    
    /**
     * Get quiz system settings
     */
    public function get_settings() {
        $defaults = array(
            'openai_api_key'         => '',
            'ai_model'               => 'gpt-4o',
            'questions_per_quiz'     => 50,
            'questions_per_attempt'  => 10,
            'min_points_percentage'  => 50,
            'default_max_points'     => 100,
            'passing_percentage'     => 60,
            'enabled'                => 1,
        );
        $settings = get_option('sp_quiz_settings', array());
        return wp_parse_args($settings, $defaults);
    }
    
    /**
     * Update quiz system settings
     */
    public function update_settings($settings) {
        $current = $this->get_settings();
        $settings = wp_parse_args($settings, $current);
        
        $settings['openai_api_key']        = sanitize_text_field($settings['openai_api_key']);
        $settings['ai_model']              = sanitize_text_field($settings['ai_model']);
        $settings['questions_per_quiz']    = absint($settings['questions_per_quiz']);
        $settings['questions_per_attempt'] = max(5, absint($settings['questions_per_attempt']));
        $settings['min_points_percentage'] = max(0, min(100, absint($settings['min_points_percentage'])));
        $settings['default_max_points']    = absint($settings['default_max_points']);
        $settings['passing_percentage']    = absint($settings['passing_percentage']);
        $settings['enabled']               = !empty($settings['enabled']) ? 1 : 0;
        
        update_option('sp_quiz_settings', $settings);
        return $settings;
    }
    
    // =========================================================================
    // CATEGORIES
    // =========================================================================
    
    /**
     * Get all categories
     */
    public function get_categories($active_only = false) {
        global $wpdb;
        $where = $active_only ? "WHERE is_active = 1" : "";
        return $wpdb->get_results("SELECT * FROM {$this->categories_table} $where ORDER BY sort_order ASC, name_ar ASC");
    }
    
    /**
     * Get category by ID
     */
    public function get_category($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->categories_table} WHERE id = %d", $id
        ));
    }
    
    /**
     * Create category
     */
    public function create_category($data) {
        global $wpdb;
        
        if (empty($data['name_ar'])) {
            return new WP_Error('missing_field', 'اسم الفئة بالعربية مطلوب');
        }
        
        $result = $wpdb->insert($this->categories_table, array(
            'name_ar'        => sanitize_text_field($data['name_ar']),
            'name_en'        => sanitize_text_field($data['name_en'] ?? ''),
            'description_ar' => sanitize_textarea_field($data['description_ar'] ?? ''),
            'icon'           => sanitize_text_field($data['icon'] ?? '📖'),
            'color'          => sanitize_hex_color($data['color'] ?? '#3B82F6'),
            'sort_order'     => absint($data['sort_order'] ?? 0),
            'is_active'      => 1,
            'created_by'     => get_current_user_id(),
        ));
        
        return $result ? $wpdb->insert_id : new WP_Error('db_error', 'فشل في إنشاء الفئة');
    }
    
    /**
     * Update category
     */
    public function update_category($id, $data) {
        global $wpdb;
        
        $update = array();
        if (isset($data['name_ar']))        $update['name_ar']        = sanitize_text_field($data['name_ar']);
        if (isset($data['name_en']))        $update['name_en']        = sanitize_text_field($data['name_en']);
        if (isset($data['description_ar'])) $update['description_ar'] = sanitize_textarea_field($data['description_ar']);
        if (isset($data['icon']))           $update['icon']           = sanitize_text_field($data['icon']);
        if (isset($data['color']))          $update['color']          = sanitize_hex_color($data['color']);
        if (isset($data['sort_order']))     $update['sort_order']     = absint($data['sort_order']);
        if (isset($data['is_active']))      $update['is_active']      = !empty($data['is_active']) ? 1 : 0;
        
        if (empty($update)) return true;
        
        return $wpdb->update($this->categories_table, $update, array('id' => $id));
    }
    
    /**
     * Delete category
     */
    public function delete_category($id) {
        global $wpdb;
        return $wpdb->delete($this->categories_table, array('id' => $id));
    }
    
    // =========================================================================
    // CONTENT
    // =========================================================================
    
    /**
     * Get all content with optional filters
     */
    public function get_all_content($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'category_id' => null,
            'status'      => null,
            'limit'       => 50,
            'offset'      => 0,
        );
        $args = wp_parse_args($args, $defaults);
        
        $where = array("1=1");
        $params = array();
        
        if ($args['category_id']) {
            $where[] = "c.category_id = %d";
            $params[] = $args['category_id'];
        }
        if ($args['status']) {
            $where[] = "c.status = %s";
            $params[] = $args['status'];
        }
        
        $where_sql = implode(' AND ', $where);
        
        $sql = "SELECT c.*, cat.name_ar as category_name, cat.icon as category_icon, cat.color as category_color,
                       (SELECT COUNT(*) FROM {$this->questions_table} q WHERE q.content_id = c.id AND q.is_active = 1) as question_count,
                       (SELECT COUNT(DISTINCT a.user_id) FROM {$this->attempts_table} a WHERE a.content_id = c.id) as total_participants
                FROM {$this->content_table} c
                LEFT JOIN {$this->categories_table} cat ON c.category_id = cat.id
                WHERE $where_sql
                ORDER BY c.created_at DESC
                LIMIT %d OFFSET %d";
        
        $params[] = $args['limit'];
        $params[] = $args['offset'];
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
    
    /**
     * Get published content for users
     */
    public function get_published_content($category_id = null) {
        $args = array('status' => 'published');
        if ($category_id) $args['category_id'] = $category_id;
        return $this->get_all_content($args);
    }
    
    /**
     * Get content by ID with full details
     */
    public function get_content($id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, cat.name_ar as category_name, cat.icon as category_icon, cat.color as category_color,
                    (SELECT COUNT(*) FROM {$this->questions_table} q WHERE q.content_id = c.id AND q.is_active = 1) as question_count,
                    (SELECT COUNT(DISTINCT a.user_id) FROM {$this->attempts_table} a WHERE a.content_id = c.id) as total_participants
             FROM {$this->content_table} c
             LEFT JOIN {$this->categories_table} cat ON c.category_id = cat.id
             WHERE c.id = %d",
            $id
        ));
    }
    
    /**
     * Create content
     */
    public function create_content($data) {
        global $wpdb;
        
        $required = array('category_id', 'title_ar', 'content_type', 'raw_input');
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', sprintf('الحقل %s مطلوب', $field));
            }
        }
        
        $result = $wpdb->insert($this->content_table, array(
            'category_id'   => absint($data['category_id']),
            'title_ar'      => sanitize_text_field($data['title_ar']),
            'title_en'      => sanitize_text_field($data['title_en'] ?? ''),
            'content_type'  => sanitize_text_field($data['content_type']),
            'raw_input'     => wp_kses_post($data['raw_input']),
            'youtube_url'   => esc_url_raw($data['youtube_url'] ?? ''),
            'max_points'    => absint($data['max_points'] ?? $this->get_settings()['default_max_points']),
            'admin_notes'   => sanitize_textarea_field($data['admin_notes'] ?? ''),
            'status'        => 'draft',
            'created_by'    => get_current_user_id(),
        ));
        
        return $result ? $wpdb->insert_id : new WP_Error('db_error', 'فشل في إنشاء المحتوى');
    }
    
    /**
     * Update content
     */
    public function update_content($id, $data) {
        global $wpdb;
        
        $update = array();
        if (isset($data['category_id']))          $update['category_id']          = absint($data['category_id']);
        if (isset($data['title_ar']))              $update['title_ar']             = sanitize_text_field($data['title_ar']);
        if (isset($data['title_en']))              $update['title_en']             = sanitize_text_field($data['title_en']);
        if (isset($data['content_type']))          $update['content_type']         = sanitize_text_field($data['content_type']);
        if (isset($data['raw_input']))             $update['raw_input']            = wp_kses_post($data['raw_input']);
        if (isset($data['youtube_url']))           $update['youtube_url']          = esc_url_raw($data['youtube_url']);
        if (isset($data['youtube_transcript']))    $update['youtube_transcript']   = wp_kses_post($data['youtube_transcript']);
        if (isset($data['ai_formatted_content']))  $update['ai_formatted_content'] = wp_kses_post($data['ai_formatted_content']);
        if (isset($data['ai_model']))              $update['ai_model']             = sanitize_text_field($data['ai_model']);
        if (isset($data['ai_generation_prompt']))  $update['ai_generation_prompt'] = sanitize_textarea_field($data['ai_generation_prompt']);
        if (isset($data['admin_notes']))           $update['admin_notes']          = sanitize_textarea_field($data['admin_notes']);
        if (isset($data['max_points']))            $update['max_points']           = absint($data['max_points']);
        if (isset($data['status']))                $update['status']               = sanitize_text_field($data['status']);
        
        if (isset($data['status']) && $data['status'] === 'approved') {
            $update['approved_by'] = get_current_user_id();
            $update['approved_at'] = current_time('mysql');
        }
        
        if (empty($update)) return true;
        
        return $wpdb->update($this->content_table, $update, array('id' => $id));
    }
    
    /**
     * Delete content and related data
     */
    public function delete_content($id) {
        global $wpdb;
        // Cascading delete will handle questions, but clean up attempts too
        $wpdb->delete($this->attempts_table, array('content_id' => $id));
        $wpdb->delete($this->ai_log_table, array('content_id' => $id));
        return $wpdb->delete($this->content_table, array('id' => $id));
    }
    
    // =========================================================================
    // QUESTIONS
    // =========================================================================
    
    /**
     * Get questions for a content item
     */
    public function get_questions($content_id, $active_only = true) {
        global $wpdb;
        $where = $active_only ? "AND q.is_active = 1" : "";
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->questions_table} q 
             WHERE q.content_id = %d $where 
             ORDER BY q.sort_order ASC, q.id ASC",
            $content_id
        ));
    }
    
    /**
     * Get random questions for a quiz attempt
     * Selects a limited number of random active questions
     */
    public function get_random_questions($content_id, $limit = null) {
        global $wpdb;
        
        if ($limit === null) {
            $settings = $this->get_settings();
            $limit = $settings['questions_per_attempt'];
        }
        $limit = max(1, absint($limit));
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->questions_table} q 
             WHERE q.content_id = %d AND q.is_active = 1 
             ORDER BY RAND() 
             LIMIT %d",
            $content_id, $limit
        ));
    }
    
    /**
     * Get a single question
     */
    public function get_question($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->questions_table} WHERE id = %d", $id
        ));
    }
    
    /**
     * Save AI-generated questions (bulk insert)
     */
    public function save_questions($content_id, $questions_data) {
        return $this->save_questions_with_offset($content_id, $questions_data, 0);
    }
    
    /**
     * Save questions with a sort_order offset (for appending to existing questions)
     */
    public function save_questions_with_offset($content_id, $questions_data, $sort_offset = 0) {
        global $wpdb;
        
        $saved = 0;
        foreach ($questions_data as $index => $q) {
            $result = $wpdb->insert($this->questions_table, array(
                'content_id'           => $content_id,
                'question_text'        => sanitize_text_field($q['question']),
                'question_type'        => sanitize_text_field($q['type'] ?? 'multiple_choice'),
                'options'              => wp_json_encode($q['options'], JSON_UNESCAPED_UNICODE),
                'correct_answer_index' => absint($q['correct_answer_index'] ?? 0),
                'explanation'          => sanitize_text_field($q['explanation'] ?? ''),
                'difficulty'           => sanitize_text_field($q['difficulty'] ?? 'medium'),
                'sort_order'           => $sort_offset + $index + 1,
                'is_active'            => 1,
            ));
            if ($result) $saved++;
        }
        
        return $saved;
    }
    
    /**
     * Update a single question
     */
    public function update_question($id, $data) {
        global $wpdb;
        
        $update = array();
        if (isset($data['question_text']))        $update['question_text']        = sanitize_text_field($data['question_text']);
        if (isset($data['question_type']))        $update['question_type']        = sanitize_text_field($data['question_type']);
        if (isset($data['options']))              $update['options']              = wp_json_encode($data['options'], JSON_UNESCAPED_UNICODE);
        if (isset($data['correct_answer_index'])) $update['correct_answer_index'] = absint($data['correct_answer_index']);
        if (isset($data['explanation']))          $update['explanation']          = sanitize_text_field($data['explanation']);
        if (isset($data['difficulty']))           $update['difficulty']           = sanitize_text_field($data['difficulty']);
        if (isset($data['sort_order']))           $update['sort_order']           = absint($data['sort_order']);
        if (isset($data['is_active']))            $update['is_active']            = !empty($data['is_active']) ? 1 : 0;
        
        if (empty($update)) return true;
        
        return $wpdb->update($this->questions_table, $update, array('id' => $id));
    }
    
    /**
     * Delete all questions for a content item (for regeneration)
     */
    public function delete_questions($content_id) {
        global $wpdb;
        return $wpdb->delete($this->questions_table, array('content_id' => $content_id));
    }
    
    /**
     * Delete a single question
     */
    public function delete_question($id) {
        global $wpdb;
        return $wpdb->delete($this->questions_table, array('id' => $id));
    }
    
    // =========================================================================
    // QUIZ ATTEMPTS
    // =========================================================================
    
    /**
     * Submit a quiz attempt
     */
    public function submit_attempt($user_id, $content_id, $answers) {
        global $wpdb;
        
        $content = $this->get_content($content_id);
        if (!$content || $content->status !== 'published') {
            return new WP_Error('invalid_content', 'هذا الاختبار غير متاح');
        }
        
        // Only score the questions that were presented (by IDs in answers)
        $question_ids = array_keys($answers);
        if (empty($question_ids)) {
            return new WP_Error('no_answers', 'لم يتم تقديم أي إجابات');
        }
        
        // Fetch only the questions the user was presented
        $placeholders = implode(',', array_fill(0, count($question_ids), '%d'));
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->questions_table} 
             WHERE content_id = %d AND id IN ($placeholders) AND is_active = 1",
            array_merge(array($content_id), $question_ids)
        ));
        
        if (empty($questions)) {
            return new WP_Error('no_questions', 'لا توجد أسئلة في هذا الاختبار');
        }
        
        // Score the quiz
        $score = 0;
        $total = count($questions);
        $answer_details = array();
        
        foreach ($questions as $question) {
            $user_answer = isset($answers[$question->id]) ? intval($answers[$question->id]) : -1;
            $is_correct = ($user_answer === intval($question->correct_answer_index));
            
            if ($is_correct) $score++;
            
            $answer_details[] = array(
                'question_id'    => $question->id,
                'selected_index' => $user_answer,
                'correct_index'  => $question->correct_answer_index,
                'is_correct'     => $is_correct,
            );
        }
        
        $percentage = ($total > 0) ? round(($score / $total) * 100, 2) : 0;
        
        // Get settings for minimum points threshold
        $settings = $this->get_settings();
        $min_pct = $settings['min_points_percentage']; // e.g. 50%
        
        // Calculate points: proportional to score, capped at max_points
        // User only earns points if they score above the minimum percentage
        $max_points = $content->max_points;
        $earned_points = 0;
        $points_eligible = ($percentage >= $min_pct);
        
        if ($points_eligible) {
            $earned_points = round(($percentage / 100) * $max_points);
        }
        
        // Check previous best score to cap points
        $best_previous = $this->get_best_attempt($user_id, $content_id);
        $previous_best_points = $best_previous ? $best_previous->points_awarded : 0;
        
        // Only award additional points if this attempt scored higher
        $additional_points = max(0, $earned_points - $previous_best_points);
        
        // Record the attempt
        $wpdb->insert($this->attempts_table, array(
            'user_id'         => $user_id,
            'content_id'      => $content_id,
            'score'           => $score,
            'total_questions'  => $total,
            'percentage'      => $percentage,
            'points_awarded'  => $earned_points,
            'answers'         => wp_json_encode($answer_details, JSON_UNESCAPED_UNICODE),
            'started_at'      => current_time('mysql'),
            'completed_at'    => current_time('mysql'),
        ));
        
        // Award additional points via points system
        if ($additional_points > 0) {
            $points_handler = SP_Points::get_instance();
            $points_handler->add(
                $user_id,
                $additional_points,
                'reward',
                null,
                sprintf('اختبار: %s (نقاط إضافية)', $content->title_ar)
            );
        }
        
        return array(
            'success'              => true,
            'score'                => $score,
            'total'                => $total,
            'percentage'           => $percentage,
            'points_earned'        => $earned_points,
            'additional_points'    => $additional_points,
            'is_best'              => ($earned_points > $previous_best_points),
            'max_points'           => $max_points,
            'points_eligible'      => $points_eligible,
            'min_points_percentage'=> $min_pct,
            'answer_details'       => $answer_details,
        );
    }
    
    /**
     * Get user's best attempt for a content item
     */
    public function get_best_attempt($user_id, $content_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->attempts_table} 
             WHERE user_id = %d AND content_id = %d 
             ORDER BY points_awarded DESC, percentage DESC 
             LIMIT 1",
            $user_id, $content_id
        ));
    }
    
    /**
     * Get all attempts by user for a content item
     */
    public function get_user_attempts($user_id, $content_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->attempts_table} 
             WHERE user_id = %d AND content_id = %d 
             ORDER BY completed_at DESC",
            $user_id, $content_id
        ));
    }
    
    /**
     * Get user's total quiz points across all quizzes
     */
    public function get_user_total_quiz_points($user_id) {
        global $wpdb;
        
        // For each content, get the best attempt points
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(best.points_awarded), 0)
             FROM (
                 SELECT MAX(points_awarded) as points_awarded
                 FROM {$this->attempts_table}
                 WHERE user_id = %d
                 GROUP BY content_id
             ) best",
            $user_id
        ));
    }
    
    /**
     * Get attempt count for a content item
     */
    public function get_attempt_count($user_id, $content_id) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->attempts_table} WHERE user_id = %d AND content_id = %d",
            $user_id, $content_id
        ));
    }
    
    /**
     * Get quiz statistics for admin
     */
    public function get_quiz_stats() {
        global $wpdb;
        
        return array(
            'total_categories'  => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->categories_table}"),
            'total_content'     => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->content_table}"),
            'published_content' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->content_table} WHERE status = 'published'"),
            'pending_review'    => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->content_table} WHERE status = 'ai_ready'"),
            'total_questions'   => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->questions_table} WHERE is_active = 1"),
            'total_attempts'    => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->attempts_table}"),
            'total_participants'=> (int) $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$this->attempts_table}"),
        );
    }
    
    /**
     * Get leaderboard / participants for a specific content item
     * Returns each user's best attempt, ranked by points then percentage
     */
    public function get_content_leaderboard($content_id, $limit = 50) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT a.user_id, 
                    MAX(a.points_awarded) as best_points,
                    MAX(a.percentage) as best_percentage,
                    MAX(a.score) as best_score,
                    (SELECT a2.total_questions FROM {$this->attempts_table} a2 
                     WHERE a2.user_id = a.user_id AND a2.content_id = a.content_id 
                     ORDER BY a2.points_awarded DESC, a2.percentage DESC LIMIT 1) as total_questions,
                    COUNT(*) as attempt_count,
                    MIN(a.completed_at) as first_attempt,
                    MAX(a.completed_at) as last_attempt,
                    u.display_name
             FROM {$this->attempts_table} a
             JOIN {$wpdb->users} u ON a.user_id = u.ID
             WHERE a.content_id = %d 
             GROUP BY a.user_id, u.display_name
             ORDER BY best_points DESC, best_percentage DESC, attempt_count ASC
             LIMIT %d",
            $content_id, $limit
        ));
    }
    
    // =========================================================================
    // AI LOG
    // =========================================================================
    
    /**
     * Log an AI generation action
     */
    public function log_ai_action($content_id, $action, $prompt, $response, $model, $tokens = 0, $status = 'success', $error = '') {
        global $wpdb;
        
        return $wpdb->insert($this->ai_log_table, array(
            'content_id'    => $content_id,
            'action'        => $action,
            'prompt_used'   => $prompt,
            'response'      => is_array($response) ? wp_json_encode($response, JSON_UNESCAPED_UNICODE) : $response,
            'model_used'    => $model,
            'tokens_used'   => $tokens,
            'status'        => $status,
            'error_message' => $error,
            'created_by'    => get_current_user_id(),
        ));
    }
    
    /**
     * Get AI log for content
     */
    public function get_ai_log($content_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->ai_log_table} WHERE content_id = %d ORDER BY created_at DESC",
            $content_id
        ));
    }
}
