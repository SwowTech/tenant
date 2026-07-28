/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import type { MaProTableColumns } from '@mineadmin/pro-table'
import { ElTag } from 'element-plus'

export default function getTableColumns(formRef: any, t: any): MaProTableColumns[] {
  return [
    {
      label: () => t('ID'),
      prop: 'id',
      width: '100px',
    },
    { label: () => t('mineCrontab.cols.name'), prop: 'name' },
    {
      label: () => t('mineCrontab.cols.status'),
      prop: 'status',
      cellRender: ({ row }) => {
        const displayStatus = (row.status === 0 || row.status === 1) ? row.status : 2
        return (
          <ElTag type={displayStatus === 0 ? 'danger' : 'success'}>
            {t(`dictionary.status.${displayStatus}`)}
          </ElTag>
        )
      },
    },
    { label: () => t('mineCrontab.cols.value'), prop: 'target' },
    { label: () => t('mineCrontab.cols.exceptionInfo'), prop: 'exception_info' },
    { label: () => t('mineCrontab.cols.executeTime'), prop: 'created_at' },
  ]
}
