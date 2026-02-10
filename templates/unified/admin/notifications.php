<?php
/**
 * Saint Porphyrius - Admin Notifications (Mobile)
 * OneSignal push notification management: settings, send, subscribers, log
 */

if (!defined('ABSPATH')) {
    exit;
}

$notifications = SP_Notifications::get_instance();
$settings = $notifications->get_settings();
$stats = $notifications->get_stats();
$is_configured = $notifications->is_configured();

// Handle form submissions
$success_message = '';
$error_message = '';

// Save settings
if (isset($_POST['sp_save_push_settings']) && wp_verify_nonce($_POST['_wpnonce'], 'sp_push_settings')) {
    $new_settings = array(
        'enabled' => !empty($_POST['enabled']) ? 1 : 0,
        'app_id' => sanitize_text_field($_POST['app_id'] ?? ''),
        'api_key' => sanitize_text_field($_POST['api_key'] ?? ''),
        'safari_web_id' => sanitize_text_field($_POST['safari_web_id'] ?? ''),
        'subscription_points' => absint($_POST['subscription_points'] ?? 10),
        'subscription_points_enabled' => !empty($_POST['subscription_points_enabled']) ? 1 : 0,
        'auto_new_event' => !empty($_POST['auto_new_event']) ? 1 : 0,
        'auto_registration_approved' => !empty($_POST['auto_registration_approved']) ? 1 : 0,
        'auto_new_quiz' => !empty($_POST['auto_new_quiz']) ? 1 : 0,
        'auto_points_milestone' => !empty($_POST['auto_points_milestone']) ? 1 : 0,
        'auto_event_reminder' => !empty($_POST['auto_event_reminder']) ? 1 : 0,
        'event_reminder_hours' => absint($_POST['event_reminder_hours'] ?? 24),
        'welcome_message_enabled' => !empty($_POST['welcome_message_enabled']) ? 1 : 0,
        'welcome_title' => sanitize_text_field($_POST['welcome_title'] ?? ''),
        'welcome_message' => sanitize_textarea_field($_POST['welcome_message'] ?? ''),
        'prompt_delay_seconds' => absint($_POST['prompt_delay_seconds'] ?? 10),
        'prompt_message' => sanitize_text_field($_POST['prompt_message'] ?? ''),
    );
    
    $settings = $notifications->update_settings($new_settings);
    $is_configured = $notifications->is_configured();
    $success_message = 'تم حفظ الإعدادات بنجاح';
}

// Send notification
if (isset($_POST['sp_send_notification']) && wp_verify_nonce($_POST['_wpnonce'], 'sp_send_notification')) {
    $title = sanitize_text_field($_POST['notif_title'] ?? '');
    $message = sanitize_textarea_field($_POST['notif_message'] ?? '');
    $url = esc_url_raw($_POST['notif_url'] ?? '');
    $target_type = sanitize_text_field($_POST['notif_target'] ?? 'all');
    $target_users = isset($_POST['notif_users']) ? array_map('absint', (array) $_POST['notif_users']) : array();
    
    if ($target_type === 'specific' && !empty($target_users)) {
        // Send to specific users
        $result = $notifications->send_to_users($target_users, $title, $message, $url, 'manual');
    } else {
        // Send to all
        $result = $notifications->send_admin_notification($title, $message, $url);
    }
    
    if (is_wp_error($result)) {
        $error_message = $result->get_error_message();
    } else {
        $recipients = isset($result['recipients']) ? $result['recipients'] : 0;
        $success_message = sprintf('تم إرسال الإشعار بنجاح إلى %d مشترك', $recipients);
        // Refresh stats
        $stats = $notifications->get_stats();
    }
}

// Test connection
if (isset($_POST['sp_test_connection']) && wp_verify_nonce($_POST['_wpnonce'], 'sp_push_settings')) {
    $test_result = $notifications->test_connection();
    if (is_wp_error($test_result)) {
        $error_message = 'فشل الاتصال: ' . $test_result->get_error_message();
    } else {
        $success_message = '✅ ' . ($test_result['message'] ?? 'تم الاتصال بنجاح!');
    }
}

// Current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';

// Get subscribers for subscriber tab
$subscribers = array();
if ($current_tab === 'subscribers') {
    $subscribers = $notifications->get_subscribers(array('limit' => 100));
}

// Get notification log
$notification_log = array();
if ($current_tab === 'log') {
    $notification_log = $notifications->get_notification_log(array('limit' => 50));
}
?>

<!-- Admin Header -->
<div class="sp-unified-header sp-admin-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/admin/dashboard'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('الإشعارات', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-spacer"></div>
    </div>
</div>

