<?php
/**
 * Saint Porphyrius - Lesson Preparation System
 * Manages lesson delivery, AI quiz generation, preparation wizard, and admin review
 *
 * @package Saint_Porphyrius
 * @since 6.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Lesson_Prep {

    private static $instance = null;

    // Table names
    private $lessons_table;
    private $access_table;
    private $questions_table;
    private $attempts_table;
    private $preparations_table;
    private $config_table;
    private $ai_log_table;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->lessons_table       = $wpdb->prefix . 'sp_lessons';
        $this->access_table        = $wpdb->prefix . 'sp_lesson_access';
        $this->questions_table     = $wpdb->prefix . 'sp_lesson_quiz_questions';
        $this->attempts_table      = $wpdb->prefix . 'sp_lesson_quiz_attempts';
        $this->preparations_table  = $wpdb->prefix . 'sp_lesson_preparations';
        $this->config_table        = $wpdb->prefix . 'sp_lesson_prep_config';
        $this->ai_log_table        = $wpdb->prefix . 'sp_lesson_ai_log';
    }

    // =========================================================================
    // CONFIGURATION
    // =========================================================================

    /**
     * Get all lesson prep configuration
     */
    public function get_config() {
        global $wpdb;

        $rows = $wpdb->get_results("SELECT config_key, config_value FROM {$this->config_table}");
        $config = array();
        foreach ($rows as $row) {
            $decoded = json_decode($row->config_value, true);
            $config[$row->config_key] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $row->config_value;
        }

        // Ensure all defaults exist
        $defaults = array(
            'section_points' => array(
                'lesson_name'          => 10,
                'objective'            => 10,
                'verse_ayah'           => 10,
                'training_exercises'   => 15,
                'explanation_means'    => 10,
                'lesson_introduction'  => 15,
                'lesson_writing'       => 30,
            ),
            'ai_detection' => array(
                'enabled'          => true,
                'threshold'        => 70,
                'penalty_type'     => 'percentage',
                'penalty_amount'   => 50,
                'show_to_user'     => false,
            ),
            'quiz_defaults' => array(
                'num_questions'    => 10,
                'points'           => 50,
                'allow_retake'     => false,
                'passing_percent'  => 60,
            ),
            'prep_required_quiz'   => '1',
            'prep_max_submissions' => '3',
            'prep_enabled'         => '1',
        );

        return wp_parse_args($config, $defaults);
    }

    /**
     * Get a single config value
     */
    public function get_config_value($key) {
        $config = $this->get_config();
        return isset($config[$key]) ? $config[$key] : null;
    }

    /**
     * Update configuration
     */
    public function update_config($key, $value) {
        global $wpdb;

        $encoded = is_array($value) ? wp_json_encode($value) : (string) $value;

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->config_table} WHERE config_key = %s",
            $key
        ));

        if ($existing) {
            $wpdb->update(
                $this->config_table,
                array('config_value' => $encoded),
                array('config_key' => $key),
                array('%s'),
                array('%s')
            );
        } else {
            $wpdb->insert(
                $this->config_table,
                array('config_key' => $key, 'config_value' => $encoded),
                array('%s', '%s')
            );
        }

        return true;
    }

    // =========================================================================
    // LESSON CRUD
    // =========================================================================

    /**
     * Create a new lesson
     */
    public function create_lesson($data) {
        global $wpdb;

        $required = array('title_ar', 'event_id');
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', __('حقل مطلوب مفقود: ', 'saint-porphyrius') . $field);
            }
        }

        $grades = isset($data['grades']) ? $data['grades'] : array();
        if (is_string($grades)) {
            $grades = json_decode($grades, true) ?: array();
        }

        $insert_data = array(
            'title_ar'              => sanitize_text_field($data['title_ar']),
            'title_en'              => sanitize_text_field($data['title_en'] ?? ''),
            'description_ar'        => sanitize_textarea_field($data['description_ar'] ?? ''),
            'description_en'        => sanitize_textarea_field($data['description_en'] ?? ''),
            'event_id'              => absint($data['event_id']),
            'grades'                => wp_json_encode(array_map('absint', $grades)),
            'pdf_urls'              => isset($data['pdf_urls']) ? (is_string($data['pdf_urls']) ? $data['pdf_urls'] : wp_json_encode($data['pdf_urls'])) : null,
            'pdf_text'              => isset($data['pdf_text']) ? $data['pdf_text'] : null,
            'quiz_config'           => isset($data['quiz_config']) ? (is_string($data['quiz_config']) ? $data['quiz_config'] : wp_json_encode($data['quiz_config'])) : null,
            'prep_points_config'    => isset($data['prep_points_config']) ? (is_string($data['prep_points_config']) ? $data['prep_points_config'] : wp_json_encode($data['prep_points_config'])) : null,
            'ai_detection_config'   => isset($data['ai_detection_config']) ? (is_string($data['ai_detection_config']) ? $data['ai_detection_config'] : wp_json_encode($data['ai_detection_config'])) : null,
            'status'                => sanitize_text_field($data['status'] ?? 'draft'),
            'created_by'            => get_current_user_id(),
        );

        $formats = array('%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d');

        $result = $wpdb->insert($this->lessons_table, $insert_data, $formats);

        if ($result === false) {
            return new WP_Error('db_error', __('فشل في إنشاء الدرس', 'saint-porphyrius'));
        }

        $lesson_id = $wpdb->insert_id;

        // Process access list if provided
        if (!empty($data['access_users'])) {
            $this->set_lesson_access($lesson_id, $data['access_users']);
        }

        return $this->get_lesson($lesson_id);
    }

    /**
     * Update an existing lesson
     */
    public function update_lesson($lesson_id, $data) {
        global $wpdb;

        $existing = $this->get_lesson($lesson_id);
        if (!$existing) {
            return new WP_Error('not_found', __('الدرس غير موجود', 'saint-porphyrius'));
        }

        $update_data = array();
        $formats = array();

        $fields = array(
            'title_ar'              => '%s',
            'title_en'              => '%s',
            'description_ar'        => '%s',
            'description_en'        => '%s',
            'event_id'              => '%d',
            'status'                => '%s',
        );

        foreach ($fields as $field => $format) {
            if (isset($data[$field])) {
                $update_data[$field] = $format === '%d' ? absint($data[$field]) : sanitize_text_field($data[$field]);
                $formats[] = $format;
            }
        }

        // Handle JSON fields
        $json_fields = array('grades', 'pdf_urls', 'pdf_text', 'quiz_config', 'prep_points_config', 'ai_detection_config');
        foreach ($json_fields as $field) {
            if (isset($data[$field])) {
                $value = $data[$field];
                if ($field === 'grades' && is_array($value)) {
                    $value = wp_json_encode(array_map('absint', $value));
                } elseif ($field === 'pdf_text') {
                    // Leave pdf_text as-is (it's already text)
                } elseif (is_array($value) || is_object($value)) {
                    $value = wp_json_encode($value);
                }
                $update_data[$field] = $value;
                $formats[] = '%s';
            }
        }

        if (empty($update_data)) {
            return $existing;
        }

        $result = $wpdb->update(
            $this->lessons_table,
            $update_data,
            array('id' => $lesson_id),
            $formats,
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('فشل في تحديث الدرس', 'saint-porphyrius'));
        }

        // Update access if provided
        if (isset($data['access_users'])) {
            $this->set_lesson_access($lesson_id, $data['access_users']);
        }

        return $this->get_lesson($lesson_id);
    }

    /**
     * Get a single lesson by ID
     */
    public function get_lesson($lesson_id) {
        global $wpdb;

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT l.*, 
                    COALESCE(e.title_ar, '') as event_title_ar,
                    COALESCE(e.event_date, '') as event_date
             FROM {$this->lessons_table} l
             LEFT JOIN {$wpdb->prefix}sp_events e ON l.event_id = e.id
             WHERE l.id = %d",
            $lesson_id
        ));

        if (!$row) {
            return null;
        }

        return $this->format_lesson($row);
    }

    /**
     * Get all lessons with optional filters
     */
    public function get_lessons($args = array()) {
        global $wpdb;

        $defaults = array(
            'status'      => null,
            'event_id'    => null,
            'grade'       => null,
            'user_id'     => null,  // For access filtering
            'limit'       => 50,
            'offset'      => 0,
            'orderby'     => 'l.created_at',
            'order'       => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $params = array();
        $joins = '';

        if ($args['status']) {
            $where[] = 'l.status = %s';
            $params[] = $args['status'];
        }

        if ($args['event_id']) {
            $where[] = 'l.event_id = %d';
            $params[] = $args['event_id'];
        }

        // Filter by access for a specific user
        if ($args['user_id'] && $args['grade']) {
            $joins .= " INNER JOIN {$this->access_table} la ON l.id = la.lesson_id AND la.user_id = %d AND la.grade = %d";
            $params[] = $args['user_id'];
            $params[] = $args['grade'];
        } elseif ($args['user_id']) {
            $joins .= " INNER JOIN {$this->access_table} la ON l.id = la.lesson_id AND la.user_id = %d";
            $params[] = $args['user_id'];
        } elseif ($args['grade']) {
            $joins .= " INNER JOIN {$this->access_table} la ON l.id = la.lesson_id AND la.grade = %d";
            $params[] = $args['grade'];
        }

        $where_sql = implode(' AND ', $where);
        $orderby = sanitize_sql_orderby("{$args['orderby']} {$args['order']}") ?: 'l.created_at DESC';

        $sql = "SELECT l.*, 
                       COALESCE(e.title_ar, '') as event_title_ar,
                       COALESCE(e.event_date, '') as event_date
                FROM {$this->lessons_table} l
                LEFT JOIN {$wpdb->prefix}sp_events e ON l.event_id = e.id
                $joins
                WHERE $where_sql
                ORDER BY $orderby
                LIMIT %d OFFSET %d";

        $params[] = $args['limit'];
        $params[] = $args['offset'];

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $results = $wpdb->get_results($sql);

        return array_map(array($this, 'format_lesson'), $results);
    }

    /**
     * Delete a lesson
     */
    public function delete_lesson($lesson_id) {
        global $wpdb;

        $result = $wpdb->delete($this->lessons_table, array('id' => $lesson_id), array('%d'));

        if ($result === false) {
            return new WP_Error('db_error', __('فشل في حذف الدرس', 'saint-porphyrius'));
        }

        return true;
    }

    /**
     * Format a lesson row with decoded JSON fields
     */
    private function format_lesson($row) {
        if (!$row) return null;

        $row->grades = json_decode($row->grades, true) ?: array();
        $row->pdf_urls = json_decode($row->pdf_urls, true) ?: new stdClass();
        $row->quiz_config = json_decode($row->quiz_config, true) ?: array();
        $row->prep_points_config = json_decode($row->prep_points_config, true) ?: array();
        $row->ai_detection_config = json_decode($row->ai_detection_config, true) ?: array();
        $row->question_count = $this->get_question_count($row->id);
        $row->quiz_attempt_count = $this->get_quiz_attempt_count($row->id);
        $row->preparation_count = $this->get_preparation_count($row->id);

        return $row;
    }

    /**
     * Count published lessons
     */
    public function count_lessons($status = 'published') {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->lessons_table} WHERE status = %s",
            $status
        ));
    }

    // =========================================================================
    // ACCESS CONTROL
    // =========================================================================

    /**
     * Set lesson access (bulk assign users per grade)
     * $access_data: array of ['user_id' => X, 'grade' => Y] or ['user_ids' => [1,2,3], 'grade' => Y]
     */
    public function set_lesson_access($lesson_id, $access_data) {
        global $wpdb;

        // Clear existing access
        $wpdb->delete($this->access_table, array('lesson_id' => $lesson_id), array('%d'));

        $created_by = get_current_user_id();
        $inserted = 0;

        foreach ($access_data as $entry) {
            $grade = absint($entry['grade'] ?? 0);
            if ($grade < 1 || $grade > 6) continue;

            // Support both single user_id and array of user_ids
            $user_ids = isset($entry['user_ids']) ? $entry['user_ids'] : (isset($entry['user_id']) ? array($entry['user_id']) : array());

            foreach ($user_ids as $uid) {
                $uid = absint($uid);
                if (!$uid) continue;

                $wpdb->insert(
                    $this->access_table,
                    array(
                        'lesson_id'  => $lesson_id,
                        'grade'      => $grade,
                        'user_id'    => $uid,
                        'created_by' => $created_by,
                    ),
                    array('%d', '%d', '%d', '%d')
                );
                $inserted++;
            }
        }

        return $inserted;
    }

    /**
     * Get users with access to a lesson
     */
    public function get_lesson_access($lesson_id) {
        global $wpdb;

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT la.*, u.display_name, 
                    COALESCE(um.meta_value, '') as sp_name_ar,
                    COALESCE(um2.meta_value, '') as sp_church_name
             FROM {$this->access_table} la
             JOIN {$wpdb->users} u ON la.user_id = u.ID
             LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'sp_name_ar'
             LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'sp_church_name'
             WHERE la.lesson_id = %d
             ORDER BY la.grade ASC, um.meta_value ASC",
            $lesson_id
        ));

        return $rows;
    }

    /**
     * Check if a user has access to a lesson for a specific grade
     */
    public function user_has_access($user_id, $lesson_id, $grade = null) {
        global $wpdb;

        if (current_user_can('manage_options')) {
            return true;
        }

        $where = "lesson_id = %d AND user_id = %d";
        $params = array($lesson_id, $user_id);

        if ($grade) {
            $where .= " AND grade = %d";
            $params[] = $grade;
        }

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->access_table} WHERE $where",
            ...$params
        ));

        return $exists > 0;
    }

    /**
     * Get user's grade (from user meta sp_grade)
     */
    public function get_user_grade($user_id) {
        $grade = get_user_meta($user_id, 'sp_grade', true);
        return $grade ? absint($grade) : 0;
    }

    /**
     * Get lessons accessible by a user
     */
    public function get_user_lessons($user_id, $args = array()) {
        $grade = $this->get_user_grade($user_id);

        if (!$grade && !current_user_can('manage_options')) {
            return array();
        }

        $args['user_id'] = $user_id;
        if ($grade) {
            $args['grade'] = $grade;
        }
        $args['status'] = 'published';

        return $this->get_lessons($args);
    }

    // =========================================================================
    // QUIZ QUESTIONS
    // =========================================================================

    /**
     * Save quiz questions for a lesson (bulk insert/replace)
     */
    public function save_questions($lesson_id, $questions) {
        global $wpdb;

        // Delete existing questions for this lesson
        $wpdb->delete($this->questions_table, array('lesson_id' => $lesson_id), array('%d'));

        $inserted = 0;
        foreach ($questions as $index => $q) {
            $result = $wpdb->insert(
                $this->questions_table,
                array(
                    'lesson_id'             => $lesson_id,
                    'question_text'         => sanitize_text_field($q['question_text'] ?? ''),
                    'question_type'         => sanitize_text_field($q['question_type'] ?? 'multiple_choice'),
                    'options'               => isset($q['options']) ? (is_string($q['options']) ? $q['options'] : wp_json_encode($q['options'])) : '[]',
                    'correct_answer_index'  => absint($q['correct_answer_index'] ?? 0),
                    'explanation'           => sanitize_textarea_field($q['explanation'] ?? ''),
                    'difficulty'            => sanitize_text_field($q['difficulty'] ?? 'medium'),
                    'sort_order'            => $index,
                    'is_active'             => isset($q['is_active']) ? absint($q['is_active']) : 1,
                ),
                array('%d', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%d')
            );

            if ($result !== false) $inserted++;
        }

        return $inserted;
    }

    /**
     * Get questions for a lesson
     */
    public function get_questions($lesson_id, $active_only = true) {
        global $wpdb;

        $where = "lesson_id = %d";
        $params = array($lesson_id);

        if ($active_only) {
            $where .= " AND is_active = 1";
        }

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->questions_table} WHERE $where ORDER BY sort_order ASC",
            ...$params
        ));

        foreach ($results as $q) {
            $q->options = json_decode($q->options, true) ?: array();
        }

        return $results;
    }

    /**
     * Get a limited set of random questions for quiz taking
     */
    public function get_random_questions($lesson_id, $limit = null) {
        $lesson = $this->get_lesson($lesson_id);
        if (!$lesson) return array();

        $quiz_config = $lesson->quiz_config;
        $limit = $limit ?: ($quiz_config['num_questions'] ?? 10);

        $all_questions = $this->get_questions($lesson_id, true);

        if (count($all_questions) <= $limit) {
            return $all_questions;
        }

        // Randomize and limit
        shuffle($all_questions);
        return array_slice($all_questions, 0, $limit);
    }

    /**
     * Get question count for a lesson
     */
    public function get_question_count($lesson_id) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->questions_table} WHERE lesson_id = %d AND is_active = 1",
            $lesson_id
        ));
    }

    // =========================================================================
    // QUIZ ATTEMPTS
    // =========================================================================

    /**
     * Submit a quiz attempt
     */
    public function submit_quiz_attempt($lesson_id, $user_id, $answers) {
        $lesson = $this->get_lesson($lesson_id);
        if (!$lesson) {
            return new WP_Error('not_found', __('الدرس غير موجود', 'saint-porphyrius'));
        }

        $quiz_config = $lesson->quiz_config;

        // Check retake policy
        $allow_retake = $quiz_config['allow_retake'] ?? false;
        if (!$allow_retake && $this->has_completed_quiz($user_id, $lesson_id)) {
            return new WP_Error('already_completed', __('لقد أكملت هذا الاختبار من قبل', 'saint-porphyrius'));
        }

        // Get questions (must be the same ones shown to user)
        $questions = $this->get_questions($lesson_id, true);
        $question_map = array();
        foreach ($questions as $q) {
            $question_map[$q->id] = $q;
        }

        // Score the answers
        $correct = 0;
        $total = 0;
        $detailed_answers = array();

        foreach ($answers as $qid => $selected_index) {
            $qid = absint($qid);
            if (!isset($question_map[$qid])) continue;

            $q = $question_map[$qid];
            $total++;
            $is_correct = (absint($selected_index) === absint($q->correct_answer_index));

            if ($is_correct) $correct++;

            $detailed_answers[] = array(
                'question_id'     => $qid,
                'selected_index'  => absint($selected_index),
                'is_correct'      => $is_correct,
                'correct_index'   => absint($q->correct_answer_index),
            );
        }

        $percentage = $total > 0 ? round(($correct / $total) * 100, 2) : 0;

        // Calculate points
        $max_points = $quiz_config['points'] ?? 50;
        $passing_percent = $quiz_config['passing_percent'] ?? 60;
        $passed = $percentage >= $passing_percent;
        $points_awarded = $passed ? $max_points : 0;

        // Save attempt
        global $wpdb;
        $result = $wpdb->insert(
            $this->attempts_table,
            array(
                'user_id'         => $user_id,
                'lesson_id'       => $lesson_id,
                'score'           => $correct,
                'total_questions' => $total,
                'percentage'      => $percentage,
                'points_awarded'  => $points_awarded,
                'answers'         => wp_json_encode($detailed_answers),
                'completed_at'    => current_time('mysql'),
            ),
            array('%d', '%d', '%d', '%d', '%s', '%d', '%s', '%s')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('فشل في حفظ نتيجة الاختبار', 'saint-porphyrius'));
        }

        $attempt_id = $wpdb->insert_id;

        // Award points if passed
        if ($points_awarded > 0) {
            $points_handler = SP_Points::get_instance();
            $points_handler->add(
                $user_id,
                $points_awarded,
                'reward',
                $lesson->event_id,
                sprintf(__('إكمال اختبار الدرس: %s', 'saint-porphyrius'), $lesson->title_ar)
            );
        }

        return array(
            'attempt_id'      => $attempt_id,
            'correct'         => $correct,
            'total'           => $total,
            'percentage'      => $percentage,
            'passed'          => $passed,
            'points_awarded'  => $points_awarded,
            'answers'         => $detailed_answers,
        );
    }

    /**
     * Check if user has completed a quiz
     */
    public function has_completed_quiz($user_id, $lesson_id) {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->attempts_table} WHERE user_id = %d AND lesson_id = %d",
            $user_id, $lesson_id
        ));
    }

    /**
     * Get best quiz attempt for a user
     */
    public function get_best_attempt($user_id, $lesson_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->attempts_table} WHERE user_id = %d AND lesson_id = %d ORDER BY percentage DESC LIMIT 1",
            $user_id, $lesson_id
        ));
    }

    /**
     * Get quiz attempt count for a lesson
     */
    public function get_quiz_attempt_count($lesson_id) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->attempts_table} WHERE lesson_id = %d",
            $lesson_id
        ));
    }

    // =========================================================================
    // LESSON PREPARATION (7-step wizard)
    // =========================================================================

    /**
     * Save a draft or submit a preparation
     */
    public function save_preparation($data) {
        global $wpdb;

        $lesson_id = absint($data['lesson_id'] ?? 0);
        $user_id = absint($data['user_id'] ?? get_current_user_id());

        if (!$lesson_id || !$user_id) {
            return new WP_Error('missing_data', __('بيانات غير مكتملة', 'saint-porphyrius'));
        }

        $lesson = $this->get_lesson($lesson_id);
        if (!$lesson) {
            return new WP_Error('not_found', __('الدرس غير موجود', 'saint-porphyrius'));
        }

        // Check access
        if (!current_user_can('manage_options') && !$this->user_has_access($user_id, $lesson_id)) {
            return new WP_Error('access_denied', __('ليس لديك صلاحية الوصول لهذا الدرس', 'saint-porphyrius'));
        }

        $grade = absint($data['grade'] ?? $this->get_user_grade($user_id));
        $is_submit = !empty($data['submit']);

        // Get points config
        $points_config = $lesson->prep_points_config ?: $this->get_config_value('section_points');

        // Build preparation data
        $sections = array(
            'lesson_name'         => 'section_lesson_name',
            'objective'           => 'section_objective',
            'verse_ayah'          => 'section_verse_ayah',
            'training_exercises'  => 'section_training_exercises',
            'explanation_means'   => 'section_explanation_means',
            'lesson_introduction' => 'section_lesson_introduction',
            'lesson_writing'      => 'section_lesson_writing',
        );

        $prep_data = array(
            'user_id'  => $user_id,
            'lesson_id'=> $lesson_id,
            'event_id' => $lesson->event_id,
            'grade'    => $grade,
        );
        $formats = array('%d', '%d', '%d', '%d');

        $total_points = 0;

        foreach ($sections as $config_key => $db_field) {
            $content = isset($data[$db_field]) ? wp_kses_post($data[$db_field]) : '';
            $notes   = isset($data[$db_field . '_notes']) ? wp_kses_post($data[$db_field . '_notes']) : '';
            $points  = isset($points_config[$config_key]) ? absint($points_config[$config_key]) : 0;

            $prep_data[$db_field] = $content;
            $prep_data[$db_field . '_notes'] = $notes;
            $prep_data[$db_field . '_points'] = $points;
            $formats = array_merge($formats, array('%s', '%s', '%d'));

            $total_points += $points;
        }

        // Check for existing draft
        $existing_id = absint($data['id'] ?? 0);
        if ($existing_id) {
            $existing = $this->get_preparation($existing_id);
            if (!$existing || $existing->user_id != $user_id) {
                return new WP_Error('invalid_prep', __('تحضير غير صالح', 'saint-porphyrius'));
            }
        }

        if ($is_submit) {
            // Run AI detection on lesson_writing section
            $writing_content = $prep_data['section_lesson_writing'] ?? '';
            if (!empty($writing_content)) {
                $detection_result = $this->run_ai_detection($lesson_id, $user_id, $writing_content);

                if (!is_wp_error($detection_result)) {
                    $prep_data['ai_detection_score']       = $detection_result['score'];
                    $prep_data['ai_detection_is_likely_ai'] = $detection_result['is_likely_ai'] ? 1 : 0;
                    $prep_data['ai_detection_details']      = wp_json_encode($detection_result['details']);

                    // Apply penalty if AI detected
                    if ($detection_result['is_likely_ai']) {
                        $ai_config = $lesson->ai_detection_config ?: $this->get_config_value('ai_detection');
                        $penalty_type = $ai_config['penalty_type'] ?? 'percentage';
                        $penalty_amount = $ai_config['penalty_amount'] ?? 50;
                        $writing_points = $prep_data['section_lesson_writing_points'] ?? 0;

                        if ($penalty_type === 'percentage') {
                            $penalty = round($writing_points * ($penalty_amount / 100));
                        } else {
                            $penalty = min($penalty_amount, $writing_points);
                        }

                        $prep_data['ai_penalty_applied'] = $penalty;
                        $total_points -= $penalty;
                    }
                }
                $formats = array_merge($formats, array('%s', '%d', '%s', '%d'));
            } else {
                $formats = array_merge($formats, array('%s', '%d', '%s', '%d'));
            }

            $prep_data['total_points_awarded'] = max(0, $total_points);
            $prep_data['status'] = 'submitted';
            $prep_data['submitted_at'] = current_time('mysql');
            $formats = array_merge($formats, array('%d', '%s', '%s'));
        } else {
            // Draft
            $formats = array_merge($formats, array('%s', '%d', '%s', '%d'));
            $prep_data['total_points_awarded'] = $total_points;
            $prep_data['status'] = 'draft';
            $formats = array_merge($formats, array('%d', '%s'));
        }

        if ($existing_id) {
            $wpdb->update(
                $this->preparations_table,
                $prep_data,
                array('id' => $existing_id),
                $formats,
                array('%d')
            );
            $prep_id = $existing_id;
        } else {
            $wpdb->insert($this->preparations_table, $prep_data, $formats);
            $prep_id = $wpdb->insert_id;
        }

        if (!$prep_id) {
            return new WP_Error('db_error', __('فشل في حفظ التحضير', 'saint-porphyrius'));
        }

        return $this->get_preparation($prep_id);
    }

    /**
     * Get a single preparation
     */
    public function get_preparation($prep_id) {
        global $wpdb;

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, u.display_name,
                    COALESCE(um.meta_value, '') as user_name_ar,
                    COALESCE(um2.meta_value, '') as user_church,
                    l.title_ar as lesson_title_ar,
                    l.title_en as lesson_title_en,
                    COALESCE(e.title_ar, '') as event_title_ar
             FROM {$this->preparations_table} p
             JOIN {$wpdb->users} u ON p.user_id = u.ID
             LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'sp_name_ar'
             LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'sp_church_name'
             JOIN {$this->lessons_table} l ON p.lesson_id = l.id
             LEFT JOIN {$wpdb->prefix}sp_events e ON p.event_id = e.id
             WHERE p.id = %d",
            $prep_id
        ));

        if (!$row) return null;

        $row->ai_detection_details = json_decode($row->ai_detection_details, true) ?: array();

        return $row;
    }

    /**
     * Get preparations with filters
     */
    public function get_preparations($args = array()) {
        global $wpdb;

        $defaults = array(
            'status'     => null,
            'user_id'    => null,
            'lesson_id'  => null,
            'event_id'   => null,
            'limit'      => 50,
            'offset'     => 0,
            'orderby'    => 'p.updated_at',
            'order'      => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $params = array();

        if ($args['status']) {
            $where[] = 'p.status = %s';
            $params[] = $args['status'];
        }

        if ($args['user_id']) {
            $where[] = 'p.user_id = %d';
            $params[] = $args['user_id'];
        }

        if ($args['lesson_id']) {
            $where[] = 'p.lesson_id = %d';
            $params[] = $args['lesson_id'];
        }

        if ($args['event_id']) {
            $where[] = 'p.event_id = %d';
            $params[] = $args['event_id'];
        }

        $where_sql = implode(' AND ', $where);
        $orderby = sanitize_sql_orderby("{$args['orderby']} {$args['order']}") ?: 'p.updated_at DESC';

        $sql = "SELECT p.*, u.display_name,
                       COALESCE(um.meta_value, '') as user_name_ar,
                       COALESCE(um2.meta_value, '') as user_church,
                       l.title_ar as lesson_title_ar,
                       l.title_en as lesson_title_en
                FROM {$this->preparations_table} p
                JOIN {$wpdb->users} u ON p.user_id = u.ID
                LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'sp_name_ar'
                LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'sp_church_name'
                JOIN {$this->lessons_table} l ON p.lesson_id = l.id
                WHERE $where_sql
                ORDER BY $orderby
                LIMIT %d OFFSET %d";

        $params[] = $args['limit'];
        $params[] = $args['offset'];

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $results = $wpdb->get_results($sql);

        foreach ($results as $row) {
            $row->ai_detection_details = json_decode($row->ai_detection_details, true) ?: array();
        }

        return $results;
    }

    /**
     * Get preparation count
     */
    public function get_preparation_count($lesson_id, $status = null) {
        global $wpdb;

        $where = "lesson_id = %d";
        $params = array($lesson_id);

        if ($status) {
            $where .= " AND status = %s";
            $params[] = $status;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->preparations_table} WHERE $where",
            ...$params
        ));
    }

    /**
     * Review a preparation (approve or request revision)
     */
    public function review_preparation($prep_id, $action, $data = array()) {
        $prep = $this->get_preparation($prep_id);
        if (!$prep) {
            return new WP_Error('not_found', __('التحضير غير موجود', 'saint-porphyrius'));
        }

        if ($prep->status !== 'submitted' && $prep->status !== 'under_review') {
            return new WP_Error('invalid_status', __('التحضير ليس في حالة قابلة للمراجعة', 'saint-porphyrius'));
        }

        global $wpdb;
        $user_id = get_current_user_id();
        $update_data = array(
            'reviewed_by' => $user_id,
            'reviewed_at' => current_time('mysql'),
        );
        $formats = array('%d', '%s');

        switch ($action) {
            case 'approve':
                // Apply per-section point adjustments if provided
                $total_points = $prep->total_points_awarded;
                $sections = array(
                    'lesson_name', 'objective', 'verse_ayah', 'training_exercises',
                    'explanation_means', 'lesson_introduction', 'lesson_writing'
                );

                foreach ($sections as $section) {
                    $field = "section_{$section}_points";
                    if (isset($data[$field])) {
                        $new_points = absint($data[$field]);
                        $update_data[$field] = max(0, $new_points);
                        $formats[] = '%d';
                        // Recalculate total
                    }
                }

                // Recalculate total from individual sections
                if (!empty($data)) {
                    $recalc_total = 0;
                    foreach ($sections as $section) {
                        $field = "section_{$section}_points";
                        $recalc_total += isset($update_data[$field])
                            ? $update_data[$field]
                            : absint($prep->{$field});
                    }
                    $update_data['total_points_awarded'] = max(0, $recalc_total - absint($prep->ai_penalty_applied));
                    $formats[] = '%d';
                }

                if (isset($data['admin_notes'])) {
                    $update_data['admin_notes'] = sanitize_textarea_field($data['admin_notes']);
                    $formats[] = '%s';
                }

                $update_data['status'] = 'approved';
                $formats[] = '%s';

                // Award points to user
                $points_to_award = $update_data['total_points_awarded'] ?? $prep->total_points_awarded;
                if ($points_to_award > 0) {
                    $points_handler = SP_Points::get_instance();
                    $points_handler->add(
                        $prep->user_id,
                        $points_to_award,
                        'reward',
                        $prep->event_id,
                        sprintf(__('نقاط تحضير الدرس: %s (الصف %d)', 'saint-porphyrius'), $prep->lesson_title_ar, $prep->grade)
                    );
                }
                break;

            case 'needs_revision':
                $update_data['status'] = 'needs_revision';
                $formats[] = '%s';
                if (isset($data['admin_notes'])) {
                    $update_data['admin_notes'] = sanitize_textarea_field($data['admin_notes']);
                    $formats[] = '%s';
                }
                break;

            case 'under_review':
                $update_data['status'] = 'under_review';
                $formats[] = '%s';
                break;

            default:
                return new WP_Error('invalid_action', __('إجراء غير صالح', 'saint-porphyrius'));
        }

        $result = $wpdb->update(
            $this->preparations_table,
            $update_data,
            array('id' => $prep_id),
            $formats,
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('فشل في تحديث حالة المراجعة', 'saint-porphyrius'));
        }

        return $this->get_preparation($prep_id);
    }

    // =========================================================================
    // AI DETECTION
    // =========================================================================

    /**
     * Run AI content detection on lesson writing text
     */
    public function run_ai_detection($lesson_id, $user_id, $text) {
        // Get detection config
        $lesson = $this->get_lesson($lesson_id);
        $ai_config = $lesson->ai_detection_config ?: $this->get_config_value('ai_detection');

        if (empty($ai_config['enabled'])) {
            return array(
                'score'        => 0,
                'is_likely_ai' => false,
                'details'      => array('message' => 'AI detection disabled'),
            );
        }

        // Use existing OpenAI API infrastructure via SP_Quiz_AI
        if (!class_exists('SP_Quiz_AI')) {
            return $this->heuristic_ai_detection($text, $ai_config);
        }

        $quiz_ai = SP_Quiz_AI::get_instance();

        $system_prompt = "أنت خبير في اكتشاف النصوص المكتوبة بواسطة الذكاء الاصطناعي. مهمتك هي تحليل النص العربي المقدم وتحديد ما إذا كان مكتوباً بواسطة إنسان أم بواسطة ذكاء اصطناعي.

حلل النص بناءً على المعايير التالية:

1. **الأسلوب الشخصي**: هل يحتوي النص على أسلوب شخصي، تجارب ذاتية، أو تعبيرات إنسانية فريدة؟
2. **التنوع اللغوي**: هل هناك تنوع في طول الجمل وتركيبها؟ النصوص البشرية تميل إلى التنوع.
3. **العمق والتفاصيل**: هل هناك أمثلة محددة، تفاصيل دقيقة، أو إشارات لمواقف حقيقية؟
4. **الترابط المنطقي**: هل الأفكار مترابطة بشكل طبيعي أم تبدو مصطنعة؟
5. **التكرار والنمطية**: هل هناك تكرار غير طبيعي للكلمات أو التراكيب؟
6. **المصطلحات الكنسية**: هل استخدام المصطلحات الكنسية والقبطية طبيعي ومناسب للسياق؟

أعد النتيجة بصيغة JSON التالية:
{
    \"ai_probability\": عدد من 0 إلى 100,
    \"is_likely_ai\": true أو false,
    \"confidence\": \"high\", \"medium\", أو \"low\",
    \"indicators\": [\"مؤشر 1\", \"مؤشر 2\", ...],
    \"reasoning\": \"تحليل مختصر للنتيجة\"
}";

        $user_prompt = "قم بتحليل النص العربي التالي لتحديد ما إذا كان مكتوباً بواسطة إنسان أم ذكاء اصطناعي:\n\n" . $text;

        $messages = array(
            array('role' => 'system', 'content' => $system_prompt),
            array('role' => 'user', 'content' => $user_prompt),
        );

        // Call the OpenAI API directly (now public)
        $result = $quiz_ai->call_api($messages, 2000, 0.1);

        if (is_wp_error($result)) {
            // Fall back to heuristics
            $heuristic = $this->heuristic_ai_detection($text, $ai_config);
            $heuristic['details']['ai_error'] = $result->get_error_message();
            $this->log_ai_action(null, $lesson_id, $user_id, 'ai_detection', $user_prompt, '', 'error', $result->get_error_message());
            return $heuristic;
        }

        $detection_data = $result['data'];
        $score = floatval($detection_data['ai_probability'] ?? 50);
        $threshold = floatval($ai_config['threshold'] ?? 70);
        $is_likely_ai = $score >= $threshold;

        $details = array(
            'ai_probability' => $score,
            'confidence'     => $detection_data['confidence'] ?? 'medium',
            'indicators'     => $detection_data['indicators'] ?? array(),
            'reasoning'      => $detection_data['reasoning'] ?? '',
            'threshold_used' => $threshold,
            'method'         => 'llm_judge',
        );

        // Add heuristic signals as supplement
        $heuristic = $this->heuristic_ai_detection($text, $ai_config);
        $details['heuristic_signals'] = $heuristic['details'];

        // Log the action
        $this->log_ai_action(null, $lesson_id, $user_id, 'ai_detection', $user_prompt, $detection_data, 'success', '', $result['tokens']);

        return array(
            'score'        => $score,
            'is_likely_ai' => $is_likely_ai,
            'details'      => $details,
        );
    }

    /**
     * Heuristic AI detection (no API call needed)
     */
    private function heuristic_ai_detection($text, $config = array()) {
        $signals = array();
        $score = 0;

        // 1. Check for overly uniform sentence length
        $sentences = preg_split('/[.!?۔؟\n]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (count($sentences) > 3) {
            $lengths = array_map('mb_strlen', $sentences);
            $avg = array_sum($lengths) / count($lengths);
            $variance = 0;
            foreach ($lengths as $len) {
                $variance += pow($len - $avg, 2);
            }
            $variance /= count($lengths);
            $cv = $avg > 0 ? sqrt($variance) / $avg : 0;

            if ($cv < 0.2) {
                $signals[] = __('طول الجمل متجانس جداً (نمط آلي)', 'saint-porphyrius');
                $score += 20;
            }
        }

        // 2. Check for generic AI phrases in Arabic
        $ai_patterns = array(
            'من المهم أن نلاحظ',
            'في الختام',
            'مما سبق نستنتج',
            'تجدر الإشارة إلى',
            'لا شك أن',
            'مما لا شك فيه',
            'بناءً على ما تقدم',
            'يتضح لنا مما سبق',
        );
        $pattern_count = 0;
        foreach ($ai_patterns as $pattern) {
            $pattern_count += mb_substr_count($text, $pattern);
        }
        if ($pattern_count >= 2) {
            $signals[] = sprintf(__('تم اكتشاف %d عبارات نمطية شائعة في نصوص AI', 'saint-porphyrius'), $pattern_count);
            $score += 15 * min($pattern_count, 3);
        }

        // 3. Check word repetition (burstiness)
        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (count($words) > 20) {
            $word_counts = array_count_values($words);
            arsort($word_counts);
            $top_word_ratio = reset($word_counts) / count($words);
            if ($top_word_ratio > 0.1) {
                $signals[] = __('تكرار غير طبيعي لكلمة واحدة', 'saint-porphyrius');
                $score += 15;
            }
        }

        // 4. Very short or very long text without structure
        $text_length = mb_strlen($text);
        if ($text_length < 50) {
            $signals[] = __('نص قصير جداً - غير كافٍ للتحليل', 'saint-porphyrius');
        }

        // 5. Lack of first-person or personal references
        $personal_markers = array('أنا', 'نحن', 'شخصياً', 'في كنيستنا', 'مع أولادنا', 'خبرتي');
        $has_personal = false;
        foreach ($personal_markers as $marker) {
            if (mb_strpos($text, $marker) !== false) {
                $has_personal = true;
                break;
            }
        }
        if (!$has_personal && $text_length > 100) {
            $signals[] = __('غياب الأسلوب الشخصي والتجارب الذاتية', 'saint-porphyrius');
            $score += 20;
        }

        $threshold = floatval($config['threshold'] ?? 70);
        $is_likely_ai = $score >= $threshold;

        return array(
            'score'        => min($score, 100),
            'is_likely_ai' => $is_likely_ai,
            'details'      => array(
                'signals'        => $signals,
                'heuristic_score'=> min($score, 100),
                'text_length'    => $text_length,
                'sentence_count' => count($sentences),
                'method'         => 'heuristic',
            ),
        );
    }

    // =========================================================================
    // AI QUIZ GENERATION
    // =========================================================================

    /**
     * Generate quiz questions from lesson PDF text using AI
     */
    public function generate_quiz_questions($lesson_id, $num_questions = 10, $admin_instructions = '') {
        $lesson = $this->get_lesson($lesson_id);
        if (!$lesson) {
            return new WP_Error('not_found', __('الدرس غير موجود', 'saint-porphyrius'));
        }

        $source_text = $lesson->pdf_text;
        if (empty($source_text)) {
            return new WP_Error('no_text', __('لا يوجد نص مستخرج من PDF. يرجى رفع ملف PDF أولاً.', 'saint-porphyrius'));
        }

        if (!class_exists('SP_Quiz_AI')) {
            return new WP_Error('no_ai', __('خدمة AI غير متاحة', 'saint-porphyrius'));
        }

        $quiz_ai = SP_Quiz_AI::get_instance();

        $system_prompt = "أنت خبير في إنشاء اختبارات تعليمية مسيحية للأطفال (الصفوف 1-6). مهمتك هي إنشاء أسئلة اختبار بناءً على محتوى الدرس المقدم.

القواعد:
1. أنشئ الأسئلة بناءً على المحتوى المقدم فقط
2. كل سؤال يجب أن يكون مناسباً لعمر الأطفال في المرحلة الابتدائية
3. نوع بين مستويات الصعوبة (سهل 40%، متوسط 40%، صعب 20%)
4. كل سؤال اختيار من متعدد يجب أن يحتوي على 4 خيارات
5. الخيارات الخاطئة يجب أن تكون معقولة للأطفال
6. أضف شرحاً مبسطاً لكل إجابة صحيحة
7. استخدم لغة عربية واضحة ومناسبة للأطفال
8. رتب الأسئلة من الأسهل إلى الأصعب

أنواع الأسئلة المسموحة:
- multiple_choice: اختيار من متعدد (4 خيارات)
- true_false: صح أو خطأ

أعد النتيجة بصيغة JSON:
{
    \"questions\": [
        {
            \"question_text\": \"نص السؤال\",
            \"question_type\": \"multiple_choice\",
            \"options\": [
                {\"text\": \"الخيار الأول\", \"is_correct\": false},
                {\"text\": \"الخيار الثاني\", \"is_correct\": true},
                {\"text\": \"الخيار الثالث\", \"is_correct\": false},
                {\"text\": \"الخيار الرابع\", \"is_correct\": false}
            ],
            \"correct_answer_index\": 1,
            \"explanation\": \"شرح مبسط للإجابة الصحيحة\",
            \"difficulty\": \"easy\"
        }
    ]
}";

        $user_prompt = "قم بإنشاء {$num_questions} سؤال اختبار من محتوى الدرس التالي:\n\n";
        $user_prompt .= "عنوان الدرس: " . $lesson->title_ar . "\n\n";
        $user_prompt .= "محتوى الدرس:\n" . $source_text;

        if ($admin_instructions) {
            $user_prompt .= "\n\n--- تعليمات إضافية ---\n" . $admin_instructions;
        }

        // Truncate if too long (model context limits)
        if (mb_strlen($user_prompt) > 8000) {
            $user_prompt = mb_substr($user_prompt, 0, 8000) . "\n\n[النص مقصوص للطول]";
        }

        $messages = array(
            array('role' => 'system', 'content' => $system_prompt),
            array('role' => 'user', 'content' => $user_prompt),
        );

        // Call the OpenAI API directly (now public)
        $result = $quiz_ai->call_api($messages, 8000, 0.3);

        if (is_wp_error($result)) {
            $this->log_ai_action(null, $lesson_id, 0, 'quiz_generation', $user_prompt, '', 'error', $result->get_error_message());
            return $result;
        }

        $questions = $result['data']['questions'] ?? array();

        // Log the generation
        $this->log_ai_action(null, $lesson_id, 0, 'quiz_generation', $user_prompt, $questions, 'success', '', $result['tokens']);

        return array(
            'questions'   => $questions,
            'tokens_used' => $result['tokens'],
            'model'       => $result['model'],
        );
    }

    /**
     * Extract text from PDF file
     */
    public function extract_pdf_text($file_path) {
        if (!file_exists($file_path)) {
            return new WP_Error('file_not_found', __('ملف PDF غير موجود', 'saint-porphyrius'));
        }

        // Try to use pdftotext (common on Linux servers)
        $pdftotext = trim(shell_exec('which pdftotext 2>/dev/null'));
        if (!empty($pdftotext)) {
            $escaped_path = escapeshellarg($file_path);
            $text = shell_exec("pdftotext -layout {$escaped_path} - 2>/dev/null");
            if ($text !== null && strlen(trim($text)) > 50) {
                return trim($text);
            }
        }

        // Try PHP's built-in if available
        if (class_exists('Smalot\PdfParser\Parser')) {
            // Would use PDFParser library if available
        }

        // Basic fallback: try to read raw content (works for some simple PDFs)
        $content = file_get_contents($file_path);
        if ($content !== false) {
            // Try to extract text between stream/endstream tags (very basic)
            if (preg_match_all('/stream\s*(.*?)\s*endstream/s', $content, $matches)) {
                $text_parts = array();
                foreach ($matches[1] as $stream) {
                    // Try to decompress
                    $decoded = @gzuncompress($stream);
                    if ($decoded !== false) {
                        // Extract readable text
                        $decoded = preg_replace('/[^\P{C}\n\r\t]/u', '', $decoded);
                        if (strlen(trim($decoded)) > 10) {
                            $text_parts[] = trim($decoded);
                        }
                    }
                }
                if (!empty($text_parts)) {
                    return implode("\n\n", $text_parts);
                }
            }
        }

        return new WP_Error('extraction_failed', __('تعذر استخراج النص من ملف PDF. يرجى تثبيت pdftotext على الخادم.', 'saint-porphyrius'));
    }

    // =========================================================================
    // PDF UPLOAD HANDLING
    // =========================================================================

    /**
     * Handle PDF upload for a lesson
     */
    public function handle_pdf_upload($lesson_id, $file, $grade_key = 'all') {
        $lesson = $this->get_lesson($lesson_id);
        if (!$lesson) {
            return new WP_Error('not_found', __('الدرس غير موجود', 'saint-porphyrius'));
        }

        // Validate file
        $allowed_types = array('application/pdf');
        $file_type = wp_check_filetype($file['name']);
        $mime_type = $file['type'] ?? '';

        if (!in_array($mime_type, $allowed_types) && $file_type['ext'] !== 'pdf') {
            return new WP_Error('invalid_type', __('يجب أن يكون الملف بصيغة PDF', 'saint-porphyrius'));
        }

        // Use WordPress upload handling
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $upload = wp_handle_upload($file, array('test_form' => false, 'mimes' => array('pdf' => 'application/pdf')));

        if (isset($upload['error'])) {
            return new WP_Error('upload_error', $upload['error']);
        }

        $pdf_url = $upload['url'];
        $pdf_path = $upload['file'];

        // Extract text from PDF
        $extracted_text = '';
        $extract_result = $this->extract_pdf_text($pdf_path);
        if (!is_wp_error($extract_result)) {
            $extracted_text = $extract_result;
        }

        // Update lesson with PDF URL and extracted text
        $pdf_urls = $lesson->pdf_urls;
        if (is_object($pdf_urls)) {
            $pdf_urls = (array) $pdf_urls;
        }
        if (!is_array($pdf_urls)) {
            $pdf_urls = array();
        }
        $pdf_urls[$grade_key] = $pdf_url;

        $update_data = array(
            'pdf_urls' => $pdf_urls,
        );

        if (!empty($extracted_text)) {
            // Append or set extracted text
            $existing_text = $lesson->pdf_text ?? '';
            $update_data['pdf_text'] = $existing_text ? $existing_text . "\n\n--- {$grade_key} ---\n" . $extracted_text : $extracted_text;
        }

        $this->update_lesson($lesson_id, $update_data);

        return array(
            'url'            => $pdf_url,
            'path'           => $pdf_path,
            'grade_key'      => $grade_key,
            'extracted_text' => !empty($extracted_text) ? mb_substr($extracted_text, 0, 500) . '...' : null,
            'text_length'    => mb_strlen($extracted_text),
        );
    }

    // =========================================================================
    // AI LOGGING
    // =========================================================================

    /**
     * Log an AI action
     */
    private function log_ai_action($preparation_id, $lesson_id, $user_id, $action_type, $prompt, $response, $status = 'success', $error = '', $tokens = 0) {
        global $wpdb;

        $wpdb->insert(
            $this->ai_log_table,
            array(
                'preparation_id'    => $preparation_id ? absint($preparation_id) : null,
                'lesson_id'         => absint($lesson_id),
                'user_id'           => absint($user_id),
                'action_type'       => $action_type,
                'prompt_sent'       => is_string($prompt) ? $prompt : wp_json_encode($prompt),
                'response_received' => is_string($response) ? $response : wp_json_encode($response),
                'ai_model'          => class_exists('SP_Quiz') ? SP_Quiz::get_instance()->get_settings()['ai_model'] ?? 'gpt-4o' : 'gpt-4o',
                'tokens_used'       => absint($tokens),
                'status'            => $status,
                'error_message'     => $error,
            ),
            array('%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
        );
    }

    // =========================================================================
    // UTILITY: BULK ACCESS via CSV
    // =========================================================================

    /**
     * Parse a CSV of user access (user_id, grade)
     */
    public function parse_access_csv($csv_content) {
        $lines = explode("\n", trim($csv_content));
        $access = array();

        foreach ($lines as $line) {
            $parts = str_getcsv(trim($line));
            if (count($parts) >= 2) {
                $user_id = absint($parts[0]);
                $grade = absint($parts[1]);
                if ($user_id && $grade >= 1 && $grade <= 6) {
                    $access[] = array('user_id' => $user_id, 'grade' => $grade);
                }
            }
        }

        return $access;
    }

    /**
     * Get all users grouped by grade
     */
    public function get_users_by_grade() {
        $users = get_users(array(
            'role__in' => array('sp_member', 'sp_church_admin'),
            'orderby'  => 'display_name',
            'order'    => 'ASC',
        ));

        $by_grade = array(1 => array(), 2 => array(), 3 => array(), 4 => array(), 5 => array(), 6 => array(), 0 => array());

        foreach ($users as $user) {
            $grade = $this->get_user_grade($user->ID);
            $by_grade[$grade][] = array(
                'id'           => $user->ID,
                'display_name' => $user->display_name,
                'name_ar'      => get_user_meta($user->ID, 'sp_name_ar', true) ?: $user->display_name,
                'church'       => get_user_meta($user->ID, 'sp_church_name', true),
                'grade'        => $grade,
            );
        }

        return $by_grade;
    }

    /**
     * Get section labels in Arabic
     */
    public static function get_section_labels() {
        return array(
            'lesson_name'         => __('اسم الدرس', 'saint-porphyrius'),
            'objective'           => __('الهدف', 'saint-porphyrius'),
            'verse_ayah'          => __('الآية', 'saint-porphyrius'),
            'training_exercises'  => __('التدريب', 'saint-porphyrius'),
            'explanation_means'   => __('وسيلة الإيضاح', 'saint-porphyrius'),
            'lesson_introduction' => __('مقدمة الدرس', 'saint-porphyrius'),
            'lesson_writing'      => __('كتابة الدرس', 'saint-porphyrius'),
        );
    }

    /**
     * Get status labels in Arabic
     */
    public static function get_status_labels() {
        return array(
            'draft'           => __('مسودة', 'saint-porphyrius'),
            'submitted'       => __('مُقدم', 'saint-porphyrius'),
            'under_review'    => __('قيد المراجعة', 'saint-porphyrius'),
            'approved'        => __('مقبول', 'saint-porphyrius'),
            'needs_revision'  => __('يحتاج تعديل', 'saint-porphyrius'),
        );
    }
}
