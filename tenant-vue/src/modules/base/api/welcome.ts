import useHttp from '@/hooks/auto-imports/useHttp.ts'

const http = useHttp()

export function getWelcomeOverview() {
  return http.get('/admin/welcome/overview')
}

export function getWelcomeChart(params: { type: string; start: string; end: string }) {
  return http.get('/admin/welcome/chart', { params })
}

export function checkWelcomeVersion() {
  return http.get('/admin/welcome/version/check')
}

export function getWelcomeSaasBind() {
  return http.get('/admin/welcome/saas-bind')
}

export function getWelcomeSystemCheck() {
  return http.get('/admin/welcome/system-check')
}

export function getWelcomeMarketApps(params?: Record<string, unknown>) {
  return http.get('/admin/welcome/market/apps', { params })
}

export function getWelcomeMarketStats() {
  return http.get('/admin/welcome/market/stats')
}

export function getWelcomeInstalledApps() {
  return http.get('/admin/welcome/installed-apps')
}

export function migrateAppData(body: { from: string; to: string }) {
  return http.post('/admin/apps/migrate-data', body)
}

export interface AppSettingsDomain {
  id: number
  domain: string
  tenant_id: number
  identifier: string
  scheme: string
  is_primary: boolean
  public_base: string
}

export function getAppSettings(identifier: string) {
  return http.get<{
    identifier: string
    domains: AppSettingsDomain[]
    admin: { identifier: string, username: string, supported: boolean }
  }>('/admin/apps/settings', { params: { identifier } })
}

export function bindAppDomain(data: {
  identifier: string
  domain: string
  scheme?: string
  is_primary?: boolean
}) {
  return http.post<AppSettingsDomain>('/admin/apps/domains', data)
}

export function updateAppDomain(id: number, data: {
  domain?: string
  scheme?: string
  is_primary?: boolean
}) {
  return http.put<AppSettingsDomain>(`/admin/apps/domains/${id}`, data)
}

export function deleteAppDomain(id: number) {
  return http.delete(`/admin/apps/domains/${id}`)
}

export function changeAppAdminPassword(data: {
  identifier: string
  username?: string
  new_password: string
  new_password_confirmation: string
}) {
  return http.post('/admin/apps/change-admin-password', data)
}
