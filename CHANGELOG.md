# Changelog

All notable changes to the Saint Porphyrius plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned for v2.1.0
- Push notifications support
- Email notifications for events
- Multi-language admin interface
- Export attendance reports to Excel/PDF
- Enhanced analytics dashboard

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

[Unreleased]: https://github.com/YourRepo/Saint-Porphyrius/compare/v2.0.2...HEAD
[2.0.2]: https://github.com/YourRepo/Saint-Porphyrius/compare/v2.0.1...v2.0.2
[2.0.1]: https://github.com/YourRepo/Saint-Porphyrius/compare/v2.0.0...v2.0.1
[2.0.0]: https://github.com/YourRepo/Saint-Porphyrius/compare/v1.0.10...v2.0.0
[1.0.10]: https://github.com/YourRepo/Saint-Porphyrius/compare/v1.0.9...v1.0.10
[1.0.9]: https://github.com/YourRepo/Saint-Porphyrius/compare/v1.0.8...v1.0.9
[1.0.8]: https://github.com/YourRepo/Saint-Porphyrius/compare/v1.0.7...v1.0.8
[1.0.7]: https://github.com/YourRepo/Saint-Porphyrius/compare/v1.0.6...v1.0.7
[1.0.6]: https://github.com/YourRepo/Saint-Porphyrius/compare/v1.0.5...v1.0.6
[1.0.5]: https://github.com/YourRepo/Saint-Porphyrius/compare/v1.0.4...v1.0.5
[1.0.4]: https://github.com/YourRepo/Saint-Porphyrius/compare/v1.0.3...v1.0.4
[1.0.3]: https://github.com/YourRepo/Saint-Porphyrius/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/YourRepo/Saint-Porphyrius/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/YourRepo/Saint-Porphyrius/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/YourRepo/Saint-Porphyrius/releases/tag/v1.0.0
