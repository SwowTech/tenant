/**
 * 是否主创始人：user_id === 1 或角色含 founder.
 */
export default function isFounder(): boolean {
  const store = useUserStore()
  const info = store.getUserInfo() as { id?: number } | null
  if (info?.id === 1) {
    return true
  }
  return store.getRoles().includes('founder')
}
