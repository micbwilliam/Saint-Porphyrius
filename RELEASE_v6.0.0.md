# Release v6.0.0 🎉

## Overview

Version 6.0.0 is a major release focused on **community engagement, fairness, and celebration**. It introduces a **Points Appeals System**, a **Bus Waiting List**, a **Birthday Gifts & Congratulations Platform**, **Enhanced Dashboard Experience**, **Admin Profile Links**, **Points Change Notifications**, **Past Events Browsing**, and a comprehensive **Branding & UX Refresh** — plus new **Developer Tooling** to improve code quality and collaboration.

---

## Key Features

### ⚖️ Points Appeals System

Members who attended an event but couldn't scan the QR code can now formally appeal for their attendance points — no more lost points due to technical issues.

**How it works:**
- Members submit an appeal from `/app/appeals`, selecting a qualifying past event and providing a reason
- Admins review appeals at `/app/admin/appeals` with a filterable list (pending / approved / denied)
- Admin decisions include granular options: **Full Points (100%)**, **Partial (80%)**, **Partial (50%)**, **Denied**, or **Denied with Penalty (-5 points)**
- Admins can add notes to each decision for transparency
- Duplicate appeals per event are prevented
- Only past events without existing attendance records qualify

**New Files:**
- `includes/class-sp-appeals.php` — Appeals handler with validation and processing
- `templates/unified/appeals.php` — User-facing appeal submission and history page
- `templates/unified/admin/appeals.php` — Admin appeal review and processing page
- `migrations/2026_04_20_000001_create_appeals_table.php` — Appeals table

**New Routes:**
- `GET /app/appeals` — User appeals page
- `GET /app/admin/appeals` — Admin appeals management

**New Database Table:** `wp_sp_appeals`

**New AJAX Actions:** `sp_submit_appeal`, `sp_process_appeal`

---

### 🚌 Bus Waiting List

When all buses for an event are full, members can now join a waiting list instead of missing out.

**How it works:**
- When no seats are available, the event page shows a "Join Waiting List" button instead of the booking form
- Members are queued by position with full visibility of their place in line
- When a seat opens (cancellation), the system automatically books the next person in the queue
- Notified members receive an in-app notification about their confirmed seat
- Members can leave the waiting list at any time

**New Files:**
- `migrations/2026_04_20_000002_create_bus_waiting_list_table.php` — Waiting list table

**New Database Table:** `wp_sp_bus_waiting_list`

**New AJAX Actions:** `sp_join_bus_waiting_list`, `sp_leave_bus_waiting_list`

---

### 🎂 Birthday Gifts & Congratulations Platform

A complete birthday celebration system that brings the community together.

#### Birthday Gifts
- Admins define gift options (type, icon, value, description) at `/app/admin/birthday-gifts`
- Birthday members see a gift selection card on their dashboard during their birthday period
- One gift claim per member per year, tracked with full audit trail

#### Birthday Congratulations
- Members can send birthday point gifts to other members celebrating their birthday
- Each sender can congratulate each birthday member once per year
- Optional personal message accompanies the gift
- Birthday announcements appear on the dashboard with celebratory UI

#### Upcoming Birthdays Admin
- New admin page at `/app/admin/birthdays` shows members with birthdays in the next 30 days
- Displays member names with WhatsApp contact links for personal outreach

**New Files:**
- `templates/unified/admin/birthday-gifts.php` — Admin gift management page
- `templates/unified/admin/birthdays.php` — Upcoming birthdays admin view
- `migrations/2026_04_20_000003_create_birthday_gifts_tables.php` — Gifts & claims tables
- `migrations/2026_04_20_000004_create_birthday_congratulations_table.php` — Congratulations table

**New Routes:**
- `GET /app/admin/birthday-gifts` — Admin gift management
- `GET /app/admin/birthdays` — Upcoming birthdays view

**New Database Tables:**
- `wp_sp_birthday_gifts` — Gift option definitions
- `wp_sp_birthday_gift_claims` — User gift claims per year
- `wp_sp_birthday_congratulations` — Member-to-member birthday gifts

**New AJAX Actions:** `sp_save_birthday_gift`, `sp_delete_birthday_gift`, `sp_claim_birthday_gift`, `sp_send_birthday_congratulation`

---

### 📊 Enhanced Dashboard Experience

The member dashboard received a significant visual and functional upgrade.

- **Profile Image Display** — User's profile photo now appears in the dashboard hero card
- **Points Trend Indicators** — Arrow indicators showing whether your points are trending up or down
- **Birthday Celebration UI** — Special birthday messages and gift selection appear during your birthday
- **Birthday Announcements** — See and congratulate fellow members on their birthdays
- **Personalized Motivational Messages** — Dynamic encouragement based on member activity
- **Profile Completion Tracking** — Visual progress toward completing your profile

---

### 🔔 Points Change Notifications

Members now receive automatic in-app notifications whenever their points balance changes — whether from attendance, penalties, appeals, transfers, or admin adjustments. Full transparency on every point transaction.

---

### 🔔 Excuse Decision Notifications

When an admin approves or denies an excuse, the member now receives an in-app notification with the decision and any notes — no more guessing about excuse status.

---

### 📜 Past Events Browser (Admin)

