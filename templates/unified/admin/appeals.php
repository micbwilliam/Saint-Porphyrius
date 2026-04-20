<?php
/**
 * Saint Porphyrius - Admin Appeals (Mobile)
 * Review and process point appeal requests
 */

if (!defined('ABSPATH')) {
    exit;
}

$appeals_handler = SP_Appeals::get_instance();
$message = '';
$message_type = '';

// Handle actions
if (isset($_GET['action']) && isset($_GET['appeal_id']) && isset($_GET['_wpnonce'])) {
    if (wp_verify_nonce($_GET['_wpnonce'], 'sp_appeal_action')) {
        $action = sanitize_text_field($_GET['action']);
        $appeal_id = absint($_GET['appeal_id']);
        $admin_notes = sanitize_textarea_field($_GET['notes'] ?? '');
        
        $valid_actions = array('full', 'partial_80', 'partial_50', 'denied', 'denied_penalty');
        if (in_array($action, $valid_actions, true)) {
            $result = $appeals_handler->process($appeal_id, $action, get_current_user_id(), $admin_notes);
            if (!empty($result['success'])) {
                $message = $result['message'];
                $message_type = 'success';
            } else {
                $message = $result['message'] ?? __('حدث خطأ', 'saint-porphyrius');
                $message_type = 'error';
            }
        }
    }
}

// Get filter
$filter = isset($_GET['filter']) ? sanitize_text_field($_GET['filter']) : 'pending';

// Get appeals
$args = array('limit' => 50);
if ($filter !== 'all') {
    $status_map = array(
        'pending' => 'pending',
        'approved' => null, // special: covers full, partial_80, partial_50
        'denied' => null,   // special: covers denied, denied_penalty
    );
    
    if ($filter === 'approved') {
        // Get approved appeals (full, partial_80, partial_50)
        global $wpdb;
        $table = $wpdb->prefix . 'sp_appeals';
        $events_table = $wpdb->prefix . 'sp_events';
        $types_table = $wpdb->prefix . 'sp_event_types';
        $appeals = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, e.title_ar as event_title, e.event_date, e.start_time,
                    et.name_ar as type_name_ar, et.icon as type_icon, et.color as type_color,
                    et.attendance_points as type_attendance_points,
                    e.attendance_points as event_attendance_points
             FROM $table a
             LEFT JOIN $events_table e ON a.event_id = e.id
             LEFT JOIN $types_table et ON e.event_type_id = et.id
             WHERE a.status IN ('full', 'partial_80', 'partial_50')
             ORDER BY a.created_at DESC
             LIMIT %d",
            50
        ));
    } elseif ($filter === 'denied') {
        global $wpdb;
        $table = $wpdb->prefix . 'sp_appeals';
        $events_table = $wpdb->prefix . 'sp_events';
        $types_table = $wpdb->prefix . 'sp_event_types';
        $appeals = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, e.title_ar as event_title, e.event_date, e.start_time,
                    et.name_ar as type_name_ar, et.icon as type_icon, et.color as type_color,
                    et.attendance_points as type_attendance_points,
                    e.attendance_points as event_attendance_points
             FROM $table a
             LEFT JOIN $events_table e ON a.event_id = e.id
             LEFT JOIN $types_table et ON e.event_type_id = et.id
             WHERE a.status IN ('denied', 'denied_penalty')
             ORDER BY a.created_at DESC
             LIMIT %d",
            50
        ));
    } else {
        $args['status'] = $filter;
        $appeals = $appeals_handler->get_all($args);
    }
} else {
    $appeals = $appeals_handler->get_all($args);
}

$pending_count = $appeals_handler->count_pending();
?>

<!-- Admin Header -->
<div class="sp-unified-header sp-admin-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/admin'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('طلبات نقاط الفعاليات', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-spacer"></div>
    </div>
</div>

