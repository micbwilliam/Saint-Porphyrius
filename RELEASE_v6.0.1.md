# Release v6.0.1

## Overview

Version 6.0.1 is a **patch release** that fixes five bugs in the Bus Waiting List system introduced in v6.0.0 and adds admin tooling to manage the queue.

---

## Bug Fixes

### 🐛 Re-join after cancel failed silently
The `wp_sp_bus_waiting_list` table has a `UNIQUE KEY (event_id, user_id)`. The previous code soft-cancelled rows by setting `status = 'cancelled'`, leaving the row intact. A subsequent re-join attempt would fail at the `INSERT` step with a duplicate-key DB error that surfaced as "فشل في التسجيل" with no further explanation.

**Fix:** `leave_waiting_list()` now **deletes** the row entirely. `join_waiting_list()` also defensively deletes any prior rows (of any status) for the same user + event before inserting the fresh entry, making re-joins always safe.

---

### 🐛 No automatic seat assignment after cancellation (cron missing)
`process_waiting_list()` was only called from within `cancel_booking()`. If the processor skipped a queued user (insufficient points or no gender-compatible seat), no retry ever happened — the freed seat was effectively lost until another cancellation occurred.

**Fix:** A new WP-Cron event `sp_process_bus_waiting_lists` is scheduled every **5 minutes**. It iterates every upcoming event that has an active queue and calls `process_waiting_list()` in a bounded loop, breaking out if no progress is made (e.g. everyone skipped). Admins can also trigger it immediately from the bus-bookings admin page.

---

### 🐛 Bus booking form visible with vacant seats while waiting list was active
When `process_waiting_list()` skipped all queued users, seats became free while people were still waiting. The event page would then show the normal seat-selection UI, allowing anyone to grab seats that should belong to the queue.

**Fix (two layers):**
- **Server:** `book_seat()` now checks `has_active_waiting_list()` before proceeding. Anyone not on the queue receives the error: *"يوجد قائمة انتظار حالياً — انضم وسيتم تعيين مقعدك تلقائياً"*.
- **Template:** `event-single.php` now enters waiting-list mode when **either** all buses are full **or** an active queue exists (`$waiting_list_mode = $is_fully_booked || $has_queue`). The copy adapts to explain the reason.

---

### 🐛 Position counter used all rows (including cancelled/skipped)
New positions were calculated as `MAX(position) + 1` across **all** statuses, so cancelled/skipped entries would bloat position numbers (e.g. user #7 when only 2 people were actually waiting).

**Fix:** Position queries now filter `AND status = 'waiting'`. A new `resequence_waiting_list()` method re-numbers active entries to a clean 1..N sequence after every change.

---

### 🐛 Cron never started automatically
No cron event was scheduled on plugin activation.

**Fix:** `wp_schedule_event` is called on activation and via an `init` guard (`maybe_schedule_cron`). The event is unscheduled cleanly on deactivation.

---

## New Feature: Admin Waiting List Management

The **Bus Bookings** admin page (`/app/admin/bus-bookings?bus_id=X`) now shows a **قائمة الانتظار** panel when members are queued for the event:

- **▲ / ▼ buttons** — move an entry up or down one position
- **Position number input** — type any target position and press Enter to jump there
- **🗑️ Remove button** — delete an entry and resequence the remainder
- **🔄 معالجة الآن** — manually run the waiting-list processor for this event (books all eligible people in order, within one click)

All operations are backed by three new cap-checked AJAX endpoints:
- `sp_admin_move_waiting_entry`
- `sp_admin_remove_waiting_entry`
- `sp_admin_process_waiting_list`

---

## Files Changed

| File | Change |
|------|--------|
| `includes/class-sp-bus.php` | Fixed `join_waiting_list`, `leave_waiting_list`; new `resequence_waiting_list`, `has_active_waiting_list`, `admin_move_waiting_entry`, `admin_remove_waiting_entry`, `cron_process_waiting_lists`; waiting-list guard in `book_seat` |
| `includes/class-sp-ajax.php` | Three new admin AJAX handlers registered and implemented |
| `saint-porphyrius.php` | Custom cron schedule + event registered; version bumped to 6.0.1 |
| `templates/unified/event-single.php` | `$waiting_list_mode` logic covers both full-bus and active-queue cases |
| `templates/unified/admin/bus-bookings.php` | Admin waiting-list management panel + JS |

---

## Upgrade Notes

- **No database migration required** — existing `wp_sp_bus_waiting_list` table is unchanged.
- **Cron registers automatically** on first admin page load after update (no reactivation needed, thanks to the `init` guard).
- **Backwards-compatible** — no breaking changes to any existing feature.

---

**Full Changelog:** `v6.0.0...v6.0.1`
