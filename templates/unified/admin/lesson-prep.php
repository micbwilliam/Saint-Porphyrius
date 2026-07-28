<?php
/**
 * Saint Porphyrius - Admin Lesson Preparation Management
 * List, create, edit, and manage lessons
 *
 * @since 6.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    echo '<main class="sp-page-content"><div class="sp-card" style="text-align:center;padding:var(--sp-space-xl);"><p>ليس لديك صلاحية</p></div></main>';
    return;
}

$handler = SP_Lesson_Prep::get_instance();
$lessons = $handler->get_lessons(array('limit' => 100));
$config = $handler->get_config();
?>

<div class="sp-unified-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/admin/dashboard'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('إدارة الدروس', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-spacer"></div>
    </div>
</div>

<main class="sp-page-content">
    <div style="padding:var(--sp-space-md);">

        <!-- Quick Stats -->
        <div style="display:flex;gap:8px;margin-bottom:var(--sp-space-md);flex-wrap:wrap;">
            <div class="sp-stat-badge" style="flex:1;min-width:80px;text-align:center;padding:12px;background:var(--sp-bg-secondary);border-radius:12px;">
                <div style="font-size:1.5rem;font-weight:700;"><?php echo count($lessons); ?></div>
                <div style="font-size:0.75rem;color:var(--sp-text-secondary);"><?php _e('درس', 'saint-porphyrius'); ?></div>
            </div>
            <div class="sp-stat-badge" style="flex:1;min-width:80px;text-align:center;padding:12px;background:#FEF3C7;border-radius:12px;">
                <div style="font-size:1.5rem;font-weight:700;"><?php echo $handler->get_preparation_count(0, 'submitted'); ?></div>
                <div style="font-size:0.75rem;color:#92400E;"><?php _e('قيد المراجعة', 'saint-porphyrius'); ?></div>
            </div>
        </div>

        <!-- Actions -->
        <div style="display:flex;gap:8px;margin-bottom:var(--sp-space-md);flex-wrap:wrap;">
            <a href="<?php echo home_url('/app/admin/lesson-prep/create'); ?>" class="sp-btn sp-btn-primary" style="font-size:0.85rem;">
                ➕ <?php _e('إنشاء درس جديد', 'saint-porphyrius'); ?>
            </a>
            <a href="<?php echo home_url('/app/admin/lesson-prep/review'); ?>" class="sp-btn sp-btn-outline" style="font-size:0.85rem;">
                🔍 <?php _e('مراجعة التحضيرات', 'saint-porphyrius'); ?>
            </a>
            <a href="<?php echo home_url('/app/admin/lesson-prep/settings'); ?>" class="sp-btn sp-btn-outline" style="font-size:0.85rem;">
                ⚙️ <?php _e('الإعدادات', 'saint-porphyrius'); ?>
            </a>
        </div>

        <!-- Lessons Table -->
        <?php if (empty($lessons)): ?>
            <div class="sp-card" style="text-align:center;padding:var(--sp-space-xl);">
                <div style="font-size:3rem;">📚</div>
                <p style="color:var(--sp-text-secondary);"><?php _e('لا توجد دروس بعد. أنشئ أول درس!', 'saint-porphyrius'); ?></p>
            </div>
        <?php else: ?>
            <div class="sp-card" style="overflow-x:auto;">
                <table class="sp-table" style="width:100%;border-collapse:collapse;font-size:0.85rem;">
                    <thead>
                        <tr style="border-bottom:2px solid var(--sp-border);">
                            <th style="padding:8px;text-align:right;"><?php _e('الدرس', 'saint-porphyrius'); ?></th>
                            <th style="padding:8px;text-align:center;"><?php _e('الفعالية', 'saint-porphyrius'); ?></th>
                            <th style="padding:8px;text-align:center;"><?php _e('الصفوف', 'saint-porphyrius'); ?></th>
                            <th style="padding:8px;text-align:center;"><?php _e('أسئلة', 'saint-porphyrius'); ?></th>
                            <th style="padding:8px;text-align:center;"><?php _e('تحضيرات', 'saint-porphyrius'); ?></th>
                            <th style="padding:8px;text-align:center;"><?php _e('الحالة', 'saint-porphyrius'); ?></th>
                            <th style="padding:8px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lessons as $lesson): 
                            $grades_str = !empty($lesson->grades) ? implode(', ', $lesson->grades) : '-';
                            $status_style = $lesson->status === 'published' ? 'color:#059669;' : ($lesson->status === 'draft' ? 'color:#D97706;' : 'color:#6B7280;');
                        ?>
                            <tr style="border-bottom:1px solid var(--sp-border);">
                                <td style="padding:8px;">
                                    <strong><?php echo esc_html($lesson->title_ar); ?></strong>
                                    <?php if (!empty($lesson->description_ar)): ?>
                                        <br><small style="color:var(--sp-text-tertiary);"><?php echo mb_substr(esc_html($lesson->description_ar), 0, 60); ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:8px;text-align:center;font-size:0.8rem;">
                                    <?php echo !empty($lesson->event_title_ar) ? esc_html($lesson->event_title_ar) : '-'; ?>
                                </td>
                                <td style="padding:8px;text-align:center;"><?php echo esc_html($grades_str); ?></td>
                                <td style="padding:8px;text-align:center;"><?php echo $lesson->question_count; ?></td>
                                <td style="padding:8px;text-align:center;"><?php echo $lesson->preparation_count; ?></td>
                                <td style="padding:8px;text-align:center;font-weight:600;<?php echo $status_style; ?>">
                                    <?php echo $lesson->status === 'published' ? '✅' : ($lesson->status === 'draft' ? '📝' : '📦'); ?>
                                    <?php echo $lesson->status; ?>
                                </td>
                                <td style="padding:8px;text-align:center;white-space:nowrap;">
                                    <a href="<?php echo home_url('/app/admin/lesson-prep/edit/' . $lesson->id); ?>" class="sp-btn sp-btn-outline sp-btn-xs" style="font-size:0.7rem;">✏️</a>
                                    <button type="button" class="sp-btn sp-btn-outline sp-btn-xs sp-delete-lesson-btn" 
                                        data-id="<?php echo $lesson->id; ?>"
                                        data-title="<?php echo esc_attr($lesson->title_ar); ?>"
                                        style="font-size:0.7rem;color:#DC2626;border-color:#DC2626;">🗑️</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
(function() {
    document.querySelectorAll('.sp-delete-lesson-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = btn.dataset.id;
            var title = btn.dataset.title;
            if (!confirm('هل أنت متأكد من حذف الدرس "' + title + '"؟\nسيتم حذف جميع الأسئلة والتحضيرات المرتبطة به.')) return;

            btn.disabled = true;
            btn.textContent = '⏳';

            var fd = new FormData();
            fd.append('nonce', '<?php echo wp_create_nonce('sp_admin_nonce'); ?>');
            fd.append('action', 'sp_lesson_delete');
            fd.append('lesson_id', id);

            window.spFetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: fd, credentials: 'same-origin' }, 30000)
            .then(window.spReadJson)
            .then(function(resp) {
                if (resp && resp.success) {
                    var row = btn.closest('tr');
                    if (row) row.remove();
                } else {
                    alert(window.spErrorMessage(resp, 'فشل الحذف'));
                    btn.disabled = false;
                    btn.textContent = '🗑️';
                }
            })
            .catch(function(error) {
                alert(error.message || 'فشل الحذف');
                btn.disabled = false;
                btn.textContent = '🗑️';
            });
        });
    });
})();
</script>
