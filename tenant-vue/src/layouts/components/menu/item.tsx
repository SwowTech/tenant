import type { PropType } from 'vue'
import { rootMenuInjectionKey } from './types'
import { isFunction } from 'radash'
import '@/layouts/style/menu.scss'

import type { MineRoute } from '#/global'
import { resolveMenuTitle } from '@/utils/resolveMenuTitle'

function isVisibleChild(item: MineRoute.routeRecord) {
  const meta = item.meta as (MineRoute.RouteMeta & { menu?: boolean }) | undefined
  if (meta?.type === 'B' || meta?.menu === false) {
    return false
  }
  const hidden = meta?.hidden as unknown
  const isHidden = hidden === true || hidden === 1 || hidden === '1'
  return !isHidden || meta?.subForceShow === true
}

/** 当前路由是否落在该菜单项自身或其子孙下 */
function isRouteUnderMenu(menuItem: MineRoute.routeRecord, route: { path: string, name?: unknown, meta?: any }): boolean {
  const path = menuItem.path || ''
  const name = menuItem.name

  if (name && (route.name === name || route.meta?.activeName === name)) {
    return true
  }
  if (path) {
    if (route.path === path) {
      return true
    }
    // 避免 `/` 或空路径前缀误匹配；欢迎 `/welcome` 不会命中 `/setting/...`
    if (path !== '/' && route.path.startsWith(`${path}/`)) {
      return true
    }
  }

  return (menuItem.children || []).some((child) => {
    if (!isVisibleChild(child)) {
      return false
    }
    return isRouteUnderMenu(child, route)
  })
}

