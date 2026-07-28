<script setup lang="ts">
import { useRouter } from 'vue-router'

defineOptions({ name: 'WelcomeBindCard' })

const router = useRouter()

const props = defineProps<{
  bind: {
    bound?: boolean
    site?: {
      key?: string
      username?: string
      phone?: string
      email?: string
      url?: string
      bound_at?: string
    } | null
    tenant?: unknown
    message?: string
    bind_url?: string
  }
}>()

function goRegister() {
  router.push('/setting/cloud/register')
}
</script>

<template>
  <div class="mine-card welcome-panel h-full">
    <h4 class="text-base font-medium mb-4">
      云服务
    </h4>
    <div class="flex items-start gap-4">
      <div class="welcome-icon bind-icon flex items-center justify-center shrink-0">
        <ma-svg-icon name="mdi:cloud-outline" :size="36" />
      </div>
      <div class="flex flex-col gap-2 min-w-0 flex-1">
        <template v-if="!bind?.bound">
          <div class="text-2xl font-semibold leading-tight text-orange-5">
            从未
          </div>
          <div class="text-sm text-gray-5 dark-text-gray-3 leading-6">
            {{ bind?.message || '您还未注册云站点（可选）。注册后可使用云升级、应用订阅等能力' }}
          </div>
          <div>
            <el-button type="primary" @click="goRegister">
              立即绑定
            </el-button>
          </div>
        </template>
        <template v-else>
          <div class="text-lg font-semibold text-[#34A853] leading-tight">
            已绑定
          </div>
          <div class="text-sm leading-6">
            <div>
              <span class="text-gray-5">站点 ID：</span>{{ bind.site?.key || '-' }}
            </div>
            <div v-if="bind.site?.username">
              <span class="text-gray-5">绑定账号：</span>{{ bind.site.username }}
            </div>
            <div v-if="bind.site?.phone">
              <span class="text-gray-5">手机：</span>{{ bind.site.phone }}
            </div>
            <div v-if="bind.site?.email">
              <span class="text-gray-5">邮箱：</span>{{ bind.site.email }}
            </div>
            <div class="text-gray-5 mt-1">
              {{ bind.message }}
            </div>
          </div>
          <div>
            <el-button @click="goRegister">
              查看站点
            </el-button>
          </div>
        </template>
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
.bind-icon {
  background: #e6f4ea;
  color: #34a853;
}
</style>
