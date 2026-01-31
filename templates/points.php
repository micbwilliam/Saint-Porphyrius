<?php
/**
 * Saint Porphyrius - My Points Template
 * Shows user's points history and balance
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

<div class="sp-header">
    <a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-back-btn">
        <span class="dashicons dashicons-arrow-right-alt2"></span>
    </a>
    <h1 class="sp-header-title"><?php _e('نقاطي', 'saint-porphyrius'); ?></h1>
</div>

<div class="sp-content">
    <!-- Points Balance Card -->
    <div class="sp-points-balance-card">
        <div class="sp-balance-icon">⭐</div>
        <div class="sp-balance-amount <?php echo $balance >= 0 ? 'positive' : 'negative'; ?>">
            <?php echo esc_html($balance); ?>
        </div>
        <div class="sp-balance-label"><?php _e('نقطة', 'saint-porphyrius'); ?></div>
    </div>
    
    <!-- Attendance Stats -->
    <div class="sp-stats-grid">
        <div class="sp-mini-stat">
            <span class="sp-mini-stat-value"><?php echo esc_html($stats->attended ?? 0); ?></span>
            <span class="sp-mini-stat-label"><?php _e('حضور', 'saint-porphyrius'); ?></span>
        </div>
        <div class="sp-mini-stat">
            <span class="sp-mini-stat-value"><?php echo esc_html($stats->absent ?? 0); ?></span>
            <span class="sp-mini-stat-label"><?php _e('غياب', 'saint-porphyrius'); ?></span>
        </div>
        <div class="sp-mini-stat">
            <span class="sp-mini-stat-value"><?php echo esc_html($stats->attendance_rate ?? 0); ?>%</span>
            <span class="sp-mini-stat-label"><?php _e('معدل الحضور', 'saint-porphyrius'); ?></span>
        </div>
    </div>
    
    <!-- Points History -->
    <div class="sp-section">
        <h2 class="sp-section-title"><?php _e('سجل النقاط', 'saint-porphyrius'); ?></h2>
        
        <?php if (empty($history)): ?>
            <div class="sp-empty-state sp-empty-small">
                <p><?php _e('لا يوجد سجل نقاط بعد', 'saint-porphyrius'); ?></p>
            </div>
        <?php else: ?>
            <div class="sp-points-history">
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
</div>

<!-- Bottom Navigation -->
<nav class="sp-bottom-nav">
    <a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-nav-item">
        <span class="dashicons dashicons-dashboard"></span>
        <span><?php _e('الرئيسية', 'saint-porphyrius'); ?></span>
    </a>
    <a href="<?php echo home_url('/app/events'); ?>" class="sp-nav-item">
        <span class="dashicons dashicons-calendar-alt"></span>
        <span><?php _e('الفعاليات', 'saint-porphyrius'); ?></span>
    </a>
    <a href="<?php echo home_url('/app/points'); ?>" class="sp-nav-item active">
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
