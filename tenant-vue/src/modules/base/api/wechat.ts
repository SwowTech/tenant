import useHttp from '@/hooks/auto-imports/useHttp.ts'

export interface WechatAccountVo {
  id?: number
  name: string
  app_id: string
  app_secret: string
  app_secret_set?: boolean
  token: string
  encoding_aes_key: string
  level: number
  status: number
  callback_url: string
}

const http = useHttp()

export function getAccount() {
  return http.get('/admin/wechat/account')
}

export function saveAccount(data: Partial<WechatAccountVo>) {
  return http.put('/admin/wechat/account', data)
}

export function refreshAccessToken() {
  return http.post('/admin/wechat/account/refresh-token')
}
