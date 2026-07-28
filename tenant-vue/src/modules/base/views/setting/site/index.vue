<script setup lang="ts">
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessageBox } from 'element-plus'
import {
  createSiteIcp,
  deleteSiteIcp,
  getSiteIcpList,
  getSiteSetting,
  saveSiteSetting,
  updateSiteIcp,
  type SiteIcpVo,
  type SiteSettingVo,
} from '~/base/api/setting'
import SettingRow from '../components/SettingRow.vue'
import SettingSection from '../components/SettingSection.vue'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import type { TransType } from '@/hooks/auto-imports/useTrans.ts'

defineOptions({ name: 'setting:site' })

const t = (useTrans() as TransType).globalTrans
const msg = useMessage()
const loading = ref(false)
const activeTab = ref('basic')

const form = reactive<SiteSettingVo>({
  closed: false,
  close_reason: '',
  auto_logout: 0,
})

const autoLogoutVisible = ref(false)
const autoLogoutPreset = ref<string>('0')
const autoLogoutCustom = ref(0)

const icpKeyword = ref('')
const icpList = ref<SiteIcpVo[]>([])
const icpLoading = ref(false)
const icpDialogVisible = ref(false)
const icpSaving = ref(false)
const icpFormRef = ref<FormInstance>()
const icpEditingId = ref<number | null>(null)
const icpForm = reactive<SiteIcpVo>({
  domain: '',
  icp: '',
  police: '',
  license_url: '',
})

const icpRules = computed<FormRules>(() => ({
  domain: [{ required: true, message: t('settingUi.domainRequired'), trigger: 'blur' }],
}))

const autoLogoutText = computed(() => {
  if (!form.auto_logout) {
    return t('settingUi.autoLogoutUnset')
  }
  if (form.auto_logout === 30) {
    return t('settingUi.autoLogout30')
  }
  if (form.auto_logout === 60) {
    return t('settingUi.autoLogout1h')
  }
  if (form.auto_logout === 180) {
    return t('settingUi.autoLogout3h')
  }
  return t('settingUi.autoLogoutMinutes', { n: form.auto_logout })
})

async function loadSite() {
  loading.value = true
  try {
    const res: any = await getSiteSetting()
    if (res.code === ResultCode.SUCCESS && res.data) {
      Object.assign(form, {
        closed: !!res.data.closed,
        close_reason: res.data.close_reason || '',
        auto_logout: Number(res.data.auto_logout) || 0,
      })
    }
  }
  finally {
    loading.value = false
  }
}

async function patchSite(partial: Partial<SiteSettingVo>) {
  const res: any = await saveSiteSetting(partial)
  if (res.code === ResultCode.SUCCESS && res.data) {
    Object.assign(form, {
      closed: !!res.data.closed,
      close_reason: res.data.close_reason || '',
      auto_logout: Number(res.data.auto_logout) || 0,
    })
    msg.success(t('settingUi.saveSuccess'))
  }
}

async function onSwitch(key: keyof SiteSettingVo, value: boolean | string | number) {
  await patchSite({ [key]: !!value } as Partial<SiteSettingVo>)
}

async function editCloseReason() {
  try {
    const { value } = await ElMessageBox.prompt(t('settingUi.closeReasonPrompt'), t('settingUi.editCloseReason'), {
      inputValue: form.close_reason,
      inputType: 'textarea',
      confirmButtonText: t('crud.ok'),
      cancelButtonText: t('crud.cancel'),
    })
    await patchSite({ close_reason: value ?? '' })
  }
  catch {
    // cancelled
  }
}

function openAutoLogout() {
  const v = form.auto_logout
  if (v === 30 || v === 60 || v === 180) {
    autoLogoutPreset.value = String(v)
    autoLogoutCustom.value = v
  }
  else if (v > 0) {
    autoLogoutPreset.value = 'custom'
    autoLogoutCustom.value = v
  }
  else {
    autoLogoutPreset.value = '0'
    autoLogoutCustom.value = 0
  }
  autoLogoutVisible.value = true
}

