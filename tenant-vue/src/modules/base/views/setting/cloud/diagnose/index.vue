<script setup lang="ts">
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  getCloudDiagnose,
  getCloudDiagnoseToken,
  pingCloudSaas,
  resetCloudDiagnose,
  type CloudDiagnoseSaasVo,
  type CloudDiagnoseVo,
} from '~/base/api/cloud'
import { ResultCode } from '@/utils/ResultCode.ts'
import type { TransType } from '@/hooks/auto-imports/useTrans.ts'

defineOptions({ name: 'setting:cloud-diagnose' })

const t = (useTrans() as TransType).globalTrans
const router = useRouter()
const loading = ref(false)
const pingLoading = ref(false)
const resetting = ref(false)
const info = ref<CloudDiagnoseVo | null>(null)
const saas = ref<CloudDiagnoseSaasVo | null>(null)
const tokenPlain = ref('')
const tokenRevealTimer = ref<ReturnType<typeof setTimeout> | null>(null)

function clearTokenRevealTimer() {
  if (tokenRevealTimer.value) {
    clearTimeout(tokenRevealTimer.value)
    tokenRevealTimer.value = null
  }
}

function clearTokenReveal() {
  clearTokenRevealTimer()
  tokenPlain.value = ''
}

async function loadDiagnose() {
  loading.value = true
  try {
    const res: any = await getCloudDiagnose()
    if (res.code !== ResultCode.SUCCESS || !res.data) {
      return
    }
    info.value = res.data
    saas.value = res.data.network?.saas ?? null
    clearTokenReveal()
  }
  finally {
    loading.value = false
  }
}

async function refreshPing() {
  pingLoading.value = true
  try {
    const res: any = await pingCloudSaas()
    if (res.code === ResultCode.SUCCESS && res.data) {
      saas.value = res.data
    }
  }
  finally {
    pingLoading.value = false
  }
}

async function revealToken() {
  if (!info.value?.bound) {
    ElMessage.warning(t('settingUi.notRegisteredWarn'))
    return
  }
  try {
    await ElMessageBox.confirm(
      t('settingUi.viewTokenWarn'),
      t('settingUi.viewTokenTitle'),
      {
        type: 'warning',
        confirmButtonText: t('crud.ok'),
        cancelButtonText: t('crud.cancel'),
      },
    )
  }
  catch {
    return
  }

  const res: any = await getCloudDiagnoseToken()
  if (res.code !== ResultCode.SUCCESS || !res.data?.token) {
    return
  }
  clearTokenRevealTimer()
  tokenPlain.value = res.data.token
  tokenRevealTimer.value = setTimeout(() => {
    tokenPlain.value = ''
    tokenRevealTimer.value = null
  }, 30000)
}

async function handleReset() {
  if (!info.value?.bound) {
    ElMessage.warning(t('settingUi.notRegisteredMsg'))
    return
  }
  try {
    await ElMessageBox.confirm(
      t('settingUi.resetWarn'),
      t('settingUi.resetTitle'),
      {
        type: 'warning',
        confirmButtonText: t('settingUi.confirmReset'),
        cancelButtonText: t('crud.cancel'),
      },
    )
  }
  catch {
    return
  }

  resetting.value = true
  try {
    const res: any = await resetCloudDiagnose()
    if (res.code !== ResultCode.SUCCESS || !res.data) {
      return
    }
    ElMessage.success(res.data.message || t('settingUi.resetOk'))
    await router.push(res.data.register_path || '/setting/cloud/register')
  }
  finally {
    resetting.value = false
  }
}

function goRegister() {
  router.push(info.value?.register_path || '/setting/cloud/register')
}

onMounted(loadDiagnose)
onBeforeUnmount(clearTokenReveal)
</script>

