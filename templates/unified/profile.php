<?php
/**
 * Saint Porphyrius - Profile Template (Unified Design)
 * User profile page with modern design
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
    'email' => $current_user->user_email,
    'phone' => get_user_meta($user_id, 'sp_phone', true),
    'home_address' => get_user_meta($user_id, 'sp_home_address', true),
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
        <div class="sp-header-spacer"></div>
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
                <?php echo esc_html($profile_data['first_name'] . ' ' . $profile_data['middle_name'] . ' ' . $profile_data['last_name']); ?>
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
                <span class="sp-field-label"><?php _e('رقم الهاتف', 'saint-porphyrius'); ?></span>
                <span class="sp-field-value" dir="ltr"><?php echo esc_html($profile_data['phone']); ?></span>
            </div>
            <div class="sp-profile-field">
                <span class="sp-field-label"><?php _e('الوظيفة / الكلية', 'saint-porphyrius'); ?></span>
                <span class="sp-field-value"><?php echo esc_html($profile_data['job_or_college']); ?></span>
            </div>
            <div class="sp-profile-field">
                <span class="sp-field-label"><?php _e('عنوان المنزل', 'saint-porphyrius'); ?></span>
                <span class="sp-field-value"><?php echo esc_html($profile_data['home_address']); ?></span>
            </div>
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
</style>

<script>
document.getElementById('sp-logout-btn')?.addEventListener('click', function() {
    if (confirm('<?php _e('هل أنت متأكد من تسجيل الخروج؟', 'saint-porphyrius'); ?>')) {
        window.location.href = '<?php echo wp_logout_url(home_url('/app/login')); ?>';
    }
});
</script>
