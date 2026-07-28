<script setup lang="ts">
import WelcomeInstalledApps from '~/base/views/welcome/components/WelcomeInstalledApps.vue'

defineOptions({ name: 'apps:mine' })
</script>

<template>
  <div class="mine-layout">
    <WelcomeInstalledApps />
  </div>
</template>
