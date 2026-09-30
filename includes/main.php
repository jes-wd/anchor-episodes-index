<?php

namespace Anchor_Episodes_Index;

use Anchor_Episodes_Index\Pro\Main as Pro_Main;

if (!defined('ABSPATH')) {
    exit;
}

class Main {
    public $Functions;

    /**
     * Legacy Pro instance.
     *
     * Only populated when an OLD Pro build (one that predates the
     * jesaei_pro_api_version handshake) is active. With a current Pro build
     * this stays null and all communication goes through hooks instead.
     */
    public $Pro_Main = null;

    public function __construct() {
        $this->Functions = new Functions();

        // Pro is loaded BEFORE this plugin, so it cannot register its
        // integration until JESAEI_PRO_API exists. It does that on
        // plugins_loaded; we look for the result afterwards, at priority 20.
        add_action('plugins_loaded', [$this, 'maybe_init_legacy_pro'], 20);

        add_action('wp_enqueue_scripts', [$this, 'register_scripts']);
        // enqueue admin css
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_shortcode('anchor_episodes', [$this, 'shortcode']);
        add_action('init', [$this, 'register_shortcode']);
        add_filter('plugin_action_links_' . JESAEI_PLUGIN_BASENAME, [$this, 'add_plugin_settings_link']);
        add_filter('plugin_action_links_' . JESAEI_PLUGIN_BASENAME, [$this, 'add_plugin_pro_link']);
        add_action('updated_option', [$this, 'settings_updated_callback'], 10, 3);
        add_action('upgrader_post_install', [$this, 'plugin_updated'], 10, 1);
        add_action('admin_notices', [$this, 'admin_notice_pro_version']);
        add_action('admin_notices', [$this, 'admin_notice_outdated_pro']);
        add_action('wp_ajax_jesaei_dismiss_notice', [$this, 'dismiss_notice']);
    }

    /**
     * Which version of the integration contract the active Pro build speaks.
     *
     * 0 means either no Pro at all, or a Pro build old enough that it predates
     * the handshake and must be driven through the legacy direct-call path.
     */
    public function get_pro_api_version() {
        return (int) apply_filters('jesaei_pro_api_version', 0);
    }

    /**
     * Bring up an old Pro build the way it expects.
     *
     * Current Pro builds register their own hooks and are skipped here.
     * Every call is method_exists()-guarded so that a future Pro build which
     * drops one of these methods degrades instead of fataling.
     */
    public function maybe_init_legacy_pro() {
        if (!JESAEI_IS_PRO_ACTIVE) {
            return;
        }

        if ($this->get_pro_api_version() >= 1) {
            return; // Current Pro: it has already hooked itself up.
        }

        if (!class_exists('Anchor_Episodes_Index\Pro\Main')) {
            return;
        }

        $this->Pro_Main = new Pro_Main();

        if (method_exists($this->Pro_Main, 'init_hooks')) {
            $this->Pro_Main->init_hooks();
        }
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_style('jesaei-admin-styles', JESAEI_PLUGIN_URL . 'assets/admin.css', array(), $this->asset_version('assets/admin.css'), 'all');
    }

    /**
     * filemtime() on a file that is not there emits a warning and returns
     * false, which then produces an unversioned asset URL. Fall back to the
     * plugin version instead.
     */
    private function asset_version($relative_path) {
        $absolute = JESAEI_PLUGIN_PATH . $relative_path;

        if (file_exists($absolute)) {
            return filemtime($absolute);
        }

        return defined('JESAEI_VERSION') ? JESAEI_VERSION : false;
    }

    // register scripts but do not enqueue
    public function register_scripts() {
        wp_register_style('jesaei-styles', JESAEI_PLUGIN_URL . 'dist/main.css', array(), $this->asset_version('dist/main.css'), 'all');

        // Data-only handle. There is no dist/localized.js and the build never
        // produced one; registering with src=false is the supported way to
        // carry wp_localize_script() output, and stops the 404 + filemtime
        // warning this used to cause on every render.
        wp_register_script('jesaei-localized', false, array(), JESAEI_VERSION, true);

        // Depend on the data handle so jesaei_settings is always defined
        // before the bundle that reads it runs.
        wp_register_script('jesaei-scripts', JESAEI_PLUGIN_URL . 'dist/jesaei.bundle.js', array('jesaei-localized'), $this->asset_version('dist/jesaei.bundle.js'), true);
    }

    public function register_shortcode() {
        add_shortcode('anchor_episodes', [$this, 'shortcode']);
    }

