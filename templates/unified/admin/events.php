<?php
/**
 * Saint Porphyrius - Admin Events (Mobile)
 * Create, edit, and manage events
 */

if (!defined('ABSPATH')) {
    exit;
}

$events_handler = SP_Events::get_instance();
$event_types = SP_Event_Types::get_instance();
$types = $event_types->get_all();

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sp_event_action'])) {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'sp_event_action')) {
        $message = __('خطأ في التحقق', 'saint-porphyrius');
        $message_type = 'error';
    } else {
        $action = sanitize_text_field($_POST['sp_event_action']);
        
        if ($action === 'create') {
            $result = $events_handler->create($_POST);
            if (is_wp_error($result)) {
                $message = $result->get_error_message();
                $message_type = 'error';
            } else {
                $message = __('تم إنشاء الفعالية بنجاح', 'saint-porphyrius');
                $message_type = 'success';
            }
        } elseif ($action === 'update' && !empty($_POST['event_id'])) {
            $result = $events_handler->update(absint($_POST['event_id']), $_POST);
            if (is_wp_error($result)) {
                $message = $result->get_error_message();
                $message_type = 'error';
            } else {
                $message = __('تم تحديث الفعالية بنجاح', 'saint-porphyrius');
                $message_type = 'success';
            }
        } elseif ($action === 'delete' && !empty($_POST['event_id'])) {
            $result = $events_handler->delete(absint($_POST['event_id']));
            if (is_wp_error($result)) {
                $message = $result->get_error_message();
                $message_type = 'error';
            } else {
                $message = __('تم حذف الفعالية', 'saint-porphyrius');
                $message_type = 'success';
            }
        } elseif ($action === 'complete' && !empty($_POST['event_id'])) {
            $result = $events_handler->complete_event(absint($_POST['event_id']));
            if (is_wp_error($result)) {
                $message = $result->get_error_message();
                $message_type = 'error';
            } else {
                $message = __('تم إكمال الفعالية ومعالجة النقاط', 'saint-porphyrius');
                $message_type = 'success';
            }
        }
    }
}

// Get events
$events = $events_handler->get_all(array('limit' => 50));
$show_form = isset($_GET['action']) && $_GET['action'] === 'new';
$edit_event = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['event_id'])) {
    $edit_event = $events_handler->get(absint($_GET['event_id']));
}

$status_labels = array(
    'draft' => __('مسودة', 'saint-porphyrius'),
    'published' => __('منشور', 'saint-porphyrius'),
    'completed' => __('مكتمل', 'saint-porphyrius'),
    'cancelled' => __('ملغي', 'saint-porphyrius'),
);
?>

