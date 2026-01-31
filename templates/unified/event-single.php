<?php
/**
 * Saint Porphyrius - Single Event Template (Unified Design)
 * Shows event details with modern design
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

<!-- Unified Header with Event Color -->
<div class="sp-unified-header sp-header-colored" style="--header-color: <?php echo esc_attr($event->type_color); ?>;">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/events'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('تفاصيل الفعالية', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-spacer"></div>
    </div>
</div>

<!-- Main Content -->
<main class="sp-page-content has-bottom-nav">
    <!-- Event Hero Section -->
    <div class="sp-card" style="background: <?php echo esc_attr($event->type_color); ?>15; border: none; text-align: center; padding: var(--sp-space-xl);">
        <div style="font-size: 56px; margin-bottom: 12px;"><?php echo esc_html($event->type_icon); ?></div>
        <span class="sp-badge" style="background: <?php echo esc_attr($event->type_color); ?>25; color: <?php echo esc_attr($event->type_color); ?>;">
            <?php echo esc_html($event->type_name_ar); ?>
        </span>
        <h1 style="font-size: var(--sp-font-size-xl); font-weight: 700; margin: 16px 0 8px; color: var(--sp-text-primary);">
            <?php echo esc_html($event->title_ar); ?>
        </h1>
        
        <?php if ($event->is_mandatory): ?>
            <div style="background: var(--sp-warning-light); color: #92400E; padding: 12px 16px; border-radius: var(--sp-radius-md); margin-top: 16px; font-size: var(--sp-font-size-sm);">
                <span class="dashicons dashicons-warning" style="margin-left: 8px;"></span>
                <?php _e('حضور إلزامي - عدم الحضور سيؤدي لخصم نقاط', 'saint-porphyrius'); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Event Info Cards -->
    <div class="sp-section">
        <div class="sp-list">
            <!-- Date -->
            <div class="sp-list-item">
                <div class="sp-list-icon" style="background: var(--sp-primary-50); color: var(--sp-primary);">
                    📅
                </div>
                <div class="sp-list-content">
                    <h4 class="sp-list-title"><?php _e('التاريخ', 'saint-porphyrius'); ?></h4>
                    <p class="sp-list-subtitle"><?php echo esc_html(date_i18n('l، j F Y', $event_date)); ?></p>
                </div>
            </div>
            
            <!-- Time -->
            <div class="sp-list-item">
                <div class="sp-list-icon" style="background: var(--sp-secondary); background: rgba(150, 194, 145, 0.15); color: var(--sp-secondary-dark);">
                    ⏰
                </div>
                <div class="sp-list-content">
                    <h4 class="sp-list-title"><?php _e('الوقت', 'saint-porphyrius'); ?></h4>
                    <p class="sp-list-subtitle">
                        <?php echo esc_html($event->start_time); ?>
                        <?php if ($event->end_time): ?>
                            - <?php echo esc_html($event->end_time); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            
            <!-- Location -->
            <?php if ($event->location_name): ?>
            <div class="sp-list-item">
                <div class="sp-list-icon" style="background: rgba(239, 68, 68, 0.1); color: var(--sp-error);">
                    📍
                </div>
                <div class="sp-list-content">
                    <h4 class="sp-list-title"><?php _e('المكان', 'saint-porphyrius'); ?></h4>
                    <p class="sp-list-subtitle"><?php echo esc_html($event->location_name); ?></p>
                    <?php if ($event->location_address): ?>
                        <p class="sp-list-meta"><?php echo esc_html($event->location_address); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Description Section -->
    <?php if ($event->description): ?>
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title"><?php _e('التفاصيل', 'saint-porphyrius'); ?></h3>
        </div>
        <div class="sp-card">
            <p style="margin: 0; line-height: 1.8; color: var(--sp-text-secondary);">
                <?php echo nl2br(esc_html($event->description)); ?>
            </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Points Section -->
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title"><?php _e('النقاط', 'saint-porphyrius'); ?></h3>
        </div>
        <div style="display: grid; grid-template-columns: <?php echo ($event->is_mandatory && $points_config['penalty'] > 0) ? '1fr 1fr' : '1fr'; ?>; gap: var(--sp-space-md);">
            <!-- Reward Points -->
            <div class="sp-card" style="text-align: center; background: var(--sp-success-light); border: none;">
                <div style="font-size: 32px; margin-bottom: 8px;">✓</div>
                <div style="font-size: var(--sp-font-size-2xl); font-weight: 700; color: var(--sp-success);">
                    +<?php echo esc_html($points_config['attendance']); ?>
                </div>
                <div style="font-size: var(--sp-font-size-xs); color: #065F46; margin-top: 4px;">
                    <?php _e('نقطة عند الحضور', 'saint-porphyrius'); ?>
                </div>
            </div>
            
            <!-- Penalty Points -->
            <?php if ($event->is_mandatory && $points_config['penalty'] > 0): ?>
            <div class="sp-card" style="text-align: center; background: var(--sp-error-light); border: none;">
                <div style="font-size: 32px; margin-bottom: 8px;">✗</div>
                <div style="font-size: var(--sp-font-size-2xl); font-weight: 700; color: var(--sp-error);">
                    -<?php echo esc_html($points_config['penalty']); ?>
                </div>
                <div style="font-size: var(--sp-font-size-xs); color: #991B1B; margin-top: 4px;">
                    <?php _e('نقطة عند الغياب', 'saint-porphyrius'); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Map Button -->
    <?php if ($has_map_url): ?>
    <div class="sp-section">
        <a href="<?php echo esc_url($event->location_map_url); ?>" 
           target="_blank" 
           class="sp-btn sp-btn-primary sp-btn-block sp-btn-lg">
            <span class="dashicons dashicons-location-alt" style="margin-left: 8px;"></span>
            <?php _e('عرض الموقع على الخريطة', 'saint-porphyrius'); ?>
        </a>
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
