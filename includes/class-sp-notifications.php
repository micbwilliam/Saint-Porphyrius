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
    private $inbox_table;
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
        $this->inbox_table = $wpdb->prefix . 'sp_user_notifications';
        
        // Auto-trigger hooks
        $this->init_auto_triggers();
    }
    
    /**
     * Initialize automatic notification triggers
     */
    private function init_auto_triggers() {
        // Auto-triggers ALWAYS register for inbox notifications.
        // The OneSignal push part is conditioned inside each handler.
        add_action('sp_event_created',  array($this, 'notify_new_event'),        10, 1);
        add_action('sp_user_approved',  array($this, 'notify_user_approved'),     10, 1);
        add_action('sp_quiz_published', array($this, 'notify_new_quiz'),          10, 1);
        add_action('sp_points_milestone', array($this, 'notify_points_milestone'), 10, 2);

        // Event reminders via cron (only if enabled in settings)
        $settings = $this->get_settings();
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
            'welcome_title' => 'مرحباً بيك! 🎉',
            'welcome_message' => 'ابن/بنت برفوريوس! تمام! 🔔 هيوصلك كل جديد عن أسرتك — فعاليات ونقاط وأخبار!',
            'prompt_delay_seconds' => 10,
            'prompt_message' => 'ابن/بنت برفوريوس! 📲 ثبّت التطبيق على موبايلك علشان توصلك أخبار أسرتك دايماً 🔔⛪',
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
            'ابن/بنت برفوريوس! مكافأة تفعيل الإشعارات — خليك متابع! 🔔'
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
     * Uses "Subscribed Users" segment which is built-in to OneSignal
     */
    public function send_to_all($title, $message, $url = '', $data = array(), $trigger_type = 'manual') {
        $settings = $this->get_settings();
        
        if (!$this->is_configured()) {
            return new WP_Error('not_configured', 'OneSignal is not configured');
        }
        
        error_log('SP OneSignal send_to_all: Starting notification send');
        error_log('SP OneSignal send_to_all: Title=' . $title . ', Message=' . $message);
        
        // Use included_segments to target all subscribed users
        // "Total Subscriptions" is OneSignal's default segment name
        $payload = array(
            'app_id' => $settings['app_id'],
            'included_segments' => array('Total Subscriptions'),
            'headings' => array('en' => $title),
            'contents' => array('en' => $message),
            'chrome_web_icon' => SP_PLUGIN_URL . 'assets/icons/icon-192x192.png',
            'chrome_web_badge' => SP_PLUGIN_URL . 'assets/icons/icon-72x72.png',
            'url' => $url ?: home_url('/app/'),
        );
        
        // Add custom data if provided
        if (!empty($data)) {
            $payload['data'] = $data;
        }
        
        $result = $this->api_request('notifications', $payload);
        
        error_log('SP OneSignal send_to_all Result: ' . print_r($result, true));
        
        // Log the notification
        $recipients = 0;
        if (is_array($result) && isset($result['recipients'])) {
            $recipients = (int) $result['recipients'];
        }
        
        $this->log_notification($title, $message, $url, 'all', null, $result, $trigger_type);
        
        return $result;
    }
    
    /**
     * Send notification to specific user(s) by subscription IDs
     */
    public function send_to_players($player_ids, $title, $message, $url = '', $data = array()) {
        $settings = $this->get_settings();
        
        if (!$this->is_configured()) {
            return new WP_Error('not_configured', 'OneSignal is not configured');
        }
        
        if (empty($player_ids)) {
            return new WP_Error('no_targets', 'No subscription IDs provided');
        }
        
        error_log('SP OneSignal send_to_players: Targeting ' . count((array) $player_ids) . ' subscriptions');
        
        $payload = array(
            'app_id' => $settings['app_id'],
            'include_subscription_ids' => array_values((array) $player_ids),
            'headings' => array('en' => $title),
            'contents' => array('en' => $message),
            'chrome_web_icon' => SP_PLUGIN_URL . 'assets/icons/icon-192x192.png',
            'chrome_web_badge' => SP_PLUGIN_URL . 'assets/icons/icon-72x72.png',
            'url' => $url ?: home_url('/app/'),
        );
        
        if (!empty($data)) {
            $payload['data'] = $data;
        }
        
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
     * Following exact format from: https://documentation.onesignal.com/reference/create-message
     */
    private function api_request($endpoint, $payload) {
        $settings = $this->get_settings();
        
        // Build URL (remove any query params from endpoint for now, use base endpoint)
        $base_endpoint = str_replace('?c=push', '', $endpoint);
        $url = "{$this->api_url}/{$base_endpoint}";
        
        // Add target_channel to payload for push notifications
        if (strpos($endpoint, 'notifications') !== false && !isset($payload['target_channel'])) {
            $payload['target_channel'] = 'push';
        }
        
        $json_body = wp_json_encode($payload);
        
        // Debug logging
        error_log('SP OneSignal Request URL: ' . $url);
        error_log('SP OneSignal Request Headers: Authorization: Key ***' . substr($settings['api_key'], -4));
        error_log('SP OneSignal Request Body: ' . $json_body);
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Key ' . $settings['api_key'],
            ),
            'body' => $json_body,
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            error_log('SP OneSignal WP Error: ' . $response->get_error_message());
            return $response;
        }
        
        $raw_body = wp_remote_retrieve_body($response);
        $code = wp_remote_retrieve_response_code($response);
        
        error_log('SP OneSignal Response Code: ' . $code);
        error_log('SP OneSignal Response Body: ' . $raw_body);
        
        $body = json_decode($raw_body, true);
        
        // Check for specific errors
        if ($code === 401) {
            error_log('SP OneSignal Auth Error: Invalid API Key');
            return new WP_Error('auth_error', 'Invalid API Key - check REST API Key in OneSignal Settings > Keys & IDs');
        }
        
        if ($code !== 200 && $code !== 201) {
            $error_msg = isset($body['errors']) ? implode(', ', (array) $body['errors']) : ($raw_body ?: 'Unknown API error');
            error_log('SP OneSignal API Error (' . $code . '): ' . $error_msg);
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
    // IN-APP NOTIFICATION INBOX
    // ==========================================
    
    /**
     * Create an in-app notification for specific user or all users
     */
    public function create_inbox_notification($args) {
        global $wpdb;
        
        $defaults = array(
            'user_id'    => 0, // 0 = broadcast to all
            'title'      => '',
            'message'    => '',
            'body_html'  => null,
            'icon'       => '🔔',
            'type'       => 'custom', // custom, event, quiz, system, registration, points, reminder
            'link_type'  => null,     // event, quiz, page, url
            'link_id'    => null,     // event_id or quiz content_id
            'url'        => '',
            'push_log_id' => null,
            'created_by' => get_current_user_id(),
        );
        $args = wp_parse_args($args, $defaults);
        
        $wpdb->insert(
            $this->inbox_table,
            array(
                'user_id'     => absint($args['user_id']),
                'title'       => sanitize_text_field($args['title']),
                'message'     => sanitize_textarea_field($args['message']),
                'body_html'   => !empty($args['body_html']) ? wp_kses_post($args['body_html']) : null,
                'icon'        => sanitize_text_field($args['icon']),
                'type'        => sanitize_text_field($args['type']),
                'link_type'   => $args['link_type'] ? sanitize_text_field($args['link_type']) : null,
                'link_id'     => $args['link_id'] ? absint($args['link_id']) : null,
                'url'         => esc_url_raw($args['url']),
                'push_log_id' => $args['push_log_id'] ? absint($args['push_log_id']) : null,
                'is_read'     => 0,
                'created_by'  => $args['created_by'] ? absint($args['created_by']) : null,
                'created_at'  => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%d', '%s')
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Create inbox notifications for multiple specific users
     */
    public function create_inbox_for_users($user_ids, $args) {
        $ids = array();
        foreach ((array) $user_ids as $uid) {
            $args['user_id'] = $uid;
            $ids[] = $this->create_inbox_notification($args);
        }
        return $ids;
    }
    
    /**
     * Get notifications for a specific user (their personal + broadcasts)
     */
    public function get_user_notifications($user_id, $args = array()) {
        global $wpdb;
        
        $defaults = array(
            'limit'  => 30,
            'offset' => 0,
            'unread_only' => false,
        );
        $args = wp_parse_args($args, $defaults);
        
        $where = "WHERE (user_id = %d OR user_id = 0)";
        $values = array($user_id);
        
        if ($args['unread_only']) {
            $where .= " AND is_read = 0";
        }
        
        $values[] = $args['limit'];
        $values[] = $args['offset'];
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->inbox_table} $where ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $values
        ));
    }
    
    /**
     * Get single notification by ID
     */
    public function get_notification($notification_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->inbox_table} WHERE id = %d",
            $notification_id
        ));
    }
    
    /**
     * Get unread notification count for a user
     */
    public function get_unread_count($user_id) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->inbox_table} WHERE (user_id = %d OR user_id = 0) AND is_read = 0",
            $user_id
        ));
    }
    
    /**
     * Check if a specific broadcast notification was read by user
     * For broadcast (user_id=0) notifications, we track reads in usermeta
     */
    public function get_user_read_broadcast_ids($user_id) {
        $read_ids = get_user_meta($user_id, 'sp_read_broadcast_notifs', true);
        return is_array($read_ids) ? $read_ids : array();
    }
    
    /**
     * Get accurate unread count considering broadcast reads
     */
    public function get_accurate_unread_count($user_id) {
        global $wpdb;
        
        // Personal unread
        $personal_unread = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->inbox_table} WHERE user_id = %d AND is_read = 0",
            $user_id
        ));
        
        // Broadcast unread (not in user's read list)
        $read_broadcast_ids = $this->get_user_read_broadcast_ids($user_id);
        
        if (!empty($read_broadcast_ids)) {
            $placeholders = implode(',', array_fill(0, count($read_broadcast_ids), '%d'));
            $broadcast_unread = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->inbox_table} WHERE user_id = 0 AND id NOT IN ($placeholders)",
                $read_broadcast_ids
            ));
        } else {
            $broadcast_unread = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->inbox_table} WHERE user_id = 0"
            );
        }
        
        return $personal_unread + $broadcast_unread;
    }
    
    /**
     * Mark a notification as read
     */
    public function mark_as_read($notification_id, $user_id) {
        global $wpdb;
        
        $notif = $this->get_notification($notification_id);
        if (!$notif) return false;
        
        // If broadcast, track in usermeta
        if ($notif->user_id == 0) {
            $read_ids = $this->get_user_read_broadcast_ids($user_id);
            if (!in_array($notification_id, $read_ids)) {
                $read_ids[] = $notification_id;
                update_user_meta($user_id, 'sp_read_broadcast_notifs', $read_ids);
            }
            return true;
        }
        
        // Personal notification - update directly
        if ($notif->user_id == $user_id) {
            return $wpdb->update(
                $this->inbox_table,
                array('is_read' => 1),
                array('id' => $notification_id),
                array('%d'),
                array('%d')
            );
        }
        
        return false;
    }
    
    /**
     * Mark all notifications as read for a user
     */
    public function mark_all_read($user_id) {
        global $wpdb;
        
        // Mark personal notifications
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->inbox_table} SET is_read = 1 WHERE user_id = %d AND is_read = 0",
            $user_id
        ));
        
        // Mark all broadcasts as read
        $broadcast_ids = $wpdb->get_col(
            "SELECT id FROM {$this->inbox_table} WHERE user_id = 0"
        );
        if (!empty($broadcast_ids)) {
            update_user_meta($user_id, 'sp_read_broadcast_notifs', array_map('intval', $broadcast_ids));
        }
        
        return true;
    }
    
    /**
     * Check if a notification is read by a specific user
     */
    public function is_notification_read($notification_id, $user_id) {
        $notif = $this->get_notification($notification_id);
        if (!$notif) return true;
        
        if ($notif->user_id == 0) {
            $read_ids = $this->get_user_read_broadcast_ids($user_id);
            return in_array($notification_id, $read_ids);
        }
        
        return (bool) $notif->is_read;
    }
    
    /**
     * Build the URL for a notification based on its link type
     */
    public function get_notification_url($notification) {
        if (!empty($notification->link_type) && !empty($notification->link_id)) {
            switch ($notification->link_type) {
                case 'event':
                    return home_url('/app/events/' . intval($notification->link_id));
                case 'quiz':
                    return home_url('/app/quizzes?quiz_id=' . $notification->link_id);
            }
        }
        
        // If has body_html, link to notification detail page
        if (!empty($notification->body_html)) {
            return home_url('/app/notifications?view=' . $notification->id);
        }
        
        // Fallback to stored URL
        if (!empty($notification->url)) {
            return $notification->url;
        }
        
        return home_url('/app/notifications');
    }
    
    // ==========================================
    // AUTO NOTIFICATION TRIGGERS
    // ==========================================
    
    /**
     * Notify about new event
     */
    public function notify_new_event($event) {
        $title = '📅 فعالية جديدة';
        $message = sprintf('أبناء وبنات برفوريوس! 📅 فعالية جديدة: %s — يوم %s. سجّلوا حضوركم!', 
            $event->title_ar ?: $event->title,
            date_i18n('j F Y', strtotime($event->event_date))
        );
        $url = home_url('/app/events/' . intval($event->id));
        
        // Create in-app inbox notification (broadcast to all)
        $this->create_inbox_notification(array(
            'user_id'   => 0,
            'title'     => $title,
            'message'   => $message,
            'icon'      => '📅',
            'type'      => 'event',
            'link_type' => 'event',
            'link_id'   => $event->id,
            'url'       => $url,
        ));
        
        // Send push notification
        if ($this->is_configured()) {
            $this->send_to_all($title, $message, $url, array(), 'auto_event');
        }
    }
    
    /**
     * Notify a user that their registration was approved
     */
    public function notify_user_approved($user_id) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $title = '🎉 تم قبول طلبك!';
        $gender = get_user_meta($user_id, 'sp_gender', true) ?: 'male';
        $first_name = $user->first_name ?: $user->display_name;
        if ($gender === 'female') {
            $message = sprintf('بنت برفوريوس! مرحباً! 🎉 تم قبول طلب انضمامك لأسرة القديس برفوريوس — نورتينا %s وأهلاً بيكي وسطينا! ⛪', $first_name);
        } else {
            $message = sprintf('ابن برفوريوس! مرحباً! 🎉 تم قبول طلب انضمامك لأسرة القديس برفوريوس — نورتنا %s وأهلاً بيك وسطينا! ⛪', $first_name);
        }
        $url = home_url('/app/dashboard');
        
        // Create in-app inbox notification
        $this->create_inbox_notification(array(
            'user_id'  => $user_id,
            'title'    => $title,
            'message'  => $message,
            'icon'     => '🎉',
            'type'     => 'registration',
            'url'      => $url,
        ));
        
        // Send push notification
        if ($this->is_configured()) {
            $this->send_to_users(array($user_id), $title, $message, $url, 'auto_registration');
        }
    }
    
    /**
     * Notify about a new quiz
     */
    public function notify_new_quiz($content) {
        $title = '📝 اختبار جديد';
        $message = sprintf('أبناء برفوريوس! 📝 اختبار جديد: %s — جاوبوا واكسبوا نقاط! 🏆', $content->title_ar);
        $url = home_url('/app/quizzes?quiz_id=' . $content->id);
        
        // Create in-app inbox notification (broadcast to all)
        $this->create_inbox_notification(array(
            'user_id'   => 0,
            'title'     => $title,
            'message'   => $message,
            'icon'      => '📝',
            'type'      => 'quiz',
            'link_type' => 'quiz',
            'link_id'   => $content->id,
            'url'       => $url,
        ));
        
        // Send push notification
        if ($this->is_configured()) {
            $this->send_to_all($title, $message, $url, array(), 'auto_quiz');
        }
    }
    
    /**
     * Notify user about points milestone
     */
    public function notify_points_milestone($user_id, $milestone) {
        $title = '🏆 ابن/بنت برفوريوس! مبروك!';
        $message = sprintf('ابن/بنت برفوريوس! مبروك! 🏆 وصلت لـ %d نقطة — أسرة برفوريوس فخورة بيك! استمر 💪', $milestone);
        $url = home_url('/app/points');
        
        // Create in-app inbox notification
        $this->create_inbox_notification(array(
            'user_id'  => $user_id,
            'title'    => $title,
            'message'  => $message,
            'icon'     => '🏆',
            'type'     => 'points',
            'url'      => $url,
        ));
        
        // Send push notification
        if ($this->is_configured()) {
            $this->send_to_users(array($user_id), $title, $message, $url, 'auto_points');
        }
    }

    /**
     * Notify user about points added or deducted
     */
    public function notify_points_change($user_id, $points, $new_balance, $reason = '') {
        if ($points == 0) return;

        $url = home_url('/app/points');

        if ($points > 0) {
            $icon = '⭐';
            $title = sprintf('⭐ +%d نقطة', $points);
            $message = $reason
                ? sprintf('ابن/بنت برفوريوس! أحسنت! ⭐ +%d نقطة (%s). رصيدك دلوقتي %d نقطة — استمر! 💪', $points, $reason, $new_balance)
                : sprintf('ابن/بنت برفوريوس! أحسنت! ⭐ +%d نقطة! رصيدك دلوقتي %d نقطة — استمر! 💪', $points, $new_balance);
        } else {
            $icon = '📉';
            $abs = abs($points);
            $title = sprintf('📉 -%d نقطة', $abs);
            $message = $reason
                ? sprintf('📉 ابن/بنت برفوريوس! تم خصم %d نقطة (%s). رصيدك %d نقطة — وحشتنا! مستنينك المرة الجاية 🙏', $abs, $reason, $new_balance)
                : sprintf('📉 ابن/بنت برفوريوس! تم خصم %d نقطة. رصيدك %d نقطة — وحشتنا! 🙏', $abs, $new_balance);
        }

        // Create in-app inbox notification
        $this->create_inbox_notification(array(
            'user_id'  => $user_id,
            'title'    => $title,
            'message'  => $message,
            'icon'     => $icon,
            'type'     => 'points',
            'url'      => $url,
        ));

        // Send push notification
        if ($this->is_configured()) {
            $this->send_to_users(array($user_id), $title, $message, $url, 'auto_points');
        }
    }

    /**
     * Send event reminders for upcoming events
     */
    public function send_event_reminders() {
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
                
                $title = '⛏️ أبناء وبنات برفوريوس — تذكير!';
                $message = sprintf('%s - %s الساعة %s', 
                    $event->title_ar ?: $event->title,
                    date_i18n('j F', strtotime($event->event_date)),
                    $event->start_time
                );
                $url = home_url('/app/events/' . intval($event->id));
                
                // Create in-app inbox notification (broadcast to all)
                $this->create_inbox_notification(array(
                    'user_id'   => 0,
                    'title'     => $title,
                    'message'   => $message,
                    'icon'      => '⏰',
                    'type'      => 'reminder',
                    'link_type' => 'event',
                    'link_id'   => $event->id,
                    'url'       => $url,
                ));
                
                // Send push notification
                if ($this->is_configured()) {
                    $result = $this->send_to_all($title, $message, $url);
                    $this->log_notification($title, $message, $url, 'all', null, $result, 'auto_event_reminder');
                }
                
                // Mark reminder as sent
                update_post_meta($event->id, '_sp_reminder_sent', 1);
            }
        }
    }
    
    // ==========================================
    // ADMIN: SEND CUSTOM NOTIFICATION
    // ==========================================
    
    /**
     * Send a manual notification from admin (push + inbox)
     */
    public function send_admin_notification($title, $message, $url = '', $segment = 'all', $user_ids = array(), $extra = array()) {
        $defaults = array(
            'body_html' => '',
            'link_type' => null,
            'link_id'   => null,
            'icon'      => '🔔',
        );
        $extra = wp_parse_args($extra, $defaults);
        
        // Determine notification type from link_type
        $type = 'custom';
        if ($extra['link_type'] === 'event') $type = 'event';
        if ($extra['link_type'] === 'quiz') $type = 'quiz';
        
        // Build the URL based on link_type if not manually set
        $notif_url = $url;
        if (!empty($extra['link_type']) && !empty($extra['link_id'])) {
            if ($extra['link_type'] === 'event') {
                $notif_url = home_url('/app/events/' . intval($extra['link_id']));
            } elseif ($extra['link_type'] === 'quiz') {
                $notif_url = home_url('/app/quizzes?quiz_id=' . $extra['link_id']);
            }
        }
        
        // Create in-app inbox notifications
        if ($segment === 'specific_users' && !empty($user_ids)) {
            foreach ((array) $user_ids as $uid) {
                $inbox_id = $this->create_inbox_notification(array(
                    'user_id'   => $uid,
                    'title'     => $title,
                    'message'   => $message,
                    'body_html' => $extra['body_html'],
                    'icon'      => $extra['icon'],
                    'type'      => $type,
                    'link_type' => $extra['link_type'],
                    'link_id'   => $extra['link_id'],
                    'url'       => $notif_url,
                ));
                
                // If body_html provided and no specific URL, auto-link to notification page
                if (!empty($extra['body_html']) && empty($url) && empty($extra['link_type'])) {
                    global $wpdb;
                    $wpdb->update(
                        $this->inbox_table,
                        array('url' => home_url('/app/notifications?view=' . $inbox_id)),
                        array('id' => $inbox_id)
                    );
                    $notif_url = home_url('/app/notifications?view=' . $inbox_id);
                }
            }
        } else {
            // Broadcast to all
            $inbox_id = $this->create_inbox_notification(array(
                'user_id'   => 0,
                'title'     => $title,
                'message'   => $message,
                'body_html' => $extra['body_html'],
                'icon'      => $extra['icon'],
                'type'      => $type,
                'link_type' => $extra['link_type'],
                'link_id'   => $extra['link_id'],
                'url'       => $notif_url,
            ));
            
            // If body_html provided and no specific URL, auto-link to notification page
            if (!empty($extra['body_html']) && empty($url) && empty($extra['link_type'])) {
                global $wpdb;
                $wpdb->update(
                    $this->inbox_table,
                    array('url' => home_url('/app/notifications?view=' . $inbox_id)),
                    array('id' => $inbox_id)
                );
                $notif_url = home_url('/app/notifications?view=' . $inbox_id);
            }
        }
        
        // Send push notification via OneSignal
        if (!$this->is_configured()) {
            // Return success even without push - inbox notification was created
            return array('success' => true, 'recipients' => 0, 'inbox_only' => true);
        }
        
        if (empty($title) || empty($message)) {
            return new WP_Error('missing_data', 'العنوان والرسالة مطلوبان');
        }
        
        if ($segment === 'specific_users' && !empty($user_ids)) {
            return $this->send_to_users($user_ids, $title, $message, $notif_url ?: $url, 'manual');
        }
        
        // Send to all subscribers
        return $this->send_to_all($title, $message, $notif_url ?: $url);
    }
    
    /**
     * Test OneSignal connection by checking app config
     * Note: This uses a simple API call to verify credentials
     */
    public function test_connection() {
        $settings = $this->get_settings();
        
        if (empty($settings['app_id']) || empty($settings['api_key'])) {
            return new WP_Error('missing_credentials', 'App ID and API Key are required');
        }
        
        error_log('SP OneSignal test_connection: Testing with App ID=' . substr($settings['app_id'], 0, 8) . '...');
        
        // Test by making a simple GET request to view notifications (limit 1)
        $url = "{$this->api_url}/notifications?app_id={$settings['app_id']}&limit=1";
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Key ' . $settings['api_key'],
            ),
            'timeout' => 15,
        ));
        
        if (is_wp_error($response)) {
            error_log('SP OneSignal test_connection WP Error: ' . $response->get_error_message());
            return $response;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $raw_body = wp_remote_retrieve_body($response);
        
        error_log('SP OneSignal test_connection Response Code: ' . $code);
        error_log('SP OneSignal test_connection Response: ' . $raw_body);
        
        if ($code === 200) {
            $body = json_decode($raw_body, true);
            return array(
                'success' => true,
                'app_id' => $settings['app_id'],
                'total_notifications' => isset($body['total_count']) ? (int) $body['total_count'] : 0,
                'message' => 'Connection successful! API Key is valid.',
            );
        }
        
        if ($code === 401) {
            return new WP_Error('auth_failed', 'API Key is invalid. Please check your REST API Key from OneSignal Settings > Keys & IDs.');
        }
        
        return new WP_Error('connection_failed', 'Connection failed with code ' . $code . ': ' . $raw_body);
    }
}

// Initialize
SP_Notifications::get_instance();
