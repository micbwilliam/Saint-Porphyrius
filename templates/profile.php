<?php
/**
 * Saint Porphyrius - Profile Template
 * User profile page
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
?>

<div class="sp-app-content">
    <!-- Header -->
    <header class="sp-header">
        <div class="sp-header-content">
            <a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-header-back">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </a>
            <h1 class="sp-header-title">الملف الشخصي</h1>
        </div>
    </header>

    <!-- Main Content -->
    <main class="sp-page">
        <!-- Profile Header -->
        <div class="sp-profile-header">
            <div class="sp-profile-avatar">
                <?php echo get_avatar($user_id, 100); ?>
            </div>
            <h2 class="sp-profile-name">
                <?php echo esc_html($profile_data['first_name'] . ' ' . $profile_data['middle_name'] . ' ' . $profile_data['last_name']); ?>
            </h2>
            <p class="sp-profile-church"><?php echo esc_html($profile_data['church_name']); ?></p>
        </div>

        <!-- Profile Sections -->
        <div class="sp-profile-section">
            <h3 class="sp-profile-section-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                المعلومات الشخصية
            </h3>
            <div class="sp-card">
                <div class="sp-profile-field">
                    <span class="sp-profile-label">البريد الإلكتروني</span>
                    <span class="sp-profile-value" dir="ltr"><?php echo esc_html($profile_data['email']); ?></span>
                </div>
                <div class="sp-profile-field">
                    <span class="sp-profile-label">رقم الهاتف</span>
                    <span class="sp-profile-value" dir="ltr"><?php echo esc_html($profile_data['phone']); ?></span>
                </div>
                <div class="sp-profile-field">
                    <span class="sp-profile-label">الوظيفة / الكلية</span>
                    <span class="sp-profile-value"><?php echo esc_html($profile_data['job_or_college']); ?></span>
                </div>
                <div class="sp-profile-field">
                    <span class="sp-profile-label">عنوان المنزل</span>
                    <span class="sp-profile-value"><?php echo esc_html($profile_data['home_address']); ?></span>
                </div>
            </div>
        </div>

        <div class="sp-profile-section">
            <h3 class="sp-profile-section-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2z"></path>
                    <path d="M12 8v4"></path>
                    <path d="M10 10h4"></path>
                </svg>
                معلومات الكنيسة
            </h3>
            <div class="sp-card">
                <div class="sp-profile-field">
                    <span class="sp-profile-label">اسم الكنيسة</span>
                    <span class="sp-profile-value"><?php echo esc_html($profile_data['church_name']); ?></span>
                </div>
                <div class="sp-profile-field">
                    <span class="sp-profile-label">أب الاعتراف</span>
                    <span class="sp-profile-value"><?php echo esc_html($profile_data['confession_father']); ?></span>
                </div>
                <div class="sp-profile-field">
                    <span class="sp-profile-label">الأسرة بالكنيسة</span>
                    <span class="sp-profile-value"><?php echo esc_html($profile_data['church_family']); ?></span>
                </div>
                <div class="sp-profile-field">
                    <span class="sp-profile-label">خادم الأسرة</span>
                    <span class="sp-profile-value"><?php echo esc_html($profile_data['church_family_servant']); ?></span>
                </div>
                <div class="sp-profile-field">
                    <span class="sp-profile-label">الخدمة الحالية</span>
                    <span class="sp-profile-value"><?php echo esc_html($profile_data['current_church_service']); ?></span>
                </div>
            </div>
        </div>

        <?php if ($profile_data['facebook_link'] || $profile_data['instagram_link']): ?>
        <div class="sp-profile-section">
            <h3 class="sp-profile-section-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="18" cy="5" r="3"></circle>
                    <circle cx="6" cy="12" r="3"></circle>
                    <circle cx="18" cy="19" r="3"></circle>
                    <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                    <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                </svg>
                التواصل الاجتماعي
            </h3>
            <div class="sp-card">
                <?php if ($profile_data['facebook_link']): ?>
                <div class="sp-profile-field">
                    <span class="sp-profile-label">فيسبوك</span>
                    <a href="<?php echo esc_url($profile_data['facebook_link']); ?>" target="_blank" class="sp-profile-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#1877F2">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        فتح الملف الشخصي
                    </a>
                </div>
                <?php endif; ?>
                <?php if ($profile_data['instagram_link']): ?>
                <div class="sp-profile-field">
                    <span class="sp-profile-label">انستجرام</span>
                    <a href="<?php echo esc_url($profile_data['instagram_link']); ?>" target="_blank" class="sp-profile-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#E4405F">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                        فتح الملف الشخصي
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="sp-footer">
        <p>© <?php echo date('Y'); ?> القديس بورفيريوس - جميع الحقوق محفوظة</p>
    </footer>
</div>

<style>
.sp-profile-header {
    text-align: center;
    padding: var(--sp-spacing-xl) 0;
    margin-bottom: var(--sp-spacing-lg);
}

.sp-profile-avatar {
    margin-bottom: var(--sp-spacing-md);
}

.sp-profile-avatar img {
    border-radius: var(--sp-radius-full);
    border: 4px solid var(--sp-primary-light);
    box-shadow: var(--sp-shadow-md);
}

.sp-profile-name {
    font-size: var(--sp-font-size-xl);
    font-weight: 700;
    color: var(--sp-text-primary);
    margin-bottom: var(--sp-spacing-xs);
}

.sp-profile-church {
    font-size: var(--sp-font-size-sm);
    color: var(--sp-text-secondary);
}

.sp-profile-section {
    margin-bottom: var(--sp-spacing-xl);
}

.sp-profile-section-title {
    display: flex;
    align-items: center;
    gap: var(--sp-spacing-sm);
    font-size: var(--sp-font-size-base);
    font-weight: 600;
    color: var(--sp-text-secondary);
    margin-bottom: var(--sp-spacing-md);
}

.sp-profile-field {
    padding: var(--sp-spacing-md) 0;
    border-bottom: 1px solid var(--sp-border-color);
}

.sp-profile-field:last-child {
    border-bottom: none;
}

.sp-profile-label {
    display: block;
    font-size: var(--sp-font-size-xs);
    color: var(--sp-text-light);
    margin-bottom: var(--sp-spacing-xs);
    text-transform: uppercase;
}

.sp-profile-value {
    font-size: var(--sp-font-size-base);
    color: var(--sp-text-primary);
    font-weight: 500;
}

.sp-profile-link {
    display: inline-flex;
    align-items: center;
    gap: var(--sp-spacing-sm);
    color: var(--sp-primary);
    text-decoration: none;
    font-weight: 500;
    font-size: var(--sp-font-size-sm);
}

.sp-profile-link:hover {
    text-decoration: underline;
}
</style>
