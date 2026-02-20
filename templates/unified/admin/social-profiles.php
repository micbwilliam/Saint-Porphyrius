<?php
/**
 * Saint Porphyrius - Admin Social Profiles Settings
 * Toggle social profile features on/off
 */

if (!defined('ABSPATH')) {
    exit;
}

$social_handler = SP_Social_Profile::get_instance();
$settings = $social_handler->get_settings();
?>

<!-- Unified Header -->
<div class="sp-unified-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/admin'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('الملفات الاجتماعية', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-actions"></div>
    </div>
</div>

<!-- Main Content -->
<main class="sp-page-content has-bottom-nav">
    <div class="sp-section" style="padding: var(--sp-space-lg);">
        
        <!-- Feature Toggle -->
        <div class="sp-social-admin-card">
            <div class="sp-social-admin-header">
                <h3>👥 <?php _e('الملفات الاجتماعية', 'saint-porphyrius'); ?></h3>
                <p><?php _e('السماح للأعضاء بمشاهدة الملفات الشخصية الاجتماعية لبعضهم البعض', 'saint-porphyrius'); ?></p>
            </div>
            
            <div class="sp-social-admin-form" id="sp-social-settings-form">
                <!-- Main toggle -->
                <div class="sp-social-toggle-row main-toggle">
                    <div class="sp-social-toggle-info">
                        <strong><?php _e('تفعيل الملفات الاجتماعية', 'saint-porphyrius'); ?></strong>
                        <span><?php _e('عند التعطيل، لن يتمكن أي عضو من رؤية الملفات الاجتماعية', 'saint-porphyrius'); ?></span>
                    </div>
                    <label class="sp-toggle-switch">
                        <input type="checkbox" name="enabled" <?php checked($settings['enabled']); ?>>
                        <span class="sp-toggle-slider"></span>
                    </label>
                </div>
                
                <div class="sp-social-admin-divider"></div>
                <h4 style="margin: var(--sp-space-md) 0 var(--sp-space-sm);">📊 <?php _e('المعلومات المعروضة', 'saint-porphyrius'); ?></h4>
                <p style="font-size: var(--sp-font-size-sm); color: var(--sp-text-secondary); margin-bottom: var(--sp-space-md);">
                    <?php _e('حدد ما يظهر في الملف الاجتماعي لكل عضو', 'saint-porphyrius'); ?>
                </p>
                
                <!-- Show Points History -->
                <div class="sp-social-toggle-row">
                    <div class="sp-social-toggle-info">
                        <strong>📜 <?php _e('سجل النقاط', 'saint-porphyrius'); ?></strong>
                        <span><?php _e('عرض تاريخ النقاط كمنشورات اجتماعية', 'saint-porphyrius'); ?></span>
                    </div>
                    <label class="sp-toggle-switch">
                        <input type="checkbox" name="show_points_history" <?php checked($settings['show_points_history']); ?>>
                        <span class="sp-toggle-slider"></span>
                    </label>
                </div>
                
                <!-- Show Attendance -->
                <div class="sp-social-toggle-row">
                    <div class="sp-social-toggle-info">
                        <strong>✅ <?php _e('إحصائيات الحضور', 'saint-porphyrius'); ?></strong>
                        <span><?php _e('عرض إحصائيات الحضور والغياب', 'saint-porphyrius'); ?></span>
                    </div>
                    <label class="sp-toggle-switch">
                        <input type="checkbox" name="show_attendance" <?php checked($settings['show_attendance']); ?>>
                        <span class="sp-toggle-slider"></span>
                    </label>
                </div>
                
                <!-- Show Events -->
                <div class="sp-social-toggle-row">
                    <div class="sp-social-toggle-info">
                        <strong>📅 <?php _e('الفعاليات الأخيرة', 'saint-porphyrius'); ?></strong>
                        <span><?php _e('عرض آخر الفعاليات التي حضرها أو غاب عنها', 'saint-porphyrius'); ?></span>
                    </div>
                    <label class="sp-toggle-switch">
                        <input type="checkbox" name="show_events" <?php checked($settings['show_events']); ?>>
                        <span class="sp-toggle-slider"></span>
                    </label>
                </div>
                
                <!-- Show Bus Info -->
                <div class="sp-social-toggle-row">
                    <div class="sp-social-toggle-info">
                        <strong>🚌 <?php _e('حجوزات الباص', 'saint-porphyrius'); ?></strong>
                        <span><?php _e('عرض حجوزات الباص الأخيرة', 'saint-porphyrius'); ?></span>
                    </div>
                    <label class="sp-toggle-switch">
                        <input type="checkbox" name="show_bus_info" <?php checked($settings['show_bus_info']); ?>>
                        <span class="sp-toggle-slider"></span>
                    </label>
                </div>
                
                <!-- Show Quiz Stats -->
                <div class="sp-social-toggle-row">
                    <div class="sp-social-toggle-info">
                        <strong>📝 <?php _e('إحصائيات المسابقات', 'saint-porphyrius'); ?></strong>
                        <span><?php _e('عرض نتائج الاختبارات والمسابقات', 'saint-porphyrius'); ?></span>
                    </div>
                    <label class="sp-toggle-switch">
                        <input type="checkbox" name="show_quiz_stats" <?php checked($settings['show_quiz_stats']); ?>>
                        <span class="sp-toggle-slider"></span>
                    </label>
                </div>
                
                <!-- Show Discipline -->
                <div class="sp-social-toggle-row">
                    <div class="sp-social-toggle-info">
                        <strong>📋 <?php _e('حالة الانضباط', 'saint-porphyrius'); ?></strong>
                        <span><?php _e('عرض الكروت الصفراء والحمراء وحالة المحروم', 'saint-porphyrius'); ?></span>
                    </div>
                    <label class="sp-toggle-switch">
                        <input type="checkbox" name="show_discipline" <?php checked($settings['show_discipline']); ?>>
                        <span class="sp-toggle-slider"></span>
                    </label>
                </div>
                
                <div class="sp-social-admin-divider"></div>
                <h4 style="margin: var(--sp-space-md) 0 var(--sp-space-sm);">📷 <?php _e('إعدادات الصور', 'saint-porphyrius'); ?></h4>
                
                <!-- Allow Cover Upload -->
                <div class="sp-social-toggle-row">
                    <div class="sp-social-toggle-info">
                        <strong>🖼️ <?php _e('صورة الغلاف', 'saint-porphyrius'); ?></strong>
                        <span><?php _e('السماح للأعضاء بتغيير صورة الغلاف', 'saint-porphyrius'); ?></span>
                    </div>
                    <label class="sp-toggle-switch">
                        <input type="checkbox" name="allow_cover_upload" <?php checked($settings['allow_cover_upload']); ?>>
                        <span class="sp-toggle-slider"></span>
                    </label>
                </div>
                
                <!-- Allow Profile Image Upload -->
                <div class="sp-social-toggle-row">
                    <div class="sp-social-toggle-info">
                        <strong>👤 <?php _e('الصورة الشخصية', 'saint-porphyrius'); ?></strong>
                        <span><?php _e('السماح للأعضاء بتغيير صورتهم الشخصية', 'saint-porphyrius'); ?></span>
                    </div>
                    <label class="sp-toggle-switch">
                        <input type="checkbox" name="allow_profile_upload" <?php checked($settings['allow_profile_upload']); ?>>
                        <span class="sp-toggle-slider"></span>
                    </label>
                </div>
                
                <!-- Save Button -->
                <div style="margin-top: var(--sp-space-lg);">
                    <button type="button" class="sp-btn sp-btn-primary sp-btn-block" id="sp-save-social-settings">
                        <?php _e('حفظ الإعدادات', 'saint-porphyrius'); ?>
                    </button>
                </div>
                
                <div id="sp-social-settings-msg" style="display: none; margin-top: var(--sp-space-md); text-align: center;"></div>
            </div>
        </div>
    </div>
