<?php
/**
 * Saint Porphyrius - Profile Template (Unified Design)
 * User profile page with edit support
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get all user meta
$profile_data = array(
    'first_name' => $current_user->first_name,
    'middle_name' => get_user_meta($user_id, 'sp_middle_name', true),
    'last_name' => $current_user->last_name,
    'gender' => get_user_meta($user_id, 'sp_gender', true) ?: 'male',
    'birth_date' => get_user_meta($user_id, 'sp_birth_date', true),
    'email' => $current_user->user_email,
    'phone' => get_user_meta($user_id, 'sp_phone', true),
    'phone_verified' => get_user_meta($user_id, 'sp_phone_verified', true),
    'whatsapp_number' => get_user_meta($user_id, 'sp_whatsapp_number', true),
    'whatsapp_same_as_phone' => get_user_meta($user_id, 'sp_whatsapp_same_as_phone', true),
    'home_address' => get_user_meta($user_id, 'sp_home_address', true),
    'address_area' => get_user_meta($user_id, 'sp_address_area', true),
    'address_street' => get_user_meta($user_id, 'sp_address_street', true),
    'address_building' => get_user_meta($user_id, 'sp_address_building', true),
    'address_floor' => get_user_meta($user_id, 'sp_address_floor', true),
    'address_apartment' => get_user_meta($user_id, 'sp_address_apartment', true),
    'address_landmark' => get_user_meta($user_id, 'sp_address_landmark', true),
    'address_maps_url' => get_user_meta($user_id, 'sp_address_maps_url', true),
    'church_name' => get_user_meta($user_id, 'sp_church_name', true),
    'confession_father' => get_user_meta($user_id, 'sp_confession_father', true),
    'job_or_college' => get_user_meta($user_id, 'sp_job_or_college', true),
    'current_church_service' => get_user_meta($user_id, 'sp_current_church_service', true),
    'church_family' => get_user_meta($user_id, 'sp_church_family', true),
    'church_family_servant' => get_user_meta($user_id, 'sp_church_family_servant', true),
    'facebook_link' => get_user_meta($user_id, 'sp_facebook_link', true),
    'instagram_link' => get_user_meta($user_id, 'sp_instagram_link', true),
);

// Get points info
$points_handler = SP_Points::get_instance();
$user_points = $points_handler->get_balance($user_id);

$gender_labels = array('male' => 'ذكر', 'female' => 'أنثى');
?>

<!-- Unified Header -->
<div class="sp-unified-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('الملف الشخصي', 'saint-porphyrius'); ?></h1>
        <button type="button" class="sp-header-action" id="sp-edit-profile-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
            </svg>
        </button>
    </div>
</div>

<!-- Main Content -->
<main class="sp-page-content has-bottom-nav">
    <!-- Profile Hero Card -->
    <div class="sp-hero-card" style="background: linear-gradient(135deg, #E8A0A0 0%, #D68B8B 100%);">
        <div class="sp-hero-content" style="flex-direction: column; align-items: center; text-align: center;">
            <div style="margin-bottom: 12px;">
                <?php 
                $avatar = get_avatar($user_id, 80);
                $avatar = str_replace('class="avatar', 'class="avatar" style="border-radius: 50%; border: 3px solid rgba(255,255,255,0.5);', $avatar);
                echo $avatar;
                ?>
            </div>
            <h2 style="font-size: 20px; font-weight: 700; margin: 0 0 4px;">
                <?php echo esc_html($profile_data['first_name'] . ' ' . $profile_data['middle_name']); ?>
            </h2>
            <p style="font-size: 14px; opacity: 0.9; margin: 0;"><?php echo esc_html($profile_data['church_name']); ?></p>
            <div style="background: rgba(255,255,255,0.2); border-radius: 20px; padding: 8px 16px; margin-top: 12px;">
                <span style="font-weight: 700;"><?php echo esc_html($user_points); ?></span>
                <span style="font-size: 12px;"><?php _e('نقطة', 'saint-porphyrius'); ?></span>
            </div>
        </div>
    </div>

    <!-- Personal Information Section -->
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title">
                <span style="margin-left: 8px;">👤</span>
                <?php _e('المعلومات الشخصية', 'saint-porphyrius'); ?>
            </h3>
        </div>
        <div class="sp-card">
            <div class="sp-profile-field">
                <span class="sp-field-label"><?php _e('البريد الإلكتروني', 'saint-porphyrius'); ?></span>
                <span class="sp-field-value" dir="ltr"><?php echo esc_html($profile_data['email']); ?></span>
            </div>
            <div class="sp-profile-field">
                <span class="sp-field-label"><?php _e('النوع', 'saint-porphyrius'); ?></span>
                <span class="sp-field-value"><?php echo esc_html($gender_labels[$profile_data['gender']] ?? $profile_data['gender']); ?></span>
            </div>
            <div class="sp-profile-field">
                <span class="sp-field-label"><?php _e('تاريخ الميلاد', 'saint-porphyrius'); ?></span>
                <span class="sp-field-value">
                    <?php 
                    if (!empty($profile_data['birth_date'])) {
                        $birth_date = new DateTime($profile_data['birth_date']);
                        echo esc_html($birth_date->format('j F Y'));
                    } else {
                        echo '-';
                    }
                    ?>
                </span>
            </div>
            <div class="sp-profile-field">
                <span class="sp-field-label"><?php _e('رقم الهاتف', 'saint-porphyrius'); ?></span>
                <span class="sp-field-value" dir="ltr">
                    <?php echo esc_html($profile_data['phone']); ?>
                    <?php if ($profile_data['phone_verified']): ?>
                        <span class="sp-verified-badge" title="<?php _e('تم التحقق', 'saint-porphyrius'); ?>">✓</span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="sp-profile-field">
                <span class="sp-field-label"><?php _e('رقم الواتساب', 'saint-porphyrius'); ?></span>
                <span class="sp-field-value" dir="ltr">
                    <?php 
                    if ($profile_data['whatsapp_same_as_phone']) {
                        echo esc_html($profile_data['phone']);
                        echo ' <small style="color: var(--sp-text-secondary);">(نفس رقم الهاتف)</small>';
                    } else {
                        echo esc_html($profile_data['whatsapp_number'] ?: '-');
                    }
                    ?>
                </span>
            </div>
            <div class="sp-profile-field">
                <span class="sp-field-label"><?php _e('الوظيفة / الكلية', 'saint-porphyrius'); ?></span>
                <span class="sp-field-value"><?php echo esc_html($profile_data['job_or_college']); ?></span>
            </div>
        </div>
    </div>

    <!-- Address Section -->
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title">
                <span style="margin-left: 8px;">📍</span>
                <?php _e('العنوان', 'saint-porphyrius'); ?>
            </h3>
        </div>
        <div class="sp-card">
            <?php if ($profile_data['address_area']): ?>
            <div class="sp-profile-field">
                <span class="sp-field-label"><?php _e('المنطقة / الحي', 'saint-porphyrius'); ?></span>
                <span class="sp-field-value"><?php echo esc_html($profile_data['address_area']); ?></span>
            </div>
            <div class="sp-profile-field">
                <span class="sp-field-label"><?php _e('الشارع', 'saint-porphyrius'); ?></span>
                <span class="sp-field-value"><?php echo esc_html($profile_data['address_street']); ?></span>
            </div>
            <div class="sp-profile-field">
                <span class="sp-field-label"><?php _e('العقار / الدور / الشقة', 'saint-porphyrius'); ?></span>
                <span class="sp-field-value">
                    <?php echo sprintf('%s / %s / %s', 
                        esc_html($profile_data['address_building']), 
                        esc_html($profile_data['address_floor']), 
                        esc_html($profile_data['address_apartment'])
                    ); ?>
                </span>
            </div>
            <?php if ($profile_data['address_landmark']): ?>
            <div class="sp-profile-field">
                <span class="sp-field-label"><?php _e('علامة مميزة', 'saint-porphyrius'); ?></span>
                <span class="sp-field-value"><?php echo esc_html($profile_data['address_landmark']); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($profile_data['address_maps_url']): ?>
            <div class="sp-profile-field">
                <span class="sp-field-label"><?php _e('خرائط جوجل', 'saint-porphyrius'); ?></span>
                <span class="sp-field-value">
                    <a href="<?php echo esc_url($profile_data['address_maps_url']); ?>" target="_blank" class="sp-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-left: 4px;">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <?php _e('فتح في الخرائط', 'saint-porphyrius'); ?>
                    </a>
                </span>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div class="sp-profile-field">
                <span class="sp-field-label"><?php _e('عنوان المنزل', 'saint-porphyrius'); ?></span>
                <span class="sp-field-value"><?php echo esc_html($profile_data['home_address']); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Church Information Section -->
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title">
                <span style="margin-left: 8px;">⛪</span>
                <?php _e('معلومات الكنيسة', 'saint-porphyrius'); ?>
            </h3>
        </div>
        <div class="sp-card">
            <div class="sp-profile-field">
                <span class="sp-field-label"><?php _e('اسم الكنيسة', 'saint-porphyrius'); ?></span>
                <span class="sp-field-value"><?php echo esc_html($profile_data['church_name']); ?></span>
            </div>
            <div class="sp-profile-field">
                <span class="sp-field-label"><?php _e('أب الاعتراف', 'saint-porphyrius'); ?></span>
                <span class="sp-field-value"><?php echo esc_html($profile_data['confession_father']); ?></span>
            </div>
            <div class="sp-profile-field">
                <span class="sp-field-label"><?php _e('الأسرة بالكنيسة', 'saint-porphyrius'); ?></span>
                <span class="sp-field-value"><?php echo esc_html($profile_data['church_family']); ?></span>
            </div>
            <div class="sp-profile-field">
                <span class="sp-field-label"><?php _e('خادم الأسرة', 'saint-porphyrius'); ?></span>
                <span class="sp-field-value"><?php echo esc_html($profile_data['church_family_servant']); ?></span>
            </div>
            <div class="sp-profile-field">
                <span class="sp-field-label"><?php _e('الخدمة الحالية', 'saint-porphyrius'); ?></span>
                <span class="sp-field-value"><?php echo esc_html($profile_data['current_church_service']); ?></span>
            </div>
        </div>
    </div>

    <!-- Social Links Section -->
    <?php if ($profile_data['facebook_link'] || $profile_data['instagram_link']): ?>
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title">
                <span style="margin-left: 8px;">🔗</span>
                <?php _e('التواصل الاجتماعي', 'saint-porphyrius'); ?>
            </h3>
        </div>
        <div class="sp-card">
            <?php if ($profile_data['facebook_link']): ?>
            <a href="<?php echo esc_url($profile_data['facebook_link']); ?>" target="_blank" class="sp-list-item" style="text-decoration: none;">
                <div class="sp-list-icon" style="background: #1877F2; color: white;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="white">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                </div>
                <div class="sp-list-content">
                    <h4 class="sp-list-title"><?php _e('فيسبوك', 'saint-porphyrius'); ?></h4>
                    <p class="sp-list-subtitle"><?php _e('فتح الملف الشخصي', 'saint-porphyrius'); ?></p>
                </div>
                <svg class="sp-feature-arrow" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>
            <?php endif; ?>
            
            <?php if ($profile_data['instagram_link']): ?>
            <a href="<?php echo esc_url($profile_data['instagram_link']); ?>" target="_blank" class="sp-list-item" style="text-decoration: none; margin-top: 8px;">
                <div class="sp-list-icon" style="background: linear-gradient(45deg, #f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%); color: white;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="white">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                    </svg>
                </div>
                <div class="sp-list-content">
                    <h4 class="sp-list-title"><?php _e('انستجرام', 'saint-porphyrius'); ?></h4>
                    <p class="sp-list-subtitle"><?php _e('فتح الملف الشخصي', 'saint-porphyrius'); ?></p>
                </div>
                <svg class="sp-feature-arrow" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Logout Section -->
    <div class="sp-section">
        <button type="button" class="sp-btn sp-btn-secondary sp-btn-block" id="sp-logout-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <?php _e('تسجيل الخروج', 'saint-porphyrius'); ?>
        </button>
    </div>
</main>

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
        <a href="<?php echo home_url('/app/points'); ?>" class="sp-nav-item">
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
        <a href="<?php echo home_url('/app/profile'); ?>" class="sp-nav-item active">
            <div class="sp-nav-indicator"></div>
            <div class="sp-nav-icon">
                <span class="dashicons dashicons-admin-users"></span>
            </div>
            <span class="sp-nav-label"><?php _e('حسابي', 'saint-porphyrius'); ?></span>
        </a>
    </div>
</nav>

<style>
.sp-profile-field {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: var(--sp-space-md) 0;
    border-bottom: 1px solid var(--sp-border-light);
}

.sp-profile-field:last-child {
    border-bottom: none;
}

.sp-field-label {
    font-size: var(--sp-font-size-sm);
    color: var(--sp-text-secondary);
    flex-shrink: 0;
}

.sp-field-value {
    font-size: var(--sp-font-size-sm);
    color: var(--sp-text-primary);
    font-weight: var(--sp-font-medium);
    text-align: left;
    margin-right: var(--sp-space-md);
}

.sp-verified-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    background: var(--sp-success);
    color: white;
    border-radius: 50%;
    font-size: 11px;
    margin-right: 4px;
}

.sp-header-action {
    background: none;
    border: none;
    color: var(--sp-primary);
    cursor: pointer;
    padding: 8px;
    border-radius: var(--sp-radius-sm);
    transition: var(--sp-transition);
}

.sp-header-action:hover {
    background: rgba(212, 161, 42, 0.1);
}

/* Edit Modal Styles */
.sp-edit-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1000;
    background: rgba(0,0,0,0.5);
    padding: var(--sp-space-md);
    overflow: hidden;
}

