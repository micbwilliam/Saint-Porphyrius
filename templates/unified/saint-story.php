<?php
/**
 * Saint Porphyrius - Saint Story Template
 * Story of Saint Porphyrius the Actor with quiz
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$gamification = SP_Gamification::get_instance();
$settings = $gamification->get_settings();

// Check if already completed
$quiz_completed = $gamification->has_completed_story_quiz($current_user->ID);

// Get story data
$story = $gamification->get_saint_story();

// Handle quiz submission via AJAX
$show_quiz = isset($_GET['quiz']) && $_GET['quiz'] === '1';
$quiz_questions = $gamification->get_random_quiz(5);
?>

<!-- Unified Header -->
<div class="sp-unified-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('قصة شفيعنا', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-actions"></div>
    </div>
</div>

<!-- Main Content -->
<main class="sp-page-content has-bottom-nav">
    <?php if (!$show_quiz): ?>
    <!-- Story View -->
    <div class="sp-story-page">
        <div class="sp-story-header">
            <img src="<?php echo esc_url(SP_PLUGIN_URL . 'media/saint-porphyrius.jpg'); ?>" 
                 alt="<?php echo esc_attr($story['title']); ?>" 
                 class="sp-story-image"
                 onerror="this.src='<?php echo esc_url(SP_PLUGIN_URL . 'media/logo.png'); ?>';">
            <h1 class="sp-story-title"><?php echo esc_html($story['title']); ?></h1>
            <p class="sp-story-date">📅 <?php echo esc_html($story['feast_date']); ?></p>
        </div>
        
        <div class="sp-story-content">
            <?php echo wp_kses_post($story['content']); ?>
        </div>
        
        <div class="sp-story-actions">
            <?php if ($quiz_completed): ?>
                <div class="sp-alert sp-alert-success">
                    <div class="sp-alert-icon">✅</div>
                    <div class="sp-alert-content">
                        <strong><?php _e('أحسنت!', 'saint-porphyrius'); ?></strong>
                        <?php _e('لقد قرأت القصة وأجبت على الأسئلة بنجاح.', 'saint-porphyrius'); ?>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?php echo home_url('/app/saint-story?quiz=1'); ?>" class="sp-btn sp-btn-primary sp-btn-lg sp-btn-block">
                    <?php _e('اختبر معلوماتك', 'saint-porphyrius'); ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 6 15 12 9 18"></polyline>
                    </svg>
                </a>
                <p style="margin-top: var(--sp-space-md); color: var(--sp-text-secondary); font-size: var(--sp-font-size-sm);">
                    🎁 <?php printf(__('احصل على %d نقطة عند إجابة 3 أسئلة صحيحة على الأقل', 'saint-porphyrius'), $settings['story_quiz_points']); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Quiz View -->
    <div class="sp-story-page">
        <div class="sp-quiz-container" id="sp-quiz-container">
            <div class="sp-quiz-header">
                <h2><?php _e('اختبار القصة', 'saint-porphyrius'); ?></h2>
                <p><?php _e('أجب على 5 أسئلة لتحصل على مكافأتك', 'saint-porphyrius'); ?></p>
            </div>
            
            <div class="sp-quiz-progress" id="sp-quiz-progress">
                <?php for ($i = 0; $i < 5; $i++): ?>
                    <div class="sp-quiz-progress-dot" data-index="<?php echo $i; ?>"></div>
                <?php endfor; ?>
            </div>
            
            <form id="sp-quiz-form" method="post">
                <?php wp_nonce_field('sp_quiz_nonce', 'sp_quiz_nonce'); ?>
                
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
        
        function updateProgress() {
            $('.sp-quiz-progress-dot').removeClass('active');
            $('.sp-quiz-progress-dot[data-index="' + currentQuestion + '"]').addClass('active');
            
            // Mark answered questions
            for (var i = 0; i < currentQuestion; i++) {
                if ($('.sp-quiz-question[data-index="' + i + '"] input:checked').length) {
                    $('.sp-quiz-progress-dot[data-index="' + i + '"]').addClass('answered');
                }
            }
        }
        
        function showQuestion(index) {
            $('.sp-quiz-question').hide();
            $('.sp-quiz-question[data-index="' + index + '"]').show();
            
            // Update buttons
            $('#sp-quiz-prev').toggle(index > 0);
            $('#sp-quiz-next').toggle(index < totalQuestions - 1);
            $('#sp-quiz-submit').toggle(index === totalQuestions - 1);
            
            updateProgress();
        }
        
        $('#sp-quiz-next').on('click', function() {
            // Check if current question is answered
            if (!$('.sp-quiz-question[data-index="' + currentQuestion + '"] input:checked').length) {
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
        
        // Option click handler
        $('.sp-quiz-option').on('click', function() {
            $(this).closest('.sp-quiz-options').find('.sp-quiz-option').removeClass('selected');
            $(this).addClass('selected');
            $(this).find('input').prop('checked', true);
        });
        
        // Form submit
        $('#sp-quiz-form').on('submit', function(e) {
            e.preventDefault();
            
            // Check if last question is answered
            if (!$('.sp-quiz-question[data-index="' + currentQuestion + '"] input:checked').length) {
                alert('<?php _e('من فضلك اختر إجابة', 'saint-porphyrius'); ?>');
                return;
            }
            
            var formData = $(this).serialize();
            
            $('#sp-quiz-submit').prop('disabled', true).text('<?php _e('جاري التحقق...', 'saint-porphyrius'); ?>');
            
            $.ajax({
                url: sp_ajax.ajax_url,
                type: 'POST',
                data: formData + '&action=sp_submit_quiz',
                success: function(response) {
                    if (response.success) {
                        var resultHtml = '';
                        if (response.data.passed) {
                            resultHtml = '<div class="sp-quiz-result passed">' +
                                '<div class="sp-quiz-result-icon">🎉</div>' +
                                '<h2><?php _e('أحسنت!', 'saint-porphyrius'); ?></h2>' +
                                '<p>' + response.data.message + '</p>' +
                                '<a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-btn sp-btn-primary"><?php _e('العودة للرئيسية', 'saint-porphyrius'); ?></a>' +
                                '</div>';
                        } else {
                            resultHtml = '<div class="sp-quiz-result failed">' +
                                '<div class="sp-quiz-result-icon">😔</div>' +
                                '<h2><?php _e('حاول مرة أخرى', 'saint-porphyrius'); ?></h2>' +
                                '<p>' + response.data.message + '</p>' +
                                '<a href="<?php echo home_url('/app/saint-story'); ?>" class="sp-btn sp-btn-outline" style="margin-left: var(--sp-space-md);"><?php _e('اقرأ القصة مجدداً', 'saint-porphyrius'); ?></a>' +
                                '<a href="<?php echo home_url('/app/saint-story?quiz=1'); ?>" class="sp-btn sp-btn-primary"><?php _e('حاول مرة أخرى', 'saint-porphyrius'); ?></a>' +
                                '</div>';
                        }
                        
                        $('#sp-quiz-form').hide();
                        $('.sp-quiz-header').hide();
                        $('.sp-quiz-progress').hide();
                        $('#sp-quiz-result').html(resultHtml).show();
                    } else {
                        alert(response.data.message || '<?php _e('حدث خطأ، حاول مرة أخرى', 'saint-porphyrius'); ?>');
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
        <a href="<?php echo home_url('/app/events'); ?>" class="sp-nav-item">
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
