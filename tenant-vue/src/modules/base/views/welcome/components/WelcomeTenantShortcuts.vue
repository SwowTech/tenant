<script setup lang="ts">
import {
  getFounderTenants,
  type FounderTenantVo,
} from '~/base/api/founder'
import { setTenantContext } from '@/utils/tenantContext.ts'
import useUserStore from '@/store/modules/useUserStore.ts'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import isFounder from '@/utils/isFounder.ts'

defineOptions({ name: 'WelcomeTenantShortcuts' })

const msg = useMessage()
const userStore = useUserStore()
const router = useRouter()

const loading = ref(false)
const list = ref<FounderTenantVo[]>([])

const allowed = computed(() => isFounder())

async function load() {
  if (!allowed.value) {
    return
  }
  loading.value = true
  try {
    const res: any = await getFounderTenants({
      status: 1,
      page: 1,
      page_size: 24,
    })
    if (res.code === ResultCode.SUCCESS) {
      list.value = res.data?.list || []
    }
  }
  finally {
    loading.value = false
  }
}

async function enterTenant(row: FounderTenantVo) {
  if (row.status !== 1 || !row.id) {
    msg.warning('请先开通或启用租户后再进入')
    return
  }
  setTenantContext(row.id, row.name)
  await userStore.logout('/')
}

function goManage() {
  router.push({ name: 'founder:tenants' }).catch(() => {
    msg.warning('未找到租户管理菜单，请确认已配置创始人菜单')
  })
}

onMounted(load)
</script>

<template>
  <div v-if="allowed" v-loading="loading" class="mine-card welcome-panel">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
      <h4 class="m-0 text-base font-medium">
        租户快捷入口
      </h4>
      <el-button link type="primary" @click="goManage">
        租户管理
      </el-button>
    </div>
    <div v-if="list.length === 0" class="text-sm text-gray-5">
      暂无已激活租户，请先在租户管理中创建并开通
    </div>
    <div v-else class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-4">
      <div
        v-for="item in list"
        :key="item.id"
        class="tenant-chip flex flex-col gap-1 rounded p-3"
      >
        <div class="truncate font-medium" :title="item.name">
          {{ item.name }}
        </div>
        <div class="truncate text-xs text-gray-5" :title="item.domain">
          {{ item.code }} · {{ item.domain }}
        </div>
        <el-button
          class="mt-1 self-start"
          type="primary"
          link
          @click="enterTenant(item)"
        >
          进入管理
        </el-button>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
.tenant-chip {
  transition: background-color .15s;
  @apply bg-[rgb(var(--ui-primary)/6%)] hover-bg-[rgb(var(--ui-primary)/12%)];
}
</style>
