<?php
/**
 * Saint Porphyrius - Admin Point Sharing Settings
 * Configure fees for point sharing between members
 */

if (!defined('ABSPATH')) {
    exit;
}

$sharing = SP_Point_Sharing::get_instance();
$settings = $sharing->get_settings();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sp_point_sharing_nonce'])) {
    if (wp_verify_nonce($_POST['sp_point_sharing_nonce'], 'sp_point_sharing_settings')) {
        $new_settings = array(
            'fee_enabled'    => !empty($_POST['fee_enabled']) ? 1 : 0,
            'fee_type'       => sanitize_text_field($_POST['fee_type'] ?? 'percentage'),
            'fee_percentage' => floatval($_POST['fee_percentage'] ?? 10),
            'fee_fixed'      => absint($_POST['fee_fixed'] ?? 1),
            'fee_min'        => absint($_POST['fee_min'] ?? 0),
            'fee_max'        => absint($_POST['fee_max'] ?? 0),
        );

        $settings = $sharing->update_settings($new_settings);
        $success_message = __('تم حفظ الإعدادات بنجاح', 'saint-porphyrius');
    }
}

// Get sharing stats
global $wpdb;
$shares_table = $wpdb->prefix . 'sp_point_shares';
$total_shares = $wpdb->get_var("SELECT COUNT(*) FROM $shares_table");
$total_points_shared = $wpdb->get_var("SELECT COALESCE(SUM(points), 0) FROM $shares_table");
$unique_senders = $wpdb->get_var("SELECT COUNT(DISTINCT sender_id) FROM $shares_table");
$unique_recipients = $wpdb->get_var("SELECT COUNT(DISTINCT recipient_id) FROM $shares_table");

// Calculate example fee for display
$example_amount = 10;
$example_fee = $sharing->calculate_fee($example_amount);
?>

<!-- Unified Header -->
<div class="sp-unified-header sp-admin-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/admin'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('إعدادات مشاركة النقاط', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-actions"></div>
    </div>
</div>

