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

$gender_labels = array('male' => 'ذكر', 'female' => 'أنثى');
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
                $gender = get_user_meta($member->ID, 'sp_gender', true) ?: 'male';
                $whatsapp = get_user_meta($member->ID, 'sp_whatsapp_number', true);
                $whatsapp_same = get_user_meta($member->ID, 'sp_whatsapp_same_as_phone', true);
                $church = get_user_meta($member->ID, 'sp_church_name', true);
                $address_area = get_user_meta($member->ID, 'sp_address_area', true);
                $address_maps = get_user_meta($member->ID, 'sp_address_maps_url', true);
                $points = $points_handler->get_balance($member->ID);
                $last_login = get_user_meta($member->ID, 'sp_last_login', true);
                
                $display_whatsapp = $whatsapp_same ? $phone : $whatsapp;
            ?>
                <div class="sp-member-card" data-member-id="<?php echo esc_attr($member->ID); ?>">
                    <div class="sp-member-header">
                        <div class="sp-member-avatar">
                            <?php echo esc_html(mb_substr($full_name, 0, 1)); ?>
                        </div>
                        <div class="sp-member-info">
                            <h4>
                                <?php echo esc_html($full_name); ?>
                                <span class="sp-member-gender"><?php echo $gender === 'male' ? '👨' : '👩'; ?></span>
                            </h4>
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
                        <?php if ($display_whatsapp): ?>
                        <div class="sp-member-detail">
                            <span class="sp-member-detail-icon" style="color: #25D366;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                            </span>
                            <a href="https://wa.me/2<?php echo esc_attr($display_whatsapp); ?>" target="_blank"><?php echo esc_html($display_whatsapp); ?></a>
                        </div>
                        <?php endif; ?>
                        <?php if ($church): ?>
                        <div class="sp-member-detail">
                            <span class="sp-member-detail-icon">⛪</span>
                            <span><?php echo esc_html($church); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($address_area): ?>
                        <div class="sp-member-detail">
                            <span class="sp-member-detail-icon">📍</span>
                            <span>
                                <?php echo esc_html($address_area); ?>
                                <?php if ($address_maps): ?>
                                    <a href="<?php echo esc_url($address_maps); ?>" target="_blank" style="margin-right: 4px;">🗺️</a>
                                <?php endif; ?>
                            </span>
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
                        <button type="button" class="sp-btn sp-btn-outline sp-btn-sm sp-view-member-btn" data-member-id="<?php echo esc_attr($member->ID); ?>">
                            👁️ <?php _e('عرض', 'saint-porphyrius'); ?>
                        </button>
                        <a href="<?php echo home_url('/app/admin/points?user_id=' . $member->ID); ?>" class="sp-btn sp-btn-outline sp-btn-sm">
                            ⭐ <?php _e('النقاط', 'saint-porphyrius'); ?>
                        </a>
                        <button type="button" class="sp-btn sp-btn-outline sp-btn-sm sp-reset-password-btn" data-user-id="<?php echo esc_attr($member->ID); ?>" data-user-email="<?php echo esc_attr($member->user_email); ?>">
                            🔐 <?php _e('كلمة المرور', 'saint-porphyrius'); ?>
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

// View Member Details Modal
const membersData = <?php 
$members_json = array();
foreach ($members as $m) {
    $members_json[$m->ID] = array(
        'id' => $m->ID,
        'first_name' => $m->first_name,
        'middle_name' => get_user_meta($m->ID, 'sp_middle_name', true),
        'last_name' => $m->last_name,
        'email' => $m->user_email,
        'gender' => get_user_meta($m->ID, 'sp_gender', true) ?: 'male',
        'phone' => get_user_meta($m->ID, 'sp_phone', true),
        'whatsapp_number' => get_user_meta($m->ID, 'sp_whatsapp_number', true),
        'whatsapp_same_as_phone' => get_user_meta($m->ID, 'sp_whatsapp_same_as_phone', true),
        'job_or_college' => get_user_meta($m->ID, 'sp_job_or_college', true),
        'address_area' => get_user_meta($m->ID, 'sp_address_area', true),
        'address_street' => get_user_meta($m->ID, 'sp_address_street', true),
        'address_building' => get_user_meta($m->ID, 'sp_address_building', true),
        'address_floor' => get_user_meta($m->ID, 'sp_address_floor', true),
        'address_apartment' => get_user_meta($m->ID, 'sp_address_apartment', true),
        'address_landmark' => get_user_meta($m->ID, 'sp_address_landmark', true),
        'address_maps_url' => get_user_meta($m->ID, 'sp_address_maps_url', true),
        'home_address' => get_user_meta($m->ID, 'sp_home_address', true),
        'church_name' => get_user_meta($m->ID, 'sp_church_name', true),
        'confession_father' => get_user_meta($m->ID, 'sp_confession_father', true),
        'church_family' => get_user_meta($m->ID, 'sp_church_family', true),
        'church_family_servant' => get_user_meta($m->ID, 'sp_church_family_servant', true),
        'current_church_service' => get_user_meta($m->ID, 'sp_current_church_service', true),
        'facebook_link' => get_user_meta($m->ID, 'sp_facebook_link', true),
        'instagram_link' => get_user_meta($m->ID, 'sp_instagram_link', true),
    );
}
echo json_encode($members_json);
?>;

