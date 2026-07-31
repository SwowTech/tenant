import useHttp from '@/hooks/auto-imports/useHttp.ts'

const http = useHttp()

export interface FounderTenantVo {
  id?: number
  code?: string
  name: string
  domain: string
  custom_domain?: string
  table_prefix?: string
  status?: number
  contact_phone?: string
  contact_email?: string
  remark?: string
  admin_user?: string
  admin_pass?: string
  access_url?: string
  created_at?: string
  updated_at?: string
}

export interface FounderTenantPage {
  list: FounderTenantVo[]
  total: number
  page: number
  page_size: number
}

export function getFounderTenants(params: Record<string, any>) {
  return http.get('/admin/founder/tenants', { params })
}

export function checkFounderDomainAvailable(domain: string, excludeId?: number) {
  return http.get<{ domain: string, available: boolean, root_host?: string, access_url?: string }>('/admin/founder/tenants/domain-available', {
    params: {
      domain,
      ...(excludeId ? { exclude_id: excludeId } : {}),
    },
  })
}

export function suggestFounderDomain() {
  return http.get<{ domain: string, available: boolean, root_host?: string, access_url?: string }>('/admin/founder/tenants/suggest-domain')
}

export function getFounderTenant(id: number) {
  return http.get(`/admin/founder/tenants/${id}`)
}

export function createFounderTenant(data: FounderTenantVo) {
  return http.post('/admin/founder/tenants', data)
}

export function updateFounderTenant(id: number, data: Partial<FounderTenantVo>) {
  return http.put(`/admin/founder/tenants/${id}`, data)
}

export function reprovisionFounderTenant(id: number, data?: { admin_user?: string, admin_pass?: string }) {
  return http.post(`/admin/founder/tenants/${id}/provision`, data || {})
}

/** 创始人免密进入租户（签发租户管理员 token） */
export function enterFounderTenant(id: number) {
  return http.post<{
    access_token: string
    refresh_token: string
    expire_at: number
    tenant?: FounderTenantVo
  }>(`/admin/founder/tenants/${id}/enter`)
}

export interface FounderAssignableApp {
  status?: boolean | number
  version?: string
  description?: string
  author?: unknown
  edition?: string
  family?: string
}

export function getFounderAssignableApps() {
  return http.get<Record<string, FounderAssignableApp>>('/admin/founder/apps')
}

export interface FounderTenantAppVo {
  identifier: string
  version: string
  edition?: string
  family?: string
  status: number
  installed_at?: string | null
  expires_at?: string | null
  expired?: boolean
  expires_label?: string
}

export function getFounderTenantApps(tenantId: number) {
  return http.get<FounderTenantAppVo[]>(`/admin/founder/tenants/${tenantId}/apps`)
}

export function assignFounderTenantApps(
  tenantId: number,
  apps: Array<{ identifier: string, version: string }>,
  period?: { years?: number, months?: number },
) {
  return http.post(`/admin/founder/tenants/${tenantId}/apps`, {
    apps,
    years: period?.years ?? 0,
    months: period?.months ?? 0,
  })
}

export function setFounderTenantAppStatus(
  tenantId: number,
  identifier: string,
  status: 1 | 2,
) {
  const enc = encodeURIComponent(identifier)
  return http.put(`/admin/founder/tenants/${tenantId}/apps/${enc}`, { status })
}

export function updateFounderTenantApp(
  tenantId: number,
  identifier: string,
  data: { status?: 1 | 2, years?: number, months?: number },
) {
  const enc = encodeURIComponent(identifier)
  return http.put(`/admin/founder/tenants/${tenantId}/apps/${enc}`, data)
}

export function removeFounderTenantApp(tenantId: number, identifier: string) {
  const enc = encodeURIComponent(identifier)
  return http.delete(`/admin/founder/tenants/${tenantId}/apps/${enc}`)
}

export function assignFounderTenantApp(tenantId: number, data: { identifier: string, version: string, years?: number, months?: number }) {
  return http.post(`/admin/founder/tenants/${tenantId}/apps`, data)
}
