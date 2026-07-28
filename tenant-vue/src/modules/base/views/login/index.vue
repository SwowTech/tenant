<!--
 - Tenant member login / register (/login)
-->
<script setup lang="ts">
import './style.scss'
import { useI18n } from 'vue-i18n'
import LogoImg from '@/assets/images/logo.png'
import LoginLeftImg from '@/assets/images/login/login-visual.png'
import MemberForm from './components/member-form.vue'
import CopyRight from './components/copyright.vue'

const { t } = useI18n()
const appTitle = import.meta.env.VITE_APP_TITLE
</script>

<template>
  <div class="tenant-login-page">
    <div class="tenant-login-logo">
      <img :src="LogoImg" :alt="appTitle" class="tenant-login-logo__img">
      <span>{{ appTitle }}</span>
    </div>

    <div class="tenant-login-wrapper">
      <div class="tenant-login-visual" aria-hidden="true">
        <img class="tenant-login-graphic" :src="LoginLeftImg" alt="">
      </div>

      <div class="tenant-login-panel">
        <div class="tenant-login-panel__head">
          <h2>{{ t('tenantLogin.title') }}</h2>
          <p>{{ t('tenantLogin.subtitle') }}</p>
        </div>
        <MemberForm />
        <CopyRight />
      </div>
    </div>
  </div>
</template>
