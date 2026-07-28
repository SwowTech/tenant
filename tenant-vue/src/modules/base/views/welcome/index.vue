<script setup lang="ts">
import WorkbenchFast from '~/base/views/dashboard/components/workbench/workbench-fast.vue'
import WelcomeInstalledApps from './components/WelcomeInstalledApps.vue'
import WelcomeGuide from './components/WelcomeGuide.vue'
import WelcomeTenantShortcuts from './components/WelcomeTenantShortcuts.vue'
import isFounder from '@/utils/isFounder.ts'

defineOptions({ name: 'welcome' })

/** 快捷入口不展示：云服务 / 系统更新 / 租户 / 应用管理（下方有专区） */
const shortcutExclude = [
  '云服务', '系统更新', '系统升级', '租户', 'founder', 'cloud',
  '应用商店', '应用市场', 'appstore', 'app-store', '我的应用', 'apps:mine', '应用管理',
]

const founder = computed(() => isFounder())
</script>

<template>
  <div class="welcome-page mine-layout flex flex-col gap-3">
    <WorkbenchFast :exclude-keywords="shortcutExclude" />
    <WelcomeInstalledApps />
    <WelcomeGuide />
    <WelcomeTenantShortcuts v-if="founder" />
  </div>
</template>
