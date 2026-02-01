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
        <div class="sp-header-spacer"></div>
    </div>
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
        foreach ($attendance_records as $record) {
            if ($record->status === 'attended') $attended++;
            elseif ($record->status === 'late') $late++;
            elseif ($record->status === 'absent') $absent++;
            elseif ($record->status === 'excused') $excused++;
        }
        ?>
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
        </div>

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
                ?>
                    <div class="sp-attendance-member <?php echo $has_excuse ? 'has-excuse' : ''; ?>">
                        <div class="sp-attendance-member-info">
                            <div class="sp-attendance-member-avatar">
                                <?php echo esc_html(mb_substr($full_name, 0, 1)); ?>
                            </div>
                            <div class="sp-attendance-member-name">
                                <span><?php echo esc_html($full_name); ?></span>
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
                            </div>
                        </div>
                        <div class="sp-attendance-member-status">
                            <select name="attendance[<?php echo esc_attr($member->ID); ?>]" class="sp-attendance-select" data-member="<?php echo esc_attr($member->ID); ?>">
                                <option value=""><?php _e('--', 'saint-porphyrius'); ?></option>
                                <option value="attended" <?php selected($current_status, 'attended'); ?>>✓ <?php _e('حاضر', 'saint-porphyrius'); ?></option>
                                <option value="late" <?php selected($current_status, 'late'); ?>>⏰ <?php _e('متأخر', 'saint-porphyrius'); ?></option>
                                <option value="absent" <?php selected($current_status, 'absent'); ?>>✕ <?php _e('غائب', 'saint-porphyrius'); ?></option>
                                <option value="excused" <?php selected($current_status, 'excused'); ?>>📝 <?php _e('معذور', 'saint-porphyrius'); ?></option>
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