</main>

<!-- Bottom Nav -->
<nav class="sp-unified-nav">
    <div class="sp-nav-inner">
        <a href="<?php echo home_url('/app/admin'); ?>" class="sp-nav-item active">
            <div class="sp-nav-indicator"></div>
            <div class="sp-nav-icon">
                <span class="dashicons dashicons-admin-generic"></span>
            </div>
            <span class="sp-nav-label"><?php _e('الإدارة', 'saint-porphyrius'); ?></span>
        </a>
        <a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-nav-item">
            <div class="sp-nav-icon">
                <span class="dashicons dashicons-dashboard"></span>
            </div>
            <span class="sp-nav-label"><?php _e('الرئيسية', 'saint-porphyrius'); ?></span>
        </a>
    </div>
</nav>

<style>
.sp-social-admin-card {
    background: var(--sp-bg-card);
    border-radius: var(--sp-radius-lg);
    box-shadow: var(--sp-shadow-sm);
    overflow: hidden;
}

.sp-social-admin-header {
    padding: var(--sp-space-lg);
    background: linear-gradient(135deg, rgba(108, 155, 207, 0.1), rgba(108, 155, 207, 0.05));
    border-bottom: 1px solid var(--sp-border-light);
}

.sp-social-admin-header h3 {
    margin: 0 0 var(--sp-space-xs);
    font-size: var(--sp-font-size-lg);
}

