# Heaven Gate Camp — Website & Booking Platform

Multilingual (English · العربية · עברית) website, live-availability booking engine and Filament admin for **Heaven Gate Camp, Nuweiba**, designed from the camp's Instagram identity (the gate/arch logo, copper on night navy, sand chalets, Sinai night skies).

- `docs/01-BRAND-ANALYSIS.md` — what was extracted from @heavengatecamp
- `docs/02-DESIGN-SYSTEM.md` — colours, type, components, motion, RTL rules
- `docs/03-ARCHITECTURE.md` — data model, availability/pricing rules, payment flow, roles

## Requirements

PHP 8.2+ (ext: intl, mbstring, pdo_mysql, gd), Composer 2, Node 20+, MySQL 8, a queue worker and cron.

## Install

```bash
cp .env.example .env            # set DB_*, MAIL_*, EASYKASH_*, ADMIN_*
composer install
php artisan key:generate
php artisan migrate --seed      # admin user, settings, 3 stays / 17 units, experiences, facilities, content
php artisan storage:link
php artisan lang:add ar he      # Laravel validation messages in Arabic & Hebrew
npm install && npm run build
php artisan serve               # http://localhost:8000 → /en, /ar, /he · admin at /admin
```

Local dev in one command: `composer dev` (server + queue + Vite). In `APP_ENV=local` the seeder also adds five demo bookings.

## Production

```cron
* * * * * cd /var/www/heavengate && php artisan schedule:run >> /dev/null 2>&1
```
Run a queue worker (Supervisor): `php artisan queue:work --tries=3` — emails are queued.
Then: `php artisan config:cache route:cache view:cache filament:optimize`.

EasyKash dashboard → callback URL: `https://YOUR-DOMAIN/payments/easykash/callback`.

## Before launch — replace placeholders

| What | Where |
|---|---|
| Real prices, weekend prices, unit count | Admin → Inventory → Stays (seed prices are placeholders) |
| Seasons & holidays | Admin → Pricing → Seasonal rates |
| Hero video (`public/videos/hero.mp4` is a generated night-sky placeholder) | Content → Page sections → home.hero → Background video |
| Photos (the SVG scenes are stand-ins) | Stays → Media tab, Stays → Photos, Content → Gallery, Page sections → image |
| Phone, WhatsApp, email, taxes, deposit, cancellation windows | Admin → Settings |
| Terms & privacy wording | Content → Pages & policies |
| EasyKash keys / payment option ids | `.env` |
| Admin password | `.env ADMIN_PASSWORD` before seeding, then change in profile |

## What's included

**Public site** — home, stays list + detail with live availability calendar (nightly prices), experiences, the camp (facilities + FAQ), gallery with lightbox, location (map, driving times), contact (honeypot, rate-limited), CMS pages, SEO (hreflang, schema.org Campground, sitemap, OG).

**Booking** — groups larger than one unit are offered as many units of a type as they need (split by capacity, guest can add rooms) · dates & guests (camp-wide availability calendar) → available stays with totals → add-ons, guest details, promo code → 20-min hold → EasyKash (cards, wallets, Meeza, Fawry) or bank transfer/InstaPay → confirmation email in the guest's language → manage booking (lookup, receipt PDF, policy-based self-cancellation).

**Admin (Filament)** — dashboard (occupancy tonight, arrivals, revenue vs last month, 30-night occupancy chart, next arrivals), unit × night calendar, bookings (tabs: upcoming / arriving / leaving / in house / awaiting payment; record payment, confirm, check-in/out, move unit, no-show, cancel with refund calc, history notes, CSV export), phone/walk-in booking form, guests (VIP, CSV export), payments ledger, stays & units, experiences, facilities, seasonal rates, blocked dates, promotions, gallery, page sections, pages, FAQ, enquiries, settings, staff roles. All content editable in EN/AR/HE. The panel itself switches between English and Arabic (RTL) from the top bar; the choice is saved per staff member. Admin UI strings live in `lang/ar.json`.

## Tests

```bash
php artisan test
```
Covers double-booking prevention, same-day turnover, hold expiry, blocked dates, capacity, cancellation tiers, weekend/seasonal pricing, extra guests, promo validation, EasyKash signature/amount/idempotency/Fawry-pending, and every public page in all three languages with correct `dir`.

## Not built (next steps)

Date changes on an existing booking (cancel & rebook for now) · OTA channel manager sync (Booking.com iCal) · automatic EasyKash refunds via API · reviews.
