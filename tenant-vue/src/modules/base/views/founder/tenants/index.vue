<script setup lang="ts">
import type { FormInstance, FormRules } from 'element-plus'
import {
  assignFounderTenantApps,
  checkFounderDomainAvailable,
  createFounderTenant,
  enterFounderTenant,
  getFounderAssignableApps,
  getFounderTenantApps,
  getFounderTenants,
  reprovisionFounderTenant,
  setFounderTenantAppStatus,
  suggestFounderDomain,
  updateFounderTenant,
  updateFounderTenantApp,
  type FounderTenantAppVo,
  type FounderTenantVo,
} from '~/base/api/founder'
import isFounder from '@/utils/isFounder.ts'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import { setTenantContext } from '@/utils/tenantContext.ts'
import useUserStore from '@/store/modules/useUserStore.ts'
import useTabStore from '@/store/modules/useTabStore.ts'
import type { TransType } from '@/hooks/auto-imports/useTrans.ts'

defineOptions({ name: 'founder:tenants' })

const i18n = useTrans() as TransType
const t = i18n.globalTrans
const msg = useMessage()
const userStore = useUserStore()
const allowed = computed(() => isFounder())

const statusOptions = computed(() => [
  { label: t('founderTenants.statusActive'), value: 1 },
  { label: t('founderTenants.statusDisabled'), value: 2 },
  { label: t('founderTenants.statusProvisioning'), value: 5 },
  { label: t('founderTenants.statusFailed'), value: 6 },
])

const loading = ref(false)
const saving = ref(false)
const dialogVisible = ref(false)
const assignVisible = ref(false)
const assignSaving = ref(false)
const assignLoading = ref(false)
const assignYears = ref(0)
const assignMonths = ref(0)
const licensePreset = ref('forever')
const editVisible = ref(false)
const editSaving = ref(false)
const editRow = ref<FounderTenantAppVo | null>(null)
const editStatus = ref<1 | 2>(1)
const editYears = ref(0)
const editMonths = ref(0)
const editLicensePreset = ref('forever')
const list = ref<FounderTenantVo[]>([])
const total = ref(0)
const formRef = ref<FormInstance>()
const assignTenant = ref<FounderTenantVo | null>(null)
const appOptions = ref<Array<{ identifier: string, version: string, description?: string, edition?: string, family?: string }>>([])
const attachedApps = ref<FounderTenantAppVo[]>([])
const selectedIdentifiers = ref<string[]>([])

const licensePresets = computed(() => [
  { value: 'forever', label: t('founderTenants.licenseForever'), years: 0, months: 0 },
  { value: '1m', label: t('founderTenants.license1m'), years: 0, months: 1 },
  { value: '3m', label: t('founderTenants.license3m'), years: 0, months: 3 },
  { value: '6m', label: t('founderTenants.license6m'), years: 0, months: 6 },
  { value: '1y', label: t('founderTenants.license1y'), years: 1, months: 0 },
  { value: '2y', label: t('founderTenants.license2y'), years: 2, months: 0 },
  { value: '3y', label: t('founderTenants.license3y'), years: 3, months: 0 },
  { value: 'custom', label: t('founderTenants.licenseCustom'), years: 0, months: 0 },
])

function formatExpiresLabel(raw?: string | null) {
  const label = String(raw || '').trim()
  if (!label || label === 'forever' || label === '永久') {
    return t('founderTenants.forever')
  }
  if (label.startsWith('expired|')) {
    return t('founderTenants.expiredUntil', { date: label.slice(8) })
  }
  if (label.startsWith('until|')) {
    return t('founderTenants.until', { date: label.slice(6) })
  }
  if (label.startsWith('已过期')) {
    return label.replace(/^已过期/, t('founderTenants.expired'))
  }
  if (label.startsWith('至 ')) {
    return t('founderTenants.until', { date: label.slice(2) })
  }
  return label
}

