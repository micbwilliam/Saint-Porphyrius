<?php
/**
 * Saint Porphyrius - Share Points Template (Unified Design)
 * Allows users to share/gift points to other members
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$current_user = wp_get_current_user();
$points_handler = SP_Points::get_instance();
$sharing_handler = SP_Point_Sharing::get_instance();

$balance = $points_handler->get_balance($user_id);
$share_stats = $sharing_handler->get_share_stats($user_id);
$share_history = $sharing_handler->get_share_history($user_id, array('limit' => 20));
$share_history_sent = $sharing_handler->get_share_history($user_id, array('limit' => 20, 'direction' => 'sent'));
$share_history_received = $sharing_handler->get_share_history($user_id, array('limit' => 20, 'direction' => 'received'));
$sharing_settings = $sharing_handler->get_settings();
$fee_enabled = !empty($sharing_settings['fee_enabled']);

// Get current rank
$leaderboard = $points_handler->get_leaderboard(100);
$user_rank = 0;
foreach ($leaderboard as $index => $entry) {
    if ($entry->user_id == $user_id) {
        $user_rank = $index + 1;
        break;
    }
}

$middle_name = get_user_meta($user_id, 'sp_middle_name', true);
$display_name = trim($current_user->first_name . ' ' . $middle_name) ?: $current_user->display_name;
?>

<!-- Unified Header -->
<div class="sp-unified-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/points'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('مشاركة النقاط', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-spacer"></div>
    </div>
</div>

<!-- Main Content -->
<main class="sp-page-content has-bottom-nav">
    <!-- Hero Card -->
    <div class="sp-hero-card" style="background: linear-gradient(135deg, #F472B6 0%, #EC4899 100%);">
        <div class="sp-hero-content" style="flex-direction: column; align-items: center; text-align: center;">
            <div style="font-size: 48px; margin-bottom: 8px;">🎁</div>
            <div style="font-size: 36px; font-weight: 700; line-height: 1;"><?php echo esc_html($balance); ?> <span style="font-size: 16px; font-weight: 400;"><?php _e('نقطة', 'saint-porphyrius'); ?></span></div>
            <div style="font-size: 14px; opacity: 0.9; margin-top: 4px;"><?php _e('ترتيبك', 'saint-porphyrius'); ?> #<?php echo esc_html($user_rank ?: '-'); ?></div>
            <div style="font-size: 13px; opacity: 0.8; margin-top: 8px;"><?php _e('شارك نقاطك مع أعضاء الأسرة بمحبة', 'saint-porphyrius'); ?></div>
        </div>
    </div>

    <!-- Share Form: Recipient Search -->
    <?php if ($fee_enabled): ?>
    <div style="padding: 0 var(--sp-space-lg); margin-bottom: var(--sp-space-sm);">
        <div style="background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); border-radius: var(--sp-radius-lg); padding: var(--sp-space-md) var(--sp-space-lg); display: flex; align-items: center; gap: var(--sp-space-sm);">
            <span style="font-size: 20px;">💰</span>
            <div style="font-size: var(--sp-font-size-sm); color: #92400E;">
                <?php if ($sharing_settings['fee_type'] === 'fixed'): ?>
                    <?php printf(__('يتم خصم %d نقطة رسوم على كل عملية مشاركة', 'saint-porphyrius'), $sharing_settings['fee_fixed']); ?>
                <?php else: ?>
                    <?php printf(__('يتم خصم %s%% رسوم على كل عملية مشاركة', 'saint-porphyrius'), rtrim(rtrim(number_format($sharing_settings['fee_percentage'], 1), '0'), '.')); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="sp-share-form-card">
        <h3 class="sp-share-card-title"><?php _e('اختر عضو', 'saint-porphyrius'); ?></h3>
        <div class="sp-search-input-wrapper" id="sp-search-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" id="sp-share-recipient-search"
                   placeholder="<?php _e('بحث عن عضو...', 'saint-porphyrius'); ?>" class="sp-search-input" autocomplete="off">
        </div>
        <div id="sp-share-search-results" class="sp-share-search-results"></div>
        <div id="sp-share-selected-recipient" class="sp-share-selected-recipient" style="display: none;"></div>
    </div>

    <!-- Share Form: Amount -->
    <div class="sp-share-form-card">
        <h3 class="sp-share-card-title"><?php _e('عدد النقاط', 'saint-porphyrius'); ?></h3>
        <div class="sp-share-amount-input-wrapper">
            <input type="number" id="sp-share-amount" min="1"
                   max="<?php echo esc_attr($balance); ?>"
                   placeholder="0" class="sp-share-amount-input">
            <span class="sp-share-amount-label"><?php _e('نقطة', 'saint-porphyrius'); ?></span>
        </div>
        <div class="sp-share-quick-amounts">
            <?php if ($balance >= 5): ?>
                <button type="button" class="sp-quick-amount" data-amount="5">5</button>
            <?php endif; ?>
            <?php if ($balance >= 10): ?>
                <button type="button" class="sp-quick-amount" data-amount="10">10</button>
            <?php endif; ?>
            <?php if ($balance >= 25): ?>
                <button type="button" class="sp-quick-amount" data-amount="25">25</button>
            <?php endif; ?>
            <?php if ($balance >= 50): ?>
                <button type="button" class="sp-quick-amount" data-amount="50">50</button>
            <?php endif; ?>
        </div>
        <div class="sp-share-balance-hint">
            <?php printf(__('رصيدك المتاح: %d نقطة', 'saint-porphyrius'), $balance); ?>
        </div>
    </div>

    <!-- Share Form: Message -->
    <div class="sp-share-form-card">
        <h3 class="sp-share-card-title"><?php _e('رسالة (اختياري)', 'saint-porphyrius'); ?></h3>
        <input type="text" id="sp-share-message" maxlength="191"
               placeholder="<?php _e('كلمة تشجيع أو محبة...', 'saint-porphyrius'); ?>" class="sp-share-message-input">
    </div>

    <!-- Rank Impact Preview -->
    <div id="sp-share-preview" class="sp-share-preview" style="display: none;">
        <div class="sp-share-preview-header">
            ⚠️ <?php _e('تأثير المشاركة على ترتيبك', 'saint-porphyrius'); ?>
        </div>
        <div class="sp-share-preview-content">
            <div class="sp-share-preview-row">
                <div class="sp-share-preview-item">
                    <span class="sp-preview-label"><?php _e('رصيدك الحالي', 'saint-porphyrius'); ?></span>
                    <span class="sp-preview-value" id="sp-preview-current-balance">0</span>
                </div>
                <div class="sp-share-preview-arrow">↓</div>
                <div class="sp-share-preview-item">
                    <span class="sp-preview-label"><?php _e('رصيدك بعد المشاركة', 'saint-porphyrius'); ?></span>
                    <span class="sp-preview-value sp-preview-new" id="sp-preview-new-balance">0</span>
                </div>
            </div>
            <div id="sp-preview-fee-section" style="display: none; background: #FEF3C7; border-radius: var(--sp-radius-md); padding: var(--sp-space-sm) var(--sp-space-md); margin: var(--sp-space-sm) 0;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: var(--sp-font-size-sm); color: #92400E;">💰 <?php _e('الرسوم', 'saint-porphyrius'); ?></span>
                    <span id="sp-preview-fee" style="font-weight: var(--sp-font-bold); color: #B45309;">0 <?php _e('نقطة', 'saint-porphyrius'); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px; border-top: 1px dashed #D4A574; padding-top: 4px;">
                    <span style="font-size: var(--sp-font-size-sm); color: #92400E;"><?php _e('إجمالي الخصم من رصيدك', 'saint-porphyrius'); ?></span>
                    <span id="sp-preview-total-cost" style="font-weight: var(--sp-font-bold); color: #E11D48;">0 <?php _e('نقطة', 'saint-porphyrius'); ?></span>
                </div>
            </div>
            <div class="sp-share-preview-rank">
                <div class="sp-rank-change">
                    <span id="sp-preview-rank-before">#<?php echo esc_html($user_rank); ?></span>
                    <span class="sp-rank-arrow">←</span>
                    <span id="sp-preview-rank-after" class="sp-rank-same">#<?php echo esc_html($user_rank); ?></span>
                </div>
                <p class="sp-rank-warning" id="sp-rank-warning-text" style="display: none;"></p>
            </div>
        </div>
    </div>

    <!-- Share Button -->
    <div style="padding: 0 var(--sp-space-lg) var(--sp-space-md);">
        <button type="button" id="sp-share-submit" class="sp-share-submit-btn" disabled>
            🎁 <?php _e('مشاركة النقاط', 'saint-porphyrius'); ?>
        </button>
    </div>

    <!-- Stats Row -->
    <div class="sp-stats-row">
        <div class="sp-stat-card">
            <div class="sp-stat-value" style="color: #E11D48;"><?php echo esc_html($share_stats->total_sent); ?></div>
            <div class="sp-stat-label"><?php _e('أرسلت', 'saint-porphyrius'); ?></div>
        </div>
        <div class="sp-stat-card">
            <div class="sp-stat-value" style="color: #7C3AED;"><?php echo esc_html($share_stats->total_received); ?></div>
            <div class="sp-stat-label"><?php _e('استقبلت', 'saint-porphyrius'); ?></div>
        </div>
        <div class="sp-stat-card">
            <div class="sp-stat-value"><?php echo esc_html($share_stats->sent_count + $share_stats->received_count); ?></div>
            <div class="sp-stat-label"><?php _e('عمليات', 'saint-porphyrius'); ?></div>
        </div>
    </div>

    <!-- Sharing History -->
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title"><?php _e('سجل المشاركة', 'saint-porphyrius'); ?></h3>
        </div>

        <div class="sp-tabs sp-share-tabs">
            <button class="sp-tab active" data-tab="all"><?php _e('الكل', 'saint-porphyrius'); ?></button>
            <button class="sp-tab" data-tab="sent"><?php _e('أرسلت', 'saint-porphyrius'); ?></button>
            <button class="sp-tab" data-tab="received"><?php _e('استقبلت', 'saint-porphyrius'); ?></button>
        </div>

        <!-- All Tab -->
        <div class="sp-share-tab-content" id="share-tab-all">
            <?php if (empty($share_history)): ?>
                <div class="sp-card">
                    <div class="sp-empty">
                        <div class="sp-empty-icon">🎁</div>
                        <h4 class="sp-empty-title"><?php _e('لا يوجد مشاركات بعد', 'saint-porphyrius'); ?></h4>
                        <p class="sp-empty-text"><?php _e('شارك نقاطك مع أعضاء الأسرة', 'saint-porphyrius'); ?></p>
                    </div>
                </div>
            <?php else: ?>
                <div class="sp-share-history-list">
                    <?php foreach ($share_history as $share): ?>
                        <?php echo sp_render_share_history_item($share); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sent Tab -->
        <div class="sp-share-tab-content" id="share-tab-sent" style="display: none;">
            <?php if (empty($share_history_sent)): ?>
                <div class="sp-card">
                    <div class="sp-empty">
                        <div class="sp-empty-icon">📤</div>
                        <h4 class="sp-empty-title"><?php _e('لم ترسل نقاط بعد', 'saint-porphyrius'); ?></h4>
                    </div>
                </div>
            <?php else: ?>
                <div class="sp-share-history-list">
                    <?php foreach ($share_history_sent as $share): ?>
                        <?php echo sp_render_share_history_item($share); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Received Tab -->
        <div class="sp-share-tab-content" id="share-tab-received" style="display: none;">
            <?php if (empty($share_history_received)): ?>
                <div class="sp-card">
                    <div class="sp-empty">
                        <div class="sp-empty-icon">📥</div>
                        <h4 class="sp-empty-title"><?php _e('لم تستقبل نقاط بعد', 'saint-porphyrius'); ?></h4>
                    </div>
                </div>
            <?php else: ?>
                <div class="sp-share-history-list">
                    <?php foreach ($share_history_received as $share): ?>
                        <?php echo sp_render_share_history_item($share); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Confirmation Modal -->
<div class="sp-share-modal-overlay" id="sp-confirm-overlay">
    <div class="sp-share-modal">
        <div class="sp-share-modal-handle"></div>
        <h3 style="text-align: center; margin: 0 0 var(--sp-space-lg);"><?php _e('تأكيد المشاركة', 'saint-porphyrius'); ?></h3>

        <div class="sp-confirm-details">
            <div class="sp-confirm-row">
                <span class="sp-confirm-label"><?php _e('إلى:', 'saint-porphyrius'); ?></span>
                <span class="sp-confirm-value" id="sp-confirm-recipient-name"></span>
            </div>
            <div class="sp-confirm-row">
                <span class="sp-confirm-label"><?php _e('النقاط:', 'saint-porphyrius'); ?></span>
                <span class="sp-confirm-value sp-confirm-amount" id="sp-confirm-amount"></span>
            </div>
            <div class="sp-confirm-row" id="sp-confirm-fee-row" style="display: none;">
                <span class="sp-confirm-label"><?php _e('الرسوم:', 'saint-porphyrius'); ?></span>
                <span class="sp-confirm-value" id="sp-confirm-fee" style="color: #B45309;"></span>
            </div>
            <div class="sp-confirm-row" id="sp-confirm-total-row" style="display: none;">
                <span class="sp-confirm-label"><?php _e('إجمالي الخصم:', 'saint-porphyrius'); ?></span>
                <span class="sp-confirm-value" id="sp-confirm-total" style="color: #E11D48; font-weight: bold;"></span>
            </div>
            <div class="sp-confirm-row">
                <span class="sp-confirm-label"><?php _e('رسالة:', 'saint-porphyrius'); ?></span>
                <span class="sp-confirm-value" id="sp-confirm-message" style="font-style: italic;"></span>
            </div>
        </div>

        <div class="sp-confirm-rank-warning" id="sp-confirm-rank-change" style="display: none;"></div>

        <div class="sp-confirm-buttons">
            <button type="button" id="sp-confirm-btn" class="sp-share-submit-btn">
                <?php _e('تأكيد المشاركة', 'saint-porphyrius'); ?>
            </button>
            <button type="button" id="sp-cancel-btn" class="sp-share-cancel-btn">
                <?php _e('إلغاء', 'saint-porphyrius'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="sp-share-modal-overlay" id="sp-success-overlay">
    <div class="sp-share-modal">
        <div class="sp-share-modal-handle"></div>
        <div class="sp-share-success-icon">🎉</div>
        <h3 style="text-align: center; margin: var(--sp-space-md) 0;"><?php _e('تمت المشاركة بنجاح!', 'saint-porphyrius'); ?></h3>

        <div class="sp-success-details">
            <p style="text-align: center; font-size: 16px; color: var(--sp-text-primary);">
                <?php _e('تم إرسال', 'saint-porphyrius'); ?>
                <strong id="sp-success-amount">0</strong>
                <?php _e('نقطة إلى', 'saint-porphyrius'); ?>
                <strong id="sp-success-recipient"></strong>
            </p>
            <div class="sp-success-stats">
                <div class="sp-success-stat">
                    <span class="sp-success-stat-label"><?php _e('رصيدك الجديد', 'saint-porphyrius'); ?></span>
                    <span class="sp-success-stat-value" id="sp-success-new-balance">0</span>
                </div>
                <div class="sp-success-stat" id="sp-success-rank-section">
                    <span class="sp-success-stat-label"><?php _e('ترتيبك', 'saint-porphyrius'); ?></span>
                    <span class="sp-success-stat-value" id="sp-success-rank-change"></span>
                </div>
            </div>
        </div>

        <button type="button" id="sp-success-ok-btn" class="sp-share-submit-btn" style="margin-top: var(--sp-space-lg);">
            <?php _e('حسناً', 'saint-porphyrius'); ?>
        </button>
    </div>
</div>

<!-- Unified Bottom Navigation -->
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
        <a href="<?php echo home_url('/app/points'); ?>" class="sp-nav-item active">
            <div class="sp-nav-indicator"></div>
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

<?php
/**
 * Render a share history item
 */
