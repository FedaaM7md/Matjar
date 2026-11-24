# Architecture — Matjar

Matjar is a server-rendered Laravel monolith with progressive enhancement via Alpine.js. It is bilingual (Arabic-first RTL / English LTR), permissioned through an in-house RBAC layer, and models money, inventory and payments explicitly rather than relying on UI state.

## Request lifecycle

```
HTTP request
  → Laravel web middleware (cookies, session, CSRF, bindings)
  → App\Http\Middleware\SetLocale        (session locale, HTML dir)
  → App\Http\Middleware\SecurityHeaders  (security / noindex headers)
  → Route (routes/web.php)
  → auth + can:<permission> middleware
  → Controller / FormRequest validation
  → Domain service (Cart, Checkout, Inventory, Order, Payment, Coupon, Shipping)
  → Eloquent models + SQLite transaction
  → Blade view (layouts.app storefront | layouts.admin | layouts.print)
  → HTML response
```

Controllers stay thin. Business rules with money or inventory consequences live in `app/Services`. Validation rules that protect admin mutations live in `app/Http/Requests/Admin`.

## Directory map

```
app/
  Http/Controllers/Storefront   Home, Shop, Cart, Checkout, Order, Payment, Account, Locale
  Http/Controllers/Admin        Dashboard, Product, Category, Variant, Image, Inventory,
                                Order, Customer, Coupon, Shipping, Settings, Report
  Http/Requests/Admin           ProductRequest, VariantRequest, CategoryRequest,
                                AdjustStockRequest, CouponRequest
  Http/Middleware               SetLocale, SecurityHeaders
  Models                        User, Role, Permission, Setting, Category, Product,
                                ProductImage, ProductVariant, StockMovement, Cart, CartItem,
                                Governorate, ShippingZone, Coupon, Order, OrderItem,
                                OrderStatusHistory, Payment
  Services                      CartService, CheckoutService, CouponService, ShippingService,
                                InventoryService, OrderService, OrderNumberService, PaymentService
  Support                       Money, Phone, Slug, Permissions
resources/
  views/layouts                 app (storefront), admin, print
  views/components              logo, product-card, flash, order/payment status, admin nav icon
  views/{home,shop,cart,checkout,orders,payments,account,auth,errors,admin}
  css/app.css                   Tailwind 4 theme + design tokens, component classes
  js/app.js                     font imports (@fontsource) + Alpine components: qtyStepper,
                                dismissible, confirmForm, productBuy, checkoutForm
lang/{ar,en}                    common, home, shop, cart, checkout, orders, payments, account,
                                auth, admin, inventory, coupons, fields, demo, validation, errors, pagination
database/migrations             schema (see below)
database/seeders                roles, settings, governorates, demo users/catalog/marketing/orders
app/Console/Commands            demo:reset (reseed the demo)
resources fonts                Rubik + Noto Sans Arabic bundled by Vite (public/build/assets, OFL)
```

## Data model (high level)

| Entity | Key relations / notes |
|---|---|
| users | `role_id` → roles; phone unique; `is_active` |
| roles / permissions | many-to-many `permission_role`; Admin, Staff, Customer |
| categories | self-referencing `parent_id`, slug, AR/EN names, position, active |
| products | category, slug, AR/EN names/descriptions, brand, active/featured |
| product_images | product, storage path, alt AR/EN, position |
| product_variants | product, unique `sku`, size/color/hex, `price_cents`, `stock`, `reserved`, low-stock threshold |
| stock_movements | variant, type, `stock_change`, `reserved_change`, `stock_after`, `reserved_after`, reason, reference (order), user, timestamp |
| carts / cart_items | guest `token` or `user_id`; unique (cart, variant); optional coupon |
| coupons | type percent/fixed, value (basis points / cents), min subtotal, max discount, usage limits, per-user limit, window, active |
| governorates / shipping_zones | 27 rows; one active zone per governorate with rate, free threshold, delivery days |
| orders | `number` ORD-YYYY-NNNN, status, payment method/status, stock state, customer snapshot, governorate snapshot, money snapshot, timestamps |
| order_items | product/variant snapshots (`sku`, names, label, unit price, qty, line total) |
| order_status_histories | from/to status, note, actor, timestamp |
| payments | order, unique random reference, provider `fake`, amount, status, signature, payload, paid_at |
| order_counters | year → last_number, locked per checkout |
| settings | key/value/group (store, payments, inventory) |

Money is always stored as integer **piastres** (`*_cents`). Strings for display are produced by `App\Support\Money`.

## Inventory model

`product_variants.stock` is physical stock; `product_variants.reserved` is stock promised to open orders. Available = `stock - reserved`.

Every mutation goes through `InventoryService`, which locks the variant row and writes a `stock_movements` row inside a transaction:

| Type | Stock change | Reserved change | Used for |
|---|---|---|---|
| `in` | +q | 0 | receiving stock, initial stock |
| `out` | −q | 0 | manual removal / damage |
| `adjust` | delta | 0 | cycle count to a new counted value |
| `reserve` | 0 | +q | checkout reservation |
| `release` | 0 | −q | cancel before confirmation |
| `commit` | −q | −q | payment/confirmation converts reservation to deduction |
| `restore` | +q | 0 | cancel/return after commitment |