<!-- Main Content -->
<main class="sp-page-content">
    <?php if (isset($success_message)): ?>
    <div class="sp-alert sp-alert-success" style="margin: var(--sp-space-lg);">
        <div class="sp-alert-icon">✅</div>
        <div class="sp-alert-content"><?php echo esc_html($success_message); ?></div>
    </div>
    <?php endif; ?>

    <!-- Stats Section -->
    <div class="sp-section" style="padding: var(--sp-space-lg);">
        <div class="sp-section-header">
            <h3 class="sp-section-title">📊 <?php _e('إحصائيات المشاركة', 'saint-porphyrius'); ?></h3>
        </div>
        <div class="sp-stats-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--sp-space-md);">
            <div class="sp-stat-card" style="background: var(--sp-success-light); border-radius: var(--sp-radius-lg); padding: var(--sp-space-lg); text-align: center;">
                <div class="sp-stat-value" style="font-size: var(--sp-font-size-2xl); font-weight: var(--sp-font-bold); color: var(--sp-success);">
                    <?php echo esc_html($total_shares); ?>
                </div>
                <div class="sp-stat-label" style="font-size: var(--sp-font-size-sm); color: var(--sp-text-secondary);">
                    <?php _e('عمليات مشاركة', 'saint-porphyrius'); ?>
                </div>
            </div>
            <div class="sp-stat-card" style="background: var(--sp-warning-light); border-radius: var(--sp-radius-lg); padding: var(--sp-space-lg); text-align: center;">
                <div class="sp-stat-value" style="font-size: var(--sp-font-size-2xl); font-weight: var(--sp-font-bold); color: var(--sp-warning);">
                    <?php echo esc_html($total_points_shared); ?>
                </div>
                <div class="sp-stat-label" style="font-size: var(--sp-font-size-sm); color: var(--sp-text-secondary);">
                    <?php _e('نقاط تم مشاركتها', 'saint-porphyrius'); ?>
                </div>
            </div>
            <div class="sp-stat-card" style="background: var(--sp-info-light); border-radius: var(--sp-radius-lg); padding: var(--sp-space-lg); text-align: center;">
                <div class="sp-stat-value" style="font-size: var(--sp-font-size-2xl); font-weight: var(--sp-font-bold); color: var(--sp-info);">
                    <?php echo esc_html($unique_senders); ?>
                </div>
                <div class="sp-stat-label" style="font-size: var(--sp-font-size-sm); color: var(--sp-text-secondary);">
                    <?php _e('أعضاء أرسلوا', 'saint-porphyrius'); ?>
                </div>
            </div>
            <div class="sp-stat-card" style="background: linear-gradient(135deg, #FCE7F3 0%, #FBCFE8 100%); border-radius: var(--sp-radius-lg); padding: var(--sp-space-lg); text-align: center;">
                <div class="sp-stat-value" style="font-size: var(--sp-font-size-2xl); font-weight: var(--sp-font-bold); color: #DB2777;">
                    <?php echo esc_html($unique_recipients); ?>
                </div>
                <div class="sp-stat-label" style="font-size: var(--sp-font-size-sm); color: var(--sp-text-secondary);">
                    <?php _e('أعضاء استقبلوا', 'saint-porphyrius'); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Form -->
    <div class="sp-section" style="padding: 0 var(--sp-space-lg) var(--sp-space-lg);">
        <form method="post" class="sp-card" style="padding: var(--sp-space-xl);" id="sp-sharing-settings-form">
            <?php wp_nonce_field('sp_point_sharing_settings', 'sp_point_sharing_nonce'); ?>

            <!-- Fee Enable/Disable -->
            <div class="sp-settings-section" style="margin-bottom: var(--sp-space-2xl);">
                <div style="display: flex; align-items: center; gap: var(--sp-space-md); margin-bottom: var(--sp-space-lg);">
                    <span style="font-size: 32px;">💰</span>
                    <div>
                        <h4 style="margin: 0; font-size: var(--sp-font-size-lg);"><?php _e('رسوم المشاركة', 'saint-porphyrius'); ?></h4>
                        <p style="margin: var(--sp-space-xs) 0 0; color: var(--sp-text-secondary); font-size: var(--sp-font-size-sm);">
                            <?php _e('فرض رسوم على كل عملية مشاركة نقاط. الرسوم تُخصم من المرسل بالإضافة إلى النقاط المُرسلة', 'saint-porphyrius'); ?>
                        </p>
                    </div>
                </div>

                <div class="sp-form-group" style="margin-bottom: var(--sp-space-lg);">
                    <label class="sp-checkbox">
                        <input type="checkbox" name="fee_enabled" value="1" id="sp-fee-enabled" <?php checked($settings['fee_enabled'], 1); ?>>
                        <span class="sp-checkbox-mark">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </span>
                        <span class="sp-checkbox-text"><?php _e('تفعيل رسوم المشاركة', 'saint-porphyrius'); ?></span>
                    </label>
                </div>
            </div>

            <!-- Fee Settings (shown when enabled) -->
            <div id="sp-fee-settings" style="<?php echo $settings['fee_enabled'] ? '' : 'opacity: 0.5; pointer-events: none;'; ?>">

                <!-- Fee Type -->
                <div class="sp-settings-section" style="margin-bottom: var(--sp-space-2xl);">
                    <div style="display: flex; align-items: center; gap: var(--sp-space-md); margin-bottom: var(--sp-space-lg);">
                        <span style="font-size: 32px;">📐</span>
                        <div>
                            <h4 style="margin: 0; font-size: var(--sp-font-size-lg);"><?php _e('نوع الرسوم', 'saint-porphyrius'); ?></h4>
                            <p style="margin: var(--sp-space-xs) 0 0; color: var(--sp-text-secondary); font-size: var(--sp-font-size-sm);">
                                <?php _e('اختر طريقة حساب الرسوم', 'saint-porphyrius'); ?>
                            </p>
                        </div>
                    </div>

                    <div style="display: flex; gap: var(--sp-space-md); margin-bottom: var(--sp-space-lg);">
                        <label class="sp-radio-card" style="flex: 1; cursor: pointer; padding: var(--sp-space-lg); border-radius: var(--sp-radius-lg); border: 2px solid var(--sp-border); text-align: center; transition: all 0.2s;">
                            <input type="radio" name="fee_type" value="percentage" <?php checked($settings['fee_type'], 'percentage'); ?> style="display: none;" class="sp-fee-type-radio">
                            <div style="font-size: 24px; margin-bottom: var(--sp-space-xs);">%</div>
                            <div style="font-weight: var(--sp-font-semibold);"><?php _e('نسبة مئوية', 'saint-porphyrius'); ?></div>
                            <div style="font-size: var(--sp-font-size-xs); color: var(--sp-text-secondary); margin-top: 2px;"><?php _e('نسبة من المبلغ', 'saint-porphyrius'); ?></div>
                        </label>
                        <label class="sp-radio-card" style="flex: 1; cursor: pointer; padding: var(--sp-space-lg); border-radius: var(--sp-radius-lg); border: 2px solid var(--sp-border); text-align: center; transition: all 0.2s;">
                            <input type="radio" name="fee_type" value="fixed" <?php checked($settings['fee_type'], 'fixed'); ?> style="display: none;" class="sp-fee-type-radio">
                            <div style="font-size: 24px; margin-bottom: var(--sp-space-xs);">#</div>
                            <div style="font-weight: var(--sp-font-semibold);"><?php _e('مبلغ ثابت', 'saint-porphyrius'); ?></div>
                            <div style="font-size: var(--sp-font-size-xs); color: var(--sp-text-secondary); margin-top: 2px;"><?php _e('عدد نقاط ثابت', 'saint-porphyrius'); ?></div>
                        </label>
                    </div>

                    <!-- Percentage Input -->
                    <div class="sp-form-group sp-fee-percentage-group" style="margin-bottom: var(--sp-space-md); <?php echo $settings['fee_type'] !== 'percentage' ? 'display: none;' : ''; ?>">
                        <label class="sp-form-label"><?php _e('نسبة الرسوم (%)', 'saint-porphyrius'); ?></label>
                        <input type="number" name="fee_percentage" class="sp-form-input"
                               value="<?php echo esc_attr($settings['fee_percentage']); ?>"
                               min="0" max="100" step="0.5" style="max-width: 150px;">
                        <p style="margin: var(--sp-space-xs) 0 0; color: var(--sp-text-secondary); font-size: var(--sp-font-size-xs);">
                            <?php _e('مثال: 10% تعني عند إرسال 10 نقاط، يُخصم 1 نقطة إضافية كرسوم', 'saint-porphyrius'); ?>
                        </p>
                    </div>

                    <!-- Fixed Input -->
                    <div class="sp-form-group sp-fee-fixed-group" style="margin-bottom: var(--sp-space-md); <?php echo $settings['fee_type'] !== 'fixed' ? 'display: none;' : ''; ?>">
                        <label class="sp-form-label"><?php _e('الرسوم الثابتة (نقاط)', 'saint-porphyrius'); ?></label>
                        <input type="number" name="fee_fixed" class="sp-form-input"
                               value="<?php echo esc_attr($settings['fee_fixed']); ?>"
                               min="0" max="1000" style="max-width: 150px;">
                        <p style="margin: var(--sp-space-xs) 0 0; color: var(--sp-text-secondary); font-size: var(--sp-font-size-xs);">
                            <?php _e('عدد نقاط ثابت يُخصم كرسوم من كل عملية مشاركة', 'saint-porphyrius'); ?>
                        </p>
                    </div>
                </div>

                <!-- Min/Max Fee -->
                <div class="sp-settings-section" style="margin-bottom: var(--sp-space-2xl);">
                    <div style="display: flex; align-items: center; gap: var(--sp-space-md); margin-bottom: var(--sp-space-lg);">
                        <span style="font-size: 32px;">📏</span>
                        <div>
                            <h4 style="margin: 0; font-size: var(--sp-font-size-lg);"><?php _e('حدود الرسوم', 'saint-porphyrius'); ?></h4>
                            <p style="margin: var(--sp-space-xs) 0 0; color: var(--sp-text-secondary); font-size: var(--sp-font-size-sm);">
                                <?php _e('تحديد الحد الأدنى والأقصى للرسوم (اختياري)', 'saint-porphyrius'); ?>
                            </p>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--sp-space-md);">
                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('الحد الأدنى', 'saint-porphyrius'); ?></label>
                            <input type="number" name="fee_min" class="sp-form-input"
                                   value="<?php echo esc_attr($settings['fee_min']); ?>"
                                   min="0" max="1000" placeholder="0">
                            <p style="margin: var(--sp-space-xs) 0 0; color: var(--sp-text-secondary); font-size: var(--sp-font-size-xs);">
                                <?php _e('0 = بدون حد أدنى', 'saint-porphyrius'); ?>
                            </p>
                        </div>
                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('الحد الأقصى', 'saint-porphyrius'); ?></label>
                            <input type="number" name="fee_max" class="sp-form-input"
                                   value="<?php echo esc_attr($settings['fee_max']); ?>"
                                   min="0" max="10000" placeholder="0">
                            <p style="margin: var(--sp-space-xs) 0 0; color: var(--sp-text-secondary); font-size: var(--sp-font-size-xs);">
                                <?php _e('0 = بدون حد أقصى', 'saint-porphyrius'); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Live Preview -->
                <div class="sp-settings-section" style="margin-bottom: var(--sp-space-2xl);">
                    <div style="display: flex; align-items: center; gap: var(--sp-space-md); margin-bottom: var(--sp-space-lg);">
                        <span style="font-size: 32px;">🔍</span>
                        <div>
                            <h4 style="margin: 0; font-size: var(--sp-font-size-lg);"><?php _e('معاينة الرسوم', 'saint-porphyrius'); ?></h4>
                            <p style="margin: var(--sp-space-xs) 0 0; color: var(--sp-text-secondary); font-size: var(--sp-font-size-sm);">
                                <?php _e('مثال على حساب الرسوم', 'saint-porphyrius'); ?>
                            </p>
                        </div>
                    </div>

                    <div style="background: var(--sp-bg-secondary, #f8f9fa); border-radius: var(--sp-radius-lg); padding: var(--sp-space-lg);">
                        <div style="display: flex; align-items: center; gap: var(--sp-space-sm); margin-bottom: var(--sp-space-md);">
                            <label class="sp-form-label" style="margin: 0; white-space: nowrap;"><?php _e('عند إرسال', 'saint-porphyrius'); ?></label>
                            <input type="number" id="sp-preview-amount" value="10" min="1" max="1000"
                                   class="sp-form-input" style="max-width: 100px; text-align: center;">
                            <span style="color: var(--sp-text-secondary);"><?php _e('نقطة', 'saint-porphyrius'); ?></span>
                        </div>
                        <div id="sp-fee-preview-result" style="border-top: 1px solid var(--sp-border); padding-top: var(--sp-space-md);">
                            <div style="display: flex; justify-content: space-between; margin-bottom: var(--sp-space-xs);">
                                <span style="color: var(--sp-text-secondary);"><?php _e('النقاط المُرسلة', 'saint-porphyrius'); ?></span>
                                <span id="sp-preview-points" style="font-weight: var(--sp-font-semibold);">10</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: var(--sp-space-xs);">
                                <span style="color: var(--sp-text-secondary);"><?php _e('الرسوم', 'saint-porphyrius'); ?></span>
                                <span id="sp-preview-fee" style="font-weight: var(--sp-font-semibold); color: #E11D48;">0</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; border-top: 1px dashed var(--sp-border); padding-top: var(--sp-space-xs);">
                                <span style="font-weight: var(--sp-font-bold);"><?php _e('إجمالي الخصم', 'saint-porphyrius'); ?></span>
                                <span id="sp-preview-total" style="font-weight: var(--sp-font-bold); color: #E11D48;">10</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="sp-btn sp-btn-primary sp-btn-lg sp-btn-block">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <?php _e('حفظ الإعدادات', 'saint-porphyrius'); ?>
            </button>
        </form>
    </div>
