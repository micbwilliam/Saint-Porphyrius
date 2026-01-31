<?php
/**
 * Saint Porphyrius - Events Handler
 * Manages events with dates, times, locations
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Events {
    
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
        $this->table_name = $wpdb->prefix . 'sp_events';
    }
    
    /**
     * Get all events with optional filters
     */
    public function get_all($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => null,
            'event_type_id' => null,
            'from_date' => null,
            'to_date' => null,
            'upcoming_only' => false,
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'event_date',
            'order' => 'ASC',
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $types_table = $wpdb->prefix . 'sp_event_types';
        
        $where = array("1=1");
        $params = array();
        
        if ($args['status']) {
            $where[] = "e.status = %s";
            $params[] = $args['status'];
        }
        
        if ($args['event_type_id']) {
            $where[] = "e.event_type_id = %d";
            $params[] = $args['event_type_id'];
        }
        
        if ($args['from_date']) {
            $where[] = "e.event_date >= %s";
            $params[] = $args['from_date'];
        }
        
        if ($args['to_date']) {
            $where[] = "e.event_date <= %s";
            $params[] = $args['to_date'];
        }
        
        if ($args['upcoming_only']) {
            $where[] = "e.event_date >= %s";
            $params[] = current_time('Y-m-d');
            $where[] = "e.status = 'published'";
        }
        
        $where_sql = implode(' AND ', $where);
        $orderby = sanitize_sql_orderby("{$args['orderby']} {$args['order']}") ?: 'event_date ASC';
        
        $sql = "SELECT e.*, et.name_ar as type_name_ar, et.name_en as type_name_en, 
                       et.icon as type_icon, et.color as type_color,
                       et.attendance_points as type_attendance_points,
                       et.absence_penalty as type_absence_penalty
                FROM {$this->table_name} e
                LEFT JOIN $types_table et ON e.event_type_id = et.id
                WHERE $where_sql
                ORDER BY $orderby
                LIMIT %d OFFSET %d";
        
        $params[] = $args['limit'];
        $params[] = $args['offset'];
        
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get event by ID
     */
    public function get($id) {
        global $wpdb;
        
        $types_table = $wpdb->prefix . 'sp_event_types';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT e.*, et.name_ar as type_name_ar, et.name_en as type_name_en,
                    et.icon as type_icon, et.color as type_color,
                    et.attendance_points as type_attendance_points,
                    et.absence_penalty as type_absence_penalty
             FROM {$this->table_name} e
             LEFT JOIN $types_table et ON e.event_type_id = et.id
             WHERE e.id = %d",
            $id
        ));
    }
    
    /**
     * Get upcoming events
     */
    public function get_upcoming($limit = 10) {
        return $this->get_all(array(
            'upcoming_only' => true,
            'limit' => $limit,
            'orderby' => 'event_date',
            'order' => 'ASC',
        ));
    }
    
    /**
     * Create event
     */
    public function create($data) {
        global $wpdb;
        
        $required = array('event_type_id', 'title_ar', 'event_date', 'start_time');
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', sprintf(__('Field %s is required.', 'saint-porphyrius'), $field));
            }
        }
        
        // Verify event type exists
        $event_types = SP_Event_Types::get_instance();
        $type = $event_types->get($data['event_type_id']);
        if (!$type) {
            return new WP_Error('invalid_type', __('Invalid event type.', 'saint-porphyrius'));
        }
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'event_type_id' => absint($data['event_type_id']),
                'title_ar' => sanitize_text_field($data['title_ar']),
                'title_en' => sanitize_text_field($data['title_en'] ?? ''),
                'description' => sanitize_textarea_field($data['description'] ?? ''),
                'event_date' => sanitize_text_field($data['event_date']),
                'start_time' => sanitize_text_field($data['start_time']),
                'end_time' => sanitize_text_field($data['end_time'] ?? ''),
                'location_name' => sanitize_text_field($data['location_name'] ?? ''),
                'location_address' => sanitize_textarea_field($data['location_address'] ?? ''),
                'location_lat' => !empty($data['location_lat']) ? floatval($data['location_lat']) : null,
                'location_lng' => !empty($data['location_lng']) ? floatval($data['location_lng']) : null,
                'attendance_points' => isset($data['attendance_points']) ? absint($data['attendance_points']) : null,
                'absence_penalty' => isset($data['absence_penalty']) ? absint($data['absence_penalty']) : null,
                'is_mandatory' => isset($data['is_mandatory']) ? (int) $data['is_mandatory'] : 0,
                'max_attendees' => !empty($data['max_attendees']) ? absint($data['max_attendees']) : null,
                'status' => sanitize_text_field($data['status'] ?? 'draft'),
                'created_by' => get_current_user_id(),
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%d', '%d', '%d', '%d', '%s', '%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create event.', 'saint-porphyrius'));
        }
        
        return array(
            'success' => true,
            'id' => $wpdb->insert_id,
            'message' => __('Event created successfully.', 'saint-porphyrius')
        );
    }
    
    /**
     * Update event
     */
    public function update($id, $data) {
        global $wpdb;
        
        $existing = $this->get($id);
        if (!$existing) {
            return new WP_Error('not_found', __('Event not found.', 'saint-porphyrius'));
        }
        
        $update_data = array();
        $format = array();
        
        $fields = array(
            'event_type_id' => '%d',
            'title_ar' => '%s',
            'title_en' => '%s',
            'description' => '%s',
            'event_date' => '%s',
            'start_time' => '%s',
            'end_time' => '%s',
            'location_name' => '%s',
            'location_address' => '%s',
            'location_lat' => '%f',
            'location_lng' => '%f',
            'attendance_points' => '%d',
            'absence_penalty' => '%d',
            'is_mandatory' => '%d',
            'max_attendees' => '%d',
            'status' => '%s',
        );
        
        foreach ($fields as $field => $field_format) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
                $format[] = $field_format;
            }
        }
        
        if (empty($update_data)) {
            return new WP_Error('no_data', __('No data to update.', 'saint-porphyrius'));
        }
        
        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => $id),
            $format,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Failed to update event.', 'saint-porphyrius'));
        }
        
        return array(
            'success' => true,
            'message' => __('Event updated successfully.', 'saint-porphyrius')
        );
    }
    
    /**
     * Delete event
     */
    public function delete($id) {
        global $wpdb;
        
        // Delete related attendance records
        $attendance_table = $wpdb->prefix . 'sp_attendance';
        $wpdb->delete($attendance_table, array('event_id' => $id), array('%d'));
        
        $result = $wpdb->delete($this->table_name, array('id' => $id), array('%d'));
        
        if ($result === false) {
            return new WP_Error('db_error', __('Failed to delete event.', 'saint-porphyrius'));
        }
        
        return array(
            'success' => true,
            'message' => __('Event deleted successfully.', 'saint-porphyrius')
        );
    }
    
    /**
     * Get event's effective points (event-specific or type default)
     */
    public function get_event_points($event) {
        $attendance = $event->attendance_points !== null ? $event->attendance_points : $event->type_attendance_points;
        $penalty = $event->absence_penalty !== null ? $event->absence_penalty : $event->type_absence_penalty;
        
        return array(
            'attendance' => (int) $attendance,
            'penalty' => (int) $penalty,
        );
    }
    
    /**
     * Mark event as completed and process attendance
     */
    public function complete_event($id) {
        $event = $this->get($id);
        if (!$event) {
            return new WP_Error('not_found', __('Event not found.', 'saint-porphyrius'));
        }
        
        // Update status
        $this->update($id, array('status' => 'completed'));
        
        // Process points for all members
        $attendance = SP_Attendance::get_instance();
        $attendance->process_event_points($id);
        
        return array(
            'success' => true,
            'message' => __('Event completed and points processed.', 'saint-porphyrius')
        );
    }
    
    /**
     * Get event statistics
     */
    public function get_stats($id) {
        global $wpdb;
        
        $attendance_table = $wpdb->prefix . 'sp_attendance';
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_records,
                SUM(CASE WHEN status = 'attended' THEN 1 ELSE 0 END) as attended,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
             FROM $attendance_table WHERE event_id = %d",
            $id
        ));
        
        return $stats;
    }
}
