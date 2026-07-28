<script setup lang="ts">
import type { FormInstance, FormRules } from 'element-plus'
import Message from 'vue-m-message'
import { useI18n } from 'vue-i18n'
import useUserStore from '@/store/modules/useUserStore.ts'
import useSettingStore from '@/store/modules/useSettingStore.ts'
import useHttp from '@/hooks/auto-imports/useHttp.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import { setTenantContext } from '@/utils/tenantContext.ts'

defineOptions({ name: 'tenant-auth-form' })

const { t } = useI18n()
const http = useHttp()
const userStore = useUserStore()
const settingStore = useSettingStore()
const router = useRouter()

const mode = ref<'login' | 'register'>('login')
const loading = ref(false)
const formRef = ref<FormInstance>()
const rootHost = ref('')
const registerEnabled = ref(true)
const tenantDomainLocked = ref(false)
const resolvedTenantName = ref('')

const loginForm = reactive({
  domain: '',
  username: '',
  password: '',
})

const registerForm = reactive({
  name: '',
  domain: '',
  custom_domain: '',
  contact_phone: '',
  contact_email: '',
  remark: '',
  admin_user: 'admin',
  admin_pass: '',
})

function normalizeDomainLabel(raw: string) {
  let domain = String(raw || '').trim().toLowerCase()
  const host = rootHost.value.trim().toLowerCase()
  if (host && domain.endsWith(`.${host}`)) {
    domain = domain.slice(0, -(host.length + 1))
  }
  if (domain.includes('://')) {
    try {
      domain = new URL(domain.includes('://') ? domain : `http://${domain}`).hostname.split('.')[0] || domain
    }
    catch {
      // keep
    }
  }
  return domain.replace(/[^a-z0-9-]/g, '')
}

/** 从当前 Host 解析租户子域（如 acme.localhost / acme.swow.tech） */
function detectDomainFromHostname(): string {
  const hostname = window.location.hostname.toLowerCase()
  if (!hostname || hostname === 'localhost' || hostname === '127.0.0.1') {
    return ''
  }

  const configured = rootHost.value.trim().toLowerCase()
  // 控制台 apex / www：swow.tech → 可手填租户标识，勿把品牌名当成租户
  if (configured) {
    if (hostname === configured || hostname === `www.${configured}`) {
      return ''
    }
    if (hostname.endsWith(`.${configured}`)) {
      const label = hostname.slice(0, -(configured.length + 1)).split('.')[0] || ''
      if (['www', 'api', 'admin', 'www-api'].includes(label)) {
        return ''
      }
      return /^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/.test(label) ? label : ''
    }
    // 已配置 root_host 但当前 Host 不是其子域 → 不猜测
    return ''
  }

  // 未拿到 root_host：仅识别 *.localhost
  const parts = hostname.split('.')
  if (parts.length === 2 && parts[1] === 'localhost') {
    const sub = parts[0]
    return /^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/.test(sub) ? sub : ''
  }

  return ''
}

async function validateDomainUnique(_rule: unknown, value: string, callback: (error?: Error) => void) {
  const domain = normalizeDomainLabel(value)
  if (!domain) {
    callback(new Error('请输入域名标识'))
    return
  }
  if (!/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/.test(domain)) {
    callback(new Error('仅支持小写字母、数字、连字符'))
    return
  }
  try {
    const res: any = await http.get('/api/v1/tenant/domain-available', { params: { domain } })
    if (res.code === ResultCode.SUCCESS) {
      if (res.data?.root_host) {
        rootHost.value = res.data.root_host
      }
      if (res.data?.available === true) {
        callback()
        return
      }
    }
    callback(new Error('域名标识已存在'))
  }
  catch {
    callback(new Error('域名检测失败，请稍后重试'))
  }
}

const loginRules = computed<FormRules>(() => ({
  domain: [{ required: true, message: t('tenantLogin.tenantRequired'), trigger: 'blur' }],
  username: [{ required: true, message: t('tenantLogin.usernamePlaceholder'), trigger: 'blur' }],
  password: [{ required: true, message: t('tenantLogin.passwordPlaceholder'), trigger: 'blur' }],
}))

