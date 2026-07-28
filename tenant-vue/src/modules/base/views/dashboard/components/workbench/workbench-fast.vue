<script setup lang="ts">
import type { TransType } from '@/hooks/auto-imports/useTrans.ts'
import { resolveMenuTitle } from '@/utils/resolveMenuTitle'

const props = withDefaults(defineProps<{
  /** Match against title / path / name to hide */
  excludeKeywords?: string[]
}>(), {
  excludeKeywords: () => [],
})

const t = (useTrans() as TransType).globalTrans
const router = useRouter()

const carouselItems = Array.from({ length: 5 }, (_, index) => index + 1)

function routeLabel(v: any): string {
  return resolveMenuTitle(v, key => t(key) as string)
}

function isExcluded(v: any): boolean {
  if (!props.excludeKeywords.length) {
    return false
  }
  const hay = `${routeLabel(v)} ${v.path || ''} ${String(v.name || '')}`.toLowerCase()
  return props.excludeKeywords.some(k => hay.includes(String(k).toLowerCase()))
}

const visibleRoutes = computed(() =>
  router.getRoutes()
    .filter((v: any) => /^(?!\/$)(?!.*\/uc)(?!.*\/login)(?!.*\/:pathMatch\([^)]*\)).*$/.test(v.path) && v.components)
    .filter((v: any) => !isExcluded(v))
    .slice(0, 12),
)
</script>

<template>
  <div class="lg:flex">
    <div class="mine-card w-auto lg:w-8/12">
      <div class="text-base">
        {{ t('welcomePage.quickEntry') }}
      </div>
      <div class="grid grid-cols-3 mt-3 gap-3 lg:grid-cols-4 md:grid-cols-4 xl:grid-cols-6">
        <div v-for="item in visibleRoutes" :key="item.name" class="flex-center">
          <el-link underline="never" @click="() => router.push(item.path)">
            <div class="link">
              <ma-svg-icon :name="(item.meta?.icon ?? 'i-carbon:unknown') as string" :size="26" />
              {{ routeLabel(item) }}
            </div>
          </el-link>
        </div>
      </div>
    </div>
    <div class="mine-card w-auto !ml-3 lg:w-4/12 !lg:ml-0">
      <el-carousel height="230px" class="w-full rounded">
        <el-carousel-item v-for="item in carouselItems" :key="item">
          <img :src="`https://picsum.photos/600/240?random=${item}`" :alt="`carousel-${item}`" class="h-full w-full rounded object-cover">
        </el-carousel-item>
      </el-carousel>
    </div>
  </div>
</template>

<style scoped lang="scss">
.link {
  transition: all .15s;
  @apply min-w-20 flex flex-col items-center gap-y-2 rounded p-4
  hover-bg-[rgb(var(--ui-primary)/10%)]
  ;
}
</style>
