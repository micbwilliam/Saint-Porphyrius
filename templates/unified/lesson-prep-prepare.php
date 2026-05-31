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

// Check if user has a draft or existing preparation
$user_preps = $handler->get_preparations(array(
    'user_id' => $current_user->ID,
    'lesson_id' => $lesson_id,
    'limit' => 1,
));
$existing_prep = !empty($user_preps) ? $user_preps[0] : null;
$existing_draft = ($existing_prep && $existing_prep->status === 'draft') ? $existing_prep : null;
$existing_needs_revision = ($existing_prep && $existing_prep->status === 'needs_revision') ? $existing_prep : null;

// Prefer the grade the member was assigned to for THIS lesson.
$user_grade = $handler->get_user_lesson_grade($current_user->ID, $lesson_id);
if (!$user_grade) {
    $user_grade = $handler->get_user_grade($current_user->ID);
}
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
        <form id="sp-prep-wizard-form" data-lesson-id="<?php echo $lesson_id; ?>" data-prep-id="<?php echo $existing_draft ? $existing_draft->id : ($existing_needs_revision ? $existing_needs_revision->id : 0); ?>">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('sp_nonce'); ?>">
            <input type="hidden" name="action" value="sp_lesson_prep_save">
            <input type="hidden" name="lesson_id" value="<?php echo $lesson_id; ?>">
            <input type="hidden" name="grade" value="<?php echo $user_grade; ?>">
            <?php if ($existing_draft || $existing_needs_revision): ?>
                <input type="hidden" name="id" value="<?php echo $existing_draft ? $existing_draft->id : $existing_needs_revision->id; ?>">
            <?php endif; ?>
            <input type="hidden" name="submit" id="sp-prep-submit-flag" value="0">

            <?php foreach ($section_keys as $idx => $skey): 
                $db_field = 'section_' . $skey;
                $points = isset($points_config[$skey]) ? absint($points_config[$skey]) : 0;
                $existing_content = '';
                $existing_notes = '';
                if ($existing_draft || $existing_needs_revision) {
                    $src = $existing_draft ?: $existing_needs_revision;
                    $existing_content = $src->{$db_field} ?? '';
                    $existing_notes = $src->{$db_field . '_notes'} ?? '';
                }
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
                            style="width:100%;min-height:120px;padding:12px;border:1px solid var(--sp-border);border-radius:8px;font-family:inherit;font-size:0.9rem;line-height:1.7;resize:vertical;"
                            rows="6"><?php echo esc_textarea($existing_content); ?></textarea>

                        <!-- Notes area -->
                        <label style="display:block;font-size:0.8rem;color:var(--sp-text-secondary);margin:12px 0 4px;">
                            📝 <?php _e('ملاحظات', 'saint-porphyrius'); ?>
                        </label>
                        <textarea name="<?php echo $db_field; ?>_notes" 
                            class="sp-prep-notes-field"
                            placeholder="<?php _e('ملاحظات إضافية (اختياري)...', 'saint-porphyrius'); ?>"
                            style="width:100%;min-height:80px;padding:12px;border:1px solid var(--sp-border);border-radius:8px;font-family:inherit;font-size:0.85rem;line-height:1.6;resize:vertical;"
                            rows="3"><?php echo esc_textarea($existing_notes); ?></textarea>

                        <?php if ($skey === 'lesson_writing'): ?>
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
            <div style="display:flex;gap:8px;margin-top:var(--sp-space-md);flex-wrap:wrap;">
                <button type="button" id="sp-prep-prev-btn" class="sp-btn sp-btn-outline" style="display:none;">
                    ⬅️ <?php _e('السابق', 'saint-porphyrius'); ?>
                </button>
                <button type="button" id="sp-prep-next-btn" class="sp-btn sp-btn-primary" style="margin-right:auto;">
                    <?php _e('التالي', 'saint-porphyrius'); ?> ➡️
                </button>
                <button type="button" id="sp-prep-save-draft-btn" class="sp-btn sp-btn-outline">
                    💾 <?php _e('حفظ مسودة', 'saint-porphyrius'); ?>
                </button>
                <button type="button" id="sp-prep-submit-btn" class="sp-btn sp-btn-success" style="display:none;background:#059669;color:#fff;">
                    ✅ <?php _e('تقديم التحضير', 'saint-porphyrius'); ?>
                </button>
            </div>
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
        submitBtn.style.display = index === totalSteps - 1 ? '' : 'none';

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

    // Auto-save draft
    var autoSaveTimeout;
    function autoSaveDraft() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(function() {
            var formData = new FormData(form);
            formData.set('action', 'sp_lesson_prep_save');
            formData.delete('submit');

            fetch(spApp.ajaxUrl, {
                method: 'POST',
                body: formData,
            })
            .then(function(r) { return r.json(); })
            .then(function(response) {
                if (response.success && response.data.preparation) {
                    // Update prep ID for future saves
                    var idInput = form.querySelector('input[name="id"]');
                    if (!idInput) {
                        idInput = document.createElement('input');
                        idInput.type = 'hidden';
                        idInput.name = 'id';
                        form.appendChild(idInput);
                    }
                    idInput.value = response.data.preparation.id;
                    console.log('💾 تم الحفظ التلقائي');
                }
            })
            .catch(function() {});
        }, 2000);
    }

    // Attach auto-save to all content fields
    form.querySelectorAll('.sp-prep-content-field, .sp-prep-notes-field').forEach(function(field) {
        field.addEventListener('input', autoSaveDraft);
    });

    // Save draft button
    saveDraftBtn.addEventListener('click', function() {
        saveDraftBtn.textContent = '⏳ جاري الحفظ...';
        saveDraftBtn.disabled = true;

        var formData = new FormData(form);
        formData.set('action', 'sp_lesson_prep_save');
        formData.delete('submit');

        fetch(spApp.ajaxUrl, {
            method: 'POST',
            body: formData,
        })
        .then(function(r) { return r.json(); })
        .then(function(response) {
            if (response.success) {
                var idInput = form.querySelector('input[name="id"]');
                if (!idInput) {
                    idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'id';
                    form.appendChild(idInput);
                }
                idInput.value = response.data.preparation.id;
                saveDraftBtn.textContent = '✅ تم الحفظ';
                setTimeout(function() {
                    saveDraftBtn.textContent = '💾 حفظ مسودة';
                    saveDraftBtn.disabled = false;
                }, 2000);
            } else {
                saveDraftBtn.textContent = '❌ فشل الحفظ';
                saveDraftBtn.disabled = false;
            }
        })
        .catch(function() {
            saveDraftBtn.textContent = '💾 حفظ مسودة';
            saveDraftBtn.disabled = false;
        });
    });

    // Submit button
    submitBtn.addEventListener('click', function() {
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

        if (!confirm('هل أنت متأكد من تقديم التحضير؟ بعد التقديم، سيتم مراجعته من قبل الإدارة.')) {
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = '⏳ جاري التقديم...';
        saveDraftBtn.disabled = true;

        var formData = new FormData(form);
        formData.set('action', 'sp_lesson_prep_save');
        formData.set('submit', '1');

        fetch(spApp.ajaxUrl, {
            method: 'POST',
            body: formData,
        })
        .then(function(r) { return r.json(); })
        .then(function(response) {
            if (response.success) {
                var prep = response.data.preparation;
                form.innerHTML = '<div class="sp-card" style="padding:var(--sp-space-xl);text-align:center;">' +
                    '<div style="font-size:4rem;">🎉</div>' +
                    '<h3>تم تقديم التحضير بنجاح!</h3>' +
                    '<p style="color:var(--sp-text-secondary);">حالة التحضير: ' + (prep.status === 'submitted' ? 'مُقدم للمراجعة' : prep.status) + '</p>' +
                    '<p>مجموع النقاط المتوقعة: ' + (prep.total_points_awarded || 0) + ' نقطة</p>' +
                    '<a href="' + spApp.appUrl + '/lesson-prep" class="sp-btn sp-btn-primary" style="margin-top:12px;">العودة للدروس</a>' +
                    '</div>';
            } else {
                alert(response.data.message || 'حدث خطأ أثناء التقديم');
                submitBtn.disabled = false;
                submitBtn.textContent = '✅ تقديم التحضير';
                saveDraftBtn.disabled = false;
            }
        })
        .catch(function() {
            alert('حدث خطأ في الاتصال');
            submitBtn.disabled = false;
            submitBtn.textContent = '✅ تقديم التحضير';
            saveDraftBtn.disabled = false;
        });
    });

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

            fetch(spApp.ajaxUrl, {
                method: 'POST',
                body: detectFormData,
            })
            .then(function(r) { return r.json(); })
            .then(function(response) {
                var resultDiv = document.getElementById('sp-ai-detection-result');
                if (response.success) {
                    var data = response.data;
                    var score = data.score || 0;
                    var isAI = data.is_likely_ai;
                    var color = score > 70 ? '#DC2626' : (score > 40 ? '#D97706' : '#059669');
                    resultDiv.innerHTML = 
                        '<div style="display:flex;align-items:center;gap:8px;">' +
                        '<div style="width:40px;height:40px;border-radius:50%;border:3px solid ' + color + ';display:flex;align-items:center;justify-content:center;font-weight:700;color:' + color + ';">' + score + '%</div>' +
                        '<div><strong>' + (isAI ? '⚠️ من المحتمل أن يكون محتوى AI' : '✅ المحتوى يبدو بشرياً') + '</strong></div>' +
                        '</div>';
                } else {
                    resultDiv.innerHTML = '<span style="color:#DC2626;">فشل الفحص: ' + (response.data.message || 'خطأ') + '</span>';
                }
                aiDetectBtn.disabled = false;
                aiDetectBtn.textContent = '🔄 إعادة الفحص';
            })
            .catch(function() {
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