function sp_render_share_history_item($share) {
    $is_sent = ($share->direction === 'sent');
    $direction_class = $is_sent ? 'sent' : 'received';
    $direction_icon = $is_sent ? '↗' : '↙';
    $points_prefix = $is_sent ? '-' : '+';

    ob_start();
    ?>
    <div class="sp-share-history-item">
        <div class="sp-share-direction-icon <?php echo $direction_class; ?>">
            <?php echo $direction_icon; ?>
        </div>
        <div class="sp-share-history-avatar">
            <?php echo esc_html($share->other_initial); ?>
        </div>
        <div class="sp-share-history-info">
            <div class="sp-share-history-name">
                <?php echo esc_html($share->other_name); ?>
                <span class="sp-share-history-gender"><?php echo $share->other_gender === 'female' ? '👩' : '👨'; ?></span>
            </div>
            <?php if (!empty($share->message)): ?>
                <div class="sp-share-history-message">"<?php echo esc_html($share->message); ?>"</div>
            <?php endif; ?>
            <div class="sp-share-history-date"><?php echo esc_html(date_i18n('j F Y - H:i', strtotime($share->created_at))); ?></div>
        </div>
        <div class="sp-share-history-points <?php echo $direction_class; ?>">
            <?php echo $points_prefix . esc_html($share->points); ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>

<style>
/* Share Form Cards */
.sp-share-form-card {
    background: var(--sp-bg-card);
    border-radius: var(--sp-radius-lg);
    padding: var(--sp-space-lg);
    margin: 0 var(--sp-space-lg) var(--sp-space-md);
    box-shadow: var(--sp-shadow-sm);
}

.sp-share-card-title {
    font-size: var(--sp-font-size-base);
    font-weight: var(--sp-font-semibold);
    margin: 0 0 var(--sp-space-md);
    color: var(--sp-text-primary);
}

/* Search Results */
.sp-share-search-results {
    max-height: 240px;
    overflow-y: auto;
    margin-top: var(--sp-space-sm);
}

.sp-share-search-result-item {
    display: flex;
    align-items: center;
    gap: var(--sp-space-md);
    padding: var(--sp-space-sm) var(--sp-space-md);
    border-radius: var(--sp-radius-md);
    cursor: pointer;
    transition: background 0.2s;
}

.sp-share-search-result-item:active {
    background: var(--sp-gray-50);
}

.sp-share-result-avatar {
    width: 40px;
    height: 40px;
    min-width: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #F472B6, #EC4899);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: var(--sp-font-bold);
    font-size: var(--sp-font-size-base);
}

.sp-share-result-info {
    flex: 1;
    min-width: 0;
}

.sp-share-result-name {
    font-weight: var(--sp-font-semibold);
    font-size: var(--sp-font-size-base);
}

.sp-share-result-points {
    font-size: var(--sp-font-size-xs);
    color: var(--sp-text-secondary);
}

/* Selected Recipient */
.sp-share-selected-recipient {
    display: flex;
    align-items: center;
    gap: var(--sp-space-md);
    background: linear-gradient(135deg, rgba(244, 114, 182, 0.1) 0%, rgba(236, 72, 153, 0.05) 100%);
    border: 2px solid #F9A8D4;
    border-radius: var(--sp-radius-lg);
    padding: var(--sp-space-sm) var(--sp-space-md);
    margin-top: var(--sp-space-md);
}

.sp-share-selected-avatar {
    width: 40px;
    height: 40px;
    min-width: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #F472B6, #EC4899);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: var(--sp-font-bold);
}

.sp-share-selected-info {
    flex: 1;
    font-weight: var(--sp-font-semibold);
}

.sp-share-remove-btn {
    background: none;
    border: none;
    color: var(--sp-text-secondary);
    cursor: pointer;
    padding: 8px;
    font-size: 18px;
    line-height: 1;
}

/* Amount Input */
.sp-share-amount-input-wrapper {
    display: flex;
    align-items: center;
    background: var(--sp-gray-50);
    border-radius: var(--sp-radius-md);
    padding: var(--sp-space-sm) var(--sp-space-md);
    gap: var(--sp-space-sm);
}

.sp-share-amount-input {
    flex: 1;
    border: none;
    background: none;
    font-size: 32px;
    font-weight: var(--sp-font-bold);
    text-align: center;
    color: var(--sp-text-primary);
    font-family: 'Cairo', sans-serif;
    outline: none;
    width: 100%;
    -moz-appearance: textfield;
}

.sp-share-amount-input::-webkit-outer-spin-button,
.sp-share-amount-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.sp-share-amount-label {
    font-size: var(--sp-font-size-base);
    color: var(--sp-text-secondary);
    font-weight: var(--sp-font-medium);
}

/* Quick Amount Buttons */
.sp-share-quick-amounts {
    display: flex;
    gap: var(--sp-space-sm);
    margin-top: var(--sp-space-md);
    justify-content: center;
    flex-wrap: wrap;
}

.sp-quick-amount {
    padding: 8px 20px;
    border-radius: 20px;
    border: 2px solid var(--sp-border);
    background: var(--sp-bg-card);
    font-family: 'Cairo', sans-serif;
    font-weight: var(--sp-font-semibold);
    font-size: var(--sp-font-size-base);
    cursor: pointer;
    transition: all 0.2s;
    color: var(--sp-text-primary);
}

.sp-quick-amount:active,
.sp-quick-amount.selected {
    border-color: #EC4899;
    background: rgba(236, 72, 153, 0.1);
    color: #EC4899;
}

.sp-share-balance-hint {
    text-align: center;
    font-size: var(--sp-font-size-xs);
    color: var(--sp-text-secondary);
    margin-top: var(--sp-space-sm);
}

/* Message Input */
.sp-share-message-input {
    width: 100%;
    border: 2px solid var(--sp-border);
    border-radius: var(--sp-radius-md);
    padding: var(--sp-space-sm) var(--sp-space-md);
    font-family: 'Cairo', sans-serif;
    font-size: var(--sp-font-size-base);
    outline: none;
    background: var(--sp-gray-50);
    box-sizing: border-box;
    transition: border-color 0.2s;
}

.sp-share-message-input:focus {
    border-color: #F472B6;
}

/* Rank Impact Preview */
.sp-share-preview {
    background: var(--sp-bg-card);
    border-radius: var(--sp-radius-lg);
    margin: 0 var(--sp-space-lg) var(--sp-space-md);
    padding: var(--sp-space-lg);
    border: 2px solid #FEF3C7;
    box-shadow: var(--sp-shadow-sm);
}

.sp-share-preview-header {
    font-weight: var(--sp-font-semibold);
    color: #92400E;
    margin-bottom: var(--sp-space-md);
    text-align: center;
    font-size: var(--sp-font-size-sm);
}

.sp-share-preview-row {
    text-align: center;
}

.sp-share-preview-item {
    display: flex;
    justify-content: space-between;
    padding: var(--sp-space-xs) 0;
}

.sp-preview-label {
    font-size: var(--sp-font-size-sm);
    color: var(--sp-text-secondary);
}

.sp-preview-value {
    font-weight: var(--sp-font-bold);
    font-size: var(--sp-font-size-base);
}

.sp-preview-new {
    color: #E11D48;
}

.sp-share-preview-arrow {
    text-align: center;
    font-size: 18px;
    color: var(--sp-text-secondary);
    padding: 2px 0;
}

.sp-share-preview-rank {
    margin-top: var(--sp-space-sm);
    padding-top: var(--sp-space-sm);
    border-top: 1px solid var(--sp-border);
}

.sp-rank-change {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--sp-space-md);
    font-size: 24px;
    font-weight: var(--sp-font-bold);
    padding: var(--sp-space-sm) 0;
}

