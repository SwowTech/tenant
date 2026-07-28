<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 - Please view the LICENSE file that was distributed with this source code,
 - For the full copyright and license information.
 - Thank you very much for using MineAdmin.
 -
 - @Author X.Mo<root@imoi.cn>
 - @Link   https://github.com/mineadmin
-->
<script setup lang="tsx">
import { logList } from '$/mine-admin/crontab/api/crontab.ts'
import type { MaProTableExpose, MaProTableOptions, MaProTableSchema } from '@mineadmin/pro-table'
import { useProTableToolbar } from '@mineadmin/pro-table'
import type { Ref } from 'vue'
import type { TransType } from '@/hooks/auto-imports/useTrans.ts'
import getTableColumns from '$/mine-admin/crontab/views/data/getTableColumns.tsx'

const { crontabId } = defineProps<{
  crontabId: number
}>()

const { getAll, hide } = useProTableToolbar()
const proTableRef = ref<MaProTableExpose>() as Ref<MaProTableExpose>
const formRef = ref()
const selections = ref<any[]>([])
const i18n = useTrans() as TransType
const t = i18n.globalTrans

// 参数配置
const options = ref<MaProTableOptions>({
  // 表格距离底部的像素偏移适配
  adaptionOffsetBottom: 161,
  header: {
    show: false,
    mainTitle: () => '授权记录',
  },
  // 表格参数
  tableOptions: {
    adaption: false,
    headerAlign: 'left', // 表头对齐方式
    columnAlign: 'left',
    size: 'small',
    on: {
      // 表格选择事件
      onSelectionChange: (selection: any[]) => selections.value = selection,
    },
  },
  searchOptions: {
    show: false,
  },
  // 请求配置
  requestOptions: {
    api: logList,
    requestPage: {
      size: 50,
    },
    requestParams: {
      crontab_id: crontabId,
    },
  },
})

// 架构配置
const schema = ref<MaProTableSchema>({
  // 表格列
  tableColumns: getTableColumns(formRef, t),
})

onMounted(() => {
  hide('mineProTableSearch')
})
</script>

<template>
  <div class="mine-layout">
    <MaProTable ref="proTableRef" :options="options" :schema="schema" />
  </div>
</template>

<style scoped lang="scss"></style>
