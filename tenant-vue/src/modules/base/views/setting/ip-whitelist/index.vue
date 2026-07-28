<script setup lang="ts">
import { ElMessageBox } from 'element-plus'
import { getIpWhitelist, saveIpWhitelist } from '~/base/api/setting'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'

defineOptions({ name: 'setting:ip-whitelist' })

const msg = useMessage()
const loading = ref(false)
const saving = ref(false)
const list = ref<string[]>([])
const keyword = ref('')
const dialogVisible = ref(false)
const ipsText = ref('')

const filteredList = computed(() => {
  const kw = keyword.value.trim()
  if (!kw) {
    return list.value
  }
  return list.value.filter(ip => ip.includes(kw))
})

const tableRows = computed(() => filteredList.value.map(ip => ({ ip })))

async function loadList() {
  loading.value = true
  try {
    const res: any = await getIpWhitelist()
    if (res.code === ResultCode.SUCCESS && res.data) {
      list.value = res.data.list ?? []
    }
  }
  finally {
    loading.value = false
  }
}

function openAdd() {
  ipsText.value = ''
  dialogVisible.value = true
}

async function saveIps() {
  const additions = ipsText.value
    .split(/[\n,，\s]+/)
    .map(s => s.trim())
    .filter(Boolean)

  if (additions.length === 0) {
    msg.warning('请输入有效 IP 地址')
    return
  }

  const next = [...list.value]
  for (const ip of additions) {
    if (!next.includes(ip)) {
      next.push(ip)
    }
  }

  saving.value = true
  try {
    const res: any = await saveIpWhitelist({ list: next })
    if (res.code === ResultCode.SUCCESS) {
      list.value = res.data?.list ?? next
      msg.success('保存成功')
      dialogVisible.value = false
    }
  }
  finally {
    saving.value = false
  }
}

async function removeIp(ip: string) {
  try {
    await ElMessageBox.confirm(`确认删除 IP「${ip}」吗？`, '提示', { type: 'warning' })
  }
  catch {
    return
  }
  const next = list.value.filter(item => item !== ip)
  const res: any = await saveIpWhitelist({ list: next })
  if (res.code === ResultCode.SUCCESS) {
    list.value = res.data?.list ?? next
    msg.success('已删除')
  }
}

onMounted(loadList)
</script>

<template>
  <div v-loading="loading" class="mine-layout p-3">
    <div class="mine-card setting-page">
      <div class="setting-toolbar">
        <el-input
          v-model="keyword"
          clearable
          placeholder="输入要搜索的 ip 地址"
          class="setting-toolbar__search"
        >
          <template #append>
            <el-button>
              搜索
            </el-button>
          </template>
        </el-input>
        <el-button type="primary" @click="openAdd">
          添加白名单
        </el-button>
      </div>

      <el-table :data="tableRows" stripe empty-text="暂无IP白名单">
        <el-table-column prop="ip" label="IP地址" min-width="200" />
        <el-table-column label="操作" width="120" fixed="right">
          <template #default="{ row }">
            <el-button link type="danger" @click="removeIp(row.ip)">
              删除
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <el-dialog v-model="dialogVisible" title="添加白名单" width="520px">
      <el-alert
        type="info"
        :closable="false"
        show-icon
        class="mb-4"
        title="添加局域网并保持开启状态的 IP 可访问系统；多个 IP 用回车隔开"
      />
      <el-input
        v-model="ipsText"
        type="textarea"
        :rows="5"
        placeholder="多个IP地址用回车隔开"
      />
      <template #footer>
        <el-button @click="dialogVisible = false">
          取消
        </el-button>
        <el-button type="primary" :loading="saving" @click="saveIps">
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

.setting-toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;

  &__search {
    width: 320px;
  }
}
</style>
