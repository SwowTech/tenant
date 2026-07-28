/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import type { Dictionary } from '#/global'

export default [
  { label: 'URL任务', value: 'url', i18n: 'dictionary.crontab.url', color: 'success' },
  { label: '类任务', value: 'class', i18n: 'dictionary.crontab.class', color: 'primary' },
  { label: 'Eval任务', value: 'eval', i18n: 'dictionary.crontab.eval', color: 'danger' },
  { label: '回调任务', value: 'callback', i18n: 'dictionary.crontab.callback', color: 'info' },
  { label: '命令任务', value: 'command', i18n: 'dictionary.crontab.command', color: 'warning' },
] as Dictionary[]
