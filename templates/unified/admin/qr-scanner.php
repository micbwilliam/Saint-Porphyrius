<?php
/**
 * Saint Porphyrius - Admin QR Scanner Page
 * Camera-based QR code scanner for attendance verification
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check admin permissions
if (!current_user_can('sp_manage_members') && !current_user_can('manage_options')) {
    wp_safe_redirect(home_url('/app'));
    exit;
}

$events_handler = SP_Events::get_instance();

// Get upcoming events for optional filtering
$events = $events_handler->get_all(array(
    'limit' => 50,
    'orderby' => 'event_date',
    'order' => 'DESC',
));
?>

<!-- Admin Header -->
<div class="sp-unified-header sp-admin-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/admin'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('ماسح QR للحضور', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-spacer"></div>
    </div>
</div>

<!-- Main Content -->
<main class="sp-page-content sp-admin-content sp-qr-scanner-page">
    <!-- Instructions Card -->
    <div class="sp-card sp-qr-instructions">
        <div style="text-align: center; padding: 8px 0;">
            <div style="font-size: 32px; margin-bottom: 8px;">📱</div>
            <p style="margin: 0; color: var(--sp-text-secondary); font-size: var(--sp-font-size-sm);">
                <?php _e('وجّه الكاميرا نحو رمز QR الموجود على هاتف العضو', 'saint-porphyrius'); ?>
            </p>
        </div>
    </div>

    <!-- Camera Permission Request -->
    <div id="sp-camera-permission" class="sp-card" style="text-align: center; padding: 40px 20px;">
        <div style="font-size: 64px; margin-bottom: 16px;">📷</div>
        <h3 style="margin: 0 0 12px; color: var(--sp-text-primary);"><?php _e('الوصول للكاميرا', 'saint-porphyrius'); ?></h3>
        <p style="color: var(--sp-text-secondary); margin: 0 0 24px; line-height: 1.6;">
            <?php _e('يحتاج التطبيق للوصول إلى الكاميرا الأمامية لمسح رموز QR للحضور', 'saint-porphyrius'); ?>
        </p>
        <button type="button" id="sp-start-camera-btn" class="sp-btn sp-btn-primary sp-btn-lg">
            <span class="dashicons dashicons-camera" style="margin-left: 8px;"></span>
            <?php _e('السماح بالوصول للكاميرا', 'saint-porphyrius'); ?>
        </button>
    </div>

    <!-- Scanner Container -->
    <div id="sp-scanner-container" style="display: none;">
        <!-- Video Container -->
        <div class="sp-scanner-video-wrapper">
            <video id="sp-scanner-video" playsinline autoplay></video>
            <div class="sp-scanner-overlay">
                <div class="sp-scanner-frame">
                    <div class="sp-scanner-corner top-left"></div>
                    <div class="sp-scanner-corner top-right"></div>
                    <div class="sp-scanner-corner bottom-left"></div>
                    <div class="sp-scanner-corner bottom-right"></div>
                    <div class="sp-scanner-line"></div>
                </div>
            </div>
        </div>

        <!-- Scanner Controls -->
        <div class="sp-scanner-controls">
            <button type="button" id="sp-toggle-camera-btn" class="sp-btn sp-btn-outline sp-btn-sm">
                <span class="dashicons dashicons-camera"></span>
                <?php _e('تبديل الكاميرا', 'saint-porphyrius'); ?>
            </button>
            <button type="button" id="sp-stop-scanner-btn" class="sp-btn sp-btn-outline sp-btn-sm" style="color: var(--sp-error); border-color: var(--sp-error);">
                <span class="dashicons dashicons-no"></span>
                <?php _e('إيقاف', 'saint-porphyrius'); ?>
            </button>
        </div>

        <!-- Recent Scans -->
        <div class="sp-section" style="margin-top: var(--sp-space-md);">
            <div class="sp-section-header">
                <h3 class="sp-section-title"><?php _e('آخر عمليات المسح', 'saint-porphyrius'); ?></h3>
                <span id="sp-scan-count" class="sp-badge" style="background: var(--sp-primary-50); color: var(--sp-primary);">0</span>
            </div>
            <div id="sp-recent-scans" class="sp-list">
                <div class="sp-empty-state-small" style="padding: 24px; text-align: center;">
                    <p style="margin: 0; color: var(--sp-text-tertiary); font-size: var(--sp-font-size-sm);">
                        <?php _e('لم يتم مسح أي رموز بعد', 'saint-porphyrius'); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Status Modal -->
    <div id="sp-attendance-modal" class="sp-modal-overlay" style="display: none;">
        <div class="sp-modal-container">
            <div id="sp-modal-loading" style="text-align: center; padding: 40px;">
                <div class="sp-spinner" style="margin: 0 auto 16px;"></div>
                <p style="margin: 0; color: var(--sp-text-secondary);"><?php _e('جاري التحقق...', 'saint-porphyrius'); ?></p>
            </div>
            
            <div id="sp-modal-select-status" style="display: none;">
                <div class="sp-modal-header" style="text-align: center; padding: 24px 20px 16px;">
                    <div id="sp-modal-user-avatar" class="sp-modal-avatar">👤</div>
                    <h3 id="sp-modal-user-name" style="margin: 12px 0 4px; font-size: var(--sp-font-size-lg); color: var(--sp-text-primary);"></h3>
                    <p id="sp-modal-event-title" style="margin: 0; color: var(--sp-text-secondary); font-size: var(--sp-font-size-sm);"></p>
                </div>
                <div class="sp-modal-body" style="padding: 0 20px 24px;">
                    <p style="text-align: center; margin: 0 0 20px; color: var(--sp-text-secondary);">
                        <?php _e('اختر حالة الحضور', 'saint-porphyrius'); ?>
                    </p>
                    <div style="display: flex; gap: 12px;">
                        <button type="button" class="sp-btn sp-btn-lg sp-attendance-status-btn" data-status="attended" style="flex: 1; background: var(--sp-success); color: white;">
                            <div style="font-size: 24px; margin-bottom: 4px;">✓</div>
                            <div><?php _e('حاضر', 'saint-porphyrius'); ?></div>
                        </button>
                        <button type="button" class="sp-btn sp-btn-lg sp-attendance-status-btn" data-status="late" style="flex: 1; background: var(--sp-warning); color: white;">
                            <div style="font-size: 24px; margin-bottom: 4px;">⏰</div>
                            <div><?php _e('متأخر', 'saint-porphyrius'); ?></div>
                        </button>
                    </div>
                    <button type="button" id="sp-cancel-attendance-btn" class="sp-btn sp-btn-outline sp-btn-block" style="margin-top: 12px; color: var(--sp-text-secondary);">
                        <?php _e('إلغاء', 'saint-porphyrius'); ?>
                    </button>
                </div>
            </div>
            
            <div id="sp-modal-result" style="display: none;">
                <div class="sp-modal-body" style="text-align: center; padding: 40px 20px;">
                    <div id="sp-modal-result-icon" style="font-size: 64px; margin-bottom: 16px;">✓</div>
                    <h3 id="sp-modal-result-title" style="margin: 0 0 8px; color: var(--sp-text-primary);"></h3>
                    <p id="sp-modal-result-message" style="margin: 0 0 24px; color: var(--sp-text-secondary);"></p>
                    <button type="button" id="sp-close-result-btn" class="sp-btn sp-btn-primary sp-btn-block">
                        <?php _e('متابعة المسح', 'saint-porphyrius'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- QR Scanner Library (jsQR) -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>

<script>
jQuery(document).ready(function($) {
    var video = document.getElementById('sp-scanner-video');
    var canvas = document.createElement('canvas');
    var ctx = canvas.getContext('2d');
    var scanning = false;
    var stream = null;
    var scanInterval = null;
    var currentFacingMode = 'environment'; // Start with back camera
    var lastScannedCode = null;
    var lastScanTime = 0;
    var scanCount = 0;
    var pendingQRContent = null;
    
    var adminNonce = '<?php echo wp_create_nonce('sp_admin_nonce'); ?>';
    
    // Start camera button
    $('#sp-start-camera-btn').on('click', function() {
        startCamera();
    });
    
    // Toggle camera (front/back)
    $('#sp-toggle-camera-btn').on('click', function() {
        currentFacingMode = currentFacingMode === 'environment' ? 'user' : 'environment';
        stopCamera();
        startCamera();
    });
    
    // Stop scanner
    $('#sp-stop-scanner-btn').on('click', function() {
        stopCamera();
        $('#sp-scanner-container').hide();
        $('#sp-camera-permission').show();
    });
    
    // Cancel attendance
    $('#sp-cancel-attendance-btn').on('click', function() {
        closeModal();
        resumeScanning();
    });
    
    // Close result
    $('#sp-close-result-btn').on('click', function() {
        closeModal();
        resumeScanning();
    });
    
    // Attendance status buttons
    $('.sp-attendance-status-btn').on('click', function() {
        var status = $(this).data('status');
        markAttendance(pendingQRContent, status);
    });
    
    function startCamera() {
        // Try with facingMode first (for mobile), fallback to simple video (for desktop)
        var constraints = {
            video: {
                width: { ideal: 1280 },
                height: { ideal: 720 }
            }
        };
        
        // Add facingMode only if we're likely on mobile
        if (currentFacingMode && /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
            constraints.video.facingMode = currentFacingMode;
        }
        
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('<?php _e('المتصفح لا يدعم الوصول للكاميرا. يرجى استخدام متصفح حديث أو التأكد من تفعيل HTTPS.', 'saint-porphyrius'); ?>');
            return;
        }
        
        navigator.mediaDevices.getUserMedia(constraints)
            .then(function(mediaStream) {
                stream = mediaStream;
                video.srcObject = mediaStream;
                video.play();
                
                video.onloadedmetadata = function() {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    
                    $('#sp-camera-permission').hide();
                    $('#sp-scanner-container').show();
                    
                    startScanning();
                };
            })
            .catch(function(err) {
                console.error('Camera error:', err);
                alert('<?php _e('فشل في الوصول للكاميرا. تأكد من السماح بالوصول للكاميرا.', 'saint-porphyrius'); ?>');
            });
    }
    
    function stopCamera() {
        scanning = false;
        if (scanInterval) {
            clearInterval(scanInterval);
            scanInterval = null;
        }
        if (stream) {
            stream.getTracks().forEach(function(track) {
                track.stop();
            });
            stream = null;
        }
    }
    
    function startScanning() {
        scanning = true;
        scanInterval = setInterval(scanFrame, 100); // Scan 10 times per second
    }
    
    function pauseScanning() {
        scanning = false;
        if (scanInterval) {
            clearInterval(scanInterval);
            scanInterval = null;
        }
    }
    
    function resumeScanning() {
        if (!scanInterval) {
            startScanning();
        }
    }
    
    function scanFrame() {
        if (!scanning || video.readyState !== video.HAVE_ENOUGH_DATA) return;
        
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        var imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        
        var code = jsQR(imageData.data, imageData.width, imageData.height, {
            inversionAttempts: 'dontInvert'
        });
        
        if (code) {
            var now = Date.now();
            // Prevent scanning same code within 3 seconds
            if (code.data === lastScannedCode && (now - lastScanTime) < 3000) {
                return;
            }
            
            lastScannedCode = code.data;
            lastScanTime = now;
            
            // Vibrate for feedback
            if (navigator.vibrate) {
                navigator.vibrate(100);
            }
            
            processQRCode(code.data);
        }
    }
    
    function processQRCode(qrContent) {
        pauseScanning();
        
        try {
            var qrData = JSON.parse(qrContent);
            
            // Validate QR structure
            if (!qrData.t || !qrData.e || !qrData.u || !qrData.s) {
                showError('<?php _e('رمز QR غير صالح', 'saint-porphyrius'); ?>');
                return;
            }
            
            pendingQRContent = qrContent;
            showStatusSelection(qrData);
            
        } catch (e) {
            showError('<?php _e('رمز QR غير صالح - تنسيق غير معروف', 'saint-porphyrius'); ?>');
        }
    }
    
    function showStatusSelection(qrData) {
        // Show modal with loading first
        $('#sp-modal-loading').hide();
        $('#sp-modal-select-status').show();
        $('#sp-modal-result').hide();
        
        // We don't have user name from QR directly, will get from response
        $('#sp-modal-user-name').text('<?php _e('جاري التحميل...', 'saint-porphyrius'); ?>');
        $('#sp-modal-event-title').text('<?php _e('الفعالية', 'saint-porphyrius'); ?> #' + qrData.e);
        
        $('#sp-attendance-modal').fadeIn(200);
    }
    
    function markAttendance(qrContent, status) {
        $('#sp-modal-select-status').hide();
        $('#sp-modal-loading').show();
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'sp_validate_qr_attendance',
                nonce: adminNonce,
                qr_content: qrContent,
                status: status
            },
            success: function(response) {
                $('#sp-modal-loading').hide();
                
                if (response.success) {
                    scanCount++;
                    $('#sp-scan-count').text(scanCount);
                    
                    showResult(true, response.data);
                    addToRecentScans(response.data);
                } else {
                    showResult(false, response.data);
                }
            },
            error: function() {
                showResult(false, {
                    message: '<?php _e('حدث خطأ في الاتصال', 'saint-porphyrius'); ?>'
                });
            }
        });
    }
    
    function showResult(success, data) {
        $('#sp-modal-loading').hide();
        $('#sp-modal-select-status').hide();
        $('#sp-modal-result').show();
        
        if (success) {
            $('#sp-modal-result-icon').text('✓').css('color', 'var(--sp-success)');
            $('#sp-modal-result-title').text(data.user_name);
            
            var statusLabel = data.status === 'attended' ? '<?php _e('حاضر', 'saint-porphyrius'); ?>' : '<?php _e('متأخر', 'saint-porphyrius'); ?>';
            var pointsText = data.points > 0 ? '+' + data.points : data.points;
            
            $('#sp-modal-result-message').html(
                statusLabel + ' | ' + pointsText + ' <?php _e('نقطة', 'saint-porphyrius'); ?>' +
                '<br><small style="color: var(--sp-text-tertiary);">' + data.event_title + '</small>'
            );
        } else {
            var icon = '⚠️';
            var title = '<?php _e('خطأ', 'saint-porphyrius'); ?>';
            
            if (data.error_code === 'token_expired') {
                icon = '⏰';
                title = '<?php _e('انتهت الصلاحية', 'saint-porphyrius'); ?>';
            } else if (data.error_code === 'token_used' || data.error_code === 'already_marked') {
                icon = '📋';
                title = '<?php _e('تم التسجيل مسبقاً', 'saint-porphyrius'); ?>';
            } else if (data.error_code === 'invalid_qr' || data.error_code === 'invalid_signature') {
                icon = '❌';
                title = '<?php _e('رمز غير صالح', 'saint-porphyrius'); ?>';
            }
            
            $('#sp-modal-result-icon').text(icon).css('color', 'var(--sp-error)');
            $('#sp-modal-result-title').text(title);
            $('#sp-modal-result-message').text(data.message);
        }
    }
    
    function showError(message) {
        $('#sp-modal-loading').hide();
        $('#sp-modal-select-status').hide();
        
        $('#sp-modal-result-icon').text('⚠️').css('color', 'var(--sp-error)');
        $('#sp-modal-result-title').text('<?php _e('خطأ', 'saint-porphyrius'); ?>');
        $('#sp-modal-result-message').text(message);
        
        $('#sp-modal-result').show();
        $('#sp-attendance-modal').fadeIn(200);
    }
    
    function closeModal() {
        $('#sp-attendance-modal').fadeOut(200);
        pendingQRContent = null;
    }
    
    function addToRecentScans(data) {
        var statusClass = data.status === 'attended' ? 'attended' : 'late';
        var statusLabel = data.status === 'attended' ? '<?php _e('حاضر', 'saint-porphyrius'); ?>' : '<?php _e('متأخر', 'saint-porphyrius'); ?>';
        var statusIcon = data.status === 'attended' ? '✓' : '⏰';
        
        var html = '<div class="sp-list-item sp-scan-result ' + statusClass + '">' +
            '<div class="sp-list-icon" style="background: ' + (data.status === 'attended' ? 'var(--sp-success-light)' : 'var(--sp-warning-light)') + '; color: ' + (data.status === 'attended' ? 'var(--sp-success)' : 'var(--sp-warning)') + ';">' + statusIcon + '</div>' +
            '<div class="sp-list-content">' +
            '<h4 class="sp-list-title">' + data.user_name + '</h4>' +
            '<p class="sp-list-subtitle">' + data.event_title + '</p>' +
            '</div>' +
            '<div class="sp-list-action">' +
            '<span class="sp-badge" style="background: ' + (data.status === 'attended' ? 'var(--sp-success-light)' : 'var(--sp-warning-light)') + '; color: ' + (data.status === 'attended' ? 'var(--sp-success)' : 'var(--sp-warning)') + ';">' + statusLabel + '</span>' +
            '</div>' +
            '</div>';
        
        // Remove empty state
        $('#sp-recent-scans .sp-empty-state-small').remove();
        
        // Add to top
        $('#sp-recent-scans').prepend(html);
        
        // Keep only last 10 scans
        if ($('#sp-recent-scans .sp-list-item').length > 10) {
            $('#sp-recent-scans .sp-list-item:last').remove();
        }
    }
});
</script>

<style>
/* Scanner Page Styles */
.sp-qr-scanner-page {
    padding-bottom: 0 !important;
}

