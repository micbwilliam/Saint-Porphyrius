<?php
/**
 * Saint Porphyrius - User Notifications Page (Inbox)
 * Shows all in-app notifications with read/unread state
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helper: Human-readable time ago
 */
if (!function_exists('sp_time_ago')) {
    function sp_time_ago($datetime) {
        $now  = current_time('timestamp');
        $time = strtotime($datetime);
        $diff = $now - $time;

        if ($diff < 60)      return 'الآن';
        if ($diff < 3600)    return sprintf('%d دقيقة', floor($diff / 60));
        if ($diff < 86400)   return sprintf('%d ساعة', floor($diff / 3600));
        if ($diff < 172800)  return 'أمس';
        if ($diff < 604800)  return sprintf('%d أيام', floor($diff / 86400));
        if ($diff < 2592000) return sprintf('%d أسبوع', floor($diff / 604800));
        return date_i18n('j M Y', $time);
    }
}


$user_id = get_current_user_id();
$notifications = SP_Notifications::get_instance();

// Check if viewing a specific notification
$view_id = isset($_GET['view']) ? absint($_GET['view']) : 0;
$single_notification = null;

if ($view_id) {
    $single_notification = $notifications->get_notification($view_id);
    if ($single_notification) {
        // Verify user can see this notification
        if ($single_notification->user_id != 0 && $single_notification->user_id != $user_id) {
            $single_notification = null;
        } else {
            // Mark as read
            $notifications->mark_as_read($view_id, $user_id);
        }
    }
}

// Get all notifications for this user
$all_notifications = $notifications->get_user_notifications($user_id, array('limit' => 50));
$read_broadcast_ids = $notifications->get_user_read_broadcast_ids($user_id);
$unread_count = $notifications->get_accurate_unread_count($user_id);

// Handle mark all read via POST
if (isset($_POST['sp_mark_all_read']) && wp_verify_nonce($_POST['_wpnonce'], 'sp_mark_all_read')) {
    $notifications->mark_all_read($user_id);
    $unread_count = 0;
    // Refresh notifications
    $all_notifications = $notifications->get_user_notifications($user_id, array('limit' => 50));
    $read_broadcast_ids = $notifications->get_user_read_broadcast_ids($user_id);
}

// Icons for notification types
$type_icons = array(
    'custom'       => '🔔',
    'event'        => '📅',
    'quiz'         => '📝',
    'system'       => '⚙️',
    'registration' => '🎉',
    'points'       => '⭐',
    'reminder'     => '⏰',
);
?>

<?php if ($single_notification && $single_notification->body_html): ?>
<!-- Single Notification View (Full Page) -->
<div class="sp-unified-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/notifications'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php echo esc_html(mb_strimwidth($single_notification->title, 0, 25, '...')); ?></h1>
        <div class="sp-header-actions"></div>
    </div>
</div>