Admins can now browse and load past events directly from the events management page via an AJAX "Load Past Events" button, making it easier to review historical data without navigating away.

---

### 👤 Admin Profile Links

All admin pages now link member names directly to their Social Profiles. Across Members, Points, Attendance, Excuses, Appeals, Forbidden, Bus Bookings, Birthday Gifts, QR Scanner, and Notifications — every user name is now a clickable link to their profile.

**Updated admin templates:** Members, Points, Attendance, Excuses, Appeals, Forbidden, Bus Bookings, Birthday Gifts, QR Scanner, Notifications

---

### 🚫 Booking Cancellation Safety Checks

Bus booking cancellations now verify that the passenger hasn't already been checked in for the event. Checked-in passengers cannot cancel their bus booking, preventing orphaned attendance records.

---

### ✨ Branding & UX Refresh

A comprehensive pass to unify the brand name and improve user-facing messaging:

- **Corrected branding** — Standardized "برفوريوس" (Porphyrius) spelling across all templates, notifications, and service worker
- **Personalized messages** — Login errors, registration status, blocked accounts, and pending approvals now include warmer, personalized greetings
- **Motivational dashboard** — Dynamic messages encouraging participation
- **Celebratory birthdays** — More engaging and celebratory birthday messages
- **Improved footer text** — Consistent branding across all page footers
- **Enhanced leaderboard** — More engaging messaging and personalized rank display

---

### 🛠️ Developer Tooling & Project Standards

New project infrastructure for better collaboration and code quality:

- **`.editorconfig`** — Consistent formatting across all editors
- **`.github/CODEOWNERS`** — Automatic PR review assignments
- **Issue Templates** — Bug report and feature request forms (`.github/ISSUE_TEMPLATE/`)
- **Pull Request Template** — Standardized PR description format
- **Security Policy** — `.github/SECURITY.md` for responsible vulnerability disclosure
- **Copilot Instructions** — `.github/copilot-instructions.md` for AI-assisted development
- **CI Linting Workflow** — `.github/workflows/lint.yml` for automated code checks
- **`.gitignore`** — Proper ignore patterns for WordPress plugin development
- **Arabic Strings Audit** — `ARABIC_STRINGS_AUDIT.md` documenting all user-facing Arabic text

---

## New Migrations (4)

| Migration | Description |
|-----------|-------------|
| `2026_04_20_000001_create_appeals_table.php` | Appeals for attendance points |
| `2026_04_20_000002_create_bus_waiting_list_table.php` | Bus waiting list queue |
| `2026_04_20_000003_create_birthday_gifts_tables.php` | Gift options & claims |
| `2026_04_20_000004_create_birthday_congratulations_table.php` | Member-to-member birthday gifts |

---

## New Database Tables (5)

| Table | Purpose |
|-------|---------|
| `wp_sp_appeals` | Point appeal requests and decisions |
| `wp_sp_bus_waiting_list` | Waiting list queue for full buses |
| `wp_sp_birthday_gifts` | Admin-defined gift options |
| `wp_sp_birthday_gift_claims` | User gift claims per year |
| `wp_sp_birthday_congratulations` | Member-to-member birthday gifts |

---

## New Routes (4)

| Route | Access | Description |
|-------|--------|-------------|
| `/app/appeals` | Members | Submit and view point appeals |
| `/app/admin/appeals` | Admin | Review and process appeals |
| `/app/admin/birthday-gifts` | Admin | Manage birthday gift options |
| `/app/admin/birthdays` | Admin | View upcoming member birthdays |

---

## Files Changed

- **48 files** changed
- **~5,000 lines** added
- **~180 lines** removed
- **4 new migrations**, **5 new database tables**
- **4 new routes**, **8+ new AJAX actions**
- **3 new template files**, **3 new admin template files**
- **1 new class** (`SP_Appeals`)
- **10 new project management files** (templates, workflows, configs)

---

## Integration Summary

| Feature | Dashboard | Admin | Notifications | Social Profile | Community |
|---------|-----------|-------|---------------|----------------|-----------|
| Appeals | ✅ Link | ✅ Full | ✅ Decision notify | — | — |
| Bus Waiting List | — | — | ✅ Auto-book notify | — | — |
| Birthday Gifts | ✅ Selection | ✅ Management | — | — | — |
| Birthday Congrats | ✅ Send/View | — | — | — | — |
| Points Notifications | — | — | ✅ Auto-trigger | — | — |
| Excuse Notifications | — | — | ✅ Approve/Deny | — | — |
| Profile Links | — | ✅ All pages | — | ✅ Linked | — |
| Past Events | — | ✅ Browser | — | — | — |

---

## Upgrade Notes

- **Database migrations run automatically** on plugin activation and admin page load
- **No breaking changes** — all existing features continue to work as before
- **New admin pages** appear automatically in the admin dashboard navigation
- **Appeals feature** is immediately available to all members after upgrade
- **Birthday gifts** require admin to create gift options before members can claim them

---

## Thank You

This release brings the Saint Porphyrius community closer together with celebration, fairness, and transparency at its core. Every feature was designed with the Arabic-speaking congregation in mind, maintaining the RTL-first, mobile-first experience throughout.

**Full Changelog:** `v5.0.0...v6.0.0`
