=== WordPress Importer ===
Contributors: wordpressdotorg
Donate link: https://wordpressfoundation.org/donate/
Tags: importer, wordpress
Requires at least: 3.6
Tested up to: 4.9
Stable tag: 0.6.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Import posts, pages, comments, custom fields, categories, tags and more from a WordPress export file.

== Description ==

The WordPress Importer will import the following content from a WordPress export file:

* Posts, pages and other custom post types
* Comments
* Custom fields and post meta
* Categories, tags and terms from custom taxonomies
* Authors

For further information and instructions please see the [Codex page on Importing Content](https://codex.wordpress.org/Importing_Content#WordPress)

== Installation ==

The quickest method for installing the importer is:

1. Visit Tools -> Import in the WordPress dashboard
1. Click on the WordPress link in the list of importers
1. Click "Install Now"
1. Finally click "Activate Plugin & Run Importer"

If you would prefer to do things manually then follow these instructions:

1. Upload the `wordpress-importer` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to the Tools -> Import screen, click on WordPress

== Changelog ==

= 0.6.4 =
* Improve PHP7 compatibility.
* Fix bug that caused slashes to be stripped from imported comments.
* Fix for various deprecation notices including `wp_get_http()` and `screen_icon()`.
* Fix for importing export files with multiline term meta data.

= 0.6.3 =
* Add support for import term metadata.
* Fix bug that caused slashes to be stripped from imported content.
* Fix bug that caused characters to be stripped inside of CDATA in some cases.
* Fix PHP notices.

= 0.6.2 =
* Add `wp_import_existing_post` filter, see [Trac ticket #33721](https://core.trac.wordpress.org/ticket/33721).

= 0.6 =
* Support for WXR 1.2 and multiple CDATA sections
* Post aren't duplicates if their post_type's are different

= 0.5.2 =
* Double check that the uploaded export file exists before processing it. This prevents incorrect error messages when
an export file is uploaded to a server with bad permissions and WordPress 3.3 or 3.3.1 is being used.

= 0.5 =
* Import comment meta (requires export from WordPress 3.2)
* Minor bugfixes and enhancements

= 0.4 =
* Map comment user_id where possible
* Import attachments from `wp:attachment_url`
* Upload attachments to correct directory
* Remap resized image URLs correctly

= 0.3 =
* Use an XML Parser if possible
* Proper import support for nav menus
* ... and much more, see [Trac ticket #15197](https://core.trac.wordpress.org/ticket/15197)

= 0.1 =
* Initial release

== Upgrade Notice ==

= 0.6 =
Support for exports from WordPress 3.4.

= 0.5.2 =
Fix incorrect error message when the export file could not be uploaded.

= 0.5 =
Import comment meta and other minor bugfixes and enhancements.

= 0.4 =
Bug fixes for attachment importing and other small enhancements.

= 0.3 =
Upgrade for a more robust and reliable experience when importing WordPress export files, and for compatibility with WordPress 3.1.

== Frequently Asked Questions ==

= Help! I'm getting out of memory errors or a blank screen. =
If your exported file is very large, the import script may run into your host's configured memory limit for PHP.

A message like "Fatal error: Allowed memory size of 8388608 bytes exhausted" indicates that the script can't successfully import your XML file under the current PHP memory limit. If you have access to the php.ini file, you can manually increase the limit; if you do not (your WordPress installation is hosted on a shared server, for instance), you might have to break your exported XML file into several smaller pieces and run the import script one at a time.

For those with shared hosting, the best alternative may be to consult hosting support to determine the safest approach for running the import. A host may be willing to temporarily lift the memory limit and/or run the process directly from their end.

-- [WordPress Codex: Importing Content](https://codex.wordpress.org/Importing_Content#Before_Importing)

== Filters ==

The importer has a couple of filters to allow you to completely enable/block certain features:

* `import_allow_create_users`: return false if you only want to allow mapping to existing users
* `import_allow_fetch_attachments`: return false if you do not wish to allow importing and downloading of attachments
* `import_attachment_size_limit`: return an integer value for the maximum file size in bytes to save (default is 0, which is unlimited)

There are also a few actions available to hook into:

* `import_start`: occurs after the export file has been uploaded and author import settings have been chosen
* `import_end`: called after the last output from the importer
