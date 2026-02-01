<?php
/**
 * Saint Porphyrius - Admin Points (Mobile)
 * Manage member points, adjustments, and history
 */

if (!defined('ABSPATH')) {
    exit;
}

$points_handler = SP_Points::get_instance();
$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sp_points_action'])) {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'sp_points_action')) {
        $message = __('خطأ في التحقق', 'saint-porphyrius');
        $message_type = 'error';
    } else {
        $action = sanitize_text_field($_POST['sp_points_action']);
        
        if ($action === 'adjust' && !empty($_POST['user_id']) && isset($_POST['points'])) {
            $result = $points_handler->adjust(
                absint($_POST['user_id']),
                intval($_POST['points']),
                sanitize_textarea_field($_POST['description'] ?? '')
            );
            
            if (is_wp_error($result)) {
                $message = $result->get_error_message();
                $message_type = 'error';
            } else {
                $message = __('تم تعديل النقاط بنجاح', 'saint-porphyrius');
                $message_type = 'success';
            }
        }
    }
}

// Get selected user for history view
$selected_user_id = isset($_GET['user_id']) ? absint($_GET['user_id']) : 0;
$selected_user = $selected_user_id ? get_user_by('id', $selected_user_id) : null;

// Get stats
$stats = $points_handler->get_summary_stats();
$leaderboard = $points_handler->get_leaderboard(10);

// Get all members for dropdown
$all_members = get_users(array('role' => 'sp_member', 'orderby' => 'display_name'));

// Get history for selected user
$user_history = array();
if ($selected_user) {
    $user_history = $points_handler->get_history($selected_user_id, array('limit' => 50));
}

$reason_types = SP_Points::get_reason_types();
?>

