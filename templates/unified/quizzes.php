<?php
/**
 * Saint Porphyrius - Christian Quizzes (User-Facing)
 * Browse and take categorized Christian quizzes
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$quiz_handler = SP_Quiz::get_instance();
$settings = $quiz_handler->get_settings();

// Check if quiz system is enabled
if (!$settings['enabled']) {
    echo '<div class="sp-unified-header"><div class="sp-header-inner"><a href="' . home_url('/app/dashboard') . '" class="sp-header-back"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg></a><h1 class="sp-header-title">الاختبارات</h1><div class="sp-header-spacer"></div></div></div>';
    echo '<main class="sp-page-content has-bottom-nav"><div class="sp-card" style="text-align:center;padding:var(--sp-space-xl);margin:var(--sp-space-md);"><p>نظام الاختبارات غير مفعل حالياً</p></div></main>';
    return;
}

$categories = $quiz_handler->get_categories(true);

// Check if viewing a specific quiz or taking a quiz
$quiz_id = absint($_GET['quiz_id'] ?? 0);
$take_quiz = isset($_GET['take']);
$view_content = null;
$quiz_questions = array();

if ($quiz_id) {
    $view_content = $quiz_handler->get_content($quiz_id);
    if ($view_content && $view_content->status === 'published') {
        $quiz_questions = $quiz_handler->get_questions($quiz_id);
        $best_attempt = $quiz_handler->get_best_attempt($current_user->ID, $quiz_id);
        $attempt_count = $quiz_handler->get_attempt_count($current_user->ID, $quiz_id);
    } else {
        $view_content = null;
    }
}

// Get filter
$filter_category = absint($_GET['category'] ?? 0);
$published_content = $quiz_handler->get_published_content($filter_category ?: null);
?>

<!-- Header -->
<div class="sp-unified-header">
    <div class="sp-header-inner">
        <?php if ($quiz_id && !$take_quiz): ?>
            <a href="<?php echo home_url('/app/quizzes'); ?>" class="sp-header-back">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>
        <?php elseif ($take_quiz): ?>
            <a href="<?php echo home_url('/app/quizzes?quiz_id=' . $quiz_id); ?>" class="sp-header-back">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>
        <?php else: ?>
            <div class="sp-header-logo">
                <img src="<?php echo esc_url(SP_PLUGIN_URL . 'media/logo.png'); ?>" alt="Logo" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover;">
            </div>
        <?php endif; ?>
        <h1 class="sp-header-title">
            <?php 
            if ($take_quiz && $view_content) {
                _e('الاختبار', 'saint-porphyrius');
            } elseif ($view_content) {
                echo esc_html(mb_strimwidth($view_content->title_ar, 0, 30, '...'));
            } else {
                _e('الاختبارات المسيحية', 'saint-porphyrius');
            }
            ?>
        </h1>
        <div class="sp-header-actions">
            <?php if (!$quiz_id): ?>
            <a href="<?php echo home_url('/app/profile'); ?>" class="sp-header-action">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </a>
            <?php else: ?>
            <div class="sp-header-spacer"></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<main class="sp-page-content has-bottom-nav">

<?php if ($take_quiz && $view_content && !empty($quiz_questions)): ?>
    <!-- ================================================================== -->
    <!-- TAKE QUIZ MODE -->
    <!-- ================================================================== -->
    <div id="sp-quiz-take" data-content-id="<?php echo esc_attr($view_content->id); ?>" data-total="<?php echo count($quiz_questions); ?>">
        
        <!-- Progress Bar -->
        <div style="padding: var(--sp-space-md); padding-bottom: 0;">
            <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--sp-text-secondary); margin-bottom: 4px;">
                <span id="sp-quiz-progress-text">السؤال 1 من <?php echo count($quiz_questions); ?></span>
                <span id="sp-quiz-answered-text">0 إجابة</span>
            </div>
            <div style="height: 6px; background: var(--sp-bg-secondary); border-radius: 3px; overflow: hidden;">
                <div id="sp-quiz-progress-bar" style="height: 100%; background: linear-gradient(90deg, var(--sp-primary), #8B5CF6); border-radius: 3px; transition: width 0.3s ease; width: 0%;"></div>
            </div>
        </div>
        
        <!-- Questions -->
        <div id="sp-quiz-questions-container" style="padding: var(--sp-space-md);">
            <?php foreach ($quiz_questions as $qindex => $question): 
                $options = json_decode($question->options, true);
            ?>
            <div class="sp-quiz-question-slide" data-index="<?php echo $qindex; ?>" data-qid="<?php echo esc_attr($question->id); ?>" style="display: <?php echo $qindex === 0 ? 'block' : 'none'; ?>;">
                <div class="sp-card" style="padding: var(--sp-space-lg);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--sp-space-md);">
                        <span style="font-size: 13px; font-weight: 700; color: var(--sp-primary);">سؤال <?php echo $qindex + 1; ?></span>
                    </div>
                    
                    <p style="font-size: 16px; font-weight: 600; line-height: 1.8; margin-bottom: var(--sp-space-lg);">
                        <?php echo esc_html($question->question_text); ?>
                    </p>
                    
                    <div class="sp-quiz-options" style="display: flex; flex-direction: column; gap: 8px;">
                        <?php if ($options): foreach ($options as $oidx => $opt): ?>
                        <label class="sp-quiz-option-label" data-qid="<?php echo esc_attr($question->id); ?>" data-idx="<?php echo $oidx; ?>" 
                            style="display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: var(--sp-radius-md); border: 2px solid var(--sp-border-color); cursor: pointer; transition: all 0.2s ease; font-size: 14px; line-height: 1.6;">
                            <div class="sp-quiz-option-radio" style="width: 22px; height: 22px; border-radius: 50%; border: 2px solid var(--sp-border-color); flex-shrink: 0; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease;">
                            </div>
                            <span><?php echo esc_html($opt['text']); ?></span>
                            <input type="hidden" name="answer_<?php echo $question->id; ?>" value="">
                        </label>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Navigation -->
        <div style="padding: 0 var(--sp-space-md) var(--sp-space-md); display: flex; gap: 8px;">
            <button id="sp-quiz-prev-btn" class="sp-btn sp-btn-outline" style="flex: 1;" disabled>
                → <?php _e('السابق', 'saint-porphyrius'); ?>
            </button>
            <button id="sp-quiz-next-btn" class="sp-btn sp-btn-primary" style="flex: 1;">
                <?php _e('التالي', 'saint-porphyrius'); ?> ←
            </button>
            <button id="sp-quiz-submit-btn" class="sp-btn sp-btn-primary" style="flex: 1; display: none; background: linear-gradient(135deg, #10B981, #059669);">
                📤 <?php _e('إرسال الإجابات', 'saint-porphyrius'); ?>
            </button>
        </div>
        
        <!-- Question Dots Navigator -->
        <div style="padding: 0 var(--sp-space-md) var(--sp-space-md); display: flex; flex-wrap: wrap; gap: 4px; justify-content: center;">
            <?php for ($i = 0; $i < count($quiz_questions); $i++): ?>
            <button class="sp-quiz-dot" data-index="<?php echo $i; ?>" 
                style="width: 28px; height: 28px; border-radius: 50%; border: 2px solid var(--sp-border-color); background: white; font-size: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; <?php echo $i === 0 ? 'border-color: var(--sp-primary); color: var(--sp-primary);' : ''; ?>">
                <?php echo $i + 1; ?>
            </button>
            <?php endfor; ?>
        </div>
    </div>
    
    <!-- Quiz Result (hidden initially) -->
    <div id="sp-quiz-result" style="display: none; padding: var(--sp-space-md);"></div>

<?php elseif ($view_content): ?>
    <!-- ================================================================== -->
    <!-- VIEW QUIZ CONTENT -->
    <!-- ================================================================== -->
    
    <!-- Content Card -->
    <div style="padding: var(--sp-space-md);">
        <div class="sp-card" style="overflow: hidden;">
            <!-- Category Badge -->
            <div style="padding: var(--sp-space-md); padding-bottom: 0;">
                <span style="font-size: 12px; padding: 4px 12px; border-radius: 20px; background: <?php echo esc_attr($view_content->category_color); ?>15; color: <?php echo esc_attr($view_content->category_color); ?>; font-weight: 600;">
                    <?php echo esc_html($view_content->category_icon . ' ' . $view_content->category_name); ?>
                </span>
            </div>
            
            <!-- YouTube Embed -->
            <?php if ($view_content->youtube_url): 
                $ai_handler = SP_Quiz_AI::get_instance();
                $video_id = $ai_handler->extract_youtube_id($view_content->youtube_url);
                if ($video_id): ?>
                <div style="position: relative; padding-bottom: 56.25%; height: 0; margin: var(--sp-space-md) var(--sp-space-md) 0;">
                    <iframe src="https://www.youtube.com/embed/<?php echo esc_attr($video_id); ?>" 
                        style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: var(--sp-radius-md);" 
                        frameborder="0" allowfullscreen></iframe>
                </div>
            <?php endif; endif; ?>
            
            <!-- Content -->
            <div style="padding: var(--sp-space-md);">
                <h2 style="font-size: 20px; font-weight: 700; margin-bottom: var(--sp-space-sm); line-height: 1.6;">
                    <?php echo esc_html($view_content->title_ar); ?>
                </h2>
                
                <div style="display: flex; gap: 16px; margin-bottom: var(--sp-space-md); font-size: 13px; color: var(--sp-text-secondary);">
                    <span>📋 <?php echo esc_html($view_content->question_count); ?> سؤال</span>
                    <span>⭐ <?php echo esc_html($view_content->max_points); ?> نقطة كحد أقصى</span>
                    <span>🔄 محاولات غير محدودة</span>
                </div>
                
                <!-- AI Formatted Content -->
                <?php if ($view_content->ai_formatted_content): ?>
                <div class="sp-quiz-content-body" style="font-size: 14px; line-height: 1.8; margin-bottom: var(--sp-space-lg);">
                    <?php echo wp_kses_post($view_content->ai_formatted_content); ?>
                </div>
                <?php endif; ?>
                
                <!-- Previous Attempt Info -->
                <?php if ($best_attempt): ?>
                <div style="background: linear-gradient(135deg, #DBEAFE, #EDE9FE); border-radius: var(--sp-radius-md); padding: var(--sp-space-md); margin-bottom: var(--sp-space-md);">
                    <h4 style="margin-bottom: var(--sp-space-sm); font-size: 14px;">📊 أفضل نتيجة لك</h4>
                    <div style="display: flex; gap: 16px; font-size: 13px;">
                        <span>✅ <?php echo esc_html($best_attempt->score); ?>/<?php echo esc_html($best_attempt->total_questions); ?></span>
                        <span>📈 <?php echo esc_html($best_attempt->percentage); ?>%</span>
                        <span>⭐ <?php echo esc_html($best_attempt->points_awarded); ?> نقطة</span>
                        <span>🔄 <?php echo esc_html($attempt_count); ?> محاولة</span>
                    </div>
                    <?php if ($best_attempt->points_awarded >= $view_content->max_points): ?>
                    <p style="margin-top: 8px; font-size: 12px; color: #065F46; font-weight: 600;">🏆 لقد حصلت على الحد الأقصى من النقاط!</p>
                    <?php else: ?>
                    <p style="margin-top: 8px; font-size: 12px; color: #1E40AF;">💡 يمكنك إعادة المحاولة لتحسين نتيجتك والحصول على نقاط إضافية</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Start Quiz Button -->
                <a href="<?php echo home_url('/app/quizzes?quiz_id=' . $view_content->id . '&take'); ?>" 
                   class="sp-btn sp-btn-primary sp-btn-lg sp-btn-block" 
                   style="background: linear-gradient(135deg, #8B5CF6, #7C3AED); font-size: 16px;">
                    <?php if ($attempt_count > 0): ?>
                        🔄 <?php _e('إعادة الاختبار', 'saint-porphyrius'); ?>
                    <?php else: ?>
                        🚀 <?php _e('ابدأ الاختبار', 'saint-porphyrius'); ?>
                    <?php endif; ?>
                </a>
                
                <?php if (!$best_attempt): ?>
                <p style="text-align: center; font-size: 12px; color: var(--sp-text-secondary); margin-top: var(--sp-space-sm);">
                    📖 اقرأ المحتوى أعلاه جيداً قبل بدء الاختبار
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ================================================================== -->
    <!-- QUIZZES LISTING -->
    <!-- ================================================================== -->
    
    <!-- Hero Section -->
    <div style="padding: var(--sp-space-md);">
        <div class="sp-card" style="background: linear-gradient(135deg, #6C9BCF 0%, #8B5CF6 100%); color: white; padding: var(--sp-space-lg); text-align: center;">
            <div style="font-size: 48px; margin-bottom: var(--sp-space-sm);">📖</div>
            <h2 style="color: white; font-size: 20px; margin-bottom: var(--sp-space-xs);"><?php _e('الاختبارات المسيحية', 'saint-porphyrius'); ?></h2>
            <p style="color: rgba(255,255,255,0.9); font-size: 14px;"><?php _e('اختبر معلوماتك واكسب نقاطاً', 'saint-porphyrius'); ?></p>
            
            <?php 
            $total_quiz_points = $quiz_handler->get_user_total_quiz_points($current_user->ID);
            if ($total_quiz_points > 0): ?>
            <div style="margin-top: var(--sp-space-md); background: rgba(255,255,255,0.2); border-radius: var(--sp-radius-md); padding: var(--sp-space-sm);">
                <span style="font-size: 13px;">⭐ نقاط الاختبارات: <strong><?php echo esc_html($total_quiz_points); ?></strong></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Category Filter -->
    <?php if (!empty($categories)): ?>
    <div style="padding: 0 var(--sp-space-md); margin-bottom: var(--sp-space-md); display: flex; gap: 8px; overflow-x: auto; -webkit-overflow-scrolling: touch;">
        <a href="<?php echo home_url('/app/quizzes'); ?>" 
           class="sp-btn sp-btn-sm <?php echo !$filter_category ? 'sp-btn-primary' : 'sp-btn-outline'; ?>" 
           style="white-space: nowrap; flex-shrink: 0;">
            📋 الكل
        </a>
        <?php foreach ($categories as $cat): ?>
        <a href="<?php echo home_url('/app/quizzes?category=' . $cat->id); ?>" 
           class="sp-btn sp-btn-sm <?php echo $filter_category == $cat->id ? 'sp-btn-primary' : 'sp-btn-outline'; ?>" 
           style="white-space: nowrap; flex-shrink: 0;">
            <?php echo esc_html($cat->icon . ' ' . $cat->name_ar); ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Quiz Cards -->
    <div style="padding: 0 var(--sp-space-md);">
        <?php if (empty($published_content)): ?>
            <div class="sp-card" style="text-align: center; padding: var(--sp-space-xl);">
                <div style="font-size: 48px; margin-bottom: var(--sp-space-md);">📚</div>
                <h3 style="margin-bottom: var(--sp-space-sm);"><?php _e('لا توجد اختبارات متاحة', 'saint-porphyrius'); ?></h3>
                <p style="color: var(--sp-text-secondary);"><?php _e('سيتم إضافة اختبارات جديدة قريباً', 'saint-porphyrius'); ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($published_content as $item): 
                $user_best = $quiz_handler->get_best_attempt($current_user->ID, $item->id);
                $user_attempts = $quiz_handler->get_attempt_count($current_user->ID, $item->id);
                $completion_pct = $user_best ? round(($user_best->points_awarded / max(1, $item->max_points)) * 100) : 0;
            ?>
            <a href="<?php echo home_url('/app/quizzes?quiz_id=' . $item->id); ?>" class="sp-card" style="display: block; padding: var(--sp-space-md); margin-bottom: var(--sp-space-sm); text-decoration: none; color: inherit;">
                <div style="display: flex; gap: var(--sp-space-md); align-items: start;">
                    <div style="width: 56px; height: 56px; border-radius: var(--sp-radius-md); background: <?php echo esc_attr($item->category_color); ?>15; display: flex; align-items: center; justify-content: center; font-size: 28px; flex-shrink: 0;">
                        <?php echo esc_html($item->category_icon); ?>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <span style="font-size: 11px; color: <?php echo esc_attr($item->category_color); ?>; font-weight: 600;">
                            <?php echo esc_html($item->category_name); ?>
                        </span>
                        <h3 style="font-size: 15px; font-weight: 700; margin: 4px 0; line-height: 1.5;">
                            <?php echo esc_html($item->title_ar); ?>
                        </h3>
                        <div style="display: flex; gap: 12px; font-size: 12px; color: var(--sp-text-secondary); margin-top: 4px;">
                            <span>📋 <?php echo esc_html($item->question_count); ?> سؤال</span>
                            <span>⭐ <?php echo esc_html($item->max_points); ?> نقطة</span>
                        </div>
                        
                        <?php if ($user_best): ?>
                        <!-- Progress Bar -->
                        <div style="margin-top: 8px;">
                            <div style="display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 2px;">
                                <span style="color: var(--sp-primary);"><?php echo esc_html($user_best->points_awarded); ?>/<?php echo esc_html($item->max_points); ?> نقطة</span>
                                <span style="color: var(--sp-text-secondary);"><?php echo $user_attempts; ?> محاولة</span>
                            </div>
                            <div style="height: 4px; background: var(--sp-bg-secondary); border-radius: 2px; overflow: hidden;">
                                <div style="height: 100%; background: <?php echo $completion_pct >= 100 ? '#10B981' : 'var(--sp-primary)'; ?>; width: <?php echo min(100, $completion_pct); ?>%; border-radius: 2px;"></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($completion_pct >= 100): ?>
                    <span style="font-size: 24px;">🏆</span>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
<?php endif; ?>

</main>

<!-- Bottom Navigation -->
<nav class="sp-unified-nav">
    <div class="sp-nav-inner">
        <a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-nav-item">
            <div class="sp-nav-icon"><span class="dashicons dashicons-dashboard"></span></div>
            <span class="sp-nav-label"><?php _e('الرئيسية', 'saint-porphyrius'); ?></span>
        </a>
        <a href="<?php echo home_url('/app/events'); ?>" class="sp-nav-item">
            <div class="sp-nav-icon"><span class="dashicons dashicons-calendar-alt"></span></div>
            <span class="sp-nav-label"><?php _e('الفعاليات', 'saint-porphyrius'); ?></span>
        </a>
        <a href="<?php echo home_url('/app/quizzes'); ?>" class="sp-nav-item active">
            <div class="sp-nav-indicator"></div>
            <div class="sp-nav-icon"><span class="dashicons dashicons-book"></span></div>
            <span class="sp-nav-label"><?php _e('اختبارات', 'saint-porphyrius'); ?></span>
        </a>
        <a href="<?php echo home_url('/app/leaderboard'); ?>" class="sp-nav-item">
            <div class="sp-nav-icon"><span class="dashicons dashicons-awards"></span></div>
            <span class="sp-nav-label"><?php _e('المتصدرين', 'saint-porphyrius'); ?></span>
        </a>
        <a href="<?php echo home_url('/app/profile'); ?>" class="sp-nav-item">
            <div class="sp-nav-icon"><span class="dashicons dashicons-admin-users"></span></div>
            <span class="sp-nav-label"><?php _e('حسابي', 'saint-porphyrius'); ?></span>
        </a>
    </div>
</nav>

<?php if ($take_quiz && $view_content): ?>
<script>
(function($) {
    'use strict';
    
    var currentQuestion = 0;
    var totalQuestions = <?php echo count($quiz_questions); ?>;
    var answers = {};
    var contentId = <?php echo $view_content->id; ?>;
    
    // Option selection
    $(document).on('click', '.sp-quiz-option-label', function() {
        var qid = $(this).data('qid');
        var idx = $(this).data('idx');
        
        // Deselect all options for this question
        $(this).closest('.sp-quiz-options').find('.sp-quiz-option-label').css({
            'border-color': 'var(--sp-border-color)',
            'background': 'white'
        }).find('.sp-quiz-option-radio').css({
            'border-color': 'var(--sp-border-color)',
            'background': 'white'
        }).html('');
        
        // Select this option
        $(this).css({
            'border-color': 'var(--sp-primary)',
            'background': 'rgba(108, 155, 207, 0.08)'
        }).find('.sp-quiz-option-radio').css({
            'border-color': 'var(--sp-primary)',
            'background': 'var(--sp-primary)'
        }).html('<div style="width: 10px; height: 10px; border-radius: 50%; background: white;"></div>');
        
        answers[qid] = idx;
        
        // Update dot
        var dotIndex = $(this).closest('.sp-quiz-question-slide').data('index');
        $('.sp-quiz-dot[data-index="' + dotIndex + '"]').css({
            'background': 'var(--sp-primary)',
            'color': 'white',
            'border-color': 'var(--sp-primary)'
        });
        
        updateProgress();
    });
    
    // Navigation
    function goToQuestion(index) {
        if (index < 0 || index >= totalQuestions) return;
        
        $('.sp-quiz-question-slide').hide();
        $('.sp-quiz-question-slide[data-index="' + index + '"]').fadeIn(200);
        
        currentQuestion = index;
        
        // Update dots
        $('.sp-quiz-dot').css('border-color', 'var(--sp-border-color)');
        $('.sp-quiz-dot[data-index="' + index + '"]').css('border-color', 'var(--sp-primary)');
        
        // Update buttons
        $('#sp-quiz-prev-btn').prop('disabled', index === 0);
        
        if (index === totalQuestions - 1) {
            $('#sp-quiz-next-btn').hide();
            $('#sp-quiz-submit-btn').show();
        } else {
            $('#sp-quiz-next-btn').show();
            $('#sp-quiz-submit-btn').hide();
        }
        
        updateProgress();
    }
    
    function updateProgress() {
        var answered = Object.keys(answers).length;
        var pct = ((currentQuestion + 1) / totalQuestions) * 100;
        
        $('#sp-quiz-progress-bar').css('width', pct + '%');
        $('#sp-quiz-progress-text').text('السؤال ' + (currentQuestion + 1) + ' من ' + totalQuestions);
        $('#sp-quiz-answered-text').text(answered + ' إجابة');
    }
    
    $('#sp-quiz-next-btn').on('click', function() { goToQuestion(currentQuestion + 1); });
    $('#sp-quiz-prev-btn').on('click', function() { goToQuestion(currentQuestion - 1); });
    
    // Dot navigation
    $(document).on('click', '.sp-quiz-dot', function() {
        goToQuestion(parseInt($(this).data('index')));
    });
    
    // Submit quiz
    $('#sp-quiz-submit-btn').on('click', function() {
        var answeredCount = Object.keys(answers).length;
        if (answeredCount < totalQuestions) {
            if (!confirm('لم تجب على جميع الأسئلة (' + answeredCount + '/' + totalQuestions + '). هل تريد الإرسال؟')) {
                return;
            }
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('جاري الإرسال...');
        
        $.ajax({
            url: spApp.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sp_quiz_submit_attempt',
                nonce: spApp.nonce,
                content_id: contentId,
                answers: JSON.stringify(answers)
            },
            success: function(response) {
                if (response.success) {
                    showResult(response.data);
                } else {
                    alert(response.data.message);
                    $btn.prop('disabled', false).text('📤 إرسال الإجابات');
                }
            },
            error: function() {
                alert('حدث خطأ');
                $btn.prop('disabled', false).text('📤 إرسال الإجابات');
            }
        });
    });
    
    function showResult(data) {
        $('#sp-quiz-take').hide();
        
        var pct = parseFloat(data.percentage);
        var isPassed = pct >= <?php echo $settings['passing_percentage']; ?>;
        var gradientColor = isPassed ? 'linear-gradient(135deg, #10B981, #059669)' : 'linear-gradient(135deg, #F59E0B, #D97706)';
        var emoji = pct >= 90 ? '🏆' : (pct >= 70 ? '🌟' : (isPassed ? '✅' : '💪'));
        
        var html = '<div class="sp-card" style="overflow: hidden; margin: var(--sp-space-md);">';
        html += '<div style="background: ' + gradientColor + '; color: white; padding: var(--sp-space-xl); text-align: center;">';
        html += '<div style="font-size: 64px; margin-bottom: var(--sp-space-md);">' + emoji + '</div>';
        html += '<h2 style="color: white; font-size: 24px; margin-bottom: var(--sp-space-sm);">' + 
                (isPassed ? 'أحسنت!' : 'حاول مرة أخرى!') + '</h2>';
        html += '<p style="font-size: 48px; font-weight: 800; color: white; margin: var(--sp-space-md) 0;">' + pct.toFixed(0) + '%</p>';
        html += '</div>';
        
        html += '<div style="padding: var(--sp-space-lg);">';
        html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--sp-space-md); margin-bottom: var(--sp-space-lg);">';
        html += '<div style="text-align: center;"><div style="font-size: 24px; font-weight: 700; color: var(--sp-primary);">' + data.score + '/' + data.total + '</div><div style="font-size: 12px; color: var(--sp-text-secondary);">إجابات صحيحة</div></div>';
        html += '<div style="text-align: center;"><div style="font-size: 24px; font-weight: 700; color: #F59E0B;">⭐ ' + data.points_earned + '</div><div style="font-size: 12px; color: var(--sp-text-secondary);">نقاط مكتسبة</div></div>';
        html += '</div>';
        
        if (data.additional_points > 0) {
            html += '<div style="background: #D1FAE5; border-radius: var(--sp-radius-md); padding: var(--sp-space-md); text-align: center; margin-bottom: var(--sp-space-md);">';
            html += '<p style="color: #065F46; font-weight: 600;">🎉 حصلت على ' + data.additional_points + ' نقطة إضافية!</p>';
            html += '</div>';
        } else if (data.points_earned >= data.max_points) {
            html += '<div style="background: #FEF3C7; border-radius: var(--sp-radius-md); padding: var(--sp-space-md); text-align: center; margin-bottom: var(--sp-space-md);">';
            html += '<p style="color: #92400E;">🏆 لقد وصلت للحد الأقصى من النقاط لهذا الاختبار</p>';
            html += '</div>';
        }
        
        html += '<div style="display: flex; gap: 8px; flex-direction: column;">';
        html += '<a href="' + spApp.appUrl + '/quizzes?quiz_id=<?php echo $quiz_id; ?>&take" class="sp-btn sp-btn-primary sp-btn-lg sp-btn-block" style="background: linear-gradient(135deg, #8B5CF6, #7C3AED);">🔄 إعادة المحاولة</a>';
        html += '<a href="' + spApp.appUrl + '/quizzes" class="sp-btn sp-btn-outline sp-btn-lg sp-btn-block">📋 العودة للاختبارات</a>';
        html += '</div>';
        html += '</div></div>';
        
        $('#sp-quiz-result').html(html).fadeIn(300);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
})(jQuery);
</script>
<?php endif; ?>
