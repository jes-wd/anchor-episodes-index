/**
 * Built-in podcast player.
 *
 * Plays the enclosure URLs straight out of the feed, so it works with any
 * podcast host. Only initialised when jesaei_settings.player_mode is 'native';
 * in 'pro' mode the Pro plugin's own script drives its player instead, and the
 * two bind to different class names so they can never collide.
 */

const formatTime = (seconds) => {
  if (!isFinite(seconds) || seconds < 0) seconds = 0

  const pad = (n) => String(n).padStart(2, '0')
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const secs = Math.floor(seconds % 60)

  return hours > 0
    ? `${hours}:${pad(minutes)}:${pad(secs)}`
    : `${pad(minutes)}:${pad(secs)}`
}

export const initNativePlayer = () => {
  const root = document.getElementById('jesaei-np')
  if (!root) return

  const audio = document.getElementById('jesaei-np-audio')
  const playBtn = document.getElementById('jesaei-np-play')
  const progress = document.getElementById('jesaei-np-progress')
  const bar = document.getElementById('jesaei-np-bar')
  const elapsed = document.getElementById('jesaei-np-elapsed')
  const durationEl = document.getElementById('jesaei-np-duration')
  const art = document.getElementById('jesaei-np-art')
  const titleEl = document.getElementById('jesaei-np-title')
  const dateEl = document.getElementById('jesaei-np-date')
  const authorEl = document.getElementById('jesaei-np-author')

  if (!audio || !playBtn || !progress || !bar) return

  const play = () => {
    const attempt = audio.play()
    // Rejects if the browser blocks playback; nothing useful to do, but an
    // unhandled rejection would show up in the console.
    if (attempt && typeof attempt.catch === 'function') attempt.catch(() => {})
  }

  const setPlayingState = (isPlaying) => {
    root.classList.toggle('is-playing', isPlaying)
    playBtn.setAttribute('aria-label', isPlaying ? 'Pause' : 'Play')

    const current = audio.getAttribute('src')
    const buttons = document.querySelectorAll('.jesaei-episode-play-btn')

    for (let i = 0; i < buttons.length; i++) {
      const active = isPlaying && buttons[i].getAttribute('data-audio-url') === current
      buttons[i].classList.toggle('jesaeip-playing', active)
    }
  }

  const render = () => {
    const duration = audio.duration
    const percent =
      isFinite(duration) && duration > 0 ? (audio.currentTime / duration) * 100 : 0

    bar.style.width = percent + '%'
    progress.setAttribute('aria-valuenow', String(Math.round(percent)))
    elapsed.textContent = formatTime(audio.currentTime)

    // Prefer the real duration once the browser knows it; until then the
    // feed's own itunes:duration string stays in place.
    if (isFinite(duration) && duration > 0) durationEl.textContent = formatTime(duration)
  }

  playBtn.addEventListener('click', () => {
    audio.paused ? play() : audio.pause()
  })

  audio.addEventListener('play', () => setPlayingState(true))
  audio.addEventListener('pause', () => setPlayingState(false))
  audio.addEventListener('ended', () => setPlayingState(false))
  audio.addEventListener('timeupdate', render)
  audio.addEventListener('loadedmetadata', render)

  const seekTo = (clientX) => {
    const rect = progress.getBoundingClientRect()
    if (!rect.width || !isFinite(audio.duration) || audio.duration <= 0) return

    const percent = Math.min(1, Math.max(0, (clientX - rect.left) / rect.width))
    audio.currentTime = percent * audio.duration
    render()
  }

  progress.addEventListener('click', (e) => seekTo(e.clientX))

  progress.addEventListener('keydown', (e) => {
    if (!isFinite(audio.duration) || audio.duration <= 0) return

    if (e.key === 'ArrowRight') {
      audio.currentTime = Math.min(audio.duration, audio.currentTime + 10)
      e.preventDefault()
    } else if (e.key === 'ArrowLeft') {
      audio.currentTime = Math.max(0, audio.currentTime - 10)
      e.preventDefault()
    } else if (e.key === ' ' || e.key === 'Enter') {
      audio.paused ? play() : audio.pause()
      e.preventDefault()
    }
  })

  const selectEpisode = (button) => {
    const url = button.getAttribute('data-audio-url')
    if (!url) return

    if (audio.getAttribute('src') !== url) {
      audio.setAttribute('src', url)

      if (art) art.src = button.getAttribute('data-episode-image') || art.src
      if (titleEl) titleEl.textContent = button.getAttribute('data-episode-title') || ''
      if (dateEl) dateEl.textContent = button.getAttribute('data-episode-published-date') || ''
      if (authorEl) authorEl.textContent = button.getAttribute('data-episode-author') || ''
      if (durationEl) durationEl.textContent = button.getAttribute('data-episode-duration') || ''

      bar.style.width = '0%'
      elapsed.textContent = '00:00'
      play()
    } else {
      audio.paused ? play() : audio.pause()
    }

    const items = document.querySelectorAll('.styles__episodeFeedItem___1U6E2')
    for (let i = 0; i < items.length; i++) {
      items[i].classList.remove('jesaeip-selected-episode')
    }

    const item = button.closest('.styles__episodeFeedItem___1U6E2')
    if (item) item.classList.add('jesaeip-selected-episode')
  }

  // Delegated, so it keeps working if the list is re-rendered later.
  document.addEventListener('click', (e) => {
    if (!e.target || !e.target.closest) return

    const button = e.target.closest('.jesaei-episode-play-btn')
    if (!button) return

    e.preventDefault()
    selectEpisode(button)
  })
}
