<script setup lang="ts">
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { TransType } from '@/hooks/auto-imports/useTrans.ts'
import {
  bindAppDomain,
  changeAppAdminPassword,
  deleteAppDomain,
  getAppSettings,
  getWelcomeInstalledApps,
  migrateAppData,
  updateAppDomain,
  type AppSettingsDomain,
} from '~/base/api/welcome'
import { ResultCode } from '@/utils/ResultCode.ts'

defineOptions({ name: 'WelcomeInstalledApps' })

const t = (useTrans() as TransType).globalTrans
const loading = ref(false)
const migrateVisible = ref(false)
const migrateTarget = ref<InstalledApp | null>(null)
const migrateFrom = ref('')
const migrateSubmitting = ref(false)

const settingsVisible = ref(false)
const settingsApp = ref<InstalledApp | null>(null)
const settingsLoading = ref(false)
const settingsSaving = ref(false)
const domains = ref<AppSettingsDomain[]>([])
const adminSupported = ref(false)
const adminUsername = ref('admin')

const domainFormRef = ref<FormInstance>()
const domainForm = reactive({
  domain: '',
  scheme: 'http',
  is_primary: true,
})
const passwordFormRef = ref<FormInstance>()
const passwordForm = reactive({
  username: 'admin',
  new_password: '',
  new_password_confirmation: '',
})

interface InstalledApp {
  identifier: string
  title: string
  version: string
  edition?: string
  family?: string
  open_url: string
  has_web: boolean
  status?: number
  enabled?: boolean
  installed_at?: string | null
  expires_at?: string | null
  expired?: boolean
  expires_label?: string
  can_migrate_from?: string[]
}

interface AppFamilyGroup {
  family: string
  editions: InstalledApp[]
}

const groups = ref<AppFamilyGroup[]>([])

const totalCount = computed(() =>
  groups.value.reduce((sum, g) => sum + g.editions.length, 0),
)

const migrateSources = computed(() => migrateTarget.value?.can_migrate_from ?? [])

const gatewayPort = computed(() => {
  const p = Number(window.location.port || 0)
  return p > 0 ? p : 9501
})

const normalizedDomain = computed(() =>
  String(domainForm.domain || '')
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
  const scheme = domainForm.scheme === 'https' ? 'https' : 'http'
  const port = gatewayPort.value
  const needPort = !([80, 443].includes(port))
  return needPort ? `${scheme}://${d}:${port}` : `${scheme}://${d}`
})

const hostsLine = computed(() =>
  normalizedDomain.value
    ? t('appDomains.dnsLocalHosts', { domain: normalizedDomain.value })
    : '',
)

const domainRules = computed<FormRules>(() => ({
  domain: [{ required: true, message: t('appDomains.domainRequired'), trigger: 'blur' }],
}))

const passwordRules = computed<FormRules>(() => ({
  new_password: [
    { required: true, message: t('appsMine.passwordRequired'), trigger: 'blur' },
    { min: 6, message: t('appsMine.passwordMin'), trigger: 'blur' },
  ],
  new_password_confirmation: [
    { required: true, message: t('appsMine.passwordConfirmRequired'), trigger: 'blur' },
    {
      validator: (_r, v, cb) => {
        if (v !== passwordForm.new_password) {
          cb(new Error(t('appsMine.passwordMismatch')))
        }
        else {
          cb()
        }
      },
      trigger: 'blur',
    },
  ],
}))

function canMigrate(app: InstalledApp) {
  return Array.isArray(app.can_migrate_from) && app.can_migrate_from.length > 0
}

function isEnabled(app: { enabled?: boolean, status?: number }) {
  if (typeof app.enabled === 'boolean') {
    return app.enabled
  }
  return app.status !== 2
}

function formatEdition(edition?: string) {
  const value = String(edition || '').trim()
  return value ? value.toUpperCase() : ''
}

function groupTitle(group: AppFamilyGroup) {
  const first = group.editions[0]
  if (first?.title) {
    return first.title
  }
  return group.family
}

