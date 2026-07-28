<?php
/**
 * Saint Porphyrius - Admin Quiz Management (Mobile)
 * Manage categorized Christian quizzes with AI generation
 */

if (!defined('ABSPATH')) {
    exit;
}

$quiz_handler = SP_Quiz::get_instance();
$settings = $quiz_handler->get_settings();
$stats = $quiz_handler->get_quiz_stats();
$categories = $quiz_handler->get_categories();
$all_content = $quiz_handler->get_all_content();

// Current tab
$tab = sanitize_text_field($_GET['tab'] ?? 'overview');
$content_id = absint($_GET['content_id'] ?? 0);
$category_id = absint($_GET['category_id'] ?? 0);

// If viewing/editing single content
$edit_content = null;
$edit_questions = array();
if ($content_id) {
    $edit_content = $quiz_handler->get_content($content_id);
    if ($edit_content) {
        $edit_questions = $quiz_handler->get_questions($content_id, false);
        $tab = 'edit-content';
    }
}
?>

<!-- Admin Header -->
<div class="sp-unified-header sp-admin-header">
    <div class="sp-header-inner">
        <a href="<?php echo $content_id ? home_url('/app/admin/quizzes') : home_url('/app/admin'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('الاختبارات المسيحية', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-spacer"></div>
    </div>
</div>

<main class="sp-page-content sp-admin-content">

<?php if ($tab === 'overview' && !$content_id): ?>
    <!-- Stats Grid -->
    <div class="sp-admin-stats-grid">
        <div class="sp-admin-stat-card">
            <div class="sp-admin-stat-icon" style="background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);">📚</div>
            <div class="sp-admin-stat-info">
                <span class="sp-admin-stat-value"><?php echo esc_html($stats['total_content']); ?></span>
                <span class="sp-admin-stat-label"><?php _e('اختبارات', 'saint-porphyrius'); ?></span>
            </div>
        </div>
        <div class="sp-admin-stat-card <?php echo $stats['pending_review'] > 0 ? 'has-alert' : ''; ?>">
            <div class="sp-admin-stat-icon" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);">⏳</div>
            <div class="sp-admin-stat-info">
                <span class="sp-admin-stat-value"><?php echo esc_html($stats['pending_review']); ?></span>
                <span class="sp-admin-stat-label"><?php _e('بانتظار المراجعة', 'saint-porphyrius'); ?></span>
            </div>
            <?php if ($stats['pending_review'] > 0): ?>
                <span class="sp-admin-stat-badge"><?php _e('جديد', 'saint-porphyrius'); ?></span>
            <?php endif; ?>
        </div>
        <div class="sp-admin-stat-card">
            <div class="sp-admin-stat-icon" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">✅</div>
            <div class="sp-admin-stat-info">
                <span class="sp-admin-stat-value"><?php echo esc_html($stats['published_content']); ?></span>
                <span class="sp-admin-stat-label"><?php _e('منشور', 'saint-porphyrius'); ?></span>
            </div>
        </div>
        <div class="sp-admin-stat-card">
            <div class="sp-admin-stat-icon" style="background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);">👥</div>
            <div class="sp-admin-stat-info">
                <span class="sp-admin-stat-value"><?php echo esc_html($stats['total_participants']); ?></span>
                <span class="sp-admin-stat-label"><?php _e('مشاركون', 'saint-porphyrius'); ?></span>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="sp-quiz-tabs" style="display: flex; gap: 8px; padding: 0 var(--sp-space-md); margin-bottom: var(--sp-space-md); overflow-x: auto;">
        <a href="<?php echo home_url('/app/admin/quizzes'); ?>" class="sp-btn sp-btn-sm <?php echo $tab === 'overview' ? 'sp-btn-primary' : 'sp-btn-outline'; ?>">📋 المحتوى</a>
        <a href="<?php echo home_url('/app/admin/quizzes?tab=categories'); ?>" class="sp-btn sp-btn-sm <?php echo $tab === 'categories' ? 'sp-btn-primary' : 'sp-btn-outline'; ?>">📂 الفئات</a>
        <a href="<?php echo home_url('/app/admin/quizzes?tab=settings'); ?>" class="sp-btn sp-btn-sm <?php echo $tab === 'settings' ? 'sp-btn-primary' : 'sp-btn-outline'; ?>">⚙️ الإعدادات</a>
        <a href="<?php echo home_url('/app/admin/quizzes?tab=new-content'); ?>" class="sp-btn sp-btn-sm sp-btn-primary" style="background: linear-gradient(135deg, #10B981, #059669);">➕ محتوى جديد</a>
    </div>

    <!-- Content List -->
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title"><?php _e('المحتوى والاختبارات', 'saint-porphyrius'); ?></h3>
        </div>
        
        <?php if (empty($all_content)): ?>
            <div class="sp-card" style="text-align: center; padding: var(--sp-space-xl);">
                <div style="font-size: 48px; margin-bottom: var(--sp-space-md);">📖</div>
                <h3 style="margin-bottom: var(--sp-space-sm);"><?php _e('لا يوجد محتوى بعد', 'saint-porphyrius'); ?></h3>
                <p style="color: var(--sp-text-secondary); margin-bottom: var(--sp-space-md);"><?php _e('ابدأ بإنشاء فئة ثم أضف محتوى لتوليد اختبارات مسيحية', 'saint-porphyrius'); ?></p>
                <a href="<?php echo home_url('/app/admin/quizzes?tab=new-content'); ?>" class="sp-btn sp-btn-primary">➕ <?php _e('إضافة محتوى جديد', 'saint-porphyrius'); ?></a>
            </div>
        <?php else: ?>
            <div class="sp-quiz-content-list">
                <?php foreach ($all_content as $item): 
                    $status_labels = array(
                        'draft'          => array('مسودة', '#94A3B8', '📝'),
                        'ai_processing'  => array('جاري المعالجة', '#F59E0B', '⏳'),
                        'ai_ready'       => array('بانتظار المراجعة', '#8B5CF6', '🔍'),
                        'approved'       => array('موافق عليه', '#3B82F6', '✅'),
                        'published'      => array('منشور', '#10B981', '🟢'),
                        'archived'       => array('مؤرشف', '#6B7280', '📦'),
                    );
                    $status = $status_labels[$item->status] ?? array('غير معروف', '#6B7280', '❓');
                ?>
                <a href="<?php echo home_url('/app/admin/quizzes?content_id=' . $item->id); ?>" class="sp-admin-menu-item" style="margin-bottom: 4px;">
                    <div class="sp-admin-menu-icon" style="background: <?php echo esc_attr($item->category_color); ?>20; color: <?php echo esc_attr($item->category_color); ?>; font-size: 20px;">
                        <?php echo esc_html($item->category_icon); ?>
                    </div>
                    <div class="sp-admin-menu-content" style="flex: 1;">
                        <h4 style="font-size: 14px; margin-bottom: 4px;"><?php echo esc_html($item->title_ar); ?></h4>
                        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                            <span style="font-size: 11px; background: <?php echo esc_attr($status[1]); ?>20; color: <?php echo esc_attr($status[1]); ?>; padding: 2px 8px; border-radius: 10px;">
                                <?php echo $status[2] . ' ' . $status[0]; ?>
                            </span>
                            <span style="font-size: 11px; color: var(--sp-text-secondary);">
                                <?php echo esc_html($item->question_count); ?> سؤال
                            </span>
                            <span style="font-size: 11px; color: var(--sp-text-secondary);">
                                ⭐ <?php echo esc_html($item->max_points); ?> نقطة
                            </span>
                            <?php if ($item->total_participants > 0): ?>
                            <span style="font-size: 11px; color: var(--sp-text-secondary);">
                                👥 <?php echo esc_html($item->total_participants); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

