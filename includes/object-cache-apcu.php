<?php
/**
 * Saint Porphyrius — APCu object cache drop-in
 *
 * Installed to wp-content/object-cache.php from Settings → Performance. Do not edit it
 * there: that copy is overwritten whenever the drop-in is re-installed. The source of
 * truth is includes/object-cache-apcu.php inside the plugin.
 *
 * It is intentionally SELF-CONTAINED. WordPress loads a drop-in before plugins, so this
 * file cannot rely on anything from Saint Porphyrius existing -- and it must keep working
 * if the plugin is ever deactivated or removed.
 *
 * What it does
 * ------------
 * WordPress caches options, users, user-meta, posts and terms in memory for the life of a
 * request, then throws it all away. Without a persistent backend it re-reads the same rows
 * from MySQL on every single request. APCu keeps that memory alive between requests, in
 * shared memory belonging to the PHP process pool, so the second request onwards is served
 * without touching the database.
 *
 * Why APCu rather than Redis: it is shared memory inside PHP itself. There is no server to
 * install, no port, no password, and it is available on most shared hosting. The trade-off
 * is below.
 *
 * SAFETY
 * ------
 * If APCu is missing, disabled, or out of memory, every method here quietly falls back to a
 * per-request array -- which is precisely WordPress's own default behaviour. The site keeps
 * working; it is just no longer faster. A cache must never be the reason a site is down.
 *
 * THE ONE REAL CAVEAT: WP-CLI
 * ---------------------------
 * APCu memory is per-process-pool. A PHP-CLI process (WP-CLI) gets its OWN empty segment and
 * cannot see -- or invalidate -- what the web server has cached. So if you change data with
 * WP-CLI, the web server can keep serving the old value.
 *
 * Two things bound that:
 *   1. Entries WordPress asks to store "forever" are capped at WP_APCU_MAX_TTL (12h by
 *      default) instead. An expired entry is not wrong data -- WordPress simply re-reads it
 *      from MySQL -- so this is a pure safety valve, never a correctness risk.
 *   2. Settings → Performance has a Flush button.
 *
 * If you start doing real work through WP-CLI, flush after, or move to Redis, where this
 * problem does not exist.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cap on entries WordPress wants to keep indefinitely. See "THE ONE REAL CAVEAT" above.
 *
 * HOUR_IN_SECONDS is defined long before WordPress loads a drop-in, but this file is loaded
 * earlier than almost anything else, so it does not assume that and spells the number out.
 */
if (!defined('WP_APCU_MAX_TTL')) {
    define('WP_APCU_MAX_TTL', 12 * 3600);
}

/**
 * Marker so the plugin can recognise its own drop-in and refuse to overwrite
 * somebody else's (a Redis or W3TC drop-in must never be clobbered).
 */
define('SP_APCU_DROPIN', '1.0.0');

// ---------------------------------------------------------------------------------------
// The wp_cache_* API WordPress expects a drop-in to provide.
// ---------------------------------------------------------------------------------------

function wp_cache_init() {
    $GLOBALS['wp_object_cache'] = new WP_Object_Cache();
}

function wp_cache_add($key, $data, $group = '', $expire = 0) {
    return $GLOBALS['wp_object_cache']->add($key, $data, $group, (int) $expire);
}

function wp_cache_add_multiple(array $data, $group = '', $expire = 0) {
    return $GLOBALS['wp_object_cache']->add_multiple($data, $group, (int) $expire);
}

function wp_cache_replace($key, $data, $group = '', $expire = 0) {
    return $GLOBALS['wp_object_cache']->replace($key, $data, $group, (int) $expire);
}

function wp_cache_set($key, $data, $group = '', $expire = 0) {
    return $GLOBALS['wp_object_cache']->set($key, $data, $group, (int) $expire);
}

function wp_cache_set_multiple(array $data, $group = '', $expire = 0) {
    return $GLOBALS['wp_object_cache']->set_multiple($data, $group, (int) $expire);
}

function wp_cache_get($key, $group = '', $force = false, &$found = null) {
    return $GLOBALS['wp_object_cache']->get($key, $group, $force, $found);
}

function wp_cache_get_multiple($keys, $group = '', $force = false) {
    return $GLOBALS['wp_object_cache']->get_multiple($keys, $group, $force);
}

function wp_cache_delete($key, $group = '') {
    return $GLOBALS['wp_object_cache']->delete($key, $group);
}

function wp_cache_delete_multiple(array $keys, $group = '') {
    return $GLOBALS['wp_object_cache']->delete_multiple($keys, $group);
}

