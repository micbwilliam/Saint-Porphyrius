<?php
/**
 * Saint Porphyrius - Admin Forbidden Management (Mobile)
 * Manage forbidden users, cards, and discipline settings
 */

if (!defined('ABSPATH')) {
    exit;
}

$forbidden_handler = SP_Forbidden::get_instance();
$settings = $forbidden_handler->get_settings();
$counts = $forbidden_handler->count_by_status();

$message = '';
$message_type = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sp_forbidden_action'])) {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'sp_forbidden_action')) {
        $message = __('خطأ في التحقق', 'saint-porphyrius');
        $message_type = 'error';
    } else {
        $action = sanitize_text_field($_POST['sp_forbidden_action']);
        
        if ($action === 'update_settings') {
            $new_settings = array(
                'forbidden_events_count' => absint($_POST['forbidden_events_count']),
                'yellow_card_threshold' => absint($_POST['yellow_card_threshold']),
                'red_card_threshold' => absint($_POST['red_card_threshold']),
            );
            $forbidden_handler->update_settings($new_settings);
            $settings = $new_settings;
            $message = __('تم حفظ الإعدادات بنجاح', 'saint-porphyrius');
            $message_type = 'success';
        } elseif ($action === 'unblock' && !empty($_POST['user_id'])) {
            $forbidden_handler->unblock_user(absint($_POST['user_id']), get_current_user_id());
            $message = __('تم إلغاء حظر المستخدم', 'saint-porphyrius');
            $message_type = 'success';
            $counts = $forbidden_handler->count_by_status(); // Refresh counts
        } elseif ($action === 'reset' && !empty($_POST['user_id'])) {
            $forbidden_handler->reset_user_status(absint($_POST['user_id']), get_current_user_id());
            $message = __('تم إعادة تعيين حالة المستخدم', 'saint-porphyrius');
            $message_type = 'success';
            $counts = $forbidden_handler->count_by_status(); // Refresh counts
        } elseif ($action === 'remove_forbidden' && !empty($_POST['user_id'])) {
            $forbidden_handler->remove_forbidden_penalty(absint($_POST['user_id']), get_current_user_id());
            $message = __('تم إزالة عقوبة الحرمان', 'saint-porphyrius');
            $message_type = 'success';
        }
    }
}

// Get users with status
$users_with_status = $forbidden_handler->get_users_with_status();
$blocked_users = $forbidden_handler->get_blocked_users();

// Current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';
?>

<!-- Admin Header -->
<div class="sp-unified-header sp-admin-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/admin'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('نظام المحروم', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-spacer"></div>
    </div>
</div>

