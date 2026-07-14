# Changelog

All notable changes to the Saint Porphyrius plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [6.4.6] - 2026-07-14

### Fixed

#### 📅 Events — duplicated on creation

- **Creating an event created it twice.** The events screen handles its POST at the top of the template and then falls through and re-renders, so the browser was left sitting on the POST result — and, because the URL stayed on `?action=new`, on an *empty create form with a success banner*, which reads as "nothing saved". A refresh, a PWA pull-to-refresh, or Back→Forward replayed the body and inserted the event a second time. WP nonces do not stop this: `wp_verify_nonce()` accepts the same nonce for 12–24h. `SP_Events::create()` was a bare INSERT with no unique constraint, so nothing downstream caught it either, and a duplicated *published* event fired `sp_event_created` twice — duplicating the push notification as well.
- Fixed with the same three layers the points log got in 6.4.5: a `dedupe_key` column with a UNIQUE index on `sp_events`; an optional `$dedupe_key` argument on `SP_Events::create()` that reports a replay back as `['duplicate' => true]` instead of inserting; and a single-use form token that carries the key. The token is what closes the double-tap race, where two requests are in flight at once and a check-then-act guard would let both through.
- **The events screen now redirects after a successful write** (POST/Redirect/GET), so there is no POST result left to replay — for update, delete and complete as well as create. Failures still render inline on purpose, so a rejected submission does not throw away what the admin typed. The submit button also disables itself on submit.
- The shared pieces live in a new `SP_Form_Guard` helper, so the other admin screens that handle their POST inline can adopt the same protection.

#### 📚 Lesson Preparation — "فشل الاتصال بالخادم" when publishing

- **Publishing a lesson usually failed, and never said why.** The wizard built its save request with `new FormData(form)` over the whole form, and the PDF file inputs live inside that form — even though PDFs are already uploaded by their own `sp_lesson_pdf_upload` request and stored against the lesson. So every draft autosave, every save-as-draft and the publish itself re-uploaded every selected PDF. That pushed the request past `post_max_size`, at which point PHP discards `$_POST` entirely, admin-ajax finds no `action`, and answers with the bare string `0`. The save requests now drop the file inputs from their payload.
- **The error handler was hiding its own cause.** The response was read with a bare `r.json()` and no status check. `JSON.parse("0")` succeeds and returns the number `0`, so the failure branch then read `resp.data.message` off it and threw — landing in the `.catch` that reports "فشل الاتصال بالخادم". *Every* server-side failure — oversized POST, HTTP 500, expired session — surfaced as a connection error, which is why this was never diagnosable. Responses now go through a shared reader that checks the status, recognises admin-ajax's `0`/`-1` replies, and reports what actually went wrong.
- **A successful publish could still report failure.** `persistQuestions()` had no error path of its own, so a questions-only failure claimed the connection died even though the lesson had published. It now says so, and does not invite the admin to press Publish again.
- **Publishing could time out on a large member list.** `set_lesson_access()` ran one INSERT per (grade × member) — 360 round-trips for 6 grades × 60 members. It is now a single batched INSERT.
- **A retried publish could create a second lesson.** The lesson id was only recorded after a successful parse, so when the server had written the row but the response was lost, the next click re-ran `sp_lesson_create`. The id is now recorded as soon as the create succeeds, and the draft/publish buttons lock while either save is in flight.

Pending migrations now also run for admins outside `wp-admin`, since the screens that need the new schema live on the frontend under `/app/admin`.

## [6.4.5] - 2026-07-09

### Fixed

#### 🎯 Points — duplicated awards

