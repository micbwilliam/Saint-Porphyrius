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
        <div class="sp-bus-stats">
            <div class="sp-stat-item">
                <span class="sp-stat-value"><?php echo count($bookings); ?></span>
                <span class="sp-stat-label"><?php _e('محجوز', 'saint-porphyrius'); ?></span>
            </div>
            <div class="sp-stat-item">
                <span class="sp-stat-value"><?php echo $bus->capacity - count($bookings); ?></span>
                <span class="sp-stat-label"><?php _e('متاح', 'saint-porphyrius'); ?></span>
            </div>
            <div class="sp-stat-item">
                <span class="sp-stat-value"><?php echo $bus->capacity; ?></span>
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
                
                <!-- Driver Row (Row 1) - Driver on left side -->
                <div class="sp-bus-row sp-driver-row">
                    <div class="sp-row-label">1</div>
                    <div class="sp-row-seats" style="grid-template-columns: repeat(<?php echo $seat_map['seats_per_row']; ?>, 1fr);">
                        <?php 
                        $driver_seats = $seat_map['driver_seats'] ?? 1;
                        // Empty space on right (appears left in RTL)
                        for ($e = $driver_seats; $e < $seat_map['seats_per_row']; $e++): ?>
                            <div class="sp-seat-empty-space"></div>
                        <?php endfor; ?>
                        <?php
                        // Passenger seats beside driver (Row 1) - in reverse order
                        for ($d = $driver_seats; $d >= 2; $d--):
                            $key = '1-' . $d;
                            $booking = isset($bookings_by_seat[$key]) ? $bookings_by_seat[$key] : null;
                            $seat_label = $bus_handler->generate_seat_label(1, $d, $seat_map['aisle_position']);
                        ?>
                            <?php if ($booking): ?>
                            <button type="button" 
                                    class="sp-bus-seat booked <?php echo $booking->status === 'checked_in' ? 'checked-in' : ''; ?>"
                                    data-booking-id="<?php echo esc_attr($booking->id); ?>"
                                    data-user-id="<?php echo esc_attr($booking->user_id); ?>"
                                    data-seat-label="<?php echo esc_attr($seat_label); ?>"
                                    data-user-name="<?php echo esc_attr($booking->name_ar ?: $booking->display_name); ?>">
                                <span class="sp-seat-label"><?php echo esc_html($seat_label); ?></span>
                                <span class="sp-seat-occupant"><?php echo $booking->status === 'checked_in' ? '✅' : '👤'; ?></span>
                            </button>
                            <?php else: ?>
                            <div class="sp-bus-seat empty"
                                 data-row="1"
                                 data-seat="<?php echo esc_attr($d); ?>"
                                 data-label="<?php echo esc_attr($seat_label); ?>">
                                <span class="sp-seat-label"><?php echo esc_html($seat_label); ?></span>
                            </div>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <!-- Driver Seat (not bookable) - on left side -->
                        <div class="sp-bus-seat driver" title="<?php _e('السائق', 'saint-porphyrius'); ?>">
                            <span class="sp-seat-icon">👨‍✈️</span>
                        </div>
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
                                $is_aisle = ($seat == $seat_map['aisle_position']);
                                $seat_label = $bus_handler->generate_seat_label($row, $seat, $seat_map['aisle_position']);
                                $aisle_class = $is_aisle ? ' after-aisle' : '';
                            ?>
                                <?php if ($booking): ?>
                                <button type="button" 
                                        class="sp-bus-seat booked<?php echo $aisle_class; ?> <?php echo $booking->status === 'checked_in' ? 'checked-in' : ''; ?>"
                                        data-booking-id="<?php echo esc_attr($booking->id); ?>"
                                        data-user-id="<?php echo esc_attr($booking->user_id); ?>"
                                        data-seat-label="<?php echo esc_attr($seat_label); ?>"
                                        data-user-name="<?php echo esc_attr($booking->name_ar ?: $booking->display_name); ?>">
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
                            $seat_label = $bus_handler->generate_seat_label($back_row, $seat, $seat_map['aisle_position']);
                        ?>
                            <?php if ($booking): ?>
                            <button type="button" 
                                    class="sp-bus-seat booked back-seat <?php echo $booking->status === 'checked_in' ? 'checked-in' : ''; ?>"
                                    data-booking-id="<?php echo esc_attr($booking->id); ?>"
                                    data-user-id="<?php echo esc_attr($booking->user_id); ?>"
                                    data-seat-label="<?php echo esc_attr($seat_label); ?>"
                                    data-user-name="<?php echo esc_attr($booking->name_ar ?: $booking->display_name); ?>">
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
            <div class="sp-booking-item <?php echo $booking->status === 'checked_in' ? 'checked-in' : ''; ?>" 
                 data-booking-id="<?php echo esc_attr($booking->id); ?>">
                <div class="sp-booking-seat-badge" style="background: <?php echo esc_attr($bus->color); ?>;">
                    <?php echo esc_html($booking->seat_label); ?>
                </div>
                <div class="sp-booking-info">
                    <div class="sp-booking-name"><?php echo esc_html($booking->name_ar ?: $booking->display_name); ?></div>
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
                            data-user-name="<?php echo esc_attr($booking->name_ar ?: $booking->display_name); ?>">
                        🗑️
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Quick Actions -->
    <div class="sp-section sp-quick-actions">
        <a href="<?php echo home_url('/app/admin/events?action=edit&id=' . $event->id); ?>" class="sp-btn sp-btn-outline sp-btn-block">
            ← <?php _e('العودة للفعالية', 'saint-porphyrius'); ?>
        </a>
    </div>
</main>

<!-- Booking Detail Modal -->
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
        <div class="sp-modal-footer">
            <button type="button" class="sp-btn sp-btn-success sp-modal-checkin" id="modal-checkin-btn">
                ✅ <?php _e('تسجيل الصعود', 'saint-porphyrius'); ?>
            </button>
            <button type="button" class="sp-btn sp-btn-outline sp-btn-danger sp-modal-cancel" id="modal-cancel-btn">
                🗑️ <?php _e('إلغاء الحجز', 'saint-porphyrius'); ?>
            </button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var currentBookingId = null;
    
    // Seat Click - Show Detail Modal
    $(document).on('click', '.sp-bus-seat.booked', function() {
        var bookingId = $(this).data('booking-id');
        var userName = $(this).data('user-name');
        var seatLabel = $(this).data('seat-label');
        var isCheckedIn = $(this).hasClass('checked-in');
        
        currentBookingId = bookingId;
        
        $('#modal-user-name').text(userName);
        $('#modal-seat-label').text(seatLabel);
        
        if (isCheckedIn) {
            $('#modal-checkin-btn').hide();
        } else {
            $('#modal-checkin-btn').show();
        }
        
        $('#booking-detail-modal').fadeIn(200);
    });
    
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
});
</script>

<style>
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
