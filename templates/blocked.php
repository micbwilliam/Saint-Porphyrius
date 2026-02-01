<?php
/**
 * Saint Porphyrius - Blocked User Page
 * Shows when user is blocked due to red card
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$first_name = $current_user->first_name ?: $current_user->display_name;

// Get blocked info
$forbidden_handler = SP_Forbidden::get_instance();
$user_status = $forbidden_handler->get_user_status($current_user->ID);
$blocked_date = $user_status->blocked_at ? date_i18n('j F Y', strtotime($user_status->blocked_at)) : '';
?>

<div class="sp-blocked-page">
    <div class="sp-blocked-container">
        <div class="sp-blocked-icon">🔴</div>
        
        <h1 class="sp-blocked-title"><?php _e('حسابك محظور', 'saint-porphyrius'); ?></h1>
        
        <div class="sp-blocked-message">
            <p><?php printf(__('مرحباً %s،', 'saint-porphyrius'), esc_html($first_name)); ?></p>
            <p><?php _e('تم إيقاف حسابك مؤقتاً بسبب تكرار الغياب بدون عذر.', 'saint-porphyrius'); ?></p>
            <p><?php _e('لقد حصلت على كارت أحمر 🔴 مما يعني أنك لا تستطيع الوصول للتطبيق حتى يتم إعادة تفعيل حسابك.', 'saint-porphyrius'); ?></p>
        </div>
        
        <?php if ($blocked_date): ?>
        <div class="sp-blocked-date">
            <span><?php _e('تاريخ الحظر:', 'saint-porphyrius'); ?></span>
            <strong><?php echo esc_html($blocked_date); ?></strong>
        </div>
        <?php endif; ?>
        
        <div class="sp-blocked-card-info">
            <div class="sp-card-visual">
                <div class="sp-card-icon red">🔴</div>
                <span><?php _e('كارت أحمر', 'saint-porphyrius'); ?></span>
            </div>
            <p><?php printf(__('الغيابات المتتالية: %d', 'saint-porphyrius'), $user_status->consecutive_absences); ?></p>
        </div>
        
        <div class="sp-blocked-actions">
            <div class="sp-blocked-info-box">
                <span class="sp-info-icon">📞</span>
                <div>
                    <h4><?php _e('تواصل مع المسؤول', 'saint-porphyrius'); ?></h4>
                    <p><?php _e('لإعادة تفعيل حسابك، يرجى التواصل مع مسؤول الأسرة لمناقشة الموضوع.', 'saint-porphyrius'); ?></p>
                </div>
            </div>
        </div>
        
        <a href="<?php echo home_url('/app/logout'); ?>" class="sp-btn sp-btn-outline sp-btn-block">
            <?php _e('تسجيل الخروج', 'saint-porphyrius'); ?>
        </a>
    </div>
</div>

<style>
.sp-blocked-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%);
}

.sp-blocked-container {
    max-width: 400px;
    width: 100%;
    background: var(--sp-white);
    border-radius: var(--sp-radius-xl);
    padding: var(--sp-space-2xl);
    text-align: center;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.sp-blocked-icon {
    font-size: 80px;
    margin-bottom: var(--sp-space-lg);
}

.sp-blocked-title {
    font-size: var(--sp-font-size-2xl);
    font-weight: var(--sp-font-bold);
    color: #991B1B;
    margin-bottom: var(--sp-space-lg);
}

.sp-blocked-message {
    color: var(--sp-text-secondary);
    line-height: 1.7;
    margin-bottom: var(--sp-space-xl);
}

.sp-blocked-message p {
    margin-bottom: var(--sp-space-sm);
}

.sp-blocked-date {
    display: flex;
    justify-content: center;
    gap: var(--sp-space-sm);
    padding: var(--sp-space-md);
    background: var(--sp-gray-50);
    border-radius: var(--sp-radius-md);
    margin-bottom: var(--sp-space-lg);
    font-size: var(--sp-font-size-sm);
}

.sp-blocked-card-info {
    padding: var(--sp-space-lg);
    background: #FEE2E2;
    border-radius: var(--sp-radius-lg);
    margin-bottom: var(--sp-space-xl);
}

.sp-card-visual {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--sp-space-sm);
    font-weight: var(--sp-font-bold);
    font-size: var(--sp-font-size-lg);
    color: #991B1B;
    margin-bottom: var(--sp-space-sm);
}

.sp-card-icon.red {
    font-size: 24px;
}

.sp-blocked-card-info p {
    color: #B91C1C;
    font-size: var(--sp-font-size-sm);
    margin: 0;
}

.sp-blocked-actions {
    margin-bottom: var(--sp-space-xl);
}

.sp-blocked-info-box {
    display: flex;
    gap: var(--sp-space-md);
    padding: var(--sp-space-lg);
    background: var(--sp-gray-50);
    border-radius: var(--sp-radius-lg);
    text-align: right;
}

.sp-blocked-info-box .sp-info-icon {
    font-size: 28px;
    flex-shrink: 0;
}

.sp-blocked-info-box h4 {
    margin: 0 0 4px 0;
    font-size: var(--sp-font-size-base);
    font-weight: var(--sp-font-semibold);
}

.sp-blocked-info-box p {
    margin: 0;
    font-size: var(--sp-font-size-sm);
    color: var(--sp-text-secondary);
}
</style>