- **Completing an event awarded every attendance a second time.** `SP_Attendance::mark()` writes points to the log immediately but never set the `points_processed` flag. `SP_Events::complete_event()` then calls `process_event_points()`, which settles every record whose flag is still `0` — i.e. all of them. Marking an event complete therefore duplicated the attendance and penalty points of every member. `mark()` now sets `points_processed = 1`, `process_event_points()` carries a dedupe key, and `complete_event()` returns early when the event is already completed. A migration backfills the flag on historical rows so completing an old event no longer re-awards it.
- **`sp_points_log` had no idempotency key.** `SP_Points::add()` was a bare INSERT, so any repeated call — double-tap, retried AJAX, refreshed POST — appended a second row and permanently doubled the balance (the log is the source of truth for `get_balance()` and the leaderboard). Added a `dedupe_key` column with a UNIQUE index and an optional `$dedupe_key` argument; repeat awards are now rejected by the database and reported back as `['duplicate' => true]`. NULL keys still repeat, so manual adjustments are unaffected.
- **Concurrent awards lost an update.** `add()` read the balance from the `sp_points_balance` user-meta cache, which can be stale, then wrote back `balance + points`. Two overlapping requests both read the same value and one award vanished from `balance_after`. The balance is now derived from the log inside a transaction with `SELECT … FOR UPDATE`, which serialises awards per user.
- **`type` was an enum of 6 values while the code wrote 25.** Values such as `attendance`, `birthday_reward` and `bus_booking_refund` were silently coerced to `''`. That broke the duplicate guard in `refund_bus_booking_fee()`, which matches on `type = 'bus_booking_refund'` and so never fired. `type` is now `varchar(40)`.
- **Two `add()` calls passed their arguments in the wrong order**, writing the reason string into the `event_id` column: excuse submission/denial (`SP_Excuses`) and waiting-list bus fees (`SP_Bus`).
- **Manual points adjustment re-ran on browser refresh.** The admin screens render the POST result inline instead of redirecting, so refresh or Back resubmitted the adjustment. Both forms now carry a single-use token that doubles as the award's dedupe key.
- **Birthday-congratulation rollback deleted the wrong row.** It used `$wpdb->insert_id` after `add()` had run its own INSERT, so the id no longer referred to the congratulation record.

Idempotency keys were also added to the once-per-occurrence awards that previously relied on a racy check-then-act guard: birthday, feast day, profile completion, story quiz, service instructions, birthday gift, birthday congratulations, push-notification subscription, appeal decisions, excuse submission/denial, lesson-prep approval and quiz passes, bus booking fees and refunds, quiz best-score top-ups, and point sharing (deduped over a one-minute window so a deliberate repeat gift still works).

## [6.4.4] - 2026-05-31

### Fixed

#### 📚 Lesson Preparation — Members can finally see their assigned lessons
- **Members saw "لا توجد دروس" even when correctly assigned.** `get_lessons()` built its prepared-statement parameters in WHERE-first order, but the access `INNER JOIN (… WHERE user_id = %d)` subquery appears *before* the `WHERE l.status = %s` clause in the SQL text. `$wpdb->prepare()` binds placeholders left-to-right, so for a member the user ID and status string were swapped: `user_id` was bound to `"published"` (→ `0`, no such user) and `status` to the numeric user ID (no such status) — so the query matched nothing. The join params are now merged ahead of the WHERE params so binding order matches the SQL text.
- Admins were unaffected (their preview path adds no access join), which is why this stayed hidden until per-member access actually started saving in 6.4.2. Lessons must still be **published** (not draft) to appear to members.

## [6.4.3] - 2026-05-31

### Fixed

#### ⬅️ Back buttons now go *back*, not to a fixed page
- **The header back arrow (`.sp-header-back`) on every member and admin page returned the user to a single hardcoded "parent" page instead of where they actually came from.** That parent is only correct when a page is reached one way — but many pages have several entry points, so "back" landed users somewhere they had never been. Examples that misbehaved: a member's social profile (always went to *community*, even when opened from the *leaderboard*, *dashboard* birthday cards or *birthdays* list); an event detail (always went to the *events* list, even when opened from a *notification* or the *dashboard*); a quiz (always to the *dashboard*); a single notification (always to the notifications list).
- **Fix is code-wide and centralized.** A single delegated handler now backs all `.sp-header-back` arrows: if the previous page was inside the app, it performs a real browser history back (returning to the actual previous screen); otherwise — direct entry, a push-notification deep link, a fresh PWA launch, a refresh, or a form POST to the same URL — it falls back to the existing hardcoded parent href, so it still goes somewhere sensible and never leaves the app or gets stuck on the current page. Modifier/middle clicks (open-in-new-tab) are left untouched.
- No per-page markup changes were needed; the hardcoded hrefs are kept as the safe fallback.