.sp-social-admin-header p {
    margin: 0;
    font-size: var(--sp-font-size-sm);
    color: var(--sp-text-secondary);
}

.sp-social-admin-form {
    padding: var(--sp-space-lg);
}

.sp-social-toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--sp-space-md) 0;
    border-bottom: 1px solid var(--sp-border-light);
    gap: var(--sp-space-md);
}

.sp-social-toggle-row:last-of-type {
    border-bottom: none;
}

.sp-social-toggle-row.main-toggle {
    padding: var(--sp-space-md);
    background: rgba(212, 161, 42, 0.05);
    border-radius: var(--sp-radius-md);
    border: 1px solid rgba(212, 161, 42, 0.2);
    margin-bottom: var(--sp-space-sm);
}

.sp-social-toggle-info {
    flex: 1;
}

.sp-social-toggle-info strong {
    display: block;
    font-size: var(--sp-font-size-base);
    margin-bottom: 2px;
}

.sp-social-toggle-info span {
    display: block;
    font-size: var(--sp-font-size-xs);
    color: var(--sp-text-secondary);
}

.sp-toggle-switch {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 26px;
    flex-shrink: 0;
}

.sp-toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.sp-toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.3s;
    border-radius: 26px;
}

.sp-toggle-slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    right: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
}

.sp-toggle-switch input:checked + .sp-toggle-slider {
    background-color: var(--sp-primary, #D4A12A);
}

.sp-toggle-switch input:checked + .sp-toggle-slider:before {
    transform: translateX(-22px);
}

.sp-social-admin-divider {
    height: 1px;
    background: var(--sp-border-light);
    margin: var(--sp-space-md) 0;
}
</style>

<script>
(function($) {
    'use strict';
    
    $('#sp-save-social-settings').on('click', function() {
        var btn = $(this);
        var form = $('#sp-social-settings-form');
        var msg = $('#sp-social-settings-msg');
        
        var data = {
            action: 'sp_save_social_settings',
            nonce: spApp.nonce
        };
        
        // Collect all checkbox values
        form.find('input[type=checkbox]').each(function() {
            data[$(this).attr('name')] = $(this).is(':checked') ? 1 : 0;
        });
        
        btn.prop('disabled', true).text('جاري الحفظ...');
        
        $.post(spApp.ajaxUrl, data, function(response) {
            btn.prop('disabled', false).text('حفظ الإعدادات');
            
            if (response.success) {
                msg.html('<span style="color: var(--sp-success);">✅ ' + (response.data.message || 'تم الحفظ بنجاح') + '</span>').show();
            } else {
                msg.html('<span style="color: var(--sp-danger);">❌ ' + (response.data.message || 'حدث خطأ') + '</span>').show();
            }
            
            setTimeout(function() { msg.fadeOut(); }, 3000);
        }).fail(function() {
            btn.prop('disabled', false).text('حفظ الإعدادات');
            msg.html('<span style="color: var(--sp-danger);">❌ حدث خطأ في الاتصال</span>').show();
        });
    });
    
})(jQuery);
</script>