<?php elseif ($tab === 'categories'): ?>
    <!-- Categories Management -->
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title"><?php _e('فئات الاختبارات', 'saint-porphyrius'); ?></h3>
        </div>
        
        <!-- Add Category Form -->
        <div class="sp-card" style="padding: var(--sp-space-md); margin-bottom: var(--sp-space-md);">
            <h4 style="margin-bottom: var(--sp-space-md);">➕ <?php _e('إضافة فئة جديدة', 'saint-porphyrius'); ?></h4>
            <form id="sp-quiz-category-form">
                <input type="hidden" name="category_id" value="0">
                <div class="sp-form-group" style="margin-bottom: var(--sp-space-sm);">
                    <label class="sp-form-label"><?php _e('اسم الفئة (عربي)', 'saint-porphyrius'); ?></label>
                    <input type="text" name="name_ar" class="sp-form-input" required placeholder="مثال: دراسة الكتاب المقدس">
                </div>
                <div class="sp-form-group" style="margin-bottom: var(--sp-space-sm);">
                    <label class="sp-form-label"><?php _e('اسم الفئة (إنجليزي)', 'saint-porphyrius'); ?></label>
                    <input type="text" name="name_en" class="sp-form-input" placeholder="e.g. Bible Study">
                </div>
                <div class="sp-form-group" style="margin-bottom: var(--sp-space-sm);">
                    <label class="sp-form-label"><?php _e('الوصف', 'saint-porphyrius'); ?></label>
                    <textarea name="description_ar" class="sp-form-input" rows="2" placeholder="وصف مختصر للفئة"></textarea>
                </div>
                <div style="display: flex; gap: var(--sp-space-sm); margin-bottom: var(--sp-space-sm);">
                    <div class="sp-form-group" style="flex: 1;">
                        <label class="sp-form-label"><?php _e('الأيقونة', 'saint-porphyrius'); ?></label>
                        <input type="text" name="icon" class="sp-form-input" value="📖" style="text-align: center; font-size: 24px;">
                    </div>
                    <div class="sp-form-group" style="flex: 1;">
                        <label class="sp-form-label"><?php _e('اللون', 'saint-porphyrius'); ?></label>
                        <input type="color" name="color" value="#3B82F6" style="width: 100%; height: 42px; border-radius: var(--sp-radius-md); border: 1px solid var(--sp-border-color);">
                    </div>
                    <div class="sp-form-group" style="flex: 1;">
                        <label class="sp-form-label"><?php _e('الترتيب', 'saint-porphyrius'); ?></label>
                        <input type="number" name="sort_order" class="sp-form-input" value="0" min="0">
                    </div>
                </div>
                <button type="submit" class="sp-btn sp-btn-primary sp-btn-block"><?php _e('حفظ الفئة', 'saint-porphyrius'); ?></button>
            </form>
        </div>
        
        <!-- Category List -->
        <?php if (empty($categories)): ?>
            <div class="sp-card" style="text-align: center; padding: var(--sp-space-lg);">
                <p style="color: var(--sp-text-secondary);"><?php _e('لا توجد فئات بعد. أضف فئة أعلاه.', 'saint-porphyrius'); ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($categories as $cat): ?>
            <div class="sp-card sp-quiz-category-item" data-id="<?php echo esc_attr($cat->id); ?>" style="padding: var(--sp-space-md); margin-bottom: 8px; display: flex; align-items: center; gap: var(--sp-space-sm);">
                <div style="width: 44px; height: 44px; border-radius: var(--sp-radius-md); background: <?php echo esc_attr($cat->color); ?>20; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                    <?php echo esc_html($cat->icon); ?>
                </div>
                <div style="flex: 1;">
                    <h4 style="font-size: 14px; font-weight: 600;"><?php echo esc_html($cat->name_ar); ?></h4>
                    <?php if ($cat->description_ar): ?>
                        <p style="font-size: 12px; color: var(--sp-text-secondary); margin-top: 2px;"><?php echo esc_html($cat->description_ar); ?></p>
                    <?php endif; ?>
                </div>
                <span style="font-size: 11px; padding: 2px 8px; border-radius: 10px; background: <?php echo $cat->is_active ? '#D1FAE520' : '#FEE2E220'; ?>; color: <?php echo $cat->is_active ? '#059669' : '#DC2626'; ?>;">
                    <?php echo $cat->is_active ? 'مفعل' : 'معطل'; ?>
                </span>
                <button class="sp-btn sp-btn-sm sp-btn-outline sp-quiz-edit-category" data-cat='<?php echo esc_attr(wp_json_encode($cat)); ?>' style="padding: 4px 8px;">✏️</button>
                <button class="sp-btn sp-btn-sm sp-quiz-delete-category" data-id="<?php echo esc_attr($cat->id); ?>" style="padding: 4px 8px; color: #DC2626;">🗑️</button>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

<?php elseif ($tab === 'new-content'): ?>
    <!-- New Content Form -->
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title"><?php _e('إضافة محتوى جديد', 'saint-porphyrius'); ?></h3>
        </div>
        
        <?php if (empty($categories)): ?>
            <div class="sp-card" style="text-align: center; padding: var(--sp-space-xl);">
                <div style="font-size: 48px; margin-bottom: var(--sp-space-md);">📂</div>
                <h3 style="margin-bottom: var(--sp-space-sm);"><?php _e('أنشئ فئة أولاً', 'saint-porphyrius'); ?></h3>
                <p style="color: var(--sp-text-secondary); margin-bottom: var(--sp-space-md);"><?php _e('يجب إنشاء فئة واحدة على الأقل قبل إضافة محتوى', 'saint-porphyrius'); ?></p>
                <a href="<?php echo home_url('/app/admin/quizzes?tab=categories'); ?>" class="sp-btn sp-btn-primary">📂 <?php _e('إدارة الفئات', 'saint-porphyrius'); ?></a>
            </div>
        <?php else: ?>
            <div class="sp-card" style="padding: var(--sp-space-md);">
                <form id="sp-quiz-content-form">
                    <input type="hidden" name="content_id" value="0">
                    
                    <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                        <label class="sp-form-label"><?php _e('الفئة', 'saint-porphyrius'); ?> *</label>
                        <select name="category_id" class="sp-form-input" required>
                            <option value=""><?php _e('اختر الفئة...', 'saint-porphyrius'); ?></option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo esc_attr($cat->id); ?>"><?php echo esc_html($cat->icon . ' ' . $cat->name_ar); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                        <label class="sp-form-label"><?php _e('عنوان المحتوى (عربي)', 'saint-porphyrius'); ?> *</label>
                        <input type="text" name="title_ar" class="sp-form-input" required placeholder="مثال: تأملات في إنجيل يوحنا - الإصحاح 3">
                    </div>
                    
                    <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                        <label class="sp-form-label"><?php _e('نوع المحتوى', 'saint-porphyrius'); ?></label>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <label class="sp-quiz-type-option" style="display: flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: var(--sp-radius-md); border: 2px solid var(--sp-border-color); cursor: pointer; flex: 1; min-width: 120px;">
                                <input type="radio" name="content_type" value="text" checked> 📝 نص
                            </label>
                            <label class="sp-quiz-type-option" style="display: flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: var(--sp-radius-md); border: 2px solid var(--sp-border-color); cursor: pointer; flex: 1; min-width: 120px;">
                                <input type="radio" name="content_type" value="youtube"> 🎥 يوتيوب
                            </label>
                            <label class="sp-quiz-type-option" style="display: flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: var(--sp-radius-md); border: 2px solid var(--sp-border-color); cursor: pointer; flex: 1; min-width: 120px;">
                                <input type="radio" name="content_type" value="bible"> ✝️ كتاب مقدس
                            </label>
                            <label class="sp-quiz-type-option" style="display: flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: var(--sp-radius-md); border: 2px solid var(--sp-border-color); cursor: pointer; flex: 1; min-width: 120px;">
                                <input type="radio" name="content_type" value="mixed"> 📚 مختلط
                            </label>
                        </div>
                    </div>
                    
                    <!-- YouTube URL field (shown when youtube or mixed is selected) -->
                    <div class="sp-form-group sp-quiz-youtube-fields" style="margin-bottom: var(--sp-space-md); display: none;">
                        <label class="sp-form-label"><?php _e('رابط يوتيوب', 'saint-porphyrius'); ?></label>
                        <div style="display: flex; gap: 8px;">
                            <input type="url" name="youtube_url" class="sp-form-input" placeholder="https://www.youtube.com/watch?v=...">
                            <button type="button" class="sp-btn sp-btn-outline sp-quiz-fetch-youtube" style="white-space: nowrap;">🔍 جلب</button>
                        </div>
                        <div class="sp-quiz-youtube-preview" style="display: none; margin-top: var(--sp-space-sm);"></div>
                        
                        <div style="margin-top: var(--sp-space-sm);">
                            <label class="sp-form-label"><?php _e('نص الفيديو / النسخة النصية', 'saint-porphyrius'); ?></label>
                            <textarea name="youtube_transcript" class="sp-form-input" rows="4" placeholder="الصق نص الفيديو هنا (إن وجد) - يمكنك نسخه من YouTube أو كتابته يدوياً"></textarea>
                            <p style="font-size: 11px; color: var(--sp-text-secondary); margin-top: 4px;">💡 إذا كان الفيديو يحتوي على ترجمة، يمكنك نسخ النص من YouTube لتحسين جودة الأسئلة</p>
                        </div>
                    </div>
                    
                    <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                        <label class="sp-form-label"><?php _e('المحتوى النصي', 'saint-porphyrius'); ?> *</label>
                        <textarea name="raw_input" class="sp-form-input" rows="10" required placeholder="أدخل النص الكتابي أو التعليمي هنا...&#10;&#10;مثال:&#10;- آيات من الكتاب المقدس&#10;- تأملات روحية&#10;- شرح لاهوتي&#10;- قصة من سير القديسين"></textarea>
                        <p style="font-size: 11px; color: var(--sp-text-secondary); margin-top: 4px;">كلما كان المحتوى أكثر تفصيلاً، كانت الأسئلة المُنشأة أفضل وأدق</p>
                    </div>
                    
                    <div style="display: flex; gap: var(--sp-space-sm); margin-bottom: var(--sp-space-md);">
                        <div class="sp-form-group" style="flex: 1;">
                            <label class="sp-form-label"><?php _e('أقصى نقاط', 'saint-porphyrius'); ?></label>
                            <input type="number" name="max_points" class="sp-form-input" value="<?php echo esc_attr($settings['default_max_points']); ?>" min="1">
                        </div>
                        <div class="sp-form-group" style="flex: 1;">
                            <label class="sp-form-label"><?php _e('عدد الأسئلة', 'saint-porphyrius'); ?></label>
                            <input type="number" name="num_questions" class="sp-form-input" value="<?php echo esc_attr($settings['questions_per_quiz']); ?>" min="5" max="100">
                        </div>
                    </div>
                    
                    <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                        <label class="sp-form-label"><?php _e('تعليمات إضافية للذكاء الاصطناعي', 'saint-porphyrius'); ?></label>
                        <textarea name="admin_notes" class="sp-form-input" rows="3" placeholder="تعليمات اختيارية لتحسين جودة الأسئلة المُنشأة...&#10;مثال: ركز على الآيات الرئيسية، اجعل الأسئلة تتدرج في الصعوبة"></textarea>
                    </div>
                    
                    <div style="display: flex; gap: var(--sp-space-sm);">
                        <button type="submit" name="action_type" value="save" class="sp-btn sp-btn-outline sp-btn-block">
                            💾 <?php _e('حفظ كمسودة', 'saint-porphyrius'); ?>
                        </button>
                        <button type="submit" name="action_type" value="generate" class="sp-btn sp-btn-primary sp-btn-block" style="background: linear-gradient(135deg, #8B5CF6, #7C3AED);">
                            🤖 <?php _e('حفظ + إنشاء بالذكاء الاصطناعي', 'saint-porphyrius'); ?>
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

