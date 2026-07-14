<?php
/**
 * Saint Porphyrius - Cache
 *
 * A deliberately small wrapper over WordPress transients.
 *
 * Why not wp_cache_* in front of transients?
 * ------------------------------------------
 * Because that is what transients already are. get_transient()/set_transient() route
 * through the persistent object cache when one is installed, and fall back to the
 * options table when one is not -- which is exactly the portability we want, for free.
 * Layering wp_cache_* on top would store every value twice, and a wp_cache_delete()
 * would leave the transient copy alive to be served after it should have died.
 *
 * So: static memo (this request) -> transient (across requests) -> compute.
 *
 * On this site there is no object cache drop-in today, so the transient tier lands in
 * wp_options. That still turns a 26ms leaderboard aggregate into a single indexed row
 * read. The day the host enables Redis, the same code gets faster with no change --
 * which is the point.
 *
 * Invalidation is by named key and an explicit delete, NOT by bumping a version number.
 * A version bump orphans the old transient rows, and WordPress only garbage-collects
 * transients lazily -- so a frequently-invalidated group would slowly bloat wp_options,
 * which is the exact problem we are trying to relieve elsewhere.
 *
 * @package Saint_Porphyrius
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Cache {

    const PREFIX = 'sp_c_';

    /** Values already resolved in this request. */
    private static $memo = array();

    /** Keys already deleted in this request -- see delete_once(). */
    private static $deleted = array();

    /** Counters for the Performance tab. */
    private static $hits   = 0;
    private static $misses = 0;
    private static $writes = 0;

    /**
     * Read a cached value, or compute and store it.
     *
     * $callback must return something other than false; false is indistinguishable from
     * a transient miss, so a value of false would be recomputed every time.
     */
    public static function remember($key, $ttl, callable $callback) {
        if (array_key_exists($key, self::$memo)) {
            self::$hits++;

            return self::$memo[$key];
        }

        $value = get_transient(self::PREFIX . $key);

        if ($value !== false) {
            self::$hits++;
            self::$memo[$key] = $value;

            return $value;
        }

        self::$misses++;

        $value = call_user_func($callback);

        self::set($key, $value, $ttl);

        return $value;
    }

    public static function get($key) {
        if (array_key_exists($key, self::$memo)) {
            self::$hits++;

            return self::$memo[$key];
        }

        $value = get_transient(self::PREFIX . $key);

        if ($value === false) {
            self::$misses++;

            return false;
        }

        self::$hits++;
        self::$memo[$key] = $value;

        return $value;
    }

    public static function set($key, $value, $ttl) {
        self::$writes++;
        self::$memo[$key] = $value;
        unset(self::$deleted[$key]);

        set_transient(self::PREFIX . $key, $value, $ttl);
    }

    public static function delete($key) {
        unset(self::$memo[$key]);

        delete_transient(self::PREFIX . $key);
    }

    /**
     * Delete a key at most once per request.
     *
     * SP_Points::add() invalidates the standings on every award, and completing an event
     * calls it once per member. With no object cache each delete_transient() is a real
     * DELETE query, so 200 awards would mean 200 pointless DELETEs of a key that is
     * already gone. The first one is enough.
     */
    public static function delete_once($key) {
        if (isset(self::$deleted[$key])) {
            return;
        }

        self::$deleted[$key] = true;
        self::delete($key);
    }

    /**
     * Hit/miss counters for this request. Read by SP_Perf at shutdown.
     */
    public static function stats() {
        return array(
            'hits'   => self::$hits,
            'misses' => self::$misses,
            'writes' => self::$writes,
        );
    }

    /**
     * Drop everything this request has memoised. Used by tests and by the
     * "flush cache" action; does not touch the stored transients.
     */
    public static function flush_memo() {
        self::$memo    = array();
        self::$deleted = array();
    }
}
