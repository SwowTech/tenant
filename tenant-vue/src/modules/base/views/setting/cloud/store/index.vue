<script setup lang="ts">
import { ElMessage } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import {
  checkLocalAppIdentifier,
  createLocalApp,
  getCloudInstalledCatalog,
  getCloudStoreToken,
  type CloudInstalledItemVo,
} from '~/base/api/cloud'
import { ResultCode } from '@/utils/ResultCode.ts'
import type { TransType } from '@/hooks/auto-imports/useTrans.ts'
import isFounder from '@/utils/isFounder.ts'

defineOptions({ name: 'setting:cloud:store' })

const t = (useTrans() as TransType).globalTrans
const opening = ref(false)
const loading = ref(false)
const creating = ref(false)
const lastUrl = ref('')
const hint = ref('')
const remoteMessage = ref('')
const list = ref<CloudInstalledItemVo[]>([])
const activeTab = ref<'app' | 'plugin'>('app')
const createVisible = ref(false)
const formRef = ref<FormInstance>()
const checkHint = ref('')
const checkOk = ref(false)

const form = reactive({
  identifier: '',
  title: '',
  version: '1.0.0',
  edition: 'community',
  with_demo: true,
})

const founder = computed(() => isFounder())

const adminBase = computed(() =>
  (import.meta.env.VITE_SAAS_ADMIN_URL || 'http://127.0.0.1:5174').replace(/\/$/, ''),
)

const fallbackStoreUrl = computed(() => `${adminBase.value}/platform/store`)

const filteredList = computed(() => list.value.filter(i => i.type === activeTab.value))

const updateCount = computed(() => filteredList.value.filter(i => i.update_available).length)

const appCount = computed(() => list.value.filter(i => i.type === 'app').length)
const pluginCount = computed(() => list.value.filter(i => i.type === 'plugin').length)

const rules: FormRules = {
  identifier: [
    { required: true, message: () => t('cloudStore.identifierRequired'), trigger: 'blur' },
    {
      pattern: /^[A-Za-z0-9_-]+\/[A-Za-z0-9_-]+$/,
      message: () => t('cloudStore.identifierPattern'),
      trigger: 'blur',
    },
  ],
  title: [{ required: true, message: () => t('cloudStore.titleRequired'), trigger: 'blur' }],
  version: [{ required: true, message: () => t('cloudStore.versionRequired'), trigger: 'blur' }],
}

async function loadCatalog() {
  loading.value = true
  remoteMessage.value = ''
  try {
    const res: any = await getCloudInstalledCatalog()
    if (res.code !== ResultCode.SUCCESS) {
      ElMessage.error(res.message || t('cloudStore.loadFailed'))
      return
    }
    list.value = res.data?.list ?? []
    if (res.data?.remote_ok === false) {
      remoteMessage.value = res.data?.remote_message || t('cloudStore.remoteFailed')
    }
  }
  catch (e: any) {
    ElMessage.error(e?.message || t('cloudStore.loadFailed'))
  }
  finally {
    loading.value = false
  }
}

async function openStore() {
  opening.value = true
  hint.value = ''
  try {
    const res: any = await getCloudStoreToken()
    if (res.code !== ResultCode.SUCCESS || !res.data) {
      ElMessage.error(res.message || t('cloudStore.openFailed'))
      return
    }
    if (!res.data.bound) {
      hint.value = res.data.message || t('cloudStore.needBind')
      ElMessage.warning(hint.value)
      return
    }
    if (res.data.store_url) {
      lastUrl.value = res.data.store_url
      window.open(res.data.store_url, '_blank')
      return
    }
    if (res.data.message) {
      hint.value = res.data.message
      ElMessage.error(res.data.message)
    }
    lastUrl.value = fallbackStoreUrl.value
    window.open(fallbackStoreUrl.value, '_blank')
  }
  catch (e: any) {
    hint.value = e?.message || t('cloudStore.openError')
    ElMessage.error(hint.value)
  }
  finally {
    opening.value = false
  }
}

function openCreate() {
  Object.assign(form, {
    identifier: '',
    title: '',
    version: '1.0.0',
    edition: 'community',
    with_demo: true,
  })
  checkHint.value = ''
  checkOk.value = false
  createVisible.value = true
}

async function onCheckIdentifier() {
  checkHint.value = ''
  checkOk.value = false
  if (!/^[A-Za-z0-9_-]+\/[A-Za-z0-9_-]+$/.test(form.identifier)) {
    return
  }
  try {
    const res: any = await checkLocalAppIdentifier(form.identifier)
    if (res.code !== ResultCode.SUCCESS) {
      checkHint.value = res.message || t('cloudStore.checkFailed')
      return
    }
    checkOk.value = !!res.data?.available
    checkHint.value = res.data?.message || ''
  }
  catch (e: any) {
    checkHint.value = e?.message || t('cloudStore.checkFailed')
  }
}

async function submitCreate() {
  await formRef.value?.validate()
  if (!checkOk.value) {
    await onCheckIdentifier()
    if (!checkOk.value) {
      ElMessage.warning(checkHint.value || t('cloudStore.needAvailableId'))
      return
    }
  }
  creating.value = true
  try {
    const res: any = await createLocalApp({
      identifier: form.identifier.trim(),
      title: form.title.trim(),
      version: form.version.trim(),
      edition: form.edition,
      with_demo: form.with_demo,
    })
    if (res.code !== ResultCode.SUCCESS) {
      ElMessage.error(res.message || t('cloudStore.createFailed'))
      return
    }
    ElMessage.success(t('cloudStore.createOk'))
    createVisible.value = false
    activeTab.value = 'app'
    await loadCatalog()
  }
  catch (e: any) {
    ElMessage.error(e?.message || t('cloudStore.createFailed'))
  }
  finally {
    creating.value = false
  }
}

