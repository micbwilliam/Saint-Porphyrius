<?php
/**
 * Saint Porphyrius - Lesson Preparation System (User-Facing)
 * Lists available lessons for the user's grade
 *
 * @since 6.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$handler = SP_Lesson_Prep::get_instance();
$user_grade = $handler->get_user_grade($current_user->ID);
$lessons = $handler->get_user_lessons($current_user->ID);
$config = $handler->get_config();
$is_enabled = !empty($config['prep_enabled']);
?>

<div class="sp-unified-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('تحضير الدروس', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-spacer"></div>
    </div>
</div>

<main class="sp-page-content has-bottom-nav">
    <?php if (!$is_enabled): ?>
        <div class="sp-card" style="text-align:center;padding:var(--sp-space-xl);margin:var(--sp-space-md);">
            <div style="font-size:3rem;margin-bottom:var(--sp-space-md);">📚</div>
            <p style="color:var(--sp-text-secondary);"><?php _e('نظام تحضير الدروس غير مفعل حالياً', 'saint-porphyrius'); ?></p>
        </div>
    <?php elseif (empty($lessons)): ?>
        <div class="sp-card" style="text-align:center;padding:var(--sp-space-xl);margin:var(--sp-space-md);">
            <div style="font-size:3rem;margin-bottom:var(--sp-space-md);">📭</div>
            <p style="color:var(--sp-text-secondary);"><?php _e('لا توجد دروس متاحة لصفك حالياً', 'saint-porphyrius'); ?></p>
            <?php if ($user_grade): ?>
                <p style="font-size:0.85rem;color:var(--sp-text-tertiary);"><?php echo sprintf(__('الصف: %d', 'saint-porphyrius'), $user_grade); ?></p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php if ($user_grade): ?>
            <div class="sp-lesson-grade-badge" style="text-align:center;padding:var(--sp-space-sm) 0;">
                <span style="background:var(--sp-primary);color:#fff;padding:4px 16px;border-radius:20px;font-size:0.85rem;">
                    <?php echo sprintf(__('الصف %d', 'saint-porphyrius'), $user_grade); ?>
                </span>
            </div>
        <?php endif; ?>

        <div class="sp-lesson-grid" style="padding:var(--sp-space-md);display:flex;flex-direction:column;gap:var(--sp-space-md);">
            <?php foreach ($lessons as $lesson): 
                $has_quiz = $lesson->question_count > 0;
                $user_attempt = $handler->get_best_attempt($current_user->ID, $lesson->id);
                $quiz_passed = $user_attempt && $user_attempt->percentage >= ($lesson->quiz_config['passing_percent'] ?? 60);
                
                // One preparation per (member, lesson) now, so this is simply theirs.
                $latest_prep = $handler->get_user_preparation($current_user->ID, $lesson->id);
                $prep_status = $latest_prep ? $latest_prep->status : null;

                // Only draft and needs_revision are still the member's to edit. Offering
                // "متابعة التحضير" for a submitted one is what sent people into a blank
                // wizard that then created a duplicate preparation.
                $prep_editable = !$prep_status || in_array($prep_status, array('draft', 'needs_revision'), true);
            ?>
                <div class="sp-card sp-lesson-card" style="padding:var(--sp-space-md);">
                    <div style="display:flex;align-items:flex-start;gap:var(--sp-space-sm);">
                        <div style="font-size:2rem;flex-shrink:0;">📖</div>
                        <div style="flex:1;min-width:0;">
                            <h3 style="margin:0 0 4px 0;font-size:1.05rem;font-weight:600;"><?php echo esc_html($lesson->title_ar); ?></h3>
                            <?php if (!empty($lesson->description_ar)): ?>
                                <p style="margin:0 0 8px 0;font-size:0.85rem;color:var(--sp-text-secondary);"><?php echo esc_html($lesson->description_ar); ?></p>
                            <?php endif; ?>
                            <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;font-size:0.8rem;color:var(--sp-text-tertiary);">
                                <?php if (!empty($lesson->event_title_ar)): ?>
                                    <span>📅 <?php echo esc_html($lesson->event_title_ar); ?></span>
                                <?php endif; ?>
                                <span>📝 <?php echo sprintf(__('%d سؤال', 'saint-porphyrius'), $lesson->question_count); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Status badges -->
                    <div style="margin-top:var(--sp-space-sm);display:flex;flex-wrap:wrap;gap:6px;">
                        <?php if ($quiz_passed): ?>
                            <span class="sp-badge sp-badge-success">✅ <?php _e('تم اجتياز الاختبار', 'saint-porphyrius'); ?></span>
                        <?php endif; ?>
                        
                        <?php if ($prep_status): 
                            $status_labels = SP_Lesson_Prep::get_status_labels();
                            $status_badge_colors = array(
                                'draft' => 'background:#E5E7EB;color:#374151;',
                                'submitted' => 'background:#FEF3C7;color:#92400E;',
                                'under_review' => 'background:#DBEAFE;color:#1E40AF;',
                                'approved' => 'background:#D1FAE5;color:#065F46;',
                                'needs_revision' => 'background:#FEE2E2;color:#991B1B;',
                            );
                            $badge_style = $status_badge_colors[$prep_status] ?? 'background:#E5E7EB;color:#374151;';
                        ?>
                            <span class="sp-badge" style="font-size:0.75rem;padding:2px 8px;border-radius:12px;<?php echo $badge_style; ?>">
                                <?php echo esc_html($status_labels[$prep_status] ?? $prep_status); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Action buttons -->
                    <div style="margin-top:var(--sp-space-sm);display:flex;flex-wrap:wrap;gap:8px;">
                        <?php if (!empty($lesson->pdf_urls)): 
                            $pdf_url = is_object($lesson->pdf_urls) ? '' : (is_array($lesson->pdf_urls) ? reset($lesson->pdf_urls) : $lesson->pdf_urls);
                            if ($pdf_url):
                        ?>
                            <a href="<?php echo esc_url($pdf_url); ?>" target="_blank" class="sp-btn sp-btn-outline sp-btn-sm" style="font-size:0.8rem;">
                                📄 <?php _e('عرض PDF', 'saint-porphyrius'); ?>
                            </a>
                        <?php endif; endif; ?>

                        <?php if ($has_quiz): ?>
                            <a href="<?php echo home_url('/app/lesson-prep/quiz/' . $lesson->id); ?>" class="sp-btn sp-btn-primary sp-btn-sm" style="font-size:0.8rem;">
                                <?php echo $quiz_passed ? '📊 ' . __('نتيجتي', 'saint-porphyrius') : '📝 ' . __('الاختبار', 'saint-porphyrius'); ?>
                            </a>
                        <?php endif; ?>

                        <?php
                        // Show preparation button if quiz passed (or no quiz required)
                        $require_quiz = !empty($config['prep_required_quiz']);
                        $can_prepare = !$require_quiz || $quiz_passed || !$has_quiz;
                        if ($can_prepare && $prep_editable):
                            if ($prep_status === 'needs_revision') {
                                $prep_label = '✏️ ' . __('تعديل التحضير', 'saint-porphyrius');
                            } elseif ($prep_status === 'draft') {
                                $prep_label = '📝 ' . __('متابعة التحضير', 'saint-porphyrius');
                            } else {
                                $prep_label = '✍️ ' . __('تحضير الدرس', 'saint-porphyrius');
                            }
                        ?>
                            <a href="<?php echo home_url('/app/lesson-prep/prepare/' . $lesson->id); ?>" class="sp-btn sp-btn-secondary sp-btn-sm" style="font-size:0.8rem;">
                                <?php echo $prep_label; ?>
                            </a>
                        <?php endif; ?>

                        <?php if ($latest_prep && $latest_prep->id): ?>
                            <a href="<?php echo home_url('/app/lesson-prep/view/' . $latest_prep->id); ?>" class="sp-btn sp-btn-outline sp-btn-sm" style="font-size:0.8rem;">
                                👁️ <?php _e('عرض تحضيري', 'saint-porphyrius'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
