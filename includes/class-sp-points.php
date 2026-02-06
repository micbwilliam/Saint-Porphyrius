<?php
/**
 * Saint Porphyrius - Points Handler
 * Manages reward points and penalties
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Points {
    
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
        $this->table_name = $wpdb->prefix . 'sp_points_log';
    }
    
    /**
     * Add points to user (can be negative for penalties)
     */
    public function add($user_id, $points, $type = 'reward', $event_id = null, $reason = '') {
        global $wpdb;
        
        $current_balance = $this->get_balance($user_id);
        $new_balance = $current_balance + $points;
        
        // Determine type based on points if not specified properly
        if ($points < 0 && $type === 'reward') {
            $type = 'penalty';
        }
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'user_id' => $user_id,
                'event_id' => $event_id,
                'points' => $points,
                'type' => $type,
                'reason' => sanitize_text_field($reason),
                'balance_after' => $new_balance,
                'created_by' => get_current_user_id(),
            ),
            array('%d', '%d', '%d', '%s', '%s', '%d', '%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Failed to add points.', 'saint-porphyrius') . ' ' . $wpdb->last_error);
        }
        
        // Update user meta for quick access
        update_user_meta($user_id, 'sp_points_balance', $new_balance);
        
        return array(
            'success' => true,
            'points' => $points,
            'new_balance' => $new_balance,
        );
    }
    
    /**
     * Get user's point balance
     */
    public function get_balance($user_id) {
        // Try user meta first (cached)
        $balance = get_user_meta($user_id, 'sp_points_balance', true);
        
        if ($balance === '') {
            // Calculate from log
            global $wpdb;
            $balance = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(points) FROM {$this->table_name} WHERE user_id = %d",
                $user_id
            ));
            $balance = $balance ? (int) $balance : 0;
            
            // Cache it
            update_user_meta($user_id, 'sp_points_balance', $balance);
        }
        
        return (int) $balance;
    }
    
    /**
     * Get user's points history
     */
    public function get_history($user_id, $args = array()) {
        global $wpdb;
        
        $defaults = array(
            'from_date' => null,
            'to_date' => null,
            'reason_type' => null,
            'limit' => 50,
            'offset' => 0,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array("user_id = %d");
        $params = array($user_id);
        
        if ($args['from_date']) {
            $where[] = "created_at >= %s";
            $params[] = $args['from_date'];
        }
        
        if ($args['to_date']) {
            $where[] = "created_at <= %s";
            $params[] = $args['to_date'];
        }
        
        if ($args['reason_type']) {
            $where[] = "type = %s";
            $params[] = $args['reason_type'];
        }
        
        $where_sql = implode(' AND ', $where);
        
        $sql = "SELECT * FROM {$this->table_name}
                WHERE $where_sql
                ORDER BY created_at DESC
                LIMIT %d OFFSET %d";
        
        $params[] = $args['limit'];
        $params[] = $args['offset'];
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
    
    /**
     * Get leaderboard
     */
    public function get_leaderboard($limit = 10, $period = 'all') {
        global $wpdb;
        
        $where = "1=1";
        
        if ($period === 'month') {
            $where = "created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        } elseif ($period === 'year') {
            $where = "created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        }
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id, SUM(points) as total_points
             FROM {$this->table_name}
             WHERE $where
             GROUP BY user_id
             HAVING total_points > 0
             ORDER BY total_points DESC
             LIMIT %d",
            $limit
        ));
        
        // Enrich with user data
        foreach ($results as &$row) {
            $user = get_user_by('id', $row->user_id);
            if ($user) {
                $row->display_name = $user->display_name;
                $row->name_ar = get_user_meta($row->user_id, 'sp_name_ar', true);
            }
        }
        
        return $results;
    }
    
    /**
     * Get all members with points
     */
    public function get_all_with_points($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'orderby' => 'points',
            'order' => 'DESC',
            'limit' => 100,
            'offset' => 0,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Get all members
        $members = get_users(array(
            'role' => 'sp_member',
            'number' => $args['limit'],
            'offset' => $args['offset'],
        ));
        
        $results = array();
        foreach ($members as $member) {
            $results[] = array(
                'user_id' => $member->ID,
                'display_name' => $member->display_name,
                'name_ar' => get_user_meta($member->ID, 'sp_name_ar', true),
                'email' => $member->user_email,
                'points' => $this->get_balance($member->ID),
            );
        }
        
        // Sort
        if ($args['orderby'] === 'points') {
            usort($results, function($a, $b) use ($args) {
                if ($args['order'] === 'DESC') {
                    return $b['points'] - $a['points'];
                }
                return $a['points'] - $b['points'];
            });
        }
        
        return $results;
    }
    
    /**
     * Manual points adjustment
     */
    public function adjust($user_id, $points, $reason) {
        $type = $points >= 0 ? 'adjustment' : 'penalty';
        return $this->add($user_id, $points, $type, null, $reason);
    }
    
    /**
     * Get points summary statistics
     */
    public function get_summary_stats() {
        global $wpdb;
        
        $stats = $wpdb->get_row(
            "SELECT 
                SUM(CASE WHEN points > 0 THEN points ELSE 0 END) as total_awarded,
                SUM(CASE WHEN points < 0 THEN ABS(points) ELSE 0 END) as total_penalties,
                COUNT(DISTINCT user_id) as members_with_points
             FROM {$this->table_name}"
        );
        
        return $stats;
    }
    
    /**
     * Get reason types with labels
     */
    public static function get_reason_types() {
        return array(
            'attendance' => array(
                'label_en' => 'Attendance',
                'label_ar' => 'حضور',
                'color' => '#10B981', // green
            ),
            'late_attendance' => array(
                'label_en' => 'Late Attendance',
                'label_ar' => 'حضور متأخر',
                'color' => '#F59E0B', // amber
            ),
            'absence_penalty' => array(
                'label_en' => 'Absence Penalty',
                'label_ar' => 'غياب',
                'color' => '#EF4444', // red
            ),
            'excused' => array(
                'label_en' => 'Excused',
                'label_ar' => 'معذور',
                'color' => '#6B7280', // gray
            ),
            'excuse_submission' => array(
                'label_en' => 'Excuse Submission',
                'label_ar' => 'رسوم اعتذار',
                'color' => '#8B5CF6', // purple
            ),
            'excuse_denied' => array(
                'label_en' => 'Excuse Denied',
                'label_ar' => 'رفض اعتذار',
                'color' => '#DC2626', // dark red
            ),
            'adjustment' => array(
                'label_en' => 'Adjustment',
                'label_ar' => 'تعديل',
                'color' => '#3B82F6', // blue
            ),
            'bonus' => array(
                'label_en' => 'Bonus',
                'label_ar' => 'مكافأة',
                'color' => '#14B8A6', // teal
            ),
            'reward' => array(
                'label_en' => 'Reward',
                'label_ar' => 'مكافأة',
                'color' => '#10B981', // green
            ),
            'penalty' => array(
                'label_en' => 'Penalty',
                'label_ar' => 'خصم',
                'color' => '#EF4444', // red
            ),
            'bus_booking_fee' => array(
                'label_en' => 'Bus Booking Fee',
                'label_ar' => 'رسوم حجز الباص',
                'color' => '#F97316', // orange
            ),
            'bus_booking_refund' => array(
                'label_en' => 'Bus Booking Refund',
                'label_ar' => 'استرداد رسوم الباص',
                'color' => '#22C55E', // green
            ),
            'point_share_sent' => array(
                'label_en' => 'Points Shared',
                'label_ar' => 'مشاركة نقاط',
                'color' => '#E11D48', // rose
            ),
            'point_share_received' => array(
                'label_en' => 'Points Received',
                'label_ar' => 'نقاط مُهداة',
                'color' => '#7C3AED', // violet
            ),
        );
    }

    /**
     * Get type label for display
     */
    public static function get_type_label($type, $lang = 'en') {
        $types = self::get_reason_types();
        if (isset($types[$type])) {
            return $lang === 'ar' ? $types[$type]['label_ar'] : $types[$type]['label_en'];
        }
        return $type;
    }

    /**
     * Get type color for display
     */
    public static function get_type_color($type) {
        $types = self::get_reason_types();
        if (isset($types[$type]) && isset($types[$type]['color'])) {
            return $types[$type]['color'];
        }
        return '#6B7280'; // default gray
    }
    
    /**
     * Recalculate user balance from log
     */
    public function recalculate_balance($user_id) {
        global $wpdb;
        
        $balance = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(points) FROM {$this->table_name} WHERE user_id = %d",
            $user_id
        ));
        
        $balance = $balance ? (int) $balance : 0;
        update_user_meta($user_id, 'sp_points_balance', $balance);
        
        return $balance;
    }
    
    /**
     * Recalculate all balances
     */
    public function recalculate_all_balances() {
        $members = get_users(array('role' => 'sp_member'));
        
        foreach ($members as $member) {
            $this->recalculate_balance($member->ID);
        }
        
        return count($members);
    }
}
