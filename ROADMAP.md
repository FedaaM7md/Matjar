# Roadmap — Matjar

This is an honest list. Items under "Done" were implemented and covered by the automated suite. Items under "Not implemented" are genuine gaps, not hidden scope.

## Done (release 1.1 — redesign, identity, demo mode, security, ownership)

- [x] Original Matjar identity: logo (bag formed from M/م), favicon, black/white/tomato-red palette, sharp corners, editorial layouts, `docs/BRAND.md`
- [x] Self-hosted Rubik (headings) + Noto Sans Arabic (body) via `@fontsource-variable/*` (OFL), no CDN; Cairo removed
- [x] "Matjar / متجر" brand with quiet "by Fedaa / من فداء" attribution; owner name removed from titles and seeded data
- [x] Rebuilt storefront (editorial hero, asymmetric sections, sticky mini-cart) and monochrome admin with red accents
- [x] Demo mode: one-click Admin/Staff/Customer login, persistent banner, `is_demo` flag, 2 MB demo upload limit, `DEMO_MODE` switch, 404s when off
- [x] `demo:reset` command + hourly schedule
- [x] Per-request nonce Content-Security-Policy (plus HSTS/COOP), inline handlers replaced with Alpine, error pages covered
- [x] Proprietary `LICENSE` + `COPYRIGHT.md`; composer/package metadata updated; `docs/SECURITY-REVIEW.md`
- [x] Arabic/English key-set parity test and demo-mode tests
- [x] Fresh real screenshots (ar/en × desktop/mobile) and documentation refresh

## Done (release 1.1 — fix round 1, reviewer feedback)

- [x] Shop: mobile/tablet filters in a sticky "Filter & sort" bottom sheet with result count; product grid shows immediately in 2 columns on phones; slim sidebar on desktop
- [x] Per-category original SVG illustrations (tee/bag/mug/headphones) used for demo images and as the fallback everywhere, never empty boxes
- [x] Page titles no longer duplicate the brand ("Page — Matjar")
- [x] 375px purchase path: sticky add-to-cart on product, sticky checkout bar on cart/checkout, 44px tap targets, no horizontal overflow
- [x] Admin list pages (orders/products/customers) use stacked cards on phones with filters behind a toggle
- [x] Switched to `@alpinejs/csp` and removed `'unsafe-eval'` from the CSP (tested)
- [x] Explicit 403 for demo accounts on the account profile update action (tested)

## Done (release 1.1 — fix round 2, realism + owner credit addendum)

- [x] Storefront presents the fictional brand **قُطن / Qoton — ملابس يومية مصرية**; owner credit removed from logos/headers/login, kept as a quiet footer link to fedaamohamed.com
- [x] Clothing-only catalogue: 6 categories, 33 products, 247 S–XXL/colour variants, realistic EGP prices
- [x] 68 demo orders across ~3 months and 25 governorates with mixed statuses (reports look alive)
- [x] Information pages: About, Contact (fake details), Shipping & Returns, Size Guide, Privacy, Terms
- [x] Open Graph + Twitter meta tags and an OG image
- [x] Discreet demo signalling: tiny footer note on public pages, small muted notice in admin; no loud red bar
- [x] Per-product illustrations vary by garment shape and variant colour
- [x] Arabic typography fixes (no letter-spacing/uppercase on Arabic), localized pagination, no English strings in Arabic UI, demo entry above the login form
- [x] CSV report export with formula-injection safety
- [x] `.artifacts/` git-ignored; no new third-party libraries

## Done (release 1.0)

- [x] Laravel 13 + Blade + Tailwind 4 + Alpine 3 + Vite, SQLite
- [x] In-house RBAC (Admin/Staff/Customer) with database permissions
- [x] Arabic-first RTL + English LTR, language switch, self-hosted fonts
- [x] Categories, products (AR/EN), validated image upload, variants (SKU/price/stock), search/filter/sort
- [x] Inventory ledger with in/out/adjust/reserve/release/commit/restore and low-stock alerts
- [x] Transactional stock reservation at checkout (no overselling in sequential tests)
- [x] Guest + customer cart with merge on login
- [x] Percentage/fixed coupons with limits, expiry and per-customer usage
- [x] All 27 Egyptian governorates with rates, free thresholds and delivery windows
- [x] Checkout with Egyptian phone validation, COD and local simulated gateway with signed callbacks
- [x] Orders with `ORD-YYYY-NNNN`, full status graph, stock restore on cancel/return, printable invoice
- [x] Customer registration/login and order history
- [x] Admin dashboard, catalog, inventory, orders, customers, coupons, shipping, settings, reports
- [x] Automated test suite and production asset build

## Not implemented (candidate next milestones)

### Accounts & communication
- [ ] Password reset, email verification, change-password/change-email flows
- [ ] Transactional email/SMS for order confirmation and status changes (queue + provider)
- [ ] Customer address book, saved addresses, profile editing

### Commerce
- [ ] Variant-level image galleries (currently product-level images)
- [ ] Product reviews/ratings (deliberately no fake ratings)
- [ ] Wishlist/favourites and recently viewed
- [ ] Coupon stacking rules, automatic promotions, first-order-only coupons
- [ ] Gift cards and store credit
- [ ] Partial returns/partial refunds and refund accounting (returns currently restore full stock)
- [ ] Back-in-stock notifications

### Payments (simulation only today)
- [ ] Real gateway integration with webhooks, idempotency keys, refunds and reconciliation
- [ ] Payment retry flow after a failed simulated payment (current flow cancels the order)
- [ ] Multi-currency and FX; VAT/e-invoicing for Egypt

### Logistics & inventory
- [ ] Courier integrations and tracking numbers
- [ ] Multi-warehouse stock, stock transfers and per-location reservations
- [ ] Barcode/QR scanning for picking and cycle counts
- [ ] Bulk CSV import/export for products and stock

### Platform & operations
- [ ] Move from SQLite to MySQL/PostgreSQL for multi-user production use and load-test true concurrency
- [ ] Background queue workers for emails, image processing and reports
- [ ] Image thumbnails/optimisation pipeline and object storage
- [ ] Full-text search index (Laravel Scout) for larger catalogs
- [ ] Audit log for admin actions beyond the inventory ledger
- [ ] Custom role editor in the UI (permissions exist; role matrix is seeded)
- [ ] API (REST/JSON) and mobile clients
- [ ] Monitoring, error tracking, backups and deployment automation

### Quality
- [ ] Browser E2E tests (Playwright/Dusk) and visual regression
- [ ] Full accessibility audit with screen readers and automated axe checks
- [ ] Load/soak tests and true parallel overselling tests against MySQL/PostgreSQL
- [ ] Arabic/English copy review by a native UX writer
- [ ] Per-visitor demo isolation (currently a shared demo dataset that resets hourly)

## Known product decisions

- Demo catalog, demo orders and demo coupons are intentionally seeded and clearly labelled; they are skipped in `APP_ENV=production`.
- No reviews, ratings or sales counters are invented anywhere in the UI.
- The simulated gateway never touches real card data and is not a payment provider.
