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
        
        // Build layout config
        $layout_config = array(
            'driver_seats' => absint($_POST['driver_seats'] ?? 1),
            'back_row_extra' => absint($_POST['back_row_extra'] ?? 1),
            'disabled_seats' => array('1A'), // Driver seat always disabled
        );
        
        if ($action === 'create') {
            $result = $bus_handler->create_template(array(
                'name_ar' => sanitize_text_field($_POST['name_ar']),
                'name_en' => sanitize_text_field($_POST['name_en'] ?? ''),
                'capacity' => absint($_POST['capacity']),
                'rows' => absint($_POST['rows']),
                'seats_per_row' => absint($_POST['seats_per_row']),
                'aisle_position' => absint($_POST['aisle_position']),
                'layout_config' => $layout_config,
                'icon' => sanitize_text_field($_POST['icon']),
                'color' => sanitize_hex_color($_POST['color']),
                'is_active' => 1, // Templates are always active
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
                'layout_config' => $layout_config,
                'icon' => sanitize_text_field($_POST['icon']),
                'color' => sanitize_hex_color($_POST['color']),
                'is_active' => 1, // Templates are always active
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
$edit_layout_config = array('driver_seats' => 1, 'back_row_extra' => 1);
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_template = $bus_handler->get_template(absint($_GET['id']));
    if ($edit_template && $edit_template->layout_config) {
        $parsed = json_decode($edit_template->layout_config, true);
        if (is_array($parsed)) {
            $edit_layout_config = array_merge($edit_layout_config, $parsed);
        }
    }
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
                    <label class="sp-form-label"><?php _e('عدد الصفوف العادية', 'saint-porphyrius'); ?> <span class="required">*</span></label>
                    <input type="number" name="rows" class="sp-form-input" required min="2" max="25"
                           value="<?php echo $edit_template ? esc_attr($edit_template->rows) : '10'; ?>">
                    <small class="sp-form-hint"><?php _e('عدد الصفوف بعد السائق وقبل الصف الخلفي', 'saint-porphyrius'); ?></small>
                </div>
                <div class="sp-form-group sp-form-group-half">
                    <label class="sp-form-label"><?php _e('مقاعد في كل صف', 'saint-porphyrius'); ?> <span class="required">*</span></label>
                    <input type="number" name="seats_per_row" class="sp-form-input" required min="2" max="6"
                           value="<?php echo $edit_template ? esc_attr($edit_template->seats_per_row) : '4'; ?>">
                </div>
            </div>
            
            <div class="sp-form-row">
                <div class="sp-form-group sp-form-group-half">
                    <label class="sp-form-label"><?php _e('مقاعد صف السائق', 'saint-porphyrius'); ?></label>
                    <select name="driver_seats" class="sp-form-select">
                        <option value="1" <?php selected($edit_layout_config['driver_seats'], 1); ?>><?php _e('1 مقعد (سائق فقط)', 'saint-porphyrius'); ?></option>
                        <option value="2" <?php selected($edit_layout_config['driver_seats'], 2); ?>><?php _e('2 مقعد (سائق + راكب)', 'saint-porphyrius'); ?></option>
                        <option value="3" <?php selected($edit_layout_config['driver_seats'], 3); ?>><?php _e('3 مقاعد (ميني فان)', 'saint-porphyrius'); ?></option>
                    </select>
                    <small class="sp-form-hint"><?php _e('عدد المقاعد بجانب السائق', 'saint-porphyrius'); ?></small>
                </div>
                <div class="sp-form-group sp-form-group-half">
                    <label class="sp-form-label"><?php _e('مقاعد إضافية بالصف الخلفي', 'saint-porphyrius'); ?></label>
                    <select name="back_row_extra" class="sp-form-select">
                        <option value="0" <?php selected($edit_layout_config['back_row_extra'], 0); ?>><?php _e('لا يوجد (نفس عدد الصفوف العادية)', 'saint-porphyrius'); ?></option>
                        <option value="1" <?php selected($edit_layout_config['back_row_extra'], 1); ?>><?php _e('+1 مقعد إضافي', 'saint-porphyrius'); ?></option>
                        <option value="2" <?php selected($edit_layout_config['back_row_extra'], 2); ?>><?php _e('+2 مقاعد إضافية', 'saint-porphyrius'); ?></option>
                    </select>
                    <small class="sp-form-hint"><?php _e('المقاعد الإضافية في الصف الأخير (عادة +1)', 'saint-porphyrius'); ?></small>
                </div>
            </div>
            
            <div class="sp-form-row">
                <div class="sp-form-group sp-form-group-half">
                    <label class="sp-form-label"><?php _e('موقع الممر', 'saint-porphyrius'); ?></label>
                    <select name="aisle_position" class="sp-form-select">
                        <option value="2" <?php selected($edit_template ? $edit_template->aisle_position : 2, 2); ?>><?php _e('بعد المقعد الثاني (2|2)', 'saint-porphyrius'); ?></option>
                        <option value="3" <?php selected($edit_template ? $edit_template->aisle_position : 2, 3); ?>><?php _e('بعد المقعد الثالث (3|1 أو 3|2)', 'saint-porphyrius'); ?></option>
                    </select>
                </div>
                <div class="sp-form-group sp-form-group-half">
                    <label class="sp-form-label"><?php _e('السعة الإجمالية', 'saint-porphyrius'); ?> <span class="required">*</span></label>
                    <input type="number" name="capacity" class="sp-form-input" required min="4" max="100"
                           value="<?php echo $edit_template ? esc_attr($edit_template->capacity) : '40'; ?>" id="capacity-input">
                    <small class="sp-form-hint" id="capacity-hint"><?php _e('سيتم حساب السعة تلقائياً', 'saint-porphyrius'); ?></small>
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
            <div class="sp-bus-template-card">
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
                </div>
                
                <div class="sp-template-preview">
                    <?php 
                    // Parse layout config
                    $layout = json_decode($template->layout_config ?? '{}', true);
                    $driver_seats = isset($layout['driver_seats']) ? intval($layout['driver_seats']) : 1;
                    $back_extra = isset($layout['back_row_extra']) ? intval($layout['back_row_extra']) : 1;
                    $back_row_seats = $template->seats_per_row + $back_extra;
                    ?>
                    <div class="sp-mini-bus" style="border-color: <?php echo esc_attr($template->color); ?>30;">
                        <!-- Front -->
                        <div class="sp-mini-front" style="background: <?php echo esc_attr($template->color); ?>;">
                            <?php echo esc_html($template->icon); ?>
                        </div>
                        
                        <!-- Driver Row (driver on left side) -->
                        <div class="sp-mini-row sp-mini-driver-row">
                            <?php for ($d = 1; $d < $driver_seats; $d++): ?>
                            <span class="sp-mini-seat" style="background: <?php echo esc_attr($template->color); ?>30;"></span>
                            <?php endfor; ?>
                            <span class="sp-mini-seat sp-mini-driver-seat">👨‍✈️</span>
                        </div>
                        
                        <!-- Regular Rows -->
                        <div class="sp-mini-rows">
                            <?php 
                            $preview_rows = min(3, $template->rows);
                            for ($r = 1; $r <= $preview_rows; $r++): ?>
                            <div class="sp-mini-row">
                                <?php for ($s = 1; $s <= $template->seats_per_row; $s++): ?>
                                <span class="sp-mini-seat" style="background: <?php echo esc_attr($template->color); ?>20;"></span>
                                <?php endfor; ?>
                            </div>
                            <?php endfor; ?>
                            <?php if ($template->rows > 3): ?>
                            <div class="sp-mini-more" style="color: <?php echo esc_attr($template->color); ?>;">
                                ⋮ +<?php echo $template->rows - 3; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Back Row -->
                        <div class="sp-mini-row sp-mini-back-row">
                            <?php for ($b = 1; $b <= $back_row_seats; $b++): ?>
                            <span class="sp-mini-seat sp-mini-back-seat" style="background: <?php echo esc_attr($template->color); ?>30;"></span>
                            <?php endfor; ?>
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
    // International seat numbering: Row number + Letter (1A, 1B, 2A, 2B, etc.)
    // Left side: A, B (window to aisle) | Right side: C, D (aisle to window)
    function getSeatLabel(row, seatInRow, seatsPerRow, aislePosition) {
        var letters = ['A', 'B', 'C', 'D', 'E', 'F'];
        return row + letters[seatInRow - 1];
    }
    
    function calculateCapacity() {
        var rows = parseInt($('input[name="rows"]').val()) || 10;
        var seatsPerRow = parseInt($('input[name="seats_per_row"]').val()) || 4;
        var driverSeats = parseInt($('select[name="driver_seats"]').val()) || 1;
        var backRowExtraVal = $('select[name="back_row_extra"]').val();
        var backRowExtra = (backRowExtraVal !== '' && !isNaN(backRowExtraVal)) ? parseInt(backRowExtraVal) : 1;
        
        // Driver row: driverSeats - 1 passengers (driver seat not counted)
        // Regular rows: rows * seatsPerRow
        // Back row: seatsPerRow + backRowExtra
        var passengerDriverRow = Math.max(0, driverSeats - 1);
        var regularRowsSeats = rows * seatsPerRow;
        var backRowSeats = seatsPerRow + backRowExtra;
        
        var total = passengerDriverRow + regularRowsSeats + backRowSeats;
        
        $('#capacity-input').val(total);
        $('#capacity-hint').text('<?php _e('السعة المحسوبة:', 'saint-porphyrius'); ?> ' + total + ' <?php _e('راكب', 'saint-porphyrius'); ?>');
    }
    
    function updateBusPreview() {
        var rows = parseInt($('input[name="rows"]').val()) || 10;
        var seatsPerRow = parseInt($('input[name="seats_per_row"]').val()) || 4;
        var aislePosition = parseInt($('select[name="aisle_position"]').val()) || 2;
        var driverSeats = parseInt($('select[name="driver_seats"]').val()) || 1;
        var backRowExtraVal = $('select[name="back_row_extra"]').val();
        var backRowExtra = (backRowExtraVal !== '' && !isNaN(backRowExtraVal)) ? parseInt(backRowExtraVal) : 1;
        var icon = $('input[name="icon"]:checked').val() || '🚌';
        var color = $('input[name="color"]:checked').val() || '#3B82F6';
        
        var html = '<div class="sp-preview-bus" style="border-color: ' + color + ';">';
        
        // Bus front with icon
        html += '<div class="sp-preview-front" style="background: ' + color + ';">';
        html += '<span class="sp-preview-icon">' + icon + '</span>';
        html += '</div>';
        
        // Row 1: Driver row
        html += '<div class="sp-preview-row sp-preview-driver-row">';
        html += '<div class="sp-row-number">1</div>';
        html += '<div class="sp-row-seats" style="grid-template-columns: repeat(' + seatsPerRow + ', 1fr);">';
        
        // Empty space on right side (RTL: appears on left visually)
        var emptySpaces = seatsPerRow - driverSeats;
        for (var e = 0; e < emptySpaces; e++) {
            html += '<div class="sp-preview-empty"></div>';
        }
        
        // Passenger seats beside driver
        for (var d = driverSeats - 1; d >= 1; d--) {
            var dLabel = getSeatLabel(1, d + 1, driverSeats, aislePosition);
            html += '<div class="sp-preview-seat" style="border-color: ' + color + '50; background: ' + color + '10;" title="' + dLabel + '"><span class="sp-seat-label">' + dLabel + '</span></div>';
        }
        
        // Driver seat (on left side - RTL: appears on right visually which is left side of bus)
        html += '<div class="sp-preview-seat sp-seat-driver" style="border-color: ' + color + '; background: ' + color + '30;" title="<?php _e('السائق', 'saint-porphyrius'); ?>"><span class="sp-seat-icon">👨‍✈️</span></div>';
        
        html += '</div></div>';
        
        // Regular rows (show max 5 rows in preview, with indication of more)
        var previewRows = Math.min(rows, 5);
        for (var r = 0; r < previewRows; r++) {
            var rowNum = r + 2; // Row numbers start after driver row
            html += '<div class="sp-preview-row">';
            html += '<div class="sp-row-number">' + rowNum + '</div>';
            html += '<div class="sp-row-seats" style="grid-template-columns: repeat(' + seatsPerRow + ', 1fr);">';
            
            for (var s = 1; s <= seatsPerRow; s++) {
                var label = getSeatLabel(rowNum, s, seatsPerRow, aislePosition);
                var aisleClass = (s === aislePosition) ? ' sp-seat-aisle-left' : '';
                html += '<div class="sp-preview-seat' + aisleClass + '" style="border-color: ' + color + '50; background: ' + color + '10;" title="' + label + '"><span class="sp-seat-label">' + label + '</span></div>';
            }
            
            html += '</div></div>';
        }
        
        // Show "more rows" indicator
        if (rows > 5) {
            html += '<div class="sp-preview-more-rows" style="color: ' + color + '; border-color: ' + color + '30;">';
            html += '<span>⋮</span>';
            html += '<span>' + (rows - 5) + ' <?php _e('صفوف أخرى', 'saint-porphyrius'); ?></span>';
            html += '</div>';
        }
        
        // Back row (with extra seats)
        var backRowNum = rows + 2;
        var backRowSeats = seatsPerRow + backRowExtra;
        html += '<div class="sp-preview-row sp-preview-back-row">';
        html += '<div class="sp-row-number">' + backRowNum + '</div>';
        html += '<div class="sp-row-seats" style="grid-template-columns: repeat(' + backRowSeats + ', 1fr);">';
        
        for (var b = 1; b <= backRowSeats; b++) {
            var bLabel = getSeatLabel(backRowNum, b, backRowSeats, aislePosition);
            html += '<div class="sp-preview-seat sp-seat-back" style="border-color: ' + color + '50; background: ' + color + '15;" title="' + bLabel + '"><span class="sp-seat-label">' + bLabel + '</span></div>';
        }
        
        html += '</div></div>';
        
        html += '</div>';
        
        // Legend
        html += '<div class="sp-preview-legend">';
        html += '<div class="sp-legend-item"><span class="sp-legend-seat sp-legend-driver">👨‍✈️</span> <?php _e('السائق', 'saint-porphyrius'); ?></div>';
        html += '<div class="sp-legend-item"><span class="sp-legend-seat" style="background: ' + color + '10; border-color: ' + color + '50;"></span> <?php _e('مقعد متاح', 'saint-porphyrius'); ?></div>';
        html += '<div class="sp-legend-item"><span class="sp-legend-seat sp-legend-back" style="background: ' + color + '15; border-color: ' + color + '50;"></span> <?php _e('الصف الخلفي', 'saint-porphyrius'); ?></div>';
        html += '</div>';
        
        // Stats
        var passengerDriverRow = Math.max(0, driverSeats - 1);
        var totalSeats = passengerDriverRow + (rows * seatsPerRow) + backRowSeats;
        html += '<div class="sp-preview-stats" style="background: ' + color + '10; border-color: ' + color + '30;">';
        html += '<div class="sp-stat">';
        html += '<span class="sp-stat-value">' + (rows + 2) + '</span>';
        html += '<span class="sp-stat-label"><?php _e('صفوف', 'saint-porphyrius'); ?></span>';
        html += '</div>';
        html += '<div class="sp-stat">';
        html += '<span class="sp-stat-value">' + totalSeats + '</span>';
        html += '<span class="sp-stat-label"><?php _e('مقعد', 'saint-porphyrius'); ?></span>';
        html += '</div>';
        html += '</div>';
        
        $('#bus-preview').html(html);
    }
    
    // Update preview and capacity on input changes
    $('input[name="rows"], input[name="seats_per_row"], select[name="aisle_position"], select[name="driver_seats"], select[name="back_row_extra"], input[name="icon"], input[name="color"]').on('change input', function() {
        calculateCapacity();
        updateBusPreview();
    });
    
    // Initial preview and capacity calculation
    calculateCapacity();
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

/* Mini Bus Preview - Templates List */
.sp-template-preview {
    padding: var(--sp-space-md);
    background: var(--sp-background);
}

.sp-mini-bus {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: var(--sp-space-sm);
    border: 2px solid;
    border-radius: 12px 12px 6px 6px;
    background: white;
    max-width: 140px;
    margin: 0 auto;
}

.sp-mini-front {
    width: 100%;
    text-align: center;
    padding: 4px;
    border-radius: 6px 6px 2px 2px;
    font-size: 14px;
    color: white;
}

.sp-mini-row {
    display: flex;
    gap: 3px;
    justify-content: center;
}

.sp-mini-driver-row {
    padding-bottom: 4px;
    border-bottom: 1px dashed var(--sp-border);
    margin-bottom: 2px;
}

.sp-mini-rows {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.sp-mini-back-row {
    padding-top: 4px;
    border-top: 1px dashed var(--sp-border);
    margin-top: 2px;
}

.sp-mini-seat {
    width: 16px;
    height: 18px;
    border-radius: 3px 3px 1px 1px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
}

.sp-mini-driver-seat {
    background: #E5E7EB;
}

.sp-mini-back-seat {
    width: 14px;
    height: 16px;
}

.sp-mini-more {
    width: 100%;
    text-align: center;
    font-size: 10px;
    padding: 2px 0;
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

/* Bus Preview - International Standard Layout */
.sp-bus-preview {
    background: var(--sp-background);
    border-radius: var(--sp-radius-lg);
    padding: var(--sp-space-lg);
    min-height: 300px;
}

.sp-preview-bus {
    background: white;
    border: 3px solid;
    border-radius: 20px 20px 12px 12px;
    padding: var(--sp-space-md);
    max-width: 320px;
    margin: 0 auto;
    position: relative;
}

.sp-preview-front {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: var(--sp-space-sm);
    border-radius: 12px 12px 4px 4px;
    margin-bottom: var(--sp-space-sm);
}

.sp-preview-icon {
    font-size: 28px;
    filter: brightness(0) invert(1);
}

.sp-preview-row {
    display: flex;
    align-items: center;
    gap: var(--sp-space-sm);
    margin-bottom: 6px;
}

.sp-row-number {
    width: 20px;
    font-size: 11px;
    font-weight: 600;
    color: var(--sp-text-tertiary);
    text-align: center;
}

.sp-row-seats {
    display: grid;
    gap: 4px;
    flex: 1;
}

.sp-preview-seat {
    width: 100%;
    min-width: 38px;
    height: 34px;
    border: 2px solid;
    border-radius: 6px 6px 3px 3px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    transition: all 0.2s ease;
    cursor: default;
}

.sp-preview-seat:hover {
    transform: scale(1.05);
    z-index: 1;
}

.sp-seat-label {
    font-size: 10px;
    font-weight: 600;
    color: var(--sp-text-secondary);
}

.sp-seat-icon {
    font-size: 16px;
}

.sp-seat-driver {
    font-weight: bold;
}

.sp-seat-aisle-left {
    margin-left: 8px;
}

.sp-seat-back {
    border-width: 2px;
}

.sp-preview-empty {
    width: 100%;
    min-width: 38px;
    height: 34px;
}

.sp-preview-driver-row {
    padding-bottom: var(--sp-space-sm);
    border-bottom: 1px dashed var(--sp-border);
    margin-bottom: var(--sp-space-sm);
}

.sp-preview-back-row {
    padding-top: var(--sp-space-sm);
    border-top: 1px dashed var(--sp-border);
    margin-top: var(--sp-space-sm);
}

.sp-preview-more-rows {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: var(--sp-space-xs) 0;
    font-size: var(--sp-font-size-xs);
    border: 1px dashed;
    border-radius: var(--sp-radius-sm);
    margin: var(--sp-space-xs) 0;
}

.sp-preview-more-rows span:first-child {
    font-size: 16px;
    line-height: 1;
}

/* Preview Legend */
.sp-preview-legend {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: var(--sp-space-md);
    margin-top: var(--sp-space-md);
    padding-top: var(--sp-space-md);
    border-top: 1px solid var(--sp-border);
}

.sp-legend-item {
    display: flex;
    align-items: center;
    gap: var(--sp-space-xs);
    font-size: var(--sp-font-size-xs);
    color: var(--sp-text-secondary);
}

.sp-legend-seat {
    width: 24px;
    height: 22px;
    border: 1px solid var(--sp-border);
    border-radius: 4px 4px 2px 2px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}

.sp-legend-driver {
    background: var(--sp-primary-50);
    border-color: var(--sp-primary);
}

/* Preview Stats */
.sp-preview-stats {
    display: flex;
    justify-content: center;
    gap: var(--sp-space-lg);
    margin-top: var(--sp-space-md);
    padding: var(--sp-space-sm) var(--sp-space-md);
    border: 1px solid;
    border-radius: var(--sp-radius-md);
}

.sp-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.sp-stat-value {
    font-size: var(--sp-font-size-lg);
    font-weight: 700;
    color: var(--sp-text-primary);
}

.sp-stat-label {
    font-size: var(--sp-font-size-xs);
    color: var(--sp-text-secondary);
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
