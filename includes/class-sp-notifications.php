<?php
/**
 * Saint Porphyrius - Push Notifications Handler (OneSignal)
 * Full integration with OneSignal for web push notifications
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Notifications {
    
    private static $instance = null;
    private $subscribers_table;
    private $log_table;
    private $api_url = 'https://api.onesignal.com';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->subscribers_table = $wpdb->prefix . 'sp_push_subscribers';
        $this->log_table = $wpdb->prefix . 'sp_push_notifications_log';
        
        // Auto-trigger hooks
        $this->init_auto_triggers();
    }
    
    /**
     * Initialize automatic notification triggers
     */
    private function init_auto_triggers() {
        $settings = $this->get_settings();
        
        // New event created
        if (!empty($settings['auto_new_event'])) {
            add_action('sp_event_created', array($this, 'notify_new_event'), 10, 1);
        }
        
        // User registration approved
        if (!empty($settings['auto_registration_approved'])) {
            add_action('sp_user_approved', array($this, 'notify_user_approved'), 10, 1);
        }
        
        // New quiz published
        if (!empty($settings['auto_new_quiz'])) {
            add_action('sp_quiz_published', array($this, 'notify_new_quiz'), 10, 1);
        }
        
        // Points milestone
        if (!empty($settings['auto_points_milestone'])) {
            add_action('sp_points_milestone', array($this, 'notify_points_milestone'), 10, 2);
        }
        
        // Event reminder (via cron)
        if (!empty($settings['auto_event_reminder'])) {
            add_action('sp_event_reminder_cron', array($this, 'send_event_reminders'));
            if (!wp_next_scheduled('sp_event_reminder_cron')) {
                wp_schedule_event(time(), 'hourly', 'sp_event_reminder_cron');
            }
        }
    }
    
    // ==========================================
    // SETTINGS
    // ==========================================
    
    /**
     * Get notification settings
     */
    public function get_settings() {
        $defaults = array(
            'enabled' => 0,
            'app_id' => '',
            'api_key' => '',
            'safari_web_id' => '',
            'subscription_points' => 10,
            'subscription_points_enabled' => 1,
            'auto_new_event' => 1,
            'auto_registration_approved' => 1,
            'auto_new_quiz' => 1,
            'auto_points_milestone' => 0,
            'auto_event_reminder' => 1,
            'event_reminder_hours' => 24,
            'welcome_message_enabled' => 1,
            'welcome_title' => 'مرحباً بك! 🎉',
            'welcome_message' => 'شكراً لتفعيل الإشعارات. ستصلك أخبار وتحديثات أسرة القديس بورفيريوس.',
            'prompt_delay_seconds' => 10,
            'prompt_message' => 'فعّل الإشعارات علشان توصلك أخبار الفعاليات والنقاط والمزيد! 🔔',
        );
        
        $settings = get_option('sp_push_settings', array());
        return wp_parse_args($settings, $defaults);
    }
    
    /**
     * Update notification settings
     */
    public function update_settings($new_settings) {
        $current = $this->get_settings();
        $settings = wp_parse_args($new_settings, $current);
        
        // Sanitize
        $settings['enabled'] = !empty($settings['enabled']) ? 1 : 0;
        $settings['app_id'] = sanitize_text_field($settings['app_id']);
        $settings['api_key'] = sanitize_text_field($settings['api_key']);
        $settings['safari_web_id'] = sanitize_text_field($settings['safari_web_id']);
        $settings['subscription_points'] = absint($settings['subscription_points']);
        $settings['subscription_points_enabled'] = !empty($settings['subscription_points_enabled']) ? 1 : 0;
        $settings['auto_new_event'] = !empty($settings['auto_new_event']) ? 1 : 0;
        $settings['auto_registration_approved'] = !empty($settings['auto_registration_approved']) ? 1 : 0;
        $settings['auto_new_quiz'] = !empty($settings['auto_new_quiz']) ? 1 : 0;
        $settings['auto_points_milestone'] = !empty($settings['auto_points_milestone']) ? 1 : 0;
        $settings['auto_event_reminder'] = !empty($settings['auto_event_reminder']) ? 1 : 0;
        $settings['event_reminder_hours'] = absint($settings['event_reminder_hours']);
        $settings['welcome_message_enabled'] = !empty($settings['welcome_message_enabled']) ? 1 : 0;
        $settings['welcome_title'] = sanitize_text_field($settings['welcome_title']);
        $settings['welcome_message'] = sanitize_textarea_field($settings['welcome_message']);
        $settings['prompt_delay_seconds'] = absint($settings['prompt_delay_seconds']);
        $settings['prompt_message'] = sanitize_text_field($settings['prompt_message']);
        
        update_option('sp_push_settings', $settings);
        return $settings;
    }
    
    /**
     * Check if OneSignal is properly configured
     */
    public function is_configured() {
        $settings = $this->get_settings();
        return !empty($settings['enabled']) && !empty($settings['app_id']) && !empty($settings['api_key']);
    }
    
    // ==========================================
    // SUBSCRIBER MANAGEMENT
    // ==========================================
    
    /**
     * Register a push subscriber
     */
    public function register_subscriber($user_id, $player_id, $device_type = 'web', $browser = '') {
        global $wpdb;
        
        if (empty($player_id)) {
            return new WP_Error('missing_player_id', 'Player ID is required');
        }
        
        // Check if this player_id already exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->subscribers_table} WHERE onesignal_player_id = %s",
            $player_id
        ));
        
        if ($existing) {
            // Update existing
            $wpdb->update(
                $this->subscribers_table,
                array(
                    'user_id' => $user_id,
                    'last_active' => current_time('mysql'),
                    'is_active' => 1,
                    'device_type' => $device_type,
                    'browser' => $browser,
                ),
                array('onesignal_player_id' => $player_id),
                array('%d', '%s', '%d', '%s', '%s'),
                array('%s')
            );
            
            return array(
                'success' => true,
                'is_new' => false,
                'subscriber_id' => $existing->id,
            );
        }
        
        // Insert new subscriber
        $wpdb->insert(
            $this->subscribers_table,
            array(
                'user_id' => $user_id,
                'onesignal_player_id' => $player_id,
                'device_type' => $device_type,
                'browser' => $browser,
                'subscribed_at' => current_time('mysql'),
                'last_active' => current_time('mysql'),
                'is_active' => 1,
                'points_awarded' => 0,
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d')
        );
        
        $subscriber_id = $wpdb->insert_id;
        
        // Award subscription points
        $points_result = $this->award_subscription_points($user_id, $subscriber_id);
        
        // Send welcome notification
        $settings = $this->get_settings();
        if (!empty($settings['welcome_message_enabled'])) {
            $this->send_to_player($player_id, $settings['welcome_title'], $settings['welcome_message'], home_url('/app/dashboard'));
        }
        
        return array(
            'success' => true,
            'is_new' => true,
            'subscriber_id' => $subscriber_id,
            'points_awarded' => $points_result,
        );
    }
    
    /**
     * Unsubscribe a player
     */
    public function unsubscribe($player_id) {
        global $wpdb;
        
        return $wpdb->update(
            $this->subscribers_table,
            array('is_active' => 0),
            array('onesignal_player_id' => $player_id),
            array('%d'),
            array('%s')
        );
    }
    
    /**
     * Award points for push notification subscription
     */
    private function award_subscription_points($user_id, $subscriber_id) {
        $settings = $this->get_settings();
        
        if (empty($settings['subscription_points_enabled']) || $settings['subscription_points'] <= 0) {
            return false;
        }
        
        global $wpdb;
        
        // Check if this user already got points for subscribing
        $already_awarded = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->subscribers_table} WHERE user_id = %d AND points_awarded = 1",
            $user_id
        ));
        
        if ($already_awarded > 0) {
            return false;
        }
        
        // Award points
        $points_handler = SP_Points::get_instance();
        $result = $points_handler->add(
            $user_id,
            $settings['subscription_points'],
            'reward',
            null,
            'مكافأة تفعيل الإشعارات 🔔'
        );
        
        if (!is_wp_error($result)) {
            // Mark as awarded
            $wpdb->update(
                $this->subscribers_table,
                array('points_awarded' => 1),
                array('id' => $subscriber_id),
                array('%d'),
                array('%d')
            );
            
            return $settings['subscription_points'];
        }
        
        return false;
    }
    
    /**
     * Get subscriber count
     */
    public function get_subscriber_count($active_only = true) {
        global $wpdb;
        
        $where = $active_only ? "WHERE is_active = 1" : "";
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->subscribers_table} $where");
    }
    
    /**
     * Get all subscribers with user info
     */
    public function get_subscribers($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'active_only' => true,
            'limit' => 50,
            'offset' => 0,
            'search' => '',
        );
        $args = wp_parse_args($args, $defaults);
        
        $where = array("1=1");
        $where_values = array();
        
        if ($args['active_only']) {
            $where[] = "s.is_active = 1";
        }
        
        if (!empty($args['search'])) {
            $where[] = "(u.display_name LIKE %s OR u.user_email LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $where_clause = implode(' AND ', $where);
        
        $query = "SELECT s.*, u.display_name, u.user_email,
                    (SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = s.user_id AND meta_key = 'first_name' LIMIT 1) as first_name,
                    (SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = s.user_id AND meta_key = 'sp_name_ar' LIMIT 1) as name_ar
                  FROM {$this->subscribers_table} s 
                  LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID 
                  WHERE $where_clause 
                  ORDER BY s.subscribed_at DESC 
                  LIMIT %d OFFSET %d";
        
        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];
        
        return $wpdb->get_results($wpdb->prepare($query, $where_values));
    }
    
    /**
     * Check if a user is subscribed
     */
    public function is_user_subscribed($user_id) {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->subscribers_table} WHERE user_id = %d AND is_active = 1",
            $user_id
        ));
    }
    
    /**
     * Get subscriber stats
     */
    public function get_stats() {
        global $wpdb;
        
        $stats = new stdClass();
        $stats->total_subscribers = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->subscribers_table} WHERE is_active = 1");
        $stats->total_unsubscribed = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->subscribers_table} WHERE is_active = 0");
        $stats->subscribed_today = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->subscribers_table} WHERE is_active = 1 AND DATE(subscribed_at) = %s",
            current_time('Y-m-d')
        ));
        $stats->total_notifications_sent = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->log_table}");
        $stats->total_delivered = (int) $wpdb->get_var("SELECT SUM(delivered_count) FROM {$this->log_table}");
        $stats->total_clicked = (int) $wpdb->get_var("SELECT SUM(clicked_count) FROM {$this->log_table}");
        $stats->recent_notifications = $wpdb->get_results(
            "SELECT * FROM {$this->log_table} ORDER BY sent_at DESC LIMIT 10"
        );
        
        // Subscription rate vs total members
        $total_members = count(get_users(array('role__in' => array('sp_member', 'sp_church_admin'))));
        $stats->total_members = $total_members;
        $stats->subscription_rate = $total_members > 0 ? round(($stats->total_subscribers / $total_members) * 100, 1) : 0;
        
        // Device breakdown
        $stats->device_breakdown = $wpdb->get_results(
            "SELECT device_type, COUNT(*) as count FROM {$this->subscribers_table} WHERE is_active = 1 GROUP BY device_type"
        );
        
        // Browser breakdown
        $stats->browser_breakdown = $wpdb->get_results(
            "SELECT browser, COUNT(*) as count FROM {$this->subscribers_table} WHERE is_active = 1 AND browser != '' GROUP BY browser ORDER BY count DESC"
        );
        
        return $stats;
    }
    
    // ==========================================
    // ONESIGNAL API CALLS
    // ==========================================
    
    /**
     * Send notification to ALL subscribers
     */
    public function send_to_all($title, $message, $url = '', $data = array(), $trigger_type = 'manual') {
        global $wpdb;
        $settings = $this->get_settings();
        
        if (!$this->is_configured()) {
            return new WP_Error('not_configured', 'OneSignal is not configured');
        }
        
        // Fetch all active subscription IDs from our DB
        // This is more reliable than using segments and works on all OneSignal plans
        $subscription_ids = $wpdb->get_col(
            "SELECT onesignal_player_id FROM {$this->subscribers_table} WHERE is_active = 1"
        );
        
        if (empty($subscription_ids)) {
            // Log with 0 sent
            $this->log_notification($title, $message, $url, 'all', null, array('id' => null, 'recipients' => 0), $trigger_type);
            return array('id' => null, 'recipients' => 0);
        }
        
        // OneSignal API limit: max 2000 subscription IDs per request
        $batches = array_chunk($subscription_ids, 2000);
        $total_recipients = 0;
        $last_result = null;
        
        foreach ($batches as $batch) {
            $payload = array(
                'app_id' => $settings['app_id'],
                'include_subscription_ids' => array_values($batch),
                'headings' => array('ar' => $title, 'en' => $title),
                'contents' => array('ar' => $message, 'en' => $message),
                'chrome_web_icon' => SP_PLUGIN_URL . 'assets/icons/icon-192x192.png',
                'chrome_web_badge' => SP_PLUGIN_URL . 'assets/icons/icon-72x72.png',
                'web_url' => $url ?: home_url('/app/'),
                'data' => $data,
            );
            
            $result = $this->api_request('notifications', $payload);
            
            if (is_array($result) && isset($result['recipients'])) {
                $total_recipients += (int) $result['recipients'];
            }
            $last_result = $result;
        }
        
        // Build combined result
        $combined = is_array($last_result) ? $last_result : array();
        $combined['recipients'] = $total_recipients;
        
        // Log the notification
        $this->log_notification($title, $message, $url, 'all', null, $combined, $trigger_type);
        
        return $combined;
    }
    
    /**
     * Send notification to specific user(s) by player IDs
     */
    public function send_to_players($player_ids, $title, $message, $url = '', $data = array()) {
        $settings = $this->get_settings();
        
        if (!$this->is_configured()) {
            return new WP_Error('not_configured', 'OneSignal is not configured');
        }
        
        if (empty($player_ids)) {
            return new WP_Error('no_targets', 'No player IDs provided');
        }
        
        $payload = array(
            'app_id' => $settings['app_id'],
            'include_subscription_ids' => array_values((array) $player_ids),
            'headings' => array('ar' => $title, 'en' => $title),
            'contents' => array('ar' => $message, 'en' => $message),
            'chrome_web_icon' => SP_PLUGIN_URL . 'assets/icons/icon-192x192.png',
            'chrome_web_badge' => SP_PLUGIN_URL . 'assets/icons/icon-72x72.png',
            'web_url' => $url ?: home_url('/app/'),
            'data' => $data,
        );
        
        return $this->api_request('notifications', $payload);
    }
    
    /**
     * Send notification to a single player by player ID
     */
    public function send_to_player($player_id, $title, $message, $url = '') {
        return $this->send_to_players(array($player_id), $title, $message, $url);
    }
    
    /**
     * Send notification to specific users (by WP user IDs)
     */
    public function send_to_users($user_ids, $title, $message, $url = '', $trigger_type = 'manual') {
        global $wpdb;
        
        if (empty($user_ids)) {
            return new WP_Error('no_targets', 'No user IDs provided');
        }
        
        $user_ids = (array) $user_ids;
        $placeholders = implode(',', array_fill(0, count($user_ids), '%d'));
        
        $player_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT onesignal_player_id FROM {$this->subscribers_table} WHERE user_id IN ($placeholders) AND is_active = 1",
            $user_ids
        ));
        
        if (empty($player_ids)) {
            return new WP_Error('no_subscribers', 'None of the target users are subscribed to push notifications');
        }
        
        $result = $this->send_to_players($player_ids, $title, $message, $url);
        
        // Log
        $this->log_notification($title, $message, $url, 'specific_users', $user_ids, $result, $trigger_type);
        
        return $result;
    }
    
    /**
     * Make API request to OneSignal
     */
    private function api_request($endpoint, $payload) {
        $settings = $this->get_settings();
        
        $response = wp_remote_post("{$this->api_url}/{$endpoint}", array(
            'headers' => array(
                'Content-Type' => 'application/json; charset=utf-8',
                'Authorization' => 'Key ' . $settings['api_key'],
            ),
            'body' => wp_json_encode($payload),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('SP OneSignal API Error: ' . $response->get_error_message());
            }
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $code = wp_remote_retrieve_response_code($response);
        
        if ($code !== 200) {
            $error_msg = isset($body['errors']) ? implode(', ', (array) $body['errors']) : 'Unknown API error';
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('SP OneSignal API Error (' . $code . '): ' . $error_msg);
            }
            return new WP_Error('api_error', $error_msg);
        }
        
        return $body;
    }
    
    /**
     * Get notification delivery stats from OneSignal
     */
    public function get_notification_stats($notification_id) {
        $settings = $this->get_settings();
        
        if (!$this->is_configured() || empty($notification_id)) {
            return null;
        }
        
        $response = wp_remote_get("{$this->api_url}/notifications/{$notification_id}?app_id={$settings['app_id']}", array(
            'headers' => array(
                'Authorization' => 'Key ' . $settings['api_key'],
            ),
            'timeout' => 15,
        ));
        
        if (is_wp_error($response)) {
            return null;
        }
        
        return json_decode(wp_remote_retrieve_body($response), true);
    }
    
    /**
     * Log a sent notification
     */
    private function log_notification($title, $message, $url, $segment, $target_users, $api_result, $trigger_type = 'manual') {
        global $wpdb;
        
        $notification_id = null;
        $sent_count = 0;
        
        if (is_array($api_result)) {
            $notification_id = isset($api_result['id']) ? $api_result['id'] : null;
            $sent_count = isset($api_result['recipients']) ? (int) $api_result['recipients'] : 0;
        }
        
        $wpdb->insert(
            $this->log_table,
            array(
                'title' => sanitize_text_field($title),
                'message' => sanitize_textarea_field($message),
                'url' => esc_url_raw($url),
                'segment' => $segment,
                'target_users' => $target_users ? wp_json_encode($target_users) : null,
                'onesignal_notification_id' => $notification_id,
                'sent_count' => $sent_count,
                'trigger_type' => $trigger_type,
                'sent_by' => get_current_user_id(),
                'sent_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s')
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get notification log
     */
    public function get_notification_log($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'limit' => 20,
            'offset' => 0,
            'trigger_type' => '',
        );
        $args = wp_parse_args($args, $defaults);
        
        $where = "1=1";
        $values = array();
        
        if (!empty($args['trigger_type'])) {
            $where .= " AND trigger_type = %s";
            $values[] = $args['trigger_type'];
        }
        
        $values[] = $args['limit'];
        $values[] = $args['offset'];
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, u.display_name as sent_by_name 
             FROM {$this->log_table} l 
             LEFT JOIN {$wpdb->users} u ON l.sent_by = u.ID 
             WHERE $where 
             ORDER BY l.sent_at DESC 
             LIMIT %d OFFSET %d",
            $values
        ));
    }
    
    // ==========================================
    // AUTO NOTIFICATION TRIGGERS
    // ==========================================
    
    /**
     * Notify about new event
     */
    public function notify_new_event($event) {
        if (!$this->is_configured()) return;
        
        $title = '📅 فعالية جديدة';
        $message = sprintf('تم إضافة فعالية جديدة: %s - %s', 
            $event->title_ar ?: $event->title,
            date_i18n('j F Y', strtotime($event->event_date))
        );
        $url = home_url('/app/events/' . $event->id);
        
        $this->send_to_all($title, $message, $url, array(), 'auto_event');
    }
    
    /**
     * Notify a user that their registration was approved
     */
    public function notify_user_approved($user_id) {
        if (!$this->is_configured()) return;
        
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $title = '🎉 تم قبول طلبك!';
        $message = sprintf('مرحباً %s! تم قبول طلب انضمامك لأسرة القديس بورفيريوس.', $user->display_name);
        $url = home_url('/app/dashboard');
        
        $this->send_to_users(array($user_id), $title, $message, $url, 'auto_registration');
    }
    
    /**
     * Notify about a new quiz
     */
    public function notify_new_quiz($content) {
        if (!$this->is_configured()) return;
        
        $title = '📝 اختبار جديد';
        $message = sprintf('تم نشر اختبار جديد: %s - جاوب واكسب نقاط!', $content->title_ar);
        $url = home_url('/app/quizzes');
        
        $this->send_to_all($title, $message, $url, array(), 'auto_quiz');
    }
    
    /**
     * Notify user about points milestone
     */
    public function notify_points_milestone($user_id, $milestone) {
        if (!$this->is_configured()) return;
        
        $title = '🏆 أحسنت!';
        $message = sprintf('مبروك! وصلت لـ %d نقطة. استمر في التقدم!', $milestone);
        $url = home_url('/app/points');
        
        $this->send_to_users(array($user_id), $title, $message, $url, 'auto_points');
    }
    
    /**
     * Send event reminders for upcoming events
     */
    public function send_event_reminders() {
        if (!$this->is_configured()) return;
        
        $settings = $this->get_settings();
        $reminder_hours = !empty($settings['event_reminder_hours']) ? $settings['event_reminder_hours'] : 24;
        
        $events_handler = SP_Events::get_instance();
        $upcoming_events = $events_handler->get_upcoming(10);
        
        foreach ($upcoming_events as $event) {
            $event_time = strtotime($event->event_date . ' ' . $event->start_time);
            $now = current_time('timestamp');
            $diff_hours = ($event_time - $now) / 3600;
            
            // Send reminder if within the reminder window (and not past)
            if ($diff_hours > 0 && $diff_hours <= $reminder_hours) {
                // Check if reminder already sent for this event
                $already_sent = get_post_meta($event->id, '_sp_reminder_sent', true);
                if ($already_sent) continue;
                
                $title = '⏰ تذكير بالفعالية';
                $message = sprintf('%s - %s الساعة %s', 
                    $event->title_ar ?: $event->title,
                    date_i18n('j F', strtotime($event->event_date)),
                    $event->start_time
                );
                $url = home_url('/app/events/' . $event->id);
                
                $result = $this->send_to_all($title, $message, $url);
                $this->log_notification($title, $message, $url, 'all', null, $result, 'auto_event_reminder');
                
                // Mark reminder as sent
                update_post_meta($event->id, '_sp_reminder_sent', 1);
            }
        }
    }
    
    // ==========================================
    // ADMIN: SEND CUSTOM NOTIFICATION
    // ==========================================
    
    /**
     * Send a manual notification from admin
     */
    public function send_admin_notification($title, $message, $url = '', $segment = 'all', $user_ids = array()) {
        if (!$this->is_configured()) {
            return new WP_Error('not_configured', 'OneSignal غير مفعّل أو غير مكتمل الإعدادات');
        }
        
        if (empty($title) || empty($message)) {
            return new WP_Error('missing_data', 'العنوان والرسالة مطلوبان');
        }
        
        if ($segment === 'specific_users' && !empty($user_ids)) {
            return $this->send_to_users($user_ids, $title, $message, $url, 'manual');
        }
        
        // Send to all subscribers
        return $this->send_to_all($title, $message, $url);
    }
    
    /**
     * Test OneSignal connection
     */
    public function test_connection() {
        $settings = $this->get_settings();
        
        if (empty($settings['app_id']) || empty($settings['api_key'])) {
            return new WP_Error('missing_credentials', 'App ID and API Key are required');
        }
        
        $response = wp_remote_get("{$this->api_url}/apps/{$settings['app_id']}", array(
            'headers' => array(
                'Authorization' => 'Key ' . $settings['api_key'],
            ),
            'timeout' => 15,
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($code === 200 && !empty($body['id'])) {
            return array(
                'success' => true,
                'app_name' => $body['name'] ?? '',
                'players' => $body['players'] ?? 0,
                'messageable_players' => $body['messageable_players'] ?? 0,
            );
        }
        
        return new WP_Error('connection_failed', 'فشل الاتصال بـ OneSignal. تحقق من البيانات.');
    }
}

// Initialize
SP_Notifications::get_instance();