.sp-edit-modal.active {
    display: flex;
    align-items: flex-start;
    justify-content: center;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
}

.sp-edit-modal-content {
    background: var(--sp-bg-card);
    border-radius: var(--sp-radius-lg);
    width: 100%;
    max-width: 500px;
    margin: var(--sp-space-xl) auto;
    display: flex;
    flex-direction: column;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.sp-edit-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--sp-space-md) var(--sp-space-lg);
    border-bottom: 1px solid var(--sp-border-light);
    flex-shrink: 0;
}

.sp-edit-modal-header h2 {
    margin: 0;
    font-size: var(--sp-font-size-lg);
}

.sp-edit-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--sp-text-secondary);
    padding: 4px;
    line-height: 1;
    width: 32px;
    height: 32px;
}

.sp-edit-modal-body {
    padding: var(--sp-space-lg);
    overflow-y: auto;
    overflow-x: hidden;
    flex: 1;
    -webkit-overflow-scrolling: touch;
}

.sp-edit-modal-footer {
    padding: var(--sp-space-md) var(--sp-space-lg);
    border-top: 1px solid var(--sp-border-light);
    display: flex;
    gap: var(--sp-space-md);
    flex-shrink: 0;
    background: var(--sp-bg-card);
}

.sp-edit-section {
    margin-bottom: var(--sp-space-xl);
}

