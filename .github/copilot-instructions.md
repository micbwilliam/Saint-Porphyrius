# Copilot Instructions for Saint Porphyrius

## Project Overview

Saint Porphyrius is a **mobile-first church community WordPress plugin** serving an Arabic-speaking congregation. The interface is **entirely in Arabic** (RTL layout). It manages members, events, attendance, points/gamification, quizzes, bus bookings, push notifications, and social profiles — all exposed as a Progressive Web App (PWA) at `/app/`.

- **Plugin version**: 5.0.0
- **PHP**: 7.4+ (8.0+ recommended)
- **WordPress**: 6.0+
- **GitHub repo**: `micbwilliam/Saint-Porphyrius`

---

## Architecture

### Entry Point
`saint-porphyrius.php` — Singleton class `Saint_Porphyrius`. Boots all domain handlers, registers rewrite rules, enqueues assets, and dispatches routes.

### Routing
Custom rewrite rules map URLs under `/app/*` to the query var `sp_app`. Route dispatch happens in `templates/app-wrapper.php` via a `switch` statement. Protected routes redirect unauthenticated users to `/app/login`; admin routes require `manage_options`.

### Class Structure
All feature classes live in `includes/class-sp-*.php` and follow the **Singleton pattern** (`private __construct`, `static get_instance()`). Each class owns one domain:

| Class | Domain |
|-------|--------|
| `SP_Registration` | Member registration & approval |
| `SP_User` | Auth, profile, login/logout |
| `SP_Admin` | WP admin menu & settings |
| `SP_Ajax` | All 50+ AJAX endpoints |
| `SP_Events` | Event CRUD |
| `SP_Event_Types` | Event type templates |
| `SP_Attendance` | Attendance records |
| `SP_Points` | Points ledger & balance |
| `SP_QR_Attendance` | QR check-in tokens |
| `SP_Excuses` | Excuse submissions |
| `SP_Forbidden` | Discipline (yellow/red cards) |
| `SP_Expected_Attendance` | Event registration |
| `SP_Gamification` | Birthday/profile rewards |
| `SP_Bus` | Bus templates & seat bookings |
| `SP_Point_Sharing` | Member-to-member transfers |
| `SP_Quiz` | Quiz engine |
| `SP_Quiz_AI` | OpenAI question generation |
| `SP_Notifications` | OneSignal + in-app inbox |
| `SP_Social_Profile` | Aggregated member profiles |
| `SP_Migrator` | Database migration runner |
| `SP_Updater` | GitHub-based auto-updates |

### AJAX
All AJAX actions are registered centrally in `SP_Ajax::init_hooks()`. Every handler:
1. Verifies nonce (`wp_verify_nonce($_POST['nonce'], 'sp_nonce')` or `sp_admin_nonce`)
2. Checks capabilities for admin operations
3. Sanitizes inputs (`sanitize_text_field`, `sanitize_email`, etc.)
4. Returns `wp_send_json_success()` / `wp_send_json_error()`

### Database
Custom tables use `{$wpdb->prefix}sp_` prefix. Schema changes are managed by migration files in `migrations/` (Laravel-inspired), tracked in `wp_sp_migrations`. Use `dbDelta()`-friendly SQL.

### Frontend
- Templates: `templates/unified/*.php` (app pages), `templates/unified/admin/*.php` (admin pages), `templates/*.php` (auth/landing)
- CSS: `assets/css/` — `main.css` (core design system), `unified.css` (modern components), `pwa.css`, `admin.css`
- JS: `assets/js/` — vanilla ES6+ (no jQuery for new code)
- PWA: `assets/manifest.json`, `assets/js/service-worker.js`, `assets/js/pwa-installer.js`

---

## Coding Standards

### PHP
- Follow **WordPress PHP Coding Standards**
- Use `$wpdb->prepare()` for all SQL — never raw queries
- Sanitize all inputs, escape all outputs (`esc_html`, `esc_attr`, `esc_url`)
- Verify nonces on every AJAX handler
- Check capabilities (`current_user_can()`) for admin operations
- Prefix everything with `sp_` (functions, hooks, options, meta keys, table names)

### JavaScript
- Vanilla ES6+ — no jQuery for new code
- Use `wp_localize_script()` for passing PHP data
- AJAX via `fetch()` with WordPress nonce in FormData

### CSS
- RTL-first design (Arabic is the primary language)
- Mobile-first responsive
- Use CSS custom properties defined in `main.css`
- BEM-ish naming with `sp-` prefix

### Naming Conventions
| Entity | Pattern | Example |
|--------|---------|---------|
| Class | `SP_PascalCase` | `SP_Events` |
| Table | `wp_sp_snake_case` | `wp_sp_events` |
| Hook | `sp_snake_case` | `sp_event_created` |
| AJAX action | `sp_snake_case` | `sp_register_user` |
| CSS class | `sp-kebab-case` | `sp-event-card` |
| Option | `sp_snake_case` | `sp_quiz_settings` |
| User meta | `sp_snake_case` | `sp_phone` |

---

## Checklists for Common Tasks

### Adding a New App Route
1. Add rewrite rule in `saint-porphyrius.php::add_rewrite_rules()`
2. Add route case in `templates/app-wrapper.php` switch
3. Add title in `sp_get_page_title()`
4. Add route to `protected_routes` / `admin_routes` / `guest_routes` as needed
5. Create template file in `templates/unified/`

### Adding an AJAX Endpoint
1. Register action in `SP_Ajax::init_hooks()` (`wp_ajax_sp_*` and optionally `wp_ajax_nopriv_sp_*`)
2. Create handler method with nonce verification
3. Validate capabilities for restricted operations
4. Sanitize inputs, return `wp_send_json_success/error`

### Adding a Database Migration
1. Create file in `migrations/` named `YYYY_MM_DD_NNNNNN_description.php`
2. Return anonymous class with `up()` and `down()` methods
3. Use `dbDelta()`-friendly SQL; for UNIQUE keys on utf8mb4, use `varchar(191)`
4. `SP_Migrator` runs pending migrations on activation and admin init

### Adding an Admin Route
1. Add rewrite rule in `saint-porphyrius.php`
2. Add route to `admin_routes` in `templates/app-wrapper.php`
3. Create template in `templates/unified/admin/`
4. Add nav link in `templates/unified/admin/dashboard.php`
5. Enforce `manage_options` capability

---

## User-Facing Text

All user-facing strings are in **Arabic**. Use proper Arabic grammar and church terminology. Examples:
- "تم بنجاح" (Success), "حدث خطأ" (Error occurred)
- "الأحداث" (Events), "النقاط" (Points), "الحضور" (Attendance)
- Member = "عضو", Admin = "مسؤول", Church = "كنيسة"

---

## Security Requirements

- Never trust user input — always sanitize and validate
- Always use `$wpdb->prepare()` for SQL queries with user data
- Verify nonces on all form submissions and AJAX requests
- Check `current_user_can()` before admin operations
- Use `wp_hash_password()` for password storage
- QR tokens expire after 5 minutes and are single-use
- HTTPS is required for PWA features

---

## External Integrations

| Service | Purpose | Config Location |
|---------|---------|-----------------|
| OpenAI | Quiz AI generation (GPT-4o) | `sp_quiz_settings` option |
| OneSignal | Push notifications | `sp_push_settings` option |
| Google Maps | Event location URLs | Inline in event data |

---

## Custom Roles & Capabilities

- `sp_member` — Regular church member
- `sp_church_admin` — Church administrator
- Custom caps: `sp_manage_members`, `sp_approve_members`
