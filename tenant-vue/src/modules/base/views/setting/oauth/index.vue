<script setup lang="ts">
import {
  getOauthSetting,
  saveOauthSetting,
  type OauthSettingVo,
  type OauthWechatAccountVo,
} from '~/base/api/setting'
import SettingRow from '../components/SettingRow.vue'
import SettingSection from '../components/SettingSection.vue'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'

defineOptions({ name: 'setting:oauth' })

const msg = useMessage()
const loading = ref(false)
const accountDialogVisible = ref(false)
const hostDialogVisible = ref(false)
const pickingAccountId = ref(0)
const editingHost = ref('')

const form = reactive<OauthSettingVo>({
  account_id: 0,
  host: '',
  wechat_accounts: [],
})

const accounts = computed<OauthWechatAccountVo[]>(() => form.wechat_accounts ?? [])

const accountTitle = computed(() => {
  if (!form.account_id) {
    return '不借用任何权限'
  }
  const hit = accounts.value.find(item => item.id === form.account_id)
  if (!hit) {
    return `公众号 #${form.account_id}`
  }
  return hit.name || hit.app_id || `公众号 #${hit.id}`
})

async function loadSetting() {
  loading.value = true
  try {
    const res: any = await getOauthSetting()
    if (res.code === ResultCode.SUCCESS && res.data) {
      Object.assign(form, res.data)
      form.wechat_accounts = res.data.wechat_accounts ?? []
    }
  }
  finally {
    loading.value = false
  }
}

async function patchSetting(partial: Partial<OauthSettingVo>) {
  const res: any = await saveOauthSetting(partial)
  if (res.code === ResultCode.SUCCESS && res.data) {
    Object.assign(form, res.data)
    form.wechat_accounts = res.data.wechat_accounts ?? form.wechat_accounts
    msg.success('保存成功')
  }
}

function openAccountDialog() {
  pickingAccountId.value = form.account_id || 0
  accountDialogVisible.value = true
}

async function saveAccount() {
  await patchSetting({ account_id: pickingAccountId.value })
  accountDialogVisible.value = false
}

function openHostDialog() {
  editingHost.value = form.host || ''
  hostDialogVisible.value = true
}

async function saveHost() {
  await patchSetting({ host: editingHost.value.trim() })
  hostDialogVisible.value = false
}

onMounted(loadSetting)
</script>

<template>
  <div v-loading="loading" class="mine-layout p-3">
    <div class="mine-card setting-page">
      <el-alert
        type="warning"
        :closable="false"
        show-icon
        class="mb-4"
        title="仅对认证服务号生效"
      />

      <SettingSection title="全局借用权限设置">
        <SettingRow
          label="选择公众号"
          type="link"
          :description="accountTitle"
          @link-click="openAccountDialog"
        />
        <SettingRow
          label="oAuth 独立域名"
          type="link"
          :description="form.host || '未设置'"
          @link-click="openHostDialog"
        />
      </SettingSection>
    </div>

    <el-dialog v-model="accountDialogVisible" title="选择公众号" width="480px">
      <el-select v-model="pickingAccountId" class="w-full" placeholder="请选择公众号">
        <el-option :value="0" label="不借用任何权限" />
        <el-option
          v-for="item in accounts"
          :key="item.id"
          :value="item.id"
          :label="item.name || item.app_id || `公众号 #${item.id}`"
        />
      </el-select>
      <p class="dialog-help">
        在微信公众号请求用户网页授权之前，需先到公众平台【开发者中心】网页服务中配置授权回调域名。
      </p>
      <template #footer>
        <el-button @click="accountDialogVisible = false">
          取消
        </el-button>
        <el-button type="primary" @click="saveAccount">
          确定
        </el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="hostDialogVisible" title="oAuth 独立域名" width="480px">
      <el-input v-model="editingHost" placeholder="例如：http://www.example.com" />
      <p class="dialog-help">
        适用于微站或活动有多个域名时，由此域名做统一 oauth 授权。注意：结尾没有 /
      </p>
      <template #footer>
        <el-button @click="hostDialogVisible = false">
          取消
        </el-button>
        <el-button type="primary" @click="saveHost">
          确定
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped lang="scss">
.setting-page {
  padding: 8px 16px 24px;
  min-height: 360px;
}

.dialog-help {
  margin: 12px 0 0;
  font-size: 12px;
  line-height: 1.6;
  color: var(--el-text-color-secondary);
}

.w-full {
  width: 100%;
}
</style>