.sp-edit-section-title {
    font-size: var(--sp-font-size-sm);
    font-weight: 600;
    color: var(--sp-text-secondary);
    margin-bottom: var(--sp-space-md);
    padding-bottom: var(--sp-space-sm);
    border-bottom: 1px solid var(--sp-border-light);
}

.sp-edit-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--sp-space-md);
}

@media (max-width: 480px) {
    .sp-edit-form-row {
        grid-template-columns: 1fr;
    }
}

.sp-edit-form-row-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: var(--sp-space-md);
}

@media (max-width: 480px) {
    .sp-edit-form-row-3 {
        grid-template-columns: 1fr 1fr 1fr;
    }
}

#sp-edit-profile-form {
    width: 100%;
    display: flex;
    flex-direction: column;
}
</style>

<!-- Edit Profile Modal -->
<div id="sp-edit-profile-modal" class="sp-edit-modal">
    <div class="sp-edit-modal-content">
        <div class="sp-edit-modal-header">
            <h2><?php _e('تعديل الملف الشخصي', 'saint-porphyrius'); ?></h2>
            <button type="button" class="sp-edit-modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form id="sp-edit-profile-form">
            <div class="sp-edit-modal-body">
                <!-- Personal Info Section -->
                <div class="sp-edit-section">
                    <h4 class="sp-edit-section-title">👤 <?php _e('المعلومات الشخصية', 'saint-porphyrius'); ?></h4>
                    
                    <div class="sp-edit-form-row">
                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('الاسم الأول', 'saint-porphyrius'); ?></label>
                            <input type="text" name="first_name" class="sp-form-input" value="<?php echo esc_attr($profile_data['first_name']); ?>">
                        </div>
                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('الاسم الأوسط', 'saint-porphyrius'); ?></label>
                            <input type="text" name="middle_name" class="sp-form-input" value="<?php echo esc_attr($profile_data['middle_name']); ?>">
                        </div>
                    </div>
                    
                    <div class="sp-edit-form-row">
                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('اسم العائلة', 'saint-porphyrius'); ?></label>
                            <input type="text" name="last_name" class="sp-form-input" value="<?php echo esc_attr($profile_data['last_name']); ?>">
                        </div>
                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('النوع', 'saint-porphyrius'); ?></label>
                            <select name="gender" class="sp-form-input">
                                <option value="male" <?php selected($profile_data['gender'], 'male'); ?>><?php _e('ذكر', 'saint-porphyrius'); ?></option>
                                <option value="female" <?php selected($profile_data['gender'], 'female'); ?>><?php _e('أنثى', 'saint-porphyrius'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="sp-form-group">
                        <label class="sp-form-label"><?php _e('تاريخ الميلاد', 'saint-porphyrius'); ?></label>
                        <input type="date" name="birth_date" class="sp-form-input" value="<?php echo esc_attr($profile_data['birth_date']); ?>" max="<?php echo date('Y-m-d', strtotime('-10 years')); ?>">
                    </div>
                    
                    <div class="sp-form-group">
                        <label class="sp-form-label"><?php _e('رقم الهاتف', 'saint-porphyrius'); ?></label>
                        <input type="tel" name="phone" class="sp-form-input" value="<?php echo esc_attr($profile_data['phone']); ?>" dir="ltr" placeholder="01xxxxxxxxx">
                    </div>
                    
                    <div class="sp-form-group">
                        <label class="sp-checkbox sp-checkbox-inline" style="margin-bottom: var(--sp-space-sm);">
                            <input type="checkbox" name="whatsapp_same_as_phone" id="edit-whatsapp-same" <?php checked($profile_data['whatsapp_same_as_phone'], '1'); ?>>
                            <span class="sp-checkbox-mark">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </span>
                            <span class="sp-checkbox-text"><?php _e('رقم الواتساب نفس رقم الهاتف', 'saint-porphyrius'); ?></span>
                        </label>
                        <input type="tel" name="whatsapp_number" id="edit-whatsapp-number" class="sp-form-input" 
                               value="<?php echo esc_attr($profile_data['whatsapp_number']); ?>" 
                               dir="ltr" placeholder="01xxxxxxxxx"
                               style="<?php echo $profile_data['whatsapp_same_as_phone'] ? 'display:none;' : ''; ?>">
                    </div>
                    
                    <div class="sp-form-group">
                        <label class="sp-form-label"><?php _e('الوظيفة / الكلية', 'saint-porphyrius'); ?></label>
                        <input type="text" name="job_or_college" class="sp-form-input" value="<?php echo esc_attr($profile_data['job_or_college']); ?>">
                    </div>
                </div>
                
                <!-- Address Section -->
                <div class="sp-edit-section">
                    <h4 class="sp-edit-section-title">📍 <?php _e('العنوان', 'saint-porphyrius'); ?></h4>
                    
                    <div class="sp-edit-form-row">
                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('المنطقة / الحي', 'saint-porphyrius'); ?></label>
                            <input type="text" name="address_area" class="sp-form-input" value="<?php echo esc_attr($profile_data['address_area']); ?>">
                        </div>
                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('الشارع', 'saint-porphyrius'); ?></label>
                            <input type="text" name="address_street" class="sp-form-input" value="<?php echo esc_attr($profile_data['address_street']); ?>">
                        </div>
                    </div>
                    
                    <div class="sp-edit-form-row-3">
                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('العقار', 'saint-porphyrius'); ?></label>
                            <input type="text" name="address_building" class="sp-form-input" value="<?php echo esc_attr($profile_data['address_building']); ?>">
                        </div>
                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('الدور', 'saint-porphyrius'); ?></label>
                            <input type="text" name="address_floor" class="sp-form-input" value="<?php echo esc_attr($profile_data['address_floor']); ?>">
                        </div>
                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('الشقة', 'saint-porphyrius'); ?></label>
                            <input type="text" name="address_apartment" class="sp-form-input" value="<?php echo esc_attr($profile_data['address_apartment']); ?>">
                        </div>
                    </div>
                    
                    <div class="sp-form-group">
                        <label class="sp-form-label"><?php _e('علامة مميزة', 'saint-porphyrius'); ?></label>
                        <input type="text" name="address_landmark" class="sp-form-input" value="<?php echo esc_attr($profile_data['address_landmark']); ?>" placeholder="<?php _e('مثال: بجوار مسجد / أمام صيدلية', 'saint-porphyrius'); ?>">
                    </div>
                    
                    <div class="sp-form-group">
                        <label class="sp-form-label"><?php _e('رابط خرائط جوجل', 'saint-porphyrius'); ?></label>
                        <input type="url" name="address_maps_url" class="sp-form-input" value="<?php echo esc_attr($profile_data['address_maps_url']); ?>" dir="ltr" placeholder="https://maps.google.com/...">
                    </div>
                </div>
                
                <!-- Church Info Section -->
                <div class="sp-edit-section">
                    <h4 class="sp-edit-section-title">⛪ <?php _e('معلومات الكنيسة', 'saint-porphyrius'); ?></h4>
                    
                    <div class="sp-form-group">
                        <label class="sp-form-label"><?php _e('اسم الكنيسة', 'saint-porphyrius'); ?></label>
                        <input type="text" name="church_name" class="sp-form-input" value="<?php echo esc_attr($profile_data['church_name']); ?>">
                    </div>
                    
                    <div class="sp-form-group">
                        <label class="sp-form-label"><?php _e('أب الاعتراف', 'saint-porphyrius'); ?></label>
                        <input type="text" name="confession_father" class="sp-form-input" value="<?php echo esc_attr($profile_data['confession_father']); ?>">
                    </div>
                    
                    <div class="sp-edit-form-row">
                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('الأسرة بالكنيسة', 'saint-porphyrius'); ?></label>
                            <input type="text" name="church_family" class="sp-form-input" value="<?php echo esc_attr($profile_data['church_family']); ?>">
                        </div>
                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('خادم الأسرة', 'saint-porphyrius'); ?></label>
                            <input type="text" name="church_family_servant" class="sp-form-input" value="<?php echo esc_attr($profile_data['church_family_servant']); ?>">
                        </div>
                    </div>
                    
                    <div class="sp-form-group">
                        <label class="sp-form-label"><?php _e('الخدمة الحالية', 'saint-porphyrius'); ?></label>
                        <textarea name="current_church_service" class="sp-form-textarea" rows="2"><?php echo esc_textarea($profile_data['current_church_service']); ?></textarea>
                    </div>
                </div>
                
                <!-- Social Links Section -->
                <div class="sp-edit-section">
                    <h4 class="sp-edit-section-title">🔗 <?php _e('وسائل التواصل', 'saint-porphyrius'); ?></h4>
                    
                    <div class="sp-form-group">
                        <label class="sp-form-label"><?php _e('حساب فيسبوك', 'saint-porphyrius'); ?></label>
                        <input type="url" name="facebook_link" class="sp-form-input" value="<?php echo esc_attr($profile_data['facebook_link']); ?>" dir="ltr" placeholder="https://facebook.com/username">
                    </div>
                    
                    <div class="sp-form-group">
                        <label class="sp-form-label"><?php _e('حساب انستجرام', 'saint-porphyrius'); ?></label>
                        <input type="url" name="instagram_link" class="sp-form-input" value="<?php echo esc_attr($profile_data['instagram_link']); ?>" dir="ltr" placeholder="https://instagram.com/username">
                    </div>
                </div>
            </div>
            <div class="sp-edit-modal-footer">
                <button type="button" class="sp-btn sp-btn-secondary" onclick="closeEditModal()" style="flex: 1;">
                    <?php _e('إلغاء', 'saint-porphyrius'); ?>
                </button>
                <button type="submit" class="sp-btn sp-btn-primary" id="sp-save-profile-btn" style="flex: 2;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    <?php _e('حفظ التغييرات', 'saint-porphyrius'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Edit Profile Modal
document.getElementById('sp-edit-profile-btn')?.addEventListener('click', function() {
    document.getElementById('sp-edit-profile-modal').classList.add('active');
    document.body.style.overflow = 'hidden';
});

function closeEditModal() {
    document.getElementById('sp-edit-profile-modal').classList.remove('active');
    document.body.style.overflow = '';
}

// Close on overlay click
document.getElementById('sp-edit-profile-modal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});

// WhatsApp toggle in edit modal
document.getElementById('edit-whatsapp-same')?.addEventListener('change', function() {
    const whatsappInput = document.getElementById('edit-whatsapp-number');
    if (this.checked) {
        whatsappInput.style.display = 'none';
        whatsappInput.value = '';
    } else {
        whatsappInput.style.display = 'block';
    }
});

// Save Profile Form
document.getElementById('sp-edit-profile-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const submitBtn = document.getElementById('sp-save-profile-btn');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = '<span class="sp-spinner"></span> <?php _e('جاري الحفظ...', 'saint-porphyrius'); ?>';
    submitBtn.disabled = true;
    
    const formData = new FormData(form);
    formData.append('action', 'sp_update_profile');
    formData.append('nonce', '<?php echo wp_create_nonce('sp_update_profile'); ?>');
    
    jQuery.ajax({
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                // Reload page to show updated data
                window.location.reload();
            } else {
                alert(response.data.message || '<?php _e('حدث خطأ أثناء الحفظ', 'saint-porphyrius'); ?>');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        },
        error: function() {
            alert('<?php _e('حدث خطأ أثناء الحفظ', 'saint-porphyrius'); ?>');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
});

// Logout
document.getElementById('sp-logout-btn')?.addEventListener('click', function() {
    if (confirm('<?php _e('هل أنت متأكد من تسجيل الخروج؟', 'saint-porphyrius'); ?>')) {
        window.location.href = '<?php echo home_url('/app/logout'); ?>';
    }
});
</script>
