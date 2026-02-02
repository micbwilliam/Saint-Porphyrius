<?php
/**
 * Saint Porphyrius - Home Template
 * App landing page
 */

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="sp-app-content sp-login-page">
    <!-- Header -->
    <div class="sp-login-header">
        <div class="sp-login-logo">
            <img src="<?php echo esc_url(SP_PLUGIN_URL . 'media/logo.png'); ?>" alt="St. Porphyrius Family" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; box-shadow: 0 4px 15px rgba(212, 161, 42, 0.3);">
        </div>
        <h1 class="sp-login-title">أسرة القديس برفوريوس</h1>
        <p class="sp-login-subtitle">وأما نحن فلنا فكر المسيح ( ١كو ١٦:٢ )</p>
    </div>

    <!-- Welcome Content -->
    <div class="sp-login-form-container">
        <div class="sp-login-form-card">
            <div class="sp-card-header">
                <h2 class="sp-card-title" style="font-size: 1.25rem;">مرحباً بك</h2>
                <p class="sp-card-subtitle">
                    انضم لأسرة القديس برفوريوس لخدمة القرى
                </p>
            </div>

            <a href="<?php echo home_url('/app/login'); ?>" class="sp-btn sp-btn-primary sp-btn-block sp-btn-lg sp-mb-lg">
                تسجيل الدخول
            </a>

            <a href="<?php echo home_url('/app/register'); ?>" class="sp-btn sp-btn-outline sp-btn-block">
                إنشاء حساب جديد
            </a>

            <div class="sp-mt-lg sp-text-center">
                <div class="sp-features">
                    <div class="sp-feature">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--sp-primary)" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span>تواصل مع الأعضاء</span>
                    </div>
                    <div class="sp-feature">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--sp-secondary)" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <span>متابعة الأحداث</span>
                    </div>
                    <div class="sp-feature">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--sp-accent-dark)" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        <span>مشاركة الأخبار</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="sp-footer">
        <p>© <?php echo date('Y'); ?> القديس بورفيريوس - جميع الحقوق محفوظة</p>
    </footer>
</div>

<style>
.sp-features {
    display: flex;
    flex-direction: column;
    gap: var(--sp-spacing-md);
    margin-top: var(--sp-spacing-lg);
}

.sp-feature {
    display: flex;
    align-items: center;
    gap: var(--sp-spacing-sm);
    color: var(--sp-text-secondary);
    font-size: var(--sp-font-size-sm);
}

.sp-feature svg {
    flex-shrink: 0;
}
</style>
