<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { getWelcomeSystemCheck } from '~/base/api/welcome'
import { useMessage } from '@/hooks/useMessage.ts'
import type { TransType } from '@/hooks/auto-imports/useTrans.ts'

defineOptions({ name: 'systemCheck' })

const t = (useTrans() as TransType).globalTrans
const loading = ref(false)
const result = ref<{ check_num: number, check_wrong_num: number, items: any[], report_text?: string } | null>(null)
const msg = useMessage()

function checkName(row: any): string {
  const key = row?.name || row?.key || ''
  const full = `settingTools.checkNameKey.${key}`
  const out = t(full, row?.params || {})
  return out === full ? (row?.name || row?.key || '') : out
}

function checkSuggestion(row: any): string {
  const key = row?.suggestion || ''
  if (!key) {
    return ''
  }
  // Raw exception detail from DB/Redis
  if (key === 'check_db' || key === 'check_redis') {
    const detail = row?.params?.detail
    const base = t(`settingTools.checkSuggest.${key}`)
    return detail ? `${base}: ${detail}` : base
  }
  const full = `settingTools.checkSuggest.${key}`
  const out = t(full, row?.params || {})
  return out === full ? key : out
}

function checkAction(row: any): string {
  const key = row?.action || ''
  if (!key) {
    return ''
  }
  const full = `settingTools.checkAction.${key}`
  const out = t(full)
  return out === full ? key : out
}

async function runCheck() {
  loading.value = true
  try {
    const { data } = await getWelcomeSystemCheck()
    result.value = data
  }
  finally {
    loading.value = false
  }
}

async function copyReport() {
  const text = result.value?.report_text || ''
  if (!text) {
    msg.warning(t('settingTools.noReport'))
    return
  }
  try {
    await navigator.clipboard.writeText(text)
    msg.success(t('settingTools.copiedReport'))
  }
  catch {
    msg.error(t('settingTools.copyFailed'))
  }
}

onMounted(runCheck)
</script>

<template>
  <div class="mine-layout p-3">
    <div class="flex justify-between items-center mb-3">
      <h2 class="text-lg">
        {{ t('settingTools.systemCheckTitle') }}
      </h2>
      <el-button type="primary" :loading="loading" @click="runCheck">
        {{ t('settingTools.runCheck') }}
      </el-button>
      <el-button :disabled="!result?.report_text" @click="copyReport">
        {{ t('settingTools.copyResult') }}
      </el-button>
    </div>
    <p v-if="result" class="mb-3">
      {{ t('settingTools.checkSummary', { total: result.check_num, wrong: result.check_wrong_num }) }}
    </p>
    <el-table v-loading="loading" :data="result?.items || []" border>
      <el-table-column :label="t('settingTools.checkName')" min-width="160">
        <template #default="{ row }">
          {{ checkName(row) }}
        </template>
      </el-table-column>
      <el-table-column :label="t('settingTools.checkResult')" width="120">
        <template #default="{ row }">
          <span :style="{ color: row.ok ? '#67c23a' : '#f56c6c' }">
            {{ row.ok ? t('settingTools.checkOk') : t('settingTools.checkFail') }}
          </span>
        </template>
      </el-table-column>
      <el-table-column :label="t('settingTools.suggestion')" min-width="200">
        <template #default="{ row }">
          {{ checkSuggestion(row) }}
        </template>
      </el-table-column>
      <el-table-column :label="t('settingTools.suggestedAction')" width="140">
        <template #default="{ row }">
          {{ checkAction(row) }}
        </template>
      </el-table-column>
      <el-table-column :label="t('settingTools.solution')" min-width="160">
        <template #default="{ row }">
          <a v-if="row.solution" :href="row.solution" target="_blank" rel="noopener">{{ t('settingTools.view') }}</a>
        </template>
      </el-table-column>
    </el-table>
  </div>
</template>
