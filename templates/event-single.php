<?php
/**
 * Saint Porphyrius - Single Event Template
 * Shows event details with map
 */

if (!defined('ABSPATH')) {
    exit;
}

$event_id = get_query_var('sp_event_id');
$events_handler = SP_Events::get_instance();
$event = $events_handler->get($event_id);

if (!$event) {
    wp_safe_redirect(home_url('/app/events'));
    exit;
}

$event_date = strtotime($event->event_date);
$points_config = $events_handler->get_event_points($event);
$has_map_url = !empty($event->location_map_url);
?>

<div class="sp-header" style="--event-color: <?php echo esc_attr($event->type_color); ?>;">
    <a href="<?php echo home_url('/app/events'); ?>" class="sp-back-btn">
        <span class="dashicons dashicons-arrow-right-alt2"></span>
    </a>
    <h1 class="sp-header-title"><?php _e('تفاصيل الفعالية', 'saint-porphyrius'); ?></h1>
</div>

<div class="sp-content sp-event-single">
    <div class="sp-event-hero" style="background-color: <?php echo esc_attr($event->type_color); ?>20;">
        <span class="sp-event-hero-icon"><?php echo esc_html($event->type_icon); ?></span>
        <span class="sp-event-hero-type"><?php echo esc_html($event->type_name_ar); ?></span>
    </div>
    
    <div class="sp-event-details">
        <h1 class="sp-event-detail-title"><?php echo esc_html($event->title_ar); ?></h1>
        
        <?php if ($event->is_mandatory): ?>
            <div class="sp-event-mandatory-badge">
                <span class="dashicons dashicons-warning"></span>
                <?php _e('حضور إلزامي - عدم الحضور سيؤدي لخصم نقاط', 'saint-porphyrius'); ?>
            </div>
        <?php endif; ?>
        
        <div class="sp-event-info-grid">
            <div class="sp-info-item">
                <div class="sp-info-icon">📅</div>
                <div class="sp-info-content">
                    <span class="sp-info-label"><?php _e('التاريخ', 'saint-porphyrius'); ?></span>
                    <span class="sp-info-value"><?php echo esc_html(date_i18n('l، j F Y', $event_date)); ?></span>
                </div>
            </div>
            
            <div class="sp-info-item">
                <div class="sp-info-icon">⏰</div>
                <div class="sp-info-content">
                    <span class="sp-info-label"><?php _e('الوقت', 'saint-porphyrius'); ?></span>
                    <span class="sp-info-value">
                        <?php echo esc_html($event->start_time); ?>
                        <?php if ($event->end_time): ?>
                            - <?php echo esc_html($event->end_time); ?>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            
            <?php if ($event->location_name): ?>
            <div class="sp-info-item">
                <div class="sp-info-icon">📍</div>
                <div class="sp-info-content">
                    <span class="sp-info-label"><?php _e('المكان', 'saint-porphyrius'); ?></span>
                    <span class="sp-info-value"><?php echo esc_html($event->location_name); ?></span>
                    <?php if ($event->location_address): ?>
                        <span class="sp-info-address"><?php echo esc_html($event->location_address); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($event->description): ?>
            <div class="sp-event-description">
                <h3><?php _e('التفاصيل', 'saint-porphyrius'); ?></h3>
                <p><?php echo nl2br(esc_html($event->description)); ?></p>
            </div>
        <?php endif; ?>
        
        <!-- Points Info -->
        <div class="sp-event-points-info">
            <h3><?php _e('النقاط', 'saint-porphyrius'); ?></h3>
            <div class="sp-points-grid">
                <div class="sp-points-item sp-points-reward">
                    <span class="sp-points-icon">✓</span>
                    <span class="sp-points-value">+<?php echo esc_html($points_config['attendance']); ?></span>
                    <span class="sp-points-label"><?php _e('نقطة عند الحضور', 'saint-porphyrius'); ?></span>
                </div>
                <?php if ($event->is_mandatory && $points_config['penalty'] > 0): ?>
                    <div class="sp-points-item sp-points-penalty">
                        <span class="sp-points-icon">✗</span>
                        <span class="sp-points-value">-<?php echo esc_html($points_config['penalty']); ?></span>
                        <span class="sp-points-label"><?php _e('نقطة عند الغياب', 'saint-porphyrius'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($has_map_url): ?>
            <div class="sp-event-map">
                <a href="<?php echo esc_url($event->location_map_url); ?>" 
                   target="_blank" 
                   class="sp-btn sp-btn-primary sp-btn-block sp-btn-map">
                    <span class="dashicons dashicons-location-alt"></span>
                    <?php _e('عرض الموقع على الخريطة', 'saint-porphyrius'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Bottom Navigation -->
<nav class="sp-bottom-nav">
    <a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-nav-item">
        <span class="dashicons dashicons-dashboard"></span>
        <span><?php _e('الرئيسية', 'saint-porphyrius'); ?></span>
    </a>
    <a href="<?php echo home_url('/app/events'); ?>" class="sp-nav-item active">
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