async function saveAutoLogout() {
  let minutes = 0
  if (autoLogoutPreset.value === 'custom') {
    minutes = Math.max(0, Number(autoLogoutCustom.value) || 0)
  }
  else {
    minutes = Math.max(0, Number(autoLogoutPreset.value) || 0)
  }
  await patchSite({ auto_logout: minutes })
  autoLogoutVisible.value = false
}

async function loadIcp() {
  icpLoading.value = true
  try {
    const res: any = await getSiteIcpList({ keyword: icpKeyword.value || undefined })
    if (res.code === ResultCode.SUCCESS && res.data) {
      icpList.value = res.data.list ?? []
    }
  }
  finally {
    icpLoading.value = false
  }
}

function openIcpDialog(row?: SiteIcpVo) {
  icpEditingId.value = row?.id ?? null
  Object.assign(icpForm, {
    domain: row?.domain ?? '',
    icp: row?.icp ?? '',
    police: row?.police ?? '',
    license_url: row?.license_url ?? '',
  })
  icpDialogVisible.value = true
}

async function saveIcp() {
  await icpFormRef.value?.validate()
  icpSaving.value = true
  try {
    const payload = { ...icpForm }
    const res: any = icpEditingId.value
      ? await updateSiteIcp(icpEditingId.value, payload)
      : await createSiteIcp(payload)
    if (res.code === ResultCode.SUCCESS) {
      msg.success(t('settingUi.saveSuccess'))
      icpDialogVisible.value = false
      await loadIcp()
    }
  }
  finally {
    icpSaving.value = false
  }
}

async function removeIcp(row: SiteIcpVo) {
  if (!row.id) {
    return
  }
  try {
    await ElMessageBox.confirm(t('settingUi.deleteIcpConfirm'), t('settingUi.tip'), {
      type: 'warning',
      confirmButtonText: t('crud.ok'),
      cancelButtonText: t('crud.cancel'),
    })
  }
  catch {
    return
  }
  const res: any = await deleteSiteIcp(row.id)
  if (res.code === ResultCode.SUCCESS) {
    msg.success(t('settingUi.deleted'))
    await loadIcp()
  }
}

watch(activeTab, (tab) => {
  if (tab === 'icp') {
    loadIcp()
  }
})

onMounted(loadSite)
</script>

