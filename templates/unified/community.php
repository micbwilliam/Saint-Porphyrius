<?php
/**
 * Saint Porphyrius - Community Page
 * View all members public info without contact details
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

// Get all members
$args = array(
    'role__in' => array('sp_member', 'sp_church_admin'),
    'orderby' => 'display_name',
    'order' => 'ASC',
    'number' => -1,
);

if ($search) {
    $args['search'] = '*' . $search . '*';
    $args['search_columns'] = array('user_login', 'display_name');
}

$members = get_users($args);

// Sort by points balance in PHP to ensure all members are shown
$points_handler_sort = SP_Points::get_instance();
usort($members, function($a, $b) use ($points_handler_sort) {
    $points_a = $points_handler_sort->get_balance($a->ID);
    $points_b = $points_handler_sort->get_balance($b->ID);
    return $points_b - $points_a; // DESC order
});
$points_handler = $points_handler_sort;
$forbidden_handler = SP_Forbidden::get_instance();
$attendance_handler = SP_Attendance::get_instance();
?>

<!-- Unified Header -->
<div class="sp-unified-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('أعضاء الأسرة', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-actions"></div>
    </div>
</div>

<!-- Main Content -->
<main class="sp-page-content has-bottom-nav">
    <!-- Search -->
    <form method="get" class="sp-search-form" style="padding: var(--sp-space-md);">
        <div class="sp-search-input-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" name="search" value="<?php echo esc_attr($search); ?>" 
                   placeholder="<?php _e('بحث عن عضو...', 'saint-porphyrius'); ?>" class="sp-search-input">
        </div>
    </form>

    <!-- Members Count -->
    <div class="sp-section" style="padding: 0 var(--sp-space-lg) var(--sp-space-md);">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <span style="font-size: var(--sp-font-size-sm); color: var(--sp-text-secondary);">
                <?php printf(__('%d عضو في الأسرة', 'saint-porphyrius'), count($members)); ?>
            </span>
            <span style="font-size: var(--sp-font-size-xs); color: var(--sp-text-muted);">
                👥 <?php _e('مرتبين حسب النقاط', 'saint-porphyrius'); ?>
            </span>
        </div>
    </div>

    <?php if (empty($members)): ?>
        <div class="sp-empty-state">
            <div class="sp-empty-icon">👥</div>
            <h3><?php _e('لا يوجد أعضاء', 'saint-porphyrius'); ?></h3>
            <p><?php _e('لم يتم العثور على نتائج', 'saint-porphyrius'); ?></p>
        </div>
    <?php else: ?>
        <div class="sp-community-list">
            <?php 
            $rank = 1;
            foreach ($members as $member): 
                $name_ar = get_user_meta($member->ID, 'sp_name_ar', true);
                $middle_name = get_user_meta($member->ID, 'sp_middle_name', true);
                $full_name = $name_ar ?: ($member->first_name . ' ' . $middle_name);
                $first_name_only = $member->first_name ?: explode(' ', $full_name)[0];
                $gender = get_user_meta($member->ID, 'sp_gender', true) ?: 'male';
                $church = get_user_meta($member->ID, 'sp_church_name', true);
                $points = $points_handler->get_balance($member->ID);
                $join_date = $member->user_registered;
                
                // Get forbidden status
                $forbidden_status = $forbidden_handler->get_user_status($member->ID);
                $card_status = $forbidden_status->card_status ?? 'none';
                $is_blocked = $forbidden_handler->is_user_blocked($member->ID);
                
                // Get attendance stats
                $attendance_stats = $attendance_handler->get_user_stats($member->ID);
                $attendance_count = $attendance_stats->attended ?? 0;
                
                // Is current user?
                $is_me = ($member->ID === $current_user->ID);
            ?>
                <div class="sp-community-card <?php echo $is_me ? 'is-me' : ''; ?> <?php echo $is_blocked ? 'is-blocked' : ''; ?>">
                    <div class="sp-community-rank">
                        <?php if ($rank <= 3): ?>
                            <span class="sp-rank-medal"><?php echo $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : '🥉'); ?></span>
                        <?php else: ?>
                            <span class="sp-rank-number"><?php echo esc_html($rank); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="sp-community-avatar">
                        <?php echo esc_html(mb_substr($first_name_only, 0, 1)); ?>
                        <?php if ($card_status === 'yellow'): ?>
                            <span class="sp-card-badge yellow">🟡</span>
                        <?php elseif ($card_status === 'red' || $is_blocked): ?>
                            <span class="sp-card-badge red">🔴</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="sp-community-info">
                        <h4 class="sp-community-name">
                            <?php echo esc_html($first_name_only); ?>
                            <span class="sp-community-gender"><?php echo $gender === 'male' ? '👨' : '👩'; ?></span>
                            <?php if ($is_me): ?>
                                <span class="sp-me-badge"><?php _e('أنا', 'saint-porphyrius'); ?></span>
                            <?php endif; ?>
                        </h4>
                        <?php if ($church): ?>
                            <p class="sp-community-church">⛪ <?php echo esc_html($church); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="sp-community-stats">
                        <div class="sp-community-points">
                            <span class="sp-stat-value"><?php echo esc_html($points); ?></span>
                            <span class="sp-stat-label"><?php _e('نقطة', 'saint-porphyrius'); ?></span>
                        </div>
                    </div>
                    
                    <button type="button" class="sp-community-expand" data-member-id="<?php echo esc_attr($member->ID); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    
                    <div class="sp-community-details" id="member-details-<?php echo esc_attr($member->ID); ?>">
                        <div class="sp-community-details-grid">
                            <div class="sp-detail-item">
                                <span class="sp-detail-icon">⭐</span>
                                <span class="sp-detail-value"><?php echo esc_html($points); ?></span>
                                <span class="sp-detail-label"><?php _e('النقاط', 'saint-porphyrius'); ?></span>
                            </div>
                            <div class="sp-detail-item">
                                <span class="sp-detail-icon">✅</span>
                                <span class="sp-detail-value"><?php echo esc_html($attendance_count); ?></span>
                                <span class="sp-detail-label"><?php _e('حضور', 'saint-porphyrius'); ?></span>
                            </div>
                            <div class="sp-detail-item">
                                <span class="sp-detail-icon"><?php 
                                    if ($is_blocked) echo '🔴';
                                    elseif ($card_status === 'yellow') echo '🟡';
                                    else echo '✨';
                                ?></span>
                                <span class="sp-detail-value"><?php 
                                    if ($is_blocked) _e('محروم', 'saint-porphyrius');
                                    elseif ($card_status === 'yellow') _e('إنذار', 'saint-porphyrius');
                                    else _e('منتظم', 'saint-porphyrius');
                                ?></span>
                                <span class="sp-detail-label"><?php _e('الحالة', 'saint-porphyrius'); ?></span>
                            </div>
                            <div class="sp-detail-item">
                                <span class="sp-detail-icon">📅</span>
                                <span class="sp-detail-value"><?php echo esc_html(date_i18n('M Y', strtotime($join_date))); ?></span>
                                <span class="sp-detail-label"><?php _e('انضم', 'saint-porphyrius'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php 
            $rank++;
            endforeach; 
            ?>
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

<style>
.sp-community-list {
    padding: 0 var(--sp-space-md) var(--sp-space-md);
}

.sp-community-card {
    background: var(--sp-bg-card);
    border-radius: var(--sp-radius-lg);
    padding: var(--sp-space-md);
    margin-bottom: var(--sp-space-md);
    display: grid;
    grid-template-columns: 40px 48px 1fr auto 32px;
    align-items: center;
    gap: var(--sp-space-md);
    box-shadow: var(--sp-shadow-sm);
    position: relative;
    transition: var(--sp-transition);
}

.sp-community-card.is-me {
    background: linear-gradient(135deg, rgba(212, 161, 42, 0.1) 0%, rgba(212, 161, 42, 0.05) 100%);
    border: 2px solid var(--sp-primary);
}

.sp-community-card.is-blocked {
    opacity: 0.6;
}

.sp-community-rank {
    text-align: center;
}

.sp-rank-medal {
    font-size: 24px;
}

.sp-rank-number {
    font-size: var(--sp-font-size-lg);
    font-weight: var(--sp-font-bold);
    color: var(--sp-text-secondary);
}

.sp-community-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--sp-primary) 0%, var(--sp-primary-dark, #B8912A) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--sp-font-size-lg);
    font-weight: var(--sp-font-bold);
    position: relative;
}

.sp-card-badge {
    position: absolute;
    bottom: -2px;
    right: -2px;
    font-size: 12px;
}

.sp-community-info {
    min-width: 0;
}

.sp-community-name {
    font-size: var(--sp-font-size-base);
    font-weight: var(--sp-font-semibold);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 4px;
}

.sp-community-gender {
    font-size: 12px;
}

.sp-me-badge {
    font-size: 10px;
    background: var(--sp-primary);
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    font-weight: var(--sp-font-medium);
}

.sp-community-church {
    font-size: var(--sp-font-size-xs);
    color: var(--sp-text-secondary);
    margin: 2px 0 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sp-community-stats {
    text-align: center;
}

.sp-community-points {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.sp-community-points .sp-stat-value {
    font-size: var(--sp-font-size-lg);
    font-weight: var(--sp-font-bold);
    color: var(--sp-primary);
}

.sp-community-points .sp-stat-label {
    font-size: var(--sp-font-size-xs);
    color: var(--sp-text-secondary);
}

.sp-community-expand {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    color: var(--sp-text-secondary);
    transition: var(--sp-transition);
}

.sp-community-expand.expanded {
    transform: rotate(180deg);
}

.sp-community-details {
    display: none;
    grid-column: 1 / -1;
    padding-top: var(--sp-space-md);
    border-top: 1px solid var(--sp-border-light);
    margin-top: var(--sp-space-sm);
}

.sp-community-details.show {
    display: block;
}

.sp-community-details-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: var(--sp-space-sm);
}

.sp-detail-item {
    text-align: center;
    padding: var(--sp-space-sm);
    background: var(--sp-gray-50);
    border-radius: var(--sp-radius-md);
}

.sp-detail-icon {
    font-size: 20px;
    display: block;
    margin-bottom: 4px;
}

.sp-detail-value {
    font-size: var(--sp-font-size-sm);
    font-weight: var(--sp-font-semibold);
    color: var(--sp-text-primary);
    display: block;
}

.sp-detail-label {
    font-size: var(--sp-font-size-xs);
    color: var(--sp-text-secondary);
    display: block;
}

@media (max-width: 400px) {
    .sp-community-card {
        grid-template-columns: 32px 40px 1fr auto 28px;
        gap: var(--sp-space-sm);
        padding: var(--sp-space-sm);
    }
    
    .sp-community-avatar {
        width: 40px;
        height: 40px;
        font-size: var(--sp-font-size-base);
    }
    
    .sp-community-details-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
document.querySelectorAll('.sp-community-expand').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const memberId = this.getAttribute('data-member-id');
        const details = document.getElementById('member-details-' + memberId);
        
        if (details.classList.contains('show')) {
            details.classList.remove('show');
            this.classList.remove('expanded');
        } else {
            // Close all other expanded
            document.querySelectorAll('.sp-community-details.show').forEach(function(d) {
                d.classList.remove('show');
            });
            document.querySelectorAll('.sp-community-expand.expanded').forEach(function(b) {
                b.classList.remove('expanded');
            });
            
            details.classList.add('show');
            this.classList.add('expanded');
        }
    });
});
</script>
