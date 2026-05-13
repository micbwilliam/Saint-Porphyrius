<?php
/**
 * Saint Porphyrius - View Preparation (User-Facing)
 * Shows a submitted/draft preparation
 *
 * @since 6.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$handler = SP_Lesson_Prep::get_instance();
$prep_id = absint(get_query_var('sp_lesson_id', 0)); // Reusing lesson_id var for prep_id

if (!$prep_id) {
    wp_safe_redirect(home_url('/app/lesson-prep'));
    exit;
}

$prep = $handler->get_preparation($prep_id);
if (!$prep) {
    echo '<main class="sp-page-content"><div class="sp-card" style="text-align:center;padding:var(--sp-space-xl);"><p>التحضير غير موجود</p></div></main>';
    return;
}

if ($prep->user_id != $current_user->ID && !current_user_can('manage_options')) {
    echo '<main class="sp-page-content"><div class="sp-card" style="text-align:center;padding:var(--sp-space-xl);"><p>ليس لديك صلاحية</p></div></main>';
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
?>

<div class="sp-unified-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/lesson-prep'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('عرض التحضير', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-spacer"></div>
    </div>
</div>

<main class="sp-page-content has-bottom-nav">
    <div style="padding:var(--sp-space-md);max-width:700px;margin:0 auto;">

        <!-- Status Card -->
        <div class="sp-card" style="padding:var(--sp-space-md);margin-bottom:var(--sp-space-md);text-align:center;">
            <div style="font-size:2.5rem;margin-bottom:4px;">
                <?php
                $status_icons = array(
                    'draft' => '📝', 'submitted' => '📤', 'under_review' => '🔍',
                    'approved' => '✅', 'needs_revision' => '✏️'
                );
                echo $status_icons[$prep->status] ?? '📋';
                ?>
            </div>
            <h2 style="margin:0;font-size:1.1rem;"><?php echo esc_html($prep->lesson_title_ar); ?></h2>
            <p style="margin:4px 0;font-size:0.85rem;color:var(--sp-text-secondary);">
                <?php echo esc_html($status_labels[$prep->status] ?? $prep->status); ?>
                <?php if ($prep->submitted_at): ?>
                    | <?php echo date_i18n(get_option('date_format'), strtotime($prep->submitted_at)); ?>
                <?php endif; ?>
            </p>
            <?php if ($prep->total_points_awarded > 0): ?>
                <p style="color:var(--sp-primary);font-weight:600;margin:8px 0 0;">
                    🏆 <?php echo sprintf(__('مجموع النقاط: %d', 'saint-porphyrius'), $prep->total_points_awarded); ?>
                </p>
            <?php endif; ?>
            <?php if ($prep->status === 'needs_revision' && !empty($prep->admin_notes)): ?>
                <div style="margin-top:8px;padding:8px;background:#FEE2E2;border-radius:8px;font-size:0.85rem;color:#991B1B;">
                    <strong>📝 ملاحظات المراجع:</strong><br><?php echo esc_html($prep->admin_notes); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sections -->
        <?php foreach ($sections as $skey => $db_field): 
            $content = $prep->{$db_field} ?? '';
            $notes = $prep->{$db_field . '_notes'} ?? '';
            $points = $prep->{$db_field . '_points'} ?? 0;
            if (empty($content) && empty($notes)) continue;
        ?>
            <div class="sp-card" style="padding:var(--sp-space-md);margin-bottom:var(--sp-space-sm);">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <h3 style="margin:0;font-size:0.95rem;"><?php echo esc_html($section_labels[$skey] ?? $skey); ?></h3>
                    <span style="font-size:0.75rem;color:var(--sp-text-tertiary);">⭐ <?php echo $points; ?></span>
                </div>

                <?php if (!empty($content)): ?>
                    <div style="white-space:pre-wrap;line-height:1.8;font-size:0.9rem;padding:8px;background:var(--sp-bg-secondary, #F9FAFB);border-radius:8px;">
                        <?php echo wp_kses_post(nl2br($content)); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($notes)): ?>
                    <div style="margin-top:8px;font-size:0.85rem;color:var(--sp-text-secondary);">
                        <strong>📝 ملاحظات:</strong>
                        <p style="margin:4px 0 0;white-space:pre-wrap;"><?php echo esc_html($notes); ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($skey === 'lesson_writing' && isset($prep->ai_detection_score)): ?>
                    <div style="margin-top:8px;padding:8px;background:#FFF7ED;border-radius:8px;font-size:0.8rem;">
                        🤖 <strong>نتيجة كشف AI:</strong>
                        <span style="color:<?php echo $prep->ai_detection_score > 70 ? '#DC2626' : '#059669'; ?>;font-weight:600;">
                            <?php echo $prep->ai_detection_score; ?>%
                        </span>
                        <?php if ($prep->ai_penalty_applied > 0): ?>
                            | ⚠️ خصم: <?php echo $prep->ai_penalty_applied; ?> نقطة
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php if ($prep->status === 'needs_revision'): ?>
            <a href="<?php echo home_url('/app/lesson-prep/prepare/' . $prep->lesson_id); ?>" class="sp-btn sp-btn-primary sp-btn-block" style="margin:var(--sp-space-md) 0;">
                ✏️ <?php _e('تعديل التحضير', 'saint-porphyrius'); ?>
            </a>
        <?php endif; ?>
    </div>
</main>
