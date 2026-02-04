<?php
/**
 * Saint Porphyrius - Admin Bus Templates Management
 * Manage bus configurations (types, capacities, layouts)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check admin permissions
if (!current_user_can('sp_manage_members') && !current_user_can('manage_options')) {
    wp_safe_redirect(home_url('/app'));
    exit;
}

$bus_handler = SP_Bus::get_instance();
$templates = $bus_handler->get_templates();

// Handle form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sp_bus_template_nonce'])) {
    if (!wp_verify_nonce($_POST['sp_bus_template_nonce'], 'sp_bus_template_action')) {
        $message = __('فشل التحقق الأمني', 'saint-porphyrius');
        $message_type = 'error';
    } else {
        $action = isset($_POST['template_action']) ? $_POST['template_action'] : '';
        
        if ($action === 'create') {
            $result = $bus_handler->create_template(array(
                'name_ar' => sanitize_text_field($_POST['name_ar']),
                'name_en' => sanitize_text_field($_POST['name_en'] ?? ''),
                'capacity' => absint($_POST['capacity']),
                'rows' => absint($_POST['rows']),
                'seats_per_row' => absint($_POST['seats_per_row']),
                'aisle_position' => absint($_POST['aisle_position']),
                'icon' => sanitize_text_field($_POST['icon']),
                'color' => sanitize_hex_color($_POST['color']),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
            ));
            
            if (is_wp_error($result)) {
                $message = $result->get_error_message();
                $message_type = 'error';
            } else {
                $message = __('تم إنشاء نوع الباص بنجاح', 'saint-porphyrius');
                $message_type = 'success';
                $templates = $bus_handler->get_templates(); // Refresh
            }
        } elseif ($action === 'update' && isset($_POST['template_id'])) {
            $result = $bus_handler->update_template(absint($_POST['template_id']), array(
                'name_ar' => sanitize_text_field($_POST['name_ar']),
                'name_en' => sanitize_text_field($_POST['name_en'] ?? ''),
                'capacity' => absint($_POST['capacity']),
                'rows' => absint($_POST['rows']),
                'seats_per_row' => absint($_POST['seats_per_row']),
                'aisle_position' => absint($_POST['aisle_position']),
                'icon' => sanitize_text_field($_POST['icon']),
                'color' => sanitize_hex_color($_POST['color']),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
            ));
            
            if (is_wp_error($result)) {
                $message = $result->get_error_message();
                $message_type = 'error';
            } else {
                $message = __('تم تحديث نوع الباص بنجاح', 'saint-porphyrius');
                $message_type = 'success';
                $templates = $bus_handler->get_templates(); // Refresh
            }
        } elseif ($action === 'delete' && isset($_POST['template_id'])) {
            $result = $bus_handler->delete_template(absint($_POST['template_id']));
            
            if (is_wp_error($result)) {
                $message = $result->get_error_message();
                $message_type = 'error';
            } else {
                $message = __('تم حذف نوع الباص بنجاح', 'saint-porphyrius');
                $message_type = 'success';
                $templates = $bus_handler->get_templates(); // Refresh
            }
        }
    }
}

$edit_template = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_template = $bus_handler->get_template(absint($_GET['id']));
}

// Bus icons
$bus_icons = array('🚌', '🚐', '🚍', '🚎', '🚃', '🚈', '🚄', '🚅', '🏎️', '🚗');

// Colors
$bus_colors = array(
    '#3B82F6' => __('أزرق', 'saint-porphyrius'),
    '#10B981' => __('أخضر', 'saint-porphyrius'),
    '#F59E0B' => __('برتقالي', 'saint-porphyrius'),
    '#EF4444' => __('أحمر', 'saint-porphyrius'),
    '#8B5CF6' => __('بنفسجي', 'saint-porphyrius'),
    '#EC4899' => __('وردي', 'saint-porphyrius'),
    '#06B6D4' => __('سماوي', 'saint-porphyrius'),
    '#6366F1' => __('نيلي', 'saint-porphyrius'),
);
?>

<!-- Header -->
<div class="sp-unified-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/admin/events'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('أنواع الباصات', 'saint-porphyrius'); ?></h1>
        <?php if (!$edit_template): ?>
        <a href="<?php echo home_url('/app/admin/bus-templates?action=new'); ?>" class="sp-header-action">
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
<main class="sp-page-content has-bottom-nav">
    <?php if ($message): ?>
    <div class="sp-alert sp-alert-<?php echo esc_attr($message_type); ?>">
        <?php echo esc_html($message); ?>
    </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['action']) && in_array($_GET['action'], array('new', 'edit'))): ?>
    <!-- Create/Edit Form -->
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title">
                <?php echo $edit_template ? __('تعديل نوع الباص', 'saint-porphyrius') : __('إضافة نوع باص جديد', 'saint-porphyrius'); ?>
            </h3>
        </div>
        
        <form method="post" class="sp-card sp-form-card">
            <?php wp_nonce_field('sp_bus_template_action', 'sp_bus_template_nonce'); ?>
            <input type="hidden" name="template_action" value="<?php echo $edit_template ? 'update' : 'create'; ?>">
            <?php if ($edit_template): ?>
            <input type="hidden" name="template_id" value="<?php echo esc_attr($edit_template->id); ?>">
            <?php endif; ?>
            
            <div class="sp-form-group">
                <label class="sp-form-label"><?php _e('الاسم بالعربية', 'saint-porphyrius'); ?> <span class="required">*</span></label>
                <input type="text" name="name_ar" class="sp-form-input" required
                       value="<?php echo $edit_template ? esc_attr($edit_template->name_ar) : ''; ?>"
                       placeholder="<?php _e('مثال: باص صغير', 'saint-porphyrius'); ?>">
            </div>
            
            <div class="sp-form-group">
                <label class="sp-form-label"><?php _e('الاسم بالإنجليزية', 'saint-porphyrius'); ?></label>
                <input type="text" name="name_en" class="sp-form-input"
                       value="<?php echo $edit_template ? esc_attr($edit_template->name_en) : ''; ?>"
                       placeholder="<?php _e('مثال: Small Bus', 'saint-porphyrius'); ?>">
            </div>
            
            <div class="sp-form-row">
                <div class="sp-form-group sp-form-group-half">
                    <label class="sp-form-label"><?php _e('عدد الصفوف', 'saint-porphyrius'); ?> <span class="required">*</span></label>
                    <input type="number" name="rows" class="sp-form-input" required min="2" max="25"
                           value="<?php echo $edit_template ? esc_attr($edit_template->rows) : '10'; ?>">
                </div>
                <div class="sp-form-group sp-form-group-half">
                    <label class="sp-form-label"><?php _e('مقاعد في كل صف', 'saint-porphyrius'); ?> <span class="required">*</span></label>
                    <input type="number" name="seats_per_row" class="sp-form-input" required min="2" max="6"
                           value="<?php echo $edit_template ? esc_attr($edit_template->seats_per_row) : '4'; ?>">
                </div>
            </div>
            
            <div class="sp-form-row">
                <div class="sp-form-group sp-form-group-half">
                    <label class="sp-form-label"><?php _e('موقع الممر', 'saint-porphyrius'); ?></label>
                    <select name="aisle_position" class="sp-form-select">
                        <option value="2" <?php selected($edit_template ? $edit_template->aisle_position : 2, 2); ?>><?php _e('بعد المقعد الثاني', 'saint-porphyrius'); ?></option>
                        <option value="3" <?php selected($edit_template ? $edit_template->aisle_position : 2, 3); ?>><?php _e('بعد المقعد الثالث', 'saint-porphyrius'); ?></option>
                    </select>
                </div>
                <div class="sp-form-group sp-form-group-half">
                    <label class="sp-form-label"><?php _e('السعة الإجمالية', 'saint-porphyrius'); ?> <span class="required">*</span></label>
                    <input type="number" name="capacity" class="sp-form-input" required min="4" max="100"
                           value="<?php echo $edit_template ? esc_attr($edit_template->capacity) : '40'; ?>">
                </div>
            </div>
            
            <div class="sp-form-group">
                <label class="sp-form-label"><?php _e('الأيقونة', 'saint-porphyrius'); ?></label>
                <div class="sp-icon-grid">
                    <?php foreach ($bus_icons as $icon): ?>
                    <label class="sp-icon-option">
                        <input type="radio" name="icon" value="<?php echo esc_attr($icon); ?>" 
                               <?php checked($edit_template ? $edit_template->icon : '🚌', $icon); ?>>
                        <span class="sp-icon-display"><?php echo esc_html($icon); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="sp-form-group">
                <label class="sp-form-label"><?php _e('اللون', 'saint-porphyrius'); ?></label>
                <div class="sp-color-grid">
                    <?php foreach ($bus_colors as $hex => $name): ?>
                    <label class="sp-color-option">
                        <input type="radio" name="color" value="<?php echo esc_attr($hex); ?>" 
                               <?php checked($edit_template ? $edit_template->color : '#3B82F6', $hex); ?>>
                        <span class="sp-color-display" style="background: <?php echo esc_attr($hex); ?>;"></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="sp-form-group">
                <label class="sp-checkbox-wrapper">
                    <input type="checkbox" name="is_active" value="1" 
                           <?php checked(!$edit_template || $edit_template->is_active, true); ?>>
                    <span class="sp-checkbox-label"><?php _e('فعّال (يظهر عند إضافة باص للفعالية)', 'saint-porphyrius'); ?></span>
                </label>
            </div>
            
            <!-- Live Preview -->
            <div class="sp-form-group">
                <label class="sp-form-label"><?php _e('معاينة التخطيط', 'saint-porphyrius'); ?></label>
                <div class="sp-bus-preview" id="bus-preview">
                    <!-- Will be populated by JS -->
                </div>
            </div>
            
            <div class="sp-form-actions">
                <button type="submit" class="sp-btn sp-btn-primary sp-btn-block">
                    <?php echo $edit_template ? __('حفظ التغييرات', 'saint-porphyrius') : __('إنشاء نوع الباص', 'saint-porphyrius'); ?>
                </button>
                <a href="<?php echo home_url('/app/admin/bus-templates'); ?>" class="sp-btn sp-btn-outline sp-btn-block">
                    <?php _e('إلغاء', 'saint-porphyrius'); ?>
                </a>
            </div>
        </form>
    </div>
    <?php else: ?>
    <!-- Templates List -->
    <div class="sp-section">
        <?php if (empty($templates)): ?>
        <div class="sp-card sp-empty-state">
            <div class="sp-empty-icon">🚌</div>
            <h3><?php _e('لا توجد أنواع باصات', 'saint-porphyrius'); ?></h3>
            <p><?php _e('أضف أنواع باصات مختلفة لاستخدامها في الفعاليات', 'saint-porphyrius'); ?></p>
            <a href="<?php echo home_url('/app/admin/bus-templates?action=new'); ?>" class="sp-btn sp-btn-primary">
                <?php _e('إضافة نوع باص', 'saint-porphyrius'); ?>
            </a>
        </div>
        <?php else: ?>
        <div class="sp-bus-templates-grid">
            <?php foreach ($templates as $template): ?>
            <div class="sp-bus-template-card <?php echo !$template->is_active ? 'inactive' : ''; ?>">
                <div class="sp-template-header" style="background: <?php echo esc_attr($template->color); ?>20;">
                    <div class="sp-template-icon" style="background: <?php echo esc_attr($template->color); ?>;">
                        <?php echo esc_html($template->icon); ?>
                    </div>
                    <div class="sp-template-info">
                        <h3><?php echo esc_html($template->name_ar); ?></h3>
                        <div class="sp-template-meta">
                            <span><?php printf(__('%d راكب', 'saint-porphyrius'), $template->capacity); ?></span>
                            <span>•</span>
                            <span><?php printf(__('%d صف', 'saint-porphyrius'), $template->rows); ?></span>
                        </div>
                    </div>
                    <?php if (!$template->is_active): ?>
                    <span class="sp-badge sp-badge-inactive"><?php _e('معطل', 'saint-porphyrius'); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="sp-template-preview">
                    <div class="sp-mini-bus">
                        <div class="sp-mini-driver">👨‍✈️</div>
                        <div class="sp-mini-seats">
                            <?php 
                            $preview_rows = min(4, $template->rows);
                            for ($r = 1; $r <= $preview_rows; $r++):
                                for ($s = 1; $s <= $template->seats_per_row; $s++):
                                    if ($s == $template->aisle_position): ?>
                                        <span class="sp-mini-aisle"></span>
                                    <?php endif; ?>
                                    <span class="sp-mini-seat"></span>
                            <?php endfor;
                            endfor; ?>
                            <?php if ($template->rows > 4): ?>
                            <span class="sp-mini-more">+<?php echo $template->rows - 4; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="sp-template-actions">
                    <a href="<?php echo home_url('/app/admin/bus-templates?action=edit&id=' . $template->id); ?>" 
                       class="sp-btn sp-btn-sm sp-btn-outline">
                        ✏️ <?php _e('تعديل', 'saint-porphyrius'); ?>
                    </a>
                    <form method="post" style="display: inline;" 
                          onsubmit="return confirm('<?php _e('هل أنت متأكد من الحذف؟', 'saint-porphyrius'); ?>');">
                        <?php wp_nonce_field('sp_bus_template_action', 'sp_bus_template_nonce'); ?>
                        <input type="hidden" name="template_action" value="delete">
                        <input type="hidden" name="template_id" value="<?php echo esc_attr($template->id); ?>">
                        <button type="submit" class="sp-btn sp-btn-sm sp-btn-outline sp-btn-danger">
                            🗑️
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</main>

<script>
jQuery(document).ready(function($) {
    function updateBusPreview() {
        var rows = parseInt($('input[name="rows"]').val()) || 10;
        var seatsPerRow = parseInt($('input[name="seats_per_row"]').val()) || 4;
        var aislePosition = parseInt($('select[name="aisle_position"]').val()) || 2;
        var icon = $('input[name="icon"]:checked').val() || '🚌';
        var color = $('input[name="color"]:checked').val() || '#3B82F6';
        
        var html = '<div class="sp-preview-bus" style="border-color: ' + color + ';">';
        html += '<div class="sp-preview-driver"><span>👨‍✈️</span><span style="color: ' + color + ';">' + icon + '</span></div>';
        html += '<div class="sp-preview-seats" style="grid-template-columns: repeat(' + seatsPerRow + ', 1fr);">';
        
        // Limit preview rows
        var previewRows = Math.min(rows, 6);
        for (var r = 1; r <= previewRows; r++) {
            for (var s = 1; s <= seatsPerRow; s++) {
                var isAisle = (s === aislePosition);
                html += '<div class="sp-preview-seat' + (isAisle ? ' after-aisle' : '') + '" style="border-color: ' + color + '30; background: ' + color + '10;"></div>';
            }
        }
        
        if (rows > 6) {
            html += '<div class="sp-preview-more" style="grid-column: span ' + seatsPerRow + '; color: ' + color + ';">+' + (rows - 6) + ' <?php _e('صفوف أخرى', 'saint-porphyrius'); ?></div>';
        }
        
        html += '</div></div>';
        
        $('#bus-preview').html(html);
    }
    
    // Update preview on input changes
    $('input[name="rows"], input[name="seats_per_row"], select[name="aisle_position"], input[name="icon"], input[name="color"]').on('change input', updateBusPreview);
    
    // Initial preview
    updateBusPreview();
});
</script>

<style>
/* Templates Grid */
.sp-bus-templates-grid {
    display: grid;
    gap: var(--sp-space-md);
}

