<?php

namespace Anchor_Episodes_Index;

if (!defined('ABSPATH')) {
    exit;
}

class RSS_Data_Formatting {
    public $options;
    public $episode;

    public function __construct() {
        $this->options = get_option('jes_anchor_settings');
        $this->episode = null;
    }

    /**
     * Build the Spotify embed URL for a single episode.
     *
     * Returns an empty string when there is no usable site_url. The Spotify
     * embed is only meaningful for Spotify-hosted shows, and site_url is
     * optional, so every caller must tolerate an empty result.
     *
     * Previously this passed a possibly-null site_url straight into
     * parse_url(), which is a deprecation notice on PHP 8.1+ and a fatal in
     * the explode() immediately after it.
     */
    public function get_iframe_url($url): string {
        $site_url = isset($this->options['site_url']) ? (string) $this->options['site_url'] : '';

        // $url arrives as a SimpleXMLElement from the feed parser.
        $url = is_scalar($url) || is_object($url) ? (string) $url : '';

        if ($site_url === '' || $url === '') {
            return '';
        }

        $episode_id = $this->get_last_path_segment($url);
        $site_id    = $this->get_last_path_segment($site_url);

        if ($episode_id === '' || $site_id === '') {
            return '';
        }

        return 'https://creators.spotify.com/pod/show/' . $site_id . '/embed/episodes/' . $episode_id;
    }

    /**
     * Last non-empty path segment of a URL, or '' if there is not one.
     */
    private function get_last_path_segment(string $url): string {
        $path = parse_url($url, PHP_URL_PATH);

        if (!is_string($path) || $path === '') {
            return '';
        }

        $parts = array_values(array_filter(explode('/', $path), 'strlen'));

        return empty($parts) ? '' : (string) end($parts);
    }

    public function get_title_excerpt($title) {
        $title_max_length = 39;
        // $title_excerpt = substr($title, 0, $title_max_length) . '...';
        // only show the ellipsis if the title is longer than the max length
        $title_excerpt = strlen($title) > $title_max_length ? substr($title, 0, $title_max_length) . '...' : $title;

        return $title_excerpt;
    }

    public function get_description_excerpt($description) {
        $description_no_html = strip_tags($description);
        $description_sanitized = $this->sanitize_description($description);
        $description_max_length = 114;
        $description_excerpt = substr($description_no_html, 0, $description_max_length);
        $has_description_excerpt = strlen($description) > $description_max_length ? true : false;
        $description_excerpt_html = $has_description_excerpt ? '<span class="podcast-description-show-more-btn" data-full-description="' . $description_sanitized . '">...</span>' : '';

        return $description_excerpt . $description_excerpt_html;
    }

    /**
     * Raw publish time as a Unix timestamp, or 0.
     *
     * get_published_date() returns a display string with no time component.
     */
    /**
     * Format itunes:duration for display.
     *
     * The spec allows HH:MM:SS, MM:SS or a plain number of seconds, and hosts
     * use all three. Values that are already clock-formatted are passed
     * through untouched; a bare number is converted, which previously rendered
     * as "2486" instead of "41:26".
     */
    public function format_duration($duration): string {
        $duration = trim((string) $duration);

        if ($duration === '') {
            return '';
        }

        if (!ctype_digit($duration)) {
            return $duration;
        }

        $seconds = (int) $duration;

        if ($seconds <= 0) {
            return '';
        }

        $hours   = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs    = $seconds % 60;

        return $hours > 0
            ? sprintf('%d:%02d:%02d', $hours, $minutes, $secs)
            : sprintf('%02d:%02d', $minutes, $secs);
    }

    public function get_published_timestamp($date): int {
        $date = trim((string) $date);

        if ($date === '') {
            return 0;
        }

        $timestamp = strtotime($date);

        return $timestamp ? (int) $timestamp : 0;
    }

    public function get_published_date($date): string {
        $date = (string) $date;

        // new DateTime('') is "now"; an unparseable string throws. Neither is
        // useful output, so fall back to an empty string.
        if ($date === '') {
            return '';
        }

        try {
            $date = new \DateTime($date);
        } catch (\Exception $e) {
            return '';
        }

        // get the month name from the date
        $month_names = [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December',
        ];
        $month = $month_names[$date->format('n') - 1];
        $day = $date->format('d');
        $year = $date->format('Y');

        return $month . ' ' . $day . ', ' . $year;
    }

    public function sanitize_description($description) {
        // Remove all tags except for <a>, <p>, <br>, <strong>, <em>, <ul>, <ol>, <li>
        $allowed_tags = '<a><p><br><strong><em><ul><ol><li>';
        $sanitized_description = strip_tags($description, $allowed_tags);
    
        // Only allow the href attribute on <a> tags
        $sanitized_description = preg_replace_callback('/<a\s[^>]*>/', function ($matches) {
            if (preg_match('/\s?href\s*=\s*[\'"]([^\'"]+)[\'"]/i', $matches[0], $href)) {
                return sprintf('<a href="%s">', htmlspecialchars($href[1], ENT_QUOTES, 'UTF-8'));
            } else {
                return '<a>';
            }
        }, $sanitized_description);
    
        // Escape any special characters for use in HTML data attributes
        $sanitized_description = htmlentities($sanitized_description, ENT_QUOTES, 'UTF-8');
    
        return $sanitized_description;
    }    
}
