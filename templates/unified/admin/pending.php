<?php
/**
 * Saint Porphyrius - Admin Pending Approvals (Mobile)
 * Review and approve/reject registration requests
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'sp_pending_users';

// Handle actions
$message = '';
$message_type = '';

if (isset($_GET['action']) && isset($_GET['id']) && isset($_GET['_wpnonce'])) {
    if (wp_verify_nonce($_GET['_wpnonce'], 'sp_pending_action')) {
        $action = sanitize_text_field($_GET['action']);
        $id = intval($_GET['id']);
        $registration = SP_Registration::get_instance();
        
        if ($action === 'approve') {
            $result = $registration->approve_user($id, get_current_user_id());
            if (!is_wp_error($result)) {
                $message = __('تمت الموافقة على المستخدم بنجاح', 'saint-porphyrius');
                $message_type = 'success';
            } else {
                $message = $result->get_error_message();
                $message_type = 'error';
            }
        } elseif ($action === 'reject') {
            $result = $registration->reject_user($id, get_current_user_id());
            if (!is_wp_error($result)) {
                $message = __('تم رفض المستخدم', 'saint-porphyrius');
                $message_type = 'warning';
            } else {
                $message = $result->get_error_message();
                $message_type = 'error';
            }
        }
    }
}

// Get pending users
$pending_users = $wpdb->get_results("SELECT * FROM $table_name WHERE status = 'pending' ORDER BY created_at DESC");
?>

<!-- Admin Header -->
<div class="sp-unified-header sp-admin-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/admin'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('الموافقات المعلقة', 'saint-porphyrius'); ?></h1>
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

    <?php if (empty($pending_users)): ?>
        <div class="sp-empty-state">
            <div class="sp-empty-icon">✅</div>
            <h3><?php _e('لا توجد طلبات معلقة', 'saint-porphyrius'); ?></h3>
            <p><?php _e('تمت معالجة جميع طلبات التسجيل', 'saint-porphyrius'); ?></p>
        </div>
    <?php else: ?>
        <div class="sp-pending-list">
            <?php foreach ($pending_users as $user): ?>
                <div class="sp-pending-card">
                    <div class="sp-pending-header">
                        <div class="sp-pending-avatar">
                            <?php echo esc_html(mb_substr($user->first_name, 0, 1)); ?>
                        </div>
                        <div class="sp-pending-info">
                            <h4><?php echo esc_html($user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name); ?></h4>
                            <span class="sp-pending-date"><?php echo esc_html(date_i18n('j F Y', strtotime($user->created_at))); ?></span>
                        </div>
                    </div>
                    
                    <div class="sp-pending-details">
                        <div class="sp-pending-detail">
                            <span class="sp-pending-label"><?php _e('البريد الإلكتروني', 'saint-porphyrius'); ?></span>
                            <span class="sp-pending-value"><?php echo esc_html($user->email); ?></span>
                        </div>
                        <div class="sp-pending-detail">
                            <span class="sp-pending-label"><?php _e('الهاتف', 'saint-porphyrius'); ?></span>
                            <span class="sp-pending-value" dir="ltr"><?php echo esc_html($user->phone); ?></span>
                        </div>
                        <?php if (!empty($user->gender)): ?>
                        <div class="sp-pending-detail">
                            <span class="sp-pending-label"><?php _e('النوع', 'saint-porphyrius'); ?></span>
                            <span class="sp-pending-value"><?php echo $user->gender === 'female' ? '👩 أنثى' : '👨 ذكر'; ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($user->whatsapp_number) || !empty($user->whatsapp_same_as_phone)): ?>
                        <div class="sp-pending-detail">
                            <span class="sp-pending-label"><?php _e('واتساب', 'saint-porphyrius'); ?></span>
                            <span class="sp-pending-value" dir="ltr">
                                <?php 
                                if ($user->whatsapp_same_as_phone) {
                                    echo esc_html($user->phone) . ' (نفس الهاتف)';
                                } else {
                                    echo esc_html($user->whatsapp_number);
                                }
                                ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <div class="sp-pending-detail">
                            <span class="sp-pending-label"><?php _e('الكنيسة', 'saint-porphyrius'); ?></span>
                            <span class="sp-pending-value"><?php echo esc_html($user->church_name); ?></span>
                        </div>
                        <?php if (!empty($user->confession_father)): ?>
                        <div class="sp-pending-detail">
                            <span class="sp-pending-label"><?php _e('أب الاعتراف', 'saint-porphyrius'); ?></span>
                            <span class="sp-pending-value"><?php echo esc_html($user->confession_father); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($user->job_or_college)): ?>
                        <div class="sp-pending-detail">
                            <span class="sp-pending-label"><?php _e('العمل/الكلية', 'saint-porphyrius'); ?></span>
                            <span class="sp-pending-value"><?php echo esc_html($user->job_or_college); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($user->current_church_service)): ?>
                        <div class="sp-pending-detail">
                            <span class="sp-pending-label"><?php _e('الخدمة الحالية', 'saint-porphyrius'); ?></span>
                            <span class="sp-pending-value"><?php echo esc_html($user->current_church_service); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($user->church_family)): ?>
                        <div class="sp-pending-detail">
                            <span class="sp-pending-label"><?php _e('الأسرة بالكنيسة', 'saint-porphyrius'); ?></span>
                            <span class="sp-pending-value"><?php echo esc_html($user->church_family); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Address Section -->
                        <?php if (!empty($user->address_area)): ?>
                        <div class="sp-pending-detail sp-pending-detail-full">
                            <span class="sp-pending-label"><?php _e('العنوان', 'saint-porphyrius'); ?></span>
                            <span class="sp-pending-value">
                                <?php 
                                echo esc_html($user->address_area);
                                if (!empty($user->address_street)) echo '، ' . esc_html($user->address_street);
                                if (!empty($user->address_building)) echo '، عقار ' . esc_html($user->address_building);
                                if (!empty($user->address_floor)) echo '، دور ' . esc_html($user->address_floor);
                                if (!empty($user->address_apartment)) echo '، شقة ' . esc_html($user->address_apartment);
                                if (!empty($user->address_landmark)) echo ' (' . esc_html($user->address_landmark) . ')';
                                ?>
                                <?php if (!empty($user->address_maps_url)): ?>
                                    <a href="<?php echo esc_url($user->address_maps_url); ?>" target="_blank" style="margin-right: 8px;">🗺️</a>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php elseif (!empty($user->home_address)): ?>
                        <div class="sp-pending-detail sp-pending-detail-full">
                            <span class="sp-pending-label"><?php _e('العنوان', 'saint-porphyrius'); ?></span>
                            <span class="sp-pending-value"><?php echo esc_html($user->home_address); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="sp-pending-actions">
                        <a href="<?php echo wp_nonce_url(home_url('/app/admin/pending?action=approve&id=' . $user->id), 'sp_pending_action'); ?>" 
                           class="sp-btn sp-btn-success sp-btn-sm">
                            ✓ <?php _e('موافقة', 'saint-porphyrius'); ?>
                        </a>
                        <a href="<?php echo wp_nonce_url(home_url('/app/admin/pending?action=reject&id=' . $user->id), 'sp_pending_action'); ?>" 
                           class="sp-btn sp-btn-danger sp-btn-sm"
                           onclick="return confirm('<?php _e('هل أنت متأكد من رفض هذا المستخدم؟', 'saint-porphyrius'); ?>');">
                            ✕ <?php _e('رفض', 'saint-porphyrius'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
