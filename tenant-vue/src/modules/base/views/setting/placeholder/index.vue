<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'

defineOptions({ name: 'setting:placeholder' })

const route = useRoute()

const pageTitle = computed(() => {
  const meta = route.meta?.title
  return typeof meta === 'string' && meta !== '' ? meta : '设置'
})
</script>

<template>
  <div class="mine-layout p-3">
    <div class="mine-card setting-placeholder">
      <h2 class="setting-placeholder__title">
        {{ pageTitle }}
      </h2>
      <el-empty description="功能开发中，敬请期待" />
    </div>
  </div>
</template>

<style scoped lang="scss">
.setting-placeholder {
  padding: 24px;
  min-height: 360px;

  &__title {
    margin: 0 0 24px;
    font-size: 18px;
    font-weight: 500;
    color: var(--el-text-color-primary);
  }
}
</style>
