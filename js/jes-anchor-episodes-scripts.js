// Your site url (must include https://anchor.fm/)
const siteUrl = window.jesAnchorEpisodesSiteUrl;
// Get position to split url for modification seen below
const urlModPosition = siteUrl.length;
// RSS feed URL (site key is found in RSS feed URL)
const rssUrl = window.jesAnchorEpisodesRssUrl;
// Container which holds the iframe - so that we can append the podcast list just below it
const iframeContainer = document.getElementById('podcasts-player-container');

// Use feednami to parse the RSS in to JSON and give us a JSON object
feednami.load(rssUrl).then((feed) => {
	// Add the container for the list before the loop
	iframeContainer.innerHTML += `<div id="podcast-list-container" class="styles__episodeFeed___3mOKz"></div>`;
	const podcastListContainer = document.getElementById(
		'podcast-list-container'
	);

	// Loop through the JSON to produce the list
	for (const entry of feed.entries) {
		// add '/embed' to URL so that it works in an iframe
		const originalURL = entry.link;
		const pathToAdd = '/embed';
		const revisedUrl = [
			originalURL.slice(0, urlModPosition),
			pathToAdd,
			originalURL.slice(urlModPosition),
		].join('');

		// create an excerpt out of the title
		const c = entry.title;
		const clength = 41;
		const titleExcerpt = c.substring(0, clength) + '...';

		// create an excerpt out of the description
		const d = entry.description.replace(/<[^>]*>?/gm, ''); // strip html also
		const dlength = 114;
		const descriptionExcerpt = d.length > dlength ? d.substring(0, dlength) : d;
		const hasDescriptionExcerpt = d.length > dlength ? true : false;

		// get minutes and seconds from seconds formatted data
		let time = entry['itunes:duration']['#'];
		const minutes = Math.floor(time / 60);
		const seconds = time - minutes * 60;
		const hours = Math.floor(time / 3600);
		time = time - hours * 3600;

		const str_pad_left = (string, pad, length) => {
			return (new Array(length + 1).join(pad) + string).slice(-length);
		};

		const finalTime =
			str_pad_left(minutes, '0', 2) + ':' + str_pad_left(seconds, '0', 2);

		// convert JSON ISO 8601 formatted date in to a readable date
		const date = new Date(entry.date);
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
		];
		const month = monthNames[date.getMonth()];
		const day = date.getDate();
		const year = date.getFullYear();

		// Output the episode on the page with the data we have prepared
		podcastListContainer.innerHTML += `
              <div class="styles__episodeFeedItem___1U6E2">
                <a class="podcast-list-link styles__episodeImage___tMifW" href="${revisedUrl}" target="iframe">
                  <img src="${entry.image.url}">
                  <button class="styles__circle___1g-9u styles__white___372tQ styles__playButton___1Ivi4 styles__playButton___1uaGA" aria-label="" style="height: 31px; min-height: 31px; width: 31px; min-width: 31px; border-radius: 16px;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="-1 0 11 12" width="13" height="13"><rect width="12" height="12" fill="none"></rect><path d="M1 .81v10.38a.76.76 0 0 0 .75.75.67.67 0 0 0 .39-.12l8.42-5.18a.75.75 0 0 0 0-1.28L2.14.18a.75.75 0 0 0-1 .24.79.79 0 0 0-.14.39z" fill="#282F36"></path></svg>
                  </button>
                </a>
                <a class="podcast-list-link" href="${revisedUrl}" target="iframe">
                  <div class="styles__episodeHeading___29q7v" style="overflow: hidden;">
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
                        ${descriptionExcerpt}
						${
							hasDescriptionExcerpt
								? `<span class="podcast-description-show-more-btn" data-full-description="${d}">...</span>`
								: ''
						}
                      </div>
                    </div>
                  </div>
                </div>
                <div class="styles__episodeDuration___2I0Qb">
                  ${finalTime}
                </div>
                <div class="styles__episodeCreated___1zP5p">
                  ${month} ${day}, ${year}
                </div>
              </div>
            `;
	}
});

const checkShowMoreBtnsExist = setInterval(() => {
	if (
		document.getElementsByClassName('podcast-description-show-more-btn').length
	) {
		setShowMoreEvents();
		clearInterval(checkShowMoreBtnsExist);
	}
}, 100); // check every 100ms

const setShowMoreEvents = () => {
	const showMoreBtns = document.getElementsByClassName(
		'podcast-description-show-more-btn'
	);
	for (i = 0; i < showMoreBtns.length; i++) {
		showMoreBtns[i].addEventListener('click', (e) => {
			e = e || window.event;
			const target = e.target || e.srcElement;
			const description = target.dataset.fullDescription;
			target.parentElement.innerHTML = description;
		});
	}
};

// set responsive breakpoint for the player iframe - adjust height
const anchorIframe = document.getElementById('anchor-podcast-iframe');
const anchorIframeWidth = anchorIframe.offsetWidth;
const loadingAnimation = document.getElementById(
	'podcast-player-loading-animation'
);

if (anchorIframeWidth > 768) {
	anchorIframe.style.height = '161px';
	loadingAnimation.style.top = '40px';
} else {
	anchorIframe.style.height = '98px';
	loadingAnimation.style.top = '10px';
}
