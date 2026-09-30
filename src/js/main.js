import { setPlayerIframeHeight } from './functions'
import { initNativePlayer } from './player'
import { hideBrokenImages } from './images'

document.addEventListener('DOMContentLoaded', () => {
  // Applies in every mode: the episode list is rendered the same way
  // whether the player is the iframe, the built-in one, or Pro's.
  hideBrokenImages()

  const settings = window.jesaei_settings || {}

  // player_mode arrived in 2.3.0; fall back to the old behaviour if an older
  // PHP side is somehow in play.
  const mode =
    settings.player_mode || (settings.is_pro_version_active ? 'pro' : 'iframe')

  if (mode === 'native') {
    initNativePlayer()
    return
  }

  if (mode === 'iframe') {
    window.addEventListener('resize', setPlayerIframeHeight)
    setPlayerIframeHeight()
  }
})
