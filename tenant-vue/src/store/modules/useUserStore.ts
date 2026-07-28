/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import useCache from '@/hooks/useCache.ts'
import type { ResponseStruct } from '#/global'
import useThemeColor from '@/hooks/useThemeColor.ts'
import useHttp from '@/hooks/auto-imports/useHttp.ts'
import * as PermissionApi from '~/base/api/permission.ts'
import type { MenuVo, RoleVo } from '~/base/api/permission.ts'
import type { CurrentUserDepartmentVo, CurrentUserInfo, CurrentUserPositionVo, CurrentUserRoleVo } from '~/base/api/user.ts'
import { recursionGetKey } from '@/utils/recursionGetKey.ts'
import { applySessionWatch, stopSessionWatch } from '@/utils/sessionWatch.ts'
import {
  clearFounderSession,
  stashFounderSession,
  takeFounderSession,
} from '@/utils/tenantContext.ts'
import { globalComposer } from '@/i18n'

export interface LoginParams {
  username: string
  password: string
}

export interface LoginResult {
  access_token: string
  expire_at: number
  refresh_token: string
}

export type UserDepartmentInfo = CurrentUserDepartmentVo
export type UserPositionInfo = CurrentUserPositionVo
export type UserRoleInfo = CurrentUserRoleVo
export type UserInfo = CurrentUserInfo

function getInfo(): Promise<ResponseStruct<UserInfo>> {
  return useHttp().get('/admin/passport/getInfo')
}

/**
 * Passport login
 * @param data
 */
function loginApi(data: LoginParams): Promise<ResponseStruct<LoginResult>> {
  return useHttp().post('/admin/passport/login', data)
}

function memberLoginApi(data: LoginParams): Promise<ResponseStruct<LoginResult & { auth?: { login_time_limit?: number } }>> {
  return useHttp().post('/api/v1/login', data)
}

