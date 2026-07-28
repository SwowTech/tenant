import { createInjectionKey } from '@/utils/injectionKeys'
import type { MineRoute } from '#/global'

export interface MenuItem {
  index: string
  indexPath: string[]
  active?: boolean
}

export interface MenuProps {
  menu: MineRoute.routeRecord[]
  value: string
  accordion?: boolean
  defaultOpens?: string[]
  mode?: 'horizontal' | 'vertical'
  collapse?: boolean
  showCollapseName?: boolean
  /** 折叠后子菜单：popup 浮层 / aside 右侧列表 */
  collapseSubMenuMode?: 'popup' | 'aside'
}

export interface MenuInjection {
  props: MenuProps
  items: Record<string, MenuItem>
  subMenus: Record<string, MenuItem>
  activeIndex: MenuProps['value']
  openedMenus: string[]
  mouseInMenu: string[]
  isMenuPopup: boolean
  /** 折叠 + aside 模式时，右侧列表面板对应的父菜单 path */
  collapseAsideIndex: string
  openMenu: (index: string, indexPath: string[]) => void
  closeMenu: (index: string | string[]) => void
  handleMenuItemClick: (index: string) => void
  handleSubMenuClick: (index: string, indexPath: string[]) => void
  /** 折叠 aside 模式：打开/切换右侧菜单列表 */
  openCollapseAside: (index: string, menu: MineRoute.routeRecord) => void
  closeCollapseAside: () => void
  /** 折叠时点击主菜单：跳转到第一个可访问子菜单 */
  navigateCollapseFirstChild: (menu: MineRoute.routeRecord) => void
}

export const rootMenuInjectionKey = createInjectionKey<MenuInjection>('rootMenu')

export interface SubMenuProps {
  uniqueKey: string[]
  menu: MineRoute.routeRecord
  level?: number
}

export interface SubMenuItemProps {
  uniqueKey: string[]
  item: MineRoute.routeRecord
  level?: number
  subMenu?: boolean
  expand?: boolean
}