.sp-bus-template-card {
    background: white;
    border-radius: var(--sp-radius-lg);
    overflow: hidden;
    border: 1px solid var(--sp-border);
}

.sp-bus-template-card.inactive {
    opacity: 0.6;
}

.sp-template-header {
    display: flex;
    align-items: center;
    gap: var(--sp-space-md);
    padding: var(--sp-space-md);
}

.sp-template-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--sp-radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    flex-shrink: 0;
}

.sp-template-info {
    flex: 1;
}

.sp-template-info h3 {
    margin: 0;
    font-size: var(--sp-font-size-md);
}

.sp-template-meta {
    display: flex;
    gap: var(--sp-space-xs);
    font-size: var(--sp-font-size-sm);
    color: var(--sp-text-secondary);
}

.sp-badge-inactive {
    background: var(--sp-background);
    color: var(--sp-text-tertiary);
    padding: 4px 8px;
    border-radius: var(--sp-radius-sm);
    font-size: var(--sp-font-size-xs);
}

/* Mini Bus Preview */
.sp-template-preview {
    padding: var(--sp-space-md);
    background: var(--sp-background);
}

.sp-mini-bus {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
}

.sp-mini-driver {
    font-size: 16px;
}

.sp-mini-seats {
    display: flex;
    flex-wrap: wrap;
    gap: 2px;
    justify-content: center;
    max-width: 120px;
}

