/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import { Transition } from 'vue'
import Logo from '../logo'
import '@/layouts/style/sub-aside.scss'
import '@/layouts/style/menu.scss'
import MineMenu from '@/layouts/components/menu'
import type { MineRoute } from '#/global'
import { resolveMenuTitle } from '@/utils/resolveMenuTitle'

export default defineComponent({
  name: 'SubAside',
  setup() {
    const shadowTop = ref<boolean>(false)
    const shadowBottom = ref<boolean>(false)
    const subAsideRef = ref<HTMLElement | null>()
    const menuStore = useMenuStore()
    const route = useRoute()
    const {
      getSettings,
      toggleCollapseButton,
      toggleFixedSubAsideButton,
      getMenuCollapseState,
      getFixedAsideState,
      setSubAsideWidth,
      isColumnsLayout,
      isMixedLayout,
      showMineHeader,
      showMineSubAside,
      getMobileState,
      setMobileSubmenuState,
      getMobileSubmenuState,
    } = useSettingStore()

    const collapseAsideMenu = ref<MineRoute.routeRecord | null>(null)

    const collapseSubMenuMode = computed(() => {
      return getSettings('subAside').collapseSubMenuMode || 'popup'
    })

    const showAsidePanel = computed(() => {
      return getMenuCollapseState()
        && collapseSubMenuMode.value === 'aside'
        && !!collapseAsideMenu.value
        && !getMobileState()
    })

    function syncAsideWidth() {
      if (getMobileState()) {
        return
      }
      if (!getMenuCollapseState()) {
        setSubAsideWidth('var(--mine-g-sub-aside-width)')
        return
      }
      if (showAsidePanel.value) {
        setSubAsideWidth('calc(var(--mine-g-sub-aside-collapse-width) + var(--mine-g-sub-aside-width))')
      }
      else {
        setSubAsideWidth('var(--mine-g-sub-aside-collapse-width)')
      }
    }

    watch(
      () => [getMenuCollapseState(), showAsidePanel.value, collapseSubMenuMode.value] as const,
      () => {
        nextTick(() => syncAsideWidth())
      },
      { immediate: true },
    )

    function findMenuByName(menus: MineRoute.routeRecord[], name?: string | symbol): MineRoute.routeRecord | null {
      if (!name) {
        return null
      }
      for (const item of menus) {
        if (item.name === name) {
          return item
        }
        const found = findMenuByName(item.children || [], name)
        if (found) {
          return found
        }
      }
      return null
    }

    function onCollapseAsideSelect(menu: MineRoute.routeRecord | null) {
      if (!menu) {
        collapseAsideMenu.value = null
      }
      else {
        // 以 store 中的菜单为准，避免传入引用 children 为空导致右侧空白
        collapseAsideMenu.value = findMenuByName(menuStore.allMenu, menu.name as string) || menu
      }
      // 同步拉宽，避免面板渲染后仍被折叠宽度 + overflow 裁切
      if (getMobileState()) {
        return
      }
      if (getMenuCollapseState() && collapseSubMenuMode.value === 'aside' && collapseAsideMenu.value) {
        setSubAsideWidth('calc(var(--mine-g-sub-aside-collapse-width) + var(--mine-g-sub-aside-width))')
      }
      else if (getMenuCollapseState()) {
        setSubAsideWidth('var(--mine-g-sub-aside-collapse-width)')
      }
    }

    function displayTitle(item: MineRoute.routeRecord) {
      const t = useTrans().globalTrans
      return resolveMenuTitle(item, key => t(key) as string)
    }

    function isVisibleMenuItem(item: MineRoute.routeRecord) {
      const meta = item.meta as (MineRoute.RouteMeta & { menu?: boolean }) | undefined
      if (meta?.type === 'B' || meta?.menu === false) {
        return false
      }
      const hidden = meta?.hidden as unknown
      const isHidden = hidden === true || hidden === 1 || hidden === '1'
      return !isHidden || meta?.subForceShow === true
    }

    function visibleChildren(item: MineRoute.routeRecord) {
      return (item.children || []).filter(child => isVisibleMenuItem(child))
    }

    const asidePanelItems = computed(() => {
      return collapseAsideMenu.value ? visibleChildren(collapseAsideMenu.value) : []
    })

    const asidePanelOpenKeys = ref<string[]>([])

    function isAsideLeafActive(item: MineRoute.routeRecord) {
      const path = item.path || ''
      if (route.meta?.activeName && route.meta.activeName === item.name) {
        return true
      }
      if (!path) {
        return false
      }
      // 仅精确匹配，避免 /setting 前缀把整组二级都标成选中
      return route.path === path
    }

    function groupContainsActive(item: MineRoute.routeRecord): boolean {
      return visibleChildren(item).some((child) => {
        if (visibleChildren(child).length > 0) {
          return groupContainsActive(child)
        }
        return isAsideLeafActive(child)
      })
    }

    watch(
      () => [collapseAsideMenu.value?.name, route.path, route.meta?.activeName] as const,
      () => {
        // 只展开包含当前页的分组，不要把所有二级都当成选中/展开
        const keys: string[] = []
        asidePanelItems.value.forEach((item) => {
          if (visibleChildren(item).length > 0 && groupContainsActive(item)) {
            keys.push(String(item.name || item.path))
          }
        })
        asidePanelOpenKeys.value = keys
      },
    )

    function toggleAsideGroup(key: string) {
      if (asidePanelOpenKeys.value.includes(key)) {
        asidePanelOpenKeys.value = asidePanelOpenKeys.value.filter(k => k !== key)
      }
      else {
        asidePanelOpenKeys.value = [...asidePanelOpenKeys.value, key]
      }
    }

    function renderAsidePanelItems(items: MineRoute.routeRecord[], level = 0): any[] {
      return items.map((item) => {
        const key = String(item.name || item.path || JSON.stringify(item))
        const children = visibleChildren(item)
        if (children.length > 0) {
          const opened = asidePanelOpenKeys.value.includes(key)
          const groupActive = groupContainsActive(item)
          return (
            <div key={key} class="mine-collapse-aside-group">
              <div
                class={{
                  'mine-menu-link mine-collapse-aside-group__title': true,
                  // 展开 ≠ 选中；仅当前路由所在分组用 parentActive 轻提示
                  'parentActive': groupActive,
                }}
                style={level > 0 ? `padding-left: ${12 + level * 12}px` : undefined}
                onClick={() => toggleAsideGroup(key)}
              >
                <div class="mine-menu-link-left">
                  {item?.meta?.icon && <ma-svg-icon name={item.meta.icon} size={18} class="mine-menu-icon" async />}
                  <span class="title">{displayTitle(item)}</span>
                </div>
                <i
                  class={{
                    'relative ml-1 w-[10px] after:(absolute h-[1.5px] w-[6px] bg-current content-empty -translate-y-[1px]) before:(absolute h-[1.5px] w-[6px] bg-current content-empty -translate-y-[1px])': true,
                    'before:(-rotate-45 -translate-x-[2px]) after:(rotate-45 translate-x-[2px])': opened,
                    'before:(rotate-45 -translate-x-[2px]) after:(-rotate-45 translate-x-[2px])': !opened,
                  }}
                />
              </div>
              {opened && (
                <div class="mine-collapse-aside-group__children">
                  {renderAsidePanelItems(children, level + 1)}
                </div>
              )}
            </div>
          )
        }

        const to = item.meta?.type === 'L' ? (item.meta.link as string) : (item.path || '/')
        const isExternal = item.meta?.type === 'L'
        const leafActive = isAsideLeafActive(item)
        return (
          <div key={key} class="mine-menu-item">
            {isExternal
              ? (
                  <a
                    class={{
                      'mine-menu-link no-underline': true,
                      'active': leafActive,
                    }}
                    href={to}
                    target="_blank"
                    style={level > 0 ? `padding-left: ${12 + level * 12}px` : undefined}
                  >
                    <div class="mine-menu-link-left">
                      {item?.meta?.icon && <ma-svg-icon name={item.meta.icon} size={18} class="mine-menu-icon" async />}
                      <span class="title">{displayTitle(item)}</span>
                    </div>
                  </a>
                )
              : (
                  <router-link
                    custom={true}
                    to={to}
                  >
                    {({ href, navigate }: { href: string, navigate: (e?: MouseEvent) => void }) => (
                      <a
                        href={href}
                        class={{
                          'mine-menu-link no-underline': true,
                          'active': leafActive,
                        }}
                        style={level > 0 ? `padding-left: ${12 + level * 12}px` : undefined}
                        onClick={(e: MouseEvent) => navigate(e)}
                      >
                        <div class="mine-menu-link-left">
                          {item?.meta?.icon && <ma-svg-icon name={item.meta.icon} size={18} class="mine-menu-icon" async />}
                          <span class="title">{displayTitle(item)}</span>
                        </div>
                      </a>
                    )}
                  </router-link>
                )}
          </div>
        )
      })
    }

    function onSubAsideScroll() {
      const scrollTop = subAsideRef.value?.scrollTop ?? 0
      shadowTop.value = scrollTop > 0
      const clientHeight = subAsideRef.value?.clientHeight ?? 0
      const scrollHeight = subAsideRef.value?.scrollHeight ?? 0
      shadowBottom.value = Math.ceil(scrollTop + clientHeight) < scrollHeight
    }
    const asideListClass = computed(() => {
      return {
        'mine-sub-aside-list': true,
        'shadow-top': shadowTop.value,
        'shadow-bottom': shadowBottom.value,
      }
    })

    function onToggleCollapse() {
      if (getMenuCollapseState()) {
        collapseAsideMenu.value = null
      }
      toggleCollapseButton()
      nextTick(() => syncAsideWidth())
    }

    return () => {
      return (
        <Transition name="mine-sub-aside-container">
          <div
            class={{
              'mine-sub-aside': true,
              'mine-sub-aside--split': showAsidePanel.value,
              // columns 布局默认 w-0，宽度由 setSubAsideWidth 内联控制；折叠时也要能显示
              'w-0': (isColumnsLayout() || (isMixedLayout() && (!menuStore.activeTopMenu || menuStore.activeTopMenu?.children?.length === 0))) && !getMenuCollapseState(),
              'w-[var(--mine-g-sub-aside-width)]': !isColumnsLayout() && !getMenuCollapseState(),
              'w-[var(--mine-g-sub-aside-collapse-width)]': getMenuCollapseState() && !showAsidePanel.value && !getMobileState(),
              'mine-sub-aside--aside-open': showAsidePanel.value,
              '!absolute left-[var(--mine-g-main-aside-width)] !w-0': getFixedAsideState() && isColumnsLayout() && !getMenuCollapseState(),
              '!group-hover-w-[var(--mine-g-sub-aside-width)] group-hover-shadow-lg': getFixedAsideState() && isColumnsLayout() && menuStore.subMenu.length > 0 && !getMenuCollapseState(),
              '!absolute shadow-md': getMobileState(),
              '!w-0': getMobileState() && !getMobileSubmenuState(),
              '!w-[var(--mine-g-sub-aside-width)]': getMobileState() && getMobileSubmenuState(),
            }}
          >
            <div
              class={{
                'mine-sub-aside-rail': true,
                'mine-sub-aside-rail--collapse': getMenuCollapseState() && !getMobileState(),
              }}
            >
              {
                ((!showMineHeader() && showMineSubAside()) || (!getMenuCollapseState() && isColumnsLayout()) || getMobileState())
                && (
                  <Logo
                    showLogo={showMineSubAside() || getMenuCollapseState()}
                    showTitle={!getMenuCollapseState()}
                  />
                )
              }
              <div ref={subAsideRef} class={asideListClass.value} onScroll={onSubAsideScroll}>
                <MineMenu
                  menu={menuStore.allMenu}
                  value={route.path}
                  default-opens={['/']}
                  collapse={getMenuCollapseState()}
                  show-collapse-name={true}
                  collapse-sub-menu-mode={collapseSubMenuMode.value}
                  onCollapseAsideSelect={onCollapseAsideSelect}
                />
              </div>
              <div
                class={{
                  'flex items-center h-13': true,
                  'justify-center': getMenuCollapseState(),
                  'justify-end px-3': showMineSubAside() || getMobileState(),
                  'justify-between px-3': !getMenuCollapseState() && isColumnsLayout() && !getMobileState(),
                }}
              >
                <div
                  v-show={!getMenuCollapseState() && isColumnsLayout() && !getMobileState()}
                  class={{
                    'mine-sub-aside-fixed-button': true,
                  }}
                  onClick={toggleFixedSubAsideButton}
                >
                  <ma-svg-icon name={
                    getFixedAsideState()
                      ? 'material-symbols:filter-alt-off-outline-sharp'
                      : 'material-symbols:filter-alt-outline-sharp'
                  }
                  />
                </div>
                <div
                  v-show={getSettings('subAside').showCollapseButton && !getFixedAsideState() && !getMobileState()}
                  class={{
                    'mine-sub-aside-collapse-button relative px-4': true,
                    '-rotate-z-180': !getMenuCollapseState(),
                  }}
                  onClick={onToggleCollapse}
                >
                  <ma-svg-icon name="system-uicons:window-collapse-right" />
                </div>
                <div
                  v-show={getMobileState()}
                  class={{
                    'mine-sub-aside-close-button relative px-4': true,
                  }}
                  onClick={() => setMobileSubmenuState(false)}
                >
                  <ma-svg-icon name="material-symbols:close-rounded" />
                </div>
              </div>
            </div>

            {
              showAsidePanel.value && collapseAsideMenu.value
              && (
                <div class="mine-collapse-aside-panel">
                  <div class="mine-collapse-aside-panel__title">
                    {displayTitle(collapseAsideMenu.value)}
                  </div>
                  <div class="mine-collapse-aside-panel__list">
                    {asidePanelItems.value.length > 0
                      ? renderAsidePanelItems(asidePanelItems.value)
                      : (
                          <div class="px-4 py-6 text-xs text-gray-4">
                            暂无子菜单
                          </div>
                        )}
                  </div>
                </div>
              )
            }
          </div>
        </Transition>
      )
    }
  },
})
