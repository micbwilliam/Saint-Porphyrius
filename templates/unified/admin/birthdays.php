<?php
/**
 * Saint Porphyrius - Admin Upcoming Birthdays
 * Shows members with birthdays in the next 30 days with WhatsApp links
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get all members with birthdays
$args = array(
    'role__in'   => array('sp_member', 'sp_church_admin', 'administrator'),
    'meta_key'   => 'sp_birth_date',
    'meta_query' => array(
        array(
            'key'     => 'sp_birth_date',
            'value'   => '',
            'compare' => '!=',
        ),
    ),
    'number'     => -1,
);

$users = get_users($args);

$today = new DateTime('now', wp_timezone());
$upcoming = array();

foreach ($users as $user) {
    $birth_date_str = get_user_meta($user->ID, 'sp_birth_date', true);
    if (empty($birth_date_str)) {
        continue;
    }

    try {
        $birth = new DateTime($birth_date_str);
    } catch (Exception $e) {
        continue;
    }

    // Build this year's birthday
    $birthday_this_year = new DateTime($today->format('Y') . '-' . $birth->format('m-d'));

    // If the birthday already passed this year, check next year
    $diff = (int) $today->diff($birthday_this_year)->format('%r%a');
    if ($diff < 0) {
        $birthday_this_year->modify('+1 year');
        $diff = (int) $today->diff($birthday_this_year)->format('%r%a');
    }

    if ($diff >= 0 && $diff <= 30) {
        $phone = get_user_meta($user->ID, 'sp_phone', true);
        $age = (int) $birthday_this_year->format('Y') - (int) $birth->format('Y');

        $upcoming[] = array(
            'user'          => $user,
            'phone'         => $phone,
            'birth_date'    => $birth,
            'birthday'      => $birthday_this_year,
            'days_until'    => $diff,
            'age'           => $age,
            'display_name'  => $user->display_name,
        );
    }
}

// Sort by days until birthday
usort($upcoming, function ($a, $b) {
    return $a['days_until'] - $b['days_until'];
});

/**
 * Format phone for WhatsApp link (Egyptian format)
 */
function sp_format_whatsapp_phone($phone) {
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    // If starts with 0, replace with Egypt code
    if (strpos($phone, '0') === 0) {
        $phone = '+2' . $phone;
    }
    // If no + prefix, assume Egypt
    if (strpos($phone, '+') !== 0) {
        $phone = '+2' . $phone;
    }
    // Remove + for WhatsApp URL
    return ltrim($phone, '+');
}

?>

<div class="sp-unified-header sp-admin-header">
    <a href="<?php echo esc_url(home_url('/app/admin')); ?>" class="sp-header-back">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="9 18 15 12 9 6"></polyline>
        </svg>
    </a>
    <h1 class="sp-header-title"><?php _e('أعياد الميلاد القادمة', 'saint-porphyrius'); ?></h1>
</div>

<main class="sp-page-content sp-admin-content">

    <div class="sp-admin-summary" style="margin-bottom: 16px; padding: 16px; background: linear-gradient(135deg, #FDF2F8, #FCE7F3); border-radius: 16px; text-align: center;">
        <div style="font-size: 2rem; margin-bottom: 4px;">🎂</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #BE185D;">
            <?php echo esc_html(count($upcoming)); ?>
        </div>
        <div style="font-size: 0.85rem; color: #9D174D;">
            <?php _e('عيد ميلاد خلال ٣٠ يوم', 'saint-porphyrius'); ?>
        </div>
    </div>

    <?php if (empty($upcoming)): ?>
        <div class="sp-empty-state">
            <div class="sp-empty-state-icon">🎈</div>
            <h3><?php _e('لا توجد أعياد ميلاد قادمة', 'saint-porphyrius'); ?></h3>
            <p><?php _e('لا يوجد أعضاء لديهم أعياد ميلاد خلال الـ ٣٠ يوم القادمة', 'saint-porphyrius'); ?></p>
        </div>
    <?php else: ?>
        <div class="sp-birthday-list" style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($upcoming as $member):
                $is_today = ($member['days_until'] === 0);
                $wa_phone = sp_format_whatsapp_phone($member['phone']);
                $wa_message = 'كل سنة وانت طيب يا ' . $member['display_name'] . '! 🎂🎉 ربنا يبارك حياتك';
                $wa_url = 'https://wa.me/' . $wa_phone . '?text=' . rawurlencode($wa_message);
            ?>
                <div class="sp-card" style="padding: 16px; border-radius: 16px; <?php echo $is_today ? 'border: 2px solid #EC4899; background: #FDF2F8;' : ''; ?>">
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;">
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                <?php if ($is_today): ?>
                                    <span style="background: #EC4899; color: white; padding: 2px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
                                        <?php _e('🎉 النهاردة!', 'saint-porphyrius'); ?>
                                    </span>
                                <?php elseif ($member['days_until'] === 1): ?>
                                    <span style="background: #F59E0B; color: white; padding: 2px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
                                        <?php _e('بكرة', 'saint-porphyrius'); ?>
                                    </span>
                                <?php elseif ($member['days_until'] <= 7): ?>
                                    <span style="background: #8B5CF6; color: white; padding: 2px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
                                        <?php printf(__('بعد %d أيام', 'saint-porphyrius'), $member['days_until']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="background: #6B7280; color: white; padding: 2px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
                                        <?php printf(__('بعد %d يوم', 'saint-porphyrius'), $member['days_until']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h4 style="margin: 0 0 4px 0; font-size: 1rem; font-weight: 600;">
                                <?php echo esc_html($member['display_name']); ?>
                            </h4>
                            <div style="font-size: 0.85rem; color: #6B7280; display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
                                <span>📅 <?php echo esc_html($member['birth_date']->format('d/m/Y')); ?></span>
                                <span>•</span>
                                <span><?php printf(__('هيكمل %d سنة', 'saint-porphyrius'), $member['age']); ?></span>
                            </div>
                            <?php if (!empty($member['phone'])): ?>
                                <div style="font-size: 0.85rem; color: #6B7280; margin-top: 4px; direction: ltr; text-align: right;">
                                    📱 <?php echo esc_html($member['phone']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 8px; flex-shrink: 0;">
                            <?php if (!empty($member['phone'])): ?>
                                <a href="<?php echo esc_url($wa_url); ?>" target="_blank" rel="noopener noreferrer"
                                   style="display: flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 16px; background: #25D366; color: white; border-radius: 12px; text-decoration: none; font-size: 0.85rem; font-weight: 600; white-space: nowrap;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                    </svg>
                                    <?php _e('واتساب', 'saint-porphyrius'); ?>
                                </a>
                                <a href="tel:<?php echo esc_attr($member['phone']); ?>"
                                   style="display: flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 16px; background: #3B82F6; color: white; border-radius: 12px; text-decoration: none; font-size: 0.85rem; font-weight: 600; white-space: nowrap;">
                                    📞 <?php _e('اتصال', 'saint-porphyrius'); ?>
                                </a>
                            <?php else: ?>
                                <span style="padding: 10px 16px; background: #F3F4F6; color: #9CA3AF; border-radius: 12px; font-size: 0.8rem; text-align: center;">
                                    <?php _e('لا يوجد رقم', 'saint-porphyrius'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</main>
