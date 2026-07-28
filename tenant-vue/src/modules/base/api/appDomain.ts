import useHttp from '@/hooks/auto-imports/useHttp.ts'

const http = useHttp()

export interface AppDomainVo {
  id: number
  domain: string
  tenant_id: number
  identifier: string
  scheme: string
  is_primary: boolean
  public_base: string
  tenant_name?: string
  tenant_domain?: string
  tenant_code?: string
}

export interface AppDomainAppOption {
  identifier: string
  title: string
}

/** 租户自助：当前租户域名列表 */
export function getTenantAppDomains(params?: { identifier?: string }) {
  return http.get<{ list: AppDomainVo[], apps: AppDomainAppOption[] }>('/admin/app-domains', { params })
}

export function createTenantAppDomain(data: {
  identifier: string
  domain: string
  scheme?: string
  is_primary?: boolean
}) {
  return http.post<AppDomainVo>('/admin/app-domains', data)
}

export function updateTenantAppDomain(id: number, data: {
  domain?: string
  scheme?: string
  is_primary?: boolean
}) {
  return http.put<AppDomainVo>(`/admin/app-domains/${id}`, data)
}

export function deleteTenantAppDomain(id: number) {
  return http.delete(`/admin/app-domains/${id}`)
}

/** 创始人：全平台域名列表（需 Bearer 访问令牌） */
export function getFounderAppDomains(params?: { tenant_id?: number, identifier?: string }) {
  return http.get<{ list: AppDomainVo[] }>('/admin/founder/app-domains', { params })
}

export function createFounderAppDomain(data: {
  tenant_id: number
  identifier: string
  domain: string
  scheme?: string
  is_primary?: boolean
}) {
  return http.post<AppDomainVo>('/admin/founder/app-domains', data)
}

export function updateFounderAppDomain(id: number, data: {
  domain?: string
  scheme?: string
  is_primary?: boolean
}) {
  return http.put<AppDomainVo>(`/admin/founder/app-domains/${id}`, data)
}

export function deleteFounderAppDomain(id: number) {
  return http.delete(`/admin/founder/app-domains/${id}`)
}