<?php elseif ($tab === 'edit-content' && $edit_content): ?>
    <!-- Edit Content / Review AI Output -->
    <div class="sp-section">
        <!-- Content Status Banner -->
        <?php
        $status_config = array(
            'draft'          => array('bg' => '#F1F5F9', 'color' => '#475569', 'icon' => '📝', 'label' => 'مسودة'),
            'ai_processing'  => array('bg' => '#FEF3C7', 'color' => '#92400E', 'icon' => '⏳', 'label' => 'جاري معالجة الذكاء الاصطناعي'),
            'ai_ready'       => array('bg' => '#EDE9FE', 'color' => '#5B21B6', 'icon' => '🔍', 'label' => 'بانتظار مراجعة المسؤول'),
            'approved'       => array('bg' => '#DBEAFE', 'color' => '#1E40AF', 'icon' => '✅', 'label' => 'تمت الموافقة - جاهز للنشر'),
            'published'      => array('bg' => '#D1FAE5', 'color' => '#065F46', 'icon' => '🟢', 'label' => 'منشور ومتاح للأعضاء'),
            'archived'       => array('bg' => '#F3F4F6', 'color' => '#4B5563', 'icon' => '📦', 'label' => 'مؤرشف'),
        );
        $sc = $status_config[$edit_content->status] ?? $status_config['draft'];
        ?>
        <div style="background: <?php echo $sc['bg']; ?>; border-radius: var(--sp-radius-lg); padding: var(--sp-space-md); margin-bottom: var(--sp-space-md); display: flex; align-items: center; gap: var(--sp-space-sm);">
            <span style="font-size: 24px;"><?php echo $sc['icon']; ?></span>
            <div>
                <strong style="color: <?php echo $sc['color']; ?>;"><?php echo $sc['label']; ?></strong>
                <p style="font-size: 12px; color: var(--sp-text-secondary); margin-top: 2px;">
                    <?php echo esc_html($edit_content->category_icon . ' ' . $edit_content->category_name); ?> · 
                    <?php echo esc_html($edit_content->question_count); ?> سؤال · 
                    ⭐ <?php echo esc_html($edit_content->max_points); ?> نقطة
                </p>
            </div>
        </div>
        
        <!-- Edit Content Details (collapsible) -->
        <details id="sp-quiz-edit-details" style="margin-bottom: var(--sp-space-md);">
            <summary class="sp-card" style="padding: var(--sp-space-md); cursor: pointer; display: flex; align-items: center; gap: var(--sp-space-sm); list-style: none;">
                <span style="font-size: 18px;">✏️</span>
                <span style="font-weight: 600; flex: 1;"><?php _e('تعديل بيانات الاختبار', 'saint-porphyrius'); ?></span>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </summary>
            <div class="sp-card" style="padding: var(--sp-space-md); border-top: none; border-radius: 0 0 var(--sp-radius-lg) var(--sp-radius-lg); margin-top: -1px;">
                <form id="sp-quiz-edit-content-form" data-content-id="<?php echo esc_attr($edit_content->id); ?>">
                    <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                        <label class="sp-form-label"><?php _e('العنوان (عربي)', 'saint-porphyrius'); ?></label>
                        <input type="text" name="title_ar" class="sp-form-input" value="<?php echo esc_attr($edit_content->title_ar); ?>" required>
                    </div>
                    
                    <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                        <label class="sp-form-label"><?php _e('الفئة', 'saint-porphyrius'); ?></label>
                        <select name="category_id" class="sp-form-input">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo esc_attr($cat->id); ?>" <?php selected($cat->id, $edit_content->category_id); ?>><?php echo esc_html($cat->icon . ' ' . $cat->name_ar); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                        <label class="sp-form-label"><?php _e('أقصى نقاط', 'saint-porphyrius'); ?></label>
                        <input type="number" name="max_points" class="sp-form-input" value="<?php echo esc_attr($edit_content->max_points); ?>" min="1">
                    </div>
                    
                    <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                        <label class="sp-form-label"><?php _e('نوع المحتوى', 'saint-porphyrius'); ?></label>
                        <select name="content_type" class="sp-form-input">
                            <option value="text" <?php selected($edit_content->content_type, 'text'); ?>>📝 نص</option>
                            <option value="youtube" <?php selected($edit_content->content_type, 'youtube'); ?>>🎥 يوتيوب</option>
                            <option value="bible" <?php selected($edit_content->content_type, 'bible'); ?>>✝️ كتاب مقدس</option>
                            <option value="mixed" <?php selected($edit_content->content_type, 'mixed'); ?>>📚 مختلط</option>
                        </select>
                    </div>
                    
                    <?php if ($edit_content->youtube_url): ?>
                    <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                        <label class="sp-form-label"><?php _e('رابط يوتيوب', 'saint-porphyrius'); ?></label>
                        <input type="url" name="youtube_url" class="sp-form-input" value="<?php echo esc_attr($edit_content->youtube_url); ?>">
                    </div>
                    <?php endif; ?>
                    
                    <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                        <label class="sp-form-label"><?php _e('المحتوى النصي الأصلي', 'saint-porphyrius'); ?></label>
                        <textarea name="raw_input" class="sp-form-input" rows="6"><?php echo esc_textarea($edit_content->raw_input); ?></textarea>
                    </div>
                    
                    <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                        <label class="sp-form-label"><?php _e('ملاحظات المسؤول', 'saint-porphyrius'); ?></label>
                        <textarea name="admin_notes" class="sp-form-input" rows="2"><?php echo esc_textarea($edit_content->admin_notes); ?></textarea>
                    </div>
                    
                    <button type="submit" class="sp-btn sp-btn-primary sp-btn-block">💾 <?php _e('حفظ التعديلات', 'saint-porphyrius'); ?></button>
                </form>
            </div>
        </details>
        
        <!-- Content Details -->
        <details style="margin-bottom: var(--sp-space-md);">
            <summary style="cursor: pointer; padding: var(--sp-space-md); background: var(--sp-bg-secondary); border-radius: var(--sp-radius-md); font-weight: 600; display: flex; align-items: center; gap: 8px;">
                📄 <span><?php _e('المحتوى التعليمي', 'saint-porphyrius'); ?></span>
            </summary>
        <div class="sp-card" style="padding: var(--sp-space-md); margin-top: 8px;">
            <h3 style="margin-bottom: var(--sp-space-md);"><?php echo esc_html($edit_content->title_ar); ?></h3>
            
            <!-- YouTube Embed -->
            <?php if ($edit_content->youtube_url): 
                $ai_handler = SP_Quiz_AI::get_instance();
                $video_id = $ai_handler->extract_youtube_id($edit_content->youtube_url);
                if ($video_id): ?>
                <div style="position: relative; padding-bottom: 56.25%; height: 0; border-radius: var(--sp-radius-md); overflow: hidden; margin-bottom: var(--sp-space-md);">
                    <iframe src="https://www.youtube.com/embed/<?php echo esc_attr($video_id); ?>" 
                        style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" 
                        frameborder="0" allowfullscreen></iframe>
                </div>
            <?php endif; endif; ?>
            
            <!-- AI Formatted Content -->
            <?php if ($edit_content->ai_formatted_content): ?>
                <div class="sp-quiz-ai-content" style="background: var(--sp-bg-secondary); border-radius: var(--sp-radius-md); padding: var(--sp-space-md); margin-bottom: var(--sp-space-md);">
                    <h4 style="margin-bottom: var(--sp-space-sm); color: var(--sp-primary);">🤖 المحتوى المُنسق بالذكاء الاصطناعي</h4>
                    <div class="sp-quiz-formatted-text">
                        <?php echo wp_kses_post($edit_content->ai_formatted_content); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Raw Input (collapsible) -->
            <details style="margin-bottom: var(--sp-space-md);">
                <summary style="cursor: pointer; font-weight: 600; color: var(--sp-text-secondary); padding: var(--sp-space-sm) 0;">📄 المحتوى الأصلي</summary>
                <div style="background: var(--sp-bg-secondary); border-radius: var(--sp-radius-md); padding: var(--sp-space-md); margin-top: var(--sp-space-sm); white-space: pre-wrap; font-size: 13px;">
                    <?php echo esc_html($edit_content->raw_input); ?>
                </div>
            </details>
            
            <!-- Action Buttons -->
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <?php if ($edit_content->status === 'draft'): ?>
                    <button class="sp-btn sp-btn-primary sp-quiz-generate-btn" data-content-id="<?php echo esc_attr($edit_content->id); ?>" style="background: linear-gradient(135deg, #8B5CF6, #7C3AED); flex: 1;">
                        🤖 <?php _e('إنشاء بالذكاء الاصطناعي', 'saint-porphyrius'); ?>
                    </button>
                <?php elseif (in_array($edit_content->status, array('ai_ready', 'approved'))): ?>
                    <button class="sp-btn sp-btn-primary sp-quiz-approve-btn" data-content-id="<?php echo esc_attr($edit_content->id); ?>" style="flex: 1;">
                        ✅ <?php _e('الموافقة', 'saint-porphyrius'); ?>
                    </button>
                    <button class="sp-btn sp-btn-primary sp-quiz-publish-btn" data-content-id="<?php echo esc_attr($edit_content->id); ?>" style="background: linear-gradient(135deg, #10B981, #059669); flex: 1;">
                        🚀 <?php _e('نشر', 'saint-porphyrius'); ?>
                    </button>
                <?php endif; ?>
                
                <?php if (in_array($edit_content->status, array('ai_ready', 'approved', 'published'))): ?>
                    <button class="sp-btn sp-btn-outline sp-quiz-regenerate-btn" data-content-id="<?php echo esc_attr($edit_content->id); ?>" style="flex: 1;">
                        🔄 <?php _e('إعادة إنشاء', 'saint-porphyrius'); ?>
                    </button>
                    <button class="sp-btn sp-btn-outline sp-quiz-generate-more-btn" data-content-id="<?php echo esc_attr($edit_content->id); ?>" style="flex: 1; color: #059669; border-color: #059669;">
                        ➕ <?php _e('إضافة أسئلة', 'saint-porphyrius'); ?>
                    </button>
                <?php endif; ?>
                
                <button class="sp-btn sp-btn-outline sp-quiz-delete-content-btn" data-content-id="<?php echo esc_attr($edit_content->id); ?>" style="color: #DC2626; border-color: #DC2626;">
                    🗑️
                </button>
            </div>
        </div>
        </details>
        
        <!-- Regeneration Panel (hidden by default) -->
        <div id="sp-quiz-regen-panel" style="display: none; margin-bottom: var(--sp-space-md);">
            <div class="sp-card" style="padding: var(--sp-space-md); border: 2px solid var(--sp-primary);">
                <h4 style="margin-bottom: var(--sp-space-sm);">🔄 <?php _e('إعادة إنشاء الأسئلة', 'saint-porphyrius'); ?></h4>
                <p style="font-size: 12px; color: var(--sp-text-secondary); margin-bottom: var(--sp-space-md);">⚠️ سيتم حذف جميع الأسئلة الحالية واستبدالها بأسئلة جديدة</p>
                <textarea id="sp-quiz-regen-instructions" class="sp-form-input" rows="3" placeholder="مثال: اجعل الأسئلة أصعب، ركز على التفاصيل الدقيقة، أضف أسئلة عن الأشخاص..."></textarea>
                <div style="display: flex; gap: 8px; margin-top: var(--sp-space-sm);">
                    <input type="number" id="sp-quiz-regen-count" class="sp-form-input" value="50" min="5" max="100" style="width: 80px;">
                    <span style="align-self: center; font-size: 13px;">سؤال</span>
                    <button class="sp-btn sp-btn-primary sp-quiz-do-regenerate" data-content-id="<?php echo esc_attr($edit_content->id); ?>" style="margin-right: auto; background: linear-gradient(135deg, #8B5CF6, #7C3AED);">
                        🤖 إعادة إنشاء
                    </button>
                    <button class="sp-btn sp-btn-outline sp-quiz-cancel-regen">إلغاء</button>
                </div>
            </div>
        </div>
        
        <!-- Generate More Questions Panel (hidden by default) -->
        <div id="sp-quiz-more-panel" style="display: none; margin-bottom: var(--sp-space-md);">
            <div class="sp-card" style="padding: var(--sp-space-md); border: 2px solid #059669;">
                <h4 style="margin-bottom: var(--sp-space-sm); color: #059669;">➕ <?php _e('إضافة أسئلة جديدة', 'saint-porphyrius'); ?></h4>
                <p style="font-size: 12px; color: var(--sp-text-secondary); margin-bottom: var(--sp-space-md);">
                    سيتم إنشاء أسئلة إضافية وإضافتها للأسئلة الموجودة (حالياً: <?php echo esc_html($edit_content->question_count); ?> سؤال)
                </p>
                <textarea id="sp-quiz-more-instructions" class="sp-form-input" rows="3" placeholder="تعليمات اختيارية: مثلاً ركز على جزء معين، أضف أسئلة صعبة، أسئلة عن تواريخ..."></textarea>
                <div style="display: flex; gap: 8px; margin-top: var(--sp-space-sm);">
                    <input type="number" id="sp-quiz-more-count" class="sp-form-input" value="20" min="5" max="50" style="width: 80px;">
                    <span style="align-self: center; font-size: 13px;">سؤال إضافي</span>
                    <button class="sp-btn sp-btn-primary sp-quiz-do-generate-more" data-content-id="<?php echo esc_attr($edit_content->id); ?>" style="margin-right: auto; background: linear-gradient(135deg, #10B981, #059669);">
                        ➕ إنشاء وإضافة
                    </button>
                    <button class="sp-btn sp-btn-outline sp-quiz-cancel-more">إلغاء</button>
                </div>
            </div>
        </div>
        
        <!-- Questions Review Section -->
        <?php if (!empty($edit_questions)): ?>
        <details style="margin-bottom: var(--sp-space-md);">
            <summary style="cursor: pointer; padding: var(--sp-space-md); background: var(--sp-bg-secondary); border-radius: var(--sp-radius-md); font-weight: 600; display: flex; align-items: center; gap: 8px;">
                📋 <span><?php _e('الأسئلة', 'saint-porphyrius'); ?> (<?php echo count($edit_questions); ?>)</span>
            </summary>
        <div class="sp-section" style="margin-top: 8px;">
            
            <div id="sp-quiz-questions-list">
                <?php foreach ($edit_questions as $index => $question): 
                    $options = json_decode($question->options, true);
                    $diff_colors = array('easy' => '#10B981', 'medium' => '#F59E0B', 'hard' => '#EF4444');
                    $diff_labels = array('easy' => 'سهل', 'medium' => 'متوسط', 'hard' => 'صعب');
                ?>
                <div class="sp-card sp-quiz-question-card" data-question-id="<?php echo esc_attr($question->id); ?>" style="padding: var(--sp-space-md); margin-bottom: 8px; <?php echo !$question->is_active ? 'opacity: 0.5;' : ''; ?>">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: var(--sp-space-sm);">
                        <span style="font-size: 12px; font-weight: 700; color: var(--sp-primary);">سؤال <?php echo $index + 1; ?></span>
                        <div style="display: flex; gap: 4px; align-items: center;">
                            <span style="font-size: 10px; padding: 2px 6px; border-radius: 8px; background: <?php echo $diff_colors[$question->difficulty]; ?>20; color: <?php echo $diff_colors[$question->difficulty]; ?>;">
                                <?php echo $diff_labels[$question->difficulty]; ?>
                            </span>
                            <button class="sp-quiz-edit-question-btn" data-qid="<?php echo esc_attr($question->id); ?>" style="background: none; border: none; cursor: pointer; font-size: 14px;">✏️</button>
                            <button class="sp-quiz-delete-question-btn" data-qid="<?php echo esc_attr($question->id); ?>" style="background: none; border: none; cursor: pointer; font-size: 14px;">🗑️</button>
                        </div>
                    </div>
                    
                    <p class="sp-quiz-question-text" style="font-weight: 600; margin-bottom: var(--sp-space-sm); font-size: 14px; line-height: 1.6;">
                        <?php echo esc_html($question->question_text); ?>
                    </p>
                    
                    <div class="sp-quiz-options-display" style="display: flex; flex-direction: column; gap: 4px;">
                        <?php if ($options): foreach ($options as $oidx => $opt): ?>
                            <div style="padding: 6px 12px; border-radius: var(--sp-radius-sm); font-size: 13px; 
                                <?php echo ($oidx == $question->correct_answer_index) 
                                    ? 'background: #D1FAE520; border: 1px solid #10B981; color: #065F46;' 
                                    : 'background: var(--sp-bg-secondary); border: 1px solid var(--sp-border-color);'; ?>">
                                <?php echo ($oidx == $question->correct_answer_index) ? '✅' : '⬜'; ?>
                                <?php echo esc_html($opt['text']); ?>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                    
                    <?php if ($question->explanation): ?>
                        <div style="margin-top: var(--sp-space-sm); padding: var(--sp-space-sm); background: #DBEAFE; border-radius: var(--sp-radius-sm); font-size: 12px; color: #1E40AF;">
                            💡 <?php echo esc_html($question->explanation); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Edit Form (hidden) -->
                    <div class="sp-quiz-question-edit-form" style="display: none; margin-top: var(--sp-space-md); padding-top: var(--sp-space-md); border-top: 1px solid var(--sp-border-color);">
                        <div class="sp-form-group" style="margin-bottom: var(--sp-space-sm);">
                            <label class="sp-form-label">نص السؤال</label>
                            <textarea class="sp-form-input sp-q-edit-text" rows="2"><?php echo esc_textarea($question->question_text); ?></textarea>
                        </div>
                        
                        <?php if ($options): foreach ($options as $oidx => $opt): ?>
                        <div style="display: flex; gap: 8px; margin-bottom: 4px; align-items: center;">
                            <input type="radio" name="correct_<?php echo $question->id; ?>" value="<?php echo $oidx; ?>" <?php checked($oidx, $question->correct_answer_index); ?>>
                            <input type="text" class="sp-form-input sp-q-edit-option" data-idx="<?php echo $oidx; ?>" value="<?php echo esc_attr($opt['text']); ?>" style="flex: 1;">
                        </div>
                        <?php endforeach; endif; ?>
                        
                        <div class="sp-form-group" style="margin-top: var(--sp-space-sm);">
                            <label class="sp-form-label">الشرح</label>
                            <input type="text" class="sp-form-input sp-q-edit-explanation" value="<?php echo esc_attr($question->explanation); ?>">
                        </div>
                        
                        <div style="display: flex; gap: 8px; margin-top: var(--sp-space-sm);">
                            <select class="sp-form-input sp-q-edit-difficulty" style="width: auto;">
                                <option value="easy" <?php selected($question->difficulty, 'easy'); ?>>سهل</option>
                                <option value="medium" <?php selected($question->difficulty, 'medium'); ?>>متوسط</option>
                                <option value="hard" <?php selected($question->difficulty, 'hard'); ?>>صعب</option>
                            </select>
                            <button class="sp-btn sp-btn-primary sp-btn-sm sp-quiz-save-question" data-qid="<?php echo esc_attr($question->id); ?>">💾 حفظ</button>
                            <button class="sp-btn sp-btn-outline sp-btn-sm sp-quiz-cancel-edit">إلغاء</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        </details>
        <?php endif; ?>
        
        <!-- Participants Section -->
        <?php 
        $participants = $quiz_handler->get_content_leaderboard($edit_content->id);
        if (!empty($participants)): 
        ?>
        <details style="margin-bottom: var(--sp-space-md);">
            <summary style="cursor: pointer; padding: var(--sp-space-md); background: var(--sp-bg-secondary); border-radius: var(--sp-radius-md); font-weight: 600; display: flex; align-items: center; gap: 8px;">
                👥 <span><?php _e('المشاركون', 'saint-porphyrius'); ?> (<?php echo count($participants); ?>)</span>
            </summary>
        <div class="sp-section" style="margin-top: 8px;">
            
            <div class="sp-card" style="padding: 0; overflow: hidden;">
                <!-- Header -->
                <div style="display: grid; grid-template-columns: 40px 1fr 60px 60px 50px; gap: 8px; padding: 10px var(--sp-space-md); background: var(--sp-bg-secondary); font-size: 11px; font-weight: 700; color: var(--sp-text-secondary); border-bottom: 1px solid var(--sp-border-color);">
                    <span>#</span>
                    <span><?php _e('الاسم', 'saint-porphyrius'); ?></span>
                    <span style="text-align: center;"><?php _e('النتيجة', 'saint-porphyrius'); ?></span>
                    <span style="text-align: center;"><?php _e('النقاط', 'saint-porphyrius'); ?></span>
                    <span style="text-align: center;"><?php _e('محاولات', 'saint-porphyrius'); ?></span>
                </div>
                
                <?php foreach ($participants as $rank => $p): 
                    $user = get_userdata($p->user_id);
                    $first_name = $user ? $user->first_name : '';
                    $middle_name = $user ? get_user_meta($p->user_id, 'sp_middle_name', true) : '';
                    $display = trim($first_name . ' ' . $middle_name) ?: $p->display_name;
                    $rank_num = $rank + 1;
                    $medal = '';
                    if ($rank_num === 1) $medal = '🥇';
                    elseif ($rank_num === 2) $medal = '🥈';
                    elseif ($rank_num === 3) $medal = '🥉';
                    $got_max = ($p->best_points >= $edit_content->max_points);
                    $pct_color = $p->best_percentage >= 80 ? '#059669' : ($p->best_percentage >= 50 ? '#D97706' : '#DC2626');
                ?>
                <div style="display: grid; grid-template-columns: 40px 1fr 60px 60px 50px; gap: 8px; padding: 10px var(--sp-space-md); align-items: center; border-bottom: 1px solid var(--sp-border-color); font-size: 13px; <?php echo $got_max ? 'background: #F0FDF4;' : ''; ?>">
                    <span style="font-weight: 700; font-size: 14px;">
                        <?php echo $medal ?: $rank_num; ?>
                    </span>
                    <div style="min-width: 0;">
                        <div style="font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?php echo esc_html($display); ?>
                            <?php if ($got_max): ?><span style="font-size: 11px;">🏆</span><?php endif; ?>
                        </div>
                        <div style="font-size: 10px; color: var(--sp-text-secondary);">
                            <?php echo esc_html(date_i18n('j M', strtotime($p->last_attempt))); ?>
                        </div>
                    </div>
                    <div style="text-align: center;">
                        <span style="font-weight: 700; color: <?php echo $pct_color; ?>;"><?php echo round($p->best_percentage); ?>%</span>
                    </div>
                    <div style="text-align: center;">
                        <span style="font-weight: 700; color: var(--sp-primary);">⭐ <?php echo esc_html($p->best_points); ?></span>
                    </div>
                    <div style="text-align: center; color: var(--sp-text-secondary);">
                        <?php echo esc_html($p->attempt_count); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        </details>
        <?php endif; ?>
    </div>

