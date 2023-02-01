<?php

namespace Anchor_Episodes_Index;

class RSS_Data_Formatting {
    public $options;
    public $episode;

    public function __construct() {
        $this->options = get_option('jes_anchor_settings');
        $this->episode = null;
    }

    public function set_episode($episode) {
        $this->episode = $episode;

        // echo '<pre>';
        // print_r($this->episode);
        // echo '</pre>';
    }
    // export const getDescriptionExcerpt = (episode) => {
    //     const description = episode
    //       .querySelector('description')
    //       .innerHTML.replace('<![CDATA[', '')
    //       .replace(']]>', '')
    //       .replace(/\<(?!p|br).*?\>/g, '', '')
    //       .replace(/"/g, '&quot;')
      
    //     const descriptionNoHtml = description.replace(/(<([^>]+)>)/gi, '')
    //     const descriptionMaxLength = 114
    //     const descriptionExcerpt =
    //       descriptionNoHtml.length > descriptionMaxLength
    //         ? descriptionNoHtml.substring(0, descriptionMaxLength)
    //         : description
    //     const hasDescriptionExcerpt =
    //       description.length > descriptionMaxLength ? true : false
    //     const descriptionExcerptHtml = hasDescriptionExcerpt
    //       ? `<span class="podcast-description-show-more-btn" data-full-description="${description}">...</span>`
    //       : ''
      
    //     return descriptionExcerpt + descriptionExcerptHtml
    //   }
      
    //   export const getEpisodesToShow = (feed) => {
    //     let episodes = feed.querySelectorAll('item')
    //     // convert from node list to array
    //     episodes = Array.from(episodes)
    //     const maxEpisodes = jesaei_settings.max_episodes
      
    //     if (maxEpisodes < episodes.length) {
    //       episodes = episodes.slice(0, maxEpisodes)
    //     }
      
    //     return episodes
    //   }
      
    //   export const getTitleExcerpt = (episode) => {
    //     // create an excerpt out of the title
    //     const title = episode
    //       .querySelector('title')
    //       .innerHTML.replace('<![CDATA[', '')
    //       .replace(']]>', '')
    //     const titleMaxLength = 39
    //     const titleExcerpt = title.substring(0, titleMaxLength) + '...'
      
    //     return titleExcerpt
    //   }

    

    public function format_episode(): array {
        // const thumbnailUrl = episode.querySelector('image').getAttribute('href')
        // const titleExcerpt = getTitleExcerpt(episode)
        // const episodeIframeUrl = getEpisodeIframeUrl(episode, siteUrl)
        // const audioUrl = episode.querySelector('enclosure').getAttribute('url')
        // const descriptionExpcerpt = getDescriptionExcerpt(episode)
        // const episodeLengthInMinutesAndSeconds =
        //   episode.querySelector('duration').innerHTML
        // const episodeDate = getEpisodeDate(episode)

        echo '<pre>';
        echo $this->episode->children('description', true)->innerHTML;
        echo '</pre>';

        // get the description
        $description = $this->episode->children('description', true)->innerHTML;

        return [
            'thumbnail_url' => $this->episode->children('itunes', true)->image->attributes()->href, //  $data->channel->item[2]->children('itunes', true)->image->attributes()->href;
            'title' => $this->episode['title'],
            'description' => $this->episode['description'],
            'title_excerpt' => $this->get_title_excerpt(),
            'episode_iframe_url' => $this->get_iframe_url(),
            'audio_url' => $this->episode['enclosure']['@attributes']['url'],
            'description_excerpt' => $this->get_description_excerpt(),
            'episode_length_in_minutes_and_seconds' => $this->episode['itunes:duration'],
            'episode_date' => $this->get_published_date(),
        ];
    }

    public function get_iframe_url($url): string {
        // Get position to split url for modification seen below
        //   const urlModPosition = siteUrl.length
        //   // add '/embed' to URL so that it works in an iframe
        //   const originalURL = episode.querySelector('link').innerHTML
        //   const pathToAdd = '/embed'
        //   const revisedUrl = [
        //     originalURL.slice(0, urlModPosition),
        //     pathToAdd,
        //     originalURL.slice(urlModPosition),
        //   ].join('')

        // convert the above commmented javascript to a php function
        $url_mod_position = strlen($this->options['site_url']);
        $path_to_add = '/embed';
        $revised_url = substr_replace($url, $path_to_add, $url_mod_position, 0);

        return $revised_url;
    }

    public function get_title_excerpt($title) {
        $title_max_length = 39;
        $title_excerpt = substr($title, 0, $title_max_length) . '...';

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
        // const getEpisodeDate = (episode) => {
        //     // convert JSON ISO 8601 formatted date in to a readable date
        //     const date = new Date(episode.querySelector('pubDate').innerHTML)
        //     const monthNames = [
        //       'January',
        //       'February',
        //       'March',
        //       'April',
        //       'May',
        //       'June',
        //       'July',
        //       'August',
        //       'September',
        //       'October',
        //       'November',
        //       'December',
        //     ]
        //     const month = monthNames[date.getMonth()]
        //     const day = date.getDate()
        //     const year = date.getFullYear()
          
        //     return `${month}  ${day}, ${year}`
        //   }

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
