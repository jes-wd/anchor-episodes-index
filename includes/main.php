<?php

namespace Anchor_Episodes_Index;

use Anchor_Episodes_Index\Pro\Main as Pro_Main;

class Main {
    public $Functions;

    public function __construct() {
        $this->Functions = new Functions();

        add_action('wp_enqueue_scripts', [$this, 'register_scripts']);
        add_shortcode('anchor_episodes', [$this, 'init']);
        add_action('init', [$this, 'register_shortcode']);
        add_filter('plugin_action_links_' . JESAEI_PLUGIN_BASENAME, [$this, 'add_plugin_settings_link']);
    }

    // register scripts but do not enqueue
    public function register_scripts() {
        wp_register_style('jesaei-styles', JESAEI_PLUGIN_URL . 'dist/main.css', array(), JESAEI_PLUGIN_VERSION, 'all');
        wp_register_script('jesaei-scripts', JESAEI_PLUGIN_URL . 'dist/jesaei.bundle.js', array(), JESAEI_PLUGIN_VERSION, true);
        wp_register_script('jesaei-localized', JESAEI_PLUGIN_URL . 'dist/localized.js');
    }

    public function register_shortcode() {
        add_shortcode('anchor_episodes', [$this, 'shortcode']);
    }

    // shortcode main function
    public function shortcode($atts) {
        $shortcode_attributes = shortcode_atts(array(
            'site_url' => '',
            'rss_url' => '',
            'max_episodes' => ''
        ), $atts);

        // Retrieve settings page data
        $options = get_option('jes_anchor_settings');
        $site_url_from_options = isset($options['site_url']) ? $options['site_url'] : '';
        $site_url = strlen($shortcode_attributes['site_url']) > 0 ? $shortcode_attributes['site_url'] : $site_url_from_options;
        $anchor_rss_url_from_options = isset($options['anchor_rss_url']) ? $options['anchor_rss_url'] : '';
        $anchor_rss_url = strlen($shortcode_attributes['rss_url']) > 0 ? $shortcode_attributes['rss_url'] : $anchor_rss_url_from_options;
        $max_episodes_from_options = !empty($options['max_episodes']) ? (int) $options['max_episodes'] : 999;
        $max_episodes = strlen($shortcode_attributes['max_episodes']) > 0 ? (int) $shortcode_attributes['max_episodes'] : $max_episodes_from_options;
        $is_pro_version_active = class_exists('Anchor_Episodes_Index\Pro\Main');

        // output PHP vars as JS vars
        wp_localize_script('jesaei-localized', 'jesaei_settings', [
            'site_url' => $site_url,
            'rss_url' => $anchor_rss_url,
            'max_episodes' => $max_episodes,
            'is_pro_version_active' => $is_pro_version_active
        ]);
        wp_enqueue_script('jesaei-localized');
        // enqueue scripts - this prevents them from enqeueing on irrelevant pages
        wp_enqueue_style('jesaei-styles');
        wp_enqueue_script('jesaei-scripts');

        if ($is_pro_version_active) {
            Pro_Main::enqueue_scripts();
        }

        $html = '<div id="jesaei-player-container" class="' . ($is_pro_version_active ? 'yes' : 'no') . '">';
        $html .= '<div id="jesaei-player-loading-animation" class="lds-ellipsis"><div></div><div></div><div></div><div></div></div>';

        if ($is_pro_version_active) {
            $html .= Pro_Main::get_player_html();
        } else {
            $html .= '<iframe id="jesaei-anchor-podcast-iframe" src="' . $site_url . '/embed" style="width: 100%;" frameborder="0" scrolling="no" name="jesaei_podcast_iframe"></iframe>';
        }

        $html .= $this->Functions->get_episode_list_html();

        return $html;
    }

    // add settings link on plugin index page
    public function add_plugin_settings_link($links) {
        $settings_link = '<a href="admin.php?page=jes-anchor-settings">Settings</a>';

        array_unshift($links, $settings_link);

        return $links;
    }
}

$Anchor_Episodes_Index_Main = new Main();
