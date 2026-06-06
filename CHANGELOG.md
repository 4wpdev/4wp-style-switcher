# Changelog

## 1.0.0 — 2026-06-06

- First WordPress.org release.
- Plugin Check fixes; readme trademark and Playground blueprint v1.0.0.

## 0.2.5 — 2026-06-01

- Performance: request-level caching for theme variation lookups, style resolution, and theme.json merge preparation.
- GitHub README banner (`.github/4WP-Style-Switcher.png`); Playground blueprint and demo links updated to v0.2.5.

## 0.2.4 — 2026-05-31

- Playground demo: page titles from theme.json variation names; no duplicate H1 in content.
- Per-page styles (Morning / Afternoon / Evening / Night); Afternoon locked.
- Light/Dark navigation toggle inactive when page style is locked.

## 0.2.3 — 2026-05-31

- Playground demo: five pages (About, Morning, Afternoon, Night, + alternate theme variation), per-page styles, A/B light/dark, switcher bottom-right, locked pages, shared header/footer navigation.
- Admin Documentation tab layout fix (`admin.css`).

## 0.2.2 — 2026-05-31

- Fix Playground blueprint: install plugin zip from the same tag (was still pulling v0.2.0).
- Apply visitor style on the same request via query param (no redirect; Playground iframe safe).
- Remove style query param from URL after page load.

## 0.2.1 — 2026-05-31

- Fix visitor style switching on WordPress Playground (query-param → server-side cookie).
- Playground setup resolves demo slugs from the active theme.
- Cookie sync uses `Secure` on HTTPS.

## 0.2.0 — 2026-05-31

- Visitor frontend switcher (localStorage + cookie).
- Light/Dark navigation block.
- Admin settings with REST API; Variations allow-list.
- A/B testing with daily stats table.
- Playground blueprint demo (TT5: Morning / Afternoon / Midnight).
- Fixes: empty `allowed_variations`, A/B `is_ready()` recursion.

## 0.1.0

- Initial scaffold.
