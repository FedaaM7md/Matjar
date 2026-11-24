# Decisions — Fedaa Matjar

Short ADR-style notes for decisions that shaped the codebase, including trade-offs.

## 1. Store money as integer minor units (piastres)

**Decision:** every price/total column is `*_cents` (`unsignedInteger`), formatted via `App\Support\Money`.

**Why:** floating point currency bugs are unacceptable in checkout. EGP has two decimals; all arithmetic (subtotal, discounts, shipping, totals) stays integer. User input in EGP major units is converted once at validation with `round(x * 100)`.

**Trade-off:** admin and API consumers must convert deliberately; helper methods (`toMinor`, `toMajor`, `withCurrency`) are used consistently.

## 2. Reserve stock transactionally; track a stock state on orders

**Decision:** checkout reserves (`reserved += qty`) inside a locked transaction; confirmation/payment commits (`stock -= qty`, `reserved -= qty`); cancellation/return releases or restores. `orders.stock_state` is `reserved|committed|released`.

**Why:** prevents overselling without decrementing stock before the order is real, and makes stock consequences of each state transition explicit and auditable.

**Trade-off:** SQLite is single-writer; true parallel load requires MySQL/PostgreSQL and dedicated concurrency tests (roadmap). Every mutation must go through `InventoryService`, which is enforced by convention and tests.

## 3. In-house RBAC instead of a package

**Decision:** roles, permissions, `permission_role`, `Gate` definitions and `can:` middleware are owned by the project.

**Why:** the task requires an own RBAC; it keeps dependencies lean and the permission set small and explicit. The permission catalogue lives in `App\Support\Permissions`.

**Trade-off:** no UI role editor yet (roles are seeded); adding permissions requires code changes.

## 4. Session-based locale, not URL prefixes

**Decision:** locale lives in the session; `POST /locale/{locale}` switches it. URLs are identical in both languages.

**Why:** simpler routing, no duplicate route trees, and the storefront is a single product rather than a content site needing localized slugs/SEO.

**Trade-off:** links are not independently shareable per language and caching by locale would need `Vary`/separate caches. Localized slugs and prefix routing are roadmap items if SEO becomes a requirement.

## 5. Simulated gateway with HMAC-signed outcomes

**Decision:** no external gateway. A local page posts one of three outcomes; each posts `reference|outcome|amount|expires` signed with HMAC-SHA256 using `APP_KEY`.

**Why:** satisfies the "success/fail/cancel with signed callback" requirement without keys or real money, while still exercising signature verification, expiry, tampering and replay defenses.

**Trade-off:** it is not a payment integration and cannot be used for real sales. A real integration would add webhooks, idempotency keys, refunds and reconciliation.

## 6. Per-year order counter table with row lock

**Decision:** `order_counters(year, last_number)` is incremented under `lockForUpdate()` inside the checkout transaction; format `ORD-YYYY-NNNN`.

**Why:** sequential, human-friendly numbers without `MAX(id)` races; numbering resets each year.

**Trade-off:** a counter row is a contention point at very high volume; a sequence or ULID would scale better.

## 7. SQLite for the demo

**Decision:** SQLite file database, with `RefreshDatabase` using in-memory SQLite in tests.

**Why:** zero setup, portable on Windows, sufficient for a single-node demo and CI-style test runs. Laravel's schema builder and query builder keep most SQL portable.

**Trade-off:** writer serialization, limited concurrent checkout throughput, and no `SKIP LOCKED` semantics. MySQL/PostgreSQL is a production prerequisite.

## 8. Coupon values: basis points and cents

**Decision:** percent coupons store `value` in basis points (1000 = 10%); fixed coupons store piastres. Caps and minimums are also piastres.

**Why:** avoids floats, supports fractions like 12.5%, and makes rounding explicit (`round(subtotal * value / 10000)`).

**Trade-off:** admin forms convert between percent/EGP and storage, which must stay consistent (`CouponRequest::couponData()`).

## 9. Free shipping evaluated after discount

**Decision:** shipping is quoted on `subtotal - discount`, not the raw subtotal.

**Why:** avoids the loophole where a coupon reduces the amount paid while granting free shipping on the pre-discount amount. The rule is surfaced in the cart/checkout UI and tested.

**Trade-off:** a customer can lose free shipping by applying a coupon. The UI shows the remaining amount needed; an alternative could compare both and choose the cheaper outcome (not implemented).

## 10. Signed temporary URLs for guest order access

**Decision:** guests receive a 7-day temporary signed URL for confirmation/show/invoice; authenticated owners and staff use normal URLs; all order routes re-check ownership.

**Why:** guests have no account but must be able to view their order and invoice; signed URLs avoid guessable IDs or session-only access.

**Trade-off:** links expire and can be shared by whoever holds them; this is acceptable for a demo but production might add email-bound tokens or OTP.

## 11. Self-hosted Rubik + Noto Sans Arabic, no CDN

**Decision:** install `@fontsource-variable/rubik` and `@fontsource-variable/noto-sans-arabic` from npm (SIL OFL) and bundle them with Vite; Rubik is the display face, Noto Sans Arabic the body face. The old Cairo files were removed. System fallbacks remain in the `font-sans` / `font-display` stacks.