.sp-rank-arrow {
    color: var(--sp-text-secondary);
    font-size: 18px;
}

.sp-rank-down {
    color: var(--sp-error);
}

.sp-rank-same {
    color: var(--sp-success);
}

.sp-rank-warning {
    text-align: center;
    font-size: var(--sp-font-size-sm);
    padding: var(--sp-space-sm) var(--sp-space-md);
    border-radius: var(--sp-radius-md);
    margin-top: var(--sp-space-sm);
}

.sp-rank-warning.warning {
    background: #FEF3C7;
    color: #B45309;
}

.sp-rank-warning.safe {
    background: #D1FAE5;
    color: #065F46;
}

/* Submit Button */
.sp-share-submit-btn {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: var(--sp-radius-lg);
    background: linear-gradient(135deg, #F472B6 0%, #EC4899 100%);
    color: white;
    font-family: 'Cairo', sans-serif;
    font-size: var(--sp-font-size-base);
    font-weight: var(--sp-font-bold);
    cursor: pointer;
    transition: all 0.2s;
}

.sp-share-submit-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.sp-share-submit-btn:not(:disabled):active {
    transform: scale(0.98);
}

.sp-share-cancel-btn {
    width: 100%;
    padding: 14px;
    border: 2px solid var(--sp-border);
    border-radius: var(--sp-radius-lg);
    background: var(--sp-bg-card);
    color: var(--sp-text-secondary);
    font-family: 'Cairo', sans-serif;
    font-size: var(--sp-font-size-base);
    font-weight: var(--sp-font-semibold);
    cursor: pointer;
    margin-top: var(--sp-space-sm);
}

/* Modal */
.sp-share-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.sp-share-modal-overlay.active {
    opacity: 1;
    visibility: visible;
}

.sp-share-modal {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: var(--sp-bg-card);
    border-radius: 20px 20px 0 0;
    padding: var(--sp-space-lg) var(--sp-space-lg) calc(var(--sp-space-lg) + env(safe-area-inset-bottom));
    z-index: 1001;
    transform: translateY(100%);
    transition: transform 0.3s ease;
    max-height: 80vh;
    overflow-y: auto;
}

.sp-share-modal-overlay.active .sp-share-modal {
    transform: translateY(0);
}

.sp-share-modal-handle {
    width: 40px;
    height: 4px;
    background: var(--sp-gray-300, #D1D5DB);
    border-radius: 2px;
    margin: 0 auto var(--sp-space-lg);
}

/* Confirm Details */
.sp-confirm-details {
    background: var(--sp-gray-50);
    border-radius: var(--sp-radius-md);
    padding: var(--sp-space-md);
    margin-bottom: var(--sp-space-md);
}

.sp-confirm-row {
    display: flex;
    justify-content: space-between;
    padding: var(--sp-space-xs) 0;
}

.sp-confirm-label {
    color: var(--sp-text-secondary);
}

.sp-confirm-value {
    font-weight: var(--sp-font-semibold);
}

.sp-confirm-amount {
    color: #E11D48;
    font-size: var(--sp-font-size-lg);
}

.sp-confirm-rank-warning {
    background: #FEF3C7;
    color: #92400E;
    padding: var(--sp-space-md);
    border-radius: var(--sp-radius-md);
    text-align: center;
    font-size: var(--sp-font-size-sm);
    margin-bottom: var(--sp-space-md);
}

.sp-confirm-buttons {
    margin-top: var(--sp-space-md);
}

/* Success Modal */
.sp-share-success-icon {
    text-align: center;
    font-size: 64px;
    animation: sp-bounce-in 0.5s ease;
}

@keyframes sp-bounce-in {
    0% { transform: scale(0); opacity: 0; }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); opacity: 1; }
}

