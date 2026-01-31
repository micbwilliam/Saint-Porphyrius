<?php
/**
 * Saint Porphyrius - Leaderboard Template
 * Shows points leaderboard
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$points_handler = SP_Points::get_instance();

$leaderboard_all = $points_handler->get_leaderboard(50, 'all');
$leaderboard_month = $points_handler->get_leaderboard(10, 'month');
$user_balance = $points_handler->get_balance($user_id);

// Find current user's rank
$user_rank = 0;
foreach ($leaderboard_all as $index => $entry) {
    if ($entry->user_id == $user_id) {
        $user_rank = $index + 1;
        break;
    }
}
?>

<div class="sp-header">
    <a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-back-btn">
        <span class="dashicons dashicons-arrow-right-alt2"></span>
    </a>
    <h1 class="sp-header-title"><?php _e('لوحة المتصدرين', 'saint-porphyrius'); ?></h1>
</div>

<div class="sp-content">
    <!-- User's Rank Card -->
    <div class="sp-user-rank-card">
        <div class="sp-rank-info">
            <span class="sp-rank-position">#<?php echo $user_rank ?: '-'; ?></span>
            <span class="sp-rank-label"><?php _e('ترتيبك', 'saint-porphyrius'); ?></span>
        </div>
        <div class="sp-rank-points">
            <span class="sp-rank-points-value"><?php echo esc_html($user_balance); ?></span>
            <span class="sp-rank-points-label"><?php _e('نقطة', 'saint-porphyrius'); ?></span>
        </div>
    </div>
    
    <!-- Tabs -->
    <div class="sp-tabs">
        <button class="sp-tab active" data-tab="all"><?php _e('كل الوقت', 'saint-porphyrius'); ?></button>
        <button class="sp-tab" data-tab="month"><?php _e('هذا الشهر', 'saint-porphyrius'); ?></button>
    </div>
    
    <!-- All Time Leaderboard -->
    <div class="sp-tab-content active" id="tab-all">
        <?php if (empty($leaderboard_all)): ?>
            <div class="sp-empty-state sp-empty-small">
                <p><?php _e('لا يوجد متصدرين بعد', 'saint-porphyrius'); ?></p>
            </div>
        <?php else: ?>
            <div class="sp-leaderboard">
                <?php foreach ($leaderboard_all as $index => $entry): 
                    $rank = $index + 1;
                    $is_current_user = $entry->user_id == $user_id;
                    $medal = '';
                    if ($rank === 1) $medal = '🥇';
                    elseif ($rank === 2) $medal = '🥈';
                    elseif ($rank === 3) $medal = '🥉';
                ?>
                    <div class="sp-leaderboard-item <?php echo $is_current_user ? 'is-current' : ''; ?> <?php echo $rank <= 3 ? 'is-top' : ''; ?>">
                        <div class="sp-lb-rank">
                            <?php if ($medal): ?>
                                <span class="sp-lb-medal"><?php echo $medal; ?></span>
                            <?php else: ?>
                                <span class="sp-lb-number"><?php echo esc_html($rank); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="sp-lb-user">
                            <span class="sp-lb-name"><?php echo esc_html($entry->name_ar ?: $entry->display_name); ?></span>
                            <?php if ($is_current_user): ?>
                                <span class="sp-lb-you"><?php _e('(أنت)', 'saint-porphyrius'); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="sp-lb-points">
                            <?php echo esc_html($entry->total_points); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Monthly Leaderboard -->
    <div class="sp-tab-content" id="tab-month">
        <?php if (empty($leaderboard_month)): ?>
            <div class="sp-empty-state sp-empty-small">
                <p><?php _e('لا يوجد نقاط هذا الشهر', 'saint-porphyrius'); ?></p>
            </div>
        <?php else: ?>
            <div class="sp-leaderboard">
                <?php foreach ($leaderboard_month as $index => $entry): 
                    $rank = $index + 1;
                    $is_current_user = $entry->user_id == $user_id;
                    $medal = '';
                    if ($rank === 1) $medal = '🥇';
                    elseif ($rank === 2) $medal = '🥈';
                    elseif ($rank === 3) $medal = '🥉';
                ?>
                    <div class="sp-leaderboard-item <?php echo $is_current_user ? 'is-current' : ''; ?> <?php echo $rank <= 3 ? 'is-top' : ''; ?>">
                        <div class="sp-lb-rank">
                            <?php if ($medal): ?>
                                <span class="sp-lb-medal"><?php echo $medal; ?></span>
                            <?php else: ?>
                                <span class="sp-lb-number"><?php echo esc_html($rank); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="sp-lb-user">
                            <span class="sp-lb-name"><?php echo esc_html($entry->name_ar ?: $entry->display_name); ?></span>
                            <?php if ($is_current_user): ?>
                                <span class="sp-lb-you"><?php _e('(أنت)', 'saint-porphyrius'); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="sp-lb-points">
                            <?php echo esc_html($entry->total_points); ?>
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
    <a href="<?php echo home_url('/app/points'); ?>" class="sp-nav-item">
        <span class="dashicons dashicons-star-filled"></span>
        <span><?php _e('نقاطي', 'saint-porphyrius'); ?></span>
    </a>
    <a href="<?php echo home_url('/app/leaderboard'); ?>" class="sp-nav-item active">
        <span class="dashicons dashicons-awards"></span>
        <span><?php _e('المتصدرين', 'saint-porphyrius'); ?></span>
    </a>
    <a href="<?php echo home_url('/app/profile'); ?>" class="sp-nav-item">
        <span class="dashicons dashicons-admin-users"></span>
        <span><?php _e('حسابي', 'saint-porphyrius'); ?></span>
    </a>
</nav>

<script>
document.querySelectorAll('.sp-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.sp-tab').forEach(function(t) { t.classList.remove('active'); });
        document.querySelectorAll('.sp-tab-content').forEach(function(c) { c.classList.remove('active'); });
        
        this.classList.add('active');
        document.getElementById('tab-' + this.dataset.tab).classList.add('active');
    });
});
</script>
