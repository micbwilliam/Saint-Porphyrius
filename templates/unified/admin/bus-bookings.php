<?php
/**
 * Saint Porphyrius - Admin Bus Bookings Management
 * View and manage seat bookings for a specific event bus
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
$events_handler = SP_Events::get_instance();

// Get bus_id from query parameter
$bus_id = isset($_GET['bus_id']) ? absint($_GET['bus_id']) : 0;

if (!$bus_id) {
    wp_safe_redirect(home_url('/app/admin/events'));
    exit;
}

// Get bus details
$bus = $bus_handler->get_event_bus($bus_id);
if (!$bus) {
    wp_safe_redirect(home_url('/app/admin/events'));
    exit;
}

// Get event details
$event = $events_handler->get($bus->event_id);
if (!$event) {
    wp_safe_redirect(home_url('/app/admin/events'));
    exit;
}

// Get bookings
$bookings = $bus_handler->get_bus_bookings($bus_id);
$seat_map = $bus_handler->get_seat_map($bus_id);

// Group bookings by row for easier display
$bookings_by_seat = array();
foreach ($bookings as $booking) {
    $key = $booking->seat_row . '-' . $booking->seat_number;
    $bookings_by_seat[$key] = $booking;
}
?>

<!-- Header -->
<div class="sp-unified-header sp-header-colored" style="--header-color: <?php echo esc_attr($event->type_color); ?>;">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/admin/events?action=edit&id=' . $event->id); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('حجوزات الباص', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-spacer"></div>
    </div>
</div>

<!-- Main Content -->
<main class="sp-page-content has-bottom-nav">
    <!-- Bus Info Card -->
    <div class="sp-card sp-bus-info-card" style="background: <?php echo esc_attr($bus->color); ?>15; border: 2px solid <?php echo esc_attr($bus->color); ?>;">
        <div class="sp-bus-header">
            <div class="sp-bus-icon-large" style="background: <?php echo esc_attr($bus->color); ?>; color: white;">
                <?php echo esc_html($bus->icon); ?>
            </div>
            <div class="sp-bus-details">
                <h2><?php printf(__('باص %d - %s', 'saint-porphyrius'), $bus->bus_number, $bus->template_name_ar); ?></h2>
                <p style="color: var(--sp-text-secondary); margin: 4px 0;">
                    <?php echo esc_html($event->title_ar); ?>
                </p>
                <div class="sp-bus-meta">
                    <?php if ($bus->departure_time): ?>
                    <span>🕐 <?php echo esc_html($bus->departure_time); ?></span>
                    <?php endif; ?>
                    <?php if ($bus->departure_location): ?>
                    <span>📍 <?php echo esc_html($bus->departure_location); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Stats -->
        <?php
        // Use the same seat-map walk that get_event_buses(true) uses, so the
        // numbers shown here always match what members see and what
        // is_event_fully_booked() decides. No arithmetic on blocked_seats —
        // duplicates / stale labels in that array would skew the math.
        $bus_for_stats = clone $bus;
        $available_admin = $bus_handler->count_available_seats_for_bus($bus_for_stats);
        $booked_count    = count($bookings);
        $effective_cap   = $available_admin + $booked_count;
        $blocked_admin   = max(0, (int)$bus->capacity - $effective_cap);
        ?>
        <div class="sp-bus-stats">
            <div class="sp-stat-item">
                <span class="sp-stat-value"><?php echo count($bookings); ?></span>
                <span class="sp-stat-label"><?php _e('محجوز', 'saint-porphyrius'); ?></span>
            </div>
            <div class="sp-stat-item">
                <span class="sp-stat-value"><?php echo $available_admin; ?></span>
                <span class="sp-stat-label"><?php _e('متاح', 'saint-porphyrius'); ?></span>
            </div>
            <div class="sp-stat-item">
                <span class="sp-stat-value"><?php echo $effective_cap; ?><?php if ($blocked_admin): ?><span style="font-size:10px;color:#aaa"> (<?php printf(_n('%d محظور', '%d محظور', $blocked_admin, 'saint-porphyrius'), $blocked_admin); ?>)</span><?php endif; ?></span>
                <span class="sp-stat-label"><?php _e('إجمالي', 'saint-porphyrius'); ?></span>
            </div>
            <div class="sp-stat-item">
                <span class="sp-stat-value sp-checkin-count">
                    <?php 
                    $checked_in = array_filter($bookings, function($b) { return $b->status === 'checked_in'; });
                    echo count($checked_in);
                    ?>
                </span>
                <span class="sp-stat-label"><?php _e('صعد', 'saint-porphyrius'); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Visual Seat Map -->
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title"><?php _e('خريطة المقاعد', 'saint-porphyrius'); ?></h3>
        </div>
        
        <div class="sp-card sp-admin-seat-map">
            <!-- Bus Visual -->
            <div class="sp-bus-visual" style="--bus-color: <?php echo esc_attr($seat_map['color'] ?? '#3B82F6'); ?>;">
                <!-- Bus Front -->
                <div class="sp-bus-front">
                    <span class="sp-bus-icon"><?php echo esc_html($seat_map['icon'] ?? '🚌'); ?></span>
                </div>
                
                <?php
                // Get blocked seats - get from seat_map which is properly parsed in SP_Bus class
                $blocked_seats = isset($seat_map['blocked_seats']) && is_array($seat_map['blocked_seats'])
                    ? $seat_map['blocked_seats']
                    : array();
                // Seats held for a pending admin-approval offer, keyed "row_seat".
                $held_seats = isset($seat_map['held_seats']) && is_array($seat_map['held_seats']) ? $seat_map['held_seats'] : array();
                $driver_seats = $seat_map['driver_seats'] ?? 1;
                $passenger_count = max(0, $driver_seats - 1);
                ?>
                
                <!-- Driver Row (Row 1) - Driver on left (last position), passenger seats on right (first positions) -->
                <div class="sp-bus-row sp-driver-row">
                    <div class="sp-row-label">1</div>
                    <div class="sp-row-seats" style="grid-template-columns: repeat(<?php echo $seat_map['seats_per_row']; ?>, 1fr);">
                        <?php 
                        for ($s = 1; $s <= $seat_map['seats_per_row']; $s++):
                            if ($passenger_count > 0 && $s <= $passenger_count):
                                // Passenger seats on right side (first positions)
                                $key = '1-' . $s;
                                $booking = isset($bookings_by_seat[$key]) ? $bookings_by_seat[$key] : null;
                                $held = isset($held_seats['1_' . $s]) ? $held_seats['1_' . $s] : null;
                                $seat_label = $bus_handler->generate_seat_label(1, $s, $seat_map['aisle_position']);
                                $is_blocked = in_array($seat_label, $blocked_seats);
                        ?>
                            <?php if ($is_blocked): ?>
                            <div class="sp-bus-seat blocked-seat" title="<?php _e('محظور', 'saint-porphyrius'); ?>">
                                <span class="sp-seat-label" style="text-decoration: line-through;"><?php echo esc_html($seat_label); ?></span>
                                <span class="sp-seat-blocked-icon">🚫</span>
                            </div>
                            <?php elseif ($held && !$booking): ?>
                            <div class="sp-bus-seat held-seat" title="<?php echo esc_attr(sprintf(__('بانتظار موافقة — %s', 'saint-porphyrius'), $held['user_name'] ?? '')); ?>">
                                <span class="sp-seat-label"><?php echo esc_html($seat_label); ?></span>
                                <span class="sp-seat-occupant">⏳</span>
                            </div>
                            <?php elseif ($booking): ?>
                            <?php $short_name = trim(($booking->first_name ?? '') . ' ' . ($booking->middle_name ?? '')) ?: ($booking->name_ar ?: $booking->display_name); ?>
                            <button type="button" 
                                    class="sp-bus-seat booked <?php echo $booking->status === 'checked_in' ? 'checked-in' : ''; ?>"
                                    data-booking-id="<?php echo esc_attr($booking->id); ?>"
                                    data-user-id="<?php echo esc_attr($booking->user_id); ?>"
                                    data-row="1"
                                    data-seat="<?php echo esc_attr($s); ?>"
                                    data-seat-label="<?php echo esc_attr($seat_label); ?>"
                                    data-user-name="<?php echo esc_attr($short_name); ?>">
                                <span class="sp-seat-label"><?php echo esc_html($seat_label); ?></span>
                                <span class="sp-seat-occupant"><?php echo $booking->status === 'checked_in' ? '✅' : '👤'; ?></span>
                            </button>
                            <?php else: ?>
                            <div class="sp-bus-seat empty"
                                 data-row="1"
                                 data-seat="<?php echo esc_attr($s); ?>"
                                 data-label="<?php echo esc_attr($seat_label); ?>">
                                <span class="sp-seat-label"><?php echo esc_html($seat_label); ?></span>
                            </div>
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
                    <?php 
                    for ($row = 2; $row <= $seat_map['rows'] + 1; $row++): ?>
                    <div class="sp-bus-row">
                        <div class="sp-row-label"><?php echo $row; ?></div>
                        <div class="sp-row-seats" style="grid-template-columns: repeat(<?php echo $seat_map['seats_per_row']; ?>, 1fr);">
                            <?php for ($seat = 1; $seat <= $seat_map['seats_per_row']; $seat++):
                                $key = $row . '-' . $seat;
                                $booking = isset($bookings_by_seat[$key]) ? $bookings_by_seat[$key] : null;
                                $held = isset($held_seats[$row . '_' . $seat]) ? $held_seats[$row . '_' . $seat] : null;
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
                                <?php elseif ($held && !$booking): ?>
                                <div class="sp-bus-seat held-seat<?php echo $aisle_class; ?>" title="<?php echo esc_attr(sprintf(__('بانتظار موافقة — %s', 'saint-porphyrius'), $held['user_name'] ?? '')); ?>">
                                    <span class="sp-seat-label"><?php echo esc_html($seat_label); ?></span>
                                    <span class="sp-seat-occupant">⏳</span>
                                </div>
                                <?php elseif ($booking): ?>
                                <?php $short_name = trim(($booking->first_name ?? '') . ' ' . ($booking->middle_name ?? '')) ?: ($booking->name_ar ?: $booking->display_name); ?>
                                <button type="button" 
                                        class="sp-bus-seat booked<?php echo $aisle_class; ?> <?php echo $booking->status === 'checked_in' ? 'checked-in' : ''; ?>"
                                        data-booking-id="<?php echo esc_attr($booking->id); ?>"
                                        data-user-id="<?php echo esc_attr($booking->user_id); ?>"
                                        data-row="<?php echo esc_attr($row); ?>"
                                        data-seat="<?php echo esc_attr($seat); ?>"
                                        data-seat-label="<?php echo esc_attr($seat_label); ?>"
                                        data-user-name="<?php echo esc_attr($short_name); ?>">
                                    <span class="sp-seat-label"><?php echo esc_html($seat_label); ?></span>
                                    <span class="sp-seat-occupant"><?php echo $booking->status === 'checked_in' ? '✅' : '👤'; ?></span>
                                </button>
                                <?php else: ?>
                                <div class="sp-bus-seat empty<?php echo $aisle_class; ?>"
                                     data-row="<?php echo esc_attr($row); ?>"
                                     data-seat="<?php echo esc_attr($seat); ?>"
                                     data-label="<?php echo esc_attr($seat_label); ?>">
                                    <span class="sp-seat-label"><?php echo esc_html($seat_label); ?></span>
                                </div>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
                
                <!-- Back Row (Last row with extra seats) -->
                <?php 
                $back_row = $seat_map['rows'] + 2;
                $back_row_seats = $seat_map['back_row_seats'] ?? ($seat_map['seats_per_row'] + 1);
                ?>
                <div class="sp-bus-row sp-back-row">
                    <div class="sp-row-label"><?php echo $back_row; ?></div>
                    <div class="sp-row-seats" style="grid-template-columns: repeat(<?php echo $back_row_seats; ?>, 1fr);">
                        <?php for ($seat = 1; $seat <= $back_row_seats; $seat++):
                            $key = $back_row . '-' . $seat;
                            $booking = isset($bookings_by_seat[$key]) ? $bookings_by_seat[$key] : null;
                            $held = isset($held_seats[$back_row . '_' . $seat]) ? $held_seats[$back_row . '_' . $seat] : null;
                            $seat_label = $bus_handler->generate_seat_label($back_row, $seat, $seat_map['aisle_position']);
                            $is_blocked = in_array($seat_label, $blocked_seats);
                        ?>
                            <?php if ($is_blocked): ?>
                            <div class="sp-bus-seat blocked-seat back-seat" title="<?php _e('محظور', 'saint-porphyrius'); ?>">
                                <span class="sp-seat-label" style="text-decoration: line-through;"><?php echo esc_html($seat_label); ?></span>
                                <span class="sp-seat-blocked-icon">🚫</span>
                            </div>
                            <?php elseif ($held && !$booking): ?>
                            <div class="sp-bus-seat held-seat back-seat" title="<?php echo esc_attr(sprintf(__('بانتظار موافقة — %s', 'saint-porphyrius'), $held['user_name'] ?? '')); ?>">
                                <span class="sp-seat-label"><?php echo esc_html($seat_label); ?></span>
                                <span class="sp-seat-occupant">⏳</span>
                            </div>
                            <?php elseif ($booking): ?>
                            <?php $short_name = trim(($booking->first_name ?? '') . ' ' . ($booking->middle_name ?? '')) ?: ($booking->name_ar ?: $booking->display_name); ?>
                            <button type="button" 
                                    class="sp-bus-seat booked back-seat <?php echo $booking->status === 'checked_in' ? 'checked-in' : ''; ?>"
                                    data-booking-id="<?php echo esc_attr($booking->id); ?>"
                                    data-user-id="<?php echo esc_attr($booking->user_id); ?>"
                                    data-row="<?php echo esc_attr($back_row); ?>"
                                    data-seat="<?php echo esc_attr($seat); ?>"
                                    data-seat-label="<?php echo esc_attr($seat_label); ?>"
                                    data-user-name="<?php echo esc_attr($short_name); ?>">
                                <span class="sp-seat-label"><?php echo esc_html($seat_label); ?></span>
                                <span class="sp-seat-occupant"><?php echo $booking->status === 'checked_in' ? '✅' : '👤'; ?></span>
                            </button>
                            <?php else: ?>
                            <div class="sp-bus-seat empty back-seat"
                                 data-row="<?php echo esc_attr($back_row); ?>"
                                 data-seat="<?php echo esc_attr($seat); ?>"
                                 data-label="<?php echo esc_attr($seat_label); ?>">
                                <span class="sp-seat-label"><?php echo esc_html($seat_label); ?></span>
                            </div>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            
            <!-- Legend -->
            <div class="sp-bus-legend">
                <div class="sp-legend-item">
                    <span class="sp-legend-seat empty"></span>
                    <span><?php _e('فارغ', 'saint-porphyrius'); ?></span>
                </div>
                <div class="sp-legend-item">
                    <span class="sp-legend-seat booked"></span>
                    <span><?php _e('محجوز', 'saint-porphyrius'); ?></span>
                </div>
                <div class="sp-legend-item">
                    <span class="sp-legend-seat checked-in"></span>
                    <span><?php _e('صعد', 'saint-porphyrius'); ?></span>
                </div>
                <?php if (!empty($blocked_seats)): ?>
                <div class="sp-legend-item">
                    <span class="sp-legend-seat blocked"></span>
                    <span><?php _e('محظور', 'saint-porphyrius'); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bookings List -->
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title"><?php _e('قائمة الحجوزات', 'saint-porphyrius'); ?></h3>
        </div>
        
        <?php if (empty($bookings)): ?>
        <div class="sp-card sp-empty-state">
            <div class="sp-empty-icon">🪑</div>
            <p><?php _e('لا توجد حجوزات بعد', 'saint-porphyrius'); ?></p>
        </div>
        <?php else: ?>
        <div class="sp-card sp-bookings-list-card">
            <?php foreach ($bookings as $booking): ?>
            <?php $short_name = trim(($booking->first_name ?? '') . ' ' . ($booking->middle_name ?? '')) ?: ($booking->name_ar ?: $booking->display_name); ?>
            <div class="sp-booking-item <?php echo $booking->status === 'checked_in' ? 'checked-in' : ''; ?>" 
                 data-booking-id="<?php echo esc_attr($booking->id); ?>">
                <div class="sp-booking-seat-badge" style="background: <?php echo esc_attr($bus->color); ?>;">
                    <?php echo esc_html($booking->seat_label); ?>
                </div>
                <div class="sp-booking-info">
                    <div class="sp-booking-name"><a href="<?php echo esc_url(sp_profile_url($booking->user_id)); ?>" class="sp-profile-link"><?php echo esc_html($short_name); ?></a></div>
                    <div class="sp-booking-meta">
                        <?php echo esc_html(date_i18n('j M H:i', strtotime($booking->booked_at))); ?>
                        <?php if ($booking->status === 'checked_in'): ?>
                        • <span style="color: var(--sp-success);"><?php _e('✅ صعد', 'saint-porphyrius'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="sp-booking-actions">
                    <?php if ($booking->status !== 'checked_in'): ?>
                    <button type="button" class="sp-btn sp-btn-sm sp-btn-success sp-checkin-btn" 
                            data-booking-id="<?php echo esc_attr($booking->id); ?>">
                        ✅ <?php _e('صعد', 'saint-porphyrius'); ?>
                    </button>
                    <?php else: ?>
                    <span class="sp-badge sp-badge-success"><?php _e('تم', 'saint-porphyrius'); ?></span>
                    <?php endif; ?>
                    <button type="button" class="sp-btn sp-btn-sm sp-btn-outline sp-btn-danger sp-cancel-booking-btn"
                            data-booking-id="<?php echo esc_attr($booking->id); ?>"
                            data-user-name="<?php echo esc_attr($short_name); ?>">
                        🗑️
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Pending Seat Offers (Admin Approval) -->
    <?php
    $pending_offers = $bus_handler->get_pending_offers($bus->event_id);
    if (!empty($pending_offers)):
    ?>
    <div class="sp-section">
        <div class="sp-card" style="padding: 0; overflow: hidden; border: 2px solid #F59E0B;">
            <div style="padding: 12px 16px; background: #FFFBEB; border-bottom: 1px solid var(--sp-border);">
                <h3 style="margin: 0; font-size: var(--sp-font-size-md); color: #92400E;">
                    🔔 <?php printf(esc_html__('طلبات بانتظار موافقتك (%d)', 'saint-porphyrius'), count($pending_offers)); ?>
                </h3>
                <div style="font-size: var(--sp-font-size-xs); color: #92400E; margin-top: 4px;">
                    <?php esc_html_e('عند تفريغ مقعد يُعرض على أول مؤهّل في قائمة الانتظار. وافق لحجزه، ارفض لإزالته من القائمة، أو تخطَّ للتالي.', 'saint-porphyrius'); ?>
                </div>
            </div>
            <?php foreach ($pending_offers as $offer):
                $o_name = $offer->first_name ?: ($offer->name_ar ?: $offer->display_name);
                $o_gender = sp_get_user_gender($offer->user_id);
                $g_icon = $o_gender === 'female' ? '👩' : ($o_gender === 'male' ? '👨' : '👤');
            ?>
            <div class="sp-offer-row" data-offer-id="<?php echo esc_attr($offer->id); ?>" style="padding: 12px 16px; border-bottom: 1px solid var(--sp-border);">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                    <span style="font-size: 18px;"><?php echo $g_icon; ?></span>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 600;"><a href="<?php echo esc_url(sp_profile_url($offer->user_id)); ?>" class="sp-profile-link"><?php echo esc_html($o_name); ?></a></div>
                        <div style="font-size: var(--sp-font-size-xs); color: var(--sp-text-secondary);">
                            <?php printf(esc_html__('باص %d — مقعد %s', 'saint-porphyrius'), (int) $offer->bus_number, esc_html($offer->seat_label)); ?>
                        </div>
                    </div>
                    <span class="sp-badge" style="background: #FEF3C7; color: #92400E;"><?php esc_html_e('بانتظار', 'saint-porphyrius'); ?></span>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="sp-btn sp-btn-sm sp-btn-success sp-offer-accept" data-offer-id="<?php echo esc_attr($offer->id); ?>" style="flex: 1;">✅ <?php esc_html_e('موافقة', 'saint-porphyrius'); ?></button>
                    <button type="button" class="sp-btn sp-btn-sm sp-btn-outline sp-offer-skip" data-offer-id="<?php echo esc_attr($offer->id); ?>" style="flex: 1;">⏭ <?php esc_html_e('تخطّي', 'saint-porphyrius'); ?></button>
                    <button type="button" class="sp-btn sp-btn-sm sp-btn-outline sp-btn-danger sp-offer-reject" data-offer-id="<?php echo esc_attr($offer->id); ?>" style="flex: 1;">✖ <?php esc_html_e('رفض', 'saint-porphyrius'); ?></button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Waiting List Management -->
    <?php
    $event_waiting_list = $bus_handler->get_waiting_list($bus->event_id);
    if (!empty($event_waiting_list)):
    ?>
    <div class="sp-section">
        <div class="sp-card" style="padding: 0; overflow: hidden;">
            <div style="padding: 12px 16px; background: var(--sp-background); border-bottom: 1px solid var(--sp-border); display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                <h3 style="margin: 0; font-size: var(--sp-font-size-md);">
                    ⏳ <?php printf(esc_html__('قائمة الانتظار (%d)', 'saint-porphyrius'), count($event_waiting_list)); ?>
                </h3>
                <button type="button" id="sp-process-waiting-list-btn" class="sp-btn sp-btn-sm sp-btn-primary" data-event-id="<?php echo esc_attr($bus->event_id); ?>">
                    🔄 <?php esc_html_e('معالجة الآن', 'saint-porphyrius'); ?>
                </button>
            </div>
            <div id="sp-waiting-list-rows">
            <?php foreach ($event_waiting_list as $idx => $entry):
                $w_name = $entry->first_name ?: ($entry->name_ar ?: $entry->display_name);
            ?>
                <div class="sp-waiting-row" data-entry-id="<?php echo esc_attr($entry->id); ?>" style="display: flex; align-items: center; gap: 8px; padding: 10px 16px; border-bottom: 1px solid var(--sp-border);">
                    <div class="sp-waiting-position" style="font-weight: 700; color: var(--sp-primary); min-width: 32px;">#<?php echo esc_html($entry->position); ?></div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 600;">
                            <a href="<?php echo esc_url(sp_profile_url($entry->user_id)); ?>" class="sp-profile-link"><?php echo esc_html($w_name); ?></a>
                        </div>
                        <div style="font-size: var(--sp-font-size-xs); color: var(--sp-text-secondary);">
                            <?php echo esc_html(date_i18n('j M - H:i', strtotime($entry->created_at))); ?>
                        </div>
                    </div>
                    <button type="button" class="sp-btn sp-btn-sm sp-btn-outline sp-waiting-up" title="<?php esc_attr_e('للأعلى', 'saint-porphyrius'); ?>" <?php echo $idx === 0 ? 'disabled' : ''; ?>>▲</button>
                    <button type="button" class="sp-btn sp-btn-sm sp-btn-outline sp-waiting-down" title="<?php esc_attr_e('للأسفل', 'saint-porphyrius'); ?>" <?php echo $idx === count($event_waiting_list) - 1 ? 'disabled' : ''; ?>>▼</button>
                    <input type="number" class="sp-waiting-pos-input" min="1" max="<?php echo esc_attr(count($event_waiting_list)); ?>" value="<?php echo esc_attr($entry->position); ?>" style="width: 56px;" title="<?php esc_attr_e('الترتيب', 'saint-porphyrius'); ?>">
                    <button type="button" class="sp-btn sp-btn-sm sp-btn-outline sp-btn-danger sp-waiting-remove" title="<?php esc_attr_e('حذف', 'saint-porphyrius'); ?>">🗑️</button>
                </div>
            <?php endforeach; ?>
            </div>
            <div style="padding: 8px 16px; font-size: var(--sp-font-size-xs); color: var(--sp-text-secondary);">
                <?php esc_html_e('استخدم الأسهم أو غيّر رقم الترتيب ثم اضغط Enter لإعادة ترتيب الانتظار. زر "معالجة الآن" يحاول حجز المقاعد المتاحة لمن في الدور.', 'saint-porphyrius'); ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Bus Activity History (Audit Log) -->
    <?php
    $audit_rows = $bus_handler->get_audit_log(array('event_id' => $bus->event_id, 'limit' => 200));
    $action_meta = array(
        'book'         => array('📗', __('حجز مقعد', 'saint-porphyrius')),
        'cancel'       => array('🗑️', __('إلغاء حجز', 'saint-porphyrius')),
        'checkin'      => array('✅', __('تسجيل صعود', 'saint-porphyrius')),
        'join_queue'   => array('📋', __('دخول قائمة الانتظار', 'saint-porphyrius')),
        'leave_queue'  => array('🚪', __('خروج من الانتظار', 'saint-porphyrius')),
        'auto_offer'   => array('🔔', __('عرض مقعد (تلقائي)', 'saint-porphyrius')),
        'approve'      => array('👍', __('موافقة على عرض', 'saint-porphyrius')),
        'reject'       => array('✖', __('رفض عرض', 'saint-porphyrius')),
        'skip'         => array('⏭', __('تخطّي عرض', 'saint-porphyrius')),
        'move_seat'    => array('🔄', __('نقل/تبديل مقعد', 'saint-porphyrius')),
        'admin_remove' => array('❌', __('حذف من الانتظار (مسؤول)', 'saint-porphyrius')),
        'admin_move'   => array('↕️', __('تغيير ترتيب الانتظار', 'saint-porphyrius')),
    );
    ?>
    <div class="sp-section">
        <div class="sp-card" style="padding: 0; overflow: hidden;">
            <div style="padding: 12px 16px; background: var(--sp-background); border-bottom: 1px solid var(--sp-border); display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap;">
                <h3 style="margin: 0; font-size: var(--sp-font-size-md);">🕓 <?php esc_html_e('سجل النشاط (لكل مقعد وكل شخص)', 'saint-porphyrius'); ?></h3>
                <input type="text" id="sp-audit-filter" placeholder="<?php esc_attr_e('بحث بالاسم أو رقم المقعد…', 'saint-porphyrius'); ?>" style="flex: 0 1 220px; padding: 6px 10px; border: 1px solid var(--sp-border); border-radius: 8px;">
            </div>
            <?php if (empty($audit_rows)): ?>
            <div style="padding: 16px; color: var(--sp-text-secondary); font-size: var(--sp-font-size-sm);"><?php esc_html_e('لا يوجد نشاط بعد.', 'saint-porphyrius'); ?></div>
            <?php else: ?>
            <div id="sp-audit-list" style="max-height: 440px; overflow: auto;">
            <?php foreach ($audit_rows as $row):
                $meta  = isset($action_meta[$row->action]) ? $action_meta[$row->action] : array('•', $row->action);
                $subj  = $row->subject_name_ar ?: ($row->subject_name ?: ('#' . $row->user_id));
                $actor = $row->actor_id ? ($row->actor_name ?: ('#' . $row->actor_id)) : __('النظام', 'saint-porphyrius');
                $search = function_exists('mb_strtolower') ? mb_strtolower(trim($subj . ' ' . $row->seat_label . ' ' . $meta[1] . ' ' . $actor)) : strtolower(trim($subj . ' ' . $row->seat_label . ' ' . $meta[1] . ' ' . $actor));
            ?>
                <div class="sp-audit-row" data-search="<?php echo esc_attr($search); ?>" style="display: flex; gap: 10px; padding: 10px 16px; border-bottom: 1px solid var(--sp-border); font-size: var(--sp-font-size-sm);">
                    <span style="font-size: 16px;"><?php echo esc_html($meta[0]); ?></span>
                    <div style="flex: 1; min-width: 0;">
                        <div><strong><?php echo esc_html($meta[1]); ?></strong><?php if ($row->seat_label): ?> · <span style="color: var(--sp-primary); font-weight: 600;"><?php echo esc_html($row->seat_label); ?></span><?php endif; ?></div>
                        <div style="font-size: var(--sp-font-size-xs); color: var(--sp-text-secondary);">
                            <?php if ($row->user_id) { echo esc_html($subj); } ?>
                            <?php if ($row->actor_id && (int) $row->actor_id !== (int) $row->user_id): ?>
                                — <?php printf(esc_html__('بواسطة %s', 'saint-porphyrius'), esc_html($actor)); ?>
                            <?php elseif (!$row->actor_id): ?>
                                — <?php esc_html_e('تلقائي', 'saint-porphyrius'); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="font-size: var(--sp-font-size-xs); color: var(--sp-text-secondary); white-space: nowrap;">
                        <?php echo esc_html(date_i18n('j M H:i', strtotime($row->created_at))); ?>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="sp-section sp-quick-actions">
        <a href="<?php echo home_url('/app/admin/events?action=edit&id=' . $event->id); ?>" class="sp-btn sp-btn-outline sp-btn-block">
            ← <?php _e('العودة للفعالية', 'saint-porphyrius'); ?>
        </a>
    </div>
</main>
<div id="booking-detail-modal" class="sp-modal" style="display: none;">
    <div class="sp-modal-overlay"></div>
    <div class="sp-modal-content">
        <div class="sp-modal-header">
            <h3><?php _e('تفاصيل الحجز', 'saint-porphyrius'); ?></h3>
            <button type="button" class="sp-modal-close">&times;</button>
        </div>
        <div class="sp-modal-body">
            <div class="sp-booking-detail-card">
                <div class="sp-detail-row">
                    <span class="sp-detail-label"><?php _e('الاسم:', 'saint-porphyrius'); ?></span>
                    <span class="sp-detail-value" id="modal-user-name"></span>
                </div>
                <div class="sp-detail-row">
                    <span class="sp-detail-label"><?php _e('المقعد:', 'saint-porphyrius'); ?></span>
                    <span class="sp-detail-value" id="modal-seat-label"></span>
                </div>
            </div>
        </div>
        <div class="sp-modal-footer sp-modal-footer-stack">
            <button type="button" class="sp-btn sp-btn-success sp-btn-block" id="modal-checkin-btn">
                ✅ <?php _e('تسجيل الصعود', 'saint-porphyrius'); ?>
            </button>
            <button type="button" class="sp-btn sp-btn-primary sp-btn-block" id="modal-move-btn">
                🔄 <?php _e('نقل المقعد', 'saint-porphyrius'); ?>
            </button>
            <button type="button" class="sp-btn sp-btn-outline sp-btn-danger sp-btn-block" id="modal-cancel-btn">
                🗑️ <?php _e('إلغاء الحجز', 'saint-porphyrius'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Move Mode Banner -->
<div id="move-mode-banner" class="sp-move-mode-banner" style="display: none;">
    <div class="sp-move-banner-content">
        <span class="sp-move-banner-text">
            <strong>🔄 <?php _e('وضع النقل', 'saint-porphyrius'); ?></strong>
            <span id="move-mode-user"></span>
        </span>
        <button type="button" class="sp-btn sp-btn-sm sp-btn-outline" id="cancel-move-btn">
            <?php _e('إلغاء', 'saint-porphyrius'); ?>
        </button>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var currentBookingId = null;
    var currentUserName = null;
    var currentSeatLabel = null;
    var isMoveMode = false;
    
    // Seat Click - Show Detail Modal (only when not in move mode)
    $(document).on('click', '.sp-bus-seat.booked', function(e) {
        // If in move mode, check if this is a swap target
        if (isMoveMode) {
            if ($(this).hasClass('sp-swap-target')) {
                // Let the swap handler handle this
                handleSwap($(this));
            }
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
        
        var bookingId = $(this).data('booking-id');
        var userName = $(this).data('user-name');
        var seatLabel = $(this).data('seat-label');
        var isCheckedIn = $(this).hasClass('checked-in');
        
        currentBookingId = bookingId;
        currentUserName = userName;
        currentSeatLabel = seatLabel;
        
        $('#modal-user-name').text(userName);
        $('#modal-seat-label').text(seatLabel);
        
        if (isCheckedIn) {
            $('#modal-checkin-btn').hide();
        } else {
            $('#modal-checkin-btn').show();
        }
        
        $('#booking-detail-modal').fadeIn(200);
    });
    
    // Handle swap when clicking on another booked seat
    function handleSwap($seat) {
        if (!currentBookingId) return;
        
        var newRow = $seat.data('row');
        var newSeatNum = $seat.data('seat');
        var newLabel = $seat.data('seat-label');
        var otherUserName = $seat.data('user-name');
        
        // Confirm swap
        if (!confirm('<?php _e('هل تريد تبديل المقاعد؟', 'saint-porphyrius'); ?>\n\n' + 
                     currentUserName + ' (' + currentSeatLabel + ') ↔ ' + otherUserName + ' (' + newLabel + ')')) {
            return;
        }
        
        // Perform swap (same endpoint, backend handles it)
        moveSeat(currentBookingId, newRow, newSeatNum);
    }
    
    // Close Modal
    $(document).on('click', '.sp-modal-close, .sp-modal-overlay', function() {
        $('#booking-detail-modal').fadeOut(200);
        currentBookingId = null;
    });
    
    // Check-in from modal
    $(document).on('click', '#modal-checkin-btn', function() {
        if (!currentBookingId) return;
        checkinBooking(currentBookingId);
    });
    
    // Cancel from modal
    $(document).on('click', '#modal-cancel-btn', function() {
        if (!currentBookingId) return;
        if (confirm('<?php _e('هل أنت متأكد من إلغاء هذا الحجز؟', 'saint-porphyrius'); ?>')) {
            cancelBooking(currentBookingId);
        }
    });
    
    // Check-in from list
    $(document).on('click', '.sp-checkin-btn', function() {
        var bookingId = $(this).data('booking-id');
        checkinBooking(bookingId);
    });
    
    // Cancel from list
    $(document).on('click', '.sp-cancel-booking-btn', function() {
        var bookingId = $(this).data('booking-id');
        var userName = $(this).data('user-name');
        if (confirm('<?php _e('هل أنت متأكد من إلغاء حجز', 'saint-porphyrius'); ?> ' + userName + '?')) {
            cancelBooking(bookingId);
        }
    });
    
    function checkinBooking(bookingId) {
        $.ajax({
            url: spApp.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sp_checkin_bus_passenger',
                nonce: spApp.nonce,
                booking_id: bookingId
            },
            success: function(response) {
                if (response.success) {
                    // Update UI
                    var $seat = $('.sp-bus-seat[data-booking-id="' + bookingId + '"]');
                    $seat.addClass('checked-in');
                    $seat.find('.sp-seat-occupant').text('✅');
                    
                    var $listItem = $('.sp-booking-item[data-booking-id="' + bookingId + '"]');
                    $listItem.addClass('checked-in');
                    $listItem.find('.sp-checkin-btn').replaceWith('<span class="sp-badge sp-badge-success"><?php _e('تم', 'saint-porphyrius'); ?></span>');
                    $listItem.find('.sp-booking-meta').append(' • <span style="color: var(--sp-success);"><?php _e('✅ صعد', 'saint-porphyrius'); ?></span>');
                    
                    // Update counter
                    var $counter = $('.sp-checkin-count');
                    $counter.text(parseInt($counter.text()) + 1);
                    
                    $('#booking-detail-modal').fadeOut(200);
                } else {
                    alert(response.data.message || '<?php _e('حدث خطأ', 'saint-porphyrius'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('حدث خطأ في الاتصال', 'saint-porphyrius'); ?>');
            }
        });
    }
    
    function cancelBooking(bookingId) {
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
                }
            },
            error: function() {
                alert('<?php _e('حدث خطأ في الاتصال', 'saint-porphyrius'); ?>');
            }
        });
    }
    
    // ========================================
    // MOVE SEAT FUNCTIONALITY
    // ========================================
    
    // Enter move mode
    $(document).on('click', '#modal-move-btn', function() {
        if (!currentBookingId) return;
        
        // Close modal
        $('#booking-detail-modal').fadeOut(200);
        
        // Enter move mode
        isMoveMode = true;
        $('body').addClass('sp-move-mode-active');
        
        // Show banner with user info
        $('#move-mode-user').text('<?php _e('نقل', 'saint-porphyrius'); ?> ' + currentUserName + ' <?php _e('من', 'saint-porphyrius'); ?> ' + currentSeatLabel);
        $('#move-mode-banner').slideDown(200);
        
        // Highlight available (empty) seats as move targets
        $('.sp-bus-seat.empty').addClass('sp-move-target');
        
        // Highlight other booked seats as swap targets
        $('.sp-bus-seat.booked').not('[data-booking-id="' + currentBookingId + '"]').addClass('sp-swap-target');
        
        // Highlight current seat being moved
        $('.sp-bus-seat[data-booking-id="' + currentBookingId + '"]').addClass('sp-move-source');
    });
    
    // Cancel move mode
    $(document).on('click', '#cancel-move-btn', function() {
        exitMoveMode();
    });
    
    // Click on empty seat while in move mode (MOVE)
    $(document).on('click', '.sp-bus-seat.empty.sp-move-target', function() {
        if (!isMoveMode || !currentBookingId) return;
        
        var $seat = $(this);
        var newRow = $seat.data('row');
        var newSeatNum = $seat.data('seat');
        var newLabel = $seat.data('label');
        
        // Confirm move
        if (!confirm('<?php _e('هل تريد نقل', 'saint-porphyrius'); ?> ' + currentUserName + ' <?php _e('من', 'saint-porphyrius'); ?> ' + currentSeatLabel + ' <?php _e('إلى', 'saint-porphyrius'); ?> ' + newLabel + '?')) {
            return;
        }
        
        // Perform move
        moveSeat(currentBookingId, newRow, newSeatNum);
    });
    
    function moveSeat(bookingId, newRow, newSeat) {
        $.ajax({
            url: spApp.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sp_move_bus_seat',
                nonce: spApp.nonce,
                booking_id: bookingId,
                new_row: newRow,
                new_seat: newSeat
            },
            beforeSend: function() {
                $('#move-mode-banner').find('.sp-btn').prop('disabled', true).text('<?php _e('جاري النقل...', 'saint-porphyrius'); ?>');
            },
            success: function(response) {
                if (response.success) {
                    // Show success and reload
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message || '<?php _e('حدث خطأ', 'saint-porphyrius'); ?>');
                    exitMoveMode();
                }
            },
            error: function() {
                alert('<?php _e('حدث خطأ في الاتصال', 'saint-porphyrius'); ?>');
                exitMoveMode();
            }
        });
    }
    
    function exitMoveMode() {
        isMoveMode = false;
        currentBookingId = null;
        currentUserName = null;
        currentSeatLabel = null;
        
        $('body').removeClass('sp-move-mode-active');
        $('#move-mode-banner').slideUp(200);
        $('.sp-bus-seat').removeClass('sp-move-target sp-move-source sp-swap-target');
        $('#cancel-move-btn').prop('disabled', false).text('<?php _e('إلغاء', 'saint-porphyrius'); ?>');
    }
    
    // ESC key to exit move mode
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && isMoveMode) {
            exitMoveMode();
        }
    });
    
    // ==========================================
    // WAITING LIST MANAGEMENT
    // ==========================================
    function reloadPage() { window.location.reload(); }
    
    function moveWaitingEntry(entryId, newPosition) {
        return $.ajax({
            url: spApp.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sp_admin_move_waiting_entry',
                nonce: spApp.nonce,
                entry_id: entryId,
                new_position: newPosition
            }
        });
    }
    
    $(document).on('click', '.sp-waiting-up, .sp-waiting-down', function() {
        var $row = $(this).closest('.sp-waiting-row');
        var entryId = $row.data('entry-id');
        var $input = $row.find('.sp-waiting-pos-input');
        var current = parseInt($input.val(), 10) || 1;
        var newPos = $(this).hasClass('sp-waiting-up') ? current - 1 : current + 1;
        if (newPos < 1) return;
        moveWaitingEntry(entryId, newPos).done(function(res) {
            if (res && res.success) reloadPage();
            else alert((res && res.data && res.data.message) || '<?php echo esc_js(__('فشل التحديث', 'saint-porphyrius')); ?>');
        });
    });
    
    $(document).on('change', '.sp-waiting-pos-input', function() {
        var $row = $(this).closest('.sp-waiting-row');
        var entryId = $row.data('entry-id');
        var newPos = parseInt($(this).val(), 10) || 1;
        moveWaitingEntry(entryId, newPos).done(function(res) {
            if (res && res.success) reloadPage();
            else alert((res && res.data && res.data.message) || '<?php echo esc_js(__('فشل التحديث', 'saint-porphyrius')); ?>');
        });
    });
    
    $(document).on('click', '.sp-waiting-remove', function() {
        if (!confirm('<?php echo esc_js(__('هل أنت متأكد من حذف هذا السجل من قائمة الانتظار؟', 'saint-porphyrius')); ?>')) return;
        var $row = $(this).closest('.sp-waiting-row');
        var entryId = $row.data('entry-id');
        $.ajax({
            url: spApp.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sp_admin_remove_waiting_entry',
                nonce: spApp.nonce,
                entry_id: entryId
            }
        }).done(function(res) {
            if (res && res.success) reloadPage();
            else alert((res && res.data && res.data.message) || '<?php echo esc_js(__('فشل الحذف', 'saint-porphyrius')); ?>');
        });
    });
    
    $(document).on('click', '#sp-process-waiting-list-btn', function() {
        var $btn = $(this);
        var eventId = $btn.data('event-id');
        $btn.prop('disabled', true).text('...');
        $.ajax({
            url: spApp.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sp_admin_process_waiting_list',
                nonce: spApp.nonce,
                event_id: eventId
            }
        }).done(function(res) {
            if (res && res.success) {
                alert(res.data.message || '<?php echo esc_js(__('تمت المعالجة', 'saint-porphyrius')); ?>');
                reloadPage();
            } else {
                alert((res && res.data && res.data.message) || '<?php echo esc_js(__('فشل التشغيل', 'saint-porphyrius')); ?>');
                $btn.prop('disabled', false).text('🔄 <?php echo esc_js(__('معالجة الآن', 'saint-porphyrius')); ?>');
            }
        });
    });

    // ========================================
    // SEAT OFFER APPROVAL (Accept / Reject / Skip)
    // ========================================
    function offerAction(action, offerId, $btn, confirmMsg) {
        if (confirmMsg && !confirm(confirmMsg)) return;
        var $row = $btn.closest('.sp-offer-row');
        $row.find('button').prop('disabled', true);
        $.ajax({
            url: spApp.ajaxUrl,
            type: 'POST',
            data: { action: action, nonce: spApp.nonce, offer_id: offerId }
        }).done(function(res) {
            if (res && res.success) {
                alert((res.data && res.data.message) || '<?php echo esc_js(__('تم', 'saint-porphyrius')); ?>');
                reloadPage();
            } else {
                alert((res && res.data && res.data.message) || '<?php echo esc_js(__('فشل الإجراء', 'saint-porphyrius')); ?>');
                $row.find('button').prop('disabled', false);
            }
        }).fail(function() {
            alert('<?php echo esc_js(__('حدث خطأ في الاتصال', 'saint-porphyrius')); ?>');
            $row.find('button').prop('disabled', false);
        });
    }
    $(document).on('click', '.sp-offer-accept', function() {
        offerAction('sp_admin_accept_offer', $(this).data('offer-id'), $(this), null);
    });
    $(document).on('click', '.sp-offer-reject', function() {
        offerAction('sp_admin_reject_offer', $(this).data('offer-id'), $(this), '<?php echo esc_js(__('رفض الطلب وإزالة العضو من قائمة الانتظار؟', 'saint-porphyrius')); ?>');
    });
    $(document).on('click', '.sp-offer-skip', function() {
        offerAction('sp_admin_skip_offer', $(this).data('offer-id'), $(this), null);
    });

    // ========================================
    // AUDIT LOG — client-side filter (per seat / per person)
    // ========================================
    $(document).on('input', '#sp-audit-filter', function() {
        var q = $(this).val().toLowerCase().trim();
        $('#sp-audit-list .sp-audit-row').each(function() {
            var hay = ($(this).data('search') || '').toString();
            $(this).toggle(q === '' || hay.indexOf(q) !== -1);
        });
    });
});
</script>

<style>
/* Move Mode Banner - Fixed at top */
.sp-move-mode-banner {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, #3B82F6, #2563EB);
    color: white;
    padding: 12px 16px;
    z-index: 1000;
    box-shadow: 0 4px 20px rgba(59, 130, 246, 0.4);
}