## [6.4.2] - 2026-05-31

### Fixed

#### 📚 Lesson Preparation — Member access & per-lesson settings now save
- **Member selections are saved again.** The "الأعضاء المسموح لهم" picker silently dropped every selection. The per-grade access list is sent as a JSON string, but WordPress slash-escapes all `$_POST` data (magic quotes); the un-unslashed JSON failed to decode (`json_decode` returned `null`), so `set_lesson_access()` was never called. The result: members saw no lessons, and reopening the wizard showed every checkbox unchecked. `create_lesson`/`update_lesson` now `wp_unslash()` the access payload before decoding (the bare `grades` array was unaffected only because it contains no quote characters).
- **Per-lesson quiz, points & AI-detection settings persist.** The same magic-quote issue caused `quiz_config`, `prep_points_config`, `ai_detection_config`, and `pdf_urls` to be stored as invalid (slash-escaped) JSON, so on read they decoded to empty and silently fell back to the global defaults. These are now unslashed before storage.

> **Note:** lessons saved before this fix have no stored member access — reopen each one, reselect members per grade, and save again.

## [6.4.1] - 2026-05-31

### Added

#### 📚 Lesson Preparation — Per-Grade Member Access
- **Assign members per target year** — The lesson create/edit wizard now shows one member panel per selected target grade (الصف ١، الصف ٣، …) instead of a single flat list, so admins add members to **each** year. Each panel has its own search, "تحديد الكل / إلغاء الكل", and a live member count.
- **Per-grade storage & visibility** — Access is saved per grade; a member who is assigned under multiple years no longer sees the lesson duplicated in their list, and a preparation records the grade the member was actually assigned to for that lesson.

#### 📅 Link Lessons to Draft Events
- The lesson wizard's "ربط بفعالية" picker now includes **upcoming draft events** (marked `(مسودة)`), not only published ones, so a lesson can be prepared before its event goes live. Completed events stay out, and an already-linked event remains selectable when editing.

#### 🖼️ Profile & Dashboard
- **Tap-to-enlarge profile photos** — On the social profile page, tapping the cover photo or the profile picture now opens it full-screen in a lightbox (close with the ✕ button, backdrop tap, or `Esc`). Only photos that actually exist are zoomable — the letter-fallback avatar is not.
- **Clickable birthday cards** — On the dashboard "أعياد ميلاد اليوم" cards, the member's name and photo now link to their social profile page. The congratulation/gift controls below remain unaffected.

### Changed
- Lesson list visibility now filters per-user via a DISTINCT-lesson access subquery (prevents duplicate rows when a member holds several grade assignments).

## [6.4.0] - 2026-05-31

### Added

#### ✏️ Profile Edit Approval Workflow
- **Request-and-approve editing** — After a member is approved, they can no longer change their profile data directly. Submitting the profile edit form now creates a pending **edit request** instead of writing changes immediately.
- **Admin review panel** — Admins review requested changes at `/app/admin/profile-edits` with a filterable list (pending/approved/rejected). Each request shows a field-by-field diff (old → new). Approving applies the changes to the member; rejecting (with an optional reason) discards them.
- **One pending request at a time** — A member cannot submit a new edit request while one is still under review; the profile page shows a "pending review" banner.
- **Notifications** — Admins are notified (inbox + push) when a request is submitted; the member is notified of the approve/reject decision.
- **Staff exemption** — Admins and member managers keep direct profile editing.

**New Files:**
- `includes/class-sp-profile-edits.php` — Profile edit requests handler
- `templates/unified/admin/profile-edits.php` — Admin review page
- `migrations/2026_05_31_000001_create_profile_edit_requests_table.php`

**New Database Table:** `wp_sp_profile_edit_requests`

## [6.0.0] - 2026-04-20

### Added

#### ⚖️ Points Appeals System
- **Appeal Submission** — Members can formally appeal for attendance points when QR scan was missed, via `/app/appeals`
- **Admin Review Panel** — Admins review appeals at `/app/admin/appeals` with filterable list (pending/approved/denied)
- **Granular Decisions** — Full Points (100%), Partial (80%), Partial (50%), Denied, or Denied with Penalty (-5 points)
- **Admin Notes** — Transparency notes on each appeal decision
- **Duplicate Prevention** — One appeal per event per member; only qualifying past events allowed