<?php elseif ($tab === 'settings'): ?>
    <!-- Settings -->
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title"><?php _e('إعدادات نظام الاختبارات', 'saint-porphyrius'); ?></h3>
        </div>
        
        <div class="sp-card" style="padding: var(--sp-space-md);">
            <form id="sp-quiz-settings-form">
                <!-- AI Settings -->
                <div style="margin-bottom: var(--sp-space-lg);">
                    <h4 style="margin-bottom: var(--sp-space-md); display: flex; align-items: center; gap: 8px;">
                        🤖 <?php _e('إعدادات الذكاء الاصطناعي', 'saint-porphyrius'); ?>
                    </h4>
                    
                    <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                        <label class="sp-form-label"><?php _e('مفتاح OpenAI API', 'saint-porphyrius'); ?></label>
                        <input type="password" name="openai_api_key" class="sp-form-input" 
                            value="<?php echo esc_attr($settings['openai_api_key']); ?>" 
                            placeholder="sk-...">
                        <p style="font-size: 11px; color: var(--sp-text-secondary); margin-top: 4px;">
                            احصل على مفتاح API من <a href="https://platform.openai.com/api-keys" target="_blank" style="color: var(--sp-primary);">platform.openai.com</a>
                        </p>
                    </div>
                    
                    <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                        <label class="sp-form-label"><?php _e('نموذج الذكاء الاصطناعي', 'saint-porphyrius'); ?></label>
                        <select name="ai_model" class="sp-form-input">
                            <option value="gpt-4o" <?php selected($settings['ai_model'], 'gpt-4o'); ?>>GPT-4o (الأفضل - موصى به)</option>
                            <option value="gpt-4o-mini" <?php selected($settings['ai_model'], 'gpt-4o-mini'); ?>>GPT-4o Mini (أسرع وأرخص)</option>
                            <option value="gpt-4-turbo" <?php selected($settings['ai_model'], 'gpt-4-turbo'); ?>>GPT-4 Turbo</option>
                        </select>
                    </div>
                </div>
                
                <!-- Quiz Settings -->
                <div style="margin-bottom: var(--sp-space-lg);">
                    <h4 style="margin-bottom: var(--sp-space-md); display: flex; align-items: center; gap: 8px;">
                        ⚙️ <?php _e('إعدادات الاختبارات', 'saint-porphyrius'); ?>
                    </h4>
                    
                    <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                        <label class="sp-form-label"><?php _e('عدد الأسئلة الافتراضي للإنشاء', 'saint-porphyrius'); ?></label>
                        <input type="number" name="questions_per_quiz" class="sp-form-input" 
                            value="<?php echo esc_attr($settings['questions_per_quiz']); ?>" min="5" max="100">
                        <p style="font-size: 11px; color: var(--sp-text-secondary); margin-top: 4px;">عدد الأسئلة التي ينشئها الذكاء الاصطناعي لكل محتوى (بنك الأسئلة)</p>
                    </div>
                    
                    <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                        <label class="sp-form-label"><?php _e('عدد أسئلة كل محاولة', 'saint-porphyrius'); ?></label>
                        <input type="number" name="questions_per_attempt" class="sp-form-input" 
                            value="<?php echo esc_attr($settings['questions_per_attempt']); ?>" min="5" max="100">
                        <p style="font-size: 11px; color: var(--sp-text-secondary); margin-top: 4px;">عدد الأسئلة العشوائية في كل محاولة اختبار (مثال: 10 أسئلة من بنك 50 سؤال)</p>
                    </div>
                    
                    <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                        <label class="sp-form-label"><?php _e('الحد الأدنى لكسب النقاط (%)', 'saint-porphyrius'); ?></label>
                        <input type="number" name="min_points_percentage" class="sp-form-input" 
                            value="<?php echo esc_attr($settings['min_points_percentage']); ?>" min="0" max="100">
                        <p style="font-size: 11px; color: var(--sp-text-secondary); margin-top: 4px;">يجب أن يحقق المستخدم هذه النسبة على الأقل ليحصل على نقاط (مثال: 50%)</p>
                    </div>
                    
                    <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                        <label class="sp-form-label"><?php _e('النقاط القصوى الافتراضية', 'saint-porphyrius'); ?></label>
                        <input type="number" name="default_max_points" class="sp-form-input" 
                            value="<?php echo esc_attr($settings['default_max_points']); ?>" min="1">
                    </div>
                    
                    <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                        <label class="sp-form-label"><?php _e('نسبة النجاح (%)', 'saint-porphyrius'); ?></label>
                        <input type="number" name="passing_percentage" class="sp-form-input" 
                            value="<?php echo esc_attr($settings['passing_percentage']); ?>" min="0" max="100">
                    </div>
                    
                    <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="enabled" value="1" <?php checked($settings['enabled'], 1); ?>>
                            <span class="sp-form-label" style="margin: 0;"><?php _e('تفعيل نظام الاختبارات', 'saint-porphyrius'); ?></span>
                        </label>
                    </div>
                </div>
                
                <!-- Anti-Random-Guessing Penalty Settings -->
                <div style="margin-bottom: var(--sp-space-lg);">
                    <h4 style="margin-bottom: var(--sp-space-md); display: flex; align-items: center; gap: 8px;">
                        🛡️ <?php _e('إعدادات عقوبة التخمين العشوائي', 'saint-porphyrius'); ?>
                    </h4>
                    <p style="font-size: 12px; color: var(--sp-text-secondary); margin-bottom: var(--sp-space-md); line-height: 1.7;">
                        يكتشف النظام أنماط الإجابة العشوائية (الإجابة السريعة جداً أو اختيار نفس الإجابة بشكل متكرر) ويطبق عقوبات تلقائية.
                    </p>
                    
                    <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="penalty_enabled" value="1" <?php checked($settings['penalty_enabled'], 1); ?>>
                            <span class="sp-form-label" style="margin: 0;"><?php _e('تفعيل نظام العقوبات', 'saint-porphyrius'); ?></span>
                        </label>
                    </div>
                    
                    <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                        <label class="sp-form-label"><?php _e('الحد الأدنى للثواني لكل سؤال', 'saint-porphyrius'); ?></label>
                        <input type="number" name="penalty_min_seconds" class="sp-form-input" 
                            value="<?php echo esc_attr($settings['penalty_min_seconds']); ?>" min="1" max="60">
                        <p style="font-size: 11px; color: var(--sp-text-secondary); margin-top: 4px;">إذا أجاب المستخدم في أقل من هذه المدة يُعتبر السؤال "إجابة سريعة"</p>
                    </div>
                    
                    <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                        <label class="sp-form-label"><?php _e('نسبة الإجابات السريعة لتفعيل العقوبة (%)', 'saint-porphyrius'); ?></label>
                        <input type="number" name="penalty_fast_threshold" class="sp-form-input" 
                            value="<?php echo esc_attr($settings['penalty_fast_threshold']); ?>" min="0" max="100">
                        <p style="font-size: 11px; color: var(--sp-text-secondary); margin-top: 4px;">إذا كانت نسبة الأسئلة المُجاب عليها بسرعة أعلى من هذا الحد تُطبّق العقوبة</p>
                    </div>
                    
                    <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                        <label class="sp-form-label"><?php _e('نسبة تكرار نفس الإجابة لتفعيل العقوبة (%)', 'saint-porphyrius'); ?></label>
                        <input type="number" name="penalty_same_answer_pct" class="sp-form-input" 
                            value="<?php echo esc_attr($settings['penalty_same_answer_pct']); ?>" min="0" max="100">
                        <p style="font-size: 11px; color: var(--sp-text-secondary); margin-top: 4px;">إذا اختار المستخدم نفس الخيار لعدد كبير من الأسئلة (مثلاً دائماً الخيار الأول)</p>
                    </div>
                    
                    <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                        <label class="sp-form-label"><?php _e('حد النتيجة لتطبيق العقوبة (%)', 'saint-porphyrius'); ?></label>
                        <input type="number" name="penalty_score_threshold" class="sp-form-input" 
                            value="<?php echo esc_attr($settings['penalty_score_threshold']); ?>" min="0" max="100">
                        <p style="font-size: 11px; color: var(--sp-text-secondary); margin-top: 4px;">تُطبّق العقوبة فقط إذا كانت النتيجة أقل من هذا الحد (لتجنب معاقبة المستخدمين الجيدين)</p>
                    </div>
                    
                    <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                        <label class="sp-form-label"><?php _e('نقاط العقوبة المخصومة', 'saint-porphyrius'); ?></label>
                        <input type="number" name="penalty_points" class="sp-form-input" 
                            value="<?php echo esc_attr($settings['penalty_points']); ?>" min="0" max="500">
                        <p style="font-size: 11px; color: var(--sp-text-secondary); margin-top: 4px;">عدد النقاط التي تُخصم من المستخدم عند اكتشاف تخمين عشوائي</p>
                    </div>
                </div>
                
                <button type="submit" class="sp-btn sp-btn-primary sp-btn-lg sp-btn-block">
                    💾 <?php _e('حفظ الإعدادات', 'saint-porphyrius'); ?>
                </button>
            </form>
        </div>
    </div>

