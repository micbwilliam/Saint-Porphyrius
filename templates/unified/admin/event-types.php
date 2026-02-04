<?php
/**
 * Saint Porphyrius - Admin Event Types (Mobile)
 * Create, edit, and manage event types with configurable points
 */

if (!defined('ABSPATH')) {
    exit;
}

$event_types = SP_Event_Types::get_instance();

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sp_event_type_action'])) {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'sp_event_type_action')) {
        $message = __('خطأ في التحقق', 'saint-porphyrius');
        $message_type = 'error';
    } else {
        $action = sanitize_text_field($_POST['sp_event_type_action']);
        
        if ($action === 'create') {
            $result = $event_types->create($_POST);
            if (is_wp_error($result)) {
                $message = $result->get_error_message();
                $message_type = 'error';
            } else {
                $message = __('تم إنشاء نوع الفعالية بنجاح', 'saint-porphyrius');
                $message_type = 'success';
            }
        } elseif ($action === 'update' && !empty($_POST['type_id'])) {
            $result = $event_types->update(absint($_POST['type_id']), $_POST);
            if (is_wp_error($result)) {
                $message = $result->get_error_message();
                $message_type = 'error';
            } else {
                $message = __('تم تحديث نوع الفعالية بنجاح', 'saint-porphyrius');
                $message_type = 'success';
            }
        } elseif ($action === 'delete' && !empty($_POST['type_id'])) {
            $result = $event_types->delete(absint($_POST['type_id']));
            if (is_wp_error($result)) {
                $message = $result->get_error_message();
                $message_type = 'error';
            } else {
                $message = __('تم حذف نوع الفعالية', 'saint-porphyrius');
                $message_type = 'success';
            }
        } elseif ($action === 'toggle_active' && !empty($_POST['type_id'])) {
            $type = $event_types->get(absint($_POST['type_id']));
            if ($type) {
                $result = $event_types->update(absint($_POST['type_id']), array('is_active' => $type->is_active ? 0 : 1));
                if (!is_wp_error($result)) {
                    $message = $type->is_active ? __('تم إلغاء تفعيل النوع', 'saint-porphyrius') : __('تم تفعيل النوع', 'saint-porphyrius');
                    $message_type = 'success';
                }
            }
        }
    }
}

// Get types
$types = $event_types->get_all();
$show_form = isset($_GET['action']) && $_GET['action'] === 'new';
$edit_type = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['type_id'])) {
    $edit_type = $event_types->get(absint($_GET['type_id']));
}

// Available icons
$available_icons = array(
    '⛪' => __('الكنيسة', 'saint-porphyrius'),
    '📖' => __('الكتاب المقدس', 'saint-porphyrius'),
    '🙏' => __('الصلاة', 'saint-porphyrius'),
    '❤️' => __('الخدمة', 'saint-porphyrius'),
    '✝️' => __('الصليب', 'saint-porphyrius'),
    '🕯️' => __('الشمعة', 'saint-porphyrius'),
    '🎵' => __('الترانيم', 'saint-porphyrius'),
    '👥' => __('الاجتماع', 'saint-porphyrius'),
    '🎉' => __('الاحتفال', 'saint-porphyrius'),
    '📚' => __('الدراسة', 'saint-porphyrius'),
    '🏠' => __('الزيارة المنزلية', 'saint-porphyrius'),
    '🌍' => __('الإرسالية', 'saint-porphyrius'),
    '🍞' => __('التناول', 'saint-porphyrius'),
    '💒' => __('الزفاف', 'saint-porphyrius'),
    '👶' => __('المعمودية', 'saint-porphyrius'),
    '⭐' => __('مناسبة خاصة', 'saint-porphyrius'),
    '🎯' => __('هدف', 'saint-porphyrius'),
    '🏃' => __('نشاط', 'saint-porphyrius'),
    '🎤' => __('محاضرة', 'saint-porphyrius'),
    '🎭' => __('مسرح', 'saint-porphyrius'),
);

// Available colors
$available_colors = array(
    '#6C9BCF' => __('أزرق', 'saint-porphyrius'),
    '#96C291' => __('أخضر', 'saint-porphyrius'),
    '#F2D388' => __('ذهبي', 'saint-porphyrius'),
    '#E57373' => __('أحمر', 'saint-porphyrius'),
    '#9575CD' => __('بنفسجي', 'saint-porphyrius'),
    '#4DB6AC' => __('تركوازي', 'saint-porphyrius'),
    '#FFB74D' => __('برتقالي', 'saint-porphyrius'),
    '#F06292' => __('وردي', 'saint-porphyrius'),
    '#7986CB' => __('نيلي', 'saint-porphyrius'),
    '#81C784' => __('أخضر فاتح', 'saint-porphyrius'),
);
?>