**New Files:**
- `includes/class-sp-appeals.php` — Appeals handler
- `templates/unified/appeals.php` — User appeal page
- `templates/unified/admin/appeals.php` — Admin review page
- `migrations/2026_04_20_000001_create_appeals_table.php`

**New Database Table:** `wp_sp_appeals`

#### 🚌 Bus Waiting List
- **Automatic Queue** — When all buses are full, members can join a waiting list with position tracking
- **Auto-Booking** — When a seat opens, the next person in the queue is automatically booked and notified
- **Leave/Join Controls** — Members can join or leave the waiting list at any time
- **Position Visibility** — Members see their position in the queue

**New Files:**
- `migrations/2026_04_20_000002_create_bus_waiting_list_table.php`

**New Database Table:** `wp_sp_bus_waiting_list`

#### 🎂 Birthday Gifts & Congratulations Platform
- **Birthday Gift Options** — Admins define gift options (type, icon, value) at `/app/admin/birthday-gifts`
- **Gift Claiming** — Birthday members select and claim one gift per year from their dashboard
- **Birthday Congratulations** — Members can send birthday point gifts to celebrating members (once per year per recipient)
- **Upcoming Birthdays Admin** — New admin page at `/app/admin/birthdays` shows next-30-days birthdays with WhatsApp links
- **Birthday Announcements** — Celebratory UI on the dashboard with congratulation cards
- **Birthday Notifications** — Enhanced birthday messages with personalized greetings

**New Files:**
- `templates/unified/admin/birthday-gifts.php`
- `templates/unified/admin/birthdays.php`
- `migrations/2026_04_20_000003_create_birthday_gifts_tables.php`
- `migrations/2026_04_20_000004_create_birthday_congratulations_table.php`

**New Database Tables:** `wp_sp_birthday_gifts`, `wp_sp_birthday_gift_claims`, `wp_sp_birthday_congratulations`

#### 📊 Enhanced Dashboard
- **Profile Image** — User photo displayed in dashboard hero card
- **Points Trend Indicators** — Up/down arrows showing points movement direction
- **Birthday Celebration UI** — Special birthday messages and gift selection during birthday period
- **Birthday Congratulation Cards** — Send and view birthday wishes on the dashboard
- **Motivational Messages** — Dynamic personalized encouragement based on activity

#### 🔔 Points Change Notifications
- **Automatic Notifications** — In-app notification triggered on every points balance change (attendance, penalties, appeals, transfers, admin adjustments)

#### 🔔 Excuse Decision Notifications
- **Approve/Deny Notifications** — Members receive in-app notification when admin approves or denies their excuse

#### 📜 Past Events Browser (Admin)
- **AJAX Past Events Loading** — "Load Past Events" button on admin events page for browsing historical events

#### 👤 Admin Profile Links
- **Clickable Names** — All member names across admin pages now link to their Social Profile
- **Updated Templates** — Members, Points, Attendance, Excuses, Appeals, Forbidden, Bus Bookings, Birthday Gifts, QR Scanner, Notifications

#### 🚫 Booking Cancellation Safety
- **Check-in Verification** — Bus booking cancellations blocked for passengers already checked in to the event

#### 🛠️ Developer Tooling & Project Standards
- **`.editorconfig`** — Consistent code formatting
- **`.github/CODEOWNERS`** — Auto PR review assignments
- **Issue & PR Templates** — Bug reports, feature requests, PR descriptions
- **Security Policy** — `.github/SECURITY.md`
- **Copilot Instructions** — `.github/copilot-instructions.md`
- **CI Linting** — `.github/workflows/lint.yml`
- **`.gitignore`** — WordPress plugin ignore patterns
- **Arabic Strings Audit** — Full documentation of all Arabic UI text

### Changed
- Corrected "برفوريوس" (Porphyrius) spelling across all templates, notifications, and service worker
- Personalized error messages for login, registration, blocked accounts, and pending approvals
- Enhanced birthday messages with more celebratory and engaging text
- Improved footer text with consistent branding across all pages
- Enhanced leaderboard with personalized messaging and rank display
- Improved admin events page empty state message