<main class="sp-page-content has-bottom-nav">
    <div class="sp-card" style="padding: var(--sp-space-lg);">
        <div style="display: flex; align-items: center; gap: var(--sp-space-sm); margin-bottom: var(--sp-space-md);">
            <span style="font-size: 1.5rem;"><?php echo esc_html($single_notification->icon ?: '🔔'); ?></span>
            <div>
                <h2 style="margin: 0; font-size: 1.1rem;"><?php echo esc_html($single_notification->title); ?></h2>
                <span style="font-size: 0.75rem; color: var(--sp-text-muted);">
                    <?php echo esc_html(date_i18n('j F Y - h:i A', strtotime($single_notification->created_at))); ?>
                </span>
            </div>
        </div>
        
        <?php if ($single_notification->message): ?>
        <p style="color: var(--sp-text-secondary); margin-bottom: var(--sp-space-md); line-height: 1.6;">
            <?php echo esc_html($single_notification->message); ?>
        </p>
        <?php endif; ?>
        
        <div class="sp-notification-body-content" style="line-height: 1.8; font-size: 0.95rem;">
            <?php echo wp_kses_post($single_notification->body_html); ?>
        </div>
        
        <?php if (!empty($single_notification->link_type) && !empty($single_notification->link_id)): ?>
        <div style="margin-top: var(--sp-space-lg); padding-top: var(--sp-space-md); border-top: 1px solid var(--sp-border-light);">
            <?php if ($single_notification->link_type === 'event'): ?>
            <a href="<?php echo home_url('/app/events/' . intval($single_notification->link_id)); ?>" class="sp-btn sp-btn-primary sp-btn-block">
                📅 عرض الفعالية
            </a>
            <?php elseif ($single_notification->link_type === 'quiz'): ?>
            <a href="<?php echo home_url('/app/quizzes?quiz_id=' . $single_notification->link_id); ?>" class="sp-btn sp-btn-primary sp-btn-block">
                📝 الذهاب للاختبار
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php else: ?>
<!-- Notification Center (List View) -->
<div class="sp-unified-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('الإشعارات', 'saint-porphyrius'); ?>
            <?php if ($unread_count > 0): ?>
            <span class="sp-notif-header-badge"><?php echo esc_html($unread_count); ?></span>
            <?php endif; ?>
        </h1>
        <div class="sp-header-actions">
            <?php if ($unread_count > 0): ?>
            <form method="post" style="display: inline;">
                <?php wp_nonce_field('sp_mark_all_read'); ?>
                <button type="submit" name="sp_mark_all_read" class="sp-header-action" title="قراءة الكل" style="background: none; border: none; cursor: pointer; color: var(--sp-primary);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 11 12 14 22 4"></polyline>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                    </svg>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<main class="sp-page-content has-bottom-nav">
    
    <?php if (empty($all_notifications)): ?>
    <!-- Empty State -->
    <div style="text-align: center; padding: var(--sp-space-xxl) var(--sp-space-lg);">
        <div style="font-size: 4rem; margin-bottom: var(--sp-space-md);">🔕</div>
        <h3 style="margin: 0 0 var(--sp-space-sm) 0; color: var(--sp-text-primary);"><?php _e('لا توجد إشعارات', 'saint-porphyrius'); ?></h3>
        <p style="color: var(--sp-text-muted); margin: 0;"><?php _e('ستظهر هنا إشعاراتك عند وصولها', 'saint-porphyrius'); ?></p>
    </div>
    
    <?php else: ?>
    
    <!-- Notification List -->
    <div class="sp-notification-list">
        <?php 
        $today = current_time('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day', current_time('timestamp')));
        $current_group = '';
        
        foreach ($all_notifications as $notif):
            $notif_date = date('Y-m-d', strtotime($notif->created_at));
            
            // Determine if read
            $is_read = true;
            if ($notif->user_id == 0) {
                $is_read = in_array($notif->id, $read_broadcast_ids);
            } else {
                $is_read = (bool) $notif->is_read;
            }
            
            // Date grouping
            $group_label = '';
            if ($notif_date === $today) {
                $group_label = 'اليوم';
            } elseif ($notif_date === $yesterday) {
                $group_label = 'أمس';
            } else {
                $group_label = date_i18n('j F Y', strtotime($notif->created_at));
            }
            
            if ($group_label !== $current_group):
                $current_group = $group_label;
        ?>
        <div class="sp-notif-date-group"><?php echo esc_html($group_label); ?></div>
        <?php endif; ?>
        
        <?php
            // Build the click URL
            $click_url = $notifications->get_notification_url($notif);
            $icon = $notif->icon ?: ($type_icons[$notif->type] ?? '🔔');
        ?>
        <a href="<?php echo esc_url($click_url); ?>" 
           class="sp-notif-item <?php echo $is_read ? 'is-read' : 'is-unread'; ?>"
           data-notif-id="<?php echo esc_attr($notif->id); ?>"
           onclick="spMarkNotifRead(<?php echo esc_attr($notif->id); ?>)">
            <div class="sp-notif-icon <?php echo $is_read ? '' : 'has-dot'; ?>">
                <span><?php echo esc_html($icon); ?></span>
            </div>
            <div class="sp-notif-content">
                <div class="sp-notif-title"><?php echo esc_html($notif->title); ?></div>
                <div class="sp-notif-message"><?php echo esc_html(mb_strimwidth($notif->message, 0, 80, '...')); ?></div>
                <div class="sp-notif-meta">
                    <span class="sp-notif-time"><?php echo esc_html(sp_time_ago($notif->created_at)); ?></span>
                    <?php if ($notif->link_type === 'event'): ?>
                    <span class="sp-notif-tag sp-notif-tag-event">📅 فعالية</span>
                    <?php elseif ($notif->link_type === 'quiz'): ?>
                    <span class="sp-notif-tag sp-notif-tag-quiz">📝 اختبار</span>
                    <?php elseif (!empty($notif->body_html)): ?>
                    <span class="sp-notif-tag sp-notif-tag-page">📄 صفحة</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!$is_read): ?>
            <div class="sp-notif-unread-dot"></div>
            <?php endif; ?>
        </a>
        
        <?php endforeach; ?>
    </div>
    
    <?php endif; ?>
    
</main>

<script>
function spMarkNotifRead(notifId) {
    fetch(spApp.ajaxUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=sp_mark_notification_read&nonce=' + spApp.nonce + '&notification_id=' + notifId
    }).then(function(r) { return r.json(); }).then(function(data) {
        if (data.success) {
            // Update bell badge
            var badges = document.querySelectorAll('.sp-bell-badge');
            badges.forEach(function(b) {
                if (data.data.count > 0) {
                    b.textContent = data.data.count > 99 ? '99+' : data.data.count;
                } else {
                    b.style.display = 'none';
                }
            });
        }
    });
}
</script>

<?php endif; ?>

