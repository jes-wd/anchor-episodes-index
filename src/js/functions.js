export const getDescriptionExcerpt = (episode) => {
  const description = episode
    .querySelector('description')
    .innerHTML.replace('<![CDATA[', '')
    .replace(']]>', '')
    .replace(/\<(?!p|br).*?\>/g, '', '')
    .replace(/"/g, '&quot;')

  const descriptionNoHtml = description.replace(/(<([^>]+)>)/gi, '')
  const descriptionMaxLength = 114
  const descriptionExcerpt =
    descriptionNoHtml.length > descriptionMaxLength
      ? descriptionNoHtml.substring(0, descriptionMaxLength)
      : description
  const hasDescriptionExcerpt =
    description.length > descriptionMaxLength ? true : false
  const descriptionExcerptHtml = hasDescriptionExcerpt
    ? `<span class="podcast-description-show-more-btn" data-full-description="${description}">...</span>`
    : ''

  return descriptionExcerpt + descriptionExcerptHtml
}

export const getEpisodesToShow = (feed) => {
  let episodes = feed.querySelectorAll('item')
  // convert from node list to array
  episodes = Array.from(episodes)
  const maxEpisodes = jesaei_settings.max_episodes

  if (maxEpisodes < episodes.length) {
    episodes = episodes.slice(0, maxEpisodes)
  }

  return episodes
}

export const getTitleExcerpt = (episode) => {
  // create an excerpt out of the title
  const title = episode
    .querySelector('title')
    .innerHTML.replace('<![CDATA[', '')
    .replace(']]>', '')
  const titleMaxLength = 39
  const titleExcerpt = title.substring(0, titleMaxLength) + '...'

  return titleExcerpt
}

export const getEpisodeIframeUrl = (episode, siteUrl) => {
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

export const checkShowMoreBtnsExist = setInterval(() => {
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

  for (let i = 0; i < showMoreBtns.length; i++) {
    showMoreBtns[i].addEventListener('click', (e) => {
      e = e || window.event
      const target = e.target || e.srcElement
      const description = target.dataset.fullDescription
      target.parentElement.innerHTML = description
    })
  }
}

export const setPlayerIframeHeight = () => {
  // set responsive breakpoint for the player iframe - adjust height
  const anchorIframe = document.getElementById('jesaei-anchor-podcast-iframe')
  const anchorIframeWidth = anchorIframe.offsetWidth
  const loadingAnimation = document.getElementById(
    'jesaei-player-loading-animation'
  )

  if (anchorIframeWidth > 768) {
    anchorIframe.style.height = '161px'
    loadingAnimation.style.top = '40px'
  } else {
    anchorIframe.style.height = '98px'
    loadingAnimation.style.top = '10px'
  }
}
