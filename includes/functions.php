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

    // delete_transient('jesaei_rss_feed');

    // if not stored in transient, fetch rss feed
    if (false === ($feed_array = get_transient('jesaei_rss_feed'))) {

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
        );
      }

      // echo pre
      echo '<pre>';
      print_r($feed_array);
      echo '</pre>';

      // save feed array in transient with 15 minute expiration
      set_transient('jesaei_rss_feed', $feed_array, 15 * MINUTE_IN_SECONDS);
    }

    return $feed_array;
  }

  public function get_episode_list_html() {
    $episodes = $this->get_rss_feed();
    $html = '<div id="jesaei-podcast-list-container" class="jesaei-podcast-list-container styles__episodeFeed___3mOKz">';

    foreach ($episodes as $episode) {
      $link_attributes = JESAEI_IS_PRO_ACTIVE ? 'data-audio-url="' . $episode['audio_url'] . '"' : 'href="' . $episode['iframe_url'] . '" target="jesaei_podcast_iframe"';

      $html .= '
        <div class="styles__episodeFeedItem___1U6E2">
          <a class="podcast-list-link styles__episodeImage___tMifW" ' . $link_attributes . '">
            <img src="' . $episode['image_url'] . '">
            <button class="styles__circle___1g-9u styles__white___372tQ styles__playButton___1Ivi4 styles__playButton___1uaGA" aria-label="" style="height: 31px; min-height: 31px; width: 31px; min-width: 31px; border-radius: 16px;">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="-1 0 11 12" width="13" height="13">
                <rect width="12" height="12" fill="none"></rect>
                <path d="M1 .81v10.38a.76.76 0 0 0 .75.75.67.67 0 0 0 .39-.12l8.42-5.18a.75.75 0 0 0 0-1.28L2.14.18a.75.75 0 0 0-1 .24.79.79 0 0 0-.14.39z" fill="#282F36"></path>
              </svg>
            </button>
          </a>
          <a class="podcast-list-link" href="' . $episode['episode_iframe_url'] . '" target="jesaei_podcast_iframe" data-audio-url="' . $episode['audio_url'] . '">
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
    }

    $html .= '</div>';

    return $html;
  }
}

// $Functions = new Functions();
// $Functions->get_rss_feed();
