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
        add_action('admin_init', [$this, 'maybe_migrate']);
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

        // The settings are attached to the bundle itself rather than to a
        // separate data-only handle. WordPress prints a handle's localized
        // data immediately before its script tag, so jesaei_settings is always
        // defined before the code that reads it, with one less handle to keep
        // in sync. The old dist/localized.js was a zero-byte file that webpack
        // never produced.
        wp_register_script('jesaei-scripts', JESAEI_PLUGIN_URL . 'dist/jesaei.bundle.js', array(), $this->asset_version('dist/jesaei.bundle.js'), true);
    }

    public function register_shortcode() {
        add_shortcode('anchor_episodes', [$this, 'shortcode']);
    }

    /**
     * Validate a user-supplied URL and return it in a form safe to fetch.
     *
     * This used to accept only anchor.fm and Spotify hosts, which locked the
     * plugin to one podcast host. Any http(s) URL is now accepted; the feed
     * itself is fetched with wp_safe_remote_get(), which refuses private and
     * internal addresses.
     *
     * Returns a RAW url (esc_url_raw), not an HTML-escaped one. The previous
     * esc_url() turned "&" into "&#038;", which is right for markup and wrong
     * for an HTTP request. It never mattered while only Spotify feed URLs were
     * allowed, because those carry no query string. Plenty of other hosts do.
     * Callers must escape at the point of output.
     */
    public function validate_and_sanitize_url($url) {
        $url = trim((string) $url);

        if ($url === '') {
            return '';
        }

        $parsed_url = wp_parse_url($url);

        if (!is_array($parsed_url) || empty($parsed_url['host'])) {
            return '';
        }

        $scheme = isset($parsed_url['scheme']) ? strtolower($parsed_url['scheme']) : '';

        if ($scheme !== 'http' && $scheme !== 'https') {
            return '';
        }

        // Credentials in the URL are never wanted here and would be leaked
        // into markup and into the JS settings object.
        if (isset($parsed_url['user']) || isset($parsed_url['pass'])) {
            return '';
        }

        $clean = esc_url_raw($url, array('http', 'https'));

        return is_string($clean) ? $clean : '';
    }

    /**
     * Is this URL on one of Spotify's podcast hosts?
     *
     * Only used to decide whether the legacy Spotify iframe is available.
     */
    public function is_spotify_url($url) {
        $host = wp_parse_url((string) $url, PHP_URL_HOST);

        if (!is_string($host) || $host === '') {
            return false;
        }

        $host = strtolower($host);
        $spotify_hosts = array('anchor.fm', 'podcasters.spotify.com', 'creators.spotify.com', 'open.spotify.com');

        foreach ($spotify_hosts as $spotify_host) {
            if ($host === $spotify_host || substr($host, -(strlen($spotify_host) + 1)) === '.' . $spotify_host) {
                return true;
            }
        }

        return false;
    }

    /**
     * Which player to render: 'pro', 'native' or 'iframe'.
     *
     * Existing installs are pinned to 'iframe' by maybe_migrate() so their
     * pages do not change appearance on update. Fresh installs, and anyone
     * whose feed is not on Spotify, get the built-in player.
     */
    public function get_player_mode($site_url, $rss_url) {
        if ($this->get_pro_api_version() >= 1 || $this->Pro_Main) {
            return 'pro';
        }

        $iframe_available = ($site_url !== '' && $this->is_spotify_url($site_url));

        $options = get_option('jes_anchor_settings');
        $stored  = is_array($options) && isset($options['player_mode']) ? $options['player_mode'] : '';

        if ($stored === 'native') {
            return 'native';
        }

        if ($stored === 'iframe') {
            return $iframe_available ? 'iframe' : 'native';
        }

        // Nothing stored. If maybe_migrate() has already run then this is a
        // fresh install, and fresh installs get the built-in player: it loads
        // faster and sets no third-party cookies. If migration has NOT run yet
        // (a front-end hit before any admin page view) fall back to the old
        // behaviour so the page does not change under an existing user.
        if (get_option('jesaei_version') !== false) {
            return 'native';
        }

        return $iframe_available ? 'iframe' : 'native';
    }

    /**
     * One-time migration for installs that predate the built-in player.
     *
     * A site already showing the Spotify iframe keeps showing it. Only sites
     * that never had settings, or that point at a non-Spotify feed, get the
     * new default. Runs in the admin only, so no front-end writes.
     */
    public function maybe_migrate() {
        if (get_option('jesaei_version') === JESAEI_VERSION) {
            return;
        }

        if (get_option('jesaei_version') === false) {
            $options = get_option('jes_anchor_settings');

            if (is_array($options) && !isset($options['player_mode'])) {
                // Only a Spotify show URL could ever have produced an iframe;
                // anything else was already rendering nothing useful.
                $options['player_mode'] =
                    (!empty($options['site_url']) && $this->is_spotify_url($options['site_url']))
                        ? 'iframe'
                        : 'native';
                update_option('jes_anchor_settings', $options);
            }
        }

        update_option('jesaei_version', JESAEI_VERSION);
    }

    // shortcode main function
    public function shortcode($atts) {
        // Block themes render template content BEFORE wp_enqueue_scripts
        // fires, so by the time this runs the handles may not exist yet and
        // wp_localize_script() would silently return false, leaving
        // jesaei_settings undefined. wp_register_script() is a no-op for an
        // already-registered handle, so calling it here is safe either way.
        $this->register_scripts();

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

        $pro_api     = $this->get_pro_api_version();
        $player_mode = $this->get_player_mode($site_url, $anchor_rss_url);

        // wp_localize_script JSON-encodes these, so the raw (unescaped) URLs
        // are correct here; escaping happens at each markup output point.
        wp_localize_script('jesaei-scripts', 'jesaei_settings', [
            'site_url' => $site_url,
            'rss_url' => $anchor_rss_url,
            'max_episodes' => (int) $max_episodes,  // Int casting already sanitizes this
            'is_pro_version_active' => JESAEI_IS_PRO_ACTIVE,
            'player_mode' => $player_mode
        ]);
        // enqueue scripts and styles here - this prevents them from enqeueing on irrelevant pages
        wp_enqueue_style('jesaei-styles');
        wp_enqueue_script('jesaei-scripts');

        if ($pro_api >= 1) {
            // Current Pro: it enqueues its own assets off this action.
            do_action('jesaei_enqueue_player_assets', $anchor_rss_url);
        } elseif ($this->Pro_Main && method_exists($this->Pro_Main, 'enqueue_scripts')) {
            $this->Pro_Main->enqueue_scripts();
        }

        $html = '<div id="jesaei-player-container" class="pro-active-' . (JESAEI_IS_PRO_ACTIVE ? 'yes' : 'no') . ' jesaei-mode-' . esc_attr($player_mode) . ' ' . ($dark_mode ? 'jesaeip-dark-theme' : '') . '">';

        // The loading animation exists to cover the Spotify iframe while it
        // loads. The built-in player is rendered server-side with nothing to
        // wait for, and the animation is absolutely positioned, so it would
        // sit on top of the player forever. Kept for the other modes so their
        // markup is unchanged.
        if ($player_mode !== 'native') {
            $html .= '<div id="jesaei-player-loading-animation" class="lds-ellipsis"><div></div><div></div><div></div><div></div></div>';
        }

        $html .= $this->get_player_html($site_url, $anchor_rss_url, $shortcode_attributes, $pro_api, $player_mode);

        $html .= $this->Functions->get_episode_list_html((int) $max_episodes, $anchor_rss_url, $player_mode);

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
    private function get_player_html($site_url, $rss_url, $atts, $pro_api, $player_mode = 'iframe') {
        $default_html = $player_mode === 'native'
            ? $this->get_native_player_html($rss_url)
            : $this->get_iframe_player_html($site_url);

        if ($pro_api >= 1) {
            return (string) apply_filters('jesaei_player_html', $default_html, $rss_url, $atts);
        }

        if ($this->Pro_Main && method_exists($this->Pro_Main, 'get_player_html')) {
            return (string) $this->Pro_Main->get_player_html($rss_url);
        }

        return $default_html;
    }

    /**
     * The built-in player: a plain HTML5 audio element driven from the feed's
     * own enclosure URLs, so it works with any podcast host.
     *
     * Seeded with the most recent episode; the episode list swaps the source.
     */
    private function get_native_player_html($rss_url) {
        $this->Functions->set_rss_url($rss_url);
        $episodes = $this->Functions->get_rss_feed();

        if (empty($episodes)) {
            return '';
        }

        $episode   = $episodes[0];
        $image_url = apply_filters('jesaei_modify_episode_image_url', $episode['image_url']);

        $html  = '<div class="jesaei-np" id="jesaei-np">';
        // preload="metadata" fetches only the file header, which is what makes
        // audio.duration available before playback. With preload="none" the
        // duration is unknown until the user presses play, so the progress bar
        // cannot be scrubbed and the arrow keys do nothing until then.
        $html .= '<audio id="jesaei-np-audio" preload="metadata" src="' . esc_url($episode['audio_url']) . '"></audio>';

        $html .= '<div class="jesaei-np__art">';
        $html .= '<img id="jesaei-np-art" src="' . esc_url($image_url) . '" alt="" />';
        $html .= '</div>';

        $html .= '<div class="jesaei-np__body">';
        $html .= '<div class="jesaei-np__title" id="jesaei-np-title">' . esc_html($episode['title']) . '</div>';
        $html .= '<div class="jesaei-np__meta">';
        $html .= '<span id="jesaei-np-author">' . esc_html($episode['author']) . '</span>';
        $html .= '<span class="jesaei-np__sep"> &middot; </span>';
        $html .= '<span id="jesaei-np-date">' . esc_html($episode['published_date']) . '</span>';
        $html .= '</div>';

        $html .= '<div class="jesaei-np__controls">';
        $html .= '<button type="button" class="jesaei-np__play" id="jesaei-np-play" aria-label="' . esc_attr__('Play', 'anchor-episodes-index') . '">';
        $html .= '<svg class="jesaei-np__icon-play" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M7.05 3.606l13.49 7.788a.7.7 0 010 1.212L7.05 20.394A.7.7 0 016 19.788V4.212a.7.7 0 011.05-.606z"/></svg>';
        $html .= '<svg class="jesaei-np__icon-pause" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M5.7 3a.7.7 0 00-.7.7v16.6a.7.7 0 00.7.7h2.6a.7.7 0 00.7-.7V3.7a.7.7 0 00-.7-.7H5.7zm10 0a.7.7 0 00-.7.7v16.6a.7.7 0 00.7.7h2.6a.7.7 0 00.7-.7V3.7a.7.7 0 00-.7-.7h-2.6z"/></svg>';
        $html .= '</button>';
        $html .= '<span class="jesaei-np__time" id="jesaei-np-elapsed">00:00</span>';
        $html .= '<div class="jesaei-np__progress" id="jesaei-np-progress" role="slider" tabindex="0"'
              . ' aria-label="' . esc_attr__('Seek', 'anchor-episodes-index') . '"'
              . ' aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">';
        $html .= '<div class="jesaei-np__bar" id="jesaei-np-bar"></div>';
        $html .= '</div>';
        $html .= '<span class="jesaei-np__time" id="jesaei-np-duration">' . esc_html($episode['duration']) . '</span>';
        $html .= '</div>';

        $html .= '</div></div>';

        return $html;
    }

    private function get_iframe_player_html($site_url) {
        if ($site_url === '') {
            return '';
        }

        $html  = '<div id="jesaei-anchor-podcast-iframe-container">';
        $html .= '<iframe id="jesaei-anchor-podcast-iframe" src="' . esc_url($site_url . '/embed') . '" style="width: 100%;" frameborder="0" scrolling="no" name="jesaei_podcast_iframe"></iframe>';
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

/**
 * Convenience wrapper for code outside the class (the settings page).
 */
function jesaei_is_spotify_url($url) {
    global $Anchor_Episodes_Index_Main;

    return $Anchor_Episodes_Index_Main instanceof Main
        ? $Anchor_Episodes_Index_Main->is_spotify_url($url)
        : false;
}
