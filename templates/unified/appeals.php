<?php
/**
 * Saint Porphyrius - User Appeals Page (Mobile)
 * Users can submit and view their point appeals
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$appeals_handler = SP_Appeals::get_instance();
$user_appeals = $appeals_handler->get_user_appeals($user_id);
$appealable_events = $appeals_handler->get_appealable_events($user_id);
?>

<!-- Header -->
<div class="sp-unified-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('طلب نقاط فعالية', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-spacer"></div>
    </div>
</div>

<!-- Main Content -->
<main class="sp-page-content">

    <!-- Info Card -->
    <div class="sp-card sp-appeal-info-card">
        <div class="sp-appeal-info-icon">📋</div>
        <div class="sp-appeal-info-content">
            <h3><?php _e('طلب نقاط فعالية لم أحصل عليها', 'saint-porphyrius'); ?></h3>
            <p><?php _e('إذا حضرت فعالية ولم تتمكن من مسح رمز QR للحضور، يمكنك طلب إضافة النقاط يدوياً.', 'saint-porphyrius'); ?></p>
        </div>
    </div>

    <!-- Submit New Appeal -->
    <?php if (!empty($appealable_events)): ?>
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title"><?php _e('تقديم طلب جديد', 'saint-porphyrius'); ?></h3>
        </div>
        
        <div class="sp-card">
            <form id="sp-appeal-form" class="sp-appeal-form">
                <?php wp_nonce_field('sp_nonce', 'sp_appeal_nonce'); ?>
                
                <div class="sp-form-group">
                    <label class="sp-form-label"><?php _e('اختر الفعالية', 'saint-porphyrius'); ?></label>
                    <select name="event_id" id="sp-appeal-event" class="sp-form-select" required>
                        <option value=""><?php _e('-- اختر فعالية --', 'saint-porphyrius'); ?></option>
                        <?php foreach ($appealable_events as $event): 
                            $events_handler = SP_Events::get_instance();
                            $points_config = $events_handler->get_event_points($event);
                        ?>
                            <option value="<?php echo esc_attr($event->id); ?>" 
                                    data-points="<?php echo esc_attr($points_config['attendance']); ?>"
                                    data-icon="<?php echo esc_attr($event->type_icon); ?>"
                                    data-color="<?php echo esc_attr($event->type_color); ?>">
                                <?php echo esc_html($event->type_icon . ' ' . $event->title_ar . ' - ' . date_i18n('j M Y', strtotime($event->event_date))); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="sp-appeal-event-info" class="sp-appeal-event-info" style="display: none;">
                    <div class="sp-appeal-event-points">
                        <span class="sp-appeal-points-label"><?php _e('نقاط الحضور الكاملة:', 'saint-porphyrius'); ?></span>
                        <span class="sp-appeal-points-value" id="sp-appeal-points-value">0</span>
                    </div>
                </div>
                
                <div class="sp-form-group">
                    <label class="sp-form-label"><?php _e('سبب الطلب', 'saint-porphyrius'); ?></label>
                    <textarea name="reason" id="sp-appeal-reason" class="sp-form-textarea" rows="3" 
                              placeholder="<?php _e('اشرح لماذا لم تتمكن من مسح رمز QR...', 'saint-porphyrius'); ?>" required></textarea>
                </div>
                
                <div class="sp-appeal-warning">
                    <span class="sp-appeal-warning-icon">⚠️</span>
                    <span><?php _e('تنبيه: في حال رفض الطلب قد يتم خصم 5 نقاط', 'saint-porphyrius'); ?></span>
                </div>
                
                <button type="submit" class="sp-btn sp-btn-primary sp-btn-block" id="sp-appeal-submit">
                    <?php _e('تقديم الطلب', 'saint-porphyrius'); ?>
                </button>
            </form>
        </div>
    </div>
    <?php else: ?>
    <div class="sp-section">
        <div class="sp-card">
            <div class="sp-empty">
                <div class="sp-empty-icon">✅</div>
                <h4 class="sp-empty-title"><?php _e('لا توجد فعاليات متاحة للطلب', 'saint-porphyrius'); ?></h4>
                <p class="sp-empty-text"><?php _e('يمكنك طلب نقاط فقط على فعاليات الـ 30 يوماً الماضية التي لم تحصل فيها على نقاط حضور', 'saint-porphyrius'); ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- My Appeals History -->
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title"><?php _e('طلباتي السابقة', 'saint-porphyrius'); ?></h3>
        </div>
        
        <?php if (empty($user_appeals)): ?>
            <div class="sp-card">
                <div class="sp-empty">
                    <div class="sp-empty-icon">📋</div>
                    <h4 class="sp-empty-title"><?php _e('لا توجد طلبات', 'saint-porphyrius'); ?></h4>
                    <p class="sp-empty-text"><?php _e('لم تقدم أي طلبات بعد', 'saint-porphyrius'); ?></p>
                </div>
            </div>
        <?php else: ?>
            <div class="sp-appeals-list">
                <?php foreach ($user_appeals as $appeal): 
                    $status_label = SP_Appeals::get_status_label($appeal->status);
                    $status_color = SP_Appeals::get_status_color($appeal->status);
                    $event_title = $appeal->event_title ?? __('فعالية محذوفة', 'saint-porphyrius');
                ?>
                    <div class="sp-appeal-card">
                        <div class="sp-appeal-card-header">
                            <div class="sp-appeal-event-badge" style="background: <?php echo esc_attr($appeal->type_color ?? '#6B7280'); ?>20; color: <?php echo esc_attr($appeal->type_color ?? '#6B7280'); ?>;">
                                <?php echo esc_html($appeal->type_icon ?? '📅'); ?>
                            </div>
                            <div class="sp-appeal-card-info">
                                <h4><?php echo esc_html($event_title); ?></h4>
                                <span class="sp-appeal-date"><?php echo esc_html(date_i18n('j M Y', strtotime($appeal->event_date ?? $appeal->created_at))); ?></span>
                            </div>
                            <span class="sp-appeal-status" style="background: <?php echo esc_attr($status_color); ?>20; color: <?php echo esc_attr($status_color); ?>;">
                                <?php echo esc_html($status_label); ?>
                            </span>
                        </div>
                        
                        <div class="sp-appeal-card-body">
                            <p class="sp-appeal-reason-text"><?php echo esc_html($appeal->reason); ?></p>
                        </div>
                        
                        <?php if ($appeal->status !== 'pending'): ?>
                        <div class="sp-appeal-card-footer">
                            <?php if ($appeal->points_awarded > 0): ?>
                                <span class="sp-appeal-result positive">+<?php echo esc_html($appeal->points_awarded); ?> <?php _e('نقطة', 'saint-porphyrius'); ?></span>
                            <?php elseif ($appeal->points_awarded < 0): ?>
                                <span class="sp-appeal-result negative"><?php echo esc_html($appeal->points_awarded); ?> <?php _e('نقطة', 'saint-porphyrius'); ?></span>
                            <?php else: ?>
                                <span class="sp-appeal-result neutral"><?php _e('بدون تغيير بالنقاط', 'saint-porphyrius'); ?></span>
                            <?php endif; ?>
                            
                            <?php if ($appeal->reviewed_at): ?>
                                <span class="sp-appeal-reviewed-date">
                                    <?php echo esc_html(date_i18n('j M Y', strtotime($appeal->reviewed_at))); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Bottom Navigation -->
<nav class="sp-unified-nav">
    <div class="sp-nav-inner">
        <a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-nav-item">
            <div class="sp-nav-icon">
                <span class="dashicons dashicons-dashboard"></span>
            </div>
            <span class="sp-nav-label"><?php _e('الرئيسية', 'saint-porphyrius'); ?></span>
        </a>
        <a href="<?php echo home_url('/app/events'); ?>" class="sp-nav-item">
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

<script>
(function() {
    const eventSelect = document.getElementById('sp-appeal-event');
    const eventInfo = document.getElementById('sp-appeal-event-info');
    const pointsValue = document.getElementById('sp-appeal-points-value');
    const form = document.getElementById('sp-appeal-form');
    const submitBtn = document.getElementById('sp-appeal-submit');
    
    if (eventSelect) {
        eventSelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            if (this.value) {
                const points = selected.dataset.points || '0';
                pointsValue.textContent = points;
                eventInfo.style.display = 'flex';
            } else {
                eventInfo.style.display = 'none';
            }
        });
    }
    
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            submitBtn.disabled = true;
            submitBtn.textContent = '<?php _e('جاري الإرسال...', 'saint-porphyrius'); ?>';
            
            const formData = new FormData();
            formData.append('action', 'sp_submit_appeal');
            formData.append('nonce', spApp.nonce);
            formData.append('event_id', document.getElementById('sp-appeal-event').value);
            formData.append('reason', document.getElementById('sp-appeal-reason').value);
            
            fetch(spApp.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(response => {
                if (response.success) {
                    // Show success and reload
                    alert(response.data.message);
                    window.location.reload();
                } else {
                    alert(response.data.message || '<?php _e('حدث خطأ', 'saint-porphyrius'); ?>');
                    submitBtn.disabled = false;
                    submitBtn.textContent = '<?php _e('تقديم الطلب', 'saint-porphyrius'); ?>';
                }
            })
            .catch(() => {
                alert('<?php _e('حدث خطأ في الاتصال', 'saint-porphyrius'); ?>');
                submitBtn.disabled = false;
                submitBtn.textContent = '<?php _e('تقديم الطلب', 'saint-porphyrius'); ?>';
            });
        });
    }
})();
</script>
