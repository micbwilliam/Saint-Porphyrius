<?php
/**
 * Saint Porphyrius - Admin Attendance (Mobile)
 * Mark attendance for events
 */

if (!defined('ABSPATH')) {
    exit;
}

$events_handler = SP_Events::get_instance();
$attendance_handler = SP_Attendance::get_instance();
$excuses_handler = SP_Excuses::get_instance();
$forbidden_handler = SP_Forbidden::get_instance();
$expected_handler = SP_Expected_Attendance::get_instance();

$event_id = isset($_GET['event_id']) ? absint($_GET['event_id']) : 0;
$message = '';
$message_type = '';

// Handle attendance marking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sp_attendance_action'])) {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'sp_attendance_action')) {
        $message = __('خطأ في التحقق', 'saint-porphyrius');
        $message_type = 'error';
    } else {
        $event_id = absint($_POST['event_id']);
        $attendance_data = isset($_POST['attendance']) ? $_POST['attendance'] : array();
        
        $result = $attendance_handler->bulk_mark($event_id, $attendance_data);
        
        if ($result['success'] > 0) {
            $message = sprintf(__('تم تحديث حضور %d عضو', 'saint-porphyrius'), $result['success']);
            $message_type = 'success';
        }
        if ($result['errors'] > 0) {
            $message .= ' ' . sprintf(__('(%d خطأ)', 'saint-porphyrius'), $result['errors']);
            $message_type = 'warning';
        }
    }
}

// Get events for selection
$events = $events_handler->get_all(array(
    'limit' => 100,
    'orderby' => 'event_date',
    'order' => 'DESC',
));

// Get event details and members if event is selected
$selected_event = null;
$members = array();
$attendance_records = array();
$event_excuses = array();

if ($event_id) {
    $selected_event = $events_handler->get($event_id);
    if ($selected_event) {
        $members = get_users(array(
            'role' => 'sp_member',
            'orderby' => 'display_name',
        ));
        
        // Get existing attendance
        $records = $attendance_handler->get_by_event($event_id);
        foreach ($records as $record) {
            $attendance_records[$record->user_id] = $record;
        }
        
        // Get excuses for this event
        $excuses = $excuses_handler->get_event_excuses($event_id);
        foreach ($excuses as $excuse) {
            $event_excuses[$excuse->user_id] = $excuse;
        }
    }
}
?>

<!-- Admin Header -->
<div class="sp-unified-header sp-admin-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/admin'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('تسجيل الحضور', 'saint-porphyrius'); ?></h1>
        <a href="<?php echo home_url('/app/admin/qr-scanner'); ?>" class="sp-header-action" title="<?php _e('ماسح QR', 'saint-porphyrius'); ?>">
            <span class="dashicons dashicons-camera" style="font-size: 24px; width: 24px; height: 24px;"></span>
        </a>
    </div>
</div>

<!-- QR Scanner Quick Access -->
<div class="sp-card sp-qr-scanner-cta" style="background: linear-gradient(135deg, var(--sp-primary) 0%, var(--sp-primary-dark, #5A8AC7) 100%); color: white; margin-bottom: var(--sp-space-md);">
    <a href="<?php echo home_url('/app/admin/qr-scanner'); ?>" style="display: flex; align-items: center; gap: 16px; text-decoration: none; color: inherit;">
        <div style="background: rgba(255,255,255,0.2); width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
            <span class="dashicons dashicons-camera" style="font-size: 28px; width: 28px; height: 28px;"></span>
        </div>
        <div style="flex: 1;">
            <h3 style="margin: 0 0 4px; font-weight: 600;"><?php _e('ماسح QR للحضور', 'saint-porphyrius'); ?></h3>
            <p style="margin: 0; opacity: 0.9; font-size: var(--sp-font-size-sm);"><?php _e('امسح رموز QR من هواتف الأعضاء لتسجيل حضورهم بسرعة وأمان', 'saint-porphyrius'); ?></p>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity: 0.7;">
            <polyline points="9 18 15 12 9 6"></polyline>
        </svg>
    </a>
