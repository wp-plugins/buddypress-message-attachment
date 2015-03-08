=== BuddyPress Message Attachment ===
Contributors: ckchaudhary 
Tags: buddypress, message attachment
Requires at least: 4.0
Tested up to: 4.1.1
Stable tag: 2.1.1
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send attachments with private messages in BuddyPress!

== Description ==

Extend BuddyPress' private message feature by enabling attachments. This plugin enables users to send attachments in private messages. The type of file types allowed and maximum size of the attachment can be controlled by admin.

== Installation ==

You can download and install this plugin using the built in WordPress plugin installer. If you download it manually, make sure it is uploaded to "/wp-content/plugins/buddypress-message-attachment/".

If you are running a multisite installation, you can activate this plugin network wide or on individual sites. This plugin should work nicely irrespective of buddypress activated network wide or on individual sites.

After activating, make sure you change settings according to your liking/requirement. Settings can be found under **Settings>Message Attachment** in wp-admin or, on network admin screen if you have activated it network wide.

== Screenshots ==
1. Compose message screen ( with an uploaded attachment ).
2. Thread details screen, with attachment displayed.
3. Plugin settings screen.

== Changelog ==
= 2.1.1 =
* Added 'archive' file formats (e.g: zip, rar) on settings screen.
* Added zip and rar into default file types allowed.

= 2.1.0 =
* Hide file upload interface, when sending a notice to all users instead of private message.

= 2.0 =
* Complete rewrite of plugin.
* Made it multisite compatible.
* Removed unnecessary custom post type.

**Updating from 1.1 to 2.0**
Plugin has been completely rewritten. Data structure where attachment details were saved is changed. Unnecessary custom post type has been removed. This means that you'll loose all previous attachments data. There was no good way to continue supporting previous version. Plugin was not working with latest versions of wordpress and buddypress in almost all cases. But in case if your site had this plugin working, you shouldn't update the plugin right away. Please contact me, i plan on writing a separate importer to import old data into new data structure.

= 1.1 =
* Small Bug fixes

= 1.0 =
* Initial release

== Upgrade Notice ==
**Updating from 1.1 to 2.0**
Plugin has been completely rewritten. Data structure where attachment details were saved is changed. Unnecessary custom post type has been removed. This means that you'll loose all previous attachments data. There was no good way to continue supporting previous version. Plugin was not working with latest versions of wordpress and buddypress in almost all cases. But in case if your site had this plugin working, you shouldn't update the plugin right away. Please contact me http://webdeveloperswall.com/author/ckchaudhary, i plan on writing a separate importer to import old data into new data structure.