const registerRules = computed<FormRules>(() => ({
  name: [{ required: true, message: '请输入名称', trigger: 'blur' }],
  domain: [
    { required: true, message: '请输入域名标识', trigger: 'blur' },
    { validator: validateDomainUnique, trigger: 'blur' },
  ],
  admin_user: [{ required: true, message: '请输入管理员账号', trigger: 'blur' }],
  admin_pass: [
    { required: true, message: '请输入管理员密码', trigger: 'blur' },
    { min: 8, message: '密码至少 8 位', trigger: 'blur' },
  ],
}))

async function resolveTenant(domainRaw: string) {
  const domain = normalizeDomainLabel(domainRaw)
  if (!domain) {
    throw new Error(t('tenantLogin.tenantRequired'))
  }
  const res: any = await http.get('/api/v1/tenant/resolve', { params: { domain } })
  if (res.code !== ResultCode.SUCCESS || !res.data?.id) {
    throw new Error(res.message || t('tenantLogin.tenantResolveFailed'))
  }
  if (res.data.root_host) {
    rootHost.value = String(res.data.root_host)
  }
  setTenantContext(Number(res.data.id), String(res.data.name || ''))
  resolvedTenantName.value = String(res.data.name || '')
  loginForm.domain = String(res.data.domain || domain)
  return res.data
}

async function loadMeta() {
  try {
    const [authRes, suggestRes]: any[] = await Promise.all([
      http.get('/api/v1/auth-config'),
      http.get('/api/v1/tenant/suggest-domain'),
    ])
    if (authRes.code === ResultCode.SUCCESS && authRes.data) {
      registerEnabled.value = !!authRes.data.register_enabled
    }
    if (suggestRes.code === ResultCode.SUCCESS && suggestRes.data) {
      if (suggestRes.data.root_host) {
        rootHost.value = String(suggestRes.data.root_host)
      }
      if (!registerForm.domain && suggestRes.data.domain) {
        registerForm.domain = suggestRes.data.domain
      }
    }
  }
  catch {
    // ignore
  }

  const fromHost = detectDomainFromHostname()
  if (fromHost) {
    tenantDomainLocked.value = true
    loginForm.domain = fromHost
    try {
      await resolveTenant(fromHost)
    }
    catch {
      // 子域无效时仍锁定展示，登录时再报错
    }
  }
}

async function fillSuggestedDomain(force = false) {
  try {
    const res: any = await http.get('/api/v1/tenant/suggest-domain')
    if (res.code === ResultCode.SUCCESS && res.data?.domain) {
      if (res.data.root_host) {
        rootHost.value = String(res.data.root_host)
      }
      if (force || !String(registerForm.domain || '').trim()) {
        registerForm.domain = res.data.domain
        formRef.value?.clearValidate?.('domain')
      }
    }
  }
  catch {
    Message.error('获取建议域名失败')
  }
}

function switchMode(next: 'login' | 'register') {
  if (next === 'register' && !registerEnabled.value) {
    Message.warning('当前未开放注册')
    return
  }
  mode.value = next
  nextTick(() => formRef.value?.clearValidate())
}

async function submitLogin() {
  loginForm.domain = normalizeDomainLabel(loginForm.domain)
  await formRef.value?.validate()
  loading.value = true
  try {
    await resolveTenant(loginForm.domain)
    // 注册开通的是 SYSTEM 管理员，进入租户后台（非 /uc 成员中心）
    const userData = await userStore.login({
      username: loginForm.username,
      password: loginForm.password,
      code: import.meta.env.MODE === 'production' ? '' : '1234',
    })
    const welcomePath = settingStore.getSettings('welcomePage')?.path ?? '/'
    const redirect = router.currentRoute.value.query?.redirect as string | undefined
    if (userData) {
      await router.push({ path: redirect || welcomePath || '/' })
    }
  }
  catch (e: any) {
    Message.error(e?.message || e?.response?.data?.message || '登录失败')
  }
  finally {
    loading.value = false
  }
}