<!-- Main Content -->
<main class="sp-page-content sp-admin-content">
    <?php if ($message): ?>
        <div class="sp-alert sp-alert-<?php echo esc_attr($message_type); ?>">
            <?php echo esc_html($message); ?>
        </div>
    <?php endif; ?>

    <!-- Filter Tabs -->
    <div class="sp-filter-tabs">
        <a href="<?php echo home_url('/app/admin/appeals?filter=pending'); ?>" 
           class="sp-filter-tab <?php echo $filter === 'pending' ? 'active' : ''; ?>">
            <?php _e('معلق', 'saint-porphyrius'); ?>
            <?php if ($pending_count > 0): ?>
                <span class="sp-filter-count"><?php echo esc_html($pending_count); ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo home_url('/app/admin/appeals?filter=approved'); ?>" 
           class="sp-filter-tab <?php echo $filter === 'approved' ? 'active' : ''; ?>">
            <?php _e('مقبول', 'saint-porphyrius'); ?>
        </a>
        <a href="<?php echo home_url('/app/admin/appeals?filter=denied'); ?>" 
           class="sp-filter-tab <?php echo $filter === 'denied' ? 'active' : ''; ?>">
            <?php _e('مرفوض', 'saint-porphyrius'); ?>
        </a>
        <a href="<?php echo home_url('/app/admin/appeals?filter=all'); ?>" 
           class="sp-filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
            <?php _e('الكل', 'saint-porphyrius'); ?>
        </a>
    </div>

    <?php if (empty($appeals)): ?>
        <div class="sp-empty-state">
            <div class="sp-empty-icon">📋</div>
            <h3><?php _e('لا توجد طلبات', 'saint-porphyrius'); ?></h3>
            <p><?php _e('لم يتم تقديم أي طلبات بعد', 'saint-porphyrius'); ?></p>
        </div>
    <?php else: ?>
        <div class="sp-appeals-admin-list">
            <?php foreach ($appeals as $appeal): 
                $user = get_user_by('id', $appeal->user_id);
                $fn = $user ? $user->first_name : '';
                $mn = $user ? get_user_meta($user->ID, 'sp_middle_name', true) : '';
                $full_name = $user ? (trim($fn . ' ' . $mn) ?: $user->display_name) : __('مستخدم محذوف', 'saint-porphyrius');
                
                $status_label = SP_Appeals::get_status_label($appeal->status);
                $status_color = SP_Appeals::get_status_color($appeal->status);
                
                // Calculate full points for this event
                $full_points = $appeal->event_attendance_points !== null 
                    ? (int) $appeal->event_attendance_points 
                    : (int) ($appeal->type_attendance_points ?? 0);
            ?>
                <div class="sp-appeal-admin-card">
                    <div class="sp-appeal-admin-header">
                        <div class="sp-appeal-user">
                            <div class="sp-appeal-avatar">
                                <?php echo sp_render_avatar($appeal->user_id, mb_substr($full_name, 0, 1)); ?>
                            </div>
                            <div class="sp-appeal-user-info">
                                <h4><?php echo esc_html($full_name); ?></h4>
                                <span class="sp-appeal-date"><?php echo esc_html(date_i18n('j M Y - g:i a', strtotime($appeal->created_at))); ?></span>
                            </div>
                        </div>
                        <span class="sp-appeal-status-badge" style="background: <?php echo esc_attr($status_color); ?>20; color: <?php echo esc_attr($status_color); ?>;">
                            <?php echo esc_html($status_label); ?>
                        </span>
                    </div>
                    
                    <div class="sp-appeal-admin-event">
                        <div class="sp-appeal-event-icon" style="background: <?php echo esc_attr($appeal->type_color ?? '#6B7280'); ?>20; color: <?php echo esc_attr($appeal->type_color ?? '#6B7280'); ?>;">
                            <?php echo esc_html($appeal->type_icon ?? '📅'); ?>
                        </div>
                        <div class="sp-appeal-event-details">
                            <h5><?php echo esc_html($appeal->event_title ?? __('فعالية محذوفة', 'saint-porphyrius')); ?></h5>
                            <span><?php echo esc_html(date_i18n('j F Y', strtotime($appeal->event_date ?? $appeal->created_at))); ?></span>
                        </div>
                        <div class="sp-appeal-event-points-info">
                            <span class="sp-appeal-full-points">+<?php echo esc_html($full_points); ?></span>
                            <span class="sp-appeal-points-sublabel"><?php _e('نقطة كاملة', 'saint-porphyrius'); ?></span>
                        </div>
                    </div>
                    
                    <div class="sp-appeal-admin-reason">
                        <strong><?php _e('سبب الطلب:', 'saint-porphyrius'); ?></strong>
                        <p><?php echo esc_html($appeal->reason); ?></p>
                    </div>
                    
                    <?php if ($appeal->status === 'pending'): ?>
                    <div class="sp-appeal-admin-actions">
                        <div class="sp-appeal-actions-label"><?php _e('اتخاذ قرار:', 'saint-porphyrius'); ?></div>
                        <div class="sp-appeal-actions-grid">
                            <a href="<?php echo wp_nonce_url(home_url('/app/admin/appeals?action=full&appeal_id=' . $appeal->id . '&filter=' . $filter), 'sp_appeal_action'); ?>" 
                               class="sp-appeal-action-btn sp-appeal-action-full"
                               onclick="return confirm('<?php printf(esc_attr__('منح %d نقطة كاملة؟', 'saint-porphyrius'), $full_points); ?>');">
                                <span class="sp-appeal-action-icon">✅</span>
                                <span class="sp-appeal-action-label"><?php _e('نقاط كاملة', 'saint-porphyrius'); ?></span>
                                <span class="sp-appeal-action-points">+<?php echo esc_html($full_points); ?></span>
                            </a>
                            
                            <a href="<?php echo wp_nonce_url(home_url('/app/admin/appeals?action=partial_80&appeal_id=' . $appeal->id . '&filter=' . $filter), 'sp_appeal_action'); ?>" 
                               class="sp-appeal-action-btn sp-appeal-action-80"
                               onclick="return confirm('<?php printf(esc_attr__('منح %d نقطة (80%%)؟', 'saint-porphyrius'), round($full_points * 0.8)); ?>');">
                                <span class="sp-appeal-action-icon">👍</span>
                                <span class="sp-appeal-action-label">80%</span>
                                <span class="sp-appeal-action-points">+<?php echo esc_html(round($full_points * 0.8)); ?></span>
                            </a>
                            
                            <a href="<?php echo wp_nonce_url(home_url('/app/admin/appeals?action=partial_50&appeal_id=' . $appeal->id . '&filter=' . $filter), 'sp_appeal_action'); ?>" 
                               class="sp-appeal-action-btn sp-appeal-action-50"
                               onclick="return confirm('<?php printf(esc_attr__('منح %d نقطة (50%%)؟', 'saint-porphyrius'), round($full_points * 0.5)); ?>');">
                                <span class="sp-appeal-action-icon">🤏</span>
                                <span class="sp-appeal-action-label">50%</span>
                                <span class="sp-appeal-action-points">+<?php echo esc_html(round($full_points * 0.5)); ?></span>
                            </a>
                            
                            <a href="<?php echo wp_nonce_url(home_url('/app/admin/appeals?action=denied&appeal_id=' . $appeal->id . '&filter=' . $filter), 'sp_appeal_action'); ?>" 
                               class="sp-appeal-action-btn sp-appeal-action-deny"
                               onclick="return confirm('<?php _e('رفض الطلب بدون خصم؟', 'saint-porphyrius'); ?>');">
                                <span class="sp-appeal-action-icon">❌</span>
                                <span class="sp-appeal-action-label"><?php _e('رفض', 'saint-porphyrius'); ?></span>
                                <span class="sp-appeal-action-points">0</span>
                            </a>
                            
                            <a href="<?php echo wp_nonce_url(home_url('/app/admin/appeals?action=denied_penalty&appeal_id=' . $appeal->id . '&filter=' . $filter), 'sp_appeal_action'); ?>" 
                               class="sp-appeal-action-btn sp-appeal-action-penalty"
                               onclick="return confirm('<?php _e('رفض الطلب مع خصم 5 نقاط؟', 'saint-porphyrius'); ?>');">
                                <span class="sp-appeal-action-icon">⛔</span>
                                <span class="sp-appeal-action-label"><?php _e('رفض + خصم', 'saint-porphyrius'); ?></span>
                                <span class="sp-appeal-action-points">-5</span>
                            </a>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="sp-appeal-admin-result">
                        <div class="sp-appeal-result-row">
                            <?php if ($appeal->points_awarded > 0): ?>
                                <span class="sp-appeal-result-badge positive">+<?php echo esc_html($appeal->points_awarded); ?> <?php _e('نقطة', 'saint-porphyrius'); ?></span>
                            <?php elseif ($appeal->points_awarded < 0): ?>
                                <span class="sp-appeal-result-badge negative"><?php echo esc_html($appeal->points_awarded); ?> <?php _e('نقطة', 'saint-porphyrius'); ?></span>
                            <?php else: ?>
                                <span class="sp-appeal-result-badge neutral"><?php _e('بدون نقاط', 'saint-porphyrius'); ?></span>
                            <?php endif; ?>
                            
                            <?php if ($appeal->reviewed_at): 
                                $reviewer = get_user_by('id', $appeal->admin_id);
                                $reviewer_name = $reviewer ? $reviewer->display_name : __('مسؤول', 'saint-porphyrius');
                            ?>
                                <span class="sp-appeal-reviewer">
                                    <?php printf(
                                        __('بواسطة %s - %s', 'saint-porphyrius'),
                                        esc_html($reviewer_name),
                                        esc_html(date_i18n('j M Y', strtotime($appeal->reviewed_at)))
                                    ); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($appeal->admin_notes)): ?>
                            <p class="sp-appeal-admin-notes"><?php echo esc_html($appeal->admin_notes); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
