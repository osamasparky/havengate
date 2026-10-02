# Heaven Gate — Design System

Implemented in `resources/css/app.css` (Tailwind v4 `@theme` tokens) and Blade components in `resources/views/components/`.

## 1. Colour

Derived from the logo (copper on night navy) and the photography (sand chalets, golden hour, Gulf water).

| Token | Hex | Use |
|---|---|---|
| `night-950` | `#070D18` | Deepest background (footer, night hero) |
| `night-900` | `#0B1424` | **Primary dark** — logo disc, night sections |
| `night-800` | `#14213A` | Raised surfaces on dark |
| `night-700` | `#22314F` | Borders on dark |
| `copper-300` | `#E2C29C` | Copper on dark text, hover |
| `copper-400` | `#D3A877` | Star, icons on dark |
| `copper-500` | `#B8875A` | **Primary accent** — logo line, primary buttons, links |
| `copper-600` | `#9A6C42` | Pressed / text-on-light accent (AA on sand-50) |
| `copper-700` | `#7A5232` | Small accent text on light |
| `sand-50` | `#FAF6EF` | **Page background (light)** |
| `sand-100` | `#F3EADC` | Alternate section background |
| `sand-200` | `#E8D9C2` | Card borders, dividers |
| `sand-300` | `#D8C09D` | Chalet walls tone, decorative |
| `dune-500` | `#A88B63` | Reed / thatch, muted labels |
| `ember-500` | `#E0813A` | Sunset — used only for "limited availability" and sunset gradients |
| `sea-500` | `#3D7891` | Gulf water — info states, map |
| `sea-700` | `#244E62` | Sea on dark |
| `ink-900` | `#1D1A16` | Body text on light |
| `ink-600` | `#5C554C` | Secondary text |
| `ink-400` | `#8F877C` | Tertiary / placeholders |

Semantic: success `#4E7D5B` (desert sage), warning `ember-500`, danger `#B4483C` (Sinai red rock), info `sea-500`.

Ratio rule: **60% sand / 30% night / 10% copper**. Ember never exceeds a single highlight per viewport.

## 2. Typography

One serif display + one humanist sans per script, so Arabic and Hebrew feel designed, not substituted.

| Role | Latin | Arabic | Hebrew |
|---|---|---|---|
| Display / headings | Cormorant Garamond 500/600 | Amiri 400/700 | Frank Ruhl Libre 500 |
| Body / UI | Manrope 400/500/600 | IBM Plex Sans Arabic 400/500/600 | Assistant 400/600 |

Scale (fluid, `clamp`):

| Token | Size | Line-height | Use |
|---|---|---|---|
| `display` | clamp(3rem, 7vw, 6.5rem) | 0.95 | Hero line |
| `h1` | clamp(2.5rem, 5vw, 4.25rem) | 1.02 | Page titles |
| `h2` | clamp(2rem, 3.6vw, 3.25rem) | 1.08 | Section titles |
| `h3` | 1.625rem | 1.2 | Card titles (serif) |
| `eyebrow` | 0.75rem, tracking 0.24em, uppercase, sans 600 | — | Section labels ("Stay", "Experiences"); no uppercase / tracking in AR/HE |
| `body-lg` | 1.125rem | 1.7 | Intros |
| `body` | 1rem | 1.65 | Paragraphs |
| `small` | 0.875rem | 1.5 | Meta, captions |

Headings in sentence case; italic Cormorant used for a single emphasised word (e.g. "Step into the *heart* of Sinai").

## 3. Shape & radius — "the gate"

- **Arch frame** (`.arch`): `border-radius: 999px 999px 0 0` (full semicircle top). The signature treatment for hero imagery, accommodation cards and gallery features.
- **Soft arch** (`.arch-soft`): `border-radius: 12rem 12rem 1rem 1rem` for wider landscape frames.
- UI radius: `--radius-sm: 6px` (inputs, chips), `--radius-md: 14px` (cards, panels), `--radius-pill: 999px` (buttons, tags).
- Four-point star `✦` as divider, bullet and loading indicator.

## 4. Elevation

Warm, low shadows — never grey.

| Token | Value |
|---|---|
| `shadow-soft` | `0 1px 2px rgb(29 26 22 / .04), 0 8px 24px -12px rgb(29 26 22 / .12)` |
| `shadow-lift` | `0 2px 4px rgb(29 26 22 / .05), 0 24px 48px -20px rgb(29 26 22 / .25)` |
| `shadow-glow` (dark) | `0 0 0 1px rgb(184 135 90 / .25), 0 20px 60px -20px rgb(184 135 90 / .35)` |

## 5. Spacing

4px base. Section rhythm: `py-24 md:py-32` (96/128px). Container 1280px max with 20px gutters (mobile) / 40px (desktop). Content measure 62ch.

## 6. Components

**Buttons**
- Primary: copper-500 fill, night-900 text, pill, 48px height, 0.95rem/600, hover → copper-400 + subtle lift, focus ring 2px copper-300 offset 2px.
- Secondary (light): 1px ink-900/20 outline, ink-900 text, hover fill sand-100.
- Ghost (dark): 1px copper-300/40 outline, sand-50 text.
- Link: copper-600 with underline offset 4px, arrow that flips in RTL.

**Cards**
- Stay card: arch image (4:5) → serif title → one-line feel ("Sea-view vaulted chalet") → meta row (guests · size · bed) → "from EGP 3,200 / night". No borders; image does the work.
- Experience card: landscape soft-arch image, duration chip, price per person.
- Panel: sand-50 bg, 1px sand-200 border, radius-md, shadow-soft (forms, booking summary).

**Inputs**: 52px height, radius-sm, 1px sand-200 border, label above (never placeholder-only), focus border copper-500 + ring copper-500/15.

**Date range picker**: two-month grid; available days ink-900, unavailable struck through ink-400, selected range copper-500 endpoints with sand-200 fill between; price per night shown under day number when available.

**Booking summary**: sticky panel, line items with nightly breakdown disclosure, total in serif 2rem.

## 7. Image treatment

- Warm grade: slight lift in shadows, +5 warmth. Never desaturated, never HDR.
- Night images on night-900 backgrounds bleed edge-to-edge.
- Day images sit in arch frames on sand.
- Overlays: bottom gradient `night-900/0 → night-900/70` only where text sits on images.
- Grain: 3% noise overlay on hero for film feel.

## 8. Motion

Unhurried — the brand is "slow mornings".
- Easing: `cubic-bezier(.22,.61,.36,1)` (ease-out-soft); durations 500–900ms for reveals, 180ms for UI.
- Reveal: fade + 24px rise, staggered 80ms (GSAP ScrollTrigger).
- Hero: arch mask opens from 0 → full (clip-path) on load; star twinkle in night sections.
- Parallax ≤ 8% on hero image only.
- All motion disabled under `prefers-reduced-motion`.

## 9. RTL rules

- `dir="rtl"` on `<html>` for `ar` and `he`; layout uses logical properties (`ms-*`, `me-*`, `ps-*`, `start-*`).
- Directional icons (arrows, chevrons) flip with `rtl:-scale-x-100`; brand marks and clocks never flip.
- Numerals: Western digits in all locales for prices and dates (clearer for mixed audiences); Arabic month names via Carbon locale.
- No letter-spacing / uppercase transformations in Arabic or Hebrew.