<!-- Admin Header -->
<div class="sp-unified-header sp-admin-header">
    <div class="sp-header-inner">
        <a href="<?php echo ($show_form || $edit_type) ? home_url('/app/admin/event-types') : home_url('/app/admin'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title">
            <?php 
            if ($edit_type) {
                _e('تعديل نوع الفعالية', 'saint-porphyrius');
            } elseif ($show_form) {
                _e('نوع فعالية جديد', 'saint-porphyrius');
            } else {
                _e('أنواع الفعاليات', 'saint-porphyrius');
            }
            ?>
        </h1>
        <?php if (!$show_form && !$edit_type): ?>
        <a href="<?php echo home_url('/app/admin/event-types?action=new'); ?>" class="sp-header-action">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
        </a>
        <?php else: ?>
        <div class="sp-header-spacer"></div>
        <?php endif; ?>
    </div>
</div>

<!-- Main Content -->
<main class="sp-page-content sp-admin-content">
    <?php if ($message): ?>
        <div class="sp-alert sp-alert-<?php echo esc_attr($message_type); ?>">
            <?php echo esc_html($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($show_form || $edit_type): ?>
        <!-- Event Type Form -->
        <form method="post" class="sp-admin-form">
            <?php wp_nonce_field('sp_event_type_action'); ?>
            <input type="hidden" name="sp_event_type_action" value="<?php echo $edit_type ? 'update' : 'create'; ?>">
            <?php if ($edit_type): ?>
                <input type="hidden" name="type_id" value="<?php echo esc_attr($edit_type->id); ?>">
            <?php endif; ?>
            
            <!-- Basic Info Section -->
            <div class="sp-form-section">
                <h3 class="sp-form-section-title">📋 <?php _e('المعلومات الأساسية', 'saint-porphyrius'); ?></h3>
                
                <div class="sp-form-group">
                    <label class="sp-form-label"><?php _e('الاسم (عربي)', 'saint-porphyrius'); ?> <span class="required">*</span></label>
                    <input type="text" name="name_ar" required class="sp-form-input" 
                           value="<?php echo $edit_type ? esc_attr($edit_type->name_ar) : ''; ?>"
                           placeholder="<?php _e('مثال: القداس الإلهي', 'saint-porphyrius'); ?>">
                </div>
                
                <div class="sp-form-group">
                    <label class="sp-form-label"><?php _e('الاسم (إنجليزي)', 'saint-porphyrius'); ?></label>
                    <input type="text" name="name_en" class="sp-form-input"
                           value="<?php echo $edit_type ? esc_attr($edit_type->name_en) : ''; ?>"
                           placeholder="<?php _e('مثال: Divine Liturgy', 'saint-porphyrius'); ?>">
                </div>
                
                <div class="sp-form-group">
                    <label class="sp-form-label"><?php _e('الوصف', 'saint-porphyrius'); ?></label>
                    <textarea name="description" class="sp-form-textarea" rows="2"
                              placeholder="<?php _e('وصف مختصر لنوع الفعالية...', 'saint-porphyrius'); ?>"><?php echo $edit_type ? esc_textarea($edit_type->description) : ''; ?></textarea>
                </div>
            </div>
            
            <!-- Appearance Section -->
            <div class="sp-form-section">
                <h3 class="sp-form-section-title">🎨 <?php _e('المظهر', 'saint-porphyrius'); ?></h3>
                
                <div class="sp-form-group">
                    <label class="sp-form-label"><?php _e('الأيقونة', 'saint-porphyrius'); ?></label>
                    <div class="sp-icon-grid">
                        <?php 
                        $current_icon = $edit_type ? $edit_type->icon : '⛪';
                        foreach ($available_icons as $icon => $label): 
                        ?>
                            <label class="sp-icon-option <?php echo $icon === $current_icon ? 'selected' : ''; ?>">
                                <input type="radio" name="icon" value="<?php echo esc_attr($icon); ?>" 
                                       <?php checked($current_icon, $icon); ?>>
                                <span class="sp-icon-display"><?php echo esc_html($icon); ?></span>
                                <span class="sp-icon-label"><?php echo esc_html($label); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="sp-form-group">
                    <label class="sp-form-label"><?php _e('اللون', 'saint-porphyrius'); ?></label>
                    <div class="sp-color-grid">
                        <?php 
                        $current_color = $edit_type ? $edit_type->color : '#6C9BCF';
                        foreach ($available_colors as $color => $label): 
                        ?>
                            <label class="sp-color-option <?php echo $color === $current_color ? 'selected' : ''; ?>">
                                <input type="radio" name="color" value="<?php echo esc_attr($color); ?>" 
                                       <?php checked($current_color, $color); ?>>
                                <span class="sp-color-display" style="background-color: <?php echo esc_attr($color); ?>"></span>
                                <span class="sp-color-label"><?php echo esc_html($label); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Custom color input -->
                    <div class="sp-custom-color-row" style="margin-top: var(--sp-space-md);">
                        <label class="sp-form-label-small"><?php _e('أو اختر لون مخصص:', 'saint-porphyrius'); ?></label>
                        <input type="color" name="custom_color" class="sp-color-input" 
                               value="<?php echo esc_attr($current_color); ?>"
                               onchange="selectCustomColor(this.value)">
                    </div>
                </div>
                
                <!-- Preview -->
                <div class="sp-form-group">
                    <label class="sp-form-label"><?php _e('معاينة', 'saint-porphyrius'); ?></label>
                    <div class="sp-event-type-preview" id="event-type-preview">
                        <span class="sp-preview-icon" id="preview-icon"><?php echo esc_html($current_icon); ?></span>
                        <span class="sp-preview-name" id="preview-name"><?php echo $edit_type ? esc_html($edit_type->name_ar) : __('اسم النوع', 'saint-porphyrius'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Points Section -->
            <div class="sp-form-section">
                <h3 class="sp-form-section-title">⭐ <?php _e('نظام النقاط', 'saint-porphyrius'); ?></h3>
                
                <div class="sp-points-info-card">
                    <div class="sp-points-info-icon">💡</div>
                    <div class="sp-points-info-text">
                        <?php _e('حدد عدد النقاط التي سيحصل عليها الأعضاء عند الحضور أو يخسرونها عند الغياب.', 'saint-porphyrius'); ?>
                    </div>
                </div>
                
                <div class="sp-points-cards-row">
                    <div class="sp-points-card positive">
                        <div class="sp-points-card-header">
                            <span class="sp-points-card-icon">✅</span>
                            <span class="sp-points-card-title"><?php _e('نقاط الحضور', 'saint-porphyrius'); ?></span>
                        </div>
                        <input type="number" name="attendance_points" class="sp-form-input sp-points-input"
                               value="<?php echo $edit_type ? esc_attr($edit_type->attendance_points) : '10'; ?>" 
                               min="0" max="100">
                        <span class="sp-points-card-desc"><?php _e('للحضور في الوقت', 'saint-porphyrius'); ?></span>
                    </div>
                    
                    <div class="sp-points-card warning">
                        <div class="sp-points-card-header">
                            <span class="sp-points-card-icon">⏰</span>
                            <span class="sp-points-card-title"><?php _e('نقاط التأخير', 'saint-porphyrius'); ?></span>
                        </div>
                        <input type="number" name="late_points" class="sp-form-input sp-points-input"
                               value="<?php echo $edit_type ? esc_attr($edit_type->late_points ?? 5) : '5'; ?>" 
                               min="0" max="100">
                        <span class="sp-points-card-desc"><?php _e('للحضور متأخراً', 'saint-porphyrius'); ?></span>
                    </div>
                    
                    <div class="sp-points-card negative">
                        <div class="sp-points-card-header">
                            <span class="sp-points-card-icon">❌</span>
                            <span class="sp-points-card-title"><?php _e('خصم الغياب', 'saint-porphyrius'); ?></span>
                        </div>
                        <input type="number" name="absence_penalty" class="sp-form-input sp-points-input"
                               value="<?php echo $edit_type ? esc_attr($edit_type->absence_penalty) : '5'; ?>" 
                               min="0" max="100">
                        <span class="sp-points-card-desc"><?php _e('للغياب بدون عذر', 'saint-porphyrius'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Excuse Points Section -->
            <div class="sp-form-section">
                <h3 class="sp-form-section-title">📝 <?php _e('نقاط الاعتذار', 'saint-porphyrius'); ?></h3>
                
                <div class="sp-points-info-card">
                    <div class="sp-points-info-icon">📅</div>
                    <div class="sp-points-info-text">
                        <?php _e('كلما اعتذر العضو مبكراً، كلما كان الخصم أقل. حدد النقاط المخصومة بناءً على عدد الأيام قبل الفعالية.', 'saint-porphyrius'); ?>
                    </div>
                </div>
                
                <div class="sp-excuse-points-grid">
                    <div class="sp-excuse-point-item">
                        <div class="sp-excuse-point-label">
                            <span class="sp-excuse-days">7+</span>
                            <span class="sp-excuse-text"><?php _e('أيام قبل', 'saint-porphyrius'); ?></span>
                        </div>
                        <input type="number" name="excuse_points_7plus" class="sp-form-input"
                               value="<?php echo $edit_type ? esc_attr($edit_type->excuse_points_7plus ?? 2) : '2'; ?>" min="0">
                        <span class="sp-excuse-badge best"><?php _e('الأفضل', 'saint-porphyrius'); ?></span>
                    </div>
                    
                    <div class="sp-excuse-point-item">
                        <div class="sp-excuse-point-label">
                            <span class="sp-excuse-days">6</span>
                            <span class="sp-excuse-text"><?php _e('أيام قبل', 'saint-porphyrius'); ?></span>
                        </div>
                        <input type="number" name="excuse_points_6" class="sp-form-input"
                               value="<?php echo $edit_type ? esc_attr($edit_type->excuse_points_6 ?? 3) : '3'; ?>" min="0">
                    </div>
                    
                    <div class="sp-excuse-point-item">
                        <div class="sp-excuse-point-label">
                            <span class="sp-excuse-days">5</span>
                            <span class="sp-excuse-text"><?php _e('أيام قبل', 'saint-porphyrius'); ?></span>
                        </div>
                        <input type="number" name="excuse_points_5" class="sp-form-input"
                               value="<?php echo $edit_type ? esc_attr($edit_type->excuse_points_5 ?? 4) : '4'; ?>" min="0">
                    </div>
                    
                    <div class="sp-excuse-point-item">
                        <div class="sp-excuse-point-label">
                            <span class="sp-excuse-days">4</span>
                            <span class="sp-excuse-text"><?php _e('أيام قبل', 'saint-porphyrius'); ?></span>
                        </div>
                        <input type="number" name="excuse_points_4" class="sp-form-input"
                               value="<?php echo $edit_type ? esc_attr($edit_type->excuse_points_4 ?? 5) : '5'; ?>" min="0">
                    </div>
                    
                    <div class="sp-excuse-point-item">
                        <div class="sp-excuse-point-label">
                            <span class="sp-excuse-days">3</span>
                            <span class="sp-excuse-text"><?php _e('أيام قبل', 'saint-porphyrius'); ?></span>
                        </div>
                        <input type="number" name="excuse_points_3" class="sp-form-input"
                               value="<?php echo $edit_type ? esc_attr($edit_type->excuse_points_3 ?? 6) : '6'; ?>" min="0">
                    </div>
                    
                    <div class="sp-excuse-point-item">
                        <div class="sp-excuse-point-label">
                            <span class="sp-excuse-days">2</span>
                            <span class="sp-excuse-text"><?php _e('أيام قبل', 'saint-porphyrius'); ?></span>
                        </div>
                        <input type="number" name="excuse_points_2" class="sp-form-input"
                               value="<?php echo $edit_type ? esc_attr($edit_type->excuse_points_2 ?? 7) : '7'; ?>" min="0">
                    </div>
                    
                    <div class="sp-excuse-point-item">
                        <div class="sp-excuse-point-label">
                            <span class="sp-excuse-days">1</span>
                            <span class="sp-excuse-text"><?php _e('يوم قبل', 'saint-porphyrius'); ?></span>
                        </div>
                        <input type="number" name="excuse_points_1" class="sp-form-input"
                               value="<?php echo $edit_type ? esc_attr($edit_type->excuse_points_1 ?? 8) : '8'; ?>" min="0">
                    </div>
                    
                    <div class="sp-excuse-point-item worst">
                        <div class="sp-excuse-point-label">
                            <span class="sp-excuse-days">0</span>
                            <span class="sp-excuse-text"><?php _e('نفس اليوم', 'saint-porphyrius'); ?></span>
                        </div>
                        <input type="number" name="excuse_points_0" class="sp-form-input"
                               value="<?php echo $edit_type ? esc_attr($edit_type->excuse_points_0 ?? 10) : '10'; ?>" min="0">
                        <span class="sp-excuse-badge worst"><?php _e('الأسوأ', 'saint-porphyrius'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Status Section -->
            <div class="sp-form-section">
                <h3 class="sp-form-section-title">⚙️ <?php _e('الحالة', 'saint-porphyrius'); ?></h3>
                
                <div class="sp-form-group">
                    <label class="sp-toggle-switch">
                        <input type="checkbox" name="is_active" value="1" 
                               <?php echo (!$edit_type || $edit_type->is_active) ? 'checked' : ''; ?>>
                        <span class="sp-toggle-slider"></span>
                        <span class="sp-toggle-label"><?php _e('نوع الفعالية مفعّل', 'saint-porphyrius'); ?></span>
                    </label>
                    <p class="sp-form-hint"><?php _e('الأنواع غير المفعّلة لن تظهر عند إنشاء فعاليات جديدة', 'saint-porphyrius'); ?></p>
                </div>
            </div>
            
            <!-- Submit Button -->
            <div class="sp-form-actions">
                <button type="submit" class="sp-btn sp-btn-primary sp-btn-block">
                    <?php echo $edit_type ? __('💾 حفظ التغييرات', 'saint-porphyrius') : __('➕ إضافة نوع الفعالية', 'saint-porphyrius'); ?>
                </button>
                <a href="<?php echo home_url('/app/admin/event-types'); ?>" class="sp-btn sp-btn-secondary sp-btn-block">
                    <?php _e('إلغاء', 'saint-porphyrius'); ?>
                </a>
            </div>
        </form>

    <?php else: ?>
        <!-- Event Types List -->
        
        <!-- Stats Summary -->
        <div class="sp-admin-stats-mini">
            <?php 
            $active_count = count(array_filter($types, function($t) { return $t->is_active; }));
            $inactive_count = count($types) - $active_count;
            ?>
            <div class="sp-stat-mini">
                <span class="sp-stat-mini-value"><?php echo count($types); ?></span>
                <span class="sp-stat-mini-label"><?php _e('إجمالي الأنواع', 'saint-porphyrius'); ?></span>
            </div>
            <div class="sp-stat-mini active">
                <span class="sp-stat-mini-value"><?php echo $active_count; ?></span>
                <span class="sp-stat-mini-label"><?php _e('مفعّل', 'saint-porphyrius'); ?></span>
            </div>
            <div class="sp-stat-mini inactive">
                <span class="sp-stat-mini-value"><?php echo $inactive_count; ?></span>
                <span class="sp-stat-mini-label"><?php _e('غير مفعّل', 'saint-porphyrius'); ?></span>
            </div>
        </div>
        
        <?php if (empty($types)): ?>
            <div class="sp-empty-state">
                <div class="sp-empty-icon">📋</div>
                <h3><?php _e('لا توجد أنواع فعاليات', 'saint-porphyrius'); ?></h3>
                <p><?php _e('قم بإضافة نوع فعالية جديد للبدء', 'saint-porphyrius'); ?></p>
                <a href="<?php echo home_url('/app/admin/event-types?action=new'); ?>" class="sp-btn sp-btn-primary">
                    <?php _e('➕ إضافة نوع فعالية', 'saint-porphyrius'); ?>
                </a>
            </div>
        <?php else: ?>
            <div class="sp-event-types-list">
                <?php foreach ($types as $type): ?>
                    <div class="sp-event-type-card <?php echo !$type->is_active ? 'inactive' : ''; ?>">
                        <div class="sp-event-type-header" style="border-right-color: <?php echo esc_attr($type->color); ?>;">
                            <div class="sp-event-type-icon" style="background-color: <?php echo esc_attr($type->color); ?>20; color: <?php echo esc_attr($type->color); ?>;">
                                <?php echo esc_html($type->icon); ?>
                            </div>
                            <div class="sp-event-type-info">
                                <h4 class="sp-event-type-name"><?php echo esc_html($type->name_ar); ?></h4>
                                <?php if ($type->name_en): ?>
                                    <span class="sp-event-type-name-en"><?php echo esc_html($type->name_en); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!$type->is_active): ?>
                                <span class="sp-event-type-status-badge"><?php _e('غير مفعّل', 'saint-porphyrius'); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($type->description): ?>
                            <p class="sp-event-type-description"><?php echo esc_html($type->description); ?></p>
                        <?php endif; ?>
                        
                        <div class="sp-event-type-points">
                            <div class="sp-point-badge positive">
                                <span class="sp-point-icon">✅</span>
                                <span class="sp-point-value">+<?php echo esc_html($type->attendance_points); ?></span>
                                <span class="sp-point-label"><?php _e('حضور', 'saint-porphyrius'); ?></span>
                            </div>
                            <div class="sp-point-badge warning">
                                <span class="sp-point-icon">⏰</span>
                                <span class="sp-point-value">+<?php echo esc_html($type->late_points ?? floor($type->attendance_points/2)); ?></span>
                                <span class="sp-point-label"><?php _e('تأخير', 'saint-porphyrius'); ?></span>
                            </div>
                            <div class="sp-point-badge negative">
                                <span class="sp-point-icon">❌</span>
                                <span class="sp-point-value">-<?php echo esc_html($type->absence_penalty); ?></span>
                                <span class="sp-point-label"><?php _e('غياب', 'saint-porphyrius'); ?></span>
                            </div>
                        </div>
                        
                        <div class="sp-event-type-excuse-summary">
                            <span class="sp-excuse-summary-label"><?php _e('خصم الاعتذار:', 'saint-porphyrius'); ?></span>
                            <span class="sp-excuse-summary-range">
                                -<?php echo esc_html($type->excuse_points_7plus ?? 2); ?> 
                                <?php _e('إلى', 'saint-porphyrius'); ?> 
                                -<?php echo esc_html($type->excuse_points_0 ?? 10); ?>
                            </span>
                        </div>
                        
                        <div class="sp-event-type-actions">
                            <a href="<?php echo home_url('/app/admin/event-types?action=edit&type_id=' . $type->id); ?>" 
                               class="sp-btn sp-btn-sm sp-btn-outline">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                                <?php _e('تعديل', 'saint-porphyrius'); ?>
                            </a>
                            
                            <form method="post" class="sp-inline-form" 
                                  onsubmit="return confirm('<?php echo $type->is_active ? __('هل تريد إلغاء تفعيل هذا النوع؟', 'saint-porphyrius') : __('هل تريد تفعيل هذا النوع؟', 'saint-porphyrius'); ?>');">
                                <?php wp_nonce_field('sp_event_type_action'); ?>
                                <input type="hidden" name="sp_event_type_action" value="toggle_active">
                                <input type="hidden" name="type_id" value="<?php echo esc_attr($type->id); ?>">
                                <button type="submit" class="sp-btn sp-btn-sm <?php echo $type->is_active ? 'sp-btn-warning' : 'sp-btn-success'; ?>">
                                    <?php echo $type->is_active ? __('إلغاء التفعيل', 'saint-porphyrius') : __('تفعيل', 'saint-porphyrius'); ?>
                                </button>
                            </form>
                            
                            <form method="post" class="sp-inline-form" 
                                  onsubmit="return confirm('<?php _e('هل أنت متأكد من حذف هذا النوع؟ هذا الإجراء لا يمكن التراجع عنه!', 'saint-porphyrius'); ?>');">
                                <?php wp_nonce_field('sp_event_type_action'); ?>
                                <input type="hidden" name="sp_event_type_action" value="delete">
                                <input type="hidden" name="type_id" value="<?php echo esc_attr($type->id); ?>">
                                <button type="submit" class="sp-btn sp-btn-sm sp-btn-danger">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        <line x1="10" y1="11" x2="10" y2="17"></line>
                                        <line x1="14" y1="11" x2="14" y2="17"></line>
                                    </svg>
                                    <?php _e('حذف', 'saint-porphyrius'); ?>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    <?php endif; ?>
</main>

<style>
/* Event Types Admin Styles */

/* Icon Grid */
.sp-icon-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: var(--sp-space-sm);
}

.sp-icon-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: var(--sp-space-sm);
    border: 2px solid var(--sp-border);
    border-radius: var(--sp-radius-md);
    cursor: pointer;
    transition: all 0.2s ease;
}

.sp-icon-option:has(input:checked),
.sp-icon-option.selected {
    border-color: var(--sp-primary);
    background: var(--sp-primary-light);
}

.sp-icon-option input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.sp-icon-display {
    font-size: 24px;
    line-height: 1;
}

.sp-icon-label {
    font-size: 10px;
    color: var(--sp-text-secondary);
    text-align: center;
    margin-top: 4px;
}

/* Color Grid */
.sp-color-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: var(--sp-space-sm);
}

