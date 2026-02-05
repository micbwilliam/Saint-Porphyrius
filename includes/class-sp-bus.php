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
                $bus->available_seats = $bus->capacity - count($bus->bookings);
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
            "SELECT sb.*, u.display_name, um.meta_value as name_ar
             FROM {$this->bookings_table} sb
             LEFT JOIN {$wpdb->users} u ON sb.user_id = u.ID
             LEFT JOIN {$wpdb->usermeta} um ON sb.user_id = um.user_id AND um.meta_key = 'sp_name_ar'
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
        
        // Check if seat is already booked
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->bookings_table} 
             WHERE event_bus_id = %d AND seat_row = %d AND seat_number = %d AND status != 'cancelled'",
            $event_bus_id, $seat_row, $seat_number
        ));
        
        if ($existing) {
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
        
        // Get booking fee from event
        $events_table = $wpdb->prefix . 'sp_events';
        $booking_fee = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT bus_booking_fee FROM $events_table WHERE id = %d",
            $event_id
        ));
        
        // Check if user has enough points
        if ($booking_fee > 0) {
            $points_handler = SP_Points::get_instance();
            $user_points = $points_handler->get_user_points($user_id);
            
            if ($user_points < $booking_fee) {
                return new WP_Error('insufficient_points', sprintf(
                    __('رصيدك غير كافٍ. تحتاج %d نقطة لحجز المقعد. رصيدك الحالي: %d نقطة', 'saint-porphyrius'),
                    $booking_fee,
                    $user_points
                ));
            }
        }
        
        // Create booking
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
        
        // Deduct booking fee from user points
        if ($booking_fee > 0) {
            $points_handler = SP_Points::get_instance();
            $points_handler->add_points(
                $user_id,
                -$booking_fee,
                'bus_booking_fee',
                $event_id,
                sprintf(__('رسوم حجز مقعد الباص للفعالية', 'saint-porphyrius'))
            );
        }
        
        return array(
            'success' => true,
            'booking_id' => $booking_id,
            'seat_label' => $seat_label,
            'bus_number' => $bus->bus_number,
            'fee_deducted' => $booking_fee,
            'message' => $booking_fee > 0 
                ? sprintf(__('تم حجز المقعد %s بنجاح. تم خصم %d نقطة (تُعاد عند الحضور)', 'saint-porphyrius'), $seat_label, $booking_fee)
                : sprintf(__('تم حجز المقعد %s بنجاح', 'saint-porphyrius'), $seat_label)
        );
    }
    
    /**
     * Cancel a booking
     */
    public function cancel_booking($booking_id, $user_id = null) {
        global $wpdb;
        
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
        if (is_string($config)) {
            return json_decode($config, true);
        }
        return $config;
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
            $booked_seats[$key] = array(
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'user_name' => $booking->name_ar ?: $booking->display_name,
                'seat_label' => $booking->seat_label,
                'status' => $booking->status,
            );
        }
        
        $layout = $this->parse_layout_config($bus->layout_config);
        $disabled_seats = isset($layout['disabled_seats']) ? $layout['disabled_seats'] : array('1A');
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
}