.sp-success-stats {
    display: flex;
    gap: var(--sp-space-md);
    margin-top: var(--sp-space-md);
}

.sp-success-stat {
    flex: 1;
    text-align: center;
    background: var(--sp-gray-50);
    border-radius: var(--sp-radius-md);
    padding: var(--sp-space-md);
}

.sp-success-stat-label {
    display: block;
    font-size: var(--sp-font-size-xs);
    color: var(--sp-text-secondary);
    margin-bottom: 4px;
}

.sp-success-stat-value {
    display: block;
    font-size: var(--sp-font-size-lg);
    font-weight: var(--sp-font-bold);
    color: var(--sp-text-primary);
}

/* History Items */
.sp-share-history-list {
    padding: 0 var(--sp-space-lg);
}

.sp-share-history-item {
    display: flex;
    align-items: center;
    gap: var(--sp-space-sm);
    padding: var(--sp-space-md);
    background: var(--sp-bg-card);
    border-radius: var(--sp-radius-lg);
    margin-bottom: var(--sp-space-sm);
    box-shadow: var(--sp-shadow-sm);
}

.sp-share-direction-icon {
    width: 32px;
    height: 32px;
    min-width: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: var(--sp-font-bold);
}

.sp-share-direction-icon.sent {
    background: rgba(225, 29, 72, 0.1);
    color: #E11D48;
}