### Fixed
- Correct class declaration for birthday gifts migration

## [5.0.0] - 2026-02-20

### Added

#### 👤 Social Profiles System
- **Automatic Social Profiles** — Every member now has a public profile page at `/app/member/?id=USER_ID` with zero user-generated content — all data is pulled automatically from existing systems
- **Cover & Profile Images** — Members can upload a custom cover photo and avatar (admin-toggleable)
- **Stats Overview** — Points balance, community rank, total attendance count, attendance rate percentage
- **Discipline Status Card** — Live red/yellow/green card status and any active ban displayed prominently
- **Achievement Badges** — Auto-generated badges: Profile Complete, Story Quiz Master, Service Quiz Master, 10+ Attendance, 100+ Points, Top 3 Leaderboard
- **Attendance Breakdown Grid** — Present / Absent / Excused / Late counts at a glance
- **Events Section** — Upcoming registered events and recent event activity (attended / missed / excused) shown as social-style posts
- **Bus Bookings Section** — Upcoming confirmed bus trip reservations
- **Quiz Stats** — Overall quiz average, total attempts, recent quiz attempts with scores
- **Points Timeline** — Full points history rendered as a dated social-media feed with icons per transaction type
- **Admin Settings** — Toggle entire feature on/off; individually enable/disable: points history, attendance, bus info, quiz stats, discipline, events, excuses, and image uploads
- **Community Page Integration** — Expanded member card now shows a "👤 عرض الملف الاجتماعي" button
- **Leaderboard Integration** — All leaderboard rows are now tappable links with a `›` indicator pointing to each member's profile
- **Dashboard Quick Link** — "👤 ملفي الاجتماعي" pill button added to the hero card for instant access

**New Files:**
- `includes/class-sp-social-profile.php` — Core handler, aggregates all systems
- `templates/unified/social-profile.php` — Public profile page template
- `templates/unified/admin/social-profiles.php` — Admin settings template
- `migrations/2026_02_20_000001_create_social_profiles_table.php` — Profile images table

**New Routes:**
- `GET /app/member/` — Own social profile
- `GET /app/member/?id=USER_ID` — Any member's social profile
- `GET /app/admin/social-profiles` — Admin settings

**New Database Table:**
- `wp_sp_social_profiles` — Stores cover_image and profile_image per user

#### 🔔 In-App Notifications Inbox
- **Persistent Notification Inbox** — Dedicated `/app/notifications` page with full notification history and read/unread states
- **Notification Types System** — Typed notifications: `event`, `quiz`, `system`, `points`, `announcement` with per-type icons and colors
- **Bell Badge** — Unread count badge on the bell icon across all pages; clears as notifications are read
- **Auto-Trigger Integration** — Quiz publishing and event creation now auto-create in-app notifications for relevant members
- **Admin Notification Enhancement** — Admin notification composer now supports an in-app delivery option alongside push
- **Mark All Read** — One-tap action to clear all unread notifications
- **Notification Links** — Each notification can carry a deep link into the app

**New Files:**
- `templates/unified/notifications.php` — Full notification inbox page
- `migrations/2026_02_12_000001_create_user_notifications_table.php` — Notifications table

**New Database Table:**
- `wp_sp_user_notifications` — Stores per-user notifications with type, read status, and link

#### 📱 PWA Settings Admin
- **PWA Settings Page** — New admin page at `/app/admin/pwa-settings` for managing progressive web app configuration
- **App Icon Badge** — Configurable numeric badge on the app icon for unread notification count
- **Manifest Management** — Admin control over PWA manifest properties (name, short name, theme/background color, display mode)
- **Install Prompt Control** — Toggle the A2HS (Add to Home Screen) install prompt from admin

**New Files:**
- `templates/unified/admin/pwa-settings.php` — PWA settings admin page

