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
$protected_routes = array('dashboard', 'profile', 'events', 'event-single', 'points', 'leaderboard', 'saint-story', 'service-instructions', 'community', 'share-points', 'quizzes');
$admin_routes = array('admin', 'admin/dashboard', 'admin/pending', 'admin/members', 'admin/events', 'admin/event-types', 'admin/bus-bookings', 'admin/bus-templates', 'admin/attendance', 'admin/excuses', 'admin/points', 'admin/forbidden', 'admin/qr-scanner', 'admin/gamification', 'admin/point-sharing', 'admin/quizzes', 'admin/notifications');
$guest_routes = array('home', 'login', 'register');
$blocked_page = 'blocked'; // Page to show for blocked users

// Handle logout
if ($sp_page === 'logout') {
    wp_logout();
    wp_safe_redirect(home_url('/app'));
    exit;
}

// Check if this is an admin route
$is_admin_route = in_array($sp_page, $admin_routes, true);

// Admin routes require admin capability
if ($is_admin_route) {
    if (!is_user_logged_in()) {
        wp_safe_redirect(home_url('/app/login'));
        exit;
    }
    if (!current_user_can('manage_options')) {
        wp_safe_redirect(home_url('/app/dashboard'));
        exit;
    }
}

if (in_array($sp_page, $protected_routes, true) && !is_user_logged_in()) {
    wp_safe_redirect(home_url('/app/login'));
    exit;
}

if (in_array($sp_page, $guest_routes, true) && is_user_logged_in()) {
    wp_safe_redirect(home_url('/app/dashboard'));
    exit;
}

// Check if user is blocked (red card) - redirect to blocked page
if (is_user_logged_in() && in_array($sp_page, $protected_routes, true) && !current_user_can('manage_options')) {
    $forbidden_handler = SP_Forbidden::get_instance();
    if ($forbidden_handler->is_user_blocked(get_current_user_id())) {
        $sp_page = 'blocked';
    }
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#6C9BCF">
    <meta name="format-detection" content="telephone=no">
    
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
            case 'blocked':
                include SP_PLUGIN_DIR . 'templates/blocked.php';
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
            // Admin routes
            case 'admin':
            case 'admin/dashboard':
                include SP_PLUGIN_DIR . 'templates/unified/admin/dashboard.php';
                break;
            case 'admin/pending':
                include SP_PLUGIN_DIR . 'templates/unified/admin/pending.php';
                break;
            case 'admin/members':
                include SP_PLUGIN_DIR . 'templates/unified/admin/members.php';
                break;
            case 'admin/events':
                include SP_PLUGIN_DIR . 'templates/unified/admin/events.php';
                break;
            case 'admin/event-types':
                include SP_PLUGIN_DIR . 'templates/unified/admin/event-types.php';
                break;
            case 'admin/bus-bookings':
                include SP_PLUGIN_DIR . 'templates/unified/admin/bus-bookings.php';
                break;
            case 'admin/bus-templates':
                include SP_PLUGIN_DIR . 'templates/unified/admin/bus-templates.php';
                break;
            case 'admin/attendance':
                include SP_PLUGIN_DIR . 'templates/unified/admin/attendance.php';
                break;
            case 'admin/excuses':
                include SP_PLUGIN_DIR . 'templates/unified/admin/excuses.php';
                break;
            case 'admin/points':
                include SP_PLUGIN_DIR . 'templates/unified/admin/points.php';
                break;
            case 'admin/forbidden':
                include SP_PLUGIN_DIR . 'templates/unified/admin/forbidden.php';
                break;
            case 'admin/qr-scanner':
                include SP_PLUGIN_DIR . 'templates/unified/admin/qr-scanner.php';
                break;
            case 'admin/gamification':
                include SP_PLUGIN_DIR . 'templates/unified/admin/gamification.php';
                break;
            case 'admin/point-sharing':
                include SP_PLUGIN_DIR . 'templates/unified/admin/point-sharing.php';
                break;
            case 'admin/quizzes':
                include SP_PLUGIN_DIR . 'templates/unified/admin/quizzes.php';
                break;
            case 'admin/notifications':
                include SP_PLUGIN_DIR . 'templates/unified/admin/notifications.php';
                break;
            case 'saint-story':
                include SP_PLUGIN_DIR . 'templates/unified/saint-story.php';
                break;
            case 'service-instructions':
                include SP_PLUGIN_DIR . 'templates/unified/service-instructions.php';
                break;
            case 'community':
                include SP_PLUGIN_DIR . 'templates/unified/community.php';
                break;
            case 'share-points':
                include SP_PLUGIN_DIR . 'templates/unified/share-points.php';
                break;
            case 'quizzes':
                include SP_PLUGIN_DIR . 'templates/unified/quizzes.php';
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
        'blocked' => __('حساب محظور', 'saint-porphyrius'),
        'dashboard' => __('لوحة التحكم', 'saint-porphyrius'),
        'profile' => __('الملف الشخصي', 'saint-porphyrius'),
        'events' => __('الفعاليات', 'saint-porphyrius'),
        'event-single' => __('تفاصيل الفعالية', 'saint-porphyrius'),
        'points' => __('نقاطي', 'saint-porphyrius'),
        'leaderboard' => __('لوحة المتصدرين', 'saint-porphyrius'),
        // Admin pages
        'admin' => __('لوحة الإدارة', 'saint-porphyrius'),
        'admin/dashboard' => __('لوحة الإدارة', 'saint-porphyrius'),
        'admin/pending' => __('الموافقات المعلقة', 'saint-porphyrius'),
        'admin/members' => __('الأعضاء', 'saint-porphyrius'),
        'admin/events' => __('إدارة الفعاليات', 'saint-porphyrius'),
        'admin/event-types' => __('أنواع الفعاليات', 'saint-porphyrius'),
        'admin/attendance' => __('تسجيل الحضور', 'saint-porphyrius'),
        'admin/excuses' => __('الاعتذارات', 'saint-porphyrius'),
        'admin/points' => __('إدارة النقاط', 'saint-porphyrius'),
        'admin/forbidden' => __('نظام المحروم', 'saint-porphyrius'),
        'share-points' => __('مشاركة النقاط', 'saint-porphyrius'),
        'quizzes' => __('الاختبارات المسيحية', 'saint-porphyrius'),
        'admin/quizzes' => __('إدارة الاختبارات', 'saint-porphyrius'),
        'admin/notifications' => __('الإشعارات', 'saint-porphyrius'),
        'admin/point-sharing' => __('إعدادات مشاركة النقاط', 'saint-porphyrius'),
    );
    
    return isset($titles[$page]) ? $titles[$page] : $titles['home'];
}
