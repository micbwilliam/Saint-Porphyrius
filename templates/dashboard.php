<?php
/**
 * Saint Porphyrius - Dashboard Template
 * User dashboard after login
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
?>

<div class="sp-app-content">
    <!-- Header -->
    <header class="sp-header">
        <div class="sp-header-content">
            <h1 class="sp-header-title">
                <svg class="sp-header-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                الرئيسية
            </h1>
            <a href="<?php echo home_url('/app/profile'); ?>" class="sp-header-back">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="sp-page">
        <!-- Welcome Card -->
        <div class="sp-card sp-welcome-card">
            <div class="sp-welcome-content">
                <div class="sp-welcome-avatar">
                    <?php echo get_avatar($current_user->ID, 60); ?>
                </div>
                <div class="sp-welcome-text">
                    <h2>مرحباً، <?php echo esc_html($first_name); ?>!</h2>
                    <p><?php echo esc_html($church_name); ?></p>
                </div>
                <div class="sp-welcome-points">
                    <span class="sp-welcome-points-value"><?php echo esc_html($user_points); ?></span>
                    <span class="sp-welcome-points-label"><?php _e('نقطة', 'saint-porphyrius'); ?></span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="sp-section">
            <h3 class="sp-section-title">الخدمات السريعة</h3>
            <div class="sp-quick-actions">
                <a href="<?php echo home_url('/app/events'); ?>" class="sp-action-card">
                    <div class="sp-action-icon" style="background: linear-gradient(135deg, #96C291 0%, #7DAF78 100%);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <span>الفعاليات</span>
                </a>
                
                <a href="<?php echo home_url('/app/points'); ?>" class="sp-action-card">
                    <div class="sp-action-icon" style="background: linear-gradient(135deg, #F2D388 0%, #E5C470 100%);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                        </svg>
                    </div>
                    <span>نقاطي</span>
                </a>
                
                <a href="<?php echo home_url('/app/leaderboard'); ?>" class="sp-action-card">
                    <div class="sp-action-icon" style="background: linear-gradient(135deg, #6C9BCF 0%, #5A89BD 100%);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M8.21 13.89L7 23l5-3 5 3-1.21-9.12"></path>
                            <circle cx="12" cy="8" r="7"></circle>
                        </svg>
                    </div>
                    <span>المتصدرين</span>
                </a>
                
                <a href="<?php echo home_url('/app/profile'); ?>" class="sp-action-card">
                    <div class="sp-action-icon" style="background: linear-gradient(135deg, #E8A0A0 0%, #D68B8B 100%);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <span>حسابي</span>
                </a>
            </div>
        </div>

        <!-- Upcoming Events -->
        <div class="sp-section">
            <div class="sp-section-header">
                <h3 class="sp-section-title">الفعاليات القادمة</h3>
                <a href="<?php echo home_url('/app/events'); ?>" class="sp-section-link"><?php _e('عرض الكل', 'saint-porphyrius'); ?></a>
            </div>
            <?php if (empty($upcoming_events)): ?>
                <div class="sp-card">
                    <div class="sp-empty-state-small">
                        <p>📅 لا توجد فعاليات قادمة</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="sp-events-mini">
                    <?php foreach ($upcoming_events as $event): 
                        $event_date = strtotime($event->event_date);
                        $points_config = $events_handler->get_event_points($event);
                    ?>
                        <a href="<?php echo home_url('/app/events/' . $event->id); ?>" class="sp-event-mini">
                            <div class="sp-event-mini-icon" style="color: <?php echo esc_attr($event->type_color); ?>;">
                                <?php echo esc_html($event->type_icon); ?>
                            </div>
                            <div class="sp-event-mini-content">
                                <span class="sp-event-mini-title"><?php echo esc_html($event->title_ar); ?></span>
                                <span class="sp-event-mini-date">
                                    <?php echo esc_html(date_i18n('l j M', $event_date)); ?> - <?php echo esc_html($event->start_time); ?>
                                </span>
                            </div>
                            <div class="sp-event-mini-points">+<?php echo esc_html($points_config['attendance']); ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Logout Button -->
        <div class="sp-mt-lg">
            <button type="button" class="sp-btn sp-btn-secondary sp-btn-block" id="sp-logout-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                تسجيل الخروج
            </button>
        </div>
    </main>

    <!-- Footer -->
    <footer class="sp-footer">
        <p>© <?php echo date('Y'); ?> القديس بورفيريوس - جميع الحقوق محفوظة</p>
    </footer>
</div>

<!-- Bottom Navigation -->
<nav class="sp-bottom-nav">
    <a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-nav-item active">
        <span class="dashicons dashicons-dashboard"></span>
        <span><?php _e('الرئيسية', 'saint-porphyrius'); ?></span>
    </a>
    <a href="<?php echo home_url('/app/events'); ?>" class="sp-nav-item">
        <span class="dashicons dashicons-calendar-alt"></span>
        <span><?php _e('الفعاليات', 'saint-porphyrius'); ?></span>
    </a>
    <a href="<?php echo home_url('/app/points'); ?>" class="sp-nav-item">
        <span class="dashicons dashicons-star-filled"></span>
        <span><?php _e('نقاطي', 'saint-porphyrius'); ?></span>
    </a>
    <a href="<?php echo home_url('/app/leaderboard'); ?>" class="sp-nav-item">
        <span class="dashicons dashicons-awards"></span>
        <span><?php _e('المتصدرين', 'saint-porphyrius'); ?></span>
    </a>
    <a href="<?php echo home_url('/app/profile'); ?>" class="sp-nav-item">
        <span class="dashicons dashicons-admin-users"></span>
        <span><?php _e('حسابي', 'saint-porphyrius'); ?></span>
    </a>
</nav>

<style>
.sp-welcome-card {
    background: linear-gradient(135deg, var(--sp-primary) 0%, var(--sp-primary-dark) 100%);
    color: var(--sp-text-white);
}

.sp-welcome-content {
    display: flex;
    align-items: center;
    gap: var(--sp-spacing-md);
}

.sp-welcome-avatar img {
    border-radius: var(--sp-radius-full);
    border: 3px solid rgba(255, 255, 255, 0.3);
}

.sp-welcome-text h2 {
    font-size: var(--sp-font-size-lg);
    font-weight: 600;
    margin-bottom: var(--sp-spacing-xs);
}

.sp-welcome-text p {
    font-size: var(--sp-font-size-sm);
    opacity: 0.9;
}

.sp-section {
    margin-bottom: var(--sp-spacing-xl);
}

.sp-section-title {
    font-size: var(--sp-font-size-base);
    font-weight: 600;
    color: var(--sp-text-secondary);
    margin-bottom: var(--sp-spacing-md);
}

.sp-quick-actions {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--sp-spacing-md);
}

.sp-action-card {
    background: var(--sp-bg-card);
    border-radius: var(--sp-radius-lg);
    padding: var(--sp-spacing-lg);
    text-align: center;
    text-decoration: none;
    color: var(--sp-text-primary);
    box-shadow: var(--sp-shadow-sm);
    transition: var(--sp-transition);
}

.sp-action-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--sp-shadow-md);
}

.sp-action-icon {
    width: 56px;
    height: 56px;
    border-radius: var(--sp-radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto var(--sp-spacing-sm);
    color: var(--sp-text-white);
}

.sp-action-card span {
    font-size: var(--sp-font-size-sm);
    font-weight: 500;
}

.sp-empty-state-small {
    text-align: center;
    padding: var(--sp-spacing-xl);
}

.sp-empty-state-small p {
    color: var(--sp-text-light);
    margin-top: var(--sp-spacing-sm);
    font-size: var(--sp-font-size-sm);
}

.sp-welcome-points {
    text-align: center;
    background: rgba(255,255,255,0.2);
    padding: var(--sp-spacing-sm) var(--sp-spacing-md);
    border-radius: var(--sp-radius-sm);
}

.sp-welcome-points-value {
    display: block;
    font-size: var(--sp-font-size-xl);
    font-weight: 700;
}

.sp-welcome-points-label {
    font-size: var(--sp-font-size-xs);
    opacity: 0.9;
}

.sp-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--sp-spacing-md);
}

.sp-section-link {
    font-size: var(--sp-font-size-sm);
    color: var(--sp-primary);
    text-decoration: none;
}

.sp-events-mini {
    display: flex;
    flex-direction: column;
    gap: var(--sp-spacing-sm);
}

.sp-event-mini {
    display: flex;
    align-items: center;
    gap: var(--sp-spacing-md);
    background: var(--sp-bg-card);
    padding: var(--sp-spacing-md);
    border-radius: var(--sp-radius-md);
    text-decoration: none;
    color: inherit;
    box-shadow: var(--sp-shadow-sm);
}

.sp-event-mini-icon {
    font-size: 28px;
}

.sp-event-mini-content {
    flex: 1;
    min-width: 0;
}

.sp-event-mini-title {
    display: block;
    font-weight: 500;
    color: var(--sp-text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sp-event-mini-date {
    display: block;
    font-size: var(--sp-font-size-xs);
    color: var(--sp-text-secondary);
}

.sp-event-mini-points {
    font-weight: 700;
    color: var(--sp-secondary-dark);
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#sp-logout-btn').on('click', function() {
        $.ajax({
            url: spApp.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sp_logout_user',
                nonce: spApp.nonce
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.redirect || spApp.appUrl + '/login';
                }
            }
        });
    });
});
</script>
