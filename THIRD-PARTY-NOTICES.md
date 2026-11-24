# Third-party notices — Matjar

Matjar application code is proprietary (see `LICENSE` and `COPYRIGHT.md`). It uses
the following **direct** dependencies, all under permissive licenses. No
GPL/AGPL/LGPL dependency is used.

## PHP (Composer) — direct dependencies

| Package | Version | License | Purpose |
|---|---|---|---|
| laravel/framework | 13.43.0 | MIT | Application framework |
| laravel/tinker | 3.2.0 | MIT | Local REPL (dev convenience) |
| fakerphp/faker | 1.24.1 | MIT | Test/demo data generation (dev) |
| laravel/pail | 1.3.0 | MIT | Local log viewer (dev) |
| laravel/pao | 1.1.6 | MIT | Agent-optimized test output (dev) |
| laravel/pint | 1.32.3 | MIT | Code formatter (dev) |
| mockery/mockery | 1.6.15 | BSD-3-Clause | Test doubles (dev) |
| nunomaduro/collision | 8.9.6 | MIT | CLI error rendering (dev) |
| pestphp/pest | 4.7.8 | MIT | Test framework (dev) |
| pestphp/pest-plugin-laravel | 4.1.0 | MIT | Pest ↔ Laravel integration (dev) |
| phpunit/phpunit | 12.5.33 | BSD-3-Clause | Underlying test runner (dev) |

Transitive Composer packages are resolved from `composer.lock` and are also
permissively licensed (MIT/BSD/Apache-2.0/ISC). Run `composer licenses` to
inspect the full tree.

## JavaScript (npm) — direct dependencies

| Package | Version | License | Purpose |
|---|---|---|---|
| @alpinejs/csp | 3.17.5 | MIT | CSP-safe Alpine build for interactive UI (menus, steppers, checkout totals, confirm dialogs) |
| @fontsource-variable/rubik | 5.3.0 | SIL OFL 1.1 | Self-hosted Rubik variable font (headings) |
| @fontsource-variable/noto-sans-arabic | 5.3.0 | SIL OFL 1.1 | Self-hosted Noto Sans Arabic variable font (body) |
| @tailwindcss/vite | 4.3.5 | MIT | Tailwind integration for Vite (dev/build) |
| laravel-vite-plugin | 3.2.1 | MIT | Laravel asset manifest integration (dev/build) |
| tailwindcss | 4.3.5 | MIT | Utility CSS framework (dev/build) |
| vite | 8.3.8 | MIT | Asset bundler (dev/build) |

Transitive npm packages are resolved from `package-lock.json`. The CSP Alpine
build depends on `@vue/reactivity` (MIT). Optional platform-specific native
binaries of Vite/Tailwind tooling carry their own permissive licenses
(MIT/BSD/Apache-2.0).

## Fonts

Both fonts are self-hosted: the `@fontsource-variable/*` packages are installed
from npm and bundled into `public/build/assets/*.woff2` by Vite at build time.
No font CDN is contacted at runtime.

| Asset | Version / source | License |
|---|---|---|
| Rubik (variable, Arabic + Latin + Cyrillic + Hebrew subsets) | `@fontsource-variable/rubik` 5.3.0 — Rubik by Hubert & Fischer (Meir Sadan) | SIL Open Font License 1.1 (OFL-1.1) |
| Noto Sans Arabic (variable, Arabic + Latin subsets) | `@fontsource-variable/noto-sans-arabic` 5.3.0 — Noto Sans Arabic by Google | SIL Open Font License 1.1 (OFL-1.1) |

Both fonts are redistributed under the SIL Open Font License 1.1. The OFL text
is available at <https://openfontlicense.org/open-font-license-official-text/>.
No ownership of these fonts is claimed here.

## Assets & icons

- The Matjar logo mark, favicon and all inline icons are original SVG authored
  for this project (no icon-font package, no third-party brand marks).
- Demo product illustrations are original abstract SVG artwork authored for the
  demo seeder. No stock photos or third-party logos are included.

## Deliberately excluded

- No Google Fonts/Bunny Fonts runtime requests, no CDNs, no analytics, no
  trackers.
- No GPL/AGPL/LGPL-licensed libraries.
- The unused Laravel starter optional npm package `@laravel/multiplex` and the
  unused `concurrently` package were removed from `package.json`.
