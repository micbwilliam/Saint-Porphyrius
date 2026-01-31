<?php
/**
 * Saint Porphyrius - Registration Handler
 * Handles user registration and pending approval
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Registration {
    
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
     * Register a new pending user
     */
    public function register_user($data) {
        global $wpdb;
        
        // Validate required fields
        $required_fields = array(
            'first_name', 'middle_name', 'last_name', 'email', 'password',
            'phone', 'home_address', 'church_name', 'confession_father',
            'job_or_college', 'current_church_service', 'church_family',
            'church_family_servant'
        );
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', sprintf(__('الحقل %s مطلوب', 'saint-porphyrius'), $field));
            }
        }
        
        // Check if email already exists
        $existing_user = get_user_by('email', $data['email']);
        if ($existing_user) {
            return new WP_Error('email_exists', __('البريد الإلكتروني مسجل بالفعل', 'saint-porphyrius'));
        }
        
        // Check if email exists in pending users
        $table_name = $wpdb->prefix . 'sp_pending_users';
        $existing_pending = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE email = %s",
            $data['email']
        ));
        
        if ($existing_pending) {
            return new WP_Error('email_pending', __('هذا البريد الإلكتروني في انتظار الموافقة', 'saint-porphyrius'));
        }
        
        // Validate email format
        if (!is_email($data['email'])) {
            return new WP_Error('invalid_email', __('البريد الإلكتروني غير صحيح', 'saint-porphyrius'));
        }
        
        // Hash password
        $hashed_password = wp_hash_password($data['password']);
        
        // Insert into pending users table
        $result = $wpdb->insert(
            $table_name,
            array(
                'first_name' => sanitize_text_field($data['first_name']),
                'middle_name' => sanitize_text_field($data['middle_name']),
                'last_name' => sanitize_text_field($data['last_name']),
                'email' => sanitize_email($data['email']),
                'password' => $hashed_password,
                'phone' => sanitize_text_field($data['phone']),
                'home_address' => sanitize_textarea_field($data['home_address']),
                'church_name' => sanitize_text_field($data['church_name']),
                'confession_father' => sanitize_text_field($data['confession_father']),
                'job_or_college' => sanitize_text_field($data['job_or_college']),
                'current_church_service' => sanitize_text_field($data['current_church_service']),
                'church_family' => sanitize_text_field($data['church_family']),
                'church_family_servant' => sanitize_text_field($data['church_family_servant']),
                'facebook_link' => esc_url_raw($data['facebook_link'] ?? ''),
                'instagram_link' => esc_url_raw($data['instagram_link'] ?? ''),
                'status' => 'pending',
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('حدث خطأ أثناء التسجيل', 'saint-porphyrius'));
        }
        
        $pending_user_id = $wpdb->insert_id;
        
        // Notify admins
        $this->notify_admins_new_registration($pending_user_id, $data);
        
        return array(
            'success' => true,
            'message' => __('تم التسجيل بنجاح، في انتظار موافقة الإدارة', 'saint-porphyrius'),
            'pending_id' => $pending_user_id,
        );
    }
    
    /**
     * Approve a pending user
     */
    public function approve_user($pending_id, $approved_by) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sp_pending_users';
        
        // Get pending user data
        $pending_user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND status = 'pending'",
            $pending_id
        ));
        
        if (!$pending_user) {
            return new WP_Error('not_found', __('المستخدم غير موجود', 'saint-porphyrius'));
        }
        
        // Create WordPress user
        $user_id = wp_insert_user(array(
            'user_login' => $pending_user->email,
            'user_email' => $pending_user->email,
            'user_pass' => '', // Password is already hashed
            'first_name' => $pending_user->first_name,
            'last_name' => $pending_user->last_name,
            'role' => 'sp_member',
        ));
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        // Set the hashed password directly
        global $wpdb;
        $wpdb->update(
            $wpdb->users,
            array('user_pass' => $pending_user->password),
            array('ID' => $user_id)
        );
        
        // Save user meta
        update_user_meta($user_id, 'sp_middle_name', $pending_user->middle_name);
        update_user_meta($user_id, 'sp_phone', $pending_user->phone);
        update_user_meta($user_id, 'sp_home_address', $pending_user->home_address);
        update_user_meta($user_id, 'sp_church_name', $pending_user->church_name);
        update_user_meta($user_id, 'sp_confession_father', $pending_user->confession_father);
        update_user_meta($user_id, 'sp_job_or_college', $pending_user->job_or_college);
        update_user_meta($user_id, 'sp_current_church_service', $pending_user->current_church_service);
        update_user_meta($user_id, 'sp_church_family', $pending_user->church_family);
        update_user_meta($user_id, 'sp_church_family_servant', $pending_user->church_family_servant);
        update_user_meta($user_id, 'sp_facebook_link', $pending_user->facebook_link);
        update_user_meta($user_id, 'sp_instagram_link', $pending_user->instagram_link);
        update_user_meta($user_id, 'sp_approved_at', current_time('mysql'));
        update_user_meta($user_id, 'sp_approved_by', $approved_by);
        
        // Update pending user status
        $wpdb->update(
            $table_name,
            array(
                'status' => 'approved',
                'approved_at' => current_time('mysql'),
                'approved_by' => $approved_by,
            ),
            array('id' => $pending_id),
            array('%s', '%s', '%d'),
            array('%d')
        );
        
        // Send approval email
        $this->send_approval_email($pending_user);
        
        return array(
            'success' => true,
            'message' => __('تمت الموافقة على المستخدم بنجاح', 'saint-porphyrius'),
            'user_id' => $user_id,
        );
    }
    
    /**
     * Reject a pending user
     */
    public function reject_user($pending_id, $rejected_by, $reason = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sp_pending_users';
        
        // Get pending user data
        $pending_user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND status = 'pending'",
            $pending_id
        ));
        
        if (!$pending_user) {
            return new WP_Error('not_found', __('المستخدم غير موجود', 'saint-porphyrius'));
        }
        
        // Update status to rejected
        $wpdb->update(
            $table_name,
            array('status' => 'rejected'),
            array('id' => $pending_id),
            array('%s'),
            array('%d')
        );
        
        // Send rejection email
        $this->send_rejection_email($pending_user, $reason);
        
        return array(
            'success' => true,
            'message' => __('تم رفض المستخدم', 'saint-porphyrius'),
        );
    }
    
    /**
     * Get all pending users
     */
    public function get_pending_users($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => 'pending',
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 20,
            'offset' => 0,
        );
        
        $args = wp_parse_args($args, $defaults);
        $table_name = $wpdb->prefix . 'sp_pending_users';
        
        $query = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE status = %s ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d",
            $args['status'],
            $args['limit'],
            $args['offset']
        );
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Get pending users count
     */
    public function get_pending_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sp_pending_users';
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'");
    }
    
    /**
     * Notify admins of new registration
     */
    private function notify_admins_new_registration($pending_id, $data) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = sprintf(__('[%s] طلب تسجيل جديد', 'saint-porphyrius'), $site_name);
        
        $message = sprintf(
            __("طلب تسجيل جديد:\n\nالاسم: %s %s %s\nالبريد الإلكتروني: %s\nالهاتف: %s\nالكنيسة: %s\n\nلمراجعة الطلب، يرجى زيارة لوحة التحكم.", 'saint-porphyrius'),
            $data['first_name'],
            $data['middle_name'],
            $data['last_name'],
            $data['email'],
            $data['phone'],
            $data['church_name']
        );
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Send approval email
     */
    private function send_approval_email($pending_user) {
        $subject = __('تمت الموافقة على حسابك', 'saint-porphyrius');
        
        $message = sprintf(
            __("مرحباً %s،\n\nتمت الموافقة على حسابك في تطبيق القديس بورفيريوس.\n\nيمكنك الآن تسجيل الدخول باستخدام بريدك الإلكتروني.\n\nشكراً لك.", 'saint-porphyrius'),
            $pending_user->first_name
        );
        
        wp_mail($pending_user->email, $subject, $message);
    }
    
    /**
     * Send rejection email
     */
    private function send_rejection_email($pending_user, $reason) {
        $subject = __('حالة طلب التسجيل', 'saint-porphyrius');
        
        $message = sprintf(
            __("مرحباً %s،\n\nنأسف لإبلاغك بأنه لم تتم الموافقة على طلب التسجيل الخاص بك.\n\n%s\n\nيمكنك التواصل معنا للمزيد من المعلومات.", 'saint-porphyrius'),
            $pending_user->first_name,
            $reason ? sprintf(__('السبب: %s', 'saint-porphyrius'), $reason) : ''
        );
        
        wp_mail($pending_user->email, $subject, $message);
    }
}

// Initialize
SP_Registration::get_instance();