function wp_cache_incr($key, $offset = 1, $group = '') {
    return $GLOBALS['wp_object_cache']->incr($key, $offset, $group);
}

function wp_cache_decr($key, $offset = 1, $group = '') {
    return $GLOBALS['wp_object_cache']->decr($key, $offset, $group);
}

function wp_cache_flush() {
    return $GLOBALS['wp_object_cache']->flush();
}

function wp_cache_flush_runtime() {
    return $GLOBALS['wp_object_cache']->flush_runtime();
}

function wp_cache_flush_group($group) {
    return $GLOBALS['wp_object_cache']->flush_group($group);
}

function wp_cache_supports($feature) {
    switch ($feature) {
        case 'get_multiple':
        case 'set_multiple':
        case 'add_multiple':
        case 'delete_multiple':
        case 'flush_runtime':
        case 'flush_group':
            return true;
        default:
            return false;
    }
}

function wp_cache_close() {
    return true;
}

function wp_cache_add_global_groups($groups) {
    $GLOBALS['wp_object_cache']->add_global_groups($groups);
}

function wp_cache_add_non_persistent_groups($groups) {
    $GLOBALS['wp_object_cache']->add_non_persistent_groups($groups);
}

function wp_cache_switch_to_blog($blog_id) {
    $GLOBALS['wp_object_cache']->switch_to_blog($blog_id);
}

/**
 * Reset the runtime half of the cache. Kept for older callers.
 */
function wp_cache_reset() {
    $GLOBALS['wp_object_cache']->flush_runtime();
}

// ---------------------------------------------------------------------------------------

class WP_Object_Cache {

    /** Values resolved during this request. Always consulted before APCu. */
    private $cache = array();

    /** Groups that must never leave this request (WordPress marks these itself). */
    private $non_persistent_groups = array();

    /** Groups shared across sites in a network -- never prefixed with a blog id. */
    private $global_groups = array();

    /** True only when APCu is actually usable. Everything degrades to runtime-only if not. */
    private $apcu = false;

    private $blog_prefix = '';
    private $salt        = '';

    /** Memoised namespace fragments, so we don't re-read them from APCu per key. */
    private $flush_token   = null;
    private $group_tokens  = array();

    public $cache_hits   = 0;
    public $cache_misses = 0;

    public function __construct() {
        $this->apcu = function_exists('apcu_fetch')
            && function_exists('apcu_enabled')
            && apcu_enabled();

        // Namespaces the cache per site, so two WordPress installs sharing one PHP pool
        // cannot read each other's entries. WP_CACHE_KEY_SALT is set by most hosts (and by
        // Local); fall back to something stable and site-specific if it isn't.
        if (defined('WP_CACHE_KEY_SALT') && WP_CACHE_KEY_SALT) {
            $this->salt = (string) WP_CACHE_KEY_SALT;
        } else {
            $this->salt = md5(ABSPATH . (defined('DB_NAME') ? DB_NAME : ''));
        }

        $this->blog_prefix = is_multisite() ? (string) get_current_blog_id() . ':' : '';
    }

    // ------------------------------------------------------------------ group bookkeeping

    public function add_global_groups($groups) {
        foreach ((array) $groups as $group) {
            $this->global_groups[$group] = true;
        }
    }

    public function add_non_persistent_groups($groups) {
        foreach ((array) $groups as $group) {
            $this->non_persistent_groups[$group] = true;
        }
    }

    public function switch_to_blog($blog_id) {
        $this->blog_prefix = is_multisite() ? (string) (int) $blog_id . ':' : '';
    }

    private function is_persistent($group) {
        return $this->apcu && !isset($this->non_persistent_groups[$group]);
    }

    // ------------------------------------------------------------------------ namespacing
    //
    // A key carries two version tokens: one for the whole cache and one for its group.
    // Flushing therefore means changing a token, not hunting down and deleting every key --
    // APCu cannot enumerate its own keys by prefix, and apcu_clear_cache() would wipe every
    // other site sharing the pool, which is not ours to do.
    //
    // The tokens are random, never sequential counters. If APCu evicts a token under memory
    // pressure, the replacement must not collide with one that was used before, or orphaned
    // entries from an old generation would suddenly become live again.

    private function flush_token() {
        if ($this->flush_token !== null) {
            return $this->flush_token;
        }

        if (!$this->apcu) {
            return $this->flush_token = '0';
        }

        $token_key = $this->salt . ':flush';
        $found     = false;
        $token     = apcu_fetch($token_key, $found);

        if (!$found || !is_string($token)) {
            $token = $this->new_token();
            apcu_store($token_key, $token);
        }

        return $this->flush_token = $token;
    }

