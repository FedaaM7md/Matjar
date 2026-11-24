# Security — Matjar

> **This is a demo, not a production system.** Do not deploy it as-is, do not accept real payments with it, and do not reuse the demo credentials. The payment "gateway" is a local simulation.

## Reporting

Report suspected vulnerabilities privately to the project owner (Fedaa). Do not publish exploit details or real customer data. This repository is proprietary; see `LICENSE`.

## What is implemented

### Authentication & sessions
- Customer self-registration only; the registration controller always assigns the `customer` role (staff/admin accounts are seeded, never self-assigned).
- Login throttling: 5 attempts per 60 seconds per normalized email + IP (`RateLimiter`), with a generic failure message.
- `session()->regenerate()` after login/registration, session invalidation + CSRF token regeneration on logout.
- Inactive accounts (`users.is_active = false`) cannot authenticate or use admin routes.
- Passwords hashed with Laravel's hasher; passwords and remember tokens are hidden from serialization and never rendered.

### Authorization
- In-house RBAC: roles, permissions and a `permission_role` pivot. Every admin route carries `auth`, `can:staff` and a permission such as `can:orders.manage`.
- Form requests re-check `products.manage`, `coupons.manage`, `inventory.manage`, etc. (defense in depth).
- Customers can only see their own orders; order/invoice/confirmation access requires ownership, admin/staff access, or a valid temporary signed URL.
- Cart line updates/deletes verify the line belongs to the current session/user cart.
- Staff cannot access coupons, shipping settings or store settings; Admin can.

### Input, output & files
- All state-changing routes use Laravel CSRF protection (no exceptions).
- Server-side validation on every form (FormRequests / controller validation); Egyptian mobile numbers are normalized and validated.
- Blade escaping is used throughout; rich text is limited to `nl2br(e(...))`.
- Uploads: `image` + MIME whitelist (`jpg/jpeg/png/webp`), 4 MB per file (2 MB for demo accounts), max 8 per product, random storage paths on the `public` disk. SVG uploads are rejected. The seeded demo artwork is static, authored SVG written by the seeder, not user upload.
- Security headers: `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` restrictions, `Cross-Origin-Opener-Policy: same-origin`, `X-Robots-Tag: noindex` on admin/account areas, and HSTS when served over HTTPS.
- Content-Security-Policy: per-request nonce on the Vite script/style tags; `script-src 'self' 'nonce-…'` with **no `'unsafe-inline'` and no `'unsafe-eval'`** (the app uses Alpine's CSP build, which never calls `eval`/`new Function`), `style-src 'self' 'unsafe-inline'`, `frame-ancestors 'none'`, `object-src 'none'`, `base-uri 'self'`. Inline event handlers are replaced with Alpine directives. See `docs/SECURITY-REVIEW.md`.
- Demo accounts are forbidden from changing name/email/password via an explicit `403` in the account profile update action (not merely by hiding a form).
- No CDNs, trackers or analytics; fonts and assets are self-hosted.

### Commerce integrity
- Prices are integer piastres; all totals are recomputed server-side at checkout (client totals are display only).
- Stock is reserved inside a DB transaction using row locks; reservations cannot exceed `stock - reserved`. Checkout re-validates stock and coupon rules under transaction.
- Coupon usage is consumed under a row lock; per-customer limits are enforced by user/email.
- Payment callbacks are HMAC-SHA256 signed over `reference|outcome|amount_cents|expires_at`, compared with `hash_equals`, with expiry and amount checks; paid/pending states block replay.
- Guest order links use Laravel temporary signed URLs (7 days). Payment page references are 24-character random strings.

### Rate limits
- Login: 5/min/email+IP.
- Registration: 10/min/IP.
- Coupon attempts: 10/min/IP.
- Checkout: 10/min/IP.
- Payment callbacks: 20/min/IP.

## Known limitations / non-goals

- **Demo gateway only.** It does not contact a provider, store card data, refund, or reconcile. Do not enable it for real commerce.
- SQLite is a single-node demo database; no row-level locking guarantees under heavy parallel load. Use MySQL/PostgreSQL for production and run real concurrency tests.
- No password reset, email verification, 2FA, admin audit log (beyond the inventory ledger), or account lockout beyond login throttling.
- Alpine.js requires `'unsafe-eval'` in the CSP script directive. Inline scripts and inline event handlers are blocked, but a durable tightening would need Alpine's CSP build or a different front-end. Tracked in `ROADMAP.md`.
- Email is logged, not sent, in this demo (`MAIL_MAILER=log`).
- Demo accounts and demo data are public by design. Demo mode adds one-click login, hourly reset (`demo:reset`) and tighter upload limits; it is not a security boundary between visitors.

## Production hardening checklist (before any real use)

1. Set `APP_ENV=production`, `APP_DEBUG=false`, a unique `APP_KEY`, and HTTPS with `SESSION_SECURE_COOKIE=true`.
2. Replace SQLite with a managed MySQL/PostgreSQL instance; configure backups and least-privilege DB users.
3. Remove demo accounts/seeders and rotate all credentials.
4. Integrate a real payment provider with webhooks, idempotency, 3-D Secure and reconciliation; never expose the simulated gateway.
5. Add email verification, password reset, 2FA for staff and an admin audit log.
6. Serve uploads from object storage with signed URLs/CDN of your choice; add image re-encoding.
7. Add monitoring, error tracking, log retention and alerting; set up a WAF and rate limits at the edge.
8. Commission a penetration test and accessibility audit.
