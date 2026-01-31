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

$balance = $points_handler->get_balance($user_id);
$history = $points_handler->get_history($user_id, array('limit' => 30));
$stats = $attendance_handler->get_user_stats($user_id);
$reason_types = SP_Points::get_reason_types();
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
                    $reason_label = isset($reason_types[$entry->type]) ? $reason_types[$entry->type]['label_ar'] : $entry->type;
                ?>
                    <div class="sp-history-item">
                        <div class="sp-history-icon <?php echo $is_positive ? 'positive' : 'negative'; ?>">
                            <?php echo $is_positive ? '+' : '-'; ?>
                        </div>
                        <div class="sp-history-content">
                            <span class="sp-history-reason"><?php echo esc_html($reason_label); ?></span>
                            <?php if (!empty($entry->reason)): ?>
                                <span class="sp-history-desc"><?php echo esc_html($entry->reason); ?></span>
                            <?php endif; ?>
                            <span class="sp-history-date"><?php echo esc_html(date_i18n('j M Y - g:i a', strtotime($entry->created_at))); ?></span>
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
