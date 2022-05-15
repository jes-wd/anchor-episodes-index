document.addEventListener('DOMContentLoaded', () => {
  setPlayerIframeHeight()

  const siteUrl = window.jesaeiSiteUrl
  const rssUrl = window.jesaeiRssUrl

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
        const titleExcerpt = getTitleExcerpt(episode)
        const episodeIframeUrl = getEpisodeIframeUrl(episode, siteUrl)
        const descriptionExpcerpt = getDescriptionExcerpt(episode)
        const episodeLengthInMinutesAndSeconds =
          getEpisodeLengthInMinutesAndSeconds(episode)
        const episodeDate = getEpisodeDate(episode)

        // Output the episode on the page with the data we have prepared
        podcastListContainer.innerHTML += `
				<div class="styles__episodeFeedItem___1U6E2">
				  <a class="podcast-list-link styles__episodeImage___tMifW" href="${episodeIframeUrl}" target="jesaei_podcast_iframe">
					<img src="${thumbnailUrl}">
					<button class="styles__circle___1g-9u styles__white___372tQ styles__playButton___1Ivi4 styles__playButton___1uaGA" aria-label="" style="height: 31px; min-height: 31px; width: 31px; min-width: 31px; border-radius: 16px;">
					  <svg xmlns="http://www.w3.org/2000/svg" viewBox="-1 0 11 12" width="13" height="13"><rect width="12" height="12" fill="none"></rect><path d="M1 .81v10.38a.76.76 0 0 0 .75.75.67.67 0 0 0 .39-.12l8.42-5.18a.75.75 0 0 0 0-1.28L2.14.18a.75.75 0 0 0-1 .24.79.79 0 0 0-.14.39z" fill="#282F36"></path></svg>
					</button>
				  </a>
				  <a class="podcast-list-link" href="${episodeIframeUrl}" target="jesaei_podcast_iframe">
					<div class="styles__episodeHeading___29q7v">
					  <div>
						<div>
						  ${titleExcerpt}
						</div>
					  </div>
					</div>
				  </a>
				  <div class="styles__episodeDescription___C3oZg ">
					<div class="styles__expander___1NNVb styles__expander--dark___3Qxhe">
					  <div>
						<div class="podcast-description">
						  ${descriptionExpcerpt}
						</div>
					  </div>
					</div>
				  </div>
				  <div class="styles__episodeDuration___2I0Qb">
					${episodeLengthInMinutesAndSeconds}
				  </div>
				  <div class="styles__episodeCreated___1zP5p">
					${episodeDate}
				  </div>
				</div>
			  `
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

const getEpisodeLengthInMinutesAndSeconds = (episode) => {
  // get minutes and seconds from seconds formatted data
  let duration = episode.querySelector('duration').innerHTML
  const minutes = Math.floor(duration / 60)
  const seconds = duration - minutes * 60
  const hours = Math.floor(duration / 3600)
  duration = duration - hours * 3600
  const str_pad_left = (string, pad, length) => {
    return (new Array(length + 1).join(pad) + string).slice(-length)
  }
  const finalTime =
    str_pad_left(minutes, '0', 2) + ':' + str_pad_left(seconds, '0', 2)

  return finalTime
}

const getDescriptionExcerpt = (episode) => {
  // create an excerpt out of the description
  const description = episode
    .querySelector('description')
    .innerHTML.replace(']]>', '')
    .replace(/<[^>]*>?/gm, '') // strip html also
  const descriptionMaxLength = 114
  const descriptionExcerpt =
    description.length > descriptionMaxLength
      ? description.substring(0, descriptionMaxLength)
      : description
  const hasDescriptionExcerpt =
    description.length > descriptionMaxLength ? true : false
  const descriptionExcerptHtml = hasDescriptionExcerpt
    ? `<span class="podcast-description-show-more-btn" data-full-description="${description}">...</span>`
    : ''

  return descriptionExcerpt + descriptionExcerptHtml
}

const getEpisodesToShow = (feed) => {
  let episodes = feed.querySelectorAll('item')
  // convert from node list to array
  episodes = Array.from(episodes)
  const maxEpisodes = window.jesaeiMaxEpisodes

  if (maxEpisodes < episodes.length) {
    episodes = episodes.slice(0, maxEpisodes)
  }

  return episodes
}

const getTitleExcerpt = (episode) => {
  // create an excerpt out of the title
  const title = episode.querySelector('title').innerHTML.replace('<![CDATA[', '').replace(']]>', '')
  const titleMaxLength = 41
  const titleExcerpt = title.substring(0, titleMaxLength) + '...'

  return titleExcerpt
}

const getEpisodeIframeUrl = (episode, siteUrl) => {
  // Get position to split url for modification seen below
  const urlModPosition = siteUrl.length
  // add '/embed' to URL so that it works in an iframe
  const originalURL = episode.querySelector('link').innerHTML
  const pathToAdd = '/embed'
  const revisedUrl = [
    originalURL.slice(0, urlModPosition),
    pathToAdd,
    originalURL.slice(urlModPosition),
  ].join('')

  return revisedUrl
}

const checkShowMoreBtnsExist = setInterval(() => {
  if (
    document.getElementsByClassName('podcast-description-show-more-btn').length
  ) {
    setShowMoreEvents()
    clearInterval(checkShowMoreBtnsExist)
  }
}, 100) // check every 100ms

const setShowMoreEvents = () => {
  const showMoreBtns = document.getElementsByClassName(
    'podcast-description-show-more-btn'
  )
  for (i = 0; i < showMoreBtns.length; i++) {
    showMoreBtns[i].addEventListener('click', (e) => {
      e = e || window.event
      const target = e.target || e.srcElement
      const description = target.dataset.fullDescription
      target.parentElement.innerHTML = description
    })
  }
}

const setPlayerIframeHeight = () => {
  // set responsive breakpoint for the player iframe - adjust height
  const anchorIframe = document.getElementById('jesaei-anchor-podcast-iframe')
  const anchorIframeWidth = anchorIframe.offsetWidth
  const loadingAnimation = document.getElementById(
    'podcast-player-loading-animation'
  )

  if (anchorIframeWidth > 768) {
    anchorIframe.style.height = '161px'
    loadingAnimation.style.top = '40px'
  } else {
    anchorIframe.style.height = '98px'
    loadingAnimation.style.top = '10px'
  }
}
