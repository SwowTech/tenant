<!--
 - Admin console login (/admin)
-->
<script setup lang="ts">
import './style.scss'
import { ElMessage } from 'element-plus'
import { clearFounderSession, clearTenantContext, getTenantId, getTenantName } from '@/utils/tenantContext.ts'
import LogoImg from '@/assets/images/logo.png'
import LoginLeftImg from '@/assets/images/login/login-left.png'
import LoginForm from './components/login-form.vue'
import CopyRight from './components/copyright.vue'

const appTitle = import.meta.env.VITE_APP_TITLE
const tenantId = ref(getTenantId())
const tenantName = ref(getTenantName())

function backToMainSite() {
  clearTenantContext()
  clearFounderSession()
  tenantId.value = null
  tenantName.value = null
  ElMessage.success('已清除租户上下文，请使用主站账号登录')
}
</script>

<template>
  <div
    class="admin-login-page"
    :style="{ '--admin-login-bg': `url(${LoginLeftImg})` }"
  >
    <div class="admin-login-brand">
      <img :src="LogoImg" :alt="appTitle" class="admin-login-brand__logo">
      <span class="admin-login-brand__name">{{ appTitle }}</span>
    </div>

    <div class="admin-login-card">
      <div class="admin-login-card__head">
        <h1>管理员登录</h1>
        <p>访问系统管理控制台</p>
      </div>

      <el-alert
        v-if="tenantId"
        class="admin-login-tenant-alert"
        type="info"
        show-icon
        :closable="false"
        :title="`当前将登录业务租户 #${tenantId}${tenantName ? ' / ' + tenantName : ''}`"
      />
      <el-button
        v-if="tenantId"
        class="admin-login-back"
        link
        type="primary"
        @click="backToMainSite"
      >
        返回主站
      </el-button>

      <LoginForm />
      <CopyRight />
    </div>
  </div>
</template>
