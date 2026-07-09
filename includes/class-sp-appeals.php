<?php
/**
 * Saint Porphyrius - Appeals Handler
 * Manages point appeal requests for users who attended events but couldn't scan QR
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Appeals {
    
    private static $instance = null;
    private $table_name;
    private $events_table;
    private $event_types_table;
    private $attendance_table;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sp_appeals';
        $this->events_table = $wpdb->prefix . 'sp_events';
        $this->event_types_table = $wpdb->prefix . 'sp_event_types';
        $this->attendance_table = $wpdb->prefix . 'sp_attendance';
    }
    
    /**
     * Submit a new appeal
     */
    public function submit($user_id, $event_id, $reason) {
        global $wpdb;
        
        // Verify event exists
        $events_handler = SP_Events::get_instance();
        $event = $events_handler->get($event_id);
        if (!$event) {
            return array('success' => false, 'message' => __('الفعالية غير موجودة', 'saint-porphyrius'));
        }
        
        // Check event is in the past
        if ($event->event_date >= current_time('Y-m-d')) {
            return array('success' => false, 'message' => __('لا يمكن تقديم طلب لفعالية لم تنتهِ بعد', 'saint-porphyrius'));
        }
        
        // Check if user already has attendance points for this event
        $existing_attendance = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->attendance_table} WHERE event_id = %d AND user_id = %d AND status = 'attended'",
            $event_id, $user_id
        ));
        if ($existing_attendance && $existing_attendance->points_awarded > 0) {
            return array('success' => false, 'message' => __('لديك بالفعل نقاط حضور لهذه الفعالية', 'saint-porphyrius'));
        }
        
        // Check if user already submitted an appeal for this event
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE user_id = %d AND event_id = %d",
            $user_id, $event_id
        ));
        if ($existing) {
            return array('success' => false, 'message' => __('لقد قمت بالفعل بتقديم طلب لهذه الفعالية', 'saint-porphyrius'));
        }
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'user_id' => $user_id,
                'event_id' => $event_id,
                'reason' => sanitize_textarea_field($reason),
                'status' => 'pending',
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return array('success' => false, 'message' => __('حدث خطأ أثناء تقديم الطلب', 'saint-porphyrius'));
        }
        
        // Notify admins about the new appeal
        $this->notify_admin_new_appeal($user_id, $event);
        
        return array(
            'success' => true,
            'message' => __('ابن/بنت برفوريوس! تم تقديم طلبك بنجاح. سيتم مراجعته من قبل الإدارة 🙏', 'saint-porphyrius'),
        );
    }
    
    /**
     * Process an appeal (admin action)
     * 
     * @param int    $appeal_id   Appeal ID
     * @param string $decision    full|partial_80|partial_50|denied|denied_penalty
     * @param int    $admin_id    Admin user ID
     * @param string $admin_notes Optional admin notes
     */
    public function process($appeal_id, $decision, $admin_id, $admin_notes = '') {
        global $wpdb;
        
        $appeal = $this->get($appeal_id);
        if (!$appeal) {
            return array('success' => false, 'message' => __('الطلب غير موجود', 'saint-porphyrius'));
        }
        
        if ($appeal->status !== 'pending') {
            return array('success' => false, 'message' => __('تم مراجعة هذا الطلب مسبقاً', 'saint-porphyrius'));
        }
        
        $valid_decisions = array('full', 'partial_80', 'partial_50', 'denied', 'denied_penalty');
        if (!in_array($decision, $valid_decisions, true)) {
            return array('success' => false, 'message' => __('قرار غير صالح', 'saint-porphyrius'));
        }
        
        // Get event points
        $events_handler = SP_Events::get_instance();
        $event = $events_handler->get($appeal->event_id);
        if (!$event) {
            return array('success' => false, 'message' => __('الفعالية غير موجودة', 'saint-porphyrius'));
        }
        
        $points_config = $events_handler->get_event_points($event);
        $full_points = $points_config['attendance'];
        $points_awarded = 0;
        $points_handler = SP_Points::get_instance();
        
        switch ($decision) {
            case 'full':
                $points_awarded = $full_points;
                break;
            case 'partial_80':
                $points_awarded = (int) round($full_points * 0.8);
                break;
            case 'partial_50':
                $points_awarded = (int) round($full_points * 0.5);
                break;
            case 'denied':
                $points_awarded = 0;
                break;
            case 'denied_penalty':
                $points_awarded = -5;
                break;
        }
        
        // Update appeal record
        $wpdb->update(
            $this->table_name,
            array(
                'status' => $decision,
                'points_awarded' => $points_awarded,
                'admin_id' => $admin_id,
                'admin_notes' => sanitize_textarea_field($admin_notes),
                'reviewed_at' => current_time('mysql'),
            ),
            array('id' => $appeal_id),
            array('%s', '%d', '%d', '%s', '%s'),
            array('%d')
        );
        
        // Award or deduct points
        if ($points_awarded !== 0) {
            $reason = $this->get_points_reason($decision, $event);
            $type = $points_awarded > 0 ? 'appeal_approved' : 'appeal_penalty';
            $points_handler->add(
                $appeal->user_id, $points_awarded, $type, $appeal->event_id, $reason,
                SP_Points::make_dedupe_key('appeal', $appeal_id)
            );
        }
        
        // Notify the user
        $this->notify_appeal_result($appeal, $decision, $points_awarded, $event);
        
        return array(
            'success' => true,
            'message' => $this->get_admin_result_message($decision, $points_awarded),
            'points_awarded' => $points_awarded,
        );
    }
    
    /**
     * Get a single appeal
     */
    public function get($appeal_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $appeal_id
        ));
    }
    
    /**
     * Get all appeals with filters
     */
    public function get_all($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => null,
            'user_id' => null,
            'limit' => 50,
            'offset' => 0,
        );
        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        $params = array();
        
        if ($args['status']) {
            $where[] = 'a.status = %s';
            $params[] = $args['status'];
        }
        
        if ($args['user_id']) {
            $where[] = 'a.user_id = %d';
            $params[] = $args['user_id'];
        }
        
        $where_sql = implode(' AND ', $where);
        
        $sql = "SELECT a.*, e.title_ar as event_title, e.event_date, e.start_time,
                       et.name_ar as type_name_ar, et.icon as type_icon, et.color as type_color,
                       et.attendance_points as type_attendance_points,
                       e.attendance_points as event_attendance_points
                FROM {$this->table_name} a
                LEFT JOIN {$this->events_table} e ON a.event_id = e.id
                LEFT JOIN {$this->event_types_table} et ON e.event_type_id = et.id
                WHERE $where_sql
                ORDER BY a.created_at DESC
                LIMIT %d OFFSET %d";
        
        $params[] = $args['limit'];
        $params[] = $args['offset'];
        
        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($sql, $params));
        }
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get user's appeals
     */
    public function get_user_appeals($user_id, $limit = 50) {
        return $this->get_all(array('user_id' => $user_id, 'limit' => $limit));
    }
    
    /**
     * Count pending appeals
     */
    public function count_pending() {
        global $wpdb;
        
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'pending'");
    }
    
    /**
     * Get past events eligible for appeal by user
     * Returns events from the last 30 days where user does NOT have full attendance points
     */
    public function get_appealable_events($user_id) {
        global $wpdb;
        
        $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
        
        $sql = "SELECT e.*, et.name_ar as type_name_ar, et.icon as type_icon, et.color as type_color,
                       et.attendance_points as type_attendance_points,
                       e.attendance_points as event_attendance_points
                FROM {$this->events_table} e
                LEFT JOIN {$this->event_types_table} et ON e.event_type_id = et.id
                WHERE e.event_date < %s
                  AND e.event_date >= %s
                  AND e.status IN ('published', 'completed')
                  AND e.id NOT IN (
                      SELECT event_id FROM {$this->attendance_table} 
                      WHERE user_id = %d AND status = 'attended' AND points_awarded > 0
                  )
                  AND e.id NOT IN (
                      SELECT event_id FROM {$this->table_name}
                      WHERE user_id = %d
                  )
                ORDER BY e.event_date DESC";
        
        return $wpdb->get_results($wpdb->prepare(
            $sql,
            current_time('Y-m-d'),
            $thirty_days_ago,
            $user_id,
            $user_id
        ));
    }
    
    /**
     * Generate points reason text
     */
    private function get_points_reason($decision, $event) {
        $event_title = $event->title_ar ?? sprintf(__('فعالية #%d', 'saint-porphyrius'), $event->id);
        
        switch ($decision) {
            case 'full':
                return sprintf(__('طلب مقبول (نقاط كاملة) - %s', 'saint-porphyrius'), $event_title);
            case 'partial_80':
                return sprintf(__('طلب مقبول (80%%) - %s', 'saint-porphyrius'), $event_title);
            case 'partial_50':
                return sprintf(__('طلب مقبول (50%%) - %s', 'saint-porphyrius'), $event_title);
            case 'denied_penalty':
                return sprintf(__('رفض طلب مع خصم - %s', 'saint-porphyrius'), $event_title);
            default:
                return sprintf(__('طلب نقاط - %s', 'saint-porphyrius'), $event_title);
        }
    }
    
    /**
     * Get admin result message
     */
    private function get_admin_result_message($decision, $points_awarded) {
        switch ($decision) {
            case 'full':
                return sprintf(__('تم قبول الطلب ومنح %d نقطة كاملة', 'saint-porphyrius'), $points_awarded);
            case 'partial_80':
                return sprintf(__('تم قبول الطلب ومنح %d نقطة (80%%)', 'saint-porphyrius'), $points_awarded);
            case 'partial_50':
                return sprintf(__('تم قبول الطلب ومنح %d نقطة (50%%)', 'saint-porphyrius'), $points_awarded);
            case 'denied':
                return __('تم رفض الطلب بدون خصم', 'saint-porphyrius');
            case 'denied_penalty':
                return __('تم رفض الطلب مع خصم 5 نقاط', 'saint-porphyrius');
            default:
                return __('تمت معالجة الطلب', 'saint-porphyrius');
        }
    }
    
    /**
     * Notify admins about a new appeal
     */
    private function notify_admin_new_appeal($user_id, $event) {
        $notifications = SP_Notifications::get_instance();
        $user = get_user_by('id', $user_id);
        $user_name = $user ? ($user->first_name ?: $user->display_name) : __('عضو', 'saint-porphyrius');
        $event_title = $event->title_ar ?? __('فعالية', 'saint-porphyrius');
        
        $title = '📋 طلب نقاط فعالية جديد';
        $message = sprintf(
            __('%s قدم طلب نقاط لفعالية: %s', 'saint-porphyrius'),
            $user_name,
            $event_title
        );
        $url = home_url('/app/admin/appeals?filter=pending');
        
        // Send to all admins via inbox
        $admin_ids = get_users(array(
            'role' => 'administrator',
            'fields' => 'ID',
        ));
        
        if (!empty($admin_ids)) {
            $notifications->create_inbox_for_users($admin_ids, array(
                'title' => $title,
                'message' => $message,
                'icon' => '📋',
                'type' => 'system',
                'url' => $url,
            ));
            
            // Push notification to admins
            if ($notifications->is_configured()) {
                $notifications->send_to_users($admin_ids, $title, $message, $url, 'auto_appeal');
            }
        }
    }
    
    /**
     * Notify user about appeal result
     */
    private function notify_appeal_result($appeal, $decision, $points_awarded, $event) {
        $notifications = SP_Notifications::get_instance();
        $event_title = $event->title_ar ?? sprintf(__('فعالية #%d', 'saint-porphyrius'), $event->id);
        
        switch ($decision) {
            case 'full':
                $title = '✅ ابن/بنت برفوريوس! تم قبول طلبك!';
                $message = sprintf(
                    __('طلبك على فعالية %s تم قبوله ومنحك %d نقطة كاملة — أحسنت! 🙏', 'saint-porphyrius'),
                    $event_title,
                    $points_awarded
                );
                $icon = '✅';
                break;
            case 'partial_80':
                $title = '✅ ابن/بنت برفوريوس! تم قبول طلبك جزئياً';
                $message = sprintf(
                    __('طلبك على فعالية %s تم قبوله ومنحك %d نقطة (80%%) — استمر! 💪', 'saint-porphyrius'),
                    $event_title,
                    $points_awarded
                );
                $icon = '✅';
                break;
            case 'partial_50':
                $title = '✅ ابن/بنت برفوريوس! تم قبول طلبك جزئياً';
                $message = sprintf(
                    __('طلبك على فعالية %s تم قبوله ومنحك %d نقطة (50%%) — حاول تلتزم أكتر! 💪', 'saint-porphyrius'),
                    $event_title,
                    $points_awarded
                );
                $icon = '✅';
                break;
            case 'denied':
                $title = '❌ ابن/بنت برفوريوس! تم رفض طلبك';
                $message = sprintf(
                    __('طلبك على فعالية %s تم رفضه. حاول تلتزم أكتر — أسرتك محتاجاك! 💪', 'saint-porphyrius'),
                    $event_title
                );
                $icon = '❌';
                break;
            case 'denied_penalty':
                $title = '❌ ابن/بنت برفوريوس! تم رفض طلبك مع خصم';
                $message = sprintf(
                    __('طلبك على فعالية %s تم رفضه مع خصم 5 نقاط. حاول تلتزم أكتر — أسرتك محتاجاك! 💪', 'saint-porphyrius'),
                    $event_title
                );
                $icon = '⛔';
                break;
            default:
                $title = '📋 ابن/بنت برفوريوس! تحديث على طلبك';
                $message = sprintf(__('تم تحديث حالة طلبك على فعالية: %s', 'saint-porphyrius'), $event_title);
                $icon = '📋';
                break;
        }
        
        $url = home_url('/app/appeals');
        
        // Create in-app inbox notification
        $notifications->create_inbox_notification(array(
            'user_id' => $appeal->user_id,
            'title' => $title,
            'message' => $message,
            'icon' => $icon,
            'type' => 'system',
            'url' => $url,
        ));
        
        // Send push notification
        if ($notifications->is_configured()) {
            $notifications->send_to_users(array($appeal->user_id), $title, $message, $url, 'auto_appeal');
        }
    }
    
    /**
     * Get status label in Arabic
     */
    public static function get_status_label($status) {
        $labels = array(
            'pending' => __('قيد المراجعة', 'saint-porphyrius'),
            'full' => __('مقبول (كامل)', 'saint-porphyrius'),
            'partial_80' => __('مقبول (80%)', 'saint-porphyrius'),
            'partial_50' => __('مقبول (50%)', 'saint-porphyrius'),
            'denied' => __('مرفوض', 'saint-porphyrius'),
            'denied_penalty' => __('مرفوض مع خصم', 'saint-porphyrius'),
        );
        
        return $labels[$status] ?? $status;
    }
    
    /**
     * Get status color
     */
    public static function get_status_color($status) {
        $colors = array(
            'pending' => '#F59E0B',
            'full' => '#10B981',
            'partial_80' => '#3B82F6',
            'partial_50' => '#6366F1',
            'denied' => '#EF4444',
            'denied_penalty' => '#DC2626',
        );
        
        return $colors[$status] ?? '#6B7280';
    }
}
