<?php
/**
 * Saint Porphyrius - Lesson Preparation Wizard (User-Facing)
 * 7-step guided preparation form with rich text and auto-save
 *
 * @since 6.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$handler = SP_Lesson_Prep::get_instance();
$lesson_id = absint(get_query_var('sp_lesson_id', 0));

if (!$lesson_id) {
    wp_safe_redirect(home_url('/app/lesson-prep'));
    exit;
}

$lesson = $handler->get_lesson($lesson_id);
if (!$lesson || $lesson->status !== 'published') {
    echo '<main class="sp-page-content"><div class="sp-card" style="text-align:center;padding:var(--sp-space-xl);"><p>الدرس غير متوفر</p></div></main>';
    return;
}

// Check access
if (!current_user_can('manage_options') && !$handler->user_has_access($current_user->ID, $lesson_id)) {
    echo '<main class="sp-page-content"><div class="sp-card" style="text-align:center;padding:var(--sp-space-xl);"><p>ليس لديك صلاحية الوصول لهذا الدرس</p></div></main>';
    return;
}

$config = $handler->get_config();
$points_config = $lesson->prep_points_config ?: $config['section_points'];
$section_labels = SP_Lesson_Prep::get_section_labels();
$section_keys = array('lesson_name', 'objective', 'verse_ayah', 'training_exercises', 'explanation_means', 'lesson_introduction', 'lesson_writing');

// The member's preparation for this lesson, whatever state it is in. This used to only
// pick up 'draft' and 'needs_revision'; for a submitted or approved one it found nothing,
// so the wizard rendered blank with no hidden id -- the member's work looked lost, and
// saving INSERTed a *second* row rather than updating theirs. Those duplicates then
// inflated the submission count and locked people out of a limit they had barely used.
$existing_prep = $handler->get_user_preparation($current_user->ID, $lesson_id);

$status_labels = SP_Lesson_Prep::get_status_labels();
$prep_status = $existing_prep ? $existing_prep->status : null;

// Editable only while it is the member's to edit. Once submitted it belongs to the
// reviewers until they send it back. save_preparation() enforces the same rule, so this
// is presentation, not protection.
$is_editable = !$prep_status || in_array($prep_status, array('draft', 'needs_revision'), true);

$remaining = $handler->get_remaining_submissions($current_user->ID, $lesson_id);
$out_of_attempts = ($remaining !== null && $remaining < 1);

// Prefer the grade the member was assigned to for THIS lesson.
$user_grade = $handler->get_user_lesson_grade($current_user->ID, $lesson_id);
if (!$user_grade) {
    $user_grade = $handler->get_user_grade($current_user->ID);
}

$status_banners = array(
    'submitted'    => array('🕒', __('تحضيرك قيد الانتظار للمراجعة. لا يمكن تعديله الآن.', 'saint-porphyrius'), '#FEF3C7', '#92400E'),
    'under_review' => array('🔍', __('تحضيرك قيد المراجعة من قبل الإدارة.', 'saint-porphyrius'), '#DBEAFE', '#1E40AF'),
    'approved'     => array('🎉', __('تم قبول تحضيرك. شكراً لمجهودك!', 'saint-porphyrius'), '#D1FAE5', '#065F46'),
    'needs_revision' => array('✏️', __('تحضيرك يحتاج تعديل. عدّله ثم أعد تقديمه.', 'saint-porphyrius'), '#FEE2E2', '#991B1B'),
);
?>

<div class="sp-unified-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/lesson-prep'); ?>" class="sp-header-back" id="sp-prep-back-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('تحضير الدرس', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-spacer"></div>
    </div>
</div>

<main class="sp-page-content has-bottom-nav">
    <div style="padding:var(--sp-space-md);max-width:700px;margin:0 auto;">

        <!-- Lesson Info -->
        <div class="sp-card" style="padding:var(--sp-space-md);margin-bottom:var(--sp-space-md);text-align:center;">
            <div style="font-size:1.8rem;margin-bottom:4px;">✍️</div>
            <h2 style="margin:0;font-size:1.05rem;"><?php echo esc_html($lesson->title_ar); ?></h2>
            <?php if ($user_grade): ?>
                <span style="font-size:0.8rem;color:var(--sp-text-secondary);"><?php echo sprintf(__('الصف %d', 'saint-porphyrius'), $user_grade); ?></span>
            <?php endif; ?>
            <?php if (!empty($lesson->event_title_ar)): ?>
                <p style="margin:4px 0 0;font-size:0.8rem;color:var(--sp-text-tertiary);">📅 <?php echo esc_html($lesson->event_title_ar); ?></p>
            <?php endif; ?>
        </div>

        <?php if ($prep_status && isset($status_banners[$prep_status])):
            list($banner_icon, $banner_text, $banner_bg, $banner_fg) = $status_banners[$prep_status]; ?>
            <div class="sp-card" style="padding:var(--sp-space-md);margin-bottom:var(--sp-space-md);background:<?php echo $banner_bg; ?>;color:<?php echo $banner_fg; ?>;">
                <div style="display:flex;align-items:flex-start;gap:8px;">
                    <span style="font-size:1.3rem;line-height:1;"><?php echo $banner_icon; ?></span>
                    <div style="flex:1;min-width:0;">
                        <strong style="display:block;font-size:0.9rem;"><?php echo esc_html($status_labels[$prep_status] ?? $prep_status); ?></strong>
                        <span style="font-size:0.82rem;"><?php echo esc_html($banner_text); ?></span>
                        <?php if (!empty($existing_prep->admin_notes)): ?>
                            <p style="margin:8px 0 0;font-size:0.82rem;">
                                <strong><?php _e('ملاحظة الإدارة:', 'saint-porphyrius'); ?></strong>
                                <?php echo esc_html($existing_prep->admin_notes); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($is_editable && $out_of_attempts): ?>
            <div class="sp-card" style="padding:var(--sp-space-md);margin-bottom:var(--sp-space-md);background:#FEE2E2;color:#991B1B;">
                <strong style="font-size:0.9rem;">⛔ <?php _e('انتهت محاولات التقديم', 'saint-porphyrius'); ?></strong>
                <p style="margin:4px 0 0;font-size:0.82rem;">
                    <?php _e('يمكنك حفظ مسودة، لكن لا يمكن تقديمها. تواصل مع الإدارة.', 'saint-porphyrius'); ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Progress bar -->
        <div class="sp-prep-progress" style="margin-bottom:var(--sp-space-md);">
            <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                <span style="font-size:0.8rem;color:var(--sp-text-secondary);" id="sp-prep-step-label"><?php echo sprintf(__('الخطوة %d من %d', 'saint-porphyrius'), 1, 7); ?></span>
                <span style="font-size:0.8rem;color:var(--sp-text-secondary);" id="sp-prep-total-points"><?php _e('مجموع النقاط: 0', 'saint-porphyrius'); ?></span>
            </div>
            <div style="background:var(--sp-border);border-radius:4px;height:6px;overflow:hidden;">
                <div id="sp-prep-progress-fill" style="background:var(--sp-primary);height:100%;width:0%;transition:width .3s;"></div>
            </div>
        </div>

        <!-- Step indicators -->
        <div class="sp-prep-steps-nav" style="display:flex;gap:4px;margin-bottom:var(--sp-space-md);overflow-x:auto;padding-bottom:4px;">
            <?php foreach ($section_keys as $idx => $skey): ?>
                <button type="button" class="sp-prep-step-dot" data-step="<?php echo $idx; ?>" 
                    style="flex-shrink:0;min-width:32px;height:32px;border-radius:50%;border:2px solid var(--sp-border);background:#fff;font-size:0.75rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s;"
                    title="<?php echo esc_attr($section_labels[$skey]); ?>">
                    <?php echo $idx + 1; ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Wizard Form -->
        <form id="sp-prep-wizard-form"
              data-lesson-id="<?php echo $lesson_id; ?>"
              data-prep-id="<?php echo $existing_prep ? absint($existing_prep->id) : 0; ?>"
              data-editable="<?php echo $is_editable ? '1' : '0'; ?>"
              data-can-submit="<?php echo ($is_editable && !$out_of_attempts) ? '1' : '0'; ?>">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('sp_nonce'); ?>">
            <input type="hidden" name="action" value="sp_lesson_prep_save">
            <input type="hidden" name="lesson_id" value="<?php echo $lesson_id; ?>">
            <input type="hidden" name="grade" value="<?php echo $user_grade; ?>">
            <?php if ($existing_prep): ?>
                <input type="hidden" name="id" value="<?php echo absint($existing_prep->id); ?>">
            <?php endif; ?>
            <input type="hidden" name="submit" id="sp-prep-submit-flag" value="0">

            <?php foreach ($section_keys as $idx => $skey): 
                $db_field = 'section_' . $skey;
                $points = isset($points_config[$skey]) ? absint($points_config[$skey]) : 0;
                // Prefill from the member's row whatever its status. Showing a submitted
                // preparation back to them is the whole point -- it used to render blank.
                $existing_content = $existing_prep ? ($existing_prep->{$db_field} ?? '') : '';
                $existing_notes   = $existing_prep ? ($existing_prep->{$db_field . '_notes'} ?? '') : '';
                $readonly_attr = $is_editable ? '' : ' readonly';
            ?>
                <div class="sp-prep-step" data-step="<?php echo $idx; ?>" style="<?php echo $idx > 0 ? 'display:none;' : ''; ?>">
                    <div class="sp-card" style="padding:var(--sp-space-md);">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-space-sm);">
                            <h3 style="margin:0;font-size:1rem;"><?php echo esc_html($section_labels[$skey]); ?></h3>
                            <span class="sp-prep-section-points" style="background:var(--sp-primary-light, #EFF6FF);color:var(--sp-primary);padding:2px 10px;border-radius:12px;font-size:0.8rem;font-weight:600;">
                                ⭐ <?php echo $points; ?>
                            </span>
                        </div>

                        <!-- Main content area (rich text) -->
                        <label style="display:block;font-size:0.8rem;color:var(--sp-text-secondary);margin-bottom:4px;">
                            <?php _e('المحتوى', 'saint-porphyrius'); ?>
                        </label>
                        <textarea name="<?php echo $db_field; ?>"
                            class="sp-prep-content-field"
                            data-section="<?php echo $skey; ?>"
                            placeholder="<?php echo sprintf(__('اكتب محتوى %s هنا...', 'saint-porphyrius'), $section_labels[$skey]); ?>"
                            style="width:100%;min-height:120px;padding:12px;border:1px solid var(--sp-border);border-radius:8px;font-family:inherit;font-size:0.9rem;line-height:1.7;resize:vertical;<?php echo $is_editable ? '' : 'background:var(--sp-bg-secondary,#F9FAFB);'; ?>"
                            rows="6"<?php echo $readonly_attr; ?>><?php echo esc_textarea($existing_content); ?></textarea>

                        <!-- Notes area -->
                        <label style="display:block;font-size:0.8rem;color:var(--sp-text-secondary);margin:12px 0 4px;">
                            📝 <?php _e('ملاحظات', 'saint-porphyrius'); ?>
                        </label>
                        <textarea name="<?php echo $db_field; ?>_notes"
                            class="sp-prep-notes-field"
                            placeholder="<?php _e('ملاحظات إضافية (اختياري)...', 'saint-porphyrius'); ?>"
                            style="width:100%;min-height:80px;padding:12px;border:1px solid var(--sp-border);border-radius:8px;font-family:inherit;font-size:0.85rem;line-height:1.6;resize:vertical;<?php echo $is_editable ? '' : 'background:var(--sp-bg-secondary,#F9FAFB);'; ?>"
                            rows="3"<?php echo $readonly_attr; ?>><?php echo esc_textarea($existing_notes); ?></textarea>

                        <?php if ($skey === 'lesson_writing' && $is_editable): ?>
                            <!-- AI Detection preview area (appears after content is entered) -->
                            <div class="sp-ai-detection-preview" style="display:none;margin-top:12px;padding:12px;background:#FFF7ED;border:1px solid #FED7AA;border-radius:8px;">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <span>🤖</span>
                                    <span style="font-size:0.8rem;font-weight:600;"><?php _e('كشف محتوى AI', 'saint-porphyrius'); ?></span>
                                </div>
                                <div id="sp-ai-detection-result" style="margin-top:8px;font-size:0.8rem;"></div>
                                <button type="button" id="sp-ai-detect-btn" class="sp-btn sp-btn-outline sp-btn-xs" style="margin-top:8px;font-size:0.75rem;">
                                    🔍 <?php _e('فحص المحتوى', 'saint-porphyrius'); ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Navigation buttons -->
            <div style="display:flex;gap:8px;margin-top:var(--sp-space-md);flex-wrap:wrap;align-items:center;">
                <button type="button" id="sp-prep-prev-btn" class="sp-btn sp-btn-outline" style="display:none;">
                    ⬅️ <?php _e('السابق', 'saint-porphyrius'); ?>
                </button>
                <button type="button" id="sp-prep-next-btn" class="sp-btn sp-btn-primary" style="margin-right:auto;">
                    <?php _e('التالي', 'saint-porphyrius'); ?> ➡️
                </button>
                <?php if ($is_editable): ?>
                    <button type="button" id="sp-prep-save-draft-btn" class="sp-btn sp-btn-outline">
                        💾 <?php _e('حفظ مسودة', 'saint-porphyrius'); ?>
                    </button>
                    <?php if (!$out_of_attempts): ?>
                        <button type="button" id="sp-prep-submit-btn" class="sp-btn sp-btn-success" style="display:none;background:#059669;color:#fff;">
                            ✅ <?php _e('تقديم التحضير', 'saint-porphyrius'); ?>
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?php echo home_url('/app/lesson-prep/view/' . absint($existing_prep->id)); ?>" class="sp-btn sp-btn-outline">
                        👁️ <?php _e('عرض تحضيري', 'saint-porphyrius'); ?>
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($is_editable && $remaining !== null): ?>
                <p style="margin:var(--sp-space-sm) 0 0;font-size:0.8rem;color:var(--sp-text-secondary);text-align:center;">
                    <?php
                    // Surfaced so the 3-attempt limit never arrives as a surprise at the
                    // moment of pressing Submit.
                    printf(
                        esc_html__('المحاولات المتبقية: %1$d من %2$d', 'saint-porphyrius'),
                        absint($remaining),
                        absint($config['prep_max_submissions'])
                    );
                    ?>
                </p>
            <?php endif; ?>
        </form>
    </div>
</main>

<script>
(function() {
    var currentStep = 0;
    var totalSteps = <?php echo count($section_keys); ?>;
    var form = document.getElementById('sp-prep-wizard-form');
    var lessonId = parseInt(form.dataset.lessonId);

    // Elements
    var steps = form.querySelectorAll('.sp-prep-step');
    var dots = document.querySelectorAll('.sp-prep-step-dot');
    var prevBtn = document.getElementById('sp-prep-prev-btn');
    var nextBtn = document.getElementById('sp-prep-next-btn');
    var saveDraftBtn = document.getElementById('sp-prep-save-draft-btn');
    var submitBtn = document.getElementById('sp-prep-submit-btn');
    var progressFill = document.getElementById('sp-prep-progress-fill');
    var stepLabel = document.getElementById('sp-prep-step-label');
    var backLink = document.getElementById('sp-prep-back-link');

    var isEditable = form.dataset.editable === '1';

    // One request at a time. Two saves used to be able to be in flight together -- an
    // autosave and the submit -- and since neither knew the row id yet, both took the
    // INSERT branch and the member ended up with two preparations. Worse, a pending
    // autosave landing *after* a submit rewrote the status back to 'draft'.
    var inFlight = false;
    var autoSaveTimeout = null;
    var autoSaveQueued = false;

    function post(formData, timeoutMs) {
        return window.spFetch(spApp.ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        }, timeoutMs || 30000).then(window.spReadJson);
    }

    function buildFormData(isSubmit) {
        var formData = new FormData(form);
        formData.set('action', 'sp_lesson_prep_save');
        if (isSubmit) {
            formData.set('submit', '1');
        } else {
            formData.delete('submit');
        }
        return formData;
    }

    // The server decides which row this is now, but keeping the id in the form means a
    // later save in this same page-load addresses it directly.
    function rememberPrepId(payload) {
        var prep = payload && payload.data && payload.data.preparation;
        if (!prep || !prep.id) {
            return;
        }
        var idInput = form.querySelector('input[name="id"]');
        if (!idInput) {
            idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            form.appendChild(idInput);
        }
        idInput.value = prep.id;
        form.dataset.prepId = prep.id;
    }

    function showStep(index) {
        steps.forEach(function(s, i) { s.style.display = i === index ? '' : 'none'; });
        dots.forEach(function(d, i) {
            d.style.background = i === index ? 'var(--sp-primary)' : (i < index ? 'var(--sp-primary-light, #EFF6FF)' : '#fff');
            d.style.borderColor = i <= index ? 'var(--sp-primary)' : 'var(--sp-border)';
            d.style.color = i <= index ? '#fff' : 'var(--sp-text-secondary)';
        });

        currentStep = index;
        var pct = Math.round((index / (totalSteps - 1)) * 100);
        progressFill.style.width = pct + '%';
        stepLabel.textContent = 'الخطوة ' + (index + 1) + ' من ' + totalSteps;

        prevBtn.style.display = index === 0 ? 'none' : '';
        nextBtn.style.display = index === totalSteps - 1 ? 'none' : '';
        // Absent when the preparation is read-only or the attempts are used up.
        if (submitBtn) {
            submitBtn.style.display = index === totalSteps - 1 ? '' : 'none';
        }

        // Scroll to top of form
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function updateTotalPoints() {
        var total = 0;
        steps.forEach(function(step) {
            var pointsEl = step.querySelector('.sp-prep-section-points');
            if (pointsEl) {
                var pts = parseInt(pointsEl.textContent.replace(/[^0-9]/g, '')) || 0;
                total += pts;
            }
        });
        document.getElementById('sp-prep-total-points').textContent = 'مجموع النقاط: ' + total;
    }

    // Dot navigation
    dots.forEach(function(dot) {
        dot.addEventListener('click', function() {
            var idx = parseInt(dot.dataset.step);
            if (!isNaN(idx)) showStep(idx);
        });
    });

    // Button handlers
    prevBtn.addEventListener('click', function() {
        if (currentStep > 0) showStep(currentStep - 1);
    });

    nextBtn.addEventListener('click', function() {
        // Validate current step has content
        var currentField = steps[currentStep].querySelector('.sp-prep-content-field');
        if (currentField && !currentField.value.trim()) {
            if (!confirm('هذا القسم فارغ. هل تريد المتابعة دون كتابة محتوى؟')) {
                return;
            }
        }
        if (currentStep < totalSteps - 1) showStep(currentStep + 1);
    });

    // ---- Auto-save draft -------------------------------------------------------
    function autoSaveDraft() {
        if (!isEditable) {
            return;
        }

        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(function() {
            // Never race another save. Queue instead, and run once the current one lands.
            if (inFlight) {
                autoSaveQueued = true;
                return;
            }

            inFlight = true;
            post(buildFormData(false))
                .then(function(payload) {
                    if (payload && payload.success) {
                        rememberPrepId(payload);
                    }
                })
                .catch(function() {
                    // A failed autosave is not worth interrupting anyone over -- the
                    // member can still press حفظ مسودة, which does report failures.
                })
                .then(function() {
                    inFlight = false;
                    if (autoSaveQueued) {
                        autoSaveQueued = false;
                        autoSaveDraft();
                    }
                });
        }, 2000);
    }

    // Attach auto-save to all content fields
    if (isEditable) {
        form.querySelectorAll('.sp-prep-content-field, .sp-prep-notes-field').forEach(function(field) {
            field.addEventListener('input', autoSaveDraft);
        });
    }

    // ---- Save draft ------------------------------------------------------------
    if (saveDraftBtn) {
        saveDraftBtn.addEventListener('click', function() {
            if (inFlight) {
                return;
            }

            clearTimeout(autoSaveTimeout);
            autoSaveQueued = false;
            inFlight = true;

            saveDraftBtn.textContent = '⏳ جاري الحفظ...';
            saveDraftBtn.disabled = true;
            if (submitBtn) submitBtn.disabled = true;

            function restore(label) {
                saveDraftBtn.textContent = label;
                saveDraftBtn.disabled = false;
                if (submitBtn) submitBtn.disabled = false;
                inFlight = false;
            }

            post(buildFormData(false))
                .then(function(payload) {
                    if (payload && payload.success) {
                        rememberPrepId(payload);
                        saveDraftBtn.textContent = '✅ تم الحفظ';
                        saveDraftBtn.disabled = false;
                        if (submitBtn) submitBtn.disabled = false;
                        inFlight = false;
                        setTimeout(function() {
                            saveDraftBtn.textContent = '💾 حفظ مسودة';
                        }, 2000);
                        return;
                    }
                    // The server said why. Show that, not a guess.
                    alert(window.spErrorMessage(payload, 'تعذّر حفظ المسودة'));
                    restore('💾 حفظ مسودة');
                })
                .catch(function(error) {
                    alert(error.message || 'تعذّر حفظ المسودة');
                    restore('💾 حفظ مسودة');
                });
        });
    }

    // ---- Submit ----------------------------------------------------------------
    if (submitBtn) {
        submitBtn.addEventListener('click', function() {
            if (inFlight) {
                return;
            }

            // Validate all sections have content
            var allFilled = true;
            var emptySections = [];
            steps.forEach(function(step, i) {
                var field = step.querySelector('.sp-prep-content-field');
                if (field && !field.value.trim()) {
                    allFilled = false;
                    emptySections.push(i + 1);
                }
            });

            if (!allFilled) {
                if (!confirm('هناك أقسام فارغة (الخطوات: ' + emptySections.join(', ') + '). هل تريد المتابعة؟')) {
                    return;
                }
            }

            if (!confirm('هل أنت متأكد من تقديم التحضير؟ بعد التقديم، سيتم مراجعته من قبل الإدارة ولن تتمكن من تعديله.')) {
                return;
            }

            // Kill any pending autosave. It carried submit=0, so landing after this one
            // it would flip the freshly submitted preparation back to a draft.
            clearTimeout(autoSaveTimeout);
            autoSaveQueued = false;
            inFlight = true;

            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ جاري التقديم...';
            if (saveDraftBtn) saveDraftBtn.disabled = true;

            function restore() {
                submitBtn.disabled = false;
                submitBtn.textContent = '✅ تقديم التحضير';
                if (saveDraftBtn) saveDraftBtn.disabled = false;
                inFlight = false;
            }

            post(buildFormData(true))
                .then(function(payload) {
                    if (payload && payload.success && payload.data && payload.data.preparation) {
                        var prep = payload.data.preparation;
                        form.innerHTML = '<div class="sp-card" style="padding:var(--sp-space-xl);text-align:center;">' +
                            '<div style="font-size:4rem;">🎉</div>' +
                            '<h3>تم تقديم التحضير بنجاح!</h3>' +
                            '<p style="color:var(--sp-text-secondary);">حالة التحضير: ' + (prep.status === 'submitted' ? 'مُقدم للمراجعة' : prep.status) + '</p>' +
                            '<p>مجموع النقاط المتوقعة: ' + (prep.total_points_awarded || 0) + ' نقطة</p>' +
                            '<a href="' + spApp.appUrl + '/lesson-prep" class="sp-btn sp-btn-primary" style="margin-top:12px;">العودة للدروس</a>' +
                            '</div>';
                        return;
                    }
                    alert(window.spErrorMessage(payload, 'حدث خطأ أثناء التقديم'));
                    restore();
                })
                .catch(function(error) {
                    alert(error.message || 'حدث خطأ أثناء التقديم');
                    restore();
                });
        });
    }

    // AI Detection button (Section 7 - lesson_writing)
    var aiDetectBtn = document.getElementById('sp-ai-detect-btn');
    if (aiDetectBtn) {
        aiDetectBtn.addEventListener('click', function() {
            var writingField = form.querySelector('[name="section_lesson_writing"]');
            if (!writingField || !writingField.value.trim()) {
                alert('يرجى كتابة محتوى قسم "كتابة الدرس" أولاً');
                return;
            }

            aiDetectBtn.disabled = true;
            aiDetectBtn.textContent = '⏳ جاري الفحص...';

            var detectFormData = new FormData();
            detectFormData.append('nonce', spApp.nonce);
            detectFormData.append('action', 'sp_lesson_ai_detect');
            detectFormData.append('lesson_id', lessonId);
            detectFormData.append('text', writingField.value);

            // This one really does call OpenAI live, so give it longer than a save --
            // but still a deadline, and still an honest message when it is not met.
            post(detectFormData, 45000)
                .then(function(payload) {
                    var resultDiv = document.getElementById('sp-ai-detection-result');
                    if (payload && payload.success) {
                        var data = payload.data || {};
                        var score = data.score || 0;
                        var isAI = data.is_likely_ai;
                        var color = score > 70 ? '#DC2626' : (score > 40 ? '#D97706' : '#059669');
                        resultDiv.innerHTML =
                            '<div style="display:flex;align-items:center;gap:8px;">' +
                            '<div style="width:40px;height:40px;border-radius:50%;border:3px solid ' + color + ';display:flex;align-items:center;justify-content:center;font-weight:700;color:' + color + ';">' + score + '%</div>' +
                            '<div><strong>' + (isAI ? '⚠️ من المحتمل أن يكون محتوى AI' : '✅ المحتوى يبدو بشرياً') + '</strong></div>' +
                            '</div>';
                    } else {
                        resultDiv.textContent = 'فشل الفحص: ' + window.spErrorMessage(payload, 'خطأ');
                        resultDiv.style.color = '#DC2626';
                    }
                    aiDetectBtn.disabled = false;
                    aiDetectBtn.textContent = '🔄 إعادة الفحص';
                })
                .catch(function(error) {
                    // Used to reset the button and say nothing at all, so a failed check
                    // was indistinguishable from one that never ran.
                    var resultDiv = document.getElementById('sp-ai-detection-result');
                    resultDiv.textContent = 'فشل الفحص: ' + (error.message || 'خطأ');
                    resultDiv.style.color = '#DC2626';
                    aiDetectBtn.disabled = false;
                    aiDetectBtn.textContent = '🔍 فحص المحتوى';
                });
        });

        // Show AI detection preview when content is entered
        var writingField = form.querySelector('[name="section_lesson_writing"]');
        if (writingField) {
            writingField.addEventListener('input', function() {
                var preview = document.querySelector('.sp-ai-detection-preview');
                if (preview) {
                    preview.style.display = writingField.value.trim().length > 50 ? '' : 'none';
                }
            });
        }
    }

    // Initialize
    updateTotalPoints();
    showStep(0);
})();
</script>
