/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo <root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */

import type { MaProTableColumns } from '@mineadmin/pro-table'
import type { UseDialogExpose } from '@/hooks/useDialog.ts'

export default function getTableColumns(dialog: UseDialogExpose, formRef: any, t: any): MaProTableColumns[] {
  return [
    [
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
        render: () => <ma-dict-select dictName="system-status" />,
        renderProps: {
          placeholder: t('form.pleaseSelect', { msg: t('mineCrontab.cols.status') }),
        },
      },
    ],
  ]
}