<?php endif; ?>

</main>

<script>
(function($) {
    'use strict';
    
    // =========================================================================
    // Category Management
    // =========================================================================
    
    $('#sp-quiz-category-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        $btn.prop('disabled', true).text('جاري الحفظ...');
        
        $.ajax({
            url: spApp.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sp_quiz_save_category',
                nonce: spApp.nonce,
                id: $form.find('[name="category_id"]').val(),
                name_ar: $form.find('[name="name_ar"]').val(),
                name_en: $form.find('[name="name_en"]').val(),
                description_ar: $form.find('[name="description_ar"]').val(),
                icon: $form.find('[name="icon"]').val(),
                color: $form.find('[name="color"]').val(),
                sort_order: $form.find('[name="sort_order"]').val()
            },
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() { alert('حدث خطأ'); },
            complete: function() { $btn.prop('disabled', false).text('حفظ الفئة'); }
        });
    });
    
    // Edit category
    $(document).on('click', '.sp-quiz-edit-category', function() {
        var cat = $(this).data('cat');
        var $form = $('#sp-quiz-category-form');
        $form.find('[name="category_id"]').val(cat.id);
        $form.find('[name="name_ar"]').val(cat.name_ar);
        $form.find('[name="name_en"]').val(cat.name_en || '');
        $form.find('[name="description_ar"]').val(cat.description_ar || '');
        $form.find('[name="icon"]').val(cat.icon);
        $form.find('[name="color"]').val(cat.color);
        $form.find('[name="sort_order"]').val(cat.sort_order);
        $form.find('button[type="submit"]').text('تحديث الفئة');
        $('html, body').animate({ scrollTop: $form.offset().top - 80 }, 300);
    });
    
    // Delete category
    $(document).on('click', '.sp-quiz-delete-category', function() {
        if (!confirm('هل أنت متأكد من حذف هذه الفئة؟ سيتم حذف جميع المحتوى والاختبارات المرتبطة بها.')) return;
        var id = $(this).data('id');
        $.ajax({
            url: spApp.ajaxUrl,
            type: 'POST',
            data: { action: 'sp_quiz_delete_category', nonce: spApp.nonce, id: id },
            success: function(response) {
                if (response.success) window.location.reload();
                else alert(response.data.message);
            }
        });
    });
    
    // =========================================================================
    // Edit Content Details Form
    // =========================================================================
    
    $('#sp-quiz-edit-content-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var contentId = $form.data('content-id');
        $btn.prop('disabled', true).text('جاري الحفظ...');
        
        var formData = {
            action: 'sp_quiz_save_content',
            nonce: spApp.nonce,
            content_id: contentId,
            title_ar: $form.find('[name="title_ar"]').val(),
            category_id: $form.find('[name="category_id"]').val(),
            max_points: $form.find('[name="max_points"]').val(),
            content_type: $form.find('[name="content_type"]').val(),
            raw_input: $form.find('[name="raw_input"]').val(),
            admin_notes: $form.find('[name="admin_notes"]').val()
        };
        
        var youtubeUrl = $form.find('[name="youtube_url"]');
        if (youtubeUrl.length) {
            formData.youtube_url = youtubeUrl.val();
        }
        
        $.ajax({
            url: spApp.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $btn.text('✅ تم الحفظ!');
                    setTimeout(function() { window.location.reload(); }, 800);
                } else {
                    alert(response.data.message);
                    $btn.prop('disabled', false).text('💾 حفظ التعديلات');
                }
            },
            error: function() {
                alert('حدث خطأ');
                $btn.prop('disabled', false).text('💾 حفظ التعديلات');
            }
        });
    });
    
    // =========================================================================
    // Content Type Toggle
    // =========================================================================
    
    $('input[name="content_type"]').on('change', function() {
        var val = $(this).val();
        if (val === 'youtube' || val === 'mixed') {
            $('.sp-quiz-youtube-fields').slideDown(200);
        } else {
            $('.sp-quiz-youtube-fields').slideUp(200);
        }
    });
    
    // Type option visual selection
    $('input[name="content_type"]').on('change', function() {
        $('.sp-quiz-type-option').css('border-color', 'var(--sp-border-color)');
        $(this).closest('.sp-quiz-type-option').css('border-color', 'var(--sp-primary)');
    });
    
    // Fetch YouTube info
    $('.sp-quiz-fetch-youtube').on('click', function() {
        var url = $('[name="youtube_url"]').val();
        if (!url) { alert('أدخل رابط يوتيوب أولاً'); return; }
        var $btn = $(this);
        $btn.prop('disabled', true).text('جاري الجلب...');
        
        $.ajax({
            url: spApp.ajaxUrl,
            type: 'POST',
            data: { action: 'sp_quiz_get_youtube_info', nonce: spApp.nonce, youtube_url: url },
            success: function(response) {
                if (response.success) {
                    var html = '<div style="border-radius: 8px; overflow: hidden;">';
                    html += '<iframe width="100%" height="200" src="https://www.youtube.com/embed/' + response.data.video_id + '" frameborder="0" allowfullscreen></iframe>';
                    if (response.data.title) html += '<p style="padding: 8px; font-size: 13px; font-weight: 600;">' + response.data.title + '</p>';
                    html += '</div>';
                    $('.sp-quiz-youtube-preview').html(html).slideDown(200);
                } else {
                    alert(response.data.message);
                }
            },
            complete: function() { $btn.prop('disabled', false).text('🔍 جلب'); }
        });
    });
    
    // =========================================================================
    // Content Form Submission
    // =========================================================================
    
    var actionType = 'save';
    $('#sp-quiz-content-form button[name="action_type"]').on('click', function() {
        actionType = $(this).val();
    });
    
    $('#sp-quiz-content-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btns = $form.find('button[type="submit"]');
        $btns.prop('disabled', true);
        
        var formData = {
            action: 'sp_quiz_save_content',
            nonce: spApp.nonce,
            content_id: $form.find('[name="content_id"]').val(),
            category_id: $form.find('[name="category_id"]').val(),
            title_ar: $form.find('[name="title_ar"]').val(),
            content_type: $form.find('[name="content_type"]:checked').val(),
            raw_input: $form.find('[name="raw_input"]').val(),
            youtube_url: $form.find('[name="youtube_url"]').val(),
            youtube_transcript: $form.find('[name="youtube_transcript"]').val(),
            max_points: $form.find('[name="max_points"]').val(),
            admin_notes: $form.find('[name="admin_notes"]').val()
        };
        
        $.ajax({
            url: spApp.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    if (actionType === 'generate') {
                        // Now trigger AI generation
                        triggerAIGeneration(response.data.content_id, formData.admin_notes, $form.find('[name="num_questions"]').val());
                    } else {
                        window.location.href = spApp.appUrl + '/admin/quizzes?content_id=' + response.data.content_id;
                    }
                } else {
                    alert(response.data.message);
                    $btns.prop('disabled', false);
                }
            },
            error: function() { alert('حدث خطأ'); $btns.prop('disabled', false); }
        });
    });
    
    function triggerAIGeneration(contentId, notes, numQuestions) {
        showLoadingOverlay('🤖 جاري معالجة المحتوى وإنشاء الأسئلة بالذكاء الاصطناعي...<br><small>قد يستغرق هذا دقيقة أو دقيقتين</small>');
        
        $.ajax({
            url: spApp.ajaxUrl,
            type: 'POST',
            timeout: 180000,
            data: {
                action: 'sp_quiz_ai_generate',
                nonce: spApp.nonce,
                content_id: contentId,
                admin_notes: notes || '',
                num_questions: numQuestions || 50
            },
            success: function(response) {
                hideLoadingOverlay();
                if (response.success) {
                    window.location.href = spApp.appUrl + '/admin/quizzes?content_id=' + contentId;
                } else {
                    alert('خطأ: ' + response.data.message);
                    window.location.href = spApp.appUrl + '/admin/quizzes?content_id=' + contentId;
                }
            },
            error: function() {
                hideLoadingOverlay();
                alert('حدث خطأ أثناء الاتصال بالذكاء الاصطناعي');
                window.location.href = spApp.appUrl + '/admin/quizzes?content_id=' + contentId;
            }
        });
    }
    
    // =========================================================================
    // Content Actions (Generate, Approve, Publish, Delete)
    // =========================================================================
    
    $(document).on('click', '.sp-quiz-generate-btn', function() {
        var contentId = $(this).data('content-id');
        triggerAIGeneration(contentId, '', 50);
    });
    
    $(document).on('click', '.sp-quiz-approve-btn', function() {
        var contentId = $(this).data('content-id');
        $.ajax({
            url: spApp.ajaxUrl, type: 'POST',
            data: { action: 'sp_quiz_approve', nonce: spApp.nonce, content_id: contentId },
            success: function(r) { if (r.success) window.location.reload(); else alert(r.data.message); }
        });
    });
    
    $(document).on('click', '.sp-quiz-publish-btn', function() {
        if (!confirm('هل أنت متأكد من نشر هذا الاختبار؟ سيصبح متاحاً لجميع الأعضاء.')) return;
        var contentId = $(this).data('content-id');
        $.ajax({
            url: spApp.ajaxUrl, type: 'POST',
            data: { action: 'sp_quiz_publish', nonce: spApp.nonce, content_id: contentId },
            success: function(r) { if (r.success) window.location.reload(); else alert(r.data.message); }
        });
    });
    
    $(document).on('click', '.sp-quiz-delete-content-btn', function() {
        if (!confirm('هل أنت متأكد من حذف هذا المحتوى وجميع أسئلته؟ لا يمكن التراجع.')) return;
        var contentId = $(this).data('content-id');
        $.ajax({
            url: spApp.ajaxUrl, type: 'POST',
            data: { action: 'sp_quiz_delete_content', nonce: spApp.nonce, content_id: contentId },
            success: function(r) { if (r.success) window.location.href = spApp.appUrl + '/admin/quizzes'; else alert(r.data.message); }
        });
    });
    
    // Regeneration panel
    $(document).on('click', '.sp-quiz-regenerate-btn', function() {
        $('#sp-quiz-regen-panel').slideDown(200);
        $('#sp-quiz-more-panel').slideUp(200);
    });
    $(document).on('click', '.sp-quiz-cancel-regen', function() {
        $('#sp-quiz-regen-panel').slideUp(200);
    });
    $(document).on('click', '.sp-quiz-do-regenerate', function() {
        var contentId = $(this).data('content-id');
        var instructions = $('#sp-quiz-regen-instructions').val();
        var numQ = $('#sp-quiz-regen-count').val();
        
        showLoadingOverlay('🤖 جاري إعادة إنشاء الأسئلة...<br><small>قد يستغرق هذا دقيقة أو دقيقتين</small>');
        
        $.ajax({
            url: spApp.ajaxUrl, type: 'POST', timeout: 180000,
            data: { action: 'sp_quiz_ai_regenerate', nonce: spApp.nonce, content_id: contentId, admin_instructions: instructions, num_questions: numQ },
            success: function(r) { hideLoadingOverlay(); if (r.success) window.location.reload(); else { alert(r.data.message); hideLoadingOverlay(); } },
            error: function() { hideLoadingOverlay(); alert('حدث خطأ'); }
        });
    });
    
    // Generate More panel
    $(document).on('click', '.sp-quiz-generate-more-btn', function() {
        $('#sp-quiz-more-panel').slideDown(200);
        $('#sp-quiz-regen-panel').slideUp(200);
    });
    $(document).on('click', '.sp-quiz-cancel-more', function() {
        $('#sp-quiz-more-panel').slideUp(200);
    });
    $(document).on('click', '.sp-quiz-do-generate-more', function() {
        var contentId = $(this).data('content-id');
        var instructions = $('#sp-quiz-more-instructions').val();
        var numQ = $('#sp-quiz-more-count').val();
        
        showLoadingOverlay('➕ جاري إنشاء أسئلة إضافية...<br><small>قد يستغرق هذا دقيقة أو دقيقتين</small>');
        
        $.ajax({
            url: spApp.ajaxUrl, type: 'POST', timeout: 180000,
            data: { action: 'sp_quiz_ai_generate_more', nonce: spApp.nonce, content_id: contentId, admin_instructions: instructions, num_questions: numQ },
            success: function(r) { 
                hideLoadingOverlay(); 
                if (r.success) {
                    alert(r.data.message);
                    window.location.reload(); 
                } else { 
                    alert(r.data.message); 
                } 
            },
            error: function() { hideLoadingOverlay(); alert('حدث خطأ'); }
        });
    });
    
    // =========================================================================
    // Question Editing
    // =========================================================================
    
    $(document).on('click', '.sp-quiz-edit-question-btn', function() {
        var $card = $(this).closest('.sp-quiz-question-card');
        $card.find('.sp-quiz-question-edit-form').slideToggle(200);
    });
    
    $(document).on('click', '.sp-quiz-cancel-edit', function() {
        $(this).closest('.sp-quiz-question-edit-form').slideUp(200);
    });
    
    $(document).on('click', '.sp-quiz-save-question', function() {
        var qid = $(this).data('qid');
        var $card = $(this).closest('.sp-quiz-question-card');
        var $form = $card.find('.sp-quiz-question-edit-form');
        
        var options = [];
        var correctIdx = parseInt($form.find('input[name="correct_' + qid + '"]:checked').val()) || 0;
        $form.find('.sp-q-edit-option').each(function() {
            options.push({
                text: $(this).val(),
                is_correct: parseInt($(this).data('idx')) === correctIdx
            });
        });
        
        $.ajax({
            url: spApp.ajaxUrl, type: 'POST',
            data: {
                action: 'sp_quiz_update_question',
                nonce: spApp.nonce,
                question_id: qid,
                question_text: $form.find('.sp-q-edit-text').val(),
                options: JSON.stringify(options),
                correct_answer_index: correctIdx,
                explanation: $form.find('.sp-q-edit-explanation').val(),
                difficulty: $form.find('.sp-q-edit-difficulty').val(),
                is_active: 1
            },
            success: function(r) { if (r.success) window.location.reload(); else alert(r.data.message); }
        });
    });
    
    $(document).on('click', '.sp-quiz-delete-question-btn', function() {
        if (!confirm('هل أنت متأكد من حذف هذا السؤال؟')) return;
        var qid = $(this).data('qid');
        $.ajax({
            url: spApp.ajaxUrl, type: 'POST',
            data: { action: 'sp_quiz_delete_question', nonce: spApp.nonce, question_id: qid },
            success: function(r) { if (r.success) window.location.reload(); else alert(r.data.message); }
        });
    });
    
    // =========================================================================
    // Settings Form
    // =========================================================================
    
    $('#sp-quiz-settings-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        $btn.prop('disabled', true).text('جاري الحفظ...');
        
        $.ajax({
            url: spApp.ajaxUrl, type: 'POST',
            data: {
                action: 'sp_quiz_update_settings',
                nonce: spApp.nonce,
                openai_api_key: $form.find('[name="openai_api_key"]').val(),
                ai_model: $form.find('[name="ai_model"]').val(),
                questions_per_quiz: $form.find('[name="questions_per_quiz"]').val(),
                questions_per_attempt: $form.find('[name="questions_per_attempt"]').val(),
                min_points_percentage: $form.find('[name="min_points_percentage"]').val(),
                default_max_points: $form.find('[name="default_max_points"]').val(),
                passing_percentage: $form.find('[name="passing_percentage"]').val(),
                enabled: $form.find('[name="enabled"]').is(':checked') ? 1 : 0,
                // Penalty settings
                penalty_enabled: $form.find('[name="penalty_enabled"]').is(':checked') ? 1 : 0,
                penalty_min_seconds: $form.find('[name="penalty_min_seconds"]').val(),
                penalty_fast_threshold: $form.find('[name="penalty_fast_threshold"]').val(),
                penalty_same_answer_pct: $form.find('[name="penalty_same_answer_pct"]').val(),
                penalty_score_threshold: $form.find('[name="penalty_score_threshold"]').val(),
                penalty_points: $form.find('[name="penalty_points"]').val()
            },
            success: function(r) {
                if (r.success) {
                    $btn.text('✅ تم الحفظ!');
                    setTimeout(function() { $btn.text('💾 حفظ الإعدادات').prop('disabled', false); }, 2000);
                } else {
                    alert(r.data.message);
                    $btn.prop('disabled', false).text('💾 حفظ الإعدادات');
                }
            },
            error: function() { alert('حدث خطأ'); $btn.prop('disabled', false).text('💾 حفظ الإعدادات'); }
        });
    });
    
    // =========================================================================
    // Loading Overlay
    // =========================================================================
    
    function showLoadingOverlay(msg) {
        var html = '<div id="sp-quiz-loading-overlay" style="position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 9999; display: flex; align-items: center; justify-content: center;">';
        html += '<div style="background: white; border-radius: 16px; padding: 32px; text-align: center; max-width: 320px; margin: 16px;">';
        html += '<div class="sp-quiz-ai-spinner" style="width: 48px; height: 48px; border: 4px solid #E5E7EB; border-top-color: #8B5CF6; border-radius: 50%; animation: sp-spin 1s linear infinite; margin: 0 auto 16px;"></div>';
        html += '<p style="font-size: 15px; font-weight: 600; color: #1F2937;">' + msg + '</p>';
        html += '</div></div>';
        html += '<style>@keyframes sp-spin { to { transform: rotate(360deg); } }</style>';
        $('body').append(html);
    }
    
    function hideLoadingOverlay() {
        $('#sp-quiz-loading-overlay').remove();
    }
    
})(jQuery);
</script>
