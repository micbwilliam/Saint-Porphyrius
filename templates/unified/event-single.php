<?php
/**
 * Saint Porphyrius - Single Event Template (Unified Design)
 * Shows event details with modern design
 */

if (!defined('ABSPATH')) {
    exit;
}

$event_id = get_query_var('sp_event_id');
$events_handler = SP_Events::get_instance();
$forbidden_handler = SP_Forbidden::get_instance();
$event = $events_handler->get($event_id);

if (!$event) {
    wp_safe_redirect(home_url('/app/events'));
    exit;
}

$event_date = strtotime($event->event_date);
$points_config = $events_handler->get_event_points($event);
$has_map_url = !empty($event->location_map_url);

// Get user's forbidden status for this event
$user_id = get_current_user_id();
$user_forbidden_status = $forbidden_handler->get_user_status($user_id);
$is_user_forbidden = $user_forbidden_status->forbidden_remaining > 0 && !empty($event->forbidden_enabled);
?>

<!-- Unified Header with Event Color -->
<div class="sp-unified-header sp-header-colored" style="--header-color: <?php echo esc_attr($event->type_color); ?>;">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/events'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('تفاصيل الفعالية', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-spacer"></div>
    </div>
</div>

<!-- Main Content -->
<main class="sp-page-content has-bottom-nav">
    <!-- Event Hero Section -->
    <div class="sp-card" style="background: <?php echo esc_attr($event->type_color); ?>15; border: none; text-align: center; padding: var(--sp-space-xl);">
        <div style="font-size: 56px; margin-bottom: 12px;"><?php echo esc_html($event->type_icon); ?></div>
        <span class="sp-badge" style="background: <?php echo esc_attr($event->type_color); ?>25; color: <?php echo esc_attr($event->type_color); ?>;">
            <?php echo esc_html($event->type_name_ar); ?>
        </span>
        <h1 style="font-size: var(--sp-font-size-xl); font-weight: 700; margin: 16px 0 8px; color: var(--sp-text-primary);">
            <?php echo esc_html($event->title_ar); ?>
        </h1>
        
        <?php if ($event->is_mandatory): ?>
            <div style="background: var(--sp-warning-light); color: #92400E; padding: 12px 16px; border-radius: var(--sp-radius-md); margin-top: 16px; font-size: var(--sp-font-size-sm);">
                <span class="dashicons dashicons-warning" style="margin-left: 8px;"></span>
                <?php _e('حضور إلزامي - عدم الحضور سيؤدي لخصم نقاط', 'saint-porphyrius'); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($is_user_forbidden): ?>
            <div style="background: #FEE2E2; color: #991B1B; padding: 16px; border-radius: var(--sp-radius-md); margin-top: 16px; text-align: center;">
                <div style="font-size: 32px; margin-bottom: 8px;">⛔</div>
                <div style="font-weight: 600; font-size: var(--sp-font-size-lg);"><?php _e('أنت محروم من هذه الفعالية', 'saint-porphyrius'); ?></div>
                <div style="font-size: var(--sp-font-size-sm); margin-top: 4px;">
                    <?php printf(__('متبقي %d فعاليات للرجوع', 'saint-porphyrius'), $user_forbidden_status->forbidden_remaining); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Event Info Cards -->
    <div class="sp-section">
        <div class="sp-list">
            <!-- Date -->
            <div class="sp-list-item">
                <div class="sp-list-icon" style="background: var(--sp-primary-50); color: var(--sp-primary);">
                    📅
                </div>
                <div class="sp-list-content">
                    <h4 class="sp-list-title"><?php _e('التاريخ', 'saint-porphyrius'); ?></h4>
                    <p class="sp-list-subtitle"><?php echo esc_html(date_i18n('l، j F Y', $event_date)); ?></p>
                </div>
            </div>
            
            <!-- Time -->
            <div class="sp-list-item">
                <div class="sp-list-icon" style="background: var(--sp-secondary); background: rgba(150, 194, 145, 0.15); color: var(--sp-secondary-dark);">
                    ⏰
                </div>
                <div class="sp-list-content">
                    <h4 class="sp-list-title"><?php _e('الوقت', 'saint-porphyrius'); ?></h4>
                    <p class="sp-list-subtitle">
                        <?php echo esc_html($event->start_time); ?>
                        <?php if ($event->end_time): ?>
                            - <?php echo esc_html($event->end_time); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            
            <!-- Location -->
            <?php if ($event->location_name): ?>
            <div class="sp-list-item">
                <div class="sp-list-icon" style="background: rgba(239, 68, 68, 0.1); color: var(--sp-error);">
                    📍
                </div>
                <div class="sp-list-content">
                    <h4 class="sp-list-title"><?php _e('المكان', 'saint-porphyrius'); ?></h4>
                    <p class="sp-list-subtitle"><?php echo esc_html($event->location_name); ?></p>
                    <?php if ($event->location_address): ?>
                        <p class="sp-list-meta"><?php echo esc_html($event->location_address); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Description Section -->
    <?php if ($event->description): ?>
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title"><?php _e('التفاصيل', 'saint-porphyrius'); ?></h3>
        </div>
        <div class="sp-card">
            <p style="margin: 0; line-height: 1.8; color: var(--sp-text-secondary);">
                <?php echo nl2br(esc_html($event->description)); ?>
            </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Points Section -->
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title"><?php _e('النقاط', 'saint-porphyrius'); ?></h3>
        </div>
        <div style="display: grid; grid-template-columns: <?php echo ($event->is_mandatory && $points_config['penalty'] > 0) ? '1fr 1fr' : '1fr'; ?>; gap: var(--sp-space-md);">
            <!-- Reward Points -->
            <div class="sp-card" style="text-align: center; background: var(--sp-success-light); border: none;">
                <div style="font-size: 32px; margin-bottom: 8px;">✓</div>
                <div style="font-size: var(--sp-font-size-2xl); font-weight: 700; color: var(--sp-success);">
                    +<?php echo esc_html($points_config['attendance']); ?>
                </div>
                <div style="font-size: var(--sp-font-size-xs); color: #065F46; margin-top: 4px;">
                    <?php _e('نقطة عند الحضور', 'saint-porphyrius'); ?>
                </div>
            </div>
            
            <!-- Penalty Points -->
            <?php if ($event->is_mandatory && $points_config['penalty'] > 0): ?>
            <div class="sp-card" style="text-align: center; background: var(--sp-error-light); border: none;">
                <div style="font-size: 32px; margin-bottom: 8px;">✗</div>
                <div style="font-size: var(--sp-font-size-2xl); font-weight: 700; color: var(--sp-error);">
                    -<?php echo esc_html($points_config['penalty']); ?>
                </div>
                <div style="font-size: var(--sp-font-size-xs); color: #991B1B; margin-top: 4px;">
                    <?php _e('نقطة عند الغياب', 'saint-porphyrius'); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Expected Attendance Section -->
    <?php 
    $expected_attendance_enabled = isset($event->expected_attendance_enabled) ? $event->expected_attendance_enabled : true;
    if ($expected_attendance_enabled):
        $expected_handler = SP_Expected_Attendance::get_instance();
        $excuses_handler_check = SP_Excuses::get_instance();
        $user_excuse_check = $excuses_handler_check->get_user_excuse($event_id, $user_id);
        $has_approved_excuse = $user_excuse_check && $user_excuse_check->status === 'approved';
        
        $is_registered = $expected_handler->is_registered($event_id, $user_id);
        $user_order = $expected_handler->get_user_order($event_id, $user_id);
        $registrations = $expected_handler->get_event_registrations($event_id);
        $registration_count = count($registrations);
        
        // Check if event is in the past
        $event_datetime = strtotime($event->event_date . ' ' . $event->start_time);
        $is_past_event = $event_datetime < time();
        
        // User can register if not forbidden, not excused, and event is in the future
        $can_register = !$is_user_forbidden && !$has_approved_excuse && !$is_past_event;
    ?>
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title">
                <?php _e('الحضور المتوقع', 'saint-porphyrius'); ?>
                <span class="sp-expected-count" id="sp-expected-count">(<?php echo $registration_count; ?>)</span>
            </h3>
        </div>
        
        <!-- Registration Button -->
        <?php if ($can_register && is_user_logged_in()): ?>
        <div class="sp-card sp-expected-register-card" id="sp-expected-register-section">
            <?php if ($is_registered): ?>
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 40px; height: 40px; background: var(--sp-success-light); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <span style="font-size: 20px;">✓</span>
                        </div>
                        <div>
                            <div style="font-weight: 600; color: var(--sp-success);">
                                <?php _e('أنت مسجل للحضور', 'saint-porphyrius'); ?>
                            </div>
                            <div style="font-size: var(--sp-font-size-sm); color: var(--sp-text-secondary);">
                                <?php printf(__('ترتيبك: #%d', 'saint-porphyrius'), $user_order); ?>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="sp-btn sp-btn-sm sp-btn-outline sp-btn-danger" id="sp-unregister-btn" data-event-id="<?php echo $event_id; ?>">
                        <?php _e('إلغاء', 'saint-porphyrius'); ?>
                    </button>
                </div>
            <?php else: ?>
                <div style="text-align: center;">
                    <p style="margin: 0 0 16px; color: var(--sp-text-secondary);">
                        <?php _e('هل تخطط للحضور؟ سجّل اسمك ليعرف الجميع!', 'saint-porphyrius'); ?>
                    </p>
                    <button type="button" class="sp-btn sp-btn-primary sp-btn-lg sp-btn-block" id="sp-register-btn" data-event-id="<?php echo $event_id; ?>">
                        <span style="margin-left: 8px;">🙋</span>
                        <?php _e('سأحضر إن شاء الله', 'saint-porphyrius'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php elseif (is_user_logged_in() && ($is_user_forbidden || $has_approved_excuse)): ?>
        <div class="sp-card" style="background: var(--sp-background); border: none;">
            <div style="text-align: center; padding: 8px;">
                <?php if ($is_user_forbidden): ?>
                    <span style="color: #991B1B;">⛔ <?php _e('لا يمكنك التسجيل - أنت محروم من هذه الفعالية', 'saint-porphyrius'); ?></span>
                <?php else: ?>
                    <span style="color: #6B21A8;">📝 <?php _e('لا يمكنك التسجيل - لديك اعتذار مقبول', 'saint-porphyrius'); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Registrations List -->
        <div class="sp-card sp-expected-list-card" id="sp-expected-list-container">
            <?php if (empty($registrations)): ?>
                <div class="sp-expected-empty" id="sp-expected-empty">
                    <div style="font-size: 48px; margin-bottom: 12px;">🤷</div>
                    <p style="margin: 0; color: var(--sp-text-secondary);">
                        <?php _e('لم يسجل أحد بعد', 'saint-porphyrius'); ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="sp-expected-list" id="sp-expected-list">
                    <?php foreach ($registrations as $reg): ?>
                        <div class="sp-expected-item <?php echo $reg->user_id == $user_id ? 'is-current-user' : ''; ?>">
                            <div class="sp-expected-order"><?php echo $reg->order_number; ?></div>
                            <div class="sp-expected-info">
                                <div class="sp-expected-name"><?php echo esc_html($reg->display_name_final); ?></div>
                                <div class="sp-expected-time">
                                    <?php echo esc_html(date_i18n('j M - H:i', strtotime($reg->registered_at))); ?>
                                </div>
                            </div>
                            <div class="sp-expected-status">
                                <span class="sp-badge" style="background: <?php echo esc_attr($reg->status_color); ?>20; color: <?php echo esc_attr($reg->status_color); ?>;">
                                    <?php echo esc_html($reg->status_label); ?>
                                </span>
                                <?php if ($reg->has_yellow_card): ?>
                                    <span title="<?php _e('بطاقة صفراء', 'saint-porphyrius'); ?>">🟨</span>
                                <?php endif; ?>
                                <?php if ($reg->has_red_card): ?>
                                    <span title="<?php _e('بطاقة حمراء', 'saint-porphyrius'); ?>">🟥</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- QR Attendance Section (Not for Forbidden Users) -->
    <?php if (!$is_user_forbidden): 
        $attendance_handler = SP_Attendance::get_instance();
        $existing_attendance = $attendance_handler->get($event_id, $user_id);
        $already_attended = $existing_attendance && in_array($existing_attendance->status, array('attended', 'late'));
        
        // Check if event is today for QR availability
        $today = date('Y-m-d');
        $is_event_today = ($event->event_date === $today);
        $is_event_past = ($event->event_date < $today);
        $days_until_event = ($event->event_date > $today) ? (strtotime($event->event_date) - strtotime($today)) / 86400 : 0;
    ?>
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title"><?php _e('تسجيل الحضور', 'saint-porphyrius'); ?></h3>
        </div>
        
        <?php if ($already_attended): ?>
            <div class="sp-card" style="text-align: center; background: var(--sp-success-light); border: none;">
                <div style="font-size: 48px; margin-bottom: 12px;">✓</div>
                <h3 style="color: var(--sp-success); font-weight: 600; margin: 0 0 8px;">
                    <?php _e('تم تسجيل حضورك', 'saint-porphyrius'); ?>
                </h3>
                <p style="color: #065F46; font-size: var(--sp-font-size-sm); margin: 0;">
                    <?php 
                    $status_labels = array(
                        'attended' => __('حاضر', 'saint-porphyrius'),
                        'late' => __('متأخر', 'saint-porphyrius'),
                    );
                    echo sprintf(
                        __('الحالة: %s | %s', 'saint-porphyrius'),
                        $status_labels[$existing_attendance->status],
                        date_i18n('j M Y - H:i', strtotime($existing_attendance->marked_at))
                    );
                    ?>
                </p>
            </div>
        <?php elseif ($is_event_past): ?>
            <!-- Event has passed - QR not available -->
            <div class="sp-card" style="text-align: center; background: var(--sp-background); border: none;">
                <div style="font-size: 48px; margin-bottom: 12px;">📅</div>
                <h3 style="color: var(--sp-text-secondary); font-weight: 600; margin: 0 0 8px;">
                    <?php _e('انتهت هذه الفعالية', 'saint-porphyrius'); ?>
                </h3>
                <p style="color: var(--sp-text-muted); font-size: var(--sp-font-size-sm); margin: 0;">
                    <?php _e('لم يعد رمز الحضور متاحاً', 'saint-porphyrius'); ?>
                </p>
            </div>
        <?php elseif (!$is_event_today): ?>
            <!-- Event is in the future - QR not yet available -->
            <div class="sp-card" style="text-align: center; background: linear-gradient(135deg, var(--sp-primary-50) 0%, #E3F2FD 100%); border: none;">
                <div style="font-size: 48px; margin-bottom: 12px;">⏳</div>
                <h3 style="color: var(--sp-primary); font-weight: 600; margin: 0 0 8px;">
                    <?php _e('رمز الحضور غير متاح حالياً', 'saint-porphyrius'); ?>
                </h3>
                <p style="color: var(--sp-text-secondary); font-size: var(--sp-font-size-sm); margin: 0;">
                    <?php 
                    if ($days_until_event == 1) {
                        _e('سيكون متاحاً غداً يوم الفعالية', 'saint-porphyrius');
                    } else {
                        printf(
                            __('سيكون متاحاً بعد %d أيام (يوم الفعالية فقط)', 'saint-porphyrius'),
                            (int)$days_until_event
                        );
                    }
                    ?>
                </p>
                <div style="margin-top: 16px; padding: 12px; background: white; border-radius: var(--sp-radius-md);">
                    <span style="font-size: var(--sp-font-size-xs); color: var(--sp-text-muted);">
                        <?php _e('تاريخ الفعالية:', 'saint-porphyrius'); ?>
                    </span>
                    <div style="font-weight: 600; color: var(--sp-primary); margin-top: 4px;">
                        <?php echo esc_html(date_i18n('l، j F Y', strtotime($event->event_date))); ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="sp-card" id="sp-qr-attendance-container" style="text-align: center;">
                <div id="sp-qr-init" style="padding: 40px;">
                    <div style="font-size: 48px; margin-bottom: 16px;">📱</div>
                    <p style="color: var(--sp-text-secondary); margin: 0 0 20px;"><?php _e('اضغط للحصول على رمز QR لتسجيل الحضور', 'saint-porphyrius'); ?></p>
                    <button type="button" id="sp-generate-qr-btn" class="sp-btn sp-btn-primary sp-btn-lg">
                        <span class="dashicons dashicons-smartphone" style="margin-left: 8px;"></span>
                        <?php _e('إنشاء رمز QR', 'saint-porphyrius'); ?>
                    </button>
                </div>
                
                <div id="sp-qr-loading" style="padding: 40px; display: none;">
                    <div class="sp-spinner" style="margin: 0 auto 16px;"></div>
                    <p style="color: var(--sp-text-secondary); margin: 0;"><?php _e('جاري تحميل رمز QR...', 'saint-porphyrius'); ?></p>
                </div>
                
                <div id="sp-qr-display" style="display: none;">
                    <div style="background: linear-gradient(135deg, var(--sp-primary-50) 0%, var(--sp-primary-100, #E3F2FD) 100%); padding: 20px; border-radius: var(--sp-radius-lg); margin-bottom: 16px;">
                        <div style="font-size: 24px; margin-bottom: 8px;">📱</div>
                        <p style="margin: 0; color: var(--sp-primary); font-weight: 500; font-size: var(--sp-font-size-sm);">
                            <?php _e('أظهر هذا الرمز للمشرف لتسجيل حضورك', 'saint-porphyrius'); ?>
                        </p>
                    </div>
                    
                    <div id="sp-qr-code-wrapper" style="background: white; padding: 16px; border-radius: var(--sp-radius-lg); display: inline-block; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <img id="sp-qr-code-image" src="" alt="QR Code" style="max-width: 200px; height: auto; display: block;">
                    </div>
                    
                    <div id="sp-qr-timer" style="background: var(--sp-warning-light); padding: 12px 16px; border-radius: var(--sp-radius-md); margin-bottom: 16px;">
                        <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <span class="dashicons dashicons-clock" style="color: #92400E;"></span>
                            <span style="color: #92400E; font-weight: 600;">
                                <?php _e('صالح لمدة:', 'saint-porphyrius'); ?>
                                <span id="sp-qr-countdown" style="font-family: monospace; font-size: var(--sp-font-size-lg);">05:00</span>
                            </span>
                        </div>
                    </div>
                    
                    <button type="button" id="sp-refresh-qr-btn" class="sp-btn sp-btn-outline sp-btn-block" style="display: none;">
                        <span class="dashicons dashicons-update" style="margin-left: 8px;"></span>
                        <?php _e('تجديد رمز QR', 'saint-porphyrius'); ?>
                    </button>
                </div>
                
                <div id="sp-qr-error" style="display: none; padding: 40px;">
                    <div style="font-size: 48px; margin-bottom: 12px;">⚠️</div>
                    <p id="sp-qr-error-message" style="color: var(--sp-error); margin: 0 0 16px;"></p>
                    <button type="button" id="sp-retry-qr-btn" class="sp-btn sp-btn-primary">
                        <?php _e('إعادة المحاولة', 'saint-porphyrius'); ?>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Map Button -->
    <?php if ($has_map_url): ?>
    <div class="sp-section">
        <a href="<?php echo esc_url($event->location_map_url); ?>" 
           target="_blank" 
           class="sp-btn sp-btn-primary sp-btn-block sp-btn-lg">
            <span class="dashicons dashicons-location-alt" style="margin-left: 8px;"></span>
            <?php _e('عرض الموقع على الخريطة', 'saint-porphyrius'); ?>
        </a>
    </div>
    <?php endif; ?>

    <!-- Excuse Section (Mandatory Events Only - Not for Forbidden Users) -->
    <?php if ($event->is_mandatory && !$is_user_forbidden): 
        $excuses_handler = SP_Excuses::get_instance();
        $user_id = get_current_user_id();
        $existing_excuse = $excuses_handler->get_user_excuse($event_id, $user_id);
        $excuse_cost = $excuses_handler->get_excuse_cost($event_id);
        $points_handler = SP_Points::get_instance();
        $user_balance = $points_handler->get_balance($user_id);
    ?>
    <div class="sp-section">
        <?php if ($existing_excuse): ?>
            <!-- Show existing excuse -->
            <div class="sp-section-header">
                <h3 class="sp-section-title"><?php _e('الاعتذار عن الحضور', 'saint-porphyrius'); ?></h3>
            </div>
            <div class="sp-card">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                    <?php 
                    $status_color = SP_Excuses::get_status_color($existing_excuse->status);
                    $status_label = SP_Excuses::get_status_label($existing_excuse->status);
                    ?>
                    <span class="sp-badge" style="background: <?php echo esc_attr($status_color); ?>20; color: <?php echo esc_attr($status_color); ?>;">
                        <?php echo esc_html($status_label); ?>
                    </span>
                    <span style="font-size: var(--sp-font-size-sm); color: var(--sp-text-secondary);">
                        <?php echo esc_html(date_i18n('j F Y', strtotime($existing_excuse->created_at))); ?>
                    </span>
                </div>
                
                <div style="background: var(--sp-background); padding: 12px; border-radius: var(--sp-radius-md); margin-bottom: 12px;">
                    <p style="margin: 0; color: var(--sp-text-secondary); line-height: 1.6;">
                        <?php echo nl2br(esc_html($existing_excuse->excuse_text)); ?>
                    </p>
                </div>
                
                <div style="display: flex; align-items: center; justify-content: space-between; font-size: var(--sp-font-size-sm);">
                    <span style="color: var(--sp-text-secondary);"><?php _e('النقاط المخصومة:', 'saint-porphyrius'); ?></span>
                    <span style="color: var(--sp-error); font-weight: 600;">-<?php echo esc_html($existing_excuse->points_deducted); ?></span>
                </div>
                
                <?php if ($existing_excuse->status === 'denied' && $existing_excuse->admin_notes): ?>
                <div style="background: var(--sp-error-light); padding: 12px; border-radius: var(--sp-radius-md); margin-top: 12px;">
                    <strong style="color: #991B1B; display: block; margin-bottom: 4px;"><?php _e('سبب الرفض:', 'saint-porphyrius'); ?></strong>
                    <p style="margin: 0; color: #991B1B;">
                        <?php echo nl2br(esc_html($existing_excuse->admin_notes)); ?>
                    </p>
                </div>
                <?php endif; ?>
                
                <?php if ($existing_excuse->status === 'approved'): ?>
                <div style="background: var(--sp-success-light); padding: 12px; border-radius: var(--sp-radius-md); margin-top: 12px; text-align: center;">
                    <span class="dashicons dashicons-yes-alt" style="color: var(--sp-success); font-size: 24px;"></span>
                    <p style="margin: 8px 0 0; color: #065F46; font-weight: 600;">
                        <?php _e('تم قبول اعتذارك', 'saint-porphyrius'); ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
            
        <?php elseif ($excuse_cost && $excuse_cost['can_submit']): ?>
            <!-- Accordion Header - Clickable -->
            <div class="sp-excuse-accordion">
                <button type="button" class="sp-excuse-accordion-header" id="sp-excuse-toggle">
                    <div class="sp-excuse-accordion-title">
                        <span class="sp-excuse-accordion-icon">📝</span>
                        <span><?php _e('لا تستطيع الحضور؟', 'saint-porphyrius'); ?></span>
                    </div>
                    <svg class="sp-excuse-accordion-arrow" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                
                <!-- Accordion Content - Hidden by default -->
                <div class="sp-excuse-accordion-content" id="sp-excuse-content" style="display: none;">
                    <div class="sp-card" style="margin-top: 12px; border-top: none; border-radius: 0 0 var(--sp-radius-lg) var(--sp-radius-lg);">
                        <!-- Cost info -->
                        <div style="background: var(--sp-warning-light); padding: 16px; border-radius: var(--sp-radius-md); margin-bottom: 20px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                                <span style="font-weight: 600; color: #92400E;"><?php _e('تكلفة الاعتذار', 'saint-porphyrius'); ?></span>
                                <span style="font-size: var(--sp-font-size-lg); font-weight: 700; color: #92400E;">
                                    -<?php echo esc_html($excuse_cost['cost']); ?> <?php _e('نقطة', 'saint-porphyrius'); ?>
                                </span>
                            </div>
                            <p style="margin: 0; font-size: var(--sp-font-size-sm); color: #92400E;">
                                <?php 
                                $days = $excuse_cost['days_before'];
                                if ($days >= 7) {
                                    printf(__('باقي %d أيام على الفعالية', 'saint-porphyrius'), $days);
                                } elseif ($days > 1) {
                                    printf(__('باقي %d أيام على الفعالية', 'saint-porphyrius'), $days);
                                } elseif ($days == 1) {
                                    _e('باقي يوم واحد على الفعالية', 'saint-porphyrius');
                                } else {
                                    _e('اليوم هو يوم الفعالية', 'saint-porphyrius');
                                }
                                ?>
                            </p>
                        </div>
                        
                        <!-- User balance -->
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding: 12px; background: var(--sp-background); border-radius: var(--sp-radius-md);">
                            <span style="color: var(--sp-text-secondary);"><?php _e('رصيدك الحالي:', 'saint-porphyrius'); ?></span>
                            <span style="font-weight: 600; color: <?php echo $user_balance >= $excuse_cost['cost'] ? 'var(--sp-success)' : 'var(--sp-error)'; ?>;">
                                <?php echo esc_html($user_balance); ?> <?php _e('نقطة', 'saint-porphyrius'); ?>
                            </span>
                        </div>
                        
                        <?php if ($user_balance < $excuse_cost['cost']): ?>
                            <div style="background: var(--sp-error-light); padding: 12px; border-radius: var(--sp-radius-md); text-align: center;">
                                <span class="dashicons dashicons-warning" style="color: var(--sp-error);"></span>
                                <p style="margin: 8px 0 0; color: #991B1B;">
                                    <?php _e('رصيدك غير كافٍ لتقديم اعتذار', 'saint-porphyrius'); ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <!-- Excuse form -->
                            <form id="sp-excuse-form" class="sp-form">
                                <input type="hidden" name="action" value="sp_submit_excuse">
                                <input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>">
                                <?php wp_nonce_field('sp_submit_excuse', 'sp_excuse_nonce'); ?>
                                
                                <div class="sp-form-group">
                                    <label for="excuse_text" class="sp-form-label"><?php _e('سبب الاعتذار', 'saint-porphyrius'); ?></label>
                                    <textarea 
                                        name="excuse_text" 
                                        id="excuse_text" 
                                        class="sp-excuse-textarea" 
                                        rows="4" 
                                        placeholder="<?php _e('اكتب سبب عدم قدرتك على الحضور...', 'saint-porphyrius'); ?>"
                                        required
                                    ></textarea>
                                </div>
                                
                                <div style="background: var(--sp-background); padding: 12px; border-radius: var(--sp-radius-md); margin-bottom: 16px; font-size: var(--sp-font-size-sm);">
                                    <span class="dashicons dashicons-info" style="color: var(--sp-warning); margin-left: 4px;"></span>
                                    <?php _e('ملاحظة: في حالة رفض الاعتذار سيتم خصم ضعف النقاط المدفوعة', 'saint-porphyrius'); ?>
                                </div>
                                
                                <button type="button" class="sp-btn sp-btn-primary sp-btn-block sp-btn-lg" id="sp-show-excuse-modal-btn">
                                    <?php printf(__('تقديم الاعتذار (-%d نقطة)', 'saint-porphyrius'), $excuse_cost['cost']); ?>
                                </button>
                            </form>
                            
                            <!-- Success/Error message placeholder -->
                            <div id="sp-excuse-message" style="display: none; margin-top: 16px; padding: 12px; border-radius: var(--sp-radius-md);"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Confirmation Modal -->
            <div id="sp-excuse-confirm-modal" class="sp-modal-overlay" style="display: none;">
                <div class="sp-modal-container">
                    <div class="sp-modal-header" style="text-align: center; padding: 24px 20px 16px;">
                        <div style="font-size: 64px; margin-bottom: 12px;">😔</div>
                        <h3 style="margin: 0; font-size: var(--sp-font-size-lg); color: var(--sp-text-primary);">
                            <?php _e('هل أنت متأكد؟', 'saint-porphyrius'); ?>
                        </h3>
                    </div>
                    <div class="sp-modal-body" style="padding: 0 20px 20px; text-align: center;">
                        <p style="color: var(--sp-text-secondary); line-height: 1.8; margin: 0 0 16px;">
                            <?php _e('حضورك مهم جداً لنا ولجميع الأعضاء!', 'saint-porphyrius'); ?>
                            <br>
                            <?php _e('نحن نتطلع لرؤيتك في الفعالية.', 'saint-porphyrius'); ?>
                        </p>
                        
                        <div style="background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); padding: 16px; border-radius: var(--sp-radius-lg); margin-bottom: 20px;">
                            <div style="font-size: 28px; margin-bottom: 8px;">⛪</div>
                            <p style="margin: 0; color: #92400E; font-weight: 500; font-size: var(--sp-font-size-sm);">
                                <?php _e('"اجْتِمَاعُنَا مَعًا كَمَا جَرَتِ الْعَادَةُ عِنْدَ قَوْمٍ"', 'saint-porphyrius'); ?>
                                <br>
                                <span style="font-size: var(--sp-font-size-xs); opacity: 0.8;"><?php _e('عبرانيين ١٠: ٢٥', 'saint-porphyrius'); ?></span>
                            </p>
                        </div>
                        
                        <div style="background: var(--sp-error-light); padding: 12px; border-radius: var(--sp-radius-md); margin-bottom: 20px;">
                            <p style="margin: 0; color: #991B1B; font-size: var(--sp-font-size-sm);">
                                <span class="dashicons dashicons-warning" style="font-size: 16px; width: 16px; height: 16px; margin-left: 4px;"></span>
                                <?php printf(__('سيتم خصم %d نقطة من رصيدك', 'saint-porphyrius'), $excuse_cost['cost']); ?>
                            </p>
                        </div>
                        
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <button type="button" id="sp-cancel-excuse-btn" class="sp-btn sp-btn-primary sp-btn-block sp-btn-lg">
                                <?php _e('سأحاول الحضور 💪', 'saint-porphyrius'); ?>
                            </button>
                            <button type="button" id="sp-confirm-excuse-btn" class="sp-btn sp-btn-outline sp-btn-block" style="color: var(--sp-text-secondary); border-color: var(--sp-border);">
                                <?php _e('تأكيد الاعتذار', 'saint-porphyrius'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php elseif ($excuse_cost && !$excuse_cost['can_submit']): ?>
            <!-- Cannot submit excuse -->
            <div class="sp-card">
                <div style="text-align: center; padding: 20px;">
                    <span class="dashicons dashicons-no-alt" style="font-size: 48px; color: var(--sp-text-tertiary);"></span>
                    <p style="margin: 12px 0 0; color: var(--sp-text-secondary);">
                        <?php echo esc_html($excuse_cost['message']); ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</main>

<script>
jQuery(document).ready(function($) {
    var $form = $('#sp-excuse-form');
    var $modal = $('#sp-excuse-confirm-modal');
    var $message = $('#sp-excuse-message');
    
    // Accordion toggle
    $('#sp-excuse-toggle').on('click', function() {
        var $content = $('#sp-excuse-content');
        var $arrow = $(this).find('.sp-excuse-accordion-arrow');
        
        $content.slideToggle(250);
        $(this).toggleClass('is-open');
        $arrow.toggleClass('rotated');
    });
    
    // Show modal when clicking submit button
    $('#sp-show-excuse-modal-btn').on('click', function() {
        var excuseText = $('#excuse_text').val().trim();
        if (!excuseText) {
            $('#excuse_text').focus();
            return;
        }
        $modal.fadeIn(200);
    });
    
    // Cancel - close modal
    $('#sp-cancel-excuse-btn').on('click', function() {
        $modal.fadeOut(200);
    });
    
    // Close modal on overlay click
    $modal.on('click', function(e) {
        if ($(e.target).is('.sp-modal-overlay')) {
            $modal.fadeOut(200);
        }
    });
    
    // Confirm and submit
    $('#sp-confirm-excuse-btn').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php _e('جاري الإرسال...', 'saint-porphyrius'); ?>');
        $message.hide();
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: $form.serialize(),
            success: function(response) {
                $modal.fadeOut(200);
                
                if (response.success) {
                    $message.removeClass('sp-alert-error').addClass('sp-alert-success')
                        .css({
                            'background': 'var(--sp-success-light)',
                            'color': '#065F46'
                        })
                        .html('<span class="dashicons dashicons-yes-alt" style="margin-left: 8px;"></span>' + response.data.message)
                        .show();
                    
                    // Hide form and reload after 2 seconds
                    $form.hide();
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $message.removeClass('sp-alert-success').addClass('sp-alert-error')
                        .css({
                            'background': 'var(--sp-error-light)',
                            'color': '#991B1B'
                        })
                        .html('<span class="dashicons dashicons-warning" style="margin-left: 8px;"></span>' + response.data.message)
                        .show();
                    
                    $btn.prop('disabled', false).text('<?php _e('تأكيد الاعتذار', 'saint-porphyrius'); ?>');
                }
            },
            error: function() {
                $modal.fadeOut(200);
                $message.removeClass('sp-alert-success').addClass('sp-alert-error')
                    .css({
                        'background': 'var(--sp-error-light)',
                        'color': '#991B1B'
                    })
                    .html('<span class="dashicons dashicons-warning" style="margin-left: 8px;"></span><?php _e('حدث خطأ، يرجى المحاولة مرة أخرى', 'saint-porphyrius'); ?>')
                    .show();
                
                $btn.prop('disabled', false).text('<?php _e('تأكيد الاعتذار', 'saint-porphyrius'); ?>');
            }
        });
    });
    
    // QR Attendance System
    var eventId = <?php echo intval($event_id); ?>;
    var qrTimer = null;
    var qrExpiresAt = null;
    
    function formatTime(seconds) {
        var mins = Math.floor(seconds / 60);
        var secs = seconds % 60;
        return String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
    }
    
    function updateQRTimer() {
        if (!qrExpiresAt) return;
        
        var now = Math.floor(Date.now() / 1000);
        var remaining = qrExpiresAt - now;
        
        if (remaining <= 0) {
            clearInterval(qrTimer);
            $('#sp-qr-countdown').text('00:00');
            $('#sp-qr-timer').css('background', 'var(--sp-error-light)');
            $('#sp-qr-timer span').css('color', '#991B1B');
            $('#sp-refresh-qr-btn').show();
            return;
        }
        
        $('#sp-qr-countdown').text(formatTime(remaining));
        
        // Change color when less than 60 seconds
        if (remaining <= 60) {
            $('#sp-qr-timer').css('background', 'var(--sp-error-light)');
            $('#sp-qr-timer span').css('color', '#991B1B');
        }
    }
    
    function generateQRCode() {
        $('#sp-qr-init').hide();
        $('#sp-qr-loading').show();
        $('#sp-qr-display').hide();
        $('#sp-qr-error').hide();
        $('#sp-refresh-qr-btn').hide();
        
        if (qrTimer) clearInterval(qrTimer);
        
        $.ajax({
            url: spApp.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sp_generate_qr_token',
                nonce: spApp.nonce,
                event_id: eventId
            },
            success: function(response) {
                if (response.success) {
                    // Generate QR code using the qr_content
                    var qrContent = response.data.qr_content;
                    var qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(qrContent);
                    
                    $('#sp-qr-code-image').attr('src', qrImageUrl);
                    $('#sp-qr-loading').hide();
                    $('#sp-qr-display').show();
                    
                    // Reset timer styling
                    $('#sp-qr-timer').css('background', 'var(--sp-warning-light)');
                    $('#sp-qr-timer span').css('color', '#92400E');
                    
                    // Set up timer
                    qrExpiresAt = Math.floor(Date.now() / 1000) + response.data.expires_in;
                    updateQRTimer();
                    qrTimer = setInterval(updateQRTimer, 1000);
                } else {
                    if (response.data.already_attended) {
                        // Reload page to show attendance status
                        location.reload();
                    } else {
                        showQRError(response.data.message);
                    }
                }
            },
            error: function() {
                showQRError('<?php _e('حدث خطأ في الاتصال، يرجى المحاولة مرة أخرى', 'saint-porphyrius'); ?>');
            }
        });
    }
    
    function showQRError(message) {
        $('#sp-qr-loading').hide();
        $('#sp-qr-display').hide();
        $('#sp-qr-error').show();
        $('#sp-qr-error-message').text(message);
    }
    
    // Generate QR code on button click
    $('#sp-generate-qr-btn, #sp-refresh-qr-btn, #sp-retry-qr-btn').on('click', function() {
        generateQRCode();
    });
    
    // Expected Attendance System
    function updateExpectedAttendanceUI(data) {
        var $count = $('#sp-expected-count');
        var $list = $('#sp-expected-list');
        var $empty = $('#sp-expected-empty');
        var $container = $('#sp-expected-list-container');
        var currentUserId = <?php echo get_current_user_id(); ?>;
        
        // Update count
        $count.text('(' + data.count + ')');
        
        // Rebuild list
        if (data.registrations && data.registrations.length > 0) {
            var html = '<div class="sp-expected-list" id="sp-expected-list">';
            data.registrations.forEach(function(reg) {
                var isCurrentUser = reg.user_id == currentUserId;
                html += '<div class="sp-expected-item ' + (isCurrentUser ? 'is-current-user' : '') + '">';
                html += '<div class="sp-expected-order">' + reg.order_number + '</div>';
                html += '<div class="sp-expected-info">';
                html += '<div class="sp-expected-name">' + escapeHtml(reg.display_name_final) + '</div>';
                html += '<div class="sp-expected-time">' + formatDate(reg.registered_at) + '</div>';
                html += '</div>';
                html += '<div class="sp-expected-status">';
                html += '<span class="sp-badge" style="background: ' + reg.status_color + '20; color: ' + reg.status_color + ';">' + reg.status_label + '</span>';
                if (reg.has_yellow_card) html += ' <span title="<?php _e('بطاقة صفراء', 'saint-porphyrius'); ?>">🟨</span>';
                if (reg.has_red_card) html += ' <span title="<?php _e('بطاقة حمراء', 'saint-porphyrius'); ?>">🟥</span>';
                html += '</div>';
                html += '</div>';
            });
            html += '</div>';
            
            $container.html(html);
        } else {
            $container.html(
                '<div class="sp-expected-empty" id="sp-expected-empty">' +
                '<div style="font-size: 48px; margin-bottom: 12px;">🤷</div>' +
                '<p style="margin: 0; color: var(--sp-text-secondary);"><?php _e('لم يسجل أحد بعد', 'saint-porphyrius'); ?></p>' +
                '</div>'
            );
        }
        
        // Update register section
        var $registerSection = $('#sp-expected-register-section');
        if (data.is_registered && data.user_order) {
            $registerSection.html(
                '<div style="display: flex; align-items: center; justify-content: space-between;">' +
                '<div style="display: flex; align-items: center; gap: 12px;">' +
                '<div style="width: 40px; height: 40px; background: var(--sp-success-light); border-radius: 50%; display: flex; align-items: center; justify-content: center;">' +
                '<span style="font-size: 20px;">✓</span>' +
                '</div>' +
                '<div>' +
                '<div style="font-weight: 600; color: var(--sp-success);"><?php _e('أنت مسجل للحضور', 'saint-porphyrius'); ?></div>' +
                '<div style="font-size: var(--sp-font-size-sm); color: var(--sp-text-secondary);"><?php _e('ترتيبك:', 'saint-porphyrius'); ?> #' + data.user_order + '</div>' +
                '</div>' +
                '</div>' +
                '<button type="button" class="sp-btn sp-btn-sm sp-btn-outline sp-btn-danger" id="sp-unregister-btn" data-event-id="' + eventId + '"><?php _e('إلغاء', 'saint-porphyrius'); ?></button>' +
                '</div>'
            );
        } else if ($registerSection.length) {
            $registerSection.html(
                '<div style="text-align: center;">' +
                '<p style="margin: 0 0 16px; color: var(--sp-text-secondary);"><?php _e('هل تخطط للحضور؟ سجّل اسمك ليعرف الجميع!', 'saint-porphyrius'); ?></p>' +
                '<button type="button" class="sp-btn sp-btn-primary sp-btn-lg sp-btn-block" id="sp-register-btn" data-event-id="' + eventId + '">' +
                '<span style="margin-left: 8px;">🙋</span>' +
                '<?php _e('سأحضر إن شاء الله', 'saint-porphyrius'); ?>' +
                '</button>' +
                '</div>'
            );
        }
    }
    
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function formatDate(dateStr) {
        var date = new Date(dateStr);
        var months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
        var day = date.getDate();
        var month = months[date.getMonth()];
        var hours = String(date.getHours()).padStart(2, '0');
        var minutes = String(date.getMinutes()).padStart(2, '0');
        return day + ' ' + month + ' - ' + hours + ':' + minutes;
    }
    
    // Register for expected attendance
    $(document).on('click', '#sp-register-btn', function() {
        var $btn = $(this);
        var eventId = $btn.data('event-id');
        
        $btn.prop('disabled', true).html('<span class="sp-spinner-sm"></span> <?php _e('جاري التسجيل...', 'saint-porphyrius'); ?>');
        
        $.ajax({
            url: spApp.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sp_register_expected_attendance',
                nonce: spApp.nonce,
                event_id: eventId
            },
            success: function(response) {
                if (response.success) {
                    updateExpectedAttendanceUI(response.data);
                } else {
                    alert(response.data.message);
                    $btn.prop('disabled', false).html('<span style="margin-left: 8px;">🙋</span> <?php _e('سأحضر إن شاء الله', 'saint-porphyrius'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('حدث خطأ، يرجى المحاولة مرة أخرى', 'saint-porphyrius'); ?>');
                $btn.prop('disabled', false).html('<span style="margin-left: 8px;">🙋</span> <?php _e('سأحضر إن شاء الله', 'saint-porphyrius'); ?>');
            }
        });
    });
    
    // Unregister from expected attendance
    $(document).on('click', '#sp-unregister-btn', function() {
        var $btn = $(this);
        var eventId = $btn.data('event-id');
        
        if (!confirm('<?php _e('هل أنت متأكد من إلغاء التسجيل؟', 'saint-porphyrius'); ?>')) {
            return;
        }
        
        $btn.prop('disabled', true).text('<?php _e('جاري...', 'saint-porphyrius'); ?>');
        
        $.ajax({
            url: spApp.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sp_unregister_expected_attendance',
                nonce: spApp.nonce,
                event_id: eventId
            },
            success: function(response) {
                if (response.success) {
                    updateExpectedAttendanceUI(response.data);
                } else {
                    alert(response.data.message);
                    $btn.prop('disabled', false).text('<?php _e('إلغاء', 'saint-porphyrius'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('حدث خطأ، يرجى المحاولة مرة أخرى', 'saint-porphyrius'); ?>');
                $btn.prop('disabled', false).text('<?php _e('إلغاء', 'saint-porphyrius'); ?>');
            }
        });
    });
});
</script>

