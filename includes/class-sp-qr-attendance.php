<?php
/**
 * Saint Porphyrius - QR Attendance Handler
 * Secure QR-based attendance system with time-limited tokens
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_QR_Attendance {
    
    private static $instance = null;
    private $table_name;
    
    // Token validity in seconds (5 minutes)
    const TOKEN_VALIDITY = 300;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sp_qr_attendance_tokens';
    }
    
    /**
     * Generate a secure QR token for a user and event
     * 
     * @param int $event_id Event ID
     * @param int $user_id User ID
     * @return array Token data with QR content and expiry
     */
    public function generate_token($event_id, $user_id) {
        global $wpdb;
        
        // Verify event exists and is valid
        $events = SP_Events::get_instance();
        $event = $events->get($event_id);
        
        if (!$event) {
            return array(
                'success' => false,
                'message' => __('الفعالية غير موجودة', 'saint-porphyrius')
            );
        }
        
        // Check if user is a valid member
        $user = get_user_by('id', $user_id);
        if (!$user || !in_array('sp_member', $user->roles)) {
            return array(
                'success' => false,
                'message' => __('المستخدم غير صالح', 'saint-porphyrius')
            );
        }
        
        // Check if user already has attendance marked
        $attendance = SP_Attendance::get_instance();
        $existing = $attendance->get($event_id, $user_id);
        if ($existing && in_array($existing->status, array('attended', 'late'))) {
            return array(
                'success' => false,
                'message' => __('تم تسجيل حضورك مسبقاً', 'saint-porphyrius'),
                'already_attended' => true
            );
        }
        
        // Auto cleanup expired unused tokens to keep DB clean
        $this->auto_cleanup_expired();
        
        // Invalidate any existing unused tokens for this user/event
        $wpdb->update(
            $this->table_name,
            array('expires_at' => current_time('mysql')),
            array(
                'event_id' => $event_id,
                'user_id' => $user_id,
                'used_at' => null
            ),
            array('%s'),
            array('%d', '%d')
        );
        
        // Generate cryptographically secure token
        $token = $this->generate_secure_token();
        $expires_at = date('Y-m-d H:i:s', time() + self::TOKEN_VALIDITY);
        
        // Store token in database
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'token' => $token,
                'event_id' => $event_id,
                'user_id' => $user_id,
                'expires_at' => $expires_at,
                'ip_address' => $this->get_client_ip(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%d', '%d', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => __('فشل في إنشاء رمز الحضور', 'saint-porphyrius')
            );
        }
        
        // Build QR data - includes site-specific data for extra security
        $qr_data = array(
            'v' => 1, // Version
            't' => $token,
            'e' => $event_id,
            'u' => $user_id,
            's' => wp_hash($token . $event_id . $user_id), // Signature
        );
        
        return array(
            'success' => true,
            'token' => $token,
            'qr_content' => json_encode($qr_data),
            'expires_at' => $expires_at,
            'expires_in' => self::TOKEN_VALIDITY,
            'event_title' => $event->title_ar,
            'user_name' => get_user_meta($user_id, 'sp_name_ar', true) ?: $user->display_name,
        );
    }
    
    /**
     * Validate a QR token and mark attendance
     * 
     * @param string $qr_content Raw QR content (JSON)
     * @param string $status Attendance status (attended/late)
     * @param int $admin_id Admin user ID who is scanning
     * @return array Result with success/error
     */
    public function validate_and_mark($qr_content, $status, $admin_id) {
        global $wpdb;
        
        // Parse QR content
        $qr_data = json_decode($qr_content, true);
        
        if (!$qr_data || !isset($qr_data['t']) || !isset($qr_data['e']) || !isset($qr_data['u']) || !isset($qr_data['s'])) {
            return array(
                'success' => false,
                'message' => __('رمز QR غير صالح', 'saint-porphyrius'),
                'error_code' => 'invalid_qr'
            );
        }
        
        $token = sanitize_text_field($qr_data['t']);
        $event_id = absint($qr_data['e']);
        $user_id = absint($qr_data['u']);
        $signature = sanitize_text_field($qr_data['s']);
        
        // Verify signature
        $expected_signature = wp_hash($token . $event_id . $user_id);
        if (!hash_equals($expected_signature, $signature)) {
            return array(
                'success' => false,
                'message' => __('رمز QR تم التلاعب به', 'saint-porphyrius'),
                'error_code' => 'invalid_signature'
            );
        }
        
        // Look up token in database
        $token_record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE token = %s",
            $token
        ));
        
        if (!$token_record) {
            return array(
                'success' => false,
                'message' => __('رمز QR غير موجود', 'saint-porphyrius'),
                'error_code' => 'token_not_found'
            );
        }
        
        // Check if token was already used
        if ($token_record->used_at !== null) {
            return array(
                'success' => false,
                'message' => __('هذا الرمز تم استخدامه مسبقاً', 'saint-porphyrius'),
                'error_code' => 'token_used',
                'used_at' => $token_record->used_at
            );
        }
        
        // Check if token has expired
        if (strtotime($token_record->expires_at) < time()) {
            return array(
                'success' => false,
                'message' => __('انتهت صلاحية رمز QR، يرجى طلب رمز جديد', 'saint-porphyrius'),
                'error_code' => 'token_expired',
                'expired_at' => $token_record->expires_at
            );
        }
        
        // Verify token matches event and user
        if ($token_record->event_id != $event_id || $token_record->user_id != $user_id) {
            return array(
                'success' => false,
                'message' => __('رمز QR لا يتطابق مع البيانات', 'saint-porphyrius'),
                'error_code' => 'data_mismatch'
            );
        }
        
        // Validate status
        if (!in_array($status, array('attended', 'late'))) {
            return array(
                'success' => false,
                'message' => __('حالة الحضور غير صالحة', 'saint-porphyrius'),
                'error_code' => 'invalid_status'
            );
        }
        
        // Check if attendance already marked
        $attendance = SP_Attendance::get_instance();
        $existing = $attendance->get($event_id, $user_id);
        if ($existing && in_array($existing->status, array('attended', 'late'))) {
            return array(
                'success' => false,
                'message' => __('تم تسجيل الحضور مسبقاً لهذا العضو', 'saint-porphyrius'),
                'error_code' => 'already_marked',
                'existing_status' => $existing->status
            );
        }
        
        // Mark the token as used
        $wpdb->update(
            $this->table_name,
            array(
                'used_at' => current_time('mysql'),
                'used_by' => $admin_id,
                'attendance_status' => $status,
            ),
            array('id' => $token_record->id),
            array('%s', '%d', '%s'),
            array('%d')
        );
        
        // Mark attendance using the main attendance system
        $result = $attendance->mark($event_id, $user_id, $status, __('تم التسجيل عبر QR', 'saint-porphyrius'));
        
        if (is_wp_error($result)) {
            return array(
                'success' => false,
                'message' => $result->get_error_message(),
                'error_code' => 'attendance_failed'
            );
        }
        
        // Get user info for response
        $user = get_user_by('id', $user_id);
        $name_ar = get_user_meta($user_id, 'sp_name_ar', true);
        
        // Get event info
        $events = SP_Events::get_instance();
        $event = $events->get($event_id);
        
        return array(
            'success' => true,
            'message' => __('تم تسجيل الحضور بنجاح', 'saint-porphyrius'),
            'user_id' => $user_id,
            'user_name' => $name_ar ?: $user->display_name,
            'user_email' => $user->user_email,
            'event_id' => $event_id,
            'event_title' => $event ? $event->title_ar : '',
            'status' => $status,
            'status_label' => $status === 'attended' ? __('حاضر', 'saint-porphyrius') : __('متأخر', 'saint-porphyrius'),
            'points' => $result['points'],
        );
    }
    
    /**
     * Get active token for user and event (if exists and not expired)
     */
    public function get_active_token($event_id, $user_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE event_id = %d AND user_id = %d AND used_at IS NULL AND expires_at > %s
             ORDER BY created_at DESC LIMIT 1",
            $event_id,
            $user_id,
            current_time('mysql')
        ));
    }
    
    /**
     * Get token usage statistics for an event
     */
    public function get_event_stats($event_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_tokens,
                SUM(CASE WHEN used_at IS NOT NULL THEN 1 ELSE 0 END) as used_tokens,
                SUM(CASE WHEN attendance_status = 'attended' THEN 1 ELSE 0 END) as attended_count,
                SUM(CASE WHEN attendance_status = 'late' THEN 1 ELSE 0 END) as late_count
             FROM {$this->table_name}
             WHERE event_id = %d",
            $event_id
        ));
    }
    
    /**
     * Cleanup expired tokens (called automatically)
     * Removes tokens older than specified time
     */
    public function cleanup_expired_tokens($days_old = 7) {
        global $wpdb;
        
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days_old} days"));
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} WHERE expires_at < %s",
            $cutoff
        ));
        
        return $deleted;
    }
    
    /**
     * Auto cleanup - removes only expired unused tokens
     * Called on every token generation to keep database clean
     */
    public function auto_cleanup_expired() {
        global $wpdb;
        
        // Delete expired tokens that were never used (older than 1 hour)
        $cutoff = date('Y-m-d H:i:s', strtotime('-1 hour'));
        
        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} WHERE expires_at < %s AND used_at IS NULL",
            $cutoff
        ));
    }
    
    /**
     * Generate cryptographically secure token
     */
    private function generate_secure_token() {
        // Use WordPress's random bytes function which uses various secure sources
        if (function_exists('random_bytes')) {
            $bytes = random_bytes(32);
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes(32);
        } else {
            $bytes = wp_generate_password(32, true, true);
        }
        
        return bin2hex($bytes);
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        );
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Generate QR code image URL
     * Uses Google Charts API for QR code generation (reliable, no dependencies)
     */
    public static function generate_qr_image_url($data, $size = 200) {
        $encoded_data = urlencode($data);
        return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encoded_data}&format=png&margin=10";
    }
    
    /**
     * Generate QR code as data URI (fetches from API and returns base64)
     * Useful for offline/embedded scenarios
     */
    public static function generate_qr_data_uri($data, $size = 200) {
        $url = self::generate_qr_image_url($data, $size);
        $response = wp_remote_get($url, array('timeout' => 10));
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }
        
        $image_data = wp_remote_retrieve_body($response);
        return 'data:image/png;base64,' . base64_encode($image_data);
    }
}

// Initialize hooks for cleanup
add_action('sp_daily_cleanup', array(SP_QR_Attendance::get_instance(), 'cleanup_expired_tokens'));