function normalizeGroups(data: { groups?: AppFamilyGroup[], list?: InstalledApp[] } | null | undefined): AppFamilyGroup[] {
  const rawGroups = data?.groups
  if (Array.isArray(rawGroups) && rawGroups.length > 0) {
    return rawGroups.map(g => ({
      family: g.family,
      editions: g.editions ?? [],
    }))
  }

  const list = data?.list ?? []
  const map = new Map<string, AppFamilyGroup>()
  for (const app of list) {
    const family = app.family || app.identifier
    if (!map.has(family)) {
      map.set(family, { family, editions: [] })
    }
    map.get(family)!.editions.push(app)
  }
  return Array.from(map.values())
}

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
  return label
}

function cardActionLabel(app: InstalledApp) {
  if (!isEnabled(app)) {
    return t('founderTenants.statusDisabled')
  }
  if (app.expired) {
    return t('founderTenants.expired')
  }
  if (app.has_web) {
    return t('welcomePage.openApp')
  }
  return t('welcomePage.appEnabled')
}

async function load() {
  loading.value = true
  try {
    const res: any = await getWelcomeInstalledApps()
    if (res.code === ResultCode.SUCCESS) {
      groups.value = normalizeGroups(res.data)
    }
  }
  catch {
    ElMessage.error(t('welcomePage.loadAppsFailed'))
  }
  finally {
    loading.value = false
  }
}

function openApp(app: InstalledApp) {
  if (!isEnabled(app)) {
    ElMessage.warning(t('founderTenants.msgDisabled'))
    return
  }
  if (app.expired) {
    ElMessage.warning(t('welcomePage.appExpired'))
    return
  }
  if (!app.has_web || !app.open_url) {
    ElMessage.warning(t('welcomePage.noAppEntry'))
    return
  }
  window.open(app.open_url, '_blank')
}

function openMigrateDialog(app: InstalledApp) {
  const sources = app.can_migrate_from ?? []
  if (sources.length === 0) {
    return
  }
  migrateTarget.value = app
  migrateFrom.value = sources.length === 1 ? sources[0] : ''
  migrateVisible.value = true
}

async function confirmMigrate() {
  const target = migrateTarget.value
  const fromId = migrateFrom.value.trim()
  if (!target || fromId === '') {
    ElMessage.warning(t('appsEdition.migrateData'))
    return
  }

  try {
    await ElMessageBox.confirm(
      t('appsEdition.migrateConfirm', { from: fromId, to: target.identifier }),
      t('appsEdition.migrateData'),
      {
        type: 'warning',
        cancelButtonText: t('crud.cancel'),
        confirmButtonText: t('crud.ok'),
      },
    )
  }
  catch {
    return
  }

  migrateSubmitting.value = true
  try {
    const res: any = await migrateAppData({ from: fromId, to: target.identifier })
    if (res.code === ResultCode.SUCCESS) {
      ElMessage.success(res.message || t('appsEdition.migrateData'))
      migrateVisible.value = false
    }
    else {
      ElMessage.error(res.message || t('welcomePage.loadAppsFailed'))
    }
  }
  catch (e: any) {
    ElMessage.error(e?.message || t('welcomePage.loadAppsFailed'))
  }
  finally {
    migrateSubmitting.value = false
  }
}

async function openSettings(app: InstalledApp) {
  settingsApp.value = app
  settingsVisible.value = true
  Object.assign(domainForm, { domain: '', scheme: 'http', is_primary: domains.value.length === 0 })
  Object.assign(passwordForm, {
    username: 'admin',
    new_password: '',
    new_password_confirmation: '',
  })
  await loadSettings()
}

async function loadSettings() {
  const app = settingsApp.value
  if (!app) {
    return
  }
  settingsLoading.value = true
  try {
    const res: any = await getAppSettings(app.identifier)
    if (res.code === ResultCode.SUCCESS) {
      domains.value = res.data?.domains || []
      adminSupported.value = !!res.data?.admin?.supported
      adminUsername.value = res.data?.admin?.username || 'admin'
      passwordForm.username = adminUsername.value
      if (domains.value.length === 0) {
        domainForm.is_primary = true
      }
    }
    else {
      ElMessage.error(res.message || t('appsMine.loadSettingsFailed'))
    }
  }
  catch (e: any) {
    ElMessage.error(e?.message || t('appsMine.loadSettingsFailed'))
  }
  finally {
    settingsLoading.value = false
  }
}

