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
import ucChildren from './ucChildren'

const rootRoutes: RouteRecordRaw[] = [
  {
    name: 'MineRootLayoutRoute',
    path: '/',
    component: () => import('@/layouts'),
    redirect: '/login',
  },
  {
    name: 'uc',
    path: '/uc',
    component: () => import('@/layouts/uc.tsx'),
    redirect: '/uc/index',
    children: ucChildren,
  },
  {
    name: 'login',
    path: '/login',
    component: () => import('~/base/views/login/index.vue'),
    meta: {
      title: '租户登录',
      i18n: 'menu.login',
    },
  },
  {
    name: 'admin',
    path: '/admin',
    component: () => import('~/base/views/admin-login/index.vue'),
    meta: {
      title: '管理登录',
    },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'MineSystemError',
    component: () => import('@/layouts/[...all].tsx'),
    meta: {
      hidden: true,
      i18n: 'menu.pageError',
    },
  },
]

export default rootRoutes
