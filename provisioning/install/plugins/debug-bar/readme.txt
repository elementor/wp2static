=== Debug Bar ===
Contributors: wordpressdotorg, ryan, westi, koopersmith, duck_, mitchoyoshitaka, dd32, jrf, obenland
Tags: debug
Tested up to: 4.8
Stable tag: 0.9
Requires at least: 3.4

Adds a debug menu to the admin bar that shows query, cache, and other helpful debugging information.

== Description ==

Adds a debug menu to the admin bar that shows query, cache, and other helpful debugging information.

A must for developers!

When `WP_DEBUG` is enabled it also tracks PHP Warnings and Notices to make them easier to find.

When `SAVEQUERIES` is enabled the mysql queries are tracked and displayed.

To enable these options, add the following code to your `wp-config.php` file:
`
define( 'WP_DEBUG', true );
define( 'SAVEQUERIES', true );
`

Add a PHP/MySQL console with the [Debug Bar Console plugin](https://wordpress.org/plugins/debug-bar-console/).

There are numerous other add-ons available to get more insight into, for instance, the registered Post Types, Shortcodes, WP Cron, Language file loading, Actions and Filters and so on. Just [search the plugin directory for 'Debug Bar'](https://wordpress.org/plugins/search/debug+bar/).

== Upgrade Notice ==

= 0.9 =
Added panel navigation to toolbar.
Improved localization support.
Security fixes.

= 0.8.4 =
Updated to avoid incompatibilities with some extensions.

= 0.8.3 =
Updated to avoid PHP7 Deprecated notices.

= 0.8.2 =
Updated to handle a new deprecated message in WordPress 4.0.

= 0.8.1 =
Minor security fix.

= 0.8 =
WordPress 3.3 compatibility
UI refresh
Removed jQuery UI requirement
Full screen by default
New debug-bar query parameter to show on page load
Removed display cookies
JavaScript error tracking (disabled by default)

= 0.7 =
Made compatible with PHP < 5.2.0
CSS Tweaks
Load JavaScript in Footer
Fixed display issues for WP_Query debug on CPT archives pages
SQL/DB error tracking

= 0.6 =
Added maximize/restore button
Added cookie to keep track of debug bar state
Added post type information to WP_Query tab
Bug fix where bottom of page was obscured in the admin

= 0.5 =
New UI
Backend rewritten with a class for each panel
Many miscellaneous improvements

= 0.4.1 =
Compatibility updates for trunk

= 0.4 =
Added DB Version information
Updated PHP Warning and Notice tracking so that multiple different errors on the same line are tracked
Compatibility updates for trunk

= 0.3 =
Added WordPress Query infomation
Added Request parsing information

= 0.2 =
Added PHP Notice / Warning tracking when WP_DEBUG enabled
Added deprecated function usage tracking

= 0.1 =
Initial Release

== Changelog ==

= 0.9 =
Added panel navigation to toolbar.
Improved localization support.
Security fixes.

= 0.8.4 =
Updated to avoid incompatibilities with some extensions.

= 0.8.3 =
Updated to avoid PHP7 Deprecated notices.

= 0.8.2 =
Updated to handle a new deprecated message in WordPress 4.0.

= 0.8.1 =
Minor security fix.

= 0.8 =
WordPress 3.3 compatibility
UI refresh
Removed jQuery UI requirement
Full screen by default
New debug-bar query parameter to show on page load
Removed display cookies
JavaScript error tracking (disabled by default)

= 0.7 =
Made compatible with PHP < 5.2.0
CSS Tweaks
Load JavaScript in Footer
Fixed display issues for WP_Query debug on CPT archives pages
SQL/DB error tracking

= 0.6 =
Added maximize/restore button
Added cookie to keep track of debug bar state
Added post type information to WP_Query tab
Bug fix where bottom of page was obscured in the admin

= 0.5 =
New UI
Backend rewritten with a class for each panel
Many miscellaneous improvements

= 0.4.1 =
Compatibility updates for trunk

= 0.4 =
Added DB Version information
Updated PHP Warning and Notice tracking so that multiple different errors on the same line are tracked
Compatibility updates for trunk

= 0.3 =
Added WordPress Query infomation
Added Request parsing information

= 0.2 =
Added PHP Notice / Warning tracking when WP_DEBUG enabled
Added deprecated function usage tracking

= 0.1 =
Initial Release

== Installation ==

Use automatic installer.