.sp-color-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: var(--sp-space-sm);
    border: 2px solid transparent;
    border-radius: var(--sp-radius-md);
    cursor: pointer;
    transition: all 0.2s ease;
}

.sp-color-option:has(input:checked),
.sp-color-option.selected {
    border-color: var(--sp-text);
}

.sp-color-option input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.sp-color-display {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.sp-color-label {
    font-size: 10px;
    color: var(--sp-text-secondary);
    margin-top: 4px;
}

.sp-color-input {
    width: 50px;
    height: 40px;
    padding: 0;
    border: 2px solid var(--sp-border);
    border-radius: var(--sp-radius-md);
    cursor: pointer;
}

/* Preview */
.sp-event-type-preview {
    display: flex;
    align-items: center;
    gap: var(--sp-space-md);
    padding: var(--sp-space-lg);
    background: var(--sp-bg-secondary);
    border-radius: var(--sp-radius-lg);
    border-right: 4px solid var(--sp-primary);
}

.sp-preview-icon {
    font-size: 32px;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--sp-bg);
    border-radius: var(--sp-radius-md);
}

.sp-preview-name {
    font-size: var(--sp-font-size-lg);
    font-weight: var(--sp-font-semibold);
}

/* Points Info Card */
.sp-points-info-card {
    display: flex;
    gap: var(--sp-space-md);
    padding: var(--sp-space-md);
    background: var(--sp-info-light);
    border-radius: var(--sp-radius-md);
    margin-bottom: var(--sp-space-lg);
}