async function saveDomain() {
  const app = settingsApp.value
  if (!app) {
    return
  }
  await domainFormRef.value?.validate()
  settingsSaving.value = true
  try {
    const res: any = await bindAppDomain({
      identifier: app.identifier,
      domain: normalizedDomain.value,
      scheme: domainForm.scheme,
      is_primary: domainForm.is_primary,
    })
    if (res.code === ResultCode.SUCCESS) {
      ElMessage.success(t('appDomains.created'))
      domainForm.domain = ''
      await loadSettings()
    }
    else {
      ElMessage.error(res.message || t('appDomains.saveFailed'))
    }
  }
  catch (e: any) {
    ElMessage.error(e?.message || t('appDomains.saveFailed'))
  }
  finally {
    settingsSaving.value = false
  }
}

async function setPrimary(row: AppSettingsDomain) {
  try {
    const res: any = await updateAppDomain(row.id, { is_primary: true })
    if (res.code === ResultCode.SUCCESS) {
      ElMessage.success(t('appDomains.setPrimaryOk'))
      await loadSettings()
    }
  }
  catch (e: any) {
    ElMessage.error(e?.message || t('appDomains.saveFailed'))
  }
}

async function removeDomain(row: AppSettingsDomain) {
  try {
    await ElMessageBox.confirm(
      t('appDomains.deleteConfirm', { domain: row.domain }),
      t('appDomains.deleteTitle'),
      { type: 'warning' },
    )
  }
  catch {
    return
  }
  try {
    const res: any = await deleteAppDomain(row.id)
    if (res.code === ResultCode.SUCCESS) {
      ElMessage.success(t('appDomains.deleted'))
      await loadSettings()
    }
  }
  catch (e: any) {
    ElMessage.error(e?.message || t('appDomains.deleteFailed'))
  }
}

async function savePassword() {
  const app = settingsApp.value
  if (!app || !adminSupported.value) {
    return
  }
  await passwordFormRef.value?.validate()
  settingsSaving.value = true
  try {
    const res: any = await changeAppAdminPassword({
      identifier: app.identifier,
      username: passwordForm.username || 'admin',
      new_password: passwordForm.new_password,
      new_password_confirmation: passwordForm.new_password_confirmation,
    })
    if (res.code === ResultCode.SUCCESS) {
      ElMessage.success(t('appsMine.passwordChanged'))
      passwordForm.new_password = ''
      passwordForm.new_password_confirmation = ''
    }
    else {
      ElMessage.error(res.message || t('appsMine.passwordFailed'))
    }
  }
  catch (e: any) {
    ElMessage.error(e?.message || t('appsMine.passwordFailed'))
  }
  finally {
    settingsSaving.value = false
  }
}

onMounted(load)
</script>