let currentMemberId = null;
let isEditMode = false;

document.querySelectorAll('.sp-view-member-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        currentMemberId = this.getAttribute('data-member-id');
        showMemberModal(currentMemberId, false);
    });
});

function showMemberModal(memberId, editMode) {
    const member = membersData[memberId];
    if (!member) return;
    
    isEditMode = editMode;
    const modal = document.getElementById('sp-member-modal');
    const modalTitle = document.getElementById('sp-member-modal-title');
    const viewContent = document.getElementById('sp-member-view-content');
    const editContent = document.getElementById('sp-member-edit-content');
    const editBtn = document.getElementById('sp-edit-member-btn');
    const saveBtn = document.getElementById('sp-save-member-btn');
    
    if (editMode) {
        modalTitle.textContent = '<?php _e('تعديل بيانات العضو', 'saint-porphyrius'); ?>';
        viewContent.style.display = 'none';
        editContent.style.display = 'block';
        editBtn.style.display = 'none';
        saveBtn.style.display = 'inline-flex';
        
        // Fill edit form
        fillEditForm(member);
    } else {
        modalTitle.textContent = '<?php _e('تفاصيل العضو', 'saint-porphyrius'); ?>';
        viewContent.style.display = 'block';
        editContent.style.display = 'none';
        editBtn.style.display = 'inline-flex';
        saveBtn.style.display = 'none';
        
        // Fill view content
        fillViewContent(member);
    }
    
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function fillViewContent(m) {
    const genderLabel = m.gender === 'female' ? 'أنثى' : 'ذكر';
    const whatsapp = m.whatsapp_same_as_phone ? m.phone : m.whatsapp_number;
    const address = m.address_area ? 
        `${m.address_area}، ${m.address_street}، عقار ${m.address_building}، دور ${m.address_floor}، شقة ${m.address_apartment}` + 
        (m.address_landmark ? ` (${m.address_landmark})` : '') 
        : m.home_address;
    
    let html = `
        <div class="sp-detail-section">
            <h4 class="sp-detail-title">👤 <?php _e('المعلومات الشخصية', 'saint-porphyrius'); ?></h4>
            <div class="sp-detail-row"><span><?php _e('الاسم', 'saint-porphyrius'); ?>:</span><strong>${m.first_name} ${m.middle_name} ${m.last_name}</strong></div>
            <div class="sp-detail-row"><span><?php _e('النوع', 'saint-porphyrius'); ?>:</span><strong>${genderLabel}</strong></div>
            <div class="sp-detail-row"><span><?php _e('البريد', 'saint-porphyrius'); ?>:</span><strong dir="ltr">${m.email}</strong></div>
            <div class="sp-detail-row"><span><?php _e('الهاتف', 'saint-porphyrius'); ?>:</span><strong dir="ltr">${m.phone || '-'}</strong></div>
            <div class="sp-detail-row"><span><?php _e('الواتساب', 'saint-porphyrius'); ?>:</span><strong dir="ltr">${whatsapp || '-'}</strong></div>
            <div class="sp-detail-row"><span><?php _e('الوظيفة/الكلية', 'saint-porphyrius'); ?>:</span><strong>${m.job_or_college || '-'}</strong></div>
        </div>
        <div class="sp-detail-section">
            <h4 class="sp-detail-title">📍 <?php _e('العنوان', 'saint-porphyrius'); ?></h4>
            <div class="sp-detail-row"><span><?php _e('العنوان', 'saint-porphyrius'); ?>:</span><strong>${address || '-'}</strong></div>
            ${m.address_maps_url ? `<div class="sp-detail-row"><span><?php _e('خرائط جوجل', 'saint-porphyrius'); ?>:</span><a href="${m.address_maps_url}" target="_blank" class="sp-link">🗺️ <?php _e('فتح', 'saint-porphyrius'); ?></a></div>` : ''}
        </div>
        <div class="sp-detail-section">
            <h4 class="sp-detail-title">⛪ <?php _e('الكنيسة', 'saint-porphyrius'); ?></h4>
            <div class="sp-detail-row"><span><?php _e('الكنيسة', 'saint-porphyrius'); ?>:</span><strong>${m.church_name || '-'}</strong></div>
            <div class="sp-detail-row"><span><?php _e('أب الاعتراف', 'saint-porphyrius'); ?>:</span><strong>${m.confession_father || '-'}</strong></div>
            <div class="sp-detail-row"><span><?php _e('الأسرة', 'saint-porphyrius'); ?>:</span><strong>${m.church_family || '-'}</strong></div>
            <div class="sp-detail-row"><span><?php _e('خادم الأسرة', 'saint-porphyrius'); ?>:</span><strong>${m.church_family_servant || '-'}</strong></div>
            <div class="sp-detail-row"><span><?php _e('الخدمة', 'saint-porphyrius'); ?>:</span><strong>${m.current_church_service || '-'}</strong></div>
        </div>
    `;
    
    if (m.facebook_link || m.instagram_link) {
        html += `<div class="sp-detail-section">
            <h4 class="sp-detail-title">🔗 <?php _e('التواصل', 'saint-porphyrius'); ?></h4>
            ${m.facebook_link ? `<div class="sp-detail-row"><a href="${m.facebook_link}" target="_blank" class="sp-link">فيسبوك</a></div>` : ''}
            ${m.instagram_link ? `<div class="sp-detail-row"><a href="${m.instagram_link}" target="_blank" class="sp-link">انستجرام</a></div>` : ''}
        </div>`;
    }
    
    document.getElementById('sp-member-view-content').innerHTML = html;
}

function fillEditForm(m) {
    document.getElementById('edit_member_id').value = m.id;
    document.getElementById('edit_first_name').value = m.first_name || '';
    document.getElementById('edit_middle_name').value = m.middle_name || '';
    document.getElementById('edit_last_name').value = m.last_name || '';
    document.getElementById('edit_gender').value = m.gender || 'male';
    document.getElementById('edit_phone').value = m.phone || '';
    document.getElementById('edit_whatsapp_same').checked = m.whatsapp_same_as_phone == '1';
    document.getElementById('edit_whatsapp_number').value = m.whatsapp_number || '';
    document.getElementById('edit_whatsapp_number').style.display = m.whatsapp_same_as_phone == '1' ? 'none' : 'block';
    document.getElementById('edit_job_or_college').value = m.job_or_college || '';
    document.getElementById('edit_address_area').value = m.address_area || '';
    document.getElementById('edit_address_street').value = m.address_street || '';
    document.getElementById('edit_address_building').value = m.address_building || '';
    document.getElementById('edit_address_floor').value = m.address_floor || '';
    document.getElementById('edit_address_apartment').value = m.address_apartment || '';
    document.getElementById('edit_address_landmark').value = m.address_landmark || '';
    document.getElementById('edit_address_maps_url').value = m.address_maps_url || '';
    document.getElementById('edit_church_name').value = m.church_name || '';
    document.getElementById('edit_confession_father').value = m.confession_father || '';
    document.getElementById('edit_church_family').value = m.church_family || '';
    document.getElementById('edit_church_family_servant').value = m.church_family_servant || '';
    document.getElementById('edit_current_church_service').value = m.current_church_service || '';
    document.getElementById('edit_facebook_link').value = m.facebook_link || '';
    document.getElementById('edit_instagram_link').value = m.instagram_link || '';
}

document.getElementById('edit_whatsapp_same').addEventListener('change', function() {
    document.getElementById('edit_whatsapp_number').style.display = this.checked ? 'none' : 'block';
});

function closeMemberModal() {
    document.getElementById('sp-member-modal').style.display = 'none';
    document.body.style.overflow = '';
    currentMemberId = null;
    isEditMode = false;
}

document.getElementById('sp-edit-member-btn').addEventListener('click', function() {
    showMemberModal(currentMemberId, true);
});

document.getElementById('sp-admin-edit-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const saveBtn = document.getElementById('sp-save-member-btn');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<span class="sp-spinner"></span> <?php _e('جاري الحفظ...', 'saint-porphyrius'); ?>';
    saveBtn.disabled = true;
    
    const formData = new FormData(this);
    formData.append('action', 'sp_admin_update_member');
    formData.append('nonce', '<?php echo wp_create_nonce('sp_admin_update_member'); ?>');
    
    jQuery.ajax({
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                window.location.reload();
            } else {
                alert(response.data.message || '<?php _e('حدث خطأ', 'saint-porphyrius'); ?>');
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
            }
        },
        error: function() {
            alert('<?php _e('حدث خطأ', 'saint-porphyrius'); ?>');
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        }
    });
});

