import {
  setPlayerIframeHeight,
} from './functions'

document.addEventListener('DOMContentLoaded', () => {
  const isProVersionActive = jesaei_settings.is_pro_version_active

  if (!isProVersionActive) {
    window.addEventListener('resize', setPlayerIframeHeight)
    setPlayerIframeHeight()
  }
})