function applyLicensePreset(val: string | number) {
  const key = String(val)
  licensePreset.value = key
  const hit = licensePresets.value.find(i => i.value === key)
  if (!hit || key === 'custom') {
    return
  }
  assignYears.value = hit.years
  assignMonths.value = hit.months
}

function applyEditLicensePreset(val: string | number) {
  const key = String(val)
  editLicensePreset.value = key
  const hit = licensePresets.value.find(i => i.value === key)
  if (!hit || key === 'custom') {
    return
  }
  editYears.value = hit.years
  editMonths.value = hit.months
}

function openEditAttached(row: FounderTenantAppVo) {
  editRow.value = row
  editStatus.value = row.status === 2 ? 2 : 1
  editLicensePreset.value = 'forever'
  editYears.value = 0
  editMonths.value = 0
  editVisible.value = true
}

async function submitEditAttached() {
  if (!assignTenant.value?.id || !editRow.value) {
    return
  }
  editSaving.value = true
  try {
    const res: any = await updateFounderTenantApp(
      assignTenant.value.id,
      editRow.value.identifier,
      {
        status: editStatus.value,
        years: editYears.value,
        months: editMonths.value,
      },
    )
    if (res.code === ResultCode.SUCCESS) {
      msg.success(t('founderTenants.msgUpdated'))
      editVisible.value = false
      const listRes: any = await getFounderTenantApps(assignTenant.value.id)
      if (listRes.code === ResultCode.SUCCESS) {
        attachedApps.value = listRes.data || []
      }
    }
  }
  finally {
    editSaving.value = false
  }
}

function formatEdition(edition?: string) {
  const value = String(edition || '').trim()
  return value ? value.toUpperCase() : ''
}

function formatAppOptionLabel(item: { identifier: string, version?: string, edition?: string }) {
  const base = `${item.identifier} (${item.version || '-'})`
  const edition = formatEdition(item.edition)
  return edition ? `${base} · ${edition}` : base
}

const availableAppOptions = computed(() => {
  const attached = new Set(attachedApps.value.map(i => i.identifier))
  return appOptions.value.filter(i => !attached.has(i.identifier))
})

const queryParams = reactive({
  name: '',
  code: '',
  status: undefined as number | undefined,
  page: 1,
  page_size: 15,
})

const form = reactive<FounderTenantVo>({
  id: undefined,
  code: '',
  name: '',
  domain: '',
  custom_domain: '',
  contact_phone: '',
  contact_email: '',
  remark: '',
  admin_user: 'admin',
  admin_pass: '123456',
})

const dialogTitle = computed(() => (
  form.id ? t('founderTenants.editTenant') : t('founderTenants.addTenant')
))

/** 主域名后缀，来自后端 AppUrl::host()（APP_URL） */
const rootHost = ref('')

async function validateDomainUnique(_rule: unknown, value: string, callback: (error?: Error) => void) {
  const domain = normalizeDomainLabel(value)
  if (!domain) {
    callback(new Error(t('founderTenants.msgDomainRequired')))
    return
  }
  if (!/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/.test(domain)) {
    callback(new Error(t('founderTenants.msgDomainFormat')))
    return
  }
  try {
    const res: any = await checkFounderDomainAvailable(domain)
    if (res.code === ResultCode.SUCCESS) {
      if (res.data?.root_host) {
        rootHost.value = res.data.root_host
      }
      if (res.data?.available === true) {
        callback()
        return
      }
    }
    callback(new Error(t('founderTenants.msgDomainExists')))
  }
  catch {
    callback(new Error(t('founderTenants.msgDomainCheckFailed')))
  }
}

const rules = computed<FormRules>(() => {
  const base: FormRules = {
    name: [{ required: true, message: t('founderTenants.msgNameRequired'), trigger: 'blur' }],
  }
  if (!form.id) {
    base.domain = [
      { required: true, message: t('founderTenants.msgDomainRequired'), trigger: 'blur' },
      { validator: validateDomainUnique, trigger: 'blur' },
    ]
    base.admin_user = [{ required: true, message: t('founderTenants.msgAdminUserRequired'), trigger: 'blur' }]
    base.admin_pass = [{ required: true, message: t('founderTenants.msgAdminPassRequired'), trigger: 'blur' }]
  }
  return base
})

