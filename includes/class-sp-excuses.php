<?php
/**
 * Saint Porphyrius - Excuses Handler
 * Manages excuse submissions for mandatory events
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Excuses {
    
    private static $instance = null;
    private $table_name;
    private $events_table;
    private $event_types_table;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sp_excuses';
        $this->events_table = $wpdb->prefix . 'sp_events';
        $this->event_types_table = $wpdb->prefix . 'sp_event_types';
    }
    
    /**
     * Get excuse cost based on days before event
     */
    public function get_excuse_cost($event_id) {
        global $wpdb;
        
        // Get event with type info
        $event = $wpdb->get_row($wpdb->prepare("
            SELECT e.*, et.excuse_points_7plus, et.excuse_points_6, et.excuse_points_5,
                   et.excuse_points_4, et.excuse_points_3, et.excuse_points_2,
                   et.excuse_points_1, et.excuse_points_0
            FROM {$this->events_table} e
            JOIN {$this->event_types_table} et ON e.event_type_id = et.id
            WHERE e.id = %d
        ", $event_id));
        
        if (!$event) {
            return false;
        }
        
        // Check if event is mandatory
        if (!$event->is_mandatory) {
            return array(
                'cost' => 0,
                'days_before' => 0,
                'message' => __('الفعالية غير إلزامية', 'saint-porphyrius'),
                'can_submit' => false,
            );
        }
        
        // Calculate days before event
        $event_date = new DateTime($event->event_date);
        $today = new DateTime(date('Y-m-d'));
        $diff = $today->diff($event_date);
        $days_before = $diff->invert ? -$diff->days : $diff->days;
        
        // Event has passed
        if ($days_before < 0) {
            return array(
                'cost' => 0,
                'days_before' => $days_before,
                'message' => __('الفعالية قد انتهت', 'saint-porphyrius'),
                'can_submit' => false,
            );
        }
        
        // Get cost based on days before
        if ($days_before >= 7) {
            $cost = $event->excuse_points_7plus ?: 2;
        } elseif ($days_before == 6) {
            $cost = $event->excuse_points_6 ?: 3;
        } elseif ($days_before == 5) {
            $cost = $event->excuse_points_5 ?: 4;
        } elseif ($days_before == 4) {
            $cost = $event->excuse_points_4 ?: 5;
        } elseif ($days_before == 3) {
            $cost = $event->excuse_points_3 ?: 6;
        } elseif ($days_before == 2) {
            $cost = $event->excuse_points_2 ?: 7;
        } elseif ($days_before == 1) {
            $cost = $event->excuse_points_1 ?: 8;
        } else {
            $cost = $event->excuse_points_0 ?: 10;
        }
        
        return array(
            'cost' => (int) $cost,
            'days_before' => $days_before,
            'message' => sprintf(__('تكلفة الاعتذار: %d نقطة', 'saint-porphyrius'), $cost),
            'can_submit' => true,
        );
    }
    
    /**
     * Submit an excuse
     */
    public function submit($event_id, $user_id, $excuse_text) {
        global $wpdb;
        
        // Check if excuse already exists
        $existing = $this->get_user_excuse($event_id, $user_id);
        if ($existing) {
            return array(
                'success' => false,
                'message' => __('لقد قدمت اعتذاراً لهذه الفعالية مسبقاً', 'saint-porphyrius'),
            );
        }
        
        // Get excuse cost
        $cost_info = $this->get_excuse_cost($event_id);
        if (!$cost_info || !$cost_info['can_submit']) {
            return array(
                'success' => false,
                'message' => $cost_info['message'] ?? __('لا يمكن تقديم اعتذار لهذه الفعالية', 'saint-porphyrius'),
            );
        }
        
        // Check if user has enough points
        $points_handler = SP_Points::get_instance();
        $balance = $points_handler->get_balance($user_id);
        
        if ($balance < $cost_info['cost']) {
            return array(
                'success' => false,
                'message' => sprintf(
                    __('رصيدك غير كافي. تحتاج %d نقطة ولديك %d نقطة فقط', 'saint-porphyrius'),
                    $cost_info['cost'],
                    $balance
                ),
            );
        }
        
        // Insert excuse
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'event_id' => $event_id,
                'user_id' => $user_id,
                'excuse_text' => sanitize_textarea_field($excuse_text),
                'points_deducted' => $cost_info['cost'],
                'days_before_event' => $cost_info['days_before'],
                'status' => 'pending',
            ),
            array('%d', '%d', '%s', '%d', '%d', '%s')
        );
        
        if (!$result) {
            return array(
                'success' => false,
                'message' => __('حدث خطأ أثناء حفظ الاعتذار', 'saint-porphyrius'),
            );
        }
        
        // Get event title for the reason
        $events_handler = SP_Events::get_instance();
        $event = $events_handler->get($event_id);
        $event_title = $event ? $event->title : sprintf(__('فعالية #%d', 'saint-porphyrius'), $event_id);
        
        // Deduct points
        $points_handler->add(
            $user_id,
            -$cost_info['cost'],
            'excuse_submission',
            sprintf(__('رسوم تقديم اعتذار لـ: %s', 'saint-porphyrius'), $event_title),
            $event_id
        );
        
        return array(
            'success' => true,
            'message' => sprintf(
                __('تم تقديم الاعتذار بنجاح. تم خصم %d نقطة', 'saint-porphyrius'),
                $cost_info['cost']
            ),
            'excuse_id' => $wpdb->insert_id,
        );
    }
    
    /**
     * Get user's excuse for an event
     */
    public function get_user_excuse($event_id, $user_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$this->table_name}
            WHERE event_id = %d AND user_id = %d
        ", $event_id, $user_id));
    }
    
    /**
     * Get all excuses for an event
     */
    public function get_event_excuses($event_id, $status = null) {
        global $wpdb;
        
        $sql = "SELECT e.*, u.display_name, 
                       um.meta_value as name_ar
                FROM {$this->table_name} e
                JOIN {$wpdb->users} u ON e.user_id = u.ID
                LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'sp_name_ar'
                WHERE e.event_id = %d";
        
        $params = array($event_id);
        
        if ($status) {
            $sql .= " AND e.status = %s";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY e.created_at DESC";
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
    
    /**
     * Get all pending excuses
     */
    public function get_pending_excuses($limit = 50) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT e.*, u.display_name, 
                   um.meta_value as name_ar,
                   ev.title_ar as event_title,
                   ev.event_date
            FROM {$this->table_name} e
            JOIN {$wpdb->users} u ON e.user_id = u.ID
            LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'sp_name_ar'
            JOIN {$this->events_table} ev ON e.event_id = ev.id
            WHERE e.status = 'pending'
            ORDER BY ev.event_date ASC, e.created_at ASC
            LIMIT %d
        ", $limit));
    }
    
    /**
     * Get all excuses with filters
     */
    public function get_all($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => null,
            'event_id' => null,
            'user_id' => null,
            'limit' => 50,
            'offset' => 0,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $sql = "SELECT e.*, u.display_name, 
                       um.meta_value as name_ar,
                       ev.title_ar as event_title,
                       ev.event_date,
                       et.name_ar as event_type_name
                FROM {$this->table_name} e
                JOIN {$wpdb->users} u ON e.user_id = u.ID
                LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'sp_name_ar'
                JOIN {$this->events_table} ev ON e.event_id = ev.id
                LEFT JOIN {$this->event_types_table} et ON ev.event_type_id = et.id
                WHERE 1=1";
        
        $params = array();
        
        if ($args['status']) {
            $sql .= " AND e.status = %s";
            $params[] = $args['status'];
        }
        
        if ($args['event_id']) {
            $sql .= " AND e.event_id = %d";
            $params[] = $args['event_id'];
        }
        
        if ($args['user_id']) {
            $sql .= " AND e.user_id = %d";
            $params[] = $args['user_id'];
        }
        
        $sql .= " ORDER BY e.created_at DESC LIMIT %d OFFSET %d";
        $params[] = $args['limit'];
        $params[] = $args['offset'];
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
    
    /**
     * Get user's excuses
     */
    public function get_user_excuses($user_id, $limit = 20) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT e.*, 
                   ev.title_ar as event_title,
                   ev.event_date,
                   et.icon as event_icon,
                   et.color as event_color
            FROM {$this->table_name} e
            JOIN {$this->events_table} ev ON e.event_id = ev.id
            LEFT JOIN {$this->event_types_table} et ON ev.event_type_id = et.id
            WHERE e.user_id = %d
            ORDER BY e.created_at DESC
            LIMIT %d
        ", $user_id, $limit));
    }
    
    /**
     * Approve excuse
     */
    public function approve($excuse_id, $admin_id, $admin_notes = '') {
        global $wpdb;
        
        $excuse = $this->get($excuse_id);
        if (!$excuse) {
            return array('success' => false, 'message' => __('الاعتذار غير موجود', 'saint-porphyrius'));
        }
        
        if ($excuse->status !== 'pending') {
            return array('success' => false, 'message' => __('تم مراجعة هذا الاعتذار مسبقاً', 'saint-porphyrius'));
        }
        
        $result = $wpdb->update(
            $this->table_name,
            array(
                'status' => 'approved',
                'admin_id' => $admin_id,
                'admin_notes' => sanitize_textarea_field($admin_notes),
                'reviewed_at' => current_time('mysql'),
            ),
            array('id' => $excuse_id),
            array('%s', '%d', '%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            return array('success' => false, 'message' => __('حدث خطأ أثناء تحديث الاعتذار', 'saint-porphyrius'));
        }
        
        // Mark attendance as excused
        $attendance_handler = SP_Attendance::get_instance();
        $attendance_handler->mark($excuse->event_id, $excuse->user_id, 'excused', __('Excused via approved excuse', 'saint-porphyrius'));
        
        return array('success' => true, 'message' => __('تم قبول الاعتذار بنجاح', 'saint-porphyrius'));
    }
    
    /**
     * Deny excuse
     */
    public function deny($excuse_id, $admin_id, $admin_notes = '') {
        global $wpdb;
        
        $excuse = $this->get($excuse_id);
        if (!$excuse) {
            return array('success' => false, 'message' => __('الاعتذار غير موجود', 'saint-porphyrius'));
        }
        
        if ($excuse->status !== 'pending') {
            return array('success' => false, 'message' => __('تم مراجعة هذا الاعتذار مسبقاً', 'saint-porphyrius'));
        }
        
        $result = $wpdb->update(
            $this->table_name,
            array(
                'status' => 'denied',
                'admin_id' => $admin_id,
                'admin_notes' => sanitize_textarea_field($admin_notes),
                'reviewed_at' => current_time('mysql'),
            ),
            array('id' => $excuse_id),
            array('%s', '%d', '%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            return array('success' => false, 'message' => __('حدث خطأ أثناء تحديث الاعتذار', 'saint-porphyrius'));
        }
        
        // Get event title for the reason
        $events_handler = SP_Events::get_instance();
        $event = $events_handler->get($excuse->event_id);
        $event_title = $event ? $event->title : sprintf(__('فعالية #%d', 'saint-porphyrius'), $excuse->event_id);
        
        // Deduct double the original points as penalty
        $penalty_points = $excuse->points_deducted * 2;
        $points_handler = SP_Points::get_instance();
        $points_handler->add(
            $excuse->user_id,
            -$penalty_points,
            'excuse_denied',
            sprintf(__('رفض الاعتذار لـ: %s - خصم مضاعف', 'saint-porphyrius'), $event_title),
            $excuse->event_id
        );
        
        return array(
            'success' => true, 
            'message' => sprintf(
                __('تم رفض الاعتذار وخصم %d نقطة إضافية من العضو', 'saint-porphyrius'),
                $penalty_points
            )
        );
    }
    
    /**
     * Get single excuse
     */
    public function get($excuse_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$this->table_name} WHERE id = %d
        ", $excuse_id));
    }
    
    /**
     * Count pending excuses
     */
    public function count_pending() {
        global $wpdb;
        
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'pending'");
    }
    
    /**
     * Get excuse status label in Arabic
     */
    public static function get_status_label($status) {
        $labels = array(
            'pending' => __('قيد المراجعة', 'saint-porphyrius'),
            'approved' => __('مقبول', 'saint-porphyrius'),
            'denied' => __('مرفوض', 'saint-porphyrius'),
        );
        
        return $labels[$status] ?? $status;
    }
    
    /**
     * Get excuse status color
     */
    public static function get_status_color($status) {
        $colors = array(
            'pending' => '#F59E0B',
            'approved' => '#10B981',
            'denied' => '#EF4444',
        );
        
        return $colors[$status] ?? '#6B7280';
    }
}