<style>
/* Spinner */
.sp-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid var(--sp-border);
    border-top-color: var(--sp-primary);
    border-radius: 50%;
    animation: sp-spin 0.8s linear infinite;
}

@keyframes sp-spin {
    to { transform: rotate(360deg); }
}

/* Accordion Styles */
.sp-excuse-accordion {
    border-radius: var(--sp-radius-lg);
    overflow: hidden;
}

.sp-excuse-accordion-header {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    background: var(--sp-card-bg, white);
    border: 1px solid var(--sp-border);
    border-radius: var(--sp-radius-lg);
    cursor: pointer;
    transition: all 0.2s ease;
    font-family: inherit;
    font-size: var(--sp-font-size-base);
}

.sp-excuse-accordion-header:hover {
    background: var(--sp-background);
}

.sp-excuse-accordion-header.is-open {
    border-radius: var(--sp-radius-lg) var(--sp-radius-lg) 0 0;
    border-bottom-color: transparent;
}

.sp-excuse-accordion-title {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--sp-text-secondary);
    font-weight: 500;
}

.sp-excuse-accordion-icon {
    font-size: 20px;
}

.sp-excuse-accordion-arrow {
    color: var(--sp-text-tertiary);
    transition: transform 0.25s ease;
}

.sp-excuse-accordion-arrow.rotated {
    transform: rotate(180deg);
}

