<?php
/**
 * Saint Porphyrius - Admin Excuses (Mobile)
 * Review and approve/reject excuse requests
 */

if (!defined('ABSPATH')) {
    exit;
}

$excuses_handler = SP_Excuses::get_instance();
$message = '';
$message_type = '';

// Handle actions
if (isset($_GET['action']) && isset($_GET['excuse_id']) && isset($_GET['_wpnonce'])) {
    if (wp_verify_nonce($_GET['_wpnonce'], 'sp_excuse_action')) {
        $action = sanitize_text_field($_GET['action']);
        $excuse_id = absint($_GET['excuse_id']);
        
        if ($action === 'approve') {
            $result = $excuses_handler->approve($excuse_id, get_current_user_id());
            if (!is_wp_error($result)) {
                $message = __('تم قبول الاعتذار', 'saint-porphyrius');
                $message_type = 'success';
            } else {
                $message = $result->get_error_message();
                $message_type = 'error';
            }
        } elseif ($action === 'reject') {
            $result = $excuses_handler->reject($excuse_id, get_current_user_id());
            if (!is_wp_error($result)) {
                $message = __('تم رفض الاعتذار', 'saint-porphyrius');
                $message_type = 'warning';
            } else {
                $message = $result->get_error_message();
                $message_type = 'error';
            }
        }
    }
}

// Get filter
$filter = isset($_GET['filter']) ? sanitize_text_field($_GET['filter']) : 'pending';

// Get excuses
$args = array('limit' => 50);
if ($filter !== 'all') {
    $args['status'] = $filter;
}
$excuses = $excuses_handler->get_all($args);

$status_labels = array(
    'pending' => __('معلق', 'saint-porphyrius'),
    'approved' => __('مقبول', 'saint-porphyrius'),
    'rejected' => __('مرفوض', 'saint-porphyrius'),
);

$pending_count = $excuses_handler->count_pending();
?>

<!-- Admin Header -->
<div class="sp-unified-header sp-admin-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/admin'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('الاعتذارات', 'saint-porphyrius'); ?></h1>
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
        <a href="<?php echo home_url('/app/admin/excuses?filter=pending'); ?>" 
           class="sp-filter-tab <?php echo $filter === 'pending' ? 'active' : ''; ?>">
            <?php _e('معلق', 'saint-porphyrius'); ?>
            <?php if ($pending_count > 0): ?>
                <span class="sp-filter-count"><?php echo esc_html($pending_count); ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo home_url('/app/admin/excuses?filter=approved'); ?>" 
           class="sp-filter-tab <?php echo $filter === 'approved' ? 'active' : ''; ?>">
            <?php _e('مقبول', 'saint-porphyrius'); ?>
        </a>
        <a href="<?php echo home_url('/app/admin/excuses?filter=rejected'); ?>" 
           class="sp-filter-tab <?php echo $filter === 'rejected' ? 'active' : ''; ?>">
            <?php _e('مرفوض', 'saint-porphyrius'); ?>
        </a>
        <a href="<?php echo home_url('/app/admin/excuses?filter=all'); ?>" 
           class="sp-filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
            <?php _e('الكل', 'saint-porphyrius'); ?>
        </a>
    </div>

    <?php if (empty($excuses)): ?>
        <div class="sp-empty-state">
            <div class="sp-empty-icon">📝</div>
            <h3><?php _e('لا توجد اعتذارات', 'saint-porphyrius'); ?></h3>
            <p><?php _e('لم يتم تقديم أي اعتذارات بعد', 'saint-porphyrius'); ?></p>
        </div>
    <?php else: ?>
        <div class="sp-excuses-list">
            <?php foreach ($excuses as $excuse): 
                $user = get_user_by('id', $excuse->user_id);
                $name_ar = $user ? get_user_meta($user->ID, 'sp_name_ar', true) : '';
                $full_name = $name_ar ?: ($user ? $user->display_name : __('مستخدم محذوف', 'saint-porphyrius'));
            ?>
                <div class="sp-excuse-card">
                    <div class="sp-excuse-header">
                        <div class="sp-excuse-user">
                            <div class="sp-excuse-avatar">
                                <?php echo esc_html(mb_substr($full_name, 0, 1)); ?>
                            </div>
                            <div class="sp-excuse-user-info">
                                <h4><?php echo esc_html($full_name); ?></h4>
                                <span class="sp-excuse-date"><?php echo esc_html(date_i18n('j M Y - g:i a', strtotime($excuse->created_at))); ?></span>
                            </div>
                        </div>
                        <span class="sp-excuse-status sp-status-<?php echo esc_attr($excuse->status); ?>">
                            <?php echo esc_html($status_labels[$excuse->status] ?? $excuse->status); ?>
                        </span>
                    </div>
                    
                    <div class="sp-excuse-event">
                        <div class="sp-excuse-event-icon" style="background: <?php echo esc_attr($excuse->event_color ?? '#6B7280'); ?>20; color: <?php echo esc_attr($excuse->event_color ?? '#6B7280'); ?>;">
                            <?php echo esc_html($excuse->event_icon ?? '📅'); ?>
                        </div>
                        <div class="sp-excuse-event-info">
                            <h5><?php echo esc_html($excuse->event_title); ?></h5>
                            <span><?php echo esc_html(date_i18n('j F Y', strtotime($excuse->event_date))); ?></span>
                        </div>
                        <div class="sp-excuse-points">
                            <span class="sp-excuse-points-value">-<?php echo esc_html($excuse->points_deducted); ?></span>
                            <span class="sp-excuse-points-label"><?php _e('نقطة', 'saint-porphyrius'); ?></span>
                        </div>
                    </div>
                    
                    <div class="sp-excuse-text">
                        <strong><?php _e('سبب الاعتذار:', 'saint-porphyrius'); ?></strong>
                        <p><?php echo esc_html($excuse->excuse_text); ?></p>
                    </div>
                    
                    <?php if ($excuse->status === 'pending'): ?>
                    <div class="sp-excuse-actions">
                        <a href="<?php echo wp_nonce_url(home_url('/app/admin/excuses?action=approve&excuse_id=' . $excuse->id . '&filter=' . $filter), 'sp_excuse_action'); ?>" 
                           class="sp-btn sp-btn-success sp-btn-sm">
                            ✓ <?php _e('قبول', 'saint-porphyrius'); ?>
                        </a>
                        <a href="<?php echo wp_nonce_url(home_url('/app/admin/excuses?action=reject&excuse_id=' . $excuse->id . '&filter=' . $filter), 'sp_excuse_action'); ?>" 
                           class="sp-btn sp-btn-danger sp-btn-sm"
                           onclick="return confirm('<?php _e('هل أنت متأكد من رفض هذا الاعتذار؟', 'saint-porphyrius'); ?>');">
                            ✕ <?php _e('رفض', 'saint-porphyrius'); ?>
                        </a>
                    </div>
                    <?php elseif ($excuse->reviewed_at): ?>
                    <div class="sp-excuse-review-info">
                        <?php 
                        $reviewer = get_user_by('id', $excuse->reviewed_by);
                        $reviewer_name = $reviewer ? $reviewer->display_name : __('مسؤول', 'saint-porphyrius');
                        printf(
                            __('تمت المراجعة بواسطة %s في %s', 'saint-porphyrius'),
                            esc_html($reviewer_name),
                            esc_html(date_i18n('j M Y - g:i a', strtotime($excuse->reviewed_at)))
                        );
                        ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
