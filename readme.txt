=== Anchor Episodes Index ===
Contributors: jeswd
Tags: anchor.fm, podcast, embed
Requires at least: 4.8
Tested up to: 5.8
Requires PHP: 5.2
Stable tag: 1.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A lightweight plugin that allows you to output an anchor.fm podcast player on your site that includes an episode index. Just add two URL's on the settings page, grab the shortcode, and you're good to go!

== Description ==

This plugin appends an episode index to the anchor.fm podcast player. Currently, the only available embed for the anchor.fm player is for a single episode. 
This plugin allows you to add your entire episode index, just the same as you have on your anchor.fm site. 
Simply input your site and RSS URL's on the settings page and add the shortcode to any page or post. 

You can see more information on the problem this solves [in this article](https://jeswebdevelopment.com/add-an-episode-index-to-the-anchor-podcasts-embed-player/)

Email me [here](mailto:jesse@jeswebdevelopment.com) for suggestions and feedback.

== Usage ==

Firstly, install and activate Anchor Episodes Index (this plugin).

Once activated, go to the settings and fill out the Anchor Site URL and Anchor RSS URL fields (be sure to add the URL's without a "/" at the end, otherwise it will not work).

Then copy the shortcode you'll see on the settings page and paste it in any page or post.

If you want to add multiple different podcasts on the site, you can define the RSS and Site URL's as shortcode attributes instead of on the settings page. 

Shortcode examples: 
- [anchor_episodes] - Uses values set in the settings page
- [anchor_episodes site_url="https://anchor.fm/your-podcast" rss_url="https://anchor.fm/s/123456-your-key/podcast/rss"] - Overrides values set on the settings page

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