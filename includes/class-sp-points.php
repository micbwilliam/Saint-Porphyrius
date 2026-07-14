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
     *
     * @param string|null $dedupe_key Stable identifier for this award. When given, the
     *                                unique index on sp_points_log.dedupe_key guarantees
     *                                the award lands at most once, however many times this
     *                                runs. Pass null only for awards that are legitimately
     *                                repeatable, such as manual adjustments.
     */
    public function add($user_id, $points, $type = 'reward', $event_id = null, $reason = '', $dedupe_key = null) {
        global $wpdb;

        $user_id = (int) $user_id;
        $points  = (int) $points;

        // Determine type based on points if not specified properly
        if ($points < 0 && $type === 'reward') {
            $type = 'penalty';
        }

        $dedupe_key = ($dedupe_key === null || $dedupe_key === '') ? null : substr((string) $dedupe_key, 0, 64);

        // Serialize concurrent awards for this user. Without this, two requests read the
        // same balance, both insert, and one award is lost from balance_after while the
        // log keeps two rows.
        $wpdb->query('START TRANSACTION');

        if ($dedupe_key !== null) {
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT id, points, balance_after FROM {$this->table_name} WHERE dedupe_key = %s FOR UPDATE",
                $dedupe_key
            ));

            if ($existing) {
                $wpdb->query('COMMIT');
                return array(
                    'success'     => true,
                    'duplicate'   => true,
                    'points'      => 0,
                    'new_balance' => (int) $existing->balance_after,
                );
            }
        }

        // The log is the source of truth; the user meta is only a cache and may be stale.
        $current_balance = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(points), 0) FROM {$this->table_name} WHERE user_id = %d FOR UPDATE",
            $user_id
        ));
        $new_balance = $current_balance + $points;

        $was_suppressed = $wpdb->suppress_errors(true);
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
                'dedupe_key' => $dedupe_key,
            ),
            array('%d', '%d', '%d', '%s', '%s', '%d', '%d', '%s')
        );
        $insert_error = $wpdb->last_error;
        $wpdb->suppress_errors($was_suppressed);

        if ($result === false) {
            $wpdb->query('ROLLBACK');

            // Lost a race to a concurrent identical award — it is already in the log.
            if ($dedupe_key !== null && stripos($insert_error, 'duplicate entry') !== false) {
                return array(
                    'success'     => true,
                    'duplicate'   => true,
                    'points'      => 0,
                    'new_balance' => $this->recalculate_balance($user_id),
                );
            }

            return new WP_Error('db_error', __('Failed to add points.', 'saint-porphyrius') . ' ' . $insert_error);
        }

        $wpdb->query('COMMIT');

        // The points log just changed, so every leaderboard and every rank derived from it
        // is now wrong. SP_Points is the ONLY writer of this table anywhere in the plugin,
        // so invalidating here is complete -- the standings cache cannot go stale behind
        // our back. delete_once() keeps a 200-member event award to a single DELETE.
        $this->flush_standings();

        // Update user meta for quick access
        update_user_meta($user_id, 'sp_points_balance', $new_balance);

        // Notify user about points change (bell + push)
        SP_Notifications::get_instance()->notify_points_change($user_id, $points, $new_balance, $reason);

        return array(
            'success' => true,
            'duplicate' => false,
            'points' => $points,
            'new_balance' => $new_balance,
        );
    }

    /**
     * Build a dedupe key from its parts, e.g. make_dedupe_key('attendance', $event_id, $user_id).
     * Over-long keys are hashed so they always fit the 64-char unique index.
     */
    public static function make_dedupe_key(...$parts) {
        $key = implode(':', array_map('strval', $parts));

        return strlen($key) <= 64 ? $key : substr((string) $parts[0], 0, 23) . ':' . md5($key);
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
     * Get user IDs that must never appear in the leaderboard / ranking.
     *
     * Admins are an invisible, unlimited source of points: they can grant
     * birthday/gift points to members without holding a balance, and they
     * must never be calculated into any ranking. Excluding them here keeps
     * every rank computation (leaderboard, social profile, share preview)
     * consistent.
     *
     * @return int[] List of user IDs to exclude from rankings.
     */
    public function get_unranked_user_ids() {
        static $ids = null;

        if ($ids === null) {
            $admins = get_users(array(
                'role__in' => array('administrator', 'sp_church_admin'),
                'fields'   => 'ID',
            ));
            $ids = array_map('intval', $admins);

            /**
             * Filter the list of user IDs excluded from all point rankings.
             *
             * @param int[] $ids User IDs to exclude.
             */
            $ids = array_values(array_unique(array_map('intval', apply_filters('sp_unranked_user_ids', $ids))));
        }

        return $ids;
    }

    /**
     * Build a "user_id NOT IN (...)" SQL fragment for the unranked users.
     * Returns an empty string when there is nothing to exclude.
     */
    private function get_unranked_exclusion_sql() {
        $excluded = $this->get_unranked_user_ids();

        if (empty($excluded)) {
            return '';
        }

        return ' AND user_id NOT IN (' . implode(',', array_map('intval', $excluded)) . ')';
    }

    // ================================================================== standings
    //
    // One cached snapshot behind every leaderboard and every rank in the app.
    //
    // Before this, each of those was its own full aggregate of the points log:
    // GROUP BY user_id SUM(points) -- a temp table and a filesort, ~26ms on a 60k-row
    // log even with the covering index. The member dashboard ran one on every single
    // load just to work out one integer (the member's own rank), and the share-points
    // preview ran two.
    //
    // Building it costs 3 queries; reading rank or a leaderboard slice out of it costs
    // none. Correctness does NOT rest on the TTL: SP_Points is the only writer of
    // sp_points_log anywhere in the plugin, and every write invalidates this. The TTL is
    // only a safety net for a cache that somehow outlives its invalidation.

    const STANDINGS_TTL = 900; // 15 minutes

    /**
     * Cache key for a period's standings. Bound to the unranked list, so promoting a
     * member to admin (which removes them from the rankings) cannot serve a stale board.
     */
    private function standings_key($period) {
        $excluded = $this->get_unranked_user_ids();

        return 'standings_' . $period . '_' . substr(md5(implode(',', $excluded)), 0, 8);
    }

    /**
     * The whole ranked field for a period, keyed by user id:
     *
     *   [ user_id => ['user_id','total_points','rank','display_name','name_ar'] ]
     *
     * Ordered best-first. Includes members on zero and negative totals -- the leaderboard
     * filters those out when it slices, which preserves the old behaviour, but rank and
     * the community page need everyone.
     */
    public function get_standings($period = 'all') {
        $self = $this;

        return SP_Cache::remember($this->standings_key($period), self::STANDINGS_TTL, function () use ($self, $period) {
            return $self->build_standings($period);
        });
    }

    /**
     * Public only so the cache callback above can reach it on PHP 7.4 (no $this binding
     * games). Treat it as private -- go through get_standings().
     */
    public function build_standings($period) {
        global $wpdb;

        $where = '1=1';

        if ($period === 'month') {
            $where = "created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        } elseif ($period === 'year') {
            $where = "created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        }

        // Admins are an invisible, unlimited points source — never rank them.
        $where .= $this->get_unranked_exclusion_sql();

        // No HAVING here: the leaderboard's "> 0" is applied when slicing, so that rank
        // and the community page can still see members on zero or negative totals.
        $rows = $wpdb->get_results(
            "SELECT user_id, SUM(points) AS total_points
             FROM {$this->table_name}
             WHERE $where
             GROUP BY user_id
             ORDER BY total_points DESC"
        );

        if (empty($rows)) {
            return array();
        }

        // One round-trip for every name. Previously this was get_user_by + get_user_meta
        // per row -- 200 queries to decorate a 100-row board.
        $user_ids = wp_list_pluck($rows, 'user_id');
        cache_users($user_ids);

        $standings = array();
        $rank      = 0;
        $seen      = 0;
        $last      = null;

        foreach ($rows as $row) {
            $total = (int) $row->total_points;
            $seen++;

            // Standard competition ranking: equal totals share a rank, and the next
            // distinct total skips ahead. Two members on 300 are both 1st; the next is 3rd.
            if ($total !== $last) {
                $rank = $seen;
                $last = $total;
            }

            $user_id = (int) $row->user_id;
            $user    = get_user_by('id', $user_id);

            $standings[$user_id] = array(
                'user_id'      => $user_id,
                'total_points' => $total,
                'rank'         => $rank,
                'display_name' => $user ? $user->display_name : '',
                'name_ar'      => get_user_meta($user_id, 'sp_name_ar', true),
            );
        }

        return $standings;
    }

    /**
     * Throw away the cached standings. Called by every write to the points log.
     */
    public function flush_standings() {
        foreach (array('all', 'month', 'year') as $period) {
            SP_Cache::delete_once($this->standings_key($period));
        }
    }

    /**
     * A member's rank, or 0 if they have no points at all.
     *
     * Previously the dashboard fetched a 100-row leaderboard and looked for itself in a
     * PHP loop -- so every member outside the top 100 was shown rank 0. This answers for
     * everyone, and costs no query when the standings are warm.
     */
    public function get_rank($user_id, $period = 'all') {
        $standings = $this->get_standings($period);
        $user_id   = (int) $user_id;

        return isset($standings[$user_id]) ? (int) $standings[$user_id]['rank'] : 0;
    }

    /**
     * What a member's rank would become after a points change, without writing anything.
     * Used by the share-points preview, which used to run two full aggregations per keystroke.
     */
    public function get_projected_rank($user_id, $point_change, $period = 'all') {
        $standings = $this->get_standings($period);
        $user_id   = (int) $user_id;

        $current   = isset($standings[$user_id]) ? (int) $standings[$user_id]['total_points'] : 0;
        $projected = $current + (int) $point_change;

        $higher = 0;
        foreach ($standings as $id => $entry) {
            if ($id !== $user_id && (int) $entry['total_points'] > $projected) {
                $higher++;
            }
        }

        return $higher + 1;
    }

    /**
     * Get leaderboard.
     *
     * Now a slice of the cached standings rather than its own aggregate. Returns objects
     * with user_id / total_points / display_name / name_ar, exactly as before, so every
     * existing template keeps working.
     */
    public function get_leaderboard($limit = 10, $period = 'all') {
        $standings = $this->get_standings($period);

        $results = array();

        foreach ($standings as $entry) {
            // Preserve the old HAVING total_points > 0.
            if ($entry['total_points'] <= 0) {
                continue;
            }

            $results[] = (object) $entry;

            if (count($results) >= $limit) {
                break;
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
    public function adjust($user_id, $points, $reason, $dedupe_key = null) {
        $type = $points >= 0 ? 'adjustment' : 'penalty';
        return $this->add($user_id, $points, $type, null, $reason, $dedupe_key);
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
            'appeal_approved' => array(
                'label_en' => 'Appeal Approved',
                'label_ar' => 'طلب مقبول',
                'color' => '#0EA5E9', // sky blue
            ),
            'appeal_penalty' => array(
                'label_en' => 'Appeal Denied (Penalty)',
                'label_ar' => 'رفض طلب (خصم)',
                'color' => '#BE123C', // deep red
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

        // A repair path: the balance we believed was wrong, so anything derived from it is
        // suspect too.
        $this->flush_standings();

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

        $this->flush_standings();

        return count($members);
    }
}
