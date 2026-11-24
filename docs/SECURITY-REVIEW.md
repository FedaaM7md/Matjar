# Security review — Matjar (release 1.1)

Date: 2026-11-24 · Owner: Fedaa Mohamed · Scope: the whole Laravel application.

This is a **demo**, not a production system. The review below records what was
checked, what was fixed in release 1.1, and what remains a known risk.

## 1. Threat model

Assets: demo catalog data, demo customer details, order records, the admin
panel, and the simulated payment flow. There is no real money, no real card
data and no production PII.

Actors:

- Anonymous shoppers (guest cart, checkout, order lookup via signed link).
- Demo visitors (one-click shared demo accounts: Admin / Staff / Customer).
- Seeded staff/admin users.
- Automated scanners / bots.

Primary threats: XSS (stored/reflected), CSRF, broken access control / IDOR
between roles and records, price manipulation, overselling, upload abuse,
session fixation, injection, and abuse of the simulated payment callback.
Because demo accounts are public, the demo is **not** a confidentiality
boundary between visitors.

## 2. What was checked

- Every route and controller: authentication and authorization.
- Every form: CSRF, server-side validation, mass-assignment exposure.
- Every Blade output of user/database text: escaping vs raw output.
- Uploads: MIME/type, size, destination, filename.
- Payment callback: signature, amount, outcome, expiry, replay.
- Inventory and coupon math under concurrent checkout.
- Response headers and the new CSP.
- Dependencies (`composer audit`, `npm audit`).

## 3. Findings fixed in release 1.1

| # | Finding | Fix |
|---|---|---|
| 1 | No Content-Security-Policy | Added a per-request nonce CSP via `SecurityHeaders`, including on error responses (exception `respond` hook). Inline event handlers (`onclick/onsubmit/onchange`) were replaced with Alpine directives that use `window.confirm` through a shared `confirmForm` component. **Round 1 follow-up:** switched to `@alpinejs/csp` so the CSP now has no `'unsafe-eval'`; a test asserts this. |
| 2 | Missing `Cross-Origin-Opener-Policy` and HSTS | Added COOP `same-origin` and HSTS when the request is HTTPS. |
| 3 | Demo accounts could be created by anyone | Explicit `is_demo` flag; only seeded demo users are flagged; demo login is role-limited and throttled (`20/min`). |
| 4 | No demo reset policy | Added `demo:reset` command (guarded by `DEMO_MODE`, production `--force`), scheduled hourly, plus a persistent demo banner. |
| 5 | Upload limits identical for demo and real staff | Demo accounts get a 2 MB per-image limit (`matjar.demo.upload_max_kb`); staff keep 4 MB. |
| 6 | `Permissions-Policy` did not cover payment | Added `payment=()`. |
| 7 | Page titles / seeded data used the owner name | Brand is now "Matjar"; demo product brands are neutral Egyptian names. Tests assert no owner name in SEO strings. |
| 8 | Demo restrictions relied on the absence of a profile screen | Added a real `PUT /account/profile` action that returns **403 for demo accounts** (and updates name/email/password with current-password confirmation for normal customers). Tested. |

## 4. Verified controls (already strong, re-tested)

- CSRF on every state-changing route; no `{!! !!}` of user input (only
  `nl2br(e(...))` on product descriptions).
- RBAC: `auth` + `can:staff` + per-action permission on every admin route;
  FormRequests re-check permissions; staff cannot reach coupons/settings.
- IDOR: customers see only their own orders; invoices/confirmation require
  ownership, staff access, or a valid 7-day signed URL.
- Login throttling (5/min/email+IP), session regeneration on login/register,
  invalidation + CSRF rotation on logout, inactive users blocked.
- Uploads: `image` + `mimes:jpg,jpeg,png,webp` + size cap, random storage paths,
  SVG uploads rejected (demo artwork is seeder-authored SVG only).
- Payment simulator: HMAC-SHA256 over `reference|outcome|amount_cents|expires_at`,
  `hash_equals`, expiry/amount checks, replay blocked.
- Inventory: reservation under DB transaction + row locks; server recomputes
  every total; coupons re-validated at checkout.
- Rate limits on login, registration, coupon, checkout, payment callbacks and
  demo login.

## 5. Remaining risks (documented, not hidden)

1. **CSP `'unsafe-eval'` removed.** The app now uses the `@alpinejs/csp` build, so the CSP is `script-src 'self' 'nonce-…'` only (no `unsafe-eval`, no `unsafe-inline` for scripts). Components read configuration from `data-*` attributes instead of evaluating complex expressions.
2. **Shared demo accounts.** All demo visitors share three accounts. Data is public and resets hourly; do not treat it as private.
3. **Simulated payments only.** No real gateway, refunds or reconciliation.
4. **SQLite single writer.** Row locks protect overselling in tests, but heavy parallel load was not load-tested; production needs MySQL/PostgreSQL.
5. **No password reset / email verification / 2FA / admin audit log** beyond the inventory ledger. Demo accounts are explicitly blocked from the profile-update action (403); normal customers can update name/email/password with current-password confirmation.
6. **No malware scan or image re-encode**; uploads are MIME/size validated only.
7. **Mail is logged, not sent.**

## 6. Dependency audit

Run and recorded in `TESTING.md`: `composer audit` and `npm audit`. No known
vulnerable direct dependencies at the time of writing; see `THIRD-PARTY-NOTICES.md`
for licences (all permissive: MIT / BSD / OFL). No GPL/AGPL/LGPL.

## 7. How the headers are applied

`App\Http\Middleware\SecurityHeaders` generates a nonce, hands it to Vite
(`Vite::useCspNonce`) so the built `<script>`/`<link>` tags carry it, sets the
header suite, and the same `apply()` method is invoked from the exception
`respond` hook so 404/419/500 pages are covered too. Verified by
`tests/Feature/SecurityHardeningTest.php`.
