import useHttp from '@/hooks/auto-imports/useHttp.ts'

const http = useHttp()

export interface SiteSettingVo {
  closed: boolean
  close_reason: string
  auto_logout: number
}

export interface SiteIcpVo {
  id?: number
  domain: string
  icp: string
  police: string
  license_url: string
}

export interface SiteIcpListVo {
  list: SiteIcpVo[]
  total: number
}

export interface AttachmentImageVo {
  thumb: boolean
  width: number
  extentions: string[]
  limit: number
  zip_percentage: number
}

export interface AttachmentAudioVo {
  extentions: string[]
  limit: number
}

export interface AttachmentRemoteVo {
  type: string
  [key: string]: unknown
}

export interface AttachmentSettingVo {
  attachment_limit: number
  image: AttachmentImageVo
  audio: AttachmentAudioVo
  remote: AttachmentRemoteVo
  php_env?: {
    upload_max_filesize: string
    post_max_size: string
  }
}

export interface SystemInfoVo {
  app_version: string
  family: string
  os: string
  php: string
  sapi: string
  mysql_version: string
  upload_max: string
  db_size: string
  attach_url: string
  attach_size: string
  copyright: {
    name: string
    url: string
  }
}

export interface IpWhitelistVo {
  list: string[]
}

export interface SensitiveWordVo {
  list: string[]
}

export interface UserLoginProviderVo {
  app_id: string
  app_secret: string
  callback_domain: string
}

export interface UserLoginSettingVo {
  register_enabled: boolean
  review_new_user: boolean
  user_agreement: string
  captcha_register: boolean
  captcha_login: boolean
  password_strength: string
  default_user_group: number
  login_time_limit: number
  // legacy optional fields kept for API compatibility
  mobile_register_enabled?: boolean
  force_bind?: string
  third_party_entry?: boolean
  qq?: UserLoginProviderVo
  wechat?: UserLoginProviderVo
  review_app_operator?: boolean
  operator_force_bind?: boolean
}

export interface LoginConfigVo {
  register_enabled: boolean
  captcha_login: boolean
  captcha_register: boolean
  password_strength: string
  user_agreement: string
  closed: boolean
  close_reason: string
  auto_logout: number
  login_time_limit: number
}

export function getSiteSetting() {
  return http.get('/admin/setting/site')
}

export function saveSiteSetting(data: Partial<SiteSettingVo>) {
  return http.put('/admin/setting/site', data)
}

export function getSiteIcpList(params?: { keyword?: string }) {
  return http.get('/admin/setting/site/icp', { params })
}

export function createSiteIcp(data: Partial<SiteIcpVo>) {
  return http.post('/admin/setting/site/icp', data)
}

export function updateSiteIcp(id: number, data: Partial<SiteIcpVo>) {
  return http.put(`/admin/setting/site/icp/${id}`, data)
}

export function deleteSiteIcp(id: number) {
  return http.delete(`/admin/setting/site/icp/${id}`)
}

export function getSystemInfo(params?: { attach_size?: number }) {
  return http.get('/admin/setting/systeminfo', { params })
}

export function getAttachmentSetting() {
  return http.get('/admin/setting/attachment')
}

export function saveAttachmentSetting(data: Partial<AttachmentSettingVo>) {
  return http.put('/admin/setting/attachment', data)
}

export function getUserLoginSetting() {
  return http.get('/admin/setting/user-login')
}

export function saveUserLoginSetting(data: Partial<UserLoginSettingVo>) {
  return http.put('/admin/setting/user-login', data)
}

/* ---- 数据库工具（对齐微擎） ---- */

export interface DbTableVo {
  name: string
  engine: string
  rows: number
  data: string
  index: string
  free: string
  free_bytes: number
  collation: string
  comment: string
  need_optimize: boolean
}

export interface DbBackupVo {
  bakdir: string
  time: number
  time_text: string
  volume: number
  prefix: string
  size: string
}

export function getDatabaseTables() {
  return http.get('/admin/setting/tools/database/tables')
}

export function optimizeDatabaseTables(tables: string[]) {
  return http.post('/admin/setting/tools/database/optimize', { tables })
}

export function backupDatabaseStep(data: Record<string, unknown>) {
  return http.post('/admin/setting/tools/database/backup', data)
}

export function getDatabaseBackups() {
  return http.get('/admin/setting/tools/database/backups')
}

export function deleteDatabaseBackup(dirname: string) {
  return http.delete(`/admin/setting/tools/database/backups/${encodeURIComponent(dirname)}`)
}

export function restoreDatabaseStep(data: { dirname: string, volume_index?: number }) {
  return http.post('/admin/setting/tools/database/restore', data)
}
