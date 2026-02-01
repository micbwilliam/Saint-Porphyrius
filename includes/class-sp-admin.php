<?php
/**
 * Saint Porphyrius - Admin Panel
 * WordPress admin interface for managing the app
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Admin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('Saint Porphyrius', 'saint-porphyrius'),
            __('Saint Porphyrius', 'saint-porphyrius'),
            'manage_options',
            'saint-porphyrius',
            array($this, 'render_dashboard_page'),
            'dashicons-groups',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'saint-porphyrius',
            __('Dashboard', 'saint-porphyrius'),
            __('Dashboard', 'saint-porphyrius'),
            'manage_options',
            'saint-porphyrius',
            array($this, 'render_dashboard_page')
        );
        
        // Pending approvals submenu
        $pending_count = $this->get_pending_count();
        $pending_label = __('Pending Approvals', 'saint-porphyrius');
        if ($pending_count > 0) {
            $pending_label .= ' <span class="awaiting-mod">' . $pending_count . '</span>';
        }
        
        add_submenu_page(
            'saint-porphyrius',
            __('Pending Approvals', 'saint-porphyrius'),
            $pending_label,
            'manage_options',
            'saint-porphyrius-pending',
            array($this, 'render_pending_page')
        );
        
        // Members submenu
        add_submenu_page(
            'saint-porphyrius',
            __('Members', 'saint-porphyrius'),
            __('Members', 'saint-porphyrius'),
            'manage_options',
            'saint-porphyrius-members',
            array($this, 'render_members_page')
        );
        
        // Event Types submenu
        add_submenu_page(
            'saint-porphyrius',
            __('Event Types', 'saint-porphyrius'),
            __('Event Types', 'saint-porphyrius'),
            'manage_options',
            'saint-porphyrius-event-types',
            array($this, 'render_event_types_page')
        );
        
        // Events submenu
        add_submenu_page(
            'saint-porphyrius',
            __('Events', 'saint-porphyrius'),
            __('Events', 'saint-porphyrius'),
            'manage_options',
            'saint-porphyrius-events',
            array($this, 'render_events_page')
        );
        
        // Attendance submenu
        add_submenu_page(
            'saint-porphyrius',
            __('Attendance', 'saint-porphyrius'),
            __('Attendance', 'saint-porphyrius'),
            'manage_options',
            'saint-porphyrius-attendance',
            array($this, 'render_attendance_page')
        );
        
        // Excuses submenu
        $excuses_handler = SP_Excuses::get_instance();
        $pending_excuses = $excuses_handler->count_pending();
        $excuses_label = __('Excuses', 'saint-porphyrius');
        if ($pending_excuses > 0) {
            $excuses_label .= ' <span class="awaiting-mod">' . $pending_excuses . '</span>';
        }
        
        add_submenu_page(
            'saint-porphyrius',
            __('Excuses', 'saint-porphyrius'),
            $excuses_label,
            'manage_options',
            'saint-porphyrius-excuses',
            array($this, 'render_excuses_page')
        );
        
        // Points submenu
        add_submenu_page(
            'saint-porphyrius',
            __('Points & Rewards', 'saint-porphyrius'),
            __('Points & Rewards', 'saint-porphyrius'),
            'manage_options',
            'saint-porphyrius-points',
            array($this, 'render_points_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'saint-porphyrius',
            __('Settings', 'saint-porphyrius'),
            __('Settings', 'saint-porphyrius'),
            'manage_options',
            'saint-porphyrius-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Get pending approvals count
     */
    private function get_pending_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sp_pending_users';
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'");
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('sp_settings', 'sp_church_name');
        register_setting('sp_settings', 'sp_admin_email');
        register_setting('sp_settings', 'sp_approval_email_enabled');
        register_setting('sp_settings', 'sp_rejection_email_enabled');
    }
    
    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        $pending_count = $this->get_pending_count();
        $members_count = $this->get_members_count();
        ?>
        <div class="wrap sp-admin-wrap">
            <h1><?php _e('Saint Porphyrius Dashboard', 'saint-porphyrius'); ?></h1>
            
            <div class="sp-admin-stats">
                <div class="sp-stat-card">
                    <div class="sp-stat-icon pending">
                        <span class="dashicons dashicons-clock"></span>
                    </div>
                    <div class="sp-stat-content">
                        <span class="sp-stat-number"><?php echo esc_html($pending_count); ?></span>
                        <span class="sp-stat-label"><?php _e('Pending Approvals', 'saint-porphyrius'); ?></span>
                    </div>
                    <?php if ($pending_count > 0): ?>
                    <a href="<?php echo admin_url('admin.php?page=saint-porphyrius-pending'); ?>" class="sp-stat-action">
                        <?php _e('Review', 'saint-porphyrius'); ?> →
                    </a>
                    <?php endif; ?>
                </div>
                
                <div class="sp-stat-card">
                    <div class="sp-stat-icon members">
                        <span class="dashicons dashicons-groups"></span>
                    </div>
                    <div class="sp-stat-content">
                        <span class="sp-stat-number"><?php echo esc_html($members_count); ?></span>
                        <span class="sp-stat-label"><?php _e('Total Members', 'saint-porphyrius'); ?></span>
                    </div>
                    <a href="<?php echo admin_url('admin.php?page=saint-porphyrius-members'); ?>" class="sp-stat-action">
                        <?php _e('View All', 'saint-porphyrius'); ?> →
                    </a>
                </div>
            </div>
            
            <div class="sp-admin-quick-links">
                <h2><?php _e('Quick Links', 'saint-porphyrius'); ?></h2>
                <div class="sp-quick-links-grid">
                    <a href="<?php echo home_url('/app'); ?>" target="_blank" class="sp-quick-link">
                        <span class="dashicons dashicons-smartphone"></span>
                        <?php _e('View App', 'saint-porphyrius'); ?>
                    </a>
                    <a href="<?php echo home_url('/app/register'); ?>" target="_blank" class="sp-quick-link">
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php _e('Registration Page', 'saint-porphyrius'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=saint-porphyrius-settings'); ?>" class="sp-quick-link">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <?php _e('Settings', 'saint-porphyrius'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get members count
     */
    private function get_members_count() {
        $members = get_users(array(
            'role__in' => array('sp_member', 'sp_church_admin'),
            'count_total' => true,
        ));
        return count($members);
    }
    
    /**
     * Render pending approvals page
     */
    public function render_pending_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sp_pending_users';
        
        // Handle actions
        if (isset($_GET['action']) && isset($_GET['id'])) {
            $action = sanitize_text_field($_GET['action']);
            $id = intval($_GET['id']);
            
            if (wp_verify_nonce($_GET['_wpnonce'], 'sp_pending_action')) {
                $registration = SP_Registration::get_instance();
                
                if ($action === 'approve') {
                    $result = $registration->approve_user($id, get_current_user_id());
                    if (!is_wp_error($result)) {
                        echo '<div class="notice notice-success"><p>' . __('User approved successfully!', 'saint-porphyrius') . '</p></div>';
                    }
                } elseif ($action === 'reject') {
                    $result = $registration->reject_user($id, get_current_user_id());
                    if (!is_wp_error($result)) {
                        echo '<div class="notice notice-warning"><p>' . __('User rejected.', 'saint-porphyrius') . '</p></div>';
                    }
                }
            }
        }
        
        // Get pending users
        $pending_users = $wpdb->get_results("SELECT * FROM $table_name WHERE status = 'pending' ORDER BY created_at DESC");
        ?>
        <div class="wrap sp-admin-wrap">
            <h1><?php _e('Pending Approvals', 'saint-porphyrius'); ?></h1>
            
            <?php if (empty($pending_users)): ?>
                <div class="sp-empty-state">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <h3><?php _e('No pending approvals', 'saint-porphyrius'); ?></h3>
                    <p><?php _e('All registration requests have been processed.', 'saint-porphyrius'); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped sp-pending-table">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'saint-porphyrius'); ?></th>
                            <th><?php _e('Email', 'saint-porphyrius'); ?></th>
                            <th><?php _e('Phone', 'saint-porphyrius'); ?></th>
                            <th><?php _e('Church', 'saint-porphyrius'); ?></th>
                            <th><?php _e('Date', 'saint-porphyrius'); ?></th>
                            <th><?php _e('Actions', 'saint-porphyrius'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_users as $user): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name); ?></strong>
                                    <br>
                                    <a href="#"
                                       class="sp-view-details"
                                       data-id="<?php echo esc_attr($user->id); ?>"
                                       data-name="<?php echo esc_attr($user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name); ?>"
                                       data-email="<?php echo esc_attr($user->email); ?>"
                                       data-phone="<?php echo esc_attr($user->phone); ?>"
                                       data-home-address="<?php echo esc_attr($user->home_address); ?>"
                                       data-church-name="<?php echo esc_attr($user->church_name); ?>"
                                       data-confession-father="<?php echo esc_attr($user->confession_father); ?>"
                                       data-job-or-college="<?php echo esc_attr($user->job_or_college); ?>"
                                       data-current-church-service="<?php echo esc_attr($user->current_church_service); ?>"
                                       data-church-family="<?php echo esc_attr($user->church_family); ?>"
                                       data-church-family-servant="<?php echo esc_attr($user->church_family_servant); ?>"
                                       data-facebook-link="<?php echo esc_attr($user->facebook_link); ?>"
                                       data-instagram-link="<?php echo esc_attr($user->instagram_link); ?>"
                                       data-created-at="<?php echo esc_attr(date_i18n(get_option('date_format'), strtotime($user->created_at))); ?>"
                                       data-approve-url="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=saint-porphyrius-pending&action=approve&id=' . $user->id), 'sp_pending_action')); ?>"
                                       data-reject-url="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=saint-porphyrius-pending&action=reject&id=' . $user->id), 'sp_pending_action')); ?>">
                                        <?php _e('View Details', 'saint-porphyrius'); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($user->email); ?></td>
                                <td><?php echo esc_html($user->phone); ?></td>
                                <td><?php echo esc_html($user->church_name); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($user->created_at))); ?></td>
                                <td>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=saint-porphyrius-pending&action=approve&id=' . $user->id), 'sp_pending_action'); ?>" 
                                       class="button button-primary sp-approve-btn">
                                        <?php _e('Approve', 'saint-porphyrius'); ?>
                                    </a>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=saint-porphyrius-pending&action=reject&id=' . $user->id), 'sp_pending_action'); ?>" 
                                       class="button sp-reject-btn"
                                       onclick="return confirm('<?php _e('Are you sure you want to reject this user?', 'saint-porphyrius'); ?>');">
                                        <?php _e('Reject', 'saint-porphyrius'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="sp-modal" id="sp-pending-modal" aria-hidden="true">
            <div class="sp-modal-overlay" data-close="true"></div>
            <div class="sp-modal-content" role="dialog" aria-modal="true" aria-labelledby="sp-modal-title">
                <button type="button" class="sp-modal-close" data-close="true" aria-label="<?php esc_attr_e('Close', 'saint-porphyrius'); ?>">&times;</button>
                <h2 id="sp-modal-title"><?php _e('Pending Member Details', 'saint-porphyrius'); ?></h2>

                <div class="sp-modal-grid">
                    <div class="sp-modal-item"><label><?php _e('Name', 'saint-porphyrius'); ?></label><span data-field="name"></span></div>
                    <div class="sp-modal-item"><label><?php _e('Email', 'saint-porphyrius'); ?></label><span data-field="email"></span></div>
                    <div class="sp-modal-item"><label><?php _e('Phone', 'saint-porphyrius'); ?></label><span data-field="phone"></span></div>
                    <div class="sp-modal-item"><label><?php _e('Home Address', 'saint-porphyrius'); ?></label><span data-field="home_address"></span></div>
                    <div class="sp-modal-item"><label><?php _e('Church', 'saint-porphyrius'); ?></label><span data-field="church_name"></span></div>
                    <div class="sp-modal-item"><label><?php _e('Confession Father (أب الاعتراف)', 'saint-porphyrius'); ?></label><span data-field="confession_father"></span></div>
                    <div class="sp-modal-item"><label><?php _e('Job/College', 'saint-porphyrius'); ?></label><span data-field="job_or_college"></span></div>
                    <div class="sp-modal-item"><label><?php _e('Current Church Service (الخدمة الحالية بالكنيسة)', 'saint-porphyrius'); ?></label><span data-field="current_church_service"></span></div>
                    <div class="sp-modal-item"><label><?php _e('Church Family (الأسرة بالكنيسة)', 'saint-porphyrius'); ?></label><span data-field="church_family"></span></div>
                    <div class="sp-modal-item"><label><?php _e('Church Family Servant (خادم الأسرة بالكنيسة)', 'saint-porphyrius'); ?></label><span data-field="church_family_servant"></span></div>
                    <div class="sp-modal-item"><label><?php _e('Facebook', 'saint-porphyrius'); ?></label><span data-field="facebook_link"></span></div>
                    <div class="sp-modal-item"><label><?php _e('Instagram', 'saint-porphyrius'); ?></label><span data-field="instagram_link"></span></div>
                    <div class="sp-modal-item"><label><?php _e('Date', 'saint-porphyrius'); ?></label><span data-field="created_at"></span></div>
                </div>

                <div class="sp-modal-actions">
                    <a href="#" class="button button-primary sp-modal-approve"><?php _e('Approve', 'saint-porphyrius'); ?></a>
                    <a href="#" class="button sp-modal-reject"><?php _e('Reject', 'saint-porphyrius'); ?></a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render members page
     */
    public function render_members_page() {
        $members = get_users(array(
            'role__in' => array('sp_member', 'sp_church_admin'),
            'orderby' => 'registered',
            'order' => 'DESC',
        ));
        ?>
        <div class="wrap sp-admin-wrap">
            <h1><?php _e('Church Members', 'saint-porphyrius'); ?></h1>
            
            <?php if (empty($members)): ?>
                <div class="sp-empty-state">
                    <span class="dashicons dashicons-admin-users"></span>
                    <h3><?php _e('No members yet', 'saint-porphyrius'); ?></h3>
                    <p><?php _e('Approved users will appear here.', 'saint-porphyrius'); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'saint-porphyrius'); ?></th>
                            <th><?php _e('Email', 'saint-porphyrius'); ?></th>
                            <th><?php _e('Phone', 'saint-porphyrius'); ?></th>
                            <th><?php _e('Church', 'saint-porphyrius'); ?></th>
                            <th><?php _e('Registered', 'saint-porphyrius'); ?></th>
                            <th><?php _e('Last Login', 'saint-porphyrius'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <?php echo esc_html($member->first_name . ' ' . get_user_meta($member->ID, 'sp_middle_name', true) . ' ' . $member->last_name); ?>
                                    </strong>
                                    <br>
                                    <a href="<?php echo get_edit_user_link($member->ID); ?>">
                                        <?php _e('Edit', 'saint-porphyrius'); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($member->user_email); ?></td>
                                <td><?php echo esc_html(get_user_meta($member->ID, 'sp_phone', true)); ?></td>
                                <td><?php echo esc_html(get_user_meta($member->ID, 'sp_church_name', true)); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($member->user_registered))); ?></td>
                                <td>
                                    <?php 
                                    $last_login = get_user_meta($member->ID, 'sp_last_login', true);
                                    echo $last_login ? esc_html(date_i18n(get_option('date_format'), strtotime($last_login))) : '-';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        
        // Handle migration actions
        if ($active_tab === 'database' && isset($_POST['sp_migration_action'])) {
            check_admin_referer('sp_migration_action');
            
            $migrator = SP_Migrator::get_instance();
            $action = sanitize_text_field($_POST['sp_migration_action']);
            
            if ($action === 'run') {
                $result = $migrator->run();
                if ($result['success']) {
                    add_settings_error('sp_migrations', 'success', $result['message'], 'success');
                } else {
                    add_settings_error('sp_migrations', 'error', $result['message'], 'error');
                }
            } elseif ($action === 'rollback') {
                $result = $migrator->rollback();
                if ($result['success']) {
                    add_settings_error('sp_migrations', 'success', $result['message'], 'success');
                } else {
                    add_settings_error('sp_migrations', 'error', $result['message'], 'error');
                }
            }
        }
        ?>
        <div class="wrap sp-admin-wrap">
            <h1><?php _e('Saint Porphyrius Settings', 'saint-porphyrius'); ?></h1>
            
            <nav class="nav-tab-wrapper">
                <a href="<?php echo admin_url('admin.php?page=saint-porphyrius-settings&tab=general'); ?>" 
                   class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('General', 'saint-porphyrius'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=saint-porphyrius-settings&tab=database'); ?>" 
                   class="nav-tab <?php echo $active_tab === 'database' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Database', 'saint-porphyrius'); ?>
                </a>
            </nav>
            
            <div class="sp-settings-content" style="margin-top: 20px;">
                <?php
                if ($active_tab === 'database') {
                    $this->render_database_tab();
                } else {
                    $this->render_general_tab();
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render general settings tab
     */
    private function render_general_tab() {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('sp_settings'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="sp_church_name"><?php _e('Church Name', 'saint-porphyrius'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="sp_church_name" name="sp_church_name" 
                               value="<?php echo esc_attr(get_option('sp_church_name', '')); ?>" 
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="sp_admin_email"><?php _e('Admin Notification Email', 'saint-porphyrius'); ?></label>
                    </th>
                    <td>
                        <input type="email" id="sp_admin_email" name="sp_admin_email" 
                               value="<?php echo esc_attr(get_option('sp_admin_email', get_option('admin_email'))); ?>" 
                               class="regular-text">
                        <p class="description"><?php _e('Email to receive new registration notifications.', 'saint-porphyrius'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Email Notifications', 'saint-porphyrius'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="sp_approval_email_enabled" value="1" 
                                   <?php checked(get_option('sp_approval_email_enabled', 1), 1); ?>>
                            <?php _e('Send email when user is approved', 'saint-porphyrius'); ?>
                        </label>
                        <br><br>
                        <label>
                            <input type="checkbox" name="sp_rejection_email_enabled" value="1" 
                                   <?php checked(get_option('sp_rejection_email_enabled', 1), 1); ?>>
                            <?php _e('Send email when user is rejected', 'saint-porphyrius'); ?>
                        </label>
                    </td>
                </tr>
            </table>
            
            <h2><?php _e('App URLs', 'saint-porphyrius'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('App Home', 'saint-porphyrius'); ?></th>
                    <td><code><?php echo home_url('/app'); ?></code></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Login Page', 'saint-porphyrius'); ?></th>
                    <td><code><?php echo home_url('/app/login'); ?></code></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Registration Page', 'saint-porphyrius'); ?></th>
                    <td><code><?php echo home_url('/app/register'); ?></code></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Dashboard', 'saint-porphyrius'); ?></th>
                    <td><code><?php echo home_url('/app/dashboard'); ?></code></td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
        <?php
    }
    
    /**
     * Render database settings tab
     */
    private function render_database_tab() {
        $migrator = SP_Migrator::get_instance();
        $status = $migrator->get_detailed_status();
        $tables = $migrator->get_tables_status();
        
        settings_errors('sp_migrations');
        ?>
        
        <!-- Migration Status Overview -->
        <div class="sp-admin-stats" style="margin-bottom: 30px;">
            <div class="sp-stat-card">
                <div class="sp-stat-icon <?php echo $status['pending'] === 0 ? 'success' : 'warning'; ?>">
                    <span class="dashicons dashicons-database"></span>
                </div>
                <div class="sp-stat-content">
                    <span class="sp-stat-number"><?php echo esc_html($status['executed']); ?> / <?php echo esc_html($status['total']); ?></span>
                    <span class="sp-stat-label"><?php _e('Migrations Executed', 'saint-porphyrius'); ?></span>
                </div>
            </div>
            
            <div class="sp-stat-card">
                <div class="sp-stat-icon <?php echo $status['pending'] > 0 ? 'warning' : 'success'; ?>">
                    <span class="dashicons dashicons-<?php echo $status['pending'] > 0 ? 'warning' : 'yes-alt'; ?>"></span>
                </div>
                <div class="sp-stat-content">
                    <span class="sp-stat-number"><?php echo esc_html($status['pending']); ?></span>
                    <span class="sp-stat-label"><?php _e('Pending Migrations', 'saint-porphyrius'); ?></span>
                </div>
            </div>
            
            <div class="sp-stat-card">
                <div class="sp-stat-icon members">
                    <span class="dashicons dashicons-update"></span>
                </div>
                <div class="sp-stat-content">
                    <span class="sp-stat-number"><?php echo esc_html($status['current_batch']); ?></span>
                    <span class="sp-stat-label"><?php _e('Current Batch', 'saint-porphyrius'); ?></span>
                </div>
            </div>
        </div>
        
        <div class="sp-admin-grid">
            <!-- Migration Actions -->
            <div class="sp-admin-card">
                <h2><?php _e('Migration Actions', 'saint-porphyrius'); ?></h2>
                
                <?php if ($status['pending'] > 0): ?>
                    <div class="sp-notice sp-notice-warning" style="margin-bottom: 15px;">
                        <p>
                            <strong><?php _e('Database update required!', 'saint-porphyrius'); ?></strong><br>
                            <?php printf(__('There are %d pending migration(s) waiting to be executed.', 'saint-porphyrius'), $status['pending']); ?>
                        </p>
                    </div>
                    
                    <form method="post" style="margin-bottom: 15px;">
                        <?php wp_nonce_field('sp_migration_action'); ?>
                        <input type="hidden" name="sp_migration_action" value="run">
                        <button type="submit" class="button button-primary button-hero">
                            <span class="dashicons dashicons-update" style="margin-top: 4px;"></span>
                            <?php _e('Run Pending Migrations', 'saint-porphyrius'); ?>
                        </button>
                    </form>
                <?php else: ?>
                    <div class="sp-notice sp-notice-success" style="margin-bottom: 15px;">
                        <p>
                            <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                            <strong><?php _e('Database is up to date!', 'saint-porphyrius'); ?></strong><br>
                            <?php _e('All migrations have been executed successfully.', 'saint-porphyrius'); ?>
                        </p>
                    </div>
                <?php endif; ?>
                
                <?php if ($status['current_batch'] > 0): ?>
                    <hr style="margin: 20px 0;">
                    <h3><?php _e('Rollback', 'saint-porphyrius'); ?></h3>
                    <p class="description" style="margin-bottom: 10px;">
                        <?php _e('Rollback will undo the last batch of migrations. Use with caution!', 'saint-porphyrius'); ?>
                    </p>
                    <form method="post" onsubmit="return confirm('<?php _e('Are you sure you want to rollback the last batch? This may cause data loss!', 'saint-porphyrius'); ?>');">
                        <?php wp_nonce_field('sp_migration_action'); ?>
                        <input type="hidden" name="sp_migration_action" value="rollback">
                        <button type="submit" class="button button-secondary">
                            <span class="dashicons dashicons-undo" style="margin-top: 4px;"></span>
                            <?php printf(__('Rollback Batch %d', 'saint-porphyrius'), $status['current_batch']); ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            
            <!-- Database Tables -->
            <div class="sp-admin-card">
                <h2><?php _e('Database Tables', 'saint-porphyrius'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Table', 'saint-porphyrius'); ?></th>
                            <th style="width: 80px;"><?php _e('Status', 'saint-porphyrius'); ?></th>
                            <th style="width: 80px; text-align: right;"><?php _e('Rows', 'saint-porphyrius'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tables as $key => $table): ?>
                            <tr>
                                <td>
                                    <code style="font-size: 12px;"><?php echo esc_html($table['table']); ?></code>
                                </td>
                                <td>
                                    <?php if ($table['exists']): ?>
                                        <span class="sp-badge sp-badge-success"><?php _e('OK', 'saint-porphyrius'); ?></span>
                                    <?php else: ?>
                                        <span class="sp-badge sp-badge-danger"><?php _e('Missing', 'saint-porphyrius'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <?php echo $table['exists'] ? esc_html($table['rows']) : '-'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Migrations List -->
        <div class="sp-admin-card" style="margin-top: 24px;">
            <h2><?php _e('All Migrations', 'saint-porphyrius'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50%;"><?php _e('Migration', 'saint-porphyrius'); ?></th>
                        <th style="width: 15%;"><?php _e('Status', 'saint-porphyrius'); ?></th>
                        <th style="width: 10%;"><?php _e('Batch', 'saint-porphyrius'); ?></th>
                        <th style="width: 25%;"><?php _e('Executed At', 'saint-porphyrius'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($status['migrations'])): ?>
                        <tr>
                            <td colspan="4"><?php _e('No migrations found.', 'saint-porphyrius'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($status['migrations'] as $migration): ?>
                            <tr>
                                <td>
                                    <code style="font-size: 11px;"><?php echo esc_html($migration['name']); ?></code>
                                </td>
                                <td>
                                    <?php if ($migration['status'] === 'executed'): ?>
                                        <span class="sp-badge sp-badge-success"><?php _e('Executed', 'saint-porphyrius'); ?></span>
                                    <?php else: ?>
                                        <span class="sp-badge sp-badge-warning"><?php _e('Pending', 'saint-porphyrius'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $migration['batch'] ? esc_html($migration['batch']) : '-'; ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($migration['executed_at']) {
                                        echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($migration['executed_at'])));
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- System Info -->
        <div class="sp-admin-card" style="margin-top: 24px;">
            <h2><?php _e('System Information', 'saint-porphyrius'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Plugin Version', 'saint-porphyrius'); ?></th>
                    <td><code><?php echo esc_html(SP_PLUGIN_VERSION); ?></code></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('WordPress Version', 'saint-porphyrius'); ?></th>
                    <td><code><?php echo esc_html(get_bloginfo('version')); ?></code></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('PHP Version', 'saint-porphyrius'); ?></th>
                    <td><code><?php echo esc_html(phpversion()); ?></code></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('MySQL Version', 'saint-porphyrius'); ?></th>
                    <td><code><?php global $wpdb; echo esc_html($wpdb->db_version()); ?></code></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Migrations Path', 'saint-porphyrius'); ?></th>
                    <td><code style="font-size: 11px;"><?php echo esc_html(SP_PLUGIN_DIR . 'migrations/'); ?></code></td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render event types page
     */
    public function render_event_types_page() {
        $event_types = SP_Event_Types::get_instance();
        
        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sp_event_type_action'])) {
            check_admin_referer('sp_event_type_action');
            
            $action = sanitize_text_field($_POST['sp_event_type_action']);
            
            if ($action === 'create') {
                $result = $event_types->create($_POST);
                if (is_wp_error($result)) {
                    add_settings_error('sp_event_types', 'error', $result->get_error_message(), 'error');
                } else {
                    add_settings_error('sp_event_types', 'success', __('Event type created successfully.', 'saint-porphyrius'), 'success');
                }
            } elseif ($action === 'update' && !empty($_POST['type_id'])) {
                $result = $event_types->update(absint($_POST['type_id']), $_POST);
                if (is_wp_error($result)) {
                    add_settings_error('sp_event_types', 'error', $result->get_error_message(), 'error');
                } else {
                    add_settings_error('sp_event_types', 'success', __('Event type updated successfully.', 'saint-porphyrius'), 'success');
                }
            } elseif ($action === 'delete' && !empty($_POST['type_id'])) {
                $result = $event_types->delete(absint($_POST['type_id']));
                if (is_wp_error($result)) {
                    add_settings_error('sp_event_types', 'error', $result->get_error_message(), 'error');
                } else {
                    add_settings_error('sp_event_types', 'success', __('Event type deleted successfully.', 'saint-porphyrius'), 'success');
                }
            }
        }
        
        $types = $event_types->get_all();
        ?>
        <div class="wrap sp-admin-wrap">
            <h1><?php _e('Event Types', 'saint-porphyrius'); ?></h1>
            
            <?php settings_errors('sp_event_types'); ?>
            
            <div class="sp-admin-grid">
                <div class="sp-admin-card">
                    <h2><?php _e('Add New Event Type', 'saint-porphyrius'); ?></h2>
                    <form method="post" class="sp-form">
                        <?php wp_nonce_field('sp_event_type_action'); ?>
                        <input type="hidden" name="sp_event_type_action" value="create">
                        
                        <p>
                            <label><?php _e('Name (Arabic)', 'saint-porphyrius'); ?></label>
                            <input type="text" name="name_ar" required class="regular-text">
                        </p>
                        <p>
                            <label><?php _e('Name (English)', 'saint-porphyrius'); ?></label>
                            <input type="text" name="name_en" class="regular-text">
                        </p>
                        <p>
                            <label><?php _e('Icon', 'saint-porphyrius'); ?></label>
                            <select name="icon" class="regular-text sp-icon-select">
                                <option value="⛪">⛪ <?php _e('Church', 'saint-porphyrius'); ?></option>
                                <option value="📖">📖 <?php _e('Book/Bible', 'saint-porphyrius'); ?></option>
                                <option value="🙏">🙏 <?php _e('Prayer', 'saint-porphyrius'); ?></option>
                                <option value="❤️">❤️ <?php _e('Heart/Service', 'saint-porphyrius'); ?></option>
                                <option value="✝️">✝️ <?php _e('Cross', 'saint-porphyrius'); ?></option>
                                <option value="🕯️">🕯️ <?php _e('Candle', 'saint-porphyrius'); ?></option>
                                <option value="🎵">🎵 <?php _e('Music/Choir', 'saint-porphyrius'); ?></option>
                                <option value="👥">👥 <?php _e('Group/Meeting', 'saint-porphyrius'); ?></option>
                                <option value="🎉">🎉 <?php _e('Celebration', 'saint-porphyrius'); ?></option>
                                <option value="📚">📚 <?php _e('Study/Education', 'saint-porphyrius'); ?></option>
                                <option value="🏠">🏠 <?php _e('Home Visit', 'saint-porphyrius'); ?></option>
                                <option value="🌍">🌍 <?php _e('Mission/Outreach', 'saint-porphyrius'); ?></option>
                                <option value="🍞">🍞 <?php _e('Communion', 'saint-porphyrius'); ?></option>
                                <option value="💒">💒 <?php _e('Wedding', 'saint-porphyrius'); ?></option>
                                <option value="👶">👶 <?php _e('Baptism', 'saint-porphyrius'); ?></option>
                                <option value="⭐">⭐ <?php _e('Special Event', 'saint-porphyrius'); ?></option>
                            </select>
                        </p>
                        <p>
                            <label><?php _e('Color', 'saint-porphyrius'); ?></label>
                            <input type="color" name="color" value="#6C9BCF">
                        </p>
                        <p>
                            <label><?php _e('Attendance Points', 'saint-porphyrius'); ?></label>
                            <input type="number" name="attendance_points" value="10" min="0" class="small-text">
                        </p>
                        <p>
                            <label><?php _e('Late Points', 'saint-porphyrius'); ?></label>
                            <input type="number" name="late_points" value="5" min="0" class="small-text">
                        </p>
                        <p>
                            <label><?php _e('Absence Penalty', 'saint-porphyrius'); ?></label>
                            <input type="number" name="absence_penalty" value="5" min="0" class="small-text">
                        </p>
                        
                        <hr style="margin: 20px 0;">
                        <h3><?php _e('Excuse Points (for Mandatory Events)', 'saint-porphyrius'); ?></h3>
                        <p class="description"><?php _e('Points deducted when submitting an excuse, based on days before the event.', 'saint-porphyrius'); ?></p>
                        
                        <div class="sp-excuse-points-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 15px;">
                            <p>
                                <label><?php _e('7+ days before', 'saint-porphyrius'); ?></label>
                                <input type="number" name="excuse_points_7plus" value="2" min="0" class="small-text">
                            </p>
                            <p>
                                <label><?php _e('6 days before', 'saint-porphyrius'); ?></label>
                                <input type="number" name="excuse_points_6" value="3" min="0" class="small-text">
                            </p>
                            <p>
                                <label><?php _e('5 days before', 'saint-porphyrius'); ?></label>
                                <input type="number" name="excuse_points_5" value="4" min="0" class="small-text">
                            </p>
                            <p>
                                <label><?php _e('4 days before', 'saint-porphyrius'); ?></label>
                                <input type="number" name="excuse_points_4" value="5" min="0" class="small-text">
                            </p>
                            <p>
                                <label><?php _e('3 days before', 'saint-porphyrius'); ?></label>
                                <input type="number" name="excuse_points_3" value="6" min="0" class="small-text">
                            </p>
                            <p>
                                <label><?php _e('2 days before', 'saint-porphyrius'); ?></label>
                                <input type="number" name="excuse_points_2" value="7" min="0" class="small-text">
                            </p>
                            <p>
                                <label><?php _e('1 day before', 'saint-porphyrius'); ?></label>
                                <input type="number" name="excuse_points_1" value="8" min="0" class="small-text">
                            </p>
                            <p>
                                <label><?php _e('Same day', 'saint-porphyrius'); ?></label>
                                <input type="number" name="excuse_points_0" value="10" min="0" class="small-text">
                            </p>
                        </div>
                        
                        <p>
                            <button type="submit" class="button button-primary"><?php _e('Add Event Type', 'saint-porphyrius'); ?></button>
                        </p>
                    </form>
                </div>
                
                <div class="sp-admin-card sp-admin-card-wide">
                    <h2><?php _e('Existing Event Types', 'saint-porphyrius'); ?></h2>
                    <?php if (empty($types)): ?>
                        <p><?php _e('No event types found.', 'saint-porphyrius'); ?></p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Icon', 'saint-porphyrius'); ?></th>
                                    <th><?php _e('Name (AR)', 'saint-porphyrius'); ?></th>
                                    <th><?php _e('Name (EN)', 'saint-porphyrius'); ?></th>
                                    <th><?php _e('Points', 'saint-porphyrius'); ?></th>
                                    <th><?php _e('Penalty', 'saint-porphyrius'); ?></th>
                                    <th><?php _e('Excuse (7+d)', 'saint-porphyrius'); ?></th>
                                    <th><?php _e('Excuse (0d)', 'saint-porphyrius'); ?></th>
                                    <th><?php _e('Actions', 'saint-porphyrius'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($types as $type): ?>
                                    <tr>
                                        <td style="color: <?php echo esc_attr($type->color); ?>; font-size: 24px;">
                                            <?php echo esc_html($type->icon); ?>
                                        </td>
                                        <td><?php echo esc_html($type->name_ar); ?></td>
                                        <td><?php echo esc_html($type->name_en); ?></td>
                                        <td>+<?php echo esc_html($type->attendance_points); ?> <span style="color:#666;font-size:0.9em;">(+<?php echo esc_html($type->late_points ?? floor($type->attendance_points/2)); ?> late)</span></td>
                                        <td>-<?php echo esc_html($type->absence_penalty); ?></td>
                                        <td>-<?php echo esc_html($type->excuse_points_7plus ?? 2); ?></td>
                                        <td>-<?php echo esc_html($type->excuse_points_0 ?? 10); ?></td>
                                        <td>
                                            <button type="button" class="button button-small sp-edit-type" 
                                                    data-id="<?php echo esc_attr($type->id); ?>"
                                                    data-name_ar="<?php echo esc_attr($type->name_ar); ?>"
                                                    data-name_en="<?php echo esc_attr($type->name_en); ?>"
                                                    data-icon="<?php echo esc_attr($type->icon); ?>"
                                                    data-color="<?php echo esc_attr($type->color); ?>"
                                                    data-attendance_points="<?php echo esc_attr($type->attendance_points); ?>"
                                                    data-late_points="<?php echo esc_attr($type->late_points ?? floor($type->attendance_points/2)); ?>"
                                                    data-absence_penalty="<?php echo esc_attr($type->absence_penalty); ?>"
                                                    data-excuse_points_7plus="<?php echo esc_attr($type->excuse_points_7plus ?? 2); ?>"
                                                    data-excuse_points_6="<?php echo esc_attr($type->excuse_points_6 ?? 3); ?>"
                                                    data-excuse_points_5="<?php echo esc_attr($type->excuse_points_5 ?? 4); ?>"
                                                    data-excuse_points_4="<?php echo esc_attr($type->excuse_points_4 ?? 5); ?>"
                                                    data-excuse_points_3="<?php echo esc_attr($type->excuse_points_3 ?? 6); ?>"
                                                    data-excuse_points_2="<?php echo esc_attr($type->excuse_points_2 ?? 7); ?>"
                                                    data-excuse_points_1="<?php echo esc_attr($type->excuse_points_1 ?? 8); ?>"
                                                    data-excuse_points_0="<?php echo esc_attr($type->excuse_points_0 ?? 10); ?>">
                                                <?php _e('Edit', 'saint-porphyrius'); ?>
                                            </button>
                                            <form method="post" style="display:inline;" onsubmit="return confirm('<?php _e('Are you sure you want to delete this event type?', 'saint-porphyrius'); ?>');">
                                                <?php wp_nonce_field('sp_event_type_action'); ?>
                                                <input type="hidden" name="sp_event_type_action" value="delete">
                                                <input type="hidden" name="type_id" value="<?php echo esc_attr($type->id); ?>">
                                                <button type="submit" class="button button-small button-link-delete"><?php _e('Delete', 'saint-porphyrius'); ?></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Edit Modal -->
            <div id="sp-edit-type-modal" class="sp-modal" style="display:none;">
                <div class="sp-modal-content">
                    <span class="sp-modal-close">&times;</span>
                    <h2><?php _e('Edit Event Type', 'saint-porphyrius'); ?></h2>
                    <form method="post" class="sp-form">
                        <?php wp_nonce_field('sp_event_type_action'); ?>
                        <input type="hidden" name="sp_event_type_action" value="update">
                        <input type="hidden" name="type_id" id="edit_type_id">
                        
                        <p>
                            <label><?php _e('Name (Arabic)', 'saint-porphyrius'); ?></label>
                            <input type="text" name="name_ar" id="edit_name_ar" required class="regular-text">
                        </p>
                        <p>
                            <label><?php _e('Name (English)', 'saint-porphyrius'); ?></label>
                            <input type="text" name="name_en" id="edit_name_en" class="regular-text">
                        </p>
                        <p>
                            <label><?php _e('Icon', 'saint-porphyrius'); ?></label>
                            <select name="icon" id="edit_icon" class="regular-text sp-icon-select">
                                <option value="⛪">⛪ <?php _e('Church', 'saint-porphyrius'); ?></option>
                                <option value="📖">📖 <?php _e('Book/Bible', 'saint-porphyrius'); ?></option>
                                <option value="🙏">🙏 <?php _e('Prayer', 'saint-porphyrius'); ?></option>
                                <option value="❤️">❤️ <?php _e('Heart/Service', 'saint-porphyrius'); ?></option>
                                <option value="✝️">✝️ <?php _e('Cross', 'saint-porphyrius'); ?></option>
                                <option value="🕯️">🕯️ <?php _e('Candle', 'saint-porphyrius'); ?></option>
                                <option value="🎵">🎵 <?php _e('Music/Choir', 'saint-porphyrius'); ?></option>
                                <option value="👥">👥 <?php _e('Group/Meeting', 'saint-porphyrius'); ?></option>
                                <option value="🎉">🎉 <?php _e('Celebration', 'saint-porphyrius'); ?></option>
                                <option value="📚">📚 <?php _e('Study/Education', 'saint-porphyrius'); ?></option>
                                <option value="🏠">🏠 <?php _e('Home Visit', 'saint-porphyrius'); ?></option>
                                <option value="🌍">🌍 <?php _e('Mission/Outreach', 'saint-porphyrius'); ?></option>
                                <option value="🍞">🍞 <?php _e('Communion', 'saint-porphyrius'); ?></option>
                                <option value="💒">💒 <?php _e('Wedding', 'saint-porphyrius'); ?></option>
                                <option value="👶">👶 <?php _e('Baptism', 'saint-porphyrius'); ?></option>
                                <option value="⭐">⭐ <?php _e('Special Event', 'saint-porphyrius'); ?></option>
                            </select>
                        </p>
                        <p>
                            <label><?php _e('Color', 'saint-porphyrius'); ?></label>
                            <input type="color" name="color" id="edit_color">
                        </p>
                        <p>
                            <label><?php _e('Attendance Points', 'saint-porphyrius'); ?></label>
                            <input type="number" name="attendance_points" id="edit_attendance_points" min="0" class="small-text">
                        </p>
                        <p>
                            <label><?php _e('Late Points', 'saint-porphyrius'); ?></label>
                            <input type="number" name="late_points" id="edit_late_points" min="0" class="small-text">
                        </p>
                        <p>
                            <label><?php _e('Absence Penalty', 'saint-porphyrius'); ?></label>
                            <input type="number" name="absence_penalty" id="edit_absence_penalty" min="0" class="small-text">
                        </p>
                        
                        <hr style="margin: 20px 0;">
                        <h3><?php _e('Excuse Points', 'saint-porphyrius'); ?></h3>
                        
                        <div class="sp-excuse-points-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <p>
                                <label><?php _e('7+ days', 'saint-porphyrius'); ?></label>
                                <input type="number" name="excuse_points_7plus" id="edit_excuse_points_7plus" min="0" class="small-text">
                            </p>
                            <p>
                                <label><?php _e('6 days', 'saint-porphyrius'); ?></label>
                                <input type="number" name="excuse_points_6" id="edit_excuse_points_6" min="0" class="small-text">
                            </p>
                            <p>
                                <label><?php _e('5 days', 'saint-porphyrius'); ?></label>
                                <input type="number" name="excuse_points_5" id="edit_excuse_points_5" min="0" class="small-text">
                            </p>
                            <p>
                                <label><?php _e('4 days', 'saint-porphyrius'); ?></label>
                                <input type="number" name="excuse_points_4" id="edit_excuse_points_4" min="0" class="small-text">
                            </p>
                            <p>
                                <label><?php _e('3 days', 'saint-porphyrius'); ?></label>
                                <input type="number" name="excuse_points_3" id="edit_excuse_points_3" min="0" class="small-text">
                            </p>
                            <p>
                                <label><?php _e('2 days', 'saint-porphyrius'); ?></label>
                                <input type="number" name="excuse_points_2" id="edit_excuse_points_2" min="0" class="small-text">
                            </p>
                            <p>
                                <label><?php _e('1 day', 'saint-porphyrius'); ?></label>
                                <input type="number" name="excuse_points_1" id="edit_excuse_points_1" min="0" class="small-text">
                            </p>
                            <p>
                                <label><?php _e('Same day', 'saint-porphyrius'); ?></label>
                                <input type="number" name="excuse_points_0" id="edit_excuse_points_0" min="0" class="small-text">
                            </p>
                        </div>
                        
                        <p>
                            <button type="submit" class="button button-primary"><?php _e('Update Event Type', 'saint-porphyrius'); ?></button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render events page
     */
    public function render_events_page() {
        $events_handler = SP_Events::get_instance();
        $event_types = SP_Event_Types::get_instance();
        $types = $event_types->get_all();
        
        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sp_event_action'])) {
            check_admin_referer('sp_event_action');
            
            $action = sanitize_text_field($_POST['sp_event_action']);
            
            if ($action === 'create') {
                $result = $events_handler->create($_POST);
                if (is_wp_error($result)) {
                    add_settings_error('sp_events', 'error', $result->get_error_message(), 'error');
                } else {
                    add_settings_error('sp_events', 'success', __('Event created successfully.', 'saint-porphyrius'), 'success');
                }
            } elseif ($action === 'update' && !empty($_POST['event_id'])) {
                $result = $events_handler->update(absint($_POST['event_id']), $_POST);
                if (is_wp_error($result)) {
                    add_settings_error('sp_events', 'error', $result->get_error_message(), 'error');
                } else {
                    add_settings_error('sp_events', 'success', __('Event updated successfully.', 'saint-porphyrius'), 'success');
                }
            } elseif ($action === 'delete' && !empty($_POST['event_id'])) {
                $result = $events_handler->delete(absint($_POST['event_id']));
                if (is_wp_error($result)) {
                    add_settings_error('sp_events', 'error', $result->get_error_message(), 'error');
                } else {
                    add_settings_error('sp_events', 'success', __('Event deleted successfully.', 'saint-porphyrius'), 'success');
                }
            } elseif ($action === 'complete' && !empty($_POST['event_id'])) {
                $result = $events_handler->complete_event(absint($_POST['event_id']));
                if (is_wp_error($result)) {
                    add_settings_error('sp_events', 'error', $result->get_error_message(), 'error');
                } else {
                    add_settings_error('sp_events', 'success', __('Event completed and points processed.', 'saint-porphyrius'), 'success');
                }
            }
        }
        
        $events = $events_handler->get_all(array('limit' => 100));
        ?>
        <div class="wrap sp-admin-wrap">
            <h1><?php _e('Events Management', 'saint-porphyrius'); ?></h1>
            
            <?php settings_errors('sp_events'); ?>
            
            <div class="sp-admin-grid">
                <div class="sp-admin-card sp-event-form-card">
                    <h2><span class="dashicons dashicons-calendar-alt"></span> <?php _e('Create New Event', 'saint-porphyrius'); ?></h2>
                    <form method="post" class="sp-form sp-event-form">
                        <?php wp_nonce_field('sp_event_action'); ?>
                        <input type="hidden" name="sp_event_action" value="create">
                        
                        <div class="sp-form-section">
                            <h3><?php _e('Event Details', 'saint-porphyrius'); ?></h3>
                            <div class="sp-form-row">
                                <div class="sp-form-field">
                                    <label><?php _e('Event Type', 'saint-porphyrius'); ?> <span class="required">*</span></label>
                                    <select name="event_type_id" required class="sp-select">
                                        <option value=""><?php _e('Select type...', 'saint-porphyrius'); ?></option>
                                        <?php foreach ($types as $type): ?>
                                            <option value="<?php echo esc_attr($type->id); ?>">
                                                <?php echo esc_html($type->icon . ' ' . $type->name_ar); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="sp-form-field">
                                    <label><?php _e('Status', 'saint-porphyrius'); ?></label>
                                    <select name="status" class="sp-select">
                                        <option value="draft"><?php _e('Draft', 'saint-porphyrius'); ?></option>
                                        <option value="published"><?php _e('Published', 'saint-porphyrius'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="sp-form-row">
                                <div class="sp-form-field">
                                    <label><?php _e('Title (Arabic)', 'saint-porphyrius'); ?> <span class="required">*</span></label>
                                    <input type="text" name="title_ar" required class="sp-input">
                                </div>
                                <div class="sp-form-field">
                                    <label><?php _e('Title (English)', 'saint-porphyrius'); ?></label>
                                    <input type="text" name="title_en" class="sp-input">
                                </div>
                            </div>
                            <div class="sp-form-field sp-form-field-full">
                                <label><?php _e('Description', 'saint-porphyrius'); ?></label>
                                <textarea name="description" class="sp-textarea" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <div class="sp-form-section">
                            <h3><?php _e('Date & Time', 'saint-porphyrius'); ?></h3>
                            <div class="sp-form-row sp-form-row-3">
                                <div class="sp-form-field">
                                    <label><?php _e('Event Date', 'saint-porphyrius'); ?> <span class="required">*</span></label>
                                    <input type="date" name="event_date" required class="sp-input">
                                </div>
                                <div class="sp-form-field">
                                    <label><?php _e('Start Time', 'saint-porphyrius'); ?> <span class="required">*</span></label>
                                    <input type="time" name="start_time" required class="sp-input">
                                </div>
                                <div class="sp-form-field">
                                    <label><?php _e('End Time', 'saint-porphyrius'); ?></label>
                                    <input type="time" name="end_time" class="sp-input">
                                </div>
                            </div>
                        </div>
                        
                        <div class="sp-form-section">
                            <h3><?php _e('Location', 'saint-porphyrius'); ?></h3>
                            <div class="sp-form-row">
                                <div class="sp-form-field">
                                    <label><?php _e('Location Name', 'saint-porphyrius'); ?></label>
                                    <input type="text" name="location_name" class="sp-input" placeholder="<?php _e('e.g., St. Porphyrius Church', 'saint-porphyrius'); ?>">
                                </div>
                                <div class="sp-form-field">
                                    <label><?php _e('Google Maps URL', 'saint-porphyrius'); ?></label>
                                    <input type="url" name="location_map_url" class="sp-input" placeholder="<?php _e('Paste Google Maps link', 'saint-porphyrius'); ?>">
                                </div>
                            </div>
                            <div class="sp-form-field sp-form-field-full">
                                <label><?php _e('Location Address', 'saint-porphyrius'); ?></label>
                                <textarea name="location_address" class="sp-textarea" rows="2"></textarea>
                            </div>
                        </div>
                        
                        <div class="sp-form-section">
                            <h3><?php _e('Options', 'saint-porphyrius'); ?></h3>
                            <div class="sp-form-field">
                                <label class="sp-checkbox-label">
                                    <input type="checkbox" name="is_mandatory" value="1">
                                    <span><?php _e('Mandatory attendance (penalty applied when marked absent)', 'saint-porphyrius'); ?></span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="sp-form-actions">
                            <button type="submit" class="button button-primary button-large">
                                <span class="dashicons dashicons-plus-alt2"></span>
                                <?php _e('Create Event', 'saint-porphyrius'); ?>
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="sp-admin-card sp-admin-card-wide">
                    <h2><?php _e('All Events', 'saint-porphyrius'); ?></h2>
                    <?php if (empty($events)): ?>
                        <p><?php _e('No events found.', 'saint-porphyrius'); ?></p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Type', 'saint-porphyrius'); ?></th>
                                    <th><?php _e('Title', 'saint-porphyrius'); ?></th>
                                    <th><?php _e('Date & Time', 'saint-porphyrius'); ?></th>
                                    <th><?php _e('Location', 'saint-porphyrius'); ?></th>
                                    <th><?php _e('Status', 'saint-porphyrius'); ?></th>
                                    <th><?php _e('Actions', 'saint-porphyrius'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $event): ?>
                                    <tr>
                                        <td style="color: <?php echo esc_attr($event->type_color); ?>;">
                                            <?php echo esc_html($event->type_icon . ' ' . $event->type_name_ar); ?>
                                        </td>
                                        <td>
                                            <strong><?php echo esc_html($event->title_ar); ?></strong>
                                            <?php if ($event->is_mandatory): ?>
                                                <span class="sp-badge sp-badge-warning"><?php _e('Mandatory', 'saint-porphyrius'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($event->event_date))); ?>
                                            <br>
                                            <small><?php echo esc_html($event->start_time); ?><?php echo $event->end_time ? ' - ' . esc_html($event->end_time) : ''; ?></small>
                                        </td>
                                        <td><?php echo esc_html($event->location_name); ?></td>
                                        <td>
                                            <?php
                                            $status_labels = array(
                                                'draft' => __('Draft', 'saint-porphyrius'),
                                                'published' => __('Published', 'saint-porphyrius'),
                                                'completed' => __('Completed', 'saint-porphyrius'),
                                                'cancelled' => __('Cancelled', 'saint-porphyrius'),
                                            );
                                            $status_class = $event->status === 'published' ? 'sp-badge-success' : ($event->status === 'completed' ? 'sp-badge-info' : 'sp-badge-warning');
                                            ?>
                                            <span class="sp-badge <?php echo esc_attr($status_class); ?>">
                                                <?php echo esc_html($status_labels[$event->status] ?? $event->status); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="button button-small sp-edit-event" 
                                                    data-id="<?php echo esc_attr($event->id); ?>"
                                                    data-event_type_id="<?php echo esc_attr($event->event_type_id); ?>"
                                                    data-title_ar="<?php echo esc_attr($event->title_ar); ?>"
                                                    data-title_en="<?php echo esc_attr($event->title_en); ?>"
                                                    data-description="<?php echo esc_attr($event->description); ?>"
                                                    data-event_date="<?php echo esc_attr($event->event_date); ?>"
                                                    data-start_time="<?php echo esc_attr($event->start_time); ?>"
                                                    data-end_time="<?php echo esc_attr($event->end_time); ?>"
                                                    data-location_name="<?php echo esc_attr($event->location_name); ?>"
                                                    data-location_address="<?php echo esc_attr($event->location_address); ?>"
                                                    data-location_map_url="<?php echo esc_attr($event->location_map_url ?? ''); ?>"
                                                    data-is_mandatory="<?php echo esc_attr($event->is_mandatory); ?>"
                                                    data-status="<?php echo esc_attr($event->status); ?>">
                                                <?php _e('Edit', 'saint-porphyrius'); ?>
                                            </button>
                                            <a href="<?php echo admin_url('admin.php?page=saint-porphyrius-attendance&event_id=' . $event->id); ?>" class="button button-small">
                                                <?php _e('Attendance', 'saint-porphyrius'); ?>
                                            </a>
                                            <?php if ($event->status === 'published'): ?>
                                                <form method="post" style="display:inline;" onsubmit="return confirm('<?php _e('Mark event as completed and process all attendance points?', 'saint-porphyrius'); ?>');">
                                                    <?php wp_nonce_field('sp_event_action'); ?>
                                                    <input type="hidden" name="sp_event_action" value="complete">
                                                    <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">
                                                    <button type="submit" class="button button-small"><?php _e('Complete', 'saint-porphyrius'); ?></button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="post" style="display:inline;" onsubmit="return confirm('<?php _e('Are you sure you want to delete this event?', 'saint-porphyrius'); ?>');">
                                                <?php wp_nonce_field('sp_event_action'); ?>
                                                <input type="hidden" name="sp_event_action" value="delete">
                                                <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">
                                                <button type="submit" class="button button-small button-link-delete"><?php _e('Delete', 'saint-porphyrius'); ?></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Edit Event Modal -->
            <div id="sp-edit-event-modal" class="sp-modal" style="display:none;">
                <div class="sp-modal-content sp-modal-large">
                    <span class="sp-modal-close">&times;</span>
                    <h2><span class="dashicons dashicons-edit"></span> <?php _e('Edit Event', 'saint-porphyrius'); ?></h2>
                    <form method="post" class="sp-form sp-event-form">
                        <?php wp_nonce_field('sp_event_action'); ?>
                        <input type="hidden" name="sp_event_action" value="update">
                        <input type="hidden" name="event_id" id="edit_event_id">
                        
                        <div class="sp-form-section">
                            <h3><?php _e('Event Details', 'saint-porphyrius'); ?></h3>
                            <div class="sp-form-row">
                                <div class="sp-form-field">
                                    <label><?php _e('Event Type', 'saint-porphyrius'); ?> <span class="required">*</span></label>
                                    <select name="event_type_id" id="edit_event_type_id" required class="sp-select">
                                        <?php foreach ($types as $type): ?>
                                            <option value="<?php echo esc_attr($type->id); ?>">
                                                <?php echo esc_html($type->icon . ' ' . $type->name_ar); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="sp-form-field">
                                    <label><?php _e('Status', 'saint-porphyrius'); ?></label>
                                    <select name="status" id="edit_event_status" class="sp-select">
                                        <option value="draft"><?php _e('Draft', 'saint-porphyrius'); ?></option>
                                        <option value="published"><?php _e('Published', 'saint-porphyrius'); ?></option>
                                        <option value="completed"><?php _e('Completed', 'saint-porphyrius'); ?></option>
                                        <option value="cancelled"><?php _e('Cancelled', 'saint-porphyrius'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="sp-form-row">
                                <div class="sp-form-field">
                                    <label><?php _e('Title (Arabic)', 'saint-porphyrius'); ?> <span class="required">*</span></label>
                                    <input type="text" name="title_ar" id="edit_event_title_ar" required class="sp-input">
                                </div>
                                <div class="sp-form-field">
                                    <label><?php _e('Title (English)', 'saint-porphyrius'); ?></label>
                                    <input type="text" name="title_en" id="edit_event_title_en" class="sp-input">
                                </div>
                            </div>
                            <div class="sp-form-field sp-form-field-full">
                                <label><?php _e('Description', 'saint-porphyrius'); ?></label>
                                <textarea name="description" id="edit_event_description" class="sp-textarea" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <div class="sp-form-section">
                            <h3><?php _e('Date & Time', 'saint-porphyrius'); ?></h3>
                            <div class="sp-form-row sp-form-row-3">
                                <div class="sp-form-field">
                                    <label><?php _e('Event Date', 'saint-porphyrius'); ?> <span class="required">*</span></label>
                                    <input type="date" name="event_date" id="edit_event_date" required class="sp-input">
                                </div>
                                <div class="sp-form-field">
                                    <label><?php _e('Start Time', 'saint-porphyrius'); ?> <span class="required">*</span></label>
                                    <input type="time" name="start_time" id="edit_event_start_time" required class="sp-input">
                                </div>
                                <div class="sp-form-field">
                                    <label><?php _e('End Time', 'saint-porphyrius'); ?></label>
                                    <input type="time" name="end_time" id="edit_event_end_time" class="sp-input">
                                </div>
                            </div>
                        </div>
                        
                        <div class="sp-form-section">
                            <h3><?php _e('Location', 'saint-porphyrius'); ?></h3>
                            <div class="sp-form-row">
                                <div class="sp-form-field">
                                    <label><?php _e('Location Name', 'saint-porphyrius'); ?></label>
                                    <input type="text" name="location_name" id="edit_event_location_name" class="sp-input">
                                </div>
                                <div class="sp-form-field">
                                    <label><?php _e('Google Maps URL', 'saint-porphyrius'); ?></label>
                                    <input type="url" name="location_map_url" id="edit_event_location_map_url" class="sp-input">
                                </div>
                            </div>
                            <div class="sp-form-field sp-form-field-full">
                                <label><?php _e('Location Address', 'saint-porphyrius'); ?></label>
                                <textarea name="location_address" id="edit_event_location_address" class="sp-textarea" rows="2"></textarea>
                            </div>
                        </div>
                        
                        <div class="sp-form-section">
                            <h3><?php _e('Options', 'saint-porphyrius'); ?></h3>
                            <div class="sp-form-field">
                                <label class="sp-checkbox-label">
                                    <input type="checkbox" name="is_mandatory" id="edit_event_is_mandatory" value="1">
                                    <span><?php _e('Mandatory attendance (penalty applied when marked absent)', 'saint-porphyrius'); ?></span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="sp-form-actions">
                            <button type="submit" class="button button-primary button-large">
                                <span class="dashicons dashicons-saved"></span>
                                <?php _e('Update Event', 'saint-porphyrius'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                // Edit event modal
                $('.sp-edit-event').on('click', function() {
                    var $btn = $(this);
                    $('#edit_event_id').val($btn.data('id'));
                    $('#edit_event_type_id').val($btn.data('event_type_id'));
                    $('#edit_event_title_ar').val($btn.data('title_ar'));
                    $('#edit_event_title_en').val($btn.data('title_en'));
                    $('#edit_event_description').val($btn.data('description'));
                    $('#edit_event_date').val($btn.data('event_date'));
                    $('#edit_event_start_time').val($btn.data('start_time'));
                    $('#edit_event_end_time').val($btn.data('end_time'));
                    $('#edit_event_location_name').val($btn.data('location_name'));
                    $('#edit_event_location_address').val($btn.data('location_address'));
                    $('#edit_event_location_map_url').val($btn.data('location_map_url'));
                    $('#edit_event_status').val($btn.data('status'));
                    $('#edit_event_is_mandatory').prop('checked', $btn.data('is_mandatory') == 1);
                    $('#sp-edit-event-modal').show();
                });
                
                // Close modal
                $('#sp-edit-event-modal .sp-modal-close').on('click', function() {
                    $('#sp-edit-event-modal').hide();
                });
                
                $(window).on('click', function(e) {
                    if ($(e.target).is('#sp-edit-event-modal')) {
                        $('#sp-edit-event-modal').hide();
                    }
                });
            });
            </script>
        </div>
        <?php
    }
    
    /**
     * Render attendance page
     */
    public function render_attendance_page() {
        $events_handler = SP_Events::get_instance();
        $attendance_handler = SP_Attendance::get_instance();
        $excuses_handler = SP_Excuses::get_instance();
        
        $event_id = isset($_GET['event_id']) ? absint($_GET['event_id']) : 0;
        
        // Handle attendance marking
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sp_attendance_action'])) {
            check_admin_referer('sp_attendance_action');
            
            $event_id = absint($_POST['event_id']);
            $attendance_data = isset($_POST['attendance']) ? $_POST['attendance'] : array();
            
            $result = $attendance_handler->bulk_mark($event_id, $attendance_data);
            
            if ($result['success'] > 0) {
                add_settings_error('sp_attendance', 'success', 
                    sprintf(__('%d attendance records saved successfully.', 'saint-porphyrius'), $result['success']), 'success');
            }
            if ($result['failed'] > 0) {
                add_settings_error('sp_attendance', 'warning', 
                    sprintf(__('%d records failed to save.', 'saint-porphyrius'), $result['failed']), 'warning');
            }
        }
        
        // Get upcoming/recent events for selection
        $events = $events_handler->get_all(array(
            'orderby' => 'event_date',
            'order' => 'DESC',
            'limit' => 50,
        ));
        
        $current_event = $event_id ? $events_handler->get($event_id) : null;
        $members = $event_id ? $attendance_handler->get_members_for_event($event_id) : array();
        
        // Get all excuses for this event
        $event_excuses = $event_id ? $excuses_handler->get_event_excuses($event_id) : array();
        $excuses_by_user = array();
        foreach ($event_excuses as $excuse) {
            $excuses_by_user[$excuse->user_id] = $excuse;
        }
        ?>
        <div class="wrap sp-admin-wrap">
            <h1><?php _e('Attendance Tracking', 'saint-porphyrius'); ?></h1>
            
            <?php settings_errors('sp_attendance'); ?>
            
            <div class="sp-admin-card">
                <h2><?php _e('Select Event', 'saint-porphyrius'); ?></h2>
                <form method="get" class="sp-form-inline">
                    <input type="hidden" name="page" value="saint-porphyrius-attendance">
                    <select name="event_id" onchange="this.form.submit()" class="regular-text">
                        <option value=""><?php _e('-- Select Event --', 'saint-porphyrius'); ?></option>
                        <?php foreach ($events as $event): ?>
                            <option value="<?php echo esc_attr($event->id); ?>" <?php selected($event_id, $event->id); ?>>
                                <?php echo esc_html($event->type_icon . ' ' . $event->title_ar . ' - ' . date_i18n(get_option('date_format'), strtotime($event->event_date))); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            
            <?php if ($current_event && !empty($members)): ?>
                <div class="sp-admin-card">
                    <h2>
                        <?php echo esc_html($current_event->type_icon . ' ' . $current_event->title_ar); ?>
                        <small>(<?php echo esc_html(date_i18n(get_option('date_format'), strtotime($current_event->event_date))); ?>)</small>
                    </h2>
                    
                    <form method="post" class="sp-attendance-form">
                        <?php wp_nonce_field('sp_attendance_action'); ?>
                        <input type="hidden" name="sp_attendance_action" value="mark">
                        <input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>">
                        
                        <div class="sp-quick-actions" style="margin-bottom: 20px;">
                            <button type="button" class="button" onclick="spMarkAll('attended')"><?php _e('Mark All Present', 'saint-porphyrius'); ?></button>
                            <button type="button" class="button" onclick="spMarkAll('absent')"><?php _e('Mark All Absent', 'saint-porphyrius'); ?></button>
                        </div>
                        
                        <table class="wp-list-table widefat fixed striped sp-attendance-table">
                            <thead>
                                <tr>
                                    <th style="width: 22%;"><?php _e('Member', 'saint-porphyrius'); ?></th>
                                    <th style="width: 13%;"><?php _e('Contact', 'saint-porphyrius'); ?></th>
                                    <th style="width: 25%;"><?php _e('Excuse', 'saint-porphyrius'); ?></th>
                                    <th style="width: 18%;"><?php _e('Status', 'saint-porphyrius'); ?></th>
                                    <th style="width: 22%;"><?php _e('Notes', 'saint-porphyrius'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members as $member): 
                                    $current_status = $member['attendance'] ? $member['attendance']->status : '';
                                    $current_notes = $member['attendance'] ? $member['attendance']->notes : '';
                                    $user_excuse = isset($excuses_by_user[$member['user_id']]) ? $excuses_by_user[$member['user_id']] : null;
                                    $has_approved_excuse = $user_excuse && $user_excuse->status === 'approved';
                                    
                                    // Auto-select excused if they have an approved excuse and no status set
                                    if ($has_approved_excuse && empty($current_status)) {
                                        $current_status = 'excused';
                                    }
                                ?>
                                    <tr class="<?php echo $has_approved_excuse ? 'sp-excused-row' : ''; ?>">
                                        <td>
                                            <strong><?php echo esc_html($member['name_ar'] ?: $member['display_name']); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo esc_html($member['phone']); ?>
                                        </td>
                                        <td class="sp-excuse-cell">
                                            <?php if ($user_excuse): ?>
                                                <?php
                                                $excuse_status_color = SP_Excuses::get_status_color($user_excuse->status);
                                                $excuse_status_label = SP_Excuses::get_status_label($user_excuse->status);
                                                ?>
                                                <div class="sp-excuse-info">
                                                    <span class="sp-excuse-badge" style="background: <?php echo esc_attr($excuse_status_color); ?>15; color: <?php echo esc_attr($excuse_status_color); ?>; border: 1px solid <?php echo esc_attr($excuse_status_color); ?>40;">
                                                        <?php echo esc_html($excuse_status_label); ?>
                                                    </span>
                                                    <span class="sp-excuse-points">-<?php echo esc_html($user_excuse->points_deducted); ?> pts</span>
                                                </div>
                                                <div class="sp-excuse-text" title="<?php echo esc_attr($user_excuse->excuse_text); ?>">
                                                    <?php echo esc_html(wp_trim_words($user_excuse->excuse_text, 6, '...')); ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="sp-no-excuse">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($has_approved_excuse): ?>
                                                <!-- Show fixed excused status for approved excuses -->
                                                <input type="hidden" name="attendance[<?php echo esc_attr($member['user_id']); ?>][status]" value="excused">
                                                <span class="sp-status-fixed sp-status-excused">
                                                    <span class="dashicons dashicons-yes-alt"></span>
                                                    <?php _e('Excused', 'saint-porphyrius'); ?>
                                                </span>
                                            <?php else: ?>
                                                <select name="attendance[<?php echo esc_attr($member['user_id']); ?>][status]" class="sp-attendance-select">
                                                    <option value=""><?php _e('-- Not marked --', 'saint-porphyrius'); ?></option>
                                                    <option value="attended" <?php selected($current_status, 'attended'); ?>><?php _e('✓ Present', 'saint-porphyrius'); ?></option>
                                                    <option value="late" <?php selected($current_status, 'late'); ?>><?php _e('⏱ Late', 'saint-porphyrius'); ?></option>
                                                    <option value="absent" <?php selected($current_status, 'absent'); ?>><?php _e('✗ Absent', 'saint-porphyrius'); ?></option>
                                                    <option value="excused" <?php selected($current_status, 'excused'); ?>><?php _e('📝 Excused', 'saint-porphyrius'); ?></option>
                                                </select>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <input type="text" name="attendance[<?php echo esc_attr($member['user_id']); ?>][notes]" 
                                                   value="<?php echo esc_attr($current_notes); ?>" class="regular-text" 
                                                   placeholder="<?php _e('Optional notes...', 'saint-porphyrius'); ?>"
                                                   <?php echo $has_approved_excuse ? 'readonly' : ''; ?>>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <p style="margin-top: 20px;">
                            <button type="submit" class="button button-primary button-hero"><?php _e('Save Attendance', 'saint-porphyrius'); ?></button>
                        </p>
                    </form>
                </div>
                
                <script>
                function spMarkAll(status) {
                    document.querySelectorAll('.sp-attendance-select').forEach(function(select) {
                        select.value = status;
                    });
                }
                </script>
                
                <style>
                .sp-attendance-table td { vertical-align: middle; }
                .sp-excused-row { background-color: #FEF9E7 !important; }
                .sp-excused-row:hover { background-color: #FEF3C7 !important; }
                
                .sp-excuse-cell { font-size: 13px; }
                .sp-excuse-info { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
                .sp-excuse-badge { 
                    padding: 3px 8px; 
                    border-radius: 4px; 
                    font-size: 11px; 
                    font-weight: 500;
                    white-space: nowrap;
                }
                .sp-excuse-points { 
                    color: #DC2626; 
                    font-size: 11px; 
                    font-weight: 600;
                }
                .sp-excuse-text { 
                    color: #6B7280; 
                    font-size: 12px; 
                    line-height: 1.4;
                    max-width: 180px;
                }
                .sp-no-excuse { color: #D1D5DB; }
                
                .sp-status-fixed {
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                    padding: 6px 12px;
                    border-radius: 6px;
                    font-size: 13px;
                    font-weight: 500;
                }
                .sp-status-excused {
                    background: #D1FAE5;
                    color: #065F46;
                }
                .sp-status-excused .dashicons {
                    font-size: 16px;
                    width: 16px;
                    height: 16px;
                }
                </style>
            <?php elseif ($event_id): ?>
                <div class="sp-admin-card">
                    <p><?php _e('No members found or event not found.', 'saint-porphyrius'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render points page
     */
    public function render_points_page() {
        $points_handler = SP_Points::get_instance();
        
        // Handle manual adjustment
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sp_points_action'])) {
            check_admin_referer('sp_points_action');
            
            $action = sanitize_text_field($_POST['sp_points_action']);
            
            if ($action === 'adjust' && !empty($_POST['user_id']) && isset($_POST['points'])) {
                $result = $points_handler->adjust(
                    absint($_POST['user_id']),
                    intval($_POST['points']),
                    sanitize_textarea_field($_POST['description'] ?? '')
                );
                
                if (is_wp_error($result)) {
                    add_settings_error('sp_points', 'error', $result->get_error_message(), 'error');
                } else {
                    add_settings_error('sp_points', 'success', __('Points adjusted successfully.', 'saint-porphyrius'), 'success');
                }
            } elseif ($action === 'recalculate') {
                $count = $points_handler->recalculate_all_balances();
                add_settings_error('sp_points', 'success', sprintf(__('Recalculated balances for %d members.', 'saint-porphyrius'), $count), 'success');
            }
        }
        
        $members_with_points = $points_handler->get_all_with_points();
        $stats = $points_handler->get_summary_stats();
        $leaderboard = $points_handler->get_leaderboard(5);
        
        // Get all members for the adjustment dropdown
        $all_members = get_users(array('role' => 'sp_member', 'orderby' => 'display_name'));
        ?>
        <div class="wrap sp-admin-wrap">
            <h1><?php _e('Points & Rewards', 'saint-porphyrius'); ?></h1>
            
            <?php settings_errors('sp_points'); ?>
            
            <div class="sp-admin-stats">
                <div class="sp-stat-card">
                    <div class="sp-stat-icon success">
                        <span class="dashicons dashicons-plus-alt"></span>
                    </div>
                    <div class="sp-stat-content">
                        <span class="sp-stat-number"><?php echo esc_html($stats->total_awarded ?? 0); ?></span>
                        <span class="sp-stat-label"><?php _e('Total Points Awarded', 'saint-porphyrius'); ?></span>
                    </div>
                </div>
                
                <div class="sp-stat-card">
                    <div class="sp-stat-icon warning">
                        <span class="dashicons dashicons-minus"></span>
                    </div>
                    <div class="sp-stat-content">
                        <span class="sp-stat-number"><?php echo esc_html($stats->total_penalties ?? 0); ?></span>
                        <span class="sp-stat-label"><?php _e('Total Penalties', 'saint-porphyrius'); ?></span>
                    </div>
                </div>
                
                <div class="sp-stat-card">
                    <div class="sp-stat-icon members">
                        <span class="dashicons dashicons-awards"></span>
                    </div>
                    <div class="sp-stat-content">
                        <span class="sp-stat-number"><?php echo esc_html($stats->members_with_points ?? 0); ?></span>
                        <span class="sp-stat-label"><?php _e('Members with Points', 'saint-porphyrius'); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="sp-admin-grid">
                <div class="sp-admin-card">
                    <h2><?php _e('Manual Points Adjustment', 'saint-porphyrius'); ?></h2>
                    <form method="post" class="sp-form">
                        <?php wp_nonce_field('sp_points_action'); ?>
                        <input type="hidden" name="sp_points_action" value="adjust">
                        
                        <p>
                            <label><?php _e('Select Member', 'saint-porphyrius'); ?></label>
                            <select name="user_id" required class="regular-text">
                                <option value=""><?php _e('-- Select Member --', 'saint-porphyrius'); ?></option>
                                <?php foreach ($all_members as $member): ?>
                                    <option value="<?php echo esc_attr($member->ID); ?>">
                                        <?php echo esc_html($member->display_name); ?>
                                        (<?php echo esc_html($points_handler->get_balance($member->ID)); ?> pts)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                        <p>
                            <label><?php _e('Points (use negative for penalty)', 'saint-porphyrius'); ?></label>
                            <input type="number" name="points" required class="small-text" placeholder="10 or -5">
                        </p>
                        <p>
                            <label><?php _e('Reason/Description', 'saint-porphyrius'); ?></label>
                            <textarea name="description" class="large-text" rows="2" placeholder="<?php _e('Reason for adjustment...', 'saint-porphyrius'); ?>"></textarea>
                        </p>
                        <p>
                            <button type="submit" class="button button-primary"><?php _e('Adjust Points', 'saint-porphyrius'); ?></button>
                        </p>
                    </form>
                    
                    <hr>
                    
                    <form method="post" onsubmit="return confirm('<?php _e('Recalculate all member balances from the points log?', 'saint-porphyrius'); ?>');">
                        <?php wp_nonce_field('sp_points_action'); ?>
                        <input type="hidden" name="sp_points_action" value="recalculate">
                        <button type="submit" class="button"><?php _e('Recalculate All Balances', 'saint-porphyrius'); ?></button>
                    </form>
                </div>
                
                <div class="sp-admin-card">
                    <h2><?php _e('Top 5 Leaderboard', 'saint-porphyrius'); ?> 🏆</h2>
                    <?php if (empty($leaderboard)): ?>
                        <p><?php _e('No points recorded yet.', 'saint-porphyrius'); ?></p>
                    <?php else: ?>
                        <table class="wp-list-table widefat striped">
                            <tbody>
                                <?php foreach ($leaderboard as $index => $entry): 
                                    $medals = array('🥇', '🥈', '🥉', '4️⃣', '5️⃣');
                                ?>
                                    <tr>
                                        <td style="width: 40px; font-size: 24px;"><?php echo $medals[$index]; ?></td>
                                        <td>
                                            <strong><?php echo esc_html($entry->name_ar ?: $entry->display_name); ?></strong>
                                        </td>
                                        <td style="text-align: right;">
                                            <strong><?php echo esc_html($entry->total_points); ?></strong> <?php _e('pts', 'saint-porphyrius'); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="sp-admin-card sp-admin-card-wide">
                <h2><?php _e('All Members Points', 'saint-porphyrius'); ?></h2>
                <?php if (empty($members_with_points)): ?>
                    <p><?php _e('No members found.', 'saint-porphyrius'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Member', 'saint-porphyrius'); ?></th>
                                <th><?php _e('Email', 'saint-porphyrius'); ?></th>
                                <th><?php _e('Points Balance', 'saint-porphyrius'); ?></th>
                                <th><?php _e('Actions', 'saint-porphyrius'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members_with_points as $member): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($member['name_ar'] ?: $member['display_name']); ?></strong>
                                    </td>
                                    <td><?php echo esc_html($member['email']); ?></td>
                                    <td>
                                        <strong class="<?php echo $member['points'] >= 0 ? 'sp-text-success' : 'sp-text-danger'; ?>">
                                            <?php echo esc_html($member['points']); ?> <?php _e('pts', 'saint-porphyrius'); ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <button type="button" class="button button-small sp-view-history" 
                                           data-user-id="<?php echo esc_attr($member['user_id']); ?>"
                                           data-user-name="<?php echo esc_attr($member['name_ar'] ?: $member['display_name']); ?>">
                                            <?php _e('View History', 'saint-porphyrius'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Points History Modal -->
            <div id="sp-points-history-modal" class="sp-modal" style="display:none;">
                <div class="sp-modal-content sp-modal-large">
                    <span class="sp-modal-close">&times;</span>
                    <h2><span class="dashicons dashicons-chart-line"></span> <span id="sp-history-title"><?php _e('Points History', 'saint-porphyrius'); ?></span></h2>
                    
                    <div class="sp-history-summary">
                        <div class="sp-history-balance">
                            <?php _e('Current Balance:', 'saint-porphyrius'); ?> 
                            <strong id="sp-history-balance">0</strong> <?php _e('pts', 'saint-porphyrius'); ?>
                        </div>
                    </div>
                    
                    <div id="sp-history-loading" style="text-align: center; padding: 40px;">
                        <span class="spinner is-active" style="float: none;"></span>
                        <p><?php _e('Loading history...', 'saint-porphyrius'); ?></p>
                    </div>
                    
                    <div id="sp-history-content" style="display: none;">
                        <table class="wp-list-table widefat striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Date', 'saint-porphyrius'); ?></th>
                                    <th><?php _e('Points', 'saint-porphyrius'); ?></th>
                                    <th><?php _e('Type', 'saint-porphyrius'); ?></th>
                                    <th><?php _e('Reason', 'saint-porphyrius'); ?></th>
                                    <th><?php _e('Balance After', 'saint-porphyrius'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="sp-history-table-body">
                            </tbody>
                        </table>
                        <div id="sp-history-empty" style="display: none; text-align: center; padding: 40px;">
                            <p><?php _e('No points history found for this member.', 'saint-porphyrius'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                // View history button click
                $('.sp-view-history').on('click', function(e) {
                    e.preventDefault();
                    var userId = $(this).data('user-id');
                    var userName = $(this).data('user-name');
                    
                    $('#sp-history-title').text('<?php _e('Points History', 'saint-porphyrius'); ?> - ' + userName);
                    $('#sp-history-loading').show();
                    $('#sp-history-content').hide();
                    $('#sp-points-history-modal').show();
                    
                    // AJAX call to get history
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'sp_get_points_history',
                            nonce: '<?php echo wp_create_nonce('sp_admin_nonce'); ?>',
                            user_id: userId
                        },
                        success: function(response) {
                            $('#sp-history-loading').hide();
                            
                            if (response.success) {
                                var data = response.data;
                                $('#sp-history-balance').text(data.current_balance);
                                
                                if (data.history.length === 0) {
                                    $('#sp-history-empty').show();
                                    $('#sp-history-table-body').empty();
                                } else {
                                    $('#sp-history-empty').hide();
                                    var html = '';
                                    $.each(data.history, function(i, entry) {
                                        var pointsClass = entry.points >= 0 ? 'sp-text-success' : 'sp-text-danger';
                                        var pointsPrefix = entry.points >= 0 ? '+' : '';
                                        var typeLabel = entry.type_label || entry.type;
                                        var typeColor = entry.type_color || '#6B7280';
                                        html += '<tr>';
                                        html += '<td>' + entry.created_at + '</td>';
                                        html += '<td><strong class="' + pointsClass + '">' + pointsPrefix + entry.points + '</strong></td>';
                                        html += '<td><span class="sp-type-badge" style="background:' + typeColor + '15;color:' + typeColor + ';border:1px solid ' + typeColor + '40;padding:4px 10px;border-radius:4px;font-size:12px;font-weight:500;">' + typeLabel.toUpperCase() + '</span></td>';
                                        html += '<td>' + (entry.reason || '-') + '</td>';
                                        html += '<td>' + entry.balance_after + '</td>';
                                        html += '</tr>';
                                    });
                                    $('#sp-history-table-body').html(html);
                                }
                                
                                $('#sp-history-content').show();
                            } else {
                                alert(response.data.message || '<?php _e('Failed to load history', 'saint-porphyrius'); ?>');
                                $('#sp-points-history-modal').hide();
                            }
                        },
                        error: function() {
                            alert('<?php _e('An error occurred', 'saint-porphyrius'); ?>');
                            $('#sp-points-history-modal').hide();
                        }
                    });
                });
                
                // Close modal
                $('#sp-points-history-modal .sp-modal-close').on('click', function() {
                    $('#sp-points-history-modal').hide();
                });
                
                $(window).on('click', function(e) {
                    if ($(e.target).is('#sp-points-history-modal')) {
                        $('#sp-points-history-modal').hide();
                    }
                });
            });
            </script>
        </div>
        <?php
    }
    
    /**
     * Render excuses management page
     */
    public function render_excuses_page() {
        $excuses_handler = SP_Excuses::get_instance();
        
        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sp_excuse_action'])) {
            check_admin_referer('sp_excuse_action');
            
            $action = sanitize_text_field($_POST['sp_excuse_action']);
            $excuse_id = absint($_POST['excuse_id'] ?? 0);
            $admin_notes = sanitize_textarea_field($_POST['admin_notes'] ?? '');
            
            if ($action === 'approve' && $excuse_id) {
                $result = $excuses_handler->approve($excuse_id, get_current_user_id(), $admin_notes);
                if ($result['success']) {
                    add_settings_error('sp_excuses', 'success', $result['message'], 'success');
                } else {
                    add_settings_error('sp_excuses', 'error', $result['message'], 'error');
                }
            } elseif ($action === 'deny' && $excuse_id) {
                $result = $excuses_handler->deny($excuse_id, get_current_user_id(), $admin_notes);
                if ($result['success']) {
                    add_settings_error('sp_excuses', 'success', $result['message'], 'success');
                } else {
                    add_settings_error('sp_excuses', 'error', $result['message'], 'error');
                }
            }
        }
        
        // Get filter
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : null;
        $excuses = $excuses_handler->get_all(array('status' => $status_filter, 'limit' => 100));
        $pending_count = $excuses_handler->count_pending();
        ?>
        <div class="wrap sp-admin-wrap">
            <h1><?php _e('Excuses Management', 'saint-porphyrius'); ?></h1>
            
            <?php settings_errors('sp_excuses'); ?>
            
            <!-- Stats Cards -->
            <div class="sp-admin-stats" style="margin-bottom: 20px;">
                <div class="sp-stat-card">
                    <div class="sp-stat-icon pending">
                        <span class="dashicons dashicons-clock"></span>
                    </div>
                    <div class="sp-stat-content">
                        <span class="sp-stat-number"><?php echo esc_html($pending_count); ?></span>
                        <span class="sp-stat-label"><?php _e('Pending Review', 'saint-porphyrius'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Filter Tabs -->
            <ul class="subsubsub" style="margin-bottom: 20px;">
                <li>
                    <a href="<?php echo admin_url('admin.php?page=saint-porphyrius-excuses'); ?>" 
                       class="<?php echo !$status_filter ? 'current' : ''; ?>">
                        <?php _e('All', 'saint-porphyrius'); ?>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo admin_url('admin.php?page=saint-porphyrius-excuses&status=pending'); ?>"
                       class="<?php echo $status_filter === 'pending' ? 'current' : ''; ?>">
                        <?php _e('Pending', 'saint-porphyrius'); ?>
                        <?php if ($pending_count > 0): ?>
                            <span class="count">(<?php echo $pending_count; ?>)</span>
                        <?php endif; ?>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo admin_url('admin.php?page=saint-porphyrius-excuses&status=approved'); ?>"
                       class="<?php echo $status_filter === 'approved' ? 'current' : ''; ?>">
                        <?php _e('Approved', 'saint-porphyrius'); ?>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo admin_url('admin.php?page=saint-porphyrius-excuses&status=denied'); ?>"
                       class="<?php echo $status_filter === 'denied' ? 'current' : ''; ?>">
                        <?php _e('Denied', 'saint-porphyrius'); ?>
                    </a>
                </li>
            </ul>
            
            <?php if (empty($excuses)): ?>
                <div class="sp-admin-card">
                    <p><?php _e('No excuses found.', 'saint-porphyrius'); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Member', 'saint-porphyrius'); ?></th>
                            <th><?php _e('Event', 'saint-porphyrius'); ?></th>
                            <th><?php _e('Event Date', 'saint-porphyrius'); ?></th>
                            <th><?php _e('Excuse', 'saint-porphyrius'); ?></th>
                            <th><?php _e('Points Deducted', 'saint-porphyrius'); ?></th>
                            <th><?php _e('Submitted', 'saint-porphyrius'); ?></th>
                            <th><?php _e('Status', 'saint-porphyrius'); ?></th>
                            <th><?php _e('Actions', 'saint-porphyrius'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($excuses as $excuse): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($excuse->name_ar ?: $excuse->display_name); ?></strong>
                                </td>
                                <td>
                                    <?php echo esc_html($excuse->event_title); ?>
                                    <br><small><?php echo esc_html($excuse->event_type_name); ?></small>
                                </td>
                                <td><?php echo esc_html(date_i18n('Y-m-d', strtotime($excuse->event_date))); ?></td>
                                <td>
                                    <div class="sp-excuse-text" style="max-width: 200px;">
                                        <?php echo esc_html(wp_trim_words($excuse->excuse_text, 15, '...')); ?>
                                        <?php if (strlen($excuse->excuse_text) > 100): ?>
                                            <button type="button" class="button-link sp-view-excuse" 
                                                    data-excuse="<?php echo esc_attr($excuse->excuse_text); ?>"
                                                    data-notes="<?php echo esc_attr($excuse->admin_notes); ?>">
                                                <?php _e('View Full', 'saint-porphyrius'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="sp-text-danger">-<?php echo esc_html($excuse->points_deducted); ?></span>
                                    <br><small><?php printf(__('%d days before', 'saint-porphyrius'), $excuse->days_before_event); ?></small>
                                </td>
                                <td>
                                    <?php echo esc_html(date_i18n('Y-m-d H:i', strtotime($excuse->created_at))); ?>
                                </td>
                                <td>
                                    <?php
                                    $status_label = SP_Excuses::get_status_label($excuse->status);
                                    $status_color = SP_Excuses::get_status_color($excuse->status);
                                    ?>
                                    <span class="sp-badge" style="background: <?php echo esc_attr($status_color); ?>20; color: <?php echo esc_attr($status_color); ?>;">
                                        <?php echo esc_html($status_label); ?>
                                    </span>
                                    <?php if ($excuse->reviewed_at): ?>
                                        <br><small><?php echo esc_html(date_i18n('Y-m-d', strtotime($excuse->reviewed_at))); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($excuse->status === 'pending'): ?>
                                        <button type="button" class="button button-small button-primary sp-approve-excuse"
                                                data-id="<?php echo esc_attr($excuse->id); ?>"
                                                data-name="<?php echo esc_attr($excuse->name_ar ?: $excuse->display_name); ?>">
                                            <?php _e('Approve', 'saint-porphyrius'); ?>
                                        </button>
                                        <button type="button" class="button button-small sp-deny-excuse"
                                                data-id="<?php echo esc_attr($excuse->id); ?>"
                                                data-name="<?php echo esc_attr($excuse->name_ar ?: $excuse->display_name); ?>"
                                                data-points="<?php echo esc_attr($excuse->points_deducted * 2); ?>">
                                            <?php _e('Deny', 'saint-porphyrius'); ?>
                                        </button>
                                    <?php else: ?>
                                        <?php if ($excuse->admin_notes): ?>
                                            <small><strong><?php _e('Notes:', 'saint-porphyrius'); ?></strong> <?php echo esc_html($excuse->admin_notes); ?></small>
                                        <?php else: ?>
                                            <span class="description">-</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <!-- Approve Modal -->
            <div id="sp-approve-modal" class="sp-modal" style="display:none;">
                <div class="sp-modal-content">
                    <span class="sp-modal-close">&times;</span>
                    <h2><?php _e('Approve Excuse', 'saint-porphyrius'); ?></h2>
                    <p id="sp-approve-info"></p>
                    <form method="post">
                        <?php wp_nonce_field('sp_excuse_action'); ?>
                        <input type="hidden" name="sp_excuse_action" value="approve">
                        <input type="hidden" name="excuse_id" id="approve_excuse_id">
                        <p>
                            <label><?php _e('Admin Notes (optional)', 'saint-porphyrius'); ?></label>
                            <textarea name="admin_notes" rows="3" class="large-text"></textarea>
                        </p>
                        <p class="description">
                            <?php _e('The member will be marked as "excused" for this event.', 'saint-porphyrius'); ?>
                        </p>
                        <p>
                            <button type="submit" class="button button-primary"><?php _e('Approve Excuse', 'saint-porphyrius'); ?></button>
                            <button type="button" class="button sp-modal-cancel"><?php _e('Cancel', 'saint-porphyrius'); ?></button>
                        </p>
                    </form>
                </div>
            </div>
            
            <!-- Deny Modal -->
            <div id="sp-deny-modal" class="sp-modal" style="display:none;">
                <div class="sp-modal-content">
                    <span class="sp-modal-close">&times;</span>
                    <h2><?php _e('Deny Excuse', 'saint-porphyrius'); ?></h2>
                    <p id="sp-deny-info"></p>
                    <div class="notice notice-warning inline">
                        <p><strong><?php _e('Warning:', 'saint-porphyrius'); ?></strong> 
                        <?php _e('Denying this excuse will deduct an additional penalty of', 'saint-porphyrius'); ?> 
                        <strong id="sp-deny-penalty">0</strong> <?php _e('points (double the submission cost).', 'saint-porphyrius'); ?></p>
                    </div>
                    <form method="post">
                        <?php wp_nonce_field('sp_excuse_action'); ?>
                        <input type="hidden" name="sp_excuse_action" value="deny">
                        <input type="hidden" name="excuse_id" id="deny_excuse_id">
                        <p>
                            <label><?php _e('Reason for Denial', 'saint-porphyrius'); ?></label>
                            <textarea name="admin_notes" rows="3" class="large-text" required></textarea>
                        </p>
                        <p>
                            <button type="submit" class="button button-primary" style="background: #dc3545; border-color: #dc3545;">
                                <?php _e('Deny Excuse', 'saint-porphyrius'); ?>
                            </button>
                            <button type="button" class="button sp-modal-cancel"><?php _e('Cancel', 'saint-porphyrius'); ?></button>
                        </p>
                    </form>
                </div>
            </div>
            
            <!-- View Excuse Modal -->
            <div id="sp-view-excuse-modal" class="sp-modal" style="display:none;">
                <div class="sp-modal-content">
                    <span class="sp-modal-close">&times;</span>
                    <h2><?php _e('Full Excuse Text', 'saint-porphyrius'); ?></h2>
                    <div id="sp-full-excuse-text" style="background: #f9f9f9; padding: 15px; border-radius: 5px;"></div>
                    <div id="sp-excuse-admin-notes" style="margin-top: 15px; display: none;">
                        <h4><?php _e('Admin Notes:', 'saint-porphyrius'); ?></h4>
                        <div id="sp-excuse-notes-text" style="background: #fff3cd; padding: 15px; border-radius: 5px;"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Approve button
            $('.sp-approve-excuse').on('click', function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                $('#approve_excuse_id').val(id);
                $('#sp-approve-info').text('<?php _e('Approving excuse for:', 'saint-porphyrius'); ?> ' + name);
                $('#sp-approve-modal').show();
            });
            
            // Deny button
            $('.sp-deny-excuse').on('click', function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                var penalty = $(this).data('points');
                $('#deny_excuse_id').val(id);
                $('#sp-deny-info').text('<?php _e('Denying excuse for:', 'saint-porphyrius'); ?> ' + name);
                $('#sp-deny-penalty').text(penalty);
                $('#sp-deny-modal').show();
            });
            
            // View full excuse
            $('.sp-view-excuse').on('click', function() {
                var excuse = $(this).data('excuse');
                var notes = $(this).data('notes');
                $('#sp-full-excuse-text').text(excuse);
                if (notes) {
                    $('#sp-excuse-notes-text').text(notes);
                    $('#sp-excuse-admin-notes').show();
                } else {
                    $('#sp-excuse-admin-notes').hide();
                }
                $('#sp-view-excuse-modal').show();
            });
            
            // Close modals
            $('.sp-modal-close, .sp-modal-cancel').on('click', function() {
                $('.sp-modal').hide();
            });
            
            $(window).on('click', function(e) {
                if ($(e.target).hasClass('sp-modal')) {
                    $('.sp-modal').hide();
                }
            });
        });
        </script>
        <?php
    }
}

// Initialize
SP_Admin::get_instance();
