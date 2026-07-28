<script setup lang="ts">
import { getSystemInfo, type SystemInfoVo } from '~/base/api/setting'
import { ResultCode } from '@/utils/ResultCode.ts'
import type { TransType } from '@/hooks/auto-imports/useTrans.ts'

defineOptions({ name: 'setting:systeminfo' })

const t = (useTrans() as TransType).globalTrans
const loading = ref(false)
const attachLoading = ref(false)

const info = reactive<SystemInfoVo>({
  app_version: '-',
  family: '-',
  os: '-',
  php: '-',
  sapi: '-',
  mysql_version: '-',
  upload_max: '-',
  db_size: '-',
  attach_url: '-',
  attach_size: '',
  copyright: {
    name: 'swow.tech',
    url: 'https://swow.tech',
  },
})

async function load() {
  loading.value = true
  try {
    const res: any = await getSystemInfo()
    if (res.code === ResultCode.SUCCESS && res.data) {
      Object.assign(info, res.data)
      if (res.data.copyright) {
        Object.assign(info.copyright, res.data.copyright)
      }
      if (!info.attach_size) {
        info.attach_size = t('settingUi.clickToView')
      }
    }
  }
  finally {
    loading.value = false
  }
}

async function loadAttachSize() {
  if (attachLoading.value) {
    return
  }
  attachLoading.value = true
  info.attach_size = t('settingUi.calculating')
  try {
    const res: any = await getSystemInfo({ attach_size: 1 })
    if (res.code === ResultCode.SUCCESS && res.data?.attach_size) {
      info.attach_size = res.data.attach_size
    }
    else {
      info.attach_size = t('settingUi.attachUnavailable')
    }
  }
  catch {
    info.attach_size = t('settingUi.attachUnavailable')
  }
  finally {
    attachLoading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div v-loading="loading" class="mine-layout p-3">
    <div class="mine-card setting-page">
      <section class="info-block">
        <div class="info-block__bar">
          {{ t('settingUi.systemInfo') }}
        </div>
        <el-descriptions :column="1" border class="info-desc">
          <el-descriptions-item :label="t('settingUi.appVersion')">
            {{ info.app_version }}
          </el-descriptions-item>
          <el-descriptions-item :label="t('settingUi.productFamily')">
            {{ info.family }}
          </el-descriptions-item>
          <el-descriptions-item :label="t('settingUi.serverOs')">
            {{ info.os }}
          </el-descriptions-item>
          <el-descriptions-item :label="t('settingUi.phpVersion')">
            PHP Version {{ info.php }}
          </el-descriptions-item>
          <el-descriptions-item :label="t('settingUi.serverSoftware')">
            {{ info.sapi }}
          </el-descriptions-item>
          <el-descriptions-item :label="t('settingUi.mysqlVersion')">
            {{ info.mysql_version }}
          </el-descriptions-item>
          <el-descriptions-item :label="t('settingUi.uploadLimit')">
            {{ info.upload_max }}
          </el-descriptions-item>
          <el-descriptions-item :label="t('settingUi.dbSize')">
            {{ info.db_size }}
          </el-descriptions-item>
          <el-descriptions-item :label="t('settingUi.attachRoot')">
            {{ info.attach_url }}
          </el-descriptions-item>
          <el-descriptions-item :label="t('settingUi.attachSize')">
            <a
              href="javascript:void(0)"
              class="info-link"
              @click.prevent="loadAttachSize"
            >
              {{ info.attach_size || t('settingUi.clickToView') }}
            </a>
          </el-descriptions-item>
        </el-descriptions>
      </section>

      <section class="info-block mt-4">
        <div class="info-block__bar">
          {{ t('settingUi.team') }}
        </div>
        <el-descriptions :column="1" border class="info-desc">
          <el-descriptions-item :label="t('settingUi.copyright')">
            <a
              :href="info.copyright?.url || 'https://swow.tech'"
              target="_blank"
              rel="noopener noreferrer"
              class="info-link"
            >
              <b>{{ info.copyright?.name || 'swow.tech' }}</b>
            </a>
          </el-descriptions-item>
          <el-descriptions-item :label="t('settingUi.relatedLinks')">
            <a
              href="https://www.swow.tech"
              target="_blank"
              rel="noopener noreferrer"
              class="info-link"
            >
              www.swow.tech
            </a>
          </el-descriptions-item>
        </el-descriptions>
      </section>
    </div>
  </div>
</template>

<style scoped lang="scss">
.setting-page {
  padding: 16px 16px 24px;
  min-height: 360px;
}

.info-block {
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 4px;
  overflow: hidden;

  &__bar {
    padding: 10px 16px;
    font-size: 14px;
    font-weight: 500;
    color: var(--el-text-color-primary);
    background: #f5f5f5;
    border-bottom: 1px solid var(--el-border-color-lighter);
  }
}

.info-desc {
  :deep(.el-descriptions__label) {
    width: 180px;
    color: var(--el-text-color-primary);
  }

  :deep(.el-descriptions__table) {
    border: none;
  }
}

.info-link {
  color: #4285f4;
  text-decoration: none;
  cursor: pointer;

  &:hover {
    color: #73d13d;
  }
}

html.dark {
  .info-block__bar {
    background: var(--el-fill-color-light);
  }
}
</style>
