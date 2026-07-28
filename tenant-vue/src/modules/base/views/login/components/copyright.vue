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
  <div class="tenant-login-footer">
    <div class="tenant-login-footer__row">
      <a
        v-for="item in userStore.getLocales()"
        :key="item.value"
        class="tenant-login-footer__link"
        :class="{ 'is-active': locale === item.value }"
        href="javascript:;"
        @click.prevent="switchLanguage(item.value)"
      >
        {{ item.label }}
      </a>
    </div>
    <div class="tenant-login-footer__meta">
      <span>{{ `${new Date().getFullYear()} ${title}` }}</span>
      <a
        v-if="setting.putOnRecord"
        href="https://beian.miit.gov.cn/"
        target="_blank"
        class="tenant-login-footer__link"
      >
        {{ setting.putOnRecord }}
      </a>
    </div>
  </div>
</template>

<style scoped lang="scss">
.tenant-login-footer {
  margin-top: 20px;
  font-size: 12px;
  color: #aeb7c2;

  &__row,
  &__meta {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
  }

  &__meta {
    margin-top: 10px;
    opacity: 0.85;
  }

  &__link {
    color: #63b8ff;
    text-decoration: none;
    cursor: pointer;

    &:hover {
      color: #9fd0ff;
    }

    &.is-active {
      color: #fff;
      font-weight: 600;
    }
  }
}
</style>
