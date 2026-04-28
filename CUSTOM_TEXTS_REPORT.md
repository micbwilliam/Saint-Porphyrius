# Custom Texts System — Full Codebase Audit & Implementation Plan

> **Date**: April 28, 2026  
> **Purpose**: Audit all hardcoded user-facing texts in the front-end, document gender/variable patterns, and plan a centralized admin-editable text system.

---

## Table of Contents

1. [Template Inventory](#1-template-inventory)
2. [Gender-Based Text Patterns](#2-gender-based-text-patterns)
3. [Variable-Based Text Patterns](#3-variable-based-text-patterns)
4. [Card & Component Structures](#4-card--component-structures)
5. [Admin Page Patterns](#5-admin-page-patterns)
6. [Route System](#6-route-system)
7. [AJAX Architecture](#7-ajax-architecture)
8. [CSS Design System](#8-css-design-system)
9. [All Customizable Text Strings (Registry)](#9-all-customizable-text-strings-registry)
10. [Implementation Plan](#10-implementation-plan)

---

## 1. Template Inventory

### 1.1 User-Facing Templates (`templates/unified/`)

| File | Purpose | Key Cards/Components |
|------|---------|---------------------|
| `dashboard.php` | Main user dashboard after login | Hero card, birthday card, profile completion card, story quiz card, service instructions card, discipline status card, quick stats, quick actions, admin banner |
| `profile.php` | View/edit user profile | Profile info card, edit form with gender select |
| `events.php` | Events listing (main/upcoming/past) | Event cards with forbidden overlay, draft badge |
| `event-single.php` | Single event detail | Event hero, info cards (date/time/location), points cards, description, bus booking, expected attendance |
| `points.php` | User points history | Points balance card, transaction list |
| `leaderboard.php` | Top members by points | Ranked member cards, empty state |
| `community.php` | All church members | Member cards with gender icons |
| `social-profile.php` | Public member profile | Profile hero, stats, gender icon |
| `share-points.php` | Point sharing between members | Balance card, member search, fee info |
| `quizzes.php` | Christian quiz system | Quiz cards, question UI, results |
| `notifications.php` | In-app notification inbox | Notification cards with time ago |
| `appeals.php` | Appeal for event points | Appeal form, event selector |
| `saint-story.php` | Saint Porphyrius biography | Story content, quiz at end |
| `service-instructions.php` | Service system instructions | Instructions content, quiz at end |

### 1.2 Admin Templates (`templates/unified/admin/`)

| File | Purpose |
|------|---------|
| `dashboard.php` | Admin dashboard with stats grid & menu |
| `pending.php` | Approve/reject pending registrations |
| `members.php` | Manage all members (view, edit, block, delete) |
| `events.php` | Create/edit/delete events |
| `event-types.php` | Manage event type templates |
| `attendance.php` | Record attendance for events |
| `excuses.php` | Review excuse submissions |
| `points.php` | Manage member points |
| `forbidden.php` | Discipline system (yellow/red cards) |
| `qr-scanner.php` | QR code attendance scanner |
| `gamification.php` | Gamification settings (points values, toggles) |
| `point-sharing.php` | Point sharing fee settings |
| `quizzes.php` | Quiz management (categories, content, AI generation) |
| `notifications.php` | Push notification sending |
| `pwa-settings.php` | PWA manifest settings (app name, colors, icons) |
| `social-profiles.php` | Social profile management |
| `appeals.php` | Review point appeals |
| `birthdays.php` | Upcoming birthdays list |
| `birthday-gifts.php` | Manage birthday gift options |
| `bus-bookings.php` | Bus booking management & waiting list |
| `bus-templates.php` | Bus template management |

---

## 2. Gender-Based Text Patterns

### 2.1 Pattern: Inline Ternary with `$is_female`

The most common pattern. Gender is loaded from user meta `sp_gender` (values: `'male'` / `'female'`), then a boolean `$is_female` is derived:

```php
$gender = get_user_meta($current_user->ID, 'sp_gender', true);
$is_female = ($gender === 'female');
```

Then used inline:
```php
<?php echo $is_female ? 'منورة أسرة برفوريوس 😇' : 'منور أسرة برفوريوس 😇'; ?>
```

### 2.2 All Gender-Based Strings Found

#### In `templates/unified/dashboard.php`

| Line | Context | Male | Female |
|------|---------|------|--------|
| 155 | Hero greeting with name | `ابن برفوريوس الغالي، %s!` | `بنت برفوريوس الغالية، %s!` |
| 160 | Hero subtitle | `منور أسرة برفوريوس 😇` | `منورة أسرة برفوريوس 😇` |
| 382 | Birthday today label | `عيد ميلاده النهاردة! 🎉` | `عيد ميلادها النهاردة! 🎉` |
| 386 | Birthday soon label | `عيد ميلاده قريب! 🎈` | `عيد ميلادها قريب! 🎈` |
| 401 | Congratulate label | `هنئه بهدية نقاط! ⭐` | `هنئيها بهدية نقاط! ⭐` |
| 433 | Profile complete praise | `أحسنت!` | `أحسنتِ!` |
| 573 | Blocked account message | `ابن/بنت برفوريوس! تم إيقاف حسابك...` | (same — gender-neutral) |

#### In `templates/unified/social-profile.php`

| Line | Context | Male | Female |
|------|---------|------|--------|
| 109 | Gender icon | `👨` | `👩` |

#### In `templates/unified/admin/members.php`

| Line | Context | Male | Female |
|------|---------|------|--------|
| 102 | Member gender icon | `👨` | `👩` |
| 406 | Gender label in JS | `ذكر` | `أنثى` |

#### In `templates/unified/community.php`

| Line | Context | Male | Female |
|------|---------|------|--------|
| 96 | Gender icon | `👨` (default) | `👩` |

#### In `templates/unified/leaderboard.php`

| Line | Context | Text |
|------|---------|------|
| 71 | Empty state | `كن أول ابن/بنت برفوريوس يكسب النقاط! 🏆` |

#### In `templates/unified/event-single.php`

| Line | Context | Text |
|------|---------|------|
| 656 | Gender label | `بنت` |

#### In `templates/unified/service-instructions.php` & `saint-story.php`

| Line | Context | Text |
|------|---------|------|
| 60, 68, 250 | Quiz pass praise | `أحسنت!` (not gendered — always masculine form) |

### 2.3 Gender Label Map

Used in profile.php and admin/members.php:
```php
$gender_labels = array('male' => 'ذكر', 'female' => 'أنثى');
```

---

## 3. Variable-Based Text Patterns

### 3.1 Pattern: `printf(__('...%s...%d...'), $var)`

The standard pattern for dynamic text:

```php
printf(__('بنت برفوريوس الغالية، %s!', 'saint-porphyrius'), esc_html($display_name));
printf(__('حصلت على %d نقطة هدية عيد ميلادك!', 'saint-porphyrius'), $gamification_settings['birthday_points']);
printf(__('اختيارك: %s %s', 'saint-porphyrius'), esc_html($claimed_gift->icon), esc_html($claimed_gift->title));
```

### 3.2 All Variable-Based Strings

| Template | String | Variables |
|----------|--------|-----------|
| `dashboard.php:155` | `بنت برفوريوس الغالية، %s!` | `$display_name` |
| `dashboard.php:157` | `ابن برفوريوس الغالي، %s!` | `$display_name` |
| `dashboard.php:192` | `حصلت على %d نقطة هدية عيد ميلادك!` | `$birthday_points` |
| `dashboard.php:209` | `اختيارك: %s %s` | `$icon`, `$title` |
| `dashboard.php:213` | `تمت إضافة %s نقطة لرصيدك` | `$value` |
| `dashboard.php:217` | `هديتك %s جنيه - تواصل مع الخدام` | `$value` |
| `dashboard.php:256` | `— %s نقطة` | `$value` |
| `dashboard.php:258` | `— %s جنيه` | `$value` |
| `dashboard.php:434` | `حصلت على %d نقطة مكافأة إكمال الملف!` | `$profile_completion_points` |
| `dashboard.php:448` | `أكمل بياناتك واحصل على %d نقطة!` | `$profile_completion_points` |
| `dashboard.php:506` | `اكتشف قصة حياة شفيع أسرتنا واحصل على %d نقاط` | `$story_quiz_points` |
| `dashboard.php:528` | `اقرأ وزود معلوماتك واكسب النقاط (%d اختبار متاح)` | `$published_count` |
| `dashboard.php:603` | `الغيابات: %d من %d` | `$consecutive_absences`, `$max_absences` |
| `dashboard.php:608` | `%d متبقي للكارت الأصفر` | `$remaining` |
| `dashboard.php:610` | `%d متبقي للكارت الأحمر` | `$remaining` |
| `dashboard.php:618` | `أنت محروم من %d فعاليات قادمة` | `$forbidden_remaining` |
| `events.php:239` | `متبقي %d فعاليات للرجوع` | `$forbidden_remaining` |
| `event-single.php:109` | `متبقي %d فعاليات للرجوع` | `$forbidden_remaining` |
| `leaderboard.php:71` | `كن أول ابن/بنت برفوريوس يكسب النقاط! 🏆` | (none) |
| `community.php:73` | `%d عضو في الأسرة` | `$count` |
| `share-points.php:70` | `يتم خصم %d نقطة رسوم على كل عملية مشاركة` | `$fee_fixed` |
| `share-points.php:72` | `يتم خصم %s%% رسوم على كل عملية مشاركة` | `$fee_percentage` |
| `share-points.php:117` | `رصيدك المتاح: %d نقطة` | `$balance` |
| `saint-story.php:74` | `احصل على %d نقطة عند إجابة 3 أسئلة صحيحة على الأقل` | `$story_quiz_points` |
| `service-instructions.php:79` | `احصل على %d نقطة إضافية عند إجابة 3 أسئلة صحيحة على الأقل (محاولة أخيرة)` | `$service_instructions_points` |
| `service-instructions.php:89` | `احصل على %d نقطة عند إجابة 3 أسئلة صحيحة على الأقل` | `$service_instructions_points` |
| `service-instructions.php:252` | `لقد أجبت على %s من 5 إجابات صحيحة وحصلت على %s نقطة!` | `$correct`, `$points` |
| `notifications.php:21-25` | Time ago: `%d دقيقة`, `%d ساعة`, `%d أيام`, `%d أسبوع` | `$count` |

### 3.3 Variable Types Used

| Type | Format | Example |
|------|--------|---------|
| Name | `%s` | Member display name |
| Points count | `%d` | Numeric point values |
| Money | `%s` | Gift value (could be numeric or text) |
| Percentage | `%s%%` | Fee percentage |
| Count | `%d` | Event count, member count, remaining count |
| Icon + Title | `%s %s` | Two strings combined |

---

## 4. Card & Component Structures

### 4.1 Hero Card (`dashboard.php`)

```html
<div class="sp-hero-card">
    <div class="sp-hero-content">
        <div class="sp-hero-avatar-section">
            <!-- Avatar image or placeholder -->
        </div>
        <div class="sp-hero-text">
            <h2><!-- Gender-based greeting with name --></h2>
            <p><!-- Gender-based subtitle --></p>
        </div>
        <div class="sp-hero-stat sp-hero-stat--up|down|neutral">
            <span class="sp-hero-stat-value"><!-- Points with trend arrow --></span>
            <span class="sp-hero-stat-label">نقطة</span>
        </div>
    </div>
</div>
```

### 4.2 Birthday Card (`dashboard.php`)

```html
<div class="sp-birthday-card is-birthday">
    <div class="sp-birthday-content">
        <div class="sp-birthday-emoji">🎂</div>
        <div class="sp-birthday-text">
            <h3><!-- Birthday message --></h3>
            <p class="sp-birthday-reward">🎁 <!-- Points reward text --></p>
        </div>
    </div>
    <div class="sp-birthday-confetti"></div>
</div>
```

### 4.3 Event Card (`events.php`)

```html
<a href="/app/events/{id}" class="sp-event-card is-forbidden is-draft"
   style="--event-color: {type_color};">
    <div class="sp-event-draft-badge"><!-- Draft indicator --></div>
    <div class="sp-event-forbidden-overlay"><!-- Forbidden overlay --></div>
    <!-- Event type icon, title, date, time, location, points -->
</a>
```

### 4.4 Feature Card (`dashboard.php`)

```html
<a href="/app/{route}" class="sp-feature-card">
    <div class="sp-feature-icon" style="background: linear-gradient(...);">
        <!-- Icon emoji -->
    </div>
    <div class="sp-feature-content">
        <h4 class="sp-feature-title"><!-- Title --></h4>
        <p class="sp-feature-desc"><!-- Description --></p>
    </div>
    <svg class="sp-feature-arrow"><!-- Left arrow --></svg>
</a>
```

### 4.5 Admin Stat Card (`admin/dashboard.php`)

```html
<a href="/app/admin/{route}" class="sp-admin-stat-card has-alert">
    <div class="sp-admin-stat-icon" style="background: linear-gradient(...);">
        <!-- Icon emoji -->
    </div>
    <div class="sp-admin-stat-info">
        <span class="sp-admin-stat-value"><!-- Count --></span>
        <span class="sp-admin-stat-label"><!-- Label --></span>
    </div>
    <span class="sp-admin-stat-badge"><!-- Alert badge --></span>
</a>
```

### 4.6 Admin Menu Item (`admin/dashboard.php`)

```html
<a href="/app/admin/{route}" class="sp-admin-menu-item has-alert">
    <div class="sp-admin-menu-icon" style="background: {color}; color: {color};">{icon}</div>
    <div class="sp-admin-menu-content">
        <h4>{Title}</h4>
        <p>{Description}</p>
    </div>
    <span class="sp-admin-stat-badge">{count}</span>
    <svg><!-- Left arrow --></svg>
</a>
```

### 4.7 Discipline Status Card (`dashboard.php`)

```html
<div class="sp-discipline-status-card blocked|warning|good">
    <div class="sp-blocked-message"><!-- If blocked --></div>
    <div class="sp-discipline-header"><!-- Title + badge --></div>
    <div class="sp-discipline-progress"><!-- Progress bar --></div>
    <div class="sp-discipline-info"><!-- Absence counts --></div>
    <div class="sp-forbidden-status"><!-- Forbidden remaining --></div>
</div>
```

### 4.8 Profile Completion Card (`dashboard.php`)

```html
<div class="sp-profile-completion-card">
    <div class="sp-profile-completion-header">
        <div class="sp-profile-completion-icon">📝</div>
        <div class="sp-profile-completion-info">
            <h3>أكمل ملفك الشخصي</h3>
            <p>أكمل بياناتك واحصل على {points} نقطة!</p>
        </div>
    </div>
    <div class="sp-profile-completion-progress">
        <div class="sp-profile-completion-bar">
            <div class="sp-profile-completion-fill" style="width: {percent}%;"></div>
        </div>
        <span class="sp-profile-completion-percent">{percent}%</span>
    </div>
    <a href="/app/profile" class="sp-btn sp-btn-outline sp-btn-sm sp-btn-block">
        إكمال الملف الشخصي
    </a>
</div>
```

---

## 5. Admin Page Patterns

### 5.1 Standard Admin Page Structure

Every admin page follows this exact pattern:

```php
<?php
/**
 * Saint Porphyrius - Admin {Page Name}
 */

if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) {
    wp_die(__('غير مسموح لك بالوصول لهذه الصفحة.', 'saint-porphyrius'));
}

// 1. Load defaults
$defaults = array(/* ... */);
$settings = get_option('sp_{name}_settings', $defaults);
$settings = wp_parse_args($settings, $defaults);

// 2. Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sp_{name}_nonce'])) {
    if (!wp_verify_nonce($_POST['sp_{name}_nonce'], 'sp_save_{name}_settings')) {
        wp_die('Security check failed');
    }
    // Sanitize and save
    $new_settings = array(
        'key' => sanitize_text_field($_POST['key'] ?? $defaults['key']),
        // ...
    );
    update_option('sp_{name}_settings', $new_settings);
    $settings = $new_settings;
    $message = 'success';
}
?>

<!-- 3. Header with back button -->
<div class="sp-unified-header">
    <div class="sp-header-inner">
        <a href="<?php echo home_url('/app/admin'); ?>" class="sp-header-back">
            <svg><!-- back arrow --></svg>
        </a>
        <h1 class="sp-header-title"><?php _e('Page Title', 'saint-porphyrius'); ?></h1>
        <div class="sp-header-actions"></div>
    </div>
</div>

<!-- 4. Main content -->
<main class="sp-page-content">
    <!-- 5. Success message (conditional) -->
    <?php if ($message === 'success'): ?>
    <div class="sp-alert sp-alert-success">
        <div class="sp-alert-icon">✅</div>
        <div class="sp-alert-content"><?php _e('تم حفظ الإعدادات بنجاح', 'saint-porphyrius'); ?></div>
    </div>
    <?php endif; ?>

    <!-- 6. Form -->
    <form method="POST" class="sp-section">
        <?php wp_nonce_field('sp_save_{name}_settings', 'sp_{name}_nonce'); ?>
        <!-- Form fields using sp-form-input, sp-card, etc. -->
        <button type="submit" class="sp-btn sp-btn-primary sp-btn-block">
            <?php _e('حفظ الإعدادات', 'saint-porphyrius'); ?>
        </button>
    </form>
</main>
```

### 5.2 Key Admin Patterns

- **Nonce**: Each page uses its own named nonce (`sp_{name}_nonce`, action `sp_save_{name}_settings`)
- **Option name**: `sp_{name}_settings` stored via `update_option()` / `get_option()`
- **Defaults**: Always defined as array, merged with `wp_parse_args()`
- **Sanitization**: `sanitize_text_field()`, `absint()`, `sanitize_hex_color()`, etc.
- **Success message**: Green alert card with ✅ icon
- **Back navigation**: Always links to `/app/admin` (the admin dashboard)

---

## 6. Route System

### 6.1 Rewrite Rules (`saint-porphyrius.php::add_rewrite_rules()`)

All routes are under `/app/` prefix:

**User routes:**
```
^app/?$                          → sp_app=home
^app/register/?$                 → sp_app=register
^app/login/?$                    → sp_app=login
^app/logout/?$                   → sp_app=logout
^app/pending/?$                  → sp_app=pending
^app/dashboard/?$                → sp_app=dashboard
^app/profile/?$                  → sp_app=profile
^app/events/?$                   → sp_app=events
^app/events/([0-9]+)/?$          → sp_app=event-single&sp_event_id=$matches[1]
^app/points/?$                   → sp_app=points
^app/leaderboard/?$              → sp_app=leaderboard
^app/saint-story/?$              → sp_app=saint-story
^app/service-instructions/?$     → sp_app=service-instructions
^app/community/?$                → sp_app=community
^app/member/?$                   → sp_app=social-profile
^app/share-points/?$             → sp_app=share-points
^app/quizzes/?$                  → sp_app=quizzes
^app/notifications/?$            → sp_app=notifications
^app/appeals/?$                  → sp_app=appeals
```

**Admin routes:**
```
^app/admin/?$                    → sp_app=admin
^app/admin/dashboard/?$          → sp_app=admin/dashboard
^app/admin/pending/?$            → sp_app=admin/pending
^app/admin/members/?$            → sp_app=admin/members
^app/admin/events/?$             → sp_app=admin/events
^app/admin/event-types/?$        → sp_app=admin/event-types
^app/admin/bus-bookings/?$       → sp_app=admin/bus-bookings
^app/admin/bus-templates/?$      → sp_app=admin/bus-templates
^app/admin/attendance/?$         → sp_app=admin/attendance
^app/admin/excuses/?$            → sp_app=admin/excuses
^app/admin/points/?$             → sp_app=admin/points
^app/admin/forbidden/?$          → sp_app=admin/forbidden
^app/admin/qr-scanner/?$         → sp_app=admin/qr-scanner
^app/admin/gamification/?$       → sp_app=admin/gamification
^app/admin/point-sharing/?$      → sp_app=admin/point-sharing
^app/admin/quizzes/?$            → sp_app=admin/quizzes
^app/admin/notifications/?$      → sp_app=admin/notifications
^app/admin/pwa-settings/?$       → sp_app=admin/pwa-settings
^app/admin/social-profiles/?$    → sp_app=admin/social-profiles
^app/admin/appeals/?$            → sp_app=admin/appeals
^app/admin/birthdays/?$          → sp_app=admin/birthdays
^app/admin/birthday-gifts/?$     → sp_app=admin/birthday-gifts
```

### 6.2 Route Dispatch (`templates/app-wrapper.php`)

```php
$protected_routes = array('dashboard', 'profile', 'events', 'event-single', 'points',
    'leaderboard', 'saint-story', 'service-instructions', 'community', 'share-points',
    'quizzes', 'notifications', 'social-profile', 'appeals');

$admin_routes = array('admin', 'admin/dashboard', 'admin/pending', 'admin/members',
    'admin/events', 'admin/event-types', 'admin/bus-bookings', 'admin/bus-templates',
    'admin/attendance', 'admin/excuses', 'admin/points', 'admin/forbidden',
    'admin/qr-scanner', 'admin/gamification', 'admin/point-sharing', 'admin/quizzes',
    'admin/notifications', 'admin/social-profiles', 'admin/appeals',
    'admin/birthdays', 'admin/birthday-gifts');

$guest_routes = array('home', 'login', 'register');
```

**Auth logic:**
- Admin routes → require `is_user_logged_in()` + `current_user_can('manage_options')`
- Protected routes → require `is_user_logged_in()`
- Guest routes → redirect to dashboard if logged in
- Blocked users (red card) → redirected to `blocked` page

### 6.3 Page Titles (`sp_get_page_title()`)

All titles are defined in a single associative array in `app-wrapper.php`. Each route maps to an Arabic title string.

---

## 7. AJAX Architecture

### 7.1 Registration Pattern

All AJAX actions are registered centrally in `SP_Ajax::init_hooks()`:

```php
// Public (no login required)
add_action('wp_ajax_nopriv_sp_{action}', array($this, 'ajax_{method}'));

// Private (login required)
add_action('wp_ajax_sp_{action}', array($this, 'ajax_{method}'));
```

### 7.2 Handler Pattern

Every handler follows this exact structure:

```php
public function ajax_{method}() {
    // 1. Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'sp_nonce')) {
        wp_send_json_error(array('message' => __('خطأ في التحقق', 'saint-porphyrius')));
    }

    // 2. Check capabilities (for admin operations)
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('غير مسموح', 'saint-porphyrius')));
    }

    // 3. Sanitize inputs
    $value = sanitize_text_field($_POST['field'] ?? '');

    // 4. Execute logic via domain handler
    $handler = SP_SomeHandler::get_instance();
    $result = $handler->do_something($value);

    // 5. Return result
    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    }

    wp_send_json_success($result);
}
```

### 7.3 Complete AJAX Action List (50+ actions)

| Category | Actions |
|----------|---------|
| Auth | `sp_register_user`, `sp_login_user`, `sp_logout_user` |
| Profile | `sp_get_profile`, `sp_update_profile` |
| Admin Users | `sp_approve_user`, `sp_reject_user`, `sp_get_pending_users`, `sp_admin_update_member`, `sp_block_member`, `sp_delete_member` |
| Points | `sp_get_points_history`, `sp_generate_reset_link` |
| Forbidden | `sp_manage_forbidden_status` |
| Excuses | `sp_submit_excuse` |
| QR Attendance | `sp_generate_qr_token`, `sp_validate_qr_attendance`, `sp_get_qr_status` |
| Expected Attendance | `sp_register_expected_attendance`, `sp_unregister_expected_attendance`, `sp_get_expected_attendance` |
| Quiz | `sp_submit_quiz`, `sp_submit_service_quiz`, `sp_quiz_save_category`, `sp_quiz_delete_category`, `sp_quiz_save_content`, `sp_quiz_delete_content`, `sp_quiz_ai_generate`, `sp_quiz_ai_regenerate`, `sp_quiz_ai_generate_more`, `sp_quiz_approve`, `sp_quiz_publish`, `sp_quiz_update_question`, `sp_quiz_delete_question`, `sp_quiz_update_settings`, `sp_quiz_get_youtube_info`, `sp_quiz_submit_attempt`, `sp_quiz_get_content` |
| Bus | `sp_get_bus_seat_map`, `sp_book_bus_seat`, `sp_cancel_bus_booking`, `sp_get_event_buses`, `sp_add_event_bus`, `sp_remove_event_bus`, `sp_load_past_events`, `sp_checkin_bus_passenger`, `sp_move_bus_seat`, `sp_join_bus_waiting_list`, `sp_leave_bus_waiting_list`, `sp_admin_move_waiting_entry`, `sp_admin_remove_waiting_entry`, `sp_admin_process_waiting_list` |
| Point Sharing | `sp_search_members_for_sharing`, `sp_preview_share_points`, `sp_share_points` |
| Push Notifications | `sp_push_subscribe`, `sp_push_unsubscribe`, `sp_push_send`, `sp_push_test` |
| In-App Notifications | `sp_get_unread_notif_count`, `sp_mark_notification_read`, `sp_mark_all_notifications_read` |
| Social Profile | `sp_social_upload_image`, `sp_save_social_settings`, `sp_get_social_profile` |
| Appeals | `sp_submit_appeal`, `sp_get_appealable_events` |
| Birthday | `sp_claim_birthday_gift`, `sp_send_birthday_congrats` |

---

## 8. CSS Design System

### 8.1 CSS Custom Properties (`main.css`)

```css
:root {
    /* Primary Colors - Golden/Amber */
    --sp-primary: #D4A12A;
    --sp-primary-dark: #B8891F;
    --sp-primary-light: #E8BE4D;
    --sp-secondary: #8B6914;
    --sp-accent: #F2D388;
    --sp-danger: #C53030;
    --sp-warning: #DD6B20;
    --sp-success: #38A169;
    --sp-info: #3182CE;

    /* Backgrounds */
    --sp-bg-primary: #FAFBFC;
    --sp-bg-secondary: #F5F7F9;
    --sp-bg-card: #FFFFFF;
    --sp-bg-input: #F8F9FA;

    /* Text */
    --sp-text-primary: #2D3748;
    --sp-text-secondary: #718096;
    --sp-text-light: #A0AEC0;

    /* Borders & Shadows */
    --sp-border-color: #E8ECF0;
    --sp-border-focus: #D4A12A;
    --sp-shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
    --sp-shadow-md: 0 4px 6px rgba(0, 0, 0, 0.07);
    --sp-shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);

    /* Border Radius */
    --sp-radius-sm: 8px;
    --sp-radius-md: 12px;
    --sp-radius-lg: 16px;
    --sp-radius-xl: 24px;

    /* Typography */
    --sp-font-family: 'Cairo', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --sp-font-size-xs: 0.75rem;
    --sp-font-size-sm: 0.875rem;
    --sp-font-size-base: 1rem;
    --sp-font-size-lg: 1.125rem;
    --sp-font-size-xl: 1.25rem;
    --sp-font-size-2xl: 1.5rem;
    --sp-font-size-3xl: 1.875rem;

    /* Spacing */
    --sp-spacing-xs: 0.25rem;
    --sp-spacing-sm: 0.5rem;
    --sp-spacing-md: 1rem;
    --sp-spacing-lg: 1.5rem;
    --sp-spacing-xl: 2rem;
    --sp-spacing-2xl: 3rem;

    /* Transitions */
    --sp-transition: all 0.3s ease;
}
```

### 8.2 Key CSS Classes Used in Admin Pages

| Class | Purpose |
|-------|---------|
| `.sp-unified-header` | Page header with back button |
| `.sp-header-inner` | Header flex container |
| `.sp-header-back` | Back navigation link |
| `.sp-header-title` | Page title (h1) |
| `.sp-page-content` | Main content wrapper |
| `.sp-section` | Content section with padding |
| `.sp-section-header` | Section title row |
| `.sp-section-title` | Section heading (h3) |
| `.sp-card` | White card container |
| `.sp-form-input` | Text input styling |
| `.sp-form-label` | Input label |
| `.sp-form-group` | Label + input group |
| `.sp-btn` | Base button |
| `.sp-btn-primary` | Primary (golden) button |
| `.sp-btn-outline` | Outline button |
| `.sp-btn-sm` | Small button |
| `.sp-btn-block` | Full-width button |
| `.sp-alert` | Alert message container |
| `.sp-alert-success` | Green success alert |
| `.sp-alert-error` | Red error alert |
| `.sp-alert-icon` | Alert icon |
| `.sp-alert-content` | Alert text |
| `.sp-badge` | Small badge/tag |
| `.sp-list` | List container |
| `.sp-list-item` | List row with icon |
| `.sp-list-icon` | List item icon |
| `.sp-list-content` | List item text |
| `.sp-list-title` | List item heading |
| `.sp-list-subtitle` | List item description |

---

## 9. All Customizable Text Strings (Registry)

### 9.1 Dashboard — Hero Card

| Key | Description | Default (Male) | Default (Female) | Variables |
|-----|-------------|----------------|-------------------|-----------|
| `hero_greeting` | Hero card greeting with name | `ابن برفوريوس الغالي، {name}!` | `بنت برفوريوس الغالية، {name}!` | `{name}` |
| `hero_subtitle` | Hero card subtitle | `منور أسرة برفوريوس 😇` | `منورة أسرة برفوريوس 😇` | — |
| `hero_points_label` | Points label under hero stat | `نقطة` | `نقطة` | — |

### 9.2 Dashboard — Birthday Card

| Key | Description | Default (Male) | Default (Female) | Variables |
|-----|-------------|----------------|-------------------|-----------|
| `birthday_reward_msg` | Birthday points reward | `حصلت على {points} نقطة هدية عيد ميلادك!` | `حصلتِ على {points} نقطة هدية عيد ميلادك!` | `{points}` |
| `birthday_today` | Birthday is today label | `عيد ميلاده النهاردة! 🎉` | `عيد ميلادها النهاردة! 🎉` | — |
| `birthday_soon` | Birthday is soon label | `عيد ميلاده قريب! 🎈` | `عيد ميلادها قريب! 🎈` | — |
| `birthday_congrat_label` | Congratulate CTA | `هنئه بهدية نقاط! ⭐` | `هنئيها بهدية نقاط! ⭐` | — |
| `birthday_congrat_sent` | Already congratulated | `تم إرسال تهنئتك` | `تم إرسال تهنئتك` | — |
| `birthday_gift_claimed_title` | Gift already claimed | `اخترت هديتك!` | `اخترتِ هديتك!` | — |
| `birthday_gift_claimed_desc` | Gift claimed description | `اختيارك: {icon} {title}` | `اختيارك: {icon} {title}` | `{icon}`, `{title}` |
| `birthday_gift_points_added` | Points added from gift | `تمت إضافة {points} نقطة لرصيدك` | `تمت إضافة {points} نقطة لرصيدك` | `{points}` |
| `birthday_gift_money_added` | Money gift note | `هديتك {value} جنيه - تواصل مع الخدام` | `هديتك {value} جنيه - تواصل مع الخدام` | `{value}` |
| `birthday_gift_other_added` | Other gift note | `تواصل مع الخدام لاستلام هديتك` | `تواصل مع الخدام لاستلام هديتك` | — |
| `birthday_gift_choose_title` | Choose gift title | `اختار هديتك! 🎉` | `اختاري هديتك! 🎉` | — |
| `birthday_gift_choose_desc` | Choose gift description | `اختار هدية واحدة من الهدايا المتاحة` | `اختاري هدية واحدة من الهدايا المتاحة` | — |

### 9.3 Dashboard — Profile Completion

| Key | Description | Default (Male) | Default (Female) | Variables |
|-----|-------------|----------------|-------------------|-----------|
| `profile_complete_praise` | Profile complete praise | `أحسنت!` | `أحسنتِ!` | — |
| `profile_complete_msg` | Profile complete message | `ملفك الشخصي مكتمل` | `ملفك الشخصي مكتمل` | — |
| `profile_complete_reward` | Profile complete reward | `حصلت على {points} نقطة مكافأة إكمال الملف!` | `حصلتِ على {points} نقطة مكافأة إكمال الملف!` | `{points}` |
| `profile_incomplete_title` | Incomplete profile title | `أكمل ملفك الشخصي` | `أكملي ملفك الشخصي` | — |
| `profile_incomplete_desc` | Incomplete profile desc | `أكمل بياناتك واحصل على {points} نقطة!` | `أكملي بياناتك واحصلي على {points} نقطة!` | `{points}` |
| `profile_incomplete_btn` | Complete profile button | `إكمال الملف الشخصي` | `إكمال الملف الشخصي` | — |

### 9.4 Dashboard — Discipline Status

| Key | Description | Default (Male) | Default (Female) | Variables |
|-----|-------------|----------------|-------------------|-----------|
| `discipline_blocked_title` | Blocked account title | `حسابك محظور` | `حسابك محظور` | — |
| `discipline_blocked_msg` | Blocked account message | `ابن برفوريوس! تم إيقاف حسابك بسبب تكرار الغياب — أسرة برفوريوس مستنياك ترجع! تواصل مع المسؤول لإعادة التفعيل 🙏` | `بنت برفوريوس! تم إيقاف حسابك بسبب تكرار الغياب — أسرة برفوريوس مستنياكي ترجعي! تواصل مع المسؤول لإعادة التفعيل 🙏` | — |
| `discipline_absences` | Absence count label | `الغيابات: {current} من {max}` | `الغيابات: {current} من {max}` | `{current}`, `{max}` |
| `discipline_remaining_yellow` | Remaining to yellow card | `{count} متبقي للكارت الأصفر` | `{count} متبقي للكارت الأصفر` | `{count}` |
| `discipline_remaining_red` | Remaining to red card | `{count} متبقي للكارت الأحمر` | `{count} متبقي للكارت الأحمر` | `{count}` |
| `discipline_forbidden` | Forbidden events remaining | `أنت محروم من {count} فعاليات قادمة` | `أنتِ محرومة من {count} فعاليات قادمة` | `{count}` |

### 9.5 Dashboard — Learning Cards

| Key | Description | Default | Variables |
|-----|-------------|---------|-----------|
| `story_quiz_incomplete` | Story quiz not done | `اكتشف قصة حياة شفيع أسرتنا واحصل على {points} نقاط` | `{points}` |
| `story_quiz_complete` | Story quiz done | `اطلعت على هذه القصة الملهمة ✓` | — |
| `service_instr_incomplete` | Service instructions not done | `تعرّف على نظام الخدمة والنقاط واحصل على {points} نقاط` | `{points}` |
| `service_instr_retry` | Service instructions retry | `يمكنك إعادة الاختبار والحصول على {points} نقطة إضافية` | `{points}` |
| `service_instr_complete` | Service instructions done | `تمت مراجعتك لهذا الموضوع ✓` | — |
| `quizzes_available` | Quizzes available | `اقرأ وزود معلوماتك واكسب النقاط ({count} اختبار متاح)` | `{count}` |

### 9.6 Events

| Key | Description | Default | Variables |
|-----|-------------|---------|-----------|
| `events_forbidden_remaining` | Forbidden events remaining | `متبقي {count} فعاليات للرجوع` | `{count}` |
| `events_forbidden_overlay` | Forbidden overlay text | `محروم` | — |
| `events_draft_badge` | Draft badge text | `مسودة` | — |

### 9.7 Leaderboard

| Key | Description | Default | Variables |
|-----|-------------|---------|-----------|
| `leaderboard_empty` | Empty leaderboard | `كن أول ابن/بنت برفوريوس يكسب النقاط! 🏆` | — |

### 9.8 Community

| Key | Description | Default | Variables |
|-----|-------------|---------|-----------|
| `community_member_count` | Member count label | `{count} عضو في الأسرة` | `{count}` |

### 9.9 Share Points

| Key | Description | Default | Variables |
|-----|-------------|---------|-----------|
| `share_fee_fixed` | Fixed fee info | `يتم خصم {fee} نقطة رسوم على كل عملية مشاركة` | `{fee}` |
| `share_fee_percent` | Percentage fee info | `يتم خصم {fee}% رسوم على كل عملية مشاركة` | `{fee}` |
| `share_balance` | Available balance | `رصيدك المتاح: {balance} نقطة` | `{balance}` |

### 9.10 Quiz Results

| Key | Description | Default | Variables |
|-----|-------------|---------|-----------|
| `quiz_pass_praise` | Quiz passed praise | `أحسنت!` | — |
| `quiz_fail_msg` | Quiz failed message | `حاول مرة أخرى` | — |
| `quiz_result_pass` | Pass result with score | `لقد أجبت على {correct} من {total} إجابات صحيحة وحصلت على {points} نقطة!` | `{correct}`, `{total}`, `{points}` |

### 9.11 Notifications

| Key | Description | Default | Variables |
|-----|-------------|---------|-----------|
| `notif_time_minute` | Time ago: minutes | `{count} دقيقة` | `{count}` |
| `notif_time_hour` | Time ago: hours | `{count} ساعة` | `{count}` |
| `notif_time_day` | Time ago: days | `{count} أيام` | `{count}` |
| `notif_time_week` | Time ago: weeks | `{count} أسبوع` | `{count}` |

### 9.12 General / Shared

| Key | Description | Default | Variables |
|-----|-------------|---------|-----------|
| `general_points_singular` | Point (singular) | `نقطة` | — |
| `general_points_plural` | Points (plural) | `نقاط` | — |
| `general_pound` | Egyptian pound | `جنيه` | — |
| `general_member` | Member label | `عضو` | — |
| `general_members` | Members label | `أعضاء` | — |

---

## 10. Implementation Plan

### 10.1 Architecture

```
┌─────────────────────────────────────────────────┐
│  sp_custom_texts (WP Option)                     │
│  Serialized array of all text keys with          │
│  male/female variants                            │
├─────────────────────────────────────────────────┤
│  SP_Custom_Texts (Singleton Class)               │
│  - get_default_texts() → full registry           │
│  - get_settings() → merged defaults + saved      │
│  - update_settings($data) → sanitize & save      │
│  - get_text($key, $gender, $vars) → final string │
│  - get_all_keys() → keys with descriptions       │
├─────────────────────────────────────────────────┤
│  Global helper function:                         │
│  sp_custom_text($key, $gender, $vars = [])       │
│  Used in templates instead of hardcoded strings  │
└─────────────────────────────────────────────────┘
```

### 10.2 Files to Create

| # | File | Purpose |
|---|------|---------|
| 1 | `includes/class-sp-custom-texts.php` | Handler class with Singleton pattern |
| 2 | `templates/unified/admin/custom-texts.php` | Admin page for editing texts |

### 10.3 Files to Modify

| # | File | Change |
|---|------|--------|
| 1 | `saint-porphyrius.php` | Include new class; add rewrite rule for `admin/custom-texts` |
| 2 | `templates/app-wrapper.php` | Add route to `$admin_routes`; add switch case; add title |
| 3 | `templates/unified/admin/dashboard.php` | Add nav menu item |
| 4 | `templates/unified/dashboard.php` | Replace hardcoded gender texts with `sp_custom_text()` |
| 5 | `templates/unified/events.php` | Replace forbidden remaining text |
| 6 | `templates/unified/event-single.php` | Replace forbidden remaining text |
| 7 | `templates/unified/leaderboard.php` | Replace empty state text |
| 8 | `templates/unified/community.php` | Replace member count text |
| 9 | `templates/unified/share-points.php` | Replace fee/balance texts |
| 10 | `templates/unified/social-profile.php` | Replace gender-dependent texts |
| 11 | `templates/unified/saint-story.php` | Replace quiz result texts |
| 12 | `templates/unified/service-instructions.php` | Replace quiz result texts |
| 13 | `templates/unified/notifications.php` | Replace time-ago texts |

### 10.4 Database

- **No new table needed**
- **No migration needed**
- Single option: `sp_custom_texts` — stores only overridden texts (not full defaults)

### 10.5 Security

- Admin page: `current_user_can('manage_options')` guard
- Form: `wp_verify_nonce($_POST['sp_admin_nonce'], 'sp_admin_nonce')`
- All inputs: `sanitize_text_field()`
- All outputs: `esc_html()` after interpolation

### 10.6 Admin UI Design

Following the existing admin page pattern:

```
┌──────────────────────────────────────┐
│ ←  النصوص المخصصة                    │  Header
├──────────────────────────────────────┤
│                                      │
│ 📱 بطاقة الترحيب                     │  Section
│ ┌────────────────────────────────┐   │
│ │ التحية (ذكر)  [____________]   │   │  Card
│ │ التحية (أنثى) [____________]   │   │
│ │ المتغيرات: {name}              │   │
│ └────────────────────────────────┘   │
│                                      │
│ 🎂 بطاقة عيد الميلاد                │  Section
│ ┌────────────────────────────────┐   │
│ │ ...                            │   │
│ └────────────────────────────────┘   │
│                                      │
│ [💾 حفظ جميع التعديلات]             │  Save button
└──────────────────────────────────────┘
```

### 10.7 Implementation Order

1. **Create `SP_Custom_Texts` class** — the core engine
2. **Create admin page template** — the UI for editing
3. **Wire up routes** — `saint-porphyrius.php` + `app-wrapper.php`
4. **Add nav link** — in admin dashboard
5. **Add `sp_custom_text()` helper** — global function
6. **Update `dashboard.php`** — most impactful, most texts
7. **Update other templates** — incrementally

### 10.8 `get_text()` Method Logic

```php
public function get_text($key, $gender = 'male', $vars = []) {
    $settings = $this->get_settings();

    // Get the gendered text, fall back to male if not set
    $text = '';
    if (isset($settings[$key][$gender])) {
        $text = $settings[$key][$gender];
    } elseif (isset($settings[$key]['male'])) {
        $text = $settings[$key]['male'];
    } else {
        return ''; // Key not found
    }

    // Interpolate variables: {name}, {points}, {count}, etc.
    foreach ($vars as $var_key => $var_value) {
        $text = str_replace('{' . $var_key . '}', $var_value, $text);
    }

    return $text;
}
```

### 10.9 Helper Function

```php
/**
 * Get a customizable text string.
 *
 * @param string $key    The text key from the registry.
 * @param string $gender 'male' or 'female'.
 * @param array  $vars   Associative array of variables to interpolate.
 * @return string
 */
function sp_custom_text($key, $gender = 'male', $vars = []) {
    $handler = SP_Custom_Texts::get_instance();
    return $handler->get_text($key, $gender, $vars);
}
```

### 10.10 Usage in Templates (Before → After)

**Before:**
```php
<?php if ($is_female): ?>
    <?php printf(__('بنت برفوريوس الغالية، %s!', 'saint-porphyrius'), esc_html($display_name)); ?>
<?php else: ?>
    <?php printf(__('ابن برفوريوس الغالي، %s!', 'saint-porphyrius'), esc_html($display_name)); ?>
<?php endif; ?>
```

**After:**
```php
<?php echo esc_html(sp_custom_text('hero_greeting', $gender, ['name' => $display_name])); ?>
```

---

> **End of Report** — Ready for implementation approval.