.sp-share-direction-icon.received {
    background: rgba(124, 58, 237, 0.1);
    color: #7C3AED;
}

.sp-share-history-avatar {
    width: 36px;
    height: 36px;
    min-width: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--sp-primary) 0%, var(--sp-primary-dark, #B8912A) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: var(--sp-font-bold);
    font-size: var(--sp-font-size-sm);
}

.sp-share-history-info {
    flex: 1;
    min-width: 0;
}

.sp-share-history-name {
    font-weight: var(--sp-font-semibold);
    font-size: var(--sp-font-size-sm);
    display: flex;
    align-items: center;
    gap: 4px;
}

.sp-share-history-gender {
    font-size: 11px;
}

.sp-share-history-message {
    font-size: var(--sp-font-size-xs);
    color: var(--sp-text-secondary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-style: italic;
}

.sp-share-history-date {
    font-size: 11px;
    color: var(--sp-text-muted, #9CA3AF);
}

.sp-share-history-points {
    font-weight: var(--sp-font-bold);
    font-size: var(--sp-font-size-lg);
    white-space: nowrap;
}

.sp-share-history-points.sent {
    color: #E11D48;
}

.sp-share-history-points.received {
    color: #7C3AED;
}

/* Loading & Empty */
.sp-share-loading {
    text-align: center;
    padding: var(--sp-space-lg);
    color: var(--sp-text-secondary);
    font-size: var(--sp-font-size-sm);
}

.sp-share-no-results {
    text-align: center;
    padding: var(--sp-space-md);
    color: var(--sp-text-secondary);
    font-size: var(--sp-font-size-sm);
}

/* Tabs */
.sp-share-tabs {
    margin: 0 var(--sp-space-lg) var(--sp-space-md);
}

@media (max-width: 400px) {
    .sp-share-history-item {
        gap: var(--sp-space-xs);
        padding: var(--sp-space-sm);
    }

    .sp-share-direction-icon {
        width: 28px;
        height: 28px;
        min-width: 28px;
        font-size: 12px;
    }

    .sp-share-history-avatar {
        width: 32px;
        height: 32px;
        min-width: 32px;
    }
}
</style>

<script>
(function() {
    var selectedRecipient = null;
    var previewTimeout = null;
    var isSubmitting = false;
    var userBalance = <?php echo (int) $balance; ?>;

    var searchInput = document.getElementById('sp-share-recipient-search');
    var searchWrapper = document.getElementById('sp-search-wrapper');
    var searchResults = document.getElementById('sp-share-search-results');
    var selectedDisplay = document.getElementById('sp-share-selected-recipient');
    var amountInput = document.getElementById('sp-share-amount');
    var previewSection = document.getElementById('sp-share-preview');
    var submitBtn = document.getElementById('sp-share-submit');
    var messageInput = document.getElementById('sp-share-message');

    // 1. Member Search (debounced)
    var searchTimeout = null;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        var query = this.value.trim();
        if (query.length < 2) {
            searchResults.innerHTML = '';
            return;
        }
        searchResults.innerHTML = '<div class="sp-share-loading"><?php _e('جاري البحث...', 'saint-porphyrius'); ?></div>';
        searchTimeout = setTimeout(function() {
            searchMembers(query);
        }, 300);
    });

    function searchMembers(query) {
        var formData = new FormData();
        formData.append('action', 'sp_search_members_for_sharing');
        formData.append('nonce', spApp.nonce);
        formData.append('search', query);

        fetch(spApp.ajaxUrl, { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(response) {
            if (response.success && response.data.length > 0) {
                renderSearchResults(response.data);
            } else {
                searchResults.innerHTML = '<div class="sp-share-no-results"><?php _e('لا يوجد نتائج', 'saint-porphyrius'); ?></div>';
            }
        })
        .catch(function() {
            searchResults.innerHTML = '<div class="sp-share-no-results"><?php _e('حدث خطأ', 'saint-porphyrius'); ?></div>';
        });
    }

    function renderSearchResults(members) {
        var html = '';
        members.forEach(function(member) {
            html += '<div class="sp-share-search-result-item" data-id="' + member.id +
                    '" data-name="' + escapeHtml(member.name) + '" data-initial="' + escapeHtml(member.initial) +
                    '" data-gender="' + member.gender + '">';
            html += '<div class="sp-share-result-avatar">' + escapeHtml(member.initial) + '</div>';
            html += '<div class="sp-share-result-info">';
            html += '<div class="sp-share-result-name">' + escapeHtml(member.name) + ' ' +
                    (member.gender === 'female' ? '👩' : '👨') + '</div>';
            html += '<div class="sp-share-result-points">' + member.points + ' <?php _e('نقطة', 'saint-porphyrius'); ?></div>';
            html += '</div></div>';
        });
        searchResults.innerHTML = html;

        document.querySelectorAll('.sp-share-search-result-item').forEach(function(item) {
            item.addEventListener('click', function() {
                selectRecipient({
                    id: this.dataset.id,
                    name: this.dataset.name,
                    initial: this.dataset.initial,
                    gender: this.dataset.gender
                });
            });
        });
    }

    function selectRecipient(member) {
        selectedRecipient = member;
        searchWrapper.style.display = 'none';
        searchResults.innerHTML = '';
        selectedDisplay.style.display = 'flex';
        selectedDisplay.innerHTML =
            '<div class="sp-share-selected-avatar">' + escapeHtml(member.initial) + '</div>' +
            '<div class="sp-share-selected-info">' + escapeHtml(member.name) + ' ' +
            (member.gender === 'female' ? '👩' : '👨') + '</div>' +
            '<button type="button" class="sp-share-remove-btn" id="sp-remove-recipient">✕</button>';

        document.getElementById('sp-remove-recipient').addEventListener('click', removeRecipient);
        updateFormState();
        fetchPreview();
    }

    function removeRecipient() {
        selectedRecipient = null;
        searchWrapper.style.display = '';
        searchInput.value = '';
        selectedDisplay.style.display = 'none';
        previewSection.style.display = 'none';
        updateFormState();
    }

    // 2. Quick Amount Buttons
    document.querySelectorAll('.sp-quick-amount').forEach(function(btn) {
        btn.addEventListener('click', function() {
            amountInput.value = this.dataset.amount;
            document.querySelectorAll('.sp-quick-amount').forEach(function(b) { b.classList.remove('selected'); });
            this.classList.add('selected');
            updateFormState();
            fetchPreview();
        });
    });

    // 3. Amount Input -> Preview
    amountInput.addEventListener('input', function() {
        document.querySelectorAll('.sp-quick-amount').forEach(function(b) { b.classList.remove('selected'); });
        updateFormState();
        clearTimeout(previewTimeout);
        previewTimeout = setTimeout(fetchPreview, 500);
    });

    // 4. Preview
    function fetchPreview() {
        var amount = parseInt(amountInput.value) || 0;
        if (!selectedRecipient || amount < 1) {
            previewSection.style.display = 'none';
            return;
        }

        var formData = new FormData();
        formData.append('action', 'sp_preview_share_points');
        formData.append('nonce', spApp.nonce);
        formData.append('recipient_id', selectedRecipient.id);
        formData.append('points', amount);

        fetch(spApp.ajaxUrl, { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(response) {
            if (response.success) {
                renderPreview(response.data);
            }
        });
    }

    function renderPreview(data) {
        document.getElementById('sp-preview-current-balance').textContent = data.current_balance;
        document.getElementById('sp-preview-new-balance').textContent = data.new_balance;
        document.getElementById('sp-preview-rank-before').textContent = '#' + data.current_rank;

        // Show fee info if applicable
        var feeSection = document.getElementById('sp-preview-fee-section');
        if (data.fee && data.fee > 0) {
            document.getElementById('sp-preview-fee').textContent = data.fee + ' <?php _e('نقطة', 'saint-porphyrius'); ?>';
            document.getElementById('sp-preview-total-cost').textContent = data.total_cost + ' <?php _e('نقطة', 'saint-porphyrius'); ?>';
            feeSection.style.display = 'block';
        } else {
            feeSection.style.display = 'none';
        }

        var rankAfter = document.getElementById('sp-preview-rank-after');
        rankAfter.textContent = '#' + data.projected_rank;
        rankAfter.className = data.projected_rank > data.current_rank ? 'sp-rank-down' : 'sp-rank-same';

        var warning = document.getElementById('sp-rank-warning-text');
        if (data.projected_rank > data.current_rank) {
            var diff = data.projected_rank - data.current_rank;
            warning.textContent = '<?php _e('ترتيبك سينخفض', 'saint-porphyrius'); ?> ' + diff + ' ' + (diff === 1 ? '<?php _e('مركز', 'saint-porphyrius'); ?>' : '<?php _e('مراكز', 'saint-porphyrius'); ?>');
            warning.className = 'sp-rank-warning warning';
            warning.style.display = 'block';
        } else {
            warning.textContent = '<?php _e('ترتيبك لن يتغير', 'saint-porphyrius'); ?>';
            warning.className = 'sp-rank-warning safe';
            warning.style.display = 'block';
        }

        previewSection.style.display = 'block';

        if (!data.is_valid) {
            submitBtn.disabled = true;
        }
    }

    // 5. Form Validation
    function updateFormState() {
        var amount = parseInt(amountInput.value) || 0;
        var valid = selectedRecipient && amount >= 1 && amount <= userBalance;
        submitBtn.disabled = !valid;
    }

    // 6. Submit -> Confirmation Modal
    submitBtn.addEventListener('click', function() {
        if (isSubmitting || this.disabled) return;
        showConfirmModal();
    });

    function showConfirmModal() {
        var amount = parseInt(amountInput.value);
        document.getElementById('sp-confirm-recipient-name').textContent = selectedRecipient.name;
        document.getElementById('sp-confirm-amount').textContent = amount + ' <?php _e('نقطة', 'saint-porphyrius'); ?>';
        document.getElementById('sp-confirm-message').textContent = messageInput.value || '<?php _e('(بدون رسالة)', 'saint-porphyrius'); ?>';

        // Show fee in confirmation if present
        var feeSection = document.getElementById('sp-preview-fee-section');
        var confirmFeeRow = document.getElementById('sp-confirm-fee-row');
        var confirmTotalRow = document.getElementById('sp-confirm-total-row');
        if (feeSection && feeSection.style.display !== 'none') {
            var feeText = document.getElementById('sp-preview-fee').textContent;
            var totalText = document.getElementById('sp-preview-total-cost').textContent;
            document.getElementById('sp-confirm-fee').textContent = feeText;
            document.getElementById('sp-confirm-total').textContent = totalText;
            confirmFeeRow.style.display = '';
            confirmTotalRow.style.display = '';
        } else {
            confirmFeeRow.style.display = 'none';
            confirmTotalRow.style.display = 'none';
        }

        var rankBefore = document.getElementById('sp-preview-rank-before').textContent;
        var rankAfter = document.getElementById('sp-preview-rank-after').textContent;
        var rankChangeEl = document.getElementById('sp-confirm-rank-change');
        if (rankBefore !== rankAfter) {
            rankChangeEl.innerHTML = '⚠️ <?php _e('ترتيبك سيتغير من', 'saint-porphyrius'); ?> <strong>' + rankBefore + '</strong> <?php _e('إلى', 'saint-porphyrius'); ?> <strong>' + rankAfter + '</strong>';
            rankChangeEl.style.display = 'block';
        } else {
            rankChangeEl.style.display = 'none';
        }

        document.getElementById('sp-confirm-overlay').classList.add('active');
    }

    // 7. Confirm -> Execute
    document.getElementById('sp-confirm-btn').addEventListener('click', function() {
        if (isSubmitting) return;
        isSubmitting = true;
        var btn = this;
        btn.disabled = true;
        btn.textContent = '<?php _e('جاري الإرسال...', 'saint-porphyrius'); ?>';

        var formData = new FormData();
        formData.append('action', 'sp_share_points');
        formData.append('nonce', spApp.nonce);
        formData.append('recipient_id', selectedRecipient.id);
        formData.append('points', amountInput.value);
        formData.append('message', messageInput.value);

        fetch(spApp.ajaxUrl, { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(response) {
            document.getElementById('sp-confirm-overlay').classList.remove('active');
            isSubmitting = false;
            btn.disabled = false;
            btn.textContent = '<?php _e('تأكيد المشاركة', 'saint-porphyrius'); ?>';

            if (response.success) {
                showSuccessModal(response.data);
            } else {
                alert(response.data.message || '<?php _e('حدث خطأ', 'saint-porphyrius'); ?>');
            }
        })
        .catch(function() {
            document.getElementById('sp-confirm-overlay').classList.remove('active');
            isSubmitting = false;
            btn.disabled = false;
            btn.textContent = '<?php _e('تأكيد المشاركة', 'saint-porphyrius'); ?>';
            alert('<?php _e('حدث خطأ في الاتصال', 'saint-porphyrius'); ?>');
        });
    });

    // Cancel
    document.getElementById('sp-cancel-btn').addEventListener('click', function() {
        document.getElementById('sp-confirm-overlay').classList.remove('active');
    });

    // Close modal on overlay click
    document.querySelectorAll('.sp-share-modal-overlay').forEach(function(overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    });

    // 8. Success Modal
    function showSuccessModal(data) {
        document.getElementById('sp-success-amount').textContent = data.points_shared;
        document.getElementById('sp-success-recipient').textContent = data.recipient_name;
        document.getElementById('sp-success-new-balance').textContent = data.new_balance;

        // Show fee in success if applicable
        if (data.fee && data.fee > 0) {
            var successDetails = document.querySelector('.sp-success-details p');
            if (successDetails) {
                successDetails.innerHTML = '<?php _e('تم إرسال', 'saint-porphyrius'); ?> <strong>' + data.points_shared + '</strong> <?php _e('نقطة إلى', 'saint-porphyrius'); ?> <strong>' + escapeHtml(data.recipient_name) + '</strong>' +
                    '<br><span style="font-size: 13px; color: var(--sp-text-secondary);"><?php _e('الرسوم:', 'saint-porphyrius'); ?> ' + data.fee + ' <?php _e('نقطة | إجمالي الخصم:', 'saint-porphyrius'); ?> ' + data.total_deducted + ' <?php _e('نقطة', 'saint-porphyrius'); ?></span>';
            }
        }

        var rankSection = document.getElementById('sp-success-rank-section');
        var rankChange = document.getElementById('sp-success-rank-change');
        if (data.rank_before !== data.rank_after) {
            rankChange.textContent = '#' + data.rank_before + ' → #' + data.rank_after;
            rankSection.style.display = '';
        } else {
            rankChange.textContent = '#' + data.rank_after;
            rankSection.style.display = '';
        }

        document.getElementById('sp-success-overlay').classList.add('active');
    }

    document.getElementById('sp-success-ok-btn').addEventListener('click', function() {
        window.location.reload();
    });

    // 9. History Tabs
    document.querySelectorAll('.sp-share-tabs .sp-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.sp-share-tabs .sp-tab').forEach(function(t) { t.classList.remove('active'); });
            document.querySelectorAll('.sp-share-tab-content').forEach(function(c) { c.style.display = 'none'; });
            this.classList.add('active');
            var tabContent = document.getElementById('share-tab-' + this.dataset.tab);
            if (tabContent) {
                tabContent.style.display = 'block';
            }
        });
    });

    // Helper
    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }
})();
</script>
