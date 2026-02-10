<?php
/**
 * Saint Porphyrius - AJAX Handler
 * Handles all AJAX requests
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Ajax {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Public AJAX actions (no login required)
        add_action('wp_ajax_nopriv_sp_register_user', array($this, 'ajax_register_user'));
        add_action('wp_ajax_nopriv_sp_login_user', array($this, 'ajax_login_user'));
        
        // Private AJAX actions (login required)
        add_action('wp_ajax_sp_register_user', array($this, 'ajax_register_user'));
        add_action('wp_ajax_sp_login_user', array($this, 'ajax_login_user'));
        add_action('wp_ajax_sp_logout_user', array($this, 'ajax_logout_user'));
        add_action('wp_ajax_sp_get_profile', array($this, 'ajax_get_profile'));
        add_action('wp_ajax_sp_update_profile', array($this, 'ajax_update_profile'));
        
        // Admin AJAX actions
        add_action('wp_ajax_sp_approve_user', array($this, 'ajax_approve_user'));
        add_action('wp_ajax_sp_reject_user', array($this, 'ajax_reject_user'));
        add_action('wp_ajax_sp_get_pending_users', array($this, 'ajax_get_pending_users'));
        add_action('wp_ajax_sp_get_points_history', array($this, 'ajax_get_points_history'));
        add_action('wp_ajax_sp_generate_reset_link', array($this, 'ajax_generate_reset_link'));
        add_action('wp_ajax_sp_admin_update_member', array($this, 'ajax_admin_update_member'));
        add_action('wp_ajax_sp_block_member', array($this, 'ajax_block_member'));
        add_action('wp_ajax_sp_delete_member', array($this, 'ajax_delete_member'));
        add_action('wp_ajax_sp_manage_forbidden_status', array($this, 'ajax_manage_forbidden_status'));
        
        // Excuse AJAX actions
        add_action('wp_ajax_sp_submit_excuse', array($this, 'ajax_submit_excuse'));
        
        // QR Attendance AJAX actions
        add_action('wp_ajax_sp_generate_qr_token', array($this, 'ajax_generate_qr_token'));
        add_action('wp_ajax_sp_validate_qr_attendance', array($this, 'ajax_validate_qr_attendance'));
        add_action('wp_ajax_sp_get_qr_status', array($this, 'ajax_get_qr_status'));
        
        // Expected Attendance AJAX actions
        add_action('wp_ajax_sp_register_expected_attendance', array($this, 'ajax_register_expected_attendance'));
        add_action('wp_ajax_sp_unregister_expected_attendance', array($this, 'ajax_unregister_expected_attendance'));
        add_action('wp_ajax_sp_get_expected_attendance', array($this, 'ajax_get_expected_attendance'));
        add_action('wp_ajax_nopriv_sp_get_expected_attendance', array($this, 'ajax_get_expected_attendance'));
        
        // Quiz AJAX actions
        add_action('wp_ajax_sp_submit_quiz', array($this, 'ajax_submit_quiz'));
        add_action('wp_ajax_sp_submit_service_quiz', array($this, 'ajax_submit_service_quiz'));
        
        // Bus Booking AJAX actions
        add_action('wp_ajax_sp_get_bus_seat_map', array($this, 'ajax_get_bus_seat_map'));
        add_action('wp_ajax_sp_book_bus_seat', array($this, 'ajax_book_bus_seat'));
        add_action('wp_ajax_sp_cancel_bus_booking', array($this, 'ajax_cancel_bus_booking'));
        add_action('wp_ajax_sp_get_event_buses', array($this, 'ajax_get_event_buses'));
        add_action('wp_ajax_sp_add_event_bus', array($this, 'ajax_add_event_bus'));
        add_action('wp_ajax_sp_remove_event_bus', array($this, 'ajax_remove_event_bus'));
        add_action('wp_ajax_sp_checkin_bus_passenger', array($this, 'ajax_checkin_bus_passenger'));
        add_action('wp_ajax_sp_move_bus_seat', array($this, 'ajax_move_bus_seat'));

        // Point Sharing AJAX actions
        add_action('wp_ajax_sp_search_members_for_sharing', array($this, 'ajax_search_members_for_sharing'));
        add_action('wp_ajax_sp_preview_share_points', array($this, 'ajax_preview_share_points'));
        add_action('wp_ajax_sp_share_points', array($this, 'ajax_share_points'));

        // Christian Quiz System AJAX actions
        // Admin actions
        add_action('wp_ajax_sp_quiz_save_category', array($this, 'ajax_quiz_save_category'));
        add_action('wp_ajax_sp_quiz_delete_category', array($this, 'ajax_quiz_delete_category'));
        add_action('wp_ajax_sp_quiz_save_content', array($this, 'ajax_quiz_save_content'));
        add_action('wp_ajax_sp_quiz_delete_content', array($this, 'ajax_quiz_delete_content'));
        add_action('wp_ajax_sp_quiz_ai_generate', array($this, 'ajax_quiz_ai_generate'));
        add_action('wp_ajax_sp_quiz_ai_regenerate', array($this, 'ajax_quiz_ai_regenerate'));
        add_action('wp_ajax_sp_quiz_approve', array($this, 'ajax_quiz_approve'));
        add_action('wp_ajax_sp_quiz_publish', array($this, 'ajax_quiz_publish'));
        add_action('wp_ajax_sp_quiz_update_question', array($this, 'ajax_quiz_update_question'));
        add_action('wp_ajax_sp_quiz_delete_question', array($this, 'ajax_quiz_delete_question'));
        add_action('wp_ajax_sp_quiz_update_settings', array($this, 'ajax_quiz_update_settings'));
        add_action('wp_ajax_sp_quiz_get_youtube_info', array($this, 'ajax_quiz_get_youtube_info'));
        // User actions
        add_action('wp_ajax_sp_quiz_submit_attempt', array($this, 'ajax_quiz_submit_attempt'));
        add_action('wp_ajax_sp_quiz_get_content', array($this, 'ajax_quiz_get_content'));
    }
    
    /**
     * Register user AJAX handler
     */
    public function ajax_register_user() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }
        
        $registration = SP_Registration::get_instance();
        
        $result = $registration->register_user($_POST);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Login user AJAX handler
     */
    public function ajax_login_user() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }
        
        $email_or_username = sanitize_text_field($_POST['email']);
        $password = $_POST['password'];
        
        $user_handler = SP_User::get_instance();
        $result = $user_handler->login_user($email_or_username, $password);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Logout user AJAX handler
     */
    public function ajax_logout_user() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }
        
        $user_handler = SP_User::get_instance();
        $result = $user_handler->logout_user();
        
        wp_send_json_success($result);
    }
    
    /**
     * Get user profile AJAX handler
     */
    public function ajax_get_profile() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }
        
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(array('message' => __('يجب تسجيل الدخول', 'saint-porphyrius')));
        }
        
        $user_handler = SP_User::get_instance();
        $result = $user_handler->get_user_profile($user_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Update user profile AJAX handler
     */
    public function ajax_update_profile() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sp_update_profile')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }
        
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(array('message' => __('يجب تسجيل الدخول', 'saint-porphyrius')));
        }
        
        $registration = SP_Registration::get_instance();
        $result = $registration->update_user_profile($user_id, $_POST, false);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Admin update member AJAX handler
     */
    public function ajax_admin_update_member() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sp_admin_update_member')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }
        
        // Check permissions
        if (!current_user_can('sp_manage_members') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('ليس لديك الصلاحية', 'saint-porphyrius')));
        }
        
        $member_id = intval($_POST['member_id']);
        
        if (!$member_id) {
            wp_send_json_error(array('message' => __('معرف العضو غير صحيح', 'saint-porphyrius')));
        }
        
        $registration = SP_Registration::get_instance();
        $result = $registration->update_user_profile($member_id, $_POST, true);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Approve user AJAX handler (Admin only)
     */
    public function ajax_approve_user() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sp_admin_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }
        
        // Check permissions
        if (!current_user_can('sp_approve_members') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('ليس لديك الصلاحية', 'saint-porphyrius')));
        }
        
        $pending_id = intval($_POST['pending_id']);
        
        $registration = SP_Registration::get_instance();
        $result = $registration->approve_user($pending_id, get_current_user_id());
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Reject user AJAX handler (Admin only)
     */
    public function ajax_reject_user() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sp_admin_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }
        
        // Check permissions
        if (!current_user_can('sp_approve_members') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('ليس لديك الصلاحية', 'saint-porphyrius')));
        }
        
        $pending_id = intval($_POST['pending_id']);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');
        
        $registration = SP_Registration::get_instance();
        $result = $registration->reject_user($pending_id, get_current_user_id(), $reason);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Get pending users AJAX handler (Admin only)
     */
    public function ajax_get_pending_users() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sp_admin_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }
        
        // Check permissions
        if (!current_user_can('sp_approve_members') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('ليس لديك الصلاحية', 'saint-porphyrius')));
        }
        
        $registration = SP_Registration::get_instance();
        $users = $registration->get_pending_users();
        
        wp_send_json_success(array('users' => $users));
    }
    
    /**
     * Get points history AJAX handler (Admin only)
     */
    public function ajax_get_points_history() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sp_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'saint-porphyrius')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'saint-porphyrius')));
        }
        
        $user_id = absint($_POST['user_id']);
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Invalid user', 'saint-porphyrius')));
        }
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            wp_send_json_error(array('message' => __('User not found', 'saint-porphyrius')));
        }
        
        $points_handler = SP_Points::get_instance();
        $history = $points_handler->get_history($user_id, array('limit' => 100));
        
        // Format for display
        $formatted_history = array();
        $reason_types = SP_Points::get_reason_types();
        foreach ($history as $entry) {
            // Handle null/empty type - infer from points value
            $entry_type = $entry->type;
            if (empty($entry_type)) {
                // Infer type from points: positive = reward, negative = penalty
                $entry_type = $entry->points >= 0 ? 'reward' : 'penalty';
            }
            
            $type_info = isset($reason_types[$entry_type]) ? $reason_types[$entry_type] : null;
            $formatted_history[] = array(
                'id' => $entry->id,
                'points' => $entry->points,
                'type' => $entry_type,
                'type_label' => $type_info ? $type_info['label_en'] : ucfirst($entry_type ?: 'Unknown'),
                'type_label_ar' => $type_info ? $type_info['label_ar'] : ($entry_type ?: 'غير معروف'),
                'type_color' => $type_info && isset($type_info['color']) ? $type_info['color'] : '#6B7280',
                'reason' => $entry->reason,
                'balance_after' => $entry->balance_after,
                'created_at' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($entry->created_at)),
            );
        }
        
        wp_send_json_success(array(
            'user_name' => get_user_meta($user_id, 'sp_name_ar', true) ?: $user->display_name,
            'current_balance' => $points_handler->get_balance($user_id),
            'history' => $formatted_history,
        ));
    }
    
    /**
     * Submit excuse AJAX handler
     */
    public function ajax_submit_excuse() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['sp_excuse_nonce'], 'sp_submit_excuse')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }
        
        // Must be logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('يجب تسجيل الدخول أولاً', 'saint-porphyrius')));
        }
        
        $event_id = absint($_POST['event_id'] ?? 0);
        $excuse_text = sanitize_textarea_field($_POST['excuse_text'] ?? '');
        
        if (!$event_id) {
            wp_send_json_error(array('message' => __('فعالية غير صالحة', 'saint-porphyrius')));
        }
        
        if (empty($excuse_text)) {
            wp_send_json_error(array('message' => __('يرجى كتابة سبب الاعتذار', 'saint-porphyrius')));
        }
        
        $excuses_handler = SP_Excuses::get_instance();
        $result = $excuses_handler->submit($event_id, get_current_user_id(), $excuse_text);
        
        if ($result['success']) {
            wp_send_json_success(array('message' => $result['message']));
        } else {
            wp_send_json_error(array('message' => $result['message']));
        }
    }

    /**
     * Generate password reset link AJAX handler
     */
    public function ajax_generate_reset_link() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sp_reset_password')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }

        // Check if user is admin
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('ليس لديك صلاحية', 'saint-porphyrius')));
        }

        $user_id = absint($_POST['user_id']);
        $user = get_user_by('ID', $user_id);

        if (!$user) {
            wp_send_json_error(array('message' => __('المستخدم غير موجود', 'saint-porphyrius')));
        }

        // Generate password reset key
        $reset_key = get_password_reset_key($user);
        if (is_wp_error($reset_key)) {
            wp_send_json_error(array('message' => __('حدث خطأ في إنشاء رابط إعادة التعيين', 'saint-porphyrius')));
        }

        // Build reset URL
        $reset_url = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user->user_login), 'login');

        wp_send_json_success(array(
            'reset_url' => $reset_url,
            'user_login' => $user->user_login,
            'user_email' => $user->user_email,
        ));
    }

    /**
     * Generate QR token for attendance AJAX handler
     */
    public function ajax_generate_qr_token() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }

        // Must be logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('يجب تسجيل الدخول أولاً', 'saint-porphyrius')));
        }

        $event_id = absint($_POST['event_id'] ?? 0);
        $user_id = get_current_user_id();

        if (!$event_id) {
            wp_send_json_error(array('message' => __('فعالية غير صالحة', 'saint-porphyrius')));
        }

        $qr_handler = SP_QR_Attendance::get_instance();
        $result = $qr_handler->generate_token($event_id, $user_id);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Validate QR code and mark attendance AJAX handler (Admin only)
     */
    public function ajax_validate_qr_attendance() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sp_admin_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }

        // Check permissions
        if (!current_user_can('sp_manage_members') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('ليس لديك الصلاحية', 'saint-porphyrius')));
        }

        $qr_content = isset($_POST['qr_content']) ? stripslashes($_POST['qr_content']) : '';
        $status = sanitize_text_field($_POST['status'] ?? 'attended');

        if (empty($qr_content)) {
            wp_send_json_error(array('message' => __('لم يتم قراءة رمز QR', 'saint-porphyrius')));
        }

        $qr_handler = SP_QR_Attendance::get_instance();
        $result = $qr_handler->validate_and_mark($qr_content, $status, get_current_user_id());

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Get QR token status AJAX handler
     */
    public function ajax_get_qr_status() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }

        // Must be logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('يجب تسجيل الدخول أولاً', 'saint-porphyrius')));
        }

        $event_id = absint($_POST['event_id'] ?? 0);
        $user_id = get_current_user_id();

        if (!$event_id) {
            wp_send_json_error(array('message' => __('فعالية غير صالحة', 'saint-porphyrius')));
        }

        // Check if user already has attendance marked
        $attendance = SP_Attendance::get_instance();
        $existing = $attendance->get($event_id, $user_id);
        if ($existing && in_array($existing->status, array('attended', 'late'))) {
            wp_send_json_success(array(
                'status' => 'already_attended',
                'attendance_status' => $existing->status,
                'message' => __('تم تسجيل حضورك مسبقاً', 'saint-porphyrius')
            ));
        }

        // Check for active token
        $qr_handler = SP_QR_Attendance::get_instance();
        $active_token = $qr_handler->get_active_token($event_id, $user_id);

        if ($active_token) {
            $expires_in = strtotime($active_token->expires_at) - time();
            wp_send_json_success(array(
                'status' => 'active',
                'expires_in' => max(0, $expires_in),
                'expires_at' => $active_token->expires_at
            ));
        } else {
            wp_send_json_success(array(
                'status' => 'none',
                'message' => __('لا يوجد رمز نشط', 'saint-porphyrius')
            ));
        }
    }

    /**
     * Register expected attendance AJAX handler
     */
    public function ajax_register_expected_attendance() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }

        // Must be logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('يجب تسجيل الدخول أولاً', 'saint-porphyrius')));
        }

        $event_id = absint($_POST['event_id'] ?? 0);
        $user_id = get_current_user_id();

        if (!$event_id) {
            wp_send_json_error(array('message' => __('فعالية غير صالحة', 'saint-porphyrius')));
        }

        $expected_attendance = SP_Expected_Attendance::get_instance();
        $result = $expected_attendance->register($event_id, $user_id);

        if ($result['success']) {
            // Get updated list
            $registrations = $expected_attendance->get_event_registrations($event_id);
            $result['registrations'] = $registrations;
            $result['count'] = count($registrations);
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Unregister expected attendance AJAX handler
     */
    public function ajax_unregister_expected_attendance() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }

        // Must be logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('يجب تسجيل الدخول أولاً', 'saint-porphyrius')));
        }

        $event_id = absint($_POST['event_id'] ?? 0);
        $user_id = get_current_user_id();

        if (!$event_id) {
            wp_send_json_error(array('message' => __('فعالية غير صالحة', 'saint-porphyrius')));
        }

        $expected_attendance = SP_Expected_Attendance::get_instance();
        $result = $expected_attendance->unregister($event_id, $user_id);

        if ($result['success']) {
            // Get updated list
            $registrations = $expected_attendance->get_event_registrations($event_id);
            $result['registrations'] = $registrations;
            $result['count'] = count($registrations);
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Get expected attendance list AJAX handler
     */
    public function ajax_get_expected_attendance() {
        $event_id = absint($_POST['event_id'] ?? $_GET['event_id'] ?? 0);

        if (!$event_id) {
            wp_send_json_error(array('message' => __('فعالية غير صالحة', 'saint-porphyrius')));
        }

        $expected_attendance = SP_Expected_Attendance::get_instance();
        $registrations = $expected_attendance->get_event_registrations($event_id);
        
        $user_id = get_current_user_id();
        $is_registered = $user_id ? $expected_attendance->is_registered($event_id, $user_id) : false;
        $user_order = $user_id ? $expected_attendance->get_user_order($event_id, $user_id) : null;

        wp_send_json_success(array(
            'registrations' => $registrations,
            'count' => count($registrations),
            'is_registered' => $is_registered,
            'user_order' => $user_order
        ));
    }
    
    /**
     * Submit quiz AJAX handler
     */
    public function ajax_submit_quiz() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['sp_quiz_nonce'], 'sp_quiz_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('يجب تسجيل الدخول أولاً', 'saint-porphyrius')));
        }
        
        $gamification = SP_Gamification::get_instance();
        
        // Check if already completed
        if ($gamification->has_completed_story_quiz($user_id)) {
            wp_send_json_error(array('message' => __('لقد أكملت هذا الاختبار من قبل', 'saint-porphyrius')));
        }
        
        // Get question IDs
        $question_ids = explode(',', sanitize_text_field($_POST['question_ids'] ?? ''));
        
        // Collect answers
        $answers = array();
        foreach ($question_ids as $qid) {
            $answer_key = 'answer_' . $qid;
            if (isset($_POST[$answer_key])) {
                $answers[$qid] = absint($_POST[$answer_key]);
            }
        }
        
        // Validate answers
        $result = $gamification->validate_quiz_answers($user_id, $answers);
        
        if ($result['passed']) {
            // Award points
            $award_result = $gamification->award_story_quiz($user_id);
            $settings = $gamification->get_settings();
            
            wp_send_json_success(array(
                'passed' => true,
                'correct' => $result['correct'],
                'total' => $result['total'],
                'percentage' => $result['percentage'],
                'points_awarded' => $settings['story_quiz_points'],
                'message' => sprintf(
                    __('أحسنت! أجبت على %d من %d أسئلة صحيحة وحصلت على %d نقطة!', 'saint-porphyrius'),
                    $result['correct'],
                    $result['total'],
                    $settings['story_quiz_points']
                ),
            ));
        } else {
            wp_send_json_success(array(
                'passed' => false,
                'correct' => $result['correct'],
                'total' => $result['total'],
                'percentage' => $result['percentage'],
                'message' => sprintf(
                    __('أجبت على %d من %d أسئلة صحيحة. تحتاج 3 إجابات صحيحة على الأقل. حاول مرة أخرى!', 'saint-porphyrius'),
                    $result['correct'],
                    $result['total']
                ),
            ));
        }
    }
    
    /**
     * Submit service instructions quiz AJAX handler
     */
    public function ajax_submit_service_quiz() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['sp_service_quiz_nonce'], 'sp_service_quiz_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('يجب تسجيل الدخول أولاً', 'saint-porphyrius')));
        }
        
        $gamification = SP_Gamification::get_instance();
        
        // Check if max attempts reached
        if ($gamification->has_completed_service_instructions($user_id)) {
            wp_send_json_error(array('message' => __('لقد أكملت هذا الاختبار الحد الأقصى من المرات', 'saint-porphyrius')));
        }
        
        // Get question IDs
        $question_ids = explode(',', sanitize_text_field($_POST['question_ids'] ?? ''));
        
        // Collect answers
        $answers = array();
        foreach ($question_ids as $qid) {
            $answer_key = 'answer_' . $qid;
            if (isset($_POST[$answer_key])) {
                $answers[$qid] = absint($_POST[$answer_key]);
            }
        }
        
        // Validate answers
        $result = $gamification->validate_service_instructions_answers($user_id, $answers);
        
        if ($result['passed']) {
            // Award points
            $award_result = $gamification->award_service_instructions($user_id);
            $settings = $gamification->get_settings();
            
            wp_send_json_success(array(
                'passed' => true,
                'correct' => $result['correct'],
                'total' => $result['total'],
                'percentage' => $result['percentage'],
                'points_awarded' => $settings['service_instructions_points'],
                'message' => sprintf(
                    __('أحسنت! أجبت على %d من %d أسئلة صحيحة وحصلت على %d نقطة!', 'saint-porphyrius'),
                    $result['correct'],
                    $result['total'],
                    $settings['service_instructions_points']
                ),
            ));
        } else {
            wp_send_json_success(array(
                'passed' => false,
                'correct' => $result['correct'],
                'total' => $result['total'],
                'percentage' => $result['percentage'],
                'message' => sprintf(
                    __('أجبت على %d من %d أسئلة صحيحة. تحتاج 3 إجابات صحيحة على الأقل. حاول مرة أخرى!', 'saint-porphyrius'),
                    $result['correct'],
                    $result['total']
                ),
            ));
        }
    }
    
    /**
     * Block member AJAX handler (Admin only)
     */
    public function ajax_block_member() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sp_admin_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }
        
        // Check permissions
        if (!current_user_can('sp_manage_members') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('ليس لديك الصلاحية', 'saint-porphyrius')));
        }
        
        $member_id = intval($_POST['member_id']);
        
        if (!$member_id) {
            wp_send_json_error(array('message' => __('معرف العضو غير صحيح', 'saint-porphyrius')));
        }
        
        // Use forbidden system to block the user
        $forbidden = SP_Forbidden::get_instance();
        
        global $wpdb;
        $status_table = $wpdb->prefix . 'sp_forbidden_status';
        
        // Set red card status
        $wpdb->replace(
            $status_table,
            array(
                'user_id' => $member_id,
                'forbidden_remaining' => 99,
                'consecutive_absences' => 99,
                'card_status' => 'red',
                'blocked_at' => current_time('mysql'),
                'unblocked_at' => null,
            ),
            array('%d', '%d', '%d', '%s', '%s', '%s')
        );
        
        wp_send_json_success(array(
            'message' => __('تم حظر العضو بنجاح', 'saint-porphyrius')
        ));
    }
    
    /**
     * Delete member AJAX handler (Admin only)
     */
    public function ajax_delete_member() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sp_admin_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }
        
        // Check permissions
        if (!current_user_can('sp_manage_members') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('ليس لديك الصلاحية', 'saint-porphyrius')));
        }
        
        $member_id = intval($_POST['member_id']);
        
        if (!$member_id) {
            wp_send_json_error(array('message' => __('معرف العضو غير صحيح', 'saint-porphyrius')));
        }
        
        // Don't allow deleting yourself
        if ($member_id === get_current_user_id()) {
            wp_send_json_error(array('message' => __('لا يمكن حذف حسابك الخاص', 'saint-porphyrius')));
        }
        
        // Delete user and all their data
        require_once(ABSPATH . 'wp-admin/includes/user.php');
        
        $deleted = wp_delete_user($member_id);
        
        if (!$deleted) {
            wp_send_json_error(array('message' => __('فشل حذف العضو', 'saint-porphyrius')));
        }
        
        wp_send_json_success(array(
            'message' => __('تم حذف العضو بنجاح', 'saint-porphyrius')
        ));
    }
    
    /**
     * Manage forbidden status AJAX handler
     */
    public function ajax_manage_forbidden_status() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sp_admin_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }
        
        // Check permissions
        if (!current_user_can('sp_manage_members') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('ليس لديك الصلاحية', 'saint-porphyrius')));
        }
        
        $member_id = intval($_POST['member_id']);
        $forbidden_action = sanitize_text_field($_POST['forbidden_action']);
        
        if (!$member_id) {
            wp_send_json_error(array('message' => __('معرف العضو غير صحيح', 'saint-porphyrius')));
        }
        
        $forbidden_handler = SP_Forbidden::get_instance();
        $admin_id = get_current_user_id();
        
        try {
            if ($forbidden_action === 'unblock') {
                $forbidden_handler->unblock_user($member_id, $admin_id);
                $message = __('تم إزالة الحظر بنجاح', 'saint-porphyrius');
            } elseif ($forbidden_action === 'reset') {
                $forbidden_handler->reset_user_status($member_id, $admin_id);
                $message = __('تم إزالة الإنذار بنجاح', 'saint-porphyrius');
            } elseif ($forbidden_action === 'remove_forbidden') {
                $forbidden_handler->remove_forbidden_penalty($member_id, $admin_id);
                $message = __('تم إزالة عقوبة الحرمان بنجاح', 'saint-porphyrius');
            } else {
                wp_send_json_error(array('message' => __('إجراء غير معروف', 'saint-porphyrius')));
            }
            
            wp_send_json_success(array('message' => $message));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    // ==========================================
    // BUS BOOKING AJAX HANDLERS
    // ==========================================
    
    /**
     * Get bus seat map AJAX handler
     */
    public function ajax_get_bus_seat_map() {
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }
        
        $event_bus_id = absint($_POST['event_bus_id']);
        if (!$event_bus_id) {
            wp_send_json_error(array('message' => __('معرف الباص غير صحيح', 'saint-porphyrius')));
        }
        
        $bus_handler = SP_Bus::get_instance();
        $seat_map = $bus_handler->get_seat_map($event_bus_id);
        
        if (!$seat_map) {
            wp_send_json_error(array('message' => __('الباص غير موجود', 'saint-porphyrius')));
        }
        
        // Add current user booking info
        $user_id = get_current_user_id();
        $seat_map['current_user_id'] = $user_id;
        
        // Get event_id from bus
        $bus = $bus_handler->get_event_bus($event_bus_id);
        if ($bus) {
            $seat_map['event_id'] = $bus->event_id;
            $user_booking = $bus_handler->get_user_event_booking($bus->event_id, $user_id);
            $seat_map['user_booking'] = $user_booking;
        }
        
        wp_send_json_success($seat_map);
    }
    
    /**
     * Book bus seat AJAX handler
     */
    public function ajax_book_bus_seat() {
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('يجب تسجيل الدخول أولاً', 'saint-porphyrius')));
        }
        
        $event_bus_id = absint($_POST['event_bus_id']);
        $seat_row = absint($_POST['seat_row']);
        $seat_number = absint($_POST['seat_number']);
        
        if (!$event_bus_id || !$seat_row || !$seat_number) {
            wp_send_json_error(array('message' => __('بيانات غير صحيحة', 'saint-porphyrius')));
        }
        
        $bus_handler = SP_Bus::get_instance();
        $result = $bus_handler->book_seat($event_bus_id, $user_id, $seat_row, $seat_number);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Cancel bus booking AJAX handler
     */
    public function ajax_cancel_bus_booking() {
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('يجب تسجيل الدخول أولاً', 'saint-porphyrius')));
        }
        
        $booking_id = absint($_POST['booking_id']);
        if (!$booking_id) {
            wp_send_json_error(array('message' => __('معرف الحجز غير صحيح', 'saint-porphyrius')));
        }
        
        $bus_handler = SP_Bus::get_instance();
        $result = $bus_handler->cancel_booking($booking_id, $user_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Get event buses AJAX handler
     */
    public function ajax_get_event_buses() {
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }
        
        $event_id = absint($_POST['event_id']);
        if (!$event_id) {
            wp_send_json_error(array('message' => __('معرف الفعالية غير صحيح', 'saint-porphyrius')));
        }
        
        $bus_handler = SP_Bus::get_instance();
        $buses = $bus_handler->get_event_buses($event_id, true);
        $stats = $bus_handler->get_event_bus_stats($event_id);
        
        wp_send_json_success(array(
            'buses' => $buses,
            'stats' => $stats,
        ));
    }
    
    /**
     * Add event bus AJAX handler (Admin only)
     */
    public function ajax_add_event_bus() {
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('ليس لديك الصلاحية', 'saint-porphyrius')));
        }
        
        $bus_handler = SP_Bus::get_instance();
        $result = $bus_handler->add_event_bus($_POST);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Remove event bus AJAX handler (Admin only)
     */
    public function ajax_remove_event_bus() {
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('ليس لديك الصلاحية', 'saint-porphyrius')));
        }
        
        $event_bus_id = absint($_POST['event_bus_id']);
        if (!$event_bus_id) {
            wp_send_json_error(array('message' => __('معرف الباص غير صحيح', 'saint-porphyrius')));
        }
        
        $bus_handler = SP_Bus::get_instance();
        $result = $bus_handler->remove_event_bus($event_bus_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Check in bus passenger AJAX handler (Admin only)
     */
    public function ajax_checkin_bus_passenger() {
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('ليس لديك الصلاحية', 'saint-porphyrius')));
        }
        
        $booking_id = absint($_POST['booking_id']);
        if (!$booking_id) {
            wp_send_json_error(array('message' => __('معرف الحجز غير صحيح', 'saint-porphyrius')));
        }
        
        $bus_handler = SP_Bus::get_instance();
        $result = $bus_handler->checkin_booking($booking_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Move bus seat booking AJAX handler (Admin only)
     */
    public function ajax_move_bus_seat() {
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }
        
        if (!current_user_can('sp_manage_members') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('ليس لديك الصلاحية', 'saint-porphyrius')));
        }
        
        $booking_id = absint($_POST['booking_id']);
        $new_row = absint($_POST['new_row']);
        $new_seat = absint($_POST['new_seat']);
        
        if (!$booking_id || !$new_row || !$new_seat) {
            wp_send_json_error(array('message' => __('بيانات غير صحيحة', 'saint-porphyrius')));
        }
        
        $bus_handler = SP_Bus::get_instance();
        $result = $bus_handler->move_seat($booking_id, $new_row, $new_seat);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }

    // ==========================================
    // POINT SHARING AJAX HANDLERS
    // ==========================================

    /**
     * Search members for point sharing
     */
    public function ajax_search_members_for_sharing() {
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('يجب تسجيل الدخول أولاً', 'saint-porphyrius')));
        }

        $search = sanitize_text_field($_POST['search'] ?? '');
        if (mb_strlen($search) < 2) {
            wp_send_json_error(array('message' => __('أدخل حرفين على الأقل للبحث', 'saint-porphyrius')));
        }

        global $wpdb;

        // Search by WP user fields
        $wp_users = get_users(array(
            'role__in' => array('sp_member', 'sp_church_admin'),
            'search' => '*' . $search . '*',
            'search_columns' => array('user_login', 'display_name'),
            'number' => 15,
            'exclude' => array($user_id),
        ));

        $found_ids = array();
        foreach ($wp_users as $u) {
            $found_ids[] = $u->ID;
        }

        // Also search by Arabic meta fields
        $meta_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT user_id FROM {$wpdb->usermeta}
             WHERE meta_key IN ('first_name', 'sp_middle_name', 'sp_name_ar')
             AND meta_value LIKE %s
             AND user_id != %d
             LIMIT 15",
            '%' . $wpdb->esc_like($search) . '%',
            $user_id
        ));

        $all_ids = array_unique(array_merge($found_ids, $meta_ids));

        $results = array();
        $points_handler = SP_Points::get_instance();

        foreach (array_slice($all_ids, 0, 10) as $mid) {
            $member = get_userdata($mid);
            if (!$member) continue;

            // Only include sp_member or sp_church_admin roles
            $roles = $member->roles;
            if (!in_array('sp_member', $roles) && !in_array('sp_church_admin', $roles)) {
                continue;
            }

            $first = $member->first_name;
            $middle = get_user_meta($mid, 'sp_middle_name', true);
            $name = trim($first . ' ' . $middle) ?: $member->display_name;

            $results[] = array(
                'id' => $mid,
                'name' => $name,
                'initial' => mb_substr($first ?: $name, 0, 1),
                'gender' => get_user_meta($mid, 'sp_gender', true) ?: 'male',
                'points' => $points_handler->get_balance($mid),
            );
        }

        wp_send_json_success($results);
    }

    /**
     * Preview point share (rank/balance impact)
     */
    public function ajax_preview_share_points() {
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('يجب تسجيل الدخول أولاً', 'saint-porphyrius')));
        }

        $recipient_id = absint($_POST['recipient_id'] ?? 0);
        $points = absint($_POST['points'] ?? 0);

        if (!$recipient_id || !$points) {
            wp_send_json_error(array('message' => __('بيانات غير صحيحة', 'saint-porphyrius')));
        }

        $sharing_handler = SP_Point_Sharing::get_instance();
        $preview = $sharing_handler->preview_share($user_id, $recipient_id, $points);

        wp_send_json_success($preview);
    }

    /**
     * Execute point share
     */
    public function ajax_share_points() {
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('يجب تسجيل الدخول أولاً', 'saint-porphyrius')));
        }

        $recipient_id = absint($_POST['recipient_id'] ?? 0);
        $points = absint($_POST['points'] ?? 0);
        $message = sanitize_text_field($_POST['message'] ?? '');

        if (!$recipient_id || !$points) {
            wp_send_json_error(array('message' => __('بيانات غير صحيحة', 'saint-porphyrius')));
        }

        $sharing_handler = SP_Point_Sharing::get_instance();
        $result = $sharing_handler->share_points($user_id, $recipient_id, $points, $message);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success($result);
    }
    // =========================================================================
    // CHRISTIAN QUIZ SYSTEM HANDLERS
    // =========================================================================

    /**
     * Save quiz category (create or update)
     */
    public function ajax_quiz_save_category() {
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => 'خطأ في التحقق'));
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $quiz = SP_Quiz::get_instance();
        $id = absint($_POST['id'] ?? 0);

        $data = array(
            'name_ar'        => sanitize_text_field($_POST['name_ar'] ?? ''),
            'name_en'        => sanitize_text_field($_POST['name_en'] ?? ''),
            'description_ar' => sanitize_textarea_field($_POST['description_ar'] ?? ''),
            'icon'           => sanitize_text_field($_POST['icon'] ?? '📖'),
            'color'          => sanitize_hex_color($_POST['color'] ?? '#3B82F6'),
            'sort_order'     => absint($_POST['sort_order'] ?? 0),
        );

        if ($id) {
            $result = $quiz->update_category($id, $data);
        } else {
            $result = $quiz->create_category($data);
        }

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => 'تم الحفظ بنجاح', 'id' => $id ?: $result));
    }

    /**
     * Delete quiz category
     */
    public function ajax_quiz_delete_category() {
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => 'خطأ في التحقق'));
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $id = absint($_POST['id'] ?? 0);
        if (!$id) {
            wp_send_json_error(array('message' => 'معرف غير صالح'));
        }

        $quiz = SP_Quiz::get_instance();
        $quiz->delete_category($id);
        wp_send_json_success(array('message' => 'تم الحذف بنجاح'));
    }

    /**
     * Save quiz content (create or update)
     */
    public function ajax_quiz_save_content() {
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => 'خطأ في التحقق'));
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $quiz = SP_Quiz::get_instance();
        $id = absint($_POST['content_id'] ?? 0);

        $data = array(
            'category_id'  => absint($_POST['category_id'] ?? 0),
            'title_ar'     => sanitize_text_field($_POST['title_ar'] ?? ''),
            'title_en'     => sanitize_text_field($_POST['title_en'] ?? ''),
            'content_type' => sanitize_text_field($_POST['content_type'] ?? 'text'),
            'raw_input'    => wp_kses_post($_POST['raw_input'] ?? ''),
            'youtube_url'  => esc_url_raw($_POST['youtube_url'] ?? ''),
            'youtube_transcript' => wp_kses_post($_POST['youtube_transcript'] ?? ''),
            'max_points'   => absint($_POST['max_points'] ?? 100),
            'admin_notes'  => sanitize_textarea_field($_POST['admin_notes'] ?? ''),
        );

        if ($id) {
            $result = $quiz->update_content($id, $data);
            $content_id = $id;
        } else {
            $result = $quiz->create_content($data);
            $content_id = $result;
        }

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => 'تم الحفظ بنجاح', 'content_id' => $content_id));
    }

    /**
     * Delete quiz content
     */
    public function ajax_quiz_delete_content() {
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => 'خطأ في التحقق'));
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $id = absint($_POST['content_id'] ?? 0);
        if (!$id) {
            wp_send_json_error(array('message' => 'معرف غير صالح'));
        }

        $quiz = SP_Quiz::get_instance();
        $quiz->delete_content($id);
        wp_send_json_success(array('message' => 'تم الحذف بنجاح'));
    }

    /**
     * Trigger AI generation (format content + generate questions)
     */
    public function ajax_quiz_ai_generate() {
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => 'خطأ في التحقق'));
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $content_id = absint($_POST['content_id'] ?? 0);
        $admin_notes = sanitize_textarea_field($_POST['admin_notes'] ?? '');
        $num_questions = absint($_POST['num_questions'] ?? 50);

        if (!$content_id) {
            wp_send_json_error(array('message' => 'معرف المحتوى غير صالح'));
        }

        $ai = SP_Quiz_AI::get_instance();
        $result = $ai->process_and_generate($content_id, $admin_notes, $num_questions);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array(
            'message'         => sprintf('تم إنشاء %d سؤال بنجاح', $result['questions_saved']),
            'questions_count'  => $result['questions_saved'],
            'total_tokens'    => $result['total_tokens'],
        ));
    }

    /**
     * Regenerate questions with updated instructions
     */
    public function ajax_quiz_ai_regenerate() {
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => 'خطأ في التحقق'));
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $content_id = absint($_POST['content_id'] ?? 0);
        $admin_instructions = sanitize_textarea_field($_POST['admin_instructions'] ?? '');
        $num_questions = absint($_POST['num_questions'] ?? 50);

        $ai = SP_Quiz_AI::get_instance();
        $result = $ai->regenerate_questions($content_id, $admin_instructions, $num_questions);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array(
            'message'         => sprintf('تم إعادة إنشاء %d سؤال بنجاح', $result['questions_saved']),
            'questions_count'  => $result['questions_saved'],
            'tokens_used'     => $result['tokens_used'],
        ));
    }

    /**
     * Approve AI-generated content and questions
     */
    public function ajax_quiz_approve() {
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => 'خطأ في التحقق'));
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $content_id = absint($_POST['content_id'] ?? 0);
        $quiz = SP_Quiz::get_instance();
        $quiz->update_content($content_id, array('status' => 'approved'));
        wp_send_json_success(array('message' => 'تم الموافقة بنجاح'));
    }

    /**
     * Publish approved content
     */
    public function ajax_quiz_publish() {
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => 'خطأ في التحقق'));
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $content_id = absint($_POST['content_id'] ?? 0);
        $quiz = SP_Quiz::get_instance();
        $content = $quiz->get_content($content_id);

        if (!$content || !in_array($content->status, array('approved', 'published'))) {
            wp_send_json_error(array('message' => 'يجب الموافقة على المحتوى أولاً'));
        }

        $quiz->update_content($content_id, array('status' => 'published'));
        wp_send_json_success(array('message' => 'تم النشر بنجاح'));
    }

    /**
     * Update a single question
     */
    public function ajax_quiz_update_question() {
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => 'خطأ في التحقق'));
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $question_id = absint($_POST['question_id'] ?? 0);
        if (!$question_id) {
            wp_send_json_error(array('message' => 'معرف السؤال غير صالح'));
        }

        $options = json_decode(stripslashes($_POST['options'] ?? '[]'), true);

        $data = array(
            'question_text'        => sanitize_text_field($_POST['question_text'] ?? ''),
            'options'              => $options,
            'correct_answer_index' => absint($_POST['correct_answer_index'] ?? 0),
            'explanation'          => sanitize_text_field($_POST['explanation'] ?? ''),
            'difficulty'           => sanitize_text_field($_POST['difficulty'] ?? 'medium'),
            'is_active'            => !empty($_POST['is_active']) ? 1 : 0,
        );

        $quiz = SP_Quiz::get_instance();
        $quiz->update_question($question_id, $data);
        wp_send_json_success(array('message' => 'تم تحديث السؤال بنجاح'));
    }

    /**
     * Delete a question
     */
    public function ajax_quiz_delete_question() {
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => 'خطأ في التحقق'));
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $question_id = absint($_POST['question_id'] ?? 0);
        $quiz = SP_Quiz::get_instance();
        $quiz->delete_question($question_id);
        wp_send_json_success(array('message' => 'تم حذف السؤال'));
    }

    /**
     * Update quiz system settings
     */
    public function ajax_quiz_update_settings() {
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => 'خطأ في التحقق'));
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $quiz = SP_Quiz::get_instance();
        $settings = array(
            'openai_api_key'      => sanitize_text_field($_POST['openai_api_key'] ?? ''),
            'ai_model'            => sanitize_text_field($_POST['ai_model'] ?? 'gpt-4o'),
            'questions_per_quiz'  => absint($_POST['questions_per_quiz'] ?? 50),
            'default_max_points'  => absint($_POST['default_max_points'] ?? 100),
            'passing_percentage'  => absint($_POST['passing_percentage'] ?? 60),
            'enabled'             => !empty($_POST['enabled']) ? 1 : 0,
        );

        $quiz->update_settings($settings);
        wp_send_json_success(array('message' => 'تم حفظ الإعدادات بنجاح'));
    }

    /**
     * Get YouTube video info
     */
    public function ajax_quiz_get_youtube_info() {
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => 'خطأ في التحقق'));
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }

        $url = esc_url_raw($_POST['youtube_url'] ?? '');
        $ai = SP_Quiz_AI::get_instance();
        $info = $ai->get_youtube_transcript($url);

        if (is_wp_error($info)) {
            wp_send_json_error(array('message' => $info->get_error_message()));
        }

        wp_send_json_success($info);
    }

    /**
     * Submit quiz attempt (user-facing)
     */
    public function ajax_quiz_submit_attempt() {
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => 'خطأ في التحقق'));
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'يجب تسجيل الدخول أولاً'));
        }

        $content_id = absint($_POST['content_id'] ?? 0);
        $raw_answers = json_decode(stripslashes($_POST['answers'] ?? '{}'), true);

        if (!$content_id || empty($raw_answers)) {
            wp_send_json_error(array('message' => 'بيانات غير مكتملة'));
        }

        // Convert string keys to int
        $answers = array();
        foreach ($raw_answers as $qid => $answer) {
            $answers[absint($qid)] = intval($answer);
        }

        $quiz = SP_Quiz::get_instance();
        $result = $quiz->submit_attempt($user_id, $content_id, $answers);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success($result);
    }

    /**
     * Get quiz content details for user
     */
    public function ajax_quiz_get_content() {
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => 'خطأ في التحقق'));
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'يجب تسجيل الدخول أولاً'));
        }

        $content_id = absint($_POST['content_id'] ?? 0);
        $quiz = SP_Quiz::get_instance();
        $content = $quiz->get_content($content_id);

        if (!$content || $content->status !== 'published') {
            wp_send_json_error(array('message' => 'هذا الاختبار غير متاح'));
        }

        $questions = $quiz->get_questions($content_id);
        $best_attempt = $quiz->get_best_attempt($user_id, $content_id);
        $attempt_count = $quiz->get_attempt_count($user_id, $content_id);

        // Strip correct answers for the user view
        $safe_questions = array();
        foreach ($questions as $q) {
            $options = json_decode($q->options, true);
            $safe_options = array();
            foreach ($options as $opt) {
                $safe_options[] = array('text' => $opt['text']);
            }
            $safe_questions[] = array(
                'id'       => $q->id,
                'question' => $q->question_text,
                'type'     => $q->question_type,
                'options'  => $safe_options,
            );
        }

        wp_send_json_success(array(
            'content'       => array(
                'id'        => $content->id,
                'title'     => $content->title_ar,
                'formatted' => $content->ai_formatted_content,
                'youtube'   => $content->youtube_url,
                'max_points'=> $content->max_points,
                'category'  => $content->category_name,
            ),
            'questions'     => $safe_questions,
            'best_attempt'  => $best_attempt ? array(
                'score'      => $best_attempt->score,
                'total'      => $best_attempt->total_questions,
                'percentage' => $best_attempt->percentage,
                'points'     => $best_attempt->points_awarded,
            ) : null,
            'attempt_count' => $attempt_count,
        ));
    }
}

// Initialize
SP_Ajax::get_instance();
