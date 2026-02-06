<?php
/**
 * Saint Porphyrius - Points Template (Unified Design)
 * Shows user's points history and balance with modern design
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$points_handler = SP_Points::get_instance();
$attendance_handler = SP_Attendance::get_instance();
$excuses_handler = SP_Excuses::get_instance();

$balance = $points_handler->get_balance($user_id);
$history = $points_handler->get_history($user_id, array('limit' => 30));
$stats = $attendance_handler->get_user_stats($user_id);
$reason_types = SP_Points::get_reason_types();
$user_excuses = $excuses_handler->get_user_excuses($user_id, 10);
?>

<!-- Unified Header -->
<div class="sp-unified-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('نقاطي', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-spacer"></div>
    </div>
</div>

<!-- Main Content -->
<main class="sp-page-content has-bottom-nav">
    <!-- Points Hero Card -->
    <div class="sp-hero-card" style="background: linear-gradient(135deg, #F2D388 0%, #E5C470 100%);">
        <div class="sp-hero-content" style="flex-direction: column; align-items: center; text-align: center;">
            <div style="font-size: 48px; margin-bottom: 8px;">⭐</div>
            <div style="font-size: 48px; font-weight: 700; line-height: 1;"><?php echo esc_html($balance); ?></div>
            <div style="font-size: 16px; opacity: 0.9; margin-top: 4px;"><?php _e('نقطة', 'saint-porphyrius'); ?></div>
        </div>
    </div>

    <!-- Share Points CTA -->
    <div style="padding: 0 var(--sp-space-lg) var(--sp-space-md);">
        <a href="<?php echo home_url('/app/share-points'); ?>" class="sp-share-cta-card" style="display: flex; align-items: center; gap: var(--sp-space-md); background: linear-gradient(135deg, rgba(244, 114, 182, 0.12) 0%, rgba(236, 72, 153, 0.06) 100%); border: 2px solid #F9A8D4; border-radius: var(--sp-radius-lg); padding: var(--sp-space-md) var(--sp-space-lg); text-decoration: none; color: inherit; transition: all 0.2s;">
            <span style="font-size: 28px;">🎁</span>
            <div style="flex: 1;">
                <h4 style="margin: 0; font-weight: 600; color: #BE185D; font-size: var(--sp-font-size-base);"><?php _e('مشاركة النقاط', 'saint-porphyrius'); ?></h4>
                <p style="margin: 2px 0 0; font-size: var(--sp-font-size-xs); color: var(--sp-text-secondary);"><?php _e('أهدِ نقاطك لأعضاء الأسرة بمحبة', 'saint-porphyrius'); ?></p>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#BE185D" stroke-width="2" style="transform: scaleX(-1);">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
    </div>

    <!-- Quick Stats -->
    <div class="sp-stats-row">
        <div class="sp-stat-card">
            <div class="sp-stat-value"><?php echo esc_html($stats->attended ?? 0); ?></div>
            <div class="sp-stat-label"><?php _e('حضور', 'saint-porphyrius'); ?></div>
        </div>
        <div class="sp-stat-card">
            <div class="sp-stat-value"><?php echo esc_html($stats->absent ?? 0); ?></div>
            <div class="sp-stat-label"><?php _e('غياب', 'saint-porphyrius'); ?></div>
        </div>
        <div class="sp-stat-card">
            <div class="sp-stat-value"><?php echo esc_html($stats->attendance_rate ?? 0); ?>%</div>
            <div class="sp-stat-label"><?php _e('معدل', 'saint-porphyrius'); ?></div>
        </div>
    </div>

    <!-- My Excuses Section -->
    <?php if (!empty($user_excuses)): ?>
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title"><?php _e('اعتذاراتي', 'saint-porphyrius'); ?></h3>
        </div>
        
        <div class="sp-list">
            <?php foreach ($user_excuses as $excuse): 
                $status_color = SP_Excuses::get_status_color($excuse->status);
                $status_label = SP_Excuses::get_status_label($excuse->status);
            ?>
                <div class="sp-excuse-card">
                    <div class="sp-excuse-top">
                        <div class="sp-excuse-icon" style="background: <?php echo esc_attr($excuse->event_color ?? '#6B7280'); ?>15; color: <?php echo esc_attr($excuse->event_color ?? '#6B7280'); ?>;">
                            <?php echo esc_html($excuse->event_icon ?? '📅'); ?>
                        </div>
                        <div class="sp-excuse-header">
                            <h4 class="sp-excuse-title"><?php echo esc_html($excuse->event_title); ?></h4>
                            <p class="sp-excuse-date"><?php echo esc_html(date_i18n('j F Y', strtotime($excuse->event_date))); ?></p>
                        </div>
                        <span class="sp-badge" style="background: <?php echo esc_attr($status_color); ?>20; color: <?php echo esc_attr($status_color); ?>;">
                            <?php echo esc_html($status_label); ?>
                        </span>
                    </div>
                    <div class="sp-excuse-points">
                        <span style="color: var(--sp-text-secondary); font-size: 12px;"><?php _e('النقاط المخصومة:', 'saint-porphyrius'); ?></span>
                        <span style="color: var(--sp-error); font-weight: 600; font-size: 14px;">-<?php echo esc_html($excuse->points_deducted); ?> <?php _e('نقطة', 'saint-porphyrius'); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Points History Section -->
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title"><?php _e('سجل النقاط', 'saint-porphyrius'); ?></h3>
        </div>
        
        <?php if (empty($history)): ?>
            <div class="sp-card">
                <div class="sp-empty">
                    <div class="sp-empty-icon">📊</div>
                    <h4 class="sp-empty-title"><?php _e('لا يوجد سجل نقاط بعد', 'saint-porphyrius'); ?></h4>
                    <p class="sp-empty-text"><?php _e('احضر الفعاليات لكسب النقاط', 'saint-porphyrius'); ?></p>
                </div>
            </div>
        <?php else: ?>
            <div class="sp-history-list">
                <?php foreach ($history as $entry): 
                    $is_positive = $entry->points >= 0;
                    // Handle null/empty type - infer from points value
                    $entry_type = !empty($entry->type) ? $entry->type : ($is_positive ? 'reward' : 'penalty');
                    $type_info = isset($reason_types[$entry_type]) ? $reason_types[$entry_type] : null;
                    $type_label = $type_info ? $type_info['label_ar'] : ucfirst($entry_type ?: 'غير معروف');
                    $type_color = $type_info && isset($type_info['color']) ? $type_info['color'] : '#6B7280';
                ?>
                    <div class="sp-history-item">
                        <div class="sp-history-icon <?php echo $is_positive ? 'positive' : 'negative'; ?>">
                            <?php echo $is_positive ? '+' : '-'; ?>
                        </div>
                        <div class="sp-history-content">
                            <span class="sp-history-type" style="background: <?php echo esc_attr($type_color); ?>15; color: <?php echo esc_attr($type_color); ?>; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 500; display: inline-block; margin-bottom: 4px;">
                                <?php echo esc_html($type_label); ?>
                            </span>
                            <?php if (!empty($entry->reason)): ?>
                                <span class="sp-history-desc"><?php echo esc_html($entry->reason); ?></span>
                            <?php endif; ?>
                            <span class="sp-history-date"><?php echo esc_html(date_i18n('d-m-Y - H:i', strtotime($entry->created_at))); ?></span>
                        </div>
                        <div class="sp-history-points <?php echo $is_positive ? 'positive' : 'negative'; ?>">
                            <?php echo $is_positive ? '+' : ''; ?><?php echo esc_html($entry->points); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
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
        <a href="<?php echo home_url('/app/events'); ?>" class="sp-nav-item">
            <div class="sp-nav-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <span class="sp-nav-label"><?php _e('الفعاليات', 'saint-porphyrius'); ?></span>
        </a>
        <a href="<?php echo home_url('/app/points'); ?>" class="sp-nav-item active">
            <div class="sp-nav-indicator"></div>
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
