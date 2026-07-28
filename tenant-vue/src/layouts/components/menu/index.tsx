/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import type { PropType } from 'vue'
import { TransitionGroup } from 'vue'
import type { MenuInjection, MenuProps } from './types'
import { rootMenuInjectionKey } from './types'
import SubMenu from './sub.tsx'
import MenuItem from './item.tsx'
import type { MineRoute } from '#/global'

export default defineComponent({
  name: 'MineMenu',
  props: {
    menu: Object as PropType<MineRoute.routeRecord[]>,
    value: String,
    accordion: { type: Boolean, default: true },
    defaultOpens: Array as PropType<string[]>,
    mode: { type: String as PropType<'horizontal' | 'vertical'>, default: 'vertical' },
    collapse: { type: Boolean, default: false },
    showCollapseName: { type: Boolean, default: false },
    collapseSubMenuMode: { type: String as PropType<'popup' | 'aside'>, default: 'popup' },
  },
  emits: ['collapseAsideSelect'],
  setup(props, { emit }) {
    const activeIndex = ref<MenuInjection['activeIndex']>(props.value as string)
    const items = ref<MenuInjection['items']>({})
    const subMenus = ref<MenuInjection['subMenus']>({})
    const openedMenus = ref<MenuInjection['openedMenus']>(props.defaultOpens?.slice(0) as string[])
    const mouseInMenu = ref<MenuInjection['mouseInMenu']>([])
    const collapseAsideIndex = ref('')
    const collapseAsideMenu = ref<MineRoute.routeRecord | null>(null)
    const router = useRouter()
    const isMenuPopup = computed<MenuInjection['isMenuPopup']>(() => {
      if (props.mode === 'horizontal') {
        return true
      }
      // 折叠 + 右侧列表模式：不走浮层弹出
      if (props.mode === 'vertical' && props.collapse && props.collapseSubMenuMode === 'aside') {
        return false
      }
      return props.mode === 'vertical' && props.collapse
    })

    function isVisibleMenuItem(item: MineRoute.routeRecord) {
      const meta = item.meta as (MineRoute.RouteMeta & { menu?: boolean }) | undefined
      if (meta?.type === 'B' || meta?.menu === false) {
        return false
      }
      const hidden = meta?.hidden as unknown
      const isHidden = hidden === true || hidden === 1 || hidden === '1'
      return !isHidden || meta?.subForceShow === true
    }

    function hasVisibleChildren(item: MineRoute.routeRecord) {
      return item.children?.some(child => isVisibleMenuItem(child)) ?? false
    }

    function notifyAsideSelect(menu: MineRoute.routeRecord | null) {
      emit('collapseAsideSelect', menu)
    }

    function menuIndex(item: MineRoute.routeRecord) {
      return item.path || String(item.name) || JSON.stringify(item)
    }

    function routeUnderMenu(menuItem: MineRoute.routeRecord, routePath: string): boolean {
      const path = menuItem.path || ''
      if (path && (routePath === path || (path !== '/' && routePath.startsWith(`${path}/`)))) {
        return true
      }
      return (menuItem.children || []).some((child) => {
        if (!isVisibleMenuItem(child)) {
          return false
        }
        return routeUnderMenu(child, routePath)
      })
    }

    function findAsideDefaultMenu(menus: MineRoute.routeRecord[]): MineRoute.routeRecord | null {
      const routePath = (props.value || activeIndex.value || '') as string
      if (!routePath) {
        return null
      }
      for (const item of menus) {
        if (!isVisibleMenuItem(item) || !hasVisibleChildren(item)) {
          continue
        }
        if (routeUnderMenu(item, routePath)) {
          return item
        }
      }
      return null
    }

    function syncAsideDefault() {
      if (!(props.collapse && props.collapseSubMenuMode === 'aside' && props.menu?.length)) {
        return
      }
      const next = findAsideDefaultMenu(props.menu)
      if (!next) {
        collapseAsideIndex.value = ''
        collapseAsideMenu.value = null
        notifyAsideSelect(null)
        return
      }
      const index = menuIndex(next)
      openedMenus.value = []
      collapseAsideIndex.value = index
      collapseAsideMenu.value = next
      setSubMenusActive(index)
      notifyAsideSelect(next)
    }

    // 解析传入的 menu 数据，并保存到 items 和 subMenus 对象中
    function initItems(menu: MenuProps['menu'], parentPaths: string[] = []) {
      menu.forEach((item) => {
        if (!isVisibleMenuItem(item)) {
          return
        }
        const index = item.path || String(item.name) || JSON.stringify(item)
        if (hasVisibleChildren(item)) {
          const indexPath = [index]
          if (parentPaths.length > 0) {
            indexPath.push(...parentPaths)
          }
          subMenus.value[index] = {
            index,
            indexPath,
            active: false,
          }
          initItems(item.children ?? [], indexPath)
        }
        else {
          const indexPath = [index, ...parentPaths]
          subMenus.value[index] = {
            index,
            indexPath,
            active: false,
          }
          items.value[index] = {
            index,
            indexPath,
          }
          if (item?.children && item?.children?.length > 0) {
            initItems(item.children, indexPath)
          }
        }
      })
    }

    const openMenu: MenuInjection['openMenu'] = (index, indexPath) => {
      if (openedMenus.value.includes(index)) {
        return
      }
      if (props.accordion) {
        openedMenus.value = indexPath
      }
      openedMenus.value.push(...indexPath)
    }
    const closeMenu: MenuInjection['closeMenu'] = async (index) => {
      if (Array.isArray(index)) {
        await nextTick(() => {
          closeMenu(index.at(-1)!)
          if (index.length > 1) {
            closeMenu(index.slice(0, -1))
          }
        })
        return
      }
      Object.keys(subMenus.value).forEach((item) => {
        if (subMenus.value[item].indexPath.includes(index)) {
          openedMenus.value = openedMenus.value.filter(item => item !== index)
        }
      })
    }

    function setSubMenusActive(index: string) {
      for (const key in subMenus.value) {
        subMenus.value[key].active = false
      }
      subMenus.value[index]?.indexPath.forEach((idx) => {
        subMenus.value[idx].active = true
      })
      items.value[index]?.indexPath.forEach((idx) => {
        subMenus.value[idx].active = true
      })
    }

    const handleMenuItemClick: MenuInjection['handleMenuItemClick'] = (index) => {
      if (props.mode === 'horizontal' || (props.collapse && props.collapseSubMenuMode === 'popup')) {
        openedMenus.value = []
      }
      // aside 模式：点击叶子不关闭右侧常驻列表
      setSubMenusActive(index)
    }
    const handleSubMenuClick: MenuInjection['handleSubMenuClick'] = (index, indexPath) => {
      if (openedMenus.value.includes(index)) {
        closeMenu(index)
      }
      else {
        openMenu(index, indexPath)
      }
    }
    const closeCollapseAside: MenuInjection['closeCollapseAside'] = () => {
      collapseAsideIndex.value = ''
      collapseAsideMenu.value = null
      notifyAsideSelect(null)
    }

    function findFirstNavigableLeaf(item: MineRoute.routeRecord): MineRoute.routeRecord | null {
      if (!isVisibleMenuItem(item)) {
        return null
      }
      const children = (item.children || []).filter(child => isVisibleMenuItem(child))
      if (children.length > 0) {
        for (const child of children) {
          const leaf = findFirstNavigableLeaf(child)
          if (leaf) {
            return leaf
          }
        }
        return null
      }
      // 叶子：有可跳转 path（外链 L 也算）
      if (item.meta?.type === 'L' && item.meta?.link) {
        return item
      }
      if (item.path) {
        return item
      }
      return null
    }

    const navigateCollapseFirstChild: MenuInjection['navigateCollapseFirstChild'] = (menu) => {
      // 优先父级 redirect（如站点设置 → /setting/cloud/upgrade）
      if (menu.redirect) {
        router.push({ path: menu.redirect as string }).catch(() => {})
        return
      }
      const leaf = findFirstNavigableLeaf(menu)
      if (!leaf) {
        return
      }
      if (leaf.meta?.type === 'L' && leaf.meta?.link) {
        window.open(leaf.meta.link as string, '_blank')
        return
      }
      if (leaf.path) {
        router.push({ path: leaf.path }).catch(() => {})
      }
    }

    const openCollapseAside: MenuInjection['openCollapseAside'] = (index, menu) => {
      // 常驻列表：同一项再点不关闭，仅切换其它父级；仍通知父级以防面板未挂载
      openedMenus.value = []
      collapseAsideIndex.value = index
      collapseAsideMenu.value = menu
      setSubMenusActive(index)
      notifyAsideSelect(menu)
      // 折叠点主菜单：进入第一个子菜单
      navigateCollapseFirstChild(menu)
    }

    function initMenu() {
      const activeItem = activeIndex.value && items.value[activeIndex.value]
      setSubMenusActive(activeIndex.value)
      if (props.collapse && props.collapseSubMenuMode === 'aside') {
        syncAsideDefault()
        return
      }
      if (!activeItem || props.collapse) {
        return
      }

      activeItem.indexPath.forEach((index) => {
        const subMenu = subMenus.value[index]
        subMenu && openMenu(index, subMenu.indexPath)
      })
    }

    watch(() => props.menu as MineRoute.routeRecord[], (val: MineRoute.routeRecord[]) => {
      initItems(val)
      initMenu()
    }, {
      deep: true,
      immediate: true,
    })

    watch(() => props.value, (currentValue) => {
      const key = (currentValue || '') as string
      if (items.value[key]) {
        activeIndex.value = items.value[key].index
      }
      else {
        // 不回退到上一次（欢迎页）选中，避免双高亮
        const found = Object.values(items.value).find(it => it.index === key)
        activeIndex.value = found?.index ?? key
      }
      if (props.collapse && props.collapseSubMenuMode === 'aside') {
        syncAsideDefault()
      }
      initMenu()
    })

    watch(() => props.collapse, (value) => {
      if (value) {
        openedMenus.value = []
        if (props.collapseSubMenuMode === 'aside') {
          syncAsideDefault()
        }
      }
      else {
        closeCollapseAside()
      }
      initMenu()
    })

    watch(() => props.collapseSubMenuMode, (mode) => {
      openedMenus.value = []
      if (props.collapse && mode === 'aside') {
        syncAsideDefault()
      }
      else {
        closeCollapseAside()
      }
    })

    const renderMenu = () => {
      return (
        <div
          class={{
            'relative transition-all': true,
            'flex-row! w-auto!': isMenuPopup.value && props.mode === 'horizontal',
          }}
        >
          <TransitionGroup name="mine-menu">
            {props.menu && props.menu.map((item: MineRoute.routeRecord) => {
              if (hasVisibleChildren(item)) {
                return (
                  <div key={item.name}><SubMenu menu={item} unique-key={[item.path || String(item.name) || JSON.stringify(item)]} /></div>
                )
              }
              else {
                return (
                  <div key={item.name}>
                    <MenuItem
                      item={item}
                      unique-key={[item.path || String(item.name) || JSON.stringify(item)]}
                      onClick={() => handleMenuItemClick(item.path || String(item.name) || JSON.stringify(item))}
                    />
                  </div>
                )
              }
            })}
          </TransitionGroup>
        </div>
      )
    }

    provide(rootMenuInjectionKey, reactive({
      props: props as any,
      items,
      subMenus,
      activeIndex,
      openedMenus,
      mouseInMenu,
      isMenuPopup,
      collapseAsideIndex,
      openMenu,
      closeMenu,
      handleMenuItemClick,
      handleSubMenuClick,
      openCollapseAside,
      closeCollapseAside,
      navigateCollapseFirstChild,
    }))

    return () => renderMenu()
  },
})
