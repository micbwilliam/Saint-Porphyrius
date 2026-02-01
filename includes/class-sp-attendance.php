<?php
/**
 * Saint Porphyrius - Attendance Handler
 * Tracks attendance for events
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Attendance {
    
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
        $this->table_name = $wpdb->prefix . 'sp_attendance';
    }
    
    /**
     * Get attendance records for an event
     */
    public function get_by_event($event_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, u.display_name, u.user_email,
                    um_phone.meta_value as phone,
                    um_name_ar.meta_value as name_ar
             FROM {$this->table_name} a
             LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
             LEFT JOIN {$wpdb->usermeta} um_phone ON a.user_id = um_phone.user_id AND um_phone.meta_key = 'sp_phone'
             LEFT JOIN {$wpdb->usermeta} um_name_ar ON a.user_id = um_name_ar.user_id AND um_name_ar.meta_key = 'sp_name_ar'
             WHERE a.event_id = %d
             ORDER BY u.display_name ASC",
            $event_id
        ));
    }
    
    /**
     * Get attendance records for a user
     */
    public function get_by_user($user_id, $args = array()) {
        global $wpdb;
        
        $defaults = array(
            'from_date' => null,
            'to_date' => null,
            'limit' => 50,
            'offset' => 0,
        );
        
        $args = wp_parse_args($args, $defaults);
        $events_table = $wpdb->prefix . 'sp_events';
        $types_table = $wpdb->prefix . 'sp_event_types';
        
        $where = array("a.user_id = %d");
        $params = array($user_id);
        
        if ($args['from_date']) {
            $where[] = "e.event_date >= %s";
            $params[] = $args['from_date'];
        }
        
        if ($args['to_date']) {
            $where[] = "e.event_date <= %s";
            $params[] = $args['to_date'];
        }
        
        $where_sql = implode(' AND ', $where);
        
        $sql = "SELECT a.*, e.title_ar as event_title, e.event_date, e.start_time,
                       et.name_ar as type_name_ar, et.icon as type_icon, et.color as type_color
                FROM {$this->table_name} a
                LEFT JOIN $events_table e ON a.event_id = e.id
                LEFT JOIN $types_table et ON e.event_type_id = et.id
                WHERE $where_sql
                ORDER BY e.event_date DESC
                LIMIT %d OFFSET %d";
        
        $params[] = $args['limit'];
        $params[] = $args['offset'];
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
    
    /**
     * Get attendance record
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
     * Mark attendance for a user
     */
    public function mark($event_id, $user_id, $status, $notes = '', $points_awarded = null) {
        global $wpdb;
        
        // Verify event exists
        $events = SP_Events::get_instance();
        $event = $events->get($event_id);
        if (!$event) {
            return new WP_Error('not_found', __('Event not found.', 'saint-porphyrius'));
        }
        
        // Verify user exists and is member
        $user = get_user_by('id', $user_id);
        if (!$user || !in_array('sp_member', $user->roles)) {
            return new WP_Error('invalid_user', __('User not found or is not a member.', 'saint-porphyrius'));
        }
        
        // Validate status
        $valid_statuses = array('attended', 'absent', 'excused', 'late');
        if (!in_array($status, $valid_statuses)) {
            return new WP_Error('invalid_status', __('Invalid attendance status.', 'saint-porphyrius'));
        }
        
        // Calculate points if not provided
        if ($points_awarded === null) {
            $points_config = $events->get_event_points($event);
            switch ($status) {
                case 'attended':
                    // Full attendance points (positive reward)
                    $points_awarded = abs((int) $points_config['attendance']);
                    break;
                case 'late':
                    // Late attendance points (positive reward, but less than full attendance)
                    $points_awarded = abs((int) $points_config['late']);
                    break;
                case 'absent':
                    // Only penalize for mandatory events (negative penalty)
                    if ($event->is_mandatory) {
                        $points_awarded = -1 * abs((int) $points_config['penalty']);
                    } else {
                        $points_awarded = 0;
                    }
                    break;
                case 'excused':
                    // No points change for excused (they already paid via excuse system)
                    $points_awarded = 0;
                    break;
            }
        }
        
        $existing = $this->get($event_id, $user_id);
        $old_points = $existing ? $existing->points_awarded : 0;
        
        if ($existing) {
            // Update existing record
            $result = $wpdb->update(
                $this->table_name,
                array(
                    'status' => $status,
                    'notes' => sanitize_textarea_field($notes),
                    'points_awarded' => $points_awarded,
                    'marked_by' => get_current_user_id(),
                    'marked_at' => current_time('mysql'),
                ),
                array('id' => $existing->id),
                array('%s', '%s', '%d', '%d', '%s'),
                array('%d')
            );
            
            // Adjust points: reverse old points and apply new
            if ($result !== false && $old_points !== $points_awarded) {
                $points_handler = SP_Points::get_instance();
                $point_diff = $points_awarded - $old_points;
                
                if ($point_diff !== 0) {
                    $reason = $this->get_points_reason($status, $event);
                    $points_handler->add($user_id, $point_diff, $this->get_points_type($status), $event_id, $reason);
                }
            }
        } else {
            // Insert new record
            $result = $wpdb->insert(
                $this->table_name,
                array(
                    'event_id' => $event_id,
                    'user_id' => $user_id,
                    'status' => $status,
                    'notes' => sanitize_textarea_field($notes),
                    'points_awarded' => $points_awarded,
                    'marked_by' => get_current_user_id(),
                    'marked_at' => current_time('mysql'),
                ),
                array('%d', '%d', '%s', '%s', '%d', '%d', '%s')
            );
            
            // Award points for new attendance record
            if ($result !== false && $points_awarded !== 0) {
                $points_handler = SP_Points::get_instance();
                $reason = $this->get_points_reason($status, $event);
                $points_handler->add($user_id, $points_awarded, $this->get_points_type($status), $event_id, $reason);
            }
        }
        
        if ($result === false) {
            return new WP_Error('db_error', __('Failed to mark attendance.', 'saint-porphyrius'));
        }
        
        return array(
            'success' => true,
            'points' => $points_awarded,
            'message' => __('Attendance marked successfully.', 'saint-porphyrius')
        );
    }
    
    /**
     * Bulk mark attendance for event
     */
    public function bulk_mark($event_id, $attendance_data) {
        $results = array(
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => array(),
        );
        
        foreach ($attendance_data as $user_id => $data) {
            $status = isset($data['status']) ? trim($data['status']) : '';
            $notes = $data['notes'] ?? '';
            
            // Skip if no status selected
            if (empty($status)) {
                $results['skipped']++;
                continue;
            }
            
            $result = $this->mark($event_id, $user_id, $status, $notes);
            
            if (is_wp_error($result)) {
                $results['failed']++;
                $results['errors'][] = sprintf('User %d: %s', $user_id, $result->get_error_message());
            } else {
                $results['success']++;
            }
        }
        
        return $results;
    }
    
    /**
     * Get all members for attendance marking
     */
    public function get_members_for_event($event_id) {
        global $wpdb;
        
        // Get all members
        $members = get_users(array(
            'role' => 'sp_member',
            'orderby' => 'display_name',
            'order' => 'ASC',
        ));
        
        // Get existing attendance records
        $existing = $this->get_by_event($event_id);
        $existing_map = array();
        foreach ($existing as $record) {
            $existing_map[$record->user_id] = $record;
        }
        
        // Merge data
        $result = array();
        foreach ($members as $member) {
            $result[] = array(
                'user_id' => $member->ID,
                'display_name' => $member->display_name,
                'name_ar' => get_user_meta($member->ID, 'sp_name_ar', true),
                'phone' => get_user_meta($member->ID, 'sp_phone', true),
                'email' => $member->user_email,
                'attendance' => $existing_map[$member->ID] ?? null,
            );
        }
        
        return $result;
    }
    
    /**
     * Process points for completed event
     */
    public function process_event_points($event_id) {
        global $wpdb;
        
        $events = SP_Events::get_instance();
        $event = $events->get($event_id);
        if (!$event) {
            return new WP_Error('not_found', __('Event not found.', 'saint-porphyrius'));
        }
        
        $points = SP_Points::get_instance();
        $attendance_records = $this->get_by_event($event_id);
        
        foreach ($attendance_records as $record) {
            if ($record->points_awarded != 0 && !$record->points_processed) {
                // Determine type based on points
                $type = $record->points_awarded > 0 ? 'reward' : 'penalty';
                
                // Add points to user's log
                $points->add(
                    $record->user_id,
                    $record->points_awarded,
                    $type,
                    $event_id,
                    sprintf(__('Event: %s - Status: %s', 'saint-porphyrius'), $event->title_ar, $record->status)
                );
                
                // Mark as processed
                $wpdb->update(
                    $this->table_name,
                    array('points_processed' => 1),
                    array('id' => $record->id),
                    array('%d'),
                    array('%d')
                );
            }
        }
        
        // Mark absent members who weren't recorded
        if ($event->is_mandatory) {
            $all_members = get_users(array('role' => 'sp_member'));
            $recorded_users = wp_list_pluck($attendance_records, 'user_id');
            
            foreach ($all_members as $member) {
                if (!in_array($member->ID, $recorded_users)) {
                    // Auto-mark as absent
                    $this->mark($event_id, $member->ID, 'absent', __('Auto-marked absent', 'saint-porphyrius'));
                }
            }
        }
        
        return array(
            'success' => true,
            'message' => __('Points processed successfully.', 'saint-porphyrius')
        );
    }
    
    /**
     * Get user attendance statistics
     */
    public function get_user_stats($user_id) {
        global $wpdb;
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'attended' THEN 1 ELSE 0 END) as attended,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                SUM(points_awarded) as total_points
             FROM {$this->table_name}
             WHERE user_id = %d",
            $user_id
        ));
        
        // Calculate attendance rate
        if ($stats->total > 0) {
            $stats->attendance_rate = round(($stats->attended + $stats->late) / $stats->total * 100, 1);
        } else {
            $stats->attendance_rate = 0;
        }
        
        return $stats;
    }
    
    /**
     * Get points type based on attendance status
     */
    private function get_points_type($status) {
        switch ($status) {
            case 'attended':
                return 'attendance';
            case 'late':
                return 'late_attendance';
            case 'absent':
                return 'absence_penalty';
            case 'excused':
                return 'excused';
            default:
                return 'attendance';
        }
    }
    
    /**
     * Get points reason based on attendance status and event
     */
    private function get_points_reason($status, $event) {
        $event_title = $event->title ?? $event->title_ar ?? sprintf(__('فعالية #%d', 'saint-porphyrius'), $event->id);
        
        switch ($status) {
            case 'attended':
                return sprintf(__('حضور: %s', 'saint-porphyrius'), $event_title);
            case 'late':
                return sprintf(__('حضور متأخر: %s', 'saint-porphyrius'), $event_title);
            case 'absent':
                return sprintf(__('غياب: %s', 'saint-porphyrius'), $event_title);
            case 'excused':
                return sprintf(__('معذور: %s', 'saint-porphyrius'), $event_title);
            default:
                return $event_title;
        }
    }
}
