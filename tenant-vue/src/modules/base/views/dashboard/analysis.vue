<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { useColorMode } from '@vueuse/core'
import { getDashboardAnalysis, type DashboardAnalysisVo } from '~/base/api/dashboard'
import { ResultCode } from '@/utils/ResultCode.ts'
import { useEcharts } from '@/hooks/useEcharts.ts'
import type { TransType } from '@/hooks/auto-imports/useTrans.ts'
import { translateDashboard } from '@/utils/dashboardI18n.ts'

defineOptions({ name: 'dashboard:analysis' })

const t = (useTrans() as TransType).globalTrans
const userStore = useUserStore()
const loading = ref(false)
const data = ref<DashboardAnalysisVo | null>(null)
const isDark = useColorMode()

const trendEl = ref<HTMLDivElement>()
const pieEl = ref<HTMLDivElement>()
const { setOption: setTrend } = useEcharts(trendEl)
const { setOption: setPie } = useEcharts(pieEl)

const scope = computed(() => data.value?.scope || 'site')

function scopeLabel(key: string) {
  return translateDashboard(t, scope.value, key)
}

function kpiTitle(key: string) {
  return translateDashboard(t, 'kpi', key)
}

function seriesName(key: string) {
  return translateDashboard(t, 'series', key)
}

function pieName(key: string) {
  return translateDashboard(t, 'pie', key)
}

function statusText(status: number) {
  const map: Record<number, string> = {
    1: t('dashboardPage.statusActive'),
    2: t('dashboardPage.statusDisabled'),
    5: t('dashboardPage.statusProvisioning'),
    6: t('dashboardPage.statusFailed'),
  }
  return map[status] || String(status)
}

async function load() {
  loading.value = true
  try {
    const res: any = await getDashboardAnalysis()
    if (res.code !== ResultCode.SUCCESS) {
      ElMessage.error(res.message || t('dashboardPage.loadFailed'))
      return
    }
    data.value = res.data
    await nextTick()
    renderCharts()
  }
  catch {
    ElMessage.error(t('dashboardPage.analysisLoadFailed'))
  }
  finally {
    loading.value = false
  }
}

function renderCharts() {
  if (!data.value) {
    return
  }
  const colors = ['#246EFF', '#00B2FF', '#81E2FF', '#722ED1']
  setTrend({
    grid: { left: 40, right: 16, top: 40, bottom: 40 },
    legend: { bottom: 0 },
    tooltip: { trigger: 'axis' },
    xAxis: {
      type: 'category',
      data: data.value.trend.dates,
      axisLabel: { color: '#86909C' },
    },
    yAxis: {
      type: 'value',
      splitLine: { lineStyle: { color: isDark.value === 'dark' ? '#3F3F3F' : '#E5E6EB' } },
    },
    series: data.value.trend.series.map((s, i) => ({
      name: seriesName(s.key || s.name),
      type: 'line',
      smooth: true,
      data: s.data,
      itemStyle: { color: colors[i % colors.length] },
    })),
  })

  setPie({
    tooltip: { trigger: 'item' },
    legend: { bottom: 0 },
    series: [{
      type: 'pie',
      radius: ['40%', '65%'],
      data: data.value.pie.map((p, i) => ({
        name: pieName(p.key || p.name),
        value: p.value,
        itemStyle: { color: colors[i % colors.length] },
      })),
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
    <div class="mine-card">
      <div class="mb-4 text-base font-medium">
        {{ scopeLabel('page_title') }}
      </div>
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="kpi in data?.kpis || []"
          :key="kpi.key"
          class="rounded border border-[var(--el-border-color-lighter)] p-4"
        >
          <div class="text-sm text-[var(--el-text-color-secondary)]">
            {{ kpiTitle(kpi.key || kpi.title) }}
          </div>
          <div class="mt-2 text-2xl font-semibold">
            {{ kpi.count.toLocaleString() }}
          </div>
          <div class="mt-1 text-xs" :class="kpi.growth >= 0 ? 'text-green-600' : 'text-red-500'">
            {{ t('dashboardPage.mom') }} {{ kpi.growth >= 0 ? '+' : '' }}{{ kpi.growth }}%
          </div>
        </div>
      </div>
    </div>

    <div class="xl:flex gap-3">
      <div class="mine-card w-auto xl:w-8/12">
        <div class="text-base">
          {{ scopeLabel('trend_title') }}
        </div>
        <div ref="trendEl" class="mt-4 h-[300px]" />
      </div>
      <div class="mine-card mt-3 w-auto xl:mt-0 xl:w-4/12 xl:ml-3">
        <div class="text-base">
          {{ scopeLabel('ranking_title') }}
        </div>
        <el-table
          v-if="data?.scope !== 'platform'"
          class="mt-3"
          :data="data?.ranking || []"
          size="small"
          stripe
        >
          <el-table-column type="index" :label="t('dashboardPage.rank')" width="60" />
          <el-table-column prop="name" :label="t('dashboardPage.account')" />
          <el-table-column prop="value" :label="t('dashboardPage.opsCount')" width="100" />
        </el-table>
        <el-table
          v-else
          class="mt-3"
          :data="data?.recent_tenants || []"
          size="small"
          stripe
        >
          <el-table-column type="index" label="#" width="50" />
          <el-table-column prop="name" :label="t('dashboardPage.tenantLabel')" min-width="100" />
          <el-table-column prop="domain" :label="t('dashboardPage.domain')" width="100" />
          <el-table-column :label="t('dashboardPage.status')" width="80">
            <template #default="{ row }">
              {{ statusText(row.status) }}
            </template>
          </el-table-column>
        </el-table>
      </div>
    </div>

    <div class="mine-card">
      <div class="text-base">
        {{ scopeLabel('pie_title') }}
      </div>
      <div ref="pieEl" class="mt-4 h-[280px]" />
    </div>
  </div>
</template>
