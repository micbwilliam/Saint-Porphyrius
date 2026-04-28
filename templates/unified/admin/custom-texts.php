<?php
/**
 * Saint Porphyrius - Admin Custom Texts Page
 * Edit all customizable user-facing text strings with gender variants
 */

if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) {
    wp_die(__('غير مسموح لك بالوصول لهذه الصفحة.', 'saint-porphyrius'));
}

$custom_texts = SP_Custom_Texts::get_instance();
$settings = $custom_texts->get_settings();
$sections = $custom_texts->get_keys_by_section();

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sp_custom_texts_nonce'])) {
    if (!wp_verify_nonce($_POST['sp_custom_texts_nonce'], 'sp_save_custom_texts')) {
        wp_die('Security check failed');
    }

    if (isset($_POST['reset']) && $_POST['reset'] === '1') {
        $custom_texts->reset_settings();
        $settings = $custom_texts->get_settings();
        $message = 'reset';
    } else {
        $data = isset($_POST['texts']) && is_array($_POST['texts']) ? $_POST['texts'] : array();
        $custom_texts->update_settings($data);
        $settings = $custom_texts->get_settings();
        $message = 'success';
    }
}
?>

<!-- Header -->
<div class="sp-unified-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/admin'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('النصوص المخصصة', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-spacer"></div>
    </div>
</div>

