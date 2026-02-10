/**
 * Saint Porphyrius - OneSignal Push Notification Integration
 * Handles OneSignal SDK initialization, subscription, and user tagging
 */
(function($) {
    'use strict';

    var SPPush = {
        initialized: false,
        playerIdSent: false,

        init: function() {
            if (!window.spPushConfig || !spPushConfig.appId) {
                return;
            }

            this.loadOneSignalSDK();
        },

        /**
         * Load OneSignal SDK dynamically
         */
        loadOneSignalSDK: function() {
            var self = this;
            
            window.OneSignalDeferred = window.OneSignalDeferred || [];
            
            // Load the SDK script
            var script = document.createElement('script');
            script.src = 'https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js';
            script.defer = true;
            script.onload = function() {
                self.initOneSignal();
            };
            document.head.appendChild(script);
        },

        /**
         * Initialize OneSignal after SDK loads
         */
        initOneSignal: function() {
            var self = this;
            var config = window.spPushConfig;

            window.OneSignalDeferred.push(function(OneSignal) {
                OneSignal.init({
                    appId: config.appId,
                    safari_web_id: config.safariWebId || undefined,
                    notifyButton: {
                        enable: false // We use custom prompt
                    },
                    welcomeNotification: {
                        disable: true // We handle this server-side
                    },
                    promptOptions: {
                        slidedown: {
                            prompts: [{
                                type: "push",
                                autoPrompt: false, // We handle the custom prompt
                                text: {
                                    acceptButton: "تفعيل 🔔",
                                    cancelButton: "لاحقاً",
                                    actionMessage: config.promptMessage || "فعّل الإشعارات علشان توصلك أخبار الفعاليات والنقاط!",
                                }
                            }]
                        }
                    },
                    allowLocalhostAsSecureOrigin: true
                }).then(function() {
                    self.initialized = true;
                    self.setupEventListeners(OneSignal);
                    self.checkSubscriptionStatus(OneSignal);
                    
                    // Show custom prompt after delay for non-subscribed users
                    if (config.promptDelay > 0 && config.userId) {
                        self.maybeShowCustomPrompt(OneSignal);
                    }
                });
            });
        },

        /**
         * Setup OneSignal event listeners
         */
        setupEventListeners: function(OneSignal) {
            var self = this;

            // Listen for subscription changes
            OneSignal.User.PushSubscription.addEventListener('change', function(event) {
                if (event.current.token) {
                    self.onSubscribed(event.current.id);
                } else {
                    self.onUnsubscribed(event.previous.id);
                }
            });
        },

        /**
         * Check current subscription status
         */
        checkSubscriptionStatus: function(OneSignal) {
            var self = this;
            var config = window.spPushConfig;
            
            var subscription = OneSignal.User.PushSubscription;
            
            if (subscription.id && subscription.optedIn) {
                // Already subscribed - sync with server
                self.syncSubscription(subscription.id);
                self.updateUI(true);
                
                // Tag user with WordPress user ID
                if (config.userId) {
                    OneSignal.User.addTag('wp_user_id', config.userId.toString());
                    OneSignal.User.addTag('user_role', config.userRole || 'member');
                }
            } else {
                self.updateUI(false);
            }
        },

        /**
         * Maybe show custom subscription prompt
         */
        maybeShowCustomPrompt: function(OneSignal) {
            var self = this;
            var config = window.spPushConfig;
            
            // Don't show if already dismissed recently
            var dismissed = localStorage.getItem('sp_push_prompt_dismissed');
            if (dismissed) {
                var dismissedAt = parseInt(dismissed, 10);
                var hoursSinceDismissed = (Date.now() - dismissedAt) / (1000 * 60 * 60);
                if (hoursSinceDismissed < 72) { // Don't show again for 72 hours
                    return;
                }
            }

            var subscription = OneSignal.User.PushSubscription;
            if (subscription.optedIn) {
                return; // Already subscribed
            }
            
            setTimeout(function() {
                // Double-check before showing
                if (subscription.optedIn || document.getElementById('sp-push-prompt')) {
                    return;
                }
                self.showCustomPrompt(OneSignal);
            }, (config.promptDelay || 10) * 1000);
        },

        /**
         * Show custom in-app subscription prompt
         */
        showCustomPrompt: function(OneSignal) {
            var self = this;
            var config = window.spPushConfig;
            var pointsText = '';
            
            if (config.subscriptionPoints && parseInt(config.subscriptionPoints) > 0) {
                pointsText = '<div style="margin-top:8px;font-size:0.85rem;color:#059669;font-weight:600;"><span style="font-size:1.1em;">⭐</span> هتاخد ' + config.subscriptionPoints + ' نقطة مكافأة!</div>';
            }

            var promptHtml = 
                '<div id="sp-push-prompt" style="position:fixed;bottom:0;left:0;right:0;z-index:99999;padding:0 16px 24px;animation:spSlideUp 0.4s ease-out;">' +
                    '<div style="background:white;border-radius:16px;padding:20px;box-shadow:0 -4px 30px rgba(0,0,0,0.15);max-width:400px;margin:0 auto;direction:rtl;text-align:center;">' +
                        '<div style="font-size:2.5rem;margin-bottom:8px;">🔔</div>' +
                        '<h3 style="margin:0 0 8px;font-size:1.05rem;color:#1a1a2e;">' + (config.promptMessage || 'فعّل الإشعارات!') + '</h3>' +
                        '<p style="margin:0;font-size:0.85rem;color:#666;">توصلك أخبار الفعاليات والاختبارات والنقاط والمزيد</p>' +
                        pointsText +
                        '<div style="display:flex;gap:8px;margin-top:16px;">' +
                            '<button id="sp-push-accept" style="flex:1;padding:12px;border:none;border-radius:10px;background:linear-gradient(135deg,#3B82F6,#2563EB);color:white;font-size:0.95rem;font-weight:600;cursor:pointer;font-family:inherit;">تفعيل الإشعارات 🔔</button>' +
                            '<button id="sp-push-dismiss" style="padding:12px 16px;border:1px solid #E5E7EB;border-radius:10px;background:white;color:#6B7280;font-size:0.85rem;cursor:pointer;font-family:inherit;">لاحقاً</button>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<style>@keyframes spSlideUp{from{transform:translateY(100%);opacity:0}to{transform:translateY(0);opacity:1}}</style>';

            $('body').append(promptHtml);

            // Accept button
            $('#sp-push-accept').on('click', function() {
                $('#sp-push-prompt').remove();
                self.requestPermission(OneSignal);
            });

            // Dismiss button
            $('#sp-push-dismiss').on('click', function() {
                $('#sp-push-prompt').remove();
                localStorage.setItem('sp_push_prompt_dismissed', Date.now().toString());
            });
        },

        /**
         * Request push notification permission
         */
        requestPermission: function(OneSignal) {
            var self = this;
            
            OneSignal.Slidedown.promptPush().then(function() {
                // Permission flow started
            }).catch(function(err) {
                console.log('SP Push: Permission prompt error', err);
                // Try native prompt as fallback
                OneSignal.Notifications.requestPermission().then(function(accepted) {
                    if (accepted) {
                        var sub = OneSignal.User.PushSubscription;
                        if (sub.id) {
                            self.onSubscribed(sub.id);
                        }
                    }
                });
            });
        },

        /**
         * Handle subscription success
         */
        onSubscribed: function(playerId) {
            var self = this;
            
            if (!playerId || self.playerIdSent) return;
            self.playerIdSent = true;
            
            var config = window.spPushConfig;
            
            // Detect browser
            var browser = self.detectBrowser();
            
            // Send to server
            $.ajax({
                url: config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sp_push_subscribe',
                    nonce: config.nonce,
                    player_id: playerId,
                    device_type: self.detectDeviceType(),
                    browser: browser
                },
                success: function(response) {
                    if (response.success) {
                        self.updateUI(true);
                        
                        // Show points notification if awarded
                        if (response.data && response.data.points_awarded) {
                            self.showPointsToast(response.data.points_awarded);
                        }
                    }
                }
            });
        },

        /**
         * Handle unsubscription
         */
        onUnsubscribed: function(playerId) {
            var self = this;
            var config = window.spPushConfig;
            
            if (!playerId) return;
            self.playerIdSent = false;
            
            $.ajax({
                url: config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sp_push_unsubscribe',
                    nonce: config.nonce,
                    player_id: playerId
                }
            });
            
            self.updateUI(false);
        },

        /**
         * Sync subscription with server (for existing subscribers)
         */
        syncSubscription: function(playerId) {
            if (!playerId || this.playerIdSent) return;
            this.onSubscribed(playerId);
        },

        /**
         * Update UI elements based on subscription status
         */
        updateUI: function(isSubscribed) {
            var $btn = $('#sp-push-toggle-btn');
            var $status = $('#sp-push-status');
            var $badge = $('.sp-push-badge');
            
            if (isSubscribed) {
                $btn.text('🔔 الإشعارات مفعّلة').removeClass('sp-btn-primary').addClass('sp-btn-outline');
                $status.text('مفعّلة ✅').css('color', '#059669');
                $badge.show().text('✅');
            } else {
                $btn.text('🔕 تفعيل الإشعارات').removeClass('sp-btn-outline').addClass('sp-btn-primary');
                $status.text('غير مفعّلة').css('color', '#DC2626');
                $badge.hide();
            }
        },

        /**
         * Show points award toast notification
         */
        showPointsToast: function(points) {
            var toast = $(
                '<div style="position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:99999;background:linear-gradient(135deg,#10B981,#059669);color:white;padding:12px 24px;border-radius:12px;font-size:0.95rem;font-weight:600;box-shadow:0 4px 15px rgba(0,0,0,0.2);animation:spSlideDown 0.4s ease-out;direction:rtl;">' +
                    '⭐ +' + points + ' نقطة مكافأة تفعيل الإشعارات!' +
                '</div>' +
                '<style>@keyframes spSlideDown{from{transform:translate(-50%,-100%);opacity:0}to{transform:translate(-50%,0);opacity:1}}</style>'
            );
            
            $('body').append(toast);
            
            setTimeout(function() {
                toast.fadeOut(500, function() {
                    toast.remove();
                });
            }, 4000);
        },

        /**
         * Manual toggle subscription (for profile page button)
         */
        toggleSubscription: function() {
            var self = this;
            
            if (!window.OneSignalDeferred) {
                alert('خدمة الإشعارات غير متوفرة');
                return;
            }
            
            window.OneSignalDeferred.push(function(OneSignal) {
                var sub = OneSignal.User.PushSubscription;
                
                if (sub.optedIn) {
                    // Unsubscribe
                    sub.optOut().then(function() {
                        self.updateUI(false);
                    });
                } else {
                    // Subscribe
                    self.requestPermission(OneSignal);
                }
            });
        },

        /**
         * Detect device type
         */
        detectDeviceType: function() {
            var ua = navigator.userAgent;
            if (/Android/i.test(ua)) return 'android';
            if (/iPhone|iPad|iPod/i.test(ua)) return 'ios';
            if (/Windows/i.test(ua)) return 'windows';
            if (/Mac/i.test(ua)) return 'mac';
            if (/Linux/i.test(ua)) return 'linux';
            return 'web';
        },

        /**
         * Detect browser
         */
        detectBrowser: function() {
            var ua = navigator.userAgent;
            if (ua.indexOf('Chrome') > -1 && ua.indexOf('Edg') === -1) return 'Chrome';
            if (ua.indexOf('Firefox') > -1) return 'Firefox';
            if (ua.indexOf('Safari') > -1 && ua.indexOf('Chrome') === -1) return 'Safari';
            if (ua.indexOf('Edg') > -1) return 'Edge';
            if (ua.indexOf('Opera') > -1 || ua.indexOf('OPR') > -1) return 'Opera';
            return 'Other';
        }
    };

    // Export globally
    window.SPPush = SPPush;

    // Initialize on document ready
    $(document).ready(function() {
        SPPush.init();
        
        // Bind toggle button
        $(document).on('click', '#sp-push-toggle-btn', function(e) {
            e.preventDefault();
            SPPush.toggleSubscription();
        });
    });

})(jQuery);
