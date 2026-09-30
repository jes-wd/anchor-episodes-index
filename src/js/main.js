import { setPlayerIframeHeight } from './functions'
import { initNativePlayer } from './player'

document.addEventListener('DOMContentLoaded', () => {
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