<template>
  <div v-loading="loading" class="mine-layout p-3">
    <div class="mine-card cloud-diagnose">
      <section class="cloud-diagnose__section">
        <h2 class="cloud-diagnose__title">
          {{ t('settingUi.cloudStatus') }}
        </h2>
        <div class="cloud-diagnose__row">
          <span class="cloud-diagnose__label">{{ t('settingUi.siteUrl') }}</span>
          <span>{{ info?.site?.url || '-' }}</span>
        </div>
        <div class="cloud-diagnose__row">
          <span class="cloud-diagnose__label">{{ t('settingUi.siteId') }}</span>
          <span>{{ info?.site?.key || t('settingUi.notRegistered') }}</span>
        </div>
        <div class="cloud-diagnose__row">
          <span class="cloud-diagnose__label">{{ t('settingUi.commKey') }}</span>
          <span class="cloud-diagnose__token">
            {{ tokenPlain || info?.site?.token_masked || '-' }}
            <el-button
              v-if="info?.bound"
              link
              type="primary"
              class="ml-2"
              @click="revealToken"
            >
              {{ t('settingUi.viewAll') }}
            </el-button>
          </span>
        </div>
        <div class="cloud-diagnose__row">
          <span class="cloud-diagnose__label">{{ t('settingUi.systemVersion') }}</span>
          <span>{{ info?.site?.version || '-' }}</span>
        </div>
        <div class="cloud-diagnose__actions">
          <el-button
            type="danger"
            :disabled="!info?.bound"
            :loading="resetting"
            @click="handleReset"
          >
            {{ t('settingUi.resetSiteCreds') }}
          </el-button>
          <el-button v-if="!info?.bound" type="primary" link @click="goRegister">
            {{ t('settingUi.goRegister') }}
          </el-button>
        </div>
      </section>

      <section class="cloud-diagnose__section">
        <div class="cloud-diagnose__section-head">
          <h2 class="cloud-diagnose__title">
            {{ t('settingUi.networkDiag') }}
          </h2>
          <el-button :loading="pingLoading" @click="refreshPing">
            {{ t('settingUi.refreshProbe') }}
          </el-button>
        </div>
        <div class="cloud-diagnose__row">
          <span class="cloud-diagnose__label">{{ t('settingUi.serverTime') }}</span>
          <span>{{ info?.network?.server_time || '-' }}</span>
        </div>
        <div class="cloud-diagnose__row">
          <span class="cloud-diagnose__label">{{ t('settingUi.saasReachable') }}</span>
          <span>
            <template v-if="saas">
              <span :class="saas.ok ? 'cloud-diagnose__ok' : 'cloud-diagnose__fail'">
                {{ saas.ok ? t('settingUi.normal') : t('settingUi.abnormal') }}
              </span>
              <span v-if="saas.latency_ms >= 0" class="ml-2">({{ saas.latency_ms }} ms)</span>
              <span v-if="!saas.ok && saas.message" class="cloud-diagnose__fail ml-2">{{ saas.message }}</span>
            </template>
            <template v-else>-</template>
          </span>
        </div>
        <div v-if="saas?.url" class="cloud-diagnose__row">
          <span class="cloud-diagnose__label">{{ t('settingUi.probeUrl') }}</span>
          <span class="cloud-diagnose__url">{{ saas.url }}</span>
        </div>
      </section>
    </div>
  </div>
</template>

<style scoped lang="scss">
.cloud-diagnose {
  padding: 24px;
  min-height: 360px;

  &__section {
    & + & {
      margin-top: 32px;
      padding-top: 24px;
      border-top: 1px solid var(--el-border-color-lighter);
    }
  }

  &__section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 16px;
  }

  &__title {
    margin: 0 0 16px;
    font-size: 18px;
    font-weight: 500;
    color: var(--el-text-color-primary);
  }

  &__section-head &__title {
    margin-bottom: 0;
  }

  &__row {
    display: flex;
    gap: 16px;
    padding: 8px 0;
    line-height: 1.6;
  }

  &__label {
    flex: 0 0 96px;
    color: var(--el-text-color-secondary);
  }

  &__token {
    word-break: break-all;
  }

  &__url {
    word-break: break-all;
    color: var(--el-text-color-secondary);
    font-size: 13px;
  }

  &__actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
    margin-top: 16px;
  }

  &__ok {
    color: var(--el-color-success);
  }

  &__fail {
    color: var(--el-color-danger);
  }
}
</style>
