# Testing — Matjar

Last verified: **2026-11-24** (release 1.1)

Environment: PHP 8.4.28, Laravel 13.43.0, Pest 4.7.8, PHPUnit 12.5.33, Node
24.12.0, npm 11.6.2, SQLite in-memory for tests.

## Commands and real results

```powershell
cd matjar
php artisan test
```

Final output:

```text
{"tool":"pest","result":"passed","tests":168,"passed":168,"assertions":848,"duration_ms":11020}
```

```powershell
npm run build
```

Final output (tail):

```text
✓ 6 modules transformed.
public/build/manifest.json                                            4.04 kB │ gzip:  0.54 kB
public/build/assets/rubik-arabic-wght-normal-CGnJ-sf6.woff2          32.46 kB
public/build/assets/rubik-latin-wght-normal-CnQIisVs.woff2           35.34 kB
public/build/assets/noto-sans-arabic-arabic-wght-normal-BhzieyvS.woff2 165.96 kB
public/build/assets/app-D7NgBv8o.css                                  6.29 kB │ gzip:  1.77 kB
public/build/assets/app-Ds43UCf0.css                                 64.33 kB │ gzip: 11.25 kB
public/build/assets/app-CFTAtVHM.js                                  72.80 kB │ gzip: 23.88 kB
✓ built in 264ms
```

```powershell
php vendor\bin\pint --test
# {"tool":"pint","result":"passed"}

composer validate --no-check-publish
# ./composer.json is valid

composer audit --no-interaction
# No security vulnerability advisories found.

npm audit
# found 0 vulnerabilities
```

There are **no skipped tests** and no tests were deleted or weakened. The suite
uses PHPUnit's in-memory SQLite database (`phpunit.xml`) and `RefreshDatabase`.

## Coverage by requirement

| Requirement | Test file(s) | What is asserted |
|---|---|---|
| Auth, locale, RTL/LTR | `AuthAndLocaleTest`, `SecurityHardeningTest` | register/login, throttling, session regeneration, inactive users, Arabic RTL default, English LTR pages, Arabic validation |
| RBAC permissions | `AdminAccessTest`, `AdminPanelTest`, `InventoryTest` | Admin vs Staff vs Customer access matrix across dashboard, products, inventory, orders, customers, coupons, shipping, settings, reports |
| Totals & minor units | `CartAndShippingTest` | subtotal/discount/shipping/total math in piastres, formatting and EGP parsing |
| Catalog & variants | `CatalogTest`, `AdminCatalogTest` | active/inactive visibility, search by name/SKU, category filter, price filter, sorting, variant price/SKU display, CRUD, first variant + ledger entry, image upload validation |
| Inventory ledger | `InventoryTest` | in/out/adjust balances, reserve/commit/restore, negative-stock rejection, reserved-above-stock rejection, low-stock filter, ledger rendering, permissions |
| Overselling prevention | `InventoryTest`, `CheckoutTest` | reservations cannot exceed available stock; checkout re-validates stock after cart changes; failed checkout leaves no order/reservation |
| Coupons | `CartAndShippingTest`, `CheckoutTest` | percent/fixed math, cap, rounding, min spend, expiry, inactive, global usage limit, per-customer (including guest email), case-insensitive apply/remove, usage increment |
| Shipping by governorate | `CartAndShippingTest` | all 27 governorates seeded with active zones, rates, free thresholds, discounted-subtotal evaluation, inactive zones, admin edits |
| Checkout & COD | `CheckoutTest` | order creation, snapshots, phone normalisation, validation, cart clearing, sequential `ORD-2026-0001` numbers, signed guest links |
| Simulated gateway | `CheckoutTest` | pending payment, valid signed success/fail/cancel callbacks, amount/outcome/expiry/signature tampering rejected, replay protection, cancelled order cannot be paid |
| Order transitions & stock restore | `OrderLifecycleTest`, `CheckoutTest`, `AdminPanelTest` | full state graph, commit on confirm, release on pending cancel, restore on confirmed cancel and return, terminal states, invalid transitions, admin transition endpoints |
| Order history & invoices | `AccountOrdersTest`, `CheckoutTest` | own-order isolation, status filter, admin access, printable invoice totals, signed invoice access, expired signatures |
| Admin & reports | `AdminPanelTest` | dashboard metrics, search/filters, customer profiles, coupon CRUD/validation, shipping updates, settings toggles, payment-method disabling, report revenue exclusions, top products, governorate breakdown |
| Hardening & CSP | `SecurityHardeningTest` | security headers (incl. CSP nonce, COOP, HSTS absence on HTTP), headers on error pages, noindex admin, branded 403/404, stored XSS escaping, rate limits on coupons/checkout, password/token leakage, method restrictions |
| Demo mode | `DemoModeTest` | role picker, one-click login as Admin/Staff/Customer, `is_demo` flag, unknown-role rejection, throttled demo login, 404s + hidden entry when `DEMO_MODE=false`, banner visibility |
| Language parity | `LanguageParityTest` | identical ar/en key sets for every language file; brand name stands alone; no owner name in SEO strings |
| Information pages & meta | `PagesAndMetaTest` | About / Contact / Shipping / Size guide / Privacy / Terms render; Open Graph + Twitter tags; footer credit link; no logo credit; fake contact details |
| Demo data volume | `DemoDataTest` | 6 categories, 30+ products, 100+ variants, product illustrations, 60+ orders over 60+ days across 10+ governorates with varied statuses |
| Reports export | `ReportExportTest` | admin CSV export (UTF-8, headers), formula-injection cells prefixed, customers forbidden |