.sp-points-info-icon {
    font-size: 20px;
}

.sp-points-info-text {
    font-size: var(--sp-font-size-sm);
    color: var(--sp-text-secondary);
    line-height: 1.5;
}

/* Points Cards Row */
.sp-points-cards-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--sp-space-md);
}

.sp-points-card {
    padding: var(--sp-space-md);
    border-radius: var(--sp-radius-lg);
    text-align: center;
}

.sp-points-card.positive {
    background: var(--sp-success-light);
}

.sp-points-card.warning {
    background: var(--sp-warning-light);
}

.sp-points-card.negative {
    background: var(--sp-danger-light);
}

.sp-points-card-header {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--sp-space-xs);
    margin-bottom: var(--sp-space-sm);
}

.sp-points-card-icon {
    font-size: 20px;
}

.sp-points-card-title {
    font-size: var(--sp-font-size-xs);
    font-weight: var(--sp-font-semibold);
    color: var(--sp-text-secondary);
}

.sp-points-input {
    text-align: center;
    font-size: var(--sp-font-size-xl);
    font-weight: var(--sp-font-bold);
    padding: var(--sp-space-sm);
}

.sp-points-card-desc {
    font-size: var(--sp-font-size-xs);
    color: var(--sp-text-muted);
    margin-top: var(--sp-space-xs);
    display: block;
}