</main>

<script>
(function() {
    var feeEnabled = document.getElementById('sp-fee-enabled');
    var feeSettings = document.getElementById('sp-fee-settings');
    var feeTypeRadios = document.querySelectorAll('.sp-fee-type-radio');
    var percentageGroup = document.querySelector('.sp-fee-percentage-group');
    var fixedGroup = document.querySelector('.sp-fee-fixed-group');
    var previewAmount = document.getElementById('sp-preview-amount');

    // Toggle fee settings visibility
    feeEnabled.addEventListener('change', function() {
        if (this.checked) {
            feeSettings.style.opacity = '1';
            feeSettings.style.pointerEvents = 'auto';
        } else {
            feeSettings.style.opacity = '0.5';
            feeSettings.style.pointerEvents = 'none';
        }
        updatePreview();
    });

    // Toggle fee type inputs and style radio cards
    function updateFeeTypeUI() {
        feeTypeRadios.forEach(function(radio) {
            var card = radio.closest('label');
            if (radio.checked) {
                card.style.borderColor = 'var(--sp-primary)';
                card.style.background = 'var(--sp-primary-light, #EBF5FF)';
            } else {
                card.style.borderColor = 'var(--sp-border)';
                card.style.background = 'transparent';
            }
        });

        var selectedType = document.querySelector('.sp-fee-type-radio:checked');
        if (selectedType && selectedType.value === 'fixed') {
            percentageGroup.style.display = 'none';
            fixedGroup.style.display = '';
        } else {
            percentageGroup.style.display = '';
            fixedGroup.style.display = 'none';
        }
    }

    feeTypeRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            updateFeeTypeUI();
            updatePreview();
        });
    });

    // Initial radio card styling
    updateFeeTypeUI();

    // Live fee preview calculator
    function calculateFee(amount) {
        if (!feeEnabled.checked) return 0;

        var selectedType = document.querySelector('.sp-fee-type-radio:checked');
        var type = selectedType ? selectedType.value : 'percentage';
        var fee = 0;

        if (type === 'fixed') {
            fee = parseInt(document.querySelector('input[name="fee_fixed"]').value) || 0;
        } else {
            var pct = parseFloat(document.querySelector('input[name="fee_percentage"]').value) || 0;
            fee = Math.ceil(amount * pct / 100);
        }

        var feeMin = parseInt(document.querySelector('input[name="fee_min"]').value) || 0;
        var feeMax = parseInt(document.querySelector('input[name="fee_max"]').value) || 0;

        if (feeMin > 0 && fee < feeMin) fee = feeMin;
        if (feeMax > 0 && fee > feeMax) fee = feeMax;

        return fee;
    }

    function updatePreview() {
        var amount = parseInt(previewAmount.value) || 0;
        var fee = calculateFee(amount);
        var total = amount + fee;

        document.getElementById('sp-preview-points').textContent = amount;
        document.getElementById('sp-preview-fee').textContent = fee;
        document.getElementById('sp-preview-total').textContent = total;
    }

    previewAmount.addEventListener('input', updatePreview);
    document.querySelector('input[name="fee_percentage"]').addEventListener('input', updatePreview);
    document.querySelector('input[name="fee_fixed"]').addEventListener('input', updatePreview);
    document.querySelector('input[name="fee_min"]').addEventListener('input', updatePreview);
    document.querySelector('input[name="fee_max"]').addEventListener('input', updatePreview);

    // Initial preview
    updatePreview();
})();
</script>
