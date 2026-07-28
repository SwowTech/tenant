<script setup lang="ts">
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessageBox } from 'element-plus'
import {
  createFounderAppDomain,
  deleteFounderAppDomain,
  getFounderAppDomains,
  updateFounderAppDomain,
  type AppDomainVo,
} from '~/base/api/appDomain'
import {
  getFounderAssignableApps,
  getFounderTenantApps,
  getFounderTenants,
  type FounderTenantVo,
} from '~/base/api/founder'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import type { TransType } from '@/hooks/auto-imports/useTrans.ts'

defineOptions({ name: 'founder:app-domains' })

const t = (useTrans() as TransType).globalTrans
const msg = useMessage()

const loading = ref(false)
const list = ref<AppDomainVo[]>([])
const tenants = ref<FounderTenantVo[]>([])
const filterTenantId = ref<number | undefined>()
const filterIdentifier = ref('')

const dialogVisible = ref(false)
const saving = ref(false)
const formRef = ref<FormInstance>()
const editingId = ref<number | null>(null)
const form = reactive({
  tenant_id: undefined as number | undefined,
  identifier: '',
  domain: '',
  scheme: 'http',
  is_primary: false,
})

const appOptions = ref<Array<{ identifier: string, label: string }>>([])

const rules = computed<FormRules>(() => ({
  tenant_id: [{ required: true, message: t('appDomains.tenantRequired'), trigger: 'change' }],
  identifier: [{ required: true, message: t('appDomains.appRequired'), trigger: 'change' }],
  domain: [{ required: true, message: t('appDomains.domainRequired'), trigger: 'blur' }],
}))

const gatewayPort = computed(() => {
  const p = Number(window.location.port || 0)
  return p > 0 ? p : 9501
})

