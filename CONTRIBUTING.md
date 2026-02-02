# Contributing to Saint Porphyrius

First off, thank you for considering contributing to Saint Porphyrius! It's people like you that make this project better for the church community.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Project Architecture](#project-architecture)
- [Coding Standards](#coding-standards)
- [Making Changes](#making-changes)
- [Submitting Changes](#submitting-changes)
- [Reporting Bugs](#reporting-bugs)
- [Feature Requests](#feature-requests)

---

## Code of Conduct

This project is maintained for a church community. Please be respectful, kind, and constructive in all interactions. We welcome contributors of all backgrounds and experience levels.

---

## Getting Started

### Prerequisites

- WordPress 6.0+
- PHP 7.4+ (8.0+ recommended)
- MySQL 5.7+ or MariaDB 10.3+
- Node.js (optional, for asset compilation)
- Git
- Local development environment (Local by Flywheel, XAMPP, MAMP, etc.)

### Fork the Repository

1. Fork the repo on GitHub
2. Clone your fork locally:
   ```bash
   git clone https://github.com/YOUR_USERNAME/Saint-Porphyrius.git
   ```
3. Add upstream remote:
   ```bash
   git remote add upstream https://github.com/ORIGINAL_OWNER/Saint-Porphyrius.git
   ```

---

## Development Setup

### 1. Install WordPress Locally

Use your preferred local development tool:
- [Local by Flywheel](https://localwp.com/) (Recommended)
- XAMPP
- MAMP
- Docker with WordPress

### 2. Install the Plugin

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/YOUR_USERNAME/Saint-Porphyrius.git
```

### 3. Activate the Plugin

Navigate to WordPress admin → Plugins → Activate "Saint Porphyrius"

### 4. Enable Debug Mode

Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
```

---

## Project Architecture

### Directory Structure

```
Saint-Porphyrius/
├── saint-porphyrius.php    # Main plugin file (entry point)
├── includes/               # PHP classes (backend logic)
├── templates/              # PHP templates (views)
├── assets/                 # Frontend assets
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript files
│   └── icons/             # PWA icons
├── migrations/             # Database migration files
└── media/                  # Media assets
```

### Design Patterns

#### Singleton Pattern
All handler classes use the Singleton pattern:

```php
class SP_Example {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Initialize
    }
}
```

#### Database Handler Pattern
Each feature has a dedicated handler class:

```php
class SP_Events {
    private $table_name;
    
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sp_events';
    }
    
    public function get($id) { /* ... */ }
    public function create($data) { /* ... */ }
    public function update($id, $data) { /* ... */ }
    public function delete($id) { /* ... */ }
}
```

### Database Migrations

Migrations follow a timestamp-based naming convention:

```
YYYY_MM_DD_NNNNNN_description.php
```

Example migration:
```php
<?php
// migrations/2026_02_01_000001_add_example_column.php

if (!defined('ABSPATH')) exit;

return new class {
    public function up() {
        global $wpdb;
        $table = $wpdb->prefix . 'sp_example';
        $wpdb->query("ALTER TABLE {$table} ADD COLUMN example VARCHAR(255)");
    }
    
    public function down() {
        global $wpdb;
        $table = $wpdb->prefix . 'sp_example';
        $wpdb->query("ALTER TABLE {$table} DROP COLUMN example");
    }
};
```

---

## Coding Standards

### PHP

Follow [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/):

```php
// ✅ Good
function sp_get_user_points( $user_id ) {
    $points = get_user_meta( $user_id, 'sp_points', true );
    return absint( $points );
}

// ❌ Bad
function getUserPoints($user_id){
    return get_user_meta($user_id, 'sp_points', true);
}
```

### JavaScript

Use ES6+ features with vanilla JS (no jQuery required for new code):

```javascript
// ✅ Good
const handleSubmit = async (e) => {
    e.preventDefault();
    const response = await fetch(spApp.ajaxUrl, {
        method: 'POST',
        body: formData
    });
};

// ❌ Avoid (unless necessary)
jQuery.ajax({
    // ...
});
```

### CSS

Use CSS custom properties and follow BEM-like naming:

```css
/* ✅ Good */
.sp-card {
    --card-padding: 1rem;
    padding: var(--card-padding);
}

.sp-card__header {
    font-weight: bold;
}

.sp-card--featured {
    border-color: var(--sp-primary);
}
```

### Arabic/RTL Considerations

- Always use logical properties when possible:
  ```css
  /* ✅ Good - works for both LTR and RTL */
  margin-inline-start: 1rem;
  padding-inline-end: 0.5rem;
  
  /* ❌ Avoid - direction-specific */
  margin-left: 1rem;
  padding-right: 0.5rem;
  ```

- Test all UI changes in RTL mode

---

## Making Changes

### Branch Naming

Use descriptive branch names:
- `feature/add-push-notifications`
- `fix/qr-scanner-timeout`
- `refactor/points-calculation`
- `docs/api-documentation`

### Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add push notification support
fix: resolve QR scanner timeout issue
docs: update API documentation
refactor: improve points calculation performance
style: format code according to standards
test: add unit tests for attendance handler
```

### Version Bumping

When preparing a release:

1. Update version in `saint-porphyrius.php`:
   ```php
   * Version: X.Y.Z
   ```
   
2. Update constant:
   ```php
   define('SP_PLUGIN_VERSION', 'X.Y.Z');
   ```

3. Update `CHANGELOG.md` with new changes

4. Create git tag:
   ```bash
   git tag -a vX.Y.Z -m "Version X.Y.Z"
   git push origin vX.Y.Z
   ```

---

## Submitting Changes

### Pull Request Process

1. **Update your fork**:
   ```bash
   git fetch upstream
   git checkout main
   git merge upstream/main
   ```

2. **Create a feature branch**:
   ```bash
   git checkout -b feature/your-feature
   ```

3. **Make your changes** following the coding standards

4. **Test thoroughly**:
   - Test on mobile devices
   - Test RTL layout
   - Test with different user roles
   - Run any existing tests

5. **Commit your changes** with descriptive messages

6. **Push to your fork**:
   ```bash
   git push origin feature/your-feature
   ```

7. **Open a Pull Request** against the `main` branch

### PR Checklist

- [ ] Code follows project coding standards
- [ ] Self-reviewed the code
- [ ] Tested on mobile and desktop
- [ ] Tested RTL layout
- [ ] Updated documentation if needed
- [ ] Added entry to CHANGELOG.md
- [ ] No PHP errors or warnings

---

## Reporting Bugs

### Before Submitting

1. Search existing issues to avoid duplicates
2. Update to the latest version
3. Disable other plugins to isolate the issue

### Bug Report Template

```markdown
**Description**
A clear description of the bug.

**Steps to Reproduce**
1. Go to '...'
2. Click on '...'
3. See error

**Expected Behavior**
What you expected to happen.

**Actual Behavior**
What actually happened.

**Screenshots**
If applicable, add screenshots.

**Environment**
- WordPress version:
- PHP version:
- Browser:
- Device:
```

---

## Feature Requests

We welcome feature suggestions! Please open an issue with:

1. **Use Case**: Describe the problem you're trying to solve
2. **Proposed Solution**: Your idea for the feature
3. **Alternatives**: Other solutions you've considered
4. **Priority**: How important is this for your use case?

---

## Questions?

Feel free to open an issue with the `question` label or reach out to the maintainer.

---

## Recognition

Contributors will be acknowledged in the project. Thank you for helping improve Saint Porphyrius! 🙏