export default defineComponent({
  name: 'MenuItem',
  inheritAttrs: false,
  props: {
    uniqueKey: Array as PropType<string[]>,
    item: Object as PropType<MineRoute.routeRecord>,
    level: { type: Number, default: 0 },
    subMenu: { type: Boolean, default: false },
    expand: { type: Boolean, default: false },
  },
  setup(props, { expose, attrs }) {
    const t = useTrans().globalTrans
    const rootMenu = inject(rootMenuInjectionKey)!
    const itemRef = ref<HTMLElement>()
    const route = useRoute()

    function handleItemClick(e: MouseEvent) {
      const onClick = attrs.onClick as ((evt: MouseEvent) => void) | undefined
      onClick?.(e)
    }

    const isCollapseVisual = computed(() => {
      return !!rootMenu.props.collapse && rootMenu.props.mode === 'vertical' && props.level === 0
    })

    const showCollapseLabel = computed(() => {
      return isCollapseVisual.value && !!rootMenu.props.showCollapseName
    })

    /** 折叠主栏：只按路由树判断，欢迎仅在 /welcome 选中，父级在子孙页选中 */
    const isCollapseRailActive = computed(() => {
      if (!isCollapseVisual.value || !props.item) {
        return false
      }
      return isRouteUnderMenu(props.item, route)
    })

    const isActive = computed(() => {
      const key = props.uniqueKey?.at(-1) || ''
      if (props.subMenu) {
        return !!rootMenu.subMenus[key]?.active
      }
      if (!props.item) {
        return false
      }
      return isRouteUnderMenu(props.item, route)
    })

    const parentActive = computed(() => {
      if (!props.subMenu || !props.item) {
        return false
      }
      const breadcrumbs = Array.isArray(route?.meta?.breadcrumb) ? route.meta.breadcrumb : []
      if (!breadcrumbs.length) {
        return false
      }
      return breadcrumbs.some((breadcrumb: any) => breadcrumb.name === props.item!.name)
    })

    const isItemActive = computed(() => {
      // 折叠主栏统一走路由树判断（含有子级的一级菜单）
      if (isCollapseVisual.value) {
        return isCollapseRailActive.value
      }
      return isActive.value && (!props.subMenu || rootMenu.isMenuPopup)
    })

    const getString = (key: any) => {
      return isFunction(key) ? key() : key
    }

    const fullTitle = computed(() => {
      return resolveMenuTitle({
        name: props.item?.name,
        meta: {
          i18n: getString(props.item?.meta?.i18n),
          title: getString(props.item?.meta?.title),
        },
      }, t)
    })

    const collapseTitle = computed(() => {
      const title = fullTitle.value || ''
      return Array.from(title).slice(0, 2).join('')
    })

    const indentStyle = computed(() => {
      if (isCollapseVisual.value || rootMenu.isMenuPopup) {
        return ''
      }
      return `padding-left: ${15 * (props.level ?? 0)}px`
    })

    const arrowIcon = computed(() => {
      return {
        'relative ml-1 w-[10px] after:(absolute h-[1.5px] w-[6px] bg-current transition-transform-200 content-empty -translate-y-[1px]) before:(absolute h-[1.5px] w-[6px] bg-current transition-transform-200 content-empty -translate-y-[1px])': true,
        'before:(-rotate-45 -translate-x-[2px]) after:(rotate-45 translate-x-[2px])': props.expand,
        'before:(rotate-45 -translate-x-[2px]) after:(-rotate-45 translate-x-[2px])': !props.expand,
        'opacity-0': isCollapseVisual.value,
        '-rotate-90 -top-[1.5px]': rootMenu.isMenuPopup && props.level !== 0,
      }
    })

    expose({ ref: itemRef })

    return () => {
      const item = props.item
      if (!item) {
        return null
      }
      const hidden = item.meta?.hidden
      const visible = (hidden !== true || hidden === undefined) || item.meta?.subForceShow === true
      if (!visible) {
        return null
      }

      const uniqueKey = props.uniqueKey
      const subMenu = props.subMenu
      const level = props.level

      return (
        <div
          ref={itemRef}
          class={{ 'mine-menu-item': true, 'active': isItemActive.value }}
          onMouseenter={attrs.onMouseenter as any}
          onMouseleave={attrs.onMouseleave as any}
        >
          <router-link custom={true} to={uniqueKey?.at(-1) || item.path || '/'}>
            {({ href, navigate }) => (
              <>
                <m-tooltip
                  enable={isCollapseVisual.value && !subMenu && !rootMenu.props.showCollapseName}
                  text={fullTitle.value}
                  placement="right"
                  class="h-full w-full"
                >
                  {h(
                    subMenu ? 'div' : 'a',
                    {
                      class: {
                        'mine-menu-link': true,
                        'mine-menu-link--collapse-label': showCollapseLabel.value,
                        'active': isItemActive.value,
                        'parentActive': !!subMenu && !isCollapseVisual.value && (route?.meta?.activeName === item.name || parentActive.value),
                        'no-underline': !subMenu,
                        'cursor-pointer': !!subMenu,
                      },
                      title: fullTitle.value,
                      onClick: (e: MouseEvent) => {
                        if (subMenu) {
                          handleItemClick(e)
                          return
                        }
                        handleItemClick(e)
                        if (item?.meta?.type !== 'L') {
                          navigate(e)
                        }
                      },
                      ...(!subMenu && {
                        href: item?.meta?.type === 'L' ? item.meta.link : href,
                        target: item?.meta?.type === 'L' ? '_blank' : '_self',
                      }),
                    },
                    (
                      <>
                        <div
                          class={{
                            'mine-menu-link-left': true,
                            'mine-menu-link-left--collapse-label': showCollapseLabel.value,
                          }}
                          style={unref(indentStyle)}
                        >
                          {item?.meta?.icon && <ma-svg-icon name={item.meta.icon} size={20} class="mine-menu-icon" async />}
                          {
                            !(isCollapseVisual.value && !rootMenu.props.showCollapseName)
                            && (
                              <span
                                class={{
                                  'title transition-height transition-opacity transition-width': true,
                                  'opacity-0 w-0 h-0': isCollapseVisual.value && !rootMenu.props.showCollapseName,
                                  'w-full text-center text-xs leading-tight': showCollapseLabel.value,
                                }}
                              >
                                {showCollapseLabel.value ? collapseTitle.value : fullTitle.value}
                              </span>
                            )
                          }
                        </div>
                        <div
                          class={{
                            'mine-menu-badge': true,
                            'absolute right-10': (subMenu && !(rootMenu.isMenuPopup && level === 0)),
                            'hidden': isCollapseVisual.value || item.meta?.badge === undefined || !item.meta?.badge?.() || rootMenu.isMenuPopup,
                          }}
                        >
                          {item.meta?.badge?.()}
                        </div>
                        {
                          (subMenu && !isCollapseVisual.value && !(rootMenu.isMenuPopup && level === 0))
                          && <i class={arrowIcon.value} />
                        }
                      </>
                    ),
                  )}
                </m-tooltip>
              </>
            )}
          </router-link>
        </div>
      )
    }
  },
})
