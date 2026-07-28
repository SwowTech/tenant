<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import type { SystemSettings } from '#/global'

const { locale } = useI18n()
const userStore = useUserStore()
const settingStore = useSettingStore()

function switchLanguage(language: string): void {
  if (!language || language === locale.value) {
    return
  }
  userStore.setLanguage(language)
  const appSettings = settingStore.getSettings('app')
  if (appSettings) {
    appSettings.useLocale = language
  }
  locale.value = language
}

const title = import.meta.env.VITE_APP_TITLE
const setting: SystemSettings.copyright = settingStore.getSettings('copyright')
</script>

<template>
  <div class="admin-login-copyright">
    <div class="admin-login-copyright__langs">
      <a
        v-for="item in userStore.getLocales()"
        :key="item.value"
        class="admin-login-copyright__link"
        :class="{ 'is-active': locale === item.value }"
        href="javascript:;"
        @click.prevent="switchLanguage(item.value)"
      >
        {{ item.label }}
      </a>
    </div>
    <div class="admin-login-copyright__meta">
      <ma-svg-icon name="lucide:copyright" />
      <span>{{ `${new Date().getFullYear()} ${title}` }}</span>
      <a
        v-if="setting.putOnRecord"
        href="https://beian.miit.gov.cn/"
        target="_blank"
        class="admin-login-copyright__link"
      >
        {{ setting.putOnRecord }}
      </a>
    </div>
  </div>
</template>

<style scoped lang="scss">
.admin-login-copyright {
  margin-top: 28px;
  text-align: center;
  color: #9aa6b2;
  font-size: 12px;

  &__langs,
  &__meta {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
  }

  &__langs {
    margin-bottom: 6px;
  }

  &__link {
    color: #8a94a6;
    text-decoration: none;
    cursor: pointer;

    &:hover {
      color: #1677e8;
    }

    &.is-active {
      color: #1677e8;
      font-weight: 600;
    }
  }
}
</style>