<main class="sp-page-content sp-admin-content">
    
    <?php if ($success_message): ?>
    <div class="sp-alert sp-alert-success" style="margin-bottom: var(--sp-space-md); padding: var(--sp-space-md); background: #D1FAE5; border-radius: var(--sp-radius-md); color: #065F46; display: flex; align-items: center; gap: var(--sp-space-sm);">
        ✅ <?php echo esc_html($success_message); ?>
    </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
    <div class="sp-alert sp-alert-error" style="margin-bottom: var(--sp-space-md); padding: var(--sp-space-md); background: #FEE2E2; border-radius: var(--sp-radius-md); color: #991B1B; display: flex; align-items: center; gap: var(--sp-space-sm);">
        ❌ <?php echo esc_html($error_message); ?>
    </div>
    <?php endif; ?>
    
    <!-- Tabs -->
    <div style="display: flex; gap: var(--sp-space-xs); overflow-x: auto; margin-bottom: var(--sp-space-lg); padding-bottom: var(--sp-space-xs);">
        <?php
        $tabs = array(
            'overview' => '📊 نظرة عامة',
            'send' => '📤 إرسال إشعار',
            'subscribers' => '👥 المشتركين',
            'log' => '📋 السجل',
            'settings' => '⚙️ الإعدادات',
        );
        foreach ($tabs as $tab_key => $tab_label):
        ?>
        <a href="<?php echo home_url('/app/admin/notifications?tab=' . $tab_key); ?>" 
           class="sp-btn <?php echo $current_tab === $tab_key ? 'sp-btn-primary' : 'sp-btn-outline'; ?>"
           style="white-space: nowrap; font-size: 0.85rem;">
            <?php echo $tab_label; ?>
        </a>
        <?php endforeach; ?>
    </div>
    
    <?php if ($current_tab === 'overview'): ?>
    <!-- ==================== OVERVIEW TAB ==================== -->
    
    <?php if (!$is_configured): ?>
    <div class="sp-admin-card" style="background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); border: 1px solid #F59E0B;">
        <div style="display: flex; align-items: center; gap: var(--sp-space-md);">
            <span style="font-size: 2rem;">⚠️</span>
            <div>
                <h3 style="margin: 0 0 var(--sp-space-xs) 0; color: #92400E;"><?php _e('OneSignal غير مفعّل', 'saint-porphyrius'); ?></h3>
                <p style="margin: 0; color: #92400E;">اذهب إلى تبويب الإعدادات لإدخال بيانات OneSignal وتفعيل الإشعارات.</p>
            </div>
        </div>
        <a href="<?php echo home_url('/app/admin/notifications?tab=settings'); ?>" class="sp-btn sp-btn-primary" style="margin-top: var(--sp-space-md);">
            ⚙️ إعداد OneSignal
        </a>
    </div>
    <?php endif; ?>
    
    <!-- Stats Grid -->
    <div class="sp-admin-stats-grid">
        <div class="sp-admin-stat-card">
            <div class="sp-admin-stat-icon" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                🔔
            </div>
            <div class="sp-admin-stat-info">
                <span class="sp-admin-stat-value"><?php echo esc_html($stats->total_subscribers); ?></span>
                <span class="sp-admin-stat-label"><?php _e('مشترك نشط', 'saint-porphyrius'); ?></span>
            </div>
        </div>
        
        <div class="sp-admin-stat-card">
            <div class="sp-admin-stat-icon" style="background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);">
                📊
            </div>
            <div class="sp-admin-stat-info">
                <span class="sp-admin-stat-value"><?php echo esc_html($stats->subscription_rate); ?>%</span>
                <span class="sp-admin-stat-label"><?php _e('نسبة الاشتراك', 'saint-porphyrius'); ?></span>
            </div>
        </div>
        
        <div class="sp-admin-stat-card">
            <div class="sp-admin-stat-icon" style="background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);">
                📤
            </div>
            <div class="sp-admin-stat-info">
                <span class="sp-admin-stat-value"><?php echo esc_html($stats->total_notifications_sent); ?></span>
                <span class="sp-admin-stat-label"><?php _e('إشعار مُرسل', 'saint-porphyrius'); ?></span>
            </div>
        </div>
        
        <div class="sp-admin-stat-card">
            <div class="sp-admin-stat-icon" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);">
                ✨
            </div>
            <div class="sp-admin-stat-info">
                <span class="sp-admin-stat-value"><?php echo esc_html($stats->subscribed_today); ?></span>
                <span class="sp-admin-stat-label"><?php _e('اشتراكات اليوم', 'saint-porphyrius'); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Subscription Progress -->
    <div class="sp-admin-card" style="margin-top: var(--sp-space-md);">
        <h3 style="margin: 0 0 var(--sp-space-md) 0;">📈 <?php _e('معدل الاشتراك', 'saint-porphyrius'); ?></h3>
        <div style="display: flex; align-items: center; gap: var(--sp-space-md); margin-bottom: var(--sp-space-sm);">
            <div style="flex: 1; background: var(--sp-bg-secondary); border-radius: 999px; height: 12px; overflow: hidden;">
                <div style="width: <?php echo min(100, $stats->subscription_rate); ?>%; height: 100%; background: linear-gradient(90deg, #10B981, #3B82F6); border-radius: 999px; transition: width 0.5s;"></div>
            </div>
            <span style="font-weight: 600; min-width: 60px; text-align: center;"><?php echo esc_html($stats->subscription_rate); ?>%</span>
        </div>
        <p style="margin: 0; color: var(--sp-text-muted); font-size: 0.85rem;">
            <?php printf('%d مشترك من أصل %d عضو', $stats->total_subscribers, $stats->total_members); ?>
        </p>
    </div>
    
    <!-- Device Breakdown -->
    <?php if (!empty($stats->browser_breakdown)): ?>
    <div class="sp-admin-card" style="margin-top: var(--sp-space-md);">
        <h3 style="margin: 0 0 var(--sp-space-md) 0;">🌐 <?php _e('المتصفحات', 'saint-porphyrius'); ?></h3>
        <?php foreach ($stats->browser_breakdown as $browser): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--sp-space-sm) 0; border-bottom: 1px solid var(--sp-border-light);">
            <span><?php echo esc_html($browser->browser ?: 'غير معروف'); ?></span>
            <span class="sp-badge"><?php echo esc_html($browser->count); ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Recent Notifications -->
    <?php if (!empty($stats->recent_notifications)): ?>
    <div class="sp-admin-card" style="margin-top: var(--sp-space-md);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--sp-space-md);">
            <h3 style="margin: 0;">📬 <?php _e('آخر الإشعارات', 'saint-porphyrius'); ?></h3>
            <a href="<?php echo home_url('/app/admin/notifications?tab=log'); ?>" style="font-size: 0.85rem; color: var(--sp-primary);">عرض الكل</a>
        </div>
        <?php foreach (array_slice($stats->recent_notifications, 0, 5) as $notif): ?>
        <div style="padding: var(--sp-space-sm) 0; border-bottom: 1px solid var(--sp-border-light);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div style="flex: 1;">
                    <strong style="font-size: 0.9rem;"><?php echo esc_html($notif->title); ?></strong>
                    <p style="margin: 2px 0 0; font-size: 0.8rem; color: var(--sp-text-muted);">
                        <?php echo esc_html(mb_strimwidth($notif->message, 0, 60, '...')); ?>
                    </p>
                </div>
                <div style="text-align: left; min-width: 70px;">
                    <span style="font-size: 0.75rem; color: var(--sp-text-muted);"><?php echo esc_html(date_i18n('j M H:i', strtotime($notif->sent_at))); ?></span>
                    <br>
                    <span style="font-size: 0.75rem; background: var(--sp-bg-secondary); padding: 2px 6px; border-radius: 4px;"><?php echo esc_html($notif->sent_count); ?> 📤</span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Quick Actions -->
    <div style="display: flex; gap: var(--sp-space-sm); margin-top: var(--sp-space-lg);">
        <a href="<?php echo home_url('/app/admin/notifications?tab=send'); ?>" class="sp-btn sp-btn-primary sp-btn-block">
            📤 <?php _e('إرسال إشعار جديد', 'saint-porphyrius'); ?>
        </a>
    </div>
    
    <?php elseif ($current_tab === 'send'): ?>
    <!-- ==================== SEND TAB ==================== -->
    
    <?php if (!$is_configured): ?>
    <div class="sp-admin-card" style="text-align: center; padding: var(--sp-space-xl);">
        <span style="font-size: 3rem;">🔕</span>
        <h3><?php _e('OneSignal غير مفعّل', 'saint-porphyrius'); ?></h3>
        <p style="color: var(--sp-text-muted);">يجب إعداد OneSignal أولاً قبل إرسال الإشعارات.</p>
        <a href="<?php echo home_url('/app/admin/notifications?tab=settings'); ?>" class="sp-btn sp-btn-primary">⚙️ إعداد الآن</a>
    </div>
    <?php else: ?>
    
    <div class="sp-admin-card">
        <h3 style="margin: 0 0 var(--sp-space-lg) 0;">📤 <?php _e('إرسال إشعار جديد', 'saint-porphyrius'); ?></h3>
        
        <form method="post" id="sp-send-notification-form">
            <?php wp_nonce_field('sp_send_notification'); ?>
            
            <!-- Target Selection -->
            <div style="margin-bottom: var(--sp-space-md);">
                <label class="sp-form-label"><?php _e('إرسال إلى', 'saint-porphyrius'); ?> *</label>
                <div style="display: flex; gap: var(--sp-space-sm); flex-wrap: wrap;">
                    <label style="display: flex; align-items: center; gap: 6px; padding: 10px 16px; background: var(--sp-bg-secondary); border-radius: var(--sp-radius-md); cursor: pointer; flex: 1; min-width: 140px;">
                        <input type="radio" name="notif_target" value="all" checked onchange="toggleUserSelect(this)">
                        <span>📢 الكل (<?php echo esc_html($stats->total_subscribers); ?>)</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 6px; padding: 10px 16px; background: var(--sp-bg-secondary); border-radius: var(--sp-radius-md); cursor: pointer; flex: 1; min-width: 140px;">
                        <input type="radio" name="notif_target" value="specific" onchange="toggleUserSelect(this)">
                        <span>👤 أعضاء محددين</span>
                    </label>
                </div>
            </div>
            
            <!-- User Selection (hidden by default) -->
            <div id="sp-user-select-container" style="display: none; margin-bottom: var(--sp-space-md);">
                <label class="sp-form-label"><?php _e('اختر المشتركين', 'saint-porphyrius'); ?></label>
                <?php
                // Get all subscribed users
                $subscribed_users = $notifications->get_subscribers(array('limit' => 500));
                ?>
                <select name="notif_users[]" multiple class="sp-form-input" id="sp-users-select" 
                        style="min-height: 150px; padding: var(--sp-space-sm);">
                    <?php foreach ($subscribed_users as $sub): 
                        $user = get_userdata($sub->user_id);
                        if (!$user) continue;
                    ?>
                    <option value="<?php echo esc_attr($sub->user_id); ?>">
                        <?php echo esc_html($user->display_name); ?> (<?php echo esc_html($sub->browser ?: 'Web'); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <p style="margin: 4px 0 0; font-size: 0.75rem; color: var(--sp-text-muted);">اضغط Ctrl/Cmd لاختيار أكثر من عضو</p>
            </div>
            
            <div style="margin-bottom: var(--sp-space-md);">
                <label class="sp-form-label"><?php _e('عنوان الإشعار', 'saint-porphyrius'); ?> *</label>
                <input type="text" name="notif_title" class="sp-form-input" required
                       placeholder="مثال: فعالية قادمة 📅" maxlength="100">
                <p style="margin: 4px 0 0; font-size: 0.75rem; color: var(--sp-text-muted);">الحد الأقصى 100 حرف</p>
            </div>
            
            <div style="margin-bottom: var(--sp-space-md);">
                <label class="sp-form-label"><?php _e('نص الرسالة', 'saint-porphyrius'); ?> *</label>
                <textarea name="notif_message" class="sp-form-input" required rows="4"
                          placeholder="اكتب رسالة الإشعار هنا..." maxlength="500"></textarea>
                <p style="margin: 4px 0 0; font-size: 0.75rem; color: var(--sp-text-muted);">الحد الأقصى 500 حرف</p>
            </div>
            
            <div style="margin-bottom: var(--sp-space-md);">
                <label class="sp-form-label"><?php _e('رابط (اختياري)', 'saint-porphyrius'); ?></label>
                <input type="url" name="notif_url" class="sp-form-input" 
                       placeholder="<?php echo home_url('/app/events'); ?>"
                       value="<?php echo esc_attr(home_url('/app/')); ?>">
                <p style="margin: 4px 0 0; font-size: 0.75rem; color: var(--sp-text-muted);">الصفحة التي يُفتح عليها عند الضغط على الإشعار</p>
            </div>
            
            <!-- Preview -->
            <div style="background: #1a1a2e; border-radius: var(--sp-radius-lg); padding: var(--sp-space-md); margin-bottom: var(--sp-space-lg);">
                <p style="color: #8B8BA3; font-size: 0.75rem; margin: 0 0 var(--sp-space-sm) 0;">معاينة الإشعار:</p>
                <div style="background: #16213e; border-radius: var(--sp-radius-md); padding: var(--sp-space-md); display: flex; gap: var(--sp-space-md); align-items: flex-start;">
                    <img src="<?php echo SP_PLUGIN_URL; ?>assets/icons/icon-72x72.png" style="width: 40px; height: 40px; border-radius: 8px;" alt="">
                    <div style="flex: 1;">
                        <div id="sp-preview-title" style="color: white; font-weight: 600; font-size: 0.9rem;">عنوان الإشعار</div>
                        <div id="sp-preview-message" style="color: #C4C4D4; font-size: 0.8rem; margin-top: 2px;">نص الرسالة...</div>
                        <div style="color: #8B8BA3; font-size: 0.7rem; margin-top: 4px;">القديس بورفيريوس • الآن</div>
                    </div>
                </div>
            </div>
            
            <div style="background: #EFF6FF; border-radius: var(--sp-radius-md); padding: var(--sp-space-md); margin-bottom: var(--sp-space-md); display: flex; align-items: center; gap: var(--sp-space-sm);">
                <span>📊</span>
                <span style="font-size: 0.85rem;">سيتم الإرسال إلى <strong><?php echo esc_html($stats->total_subscribers); ?></strong> مشترك نشط</span>
            </div>
            
            <button type="submit" name="sp_send_notification" class="sp-btn sp-btn-primary sp-btn-block" style="font-size: 1.1rem; padding: var(--sp-space-md);">
                📤 <?php _e('إرسال الإشعار الآن', 'saint-porphyrius'); ?>
            </button>
        </form>
    </div>
    
    <!-- Quick Templates -->
    <div class="sp-admin-card" style="margin-top: var(--sp-space-md);">
        <h3 style="margin: 0 0 var(--sp-space-md) 0;">⚡ <?php _e('قوالب سريعة', 'saint-porphyrius'); ?></h3>
        
        <div style="display: flex; flex-direction: column; gap: var(--sp-space-sm);">
            <button type="button" class="sp-btn sp-btn-outline sp-btn-block sp-quick-template" 
                    data-title="📅 تذكير بالخدمة" data-message="لا تنسوا خدمة يوم الأحد القادم. نتمنى حضوركم جميعاً!">
                📅 تذكير بالخدمة
            </button>
            <button type="button" class="sp-btn sp-btn-outline sp-btn-block sp-quick-template"
                    data-title="📝 اختبار جديد" data-message="تم إضافة اختبار جديد! جاوب على الأسئلة واكسب نقاط. 🎯">
                📝 اختبار جديد
            </button>
            <button type="button" class="sp-btn sp-btn-outline sp-btn-block sp-quick-template"
                    data-title="🎉 إعلان مهم" data-message="لدينا إعلان مهم لأسرة القديس بورفيريوس. افتح التطبيق لمعرفة التفاصيل!">
                🎉 إعلان مهم
            </button>
            <button type="button" class="sp-btn sp-btn-outline sp-btn-block sp-quick-template"
                    data-title="🙏 طلب صلاة" data-message="نطلب صلاتكم من أجل أسرتنا. ربنا يبارككم. ☦️">
                🙏 طلب صلاة
            </button>
        </div>
    </div>
    
    <?php endif; ?>
    
    <?php elseif ($current_tab === 'subscribers'): ?>
    <!-- ==================== SUBSCRIBERS TAB ==================== -->
    
    <div class="sp-admin-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--sp-space-md);">
            <h3 style="margin: 0;">👥 <?php _e('المشتركين في الإشعارات', 'saint-porphyrius'); ?></h3>
            <span class="sp-badge" style="background: var(--sp-primary); color: white; padding: 4px 10px; border-radius: 999px;">
                <?php echo esc_html($stats->total_subscribers); ?>
            </span>
        </div>
        
        <?php if (empty($subscribers)): ?>
        <div style="text-align: center; padding: var(--sp-space-xl) 0;">
            <span style="font-size: 3rem;">🔕</span>
            <p style="color: var(--sp-text-muted);"><?php _e('لا يوجد مشتركين بعد', 'saint-porphyrius'); ?></p>
        </div>
        <?php else: ?>
        
        <div style="display: flex; flex-direction: column; gap: var(--sp-space-sm);">
            <?php foreach ($subscribers as $sub): ?>
            <div style="display: flex; align-items: center; gap: var(--sp-space-md); padding: var(--sp-space-sm); background: var(--sp-bg-secondary); border-radius: var(--sp-radius-md);">
                <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #10B981, #059669); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.9rem;">
                    <?php echo esc_html(mb_substr($sub->name_ar ?: $sub->display_name ?: '?', 0, 1)); ?>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-weight: 600; font-size: 0.9rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        <?php echo esc_html($sub->name_ar ?: $sub->display_name ?: 'غير معروف'); ?>
                    </div>
                    <div style="font-size: 0.75rem; color: var(--sp-text-muted);">
                        <?php echo esc_html($sub->browser ?: $sub->device_type); ?> · <?php echo esc_html(date_i18n('j M Y', strtotime($sub->subscribed_at))); ?>
                    </div>
                </div>
                <div style="text-align: left;">
                    <?php if ($sub->points_awarded): ?>
                    <span style="font-size: 0.7rem; background: #D1FAE5; color: #065F46; padding: 2px 6px; border-radius: 4px;">⭐ نقاط</span>
                    <?php endif; ?>
                    <span style="font-size: 0.7rem; background: <?php echo $sub->is_active ? '#D1FAE5' : '#FEE2E2'; ?>; color: <?php echo $sub->is_active ? '#065F46' : '#991B1B'; ?>; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 2px;">
                        <?php echo $sub->is_active ? '✅ نشط' : '❌ غير نشط'; ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php endif; ?>
    </div>
    
    <?php elseif ($current_tab === 'log'): ?>
    <!-- ==================== LOG TAB ==================== -->
    
    <div class="sp-admin-card">
        <h3 style="margin: 0 0 var(--sp-space-md) 0;">📋 <?php _e('سجل الإشعارات', 'saint-porphyrius'); ?></h3>
        
        <?php if (empty($notification_log)): ?>
        <div style="text-align: center; padding: var(--sp-space-xl) 0;">
            <span style="font-size: 3rem;">📭</span>
            <p style="color: var(--sp-text-muted);"><?php _e('لم يتم إرسال أي إشعارات بعد', 'saint-porphyrius'); ?></p>
        </div>
        <?php else: ?>
        
        <div style="display: flex; flex-direction: column; gap: var(--sp-space-sm);">
            <?php foreach ($notification_log as $log): ?>
            <div style="padding: var(--sp-space-md); background: var(--sp-bg-secondary); border-radius: var(--sp-radius-md); border-right: 3px solid <?php echo $log->trigger_type === 'manual' ? 'var(--sp-primary)' : '#10B981'; ?>;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: var(--sp-space-xs);">
                    <strong style="font-size: 0.9rem;"><?php echo esc_html($log->title); ?></strong>
                    <span style="font-size: 0.7rem; padding: 2px 8px; border-radius: 4px; white-space: nowrap; background: <?php 
                        echo $log->trigger_type === 'manual' ? '#DBEAFE' : '#D1FAE5'; 
                    ?>; color: <?php 
                        echo $log->trigger_type === 'manual' ? '#1E40AF' : '#065F46'; 
                    ?>;">
                        <?php 
                        $type_labels = array(
                            'manual' => '✍️ يدوي',
                            'auto_event' => '📅 فعالية',
                            'auto_registration' => '👤 تسجيل',
                            'auto_quiz' => '📝 اختبار',
                            'auto_points' => '⭐ نقاط',
                            'auto_event_reminder' => '⏰ تذكير',
                        );
                        echo $type_labels[$log->trigger_type] ?? $log->trigger_type;
                        ?>
                    </span>
                </div>
                <p style="margin: 0 0 var(--sp-space-xs) 0; font-size: 0.8rem; color: var(--sp-text-secondary);">
                    <?php echo esc_html(mb_strimwidth($log->message, 0, 100, '...')); ?>
                </p>
                <div style="display: flex; gap: var(--sp-space-md); font-size: 0.75rem; color: var(--sp-text-muted);">
                    <span>📤 <?php echo esc_html($log->sent_count); ?> مُرسل</span>
                    <span>📅 <?php echo esc_html(date_i18n('j M Y - H:i', strtotime($log->sent_at))); ?></span>
                    <?php if ($log->sent_by_name): ?>
                    <span>👤 <?php echo esc_html($log->sent_by_name); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php endif; ?>
    </div>
    
    <?php elseif ($current_tab === 'settings'): ?>
    <!-- ==================== SETTINGS TAB ==================== -->
    
    <form method="post">
        <?php wp_nonce_field('sp_push_settings'); ?>
        
        <!-- OneSignal Connection -->
        <div class="sp-admin-card">
            <h3 style="margin: 0 0 var(--sp-space-md) 0;">🔑 <?php _e('إعدادات OneSignal', 'saint-porphyrius'); ?></h3>
            
            <div style="margin-bottom: var(--sp-space-md);">
                <label class="sp-form-label">
                    <input type="checkbox" name="enabled" value="1" <?php checked($settings['enabled'], 1); ?>>
                    <?php _e('تفعيل الإشعارات', 'saint-porphyrius'); ?>
                </label>
            </div>
            
            <div style="margin-bottom: var(--sp-space-md);">
                <label class="sp-form-label"><?php _e('OneSignal App ID', 'saint-porphyrius'); ?></label>
                <input type="text" name="app_id" class="sp-form-input" 
                       value="<?php echo esc_attr($settings['app_id']); ?>"
                       placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" dir="ltr">
                <p style="margin: 4px 0 0; font-size: 0.75rem; color: var(--sp-text-muted);">
                    من لوحة تحكم OneSignal → Settings → Keys & IDs
                </p>
            </div>
            
            <div style="margin-bottom: var(--sp-space-md);">
                <label class="sp-form-label"><?php _e('OneSignal REST API Key', 'saint-porphyrius'); ?></label>
                <input type="password" name="api_key" class="sp-form-input" 
                       value="<?php echo esc_attr($settings['api_key']); ?>"
                       placeholder="REST API Key..." dir="ltr">
                <p style="margin: 4px 0 0; font-size: 0.75rem; color: var(--sp-text-muted);">
                    المفتاح السري لإرسال الإشعارات من الخادم
                </p>
            </div>
            
            <div style="margin-bottom: var(--sp-space-md);">
                <label class="sp-form-label"><?php _e('Safari Web ID (اختياري)', 'saint-porphyrius'); ?></label>
                <input type="text" name="safari_web_id" class="sp-form-input" 
                       value="<?php echo esc_attr($settings['safari_web_id']); ?>"
                       placeholder="web.onesignal.auto.xxxxx" dir="ltr">
            </div>
            
            <div style="display: flex; gap: var(--sp-space-sm);">
                <button type="submit" name="sp_test_connection" class="sp-btn sp-btn-outline">
                    🔌 <?php _e('اختبار الاتصال', 'saint-porphyrius'); ?>
                </button>
            </div>
        </div>
        
        <!-- Subscription Points -->
        <div class="sp-admin-card" style="margin-top: var(--sp-space-md);">
            <h3 style="margin: 0 0 var(--sp-space-md) 0;">⭐ <?php _e('نقاط الاشتراك', 'saint-porphyrius'); ?></h3>
            
            <div style="margin-bottom: var(--sp-space-md);">
                <label class="sp-form-label">
                    <input type="checkbox" name="subscription_points_enabled" value="1" <?php checked($settings['subscription_points_enabled'], 1); ?>>
                    <?php _e('منح نقاط عند تفعيل الإشعارات', 'saint-porphyrius'); ?>
                </label>
            </div>
            
            <div style="margin-bottom: var(--sp-space-md);">
                <label class="sp-form-label"><?php _e('عدد النقاط', 'saint-porphyrius'); ?></label>
                <input type="number" name="subscription_points" class="sp-form-input" 
                       value="<?php echo esc_attr($settings['subscription_points']); ?>"
                       min="0" max="1000" style="max-width: 150px;">
                <p style="margin: 4px 0 0; font-size: 0.75rem; color: var(--sp-text-muted);">
                    تُمنح مرة واحدة فقط لكل مستخدم عند أول اشتراك
                </p>
            </div>
        </div>
        
        <!-- Subscription Prompt -->
        <div class="sp-admin-card" style="margin-top: var(--sp-space-md);">
            <h3 style="margin: 0 0 var(--sp-space-md) 0;">💬 <?php _e('رسالة طلب الاشتراك', 'saint-porphyrius'); ?></h3>
            
            <div style="margin-bottom: var(--sp-space-md);">
                <label class="sp-form-label"><?php _e('تأخير الظهور (ثواني)', 'saint-porphyrius'); ?></label>
                <input type="number" name="prompt_delay_seconds" class="sp-form-input" 
                       value="<?php echo esc_attr($settings['prompt_delay_seconds']); ?>"
                       min="0" max="300" style="max-width: 150px;">
            </div>
            
            <div style="margin-bottom: var(--sp-space-md);">
                <label class="sp-form-label"><?php _e('نص الرسالة', 'saint-porphyrius'); ?></label>
                <input type="text" name="prompt_message" class="sp-form-input" 
                       value="<?php echo esc_attr($settings['prompt_message']); ?>">
            </div>
        </div>
        
        <!-- Welcome Message -->
        <div class="sp-admin-card" style="margin-top: var(--sp-space-md);">
            <h3 style="margin: 0 0 var(--sp-space-md) 0;">👋 <?php _e('رسالة الترحيب', 'saint-porphyrius'); ?></h3>
            
            <div style="margin-bottom: var(--sp-space-md);">
                <label class="sp-form-label">
                    <input type="checkbox" name="welcome_message_enabled" value="1" <?php checked($settings['welcome_message_enabled'], 1); ?>>
                    <?php _e('إرسال إشعار ترحيب عند الاشتراك', 'saint-porphyrius'); ?>
                </label>
            </div>
            
            <div style="margin-bottom: var(--sp-space-md);">
                <label class="sp-form-label"><?php _e('عنوان الترحيب', 'saint-porphyrius'); ?></label>
                <input type="text" name="welcome_title" class="sp-form-input" 
                       value="<?php echo esc_attr($settings['welcome_title']); ?>">
            </div>
            
            <div style="margin-bottom: var(--sp-space-md);">
                <label class="sp-form-label"><?php _e('نص الترحيب', 'saint-porphyrius'); ?></label>
                <textarea name="welcome_message" class="sp-form-input" rows="3"><?php echo esc_textarea($settings['welcome_message']); ?></textarea>
            </div>
        </div>
        
        <!-- Auto Triggers -->
        <div class="sp-admin-card" style="margin-top: var(--sp-space-md);">
            <h3 style="margin: 0 0 var(--sp-space-md) 0;">🤖 <?php _e('الإشعارات التلقائية', 'saint-porphyrius'); ?></h3>
            
            <div style="display: flex; flex-direction: column; gap: var(--sp-space-md);">
                <label class="sp-form-label" style="display: flex; align-items: center; gap: var(--sp-space-sm);">
                    <input type="checkbox" name="auto_new_event" value="1" <?php checked($settings['auto_new_event'], 1); ?>>
                    📅 <?php _e('إشعار عند إنشاء فعالية جديدة', 'saint-porphyrius'); ?>
                </label>
                
                <label class="sp-form-label" style="display: flex; align-items: center; gap: var(--sp-space-sm);">
                    <input type="checkbox" name="auto_event_reminder" value="1" <?php checked($settings['auto_event_reminder'], 1); ?>>
                    ⏰ <?php _e('تذكير قبل الفعالية', 'saint-porphyrius'); ?>
                </label>
                
                <?php if (!empty($settings['auto_event_reminder'])): ?>
                <div style="padding-right: var(--sp-space-xl);">
                    <label class="sp-form-label"><?php _e('قبل كم ساعة؟', 'saint-porphyrius'); ?></label>
                    <input type="number" name="event_reminder_hours" class="sp-form-input" 
                           value="<?php echo esc_attr($settings['event_reminder_hours']); ?>"
                           min="1" max="168" style="max-width: 120px;">
                </div>
                <?php endif; ?>
                
                <label class="sp-form-label" style="display: flex; align-items: center; gap: var(--sp-space-sm);">
                    <input type="checkbox" name="auto_registration_approved" value="1" <?php checked($settings['auto_registration_approved'], 1); ?>>
                    👤 <?php _e('إشعار عند قبول عضو جديد', 'saint-porphyrius'); ?>
                </label>
                
                <label class="sp-form-label" style="display: flex; align-items: center; gap: var(--sp-space-sm);">
                    <input type="checkbox" name="auto_new_quiz" value="1" <?php checked($settings['auto_new_quiz'], 1); ?>>
                    📝 <?php _e('إشعار عند نشر اختبار جديد', 'saint-porphyrius'); ?>
                </label>
                
                <label class="sp-form-label" style="display: flex; align-items: center; gap: var(--sp-space-sm);">
                    <input type="checkbox" name="auto_points_milestone" value="1" <?php checked($settings['auto_points_milestone'], 1); ?>>
                    🏆 <?php _e('إشعار عند الوصول لمراحل النقاط', 'saint-porphyrius'); ?>
                </label>
            </div>
        </div>
        
        <!-- Save -->
        <div style="margin-top: var(--sp-space-lg);">
            <button type="submit" name="sp_save_push_settings" class="sp-btn sp-btn-primary sp-btn-block" style="font-size: 1.1rem; padding: var(--sp-space-md);">
                💾 <?php _e('حفظ الإعدادات', 'saint-porphyrius'); ?>
            </button>
        </div>
    </form>
    
    <!-- Setup Guide -->
    <div class="sp-admin-card" style="margin-top: var(--sp-space-lg); background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%); border: 1px solid #93C5FD;">
        <h3 style="margin: 0 0 var(--sp-space-md) 0; color: #1E40AF;">📖 <?php _e('دليل الإعداد', 'saint-porphyrius'); ?></h3>
        <ol style="margin: 0; padding-right: var(--sp-space-lg); font-size: 0.85rem; line-height: 1.8; color: #1E40AF;">
            <li>سجل حساب مجاني في <a href="https://onesignal.com" target="_blank" style="color: #2563EB; font-weight: 600;">onesignal.com</a></li>
            <li>أنشئ تطبيق جديد واختر "Web Push"</li>
            <li>في Site Setup → اختر "Custom Code" واضبط الدومين</li>
            <li>انسخ App ID و REST API Key من Settings → Keys & IDs</li>
            <li>الصقهم هنا واضغط "اختبار الاتصال"</li>
            <li>فعّل الإشعارات واحفظ!</li>
        </ol>
    </div>
    
    <?php endif; ?>
    
</main>

<script>
// Live preview for send notification
document.addEventListener('DOMContentLoaded', function() {
    var titleInput = document.querySelector('[name="notif_title"]');
    var messageInput = document.querySelector('[name="notif_message"]');
    var previewTitle = document.getElementById('sp-preview-title');
    var previewMessage = document.getElementById('sp-preview-message');
    
    if (titleInput && previewTitle) {
        titleInput.addEventListener('input', function() {
            previewTitle.textContent = this.value || 'عنوان الإشعار';
        });
    }
    if (messageInput && previewMessage) {
        messageInput.addEventListener('input', function() {
            previewMessage.textContent = this.value || 'نص الرسالة...';
        });
    }
    
    // Quick templates
    document.querySelectorAll('.sp-quick-template').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (titleInput) titleInput.value = this.dataset.title;
            if (messageInput) messageInput.value = this.dataset.message;
            if (titleInput) titleInput.dispatchEvent(new Event('input'));
            if (messageInput) messageInput.dispatchEvent(new Event('input'));
        });
    });
});

// Toggle user select for specific targeting
function toggleUserSelect(radio) {
    var container = document.getElementById('sp-user-select-container');
    var select = document.getElementById('sp-users-select');
    if (radio.value === 'specific') {
        container.style.display = 'block';
        select.required = true;
    } else {
        container.style.display = 'none';
        select.required = false;
    }
}
</script>
