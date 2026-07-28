<script setup lang="ts">
import { getWelcomeChart } from '~/base/api/welcome'
import { useEcharts } from '@/hooks/useEcharts.ts'
import { ResultCode } from '@/utils/ResultCode.ts'

defineOptions({ name: 'WelcomeChart' })

const props = defineProps<{
  initial?: {
    dates?: string[]
    visits?: number[]
    visitors?: number[]
    type?: string
  } | null
}>()

const chartEl = ref<HTMLElement>()
const chartApi = useEcharts(chartEl)
const loading = ref(false)
const activeType = ref('realtime')
const dateRange = ref<[Date, Date] | ''>('')

const typeTabs = [
  { name: 'realtime', label: '实时概况' },
  { name: 'platform', label: '平台统计' },
  { name: 'recycle', label: '回收统计' },
]

function defaultRange(): [Date, Date] {
  const end = new Date()
  const start = new Date()
  start.setDate(end.getDate() - 6)
  start.setHours(0, 0, 0, 0)
  end.setHours(0, 0, 0, 0)
  return [start, end]
}

function formatDate(d: Date): string {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function buildOption(dates: string[], visits: number[], visitors: number[]) {
  return {
    color: ['#4285F4', '#34A853'],
    grid: {
      left: '3%',
      right: '3%',
      top: 40,
      bottom: 30,
      containLabel: true,
    },
    legend: {
      data: ['访问次数', '访问人数'],
      top: 0,
    },
    tooltip: {
      trigger: 'axis',
    },
    xAxis: {
      type: 'category',
      boundaryGap: false,
      data: dates,
      axisLine: { lineStyle: { color: '#E5E8EF' } },
      axisLabel: { color: '#4E5969' },
    },
    yAxis: {
      type: 'value',
      minInterval: 1,
      splitLine: {
        lineStyle: { type: 'dashed', color: '#E5E8EF' },
      },
      axisLabel: { color: '#4E5969' },
    },
    series: [
      {
        name: '访问次数',
        type: 'line',
        smooth: true,
        showSymbol: dates.length <= 14,
        data: visits,
      },
      {
        name: '访问人数',
        type: 'line',
        smooth: true,
        showSymbol: dates.length <= 14,
        data: visitors,
      },
    ],
  }
}

function renderChart(dates: string[], visits: number[], visitors: number[]) {
  chartApi.setOption(buildOption(dates, visits, visitors))
}

async function loadChart() {
  if (!dateRange.value || !Array.isArray(dateRange.value)) {
    return
  }
  loading.value = true
  try {
    const res: any = await getWelcomeChart({
      type: activeType.value,
      start: formatDate(dateRange.value[0]),
      end: formatDate(dateRange.value[1]),
    })
    if (res.code === ResultCode.SUCCESS && res.data) {
      renderChart(
        res.data.dates ?? [],
        res.data.visits ?? [],
        res.data.visitors ?? [],
      )
    }
  }
  finally {
    loading.value = false
  }
}

function onTabChange() {
  loadChart()
}

function onDateChange() {
  loadChart()
}

onMounted(() => {
  dateRange.value = defaultRange()
  if (props.initial?.dates?.length) {
    renderChart(
      props.initial.dates,
      props.initial.visits ?? [],
      props.initial.visitors ?? [],
    )
    if (props.initial.type) {
      activeType.value = props.initial.type
    }
  }
  else {
    loadChart()
  }
})
</script>

<template>
  <div v-loading="loading" class="mine-card welcome-panel">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
      <div class="flex items-center gap-3 flex-wrap">
        <h4 class="text-base font-medium m-0">
          统计
        </h4>
        <el-tabs v-model="activeType" class="welcome-chart-tabs" @tab-change="onTabChange">
          <el-tab-pane
            v-for="tab in typeTabs"
            :key="tab.name"
            :label="tab.label"
            :name="tab.name"
          />
        </el-tabs>
      </div>
      <el-date-picker
        v-model="dateRange"
        type="daterange"
        range-separator="至"
        start-placeholder="开始日期"
        end-placeholder="结束日期"
        :clearable="false"
        @change="onDateChange"
      />
    </div>
    <div ref="chartEl" class="h-320px w-full" />
  </div>
</template>

<style scoped lang="scss">
.welcome-chart-tabs {
  :deep(.el-tabs__header) {
    margin: 0;
  }
  :deep(.el-tabs__nav-wrap::after) {
    display: none;
  }
}
</style>
