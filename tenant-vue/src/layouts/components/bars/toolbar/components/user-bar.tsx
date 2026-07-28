/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import { useI18n } from 'vue-i18n'
import Message from 'vue-m-message'
import { clearFounderSession, clearTenantContext, getTenantId, getTenantName } from '@/utils/tenantContext.ts'
import MineShortcutsDesc from './dropdownMenuComponents/shortcuts-desc.tsx'
import MineSystemInfo from './dropdownMenuComponents/system-info.tsx'

export default defineComponent({
  name: 'UserBar',
  setup() {
    const userStore = useUserStore()
    const router = useRouter()
    const userInfo = computed(() => userStore.getUserInfo())
    const { t } = useI18n()
    const inTenantContext = computed(() => !!getTenantId())
    const tenantName = computed(() => getTenantName())

    const links = computed(() => {
      const items: any[] = [
        {
          label: 'mineAdmin.userBar.uc',
          icon: 'material-symbols:account-circle-outline',
          handle: () => router.push({ path: '/uc' }),
        },
        {
          label: 'mineAdmin.userBar.clearCache',
          icon: 'mingcute:broom-line',
          handle: async () => {
            await userStore.clearCache()
            Message.success(t('mineAdmin.common.clearCache'))
          },
        },
        { label: 'divider' },
        {
          label: 'mineAdmin.userBar.shortcuts',
          icon: 'i-material-symbols:keyboard-keys',
          handle: () => userStore.setDropdownMenuState('shortcuts', true),
        },
      ]
      // 业务租户后台不展示「系统信息」
      if (!inTenantContext.value) {
        items.push({
          label: 'mineAdmin.userBar.systemInfo',
          icon: 'i-bi:info-circle',
          handle: () => userStore.setDropdownMenuState('systemInfo', true),
        })
      }
      items.push({ label: 'divider' })
      // 与「进入租户」对称：清租户上下文并恢复创始人会话
      if (inTenantContext.value) {
        items.push({
          labelText: '返回创始人',
          icon: 'material-symbols:home-outline-rounded',
          handle: () => {
            clearTenantContext()
            if (userStore.restoreFounderSession()) {
              useTabStore().clearTab()
              window.location.assign('/')
              return
            }
            clearFounderSession()
            userStore.logout('/')
          },
        })
      }
      items.push({
        label: 'mineAdmin.userBar.logout',
        icon: 'hugeicons:logout-04',
        handle: () => userStore.logout(),
      })
      return items
    })

    return () => {
      const avatar = userInfo.value?.avatar
      const username = userInfo.value?.username ?? ''
      const tenantHint = inTenantContext.value
        ? (tenantName.value ? ` · ${tenantName.value}` : ` · #${getTenantId()}`)
        : ''

      return (
        <div class="mine-user-bar">
          <m-dropdown
            class="min-w-[6rem] p-1"
            v-slots={{
              default: () => (
                <div class="mine-userinfo">
                  {avatar && <img src={avatar} alt={username} class="mine-img-avatar" />}
                  {!avatar && <div class="mine-text-avatar">{username[0]?.toUpperCase() ?? ''}</div>}
                  <a class="username hidden lg:flex" title={inTenantContext.value ? '当前为业务租户后台' : undefined}>
                    {username}
                    {tenantHint && <span class="ml-1 opacity-70 text-xs font-normal">{tenantHint}</span>}
                    <ma-svg-icon name="material-symbols:keyboard-arrow-down-rounded" className="icon" size={20} />
                  </a>
                </div>
              ),
              popper: () => (
                <div>
                  {links.value.map((item: any) => (
                    <div>
                      {item.label !== 'divider' && (
                        <m-dropdown-item
                          type="default"
                          handle={item.handle}
                          v-slots={{
                            'default': () => (
                              <span>{item.labelText ?? useTrans(item.label)}</span>
                            ),
                            'prefix-icon': () => <ma-svg-icon name={item.icon} size={18} />,
                          }}
                        />
                      )}
                      {item.label === 'divider' && <m-dropdown-divider />}
                    </div>
                  ),
                  )}
                </div>
              ),
            }}
          />

          {!inTenantContext.value && <MineSystemInfo />}
          <MineShortcutsDesc />
        </div>
      )
    }
  },
})