<template>
  <div v-loading="loading" class="mine-card">
    <div class="flex items-center justify-between mb-3">
      <div class="text-base font-medium">
        {{ t('welcomePage.myApps') }}
      </div>
      <el-button link type="primary" @click="load">
        {{ t('welcomePage.refresh') }}
      </el-button>
    </div>

    <div v-if="totalCount === 0" class="py-8 text-center text-gray-400 text-sm">
      {{ t('welcomePage.noAppsHint') }}
    </div>

    <div v-else class="app-grid">
      <template v-for="group in groups" :key="group.family">
        <div
          v-if="group.editions.length > 1"
          class="app-family__header app-grid__span"
        >
          <div class="app-family__title">
            {{ groupTitle(group) }}
          </div>
          <div class="app-family__meta">
            {{ t('appsEdition.family') }}：{{ group.family }}
          </div>
        </div>

        <button
          v-for="app in group.editions"
          :key="app.identifier"
          type="button"
          class="app-card"
          :class="{ 'is-expired': app.expired || !isEnabled(app) }"
          @click="openApp(app)"
        >
          <div class="app-card__title">
            {{ app.title }}
            <span v-if="formatEdition(app.edition)" class="app-card__edition">{{ formatEdition(app.edition) }}</span>
            <span v-if="!isEnabled(app)" class="app-card__badge">{{ t('founderTenants.statusDisabled') }}</span>
            <span v-else-if="app.expired" class="app-card__badge">{{ t('founderTenants.expired') }}</span>
          </div>
          <div class="app-card__meta">
            {{ app.identifier }} · v{{ app.version }}
          </div>
          <div class="app-card__meta">
            {{ t('welcomePage.installedAt') }}：{{ app.installed_at || '-' }}
          </div>
          <div class="app-card__meta">
            {{ t('founderTenants.expires') }}：{{ formatExpiresLabel(app.expires_label) }}
          </div>
          <div class="app-card__footer">
            <div class="app-card__action">
              {{ cardActionLabel(app) }}
            </div>
            <div class="app-card__links">
              <button
                type="button"
                class="app-card__link"
                @click.stop="openSettings(app)"
              >
                {{ t('appsMine.settings') }}
              </button>
              <button
                v-if="canMigrate(app)"
                type="button"
                class="app-card__link"
                @click.stop="openMigrateDialog(app)"
              >
                {{ t('appsEdition.migrateData') }}
              </button>
            </div>
          </div>
        </button>
      </template>
    </div>

    <el-dialog
      v-model="migrateVisible"
      :title="t('appsEdition.migrateData')"
      width="420px"
      destroy-on-close
      @closed="migrateTarget = null"
    >
      <div v-if="migrateTarget" class="migrate-dialog">
        <div class="migrate-dialog__target">
          {{ migrateTarget.title }} ({{ migrateTarget.identifier }})
        </div>
        <el-select
          v-model="migrateFrom"
          class="w-full"
          :placeholder="t('appsEdition.migrateData')"
        >
          <el-option
            v-for="sourceId in migrateSources"
            :key="sourceId"
            :label="sourceId"
            :value="sourceId"
          />
        </el-select>
      </div>
      <template #footer>
        <el-button @click="migrateVisible = false">
          {{ t('crud.cancel') }}
        </el-button>
        <el-button type="primary" :loading="migrateSubmitting" @click="confirmMigrate">
          {{ t('crud.ok') }}
        </el-button>
      </template>
    </el-dialog>

    <el-dialog
      v-model="settingsVisible"
      :title="settingsApp ? `${t('appsMine.settings')} - ${settingsApp.title}` : t('appsMine.settings')"
      width="640px"
      destroy-on-close
      @closed="settingsApp = null"
    >
      <div v-loading="settingsLoading" class="settings-dialog">
        <div class="settings-section">
          <div class="settings-section__title">
            {{ t('appsMine.domainSection') }}
          </div>
          <el-table v-if="domains.length" :data="domains" size="small" border class="mb-3">
            <el-table-column prop="domain" :label="t('appDomains.domain')" min-width="140" />
            <el-table-column prop="scheme" :label="t('appDomains.scheme')" width="80" />
            <el-table-column :label="t('appDomains.primary')" width="100">
              <template #default="{ row }">
                <el-tag v-if="row.is_primary" type="success" size="small">
                  {{ t('appDomains.yes') }}
                </el-tag>
                <el-button v-else link type="primary" @click="setPrimary(row)">
                  {{ t('appDomains.setPrimary') }}
                </el-button>
              </template>
            </el-table-column>
            <el-table-column :label="t('appDomains.actions')" width="120">
              <template #default="{ row }">
                <el-button link type="danger" @click="removeDomain(row)">
                  {{ t('appDomains.unbind') }}
                </el-button>
              </template>
            </el-table-column>
          </el-table>
          <el-form ref="domainFormRef" :model="domainForm" :rules="domainRules" label-width="88px">
            <el-form-item :label="t('appDomains.domain')" prop="domain">
              <el-input v-model="domainForm.domain" :placeholder="t('appDomains.domainPlaceholder')" />
            </el-form-item>
            <el-form-item :label="t('appDomains.scheme')">
              <el-radio-group v-model="domainForm.scheme">
                <el-radio value="http">
                  http
                </el-radio>
                <el-radio value="https">
                  https
                </el-radio>
              </el-radio-group>
            </el-form-item>
            <el-form-item :label="t('appDomains.primary')">
              <el-switch v-model="domainForm.is_primary" />
            </el-form-item>
            <el-alert
              v-if="normalizedDomain"
              class="mb-3"
              type="info"
              show-icon
              :closable="false"
              :title="t('appDomains.dnsTitle')"
            >
              <div v-if="isLocalDomain" class="text-sm leading-6 space-y-1">
                <div>{{ t('appDomains.dnsLocal') }}</div>
                <code class="block bg-gray-50 px-2 py-1 rounded select-all">{{ hostsLine }}</code>
                <div>{{ t('appDomains.dnsLocalOpen', { url: previewUrl }) }}</div>
              </div>
              <div v-else class="text-sm leading-6 space-y-1">
                <div>{{ t('appDomains.dnsProd') }}</div>
                <div>• {{ t('appDomains.dnsProdA') }}</div>
                <div>• {{ t('appDomains.dnsProdCname') }}</div>
              </div>
            </el-alert>
            <el-button type="primary" :loading="settingsSaving" @click="saveDomain">
              {{ t('appDomains.bind') }}
            </el-button>
          </el-form>
        </div>

        <el-divider />

        <div class="settings-section">
          <div class="settings-section__title">
            {{ t('appsMine.passwordSection') }}
          </div>
          <el-alert
            v-if="!adminSupported"
            type="warning"
            :closable="false"
            show-icon
            :title="t('appsMine.passwordUnsupported')"
            class="mb-2"
          />
          <el-form
            v-else
            ref="passwordFormRef"
            :model="passwordForm"
            :rules="passwordRules"
            label-width="100px"
          >
            <el-form-item :label="t('appsMine.adminUser')">
              <el-input v-model="passwordForm.username" />
            </el-form-item>
            <el-form-item :label="t('appsMine.newPassword')" prop="new_password">
              <el-input v-model="passwordForm.new_password" type="password" show-password autocomplete="new-password" />
            </el-form-item>
            <el-form-item :label="t('appsMine.confirmPassword')" prop="new_password_confirmation">
              <el-input v-model="passwordForm.new_password_confirmation" type="password" show-password autocomplete="new-password" />
            </el-form-item>
            <el-button type="primary" :loading="settingsSaving" @click="savePassword">
              {{ t('appsMine.savePassword') }}
            </el-button>
          </el-form>
        </div>
      </div>
      <template #footer>
        <el-button @click="settingsVisible = false">
          {{ t('crud.cancel') }}
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped lang="scss">
.app-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 12px;

  &__span {
    grid-column: 1 / -1;
  }
}

