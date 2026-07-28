/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import type { App } from 'vue'
import type { Plugin } from '#/global'

const pluginConfig: Plugin.PluginConfig = {
  // eslint-disable-next-line unused-imports/no-unused-vars
  install(app: App) {},
  config: {
    enable: true,
    info: {
      name: 'mineadmin/hyperf-crontab',
      version: '1.0.8',
      author: 'MineAdmin',
      description: 'MineAdmin 定时任务应用',
    },
  },
}

export default pluginConfig
