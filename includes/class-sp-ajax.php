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
        if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
            wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
        }
        
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(array('message' => __('يجب تسجيل الدخول', 'saint-porphyrius')));
        }
        
        $user_handler = SP_User::get_instance();
        $result = $user_handler->update_user_profile($user_id, $_POST);
        
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
}

// Initialize
SP_Ajax::get_instance();
