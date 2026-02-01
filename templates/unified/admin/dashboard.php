<?php
/**
 * Saint Porphyrius - Admin Dashboard (Mobile)
 * Main admin dashboard for mobile app
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get stats
global $wpdb;
$pending_table = $wpdb->prefix . 'sp_pending_users';
$pending_count = $wpdb->get_var("SELECT COUNT(*) FROM $pending_table WHERE status = 'pending'");

$members_count = count(get_users(array(
    'role__in' => array('sp_member', 'sp_church_admin'),
)));

$events_handler = SP_Events::get_instance();
$upcoming_events = $events_handler->get_upcoming(5);
$events_count = count($upcoming_events);

$excuses_handler = SP_Excuses::get_instance();
$pending_excuses = $excuses_handler->count_pending();

$points_handler = SP_Points::get_instance();
$stats = $points_handler->get_summary_stats();
?>

<!-- Admin Header -->
<div class="sp-unified-header sp-admin-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('لوحة الإدارة', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-spacer"></div>
    </div>
</div>

<!-- Main Content -->
<main class="sp-page-content sp-admin-content">
    <!-- Quick Stats -->
    <div class="sp-admin-stats-grid">
        <a href="<?php echo home_url('/app/admin/pending'); ?>" class="sp-admin-stat-card <?php echo $pending_count > 0 ? 'has-alert' : ''; ?>">
            <div class="sp-admin-stat-icon" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);">
                ⏳
            </div>
            <div class="sp-admin-stat-info">
                <span class="sp-admin-stat-value"><?php echo esc_html($pending_count); ?></span>
                <span class="sp-admin-stat-label"><?php _e('طلبات معلقة', 'saint-porphyrius'); ?></span>
            </div>
            <?php if ($pending_count > 0): ?>
                <span class="sp-admin-stat-badge"><?php _e('جديد', 'saint-porphyrius'); ?></span>
            <?php endif; ?>
        </a>
        
        <a href="<?php echo home_url('/app/admin/members'); ?>" class="sp-admin-stat-card">
            <div class="sp-admin-stat-icon" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                👥
            </div>
            <div class="sp-admin-stat-info">
                <span class="sp-admin-stat-value"><?php echo esc_html($members_count); ?></span>
                <span class="sp-admin-stat-label"><?php _e('الأعضاء', 'saint-porphyrius'); ?></span>
            </div>
        </a>
        
        <a href="<?php echo home_url('/app/admin/events'); ?>" class="sp-admin-stat-card">
            <div class="sp-admin-stat-icon" style="background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);">
                📅
            </div>
            <div class="sp-admin-stat-info">
                <span class="sp-admin-stat-value"><?php echo esc_html($events_count); ?></span>
                <span class="sp-admin-stat-label"><?php _e('فعاليات قادمة', 'saint-porphyrius'); ?></span>
            </div>
        </a>
        
        <a href="<?php echo home_url('/app/admin/excuses'); ?>" class="sp-admin-stat-card <?php echo $pending_excuses > 0 ? 'has-alert' : ''; ?>">
            <div class="sp-admin-stat-icon" style="background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);">
                📝
            </div>
            <div class="sp-admin-stat-info">
                <span class="sp-admin-stat-value"><?php echo esc_html($pending_excuses); ?></span>
                <span class="sp-admin-stat-label"><?php _e('اعتذارات معلقة', 'saint-porphyrius'); ?></span>
            </div>
            <?php if ($pending_excuses > 0): ?>
                <span class="sp-admin-stat-badge"><?php _e('جديد', 'saint-porphyrius'); ?></span>
            <?php endif; ?>
        </a>
    </div>

    <!-- Admin Menu -->
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title"><?php _e('الإدارة', 'saint-porphyrius'); ?></h3>
        </div>
        
        <div class="sp-admin-menu">
            <a href="<?php echo home_url('/app/admin/pending'); ?>" class="sp-admin-menu-item">
                <div class="sp-admin-menu-icon" style="background: #FEF3C7; color: #D97706;">⏳</div>
                <div class="sp-admin-menu-content">
                    <h4><?php _e('الموافقات المعلقة', 'saint-porphyrius'); ?></h4>
                    <p><?php _e('مراجعة طلبات التسجيل الجديدة', 'saint-porphyrius'); ?></p>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>
            
            <a href="<?php echo home_url('/app/admin/members'); ?>" class="sp-admin-menu-item">
                <div class="sp-admin-menu-icon" style="background: #D1FAE5; color: #059669;">👥</div>
                <div class="sp-admin-menu-content">
                    <h4><?php _e('الأعضاء', 'saint-porphyrius'); ?></h4>
                    <p><?php _e('عرض وإدارة أعضاء الأسرة', 'saint-porphyrius'); ?></p>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>
            
            <a href="<?php echo home_url('/app/admin/events'); ?>" class="sp-admin-menu-item">
                <div class="sp-admin-menu-icon" style="background: #DBEAFE; color: #2563EB;">📅</div>
                <div class="sp-admin-menu-content">
                    <h4><?php _e('الفعاليات', 'saint-porphyrius'); ?></h4>
                    <p><?php _e('إنشاء وإدارة الفعاليات', 'saint-porphyrius'); ?></p>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>
            
            <a href="<?php echo home_url('/app/admin/attendance'); ?>" class="sp-admin-menu-item">
                <div class="sp-admin-menu-icon" style="background: #FEE2E2; color: #DC2626;">✅</div>
                <div class="sp-admin-menu-content">
                    <h4><?php _e('الحضور', 'saint-porphyrius'); ?></h4>
                    <p><?php _e('تسجيل حضور الأعضاء', 'saint-porphyrius'); ?></p>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>
            
            <a href="<?php echo home_url('/app/admin/qr-scanner'); ?>" class="sp-admin-menu-item" style="background: linear-gradient(135deg, var(--sp-primary) 0%, var(--sp-primary-dark, #5A8AC7) 100%); color: white;">
                <div class="sp-admin-menu-icon" style="background: rgba(255,255,255,0.2); color: white;">📱</div>
                <div class="sp-admin-menu-content" style="color: white;">
                    <h4 style="color: white;"><?php _e('ماسح QR للحضور', 'saint-porphyrius'); ?></h4>
                    <p style="color: rgba(255,255,255,0.9);"><?php _e('مسح رموز QR لتسجيل الحضور بسرعة', 'saint-porphyrius'); ?></p>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity: 0.8;">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>
            
            <a href="<?php echo home_url('/app/admin/excuses'); ?>" class="sp-admin-menu-item">
                <div class="sp-admin-menu-icon" style="background: #EDE9FE; color: #7C3AED;">📝</div>
                <div class="sp-admin-menu-content">
                    <h4><?php _e('الاعتذارات', 'saint-porphyrius'); ?></h4>
                    <p><?php _e('مراجعة طلبات الاعتذار', 'saint-porphyrius'); ?></p>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>
            
            <a href="<?php echo home_url('/app/admin/points'); ?>" class="sp-admin-menu-item">
                <div class="sp-admin-menu-icon" style="background: #FEF3C7; color: #B45309;">⭐</div>
                <div class="sp-admin-menu-content">
                    <h4><?php _e('النقاط', 'saint-porphyrius'); ?></h4>
                    <p><?php _e('إدارة نقاط الأعضاء', 'saint-porphyrius'); ?></p>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>
            
            <?php 
            // Get forbidden counts for badge
            $forbidden_handler = SP_Forbidden::get_instance();
            $forbidden_counts = $forbidden_handler->count_by_status();
            $has_forbidden_alerts = $forbidden_counts['red_card'] > 0 || $forbidden_counts['forbidden'] > 0;
            ?>
            <a href="<?php echo home_url('/app/admin/forbidden'); ?>" class="sp-admin-menu-item <?php echo $has_forbidden_alerts ? 'has-alert' : ''; ?>">
                <div class="sp-admin-menu-icon" style="background: #FEE2E2; color: #B91C1C;">⛔</div>
                <div class="sp-admin-menu-content">
                    <h4><?php _e('نظام المحروم', 'saint-porphyrius'); ?></h4>
                    <p><?php _e('إدارة الحرمان والكروت', 'saint-porphyrius'); ?></p>
                </div>
                <?php if ($forbidden_counts['red_card'] > 0): ?>
                    <span class="sp-admin-stat-badge danger"><?php echo esc_html($forbidden_counts['red_card']); ?> 🔴</span>
                <?php endif; ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>
        </div>
    </div>

    <!-- Upcoming Events Preview -->
    <?php if (!empty($upcoming_events)): ?>
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title"><?php _e('الفعاليات القادمة', 'saint-porphyrius'); ?></h3>
            <a href="<?php echo home_url('/app/admin/events'); ?>" class="sp-section-link"><?php _e('عرض الكل', 'saint-porphyrius'); ?></a>
        </div>
        
        <div class="sp-admin-events-preview">
            <?php foreach (array_slice($upcoming_events, 0, 3) as $event): ?>
                <a href="<?php echo home_url('/app/admin/attendance?event_id=' . $event->id); ?>" class="sp-admin-event-card">
                    <div class="sp-admin-event-date">
                        <span class="day"><?php echo esc_html(date_i18n('j', strtotime($event->event_date))); ?></span>
                        <span class="month"><?php echo esc_html(date_i18n('M', strtotime($event->event_date))); ?></span>
                    </div>
                    <div class="sp-admin-event-info">
                        <div class="sp-admin-event-type" style="color: <?php echo esc_attr($event->type_color); ?>;">
                            <?php echo esc_html($event->type_icon . ' ' . $event->type_name_ar); ?>
                        </div>
                        <h4><?php echo esc_html($event->title_ar); ?></h4>
                        <span class="sp-admin-event-time"><?php echo esc_html($event->start_time); ?></span>
                    </div>
                    <div class="sp-admin-event-action">
                        <?php _e('تسجيل', 'saint-porphyrius'); ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Points Summary -->
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title"><?php _e('ملخص النقاط', 'saint-porphyrius'); ?></h3>
        </div>
        
        <div class="sp-admin-points-summary">
            <div class="sp-admin-points-item">
                <span class="sp-admin-points-label"><?php _e('إجمالي المكافآت', 'saint-porphyrius'); ?></span>
                <span class="sp-admin-points-value positive">+<?php echo esc_html($stats->total_awarded ?? 0); ?></span>
            </div>
            <div class="sp-admin-points-item">
                <span class="sp-admin-points-label"><?php _e('إجمالي الخصومات', 'saint-porphyrius'); ?></span>
                <span class="sp-admin-points-value negative"><?php echo esc_html($stats->total_penalties ?? 0); ?></span>
            </div>
            <div class="sp-admin-points-item">
                <span class="sp-admin-points-label"><?php _e('أعضاء لديهم نقاط', 'saint-porphyrius'); ?></span>
                <span class="sp-admin-points-value"><?php echo esc_html($stats->members_with_points ?? 0); ?></span>
            </div>
        </div>
    </div>

    <!-- Back to App -->
    <div class="sp-admin-back-link">
        <a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-btn sp-btn-outline">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            <?php _e('العودة للتطبيق', 'saint-porphyrius'); ?>
        </a>
    </div>
</main>
