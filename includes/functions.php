<?php

namespace Anchor_Episodes_Index;

if (!defined('ABSPATH')) {
  exit;
}

class Functions {
  public $options;
  public $RSS_Data_Formatting;

  /** Seconds to cache a successful feed fetch. */
  const FEED_CACHE_TTL = 15 * MINUTE_IN_SECONDS;

  /** Seconds to cache a FAILED feed fetch, so a dead feed is not re-fetched on every page view. */
  const FEED_FAIL_CACHE_TTL = 2 * MINUTE_IN_SECONDS;

  public function __construct() {
    $this->options = get_option('jes_anchor_settings');
    $this->RSS_Data_Formatting = new RSS_Data_Formatting();
  }

  /**
   * Transient key for a given feed URL. Public so callers (e.g. the settings
   * save handler) can invalidate the right cache entry.
   */
  public function get_transient_key(string $rss_url = '') {
    return 'jesaei_episodes_' . $this->get_unique_id_from_rss_url($rss_url);
  }

  /**
   * Fetch and parse the podcast RSS feed.
   *
   * Always returns an array. On any failure (bad URL, network error, non-200,
   * unparseable XML, feed with no <item> elements) it returns an empty array
   * rather than emitting warnings or fataling on PHP 8.
   */
  public function get_rss_feed() {
    $rss_url = isset($this->options['anchor_rss_url']) ? (string) $this->options['anchor_rss_url'] : '';

    if ($rss_url === '') {
      return array();
    }

    $transient_key = $this->get_transient_key($rss_url);

    $cached = get_transient($transient_key);
    if (is_array($cached)) {
      return $cached;
    }

    $feed_content = $this->request_feed($rss_url);

    if ($feed_content === null) {
      // Negative-cache so a broken feed does not hammer the remote host or
      // block every page render while it is down.
      set_transient($transient_key, array(), self::FEED_FAIL_CACHE_TTL);
      return array();
    }

    $feed_array = $this->parse_feed($feed_content);

    set_transient(
      $transient_key,
      $feed_array,
      empty($feed_array) ? self::FEED_FAIL_CACHE_TTL : self::FEED_CACHE_TTL
    );

    return $feed_array;
  }

  /**
   * Perform the HTTP request.
   *
   * Uses wp_safe_remote_get() rather than raw cURL: it respects the site's
   * HTTP configuration, works on hosts without the cURL extension, always has
   * a timeout, and blocks requests to private/internal addresses (the feed URL
   * can come from a shortcode attribute, so it is not fully trusted input).
   *
   * @return string|null Response body, or null on any failure.
   */
  private function request_feed(string $rss_url) {
    $response = wp_safe_remote_get(
      $rss_url,
      array(
        'timeout'    => 10,
        'user-agent' => 'AnchorEpisodesIndex/' . (defined('JESAEI_VERSION') ? JESAEI_VERSION : '0') . '; ' . home_url('/'),
      )
    );

    if (is_wp_error($response)) {
      return null;
    }

    if ((int) wp_remote_retrieve_response_code($response) !== 200) {
      return null;
    }

    $body = wp_remote_retrieve_body($response);

    return ($body === '') ? null : $body;
  }

  /**
   * Turn feed XML into the episode array.
   *
   * @return array Empty array if the XML cannot be parsed or has no items.
   */
  private function parse_feed(string $feed_content) {
    // Keep malformed-feed warnings out of the page output / error log.
    $previous_state = libxml_use_internal_errors(true);
    $feed_xml       = simplexml_load_string($feed_content);
    libxml_clear_errors();
    libxml_use_internal_errors($previous_state);

    if ($feed_xml === false || !isset($feed_xml->channel->item)) {
      return array();
    }

    $channel_title = isset($feed_xml->channel->image->title)
      ? (string) $feed_xml->channel->image->title
      : (isset($feed_xml->channel->title) ? (string) $feed_xml->channel->title : '');

    $feed_array = array();

    foreach ($feed_xml->channel->item as $item) {
      $itunes_data = $item->children('itunes', true);

      $image_url = isset($itunes_data->image) && isset($itunes_data->image->attributes()->href)
        ? (string) $itunes_data->image->attributes()->href
        : '';

      $audio_url = isset($item->enclosure) && isset($item->enclosure->attributes()->url)
        ? (string) $item->enclosure->attributes()->url
        : '';

      $episode_data = array(
        "title" => esc_html((string)$item->title),
        "title_excerpt" => esc_html((string)$this->RSS_Data_Formatting->get_title_excerpt($item->title)),
        "iframe_url" => esc_url((string)$this->RSS_Data_Formatting->get_iframe_url($item->link)),
        "audio_url" => esc_url($audio_url),
        // NOTE: the full sanitised description is not stored separately. It is
        // already embedded in description_excerpt's data-full-description
        // attribute, and storing it twice was 37% of the cached feed.
        "description_excerpt" => wp_kses_post((string)$this->RSS_Data_Formatting->get_description_excerpt($item->description)),
        "published_date" => esc_html((string)$this->RSS_Data_Formatting->get_published_date($item->pubDate)),
        // published_date is formatted for display and has lost the time, which
        // matters for ordering shows that publish more than once a day.
        "published_timestamp" => $this->RSS_Data_Formatting->get_published_timestamp($item->pubDate),
        "guid" => esc_html((string)$item->guid),
        "image_url" => esc_url($image_url),
        "subtitle" => esc_html((string)$itunes_data->subtitle),
        "summary" => esc_html((string)$itunes_data->summary),
        "duration" => esc_html($this->RSS_Data_Formatting->format_duration($itunes_data->duration)),
        "keywords" => esc_html((string)$itunes_data->keywords),
        "site_url" => esc_url((string)$item->link),
        "author" => esc_html($channel_title)
      );

      // Podcasting 2.0 tags: transcripts, chapters and per-episode credits.
      // URLs are kept raw here because they are fetched, not printed; the
      // renderer escapes them at output.
      $transcripts = Podcast_Namespace::get_transcripts($item);
      $numbering   = Podcast_Namespace::get_numbering($item);

      $episode_data['transcripts']        = $transcripts;
      $episode_data['primary_transcript'] = Podcast_Namespace::pick_transcript($transcripts);
      $episode_data['chapters']           = Podcast_Namespace::get_chapters($item);
      $episode_data['persons']            = Podcast_Namespace::get_persons($item);
      $episode_data['season']             = $numbering['season'];
      $episode_data['episode_number']     = $numbering['episode_number'];
      $episode_data['episode_type']       = $numbering['episode_type'];
      $episode_data['explicit']           = $numbering['explicit'];

      /**
       * One parsed episode, before it is cached.
       *
       * @param array             $episode_data
       * @param \SimpleXMLElement $item Raw feed item, for anything not parsed here.
       */
      $feed_array[] = apply_filters('jesaei_episode_data', $episode_data, $item);
    }

    return $feed_array;
  }

