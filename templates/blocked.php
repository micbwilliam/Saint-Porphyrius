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
    position: relative;
}

.sp-blocked-container {
    max-width: 500px;
    width: 100%;
    background: var(--sp-white);
    border-radius: var(--sp-radius-xl);
    padding: var(--sp-space-2xl);
    text-align: center;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.sp-blocked-icon {
    font-size: 100px;
    margin-bottom: var(--sp-space-lg);
    display: inline-block;
}

.sp-blocked-title {
    font-size: var(--sp-font-size-2xl);
    font-weight: var(--sp-font-bold);
    color: #991B1B;
    margin: 0 0 var(--sp-space-lg) 0;
    line-height: 1.4;
}

.sp-blocked-message {
    color: var(--sp-text-secondary);
    line-height: 1.8;
    margin-bottom: var(--sp-space-xl);
    font-size: var(--sp-font-size-base);
}

.sp-blocked-message p {
    margin-bottom: var(--sp-space-sm);
}

.sp-blocked-message p:first-child {
    font-size: var(--sp-font-size-lg);
    font-weight: var(--sp-font-semibold);
    color: #1F2937;
}

.sp-blocked-date {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--sp-space-xs);
    padding: var(--sp-space-md) var(--sp-space-lg);
    background: var(--sp-gray-50);
    border-radius: var(--sp-radius-md);
    margin-bottom: var(--sp-space-lg);
    font-size: var(--sp-font-size-sm);
}

.sp-blocked-date span {
    color: var(--sp-text-secondary);
}

.sp-blocked-date strong {
    color: #991B1B;
    font-size: var(--sp-font-size-base);
}

.sp-blocked-card-info {
    padding: var(--sp-space-lg) var(--sp-space-xl);
    background: #FEE2E2;
    border-radius: var(--sp-radius-lg);
    margin-bottom: var(--sp-space-xl);
    border-left: 4px solid #DC2626;
}

.sp-card-visual {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--sp-space-md);
    font-weight: var(--sp-font-bold);
    font-size: var(--sp-font-size-lg);
    color: #991B1B;
    margin-bottom: var(--sp-space-md);
}

.sp-card-icon.red {
    font-size: 60px;
    line-height: 1;
}

.sp-blocked-card-info p {
    color: #991B1B;
    font-size: var(--sp-font-size-base);
    margin: 0;
    font-weight: var(--sp-font-semibold);
}

.sp-blocked-actions {
    margin-bottom: var(--sp-space-xl);
}

.sp-blocked-info-box {
    display: flex;
    gap: var(--sp-space-md);
    padding: var(--sp-space-lg);
    background: #F3F4F6;
    border-radius: var(--sp-radius-lg);
    text-align: right;
    align-items: flex-start;
}

.sp-blocked-info-box .sp-info-icon {
    font-size: 32px;
    flex-shrink: 0;
    line-height: 1;
}

.sp-blocked-info-box h4 {
    margin: 0 0 8px 0;
    font-size: var(--sp-font-size-base);
    font-weight: var(--sp-font-semibold);
    color: #1F2937;
}

.sp-blocked-info-box p {
    margin: 0;
    font-size: var(--sp-font-size-sm);
    color: var(--sp-text-secondary);
    line-height: 1.6;
}

.sp-blocked-container .sp-btn {
    margin-top: var(--sp-space-sm);
    padding: var(--sp-space-md) var(--sp-space-lg);
    font-size: var(--sp-font-size-base);
    border-radius: var(--sp-radius-md);
}
</style>