<!-- Admin Header -->
<div class="sp-unified-header sp-admin-header">
    <div class="sp-header-inner">
        <a href="<?php echo ($show_form || $edit_event) ? home_url('/app/admin/events') : home_url('/app/admin'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title">
            <?php 
            if ($edit_event) {
                _e('تعديل الفعالية', 'saint-porphyrius');
            } elseif ($show_form) {
                _e('فعالية جديدة', 'saint-porphyrius');
            } else {
                _e('الفعاليات', 'saint-porphyrius');
            }
            ?>
        </h1>
        <?php if (!$show_form && !$edit_event): ?>
        <a href="<?php echo home_url('/app/admin/events?action=new'); ?>" class="sp-header-action">
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

    <?php if ($show_form || $edit_event): ?>
        <!-- Event Form -->
        <form method="post" class="sp-admin-form">
            <?php wp_nonce_field('sp_event_action'); ?>
            <input type="hidden" name="sp_event_action" value="<?php echo $edit_event ? 'update' : 'create'; ?>">
            <?php if ($edit_event): ?>
                <input type="hidden" name="event_id" value="<?php echo esc_attr($edit_event->id); ?>">
            <?php endif; ?>
            
            <div class="sp-form-section">
                <h3 class="sp-form-section-title"><?php _e('تفاصيل الفعالية', 'saint-porphyrius'); ?></h3>
                
                <div class="sp-form-group">
                    <label class="sp-form-label"><?php _e('نوع الفعالية', 'saint-porphyrius'); ?> <span class="required">*</span></label>
                    <select name="event_type_id" required class="sp-form-select">
                        <option value=""><?php _e('اختر النوع...', 'saint-porphyrius'); ?></option>
                        <?php foreach ($types as $type): ?>
                            <option value="<?php echo esc_attr($type->id); ?>" <?php selected($edit_event ? $edit_event->event_type_id : '', $type->id); ?>>
                                <?php echo esc_html($type->icon . ' ' . $type->name_ar); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="sp-form-group">
                    <label class="sp-form-label"><?php _e('العنوان (عربي)', 'saint-porphyrius'); ?> <span class="required">*</span></label>
                    <input type="text" name="title_ar" required class="sp-form-input" 
                           value="<?php echo $edit_event ? esc_attr($edit_event->title_ar) : ''; ?>">
                </div>
                
                <div class="sp-form-group">
                    <label class="sp-form-label"><?php _e('العنوان (إنجليزي)', 'saint-porphyrius'); ?></label>
                    <input type="text" name="title_en" class="sp-form-input"
                           value="<?php echo $edit_event ? esc_attr($edit_event->title_en) : ''; ?>">
                </div>
                
                <div class="sp-form-group">
                    <label class="sp-form-label"><?php _e('الوصف', 'saint-porphyrius'); ?></label>
                    <textarea name="description" class="sp-form-textarea" rows="3"><?php echo $edit_event ? esc_textarea($edit_event->description) : ''; ?></textarea>
                </div>
            </div>
            
            <div class="sp-form-section">
                <h3 class="sp-form-section-title"><?php _e('التاريخ والوقت', 'saint-porphyrius'); ?></h3>
                
                <div class="sp-form-group">
                    <label class="sp-form-label"><?php _e('تاريخ الفعالية', 'saint-porphyrius'); ?> <span class="required">*</span></label>
                    <input type="date" name="event_date" required class="sp-form-input"
                           value="<?php echo $edit_event ? esc_attr($edit_event->event_date) : ''; ?>">
                </div>
                
                <div class="sp-form-row">
                    <div class="sp-form-group sp-form-group-half">
                        <label class="sp-form-label"><?php _e('وقت البدء', 'saint-porphyrius'); ?> <span class="required">*</span></label>
                        <input type="time" name="start_time" required class="sp-form-input"
                               value="<?php echo $edit_event ? esc_attr($edit_event->start_time) : ''; ?>">
                    </div>
                    <div class="sp-form-group sp-form-group-half">
                        <label class="sp-form-label"><?php _e('وقت الانتهاء', 'saint-porphyrius'); ?></label>
                        <input type="time" name="end_time" class="sp-form-input"
                               value="<?php echo $edit_event ? esc_attr($edit_event->end_time) : ''; ?>">
                    </div>
                </div>
            </div>
            
            <div class="sp-form-section">
                <h3 class="sp-form-section-title"><?php _e('المكان', 'saint-porphyrius'); ?></h3>
                
                <div class="sp-form-group">
                    <label class="sp-form-label"><?php _e('اسم المكان', 'saint-porphyrius'); ?></label>
                    <input type="text" name="location_name" class="sp-form-input"
                           value="<?php echo $edit_event ? esc_attr($edit_event->location_name) : ''; ?>"
                           placeholder="<?php _e('مثال: كنيسة القديس بورفيريوس', 'saint-porphyrius'); ?>">
                </div>
                
                <div class="sp-form-group">
                    <label class="sp-form-label"><?php _e('العنوان', 'saint-porphyrius'); ?></label>
                    <textarea name="location_address" class="sp-form-textarea" rows="2"><?php echo $edit_event ? esc_textarea($edit_event->location_address) : ''; ?></textarea>
                </div>
                
                <div class="sp-form-group">
                    <label class="sp-form-label"><?php _e('رابط خرائط جوجل', 'saint-porphyrius'); ?></label>
                    <input type="url" name="location_map_url" class="sp-form-input"
                           value="<?php echo $edit_event ? esc_attr($edit_event->location_map_url ?? '') : ''; ?>"
                           placeholder="https://maps.google.com/...">
                </div>
            </div>
            
            <div class="sp-form-section">
                <h3 class="sp-form-section-title"><?php _e('الإعدادات', 'saint-porphyrius'); ?></h3>
                
                <div class="sp-form-group">
                    <label class="sp-form-label"><?php _e('الحالة', 'saint-porphyrius'); ?></label>
                    <select name="status" class="sp-form-select">
                        <option value="draft" <?php selected($edit_event ? $edit_event->status : '', 'draft'); ?>><?php _e('مسودة', 'saint-porphyrius'); ?></option>
                        <option value="published" <?php selected($edit_event ? $edit_event->status : '', 'published'); ?>><?php _e('منشور', 'saint-porphyrius'); ?></option>
                        <?php if ($edit_event): ?>
                        <option value="completed" <?php selected($edit_event->status, 'completed'); ?>><?php _e('مكتمل', 'saint-porphyrius'); ?></option>
                        <option value="cancelled" <?php selected($edit_event->status, 'cancelled'); ?>><?php _e('ملغي', 'saint-porphyrius'); ?></option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="sp-form-group">
                    <label class="sp-checkbox-wrapper">
                        <input type="checkbox" name="is_mandatory" value="1" <?php checked($edit_event ? $edit_event->is_mandatory : false, true); ?>>
                        <span class="sp-checkbox-label"><?php _e('حضور إلزامي (يتم تطبيق خصم النقاط عند الغياب)', 'saint-porphyrius'); ?></span>
                    </label>
                </div>
                
                <div class="sp-form-group">
                    <label class="sp-checkbox-wrapper sp-checkbox-forbidden">
                        <input type="checkbox" name="forbidden_enabled" value="1" <?php checked($edit_event && isset($edit_event->forbidden_enabled) ? $edit_event->forbidden_enabled : false, true); ?>>
                        <span class="sp-checkbox-label">
                            <span class="sp-forbidden-label-icon">⛔</span>
                            <?php _e('تفعيل نظام المحروم (الغياب بدون عذر يؤدي للحرمان من الفعاليات القادمة)', 'saint-porphyrius'); ?>
                        </span>
                    </label>
                </div>
                
                <div class="sp-form-group">
                    <label class="sp-checkbox-wrapper">
                        <input type="checkbox" name="expected_attendance_enabled" value="1" <?php checked(!$edit_event || (isset($edit_event->expected_attendance_enabled) ? $edit_event->expected_attendance_enabled : true), true); ?>>
                        <span class="sp-checkbox-label">
                            <span style="margin-left: 4px;">🙋</span>
                            <?php _e('تفعيل قائمة الحضور المتوقع (يمكن للأعضاء تسجيل نيتهم للحضور)', 'saint-porphyrius'); ?>
                        </span>
                    </label>
                </div>
            </div>
            
            <div class="sp-form-actions">
                <button type="submit" class="sp-btn sp-btn-primary sp-btn-block">
                    <?php echo $edit_event ? __('حفظ التغييرات', 'saint-porphyrius') : __('إنشاء الفعالية', 'saint-porphyrius'); ?>
                </button>
                <a href="<?php echo home_url('/app/admin/events'); ?>" class="sp-btn sp-btn-outline sp-btn-block">
                    <?php _e('إلغاء', 'saint-porphyrius'); ?>
                </a>
            </div>
        </form>
    <?php else: ?>
        <!-- Events List -->
        <?php if (empty($events)): ?>
            <div class="sp-empty-state">
                <div class="sp-empty-icon">📅</div>
                <h3><?php _e('لا توجد فعاليات', 'saint-porphyrius'); ?></h3>
                <p><?php _e('أنشئ فعالية جديدة للبدء', 'saint-porphyrius'); ?></p>
                <a href="<?php echo home_url('/app/admin/events?action=new'); ?>" class="sp-btn sp-btn-primary">
                    <?php _e('إنشاء فعالية', 'saint-porphyrius'); ?>
                </a>
            </div>
        <?php else: ?>
            <div class="sp-events-admin-list">
                <?php foreach ($events as $event): ?>
                    <div class="sp-event-admin-card">
                        <div class="sp-event-admin-header">
                            <div class="sp-event-admin-date">
                                <span class="day"><?php echo esc_html(date_i18n('j', strtotime($event->event_date))); ?></span>
                                <span class="month"><?php echo esc_html(date_i18n('M', strtotime($event->event_date))); ?></span>
                            </div>
                            <div class="sp-event-admin-info">
                                <div class="sp-event-admin-type" style="color: <?php echo esc_attr($event->type_color); ?>;">
                                    <?php echo esc_html($event->type_icon . ' ' . $event->type_name_ar); ?>
                                </div>
                                <h4><?php echo esc_html($event->title_ar); ?></h4>
                                <div class="sp-event-admin-meta">
                                    <span><?php echo esc_html($event->start_time); ?></span>
                                    <?php if ($event->location_name): ?>
                                    <span>• <?php echo esc_html($event->location_name); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="sp-event-admin-status">
                                <span class="sp-status-badge sp-status-<?php echo esc_attr($event->status); ?>">
                                    <?php echo esc_html($status_labels[$event->status] ?? $event->status); ?>
                                </span>
                                <?php if ($event->is_mandatory): ?>
                                <span class="sp-mandatory-badge"><?php _e('إلزامي', 'saint-porphyrius'); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($event->forbidden_enabled)): ?>
                                <span class="sp-forbidden-badge">⛔ <?php _e('محروم', 'saint-porphyrius'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="sp-event-admin-actions">
                            <a href="<?php echo home_url('/app/admin/attendance?event_id=' . $event->id); ?>" class="sp-btn sp-btn-sm sp-btn-primary">
                                ✓ <?php _e('الحضور', 'saint-porphyrius'); ?>
                            </a>
                            <a href="<?php echo home_url('/app/admin/events?action=edit&event_id=' . $event->id); ?>" class="sp-btn sp-btn-sm sp-btn-outline">
                                ✏️ <?php _e('تعديل', 'saint-porphyrius'); ?>
                            </a>
                            <?php if ($event->status === 'published'): ?>
                            <form method="post" style="display:inline;" onsubmit="return confirm('<?php _e('هل تريد إكمال الفعالية ومعالجة نقاط الحضور؟', 'saint-porphyrius'); ?>');">
                                <?php wp_nonce_field('sp_event_action'); ?>
                                <input type="hidden" name="sp_event_action" value="complete">
                                <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">
                                <button type="submit" class="sp-btn sp-btn-sm sp-btn-success">
                                    ✅ <?php _e('إكمال', 'saint-porphyrius'); ?>
                                </button>
                            </form>
                            <?php endif; ?>
                            <form method="post" style="display:inline;" onsubmit="return confirm('<?php _e('هل أنت متأكد من حذف هذه الفعالية؟', 'saint-porphyrius'); ?>');">
                                <?php wp_nonce_field('sp_event_action'); ?>
                                <input type="hidden" name="sp_event_action" value="delete">
                                <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">
                                <button type="submit" class="sp-btn sp-btn-sm sp-btn-danger">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>
