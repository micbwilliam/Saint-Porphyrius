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
     * Validate Egyptian phone number
     */
    public function validate_egyptian_phone($phone) {
        // Remove any spaces, dashes or country code
        $phone = preg_replace('/[\s\-]/', '', $phone);
        
        // If starts with +20, remove it
        if (strpos($phone, '+20') === 0) {
            $phone = substr($phone, 3);
        }
        // If starts with 20, remove it
        if (strpos($phone, '20') === 0 && strlen($phone) > 10) {
            $phone = substr($phone, 2);
        }
        // If starts with 002, remove it
        if (strpos($phone, '002') === 0) {
            $phone = substr($phone, 3);
        }
        
        // Should start with 01 and be 11 digits
        if (!preg_match('/^01[0-9]{9}$/', $phone)) {
            return false;
        }
        
        // Valid Egyptian mobile prefixes: 010, 011, 012, 015
        $valid_prefixes = array('010', '011', '012', '015');
        $prefix = substr($phone, 0, 3);
        
        if (!in_array($prefix, $valid_prefixes)) {
            return false;
        }
        
        return $phone;
    }
    
    /**
     * Format phone for WhatsApp (international format)
     */
    public function format_phone_whatsapp($phone) {
        $phone = $this->validate_egyptian_phone($phone);
        if ($phone) {
            return '+20' . $phone;
        }
        return false;
    }
    
    /**
     * Register a new pending user
     */
    public function register_user($data) {
        global $wpdb;
        
        // Validate required fields
        $required_fields = array(
            'first_name', 'middle_name', 'last_name', 'email', 'password',
            'phone', 'gender', 'birth_date', 'address_area', 'address_street', 'address_building',
            'address_floor', 'address_apartment', 'address_landmark', 'address_maps_url',
            'church_name', 'confession_father', 'job_or_college', 'current_church_service', 
            'church_family', 'church_family_servant'
        );
        
        $field_labels = array(
            'first_name' => 'الاسم الأول',
            'middle_name' => 'الاسم الأوسط',
            'last_name' => 'اسم العائلة',
            'email' => 'البريد الإلكتروني',
            'password' => 'كلمة المرور',
            'phone' => 'رقم الهاتف',
            'gender' => 'النوع',
            'birth_date' => 'تاريخ الميلاد',
            'address_area' => 'المنطقة/الحي',
            'address_street' => 'الشارع',
            'address_building' => 'رقم العقار',
            'address_floor' => 'الدور',
            'address_apartment' => 'رقم الشقة',
            'address_landmark' => 'علامة مميزة',
            'address_maps_url' => 'رابط خرائط جوجل',
            'church_name' => 'اسم الكنيسة',
            'confession_father' => 'أب الاعتراف',
            'job_or_college' => 'الوظيفة/الكلية',
            'current_church_service' => 'الخدمة الحالية',
            'church_family' => 'الأسرة بالكنيسة',
            'church_family_servant' => 'خادم الأسرة',
        );
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $label = isset($field_labels[$field]) ? $field_labels[$field] : $field;
                return new WP_Error('missing_field', sprintf(__('الحقل %s مطلوب', 'saint-porphyrius'), $label));
            }
        }
        
        // Validate gender
        if (!in_array($data['gender'], array('male', 'female'))) {
            return new WP_Error('invalid_gender', __('النوع غير صحيح', 'saint-porphyrius'));
        }
        
        // Validate and format Egyptian phone number
        $phone = $this->validate_egyptian_phone($data['phone']);
        if (!$phone) {
            return new WP_Error('invalid_phone', __('رقم الهاتف غير صحيح. يجب أن يكون رقم مصري (01xxxxxxxxx)', 'saint-porphyrius'));
        }
        
        // Handle WhatsApp number
        $whatsapp_same = !empty($data['whatsapp_same_as_phone']);
        $whatsapp_number = '';
        
        if ($whatsapp_same) {
            $whatsapp_number = $phone;
        } elseif (!empty($data['whatsapp_number'])) {
            $whatsapp_number = $this->validate_egyptian_phone($data['whatsapp_number']);
            if (!$whatsapp_number) {
                return new WP_Error('invalid_whatsapp', __('رقم الواتساب غير صحيح. يجب أن يكون رقم مصري (01xxxxxxxxx)', 'saint-porphyrius'));
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
        
        // Validate birth date
        $birth_date = sanitize_text_field($data['birth_date']);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
            return new WP_Error('invalid_birth_date', __('تاريخ الميلاد غير صحيح', 'saint-porphyrius'));
        }
        $birth_timestamp = strtotime($birth_date);
        $min_age_timestamp = strtotime('-10 years');
        if ($birth_timestamp > $min_age_timestamp) {
            return new WP_Error('too_young', __('يجب أن يكون عمرك 10 سنوات على الأقل', 'saint-porphyrius'));
        }
        
        // Validate Google Maps URL (required)
        $maps_url = '';
        if (!preg_match('/^https?:\/\/(www\.)?(google\.com\/maps|maps\.google\.com|goo\.gl\/maps|maps\.app\.goo\.gl)/i', $data['address_maps_url'])) {
            return new WP_Error('invalid_maps_url', __('رابط خرائط جوجل غير صحيح', 'saint-porphyrius'));
        }
        $maps_url = esc_url_raw($data['address_maps_url']);
        
        // Hash password
        $hashed_password = wp_hash_password($data['password']);
        
        // Build combined address for backwards compatibility
        $home_address = sprintf('%s، %s، عقار %s، دور %s، شقة %s',
            $data['address_area'],
            $data['address_street'],
            $data['address_building'],
            $data['address_floor'],
            $data['address_apartment']
        );
        if (!empty($data['address_landmark'])) {
            $home_address .= ' (' . $data['address_landmark'] . ')';
        }
        
        // Insert into pending users table
        $result = $wpdb->insert(
            $table_name,
            array(
                'first_name' => sanitize_text_field($data['first_name']),
                'middle_name' => sanitize_text_field($data['middle_name']),
                'last_name' => sanitize_text_field($data['last_name']),
                'gender' => $data['gender'],
                'birth_date' => $birth_date,
                'email' => sanitize_email($data['email']),
                'password' => $hashed_password,
                'phone' => $phone,
                'phone_verified' => 0,
                'whatsapp_number' => $whatsapp_number,
                'whatsapp_same_as_phone' => $whatsapp_same ? 1 : 0,
                'home_address' => sanitize_textarea_field($home_address),
                'address_area' => sanitize_text_field($data['address_area']),
                'address_street' => sanitize_text_field($data['address_street']),
                'address_building' => sanitize_text_field($data['address_building']),
                'address_floor' => sanitize_text_field($data['address_floor']),
                'address_apartment' => sanitize_text_field($data['address_apartment']),
                'address_landmark' => sanitize_text_field($data['address_landmark'] ?? ''),
                'address_maps_url' => $maps_url,
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
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
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
        update_user_meta($user_id, 'sp_gender', $pending_user->gender ?? 'male');
        update_user_meta($user_id, 'sp_birth_date', $pending_user->birth_date ?? '');
        update_user_meta($user_id, 'sp_phone', $pending_user->phone);
        update_user_meta($user_id, 'sp_phone_verified', $pending_user->phone_verified ?? 0);
        update_user_meta($user_id, 'sp_whatsapp_number', $pending_user->whatsapp_number ?? '');
        update_user_meta($user_id, 'sp_whatsapp_same_as_phone', $pending_user->whatsapp_same_as_phone ?? 1);
        update_user_meta($user_id, 'sp_home_address', $pending_user->home_address);
        update_user_meta($user_id, 'sp_address_area', $pending_user->address_area ?? '');
        update_user_meta($user_id, 'sp_address_street', $pending_user->address_street ?? '');
        update_user_meta($user_id, 'sp_address_building', $pending_user->address_building ?? '');
        update_user_meta($user_id, 'sp_address_floor', $pending_user->address_floor ?? '');
        update_user_meta($user_id, 'sp_address_apartment', $pending_user->address_apartment ?? '');
        update_user_meta($user_id, 'sp_address_landmark', $pending_user->address_landmark ?? '');
        update_user_meta($user_id, 'sp_address_maps_url', $pending_user->address_maps_url ?? '');
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
     * Update user profile
     */
    public function update_user_profile($user_id, $data, $is_admin = false) {
        // Verify user can edit
        if (!$is_admin && get_current_user_id() !== $user_id) {
            return new WP_Error('permission_denied', __('ليس لديك صلاحية لتعديل هذا الملف الشخصي', 'saint-porphyrius'));
        }
        
        // Get user
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return new WP_Error('user_not_found', __('المستخدم غير موجود', 'saint-porphyrius'));
        }
        
        // Validate and format phone if provided
        if (!empty($data['phone'])) {
            $phone = $this->validate_egyptian_phone($data['phone']);
            if (!$phone) {
                return new WP_Error('invalid_phone', __('رقم الهاتف غير صحيح. يجب أن يكون رقم مصري (01xxxxxxxxx)', 'saint-porphyrius'));
            }
            update_user_meta($user_id, 'sp_phone', $phone);
        }
        
        // Handle WhatsApp
        if (isset($data['whatsapp_same_as_phone'])) {
            $whatsapp_same = !empty($data['whatsapp_same_as_phone']);
            update_user_meta($user_id, 'sp_whatsapp_same_as_phone', $whatsapp_same ? 1 : 0);
            
            if ($whatsapp_same) {
                $phone = get_user_meta($user_id, 'sp_phone', true);
                update_user_meta($user_id, 'sp_whatsapp_number', $phone);
            } elseif (!empty($data['whatsapp_number'])) {
                $whatsapp = $this->validate_egyptian_phone($data['whatsapp_number']);
                if (!$whatsapp) {
                    return new WP_Error('invalid_whatsapp', __('رقم الواتساب غير صحيح. يجب أن يكون رقم مصري (01xxxxxxxxx)', 'saint-porphyrius'));
                }
                update_user_meta($user_id, 'sp_whatsapp_number', $whatsapp);
            }
        }
        
        // Validate Google Maps URL if provided
        if (isset($data['address_maps_url'])) {
            if (!empty($data['address_maps_url'])) {
                if (!preg_match('/^https?:\/\/(www\.)?(google\.com\/maps|maps\.google\.com|goo\.gl\/maps|maps\.app\.goo\.gl)/i', $data['address_maps_url'])) {
                    return new WP_Error('invalid_maps_url', __('رابط خرائط جوجل غير صحيح', 'saint-porphyrius'));
                }
                update_user_meta($user_id, 'sp_address_maps_url', esc_url_raw($data['address_maps_url']));
            } else {
                update_user_meta($user_id, 'sp_address_maps_url', '');
            }
        }
        
        // Update basic info
        $wp_user_data = array('ID' => $user_id);
        
        if (!empty($data['first_name'])) {
            $wp_user_data['first_name'] = sanitize_text_field($data['first_name']);
        }
        if (!empty($data['last_name'])) {
            $wp_user_data['last_name'] = sanitize_text_field($data['last_name']);
        }
        
        if (count($wp_user_data) > 1) {
            wp_update_user($wp_user_data);
        }
        
        // Update text fields
        $text_fields = array(
            'middle_name', 'gender', 'birth_date', 'job_or_college', 'church_name',
            'confession_father', 'current_church_service', 'church_family',
            'church_family_servant', 'address_area', 'address_street',
            'address_building', 'address_floor', 'address_apartment', 'address_landmark'
        );
        
        foreach ($text_fields as $field) {
            if (isset($data[$field])) {
                update_user_meta($user_id, 'sp_' . $field, sanitize_text_field($data[$field]));
            }
        }
        
        // Update URL fields
        $url_fields = array('facebook_link', 'instagram_link');
        foreach ($url_fields as $field) {
            if (isset($data[$field])) {
                update_user_meta($user_id, 'sp_' . $field, esc_url_raw($data[$field]));
            }
        }
        
        // Update combined home_address for backwards compatibility
        $address_area = get_user_meta($user_id, 'sp_address_area', true);
        $address_street = get_user_meta($user_id, 'sp_address_street', true);
        $address_building = get_user_meta($user_id, 'sp_address_building', true);
        $address_floor = get_user_meta($user_id, 'sp_address_floor', true);
        $address_apartment = get_user_meta($user_id, 'sp_address_apartment', true);
        $address_landmark = get_user_meta($user_id, 'sp_address_landmark', true);
        
        if ($address_area && $address_building) {
            $home_address = sprintf('%s، %s، عقار %s، دور %s، شقة %s',
                $address_area, $address_street, $address_building, $address_floor, $address_apartment
            );
            if ($address_landmark) {
                $home_address .= ' (' . $address_landmark . ')';
            }
            update_user_meta($user_id, 'sp_home_address', $home_address);
        }
        
        return array(
            'success' => true,
            'message' => __('تم تحديث البيانات بنجاح', 'saint-porphyrius'),
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
