<?php
/**
 * Saint Porphyrius - Events Template (Unified Design)
 * Shows events in 3 sections: Main (forbidden), Upcoming, Past
 */

if (!defined('ABSPATH')) {
    exit;
}

$events_handler = SP_Events::get_instance();
$forbidden_handler = SP_Forbidden::get_instance();
$expected_handler = SP_Expected_Attendance::get_instance();
$gamification = SP_Gamification::get_instance();
$user_id = get_current_user_id();

// Get user's forbidden status
$user_forbidden_status = $forbidden_handler->get_user_status($user_id);
$is_user_forbidden = $user_forbidden_status->forbidden_remaining > 0;

// Check if user has completed service instructions
$instructions_completed = $gamification->has_completed_service_instructions($user_id);

// Check if user is admin
$is_admin = current_user_can('manage_options');

// Get all published events
$all_events = $events_handler->get_all(array(
    'status' => 'published',
    'limit' => 50,
    'orderby' => 'event_date',
    'order' => 'ASC',
));

// For admins, also get draft events for testing
if ($is_admin) {
    $draft_events = $events_handler->get_all(array(
        'status' => 'draft',
        'limit' => 50,
        'orderby' => 'event_date',
        'order' => 'ASC',
    ));
    $all_events = array_merge($all_events, $draft_events);
    // Sort by date
    usort($all_events, function($a, $b) {
        return strtotime($a->event_date) - strtotime($b->event_date);
    });
}

// Get past events (completed)
$past_events = $events_handler->get_all(array(
    'status' => 'completed',
    'limit' => 20,
    'orderby' => 'event_date',
    'order' => 'DESC',
));

// Separate events into categories
$main_events = array(); // Forbidden system events (upcoming)
$upcoming_events = array(); // Regular upcoming events
$today = date('Y-m-d');

foreach ($all_events as $event) {
    if ($event->event_date >= $today) {
        if (!empty($event->forbidden_enabled)) {
            $main_events[] = $event;
        } else {
            $upcoming_events[] = $event;
        }
    }
}

// Helper function to render event card
function render_event_card($event, $events_handler, $expected_handler, $is_user_forbidden, $show_forbidden_badge = false, $is_draft = false) {
    $event_date = strtotime($event->event_date);
    $is_today = date('Y-m-d') === $event->event_date;
    $is_tomorrow = date('Y-m-d', strtotime('+1 day')) === $event->event_date;
    $points_config = $events_handler->get_event_points($event);
    
    // Check if user is forbidden from this event
    $is_forbidden_event = !empty($event->forbidden_enabled) && $is_user_forbidden;
    
    // Get expected attendance count
    $expected_count = 0;
    $expected_attendance_enabled = isset($event->expected_attendance_enabled) ? $event->expected_attendance_enabled : true;
    if ($expected_attendance_enabled) {
        $expected_count = $expected_handler->get_count($event->id);
    }
    ?>
    <a href="<?php echo home_url('/app/events/' . $event->id); ?>" class="sp-event-card <?php echo $is_forbidden_event ? 'is-forbidden' : ''; ?> <?php echo $is_draft ? 'is-draft' : ''; ?>" style="--event-color: <?php echo esc_attr($event->type_color); ?>;">
        <?php if ($is_draft): ?>
        <div class="sp-event-draft-badge">
            <span>📝</span>
            <span><?php _e('مسودة', 'saint-porphyrius'); ?></span>
        </div>
        <?php endif; ?>
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
                <?php if ($show_forbidden_badge && !empty($event->forbidden_enabled)): ?>
                    <span class="sp-badge sp-badge-danger" style="margin-right: 8px; font-size: 10px;">⛔</span>
                <?php endif; ?>
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
            <?php if ($expected_attendance_enabled && $expected_count > 0): ?>
                <span class="sp-event-expected-count" title="<?php _e('عدد المسجلين للحضور', 'saint-porphyrius'); ?>">
                    🙋 <?php echo $expected_count; ?>
                </span>
            <?php endif; ?>
        </div>
    </a>
    <?php
}
?>

<!-- Unified Header -->
<div class="sp-unified-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('الفعاليات', 'saint-porphyrius'); ?></h1>
        <a href="<?php echo home_url('/app/service-instructions'); ?>" class="sp-header-action" title="<?php _e('تعليمات الخدمة والنظام', 'saint-porphyrius'); ?>" style="position: relative;">
            <?php if (!$instructions_completed): ?>
                <span style="position: absolute; top: -2px; right: -2px; width: 10px; height: 10px; background: var(--sp-error); border-radius: 50%; border: 2px solid white;"></span>
            <?php endif; ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
        </a>
    </div>
</div>

