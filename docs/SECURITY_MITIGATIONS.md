# Security mitigations (summary)

This document summarizes recommended mitigations implemented in this project and suggested additional practices to reduce threats.

## Password hashing ✅
- Symfony password hashing is set to `auto` in `config/packages/security.yaml`. This selects the best algorithm available (Argon2id preferred, bcrypt fallback).
- Recommendations:
  - Keep `php` up to date to support Argon2id.
  - Require strong passwords and enforce password policy on the frontend and backend (length & entropy).
  - Rotate hashing parameters gradually and migrate old hashes (Symfony handles rehashing when `needsRehash` is true).

## Login throttling / Rate limiter ✅
- A login limiter is configured in `config/packages/rate_limiter.yaml` (5 attempts / 1 minute).
- `security.yaml` firewall has `login_throttling` bound to that limiter so repeated failed logins are throttled.
- Recommendations:
  - Tune `limit` and `interval` for your user base (e.g., 5 attempts per minute, exponential backoff after repeated blocks).
  - Log throttled attempts for monitoring and alerting.

## Two-Factor Authentication (2FA) with SchebTwoFactorBundle (advice) ⚠️
- A sample configuration `config/packages/scheb_two_factor.yaml.dist` is included (not active by default).
- To enable: `composer require scheb/two-factor-bundle` and follow the bundle docs to wire the authenticators and routes.
- Mitigations and best practices:
  - Use time-based one-time passwords (TOTP) for the second factor.
  - Provide recovery codes, and optionally trusted devices (with short TTL).
  - Protect 2FA enrollment flows and limit attempts as for password logins.

## SQL Injection ✅
- Use Doctrine ORM / prepared statements and QueryBuilder to avoid concatenating raw SQL with user input.
- Example safe pattern (use parameters):

```php
$qb = $this->createQueryBuilder('n')
    ->where('n.patient = :patient')
    ->setParameter('patient', $patient);
```

- Recommendations:
  - Avoid `->getConnection()->executeQuery()` with interpolated values unless parameters are used.
  - Validate and sanitize inputs at the application boundary where appropriate.

## Cross-Site Scripting (XSS) ✅
- Twig auto-escaping is enabled by default; avoid marking untrusted content as `|raw`.
- Recommendations:
  - Escape any user-provided HTML before rendering.
  - When allowing limited HTML (e.g., user-provided content), sanitize it server-side (HTMLPurifier or similar) and restrict allowed tags/attributes.
  - Consider adding a Content Security Policy (CSP) header to restrict script sources.

## Cross-Site Request Forgery (CSRF) ✅
- CSRF protection is enabled in `config/packages/framework.yaml` and Symfony Forms include tokens automatically.
- For AJAX/JS requests, make sure to include the CSRF token in headers or request data and validate server-side.
- Additional measures:
  - Use `SameSite` cookie settings (configured in `framework.session.cookie_samesite`).
  - Invalidate CSRF tokens after use for highly sensitive actions if needed.

## Additional hardening tips
- Use HTTPS and HSTS headers in production.
- Set secure cookie flags (`Secure`, `HttpOnly`, `SameSite`).
- Keep dependencies up to date and run `composer audit` regularly.
- Monitor logs and set alerting for repeated authentication failures, spikes, and suspicious activity.

---
If you want, I can:
- install and configure `scheb/two-factor-bundle` and add a basic TOTP enrollment UI, or
- add an example middleware to add CSP headers automatically.

Which follow-up would you like next?