<script setup lang="ts">
defineOptions({ name: 'SettingSection' })

defineProps<{
  title: string
}>()
</script>

<template>
  <section class="setting-section">
    <div class="setting-section__bar">
      {{ title }}
    </div>
    <div class="setting-section__body">
      <slot />
    </div>
  </section>
</template>

<style scoped lang="scss">
.setting-section {
  background: var(--el-bg-color);
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 4px;
  overflow: hidden;

  & + & {
    margin-top: 16px;
  }

  &__bar {
    padding: 10px 16px;
    font-size: 14px;
    font-weight: 500;
    color: var(--el-text-color-primary);
    background: #f5f5f5;
    border-bottom: 1px solid var(--el-border-color-lighter);
  }

  &__body {
    padding: 0;
  }
}

html.dark {
  .setting-section__bar {
    background: var(--el-fill-color-light);
  }
}
</style>
