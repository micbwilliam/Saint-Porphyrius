<?php
/**
 * Saint Porphyrius - Admin Members (Mobile)
 * View and manage church members
 */

if (!defined('ABSPATH')) {
    exit;
}

$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

// Get members
$args = array(
    'role__in' => array('sp_member', 'sp_church_admin'),
    'orderby' => 'registered',
    'order' => 'DESC',
);

if ($search) {
    $args['search'] = '*' . $search . '*';
    $args['search_columns'] = array('user_login', 'user_email', 'display_name');
}

$members = get_users($args);
$points_handler = SP_Points::get_instance();
?>

<!-- Admin Header -->
<div class="sp-unified-header sp-admin-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/admin'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('الأعضاء', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-spacer"></div>
    </div>
</div>

<!-- Main Content -->
<main class="sp-page-content sp-admin-content">
    <!-- Search -->
    <form method="get" class="sp-search-form">
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
    <div class="sp-admin-count">
        <?php printf(__('%d عضو', 'saint-porphyrius'), count($members)); ?>
    </div>

    <?php if (empty($members)): ?>
        <div class="sp-empty-state">
            <div class="sp-empty-icon">👥</div>
            <h3><?php _e('لا يوجد أعضاء', 'saint-porphyrius'); ?></h3>
            <p><?php _e('سيظهر الأعضاء المعتمدون هنا', 'saint-porphyrius'); ?></p>
        </div>
    <?php else: ?>
        <div class="sp-members-list">
            <?php foreach ($members as $member): 
                $name_ar = get_user_meta($member->ID, 'sp_name_ar', true);
                $middle_name = get_user_meta($member->ID, 'sp_middle_name', true);
                $full_name = $name_ar ?: ($member->first_name . ' ' . $middle_name . ' ' . $member->last_name);
                $phone = get_user_meta($member->ID, 'sp_phone', true);
                $church = get_user_meta($member->ID, 'sp_church_name', true);
                $points = $points_handler->get_balance($member->ID);
                $last_login = get_user_meta($member->ID, 'sp_last_login', true);
            ?>
                <div class="sp-member-card" data-member-id="<?php echo esc_attr($member->ID); ?>">
                    <div class="sp-member-header">
                        <div class="sp-member-avatar">
                            <?php echo esc_html(mb_substr($full_name, 0, 1)); ?>
                        </div>
                        <div class="sp-member-info">
                            <h4><?php echo esc_html($full_name); ?></h4>
                            <span class="sp-member-email"><?php echo esc_html($member->user_email); ?></span>
                        </div>
                        <div class="sp-member-points">
                            <span class="sp-member-points-value"><?php echo esc_html($points); ?></span>
                            <span class="sp-member-points-label"><?php _e('نقطة', 'saint-porphyrius'); ?></span>
                        </div>
                    </div>
                    
                    <div class="sp-member-details">
                        <?php if ($phone): ?>
                        <div class="sp-member-detail">
                            <span class="sp-member-detail-icon">📱</span>
                            <a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($phone); ?></a>
                        </div>
                        <?php endif; ?>
                        <?php if ($church): ?>
                        <div class="sp-member-detail">
                            <span class="sp-member-detail-icon">⛪</span>
                            <span><?php echo esc_html($church); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="sp-member-detail">
                            <span class="sp-member-detail-icon">📅</span>
                            <span><?php _e('انضم:', 'saint-porphyrius'); ?> <?php echo esc_html(date_i18n('j M Y', strtotime($member->user_registered))); ?></span>
                        </div>
                        <?php if ($last_login): ?>
                        <div class="sp-member-detail">
                            <span class="sp-member-detail-icon">🕐</span>
                            <span><?php _e('آخر دخول:', 'saint-porphyrius'); ?> <?php echo esc_html(date_i18n('j M Y', strtotime($last_login))); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="sp-member-actions">
                        <a href="<?php echo home_url('/app/admin/points?user_id=' . $member->ID); ?>" class="sp-btn sp-btn-outline sp-btn-sm">
                            ⭐ <?php _e('النقاط', 'saint-porphyrius'); ?>
                        </a>
                        <a href="tel:<?php echo esc_attr($phone); ?>" class="sp-btn sp-btn-outline sp-btn-sm">
                            📞 <?php _e('اتصال', 'saint-porphyrius'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