.sp-excuse-accordion-content .sp-card {
    border-top: 1px solid var(--sp-border);
    border-radius: 0 0 var(--sp-radius-lg) var(--sp-radius-lg);
    margin-top: 0 !important;
}

/* Textarea Styles */
.sp-excuse-textarea {
    width: 100%;
    padding: 14px 16px;
    border: 1px solid var(--sp-border);
    border-radius: var(--sp-radius-md);
    font-family: inherit;
    font-size: var(--sp-font-size-base);
    line-height: 1.6;
    resize: vertical;
    background: var(--sp-card-bg, white);
    color: var(--sp-text-primary);
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    direction: rtl;
}

.sp-excuse-textarea:focus {
    outline: none;
    border-color: var(--sp-primary);
    box-shadow: 0 0 0 3px rgba(108, 155, 207, 0.15);
}

.sp-excuse-textarea::placeholder {
    color: var(--sp-text-tertiary);
}

/* Modal Styles */
.sp-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.sp-modal-container {
    background: white;
    border-radius: var(--sp-radius-xl, 16px);
    max-width: 360px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: modalSlideUp 0.3s ease;
}

@keyframes modalSlideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Expected Attendance Styles */
.sp-expected-count {
    font-weight: 400;
    color: var(--sp-text-secondary);
    font-size: var(--sp-font-size-sm);
}

