import useUserStore from '@/store/modules/useUserStore.ts'
import useCache from '@/hooks/useCache.ts'

let idleTimer: ReturnType<typeof setTimeout> | null = null
let absoluteTimer: ReturnType<typeof setTimeout> | null = null
let listenersBound = false

function clearTimers() {
  if (idleTimer) {
    clearTimeout(idleTimer)
    idleTimer = null
  }
  if (absoluteTimer) {
    clearTimeout(absoluteTimer)
    absoluteTimer = null
  }
}

function bumpIdle(minutes: number) {
  if (idleTimer) {
    clearTimeout(idleTimer)
  }
  if (minutes <= 0) {
    return
  }
  idleTimer = setTimeout(() => {
    const store = useUserStore()
    if (store.isLogin) {
      store.logout()
    }
  }, minutes * 60 * 1000)
}

/**
 * Apply site auto_logout (idle) and login_time_limit (absolute session).
 */
export function applySessionWatch(opts: { auto_logout?: number, login_time_limit?: number }) {
  const cache = useCache()
  const autoLogout = Math.max(0, Number(opts.auto_logout) || 0)
  const loginLimit = Math.max(0, Number(opts.login_time_limit) || 0)

  cache.set('session_auto_logout', autoLogout)
  cache.set('session_login_limit', loginLimit)
  if (loginLimit > 0) {
    cache.set('session_login_at', useDayjs().unix())
  }

  clearTimers()

  if (loginLimit > 0) {
    absoluteTimer = setTimeout(() => {
      const store = useUserStore()
      if (store.isLogin) {
        store.logout()
      }
    }, loginLimit * 60 * 1000)
  }

  const onActivity = () => bumpIdle(autoLogout)
  if (autoLogout > 0) {
    bumpIdle(autoLogout)
    if (!listenersBound) {
      ;['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach((evt) => {
        window.addEventListener(evt, onActivity, { passive: true })
      })
      listenersBound = true
      ;(window as any).__sessionWatchActivity = onActivity
    }
  }
}

export function stopSessionWatch() {
  clearTimers()
  const onActivity = (window as any).__sessionWatchActivity as (() => void) | undefined
  if (onActivity && listenersBound) {
    ;['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach((evt) => {
      window.removeEventListener(evt, onActivity)
    })
    listenersBound = false
    delete (window as any).__sessionWatchActivity
  }
}
