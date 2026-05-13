<?php
/**
 * Saint Porphyrius - Admin Lesson Prep Settings
 * Global configuration for the lesson preparation system
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
$section_labels = SP_Lesson_Prep::get_section_labels();
?>

<div class="sp-unified-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/admin/lesson-prep'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('إعدادات نظام التحضير', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-spacer"></div>
    </div>
</div>

<main class="sp-page-content">
    <div style="padding:var(--sp-space-md);max-width:700px;margin:0 auto;">

        <form id="sp-lesson-config-form">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('sp_admin_nonce'); ?>">
            <input type="hidden" name="action" value="sp_lesson_config_update">

            <!-- Enable/Disable -->
            <div class="sp-card" style="padding:var(--sp-space-md);margin-bottom:var(--sp-space-sm);">
                <h3 style="margin:0 0 8px 0;">🔧 <?php _e('تفعيل النظام', 'saint-porphyrius'); ?></h3>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="prep_enabled" value="1" <?php echo !empty($config['prep_enabled']) ? 'checked' : ''; ?>>
                    <span><?php _e('تفعيل نظام تحضير الدروس', 'saint-porphyrius'); ?></span>
                </label>
            </div>

            <!-- Section Points -->
            <div class="sp-card" style="padding:var(--sp-space-md);margin-bottom:var(--sp-space-sm);">
                <h3 style="margin:0 0 12px 0;">⭐ <?php _e('توزيع النقاط الافتراضي', 'saint-porphyrius'); ?></h3>
                <?php foreach ($config['section_points'] as $sk => $sp): ?>
                    <label style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;font-size:0.85rem;">
                        <span><?php echo esc_html($section_labels[$sk] ?? $sk); ?></span>
                        <input type="number" name="section_points_<?php echo $sk; ?>" value="<?php echo $sp; ?>" min="0" max="100"
                            style="width:70px;padding:6px;border:1px solid var(--sp-border);border-radius:6px;text-align:center;">
                    </label>
                <?php endforeach; ?>
            </div>

            <!-- AI Detection -->
            <div class="sp-card" style="padding:var(--sp-space-md);margin-bottom:var(--sp-space-sm);">
                <h3 style="margin:0 0 12px 0;">🤖 <?php _e('إعدادات كشف محتوى AI', 'saint-porphyrius'); ?></h3>
                <label style="display:flex;align-items:center;gap:8px;margin-bottom:12px;cursor:pointer;">
                    <input type="checkbox" name="ai_detection_enabled" value="1" <?php echo !empty($config['ai_detection']['enabled']) ? 'checked' : ''; ?>>
                    <span><?php _e('تفعيل كشف محتوى AI', 'saint-porphyrius'); ?></span>
                </label>
                <label style="display:block;margin-bottom:12px;">
                    <span style="font-size:0.85rem;"><?php _e('حد الاحتمال للتصنيف كـ AI (%)', 'saint-porphyrius'); ?></span>
                    <input type="number" name="ai_detection_threshold" value="<?php echo $config['ai_detection']['threshold'] ?? 70; ?>" min="0" max="100"
                        style="width:100%;padding:8px;border:1px solid var(--sp-border);border-radius:6px;margin-top:4px;">
                </label>
                <label style="display:block;margin-bottom:12px;">
                    <span style="font-size:0.85rem;"><?php _e('نوع العقوبة', 'saint-porphyrius'); ?></span>
                    <select name="ai_detection_penalty_type" style="width:100%;padding:8px;border:1px solid var(--sp-border);border-radius:6px;margin-top:4px;">
                        <option value="percentage" <?php echo ($config['ai_detection']['penalty_type'] ?? '') === 'percentage' ? 'selected' : ''; ?>><?php _e('نسبة مئوية من نقاط القسم', 'saint-porphyrius'); ?></option>
                        <option value="fixed" <?php echo ($config['ai_detection']['penalty_type'] ?? '') === 'fixed' ? 'selected' : ''; ?>><?php _e('قيمة ثابتة', 'saint-porphyrius'); ?></option>
                    </select>
                </label>
                <label style="display:block;margin-bottom:12px;">
                    <span style="font-size:0.85rem;"><?php _e('قيمة العقوبة', 'saint-porphyrius'); ?></span>
                    <input type="number" name="ai_detection_penalty_amount" value="<?php echo $config['ai_detection']['penalty_amount'] ?? 50; ?>" min="0"
                        style="width:100%;padding:8px;border:1px solid var(--sp-border);border-radius:6px;margin-top:4px;">
                </label>
                <label style="display:flex;align-items:center;gap:8px;margin-bottom:12px;cursor:pointer;">
                    <input type="checkbox" name="ai_detection_show_to_user" value="1" <?php echo !empty($config['ai_detection']['show_to_user']) ? 'checked' : ''; ?>>
                    <span><?php _e('إظهار نتيجة الكشف للمستخدم', 'saint-porphyrius'); ?></span>
                </label>
            </div>

            <!-- Quiz Defaults -->
            <div class="sp-card" style="padding:var(--sp-space-md);margin-bottom:var(--sp-space-sm);">
                <h3 style="margin:0 0 12px 0;">📝 <?php _e('إعدادات الاختبار الافتراضية', 'saint-porphyrius'); ?></h3>
                <label style="display:block;margin-bottom:12px;">
                    <span style="font-size:0.85rem;"><?php _e('عدد الأسئلة الافتراضي', 'saint-porphyrius'); ?></span>
                    <input type="number" name="quiz_num_questions" value="<?php echo $config['quiz_defaults']['num_questions'] ?? 10; ?>" min="3" max="100"
                        style="width:100%;padding:8px;border:1px solid var(--sp-border);border-radius:6px;margin-top:4px;">
                </label>
                <label style="display:block;margin-bottom:12px;">
                    <span style="font-size:0.85rem;"><?php _e('نقاط الاختبار الافتراضية', 'saint-porphyrius'); ?></span>
                    <input type="number" name="quiz_points" value="<?php echo $config['quiz_defaults']['points'] ?? 50; ?>" min="0"
                        style="width:100%;padding:8px;border:1px solid var(--sp-border);border-radius:6px;margin-top:4px;">
                </label>
                <label style="display:block;margin-bottom:12px;">
                    <span style="font-size:0.85rem;"><?php _e('نسبة النجاح (%)', 'saint-porphyrius'); ?></span>
                    <input type="number" name="quiz_passing_percent" value="<?php echo $config['quiz_defaults']['passing_percent'] ?? 60; ?>" min="0" max="100"
                        style="width:100%;padding:8px;border:1px solid var(--sp-border);border-radius:6px;margin-top:4px;">
                </label>
            </div>

            <!-- Prep Settings -->
            <div class="sp-card" style="padding:var(--sp-space-md);margin-bottom:var(--sp-space-sm);">
                <h3 style="margin:0 0 12px 0;">⚙️ <?php _e('إعدادات التحضير', 'saint-porphyrius'); ?></h3>
                <label style="display:flex;align-items:center;gap:8px;margin-bottom:12px;cursor:pointer;">
                    <input type="checkbox" name="prep_required_quiz" value="1" <?php echo !empty($config['prep_required_quiz']) ? 'checked' : ''; ?>>
                    <span><?php _e('اجتياز الاختبار مطلوب قبل التحضير', 'saint-porphyrius'); ?></span>
                </label>
                <label style="display:block;margin-bottom:12px;">
                    <span style="font-size:0.85rem;"><?php _e('الحد الأقصى لمحاولات التحضير', 'saint-porphyrius'); ?></span>
                    <input type="number" name="prep_max_submissions" value="<?php echo $config['prep_max_submissions'] ?? 3; ?>" min="1" max="10"
                        style="width:100%;padding:8px;border:1px solid var(--sp-border);border-radius:6px;margin-top:4px;">
                </label>
            </div>

            <button type="submit" class="sp-btn sp-btn-primary sp-btn-block sp-btn-lg" style="margin-bottom:var(--sp-space-xl);">
                💾 <?php _e('حفظ الإعدادات', 'saint-porthyrius'); ?>
            </button>
        </form>
    </div>
</main>

<script>
document.getElementById('sp-lesson-config-form').addEventListener('submit', function(e) {
    e.preventDefault();

    var btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = '⏳ جاري الحفظ...';

    var formData = new FormData(this);

    // Build section_points JSON
    var sectionPoints = {};
    formData.forEach(function(val, key) {
        if (key.startsWith('section_points_')) {
            var sk = key.replace('section_points_', '');
            sectionPoints[sk] = parseInt(val) || 0;
        }
    });
    formData.set('section_points', JSON.stringify(sectionPoints));

    // Build ai_detection JSON
    var aiDetection = {
        enabled: formData.get('ai_detection_enabled') === '1',
        threshold: parseInt(formData.get('ai_detection_threshold')) || 70,
        penalty_type: formData.get('ai_detection_penalty_type') || 'percentage',
        penalty_amount: parseInt(formData.get('ai_detection_penalty_amount')) || 50,
        show_to_user: formData.get('ai_detection_show_to_user') === '1',
    };
    formData.set('ai_detection', JSON.stringify(aiDetection));

    // Build quiz_defaults JSON
    var quizDefaults = {
        num_questions: parseInt(formData.get('quiz_num_questions')) || 10,
        points: parseInt(formData.get('quiz_points')) || 50,
        allow_retake: false,
        passing_percent: parseInt(formData.get('quiz_passing_percent')) || 60,
    };
    formData.set('quiz_defaults', JSON.stringify(quizDefaults));

    // Clean up individual fields
    ['section_points_', 'ai_detection_', 'quiz_'].forEach(function(prefix) {
        var keys = [];
        formData.forEach(function(v, k) { if (k.indexOf(prefix) === 0) keys.push(k); });
        keys.forEach(function(k) { formData.delete(k); });
    });

    fetch(spApp.ajaxUrl, { method: 'POST', body: formData })
    .then(function(r) { return r.json(); })
    .then(function(resp) {
        if (resp.success) {
            btn.textContent = '✅ تم الحفظ';
            btn.style.background = '#059669';
            setTimeout(function() {
                btn.textContent = '💾 حفظ الإعدادات';
                btn.style.background = '';
            }, 2000);
        } else {
            alert(resp.data.message || 'فشل الحفظ');
            btn.disabled = false;
            btn.textContent = '💾 حفظ الإعدادات';
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.textContent = '💾 حفظ الإعدادات';
    });
});
</script>