async function submitRegister() {
  if (!registerEnabled.value) {
    Message.error('当前未开放注册')
    return
  }
  registerForm.domain = normalizeDomainLabel(registerForm.domain)
  await formRef.value?.validate()
  loading.value = true
  try {
    const res: any = await http.post('/api/v1/tenant/register', {
      name: registerForm.name.trim(),
      domain: registerForm.domain,
      custom_domain: String(registerForm.custom_domain || '').trim(),
      contact_phone: String(registerForm.contact_phone || '').trim(),
      contact_email: String(registerForm.contact_email || '').trim(),
      remark: String(registerForm.remark || '').trim(),
      admin_user: registerForm.admin_user.trim(),
      admin_pass: registerForm.admin_pass,
    })
    if (res.code !== ResultCode.SUCCESS) {
      Message.error(res.message || '注册失败')
      return
    }
    const tenant = res.data || {}
    if (tenant.id) {
      setTenantContext(Number(tenant.id), String(tenant.name || ''))
      resolvedTenantName.value = String(tenant.name || '')
    }
    loginForm.domain = String(tenant.domain || registerForm.domain)
    loginForm.username = registerForm.admin_user
    loginForm.password = ''
    mode.value = 'login'
    Message.success(t('tenantLogin.registerSuccess'))
    nextTick(() => formRef.value?.clearValidate())
  }
  catch (e: any) {
    Message.error(e?.message || e?.response?.data?.message || '注册失败')
  }
  finally {
    loading.value = false
  }
}

async function onSubmit() {
  if (mode.value === 'login') {
    await submitLogin()
  }
  else {
    await submitRegister()
  }
}

onMounted(loadMeta)
</script>

<template>
  <div class="auth-panel">
    <div class="auth-tabs" role="tablist">
      <button
        type="button"
        class="auth-tab"
        :class="{ 'is-active': mode === 'login' }"
        @click="switchMode('login')"
      >
        {{ t('tenantLogin.tabLogin') }}
      </button>
      <button
        type="button"
        class="auth-tab"
        :class="{ 'is-active': mode === 'register' }"
        :disabled="!registerEnabled"
        @click="switchMode('register')"
      >
        {{ t('tenantLogin.tabRegister') }}
      </button>
    </div>

    <el-form
      ref="formRef"
      class="auth-form"
      label-position="top"
      :model="mode === 'login' ? loginForm : registerForm"
      :rules="mode === 'login' ? loginRules : registerRules"
      @submit.prevent="onSubmit"
    >
      <template v-if="mode === 'login'">
        <el-form-item :label="t('tenantLogin.tenantDomain')" prop="domain">
          <el-input
            v-model="loginForm.domain"
            size="large"
            :placeholder="t('tenantLogin.tenantDomainPlaceholder')"
            :disabled="tenantDomainLocked"
            clearable
            @blur="loginForm.domain = normalizeDomainLabel(loginForm.domain)"
          >
            <template v-if="rootHost" #append>
              .{{ rootHost }}
            </template>
          </el-input>
          <div v-if="tenantDomainLocked || resolvedTenantName" class="tenant-hint">
            <span v-if="resolvedTenantName">{{ resolvedTenantName }}</span>
            <span v-if="tenantDomainLocked">{{ t('tenantLogin.tenantDomainLockedHint') }}</span>
          </div>
        </el-form-item>
        <el-form-item :label="t('tenantLogin.username')" prop="username">
          <el-input
            v-model="loginForm.username"
            size="large"
            :placeholder="t('tenantLogin.usernamePlaceholder')"
            clearable
          />
        </el-form-item>
        <el-form-item :label="t('tenantLogin.password')" prop="password">
          <el-input
            v-model="loginForm.password"
            size="large"
            type="password"
            show-password
            :placeholder="t('tenantLogin.passwordPlaceholder')"
            @keyup.enter="onSubmit"
          />
        </el-form-item>
      </template>

      <template v-else>
        <el-form-item :label="t('tenantLogin.name')" prop="name">
          <el-input v-model="registerForm.name" size="large" :placeholder="t('tenantLogin.namePlaceholder')" clearable />
        </el-form-item>
        <el-form-item :label="t('tenantLogin.domain')" prop="domain">
          <div class="domain-row">
            <el-input
              v-model="registerForm.domain"
              size="large"
              class="domain-row__input"
              :placeholder="t('tenantLogin.domainPlaceholder')"
              @blur="registerForm.domain = normalizeDomainLabel(registerForm.domain)"
            />
            <span v-if="rootHost" class="domain-row__host">
              .{{ rootHost }}
            </span>
            <el-button size="large" @click="fillSuggestedDomain(true)">
              {{ t('tenantLogin.suggestDomain') }}
            </el-button>
          </div>
        </el-form-item>
        <el-form-item :label="t('tenantLogin.customDomain')">
          <el-input
            v-model="registerForm.custom_domain"
            size="large"
            autocomplete="off"
            :placeholder="t('tenantLogin.customDomainPlaceholder')"
            clearable
          />
        </el-form-item>
        <div class="auth-grid">
          <el-form-item :label="t('tenantLogin.phone')">
            <el-input v-model="registerForm.contact_phone" size="large" clearable />
          </el-form-item>
          <el-form-item :label="t('tenantLogin.email')">
            <el-input v-model="registerForm.contact_email" size="large" clearable />
          </el-form-item>
        </div>
        <el-form-item :label="t('tenantLogin.remark')">
          <el-input v-model="registerForm.remark" type="textarea" :rows="2" :placeholder="t('tenantLogin.remarkPlaceholder')" />
        </el-form-item>
        <div class="auth-grid">
          <el-form-item :label="t('tenantLogin.adminUser')" prop="admin_user">
            <el-input v-model="registerForm.admin_user" size="large" clearable />
          </el-form-item>
          <el-form-item :label="t('tenantLogin.adminPass')" prop="admin_pass">
            <el-input
              v-model="registerForm.admin_pass"
              size="large"
              type="password"
              show-password
              :placeholder="t('tenantLogin.adminPassPlaceholder')"
            />
          </el-form-item>
        </div>
      </template>

      <el-button
        class="auth-submit"
        type="primary"
        size="large"
        native-type="submit"
        :loading="loading"
      >
        {{ mode === 'register' ? t('tenantLogin.submitRegister') : t('tenantLogin.submitLogin') }}
      </el-button>
    </el-form>
  </div>
