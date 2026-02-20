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
        if ($take_quiz) {
            // For taking quiz: get random limited questions
            $quiz_questions = $quiz_handler->get_random_questions($quiz_id);
        } else {
            // For viewing: get all questions (for count display)
            $quiz_questions = $quiz_handler->get_questions($quiz_id);
        }
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
            <?php 
            $sp_notif_handler = SP_Notifications::get_instance();
            $sp_unread = $sp_notif_handler->get_accurate_unread_count(get_current_user_id());
            ?>
            <a href="<?php echo home_url('/app/notifications'); ?>" class="sp-header-action sp-bell-icon" title="الإشعارات">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <?php if ($sp_unread > 0): ?>
                <span class="sp-bell-badge"><?php echo $sp_unread > 99 ? '99+' : $sp_unread; ?></span>
                <?php endif; ?>
            </a>
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
                // Shuffle options at render time to randomize correct answer position
                if ($options && count($options) > 1) {
                    $indices = range(0, count($options) - 1);
                    shuffle($indices);
                    $shuffled_options = array();
                    $shuffle_map = array(); // shuffled_pos => original_pos
                    foreach ($indices as $new_pos => $old_pos) {
                        $shuffled_options[] = $options[$old_pos];
                        $shuffle_map[$new_pos] = $old_pos;
                    }
                    $options = $shuffled_options;
                } else {
                    $shuffle_map = $options ? range(0, count($options) - 1) : array();
                }
            ?>
            <div class="sp-quiz-question-slide" data-index="<?php echo $qindex; ?>" data-qid="<?php echo esc_attr($question->id); ?>" data-shuffle-map="<?php echo esc_attr(wp_json_encode($shuffle_map)); ?>" style="display: <?php echo $qindex === 0 ? 'block' : 'none'; ?>;">
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
                    <span>📋 <?php echo esc_html($settings['questions_per_attempt']); ?> سؤال عشوائي</span>
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
                
                <!-- Quiz Rules Card -->
                <div style="background: linear-gradient(135deg, #FEF3C7, #FDE68A); border-radius: var(--sp-radius-md); padding: var(--sp-space-md); margin-top: var(--sp-space-md); border-right: 4px solid #F59E0B;">
                    <h4 style="margin-bottom: var(--sp-space-sm); font-size: 14px; color: #92400E;">📜 قواعد الاختبار</h4>
                    <ul style="font-size: 13px; color: #78350F; line-height: 1.8; margin: 0; padding-right: 16px; list-style: none;">
                        <li>📌 يتم اختيار <strong><?php echo esc_html($settings['questions_per_attempt']); ?> أسئلة عشوائية</strong> في كل محاولة من بنك الأسئلة (<?php echo esc_html($view_content->question_count); ?> سؤال)</li>
                        <li>📌 يجب الحصول على <strong><?php echo esc_html($settings['min_points_percentage']); ?>% على الأقل</strong> لكسب النقاط</li>
                        <li>📌 النقاط تُحسب بنسبة الإجابات الصحيحة (كحد أقصى <?php echo esc_html($view_content->max_points); ?> نقطة)</li>
                        <li>📌 المحاولات غير محدودة ولكن تُحتسب <strong>أفضل نتيجة فقط</strong></li>
                        <li>📌 ترتيب الأسئلة والإجابات <strong>يتغير عشوائياً</strong> في كل محاولة</li>
                    </ul>
                </div>
                
                <?php if (!empty($settings['penalty_enabled'])): ?>
                <!-- Penalty Warning Card -->
                <div style="background: linear-gradient(135deg, #FEE2E2, #FECACA); border-radius: var(--sp-radius-md); padding: var(--sp-space-md); margin-top: var(--sp-space-sm); border-right: 4px solid #EF4444;">
                    <h4 style="margin-bottom: var(--sp-space-sm); font-size: 14px; color: #991B1B;">⚠️ تحذير - نظام مكافحة التخمين العشوائي</h4>
                    <ul style="font-size: 13px; color: #7F1D1D; line-height: 1.8; margin: 0; padding-right: 16px; list-style: none;">
                        <li>🚫 يتم مراقبة وقت الإجابة على كل سؤال - <strong>الإجابة في أقل من <?php echo esc_html($settings['penalty_min_seconds']); ?> ثوانٍ</strong> تُعتبر تخميناً</li>
                        <li>🚫 اختيار <strong>نفس الإجابة بشكل متكرر</strong> لمعظم الأسئلة يُعتبر تخميناً عشوائياً</li>
                        <li>🚫 عند اكتشاف تخمين عشوائي: <strong>لا تُمنح أي نقاط</strong> + يُخصم <strong><?php echo esc_html($settings['penalty_points']); ?> نقطة</strong> كعقوبة</li>
                        <li>💡 <strong>اقرأ كل سؤال بعناية</strong> وخذ وقتك في الإجابة</li>
                    </ul>
                </div>
                <?php endif; ?>
                
                <!-- Competition Leaderboard -->
                <?php 
                $leaderboard = $quiz_handler->get_content_leaderboard($view_content->id, 20);
                if (!empty($leaderboard)): 
                ?>
                <div style="margin-top: var(--sp-space-lg);">
                    <h3 style="font-size: 16px; font-weight: 700; margin-bottom: var(--sp-space-md); text-align: center;">
                        🏆 <?php _e('لوحة المتصدرين', 'saint-porphyrius'); ?>
                    </h3>
                    
                    <!-- Top 3 Podium -->
                    <?php if (count($leaderboard) >= 1): ?>
                    <div style="display: flex; justify-content: center; align-items: flex-end; gap: 8px; margin-bottom: var(--sp-space-md); padding: 0 var(--sp-space-sm);">
                        <?php 
                        $podium_order = [];
                        if (isset($leaderboard[1])) $podium_order[] = ['data' => $leaderboard[1], 'rank' => 2, 'medal' => '🥈', 'height' => '80px', 'bg' => 'linear-gradient(to top, #94A3B8, #CBD5E1)'];
                        $podium_order[] = ['data' => $leaderboard[0], 'rank' => 1, 'medal' => '🥇', 'height' => '100px', 'bg' => 'linear-gradient(to top, #D4A12A, #FBBF24)'];
                        if (isset($leaderboard[2])) $podium_order[] = ['data' => $leaderboard[2], 'rank' => 3, 'medal' => '🥉', 'height' => '65px', 'bg' => 'linear-gradient(to top, #B45309, #F59E0B)'];
                        
                        foreach ($podium_order as $pod):
                            $pod_user = get_userdata($pod['data']->user_id);
                            $pod_first = $pod_user ? $pod_user->first_name : '';
                            $pod_middle = $pod_user ? get_user_meta($pod['data']->user_id, 'sp_middle_name', true) : '';
                            $pod_name = trim($pod_first . ' ' . $pod_middle) ?: $pod['data']->display_name;
                            $is_me = ($pod['data']->user_id == $current_user->ID);
                        ?>
                        <div style="flex: 1; max-width: 110px; text-align: center;">
                            <div style="font-size: <?php echo $pod['rank'] === 1 ? '28px' : '22px'; ?>; margin-bottom: 4px;"><?php echo $pod['medal']; ?></div>
                            <div style="font-size: 11px; font-weight: <?php echo $is_me ? '800' : '600'; ?>; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 4px; <?php echo $is_me ? 'color: var(--sp-primary);' : ''; ?>">
                                <?php echo esc_html($pod_name); ?>
                                <?php if ($is_me): ?><span style="font-size: 9px;"> (أنا)</span><?php endif; ?>
                            </div>
                            <div style="background: <?php echo $pod['bg']; ?>; border-radius: var(--sp-radius-md) var(--sp-radius-md) 0 0; height: <?php echo $pod['height']; ?>; display: flex; flex-direction: column; justify-content: center; align-items: center; color: white; font-weight: 700;">
                                <div style="font-size: 16px;"><?php echo round($pod['data']->best_percentage); ?>%</div>
                                <div style="font-size: 10px; opacity: 0.9;">⭐ <?php echo esc_html($pod['data']->best_points); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Full Rankings List -->
                    <?php if (count($leaderboard) > 3): ?>
                    <div class="sp-card" style="padding: 0; overflow: hidden;">
                        <?php foreach ($leaderboard as $idx => $entry):
                            if ($idx < 3) continue;
                            $rank = $idx + 1;
                            $lb_user = get_userdata($entry->user_id);
                            $lb_first = $lb_user ? $lb_user->first_name : '';
                            $lb_middle = $lb_user ? get_user_meta($entry->user_id, 'sp_middle_name', true) : '';
                            $lb_name = trim($lb_first . ' ' . $lb_middle) ?: $entry->display_name;
                            $is_current = ($entry->user_id == $current_user->ID);
                            $pct_color = $entry->best_percentage >= 80 ? '#059669' : ($entry->best_percentage >= 50 ? '#D97706' : '#DC2626');
                        ?>
                        <div style="display: flex; align-items: center; gap: var(--sp-space-sm); padding: 10px var(--sp-space-md); border-bottom: 1px solid var(--sp-border-color); <?php echo $is_current ? 'background: #FFFBEB;' : ''; ?>">
                            <span style="width: 28px; font-weight: 700; font-size: 14px; color: var(--sp-text-secondary); text-align: center;">
                                <?php echo $rank; ?>
                            </span>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-size: 13px; font-weight: <?php echo $is_current ? '700' : '600'; ?>; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; <?php echo $is_current ? 'color: var(--sp-primary);' : ''; ?>">
                                    <?php echo esc_html($lb_name); ?>
                                    <?php if ($is_current): ?><span style="font-size: 10px;"> (أنا)</span><?php endif; ?>
                                </div>
                            </div>
                            <div style="text-align: left; white-space: nowrap;">
                                <span style="font-weight: 700; font-size: 13px; color: <?php echo $pct_color; ?>;"><?php echo round($entry->best_percentage); ?>%</span>
                                <span style="font-size: 11px; color: var(--sp-text-secondary); margin-right: 4px;">⭐<?php echo esc_html($entry->best_points); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Current user rank if not in top 20 -->
                    <?php
                    $user_in_list = false;
                    foreach ($leaderboard as $entry) {
                        if ($entry->user_id == $current_user->ID) { $user_in_list = true; break; }
                    }
                    if (!$user_in_list && $best_attempt):
                    ?>
                    <div class="sp-card" style="margin-top: var(--sp-space-sm); background: #FFFBEB; border: 2px solid var(--sp-primary); padding: var(--sp-space-sm) var(--sp-space-md);">
                        <div style="display: flex; align-items: center; gap: var(--sp-space-sm);">
                            <span style="font-size: 16px;">📍</span>
                            <div style="flex: 1;">
                                <div style="font-size: 13px; font-weight: 700; color: var(--sp-primary);">
                                    <?php _e('نتيجتك', 'saint-porphyrius'); ?>
                                </div>
                            </div>
                            <div style="text-align: left;">
                                <span style="font-weight: 700; font-size: 14px; color: var(--sp-primary);"><?php echo round($best_attempt->percentage); ?>%</span>
                                <span style="font-size: 12px; color: var(--sp-text-secondary);">⭐<?php echo esc_html($best_attempt->points_awarded); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
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
    
    // Timing tracking for anti-random-guessing
    var questionTimings = {};   // qid -> seconds spent
    var questionShowTime = {};  // qid -> timestamp when shown
    var quizStartTime = Date.now();
    
    // Record when first question is shown
    var firstSlide = $('.sp-quiz-question-slide[data-index="0"]');
    if (firstSlide.length) {
        questionShowTime[firstSlide.data('qid')] = Date.now();
    }
    
    // Option selection - maps back to original DB index via shuffle-map
    $(document).on('click', '.sp-quiz-option-label', function() {
        var qid = $(this).data('qid');
        var displayIdx = $(this).data('idx');
        
        // Get the shuffle map from the question slide
        var $slide = $(this).closest('.sp-quiz-question-slide');
        var shuffleMap = $slide.data('shuffle-map');
        
        // Map display index back to original DB index
        var originalIdx = (shuffleMap && shuffleMap[displayIdx] !== undefined) ? shuffleMap[displayIdx] : displayIdx;
        
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
        
        // Store the ORIGINAL index (for backend scoring)
        answers[qid] = originalIdx;
        
        // Record timing for this question
        if (questionShowTime[qid]) {
            questionTimings[qid] = (Date.now() - questionShowTime[qid]) / 1000;
        }
        
        // Update dot
        var dotIndex = $slide.data('index');
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
        
        // Record show time when question appears
        var $newSlide = $('.sp-quiz-question-slide[data-index="' + index + '"]');
        var newQid = $newSlide.data('qid');
        if (!questionShowTime[newQid]) {
            questionShowTime[newQid] = Date.now();
        }
        
        $('.sp-quiz-question-slide').hide();
        $newSlide.fadeIn(200);
        
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
        
        // For unanswered questions, record their timing too (as time since shown or total quiz time)
        $('.sp-quiz-question-slide').each(function() {
            var qid = $(this).data('qid');
            if (!questionTimings[qid] && questionShowTime[qid]) {
                questionTimings[qid] = (Date.now() - questionShowTime[qid]) / 1000;
            }
        });
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('جاري الإرسال...');
        
        $.ajax({
            url: spApp.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sp_quiz_submit_attempt',
                nonce: spApp.nonce,
                content_id: contentId,
                answers: JSON.stringify(answers),
                timings: JSON.stringify(questionTimings)
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
        var minPct = parseFloat(data.min_points_percentage || <?php echo $settings['min_points_percentage']; ?>);
        var pointsEligible = data.points_eligible;
        var isPassed = pct >= <?php echo $settings['passing_percentage']; ?>;
        var penaltyApplied = data.penalty_applied;
        var gradientColor = penaltyApplied ? 'linear-gradient(135deg, #DC2626, #991B1B)' : (isPassed ? 'linear-gradient(135deg, #10B981, #059669)' : 'linear-gradient(135deg, #F59E0B, #D97706)');
        var emoji = penaltyApplied ? '🚨' : (pct >= 90 ? '🏆' : (pct >= 70 ? '🌟' : (isPassed ? '✅' : '💪')));
        
        var html = '<div class="sp-card" style="overflow: hidden; margin: var(--sp-space-md);">';
        html += '<div style="background: ' + gradientColor + '; color: white; padding: var(--sp-space-xl); text-align: center;">';
        html += '<div style="font-size: 64px; margin-bottom: var(--sp-space-md);">' + emoji + '</div>';
        html += '<h2 style="color: white; font-size: 24px; margin-bottom: var(--sp-space-sm);">' + 
                (penaltyApplied ? 'تم رصد تخمين عشوائي!' : (isPassed ? 'أحسنت!' : 'حاول مرة أخرى!')) + '</h2>';
        html += '<p style="font-size: 48px; font-weight: 800; color: white; margin: var(--sp-space-md) 0;">' + pct.toFixed(0) + '%</p>';
        html += '</div>';
        
        html += '<div style="padding: var(--sp-space-lg);">';
        html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--sp-space-md); margin-bottom: var(--sp-space-lg);">';
        html += '<div style="text-align: center;"><div style="font-size: 24px; font-weight: 700; color: var(--sp-primary);">' + data.score + '/' + data.total + '</div><div style="font-size: 12px; color: var(--sp-text-secondary);">إجابات صحيحة</div></div>';
        html += '<div style="text-align: center;"><div style="font-size: 24px; font-weight: 700; color: #F59E0B;">⭐ ' + data.points_earned + '</div><div style="font-size: 12px; color: var(--sp-text-secondary);">نقاط مكتسبة</div></div>';
        html += '</div>';
        
        // Show penalty warning if applied
        if (penaltyApplied) {
            html += '<div style="background: #FEE2E2; border-radius: var(--sp-radius-md); padding: var(--sp-space-md); text-align: center; margin-bottom: var(--sp-space-md); border: 2px solid #EF4444;">';
            html += '<p style="color: #991B1B; font-weight: 700; font-size: 15px; margin-bottom: 4px;">🚨 تم اكتشاف نمط تخمين عشوائي</p>';
            html += '<p style="color: #7F1D1D; font-size: 13px;">لم تحصل على أي نقاط وتم خصم <strong>' + data.penalty_deducted + '</strong> نقطة كعقوبة</p>';
            html += '<p style="color: #7F1D1D; font-size: 12px; margin-top: 4px;">💡 يرجى قراءة الأسئلة بعناية والإجابة بتفكير</p>';
            html += '</div>';
        }
        
        // Show minimum percentage rule feedback
        if (!pointsEligible && !penaltyApplied) {
            html += '<div style="background: #FEE2E2; border-radius: var(--sp-radius-md); padding: var(--sp-space-md); text-align: center; margin-bottom: var(--sp-space-md);">';
            html += '<p style="color: #991B1B; font-weight: 600;">⚠️ لم تحصل على نقاط - يجب تحقيق ' + minPct + '% على الأقل</p>';
            html += '<p style="color: #991B1B; font-size: 12px; margin-top: 4px;">نتيجتك: ' + pct.toFixed(0) + '% | المطلوب: ' + minPct + '%</p>';
            html += '</div>';
        } else if (data.additional_points > 0 && !penaltyApplied) {
            html += '<div style="background: #D1FAE5; border-radius: var(--sp-radius-md); padding: var(--sp-space-md); text-align: center; margin-bottom: var(--sp-space-md);">';
            html += '<p style="color: #065F46; font-weight: 600;">🎉 حصلت على ' + data.additional_points + ' نقطة إضافية!</p>';
            html += '</div>';
        } else if (data.points_earned >= data.max_points && !penaltyApplied) {
            html += '<div style="background: #FEF3C7; border-radius: var(--sp-radius-md); padding: var(--sp-space-md); text-align: center; margin-bottom: var(--sp-space-md);">';
            html += '<p style="color: #92400E;">🏆 لقد وصلت للحد الأقصى من النقاط لهذا الاختبار</p>';
            html += '</div>';
        }
        
        // =============================================
        // ANSWER REVIEW SECTION
        // =============================================
        if (data.review_questions && data.review_questions.length > 0) {
            var wrongCount = 0;
            for (var i = 0; i < data.review_questions.length; i++) {
                if (!data.review_questions[i].is_correct) wrongCount++;
            }
            
            if (wrongCount > 0) {
                html += '<div style="margin-top: var(--sp-space-md); margin-bottom: var(--sp-space-lg);">';
                html += '<h3 style="font-size: 16px; font-weight: 700; margin-bottom: var(--sp-space-md); display: flex; align-items: center; gap: 8px;">';
                html += '📝 مراجعة الإجابات الخاطئة <span style="font-size: 12px; color: var(--sp-text-secondary); font-weight: 400;">(' + wrongCount + ' من ' + data.total + ')</span></h3>';
                
                for (var i = 0; i < data.review_questions.length; i++) {
                    var rq = data.review_questions[i];
                    if (rq.is_correct) continue;
                    
                    html += '<div style="background: white; border: 1px solid var(--sp-border-color); border-radius: var(--sp-radius-md); padding: var(--sp-space-md); margin-bottom: 8px;">';
                    html += '<p style="font-size: 14px; font-weight: 600; margin-bottom: var(--sp-space-sm); line-height: 1.7;">❓ ' + escapeHtml(rq.question_text) + '</p>';
                    
                    if (rq.options && rq.options.length > 0) {
                        for (var j = 0; j < rq.options.length; j++) {
                            var optText = rq.options[j].text || '';
                            var isUserAnswer = (j === rq.user_answer);
                            var isCorrect = (j === rq.correct_answer);
                            var optStyle = 'padding: 6px 10px; border-radius: 6px; font-size: 13px; line-height: 1.6; margin-bottom: 4px; ';
                            
                            if (isCorrect) {
                                optStyle += 'background: #D1FAE5; color: #065F46; font-weight: 600;';
                            } else if (isUserAnswer) {
                                optStyle += 'background: #FEE2E2; color: #991B1B; text-decoration: line-through;';
                            } else {
                                optStyle += 'color: var(--sp-text-secondary);';
                            }
                            
                            var prefix = isCorrect ? '✅ ' : (isUserAnswer ? '❌ ' : '○ ');
                            html += '<div style="' + optStyle + '">' + prefix + escapeHtml(optText) + '</div>';
                        }
                    }
                    
                    if (rq.explanation) {
                        html += '<div style="margin-top: 6px; padding: 8px; background: #EFF6FF; border-radius: 6px; font-size: 12px; color: #1E40AF; line-height: 1.6;">';
                        html += '💡 ' + escapeHtml(rq.explanation);
                        html += '</div>';
                    }
                    
                    html += '</div>';
                }
                html += '</div>';
            }
        }
        
        html += '<div style="display: flex; gap: 8px; flex-direction: column;">';
        html += '<a href="' + spApp.appUrl + '/quizzes?quiz_id=<?php echo $quiz_id; ?>&take" class="sp-btn sp-btn-primary sp-btn-lg sp-btn-block" style="background: linear-gradient(135deg, #8B5CF6, #7C3AED);">🔄 إعادة المحاولة</a>';
        html += '<a href="' + spApp.appUrl + '/quizzes" class="sp-btn sp-btn-outline sp-btn-lg sp-btn-block">📋 العودة للاختبارات</a>';
        html += '</div>';
        html += '</div></div>';
        
        $('#sp-quiz-result').html(html).fadeIn(300);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    // Helper: escape HTML for safe rendering
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }
    
})(jQuery);
</script>
<?php endif; ?>