/* Excuse Points Grid */
.sp-excuse-points-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--sp-space-md);
}

.sp-excuse-point-item {
    display: flex;
    align-items: center;
    gap: var(--sp-space-sm);
    padding: var(--sp-space-sm) var(--sp-space-md);
    background: var(--sp-bg-secondary);
    border-radius: var(--sp-radius-md);
    position: relative;
}

.sp-excuse-point-item.worst {
    background: var(--sp-danger-light);
}

.sp-excuse-point-label {
    display: flex;
    flex-direction: column;
    min-width: 50px;
}

.sp-excuse-days {
    font-size: var(--sp-font-size-lg);
    font-weight: var(--sp-font-bold);
    color: var(--sp-primary);
}

.sp-excuse-text {
    font-size: var(--sp-font-size-xs);
    color: var(--sp-text-secondary);
}

.sp-excuse-point-item input {
    width: 60px;
    text-align: center;
    font-weight: var(--sp-font-semibold);
}

.sp-excuse-badge {
    position: absolute;
    top: -8px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 9px;
    padding: 2px 6px;
    border-radius: 10px;
    font-weight: var(--sp-font-bold);
}

.sp-excuse-badge.best {
    background: var(--sp-success);
    color: white;
}

.sp-excuse-badge.worst {
    background: var(--sp-danger);
    color: white;
}

