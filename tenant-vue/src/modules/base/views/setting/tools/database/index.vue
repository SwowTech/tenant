<script setup lang="ts">
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  backupDatabaseStep,
  deleteDatabaseBackup,
  getDatabaseBackups,
  getDatabaseTables,
  optimizeDatabaseTables,
  restoreDatabaseStep,
  type DbBackupVo,
  type DbTableVo,
} from '~/base/api/setting'
import { ResultCode } from '@/utils/ResultCode.ts'
import type { TransType } from '@/hooks/auto-imports/useTrans.ts'

defineOptions({ name: 'setting:tools:database' })

const t = (useTrans() as TransType).globalTrans
const activeTab = ref('backup')
const loading = ref(false)
const tables = ref<DbTableVo[]>([])
const backups = ref<DbBackupVo[]>([])
const selectedOptimize = ref<string[]>([])
const backupRunning = ref(false)
const backupMessage = ref('')
const restoreRunning = ref(false)
const restoreMessage = ref('')

const optimizeTableRef = ref()

async function loadTables() {
  loading.value = true
  try {
    const res: any = await getDatabaseTables()
    if (res.code === ResultCode.SUCCESS) {
      tables.value = res.data?.list || []
      selectedOptimize.value = tables.value.filter(t => t.need_optimize).map(t => t.name)
      await nextTick()
      const table = optimizeTableRef.value
      if (table) {
        table.clearSelection()
        tables.value.forEach((row) => {
          if (row.need_optimize) {
            table.toggleRowSelection(row, true)
          }
        })
      }
    }
  }
  finally {
    loading.value = false
  }
}

async function loadBackups() {
  const res: any = await getDatabaseBackups()
  if (res.code === ResultCode.SUCCESS) {
    backups.value = res.data?.list || []
  }
}

async function onTabChange(name: string | number) {
  if (name === 'optimize' || name === 'backup') {
    await loadTables()
  }
  if (name === 'restore') {
    await loadBackups()
  }
}

async function runOptimize() {
  if (!selectedOptimize.value.length) {
    ElMessage.warning(t('settingTools.selectTables'))
    return
  }
  loading.value = true
  try {
    const res: any = await optimizeDatabaseTables(selectedOptimize.value)
    if (res.code === ResultCode.SUCCESS) {
      ElMessage.success(t('settingTools.optimizedCount', { count: res.data?.optimized?.length || 0 }))
      await loadTables()
    }
    else {
      ElMessage.error(res.message || t('settingTools.optimizeFailed'))
    }
  }
  finally {
    loading.value = false
  }
}

async function runBackup() {
  await ElMessageBox.confirm(
    t('settingTools.backupConfirmMsg'),
    t('settingTools.backupConfirmTitle'),
    { type: 'warning' },
  )
  backupRunning.value = true
  backupMessage.value = t('settingTools.backupPreparing')
  let state: Record<string, unknown> = { status: 1, start: 1 }
  try {
    while (true) {
      const res: any = await backupDatabaseStep(state)
      if (res.code !== ResultCode.SUCCESS) {
        ElMessage.error(res.message || t('settingTools.backupFailed'))
        break
      }
      const data = res.data || {}
      backupMessage.value = data.message || ''
      if (!data.continue) {
        ElMessage.success(data.message || t('settingTools.backupDone'))
        await loadBackups()
        activeTab.value = 'restore'
        break
      }
      state = {
        status: 1,
        last_table: data.last_table,
        index: data.index,
        series: data.series,
        folder_suffix: data.folder_suffix,
        volume_suffix: data.volume_suffix,
      }
    }
  }
  catch (e: any) {
    if (e !== 'cancel') {
      ElMessage.error(e?.message || t('settingTools.backupInterrupted'))
    }
  }
  finally {
    backupRunning.value = false
  }
}

async function runRestore(row: DbBackupVo) {
  await ElMessageBox.confirm(
    t('settingTools.restoreConfirmMsg', { time: row.time_text }),
    t('settingTools.restoreConfirmTitle'),
    { type: 'error', confirmButtonText: t('settingTools.restoreConfirmBtn') },
  )
  restoreRunning.value = true
  restoreMessage.value = t('settingTools.restoring')
  let volumeIndex = 0
  try {
    while (true) {
      const res: any = await restoreDatabaseStep({ dirname: row.bakdir, volume_index: volumeIndex })
      if (res.code !== ResultCode.SUCCESS) {
        ElMessage.error(res.message || t('settingTools.restoreFailed'))
        break
      }
      const data = res.data || {}
      restoreMessage.value = data.message || ''
      if (!data.continue) {
        ElMessage.success(data.message || t('settingTools.restoreDone'))
        break
      }
      volumeIndex = Number(data.volume_index || volumeIndex + 1)
    }
  }
  finally {
    restoreRunning.value = false
  }
}

