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

// Check if admin viewing draft
$is_admin = current_user_can('manage_options');
$is_draft = $event->status === 'draft';

// Non-admins cannot view draft events
if ($is_draft && !$is_admin) {
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

// Bus booking data
$bus_booking_enabled = isset($event->bus_booking_enabled) ? $event->bus_booking_enabled : false;
$bus_handler = null;
$event_buses = array();
$user_bus_booking = null;

if ($bus_booking_enabled) {
    $bus_handler = SP_Bus::get_instance();
    $event_buses = $bus_handler->get_event_buses($event_id, true);
    $user_bus_booking = $bus_handler->get_user_event_booking($event_id, $user_id);
}
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
    <?php if ($is_draft && $is_admin): ?>
    <!-- Draft Warning Banner -->
    <div style="background: linear-gradient(135deg, #F59E0B, #D97706); color: white; padding: 16px; text-align: center; margin-bottom: var(--sp-space-md); border-radius: var(--sp-radius-md); box-shadow: var(--sp-shadow-md);">
        <div style="display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 700; font-size: var(--sp-font-size-lg);">
            <span>📝</span>
            <span><?php _e('مسودة - هذه الفعالية مرئية للمشرفين فقط', 'saint-porphyrius'); ?></span>
        </div>
        <div style="font-size: var(--sp-font-size-sm); margin-top: 4px; opacity: 0.9;">
            <?php _e('يجب نشر الفعالية لتظهر للأعضاء', 'saint-porphyrius'); ?>
        </div>
    </div>
    <?php endif; ?>
    
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

    <?php 
    // Check if event is in the past (used by multiple sections)
    $event_datetime = strtotime($event->event_date . ' ' . $event->start_time);
    $is_past_event = $event_datetime < time();
    ?>

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

    <!-- Bus Booking Section -->
    <?php if ($bus_booking_enabled && !empty($event_buses) && !$is_user_forbidden && !$is_past_event): ?>
    <div class="sp-section sp-bus-booking-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title">
                🚌 <?php _e('حجز مقعد في الباص', 'saint-porphyrius'); ?>
            </h3>
        </div>
        
        <?php if ($user_bus_booking): ?>
        <!-- User Already Has Booking -->
        <div class="sp-card sp-bus-booking-confirmed">
            <div class="sp-bus-booking-header">
                <div class="sp-bus-booking-icon" style="background: <?php echo esc_attr($user_bus_booking->color); ?>20; color: <?php echo esc_attr($user_bus_booking->color); ?>;">
                    <?php echo esc_html($user_bus_booking->icon); ?>
                </div>
                <div class="sp-bus-booking-info">
                    <h4><?php printf(__('باص %d - %s', 'saint-porphyrius'), $user_bus_booking->bus_number, $user_bus_booking->template_name); ?></h4>
                    <div class="sp-bus-booking-seat">
                        <span class="sp-seat-label"><?php _e('مقعدك:', 'saint-porphyrius'); ?></span>
                        <span class="sp-seat-number"><?php echo esc_html($user_bus_booking->seat_label); ?></span>
                    </div>
                </div>
            </div>
            <?php if ($user_bus_booking->departure_time || $user_bus_booking->departure_location): ?>
            <div class="sp-bus-booking-details">
                <?php if ($user_bus_booking->departure_time): ?>
                <div class="sp-bus-detail-item">
                    <span class="sp-bus-detail-icon">🕐</span>
                    <span><?php printf(__('وقت الانطلاق: %s', 'saint-porphyrius'), esc_html($user_bus_booking->departure_time)); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($user_bus_booking->departure_location): ?>
                <div class="sp-bus-detail-item">
                    <span class="sp-bus-detail-icon">📍</span>
                    <span><?php echo esc_html($user_bus_booking->departure_location); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <button type="button" class="sp-btn sp-btn-outline sp-btn-danger sp-btn-block sp-cancel-bus-booking" 
                    data-booking-id="<?php echo esc_attr($user_bus_booking->id); ?>">
                <?php _e('إلغاء الحجز', 'saint-porphyrius'); ?>
            </button>
        </div>
        <?php else: ?>
        <!-- Bus Selection -->
        <div class="sp-bus-selection-container">
            <p class="sp-bus-selection-intro">
                <?php _e('اختر الباص ثم اختر مقعدك المفضل', 'saint-porphyrius'); ?>
            </p>
            
            <!-- Bus Tabs -->
            <div class="sp-bus-tabs">
                <?php foreach ($event_buses as $index => $bus): ?>
                <button type="button" class="sp-bus-tab <?php echo $index === 0 ? 'active' : ''; ?>" 
                        data-bus-id="<?php echo esc_attr($bus->id); ?>">
                    <span class="sp-bus-tab-icon" style="color: <?php echo esc_attr($bus->color); ?>;"><?php echo esc_html($bus->icon); ?></span>
                    <span class="sp-bus-tab-label"><?php printf(__('باص %d', 'saint-porphyrius'), $bus->bus_number); ?></span>
                    <span class="sp-bus-tab-seats <?php echo $bus->available_seats == 0 ? 'full' : ''; ?>">
                        <?php echo $bus->available_seats; ?>/<?php echo $bus->capacity; ?>
                    </span>
                </button>
                <?php endforeach; ?>
            </div>
            
            <!-- Bus Seat Maps -->
            <?php foreach ($event_buses as $index => $bus): 
                $seat_map = $bus_handler->get_seat_map($bus->id);
            ?>
            <div class="sp-bus-seat-map-container <?php echo $index === 0 ? 'active' : ''; ?>" 
                 data-bus-id="<?php echo esc_attr($bus->id); ?>">
                
                <!-- Bus Info -->
                <div class="sp-bus-info-bar">
                    <span style="color: <?php echo esc_attr($bus->color); ?>;"><?php echo esc_html($bus->icon); ?></span>
                    <span><?php echo esc_html($bus->template_name_ar); ?></span>
                    <?php if ($bus->departure_time): ?>
                    <span class="sp-bus-time">🕐 <?php echo esc_html($bus->departure_time); ?></span>
                    <?php endif; ?>
                </div>
                
                <!-- Bus Visual Layout - International Standard -->
                <div class="sp-bus-visual" style="--bus-color: <?php echo esc_attr($bus->color); ?>;">
                    <!-- Bus Front -->
                    <div class="sp-bus-front">
                        <span class="sp-bus-icon"><?php echo esc_html($bus->icon); ?></span>
                    </div>
                    
                    <?php 
                    $booked_seats = array();
                    foreach ($seat_map['booked_seats'] as $seat_key => $booked) {
                        $booked_seats[$seat_key] = $booked;
                    }
                    $driver_seats = $seat_map['driver_seats'] ?? 1;
                    $back_row_extra = $seat_map['back_row_extra'] ?? 1;
                    $back_row_seats = $seat_map['back_row_seats'] ?? ($seat_map['seats_per_row'] + 1);
                    $total_rows = $seat_map['rows'] + 2; // +1 driver, +1 back
                    
                    // Get blocked seats - get from seat_map which is properly parsed in SP_Bus class
                    $blocked_seats = isset($seat_map['blocked_seats']) && is_array($seat_map['blocked_seats']) 
                        ? $seat_map['blocked_seats'] 
                        : array();
                    ?>
                    
                    <!-- Driver Row (Row 1) - Driver on left, passenger seats on right -->
                    <div class="sp-bus-row sp-driver-row">
                        <div class="sp-row-label">1</div>
                        <div class="sp-row-seats" style="grid-template-columns: repeat(<?php echo $seat_map['seats_per_row']; ?>, 1fr);">
                            <?php 
                            // Number of passenger seats in driver row (driver_seats - 1 for the driver itself)
                            $passenger_count = max(0, $driver_seats - 1);
                            
                            for ($s = 1; $s <= $seat_map['seats_per_row']; $s++):
                                if ($passenger_count > 0 && $s <= $passenger_count):
                                    // Passenger seats on right side (first positions)
                                    $key = '1_' . $s;
                                    $is_booked = isset($booked_seats[$key]);
                                    $seat_label = $bus_handler->generate_seat_label(1, $s, $seat_map['aisle_position']);
                                    $is_blocked = in_array($seat_label, $blocked_seats);
                            ?>
                            <?php if ($is_blocked): ?>
                            <div class="sp-bus-seat blocked-seat" title="<?php _e('محظور', 'saint-porphyrius'); ?>">
                                <span class="sp-seat-label" style="text-decoration: line-through;"><?php echo esc_html($seat_label); ?></span>
                                <span class="sp-seat-blocked-icon">🚫</span>
                            </div>
                            <?php else: ?>
                            <button type="button" 
                                    class="sp-bus-seat <?php echo $is_booked ? 'booked' : 'available'; ?>"
                                    data-bus-id="<?php echo esc_attr($bus->id); ?>"
                                    data-row="1"
                                    data-seat="<?php echo esc_attr($s); ?>"
                                    data-label="<?php echo esc_attr($seat_label); ?>"
                                    <?php echo $is_booked ? 'disabled' : ''; ?>
                                    title="<?php echo $is_booked ? esc_attr($booked_seats[$key]['user_name'] ?? __('محجوز', 'saint-porphyrius')) : esc_attr($seat_label); ?>">
                                <span class="sp-seat-label"><?php echo esc_html($seat_label); ?></span>
                                <?php if ($is_booked): ?>
                                <span class="sp-seat-occupant">👤</span>
                                <?php endif; ?>
                            </button>
                            <?php endif; ?>
                            <?php 
                                elseif ($s === $seat_map['seats_per_row']):
                                    // Driver seat on left (last position)
                            ?>
                            <div class="sp-bus-seat driver" title="<?php _e('السائق', 'saint-porphyrius'); ?>">
                                <span class="sp-seat-icon">👨‍✈️</span>
                            </div>
                            <?php 
                                else:
                                    // Empty space in between
                            ?>
                            <div class="sp-seat-empty-space"></div>
                            <?php 
                                endif;
                            endfor; 
                            ?>
                        </div>
                    </div>
                    
                    <!-- Regular Rows (Row 2 to rows+1) -->
                    <div class="sp-bus-seats">
                        <?php for ($row = 2; $row <= $seat_map['rows'] + 1; $row++): ?>
                        <div class="sp-bus-row">
                            <div class="sp-row-label"><?php echo $row; ?></div>
                            <div class="sp-row-seats" style="grid-template-columns: repeat(<?php echo $seat_map['seats_per_row']; ?>, 1fr);">
                                <?php for ($seat = 1; $seat <= $seat_map['seats_per_row']; $seat++):
                                    $key = $row . '_' . $seat;
                                    $is_booked = isset($booked_seats[$key]);
                                    $is_aisle = ($seat == $seat_map['aisle_position']);
                                    $seat_label = $bus_handler->generate_seat_label($row, $seat, $seat_map['aisle_position']);
                                    $aisle_class = $is_aisle ? ' after-aisle' : '';
                                    $is_blocked = in_array($seat_label, $blocked_seats);
                                ?>
                                <?php if ($is_blocked): ?>
                                <div class="sp-bus-seat blocked-seat<?php echo $aisle_class; ?>" title="<?php _e('محظور', 'saint-porphyrius'); ?>">
                                    <span class="sp-seat-label" style="text-decoration: line-through;"><?php echo esc_html($seat_label); ?></span>
                                    <span class="sp-seat-blocked-icon">🚫</span>
                                </div>
                                <?php else: ?>
                                <button type="button" 
                                        class="sp-bus-seat<?php echo $aisle_class; ?> <?php echo $is_booked ? 'booked' : 'available'; ?>"
                                        data-bus-id="<?php echo esc_attr($bus->id); ?>"
                                        data-row="<?php echo esc_attr($row); ?>"
                                        data-seat="<?php echo esc_attr($seat); ?>"
                                        data-label="<?php echo esc_attr($seat_label); ?>"
                                        <?php echo $is_booked ? 'disabled' : ''; ?>
                                        title="<?php echo $is_booked ? esc_attr($booked_seats[$key]['user_name'] ?? __('محجوز', 'saint-porphyrius')) : esc_attr($seat_label); ?>">
                                    <span class="sp-seat-label"><?php echo esc_html($seat_label); ?></span>
                                    <?php if ($is_booked): ?>
                                    <span class="sp-seat-occupant">👤</span>
                                    <?php endif; ?>
                                </button>
                                <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                    
                    <!-- Back Row -->
                    <?php $back_row = $seat_map['rows'] + 2; ?>
                    <div class="sp-bus-row sp-back-row">
                        <div class="sp-row-label"><?php echo $back_row; ?></div>
                        <div class="sp-row-seats" style="grid-template-columns: repeat(<?php echo $back_row_seats; ?>, 1fr);">
                            <?php for ($seat = 1; $seat <= $back_row_seats; $seat++):
                                $key = $back_row . '_' . $seat;
                                $is_booked = isset($booked_seats[$key]);
                                $seat_label = $bus_handler->generate_seat_label($back_row, $seat, $seat_map['aisle_position']);
                                $is_blocked = in_array($seat_label, $blocked_seats);
                            ?>
                            <?php if ($is_blocked): ?>
                            <div class="sp-bus-seat back-seat blocked-seat" title="<?php _e('محظور', 'saint-porphyrius'); ?>">
                                <span class="sp-seat-label" style="text-decoration: line-through;"><?php echo esc_html($seat_label); ?></span>
                                <span class="sp-seat-blocked-icon">🚫</span>
                            </div>
                            <?php else: ?>
                            <button type="button" 
                                    class="sp-bus-seat back-seat <?php echo $is_booked ? 'booked' : 'available'; ?>"
                                    data-bus-id="<?php echo esc_attr($bus->id); ?>"
                                    data-row="<?php echo esc_attr($back_row); ?>"
                                    data-seat="<?php echo esc_attr($seat); ?>"
                                    data-label="<?php echo esc_attr($seat_label); ?>"
                                    <?php echo $is_booked ? 'disabled' : ''; ?>
                                    title="<?php echo $is_booked ? esc_attr($booked_seats[$key]['user_name'] ?? __('محجوز', 'saint-porphyrius')) : esc_attr($seat_label); ?>">
                                <span class="sp-seat-label"><?php echo esc_html($seat_label); ?></span>
                                <?php if ($is_booked): ?>
                                <span class="sp-seat-occupant">👤</span>
                                <?php endif; ?>
                            </button>
                            <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Legend -->
                <div class="sp-bus-legend">
                    <div class="sp-legend-item">
                        <span class="sp-legend-seat available"></span>
                        <span><?php _e('متاح', 'saint-porphyrius'); ?></span>
                    </div>
                    <div class="sp-legend-item">
                        <span class="sp-legend-seat booked"></span>
                        <span><?php _e('محجوز', 'saint-porphyrius'); ?></span>
                    </div>
                    <div class="sp-legend-item">
                        <span class="sp-legend-seat selected"></span>
                        <span><?php _e('اختيارك', 'saint-porphyrius'); ?></span>
                    </div>
                    <?php if (!empty($blocked_seats)): ?>
                    <div class="sp-legend-item">
                        <span class="sp-legend-seat blocked"></span>
                        <span><?php _e('غير متاح', 'saint-porphyrius'); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Price Section -->
                <?php if ((int)$event->bus_booking_fee > 0): ?>
                <div style="background: linear-gradient(135deg, #F0F9FF 0%, #E0F2FE 100%); border-radius: var(--sp-radius-lg); padding: 16px; margin-bottom: 16px; border-left: 4px solid var(--sp-primary);">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 24px;">💳</span>
                            <span style="font-weight: 600; color: var(--sp-text-primary);"><?php _e('رسوم الحجز', 'saint-porphyrius'); ?></span>
                        </div>
                        <span style="font-size: var(--sp-font-size-2xl); font-weight: 700; color: var(--sp-primary);">
                            <?php echo esc_html((int)$event->bus_booking_fee); ?>
                            <span style="font-size: var(--sp-font-size-base); margin-right: 4px;">نقطة</span>
                        </span>
                    </div>
                    <p style="color: #0369A1; font-size: var(--sp-font-size-sm); margin: 0;">
                        <span style="display: inline-block; margin-bottom: 4px;">✓ <?php _e('ستُخصم من رصيدك عند الحجز', 'saint-porphyrius'); ?></span><br>
                        <span style="display: inline-block;">↩️ <?php _e('وتُعاد تلقائياً عند حضورك الفعالية', 'saint-porphyrius'); ?></span>
                    </p>
                </div>
                <?php else: ?>
                <div style="background: linear-gradient(135deg, #F0FDF4 0%, #DBEAFE 100%); border-radius: var(--sp-radius-lg); padding: 16px; margin-bottom: 16px; border-left: 4px solid var(--sp-success);">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 24px;">🎉</span>
                        <div>
                            <span style="font-weight: 600; color: var(--sp-text-primary); display: block;"><?php _e('بدون رسوم حجز', 'saint-porphyrius'); ?></span>
                            <span style="color: #166534; font-size: var(--sp-font-size-sm);"><?php _e('الحجز مجاني تماماً', 'saint-porphyrius'); ?></span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Confirm Button -->
                <button type="button" class="sp-btn sp-btn-primary sp-btn-lg sp-btn-block sp-confirm-seat-btn" 
                        data-bus-id="<?php echo esc_attr($bus->id); ?>" disabled>
                    <?php _e('اختر مقعداً أولاً', 'saint-porphyrius'); ?>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php elseif ($bus_booking_enabled && !empty($event_buses) && $user_bus_booking && $is_past_event): ?>
    <!-- Show booking info for past events -->
    <div class="sp-section sp-bus-booking-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title">
                🚌 <?php _e('حجز الباص', 'saint-porphyrius'); ?>
            </h3>
        </div>
        <div class="sp-card sp-bus-booking-confirmed">
            <div class="sp-bus-booking-header">
                <div class="sp-bus-booking-icon" style="background: <?php echo esc_attr($user_bus_booking->color); ?>20; color: <?php echo esc_attr($user_bus_booking->color); ?>;">
                    <?php echo esc_html($user_bus_booking->icon); ?>
                </div>
                <div class="sp-bus-booking-info">
                    <h4><?php printf(__('باص %d - %s', 'saint-porphyrius'), $user_bus_booking->bus_number, $user_bus_booking->template_name); ?></h4>
                    <div class="sp-bus-booking-seat">
                        <span class="sp-seat-label"><?php _e('مقعدك:', 'saint-porphyrius'); ?></span>
                        <span class="sp-seat-number"><?php echo esc_html($user_bus_booking->seat_label); ?></span>
                    </div>
                    <span class="sp-badge" style="margin-top: 8px;">
                        <?php echo $user_bus_booking->status === 'checked_in' ? '✅ ' . __('تم الصعود', 'saint-porphyrius') : '📋 ' . __('محجوز', 'saint-porphyrius'); ?>
                    </span>
                </div>
            </div>
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
    
    // ==========================================
    // BUS BOOKING SYSTEM
    // ==========================================
    
    // Bus Tab Switching
    $(document).on('click', '.sp-bus-tab', function() {
        var busId = $(this).data('bus-id');
        
        // Update tab states
        $('.sp-bus-tab').removeClass('active');
        $(this).addClass('active');
        
        // Update seat map visibility
        $('.sp-bus-seat-map-container').removeClass('active');
        $('.sp-bus-seat-map-container[data-bus-id="' + busId + '"]').addClass('active');
        
        // Clear any selected seats in other buses
        $('.sp-bus-seat.selected').removeClass('selected');
        updateConfirmButton(busId);
    });
    
    // Seat Selection
    $(document).on('click', '.sp-bus-seat.available', function() {
        var $seat = $(this);
        var busId = $seat.data('bus-id');
        var $container = $seat.closest('.sp-bus-seat-map-container');
        
        // Deselect any previously selected seat in this bus
        $container.find('.sp-bus-seat.selected').removeClass('selected');
        
        // Select this seat
        $seat.addClass('selected');
        
        // Update confirm button
        updateConfirmButton(busId);
    });
    
    function updateConfirmButton(busId) {
        var $container = $('.sp-bus-seat-map-container[data-bus-id="' + busId + '"]');
        var $selectedSeat = $container.find('.sp-bus-seat.selected');
        var $confirmBtn = $container.find('.sp-confirm-seat-btn');
        
        if ($selectedSeat.length > 0) {
            var seatLabel = $selectedSeat.data('label');
            $confirmBtn.prop('disabled', false).html('🎫 <?php _e('تأكيد حجز المقعد', 'saint-porphyrius'); ?> ' + seatLabel);
        } else {
            $confirmBtn.prop('disabled', true).html('<?php _e('اختر مقعداً أولاً', 'saint-porphyrius'); ?>');
        }
    }
    
    // Confirm Seat Booking
    $(document).on('click', '.sp-confirm-seat-btn:not(:disabled)', function() {
        var $btn = $(this);
        var busId = $btn.data('bus-id');
        var $container = $('.sp-bus-seat-map-container[data-bus-id="' + busId + '"]');
        var $selectedSeat = $container.find('.sp-bus-seat.selected');
        
        if ($selectedSeat.length === 0) return;
        
        var seatRow = $selectedSeat.data('row');
        var seatNumber = $selectedSeat.data('seat');
        var seatLabel = $selectedSeat.data('label');
        
        $btn.prop('disabled', true).html('<span class="sp-spinner-sm"></span> <?php _e('جاري الحجز...', 'saint-porphyrius'); ?>');
        
        $.ajax({
            url: spApp.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sp_book_bus_seat',
                nonce: spApp.nonce,
                event_bus_id: busId,
                seat_row: seatRow,
                seat_number: seatNumber
            },
            success: function(response) {
                if (response.success) {
                    // Reload page to show confirmed booking
                    location.reload();
                } else {
                    alert(response.data.message || '<?php _e('حدث خطأ', 'saint-porphyrius'); ?>');
                    $btn.prop('disabled', false).html('🎫 <?php _e('تأكيد حجز المقعد', 'saint-porphyrius'); ?> ' + seatLabel);
                    
                    // If seat is already taken, mark it as booked
                    if (response.data.code === 'seat_taken') {
                        $selectedSeat.removeClass('selected available').addClass('booked').prop('disabled', true);
                        $selectedSeat.find('.sp-seat-label').after('<span class="sp-seat-occupant">👤</span>');
                    }
                }
            },
            error: function() {
                alert('<?php _e('حدث خطأ في الاتصال', 'saint-porphyrius'); ?>');
                $btn.prop('disabled', false).html('🎫 <?php _e('تأكيد حجز المقعد', 'saint-porphyrius'); ?> ' + seatLabel);
            }
        });
    });
    
    // Cancel Bus Booking
    $(document).on('click', '.sp-cancel-bus-booking', function() {
        if (!confirm('<?php _e('هل أنت متأكد من إلغاء حجز المقعد؟', 'saint-porphyrius'); ?>')) {
            return;
        }
        
        var $btn = $(this);
        var bookingId = $btn.data('booking-id');
        
        $btn.prop('disabled', true).html('<span class="sp-spinner-sm"></span> <?php _e('جاري الإلغاء...', 'saint-porphyrius'); ?>');
        
        $.ajax({
            url: spApp.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sp_cancel_bus_booking',
                nonce: spApp.nonce,
                booking_id: bookingId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || '<?php _e('حدث خطأ', 'saint-porphyrius'); ?>');
                    $btn.prop('disabled', false).html('<?php _e('إلغاء الحجز', 'saint-porphyrius'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('حدث خطأ في الاتصال', 'saint-porphyrius'); ?>');
                $btn.prop('disabled', false).html('<?php _e('إلغاء الحجز', 'saint-porphyrius'); ?>');
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

/* ==========================================
   BUS BOOKING STYLES
   ========================================== */

.sp-bus-booking-section {
    margin-top: var(--sp-space-lg);
}

/* Confirmed Booking Card */
.sp-bus-booking-confirmed {
    background: linear-gradient(135deg, #E0F2FE 0%, #BAE6FD 100%);
    border: 2px solid #0EA5E9;
    border-radius: var(--sp-radius-lg);
    padding: var(--sp-space-lg);
}

.sp-bus-booking-header {
    display: flex;
    align-items: flex-start;
    gap: var(--sp-space-md);
    margin-bottom: var(--sp-space-md);
}

.sp-bus-booking-icon {
    width: 56px;
    height: 56px;
    border-radius: var(--sp-radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    flex-shrink: 0;
}

.sp-bus-booking-info h4 {
    margin: 0 0 8px;
    font-size: var(--sp-font-size-lg);
    color: #0369A1;
}

.sp-bus-booking-seat {
    display: flex;
    align-items: center;
    gap: 8px;
}

.sp-bus-booking-seat .sp-seat-label {
    color: #0C4A6E;
    font-size: var(--sp-font-size-sm);
}

.sp-bus-booking-seat .sp-seat-number {
    background: #0EA5E9;
    color: white;
    padding: 6px 16px;
    border-radius: var(--sp-radius-sm);
    font-weight: 700;
    font-size: var(--sp-font-size-lg);
}

.sp-bus-booking-details {
    background: rgba(255,255,255,0.7);
    border-radius: var(--sp-radius-md);
    padding: var(--sp-space-md);
    margin-bottom: var(--sp-space-md);
}

.sp-bus-detail-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #0369A1;
    font-size: var(--sp-font-size-sm);
}

.sp-bus-detail-item + .sp-bus-detail-item {
    margin-top: 8px;
}

/* Bus Selection Container */
.sp-bus-selection-container {
    background: white;
    border-radius: var(--sp-radius-lg);
    padding: var(--sp-space-lg);
}

.sp-bus-selection-intro {
    text-align: center;
    color: var(--sp-text-secondary);
    margin: 0 0 var(--sp-space-lg);
}

/* Bus Tabs */
.sp-bus-tabs {
    display: flex;
    gap: var(--sp-space-sm);
    overflow-x: auto;
    padding-bottom: var(--sp-space-sm);
    margin-bottom: var(--sp-space-lg);
    -webkit-overflow-scrolling: touch;
}

.sp-bus-tab {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: var(--sp-space-md);
    border: 2px solid var(--sp-border);
    border-radius: var(--sp-radius-md);
    background: white;
    cursor: pointer;
    min-width: 80px;
    transition: all 0.2s ease;
}

.sp-bus-tab:hover {
    border-color: var(--sp-primary);
}

.sp-bus-tab.active {
    border-color: var(--sp-primary);
    background: var(--sp-primary-50);
}

.sp-bus-tab-icon {
    font-size: 24px;
}

.sp-bus-tab-label {
    font-weight: 600;
    font-size: var(--sp-font-size-sm);
    color: var(--sp-text-primary);
}

.sp-bus-tab-seats {
    font-size: var(--sp-font-size-xs);
    color: var(--sp-success);
    font-weight: 500;
}

.sp-bus-tab-seats.full {
    color: var(--sp-error);
}

/* Bus Seat Map Container */
.sp-bus-seat-map-container {
    display: none;
}

.sp-bus-seat-map-container.active {
    display: block;
}

.sp-bus-info-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--sp-space-md);
    padding: var(--sp-space-sm) var(--sp-space-md);
    background: var(--sp-background);
    border-radius: var(--sp-radius-md);
    margin-bottom: var(--sp-space-md);
    font-size: var(--sp-font-size-sm);
}

.sp-bus-time {
    color: var(--sp-text-secondary);
}

/* Bus Visual Layout - International Standard */
.sp-bus-visual {
    background: linear-gradient(180deg, #F8FAFC 0%, #F1F5F9 100%);
    border: 3px solid var(--bus-color, #3B82F6);
    border-radius: 24px 24px 16px 16px;
    padding: var(--sp-space-md);
    margin-bottom: var(--sp-space-lg);
    position: relative;
}

/* Bus Front */
.sp-bus-front {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: var(--sp-space-sm);
    background: var(--bus-color, #3B82F6);
    border-radius: 16px 16px 4px 4px;
    margin-bottom: var(--sp-space-sm);
}

.sp-bus-icon {
    font-size: 28px;
    filter: brightness(0) invert(1);
}

/* Bus Row */
.sp-bus-row {
    display: flex;
    align-items: center;
    gap: var(--sp-space-sm);
    margin-bottom: 8px;
}

.sp-row-label {
    width: 24px;
    font-size: 11px;
    font-weight: 600;
    color: #64748B;
    text-align: center;
}

.sp-row-seats {
    display: grid;
    gap: 6px;
    flex: 1;
}

.sp-driver-row {
    padding-bottom: var(--sp-space-sm);
    border-bottom: 2px dashed #CBD5E1;
    margin-bottom: var(--sp-space-sm);
}

.sp-back-row {
    padding-top: var(--sp-space-sm);
    border-top: 2px dashed #CBD5E1;
    margin-top: var(--sp-space-sm);
}

/* Seats Grid */
.sp-bus-seats {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.sp-seat-empty-space {
    width: 100%;
    min-width: 42px;
    height: 50px;
}

/* Bus Seat */
.sp-bus-seat {
    width: 100%;
    min-width: 42px;
    height: 50px;
    border: 2px solid #CBD5E1;
    border-radius: 8px 8px 4px 4px;
    background: linear-gradient(180deg, #FFFFFF 0%, #F1F5F9 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.sp-bus-seat.back-seat {
    min-width: 36px;
    height: 46px;
}

.sp-bus-seat.after-aisle {
    margin-right: 8px;
}

.sp-bus-seat::before {
    content: '';
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80%;
    height: 6px;
    background: #CBD5E1;
    border-radius: 0 0 4px 4px;
}

.sp-bus-seat .sp-seat-label {
    font-size: 10px;
    font-weight: 700;
    color: #64748B;
}

.sp-bus-seat .sp-seat-icon {
    font-size: 18px;
}

.sp-bus-seat .sp-seat-occupant {
    font-size: 14px;
    margin-top: 2px;
}

/* Driver Seat */
.sp-bus-seat.driver {
    background: linear-gradient(180deg, #E2E8F0 0%, #CBD5E1 100%);
    border-color: #94A3B8;
    cursor: default;
}

.sp-bus-seat.driver::before {
    background: #94A3B8;
}

/* Available Seat */
.sp-bus-seat.available:hover {
    border-color: var(--sp-primary);
    background: var(--sp-primary-50);
    transform: scale(1.05);
}

.sp-bus-seat.available:active {
    transform: scale(0.98);
}

/* Booked Seat */
.sp-bus-seat.booked {
    background: linear-gradient(180deg, #FEE2E2 0%, #FECACA 100%);
    border-color: #F87171;
    cursor: not-allowed;
}

.sp-bus-seat.booked::before {
    background: #F87171;
}

.sp-bus-seat.booked .sp-seat-label {
    color: #991B1B;
}

/* Blocked Seat (admin-blocked, not bookable) */
.sp-bus-seat.blocked-seat {
    background: linear-gradient(180deg, #F3F4F6 0%, #E5E7EB 100%);
    border-color: #9CA3AF;
    cursor: not-allowed;
    opacity: 0.5;
}

.sp-bus-seat.blocked-seat::before {
    background: #9CA3AF;
}

.sp-bus-seat.blocked-seat .sp-seat-label {
    color: #6B7280;
}

.sp-bus-seat.blocked-seat .sp-seat-blocked-icon {
    font-size: 12px;
    position: absolute;
    bottom: 2px;
}

/* Selected Seat */
.sp-bus-seat.selected {
    background: linear-gradient(180deg, #DCFCE7 0%, #BBF7D0 100%);
    border-color: #22C55E;
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
}

.sp-bus-seat.selected::before {
    background: #22C55E;
}

.sp-bus-seat.selected .sp-seat-label {
    color: #166534;
}

/* Legend */
.sp-bus-legend {
    display: flex;
    justify-content: center;
    gap: var(--sp-space-lg);
    margin-bottom: var(--sp-space-lg);
}

.sp-legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: var(--sp-font-size-xs);
    color: var(--sp-text-secondary);
}

.sp-legend-seat {
    width: 24px;
    height: 28px;
    border: 2px solid #CBD5E1;
    border-radius: 4px 4px 2px 2px;
    background: linear-gradient(180deg, #FFFFFF 0%, #F1F5F9 100%);
}

.sp-legend-seat.booked {
    background: linear-gradient(180deg, #FEE2E2 0%, #FECACA 100%);
    border-color: #F87171;
}

.sp-legend-seat.selected {
    background: linear-gradient(180deg, #DCFCE7 0%, #BBF7D0 100%);
    border-color: #22C55E;
}

.sp-legend-seat.blocked {
    background: linear-gradient(180deg, #F3F4F6 0%, #E5E7EB 100%);
    border-color: #9CA3AF;
    opacity: 0.5;
}

/* Confirm Button */
.sp-confirm-seat-btn {
    margin-top: var(--sp-space-md);
}

.sp-confirm-seat-btn:disabled {
    background: var(--sp-background);
    color: var(--sp-text-secondary);
    cursor: not-allowed;
}

/* Responsive adjustments for smaller screens */
@media (max-width: 380px) {
    .sp-bus-seat {
        width: 40px;
        height: 48px;
    }
    
    .sp-bus-seat .sp-seat-label {
        font-size: 9px;
    }
    
    .sp-bus-seat .sp-seat-occupant {
        font-size: 12px;
    }
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
