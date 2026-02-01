/**
 * Saint Porphyrius PWA Installer
 * Handles "Add to Home Screen" prompts for iOS and Android
 */

(function() {
    'use strict';

    // PWA Install Manager
    const PWAInstaller = {
        deferredPrompt: null,
        isInstalled: false,
        isIOS: false,
        isAndroid: false,
        isStandalone: false,

        init: function() {
            this.detectPlatform();
            this.checkIfInstalled();
            this.setupEventListeners();
            this.checkShowPrompt();
        },

        detectPlatform: function() {
            const ua = navigator.userAgent.toLowerCase();
            this.isIOS = /iphone|ipad|ipod/.test(ua);
            this.isAndroid = /android/.test(ua);
            this.isStandalone = window.matchMedia('(display-mode: standalone)').matches || 
                               window.navigator.standalone === true;
        },

        checkIfInstalled: function() {
            // Check if running as standalone app
            if (this.isStandalone) {
                this.isInstalled = true;
                return;
            }

            // Check localStorage for dismissal
            const dismissed = localStorage.getItem('sp_pwa_dismissed');
            const dismissedTime = localStorage.getItem('sp_pwa_dismissed_time');
            
            if (dismissed && dismissedTime) {
                // Show again after 7 days
                const sevenDays = 7 * 24 * 60 * 60 * 1000;
                if (Date.now() - parseInt(dismissedTime) < sevenDays) {
                    this.isInstalled = true;
                }
            }
        },

        setupEventListeners: function() {
            // Listen for beforeinstallprompt (Android/Chrome)
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                this.deferredPrompt = e;
                this.showInstallBanner();
            });

            // Listen for successful install
            window.addEventListener('appinstalled', () => {
                this.hideInstallBanner();
                this.isInstalled = true;
                localStorage.setItem('sp_pwa_installed', 'true');
            });

            // Listen for display mode changes
            window.matchMedia('(display-mode: standalone)').addEventListener('change', (e) => {
                if (e.matches) {
                    this.isStandalone = true;
                    this.hideInstallBanner();
                }
            });
        },

        checkShowPrompt: function() {
            // Don't show if already installed or running standalone
            if (this.isInstalled || this.isStandalone) {
                return;
            }

            // Delay showing the prompt
            setTimeout(() => {
                if (this.isIOS) {
                    this.showIOSPrompt();
                } else if (this.deferredPrompt) {
                    this.showInstallBanner();
                }
            }, 3000);
        },

        showInstallBanner: function() {
            if (this.isInstalled || this.isStandalone) return;

            const existingBanner = document.getElementById('sp-pwa-banner');
            if (existingBanner) return;

            const banner = document.createElement('div');
            banner.id = 'sp-pwa-banner';
            banner.className = 'sp-pwa-banner';
            banner.innerHTML = `
                <div class="sp-pwa-banner-content">
                    <div class="sp-pwa-banner-icon">
                        <img src="${spPWA.iconUrl}" alt="App Icon">
                    </div>
                    <div class="sp-pwa-banner-text">
                        <strong>إضافة للشاشة الرئيسية</strong>
                        <span>أضف التطبيق لتجربة أفضل وأسرع</span>
                    </div>
                    <div class="sp-pwa-banner-actions">
                        <button class="sp-pwa-install-btn" id="sp-pwa-install">تثبيت</button>
                        <button class="sp-pwa-close-btn" id="sp-pwa-close">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(banner);

            // Animate in
            setTimeout(() => banner.classList.add('sp-pwa-banner-show'), 100);

            // Setup button handlers
            document.getElementById('sp-pwa-install').addEventListener('click', () => {
                this.installApp();
            });

            document.getElementById('sp-pwa-close').addEventListener('click', () => {
                this.dismissBanner();
            });
        },

        showIOSPrompt: function() {
            if (this.isInstalled || this.isStandalone) return;

            const existingBanner = document.getElementById('sp-pwa-banner');
            if (existingBanner) return;

            const banner = document.createElement('div');
            banner.id = 'sp-pwa-banner';
            banner.className = 'sp-pwa-banner sp-pwa-banner-ios';
            banner.innerHTML = `
                <div class="sp-pwa-banner-content">
                    <div class="sp-pwa-banner-icon">
                        <img src="${spPWA.iconUrl}" alt="App Icon">
                    </div>
                    <div class="sp-pwa-banner-text">
                        <strong>إضافة للشاشة الرئيسية</strong>
                        <span>لتجربة أفضل، أضف التطبيق للشاشة الرئيسية</span>
                    </div>
                    <button class="sp-pwa-close-btn" id="sp-pwa-close">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                <div class="sp-pwa-ios-instructions">
                    <div class="sp-pwa-ios-step">
                        <span class="sp-pwa-ios-step-num">1</span>
                        <span>اضغط على <span class="dashicons dashicons-share"></span> في أسفل الشاشة</span>
                    </div>
                    <div class="sp-pwa-ios-step">
                        <span class="sp-pwa-ios-step-num">2</span>
                        <span>اختر "إضافة إلى الشاشة الرئيسية" <span class="dashicons dashicons-plus-alt"></span></span>
                    </div>
                    <div class="sp-pwa-ios-step">
                        <span class="sp-pwa-ios-step-num">3</span>
                        <span>اضغط "إضافة" في الأعلى</span>
                    </div>
                </div>
                <div class="sp-pwa-ios-arrow">
                    <span class="dashicons dashicons-arrow-down-alt"></span>
                </div>
            `;
            document.body.appendChild(banner);

            // Animate in
            setTimeout(() => banner.classList.add('sp-pwa-banner-show'), 100);

            // Setup close handler
            document.getElementById('sp-pwa-close').addEventListener('click', () => {
                this.dismissBanner();
            });
        },

        installApp: async function() {
            if (!this.deferredPrompt) return;

            // Show the install prompt
            this.deferredPrompt.prompt();

            // Wait for user response
            const { outcome } = await this.deferredPrompt.userChoice;
            
            if (outcome === 'accepted') {
                console.log('SP PWA: User accepted install');
                this.hideInstallBanner();
            } else {
                console.log('SP PWA: User dismissed install');
            }

            this.deferredPrompt = null;
        },

        dismissBanner: function() {
            localStorage.setItem('sp_pwa_dismissed', 'true');
            localStorage.setItem('sp_pwa_dismissed_time', Date.now().toString());
            this.hideInstallBanner();
        },

        hideInstallBanner: function() {
            const banner = document.getElementById('sp-pwa-banner');
            if (banner) {
                banner.classList.remove('sp-pwa-banner-show');
                setTimeout(() => banner.remove(), 300);
            }
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => PWAInstaller.init());
    } else {
        PWAInstaller.init();
    }

    // Export for external use
    window.SPPWAInstaller = PWAInstaller;
})();
