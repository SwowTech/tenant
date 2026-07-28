import useHttp from '@/hooks/auto-imports/useHttp.ts'

const http = useHttp()

export interface DashboardKpi {
  key: string
  title: string
  count: number
  growth: number
}

export interface DashboardSeries {
  key?: string
  name: string
  data: number[]
}

export interface DashboardAnalysisVo {
  scope: 'tenant' | 'platform' | 'site'
  labels: Record<string, string>
  kpis: DashboardKpi[]
  trend: { dates: string[], series: DashboardSeries[] }
  ranking: Array<{ name: string, value: number }>
  pie: Array<{ key?: string, name: string, value: number }>
  recent_tenants?: Array<{ name: string, domain: string, status: number, created_at: string }>
}

export interface DashboardReportVo {
  scope: 'tenant' | 'platform' | 'site'
  labels: Record<string, string>
  overview: {
    dates: string[]
    series: DashboardSeries[]
    summary: Array<{ key?: string, title: string, value: number, icon: string, color: string }>
  }
  browsers: Array<{ key?: string, name: string, value: number }>
  os: Array<{ name: string, value: number }>
  hot_routes: Array<{ name: string, value: number }>
  status_pie?: Array<{ key?: string, name: string, value: number }>
  domain_pie?: Array<{ key?: string, name: string, value: number }>
  items: Array<{
    key?: string
    title: string
    count: number
    growth: number
    chart_type: string
    chart: { xAxis: string[], data: number[] }
  }>
}

export function getDashboardAnalysis() {
  return http.get('/admin/dashboard/analysis')
}

export function getDashboardReport() {
  return http.get('/admin/dashboard/report')
}
