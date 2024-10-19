<?php

/**
 * Plugin Name: Anchor Episodes Index (Spotify for Podcasters)
 * Description: A lightweight plugin that allows you to output an anchor.fm (now called Spotify for Podcasters) podcast player on your site that includes an episode index. Just add two URL's on the settings page, grab the shortcode, and you're good to go!
 * Version: 2.1.11
 * Author: jesweb.dev
 * Author URI: https://jesweb.dev
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 **/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('JESAEI_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('JESAEI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('JESAEI_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('JESAEI_IS_PRO_ACTIVE', class_exists('Anchor_Episodes_Index\Pro\Main'));

include(JESAEI_PLUGIN_PATH . 'includes/rss-data-formatting.php');
include(JESAEI_PLUGIN_PATH . 'includes/functions.php');
include(JESAEI_PLUGIN_PATH . 'includes/main.php');
include(JESAEI_PLUGIN_PATH . 'includes/admin-settings-page.php');

// Schedule an hourly event
if (!wp_next_scheduled('jesaei_hourly_event')) {
    wp_schedule_event(time(), 'hourly', 'jesaei_hourly_event');
}

// This function will run once per hour
function jesaei_delete_transient_once() {
    // Check if the transient has already been deleted
    if (!get_option('jesaei_jun23_sale_transient_deleted')) {
        // Transient hasn't been deleted yet, delete it now
        delete_transient('jesaei_notice_dismissed');

        // Set a flag in the options table to indicate that the transient has been deleted
        update_option('jesaei_jun23_sale_transient_deleted', true);
    }
}
add_action('jesaei_hourly_event', 'jesaei_delete_transient_once');

