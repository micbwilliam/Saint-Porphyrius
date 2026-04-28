<?php
/**
 * Saint Porphyrius - Service Instructions Template
 * Instructions about attendance, points, and discipline system with quiz
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$gamification = SP_Gamification::get_instance();
$settings = $gamification->get_settings();

// Check completion count
$completion_count = $gamification->get_service_instructions_completion_count($current_user->ID);
$quiz_completed = $completion_count >= 2;

// Get instructions data
$instructions = $gamification->get_service_instructions();

// Handle quiz submission via AJAX
$show_quiz = isset($_GET['quiz']) && $_GET['quiz'] === '1';
$quiz_questions = $gamification->get_random_service_instructions_quiz(5);
?>

<!-- Unified Header -->
<div class="sp-unified-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/events'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('الخدمة والنظام', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-actions"></div>
    </div>
</div>

<!-- Main Content -->
<main class="sp-page-content has-bottom-nav">
    <?php if (!$show_quiz): ?>
    <!-- Instructions View -->
    <div class="sp-story-page">
        <div class="sp-story-header" style="background: linear-gradient(135deg, var(--sp-primary) 0%, var(--sp-primary-dark) 100%); padding: 32px 24px;">
            <div style="font-size: 64px; margin-bottom: 16px;">📋</div>
            <h1 class="sp-story-title" style="color: white;"><?php echo esc_html($instructions['title']); ?></h1>
            <p class="sp-story-date" style="color: rgba(255,255,255,0.9);">📖 <?php echo esc_html($instructions['subtitle']); ?></p>
        </div>
        
        <div class="sp-story-content" style="padding: var(--sp-space-lg);">
            <?php echo wp_kses_post($instructions['content']); ?>
        </div>
        
        <div class="sp-story-actions" style="padding: var(--sp-space-lg);">
            <?php if ($quiz_completed): ?>
                <div class="sp-alert sp-alert-success">
                    <div class="sp-alert-icon">✅</div>
                    <div class="sp-alert-content">
                        <strong><?php
                            $gender = get_user_meta(get_current_user_id(), 'sp_gender', true) ?: 'male';
                            echo esc_html(sp_custom_text('quiz_pass_praise', $gender));
                        ?></strong>
                        <?php _e('لقد قرأت التعليمات وأجبت على الأسئلة بنجاح مرتين.', 'saint-porphyrius'); ?>
                    </div>
                </div>
            <?php elseif ($completion_count === 1): ?>
                <div class="sp-alert sp-alert-success" style="margin-bottom: var(--sp-space-md);">
                    <div class="sp-alert-icon">✅</div>
                    <div class="sp-alert-content">
                        <strong><?php
                            $gender = get_user_meta(get_current_user_id(), 'sp_gender', true) ?: 'male';
                            echo esc_html(sp_custom_text('quiz_pass_praise', $gender));
                        ?></strong>
                        <?php _e('لقد أجبت على الأسئلة بنجاح مرة واحدة.', 'saint-porphyrius'); ?>
                    </div>
                </div>
                <a href="<?php echo home_url('/app/service-instructions?quiz=1'); ?>" class="sp-btn sp-btn-primary sp-btn-lg sp-btn-block">
                    <?php _e('اختبر معلوماتك مرة أخرى', 'saint-porphyrius'); ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 6 15 12 9 18"></polyline>
                    </svg>
                </a>
                <p style="margin-top: var(--sp-space-md); color: var(--sp-text-secondary); font-size: var(--sp-font-size-sm);">
                    🎁 <?php printf(__('احصل على %d نقطة إضافية عند إجابة 3 أسئلة صحيحة على الأقل (محاولة أخيرة)', 'saint-porphyrius'), $settings['service_instructions_points']); ?>
                </p>
            <?php else: ?>
                <a href="<?php echo home_url('/app/service-instructions?quiz=1'); ?>" class="sp-btn sp-btn-primary sp-btn-lg sp-btn-block">
                    <?php _e('اختبر معلوماتك', 'saint-porphyrius'); ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 6 15 12 9 18"></polyline>
                    </svg>
                </a>
                <p style="margin-top: var(--sp-space-md); color: var(--sp-text-secondary); font-size: var(--sp-font-size-sm);">
                    🎁 <?php printf(__('احصل على %d نقطة عند إجابة 3 أسئلة صحيحة على الأقل', 'saint-porphyrius'), $settings['service_instructions_points']); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Quiz View -->
    <div class="sp-story-page">
        <div class="sp-quiz-container" id="sp-quiz-container">
            <div class="sp-quiz-header">
                <h2><?php _e('اختبار تعليمات الخدمة', 'saint-porphyrius'); ?></h2>
                <p><?php _e('أجب على 5 أسئلة لتحصل على مكافأتك', 'saint-porphyrius'); ?></p>
            </div>
            
            <div class="sp-quiz-progress" id="sp-quiz-progress">
                <?php for ($i = 0; $i < 5; $i++): ?>
                    <div class="sp-quiz-progress-dot" data-index="<?php echo $i; ?>"></div>
                <?php endfor; ?>
            </div>
            
            <form id="sp-service-quiz-form" method="post">
                <?php wp_nonce_field('sp_service_quiz_nonce', 'sp_service_quiz_nonce'); ?>
                
                <?php foreach ($quiz_questions as $index => $question): ?>
                <div class="sp-quiz-question" data-index="<?php echo $index; ?>" style="<?php echo $index > 0 ? 'display: none;' : ''; ?>">
                    <h3><?php echo ($index + 1) . '. ' . esc_html($question['question']); ?></h3>
                    <div class="sp-quiz-options">
                        <?php foreach ($question['options'] as $option_index => $option): ?>
                        <label class="sp-quiz-option">
                            <input type="radio" name="answer_<?php echo $question['id']; ?>" 
                                   value="<?php echo $option_index; ?>" required>
                            <span class="sp-quiz-option-marker">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </span>
                            <span class="sp-quiz-option-text"><?php echo esc_html($option); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <input type="hidden" name="question_ids" value="<?php echo esc_attr(implode(',', array_column($quiz_questions, 'id'))); ?>">
                
                <div class="sp-quiz-navigation" style="margin-top: var(--sp-space-xl); display: flex; gap: var(--sp-space-md);">
                    <button type="button" id="sp-quiz-prev" class="sp-btn sp-btn-outline" style="display: none;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                        <?php _e('السابق', 'saint-porphyrius'); ?>
                    </button>
                    <button type="button" id="sp-quiz-next" class="sp-btn sp-btn-primary" style="flex: 1;">
                        <?php _e('التالي', 'saint-porphyrius'); ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 6 15 12 9 18"></polyline>
                        </svg>
                    </button>
                    <button type="submit" id="sp-quiz-submit" class="sp-btn sp-btn-primary sp-btn-lg" style="flex: 1; display: none;">
                        <?php _e('إرسال الإجابات', 'saint-porphyrius'); ?>
                    </button>
                </div>
            </form>
            
            <div id="sp-quiz-result" style="display: none;"></div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        var currentQuestion = 0;
        var totalQuestions = 5;
        
        // Handle option selection - add selected class
        $('.sp-quiz-option').on('click', function() {
            var $this = $(this);
            var $questionContainer = $this.closest('.sp-quiz-question');
            
            // Remove selected from other options in the same question
            $questionContainer.find('.sp-quiz-option').removeClass('selected');
            
            // Add selected to clicked option
            $this.addClass('selected');
            
            // Make sure the radio input is checked
            $this.find('input[type="radio"]').prop('checked', true);
        });
        
        function updateProgress() {
            $('.sp-quiz-progress-dot').removeClass('active');
            $('.sp-quiz-progress-dot[data-index="' + currentQuestion + '"]').addClass('active');
        }
        
        function showQuestion(index) {
            $('.sp-quiz-question').hide();
            $('.sp-quiz-question[data-index="' + index + '"]').show();
            
            // Update buttons
            if (index === 0) {
                $('#sp-quiz-prev').hide();
            } else {
                $('#sp-quiz-prev').show();
            }
            
            if (index === totalQuestions - 1) {
                $('#sp-quiz-next').hide();
                $('#sp-quiz-submit').show();
            } else {
                $('#sp-quiz-next').show();
                $('#sp-quiz-submit').hide();
            }
            
            updateProgress();
        }
        
        $('#sp-quiz-next').on('click', function() {
            // Check if current question is answered
            var currentQuestionEl = $('.sp-quiz-question[data-index="' + currentQuestion + '"]');
            if (currentQuestionEl.find('input[type="radio"]:checked').length === 0) {
                alert('<?php _e('من فضلك اختر إجابة', 'saint-porphyrius'); ?>');
                return;
            }
            
            if (currentQuestion < totalQuestions - 1) {
                currentQuestion++;
                showQuestion(currentQuestion);
            }
        });
        
        $('#sp-quiz-prev').on('click', function() {
            if (currentQuestion > 0) {
                currentQuestion--;
                showQuestion(currentQuestion);
            }
        });
        
        // Handle form submission
        $('#sp-service-quiz-form').on('submit', function(e) {
            e.preventDefault();
            
            // Check if last question is answered
            var lastQuestionEl = $('.sp-quiz-question[data-index="' + (totalQuestions - 1) + '"]');
            if (lastQuestionEl.find('input[type="radio"]:checked').length === 0) {
                alert('<?php _e('من فضلك اختر إجابة', 'saint-porphyrius'); ?>');
                return;
            }
            
            var formData = $(this).serialize();
            formData += '&action=sp_submit_service_quiz';
            
            $('#sp-quiz-submit').prop('disabled', true).text('<?php _e('جاري الإرسال...', 'saint-porphyrius'); ?>');
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        var result = response.data;
                        var icon = result.passed ? '🎉' : '😔';
                        var title = result.passed ? '<?php _e('أحسنت!', 'saint-porphyrius'); ?>' : '<?php _e('حاول مرة أخرى', 'saint-porphyrius'); ?>';
                        var message = result.passed 
                            ? '<?php printf(__('لقد أجبت على %s من 5 إجابات صحيحة وحصلت على %s نقطة!', 'saint-porphyrius'), '\' + result.correct + \'', $settings['service_instructions_points']); ?>'
                            : '<?php _e('لقد أجبت على \' + result.correct + \' من 5 إجابات صحيحة. تحتاج 3 على الأقل للحصول على المكافأة.', 'saint-porphyrius'); ?>';
                        
                        var resultHtml = '<div class="sp-quiz-result-card ' + (result.passed ? 'success' : 'fail') + '">' +
                            '<div class="sp-quiz-result-icon">' + icon + '</div>' +
                            '<h3>' + title + '</h3>' +
                            '<p>' + '<?php _e('أجبت على', 'saint-porphyrius'); ?> ' + result.correct + ' <?php _e('من', 'saint-porphyrius'); ?> 5 <?php _e('إجابات صحيحة', 'saint-porphyrius'); ?></p>' +
                            (result.passed ? '<p class="sp-quiz-points">+<?php echo $settings['service_instructions_points']; ?> <?php _e('نقطة', 'saint-porphyrius'); ?> 🎁</p>' : '') +
                            '<a href="<?php echo home_url('/app/events'); ?>" class="sp-btn sp-btn-primary sp-btn-block"><?php _e('العودة للفعاليات', 'saint-porphyrius'); ?></a>' +
                            (result.passed ? '' : '<a href="<?php echo home_url('/app/service-instructions'); ?>" class="sp-btn sp-btn-outline sp-btn-block" style="margin-top: 8px;"><?php _e('اقرأ التعليمات مرة أخرى', 'saint-porphyrius'); ?></a>') +
                            '</div>';
                        
                        $('#sp-service-quiz-form').hide();
                        $('.sp-quiz-header').hide();
                        $('.sp-quiz-progress').hide();
                        $('#sp-quiz-result').html(resultHtml).show();
                    } else {
                        alert(response.data.message || '<?php _e('حدث خطأ', 'saint-porphyrius'); ?>');
                        $('#sp-quiz-submit').prop('disabled', false).text('<?php _e('إرسال الإجابات', 'saint-porphyrius'); ?>');
                    }
                },
                error: function() {
                    alert('<?php _e('حدث خطأ في الاتصال', 'saint-porphyrius'); ?>');
                    $('#sp-quiz-submit').prop('disabled', false).text('<?php _e('إرسال الإجابات', 'saint-porphyrius'); ?>');
                }
            });
        });
        
        // Initialize
        updateProgress();
    });
    </script>
    <?php endif; ?>
</main>

<!-- Unified Bottom Navigation -->
<nav class="sp-unified-nav">
    <div class="sp-nav-inner">
        <a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-nav-item">
            <div class="sp-nav-icon">
                <span class="dashicons dashicons-dashboard"></span>
            </div>
            <span class="sp-nav-label"><?php _e('الرئيسية', 'saint-porphyrius'); ?></span>
        </a>
        <a href="<?php echo home_url('/app/events'); ?>" class="sp-nav-item active">
            <div class="sp-nav-indicator"></div>
            <div class="sp-nav-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <span class="sp-nav-label"><?php _e('الفعاليات', 'saint-porphyrius'); ?></span>
        </a>
        <a href="<?php echo home_url('/app/points'); ?>" class="sp-nav-item">
            <div class="sp-nav-icon">
                <span class="dashicons dashicons-star-filled"></span>
            </div>
            <span class="sp-nav-label"><?php _e('نقاطي', 'saint-porphyrius'); ?></span>
        </a>
        <a href="<?php echo home_url('/app/leaderboard'); ?>" class="sp-nav-item">
            <div class="sp-nav-icon">
                <span class="dashicons dashicons-awards"></span>
            </div>
            <span class="sp-nav-label"><?php _e('المتصدرين', 'saint-porphyrius'); ?></span>
        </a>
        <a href="<?php echo home_url('/app/profile'); ?>" class="sp-nav-item">
            <div class="sp-nav-icon">
                <span class="dashicons dashicons-admin-users"></span>
            </div>
            <span class="sp-nav-label"><?php _e('حسابي', 'saint-porphyrius'); ?></span>
        </a>
    </div>
</nav>