#### 🧠 Anti-Random-Guessing Quiz Protection
- **Timing-Based Penalty System** — Answers submitted too quickly are penalised to discourage random tapping
- **Minimum Time Threshold** — Admin-configurable minimum seconds required per question before an answer counts
- **Speed Penalty Scoring** — Points deducted for answers submitted under the threshold
- **Per-Question Timing Tracking** — Client tracks time-on-question and sends it with each answer submission
- **Admin Settings** — Configure threshold (seconds) and penalty (point deduction amount) in the quiz admin

### Changed
- Leaderboard items render as `<a>` tags when social profiles are enabled, falling back to `<div>` when disabled
- Community member cards show profile link button inside expanded details section
- Dashboard hero card includes social profile quick-access button
- Quiz answer submission now includes elapsed time for server-side validation
- Admin notifications page extended with in-app notification options and link fields

## [4.1.0] - 2026-02-20

### Added
- Admin option to send push notifications to specific individual users
- Per-user notification targeting in the admin notifications composer


### Added

#### 📖 Christian Quiz System
- **Quiz Categories** - Organized quizzes by Biblical topics and church teachings
- **AI-Powered Quiz Generation** - Generate quiz questions using AI with admin review
- **Timed Quizzes** - Configurable time limits with auto-submit
- **Points Rewards** - Earn points for quiz completion and perfect scores
- **Leaderboard Integration** - Quiz scores contribute to overall rankings
- **Admin Quiz Management** - Full CRUD for quizzes, questions, and categories
- **Quiz Attempts Tracking** - Configurable max attempts with scoring history
- **Category Browsing** - User-facing quiz browser with category filters

#### 💰 Point Sharing Settings
- **Admin Fee Configuration** - Configurable percentage fee on point transfers
- **Minimum/Maximum Transfer Limits** - Admin-defined transfer boundaries
- **Transfer History** - Full audit trail for point sharing transactions
- **Admin Point Sharing Dashboard** - Manage and monitor all point transfers

#### 🔔 OneSignal Push Notifications
- **OneSignal Integration** - Full Web Push SDK v16 integration
- **Admin Notification Center** - 5-tab management interface (Overview, Send, Subscribers, Log, Settings)
- **Custom In-App Prompt** - Branded subscription prompt with points incentive
- **Subscriber Tracking** - Device type, browser, subscription status analytics
- **Points for Subscribing** - Configurable points reward for enabling notifications
- **Auto-Trigger System** - Automatic notifications for new events, quizzes, and member approvals
- **Live Preview Composer** - Real-time notification preview before sending
- **Message Templates** - Pre-built Arabic notification templates
- **Notification Log** - Full history with delivery and click tracking
- **Test Connection** - OneSignal API connectivity verification

### Changed
- Enhanced dashboard with quiz stats and quick links
- Improved point sharing UI with fee calculations
- Updated profile page with push notification toggle

## [3.2.2] - 2026-02-06
### Fixed
- Bus seat booking failure when rebooking a previously cancelled seat (UNIQUE KEY constraint conflict)
- Admin move/swap seat failure when target or source seat had a cancelled booking

## [3.1.0] - 2026-02-06
### Added
- Admin functionality to move and swap bus seats with visual UI
- Seat occupant details popup on public event page
- Bus reservation quick links on admin event cards
- Visual indicators for seat moving and swapping status

### Changed
- Standardized user name display to "First Name + Middle Name" across all admin templates (Members, Points, attendance, etc.)
- Improved bus seat map interaction on mobile devices

## [3.0.1] - 2026-02-05
### Added
- Bus booking system with seats management
- Bus templates for quick event creation
- Points log for forbidden actions (system enforcement)
- Extended user fields for gamification
- Birthday notifications and points
- QR attendance tokens

### Changed
- Updated database schema for events and users
- Improved admin dashboard for bus management

### Fixed
- Migration issues with bus fees

## [2.2.0] - 2026-02-03

### Added
- Service instructions page with quiz and points reward
- Instructions shortcut icon on events page

### Changed
- Events page reorganized into main/forbidden, upcoming, and past sections
- Name display format updated to first + middle name across templates

### Fixed
- QR attendance generation restricted to same-day only
- Quiz option selection UI now highlights selected answer

---

## [2.1.0] - 2026-02-02

