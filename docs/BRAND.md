# Qoton — Brand guide (Matjar demo storefront)

> **قُطن** / **Qoton** — everyday Egyptian clothing. The project behind it is
> Matjar; the shop the visitor sees is Qoton. Owner: Fedaa Mohamed.

This document is the source of truth for the identity. UI work should follow
the tokens in `resources/css/app.css` (`@theme`).

## 1. Concept

Qoton is a **bold editorial storefront**, not a soft SaaS dashboard. It reads
like a well-designed clothing catalogue: high contrast, large type, sharp edges,
product illustrations, prices that are impossible to miss. The admin is the
quiet back room — monochrome with a single red accent.

Personality: confident, direct, practical. It talks about delivery to
governorates and exact prices, not "seamless experiences".

## 2. Logo

- **Mark:** a shopping-bag outline whose two handles rise as the peaks of the
  letter **M** (and the Arabic **م**), completed by a centre crease. The bag
  body is drawn in `currentColor` so it adapts to light/dark surfaces; the
  handles carry the tomato accent.
- **Files:** `resources/views/components/logo.blade.php` (inline SVG),
  `public/favicon.svg` (solid black tile, white mark, red handles).
- **Wordmark:** set in Rubik ExtraBold. The brand name stands alone; the owner
  credit lives only in the footer (see §5), never under the logo.
- **Clear space:** at least half the bag height on every side. Never recolour
  the handles to anything other than the tomato accent; never put the mark on
  a busy photo without a solid block behind it.
- **Do not:** rotate, outline, add gradients, place inside a rounded square, or
  rebuild it from letterforms.

## 3. Colour

| Token | Hex | Use |
|---|---|---|
| Ink / brand-900 | `#111111` | Headlines, primary buttons, footer, admin sidebar |
| brand-950 | `#0A0A0A` | Body text, dark panels |
| White | `#FFFFFF` | Page background, cards |
| Tomato / accent-500 | `#E63B2E` | The one accent: links, focus ring, sale/featured tags, active admin nav |
| accent-700 | `#A8271E` | Accent text on light, danger buttons |
| Warm grey / paper | `#EDEAE6` | Secondary surfaces, image placeholders, section bands |
| Price green | `#1D7A4F` | Prices, in-stock, success |
| Amber | `#B45309`-family | Low stock / caution |
| Stone neutrals | warm greys | Borders, secondary text |

Rules:

- Use **one** accent colour (tomato). Do not introduce purple, teal or blue.
- Prices are always **price green** and use tabular numerals (`.num`).
- No gradients, no glassmorphism, no drop-shadow stacks. Borders over shadows.
- Maintain AA contrast. Ink on white and white on ink pass; avoid tomato text
  below 16px on white unless bold.

## 4. Typography

Both fonts are self-hosted via `@fontsource-variable/*` (SIL OFL 1.1) and
bundled by Vite — **no CDN**.

| Role | Font | Weights | Notes |
|---|---|---|---|
| Display / headings / prices | **Rubik** (`Rubik Variable`) | 300–900 | ExtraBold (800) for H1/H2; tight tracking |
| Body / UI | **Noto Sans Arabic** (`Noto Sans Arabic Variable`) | 100–900 | Strong Arabic text rendering; Latin fallback |

- Numerals: tabular lining for money and order codes (`font-variant-numeric`).
- Arabic and English share the same scale; logical properties (`ps/pe`,
  `ms/me`, `start/end`) keep RTL/LTR identical.
- Line length capped around 65–70 characters for body copy.

## 5. Shape, spacing, motion

- **Radius:** 0–4px. Cards, buttons, inputs and badges are square. Circles are
  reserved for avatars, colour swatches and notification dots.
- **Borders:** 1px stone hairlines. Emphasis via a 2–4px accent edge, not glow.
- **Spacing:** 4px base scale; generous section padding (48–80px).
- **Motion:** fast and subtle (150–300ms). Product images may scale ~3% on
  hover. No parallax, no floating blobs.

## 6. Voice and tone

Punchy retail, specific, prices clear, delivery explicit.

- Arabic: Modern Standard Arabic that reads naturally to Egyptians. Short
  sentences. Correct hamzas. Avoid Gulf-only words and literal translation.
- English: concise product English. No filler.

**Prefer → avoid**

| Prefer | Avoid |
|---|---|
| «الشحن لكل المحافظات — الدفع عند الاستلام» | «حلول متكاملة بضغطة زر» |
| «349.00 ج.م» | «أسعار لا تُقاوم» |
| "Delivery to every governorate" | "Seamless nationwide logistics" |
| "Cash on delivery" | "Elevate your checkout experience" |

Avoid these words entirely in UI copy: *seamless, elevate, empower, unlock,
revolutionize, next-level, all-in-one*; Arabic: *بكل سهولة، ارتقِ، حلول متكاملة،
بضغطة زر*.

## 7. Arabic / English glossary (consistent terminology)

| Concept | Arabic | English |
|---|---|---|
| Store / product brand | قُطن | Qoton |
| Product | منتج | Product |
| Variant | متغير | Variant |
| Order | طلب | Order |
| Cart | سلة التسوق | Cart |
| Checkout | إتمام الطلب | Checkout |
| Cash on delivery | الدفع عند الاستلام | Cash on delivery |
| Governorate | محافظة | Governorate |
| Inventory | المخزون | Inventory |
| Coupon | كوبون | Coupon |
| Demo build | نسخة تجريبية | Demo build |
| Demo mode | وضع تجريبي | Demo mode |

## 8. Do / don't

**Do**

- Lead with real product imagery and exact prices.
- Keep hierarchies uneven: one large hero product, then a grid, then a band.
- Use the warm paper tone for calm secondary blocks.
- Show delivery and payment facts near the price.

**Don't**

- Use gradient blobs, floating mock cards, or "trusted by" logo rows.
- Put a pill badge on every heading, or repeat identical card grids.
- Use emoji as icons, or third-party logos.
- Invent reviews, ratings or sales counters that do not exist.
