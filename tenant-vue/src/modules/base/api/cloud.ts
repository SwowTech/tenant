import useHttp from '@/hooks/auto-imports/useHttp.ts'

const http = useHttp()

export interface CloudAuthUrlVo {
  url: string
  bound: boolean
}

export interface CloudSiteInfoVo {
  bound: boolean
  key: string
  token_masked: string
  url: string
  username: string
  phone: string
  email?: string
  bound_at: string
  auth: CloudAuthUrlVo
}

export function getCloudAuthUrl() {
  return http.get('/admin/cloud/auth-url')
}

export function getCloudSiteInfo() {
  return http.get('/admin/cloud/site-info')
}

export function getCloudStoreToken() {
  return http.get('/admin/cloud/store-token')
}

export interface CloudInstalledItemVo {
  identifier: string
  title: string
  version: string
  description?: string
  author?: string
  type: 'plugin' | 'app'
  /** local=仅本机；cloud=已在 SaaS 市场登记 */
  origin?: 'local' | 'cloud'
  status: boolean
  remote_version?: string
  update_available?: boolean
  in_market?: boolean
}

export interface CloudInstalledCatalogVo {
  list: CloudInstalledItemVo[]
  remote_ok: boolean
  remote_message?: string
}

export function getCloudInstalledCatalog() {
  return http.get<CloudInstalledCatalogVo>('/admin/cloud/installed-catalog')
}

export interface LocalAppCheckVo {
  available: boolean
  local_exists: boolean
  saas_exists: boolean
  message: string
}

export function checkLocalAppIdentifier(identifier: string) {
  return http.get<LocalAppCheckVo>('/admin/cloud/local-apps/check-identifier', {
    params: { identifier },
  })
}

export function createLocalApp(data: {
  identifier: string
  title: string
  version?: string
  edition?: string
  family?: string
  with_demo?: boolean
}) {
  return http.post('/admin/cloud/local-apps', data)
}

export interface CloudDiagnoseSaasVo {
  ok: boolean
  url: string
  latency_ms: number
  message: string
}

export interface CloudDiagnoseNetworkVo {
  server_time: string
  saas: CloudDiagnoseSaasVo
}

export interface CloudDiagnoseSiteVo {
  url: string
  key: string
  token_masked: string
  token: string
  version: string
  username: string
  phone: string
  email: string
  bound_at: string
}

export interface CloudDiagnoseVo {
  bound: boolean
  site: CloudDiagnoseSiteVo
  network: CloudDiagnoseNetworkVo
  register_path: string
}

export function getCloudDiagnose() {
  return http.get('/admin/cloud/diagnose')
}

export function getCloudDiagnoseToken() {
  return http.get('/admin/cloud/diagnose/token')
}

export function resetCloudDiagnose() {
  return http.post('/admin/cloud/diagnose/reset')
}

export function pingCloudSaas() {
  return http.get('/admin/cloud/diagnose/ping')
}

export interface CloudUpgradeCountsVo {
  overlay_files: number
  migrations: number
  scripts: number
}

export interface CloudUpgradeCheckVo {
  upgrade: boolean
  message?: string
  version?: string
  changelog?: string
  sha256?: string
  counts?: CloudUpgradeCountsVo
}

export interface CloudUpgradeTaskVo {
  id: string
  status: 'pending' | 'running' | 'success' | 'failed'
  step: string
  progress: number
  message: string
  target_version: string
  logs?: Array<{ at: string, message: string }>
  created_at?: string
  updated_at?: string
}

export interface CloudUpgradeOverviewVo {
  current_version: string
  latest_task: CloudUpgradeTaskVo | null
}

export interface CloudUpgradeStartVo {
  task_id: string
  poll_interval: number
}

export function getCloudUpgrade() {
  return http.get('/admin/cloud/upgrade')
}

export function checkCloudUpgrade() {
  return http.post('/admin/cloud/upgrade/check')
}

export function startCloudUpgrade(data: { agreed: boolean }) {
  return http.post('/admin/cloud/upgrade/start', data)
}

export function getCloudUpgradeTask(id: string) {
  return http.get(`/admin/cloud/upgrade/task/${id}`)
}
