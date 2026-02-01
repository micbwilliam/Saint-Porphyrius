<?php
/**
 * Saint Porphyrius - Admin Members (Mobile)
 * View and manage church members
 */

if (!defined('ABSPATH')) {
    exit;
}

$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

// Get members
$args = array(
    'role__in' => array('sp_member', 'sp_church_admin'),
    'orderby' => 'registered',
    'order' => 'DESC',
);

if ($search) {
    $args['search'] = '*' . $search . '*';
    $args['search_columns'] = array('user_login', 'user_email', 'display_name');
}

$members = get_users($args);
$points_handler = SP_Points::get_instance();
?>

<!-- Admin Header -->
<div class="sp-unified-header sp-admin-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/admin'); ?>" class="sp-header-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('الأعضاء', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-spacer"></div>
    </div>
</div>

<!-- Main Content -->
<main class="sp-page-content sp-admin-content">
    <!-- Search -->
    <form method="get" class="sp-search-form">
        <div class="sp-search-input-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" name="search" value="<?php echo esc_attr($search); ?>" 
                   placeholder="<?php _e('بحث عن عضو...', 'saint-porphyrius'); ?>" class="sp-search-input">
        </div>
    </form>

    <!-- Members Count -->
    <div class="sp-admin-count">
        <?php printf(__('%d عضو', 'saint-porphyrius'), count($members)); ?>
    </div>

    <?php if (empty($members)): ?>
        <div class="sp-empty-state">
            <div class="sp-empty-icon">👥</div>
            <h3><?php _e('لا يوجد أعضاء', 'saint-porphyrius'); ?></h3>
            <p><?php _e('سيظهر الأعضاء المعتمدون هنا', 'saint-porphyrius'); ?></p>
        </div>
    <?php else: ?>
        <div class="sp-members-list">
            <?php foreach ($members as $member): 
                $name_ar = get_user_meta($member->ID, 'sp_name_ar', true);
                $middle_name = get_user_meta($member->ID, 'sp_middle_name', true);
                $full_name = $name_ar ?: ($member->first_name . ' ' . $middle_name . ' ' . $member->last_name);
                $phone = get_user_meta($member->ID, 'sp_phone', true);
                $church = get_user_meta($member->ID, 'sp_church_name', true);
                $points = $points_handler->get_balance($member->ID);
                $last_login = get_user_meta($member->ID, 'sp_last_login', true);
            ?>
                <div class="sp-member-card" data-member-id="<?php echo esc_attr($member->ID); ?>">
                    <div class="sp-member-header">
                        <div class="sp-member-avatar">
                            <?php echo esc_html(mb_substr($full_name, 0, 1)); ?>
                        </div>
                        <div class="sp-member-info">
                            <h4><?php echo esc_html($full_name); ?></h4>
                            <span class="sp-member-email"><?php echo esc_html($member->user_email); ?></span>
                        </div>
                        <div class="sp-member-points">
                            <span class="sp-member-points-value"><?php echo esc_html($points); ?></span>
                            <span class="sp-member-points-label"><?php _e('نقطة', 'saint-porphyrius'); ?></span>
                        </div>
                    </div>
                    
                    <div class="sp-member-details">
                        <?php if ($phone): ?>
                        <div class="sp-member-detail">
                            <span class="sp-member-detail-icon">📱</span>
                            <a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($phone); ?></a>
                        </div>
                        <?php endif; ?>
                        <?php if ($church): ?>
                        <div class="sp-member-detail">
                            <span class="sp-member-detail-icon">⛪</span>
                            <span><?php echo esc_html($church); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="sp-member-detail">
                            <span class="sp-member-detail-icon">📅</span>
                            <span><?php _e('انضم:', 'saint-porphyrius'); ?> <?php echo esc_html(date_i18n('j M Y', strtotime($member->user_registered))); ?></span>
                        </div>
                        <?php if ($last_login): ?>
                        <div class="sp-member-detail">
                            <span class="sp-member-detail-icon">🕐</span>
                            <span><?php _e('آخر دخول:', 'saint-porphyrius'); ?> <?php echo esc_html(date_i18n('j M Y', strtotime($last_login))); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="sp-member-actions">
                        <a href="<?php echo home_url('/app/admin/points?user_id=' . $member->ID); ?>" class="sp-btn sp-btn-outline sp-btn-sm">
                            ⭐ <?php _e('النقاط', 'saint-porphyrius'); ?>
                        </a>
                        <button type="button" class="sp-btn sp-btn-outline sp-btn-sm sp-reset-password-btn" data-user-id="<?php echo esc_attr($member->ID); ?>" data-user-email="<?php echo esc_attr($member->user_email); ?>">
                            🔐 <?php _e('إعادة تعيين كلمة المرور', 'saint-porphyrius'); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<!-- Password Reset Modal -->
