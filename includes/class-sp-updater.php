<?php
/**
 * GitHub Plugin Updater
 * 
 * Handles automatic updates from GitHub releases with proper WordPress integration
 * and a fallback direct GitHub download system.
 * 
 * @package Saint_Porphyrius
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Updater {
    private $plugin_slug;
    private $plugin_file;
    private $plugin_basename;
    private $github_repo;
    private $github_api_url;
    private $plugin_data;
    private $github_response;
    private $transient_key = 'sp_github_update_check';
    private $transient_expiry = 43200; // 12 hours

    public function __construct() {
        $this->plugin_file = SP_PLUGIN_DIR . 'saint-porphyrius.php';
        $this->plugin_basename = plugin_basename($this->plugin_file);
        $this->plugin_slug = dirname($this->plugin_basename);
        $this->github_repo = 'micbwilliam/Saint-Porphyrius';
        $this->github_api_url = 'https://api.github.com/repos/' . $this->github_repo;

        // WordPress update system hooks
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('site_transient_update_plugins', array($this, 'site_transient_update_plugins'));
        add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);
        
        // Critical: Fix the folder name after GitHub zip extraction
        add_filter('upgrader_source_selection', array($this, 'fix_source_directory'), 10, 4);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
        add_filter('upgrader_package_options', array($this, 'fix_package_options'), 10, 1);
        
        // Store whether plugin was active before update
        add_filter('upgrader_pre_install', array($this, 'before_install'), 10, 2);
        
        // Admin menu for update settings
        add_action('admin_menu', array($this, 'add_update_menu'), 99);
        
        // AJAX handlers
        add_action('wp_ajax_sp_check_updates', array($this, 'ajax_check_updates'));
        add_action('wp_ajax_sp_force_update', array($this, 'ajax_force_update'));
        add_action('wp_ajax_sp_direct_github_update', array($this, 'ajax_direct_github_update'));
        add_action('wp_ajax_sp_clear_update_cache', array($this, 'ajax_clear_cache'));
        
        // Add update notice
        add_action('admin_notices', array($this, 'update_notice'));
        
        // Add row action for force check on plugins page
        add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 10, 2);
    }
    
    /**
     * Add "Check for updates" link on plugins page
     */
    public function plugin_row_meta($links, $file) {
        if ($file === $this->plugin_basename) {
            $links[] = '<a href="' . admin_url('admin.php?page=sp-updates') . '">Check for updates</a>';
        }
        return $links;
    }

    /**
     * Get plugin data
     */
    private function get_plugin_data() {
        if (empty($this->plugin_data)) {
            // Always use the constant first - most reliable
            if (defined('SP_PLUGIN_VERSION')) {
                $this->plugin_data = array(
                    'Name' => 'Saint Porphyrius',
                    'Version' => SP_PLUGIN_VERSION,
                    'Author' => 'Saint Porphyrius Team',
                    'Description' => 'A mobile-first church community app with Arabic interface',
                );
                return $this->plugin_data;
            }
            
            // Fallback: Try to read from file
            if (file_exists($this->plugin_file)) {
                // Ensure get_plugin_data function is available
                if (!function_exists('get_plugin_data')) {
                    require_once ABSPATH . 'wp-admin/includes/plugin.php';
                }
                
                // Force refresh by not using cache
                $this->plugin_data = get_plugin_data($this->plugin_file, false, false);
                
                // Fallback if version is still empty
                if (empty($this->plugin_data['Version'])) {
                    $this->plugin_data['Version'] = '0.0.0';
                }
            } else {
                // File doesn't exist - return defaults
                $this->plugin_data = array(
                    'Name' => 'Saint Porphyrius',
                    'Version' => '0.0.0',
                    'Author' => 'Saint Porphyrius Team',
                    'Description' => '',
                );
            }
        }
        return $this->plugin_data;
    }
    
    /**
     * Get the download URL - prefer uploaded asset, fallback to zipball
     */
    private function get_download_url($release) {
        $download_url = $release['zipball_url'] ?? '';
        
        // Check for uploaded ZIP asset first (more reliable folder structure)
        if (!empty($release['assets'])) {
            foreach ($release['assets'] as $asset) {
                if (strpos($asset['name'], '.zip') !== false) {
                    $download_url = $asset['browser_download_url'];
                    break;
                }
            }
        }
        
        return $download_url;
    }

    /**
     * Get GitHub release information
     */
    public function get_github_release($force = false) {
        if (!$force) {
            $cached = get_transient($this->transient_key);
            if ($cached !== false) {
                return $cached;
            }
        }

        $response = wp_remote_get(
            $this->github_api_url . '/releases/latest',
            array(
                'headers' => array(
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
                ),
                'timeout' => 15
            )
        );

        if (is_wp_error($response)) {
            return array('error' => $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code === 404) {
            return array(
                'error' => 'no_releases',
                'message' => 'No releases found in the GitHub repository. Create a release to enable updates.'
            );
        }
        
        if ($response_code === 403) {
            // Rate limit exceeded - try to use stale cache
            $stale_cache = get_option('sp_github_release_backup');
            if ($stale_cache) {
                return $stale_cache;
            }
            return array(
                'error' => 'rate_limit',
                'message' => 'GitHub API rate limit exceeded (60 requests/hour for unauthenticated requests). Please try again later or check GitHub directly.'
            );
        }
        
        if ($response_code !== 200) {
            return array(
                'error' => 'api_error',
                'message' => 'GitHub API returned status code: ' . $response_code
            );
        }

        $body = wp_remote_retrieve_body($response);
        $release = json_decode($body, true);

        if (empty($release) || isset($release['message'])) {
            return array(
                'error' => 'parse_error',
                'message' => $release['message'] ?? 'Failed to parse GitHub response'
            );
        }

        $this->github_response = $release;
        set_transient($this->transient_key, $release, $this->transient_expiry);
        
        // Also save as backup for when rate limited
        update_option('sp_github_release_backup', $release);

        return $release;
    }

    /**
     * Get all releases from GitHub
     */
    public function get_all_releases($limit = 10) {
        $response = wp_remote_get(
            $this->github_api_url . '/releases?per_page=' . $limit,
            array(
                'headers' => array(
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
                ),
                'timeout' => 15
            )
        );

        if (is_wp_error($response)) {
            return array('error' => $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return array();
        }

        $body = wp_remote_retrieve_body($response);
        $releases = json_decode($body, true);
        
        return is_array($releases) ? $releases : array();
    }

    /**
     * Get repository information
     */
    public function get_repo_info() {
        $response = wp_remote_get(
            $this->github_api_url,
            array(
                'headers' => array(
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
                ),
                'timeout' => 15
            )
        );

        if (is_wp_error($response)) {
            return array('error' => $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return array('error' => 'Repository not found or not accessible');
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * Check for plugin updates - hooks into WordPress update system
     * 
     * This filter runs when WordPress is about to save the update_plugins transient.
     * We inject our plugin update info here if a new version is available.
     * 
     * @param object $transient The update_plugins transient object
     * @return object Modified transient with our update info
     */
    public function check_for_update($transient) {
        // Ensure transient is an object
        if (!is_object($transient)) {
            $transient = new stdClass();
        }
        
        // Initialize response array if needed
        if (!isset($transient->response)) {
            $transient->response = array();
        }
        if (!isset($transient->checked)) {
            $transient->checked = array();
        }
        
        // Set current version in checked array
        $plugin_data = $this->get_plugin_data();
        $current_version = $plugin_data['Version'] ?? '0.0.0';
        $transient->checked[$this->plugin_basename] = $current_version;

        // Get GitHub release info
        $release = $this->get_github_release();
        
        if (isset($release['error'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('SP Updater: GitHub error - ' . ($release['message'] ?? $release['error']));
            }
            return $transient;
        }

        $github_version = ltrim($release['tag_name'] ?? '', 'v');

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SP Updater: Current=' . $current_version . ', GitHub=' . $github_version . ', Needs update=' . (version_compare($github_version, $current_version, '>') ? 'yes' : 'no'));
        }

        // Check if update is available
        if (version_compare($github_version, $current_version, '>')) {
            $download_url = $this->get_download_url($release);

            $transient->response[$this->plugin_basename] = (object) array(
                'id' => $this->github_repo,
                'slug' => $this->plugin_slug,
                'plugin' => $this->plugin_basename,
                'new_version' => $github_version,
                'url' => $release['html_url'] ?? 'https://github.com/' . $this->github_repo,
                'package' => $download_url,
                'icons' => array(),
                'banners' => array(),
                'banners_rtl' => array(),
                'requires' => '5.0',
                'tested' => get_bloginfo('version'),
                'requires_php' => '7.4',
            );
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('SP Updater: Update registered for ' . $this->plugin_basename . ' - Download: ' . $download_url);
            }
        } else {
            // No update available - add to no_update array
            if (!isset($transient->no_update)) {
                $transient->no_update = array();
            }
            $transient->no_update[$this->plugin_basename] = (object) array(
                'id' => $this->github_repo,
                'slug' => $this->plugin_slug,
                'plugin' => $this->plugin_basename,
                'new_version' => $current_version,
                'url' => 'https://github.com/' . $this->github_repo,
                'package' => '',
            );
        }

        return $transient;
    }
    
    /**
     * Filter the update_plugins transient when it's retrieved
     * 
     * This ensures update info is always available even if the pre_set hook
     * didn't fire (e.g., when viewing the plugins page directly).
     */
    public function site_transient_update_plugins($transient) {
        // Don't check during AJAX or if empty
        if (empty($transient) || (defined('DOING_AJAX') && DOING_AJAX && !isset($_POST['action']))) {
            return $transient;
        }
        
        // If update already registered, return as-is
        if (isset($transient->response[$this->plugin_basename])) {
            return $transient;
        }
        
        // Get cached release info (don't force refresh here to avoid rate limits)
        $release = $this->get_github_release(false);
        
        if (isset($release['error'])) {
            return $transient;
        }
        
        $plugin_data = $this->get_plugin_data();
        $current_version = $plugin_data['Version'] ?? '0.0.0';
        $github_version = ltrim($release['tag_name'] ?? '', 'v');
        
        if (version_compare($github_version, $current_version, '>')) {
            if (!is_object($transient)) {
                $transient = new stdClass();
            }
            if (!isset($transient->response)) {
                $transient->response = array();
            }
            
            $download_url = $this->get_download_url($release);
            
            $transient->response[$this->plugin_basename] = (object) array(
                'id' => $this->github_repo,
                'slug' => $this->plugin_slug,
                'plugin' => $this->plugin_basename,
                'new_version' => $github_version,
                'url' => $release['html_url'] ?? 'https://github.com/' . $this->github_repo,
                'package' => $download_url,
                'icons' => array(),
                'banners' => array(),
                'banners_rtl' => array(),
                'requires' => '5.0',
                'tested' => get_bloginfo('version'),
                'requires_php' => '7.4',
            );
        }
        
        return $transient;
    }

    /**
     * Plugin information popup
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }

        if (!isset($args->slug) || $args->slug !== $this->plugin_slug) {
            return $result;
        }

        $release = $this->get_github_release();
        $repo_info = $this->get_repo_info();
        $plugin_data = $this->get_plugin_data();

        if (isset($release['error'])) {
            return $result;
        }

        $github_version = ltrim($release['tag_name'] ?? '', 'v');
        $download_url = $this->get_download_url($release);

        return (object) array(
            'name' => $plugin_data['Name'] ?? 'Saint Porphyrius',
            'slug' => $this->plugin_slug,
            'version' => $github_version,
            'author' => '<a href="https://github.com/micbwilliam">' . ($plugin_data['Author'] ?? 'Saint Porphyrius Team') . '</a>',
            'author_profile' => 'https://github.com/micbwilliam',
            'homepage' => $release['html_url'] ?? 'https://github.com/' . $this->github_repo,
            'short_description' => $plugin_data['Description'] ?? '',
            'sections' => array(
                'description' => $plugin_data['Description'] ?? '',
                'changelog' => $this->format_changelog($release['body'] ?? ''),
                'installation' => 'Upload the plugin files to the `/wp-content/plugins/Saint-Porphyrius` directory, or install the plugin through the WordPress plugins screen directly.'
            ),
            'download_link' => $download_url,
            'last_updated' => $release['published_at'] ?? '',
            'requires' => '5.0',
            'tested' => get_bloginfo('version'),
            'requires_php' => '7.4',
            'downloaded' => $repo_info['watchers_count'] ?? 0,
            'active_installs' => $repo_info['stargazers_count'] ?? 0,
            'banners' => array(),
        );
    }
    
    /**
     * Fix the source directory name after extraction
     * 
     * GitHub's zipball creates folders like "owner-repo-hash" which need to be
     * renamed to the proper plugin slug for WordPress to recognize it.
     * 
     * @param string $source File source location
     * @param string $remote_source Remote file source location
     * @param WP_Upgrader $upgrader WP_Upgrader instance
     * @param array $hook_extra Extra arguments passed to hooked filters
     * @return string|WP_Error
     */
    public function fix_source_directory($source, $remote_source, $upgrader, $hook_extra) {
        global $wp_filesystem;
        
        // Only process our plugin updates
        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->plugin_basename) {
            // Also check if this is a plugin update (not theme or other)
            if (!($upgrader instanceof Plugin_Upgrader)) {
                return $source;
            }
        }
        
        // Normalize source path - remove trailing slashes for comparison
        $source = untrailingslashit($source);
        $source_name = basename($source);
        
        // Expected folder name (must match plugin slug exactly)
        $expected_slug = 'Saint-Porphyrius';
        
        // If source already has the correct name, no changes needed
        if ($source_name === $expected_slug) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('SP Updater: Source directory already correct: ' . $source_name);
            }
            return trailingslashit($source);
        }
        
        // Check if this is a GitHub-style folder name that needs renaming
        // Patterns to match:
        // - micbwilliam-Saint-Porphyrius-abc1234
        // - Saint-Porphyrius-abc1234  
        // - Saint-Porphyrius-main
        // - Saint-Porphyrius-master
        $is_our_plugin = (
            stripos($source_name, 'Saint-Porphyrius') !== false ||
            stripos($source_name, 'saint-porphyrius') !== false ||
            stripos($source_name, 'micbwilliam') !== false
        );
        
        // Additional check: verify the plugin main file exists in source
        if (!$is_our_plugin && $wp_filesystem->exists($source . '/saint-porphyrius.php')) {
            $is_our_plugin = true;
        }
        
        if (!$is_our_plugin) {
            return trailingslashit($source);
        }
        
        // Verify the main plugin file exists before renaming
        if (!$wp_filesystem->exists($source . '/saint-porphyrius.php')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('SP Updater: Main plugin file not found in source: ' . $source);
            }
            return trailingslashit($source);
        }
        
        // Rename to the correct plugin slug
        $corrected_source = trailingslashit($remote_source) . $expected_slug;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SP Updater: Renaming source from "' . $source . '" to "' . $corrected_source . '"');
        }
        
        // If corrected source already exists (shouldn't happen), remove it first
        if ($wp_filesystem->exists($corrected_source)) {
            $wp_filesystem->delete($corrected_source, true);
        }
        
        if ($wp_filesystem->move($source, $corrected_source)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('SP Updater: Successfully renamed source directory');
            }
            return trailingslashit($corrected_source);
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('SP Updater: Failed to rename source directory');
            }
            // Return original source with trailing slash
            return trailingslashit($source);
        }
    }

    /**
     * Format changelog from GitHub markdown
     */
    private function format_changelog($body) {
        if (empty($body)) {
            return '<p>No changelog available.</p>';
        }
        
        // Convert markdown to HTML (basic conversion)
        $body = esc_html($body);
        $body = nl2br($body);
        $body = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $body);
        $body = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $body);
        $body = preg_replace('/^- (.*)$/m', '<li>$1</li>', $body);
        $body = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $body);
        
        return $body;
    }

    /**
     * After installation, ensure plugin is properly activated
     * 
     * @param bool $response Installation response
     * @param array $hook_extra Extra arguments
     * @param array $result Installation result
     * @return array Modified result
     */
    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;

        // Only process our plugin
        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->plugin_basename) {
            return $result;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SP Updater after_install: response=' . var_export($response, true));
            error_log('SP Updater after_install: result destination=' . ($result['destination'] ?? 'not set'));
        }

        // Expected plugin folder
        $expected_folder = WP_PLUGIN_DIR . '/Saint-Porphyrius';
        
        // Get the actual destination from result
        $actual_destination = isset($result['destination']) ? untrailingslashit($result['destination']) : '';
        
        // Check if destination is incorrect and needs fixing
        if (!empty($actual_destination) && $actual_destination !== $expected_folder) {
            if ($wp_filesystem->exists($actual_destination)) {
                // Verify the main plugin file exists in the source
                if (!$wp_filesystem->exists($actual_destination . '/saint-porphyrius.php')) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('SP Updater: Plugin file not found in destination: ' . $actual_destination);
                    }
                    return $result;
                }
                
                // Remove old plugin folder if it exists
                if ($wp_filesystem->exists($expected_folder)) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('SP Updater: Removing existing folder: ' . $expected_folder);
                    }
                    $wp_filesystem->delete($expected_folder, true);
                }
                
                // Move to correct location
                if ($wp_filesystem->move($actual_destination, $expected_folder)) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('SP Updater: Moved plugin to correct location: ' . $expected_folder);
                    }
                    $result['destination'] = $expected_folder;
                    $result['destination_name'] = 'Saint-Porphyrius';
                } else {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('SP Updater: Failed to move plugin to correct location');
                    }
                }
            }
        }
        
        // Ensure the plugin file exists before trying to activate
        $plugin_file = $expected_folder . '/saint-porphyrius.php';
        if (!$wp_filesystem->exists($plugin_file)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('SP Updater: Cannot activate - plugin file missing: ' . $plugin_file);
            }
            return $result;
        }

        // Reactivate plugin if it was active
        if (!is_plugin_active($this->plugin_basename)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('SP Updater: Reactivating plugin: ' . $this->plugin_basename);
            }
            $activate_result = activate_plugin($this->plugin_basename);
            if (is_wp_error($activate_result)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('SP Updater: Activation failed: ' . $activate_result->get_error_message());
                }
            }
        }
        
        // Clear all caches
        delete_transient($this->transient_key);
        delete_site_transient('update_plugins');
        wp_clean_plugins_cache();
        
        // Flush rewrite rules to ensure routes work
        flush_rewrite_rules();

        return $result;
    }
    
    /**
     * Store plugin state before update
     * 
     * @param bool $response
     * @param array $hook_extra
     * @return bool
     */
    public function before_install($response, $hook_extra) {
        // Only process our plugin
        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->plugin_basename) {
            return $response;
        }
        
        // Store whether plugin is currently active
        $is_active = is_plugin_active($this->plugin_basename);
        update_option('sp_was_active_before_update', $is_active);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SP Updater before_install: Plugin was active = ' . ($is_active ? 'yes' : 'no'));
        }
        
        return $response;
    }
    
    /**
     * Fix package options to ensure correct destination name
     * 
     * @param array $options
     * @return array
     */
    public function fix_package_options($options) {
        // Check if this is our plugin update
        if (isset($options['hook_extra']['plugin']) && $options['hook_extra']['plugin'] === $this->plugin_basename) {
            // Ensure the destination name matches our plugin folder
            $options['destination'] = WP_PLUGIN_DIR;
            $options['clear_destination'] = true;
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('SP Updater fix_package_options: Setting destination to ' . WP_PLUGIN_DIR);
            }
        }
        
        return $options;
    }

    /**
     * Add update menu page
     */
    public function add_update_menu() {
        add_submenu_page(
            'saint-porphyrius',
            'Plugin Updates',
            'Updates',
            'manage_options',
            'sp-updates',
            array($this, 'render_update_page')
        );
    }

    /**
     * Render update settings page
     */
    public function render_update_page() {
        $plugin_data = $this->get_plugin_data();
        $current_version = $plugin_data['Version'] ?? '0.0.0';
        $release = $this->get_github_release(true);
        $repo_info = $this->get_repo_info();
        $all_releases = $this->get_all_releases(5);
        
        $github_version = '';
        $update_available = false;
        
        if (!isset($release['error'])) {
            $github_version = ltrim($release['tag_name'] ?? '', 'v');
            $update_available = version_compare($github_version, $current_version, '>');
        }
        
        ?>
        <div class="wrap">
            <h1>
                <span class="dashicons dashicons-update" style="font-size: 30px; margin-right: 10px;"></span>
                Saint Porphyrius - Plugin Updates
            </h1>

            <div class="sp-update-dashboard">
                <!-- Current Status Card -->
                <div class="sp-update-card sp-status-card">
                    <h2>
                        <span class="dashicons dashicons-info-outline"></span>
                        Current Status
                    </h2>
                    <div class="sp-status-grid">
                        <div class="sp-status-item">
                            <label>Installed Version</label>
                            <span class="sp-version-badge sp-current"><?php echo esc_html($current_version); ?></span>
                        </div>
                        <div class="sp-status-item">
                            <label>Latest Version</label>
                            <span class="sp-version-badge <?php echo $update_available ? 'sp-new' : 'sp-current'; ?>">
                                <?php echo esc_html($github_version ?: (isset($release['error']) && $release['error'] === 'no_releases' ? 'No releases' : 'Unknown')); ?>
                            </span>
                        </div>
                        <div class="sp-status-item">
                            <label>Status</label>
                            <?php if (isset($release['error']) && $release['error'] === 'no_releases'): ?>
                                <span class="sp-status-badge sp-info">
                                    <span class="dashicons dashicons-info"></span>
                                    No releases found
                                </span>
                            <?php elseif (isset($release['error']) && $release['error'] === 'rate_limit'): ?>
                                <span class="sp-status-badge sp-warning">
                                    <span class="dashicons dashicons-clock"></span>
                                    Rate limited
                                </span>
                            <?php elseif (isset($release['error'])): ?>
                                <span class="sp-status-badge sp-error">
                                    <span class="dashicons dashicons-warning"></span>
                                    Error checking updates
                                </span>
                            <?php elseif ($update_available): ?>
                                <span class="sp-status-badge sp-update-available">
                                    <span class="dashicons dashicons-update"></span>
                                    Update Available
                                </span>
                            <?php else: ?>
                                <span class="sp-status-badge sp-up-to-date">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    Up to Date
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="sp-status-item">
                            <label>Last Checked</label>
                            <span><?php echo esc_html(current_time('F j, Y g:i a')); ?></span>
                        </div>
                    </div>
                    
                    <div class="sp-action-buttons">
                        <button type="button" class="button button-secondary" id="sp-check-updates">
                            <span class="dashicons dashicons-update"></span>
                            Check for Updates
                        </button>
                        <button type="button" class="button button-secondary" id="sp-clear-cache">
                            <span class="dashicons dashicons-trash"></span>
                            Clear Cache
                        </button>
                        <?php if ($update_available): ?>
                            <a href="<?php echo esc_url(admin_url('update-core.php')); ?>" class="button button-primary">
                                <span class="dashicons dashicons-download"></span>
                                Update Now
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (isset($release['error'])): ?>
                    <div class="sp-update-card <?php echo in_array($release['error'], ['no_releases', 'rate_limit']) ? 'sp-info-card' : 'sp-error-card'; ?>">
                        <h2>
                            <span class="dashicons dashicons-<?php echo $release['error'] === 'rate_limit' ? 'clock' : ($release['error'] === 'no_releases' ? 'info' : 'warning'); ?>"></span>
                            <?php 
                            if ($release['error'] === 'no_releases') {
                                echo 'No Releases Available';
                            } elseif ($release['error'] === 'rate_limit') {
                                echo 'GitHub API Rate Limited';
                            } else {
                                echo 'Error';
                            }
                            ?>
                        </h2>
                        <p><?php echo esc_html($release['message'] ?? $release['error']); ?></p>
                        
                        <?php if ($release['error'] === 'rate_limit'): ?>
                            <p>
                                <strong>Why this happens:</strong><br>
                                GitHub limits unauthenticated API requests to 60 per hour. Your server has exceeded this limit.
                            </p>
                            <p>
                                <strong>Solutions:</strong><br>
                                1. Wait an hour and try again<br>
                                2. Check updates manually on <a href="https://github.com/<?php echo esc_attr($this->github_repo); ?>/releases" target="_blank">GitHub Releases</a><br>
                                3. Download the latest version directly from GitHub
                            </p>
                            <a href="https://github.com/<?php echo esc_attr($this->github_repo); ?>/releases" target="_blank" class="button button-primary">
                                <span class="dashicons dashicons-external"></span>
                                View Releases on GitHub
                            </a>
                        <?php elseif ($release['error'] === 'no_releases'): ?>
                            <p>
                                <strong>How to create a release:</strong><br>
                                1. Go to your <a href="https://github.com/<?php echo esc_attr($this->github_repo); ?>/releases/new" target="_blank">GitHub repository releases page</a><br>
                                2. Click "Create a new release"<br>
                                3. Create a tag (e.g., v1.0.1) and fill in the release details<br>
                                4. Attach the plugin ZIP file or let GitHub create it automatically<br>
                                5. Click "Publish release"
                            </p>
                            <a href="https://github.com/<?php echo esc_attr($this->github_repo); ?>/releases/new" target="_blank" class="button button-primary">
                                <span class="dashicons dashicons-external"></span>
                                Create First Release on GitHub
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Repository Info Card -->
                <?php if (!isset($repo_info['error']) && !empty($repo_info)): ?>
                <div class="sp-update-card">
                    <h2>
                        <span class="dashicons dashicons-github"></span>
                        Repository Information
                    </h2>
                    <div class="sp-repo-info">
                        <div class="sp-repo-stat">
                            <span class="dashicons dashicons-star-filled"></span>
                            <span class="sp-stat-value"><?php echo esc_html($repo_info['stargazers_count'] ?? 0); ?></span>
                            <span class="sp-stat-label">Stars</span>
                        </div>
                        <div class="sp-repo-stat">
                            <span class="dashicons dashicons-visibility"></span>
                            <span class="sp-stat-value"><?php echo esc_html($repo_info['watchers_count'] ?? 0); ?></span>
                            <span class="sp-stat-label">Watchers</span>
                        </div>
                        <div class="sp-repo-stat">
                            <span class="dashicons dashicons-networking"></span>
                            <span class="sp-stat-value"><?php echo esc_html($repo_info['forks_count'] ?? 0); ?></span>
                            <span class="sp-stat-label">Forks</span>
                        </div>
                        <div class="sp-repo-stat">
                            <span class="dashicons dashicons-editor-code"></span>
                            <span class="sp-stat-value"><?php echo esc_html($repo_info['open_issues_count'] ?? 0); ?></span>
                            <span class="sp-stat-label">Open Issues</span>
                        </div>
                    </div>
                    <div class="sp-repo-links">
                        <a href="https://github.com/<?php echo esc_attr($this->github_repo); ?>" target="_blank" class="button">
                            <span class="dashicons dashicons-external"></span>
                            View on GitHub
                        </a>
                        <a href="https://github.com/<?php echo esc_attr($this->github_repo); ?>/issues" target="_blank" class="button">
                            <span class="dashicons dashicons-editor-help"></span>
                            Report Issue
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Latest Release Card -->
                <?php if (!isset($release['error']) && !empty($release)): ?>
                <div class="sp-update-card sp-release-card <?php echo $update_available ? 'sp-has-update' : ''; ?>">
                    <h2>
                        <span class="dashicons dashicons-download"></span>
                        Latest Release: <?php echo esc_html($release['tag_name'] ?? ''); ?>
                    </h2>
                    <div class="sp-release-meta">
                        <span>
                            <span class="dashicons dashicons-calendar-alt"></span>
                            Published: <?php echo esc_html(date('F j, Y', strtotime($release['published_at'] ?? ''))); ?>
                        </span>
                        <span>
                            <span class="dashicons dashicons-admin-users"></span>
                            By: <?php echo esc_html($release['author']['login'] ?? 'Unknown'); ?>
                        </span>
                        <?php if (!empty($release['assets'])): ?>
                        <span>
                            <span class="dashicons dashicons-download"></span>
                            Downloads: <?php echo esc_html(array_sum(array_column($release['assets'], 'download_count'))); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($release['body'])): ?>
                    <div class="sp-release-notes">
                        <h3>Release Notes</h3>
                        <div class="sp-release-body">
                            <?php echo wp_kses_post($this->format_changelog($release['body'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="sp-release-actions">
                        <a href="<?php echo esc_url($release['html_url'] ?? '#'); ?>" target="_blank" class="button">
                            <span class="dashicons dashicons-external"></span>
                            View Release
                        </a>
                        <a href="<?php echo esc_url($release['zipball_url'] ?? '#'); ?>" class="button">
                            <span class="dashicons dashicons-download"></span>
                            Download ZIP
                        </a>
                        <?php if ($update_available): ?>
                        <button type="button" class="button button-primary button-hero" id="sp-install-update">
                            <span class="dashicons dashicons-update"></span>
                            Install Update
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Direct Update Notice -->
                <?php if ($update_available): ?>
                <div class="sp-update-card sp-direct-update">
                    <h2>
                        <span class="dashicons dashicons-admin-generic"></span>
                        Update Options
                    </h2>
                    <p><strong>Choose your preferred update method:</strong></p>
                    
                    <div class="sp-update-methods">
                        <div class="sp-update-method">
                            <h4>Option 1: WordPress Standard Update</h4>
                            <p>Uses WordPress's built-in update system. Recommended for most users.</p>
                            <button type="button" class="button button-primary" id="sp-force-update">
                                <span class="dashicons dashicons-update"></span>
                                Update via WordPress
                            </button>
                        </div>
                        
                        <div class="sp-update-method">
                            <h4>Option 2: Direct GitHub Download</h4>
                            <p>Downloads directly from GitHub and replaces files. Use if Option 1 fails.</p>
                            <button type="button" class="button button-secondary" id="sp-direct-github-update">
                                <span class="dashicons dashicons-download"></span>
                                Direct GitHub Update
                            </button>
                        </div>
                    </div>
                    
                    <div id="sp-update-progress" style="display:none; margin-top: 15px;">
                        <div class="sp-progress-bar">
                            <div class="sp-progress-fill"></div>
                        </div>
                        <p id="sp-update-status">Preparing update...</p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Release History Card -->
                <?php if (!isset($all_releases['error']) && is_array($all_releases) && !empty($all_releases)): ?>
                <div class="sp-update-card">
                    <h2>
                        <span class="dashicons dashicons-backup"></span>
                        Release History
                    </h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Version</th>
                                <th>Release Date</th>
                                <th>Author</th>
                                <th>Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_releases as $rel): ?>
                            <?php 
                                $rel_version = ltrim($rel['tag_name'] ?? '', 'v');
                                $is_current = version_compare($rel_version, $current_version, '==');
                                $is_newer = version_compare($rel_version, $current_version, '>');
                            ?>
                            <tr class="<?php echo $is_current ? 'sp-current-release' : ''; ?>">
                                <td>
                                    <strong><?php echo esc_html($rel['tag_name'] ?? ''); ?></strong>
                                    <?php if ($is_current): ?>
                                        <span class="sp-badge sp-badge-current">Installed</span>
                                    <?php elseif ($is_newer): ?>
                                        <span class="sp-badge sp-badge-new">New</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(date('M j, Y', strtotime($rel['published_at'] ?? ''))); ?></td>
                                <td><?php echo esc_html($rel['author']['login'] ?? 'Unknown'); ?></td>
                                <td>
                                    <?php if ($rel['prerelease'] ?? false): ?>
                                        <span class="sp-badge sp-badge-prerelease">Pre-release</span>
                                    <?php else: ?>
                                        <span class="sp-badge sp-badge-stable">Stable</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url($rel['html_url'] ?? '#'); ?>" target="_blank" class="button button-small">
                                        View
                                    </a>
                                    <a href="<?php echo esc_url($rel['zipball_url'] ?? '#'); ?>" class="button button-small">
                                        Download
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p style="margin-top: 15px;">
                        <a href="https://github.com/<?php echo esc_attr($this->github_repo); ?>/releases" target="_blank">
                            View all releases on GitHub →
                        </a>
                    </p>
                </div>
                <?php endif; ?>

                <!-- System Info Card -->
                <div class="sp-update-card">
                    <h2>
                        <span class="dashicons dashicons-admin-tools"></span>
                        System Information
                    </h2>
                    <table class="sp-system-info">
                        <tr>
                            <th>WordPress Version</th>
                            <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                        </tr>
                        <tr>
                            <th>PHP Version</th>
                            <td><?php echo esc_html(phpversion()); ?></td>
                        </tr>
                        <tr>
                            <th>Plugin Directory</th>
                            <td><code><?php echo esc_html(WP_PLUGIN_DIR . '/' . $this->plugin_slug); ?></code></td>
                        </tr>
                        <tr>
                            <th>GitHub Repository</th>
                            <td><a href="https://github.com/<?php echo esc_attr($this->github_repo); ?>" target="_blank"><?php echo esc_html($this->github_repo); ?></a></td>
                        </tr>
                        <tr>
                            <th>Cache Status</th>
                            <td>
                                <?php 
                                $cache = get_transient($this->transient_key);
                                echo $cache ? 'Cached' : 'Not Cached';
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <style>
            .sp-update-dashboard {
                display: grid;
                gap: 20px;
                margin-top: 20px;
            }
            
            .sp-update-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 8px;
                padding: 20px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            }
            
            .sp-update-card h2 {
                margin: 0 0 20px 0;
                padding-bottom: 15px;
                border-bottom: 1px solid #eee;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .sp-update-card h2 .dashicons {
                color: #2271b1;
            }
            
            .sp-status-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-bottom: 20px;
            }
            
            .sp-status-item {
                display: flex;
                flex-direction: column;
                gap: 5px;
            }
            
            .sp-status-item label {
                font-weight: 600;
                color: #666;
                font-size: 12px;
                text-transform: uppercase;
            }
            
            .sp-version-badge {
                display: inline-block;
                padding: 5px 12px;
                border-radius: 20px;
                font-weight: 600;
                font-size: 14px;
            }
            
            .sp-version-badge.sp-current {
                background: #e7f5e7;
                color: #1e7e1e;
            }
            
            .sp-version-badge.sp-new {
                background: #fef3e7;
                color: #b35900;
            }
            
            .sp-status-badge {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 8px 15px;
                border-radius: 5px;
                font-weight: 600;
            }
            
            .sp-status-badge.sp-up-to-date {
                background: #d4edda;
                color: #155724;
            }
            
            .sp-status-badge.sp-update-available {
                background: #fff3cd;
                color: #856404;
            }
            
            .sp-status-badge.sp-error {
                background: #f8d7da;
                color: #721c24;
            }
            
            .sp-status-badge.sp-warning {
                background: #fff3cd;
                color: #856404;
            }
            
            .sp-action-buttons {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
                padding-top: 15px;
                border-top: 1px solid #eee;
            }
            
            .sp-action-buttons .button {
                display: inline-flex;
                align-items: center;
                gap: 5px;
            }
            
            .sp-action-buttons .button .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
            }
            
            .sp-repo-info {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                gap: 20px;
                margin-bottom: 20px;
            }
            
            .sp-repo-stat {
                text-align: center;
                padding: 15px;
                background: #f8f9fa;
                border-radius: 8px;
            }
            
            .sp-repo-stat .dashicons {
                font-size: 24px;
                width: 24px;
                height: 24px;
                color: #2271b1;
            }
            
            .sp-stat-value {
                display: block;
                font-size: 24px;
                font-weight: 700;
                margin: 10px 0 5px;
            }
            
            .sp-stat-label {
                display: block;
                color: #666;
                font-size: 12px;
            }
            
            .sp-repo-links {
                display: flex;
                gap: 10px;
            }
            
            .sp-release-card.sp-has-update {
                border-color: #ffc107;
                border-width: 2px;
            }
            
            .sp-release-meta {
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
                color: #666;
                margin-bottom: 20px;
            }
            
            .sp-release-meta span {
                display: inline-flex;
                align-items: center;
                gap: 5px;
            }
            
            .sp-release-notes {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 20px;
            }
            
            .sp-release-notes h3 {
                margin: 0 0 10px 0;
            }
            
            .sp-release-body {
                max-height: 200px;
                overflow-y: auto;
            }
            
            .sp-release-actions {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }
            
            .sp-badge {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: 600;
                margin-left: 5px;
            }
            
            .sp-badge-current {
                background: #2271b1;
                color: #fff;
            }
            
            .sp-badge-new {
                background: #ffc107;
                color: #000;
            }
            
            .sp-badge-stable {
                background: #28a745;
                color: #fff;
            }
            
            .sp-badge-prerelease {
                background: #6c757d;
                color: #fff;
            }
            
            .sp-current-release {
                background: #e7f3ff !important;
            }
            
            .sp-direct-update {
                border-color: #2271b1;
                background: #f0f6fc;
            }
            
            .sp-direct-update h2 {
                color: #2271b1;
            }
            
            .sp-direct-update ol {
                margin: 15px 0;
                padding-left: 20px;
            }
            
            .sp-direct-update li {
                margin: 8px 0;
                line-height: 1.6;
            }
            
            .sp-update-methods {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 20px;
                margin-top: 15px;
            }
            
            .sp-update-method {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 20px;
            }
            
            .sp-update-method h4 {
                margin: 0 0 10px 0;
                color: #1d2327;
            }
            
            .sp-update-method p {
                color: #666;
                margin-bottom: 15px;
            }
            
            .sp-progress-bar {
                height: 20px;
                background: #e0e0e0;
                border-radius: 10px;
                overflow: hidden;
            }
            
            .sp-progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #2271b1, #135e96);
                width: 0%;
                transition: width 0.3s ease;
                animation: sp-progress-pulse 1.5s infinite;
            }
            
            @keyframes sp-progress-pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.7; }
            }
            
            #sp-update-status {
                text-align: center;
                color: #666;
                margin-top: 10px;
            }
            
            .sp-system-info {
                width: 100%;
            }
            
            .sp-system-info th {
                text-align: left;
                padding: 10px 15px 10px 0;
                width: 200px;
                color: #666;
            }
            
            .sp-system-info td {
                padding: 10px 0;
            }
            
            .sp-system-info code {
                background: #f0f0f1;
                padding: 3px 8px;
                border-radius: 3px;
            }
            
            .sp-error-card {
                border-color: #dc3545;
                background: #fff5f5;
            }
            
            .sp-error-card h2 .dashicons {
                color: #dc3545;
            }
            
            .sp-info-card {
                border-color: #0073aa;
                background: #f0f6fc;
            }
            
            .sp-info-card h2 .dashicons {
                color: #0073aa;
            }
            
            .sp-status-badge.sp-info {
                background: #d1ecf1;
                color: #0c5460;
            }
            
            #sp-update-loading {
                display: none;
                margin-left: 10px;
            }
            
            .button-hero {
                height: auto !important;
                padding: 10px 20px !important;
            }
            
            .dashicons.spin {
                animation: sp-spin 1s linear infinite;
            }
            
            @keyframes sp-spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            var updateNonce = '<?php echo wp_create_nonce('sp_update_nonce'); ?>';
            
            // Check for Updates button
            $('#sp-check-updates').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Checking...');
                
                $.post(ajaxurl, {
                    action: 'sp_check_updates',
                    nonce: updateNonce
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + (response.data || 'Unknown error'));
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Check for Updates');
                    }
                }).fail(function() {
                    alert('Connection error. Please try again.');
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Check for Updates');
                });
            });
            
            // Clear Cache button
            $('#sp-clear-cache').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Clearing...');
                
                $.post(ajaxurl, {
                    action: 'sp_clear_update_cache',
                    nonce: updateNonce
                }, function(response) {
                    if (response.success) {
                        alert('Cache cleared successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + (response.data || 'Unknown error'));
                    }
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> Clear Cache');
                });
            });
            
            // Install Update (goes to WP updates page)
            $('#sp-install-update').on('click', function() {
                if (confirm('This will take you to the WordPress Updates page. Continue?')) {
                    window.location.href = '<?php echo esc_url(admin_url('update-core.php')); ?>';
                }
            });
            
            // WordPress Standard Update
            $('#sp-force-update').on('click', function() {
                if (!confirm('Update the plugin using WordPress upgrader?\n\nMake sure to backup your site first.')) {
                    return;
                }
                
                var $btn = $(this);
                $btn.prop('disabled', true);
                $('#sp-direct-github-update').prop('disabled', true);
                
                showProgress('Preparing WordPress update...');
                updateProgress(20);
                
                $.post(ajaxurl, {
                    action: 'sp_force_update',
                    nonce: updateNonce
                }, function(response) {
                    if (response.success) {
                        updateProgress(100);
                        updateStatus('Update successful! Reloading...');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        hideProgress();
                        alert('WordPress Update Failed:\n\n' + (response.data || 'Unknown error') + '\n\nTry the "Direct GitHub Update" option instead.');
                        $btn.prop('disabled', false);
                        $('#sp-direct-github-update').prop('disabled', false);
                    }
                }).fail(function() {
                    hideProgress();
                    alert('Connection error during update. Please try again.');
                    $btn.prop('disabled', false);
                    $('#sp-direct-github-update').prop('disabled', false);
                });
            });
            
            // Direct GitHub Update (fallback)
            $('#sp-direct-github-update').on('click', function() {
                if (!confirm('This will download directly from GitHub and replace plugin files.\n\nThis is a fallback method - use if WordPress update fails.\n\nMake sure to backup your site first. Continue?')) {
                    return;
                }
                
                var $btn = $(this);
                $btn.prop('disabled', true);
                $('#sp-force-update').prop('disabled', true);
                
                showProgress('Downloading from GitHub...');
                updateProgress(10);
                
                $.post(ajaxurl, {
                    action: 'sp_direct_github_update',
                    nonce: updateNonce
                }, function(response) {
                    if (response.success) {
                        updateProgress(100);
                        updateStatus('Update successful! Reloading...');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        hideProgress();
                        alert('Direct GitHub Update Failed:\n\n' + (response.data || 'Unknown error'));
                        $btn.prop('disabled', false);
                        $('#sp-force-update').prop('disabled', false);
                    }
                }).fail(function() {
                    hideProgress();
                    alert('Connection error during update. Please try again.');
                    $btn.prop('disabled', false);
                    $('#sp-force-update').prop('disabled', false);
                });
            });
            
            // Progress helpers
            function showProgress(status) {
                $('#sp-update-progress').show();
                updateStatus(status);
            }
            
            function hideProgress() {
                $('#sp-update-progress').hide();
                updateProgress(0);
            }
            
            function updateProgress(percent) {
                $('.sp-progress-fill').css('width', percent + '%');
            }
            
            function updateStatus(status) {
                $('#sp-update-status').text(status);
            }
            
            // Simulate progress during update
            var progressInterval;
            function simulateProgress() {
                var progress = 20;
                progressInterval = setInterval(function() {
                    progress += Math.random() * 10;
                    if (progress > 90) progress = 90;
                    updateProgress(progress);
                }, 500);
            }
        });
        </script>
        <?php
    }

    /**
     * AJAX: Check for updates
     */
    public function ajax_check_updates() {
        check_ajax_referer('sp_update_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        // Clear all caches
        delete_transient($this->transient_key);
        delete_site_transient('update_plugins');
        
        // Force fresh fetch
        $release = $this->get_github_release(true);
        
        if (isset($release['error'])) {
            wp_send_json_error($release['message'] ?? $release['error']);
        }
        
        // Trigger WordPress to re-check updates
        wp_update_plugins();
        
        wp_send_json_success($release);
    }

    /**
     * AJAX: Clear update cache
     */
    public function ajax_clear_cache() {
        check_ajax_referer('sp_update_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        delete_transient($this->transient_key);
        delete_site_transient('update_plugins');
        delete_option('sp_github_release_backup');
        wp_clean_plugins_cache();
        
        wp_send_json_success('Cache cleared');
    }

    /**
     * AJAX: Force update through WordPress upgrader
     */
    public function ajax_force_update() {
        check_ajax_referer('sp_update_nonce', 'nonce');
        
        if (!current_user_can('update_plugins')) {
            wp_send_json_error('Unauthorized');
        }

        try {
            // Get the latest release
            $release = $this->get_github_release(true);
            
            if (isset($release['error'])) {
                wp_send_json_error('Failed to get release information: ' . ($release['message'] ?? $release['error']));
            }

            // Get download URL
            $download_url = $this->get_download_url($release);

            if (empty($download_url)) {
                wp_send_json_error('No download URL found in GitHub release');
            }

            // Build update transient for WordPress upgrader
            $plugin_data = $this->get_plugin_data();
            $current_version = $plugin_data['Version'] ?? '0.0.0';
            $github_version = ltrim($release['tag_name'] ?? '', 'v');

            // Ensure update is available
            if (!version_compare($github_version, $current_version, '>')) {
                wp_send_json_error('No update available. Current: ' . $current_version . ', Latest: ' . $github_version);
            }

            // Set up transient for upgrader
            $transient = get_site_transient('update_plugins');
            if (!is_object($transient)) {
                $transient = new stdClass();
            }
            if (empty($transient->checked)) {
                $transient->checked = array();
            }
            if (empty($transient->response)) {
                $transient->response = array();
            }
            
            $transient->checked[$this->plugin_basename] = $current_version;
            $transient->response[$this->plugin_basename] = (object) array(
                'id' => $this->github_repo,
                'slug' => $this->plugin_slug,
                'plugin' => $this->plugin_basename,
                'new_version' => $github_version,
                'url' => $release['html_url'] ?? 'https://github.com/' . $this->github_repo,
                'package' => $download_url,
                'icons' => array(),
                'banners' => array(),
                'requires' => '5.0',
                'tested' => get_bloginfo('version'),
                'requires_php' => '7.4',
            );
            set_site_transient('update_plugins', $transient);

            // Include upgrader classes
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            require_once ABSPATH . 'wp-admin/includes/misc.php';
            
            // Ensure WP_Filesystem is initialized
            if (!WP_Filesystem()) {
                wp_send_json_error('Failed to initialize WordPress filesystem');
            }
            
            // Create a custom upgrader with silent skin
            $skin = new Automatic_Upgrader_Skin();
            $upgrader = new Plugin_Upgrader($skin);
            
            // Perform upgrade
            $result = $upgrader->upgrade($this->plugin_basename, array('clear_update_cache' => true));
            
            if (is_wp_error($result)) {
                wp_send_json_error('Update failed: ' . $result->get_error_message());
            }
            
            if ($result === false) {
                $errors = $skin->get_upgrade_messages();
                $error_msg = !empty($errors) ? implode(', ', $errors) : 'Unknown error during upgrade';
                wp_send_json_error('Update failed: ' . $error_msg);
            }
            
            // Verify the plugin exists after update
            $plugin_file = WP_PLUGIN_DIR . '/Saint-Porphyrius/saint-porphyrius.php';
            if (!file_exists($plugin_file)) {
                wp_send_json_error('Update completed but plugin file not found. The folder may have an incorrect name. Please check the plugins directory.');
            }
            
            // Ensure plugin is activated
            if (!is_plugin_active($this->plugin_basename)) {
                $activate_result = activate_plugin($this->plugin_basename);
                if (is_wp_error($activate_result)) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('SP Updater: Post-update activation failed: ' . $activate_result->get_error_message());
                    }
                }
            }
            
            // Clear caches after successful update
            delete_transient($this->transient_key);
            delete_site_transient('update_plugins');
            wp_clean_plugins_cache();
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
            wp_send_json_success('Plugin updated successfully to version ' . $github_version);
            
        } catch (Exception $e) {
            wp_send_json_error('Exception during update: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX: Direct GitHub update - bypasses WordPress upgrader
     * 
     * This is a fallback method that downloads directly from GitHub and
     * replaces the plugin files manually. Useful when the WordPress 
     * upgrader system fails.
     */
    public function ajax_direct_github_update() {
        check_ajax_referer('sp_update_nonce', 'nonce');
        
        if (!current_user_can('update_plugins')) {
            wp_send_json_error('Unauthorized');
        }
        
        try {
            // Get the latest release
            $release = $this->get_github_release(true);
            
            if (isset($release['error'])) {
                wp_send_json_error('Failed to get release: ' . ($release['message'] ?? $release['error']));
            }
            
            $download_url = $this->get_download_url($release);
            
            if (empty($download_url)) {
                wp_send_json_error('No download URL available');
            }
            
            // Include required files
            require_once ABSPATH . 'wp-admin/includes/file.php';
            
            // Initialize filesystem
            if (!WP_Filesystem()) {
                wp_send_json_error('Failed to initialize filesystem');
            }
            
            global $wp_filesystem;
            
            // Download the ZIP file
            $temp_file = download_url($download_url, 300);
            
            if (is_wp_error($temp_file)) {
                wp_send_json_error('Download failed: ' . $temp_file->get_error_message());
            }
            
            // Create temp extraction directory
            $temp_dir = WP_CONTENT_DIR . '/upgrade/sp-github-update-' . time();
            $wp_filesystem->mkdir($temp_dir);
            
            // Unzip the file
            $unzip_result = unzip_file($temp_file, $temp_dir);
            
            // Delete the temp zip file
            @unlink($temp_file);
            
            if (is_wp_error($unzip_result)) {
                $wp_filesystem->delete($temp_dir, true);
                wp_send_json_error('Unzip failed: ' . $unzip_result->get_error_message());
            }
            
            // Find the extracted folder (GitHub creates weird folder names)
            $extracted_folders = $wp_filesystem->dirlist($temp_dir);
            $source_folder = null;
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('SP Direct Update: Found folders in temp: ' . print_r(array_keys($extracted_folders), true));
            }
            
            // First try: look for folder containing Saint-Porphyrius (case insensitive)
            foreach ($extracted_folders as $name => $info) {
                if ($info['type'] === 'd' && stripos($name, 'Saint-Porphyrius') !== false) {
                    $source_folder = $temp_dir . '/' . $name;
                    break;
                }
            }
            
            // Second try: look for folder containing micbwilliam (GitHub owner name)
            if (!$source_folder) {
                foreach ($extracted_folders as $name => $info) {
                    if ($info['type'] === 'd' && stripos($name, 'micbwilliam') !== false) {
                        $source_folder = $temp_dir . '/' . $name;
                        break;
                    }
                }
            }
            
            // Third try: use the first directory that contains saint-porphyrius.php
            if (!$source_folder) {
                foreach ($extracted_folders as $name => $info) {
                    if ($info['type'] === 'd') {
                        $potential = $temp_dir . '/' . $name;
                        if ($wp_filesystem->exists($potential . '/saint-porphyrius.php')) {
                            $source_folder = $potential;
                            break;
                        }
                    }
                }
            }
            
            // Last resort: use the first directory
            if (!$source_folder) {
                foreach ($extracted_folders as $name => $info) {
                    if ($info['type'] === 'd') {
                        $source_folder = $temp_dir . '/' . $name;
                        break;
                    }
                }
            }
            
            if (!$source_folder || !$wp_filesystem->exists($source_folder)) {
                $wp_filesystem->delete($temp_dir, true);
                wp_send_json_error('Could not find extracted plugin folder');
            }
            
            // Verify plugin file exists in source
            if (!$wp_filesystem->exists($source_folder . '/saint-porphyrius.php')) {
                $wp_filesystem->delete($temp_dir, true);
                wp_send_json_error('Invalid plugin archive - main plugin file not found');
            }
            
            // Deactivate the plugin first
            deactivate_plugins($this->plugin_basename, true);
            
            // Use hardcoded correct folder name to avoid any slug calculation issues
            $plugin_dir = WP_PLUGIN_DIR . '/Saint-Porphyrius';
            $backup_dir = WP_CONTENT_DIR . '/upgrade/sp-backup-' . time();
            
            if ($wp_filesystem->exists($plugin_dir)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('SP Direct Update: Backing up existing plugin to ' . $backup_dir);
                }
                $wp_filesystem->move($plugin_dir, $backup_dir);
            }
            
            // Move new files to plugin directory
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('SP Direct Update: Moving ' . $source_folder . ' to ' . $plugin_dir);
            }
            $move_result = $wp_filesystem->move($source_folder, $plugin_dir);
            
            if (!$move_result) {
                // Restore backup on failure
                if ($wp_filesystem->exists($backup_dir)) {
                    $wp_filesystem->move($backup_dir, $plugin_dir);
                    // Try to reactivate
                    activate_plugin($this->plugin_basename);
                }
                $wp_filesystem->delete($temp_dir, true);
                wp_send_json_error('Failed to move plugin files');
            }
            
            // Clean up
            $wp_filesystem->delete($temp_dir, true);
            if ($wp_filesystem->exists($backup_dir)) {
                $wp_filesystem->delete($backup_dir, true);
            }
            
            // Reactivate the plugin
            $activate_result = activate_plugin($this->plugin_basename);
            
            if (is_wp_error($activate_result)) {
                // Plugin was updated but failed to activate - still success but warn user
                delete_transient($this->transient_key);
                delete_site_transient('update_plugins');
                wp_clean_plugins_cache();
                
                $github_version = ltrim($release['tag_name'] ?? '', 'v');
                wp_send_json_success('Plugin updated to version ' . $github_version . ' but automatic activation failed. Please activate manually from the Plugins page.');
            }
            
            // Clear all caches
            delete_transient($this->transient_key);
            delete_site_transient('update_plugins');
            wp_clean_plugins_cache();
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
            $github_version = ltrim($release['tag_name'] ?? '', 'v');
            wp_send_json_success('Plugin updated successfully to version ' . $github_version . ' via direct download');
            
        } catch (Exception $e) {
            wp_send_json_error('Exception: ' . $e->getMessage());
        }
    }

    /**
     * Show update notice in admin
     */
    public function update_notice() {
        if (!current_user_can('update_plugins')) {
            return;
        }
        
        // Don't show on our updates page
        if (isset($_GET['page']) && $_GET['page'] === 'sp-updates') {
            return;
        }

        $release = $this->get_github_release();
        
        if (isset($release['error'])) {
            return;
        }

        $plugin_data = $this->get_plugin_data();
        $current_version = $plugin_data['Version'] ?? '0.0.0';
        $github_version = ltrim($release['tag_name'] ?? '', 'v');

        if (version_compare($github_version, $current_version, '>')) {
            $update_url = admin_url('admin.php?page=sp-updates');
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong>Saint Porphyrius</strong> - A new version (<?php echo esc_html($github_version); ?>) is available! 
                    You are running version <?php echo esc_html($current_version); ?>.
                    <a href="<?php echo esc_url($update_url); ?>">View details and update</a>
                </p>
            </div>
            <?php
        }
    }
}

// Initialize the updater
new SP_Updater();
