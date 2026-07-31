/**
 * 是否平台创始人（可管租户快捷入口等）.
 * 注意：租户站管理员常为 user_id=1，绝不能仅凭 id===1 判定。
 */
import { getTenantId } from '@/utils/tenantContext.ts'
import useUserStore from '@/store/modules/useUserStore.ts'

/** 当前是否像租户子域（test.swow.tech / acme.localhost） */
function isTenantHostname(): boolean {
  const hostname = window.location.hostname.toLowerCase()
  if (!hostname || hostname === 'localhost' || hostname === '127.0.0.1') {
    return false
  }
  // *.localhost → 租户；localhost 本身是主站
  if (hostname.endsWith('.localhost')) {
    const label = hostname.slice(0, -'.localhost'.length).split('.')[0] || ''
    return label !== '' && !['www', 'api', 'admin', 'docs'].includes(label)
  }
  const parts = hostname.split('.').filter(Boolean)
  if (parts.length <= 2) {
    return false
  }
  const label = parts[0]
  return !['www', 'api', 'admin', 'docs', 'www-api'].includes(label)
}

export default function isFounder(): boolean {
  // 「进入租户」后的会话 / 租户子域：不展示平台创始人能力
  if (getTenantId() || isTenantHostname()) {
    return false
  }

  const store = useUserStore()
  if (store.getRoles().includes('founder')) {
    return true
  }

  const info = store.getUserInfo() as { id?: number } | null
  return info?.id === 1
}