.sp-qr-instructions {
    margin-bottom: var(--sp-space-md);
}

.sp-scanner-video-wrapper {
    position: relative;
    width: 100%;
    aspect-ratio: 4/3;
    background: #000;
    border-radius: var(--sp-radius-lg);
    overflow: hidden;
}

#sp-scanner-video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.sp-scanner-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.sp-scanner-frame {
    width: 200px;
    height: 200px;
    position: relative;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 16px;
}

.sp-scanner-corner {
    position: absolute;
    width: 30px;
    height: 30px;
    border-color: var(--sp-primary);
    border-style: solid;
    border-width: 0;
}

.sp-scanner-corner.top-left {
    top: -2px;
    left: -2px;
    border-top-width: 4px;
    border-left-width: 4px;
    border-top-left-radius: 16px;
}

.sp-scanner-corner.top-right {
    top: -2px;
    right: -2px;
    border-top-width: 4px;
    border-right-width: 4px;
    border-top-right-radius: 16px;
}

.sp-scanner-corner.bottom-left {
    bottom: -2px;
    left: -2px;
    border-bottom-width: 4px;
    border-left-width: 4px;
    border-bottom-left-radius: 16px;
}

.sp-scanner-corner.bottom-right {
    bottom: -2px;
    right: -2px;
    border-bottom-width: 4px;
    border-right-width: 4px;
    border-bottom-right-radius: 16px;
}

.sp-scanner-line {
    position: absolute;
    left: 10px;
    right: 10px;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--sp-primary), transparent);
    animation: scanLine 2s ease-in-out infinite;
}

@keyframes scanLine {
    0%, 100% { top: 10px; }
    50% { top: calc(100% - 10px); }
}

.sp-scanner-controls {
    display: flex;
    gap: 12px;
    padding: 16px 0;
    justify-content: center;
}

/* Modal */
.sp-modal-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: var(--sp-primary-50);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
    margin: 0 auto;
}

.sp-attendance-status-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px !important;
    border-radius: var(--sp-radius-lg) !important;
    min-height: 100px;
}

/* Recent scans */
.sp-scan-result {
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Spinner */
.sp-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid var(--sp-border);
    border-top-color: var(--sp-primary);
    border-radius: 50%;
    animation: sp-spin 0.8s linear infinite;
}

@keyframes sp-spin {
    to { transform: rotate(360deg); }
}
</style>
