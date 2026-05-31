<?php
/**
 * Saint Porphyrius - Admin Profile Edit Requests (Mobile)
 * Review and approve/reject member profile edit requests.
 */

if (!defined('ABSPATH')) {
    exit;
}

$edits_handler = SP_Profile_Edits::get_instance();
$message = '';
$message_type = '';

// Handle approve / reject actions
if (isset($_GET['action']) && isset($_GET['request_id']) && isset($_GET['_wpnonce'])) {
    if (wp_verify_nonce($_GET['_wpnonce'], 'sp_profile_edit_action')) {
        $action = sanitize_text_field($_GET['action']);
        $request_id = absint($_GET['request_id']);
        $admin_notes = sanitize_textarea_field($_GET['notes'] ?? '');

        $map = array('approve' => 'approved', 'reject' => 'rejected');
        if (isset($map[$action])) {
            $result = $edits_handler->process($request_id, $map[$action], get_current_user_id(), $admin_notes);
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

// Filter
$filter = isset($_GET['filter']) ? sanitize_text_field($_GET['filter']) : 'pending';
$valid_filters = array('pending', 'approved', 'rejected', 'all');
if (!in_array($filter, $valid_filters, true)) {
    $filter = 'pending';
}

$args = array('limit' => 50);
if ($filter !== 'all') {
    $args['status'] = $filter;
}
$requests = $edits_handler->get_all($args);
$pending_count = $edits_handler->count_pending();
?>

<!-- Admin Header -->
<div class="sp-unified-header sp-admin-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/admin'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('طلبات تعديل الملف الشخصي', 'saint-porphyrius'); ?></h1>
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
        <a href="<?php echo home_url('/app/admin/profile-edits?filter=pending'); ?>"
           class="sp-filter-tab <?php echo $filter === 'pending' ? 'active' : ''; ?>">
            <?php _e('معلق', 'saint-porphyrius'); ?>
            <?php if ($pending_count > 0): ?>
                <span class="sp-filter-count"><?php echo esc_html($pending_count); ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo home_url('/app/admin/profile-edits?filter=approved'); ?>"
           class="sp-filter-tab <?php echo $filter === 'approved' ? 'active' : ''; ?>">
            <?php _e('مقبول', 'saint-porphyrius'); ?>
        </a>
        <a href="<?php echo home_url('/app/admin/profile-edits?filter=rejected'); ?>"
           class="sp-filter-tab <?php echo $filter === 'rejected' ? 'active' : ''; ?>">
            <?php _e('مرفوض', 'saint-porphyrius'); ?>
        </a>
        <a href="<?php echo home_url('/app/admin/profile-edits?filter=all'); ?>"
           class="sp-filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
            <?php _e('الكل', 'saint-porphyrius'); ?>
        </a>
    </div>

    <?php if (empty($requests)): ?>
        <div class="sp-empty-state">
            <div class="sp-empty-icon">✏️</div>
            <h3><?php _e('لا توجد طلبات', 'saint-porphyrius'); ?></h3>
            <p><?php _e('لم يتم تقديم أي طلبات تعديل بعد', 'saint-porphyrius'); ?></p>
        </div>
    <?php else: ?>
        <div class="sp-edit-requests-list">
            <?php foreach ($requests as $req):
                $user = get_user_by('id', $req->user_id);
                $fn = $user ? $user->first_name : '';
                $mn = $user ? get_user_meta($user->ID, 'sp_middle_name', true) : '';
                $full_name = $user ? (trim($fn . ' ' . $mn) ?: $user->display_name) : __('مستخدم محذوف', 'saint-porphyrius');

                $status_label = SP_Profile_Edits::get_status_label($req->status);
                $status_color = SP_Profile_Edits::get_status_color($req->status);

                $changes = json_decode($req->changes, true);
                if (!is_array($changes)) {
                    $changes = array();
                }
            ?>
                <div class="sp-edit-request-card">
                    <div class="sp-edit-request-header">
                        <div class="sp-edit-request-user">
                            <a href="<?php echo esc_url(sp_profile_url($req->user_id)); ?>" class="sp-profile-link sp-edit-request-avatar">
                                <?php echo sp_render_avatar($req->user_id, mb_substr($full_name, 0, 1)); ?>
                            </a>
                            <div class="sp-edit-request-user-info">
                                <h4><a href="<?php echo esc_url(sp_profile_url($req->user_id)); ?>" class="sp-profile-link"><?php echo esc_html($full_name); ?></a></h4>
                                <span class="sp-edit-request-date"><?php echo esc_html(date_i18n('j M Y - g:i a', strtotime($req->created_at))); ?></span>
                            </div>
                        </div>
                        <span class="sp-edit-request-status-badge" style="background: <?php echo esc_attr($status_color); ?>20; color: <?php echo esc_attr($status_color); ?>;">
                            <?php echo esc_html($status_label); ?>
                        </span>
                    </div>

                    <div class="sp-edit-request-changes">
                        <div class="sp-edit-request-changes-label">
                            <?php printf(esc_html__('التغييرات المطلوبة (%d):', 'saint-porphyrius'), count($changes)); ?>
                        </div>
                        <?php foreach ($changes as $field => $info):
                            $label = isset($info['label']) ? $info['label'] : SP_Profile_Edits::get_field_label($field);
                            $old_display = SP_Profile_Edits::format_value($field, $info['old'] ?? '');
                            $new_display = SP_Profile_Edits::format_value($field, $info['new'] ?? '');
                        ?>
                            <div class="sp-edit-change-row">
                                <span class="sp-edit-change-field"><?php echo esc_html($label); ?></span>
                                <div class="sp-edit-change-values">
                                    <span class="sp-edit-change-old"><?php echo esc_html($old_display); ?></span>
                                    <span class="sp-edit-change-arrow">←</span>
                                    <span class="sp-edit-change-new"><?php echo esc_html($new_display); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($req->status === 'pending'): ?>
                    <div class="sp-edit-request-actions">
                        <a href="<?php echo wp_nonce_url(home_url('/app/admin/profile-edits?action=approve&request_id=' . $req->id . '&filter=' . $filter), 'sp_profile_edit_action'); ?>"
                           class="sp-btn sp-btn-success sp-btn-sm"
                           onclick="return confirm('<?php esc_attr_e('قبول طلب التعديل وتطبيق التغييرات على بيانات العضو؟', 'saint-porphyrius'); ?>');">
                            ✅ <?php _e('قبول', 'saint-porphyrius'); ?>
                        </a>
                        <button type="button" class="sp-btn sp-btn-danger sp-btn-sm sp-edit-reject-toggle">
                            ❌ <?php _e('رفض', 'saint-porphyrius'); ?>
                        </button>
                    </div>
                    <form class="sp-edit-reject-form" method="get" action="<?php echo home_url('/app/admin/profile-edits'); ?>">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="request_id" value="<?php echo esc_attr($req->id); ?>">
                        <input type="hidden" name="filter" value="<?php echo esc_attr($filter); ?>">
                        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr(wp_create_nonce('sp_profile_edit_action')); ?>">
                        <textarea name="notes" class="sp-form-textarea" rows="2" placeholder="<?php esc_attr_e('سبب الرفض (اختياري)', 'saint-porphyrius'); ?>"></textarea>
                        <button type="submit" class="sp-btn sp-btn-danger sp-btn-block sp-btn-sm"
                                onclick="return confirm('<?php esc_attr_e('رفض طلب التعديل؟', 'saint-porphyrius'); ?>');">
                            <?php _e('تأكيد الرفض', 'saint-porphyrius'); ?>
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="sp-edit-request-result">
                        <?php if ($req->reviewed_at):
                            $reviewer = get_user_by('id', $req->admin_id);
                            $reviewer_name = $reviewer ? $reviewer->display_name : __('مسؤول', 'saint-porphyrius');
                        ?>
                            <span class="sp-edit-request-reviewer">
                                <?php printf(
                                    esc_html__('بواسطة %s - %s', 'saint-porphyrius'),
                                    esc_html($reviewer_name),
                                    esc_html(date_i18n('j M Y', strtotime($req->reviewed_at)))
                                ); ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($req->admin_notes)): ?>
                            <p class="sp-edit-request-notes"><?php echo esc_html($req->admin_notes); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<style>
.sp-edit-requests-list { display: flex; flex-direction: column; gap: var(--sp-space-md); }
.sp-edit-request-card {
    background: var(--sp-bg-card);
    border-radius: var(--sp-radius-lg);
    padding: var(--sp-space-md);
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}
.sp-edit-request-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--sp-space-md);
}
.sp-edit-request-user { display: flex; align-items: center; gap: var(--sp-space-sm); }
.sp-edit-request-avatar img,
.sp-edit-request-avatar { width: 40px; height: 40px; border-radius: 50%; overflow: hidden; flex-shrink: 0; }
.sp-edit-request-user-info h4 { margin: 0; font-size: var(--sp-font-size-sm); }
.sp-edit-request-user-info h4 a { color: var(--sp-text-primary); text-decoration: none; }
.sp-edit-request-date { font-size: 0.75rem; color: var(--sp-text-muted); }
.sp-edit-request-status-badge {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 12px;
    white-space: nowrap;
}
.sp-edit-request-changes-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--sp-text-secondary);
    margin-bottom: var(--sp-space-sm);
}
.sp-edit-change-row {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: var(--sp-space-sm) 0;
    border-bottom: 1px solid var(--sp-border-light);
}
.sp-edit-change-row:last-child { border-bottom: none; }
.sp-edit-change-field { font-size: 0.8rem; color: var(--sp-text-secondary); }
.sp-edit-change-values {
    display: flex;
    align-items: center;
    gap: var(--sp-space-sm);
    flex-wrap: wrap;
}
.sp-edit-change-old {
    font-size: 0.85rem;
    color: #B91C1C;
    text-decoration: line-through;
    opacity: 0.8;
}
.sp-edit-change-arrow { color: var(--sp-text-muted); }
.sp-edit-change-new { font-size: 0.85rem; color: #059669; font-weight: 600; }
.sp-edit-request-actions {
    display: flex;
    gap: var(--sp-space-sm);
    margin-top: var(--sp-space-md);
}
.sp-edit-request-actions .sp-btn { flex: 1; }
.sp-btn-success { background: #10B981; color: #fff; }
.sp-btn-danger { background: #EF4444; color: #fff; }
.sp-btn-sm { padding: 8px 12px; font-size: 0.85rem; }
.sp-edit-reject-form { display: none; margin-top: var(--sp-space-sm); }
.sp-edit-reject-form.active { display: block; }
.sp-edit-reject-form .sp-form-textarea { margin-bottom: var(--sp-space-sm); width: 100%; }
.sp-edit-request-result {
    margin-top: var(--sp-space-md);
    padding-top: var(--sp-space-sm);
    border-top: 1px solid var(--sp-border-light);
    font-size: 0.8rem;
    color: var(--sp-text-muted);
}
.sp-edit-request-notes { margin: 4px 0 0; color: var(--sp-text-secondary); }
</style>

<script>
document.querySelectorAll('.sp-edit-reject-toggle').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var card = btn.closest('.sp-edit-request-card');
        var form = card ? card.querySelector('.sp-edit-reject-form') : null;
        if (form) {
            form.classList.toggle('active');
        }
    });
});
</script>