### Added
- Comprehensive project documentation
  - **README.md** with features overview and installation guide
  - **CHANGELOG.md** with complete version history
  - **CONTRIBUTING.md** with development guidelines
- Developer information and credentials
- GitHub repository links

### Changed
- Improved Arabic text in welcome screen
- Refined home template for better user experience
- Updated plugin header with author information and GitHub links

### Documentation
- Added detailed technical stack documentation
- Added project structure documentation
- Added development setup guidelines
- Added coding standards for contributors
- Added feature matrix and capabilities table

---

## [2.0.2] - 2026-02-02

### Fixed
- App route handling priority to prevent redirect conflicts
- Quiz question wording improvements
- AJAX URL handling in frontend components

### Changed
- Updated excuse card styling for better visual hierarchy
- Improved points display in user interface

---

## [2.0.1] - 2026-02-01

### Fixed
- Redirect loop issue on front page
- App routes not showing correctly
- Prioritized app handler before redirects

---

## [2.0.0] - 2026-02-01

### Added
- **PWA Support** - Full Progressive Web App capabilities
  - Service Worker with offline caching
  - Web App Manifest for installability
  - Install prompts for mobile and desktop
  - Custom app icons (72px to 512px)
- **Gamification System**
  - Birthday detection and rewards (gender-specific Arabic messages)
  - Profile completion tracking and rewards
  - Saint story quiz with points
  - Achievement system
- **Community Page** - New community hub for members
- **Extended User Profile Fields**
  - Detailed address fields (area, street, building, floor, apartment, landmark)
  - Google Maps URL for addresses
  - Gender field for personalized messages
  - Birth date for birthday rewards
  - WhatsApp number support
- **Expected Attendance Feature** - RSVP system for events
- **Block and Delete Member** functionality in admin panel
- **Profile Completion Congratulation Cards**

### Changed
- Major UI/UX overhaul with unified design system
- Improved Arabic text consistency across all templates
- Enhanced GitHub updater with better UI

### Security
- Improved AJAX nonce handling
- Better input validation

---

## [1.0.10] - 2026-01-31

### Fixed
- Migration execution issues
- Updater reliability improvements

---

## [1.0.9] - 2026-01-31

### Added
- Database diagnostics tools
- Database reset functionality
- Improved migration debugging tools

---

## [1.0.8] - 2026-01-31

### Fixed
- MySQL key length error (767 byte limit)
- Changed migration column to varchar(191) for UTF-8 compatibility

---

## [1.0.7] - 2026-01-30

### Improved
- Migration table creation with better error handling
- Fallback mechanisms for table creation

---

## [1.0.6] - 2026-01-30

### Added
- QR Attendance system
  - Secure time-limited tokens (5 minutes validity)
  - Cryptographic signature verification
  - QR Scanner interface for admins
- Expected Attendance table and functionality

---

## [1.0.5] - 2026-01-30

### Added
- Forbidden System (محروم)
  - Consecutive absence tracking
  - Yellow card / Red card system
  - Automatic forbidden status assignment
  - Admin management interface
- Late points configuration
- Late attendance status

---

## [1.0.4] - 2026-01-30

### Added
- Excuse System
  - Tiered excuse costs based on days before event
  - Admin approval workflow
  - Points deduction for submissions
- Excuse points configuration per event type

---

## [1.0.3] - 2026-01-29

### Added
- Events map URL support
- Location management for events

### Fixed
- Migration for events table to support map URLs

---

## [1.0.2] - 2026-01-29

### Added
- Points Log table
- Attendance penalties for mandatory events
- Leaderboard views (monthly/yearly/all-time)

---

## [1.0.1] - 2026-01-29

### Added
- Attendance table with status tracking
- Event types customization
- Points configuration per event type

---

## [1.0.0] - 2026-01-28

### Added
- Initial release
- **Member Management**
  - Registration with admin approval
  - Member profiles with church information
  - Egyptian phone validation
- **Event Management**
  - Event types (Liturgy, Meeting, Trip, Activity)
  - Event creation and management
  - Attendance tracking
- **Points System**
  - Attendance rewards
  - Absence penalties
  - Points history
- **Admin Panel**
  - Member approval queue
  - Event management interface
  - Attendance tracking interface