## Demo-mode tests in detail

`tests/Feature/DemoModeTest.php`:

- The `/demo` page lists Admin, Staff and Customer.
- `POST /demo/login` with each role authenticates the visitor, redirects to
  `/admin` (admin/staff) or `/account` (customer), and the user has `is_demo`.
- An unknown role fails validation and leaves the visitor a guest.
- Exactly three seeded users are flagged `is_demo`, including an admin.
- With `matjar.demo.enabled = false`, `/demo` and `/demo/login` return 404 and
  the landing page hides the demo launcher and banner.

## Language parity

`tests/Feature/LanguageParityTest.php` walks every file in `lang/ar` and `lang/en`
and compares flattened keys (including nested groups). Missing keys in either
language fail the test. It also asserts `common.app_name` is `متجر` / `Matjar`,
`common.app_tagline` is `ملابس يومية مصرية` / `Everyday Wear`, and that SEO strings contain no owner name.

## Manual / headless-browser status

A local HTTP smoke run was performed on 2026-11-24 against
`php artisan serve --port=8107` with the seeded SQLite database:

```text
http://127.0.0.1:8107/                              -> 200
http://127.0.0.1:8107/shop                          -> 200
http://127.0.0.1:8107/product/classic-cotton-shirt  -> 200
http://127.0.0.1:8107/cart                          -> 200
http://127.0.0.1:8107/login                         -> 200 (includes demo role buttons)
http://127.0.0.1:8107/demo                          -> 200
http://127.0.0.1:8107/storage/demo/product-1.svg    -> 200
http://127.0.0.1:8107/does-not-exist                -> 404 (branded)
```

The one-click demo login was exercised end-to-end over HTTP (token + session):
posting to `/demo/login` with `role=admin` landed on `/admin` with a 200.

Rendering was checked in **headless Google Chrome** (real browser engine):

- Captured 11 screenshots (Arabic + English, desktop + mobile) into
  `docs/screenshots/` using `chrome --headless --screenshot`.
- Verified via `--dump-dom` that Alpine initialises under the CSP: the mobile
  menu is `display:none` when closed, `aria-expanded="false"` is bound, and the
  product variant selector populates the price/SKU/availability fields.

This is not a full browser E2E suite or a visual-regression setup; those, plus a
screen-reader accessibility audit, remain future work in `ROADMAP.md`.