/* Toggle Switch */
.sp-toggle-switch {
    display: flex;
    align-items: center;
    gap: var(--sp-space-md);
    cursor: pointer;
}

.sp-toggle-switch input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.sp-toggle-slider {
    width: 50px;
    height: 28px;
    background: var(--sp-border);
    border-radius: 14px;
    position: relative;
    transition: background 0.3s ease;
}

.sp-toggle-slider::after {
    content: '';
    position: absolute;
    width: 22px;
    height: 22px;
    background: white;
    border-radius: 50%;
    top: 3px;
    right: 3px;
    transition: transform 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.sp-toggle-switch input:checked + .sp-toggle-slider {
    background: var(--sp-success);
}

.sp-toggle-switch input:checked + .sp-toggle-slider::after {
    transform: translateX(-22px);
}

.sp-toggle-label {
    font-weight: var(--sp-font-medium);
}

/* Stats Mini */
.sp-admin-stats-mini {
    display: flex;
    gap: var(--sp-space-md);
    padding: var(--sp-space-md) var(--sp-space-lg);
    background: var(--sp-bg-secondary);
    margin-bottom: var(--sp-space-lg);
}

.sp-stat-mini {
    flex: 1;
    text-align: center;
    padding: var(--sp-space-sm);
    background: var(--sp-bg);
    border-radius: var(--sp-radius-md);
}

.sp-stat-mini.active {
    background: var(--sp-success-light);
}

.sp-stat-mini.inactive {
    background: var(--sp-bg-secondary);
}

.sp-stat-mini-value {
    display: block;
    font-size: var(--sp-font-size-xl);
    font-weight: var(--sp-font-bold);
}

.sp-stat-mini.active .sp-stat-mini-value {
    color: var(--sp-success);
}

.sp-stat-mini-label {
    font-size: var(--sp-font-size-xs);
    color: var(--sp-text-secondary);
}

/* Event Types List */
.sp-event-types-list {
    padding: 0 var(--sp-space-lg) var(--sp-space-lg);
    display: flex;
    flex-direction: column;
    gap: var(--sp-space-md);
}

.sp-event-type-card {
    background: var(--sp-bg);
    border-radius: var(--sp-radius-lg);
    padding: var(--sp-space-lg);
    box-shadow: var(--sp-shadow-sm);
}

.sp-event-type-card.inactive {
    opacity: 0.7;
    background: var(--sp-bg-secondary);
}

.sp-event-type-header {
    display: flex;
    align-items: center;
    gap: var(--sp-space-md);
    border-right: 4px solid;
    padding-right: var(--sp-space-md);
    margin-bottom: var(--sp-space-md);
}

.sp-event-type-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--sp-radius-md);
    font-size: 24px;
}

