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
            // JSON config strings arrive slash-escaped from $_POST; wp_unslash()
            // before storing so the persisted JSON stays valid (otherwise it
            // decodes to an empty array on read and the lesson silently falls
            // back to global defaults). Array inputs are encoded clean.
            'pdf_urls'              => isset($data['pdf_urls']) ? (is_string($data['pdf_urls']) ? wp_unslash($data['pdf_urls']) : wp_json_encode($data['pdf_urls'])) : null,
            'pdf_text'              => isset($data['pdf_text']) ? $data['pdf_text'] : null,
            'quiz_config'           => isset($data['quiz_config']) ? (is_string($data['quiz_config']) ? wp_unslash($data['quiz_config']) : wp_json_encode($data['quiz_config'])) : null,
            'prep_points_config'    => isset($data['prep_points_config']) ? (is_string($data['prep_points_config']) ? wp_unslash($data['prep_points_config']) : wp_json_encode($data['prep_points_config'])) : null,
            'ai_detection_config'   => isset($data['ai_detection_config']) ? (is_string($data['ai_detection_config']) ? wp_unslash($data['ai_detection_config']) : wp_json_encode($data['ai_detection_config'])) : null,
            'status'                => sanitize_text_field($data['status'] ?? 'draft'),
            'created_by'            => get_current_user_id(),
        );

        $formats = array('%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d');

        $result = $wpdb->insert($this->lessons_table, $insert_data, $formats);

        if ($result === false) {
            return new WP_Error('db_error', __('فشل في إنشاء الدرس', 'saint-porphyrius'));
        }

        $lesson_id = $wpdb->insert_id;

        // Process access list if provided. It arrives as a JSON string in $_POST,
        // which WordPress slash-escapes (magic quotes); without wp_unslash() the
        // escaped quotes make json_decode() return null and the access is silently
        // dropped (grades survive only because [1,2,3] contains no quotes).
        if (!empty($data['access_users'])) {
            $users = is_string($data['access_users']) ? json_decode(wp_unslash($data['access_users']), true) : $data['access_users'];
            if (is_array($users) && !empty($users)) {
                $access = $this->set_lesson_access($lesson_id, $users);
                if (is_wp_error($access)) {
                    return $access;
                }
            }
        }

        $this->maybe_announce_publish($lesson_id, null, $data['status'] ?? 'draft');

        return $this->get_lesson($lesson_id);
    }

    /**
     * Tell the assigned members when a lesson becomes visible to them -- on creation as
     * `published`, or on the edit that flips it there. Only on the transition, so
     * re-saving a published lesson does not re-announce it.
     */
    private function maybe_announce_publish($lesson_id, $previous_status, $new_status) {
        if ($new_status !== 'published' || $previous_status === 'published') {
            return;
        }

        $this->notify_lesson_published($lesson_id);
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
                } elseif (is_string($value)) {
                    // JSON config strings arrive slash-escaped from $_POST; unslash
                    // so the stored JSON stays valid instead of decoding to empty.
                    $value = wp_unslash($value);
                }
                $update_data[$field] = $value;
                $formats[] = '%s';
            }
        }

        // An empty $update_data used to return here -- which meant that changing *only*
        // the member list saved nothing at all, since the access write lives below.
        if (!empty($update_data)) {
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
        }

        // Update access if provided. Like create_lesson, the JSON string from
        // $_POST is slash-escaped by WordPress, so wp_unslash() before decoding —
        // otherwise json_decode() returns null and the saved access is left
        // untouched while the admin believes their selection was stored.
        if (isset($data['access_users'])) {
            $users = is_string($data['access_users']) ? json_decode(wp_unslash($data['access_users']), true) : $data['access_users'];
            if (is_array($users)) {
                $access = $this->set_lesson_access($lesson_id, $users);
                if (is_wp_error($access)) {
                    return $access;
                }
            }
        }

        $this->maybe_announce_publish(
            $lesson_id,
            $existing->status,
            $update_data['status'] ?? $existing->status
        );

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
        $where_params = array();
        $join_params = array();
        $joins = '';

        if ($args['status']) {
            $where[] = 'l.status = %s';
            $where_params[] = $args['status'];
        }

        if ($args['event_id']) {
            $where[] = 'l.event_id = %d';
            $where_params[] = $args['event_id'];
        }

        // Filter by access. Access is granted per-user, and a member may be
        // assigned under more than one target grade for the same lesson, so we
        // join a DISTINCT-lesson subquery to avoid returning the lesson twice.
        if ($args['user_id']) {
            $joins .= " INNER JOIN (SELECT DISTINCT lesson_id FROM {$this->access_table} WHERE user_id = %d) la ON l.id = la.lesson_id";
            $join_params[] = $args['user_id'];
        } elseif ($args['grade']) {
            $joins .= " INNER JOIN (SELECT DISTINCT lesson_id FROM {$this->access_table} WHERE grade = %d) la ON l.id = la.lesson_id";
            $join_params[] = $args['grade'];
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

        // Placeholders must be bound in the order they appear in the SQL text:
        // the JOIN subquery comes before the WHERE clause, then LIMIT/OFFSET. The
        // join params therefore go FIRST — pushing them after the WHERE params
        // (as before) swapped status/user_id and made every member query return
        // nothing (user_id bound to "published" => 0, status bound to the user ID).
        $params = array_merge($join_params, $where_params);
        $params[] = $args['limit'];
        $params[] = $args['offset'];

        $sql = $wpdb->prepare($sql, $params);

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

        $lesson_id  = absint($lesson_id);
        $created_by = get_current_user_id();

        // Collect first, write once. This used to run one INSERT per (grade x member) --
        // 6 grades x 60 members is 360 round-trips on the publish request, which is what
        // left the publish button hanging until the gateway timed out.
        $rows = array();

        foreach ($access_data as $entry) {
            // Flat user ID (integer or numeric string)
            if (is_numeric($entry)) {
                $uid = absint($entry);
                if ($uid) {
                    $rows[] = array(0, $uid);
                }
                continue;
            }

            // Legacy format: {grade, user_ids[]}
            if (!is_array($entry)) continue;
            $grade = absint($entry['grade'] ?? 0);
            $user_ids = isset($entry['user_ids']) ? $entry['user_ids'] : (isset($entry['user_id']) ? array($entry['user_id']) : array());

            foreach ($user_ids as $uid) {
                $uid = absint($uid);
                if ($uid) {
                    $rows[] = array($grade, $uid);
                }
            }
        }

        if (empty($rows)) {
            return 0;
        }

        // A member listed twice under the same grade would trip the UNIQUE key
        // (lesson_id, grade, user_id) and take the *whole* batch down with it -- and
        // since the existing rows were already deleted above, that leaves the lesson
        // with nobody assigned to it.
        $unique = array();
        foreach ($rows as $row) {
            $unique[$row[0] . ':' . $row[1]] = $row;
        }
        $rows = array_values($unique);

        $placeholders = array();
        $values = array();

        foreach ($rows as $row) {
            $placeholders[] = '(%d, %d, %d, %d)';
            array_push($values, $lesson_id, $row[0], $row[1], $created_by);
        }

        $suppress = $wpdb->suppress_errors(true);
        $wpdb->last_error = '';

        $result = $wpdb->query($wpdb->prepare(
            "INSERT INTO {$this->access_table} (lesson_id, grade, user_id, created_by)
             VALUES " . implode(', ', $placeholders),
            $values
        ));

        $error = $wpdb->last_error;
        $wpdb->suppress_errors($suppress);

        if ($result === false) {
            // This used to return 0 into a caller that discarded it, so a failed write
            // silently stripped every member's access and they were left being told
            // "ليس لديك صلاحية الوصول لهذا الدرس" with nothing to explain it.
            return new WP_Error(
                'access_write_failed',
                __('تعذّر حفظ قائمة الأعضاء لهذا الدرس. لم يتم تعيين أي عضو.', 'saint-porphyrius')
                    . ($error ? ' — ' . $error : '')
            );
        }

        return count($rows);
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
     * Resolve the grade (year) a member was assigned to for a specific lesson,
     * read from the per-grade access rows. Returns the lowest assigned grade,
     * or 0 if the member has no grade-specific access row.
     */
    public function get_user_lesson_grade($user_id, $lesson_id) {
        global $wpdb;
        $grade = $wpdb->get_var($wpdb->prepare(
            "SELECT grade FROM {$this->access_table} WHERE user_id = %d AND lesson_id = %d AND grade > 0 ORDER BY grade ASC LIMIT 1",
            $user_id, $lesson_id
        ));
        return $grade !== null ? absint($grade) : 0;
    }

    /**
     * Get lessons accessible by a user
     */
    public function get_user_lessons($user_id, $args = array()) {
        // Access is per-user (see set_lesson_access). Admins preview every
        // published lesson; members only see published lessons they've been
        // granted access to — no sp_grade dependency.
        $args['status'] = 'published';

        if (!current_user_can('manage_options')) {
            $args['user_id'] = $user_id;
        }

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
            $type = sanitize_text_field($q['question_type'] ?? 'multiple_choice');

            // Normalize options. true_false questions must still carry a 2-option
            // array so they score the same way as multiple_choice (index match),
            // otherwise an empty options set can never be answered correctly.
            $options = isset($q['options']) ? $q['options'] : array();
            if (is_string($options)) {
                $decoded = json_decode($options, true);
                $options = is_array($decoded) ? $decoded : array();
            }
            $correct_index = absint($q['correct_answer_index'] ?? 0);
            if ($type === 'true_false' && count($options) < 2) {
                $options = array(
                    array('text' => __('صح', 'saint-porphyrius'),  'is_correct' => ($correct_index === 0)),
                    array('text' => __('خطأ', 'saint-porphyrius'), 'is_correct' => ($correct_index === 1)),
                );
            }

            $result = $wpdb->insert(
                $this->questions_table,
                array(
                    'lesson_id'             => $lesson_id,
                    'question_text'         => sanitize_text_field($q['question_text'] ?? ''),
                    'question_type'         => $type,
                    'options'               => wp_json_encode(array_values($options)),
                    'correct_answer_index'  => $correct_index,
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

        // Check retake policy. A *failed* attempt must never lock the user out
        // (otherwise a single failure permanently blocks both the quiz and the
        // preparation gated on it). Only a passing attempt locks when retakes
        // are disabled.
        $allow_retake = $quiz_config['allow_retake'] ?? false;
        if (!$allow_retake && $this->has_passed_quiz($user_id, $lesson_id)) {
            return new WP_Error('already_completed', __('لقد اجتزت هذا الاختبار من قبل', 'saint-porphyrius'));
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

        // Award points if passed. When retakes are off a pass can only be rewarded once, so
        // key on (lesson, user) and two concurrent submissions collapse into one award.
        // With retakes on, each passing attempt is meant to award again, so no key applies.
        if ($points_awarded > 0) {
            $points_handler = SP_Points::get_instance();
            $points_handler->add(
                $user_id,
                $points_awarded,
                'reward',
                $lesson->event_id,
                sprintf(__('إكمال اختبار الدرس: %s', 'saint-porphyrius'), $lesson->title_ar),
                $allow_retake ? null : SP_Points::make_dedupe_key('lp_quiz', $lesson_id, $user_id)
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
     * Check if user has a PASSING attempt for a quiz (>= the lesson's passing %).
     */
    public function has_passed_quiz($user_id, $lesson_id) {
        global $wpdb;
        $lesson = $this->get_lesson($lesson_id);
        $passing = $lesson ? floatval($lesson->quiz_config['passing_percent'] ?? 60) : 60;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->attempts_table} WHERE user_id = %d AND lesson_id = %d AND percentage >= %f",
            $user_id, $lesson_id, $passing
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
     * Column => printf format map for the preparations table.
     *
     * Building $wpdb formats from this map (keyed by the actual columns being
     * written) guarantees alignment even when the optional AI-detection fields
     * are absent. The previous hand-built parallel arrays drifted out of sync
     * and corrupted the `status` column on every draft/submission.
     */
    private function prep_field_formats() {
        return array(
            'user_id'                            => '%d',
            'lesson_id'                          => '%d',
            'event_id'                           => '%d',
            'grade'                              => '%d',
            'section_lesson_name'                => '%s',
            'section_lesson_name_notes'          => '%s',
            'section_lesson_name_points'         => '%d',
            'section_objective'                  => '%s',
            'section_objective_notes'            => '%s',
            'section_objective_points'           => '%d',
            'section_verse_ayah'                 => '%s',
            'section_verse_ayah_notes'           => '%s',
            'section_verse_ayah_points'          => '%d',
            'section_training_exercises'         => '%s',
            'section_training_exercises_notes'   => '%s',
            'section_training_exercises_points'  => '%d',
            'section_explanation_means'          => '%s',
            'section_explanation_means_notes'    => '%s',
            'section_explanation_means_points'   => '%d',
            'section_lesson_introduction'        => '%s',
            'section_lesson_introduction_notes'  => '%s',
            'section_lesson_introduction_points' => '%d',
            'section_lesson_writing'             => '%s',
            'section_lesson_writing_notes'       => '%s',
            'section_lesson_writing_points'      => '%d',
            'ai_detection_score'                 => '%f',
            'ai_detection_is_likely_ai'          => '%d',
            'ai_detection_details'               => '%s',
            'ai_detection_status'                => '%s',
            'ai_penalty_applied'                 => '%d',
            'total_points_awarded'               => '%d',
            'submission_count'                   => '%d',
            'status'                             => '%s',
            'submitted_at'                       => '%s',
        );
    }

    /**
     * Build a $wpdb format array aligned to the keys of $data.
     */
    private function prep_formats_for($data) {
        $map = $this->prep_field_formats();
        $formats = array();
        foreach (array_keys($data) as $col) {
            $formats[] = isset($map[$col]) ? $map[$col] : '%s';
        }
        return $formats;
    }

    /**
     * The statuses a member is still allowed to write to. Once a preparation is with
     * the reviewers it is theirs; it reopens only when an admin sends it back.
     */
    private function is_editable_status($status) {
        return in_array($status, array('draft', 'needs_revision'), true);
    }

    /**
     * The member's preparation for a lesson, or null. There is exactly one per
     * (user, lesson) -- the UNIQUE key added in 2026_07_28_000001 guarantees it -- so
     * this never has to guess which of several rows was meant.
     */
    public function get_user_preparation($user_id, $lesson_id) {
        global $wpdb;

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->preparations_table} WHERE user_id = %d AND lesson_id = %d LIMIT 1",
            absint($user_id),
            absint($lesson_id)
        ));

        if (!$row) {
            return null;
        }

        $row->ai_detection_details = json_decode($row->ai_detection_details, true) ?: array();

        return $row;
    }

    /**
     * How many submissions the member has left for this lesson. 0 means blocked;
     * null means the admin has not set a limit.
     */
    public function get_remaining_submissions($user_id, $lesson_id) {
        $max = absint($this->get_config_value('prep_max_submissions'));

        if ($max < 1) {
            return null;
        }

        $prep = $this->get_user_preparation($user_id, $lesson_id);
        $used = $prep ? absint($prep->submission_count) : 0;

        return max(0, $max - $used);
    }

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

        $config = $this->get_config();

        // The whole system can be switched off by the admin
        if (empty($config['prep_enabled'])) {
            return new WP_Error('prep_disabled', __('نظام تحضير الدروس غير مفعل حالياً', 'saint-porphyrius'));
        }

        // Record the grade the member is preparing under: prefer an explicit
        // value, else the grade they were assigned to for THIS lesson, else
        // their profile grade.
        $grade = absint($data['grade'] ?? 0);
        if (!$grade) {
            $grade = $this->get_user_lesson_grade($user_id, $lesson_id);
        }
        if (!$grade) {
            $grade = $this->get_user_grade($user_id);
        }
        $is_submit = !empty($data['submit']);

        // Resolve the row ourselves rather than trusting the posted id. The client used
        // to decide this, and it got it wrong in both directions: it omitted the id for
        // an already-submitted preparation (so the save INSERTed a duplicate) and two
        // autosaves in flight at once both INSERTed because neither had one yet.
        $existing = $this->get_user_preparation($user_id, $lesson_id);

        // A posted id that names someone else's row is still worth rejecting outright --
        // it means the form came from somewhere it should not have.
        $posted_id = absint($data['id'] ?? 0);
        if ($posted_id && (!$existing || $posted_id != $existing->id)) {
            $claimed = $this->get_preparation($posted_id);
            if (!$claimed || $claimed->user_id != $user_id) {
                return new WP_Error('invalid_prep', __('تحضير غير صالح', 'saint-porphyrius'));
            }
        }

        // Once it is with the reviewers, it is read-only until they send it back. The
        // template hides the controls, but the rule has to live here too: a pending
        // autosave used to land after a submit and quietly flip the row back to 'draft'.
        if ($existing && !$this->is_editable_status($existing->status) && !current_user_can('manage_options')) {
            $labels = self::get_status_labels();
            return new WP_Error(
                'not_editable',
                sprintf(
                    __('لا يمكن تعديل التحضير في حالة "%s". انتظر مراجعة الإدارة.', 'saint-porphyrius'),
                    $labels[$existing->status] ?? $existing->status
                )
            );
        }

        // Submission-time gates (admins bypass)
        if ($is_submit && !current_user_can('manage_options')) {
            $require_quiz = !empty($config['prep_required_quiz']);
            $has_quiz = $this->get_question_count($lesson_id) > 0;
            if ($require_quiz && $has_quiz && !$this->has_passed_quiz($user_id, $lesson_id)) {
                return new WP_Error('quiz_required', __('يجب اجتياز اختبار الدرس قبل تقديم التحضير', 'saint-porphyrius'));
            }

            $max_submissions = absint($config['prep_max_submissions'] ?? 0);
            if ($max_submissions > 0) {
                // One row per (user, lesson) now, so this is simply that row's count.
                // It used to SUM across every row for the pair, which meant the
                // duplicates the old save path created were counted as real attempts
                // and members were locked out after barely one genuine submission.
                $prior_submissions = $existing ? absint($existing->submission_count) : 0;
                if ($prior_submissions >= $max_submissions) {
                    return new WP_Error('max_submissions', sprintf(__('لقد بلغت الحد الأقصى لعدد مرات التقديم (%d)', 'saint-porphyrius'), $max_submissions));
                }
            }
        }

        // Per-section point values (per-lesson override falls back to global)
        $points_config = $lesson->prep_points_config ?: $config['section_points'];

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
            'user_id'   => $user_id,
            'lesson_id' => $lesson_id,
            'event_id'  => absint($lesson->event_id),
            'grade'     => $grade,
        );

        $total_points = 0;
        foreach ($sections as $config_key => $db_field) {
            // wp_unslash() first. $_POST arrives slashed, and running wp_kses_post()
            // straight on it stored \' for every apostrophe -- which the template then
            // rendered back into the textarea, so the next autosave two seconds later
            // added another backslash, forever.
            $content = isset($data[$db_field]) ? wp_kses_post(wp_unslash($data[$db_field])) : '';
            $notes   = isset($data[$db_field . '_notes']) ? wp_kses_post(wp_unslash($data[$db_field . '_notes'])) : '';
            $points  = isset($points_config[$config_key]) ? absint($points_config[$config_key]) : 0;

            $prep_data[$db_field] = $content;
            $prep_data[$db_field . '_notes'] = $notes;
            $prep_data[$db_field . '_points'] = $points;

            $total_points += $points;
        }

        $queue_detection = false;

        if ($is_submit) {
            // AI detection used to run right here, inline: a wp_remote_post to OpenAI
            // with a 120-second timeout, on a request a member was sitting and waiting
            // for. PHP's max_execution_time and the gateway's read timeout are both far
            // shorter than that, so a slow completion meant the browser got a 502/504
            // instead of JSON and the wizard reported "حدث خطأ في الاتصال" -- while PHP
            // carried on and saved the row anyway, burning one of the three attempts for
            // a submission the member had been told had failed.
            //
            // Points are only awarded when an admin approves, so nothing user-facing
            // needs the score at submit time. Park the row and let cron do the call,
            // the same way 6.7.0 moved push notifications off the request.
            $prep_data['ai_detection_status'] = 'none';
            if (trim((string) ($prep_data['section_lesson_writing'] ?? '')) !== '') {
                $ai_config = wp_parse_args(
                    is_array($lesson->ai_detection_config) ? $lesson->ai_detection_config : array(),
                    (array) $this->get_config_value('ai_detection')
                );
                if (!empty($ai_config['enabled'])) {
                    $prep_data['ai_detection_status'] = 'pending';
                    $queue_detection = true;
                }
            }

            $prep_data['total_points_awarded'] = max(0, $total_points);
            $prep_data['submission_count'] = ($existing ? absint($existing->submission_count) : 0) + 1;
            $prep_data['status'] = 'submitted';
            $prep_data['submitted_at'] = current_time('mysql');
        } else {
            // Draft
            $prep_data['total_points_awarded'] = max(0, $total_points);
            $prep_data['status'] = 'draft';
        }

        $prep_id = $this->write_preparation($prep_data, $existing ? absint($existing->id) : 0);

        if (is_wp_error($prep_id)) {
            return $prep_id;
        }

        if ($queue_detection) {
            // Fire and forget. If cron never runs the row simply stays 'pending' and the
            // review screen says so -- it must never block or fail the submission.
            wp_schedule_single_event(time(), 'sp_lesson_prep_ai_detect', array($prep_id));
            spawn_cron();
        }

        $saved = $this->get_preparation($prep_id);

        if (!$saved) {
            // get_preparation() INNER JOINs users and lessons, so it can come back null
            // even though the write succeeded. Returning it regardless used to hand the
            // browser {success: true, data: {preparation: null}}, and reading .status off
            // that threw -- landing in the same "connection error" alert.
            return new WP_Error('db_error', __('تم الحفظ ولكن تعذّر تحميل التحضير. حدّث الصفحة.', 'saint-porphyrius'));
        }

        if ($is_submit) {
            $this->notify_preparation_submitted($saved);
        }

        return $saved;
    }

    /**
     * UPDATE the member's row, or INSERT their first one.
     *
     * The UNIQUE key on (user_id, lesson_id) is what makes this safe against two
     * requests racing -- the loser's INSERT is rejected by the database rather than by
     * a check-then-act guard that both would pass. Same shape as SP_Points::add().
     *
     * @return int|WP_Error The preparation id.
     */
    private function write_preparation($prep_data, $existing_id) {
        global $wpdb;

        $formats = $this->prep_formats_for($prep_data);

        $suppress = $wpdb->suppress_errors(true);
        $wpdb->last_error = '';

        if ($existing_id) {
            $result = $wpdb->update(
                $this->preparations_table,
                $prep_data,
                array('id' => $existing_id),
                $formats,
                array('%d')
            );
            $prep_id = ($result === false) ? 0 : $existing_id;
        } else {
            $result = $wpdb->insert($this->preparations_table, $prep_data, $formats);
            $prep_id = ($result === false) ? 0 : (int) $wpdb->insert_id;
        }

        $error = $wpdb->last_error;
        $wpdb->suppress_errors($suppress);

        if ($prep_id) {
            return $prep_id;
        }

        // A concurrent request beat us to the INSERT. Its row is the one that exists
        // now, so fold this save into it instead of failing the member.
        if (!$existing_id && stripos($error, 'duplicate entry') !== false) {
            $winner = $this->get_user_preparation($prep_data['user_id'], $prep_data['lesson_id']);
            if ($winner) {
                return $this->write_preparation($prep_data, absint($winner->id));
            }
        }

        // "فشل في حفظ التحضير" on its own was a dead end -- a packet-too-large, a
        // charset failure and a dropped connection all looked identical. Carry the
        // driver's own message so the next report is diagnosable.
        $this->log_prep_failure($prep_data, $error);

        $message = __('فشل في حفظ التحضير', 'saint-porphyrius');
        if ($error && current_user_can('manage_options')) {
            $message .= ' — ' . $error;
        }

        return new WP_Error('db_error', $message);
    }

    /**
     * Record a failed write in the AI log table, which already exists and is already
     * the place admins look when the lesson system misbehaves.
     */
    private function log_prep_failure($prep_data, $error) {
        if (!$error) {
            return;
        }

        $this->log_ai_action(
            null,
            absint($prep_data['lesson_id'] ?? 0),
            absint($prep_data['user_id'] ?? 0),
            'prep_save_failed',
            wp_json_encode(array(
                'status'           => $prep_data['status'] ?? '',
                'submission_count' => $prep_data['submission_count'] ?? 0,
            )),
            '',
            'error',
            $error
        );
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
     * Get preparation count. A falsy $lesson_id counts across ALL lessons
     * (used by the admin dashboard "awaiting review" badge).
     */
    public function get_preparation_count($lesson_id = 0, $status = null) {
        global $wpdb;

        $where = array('1=1');
        $params = array();

        if ($lesson_id) {
            $where[] = 'lesson_id = %d';
            $params[] = absint($lesson_id);
        }

        if ($status) {
            $where[] = 'status = %s';
            $params[] = $status;
        }

        $sql = "SELECT COUNT(*) FROM {$this->preparations_table} WHERE " . implode(' AND ', $where);
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return (int) $wpdb->get_var($sql);
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
                    $update_data['admin_notes'] = sanitize_textarea_field(wp_unslash($data['admin_notes']));
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
                        sprintf(__('نقاط تحضير الدرس: %s (الصف %d)', 'saint-porphyrius'), $prep->lesson_title_ar, $prep->grade),
                        SP_Points::make_dedupe_key('lp_approve', $prep_id)
                    );
                }
                break;

            case 'needs_revision':
                $update_data['status'] = 'needs_revision';
                $formats[] = '%s';
                if (isset($data['admin_notes'])) {
                    $update_data['admin_notes'] = sanitize_textarea_field(wp_unslash($data['admin_notes']));
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

        $reviewed = $this->get_preparation($prep_id);

        if ($reviewed) {
            $this->notify_preparation_reviewed($reviewed, $action);
        }

        return $reviewed;
    }

    // =========================================================================
    // BACKGROUND AI DETECTION
    // =========================================================================

    /**
     * Cron worker for `sp_lesson_prep_ai_detect`.
     *
     * Runs the detection that used to sit inline on the member's submit request,
     * applies the penalty, and tells the admins if the text looks machine-written.
     * Everything here is best-effort: the preparation is already saved and already in
     * the review queue, so a failure must only mark the row, never undo anything.
     */
    public function run_queued_ai_detection($prep_id) {
        global $wpdb;

        $prep_id = absint($prep_id);
        if (!$prep_id) {
            return;
        }

        $prep = $this->get_preparation($prep_id);
        if (!$prep) {
            return;
        }

        // Only act on a row still waiting. A retried cron event, or an admin who has
        // already reviewed and adjusted the points, must not be overwritten.
        if (($prep->ai_detection_status ?? 'none') !== 'pending') {
            return;
        }

        $text = (string) $prep->section_lesson_writing;
        if (trim($text) === '') {
            $wpdb->update(
                $this->preparations_table,
                array('ai_detection_status' => 'none'),
                array('id' => $prep_id),
                array('%s'),
                array('%d')
            );
            return;
        }

        $detection = $this->run_ai_detection($prep->lesson_id, $prep->user_id, $text);

        if (is_wp_error($detection)) {
            $wpdb->update(
                $this->preparations_table,
                array('ai_detection_status' => 'failed'),
                array('id' => $prep_id),
                array('%s'),
                array('%d')
            );
            $this->notify_ai_detection_failed($prep, $detection->get_error_message());
            return;
        }

        $lesson = $this->get_lesson($prep->lesson_id);
        $ai_config = wp_parse_args(
            ($lesson && is_array($lesson->ai_detection_config)) ? $lesson->ai_detection_config : array(),
            (array) $this->get_config_value('ai_detection')
        );

        $penalty = 0;
        if (!empty($detection['is_likely_ai'])) {
            $penalty_type   = $ai_config['penalty_type'] ?? 'percentage';
            $penalty_amount = $ai_config['penalty_amount'] ?? 50;
            $writing_points = absint($prep->section_lesson_writing_points);

            $penalty = ($penalty_type === 'percentage')
                ? (int) round($writing_points * ($penalty_amount / 100))
                : (int) min($penalty_amount, $writing_points);
        }

        // Rebuild the total from the sections rather than subtracting from whatever is
        // stored, so a re-run cannot deduct the same penalty twice.
        $section_total = 0;
        foreach (array_keys(self::get_section_labels()) as $key) {
            $section_total += absint($prep->{'section_' . $key . '_points'});
        }

        $wpdb->update(
            $this->preparations_table,
            array(
                'ai_detection_score'        => $detection['score'],
                'ai_detection_is_likely_ai' => !empty($detection['is_likely_ai']) ? 1 : 0,
                'ai_detection_details'      => wp_json_encode($detection['details']),
                'ai_detection_status'       => 'done',
                'ai_penalty_applied'        => $penalty,
                'total_points_awarded'      => max(0, $section_total - $penalty),
            ),
            array('id' => $prep_id),
            array('%f', '%d', '%s', '%s', '%d', '%d'),
            array('%d')
        );

        if (!empty($detection['is_likely_ai'])) {
            $this->notify_ai_suspicion($prep, $detection);
        }
    }

    // =========================================================================
    // NOTIFICATIONS
    // =========================================================================

    /**
     * The notification handler, or null when the system is not available. Every caller
     * treats a null as "skip" -- a notification must never be able to fail a save.
     */
    private function notifications() {
        return class_exists('SP_Notifications') ? SP_Notifications::get_instance() : null;
    }

    private function admin_ids() {
        return get_users(array('role' => 'administrator', 'fields' => 'ID'));
    }

    /**
     * Inbox row plus a queued push, the shape SP_Appeals established. Push is queued,
     * never sent inline, so nothing here can add latency to the request that triggered it.
     */
    private function push_notice($user_ids, $args) {
        $notifications = $this->notifications();
        if (!$notifications || empty($user_ids)) {
            return;
        }

        $user_ids = array_values(array_unique(array_map('absint', (array) $user_ids)));

        $inbox = wp_parse_args($args, array(
            'title'   => '',
            'message' => '',
            'icon'    => '📚',
            'type'    => 'system',
            'url'     => home_url('/app/lesson-prep'),
        ));

        if (count($user_ids) === 1) {
            $inbox['user_id'] = $user_ids[0];
            $notifications->create_inbox_notification($inbox);
        } else {
            $notifications->create_inbox_for_users($user_ids, $inbox);
        }

        if ($notifications->is_configured()) {
            $notifications->queue_to_users(
                $user_ids,
                $inbox['title'],
                $inbox['message'],
                $inbox['url'],
                'auto_lesson_prep'
            );
        }
    }

    /**
     * A preparation reached the review queue: tell the member it arrived, and the admins
     * that there is something waiting. Nothing told anyone before this -- admins found out
     * by loading the review screen and noticing the badge.
     */
    private function notify_preparation_submitted($prep) {
        $lesson_title = $prep->lesson_title_ar ?: __('الدرس', 'saint-porphyrius');

        $this->push_notice(array($prep->user_id), array(
            'title'   => __('تم استلام تحضيرك ✅', 'saint-porphyrius'),
            'message' => sprintf(
                __('تم استلام تحضيرك لدرس «%s» — سيتم مراجعته قريباً.', 'saint-porphyrius'),
                $lesson_title
            ),
            'icon'    => '📝',
            'url'     => home_url('/app/lesson-prep/view/' . $prep->id),
        ));

        $this->push_notice($this->admin_ids(), array(
            'title'   => __('تحضير جديد بانتظار المراجعة', 'saint-porphyrius'),
            'message' => sprintf(
                __('%s قدّم تحضير درس «%s» (الصف %d).', 'saint-porphyrius'),
                $prep->user_name_ar ?: $prep->display_name,
                $lesson_title,
                absint($prep->grade)
            ),
            'icon'    => '📋',
            'url'     => home_url('/app/admin/lesson-prep?filter=submitted'),
        ));
    }

    /**
     * Admins acted on a preparation. `approve` deliberately carries the points itself:
     * SP_Points::add() also fires a generic "⭐ +N نقطة", and that generic line said
     * nothing about which lesson -- and said nothing at all when the award was 0.
     */
    private function notify_preparation_reviewed($prep, $action) {
        $lesson_title = $prep->lesson_title_ar ?: __('الدرس', 'saint-porphyrius');

        if ($action === 'approve') {
            $points = absint($prep->total_points_awarded);
            $this->push_notice(array($prep->user_id), array(
                'title'   => __('تم قبول تحضيرك 🎉', 'saint-porphyrius'),
                'message' => $points > 0
                    ? sprintf(
                        __('تم قبول تحضيرك لدرس «%s» — حصلت على %d نقطة.', 'saint-porphyrius'),
                        $lesson_title,
                        $points
                    )
                    : sprintf(
                        __('تم قبول تحضيرك لدرس «%s».', 'saint-porphyrius'),
                        $lesson_title
                    ),
                'icon'    => '🎉',
                'url'     => home_url('/app/lesson-prep/view/' . $prep->id),
            ));
            return;
        }

        if ($action === 'needs_revision') {
            $note = trim((string) $prep->admin_notes);
            $message = sprintf(
                __('تحضيرك لدرس «%s» يحتاج تعديل.', 'saint-porphyrius'),
                $lesson_title
            );
            if ($note !== '') {
                $message .= ' ' . sprintf(__('ملاحظة الإدارة: %s', 'saint-porphyrius'), $note);
            }

            $this->push_notice(array($prep->user_id), array(
                'title'   => __('تحضيرك يحتاج تعديل ✏️', 'saint-porphyrius'),
                'message' => $message,
                'icon'    => '✏️',
                // Straight back into the wizard -- needs_revision is editable again.
                'url'     => home_url('/app/lesson-prep/prepare/' . $prep->lesson_id),
            ));
            return;
        }

        if ($action === 'under_review') {
            $this->push_notice(array($prep->user_id), array(
                'title'   => __('تحضيرك قيد المراجعة', 'saint-porphyrius'),
                'message' => sprintf(
                    __('بدأت مراجعة تحضيرك لدرس «%s».', 'saint-porphyrius'),
                    $lesson_title
                ),
                'icon'    => '🔍',
                'url'     => home_url('/app/lesson-prep/view/' . $prep->id),
            ));
        }
    }

    private function notify_ai_suspicion($prep, $detection) {
        $this->push_notice($this->admin_ids(), array(
            'title'   => __('تنبيه: تحضير مشتبه بأنه بالذكاء الاصطناعي 🤖', 'saint-porphyrius'),
            'message' => sprintf(
                __('تحضير %s لدرس «%s» سجّل %d%% في فحص الذكاء الاصطناعي.', 'saint-porphyrius'),
                $prep->user_name_ar ?: $prep->display_name,
                $prep->lesson_title_ar ?: __('الدرس', 'saint-porphyrius'),
                (int) round($detection['score'])
            ),
            'icon'    => '🤖',
            'url'     => home_url('/app/admin/lesson-prep/review/' . $prep->id),
        ));
    }

    /**
     * The detection is a background job now, so a failure has no user in front of it to
     * notice. Say so rather than letting a preparation sit un-checked in silence.
     */
    private function notify_ai_detection_failed($prep, $error) {
        $this->log_ai_action(
            $prep->id,
            $prep->lesson_id,
            $prep->user_id,
            'ai_detect_failed',
            '',
            '',
            'error',
            (string) $error
        );

        $this->push_notice($this->admin_ids(), array(
            'title'   => __('تعذّر فحص الذكاء الاصطناعي', 'saint-porphyrius'),
            'message' => sprintf(
                __('لم يكتمل فحص تحضير #%d. يمكن مراجعته يدوياً.', 'saint-porphyrius'),
                absint($prep->id)
            ),
            'icon'    => '⚠️',
            'url'     => home_url('/app/admin/lesson-prep/review/' . $prep->id),
        ));
    }

    /**
     * A lesson went live: tell the members who were given access to it. The access rows
     * are already the exact recipient list, so no extra targeting is needed.
     */
    public function notify_lesson_published($lesson_id) {
        global $wpdb;

        $lesson = $this->get_lesson($lesson_id);
        if (!$lesson || $lesson->status !== 'published') {
            return;
        }

        $user_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT user_id FROM {$this->access_table} WHERE lesson_id = %d AND user_id > 0",
            absint($lesson_id)
        ));

        if (empty($user_ids)) {
            return;
        }

        $this->push_notice($user_ids, array(
            'title'   => __('درس جديد متاح للتحضير 📖', 'saint-porphyrius'),
            'message' => sprintf(
                __('درس «%s» أصبح متاحاً. ابدأ التحضير الآن.', 'saint-porphyrius'),
                $lesson->title_ar
            ),
            'icon'    => '📖',
            'url'     => home_url('/app/lesson-prep/prepare/' . absint($lesson_id)),
        ));
    }

    /**
     * Nudge members who have access to a lesson tied to an upcoming event and have not
     * submitted yet. Driven by the existing hourly reminder cron.
     */
    public function send_preparation_reminders() {
        global $wpdb;

        if (empty($this->get_config()['prep_enabled'])) {
            return;
        }

        $events_table = $wpdb->prefix . 'sp_events';

        // Lessons whose event starts within the next 48 hours.
        $lessons = $wpdb->get_results(
            "SELECT l.id, l.title_ar
             FROM {$this->lessons_table} l
             JOIN $events_table e ON l.event_id = e.id
             WHERE l.status = 'published'
               AND e.event_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 48 HOUR)"
        );

        foreach ($lessons as $lesson) {
            $user_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT a.user_id
                 FROM {$this->access_table} a
                 LEFT JOIN {$this->preparations_table} p
                        ON p.user_id = a.user_id AND p.lesson_id = a.lesson_id
                 WHERE a.lesson_id = %d
                   AND a.user_id > 0
                   AND (p.id IS NULL OR p.status = 'draft')",
                $lesson->id
            ));

            if (empty($user_ids)) {
                continue;
            }

            $this->push_notice($user_ids, array(
                'title'   => __('تذكير: تحضير الدرس ⏰', 'saint-porphyrius'),
                'message' => sprintf(
                    __('لم تقدّم تحضير درس «%s» بعد، والموعد اقترب.', 'saint-porphyrius'),
                    $lesson->title_ar
                ),
                'icon'    => '⏰',
                'url'     => home_url('/app/lesson-prep/prepare/' . absint($lesson->id)),
            ));
        }
    }

    // =========================================================================
    // AI DETECTION
    // =========================================================================

    /**
     * Run AI content detection on lesson writing text
     */
    public function run_ai_detection($lesson_id, $user_id, $text) {
        // Get detection config. The per-lesson override is MERGED over the
        // global defaults (not replaced) so missing keys — notably `enabled`,
        // which the create wizard never wrote — fall back to the global value.
        $lesson = $this->get_lesson($lesson_id);
        $ai_config = wp_parse_args(
            ($lesson && is_array($lesson->ai_detection_config)) ? $lesson->ai_detection_config : array(),
            (array) $this->get_config_value('ai_detection')
        );

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

        // 30s, not the 120s default. This runs on cron now, but a wedged call would
        // still hold a worker for two minutes and the heuristic fallback is right there.
        $result = $quiz_ai->call_api($messages, 2000, 0.1, 30);

        if (is_wp_error($result)) {
            // Fall back to heuristics
            $heuristic = $this->heuristic_ai_detection($text, $ai_config);
            $heuristic['details']['ai_error'] = $result->get_error_message();
            $this->log_ai_action(null, $lesson_id, $user_id, 'ai_detection', $user_prompt, '', 'error', $result->get_error_message());
            return $heuristic;
        }

        $detection_data = is_array($result['data'] ?? null) ? $result['data'] : array();
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
     * @param int    $lesson_id         Lesson ID
     * @param int    $num_questions     Number of questions to generate
     * @param string $admin_instructions Optional admin instructions for AI
     * @param string $override_text     Optional: use this text instead of lesson's pdf_text
     */
    public function generate_quiz_questions($lesson_id, $num_questions = 10, $admin_instructions = '', $override_text = '') {
        $lesson = $this->get_lesson($lesson_id);
        if (!$lesson) {
            return new WP_Error('not_found', __('الدرس غير موجود', 'saint-porphyrius'));
        }

        $source_text = !empty($override_text) ? $override_text : $lesson->pdf_text;
        if (empty($source_text)) {
            return new WP_Error('no_text', __('لا يوجد نص للدرس. يرجى رفع ملف PDF أو كتابة النص يدوياً أولاً.', 'saint-porphyrius'));
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

        // Delegate to the shared generator, which handles long context (no
        // 8000-char truncation), batching for large question sets, and
        // correct-answer validation + option shuffling.
        $result = $quiz_ai->generate_quiz_from_text(
            $lesson->title_ar,
            $source_text,
            $num_questions,
            $admin_instructions,
            $system_prompt
        );

        if (is_wp_error($result)) {
            $this->log_ai_action(null, $lesson_id, 0, 'quiz_generation', mb_substr($source_text, 0, 2000), '', 'error', $result->get_error_message());
            return $result;
        }

        $questions = $result['questions'];

        // Log the generation
        $this->log_ai_action(null, $lesson_id, 0, 'quiz_generation', mb_substr($source_text, 0, 2000), $questions, 'success', '', $result['tokens_used']);

        return array(
            'questions'   => $questions,
            'tokens_used' => $result['tokens_used'],
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

            // Strategy 1: -enc UTF-8 + -layout (best for Arabic)
            $text = shell_exec("pdftotext -enc UTF-8 -layout {$escaped_path} - 2>/dev/null");
            if ($text !== null && strlen(trim($text)) > 50) {
                return trim($text);
            }

            // Strategy 2: -enc UTF-8 without layout (sometimes better for RTL)
            $text = shell_exec("pdftotext -enc UTF-8 {$escaped_path} - 2>/dev/null");
            if ($text !== null && strlen(trim($text)) > 50) {
                return trim($text);
            }

            // Strategy 3: -raw for problem PDFs
            $text = shell_exec("pdftotext -raw -enc UTF-8 {$escaped_path} - 2>/dev/null");
            if ($text !== null && strlen(trim($text)) > 20) {
                return trim($text);
            }
        }

        // Fallback: try to read raw content (works for some simple PDFs)
        $content = @file_get_contents($file_path);
        if ($content !== false) {
            // Try to extract text between stream/endstream tags
            if (preg_match_all('/stream\s*(.*?)\s*endstream/s', $content, $matches)) {
                $text_parts = array();
                foreach ($matches[1] as $stream) {
                    // Try to decompress
                    $decoded = @gzuncompress($stream);
                    if ($decoded !== false) {
                        // Extract readable text including Arabic (Unicode)
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

        return new WP_Error('extraction_failed', __('تعذر استخراج النص من ملف PDF. يمكنك كتابة النص يدوياً في الخيار البديل.', 'saint-porphyrius'));
    }

    /**
     * Save lesson source text directly (manual input, bypasses PDF)
     */
    public function save_lesson_text($lesson_id, $text) {
        $lesson = $this->get_lesson($lesson_id);
        if (!$lesson) {
            return new WP_Error('not_found', __('الدرس غير موجود', 'saint-porphyrius'));
        }

        $existing = $lesson->pdf_text ?? '';
        $new_text = $existing ? $existing . "\n\n" . $text : $text;

        return $this->update_lesson($lesson_id, array('pdf_text' => $new_text));
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

        // sp_grade meta is not yet used in the system — return all members as a flat list
        $all = array();
        foreach ($users as $user) {
            $all[] = array(
                'id'           => $user->ID,
                'display_name' => $user->display_name,
                'name_ar'      => get_user_meta($user->ID, 'sp_name_ar', true) ?: $user->display_name,
                'church'       => get_user_meta($user->ID, 'sp_church_name', true),
            );
        }

        return array('all' => $all);
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
