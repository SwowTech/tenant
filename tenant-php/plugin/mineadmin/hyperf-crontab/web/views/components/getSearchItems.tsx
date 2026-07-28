/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo <root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */

import type { MaSearchItem } from '@mineadmin/search'
import MaDictSelect from '@/components/ma-dict-picker/ma-dict-select.vue'

export default function getSearchItems(t: any): MaSearchItem[] {
  return [
    {
      label: () => t('mineCrontab.cols.name'),
      prop: 'name',
      render: 'input',
      renderProps: {
        placeholder: t('form.pleaseInput', { msg: t('mineCrontab.cols.name') }),
      },
    },
    {
      label: () => t('mineCrontab.cols.type'),
      prop: 'type',
      render: () => <ma-dict-select dictName="crontab-type" />,
      renderProps: {
        placeholder: t('form.pleaseSelect', { msg: t('mineCrontab.cols.type') }),
      },
    },
    {
      label: () => t('mineCrontab.cols.status'),
      prop: 'status',
      render: () => MaDictSelect,
      renderProps: {
        placeholder: t('form.pleaseSelect', { msg: t('mineCrontab.cols.status') }),
        data: [
          {
            label: t('mineCrontab.status.stopped'),
            value: 0,
          },
          {
            label: t('mineCrontab.status.running'),
            value: 1,
          },
        ],
      },
    },
  ]
}