<!-- Admin Header -->
<div class="sp-unified-header sp-admin-header">
    <div class="sp-header-inner">
        <a href="<?php echo $selected_user ? home_url('/app/admin/points') : home_url('/app/admin'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title">
            <?php echo $selected_user ? __('سجل النقاط', 'saint-porphyrius') : __('إدارة النقاط', 'saint-porphyrius'); ?>
        </h1>
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

    <?php if ($selected_user): ?>
        <!-- User Points History -->
        <?php 
        $name_ar = get_user_meta($selected_user_id, 'sp_name_ar', true);
        $full_name = $name_ar ?: $selected_user->display_name;
        $balance = $points_handler->get_balance($selected_user_id);
        ?>
        
        <div class="sp-points-user-header">
            <div class="sp-points-user-avatar">
                <?php echo esc_html(mb_substr($full_name, 0, 1)); ?>
            </div>
            <div class="sp-points-user-info">
                <h3><?php echo esc_html($full_name); ?></h3>
                <span class="sp-points-user-email"><?php echo esc_html($selected_user->user_email); ?></span>
            </div>
            <div class="sp-points-user-balance">
                <span class="value"><?php echo esc_html($balance); ?></span>
                <span class="label"><?php _e('نقطة', 'saint-porphyrius'); ?></span>
            </div>
        </div>
        
        <!-- Quick Adjustment -->
        <div class="sp-admin-card">
            <h4><?php _e('تعديل سريع', 'saint-porphyrius'); ?></h4>
            <form method="post" class="sp-quick-adjust-form">
                <?php wp_nonce_field('sp_points_action'); ?>
                <input type="hidden" name="sp_points_action" value="adjust">
                <input type="hidden" name="user_id" value="<?php echo esc_attr($selected_user_id); ?>">
                
                <div class="sp-quick-adjust-row">
                    <input type="number" name="points" placeholder="<?php _e('النقاط (سالب للخصم)', 'saint-porphyrius'); ?>" required class="sp-form-input">
                    <button type="submit" class="sp-btn sp-btn-primary"><?php _e('تعديل', 'saint-porphyrius'); ?></button>
                </div>
                <input type="text" name="description" placeholder="<?php _e('السبب (اختياري)', 'saint-porphyrius'); ?>" class="sp-form-input">
            </form>
        </div>
        
        <!-- Points History -->
        <div class="sp-section">
            <div class="sp-section-header">
                <h3 class="sp-section-title"><?php _e('سجل النقاط', 'saint-porphyrius'); ?></h3>
            </div>
            
            <?php if (empty($user_history)): ?>
                <div class="sp-empty-state">
                    <div class="sp-empty-icon">📊</div>
                    <h3><?php _e('لا يوجد سجل', 'saint-porphyrius'); ?></h3>
                </div>
            <?php else: ?>
                <div class="sp-points-history-list">
                    <?php foreach ($user_history as $entry): 
                        $is_positive = $entry->points >= 0;
                        $entry_type = !empty($entry->type) ? $entry->type : ($is_positive ? 'reward' : 'penalty');
                        $type_info = isset($reason_types[$entry_type]) ? $reason_types[$entry_type] : null;
                        $type_label = $type_info ? $type_info['label_ar'] : ucfirst($entry_type ?: 'غير معروف');
                        $type_color = $type_info && isset($type_info['color']) ? $type_info['color'] : '#6B7280';
                    ?>
                        <div class="sp-points-history-item">
                            <div class="sp-points-history-icon <?php echo $is_positive ? 'positive' : 'negative'; ?>">
                                <?php echo $is_positive ? '+' : '-'; ?>
                            </div>
                            <div class="sp-points-history-content">
                                <span class="sp-points-history-type" style="background: <?php echo esc_attr($type_color); ?>15; color: <?php echo esc_attr($type_color); ?>;">
                                    <?php echo esc_html($type_label); ?>
                                </span>
                                <?php if (!empty($entry->reason)): ?>
                                    <span class="sp-points-history-reason"><?php echo esc_html($entry->reason); ?></span>
                                <?php endif; ?>
                                <span class="sp-points-history-date"><?php echo esc_html(date_i18n('j M Y - g:i a', strtotime($entry->created_at))); ?></span>
                            </div>
                            <div class="sp-points-history-value <?php echo $is_positive ? 'positive' : 'negative'; ?>">
                                <?php echo $is_positive ? '+' : ''; ?><?php echo esc_html($entry->points); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
    <?php else: ?>
        <!-- Points Overview -->
        
        <!-- Stats Cards -->
        <div class="sp-points-stats-grid">
            <div class="sp-points-stat-card">
                <span class="sp-points-stat-icon positive">+</span>
                <div class="sp-points-stat-info">
                    <span class="sp-points-stat-value"><?php echo esc_html($stats->total_awarded ?? 0); ?></span>
                    <span class="sp-points-stat-label"><?php _e('إجمالي المكافآت', 'saint-porphyrius'); ?></span>
                </div>
            </div>
            <div class="sp-points-stat-card">
                <span class="sp-points-stat-icon negative">-</span>
                <div class="sp-points-stat-info">
                    <span class="sp-points-stat-value"><?php echo esc_html(abs($stats->total_penalties ?? 0)); ?></span>
                    <span class="sp-points-stat-label"><?php _e('إجمالي الخصومات', 'saint-porphyrius'); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Manual Adjustment -->
        <div class="sp-admin-card">
            <h3><?php _e('تعديل النقاط', 'saint-porphyrius'); ?></h3>
            <form method="post" class="sp-admin-form">
                <?php wp_nonce_field('sp_points_action'); ?>
                <input type="hidden" name="sp_points_action" value="adjust">
                
                <div class="sp-form-group">
                    <label class="sp-form-label"><?php _e('اختر العضو', 'saint-porphyrius'); ?></label>
                    <select name="user_id" required class="sp-form-select">
                        <option value=""><?php _e('-- اختر عضو --', 'saint-porphyrius'); ?></option>
                        <?php foreach ($all_members as $member): 
                            $name_ar = get_user_meta($member->ID, 'sp_name_ar', true);
                            $display = $name_ar ?: $member->display_name;
                            $pts = $points_handler->get_balance($member->ID);
                        ?>
                            <option value="<?php echo esc_attr($member->ID); ?>">
                                <?php echo esc_html($display . ' (' . $pts . ' ' . __('نقطة', 'saint-porphyrius') . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="sp-form-group">
                    <label class="sp-form-label"><?php _e('النقاط (استخدم السالب للخصم)', 'saint-porphyrius'); ?></label>
                    <input type="number" name="points" required class="sp-form-input" placeholder="10 أو -5">
                </div>
                
                <div class="sp-form-group">
                    <label class="sp-form-label"><?php _e('السبب', 'saint-porphyrius'); ?></label>
                    <textarea name="description" class="sp-form-textarea" rows="2" placeholder="<?php _e('سبب التعديل...', 'saint-porphyrius'); ?>"></textarea>
                </div>
                
                <button type="submit" class="sp-btn sp-btn-primary sp-btn-block">
                    <?php _e('تعديل النقاط', 'saint-porphyrius'); ?>
                </button>
            </form>
        </div>
        
        <!-- Leaderboard -->
        <div class="sp-section">
            <div class="sp-section-header">
                <h3 class="sp-section-title"><?php _e('المتصدرين', 'saint-porphyrius'); ?> 🏆</h3>
            </div>
            
            <?php if (empty($leaderboard)): ?>
                <div class="sp-empty-state">
                    <div class="sp-empty-icon">🏆</div>
                    <h3><?php _e('لا توجد نقاط بعد', 'saint-porphyrius'); ?></h3>
                </div>
            <?php else: ?>
                <div class="sp-leaderboard-list">
                    <?php $medals = array('🥇', '🥈', '🥉', '4️⃣', '5️⃣', '6️⃣', '7️⃣', '8️⃣', '9️⃣', '🔟'); ?>
                    <?php foreach ($leaderboard as $index => $entry): ?>
                        <a href="<?php echo home_url('/app/admin/points?user_id=' . $entry->user_id); ?>" class="sp-leaderboard-item">
                            <span class="sp-leaderboard-rank"><?php echo $medals[$index] ?? ($index + 1); ?></span>
                            <div class="sp-leaderboard-user">
                                <span class="sp-leaderboard-name"><?php echo esc_html($entry->name_ar ?: $entry->display_name); ?></span>
                            </div>
                            <span class="sp-leaderboard-points"><?php echo esc_html($entry->total_points); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- All Members -->
        <div class="sp-section">
            <div class="sp-section-header">
                <h3 class="sp-section-title"><?php _e('جميع الأعضاء', 'saint-porphyrius'); ?></h3>
            </div>
            
            <div class="sp-members-points-list">
                <?php foreach ($all_members as $member): 
                    $name_ar = get_user_meta($member->ID, 'sp_name_ar', true);
                    $full_name = $name_ar ?: $member->display_name;
                    $balance = $points_handler->get_balance($member->ID);
                ?>
                    <a href="<?php echo home_url('/app/admin/points?user_id=' . $member->ID); ?>" class="sp-member-points-item">
                        <div class="sp-member-points-avatar">
                            <?php echo esc_html(mb_substr($full_name, 0, 1)); ?>
                        </div>
                        <span class="sp-member-points-name"><?php echo esc_html($full_name); ?></span>
                        <span class="sp-member-points-balance <?php echo $balance >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo esc_html($balance); ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</main>
