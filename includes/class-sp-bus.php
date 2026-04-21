<?php
/**
 * Saint Porphyrius - Bus Booking Handler
 * Manages bus templates, event buses, and seat bookings
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Bus {
    
    private static $instance = null;
    private $templates_table;
    private $event_buses_table;
    private $bookings_table;
    private $waiting_list_table;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->templates_table = $wpdb->prefix . 'sp_bus_templates';
        $this->event_buses_table = $wpdb->prefix . 'sp_event_buses';
        $this->bookings_table = $wpdb->prefix . 'sp_bus_seat_bookings';
        $this->waiting_list_table = $wpdb->prefix . 'sp_bus_waiting_list';
    }
    
    // ==========================================
    // BUS TEMPLATES METHODS
    // ==========================================
    
    /**
     * Get all bus templates
     */
    public function get_templates($active_only = false) {
        global $wpdb;
        
        $where = $active_only ? "WHERE is_active = 1" : "";
        return $wpdb->get_results("SELECT * FROM {$this->templates_table} $where ORDER BY capacity ASC");
    }
    
    /**
     * Get bus template by ID
     */
    public function get_template($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->templates_table} WHERE id = %d",
            $id
        ));
    }
    
    /**
     * Create bus template
     */
    public function create_template($data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->templates_table,
            array(
                'name_ar' => sanitize_text_field($data['name_ar']),
                'name_en' => sanitize_text_field($data['name_en'] ?? ''),
                'capacity' => absint($data['capacity']),
                'rows' => absint($data['rows'] ?? 10),
                'seats_per_row' => absint($data['seats_per_row'] ?? 4),
                'aisle_position' => absint($data['aisle_position'] ?? 2),
                'layout_config' => isset($data['layout_config']) ? wp_json_encode($data['layout_config']) : null,
                'icon' => sanitize_text_field($data['icon'] ?? '🚌'),
                'color' => sanitize_hex_color($data['color'] ?? '#3B82F6'),
                'is_active' => isset($data['is_active']) ? (int) $data['is_active'] : 1,
            ),
            array('%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('فشل في إنشاء قالب الباص', 'saint-porphyrius'));
        }
        
        return array(
            'success' => true,
            'id' => $wpdb->insert_id,
            'message' => __('تم إنشاء قالب الباص بنجاح', 'saint-porphyrius')
        );
    }
    
    /**
     * Update bus template
     */
    public function update_template($id, $data) {
        global $wpdb;
        
        $update_data = array();
        $format = array();
        
        $fields = array(
            'name_ar' => '%s',
            'name_en' => '%s',
            'capacity' => '%d',
            'rows' => '%d',
            'seats_per_row' => '%d',
            'aisle_position' => '%d',
            'icon' => '%s',
            'color' => '%s',
            'is_active' => '%d',
        );
        
        foreach ($fields as $field => $field_format) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
                $format[] = $field_format;
            }
        }
        
        if (isset($data['layout_config'])) {
            $update_data['layout_config'] = is_array($data['layout_config']) ? wp_json_encode($data['layout_config']) : $data['layout_config'];
            $format[] = '%s';
        }
        
        if (empty($update_data)) {
            return new WP_Error('no_data', __('لا توجد بيانات للتحديث', 'saint-porphyrius'));
        }
        
        $result = $wpdb->update(
            $this->templates_table,
            $update_data,
            array('id' => $id),
            $format,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('فشل في تحديث قالب الباص', 'saint-porphyrius'));
        }
        
        return array('success' => true, 'message' => __('تم تحديث قالب الباص بنجاح', 'saint-porphyrius'));
    }
    
    /**
     * Delete bus template
     */
    public function delete_template($id) {
        global $wpdb;
        
        // Check if template is in use
        $in_use = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->event_buses_table} WHERE bus_template_id = %d",
            $id
        ));
        
        if ($in_use > 0) {
            return new WP_Error('in_use', __('لا يمكن حذف قالب مستخدم في فعاليات', 'saint-porphyrius'));
        }
        
        $result = $wpdb->delete($this->templates_table, array('id' => $id), array('%d'));
        
        if ($result === false) {
            return new WP_Error('db_error', __('فشل في حذف قالب الباص', 'saint-porphyrius'));
        }
        
        return array('success' => true, 'message' => __('تم حذف قالب الباص بنجاح', 'saint-porphyrius'));
    }
    
    // ==========================================
    // EVENT BUSES METHODS
    // ==========================================
    
    /**
     * Get buses for an event
     */
    public function get_event_buses($event_id, $include_bookings = false) {
        global $wpdb;
        
        $buses = $wpdb->get_results($wpdb->prepare(
            "SELECT eb.*, bt.name_ar as template_name_ar, bt.name_en as template_name_en,
                    bt.capacity, bt.rows, bt.seats_per_row, bt.aisle_position,
                    bt.layout_config, bt.icon, bt.color
             FROM {$this->event_buses_table} eb
             LEFT JOIN {$this->templates_table} bt ON eb.bus_template_id = bt.id
             WHERE eb.event_id = %d AND eb.is_active = 1
             ORDER BY eb.bus_number ASC",
            $event_id
        ));
        
        if ($include_bookings && !empty($buses)) {
            foreach ($buses as &$bus) {
                $bus->bookings = $this->get_bus_bookings($bus->id);
                $bus->booked_seats = array_column($bus->bookings, 'seat_label');
                
                // Subtract admin-blocked seats so available_seats is consistent
                // with is_event_fully_booked() — both use effective capacity.
                $layout = $this->parse_layout_config($bus->layout_config);
                $blocked_count = 0;
                if (isset($layout['blocked_seats'])) {
                    if (is_array($layout['blocked_seats'])) {
                        $blocked_count = count($layout['blocked_seats']);
                    } elseif (is_string($layout['blocked_seats']) && !empty($layout['blocked_seats'])) {
                        $blocked_count = count(array_filter(array_map('trim', explode(',', $layout['blocked_seats']))));
                    }
                }
                $bus->effective_capacity = $bus->capacity - $blocked_count;
                $bus->available_seats = max(0, $bus->effective_capacity - count($bus->bookings));
            }
        }
        
        return $buses;
    }
    
    /**
     * Get single event bus by ID
     */
    public function get_event_bus($id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT eb.*, bt.name_ar as template_name_ar, bt.name_en as template_name_en,
                    bt.capacity, bt.rows, bt.seats_per_row, bt.aisle_position,
                    bt.layout_config, bt.icon, bt.color
             FROM {$this->event_buses_table} eb
             LEFT JOIN {$this->templates_table} bt ON eb.bus_template_id = bt.id
             WHERE eb.id = %d",
            $id
        ));
    }
    
    /**
     * Add bus to event
     */
    public function add_event_bus($data) {
        global $wpdb;
        
        // Get next bus number for this event
        $bus_number = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(MAX(bus_number), 0) + 1 FROM {$this->event_buses_table} WHERE event_id = %d",
            $data['event_id']
        ));
        
        $result = $wpdb->insert(
            $this->event_buses_table,
            array(
                'event_id' => absint($data['event_id']),
                'bus_template_id' => absint($data['bus_template_id']),
                'bus_name' => sanitize_text_field($data['bus_name'] ?? ''),
                'bus_number' => $bus_number,
                'departure_time' => sanitize_text_field($data['departure_time'] ?? ''),
                'departure_location' => sanitize_text_field($data['departure_location'] ?? ''),
                'return_time' => sanitize_text_field($data['return_time'] ?? ''),
                'notes' => sanitize_textarea_field($data['notes'] ?? ''),
                'is_active' => 1,
            ),
            array('%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('فشل في إضافة الباص', 'saint-porphyrius'));
        }
        
        return array(
            'success' => true,
            'id' => $wpdb->insert_id,
            'bus_number' => $bus_number,
            'message' => __('تم إضافة الباص بنجاح', 'saint-porphyrius')
        );
    }
    
    /**
     * Update event bus
     */
    public function update_event_bus($id, $data) {
        global $wpdb;
        
        $update_data = array();
        $format = array();
        
        $fields = array(
            'bus_template_id' => '%d',
            'bus_name' => '%s',
            'departure_time' => '%s',
            'departure_location' => '%s',
            'return_time' => '%s',
            'notes' => '%s',
            'is_active' => '%d',
        );
        
        foreach ($fields as $field => $field_format) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
                $format[] = $field_format;
            }
        }
        
        if (empty($update_data)) {
            return new WP_Error('no_data', __('لا توجد بيانات للتحديث', 'saint-porphyrius'));
        }
        
        $result = $wpdb->update(
            $this->event_buses_table,
            $update_data,
            array('id' => $id),
            $format,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('فشل في تحديث الباص', 'saint-porphyrius'));
        }
        
        return array('success' => true, 'message' => __('تم تحديث الباص بنجاح', 'saint-porphyrius'));
    }
    
    /**
     * Remove bus from event
     */
    public function remove_event_bus($id) {
        global $wpdb;
        
        // Delete all bookings first
        $wpdb->delete($this->bookings_table, array('event_bus_id' => $id), array('%d'));
        
        $result = $wpdb->delete($this->event_buses_table, array('id' => $id), array('%d'));
        
        if ($result === false) {
            return new WP_Error('db_error', __('فشل في حذف الباص', 'saint-porphyrius'));
        }
        
        return array('success' => true, 'message' => __('تم حذف الباص بنجاح', 'saint-porphyrius'));
    }
    
    // ==========================================
    // SEAT BOOKING METHODS
    // ==========================================
    
    /**
     * Get bookings for a bus
     */
    public function get_bus_bookings($event_bus_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT sb.*, u.display_name, um.meta_value as name_ar,
                    um_fn.meta_value as first_name, um_mn.meta_value as middle_name
             FROM {$this->bookings_table} sb
             LEFT JOIN {$wpdb->users} u ON sb.user_id = u.ID
             LEFT JOIN {$wpdb->usermeta} um ON sb.user_id = um.user_id AND um.meta_key = 'sp_name_ar'
             LEFT JOIN {$wpdb->usermeta} um_fn ON sb.user_id = um_fn.user_id AND um_fn.meta_key = 'first_name'
             LEFT JOIN {$wpdb->usermeta} um_mn ON sb.user_id = um_mn.user_id AND um_mn.meta_key = 'sp_middle_name'
             WHERE sb.event_bus_id = %d AND sb.status != 'cancelled'
             ORDER BY sb.seat_row ASC, sb.seat_number ASC",
            $event_bus_id
        ));
    }
    
    /**
     * Get user's booking for an event
     */
    public function get_user_event_booking($event_id, $user_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT sb.*, eb.bus_number, eb.bus_name, eb.departure_time, eb.departure_location,
                    bt.name_ar as template_name, bt.icon, bt.color
             FROM {$this->bookings_table} sb
             JOIN {$this->event_buses_table} eb ON sb.event_bus_id = eb.id
             JOIN {$this->templates_table} bt ON eb.bus_template_id = bt.id
             WHERE eb.event_id = %d AND sb.user_id = %d AND sb.status != 'cancelled'",
            $event_id,
            $user_id
        ));
    }
    
    /**
     * Book a seat
     */
    public function book_seat($event_bus_id, $user_id, $seat_row, $seat_number) {
        global $wpdb;
        
        // Get bus info
        $bus = $this->get_event_bus($event_bus_id);
        if (!$bus) {
            return new WP_Error('not_found', __('الباص غير موجود', 'saint-porphyrius'));
        }
        
        // Generate seat label (e.g., "3B")
        $seat_label = $this->generate_seat_label($seat_row, $seat_number, $bus->aisle_position);
        
        // Check if seat is blocked by admin
        $layout = $this->parse_layout_config($bus->layout_config);
        $blocked_seats = array();
        if (isset($layout['blocked_seats'])) {
            if (is_array($layout['blocked_seats'])) {
                $blocked_seats = $layout['blocked_seats'];
            } elseif (is_string($layout['blocked_seats']) && !empty($layout['blocked_seats'])) {
                $blocked_seats = array_filter(array_map('trim', explode(',', $layout['blocked_seats'])));
            }
        }
        if (in_array($seat_label, $blocked_seats)) {
            return new WP_Error('seat_blocked', __('هذا المقعد غير متاح للحجز', 'saint-porphyrius'));
        }
        
        // Gender seating validation: males and females cannot sit in the same row pair
        $gender_check = $this->validate_gender_seating($event_bus_id, $user_id, $seat_row, $seat_number, $bus->aisle_position);
        if (is_wp_error($gender_check)) {
            return $gender_check;
        }
        
        // Check if seat is already booked (active booking)
        $existing_active = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->bookings_table} 
             WHERE event_bus_id = %d AND seat_row = %d AND seat_number = %d AND status != 'cancelled'",
            $event_bus_id, $seat_row, $seat_number
        ));
        
        if ($existing_active) {
            return new WP_Error('seat_taken', __('هذا المقعد محجوز بالفعل', 'saint-porphyrius'));
        }
        
        // Check if user already has a booking for this event
        $event_id = $bus->event_id ?? $wpdb->get_var($wpdb->prepare(
            "SELECT event_id FROM {$this->event_buses_table} WHERE id = %d",
            $event_bus_id
        ));
        
        $user_booking = $this->get_user_event_booking($event_id, $user_id);
        if ($user_booking) {
            return new WP_Error('already_booked', __('لديك حجز بالفعل في هذه الفعالية. الغِ حجزك الحالي أولاً.', 'saint-porphyrius'));
        }
        
        // If a waiting list exists for this event, freed seats are reserved for the queue.
        // Block direct booking for everyone except the user being processed by the waiting list itself.
        if ($this->has_active_waiting_list($event_id)) {
            $on_waiting = $this->get_user_waiting_entry($event_id, $user_id);
            if (!$on_waiting) {
                return new WP_Error('waiting_list_active', __('يوجد قائمة انتظار حالياً. لا يمكن الحجز المباشر — انضم لقائمة الانتظار وسيتم تعيين مقعدك تلقائياً عند توفره.', 'saint-porphyrius'));
            }
        }
        
        // Get booking fee from event
        $events_table = $wpdb->prefix . 'sp_events';
        $booking_fee = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT bus_booking_fee FROM $events_table WHERE id = %d",
            $event_id
        ));
        
        // Check if user has enough points
        if ($booking_fee > 0) {
            $points_handler = SP_Points::get_instance();
            $user_points = $points_handler->get_balance($user_id);
            
            if ($user_points < $booking_fee) {
                return new WP_Error('insufficient_points', sprintf(
                    __('رصيدك غير كافٍ. تحتاج %d نقطة لحجز المقعد. رصيدك الحالي: %d نقطة', 'saint-porphyrius'),
                    $booking_fee,
                    $user_points
                ));
            }
        }
        
        // Check if there's a cancelled booking for this seat (due to UNIQUE KEY constraint)
        $cancelled_booking_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->bookings_table} 
             WHERE event_bus_id = %d AND seat_row = %d AND seat_number = %d AND status = 'cancelled'",
            $event_bus_id, $seat_row, $seat_number
        ));
        
        if ($cancelled_booking_id) {
            // Reactivate cancelled booking with new user
            $result = $wpdb->update(
                $this->bookings_table,
                array(
                    'user_id' => $user_id,
                    'seat_label' => $seat_label,
                    'status' => 'booked',
                    'booked_at' => current_time('mysql'),
                    'cancelled_at' => null,
                    'checked_in_at' => null,
                ),
                array('id' => $cancelled_booking_id),
                array('%d', '%s', '%s', '%s', '%s', '%s'),
                array('%d')
            );
            
            if ($result === false) {
                return new WP_Error('db_error', __('فشل في حجز المقعد', 'saint-porphyrius'));
            }
            
            $booking_id = $cancelled_booking_id;
        } else {
            // Create new booking
            $result = $wpdb->insert(
                $this->bookings_table,
                array(
                    'event_bus_id' => $event_bus_id,
                    'user_id' => $user_id,
                    'seat_row' => $seat_row,
                    'seat_number' => $seat_number,
                    'seat_label' => $seat_label,
                    'status' => 'booked',
                ),
                array('%d', '%d', '%d', '%d', '%s', '%s')
            );
            
            if ($result === false) {
                return new WP_Error('db_error', __('فشل في حجز المقعد', 'saint-porphyrius'));
            }
            
            $booking_id = $wpdb->insert_id;
        }
        
        // Deduct booking fee from user points
        if ($booking_fee > 0) {
            $points_handler = SP_Points::get_instance();
            $points_handler->add(
                $user_id,
                -$booking_fee,
                'bus_booking_fee',
                $event_id,
                sprintf(__('رسوم حجز مقعد الباص للفعالية', 'saint-porphyrius'))
            );
        }
        
        // Send booking confirmation notification
        $notifications = SP_Notifications::get_instance();
        $event_url = home_url('/app/events/' . $event_id);
        
        $notifications->create_inbox_notification(array(
            'user_id' => $user_id,
            'title' => '🚌 ' . __('ابن/بنت برفوريوس! تم حجز مقعدك!', 'saint-porphyrius'),
            'message' => sprintf(
                __('تم تأكيد حجز المقعد %s في باص %d. لا تنسَ الوصول في الموعد! 🙏', 'saint-porphyrius'),
                $seat_label,
                $bus->bus_number
            ),
            'icon' => '🚌',
            'type' => 'system',
            'link_type' => 'event',
            'link_id' => $event_id,
            'url' => $event_url,
        ));
        
        $notifications->send_to_users(
            array($user_id),
            '🚌 ' . __('ابن/بنت برفوريوس! تم حجز مقعدك!', 'saint-porphyrius'),
            sprintf(__('المقعد %s - باص %d', 'saint-porphyrius'), $seat_label, $bus->bus_number),
            $event_url,
            'bus_booking'
        );
        
        // If user was on waiting list, mark as resolved
        global $wpdb;
        $wpdb->update(
            $this->waiting_list_table,
            array('status' => 'booked', 'resolved_at' => current_time('mysql')),
            array('event_id' => $event_id, 'user_id' => $user_id, 'status' => 'waiting'),
            array('%s', '%s'),
            array('%d', '%d', '%s')
        );
        
        return array(
            'success' => true,
            'booking_id' => $booking_id,
            'seat_label' => $seat_label,
            'bus_number' => $bus->bus_number,
            'fee_deducted' => $booking_fee,
            'message' => $booking_fee > 0 
                ? sprintf(__('ابن/بنت برفوريوس! تم حجز مقعدك! 🚌 المقعد %s (تم خصم %d نقطة — بترجع لما تحضر!). لا تنسَ الوصول في الموعد 🙏', 'saint-porphyrius'), $seat_label, $booking_fee)
                : sprintf(__('ابن/بنت برفوريوس! تم حجز مقعدك! 🚌 المقعد %s — لا تنسَ الوصول في الموعد 🙏', 'saint-porphyrius'), $seat_label)
        );
    }
    
    /**
     * Cancel a booking
     */
    public function cancel_booking($booking_id, $user_id = null) {
        global $wpdb;
        
        // Get booking info before cancelling (for waiting list processing)
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT sb.*, eb.event_id 
             FROM {$this->bookings_table} sb
             JOIN {$this->event_buses_table} eb ON sb.event_bus_id = eb.id
             WHERE sb.id = %d AND sb.status != 'cancelled'",
            $booking_id
        ));
        
        if (!$booking) {
            return new WP_Error('not_found', __('الحجز غير موجود', 'saint-porphyrius'));
        }
        
        // Block cancellation if passenger already checked in on the bus
        if ($booking->status === 'checked_in') {
            return new WP_Error('already_checked_in', __('لا يمكن إلغاء الحجز بعد تسجيل الركوب', 'saint-porphyrius'));
        }
        
        // Block cancellation if user already attended the event
        $booking_user_id = $user_id ?: $booking->user_id;
        $attendance_table = $wpdb->prefix . 'sp_attendance';
        $attended = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $attendance_table 
             WHERE event_id = %d AND user_id = %d AND status IN ('attended', 'late')",
            $booking->event_id, $booking_user_id
        ));
        if ($attended) {
            return new WP_Error('already_attended', __('لا يمكن إلغاء الحجز بعد تسجيل الحضور', 'saint-porphyrius'));
        }
        
        $where = array('id' => $booking_id);
        $where_format = array('%d');
        
        // If user_id provided, ensure they own the booking (unless admin)
        if ($user_id && !current_user_can('manage_options')) {
            $where['user_id'] = $user_id;
            $where_format[] = '%d';
        }
        
        $result = $wpdb->update(
            $this->bookings_table,
            array('status' => 'cancelled', 'cancelled_at' => current_time('mysql')),
            $where,
            array('%s', '%s'),
            $where_format
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('فشل في إلغاء الحجز', 'saint-porphyrius'));
        }
        
        if ($result === 0) {
            return new WP_Error('not_found', __('الحجز غير موجود', 'saint-porphyrius'));
        }
        
        // Send cancellation notification
        if ($booking) {
            $notifications = SP_Notifications::get_instance();
            $event_url = home_url('/app/events/' . $booking->event_id);
            $cancel_user_id = $user_id ?: $booking->user_id;
            
            $notifications->create_inbox_notification(array(
                'user_id' => $cancel_user_id,
                'title' => '🚌 ' . __('تم إلغاء حجز الباص', 'saint-porphyrius'),
                'message' => __('تم إلغاء حجز مقعدك في الباص بنجاح.', 'saint-porphyrius'),
                'icon' => '🚌',
                'type' => 'system',
                'link_type' => 'event',
                'link_id' => $booking->event_id,
                'url' => $event_url,
            ));
        }
        
        // Process waiting list - auto-book seat for next eligible person
        if ($booking && !empty($booking->event_id)) {
            $this->process_waiting_list($booking->event_id, $booking->event_bus_id, $booking->seat_row, $booking->seat_number);
        }
        
        return array('success' => true, 'message' => __('تم إلغاء الحجز بنجاح', 'saint-porphyrius'));
    }
    
    /**
     * Check in passenger
     */
    public function checkin_booking($booking_id) {
        global $wpdb;
        
        $result = $wpdb->update(
            $this->bookings_table,
            array('status' => 'checked_in', 'checked_in_at' => current_time('mysql')),
            array('id' => $booking_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('فشل في تسجيل الركوب', 'saint-porphyrius'));
        }
        
        return array('success' => true, 'message' => __('تم تسجيل الركوب بنجاح', 'saint-porphyrius'));
    }
    
    /**
     * Move a booking to a different seat (Admin only)
     * If target seat is occupied, performs a swap
     */
    public function move_seat($booking_id, $new_row, $new_seat_number) {
        global $wpdb;
        
        // Admins only
        if (!current_user_can('sp_manage_members') && !current_user_can('manage_options')) {
            return new WP_Error('unauthorized', __('غير مصرح لك بنقل الحجوزات', 'saint-porphyrius'));
        }
        
        // Get current booking with simpler query first
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->bookings_table} WHERE id = %d AND status != 'cancelled'",
            $booking_id
        ));
        
        if (!$booking) {
            return new WP_Error('not_found', __('الحجز غير موجود - الرقم: ', 'saint-porphyrius') . $booking_id);
        }
        
        // Get bus info
        $bus = $this->get_event_bus($booking->event_bus_id);
        if (!$bus) {
            return new WP_Error('bus_not_found', __('الباص غير موجود', 'saint-porphyrius'));
        }
        
        // Generate new seat label
        $new_seat_label = $this->generate_seat_label($new_row, $new_seat_number, $bus->aisle_position);
        
        // Check if new seat is blocked
        $layout = $this->parse_layout_config($bus->layout_config);
        $blocked_seats = array();
        if (isset($layout['blocked_seats'])) {
            if (is_array($layout['blocked_seats'])) {
                $blocked_seats = $layout['blocked_seats'];
            } elseif (is_string($layout['blocked_seats']) && !empty($layout['blocked_seats'])) {
                $blocked_seats = array_filter(array_map('trim', explode(',', $layout['blocked_seats'])));
            }
        }
        if (in_array($new_seat_label, $blocked_seats)) {
            return new WP_Error('seat_blocked', __('هذا المقعد محظور', 'saint-porphyrius'));
        }
        
        // Check if new seat is already taken (for swap)
        $existing_booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->bookings_table} 
             WHERE event_bus_id = %d AND seat_row = %d AND seat_number = %d AND status != 'cancelled' AND id != %d",
            $booking->event_bus_id, $new_row, $new_seat_number, $booking_id
        ));
        
        $old_seat_label = $booking->seat_label;
        $old_row = $booking->seat_row;
        $old_seat_number = $booking->seat_number;
        
        // Delete any cancelled bookings at target seat (to avoid UNIQUE KEY conflict)
        $wpdb->delete(
            $this->bookings_table,
            array(
                'event_bus_id' => $booking->event_bus_id,
                'seat_row' => $new_row,
                'seat_number' => $new_seat_number,
                'status' => 'cancelled'
            ),
            array('%d', '%d', '%d', '%s')
        );
        
        if ($existing_booking) {
            // SWAP: We need to use a temporary seat for one booking to avoid Unique Key violation
            // unique_seat (event_bus_id, seat_row, seat_number)
            
            // Also delete any cancelled bookings at source seat (for swap back)
            $wpdb->delete(
                $this->bookings_table,
                array(
                    'event_bus_id' => $booking->event_bus_id,
                    'seat_row' => $old_row,
                    'seat_number' => $old_seat_number,
                    'status' => 'cancelled'
                ),
                array('%d', '%d', '%d', '%s')
            );
            
            // 1. Move existing booking (Target) to temporary location
            // Use negative values which valid seats won't use
            $temp_row = -1 * $existing_booking->id;
            $temp_seat = -1 * $existing_booking->id;
            
            $temp_result = $wpdb->update(
                $this->bookings_table,
                array(
                    'seat_row' => $temp_row,
                    'seat_number' => $temp_seat
                ),
                array('id' => $existing_booking->id),
                array('%d', '%d'),
                array('%d')
            );
            
            if ($temp_result === false) {
                 return new WP_Error('db_error', __('فشل في بدء عملية التبديل', 'saint-porphyrius'));
            }
            
            // 2. Move original booking (Source) to new seat (Target location)
            $result = $wpdb->update(
                $this->bookings_table,
                array(
                    'seat_row' => $new_row,
                    'seat_number' => $new_seat_number,
                    'seat_label' => $new_seat_label
                ),
                array('id' => $booking_id),
                array('%d', '%d', '%s'),
                array('%d')
            );
            
            if ($result === false) {
                // Rollback: Move Target back to original location
                $wpdb->update(
                    $this->bookings_table,
                    array(
                        'seat_row' => $new_row,
                        'seat_number' => $new_seat_number
                    ),
                    array('id' => $existing_booking->id),
                    array('%d', '%d'),
                    array('%d')
                );
                return new WP_Error('db_error', __('فشل في نقل الحجز الأول', 'saint-porphyrius'));
            }
            
            // 3. Move existing booking (Target) from Temp to old seat (Source location)
            $swap_result = $wpdb->update(
                $this->bookings_table,
                array(
                    'seat_row' => $old_row,
                    'seat_number' => $old_seat_number,
                    'seat_label' => $old_seat_label
                ),
                array('id' => $existing_booking->id),
                array('%d', '%d', '%s'),
                array('%d')
            );
            
            if ($swap_result === false) {
                 // Detailed error logging would be good here, state is inconsistent
                 return new WP_Error('db_error', __('فشل في نقل الحجز الثاني (حالة غير متناسقة)', 'saint-porphyrius'));
            }
            
            // Success
        } else {
            // Update the original booking to new seat (Normal Move)
            $result = $wpdb->update(
                $this->bookings_table,
                array(
                    'seat_row' => $new_row,
                    'seat_number' => $new_seat_number,
                    'seat_label' => $new_seat_label
                ),
                array('id' => $booking_id),
                array('%d', '%d', '%s'),
                array('%d')
            );
            
            if ($result === false) {
                return new WP_Error('db_error', __('فشل في نقل الحجز', 'saint-porphyrius'));
            }
        }
        
        if ($existing_booking) {
            return array(
                'success' => true,
                'is_swap' => true,
                'old_seat_label' => $old_seat_label,
                'new_seat_label' => $new_seat_label,
                'message' => sprintf(__('تم تبديل المقاعد: %s ↔ %s', 'saint-porphyrius'), $old_seat_label, $new_seat_label)
            );
        }
        
        return array(
            'success' => true,
            'is_swap' => false,
            'old_seat_label' => $old_seat_label,
            'new_seat_label' => $new_seat_label,
            'message' => sprintf(__('تم نقل الحجز من %s إلى %s بنجاح', 'saint-porphyrius'), $old_seat_label, $new_seat_label)
        );
    }
    
    // ==========================================
    // HELPER METHODS
    // ==========================================
    
    /**
     * Generate seat label from row and seat number
     */
    public function generate_seat_label($row, $seat_number, $aisle_position = 2) {
        // Letters: A, B for window/aisle on left, C, D for aisle/window on right
        $letters = array('A', 'B', 'C', 'D', 'E');
        
        // Adjust for aisle
        if ($seat_number > $aisle_position) {
            // Right side of aisle
            $letter_index = $seat_number - 1;
        } else {
            // Left side of aisle
            $letter_index = $seat_number - 1;
        }
        
        $letter = isset($letters[$letter_index]) ? $letters[$letter_index] : chr(64 + $seat_number);
        
        return $row . $letter;
    }
    
    /**
     * Parse layout config
     */
    public function parse_layout_config($config) {
        if (empty($config)) {
            return array();
        }
        if (is_string($config)) {
            $decoded = json_decode($config, true);
            return is_array($decoded) ? $decoded : array();
        }
        return is_array($config) ? $config : array();
    }

    /**
     * Get seat map for a bus
     */
    public function get_seat_map($event_bus_id) {
        $bus = $this->get_event_bus($event_bus_id);
        if (!$bus) {
            return null;
        }
        
        $bookings = $this->get_bus_bookings($event_bus_id);
        $booked_seats = array();
        foreach ($bookings as $booking) {
            $key = $booking->seat_row . '_' . $booking->seat_number;
            $first_middle = trim(($booking->first_name ?? '') . ' ' . ($booking->middle_name ?? ''));
            $gender = get_user_meta($booking->user_id, 'sp_gender', true) ?: 'male';
            $booked_seats[$key] = array(
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'user_name' => $booking->name_ar ?: $booking->display_name,
                'user_name_short' => $first_middle ?: ($booking->name_ar ?: $booking->display_name),
                'seat_label' => $booking->seat_label,
                'status' => $booking->status,
                'gender' => $gender,
            );
        }
        
        $layout = $this->parse_layout_config($bus->layout_config);
        $disabled_seats = isset($layout['disabled_seats']) && is_array($layout['disabled_seats']) ? $layout['disabled_seats'] : array('1A');
        
        // Ensure blocked_seats is always an array
        $blocked_seats = array();
        if (isset($layout['blocked_seats'])) {
            if (is_array($layout['blocked_seats'])) {
                $blocked_seats = $layout['blocked_seats'];
            } elseif (is_string($layout['blocked_seats']) && !empty($layout['blocked_seats'])) {
                // Handle case where blocked_seats might be stored as comma-separated string
                $blocked_seats = array_filter(array_map('trim', explode(',', $layout['blocked_seats'])));
            }
        }
        
        $driver_seats = isset($layout['driver_seats']) ? intval($layout['driver_seats']) : 1;
        $back_row_extra = isset($layout['back_row_extra']) ? intval($layout['back_row_extra']) : 1;
        
        // Calculate total rows (driver row + regular rows + back row)
        $total_rows = $bus->rows + 2; // +1 for driver, +1 for back
        $back_row_seats = $bus->seats_per_row + $back_row_extra;
        
        $seat_map = array(
            'bus_id' => $bus->id,
            'bus_number' => $bus->bus_number,
            'bus_name' => $bus->bus_name,
            'template_name' => $bus->template_name_ar,
            'capacity' => $bus->capacity,
            'rows' => $bus->rows,
            'total_rows' => $total_rows,
            'seats_per_row' => $bus->seats_per_row,
            'aisle_position' => $bus->aisle_position,
            'driver_seats' => $driver_seats,
            'back_row_extra' => $back_row_extra,
            'back_row_seats' => $back_row_seats,
            'icon' => $bus->icon,
            'color' => $bus->color,
            'layout' => $layout,
            'booked_seats' => $booked_seats,
            'disabled_seats' => $disabled_seats,
            'blocked_seats' => $blocked_seats,
            'departure_time' => $bus->departure_time,
            'departure_location' => $bus->departure_location,
            'return_time' => $bus->return_time,
        );
        
        return $seat_map;
    }
    
    /**
     * Get event bus statistics
     */
    public function get_event_bus_stats($event_id) {
        global $wpdb;
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(DISTINCT eb.id) as total_buses,
                SUM(bt.capacity) as total_capacity,
                COUNT(sb.id) as total_booked,
                SUM(CASE WHEN sb.status = 'checked_in' THEN 1 ELSE 0 END) as total_checked_in
             FROM {$this->event_buses_table} eb
             LEFT JOIN {$this->templates_table} bt ON eb.bus_template_id = bt.id
             LEFT JOIN {$this->bookings_table} sb ON eb.id = sb.event_bus_id AND sb.status != 'cancelled'
             WHERE eb.event_id = %d AND eb.is_active = 1",
            $event_id
        ));
        
        if ($stats) {
            $stats->available = ($stats->total_capacity ?? 0) - ($stats->total_booked ?? 0);
        }
        
        return $stats;
    }
    
    /**
     * Check if event has bus booking enabled
     */
    public function is_bus_booking_enabled($event_id) {
        global $wpdb;
        $events_table = $wpdb->prefix . 'sp_events';
        
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT bus_booking_enabled FROM $events_table WHERE id = %d",
            $event_id
        ));
    }
    
    // ==========================================
    // GENDER SEATING VALIDATION
    // ==========================================
    
    /**
     * Validate that male and female passengers don't sit in the same row pair.
     * In a 2|2 layout, seats 1-2 are a pair (left side) and seats 3-4 are a pair (right side).
     * Each pair must be same gender.
     */
    public function validate_gender_seating($event_bus_id, $user_id, $seat_row, $seat_number, $aisle_position) {
        global $wpdb;
        
        // Determine which pair this seat belongs to (left or right of aisle)
        if ($seat_number <= $aisle_position) {
            // Left side pair: seats 1 to aisle_position
            $pair_start = 1;
            $pair_end = $aisle_position;
        } else {
            // Right side pair: seats aisle_position+1 to end
            $pair_start = $aisle_position + 1;
            $pair_end = 99; // High number, query will filter by actual seats
        }
        
        // Get the current user's gender
        $user_gender = get_user_meta($user_id, 'sp_gender', true) ?: 'male';
        
        // Check if any other seat in the same pair (same row, same side) is booked by someone of different gender
        $pair_bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT sb.user_id, sb.seat_number
             FROM {$this->bookings_table} sb
             WHERE sb.event_bus_id = %d 
               AND sb.seat_row = %d 
               AND sb.seat_number >= %d 
               AND sb.seat_number <= %d
               AND sb.seat_number != %d
               AND sb.status != 'cancelled'",
            $event_bus_id, $seat_row, $pair_start, $pair_end, $seat_number
        ));
        
        foreach ($pair_bookings as $booking) {
            $neighbor_gender = get_user_meta($booking->user_id, 'sp_gender', true) ?: 'male';
            if ($neighbor_gender !== $user_gender) {
                return new WP_Error(
                    'gender_seating_conflict',
                    __('ابن/بنت برفوريوس! عذراً! 🙏 الرجاء اختيار مقعد بجانب شخص من نفس الجنس — شكراً لتفهمك!', 'saint-porphyrius')
                );
            }
        }
        
        return true;
    }
    
    /**
     * Get the gender of a seat's neighbor(s) in the same pair for UI hints
     */
    public function get_seat_pair_gender($event_bus_id, $seat_row, $seat_number, $aisle_position) {
        global $wpdb;
        
        if ($seat_number <= $aisle_position) {
            $pair_start = 1;
            $pair_end = $aisle_position;
        } else {
            $pair_start = $aisle_position + 1;
            $pair_end = 99;
        }
        
        $pair_bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT sb.user_id
             FROM {$this->bookings_table} sb
             WHERE sb.event_bus_id = %d 
               AND sb.seat_row = %d 
               AND sb.seat_number >= %d 
               AND sb.seat_number <= %d
               AND sb.seat_number != %d
               AND sb.status != 'cancelled'",
            $event_bus_id, $seat_row, $pair_start, $pair_end, $seat_number
        ));
        
        if (empty($pair_bookings)) {
            return null; // No neighbor, any gender can sit
        }
        
        // Return the gender of the first neighbor
        return get_user_meta($pair_bookings[0]->user_id, 'sp_gender', true) ?: 'male';
    }
    
    // ==========================================
    // WAITING LIST METHODS
    // ==========================================
    
    /**
     * Check if all buses for an event are fully booked (no available seats)
     */
    public function is_event_fully_booked($event_id) {
        $buses = $this->get_event_buses($event_id, true);
        if (empty($buses)) {
            return true;
        }
        
        foreach ($buses as $bus) {
            // Check actual available seats (capacity minus booked minus blocked)
            $layout = $this->parse_layout_config($bus->layout_config);
            $blocked_count = 0;
            if (isset($layout['blocked_seats'])) {
                if (is_array($layout['blocked_seats'])) {
                    $blocked_count = count($layout['blocked_seats']);
                } elseif (is_string($layout['blocked_seats']) && !empty($layout['blocked_seats'])) {
                    $blocked_count = count(array_filter(array_map('trim', explode(',', $layout['blocked_seats']))));
                }
            }
            $effective_capacity = $bus->capacity - $blocked_count;
            $booked_count = count($bus->bookings);
            
            if ($booked_count < $effective_capacity) {
                return false; // At least one seat available
            }
        }
        
        return true;
    }
    
    /**
     * Add user to waiting list for an event
     */
    public function join_waiting_list($event_id, $user_id) {
        global $wpdb;
        
        // Check if user already has a booking
        $existing_booking = $this->get_user_event_booking($event_id, $user_id);
        if ($existing_booking) {
            return new WP_Error('already_booked', __('لديك حجز بالفعل في هذه الفعالية', 'saint-porphyrius'));
        }
        
        // Check if user is already actively on the waiting list
        $existing_waiting = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->waiting_list_table} WHERE event_id = %d AND user_id = %d AND status = 'waiting'",
            $event_id, $user_id
        ));
        
        if ($existing_waiting) {
            return new WP_Error('already_waiting', __('أنت مسجل بالفعل في قائمة الانتظار', 'saint-porphyrius'));
        }
        
        // Defensive cleanup: remove any non-active prior rows for this user/event
        // (UNIQUE KEY (event_id, user_id) would otherwise block a fresh re-join after a previous cancel/skip).
        $wpdb->delete(
            $this->waiting_list_table,
            array('event_id' => absint($event_id), 'user_id' => absint($user_id)),
            array('%d', '%d')
        );
        
        // Recompute next position after cleanup
        $position = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(MAX(position), 0) + 1 FROM {$this->waiting_list_table} WHERE event_id = %d AND status = 'waiting'",
            $event_id
        ));
        
        $result = $wpdb->insert(
            $this->waiting_list_table,
            array(
                'event_id' => absint($event_id),
                'user_id' => absint($user_id),
                'position' => $position,
                'status' => 'waiting',
            ),
            array('%d', '%d', '%d', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('فشل في التسجيل في قائمة الانتظار', 'saint-porphyrius'));
        }
        
        // Try to immediately auto-book in case a seat has been freed since the page loaded
        // (e.g., another booking was cancelled but cron hadn't run yet for an empty queue).
        $this->process_waiting_list($event_id);
        
        return array(
            'success' => true,
            'position' => $position,
            'waiting_id' => $wpdb->insert_id,
            'message' => sprintf(__('ابن/بنت برفوريوس! تم تسجيلك في قائمة الانتظار (#%d)! 🔔 هنبلغك فوراً لو مقعد فضي — متقلقش! 🙏', 'saint-porphyrius'), $position)
        );
    }
    
    /**
     * Leave the waiting list
     *
     * Deletes the row outright (rather than soft-cancel) so the user can re-join later
     * — the table has a UNIQUE KEY (event_id, user_id) which would otherwise block a re-insert.
     * We also re-sequence the remaining positions to keep the queue contiguous.
     */
    public function leave_waiting_list($event_id, $user_id) {
        global $wpdb;
        
        // Only remove an active "waiting" entry; skipped rows can be cleared too as cleanup.
        $deleted = $wpdb->delete(
            $this->waiting_list_table,
            array('event_id' => absint($event_id), 'user_id' => absint($user_id), 'status' => 'waiting'),
            array('%d', '%d', '%s')
        );
        
        if ($deleted === false || $deleted === 0) {
            return new WP_Error('not_found', __('لست مسجلاً في قائمة الانتظار', 'saint-porphyrius'));
        }
        
        // Resequence remaining waiting entries (1..N) so positions stay tidy.
        $this->resequence_waiting_list($event_id);
        
        return array('success' => true, 'message' => __('تم إلغاء تسجيلك من قائمة الانتظار', 'saint-porphyrius'));
    }
    
    /**
     * Re-number waiting list positions to be contiguous (1..N) ordered by current position then created_at.
     */
    public function resequence_waiting_list($event_id) {
        global $wpdb;
        
        $entries = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$this->waiting_list_table}
             WHERE event_id = %d AND status = 'waiting'
             ORDER BY position ASC, created_at ASC, id ASC",
            $event_id
        ));
        
        $i = 1;
        foreach ($entries as $row) {
            $wpdb->update(
                $this->waiting_list_table,
                array('position' => $i++),
                array('id' => $row->id),
                array('%d'),
                array('%d')
            );
        }
    }
    
    /**
     * Whether there is at least one user actively waiting for a seat for this event.
     * Used to lock direct seat selection while a queue exists.
     */
    public function has_active_waiting_list($event_id) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->waiting_list_table} WHERE event_id = %d AND status = 'waiting'",
            $event_id
        )) > 0;
    }
    
    /**
     * Admin: move a waiting-list entry to a specific position (1-based).
     * Other entries are shifted to keep order contiguous.
     */
    public function admin_move_waiting_entry($entry_id, $new_position) {
        global $wpdb;
        
        if (!current_user_can('sp_manage_members') && !current_user_can('manage_options')) {
            return new WP_Error('unauthorized', __('غير مصرح', 'saint-porphyrius'));
        }
        
        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->waiting_list_table} WHERE id = %d AND status = 'waiting'",
            $entry_id
        ));
        if (!$entry) {
            return new WP_Error('not_found', __('السجل غير موجود', 'saint-porphyrius'));
        }
        
        $entries = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$this->waiting_list_table}
             WHERE event_id = %d AND status = 'waiting'
             ORDER BY position ASC, created_at ASC, id ASC",
            $entry->event_id
        ));
        
        // Build ordered list of IDs without the moved entry
        $ids = array();
        foreach ($entries as $r) {
            if ((int) $r->id !== (int) $entry_id) {
                $ids[] = (int) $r->id;
            }
        }
        
        // Clamp target position
        $target = max(1, min((int) $new_position, count($ids) + 1));
        array_splice($ids, $target - 1, 0, array((int) $entry_id));
        
        // Write back contiguous positions
        $i = 1;
        foreach ($ids as $id) {
            $wpdb->update(
                $this->waiting_list_table,
                array('position' => $i++),
                array('id' => $id),
                array('%d'),
                array('%d')
            );
        }
        
        return array('success' => true, 'message' => __('تم تحديث الترتيب', 'saint-porphyrius'));
    }
    
    /**
     * Admin: remove a waiting-list entry entirely and resequence.
     */
    public function admin_remove_waiting_entry($entry_id) {
        global $wpdb;
        
        if (!current_user_can('sp_manage_members') && !current_user_can('manage_options')) {
            return new WP_Error('unauthorized', __('غير مصرح', 'saint-porphyrius'));
        }
        
        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->waiting_list_table} WHERE id = %d",
            $entry_id
        ));
        if (!$entry) {
            return new WP_Error('not_found', __('السجل غير موجود', 'saint-porphyrius'));
        }
        
        $wpdb->delete(
            $this->waiting_list_table,
            array('id' => $entry_id),
            array('%d')
        );
        
        $this->resequence_waiting_list($entry->event_id);
        
        return array('success' => true, 'message' => __('تم حذف السجل من قائمة الانتظار', 'saint-porphyrius'));
    }
    
    /**
     * Cron: process every event that has people waiting and at least one free seat.
     * Safe no-op when there is nothing to do.
     */
    public function cron_process_waiting_lists() {
        global $wpdb;
        
        $events_table = $wpdb->prefix . 'sp_events';
        // Only consider events whose date is today or in the future to avoid endless work on archived events.
        $event_ids = $wpdb->get_col(
            "SELECT DISTINCT wl.event_id
             FROM {$this->waiting_list_table} wl
             INNER JOIN {$events_table} e ON e.id = wl.event_id
             WHERE wl.status = 'waiting'
               AND e.event_date >= CURDATE()"
        );
        
        if (empty($event_ids)) {
            return 0;
        }
        
        $processed = 0;
        foreach ($event_ids as $event_id) {
            // Loop because a single call only books one person per invocation.
            for ($i = 0; $i < 50; $i++) {
                if (!$this->has_active_waiting_list($event_id)) {
                    break;
                }
                $available = $this->get_available_seats_for_event($event_id);
                if (empty($available)) {
                    break;
                }
                $before_count = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->waiting_list_table} WHERE event_id = %d AND status = 'waiting'",
                    $event_id
                ));
                $this->process_waiting_list($event_id);
                $after_count = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->waiting_list_table} WHERE event_id = %d AND status = 'waiting'",
                    $event_id
                ));
                if ($after_count >= $before_count) {
                    // No progress (everyone skipped due to gender/points). Stop to avoid loops.
                    break;
                }
                $processed++;
            }
            // Tidy positions after any movement
            $this->resequence_waiting_list($event_id);
        }
        return $processed;
    }
    
    /**
     * Get waiting list for an event
     */
    public function get_waiting_list($event_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT wl.*, u.display_name, 
                    um.meta_value as name_ar,
                    um_fn.meta_value as first_name
             FROM {$this->waiting_list_table} wl
             LEFT JOIN {$wpdb->users} u ON wl.user_id = u.ID
             LEFT JOIN {$wpdb->usermeta} um ON wl.user_id = um.user_id AND um.meta_key = 'sp_name_ar'
             LEFT JOIN {$wpdb->usermeta} um_fn ON wl.user_id = um_fn.user_id AND um_fn.meta_key = 'first_name'
             WHERE wl.event_id = %d AND wl.status = 'waiting'
             ORDER BY wl.position ASC",
            $event_id
        ));
    }
    
    /**
     * Get user's waiting list entry for an event
     */
    public function get_user_waiting_entry($event_id, $user_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->waiting_list_table} WHERE event_id = %d AND user_id = %d AND status = 'waiting'",
            $event_id, $user_id
        ));
    }
    
    /**
     * Process waiting list when a seat becomes available (after cancellation)
     * Tries to auto-assign seat to next eligible person in queue.
     */
    public function process_waiting_list($event_id, $freed_bus_id = null, $freed_row = null, $freed_seat = null) {
        global $wpdb;
        
        // Get waiting list in order
        $waiting = $this->get_waiting_list($event_id);
        if (empty($waiting)) {
            return false;
        }
        
        // Get booking fee
        $events_table = $wpdb->prefix . 'sp_events';
        $booking_fee = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT bus_booking_fee FROM $events_table WHERE id = %d",
            $event_id
        ));
        
        // Build list of available seats across all buses for this event
        $available_seats = $this->get_available_seats_for_event($event_id);
        if (empty($available_seats)) {
            return false;
        }
        
        // Put the freed seat first in the list (preferred seat)
        if ($freed_bus_id && $freed_row !== null && $freed_seat !== null) {
            usort($available_seats, function($a, $b) use ($freed_bus_id, $freed_row, $freed_seat) {
                $a_is_freed = ($a['event_bus_id'] == $freed_bus_id && $a['seat_row'] == $freed_row && $a['seat_number'] == $freed_seat) ? 0 : 1;
                $b_is_freed = ($b['event_bus_id'] == $freed_bus_id && $b['seat_row'] == $freed_row && $b['seat_number'] == $freed_seat) ? 0 : 1;
                return $a_is_freed - $b_is_freed;
            });
        }
        
        $notifications = SP_Notifications::get_instance();
        $event_url = home_url('/app/events/' . $event_id);
        
        foreach ($waiting as $entry) {
            // Check if user has enough points
            if ($booking_fee > 0) {
                $points_handler = SP_Points::get_instance();
                $user_points = $points_handler->get_balance($entry->user_id);
                
                if ($user_points < $booking_fee) {
                    // Skip this user - not enough points, notify them
                    $wpdb->update(
                        $this->waiting_list_table,
                        array('status' => 'skipped_no_points', 'resolved_at' => current_time('mysql')),
                        array('id' => $entry->id),
                        array('%s', '%s'),
                        array('%d')
                    );
                    
                    $notifications->create_inbox_notification(array(
                        'user_id' => $entry->user_id,
                        'title' => '⚠️ ' . __('رصيدك غير كافٍ لحجز مقعد الباص', 'saint-porphyrius'),
                        'message' => sprintf(
                            __('توفر مقعد في الباص لكن رصيدك (%d نقطة) غير كافٍ لرسوم الحجز (%d نقطة). تم تخطيك في قائمة الانتظار.', 'saint-porphyrius'),
                            $user_points,
                            $booking_fee
                        ),
                        'icon' => '🚌',
                        'type' => 'system',
                        'link_type' => 'event',
                        'link_id' => $event_id,
                        'url' => $event_url,
                    ));
                    
                    $notifications->send_to_users(
                        array($entry->user_id),
                        '⚠️ ' . __('رصيدك غير كافٍ', 'saint-porphyrius'),
                        sprintf(__('توفر مقعد لكن رصيدك غير كافٍ (%d/%d نقطة)', 'saint-porphyrius'), $user_points, $booking_fee),
                        $event_url,
                        'bus_waiting_list'
                    );
                    
                    continue; // Try next person
                }
            }
            
            // Eligible user found! Try to find a gender-compatible seat
            $user_gender = get_user_meta($entry->user_id, 'sp_gender', true) ?: 'male';
            $assigned_seat = null;
            
            foreach ($available_seats as $seat) {
                // Check gender compatibility with seat pair neighbor
                $gender_ok = $this->validate_gender_seating(
                    $seat['event_bus_id'], $entry->user_id, 
                    $seat['seat_row'], $seat['seat_number'], 
                    $seat['aisle_position']
                );
                
                if (!is_wp_error($gender_ok)) {
                    $assigned_seat = $seat;
                    break;
                }
            }
            
            if (!$assigned_seat) {
                // No gender-compatible seat available for this user, skip
                $wpdb->update(
                    $this->waiting_list_table,
                    array('status' => 'skipped_gender', 'resolved_at' => current_time('mysql')),
                    array('id' => $entry->id),
                    array('%s', '%s'),
                    array('%d')
                );
                
                $notifications->create_inbox_notification(array(
                    'user_id' => $entry->user_id,
                    'title' => '⚠️ ' . __('لا يوجد مقعد مناسب حالياً', 'saint-porphyrius'),
                    'message' => __('توفر مقعد في الباص لكن لا يتوافق مع قواعد جلوس الشباب والبنات. سنحاول مجدداً عند توفر مقعد آخر.', 'saint-porphyrius'),
                    'icon' => '🚌',
                    'type' => 'system',
                    'link_type' => 'event',
                    'link_id' => $event_id,
                    'url' => $event_url,
                ));
                
                continue; // Try next person
            }
            
            // Auto-book the seat!
            $seat_label = $this->generate_seat_label($assigned_seat['seat_row'], $assigned_seat['seat_number'], $assigned_seat['aisle_position']);
            
            $insert_result = $wpdb->insert(
                $this->bookings_table,
                array(
                    'event_bus_id' => $assigned_seat['event_bus_id'],
                    'user_id' => $entry->user_id,
                    'seat_row' => $assigned_seat['seat_row'],
                    'seat_number' => $assigned_seat['seat_number'],
                    'seat_label' => $seat_label,
                    'status' => 'booked',
                ),
                array('%d', '%d', '%d', '%d', '%s', '%s')
            );
            
            if ($insert_result === false) {
                continue; // DB error, try next person
            }
            
            // Deduct points if applicable
            if ($booking_fee > 0) {
                $points_handler = SP_Points::get_instance();
                $points_handler->add(
                    $entry->user_id,
                    -$booking_fee,
                    'bus_booking',
                    sprintf(__('رسوم حجز مقعد الباص تلقائياً من قائمة الانتظار', 'saint-porphyrius'))
                );
            }
            
            // Update waiting list status
            $wpdb->update(
                $this->waiting_list_table,
                array('status' => 'booked', 'resolved_at' => current_time('mysql')),
                array('id' => $entry->id),
                array('%s', '%s'),
                array('%d')
            );
            
            // Get bus info for notification
            $bus = $this->get_event_bus($assigned_seat['event_bus_id']);
            $bus_number = $bus ? $bus->bus_number : '';
            $user_name = $entry->first_name ?: $entry->name_ar ?: $entry->display_name;
            
            // Send booking confirmation notification
            $notifications->create_inbox_notification(array(
                'user_id' => $entry->user_id,
                'title' => '🎉 ' . __('ابن/بنت برفوريوس! تم حجز مقعدك تلقائياً!', 'saint-porphyrius'),
                'message' => sprintf(
                    __('ابن/بنت برفوريوس %s! 🎉 تم حجز المقعد %s في باص %d تلقائياً — لا تنسَ الوصول في الموعد! بركة شفيعنا تكون معاك 🙏', 'saint-porphyrius'),
                    $user_name,
                    $seat_label,
                    $bus_number
                ),
                'icon' => '🚌',
                'type' => 'system',
                'link_type' => 'event',
                'link_id' => $event_id,
                'url' => $event_url,
            ));
            
            $notifications->send_to_users(
                array($entry->user_id),
                '🎉 ' . __('ابن/بنت برفوريوس! تم حجز مقعدك تلقائياً!', 'saint-porphyrius'),
                sprintf(__('المقعد %s في باص %d - من قائمة الانتظار', 'saint-porphyrius'), $seat_label, $bus_number),
                $event_url,
                'bus_waiting_list'
            );
            
            // Remove the assigned seat from available list so next person doesn't get it
            $available_seats = array_filter($available_seats, function($s) use ($assigned_seat) {
                return !($s['event_bus_id'] == $assigned_seat['event_bus_id'] 
                    && $s['seat_row'] == $assigned_seat['seat_row'] 
                    && $s['seat_number'] == $assigned_seat['seat_number']);
            });
            
            // Only book one person per cancellation
            break;
        }
        
        return true;
    }
    
    /**
     * Get all available (unbooked, unblocked) seats across all buses for an event
     */
    private function get_available_seats_for_event($event_id) {
        global $wpdb;
        
        $buses = $this->get_event_buses($event_id, true);
        if (empty($buses)) {
            return array();
        }
        
        $available = array();
        
        foreach ($buses as $bus) {
            $seat_map = $this->get_seat_map($bus->id);
            if (!$seat_map) continue;
            
            $layout = $this->parse_layout_config($bus->layout_config);
            $blocked_seats = array();
            if (isset($layout['blocked_seats'])) {
                if (is_array($layout['blocked_seats'])) {
                    $blocked_seats = $layout['blocked_seats'];
                } elseif (is_string($layout['blocked_seats']) && !empty($layout['blocked_seats'])) {
                    $blocked_seats = array_filter(array_map('trim', explode(',', $layout['blocked_seats'])));
                }
            }
            
            $driver_seats = isset($layout['driver_seats']) ? intval($layout['driver_seats']) : 1;
            $back_row_extra = isset($layout['back_row_extra']) ? intval($layout['back_row_extra']) : 1;
            $total_rows = $bus->rows + 2; // driver + regular + back
            $back_row = $total_rows;
            $back_row_seats_count = $bus->seats_per_row + $back_row_extra;
            
            // Driver row (row 1) - passenger seats only
            $passenger_count = max(0, $driver_seats - 1);
            for ($s = 1; $s <= $passenger_count; $s++) {
                $key = '1_' . $s;
                $label = $this->generate_seat_label(1, $s, $bus->aisle_position);
                if (!isset($seat_map['booked_seats'][$key]) && !in_array($label, $blocked_seats)) {
                    $available[] = array(
                        'event_bus_id' => $bus->id,
                        'seat_row' => 1,
                        'seat_number' => $s,
                        'aisle_position' => $bus->aisle_position,
                        'seat_label' => $label,
                    );
                }
            }
            
            // Regular rows (row 2 to rows+1)
            for ($r = 2; $r <= $bus->rows + 1; $r++) {
                for ($s = 1; $s <= $bus->seats_per_row; $s++) {
                    $key = $r . '_' . $s;
                    $label = $this->generate_seat_label($r, $s, $bus->aisle_position);
                    if (!isset($seat_map['booked_seats'][$key]) && !in_array($label, $blocked_seats)) {
                        $available[] = array(
                            'event_bus_id' => $bus->id,
                            'seat_row' => $r,
                            'seat_number' => $s,
                            'aisle_position' => $bus->aisle_position,
                            'seat_label' => $label,
                        );
                    }
                }
            }
            
            // Back row
            for ($s = 1; $s <= $back_row_seats_count; $s++) {
                $key = $back_row . '_' . $s;
                $label = $this->generate_seat_label($back_row, $s, $bus->aisle_position);
                if (!isset($seat_map['booked_seats'][$key]) && !in_array($label, $blocked_seats)) {
                    $available[] = array(
                        'event_bus_id' => $bus->id,
                        'seat_row' => $back_row,
                        'seat_number' => $s,
                        'aisle_position' => $bus->aisle_position,
                        'seat_label' => $label,
                    );
                }
            }
        }
        
        return $available;
    }
    
    /**
     * Get all bus passengers for an event (with user details)
     */
    public function get_event_passengers($event_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT sb.*, eb.bus_number, eb.bus_name,
                    bt.name_ar as template_name, bt.icon, bt.color,
                    u.display_name,
                    um.meta_value as name_ar,
                    um_fn.meta_value as first_name,
                    um_mn.meta_value as middle_name,
                    um_g.meta_value as gender
             FROM {$this->bookings_table} sb
             JOIN {$this->event_buses_table} eb ON sb.event_bus_id = eb.id
             JOIN {$this->templates_table} bt ON eb.bus_template_id = bt.id
             LEFT JOIN {$wpdb->users} u ON sb.user_id = u.ID
             LEFT JOIN {$wpdb->usermeta} um ON sb.user_id = um.user_id AND um.meta_key = 'sp_name_ar'
             LEFT JOIN {$wpdb->usermeta} um_fn ON sb.user_id = um_fn.user_id AND um_fn.meta_key = 'first_name'
             LEFT JOIN {$wpdb->usermeta} um_mn ON sb.user_id = um_mn.user_id AND um_mn.meta_key = 'sp_middle_name'
             LEFT JOIN {$wpdb->usermeta} um_g ON sb.user_id = um_g.user_id AND um_g.meta_key = 'sp_gender'
             WHERE eb.event_id = %d AND eb.is_active = 1 AND sb.status != 'cancelled'
             ORDER BY eb.bus_number ASC, sb.seat_row ASC, sb.seat_number ASC",
            $event_id
        ));
    }
}