**Why:** the rules forbid CDNs/trackers and require self-hosted OFL fonts. Rubik gives confident heavy headings (with Arabic coverage) and Noto Sans Arabic gives strong Arabic body text — matching the editorial identity.

**Trade-off:** fonts are part of the build output (`public/build/assets/*.woff2`) rather than committed source; rebuild after `npm install`.

## 12. Blade + Alpine, no SPA

**Decision:** server-rendered Blade with small Alpine components for menus, variant selection, quantity steppers and checkout totals.

**Why:** fast first paint, no API duplication, SEO-friendly order/product pages, and low dependency surface. Totals in Alpine are display-only; the server recalculates.

**Trade-off:** complex interactions (live search, dashboards) would be less fluid than a full JS app; that is acceptable for this scope.

## 13. Snapshot order line data

**Decision:** `order_items` copy product names, variant label, SKU and unit price; orders copy customer and governorate names.

**Why:** historical invoices must not change when a product is renamed, repriced or deleted. Variant/product FKs are nullable on delete for the same reason.

**Trade-off:** more columns and a deliberate projection step at checkout; reports group by snapshot names.

## 14. Demo data is seeded and clearly labelled

**Decision:** demo seeder creates users, a 33-product clothing catalogue, coupons and 60+ orders; orders carry a `DEMO ORDER` note and the admin shows a small demo notice, while the public storefront keeps only a tiny footer note. Seeders no-op in `APP_ENV=production`. Product imagery is original per-product SVG illustration, not photography.

**Why:** the reviewer can exercise dashboards/reports immediately without presenting invented data as real.

**Trade-off:** demo stock is consumed by demo orders; `migrate:fresh --seed` is the clean baseline. Demo database must never be treated as production.

## 15. Pest for feature tests

**Decision:** Pest 4 + `pest-plugin-laravel` on top of PHPUnit 12 for expressive feature tests.

**Why:** concise HTTP tests with Laravel helpers; the suite is grouped by domain and remains readable.

**Trade-off:** an extra dev dependency; it is MIT-licensed and listed in `THIRD-PARTY-NOTICES.md`.

## 16. Security headers with a nonce Content-Security-Policy

**Decision:** `SecurityHeaders` generates a per-request nonce, hands it to Vite (`Vite::useCspNonce`) so the bundled `<script>`/`<link>` tags carry it, and sets a CSP plus `nosniff`, `DENY`, referrer/permissions policies, COOP and (over HTTPS) HSTS. The same `apply()` method runs from the exception `respond` hook so error pages are covered. Inline event handlers were replaced with Alpine directives.

**Why:** release 1.1 requires a CSP without inline scripts. Nonces keep the built assets working while blocking inline `<script>` and inline handlers. The app uses `@alpinejs/csp` (the eval-free Alpine build) and passes component configuration through `data-*` attributes, so the `script-src` directive needs only `'self'` and the nonce — no `'unsafe-inline'` and no `'unsafe-eval'`.

**Trade-off:** `style-src` keeps `'unsafe-inline'` for dynamic style bindings and chart heights; that is scoped to styles only. The CSP Alpine build supports a subset of expressions, so interactive logic lives in `Alpine.data` components.

## 17. No fake reviews, ratings or sales counters

**Decision:** product pages show no stars/review counts unless real data exists; no invented "sold X times" badges.

**Why:** the rules forbid fake data presented as real. Demo notices are explicit and product illustrations are labelled.

**Trade-off:** the UI is less "marketing rich"; real reviews would be a future feature with moderation.

## 18. Demo mode behind `DEMO_MODE`, shared dataset that resets

**Decision:** when `DEMO_MODE=true`, a `/demo` role picker and `/demo/login` route sign visitors in as seeded `is_demo` accounts with one click (throttled). A persistent banner explains that data resets. `demo:reset` wipes and reseeds the demo DB and is scheduled hourly. When `DEMO_MODE=false`, the routes 404 and the entry points disappear. `DemoUserSeeder` flags accounts with `is_demo`, and demo accounts get a 2 MB image upload cap.

**Why:** release 1.1 requires guest/demo access without registration and a resettable dataset. A shared dataset is simple and honest for a single-tenant demo; per-visitor isolation is a roadmap item.

**Trade-off:** demo visitors share data and are logged out when the hourly reset runs. It is not a security boundary. Because there is no profile-edit or user-CRUD screen, "demo users cannot change email/password / create admins / delete the account" is enforced by the absence of those actions (documented in `SECURITY-REVIEW.md`).

## 19. Editorial identity over the old generic SaaS look

**Decision:** replace the indigo/teal "Fedaa" palette with the Matjar black/white/tomato/price-green system, sharp corners, Rubik display type, an original bag-and-M logo, and asymmetric editorial layouts. Keep the data model and working features intact.

**Why:** release 1.1 requires a distinct identity that does not look templated, and the product should stand on its own with a quiet footer credit to the owner.

**Trade-off:** a large visual change with no schema change; all strings were reviewed and a parity test guards ar/en key sets.
