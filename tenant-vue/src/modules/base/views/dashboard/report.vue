<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { useColorMode } from '@vueuse/core'
import { getDashboardReport, type DashboardReportVo } from '~/base/api/dashboard'
import { ResultCode } from '@/utils/ResultCode.ts'
import { useEcharts } from '@/hooks/useEcharts.ts'
import ReportMiniChart from './components/report/report-mini-chart.vue'
import type { TransType } from '@/hooks/auto-imports/useTrans.ts'
import { translateDashboard } from '@/utils/dashboardI18n.ts'

defineOptions({ name: 'dashboard:report' })

const t = (useTrans() as TransType).globalTrans
const userStore = useUserStore()
const loading = ref(false)
const data = ref<DashboardReportVo | null>(null)
const isDark = useColorMode()

const overviewEl = ref<HTMLDivElement>()
const browserEl = ref<HTMLDivElement>()
const routeEl = ref<HTMLDivElement>()

const { setOption: setOverview } = useEcharts(overviewEl)
const { setOption: setBrowser } = useEcharts(browserEl)
const { setOption: setRoute } = useEcharts(routeEl)

const itemColors = ['#722ED1', '#F77234', '#33D1C9', '#3469FF']
const scope = computed(() => data.value?.scope || 'site')

function scopeLabel(key: string) {
  return translateDashboard(t, scope.value, key)
}

function seriesName(key: string) {
  return translateDashboard(t, 'series', key)
}

function pieName(key: string) {
  return translateDashboard(t, 'pie', key)
}

function summaryTitle(key: string) {
  return translateDashboard(t, 'summary', key)
}

function itemTitle(key: string) {
  return translateDashboard(t, 'item', key)
}

function axisLabel(key: string) {
  return translateDashboard(t, 'axis', key)
}

async function load() {
  loading.value = true
  try {
    const res: any = await getDashboardReport()
    if (res.code !== ResultCode.SUCCESS) {
      ElMessage.error(res.message || t('dashboardPage.loadFailed'))
      return
    }
    data.value = res.data
    await nextTick()
    renderCharts()
  }
  catch {
    ElMessage.error(t('dashboardPage.reportLoadFailed'))
  }
  finally {
    loading.value = false
  }
}

function renderCharts() {
  if (!data.value) {
    return
  }
  const colors = itemColors
  setOverview({
    grid: { left: '4%', right: 16, top: 40, bottom: 40 },
    legend: { bottom: 0 },
    tooltip: { trigger: 'axis' },
    xAxis: {
      type: 'category',
      data: data.value.overview.dates,
      boundaryGap: false,
    },
    yAxis: {
      type: 'value',
      splitLine: { lineStyle: { color: isDark.value === 'dark' ? '#2E2E30' : '#F2F3F5' } },
    },
    series: data.value.overview.series.map((s, i) => ({
      name: seriesName(s.key || s.name),
      type: 'line',
      smooth: true,
      data: s.data,
      areaStyle: { opacity: 0.08 },
      itemStyle: { color: colors[i % colors.length] },
    })),
  })

  const dist = data.value.scope === 'platform'
    ? (data.value.status_pie || [])
    : (data.value.browsers || [])
  setBrowser({
    tooltip: { trigger: 'item' },
    legend: { bottom: 0, type: 'scroll' },
    series: [{
      type: 'pie',
      radius: '60%',
      data: dist.map((d, i) => ({
        name: data.value?.scope === 'platform'
          ? pieName(d.key || d.name)
          : (d.name || t('dashboardPage.unknown')),
        value: d.value,
        itemStyle: { color: colors[i % colors.length] },
      })),
    }],
  })

  const routes = data.value.hot_routes || []
  setRoute({
    tooltip: { trigger: 'axis' },
    grid: { left: 80, right: 16, top: 16, bottom: 32 },
    xAxis: { type: 'value' },
    yAxis: {
      type: 'category',
      data: routes.map(r => r.name).reverse(),
      axisLabel: { width: 70, overflow: 'truncate' },
    },
    series: [{
      type: 'bar',
      data: routes.map(r => r.value).reverse(),
      itemStyle: { color: '#165DFF' },
    }],
  })
}

watch(() => userStore.getLanguage(), () => {
  if (data.value) {
    renderCharts()
  }
})

onMounted(load)
</script>

<template>
  <div v-loading="loading" class="mine-layout flex flex-col gap-3">
    <div class="lg:flex gap-3">
      <div class="mine-card w-auto p-3 xl:w-8/12">
        <div class="text-base">
          {{ scopeLabel('report_title') }}
        </div>
        <div class="mt-6 grid grid-cols-2 gap-y-3 md:grid-cols-4">
          <div
            v-for="(item, idx) in data?.overview?.summary || []"
            :key="idx"
            class="flex gap-3"
          >
            <div
              class="h-[50px] w-[50px] flex-center rounded-md p-1"
              :style="{ background: `${item.color}22` }"
            >
              <ma-svg-icon :name="item.icon" :size="28" :style="{ color: item.color }" />
            </div>
            <el-statistic :value="item.value">
              <template #title>
                <div class="text-sm">
                  {{ summaryTitle(item.key || item.title) }}
                </div>
              </template>
            </el-statistic>
          </div>
        </div>
        <div ref="overviewEl" class="mt-5 h-[360px]" />
      </div>

      <div class="mt-3 w-full flex flex-col gap-3 lg:mt-0 lg:ml-3 lg:w-4/12">
        <div class="mine-card">
          <div class="text-base">
            {{ data?.scope === 'platform' ? t('dashboardPage.tenantStatus') : t('dashboardPage.browserDist') }}
          </div>
          <div ref="browserEl" class="mt-3 h-[220px]" />
        </div>
        <div class="mine-card">
          <div class="text-base">
            {{ data?.scope === 'platform' ? t('dashboardPage.appEnableRank') : t('dashboardPage.hotBiz') }}
          </div>
          <div ref="routeEl" class="mt-3 h-[220px]" />
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="(item, idx) in data?.items || []"
        :key="idx"
        class="mine-card"
      >
        <div class="text-sm text-[var(--el-text-color-secondary)]">
          {{ itemTitle(item.key || item.title) }}
        </div>
        <div class="mt-1 text-xl font-semibold">
          {{ item.count.toLocaleString() }}
        </div>
        <ReportMiniChart
          class="mt-2"
          :chart-type="item.chart_type"
          :x-axis="(item.chart.xAxis || []).map(axisLabel)"
          :data="item.chart.data"
          :color="itemColors[idx % itemColors.length]"
        />
      </div>
    </div>
  </div>
</template>
