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

const systemCheckRoute: RouteRecordRaw = {
  name: 'systemCheck',
  path: '/system/check',
  meta: {
    title: '系统常规检测',
    i18n: 'menu.systemCheck',
    icon: 'mdi:clipboard-check-outline',
    type: 'M',
    // 菜单入口已挂到「设置」下（setting:system-check），此处仅保留路由供欢迎页跳转
    hidden: true,
    breadcrumbEnable: true,
    copyright: true,
    cache: true,
  },
  component: () => import('~/base/views/system/check/index.vue'),
}

export default systemCheckRoute