.sp-mini-seat {
    width: 12px;
    height: 14px;
    background: #CBD5E1;
    border-radius: 2px;
}

.sp-mini-aisle {
    width: 6px;
}

.sp-mini-more {
    width: 100%;
    text-align: center;
    font-size: 10px;
    color: var(--sp-text-secondary);
}

.sp-template-actions {
    display: flex;
    gap: var(--sp-space-sm);
    padding: var(--sp-space-md);
    border-top: 1px solid var(--sp-border);
}

/* Form Styles */
.sp-form-card {
    padding: var(--sp-space-lg);
}

.sp-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--sp-space-md);
}

.sp-icon-grid {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-space-sm);
}

.sp-icon-option {
    cursor: pointer;
}

.sp-icon-option input {
    display: none;
}

.sp-icon-display {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    font-size: 24px;
    border: 2px solid var(--sp-border);
    border-radius: var(--sp-radius-md);
    transition: all 0.2s ease;
}

.sp-icon-option input:checked + .sp-icon-display {
    border-color: var(--sp-primary);
    background: var(--sp-primary-50);
}

.sp-color-grid {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-space-sm);
}

.sp-color-option {
    cursor: pointer;
}

.sp-color-option input {
    display: none;
}

.sp-color-display {
    display: block;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 3px solid transparent;
    transition: all 0.2s ease;
}

