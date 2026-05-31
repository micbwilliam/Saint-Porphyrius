<?php
/**
 * Saint Porphyrius - Social Profile Page
 * Public social profile view for community members
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$social_handler = SP_Social_Profile::get_instance();

// Check if feature is enabled
if (!$social_handler->is_enabled()) {
    wp_safe_redirect(home_url('/app/community'));
    exit;
}

$settings = $social_handler->get_settings();

// Get target user ID from URL
$profile_user_id = isset($_GET['id']) ? absint($_GET['id']) : $current_user->ID;

// Verify user exists and is a member
$profile_user = get_user_by('id', $profile_user_id);
if (!$profile_user || (!in_array('sp_member', $profile_user->roles) && !in_array('sp_church_admin', $profile_user->roles) && !in_array('administrator', $profile_user->roles))) {
    wp_safe_redirect(home_url('/app/community'));
    exit;
}

// Get full profile data
$profile = $social_handler->get_full_profile($profile_user_id, $current_user->ID);
if (is_wp_error($profile)) {
    wp_safe_redirect(home_url('/app/community'));
    exit;
}

$is_own = $profile['is_own_profile'];
$gender = $profile['gender'];
$is_female = ($gender === 'female');

// Upcoming registrations
$upcoming = $social_handler->get_upcoming_registrations($profile_user_id, 5);
?>

<!-- Unified Header -->
<div class="sp-unified-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/community'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php echo esc_html($profile['full_name']); ?></h1>
        <div class="sp-header-actions">
            <?php if ($is_own): ?>
            <button type="button" class="sp-header-action" id="sp-social-settings-btn" title="إعدادات">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Main Content -->
<main class="sp-page-content has-bottom-nav">
    <!-- Cover & Profile Image Section -->
    <div class="sp-social-hero">
        <div class="sp-social-cover<?php echo !empty($profile['cover_image']) ? ' sp-zoomable' : ''; ?>" id="sp-social-cover"
             style="<?php echo !empty($profile['cover_image']) ? 'background-image: url(' . esc_url($profile['cover_image']) . ');' : ''; ?>">
            <?php if ($is_own && $settings['allow_cover_upload']): ?>
            <label class="sp-social-cover-edit" for="sp-cover-upload">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                    <circle cx="12" cy="13" r="4"></circle>
                </svg>
            </label>
            <input type="file" id="sp-cover-upload" accept="image/jpeg,image/png,image/webp" style="display:none;">
            <?php endif; ?>
        </div>
        
        <div class="sp-social-avatar-wrapper">
            <div class="sp-social-avatar<?php echo !empty($profile['profile_image']) ? ' sp-zoomable' : ''; ?>" id="sp-social-avatar">
                <?php if (!empty($profile['profile_image'])): ?>
                    <img src="<?php echo esc_url($profile['profile_image']); ?>" alt="<?php echo esc_attr($profile['full_name']); ?>">
                <?php else: ?>
                    <span class="sp-social-avatar-letter"><?php echo esc_html(mb_substr($profile['first_name'], 0, 1)); ?></span>
                <?php endif; ?>
                <?php if ($is_own && $settings['allow_profile_upload']): ?>
                <label class="sp-social-avatar-edit" for="sp-avatar-upload">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                        <circle cx="12" cy="13" r="4"></circle>
                    </svg>
                </label>
                <input type="file" id="sp-avatar-upload" accept="image/jpeg,image/png,image/webp" style="display:none;">
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Name & Basic Info -->
        <div class="sp-social-name-section">
            <h2 class="sp-social-name">
                <?php echo esc_html($profile['full_name']); ?>
                <span class="sp-social-gender-icon"><?php echo $is_female ? '👩' : '👨'; ?></span>
            </h2>
            <?php if ($profile['church']): ?>
                <p class="sp-social-church">⛪ <?php echo esc_html($profile['church']); ?></p>
            <?php endif; ?>
            <p class="sp-social-joined">
                <?php printf(__('عضو منذ %s', 'saint-porphyrius'), date_i18n('F Y', strtotime($profile['join_date']))); ?>
            </p>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="sp-social-stats-row">
        <div class="sp-social-stat-card gold">
            <div class="sp-social-stat-icon">⭐</div>
            <div class="sp-social-stat-value"><?php echo esc_html($profile['points']); ?></div>
            <div class="sp-social-stat-label"><?php _e('نقطة', 'saint-porphyrius'); ?></div>
        </div>
        <div class="sp-social-stat-card blue">
            <div class="sp-social-stat-icon">🏆</div>
            <div class="sp-social-stat-value">#<?php echo esc_html($profile['rank'] ?: '-'); ?></div>
            <div class="sp-social-stat-label"><?php _e('الترتيب', 'saint-porphyrius'); ?></div>
        </div>
        <?php if ($settings['show_attendance'] && isset($profile['attendance'])): ?>
        <div class="sp-social-stat-card green">
            <div class="sp-social-stat-icon">✅</div>
            <div class="sp-social-stat-value"><?php echo esc_html($profile['attendance']['attended']); ?></div>
            <div class="sp-social-stat-label"><?php _e('حضور', 'saint-porphyrius'); ?></div>
        </div>
        <div class="sp-social-stat-card purple">
            <div class="sp-social-stat-icon">📊</div>
            <div class="sp-social-stat-value"><?php echo esc_html(round($profile['attendance']['attendance_rate'])); ?>%</div>
            <div class="sp-social-stat-label"><?php _e('نسبة الحضور', 'saint-porphyrius'); ?></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Discipline Status -->
    <?php if ($settings['show_discipline'] && isset($profile['discipline'])): ?>
    <?php
        $disc = $profile['discipline'];
        $disc_class = 'good';
        if ($disc['is_blocked']) $disc_class = 'blocked';
        elseif ($disc['card_status'] === 'red') $disc_class = 'danger';
        elseif ($disc['card_status'] === 'yellow') $disc_class = 'warning';
        elseif ($disc['consecutive_absences'] > 0) $disc_class = 'caution';
    ?>
    <div class="sp-social-section">
        <div class="sp-social-discipline <?php echo esc_attr($disc_class); ?>">
            <div class="sp-social-discipline-header">
                <?php if ($disc['is_blocked']): ?>
                    <span>🔴 <?php _e('محروم', 'saint-porphyrius'); ?></span>
                <?php elseif ($disc['card_status'] === 'red'): ?>
                    <span>🔴 <?php _e('كارت أحمر', 'saint-porphyrius'); ?></span>
                <?php elseif ($disc['card_status'] === 'yellow'): ?>
                    <span>🟡 <?php _e('كارت أصفر', 'saint-porphyrius'); ?></span>
                <?php elseif ($disc['consecutive_absences'] == 0): ?>
                    <span>✨ <?php _e('منتظم', 'saint-porphyrius'); ?></span>
                <?php else: ?>
                    <span>📊 <?php printf(__('غيابات متتالية: %d', 'saint-porphyrius'), $disc['consecutive_absences']); ?></span>
                <?php endif; ?>
            </div>
            <?php if ($disc['forbidden_remaining'] > 0): ?>
                <div class="sp-social-discipline-detail">
                    ⛔ <?php printf(__('محروم من %d فعاليات', 'saint-porphyrius'), $disc['forbidden_remaining']); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Achievements Badges -->
    <div class="sp-social-section">
        <h3 class="sp-social-section-title">🏅 <?php _e('الإنجازات', 'saint-porphyrius'); ?></h3>
        <div class="sp-social-badges">
            <div class="sp-social-badge <?php echo $profile['achievements']['profile_complete'] ? 'earned' : 'locked'; ?>">
                <span class="sp-badge-icon">📝</span>
                <span class="sp-badge-label"><?php _e('ملف مكتمل', 'saint-porphyrius'); ?></span>
            </div>
            <div class="sp-social-badge <?php echo $profile['achievements']['story_quiz_completed'] ? 'earned' : 'locked'; ?>">
                <span class="sp-badge-icon">📖</span>
                <span class="sp-badge-label"><?php _e('قصة القديس', 'saint-porphyrius'); ?></span>
            </div>
            <div class="sp-social-badge <?php echo $profile['achievements']['service_quiz_completed'] ? 'earned' : 'locked'; ?>">
                <span class="sp-badge-icon">⚙️</span>
                <span class="sp-badge-label"><?php _e('تعليمات الخدمة', 'saint-porphyrius'); ?></span>
            </div>
            <?php if ($settings['show_attendance'] && isset($profile['attendance']) && $profile['attendance']['attended'] >= 10): ?>
            <div class="sp-social-badge earned">
                <span class="sp-badge-icon">🔥</span>
                <span class="sp-badge-label"><?php _e('10+ حضور', 'saint-porphyrius'); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($profile['points'] >= 100): ?>
            <div class="sp-social-badge earned">
                <span class="sp-badge-icon">💯</span>
                <span class="sp-badge-label"><?php _e('100+ نقطة', 'saint-porphyrius'); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($profile['rank'] > 0 && $profile['rank'] <= 3): ?>
            <div class="sp-social-badge earned">
                <span class="sp-badge-icon"><?php echo $profile['rank'] === 1 ? '🥇' : ($profile['rank'] === 2 ? '🥈' : '🥉'); ?></span>
                <span class="sp-badge-label"><?php _e('متصدر', 'saint-porphyrius'); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Attendance Breakdown -->
    <?php if ($settings['show_attendance'] && isset($profile['attendance']) && $profile['attendance']['total'] > 0): ?>
    <div class="sp-social-section">
        <h3 class="sp-social-section-title">📊 <?php _e('إحصائيات الحضور', 'saint-porphyrius'); ?></h3>
        <div class="sp-social-attendance-grid">
            <div class="sp-social-att-item green">
                <span class="sp-att-count"><?php echo esc_html($profile['attendance']['attended']); ?></span>
                <span class="sp-att-label">✅ <?php _e('حاضر', 'saint-porphyrius'); ?></span>
            </div>
            <div class="sp-social-att-item red">
                <span class="sp-att-count"><?php echo esc_html($profile['attendance']['absent']); ?></span>
                <span class="sp-att-label">❌ <?php _e('غائب', 'saint-porphyrius'); ?></span>
            </div>
            <div class="sp-social-att-item yellow">
                <span class="sp-att-count"><?php echo esc_html($profile['attendance']['late']); ?></span>
                <span class="sp-att-label">⏰ <?php _e('متأخر', 'saint-porphyrius'); ?></span>
            </div>
            <div class="sp-social-att-item gray">
                <span class="sp-att-count"><?php echo esc_html($profile['attendance']['excused']); ?></span>
                <span class="sp-att-label">📋 <?php _e('معذور', 'saint-porphyrius'); ?></span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Upcoming Events (registered) -->
    <?php if (!empty($upcoming)): ?>
    <div class="sp-social-section">
        <h3 class="sp-social-section-title">📅 <?php _e('الفعاليات القادمة', 'saint-porphyrius'); ?></h3>
        <div class="sp-social-upcoming-list">
            <?php foreach ($upcoming as $evt): ?>
            <a href="<?php echo home_url('/app/events/' . $evt->event_id); ?>" class="sp-social-upcoming-card">
                <div class="sp-social-upcoming-icon" style="color: <?php echo esc_attr($evt->type_color); ?>">
                    <?php echo esc_html($evt->type_icon); ?>
                </div>
                <div class="sp-social-upcoming-info">
                    <strong><?php echo esc_html($evt->title_ar); ?></strong>
                    <span><?php echo esc_html($evt->type_name); ?> • <?php echo esc_html(date_i18n('j M', strtotime($evt->event_date))); ?></span>
                </div>
                <div class="sp-social-upcoming-badge">✅ <?php _e('مسجّل', 'saint-porphyrius'); ?></div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Events Activity -->
    <?php if ($settings['show_events'] && !empty($profile['recent_events'])): ?>
    <div class="sp-social-section">
        <h3 class="sp-social-section-title">📅 <?php _e('آخر الفعاليات', 'saint-porphyrius'); ?></h3>
        <div class="sp-social-events-list">
            <?php foreach ($profile['recent_events'] as $evt): 
                $status_icon = '❓';
                $status_class = '';
                switch ($evt->status) {
                    case 'present': case 'attended': $status_icon = '✅'; $status_class = 'attended'; break;
                    case 'absent':    $status_icon = '❌'; $status_class = 'absent'; break;
                    case 'late':      $status_icon = '⏰'; $status_class = 'late'; break;
                    case 'excused':   $status_icon = '📋'; $status_class = 'excused'; break;
                    case 'forbidden': $status_icon = '⛔'; $status_class = 'forbidden'; break;
                }
            ?>
            <div class="sp-social-event-item <?php echo esc_attr($status_class); ?>">
                <div class="sp-social-event-icon" style="color: <?php echo esc_attr($evt->type_color); ?>">
                    <?php echo esc_html($evt->type_icon); ?>
                </div>
                <div class="sp-social-event-info">
                    <strong><?php echo esc_html($evt->title_ar); ?></strong>
                    <span><?php echo esc_html(date_i18n('j M Y', strtotime($evt->event_date))); ?></span>
                </div>
                <div class="sp-social-event-status">
                    <span class="sp-social-event-status-icon"><?php echo $status_icon; ?></span>
                    <?php if ($evt->points_awarded != 0): ?>
                    <span class="sp-social-event-points <?php echo $evt->points_awarded > 0 ? 'positive' : 'negative'; ?>">
                        <?php echo ($evt->points_awarded > 0 ? '+' : '') . esc_html($evt->points_awarded); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bus Activity -->
    <?php if ($settings['show_bus_info'] && !empty($profile['bus_activity'])): ?>
    <div class="sp-social-section">
        <h3 class="sp-social-section-title">🚌 <?php _e('حجوزات الباص', 'saint-porphyrius'); ?></h3>
        <div class="sp-social-bus-list">
            <?php foreach ($profile['bus_activity'] as $bus): ?>
            <div class="sp-social-bus-item">
                <div class="sp-social-bus-icon">🚌</div>
                <div class="sp-social-bus-info">
                    <strong><?php echo esc_html($bus->event_title); ?></strong>
                    <span>
                        <?php echo esc_html($bus->bus_name); ?> 
                        • <?php _e('مقعد', 'saint-porphyrius'); ?> <?php echo esc_html($bus->seat_label); ?>
                        • <?php echo esc_html(date_i18n('j M', strtotime($bus->event_date))); ?>
                    </span>
                </div>
                <div class="sp-social-bus-status">
                    <?php if ($bus->booking_status === 'checked_in'): ?>
                        <span class="sp-social-bus-badge checked">✅</span>
                    <?php else: ?>
                        <span class="sp-social-bus-badge booked">🎫</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quiz Stats -->
    <?php if ($settings['show_quiz_stats'] && isset($profile['quiz_stats']['summary']) && $profile['quiz_stats']['summary']->total_attempts > 0): ?>
    <div class="sp-social-section">
        <h3 class="sp-social-section-title">📝 <?php _e('المسابقات', 'saint-porphyrius'); ?></h3>
        <div class="sp-social-quiz-stats">
            <div class="sp-social-quiz-stat-row">
                <div class="sp-social-quiz-stat">
                    <span class="sp-qstat-value"><?php echo esc_html($profile['quiz_stats']['summary']->quizzes_taken); ?></span>
                    <span class="sp-qstat-label"><?php _e('اختبارات', 'saint-porphyrius'); ?></span>
                </div>
                <div class="sp-social-quiz-stat">
                    <span class="sp-qstat-value"><?php echo esc_html(round($profile['quiz_stats']['summary']->avg_score)); ?>%</span>
                    <span class="sp-qstat-label"><?php _e('متوسط النتيجة', 'saint-porphyrius'); ?></span>
                </div>
                <div class="sp-social-quiz-stat">
                    <span class="sp-qstat-value"><?php echo esc_html((int)$profile['quiz_stats']['summary']->total_points); ?></span>
                    <span class="sp-qstat-label"><?php _e('نقاط المسابقات', 'saint-porphyrius'); ?></span>
                </div>
            </div>
            
            <?php if (!empty($profile['quiz_stats']['recent'])): ?>
            <div class="sp-social-quiz-recent">
                <?php foreach ($profile['quiz_stats']['recent'] as $attempt): ?>
                <div class="sp-social-quiz-attempt">
                    <div class="sp-social-quiz-attempt-info">
                        <strong><?php echo esc_html($attempt->quiz_title); ?></strong>
                        <span><?php echo esc_html(date_i18n('j M Y', strtotime($attempt->completed_at))); ?></span>
                    </div>
                    <div class="sp-social-quiz-attempt-score">
                        <span class="sp-qscore"><?php echo esc_html(round($attempt->percentage)); ?>%</span>
                        <?php if ($attempt->points_awarded > 0): ?>
                        <span class="sp-qpoints">+<?php echo esc_html($attempt->points_awarded); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Points Timeline (Social Posts) -->
    <?php if ($settings['show_points_history'] && !empty($profile['points_timeline'])): ?>
    <div class="sp-social-section">
        <h3 class="sp-social-section-title">📜 <?php _e('سجل النشاط', 'saint-porphyrius'); ?></h3>
        <div class="sp-social-timeline">
            <?php 
            $prev_date = '';
            foreach ($profile['points_timeline'] as $item): 
                $item_date = date_i18n('j M Y', strtotime($item['created_at']));
                $show_date = ($item_date !== $prev_date);
                $prev_date = $item_date;
            ?>
                <?php if ($show_date): ?>
                <div class="sp-timeline-date"><?php echo esc_html($item_date); ?></div>
                <?php endif; ?>
                
                <div class="sp-timeline-post">
                    <div class="sp-timeline-post-icon" style="background: <?php echo esc_attr($item['type_color']); ?>20; color: <?php echo esc_attr($item['type_color']); ?>;">
                        <?php echo $item['icon']; ?>
                    </div>
                    <div class="sp-timeline-post-content">
                        <p class="sp-timeline-post-message"><?php echo esc_html($item['message']); ?></p>
                        <div class="sp-timeline-post-meta">
                            <span class="sp-timeline-post-time">
                                <?php echo esc_html(date_i18n('g:i A', strtotime($item['created_at']))); ?>
                            </span>
                            <span class="sp-timeline-post-points <?php echo $item['points'] >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo ($item['points'] >= 0 ? '+' : '') . esc_html($item['points']); ?> <?php _e('نقطة', 'saint-porphyrius'); ?>
                            </span>
                            <span class="sp-timeline-post-balance">
                                <?php _e('الرصيد:', 'saint-porphyrius'); ?> <?php echo esc_html($item['balance_after']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Social Links -->
    <?php if (!empty($profile['social']['facebook']) || !empty($profile['social']['instagram'])): ?>
    <div class="sp-social-section">
        <h3 class="sp-social-section-title">🔗 <?php _e('روابط التواصل', 'saint-porphyrius'); ?></h3>
        <div class="sp-social-links">
            <?php if (!empty($profile['social']['facebook'])): ?>
            <a href="<?php echo esc_url($profile['social']['facebook']); ?>" class="sp-social-link facebook" target="_blank" rel="noopener">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
                Facebook
            </a>
            <?php endif; ?>
            <?php if (!empty($profile['social']['instagram'])): ?>
            <a href="<?php echo esc_url($profile['social']['instagram']); ?>" class="sp-social-link instagram" target="_blank" rel="noopener">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                </svg>
                Instagram
            </a>
            <?php endif; ?>
        </div>
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

<!-- Loading overlay for image uploads -->
<div class="sp-social-upload-overlay" id="sp-upload-overlay" style="display: none;">
    <div class="sp-social-upload-spinner">
        <div class="sp-spinner"></div>
        <p><?php _e('جاري رفع الصورة...', 'saint-porphyrius'); ?></p>
    </div>
</div>

<!-- Image enlarge lightbox -->
<div class="sp-img-lightbox" id="sp-img-lightbox">
    <button type="button" class="sp-img-lightbox-close" id="sp-img-lightbox-close" aria-label="<?php esc_attr_e('إغلاق', 'saint-porphyrius'); ?>">&times;</button>
    <img id="sp-img-lightbox-img" src="" alt="<?php echo esc_attr($profile['full_name']); ?>">
</div>

<script>
(function($) {
    'use strict';
    
    // Image upload handler
    function handleImageUpload(inputEl, type) {
        var file = inputEl.files[0];
        if (!file) return;
        
        var maxSize = (type === 'profile') ? 2 * 1024 * 1024 : 5 * 1024 * 1024;
        if (file.size > maxSize) {
            alert(type === 'profile' ? 'حجم الصورة أكبر من 2 ميجابايت' : 'حجم الصورة أكبر من 5 ميجابايت');
            return;
        }
        
        var formData = new FormData();
        formData.append('action', 'sp_social_upload_image');
        formData.append('nonce', spApp.nonce);
        formData.append('image_type', type);
        formData.append('image', file);
        
        $('#sp-upload-overlay').show();
        
        $.ajax({
            url: spApp.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#sp-upload-overlay').hide();
                if (response.success) {
                    // Update the displayed image
                    if (type === 'cover') {
                        $('#sp-social-cover').css('background-image', 'url(' + response.data.url + ')').addClass('sp-zoomable');
                    } else {
                        var avatarEl = $('#sp-social-avatar');
                        avatarEl.find('img').remove();
                        avatarEl.find('.sp-social-avatar-letter').remove();
                        avatarEl.prepend('<img src="' + response.data.url + '" alt="Profile">');
                        avatarEl.addClass('sp-zoomable');
                    }
                } else {
                    alert(response.data.message || 'حدث خطأ');
                }
            },
            error: function() {
                $('#sp-upload-overlay').hide();
                alert('حدث خطأ في رفع الصورة');
            }
        });
    }
    
    // Bind upload events
    $('#sp-cover-upload').on('change', function() {
        handleImageUpload(this, 'cover');
    });

    $('#sp-avatar-upload').on('change', function() {
        handleImageUpload(this, 'profile');
    });

    // ---- Enlarge cover / profile photo on click ----
    var $lightbox = $('#sp-img-lightbox');
    var $lightboxImg = $('#sp-img-lightbox-img');

    function openLightbox(url) {
        if (!url) return;
        $lightboxImg.attr('src', url);
        $lightbox.addClass('active');
        $('body').css('overflow', 'hidden');
    }

    function closeLightbox() {
        $lightbox.removeClass('active');
        $lightboxImg.attr('src', '');
        $('body').css('overflow', '');
    }

    // Cover: read its current background-image so it stays correct after uploads
    $('#sp-social-cover').on('click', function(e) {
        if ($(e.target).closest('.sp-social-cover-edit').length) return; // editing, not viewing
        var bg = this.style.backgroundImage;
        var match = bg && bg.match(/url\(["']?(.*?)["']?\)/);
        if (match && match[1]) openLightbox(match[1]);
    });

    // Avatar: only enlarge when a real photo is present (not the letter fallback)
    $('#sp-social-avatar').on('click', function(e) {
        if ($(e.target).closest('.sp-social-avatar-edit').length) return; // editing, not viewing
        var img = this.querySelector('img');
        if (img && img.src) openLightbox(img.src);
    });

    $('#sp-img-lightbox-close').on('click', closeLightbox);
    $lightbox.on('click', function(e) {
        if (e.target === this) closeLightbox();
    });
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $lightbox.hasClass('active')) closeLightbox();
    });

})(jQuery);
</script>
