<script setup lang="ts">
import { ElMessageBox } from 'element-plus'
import {
  getUserLoginSetting,
  saveUserLoginSetting,
  type UserLoginSettingVo,
} from '~/base/api/setting'
import SettingRow from '../components/SettingRow.vue'
import SettingSection from '../components/SettingSection.vue'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import type { TransType } from '@/hooks/auto-imports/useTrans.ts'

defineOptions({ name: 'setting:user-login' })

const t = (useTrans() as TransType).globalTrans
const msg = useMessage()
const loading = ref(false)

const form = reactive<UserLoginSettingVo>({
  register_enabled: false,
  review_new_user: false,
  user_agreement: '',
  captcha_register: false,
  captcha_login: false,
  password_strength: 'medium',
  default_user_group: 0,
  login_time_limit: 0,
})

const strengthLabels = computed<Record<string, string>>(() => ({
  weak: t('settingUi.pwdWeak'),
  medium: t('settingUi.pwdMedium'),
  strong: t('settingUi.pwdStrong'),
}))

async function loadSetting() {
  loading.value = true
  try {
    const res: any = await getUserLoginSetting()
    if (res.code === ResultCode.SUCCESS && res.data) {
      Object.assign(form, {
        register_enabled: !!res.data.register_enabled,
        review_new_user: !!res.data.review_new_user,
        user_agreement: res.data.user_agreement || '',
        captcha_register: !!res.data.captcha_register,
        captcha_login: !!res.data.captcha_login,
        password_strength: res.data.password_strength || 'medium',
        default_user_group: Number(res.data.default_user_group) || 0,
        login_time_limit: Number(res.data.login_time_limit) || 0,
      })
    }
  }
  finally {
    loading.value = false
  }
}

async function patchSetting(partial: Partial<UserLoginSettingVo>) {
  const res: any = await saveUserLoginSetting(partial)
  if (res.code === ResultCode.SUCCESS && res.data) {
    Object.assign(form, {
      register_enabled: !!res.data.register_enabled,
      review_new_user: !!res.data.review_new_user,
      user_agreement: res.data.user_agreement || '',
      captcha_register: !!res.data.captcha_register,
      captcha_login: !!res.data.captcha_login,
      password_strength: res.data.password_strength || 'medium',
      default_user_group: Number(res.data.default_user_group) || 0,
      login_time_limit: Number(res.data.login_time_limit) || 0,
    })
    msg.success(t('settingUi.saveSuccess'))
  }
}

async function onSwitch(key: keyof UserLoginSettingVo, value: boolean | string | number) {
  await patchSetting({ [key]: !!value } as Partial<UserLoginSettingVo>)
}

async function editAgreement() {
  try {
    const { value } = await ElMessageBox.prompt(t('settingUi.agreementPrompt'), t('settingUi.editAgreement'), {
      inputValue: form.user_agreement,
      inputType: 'textarea',
      confirmButtonText: t('crud.ok'),
      cancelButtonText: t('crud.cancel'),
    })
    await patchSetting({ user_agreement: value ?? '' })
  }
  catch {
    // cancelled
  }
}

async function editPasswordStrength() {
  try {
    const { value } = await ElMessageBox.prompt(t('settingUi.passwordPrompt'), t('settingUi.editPasswordStrength'), {
      inputValue: form.password_strength,
      inputPattern: /^(weak|medium|strong)$/,
      inputErrorMessage: t('settingUi.passwordInvalid'),
      confirmButtonText: t('crud.ok'),
      cancelButtonText: t('crud.cancel'),
    })
    await patchSetting({ password_strength: value })
  }
  catch {
    // cancelled
  }
}

async function editDefaultGroup() {
  try {
    const { value } = await ElMessageBox.prompt(t('settingUi.defaultRolePrompt'), t('settingUi.editDefaultRole'), {
      inputValue: String(form.default_user_group ?? 0),
      inputPattern: /^\d+$/,
      inputErrorMessage: t('settingUi.nonnegativeInt'),
      confirmButtonText: t('crud.ok'),
      cancelButtonText: t('crud.cancel'),
    })
    await patchSetting({ default_user_group: Number(value) })
  }
  catch {
    // cancelled
  }
}

async function editLoginTimeLimit() {
  try {
    const { value } = await ElMessageBox.prompt(t('settingUi.loginDurationPrompt'), t('settingUi.editLoginDuration'), {
      inputValue: String(form.login_time_limit ?? 0),
      inputPattern: /^\d+$/,
      inputErrorMessage: t('settingUi.nonnegativeInt'),
      confirmButtonText: t('crud.ok'),
      cancelButtonText: t('crud.cancel'),
    })
    await patchSetting({ login_time_limit: Number(value) })
  }
  catch {
    // cancelled
  }
}

onMounted(loadSetting)
</script>

<template>
  <div v-loading="loading" class="mine-layout p-3">
    <div class="mine-card setting-page">
      <SettingSection :title="t('settingUi.sectionMemberLogin')">
        <el-alert
          class="mb-4"
          type="info"
          :closable="false"
          show-icon
          :title="t('settingUi.memberLoginAlert')"
        />
        <SettingRow
          :label="t('settingUi.userRegister')"
          type="switch"
          :description="t('settingUi.userRegisterDesc')"
          :model-value="form.register_enabled"
          @update:model-value="v => onSwitch('register_enabled', v)"
        />
        <SettingRow
          :label="t('settingUi.auditNewUser')"
          type="switch"
          :description="t('settingUi.auditNewUserDesc')"
          :model-value="form.review_new_user"
          @update:model-value="v => onSwitch('review_new_user', v)"
        />
        <SettingRow
          :label="t('settingUi.userAgreement')"
          type="link"
          :description="form.user_agreement || t('settingUi.notSet')"
          @link-click="editAgreement"
        />
        <SettingRow
          :label="t('settingUi.registerCaptcha')"
          type="switch"
          :model-value="form.captcha_register"
          @update:model-value="v => onSwitch('captcha_register', v)"
        />
        <SettingRow
          :label="t('settingUi.loginCaptcha')"
          type="switch"
          :model-value="form.captcha_login"
          @update:model-value="v => onSwitch('captcha_login', v)"
        />
        <SettingRow
          :label="t('settingUi.passwordStrength')"
          type="link"
          :description="strengthLabels[form.password_strength] || form.password_strength"
          @link-click="editPasswordStrength"
        />
        <SettingRow
          :label="t('settingUi.defaultRole')"
          type="link"
          :description="form.default_user_group ? t('settingUi.roleId', { id: form.default_user_group }) : t('settingUi.roleUnspecified')"
          @link-click="editDefaultGroup"
        />
        <SettingRow
          :label="t('settingUi.maxLoginDuration')"
          type="link"
          :description="form.login_time_limit ? t('settingUi.minutesValue', { n: form.login_time_limit }) : t('settingUi.unlimited')"
          @link-click="editLoginTimeLimit"
        />
      </SettingSection>
    </div>
  </div>
</template>

<style scoped lang="scss">
.setting-page {
  padding: 8px 16px 24px;
  min-height: 360px;
}
</style>
