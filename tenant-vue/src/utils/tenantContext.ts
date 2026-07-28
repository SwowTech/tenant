export const TENANT_ID_KEY = 'mine_tenant_id'
export const TENANT_NAME_KEY = 'mine_tenant_name'
export const FOUNDER_SESSION_KEY = 'mine_founder_session'

export interface FounderSessionStash {
  access_token: string
  refresh_token: string
  /** 绝对过期时间（unix 秒） */
  expire: number
}

export function getTenantId(): string | null {
  const raw = localStorage.getItem(TENANT_ID_KEY)
  if (!raw || !/^\d+$/.test(raw)) {
    return null
  }
  return raw
}

export function getTenantName(): string | null {
  return sessionStorage.getItem(TENANT_NAME_KEY)
}

export function setTenantContext(id: number, name?: string): void {
  if (!Number.isFinite(id) || id <= 0) {
    throw new Error('invalid tenant id')
  }
  localStorage.setItem(TENANT_ID_KEY, String(Math.trunc(id)))
  if (name && name.trim()) {
    sessionStorage.setItem(TENANT_NAME_KEY, name.trim())
  }
  else {
    sessionStorage.removeItem(TENANT_NAME_KEY)
  }
}

export function clearTenantContext(): void {
  localStorage.removeItem(TENANT_ID_KEY)
  sessionStorage.removeItem(TENANT_NAME_KEY)
}

export function stashFounderSession(session: FounderSessionStash): void {
  if (!session.access_token || !session.refresh_token) {
    return
  }
  localStorage.setItem(FOUNDER_SESSION_KEY, JSON.stringify({
    access_token: session.access_token,
    refresh_token: session.refresh_token,
    expire: Number(session.expire) || 0,
  }))
}

export function peekFounderSession(): FounderSessionStash | null {
  const raw = localStorage.getItem(FOUNDER_SESSION_KEY)
  if (!raw) {
    return null
  }
  try {
    const data = JSON.parse(raw) as FounderSessionStash
    if (!data?.access_token || !data?.refresh_token) {
      return null
    }
    return data
  }
  catch {
    return null
  }
}

/** 取出并清除暂存的创始人会话 */
export function takeFounderSession(): FounderSessionStash | null {
  const data = peekFounderSession()
  clearFounderSession()
  return data
}

export function clearFounderSession(): void {
  localStorage.removeItem(FOUNDER_SESSION_KEY)
}
