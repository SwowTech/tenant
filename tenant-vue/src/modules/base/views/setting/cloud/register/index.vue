<script setup lang="ts">
import { getCloudAuthUrl, getCloudSiteInfo, type CloudSiteInfoVo } from '~/base/api/cloud'
import { ResultCode } from '@/utils/ResultCode.ts'
import type { TransType } from '@/hooks/auto-imports/useTrans.ts'

defineOptions({ name: 'setting:cloud:register' })

const t = (useTrans() as TransType).globalTrans
const loading = ref(false)
const info = ref<CloudSiteInfoVo | null>(null)
const iframeSrc = ref('')
const showIframe = ref(false)

async function loadSiteInfo() {
  loading.value = true
  try {
    const res: any = await getCloudSiteInfo()
    if (res.code !== ResultCode.SUCCESS || !res.data) {
      return
    }
    info.value = res.data
    showIframe.value = false
    if (!res.data.bound) {
      iframeSrc.value = res.data.auth?.url || ''
      if (!iframeSrc.value) {
        const authRes: any = await getCloudAuthUrl()
        if (authRes.code === ResultCode.SUCCESS && authRes.data?.url) {
          iframeSrc.value = authRes.data.url
        }
      }
    }
  }
  finally {
    loading.value = false
  }
}

function reopenCloudPlatform() {
  if (!info.value?.auth?.url) {
    return
  }
  iframeSrc.value = info.value.auth.url
  showIframe.value = true
}

onMounted(loadSiteInfo)
</script>

<template>
  <div v-loading="loading" class="mine-layout p-3">
    <div class="mine-card cloud-register">
      <template v-if="info?.bound && !showIframe">
        <h2 class="cloud-register__title">
          {{ t('settingUi.siteBound') }}
        </h2>
        <div class="cloud-register__row">
          <span class="cloud-register__label">{{ t('settingUi.siteId') }}</span>
          <span>{{ info.key }}</span>
        </div>
        <div class="cloud-register__row">
          <span class="cloud-register__label">{{ t('settingUi.commKey') }}</span>
          <span>{{ info.token_masked }}</span>
        </div>
        <div class="cloud-register__row">
          <span class="cloud-register__label">{{ t('settingUi.siteUrl') }}</span>
          <span>{{ info.url }}</span>
        </div>
        <div class="cloud-register__row">
          <span class="cloud-register__label">{{ t('settingUi.boundAccount') }}</span>
          <span>{{ info.username }}</span>
        </div>
        <div class="cloud-register__row">
          <span class="cloud-register__label">{{ t('settingUi.phone') }}</span>
          <span>{{ info.phone || '-' }}</span>
        </div>
        <div class="cloud-register__row">
          <span class="cloud-register__label">{{ t('settingUi.email') }}</span>
          <span>{{ info.email || '-' }}</span>
        </div>
        <div class="cloud-register__row">
          <span class="cloud-register__label">{{ t('settingUi.boundAt') }}</span>
          <span>{{ info.bound_at }}</span>
        </div>
        <el-button class="mt-4" type="primary" @click="reopenCloudPlatform">
          {{ t('settingUi.reopenCloud') }}
        </el-button>
        <el-button class="mt-4 ml-2" type="success" @click="$router.push('/setting/cloud/store')">
          {{ t('settingUi.enterStore') }}
        </el-button>
        <el-button class="mt-4 ml-2" type="success" @click="$router.push('/setting/cloud/store')">
          {{ t('settingUi.enterStore') }}
        </el-button>
      </template>
      <iframe
        v-else-if="iframeSrc"
        :src="iframeSrc"
        class="cloud-register__iframe"
        :title="t('settingUi.cloudRegister')"
      />
      <el-empty v-else-if="!loading" :description="t('settingUi.cannotLoadCloudUrl')" />
    </div>
  </div>
</template>

<style scoped lang="scss">
.cloud-register {
  padding: 24px;
  min-height: 360px;

  &__title {
    margin: 0 0 24px;
    font-size: 18px;
    font-weight: 500;
    color: var(--el-text-color-primary);
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

  &__iframe {
    width: 100%;
    height: 70vh;
    border: 0;
  }
}
</style>
