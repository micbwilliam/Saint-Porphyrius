<?php
/**
 * Saint Porphyrius - App Wrapper Template
 * Main template wrapper for the mobile app
 */

if (!defined('ABSPATH')) {
    exit;
}

$sp_page = get_query_var('sp_app');
$sp_page = $sp_page ? $sp_page : 'home';

// Handle auth redirects before any output
$protected_routes = array('dashboard', 'profile', 'events', 'event-single', 'points', 'leaderboard');
$guest_routes = array('home', 'login', 'register');

// Handle logout
if ($sp_page === 'logout') {
    wp_logout();
    wp_safe_redirect(home_url('/app'));
    exit;
}

if (in_array($sp_page, $protected_routes, true) && !is_user_logged_in()) {
    wp_safe_redirect(home_url('/app/login'));
    exit;
}

if (in_array($sp_page, $guest_routes, true) && is_user_logged_in()) {
    wp_safe_redirect(home_url('/app/dashboard'));
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#6C9BCF">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?php _e('القديس بورفيريوس', 'saint-porphyrius'); ?>">
    
    <title><?php echo esc_html(sp_get_page_title($sp_page)); ?> - <?php _e('القديس بورفيريوس', 'saint-porphyrius'); ?></title>
    
    <?php wp_head(); ?>
</head>
<body class="sp-app-body sp-page-<?php echo esc_attr($sp_page); ?>">
    <div class="sp-app">
        <?php
        // Use unified templates for logged-in pages, keep original for auth pages
        $unified_pages = array('dashboard', 'profile', 'events', 'event-single', 'points', 'leaderboard');
        
        // Load the appropriate template based on the route
        switch ($sp_page) {
            case 'register':
                include SP_PLUGIN_DIR . 'templates/register.php';
                break;
            case 'login':
                include SP_PLUGIN_DIR . 'templates/login.php';
                break;
            case 'pending':
                include SP_PLUGIN_DIR . 'templates/pending.php';
                break;
            case 'dashboard':
                include SP_PLUGIN_DIR . 'templates/unified/dashboard.php';
                break;
            case 'profile':
                include SP_PLUGIN_DIR . 'templates/unified/profile.php';
                break;
            case 'events':
                include SP_PLUGIN_DIR . 'templates/unified/events.php';
                break;
            case 'event-single':
                include SP_PLUGIN_DIR . 'templates/unified/event-single.php';
                break;
            case 'points':
                include SP_PLUGIN_DIR . 'templates/unified/points.php';
                break;
            case 'leaderboard':
                include SP_PLUGIN_DIR . 'templates/unified/leaderboard.php';
                break;
            default:
                include SP_PLUGIN_DIR . 'templates/home.php';
                break;
        }
        ?>
    </div>
    
    <?php wp_footer(); ?>
</body>
</html>
<?php

/**
 * Get page title based on route
 */
function sp_get_page_title($page) {
    $titles = array(
        'home' => __('الرئيسية', 'saint-porphyrius'),
        'register' => __('التسجيل', 'saint-porphyrius'),
        'login' => __('تسجيل الدخول', 'saint-porphyrius'),
        'pending' => __('في انتظار الموافقة', 'saint-porphyrius'),
        'dashboard' => __('لوحة التحكم', 'saint-porphyrius'),
        'profile' => __('الملف الشخصي', 'saint-porphyrius'),
        'events' => __('الفعاليات', 'saint-porphyrius'),
        'event-single' => __('تفاصيل الفعالية', 'saint-porphyrius'),
        'points' => __('نقاطي', 'saint-porphyrius'),
        'leaderboard' => __('لوحة المتصدرين', 'saint-porphyrius'),
    );
    
    return isset($titles[$page]) ? $titles[$page] : $titles['home'];
}