<div id="sp-password-reset-modal" class="sp-modal" style="display: none;">
    <div class="sp-modal-overlay"></div>
    <div class="sp-modal-content">
        <div class="sp-modal-header">
            <h2><?php _e('إعادة تعيين كلمة المرور', 'saint-porphyrius'); ?></h2>
            <button type="button" class="sp-modal-close" onclick="closePasswordResetModal()">×</button>
        </div>
        <div class="sp-modal-body">
            <div id="sp-reset-loading" style="text-align: center;">
                <p><?php _e('جاري إنشاء رابط إعادة التعيين...', 'saint-porphyrius'); ?></p>
            </div>
            
            <div id="sp-reset-link-container" style="display: none;">
                <p style="margin-bottom: var(--sp-space-md);">
                    <?php _e('انسخ الرابط أدناه وأرسله للمستخدم:', 'saint-porphyrius'); ?>
                </p>
                <div class="sp-reset-link-box">
                    <input type="text" id="sp-reset-link-input" readonly class="sp-form-input" style="margin-bottom: var(--sp-space-md);">
                    <button type="button" class="sp-btn sp-btn-primary sp-btn-block" onclick="copyResetLink()">
                        📋 <?php _e('نسخ الرابط', 'saint-porphyrius'); ?>
                    </button>
                </div>
                <div style="margin-top: var(--sp-space-lg); padding: var(--sp-space-md); background: var(--sp-gray-50); border-radius: var(--sp-radius-md);">
                    <p style="margin: 0 0 var(--sp-space-sm); font-size: var(--sp-font-size-sm); font-weight: 600;">
                        <?php _e('📝 تعليمات للمستخدم:', 'saint-porphyrius'); ?>
                    </p>
                    <ol style="margin: 0; padding-left: var(--sp-space-lg); font-size: var(--sp-font-size-sm); color: var(--sp-text-secondary);">
                        <li><?php _e('افتح الرابط في المتصفح', 'saint-porphyrius'); ?></li>
                        <li><?php _e('أدخل كلمة المرور الجديدة', 'saint-porphyrius'); ?></li>
                        <li><?php _e('أكمل عملية التحديث', 'saint-porphyrius'); ?></li>
                    </ol>
                </div>
            </div>
        </div>
        <div class="sp-modal-footer">
            <button type="button" class="sp-btn sp-btn-outline sp-btn-block" onclick="closePasswordResetModal()">
                <?php _e('إغلاق', 'saint-porphyrius'); ?>
            </button>
        </div>
    </div>
</div>

<script>
let currentResetUserId = null;

document.querySelectorAll('.sp-reset-password-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        currentResetUserId = this.getAttribute('data-user-id');
        document.getElementById('sp-password-reset-modal').style.display = 'flex';
        generateResetLink();
    });
});

function generateResetLink() {
    if (!currentResetUserId) return;
    
    jQuery.ajax({
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        type: 'POST',
        data: {
            action: 'sp_generate_reset_link',
            nonce: '<?php echo wp_create_nonce('sp_reset_password'); ?>',
            user_id: currentResetUserId
        },
        success: function(response) {
            if (response.success) {
                document.getElementById('sp-reset-loading').style.display = 'none';
                document.getElementById('sp-reset-link-container').style.display = 'block';
                document.getElementById('sp-reset-link-input').value = response.data.reset_url;
            } else {
                alert('<?php _e('خطأ:', 'saint-porphyrius'); ?> ' + response.data.message);
                closePasswordResetModal();
            }
        },
        error: function() {
            alert('<?php _e('حدث خطأ في إنشاء الرابط', 'saint-porphyrius'); ?>');
            closePasswordResetModal();
        }
    });
}

function copyResetLink() {
    const input = document.getElementById('sp-reset-link-input');
    input.select();
    document.execCommand('copy');
    
    const btn = event.target;
    const originalText = btn.textContent;
    btn.textContent = '✓ <?php _e('تم النسخ!', 'saint-porphyrius'); ?>';
    
    setTimeout(function() {
        btn.textContent = originalText;
    }, 2000);
}

function closePasswordResetModal() {
    document.getElementById('sp-password-reset-modal').style.display = 'none';
    document.getElementById('sp-reset-loading').style.display = 'block';
    document.getElementById('sp-reset-link-container').style.display = 'none';
    currentResetUserId = null;
}

document.addEventListener('click', function(e) {
    const modal = document.getElementById('sp-password-reset-modal');
    if (e.target === modal) {
        closePasswordResetModal();
    }
});
</script>
