=== Anchor Episodes Index (Spotify for Podcasters) ===
Contributors: jeswd
Tags: anchor.fm, podcast, embed, spotify
Requires at least: 4.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 2.1.11
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A lightweight plugin that allows you to output an anchor.fm podcast player on your site that includes an episode index. Just add two URL's on the settings page, grab the shortcode, and you're good to go!

== Announcements ==

* Anchor Episodes Index Pro is available now! [Learn more here](https://jesweb.dev) 

== Description ==

This plugin appends an episode index to the anchor.fm podcast player. Currently, the only available embed for the anchor.fm player is for a single episode. 
This plugin allows you to add your entire episode index, just the same as you have on your anchor.fm site. 
Simply input your site and RSS URL's on the settings page and add the shortcode to any page or post. 

Email me [here](mailto:jesse@jesweb.dev) for suggestions and feedback.

== Usage ==

Firstly, install and activate Anchor Episodes Index (this plugin).

Once activated, go to the settings and fill out the Anchor Site URL and Anchor RSS URL fields (be sure to add the URL's without a "/" at the end, otherwise it will not work).

Then copy the shortcode you'll see on the settings page and paste it in any page or post.

If you want to add multiple different podcasts on the site, you can define the RSS and Site URL's as shortcode attributes instead of on the settings page. Note, you cannot currently output two players on one page.

== Shortcode examples ==
Uses values set in the settings page:
`[anchor_episodes]`
Overrides values set on the settings page:
`[anchor_episodes site_url="https://anchor.fm/your-podcast" rss_url="https://anchor.fm/s/123456-your-key/podcast/rss" max_episodes="10"]`

== Changelog ==

= 1.0.0 =
* Initial Release.

= 1.0.1 =
* Enqueued files dir path fix

= 1.0.2 =
* Fixed gap between iframe and index. Improved enqueue code to avoid previous issue

= 1.0.3 =
* Revert to old enqueue due to issues

= 1.1.0 =
* Added loading animation and bg color when loading, added expand description function

= 1.1.1 =
* Added ability for shortcode attributes to be defined for the Site and RSS URL's, so that different podcast can be output on the site at one time. 

= 1.1.2 =
* Added option for user to define the maximum amount of episodes to display. Add arguments to enqueue scripts in footer.

= 1.2.1 =
* Improved CSS structure so that themes conflict with the styles less. Refactored JS for better speed and readability. Removed feednami and now working with the XML directly. Improved naming throughout code.

= 1.2.2 =
* Update readme. Tested for WP v6.

= 1.2.3 =
* Fix minor update bug

= 1.2.4 =
* RSS feed duration value changed from timestamp to HH:mm:ss. Removed conversion function and display as is.

= 1.2.5 =
* episode description now respects anchor.fm text formatting

= 2.0.0 =
* improved text formatting. added script localization to output PHP vars.

= 2.0.1 =
* Fixed episode title link being old iframe url

= 2.0.2 =
* Switched from using file_get_contents to curl for getting RSS feed due to reports of some servers denying requests. Error was: file_get_contents(): https:// wrapper is disabled in the server configuration by allow_url_fopen=0

= 2.0.30 =
* sanitize description and excerpt for html and special chars

= 2.0.31 =
* fix broken plugin settings link

= 2.0.40 =
* Enhanced integration with Pro version

= 2.1.0 =
* Pro version release. Added links and notices to alert users. 

= 2.1.1 =
* Fix minor filemtime() issue with admin.css

= 2.1.2 =
* Add sale notice

= 2.1.3 =
* Fix sale notice

= 2.1.4 =
* Fix sale notice

= 2.1.5 =
* Fix RSS URL override in shortcode attributes - save transient per rss url

= 2.1.6 =
* Fix RSS URL override - fix typo

= 2.1.7 =
* RSS URL override - handle for pro version

= 2.1.8 =
* Fix XSS vulnerability - alerted by patchstack.com

= 2.1.9 =
* Test with WP 6.4

= 2.1.10 =
* Test with WP 6.6 and php 8.2

= 2.1.11 =
* Fix XSS vulnerability for contributor role