The service rejects negative stock, negative reservations and `reserved > stock`. Admin UI exposes in/out/adjust plus the full ledger; the low-stock scope uses `(stock - reserved) <= low_stock_threshold`.

## Order state machine

`OrderService::TRANSITIONS` is the single source of truth:

```
pending   → confirmed | cancelled
confirmed → shipped   | cancelled
shipped   → delivered | cancelled
delivered → returned
cancelled → (terminal)
returned  → (terminal)
```

Stock consequences:
- `pending → confirmed`: if stock is `reserved`, commit it (`stock -= qty`, `reserved -= qty`) and set `stock_state=committed`.
- `pending → cancelled`: release reservations (`reserved -= qty`), state `released`.
- `confirmed/shipped → cancelled`: restore committed stock to physical stock, state `released`.
- `delivered → returned`: restore committed stock, state `released`.
- Same-status transitions are idempotent no-ops; invalid transitions throw `OrderTransitionException` and change nothing.

Every transition writes `order_status_histories` with actor and optional note.

## Checkout sequence

1. `GET /checkout` validates cart is non-empty and has no stale lines, lists governorates with active zones.
2. `POST /checkout` validates customer/address/phone/payment method.
3. `CheckoutService::place()` opens one DB transaction:
   - locks all cart variant rows,
   - re-checks active product/variant and `qty <= available`,
   - recomputes subtotal from locked prices,
   - re-validates coupon (active, window, global/per-user limits, min subtotal),
   - quotes shipping from the selected governorate using the **discounted** subtotal,
   - consumes coupon usage under a row lock,
   - allocates the next `ORD-YYYY-NNNN` from `order_counters` under a lock,
   - creates the order, item snapshots and status history,
   - reserves every line through `InventoryService`,
   - clears the cart.
4. COD: redirect to the signed (guests) or authenticated confirmation page. Simulated gateway: create a pending `payments` row and redirect to `/pay/{reference}`.

Any exception rolls the whole transaction back (no order, no reservation, no coupon consumption).

## Simulated gateway

`PaymentService` signs each outcome:

```
HMAC-SHA256(key = APP_KEY,
  reference | outcome(success|fail|cancel) | amount_cents | expires_at)
```

The page renders three forms, each with its own signature for the corresponding outcome. The callback verifies:
- outcome whitelist, expiry, amount equality and constant-time HMAC equality;
- payment is still pending;
- order is still open;
- payment method is `test_gateway`.

Success marks the payment paid and transitions the order to `confirmed` (committing stock). Failure/cancel marks the payment failed/cancelled and cancels the order (releasing stock). Replays and tampering are rejected.

## RBAC

`App\Support\Permissions` is the permission catalogue. `RolePermissionSeeder` seeds Admin (all), Staff (operational catalogue, inventory, orders, customers, reports) and Customer (none). Routes use `auth` + `can:staff` + a permission. Blade uses `@can`. Admin route middleware is duplicated by FormRequest authorization for mutating actions.

## Localization & front-end

- Locale is stored in the session by `SetLocale`; `POST /locale/{ar|en}` switches it. `<html lang dir>` is rendered from config.
- Translation files cover all copy; product/category/order snapshots expose AR/EN accessors that follow `app()->getLocale()`.
- CSS uses Tailwind logical properties (`ps/pe`, `ms/me`, `start/end`) so RTL/LTR share one stylesheet. Rubik (display) and Noto Sans Arabic (body) are self-hosted via `@fontsource-variable/*` and bundled by Vite, with system fallbacks.
- Alpine components are small and progressive: mobile menu, quantity steppers, product variant selector, checkout shipping calculator, dismissible alerts.
- Cart count and navigation categories are shared through a view composer on the storefront layout.

## Access control for orders

Order pages (`/orders/{number}`, `/confirmation`, `/invoice`) allow:
- the authenticated owner,
- Admin/Staff,
- any request with a valid temporary signed URL (7 days) — used for guests after checkout.

Invoice/confirmation links shown to guests are separately signed. Unauthenticated, unsigned requests return 403.

## Testing architecture

Feature tests (Pest) run on in-memory SQLite and cover auth/RBAC, catalog, inventory/overselling, cart/coupons/shipping totals, checkout/gateway signatures and tampering, order transitions/stock restore, account isolation/invoices, admin CRUD/reports and hardening. Factories provide `User`, `Category`, `Product`, `ProductVariant`, `Coupon`, `Governorate`, `Order`, `OrderItem`, `Payment`. A per-test `Setting` memo reset keeps `Setting` tests isolated.

## Extension points

- New payment provider: implement alongside `PaymentService` and keep the order state machine unchanged.
- New shipping provider: swap `ShippingService` quote source, keep the zone model.
- New role/matrix: permissions already exist; add a UI editor and reuse `can:` middleware.
- API: reuse services and FormRequests; add JSON resources without touching inventory invariants.