.sp-event-type-info {
    flex: 1;
}

.sp-event-type-name {
    font-size: var(--sp-font-size-lg);
    font-weight: var(--sp-font-semibold);
    margin: 0;
}

.sp-event-type-name-en {
    font-size: var(--sp-font-size-sm);
    color: var(--sp-text-secondary);
}

.sp-event-type-status-badge {
    padding: 4px 8px;
    background: var(--sp-warning-light);
    color: var(--sp-warning);
    font-size: var(--sp-font-size-xs);
    border-radius: var(--sp-radius-sm);
    font-weight: var(--sp-font-semibold);
}

.sp-event-type-description {
    font-size: var(--sp-font-size-sm);
    color: var(--sp-text-secondary);
    margin-bottom: var(--sp-space-md);
    line-height: 1.5;
}

.sp-event-type-points {
    display: flex;
    gap: var(--sp-space-sm);
    margin-bottom: var(--sp-space-md);
}

.sp-point-badge {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: var(--sp-space-sm);
    border-radius: var(--sp-radius-md);
}

.sp-point-badge.positive {
    background: var(--sp-success-light);
}

.sp-point-badge.warning {
    background: var(--sp-warning-light);
}

.sp-point-badge.negative {
    background: var(--sp-danger-light);
}

.sp-point-icon {
    font-size: 14px;
}

