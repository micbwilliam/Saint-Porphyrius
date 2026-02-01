<?php
/**
 * Saint Porphyrius - Expected Attendance Handler
 * Manages users who plan to attend events
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Expected_Attendance {
    
    private static $instance = null;
    private $table_name;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sp_expected_attendance';
    }
    
    /**
     * Register a user as planning to attend an event
     */
    public function register($event_id, $user_id) {
        global $wpdb;
        
        // Check if event exists and has expected attendance enabled
        $events_handler = SP_Events::get_instance();
        $event = $events_handler->get($event_id);
        
        if (!$event) {
            return array(
                'success' => false,
                'message' => __('الفعالية غير موجودة', 'saint-porphyrius')
            );
        }
        
        if (empty($event->expected_attendance_enabled)) {
            return array(
                'success' => false,
                'message' => __('تسجيل الحضور المتوقع غير مفعل لهذه الفعالية', 'saint-porphyrius')
            );
        }
        
        // Check if event is in the future
        $event_datetime = strtotime($event->event_date . ' ' . $event->start_time);
        if ($event_datetime < time()) {
            return array(
                'success' => false,
                'message' => __('لا يمكن التسجيل لفعالية منتهية', 'saint-porphyrius')
            );
        }
        
        // Check if user is forbidden
        $forbidden_handler = SP_Forbidden::get_instance();
        $user_status = $forbidden_handler->get_user_status($user_id);
        if ($user_status->forbidden_remaining > 0 && !empty($event->forbidden_enabled)) {
            return array(
                'success' => false,
                'message' => __('لا يمكنك التسجيل - أنت محروم من هذه الفعالية', 'saint-porphyrius')
            );
        }
        
        // Check if user has an approved excuse
        $excuses_handler = SP_Excuses::get_instance();
        $user_excuse = $excuses_handler->get_user_excuse($event_id, $user_id);
        if ($user_excuse && $user_excuse->status === 'approved') {
            return array(
                'success' => false,
                'message' => __('لا يمكنك التسجيل - لديك اعتذار مقبول عن هذه الفعالية', 'saint-porphyrius')
            );
        }
        
        // Check if already registered
        $existing = $this->get($event_id, $user_id);
        if ($existing) {
            return array(
                'success' => false,
                'message' => __('أنت مسجل مسبقاً', 'saint-porphyrius')
            );
        }
        
        // Get next order number
        $max_order = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(order_number) FROM {$this->table_name} WHERE event_id = %d",
            $event_id
        ));
        $order_number = $max_order ? (int)$max_order + 1 : 1;
        
        // Insert registration
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'event_id' => $event_id,
                'user_id' => $user_id,
                'order_number' => $order_number,
                'registered_at' => current_time('mysql')
            ),
            array('%d', '%d', '%d', '%s')
        );
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => __('حدث خطأ أثناء التسجيل', 'saint-porphyrius')
            );
        }
        
        return array(
            'success' => true,
            'message' => __('تم تسجيلك بنجاح', 'saint-porphyrius'),
            'order_number' => $order_number
        );
    }
    
    /**
     * Unregister a user from expected attendance
     */
    public function unregister($event_id, $user_id) {
        global $wpdb;
        
        // Check if event exists
        $events_handler = SP_Events::get_instance();
        $event = $events_handler->get($event_id);
        
        if (!$event) {
            return array(
                'success' => false,
                'message' => __('الفعالية غير موجودة', 'saint-porphyrius')
            );
        }
        
        // Check if event is in the future
        $event_datetime = strtotime($event->event_date . ' ' . $event->start_time);
        if ($event_datetime < time()) {
            return array(
                'success' => false,
                'message' => __('لا يمكن إلغاء التسجيل لفعالية منتهية', 'saint-porphyrius')
            );
        }
        
        // Get current registration
        $existing = $this->get($event_id, $user_id);
        if (!$existing) {
            return array(
                'success' => false,
                'message' => __('أنت غير مسجل في هذه الفعالية', 'saint-porphyrius')
            );
        }
        
        $removed_order = $existing->order_number;
        
        // Delete registration
        $result = $wpdb->delete(
            $this->table_name,
            array(
                'event_id' => $event_id,
                'user_id' => $user_id
            ),
            array('%d', '%d')
        );
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => __('حدث خطأ أثناء إلغاء التسجيل', 'saint-porphyrius')
            );
        }
        
        // Reorder remaining registrations
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->table_name} 
             SET order_number = order_number - 1 
             WHERE event_id = %d AND order_number > %d",
            $event_id,
            $removed_order
        ));
        
        return array(
            'success' => true,
            'message' => __('تم إلغاء تسجيلك', 'saint-porphyrius')
        );
    }
    
    /**
     * Get a specific registration
     */
    public function get($event_id, $user_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE event_id = %d AND user_id = %d",
            $event_id,
            $user_id
        ));
    }
    
    /**
     * Get all registrations for an event with user details and status
     */
    public function get_event_registrations($event_id) {
        global $wpdb;
        
        $users_table = $wpdb->users;
        $usermeta_table = $wpdb->usermeta;
        $forbidden_table = $wpdb->prefix . 'sp_forbidden_status';
        $excuses_table = $wpdb->prefix . 'sp_excuses';
        $attendance_table = $wpdb->prefix . 'sp_attendance';
        
        // Get event to check forbidden_enabled
        $events_handler = SP_Events::get_instance();
        $event = $events_handler->get($event_id);
        $forbidden_enabled = $event ? !empty($event->forbidden_enabled) : false;
        
        $registrations = $wpdb->get_results($wpdb->prepare("
            SELECT 
                ea.id,
                ea.event_id,
                ea.user_id,
                ea.order_number,
                ea.registered_at,
                u.display_name,
                um.meta_value as name_ar,
                fs.forbidden_remaining,
                fs.card_status,
                ex.status as excuse_status,
                att.status as attendance_status
            FROM {$this->table_name} ea
            LEFT JOIN {$users_table} u ON ea.user_id = u.ID
            LEFT JOIN {$usermeta_table} um ON ea.user_id = um.user_id AND um.meta_key = 'sp_name_ar'
            LEFT JOIN {$forbidden_table} fs ON ea.user_id = fs.user_id
            LEFT JOIN {$excuses_table} ex ON ea.event_id = ex.event_id AND ea.user_id = ex.user_id AND ex.status = 'approved'
            LEFT JOIN {$attendance_table} att ON ea.event_id = att.event_id AND ea.user_id = att.user_id
            WHERE ea.event_id = %d
            ORDER BY ea.order_number ASC
        ", $event_id));
        
        // Process results to add status info
        foreach ($registrations as &$reg) {
            $reg->display_name_final = !empty($reg->name_ar) ? $reg->name_ar : $reg->display_name;
            
            // Determine user status for this event
            $reg->status = 'registered'; // Default
            $reg->status_label = __('مسجل', 'saint-porphyrius');
            $reg->status_color = '#3B82F6'; // Blue
            
            if (!empty($reg->attendance_status)) {
                switch ($reg->attendance_status) {
                    case 'attended':
                        $reg->status = 'attended';
                        $reg->status_label = __('حاضر', 'saint-porphyrius');
                        $reg->status_color = '#10B981'; // Green
                        break;
                    case 'late':
                        $reg->status = 'late';
                        $reg->status_label = __('متأخر', 'saint-porphyrius');
                        $reg->status_color = '#F59E0B'; // Amber
                        break;
                    case 'forbidden':
                        $reg->status = 'forbidden';
                        $reg->status_label = __('محروم', 'saint-porphyrius');
                        $reg->status_color = '#EF4444'; // Red
                        break;
                    case 'excused':
                        $reg->status = 'excused';
                        $reg->status_label = __('معتذر', 'saint-porphyrius');
                        $reg->status_color = '#8B5CF6'; // Purple
                        break;
                }
            } elseif (!empty($reg->excuse_status) && $reg->excuse_status === 'approved') {
                $reg->status = 'excused';
                $reg->status_label = __('معتذر', 'saint-porphyrius');
                $reg->status_color = '#8B5CF6'; // Purple
            } elseif ($forbidden_enabled && !empty($reg->forbidden_remaining) && $reg->forbidden_remaining > 0) {
                $reg->status = 'forbidden';
                $reg->status_label = __('محروم', 'saint-porphyrius');
                $reg->status_color = '#EF4444'; // Red
            }
            
            // Card status indicator
            $reg->has_yellow_card = ($reg->card_status === 'yellow');
            $reg->has_red_card = ($reg->card_status === 'red');
        }
        
        return $registrations;
    }
    
    /**
     * Get registration count for an event
     */
    public function get_count($event_id) {
        global $wpdb;
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE event_id = %d",
            $event_id
        ));
    }
    
    /**
     * Check if user is registered for an event
     */
    public function is_registered($event_id, $user_id) {
        return $this->get($event_id, $user_id) !== null;
    }
    
    /**
     * Get user's order number for an event
     */
    public function get_user_order($event_id, $user_id) {
        $registration = $this->get($event_id, $user_id);
        return $registration ? $registration->order_number : null;
    }
    
    /**
     * Delete all registrations for an event
     */
    public function delete_event_registrations($event_id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            array('event_id' => $event_id),
            array('%d')
        );
    }
    
    /**
     * Get user's upcoming registrations
     */
    public function get_user_registrations($user_id, $limit = 10) {
        global $wpdb;
        
        $events_table = $wpdb->prefix . 'sp_events';
        $types_table = $wpdb->prefix . 'sp_event_types';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                ea.*,
                e.title_ar,
                e.title_en,
                e.event_date,
                e.start_time,
                e.location_name,
                et.name_ar as type_name_ar,
                et.icon as type_icon,
                et.color as type_color
            FROM {$this->table_name} ea
            INNER JOIN {$events_table} e ON ea.event_id = e.id
            LEFT JOIN {$types_table} et ON e.event_type_id = et.id
            WHERE ea.user_id = %d 
                AND e.event_date >= %s 
                AND e.status = 'published'
            ORDER BY e.event_date ASC, e.start_time ASC
            LIMIT %d
        ", $user_id, current_time('Y-m-d'), $limit));
    }
    
    /**
     * Auto-remove forbidden users from expected attendance when they become forbidden
     */
    public function remove_forbidden_user($user_id) {
        global $wpdb;
        
        $events_table = $wpdb->prefix . 'sp_events';
        
        // Get all future events with forbidden_enabled where user is registered
        $registrations = $wpdb->get_results($wpdb->prepare("
            SELECT ea.event_id, ea.order_number
            FROM {$this->table_name} ea
            INNER JOIN {$events_table} e ON ea.event_id = e.id
            WHERE ea.user_id = %d 
                AND e.event_date >= %s 
                AND e.forbidden_enabled = 1
        ", $user_id, current_time('Y-m-d')));
        
        foreach ($registrations as $reg) {
            $this->unregister($reg->event_id, $user_id);
        }
        
        return count($registrations);
    }
}