<template>
  <div v-loading="loading" class="mine-layout p-3">
    <div class="mine-card setting-page">
      <el-tabs v-model="activeTab">
        <el-tab-pane :label="t('settingUi.tabBasic')" name="basic">
          <SettingSection :title="t('settingUi.basicSettings')">
            <SettingRow
              :label="t('settingUi.closeSite')"
              type="switch"
              :description="t('settingUi.closeSiteDesc')"
              :model-value="form.closed"
              @update:model-value="v => onSwitch('closed', v)"
            />
            <SettingRow
              :label="t('settingUi.closeReason')"
              type="link"
              :description="form.close_reason || t('settingUi.notSet')"
              @link-click="editCloseReason"
            />
            <SettingRow
              :label="t('settingUi.autoLogout')"
              type="link"
              :description="autoLogoutText"
              @link-click="openAutoLogout"
            />
          </SettingSection>
        </el-tab-pane>

        <el-tab-pane :label="t('settingUi.tabIcp')" name="icp">
          <div class="setting-toolbar">
            <el-input
              v-model="icpKeyword"
              clearable
              :placeholder="t('settingUi.searchDomain')"
              class="setting-toolbar__search"
              @keyup.enter="loadIcp"
            >
              <template #append>
                <el-button @click="loadIcp">
                  {{ t('crud.search') }}
                </el-button>
              </template>
            </el-input>
            <el-button type="primary" @click="openIcpDialog()">
              +{{ t('settingUi.add') }}
            </el-button>
          </div>

          <el-table v-loading="icpLoading" :data="icpList" stripe empty-text=" ">
            <el-table-column prop="domain" :label="t('settingUi.domainAddress')" min-width="140" />
            <el-table-column prop="icp" :label="t('settingUi.icpNo')" min-width="140" />
            <el-table-column prop="police" :label="t('settingUi.policeNo')" min-width="160" />
            <el-table-column prop="license_url" :label="t('settingUi.eLicense')" min-width="200" show-overflow-tooltip />
            <el-table-column :label="t('settingUi.actions')" width="140" fixed="right">
              <template #default="{ row }">
                <el-button link type="primary" @click="openIcpDialog(row)">
                  {{ t('crud.edit') }}
                </el-button>
                <el-button link type="danger" @click="removeIcp(row)">
                  {{ t('crud.delete') }}
                </el-button>
              </template>
            </el-table-column>
          </el-table>

          <div v-if="!icpLoading && icpList.length === 0" class="setting-empty">
            <p class="setting-empty__title">
              {{ t('settingUi.noIcp') }}
            </p>
            <p class="setting-empty__desc">
              {{ t('settingUi.noIcpHint') }}
            </p>
            <el-button link type="primary" @click="openIcpDialog()">
              {{ t('settingUi.addNow') }}
            </el-button>
          </div>
        </el-tab-pane>
      </el-tabs>
    </div>

    <el-dialog v-model="autoLogoutVisible" :title="t('settingUi.autoLogoutDialog')" width="480px">
      <el-alert
        type="info"
        :closable="false"
        show-icon
        class="mb-4"
        :title="t('settingUi.autoLogoutHint')"
      />
      <el-radio-group v-model="autoLogoutPreset" class="auto-logout-radios">
        <el-radio value="0">
          {{ t('settingUi.autoLogoutNever') }}
        </el-radio>
        <el-radio value="30">
          {{ t('settingUi.autoLogout30') }}
        </el-radio>
        <el-radio value="60">
          {{ t('settingUi.autoLogout1h') }}
        </el-radio>
        <el-radio value="180">
          {{ t('settingUi.autoLogout3h') }}
        </el-radio>
        <el-radio value="custom">
          {{ t('settingUi.autoLogoutCustom') }}
        </el-radio>
      </el-radio-group>
      <div v-if="autoLogoutPreset === 'custom'" class="mt-3">
        <el-input-number v-model="autoLogoutCustom" :min="1" :max="10080" />
        <span class="ml-2 text-gray-500">{{ t('settingUi.minutes') }}</span>
      </div>
      <template #footer>
        <el-button @click="autoLogoutVisible = false">
          {{ t('crud.cancel') }}
        </el-button>
        <el-button type="primary" @click="saveAutoLogout">
          {{ t('crud.ok') }}
        </el-button>
      </template>
    </el-dialog>

    <el-dialog
      v-model="icpDialogVisible"
      :title="icpEditingId ? t('settingUi.editIcp') : t('settingUi.addIcp')"
      width="520px"
    >
      <el-form ref="icpFormRef" :model="icpForm" :rules="icpRules" label-width="100px">
        <el-form-item :label="t('settingUi.domain')" prop="domain">
          <el-input v-model="icpForm.domain" :placeholder="t('settingUi.domainPlaceholder')" />
        </el-form-item>
        <el-form-item :label="t('settingUi.icpNo')">
          <el-input v-model="icpForm.icp" :placeholder="t('settingUi.icpPlaceholder')" />
        </el-form-item>
        <el-form-item :label="t('settingUi.policeFiling')">
          <el-input v-model="icpForm.police" :placeholder="t('settingUi.policePlaceholder')" />
        </el-form-item>
        <el-form-item :label="t('settingUi.eLicense')">
          <el-input v-model="icpForm.license_url" :placeholder="t('settingUi.licensePlaceholder')" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="icpDialogVisible = false">
          {{ t('crud.cancel') }}
        </el-button>
        <el-button type="primary" :loading="icpSaving" @click="saveIcp">
          {{ t('crud.save') }}
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped lang="scss">
.setting-page {
  padding: 8px 16px 24px;
  min-height: 360px;
}

.setting-toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;

  &__search {
    width: 280px;
  }
}

.setting-empty {
  margin-top: 32px;
  text-align: center;
  line-height: 1.6;
  color: var(--el-text-color-regular);

  &__title {
    margin: 0 0 8px;
    font-size: 14px;
    color: var(--el-text-color-primary);
  }

  &__desc {
    margin: 0 0 12px;
    font-size: 13px;
    color: var(--el-text-color-secondary);
  }
}

.auto-logout-radios {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 8px;
}
</style>
