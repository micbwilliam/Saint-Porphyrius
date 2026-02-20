<?php
/**
 * Saint Porphyrius - PWA Settings Admin Page
 * Manage app name, theme color, and other PWA manifest settings
 */

if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) {
    wp_die(__('غير مسموح لك بالوصول لهذه الصفحة.', 'saint-porphyrius'));
}

// Default settings
$defaults = array(
    'app_name'          => 'القديس برفيريوس',
    'app_short_name'    => 'برفيريوس',
    'app_description'   => 'تطبيق كنيسة القديس برفيريوس - مجتمع كنسي متكامل',
    'theme_color'       => '#D4A12A',
    'background_color'  => '#ffffff',
    'display'           => 'standalone',
    'orientation'       => 'portrait',
    'apple_status_bar'  => 'default',
);
$settings = get_option('sp_pwa_settings', $defaults);
$settings = wp_parse_args($settings, $defaults);

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sp_pwa_nonce'])) {
    if (!wp_verify_nonce($_POST['sp_pwa_nonce'], 'sp_save_pwa_settings')) {
        wp_die('Security check failed');
    }

    $new_settings = array(
        'app_name'          => sanitize_text_field($_POST['app_name'] ?? $defaults['app_name']),
        'app_short_name'    => sanitize_text_field($_POST['app_short_name'] ?? $defaults['app_short_name']),
        'app_description'   => sanitize_text_field($_POST['app_description'] ?? $defaults['app_description']),
        'theme_color'       => sanitize_hex_color($_POST['theme_color'] ?? $defaults['theme_color']),
        'background_color'  => sanitize_hex_color($_POST['background_color'] ?? $defaults['background_color']),
        'display'           => sanitize_text_field($_POST['display'] ?? $defaults['display']),
        'orientation'       => sanitize_text_field($_POST['orientation'] ?? $defaults['orientation']),
        'apple_status_bar'  => sanitize_text_field($_POST['apple_status_bar'] ?? $defaults['apple_status_bar']),
    );

    update_option('sp_pwa_settings', $new_settings);
    $settings = $new_settings;

    // Regenerate manifest.json
    sp_regenerate_manifest($settings);

    $message = 'success';
}

/**
 * Regenerate manifest.json with current settings
 */