</div>

<!-- Main Content -->
<main class="sp-page-content sp-admin-content">
    <?php if ($message): ?>
        <div class="sp-alert sp-alert-<?php echo esc_attr($message_type); ?>">
            <?php echo esc_html($message); ?>
        </div>
    <?php endif; ?>

    <!-- Event Selection -->
    <div class="sp-admin-card" style="margin-bottom: var(--sp-space-md);">
        <div class="sp-form-group">
            <label class="sp-form-label"><?php _e('اختر الفعالية', 'saint-porphyrius'); ?></label>
            <form method="get" class="sp-event-select-form" style="margin: 0;">
                <select name="event_id" onchange="this.form.submit()" class="sp-form-select">
                    <option value=""><?php _e('-- اختر فعالية --', 'saint-porphyrius'); ?></option>
                    <?php foreach ($events as $event): ?>
                        <option value="<?php echo esc_attr($event->id); ?>" <?php selected($event_id, $event->id); ?>>
                            <?php echo esc_html($event->type_icon . ' ' . $event->title_ar . ' - ' . date_i18n('j M Y', strtotime($event->event_date))); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <?php if ($selected_event && !empty($members)): ?>
        <!-- Event Info -->
        <div class="sp-attendance-event-info">
            <div class="sp-attendance-event-icon" style="background: <?php echo esc_attr($selected_event->type_color); ?>20; color: <?php echo esc_attr($selected_event->type_color); ?>;">
                <?php echo esc_html($selected_event->type_icon); ?>
            </div>
            <div class="sp-attendance-event-details">
                <h3><?php echo esc_html($selected_event->title_ar); ?></h3>
                <p><?php echo esc_html(date_i18n('l j F Y', strtotime($selected_event->event_date)) . ' • ' . $selected_event->start_time); ?></p>
            </div>
        </div>

        <!-- Quick Stats -->
        <?php
        $attended = 0;
        $late = 0;
        $absent = 0;
        $excused = 0;
        $forbidden = 0;
        foreach ($attendance_records as $record) {
            if ($record->status === 'attended') $attended++;
            elseif ($record->status === 'late') $late++;
            elseif ($record->status === 'absent') $absent++;
            elseif ($record->status === 'excused') $excused++;
            elseif ($record->status === 'forbidden') $forbidden++;
        }
        $is_forbidden_event = !empty($selected_event->forbidden_enabled);
        ?>
        
        <?php if ($is_forbidden_event): ?>
        <div class="sp-forbidden-event-notice">
            <span class="sp-forbidden-icon">⛔</span>
            <span><?php _e('نظام المحروم مفعّل لهذه الفعالية', 'saint-porphyrius'); ?></span>
        </div>
        <?php endif; ?>
        
        <div class="sp-attendance-stats">
            <div class="sp-attendance-stat attended">
                <span class="value"><?php echo esc_html($attended); ?></span>
                <span class="label"><?php _e('حاضر', 'saint-porphyrius'); ?></span>
            </div>
            <div class="sp-attendance-stat late">
                <span class="value"><?php echo esc_html($late); ?></span>
                <span class="label"><?php _e('متأخر', 'saint-porphyrius'); ?></span>
            </div>
            <div class="sp-attendance-stat absent">
                <span class="value"><?php echo esc_html($absent); ?></span>
                <span class="label"><?php _e('غائب', 'saint-porphyrius'); ?></span>
            </div>
            <div class="sp-attendance-stat excused">
                <span class="value"><?php echo esc_html($excused); ?></span>
                <span class="label"><?php _e('معذور', 'saint-porphyrius'); ?></span>
            </div>
            <?php if ($is_forbidden_event): ?>
            <div class="sp-attendance-stat forbidden">
                <span class="value"><?php echo esc_html($forbidden); ?></span>
                <span class="label"><?php _e('محروم', 'saint-porphyrius'); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Expected Attendance Section -->
        <?php 
        $expected_attendance_enabled = isset($selected_event->expected_attendance_enabled) ? $selected_event->expected_attendance_enabled : true;
        if ($expected_attendance_enabled):
            $expected_registrations = $expected_handler->get_event_registrations($event_id);
            $expected_count = count($expected_registrations);
        ?>
        <div class="sp-admin-card" style="margin-bottom: var(--sp-space-md);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                <h4 style="margin: 0; font-size: var(--sp-font-size-base); font-weight: 600;">
                    🙋 <?php _e('الحضور المتوقع', 'saint-porphyrius'); ?> 
                    <span style="color: var(--sp-text-secondary); font-weight: 400;">(<?php echo $expected_count; ?>)</span>
                </h4>
            </div>
            
            <?php if (empty($expected_registrations)): ?>
                <p style="color: var(--sp-text-secondary); text-align: center; padding: 16px 0; margin: 0;">
                    <?php _e('لم يسجل أحد بعد', 'saint-porphyrius'); ?>
                </p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 8px; max-height: 200px; overflow-y: auto;">
                    <?php foreach ($expected_registrations as $reg): ?>
                        <div style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; background: var(--sp-background, #f9fafb); border-radius: var(--sp-radius-md);">
                            <span style="width: 24px; height: 24px; background: var(--sp-primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600;">
                                <?php echo $reg->order_number; ?>
                            </span>
                            <span style="flex: 1; font-weight: 500;"><?php echo esc_html($reg->display_name_final); ?></span>
                            <span class="sp-badge" style="background: <?php echo esc_attr($reg->status_color); ?>20; color: <?php echo esc_attr($reg->status_color); ?>; font-size: 11px; padding: 2px 6px;">
                                <?php echo esc_html($reg->status_label); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Attendance Form -->
        <form method="post" class="sp-attendance-form" style="margin-top: 0;">
            <?php wp_nonce_field('sp_attendance_action'); ?>
            <input type="hidden" name="sp_attendance_action" value="mark">
            <input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>">
            
            <!-- Quick Actions -->
            <div class="sp-attendance-quick-actions">
                <button type="button" class="sp-btn sp-btn-sm sp-btn-outline" onclick="setAllAttendance('attended')">
                    ✓ <?php _e('الكل حاضر', 'saint-porphyrius'); ?>
                </button>
                <button type="button" class="sp-btn sp-btn-sm sp-btn-outline" onclick="setAllAttendance('absent')">
                    ✕ <?php _e('الكل غائب', 'saint-porphyrius'); ?>
                </button>
            </div>
            
            <!-- Members List Section -->
            <div class="sp-attendance-list-section">
                <h4 class="sp-attendance-list-title"><?php _e('تسجيل الحضور', 'saint-porphyrius'); ?></h4>
                <!-- Members List -->
                <div class="sp-attendance-list">
                <?php foreach ($members as $member): 
                    $name_ar = get_user_meta($member->ID, 'sp_name_ar', true);
                    $full_name = $name_ar ?: $member->display_name;
                    $current_status = isset($attendance_records[$member->ID]) ? $attendance_records[$member->ID]->status : '';
                    $has_excuse = isset($event_excuses[$member->ID]);
                    $excuse = $has_excuse ? $event_excuses[$member->ID] : null;
                    
                    // Get forbidden status for this member
                    $user_forbidden_status = $forbidden_handler->get_user_status($member->ID);
                    $is_user_forbidden = $user_forbidden_status->forbidden_remaining > 0;
                    $user_card = $user_forbidden_status->card_status;
                    $user_absences = $user_forbidden_status->consecutive_absences;
                ?>
                    <div class="sp-attendance-member <?php echo $has_excuse ? 'has-excuse' : ''; ?> <?php echo $is_user_forbidden ? 'is-forbidden' : ''; ?>">
                        <div class="sp-attendance-member-info">
                            <div class="sp-attendance-member-avatar <?php echo $user_card !== 'none' ? 'has-card-' . $user_card : ''; ?>">
                                <?php echo esc_html(mb_substr($full_name, 0, 1)); ?>
                                <?php if ($user_card === 'yellow'): ?>
                                    <span class="sp-card-indicator yellow">🟡</span>
                                <?php elseif ($user_card === 'red'): ?>
                                    <span class="sp-card-indicator red">🔴</span>
                                <?php endif; ?>
                            </div>
                            <div class="sp-attendance-member-name">
                                <span><?php echo esc_html($full_name); ?></span>
                                <div class="sp-member-badges">
                                    <?php if ($has_excuse): ?>
                                        <span class="sp-excuse-badge sp-excuse-<?php echo esc_attr($excuse->status); ?>">
                                            <?php 
                                            $excuse_statuses = array(
                                                'pending' => __('اعتذار معلق', 'saint-porphyrius'),
                                                'approved' => __('اعتذار مقبول', 'saint-porphyrius'),
                                                'rejected' => __('اعتذار مرفوض', 'saint-porphyrius'),
                                            );
                                            echo esc_html($excuse_statuses[$excuse->status] ?? $excuse->status);
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($is_user_forbidden && $is_forbidden_event): ?>
                                        <span class="sp-forbidden-user-badge">⛔ <?php printf(__('محروم (%d متبقي)', 'saint-porphyrius'), $user_forbidden_status->forbidden_remaining); ?></span>
                                    <?php endif; ?>
                                    <?php if ($user_absences > 0): ?>
                                        <span class="sp-absences-count <?php echo $user_absences >= 3 ? 'warning' : ''; ?>"><?php printf(__('%d غيابات', 'saint-porphyrius'), $user_absences); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="sp-attendance-member-status">
                            <select name="attendance[<?php echo esc_attr($member->ID); ?>]" class="sp-attendance-select" data-member="<?php echo esc_attr($member->ID); ?>">
                                <option value=""><?php _e('--', 'saint-porphyrius'); ?></option>
                                <option value="attended" <?php selected($current_status, 'attended'); ?>>✓ <?php _e('حاضر', 'saint-porphyrius'); ?></option>
                                <option value="late" <?php selected($current_status, 'late'); ?>>⏰ <?php _e('متأخر', 'saint-porphyrius'); ?></option>
                                <option value="absent" <?php selected($current_status, 'absent'); ?>>✕ <?php _e('غائب', 'saint-porphyrius'); ?></option>
                                <option value="excused" <?php selected($current_status, 'excused'); ?>>📝 <?php _e('معذور', 'saint-porphyrius'); ?></option>
                                <?php if ($is_forbidden_event): ?>
                                <option value="forbidden" <?php selected($current_status, 'forbidden'); ?>>⛔ <?php _e('محروم', 'saint-porphyrius'); ?></option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
            
            <div class="sp-form-actions sp-form-actions-sticky">
                <button type="submit" class="sp-btn sp-btn-primary sp-btn-block">
                    <?php _e('حفظ الحضور', 'saint-porphyrius'); ?>
                </button>
            </div>
        </form>
        
        <script>
        function setAllAttendance(status) {
            document.querySelectorAll('.sp-attendance-select').forEach(function(select) {
                select.value = status;
            });
        }
        </script>
    <?php elseif ($event_id): ?>
        <div class="sp-empty-state">
            <div class="sp-empty-icon">❌</div>
            <h3><?php _e('الفعالية غير موجودة', 'saint-porphyrius'); ?></h3>
        </div>
    <?php else: ?>
        <div class="sp-empty-state">
            <div class="sp-empty-icon">📋</div>
            <h3><?php _e('اختر فعالية', 'saint-porphyrius'); ?></h3>
            <p><?php _e('اختر فعالية من القائمة أعلاه لتسجيل الحضور', 'saint-porphyrius'); ?></p>
        </div>
    <?php endif; ?>
</main>
