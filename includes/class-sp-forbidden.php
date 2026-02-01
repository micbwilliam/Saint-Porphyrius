<?php
/**
 * Saint Porphyrius - Forbidden System Handler
 * Handles the محروم (forbidden/discipline) system
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Forbidden {
    
    private static $instance = null;
    private $status_table;
    private $history_table;
    private $events_table;
    private $attendance_table;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->status_table = $wpdb->prefix . 'sp_forbidden_status';
        $this->history_table = $wpdb->prefix . 'sp_forbidden_history';
        $this->events_table = $wpdb->prefix . 'sp_events';
        $this->attendance_table = $wpdb->prefix . 'sp_attendance';
    }
    
    /**
     * Get forbidden settings
     */
    public function get_settings() {
        $defaults = array(
            'forbidden_events_count' => 2,
            'yellow_card_threshold' => 3,
            'red_card_threshold' => 6,
        );
        
        $settings = get_option('sp_forbidden_settings', $defaults);
        return wp_parse_args($settings, $defaults);
    }
    
    /**
     * Update forbidden settings
     */
    public function update_settings($settings) {
        update_option('sp_forbidden_settings', $settings);
    }
    
    /**
     * Get user's forbidden status
     */
    public function get_user_status($user_id) {
        global $wpdb;
        
        $status = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->status_table} WHERE user_id = %d",
            $user_id
        ));
        
        if (!$status) {
            // Return default status
            return (object) array(
                'user_id' => $user_id,
                'forbidden_remaining' => 0,
                'consecutive_absences' => 0,
                'card_status' => 'none',
                'last_absence_event_id' => null,
                'blocked_at' => null,
                'unblocked_at' => null,
            );
        }
        
        return $status;
    }
    
    /**
     * Check if user is currently forbidden
     */
    public function is_user_forbidden($user_id) {
        $status = $this->get_user_status($user_id);
        return $status->forbidden_remaining > 0;
    }
    
    /**
     * Check if user is blocked (red card)
     */
    public function is_user_blocked($user_id) {
        $status = $this->get_user_status($user_id);
        return $status->card_status === 'red' && $status->blocked_at && !$status->unblocked_at;
    }
    
    /**
     * Check if user has yellow card
     */
    public function has_yellow_card($user_id) {
        $status = $this->get_user_status($user_id);
        return $status->card_status === 'yellow';
    }
    
    /**
     * Process attendance for forbidden system
     * Called when attendance is marked
     */
    public function process_attendance($event_id, $user_id, $attendance_status) {
        global $wpdb;
        
        // Check if event has forbidden enabled
        $event = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->events_table} WHERE id = %d",
            $event_id
        ));
        
        if (!$event || !$event->forbidden_enabled) {
            return; // Forbidden system not active for this event
        }
        
        $settings = $this->get_settings();
        $current_status = $this->get_user_status($user_id);
        
        // If user attended or was late - reset consecutive absences
        if ($attendance_status === 'attended' || $attendance_status === 'late') {
            $this->reset_consecutive_absences($user_id);
            return;
        }
        
        // If user was excused - don't count as absence, don't reset
        if ($attendance_status === 'excused') {
            return;
        }
        
        // If user is currently forbidden - mark as forbidden served
        if ($attendance_status === 'forbidden') {
            $this->serve_forbidden($user_id, $event_id);
            return;
        }
        
        // User is absent without excuse - trigger forbidden system
        if ($attendance_status === 'absent') {
            $this->record_absence($user_id, $event_id, $settings, $current_status);
        }
    }
    
    /**
     * Record an absence and apply penalties
     */
    private function record_absence($user_id, $event_id, $settings, $current_status) {
        global $wpdb;
        
        $new_absences = $current_status->consecutive_absences + 1;
        $new_card_status = $current_status->card_status;
        $blocked_at = $current_status->blocked_at;
        
        // Check for yellow card
        if ($new_absences >= $settings['yellow_card_threshold'] && $new_card_status === 'none') {
            $new_card_status = 'yellow';
            $this->log_history($user_id, $event_id, 'yellow_card', 'Yellow card issued after ' . $new_absences . ' consecutive absences');
        }
        
        // Check for red card
        if ($new_absences >= $settings['red_card_threshold'] && $new_card_status !== 'red') {
            $new_card_status = 'red';
            $blocked_at = current_time('mysql');
            $this->log_history($user_id, $event_id, 'red_card', 'Red card issued - user blocked after ' . $new_absences . ' consecutive absences');
        }
        
        // Apply forbidden penalty (user must miss next X forbidden events)
        $forbidden_remaining = $settings['forbidden_events_count'];
        
        // Update or insert status
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->status_table} WHERE user_id = %d",
            $user_id
        ));
        
        if ($existing) {
            $wpdb->update(
                $this->status_table,
                array(
                    'forbidden_remaining' => $forbidden_remaining,
                    'consecutive_absences' => $new_absences,
                    'card_status' => $new_card_status,
                    'last_absence_event_id' => $event_id,
                    'blocked_at' => $blocked_at,
                ),
                array('user_id' => $user_id)
            );
        } else {
            $wpdb->insert(
                $this->status_table,
                array(
                    'user_id' => $user_id,
                    'forbidden_remaining' => $forbidden_remaining,
                    'consecutive_absences' => $new_absences,
                    'card_status' => $new_card_status,
                    'last_absence_event_id' => $event_id,
                    'blocked_at' => $blocked_at,
                )
            );
        }
        
        // Log the absence
        $this->log_history($user_id, $event_id, 'absence_recorded', 'Absence recorded. Consecutive: ' . $new_absences . ', Forbidden for next ' . $forbidden_remaining . ' events');
        $this->log_history($user_id, $event_id, 'forbidden_applied', 'User must miss next ' . $forbidden_remaining . ' forbidden-enabled events');
    }
    
    /**
     * Serve one forbidden event (reduce remaining count)
     */
    private function serve_forbidden($user_id, $event_id) {
        global $wpdb;
        
        $status = $this->get_user_status($user_id);
        
        if ($status->forbidden_remaining > 0) {
            $new_remaining = $status->forbidden_remaining - 1;
            
            $wpdb->update(
                $this->status_table,
                array('forbidden_remaining' => $new_remaining),
                array('user_id' => $user_id)
            );
            
            $this->log_history($user_id, $event_id, 'forbidden_served', 'Forbidden served. Remaining: ' . $new_remaining);
        }
    }
    
    /**
     * Reset consecutive absences (when user attends)
     */
    private function reset_consecutive_absences($user_id) {
        global $wpdb;
        
        $status = $this->get_user_status($user_id);
        
        // Only reset if not blocked
        if ($status->card_status !== 'red') {
            $wpdb->update(
                $this->status_table,
                array(
                    'consecutive_absences' => 0,
                    'card_status' => 'none',
                ),
                array('user_id' => $user_id)
            );
        }
    }
    
    /**
     * Admin: Unblock user (remove red card)
     */
    public function unblock_user($user_id, $admin_id) {
        global $wpdb;
        
        $wpdb->update(
            $this->status_table,
            array(
                'consecutive_absences' => 0,
                'card_status' => 'none',
                'forbidden_remaining' => 0,
                'unblocked_at' => current_time('mysql'),
            ),
            array('user_id' => $user_id)
        );
        
        $this->log_history($user_id, 0, 'admin_unblock', 'User unblocked by admin', $admin_id);
        
        return true;
    }
    
    /**
     * Admin: Reset user's forbidden status
     */
    public function reset_user_status($user_id, $admin_id) {
        global $wpdb;
        
        $wpdb->update(
            $this->status_table,
            array(
                'consecutive_absences' => 0,
                'card_status' => 'none',
                'forbidden_remaining' => 0,
                'blocked_at' => null,
                'unblocked_at' => null,
            ),
            array('user_id' => $user_id)
        );
        
        $this->log_history($user_id, 0, 'admin_reset', 'User status reset by admin', $admin_id);
        
        return true;
    }
    
    /**
     * Admin: Remove forbidden penalty only
     */
    public function remove_forbidden_penalty($user_id, $admin_id) {
        global $wpdb;
        
        $wpdb->update(
            $this->status_table,
            array('forbidden_remaining' => 0),
            array('user_id' => $user_id)
        );
        
        $this->log_history($user_id, 0, 'admin_reset', 'Forbidden penalty removed by admin', $admin_id);
        
        return true;
    }
    
    /**
     * Log history entry
     */
    private function log_history($user_id, $event_id, $action_type, $details, $created_by = null) {
        global $wpdb;
        
        $wpdb->insert(
            $this->history_table,
            array(
                'user_id' => $user_id,
                'event_id' => $event_id,
                'action_type' => $action_type,
                'details' => $details,
                'created_by' => $created_by,
            )
        );
    }
    
    /**
     * Get user's forbidden history
     */
    public function get_user_history($user_id, $limit = 20) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT h.*, e.title_ar as event_title
             FROM {$this->history_table} h
             LEFT JOIN {$this->events_table} e ON h.event_id = e.id
             WHERE h.user_id = %d
             ORDER BY h.created_at DESC
             LIMIT %d",
            $user_id,
            $limit
        ));
    }
    
    /**
     * Get all users with active forbidden/card status
     */
    public function get_users_with_status() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT s.*, u.display_name, u.user_email
             FROM {$this->status_table} s
             JOIN {$wpdb->users} u ON s.user_id = u.ID
             WHERE s.forbidden_remaining > 0 
                OR s.card_status != 'none'
                OR s.consecutive_absences > 0
             ORDER BY s.card_status DESC, s.consecutive_absences DESC"
        );
    }
    
    /**
     * Get blocked users (red card)
     */
    public function get_blocked_users() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT s.*, u.display_name, u.user_email
             FROM {$this->status_table} s
             JOIN {$wpdb->users} u ON s.user_id = u.ID
             WHERE s.card_status = 'red' 
               AND s.blocked_at IS NOT NULL
               AND s.unblocked_at IS NULL
             ORDER BY s.blocked_at DESC"
        );
    }
    
    /**
     * Count users by status
     */
    public function count_by_status() {
        global $wpdb;
        
        $forbidden = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->status_table} WHERE forbidden_remaining > 0"
        );
        
        $yellow = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->status_table} WHERE card_status = 'yellow'"
        );
        
        $red = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->status_table} 
             WHERE card_status = 'red' AND blocked_at IS NOT NULL AND unblocked_at IS NULL"
        );
        
        return array(
            'forbidden' => (int) $forbidden,
            'yellow_card' => (int) $yellow,
            'red_card' => (int) $red,
        );
    }
    
    /**
     * Check if user should be marked as forbidden for an event
     */
    public function should_mark_forbidden($user_id, $event_id) {
        global $wpdb;
        
        // Check if event has forbidden enabled
        $event = $wpdb->get_row($wpdb->prepare(
            "SELECT forbidden_enabled FROM {$this->events_table} WHERE id = %d",
            $event_id
        ));
        
        if (!$event || !$event->forbidden_enabled) {
            return false;
        }
        
        // Check if user is currently forbidden
        return $this->is_user_forbidden($user_id);
    }
    
    /**
     * Get visual status for user (for dashboard display)
     */
    public function get_visual_status($user_id) {
        $settings = $this->get_settings();
        $status = $this->get_user_status($user_id);
        
        return array(
            'consecutive_absences' => $status->consecutive_absences,
            'max_absences' => $settings['red_card_threshold'],
            'yellow_threshold' => $settings['yellow_card_threshold'],
            'forbidden_remaining' => $status->forbidden_remaining,
            'card_status' => $status->card_status,
            'is_blocked' => $status->card_status === 'red' && $status->blocked_at && !$status->unblocked_at,
            'percentage' => ($status->consecutive_absences / $settings['red_card_threshold']) * 100,
        );
    }
}

// Initialize
SP_Forbidden::get_instance();
