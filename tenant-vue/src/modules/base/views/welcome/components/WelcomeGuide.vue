<script setup lang="ts">
import type { TransType } from '@/hooks/auto-imports/useTrans.ts'

defineOptions({ name: 'WelcomeGuide' })

const t = (useTrans() as TransType).globalTrans
const router = useRouter()

const cards = computed(() => [
  {
    key: 'apps',
    icon: 'ri:apps-2-line',
    title: t('welcomePage.guideAppsTitle'),
    desc: t('welcomePage.guideAppsDesc'),
    action: t('welcomePage.guideAppsAction'),
    path: '/setting/cloud/store',
    tone: 'teal',
  },
  {
    key: 'site',
    icon: 'ri:global-line',
    title: t('welcomePage.guideSiteTitle'),
    desc: t('welcomePage.guideSiteDesc'),
    action: t('welcomePage.guideSiteAction'),
    path: '/setting/site',
    tone: 'blue',
  },
  {
    key: 'check',
    icon: 'mdi:shield-check-outline',
    title: t('welcomePage.guideCheckTitle'),
    desc: t('welcomePage.guideCheckDesc'),
    action: t('welcomePage.guideCheckAction'),
    path: '/system/check',
    tone: 'amber',
  },
])

function go(path: string) {
  router.push(path).catch(() => {})
}
</script>

<template>
  <div class="mine-card welcome-guide">
    <div class="text-base font-medium mb-3">
      {{ t('welcomePage.guideTitle') }}
    </div>
    <p class="text-sm text-gray-5 dark-text-gray-3 m-0 mb-4 leading-6">
      {{ t('welcomePage.guideSubtitle') }}
    </p>
    <div class="guide-grid">
      <button
        v-for="card in cards"
        :key="card.key"
        type="button"
        class="guide-card"
        :class="`tone-${card.tone}`"
        @click="go(card.path)"
      >
        <div class="guide-card__icon">
          <ma-svg-icon :name="card.icon" :size="28" />
        </div>
        <div class="guide-card__body">
          <div class="guide-card__title">
            {{ card.title }}
          </div>
          <div class="guide-card__desc">
            {{ card.desc }}
          </div>
          <div class="guide-card__action">
            {{ card.action }} →
          </div>
        </div>
      </button>
    </div>
  </div>
</template>

<style scoped lang="scss">
.guide-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
}

.guide-card {
  display: flex;
  gap: 14px;
  align-items: flex-start;
  text-align: left;
  padding: 16px;
  border-radius: 12px;
  border: 1px solid var(--el-border-color-lighter);
  background: var(--el-bg-color);
  cursor: pointer;
  transition: border-color 0.15s, box-shadow 0.15s, transform 0.15s;

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgb(15 23 42 / 6%);
  }
}

.guide-card__icon {
  flex-shrink: 0;
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.tone-teal .guide-card__icon {
  background: rgb(15 110 117 / 12%);
  color: #0f6e75;
}
.tone-blue .guide-card__icon {
  background: #e8f0fe;
  color: #4285f4;
}
.tone-amber .guide-card__icon {
  background: #fff3e0;
  color: #fb8c00;
}

.guide-card__title {
  font-weight: 600;
  margin-bottom: 6px;
}

.guide-card__desc {
  font-size: 13px;
  line-height: 1.55;
  color: var(--el-text-color-secondary);
  margin-bottom: 10px;
  min-height: 40px;
}

.guide-card__action {
  font-size: 13px;
  font-weight: 600;
  color: rgb(var(--ui-primary));
}

@media (max-width: 900px) {
  .guide-grid {
    grid-template-columns: 1fr;
  }
}
</style>