    private function group_token($group) {
        if (isset($this->group_tokens[$group])) {
            return $this->group_tokens[$group];
        }

        if (!$this->apcu) {
            return $this->group_tokens[$group] = '0';
        }

        $token_key = $this->salt . ':' . $this->flush_token() . ':gt:' . $group;
        $found     = false;
        $token     = apcu_fetch($token_key, $found);

        if (!$found || !is_string($token)) {
            $token = $this->new_token();
            apcu_store($token_key, $token);
        }

        return $this->group_tokens[$group] = $token;
    }

    private function new_token() {
        if (function_exists('random_bytes')) {
            try {
                return bin2hex(random_bytes(6));
            } catch (Exception $e) {
                // fall through
            }
        }

        return uniqid('', true);
    }

    private function apcu_key($key, $group) {
        $prefix = isset($this->global_groups[$group]) ? '' : $this->blog_prefix;

        return $this->salt . ':' . $this->flush_token() . ':' . $prefix . $group . ':'
            . $this->group_token($group) . ':' . $key;
    }

    private function ttl($expire) {
        $expire = (int) $expire;

        // WordPress means "keep this forever". Nothing in shared memory is forever, and an
        // unbounded entry is also an unbounded window for CLI-induced staleness -- so cap it.
        // An expired entry is not wrong data; WordPress just re-reads it from MySQL.
        if ($expire <= 0) {
            return (int) WP_APCU_MAX_TTL;
        }

        if (WP_APCU_MAX_TTL > 0 && $expire > WP_APCU_MAX_TTL) {
            return (int) WP_APCU_MAX_TTL;
        }

        return $expire;
    }

    // ------------------------------------------------------------------------------- reads

    public function get($key, $group = 'default', $force = false, &$found = null) {
        $group = $group ? $group : 'default';

        $in_runtime = isset($this->cache[$group]) && array_key_exists($key, $this->cache[$group]);

        // With no persistent backend the runtime array IS the store, so $force has nothing
        // to go back to and must not be honoured -- otherwise a forced read (which is what
        // replace() and incr() do) would always miss and those calls would silently fail.
        if (!$this->is_persistent($group)) {
            if ($in_runtime) {
                $found = true;
                $this->cache_hits++;

                return $this->copy($this->cache[$group][$key]);
            }

            $found = false;
            $this->cache_misses++;

            return false;
        }

        if (!$force && $in_runtime) {
            $found = true;
            $this->cache_hits++;

            return $this->copy($this->cache[$group][$key]);
        }

        $ok    = false;
        $value = apcu_fetch($this->apcu_key($key, $group), $ok);

        if (!$ok) {
            $found = false;
            $this->cache_misses++;

            return false;
        }

        $found = true;
        $this->cache_hits++;
        $this->cache[$group][$key] = $value;

        return $this->copy($value);
    }

    public function get_multiple($keys, $group = 'default', $force = false) {
        $values = array();

        foreach ((array) $keys as $key) {
            $values[$key] = $this->get($key, $group, $force);
        }

        return $values;
    }

    // ------------------------------------------------------------------------------ writes

    public function set($key, $data, $group = 'default', $expire = 0) {
        $group = $group ? $group : 'default';

        $this->cache[$group][$key] = $this->copy($data);

        if (!$this->is_persistent($group)) {
            return true;
        }

        return (bool) apcu_store($this->apcu_key($key, $group), $data, $this->ttl($expire));
    }

    public function set_multiple(array $data, $group = 'default', $expire = 0) {
        $results = array();

        foreach ($data as $key => $value) {
            $results[$key] = $this->set($key, $value, $group, $expire);
        }

        return $results;
    }

    public function add($key, $data, $group = 'default', $expire = 0) {
        if (function_exists('wp_suspend_cache_addition') && wp_suspend_cache_addition()) {
            return false;
        }

        $group = $group ? $group : 'default';

        if (isset($this->cache[$group]) && array_key_exists($key, $this->cache[$group])) {
            return false;
        }

        if (!$this->is_persistent($group)) {
            $this->cache[$group][$key] = $this->copy($data);

            return true;
        }

        // apcu_add is atomic: it fails if the key is already there, which is exactly the
        // contract of wp_cache_add().
        if (!apcu_add($this->apcu_key($key, $group), $data, $this->ttl($expire))) {
            return false;
        }

        $this->cache[$group][$key] = $this->copy($data);

        return true;
    }

    public function add_multiple(array $data, $group = '', $expire = 0) {
        $results = array();

        foreach ($data as $key => $value) {
            $results[$key] = $this->add($key, $value, $group, $expire);
        }

        return $results;
    }

