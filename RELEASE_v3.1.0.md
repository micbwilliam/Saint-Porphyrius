# Release v3.1.0

## Overview
This release focuses on enhancing the bus booking management system and standardizing user name displays across the administrative interface. The key highlight is the new "Move & Swap" functionality for bus seats, designed with a mobile-first approach.

## Key Features

### 🔄 Admin Bus Seat Management
- **Move Seat**: Admins can now move a passenger to any empty seat directly from the visual seat map.
- **Swap Seats**: Admins can swap seats between two passengers. Handled intelligently with a 3-step safe transaction process to prevent database conflicts.
- **Mobile-First UI**: "Tap-to-move" interface instead of drag-and-drop, making it easy to manage bookings on phones and tablets.
- **Visual Feedback**: visual indicators for source seat (orange), target seats (green), and swap targets (purple).

### 🏷️ Name Display Standardization
- All admin interfaces (Members, Points, Attendance, Excuses, Forbidden, Bus Bookings) now consistently display names as `First Name Middle Name`.
- Fallbacks to Arabic Name or Display Name if first/middle are missing.

### 🚌 Event & Bus Enhancements
- **Bus Quick Links**: Admin event library cards now show quick links to bus booking pages with live occupancy stats.
- **Public Seat Details**: Users can now tap occupied seats on the public event page to see who is sitting there (occupant name popup).

## Technical Details
- Added `move_seat` method to `SP_Bus` class with unique constraint handling.
- New AJAX endpoint `sp_move_bus_seat`.
- Updated `SP_Bus::get_bus_bookings` to fetch first and middle names efficiently.
- Frontend CSS animations for seat moving modes.

## Installation
1. Deactivate the current version.
2. Upload the new plugin files.
3. Activate the plugin.
4. Verify bus booking functionality in the admin dashboard.
