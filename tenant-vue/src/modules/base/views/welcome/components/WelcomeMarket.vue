<script setup lang="ts">
import { getWelcomeMarketApps, getWelcomeMarketStats } from '~/base/api/welcome'
import { getCloudStoreToken } from '~/base/api/cloud'
import { ResultCode } from '@/utils/ResultCode.ts'
import { ElMessage } from 'element-plus'

defineOptions({ name: 'WelcomeMarket' })

const props = defineProps<{
  stats?: {
    total_apps?: number
    available_apps?: number
    total_versions?: number
  } | null
  saasAdminUrl?: string
}>()

interface MarketAppItem {
  id: number
  identifier?: string
  title?: string
  edition?: string
  family?: string
  cover_url?: string
  category?: string
  price_type?: string
}

interface MarketFamilyGroup {
  family: string
  editions: MarketAppItem[]
}

const loading = ref(false)
const keyword = ref('')
const sort = ref('default')
const groups = ref<MarketFamilyGroup[]>([])
const total = ref(0)
const marketStats = ref({ ...props.stats })

const sortTabs = [
  { name: 'default', label: '默认' },
  { name: 'newest', label: '最新' },
  { name: 'hot', label: '热门' },
]

const adminBase = computed(() => {
  const fromProp = (props.saasAdminUrl || '').replace(/\/$/, '')
  if (fromProp) {
    return fromProp
  }
  return (import.meta.env.VITE_SAAS_ADMIN_URL || 'http://127.0.0.1:5174').replace(/\/$/, '')
})

/** saas-vue base 为 /platform/，商城在买家路由 /store */
function fallbackStoreUrl() {
  return `${adminBase.value}/platform/app-store`
}

watch(
  () => props.stats,
  (v) => {
    if (v) {
      marketStats.value = { ...v }
    }
  },
  { deep: true },
)

function formatEdition(edition?: string) {
  const value = String(edition || '').trim()
  return value ? value.toUpperCase() : ''
}

function groupTitle(group: MarketFamilyGroup) {
  const first = group.editions[0]
  if (first?.title) {
    return first.title
  }
  return group.family
}

function normalizeGroups(data: { groups?: MarketFamilyGroup[], list?: MarketAppItem[] } | null | undefined): MarketFamilyGroup[] {
  const rawGroups = data?.groups
  if (Array.isArray(rawGroups) && rawGroups.length > 0) {
    return rawGroups.map(g => ({
      family: g.family,
      editions: g.editions ?? [],
    }))
  }

  const list = data?.list ?? []
  const map = new Map<string, MarketFamilyGroup>()
  for (const app of list) {
    const family = app.family || app.identifier || String(app.id)
    if (!map.has(family)) {
      map.set(family, { family, editions: [] })
    }
    map.get(family)!.editions.push(app)
  }
  return Array.from(map.values())
}

async function loadApps() {
  loading.value = true
  try {
    const res: any = await getWelcomeMarketApps({
      keyword: keyword.value.trim() || undefined,
      sort: sort.value,
      page: 1,
      page_size: 12,
    })
    if (res.code === ResultCode.SUCCESS && res.data) {
      groups.value = normalizeGroups(res.data)
      total.value = res.data.total ?? 0
    }
  }
  finally {
    loading.value = false
  }
}

async function refreshStats() {
  try {
    const res: any = await getWelcomeMarketStats()
    if (res.code === ResultCode.SUCCESS && res.data) {
      marketStats.value = res.data
    }
  }
  catch {
    // ignore
  }
}

function onSearch() {
  loadApps()
}

function onSortChange() {
  loadApps()
}

async function openManage() {
  try {
    const res: any = await getCloudStoreToken()
    if (res.code === ResultCode.SUCCESS && res.data?.store_url) {
      window.open(res.data.store_url, '_blank')
      return
    }
    if (res.data?.message) {
      ElMessage.warning(res.data.message)
    }
  }
  catch {
    // fallback
  }
  window.open(fallbackStoreUrl(), '_blank')
}