  /**
   * @param string|null $player_mode 'pro', 'native' or 'iframe'. Null keeps the
   *                                 pre-2.3 behaviour for any external caller.
   */
  public function get_episode_list_html(int $limit, ?string $rss_url = null, ?string $player_mode = null) {
    $this->options['anchor_rss_url'] = $rss_url;
    require('svgs.php');

    $episodes = $this->get_rss_feed();

    if ($player_mode === null) {
      $player_mode = JESAEI_IS_PRO_ACTIVE ? 'pro' : 'iframe';
    }

    // Both the Pro player and the built-in player are driven from data
    // attributes; only the legacy Spotify iframe uses a link target.
    $uses_js_player = ($player_mode === 'pro' || $player_mode === 'native');

    $html = '<div id="jesaei-podcast-list-container" class="jesaei-podcast-list-container styles__episodeFeed___3mOKz">';
    $index = 0;

    foreach ($episodes as $episode) {
      if ($index >= $limit) {
        break;
      }

      $image_url = apply_filters('jesaei_modify_episode_image_url', $episode['image_url']);
      $link_attributes = '';

      if ($uses_js_player) {
        $link_attributes .= 'data-audio-url="' . $episode['audio_url'] . '"';
        $link_attributes .= 'data-episode-title="' . $episode['title'] . '"';
        $link_attributes .= 'data-episode-image="' . $image_url . '"';
        $link_attributes .= 'data-episode-published-date="' . $episode['published_date'] . '"';
        $link_attributes .= 'data-episode-duration="' . $episode['duration'] . '"';
        $link_attributes .= 'data-episode-author="' . $episode['author'] . '"';
        $link_attributes .= 'data-episode-site-url="' . $episode['site_url'] . '"';
      } else {
        $link_attributes .= 'href="' . $episode['iframe_url'] . '" target="jesaei_podcast_iframe"';
      }

      // The built-in player binds to its own class so it can never
      // double-bind with Pro's script, which uses the jesaeip- prefix.
      $play_btn_classes = 'jesaeip-episode-play-btn'
        . ($player_mode === 'native' ? ' jesaei-episode-play-btn' : '');

      // In native mode there is no iframe to target, so the title becomes a
      // play trigger rather than a link to nowhere.
      $title_link = $player_mode === 'native'
        ? '<a class="podcast-list-link jesaei-episode-play-btn" href="#" ' . $link_attributes . ' aria-label="' . $episode['title'] . '">'
        : '<a class="podcast-list-link" href="' . $episode['iframe_url'] . '" target="jesaei_podcast_iframe" data-audio-url="' . $episode['audio_url'] . '" aria-label="' . $episode['title'] . '">';

      $html .= '
        <div class="styles__episodeFeedItem___1U6E2 ' . ($uses_js_player && $index === 0 ? 'jesaeip-selected-episode' : '') . '">
          <span class="styles__isActiveEpisode___cXlB4"></span>  
          <a class="' . $play_btn_classes . ' podcast-list-link styles__episodeImage___tMifW" ' . $link_attributes . ' aria-label="Play ' . $episode['title'] . '">
            <img src="' . $image_url . '" alt="' . $episode['title'] . '">
            <button class="styles__circle___1g-9u styles__white___372tQ styles__playButton___1Ivi4 styles__playButton___1uaGA" aria-label="Play" style="height: 31px; min-height: 31px; width: 31px; min-width: 31px; border-radius: 16px;">
            ' . $play_icon . '
            ' . $pause_icon . '
            </button>
          </a>
          ' . $title_link . '
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

  public function get_unique_id_from_rss_url(string $url) {
    $path = parse_url($url, PHP_URL_PATH);

    // parse_url() returns null when there is no path component, and false on a
    // seriously malformed URL. md5() requires a string on PHP 8.
    if (!is_string($path)) {
      $path = '';
    }

    $id = md5($path);

    return $id;
  }

  public function set_rss_url(string $rss_url) {
    $this->options['anchor_rss_url'] = $rss_url;
  }
}