.sp-color-option input:checked + .sp-color-display {
    border-color: var(--sp-text-primary);
    transform: scale(1.1);
}

/* Bus Preview */
.sp-bus-preview {
    background: var(--sp-background);
    border-radius: var(--sp-radius-lg);
    padding: var(--sp-space-lg);
    min-height: 200px;
}

.sp-preview-bus {
    background: white;
    border: 3px solid;
    border-radius: 16px 16px 8px 8px;
    padding: var(--sp-space-md);
    max-width: 280px;
    margin: 0 auto;
}

.sp-preview-driver {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--sp-space-sm);
    background: var(--sp-background);
    border-radius: 8px;
    margin-bottom: var(--sp-space-md);
    font-size: 24px;
}

.sp-preview-seats {
    display: grid;
    gap: 6px;
    justify-content: center;
}

.sp-preview-seat {
    width: 32px;
    height: 36px;
    border: 2px solid;
    border-radius: 4px 4px 2px 2px;
}

.sp-preview-seat.after-aisle {
    margin-right: 12px;
}

.sp-preview-more {
    text-align: center;
    padding: var(--sp-space-sm);
    font-size: var(--sp-font-size-sm);
}

/* Alert Styles */
.sp-alert {
    padding: var(--sp-space-md);
    border-radius: var(--sp-radius-md);
    margin-bottom: var(--sp-space-lg);
}

.sp-alert-success {
    background: var(--sp-success-light);
    color: var(--sp-success);
}

.sp-alert-error {
    background: var(--sp-error-light);
    color: var(--sp-error);
}

/* Empty State */
.sp-empty-state {
    text-align: center;
    padding: var(--sp-space-2xl);
}

.sp-empty-icon {
    font-size: 64px;
    margin-bottom: var(--sp-space-md);
}

.sp-empty-state h3 {
    margin: 0 0 var(--sp-space-sm);
}

.sp-empty-state p {
    color: var(--sp-text-secondary);
    margin: 0 0 var(--sp-space-lg);
}

.required {
    color: var(--sp-error);
}
</style>