<!-- Main Content -->
<main class="sp-page-content has-bottom-nav">
    <?php if (empty($main_events) && empty($upcoming_events) && empty($past_events)): ?>
        <div class="sp-card">
            <div class="sp-empty">
                <div class="sp-empty-icon">📅</div>
                <h4 class="sp-empty-title"><?php _e('لا توجد فعاليات', 'saint-porphyrius'); ?></h4>
                <p class="sp-empty-text"><?php _e('سيتم إضافة فعاليات جديدة قريباً', 'saint-porphyrius'); ?></p>
            </div>
        </div>
    <?php else: ?>
        
        <!-- Instructions Banner (if not completed) -->
        <?php if (!$instructions_completed): ?>
        <a href="<?php echo home_url('/app/service-instructions'); ?>" class="sp-card sp-instructions-banner" style="background: linear-gradient(135deg, var(--sp-primary) 0%, var(--sp-primary-dark) 100%); color: white; text-decoration: none; display: flex; align-items: center; gap: 16px; padding: 16px;">
            <div style="font-size: 36px;">📋</div>
            <div style="flex: 1;">
                <h4 style="margin: 0 0 4px; font-size: var(--sp-font-size-base);"><?php _e('تعليمات الخدمة والنظام', 'saint-porphyrius'); ?></h4>
                <p style="margin: 0; font-size: var(--sp-font-size-sm); opacity: 0.9;"><?php _e('اقرأ التعليمات واحصل على 10 نقاط!', 'saint-porphyrius'); ?></p>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 6 15 12 9 18"></polyline>
            </svg>
        </a>
        <?php endif; ?>
        
        <!-- Section 1: Main Events (Forbidden System) -->
        <?php if (!empty($main_events)): ?>
        <div class="sp-section">
            <div class="sp-section-header">
                <h3 class="sp-section-title">
                    <span style="margin-left: 8px;">⛔</span>
                    <?php _e('الفعاليات الرئيسية', 'saint-porphyrius'); ?>
                </h3>
                <span class="sp-badge sp-badge-danger" style="font-size: 10px;"><?php _e('نظام المحروم', 'saint-porphyrius'); ?></span>
            </div>
            
            <?php if ($is_user_forbidden): ?>
            <div class="sp-alert sp-alert-warning" style="margin-bottom: var(--sp-space-md);">
                <div class="sp-alert-icon">⚠️</div>
                <div class="sp-alert-content">
                    <strong><?php _e('أنت محروم حالياً', 'saint-porphyrius'); ?></strong>
                    <?php printf(__('متبقي %d فعاليات للرجوع', 'saint-porphyrius'), $user_forbidden_status->forbidden_remaining); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="sp-events-list">
                <?php foreach ($main_events as $event): ?>
                    <?php render_event_card($event, $events_handler, $expected_handler, $is_user_forbidden, true, $event->status === 'draft'); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Section 2: Upcoming Events -->
        <?php if (!empty($upcoming_events)): ?>
        <div class="sp-section">
            <div class="sp-section-header">
                <h3 class="sp-section-title">
                    <span style="margin-left: 8px;">📅</span>
                    <?php _e('الفعاليات القادمة', 'saint-porphyrius'); ?>
                </h3>
                <span class="sp-badge"><?php echo count($upcoming_events); ?></span>
            </div>
            <div class="sp-events-list">
                <?php foreach ($upcoming_events as $event): ?>
                    <?php render_event_card($event, $events_handler, $expected_handler, $is_user_forbidden, false, $event->status === 'draft'); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Section 3: Past Events -->
        <?php if (!empty($past_events)): ?>
        <div class="sp-section">
            <div class="sp-section-header">
                <h3 class="sp-section-title">
                    <span style="margin-left: 8px;">📜</span>
                    <?php _e('الفعاليات السابقة', 'saint-porphyrius'); ?>
                </h3>
                <span class="sp-badge sp-badge-secondary"><?php echo count($past_events); ?></span>
            </div>
            <div class="sp-events-list sp-events-past">
                <?php foreach ($past_events as $event): 
                    $event_date = strtotime($event->event_date);
                    $points_config = $events_handler->get_event_points($event);
                ?>
                    <a href="<?php echo home_url('/app/events/' . $event->id); ?>" class="sp-event-card sp-event-past" style="--event-color: <?php echo esc_attr($event->type_color); ?>; opacity: 0.7;">
                        <div class="sp-event-date-badge" style="background: var(--sp-gray-100); color: var(--sp-text-secondary);">
                            <span class="sp-event-date-day"><?php echo esc_html(date_i18n('j', $event_date)); ?></span>
                            <span class="sp-event-date-month"><?php echo esc_html(date_i18n('M', $event_date)); ?></span>
                        </div>
                        
                        <div class="sp-event-info">
                            <div class="sp-event-type" style="opacity: 0.7;">
                                <span class="sp-event-type-icon"><?php echo esc_html($event->type_icon); ?></span>
                                <span><?php echo esc_html($event->type_name_ar); ?></span>
                            </div>
                            
                            <h3 class="sp-event-title" style="color: var(--sp-text-secondary);"><?php echo esc_html($event->title_ar); ?></h3>
                            
                            <div class="sp-event-meta">
                                <span class="sp-event-time">
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php echo esc_html($event->start_time); ?>
                                </span>
                                <span class="sp-badge sp-badge-success" style="font-size: 10px;">
                                    ✓ <?php _e('مكتمل', 'saint-porphyrius'); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="sp-event-points" style="opacity: 0.6;">
                            <span class="sp-points-value">+<?php echo esc_html($points_config['attendance']); ?></span>
                            <span class="sp-points-label"><?php _e('نقطة', 'saint-porphyrius'); ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
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