function sp_regenerate_manifest($settings) {
    $plugin_path = '/wp-content/plugins/Saint-Porphyrius/assets/icons/';

    $manifest = array(
        'name'              => $settings['app_name'],
        'short_name'        => $settings['app_short_name'],
        'description'       => $settings['app_description'],
        'start_url'         => '/app/',
        'scope'             => '/app/',
        'display'           => $settings['display'],
        'orientation'       => $settings['orientation'],
        'background_color'  => $settings['background_color'],
        'theme_color'       => $settings['theme_color'],
        'dir'               => 'rtl',
        'lang'              => 'ar',
        'categories'        => array('lifestyle', 'social'),
        'icons'             => array(
            array('src' => $plugin_path . 'icon-72x72.png',   'sizes' => '72x72',   'type' => 'image/png', 'purpose' => 'any maskable'),
            array('src' => $plugin_path . 'icon-96x96.png',   'sizes' => '96x96',   'type' => 'image/png', 'purpose' => 'any maskable'),
            array('src' => $plugin_path . 'icon-128x128.png', 'sizes' => '128x128', 'type' => 'image/png', 'purpose' => 'any maskable'),
            array('src' => $plugin_path . 'icon-144x144.png', 'sizes' => '144x144', 'type' => 'image/png', 'purpose' => 'any maskable'),
            array('src' => $plugin_path . 'icon-152x152.png', 'sizes' => '152x152', 'type' => 'image/png', 'purpose' => 'any maskable'),
            array('src' => $plugin_path . 'icon-192x192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'),
            array('src' => $plugin_path . 'icon-384x384.png', 'sizes' => '384x384', 'type' => 'image/png', 'purpose' => 'any maskable'),
            array('src' => $plugin_path . 'icon-512x512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'),
        ),
        'screenshots'       => array(),
        'shortcuts'         => array(
            array(
                'name'        => 'الفعاليات',
                'short_name'  => 'فعاليات',
                'description' => 'عرض الفعاليات القادمة',
                'url'         => '/app/events',
                'icons'       => array(array('src' => $plugin_path . 'icon-96x96.png', 'sizes' => '96x96')),
            ),
            array(
                'name'        => 'النقاط',
                'short_name'  => 'نقاط',
                'description' => 'عرض النقاط المكتسبة',
                'url'         => '/app/points',
                'icons'       => array(array('src' => $plugin_path . 'icon-96x96.png', 'sizes' => '96x96')),
            ),
            array(
                'name'        => 'المتصدرين',
                'short_name'  => 'متصدرين',
                'description' => 'عرض قائمة المتصدرين',
                'url'         => '/app/leaderboard',
                'icons'       => array(array('src' => $plugin_path . 'icon-96x96.png', 'sizes' => '96x96')),
            ),
        ),
    );

    $manifest_path = SP_PLUGIN_DIR . 'assets/manifest.json';
    file_put_contents($manifest_path, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
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
        <h1 class="sp-header-title"><?php _e('إعدادات التطبيق (PWA)', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-spacer"></div>
    </div>
</div>

<main class="sp-page-content">

    <?php if ($message === 'success'): ?>
    <div class="sp-alert sp-alert-success" style="margin-bottom: var(--sp-space-lg);">
        ✅ <?php _e('تم حفظ الإعدادات وتحديث ملف manifest.json بنجاح!', 'saint-porphyrius'); ?>
    </div>
    <?php endif; ?>

    <form method="post" class="sp-form">
        <?php wp_nonce_field('sp_save_pwa_settings', 'sp_pwa_nonce'); ?>

        <!-- App Identity -->
        <div class="sp-card" style="padding: var(--sp-space-lg); margin-bottom: var(--sp-space-lg);">
            <h3 style="margin: 0 0 var(--sp-space-md); display: flex; align-items: center; gap: 8px;">
                📱 <?php _e('هوية التطبيق', 'saint-porphyrius'); ?>
            </h3>

            <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                <label class="sp-form-label"><?php _e('اسم التطبيق', 'saint-porphyrius'); ?></label>
                <input type="text" name="app_name" value="<?php echo esc_attr($settings['app_name']); ?>" class="sp-form-input" required>
                <small style="color: var(--sp-text-muted);"><?php _e('يظهر عند تثبيت التطبيق وفي شاشة البداية', 'saint-porphyrius'); ?></small>
            </div>

            <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                <label class="sp-form-label"><?php _e('الاسم المختصر', 'saint-porphyrius'); ?></label>
                <input type="text" name="app_short_name" value="<?php echo esc_attr($settings['app_short_name']); ?>" class="sp-form-input" maxlength="12" required>
                <small style="color: var(--sp-text-muted);"><?php _e('يظهر أسفل أيقونة التطبيق على الشاشة الرئيسية (حد أقصى 12 حرف)', 'saint-porphyrius'); ?></small>
            </div>

            <div class="sp-form-group">
                <label class="sp-form-label"><?php _e('وصف التطبيق', 'saint-porphyrius'); ?></label>
                <input type="text" name="app_description" value="<?php echo esc_attr($settings['app_description']); ?>" class="sp-form-input">
                <small style="color: var(--sp-text-muted);"><?php _e('وصف قصير يظهر في متجر التطبيقات', 'saint-porphyrius'); ?></small>
            </div>
        </div>

        <!-- Appearance -->
        <div class="sp-card" style="padding: var(--sp-space-lg); margin-bottom: var(--sp-space-lg);">
            <h3 style="margin: 0 0 var(--sp-space-md); display: flex; align-items: center; gap: 8px;">
                🎨 <?php _e('المظهر', 'saint-porphyrius'); ?>
            </h3>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--sp-space-md); margin-bottom: var(--sp-space-md);">
                <div class="sp-form-group">
                    <label class="sp-form-label"><?php _e('لون التطبيق الأساسي', 'saint-porphyrius'); ?></label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="color" name="theme_color" value="<?php echo esc_attr($settings['theme_color']); ?>" style="width: 50px; height: 40px; border: 1px solid var(--sp-border-light); border-radius: var(--sp-radius-sm); cursor: pointer; padding: 2px;">
                        <span id="theme-color-label" style="font-family: monospace; font-size: 0.85rem; color: var(--sp-text-secondary);"><?php echo esc_html($settings['theme_color']); ?></span>
                    </div>
                </div>

                <div class="sp-form-group">
                    <label class="sp-form-label"><?php _e('لون الخلفية', 'saint-porphyrius'); ?></label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="color" name="background_color" value="<?php echo esc_attr($settings['background_color']); ?>" style="width: 50px; height: 40px; border: 1px solid var(--sp-border-light); border-radius: var(--sp-radius-sm); cursor: pointer; padding: 2px;">
                        <span id="bg-color-label" style="font-family: monospace; font-size: 0.85rem; color: var(--sp-text-secondary);"><?php echo esc_html($settings['background_color']); ?></span>
                    </div>
                </div>
            </div>

            <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                <label class="sp-form-label"><?php _e('وضع العرض', 'saint-porphyrius'); ?></label>
                <select name="display" class="sp-form-select">
                    <option value="standalone" <?php selected($settings['display'], 'standalone'); ?>><?php _e('مستقل (standalone) - مثل تطبيق حقيقي', 'saint-porphyrius'); ?></option>
                    <option value="fullscreen" <?php selected($settings['display'], 'fullscreen'); ?>><?php _e('ملء الشاشة (fullscreen)', 'saint-porphyrius'); ?></option>
                    <option value="minimal-ui" <?php selected($settings['display'], 'minimal-ui'); ?>><?php _e('واجهة بسيطة (minimal-ui)', 'saint-porphyrius'); ?></option>
                    <option value="browser" <?php selected($settings['display'], 'browser'); ?>><?php _e('متصفح (browser)', 'saint-porphyrius'); ?></option>
                </select>
            </div>

            <div class="sp-form-group" style="margin-bottom: var(--sp-space-md);">
                <label class="sp-form-label"><?php _e('اتجاه الشاشة', 'saint-porphyrius'); ?></label>
                <select name="orientation" class="sp-form-select">
                    <option value="portrait" <?php selected($settings['orientation'], 'portrait'); ?>><?php _e('عمودي (portrait)', 'saint-porphyrius'); ?></option>
                    <option value="landscape" <?php selected($settings['orientation'], 'landscape'); ?>><?php _e('أفقي (landscape)', 'saint-porphyrius'); ?></option>
                    <option value="any" <?php selected($settings['orientation'], 'any'); ?>><?php _e('الكل (any)', 'saint-porphyrius'); ?></option>
                </select>
            </div>

            <div class="sp-form-group">
                <label class="sp-form-label"><?php _e('شريط الحالة (iOS)', 'saint-porphyrius'); ?></label>
                <select name="apple_status_bar" class="sp-form-select">
                    <option value="default" <?php selected($settings['apple_status_bar'], 'default'); ?>><?php _e('افتراضي - أبيض', 'saint-porphyrius'); ?></option>
                    <option value="black" <?php selected($settings['apple_status_bar'], 'black'); ?>><?php _e('أسود', 'saint-porphyrius'); ?></option>
                    <option value="black-translucent" <?php selected($settings['apple_status_bar'], 'black-translucent'); ?>><?php _e('شفاف', 'saint-porphyrius'); ?></option>
                </select>
            </div>
        </div>

        <!-- Preview Card -->
        <div class="sp-card" style="padding: var(--sp-space-lg); margin-bottom: var(--sp-space-lg);">
            <h3 style="margin: 0 0 var(--sp-space-md); display: flex; align-items: center; gap: 8px;">
                👁️ <?php _e('معاينة', 'saint-porphyrius'); ?>
            </h3>
            <div style="background: #f5f5f5; border-radius: var(--sp-radius-lg); padding: var(--sp-space-xl); text-align: center;">
                <div style="width: 72px; height: 72px; margin: 0 auto var(--sp-space-sm); border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                    <img src="<?php echo esc_url(SP_PLUGIN_URL . 'assets/icons/icon-192x192.png'); ?>" alt="App Icon" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <div id="pwa-preview-name" style="font-size: 0.85rem; font-weight: 600; color: #333; margin-bottom: 2px;"><?php echo esc_html($settings['app_short_name']); ?></div>
                <div id="pwa-preview-desc" style="font-size: 0.7rem; color: #999;"><?php echo esc_html(mb_strimwidth($settings['app_description'], 0, 40, '...')); ?></div>
                <div style="margin-top: var(--sp-space-md);">
                    <span style="display: inline-block; width: 24px; height: 4px; border-radius: 2px; margin: 0 2px;" id="pwa-preview-bar-theme"></span>
                    <span style="display: inline-block; width: 24px; height: 4px; border-radius: 2px; background: #ddd; margin: 0 2px;"></span>
                </div>
            </div>
        </div>

        <!-- Current Manifest Info -->
        <div class="sp-card" style="padding: var(--sp-space-lg); margin-bottom: var(--sp-space-lg); background: var(--sp-bg-secondary);">
            <h3 style="margin: 0 0 var(--sp-space-sm); font-size: 0.9rem; display: flex; align-items: center; gap: 8px;">
                ℹ️ <?php _e('ملاحظات', 'saint-porphyrius'); ?>
            </h3>
            <ul style="margin: 0; padding: 0 var(--sp-space-lg); font-size: 0.8rem; color: var(--sp-text-muted); line-height: 1.8;">
                <li><?php _e('التغييرات تحدّث ملف manifest.json تلقائياً.', 'saint-porphyrius'); ?></li>
                <li><?php _e('قد يحتاج المستخدمون لإعادة تثبيت التطبيق لرؤية التغييرات.', 'saint-porphyrius'); ?></li>
                <li><?php _e('الأيقونات يمكن تغييرها عبر استبدال الملفات في مجلد assets/icons/.', 'saint-porphyrius'); ?></li>
                <li><?php _e('عدد الإشعارات يظهر تلقائياً على أيقونة التطبيق (في المتصفحات الداعمة).', 'saint-porphyrius'); ?></li>
            </ul>
        </div>

        <button type="submit" class="sp-btn sp-btn-primary sp-btn-lg sp-btn-block">
            💾 <?php _e('حفظ الإعدادات', 'saint-porphyrius'); ?>
        </button>
    </form>

</main>

<script>
(function() {
    // Live preview for color pickers
    var themeInput = document.querySelector('input[name="theme_color"]');
    var bgInput = document.querySelector('input[name="background_color"]');
    var shortNameInput = document.querySelector('input[name="app_short_name"]');
    var previewBar = document.getElementById('pwa-preview-bar-theme');
    var previewName = document.getElementById('pwa-preview-name');

    if (themeInput && previewBar) {
        previewBar.style.background = themeInput.value;
        themeInput.addEventListener('input', function() {
            previewBar.style.background = this.value;
            document.getElementById('theme-color-label').textContent = this.value;
        });
    }
    if (bgInput) {
        bgInput.addEventListener('input', function() {
            document.getElementById('bg-color-label').textContent = this.value;
        });
    }
    if (shortNameInput && previewName) {
        shortNameInput.addEventListener('input', function() {
            previewName.textContent = this.value || 'التطبيق';
        });
    }
})();
</script>
