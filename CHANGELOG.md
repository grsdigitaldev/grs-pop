# Changelog

Tutte le modifiche rilevanti di GRS Pop. Formato [Keep a Changelog](https://keepachangelog.com/it/1.1.0/), versioni [SemVer](https://semver.org/lang/it/).

## [1.3.0] - 2026-11-20

### Added
- Top bar over the page: option "Move the fixed elements of the site down" (Format panel). While the bar is open, `body` padding, `scroll-padding-top` and the `top` of the site's fixed or sticky elements grow by the bar height (`--grs-pop-bar-h` on `<html>`, class `grs-pop-has-bar-top`), with the theme's own values kept (computed `top` read before the change, inline styles restored on close). Elements from CSS selectors and/or found automatically (fixed or sticky, `top` 0-50px, up to 6 levels below `body`, admin bar and full-height panels excluded). Height followed with `ResizeObserver`; transition only if the element has none; off with reduced motion. On for new popups and templates, off for popups saved with earlier versions.
- Tab to reopen a bar or slide-in after closing (Triggers panel, "Reopen after closing"): text, icon, position (automatic or one of four corners), distance from the top, alignment to the site content width, colours with a contrast warning, live example. Shown after a close even when Frequency blocks the automatic opening; hidden after a conversion (unless "Keep showing it"), at the end of the schedule, when the conditions no longer match and on devices where the popup is not a bar or slide-in. Under a fixed header it hangs below the header (follows it while scrolling). Accessible button (`aria-label`, `aria-controls`, focus back on the tab after closing, `hidden` when not shown). The reopening counts as a view; new browser event `grs-pop:reopen` and `reopen: true` in the following `grs-pop:view`. Text in the GRS i18n bridge (`popup_{ID}_reopen_label`). Extra CSS classes of the popup copied to the tab with the suffix `-reopen`.
- Frequency "Once per visit": a visit ends after 5-240 minutes without activity (default 30) on the pages that load GRS Pop. With "After it is closed, hide it for 0 days" the popup comes back at the next visit. Browser storage adds the last activity time and the last 20 long pauses (`la`, `gp`).

### Changed
- The existing frequency "Once per visit" is now called "Once per browser session (until the tab is closed)", with a note in the builder; behaviour unchanged.
- Privacy text: mentions the time of the last activity stored in the browser.

## [1.2.0] - 2026-11-20

### Added
- "Coming soon" tab (administrators): coming soon mode (status 200, `noindex` in header and meta) or maintenance mode (status 503 with `Retry-After`, until the launch date or one hour). Courtesy page served on `template_redirect` before the theme, without theme assets or other plugins' scripts: own CSS, palette colours, light or brand style.
- Content: logo (theme logo, another image or none, adjustable width), title, text (strong, em, links), email, phone, WhatsApp, address, social profiles, countdown to the launch date with optional automatic switch-off, GRS Forms form (shortcode, assets enqueued at render time are printed).
- Access: administrators always, chosen roles, secret preview link (HttpOnly cookie valid 1-60 days, regenerable). Login, dashboard, REST, AJAX, cron, robots.txt and favicon stay reachable.
- Cache: page cache cleared at every save; courtesy page and pages seen through the secret link sent with no-cache headers (`DONOTCACHEPAGE`, LiteSpeed no-cache, `no-store, private`).
- Four starting templates (coming soon with launch, minimal coming soon, short maintenance, scheduled maintenance): they replace title, text, style and countdown settings and propose a launch date (in 14 days or tomorrow at 09:00) if none is set in the future; mode, access, logo, contacts, form and secret link are kept. Applied from a separate form (`admin-post.php`, nonce, `manage_options`).
- Preview in the tab (desktop and mobile) with nonce, also while the mode is off; warning in the admin bar while active.
- Texts in the GRS i18n bridge (context "GRS Pop", names `maintenance_heading`, `maintenance_text`, `maintenance_countdown_title`, `maintenance_address`). Option `grs_pop_maintenance` in autoload: no extra query while the mode is off.

## [1.1.0] - 2026-10-18

### Added
- Audience rule "Forms already filled in" (needs GRS Forms): show the popup to everyone, only to visitors who have not filled in, or only to those who have filled in at least one of the chosen forms, optionally in the last N days. Contract `dev/contratto-grs-pop-forms.md`.
- The browser remembers the IDs of the GRS Forms forms sent and the date (localStorage `grs_pop`, key `f`, at most 50), also on pages without popups, only while an active popup uses the rule: a tiny inline listener, no extra files or queries.
- Integrations declared in the GRS DEVs Overview: pop-forms, pop-forms-audience, pop-tracking.
- Privacy text: forms sent remembered in the browser.
- Page cache cleared when popups or settings are saved, with the public hooks of LiteSpeed Cache, WP Rocket, WP Super Cache and W3 Total Cache (only if present).

### Changed
- GRS DEVs component 1.2.0 (Integrations card in the Overview).
- The builder reads the list of forms from the GRS Forms REST API (`GET grs-forms/v1/forms`) instead of its posts; GRS Forms is detected with `GRS_FORMS_VERSION`.
- Settings, Integrations: GRS Tracking shown as "Update to 1.1.0" until the version that listens to the GRS Pop events.

## [1.0.0] - 2026-10-18

### Added
- Formats: centred popup, slide-in (4 corners or bottom sheet), top/bottom bar (fixed or pushing the page), full screen / welcome mat, floating tab with panel, inline box (automatic position, shortcode or block).
- Different format and position for desktop, tablet and mobile, with a warning for intrusive formats on mobile.
- Section-based editor with live preview on desktop, tablet and mobile: title, text, button (with icons), GRS Forms form, coupon code, countdown (date or per visitor), list, spacer, "no thanks" link, shortcode.
- 31 ready-made templates using a site colour palette.
- Triggers: delay, scroll, exit intent (with mobile fallback), inactivity, element visible, click (#grs-pop-ID, grs-pop-open-ID class, custom selector).
- Audience rules: pages, post types, terms, archives, URL, logged-in users and roles, language; in the browser: device, referrer, UTM parameters, new or returning visitor, page views, schedule.
- Frequency: every N days, once per visit, only once, after closing, after conversion, maximum views; one covering popup at a time.
- Anonymous daily statistics (views, closes, conversions, clicks) by device, with automatic cleanup.
- Events for GRS Tracking: grs-pop:view, grs-pop:close, grs-pop:convert.
- Multilingual: English code with Italian translation, popup texts in the GRS i18n bridge (WPML, Polylang, GRS Loco).
- GRS DEVs menu, shared GRS style, updates from GitHub releases.
