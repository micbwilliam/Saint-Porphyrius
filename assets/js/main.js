/**
 * Saint Porphyrius - Main JavaScript
 * Multi-step wizard and app functionality
 */

(function($) {
    'use strict';

    // Main App Object
    const SPApp = {
        // Configuration
        config: {
            wizardSelector: '.sp-wizard',
            stepSelector: '.sp-wizard-step',
            panelSelector: '.sp-wizard-panel',
            formSelector: '#sp-registration-form',
            nextBtnSelector: '.sp-wizard-next',
            prevBtnSelector: '.sp-wizard-prev',
            submitBtnSelector: '.sp-wizard-submit',
        },

        // Current state
        state: {
            currentStep: 1,
            totalSteps: 6,
            formData: {},
            isSubmitting: false,
        },

        // Initialize
        init: function() {
            this.bindEvents();
            this.initPasswordToggle();
            this.initPasswordStrength();
            this.initWhatsAppToggle();
            this.initPhoneValidation();
            this.updateWizardUI();
        },

        // Bind Events
        bindEvents: function() {
            const self = this;

            // Wizard navigation
            $(document).on('click', this.config.nextBtnSelector, function(e) {
                e.preventDefault();
                self.nextStep();
            });

            $(document).on('click', this.config.prevBtnSelector, function(e) {
                e.preventDefault();
                self.prevStep();
            });

            $(document).on('click', this.config.submitBtnSelector, function(e) {
                e.preventDefault();
                self.submitForm();
            });

            // Form input events
            $(document).on('input', '.sp-form-input, .sp-form-textarea', function() {
                self.clearFieldError($(this));
            });

            // Login form
            $(document).on('submit', '#sp-login-form', function(e) {
                e.preventDefault();
                self.handleLogin();
            });
        },

        // Validate Current Step
        validateStep: function(stepNumber) {
            const panel = $(`.sp-wizard-panel[data-step="${stepNumber}"]`);
            let isValid = true;
            const self = this;

            panel.find('[data-required="true"]').each(function() {
                const field = $(this);
                const value = field.val().trim();

                if (!value) {
                    self.showFieldError(field, spApp.strings.required);
                    isValid = false;
                }
            });

            // Email validation
            panel.find('input[type="email"][data-required="true"]').each(function() {
                const field = $(this);
                const value = field.val().trim();

                if (value && !self.isValidEmail(value)) {
                    self.showFieldError(field, spApp.strings.invalidEmail);
                    isValid = false;
                }
            });

            // Password confirmation
            if (stepNumber === 1) {
                const password = panel.find('input[name="password"]').val();
                const confirmPassword = panel.find('input[name="confirm_password"]').val();

                if (password && confirmPassword && password !== confirmPassword) {
                    self.showFieldError(
                        panel.find('input[name="confirm_password"]'),
                        spApp.strings.passwordMismatch
                    );
                    isValid = false;
                }
            }
            
            // Phone validation (Step 2)
            if (stepNumber === 2) {
                const phoneField = panel.find('input[name="phone"]');
                const phone = phoneField.val().trim();
                
                if (phone && !self.isValidEgyptianPhone(phone)) {
                    self.showFieldError(phoneField, 'رقم الهاتف غير صحيح. يجب أن يكون 01xxxxxxxxx');
                    isValid = false;
                }
                
                // WhatsApp validation if not same as phone
                const whatsappSame = panel.find('input[name="whatsapp_same_as_phone"]').is(':checked');
                if (!whatsappSame) {
                    const whatsappField = panel.find('input[name="whatsapp_number"]');
                    const whatsapp = whatsappField.val().trim();
                    
                    if (whatsapp && !self.isValidEgyptianPhone(whatsapp)) {
                        self.showFieldError(whatsappField, 'رقم الواتساب غير صحيح. يجب أن يكون 01xxxxxxxxx');
                        isValid = false;
                    }
                }
            }
            
            // Google Maps URL validation (Step 3)
            if (stepNumber === 3) {
                const mapsField = panel.find('input[name="address_maps_url"]');
                const mapsUrl = mapsField.val().trim();
                
                if (mapsUrl) {
                    const mapsRegex = /^https?:\/\/(www\.)?(google\.com\/maps|maps\.google\.com|goo\.gl\/maps|maps\.app\.goo\.gl)/i;
                    if (!mapsRegex.test(mapsUrl)) {
                        self.showFieldError(mapsField, 'رابط خرائط جوجل غير صحيح');
                        isValid = false;
                    }
                }
            }

            return isValid;
        },

        // Show Field Error
        showFieldError: function(field, message) {
            const group = field.closest('.sp-form-group');
            group.addClass('has-error');
            group.find('.sp-form-error').text(message);
        },

        // Clear Field Error
        clearFieldError: function(field) {
            const group = field.closest('.sp-form-group');
            group.removeClass('has-error');
            field.removeClass('sp-input-error');
            
            // Also hide login error if on login page
            $('#sp-login-error').slideUp(200);
        },

        // Next Step
        nextStep: function() {
            if (!this.validateStep(this.state.currentStep)) {
                this.shakeForm();
                return;
            }

            if (this.state.currentStep < this.state.totalSteps) {
                this.saveStepData();
                this.state.currentStep++;
                this.updateWizardUI();
                this.scrollToTop();
            }
        },

        // Previous Step
        prevStep: function() {
            if (this.state.currentStep > 1) {
                this.state.currentStep--;
                this.updateWizardUI();
                this.scrollToTop();
            }
        },

        // Update Wizard UI
        updateWizardUI: function() {
            const current = this.state.currentStep;
            const total = this.state.totalSteps;

            // Update step indicators
            $('.sp-wizard-step').each(function(index) {
                const stepNum = index + 1;
                const step = $(this);

                step.removeClass('active completed');

                if (stepNum < current) {
                    step.addClass('completed');
                } else if (stepNum === current) {
                    step.addClass('active');
                }
            });

            // Update panels
            $('.sp-wizard-panel').removeClass('active');
            $(`.sp-wizard-panel[data-step="${current}"]`).addClass('active');

            // Update navigation buttons
            if (current === 1) {
                $('.sp-wizard-prev').hide();
            } else {
                $('.sp-wizard-prev').show();
            }

            if (current === total) {
                $('.sp-wizard-next').hide();
                $('.sp-wizard-submit').show();
            } else {
                $('.sp-wizard-next').show();
                $('.sp-wizard-submit').hide();
            }
        },

        // Save Step Data
        saveStepData: function() {
            const panel = $(`.sp-wizard-panel[data-step="${this.state.currentStep}"]`);
            const self = this;

            panel.find('input, select, textarea').each(function() {
                const field = $(this);
                const name = field.attr('name');
                if (name) {
                    if (field.attr('type') === 'checkbox') {
                        self.state.formData[name] = field.is(':checked') ? '1' : '';
                    } else if (field.attr('type') === 'radio') {
                        if (field.is(':checked')) {
                            self.state.formData[name] = field.val();
                        }
                    } else {
                        self.state.formData[name] = field.val();
                    }
                }
            });
        },

        // Submit Form
        submitForm: function() {
            if (this.state.isSubmitting) return;

            if (!this.validateStep(this.state.currentStep)) {
                this.shakeForm();
                return;
            }

            this.saveStepData();
            this.state.isSubmitting = true;

            const submitBtn = $('.sp-wizard-submit');
            const originalText = submitBtn.html();
            submitBtn.html('<span class="sp-spinner"></span> ' + spApp.strings.loading);
            submitBtn.prop('disabled', true);

            const self = this;

            $.ajax({
                url: spApp.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sp_register_user',
                    nonce: spApp.nonce,
                    ...this.state.formData,
                },
                success: function(response) {
                    if (response.success) {
                        window.location.href = spApp.appUrl + '/pending';
                    } else {
                        self.showAlert('error', response.data.message || spApp.strings.error);
                        submitBtn.html(originalText);
                        submitBtn.prop('disabled', false);
                    }
                },
                error: function() {
                    self.showAlert('error', spApp.strings.error);
                    submitBtn.html(originalText);
                    submitBtn.prop('disabled', false);
                },
                complete: function() {
                    self.state.isSubmitting = false;
                },
            });
        },

        // Handle Login
        handleLogin: function() {
            const form = $('#sp-login-form');
            const emailInput = form.find('input[name="email"]');
            const passwordInput = form.find('input[name="password"]');
            const email = emailInput.val().trim();
            const password = passwordInput.val();
            const submitBtn = form.find('button[type="submit"]');
            const originalText = submitBtn.html();

            // Clear previous error states
            emailInput.removeClass('sp-input-error');
            passwordInput.removeClass('sp-input-error');

            // Validate fields
            let hasError = false;
            if (!email) {
                emailInput.addClass('sp-input-error');
                hasError = true;
            }
            if (!password) {
                passwordInput.addClass('sp-input-error');
                hasError = true;
            }

            if (hasError) {
                this.showAlert('error', spApp.strings.required || 'يرجى ملء جميع الحقول المطلوبة');
                return;
            }

            // Check if it's an email or username
            const isEmail = this.isValidEmail(email);
            const isUsername = /^[a-zA-Z0-9_-]{3,20}$/.test(email);
            
            if (!isEmail && !isUsername) {
                emailInput.addClass('sp-input-error');
                this.showAlert('error', 'يرجى إدخال بريد إلكتروني صحيح أو اسم مستخدم صحيح');
                return;
            }

            submitBtn.html('<span class="sp-spinner"></span> ' + (spApp.strings.loading || 'جاري التحميل...'));
            submitBtn.prop('disabled', true);

            const self = this;

            $.ajax({
                url: spApp.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sp_login_user',
                    nonce: spApp.nonce,
                    email: email,
                    password: password,
                },
                success: function(response) {
                    console.log('Login response:', response);
                    if (response.success) {
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        } else {
                            window.location.href = spApp.appUrl + '/dashboard';
                        }
                    } else {
                        // Mark inputs as error on failed login
                        emailInput.addClass('sp-input-error');
                        passwordInput.addClass('sp-input-error');
                        self.showAlert('error', response.data.message || spApp.strings.error);
                        submitBtn.html(originalText);
                        submitBtn.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Login error:', xhr, status, error);
                    self.showAlert('error', spApp.strings.error || 'حدث خطأ، يرجى المحاولة مرة أخرى');
                    submitBtn.html(originalText);
                    submitBtn.prop('disabled', false);
                },
            });
        },

        // Password Toggle
        initPasswordToggle: function() {
            $(document).on('click', '.sp-toggle-password', function() {
                const input = $(this).closest('.sp-input-wrapper').find('input');
                const icon = $(this).find('svg use');
                
                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    $(this).find('.icon-eye').hide();
                    $(this).find('.icon-eye-off').show();
                } else {
                    input.attr('type', 'password');
                    $(this).find('.icon-eye').show();
                    $(this).find('.icon-eye-off').hide();
                }
            });
        },

        // Password Strength
        initPasswordStrength: function() {
            const self = this;

            $(document).on('input', 'input[name="password"]', function() {
                const password = $(this).val();
                const strength = self.checkPasswordStrength(password);
                const strengthEl = $(this).closest('.sp-form-group').find('.sp-password-strength');

                strengthEl.removeClass('weak medium strong');
                if (password.length > 0) {
                    strengthEl.addClass(strength);
                }
            });
        },

        // Check Password Strength
        checkPasswordStrength: function(password) {
            let strength = 0;

            if (password.length >= 8) strength++;
            if (password.match(/[a-z]+/)) strength++;
            if (password.match(/[A-Z]+/)) strength++;
            if (password.match(/[0-9]+/)) strength++;
            if (password.match(/[!@#$%^&*(),.?":{}|<>]+/)) strength++;

            if (strength < 2) return 'weak';
            if (strength < 4) return 'medium';
            return 'strong';
        },

        // WhatsApp Toggle
        initWhatsAppToggle: function() {
            const checkbox = $('#whatsapp-same-check');
            const whatsappInput = $('.sp-whatsapp-input');
            
            function toggleWhatsAppInput() {
                if (checkbox.is(':checked')) {
                    whatsappInput.hide();
                    whatsappInput.find('input').val('');
                } else {
                    whatsappInput.show();
                }
            }
            
            checkbox.on('change', toggleWhatsAppInput);
            toggleWhatsAppInput(); // Initial state
        },

        // Phone Validation for Egyptian Numbers
        initPhoneValidation: function() {
            const self = this;
            
            $(document).on('input', 'input[name="phone"], input[name="whatsapp_number"]', function() {
                let value = $(this).val();
                
                // Remove non-digits
                value = value.replace(/\D/g, '');
                
                // Remove leading zeros or country code
                if (value.startsWith('002')) {
                    value = value.substring(3);
                } else if (value.startsWith('20') && value.length > 10) {
                    value = value.substring(2);
                }
                
                // Ensure it starts with 0
                if (value.length > 0 && !value.startsWith('0')) {
                    if (value.startsWith('1')) {
                        value = '0' + value;
                    }
                }
                
                // Limit to 11 digits
                if (value.length > 11) {
                    value = value.substring(0, 11);
                }
                
                $(this).val(value);
                
                // Validate format
                if (value.length === 11) {
                    if (self.isValidEgyptianPhone(value)) {
                        $(this).removeClass('sp-input-error');
                        $(this).closest('.sp-form-group').removeClass('has-error');
                    } else {
                        $(this).addClass('sp-input-error');
                    }
                }
            });
        },

        // Validate Egyptian Phone Number
        isValidEgyptianPhone: function(phone) {
            // Must start with 01 followed by 0, 1, 2, or 5
            const regex = /^01[0125][0-9]{8}$/;
            return regex.test(phone);
        },

        // Utilities
        isValidEmail: function(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        },

        showAlert: function(type, message) {
            // Check if we're on login page with dedicated error container
            const loginError = $('#sp-login-error');
            if (loginError.length) {
                loginError.find('.sp-alert-content').text(message);
                loginError.removeClass('sp-alert-success sp-alert-error').addClass('sp-alert-' + type);
                loginError.slideDown(200);
                
                // Shake the form
                $('#sp-login-form').addClass('sp-shake');
                setTimeout(function() {
                    $('#sp-login-form').removeClass('sp-shake');
                }, 500);
                
                // Auto hide after 5 seconds
                setTimeout(function() {
                    loginError.slideUp(300);
                }, 5000);
                return;
            }
            
            const alertHtml = `
                <div class="sp-alert sp-alert-${type}">
                    <div class="sp-alert-icon">
                        ${type === 'error' ? this.getIcon('alert-circle') : this.getIcon('check-circle')}
                    </div>
                    <div class="sp-alert-content">${message}</div>
                </div>
            `;

            // Remove existing alerts
            $('.sp-alert').remove();

            // Add new alert
            $('.sp-wizard-panel.active').prepend(alertHtml);

            // Auto remove after 5 seconds
            setTimeout(function() {
                $('.sp-alert').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        shakeForm: function() {
            const form = $(this.config.formSelector);
            form.addClass('shake');
            setTimeout(function() {
                form.removeClass('shake');
            }, 500);
        },

        scrollToTop: function() {
            $('html, body').animate({
                scrollTop: $('.sp-wizard').offset().top - 20
            }, 300);
        },

        getIcon: function(name) {
            const icons = {
                'alert-circle': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>',
                'check-circle': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
            };
            return icons[name] || '';
        },
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        SPApp.init();
    });

    // Expose to global scope
    window.SPApp = SPApp;

})(jQuery);
