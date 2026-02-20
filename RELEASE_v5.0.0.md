# Release v5.0.0 🎉

## Overview

Version 5.0.0 is a major release that transforms Saint Porphyrius from a management tool into a **social community platform**. It introduces **Social Profiles** — automatic, data-rich public profiles for every member — alongside a persistent **In-App Notifications Inbox**, a **PWA Settings** admin panel, and an **Anti-Random-Guessing Quiz Protection** system. All new features are fully integrated with every existing system in the app.

---

## Key Features

### 👤 Social Profiles System

Every member now has a fully automatic social profile that aggregates their entire journey in the community — no user-generated content, no setup required. Just open and explore.

**What's on a profile:**
- **Hero section** — Cover photo, avatar (with optional upload), full name, church, join date
- **Stats row** — Points balance, community rank, attendance count, attendance rate %
- **Discipline status** — Live red/yellow/green card and any active ban indicator
- **Achievement badges** — Auto-computed from real data:
  - ✅ Profile Complete
  - 📖 Story Quiz Master
  - ⛪ Service Quiz Master
  - 🏆 Top 3 Leaderboard
  - 📅 10+ Attendance
  - ⭐ 100+ Points
- **Attendance breakdown** — Present / Absent / Excused / Late grid
- **Upcoming events** — Events the member has registered for
- **Event activity** — Recent attended / missed / excused events as social-style dated posts
- **Bus bookings** — Confirmed upcoming trip reservations
- **Quiz stats** — Average score, total attempts, recent quiz results
- **Points timeline** — Every points transaction as a social feed post with icons and date separators

**How to access:**
- Own profile: `/app/member/` (or tap **👤 ملفي الاجتماعي** on the dashboard)
- Any member: `/app/member/?id=USER_ID`
- Community page: tap a member card → expand → **"عرض الملف الاجتماعي"**
- Leaderboard: tap any row

**Admin controls** (`/app/admin/social-profiles`):
- Master toggle to disable the entire feature
- Individual toggles for each section: points history, attendance, bus, quiz, discipline, events, excuses, cover/profile image upload

**New Files:**
- `includes/class-sp-social-profile.php`
- `templates/unified/social-profile.php`
- `templates/unified/admin/social-profiles.php`
- `migrations/2026_02_20_000001_create_social_profiles_table.php`

**New DB Table:** `wp_sp_social_profiles`

---

### 🔔 In-App Notifications Inbox

A persistent, typed notification inbox so members never miss an important update — even without push notifications.

- **Notification Inbox** — Full page at `/app/notifications` with history, icons, and read/unread styling
- **Notification Types** — `event`, `quiz`, `system`, `points`, `announcement`, each with distinct icon and color
- **Bell Badge** — Real-time unread count shown on the bell icon across all app pages
- **Auto-Trigger** — New quizzes and events automatically create in-app notifications for relevant members
- **Deep Links** — Notifications can link directly to any page in the app
- **Mark All Read** — One-tap bulk clear
- **Admin Integration** — Admin notification composer now supports sending in-app notifications alongside push notifications

**New Files:**
- `templates/unified/notifications.php`
- `migrations/2026_02_12_000001_create_user_notifications_table.php`

**New DB Table:** `wp_sp_user_notifications`

---

### 📱 PWA Settings Admin

A dedicated admin panel to manage the Progressive Web App configuration without touching code.

- **Manifest Properties** — App name, short name, theme color, background color, display mode
- **App Icon Badge** — Numeric badge on the home screen icon showing unread notification count
- **Install Prompt Control** — Toggle the Add-to-Home-Screen install prompt on/off
- Available at `/app/admin/pwa-settings`

**New Files:**
- `templates/unified/admin/pwa-settings.php`

---

### 🧠 Anti-Random-Guessing Quiz Protection

Prevents members from rapidly tapping through quiz answers to farm points without reading the questions.

- **Per-Question Timer** — Client tracks how long a member spends on each question
- **Minimum Threshold** — Admin sets a minimum time (seconds) required before an answer is accepted
- **Speed Penalty** — Answers submitted too fast receive a configurable point deduction
- **Admin Settings** — Threshold and penalty amount configurable from the quiz admin panel

---

## Integration Summary

| Feature | Community | Leaderboard | Dashboard | Admin |
|---|---|---|---|---|
| Social Profiles | ✅ Profile link in card | ✅ Clickable rows | ✅ Hero quick link | ✅ Settings toggle |
| Notifications | ✅ Bell badge | ✅ Bell badge | ✅ Bell badge | ✅ Composer enhanced |
| PWA Settings | — | — | — | ✅ New settings page |
| Quiz Protection | — | — | — | ✅ Quiz admin settings |

---

## Migration Notes

1. **Run migrations** from Admin → Migrations to create the two new tables:
   - `wp_sp_social_profiles`
   - `wp_sp_user_notifications`

2. **Flush permalink rules** — Visit WP Admin → Settings → Permalinks → Save Changes to register the new `/app/member/` route (this happens automatically on first admin page load due to the flush version bump).

3. **Social profiles are enabled by default.** Disable from `/app/admin/social-profiles` if needed.

---

## Files Changed

### New Files
| File | Purpose |
|---|---|
| `includes/class-sp-social-profile.php` | Social profile data aggregator |
| `templates/unified/social-profile.php` | Public profile page |
| `templates/unified/admin/social-profiles.php` | Admin social profile settings |
| `templates/unified/admin/pwa-settings.php` | PWA configuration admin |
| `migrations/2026_02_20_000001_create_social_profiles_table.php` | Social profiles DB table |
| `migrations/2026_02_12_000001_create_user_notifications_table.php` | Notifications DB table |

### Modified Files
| File | Changes |
|---|---|
| `saint-porphyrius.php` | Version 5.0.0, new rewrite rules, new includes |
| `templates/app-wrapper.php` | New routes: social-profile, admin/social-profiles, pwa-settings |
| `includes/class-sp-ajax.php` | 3 social profile AJAX endpoints + notifications endpoints |
| `includes/class-sp-notifications.php` | In-app notification system, typed notifications |
| `includes/class-sp-quiz.php` | Timing tracking and anti-guessing penalty logic |
| `includes/class-sp-quiz-ai.php` | Anti-guessing penalty settings |
| `templates/unified/dashboard.php` | Social profile hero button, notification bell |
| `templates/unified/community.php` | Profile link in expanded member cards |
| `templates/unified/leaderboard.php` | Clickable rows linking to profiles |
| `templates/unified/notifications.php` | Full inbox page (rewritten) |
| `templates/unified/quizzes.php` | Per-question timing, anti-guessing UI feedback |
| `templates/unified/admin/dashboard.php` | Social profiles + PWA settings menu items |
| `templates/unified/admin/notifications.php` | In-app notification fields |
| `templates/unified/admin/quizzes.php` | Anti-guessing penalty settings |
| `assets/css/unified.css` | Social profile styles, notification inbox styles, leaderboard hover, hero button |
| `assets/js/main.js` | Notification read/unread handling, quiz timer, badge updates |
| `assets/manifest.json` | PWA manifest updates |