.sp-point-value {
    font-size: var(--sp-font-size-lg);
    font-weight: var(--sp-font-bold);
}

.sp-point-badge.positive .sp-point-value {
    color: var(--sp-success);
}

.sp-point-badge.warning .sp-point-value {
    color: var(--sp-warning);
}

.sp-point-badge.negative .sp-point-value {
    color: var(--sp-danger);
}

.sp-point-label {
    font-size: var(--sp-font-size-xs);
    color: var(--sp-text-secondary);
}

.sp-event-type-excuse-summary {
    display: flex;
    justify-content: space-between;
    padding: var(--sp-space-sm) var(--sp-space-md);
    background: var(--sp-bg-secondary);
    border-radius: var(--sp-radius-sm);
    margin-bottom: var(--sp-space-md);
    font-size: var(--sp-font-size-sm);
}

.sp-excuse-summary-label {
    color: var(--sp-text-secondary);
}

.sp-excuse-summary-range {
    font-weight: var(--sp-font-semibold);
    color: var(--sp-text);
}

.sp-event-type-actions {
    display: flex;
    gap: var(--sp-space-sm);
    padding-top: var(--sp-space-md);
    border-top: 1px solid var(--sp-border);
}

.sp-inline-form {
    display: inline;
}

.sp-btn-sm {
    padding: var(--sp-space-xs) var(--sp-space-sm);
    font-size: var(--sp-font-size-sm);
}

.sp-btn-outline {
    background: transparent;
    border: 1px solid var(--sp-primary);
    color: var(--sp-primary);
}

.sp-btn-warning {
    background: var(--sp-warning);
    color: white;
}

.sp-btn-success {
    background: var(--sp-success);
    color: white;
}

.sp-btn-danger {
    background: var(--sp-danger);
    color: white;
}

.sp-btn-danger svg {
    width: 14px;
    height: 14px;
}

/* Form Actions */
.sp-form-actions {
    padding: var(--sp-space-lg);
    display: flex;
    flex-direction: column;
    gap: var(--sp-space-md);
}

.sp-btn-block {
    width: 100%;
}

.sp-btn-secondary {
    background: var(--sp-bg-secondary);
    color: var(--sp-text);
    border: 1px solid var(--sp-border);
}

/* Custom Color Row */
.sp-custom-color-row {
    display: flex;
    align-items: center;
    gap: var(--sp-space-md);
}

.sp-form-label-small {
    font-size: var(--sp-font-size-sm);
    color: var(--sp-text-secondary);
}

/* Responsive adjustments */
@media (max-width: 400px) {
    .sp-icon-grid {
        grid-template-columns: repeat(4, 1fr);
    }
    
    .sp-color-grid {
        grid-template-columns: repeat(4, 1fr);
    }
    
    .sp-points-cards-row {
        grid-template-columns: 1fr;
    }
    
    .sp-excuse-points-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update preview when name changes
    const nameInput = document.querySelector('input[name="name_ar"]');
    const previewName = document.getElementById('preview-name');
    const previewElement = document.getElementById('event-type-preview');
    const previewIcon = document.getElementById('preview-icon');
    
    if (nameInput && previewName) {
        nameInput.addEventListener('input', function() {
            previewName.textContent = this.value || '<?php _e('اسم النوع', 'saint-porphyrius'); ?>';
        });
    }
    
    // Update preview when icon changes
    document.querySelectorAll('input[name="icon"]').forEach(function(input) {
        input.addEventListener('change', function() {
            if (previewIcon) {
                previewIcon.textContent = this.value;
            }
            // Update selected state
            document.querySelectorAll('.sp-icon-option').forEach(function(opt) {
                opt.classList.remove('selected');
            });
            this.closest('.sp-icon-option').classList.add('selected');
        });
    });
    
    // Update preview when color changes
    document.querySelectorAll('input[name="color"]').forEach(function(input) {
        input.addEventListener('change', function() {
            if (previewElement) {
                previewElement.style.borderRightColor = this.value;
            }
            // Update selected state
            document.querySelectorAll('.sp-color-option').forEach(function(opt) {
                opt.classList.remove('selected');
            });
            this.closest('.sp-color-option').classList.add('selected');
        });
    });
});

// Custom color selection
function selectCustomColor(color) {
    const previewElement = document.getElementById('event-type-preview');
    if (previewElement) {
        previewElement.style.borderRightColor = color;
    }
    
    // Deselect all color options
    document.querySelectorAll('.sp-color-option').forEach(function(opt) {
        opt.classList.remove('selected');
        const input = opt.querySelector('input');
        if (input) input.checked = false;
    });
    
    // Create or update hidden input for custom color
    let customInput = document.querySelector('input[name="color"][type="hidden"]');
    if (!customInput) {
        customInput = document.createElement('input');
        customInput.type = 'hidden';
        customInput.name = 'color';
        document.querySelector('form').appendChild(customInput);
    }
    customInput.value = color;
}
</script>
