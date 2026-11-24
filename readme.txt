=== Calico Dynamic Date Selection for WordPress Menus ===
Requires at least: 5.4
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Schedule individual links and submenus directly in Appearance > Menus.

== Usage ==

1. Upload and activate the ZIP under Plugins > Add New Plugin > Upload Plugin.
2. Open Appearance > Menus and expand the menu item you want to schedule.
3. Under Visibility schedule, add a period and pick a rule and date. Add more rules to define that period.
4. For a separate run of dates, add another visibility period. Click Save Menu when finished.

The item appears if every rule in at least one period matches. With no periods, the item follows the menu's normal visibility. A hidden parent also hides its submenu.

* Show On and Before: Show through the selected date.
* Show On and After: Show starting on the selected date.
* Hide Before: Hide earlier dates. The selected date is allowed.
* Hide After: Hide later dates. The selected date is allowed.

For example, to show a cruise from November 20 through November 28 and again from December 10 through December 24, add one period with November 20 and November 28 and another with December 10 and December 24. Use Show On and After for each start and Hide After for each end.

Dates change at midnight in the timezone under Settings > General. If a page cache, CDN, or builder caches your menu HTML, configure that cache to expire or purge at the scheduled boundary.

== Compatibility ==

Works with classic WordPress menus rendered through wp_nav_menu(), including themes and builders that use it. Navigation blocks in the Site Editor and independently rendered link lists use different systems.

== Updates ==

To upgrade from Calico Menu Scheduler 1.0.0, upload the release ZIP once and choose Replace current with uploaded. Existing schedules are carried forward. GitHub updates are available under Dashboard > Updates on WordPress 5.8 or newer.

If you need to recover the menu's regular visibility while troubleshooting, deactivate the plugin. Reactivation restores the saved dates.

== Changelog ==

= 1.1.0 =
* Renamed the plugin to Calico Dynamic Date Selection for WordPress Menus.
* Added WordPress update checks for stable GitHub releases.

= 1.0.0 =
* Added editable visibility periods and four date rules to classic menu items.
