<?php

/**
 * Plugin Name: Anchor Episodes Index
 * Description: A lightweight plugin that allows you to output an anchor.fm podcast player on your site that includes an episode index. Just add two URL's on the settings page, grab the shortcode, and you're good to go!
 * Version: 1.1.1
 * Author: JES Web Development
 * Author URI: https://jeswebdevelopment.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 **/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// define the absolute plugin path for includes
define('JES_ANCHOR_EPISODES_PLUGIN_PATH', plugin_dir_path(__FILE__));

// register scripts but do not enqueue
function jes_anchor_episodes_register_scripts() {
    wp_register_style('jes-anchor-episodes-styles', plugins_url('anchor-episodes-index/css/jes-anchor-episodes-styles.css'), array(), '1.0.0', 'all');
    wp_register_script('jes-anchor-episodes-feednami', plugins_url('anchor-episodes-index/js/feednami-client-v1.1.js'));
    wp_register_script('jes-anchor-episodes-scripts', plugins_url('anchor-episodes-index/js/jes-anchor-episodes-scripts.js'));
}

add_action('wp_enqueue_scripts', 'jes_anchor_episodes_register_scripts');

// shortcode main function
function jes_anchor_episodes_init($atts) {
    $shortcode_attributes = shortcode_atts( array(
		'site_url' => '',
		'rss_url' => '',
	), $atts );


    // enqueue scripts - this prevents them from enqeueing on irrelevant pages
    wp_enqueue_style('jes-anchor-episodes-styles');
    wp_enqueue_script('jes-anchor-episodes-feednami');
    wp_enqueue_script('jes-anchor-episodes-scripts');

    // Retrieve settings page data from the database
    $options = get_option('jes_anchor_settings');
    $site_url_from_options = isset($options['site_url']) ? $options['site_url'] : '';
    $anchor_rss_url_from_options = isset($options['anchor_rss_url']) ? $options['anchor_rss_url'] : '';
    $site_url = strlen($shortcode_attributes['site_url']) > 0 ? $shortcode_attributes['site_url'] : $site_url_from_options;
    $anchor_rss_url = strlen($shortcode_attributes['rss_url']) > 0 ? $shortcode_attributes['rss_url'] : $anchor_rss_url_from_options;

    // return html
    return '
        <div id="podcasts-player-container">
            <div id="podcast-player-loading-animation" class="lds-ellipsis"><div></div><div></div><div></div><div></div></div>
            <iframe id="anchor-podcast-iframe" src="' . $site_url . '/embed" style="width: 100%;" frameborder="0" scrolling="no" name="iframe"></iframe>
        </div>
        <script>
            window.jesAnchorEpisodesSiteUrl = "' . $site_url . '";
            window.jesAnchorEpisodesRssUrl =  "' . $anchor_rss_url . '";
        </script>
    ';
}

// check for init function above
if (function_exists('jes_anchor_episodes_init')) {

    // register shortcode function
    function jes_anchor_episodes_register_shortcode() {
        add_shortcode('anchor_episodes', 'jes_anchor_episodes_init');
    }

    // init shortcode action
    add_action('init', 'jes_anchor_episodes_register_shortcode');
}

// include settings page
include(JES_ANCHOR_EPISODES_PLUGIN_PATH . 'admin/settings-page.php');

// add settings link on plugin index page
function jes_anchor_episodes_plugin_settings_link($links) {

    $settings_link = '<a href="admin.php?page=jes-anchor-settings">Settings</a>';

    array_unshift($links, $settings_link);

    return $links;
}

add_filter("plugin_action_links_" . plugin_basename(__FILE__), 'jes_anchor_episodes_plugin_settings_link');
