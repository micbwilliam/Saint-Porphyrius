# Release v4.0.0 🎉

## Overview
Version 4.0.0 is a major release that introduces three powerful new systems: a **Christian Quiz System** with AI-powered question generation, **Point Sharing Settings** with admin-configurable fees and limits, and a full **OneSignal Push Notifications** integration. This release significantly expands the platform's engagement and communication capabilities.

---

## Key Features

### 📖 Christian Quiz System
A complete quiz platform for Bible studies, church teachings, and Christian education.

- **Quiz Categories**: Organize quizzes by topics (Bible, Church History, Saints, Theology, etc.)
- **AI-Powered Generation**: Admins can generate quiz questions using AI (OpenAI integration) and review/edit before publishing
- **Timed Quizzes**: Configurable time limits per quiz with auto-submit when time runs out
- **Points Rewards**: Members earn points for completing quizzes and bonus points for perfect scores
- **Attempts System**: Configurable maximum attempts per quiz with full scoring history
- **Category Browser**: User-facing quiz interface with category filtering where they can browse, start, and track their quiz progress
- **Admin Dashboard**: Full quiz management with CRUD operations for quizzes, questions, and categories
- **Leaderboard Integration**: Quiz performance contributes to overall community rankings

**New Files:**
- `includes/class-sp-quiz.php` — Quiz engine and data management
- `includes/class-sp-quiz-ai.php` — AI quiz generation via OpenAI API
- `templates/unified/quizzes.php` — User quiz browser and quiz-taking interface
- `templates/unified/admin/quizzes.php` — Admin quiz management dashboard
- `migrations/2026_02_10_000001_create_quiz_system_tables.php` — Quiz database tables

**New Database Tables:**
- `wp_sp_quiz_categories` — Quiz category definitions
- `wp_sp_quizzes` — Quiz data (title, description, time limit, points, status)
- `wp_sp_quiz_questions` — Questions with multiple-choice options
- `wp_sp_quiz_attempts` — User attempt tracking with scores

---

### 💰 Point Sharing Settings
Enhanced admin controls for the point sharing feature.

- **Fee Configuration**: Set a percentage fee on all point transfers (e.g., 10% tax)
- **Transfer Limits**: Define minimum and maximum point transfer amounts
- **Admin Dashboard**: New dedicated point sharing settings page with live fee calculation preview
- **Transfer History**: Full audit trail visible to admins

**New Files:**
- `templates/unified/admin/point-sharing.php` — Admin point sharing settings dashboard

**Modified Files:**
- `includes/class-sp-point-sharing.php` — Added fee calculation and limit enforcement
- `templates/unified/share-points.php` — Updated UI with fee display
- `templates/unified/dashboard.php` — Added quick stats

---

### 🔔 OneSignal Push Notifications
Full web push notification system powered by OneSignal.

- **OneSignal SDK v16**: Client-side integration with dynamic script loading
- **Admin Notification Center**: 5-tab management interface:
  - **Overview** — Subscriber stats, device breakdown charts, recent activity
  - **Send** — Compose notifications with live mobile preview and pre-built Arabic templates
  - **Subscribers** — Full subscriber list with device/browser details
  - **Log** — Notification history with delivery and click metrics
  - **Settings** — OneSignal App ID, REST API Key, auto-triggers, points configuration
- **Custom In-App Prompt**: Branded subscription prompt showing points incentive, with 72-hour dismissal cooldown
- **Points Incentive**: Configurable points reward when a member subscribes for the first time
- **Auto-Trigger System**: Automatic notifications when:
  - A new event is created
  - A new quiz is published
  - A member is approved
  - Scheduled reminders
- **Subscriber Analytics**: Track device types (mobile/desktop), browsers, subscription dates
- **Test Connection**: Verify OneSignal API connectivity from the admin panel
- **Profile Toggle**: Members can enable/disable push notifications from their profile

**New Files:**
- `includes/class-sp-notifications.php` — OneSignal API integration and notification engine
- `assets/js/onesignal-init.js` — Client-side OneSignal SDK initialization
- `templates/unified/admin/notifications.php` — Admin notification management (5 tabs)
- `migrations/2026_02_10_000002_create_push_notifications_tables.php` — Push notification tables

**New Database Tables:**
- `wp_sp_push_subscribers` — Subscriber tracking (player_id, user_id, device_type, browser, points_awarded)
- `wp_sp_push_notifications_log` — Notification log (title, message, delivery/click counts, trigger_type)

**Modified Files:**
- `saint-porphyrius.php` — Added notification class, routes, asset enqueue
- `includes/class-sp-ajax.php` — 4 new AJAX handlers (subscribe, unsubscribe, send, test)
- `includes/class-sp-events.php` — Added `sp_event_created` action hook
- `includes/class-sp-registration.php` — Added `sp_user_approved` action hook
- `templates/app-wrapper.php` — Added notification routes and template cases
- `templates/unified/admin/dashboard.php` — Added notifications menu item
- `templates/unified/profile.php` — Added push notification toggle section

---

## Technical Details

### New AJAX Endpoints
| Endpoint | Purpose |
|----------|---------|
| `sp_quiz_*` | Quiz CRUD, attempt submission, AI generation |
| `sp_push_subscribe` | Register push notification subscriber |
| `sp_push_unsubscribe` | Remove push notification subscriber |
| `sp_push_send` | Send notification to all subscribers |
| `sp_push_test` | Test OneSignal API connection |

### New URL Routes
| Route | Description |
|-------|-------------|
| `/app/quizzes` | User quiz browser |
| `/app/share-points` | Point sharing page |
| `/app/admin/quizzes` | Admin quiz management |
| `/app/admin/point-sharing` | Admin point sharing settings |
| `/app/admin/notifications` | Admin notification center |

### New Action Hooks
| Hook | Trigger |
|------|---------|
| `sp_event_created` | Fires when a new event is published |
| `sp_quiz_published` | Fires when a quiz is published |
| `sp_user_approved` | Fires when a pending member is approved |

### Database Migrations
- `2026_02_10_000001_create_quiz_system_tables.php` — 4 tables for quiz system
- `2026_02_10_000002_create_push_notifications_tables.php` — 2 tables for push notifications

### Stats
- **21 files changed**
- **6,583 lines added**
- **6 new database tables**
- **5 new URL routes**
- **4+ new AJAX endpoints**

---

## Installation

### Fresh Install
1. Download the release from the [Releases](https://github.com/micbwilliam/Saint-Porphyrius/releases/tag/v4.0.0) page
2. Upload to `wp-content/plugins/Saint-Porphyrius/`
3. Activate the plugin
4. Database tables will be created automatically

### Upgrade from v3.x
1. Deactivate the current version
2. Replace the plugin files with the new version
3. Activate the plugin
4. New database migrations will run automatically
5. Configure OneSignal settings in **Admin → Notifications → Settings**
6. Configure quiz settings in **Admin → Quizzes**
7. Configure point sharing fees in **Admin → Point Sharing**

### OneSignal Setup
1. Create an account at [onesignal.com](https://onesignal.com)
2. Create a new Web Push app
3. Copy the **App ID** and **REST API Key**
4. Go to **Admin → Notifications → Settings** and enter the credentials
5. Click "Test Connection" to verify

---

## Full Changelog
See [CHANGELOG.md](CHANGELOG.md) for the complete version history.
