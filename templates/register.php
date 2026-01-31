<?php
/**
 * Saint Porphyrius - Registration Template
 * Multi-step registration wizard
 */

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="sp-app-content">
    <!-- Header -->
    <header class="sp-header">
        <div class="sp-header-content">
            <a href="<?php echo home_url('/app'); ?>" class="sp-header-back">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </a>
            <h1 class="sp-header-title">
                <svg class="sp-header-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
                تسجيل حساب جديد
            </h1>
        </div>
    </header>

    <!-- Main Content -->
    <main class="sp-page">
        <div class="sp-card">
            <form id="sp-registration-form" class="sp-wizard">
                <!-- Progress Steps -->
                <div class="sp-wizard-progress">
                    <div class="sp-wizard-step active" data-step="1">
                        <span class="sp-wizard-step-number">1</span>
                        <span class="sp-wizard-step-label">الحساب</span>
                    </div>
                    <div class="sp-wizard-step-line"></div>
                    <div class="sp-wizard-step" data-step="2">
                        <span class="sp-wizard-step-number">2</span>
                        <span class="sp-wizard-step-label">البيانات</span>
                    </div>
                    <div class="sp-wizard-step-line"></div>
                    <div class="sp-wizard-step" data-step="3">
                        <span class="sp-wizard-step-number">3</span>
                        <span class="sp-wizard-step-label">الكنيسة</span>
                    </div>
                    <div class="sp-wizard-step-line"></div>
                    <div class="sp-wizard-step" data-step="4">
                        <span class="sp-wizard-step-number">4</span>
                        <span class="sp-wizard-step-label">الخدمة</span>
                    </div>
                    <div class="sp-wizard-step-line"></div>
                    <div class="sp-wizard-step" data-step="5">
                        <span class="sp-wizard-step-number">5</span>
                        <span class="sp-wizard-step-label">التواصل</span>
                    </div>
                </div>

                <!-- Wizard Content -->
                <div class="sp-wizard-content">
                    
                    <!-- Step 1: Account Info -->
                    <div class="sp-wizard-panel active" data-step="1">
                        <h3 class="sp-wizard-panel-title">معلومات الحساب</h3>
                        
                        <div class="sp-form-group">
                            <label class="sp-form-label">
                                البريد الإلكتروني <span class="required">*</span>
                            </label>
                            <div class="sp-input-wrapper">
                                <span class="sp-input-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                        <polyline points="22,6 12,13 2,6"></polyline>
                                    </svg>
                                </span>
                                <input type="email" name="email" class="sp-form-input" 
                                       placeholder="example@email.com" data-required="true" dir="ltr">
                            </div>
                            <span class="sp-form-error"></span>
                        </div>

                        <div class="sp-form-group">
                            <label class="sp-form-label">
                                كلمة المرور <span class="required">*</span>
                            </label>
                            <div class="sp-input-wrapper">
                                <span class="sp-input-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                    </svg>
                                </span>
                                <input type="password" name="password" class="sp-form-input" 
                                       placeholder="أدخل كلمة المرور" data-required="true">
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
                            <div class="sp-password-strength">
                                <div class="sp-password-strength-bar"></div>
                                <div class="sp-password-strength-bar"></div>
                                <div class="sp-password-strength-bar"></div>
                            </div>
                            <span class="sp-form-hint">يجب أن تحتوي على 8 أحرف على الأقل</span>
                            <span class="sp-form-error"></span>
                        </div>

                        <div class="sp-form-group">
                            <label class="sp-form-label">
                                تأكيد كلمة المرور <span class="required">*</span>
                            </label>
                            <div class="sp-input-wrapper">
                                <span class="sp-input-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                    </svg>
                                </span>
                                <input type="password" name="confirm_password" class="sp-form-input" 
                                       placeholder="أعد كتابة كلمة المرور" data-required="true">
                            </div>
                            <span class="sp-form-error"></span>
                        </div>
                    </div>

                    <!-- Step 2: Personal Info -->
                    <div class="sp-wizard-panel" data-step="2">
                        <h3 class="sp-wizard-panel-title">البيانات الشخصية</h3>
                        
                        <div class="sp-form-group">
                            <label class="sp-form-label">
                                الاسم الأول <span class="required">*</span>
                            </label>
                            <input type="text" name="first_name" class="sp-form-input" 
                                   placeholder="أدخل الاسم الأول" data-required="true">
                            <span class="sp-form-error"></span>
                        </div>

                        <div class="sp-form-group">
                            <label class="sp-form-label">
                                الاسم الأوسط <span class="required">*</span>
                            </label>
                            <input type="text" name="middle_name" class="sp-form-input" 
                                   placeholder="أدخل الاسم الأوسط" data-required="true">
                            <span class="sp-form-error"></span>
                        </div>

                        <div class="sp-form-group">
                            <label class="sp-form-label">
                                اسم العائلة <span class="required">*</span>
                            </label>
                            <input type="text" name="last_name" class="sp-form-input" 
                                   placeholder="أدخل اسم العائلة" data-required="true">
                            <span class="sp-form-error"></span>
                        </div>

                        <div class="sp-form-group">
                            <label class="sp-form-label">
                                رقم الهاتف <span class="required">*</span>
                            </label>
                            <div class="sp-input-wrapper">
                                <span class="sp-input-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                    </svg>
                                </span>
                                <input type="tel" name="phone" class="sp-form-input" 
                                       placeholder="01xxxxxxxxx" data-required="true" dir="ltr">
                            </div>
                            <span class="sp-form-error"></span>
                        </div>

                        <div class="sp-form-group">
                            <label class="sp-form-label">
                                الوظيفة / الكلية <span class="required">*</span>
                            </label>
                            <input type="text" name="job_or_college" class="sp-form-input" 
                                   placeholder="مثال: طالب بكلية الهندسة / مهندس" data-required="true">
                            <span class="sp-form-error"></span>
                        </div>

                        <div class="sp-form-group">
                            <label class="sp-form-label">
                                عنوان المنزل <span class="required">*</span>
                            </label>
                            <textarea name="home_address" class="sp-form-textarea" rows="2" 
                                      placeholder="أدخل العنوان بالتفصيل" data-required="true"></textarea>
                            <span class="sp-form-error"></span>
                        </div>
                    </div>

                    <!-- Step 3: Church Info -->
                    <div class="sp-wizard-panel" data-step="3">
                        <h3 class="sp-wizard-panel-title">معلومات الكنيسة</h3>
                        
                        <div class="sp-form-group">
                            <label class="sp-form-label">
                                اسم الكنيسة <span class="required">*</span>
                            </label>
                            <div class="sp-input-wrapper">
                                <span class="sp-input-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M18 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2z"></path>
                                        <path d="M12 8v4"></path>
                                        <path d="M10 10h4"></path>
                                    </svg>
                                </span>
                                <input type="text" name="church_name" class="sp-form-input" 
                                       placeholder="أدخل اسم الكنيسة" data-required="true">
                            </div>
                            <span class="sp-form-error"></span>
                        </div>

                        <div class="sp-form-group">
                            <label class="sp-form-label">
                                أب الاعتراف <span class="required">*</span>
                            </label>
                            <input type="text" name="confession_father" class="sp-form-input" 
                                   placeholder="أدخل اسم أب الاعتراف" data-required="true">
                            <span class="sp-form-error"></span>
                        </div>

                        <div class="sp-form-group">
                            <label class="sp-form-label">
                                الأسرة بالكنيسة <span class="required">*</span>
                            </label>
                            <input type="text" name="church_family" class="sp-form-input" 
                                   placeholder="أدخل اسم الأسرة" data-required="true">
                            <span class="sp-form-error"></span>
                        </div>

                        <div class="sp-form-group">
                            <label class="sp-form-label">
                                خادم الأسرة بالكنيسة <span class="required">*</span>
                            </label>
                            <input type="text" name="church_family_servant" class="sp-form-input" 
                                   placeholder="أدخل اسم خادم الأسرة" data-required="true">
                            <span class="sp-form-error"></span>
                        </div>
                    </div>

                    <!-- Step 4: Service Info -->
                    <div class="sp-wizard-panel" data-step="4">
                        <h3 class="sp-wizard-panel-title">الخدمة بالكنيسة</h3>
                        
                        <div class="sp-form-group">
                            <label class="sp-form-label">
                                الخدمة الحالية بالكنيسة <span class="required">*</span>
                            </label>
                            <textarea name="current_church_service" class="sp-form-textarea" rows="4" 
                                      placeholder="اكتب تفاصيل خدمتك الحالية بالكنيسة (مثال: خادم بمدارس الأحد - فصل ثالثة ابتدائي)" 
                                      data-required="true"></textarea>
                            <span class="sp-form-error"></span>
                        </div>

                        <div class="sp-alert sp-alert-info">
                            <div class="sp-alert-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                </svg>
                            </div>
                            <div class="sp-alert-content">
                                يرجى ذكر جميع الخدمات التي تشارك فيها حالياً
                            </div>
                        </div>
                    </div>

                    <!-- Step 5: Social Media -->
                    <div class="sp-wizard-panel" data-step="5">
                        <h3 class="sp-wizard-panel-title">وسائل التواصل</h3>
                        
                        <div class="sp-form-group">
                            <label class="sp-form-label">حساب فيسبوك</label>
                            <div class="sp-social-input">
                                <div class="sp-social-icon facebook">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                </div>
                                <input type="url" name="facebook_link" class="sp-form-input" 
                                       placeholder="https://facebook.com/username" dir="ltr">
                            </div>
                            <span class="sp-form-hint">اختياري</span>
                        </div>

                        <div class="sp-form-group">
                            <label class="sp-form-label">حساب انستجرام</label>
                            <div class="sp-social-input">
                                <div class="sp-social-icon instagram">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                    </svg>
                                </div>
                                <input type="url" name="instagram_link" class="sp-form-input" 
                                       placeholder="https://instagram.com/username" dir="ltr">
                            </div>
                            <span class="sp-form-hint">اختياري</span>
                        </div>

                        <div class="sp-form-group sp-mt-lg">
                            <label class="sp-checkbox">
                                <input type="checkbox" name="terms" data-required="true">
                                <span class="sp-checkbox-mark">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </span>
                                <span class="sp-checkbox-text">
                                    أوافق على <a href="#" class="sp-link">شروط الاستخدام</a> و <a href="#" class="sp-link">سياسة الخصوصية</a>
                                </span>
                            </label>
                            <span class="sp-form-error"></span>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="sp-wizard-nav">
                    <button type="button" class="sp-btn sp-btn-secondary sp-wizard-prev" style="display: none;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                        السابق
                    </button>
                    <button type="button" class="sp-btn sp-btn-primary sp-wizard-next">
                        التالي
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>
                    <button type="submit" class="sp-btn sp-btn-primary sp-btn-block sp-wizard-submit" style="display: none;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        إرسال الطلب
                    </button>
                </div>
            </form>
        </div>

        <div class="sp-text-center sp-mt-lg">
            <p class="sp-text-sm">
                لديك حساب بالفعل؟ 
                <a href="<?php echo home_url('/app/login'); ?>" class="sp-link">تسجيل الدخول</a>
            </p>
        </div>
    </main>

    <!-- Footer -->
    <footer class="sp-footer">
        <p>© <?php echo date('Y'); ?> القديس بورفيريوس - جميع الحقوق محفوظة</p>
    </footer>
</div>
