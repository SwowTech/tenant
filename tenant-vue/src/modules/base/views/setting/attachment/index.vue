<script setup lang="ts">
import { ElMessageBox } from 'element-plus'
import {
  getAttachmentSetting,
  saveAttachmentSetting,
  type AttachmentSettingVo,
} from '~/base/api/setting'
import SettingRow from '../components/SettingRow.vue'
import SettingSection from '../components/SettingSection.vue'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import type { TransType } from '@/hooks/auto-imports/useTrans.ts'

defineOptions({ name: 'setting:attachment' })

const t = (useTrans() as TransType).globalTrans
const msg = useMessage()
const loading = ref(false)
const saving = ref(false)
const activeTab = ref('global')

const form = reactive<AttachmentSettingVo>({
  attachment_limit: 0,
  image: {
    thumb: false,
    width: 800,
    extentions: [],
    limit: 0,
    zip_percentage: 100,
  },
  audio: {
    extentions: [],
    limit: 0,
  },
  remote: {
    type: 'off',
    alioss: {
      key: '',
      secret: '',
      bucket: '',
      endpoint: '',
      internal: '0',
      url: '',
    },
  },
  php_env: {
    upload_max_filesize: '-',
    post_max_size: '-',
  },
})

const remoteType = computed({
  get: () => String(form.remote?.type || 'off'),
  set: (v: string) => {
    if (!form.remote) {
      form.remote = { type: v }
    }
    else {
      form.remote.type = v
    }
  },
})

const alioss = computed(() => (form.remote.alioss || {}) as Record<string, string>)

function joinExt(list?: string[]) {
  return (list ?? []).join('\n')
}

function parseExt(text: string): string[] {
  return text
    .split(/[\n,，\s]+/)
    .map(s => s.trim().replace(/^\./, ''))
    .filter(Boolean)
}

function stringifyRecord(input: unknown): Record<string, string> {
  const out: Record<string, string> = {}
  if (!input || typeof input !== 'object') {
    return out
  }
  for (const [k, v] of Object.entries(input as Record<string, unknown>)) {
    out[k] = v == null ? '' : String(v)
  }
  return out
}

function ensureRemote() {
  if (!form.remote) {
    form.remote = { type: 'off' }
  }
  form.remote.alioss = stringifyRecord(form.remote.alioss)
  form.remote.type = String(form.remote.type || 'off')
  if (!['off', 'alioss'].includes(form.remote.type)) {
    form.remote.type = 'off'
  }
}

async function load() {
  loading.value = true
  try {
    const res: any = await getAttachmentSetting()
    if (res.code === ResultCode.SUCCESS && res.data) {
      Object.assign(form, res.data)
      if (res.data.image) {
        Object.assign(form.image, res.data.image)
      }
      if (res.data.audio) {
        Object.assign(form.audio, res.data.audio)
      }
      if (res.data.remote) {
        form.remote = {
          ...form.remote,
          ...res.data.remote,
          alioss: { ...(form.remote.alioss as object), ...(res.data.remote.alioss as object || {}) },
        }
        ensureRemote()
      }
      if (res.data.php_env) {
        form.php_env = res.data.php_env
      }
    }
  }
  finally {
    loading.value = false
  }
}

async function patch(partial: Partial<AttachmentSettingVo>) {
  const res: any = await saveAttachmentSetting(partial)
  if (res.code === ResultCode.SUCCESS && res.data) {
    Object.assign(form, {
      attachment_limit: res.data.attachment_limit,
      php_env: res.data.php_env ?? form.php_env,
    })
    if (res.data.image) {
      Object.assign(form.image, res.data.image)
    }
    if (res.data.audio) {
      Object.assign(form.audio, res.data.audio)
    }
    if (res.data.remote) {
      form.remote = { ...form.remote, ...res.data.remote }
      ensureRemote()
    }
    msg.success(t('settingUi.saveSuccess'))
  }
}

async function promptNumber(title: string, current: number, help: string) {
  const { value } = await ElMessageBox.prompt(help, title, {
    inputValue: String(current),
    inputPattern: /^\d+$/,
    inputErrorMessage: t('settingUi.nonnegativeInt'),
    confirmButtonText: t('crud.ok'),
    cancelButtonText: t('crud.cancel'),
  })
  return Number(value)
}

