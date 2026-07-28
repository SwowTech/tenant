/**
 * Backend may still return Chinese labels as key/title before process restart.
 * Map them to stable i18n keys under dashboardPage.*.
 */
const DASHBOARD_KEY_ALIAS: Record<string, string> = {
  // kpi / summary
  租户总数: 'tenants',
  正常租户: 'active',
  '开通中/失败': 'pending',
  已绑自定义域: 'custom_domain',
  用户总数: 'users',
  近7日登录成功: 'logins',
  近7日操作次数: 'ops',
  附件数: 'attachments',
  已启用应用实例: 'apps',
  近30日登录: 'logins_30',
  近30日操作: 'ops_30',
  近30日新增用户: 'new_users_30',
  近7日留存用户: 'retention_7',
  // items
  本周登录趋势: 'week_login',
  本周操作趋势: 'week_ops',
  附件近7日新增: 'attach_7',
  近30日开通趋势: 'provision_30',
  租户应用启用排行: 'app_rank',
  '租户应用启用排行(示意)': 'app_rank',
  域名绑定: 'domain_bind',
  状态分布: 'status_dist',
  // series
  新建租户: 'new_tenants',
  登录成功: 'login_ok',
  操作次数: 'ops',
  新增用户: 'new_users',
  // pie / axis
  正常: 'active',
  停用: 'disabled',
  异常: 'pending',
  成功: 'success',
  失败: 'fail',
  已绑定: 'bound',
  未绑定: 'unbound',
}

export function normalizeDashboardKey(raw: string | undefined | null): string {
  const value = (raw ?? '').trim()
  if (!value) {
    return ''
  }
  return DASHBOARD_KEY_ALIAS[value] || value
}

export function translateDashboard(
  translate: (key: string) => string,
  namespace: string,
  raw: string | undefined | null,
): string {
  const key = normalizeDashboardKey(raw)
  if (!key) {
    return ''
  }
  const full = `dashboardPage.${namespace}.${key}`
  const out = translate(full)
  if (out !== full) {
    return out
  }
  // Unknown stable key: if backend sent human text, show it as-is
  if (raw && /[^\w.]/.test(raw)) {
    return raw
  }
  return out
}