function statusLabel(status?: number) {
  return statusOptions.value.find(item => item.value === status)?.label || String(status ?? '')
}

async function copyText(text: string) {
  try {
    await navigator.clipboard.writeText(text)
    msg.success(t('founderTenants.copied'))
  }
  catch {
    msg.error(t('founderTenants.copyFailed'))
  }
}

async function loadData() {
  if (!allowed.value) {
    return
  }
  loading.value = true
  try {
    const res: any = await getFounderTenants({ ...queryParams })
    if (res.code === ResultCode.SUCCESS) {
      list.value = res.data?.list || []
      total.value = res.data?.total || 0
    }
  }
  finally {
    loading.value = false
  }
}

function resetQuery() {
  queryParams.name = ''
  queryParams.code = ''
  queryParams.status = undefined
  queryParams.page = 1
  loadData()
}

async function fillSuggestedDomain(force = false) {
  try {
    const res: any = await suggestFounderDomain()
    if (res.code === ResultCode.SUCCESS && res.data?.domain) {
      if (res.data.root_host) {
        rootHost.value = String(res.data.root_host)
      }
      const current = String(form.domain || '').trim()
      if (force || current === '') {
        form.domain = res.data.domain
        formRef.value?.clearValidate?.('domain')
      }
      return
    }
  }
  catch {
    // fall through
  }
  msg.warning(t('founderTenants.msgSuggestFailed'))
}

async function openCreate() {
  Object.assign(form, {
    id: undefined,
    code: '',
    name: '',
    domain: '',
    custom_domain: '',
    contact_phone: '',
    contact_email: '',
    remark: '',
    admin_user: 'admin',
    admin_pass: '123456',
  })
  dialogVisible.value = true
  await nextTick()
  await fillSuggestedDomain(true)
}

/** 规范化域名标识：去空格、小写；若误粘贴了「.主域名」后缀则剥掉 */
function normalizeDomainLabel(raw: string): string {
  let domain = String(raw || '').trim().toLowerCase()
  const host = String(rootHost.value || '').trim().toLowerCase()
  if (host && domain.endsWith(`.${host}`)) {
    domain = domain.slice(0, -(host.length + 1))
  }
  if (domain.includes('://')) {
    try {
      domain = new URL(domain.includes('://') ? domain : `http://${domain}`).hostname.split('.')[0] || domain
    }
    catch {
      // keep trimmed lower
    }
  }
  return domain
}

function openEdit(row: FounderTenantVo) {
  Object.assign(form, {
    id: row.id,
    name: row.name,
    custom_domain: String(row.custom_domain || '').trim(),
    contact_phone: row.contact_phone || '',
    contact_email: row.contact_email || '',
    remark: row.remark || '',
  })
  dialogVisible.value = true
}

async function save() {
  if (!form.id) {
    form.domain = normalizeDomainLabel(form.domain)
  }
  const customDomain = String(form.custom_domain || '').trim()
  form.custom_domain = customDomain
  await formRef.value?.validate()
  saving.value = true
  try {
    if (form.id) {
      const res: any = await updateFounderTenant(form.id, {
        name: form.name,
        custom_domain: customDomain,
        contact_phone: form.contact_phone,
        contact_email: form.contact_email,
        remark: form.remark,
      })
      if (res.code === ResultCode.SUCCESS) {
        msg.success(t('founderTenants.msgUpdateOk'))
        dialogVisible.value = false
        await loadData()
      }
    }
    else {
      const res: any = await createFounderTenant({
        name: form.name,
        domain: normalizeDomainLabel(form.domain),
        custom_domain: customDomain,
        contact_phone: form.contact_phone,
        contact_email: form.contact_email,
        remark: form.remark,
        admin_user: form.admin_user,
        admin_pass: form.admin_pass,
      })
      if (res.code === ResultCode.SUCCESS) {
        msg.success(t('founderTenants.msgCreateOk'))
        dialogVisible.value = false
        await loadData()
      }
    }
  }
  finally {
    saving.value = false
  }
}