<!-- Main Content -->
<main class="sp-page-content sp-admin-content">
    <?php if ($message): ?>
        <div class="sp-alert sp-alert-<?php echo esc_attr($message_type); ?>">
            <?php echo esc_html($message); ?>
        </div>
    <?php endif; ?>

    <!-- Tabs Navigation -->
    <div class="sp-tabs-nav">
        <a href="?tab=overview" class="sp-tab-link <?php echo $current_tab === 'overview' ? 'active' : ''; ?>">
            📊 <?php _e('نظرة عامة', 'saint-porphyrius'); ?>
        </a>
        <a href="?tab=users" class="sp-tab-link <?php echo $current_tab === 'users' ? 'active' : ''; ?>">
            👥 <?php _e('المستخدمين', 'saint-porphyrius'); ?>
        </a>
        <a href="?tab=blocked" class="sp-tab-link <?php echo $current_tab === 'blocked' ? 'active' : ''; ?>">
            🔴 <?php _e('المحظورين', 'saint-porphyrius'); ?>
            <?php if ($counts['red_card'] > 0): ?>
                <span class="sp-tab-badge"><?php echo esc_html($counts['red_card']); ?></span>
            <?php endif; ?>
        </a>
        <a href="?tab=settings" class="sp-tab-link <?php echo $current_tab === 'settings' ? 'active' : ''; ?>">
            ⚙️ <?php _e('الإعدادات', 'saint-porphyrius'); ?>
        </a>
    </div>

    <?php if ($current_tab === 'overview'): ?>
        <!-- Overview Tab -->
        <div class="sp-forbidden-stats-grid">
            <div class="sp-forbidden-stat-card">
                <div class="sp-stat-icon forbidden">⛔</div>
                <div class="sp-stat-value"><?php echo esc_html($counts['forbidden']); ?></div>
                <div class="sp-stat-label"><?php _e('محروم حالياً', 'saint-porphyrius'); ?></div>
            </div>
            <div class="sp-forbidden-stat-card">
                <div class="sp-stat-icon yellow">🟡</div>
                <div class="sp-stat-value"><?php echo esc_html($counts['yellow_card']); ?></div>
                <div class="sp-stat-label"><?php _e('كارت أصفر', 'saint-porphyrius'); ?></div>
            </div>
            <div class="sp-forbidden-stat-card">
                <div class="sp-stat-icon red">🔴</div>
                <div class="sp-stat-value"><?php echo esc_html($counts['red_card']); ?></div>
                <div class="sp-stat-label"><?php _e('كارت أحمر (محظور)', 'saint-porphyrius'); ?></div>
            </div>
        </div>

        <div class="sp-admin-card">
            <h3 class="sp-card-title"><?php _e('كيف يعمل النظام؟', 'saint-porphyrius'); ?></h3>
            <div class="sp-info-list">
                <div class="sp-info-item">
                    <span class="sp-info-icon">1️⃣</span>
                    <span><?php printf(__('الغياب بدون عذر من فعالية محروم = حرمان من %d فعاليات قادمة', 'saint-porphyrius'), $settings['forbidden_events_count']); ?></span>
                </div>
                <div class="sp-info-item">
                    <span class="sp-info-icon">🟡</span>
                    <span><?php printf(__('بعد %d غيابات متتالية = كارت أصفر (تحذير)', 'saint-porphyrius'), $settings['yellow_card_threshold']); ?></span>
                </div>
                <div class="sp-info-item">
                    <span class="sp-info-icon">🔴</span>
                    <span><?php printf(__('بعد %d غيابات متتالية = كارت أحمر (حظر من التطبيق)', 'saint-porphyrius'), $settings['red_card_threshold']); ?></span>
                </div>
                <div class="sp-info-item">
                    <span class="sp-info-icon">✓</span>
                    <span><?php _e('الحضور يعيد تصفير عداد الغيابات المتتالية', 'saint-porphyrius'); ?></span>
                </div>
                <div class="sp-info-item">
                    <span class="sp-info-icon">⛔</span>
                    <span><?php _e('حالة "محروم" لا تُحسب كغياب في نظام الكروت', 'saint-porphyrius'); ?></span>
                </div>
            </div>
        </div>

    <?php elseif ($current_tab === 'users'): ?>
        <!-- Users Tab -->
        <?php if (empty($users_with_status)): ?>
            <div class="sp-empty-state">
                <div class="sp-empty-icon">✓</div>
                <h3><?php _e('لا توجد حالات نشطة', 'saint-porphyrius'); ?></h3>
                <p><?php _e('جميع الأعضاء بدون حرمان أو كروت', 'saint-porphyrius'); ?></p>
            </div>
        <?php else: ?>
            <div class="sp-forbidden-users-list">
                <?php foreach ($users_with_status as $user_status): 
                    $user_obj = get_user_by('id', $user_status->user_id);
                    $fn = $user_obj ? $user_obj->first_name : $user_status->display_name;
                    $mn = $user_obj ? get_user_meta($user_obj->ID, 'sp_middle_name', true) : '';
                    $user_display_name = $user_obj ? (trim($fn . ' ' . $mn) ?: $user_status->display_name) : $user_status->display_name;
                ?>
                    <div class="sp-forbidden-user-card <?php echo $user_status->card_status; ?>">
                        <div class="sp-user-header">
                            <div class="sp-user-avatar <?php echo $user_status->card_status !== 'none' ? 'has-card-' . $user_status->card_status : ''; ?>">
                                <?php echo esc_html(mb_substr($user_display_name, 0, 1)); ?>
                            </div>
                            <div class="sp-user-info">
                                <h4><?php echo esc_html($user_display_name); ?></h4>
                                <span class="sp-user-email"><?php echo esc_html($user_status->user_email); ?></span>
                            </div>
                        </div>
                        
                        <div class="sp-user-status-grid">
                            <?php if ($user_status->forbidden_remaining > 0): ?>
                            <div class="sp-status-item forbidden">
                                <span class="sp-status-label"><?php _e('محروم من', 'saint-porphyrius'); ?></span>
                                <span class="sp-status-value"><?php echo esc_html($user_status->forbidden_remaining); ?> <?php _e('فعاليات', 'saint-porphyrius'); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="sp-status-item absences">
                                <span class="sp-status-label"><?php _e('الغيابات المتتالية', 'saint-porphyrius'); ?></span>
                                <span class="sp-status-value"><?php echo esc_html($user_status->consecutive_absences); ?> / <?php echo esc_html($settings['red_card_threshold']); ?></span>
                            </div>
                            
                            <div class="sp-status-item card">
                                <span class="sp-status-label"><?php _e('حالة الكارت', 'saint-porphyrius'); ?></span>
                                <span class="sp-status-value">
                                    <?php 
                                    switch($user_status->card_status) {
                                        case 'yellow':
                                            echo '🟡 ' . __('أصفر', 'saint-porphyrius');
                                            break;
                                        case 'red':
                                            echo '🔴 ' . __('أحمر', 'saint-porphyrius');
                                            break;
                                        default:
                                            echo '✓ ' . __('لا يوجد', 'saint-porphyrius');
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="sp-absences-progress">
                            <div class="sp-progress-bar">
                                <div class="sp-progress-fill <?php echo $user_status->consecutive_absences >= $settings['yellow_card_threshold'] ? 'warning' : ''; ?>" 
                                     style="width: <?php echo min(100, ($user_status->consecutive_absences / $settings['red_card_threshold']) * 100); ?>%;">
                                </div>
                                <div class="sp-progress-marker yellow" style="left: <?php echo ($settings['yellow_card_threshold'] / $settings['red_card_threshold']) * 100; ?>%;"></div>
                            </div>
                            <div class="sp-progress-labels">
                                <span>0</span>
                                <span class="yellow-marker"><?php echo esc_html($settings['yellow_card_threshold']); ?> 🟡</span>
                                <span><?php echo esc_html($settings['red_card_threshold']); ?> 🔴</span>
                            </div>
                        </div>
                        
                        <div class="sp-user-actions">
                            <?php if ($user_status->forbidden_remaining > 0): ?>
                            <form method="post" style="display:inline;">
                                <?php wp_nonce_field('sp_forbidden_action'); ?>
                                <input type="hidden" name="sp_forbidden_action" value="remove_forbidden">
                                <input type="hidden" name="user_id" value="<?php echo esc_attr($user_status->user_id); ?>">
                                <button type="submit" class="sp-btn sp-btn-sm sp-btn-outline" onclick="return confirm('<?php _e('إزالة عقوبة الحرمان؟', 'saint-porphyrius'); ?>');">
                                    <?php _e('إزالة الحرمان', 'saint-porphyrius'); ?>
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <form method="post" style="display:inline;">
                                <?php wp_nonce_field('sp_forbidden_action'); ?>
                                <input type="hidden" name="sp_forbidden_action" value="reset">
                                <input type="hidden" name="user_id" value="<?php echo esc_attr($user_status->user_id); ?>">
                                <button type="submit" class="sp-btn sp-btn-sm sp-btn-primary" onclick="return confirm('<?php _e('إعادة تعيين جميع الحالات؟', 'saint-porphyrius'); ?>');">
                                    <?php _e('إعادة تعيين الكل', 'saint-porphyrius'); ?>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php elseif ($current_tab === 'blocked'): ?>
        <!-- Blocked Users Tab -->
        <?php if (empty($blocked_users)): ?>
            <div class="sp-empty-state">
                <div class="sp-empty-icon">✓</div>
                <h3><?php _e('لا يوجد مستخدمين محظورين', 'saint-porphyrius'); ?></h3>
                <p><?php _e('جميع الأعضاء يستطيعون الوصول للتطبيق', 'saint-porphyrius'); ?></p>
            </div>
        <?php else: ?>
            <div class="sp-alert sp-alert-danger">
                <?php _e('هؤلاء المستخدمين لا يستطيعون الوصول للتطبيق بسبب الكارت الأحمر', 'saint-porphyrius'); ?>
            </div>
            
            <div class="sp-blocked-users-list">
                <?php foreach ($blocked_users as $user): 
                    $u_obj = get_user_by('id', $user->user_id);
                    $u_fn = $u_obj ? $u_obj->first_name : $user->display_name;
                    $u_mn = $u_obj ? get_user_meta($u_obj->ID, 'sp_middle_name', true) : '';
                    $u_display = $u_obj ? (trim($u_fn . ' ' . $u_mn) ?: $user->display_name) : $user->display_name;
                ?>
                    <div class="sp-blocked-user-card">
                        <div class="sp-user-header">
                            <div class="sp-user-avatar blocked">
                                <?php echo esc_html(mb_substr($u_display, 0, 1)); ?>
                                <span class="sp-blocked-icon">🔴</span>
                            </div>
                            <div class="sp-user-info">
                                <h4><?php echo esc_html($u_display); ?></h4>
                                <span class="sp-blocked-date">
                                    <?php printf(__('محظور منذ: %s', 'saint-porphyrius'), date_i18n('j F Y', strtotime($user->blocked_at))); ?>
                                </span>
                            </div>
                        </div>
                        
                        <form method="post" class="sp-unblock-form">
                            <?php wp_nonce_field('sp_forbidden_action'); ?>
                            <input type="hidden" name="sp_forbidden_action" value="unblock">
                            <input type="hidden" name="user_id" value="<?php echo esc_attr($user->user_id); ?>">
                            <button type="submit" class="sp-btn sp-btn-success sp-btn-block" onclick="return confirm('<?php _e('إلغاء حظر هذا المستخدم؟', 'saint-porphyrius'); ?>');">
                                <?php _e('إلغاء الحظر وإعادة تفعيل', 'saint-porphyrius'); ?>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php elseif ($current_tab === 'settings'): ?>
        <!-- Settings Tab -->
        <form method="post" class="sp-admin-form">
            <?php wp_nonce_field('sp_forbidden_action'); ?>
            <input type="hidden" name="sp_forbidden_action" value="update_settings">
            
            <div class="sp-form-section">
                <h3 class="sp-form-section-title"><?php _e('إعدادات نظام المحروم', 'saint-porphyrius'); ?></h3>
                
                <div class="sp-form-group">
                    <label class="sp-form-label">
                        <?php _e('عدد فعاليات الحرمان', 'saint-porphyrius'); ?>
                        <span class="sp-form-hint"><?php _e('عدد الفعاليات التي يُحرم منها العضو بعد الغياب', 'saint-porphyrius'); ?></span>
                    </label>
                    <input type="number" name="forbidden_events_count" class="sp-form-input" 
                           value="<?php echo esc_attr($settings['forbidden_events_count']); ?>" min="1" max="10">
                </div>
                
                <div class="sp-form-group">
                    <label class="sp-form-label">
                        🟡 <?php _e('عتبة الكارت الأصفر', 'saint-porphyrius'); ?>
                        <span class="sp-form-hint"><?php _e('عدد الغيابات المتتالية للحصول على كارت أصفر', 'saint-porphyrius'); ?></span>
                    </label>
                    <input type="number" name="yellow_card_threshold" class="sp-form-input" 
                           value="<?php echo esc_attr($settings['yellow_card_threshold']); ?>" min="1" max="10">
                </div>
                
                <div class="sp-form-group">
                    <label class="sp-form-label">
                        🔴 <?php _e('عتبة الكارت الأحمر', 'saint-porphyrius'); ?>
                        <span class="sp-form-hint"><?php _e('عدد الغيابات المتتالية للحصول على كارت أحمر (حظر)', 'saint-porphyrius'); ?></span>
                    </label>
                    <input type="number" name="red_card_threshold" class="sp-form-input" 
                           value="<?php echo esc_attr($settings['red_card_threshold']); ?>" min="1" max="20">
                </div>
            </div>
            
            <div class="sp-form-actions">
                <button type="submit" class="sp-btn sp-btn-primary sp-btn-block">
                    <?php _e('حفظ الإعدادات', 'saint-porphyrius'); ?>
                </button>
            </div>
        </form>
    <?php endif; ?>
</main>
