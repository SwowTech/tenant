<script setup lang="ts">
import { useRouter } from 'vue-router'

defineOptions({ name: 'setting:logs' })

const router = useRouter()
const activeTab = ref('wechat')

const dateRange = ref<[Date, Date] | null>(null)
const keyword = ref('')

const emptyTips: Record<string, string> = {
  wechat: '暂无微信日志',
  database: '数据库日志即将开放',
  sms: '暂无短信发送日志',
  attachment: '暂无附件操作日志',
}

function goOperationLog() {
  router.push('/log/operationLog')
}

function onSearch() {
  // 本期仅系统日志可跳转；其余 Tab 为空态筛选 UI
}
</script>

<template>
  <div class="mine-layout p-3">
    <div class="mine-card setting-page">
      <el-tabs v-model="activeTab">
        <el-tab-pane label="微信日志" name="wechat" />
        <el-tab-pane label="系统日志" name="system" />
        <el-tab-pane label="数据库日志" name="database" />
        <el-tab-pane label="短信发送日志" name="sms" />
        <el-tab-pane label="附件操作日志" name="attachment" />
      </el-tabs>

      <div v-if="activeTab !== 'wechat'" class="setting-toolbar">
        <el-date-picker
          v-model="dateRange"
          type="daterange"
          range-separator="至"
          start-placeholder="开始日期"
          end-placeholder="结束日期"
          value-format="YYYY-MM-DD"
          class="setting-toolbar__date"
        />
        <el-input
          v-if="activeTab === 'sms'"
          v-model="keyword"
          clearable
          placeholder="请输入手机号"
          class="setting-toolbar__search"
          @keyup.enter="onSearch"
        />
        <el-input
          v-else-if="activeTab === 'attachment'"
          v-model="keyword"
          clearable
          placeholder="请输入要搜索的平台名称"
          class="setting-toolbar__search"
          @keyup.enter="onSearch"
        />
        <el-input
          v-else-if="activeTab !== 'system'"
          v-model="keyword"
          clearable
          placeholder="关键字"
          class="setting-toolbar__search"
          @keyup.enter="onSearch"
        />
        <el-button v-if="activeTab !== 'system'" @click="onSearch">
          搜索
        </el-button>
      </div>

      <div v-if="activeTab === 'system'" class="system-log-panel">
        <el-alert
          type="info"
          :closable="false"
          show-icon
          title="系统日志复用现有操作日志。可点击下方按钮跳转查看。"
          class="mb-4"
        />
        <el-button type="primary" @click="goOperationLog">
          查看操作日志
        </el-button>
      </div>

      <div v-else class="setting-empty">
        <p class="setting-empty__title">
          {{ emptyTips[activeTab] || '暂无数据' }}
        </p>
        <p class="setting-empty__desc">
          功能开发中，敬请期待
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
.setting-page {
  padding: 8px 16px 24px;
  min-height: 360px;
}

.setting-toolbar {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 16px;

  &__date {
    width: 280px;
  }

  &__search {
    width: 240px;
  }
}

.system-log-panel {
  padding: 8px 0 16px;
}

.setting-empty {
  margin-top: 48px;
  text-align: center;
  line-height: 1.6;
  color: var(--el-text-color-regular);

  &__title {
    margin: 0 0 8px;
    font-size: 14px;
    color: var(--el-text-color-primary);
  }

  &__desc {
    margin: 0;
    font-size: 13px;
    color: var(--el-text-color-secondary);
  }
}
</style>