async function toggleStatus(row: FounderTenantVo) {
  if (!row.id) {
    return
  }
  const next = row.status === 1 ? 2 : 1
  const res: any = await updateFounderTenant(row.id, { status: next })
  if (res.code === ResultCode.SUCCESS) {
    msg.success(next === 1 ? t('founderTenants.msgEnabled') : t('founderTenants.msgDisabled'))
    await loadData()
  }
}

async function onReprovision(row: FounderTenantVo) {
  if (!row.id) {
    return
  }
  const res: any = await reprovisionFounderTenant(row.id)
  if (res.code === ResultCode.SUCCESS) {
    msg.success(t('founderTenants.msgReprovisionOk'))
    await loadData()
  }
}

async function openAssign(row: FounderTenantVo) {
  if (row.status !== 1) {
    msg.warning(t('founderTenants.msgNeedActiveAssign'))
    return
  }
  assignTenant.value = row
  selectedIdentifiers.value = []
  licensePreset.value = 'forever'
  assignYears.value = 0
  assignMonths.value = 0
  assignVisible.value = true
  assignLoading.value = true
  try {
    const [poolRes, listRes]: any[] = await Promise.all([
      getFounderAssignableApps(),
      getFounderTenantApps(row.id!),
    ])
    if (poolRes.code === ResultCode.SUCCESS) {
      const data = poolRes.data || {}
      appOptions.value = Object.keys(data).map(identifier => ({
        identifier,
        version: String(data[identifier]?.version || ''),
        description: data[identifier]?.description,
        edition: String(data[identifier]?.edition || ''),
        family: String(data[identifier]?.family || ''),
      }))
    }
    if (listRes.code === ResultCode.SUCCESS) {
      attachedApps.value = listRes.data || []
    }
  }
  finally {
    assignLoading.value = false
  }
}

async function submitAssign() {
  if (!assignTenant.value?.id) {
    return
  }
  const apps = selectedIdentifiers.value.map((identifier) => {
    const hit = appOptions.value.find(i => i.identifier === identifier)
    return { identifier, version: hit?.version || '' }
  }).filter(a => a.version)
  if (apps.length === 0) {
    msg.warning(t('founderTenants.msgSelectApp'))
    return
  }
  assignSaving.value = true
  try {
    const res: any = await assignFounderTenantApps(
      assignTenant.value.id,
      apps,
      { years: assignYears.value, months: assignMonths.value },
    )
    if (res.code === ResultCode.SUCCESS) {
      msg.success(t('founderTenants.msgAssignOk'))
      const listRes: any = await getFounderTenantApps(assignTenant.value.id)
      if (listRes.code === ResultCode.SUCCESS) {
        attachedApps.value = listRes.data || []
      }
      selectedIdentifiers.value = []
    }
  }
  finally {
    assignSaving.value = false
  }
}

async function toggleAttached(row: FounderTenantAppVo) {
  if (!assignTenant.value?.id) {
    return
  }
  const next = row.status === 1 ? 2 : 1
  const res: any = await setFounderTenantAppStatus(assignTenant.value.id, row.identifier, next as 1 | 2)
  if (res.code === ResultCode.SUCCESS) {
    msg.success(next === 1 ? t('founderTenants.msgEnabled') : t('founderTenants.msgDisabled'))
    const listRes: any = await getFounderTenantApps(assignTenant.value.id)
    if (listRes.code === ResultCode.SUCCESS) {
      attachedApps.value = listRes.data || []
    }
  }
}

async function enterTenant(row: FounderTenantVo) {
  if (row.status !== 1) {
    msg.warning(t('founderTenants.msgNeedActiveEnter'))
    return
  }
  if (!row.id) {
    return
  }
  try {
    const res: any = await enterFounderTenant(row.id)
    if (res.code !== ResultCode.SUCCESS || !res.data?.access_token) {
      msg.error(res.message || t('founderTenants.msgEnterFailed'))
      return
    }
    setTenantContext(row.id, row.name)
    userStore.stashCurrentAsFounderSession()
    userStore.applyAdminSession(res.data)
    useTabStore().clearTab()
    window.location.assign('/')
  }
  catch (e: any) {
    msg.error(e?.message || e?.msg || t('founderTenants.msgEnterFailed'))
  }
}

