<?php
/**
 * Saint Porphyrius - Performance instrumentation
 *
 * Samples real requests so the Performance tab can show what the app actually
 * costs, rather than what we hope it costs.
 *
 * Two rules keep the instrument from becoming the problem it measures:
 *
 *   1. SAVEQUERIES is never enabled. It retains every query and a backtrace for
 *      each, which is far more expensive than the thing being measured. We take
 *      $wpdb->num_queries, which WordPress counts anyway, for free.
 *   2. Only a fraction of requests are recorded (SAMPLE_RATE), and only app
 *      routes and sp_* AJAX -- never cron, never wp-admin, never REST.
 *
 * Sampling and the `forced` column
 * --------------------------------
 * A request is recorded if it wins the dice OR if it was slow (SLOW_MS). Always
 * keeping the slow ones means a rare stall can't hide from us -- but it also means
 * the stored rows are NOT a uniform sample of traffic, and computing a median over
 * all of them would overstate it. So slow-path captures are tagged forced = 1, and
 * the percentile figures are computed over forced = 0 only, which IS uniform.
 * The slow-routes table deliberately reads both.
 *
 * @package Saint_Porphyrius
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Perf {

    const TABLE          = 'sp_perf_samples';
    const OPT_ENABLED    = 'sp_perf_enabled';
    const OPT_BASELINE   = 'sp_perf_baseline';
    const SAMPLE_RATE    = 10;    // record 1 request in N
    const SLOW_MS        = 800;   // always record a request slower than this
    const RETAIN_DAYS    = 14;
    const MAX_ROWS       = 20000;

    private static $instance = null;

    private $table_name;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . self::TABLE;
    }

    public function init() {
        add_action('shutdown', array($this, 'maybe_record'), 999);
        add_action('sp_perf_prune', array($this, 'prune'));
    }

    // ---------------------------------------------------------------- sampling

    /**
     * Which route is this request? Returns '' for anything we don't sample.
     */
    private function current_route() {
        if (wp_doing_cron() || (defined('WP_CLI') && WP_CLI)) {
            return '';
        }

        if (wp_doing_ajax()) {
            $action = isset($_REQUEST['action']) ? sanitize_key($_REQUEST['action']) : '';

            // Only our own endpoints. Core's heartbeat et al. are not our problem.
            if (strpos($action, 'sp_') !== 0) {
                return '';
            }

            return substr('ajax:' . $action, 0, 64);
        }

        $app = get_query_var('sp_app');

        return $app ? substr((string) $app, 0, 64) : '';
    }

    public function maybe_record() {
        // Route first: this is the cheapest possible bail-out, and it means wp-admin,
        // REST and cron requests leave this method without touching anything else.
        $route = $this->current_route();
        if ($route === '') {
            return;
        }

        if (!get_option(self::OPT_ENABLED, 1)) {
            return;
        }

        $ms = $this->elapsed_ms();

        // Slow requests are always kept; the rest are a 1-in-N sample. See the
        // class docblock for why the distinction is recorded rather than lost.
        $is_slow = ($ms >= self::SLOW_MS);
        if (!$is_slow && mt_rand(1, self::SAMPLE_RATE) !== 1) {
            return;
        }

        if (!$this->table_ready()) {
            return;
        }

        // This runs on `shutdown`, which fires *after* wp_send_json_*() has already
        // written the response body and called die(). Anything printed from here --
        // a wpdb error block, a warning, a fatal -- lands after valid JSON and makes
        // the client's JSON.parse fail, which the app then reports as a connection
        // error. Since a slow request is always sampled, and the lesson-prep submit
        // was always slow, that landed on exactly the requests already in trouble.
        //
        // So: nothing in here may reach the output stream, and nothing in here may
        // escape. The sample is diagnostics; it is never worth a broken response.
        global $wpdb;

        $suppress = $wpdb->suppress_errors(true);
        $show = $wpdb->show_errors(false);

        try {
            $this->insert_sample($route, $ms, $is_slow);
        } catch (Exception $e) {
            // Swallowed on purpose.
        } catch (Error $e) {
            // Swallowed on purpose (PHP 7+ fatals are Throwable).
        }

        $wpdb->suppress_errors($suppress);
        $wpdb->show_errors($show);
    }

    private function elapsed_ms() {
        $start = isset($_SERVER['REQUEST_TIME_FLOAT']) ? (float) $_SERVER['REQUEST_TIME_FLOAT'] : 0;

        if ($start <= 0) {
            return 0;
        }

        // Measured from REQUEST_TIME_FLOAT, so bootstrap is included -- that is the
        // number the member actually waits for, not just our slice of it.
        return (int) round((microtime(true) - $start) * 1000);
    }

    private function insert_sample($route, $ms, $is_slow) {
        global $wpdb;

        $stats = class_exists('SP_Cache') ? SP_Cache::stats() : array('hits' => 0, 'misses' => 0);

        $wpdb->insert(
            $this->table_name,
            array(
                'route'          => $route,
                'queries'        => (int) $wpdb->num_queries,
                'ms'             => $ms,
                'mem'            => (int) memory_get_peak_usage(true),
                'cache_hits'     => (int) $stats['hits'],
                'cache_misses'   => (int) $stats['misses'],
                'forced'         => $is_slow ? 1 : 0,
                'plugin_version' => defined('SP_PLUGIN_VERSION') ? SP_PLUGIN_VERSION : '',
                'created_at'     => current_time('mysql'),
            ),
            array('%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s')
        );
    }

    /**
     * The table lands via a migration, and migrations only run once an admin hits
     * a page. Until then a member's request would be inserting into nothing, so
     * check first. Memoized -- at most one extra query, on sampled requests only.
     */
    private function table_ready() {
        static $ready = null;

        if ($ready === null) {
            global $wpdb;
            $ready = (bool) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $this->table_name));
        }

        return $ready;
    }

    // ---------------------------------------------------------------- retention

    public function prune() {
        global $wpdb;

        if (!$this->table_ready()) {
            return;
        }

        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            self::RETAIN_DAYS
        ));

        // Hard cap as well as a time window: a traffic spike could blow past the
        // row budget long before anything is old enough to expire.
        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");

        if ($count > self::MAX_ROWS) {
            $cutoff = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$this->table_name} ORDER BY id DESC LIMIT %d, 1",
                self::MAX_ROWS
            ));

            if ($cutoff) {
                $wpdb->query($wpdb->prepare("DELETE FROM {$this->table_name} WHERE id <= %d", $cutoff));
            }
        }
    }

    // ---------------------------------------------------------------- reporting

    /**
     * Is a persistent object cache actually in play? This is the question the
     * admin cannot otherwise answer, and the whole reason the tab exists.
     */
    public function get_object_cache_status() {
        $dropin = WP_CONTENT_DIR . '/object-cache.php';

        $status = array(
            'persistent'      => function_exists('wp_using_ext_object_cache') && wp_using_ext_object_cache(),
            'dropin_present'  => file_exists($dropin),
            'backend'         => __('None (WordPress default, per-request only)', 'saint-porphyrius'),
            'redis_ext'       => extension_loaded('redis'),
            'memcached_ext'   => extension_loaded('memcached') || extension_loaded('memcache'),
            'opcache'         => function_exists('opcache_get_status'),
        );

        if ($status['persistent']) {
            global $wp_object_cache;
            $status['backend'] = is_object($wp_object_cache)
                ? get_class($wp_object_cache)
                : __('Unknown drop-in', 'saint-porphyrius');
        }

        return $status;
    }

    /**
     * Percentiles over the unbiased (forced = 0) rows. MySQL 5.7 has no percentile
     * function and the row budget is small, so this is done in PHP.
     */
    public function get_summary($days = 7) {
        global $wpdb;

        $empty = array(
            'samples'    => 0,
            'queries_p50' => 0,
            'ms_p50'     => 0,
            'ms_p95'     => 0,
            'mem_p95'    => 0,
            'hit_rate'   => null,
        );

        if (!$this->table_ready()) {
            return $empty;
        }

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT queries, ms, mem, cache_hits, cache_misses
             FROM {$this->table_name}
             WHERE forced = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             ORDER BY id DESC
             LIMIT 5000",
            $days
        ));

        if (empty($rows)) {
            return $empty;
        }

        $hits   = 0;
        $misses = 0;
        foreach ($rows as $row) {
            $hits   += (int) $row->cache_hits;
            $misses += (int) $row->cache_misses;
        }
        $lookups = $hits + $misses;

        return array(
            'samples'     => count($rows),
            'queries_p50' => $this->percentile(wp_list_pluck($rows, 'queries'), 50),
            'ms_p50'      => $this->percentile(wp_list_pluck($rows, 'ms'), 50),
            'ms_p95'      => $this->percentile(wp_list_pluck($rows, 'ms'), 95),
            'mem_p95'     => $this->percentile(wp_list_pluck($rows, 'mem'), 95),
            'hit_rate'    => $lookups > 0 ? round(($hits / $lookups) * 100, 1) : null,
        );
    }

    /**
     * Per-route figures, worst first. Reads forced rows too: the point of this
     * table is to find the stalls, not to describe the typical request.
     */
    public function get_routes($days = 7) {
        global $wpdb;

        if (!$this->table_ready()) {
            return array();
        }

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT route, queries, ms
             FROM {$this->table_name}
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             ORDER BY id DESC
             LIMIT 5000",
            $days
        ));

        $by_route = array();
        foreach ($rows as $row) {
            $by_route[$row->route]['ms'][]      = (int) $row->ms;
            $by_route[$row->route]['queries'][] = (int) $row->queries;
        }

        $out = array();
        foreach ($by_route as $route => $data) {
            $out[] = array(
                'route'       => $route,
                'samples'     => count($data['ms']),
                'ms_p50'      => $this->percentile($data['ms'], 50),
                'ms_p95'      => $this->percentile($data['ms'], 95),
                'queries_p50' => $this->percentile($data['queries'], 50),
            );
        }

        usort($out, function ($a, $b) {
            return $b['ms_p95'] - $a['ms_p95'];
        });

        return $out;
    }

    private function percentile($values, $p) {
        $values = array_map('intval', (array) $values);

        if (empty($values)) {
            return 0;
        }

        sort($values);
        $index = (int) floor(($p / 100) * (count($values) - 1));

        return (int) $values[$index];
    }

    // ---------------------------------------------------------------- baseline

    public function record_baseline() {
        $summary = $this->get_summary(self::RETAIN_DAYS);

        if ($summary['samples'] < 1) {
            return new WP_Error('no_samples', __('No samples recorded yet. Use the app for a while, then try again.', 'saint-porphyrius'));
        }

        $summary['recorded_at'] = current_time('mysql');
        $summary['version']     = defined('SP_PLUGIN_VERSION') ? SP_PLUGIN_VERSION : '';

        update_option(self::OPT_BASELINE, $summary, false);

        return $summary;
    }

    public function get_baseline() {
        $baseline = get_option(self::OPT_BASELINE, array());

        return is_array($baseline) && !empty($baseline['samples']) ? $baseline : null;
    }

    // ---------------------------------------------------------------- autoload

    /**
     * Autoloaded options are read from the DB on *every* request, so a big one is a
     * tax on the whole site. Report rather than assume: show the real bytes.
     */
    public function get_autoload_report() {
        global $wpdb;

        $total = (int) $wpdb->get_var(
            "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload = 'yes'"
        );

        $biggest = $wpdb->get_results(
            "SELECT option_name, LENGTH(option_value) AS bytes, autoload
             FROM {$wpdb->options}
             WHERE autoload = 'yes'
             ORDER BY bytes DESC
             LIMIT 10"
        );

        return array(
            'total_bytes' => $total,
            'biggest'     => $biggest ? $biggest : array(),
        );
    }

    // ---------------------------------------------------------------- object cache drop-in

    private function dropin_path() {
        return WP_CONTENT_DIR . '/object-cache.php';
    }

    private function dropin_source() {
        return SP_PLUGIN_DIR . 'includes/object-cache-apcu.php';
    }

    /**
     * Is APCu usable on this server at all? Everything else here depends on it.
     */
    public function apcu_available() {
        return function_exists('apcu_enabled') && apcu_enabled();
    }

    /**
     * What is currently sitting at wp-content/object-cache.php?
     *
     *   'none'    - nothing installed
     *   'ours'    - our APCu drop-in
     *   'foreign' - somebody else's (Redis, W3TC, LiteSpeed...). NEVER touch it.
     */
    public function dropin_status() {
        $path = $this->dropin_path();

        if (!file_exists($path)) {
            return 'none';
        }

        $contents = file_get_contents($path);

        return (is_string($contents) && strpos($contents, 'SP_APCU_DROPIN') !== false)
            ? 'ours'
            : 'foreign';
    }

    /**
     * Install the drop-in.
     *
     * Refuses outright if another cache backend already owns object-cache.php. Overwriting
     * a Redis drop-in would silently downgrade the whole site, and it is not ours to remove.
     */
    public function install_dropin() {
        if (!$this->apcu_available()) {
            return new WP_Error('no_apcu', __('APCu is not available on this server, so there is nothing to install. Ask your host to enable the APCu PHP extension.', 'saint-porphyrius'));
        }

        if ($this->dropin_status() === 'foreign') {
            return new WP_Error('foreign_dropin', __('Another caching plugin already owns wp-content/object-cache.php. Refusing to overwrite it — remove that plugin first if you want to use APCu instead.', 'saint-porphyrius'));
        }

        $source = $this->dropin_source();

        if (!is_readable($source)) {
            return new WP_Error('no_source', __('The drop-in source file is missing from the plugin.', 'saint-porphyrius'));
        }

        if (!is_writable(WP_CONTENT_DIR)) {
            return new WP_Error('not_writable', sprintf(
                __('%s is not writable, so the drop-in cannot be installed. Copy includes/object-cache-apcu.php to wp-content/object-cache.php by hand.', 'saint-porphyrius'),
                WP_CONTENT_DIR
            ));
        }

        if (!copy($source, $this->dropin_path())) {
            return new WP_Error('copy_failed', __('Could not write wp-content/object-cache.php.', 'saint-porphyrius'));
        }

        return true;
    }

    /**
     * Remove the drop-in -- but only ever our own.
     */
    public function remove_dropin() {
        if ($this->dropin_status() !== 'ours') {
            return new WP_Error('not_ours', __('The installed object-cache.php does not belong to Saint Porphyrius, so it will not be removed.', 'saint-porphyrius'));
        }

        if (!unlink($this->dropin_path())) {
            return new WP_Error('unlink_failed', __('Could not remove wp-content/object-cache.php.', 'saint-porphyrius'));
        }

        return true;
    }

    /**
     * Live APCu numbers: how full is it, and is it actually being hit?
     */
    public function apcu_info() {
        $info = array(
            'available'  => $this->apcu_available(),
            'cli'        => (php_sapi_name() === 'cli'),
            'hits'       => 0,
            'misses'     => 0,
            'hit_rate'   => null,
            'entries'    => 0,
            'used'       => 0,
            'total'      => 0,
            'used_pct'   => 0,
            'full_count' => 0,
        );

        if (!$info['available'] || !function_exists('apcu_cache_info')) {
            return $info;
        }

        // The `true` skips the (potentially huge) per-entry list; we only want the totals.
        $cache = @apcu_cache_info(true);
        $sma   = function_exists('apcu_sma_info') ? @apcu_sma_info(true) : array();

        if (is_array($cache)) {
            $info['hits']       = isset($cache['num_hits']) ? (int) $cache['num_hits'] : 0;
            $info['misses']     = isset($cache['num_misses']) ? (int) $cache['num_misses'] : 0;
            $info['entries']    = isset($cache['num_entries']) ? (int) $cache['num_entries'] : 0;
            $info['used']       = isset($cache['mem_size']) ? (int) $cache['mem_size'] : 0;
            // A rising expunge count means APCu keeps running out of room and throwing
            // things away -- the cache is too small to be doing its job.
            $info['full_count'] = isset($cache['expunges']) ? (int) $cache['expunges'] : 0;

            $lookups = $info['hits'] + $info['misses'];
            if ($lookups > 0) {
                $info['hit_rate'] = round(($info['hits'] / $lookups) * 100, 1);
            }
        }

        if (is_array($sma) && isset($sma['seg_size'], $sma['num_seg'])) {
            $info['total'] = (int) $sma['seg_size'] * (int) $sma['num_seg'];

            if ($info['total'] > 0) {
                $info['used_pct'] = round(($info['used'] / $info['total']) * 100, 1);
            }
        }

        return $info;
    }

    // ---------------------------------------------------------------- push queue

    /**
     * Health of the outgoing push queue. If pushes stop arriving, this is where you
     * find out why: either nothing is draining (cron is dead) or jobs are failing.
     */
    public function get_push_queue_status() {
        global $wpdb;

        $table = $wpdb->prefix . 'sp_push_queue';

        $status = array(
            'available'      => false,
            'pending'        => 0,
            'sending'        => 0,
            'failed'         => 0,
            'sent_24h'       => 0,
            'oldest_pending' => null,
            'cron_disabled'  => (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON),
            'next_run'       => wp_next_scheduled('sp_drain_push_queue'),
        );

        if (!$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table))) {
            return $status;
        }

        $status['available'] = true;

        $counts = $wpdb->get_results("SELECT status, COUNT(*) AS n FROM $table GROUP BY status");
        foreach ($counts as $row) {
            if (isset($status[$row->status])) {
                $status[$row->status] = (int) $row->n;
            }
        }

        $status['sent_24h'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $table WHERE status = 'sent' AND sent_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)"
        );

        $status['oldest_pending'] = $wpdb->get_var(
            "SELECT created_at FROM $table WHERE status = 'pending' ORDER BY id ASC LIMIT 1"
        );

        return $status;
    }

    // ---------------------------------------------------------------- indexes

    /**
     * Indexes the plugin's hot queries want. Reported here so the tab can show
     * what is missing; they are created by migration, not from this screen.
     */
    public function get_expected_indexes() {
        global $wpdb;

        return array(
            array('table' => $wpdb->prefix . 'sp_points_log',      'index' => 'user_points',   'why' => __('Leaderboard SUM(points) GROUP BY user_id', 'saint-porphyrius')),
            array('table' => $wpdb->prefix . 'sp_points_log',      'index' => 'user_created',  'why' => __('Points history sort', 'saint-porphyrius')),
            array('table' => $wpdb->prefix . 'sp_attendance',      'index' => 'user_status',   'why' => __('Per-member attendance stats', 'saint-porphyrius')),
            array('table' => $wpdb->prefix . 'sp_pending_users',   'index' => 'status',        'why' => __('Pending-approval counts', 'saint-porphyrius')),
            array('table' => $wpdb->prefix . 'sp_bus_templates',   'index' => 'is_active',     'why' => __('Active bus templates', 'saint-porphyrius')),
            array('table' => $wpdb->prefix . 'sp_quiz_categories', 'index' => 'active_sort',   'why' => __('Active quiz categories', 'saint-porphyrius')),
        );
    }

    public function get_index_status() {
        global $wpdb;

        $out = array();

        foreach ($this->get_expected_indexes() as $expected) {
            $exists = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND INDEX_NAME = %s",
                DB_NAME,
                $expected['table'],
                $expected['index']
            ));

            $expected['present'] = ($exists > 0);
            $out[] = $expected;
        }

        return $out;
    }

    // ---------------------------------------------------------------- benchmark

    /**
     * Times the known-expensive reads right now, in-process. Traffic sampling needs
     * days to say anything; this answers "what do these cost today" immediately.
     */
    public function run_benchmark() {
        $results = array();

        $results[] = $this->time_it(__('Leaderboard (top 100)', 'saint-porphyrius'), function () {
            SP_Points::get_instance()->get_leaderboard(100);
        });

        $results[] = $this->time_it(__('Points summary (whole log)', 'saint-porphyrius'), function () {
            SP_Points::get_instance()->get_summary_stats();
        });

        $results[] = $this->time_it(__('User rank', 'saint-porphyrius'), function () {
            SP_Point_Sharing::get_instance()->get_user_rank(get_current_user_id());
        });

        $results[] = $this->time_it(__('Custom texts (x32, as the dashboard does)', 'saint-porphyrius'), function () {
            for ($i = 0; $i < 32; $i++) {
                SP_Custom_Texts::get_instance()->get_settings();
            }
        });

        $results[] = $this->time_it(__('Birthday members', 'saint-porphyrius'), function () {
            SP_Gamification::get_instance()->get_birthday_members();
        });

        return $results;
    }

    private function time_it($label, callable $fn) {
        global $wpdb;

        $q0 = $wpdb->num_queries;
        $t0 = microtime(true);

        // Throwable, not Exception: a TypeError in a benchmarked call would otherwise
        // take the whole settings page down with it.
        try {
            $fn();
            $error = '';
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        return array(
            'label'   => $label,
            'ms'      => round((microtime(true) - $t0) * 1000, 1),
            'queries' => $wpdb->num_queries - $q0,
            'error'   => $error,
        );
    }
}