.app-family {
  &__header {
    margin-top: 4px;
  }

  &__title {
    font-size: 14px;
    font-weight: 600;
    color: inherit;
  }

  &__meta {
    margin-top: 4px;
    font-size: 12px;
    color: #94a3b8;
  }
}

.app-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
  padding: 14px 16px;
  border: 1px solid rgb(var(--ui-primary) / 12%);
  border-radius: 12px;
  background: rgb(var(--ui-primary) / 4%);
  text-align: left;
  cursor: pointer;
  transition: all 0.15s ease;

  &:hover {
    border-color: rgb(var(--ui-primary) / 35%);
    background: rgb(var(--ui-primary) / 8%);
  }

  &.is-expired {
    opacity: 0.72;
    cursor: not-allowed;
  }

  &__title {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 600;
    color: inherit;
  }

  &__edition {
    font-size: 11px;
    font-weight: 500;
    color: rgb(var(--ui-primary));
    border: 1px solid rgb(var(--ui-primary) / 35%);
    border-radius: 999px;
    padding: 0 6px;
    line-height: 18px;
    text-transform: uppercase;
  }

  &__badge {
    font-size: 11px;
    font-weight: 500;
    color: #ef4444;
    border: 1px solid rgb(239 68 68 / 35%);
    border-radius: 999px;
    padding: 0 6px;
    line-height: 18px;
  }

  &__meta {
    font-size: 12px;
    color: #94a3b8;
    word-break: break-all;
  }

  &__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    width: 100%;
    margin-top: 4px;
  }

  &__action {
    font-size: 12px;
    color: rgb(var(--ui-primary));
  }

  &__links {
    display: flex;
    gap: 10px;
  }

  &__link {
    border: none;
    background: transparent;
    padding: 0;
    font-size: 12px;
    color: #64748b;
    cursor: pointer;

    &:hover {
      color: rgb(var(--ui-primary));
    }
  }

  &.is-expired &__action {
    color: #ef4444;
  }
}

.migrate-dialog {
  display: flex;
  flex-direction: column;
  gap: 12px;

  &__target {
    font-size: 13px;
    color: #64748b;
    word-break: break-all;
  }
}

.settings-dialog {
  min-height: 120px;
}

.settings-section {
  &__title {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 12px;
  }
}
</style>
