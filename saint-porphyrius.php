<?php
/**
 * Plugin Name: Saint Porphyrius
 * Plugin URI: https://saintporphyrius.org
 * Description: A mobile-first church community app with Arabic interface
 * Version: 1.0.9
 * Author: Saint Porphyrius Team
 * Text Domain: saint-porphyrius
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SP_PLUGIN_VERSION', '1.0.9');
define('SP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SP_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Saint Porphyrius Plugin Class
 */
class Saint_Porphyrius {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }
    
    private function includes() {
        require_once SP_PLUGIN_DIR . 'includes/class-sp-registration.php';
        require_once SP_PLUGIN_DIR . 'includes/class-sp-admin.php';
        require_once SP_PLUGIN_DIR . 'includes/class-sp-user.php';
        require_once SP_PLUGIN_DIR . 'includes/class-sp-ajax.php';
        require_once SP_PLUGIN_DIR . 'includes/class-sp-migrator.php';
        require_once SP_PLUGIN_DIR . 'includes/class-sp-event-types.php';
        require_once SP_PLUGIN_DIR . 'includes/class-sp-events.php';
        require_once SP_PLUGIN_DIR . 'includes/class-sp-attendance.php';
        require_once SP_PLUGIN_DIR . 'includes/class-sp-points.php';
        require_once SP_PLUGIN_DIR . 'includes/class-sp-excuses.php';
        require_once SP_PLUGIN_DIR . 'includes/class-sp-forbidden.php';
        require_once SP_PLUGIN_DIR . 'includes/class-sp-qr-attendance.php';
        require_once SP_PLUGIN_DIR . 'includes/class-sp-updater.php';
    }
    
    private function init_hooks() {
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Init hooks
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_filter('show_admin_bar', array($this, 'maybe_hide_admin_bar'));
        
        // Custom rewrite rules
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_app_routes'));
        
        // Add custom user role
        add_action('init', array($this, 'add_custom_roles'));
        
        // One-time flush for new routes
        add_action('admin_init', array($this, 'maybe_flush_rewrite_rules'));
    }
    
    public function activate() {
        // Create custom database tables
        $this->create_tables();
        
        // Run migrations
        $this->run_migrations();
        
        // Add custom roles
        $this->add_custom_roles();
        
        // Create app pages
        $this->create_app_pages();
        
        // Flush rewrite rules
        $this->add_rewrite_rules();
        flush_rewrite_rules();
    }
    
    private function run_migrations() {
        $migrator = SP_Migrator::get_instance();
        $migrator->run();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Flush rewrite rules once after adding new routes
     */
    public function maybe_flush_rewrite_rules() {
        if (get_option('sp_flush_rewrite_rules') !== 'done') {
            flush_rewrite_rules();
            update_option('sp_flush_rewrite_rules', 'done');
        }
    }
    
    public function init() {
        load_plugin_textdomain('saint-porphyrius', false, dirname(SP_PLUGIN_BASENAME) . '/languages');
        
        // Run any pending migrations (important for updates)
        if (is_admin() && current_user_can('manage_options')) {
            $migrator = SP_Migrator::get_instance();
            $pending = $migrator->get_pending_migrations();
            
            if (!empty($pending)) {
                $migrator->run();
            }
        }
    }

    public function maybe_hide_admin_bar($show) {
        if (is_admin()) {
            return $show;
        }

        return current_user_can('manage_options') ? $show : false;
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sp_pending_users (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            first_name varchar(100) NOT NULL,
            middle_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            password varchar(255) NOT NULL,
            phone varchar(50) NOT NULL,
            home_address text NOT NULL,
            church_name varchar(255) NOT NULL,
            confession_father varchar(255) NOT NULL,
            job_or_college varchar(255) NOT NULL,
            current_church_service varchar(255) NOT NULL,
            church_family varchar(255) NOT NULL,
            church_family_servant varchar(255) NOT NULL,
            facebook_link varchar(255) DEFAULT '',
            instagram_link varchar(255) DEFAULT '',
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            approved_at datetime DEFAULT NULL,
            approved_by bigint(20) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY email (email)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function add_custom_roles() {
        // Add church member role
        add_role('sp_member', __('Church Member', 'saint-porphyrius'), array(
            'read' => true,
        ));
        
        // Add church admin role
        add_role('sp_church_admin', __('Church Admin', 'saint-porphyrius'), array(
            'read' => true,
            'sp_manage_members' => true,
            'sp_approve_members' => true,
        ));
    }
    
    private function create_app_pages() {
        // Create main app page
        $app_page = get_page_by_path('app');
        if (!$app_page) {
            wp_insert_post(array(
                'post_title' => 'Saint Porphyrius App',
                'post_name' => 'app',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_content' => '[saint_porphyrius_app]',
            ));
        }
    }
    
    public function add_rewrite_rules() {
        add_rewrite_rule('^app/?$', 'index.php?sp_app=home', 'top');
        add_rewrite_rule('^app/register/?$', 'index.php?sp_app=register', 'top');
        add_rewrite_rule('^app/login/?$', 'index.php?sp_app=login', 'top');
        add_rewrite_rule('^app/logout/?$', 'index.php?sp_app=logout', 'top');
        add_rewrite_rule('^app/pending/?$', 'index.php?sp_app=pending', 'top');
        add_rewrite_rule('^app/dashboard/?$', 'index.php?sp_app=dashboard', 'top');
        add_rewrite_rule('^app/profile/?$', 'index.php?sp_app=profile', 'top');
        add_rewrite_rule('^app/events/?$', 'index.php?sp_app=events', 'top');
        add_rewrite_rule('^app/events/([0-9]+)/?$', 'index.php?sp_app=event-single&sp_event_id=$matches[1]', 'top');
        add_rewrite_rule('^app/points/?$', 'index.php?sp_app=points', 'top');
        add_rewrite_rule('^app/leaderboard/?$', 'index.php?sp_app=leaderboard', 'top');
        
        // Admin routes
        add_rewrite_rule('^app/admin/?$', 'index.php?sp_app=admin', 'top');
        add_rewrite_rule('^app/admin/dashboard/?$', 'index.php?sp_app=admin/dashboard', 'top');
        add_rewrite_rule('^app/admin/pending/?$', 'index.php?sp_app=admin/pending', 'top');
        add_rewrite_rule('^app/admin/members/?$', 'index.php?sp_app=admin/members', 'top');
        add_rewrite_rule('^app/admin/events/?$', 'index.php?sp_app=admin/events', 'top');
        add_rewrite_rule('^app/admin/attendance/?$', 'index.php?sp_app=admin/attendance', 'top');
        add_rewrite_rule('^app/admin/excuses/?$', 'index.php?sp_app=admin/excuses', 'top');
        add_rewrite_rule('^app/admin/points/?$', 'index.php?sp_app=admin/points', 'top');
        add_rewrite_rule('^app/admin/forbidden/?$', 'index.php?sp_app=admin/forbidden', 'top');
        add_rewrite_rule('^app/admin/qr-scanner/?$', 'index.php?sp_app=admin/qr-scanner', 'top');
    }
    
    public function add_query_vars($vars) {
        $vars[] = 'sp_app';
        $vars[] = 'sp_event_id';
        return $vars;
    }
    
    public function handle_app_routes() {
        $sp_app = get_query_var('sp_app');
        
        if (!empty($sp_app)) {
            // Load app template
            include SP_PLUGIN_DIR . 'templates/app-wrapper.php';
            exit;
        }
    }
    
    public function enqueue_frontend_assets() {
        $sp_app = get_query_var('sp_app');
        
        if (!empty($sp_app) || is_page('app')) {
            // Google Fonts - Cairo for Arabic
            wp_enqueue_style('sp-google-fonts', 'https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap', array(), null);
            
            // Dashicons for icons
            wp_enqueue_style('dashicons');
            
            // Main styles
            wp_enqueue_style('sp-main-styles', SP_PLUGIN_URL . 'assets/css/main.css', array('dashicons'), SP_PLUGIN_VERSION);
            
            // Unified design system styles
            wp_enqueue_style('sp-unified-styles', SP_PLUGIN_URL . 'assets/css/unified.css', array('sp-main-styles'), SP_PLUGIN_VERSION);
            
            // Main scripts
            wp_enqueue_script('sp-main-scripts', SP_PLUGIN_URL . 'assets/js/main.js', array('jquery'), SP_PLUGIN_VERSION, true);
            
            // Localize script
            wp_localize_script('sp-main-scripts', 'spApp', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sp_nonce'),
                'appUrl' => home_url('/app'),
                'strings' => array(
                    'loading' => 'جاري التحميل...',
                    'error' => 'حدث خطأ، يرجى المحاولة مرة أخرى',
                    'success' => 'تم بنجاح',
                    'required' => 'هذا الحقل مطلوب',
                    'invalidEmail' => 'البريد الإلكتروني غير صحيح',
                    'passwordMismatch' => 'كلمة المرور غير متطابقة',
                    'passwordWeak' => 'كلمة المرور ضعيفة',
                ),
            ));
        }
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'saint-porphyrius') !== false) {
            wp_enqueue_style('sp-admin-styles', SP_PLUGIN_URL . 'assets/css/admin.css', array(), SP_PLUGIN_VERSION);
            wp_enqueue_script('sp-admin-scripts', SP_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), SP_PLUGIN_VERSION, true);
            
            wp_localize_script('sp-admin-scripts', 'spAdmin', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sp_admin_nonce'),
            ));
        }
    }
}

// Initialize the plugin
function saint_porphyrius() {
    return Saint_Porphyrius::get_instance();
}

// Start the plugin
saint_porphyrius();
