<?php
/**
 * Saint Porphyrius - Dashboard Template (Unified Design)
 * User dashboard after login - Modern, professional design
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$first_name = $current_user->first_name;
$middle_name = get_user_meta($current_user->ID, 'sp_middle_name', true);
$last_name = $current_user->last_name;
$church_name = get_user_meta($current_user->ID, 'sp_church_name', true);
$gender = get_user_meta($current_user->ID, 'sp_gender', true);
$is_female = ($gender === 'female');

// Get points and events data
$points_handler = SP_Points::get_instance();
$events_handler = SP_Events::get_instance();
$gamification = SP_Gamification::get_instance();

$user_points = $points_handler->get_balance($current_user->ID);
$upcoming_events = $events_handler->get_upcoming(3);

// Get profile image for hero card
$sp_hero_profile_img = '';
if (class_exists('SP_Social_Profile')) {
    $sp_hero_profile_img = SP_Social_Profile::get_instance()->get_profile_image_url($current_user->ID);
}

// Get last point transaction to determine trend (up/down)
$sp_last_points_log = $points_handler->get_history($current_user->ID, array('limit' => 1));
$sp_points_trend = 'neutral'; // neutral, up, down
if (!empty($sp_last_points_log)) {
    $sp_points_trend = ($sp_last_points_log[0]->points >= 0) ? 'up' : 'down';
}

// Get birthday info
$birthday_info = $gamification->get_birthday_message($current_user->ID);

// Get profile completion info
$profile_completion = $gamification->get_profile_completion($current_user->ID);
$gamification_settings = $gamification->get_settings();

// Check and award birthday points if applicable
$gamification->award_birthday_points($current_user->ID);

// Check and award profile completion if applicable (one-time only)
$profile_reward_just_awarded = false;
$profile_already_rewarded = get_user_meta($current_user->ID, 'sp_profile_completion_rewarded', true);
if (!$profile_already_rewarded && $profile_completion['is_complete'] && $gamification_settings['profile_completion_enabled']) {
    $result = $gamification->award_profile_completion($current_user->ID);
    if ($result && !is_wp_error($result)) {
        $profile_reward_just_awarded = true;
        // Refresh points after award
        $user_points = $points_handler->get_balance($current_user->ID);
    }
}

// Check if we should show the profile completion notification
$profile_notification_seen = get_user_meta($current_user->ID, 'sp_profile_completion_notification_seen', true);
$show_profile_congratulation = ($profile_completion['is_complete'] && $profile_already_rewarded && !$profile_notification_seen);

// If just awarded, show notification and mark as seen on next load
if ($profile_reward_just_awarded) {
    $show_profile_congratulation = true;
} elseif ($show_profile_congratulation) {
    // Mark as seen for next login
    update_user_meta($current_user->ID, 'sp_profile_completion_notification_seen', 1);
}

// Check if story quiz is completed
$story_quiz_completed = $gamification->has_completed_story_quiz($current_user->ID);

// Check if service instructions quiz is completed
$service_instructions_completed = $gamification->has_completed_service_instructions($current_user->ID);
$service_instructions_count = $gamification->get_service_instructions_completion_count($current_user->ID);

// Get attendance stats
global $wpdb;
$attendance_table = $wpdb->prefix . 'sp_attendance';
$total_events_attended = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$attendance_table} WHERE user_id = %d AND status = 'present'",
    $current_user->ID
));

// Calculate user rank
$leaderboard = $points_handler->get_leaderboard(100);
$user_rank = 0;
foreach ($leaderboard as $index => $user) {
    if ($user->user_id == $current_user->ID) {
        $user_rank = $index + 1;
        break;
    }
}
?>

<!-- Unified Header -->
<div class="sp-unified-header">
    <div class="sp-header-inner">
        <div class="sp-header-logo">
            <img src="<?php echo esc_url(SP_PLUGIN_URL . 'media/logo.png'); ?>" alt="Logo" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover;">
        </div>
        <h1 class="sp-header-title"><?php _e('الرئيسية', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-actions">
            <?php 
            $sp_notif_handler = SP_Notifications::get_instance();
            $sp_unread = $sp_notif_handler->get_accurate_unread_count(get_current_user_id());
            ?>
            <a href="<?php echo home_url('/app/notifications'); ?>" class="sp-header-action sp-bell-icon" title="الإشعارات">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <?php if ($sp_unread > 0): ?>
                <span class="sp-bell-badge"><?php echo $sp_unread > 99 ? '99+' : $sp_unread; ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo home_url('/app/profile'); ?>" class="sp-header-action">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </a>
        </div>
    </div>
</div>

<!-- Main Content -->
<main class="sp-page-content has-bottom-nav">
    <!-- Hero Card with User Info -->
    <div class="sp-hero-card">
        <div class="sp-hero-content">
            <div class="sp-hero-avatar-section">
                <a href="<?php echo esc_url(home_url('/app/member/')); ?>" class="sp-hero-avatar-link">
                    <?php if ($sp_hero_profile_img): ?>
                        <img src="<?php echo esc_url($sp_hero_profile_img); ?>" alt="" class="sp-hero-avatar-img">
                    <?php else: ?>
                        <div class="sp-hero-avatar-placeholder">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                    <?php endif; ?>
                </a>
                <a href="<?php echo esc_url(home_url('/app/member/')); ?>" class="sp-hero-profile-label"><?php _e('ملفي', 'saint-porphyrius'); ?></a>
            </div>
            <div class="sp-hero-text">
                <h2><?php 
                    $display_name = trim($first_name . ' ' . $middle_name);
                    echo esc_html(sp_custom_text('hero_greeting', $gender, array('name' => $display_name)));
                ?></h2>
                <p><?php echo esc_html(sp_custom_text('hero_subtitle', $gender)); ?></p>
            </div>
            <div class="sp-hero-stat sp-hero-stat--<?php echo esc_attr($sp_points_trend); ?>">
                <span class="sp-hero-stat-value">
                    <?php echo esc_html($user_points); ?>
                    <?php if ($sp_points_trend === 'up'): ?>
                        <span class="sp-hero-trend sp-hero-trend--up" title="<?php esc_attr_e('النقاط في ارتفاع', 'saint-porphyrius'); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
                        </span>
                    <?php elseif ($sp_points_trend === 'down'): ?>
                        <span class="sp-hero-trend sp-hero-trend--down" title="<?php esc_attr_e('النقاط في انخفاض', 'saint-porphyrius'); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </span>
                    <?php endif; ?>
                </span>
                <span class="sp-hero-stat-label"><?php _e('نقطة', 'saint-porphyrius'); ?></span>
            </div>
        </div>
    </div>

    <?php // Birthday Celebration Card with Gift Selection ?>
    <?php if ($birthday_info): 
        $birthday_gifts = $gamification->get_birthday_gifts(true);
        $has_claimed = $gamification->has_claimed_birthday_gift($current_user->ID);
        $claimed_gift = $has_claimed ? $gamification->get_user_birthday_gift_claim($current_user->ID) : null;
    ?>
    <div class="sp-birthday-card <?php echo $birthday_info['is_birthday'] ? 'is-birthday' : ''; ?>">
        <div class="sp-birthday-content">
            <div class="sp-birthday-emoji">🎂</div>
            <div class="sp-birthday-text">
                <h3><?php echo esc_html($birthday_info['message']); ?></h3>
                <?php if ($birthday_info['is_birthday'] && $gamification_settings['birthday_reward_enabled']): ?>
                <p class="sp-birthday-reward">🎁 <?php echo esc_html(sp_custom_text('birthday_reward_msg', $gender, array('points' => $gamification_settings['birthday_points']))); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="sp-birthday-confetti"></div>
    </div>

    <?php // Birthday Gift Selection (only during birthday period) ?>
    <?php if (!empty($birthday_gifts)): ?>
    <div class="sp-card" style="margin: 0 16px 16px; padding: 0; overflow: hidden; border-radius: 20px; background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%); border: 1px solid #FDE68A;" id="sp-birthday-gift-section">
        <div style="padding: 20px 20px 12px; text-align: center;">
            <div style="font-size: 2.5rem; margin-bottom: 8px;">🎁</div>
            <?php if ($has_claimed && $claimed_gift): ?>
                <h3 style="margin: 0 0 4px; font-size: 1.1rem; color: #92400E;">
                    <?php echo esc_html(sp_custom_text('birthday_gift_claimed_title', $gender)); ?>
                </h3>
                <p style="margin: 0; font-size: 0.85rem; color: #B45309;">
                    <?php echo esc_html(sp_custom_text('birthday_gift_claimed_desc', $gender, array('icon' => $claimed_gift->icon, 'title' => $claimed_gift->title))); ?>
                </p>
                <?php if ($claimed_gift->gift_type === 'points'): ?>
                <p style="margin: 4px 0 0; font-size: 0.8rem; color: #059669; font-weight: 600;">
                    ⭐ <?php echo esc_html(sp_custom_text('birthday_gift_points_added', $gender, array('points' => $claimed_gift->value))); ?>
                </p>
                <?php elseif ($claimed_gift->gift_type === 'money'): ?>
                <p style="margin: 4px 0 0; font-size: 0.8rem; color: #B45309; font-weight: 600;">
                    💰 <?php echo esc_html(sp_custom_text('birthday_gift_money_added', $gender, array('value' => $claimed_gift->value))); ?>
                </p>
                <?php else: ?>
                <p style="margin: 4px 0 0; font-size: 0.8rem; color: #6D28D9; font-weight: 600;">
                    <?php echo esc_html($claimed_gift->icon); ?> <?php echo esc_html(sp_custom_text('birthday_gift_other_added', $gender)); ?>
                </p>
                <?php endif; ?>
            <?php else: ?>
                <h3 style="margin: 0 0 4px; font-size: 1.1rem; color: #92400E;">
                    <?php echo esc_html(sp_custom_text('birthday_gift_choose_title', $gender)); ?>
                </h3>
                <p style="margin: 0; font-size: 0.85rem; color: #B45309;">
                    <?php echo esc_html(sp_custom_text('birthday_gift_choose_desc', $gender)); ?>
                </p>
            <?php endif; ?>
        </div>

        <?php if (!$has_claimed): ?>
        <div style="padding: 0 16px 20px; display: flex; flex-direction: column; gap: 10px;" id="sp-gift-options">
            <?php foreach ($birthday_gifts as $gift): ?>
            <div class="sp-birthday-gift-option" data-gift-id="<?php echo esc_attr($gift->id); ?>"
                 style="display: flex; align-items: center; gap: 12px; padding: 14px 16px; background: white; border-radius: 16px; border: 2px solid #E5E7EB; cursor: pointer; transition: all 0.2s;">
                <div style="font-size: 1.8rem; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; background: #FEF3C7; border-radius: 14px; flex-shrink: 0;">
                    <?php echo esc_html($gift->icon); ?>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-weight: 600; font-size: 0.95rem; color: #1F2937;">
                        <?php echo esc_html($gift->title); ?>
                    </div>
                    <?php if ($gift->description): ?>
                    <div style="font-size: 0.8rem; color: #6B7280; margin-top: 2px;">
                        <?php echo esc_html($gift->description); ?>
                    </div>
                    <?php endif; ?>
                    <div style="font-size: 0.75rem; color: #9CA3AF; margin-top: 2px;">
                        <?php
                        $gift_types = $gamification->get_gift_types();
                        echo esc_html($gift_types[$gift->gift_type]['icon'] . ' ' . $gift_types[$gift->gift_type]['label']);
                        if ($gift->gift_type === 'points' && $gift->value) {
                            printf(' — %s ' . __('نقطة', 'saint-porphyrius'), esc_html($gift->value));
                        } elseif ($gift->gift_type === 'money' && $gift->value) {
                            printf(' — %s ' . __('جنيه', 'saint-porphyrius'), esc_html($gift->value));
                        }
                        ?>
                    </div>
                </div>
                <div class="sp-gift-check" style="width: 28px; height: 28px; border-radius: 50%; border: 2px solid #D1D5DB; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all 0.2s;">
                </div>
            </div>
            <?php endforeach; ?>

            <button type="button" id="sp-claim-gift-btn" disabled
                    style="margin-top: 4px; padding: 14px; background: #D1D5DB; color: white; border: none; border-radius: 14px; font-size: 1rem; font-weight: 700; cursor: not-allowed; transition: all 0.3s; font-family: inherit;">
                <?php _e('اختار هديتي 🎁', 'saint-porphyrius'); ?>
            </button>
        </div>

        <script>
        (function() {
            var selectedGiftId = null;
            var options = document.querySelectorAll('.sp-birthday-gift-option');
            var claimBtn = document.getElementById('sp-claim-gift-btn');

            options.forEach(function(option) {
                option.addEventListener('click', function() {
                    // Deselect all
                    options.forEach(function(o) {
                        o.style.borderColor = '#E5E7EB';
                        o.style.background = 'white';
                        o.querySelector('.sp-gift-check').innerHTML = '';
                        o.querySelector('.sp-gift-check').style.borderColor = '#D1D5DB';
                        o.querySelector('.sp-gift-check').style.background = 'transparent';
                    });
                    // Select this one
                    this.style.borderColor = '#F59E0B';
                    this.style.background = '#FFFBEB';
                    var check = this.querySelector('.sp-gift-check');
                    check.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>';
                    check.style.borderColor = '#F59E0B';
                    check.style.background = '#F59E0B';
                    selectedGiftId = this.getAttribute('data-gift-id');
                    claimBtn.disabled = false;
                    claimBtn.style.background = 'linear-gradient(135deg, #F59E0B, #D97706)';
                    claimBtn.style.cursor = 'pointer';
                });
            });

            claimBtn.addEventListener('click', function() {
                if (!selectedGiftId || claimBtn.disabled) return;
                claimBtn.disabled = true;
                claimBtn.textContent = '<?php _e('جاري الحفظ...', 'saint-porphyrius'); ?>';

                var formData = new FormData();
                formData.append('action', 'sp_claim_birthday_gift');
                formData.append('nonce', '<?php echo wp_create_nonce('sp_nonce'); ?>');
                formData.append('gift_id', selectedGiftId);

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        var section = document.getElementById('sp-birthday-gift-section');
                        section.innerHTML = '<div style="padding: 24px; text-align: center;">' +
                            '<div style="font-size: 3rem; margin-bottom: 8px;">🎉</div>' +
                            '<h3 style="margin: 0 0 8px; color: #92400E; font-size: 1.1rem;">' + res.data.message + '</h3>' +
                            '<p style="margin: 0; font-size: 0.85rem; color: #B45309;"><?php _e('كل سنة وانت طيب!', 'saint-porphyrius'); ?></p>' +
                            '</div>';
                    } else {
                        alert(res.data.message);
                        claimBtn.disabled = false;
                        claimBtn.textContent = '<?php _e('اختار هديتي 🎁', 'saint-porphyrius'); ?>';
                        claimBtn.style.background = 'linear-gradient(135deg, #F59E0B, #D97706)';
                    }
                })
                .catch(function() {
                    alert('<?php _e('حدث خطأ، حاول مرة أخرى', 'saint-porphyrius'); ?>');
                    claimBtn.disabled = false;
                    claimBtn.textContent = '<?php _e('اختار هديتي 🎁', 'saint-porphyrius'); ?>';
                });
            });
        })();
        </script>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <?php
    // Birthday Congratulations Section - Show other members' birthdays
    $sp_birthday_members = $gamification->get_birthday_members($current_user->ID);
    if (!empty($sp_birthday_members)):
        $sp_current_year = date('Y');
    ?>
    <div class="sp-birthday-congrats-section">
        <div class="sp-birthday-congrats-header">
            <span class="sp-birthday-congrats-icon">🎂</span>
            <h3><?php _e('أعياد ميلاد اليوم', 'saint-porphyrius'); ?></h3>
        </div>

        <?php foreach ($sp_birthday_members as $bm):
            $bm_display = trim($bm['first_name'] . ' ' . $bm['middle_name']);
            $bm_already_sent = $gamification->has_congratulated($current_user->ID, $bm['user_id'], $sp_current_year);
        ?>
        <div class="sp-birthday-member-card" data-user-id="<?php echo esc_attr($bm['user_id']); ?>">
            <div class="sp-birthday-member-info">
                <div class="sp-birthday-member-avatar">
                    <?php if (!empty($bm['profile_img'])): ?>
                        <img src="<?php echo esc_url($bm['profile_img']); ?>" alt="">
                    <?php else: ?>
                        <div class="sp-birthday-member-avatar-placeholder">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                    <?php endif; ?>
                    <span class="sp-birthday-member-cake">🎂</span>
                </div>
                <div class="sp-birthday-member-text">
                    <h4><?php echo esc_html($bm_display); ?></h4>
                    <p>
                        <?php if ($bm['is_today']): ?>
                            <?php echo esc_html(sp_custom_text('birthday_today', $bm['gender'])); ?>
                        <?php else: ?>
                            <?php echo esc_html(sp_custom_text('birthday_soon', $bm['gender'])); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <?php if ($bm_already_sent): ?>
                <div class="sp-birthday-congrats-sent">
                    <span>✅</span>
                    <span><?php echo esc_html(sp_custom_text('birthday_congrat_sent', $bm['gender'])); ?></span>
                </div>
            <?php else: ?>
                <div class="sp-birthday-gift-picker">
                    <p class="sp-birthday-gift-label"><?php echo esc_html(sp_custom_text('birthday_congrat_label', $bm['gender'])); ?></p>
                    <div class="sp-birthday-amounts">
                        <button type="button" class="sp-birthday-amount-btn" data-amount="5">5</button>
                        <button type="button" class="sp-birthday-amount-btn" data-amount="10">10</button>
                        <button type="button" class="sp-birthday-amount-btn" data-amount="20">20</button>
                        <button type="button" class="sp-birthday-amount-btn" data-amount="50">50</button>
                        <button type="button" class="sp-birthday-amount-btn sp-birthday-amount-other" data-amount="other"><?php _e('أخرى', 'saint-porphyrius'); ?></button>
                    </div>
                    <div class="sp-birthday-custom-amount" style="display: none;">
                        <input type="number" min="51" max="1000" placeholder="<?php esc_attr_e('أكثر من 50 نقطة', 'saint-porphyrius'); ?>" class="sp-birthday-custom-input">
                    </div>
                    <div class="sp-birthday-message-wrap">
                        <input type="text" maxlength="100" placeholder="<?php esc_attr_e('رسالة قصيرة (اختياري)', 'saint-porphyrius'); ?>" class="sp-birthday-message-input">
                    </div>
                    <button type="button" class="sp-birthday-send-btn" disabled>
                        <?php _e('إرسال التهنئة 🎁', 'saint-porphyrius'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php // Profile Completion Congratulation Card (shows once after completing profile) ?>
    <?php if ($show_profile_congratulation): ?>
    <div class="sp-profile-congrats-card">
        <div class="sp-profile-congrats-content">
            <div class="sp-profile-congrats-emoji">🏆</div>
            <div class="sp-profile-congrats-text">
                <h3><?php echo esc_html(sp_custom_text('profile_complete_praise', $gender)); ?> <?php echo esc_html(sp_custom_text('profile_complete_msg', $gender)); ?></h3>
                <p class="sp-profile-congrats-reward">🎁 <?php echo esc_html(sp_custom_text('profile_complete_reward', $gender, array('points' => $gamification_settings['profile_completion_points']))); ?></p>
            </div>
        </div>
        <div class="sp-profile-congrats-confetti"></div>
    </div>
    <?php endif; ?>

    <?php // Profile Completion Card ?>
    <?php if (!$profile_completion['is_complete'] && $gamification_settings['profile_completion_enabled']): ?>
    <div class="sp-profile-completion-card">
        <div class="sp-profile-completion-header">
            <div class="sp-profile-completion-icon">📝</div>
            <div class="sp-profile-completion-info">
                <h3><?php echo esc_html(sp_custom_text('profile_incomplete_title', $gender)); ?></h3>
                <p><?php echo esc_html(sp_custom_text('profile_incomplete_desc', $gender, array('points' => $gamification_settings['profile_completion_points']))); ?></p>
            </div>
        </div>
        <div class="sp-profile-completion-progress">
            <div class="sp-profile-completion-bar">
                <div class="sp-profile-completion-fill" style="width: <?php echo esc_attr($profile_completion['percentage']); ?>%;"></div>
            </div>
            <span class="sp-profile-completion-percent"><?php echo esc_html($profile_completion['percentage']); ?>%</span>
        </div>
        <a href="<?php echo home_url('/app/profile'); ?>" class="sp-btn sp-btn-outline sp-btn-sm sp-btn-block">
            <?php echo esc_html(sp_custom_text('profile_incomplete_btn', $gender)); ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 6 15 12 9 18"></polyline>
            </svg>
        </a>
    </div>
    <?php endif; ?>

    <?php // Story Quiz Card & Service Instructions - Always visible ?>
    <div class="sp-learning-section">
        <!-- Service Instructions Card -->
        <div class="sp-story-quiz-card">
            <div class="sp-story-quiz-icon">📝</div>
            <div class="sp-story-quiz-content">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                    <h3 style="margin: 0;"><?php _e('تعليمات الخدمة', 'saint-porphyrius'); ?></h3>
                    <span style="background: #fbbf24; color: #78350f; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600;">✨ تم التحديث</span>
                </div>
                <?php if ($service_instructions_completed): ?>
                    <p><?php echo esc_html(sp_custom_text('service_instr_complete', $gender)); ?></p>
                <?php elseif ($service_instructions_count === 1 && $gamification_settings['service_instructions_enabled']): ?>
                    <p><?php echo esc_html(sp_custom_text('service_instr_retry', $gender, array('points' => $gamification_settings['service_instructions_points']))); ?></p>
                <?php elseif ($gamification_settings['service_instructions_enabled']): ?>
                    <p><?php echo esc_html(sp_custom_text('service_instr_incomplete', $gender, array('points' => $gamification_settings['service_instructions_points']))); ?></p>
                <?php else: ?>
                    <p><?php echo esc_html(sp_custom_text('service_instr_complete', $gender)); ?></p>
                <?php endif; ?>
            </div>
            <a href="<?php echo home_url('/app/service-instructions'); ?>" class="sp-btn sp-btn-primary sp-btn-sm">
                <?php
                if ($service_instructions_completed) {
                    _e('عرض مرة أخرى', 'saint-porphyrius');
                } elseif ($service_instructions_count === 1) {
                    _e('إعادة الاختبار', 'saint-porphyrius');
                } else {
                    _e('ابدأ الآن', 'saint-porphyrius');
                }
                ?>
            </a>
        </div>

        <!-- Saint Story Card -->
        <div class="sp-story-quiz-card">
            <div class="sp-story-quiz-icon">📖</div>
            <div class="sp-story-quiz-content">
                <h3><?php _e('قصة حياة القديس الشهيد برفوريوس البهلوان', 'saint-porphyrius'); ?></h3>
                <?php if (!$story_quiz_completed && $gamification_settings['story_quiz_enabled']): ?>
                    <p><?php echo esc_html(sp_custom_text('story_quiz_incomplete', $gender, array('points' => $gamification_settings['story_quiz_points']))); ?></p>
                <?php else: ?>
                    <p><?php echo esc_html(sp_custom_text('story_quiz_complete', $gender)); ?></p>
                <?php endif; ?>
            </div>
            <a href="<?php echo home_url('/app/saint-story'); ?>" class="sp-btn sp-btn-primary sp-btn-sm">
                <?php echo $story_quiz_completed ? __('اقرأ مرة أخرى', 'saint-porphyrius') : __('ابدأ الآن', 'saint-porphyrius'); ?>
            </a>
        </div>

        <!-- Christian Quizzes Card -->
        <?php
        $quiz_handler = SP_Quiz::get_instance();
        $user_quiz_points = $quiz_handler->get_user_total_quiz_points($current_user->ID);
        $published_count = count($quiz_handler->get_all_content('published'));
        ?>
        <?php if ($published_count > 0): ?>
        <div class="sp-story-quiz-card">
            <div class="sp-story-quiz-icon">📝</div>
            <div class="sp-story-quiz-content">
                <h3><?php _e('مسابقات برفوريوس', 'saint-porphyrius'); ?></h3>
                <p><?php echo esc_html(sp_custom_text('quizzes_available', $gender, array('count' => $published_count))); ?></p>
            </div>
            <a href="<?php echo home_url('/app/quizzes'); ?>" class="sp-btn sp-btn-primary sp-btn-sm">
                <?php _e('ابدأ الآن', 'saint-porphyrius'); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php // Admin Section (Only for Admins) ?>
    <?php if (current_user_can('manage_options')): ?>
    <div class="sp-admin-banner">
        <div class="sp-admin-banner-content">
            <div class="sp-admin-banner-text">
                <h3><?php _e('🔐 منطقة الإدارة', 'saint-porphyrius'); ?></h3>
                <p><?php _e('إدارة الفعاليات والأعضاء والنقاط', 'saint-porphyrius'); ?></p>
            </div>
            <a href="<?php echo home_url('/app/admin'); ?>" class="sp-btn sp-btn-primary">
                <?php _e('الدخول للإدارة', 'saint-porphyrius'); ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 6 15 12 9 18"></polyline>
                </svg>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <?php 
    // Get user's discipline status - Always show the card
    $forbidden_handler = SP_Forbidden::get_instance();
    $discipline_status = $forbidden_handler->get_visual_status($current_user->ID);
    
    $card_class = '';
    if ($discipline_status['is_blocked']) {
        $card_class = 'blocked';
    } elseif ($discipline_status['card_status'] === 'red' || $discipline_status['consecutive_absences'] >= $discipline_status['yellow_threshold']) {
        $card_class = 'warning';
    } elseif ($discipline_status['consecutive_absences'] == 0 && $discipline_status['card_status'] === 'none') {
        $card_class = 'good';
    }
    ?>
    <!-- Discipline Status Card -->
    <div class="sp-discipline-status-card <?php echo esc_attr($card_class); ?>">
        <?php if ($discipline_status['is_blocked']): ?>
            <div class="sp-blocked-message">
                <div class="sp-blocked-icon">🔴</div>
                <h3><?php echo esc_html(sp_custom_text('discipline_blocked_title', $gender)); ?></h3>
                <p><?php echo esc_html(sp_custom_text('discipline_blocked_msg', $gender)); ?></p>
            </div>
        <?php else: ?>
            <div class="sp-discipline-header">
                <div class="sp-discipline-title">
                    <?php if ($discipline_status['consecutive_absences'] == 0 && $discipline_status['card_status'] === 'none'): ?>
                        ✅ <?php _e('حالة الحضور', 'saint-porphyrius'); ?>
                    <?php else: ?>
                        📊 <?php _e('حالة الحضور', 'saint-porphyrius'); ?>
                    <?php endif; ?>
                </div>
                <?php if ($discipline_status['card_status'] === 'yellow'): ?>
                    <span class="sp-discipline-card-badge yellow">🟡 <?php _e('كارت أصفر', 'saint-porphyrius'); ?></span>
                <?php elseif ($discipline_status['card_status'] === 'red'): ?>
                    <span class="sp-discipline-card-badge red">🔴 <?php _e('كارت أحمر', 'saint-porphyrius'); ?></span>
                <?php elseif ($discipline_status['consecutive_absences'] == 0): ?>
                    <span class="sp-discipline-card-badge good">✓ <?php _e('ممتاز', 'saint-porphyrius'); ?></span>
                <?php endif; ?>
            </div>
            
            <div class="sp-discipline-progress">
                <div class="sp-discipline-progress-bar">
                    <div class="sp-discipline-progress-fill" style="width: <?php echo esc_attr(min(100, $discipline_status['percentage'])); ?>%;"></div>
                </div>
            </div>
            
            <div class="sp-discipline-info">
                <span><?php echo esc_html(sp_custom_text('discipline_absences', $gender, array('current' => $discipline_status['consecutive_absences'], 'max' => $discipline_status['max_absences']))); ?></span>
                <span>
                    <?php if ($discipline_status['consecutive_absences'] == 0): ?>
                        <?php _e('لا يوجد غيابات 👏', 'saint-porphyrius'); ?>
                    <?php elseif ($discipline_status['consecutive_absences'] < $discipline_status['yellow_threshold']): ?>
                        <?php echo esc_html(sp_custom_text('discipline_remaining_yellow', $gender, array('count' => $discipline_status['yellow_threshold'] - $discipline_status['consecutive_absences']))); ?>
                    <?php elseif ($discipline_status['consecutive_absences'] < $discipline_status['max_absences']): ?>
                        <?php echo esc_html(sp_custom_text('discipline_remaining_red', $gender, array('count' => $discipline_status['max_absences'] - $discipline_status['consecutive_absences']))); ?>
                    <?php endif; ?>
                </span>
            </div>
            
            <?php if ($discipline_status['forbidden_remaining'] > 0): ?>
            <div class="sp-forbidden-status">
                <span>⛔</span>
                <span><?php echo esc_html(sp_custom_text('discipline_forbidden', $gender, array('count' => $discipline_status['forbidden_remaining']))); ?></span>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Quick Stats -->
    <div class="sp-stats-row">
        <div class="sp-stat-card">
            <div class="sp-stat-value"><?php echo esc_html($user_points); ?></div>
            <div class="sp-stat-label"><?php _e('نقاطي', 'saint-porphyrius'); ?></div>
        </div>
        <div class="sp-stat-card">
            <div class="sp-stat-value"><?php echo esc_html($total_events_attended ?: 0); ?></div>
            <div class="sp-stat-label"><?php _e('حضور', 'saint-porphyrius'); ?></div>
        </div>
        <div class="sp-stat-card">
            <div class="sp-stat-value">#<?php echo esc_html($user_rank ?: '-'); ?></div>
            <div class="sp-stat-label"><?php _e('ترتيبي', 'saint-porphyrius'); ?></div>
        </div>
    </div>

    <!-- Quick Actions Section -->
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title"><?php _e('الخدمات السريعة', 'saint-porphyrius'); ?></h3>
        </div>
        
        <div class="sp-list">
            <a href="<?php echo home_url('/app/events'); ?>" class="sp-feature-card">
                <div class="sp-feature-icon" style="background: linear-gradient(135deg, #96C291 0%, #7DAF78 100%); color: white;">
                    📅
                </div>
                <div class="sp-feature-content">
                    <h4 class="sp-feature-title"><?php _e('الفعاليات', 'saint-porphyrius'); ?></h4>
                    <p class="sp-feature-desc"><?php _e('تصفح الفعاليات القادمة', 'saint-porphyrius'); ?></p>
                </div>
                <svg class="sp-feature-arrow" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>
            
            <a href="<?php echo home_url('/app/points'); ?>" class="sp-feature-card">
                <div class="sp-feature-icon" style="background: linear-gradient(135deg, #F2D388 0%, #E5C470 100%); color: white;">
                    ⭐
                </div>
                <div class="sp-feature-content">
                    <h4 class="sp-feature-title"><?php _e('نقاطي', 'saint-porphyrius'); ?></h4>
                    <p class="sp-feature-desc"><?php _e('تتبع نقاطك وسجل النقاط', 'saint-porphyrius'); ?></p>
                </div>
                <svg class="sp-feature-arrow" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>
            
            <a href="<?php echo home_url('/app/leaderboard'); ?>" class="sp-feature-card">
                <div class="sp-feature-icon" style="background: linear-gradient(135deg, #6C9BCF 0%, #5A89BD 100%); color: white;">
                    🏆
                </div>
                <div class="sp-feature-content">
                    <h4 class="sp-feature-title"><?php _e('المتصدرين', 'saint-porphyrius'); ?></h4>
                    <p class="sp-feature-desc"><?php _e('شاهد ترتيبك بين الأعضاء', 'saint-porphyrius'); ?></p>
                </div>
                <svg class="sp-feature-arrow" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>
            
            <a href="<?php echo home_url('/app/community'); ?>" class="sp-feature-card">
                <div class="sp-feature-icon" style="background: linear-gradient(135deg, #EC4899 0%, #DB2777 100%); color: white;">
                    👥
                </div>
                <div class="sp-feature-content">
                    <h4 class="sp-feature-title"><?php _e('أعضاء الأسرة', 'saint-porphyrius'); ?></h4>
                    <p class="sp-feature-desc"><?php _e('تعرف على أعضاء أسرتك', 'saint-porphyrius'); ?></p>
                </div>
                <svg class="sp-feature-arrow" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>
            
            <a href="<?php echo home_url('/app/appeals'); ?>" class="sp-feature-card">
                <div class="sp-feature-icon" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%); color: white;">
                    📋
                </div>
                <div class="sp-feature-content">
                    <h4 class="sp-feature-title"><?php _e('طلب نقاط فعالية', 'saint-porphyrius'); ?></h4>
                    <p class="sp-feature-desc"><?php _e('طلب نقاط فعالية لم أحصل عليها', 'saint-porphyrius'); ?></p>
                </div>
                <svg class="sp-feature-arrow" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>
        </div>
    </div>

    <!-- Upcoming Events Section -->
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title"><?php _e('الفعاليات القادمة', 'saint-porphyrius'); ?></h3>
            <a href="<?php echo home_url('/app/events'); ?>" class="sp-section-link"><?php _e('عرض الكل', 'saint-porphyrius'); ?></a>
        </div>
        
        <?php if (empty($upcoming_events)): ?>
            <div class="sp-card">
                <div class="sp-empty">
                    <div class="sp-empty-icon">📅</div>
                    <h4 class="sp-empty-title"><?php _e('لا توجد فعاليات قادمة', 'saint-porphyrius'); ?></h4>
                    <p class="sp-empty-text"><?php _e('سيتم إضافة فعاليات جديدة قريباً', 'saint-porphyrius'); ?></p>
                </div>
            </div>
        <?php else: ?>
            <div class="sp-events-list">
                <?php foreach ($upcoming_events as $event): 
                    $event_date = strtotime($event->event_date);
                    $is_today = date('Y-m-d') === $event->event_date;
                    $is_tomorrow = date('Y-m-d', strtotime('+1 day')) === $event->event_date;
                    $points_config = $events_handler->get_event_points($event);
                ?>
                    <a href="<?php echo home_url('/app/events/' . $event->id); ?>" class="sp-event-card" style="--event-color: <?php echo esc_attr($event->type_color); ?>;">
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
                            </div>
                            
                            <h3 class="sp-event-title"><?php echo esc_html($event->title_ar); ?></h3>
                            
                            <div class="sp-event-meta">
                                <span>
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php echo esc_html($event->start_time); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="sp-event-points">
                            <span class="sp-points-value">+<?php echo esc_html($points_config['attendance']); ?></span>
                            <span class="sp-points-label"><?php _e('نقطة', 'saint-porphyrius'); ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Logout Button -->
    <div class="sp-section">
        <button type="button" class="sp-btn sp-btn-secondary sp-btn-block" id="sp-logout-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <?php _e('تسجيل الخروج', 'saint-porphyrius'); ?>
        </button>
    </div>
</main>

<!-- Unified Bottom Navigation -->
<nav class="sp-unified-nav">
    <div class="sp-nav-inner">
        <a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-nav-item active">
            <div class="sp-nav-indicator"></div>
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

<script>
document.getElementById('sp-logout-btn')?.addEventListener('click', function() {
    if (confirm('<?php _e('هل أنت متأكد من تسجيل الخروج؟', 'saint-porphyrius'); ?>')) {
        window.location.href = '<?php echo home_url('/app/logout'); ?>';
    }
});

// Birthday Congratulations Gift Picker
(function() {
    var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
    var nonce = '<?php echo wp_create_nonce('sp_nonce'); ?>';

    document.querySelectorAll('.sp-birthday-member-card').forEach(function(card) {
        var userId = card.getAttribute('data-user-id');
        var picker = card.querySelector('.sp-birthday-gift-picker');
        if (!picker) return;

        var amountBtns = picker.querySelectorAll('.sp-birthday-amount-btn');
        var customWrap = picker.querySelector('.sp-birthday-custom-amount');
        var customInput = picker.querySelector('.sp-birthday-custom-input');
        var messageInput = picker.querySelector('.sp-birthday-message-input');
        var sendBtn = picker.querySelector('.sp-birthday-send-btn');
        var selectedAmount = 0;

        amountBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                amountBtns.forEach(function(b) { b.classList.remove('active'); });
                btn.classList.add('active');

                if (btn.getAttribute('data-amount') === 'other') {
                    customWrap.style.display = 'block';
                    customInput.focus();
                    selectedAmount = parseInt(customInput.value) || 0;
                } else {
                    customWrap.style.display = 'none';
                    selectedAmount = parseInt(btn.getAttribute('data-amount'));
                }
                sendBtn.disabled = (selectedAmount < 1);
            });
        });

        if (customInput) {
            customInput.addEventListener('input', function() {
                selectedAmount = parseInt(this.value) || 0;
                sendBtn.disabled = (selectedAmount < 1);
            });
        }

        sendBtn.addEventListener('click', function() {
            if (selectedAmount < 1 || sendBtn.disabled) return;
            sendBtn.disabled = true;
            sendBtn.textContent = '<?php _e('جاري الإرسال...', 'saint-porphyrius'); ?>';

            var formData = new FormData();
            formData.append('action', 'sp_send_birthday_congrats');
            formData.append('nonce', nonce);
            formData.append('recipient_id', userId);
            formData.append('points', selectedAmount);
            formData.append('message', messageInput ? messageInput.value : '');

            fetch(ajaxUrl, { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        picker.innerHTML = '<div class="sp-birthday-congrats-sent"><span>🎉</span><span>' + res.data.message + '</span></div>';
                    } else {
                        alert(res.data.message);
                        sendBtn.disabled = false;
                        sendBtn.textContent = '<?php _e('إرسال التهنئة 🎁', 'saint-porphyrius'); ?>';
                    }
                })
                .catch(function() {
                    alert('<?php _e('حدث خطأ، حاول مرة أخرى', 'saint-porphyrius'); ?>');
                    sendBtn.disabled = false;
                    sendBtn.textContent = '<?php _e('إرسال التهنئة 🎁', 'saint-porphyrius'); ?>';
                });
        });
    });
})();
</script>
