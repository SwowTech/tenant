<script setup lang="ts">
import { useEcharts } from '@/hooks/useEcharts.ts'

const props = defineProps<{
  chartType: string
  xAxis: string[]
  data: number[]
  color?: string
}>()

const el = ref<HTMLDivElement>()
const { setOption } = useEcharts(el)

watch(
  () => [props.xAxis, props.data, props.chartType, props.color],
  () => {
    setOption({
      grid: { left: 28, right: 8, top: 8, bottom: 24 },
      xAxis: { type: 'category', data: props.xAxis, show: false },
      yAxis: { type: 'value', show: false },
      tooltip: { trigger: 'axis' },
      series: [{
        type: props.chartType === 'bar' ? 'bar' : 'line',
        data: props.data,
        smooth: true,
        itemStyle: { color: props.color || '#3469FF' },
        areaStyle: props.chartType === 'line' ? { opacity: 0.1 } : undefined,
      }],
    })
  },
  { immediate: true, deep: true },
)
</script>

<template>
  <div ref="el" class="h-[120px] w-full" />
</template>
