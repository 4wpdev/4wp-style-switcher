=== 4WP Theme Style Switcher ===
Contributors: 4wpdev
Tags: full-site-editing, theme.json, dark mode, style variations, block-theme
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.2.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Apply theme.json style variations per page or let visitors switch styles on the frontend (FSE block themes).

== Description ==

4WP Theme Style Switcher reads **style variations** from the active block theme (`theme.json` and `/styles/*.json`).

**Live demo (WordPress Playground):** https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/4wpdev/4wp-style-switcher/v0.2.3/.wordpress-org/assets/blueprints/blueprint.json

The demo site **Style Switcher** uses Twenty Twenty-Five: five pages (About, Morning, Afternoon, Night, plus one alternate theme variation), per-page styles, A/B light vs dark, a bottom-right visitor switcher, and Light/Dark in the header and footer menus.

**Page style (editor)** — pick a variation for a page; optionally lock it so visitors cannot override it.

**Frontend switcher** — visitors choose from allowed variations; the choice is stored in localStorage and synced to a cookie for server-side rendering.

**Light / Dark (navigation block)** — add the Light/Dark block inside Navigation; map two variations (sun/moon icons).

**A/B testing** — split new visitors between two variations; daily aggregate stats monitor the traffic split.

== Installation ==

1. Upload and activate the plugin.
2. Go to **Settings → 4WP Style Switcher**.
3. On **Variations**, check which style variations visitors may use.
4. Under **General**, set the default variation and optional Light/Dark mapping.
5. Edit a page → **Page style** panel to assign or lock a variation.
6. In the site editor, insert **Light / Dark** inside the **Navigation** block.

== Frequently Asked Questions ==

= Which themes are supported? =

Block themes (FSE) that ship style variations under the theme’s `/styles/` directory.

= Where is the Playground demo configured? =

In the GitHub repository: `playground/setup.php` and `.wordpress-org/assets/blueprints/blueprint.json`.

= Does the floating switcher list every theme variation? =

It lists variations allowed on the **Variations** settings tab. The menu Light/Dark block uses only the two variations mapped under **General**.

== Screenshots ==

1. Settings — General tab (default variation, visitor switcher, Light/Dark mapping).
2. Settings — Variations tab (allowed style variations).
3. Frontend — floating style switcher and menu Light/Dark toggle on Twenty Twenty-Five.

== Upgrade Notice ==

= 0.2.3 =
Playground showcase site: per-page styles, A/B testing, locked pages, shared nav in header/footer.

= 0.2.2 =

= 0.2.1 =
Fixes style switching on WordPress Playground (HTTPS cookie + dynamic demo slugs).

= 0.2.0 =
First feature release: visitor switcher, Light/Dark navigation block, A/B stats, and Playground demo.

== Changelog ==

= 0.2.3 =
* Playground demo site "Style Switcher" with About / Morning / Afternoon / Night / plus one alternate theme variation page.
* Per-page style meta, A/B light vs dark, visitor switcher bottom-right, two locked demo pages.
* Shared navigation in header and footer template parts.
* Fix Documentation tab accordion spacing in admin.

= 0.2.2 =
* Apply visitor style from the query param on the same request (no redirect; works in Playground iframe).
* Clean the style query param from the URL after the page loads.

= 0.2.1 =
* Fix visitor style switching on WordPress Playground (server-side cookie via query param).
* Playground setup resolves variation slugs from the active theme dynamically.
* Cookie sync uses Secure flag on HTTPS.

= 0.2.0 =
* Style registry and resolver for theme.json variations.
* Admin settings (General, Variations, A/B Testing) with REST API.
* Frontend floating switcher with localStorage and cookie sync.
* Light/Dark Gutenberg block for the Navigation menu.
* A/B testing with lightweight daily stats table.
* WordPress Playground blueprint (Morning / Afternoon / Midnight demo).
* Fixes: empty allowed-variations recovery, A/B REST recursion, partial settings save.

= 0.1.0 =
* Initial scaffold.

== Other Notes ==

**Hooks (developers)**

* `forwp_style_switcher_variations` — filter theme variation list.
* `forwp_style_switcher_ab_assigned` — after A/B cohort assignment.
* `forwp_style_switcher_analytics_track` — analytics events.
* `forwp_style_switcher_ab_assignment_enabled` — filter A/B assignment.

**Playground URL constant:** `FORWP_STYLE_SWITCHER_PLAYGROUND_URL` in the main plugin file.
