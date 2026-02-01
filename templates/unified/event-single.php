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

    <!-- Excuse Section (Mandatory Events Only) -->
    <?php if ($event->is_mandatory): 
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
});
</script>

<style>
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
