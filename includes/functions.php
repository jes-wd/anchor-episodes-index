<?php

namespace Anchor_Episodes_Index;

class Functions {
  public $options;
  public $RSS_Data_Formatting;

  public function __construct() {
    $this->options = get_option('jes_anchor_settings');
    $this->RSS_Data_Formatting = new RSS_Data_Formatting();
  }

  public function get_rss_feed() {
    $rss_url = isset($this->options['anchor_rss_url']) ? $this->options['anchor_rss_url'] : '';

    // delete_transient('jesaei_episodes');

    // if not stored in transient, fetch rss feed
    if (false === ($feed_array = get_transient('jesaei_episodes'))) {

      $feed_content = file_get_contents($rss_url);
      $feed_xml = simplexml_load_string($feed_content);
      $feed_array = array();

      foreach ($feed_xml->channel->item as $item) {
        $itunes_data = $item->children('itunes', true);
        $feed_array[] = array(
          "title" => (string) $item->title,
          "title_excerpt" => (string) $this->RSS_Data_Formatting->get_title_excerpt($item->title),
          "iframe_url" => (string) $this->RSS_Data_Formatting->get_iframe_url($item->link),
          "audio_url" => (string) $item->enclosure->attributes()->url,
          "description" => (string) $item->description,
          "description_excerpt" => (string) $this->RSS_Data_Formatting->get_description_excerpt($item->description),
          "published_date" => (string) $this->RSS_Data_Formatting->get_published_date($item->pubDate),
          "guid" => (string) $item->guid,
          "image_url" => (string) $itunes_data->image->attributes()->href,
          "author" => (string) $itunes_data->author,
          "subtitle" => (string) $itunes_data->subtitle,
          "summary" => (string) $itunes_data->summary,
          "duration" => (string) $itunes_data->duration,
          "keywords" => (string) $itunes_data->keywords,
          "site_url" => (string) $item->link,
          "author" => (string) $feed_xml->channel->image->title
        );
      }

      // echo pre
      // echo '<pre>';
      // print_r($feed_xml);
      // echo '</pre>';

      // save feed array in transient with 15 minute expiration
      set_transient('jesaei_episodes', $feed_array, 15 * MINUTE_IN_SECONDS);
    }

    return $feed_array;
  }

  public function get_episode_list_html() {
    $episodes = $this->get_rss_feed();
    $html = '<div id="jesaei-podcast-list-container" class="jesaei-podcast-list-container styles__episodeFeed___3mOKz">';
    $index = 0;

    foreach ($episodes as $episode) {
      $link_attributes = '';

      if (JESAEI_IS_PRO_ACTIVE) {
        $link_attributes .= 'data-audio-url="' . $episode['audio_url'] . '"';
        $link_attributes .= 'data-episode-title="' . $episode['title'] . '"';
        $link_attributes .= 'data-episode-image="' . $episode['image_url'] . '"';
        $link_attributes .= 'data-episode-published-date="' . $episode['published_date'] . '"';
        $link_attributes .= 'data-episode-duration="' . $episode['duration'] . '"';
        $link_attributes .= 'data-episode-author="' . $episode['author'] . '"';
        $link_attributes .= 'data-episode-site-url="' . $episode['site_url'] . '"';
      } else {
        $link_attributes .= 'href="' . $episode['iframe_url'] . '" target="jesaei_podcast_iframe"';
      }

      $html .= '
        <div class="styles__episodeFeedItem___1U6E2 ' . ($index === 0 ? 'jesaeip-selected-episode' : '') . '">
          <span class="styles__isActiveEpisode___cXlB4"></span>
          <a class="jesaeip-episode-play-btn podcast-list-link styles__episodeImage___tMifW" ' . $link_attributes . '>
            <img src="' . $episode['image_url'] . '">
            <button class="styles__circle___1g-9u styles__white___372tQ styles__playButton___1Ivi4 styles__playButton___1uaGA" aria-label="" style="height: 31px; min-height: 31px; width: 31px; min-width: 31px; border-radius: 16px;">
              <svg class="jesaeip-episode-play-icon" xmlns="http://www.w3.org/2000/svg" viewBox="-1 0 11 12" width="13" height="13">
                <rect width="12" height="12" fill="none"></rect>
                <path d="M1 .81v10.38a.76.76 0 0 0 .75.75.67.67 0 0 0 .39-.12l8.42-5.18a.75.75 0 0 0 0-1.28L2.14.18a.75.75 0 0 0-1 .24.79.79 0 0 0-.14.39z" fill="#282F36"></path>
              </svg>
              <svg class="jesaeip-episode-pause-icon" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg" width="13" height="13" aria-label="pause icon"><rect x=".03" width="12" height="12" fill="none"></rect><path d="M8.45 0h1.83a.75.75 0 0 1 .72.75v10.5a.75.75 0 0 1-.75.75h-1.8a.75.75 0 0 1-.75-.75V.75A.75.75 0 0 1 8.45 0zM1.78 0h1.83a.75.75 0 0 1 .75.75v10.5a.75.75 0 0 1-.75.75H1.78a.76.76 0 0 1-.78-.75V.75A.76.76 0 0 1 1.78 0z" fill="#282F36"></path></svg>
            </button>
          </a>
          <a class="podcast-list-link" href="' . $episode['iframe_url'] . '" target="jesaei_podcast_iframe" data-audio-url="' . $episode['audio_url'] . '">
            <div class="styles__episodeHeading___29q7v">
              <div>
                <div>' . $episode['title_excerpt'] . '</div>
              </div>
            </div>
          </a>
          <div class="styles__episodeDescription___C3oZg ">
            <div class="styles__expander___1NNVb styles__expander--dark___3Qxhe">
              <div>
                <div class="podcast-description">' . $episode['description_excerpt'] . '</div>
              </div>
            </div>
          </div>
          <div class="styles__episodeDuration___2I0Qb">' . $episode['duration'] . '</div>
          <div class="styles__episodeCreated___1zP5p">' . $episode['published_date'] . '</div>
        </div>
      ';

      $index++;
    }

    $html .= '</div>';

    return $html;
  }
}

// $Functions = new Functions();
// $Functions->get_rss_feed();