    // Function to validate allowed domains and sanitize URL
    public function validate_and_sanitize_url($url) {
        $parsed_url = parse_url($url);

        if (!is_array($parsed_url) || empty($parsed_url['host'])) {
            return '';
        }

        $allowed_domains = ['podcasters.spotify.com', 'anchor.fm', 'creators.spotify.com'];

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

        $pro_api = $this->get_pro_api_version();

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

        if ($pro_api >= 1) {
            // Current Pro: it enqueues its own assets off this action.
            do_action('jesaei_enqueue_player_assets', $anchor_rss_url);
        } elseif ($this->Pro_Main && method_exists($this->Pro_Main, 'enqueue_scripts')) {
            $this->Pro_Main->enqueue_scripts();
        }

        $html = '<div id="jesaei-player-container" class="pro-active-' . (JESAEI_IS_PRO_ACTIVE ? 'yes' : 'no') . ' ' . ($dark_mode ? 'jesaeip-dark-theme' : '') . '">';
        $html .= '<div id="jesaei-player-loading-animation" class="lds-ellipsis"><div></div><div></div><div></div><div></div></div>';

        $html .= $this->get_player_html($site_url, $anchor_rss_url, $shortcode_attributes, $pro_api);

        $html .= $this->Functions->get_episode_list_html((int) $max_episodes, $anchor_rss_url);

        return $html;
    }

    /**
     * Resolve the player markup.
     *
     * Order of preference:
     *   1. Current Pro, via the jesaei_player_html filter.
     *   2. Legacy Pro, via a direct guarded call (old builds predate the filter).
     *   3. The built-in Spotify iframe.
     */
    private function get_player_html($site_url, $rss_url, $atts, $pro_api) {
        $default_html = $this->get_iframe_player_html($site_url);

        if ($pro_api >= 1) {
            return (string) apply_filters('jesaei_player_html', $default_html, $rss_url, $atts);
        }

        if ($this->Pro_Main && method_exists($this->Pro_Main, 'get_player_html')) {
            return (string) $this->Pro_Main->get_player_html($rss_url);
        }

        return $default_html;
    }

    private function get_iframe_player_html($site_url) {
        if ($site_url === '') {
            return '';
        }

        $html  = '<div id="jesaei-anchor-podcast-iframe-container">';
        $html .= '<iframe id="jesaei-anchor-podcast-iframe" src="' . $site_url . '/embed" style="width: 100%;" frameborder="0" scrolling="no" name="jesaei_podcast_iframe"></iframe>';
        $html .= '</div>';

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

    /**
     * Feed cache is keyed per RSS URL, so clearing the bare 'jesaei_episodes'
     * key never actually invalidated anything. Clear the keys for both the old
     * and the new URL so a settings change takes effect immediately.
     */
    public function settings_updated_callback($option_name, $old_value, $new_value) {
        if ($option_name !== 'jes_anchor_settings') {
            return;
        }

        delete_transient('jesaei_episodes'); // legacy key, harmless to keep clearing

        foreach (array($old_value, $new_value) as $value) {
            if (is_array($value) && !empty($value['anchor_rss_url'])) {
                delete_transient($this->Functions->get_transient_key((string) $value['anchor_rss_url']));
            }
        }
    }

    public function plugin_updated($plugin) {
        if ($plugin == 'anchor-episodes-index/anchor-episodes-index.php') {
            // delete transient to force new API call
            delete_transient('jesaei_episodes');

            $options = get_option('jes_anchor_settings');
            if (is_array($options) && !empty($options['anchor_rss_url'])) {
                delete_transient($this->Functions->get_transient_key((string) $options['anchor_rss_url']));
            }
        }
    }

    /**
     * Pro is installed but too old to speak the current contract. It still
     * works through the legacy path, but tell them to update so the next
     * release can drop that path.
     */
    public function admin_notice_outdated_pro() {
        if (!JESAEI_IS_PRO_ACTIVE || $this->get_pro_api_version() >= 1) {
            return;
        }

        if (!current_user_can('update_plugins')) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $allowed_screens = array('plugins', 'plugins-network', 'settings_page_jes_anchor_settings', 'dashboard');

        if ($screen && !in_array($screen->id, $allowed_screens, true)) {
            return;
        }

        echo '<div class="notice notice-warning"><p>'
            . '<strong>Anchor Episodes Index Pro is out of date.</strong> '
            . 'Your site is still running on a compatibility fallback. '
            . 'Update Pro from <a href="' . esc_url(admin_url('plugins.php')) . '">your plugins page</a> '
            . 'or download the latest build at <a href="https://jesweb.dev" target="_blank" rel="noopener">jesweb.dev</a>.'
            . '</p></div>';
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
