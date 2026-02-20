<?php
/**
 * Saint Porphyrius - Leaderboard Template (Unified Design)
 * Shows points leaderboard with modern design
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$points_handler = SP_Points::get_instance();
$social_profiles_enabled = SP_Social_Profile::get_instance()->is_enabled();

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

<!-- Unified Header -->
<div class="sp-unified-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('لوحة المتصدرين', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-spacer"></div>
    </div>
</div>

<!-- Main Content -->
<main class="sp-page-content has-bottom-nav">
    <!-- User's Rank Hero Card -->
    <div class="sp-hero-card" style="background: linear-gradient(135deg, #6C9BCF 0%, #5A89BD 100%);">
        <div class="sp-hero-content">
            <div class="sp-hero-text">
                <h2><?php _e('ترتيبك', 'saint-porphyrius'); ?></h2>
                <p style="font-size: 40px; font-weight: 700; margin: 8px 0 0;">#<?php echo esc_html($user_rank ?: '-'); ?></p>
            </div>
            <div class="sp-hero-stat">
                <span class="sp-hero-stat-value"><?php echo esc_html($user_balance); ?></span>
                <span class="sp-hero-stat-label"><?php _e('نقطة', 'saint-porphyrius'); ?></span>
            </div>
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
            <div class="sp-card">
                <div class="sp-empty">
                    <div class="sp-empty-icon">🏆</div>
                    <h4 class="sp-empty-title"><?php _e('لا يوجد متصدرين بعد', 'saint-porphyrius'); ?></h4>
                    <p class="sp-empty-text"><?php _e('كن أول من يكسب النقاط', 'saint-porphyrius'); ?></p>
                </div>
            </div>
        <?php else: ?>
            <div class="sp-leaderboard-list">
                <?php foreach ($leaderboard_all as $index => $entry): 
                    $rank = $index + 1;
                    $is_current_user = $entry->user_id == $user_id;
                    $medal = '';
                    if ($rank === 1) $medal = '🥇';
                    elseif ($rank === 2) $medal = '🥈';
                    elseif ($rank === 3) $medal = '🥉';
                ?>
                    <?php $lb_tag = $social_profiles_enabled ? 'a' : 'div'; $lb_href = $social_profiles_enabled ? ' href="' . home_url('/app/member/?id=' . $entry->user_id) . '"' : ''; ?>
                    <<?php echo $lb_tag; ?><?php echo $lb_href; ?> class="sp-leaderboard-item <?php echo $is_current_user ? 'is-current' : ''; ?> <?php echo $rank <= 3 ? 'is-top' : ''; ?><?php echo $social_profiles_enabled ? ' sp-lb-clickable' : ''; ?>" style="text-decoration:none;color:inherit;">
                        <div class="sp-lb-rank">
                            <?php if ($medal): ?>
                                <span class="medal"><?php echo $medal; ?></span>
                            <?php else: ?>
                                <span class="number"><?php echo esc_html($rank); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="sp-lb-user">
                            <span class="sp-lb-name">
                                <?php 
                                    $user = get_userdata($entry->user_id);
                                    $first_name = $user->first_name;
                                    $middle_name = get_user_meta($entry->user_id, 'sp_middle_name', true);
                                    $display_name_format = trim($first_name . ' ' . $middle_name);
                                    echo esc_html($display_name_format ?: $entry->display_name);
                                ?>
                                <?php if ($is_current_user): ?>
                                    <span style="color: var(--sp-primary); font-size: 12px;"><?php _e('(أنت)', 'saint-porphyrius'); ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="sp-lb-points">
                            <?php echo esc_html($entry->total_points); ?>
                        </div>
                        <?php if ($social_profiles_enabled): ?>
                        <div class="sp-lb-arrow">›</div>
                        <?php endif; ?>
                    </<?php echo $lb_tag; ?>>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Monthly Leaderboard -->
    <div class="sp-tab-content" id="tab-month" style="display: none;">
        <?php if (empty($leaderboard_month)): ?>
            <div class="sp-card">
                <div class="sp-empty">
                    <div class="sp-empty-icon">📅</div>
                    <h4 class="sp-empty-title"><?php _e('لا يوجد نقاط هذا الشهر', 'saint-porphyrius'); ?></h4>
                    <p class="sp-empty-text"><?php _e('احضر الفعاليات لكسب النقاط', 'saint-porphyrius'); ?></p>
                </div>
            </div>
        <?php else: ?>
            <div class="sp-leaderboard-list">
                <?php foreach ($leaderboard_month as $index => $entry): 
                    $rank = $index + 1;
                    $is_current_user = $entry->user_id == $user_id;
                    $medal = '';
                    if ($rank === 1) $medal = '🥇';
                    elseif ($rank === 2) $medal = '🥈';
                    elseif ($rank === 3) $medal = '🥉';
                ?>
                    <?php $lb_tag2 = $social_profiles_enabled ? 'a' : 'div'; $lb_href2 = $social_profiles_enabled ? ' href="' . home_url('/app/member/?id=' . $entry->user_id) . '"' : ''; ?>
                    <<?php echo $lb_tag2; ?><?php echo $lb_href2; ?> class="sp-leaderboard-item <?php echo $is_current_user ? 'is-current' : ''; ?> <?php echo $rank <= 3 ? 'is-top' : ''; ?><?php echo $social_profiles_enabled ? ' sp-lb-clickable' : ''; ?>" style="text-decoration:none;color:inherit;">
                        <div class="sp-lb-rank">
                            <?php if ($medal): ?>
                                <span class="medal"><?php echo $medal; ?></span>
                            <?php else: ?>
                                <span class="number"><?php echo esc_html($rank); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="sp-lb-user">
                            <span class="sp-lb-name">
                                <?php 
                                    $user = get_userdata($entry->user_id);
                                    $first_name = $user->first_name;
                                    $middle_name = get_user_meta($entry->user_id, 'sp_middle_name', true);
                                    $display_name_format = trim($first_name . ' ' . $middle_name);
                                    echo esc_html($display_name_format ?: $entry->display_name);
                                ?>
                                <?php if ($is_current_user): ?>
                                    <span style="color: var(--sp-primary); font-size: 12px;"><?php _e('(أنت)', 'saint-porphyrius'); ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="sp-lb-points">
                            <?php echo esc_html($entry->total_points); ?>
                        </div>
                        <?php if ($social_profiles_enabled): ?>
                        <div class="sp-lb-arrow">›</div>
                        <?php endif; ?>
                    </<?php echo $lb_tag2; ?>>
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
        <a href="<?php echo home_url('/app/points'); ?>" class="sp-nav-item">
            <div class="sp-nav-icon">
                <span class="dashicons dashicons-star-filled"></span>
            </div>
            <span class="sp-nav-label"><?php _e('نقاطي', 'saint-porphyrius'); ?></span>
        </a>
        <a href="<?php echo home_url('/app/leaderboard'); ?>" class="sp-nav-item active">
            <div class="sp-nav-indicator"></div>
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

<script>
document.querySelectorAll('.sp-tabs .sp-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        // Remove active from all tabs
        document.querySelectorAll('.sp-tabs .sp-tab').forEach(function(t) {
            t.classList.remove('active');
        });
        // Hide all tab contents
        document.querySelectorAll('.sp-tab-content').forEach(function(c) {
            c.style.display = 'none';
            c.classList.remove('active');
        });
        // Activate clicked tab
        this.classList.add('active');
        var tabId = 'tab-' + this.getAttribute('data-tab');
        var tabContent = document.getElementById(tabId);
        if (tabContent) {
            tabContent.style.display = 'block';
            tabContent.classList.add('active');
        }
    });
});
</script>