document.getElementById('sp-member-modal').addEventListener('click', function(e) {
    if (e.target === this) closeMemberModal();
});
</script>

<!-- Member Details Modal -->
<div id="sp-member-modal" class="sp-modal" style="display: none;">
    <div class="sp-modal-overlay"></div>
    <div class="sp-modal-content" style="max-width: 550px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column;">
        <div class="sp-modal-header">
            <h2 id="sp-member-modal-title"><?php _e('تفاصيل العضو', 'saint-porphyrius'); ?></h2>
            <button type="button" class="sp-modal-close" onclick="closeMemberModal()">×</button>
        </div>
        <div class="sp-modal-body" style="overflow-y: auto; flex: 1;">
            <!-- View Content -->
            <div id="sp-member-view-content"></div>
            
            <!-- Edit Content -->
            <div id="sp-member-edit-content" style="display: none;">
                <form id="sp-admin-edit-form">
                    <input type="hidden" name="member_id" id="edit_member_id">
                    
                    <div class="sp-admin-edit-section">
                        <h4>👤 <?php _e('المعلومات الشخصية', 'saint-porphyrius'); ?></h4>
                        <div class="sp-form-row">
                            <div class="sp-form-group">
                                <label class="sp-form-label"><?php _e('الاسم الأول', 'saint-porphyrius'); ?></label>
                                <input type="text" name="first_name" id="edit_first_name" class="sp-form-input">
                            </div>
                            <div class="sp-form-group">
                                <label class="sp-form-label"><?php _e('الاسم الأوسط', 'saint-porphyrius'); ?></label>
                                <input type="text" name="middle_name" id="edit_middle_name" class="sp-form-input">
                            </div>
                        </div>
                        <div class="sp-form-row">
                            <div class="sp-form-group">
                                <label class="sp-form-label"><?php _e('اسم العائلة', 'saint-porphyrius'); ?></label>
                                <input type="text" name="last_name" id="edit_last_name" class="sp-form-input">
                            </div>
                            <div class="sp-form-group">
                                <label class="sp-form-label"><?php _e('النوع', 'saint-porphyrius'); ?></label>
                                <select name="gender" id="edit_gender" class="sp-form-input">
                                    <option value="male"><?php _e('ذكر', 'saint-porphyrius'); ?></option>
                                    <option value="female"><?php _e('أنثى', 'saint-porphyrius'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('رقم الهاتف', 'saint-porphyrius'); ?></label>
                            <input type="tel" name="phone" id="edit_phone" class="sp-form-input" dir="ltr">
                        </div>
                        <div class="sp-form-group">
                            <label class="sp-checkbox">
                                <input type="checkbox" name="whatsapp_same_as_phone" id="edit_whatsapp_same">
                                <span class="sp-checkbox-mark"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg></span>
                                <span class="sp-checkbox-text"><?php _e('رقم الواتساب نفس رقم الهاتف', 'saint-porphyrius'); ?></span>
                            </label>
                            <input type="tel" name="whatsapp_number" id="edit_whatsapp_number" class="sp-form-input" dir="ltr" style="margin-top: 8px;">
                        </div>
                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('الوظيفة / الكلية', 'saint-porphyrius'); ?></label>
                            <input type="text" name="job_or_college" id="edit_job_or_college" class="sp-form-input">
                        </div>
                    </div>
                    
                    <div class="sp-admin-edit-section">
                        <h4>📍 <?php _e('العنوان', 'saint-porphyrius'); ?></h4>
                        <div class="sp-form-row">
                            <div class="sp-form-group">
                                <label class="sp-form-label"><?php _e('المنطقة', 'saint-porphyrius'); ?></label>
                                <input type="text" name="address_area" id="edit_address_area" class="sp-form-input">
                            </div>
                            <div class="sp-form-group">
                                <label class="sp-form-label"><?php _e('الشارع', 'saint-porphyrius'); ?></label>
                                <input type="text" name="address_street" id="edit_address_street" class="sp-form-input">
                            </div>
                        </div>
                        <div class="sp-form-row" style="grid-template-columns: 1fr 1fr 1fr;">
                            <div class="sp-form-group">
                                <label class="sp-form-label"><?php _e('العقار', 'saint-porphyrius'); ?></label>
                                <input type="text" name="address_building" id="edit_address_building" class="sp-form-input">
                            </div>
                            <div class="sp-form-group">
                                <label class="sp-form-label"><?php _e('الدور', 'saint-porphyrius'); ?></label>
                                <input type="text" name="address_floor" id="edit_address_floor" class="sp-form-input">
                            </div>
                            <div class="sp-form-group">
                                <label class="sp-form-label"><?php _e('الشقة', 'saint-porphyrius'); ?></label>
                                <input type="text" name="address_apartment" id="edit_address_apartment" class="sp-form-input">
                            </div>
                        </div>
                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('علامة مميزة', 'saint-porphyrius'); ?></label>
                            <input type="text" name="address_landmark" id="edit_address_landmark" class="sp-form-input">
                        </div>
                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('خرائط جوجل', 'saint-porphyrius'); ?></label>
                            <input type="url" name="address_maps_url" id="edit_address_maps_url" class="sp-form-input" dir="ltr">
                        </div>
                    </div>
                    
                    <div class="sp-admin-edit-section">
                        <h4>⛪ <?php _e('الكنيسة', 'saint-porphyrius'); ?></h4>
                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('اسم الكنيسة', 'saint-porphyrius'); ?></label>
                            <input type="text" name="church_name" id="edit_church_name" class="sp-form-input">
                        </div>
                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('أب الاعتراف', 'saint-porphyrius'); ?></label>
                            <input type="text" name="confession_father" id="edit_confession_father" class="sp-form-input">
                        </div>
                        <div class="sp-form-row">
                            <div class="sp-form-group">
                                <label class="sp-form-label"><?php _e('الأسرة', 'saint-porphyrius'); ?></label>
                                <input type="text" name="church_family" id="edit_church_family" class="sp-form-input">
                            </div>
                            <div class="sp-form-group">
                                <label class="sp-form-label"><?php _e('خادم الأسرة', 'saint-porphyrius'); ?></label>
                                <input type="text" name="church_family_servant" id="edit_church_family_servant" class="sp-form-input">
                            </div>
                        </div>
                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('الخدمة الحالية', 'saint-porphyrius'); ?></label>
                            <textarea name="current_church_service" id="edit_current_church_service" class="sp-form-textarea" rows="2"></textarea>
                        </div>
                    </div>
                    
                    <div class="sp-admin-edit-section">
                        <h4>🔗 <?php _e('التواصل', 'saint-porphyrius'); ?></h4>
                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('فيسبوك', 'saint-porphyrius'); ?></label>
                            <input type="url" name="facebook_link" id="edit_facebook_link" class="sp-form-input" dir="ltr">
                        </div>
                        <div class="sp-form-group">
                            <label class="sp-form-label"><?php _e('انستجرام', 'saint-porphyrius'); ?></label>
                            <input type="url" name="instagram_link" id="edit_instagram_link" class="sp-form-input" dir="ltr">
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="sp-modal-footer">
            <button type="button" class="sp-btn sp-btn-secondary" onclick="closeMemberModal()" style="flex: 1;">
                <?php _e('إغلاق', 'saint-porphyrius'); ?>
            </button>
            <button type="button" class="sp-btn sp-btn-primary" id="sp-edit-member-btn" style="flex: 1;">
                ✏️ <?php _e('تعديل', 'saint-porphyrius'); ?>
            </button>
            <button type="submit" form="sp-admin-edit-form" class="sp-btn sp-btn-primary" id="sp-save-member-btn" style="flex: 1; display: none;">
                💾 <?php _e('حفظ', 'saint-porphyrius'); ?>
            </button>
        </div>
    </div>
</div>

<style>
.sp-detail-section {
    margin-bottom: var(--sp-space-lg);
    padding-bottom: var(--sp-space-md);
    border-bottom: 1px solid var(--sp-border-light);
}
.sp-detail-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}
.sp-detail-title {
    font-size: var(--sp-font-size-sm);
    color: var(--sp-text-secondary);
    margin-bottom: var(--sp-space-sm);
}
.sp-detail-row {
    display: flex;
    justify-content: space-between;
    padding: var(--sp-space-xs) 0;
    font-size: var(--sp-font-size-sm);
}
.sp-detail-row span {
    color: var(--sp-text-secondary);
}
.sp-detail-row strong {
    color: var(--sp-text-primary);
    text-align: left;
}
.sp-admin-edit-section {
    margin-bottom: var(--sp-space-lg);
    padding-bottom: var(--sp-space-md);
    border-bottom: 1px solid var(--sp-border-light);
}
.sp-admin-edit-section h4 {
    font-size: var(--sp-font-size-sm);
    color: var(--sp-text-secondary);
    margin-bottom: var(--sp-space-md);
}
.sp-member-gender {
    font-size: 14px;
    margin-right: 4px;
}
.sp-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--sp-space-md);
}
</style>