    public function replace($key, $data, $group = 'default', $expire = 0) {
        $group = $group ? $group : 'default';

        $found = false;
        $this->get($key, $group, true, $found);

        if (!$found) {
            return false;
        }

        return $this->set($key, $data, $group, $expire);
    }

    public function delete($key, $group = 'default') {
        $group = $group ? $group : 'default';

        // WordPress reports whether anything was actually removed: deleting a key that was
        // never there returns false, not true.
        $existed = isset($this->cache[$group]) && array_key_exists($key, $this->cache[$group]);

        unset($this->cache[$group][$key]);

        if (!$this->is_persistent($group)) {
            return $existed;
        }

        $removed = apcu_delete($this->apcu_key($key, $group));

        return $existed || (bool) $removed;
    }

    public function delete_multiple(array $keys, $group = '') {
        $results = array();

        foreach ($keys as $key) {
            $results[$key] = $this->delete($key, $group);
        }

        return $results;
    }

    // -------------------------------------------------------------------------- counters

    public function incr($key, $offset = 1, $group = 'default') {
        $group  = $group ? $group : 'default';
        $offset = (int) $offset;

        // Incrementing a key that is not there must FAIL, and must not create it. That is
        // WordPress's contract, and apcu_inc() does the opposite -- it happily creates the
        // key and reports success -- so existence has to be established first.
        $found   = false;
        $current = $this->get($key, $group, true, $found);

        if (!$found) {
            return false;
        }

        // WordPress treats a non-numeric value as 0 rather than failing. apcu_inc() would
        // refuse it, so this case is handled with a plain write.
        if (!is_numeric($current)) {
            $value = max(0, $offset);
            $this->set($key, $value, $group, 0);

            return $value;
        }

        if (!$this->is_persistent($group)) {
            $value = max(0, (int) $current + $offset);
            $this->cache[$group][$key] = $value;

            return $value;
        }

        // The key exists and holds a number, so hand the arithmetic to APCu, which does it
        // atomically -- two concurrent requests incrementing the same counter cannot lose
        // an update the way a read-modify-write here would.
        $ok    = false;
        $value = apcu_inc($this->apcu_key($key, $group), $offset, $ok);

        if (!$ok) {
            return false;
        }

        // WordPress never lets a counter go below zero.
        if ($value < 0) {
            $value = 0;
            apcu_store($this->apcu_key($key, $group), 0, $this->ttl(0));
        }

        $this->cache[$group][$key] = $value;

        return $value;
    }

    public function decr($key, $offset = 1, $group = 'default') {
        return $this->incr($key, -abs((int) $offset), $group);
    }

    // ---------------------------------------------------------------------------- flushes

    /**
     * Flush everything -- by rotating the namespace, not by clearing APCu.
     *
     * apcu_clear_cache() would wipe the memory of every other application sharing this PHP
     * pool. On shared hosting that is somebody else's site. Rotating the token orphans our
     * entries instead; they cost nothing and are reclaimed by APCu's own expiry and LRU.
     */
    public function flush() {
        $this->flush_runtime();

        if (!$this->apcu) {
            return true;
        }

        apcu_store($this->salt . ':flush', $this->new_token());

        $this->flush_token  = null;
        $this->group_tokens = array();

        return true;
    }

    public function flush_group($group) {
        unset($this->cache[$group]);

        if (!$this->apcu) {
            return true;
        }

        apcu_store($this->salt . ':' . $this->flush_token() . ':gt:' . $group, $this->new_token());

        unset($this->group_tokens[$group]);

        return true;
    }

    public function flush_runtime() {
        $this->cache = array();

        return true;
    }

    // ------------------------------------------------------------------------ diagnostics

    /**
     * Objects must not be handed out by reference, or a caller mutating what it got back
     * would silently corrupt the cached copy. WordPress core's own cache clones for the
     * same reason.
     */
    private function copy($value) {
        return is_object($value) ? clone $value : $value;
    }

    public function is_apcu_active() {
        return $this->apcu;
    }

    public function stats() {
        $total = $this->cache_hits + $this->cache_misses;
        $rate  = $total > 0 ? round(($this->cache_hits / $total) * 100, 1) : 0;

        echo '<p><strong>APCu:</strong> ' . ($this->apcu ? 'active' : 'NOT active (runtime only)') . '<br />';
        echo '<strong>Hits:</strong> ' . (int) $this->cache_hits . '<br />';
        echo '<strong>Misses:</strong> ' . (int) $this->cache_misses . '<br />';
        echo '<strong>Hit rate:</strong> ' . $rate . '%</p>';
    }
}