.sp-expected-register-card {
    margin-bottom: var(--sp-space-md);
}

.sp-expected-list-card {
    padding: 0;
    overflow: hidden;
}

.sp-expected-empty {
    text-align: center;
    padding: 32px 20px;
}

.sp-expected-list {
    display: flex;
    flex-direction: column;
}

.sp-expected-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border-bottom: 1px solid var(--sp-border);
    transition: background 0.2s ease;
}

.sp-expected-item:last-child {
    border-bottom: none;
}

.sp-expected-item.is-current-user {
    background: var(--sp-primary-50, rgba(108, 155, 207, 0.1));
}

.sp-expected-order {
    width: 32px;
    height: 32px;
    background: var(--sp-background);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: var(--sp-font-size-sm);
    color: var(--sp-text-secondary);
    flex-shrink: 0;
}

.sp-expected-item.is-current-user .sp-expected-order {
    background: var(--sp-primary);
    color: white;
}

.sp-expected-info {
    flex: 1;
    min-width: 0;
}

.sp-expected-name {
    font-weight: 500;
    color: var(--sp-text-primary);
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sp-expected-time {
    font-size: var(--sp-font-size-xs);
    color: var(--sp-text-tertiary);
}

.sp-expected-status {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
}

.sp-expected-status .sp-badge {
    font-size: var(--sp-font-size-xs);
    padding: 4px 8px;
}

/* Small spinner for buttons */
.sp-spinner-sm {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: sp-spin 0.8s linear infinite;
    vertical-align: middle;
    margin-left: 8px;
}

/* Button danger variant */
.sp-btn-danger {
    color: var(--sp-error) !important;
    border-color: var(--sp-error) !important;
}

.sp-btn-danger:hover {
    background: var(--sp-error-light) !important;
}
</style>

<!-- Unified Bottom Navigation -->
<nav class="sp-unified-nav">
    <div class="sp-nav-inner">
        <a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-nav-item">
            <div class="sp-nav-icon">
                <span class="dashicons dashicons-dashboard"></span>
            </div>
            <span class="sp-nav-label"><?php _e('الرئيسية', 'saint-porphyrius'); ?></span>
        </a>
        <a href="<?php echo home_url('/app/events'); ?>" class="sp-nav-item active">
            <div class="sp-nav-indicator"></div>
            <div class="sp-nav-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <span class="sp-nav-label"><?php _e('الفعاليات', 'saint-porphyrius'); ?></span>
        </a>
        <a href="<?php echo home_url('/app/points'); ?>" class="sp-nav-item">
            <div class="sp-nav-icon">
                <span class="dashicons dashicons-star-filled"></span>
            </div>
            <span class="sp-nav-label"><?php _e('نقاطي', 'saint-porphyrius'); ?></span>
        </a>
        <a href="<?php echo home_url('/app/leaderboard'); ?>" class="sp-nav-item">
            <div class="sp-nav-icon">
                <span class="dashicons dashicons-awards"></span>
            </div>
            <span class="sp-nav-label"><?php _e('المتصدرين', 'saint-porphyrius'); ?></span>
        </a>
        <a href="<?php echo home_url('/app/profile'); ?>" class="sp-nav-item">
            <div class="sp-nav-icon">
                <span class="dashicons dashicons-admin-users"></span>
            </div>
            <span class="sp-nav-label"><?php _e('حسابي', 'saint-porphyrius'); ?></span>
        </a>
    </div>
</nav>