const useUserStore = defineStore(
  'useUserStore',
  () => {
    const cache = useCache()
    const router = useRouter()
    const setting = useSettingStore()
    const token = ref<string | null>(cache.get('token', null))
    const locales = ref<any[]>([])
    const language = ref(cache.get('language', 'zh_CN'))
    const isLogin = computed(() => !!token.value)
    const userInfo = ref<UserInfo | null>(null)
    const menu = ref<MenuVo[]>([])
    const permissions = ref<string[]>([])
    const roles = ref<string[]>([])
    const dropdownMenuState = ref<{
      shortcuts: boolean
      systemInfo: boolean
    }>({
      shortcuts: false,
      systemInfo: false,
    })

    function getDropdownMenu() {
      return dropdownMenuState.value
    }

    function setDropdownMenuState(key: string, state: boolean) {
      if (dropdownMenuState.value[key] !== undefined) {
        dropdownMenuState.value[key] = state
      }
    }

    function getMenu() {
      return menu.value
    }

    function setMenu(list: MenuVo[]) {
      menu.value = list
    }

    function getDropdownMenuState(key: string) {
      return dropdownMenuState.value[key] !== undefined ? dropdownMenuState.value[key] : undefined
    }

    async function refreshRole() {
      const res = await PermissionApi.getRoles()
      setRoles(res.data)
    }

    async function refreshMenu() {
      const res = await PermissionApi.getMenus()
      setMenu(res.data)
    }

    async function login(data: { username: string, password: string, code: string, [key: string]: any }) {
      await usePluginStore().callHooks('loginBefore', data)
      return new Promise((resolve, reject) => {
        loginApi(data).then(async (res) => {
          applyAdminSession(res.data)
          const site = (res.data as any)?.site || {}
          applySessionWatch({
            auto_logout: Number(site.auto_logout) || 0,
            login_time_limit: Number(site.login_time_limit) || 0,
          })
          await usePluginStore().callHooks('login', { username: data.username, ...res.data })
          resolve(res.data)
        }).catch((error) => {
          reject(error)
        })
      })
    }

    /** 写入管理员会话（登录 / 创始人免密进租户） */
    function applyAdminSession(data: LoginResult) {
      token.value = data.access_token
      cache.set('token', data.access_token)
      cache.set('expire', useDayjs().unix() + data.expire_at, { exp: data.expire_at })
      cache.set('refresh_token', data.refresh_token)
      cache.set('auth_realm', 'admin')
    }

    /** 进入租户前暂存当前创始人 token，返回时可恢复 */
    function stashCurrentAsFounderSession() {
      const access = cache.get('token')
      const refresh = cache.get('refresh_token')
      const expire = Number(cache.get('expire') || 0)
      if (!access || !refresh) {
        return
      }
      stashFounderSession({
        access_token: String(access),
        refresh_token: String(refresh),
        expire,
      })
    }

    /** 恢复创始人会话；成功返回 true */
    function restoreFounderSession(): boolean {
      const stash = takeFounderSession()
      if (!stash?.access_token || !stash.refresh_token) {
        return false
      }
      const remain = Math.max(0, Number(stash.expire) - useDayjs().unix())
      if (remain <= 0) {
        return false
      }
      applyAdminSession({
        access_token: stash.access_token,
        refresh_token: stash.refresh_token,
        expire_at: remain,
      })
      return true
    }

    /** Tenant member login via /api/v1 (USER type). */
    async function memberLogin(data: { username: string, password: string }) {
      const res = await memberLoginApi(data)
      token.value = res.data.access_token
      cache.set('token', res.data.access_token)
      cache.set('expire', useDayjs().unix() + res.data.expire_at, { exp: res.data.expire_at })
      cache.set('refresh_token', res.data.refresh_token)
      cache.set('auth_realm', 'member')
      applySessionWatch({
        auto_logout: 0,
        login_time_limit: Number((res.data as any)?.auth?.login_time_limit) || 0,
      })
      try {
        const info = await getInfo()
        setUserInfo(info.data)
      }
      catch {
        setUserInfo({
          id: 0,
          username: data.username,
          nickname: data.username,
        } as any)
      }
      return res.data
    }
    async function requestUserInfo(): Promise<void> {
      try {
        if (cache.get('auth_realm', 'admin') === 'member') {
          const { data } = await getInfo()
          setUserInfo(data)
          await usePluginStore().callHooks('getUserInfo', data)
          return
        }
        const routeStore = useRouteStore()
        const { data } = await getInfo()
        setUserInfo(data)
        if ((setting.getSettings('app')?.loadUserSetting ?? true) && data.backend_setting) {
          const raw = data?.backend_setting
          const normalized = raw && !Array.isArray(raw) ? raw : null
          await setUserSetting(normalized)
        }
        await refreshMenu()
        await refreshRole()
        await routeStore.initRoutes(router, getMenu())
        const codes: string[] = recursionGetKey(getMenu(), 'name')
        getRoles().includes('SuperAdmin') && codes.unshift('*')
        setPermissions(codes)
        await usePluginStore().callHooks('getUserInfo', data)
      }
      // eslint-disable-next-line unused-imports/no-unused-vars
      catch (e) {
        await logout()
      }
    }

    let loggingOut = false

    async function logout(redirect = router.currentRoute.value.fullPath) {
      if (loggingOut) {
        return
      }
      loggingOut = true
      try {
        stopSessionWatch()
        const realm = cache.get('auth_realm', 'admin')
        try {
          await usePluginStore().callHooks('logout')
        }
        catch {
          // ignore plugin logout errors
        }
        useTabStore().clearTab()
        clearInfo()
        const loginName = realm === 'member' ? 'login' : 'admin'
        const currentName = router.currentRoute.value.name
        if (currentName !== loginName) {
          await router.replace({
            name: loginName,
            query: redirect && redirect !== '/' ? { redirect: String(redirect) } : undefined,
          })
        }
      }
      finally {
        loggingOut = false
      }
    }

    function setLanguage(langName: string) {
      if (!langName || typeof langName !== 'string' || !langName.trim()) {
        return false
      }
      language.value = langName.trim()
      cache.set('language', language.value)
      if (globalComposer?.locale) {
        globalComposer.locale.value = language.value as any
      }
      return true
    }

    function getLanguage() {
      return language.value?.trim?.() || 'zh_CN'
    }

    function getLocales(): any[] {
      return locales.value
    }

    function setLocales(localeArray: any[]): boolean {
      locales.value = localeArray
      return true
    }

    function getUserInfo(): UserInfo | null {
      return userInfo.value
    }

    function setUserInfo(data: UserInfo): boolean {
      userInfo.value = data
      return true
    }

    function getUserDepartments(): UserDepartmentInfo[] {
      return userInfo.value?.departments ?? []
    }

    function getUserPositions(): UserPositionInfo[] {
      return userInfo.value?.positions ?? []
    }

    function getUserRoleList(): UserRoleInfo[] {
      return userInfo.value?.roles ?? []
    }

    function getPermissions(): string[] {
      return permissions.value
    }

    function setPermissions(permissionArray: string[]): boolean {
      permissions.value = permissionArray
      return true
    }

    function getRoles(): string[] {
      return roles.value
    }

    function setRoles(roleArray: RoleVo[]): boolean {
      roles.value = roleArray.map(item => item.code) as string[]
      return true
    }

    async function setUserSetting(settings: any) {
      settings && setting.setSettings(settings)
      setting.initColorMode()

      await nextTick()
      useThemeColor().initThemeColor()
      const cacheLanguage = cache.get('language', '')?.trim?.() || ''
      const settingsLanguage = settings?.app?.useLocale?.trim?.() || ''
      const locale = cacheLanguage || settingsLanguage || 'zh_CN'
      const appSettings = setting.getSettings('app')
      if (appSettings) {
        appSettings.useLocale = locale
      }
      setLanguage(locale)
    }

    function saveSettingToSever() {
      const backend_setting = setting.getSettings()
      return useHttp().post('/admin/permission/update', { backend_setting }).then(() => {
        cache.set('sys_settings', backend_setting)
      }).catch((error) => {
        console.log(error)
        return Promise.reject(error)
      })
    }

    async function clearCache() {
      // await useHttp().post('/mock/system/clearCache')
    }

    function clearInfo() {
      cache.remove('token')
      cache.remove('refresh_token')
      cache.remove('expire')
      cache.remove('auth_realm')
      clearFounderSession()
      token.value = null
      // 保留语言偏好，避免退出后 Element Plus / i18n locale 失效
      userInfo.value = null
      permissions.value = []
      roles.value = []
    }

    return {
      token,
      isLogin,
      login,
      memberLogin,
      applyAdminSession,
      stashCurrentAsFounderSession,
      restoreFounderSession,
      logout,
      getDropdownMenu,
      getDropdownMenuState,
      setDropdownMenuState,
      clearCache,
      setLanguage,
      getLanguage,
      requestUserInfo,
      getUserInfo,
      getUserDepartments,
      getUserPositions,
      getUserRoleList,
      setPermissions,
      getPermissions,
      getRoles,
      getLocales,
      setLocales,
      saveSettingToSever,
      getMenu,
    }
  },
)

export default useUserStore
