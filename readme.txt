=== GRS Pop ===
Contributors: grsdigital
Tags: popup, slide-in, notification bar, welcome mat, lead generation
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight popups, slide-ins, bars, welcome mats, inline boxes and floating tabs with ready-made templates, smart triggers and mobile-first display rules.

== Description ==

GRS Pop shows messages, offers and forms in the most suitable format for every device:

* centred popup, slide-in, top or bottom bar, full screen / welcome mat, floating tab, inline box;
* a different format for desktop, tablet and mobile (for example a centred popup on computers and a bottom slide-in on phones);
* 31 templates that follow your colour palette;
* triggers: delay, scroll, exit intent, inactivity, visible element, click;
* audience: pages, post types, categories, URL, users, language, device, source, UTM parameters, new or returning visitors, schedule;
* with GRS Forms: show a popup only to visitors who have (or have not) already filled in a form, for example no newsletter popup for subscribers;
* frequency limits remembered in the visitor's browser with technical data only;
* anonymous daily statistics and events for GRS Tracking;
* "coming soon" and maintenance mode: a courtesy page with logo, text, contacts, countdown and a GRS Forms form, in your brand colours.

Forms come from GRS Forms (leads, anti-spam, notifications, conversions). Without popups for a page the plugin adds no queries and loads no files; with popups it adds one query and two small files (no jQuery, no external resources).

== Installation ==

1. Upload the plugin zip from Plugins → Add New → Upload Plugin and activate it.
2. Go to GRS DEVs → Pop → Settings and set your colour palette.
3. GRS DEVs → Pop → Create popup: choose a template, edit the content, choose format, triggers and audience, switch it to Active and save.

== Frequently Asked Questions ==

= How do I open a popup from a button or a menu item? =
Link to `#grs-pop-ID` or add the CSS class `grs-pop-open-ID` (ID is shown in the list). The shortcode `[grs_pop id="ID"]Text[/grs_pop]` prints a link that opens it.

= How do I put a box inside a page? =
Choose "Inside the content" in the Format panel and use an automatic position, the shortcode `[grs_pop id="ID"]` or the "GRS Pop inline" block.

= Does it work with page caching? =
Yes. Page rules are applied on the server; visitor rules (device, source, frequency, schedule) are applied in the browser.

= Is it GDPR compliant? =
GRS Pop stores no personal data. Statistics are anonymous daily totals; frequency limits use technical data in the visitor's browser (localStorage/sessionStorage). A suggested text is added to Settings → Privacy.

= How does the "forms already filled in" rule work? =
It needs GRS Forms. When a popup with this rule is active, the visitor's browser remembers which GRS Forms forms were sent and when (only the form ID and the date, never the data entered), on every page of the site. Forms sent before the rule was activated are not known. Without GRS Forms the rule is ignored.

= How does the coming soon / maintenance mode work? =
GRS DEVs, then Pop, then Coming soon. "Coming soon" answers with status 200 and noindex (site not launched yet); "Maintenance" answers with status 503 and Retry-After, so search engines come back later without losing rankings. Administrators and the roles you choose see the real site; a secret link lets a client who is not logged in see it too. Login, dashboard, REST API, cron and robots.txt stay reachable. The courtesy page does not load the theme or the scripts of other plugins: only its own style and the GRS Forms form, if chosen. The page cache is cleared at every save and the courtesy page is never cached. To start quickly, pick one of the four templates at the top of the tab: it fills title, text, style and countdown and keeps logo, contacts and secret link.

= My theme has a fixed header: does the bar at the top cover it? =
Not if you turn on "Move the fixed elements of the site down" in the Format panel (on by default for new popups): while the bar is open the page and the fixed header slide down by its height. If the automatic search misses an element, add its CSS selector.

= Can visitors reopen a bar after closing it? =
Yes: in Triggers, "Reopen after closing", turn on the tab and choose text, icon, position and colours. It works for bars and slide-ins.

= Who can create popups? =
Administrators and Editors (capability grs_pop_manage_popups). Only Administrators can change the settings.

== Changelog ==

= 1.3.0 =
* Bars at the top no longer cover fixed headers: the page and the site's fixed elements (header, menu) slide down while the bar is open.
* New tab to reopen a bar or slide-in after the visitor has closed it, with text, icon, position and colours.
* New frequency "Once per visit" (a visit ends after 30 minutes without activity, adjustable); the previous option is now called "Once per browser session".

= 1.2.0 =
* New "Coming soon" tab: coming soon mode (200 + noindex) or maintenance mode (503 + Retry-After) with logo, title, text, contacts, social profiles, countdown, GRS Forms form, light or brand style, preview on desktop and mobile.
* Four starting templates for the courtesy page: coming soon with launch, minimal coming soon, short maintenance, scheduled maintenance.
* Access for chosen roles and through a secret preview link; automatic switch-off at the launch date; warning in the admin bar while the mode is active.

= 1.1.0 =
* New audience rule with GRS Forms: show the popup only to visitors who have, or have not, filled in one of the chosen forms, optionally in the last N days.
* The list of forms comes from the public REST API of GRS Forms.
* GRS DEVs component 1.2.0: integrations with the other GRS plugins shown in the Overview.
* Page cache (LiteSpeed Cache, WP Rocket, WP Super Cache, W3 Total Cache) cleared when popups or settings are saved.

= 1.0.0 =
* First release.

== Upgrade Notice ==

= 1.3.0 =
Fixed headers and top bars, tab to reopen, frequency per visit. If your theme already moves the header for a GRS Pop bar, remove that code when you turn on the new option.

= 1.2.0 =
New coming soon and maintenance mode.

= 1.1.0 =
New "forms already filled in" rule with GRS Forms.

= 1.0.0 =
First release.
