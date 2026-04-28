<?php
/**
 * Saint Porphyrius - Admin Dashboard (Mobile)
 * Reorganized: Quick Actions + Alerts strip + Search + Collapsible categories.
 * Every existing admin page is preserved.
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// --- Stats / counters ---------------------------------------------------
$pending_table = $wpdb->prefix . 'sp_pending_users';
$pending_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $pending_table WHERE status = 'pending'");

$members_count = count(get_users(array(
    'role__in' => array('sp_member', 'sp_church_admin'),
)));

$events_handler   = SP_Events::get_instance();
$upcoming_events  = $events_handler->get_upcoming(5);
$events_count     = count($upcoming_events);

$excuses_handler  = SP_Excuses::get_instance();
$pending_excuses  = (int) $excuses_handler->count_pending();

$appeals_handler  = SP_Appeals::get_instance();
$pending_appeals  = (int) $appeals_handler->count_pending();

$points_handler   = SP_Points::get_instance();
$stats            = $points_handler->get_summary_stats();

$forbidden_handler = SP_Forbidden::get_instance();
$forbidden_counts  = $forbidden_handler->count_by_status();
$red_cards         = (int) ($forbidden_counts['red_card'] ?? 0);
$yellow_cards      = (int) ($forbidden_counts['forbidden'] ?? 0);

$push_handler           = SP_Notifications::get_instance();
$push_subscriber_count  = (int) $push_handler->get_subscriber_count();

// --- Menu definition ----------------------------------------------------
// Single source of truth. Add/remove items here only.
$alert_total = $pending_count + $pending_excuses + $pending_appeals + $red_cards;

$menu_groups = array(
    'daily' => array(
        'title' => __('العمليات اليومية', 'saint-porphyrius'),
        'icon'  => '📋',
        'open'  => true,
        'items' => array(
            array('url' => '/app/admin/qr-scanner',  'icon' => '📱', 'bg' => '#DBEAFE', 'color' => '#2563EB', 'title' => __('ماسح QR للحضور', 'saint-porphyrius'),    'desc' => __('مسح رموز QR لتسجيل الحضور بسرعة', 'saint-porphyrius'), 'featured' => true),
            array('url' => '/app/admin/attendance',  'icon' => '✅', 'bg' => '#FEE2E2', 'color' => '#DC2626', 'title' => __('الحضور', 'saint-porphyrius'),             'desc' => __('تسجيل حضور الأعضاء', 'saint-porphyrius')),
            array('url' => '/app/admin/pending',     'icon' => '⏳', 'bg' => '#FEF3C7', 'color' => '#D97706', 'title' => __('الموافقات المعلقة', 'saint-porphyrius'),  'desc' => __('مراجعة طلبات التسجيل الجديدة', 'saint-porphyrius'), 'badge' => $pending_count),
            array('url' => '/app/admin/excuses',     'icon' => '📝', 'bg' => '#EDE9FE', 'color' => '#7C3AED', 'title' => __('الاعتذارات', 'saint-porphyrius'),         'desc' => __('مراجعة طلبات الاعتذار', 'saint-porphyrius'), 'badge' => $pending_excuses),
            array('url' => '/app/admin/appeals',     'icon' => '📋', 'bg' => '#FEF3C7', 'color' => '#B45309', 'title' => __('طلبات نقاط الفعاليات', 'saint-porphyrius'), 'desc' => __('مراجعة طلبات الأعضاء للنقاط', 'saint-porphyrius'), 'badge' => $pending_appeals),
        ),
    ),
    'members' => array(
        'title' => __('الأعضاء', 'saint-porphyrius'),
        'icon'  => '👥',
        'items' => array(
            array('url' => '/app/admin/members',          'icon' => '👥', 'bg' => '#D1FAE5', 'color' => '#059669', 'title' => __('الأعضاء', 'saint-porphyrius'),            'desc' => __('عرض وإدارة أعضاء الأسرة', 'saint-porphyrius')),
            array('url' => '/app/admin/birthdays',        'icon' => '🎂', 'bg' => '#FDF2F8', 'color' => '#BE185D', 'title' => __('أعياد الميلاد', 'saint-porphyrius'),       'desc' => __('أعياد الميلاد القادمة خلال ٣٠ يوم', 'saint-porphyrius')),
            array('url' => '/app/admin/forbidden',        'icon' => '⛔', 'bg' => '#FEE2E2', 'color' => '#B91C1C', 'title' => __('نظام المحروم', 'saint-porphyrius'),        'desc' => __('إدارة الحرمان والكروت', 'saint-porphyrius'), 'badge' => $red_cards, 'badge_class' => 'danger'),
            array('url' => '/app/admin/social-profiles',  'icon' => '👥', 'bg' => '#FDF2F8', 'color' => '#DB2777', 'title' => __('الملفات الاجتماعية', 'saint-porphyrius'),  'desc' => __('تفعيل/تعطيل الملفات الاجتماعية للأعضاء', 'saint-porphyrius')),
        ),
    ),
    'events' => array(
        'title' => __('الفعاليات والباصات', 'saint-porphyrius'),
        'icon'  => '📅',
        'items' => array(
            array('url' => '/app/admin/events',         'icon' => '📅', 'bg' => '#DBEAFE', 'color' => '#2563EB', 'title' => __('الفعاليات', 'saint-porphyrius'),          'desc' => __('إنشاء وإدارة الفعاليات', 'saint-porphyrius')),
            array('url' => '/app/admin/event-types',    'icon' => '📋', 'bg' => '#E0E7FF', 'color' => '#4F46E5', 'title' => __('أنواع الفعاليات', 'saint-porphyrius'),    'desc' => __('إدارة أنواع الفعاليات ونقاطها', 'saint-porphyrius')),
            array('url' => '/app/admin/bus-templates',  'icon' => '🚌', 'bg' => '#DBEAFE', 'color' => '#2563EB', 'title' => __('أنواع الباصات', 'saint-porphyrius'),      'desc' => __('إدارة أنواع وأحجام الباصات', 'saint-porphyrius')),
        ),
    ),
    'points' => array(
        'title' => __('النقاط والمكافآت', 'saint-porphyrius'),
        'icon'  => '⭐',
        'items' => array(
            array('url' => '/app/admin/points',          'icon' => '⭐', 'bg' => '#FEF3C7', 'color' => '#B45309', 'title' => __('النقاط', 'saint-porphyrius'),               'desc' => __('إدارة نقاط الأعضاء', 'saint-porphyrius')),
            array('url' => '/app/admin/quizzes',         'icon' => '📝', 'bg' => '#EDE9FE', 'color' => '#7C3AED', 'title' => __('الاختبارات المسيحية', 'saint-porphyrius'),  'desc' => __('إدارة المحتوى والأسئلة والتصنيفات', 'saint-porphyrius')),
            array('url' => '/app/admin/birthday-gifts',  'icon' => '🎁', 'bg' => '#FFFBEB', 'color' => '#D97706', 'title' => __('هدايا عيد الميلاد', 'saint-porphyrius'),    'desc' => __('إدارة الهدايا التي يختار منها الأعضاء', 'saint-porphyrius')),
        ),
    ),
    'settings' => array(
        'title' => __('الإعدادات', 'saint-porphyrius'),
        'icon'  => '⚙️',
        'items' => array(
            array('url' => '/app/admin/notifications',   'icon' => '🔔', 'bg' => '#FEF3C7', 'color' => '#D97706', 'title' => __('الإشعارات', 'saint-porphyrius'),               'desc' => __('إرسال إشعارات وإدارة المشتركين', 'saint-porphyrius'), 'badge' => $push_subscriber_count, 'badge_class' => 'info'),
            array('url' => '/app/admin/gamification',    'icon' => '🎁', 'bg' => '#FCE7F3', 'color' => '#DB2777', 'title' => __('إعدادات المكافآت', 'saint-porphyrius'),         'desc' => __('ضبط نقاط عيد الميلاد والملف الشخصي', 'saint-porphyrius')),
            array('url' => '/app/admin/point-sharing',   'icon' => '💰', 'bg' => '#FEF3C7', 'color' => '#D97706', 'title' => __('إعدادات مشاركة النقاط', 'saint-porphyrius'),    'desc' => __('ضبط رسوم مشاركة النقاط بين الأعضاء', 'saint-porphyrius')),
            array('url' => '/app/admin/pwa-settings',    'icon' => '📱', 'bg' => '#EDE9FE', 'color' => '#7C3AED', 'title' => __('إعدادات التطبيق', 'saint-porphyrius'),          'desc' => __('تغيير اسم التطبيق والألوان والمظهر', 'saint-porphyrius')),
            array('url' => '/app/admin/custom-texts',    'icon' => '✏️', 'bg' => '#D1FAE5', 'color' => '#059669', 'title' => __('النصوص المخصصة', 'saint-porphyrius'),           'desc' => __('تعديل النصوص الظاهرة في البطاقات والإشعارات', 'saint-porphyrius')),
        ),
    ),
);

// Quick actions = a curated row of the most-used daily tasks.
$quick_actions = array(
    array('url' => '/app/admin/qr-scanner', 'icon' => '📱', 'label' => __('ماسح QR', 'saint-porphyrius')),
    array('url' => '/app/admin/attendance', 'icon' => '✅', 'label' => __('الحضور', 'saint-porphyrius')),
    array('url' => '/app/admin/pending',    'icon' => '⏳', 'label' => __('الموافقات', 'saint-porphyrius'), 'badge' => $pending_count),
    array('url' => '/app/admin/excuses',    'icon' => '📝', 'label' => __('الاعتذارات', 'saint-porphyrius'), 'badge' => $pending_excuses),
);

// Helper to render chevron
$chevron = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>';
?>

<!-- Admin Header -->
<div class="sp-unified-header sp-admin-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-header-back" aria-label="<?php esc_attr_e('رجوع', 'saint-porphyrius'); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h1 class="sp-header-title"><?php _e('لوحة الإدارة', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-spacer"></div>
    </div>
</div>

<main class="sp-page-content sp-admin-content sp-admin-dashboard-v2">

    <!-- Quick Actions -->
    <div class="sp-admin-quick-actions" role="navigation" aria-label="<?php esc_attr_e('إجراءات سريعة', 'saint-porphyrius'); ?>">
        <?php foreach ($quick_actions as $qa): ?>
            <a href="<?php echo esc_url(home_url($qa['url'])); ?>" class="sp-quick-action">
                <span class="sp-quick-action-icon"><?php echo esc_html($qa['icon']); ?></span>
                <span class="sp-quick-action-label"><?php echo esc_html($qa['label']); ?></span>
                <?php if (!empty($qa['badge']) && $qa['badge'] > 0): ?>
                    <span class="sp-quick-action-badge"><?php echo esc_html($qa['badge']); ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Alerts Strip (only when there's something to act on) -->
    <?php if ($alert_total > 0): ?>
    <div class="sp-admin-alerts" role="status">
        <span class="sp-admin-alerts-label">⚠️ <?php _e('بحاجة لاهتمامك:', 'saint-porphyrius'); ?></span>
        <div class="sp-admin-alerts-chips">
            <?php if ($pending_count > 0): ?>
                <a href="<?php echo home_url('/app/admin/pending'); ?>" class="sp-alert-chip">
                    <?php printf(_n('%d طلب تسجيل', '%d طلبات تسجيل', $pending_count, 'saint-porphyrius'), $pending_count); ?>
                </a>
            <?php endif; ?>
            <?php if ($pending_excuses > 0): ?>
                <a href="<?php echo home_url('/app/admin/excuses'); ?>" class="sp-alert-chip">
                    <?php printf(_n('%d اعتذار', '%d اعتذارات', $pending_excuses, 'saint-porphyrius'), $pending_excuses); ?>
                </a>
            <?php endif; ?>
            <?php if ($pending_appeals > 0): ?>
                <a href="<?php echo home_url('/app/admin/appeals'); ?>" class="sp-alert-chip">
                    <?php printf(_n('%d طلب نقاط', '%d طلبات نقاط', $pending_appeals, 'saint-porphyrius'), $pending_appeals); ?>
                </a>
            <?php endif; ?>
            <?php if ($red_cards > 0): ?>
                <a href="<?php echo home_url('/app/admin/forbidden'); ?>" class="sp-alert-chip danger">
                    <?php printf(_n('%d كارت أحمر', '%d كروت حمراء', $red_cards, 'saint-porphyrius'), $red_cards); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Search -->
    <div class="sp-admin-search-wrap">
        <svg class="sp-admin-search-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input
            type="search"
            id="sp-admin-search"
            class="sp-admin-search-input"
            placeholder="<?php esc_attr_e('ابحث عن صفحة... (مثال: نقاط، حضور، باص)', 'saint-porphyrius'); ?>"
            autocomplete="off"
        />
        <button type="button" class="sp-admin-search-clear" id="sp-admin-search-clear" aria-label="<?php esc_attr_e('مسح', 'saint-porphyrius'); ?>" hidden>×</button>
    </div>
    <div class="sp-admin-search-empty" id="sp-admin-search-empty" hidden>
        <?php _e('لا توجد نتائج مطابقة', 'saint-porphyrius'); ?>
    </div>

    <!-- Categorized Menu -->
    <div class="sp-admin-categories">
        <?php foreach ($menu_groups as $group_key => $group): ?>
            <?php
            $item_count   = count($group['items']);
            $is_open_attr = !empty($group['open']) ? 'open' : '';
            ?>
            <details class="sp-admin-category" data-group="<?php echo esc_attr($group_key); ?>" <?php echo $is_open_attr; ?>>
                <summary class="sp-admin-category-header">
                    <span class="sp-admin-category-icon"><?php echo esc_html($group['icon']); ?></span>
                    <span class="sp-admin-category-title"><?php echo esc_html($group['title']); ?></span>
                    <span class="sp-admin-category-count"><?php echo esc_html($item_count); ?></span>
                    <svg class="sp-admin-category-chevron" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </summary>
                <div class="sp-admin-category-body">
                    <?php foreach ($group['items'] as $item): ?>
                        <?php
                        $badge       = isset($item['badge']) ? (int) $item['badge'] : 0;
                        $badge_class = isset($item['badge_class']) ? $item['badge_class'] : '';
                        $featured    = !empty($item['featured']);
                        $haystack    = $item['title'] . ' ' . $item['desc'];
                        $classes     = 'sp-admin-menu-item';
                        if ($featured) {
                            $classes .= ' featured';
                        }
                        if ($badge > 0 && $badge_class !== 'info') {
                            $classes .= ' has-alert';
                        }
                        ?>
                        <a href="<?php echo esc_url(home_url($item['url'])); ?>"
                           class="<?php echo esc_attr($classes); ?>"
                           data-search="<?php echo esc_attr(mb_strtolower($haystack)); ?>">
                            <div class="sp-admin-menu-icon" style="background: <?php echo esc_attr($item['bg']); ?>; color: <?php echo esc_attr($item['color']); ?>;">
                                <?php echo esc_html($item['icon']); ?>
                            </div>
                            <div class="sp-admin-menu-content">
                                <h4><?php echo esc_html($item['title']); ?></h4>
                                <p><?php echo esc_html($item['desc']); ?></p>
                            </div>
                            <?php if ($badge > 0): ?>
                                <span class="sp-admin-stat-badge <?php echo esc_attr($badge_class); ?>"><?php echo esc_html($badge); ?></span>
                            <?php endif; ?>
                            <?php echo $chevron; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </details>
        <?php endforeach; ?>
    </div>

    <!-- Upcoming Events Preview -->
    <?php if (!empty($upcoming_events)): ?>
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title"><?php _e('الفعاليات القادمة', 'saint-porphyrius'); ?></h3>
            <a href="<?php echo home_url('/app/admin/events'); ?>" class="sp-section-link"><?php _e('عرض الكل', 'saint-porphyrius'); ?></a>
        </div>
        <div class="sp-admin-events-preview">
            <?php foreach (array_slice($upcoming_events, 0, 3) as $event): ?>
                <a href="<?php echo home_url('/app/admin/attendance?event_id=' . $event->id); ?>" class="sp-admin-event-card">
                    <div class="sp-admin-event-date">
                        <span class="day"><?php echo esc_html(date_i18n('j', strtotime($event->event_date))); ?></span>
                        <span class="month"><?php echo esc_html(date_i18n('M', strtotime($event->event_date))); ?></span>
                    </div>
                    <div class="sp-admin-event-info">
                        <div class="sp-admin-event-type" style="color: <?php echo esc_attr($event->type_color); ?>;">
                            <?php echo esc_html($event->type_icon . ' ' . $event->type_name_ar); ?>
                        </div>
                        <h4><?php echo esc_html($event->title_ar); ?></h4>
                        <span class="sp-admin-event-time"><?php echo esc_html($event->start_time); ?></span>
                    </div>
                    <div class="sp-admin-event-action">
                        <?php _e('تسجيل', 'saint-porphyrius'); ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Points Summary -->
    <div class="sp-section">
        <div class="sp-section-header">
            <h3 class="sp-section-title"><?php _e('ملخص النقاط', 'saint-porphyrius'); ?></h3>
        </div>
        <div class="sp-admin-points-summary">
            <div class="sp-admin-points-item">
                <span class="sp-admin-points-label"><?php _e('إجمالي المكافآت', 'saint-porphyrius'); ?></span>
                <span class="sp-admin-points-value positive">+<?php echo esc_html($stats->total_awarded ?? 0); ?></span>
            </div>
            <div class="sp-admin-points-item">
                <span class="sp-admin-points-label"><?php _e('إجمالي الخصومات', 'saint-porphyrius'); ?></span>
                <span class="sp-admin-points-value negative"><?php echo esc_html($stats->total_penalties ?? 0); ?></span>
            </div>
            <div class="sp-admin-points-item">
                <span class="sp-admin-points-label"><?php _e('أعضاء لديهم نقاط', 'saint-porphyrius'); ?></span>
                <span class="sp-admin-points-value"><?php echo esc_html($stats->members_with_points ?? 0); ?></span>
            </div>
        </div>
    </div>

    <!-- Back to App -->
    <div class="sp-admin-back-link">
        <a href="<?php echo home_url('/app/dashboard'); ?>" class="sp-btn sp-btn-outline">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            <?php _e('العودة للتطبيق', 'saint-porphyrius'); ?>
        </a>
    </div>
</main>

<script>
(function () {
    'use strict';

    var STORAGE_KEY = 'sp_admin_categories_state_v1';

    // --- Persist accordion open/closed state ----------------------------
    var categories = document.querySelectorAll('.sp-admin-category');
    var savedState = {};
    try {
        savedState = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
    } catch (e) {
        savedState = {};
    }

    categories.forEach(function (cat) {
        var key = cat.getAttribute('data-group');
        if (Object.prototype.hasOwnProperty.call(savedState, key)) {
            cat.open = !!savedState[key];
        }
        cat.addEventListener('toggle', function () {
            savedState[key] = cat.open;
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(savedState));
            } catch (e) { /* ignore quota errors */ }
        });
    });

    // --- Search filter --------------------------------------------------
    var input = document.getElementById('sp-admin-search');
    var clearBtn = document.getElementById('sp-admin-search-clear');
    var emptyState = document.getElementById('sp-admin-search-empty');
    if (!input) { return; }

    var allItems = document.querySelectorAll('.sp-admin-menu-item[data-search]');

    function normalize(s) {
        return (s || '').toString().toLowerCase().trim();
    }

    function applyFilter() {
        var q = normalize(input.value);
        clearBtn.hidden = q.length === 0;

        if (!q) {
            // Restore: show all, restore saved accordion state
            allItems.forEach(function (el) { el.style.display = ''; });
            categories.forEach(function (cat) {
                var key = cat.getAttribute('data-group');
                if (Object.prototype.hasOwnProperty.call(savedState, key)) {
                    cat.open = !!savedState[key];
                } else {
                    cat.open = cat.hasAttribute('data-default-open');
                }
                cat.style.display = '';
            });
            emptyState.hidden = true;
            return;
        }

        var anyMatch = false;
        categories.forEach(function (cat) {
            var items = cat.querySelectorAll('.sp-admin-menu-item[data-search]');
            var visible = 0;
            items.forEach(function (el) {
                var hay = el.getAttribute('data-search') || '';
                if (hay.indexOf(q) !== -1) {
                    el.style.display = '';
                    visible++;
                } else {
                    el.style.display = 'none';
                }
            });
            if (visible > 0) {
                cat.open = true;
                cat.style.display = '';
                anyMatch = true;
            } else {
                cat.style.display = 'none';
            }
        });
        emptyState.hidden = anyMatch;
    }

    input.addEventListener('input', applyFilter);
    clearBtn.addEventListener('click', function () {
        input.value = '';
        applyFilter();
        input.focus();
    });
})();
</script>
