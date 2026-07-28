<script setup lang="ts">
import { ElMessage } from 'element-plus'
import {
  checkCloudUpgrade,
  getCloudUpgrade,
  getCloudUpgradeTask,
  startCloudUpgrade,
  type CloudUpgradeCheckVo,
  type CloudUpgradeOverviewVo,
  type CloudUpgradeTaskVo,
} from '~/base/api/cloud'
import { ResultCode } from '@/utils/ResultCode.ts'
import type { TransType } from '@/hooks/auto-imports/useTrans.ts'

defineOptions({ name: 'setting:cloud:upgrade' })

const t = (useTrans() as TransType).globalTrans
const loading = ref(false)
const checking = ref(false)
const starting = ref(false)
const overview = ref<CloudUpgradeOverviewVo | null>(null)
const checkResult = ref<CloudUpgradeCheckVo | null>(null)
const activeTask = ref<CloudUpgradeTaskVo | null>(null)
const pollTimer = ref<ReturnType<typeof setTimeout> | null>(null)

const agreements = reactive({
  official: false,
  backup: false,
  license: false,
})

const agreementTexts = computed(() => [
  t('settingUi.agreeOfficial'),
  t('settingUi.agreeBackup'),
  t('settingUi.agreeLicense'),
])

const allAgreementsChecked = computed(() => agreements.official && agreements.backup && agreements.license)

const canStart = computed(() =>
  checkResult.value?.upgrade === true
  && allAgreementsChecked.value
  && !isTaskRunning.value,
)

const isTaskRunning = computed(() => {
  const status = activeTask.value?.status
  return status === 'pending' || status === 'running'
})

function clearPollTimer() {
  if (pollTimer.value) {
    clearTimeout(pollTimer.value)
    pollTimer.value = null
  }
}

function resetAgreements() {
  agreements.official = false
  agreements.backup = false
  agreements.license = false
}

function schedulePoll(id: string, interval: number) {
  clearPollTimer()
  pollTimer.value = setTimeout(() => {
    pollTask(id, interval)
  }, interval)
}

async function pollTask(id: string, interval = 1000) {
  try {
    const res: any = await getCloudUpgradeTask(id)
    if (res.code !== ResultCode.SUCCESS || !res.data) {
      schedulePoll(id, interval)
      return
    }
    activeTask.value = res.data
    const status = res.data.status as string
    if (status === 'success') {
      clearPollTimer()
      ElMessage.success(t('settingUi.upgradeSuccessMsg'))
      checkResult.value = null
      resetAgreements()
      await loadOverview()
      return
    }
    if (status === 'failed') {
      clearPollTimer()
      ElMessage.error(res.data.message || t('settingUi.upgradeFailed'))
      await loadOverview()
      return
    }
    schedulePoll(id, interval)
  }
  catch {
    schedulePoll(id, interval)
  }
}

function resumeTaskIfRunning(task: CloudUpgradeTaskVo | null | undefined) {
  if (!task) {
    return
  }
  const status = task.status
  if (status === 'pending' || status === 'running') {
    activeTask.value = task
    if (!pollTimer.value) {
      pollTask(task.id)
    }
  }
}

async function loadOverview() {
  loading.value = true
  try {
    const res: any = await getCloudUpgrade()
    if (res.code !== ResultCode.SUCCESS || !res.data) {
      return
    }
    overview.value = res.data
    const latest = res.data.latest_task as CloudUpgradeTaskVo | null | undefined
    if (latest && (latest.status === 'pending' || latest.status === 'running')) {
      resumeTaskIfRunning(latest)
      return
    }
    if (!isTaskRunning.value) {
      activeTask.value = latest ?? null
    }
  }
  finally {
    loading.value = false
  }
}

async function handleCheck() {
  checking.value = true
  try {
    const res: any = await checkCloudUpgrade()
    if (res.code !== ResultCode.SUCCESS || !res.data) {
      return
    }
    checkResult.value = res.data
    resetAgreements()
    if (!res.data.upgrade) {
      activeTask.value = null
    }
  }
  finally {
    checking.value = false
  }
}

async function handleStart() {
  if (!canStart.value) {
    return
  }
  starting.value = true
  try {
    const res: any = await startCloudUpgrade({ agreed: true })
    if (res.code !== ResultCode.SUCCESS || !res.data?.task_id) {
      return
    }
    const interval = Number(res.data.poll_interval) || 1000
    activeTask.value = {
      id: res.data.task_id,
      status: 'pending',
      step: 'pending',
      progress: 0,
      message: t('settingUi.startingUpgrade'),
      target_version: checkResult.value?.version || '',
    }
    await pollTask(res.data.task_id, interval)
  }
  finally {
    starting.value = false
  }
}

onMounted(loadOverview)
onBeforeUnmount(clearPollTimer)
</script>