async function openApp(app: MarketAppItem) {
  const id = app?.id
  if (!id) {
    await openManage()
    return
  }
  try {
    const res: any = await getCloudStoreToken()
    if (res.code === ResultCode.SUCCESS && res.data?.store_token) {
      window.open(
        `${adminBase.value}/platform/app-store/${id}?store_token=${encodeURIComponent(res.data.store_token)}`,
        '_blank',
      )
      return
    }
    if (res.data?.message) {
      ElMessage.warning(res.data.message)
    }
  }
  catch {
    // fallback
  }
  window.open(`${fallbackStoreUrl()}/${id}`, '_blank')
}

onMounted(() => {
  loadApps()
  if (!props.stats) {
    refreshStats()
  }
})
</script>

<template>
  <div v-loading="loading" class="mine-card welcome-panel">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
      <div class="flex items-center gap-3 flex-wrap">
        <h4 class="text-base font-medium m-0">
          云应用商城
        </h4>
        <el-tabs v-model="sort" class="welcome-market-tabs" @tab-change="onSortChange">
          <el-tab-pane
            v-for="tab in sortTabs"
            :key="tab.name"
            :label="tab.label"
            :name="tab.name"
          />
        </el-tabs>
      </div>
      <div class="flex items-center gap-2 flex-wrap">
        <el-input
          v-model="keyword"
          clearable
          placeholder="搜索应用"
          class="w-220px"
          @keyup.enter="onSearch"
        >
          <template #append>
            <el-button @click="onSearch">
              搜索
            </el-button>
          </template>
        </el-input>
        <el-button type="primary" @click="openManage">
          进入商城
        </el-button>
      </div>
    </div>

    <div class="text-sm text-gray-5 dark-text-gray-3 mb-4">
      商品版本合计
      <span class="text-[#4285F4] font-medium mx-0.5">{{ marketStats?.total_versions ?? 0 }}</span>
      ，可用应用
      <span class="text-[#4285F4] font-medium mx-0.5">{{ marketStats?.available_apps ?? 0 }}</span>
      （共 {{ total }} 条）
    </div>

    <div v-if="groups.length === 0" class="py-10 text-center text-gray-4">
      <p class="mb-3">
        暂无上架应用预览，可直接进入商城浏览与购买
      </p>
      <el-button type="primary" @click="openManage">
        进入商城
      </el-button>
    </div>
    <div v-else class="flex flex-col gap-5">
      <section v-for="group in groups" :key="group.family" class="market-family">
        <div class="market-family__header">
          <div class="market-family__title">
            {{ groupTitle(group) }}
          </div>
          <div v-if="group.editions.length > 1" class="market-family__meta">
            应用家族：{{ group.family }}
          </div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
          <div
            v-for="app in group.editions"
            :key="app.id"
            class="market-item flex flex-col items-center gap-2 p-3 b b-solid b-gray-1 dark-b-dark-3 rounded cursor-pointer"
            @click="openApp(app)"
          >
            <el-avatar
              :size="56"
              shape="square"
              :src="app.cover_url || undefined"
              class="shrink-0"
            >
              {{ (app.title || app.identifier || '?').slice(0, 1) }}
            </el-avatar>
            <div class="text-sm text-center w-full truncate" :title="app.title">
              {{ app.title || app.identifier }}
              <span v-if="formatEdition(app.edition)" class="market-item__edition">{{ formatEdition(app.edition) }}</span>
            </div>
            <div class="text-xs text-gray-4 truncate w-full text-center">
              {{ app.category || app.price_type || '' }}
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<style scoped lang="scss">
.welcome-market-tabs {
  :deep(.el-tabs__header) {
    margin: 0;
  }
  :deep(.el-tabs__nav-wrap::after) {
    display: none;
  }
}
.market-family__header {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 8px;
  margin-bottom: 10px;
}
.market-family__title {
  font-size: 14px;
  font-weight: 600;
}
.market-family__meta {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.market-item {
  transition: box-shadow 0.2s;
  &:hover {
    box-shadow: 0 2px 8px rgb(0 0 0 / 8%);
  }
}
.market-item__edition {
  display: inline-block;
  margin-left: 4px;
  padding: 0 5px;
  font-size: 10px;
  font-weight: 600;
  line-height: 16px;
  color: var(--el-color-primary);
  background: var(--el-color-primary-light-9);
  border-radius: 4px;
  vertical-align: middle;
}
</style>