<main class="sp-page-content">

    <?php if ($message === 'success'): ?>
    <div class="sp-alert sp-alert-success" style="margin: var(--sp-space-md) var(--sp-space-lg);">
        <div class="sp-alert-icon">✅</div>
        <div class="sp-alert-content"><?php _e('تم حفظ جميع النصوص بنجاح!', 'saint-porphyrius'); ?></div>
    </div>
    <?php elseif ($message === 'reset'): ?>
    <div class="sp-alert sp-alert-success" style="margin: var(--sp-space-md) var(--sp-space-lg);">
        <div class="sp-alert-icon">🔄</div>
        <div class="sp-alert-content"><?php _e('تم إعادة جميع النصوص إلى الإعدادات الافتراضية.', 'saint-porphyrius'); ?></div>
    </div>
    <?php endif; ?>

    <!-- Info Banner -->
    <div style="background: var(--sp-bg-secondary); border-radius: var(--sp-radius-md); padding: 16px; margin: 0 var(--sp-space-lg) var(--sp-space-lg); font-size: var(--sp-font-size-sm); color: var(--sp-text-secondary); line-height: 1.6;">
        <strong>💡 <?php _e('كيفية الاستخدام:', 'saint-porphyrius'); ?></strong><br>
        <?php _e('• اترك الحقل فارغاً لاستخدام النص الافتراضي.', 'saint-porphyrius'); ?><br>
        <?php _e('• استخدم {name} و {points} و {count} كمتغيرات يتم استبدالها تلقائياً.', 'saint-porphyrius'); ?><br>
        <?php _e('• النصوص تُكتب بصيغة المذكر والمؤنث حسب نوع العضو.', 'saint-porphyrius'); ?>
    </div>

    <form method="POST" class="sp-form">
        <?php wp_nonce_field('sp_save_custom_texts', 'sp_custom_texts_nonce'); ?>

        <?php foreach ($sections as $section_id => $section): ?>
        <div class="sp-card" style="padding: var(--sp-space-lg); margin: 0 var(--sp-space-lg) var(--sp-space-lg);">
            <h3 style="margin: 0 0 var(--sp-space-md); display: flex; align-items: center; gap: 8px; font-size: var(--sp-font-size-lg);">
                <?php echo esc_html($section['label']); ?>
            </h3>

            <?php foreach ($section['keys'] as $key => $def): 
                $current_male   = isset($settings[$key]['male']) ? $settings[$key]['male'] : '';
                $current_female = isset($settings[$key]['female']) ? $settings[$key]['female'] : '';
                $default_male   = isset($def['male']) ? $def['male'] : '';
                $default_female = isset($def['female']) ? $def['female'] : '';
                $is_modified_m  = ($current_male !== '' && $current_male !== $default_male);
                $is_modified_f  = ($current_female !== '' && $current_female !== $default_female);
            ?>
            <div style="margin-bottom: var(--sp-space-lg); padding-bottom: var(--sp-space-lg); border-bottom: 1px solid var(--sp-border-color);">
                <!-- Label & Variables -->
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--sp-space-sm);">
                    <label style="font-weight: 600; font-size: var(--sp-font-size-sm); color: var(--sp-text-primary);">
                        <?php echo esc_html($def['label']); ?>
                    </label>
                    <?php if (!empty($def['variables'])): ?>
                    <span style="font-size: var(--sp-font-size-xs); color: var(--sp-text-light); background: var(--sp-bg-secondary); padding: 2px 8px; border-radius: 10px;">
                        <?php 
                        $var_labels = array();
                        foreach ($def['variables'] as $v_key => $v_label) {
                            $var_labels[] = '{' . $v_key . '}';
                        }
                        echo esc_html(implode(' · ', $var_labels));
                        ?>
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Male Input -->
                <div style="margin-bottom: var(--sp-space-sm);">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                        <span style="font-size: var(--sp-font-size-xs); color: #2563EB; font-weight: 600; min-width: 40px;">👨 ذكر</span>
                        <?php if ($is_modified_m): ?>
                        <span style="font-size: 10px; background: #FEF3C7; color: #92400E; padding: 1px 6px; border-radius: 8px;">✨ معدّل</span>
                        <?php endif; ?>
                    </div>
                    <input type="text" 
                           name="texts[<?php echo esc_attr($key); ?>][male]" 
                           value="<?php echo esc_attr($current_male); ?>"
                           placeholder="<?php echo esc_attr($default_male); ?>"
                           class="sp-form-input"
                           style="font-size: var(--sp-font-size-sm); <?php echo $is_modified_m ? 'border-color: #F59E0B; background: #FFFBEB;' : ''; ?>">
                </div>

                <!-- Female Input -->
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                        <span style="font-size: var(--sp-font-size-xs); color: #DB2777; font-weight: 600; min-width: 40px;">👩 أنثى</span>
                        <?php if ($is_modified_f): ?>
                        <span style="font-size: 10px; background: #FEF3C7; color: #92400E; padding: 1px 6px; border-radius: 8px;">✨ معدّل</span>
                        <?php endif; ?>
                    </div>
                    <input type="text" 
                           name="texts[<?php echo esc_attr($key); ?>][female]" 
                           value="<?php echo esc_attr($current_female); ?>"
                           placeholder="<?php echo esc_attr($default_female); ?>"
                           class="sp-form-input"
                           style="font-size: var(--sp-font-size-sm); <?php echo $is_modified_f ? 'border-color: #F59E0B; background: #FFFBEB;' : ''; ?>">
                </div>

                <!-- Variable descriptions (help text) -->
                <?php if (!empty($def['variables'])): ?>
                <div style="margin-top: 6px; font-size: 11px; color: var(--sp-text-light); line-height: 1.5;">
                    <?php foreach ($def['variables'] as $v_key => $v_label): ?>
                    <span style="margin-left: 12px;"><code style="background: var(--sp-bg-secondary); padding: 1px 4px; border-radius: 3px;">{<?php echo esc_html($v_key); ?>}</code> = <?php echo esc_html($v_label); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>

        <!-- Action Buttons -->
        <div style="padding: 0 var(--sp-space-lg) var(--sp-space-xl); display: flex; flex-direction: column; gap: var(--sp-space-md);">
            <button type="submit" class="sp-btn sp-btn-primary sp-btn-block" style="padding: 16px; font-size: var(--sp-font-size-lg); font-weight: 700;">
                💾 <?php _e('حفظ جميع التعديلات', 'saint-porphyrius'); ?>
            </button>

            <button type="submit" name="reset" value="1" class="sp-btn sp-btn-outline sp-btn-block" 
                    style="padding: 14px; font-size: var(--sp-font-size-sm); color: var(--sp-danger); border-color: var(--sp-danger);"
                    onclick="return confirm('<?php esc_attr_e('هل أنت متأكد من إعادة جميع النصوص إلى الإعدادات الافتراضية؟ لا يمكن التراجع عن هذا الإجراء.', 'saint-porphyrius'); ?>');">
                🔄 <?php _e('إعادة جميع النصوص إلى الافتراضي', 'saint-porphyrius'); ?>
            </button>
        </div>
    </form>
</main>
