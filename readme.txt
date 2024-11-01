=== WPFTS Add-on for WP Download Manager ===
Contributors: Epsiloncool
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=EJ6FG3AJKYGG8&item_name=New+laptop+for+faster+development&currency_code=USD&source=url
Tags: fulltext search, wp download manager, wpfts add-on, file search, content search
Requires at least: 4.6
Tested up to: 6.0.2
Stable tag: 1.10.24
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

A data bridge between WP Download Manager plugin and WP Fast Total Search plugin to allow it index and search files, uploaded via WPDM.

== Description ==

There is an awesome plugin WP Download Manager, which allows us to upload and manage files in a very comfortable way. But unfortunately, it has no deep file search functionality.

This WPFTS add-on plugin makes a "bridge" for WPFTS to let it understand WP Download Manager's files and implement the search on them by content.

Changes in 1.10.24
Added support for WPFTS Pro v3.x+

Fixed in 1.8.18
Improved local file handling

Fixed in 1.7.16
Improved cache update when saving a WPDM post

Added in 1.6.14
Improved compatibility with WP Download Manager Pro 6.1.2

Added in 1.5.12
Improved compatibility with WP Download Manager Pro 6.0.8

Added in 1.4.11
Improved compatibility with WP Download Manager 3.2.15 (free) and WP Download Manager Pro 6.0.7
Improved compatibility with WPFTP Pro 2.46.180 and Wordpress 5.8

Added in 1.3.6:
Improved compatibility with WP Download Manager 3.1.0.9 (free) and WP Download Manager Pro 5.0.8.

Added in 1.2.1:
Special tags which you can insert to File Pages to show extracted text (may be used for Google Indexing or file previews).

`[wpfts_dm_rawtext]`
Will show extracted file content without any formatting or escaping

`[wpfts_dm_rawtext_esc]`
Will show extracted file content with htmlspecialchars() escaping

`[wpfts_dm_rawtext_pre]`
escaped and wrapped by `pre` tag and `class="wpfts_dm_rawtext_pre"`

`[wpfts_dm_rawtext_div]`
escaped and wrapped by `div` tag and `class="wpfts_dm_rawtext_div"`


= Documentation =

Please refer [WPFTS Add-on for WP Download Manager Documentation](https://fulltextsearch.org/addon/wpfts-download-manager "WPFTS Add-on for WP Download Manager Documentation").

== Installation ==

1. Unpack and upload `wpfts-download-manager-addon` folder with all files to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Make sure WPFTS Pro is active and WP Download Manager is active too
1. Go to WPFTS Pro Settings page / Main Settings and press "Rebuild Index" button

== Frequently Asked Questions ==

= Where can I put some notices, comments or bugreports? =

Do not hesistate to write to us at [Contact Us](https://fulltextsearch.org/contact/ "Contact Us") page.

== Changelog ==

= 1.10.24 =
Added support for WPFTS Pro v3.x+

= 1.8.18 =
Improved local file handling

= 1.7.16 =
Improved cache update when saving a WPDM post

= 1.6.14 =
Improved compatibility with WP Download Manager Pro 6.1.2

= 1.5.12 =
* Improved compatibility with WP Download Manager Pro 6.0.8

= 1.4.11 =
* Improved compatibility with WP Download Manager 3.2.15 (free) and WP Download Manager Pro 6.0.7
* Improved compatibility with WPFTP Pro 2.46.180 and Wordpress 5.8

= 1.3.6 =
* Added support for Pro and Free WP Download Managers (since they have changed their file storage format slightly)

= 1.2.1 =
* Added some Download Manager "tags" which could be included to page templates

= 1.1.1 =
* The issue detected by the Wordpress team was fixed

= 1.1.0 =
* Alpha release, checked and tested

= 1.0.0 =
* Test release