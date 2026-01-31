<?php
/**
 * Saint Porphyrius - Login Template
 * User login page
 */

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="sp-app-content sp-login-page">
    <!-- Header -->
    <div class="sp-login-header">
        <div class="sp-login-logo">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                <path d="M2 17l10 5 10-5"></path>
                <path d="M2 12l10 5 10-5"></path>
                <line x1="12" y1="7" x2="12" y2="12"></line>
                <circle cx="12" cy="5" r="1" fill="currentColor"></circle>
            </svg>
        </div>
        <h1 class="sp-login-title">القديس بورفيريوس</h1>
        <p class="sp-login-subtitle">مرحباً بك في تطبيق الكنيسة</p>
    </div>

    <!-- Login Form -->
    <div class="sp-login-form-container">
        <div class="sp-login-form-card">
            <div id="sp-login-error" class="sp-alert sp-alert-error" style="display: none;">
                <div class="sp-alert-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <div class="sp-alert-content"></div>
            </div>
            <form id="sp-login-form">
                <div class="sp-form-group">
                    <label class="sp-form-label">البريد الإلكتروني</label>
                    <div class="sp-input-wrapper">
                        <span class="sp-input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                        </span>
                        <input type="email" name="email" class="sp-form-input" 
                               placeholder="أدخل بريدك الإلكتروني" required dir="ltr">
                    </div>
                </div>

                <div class="sp-form-group">
                    <label class="sp-form-label">كلمة المرور</label>
                    <div class="sp-input-wrapper">
                        <span class="sp-input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </span>
                        <input type="password" name="password" class="sp-form-input" 
                               placeholder="أدخل كلمة المرور" required>
                        <button type="button" class="sp-input-action sp-toggle-password">
                            <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="icon-eye-off" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="sp-btn sp-btn-primary sp-btn-block sp-btn-lg">
                    تسجيل الدخول
                </button>

                <div class="sp-divider">
                    <span class="sp-divider-line"></span>
                    <span class="sp-divider-text">أو</span>
                    <span class="sp-divider-line"></span>
                </div>

                <a href="<?php echo home_url('/app/register'); ?>" class="sp-btn sp-btn-outline sp-btn-block">
                    إنشاء حساب جديد
                </a>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="sp-footer">
        <p>© <?php echo date('Y'); ?> القديس بورفيريوس - جميع الحقوق محفوظة</p>
    </footer>
</div>

<script>
jQuery(document).ready(function($) {
    // Fallback login handler if main JS doesn't catch it
    $('#sp-login-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var emailInput = form.find('input[name="email"]');
        var passwordInput = form.find('input[name="password"]');
        var email = emailInput.val().trim();
        var password = passwordInput.val().trim();
        var submitBtn = form.find('button[type="submit"]');
        var originalText = submitBtn.html();
        var loginError = $('#sp-login-error');
        
        // Clear previous errors
        emailInput.removeClass('sp-input-error');
        passwordInput.removeClass('sp-input-error');
        loginError.hide();
        
        // Validate
        var hasError = false;
        if (!email) {
            emailInput.addClass('sp-input-error');
            hasError = true;
        }
        if (!password) {
            passwordInput.addClass('sp-input-error');
            hasError = true;
        }
        
        if (hasError) {
            loginError.find('.sp-alert-content').text('يرجى ملء جميع الحقول المطلوبة');
            loginError.slideDown(200);
            form.addClass('sp-shake');
            setTimeout(function() { form.removeClass('sp-shake'); }, 500);
            return false;
        }
        
        // Show loading
        submitBtn.html('<span class="sp-spinner"></span> جاري التحميل...').prop('disabled', true);
        
        // AJAX login
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'sp_login_user',
                nonce: '<?php echo wp_create_nonce('sp_nonce'); ?>',
                email: email,
                password: password
            },
            success: function(response) {
                console.log('Login response:', response);
                if (response.success) {
                    window.location.href = response.data.redirect || '<?php echo home_url('/app/dashboard'); ?>';
                } else {
                    emailInput.addClass('sp-input-error');
                    passwordInput.addClass('sp-input-error');
                    loginError.find('.sp-alert-content').text(response.data.message || 'حدث خطأ');
                    loginError.slideDown(200);
                    form.addClass('sp-shake');
                    setTimeout(function() { form.removeClass('sp-shake'); }, 500);
                    submitBtn.html(originalText).prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', xhr, status, error);
                loginError.find('.sp-alert-content').text('حدث خطأ في الاتصال، يرجى المحاولة مرة أخرى');
                loginError.slideDown(200);
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
        
        return false;
    });
    
    // Clear errors on input
    $('#sp-login-form input').on('input', function() {
        $(this).removeClass('sp-input-error');
        $('#sp-login-error').slideUp(200);
    });
});
</script>
