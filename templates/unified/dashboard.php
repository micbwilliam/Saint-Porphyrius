<?php
/**
 * Saint Porphyrius - Dashboard Template (Unified Design)
 * User dashboard after login - Modern, professional design
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$first_name = $current_user->first_name;
$middle_name = get_user_meta($current_user->ID, 'sp_middle_name', true);
$last_name = $current_user->last_name;
$church_name = get_user_meta($current_user->ID, 'sp_church_name', true);

// Get points and events data
$points_handler = SP_Points::get_instance();
$events_handler = SP_Events::get_instance();

$user_points = $points_handler->get_balance($current_user->ID);
$upcoming_events = $events_handler->get_upcoming(3);

// Get attendance stats
global $wpdb;
$attendance_table = $wpdb->prefix . 'sp_attendance';
$total_events_attended = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$attendance_table} WHERE user_id = %d AND status = 'present'",
    $current_user->ID
));

// Calculate user rank
$leaderboard = $points_handler->get_leaderboard(100);
$user_rank = 0;
foreach ($leaderboard as $index => $user) {
    if ($user->user_id == $current_user->ID) {
        $user_rank = $index + 1;
        break;
    }
}
?>

<!-- Unified Header -->
<div class="sp-unified-header">
    <div class="sp-header-inner">
        <div class="sp-header-logo">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
        </div>
        <h1 class="sp-header-title"><?php _e('الرئيسية', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-actions">
            <a href="<?php echo home_url('/app/profile'); ?>" class="sp-header-action">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </a>
        </div>
    </div>
</div>

<!-- Main Content -->
<main class="sp-page-content has-bottom-nav">
    <!-- Hero Card with User Info -->
    <div class="sp-hero-card">
        <div class="sp-hero-content">
            <div class="sp-hero-text">
                <h2><?php printf(__('مرحباً، %s!', 'saint-porphyrius'), esc_html($first_name)); ?></h2>
                <p><?php echo esc_html($church_name); ?></p>
            </div>
            <div class="sp-hero-stat">
                <span class="sp-hero-stat-value"><?php echo esc_html($user_points); ?></span>
                <span class="sp-hero-stat-label"><?php _e('نقطة', 'saint-porphyrius'); ?></span>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="sp-stats-row">
        <div class="sp-stat-card">
            <div class="sp-stat-value"><?php echo esc_html($user_points); ?></div>
            <div class="sp-stat-label"><?php _e('نقاطي', 'saint-porphyrius'); ?></div>
        </div>
        <div class="sp-stat-card">
            <div class="sp-stat-value"><?php echo esc_html($total_events_attended ?: 0); ?></div>
            <div class="sp-stat-label"><?php _e('حضور', 'saint-porphyrius'); ?></div>
        </div>
        <div class="sp-stat-card">
            <div class="sp-stat-value">#<?php echo esc_html($user_rank ?: '-'); ?></div>
            <div class="sp-stat-label"><?php _e('ترتيبي', 'saint-porphyrius'); ?></div>
        </div>
    </div>

    <!-- Quick Actions Section -->
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title"><?php _e('الخدمات السريعة', 'saint-porphyrius'); ?></h3>
        </div>
        
        <div class="sp-list">
            <a href="<?php echo home_url('/app/events'); ?>" class="sp-feature-card">
                <div class="sp-feature-icon" style="background: linear-gradient(135deg, #96C291 0%, #7DAF78 100%); color: white;">
                    📅
                </div>
                <div class="sp-feature-content">
                    <h4 class="sp-feature-title"><?php _e('الفعاليات', 'saint-porphyrius'); ?></h4>
                    <p class="sp-feature-desc"><?php _e('تصفح الفعاليات القادمة', 'saint-porphyrius'); ?></p>
                </div>
                <svg class="sp-feature-arrow" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>
            
            <a href="<?php echo home_url('/app/points'); ?>" class="sp-feature-card">
                <div class="sp-feature-icon" style="background: linear-gradient(135deg, #F2D388 0%, #E5C470 100%); color: white;">
                    ⭐
                </div>
                <div class="sp-feature-content">
                    <h4 class="sp-feature-title"><?php _e('نقاطي', 'saint-porphyrius'); ?></h4>
                    <p class="sp-feature-desc"><?php _e('تتبع نقاطك وسجل النقاط', 'saint-porphyrius'); ?></p>
                </div>
                <svg class="sp-feature-arrow" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>
            
            <a href="<?php echo home_url('/app/leaderboard'); ?>" class="sp-feature-card">
                <div class="sp-feature-icon" style="background: linear-gradient(135deg, #6C9BCF 0%, #5A89BD 100%); color: white;">
                    🏆
                </div>
                <div class="sp-feature-content">
                    <h4 class="sp-feature-title"><?php _e('المتصدرين', 'saint-porphyrius'); ?></h4>
                    <p class="sp-feature-desc"><?php _e('شاهد ترتيبك بين الأعضاء', 'saint-porphyrius'); ?></p>
                </div>
                <svg class="sp-feature-arrow" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>
        </div>
    </div>

    <!-- Upcoming Events Section -->
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title"><?php _e('الفعاليات القادمة', 'saint-porphyrius'); ?></h3>
            <a href="<?php echo home_url('/app/events'); ?>" class="sp-section-link"><?php _e('عرض الكل', 'saint-porphyrius'); ?></a>
        </div>
        
        <?php if (empty($upcoming_events)): ?>
            <div class="sp-card">
                <div class="sp-empty">
                    <div class="sp-empty-icon">📅</div>
                    <h4 class="sp-empty-title"><?php _e('لا توجد فعاليات قادمة', 'saint-porphyrius'); ?></h4>
                    <p class="sp-empty-text"><?php _e('سيتم إضافة فعاليات جديدة قريباً', 'saint-porphyrius'); ?></p>
                </div>
            </div>
        <?php else: ?>
            <div class="sp-events-list">
                <?php foreach ($upcoming_events as $event): 
                    $event_date = strtotime($event->event_date);
                    $is_today = date('Y-m-d') === $event->event_date;
                    $is_tomorrow = date('Y-m-d', strtotime('+1 day')) === $event->event_date;
                    $points_config = $events_handler->get_event_points($event);
                ?>
                    <a href="<?php echo home_url('/app/events/' . $event->id); ?>" class="sp-event-card" style="--event-color: <?php echo esc_attr($event->type_color); ?>;">
                        <div class="sp-event-date-badge">
                            <?php if ($is_today): ?>
                                <span class="sp-event-date-label"><?php _e('اليوم', 'saint-porphyrius'); ?></span>
                            <?php elseif ($is_tomorrow): ?>
                                <span class="sp-event-date-label"><?php _e('غداً', 'saint-porphyrius'); ?></span>
                            <?php else: ?>
                                <span class="sp-event-date-day"><?php echo esc_html(date_i18n('j', $event_date)); ?></span>
                                <span class="sp-event-date-month"><?php echo esc_html(date_i18n('M', $event_date)); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="sp-event-info">
                            <div class="sp-event-type">
                                <span class="sp-event-type-icon"><?php echo esc_html($event->type_icon); ?></span>
                                <span><?php echo esc_html($event->type_name_ar); ?></span>
                            </div>
                            
                            <h3 class="sp-event-title"><?php echo esc_html($event->title_ar); ?></h3>
                            
                            <div class="sp-event-meta">
                                <span>
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php echo esc_html($event->start_time); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="sp-event-points">
                            <span class="sp-points-value">+<?php echo esc_html($points_config['attendance']); ?></span>
                            <span class="sp-points-label"><?php _e('نقطة', 'saint-porphyrius'); ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Logout Button -->
    <div class="sp-section">
        <button type="button" class="sp-btn sp-btn-secondary sp-btn-block" id="sp-logout-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <?php _e('تسجيل الخروج', 'saint-porphyrius'); ?>
        </button>
    </div>
</main>

<!-- Unified Bottom Navigation -->
<nav class="sp-unified-nav">
    <div class="sp-nav-inner">
        <a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-nav-item active">
            <div class="sp-nav-indicator"></div>
            <div class="sp-nav-icon">
                <span class="dashicons dashicons-dashboard"></span>
            </div>
            <span class="sp-nav-label"><?php _e('الرئيسية', 'saint-porphyrius'); ?></span>
        </a>
        <a href="<?php echo home_url('/app/events'); ?>" class="sp-nav-item">
            <div class="sp-nav-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <span class="sp-nav-label"><?php _e('الفعاليات', 'saint-porphyrius'); ?></span>
        </a>
        <a href="<?php echo home_url('/app/points'); ?>" class="sp-nav-item">
            <div class="sp-nav-icon">
                <span class="dashicons dashicons-star-filled"></span>
            </div>
            <span class="sp-nav-label"><?php _e('نقاطي', 'saint-porphyrius'); ?></span>
        </a>
        <a href="<?php echo home_url('/app/leaderboard'); ?>" class="sp-nav-item">
            <div class="sp-nav-icon">
                <span class="dashicons dashicons-awards"></span>
            </div>
            <span class="sp-nav-label"><?php _e('المتصدرين', 'saint-porphyrius'); ?></span>
        </a>
        <a href="<?php echo home_url('/app/profile'); ?>" class="sp-nav-item">
            <div class="sp-nav-icon">
                <span class="dashicons dashicons-admin-users"></span>
            </div>
            <span class="sp-nav-label"><?php _e('حسابي', 'saint-porphyrius'); ?></span>
        </a>
    </div>
</nav>

<script>
document.getElementById('sp-logout-btn')?.addEventListener('click', function() {
    if (confirm('<?php _e('هل أنت متأكد من تسجيل الخروج؟', 'saint-porphyrius'); ?>')) {
        window.location.href = '<?php echo wp_logout_url(home_url('/app/login')); ?>';
    }
});
</script>