- **Mobile-First Design**
  - Responsive layout
  - Arabic RTL support
  - Cairo font integration
- **WordPress Integration**
  - Custom roles (sp_member, sp_church_admin)
  - Custom URL routes
  - AJAX API endpoints

---

## Version History Summary

| Version | Date | Highlights |
|---------|------|------------|
| 4.0.0 | 2026-02-10 | Quiz System, Point Sharing Settings, OneSignal Push Notifications |
| 3.2.2 | 2026-02-06 | Bus booking fix |
| 3.1.0 | 2026-02-06 | Bus seat move/swap, name standardization |
| 3.0.1 | 2026-02-05 | Bus booking system, gamification fields |
| 2.2.0 | 2026-02-03 | Service instructions, events reorganization |
| 2.1.0 | 2026-02-02 | Project documentation |
| 2.0.2 | 2026-02-02 | Bug fixes, UI improvements |
| 2.0.1 | 2026-02-01 | Redirect fixes |
| 2.0.0 | 2026-02-01 | PWA, Gamification, Major UI overhaul |
| 1.0.10 | 2026-01-31 | Migration & updater fixes |
| 1.0.9 | 2026-01-31 | Diagnostics tools |
| 1.0.8 | 2026-01-31 | MySQL compatibility |
| 1.0.7 | 2026-01-30 | Error handling |
| 1.0.6 | 2026-01-30 | QR Attendance |
| 1.0.5 | 2026-01-30 | Forbidden System |
| 1.0.4 | 2026-01-30 | Excuse System |
| 1.0.3 | 2026-01-29 | Map URLs |
| 1.0.2 | 2026-01-29 | Points & Leaderboard |
| 1.0.1 | 2026-01-29 | Attendance tracking |
| 1.0.0 | 2026-01-28 | Initial release |

---

[Unreleased]: https://github.com/micbwilliam/Saint-Porphyrius/compare/v4.0.0...HEAD
[4.0.0]: https://github.com/micbwilliam/Saint-Porphyrius/compare/v3.2.2...v4.0.0
[3.2.2]: https://github.com/micbwilliam/Saint-Porphyrius/compare/v3.1.0...v3.2.2
[3.1.0]: https://github.com/micbwilliam/Saint-Porphyrius/compare/v3.0.1...v3.1.0
[3.0.1]: https://github.com/micbwilliam/Saint-Porphyrius/compare/v2.2.0...v3.0.1
[2.2.0]: https://github.com/micbwilliam/Saint-Porphyrius/compare/v2.1.0...v2.2.0
[2.1.0]: https://github.com/micbwilliam/Saint-Porphyrius/compare/v2.0.2...v2.1.0
[2.0.2]: https://github.com/micbwilliam/Saint-Porphyrius/compare/v2.0.1...v2.0.2
[2.0.1]: https://github.com/micbwilliam/Saint-Porphyrius/compare/v2.0.0...v2.0.1
[2.0.0]: https://github.com/micbwilliam/Saint-Porphyrius/compare/v1.0.10...v2.0.0
[1.0.10]: https://github.com/micbwilliam/Saint-Porphyrius/compare/v1.0.9...v1.0.10
[1.0.9]: https://github.com/micbwilliam/Saint-Porphyrius/compare/v1.0.8...v1.0.9
[1.0.8]: https://github.com/micbwilliam/Saint-Porphyrius/compare/v1.0.7...v1.0.8
[1.0.7]: https://github.com/micbwilliam/Saint-Porphyrius/compare/v1.0.6...v1.0.7
[1.0.6]: https://github.com/micbwilliam/Saint-Porphyrius/compare/v1.0.5...v1.0.6
[1.0.5]: https://github.com/micbwilliam/Saint-Porphyrius/compare/v1.0.4...v1.0.5
[1.0.4]: https://github.com/micbwilliam/Saint-Porphyrius/compare/v1.0.3...v1.0.4
[1.0.3]: https://github.com/micbwilliam/Saint-Porphyrius/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/micbwilliam/Saint-Porphyrius/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/micbwilliam/Saint-Porphyrius/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/micbwilliam/Saint-Porphyrius/releases/tag/v1.0.0