</template>

<style scoped lang="scss">
.auth-panel {
  width: 100%;
}

.auth-tabs {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 4px;
  padding: 4px;
  margin-bottom: 1.1rem;
  border: 1px solid rgba(99, 184, 255, 0.2);
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.04);
}

.auth-tab {
  height: 40px;
  border: 0;
  border-radius: 10px;
  background: transparent;
  color: #aeb7c2;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;

  &.is-active {
    background: linear-gradient(135deg, rgba(99, 184, 255, 0.35), rgba(22, 119, 232, 0.45));
    color: #fff;
    box-shadow: 0 0 14px rgba(99, 184, 255, 0.35);
  }

  &:disabled {
    opacity: 0.45;
    cursor: not-allowed;
  }
}

.auth-form {
  :deep(.el-form-item) {
    margin-bottom: 18px;
  }

  :deep(.el-form-item__label) {
    color: #cbd5e1;
    font-size: 12px;
    font-weight: 500;
    margin-bottom: 6px !important;
  }

  :deep(.el-input__wrapper),
  :deep(.el-textarea__inner) {
    min-height: 46px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.06);
    box-shadow: 0 0 0 1px rgba(99, 184, 255, 0.35) inset;
  }

  :deep(.el-input__inner),
  :deep(.el-textarea__inner) {
    color: #fff;
  }

  :deep(.el-input__inner::placeholder),
  :deep(.el-textarea__inner::placeholder) {
    color: rgba(174, 183, 194, 0.75);
  }

  :deep(.el-input__wrapper.is-focus),
  :deep(.el-textarea__inner:focus) {
    box-shadow:
      0 0 0 1px #63b8ff inset,
      0 0 0 2px rgba(99, 184, 255, 0.15);
  }

  :deep(.el-input__suffix),
  :deep(.el-input__prefix) {
    color: #aeb7c2;
  }

  :deep(.el-button:not(.auth-submit)) {
    border-color: rgba(99, 184, 255, 0.35);
    background: rgba(255, 255, 255, 0.06);
    color: #e2e8f0;
  }
}

.auth-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0 12px;
}

.domain-row {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;

  &__input {
    flex: 1;
  }

  &__host {
    flex-shrink: 0;
    color: #aeb7c2;
    font-size: 13px;
  }
}

.tenant-hint {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 6px;
  font-size: 12px;
  color: #aeb7c2;
}

.auth-submit {
  width: 100%;
  height: 48px !important;
  margin-top: 4px;
  border: none !important;
  border-radius: 12px !important;
  background: linear-gradient(135deg, #63b8ff, #1677e8) !important;
  color: #fff !important;
  font-size: 15px;
  font-weight: 600;
  letter-spacing: 0.02em;
  box-shadow: 0 0 18px rgba(99, 184, 255, 0.6);
  transition: 0.22s ease;

  &:hover {
    transform: translateY(-1px);
    box-shadow: 0 0 28px rgba(99, 184, 255, 0.85);
  }
}

@media (max-width: 640px) {
  .auth-grid {
    grid-template-columns: 1fr;
  }
}
</style>
