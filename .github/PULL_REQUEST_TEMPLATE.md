## Description

<!-- Brief description of the changes in this PR -->

## Related Issue

<!-- Link to the issue this PR addresses: Fixes #123, Closes #456 -->

## Type of Change

- [ ] Bug fix (non-breaking change that fixes an issue)
- [ ] New feature (non-breaking change that adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Database migration (new migration file added)
- [ ] Documentation update
- [ ] Refactor (no functional change)

## Changes Made

<!-- List the specific changes made in this PR -->

-
-
-

## Checklist

### General
- [ ] My code follows the project's coding standards (`sp_` prefix, WordPress PHP standards)
- [ ] I have tested this on mobile (primary target)
- [ ] All user-facing text is in Arabic
- [ ] RTL layout is preserved

### PHP
- [ ] All SQL queries use `$wpdb->prepare()`
- [ ] All user inputs are sanitized (`sanitize_text_field`, `sanitize_email`, etc.)
- [ ] All outputs are escaped (`esc_html`, `esc_attr`, `esc_url`)
- [ ] Nonce verification is present on any new AJAX handlers
- [ ] Capability checks (`current_user_can()`) are in place for admin operations

### Database (if applicable)
- [ ] New migration file created in `migrations/` with proper naming (`YYYY_MM_DD_NNNNNN_description.php`)
- [ ] Migration includes both `up()` and `down()` methods
- [ ] UNIQUE keys on utf8mb4 use `varchar(191)`

### Routes (if applicable)
- [ ] Rewrite rule added in `saint-porphyrius.php::add_rewrite_rules()`
- [ ] Route case added in `templates/app-wrapper.php`
- [ ] Title added in `sp_get_page_title()`
- [ ] Route added to correct access array (`protected_routes` / `admin_routes` / `guest_routes`)

### JavaScript
- [ ] No jQuery used (vanilla ES6+ only for new code)
- [ ] AJAX requests use nonce validation

## Screenshots / Screen Recording

<!-- If applicable, add screenshots showing the change (especially for UI changes) -->

## Testing Notes

<!-- How was this tested? Any specific scenarios to verify? -->
