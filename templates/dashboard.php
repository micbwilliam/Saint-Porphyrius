<?php
/**
 * Saint Porphyrius - Dashboard Template
 * User dashboard after login
 */

if (!defined('ABSPATH')) {
    exit;
}


$current_user = wp_get_current_user();
$first_name = $current_user->first_name;
$middle_name = get_user_meta($current_user->ID, 'sp_middle_name', true);
$last_name = $current_user->last_name;
$church_name = get_user_meta($current_user->ID, 'sp_church_name', true);
?>

<div class="sp-app-content">
    <!-- Header -->
    <header class="sp-header">
        <div class="sp-header-content">
            <h1 class="sp-header-title">
                <svg class="sp-header-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                الرئيسية
            </h1>
            <a href="<?php echo home_url('/app/profile'); ?>" class="sp-header-back">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="sp-page">
        <!-- Welcome Card -->
        <div class="sp-card sp-welcome-card">
            <div class="sp-welcome-content">
                <div class="sp-welcome-avatar">
                    <?php echo get_avatar($current_user->ID, 60); ?>
                </div>
                <div class="sp-welcome-text">
                    <h2>مرحباً، <?php echo esc_html($first_name); ?>!</h2>
                    <p><?php echo esc_html($church_name); ?></p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="sp-section">
            <h3 class="sp-section-title">الخدمات السريعة</h3>
            <div class="sp-quick-actions">
                <a href="<?php echo home_url('/app/profile'); ?>" class="sp-action-card">
                    <div class="sp-action-icon" style="background: linear-gradient(135deg, #6C9BCF 0%, #5A89BD 100%);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <span>الملف الشخصي</span>
                </a>
                
                <a href="#" class="sp-action-card">
                    <div class="sp-action-icon" style="background: linear-gradient(135deg, #96C291 0%, #7DAF78 100%);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <span>الأحداث</span>
                </a>
                
                <a href="#" class="sp-action-card">
                    <div class="sp-action-icon" style="background: linear-gradient(135deg, #F2D388 0%, #E5C470 100%);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <span>الأعضاء</span>
                </a>
                
                <a href="#" class="sp-action-card">
                    <div class="sp-action-icon" style="background: linear-gradient(135deg, #E8A0A0 0%, #D68B8B 100%);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </div>
                    <span>الإعلانات</span>
                </a>
            </div>
        </div>

        <!-- Recent Updates -->
        <div class="sp-section">
            <h3 class="sp-section-title">آخر الأخبار</h3>
            <div class="sp-card">
                <div class="sp-empty-state-small">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--sp-text-light)" stroke-width="1.5">
                        <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <p>لا توجد أخبار حالياً</p>
                </div>
            </div>
        </div>

        <!-- Logout Button -->
        <div class="sp-mt-lg">
            <button type="button" class="sp-btn sp-btn-secondary sp-btn-block" id="sp-logout-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                تسجيل الخروج
            </button>
        </div>
    </main>

    <!-- Footer -->
    <footer class="sp-footer">
        <p>© <?php echo date('Y'); ?> القديس بورفيريوس - جميع الحقوق محفوظة</p>
    </footer>
</div>

<style>
.sp-welcome-card {
    background: linear-gradient(135deg, var(--sp-primary) 0%, var(--sp-primary-dark) 100%);
    color: var(--sp-text-white);
}

.sp-welcome-content {
    display: flex;
    align-items: center;
    gap: var(--sp-spacing-md);
}

.sp-welcome-avatar img {
    border-radius: var(--sp-radius-full);
    border: 3px solid rgba(255, 255, 255, 0.3);
}

.sp-welcome-text h2 {
    font-size: var(--sp-font-size-lg);
    font-weight: 600;
    margin-bottom: var(--sp-spacing-xs);
}

.sp-welcome-text p {
    font-size: var(--sp-font-size-sm);
    opacity: 0.9;
}

.sp-section {
    margin-bottom: var(--sp-spacing-xl);
}

.sp-section-title {
    font-size: var(--sp-font-size-base);
    font-weight: 600;
    color: var(--sp-text-secondary);
    margin-bottom: var(--sp-spacing-md);
}

.sp-quick-actions {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--sp-spacing-md);
}

.sp-action-card {
    background: var(--sp-bg-card);
    border-radius: var(--sp-radius-lg);
    padding: var(--sp-spacing-lg);
    text-align: center;
    text-decoration: none;
    color: var(--sp-text-primary);
    box-shadow: var(--sp-shadow-sm);
    transition: var(--sp-transition);
}

.sp-action-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--sp-shadow-md);
}

.sp-action-icon {
    width: 56px;
    height: 56px;
    border-radius: var(--sp-radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto var(--sp-spacing-sm);
    color: var(--sp-text-white);
}

.sp-action-card span {
    font-size: var(--sp-font-size-sm);
    font-weight: 500;
}

.sp-empty-state-small {
    text-align: center;
    padding: var(--sp-spacing-xl);
}

.sp-empty-state-small p {
    color: var(--sp-text-light);
    margin-top: var(--sp-spacing-sm);
    font-size: var(--sp-font-size-sm);
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#sp-logout-btn').on('click', function() {
        $.ajax({
            url: spApp.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sp_logout_user',
                nonce: spApp.nonce
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.redirect || spApp.appUrl + '/login';
                }
            }
        });
    });
});
</script>
