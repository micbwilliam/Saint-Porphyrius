<?php
/**
 * Saint Porphyrius - Point Sharing Handler
 * Manages sharing/gifting points between members
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Point_Sharing {

    private static $instance = null;
    private $table_name;
    private $points_handler;

    const MIN_SHARE_AMOUNT = 1;
    const MIN_BALANCE_AFTER_SHARE = 0;

    /**
     * Get point sharing settings
     */
    public function get_settings() {
        $defaults = array(
            'fee_enabled'    => 0,
            'fee_type'       => 'percentage', // 'percentage' or 'fixed'
            'fee_percentage' => 10,
            'fee_fixed'      => 1,
            'fee_min'        => 0,
            'fee_max'        => 0, // 0 = no max
        );

        $settings = get_option('sp_point_sharing_settings', array());
        return wp_parse_args($settings, $defaults);
    }

    /**
     * Update point sharing settings
     */
    public function update_settings($settings) {
        $current = $this->get_settings();
        $settings = wp_parse_args($settings, $current);

        $settings['fee_enabled']    = !empty($settings['fee_enabled']) ? 1 : 0;
        $settings['fee_type']       = in_array($settings['fee_type'], array('percentage', 'fixed')) ? $settings['fee_type'] : 'percentage';
        $settings['fee_percentage'] = max(0, min(100, floatval($settings['fee_percentage'])));
        $settings['fee_fixed']      = max(0, absint($settings['fee_fixed']));
        $settings['fee_min']        = max(0, absint($settings['fee_min']));
        $settings['fee_max']        = max(0, absint($settings['fee_max']));

        update_option('sp_point_sharing_settings', $settings);
        return $settings;
    }

    /**
     * Calculate the fee for a given share amount
     */
    public function calculate_fee($points) {
        $settings = $this->get_settings();

        if (empty($settings['fee_enabled'])) {
            return 0;
        }

        $points = absint($points);

        if ($settings['fee_type'] === 'fixed') {
            $fee = absint($settings['fee_fixed']);
        } else {
            $fee = (int) ceil($points * $settings['fee_percentage'] / 100);
        }

        // Apply min
        if ($settings['fee_min'] > 0 && $fee < $settings['fee_min']) {
            $fee = $settings['fee_min'];
        }

        // Apply max
        if ($settings['fee_max'] > 0 && $fee > $settings['fee_max']) {
            $fee = $settings['fee_max'];
        }

        return $fee;
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sp_point_shares';
        $this->points_handler = SP_Points::get_instance();
    }

    /**
     * Share points from sender to recipient
     */
    public function share_points($sender_id, $recipient_id, $points, $message = '') {
        global $wpdb;

        $points = absint($points);
        $fee = $this->calculate_fee($points);
        $total_cost = $points + $fee;

        // Validate (use total_cost for balance check)
        $validation = $this->can_share($sender_id, $recipient_id, $points);
        if (is_wp_error($validation)) {
            return $validation;
        }

        $message = sanitize_text_field(mb_substr($message, 0, 191));

        // Get recipient name for log
        $recipient_user = get_userdata($recipient_id);
        $recipient_first = $recipient_user->first_name;
        $recipient_middle = get_user_meta($recipient_id, 'sp_middle_name', true);
        $recipient_name = trim($recipient_first . ' ' . $recipient_middle) ?: $recipient_user->display_name;

        // Get sender name for log
        $sender_user = get_userdata($sender_id);
        $sender_first = $sender_user->first_name;
        $sender_middle = get_user_meta($sender_id, 'sp_middle_name', true);
        $sender_name = trim($sender_first . ' ' . $sender_middle) ?: $sender_user->display_name;

        // Snapshot before
        $sender_balance_before = $this->points_handler->get_balance($sender_id);
        $recipient_balance_before = $this->points_handler->get_balance($recipient_id);
        $sender_rank_before = $this->get_user_rank($sender_id);

        // Re-check balance (in case it changed since validation)
        if ($sender_balance_before - $total_cost < self::MIN_BALANCE_AFTER_SHARE) {
            return new WP_Error('insufficient_balance', __('رصيدك غير كافي لإتمام المشاركة (شامل الرسوم)', 'saint-porphyrius'));
        }

        // Deduct points + fee from sender
        $reason_sent = sprintf('مشاركة نقاط لـ %s', $recipient_name);
        if ($fee > 0) {
            $reason_sent .= sprintf(' (رسوم: %d)', $fee);
        }
        if ($message) {
            $reason_sent .= ' - ' . $message;
        }
        $sender_result = $this->points_handler->add($sender_id, -$total_cost, 'point_share_sent', null, $reason_sent);

        if (is_wp_error($sender_result)) {
            return $sender_result;
        }

        // Add to recipient (only the shared amount, not the fee)
        $reason_received = sprintf('نقاط مُهداة من %s', $sender_name);
        if ($message) {
            $reason_received .= ' - ' . $message;
        }
        $recipient_result = $this->points_handler->add($recipient_id, $points, 'point_share_received', null, $reason_received);

        if (is_wp_error($recipient_result)) {
            // Rollback sender deduction
            $rollback = $this->points_handler->add($sender_id, $total_cost, 'adjustment', null, 'استرداد - فشل المشاركة');
            if (is_wp_error($rollback)) {
                error_log('SP Point Sharing: CRITICAL - Failed to rollback sender deduction for user ' . $sender_id . ': ' . $rollback->get_error_message());
            }
            return $recipient_result;
        }

        // Snapshot after
        $sender_balance_after = $this->points_handler->get_balance($sender_id);
        $recipient_balance_after = $this->points_handler->get_balance($recipient_id);
        $sender_rank_after = $this->get_user_rank($sender_id);

        // Record in shares table
        $wpdb->insert(
            $this->table_name,
            array(
                'sender_id' => $sender_id,
                'recipient_id' => $recipient_id,
                'points' => $points,
                'message' => $message,
                'sender_balance_before' => $sender_balance_before,
                'sender_balance_after' => $sender_balance_after,
                'recipient_balance_before' => $recipient_balance_before,
                'recipient_balance_after' => $recipient_balance_after,
                'sender_rank_before' => $sender_rank_before,
                'sender_rank_after' => $sender_rank_after,
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%d', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s')
        );

        return array(
            'success' => true,
            'points_shared' => $points,
            'fee' => $fee,
            'total_deducted' => $total_cost,
            'recipient_name' => $recipient_name,
            'new_balance' => $sender_balance_after,
            'rank_before' => $sender_rank_before,
            'rank_after' => $sender_rank_after,
            'message' => $fee > 0
                ? sprintf('تم إرسال %d نقطة إلى %s (رسوم: %d نقطة)', $points, $recipient_name, $fee)
                : sprintf('تم إرسال %d نقطة إلى %s', $points, $recipient_name),
        );
    }

    /**
     * Preview a share without executing
     */
    public function preview_share($sender_id, $recipient_id, $points) {
        $points = absint($points);
        $fee = $this->calculate_fee($points);
        $total_cost = $points + $fee;

        $sender_balance = $this->points_handler->get_balance($sender_id);
        $new_balance = $sender_balance - $total_cost;

        $recipient_user = get_userdata($recipient_id);
        $recipient_first = $recipient_user ? $recipient_user->first_name : '';
        $recipient_middle = $recipient_user ? get_user_meta($recipient_id, 'sp_middle_name', true) : '';
        $recipient_name = trim($recipient_first . ' ' . $recipient_middle) ?: ($recipient_user ? $recipient_user->display_name : '');

        $current_rank = $this->get_user_rank($sender_id);
        $projected_rank = $this->get_projected_rank($sender_id, -$total_cost);

        $is_valid = true;
        $validation_message = '';

        if ($points < self::MIN_SHARE_AMOUNT) {
            $is_valid = false;
            $validation_message = 'الحد الأدنى نقطة واحدة';
        } elseif ($new_balance < self::MIN_BALANCE_AFTER_SHARE) {
            $is_valid = false;
            $validation_message = $fee > 0 ? 'رصيدك غير كافي (شامل الرسوم)' : 'رصيدك غير كافي';
        }

        return array(
            'current_balance' => $sender_balance,
            'new_balance' => $new_balance,
            'fee' => $fee,
            'total_cost' => $total_cost,
            'current_rank' => $current_rank,
            'projected_rank' => $projected_rank,
            'recipient_name' => $recipient_name,
            'is_valid' => $is_valid,
            'validation_message' => $validation_message,
        );
    }

    /**
     * Validate whether a share can proceed
     */
    public function can_share($sender_id, $recipient_id, $points) {
        $points = absint($points);
        $fee = $this->calculate_fee($points);
        $total_cost = $points + $fee;

        if ($sender_id == $recipient_id) {
            return new WP_Error('self_share', __('لا يمكنك مشاركة النقاط مع نفسك', 'saint-porphyrius'));
        }

        if ($points < self::MIN_SHARE_AMOUNT) {
            return new WP_Error('min_amount', __('الحد الأدنى للمشاركة نقطة واحدة', 'saint-porphyrius'));
        }

        $recipient = get_userdata($recipient_id);
        if (!$recipient) {
            return new WP_Error('invalid_recipient', __('العضو غير موجود', 'saint-porphyrius'));
        }

        $balance = $this->points_handler->get_balance($sender_id);
        if ($balance - $total_cost < self::MIN_BALANCE_AFTER_SHARE) {
            return new WP_Error('insufficient_balance', 
                $fee > 0 
                    ? sprintf(__('رصيدك غير كافي لإتمام المشاركة (المطلوب: %d نقطة + %d رسوم = %d)', 'saint-porphyrius'), $points, $fee, $total_cost)
                    : __('رصيدك غير كافي لإتمام المشاركة', 'saint-porphyrius')
            );
        }

        return true;
    }

    /**
     * Get current user rank in leaderboard
     */
    public function get_user_rank($user_id) {
        global $wpdb;
        $points_table = $wpdb->prefix . 'sp_points_log';

        $user_points = $this->points_handler->get_balance($user_id);

        // Count users with more points than this user (excluding self)
        $higher_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM (
                SELECT user_id, SUM(points) as total
                FROM $points_table
                WHERE user_id != %d
                GROUP BY user_id
                HAVING total > %d
            ) AS ranked",
            $user_id,
            $user_points
        ));

        return ($higher_count !== null) ? (int) $higher_count + 1 : 1;
    }

    /**
     * Get projected rank after a point change
     */
    private function get_projected_rank($user_id, $point_change) {
        global $wpdb;
        $points_table = $wpdb->prefix . 'sp_points_log';

        $current_balance = $this->points_handler->get_balance($user_id);
        $projected_balance = $current_balance + $point_change;

        $higher_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM (
                SELECT user_id, SUM(points) as total
                FROM $points_table
                WHERE user_id != %d
                GROUP BY user_id
                HAVING total > %d
            ) AS ranked",
            $user_id,
            $projected_balance
        ));

        return ($higher_count !== null) ? (int) $higher_count + 1 : 1;
    }

    /**
     * Get share history for a user
     */
    public function get_share_history($user_id, $args = array()) {
        global $wpdb;

        $defaults = array(
            'direction' => 'all', // 'all', 'sent', 'received'
            'limit' => 20,
            'offset' => 0,
        );

        $args = wp_parse_args($args, $defaults);

        $where = array();
        $params = array();

        if ($args['direction'] === 'sent') {
            $where[] = "sender_id = %d";
            $params[] = $user_id;
        } elseif ($args['direction'] === 'received') {
            $where[] = "recipient_id = %d";
            $params[] = $user_id;
        } else {
            $where[] = "(sender_id = %d OR recipient_id = %d)";
            $params[] = $user_id;
            $params[] = $user_id;
        }

        $where_sql = implode(' AND ', $where);

        $params[] = $args['limit'];
        $params[] = $args['offset'];

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name}
             WHERE $where_sql
             ORDER BY created_at DESC
             LIMIT %d OFFSET %d",
            $params
        ));

        // Enrich with user data
        foreach ($results as &$row) {
            $row->direction = ($row->sender_id == $user_id) ? 'sent' : 'received';
            $other_id = ($row->direction === 'sent') ? $row->recipient_id : $row->sender_id;

            $other_user = get_userdata($other_id);
            if ($other_user) {
                $first = $other_user->first_name;
                $middle = get_user_meta($other_id, 'sp_middle_name', true);
                $row->other_name = trim($first . ' ' . $middle) ?: $other_user->display_name;
                $row->other_initial = mb_substr($first ?: $other_user->display_name, 0, 1);
                $row->other_gender = get_user_meta($other_id, 'sp_gender', true) ?: 'male';
            } else {
                $row->other_name = __('عضو محذوف', 'saint-porphyrius');
                $row->other_initial = '?';
                $row->other_gender = 'male';
            }
        }

        return $results;
    }

    /**
     * Get sharing statistics for a user
     */
    public function get_share_stats($user_id) {
        global $wpdb;

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN sender_id = %d THEN points ELSE 0 END), 0) as total_sent,
                COALESCE(SUM(CASE WHEN recipient_id = %d THEN points ELSE 0 END), 0) as total_received,
                COALESCE(SUM(CASE WHEN sender_id = %d THEN 1 ELSE 0 END), 0) as sent_count,
                COALESCE(SUM(CASE WHEN recipient_id = %d THEN 1 ELSE 0 END), 0) as received_count
             FROM {$this->table_name}
             WHERE sender_id = %d OR recipient_id = %d",
            $user_id, $user_id, $user_id, $user_id, $user_id, $user_id
        ));

        if (!$stats) {
            return (object) array(
                'total_sent' => 0,
                'total_received' => 0,
                'sent_count' => 0,
                'received_count' => 0,
            );
        }

        return $stats;
    }
}
