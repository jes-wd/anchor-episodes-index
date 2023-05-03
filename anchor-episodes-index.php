<?php

/**
 * Plugin Name: Anchor Episodes Index (Spotify for Podcasters)
 * Description: A lightweight plugin that allows you to output an anchor.fm (now called Spotify for Podcasters) podcast player on your site that includes an episode index. Just add two URL's on the settings page, grab the shortcode, and you're good to go!
 * Version: 2.0.30
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
define('JESAEI_PLUGIN_VERSION', '0.0.1');
define('JESAEI_IS_PRO_ACTIVE', class_exists('Anchor_Episodes_Index\Pro\Main'));

// updater 
define('JESAEIP_STORE_URL', 'https://jesweb.dev');
define('JESAEIP_ITEM_ID', 42); 
define('JESAEIP_ITEM_NAME', 'Anchor Episodes Index Pro');
define('JESAEIP_PLUGIN_LICENSE_PAGE', 'jes_anchor_settings');

include(JESAEI_PLUGIN_PATH . 'includes/rss-data-formatting.php');
include(JESAEI_PLUGIN_PATH . 'includes/functions.php');
include(JESAEI_PLUGIN_PATH . 'includes/main.php');
include(JESAEI_PLUGIN_PATH . 'includes/admin-settings-page.php');