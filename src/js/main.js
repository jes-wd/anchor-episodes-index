import {
  getDescriptionExcerpt,
  getEpisodesToShow,
  getTitleExcerpt,
  getEpisodeIframeUrl,
  setPlayerIframeHeight,
} from './functions'

document.addEventListener('DOMContentLoaded', () => {
  const siteUrl = jesaei_settings.site_url
  const rssUrl = jesaei_settings.rss_url
  const isProVersionActive = jesaei_settings.is_pro_version_active

  if (!isProVersionActive) {
    window.addEventListener('resize', setPlayerIframeHeight)
    setPlayerIframeHeight()
  }

  fetch(rssUrl)
    .then((response) => response.text())
    .then((str) => new window.DOMParser().parseFromString(str, 'text/xml'))
    .then((feed) => {
      const episodes = getEpisodesToShow(feed)
      const podcastListContainer = document.getElementById(
        'jesaei-podcast-list-container'
      )

      if (episodes.length === 0) {
        // hide episode container if there are no episodes
        document.getElementById('jesaei-podcast-list-container').style.display =
          'none'
      }

      for (const episode of episodes) {
        const thumbnailUrl = episode.querySelector('image').getAttribute('href')
        console.log(thumbnailUrl)
        // const titleExcerpt = getTitleExcerpt(episode)
        // const episodeIframeUrl = getEpisodeIframeUrl(episode, siteUrl)
        // const audioUrl = episode.querySelector('enclosure').getAttribute('url')
        // const descriptionExpcerpt = getDescriptionExcerpt(episode)
        // const episodeLengthInMinutesAndSeconds =
        //   episode.querySelector('duration').innerHTML
        // const episodeDate = getEpisodeDate(episode)

        // // Output the episode on the page with the data we have prepared
        // podcastListContainer.innerHTML += `
				// <div class="styles__episodeFeedItem___1U6E2">
				//   <a class="podcast-list-link styles__episodeImage___tMifW" href="${episodeIframeUrl}" target="jesaei_podcast_iframe" data-audio-url="${audioUrl}">
				// 	<img src="${thumbnailUrl}">
				// 	<button class="styles__circle___1g-9u styles__white___372tQ styles__playButton___1Ivi4 styles__playButton___1uaGA" aria-label="" style="height: 31px; min-height: 31px; width: 31px; min-width: 31px; border-radius: 16px;">
				// 	  <svg xmlns="http://www.w3.org/2000/svg" viewBox="-1 0 11 12" width="13" height="13"><rect width="12" height="12" fill="none"></rect><path d="M1 .81v10.38a.76.76 0 0 0 .75.75.67.67 0 0 0 .39-.12l8.42-5.18a.75.75 0 0 0 0-1.28L2.14.18a.75.75 0 0 0-1 .24.79.79 0 0 0-.14.39z" fill="#282F36"></path></svg>
				// 	</button>
				//   </a>
				//   <a class="podcast-list-link" href="${episodeIframeUrl}" target="jesaei_podcast_iframe" data-audio-url="${audioUrl}">
				// 	<div class="styles__episodeHeading___29q7v">
				// 	  <div>
				// 		<div>
				// 		  ${titleExcerpt}
				// 		</div>
				// 	  </div>
				// 	</div>
				//   </a>
				//   <div class="styles__episodeDescription___C3oZg ">
				// 	<div class="styles__expander___1NNVb styles__expander--dark___3Qxhe">
				// 	  <div>
				// 		<div class="podcast-description">
				// 		  ${descriptionExpcerpt}
				// 		</div>
				// 	  </div>
				// 	</div>
				//   </div>
				//   <div class="styles__episodeDuration___2I0Qb">
				// 	${episodeLengthInMinutesAndSeconds}
				//   </div>
				//   <div class="styles__episodeCreated___1zP5p">
				// 	${episodeDate}
				//   </div>
				// </div>
			  // `
      }
    })
})

const getEpisodeDate = (episode) => {
  // convert JSON ISO 8601 formatted date in to a readable date
  const date = new Date(episode.querySelector('pubDate').innerHTML)
  const monthNames = [
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
  ]
  const month = monthNames[date.getMonth()]
  const day = date.getDate()
  const year = date.getFullYear()

  return `${month}  ${day}, ${year}`
}
