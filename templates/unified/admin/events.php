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
                
                <div class="sp-form-group">
                    <label class="sp-checkbox-wrapper sp-checkbox-bus">
                        <input type="checkbox" name="bus_booking_enabled" value="1" id="bus_booking_toggle" <?php checked($edit_event && isset($edit_event->bus_booking_enabled) ? $edit_event->bus_booking_enabled : false, true); ?>>
                        <span class="sp-checkbox-label">
                            <span style="margin-left: 4px;">🚌</span>
                            <?php _e('تفعيل حجز مقاعد الباص (يمكن للأعضاء اختيار مقاعدهم)', 'saint-porphyrius'); ?>
                        </span>
                    </label>
                </div>
            </div>
            
            <?php if ($edit_event && isset($edit_event->bus_booking_enabled) && $edit_event->bus_booking_enabled): 
                $bus_handler = SP_Bus::get_instance();
                $event_buses = $bus_handler->get_event_buses($edit_event->id, true);
                $bus_templates = $bus_handler->get_templates(true);
                $bus_stats = $bus_handler->get_event_bus_stats($edit_event->id);
            ?>
            <!-- Bus Management Section -->
            <div class="sp-form-section sp-bus-management-section" id="bus-management-section">
                <h3 class="sp-form-section-title">
                    🚌 <?php _e('إدارة الباصات', 'saint-porphyrius'); ?>
                    <?php if ($bus_stats && $bus_stats->total_buses > 0): ?>
                    <span class="sp-bus-stats-badge">
                        <?php printf(__('%d باص | %d/%d مقعد محجوز', 'saint-porphyrius'), 
                            $bus_stats->total_buses, 
                            $bus_stats->total_booked ?? 0, 
                            $bus_stats->total_capacity ?? 0
                        ); ?>
                    </span>
                    <?php endif; ?>
                </h3>
                
                <!-- Add Bus Form -->
                <div class="sp-add-bus-card">
                    <h4><?php _e('إضافة باص جديد', 'saint-porphyrius'); ?></h4>
                    <div class="sp-add-bus-form">
                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('نوع الباص', 'saint-porphyrius'); ?></label>
                            <select id="new_bus_template" class="sp-form-select">
                                <option value=""><?php _e('اختر نوع الباص...', 'saint-porphyrius'); ?></option>
                                <?php foreach ($bus_templates as $template): ?>
                                <option value="<?php echo esc_attr($template->id); ?>" 
                                        data-capacity="<?php echo esc_attr($template->capacity); ?>"
                                        data-icon="<?php echo esc_attr($template->icon); ?>">
                                    <?php echo esc_html($template->icon . ' ' . $template->name_ar . ' (' . $template->capacity . ' راكب)'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="sp-form-row">
                            <div class="sp-form-group sp-form-group-half">
                                <label class="sp-form-label"><?php _e('وقت الانطلاق', 'saint-porphyrius'); ?></label>
                                <input type="time" id="new_bus_departure_time" class="sp-form-input">
                            </div>
                            <div class="sp-form-group sp-form-group-half">
                                <label class="sp-form-label"><?php _e('وقت العودة', 'saint-porphyrius'); ?></label>
                                <input type="time" id="new_bus_return_time" class="sp-form-input">
                            </div>
                        </div>
                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('مكان التجمع', 'saint-porphyrius'); ?></label>
                            <input type="text" id="new_bus_departure_location" class="sp-form-input" 
                                   placeholder="<?php _e('مثال: أمام الكنيسة', 'saint-porphyrius'); ?>">
                        </div>
                        <button type="button" id="add-bus-btn" class="sp-btn sp-btn-primary sp-btn-block" data-event-id="<?php echo esc_attr($edit_event->id); ?>">
                            <span class="sp-btn-icon">+</span>
                            <?php _e('إضافة الباص', 'saint-porphyrius'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Existing Buses List -->
                <div class="sp-event-buses-list" id="event-buses-list">
                    <?php if (empty($event_buses)): ?>
                    <div class="sp-empty-buses" id="empty-buses-message">
                        <div class="sp-empty-icon">🚌</div>
                        <p><?php _e('لم تتم إضافة أي باصات بعد', 'saint-porphyrius'); ?></p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($event_buses as $bus): ?>
                        <div class="sp-event-bus-card" data-bus-id="<?php echo esc_attr($bus->id); ?>">
                            <div class="sp-bus-card-header">
                                <div class="sp-bus-icon" style="background-color: <?php echo esc_attr($bus->color); ?>20; color: <?php echo esc_attr($bus->color); ?>;">
                                    <?php echo esc_html($bus->icon); ?>
                                </div>
                                <div class="sp-bus-info">
                                    <h4><?php printf(__('باص %d', 'saint-porphyrius'), $bus->bus_number); ?> - <?php echo esc_html($bus->template_name_ar); ?></h4>
                                    <div class="sp-bus-meta">
                                        <?php if ($bus->departure_time): ?>
                                        <span>🕐 <?php echo esc_html($bus->departure_time); ?></span>
                                        <?php endif; ?>
                                        <?php if ($bus->departure_location): ?>
                                        <span>📍 <?php echo esc_html($bus->departure_location); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="sp-bus-capacity">
                                    <span class="sp-capacity-badge <?php echo $bus->available_seats == 0 ? 'full' : ''; ?>">
                                        <?php printf(__('%d/%d', 'saint-porphyrius'), count($bus->bookings), $bus->capacity); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="sp-bus-card-actions">
                                <a href="<?php echo home_url('/app/admin/bus-bookings?bus_id=' . $bus->id); ?>" class="sp-btn sp-btn-sm sp-btn-outline">
                                    👥 <?php _e('الحجوزات', 'saint-porphyrius'); ?>
                                </a>
                                <button type="button" class="sp-btn sp-btn-sm sp-btn-danger remove-bus-btn" data-bus-id="<?php echo esc_attr($bus->id); ?>">
                                    🗑️ <?php _e('حذف', 'saint-porphyrius'); ?>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="sp-form-actions">
                <button type="submit" class="sp-btn sp-btn-primary sp-btn-block">
                    <?php echo $edit_event ? __('حفظ التغييرات', 'saint-porphyrius') : __('إنشاء الفعالية', 'saint-porphyrius'); ?>
                </button>
                <a href="<?php echo home_url('/app/admin/events'); ?>" class="sp-btn sp-btn-outline sp-btn-block">
                    <?php _e('إلغاء', 'saint-porphyrius'); ?>
                </a>
            </div>
        </form>
        
        <style>
        /* Bus Management Styles */
        .sp-checkbox-bus .sp-checkbox-label {
            color: #3B82F6;
        }
        .sp-bus-management-section {
            background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
            border-radius: var(--sp-radius-lg);
            padding: var(--sp-space-lg);
            margin-top: var(--sp-space-lg);
        }
        .sp-bus-stats-badge {
            font-size: var(--sp-font-size-xs);
            background: var(--sp-primary);
            color: white;
            padding: 4px 8px;
            border-radius: var(--sp-radius-sm);
            margin-right: 8px;
            font-weight: normal;
        }
        .sp-add-bus-card {
            background: white;
            border-radius: var(--sp-radius-md);
            padding: var(--sp-space-lg);
            margin-bottom: var(--sp-space-lg);
        }
        .sp-add-bus-card h4 {
            margin: 0 0 var(--sp-space-md);
            font-size: var(--sp-font-size-md);
        }
        .sp-event-buses-list {
            display: flex;
            flex-direction: column;
            gap: var(--sp-space-md);
        }
        .sp-event-bus-card {
            background: white;
            border-radius: var(--sp-radius-md);
            padding: var(--sp-space-md);
            border-right: 4px solid var(--sp-primary);
        }
        .sp-bus-card-header {
            display: flex;
            align-items: center;
            gap: var(--sp-space-md);
            margin-bottom: var(--sp-space-sm);
        }
        .sp-bus-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--sp-radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .sp-bus-info {
            flex: 1;
        }
        .sp-bus-info h4 {
            margin: 0;
            font-size: var(--sp-font-size-md);
        }
        .sp-bus-meta {
            display: flex;
            gap: var(--sp-space-md);
            font-size: var(--sp-font-size-sm);
            color: var(--sp-text-secondary);
            margin-top: 4px;
        }
        .sp-capacity-badge {
            background: var(--sp-success-light);
            color: var(--sp-success);
            padding: 4px 12px;
            border-radius: var(--sp-radius-sm);
            font-weight: var(--sp-font-semibold);
        }
        .sp-capacity-badge.full {
            background: var(--sp-danger-light);
            color: var(--sp-danger);
        }
        .sp-bus-card-actions {
            display: flex;
            gap: var(--sp-space-sm);
            justify-content: flex-end;
        }
        .sp-empty-buses {
            text-align: center;
            padding: var(--sp-space-xl);
            background: white;
            border-radius: var(--sp-radius-md);
        }
        .sp-empty-buses .sp-empty-icon {
            font-size: 48px;
            margin-bottom: var(--sp-space-md);
        }
        </style>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add Bus Button Handler
            const addBusBtn = document.getElementById('add-bus-btn');
            if (addBusBtn) {
                addBusBtn.addEventListener('click', function() {
                    const eventId = this.dataset.eventId;
                    const templateId = document.getElementById('new_bus_template').value;
                    const departureTime = document.getElementById('new_bus_departure_time').value;
                    const returnTime = document.getElementById('new_bus_return_time').value;
                    const departureLocation = document.getElementById('new_bus_departure_location').value;
                    
                    if (!templateId) {
                        alert('<?php _e('الرجاء اختيار نوع الباص', 'saint-porphyrius'); ?>');
                        return;
                    }
                    
                    addBusBtn.disabled = true;
                    addBusBtn.innerHTML = '<?php _e('جاري الإضافة...', 'saint-porphyrius'); ?>';
                    
                    const formData = new FormData();
                    formData.append('action', 'sp_add_event_bus');
                    formData.append('nonce', '<?php echo wp_create_nonce('sp_nonce'); ?>');
                    formData.append('event_id', eventId);
                    formData.append('bus_template_id', templateId);
                    formData.append('departure_time', departureTime);
                    formData.append('return_time', returnTime);
                    formData.append('departure_location', departureLocation);
                    
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        addBusBtn.disabled = false;
                        addBusBtn.innerHTML = '<span class="sp-btn-icon">+</span> <?php _e('إضافة الباص', 'saint-porphyrius'); ?>';
                        
                        if (data.success) {
                            // Reload page to show new bus
                            location.reload();
                        } else {
                            alert(data.data.message || '<?php _e('حدث خطأ', 'saint-porphyrius'); ?>');
                        }
                    })
                    .catch(error => {
                        addBusBtn.disabled = false;
                        addBusBtn.innerHTML = '<span class="sp-btn-icon">+</span> <?php _e('إضافة الباص', 'saint-porphyrius'); ?>';
                        alert('<?php _e('حدث خطأ في الاتصال', 'saint-porphyrius'); ?>');
                    });
                });
            }
            
            // Remove Bus Button Handlers
            document.querySelectorAll('.remove-bus-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    if (!confirm('<?php _e('هل أنت متأكد من حذف هذا الباص؟ سيتم إلغاء جميع الحجوزات.', 'saint-porphyrius'); ?>')) {
                        return;
                    }
                    
                    const busId = this.dataset.busId;
                    const card = this.closest('.sp-event-bus-card');
                    
                    const formData = new FormData();
                    formData.append('action', 'sp_remove_event_bus');
                    formData.append('nonce', '<?php echo wp_create_nonce('sp_nonce'); ?>');
                    formData.append('event_bus_id', busId);
                    
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            card.remove();
                            // Check if no buses left
                            if (document.querySelectorAll('.sp-event-bus-card').length === 0) {
                                document.getElementById('event-buses-list').innerHTML = `
                                    <div class="sp-empty-buses" id="empty-buses-message">
                                        <div class="sp-empty-icon">🚌</div>
                                        <p><?php _e('لم تتم إضافة أي باصات بعد', 'saint-porphyrius'); ?></p>
                                    </div>
                                `;
                            }
                        } else {
                            alert(data.data.message || '<?php _e('حدث خطأ', 'saint-porphyrius'); ?>');
                        }
                    });
                });
            });
        });
        </script>
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
