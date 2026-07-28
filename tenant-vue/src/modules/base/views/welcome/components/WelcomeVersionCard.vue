<script setup lang="ts">
import { checkWelcomeVersion } from '~/base/api/welcome'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'

defineOptions({ name: 'WelcomeVersionCard' })

const props = defineProps<{
  version: {
    current?: string
    latest?: string | null
    upgradable?: boolean
    message?: string
  }
}>()

const msg = useMessage()
const checking = ref(false)
const info = ref({ ...props.version })

watch(
  () => props.version,
  (v) => {
    info.value = { ...v }
  },
  { deep: true },
)

async function handleCheck() {
  checking.value = true
  try {
    const res: any = await checkWelcomeVersion()
    if (res.code === ResultCode.SUCCESS && res.data) {
      info.value = res.data
      msg.success(res.data.message || '检查完成')
    }
  }
  finally {
    checking.value = false
  }
}
</script>

<template>
  <div class="mine-card welcome-panel h-full">
    <h4 class="text-base font-medium mb-4">
      系统更新
    </h4>
    <div class="flex items-start gap-4">
      <div class="welcome-icon version-icon flex items-center justify-center shrink-0">
        <ma-svg-icon name="mdi:update" :size="36" />
      </div>
      <div class="flex flex-col gap-2 min-w-0">
        <div class="text-2xl font-semibold text-[#4285F4] leading-tight">
          v{{ info.current || '-' }}
        </div>
        <div class="text-sm text-gray-5 dark-text-gray-3 leading-6">
          {{ info.message || '当前版本信息' }}
        </div>
        <div>
          <el-button type="primary" :loading="checking" @click="handleCheck">
            检查新版本
          </el-button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
.welcome-icon {
  width: 72px;
  height: 72px;
  border-radius: 12px;
  background: #e8f0fe;
  color: #4285f4;
}
</style>
