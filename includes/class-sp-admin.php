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
        ?>
        <div class="wrap sp-admin-wrap">
            <h1><?php _e('Saint Porphyrius Settings', 'saint-porphyrius'); ?></h1>
            
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
        </div>
        <?php
    }
}

// Initialize
SP_Admin::get_instance();
