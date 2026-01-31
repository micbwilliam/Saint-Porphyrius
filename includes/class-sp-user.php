<?php
/**
 * Saint Porphyrius - User Handler
 * Handles user authentication and profile
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_User {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hooks are handled in SP_Ajax class
    }
    
    /**
     * Login user
     */
    public function login_user($email, $password) {
        // Check if user exists
        $user = get_user_by('email', $email);
        
        if (!$user) {
            // Check if user is pending
            global $wpdb;
            $table_name = $wpdb->prefix . 'sp_pending_users';
            
            $pending = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE email = %s",
                $email
            ));
            
            if ($pending) {
                if ($pending->status === 'pending') {
                    return new WP_Error('pending', __('حسابك في انتظار موافقة الإدارة', 'saint-porphyrius'));
                } elseif ($pending->status === 'rejected') {
                    return new WP_Error('rejected', __('تم رفض طلب التسجيل الخاص بك', 'saint-porphyrius'));
                }
            }
            
            return new WP_Error('invalid_credentials', __('البريد الإلكتروني أو كلمة المرور غير صحيحة', 'saint-porphyrius'));
        }
        
        // Verify password
        if (!wp_check_password($password, $user->user_pass, $user->ID)) {
            return new WP_Error('invalid_credentials', __('البريد الإلكتروني أو كلمة المرور غير صحيحة', 'saint-porphyrius'));
        }
        
        // Check if user is a church member
        if (!in_array('sp_member', (array) $user->roles) && 
            !in_array('sp_church_admin', (array) $user->roles) && 
            !in_array('administrator', (array) $user->roles)) {
            return new WP_Error('unauthorized', __('ليس لديك صلاحية الوصول', 'saint-porphyrius'));
        }
        
        // Log user in
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);
        
        // Update last login
        update_user_meta($user->ID, 'sp_last_login', current_time('mysql'));
        
        return array(
            'success' => true,
            'user_id' => $user->ID,
            'redirect' => home_url('/app'),
        );
    }
    
    /**
     * Get user profile data
     */
    public function get_user_profile($user_id) {
        $user = get_userdata($user_id);
        
        if (!$user) {
            return new WP_Error('not_found', __('المستخدم غير موجود', 'saint-porphyrius'));
        }
        
        return array(
            'id' => $user->ID,
            'email' => $user->user_email,
            'first_name' => $user->first_name,
            'middle_name' => get_user_meta($user_id, 'sp_middle_name', true),
            'last_name' => $user->last_name,
            'phone' => get_user_meta($user_id, 'sp_phone', true),
            'home_address' => get_user_meta($user_id, 'sp_home_address', true),
            'church_name' => get_user_meta($user_id, 'sp_church_name', true),
            'confession_father' => get_user_meta($user_id, 'sp_confession_father', true),
            'job_or_college' => get_user_meta($user_id, 'sp_job_or_college', true),
            'current_church_service' => get_user_meta($user_id, 'sp_current_church_service', true),
            'church_family' => get_user_meta($user_id, 'sp_church_family', true),
            'church_family_servant' => get_user_meta($user_id, 'sp_church_family_servant', true),
            'facebook_link' => get_user_meta($user_id, 'sp_facebook_link', true),
            'instagram_link' => get_user_meta($user_id, 'sp_instagram_link', true),
            'avatar_url' => get_avatar_url($user_id, array('size' => 200)),
        );
    }
    
    /**
     * Update user profile
     */
    public function update_user_profile($user_id, $data) {
        $user = get_userdata($user_id);
        
        if (!$user) {
            return new WP_Error('not_found', __('المستخدم غير موجود', 'saint-porphyrius'));
        }
        
        // Update basic info
        $update_data = array('ID' => $user_id);
        
        if (!empty($data['first_name'])) {
            $update_data['first_name'] = sanitize_text_field($data['first_name']);
        }
        
        if (!empty($data['last_name'])) {
            $update_data['last_name'] = sanitize_text_field($data['last_name']);
        }
        
        wp_update_user($update_data);
        
        // Update meta
        $meta_fields = array(
            'middle_name', 'phone', 'home_address', 'church_name',
            'confession_father', 'job_or_college', 'current_church_service',
            'church_family', 'church_family_servant', 'facebook_link', 'instagram_link'
        );
        
        foreach ($meta_fields as $field) {
            if (isset($data[$field])) {
                update_user_meta($user_id, 'sp_' . $field, sanitize_text_field($data[$field]));
            }
        }
        
        return array(
            'success' => true,
            'message' => __('تم تحديث الملف الشخصي بنجاح', 'saint-porphyrius'),
        );
    }
    
    /**
     * Check if user is logged in and approved
     */
    public function is_user_authorized() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user = wp_get_current_user();
        
        return in_array('sp_member', (array) $user->roles) || 
               in_array('sp_church_admin', (array) $user->roles) || 
               in_array('administrator', (array) $user->roles);
    }
    
    /**
     * Logout user
     */
    public function logout_user() {
        wp_logout();
        
        return array(
            'success' => true,
            'redirect' => home_url('/app'),
        );
    }
}

// Initialize
SP_User::get_instance();
