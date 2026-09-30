/**
 * Hide artwork that fails to load.
 *
 * Feeds outlive the images they point at. Anchor-era shows in particular now
 * have artwork URLs that return 403 from the old CloudFront bucket, and a
 * failed <img> renders as a broken-image box, which looks like the plugin is
 * at fault rather than the feed.
 *
 * The image is hidden rather than removed, so the container keeps its size and
 * its neutral background shows through as a plain tile.
 */
export const hideBrokenImages = () => {
  const root = document.getElementById('jesaei-player-container')
  if (!root) return

  const mark = (img) => img.classList.add('jesaei-img-failed')

  const images = root.getElementsByTagName('img')

  for (let i = 0; i < images.length; i++) {
    const img = images[i]

    // complete === true with naturalWidth 0 is how a browser reports an image
    // that has already finished failing before this script ran.
    if (img.complete && img.naturalWidth === 0) {
      mark(img)
    } else if (!img.complete) {
      img.addEventListener('error', function () { mark(this) })
    }
  }

  // The player swaps its artwork when the episode changes, so a later failure
  // has to be caught too. Capture phase: error events do not bubble.
  root.addEventListener(
    'error',
    (e) => { if (e.target && e.target.tagName === 'IMG') mark(e.target) },
    true
  )
}
