<?php
/**
 * Saint Porphyrius - Pending Approval Template
 * Shown after successful registration
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="sp-app-content">
    <!-- Pending Status Page -->
    <div class="sp-pending-page">
        <div class="sp-pending-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
        </div>
        
        <h1 class="sp-pending-title">في انتظار الموافقة</h1>
        
        <p class="sp-pending-message">
            تم استلام طلب التسجيل الخاص بك بنجاح.
            <br><br>
            سيتم مراجعة طلبك من قِبل إدارة الكنيسة وسنقوم بإرسال رسالة إلى بريدك الإلكتروني فور الموافقة على حسابك.
            <br><br>
            شكراً لصبرك!
        </p>
        
        <div class="sp-mt-lg">
            <a href="<?php echo home_url('/app/login'); ?>" class="sp-btn sp-btn-outline">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
                العودة لتسجيل الدخول
            </a>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="sp-footer">
        <p>© <?php echo date('Y'); ?> أسرة القديس بورفيريوس - جميع الحقوق محفوظة</p>
    </footer>
</div>
