<?php
/**
 * Saint Porphyrius - Social Profile Handler
 * Aggregates all user data into a social profile view
 * Integrates with: Points, Attendance, Events, Bus, Quiz, Forbidden, Excuses, Gamification
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Social_Profile {
    
    private static $instance = null;
    private $table_name;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sp_social_profiles';
    }
    
    /**
     * Check if social profiles feature is enabled
     */
    public function is_enabled() {
        return (bool) get_option('sp_social_profiles_enabled', true);
    }
    
    /**
     * Get settings
     */
    public function get_settings() {
        return array(
            'enabled'              => (bool) get_option('sp_social_profiles_enabled', true),
            'show_points_history'  => (bool) get_option('sp_social_show_points_history', true),
            'show_attendance'      => (bool) get_option('sp_social_show_attendance', true),
            'show_bus_info'        => (bool) get_option('sp_social_show_bus_info', true),
            'show_quiz_stats'      => (bool) get_option('sp_social_show_quiz_stats', true),
            'show_discipline'      => (bool) get_option('sp_social_show_discipline', true),
            'show_events'          => (bool) get_option('sp_social_show_events', true),
            'show_excuses'         => (bool) get_option('sp_social_show_excuses', false),
            'allow_cover_upload'   => (bool) get_option('sp_social_allow_cover_upload', true),
            'allow_profile_upload' => (bool) get_option('sp_social_allow_profile_upload', true),
        );
    }
    
    /**
     * Save settings
     */
    public function save_settings($data) {
        $fields = array(
            'sp_social_profiles_enabled'      => 'enabled',
            'sp_social_show_points_history'    => 'show_points_history',
            'sp_social_show_attendance'        => 'show_attendance',
            'sp_social_show_bus_info'          => 'show_bus_info',
            'sp_social_show_quiz_stats'        => 'show_quiz_stats',
            'sp_social_show_discipline'        => 'show_discipline',
            'sp_social_show_events'            => 'show_events',
            'sp_social_show_excuses'           => 'show_excuses',
            'sp_social_allow_cover_upload'     => 'allow_cover_upload',
            'sp_social_allow_profile_upload'   => 'allow_profile_upload',
        );
        
        foreach ($fields as $option_key => $data_key) {
            $value = isset($data[$data_key]) ? (bool) $data[$data_key] : false;
            update_option($option_key, $value);
        }
        
        return true;
    }
    
    /**
     * Get or create social profile record
     */
    public function get_profile_record($user_id) {
        global $wpdb;
        
        $record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE user_id = %d",
            $user_id
        ));
        
        if (!$record) {
            // Auto-create record
            $wpdb->insert($this->table_name, array(
                'user_id' => $user_id,
                'cover_image' => '',
                'profile_image' => '',
                'bio' => '',
            ), array('%d', '%s', '%s', '%s'));
            
            $record = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE user_id = %d",
                $user_id
            ));
        }
        
        return $record;
    }
    
    /**
     * Update profile images
     */
    public function update_images($user_id, $cover_url = null, $profile_url = null) {
        global $wpdb;
        
        $this->get_profile_record($user_id); // ensure record exists
        
        $data = array();
        $format = array();
        
        if ($cover_url !== null) {
            $data['cover_image'] = sanitize_url($cover_url);
            $format[] = '%s';
        }
        
        if ($profile_url !== null) {
            $data['profile_image'] = sanitize_url($profile_url);
            $format[] = '%s';
        }
        
        if (empty($data)) {
            return false;
        }
        
        return $wpdb->update(
            $this->table_name,
            $data,
            array('user_id' => $user_id),
            $format,
            array('%d')
        );
    }
    
    /**
     * Get the full social profile data for a user
     * Aggregates from all system components
     */
    public function get_full_profile($user_id, $viewer_id = null) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return new WP_Error('not_found', __('المستخدم غير موجود', 'saint-porphyrius'));
        }
        
        $settings = $this->get_settings();
        $profile_record = $this->get_profile_record($user_id);
        
        // Basic info
        $first_name = $user->first_name;
        $middle_name = get_user_meta($user_id, 'sp_middle_name', true);
        $last_name = $user->last_name;
        $gender = get_user_meta($user_id, 'sp_gender', true) ?: 'male';
        $church = get_user_meta($user_id, 'sp_church_name', true);
        $join_date = $user->user_registered;
        
        $profile = array(
            'user_id'        => $user_id,
            'first_name'     => $first_name,
            'middle_name'    => $middle_name,
            'last_name'      => $last_name,
            'full_name'      => trim($first_name . ' ' . $middle_name),
            'gender'         => $gender,
            'church'         => $church,
            'join_date'      => $join_date,
            'cover_image'    => $profile_record->cover_image ?? '',
            'profile_image'  => $profile_record->profile_image ?? '',
            'is_own_profile' => ($viewer_id && $viewer_id == $user_id),
        );
        
        // Points & Rank
        $points_handler = SP_Points::get_instance();
        $profile['points'] = $points_handler->get_balance($user_id);
        
        $leaderboard = $points_handler->get_leaderboard(200);
        $profile['rank'] = 0;
        $profile['total_members'] = count($leaderboard);
        foreach ($leaderboard as $idx => $entry) {
            if ($entry->user_id == $user_id) {
                $profile['rank'] = $idx + 1;
                break;
            }
        }
        
        // Attendance stats
        if ($settings['show_attendance']) {
            $attendance_handler = SP_Attendance::get_instance();
            $stats = $attendance_handler->get_user_stats($user_id);
            $profile['attendance'] = array(
                'total'           => $stats->total ?? 0,
                'attended'        => $stats->attended ?? 0,
                'absent'          => $stats->absent ?? 0,
                'excused'         => $stats->excused ?? 0,
                'late'            => $stats->late ?? 0,
                'attendance_rate' => $stats->attendance_rate ?? 0,
            );
        }
        
        // Discipline / Forbidden status
        if ($settings['show_discipline']) {
            $forbidden_handler = SP_Forbidden::get_instance();
            $visual = $forbidden_handler->get_visual_status($user_id);
            $profile['discipline'] = array(
                'card_status'          => $visual['card_status'],
                'consecutive_absences' => $visual['consecutive_absences'],
                'forbidden_remaining'  => $visual['forbidden_remaining'],
                'is_blocked'           => $visual['is_blocked'],
                'max_absences'         => $visual['max_absences'],
                'percentage'           => $visual['percentage'],
            );
        }
        
        // Recent events (last 10 attended/absent)
        if ($settings['show_events']) {
            $profile['recent_events'] = $this->get_recent_event_activity($user_id, 10);
        }
        
        // Bus bookings
        if ($settings['show_bus_info']) {
            $profile['bus_activity'] = $this->get_bus_activity($user_id, 5);
        }
        
        // Quiz stats
        if ($settings['show_quiz_stats']) {
            $profile['quiz_stats'] = $this->get_quiz_stats($user_id);
        }
        
        // Points history (timeline / "social posts")
        if ($settings['show_points_history']) {
            $profile['points_timeline'] = $this->get_points_timeline($user_id, 30);
        }
        
        // Gamification achievements
        $gamification = SP_Gamification::get_instance();
        $profile['achievements'] = array(
            'story_quiz_completed'      => $gamification->has_completed_story_quiz($user_id),
            'service_quiz_completed'     => $gamification->has_completed_service_instructions($user_id),
            'profile_complete'           => (bool) get_user_meta($user_id, 'sp_profile_completion_rewarded', true),
        );
        
        // Social links
        $profile['social'] = array(
            'facebook'  => get_user_meta($user_id, 'facebook_link', true),
            'instagram' => get_user_meta($user_id, 'instagram_link', true),
        );
        
        return $profile;
    }
    
    /**
     * Get recent event attendance activity
     */
    private function get_recent_event_activity($user_id, $limit = 10) {
        global $wpdb;
        
        $attendance_table = $wpdb->prefix . 'sp_attendance';
        $events_table = $wpdb->prefix . 'sp_events';
        $types_table = $wpdb->prefix . 'sp_event_types';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT a.status, a.points_awarded, a.marked_at,
                    e.title_ar, e.event_date, e.start_time,
                    t.name_ar as type_name, t.icon as type_icon, t.color as type_color
             FROM {$attendance_table} a
             JOIN {$events_table} e ON a.event_id = e.id
             LEFT JOIN {$types_table} t ON e.event_type_id = t.id
             WHERE a.user_id = %d
             ORDER BY e.event_date DESC, a.marked_at DESC
             LIMIT %d",
            $user_id,
            $limit
        ));
        
        return $results;
    }
    
    /**
     * Get bus booking activity
     */
    private function get_bus_activity($user_id, $limit = 5) {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'sp_bus_seat_bookings';
        $event_buses_table = $wpdb->prefix . 'sp_event_buses';
        $events_table = $wpdb->prefix . 'sp_events';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT b.seat_label, b.status as booking_status, b.booked_at, b.checked_in_at,
                    eb.bus_name, eb.bus_number, eb.departure_time, eb.departure_location,
                    e.title_ar as event_title, e.event_date
             FROM {$bookings_table} b
             JOIN {$event_buses_table} eb ON b.event_bus_id = eb.id
             JOIN {$events_table} e ON eb.event_id = e.id
             WHERE b.user_id = %d AND b.status != 'cancelled'
             ORDER BY b.booked_at DESC
             LIMIT %d",
            $user_id,
            $limit
        ));
        
        return $results;
    }
    
    /**
     * Get quiz stats for user
     */
    private function get_quiz_stats($user_id) {
        global $wpdb;
        
        $attempts_table = $wpdb->prefix . 'sp_quiz_attempts';
        $content_table = $wpdb->prefix . 'sp_quiz_content';
        
        // Get summary
        $summary = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as total_attempts,
                    COUNT(DISTINCT content_id) as quizzes_taken,
                    SUM(points_awarded) as total_points,
                    AVG(percentage) as avg_score,
                    MAX(percentage) as best_score
             FROM {$attempts_table}
             WHERE user_id = %d",
            $user_id
        ));
        
        // Get recent attempts
        $recent = $wpdb->get_results($wpdb->prepare(
            "SELECT a.score, a.total_questions, a.percentage, a.points_awarded, a.completed_at,
                    c.title_ar as quiz_title
             FROM {$attempts_table} a
             JOIN {$content_table} c ON a.content_id = c.id
             WHERE a.user_id = %d
             ORDER BY a.completed_at DESC
             LIMIT 5",
            $user_id
        ));
        
        return array(
            'summary' => $summary,
            'recent'  => $recent,
        );
    }
    
    /**
     * Get points timeline like social posts
     * Each point transaction becomes a "post" in the timeline
     */
    private function get_points_timeline($user_id, $limit = 30) {
        global $wpdb;
        
        $points_table = $wpdb->prefix . 'sp_points_log';
        $events_table = $wpdb->prefix . 'sp_events';
        $types_table = $wpdb->prefix . 'sp_event_types';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT p.points, p.type, p.reason, p.balance_after, p.created_at,
                    e.title_ar as event_title, e.event_date,
                    t.name_ar as event_type_name, t.icon as event_type_icon
             FROM {$points_table} p
             LEFT JOIN {$events_table} e ON p.event_id = e.id
             LEFT JOIN {$types_table} t ON e.event_type_id = t.id
             WHERE p.user_id = %d
             ORDER BY p.created_at DESC
             LIMIT %d",
            $user_id,
            $limit
        ));
        
        // Enrich with labels and icons
        $timeline = array();
        foreach ($results as $entry) {
            $item = (array) $entry;
            $item['type_label'] = SP_Points::get_type_label($entry->type, 'ar');
            $item['type_color'] = SP_Points::get_type_color($entry->type);
            $item['icon'] = $this->get_timeline_icon($entry->type);
            $item['message'] = $this->get_timeline_message($entry);
            $timeline[] = $item;
        }
        
        return $timeline;
    }
    
    /**
     * Get icon for timeline entry
     */
    private function get_timeline_icon($type) {
        $icons = array(
            'attendance'          => '✅',
            'late_attendance'     => '⏰',
            'absence_penalty'     => '❌',
            'excused'             => '📋',
            'excuse_submission'   => '📝',
            'excuse_denied'       => '🚫',
            'adjustment'          => '⚙️',
            'bonus'               => '🎁',
            'reward'              => '🏆',
            'penalty'             => '⚠️',
            'bus_booking_fee'     => '🚌',
            'bus_booking_refund'  => '🚌',
            'point_share_sent'    => '💝',
            'point_share_received'=> '💝',
        );
        
        return isset($icons[$type]) ? $icons[$type] : '⭐';
    }
    
    /**
     * Generate human-readable Arabic message for timeline
     */
    private function get_timeline_message($entry) {
        $event_title = $entry->event_title ? $entry->event_title : '';
        
        switch ($entry->type) {
            case 'attendance':
                return sprintf('حضر "%s" وحصل على %+d نقطة', $event_title, $entry->points);
            case 'late_attendance':
                return sprintf('حضر متأخراً "%s" وحصل على %+d نقطة', $event_title, $entry->points);
            case 'absence_penalty':
                return sprintf('غاب عن "%s" وخسر %d نقطة', $event_title, abs($entry->points));
            case 'excused':
                return sprintf('اعتذر عن "%s"', $event_title);
            case 'excuse_submission':
                return sprintf('قدّم اعتذار عن "%s" (-‎%d نقطة رسوم)', $event_title, abs($entry->points));
            case 'excuse_denied':
                return sprintf('تم رفض اعتذاره عن "%s" (-‎%d نقطة)', $event_title, abs($entry->points));
            case 'bus_booking_fee':
                return sprintf('حجز مقعد في الباص لـ "%s" (-‎%d نقطة)', $event_title, abs($entry->points));
            case 'bus_booking_refund':
                return sprintf('استرد رسوم الباص لـ "%s" (+%d نقطة)', $event_title, $entry->points);
            case 'point_share_sent':
                return sprintf('شارك %d نقطة مع صديق', abs($entry->points));
            case 'point_share_received':
                return sprintf('حصل على %d نقطة هدية من صديق', $entry->points);
            case 'bonus':
                return $entry->reason ?: sprintf('حصل على مكافأة %+d نقطة', $entry->points);
            case 'reward':
                return $entry->reason ?: sprintf('حصل على جائزة %+d نقطة', $entry->points);
            case 'penalty':
                return $entry->reason ?: sprintf('خسر %d نقطة عقوبة', abs($entry->points));
            case 'adjustment':
                return $entry->reason ?: sprintf('تعديل يدوي %+d نقطة', $entry->points);
            default:
                return $entry->reason ?: sprintf('%+d نقطة', $entry->points);
        }
    }
    
    /**
     * Handle image upload for profile/cover
     */
    public function handle_image_upload($user_id, $file, $type = 'profile') {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        // Validate type
        if (!in_array($type, array('profile', 'cover'))) {
            return new WP_Error('invalid_type', __('نوع الصورة غير صحيح', 'saint-porphyrius'));
        }
        
        // Check file size (2MB for profile, 5MB for cover)
        $max_size = ($type === 'profile') ? 2 * 1024 * 1024 : 5 * 1024 * 1024;
        if ($file['size'] > $max_size) {
            $max_mb = $max_size / (1024 * 1024);
            return new WP_Error('file_too_large', sprintf(__('حجم الصورة أكبر من %d ميجابايت', 'saint-porphyrius'), $max_mb));
        }
        
        // Validate file type
        $allowed = array('image/jpeg', 'image/png', 'image/webp');
        if (!in_array($file['type'], $allowed)) {
            return new WP_Error('invalid_type', __('نوع الملف غير مسموح. استخدم JPG أو PNG أو WebP', 'saint-porphyrius'));
        }
        
        $upload_overrides = array(
            'test_form' => false,
            'unique_filename_callback' => function($dir, $name, $ext) use ($user_id, $type) {
                return "sp_{$type}_{$user_id}" . $ext;
            },
        );
        
        $movefile = wp_handle_upload($file, $upload_overrides);
        
        if (isset($movefile['error'])) {
            return new WP_Error('upload_error', $movefile['error']);
        }
        
        $url = $movefile['url'];
        
        // Update in DB
        if ($type === 'profile') {
            $this->update_images($user_id, null, $url);
        } else {
            $this->update_images($user_id, $url, null);
        }
        
        return array(
            'url' => $url,
            'type' => $type,
        );
    }
    
    /**
     * Get upcoming event registrations for user
     */
    public function get_upcoming_registrations($user_id, $limit = 5) {
        global $wpdb;
        
        $expected_table = $wpdb->prefix . 'sp_expected_attendance';
        $events_table = $wpdb->prefix . 'sp_events';
        $types_table = $wpdb->prefix . 'sp_event_types';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT ea.registered_at,
                    e.id as event_id, e.title_ar, e.event_date, e.start_time,
                    t.name_ar as type_name, t.icon as type_icon, t.color as type_color
             FROM {$expected_table} ea
             JOIN {$events_table} e ON ea.event_id = e.id
             LEFT JOIN {$types_table} t ON e.event_type_id = t.id
             WHERE ea.user_id = %d AND e.event_date >= CURDATE()
             ORDER BY e.event_date ASC
             LIMIT %d",
            $user_id,
            $limit
        ));
        
        return $results;
    }
    
    /**
     * Get excuse history for user
     */
    public function get_excuse_history($user_id, $limit = 5) {
        global $wpdb;
        
        $excuses_table = $wpdb->prefix . 'sp_excuses';
        $events_table = $wpdb->prefix . 'sp_events';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT ex.excuse_text, ex.status, ex.points_deducted, ex.created_at,
                    e.title_ar as event_title, e.event_date
             FROM {$excuses_table} ex
             JOIN {$events_table} e ON ex.event_id = e.id
             WHERE ex.user_id = %d
             ORDER BY ex.created_at DESC
             LIMIT %d",
            $user_id,
            $limit
        ));
        
        return $results;
    }

    /**
     * Get profile image URL for a user (with static cache)
     */
    private static $image_cache = array();

    public function get_profile_image_url($user_id) {
        if (isset(self::$image_cache[$user_id])) {
            return self::$image_cache[$user_id];
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sp_social_profiles';
        $url = $wpdb->get_var($wpdb->prepare(
            "SELECT profile_image FROM {$table} WHERE user_id = %d",
            $user_id
        ));

        self::$image_cache[$user_id] = $url ?: '';
        return self::$image_cache[$user_id];
    }
}

/**
 * Global helper: render avatar HTML (image or initial fallback)
 *
 * @param int    $user_id     WordPress user ID
 * @param string $initial     Fallback initial letter
 * @param string $extra_class Extra CSS classes for the wrapper div
 * @return string HTML
 */
function sp_render_avatar($user_id, $initial, $extra_class = '') {
    $profile_img = SP_Social_Profile::get_instance()->get_profile_image_url($user_id);
    if ($profile_img) {
        return '<img src="' . esc_url($profile_img) . '" alt="" class="sp-avatar-img">';
    }
    return esc_html($initial);
}

/**
 * Get the URL to a user's social profile page.
 *
 * @param int $user_id WordPress user ID
 * @return string Profile URL
 */
function sp_profile_url($user_id) {
    return home_url('/app/member/?id=' . absint($user_id));
}
