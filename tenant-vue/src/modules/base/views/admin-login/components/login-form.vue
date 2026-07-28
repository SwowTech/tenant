<script setup lang="ts">
import Message from 'vue-m-message'
import { useI18n } from 'vue-i18n'
import useUserStore from '@/store/modules/useUserStore.ts'
import useSettingStore from '@/store/modules/useSettingStore.ts'

const { t } = useI18n()
const isProduction: boolean = import.meta.env.MODE === 'production'
const userStore = useUserStore()
const settingStore = useSettingStore()
const router = useRouter()
const isFormSubmit = ref(false)
const isValidState = ref(true)
const codeRef = ref()
const invalidFields = reactive<Record<string, boolean>>({
  username: false,
  password: false,
  code: false,
})
const form = reactive<{
  username: string
  password: string
  code: string
}>({
  username: isProduction ? '' : 'admin',
  password: isProduction ? '' : '123456',
  code: isProduction ? '' : '1234',
})

function easyValidate(event: Event) {
  const dom = event?.target as HTMLInputElement
  const name = dom.name as keyof typeof form
  if (form[name] === undefined || form[name] === '') {
    invalidFields[name] = true
    Message.error(t(`loginForm.${name}Placeholder`))
    isValidState.value = false
  }
  else {
    invalidFields[name] = false
    isValidState.value = true
  }
}

async function submit() {
  isValidState.value = true
  Object.keys(form).forEach((key) => {
    if (!isProduction && key === 'code') {
      return
    }
    if (form[key as keyof typeof form] === undefined || form[key as keyof typeof form] === '') {
      invalidFields[key] = true
      Message.error(t(`loginForm.${key}Placeholder`))
      isValidState.value = false
    }
    else {
      invalidFields[key] = false
    }
  })
  if (!isValidState.value) {
    return false
  }

  if (isProduction && !codeRef.value.checkResult(form.code)) {
    form.code = ''
    invalidFields.code = true
    return false
  }

  isFormSubmit.value = true
  userStore.login(form).then(async (userData: any) => {
    const welcomePath = settingStore.getSettings('welcomePage').path ?? null
    const redirect = router.currentRoute.value.query?.redirect ?? undefined
    if (userData) {
      await router.push({ path: (redirect as string) ?? welcomePath ?? '/' })
    }
    isFormSubmit.value = false
  }).catch(() => isFormSubmit.value = false)
}
</script>

<template>
  <form class="admin-login-form" @submit.prevent="submit">
    <div class="admin-login-form__item">
      <label class="admin-login-form__field" :class="{ 'is-invalid': invalidFields.username }">
        <ma-svg-icon name="mdi:account-outline" class="admin-login-form__icon" />
        <input
          v-model="form.username"
          class="admin-login-form__input"
          name="username"
          autocomplete="username"
          :placeholder="t('loginForm.usernamePlaceholder')"
          @blur="easyValidate"
        >
      </label>
    </div>

    <div class="admin-login-form__item">
      <label class="admin-login-form__field" :class="{ 'is-invalid': invalidFields.password }">
        <ma-svg-icon name="mdi:lock-outline" class="admin-login-form__icon" />
        <input
          v-model="form.password"
          class="admin-login-form__input"
          name="password"
          type="password"
          autocomplete="current-password"
          :placeholder="t('loginForm.passwordPlaceholder')"
          @blur="easyValidate"
        >
      </label>
    </div>

    <div v-if="isProduction" class="admin-login-form__item">
      <label class="admin-login-form__field" :class="{ 'is-invalid': invalidFields.code }">
        <ma-svg-icon name="mdi:shield-key-outline" class="admin-login-form__icon" />
        <input
          v-model="form.code"
          class="admin-login-form__input"
          name="code"
          autocomplete="off"
          :placeholder="t('loginForm.codePlaceholder')"
          @blur="easyValidate"
        >
        <div class="admin-login-form__code">
          <ma-verify-code ref="codeRef" />
        </div>
      </label>
    </div>

    <div class="admin-login-form__item">
      <button
        type="submit"
        class="admin-login-form__submit"
        :class="{ loading: isFormSubmit }"
        :disabled="isFormSubmit"
      >
        立即登录
      </button>
    </div>
  </form>
</template>