onMounted(() => {
  loadCatalog()
})
</script>

<template>
  <div class="mine-layout p-3 flex flex-col gap-3">
    <div class="mine-card p-6">
      <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
        <div>
          <h2 class="text-lg font-medium m-0 mb-2">
            {{ t('cloudStore.title') }}
          </h2>
          <p class="text-sm text-gray-5 dark-text-gray-3 leading-6 m-0">
            {{ t('cloudStore.subtitle') }}
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <el-button :loading="loading" @click="loadCatalog">
            {{ t('cloudStore.refresh') }}
          </el-button>
          <el-button v-if="founder" type="primary" @click="openCreate">
            {{ t('cloudStore.createApp') }}
          </el-button>
          <el-button :loading="opening" @click="openStore">
            {{ t('cloudStore.openStore') }}
          </el-button>
          <el-button @click="$router.push('/setting/cloud/register')">
            {{ t('cloudStore.goRegister') }}
          </el-button>
        </div>
      </div>

      <el-alert
        v-if="hint"
        class="mb-3"
        type="warning"
        :closable="false"
        :title="hint"
      />
      <el-alert
        v-if="remoteMessage"
        class="mb-3"
        type="warning"
        :closable="false"
        :title="remoteMessage"
      />
      <el-alert
        v-if="updateCount > 0"
        class="mb-3"
        type="info"
        :closable="false"
        :title="t('cloudStore.updateAvailable', { count: updateCount })"
      />

      <el-tabs v-model="activeTab" class="mb-2">
        <el-tab-pane :label="`${t('cloudStore.typeApp')} (${appCount})`" name="app" />
        <el-tab-pane :label="`${t('cloudStore.typePlugin')} (${pluginCount})`" name="plugin" />
      </el-tabs>

      <el-table v-loading="loading" :data="filteredList" size="small" stripe>
        <el-table-column :label="t('cloudStore.origin')" width="100">
          <template #default="{ row }">
            <el-tag size="small" :type="row.origin === 'cloud' ? 'warning' : 'info'">
              {{ row.origin === 'cloud' ? t('cloudStore.originCloud') : t('cloudStore.originLocal') }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="identifier" :label="t('cloudStore.identifier')" min-width="180" show-overflow-tooltip />
        <el-table-column prop="title" :label="t('cloudStore.name')" min-width="160" show-overflow-tooltip />
        <el-table-column prop="version" :label="t('cloudStore.localVersion')" width="110" />
        <el-table-column :label="t('cloudStore.remoteVersion')" width="110">
          <template #default="{ row }">
            {{ row.remote_version || (row.in_market ? '-' : t('cloudStore.notListed')) }}
          </template>
        </el-table-column>
        <el-table-column :label="t('cloudStore.update')" width="110">
          <template #default="{ row }">
            <el-tag v-if="row.update_available" type="danger" size="small">
              {{ t('cloudStore.needUpdate') }}
            </el-tag>
            <el-tag v-else-if="row.remote_version" type="success" size="small">
              {{ t('cloudStore.upToDate') }}
            </el-tag>
            <span v-else class="text-gray-400">-</span>
          </template>
        </el-table-column>
        <el-table-column prop="author" :label="t('cloudStore.author')" width="120" show-overflow-tooltip />
        <el-table-column :label="t('cloudStore.status')" width="90">
          <template #default="{ row }">
            <el-tag size="small" :type="row.status ? 'success' : 'info'">
              {{ row.status ? t('cloudStore.installed') : t('cloudStore.disabled') }}
            </el-tag>
          </template>
        </el-table-column>
      </el-table>

      <div v-if="lastUrl" class="mt-4 text-xs text-gray-4 break-all">
        {{ lastUrl }}
      </div>
    </div>

    <el-dialog
      v-model="createVisible"
      :title="t('cloudStore.createTitle')"
      width="520px"
      destroy-on-close
    >
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
        <el-form-item :label="t('cloudStore.identifier')" prop="identifier">
          <el-input
            v-model="form.identifier"
            :placeholder="t('cloudStore.identifierPlaceholder')"
            @blur="onCheckIdentifier"
          />
          <div
            v-if="checkHint"
            class="text-xs mt-1"
            :class="checkOk ? 'text-green-600' : 'text-red-500'"
          >
            {{ checkHint }}
          </div>
        </el-form-item>
        <el-form-item :label="t('cloudStore.name')" prop="title">
          <el-input v-model="form.title" :placeholder="t('cloudStore.titlePlaceholder')" />
        </el-form-item>
        <el-form-item :label="t('cloudStore.localVersion')" prop="version">
          <el-input v-model="form.version" />
        </el-form-item>
        <el-form-item :label="t('cloudStore.edition')">
          <el-select v-model="form.edition" class="w-full">
            <el-option label="community" value="community" />
            <el-option label="pro" value="pro" />
          </el-select>
        </el-form-item>
        <el-form-item :label="t('cloudStore.withDemo')">
          <el-switch v-model="form.with_demo" />
          <span class="ml-2 text-xs text-gray-400">{{ t('cloudStore.withDemoHint') }}</span>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="createVisible = false">
          {{ t('crud.cancel') }}
        </el-button>
        <el-button type="primary" :loading="creating" @click="submitCreate">
          {{ t('cloudStore.createSubmit') }}
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>