const normalizedDomain = computed(() =>
  String(form.domain || '')
    .trim()
    .toLowerCase()
    .replace(/^https?:\/\//, '')
    .split('/')[0]
    .split(':')[0]
    .replace(/\.$/, ''),
)

const isLocalDomain = computed(() => {
  const d = normalizedDomain.value
  return d === 'localhost' || d.endsWith('.localhost') || d === '127.0.0.1'
})

const previewUrl = computed(() => {
  const d = normalizedDomain.value
  if (!d) {
    return ''
  }
  const scheme = form.scheme === 'https' ? 'https' : 'http'
  const port = gatewayPort.value
  const needPort = !([80, 443].includes(port))
  return needPort ? `${scheme}://${d}:${port}` : `${scheme}://${d}`
})

const hostsLine = computed(() =>
  normalizedDomain.value
    ? t('appDomains.dnsLocalHosts', { domain: normalizedDomain.value })
    : '',
)

async function loadTenants() {
  const res: any = await getFounderTenants({ page: 1, page_size: 100 })
  if (res.code === ResultCode.SUCCESS) {
    tenants.value = (res.data?.list || []).map((ten: FounderTenantVo) => ({
      ...ten,
      id: Number(ten.id),
    }))
  }
}

function formatTenantOption(ten: FounderTenantVo) {
  const name = String(ten.name || ten.code || '').trim()
  const domain = String(ten.domain || '').trim()
  const idPart = ten.id != null ? `#${ten.id}` : ''
  if (name && domain) {
    return `${name} (${domain}) ${idPart}`.trim()
  }
  return `${name || domain || ''} ${idPart}`.trim() || `#${ten.id}`
}

function formatTenantCell(row: AppDomainVo) {
  const idPart = row.tenant_id != null ? ` #${row.tenant_id}` : ''
  if (row.tenant_name) {
    return `${row.tenant_name}${idPart}`
  }
  const hit = tenants.value.find(t => Number(t.id) === Number(row.tenant_id))
  if (hit) {
    const name = String(hit.name || hit.code || '').trim()
    return `${name || hit.domain || ''}${idPart}`.trim() || `#${row.tenant_id}`
  }
  return row.tenant_domain ? `${row.tenant_domain}${idPart}` : `#${row.tenant_id}`
}

async function loadAppsForTenant(tenantId?: number) {
  if (!tenantId) {
    const res: any = await getFounderAssignableApps()
    if (res.code === ResultCode.SUCCESS && res.data) {
      appOptions.value = Object.keys(res.data).map(id => ({ identifier: id, label: id }))
    }
    return
  }
  const res: any = await getFounderTenantApps(tenantId)
  if (res.code === ResultCode.SUCCESS) {
    appOptions.value = (res.data || []).map((a: any) => ({
      identifier: a.identifier,
      label: a.identifier,
    }))
  }
}

async function loadList() {
  loading.value = true
  try {
    const res: any = await getFounderAppDomains({
      tenant_id: filterTenantId.value,
      identifier: filterIdentifier.value || undefined,
    })
    if (res.code === ResultCode.SUCCESS) {
      list.value = res.data?.list || []
    }
  }
  finally {
    loading.value = false
  }
}

async function openCreate() {
  editingId.value = null
  if (!tenants.value.length) {
    await loadTenants()
  }
  Object.assign(form, {
    tenant_id: filterTenantId.value ? Number(filterTenantId.value) : undefined,
    identifier: filterIdentifier.value || '',
    domain: '',
    scheme: 'http',
    is_primary: false,
  })
  await loadAppsForTenant(form.tenant_id)
  dialogVisible.value = true
}

function openEdit(row: AppDomainVo) {
  editingId.value = row.id
  const tid = Number(row.tenant_id)
  // 编辑时确保下拉能匹配到当前租户（避免只显示数字 id）
  if (!tenants.value.some(t => Number(t.id) === tid)) {
    tenants.value = [
      {
        id: tid,
        name: row.tenant_name || `#${tid}`,
        domain: row.tenant_domain || '',
        code: row.tenant_code || '',
      },
      ...tenants.value,
    ]
  }
  Object.assign(form, {
    tenant_id: tid,
    identifier: row.identifier,
    domain: row.domain,
    scheme: row.scheme || 'https',
    is_primary: !!row.is_primary,
  })
  loadAppsForTenant(tid)
  dialogVisible.value = true
}

async function onTenantChange(id: number | undefined) {
  form.identifier = ''
  await loadAppsForTenant(id)
}

async function save() {
  await formRef.value?.validate()
  saving.value = true
  try {
    if (editingId.value) {
      const res: any = await updateFounderAppDomain(editingId.value, {
        domain: form.domain.trim(),
        scheme: form.scheme,
        is_primary: form.is_primary,
      })
      if (res.code !== ResultCode.SUCCESS) {
        msg.error(res.message || t('appDomains.saveFailed'))
        return
      }
      msg.success(t('appDomains.updated'))
    }
    else {
      if (!form.tenant_id) {
        return
      }
      const res: any = await createFounderAppDomain({
        tenant_id: form.tenant_id,
        identifier: form.identifier,
        domain: form.domain.trim(),
        scheme: form.scheme,
        is_primary: form.is_primary,
      })
      if (res.code !== ResultCode.SUCCESS) {
        msg.error(res.message || t('appDomains.saveFailed'))
        return
      }
      msg.success(t('appDomains.created'))
    }
    dialogVisible.value = false
    await loadList()
  }
  finally {
    saving.value = false
  }
}

async function setPrimary(row: AppDomainVo) {
  const res: any = await updateFounderAppDomain(row.id, { is_primary: true })
  if (res.code === ResultCode.SUCCESS) {
    msg.success(t('appDomains.setPrimaryOk'))
    await loadList()
  }
  else {
    msg.error(res.message || t('appDomains.saveFailed'))
  }
}

async function remove(row: AppDomainVo) {
  await ElMessageBox.confirm(
    t('appDomains.deleteConfirm', { domain: row.domain }),
    t('appDomains.deleteTitle'),
    { type: 'warning' },
  )
  const res: any = await deleteFounderAppDomain(row.id)
  if (res.code === ResultCode.SUCCESS) {
    msg.success(t('appDomains.deleted'))
    await loadList()
  }
  else {
    msg.error(res.message || t('appDomains.deleteFailed'))
  }
}

onMounted(async () => {
  await loadTenants()
  await loadList()
})
</script>

<template>
  <div class="mine-layout p-3">
    <el-card shadow="never">
      <div class="mb-3 flex flex-wrap gap-2 items-center">
        <el-select
          v-model="filterTenantId"
          clearable
          filterable
          class="w-[220px]"
          :placeholder="t('appDomains.filterTenant')"
          @change="loadList"
        >
          <el-option
            v-for="ten in tenants"
            :key="ten.id"
            :label="formatTenantOption(ten)"
            :value="Number(ten.id)"
          />
        </el-select>
        <el-input
          v-model="filterIdentifier"
          clearable
          class="w-[240px]"
          :placeholder="t('appDomains.filterApp')"
          @keyup.enter="loadList"
        />
        <el-button type="primary" @click="loadList">
          {{ t('appDomains.search') }}
        </el-button>
        <el-button type="success" @click="openCreate">
          {{ t('appDomains.bind') }}
        </el-button>
      </div>

      <el-table v-loading="loading" :data="list" border stripe>
        <el-table-column :label="t('appDomains.tenant')" min-width="160">
          <template #default="{ row }">
            <div>{{ formatTenantCell(row) }}</div>
            <div class="text-gray-400 text-xs">
              {{ row.tenant_domain }}
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="identifier" :label="t('appDomains.app')" min-width="180" />
        <el-table-column prop="domain" :label="t('appDomains.domain')" min-width="160" />
        <el-table-column prop="scheme" :label="t('appDomains.scheme')" width="90" />
        <el-table-column :label="t('appDomains.primary')" width="90">
          <template #default="{ row }">
            <el-tag v-if="row.is_primary" type="success" size="small">
              {{ t('appDomains.yes') }}
            </el-tag>
            <el-button v-else link type="primary" @click="setPrimary(row)">
              {{ t('appDomains.setPrimary') }}
            </el-button>
          </template>
        </el-table-column>
        <el-table-column prop="public_base" :label="t('appDomains.publicBase')" min-width="200" />
        <el-table-column :label="t('appDomains.actions')" width="160" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="openEdit(row)">
              {{ t('appDomains.edit') }}
            </el-button>
            <el-button link type="danger" @click="remove(row)">
              {{ t('appDomains.unbind') }}
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog
      v-model="dialogVisible"
      :title="editingId ? t('appDomains.editTitle') : t('appDomains.bindTitle')"
      width="560px"
      destroy-on-close
    >
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
        <el-form-item :label="t('appDomains.tenant')" prop="tenant_id">
          <el-select
            v-model="form.tenant_id"
            filterable
            class="w-full"
            :disabled="!!editingId"
            @change="onTenantChange"
          >
            <el-option
              v-for="ten in tenants"
              :key="`dlg-${ten.id}`"
              :label="formatTenantOption(ten)"
              :value="Number(ten.id)"
            />
          </el-select>
        </el-form-item>
        <el-form-item :label="t('appDomains.app')" prop="identifier">
          <el-select
            v-model="form.identifier"
            filterable
            allow-create
            default-first-option
            class="w-full"
            :disabled="!!editingId"
          >
            <el-option
              v-for="app in appOptions"
              :key="app.identifier"
              :label="app.label"
              :value="app.identifier"
            />
          </el-select>
        </el-form-item>
        <el-form-item :label="t('appDomains.domain')" prop="domain">
          <el-input v-model="form.domain" :placeholder="t('appDomains.domainPlaceholder')" />
        </el-form-item>
        <el-form-item :label="t('appDomains.scheme')">
          <el-radio-group v-model="form.scheme">
            <el-radio value="http">
              http
            </el-radio>
            <el-radio value="https">
              https
            </el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item :label="t('appDomains.primary')">
          <el-switch v-model="form.is_primary" />
        </el-form-item>
        <el-alert
          class="mb-1"
          type="info"
          show-icon
          :closable="false"
          :title="t('appDomains.dnsTitle')"
        >
          <template #default>
            <div v-if="!normalizedDomain" class="text-sm leading-6">
              {{ t('appDomains.dnsEmpty') }}
            </div>
            <div v-else-if="isLocalDomain" class="text-sm leading-6 space-y-1">
              <div>{{ t('appDomains.dnsLocal') }}</div>
              <code class="block bg-gray-50 px-2 py-1 rounded select-all">{{ hostsLine }}</code>
              <div>{{ t('appDomains.dnsLocalOpen', { url: previewUrl }) }}</div>
            </div>
            <div v-else class="text-sm leading-6 space-y-1">
              <div>{{ t('appDomains.dnsProd') }}</div>
              <div>• {{ t('appDomains.dnsProdA') }}</div>
              <div>• {{ t('appDomains.dnsProdCname') }}</div>
              <div>{{ t('appDomains.dnsProdPort', { domain: normalizedDomain, port: gatewayPort, url: previewUrl }) }}</div>
            </div>
          </template>
        </el-alert>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">
          {{ t('appDomains.cancel') }}
        </el-button>
        <el-button type="primary" :loading="saving" @click="save">
          {{ t('appDomains.save') }}
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>
