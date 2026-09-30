=== Anchor Episodes Index (Spotify for Podcasters) ===
Contributors: jeswd
Tags: anchor.fm, podcast, embed, spotify
Requires at least: 4.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.4.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Show your podcast with a full episode index on your own site, from any host's RSS feed. Add your feed URL, grab the shortcode, done.

== Announcements ==

* Anchor Episodes Index Pro is available now! [Learn more here](https://jesweb.dev) 

== Description ==

This plugin puts your whole podcast on your own site: a player plus a complete, scrollable episode index, built from your podcast's RSS feed.

It works with any podcast host that publishes a standard RSS feed, including Spotify for Creators (formerly Anchor), Buzzsprout, Transistor, Captivate, Libsyn, Podbean, RSS.com, Spreaker and Simplecast.

Paste your feed URL on the settings page and add the shortcode to any page or post.

If your show is hosted on Spotify for Creators you can optionally use Spotify's own embedded player instead of the built-in one, by filling in the Spotify Show URL field.

Email me [here](mailto:jesse@jesweb.dev) for suggestions and feedback.

== Usage ==

Firstly, install and activate Anchor Episodes Index (this plugin).

Once activated, go to the settings and fill out the Podcast RSS Feed URL field. That is the only required setting.

The Spotify Show URL field is optional and only applies to shows hosted on Spotify for Creators; fill it in if you would rather use Spotify's embedded player than the built-in one.

Then copy the shortcode you'll see on the settings page and paste it in any page or post.

If you want to add multiple different podcasts on the site, you can define the RSS and Site URL's as shortcode attributes instead of on the settings page. Note, you cannot currently output more than one player on one page.

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

= 2.1.12 =
* Add creators.spotify.com to allowed domains

= 2.1.13 =
* Fix cookie notice coming from Spotify

= 2.1.14 =
* Test with WP 6.9

= 2.1.15 =
* Update readme

= 2.2.0 =
* Compatibility: added a version handshake between the free and Pro plugins. An out-of-date Pro build now falls back gracefully and shows a notice instead of risking a fatal error.
* Pro integration now runs through hooks (`jesaei_player_html`, `jesaei_enqueue_player_assets`, `jesaei_pro_api_version`) rather than direct calls. The old direct-call path is retained for existing Pro builds.
* Fixed episode links being built as broken URLs (with a doubled slash) when the Site URL setting was empty, and removed the PHP 8 warnings that went with it.
* Fixed a cascade of PHP warnings, printed into the page on some hosts, when a podcast feed was unreachable, returned a non-200, or contained invalid XML. The player now renders an empty list cleanly.
* Feed requests now use the WordPress HTTP API with a 10 second timeout, and work on hosts without the cURL extension.
* Fixed the episode cache not clearing when settings were saved.
* Fixed the player not appearing at all on block themes, which includes every default WordPress theme since Twenty Twenty-Two. The settings the player needs were never reaching the page, because block themes render page content before scripts are registered. If your player has been showing as an empty space or a stuck loading animation, this is why.
* The settings passed to JavaScript are attached to the plugin's own script instead of a separate empty file, removing one HTTP request per page and a build step that webpack never produced.
* Removed includes/class-anchor-episodes-index.php, an unused legacy copy of the main class that declared a second class with the same name in the same namespace.
* No changes to the shortcode, its attributes, saved settings, or CSS class names.

= 2.3.0 =
* Works with any podcast host. The plugin previously accepted feeds only from anchor.fm and Spotify; it now accepts any standard podcast RSS feed, including Buzzsprout, Transistor, Captivate, Libsyn, Podbean, RSS.com, Spreaker and Simplecast.
* Added a built-in player that streams straight from your feed. It loads faster than the Spotify embed and sets no third-party cookies.
* The Spotify Show URL setting is now optional, and only needed if you want Spotify's embedded player.
* New "Player" setting to choose between the built-in player and the Spotify embed.
* Existing sites are unchanged: if you are using the Spotify embed today, you keep it, and you can switch whenever you like from the settings page.
* Feed URLs containing a query string now work. They were previously HTML-escaped before being requested, which broke the request.
* The JavaScript bundle is 62% smaller (10.1KB to 3.9KB) and no longer uses eval(), so it works under a strict Content-Security-Policy.

= 2.4.0 =
* Reads the Podcasting 2.0 tags your podcast host already publishes: transcripts, chapter markers, per-episode credits, and season and episode numbers.
* Episode data is available to other plugins and themes through the new `jesaei_episode_data` filter.
* The cached feed is around 23% smaller despite the extra data, because the full episode description was being stored twice.
* The publish time of each episode is now kept, not just the date.
* Accessibility: episode dates and durations in the index were #c9cbcd on white, a contrast ratio of 1.63:1 at 11px, which is effectively unreadable. Dates, durations and descriptions now meet the WCAG AA threshold. This slightly changes how the index looks.
* The built-in player no longer leaves the loading animation sitting on top of it.
* Episode artwork that fails to load now shows a plain tile instead of the browser's broken-image box. Older Anchor-hosted shows in particular have artwork URLs that no longer resolve.
* The built-in player loads audio metadata, so the progress bar can be scrubbed and the arrow keys work before you press play.
* Dark mode now styles the episode index as well as the player. Sites whose dark mode setting was on but whose Pro licence had lapsed were showing a dark player above a white list.
* Episode durations are formatted correctly when your host publishes them as a plain number of seconds. They previously displayed as "2486" instead of "41:26"; Transistor, among others, uses seconds.
* Episodes without their own artwork now fall back to the show artwork, as podcast apps do. On a real feed only 35 of 163 episodes carried their own image and the rest were rendering an empty image tag. Anchor sets artwork on every episode, which is why this never showed up while only Anchor feeds were accepted.
