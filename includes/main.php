<?php

namespace Anchor_Episodes_Index;

use Anchor_Episodes_Index\Pro\Main as Pro_Main;

class Main {
    public $Functions;
    public $Pro_Main;

    public function __construct() {
        $this->Functions = new Functions();

        if (JESAEI_IS_PRO_ACTIVE) {
            $this->Pro_Main = new Pro_Main();
            $this->Pro_Main->init_hooks();
        }

        add_action('wp_enqueue_scripts', [$this, 'register_scripts']);
        // enqueue admin css
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_shortcode('anchor_episodes', [$this, 'init']);
        add_action('init', [$this, 'register_shortcode']);
        add_filter('plugin_action_links_' . JESAEI_PLUGIN_BASENAME, [$this, 'add_plugin_settings_link']);
        add_filter('plugin_action_links_' . JESAEI_PLUGIN_BASENAME, [$this, 'add_plugin_pro_link']);
        add_action('updated_option', [$this, 'settings_updated_callback'], 10, 3);
        add_action('upgrader_post_install', [$this, 'plugin_updated'], 10, 1);
        add_action('admin_notices', [$this, 'admin_notice_pro_version']);
        add_action('wp_ajax_jesaei_dismiss_notice', [$this, 'dismiss_notice']);
    }
    public function enqueue_admin_scripts() {
        wp_enqueue_style('jesaei-admin-styles', JESAEI_PLUGIN_URL . 'assets/admin.css', array(), filemtime(JESAEI_PLUGIN_PATH . 'assets/admin.css'), 'all');
    }

    // register scripts but do not enqueue
    public function register_scripts() {
        wp_register_style('jesaei-styles', JESAEI_PLUGIN_URL . 'dist/main.css', array(), filemtime(JESAEI_PLUGIN_PATH . 'dist/main.css'), 'all');
        wp_register_script('jesaei-scripts', JESAEI_PLUGIN_URL . 'dist/jesaei.bundle.js', array(), filemtime(JESAEI_PLUGIN_PATH . 'dist/jesaei.bundle.js'), true);
        wp_register_script('jesaei-localized', JESAEI_PLUGIN_URL . 'dist/localized.js', array(), filemtime(JESAEI_PLUGIN_PATH . 'dist/localized.js'), true);
    }

    public function register_shortcode() {
        add_shortcode('anchor_episodes', [$this, 'shortcode']);
    }

    // Function to validate allowed domains and sanitize URL
    public function validate_and_sanitize_url($url) {
        $parsed_url = parse_url($url);
        $allowed_domains = ['podcasters.spotify.com', 'anchor.fm'];
        if (in_array($parsed_url['host'], $allowed_domains)) {
            return esc_url(sanitize_url($url));
        }
        return '';
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

        // Use the new function to validate, sanitize, and escape URL-related data
        $site_url_from_options = isset($options['site_url']) ? $this->validate_and_sanitize_url($options['site_url']) : '';
        $site_url = strlen($shortcode_attributes['site_url']) > 0 ? $this->validate_and_sanitize_url($shortcode_attributes['site_url']) : $site_url_from_options;

        $anchor_rss_url_from_options = isset($options['anchor_rss_url']) ? $this->validate_and_sanitize_url($options['anchor_rss_url']) : '';
        $anchor_rss_url = strlen($shortcode_attributes['rss_url']) > 0 ? $this->validate_and_sanitize_url($shortcode_attributes['rss_url']) : $anchor_rss_url_from_options;

        $max_episodes_from_options = !empty($options['max_episodes']) ? (int) $options['max_episodes'] : 999;
        $max_episodes = strlen($shortcode_attributes['max_episodes']) > 0 ? (int) $shortcode_attributes['max_episodes'] : $max_episodes_from_options;

        $dark_mode = isset($options['dark_mode']) ? $options['dark_mode'] : false;

        // Make sure to escape data before outputting it to JS
        wp_localize_script('jesaei-localized', 'jesaei_settings', [
            'site_url' => $site_url,  // Already escaped using esc_url
            'rss_url' => $anchor_rss_url,  // Already escaped using esc_url
            'max_episodes' => (int) $max_episodes,  // Int casting already sanitizes this
            'is_pro_version_active' => JESAEI_IS_PRO_ACTIVE
        ]);
        wp_enqueue_script('jesaei-localized');
        // enqueue scripts and styles here - this prevents them from enqeueing on irrelevant pages
        wp_enqueue_style('jesaei-styles');
        wp_enqueue_script('jesaei-scripts');

        if (JESAEI_IS_PRO_ACTIVE) {
            $this->Pro_Main->enqueue_scripts();
        }

        $html = '<div id="jesaei-player-container" class="pro-active-' . (JESAEI_IS_PRO_ACTIVE ? 'yes' : 'no') . ' ' . ($dark_mode ? 'jesaeip-dark-theme' : '') . '">';
        $html .= '<div id="jesaei-player-loading-animation" class="lds-ellipsis"><div></div><div></div><div></div><div></div></div>';

        if (JESAEI_IS_PRO_ACTIVE) {
            $html .= $this->Pro_Main->get_player_html($anchor_rss_url);
        } else {
            $html .= '<iframe id="jesaei-anchor-podcast-iframe" src="' . $site_url . '/embed" style="width: 100%;" frameborder="0" scrolling="no" name="jesaei_podcast_iframe"></iframe>';
        }

        $html .= $this->Functions->get_episode_list_html((int) $max_episodes, $anchor_rss_url);

        return $html;
    }

    public function add_plugin_pro_link($links) {
        if (JESAEI_IS_PRO_ACTIVE) {
            return $links;
        }

        $settings_link = '<a class="jesaei-plugin-pro-link" href="https://jesweb.dev" target="_blank">Get Pro version</a>';

        array_unshift($links, $settings_link);

        return $links;
    }

    // add settings link on plugin index page
    public function add_plugin_settings_link($links) {
        $settings_link = '<a href="options-general.php?page=jes_anchor_settings">Settings</a>';

        array_unshift($links, $settings_link);

        return $links;
    }

    public function settings_updated_callback($option_name, $old_value, $new_value) {
        if ($option_name === 'jes_anchor_settings') {
            delete_transient('jesaei_episodes');
        }
    }

    public function plugin_updated($plugin) {
        if ($plugin == 'anchor-episodes-index/anchor-episodes-index.php') {
            // delete transient to force new API call
            delete_transient('jesaei_episodes');
        }
    }

    public function admin_notice_pro_version() {
        // Check if the notice has been dismissed
        if (get_transient('jesaei_notice_dismissed')) {
            return;
        }

        if (!JESAEI_IS_PRO_ACTIVE) {
            echo '<div class="notice notice-info is-dismissible" id="jesaei-pro-version-notice">
                <p><strong>Get 50% off Anchor Episodes Index Pro. For a limited time only. Get a superior episode player along with added features. </strong></p>
                <p><a href="https://jesweb.dev" target="_blank">Get the Pro version</a></p>
            </div>';
            $this->notice_dismissible_script();
        }
    }

    public function notice_dismissible_script() {
        echo "<script>
            jQuery(document).on('click', '#jesaei-pro-version-notice .notice-dismiss', function() {
                jQuery.ajax({
                    url: ajaxurl,
                    data: {
                        action: 'jesaei_dismiss_notice'
                    }
                });
            });
        </script>";
    }

    public function dismiss_notice() {
        // Set the notice to be dismissed for 6 months
        set_transient('jesaei_notice_dismissed', true, 3 * MONTH_IN_SECONDS);

        wp_die();
    }
}

$Anchor_Episodes_Index_Main = new Main();
