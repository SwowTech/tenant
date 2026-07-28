<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useMessage } from '@/hooks/useMessage.ts'

defineOptions({ name: 'WelcomeCheckBar' })

const props = defineProps<{
  summary: {
    check_num?: number
    check_wrong_num?: number
    report_text?: string
  }
}>()

const router = useRouter()
const msg = useMessage()

const hasError = computed(() => (props.summary?.check_wrong_num ?? 0) > 0)

async function copyReport() {
  const text = props.summary?.report_text || ''
  if (!text) {
    msg.warning('暂无检测报告')
    return
  }
  try {
    await navigator.clipboard.writeText(text)
    msg.success('已复制检测结果')
  }
  catch {
    msg.error('复制失败，请手动选择')
  }
}

function goDetail() {
  router.push('/system/check')
}
</script>

<template>
  <div class="mine-card welcome-panel">
    <h4 class="text-base font-medium mb-4">
      环境检测
    </h4>
    <div class="flex flex-wrap items-center gap-4">
      <div class="welcome-icon check-icon flex items-center justify-center shrink-0">
        <ma-svg-icon name="mdi:shield-check-outline" :size="36" />
      </div>
      <div class="flex-1 min-w-0">
        <div class="text-base font-medium mb-1">
          <template v-if="hasError">
            系统环境检测发现异常
          </template>
          <template v-else>
            环境检测
          </template>
        </div>
        <div class="text-sm text-gray-5 dark-text-gray-3 leading-6">
          全部检测
          <span class="text-[#4285F4] font-medium mx-0.5">{{ summary?.check_num ?? 0 }}</span>
          项，错误检测项
          <span :class="hasError ? 'text-red-5 font-medium mx-0.5' : 'font-medium mx-0.5'">
            {{ summary?.check_wrong_num ?? 0 }}
          </span>
          项。在使用过程中如出现未知错误，可自行检测修复。
        </div>
      </div>
      <div class="flex gap-2 shrink-0">
        <el-button @click="copyReport">
          复制结果
        </el-button>
        <el-button type="primary" @click="goDetail">
          详情
        </el-button>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
.welcome-icon {
  width: 72px;
  height: 72px;
  border-radius: 12px;
}
.check-icon {
  background: #fff3e0;
  color: #fb8c00;
}
</style>
