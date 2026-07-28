<?php
/**
 * Saint Porphyrius - Admin Lesson Prep Review Detail
 * Review, adjust points, approve or request revision
 *
 * @since 6.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    echo '<main class="sp-page-content"><div class="sp-card"><p>ليس لديك صلاحية</p></div></main>';
    return;
}

$handler = SP_Lesson_Prep::get_instance();
$prep_id = absint(get_query_var('sp_prep_id', 0));

if (!$prep_id) {
    echo '<main class="sp-page-content"><div class="sp-card"><p>معرف التحضير مطلوب</p></div></main>';
    return;
}

$prep = $handler->get_preparation($prep_id);
if (!$prep) {
    echo '<main class="sp-page-content"><div class="sp-card"><p>التحضير غير موجود</p></div></main>';
    return;
}

$section_labels = SP_Lesson_Prep::get_section_labels();
$status_labels = SP_Lesson_Prep::get_status_labels();
$sections = array(
    'lesson_name' => 'section_lesson_name',
    'objective' => 'section_objective',
    'verse_ayah' => 'section_verse_ayah',
    'training_exercises' => 'section_training_exercises',
    'explanation_means' => 'section_explanation_means',
    'lesson_introduction' => 'section_lesson_introduction',
    'lesson_writing' => 'section_lesson_writing',
);

$lesson = $handler->get_lesson($prep->lesson_id);
$best_attempt = $handler->get_best_attempt($prep->user_id, $prep->lesson_id);

// Member identity for the header card. The reviewer needs to know whose work this is at a
// glance, and be able to get to their profile without hunting for them in the community
// list -- so: photo, name, and the whole block links through.
$member_name = $prep->user_name_ar ?: $prep->display_name;
$member_initial = mb_substr(trim($member_name), 0, 1);
$profiles_enabled = class_exists('SP_Social_Profile') && SP_Social_Profile::get_instance()->is_enabled();
$member_url = ($profiles_enabled && function_exists('sp_profile_url')) ? sp_profile_url($prep->user_id) : '';
?>

<div class="sp-unified-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/admin/lesson-prep/review'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('تفاصيل المراجعة', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-spacer"></div>
    </div>
</div>

<main class="sp-page-content">
    <div style="padding:var(--sp-space-md);max-width:800px;margin:0 auto;">

        <!-- Member & Lesson Info -->
        <div class="sp-card" style="padding:var(--sp-space-md);margin-bottom:var(--sp-space-sm);">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;">
                <div style="flex:1;min-width:0;">
                    <h2 style="margin:0 0 10px;font-size:1rem;"><?php echo esc_html($prep->lesson_title_ar); ?></h2>

                    <?php
                    // The whole member block is one target: photo + name + church + grade.
                    $member_tag  = $member_url ? 'a' : 'div';
                    $member_href = $member_url ? ' href="' . esc_url($member_url) . '"' : '';
                    ?>
                    <<?php echo $member_tag; ?><?php echo $member_href; ?>
                        style="display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit;<?php echo $member_url ? 'cursor:pointer;' : ''; ?>"
                        <?php if ($member_url): ?>title="<?php esc_attr_e('عرض الملف الشخصي', 'saint-porphyrius'); ?>"<?php endif; ?>>
                        <span class="sp-avatar" style="flex-shrink:0;width:48px;height:48px;border-radius:50%;overflow:hidden;display:flex;align-items:center;justify-content:center;background:var(--sp-primary-light,#EFF6FF);color:var(--sp-primary);font-weight:700;font-size:1.1rem;">
                            <?php echo sp_render_avatar($prep->user_id, $member_initial); ?>
                        </span>
                        <span style="min-width:0;">
                            <span style="display:block;font-size:0.95rem;font-weight:600;<?php echo $member_url ? 'color:var(--sp-primary);' : ''; ?>">
                                <?php echo esc_html($member_name); ?>
                            </span>
                            <?php if ($prep->user_church): ?>
                                <span style="display:block;font-size:0.78rem;color:var(--sp-text-secondary);">
                                    ⛪ <?php echo esc_html($prep->user_church); ?>
                                </span>
                            <?php endif; ?>
                            <span style="display:block;font-size:0.75rem;color:var(--sp-text-tertiary);">
                                📚 <?php echo sprintf(__('الصف %d', 'saint-porphyrius'), $prep->grade); ?>
                                | 📅 <?php echo date_i18n(get_option('date_format'), strtotime($prep->submitted_at ?: $prep->created_at)); ?>
                            </span>
                        </span>
                    </<?php echo $member_tag; ?>>
                </div>
                <div style="text-align:center;">
                    <span style="display:inline-block;padding:4px 12px;border-radius:12px;font-size:0.8rem;font-weight:600;
                        <?php
                        $status_colors = array(
                            'submitted' => 'background:#FEF3C7;color:#92400E;',
                            'under_review' => 'background:#DBEAFE;color:#1E40AF;',
                            'approved' => 'background:#D1FAE5;color:#065F46;',
                            'needs_revision' => 'background:#FEE2E2;color:#991B1B;',
                        );
                        echo $status_colors[$prep->status] ?? 'background:#E5E7EB;color:#374151;';
                        ?>">
                        <?php echo esc_html($status_labels[$prep->status] ?? $prep->status); ?>
                    </span>
                </div>
            </div>

            <!-- Quiz Result -->
            <?php if ($best_attempt): ?>
                <div style="margin-top:8px;padding:8px;background:var(--sp-bg-secondary);border-radius:8px;font-size:0.8rem;">
                    📝 <strong><?php _e('نتيجة الاختبار:', 'saint-porphyrius'); ?></strong>
                    <?php echo $best_attempt->score; ?>/<?php echo $best_attempt->total_questions; ?>
                    (<?php echo round($best_attempt->percentage); ?>%)
                    <?php if ($best_attempt->points_awarded > 0): ?>
                        | 🏆 <?php echo $best_attempt->points_awarded; ?> <?php _e('نقطة', 'saint-porphyrius'); ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sections Review -->
        <?php foreach ($sections as $skey => $db_field): 
            $content = $prep->{$db_field} ?? '';
            $notes = $prep->{$db_field . '_notes'} ?? '';
            $points = $prep->{$db_field . '_points'} ?? 0;
        ?>
            <div class="sp-card" style="padding:var(--sp-space-md);margin-bottom:var(--sp-space-sm);">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <h3 style="margin:0;font-size:0.95rem;"><?php echo esc_html($section_labels[$skey] ?? $skey); ?></h3>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-size:0.75rem;color:var(--sp-text-tertiary);"><?php _e('النقاط:', 'saint-porphyrius'); ?></span>
                        <input type="number" class="sp-review-point-input" data-section="<?php echo $db_field; ?>_points" 
                            value="<?php echo $points; ?>" min="0"
                            style="width:60px;padding:4px 8px;border:1px solid var(--sp-border);border-radius:6px;text-align:center;font-size:0.85rem;">
                    </div>
                </div>

                <?php if (!empty($content)): ?>
                    <div style="white-space:pre-wrap;line-height:1.8;font-size:0.85rem;padding:8px;background:var(--sp-bg-secondary);border-radius:8px;max-height:300px;overflow-y:auto;">
                        <?php echo wp_kses_post(nl2br($content)); ?>
                    </div>
                <?php else: ?>
                    <p style="color:var(--sp-text-tertiary);font-size:0.85rem;font-style:italic;"><?php _e('(فارغ)', 'saint-porphyrius'); ?></p>
                <?php endif; ?>

                <?php if (!empty($notes)): ?>
                    <div style="margin-top:8px;font-size:0.8rem;color:var(--sp-text-secondary);">
                        <strong>📝 ملاحظات:</strong>
                        <p style="margin:2px 0 0;white-space:pre-wrap;"><?php echo esc_html($notes); ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($skey === 'lesson_writing' && isset($prep->ai_detection_score)): 
                    $ai_score = floatval($prep->ai_detection_score);
                    $ai_color = $ai_score > 70 ? '#DC2626' : ($ai_score > 40 ? '#D97706' : '#059669');
                    $details = is_array($prep->ai_detection_details) ? $prep->ai_detection_details : json_decode($prep->ai_detection_details, true);
                ?>
                    <div style="margin-top:8px;padding:12px;background:#FFF7ED;border:1px solid #FED7AA;border-radius:8px;">
                        <h4 style="margin:0 0 8px 0;font-size:0.85rem;">🤖 <?php _e('تقرير كشف محتوى AI', 'saint-porphyrius'); ?></h4>
                        <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
                            <div style="width:50px;height:50px;border-radius:50%;border:3px solid <?php echo $ai_color; ?>;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1rem;color:<?php echo $ai_color; ?>;">
                                <?php echo $ai_score; ?>%
                            </div>
                            <div>
                                <strong style="color:<?php echo $ai_color; ?>;">
                                    <?php echo $prep->ai_detection_is_likely_ai ? '⚠️ ' . __('المحتوى على الأرجح AI', 'saint-porphyrius') : '✅ ' . __('المحتوى يبدو بشرياً', 'saint-porphyrius'); ?>
                                </strong>
                                <?php if ($prep->ai_penalty_applied > 0): ?>
                                    <br><span style="color:#DC2626;font-size:0.8rem;">⚠️ <?php echo sprintf(__('تم تطبيق عقوبة: %d نقطة', 'saint-porphyrius'), $prep->ai_penalty_applied); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (is_array($details) && !empty($details)): ?>
                            <div style="font-size:0.75rem;color:var(--sp-text-secondary);">
                                <?php if (!empty($details['indicators'])): ?>
                                    <strong><?php _e('المؤشرات:', 'saint-porphyrius'); ?></strong>
                                    <ul style="margin:4px 0;padding-right:16px;">
                                        <?php foreach ($details['indicators'] as $indicator): ?>
                                            <li><?php echo esc_html(is_array($indicator) ? ($indicator['text'] ?? '') : $indicator); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                <?php if (!empty($details['reasoning'])): ?>
                                    <p style="margin:4px 0;"><strong><?php _e('التحليل:', 'saint-porphyrius'); ?></strong> <?php echo esc_html($details['reasoning']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($details['heuristic_signals']['signals'])): ?>
                                    <p style="margin:4px 0;"><strong><?php _e('إشارات إضافية:', 'saint-porphyrius'); ?></strong></p>
                                    <ul style="margin:4px 0;padding-right:16px;">
                                        <?php foreach ($details['heuristic_signals']['signals'] as $sig): ?>
                                            <li><?php echo esc_html($sig); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <!-- Admin Notes & Actions -->
        <div class="sp-card" style="padding:var(--sp-space-md);margin-bottom:var(--sp-space-sm);">
            <h3 style="margin:0 0 8px 0;font-size:0.95rem;">📝 <?php _e('ملاحظات المراجع', 'saint-porphyrius'); ?></h3>
            <textarea id="sp-review-admin-notes" rows="3" placeholder="<?php _e('اكتب ملاحظاتك للمراجع هنا...', 'saint-porphyrius'); ?>"
                style="width:100%;padding:10px;border:1px solid var(--sp-border);border-radius:8px;font-family:inherit;font-size:0.85rem;resize:vertical;"><?php echo esc_textarea($prep->admin_notes ?? ''); ?></textarea>
        </div>

        <!-- Action Buttons -->
        <?php if ($prep->status === 'submitted' || $prep->status === 'under_review'): ?>
            <div style="display:flex;gap:8px;flex-wrap:wrap;" id="sp-review-actions">
                <button type="button" id="sp-approve-btn" class="sp-btn sp-btn-success" style="background:#059669;color:#fff;flex:1;min-width:120px;">
                    ✅ <?php _e('اعتماد التحضير', 'saint-porphyrius'); ?>
                </button>
                <button type="button" id="sp-revision-btn" class="sp-btn sp-btn-outline" style="color:#DC2626;border-color:#DC2626;flex:1;min-width:120px;">
                    ✏️ <?php _e('طلب تعديل', 'saint-porphyrius'); ?>
                </button>
            </div>
        <?php elseif ($prep->status === 'approved'): ?>
            <div class="sp-card" style="text-align:center;padding:var(--sp-space-md);background:#D1FAE5;">
                ✅ <?php _e('تم اعتماد هذا التحضير', 'saint-porphyrius'); ?>
                <?php if ($prep->reviewed_at): ?>
                    <br><small><?php echo date_i18n(get_option('date_format'), strtotime($prep->reviewed_at)); ?></small>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
(function() {
    var prepId = <?php echo $prep_id; ?>;
    var approveBtn = document.getElementById('sp-approve-btn');
    var revisionBtn = document.getElementById('sp-revision-btn');
    var notesField = document.getElementById('sp-review-admin-notes');

    function getPointAdjustments() {
        var adjustments = {};
        document.querySelectorAll('.sp-review-point-input').forEach(function(input) {
            adjustments[input.dataset.section] = parseInt(input.value) || 0;
        });
        return adjustments;
    }

    if (approveBtn) {
        approveBtn.addEventListener('click', function() {
            if (!confirm('هل أنت متأكد من اعتماد التحضير؟ سيتم منح النقاط للعضو.')) return;

            approveBtn.disabled = true;
            approveBtn.textContent = '⏳ جاري الاعتماد...';

            var formData = new FormData();
            formData.append('nonce', '<?php echo wp_create_nonce('sp_admin_nonce'); ?>');
            formData.append('action', 'sp_lesson_review_approve');
            formData.append('prep_id', prepId);
            formData.append('admin_notes', notesField.value);

            // Add adjusted points
            var adjustments = getPointAdjustments();
            for (var key in adjustments) {
                formData.append(key, adjustments[key]);
            }

            window.spFetch(spApp.ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' }, 30000)
            .then(window.spReadJson)
            .then(function(resp) {
                if (resp && resp.success) {
                    alert('✅ تم اعتماد التحضير بنجاح!');
                    location.reload();
                } else {
                    alert('❌ ' + window.spErrorMessage(resp, 'فشل الاعتماد'));
                    approveBtn.disabled = false;
                    approveBtn.textContent = '✅ اعتماد التحضير';
                }
            })
            .catch(function(error) {
                // Used to reset the button silently, so a failed approval looked like
                // a misclick.
                alert('❌ ' + (error.message || 'فشل الاعتماد'));
                approveBtn.disabled = false;
                approveBtn.textContent = '✅ اعتماد التحضير';
            });
        });
    }

    if (revisionBtn) {
        revisionBtn.addEventListener('click', function() {
            var notes = notesField.value.trim();
            if (!notes) {
                if (!confirm('لم تكتب ملاحظات. هل تريد المتابعة دون ملاحظات؟')) return;
            }

            revisionBtn.disabled = true;
            revisionBtn.textContent = '⏳...';

            var formData = new FormData();
            formData.append('nonce', '<?php echo wp_create_nonce('sp_admin_nonce'); ?>');
            formData.append('action', 'sp_lesson_review_revision');
            formData.append('prep_id', prepId);
            formData.append('admin_notes', notes);

            window.spFetch(spApp.ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' }, 30000)
            .then(window.spReadJson)
            .then(function(resp) {
                if (resp && resp.success) {
                    alert('✅ تم طلب التعديل');
                    location.reload();
                } else {
                    alert('❌ ' + window.spErrorMessage(resp, 'فشل'));
                    revisionBtn.disabled = false;
                    revisionBtn.textContent = '✏️ طلب تعديل';
                }
            })
            .catch(function(error) {
                alert('❌ ' + (error.message || 'فشل'));
                revisionBtn.disabled = false;
                revisionBtn.textContent = '✏️ طلب تعديل';
            });
        });
    }
})();
</script>
