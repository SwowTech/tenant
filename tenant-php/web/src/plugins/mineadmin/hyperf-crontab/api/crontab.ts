/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
const http = useHttp()

/**
 * 定时任务列表
 */
export function list(params: any) {
  return http.get('/admin/plugin/crontab/list', { params })
}

/**
 * 创建定时任务
 */
export function create(data: any) {
  return http.post('/admin/plugin/crontab/create', data)
}

/**
 * 保存更新定时任务
 */
export function save(id: number, data: any) {
  return http.put(`/admin/plugin/crontab/${id}/save`, data)
}

/**
 * 删除定时任务
 */
export function deleted(ids: number[]) {
  return http.delete(`/admin/plugin/crontab/delete`, { data: { ids } })
}

/**
 * 执行定时任务
 */
export function execute(ids: number[]) {
  return http.post(`/admin/plugin/crontab/execute`, { ids })
}

/**
 * 定时任务记录列表
 */
export function logList(params: any) {
  return http.get('/admin/plugin/crontab/log/list', { params })
}