<template>
  <div v-loading="loading" class="mine-layout p-3">
    <el-alert
      class="cloud-upgrade__alert"
      type="warning"
      :closable="false"
      show-icon
      :title="t('settingUi.afterUpgradeTip')"
    />

    <div class="mine-card cloud-upgrade">
      <section class="cloud-upgrade__section">
        <h2 class="cloud-upgrade__title">
          {{ t('settingUi.cloudUpgrade') }}
        </h2>
        <div class="cloud-upgrade__row">
          <span class="cloud-upgrade__label">{{ t('settingUi.currentVersion') }}</span>
          <span>{{ overview?.current_version || '-' }}</span>
        </div>
        <div class="cloud-upgrade__actions">
          <el-button :loading="checking" :disabled="isTaskRunning" @click="handleCheck">
            {{ t('settingUi.checkNow') }}
          </el-button>
        </div>
      </section>

      <section v-if="checkResult && !isTaskRunning" class="cloud-upgrade__section">
        <template v-if="checkResult.upgrade">
          <div class="cloud-upgrade__row">
            <span class="cloud-upgrade__label">{{ t('settingUi.targetVersion') }}</span>
            <span>{{ checkResult.version }}</span>
          </div>
          <div class="cloud-upgrade__row cloud-upgrade__row--top">
            <span class="cloud-upgrade__label">{{ t('settingUi.changelog') }}</span>
            <pre class="cloud-upgrade__changelog">{{ checkResult.changelog || t('settingUi.noChangelog') }}</pre>
          </div>
          <div class="cloud-upgrade__row">
            <span class="cloud-upgrade__label">{{ t('settingUi.counts') }}</span>
            <span>
              {{ t('settingUi.countsSummary', {
                overlay: checkResult.counts?.overlay_files ?? 0,
                migrations: checkResult.counts?.migrations ?? 0,
                scripts: checkResult.counts?.scripts ?? 0,
              }) }}
            </span>
          </div>

          <div class="cloud-upgrade__agreements">
            <el-checkbox v-model="agreements.official">
              {{ agreementTexts[0] }}
            </el-checkbox>
            <el-checkbox v-model="agreements.backup">
              {{ agreementTexts[1] }}
            </el-checkbox>
            <el-checkbox v-model="agreements.license">
              {{ agreementTexts[2] }}
            </el-checkbox>
          </div>

          <div class="cloud-upgrade__actions">
            <el-button
              type="primary"
              :loading="starting"
              :disabled="!canStart"
              @click="handleStart"
            >
              {{ t('settingUi.oneClickUpdate') }}
            </el-button>
          </div>
        </template>
        <el-result v-else icon="success" :title="checkResult.message || t('settingUi.alreadyLatest')" />
      </section>

      <section v-if="isTaskRunning && activeTask" class="cloud-upgrade__section">
        <h2 class="cloud-upgrade__title">
          {{ t('settingUi.upgrading') }}
        </h2>
        <div class="cloud-upgrade__row">
          <span class="cloud-upgrade__label">{{ t('settingUi.targetVersion') }}</span>
          <span>{{ activeTask.target_version || '-' }}</span>
        </div>
        <div class="cloud-upgrade__row">
          <span class="cloud-upgrade__label">{{ t('settingUi.currentStep') }}</span>
          <span>{{ activeTask.message || activeTask.step || '-' }}</span>
        </div>
        <el-progress
          class="cloud-upgrade__progress"
          :percentage="activeTask.progress"
          :status="activeTask.status === 'failed' ? 'exception' : undefined"
        />
      </section>

      <section
        v-else-if="activeTask && (activeTask.status === 'success' || activeTask.status === 'failed')"
        class="cloud-upgrade__section"
      >
        <el-result
          :icon="activeTask.status === 'success' ? 'success' : 'error'"
          :title="activeTask.status === 'success' ? t('settingUi.upgradeSuccess') : t('settingUi.upgradeFailed')"
          :sub-title="activeTask.message"
        />
      </section>
    </div>
  </div>
</template>

<style scoped lang="scss">
.cloud-upgrade {
  padding: 24px;
  min-height: 360px;

  &__alert {
    margin-bottom: 16px;
  }

  &__section {
    & + & {
      margin-top: 32px;
      padding-top: 24px;
      border-top: 1px solid var(--el-border-color-lighter);
    }
  }

  &__title {
    margin: 0 0 16px;
    font-size: 18px;
    font-weight: 500;
    color: var(--el-text-color-primary);
  }

  &__row {
    display: flex;
    gap: 16px;
    padding: 8px 0;
    line-height: 1.6;

    &--top {
      align-items: flex-start;
    }
  }

  &__label {
    flex: 0 0 96px;
    color: var(--el-text-color-secondary);
  }

  &__changelog {
    flex: 1;
    margin: 0;
    white-space: pre-wrap;
    word-break: break-word;
    font-family: inherit;
    line-height: 1.6;
    color: var(--el-text-color-primary);
  }

  &__agreements {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
    margin-top: 16px;
  }

  &__actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
    margin-top: 16px;
  }

  &__progress {
    margin-top: 16px;
  }
}
</style>
