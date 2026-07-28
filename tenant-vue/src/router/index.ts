/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import type { RouteRecordRaw } from 'vue-router'
import { useNProgress } from '@vueuse/integrations/useNProgress'
import { createRouter, createWebHashHistory, createWebHistory } from 'vue-router'
import routes from './static-routes/rootRoute.ts'
import '@/assets/styles/nprogress.scss'
import hasAuth from '@/utils/permission/hasAuth.ts'
import hasRole from '@/utils/permission/hasRole.ts'
import hasUser from '@/utils/permission/hasUser.ts'
import { isEmpty } from 'radash'
import useCache from '@/hooks/useCache.ts'

const { isLoading } = useNProgress()

const router = createRouter({
  history: import.meta.env.VITE_APP_ROUTE_MODE === 'history' ? createWebHistory() : createWebHashHistory(),
  routes: routes as RouteRecordRaw[],
})

router.beforeEach(async (to, from, next) => {
  const settingStore = useSettingStore()
  const userStore = useUserStore()
  const cache = useCache()
  isLoading.value = true

  const isAuthPage = to.name === 'login' || to.name === 'admin'
    || to.path === '/login' || to.path === '/admin'

  if (userStore.isLogin) {
    const realm = cache.get('auth_realm', 'admin')
    if (to.name === 'admin' || to.path === '/admin') {
      next({
        path: realm === 'member' ? '/uc' : settingStore.getSettings('welcomePage').path,
        replace: true,
      })
      return
    }
    if (to.name === 'login' || to.path === '/login') {
      next({ path: realm === 'member' ? '/uc' : settingStore.getSettings('welcomePage').path, replace: true })
      return
    }
    // Member tokens should stay in /uc
    if (realm === 'member' && !to.path.startsWith('/uc') && to.name !== 'MineSystemError') {
      next({ path: '/uc', replace: true })
      return
    }
    if (userStore.getUserInfo() === null) {
      await userStore.requestUserInfo()
      // logout inside requestUserInfo may have cleared the session
      if (!userStore.isLogin) {
        next({ name: 'admin', replace: true })
        return
      }
      next({ path: to.fullPath, query: to.query })
    }
    else {
      next()
    }
  }
  else {
    const white = settingStore.getSettings('app').whiteRoute || []
    if (isAuthPage || white.includes(to.name as string)) {
      next()
      return
    }
    const toUc = to.path === '/uc' || to.path.startsWith('/uc/')
    next({
      name: toUc ? 'login' : 'admin',
      query: { redirect: to.fullPath },
      replace: true,
    })
  }
})

router.afterEach(async (to) => {
  isLoading.value = false
  const keepAliveStore = useKeepAliveStore()
  const iframeKeepAliveStore = useIframeKeepAliveStore()

  if (!isEmpty(to.meta.auth) && !hasAuth(to.meta.auth as string[])) {
    await router.push({ path: '/403' })
    return
  }

  if (!isEmpty(to.meta.role) && !hasRole(to.meta.role as string[])) {
    await router.push({ path: '/403' })
    return
  }

  if (!isEmpty(to.meta.user) && !hasUser(to.meta.user as string[])) {
    await router.push({ path: '/403' })
    return
  }

  if (to.meta.cache && to.meta.type !== 'I') {
    const componentName = to.matched.at(-1)?.components?.default!.name
    if (componentName) {
      keepAliveStore.add(componentName)
    }
    else {
      console.warn(`MineAdmin-UI：[${to.meta.title}] 组件页面未设置组件名，将不会被缓存`)
    }
  }

  if (to.meta.type === 'I' && typeof to.name === 'string') {
    iframeKeepAliveStore.add(to.name)
  }
})

export default router
