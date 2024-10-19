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
    $rss_id = $this->get_unique_id_from_rss_url($rss_url);
    $transient_key = 'jesaei_episodes_' . $rss_id;

    // delete transient
    // delete_transient($transient_key);

    // if not stored in transient, fetch rss feed
    if (false === ($feed_array = get_transient($transient_key))) {

      $ch = curl_init($rss_url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $feed_content = curl_exec($ch);
      curl_close($ch);

      $feed_xml = simplexml_load_string($feed_content);
      $feed_array = array();

      foreach ($feed_xml->channel->item as $item) {
        $itunes_data = $item->children('itunes', true);
        $image_url = (string)$itunes_data->image->attributes()->href;
        // $image_name = basename($image_url);
        // $image_exists_in_custom_folder = $this->image_exists_in_custom_folder($image_name);

        // // If image does not exist in custom folder, download and save it
        // if (!$image_exists_in_custom_folder) {
        //   error_log('image does not exist in custom folder');
        //   error_log('image name: ' . $image_name);
        //   $this->save_image_to_custom_folder($image_url, $image_name);
        // }

        // // Get thumbnail URL of the image
        // $upload_dir = wp_upload_dir();
        // $thumbnail_url = $upload_dir['baseurl'] . '/anchor-episodes-index/' . $image_name;

        $feed_array[] = array(
          "title" => esc_html((string)$item->title),
          "title_excerpt" => esc_html((string)$this->RSS_Data_Formatting->get_title_excerpt($item->title)),
          "iframe_url" => esc_url((string)$this->RSS_Data_Formatting->get_iframe_url($item->link)),
          "audio_url" => esc_url((string)$item->enclosure->attributes()->url),
          "description" => (string) $this->RSS_Data_Formatting->sanitize_description($item->description),
          "description_excerpt" => wp_kses_post((string)$this->RSS_Data_Formatting->get_description_excerpt($item->description)),
          "published_date" => esc_html((string)$this->RSS_Data_Formatting->get_published_date($item->pubDate)),
          "guid" => esc_html((string)$item->guid),
          "image_url" => esc_url($image_url),
          "author" => esc_html((string)$itunes_data->author),
          "subtitle" => esc_html((string)$itunes_data->subtitle),
          "summary" => esc_html((string)$itunes_data->summary),
          "duration" => esc_html((string)$itunes_data->duration),
          "keywords" => esc_html((string)$itunes_data->keywords),
          "site_url" => esc_url((string)$item->link),
          "author" => esc_html((string)$feed_xml->channel->image->title)
        );
      }

      // error_log('feed array: ' . print_r($feed_array, true));

      // save feed array in transient with 15 minute expiration
      set_transient($transient_key, $feed_array, 15 * MINUTE_IN_SECONDS);
    }

    return $feed_array;
  }


  public function get_episode_list_html(int $limit, string $rss_url = null) {
    $this->options['anchor_rss_url'] = $rss_url;
    require('svgs.php');

    $episodes = $this->get_rss_feed();
    $html = '<div id="jesaei-podcast-list-container" class="jesaei-podcast-list-container styles__episodeFeed___3mOKz">';
    $index = 0;

    foreach ($episodes as $episode) {
      if ($index >= $limit) {
        break;
      }

      $image_url = apply_filters('jesaei_modify_episode_image_url', $episode['image_url']);
      $link_attributes = '';

      if (JESAEI_IS_PRO_ACTIVE) {
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

      $html .= '
        <div class="styles__episodeFeedItem___1U6E2 ' . (JESAEI_IS_PRO_ACTIVE ? ($index === 0 ? 'jesaeip-selected-episode' : '') : '') . '">
          <span class="styles__isActiveEpisode___cXlB4"></span>  
          <a class="jesaeip-episode-play-btn podcast-list-link styles__episodeImage___tMifW" ' . $link_attributes . ' aria-label="Play ' . $episode['title'] . '">
            <img src="' . $image_url . '" alt="' . $episode['title'] . '">
            <button class="styles__circle___1g-9u styles__white___372tQ styles__playButton___1Ivi4 styles__playButton___1uaGA" aria-label="Play" style="height: 31px; min-height: 31px; width: 31px; min-width: 31px; border-radius: 16px;">
            ' . $play_icon . '
            ' . $pause_icon . '
            </button>
          </a>
          <a class="podcast-list-link" href="' . $episode['iframe_url'] . '" target="jesaei_podcast_iframe" data-audio-url="' . $episode['audio_url'] . '" aria-label="' . $episode['title'] . '">
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
    $id = md5($path);

    return $id;
  }

  function image_exists_in_custom_folder($filename) {
    $upload_dir = wp_upload_dir();
    $custom_folder = $upload_dir['basedir'] . '/anchor-episodes-index/';
    return file_exists($custom_folder . $filename);
  }


  function save_image_to_custom_folder($image_url) {
    $upload_dir = wp_upload_dir();
    $custom_folder = $upload_dir['basedir'] . '/anchor-episodes-index/';
    $custom_url = $upload_dir['baseurl'] . '/anchor-episodes-index/';

    // Create custom folder if it doesn't exist
    if (!file_exists($custom_folder)) {
      mkdir($custom_folder, 0755, true);
    }

    // Get the filename from the URL
    $filename = basename($image_url);

    // Check if the file already exists in the custom folder
    if (file_exists($custom_folder . $filename)) {
      return $custom_url . $filename;
    }

    // Get the image file
    $image_data = file_get_contents($image_url);

    // Save the image file to the custom folder
    $file_path = $custom_folder . $filename;
    file_put_contents($file_path, $image_data);

    // Resize the image to 150x150
    $image = imagecreatefromstring($image_data);
    $resized_image = imagescale($image, 150, 150);
    imagejpeg($resized_image, $file_path);
    imagedestroy($image);
    imagedestroy($resized_image);

    return $custom_url . $filename;
  }

  public function set_rss_url(string $rss_url) {
    $this->options['anchor_rss_url'] = $rss_url;
  }
}
