<?php
/**
 * Saint Porphyrius - Lesson Quiz (User-Facing)
 * Take a quiz for a lesson
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

$quiz_config = $lesson->quiz_config;
$allow_retake = $quiz_config['allow_retake'] ?? false;
$passing_percent = $quiz_config['passing_percent'] ?? 60;
$best_attempt = $handler->get_best_attempt($current_user->ID, $lesson_id);
// Only a PASSING attempt locks the quiz when retakes are off — a failed
// attempt must let the member try again (and unlock the gated preparation).
$has_passed = $best_attempt && $best_attempt->percentage >= $passing_percent;
$already_completed = $has_passed && !$allow_retake;

$questions = $handler->get_random_questions($lesson_id);
?>

<div class="sp-unified-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/lesson-prep'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('اختبار الدرس', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-spacer"></div>
    </div>
</div>

<main class="sp-page-content has-bottom-nav">
    <div style="padding:var(--sp-space-md);">
        <!-- Lesson info -->
        <div class="sp-card" style="padding:var(--sp-space-md);margin-bottom:var(--sp-space-md);text-align:center;">
            <div style="font-size:2rem;margin-bottom:4px;">📝</div>
            <h2 style="margin:0 0 4px 0;font-size:1.1rem;"><?php echo esc_html($lesson->title_ar); ?></h2>
            <?php if ($best_attempt): ?>
                <div style="margin-top:8px;font-size:0.85rem;color:var(--sp-text-secondary);">
                    <?php echo sprintf(__('أفضل نتيجة: %d/%d (%d%%)', 'saint-porphyrius'), $best_attempt->score, $best_attempt->total_questions, round($best_attempt->percentage)); ?>
                    <?php if ($best_attempt->points_awarded > 0): ?>
                        | 🏆 <?php echo sprintf(__('%d نقطة', 'saint-porphyrius'), $best_attempt->points_awarded); ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($already_completed && $best_attempt): ?>
            <!-- Show results only (no retake) -->
            <div class="sp-card" style="padding:var(--sp-space-lg);text-align:center;">
                <div style="font-size:4rem;">🏆</div>
                <h3><?php _e('لقد أكملت الاختبار!', 'saint-porphyrius'); ?></h3>
                <p style="color:var(--sp-text-secondary);">
                    <?php echo sprintf(__('نتيجتك: %d من %d', 'saint-porphyrius'), $best_attempt->score, $best_attempt->total_questions); ?>
                </p>
                <p>
                    <?php echo sprintf(__('النسبة: %d%%', 'saint-porphyrius'), round($best_attempt->percentage)); ?>
                    <?php if ($best_attempt->percentage >= ($quiz_config['passing_percent'] ?? 60)): ?>
                        <span style="color:#059669;">✅ <?php _e('ناجح', 'saint-porphyrius'); ?></span>
                    <?php else: ?>
                        <span style="color:#DC2626;">❌ <?php _e('لم تنجح', 'saint-porphyrius'); ?></span>
                    <?php endif; ?>
                </p>
                <?php if ($best_attempt->points_awarded > 0): ?>
                    <p style="color:var(--sp-primary);font-weight:600;">
                        🏆 <?php echo sprintf(__('حصلت على %d نقطة!', 'saint-porphyrius'), $best_attempt->points_awarded); ?>
                    </p>
                <?php endif; ?>
                <a href="<?php echo home_url('/app/lesson-prep'); ?>" class="sp-btn sp-btn-primary" style="margin-top:12px;">
                    <?php _e('العودة للدروس', 'saint-porphyrius'); ?>
                </a>
            </div>
        <?php elseif (empty($questions)): ?>
            <div class="sp-card" style="padding:var(--sp-space-xl);text-align:center;">
                <p><?php _e('لا توجد أسئلة متاحة لهذا الدرس بعد', 'saint-porphyrius'); ?></p>
            </div>
        <?php else: ?>
            <!-- Quiz form -->
            <form id="sp-lesson-quiz-form" data-lesson-id="<?php echo $lesson_id; ?>">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('sp_nonce'); ?>">
                <input type="hidden" name="action" value="sp_lesson_quiz_submit">
                <input type="hidden" name="lesson_id" value="<?php echo $lesson_id; ?>">

                <?php foreach ($questions as $index => $q): ?>
                    <div class="sp-card sp-quiz-question-card" style="padding:var(--sp-space-md);margin-bottom:var(--sp-space-md);" data-question-id="<?php echo $q->id; ?>">
                        <div style="display:flex;align-items:flex-start;gap:8px;margin-bottom:var(--sp-space-sm);">
                            <span style="background:var(--sp-primary);color:#fff;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:700;flex-shrink:0;"><?php echo $index + 1; ?></span>
                            <div>
                                <p style="margin:0;font-weight:600;line-height:1.6;"><?php echo esc_html($q->question_text); ?></p>
                                <?php if ($q->difficulty === 'easy'): ?>
                                    <span style="font-size:0.7rem;background:#D1FAE5;color:#065F46;padding:1px 8px;border-radius:10px;"><?php _e('سهل', 'saint-porphyrius'); ?></span>
                                <?php elseif ($q->difficulty === 'hard'): ?>
                                    <span style="font-size:0.7rem;background:#FEE2E2;color:#991B1B;padding:1px 8px;border-radius:10px;"><?php _e('صعب', 'saint-porphyrius'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="sp-quiz-options" style="padding-right:36px;">
                            <?php
                            $options = is_array($q->options) ? $q->options : json_decode($q->options, true);
                            if (!is_array($options)) $options = array();

                            if ($q->question_type === 'true_false'):
                                $options = array(
                                    array('text' => __('صح', 'saint-porphyrius'), 'is_correct' => $q->correct_answer_index === 0),
                                    array('text' => __('خطأ', 'saint-porphyrius'), 'is_correct' => $q->correct_answer_index === 1),
                                );
                            endif;

                            foreach ($options as $oidx => $opt):
                                $opt_text = is_array($opt) ? ($opt['text'] ?? '') : $opt;
                            ?>
                                <label class="sp-quiz-option" style="display:flex;align-items:center;gap:8px;padding:8px 12px;margin-bottom:4px;border:1px solid var(--sp-border);border-radius:8px;cursor:pointer;transition:all .15s;">
                                    <input type="radio" name="answer_<?php echo $q->id; ?>" value="<?php echo $oidx; ?>" style="accent-color:var(--sp-primary);">
                                    <span style="font-size:0.9rem;"><?php echo esc_html($opt_text); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="sp-btn sp-btn-primary sp-btn-block sp-btn-lg" style="margin-bottom:var(--sp-space-xl);">
                    <?php _e('تسليم الإجابات', 'saint-porphyrius'); ?> ✅
                </button>
            </form>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('sp-lesson-quiz-form');
    if (!form) return;

    // Highlight selected option
    form.addEventListener('change', function(e) {
        if (e.target.type === 'radio') {
            var card = e.target.closest('.sp-quiz-question-card');
            if (card) {
                card.querySelectorAll('.sp-quiz-option').forEach(function(opt) {
                    opt.style.background = '';
                    opt.style.borderColor = 'var(--sp-border)';
                });
                var selected = e.target.closest('.sp-quiz-option');
                if (selected) {
                    selected.style.background = 'var(--sp-primary-light, #EFF6FF)';
                    selected.style.borderColor = 'var(--sp-primary)';
                }
            }
        }
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Validate all questions answered
        var questions = form.querySelectorAll('.sp-quiz-question-card');
        var allAnswered = true;
        questions.forEach(function(card) {
            var answered = card.querySelector('input[type="radio"]:checked');
            if (!answered) {
                card.style.border = '2px solid #DC2626';
                allAnswered = false;
            } else {
                card.style.border = '';
            }
        });

        if (!allAnswered) {
            alert('يرجى الإجابة على جميع الأسئلة');
            return;
        }

        var submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'جاري التصحيح...';

        var formData = new FormData(form);

        fetch(spApp.ajaxUrl, {
            method: 'POST',
            body: formData,
        })
        .then(function(r) { return r.json(); })
        .then(function(response) {
            if (response.success) {
                var data = response.data;
                var resultHtml = '<div class="sp-card" style="padding:var(--sp-space-lg);text-align:center;">';
                resultHtml += '<div style="font-size:4rem;">' + (data.passed ? '🏆' : '💪') + '</div>';
                resultHtml += '<h3>' + (data.passed ? 'أحسنت! نجحت في الاختبار!' : 'حاول مرة أخرى!') + '</h3>';
                resultHtml += '<p style="color:var(--sp-text-secondary);">' + data.correct + ' من ' + data.total + ' (' + data.percentage + '%)</p>';
                if (data.points_awarded > 0) {
                    resultHtml += '<p style="color:var(--sp-primary);font-weight:600;">🏆 حصلت على ' + data.points_awarded + ' نقطة!</p>';
                }
                resultHtml += '<a href="' + spApp.appUrl + '/lesson-prep" class="sp-btn sp-btn-primary" style="margin-top:12px;">العودة للدروس</a>';
                resultHtml += '</div>';
                form.innerHTML = resultHtml;
            } else {
                alert(response.data.message || 'حدث خطأ');
                submitBtn.disabled = false;
                submitBtn.textContent = 'تسليم الإجابات ✅';
            }
        })
        .catch(function(err) {
            alert('حدث خطأ في الاتصال');
            submitBtn.disabled = false;
            submitBtn.textContent = 'تسليم الإجابات ✅';
        });
    });
});
</script>
