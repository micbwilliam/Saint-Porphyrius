<div align="center">
  <img src="assets/icons/icon-192x192.png" alt="Saint Porphyrius Logo" width="120" height="120">
  
  # Saint Porphyrius ⛪
  
  ### A Modern Church Community Management Platform
  
  [![WordPress Plugin](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg?logo=wordpress)](https://wordpress.org/)
  [![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple.svg?logo=php)](https://php.net)
  [![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)](LICENSE)
  [![Version](https://img.shields.io/badge/Version-2.1.0-orange.svg)](https://github.com/micbwilliam/Saint-Porphyrius/releases)
  [![PWA Ready](https://img.shields.io/badge/PWA-Ready-brightgreen.svg?logo=pwa)](https://web.dev/progressive-web-apps/)
  
  **A feature-rich, mobile-first Progressive Web App (PWA) built as a WordPress plugin for church community management with Arabic RTL support.**
  
  [Features](#-features) • [Installation](#-installation) • [Screenshots](#-screenshots) • [Documentation](#-documentation) • [Contributing](#-contributing)
  
</div>

---

## 📖 Overview

Saint Porphyrius is a comprehensive church community management system designed specifically for Arabic-speaking congregations. Built on WordPress, it provides a native app-like experience through PWA technology while offering powerful tools for member management, event tracking, attendance monitoring, and gamification features to encourage community engagement.

## 🎯 Key Highlights

- **🌐 Arabic-First Design** - Full RTL support with modern Arabic typography (Cairo font)
- **📱 Progressive Web App** - Installable on mobile devices with offline capabilities
- **🎮 Gamification System** - Points, leaderboards, achievements, and rewards
- **📊 Comprehensive Analytics** - Track attendance, engagement, and member activity
- **🔐 Role-Based Access** - Separate interfaces for members and administrators
- **⚡ Real-time Updates** - AJAX-powered interactions without page reloads

---

## ✨ Features

### 👥 Member Management
- **Member Registration** with admin approval workflow
- **Extended Profile System** with detailed personal information
- **Egyptian Phone Validation** (01xxxxxxxxx format)
- **Google Maps Integration** for address management
- **WhatsApp Integration** for quick communication
- **Profile Completion Tracking** with rewards

### 📅 Event Management
- **Event Types** with customizable icons and colors
- **Event Categories**: Liturgies, Meetings, Trips, Activities
- **Location Management** with map URLs
- **Mandatory vs Optional Events**
- **Maximum Attendee Limits**
- **Expected Attendance Registration**

### ✅ Attendance System
- **Multiple Status Types**: Attended, Late, Absent, Excused, Forbidden
- **QR Code Check-in** with time-limited secure tokens
- **Real-time QR Scanner** for admins
- **Automated Points Calculation**
- **Attendance History** per member

### 🏆 Points & Gamification
- **Points System** for attendance rewards and penalties
- **Leaderboard** with monthly/yearly/all-time views
- **Birthday Rewards** with gender-specific Arabic messages
- **Profile Completion Rewards**
- **Saint Story Quiz** with points rewards
- **Achievement Tracking**

### 📝 Excuse System
- **Tiered Excuse Costs** based on days before event
- **Admin Approval Workflow**
- **Points Deduction** for excuse submissions
- **Automatic Attendance Status Update**

### ⚠️ Discipline System (محروم)
- **Consecutive Absence Tracking**
- **Yellow Card / Red Card System**
- **Automatic Forbidden Status** assignment
- **Forbidden Event Penalties**
- **Admin Override Controls**

### 🛠️ Administration
- **Dashboard** with quick stats and actions
- **Pending Approvals Management**
- **Member Directory** with search and filters
- **Event CRUD Operations**
- **Attendance Management Interface**
- **Points Adjustment Tools**
- **Gamification Settings**
- **Forbidden System Configuration**

### 📱 Progressive Web App
- **Installable** on iOS/Android/Desktop
- **Offline Support** via Service Worker
- **App-like Navigation** with smooth transitions
- **Push-ready Architecture**
- **Custom App Icons** (72px to 512px)
- **Splash Screen Support**

---

## 🛠️ Technical Stack

### Backend
- **WordPress** 6.0+ (Plugin Architecture)
- **PHP** 7.4+ with OOP patterns
- **MySQL/MariaDB** with custom tables
- **Database Migrations** system (Laravel-inspired)
- **Singleton Pattern** for all handlers

### Frontend
- **Vanilla JavaScript** (ES6+)
- **CSS3** with CSS Variables
- **RTL-First Design**
- **Cairo Google Font** for Arabic typography
- **Dashicons** for iconography
- **Responsive Design** (Mobile-first)

### PWA Stack
- **Web App Manifest** (manifest.json)
- **Service Worker** with caching strategies
- **PWA Installer** component

### Security
- **WordPress Nonces** for AJAX requests
- **Cryptographic Tokens** for QR attendance
- **Role-based Permissions**
- **Input Sanitization** throughout
- **Prepared SQL Statements**

---

## 📦 Installation

### Requirements
- WordPress 6.0 or higher
- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.3+
- HTTPS enabled (required for PWA features)

### Standard Installation

1. **Download** the latest release from the [Releases](https://github.com/micbwilliam/Saint-Porphyrius/releases) page

2. **Upload** to your WordPress plugins directory:
   ```bash
   wp-content/plugins/Saint-Porphyrius/
   ```

3. **Activate** the plugin through WordPress admin:
   - Go to `Plugins → Installed Plugins`
   - Find "Saint Porphyrius" and click "Activate"

4. **Initialize** the database:
   - The plugin automatically creates required tables on activation
   - Migrations run automatically for updates

5. **Access** the app at:
   ```
   https://yourdomain.com/app/
   ```

### Manual Installation via Git

```bash
cd wp-content/plugins/
git clone https://github.com/micbwilliam/Saint-Porphyrius.git
```

Then activate through WordPress admin.

---

## 📁 Project Structure

```
Saint-Porphyrius/
├── saint-porphyrius.php      # Main plugin file
├── assets/
│   ├── css/
│   │   ├── main.css          # Base styles
│   │   ├── unified.css       # Design system
│   │   ├── pwa.css           # PWA-specific styles
│   │   └── admin.css         # Admin styles
│   ├── js/
│   │   ├── main.js           # Core JavaScript
│   │   ├── admin.js          # Admin JavaScript
│   │   ├── pwa-installer.js  # PWA installation
│   │   └── service-worker.js # Service Worker
│   ├── icons/                # PWA icons (72px-512px)
│   └── manifest.json         # Web App Manifest
├── includes/
│   ├── class-sp-admin.php           # Admin functionality
│   ├── class-sp-ajax.php            # AJAX handlers
│   ├── class-sp-attendance.php      # Attendance management
│   ├── class-sp-event-types.php     # Event type definitions
│   ├── class-sp-events.php          # Event management
│   ├── class-sp-excuses.php         # Excuse system
│   ├── class-sp-expected-attendance.php  # RSVP system
│   ├── class-sp-forbidden.php       # Discipline system
│   ├── class-sp-gamification.php    # Points & rewards
│   ├── class-sp-migrator.php        # Database migrations
│   ├── class-sp-points.php          # Points management
│   ├── class-sp-qr-attendance.php   # QR check-in
│   ├── class-sp-registration.php    # Member registration
│   ├── class-sp-updater.php         # GitHub auto-updater
│   └── class-sp-user.php            # User management
├── migrations/               # Database migration files
├── templates/
│   ├── app-wrapper.php       # Main app shell
│   ├── login.php             # Login page
│   ├── register.php          # Registration page
│   └── unified/              # App templates
│       ├── dashboard.php     # Member dashboard
│       ├── events.php        # Events list
│       ├── event-single.php  # Event details
│       ├── points.php        # Points history
│       ├── leaderboard.php   # Rankings
│       ├── profile.php       # User profile
│       ├── community.php     # Community page
│       ├── saint-story.php   # Saint story & quiz
│       └── admin/            # Admin templates
└── media/                    # Media assets
```

---

## 🔌 WordPress Integration

### Custom Roles

| Role | Capabilities |
|------|--------------|
| `sp_member` | Basic member access, view events, track points |
| `sp_church_admin` | Member management, event management, attendance |
| `administrator` | Full access to all features |

### Database Tables

| Table | Purpose |
|-------|---------|
| `wp_sp_pending_users` | Registration queue |
| `wp_sp_event_types` | Event type definitions |
| `wp_sp_events` | Event data |
| `wp_sp_attendance` | Attendance records |
| `wp_sp_points_log` | Points transaction history |
| `wp_sp_excuses` | Excuse submissions |
| `wp_sp_expected_attendance` | RSVP records |
| `wp_sp_forbidden_status` | Member discipline status |
| `wp_sp_forbidden_history` | Discipline history |
| `wp_sp_qr_attendance_tokens` | QR tokens |
| `wp_sp_migrations` | Migration tracking |

### URL Routes

| Route | Description |
|-------|-------------|
| `/app/` | Main app entry (dashboard) |
| `/app/login` | Login page |
| `/app/register` | Registration page |
| `/app/events` | Events listing |
| `/app/events/{id}` | Single event view |
| `/app/points` | Points history |
| `/app/leaderboard` | Rankings |
| `/app/profile` | User profile |
| `/app/community` | Community page |
| `/app/saint-story` | Saint story & quiz |
| `/app/admin/*` | Admin routes |

---

## 🌍 Localization

The plugin is fully localized for Arabic with:
- RTL layout support
- Arabic date formatting
- Gender-specific messages (male/female)
- Cultural adaptations for Egyptian context

### Supported Languages
- 🇪🇬 Arabic (Primary)
- 🇺🇸 English (Interface labels)

---

## 🔄 Automatic Updates

The plugin includes a built-in GitHub updater that:
- Checks for new releases automatically
- Shows update notifications in WordPress admin
- Supports one-click updates
- Maintains database migrations across versions

---

## 👨‍💻 Developer

<div align="center">

### Michael B. William
**Full Stack WordPress Developer**

[![Website](https://img.shields.io/badge/Website-michaelbwilliam.com-blue?style=for-the-badge&logo=google-chrome&logoColor=white)](https://michaelbwilliam.com/)
[![GitHub](https://img.shields.io/badge/GitHub-Profile-181717?style=for-the-badge&logo=github&logoColor=white)](https://github.com/micbwilliam/)

</div>

### Technical Expertise Demonstrated

- **WordPress Plugin Development** - Custom plugin architecture with best practices
- **PHP OOP** - Singleton patterns, class-based architecture, namespacing
- **Database Design** - Custom tables, migrations, prepared statements
- **Frontend Development** - Vanilla JS, CSS3, responsive design
- **PWA Development** - Service workers, manifests, offline support
- **RTL/i18n** - Arabic localization, bidirectional text support
- **Security** - Nonces, token-based auth, input sanitization
- **API Design** - RESTful AJAX endpoints with WordPress integration
- **Git Workflow** - Semantic versioning, release management

---

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

---

## 🙏 Acknowledgments

- Saint Porphyrius Orthodox Church community
- WordPress community for the excellent platform
- All contributors and testers

---

<div align="center">

**Made with ❤️ for the Orthodox Christian Community**

⭐ Star this repository if you find it useful!

</div>
