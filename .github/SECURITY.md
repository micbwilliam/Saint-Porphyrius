# Security Policy

## Supported Versions

| Version | Supported          |
|---------|--------------------|
| 5.0.x   | :white_check_mark: |
| 4.0.x   | :white_check_mark: |
| < 4.0   | :x:                |

## Reporting a Vulnerability

**Do not open a public GitHub issue for security vulnerabilities.**

If you discover a security vulnerability in Saint Porphyrius, please report it responsibly:

1. **Email**: Send details to the repository owner via GitHub private contact
2. **GitHub Security Advisories**: Use the [Security Advisories](https://github.com/micbwilliam/Saint-Porphyrius/security/advisories/new) feature to report privately

### What to include

- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if you have one)

### Response Timeline

- **Acknowledgment**: Within 48 hours
- **Assessment**: Within 1 week
- **Fix**: Depending on severity, within 1-4 weeks

## Security Practices

This plugin follows these security practices:

- All SQL queries use `$wpdb->prepare()` (no raw queries)
- User input is sanitized via `sanitize_text_field()`, `sanitize_email()`, etc.
- Output is escaped with `esc_html()`, `esc_attr()`, `esc_url()`
- Nonce verification on all AJAX endpoints
- Capability checks (`current_user_can()`) on admin operations
- Passwords hashed with `wp_hash_password()`
- QR tokens are cryptographically random, expire after 5 minutes, and are single-use
- HTTPS is required for PWA and push notification features
