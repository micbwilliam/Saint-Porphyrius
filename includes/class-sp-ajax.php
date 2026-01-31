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
        
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        
        $user_handler = SP_User::get_instance();
        $result = $user_handler->login_user($email, $password);
        
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
}

// Initialize
SP_Ajax::get_instance();
