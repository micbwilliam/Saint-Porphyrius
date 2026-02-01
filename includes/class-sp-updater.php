<?php
/**
 * GitHub Plugin Updater
 * 
 * Handles automatic updates from GitHub releases
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Updater {
    private $plugin_slug;
    private $plugin_file;
    private $github_repo;
    private $github_api_url;
    private $plugin_data;
    private $github_response;
    private $transient_key = 'sp_github_update_check';
    private $transient_expiry = 43200; // 12 hours

    public function __construct() {
        $this->plugin_file = 'Saint-Porphyrius/saint-porphyrius.php';
        $this->plugin_slug = 'Saint-Porphyrius';
        $this->github_repo = 'micbwilliam/Saint-Porphyrius';
        $this->github_api_url = 'https://api.github.com/repos/' . $this->github_repo;

        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
        
        // Admin menu for update settings
        add_action('admin_menu', array($this, 'add_update_menu'), 99);
        
        // AJAX handlers
        add_action('wp_ajax_sp_check_updates', array($this, 'ajax_check_updates'));
        add_action('wp_ajax_sp_force_update', array($this, 'ajax_force_update'));
        add_action('wp_ajax_sp_clear_update_cache', array($this, 'ajax_clear_cache'));
        
        // Add update notice
        add_action('admin_notices', array($this, 'update_notice'));
    }

    /**
     * Get plugin data
     */
    private function get_plugin_data() {
        if (empty($this->plugin_data)) {
            $plugin_file = WP_PLUGIN_DIR . '/' . $this->plugin_file;
            if (file_exists($plugin_file)) {
                // Force refresh by not using cache
                $this->plugin_data = get_plugin_data($plugin_file, false, false);
                
                // Fallback to constant if version is missing
                if (empty($this->plugin_data['Version'])) {
                    $this->plugin_data['Version'] = defined('SP_PLUGIN_VERSION') ? SP_PLUGIN_VERSION : '0.0.0';
                }
            }
        }
        return $this->plugin_data;
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
     * Check for plugin updates
     */
    public function check_for_update($transient) {
        // Ensure transient is an object
        if (!is_object($transient)) {
            $transient = new stdClass();
        }
        
        if (empty($transient->checked)) {
            $transient->checked = array();
        }

        $release = $this->get_github_release();
        
        if (isset($release['error'])) {
            return $transient;
        }

        $plugin_data = $this->get_plugin_data();
        $current_version = $plugin_data['Version'] ?? '0.0.0';
        $github_version = ltrim($release['tag_name'] ?? '', 'v');

        if (version_compare($github_version, $current_version, '>')) {
            $download_url = $release['zipball_url'] ?? '';
            
            // Check for uploaded asset first
            if (!empty($release['assets'])) {
                foreach ($release['assets'] as $asset) {
                    if (strpos($asset['name'], '.zip') !== false) {
                        $download_url = $asset['browser_download_url'];
                        break;
                    }
                }
            }

            if (!isset($transient->response)) {
                $transient->response = array();
            }

            $transient->response[$this->plugin_file] = (object) array(
                'slug' => $this->plugin_slug,
                'plugin' => $this->plugin_file,
                'new_version' => $github_version,
                'url' => $release['html_url'] ?? '',
                'package' => $download_url,
                'icons' => array(),
                'banners' => array(),
                'requires' => '5.0',
                'tested' => get_bloginfo('version'),
                'requires_php' => '7.4'
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

        if ($args->slug !== $this->plugin_slug) {
            return $result;
        }

        $release = $this->get_github_release();
        $repo_info = $this->get_repo_info();
        $plugin_data = $this->get_plugin_data();

        if (isset($release['error'])) {
            return $result;
        }

        $github_version = ltrim($release['tag_name'] ?? '', 'v');

        return (object) array(
            'name' => $plugin_data['Name'] ?? 'Saint Porphyrius',
            'slug' => $this->plugin_slug,
            'version' => $github_version,
            'author' => $plugin_data['Author'] ?? '',
            'author_profile' => 'https://github.com/micbwilliam',
            'homepage' => $release['html_url'] ?? '',
            'short_description' => $plugin_data['Description'] ?? '',
            'sections' => array(
                'description' => $plugin_data['Description'] ?? '',
                'changelog' => $this->format_changelog($release['body'] ?? ''),
                'installation' => 'Upload the plugin files to the `/wp-content/plugins/Saint-Porphyrius` directory, or install the plugin through the WordPress plugins screen directly.'
            ),
            'download_link' => $release['zipball_url'] ?? '',
            'last_updated' => $release['published_at'] ?? '',
            'requires' => '5.0',
            'tested' => get_bloginfo('version'),
            'requires_php' => '7.4',
            'downloaded' => $repo_info['watchers_count'] ?? 0,
            'active_installs' => $repo_info['stargazers_count'] ?? 0
        );
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
     * After installation, rename folder if needed
     */
    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;

        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->plugin_file) {
            return $result;
        }

        $plugin_folder = WP_PLUGIN_DIR . '/' . $this->plugin_slug;
        
        // GitHub creates folder with repo-branch format, rename it
        if ($result['destination'] !== $plugin_folder) {
            $wp_filesystem->move($result['destination'], $plugin_folder);
            $result['destination'] = $plugin_folder;
        }

        // Reactivate plugin
        activate_plugin($this->plugin_file);

        return $result;
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
                    <div class="sp-update-card <?php echo $release['error'] === 'no_releases' ? 'sp-info-card' : 'sp-error-card'; ?>">
                        <h2>
                            <span class="dashicons dashicons-<?php echo $release['error'] === 'no_releases' ? 'info' : 'warning'; ?>"></span>
                            <?php echo $release['error'] === 'no_releases' ? 'No Releases Available' : 'Error'; ?>
                        </h2>
                        <p><?php echo esc_html($release['message'] ?? $release['error']); ?></p>
                        <?php if ($release['error'] === 'no_releases'): ?>
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
                        <span class="dashicons dashicons-info"></span>
                        Manual Update Instructions
                    </h2>
                    <p><strong>If the update doesn't appear in WordPress Updates page, use this method:</strong></p>
                    <ol>
                        <li>Click the button below to directly update via WordPress's built-in upgrader</li>
                        <li>Or manually download and upload the ZIP file to your plugins directory</li>
                    </ol>
                    <button type="button" class="button button-primary button-hero" id="sp-direct-update">
                        <span class="dashicons dashicons-update"></span>
                        Update Now (Direct)
                    </button>
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
        </style>

        <script>
        jQuery(document).ready(function($) {
            $('#sp-check-updates').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true).text('Checking...');
                
                $.post(ajaxurl, {
                    action: 'sp_check_updates',
                    nonce: '<?php echo wp_create_nonce('sp_update_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Check for Updates');
                    }
                });
            });
            
            $('#sp-clear-cache').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true).text('Clearing...');
                
                $.post(ajaxurl, {
                    action: 'sp_clear_update_cache',
                    nonce: '<?php echo wp_create_nonce('sp_update_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('Cache cleared successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> Clear Cache');
                });
            });
            
            $('#sp-install-update').on('click', function() {
                if (confirm('Are you sure you want to update the plugin? Make sure to backup your site first.')) {
                    window.location.href = '<?php echo esc_url(admin_url('update-core.php')); ?>';
                }
            });
            
            $('#sp-direct-update').on('click', function() {
                if (confirm('This will directly update the plugin using WordPress upgrader. Backup your site first. Continue?')) {
                    var $btn = $(this);
                    $btn.prop('disabled', true).html('<span class="spinner" style="display:inline-block; margin-right:5px;"></span>Updating...');
                    
                    $.post(ajaxurl, {
                        action: 'sp_force_update',
                        nonce: '<?php echo wp_create_nonce('sp_update_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            alert('Plugin updated successfully! Page will reload...');
                            location.reload();
                        } else {
                            alert('Update failed: ' + (response.data || 'Unknown error'));
                            $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Update Now (Direct)');
                        }
                    }).fail(function() {
                        alert('Connection error during update');
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Update Now (Direct)');
                    });
                }
            });
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

        delete_transient($this->transient_key);
        $release = $this->get_github_release(true);
        
        if (isset($release['error'])) {
            wp_send_json_error($release['error']);
        }
        
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
        
        wp_send_json_success();
    }

    /**
     * AJAX: Force update
     */
    public function ajax_force_update() {
        check_ajax_referer('sp_update_nonce', 'nonce');
        
        if (!current_user_can('update_plugins')) {
            wp_send_json_error('Unauthorized');
        }

        // Get the latest release and update info
        $release = $this->get_github_release();
        
        if (isset($release['error'])) {
            wp_send_json_error('Failed to get release information');
        }

        // Get download URL
        $download_url = $release['zipball_url'] ?? '';
        
        // Check for uploaded asset first
        if (!empty($release['assets'])) {
            foreach ($release['assets'] as $asset) {
                if (strpos($asset['name'], '.zip') !== false) {
                    $download_url = $asset['browser_download_url'];
                    break;
                }
            }
        }

        if (empty($download_url)) {
            wp_send_json_error('No download URL found');
        }

        // Include upgrader classes
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        
        // Create upgrader
        $upgrader = new Plugin_Upgrader();
        
        // Perform upgrade
        $result = $upgrader->upgrade($this->plugin_file);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        if ($result === false) {
            wp_send_json_error('Update failed. Please try again or update manually.');
        }
        
        wp_send_json_success('Plugin updated successfully');
    }

    /**
     * Show update notice in admin
     */
    public function update_notice() {
        if (!current_user_can('update_plugins')) {
            return;
        }

        $release = $this->get_github_release();
        
        if (isset($release['error'])) {
            return;
        }

        $plugin_data = $this->get_plugin_data();
        $current_version = $plugin_data['Version'] ?? '0.0.0';
        $github_version = ltrim($release['tag_name'] ?? '', 'v');

        // Debug: Log version checking (remove in production)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SP Update Check - Current: ' . $current_version . ', GitHub: ' . $github_version . ', Comparison: ' . version_compare($github_version, $current_version, '>'));
        }

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