async function promptText(title: string, current: string, help: string, textarea = false) {
  const { value } = await ElMessageBox.prompt(help, title, {
    inputValue: current,
    inputType: textarea ? 'textarea' : 'text',
    confirmButtonText: t('crud.ok'),
    cancelButtonText: t('crud.cancel'),
  })
  return value ?? ''
}

async function editAttachmentLimit() {
  try {
    const n = await promptNumber(t('settingUi.promptSpace'), form.attachment_limit, t('settingUi.promptSpaceDesc'))
    await patch({ attachment_limit: n })
  }
  catch {
    // cancelled
  }
}

async function onThumbChange(value: boolean | string | number) {
  await patch({ image: { ...form.image, thumb: !!value } })
}

async function editImageWidth() {
  try {
    const n = await promptNumber(t('settingUi.promptThumbWidth'), form.image.width, t('settingUi.promptThumbWidthDesc'))
    await patch({ image: { ...form.image, width: n } })
  }
  catch {
    // cancelled
  }
}

async function editImageExt() {
  try {
    const text = await promptText(
      t('settingUi.promptExt'),
      joinExt(form.image.extentions),
      t('settingUi.promptImageExtDesc'),
      true,
    )
    await patch({ image: { ...form.image, extentions: parseExt(text) } })
  }
  catch {
    // cancelled
  }
}

async function editImageLimit() {
  try {
    const n = await promptNumber(t('settingUi.promptFileSize'), form.image.limit, t('settingUi.promptFileSizeDesc'))
    await patch({ image: { ...form.image, limit: n } })
  }
  catch {
    // cancelled
  }
}

async function editZip() {
  try {
    const n = await promptNumber(t('settingUi.promptZip'), form.image.zip_percentage, t('settingUi.promptZipDesc'))
    if (n < 1 || n > 100) {
      msg.error(t('settingUi.zipRangeError'))
      return
    }
    await patch({ image: { ...form.image, zip_percentage: n } })
  }
  catch {
    // cancelled
  }
}

async function editAudioExt() {
  try {
    const text = await promptText(
      t('settingUi.promptExt'),
      joinExt(form.audio.extentions),
      t('settingUi.promptAvExtDesc'),
      true,
    )
    await patch({ audio: { ...form.audio, extentions: parseExt(text) } })
  }
  catch {
    // cancelled
  }
}

async function editAudioLimit() {
  try {
    const n = await promptNumber(t('settingUi.promptFileSize'), form.audio.limit, t('settingUi.promptFileSizeDesc'))
    await patch({ audio: { ...form.audio, limit: n } })
  }
  catch {
    // cancelled
  }
}

async function saveRemote() {
  ensureRemote()
  saving.value = true
  try {
    await patch({ remote: form.remote })
  }
  finally {
    saving.value = false
  }
}

const limitText = computed(() => {
  if (!form.attachment_limit) {
    return t('settingUi.spaceCapacityHint')
  }
  return t('settingUi.spaceCapacityValue', { n: form.attachment_limit })
})

const imageExtText = computed(() => {
  const list = form.image.extentions
  if (!list?.length) {
    return t('settingUi.unsetSystemDefault')
  }
  return list.join(', ')
})

const audioExtText = computed(() => {
  const list = form.audio.extentions
  if (!list?.length) {
    return t('settingUi.unsetSystemDefault')
  }
  return list.join(', ')
})

onMounted(load)
</script>