async function removeBackup(row: DbBackupVo) {
  await ElMessageBox.confirm(
    t('settingTools.deleteBackupMsg', { name: row.bakdir }),
    t('settingTools.deleteBackupTitle'),
    { type: 'warning' },
  )
  const res: any = await deleteDatabaseBackup(row.bakdir)
  if (res.code === ResultCode.SUCCESS) {
    ElMessage.success(t('settingTools.deleted'))
    await loadBackups()
  }
  else {
    ElMessage.error(res.message || t('settingTools.deleteFailed'))
  }
}

onMounted(async () => {
  await loadTables()
  await loadBackups()
})
</script>

<template>
  <div v-loading="loading || backupRunning || restoreRunning" class="mine-layout p-3">
    <div class="mine-card p-4">
      <div class="mb-4 text-lg font-medium">
        {{ t('settingTools.databaseTitle') }}
      </div>
      <el-alert
        class="mb-4"
        type="warning"
        :closable="false"
        :title="t('settingTools.databaseAlert')"
      />

      <el-tabs v-model="activeTab" @tab-change="onTabChange">
        <el-tab-pane :label="t('settingTools.tabBackup')" name="backup">
          <p class="mb-4 text-sm text-[var(--el-text-color-secondary)]">
            {{ t('settingTools.backupDesc') }}
            <code>runtime/db_backup</code>.
          </p>
          <div class="mb-3 text-sm">
            {{ t('settingTools.visibleTables', { count: tables.length }) }}
          </div>
          <el-button type="primary" :loading="backupRunning" @click="runBackup">
            {{ t('settingTools.startBackup') }}
          </el-button>
          <div v-if="backupMessage" class="mt-3 text-sm text-[var(--el-color-primary)]">
            {{ backupMessage }}
          </div>
        </el-tab-pane>

        <el-tab-pane :label="t('settingTools.tabRestore')" name="restore">
          <p class="mb-4 text-sm text-[var(--el-text-color-secondary)]">
            {{ t('settingTools.restoreDesc') }}
          </p>
          <div v-if="restoreMessage" class="mb-3 text-sm text-[var(--el-color-primary)]">
            {{ restoreMessage }}
          </div>
          <el-table :data="backups" stripe border>
            <el-table-column prop="bakdir" :label="t('settingTools.backupName')" min-width="180" />
            <el-table-column prop="time_text" :label="t('settingTools.backupTime')" width="170" />
            <el-table-column prop="volume" :label="t('settingTools.volumeCount')" width="100" />
            <el-table-column prop="size" :label="t('settingTools.size')" width="100" />
            <el-table-column prop="prefix" :label="t('settingTools.tablePrefix')" width="120" />
            <el-table-column :label="t('crud.operation')" width="180" fixed="right">
              <template #default="{ row }">
                <el-button link type="primary" :disabled="restoreRunning" @click="runRestore(row)">
                  {{ t('settingTools.restoreThis') }}
                </el-button>
                <el-button link type="danger" @click="removeBackup(row)">
                  {{ t('crud.delete') }}
                </el-button>
              </template>
            </el-table-column>
          </el-table>
          <el-empty v-if="!backups.length" :description="t('settingTools.noBackups')" />
        </el-tab-pane>

        <el-tab-pane :label="t('settingTools.tabOptimize')" name="optimize">
          <el-alert
            class="mb-4"
            type="info"
            :closable="false"
            :title="t('settingTools.optimizeAlert')"
          />
          <div class="mb-3">
            <el-button type="primary" :disabled="!selectedOptimize.length" @click="runOptimize">
              {{ t('settingTools.startOptimize') }}
            </el-button>
            <el-button @click="selectedOptimize = tables.map(row => row.name)">
              {{ t('settingTools.selectAll') }}
            </el-button>
            <el-button @click="selectedOptimize = []">
              {{ t('settingTools.clearSelection') }}
            </el-button>
          </div>
          <el-table
            ref="optimizeTableRef"
            :data="tables"
            row-key="name"
            stripe
            border
            @selection-change="(rows: DbTableVo[]) => selectedOptimize = rows.map(r => r.name)"
          >
            <el-table-column type="selection" width="48" :selectable="() => true" />
            <el-table-column prop="name" :label="t('settingTools.tableName')" min-width="180" />
            <el-table-column prop="engine" :label="t('settingTools.engine')" width="90" />
            <el-table-column prop="rows" :label="t('settingTools.rows')" width="100" />
            <el-table-column prop="data" :label="t('settingTools.dataSize')" width="100" />
            <el-table-column prop="index" :label="t('settingTools.indexSize')" width="100" />
            <el-table-column prop="free" :label="t('settingTools.freeSize')" width="100" />
          </el-table>
        </el-tab-pane>
      </el-tabs>
    </div>
  </div>
</template>
