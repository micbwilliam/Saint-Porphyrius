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
            <div class="sp-hero-text">
                <h2><?php 
                    $display_name = trim($first_name . ' ' . $middle_name);
                    if ($is_female) {
                        printf(__('بنتنا الغالية، %s!', 'saint-porphyrius'), esc_html($display_name));
                    } else {
                        printf(__('ابننا الغالي، %s!', 'saint-porphyrius'), esc_html($display_name));
                    }
                ?></h2>
                <p><?php echo $is_female ? 'منورة أسرة برفوريوس 😇' : 'منور أسرة برفوريوس 😇'; ?></p>
            </div>
            <div class="sp-hero-stat">
                <span class="sp-hero-stat-value"><?php echo esc_html($user_points); ?></span>
                <span class="sp-hero-stat-label"><?php _e('نقطة', 'saint-porphyrius'); ?></span>
            </div>
        </div>
    </div>

    <?php // Birthday Celebration Card ?>
    <?php if ($birthday_info): ?>
    <div class="sp-birthday-card <?php echo $birthday_info['is_birthday'] ? 'is-birthday' : ''; ?>">
        <div class="sp-birthday-content">
            <div class="sp-birthday-emoji">🎂</div>
            <div class="sp-birthday-text">
                <h3><?php echo esc_html($birthday_info['message']); ?></h3>
                <?php if ($birthday_info['is_birthday'] && $gamification_settings['birthday_reward_enabled']): ?>
                <p class="sp-birthday-reward">🎁 <?php printf(__('حصلت على %d نقطة هدية عيد ميلادك!', 'saint-porphyrius'), $gamification_settings['birthday_points']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="sp-birthday-confetti"></div>
    </div>
    <?php endif; ?>

    <?php // Profile Completion Congratulation Card (shows once after completing profile) ?>
    <?php if ($show_profile_congratulation): ?>
    <div class="sp-profile-congrats-card">
        <div class="sp-profile-congrats-content">
            <div class="sp-profile-congrats-emoji">🏆</div>
            <div class="sp-profile-congrats-text">
                <h3><?php echo $is_female ? 'أحسنتِ!' : 'أحسنت!'; ?> <?php _e('ملفك الشخصي مكتمل', 'saint-porphyrius'); ?></h3>
                <p class="sp-profile-congrats-reward">🎁 <?php printf(__('حصلت على %d نقطة مكافأة إكمال الملف!', 'saint-porphyrius'), $gamification_settings['profile_completion_points']); ?></p>
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
                <h3><?php _e('أكمل ملفك الشخصي', 'saint-porphyrius'); ?></h3>
                <p><?php printf(__('أكمل بياناتك واحصل على %d نقطة!', 'saint-porphyrius'), $gamification_settings['profile_completion_points']); ?></p>
            </div>
        </div>
        <div class="sp-profile-completion-progress">
            <div class="sp-profile-completion-bar">
                <div class="sp-profile-completion-fill" style="width: <?php echo esc_attr($profile_completion['percentage']); ?>%;"></div>
            </div>
            <span class="sp-profile-completion-percent"><?php echo esc_html($profile_completion['percentage']); ?>%</span>
        </div>
        <a href="<?php echo home_url('/app/profile'); ?>" class="sp-btn sp-btn-outline sp-btn-sm sp-btn-block">
            <?php _e('إكمال الملف الشخصي', 'saint-porphyrius'); ?>
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
                    <p><?php _e('تمت مراجعتك لهذا الموضوع ✓', 'saint-porphyrius'); ?></p>
                <?php elseif ($service_instructions_count === 1 && $gamification_settings['service_instructions_enabled']): ?>
                    <p><?php printf(__('يمكنك إعادة الاختبار والحصول على %d نقطة إضافية', 'saint-porphyrius'), $gamification_settings['service_instructions_points']); ?></p>
                <?php elseif ($gamification_settings['service_instructions_enabled']): ?>
                    <p><?php printf(__('تعرّف على نظام الخدمة والنقاط واحصل على %d نقاط', 'saint-porphyrius'), $gamification_settings['service_instructions_points']); ?></p>
                <?php else: ?>
                    <p><?php _e('تمت مراجعتك لهذا الموضوع ✓', 'saint-porphyrius'); ?></p>
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
                <h3><?php _e('قصة حياة القديس الشهيد برفوريوس', 'saint-porphyrius'); ?></h3>
                <?php if (!$story_quiz_completed && $gamification_settings['story_quiz_enabled']): ?>
                    <p><?php printf(__('اكتشف قصة حياة شفيع أسرتنا واحصل على %d نقاط', 'saint-porphyrius'), $gamification_settings['story_quiz_points']); ?></p>
                <?php else: ?>
                    <p><?php _e('اطلعت على هذه القصة الملهمة ✓', 'saint-porphyrius'); ?></p>
                <?php endif; ?>
            </div>
            <a href="<?php echo home_url('/app/saint-story'); ?>" class="sp-btn sp-btn-primary sp-btn-sm">
                <?php echo $story_quiz_completed ? __('اقرأ مرة أخرى', 'saint-porphyrius') : __('ابدأ الآن', 'saint-porphyrius'); ?>
            </a>
        </div>
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
                <h3><?php _e('حسابك محظور', 'saint-porphyrius'); ?></h3>
                <p><?php _e('تم إيقاف حسابك بسبب تكرار الغياب. تواصل مع المسؤول لإعادة التفعيل.', 'saint-porphyrius'); ?></p>
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
                <span><?php printf(__('الغيابات: %d من %d', 'saint-porphyrius'), $discipline_status['consecutive_absences'], $discipline_status['max_absences']); ?></span>
                <span>
                    <?php if ($discipline_status['consecutive_absences'] == 0): ?>
                        <?php _e('لا يوجد غيابات 👏', 'saint-porphyrius'); ?>
                    <?php elseif ($discipline_status['consecutive_absences'] < $discipline_status['yellow_threshold']): ?>
                        <?php printf(__('%d متبقي للكارت الأصفر', 'saint-porphyrius'), $discipline_status['yellow_threshold'] - $discipline_status['consecutive_absences']); ?>
                    <?php elseif ($discipline_status['consecutive_absences'] < $discipline_status['max_absences']): ?>
                        <?php printf(__('%d متبقي للكارت الأحمر', 'saint-porphyrius'), $discipline_status['max_absences'] - $discipline_status['consecutive_absences']); ?>
                    <?php endif; ?>
                </span>
            </div>
            
            <?php if ($discipline_status['forbidden_remaining'] > 0): ?>
            <div class="sp-forbidden-status">
                <span>⛔</span>
                <span><?php printf(__('أنت محروم من %d فعاليات قادمة', 'saint-porphyrius'), $discipline_status['forbidden_remaining']); ?></span>
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
</script>
