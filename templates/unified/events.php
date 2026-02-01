<?php
/**
 * Saint Porphyrius - Events Template (Unified Design)
 * Shows upcoming events with modern design
 */

if (!defined('ABSPATH')) {
    exit;
}

$events_handler = SP_Events::get_instance();
$forbidden_handler = SP_Forbidden::get_instance();
$upcoming_events = $events_handler->get_upcoming(20);
$user_id = get_current_user_id();

// Get user's forbidden status
$user_forbidden_status = $forbidden_handler->get_user_status($user_id);
$is_user_forbidden = $user_forbidden_status->forbidden_remaining > 0;
?>

<!-- Unified Header -->
<div class="sp-unified-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('الفعاليات القادمة', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-spacer"></div>
    </div>
</div>

<!-- Main Content -->
<main class="sp-page-content has-bottom-nav">
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
                
                // Check if user is forbidden from this event
                $is_forbidden_event = !empty($event->forbidden_enabled) && $is_user_forbidden;
            ?>
                <a href="<?php echo home_url('/app/events/' . $event->id); ?>" class="sp-event-card <?php echo $is_forbidden_event ? 'is-forbidden' : ''; ?>" style="--event-color: <?php echo esc_attr($event->type_color); ?>;">
                    <?php if ($is_forbidden_event): ?>
                    <div class="sp-event-forbidden-overlay">
                        <span class="sp-forbidden-icon">⛔</span>
                        <span><?php _e('محروم', 'saint-porphyrius'); ?></span>
                    </div>
                    <?php endif; ?>
                    
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
                            <span class="sp-event-time">
                                <span class="dashicons dashicons-clock"></span>
                                <?php echo esc_html($event->start_time); ?>
                                <?php if ($event->end_time): ?>
                                    - <?php echo esc_html($event->end_time); ?>
                                <?php endif; ?>
                            </span>
                            
                            <?php if ($event->location_name): ?>
                                <span class="sp-event-location">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php echo esc_html($event->location_name); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="sp-event-points">
                        <span class="sp-points-value">+<?php echo esc_html($points_config['attendance']); ?></span>
                        <span class="sp-points-label"><?php _e('نقطة', 'saint-porphyrius'); ?></span>
                        <?php if ($event->is_mandatory): ?>
                            <span class="sp-event-mandatory"><?php _e('إلزامي', 'saint-porphyrius'); ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<!-- Unified Bottom Navigation -->
<nav class="sp-unified-nav">
    <div class="sp-nav-inner">
        <a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-nav-item">
            <div class="sp-nav-icon">
                <span class="dashicons dashicons-dashboard"></span>
            </div>
            <span class="sp-nav-label"><?php _e('الرئيسية', 'saint-porphyrius'); ?></span>
        </a>
        <a href="<?php echo home_url('/app/events'); ?>" class="sp-nav-item active">
            <div class="sp-nav-indicator"></div>
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