onMounted(loadData)
</script>

<template>
  <div v-if="!allowed" class="mine-layout mine-card">
    <el-alert type="warning" show-icon :closable="false" :title="t('founderTenants.forbidden')" />
  </div>
  <div v-else class="mine-layout">
    <div class="mine-card mb-4">
      <el-form :model="queryParams" :inline="true">
        <el-form-item :label="t('founderTenants.name')">
          <el-input v-model="queryParams.name" clearable :placeholder="t('founderTenants.namePlaceholder')" />
        </el-form-item>
        <el-form-item :label="t('founderTenants.code')">
          <el-input v-model="queryParams.code" clearable :placeholder="t('founderTenants.codePlaceholder')" />
        </el-form-item>
        <el-form-item :label="t('founderTenants.status')">
          <el-select v-model="queryParams.status" clearable :placeholder="t('founderTenants.all')" class="w-[140px]">
            <el-option
              v-for="item in statusOptions"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">
            {{ t('crud.search') }}
          </el-button>
          <el-button @click="resetQuery">
            {{ t('crud.reset') }}
          </el-button>
        </el-form-item>
      </el-form>
    </div>

    <div class="mine-card">
      <el-button type="primary" @click="openCreate">
        {{ t('founderTenants.addTenant') }}
      </el-button>
      <el-table v-loading="loading" class="mt-4" :data="list" size="large">
        <el-table-column label="ID" prop="id" width="80" />
        <el-table-column :label="t('founderTenants.code')" prop="code" min-width="120" />
        <el-table-column :label="t('founderTenants.name')" prop="name" min-width="160" />
        <el-table-column :label="t('founderTenants.domain')" prop="domain" min-width="160" />
        <el-table-column :label="t('founderTenants.accessDomain')" min-width="220">
          <template #default="{ row }">
            <span v-if="row.access_url">{{ row.access_url }}</span>
            <span v-else class="text-gray-400">—</span>
            <el-button
              v-if="row.access_url"
              link
              type="primary"
              class="ml-1"
              @click="copyText(row.access_url)"
            >
              {{ t('founderTenants.copy') }}
            </el-button>
          </template>
        </el-table-column>
        <el-table-column :label="t('founderTenants.prefix')" prop="table_prefix" min-width="120" />
        <el-table-column :label="t('founderTenants.status')" min-width="100">
          <template #default="{ row }">
            <el-tag>{{ statusLabel(row.status) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column :label="t('crud.operation')" width="400" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="openEdit(row)">
              {{ t('crud.edit') }}
            </el-button>
            <el-button link type="success" @click="openAssign(row)">
              {{ t('founderTenants.assignApp') }}
            </el-button>
            <el-button
              link
              type="primary"
              :disabled="row.status !== 1"
              @click="enterTenant(row)"
            >
              {{ t('founderTenants.enterTenant') }}
            </el-button>
            <el-button link type="warning" @click="toggleStatus(row)">
              {{ row.status === 1 ? t('founderTenants.disable') : t('founderTenants.enable') }}
            </el-button>
            <el-button
              v-if="row.status === 6 || row.status === 5"
              link
              type="danger"
              @click="onReprovision(row)"
            >
              {{ t('founderTenants.reprovision') }}
            </el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="mt-4 flex justify-end">
        <el-pagination
          v-model:current-page="queryParams.page"
          v-model:page-size="queryParams.page_size"
          :total="total"
          layout="total, prev, pager, next"
          @current-change="loadData"
        />
      </div>
    </div>

    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="560px"
      destroy-on-close
      align-center
    >
      <el-form ref="formRef" :model="form" :rules="rules" label-width="130px">
        <el-form-item :label="t('founderTenants.name')" prop="name">
          <el-input v-model="form.name" />
        </el-form-item>
        <el-form-item v-if="!form.id" :label="t('founderTenants.domainLabel')" prop="domain">
          <div class="flex w-full items-center gap-2">
            <el-input
              v-model="form.domain"
              class="flex-1"
              :placeholder="t('founderTenants.domainPlaceholder')"
              @blur="() => form.domain = normalizeDomainLabel(form.domain)"
            />
            <span v-if="rootHost" class="shrink-0 text-gray-500">
              .{{ rootHost }}
            </span>
            <el-button @click="fillSuggestedDomain(true)">
              {{ t('founderTenants.suggestAnother') }}
            </el-button>
          </div>
        </el-form-item>
        <el-form-item :label="t('founderTenants.customDomain')">
          <el-input
            v-model="form.custom_domain"
            autocomplete="off"
            :placeholder="t('founderTenants.customDomainPlaceholder')"
          />
        </el-form-item>
        <el-form-item :label="t('founderTenants.contactPhone')">
          <el-input v-model="form.contact_phone" />
        </el-form-item>
        <el-form-item :label="t('founderTenants.contactEmail')">
          <el-input v-model="form.contact_email" />
        </el-form-item>
        <el-form-item :label="t('founderTenants.remark')">
          <el-input v-model="form.remark" type="textarea" />
        </el-form-item>
        <template v-if="!form.id">
          <el-form-item :label="t('founderTenants.adminUser')" prop="admin_user">
            <el-input v-model="form.admin_user" />
          </el-form-item>
          <el-form-item :label="t('founderTenants.adminPass')" prop="admin_pass">
            <el-input v-model="form.admin_pass" show-password />
          </el-form-item>
        </template>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">
          {{ t('crud.cancel') }}
        </el-button>
        <el-button type="primary" :loading="saving" @click="save">
          {{ t('crud.ok') }}
        </el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="assignVisible" :title="t('founderTenants.assignApp')" width="820px">
      <div v-loading="assignLoading">
        <p class="mb-3 text-gray-500">
          {{ t('founderTenants.tenantLine', { name: assignTenant?.name || '', prefix: assignTenant?.table_prefix || '' }) }}
        </p>
        <h4 class="mb-2">
          {{ t('founderTenants.attached') }}
        </h4>
        <el-table :data="attachedApps" size="small" class="mb-4">
          <el-table-column prop="identifier" :label="t('founderTenants.app')" min-width="160">
            <template #default="{ row }">
              <div class="flex flex-wrap items-center gap-2">
                <span>{{ row.identifier }}</span>
                <span v-if="formatEdition(row.edition)" class="edition-badge">{{ formatEdition(row.edition) }}</span>
              </div>
            </template>
          </el-table-column>
          <el-table-column prop="version" :label="t('founderTenants.version')" width="80" />
          <el-table-column :label="t('crud.createTime')" width="160">
            <template #default="{ row }">
              {{ row.installed_at || '-' }}
            </template>
          </el-table-column>
          <el-table-column :label="t('founderTenants.expires')" min-width="150">
            <template #default="{ row }">
              <span :class="row.expired ? 'text-red-500' : ''">{{ formatExpiresLabel(row.expires_label) }}</span>
            </template>
          </el-table-column>
          <el-table-column :label="t('founderTenants.status')" width="80">
            <template #default="{ row }">
              {{ row.status === 1 ? (row.expired ? t('founderTenants.expired') : t('founderTenants.enable')) : t('founderTenants.disable') }}
            </template>
          </el-table-column>
          <el-table-column :label="t('crud.operation')" width="150">
            <template #default="{ row }">
              <el-button link type="primary" @click="openEditAttached(row)">
                {{ t('founderTenants.modify') }}
              </el-button>
              <el-button link type="primary" @click="toggleAttached(row)">
                {{ row.status === 1 ? t('founderTenants.disable') : t('founderTenants.enable') }}
              </el-button>
            </template>
          </el-table-column>
        </el-table>
        <h4 class="mb-2">
          {{ t('founderTenants.add') }}
        </h4>
        <el-select
          v-model="selectedIdentifiers"
          multiple
          filterable
          clearable
          :placeholder="t('founderTenants.selectApps')"
          class="w-full mb-3"
        >
          <el-option
            v-for="item in availableAppOptions"
            :key="item.identifier"
            :label="formatAppOptionLabel(item)"
            :value="item.identifier"
          >
            <div class="flex items-center justify-between gap-3">
              <span>{{ formatAppOptionLabel(item) }}</span>
              <span v-if="formatEdition(item.edition)" class="edition-badge">{{ formatEdition(item.edition) }}</span>
            </div>
          </el-option>
        </el-select>
        <div class="mb-2 text-sm text-gray-600">
          {{ t('founderTenants.licensePeriod') }}
        </div>
        <el-select v-model="licensePreset" class="w-full mb-3" @change="applyLicensePreset">
          <el-option
            v-for="item in licensePresets"
            :key="item.value"
            :label="item.label"
            :value="item.value"
          />
        </el-select>
        <div v-if="licensePreset === 'custom'" class="flex items-center gap-3 mb-2">
          <el-input-number v-model="assignYears" :min="0" :max="100" />
          <span>{{ t('founderTenants.year') }}</span>
          <el-input-number v-model="assignMonths" :min="0" :max="120" />
          <span>{{ t('founderTenants.month') }}</span>
        </div>
        <p class="text-xs text-gray-400 mb-0">
          {{ t('founderTenants.licenseHint') }}
        </p>
      </div>
      <template #footer>
        <el-button @click="assignVisible = false">
          {{ t('crud.cancel') }}
        </el-button>
        <el-button type="primary" :loading="assignSaving" @click="submitAssign">
          {{ t('founderTenants.assign') }}
        </el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="editVisible" :title="t('founderTenants.editAttached')" width="480px" append-to-body>
      <p class="mb-3 text-gray-500 break-all">
        {{ editRow?.identifier }}
      </p>
      <div class="mb-2 text-sm text-gray-600">
        {{ t('founderTenants.status') }}
      </div>
      <el-radio-group v-model="editStatus" class="mb-4">
        <el-radio :value="1">
          {{ t('founderTenants.enable') }}
        </el-radio>
        <el-radio :value="2">
          {{ t('founderTenants.disable') }}
        </el-radio>
      </el-radio-group>
      <div class="mb-2 text-sm text-gray-600">
        {{ t('founderTenants.licensePeriodRecalc') }}
      </div>
      <el-select v-model="editLicensePreset" class="w-full mb-3" @change="applyEditLicensePreset">
        <el-option
          v-for="item in licensePresets"
          :key="item.value"
          :label="item.label"
          :value="item.value"
        />
      </el-select>
      <div v-if="editLicensePreset === 'custom'" class="flex items-center gap-3 mb-2">
        <el-input-number v-model="editYears" :min="0" :max="100" />
        <span>{{ t('founderTenants.year') }}</span>
        <el-input-number v-model="editMonths" :min="0" :max="120" />
        <span>{{ t('founderTenants.month') }}</span>
      </div>
      <p class="text-xs text-gray-400 mb-0">
        {{ t('founderTenants.current') }}：{{ formatExpiresLabel(editRow?.expires_label) }}
      </p>
      <template #footer>
        <el-button @click="editVisible = false">
          {{ t('crud.cancel') }}
        </el-button>
        <el-button type="primary" :loading="editSaving" @click="submitEditAttached">
          {{ t('crud.save') }}
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped lang="scss">
.edition-badge {
  display: inline-block;
  font-size: 11px;
  font-weight: 500;
  color: rgb(var(--ui-primary));
  border: 1px solid rgb(var(--ui-primary) / 35%);
  border-radius: 999px;
  padding: 0 6px;
  line-height: 18px;
  text-transform: uppercase;
  white-space: nowrap;
}
</style>