.sp-move-banner-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 600px;
    margin: 0 auto;
    gap: 12px;
}

.sp-move-banner-text {
    display: flex;
    flex-direction: column;
    font-size: 14px;
    flex: 1;
}

.sp-move-banner-text strong {
    font-size: 16px;
    margin-bottom: 2px;
}

#move-mode-user {
    opacity: 0.9;
    font-size: 13px;
}

.sp-move-mode-banner .sp-btn {
    white-space: nowrap;
    background: rgba(255,255,255,0.2);
    border-color: rgba(255,255,255,0.4);
    color: white;
}

.sp-move-mode-banner .sp-btn:hover {
    background: rgba(255,255,255,0.3);
}

/* Move Mode Active State */
body.sp-move-mode-active .sp-page-content {
    padding-top: 70px;
}

/* Move Target (empty seats in move mode) */
.sp-bus-seat.sp-move-target {
    background: linear-gradient(135deg, #10B981, #059669) !important;
    border: 2px dashed #059669 !important;
    animation: sp-pulse-move 1.5s infinite;
    cursor: pointer !important;
}

.sp-bus-seat.sp-move-target:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.5);
}

.sp-bus-seat.sp-move-target .sp-seat-label {
    color: white !important;
    font-weight: 600;
}

/* Move Source (current seat being moved) */
.sp-bus-seat.sp-move-source {
    background: linear-gradient(135deg, #F59E0B, #D97706) !important;
    border: 2px solid #D97706 !important;
    animation: sp-pulse-source 1s infinite;
}

.sp-bus-seat.sp-move-source .sp-seat-label {
    color: white !important;
}

@keyframes sp-pulse-move {
    0%, 100% { opacity: 0.8; }
    50% { opacity: 1; }
}

@keyframes sp-pulse-source {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

/* Swap Target (other booked seats in move mode) */
.sp-bus-seat.sp-swap-target {
    background: linear-gradient(135deg, #8B5CF6, #7C3AED) !important;
    border: 2px dashed #7C3AED !important;
    animation: sp-pulse-swap 1.5s infinite;
    cursor: pointer !important;
    opacity: 1 !important;
    pointer-events: auto !important;
}

.sp-bus-seat.sp-swap-target:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.5);
}

.sp-bus-seat.sp-swap-target .sp-seat-label {
    color: white !important;
    font-weight: 600;
}

.sp-bus-seat.sp-swap-target .sp-seat-occupant {
    display: none;
}

.sp-bus-seat.sp-swap-target::after {
    content: '↔';
    font-size: 14px;
}

@keyframes sp-pulse-swap {
    0%, 100% { opacity: 0.85; }
    50% { opacity: 1; }
}

/* Modal footer for move button */
.sp-modal-footer-stack {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.sp-modal-footer-stack .sp-btn {
    margin: 0;
}

/* In move mode, dim only non-interactive seats */
body.sp-move-mode-active .sp-bus-seat.booked:not(.sp-move-source):not(.sp-swap-target) {
    opacity: 0.3;
    pointer-events: none;
}

/* Bus Info Card */
.sp-bus-info-card {
    margin-bottom: var(--sp-space-lg);
}

.sp-bus-header {
    display: flex;
    gap: var(--sp-space-md);
    margin-bottom: var(--sp-space-lg);
}

.sp-bus-icon-large {
    width: 64px;
    height: 64px;
    border-radius: var(--sp-radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    flex-shrink: 0;
}

.sp-bus-details h2 {
    margin: 0;
    font-size: var(--sp-font-size-lg);
}

.sp-bus-meta {
    display: flex;
    gap: var(--sp-space-md);
    font-size: var(--sp-font-size-sm);
    color: var(--sp-text-secondary);
    margin-top: 4px;
}

.sp-bus-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: var(--sp-space-sm);
    text-align: center;
}

.sp-stat-item {
    background: white;
    padding: var(--sp-space-md);
    border-radius: var(--sp-radius-md);
}

.sp-stat-value {
    display: block;
    font-size: var(--sp-font-size-xl);
    font-weight: 700;
    color: var(--sp-primary);
}

.sp-stat-label {
    font-size: var(--sp-font-size-xs);
    color: var(--sp-text-secondary);
}

/* Admin Seat Map - International Bus Layout */
.sp-admin-seat-map {
    overflow: hidden;
}

.sp-bus-visual {
    background: linear-gradient(180deg, #F8FAFC 0%, #F1F5F9 100%);
    border: 3px solid var(--bus-color, #3B82F6);
    border-radius: 24px 24px 16px 16px;
    padding: var(--sp-space-md);
    margin-bottom: var(--sp-space-lg);
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

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

.sp-bus-seats {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.sp-seat-empty-space {
    width: 48px;
    height: 56px;
}

.sp-bus-seat {
    width: 100%;
    min-width: 42px;
    height: 52px;
    border: 2px solid #CBD5E1;
    border-radius: 8px 8px 4px 4px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    transition: all 0.2s ease;
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

.sp-bus-seat.driver {
    background: linear-gradient(180deg, #E2E8F0 0%, #CBD5E1 100%);
    border-color: #94A3B8;
    cursor: default;
}

.sp-bus-seat.driver::before {
    background: #94A3B8;
}

.sp-bus-seat.empty {
    background: linear-gradient(180deg, #FFFFFF 0%, #F1F5F9 100%);
}

.sp-bus-seat.empty.after-aisle {
    margin-right: 10px;
}

.sp-bus-seat.back-seat {
    min-width: 38px;
    height: 48px;
}

.sp-bus-seat.booked {
    background: linear-gradient(180deg, #FEF3C7 0%, #FDE68A 100%);
    border-color: #F59E0B;
    cursor: pointer;
}

/* Held seat — pending an admin-approval offer. Indigo + dashed, clearly distinct
   from the amber "booked" seat so the admin can spot reserved-for-review seats. */
.sp-bus-seat.held-seat {
    background: linear-gradient(180deg, #EEF2FF 0%, #C7D2FE 100%);
    border: 2px dashed #6366F1;
    cursor: not-allowed;
}
.sp-bus-seat.held-seat::before {
    background: #6366F1;
}
.sp-bus-seat.held-seat .sp-seat-label {
    color: #3730A3;
}

.sp-bus-seat.booked::before {
    background: #F59E0B;
}

.sp-bus-seat.booked:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.sp-bus-seat.checked-in {
    background: linear-gradient(180deg, #DCFCE7 0%, #BBF7D0 100%);
    border-color: #22C55E;
}

.sp-bus-seat.checked-in::before {
    background: #22C55E;
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

.sp-bus-back {
    background: #E2E8F0;
    border-radius: 8px;
    padding: var(--sp-space-sm);
    text-align: center;
    margin-top: var(--sp-space-md);
}

.sp-back-seats-label {
    font-size: var(--sp-font-size-xs);
    color: #64748B;
}

/* Legend */
.sp-bus-legend {
    display: flex;
    justify-content: center;
    gap: var(--sp-space-lg);
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
}

.sp-legend-seat.empty {
    background: linear-gradient(180deg, #FFFFFF 0%, #F1F5F9 100%);
}

.sp-legend-seat.booked {
    background: linear-gradient(180deg, #FEF3C7 0%, #FDE68A 100%);
    border-color: #F59E0B;
}

.sp-legend-seat.checked-in {
    background: linear-gradient(180deg, #DCFCE7 0%, #BBF7D0 100%);
    border-color: #22C55E;
}

.sp-legend-seat.blocked {
    background: linear-gradient(180deg, #F3F4F6 0%, #E5E7EB 100%);
    border-color: #9CA3AF;
    opacity: 0.5;
}

/* Bookings List */
.sp-bookings-list-card {
    padding: 0;
    overflow: hidden;
}

.sp-booking-item {
    display: flex;
    align-items: center;
    gap: var(--sp-space-md);
    padding: var(--sp-space-md);
    border-bottom: 1px solid var(--sp-border);
}

.sp-booking-item:last-child {
    border-bottom: none;
}

.sp-booking-item.checked-in {
    background: var(--sp-success-light);
}

.sp-booking-seat-badge {
    width: 40px;
    height: 40px;
    border-radius: var(--sp-radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: var(--sp-font-size-sm);
    flex-shrink: 0;
}

.sp-booking-info {
    flex: 1;
    min-width: 0;
}

.sp-booking-name {
    font-weight: 600;
    color: var(--sp-text-primary);
}

.sp-booking-meta {
    font-size: var(--sp-font-size-xs);
    color: var(--sp-text-secondary);
}

.sp-booking-actions {
    display: flex;
    gap: var(--sp-space-xs);
    flex-shrink: 0;
}

/* Modal Styles */
.sp-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--sp-space-lg);
}

.sp-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
}

.sp-modal-content {
    position: relative;
    background: white;
    border-radius: var(--sp-radius-lg);
    width: 100%;
    max-width: 400px;
    overflow: hidden;
}

.sp-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--sp-space-md) var(--sp-space-lg);
    border-bottom: 1px solid var(--sp-border);
}

.sp-modal-header h3 {
    margin: 0;
    font-size: var(--sp-font-size-lg);
}

.sp-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: var(--sp-text-secondary);
    cursor: pointer;
    padding: 0;
    line-height: 1;
}

.sp-modal-body {
    padding: var(--sp-space-lg);
}

.sp-detail-row {
    display: flex;
    justify-content: space-between;
    padding: var(--sp-space-sm) 0;
    border-bottom: 1px solid var(--sp-border);
}

.sp-detail-row:last-child {
    border-bottom: none;
}

.sp-detail-label {
    color: var(--sp-text-secondary);
}

.sp-detail-value {
    font-weight: 600;
}

.sp-modal-footer {
    padding: var(--sp-space-lg);
    display: flex;
    gap: var(--sp-space-sm);
    border-top: 1px solid var(--sp-border);
}

.sp-modal-footer .sp-btn {
    flex: 1;
}

/* Empty State */
.sp-empty-state {
    text-align: center;
    padding: var(--sp-space-xl);
}

.sp-empty-icon {
    font-size: 48px;
    margin-bottom: var(--sp-space-md);
}

/* Quick Actions */
.sp-quick-actions {
    padding-bottom: var(--sp-space-xl);
}

/* Button Success */
.sp-btn-success {
    background: var(--sp-success);
    color: white;
    border-color: var(--sp-success);
}

.sp-btn-success:hover {
    background: #16a34a;
}

/* Badge Success */
.sp-badge-success {
    background: var(--sp-success-light);
    color: var(--sp-success);
}
</style>
