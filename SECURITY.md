# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | ✅                 |

## Reporting a Vulnerability

If you discover a security vulnerability in `laravel-agent-native`, please report it responsibly.

### How to Report

1. **Do NOT post details publicly** (in issues, discussions, etc.)
2. **Email**: Send detailed report to **nataondev@proton.me**
3. **Include**:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if known)
   - Your contact information for follow-up

### What to Expect

- **Response**: Within 48 hours acknowledging receipt
- **Timeline**: 
  - Initial triage: 1-2 business days
  - Fix development: As needed (typically 7-14 days)
  - Public disclosure: Coordinated with reporter

### Security Review Process

1. Issue receives confidential label
2. Reproduce and validate the issue
3. Develop and test fix
4. Coordinate release timeline
5. Responsible disclosure announcement

## Current Security Features

### Authentication & Authorization

- **Kill switch**: `agent-native.http.enabled` configuration flag
- **Authorize hook**: Custom authorization callbacks via `agent-native.http.authorize`
- **HTTP middleware**: Users should apply their own auth middleware (Sanctum/session)
- **Rate limiting**: Configurable throttle settings

### Input Validation

- All tool calls validated before execution
- Structured error messages (no stack trace leakage)
- `additionalProperties: false` parity prevents mass assignment
- Parameter type coercion with comprehensive error reporting

### Dependency Security

- Regular dependency updates via Composer
- GitHub Actions audit checks
- No critical vulnerabilities detected in latest scan

## Security Best Practices for Users

When implementing with this package:

1. **Always protect endpoints** with authentication middleware
2. **Apply rate limiting** to prevent abuse
3. **Review generated schemas** for sensitive operations
4. **Test authorization hooks** thoroughly
5. **Monitor logs** for suspicious activity patterns
6. **Keep dependencies updated** via `composer update`

## Compliance

This project aims to align with:

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security](https://laravel.com/docs/security)
- [Composer Security Advisories](https://github.com/FriendsOfPHP/security-advisories)

## Acknowledgments

Security disclosures and patches are credited appropriately upon request.

---

**Last updated**: 2026-09-22
