<?php

/**
 * Plugin Name: Anchor Episodes Index
 * Description: A lightweight plugin that allows you to output an anchor.fm podcast player on your site that includes an episode index. Just add two URL's on the settings page, grab the shortcode, and you're good to go!
 * Version: 1.2.0
 * Author: JES Web Development
 * Author URI: https://jeswebdevelopment.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 **/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('JESAEI_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('JESAEI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('JESAEI_PLUGIN_BASENAME', plugin_basename(__FILE__));

include(JESAEI_PLUGIN_PATH . 'includes/class-anchor-episodes-index.php');
include(JESAEI_PLUGIN_PATH . 'includes/admin-settings-page.php');