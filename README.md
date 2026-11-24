# Calico Dynamic Date Selection for WordPress Menus

Schedule when individual links appear in classic WordPress menus. Set the dates directly inside each expanded item under **Appearance → Menus**.

[Download the installable ZIP](https://github.com/CalicoDesignCo/calico-menu-scheduler/releases/latest/download/calico-menu-scheduler.zip)

## Install and use

1. Under **Plugins → Add New Plugin → Upload Plugin**, upload `calico-menu-scheduler.zip` and activate it.
2. In **Appearance → Menus**, expand an item and choose **Add visibility period**.
3. Select a rule and date. Add rules to set both a start and an end, or add another period to bring the item back on different dates.
4. Click **Save Menu**.

| Rule | When the rule allows the item to appear |
| --- | --- |
| Show On and Before | Through the selected date |
| Show On and After | Starting on the selected date |
| Hide Before | Starting on the selected date |
| Hide After | Through the selected date |

An item appears when it meets every date rule in at least one period. An item without a period appears as usual. Hiding a parent hides its submenu. Dates follow the site timezone in **Settings → General**. If a cache stores rendered menu HTML, its expiry or purge schedule determines when visitors see the change.

## Compatibility and updates

Requires WordPress 5.4 or newer and PHP 7.4 or newer. Supports classic menus rendered via `wp_nav_menu()`. Site Editor Navigation blocks have their own editing and rendering system.

If you installed Calico Menu Scheduler 1.0.0, upload this ZIP and choose **Replace current with uploaded** once. The plugin keeps its installed folder and saved schedules. Future stable releases appear in **Dashboard → Updates** on WordPress 5.8 or newer. You can deactivate the plugin to restore regular menu visibility during troubleshooting. Reactivation restores the saved schedules.

## Changelog

- **1.1.0** Renamed the plugin and added GitHub-backed WordPress updates.
- **1.0.0** Added four date rules and multiple visibility periods to classic menu items.

Licensed under GPL-2.0-or-later. See [LICENSE](LICENSE).
