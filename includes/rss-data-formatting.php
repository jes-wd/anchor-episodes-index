<?php

namespace Anchor_Episodes_Index;

class RSS_Data_Formatting {
    public $options;
    public $episode;

    public function __construct() {
        $this->options = get_option('jes_anchor_settings');
        $this->episode = null;
    }

    public function get_iframe_url($url): string {
        // get the last path from the url
        $url_path = parse_url($url, PHP_URL_PATH);
        $url_path_parts = explode('/', $url_path);
        $episode_id = end($url_path_parts);
        // get the last path from the site_url
        $site_url_path = parse_url($this->options['site_url'], PHP_URL_PATH);
        $site_url_path_parts = explode('/', $site_url_path);
        $site_id = end($site_url_path_parts);
        // create the embed url
        $iframe_url = 'https://podcasters.spotify.com/pod/show/' . $site_id . '/embed/episodes/' . $episode_id;

        return $iframe_url;
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
        $description_max_length = 114;
        $description_excerpt = substr($description_no_html, 0, $description_max_length);
        $has_description_excerpt = strlen($description) > $description_max_length ? true : false;
        $description_excerpt_html = $has_description_excerpt ? '<span class="podcast-description-show-more-btn" data-full-description="' . $description . '">...</span>' : '';

        return $description_excerpt . $description_excerpt_html;
    }

    public function get_published_date($date): string {
        $date = new \DateTime($date);
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
}
