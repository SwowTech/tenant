<script setup lang="ts">
import type { TransType } from '@/hooks/auto-imports/useTrans.ts'

defineOptions({ name: 'SettingRow' })

withDefaults(defineProps<{
  label: string
  description?: string
  type?: 'switch' | 'link' | 'text'
  modelValue?: boolean | string | number
}>(), {
  type: 'text',
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean | string | number]
  'link-click': []
}>()

const t = (useTrans() as TransType).globalTrans

function onSwitchChange(value: boolean | string | number) {
  emit('update:modelValue', value)
}

function onLinkClick() {
  emit('link-click')
}
</script>

<template>
  <div class="setting-row">
    <div class="setting-row__label">
      <div class="setting-row__title">
        {{ label }}
      </div>
      <div v-if="description" class="setting-row__desc">
        {{ description }}
      </div>
      <slot name="description" />
    </div>
    <div class="setting-row__control">
      <el-switch
        v-if="type === 'switch'"
        :model-value="!!modelValue"
        @update:model-value="onSwitchChange"
      />
      <a
        v-else-if="type === 'link'"
        class="setting-row__link"
        href="javascript:void(0)"
        @click.prevent="onLinkClick"
      >
        {{ t('crud.edit') }}
      </a>
      <span v-else-if="type === 'text'" class="setting-row__text">
        <slot>{{ modelValue }}</slot>
      </span>
      <slot v-else name="control" />
    </div>
  </div>
</template>

<style scoped lang="scss">
.setting-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 16px;
  border-bottom: 1px solid var(--el-border-color-lighter);

  &:last-child {
    border-bottom: none;
  }

  &__label {
    flex: 1;
    min-width: 0;
  }

  &__title {
    font-size: 14px;
    color: var(--el-text-color-primary);
    line-height: 22px;
  }

  &__desc {
    margin-top: 4px;
    font-size: 12px;
    color: var(--el-text-color-secondary);
    line-height: 18px;
  }

  &__control {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    min-height: 22px;
    padding-top: 1px;
  }

  &__link {
    font-size: 14px;
    color: #4285f4;
    text-decoration: none;
    cursor: pointer;

    &:hover {
      color: #73d13d;
    }
  }

  &__text {
    font-size: 14px;
    color: var(--el-text-color-regular);
  }
}
</style>
