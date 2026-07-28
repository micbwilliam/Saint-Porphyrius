<?php
/**
 * Saint Porphyrius - Admin Lesson Creation Wizard
 * Multi-step wizard: Info → PDF → Quiz Config → AI Generation → Points → Access → Publish
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
$config = $handler->get_config();
$events_handler = SP_Events::get_instance();

$is_edit = false;
$lesson = null;
$lesson_id = absint(get_query_var('sp_lesson_id', 0));
if ($lesson_id) {
    $lesson = $handler->get_lesson($lesson_id);
    if ($lesson) $is_edit = true;
}

// Upcoming events a lesson can be linked to. Include DRAFT events (not only
// published) so a lesson can be prepared before its event goes live; exclude
// completed/cancelled. get_upcoming() can't do this (it hardcodes published),
// so query directly. In edit mode, keep the currently-linked event selectable
// even if it's past or in another status.
$events = $events_handler->get_all(array(
    'from_date' => current_time('Y-m-d'),
    'limit'     => 200,
    'orderby'   => 'event_date',
    'order'     => 'ASC',
));
$events = array_values(array_filter($events, function ($ev) {
    return in_array($ev->status, array('draft', 'published'), true);
}));
if ($is_edit && $lesson->event_id) {
    $linked_present = false;
    foreach ($events as $ev) {
        if ((int) $ev->id === (int) $lesson->event_id) { $linked_present = true; break; }
    }
    if (!$linked_present) {
        $linked_event = $events_handler->get($lesson->event_id);
        if ($linked_event) {
            array_unshift($events, $linked_event);
        }
    }
}

$section_points = $config['section_points'];
$quiz_defaults = $config['quiz_defaults'];
// In edit mode reflect the lesson's saved AI-detection config (merged over global).
$ai_detection = ($is_edit && is_array($lesson->ai_detection_config) && !empty($lesson->ai_detection_config))
    ? wp_parse_args($lesson->ai_detection_config, $config['ai_detection'])
    : $config['ai_detection'];

// Existing questions for the JS editor (edit mode) so manual admin edits are captured.
$existing_questions_for_js = array();
if ($is_edit) {
    foreach ($handler->get_questions($lesson->id, false) as $eq) {
        $eq_opts = is_array($eq->options) ? $eq->options : (json_decode($eq->options, true) ?: array());
        $existing_questions_for_js[] = array(
            'question_text'        => $eq->question_text,
            'question_type'        => $eq->question_type,
            'options'              => $eq_opts,
            'correct_answer_index' => (int) $eq->correct_answer_index,
            'explanation'          => $eq->explanation,
            'difficulty'           => $eq->difficulty,
        );
    }
}
?>

<div class="sp-unified-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/admin/lesson-prep'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php echo $is_edit ? __('تعديل الدرس', 'saint-porphyrius') : __('إنشاء درس جديد', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-spacer"></div>
    </div>
</div>

<main class="sp-page-content">
    <div style="padding:var(--sp-space-md);max-width:800px;margin:0 auto;">

        <!-- Wizard Progress -->
        <div class="sp-wizard-progress" style="display:flex;gap:4px;margin-bottom:var(--sp-space-md);overflow-x:auto;padding-bottom:8px;">
            <?php 
            $step_names = array(
                __('المعلومات', 'saint-porphyrius'),
                __('ملف PDF', 'saint-porphyrius'),
                __('إعداد الاختبار', 'saint-porphyrius'),
                __('توليد الأسئلة', 'saint-porphyrius'),
                __('النقاط والصلاحيات', 'saint-porphyrius'),
                __('نشر', 'saint-porphyrius'),
            );
            foreach ($step_names as $si => $sn): ?>
                <button type="button" class="sp-wiz-step-btn" data-step="<?php echo $si; ?>" 
                    style="flex:1;min-width:60px;padding:8px 4px;border:none;border-bottom:3px solid <?php echo $si === 0 ? 'var(--sp-primary)' : 'var(--sp-border)'; ?>;background:none;font-size:0.7rem;color:<?php echo $si === 0 ? 'var(--sp-primary)' : 'var(--sp-text-tertiary)'; ?>;cursor:pointer;font-weight:<?php echo $si === 0 ? '600' : '400'; ?>;transition:all .2s;">
                    <?php echo esc_html($sn); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <form id="sp-admin-lesson-form" data-lesson-id="<?php echo $is_edit ? $lesson->id : 0; ?>">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('sp_admin_nonce'); ?>">

            <!-- STEP 1: Basic Info -->
            <div class="sp-wiz-step-content" data-step="0">
                <div class="sp-card" style="padding:var(--sp-space-md);">
                    <h3 style="margin:0 0 var(--sp-space-sm) 0;">📋 <?php _e('معلومات الدرس الأساسية', 'saint-porphyrius'); ?></h3>

                    <label style="display:block;margin-bottom:12px;">
                        <span style="font-size:0.85rem;font-weight:600;"><?php _e('عنوان الدرس (عربي)', 'saint-porphyrius'); ?> *</span>
                        <input type="text" name="title_ar" class="sp-input" value="<?php echo $is_edit ? esc_attr($lesson->title_ar) : ''; ?>" 
                            style="width:100%;padding:10px;border:1px solid var(--sp-border);border-radius:8px;margin-top:4px;font-family:inherit;" required>
                    </label>

                    <label style="display:block;margin-bottom:12px;">
                        <span style="font-size:0.85rem;font-weight:600;"><?php _e('الوصف (اختياري)', 'saint-porphyrius'); ?></span>
                        <textarea name="description_ar" class="sp-input" rows="3"
                            style="width:100%;padding:10px;border:1px solid var(--sp-border);border-radius:8px;margin-top:4px;font-family:inherit;resize:vertical;"><?php echo $is_edit ? esc_textarea($lesson->description_ar) : ''; ?></textarea>
                    </label>

                    <label style="display:block;margin-bottom:12px;">
                        <span style="font-size:0.85rem;font-weight:600;"><?php _e('ربط بفعالية', 'saint-porphyrius'); ?> *</span>
                        <select name="event_id" class="sp-input" 
                            style="width:100%;padding:10px;border:1px solid var(--sp-border);border-radius:8px;margin-top:4px;font-family:inherit;" required>
                            <option value="">-- اختر فعالية --</option>
                            <?php foreach ($events as $ev):
                                $ev_label = $ev->title_ar . ' — ' . $ev->event_date;
                                if ($ev->status === 'draft') {
                                    $ev_label .= ' (' . __('مسودة', 'saint-porphyrius') . ')';
                                }
                            ?>
                                <option value="<?php echo $ev->id; ?>" <?php echo ($is_edit && $lesson->event_id == $ev->id) ? 'selected' : ''; ?>>
                                    <?php echo esc_html($ev_label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <div style="margin-bottom:12px;">
                        <span style="font-size:0.85rem;font-weight:600;"><?php _e('الصفوف المستهدفة', 'saint-porphyrius'); ?></span>
                        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px;">
                            <?php 
                            $sel_grades = $is_edit ? ($lesson->grades ?: array()) : array();
                            for ($g = 1; $g <= 6; $g++): ?>
                                <label style="display:flex;align-items:center;gap:4px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="grades[]" value="<?php echo $g; ?>" 
                                        <?php echo in_array($g, $sel_grades) ? 'checked' : ''; ?>>
                                    <?php echo sprintf(__('الصف %d', 'saint-porphyrius'), $g); ?>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 2: PDF Upload + Text Input -->
            <div class="sp-wiz-step-content" data-step="1" style="display:none;">
                <div class="sp-card" style="padding:var(--sp-space-md);margin-bottom:var(--sp-space-sm);">
                    <h3 style="margin:0 0 var(--sp-space-sm) 0;">📄 <?php _e('مصدر محتوى الدرس', 'saint-porphyrius'); ?></h3>
                    <p style="font-size:0.85rem;color:var(--sp-text-secondary);"><?php _e('اختر إما رفع ملف PDF أو كتابة النص يدوياً.', 'saint-porphyrius'); ?></p>

                    <!-- Tabs -->
                    <div style="display:flex;gap:0;margin-bottom:var(--sp-space-sm);border-bottom:2px solid var(--sp-border);">
                        <button type="button" class="sp-source-tab active" data-tab="pdf" style="padding:8px 16px;border:none;background:none;border-bottom:2px solid var(--sp-primary);color:var(--sp-primary);font-weight:600;cursor:pointer;margin-bottom:-2px;font-size:0.85rem;">📎 PDF</button>
                        <button type="button" class="sp-source-tab" data-tab="text" style="padding:8px 16px;border:none;background:none;border-bottom:2px solid transparent;color:var(--sp-text-secondary);cursor:pointer;margin-bottom:-2px;font-size:0.85rem;">✏️ <?php _e('نص يدوي', 'saint-porphyrius'); ?></button>
                    </div>

                    <!-- PDF Tab Content -->
                    <div class="sp-source-panel" id="sp-source-pdf">
                        <p style="font-size:0.8rem;color:var(--sp-text-tertiary);margin-bottom:8px;"><?php _e('ارفع ملف PDF لكل صف، أو ملف واحد للجميع. النصوص العربية مدعومة.', 'saint-porphyrius'); ?></p>

                        <div style="margin-bottom:12px;">
                            <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:4px;">
                                <?php _e('رفع للجميع', 'saint-porphyrius'); ?>
                            </label>
                            <input type="file" name="pdf_all" accept=".pdf" class="sp-pdf-upload-input" style="font-size:0.85rem;">
                            <div class="sp-pdf-upload-status" style="font-size:0.75rem;color:var(--sp-text-tertiary);margin-top:4px;"></div>
                            <button type="button" class="sp-upload-pdf-btn sp-btn sp-btn-outline sp-btn-xs" data-grade="all" style="margin-top:4px;font-size:0.75rem;">
                                ⬆️ <?php _e('رفع', 'saint-porphyrius'); ?>
                            </button>
                            <?php if ($is_edit && !empty($lesson->pdf_urls)): 
                                $pdfs = is_object($lesson->pdf_urls) ? (array)$lesson->pdf_urls : (array)$lesson->pdf_urls;
                                foreach ($pdfs as $gk => $gu):
                                    if (!empty($gu)):
                            ?>
                                <div style="font-size:0.75rem;margin-top:4px;">
                                    📎 <a href="<?php echo esc_url($gu); ?>" target="_blank"><?php echo esc_html($gk); ?></a>
                                </div>
                            <?php endif; endforeach; endif; ?>
                        </div>

                        <?php for ($g = 1; $g <= 6; $g++): ?>
                            <div style="margin-bottom:8px;">
                                <label style="font-size:0.85rem;font-weight:600;">
                                    <?php echo sprintf(__('PDF الصف %d', 'saint-porphyrius'), $g); ?>
                                </label>
                                <div style="display:flex;gap:8px;align-items:center;">
                                    <input type="file" name="pdf_grade_<?php echo $g; ?>" accept=".pdf" style="font-size:0.8rem;flex:1;">
                                    <button type="button" class="sp-upload-pdf-btn sp-btn sp-btn-outline sp-btn-xs" data-grade="<?php echo $g; ?>" style="font-size:0.75rem;">⬆️</button>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <!-- Text Input Tab Content -->
                    <div class="sp-source-panel" id="sp-source-text" style="display:none;">
                        <p style="font-size:0.8rem;color:var(--sp-text-tertiary);margin-bottom:8px;">
                            <?php _e('اكتب أو الصق محتوى الدرس هنا. يمكن استخدام هذا الخيار كبديل عن PDF أو لإضافة نص إضافي.', 'saint-porphyrius'); ?>
                        </p>
                        <textarea id="sp-lesson-text-input" rows="12" placeholder="<?php _e('اكتب أو الصق نص الدرس هنا...', 'saint-porphyrius'); ?>"
                            style="width:100%;padding:12px;border:1px solid var(--sp-border);border-radius:8px;font-family:inherit;font-size:0.9rem;line-height:1.8;resize:vertical;direction:rtl;"
                        ><?php echo $is_edit && !empty($lesson->pdf_text) ? esc_textarea($lesson->pdf_text) : ''; ?></textarea>
                        <div style="margin-top:8px;display:flex;gap:8px;align-items:center;">
                            <button type="button" id="sp-save-text-btn" class="sp-btn sp-btn-outline sp-btn-sm" style="font-size:0.8rem;">
                                💾 <?php _e('حفظ النص', 'saint-porphyrius'); ?>
                            </button>
                            <span id="sp-text-save-status" style="font-size:0.75rem;color:var(--sp-text-tertiary);"></span>
                            <?php if ($is_edit && !empty($lesson->pdf_text)): ?>
                                <span style="font-size:0.75rem;color:var(--sp-text-tertiary);margin-right:auto;">
                                    <?php echo sprintf(__('طول النص الحالي: %d حرف', 'saint-porphyrius'), mb_strlen($lesson->pdf_text)); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 3: Quiz Configuration -->
            <div class="sp-wiz-step-content" data-step="2" style="display:none;">
                <div class="sp-card" style="padding:var(--sp-space-md);">
                    <h3 style="margin:0 0 var(--sp-space-sm) 0;">📝 <?php _e('إعدادات الاختبار', 'saint-porphyrius'); ?></h3>

                    <label style="display:block;margin-bottom:12px;">
                        <span style="font-size:0.85rem;font-weight:600;"><?php _e('عدد الأسئلة', 'saint-porphyrius'); ?></span>
                        <input type="number" name="num_questions" value="<?php echo $is_edit && isset($lesson->quiz_config['num_questions']) ? $lesson->quiz_config['num_questions'] : $quiz_defaults['num_questions']; ?>" min="3" max="100"
                            style="width:100%;padding:10px;border:1px solid var(--sp-border);border-radius:8px;margin-top:4px;">
                    </label>

                    <label style="display:block;margin-bottom:12px;">
                        <span style="font-size:0.85rem;font-weight:600;"><?php _e('مجموع نقاط الاختبار', 'saint-porphyrius'); ?></span>
                        <input type="number" name="points" value="<?php echo $is_edit && isset($lesson->quiz_config['points']) ? $lesson->quiz_config['points'] : $quiz_defaults['points']; ?>" min="0"
                            style="width:100%;padding:10px;border:1px solid var(--sp-border);border-radius:8px;margin-top:4px;">
                    </label>

                    <label style="display:block;margin-bottom:12px;">
                        <span style="font-size:0.85rem;font-weight:600;"><?php _e('نسبة النجاح (%)', 'saint-porphyrius'); ?></span>
                        <input type="number" name="passing_percent" value="<?php echo $is_edit && isset($lesson->quiz_config['passing_percent']) ? $lesson->quiz_config['passing_percent'] : $quiz_defaults['passing_percent']; ?>" min="0" max="100"
                            style="width:100%;padding:10px;border:1px solid var(--sp-border);border-radius:8px;margin-top:4px;">
                    </label>

                    <label style="display:flex;align-items:center;gap:8px;margin-bottom:12px;cursor:pointer;">
                        <input type="checkbox" name="allow_retake" value="1" 
                            <?php echo ($is_edit && !empty($lesson->quiz_config['allow_retake'])) ? 'checked' : ''; ?>>
                        <span style="font-size:0.85rem;"><?php _e('السماح بإعادة الاختبار', 'saint-porphyrius'); ?></span>
                    </label>
                </div>
            </div>

            <!-- STEP 4: AI Quiz Generation -->
            <div class="sp-wiz-step-content" data-step="3" style="display:none;">
                <div class="sp-card" style="padding:var(--sp-space-md);">
                    <h3 style="margin:0 0 var(--sp-space-sm) 0;">🤖 <?php _e('توليد الأسئلة بالذكاء الاصطناعي', 'saint-porphyrius'); ?></h3>
                    <p style="font-size:0.85rem;color:var(--sp-text-secondary);margin-bottom:12px;">
                        <?php _e('سيتم استخراج النص من PDF واستخدام الذكاء الاصطناعي لتوليد أسئلة.', 'saint-porphyrius'); ?>
                    </p>

                    <button type="button" id="sp-generate-questions-btn" class="sp-btn sp-btn-primary" style="margin-bottom:12px;">
                        🪄 <?php _e('توليد الأسئلة', 'saint-porphyrius'); ?>
                    </button>
                    <button type="button" id="sp-generate-more-btn" class="sp-btn sp-btn-outline" style="margin-bottom:12px;display:none;">
                        ➕ <?php _e('توليد أسئلة إضافية', 'saint-porphyrius'); ?>
                    </button>

                    <div id="sp-generation-status" style="font-size:0.85rem;margin-bottom:8px;display:none;"></div>

                    <!-- Questions editor (populated after generation) -->
                    <div id="sp-questions-editor" style="max-height:500px;overflow-y:auto;">
                        <?php if ($is_edit): 
                            $existing_questions = $handler->get_questions($lesson->id, false);
                            foreach ($existing_questions as $qidx => $q):
                                $options = is_array($q->options) ? $q->options : json_decode($q->options, true);
                        ?>
                            <div class="sp-question-editor-row" data-qid="<?php echo $q->id; ?>" data-index="<?php echo $qidx; ?>" 
                                style="padding:8px;margin-bottom:8px;border:1px solid var(--sp-border);border-radius:8px;background:var(--sp-bg-secondary);">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                                    <strong style="font-size:0.8rem;"><?php echo sprintf(__('سؤال %d', 'saint-porphyrius'), $qidx + 1); ?></strong>
                                    <span style="font-size:0.7rem;color:var(--sp-text-tertiary);"><?php echo $q->difficulty; ?></span>
                                </div>
                                <input type="text" class="sp-q-text" value="<?php echo esc_attr($q->question_text); ?>" 
                                    style="width:100%;padding:6px;border:1px solid var(--sp-border);border-radius:4px;font-size:0.85rem;margin-bottom:4px;">
                                <?php if ($q->question_type === 'multiple_choice' && is_array($options)): 
                                    foreach ($options as $oidx => $opt):
                                        $opt_text = is_array($opt) ? ($opt['text'] ?? '') : $opt;
                                        $is_correct = is_array($opt) ? ($opt['is_correct'] ?? false) : false;
                                ?>
                                    <div style="display:flex;align-items:center;gap:4px;margin-bottom:2px;">
                                        <input type="radio" name="correct_<?php echo $qidx; ?>" value="<?php echo $oidx; ?>" <?php echo $is_correct ? 'checked' : ''; ?>>
                                        <input type="text" value="<?php echo esc_attr($opt_text); ?>" 
                                            style="flex:1;padding:4px;border:1px solid var(--sp-border);border-radius:4px;font-size:0.8rem;">
                                    </div>
                                <?php endforeach; endif; ?>
                                <button type="button" class="sp-delete-question-btn sp-btn sp-btn-outline sp-btn-xs" style="color:#DC2626;border-color:#DC2626;font-size:0.7rem;margin-top:4px;">
                                    🗑️ <?php _e('حذف', 'saint-porphyrius'); ?>
                                </button>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>

            <!-- STEP 5: Points & Access -->
            <div class="sp-wiz-step-content" data-step="4" style="display:none;">
                <div class="sp-card" style="padding:var(--sp-space-md);margin-bottom:var(--sp-space-sm);">
                    <h3 style="margin:0 0 var(--sp-space-sm) 0;">⭐ <?php _e('توزيع نقاط التحضير', 'saint-porphyrius'); ?></h3>
                    <?php 
                    $section_labels = SP_Lesson_Prep::get_section_labels();
                    foreach ($section_points as $sk => $sp): ?>
                        <label style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;font-size:0.85rem;">
                            <span><?php echo esc_html($section_labels[$sk] ?? $sk); ?></span>
                            <input type="number" name="prep_point_<?php echo $sk; ?>" value="<?php echo $sp; ?>" min="0"
                                style="width:70px;padding:6px;border:1px solid var(--sp-border);border-radius:6px;text-align:center;">
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="sp-card" style="padding:var(--sp-space-md);margin-bottom:var(--sp-space-sm);">
                    <h3 style="margin:0 0 var(--sp-space-sm) 0;">🤖 <?php _e('إعدادات كشف محتوى AI', 'saint-porphyrius'); ?></h3>
                    <label style="display:flex;align-items:center;gap:8px;margin-bottom:10px;font-size:0.85rem;cursor:pointer;">
                        <input type="checkbox" name="ai_enabled" value="1" <?php echo (!isset($ai_detection['enabled']) || $ai_detection['enabled']) ? 'checked' : ''; ?>>
                        <span><?php _e('تفعيل كشف محتوى AI لقسم "كتابة الدرس"', 'saint-porphyrius'); ?></span>
                    </label>
                    <label style="display:block;margin-bottom:8px;font-size:0.85rem;">
                        <span><?php _e('نسبة الاحتمال للتصنيف كـ AI (%)', 'saint-porphyrius'); ?></span>
                        <input type="number" name="ai_threshold" value="<?php echo $ai_detection['threshold'] ?? 70; ?>" min="0" max="100"
                            style="width:100%;padding:8px;border:1px solid var(--sp-border);border-radius:6px;margin-top:4px;">
                    </label>
                    <label style="display:block;margin-bottom:8px;font-size:0.85rem;">
                        <span><?php _e('نوع العقوبة', 'saint-porphyrius'); ?></span>
                        <select name="ai_penalty_type" style="width:100%;padding:8px;border:1px solid var(--sp-border);border-radius:6px;margin-top:4px;">
                            <option value="percentage" <?php echo ($ai_detection['penalty_type'] ?? '') === 'percentage' ? 'selected' : ''; ?>><?php _e('نسبة مئوية', 'saint-porphyrius'); ?></option>
                            <option value="fixed" <?php echo ($ai_detection['penalty_type'] ?? '') === 'fixed' ? 'selected' : ''; ?>><?php _e('قيمة ثابتة', 'saint-porphyrius'); ?></option>
                        </select>
                    </label>
                    <label style="display:block;margin-bottom:8px;font-size:0.85rem;">
                        <span><?php _e('قيمة العقوبة', 'saint-porphyrius'); ?></span>
                        <input type="number" name="ai_penalty_amount" value="<?php echo $ai_detection['penalty_amount'] ?? 50; ?>" min="0"
                            style="width:100%;padding:8px;border:1px solid var(--sp-border);border-radius:6px;margin-top:4px;">
                    </label>
                </div>

                <div class="sp-card" style="padding:var(--sp-space-md);">
                    <h3 style="margin:0 0 var(--sp-space-sm) 0;">👥 <?php _e('الأعضاء المسموح لهم', 'saint-porphyrius'); ?></h3>
                    <p style="font-size:0.85rem;color:var(--sp-text-secondary);"><?php _e('اختر الأعضاء المسموح لهم بتحضير هذا الدرس حسب الصف.', 'saint-porphyrius'); ?></p>
                    <div id="sp-access-user-list" style="max-height:300px;overflow-y:auto;">
                        <p style="font-size:0.85rem;color:var(--sp-text-tertiary);"><?php _e('جاري تحميل قائمة الأعضاء...', 'saint-porphyrius'); ?></p>
                    </div>
                </div>
            </div>

            <!-- STEP 6: Publish -->
            <div class="sp-wiz-step-content" data-step="5" style="display:none;">
                <div class="sp-card" style="padding:var(--sp-space-lg);text-align:center;">
                    <div style="font-size:4rem;">📚</div>
                    <h3 style="margin:8px 0;"><?php _e('جاهز للنشر!', 'saint-porphyrius'); ?></h3>
                    <p style="color:var(--sp-text-secondary);"><?php _e('راجع جميع البيانات قبل النشر.', 'saint-porphyrius'); ?></p>

                    <div style="margin:16px 0;text-align:right;font-size:0.85rem;line-height:2;">
                        <div id="sp-review-title"><strong>العنوان:</strong> <span></span></div>
                        <div id="sp-review-event"><strong>الفعالية:</strong> <span></span></div>
                        <div id="sp-review-grades"><strong>الصفوف:</strong> <span></span></div>
                        <div id="sp-review-questions"><strong>الأسئلة:</strong> <span>0</span></div>
                        <div id="sp-review-points"><strong>مجموع نقاط التحضير:</strong> <span>0</span></div>
                    </div>

                    <div style="display:flex;gap:8px;justify-content:center;">
                        <button type="button" id="sp-save-draft-btn" class="sp-btn sp-btn-outline">
                            💾 <?php _e('حفظ كمسودة', 'saint-porphyrius'); ?>
                        </button>
                        <button type="button" id="sp-publish-btn" class="sp-btn sp-btn-success" style="background:#059669;color:#fff;">
                            ✅ <?php _e('نشر الدرس', 'saint-porphyrius'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div style="display:flex;gap:8px;margin-top:var(--sp-space-md);">
                <button type="button" id="sp-wiz-prev" class="sp-btn sp-btn-outline" style="display:none;">⬅️ <?php _e('السابق', 'saint-porphyrius'); ?></button>
                <button type="button" id="sp-wiz-next" class="sp-btn sp-btn-primary" style="margin-right:auto;"><?php _e('التالي', 'saint-porphyrius'); ?> ➡️</button>
            </div>
        </form>
    </div>
</main>

<script>
(function() {
    var currentStep = 0;
    var totalSteps = 6;
    var form = document.getElementById('sp-admin-lesson-form');
    var lessonId = parseInt(form.dataset.lessonId);
    var IS_EDIT = <?php echo $is_edit ? 'true' : 'false'; ?>;
    var ADMIN_NONCE = '<?php echo wp_create_nonce('sp_admin_nonce'); ?>';
    var generatedQuestions = <?php echo wp_json_encode($existing_questions_for_js); ?> || [];
    var accessLoaded = false; // becomes true once the member access list is rendered

    // This screen is where the honest-response reader was first written (6.4.6). It now
    // lives in main.js as window.spReadJson so the member-facing wizard -- which had the
    // same bug and never got the fix -- shares one implementation.
    var readJson = window.spReadJson;
    var errorMessage = window.spErrorMessage;

    // Record the id the moment a create succeeds. If this is missed, a later retry sends
    // sp_lesson_create again and the admin ends up with two copies of the lesson.
    function setLessonId(id) {
        id = parseInt(id);
        if (!id) return;
        lessonId = id;
        form.dataset.lessonId = id;
    }

    // Both save buttons write the same lesson, so neither may run while the other is in
    // flight, and neither may be re-armed until the whole chain settles.
    var saveButtons = ['sp-save-draft-btn', 'sp-publish-btn'];

    function lockSaving(activeId, label) {
        saveButtons.forEach(function(id) {
            var b = document.getElementById(id);
            if (b) b.disabled = true;
        });
        var active = document.getElementById(activeId);
        if (active) active.textContent = label;
    }

    function unlockSaving(activeId, label) {
        saveButtons.forEach(function(id) {
            var b = document.getElementById(id);
            if (b) b.disabled = false;
        });
        var active = document.getElementById(activeId);
        if (active) active.textContent = label;
    }

    // Step navigation
    var stepBtns = document.querySelectorAll('.sp-wiz-step-btn');
    var stepContents = document.querySelectorAll('.sp-wiz-step-content');
    var prevBtn = document.getElementById('sp-wiz-prev');
    var nextBtn = document.getElementById('sp-wiz-next');

    function showStep(index) {
        stepContents.forEach(function(s, i) { s.style.display = i === index ? '' : 'none'; });
        stepBtns.forEach(function(b, i) {
            b.style.borderBottomColor = i === index ? 'var(--sp-primary)' : 'var(--sp-border)';
            b.style.color = i <= index ? 'var(--sp-primary)' : 'var(--sp-text-tertiary)';
            b.style.fontWeight = i === index ? '600' : '400';
        });
        currentStep = index;
        prevBtn.style.display = index === 0 ? 'none' : '';
        nextBtn.style.display = index === totalSteps - 1 ? 'none' : '';
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });

        // Update review step
        if (index === 5) updateReviewStep();
        // Load members when reaching access step
        if (index === 4) loadUsersByGrade();
    }

    stepBtns.forEach(function(b) {
        b.addEventListener('click', function() { showStep(parseInt(b.dataset.step)); });
    });

    prevBtn.addEventListener('click', function() { if (currentStep > 0) showStep(currentStep - 1); });

    nextBtn.addEventListener('click', function() {
        if (currentStep >= totalSteps - 1) return;

        // On step 0 (basic info), auto-save draft to get a lessonId before proceeding
        if (currentStep === 0 && !lessonId) {
            var titleVal = form.querySelector('[name="title_ar"]');
            if (!titleVal || !titleVal.value.trim()) {
                alert('يرجى كتابة عنوان الدرس أولاً');
                return;
            }
            nextBtn.disabled = true;
            nextBtn.textContent = '⏳ جاري الحفظ...';
            saveLesson('draft').then(function(resp) {
                if (resp.success) {
                    setLessonId(resp.data.lesson.id);
                    showStep(currentStep + 1);
                } else {
                    alert(errorMessage(resp, 'فشل حفظ الدرس'));
                }
            }).catch(function(e) {
                alert(e.message || 'فشل الاتصال بالخادم');
            }).finally(function() {
                nextBtn.disabled = false;
                nextBtn.textContent = 'التالي ➡️';
            });
        } else {
            showStep(currentStep + 1);
        }
    });

    function updateReviewStep() {
        var title = form.querySelector('[name="title_ar"]');
        var eventSel = form.querySelector('[name="event_id"]');
        document.querySelector('#sp-review-title span').textContent = title ? title.value : '';
        document.querySelector('#sp-review-event span').textContent = eventSel ? eventSel.selectedOptions[0].textContent : '';
        
        var grades = form.querySelectorAll('[name="grades[]"]:checked');
        var gradesArr = [];
        grades.forEach(function(g) { gradesArr.push(g.value); });
        document.querySelector('#sp-review-grades span').textContent = gradesArr.join(', ') || '-';

        document.querySelector('#sp-review-questions span').textContent = generatedQuestions.length;
        
        var totalPts = 0;
        form.querySelectorAll('[name^="prep_point_"]').forEach(function(f) { totalPts += parseInt(f.value) || 0; });
        document.querySelector('#sp-review-points span').textContent = totalPts;
    }

    // Save/Publish
    function saveLesson(status) {
        var formData = new FormData(form);
        var action = lessonId ? 'sp_lesson_update' : 'sp_lesson_create';
        formData.set('action', action);
        formData.set('status', status);

        // Always send lesson_id so sp_lesson_update works
        if (lessonId) formData.set('lesson_id', lessonId);

        // Build quiz config
        var quizConfig = {
            num_questions: parseInt(formData.get('num_questions')) || 10,
            points: parseInt(formData.get('points')) || 50,
            allow_retake: formData.get('allow_retake') === '1',
            passing_percent: parseInt(formData.get('passing_percent')) || 60,
        };
        formData.set('quiz_config', JSON.stringify(quizConfig));

        // Build prep points config
        var pointsConfig = {};
        var pointFields = form.querySelectorAll('[name^="prep_point_"]');
        pointFields.forEach(function(f) {
            var key = f.name.replace('prep_point_', '');
            pointsConfig[key] = parseInt(f.value) || 0;
        });
        formData.set('prep_points_config', JSON.stringify(pointsConfig));

        // Build AI detection config (include `enabled` — its absence previously
        // disabled detection for every wizard-created lesson)
        var aiEnabledEl = form.querySelector('[name="ai_enabled"]');
        var aiConfig = {
            enabled: aiEnabledEl ? aiEnabledEl.checked : true,
            threshold: parseInt(formData.get('ai_threshold')) || 70,
            penalty_type: formData.get('ai_penalty_type') || 'percentage',
            penalty_amount: parseInt(formData.get('ai_penalty_amount')) || 50,
        };
        formData.set('ai_detection_config', JSON.stringify(aiConfig));

        // Build grades array
        var gradesArr = [];
        form.querySelectorAll('[name="grades[]"]:checked').forEach(function(cb) { gradesArr.push(parseInt(cb.value)); });
        formData.set('grades', JSON.stringify(gradesArr));

        // Only send access if the admin actually opened the access step;
        // otherwise omit it so an unrelated save never wipes existing access.
        // Access is collected per target grade: [{grade, user_ids:[...]}, ...].
        if (accessLoaded) {
            var accessByGrade = [];
            form.querySelectorAll('.sp-access-grade-panel').forEach(function(panel) {
                var g = parseInt(panel.dataset.grade);
                var ids = [];
                panel.querySelectorAll('input[type="checkbox"]:checked').forEach(function(cb) {
                    ids.push(parseInt(cb.value));
                });
                accessByGrade.push({ grade: g, user_ids: ids });
            });
            formData.set('access_users', JSON.stringify(accessByGrade));
        } else {
            formData.delete('access_users');
        }

        // Clean up
        formData.delete('num_questions');
        formData.delete('points');
        formData.delete('allow_retake');
        formData.delete('passing_percent');
        formData.delete('ai_threshold');
        formData.delete('ai_penalty_type');
        formData.delete('ai_penalty_amount');
        formData.delete('ai_enabled');
        formData.delete('nonce');
        formData.set('nonce', ADMIN_NONCE);

        // Drop the PDF binaries. They are uploaded by sp_lesson_pdf_upload and already
        // stored against the lesson, but the file inputs live inside this form, so
        // FormData(form) was re-sending every selected PDF on every save. That pushed the
        // publish POST past post_max_size, at which point PHP throws away $_POST entirely
        // and admin-ajax answers "0" -- the real cause of "فشل الاتصال بالخادم".
        form.querySelectorAll('input[type="file"]').forEach(function(input) {
            formData.delete(input.name);
        });

        return fetch(spApp.ajaxUrl, {
            method: 'POST',
            body: formData,
        }).then(readJson);
    }

    // Save draft (also persists any generated/edited questions)
    document.getElementById('sp-save-draft-btn').addEventListener('click', function() {
        var DRAFT_LABEL = '💾 حفظ كمسودة';
        lockSaving('sp-save-draft-btn', '⏳ جاري الحفظ...');

        saveLesson('draft').then(function(resp) {
            if (!resp.success) {
                alert(errorMessage(resp, 'فشل الحفظ'));
                unlockSaving('sp-save-draft-btn', DRAFT_LABEL);
                return;
            }
            setLessonId(resp.data.lesson.id);

            // The lesson itself is saved by this point; only the questions can still fail,
            // so say that rather than claiming the whole save died.
            return persistQuestions(lessonId).then(function() {
                alert('تم حفظ الدرس كمسودة!');
                window.location.href = '<?php echo home_url('/app/admin/lesson-prep'); ?>';
            }).catch(function(e) {
                alert('تم حفظ الدرس، لكن فشل حفظ الأسئلة: ' + (e.message || ''));
                unlockSaving('sp-save-draft-btn', DRAFT_LABEL);
            });
        }).catch(function(e) {
            alert(e.message || 'فشل الاتصال بالخادم');
            unlockSaving('sp-save-draft-btn', DRAFT_LABEL);
        });
    });

    // Publish (persists the lesson, then the edited/generated questions)
    document.getElementById('sp-publish-btn').addEventListener('click', function() {
        var grades = form.querySelectorAll('[name="grades[]"]:checked');
        if (grades.length === 0) {
            alert('يرجى اختيار صف واحد على الأقل');
            return;
        }

        var PUBLISH_LABEL = '✅ نشر الدرس';
        lockSaving('sp-publish-btn', '⏳ جاري النشر...');

        saveLesson('published').then(function(resp) {
            if (!resp.success) {
                alert(errorMessage(resp, 'فشل النشر'));
                unlockSaving('sp-publish-btn', PUBLISH_LABEL);
                return;
            }
            setLessonId(resp.data.lesson.id);

            // Published already. A questions failure here must not send the admin back to
            // press Publish again -- that would create a second lesson.
            return persistQuestions(lessonId).then(function() {
                window.location.href = '<?php echo home_url('/app/admin/lesson-prep'); ?>';
            }).catch(function(e) {
                alert('تم نشر الدرس، لكن فشل حفظ الأسئلة: ' + (e.message || ''));
                unlockSaving('sp-publish-btn', PUBLISH_LABEL);
            });
        }).catch(function(e) {
            alert(e.message || 'فشل الاتصال بالخادم');
            unlockSaving('sp-publish-btn', PUBLISH_LABEL);
        });
    });

    // Generate questions
    var genBtn = document.getElementById('sp-generate-questions-btn');
    var genMoreBtn = document.getElementById('sp-generate-more-btn');
    var genStatus = document.getElementById('sp-generation-status');
    var questionsEditor = document.getElementById('sp-questions-editor');
    var lessonTextField = document.getElementById('sp-lesson-text-input');

    genBtn.addEventListener('click', function() {
        genBtn.disabled = true;
        genBtn.textContent = '⏳ جاري التوليد...';
        genStatus.style.display = '';
        genStatus.innerHTML = '<span style="color:#D97706;">⏳ جاري توليد الأسئلة بالذكاء الاصطناعي...</span>';

        function doGenerate() {
            var formData = new FormData();
            formData.append('nonce', '<?php echo wp_create_nonce('sp_admin_nonce'); ?>');
            formData.append('action', 'sp_lesson_quiz_generate');
            formData.append('lesson_id', lessonId);
            formData.append('num_questions', parseInt(form.querySelector('[name="num_questions"]').value) || 10);

            if (lessonTextField && lessonTextField.value.trim()) {
                formData.append('text_source', lessonTextField.value.trim());
            }

            fetch(spApp.ajaxUrl, { method: 'POST', body: formData })
            .then(readJson)
            .then(function(resp) {
                if (resp.success) {
                    generatedQuestions = resp.data.questions || [];
                    renderQuestionsEditor(generatedQuestions);
                    genStatus.innerHTML = '<span style="color:#059669;">✅ تم توليد ' + generatedQuestions.length + ' سؤال بنجاح!</span>';
                    genMoreBtn.style.display = '';
                } else {
                    genStatus.innerHTML = '<span style="color:#DC2626;">❌ ' + errorMessage(resp, 'فشل التوليد') + '</span>';
                }
            })
            .catch(function() {
                genStatus.innerHTML = '<span style="color:#DC2626;">❌ فشل الاتصال</span>';
            })
            .finally(function() {
                genBtn.disabled = false;
                genBtn.textContent = '🔄 إعادة التوليد';
            });
        }

        // Auto-save draft first if no lessonId yet
        if (!lessonId) {
            genStatus.innerHTML = '<span style="color:#D97706;">⏳ جاري حفظ الدرس أولاً...</span>';
            saveLesson('draft').then(function(resp) {
                if (resp.success) {
                    lessonId = resp.data.lesson.id;
                    form.dataset.lessonId = lessonId;
                    doGenerate();
                } else {
                    genStatus.innerHTML = '<span style="color:#DC2626;">❌ ' + errorMessage(resp, 'فشل حفظ الدرس') + '</span>';
                    genBtn.disabled = false;
                    genBtn.textContent = '🤖 توليد الأسئلة';
                }
            }).catch(function() {
                genStatus.innerHTML = '<span style="color:#DC2626;">❌ فشل الاتصال</span>';
                genBtn.disabled = false;
                genBtn.textContent = '🤖 توليد الأسئلة';
            });
        } else {
            doGenerate();
        }
    });

    genMoreBtn.addEventListener('click', function() {
        genMoreBtn.disabled = true;
        genMoreBtn.textContent = '⏳...';
        var formData = new FormData();
        formData.append('nonce', '<?php echo wp_create_nonce('sp_admin_nonce'); ?>');
        formData.append('action', 'sp_lesson_quiz_generate');
        formData.append('lesson_id', lessonId || 0);
        formData.append('num_questions', 5);

        // Also send text override for consistency
        if (lessonTextField && lessonTextField.value.trim()) {
            formData.append('text_source', lessonTextField.value.trim());
        }

        fetch(spApp.ajaxUrl, { method: 'POST', body: formData })
        .then(readJson)
        .then(function(resp) {
            if (resp.success) {
                var more = resp.data.questions || [];
                generatedQuestions = generatedQuestions.concat(more);
                renderQuestionsEditor(generatedQuestions);
                genStatus.innerHTML = '<span style="color:#059669;">✅ تمت إضافة ' + more.length + ' أسئلة</span>';
            }
        })
        .finally(function() {
            genMoreBtn.disabled = false;
            genMoreBtn.textContent = '➕ توليد أسئلة إضافية';
        });
    });

    function renderQuestionsEditor(questions) {
        var html = '';
        questions.forEach(function(q, idx) {
            var options = q.options || [];
            html += '<div class="sp-question-editor-row" data-index="' + idx + '" style="padding:8px;margin-bottom:8px;border:1px solid var(--sp-border);border-radius:8px;background:var(--sp-bg-secondary);">';
            html += '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">';
            html += '<strong style="font-size:0.8rem;">سؤال ' + (idx + 1) + '</strong>';
            html += '<span style="font-size:0.7rem;color:var(--sp-text-tertiary);">' + (q.difficulty || 'medium') + '</span>';
            html += '</div>';
            html += '<input type="text" class="sp-q-text" value="' + (q.question_text || '').replace(/"/g, '&quot;') + '" style="width:100%;padding:6px;border:1px solid var(--sp-border);border-radius:4px;font-size:0.85rem;margin-bottom:4px;">';
            
            if (q.question_type === 'multiple_choice' && options.length > 0) {
                options.forEach(function(opt, oidx) {
                    var optText = typeof opt === 'string' ? opt : (opt.text || '');
                    var isCorrect = typeof opt === 'object' && opt.is_correct;
                    html += '<div style="display:flex;align-items:center;gap:4px;margin-bottom:2px;">';
                    html += '<input type="radio" name="correct_' + idx + '" value="' + oidx + '" ' + (isCorrect ? 'checked' : '') + '>';
                    html += '<input type="text" value="' + optText.replace(/"/g, '&quot;') + '" style="flex:1;padding:4px;border:1px solid var(--sp-border);border-radius:4px;font-size:0.8rem;">';
                    html += '</div>';
                });
            }
            html += '<button type="button" class="sp-delete-question-btn sp-btn sp-btn-outline sp-btn-xs" style="color:#DC2626;border-color:#DC2626;font-size:0.7rem;margin-top:4px;">🗑️ حذف</button>';
            html += '</div>';
        });
        questionsEditor.innerHTML = html;

        // Attach delete handlers
        questionsEditor.querySelectorAll('.sp-delete-question-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var row = btn.closest('.sp-question-editor-row');
                var idx = parseInt(row.dataset.index);
                generatedQuestions.splice(idx, 1);
                renderQuestionsEditor(generatedQuestions);
            });
        });
    }

    // Read the question editor back out of the DOM, overlaying manual edits on
    // the generated metadata (type/difficulty/explanation) so nothing is lost.
    function collectQuestions() {
        var rows = questionsEditor.querySelectorAll('.sp-question-editor-row');
        var out = [];
        rows.forEach(function(row) {
            var idx = parseInt(row.dataset.index);
            var base = (!isNaN(idx) && generatedQuestions[idx])
                ? Object.assign({}, generatedQuestions[idx])
                : { question_type: 'multiple_choice', difficulty: 'medium', explanation: '' };

            var textEl = row.querySelector('.sp-q-text');
            if (textEl) base.question_text = textEl.value;

            var radios = row.querySelectorAll('input[type="radio"]');
            if (radios.length > 0) {
                var options = [];
                var correctIdx = 0;
                radios.forEach(function(radio, oi) {
                    var ti = radio.parentElement.querySelector('input[type="text"]');
                    options.push({ text: ti ? ti.value : '', is_correct: radio.checked });
                    if (radio.checked) correctIdx = oi;
                });
                base.options = options;
                base.correct_answer_index = correctIdx;
            }
            out.push(base);
        });
        return out;
    }

    // Persist the current questions to the lesson (used by both draft + publish).
    function persistQuestions(lid) {
        generatedQuestions = collectQuestions();
        if (!lid || generatedQuestions.length === 0) return Promise.resolve({ success: true });

        var qfd = new FormData();
        qfd.append('nonce', ADMIN_NONCE);
        qfd.append('action', 'sp_lesson_quiz_save');
        qfd.append('lesson_id', lid);
        qfd.append('questions', JSON.stringify(generatedQuestions));

        var nq = form.querySelector('[name="num_questions"]');
        var pts = form.querySelector('[name="points"]');
        var pass = form.querySelector('[name="passing_percent"]');
        var retake = form.querySelector('[name="allow_retake"]');
        if (nq) qfd.append('num_questions', parseInt(nq.value) || 10);
        if (pts) qfd.append('points', parseInt(pts.value) || 50);
        if (pass) qfd.append('passing_percent', parseInt(pass.value) || 60);
        qfd.append('allow_retake', (retake && retake.checked) ? 1 : 0);

        return fetch(spApp.ajaxUrl, { method: 'POST', body: qfd })
            .then(readJson)
            .then(function(resp) {
                if (!resp.success) {
                    throw new Error(errorMessage(resp, 'فشل حفظ الأسئلة'));
                }
                return resp;
            });
    }

    // PDF upload handler
    document.querySelectorAll('.sp-upload-pdf-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var grade = btn.dataset.grade;
            var fileInput = grade === 'all' 
                ? form.querySelector('[name="pdf_all"]') 
                : form.querySelector('[name="pdf_grade_' + grade + '"]');
            
            if (!fileInput || !fileInput.files[0]) {
                alert('يرجى اختيار ملف PDF أولاً');
                return;
            }

            if (!lessonId) {
                alert('يرجى حفظ الدرس كمسودة أولاً قبل رفع PDF');
                return;
            }

            btn.disabled = true;
            btn.textContent = '⏳...';

            var fd = new FormData();
            fd.append('nonce', '<?php echo wp_create_nonce('sp_admin_nonce'); ?>');
            fd.append('action', 'sp_lesson_pdf_upload');
            fd.append('lesson_id', lessonId);
            fd.append('grade_key', grade);
            fd.append('pdf_file', fileInput.files[0]);

            fetch(spApp.ajaxUrl, { method: 'POST', body: fd })
            .then(readJson)
            .then(function(resp) {
                if (resp.success) {
                    btn.textContent = '✅ تم';
                    btn.style.color = '#059669';
                } else {
                    btn.textContent = '❌ فشل';
                    alert(errorMessage(resp, 'فشل الرفع'));
                }
            })
            .catch(function(e) {
                alert(e.message || 'فشل رفع الملف');
                btn.textContent = '⬆️ رفع';
            })
            .finally(function() { btn.disabled = false; });
        });
    });

    var accessLoadedSig = null; // signature of the grade-set the access UI was built for

    // Builds the access step as one panel per selected target grade, so the
    // admin assigns members to EACH year. Stores [{grade, user_ids:[...]}].
    function loadUsersByGrade() {
        var container = document.getElementById('sp-access-user-list');

        // Target grades chosen in step 1 ("الصفوف المستهدفة").
        var selectedGrades = [];
        form.querySelectorAll('[name="grades[]"]:checked').forEach(function(cb) { selectedGrades.push(parseInt(cb.value)); });
        selectedGrades.sort(function(a, b) { return a - b; });
        var sig = selectedGrades.join(',');

        // Don't rebuild (and lose in-progress selections) if the grade set is
        // unchanged since the last render.
        if (accessLoaded && sig === accessLoadedSig) return;

        if (selectedGrades.length === 0) {
            container.innerHTML = '<p style="font-size:0.85rem;color:#B45309;background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:10px;">⚠️ اختر "الصفوف المستهدفة" في خطوة المعلومات أولاً، ثم ارجع هنا لإضافة الأعضاء لكل صف.</p>';
            accessLoaded = false;
            accessLoadedSig = sig;
            return;
        }

        container.innerHTML = '<p style="font-size:0.85rem;color:var(--sp-text-tertiary);">⏳ جاري تحميل الأعضاء...</p>';

        // Edit mode: fetch existing access -> map grade -> {user_id: true}
        var accessPromise;
        if (IS_EDIT && lessonId) {
            var afd = new FormData();
            afd.append('nonce', ADMIN_NONCE);
            afd.append('action', 'sp_lesson_access_get');
            afd.append('lesson_id', lessonId);
            accessPromise = fetch(spApp.ajaxUrl, { method: 'POST', body: afd })
                .then(readJson)
                .then(function(resp) {
                    var map = {};
                    if (resp.success && resp.data.access) {
                        resp.data.access.forEach(function(a) {
                            var g = parseInt(a.grade) || 0;
                            (map[g] = map[g] || {})[parseInt(a.user_id)] = true;
                        });
                    }
                    return map;
                })
                .catch(function() { return null; });
        } else {
            accessPromise = Promise.resolve(null);
        }

        accessPromise.then(function(accessMap) {
            var fd = new FormData();
            fd.append('nonce', ADMIN_NONCE);
            fd.append('action', 'sp_lesson_users_by_grade');

            fetch(spApp.ajaxUrl, { method: 'POST', body: fd })
            .then(readJson)
            .then(function(resp) {
                if (!resp.success) {
                    container.innerHTML = '<p style="color:#DC2626;font-size:0.85rem;">❌ فشل تحميل الأعضاء</p>';
                    return;
                }
                var allUsers = resp.data.users_by_grade['all'] || [];
                if (allUsers.length === 0) {
                    container.innerHTML = '<p style="font-size:0.85rem;color:var(--sp-text-tertiary);">لا يوجد أعضاء مسجلون بعد</p>';
                    accessLoaded = true;
                    accessLoadedSig = sig;
                    return;
                }

                var html = '';
                selectedGrades.forEach(function(g) {
                    var allowed = accessMap ? (accessMap[g] || {}) : null; // null => create mode (none checked)
                    var count = 0;
                    var chips = '';
                    allUsers.forEach(function(u) {
                        var checked = allowed ? !!allowed[parseInt(u.id)] : false;
                        if (checked) count++;
                        var label = ((u.name_ar || u.display_name) + (u.church ? ' — ' + u.church : '')).toLowerCase().replace(/"/g, '');
                        var name = (u.name_ar || u.display_name).replace(/</g, '&lt;');
                        chips += '<label data-name="' + label + '" style="font-size:0.8rem;cursor:pointer;padding:4px 10px;border:1px solid var(--sp-border);border-radius:16px;display:flex;align-items:center;gap:5px;background:var(--sp-bg-secondary);">';
                        chips += '<input type="checkbox" name="access_grade_' + g + '[]" value="' + u.id + '"' + (checked ? ' checked' : '') + '> ';
                        chips += '<span>' + name + '</span>';
                        chips += '</label>';
                    });

                    html += '<div class="sp-access-grade-panel" data-grade="' + g + '" style="border:1px solid var(--sp-border);border-radius:10px;margin-bottom:10px;overflow:hidden;">';
                    html += '<div onclick="spToggleGrade(' + g + ')" style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:10px 12px;background:var(--sp-bg-secondary);cursor:pointer;">';
                    html += '<strong style="font-size:0.9rem;">📚 الصف ' + g + ' <span class="sp-grade-count" style="font-weight:400;color:var(--sp-text-tertiary);font-size:0.8rem;">(' + count + ' عضو)</span></strong>';
                    html += '<span class="sp-grade-caret" style="font-size:0.8rem;color:var(--sp-text-tertiary);">▾</span>';
                    html += '</div>';
                    html += '<div class="sp-grade-body" style="padding:10px 12px;">';
                    html += '<div style="margin-bottom:8px;display:flex;gap:6px;align-items:center;flex-wrap:wrap;">';
                    html += '<input type="text" placeholder="🔍 بحث..." oninput="spFilterGrade(' + g + ', this.value)" style="flex:1;min-width:120px;padding:6px 10px;border:1px solid var(--sp-border);border-radius:8px;font-family:inherit;font-size:0.8rem;">';
                    html += '<button type="button" onclick="spSelectGrade(' + g + ', true)" style="font-size:0.72rem;padding:5px 9px;border:1px solid var(--sp-border);border-radius:8px;background:none;cursor:pointer;">تحديد الكل</button>';
                    html += '<button type="button" onclick="spSelectGrade(' + g + ', false)" style="font-size:0.72rem;padding:5px 9px;border:1px solid var(--sp-border);border-radius:8px;background:none;cursor:pointer;">إلغاء الكل</button>';
                    html += '</div>';
                    html += '<div class="sp-grade-members" style="display:flex;flex-wrap:wrap;gap:6px;max-height:220px;overflow-y:auto;">' + chips + '</div>';
                    html += '</div></div>';
                });

                container.innerHTML = html;
                accessLoaded = true;
                accessLoadedSig = sig;

                // Keep each panel's "(N عضو)" badge live as boxes are toggled.
                container.querySelectorAll('.sp-access-grade-panel').forEach(function(panel) {
                    panel.addEventListener('change', function() { spUpdateGradeCount(parseInt(panel.dataset.grade)); });
                });
            })
            .catch(function() {
                container.innerHTML = '<p style="color:#DC2626;font-size:0.85rem;">❌ فشل الاتصال</p>';
            });
        });
    }

    function spGradePanel(grade) {
        return document.querySelector('.sp-access-grade-panel[data-grade="' + grade + '"]');
    }

    // Inline onclick/oninput handlers run in global scope, so expose these on window.
    window.spToggleGrade = function(grade) {
        var panel = spGradePanel(grade); if (!panel) return;
        var body = panel.querySelector('.sp-grade-body');
        var caret = panel.querySelector('.sp-grade-caret');
        var collapsed = body.style.display === 'none';
        body.style.display = collapsed ? '' : 'none';
        if (caret) caret.textContent = collapsed ? '▾' : '▸';
    };
    window.spSelectGrade = function(grade, checked) {
        var panel = spGradePanel(grade); if (!panel) return;
        panel.querySelectorAll('.sp-grade-members label').forEach(function(lbl) {
            if (lbl.style.display !== 'none') {
                var cb = lbl.querySelector('input[type="checkbox"]');
                if (cb) cb.checked = checked;
            }
        });
        spUpdateGradeCount(grade);
    };
    window.spFilterGrade = function(grade, q) {
        q = (q || '').toLowerCase();
        var panel = spGradePanel(grade); if (!panel) return;
        panel.querySelectorAll('.sp-grade-members label').forEach(function(lbl) {
            lbl.style.display = (q === '' || (lbl.dataset.name || '').includes(q)) ? '' : 'none';
        });
    };
    window.spUpdateGradeCount = function(grade) {
        var panel = spGradePanel(grade); if (!panel) return;
        var n = panel.querySelectorAll('input[type="checkbox"]:checked').length;
        var badge = panel.querySelector('.sp-grade-count');
        if (badge) badge.textContent = '(' + n + ' عضو)';
    };

    // ── Source tabs (PDF / Text) ──
    var sourceTabs = document.querySelectorAll('.sp-source-tab');
    var sourcePdfPanel = document.getElementById('sp-source-pdf');
    var sourceTextPanel = document.getElementById('sp-source-text');

    sourceTabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            var target = tab.dataset.tab;
            sourceTabs.forEach(function(t) {
                t.style.borderBottomColor = 'transparent';
                t.style.color = 'var(--sp-text-secondary)';
                t.style.fontWeight = '400';
                t.classList.remove('active');
            });
            tab.style.borderBottomColor = 'var(--sp-primary)';
            tab.style.color = 'var(--sp-primary)';
            tab.style.fontWeight = '600';
            tab.classList.add('active');

            if (sourcePdfPanel) sourcePdfPanel.style.display = target === 'pdf' ? '' : 'none';
            if (sourceTextPanel) sourceTextPanel.style.display = target === 'text' ? '' : 'none';
        });
    });

    // ── Save manual text button ──
    var saveTextBtn = document.getElementById('sp-save-text-btn');
    var textStatus = document.getElementById('sp-text-save-status');

    if (saveTextBtn) {
        saveTextBtn.addEventListener('click', function() {
            var textVal = lessonTextField ? lessonTextField.value.trim() : '';
            if (!textVal) {
                textStatus.textContent = 'يرجى كتابة نص أولاً';
                return;
            }
            if (!lessonId) {
                alert('يرجى حفظ الدرس كمسودة أولاً قبل حفظ النص');
                return;
            }

            saveTextBtn.disabled = true;
            saveTextBtn.textContent = '⏳...';
            textStatus.textContent = '';

            var fd = new FormData();
            fd.append('nonce', '<?php echo wp_create_nonce('sp_admin_nonce'); ?>');
            fd.append('action', 'sp_lesson_text_save');
            fd.append('lesson_id', lessonId);
            fd.append('text', textVal);

            fetch(spApp.ajaxUrl, { method: 'POST', body: fd })
            .then(readJson)
            .then(function(resp) {
                if (resp.success) {
                    textStatus.textContent = '✅ تم حفظ ' + resp.data.text_length + ' حرف';
                    textStatus.style.color = '#059669';
                } else {
                    textStatus.textContent = '❌ ' + errorMessage(resp, 'فشل');
                    textStatus.style.color = '#DC2626';
                }
            })
            .catch(function() {
                textStatus.textContent = '❌ فشل الاتصال';
                textStatus.style.color = '#DC2626';
            })
            .finally(function() {
                saveTextBtn.disabled = false;
                saveTextBtn.textContent = '💾 حفظ النص';
            });
        });
    }

    // Initialize
    showStep(0);
    // Re-render any existing questions through the JS editor so manual edits are
    // captured on save (the PHP-rendered rows are replaced by editable ones).
    if (generatedQuestions.length > 0) {
        renderQuestionsEditor(generatedQuestions);
        if (genMoreBtn) genMoreBtn.style.display = '';
    }
})();
</script>