<template>
  <div v-loading="loading" class="mine-layout p-3">
    <div class="mine-card setting-page">
      <el-tabs v-model="activeTab">
        <el-tab-pane :label="t('settingUi.localStrategy')" name="global">
          <el-alert
            class="mb-4"
            type="warning"
            show-icon
            :closable="false"
            :title="t('settingUi.uploadForceAlert')"
          />

          <div class="php-env mb-4">
            <div class="php-env__label">
              {{ t('settingUi.phpEnvTitle') }}
            </div>
            <div class="php-env__body">
              <div>{{ t('settingUi.phpUploadMax', { size: form.php_env?.upload_max_filesize || '-' }) }}</div>
              <div>{{ t('settingUi.phpPostMax', { size: form.php_env?.post_max_size || '-' }) }}</div>
            </div>
          </div>

          <SettingSection :title="t('settingUi.localSpace')">
            <SettingRow
              :label="t('settingUi.spaceCapacity')"
              type="link"
              :description="limitText"
              @link-click="editAttachmentLimit"
            />
          </SettingSection>

          <SettingSection :title="t('settingUi.thumbSettings')">
            <SettingRow
              :label="t('settingUi.thumbEnable')"
              type="switch"
              :description="t('settingUi.thumbConfigSaved')"
              :model-value="form.image.thumb"
              @update:model-value="onThumbChange"
            />
            <SettingRow
              v-if="form.image.thumb"
              :label="t('settingUi.thumbMaxWidth')"
              type="link"
              :description="form.image.width ? `${form.image.width}px` : t('settingUi.thumbMaxWidthHint')"
              @link-click="editImageWidth"
            />
          </SettingSection>

          <SettingSection :title="t('settingUi.imageSettings')">
            <SettingRow
              :label="t('settingUi.fileExt')"
              type="link"
              :description="imageExtText"
              @link-click="editImageExt"
            />
            <SettingRow
              :label="t('settingUi.fileSize')"
              type="link"
              :description="form.image.limit ? `${form.image.limit}KB` : t('settingUi.notSet')"
              @link-click="editImageLimit"
            />
            <SettingRow
              :label="t('settingUi.imageZip')"
              type="link"
              :description="form.image.zip_percentage ? `${form.image.zip_percentage}%` : t('settingUi.imageZipHint')"
              @link-click="editZip"
            />
          </SettingSection>

          <SettingSection :title="t('settingUi.avSettings')">
            <SettingRow
              :label="t('settingUi.fileExt')"
              type="link"
              :description="audioExtText"
              @link-click="editAudioExt"
            />
            <SettingRow
              :label="t('settingUi.fileSize')"
              type="link"
              :description="form.audio.limit ? `${form.audio.limit}KB` : t('settingUi.notSet')"
              @link-click="editAudioLimit"
            />
          </SettingSection>
        </el-tab-pane>

        <el-tab-pane :label="t('settingUi.remoteAttachment')" name="remote">
          <el-radio-group v-model="remoteType" class="remote-type mb-4">
            <el-radio value="off">
              {{ t('settingUi.storageLocal') }}
            </el-radio>
            <el-radio value="alioss">
              {{ t('settingUi.storageAliyun') }}
            </el-radio>
          </el-radio-group>

          <div v-if="remoteType === 'off'" class="remote-tip text-gray-500 mb-4">
            {{ t('settingUi.localStorageHint') }}
          </div>

          <el-alert
            v-if="remoteType === 'alioss'"
            class="mb-4"
            type="info"
            show-icon
            :closable="false"
            :title="t('settingUi.ossPlatformTip')"
          />

          <el-form v-if="remoteType === 'alioss'" label-width="140px" class="max-w-3xl">
            <el-form-item label="Access Key ID">
              <el-input v-model="alioss.key" />
            </el-form-item>
            <el-form-item label="Access Key Secret">
              <el-input v-model="alioss.secret" type="password" show-password />
            </el-form-item>
            <el-form-item label="Bucket">
              <el-input v-model="alioss.bucket" />
            </el-form-item>
            <el-form-item label="Endpoint">
              <el-input v-model="alioss.endpoint" :placeholder="t('settingUi.endpointPlaceholder')" />
            </el-form-item>
            <el-form-item :label="t('settingUi.intranetUpload')">
              <el-radio-group v-model="alioss.internal">
                <el-radio value="1">
                  {{ t('settingUi.yes') }}
                </el-radio>
                <el-radio value="0">
                  {{ t('settingUi.no') }}
                </el-radio>
              </el-radio-group>
            </el-form-item>
            <el-form-item :label="t('settingUi.customUrl')">
              <el-input v-model="alioss.url" :placeholder="t('settingUi.customUrlPlaceholder')" />
            </el-form-item>
          </el-form>

          <div class="mt-4">
            <el-button type="primary" :loading="saving" @click="saveRemote">
              {{ t('settingUi.saveConfig') }}
            </el-button>
          </div>
        </el-tab-pane>
      </el-tabs>
    </div>
  </div>
</template>

<style scoped lang="scss">
.setting-page {
  padding: 8px 16px 24px;
  min-height: 360px;
}

.php-env {
  display: flex;
  gap: 16px;
  font-size: 14px;
  color: var(--el-text-color-regular);

  &__label {
    flex-shrink: 0;
    width: 120px;
    color: var(--el-text-color-primary);
  }

  &__body {
    line-height: 1.8;
  }
}

.remote-type {
  display: flex;
  flex-wrap: wrap;
  gap: 8px 16px;
}
</style>
