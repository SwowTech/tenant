<script setup lang="ts">
import { ElMessageBox } from 'element-plus'
import { addSensitiveWord, deleteSensitiveWord, getSensitiveWordList } from '~/base/api/setting'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'

defineOptions({ name: 'setting:sensitive-word' })

const msg = useMessage()
const loading = ref(false)
const saving = ref(false)
const list = ref<string[]>([])
const keyword = ref('')
const dialogVisible = ref(false)
const wordsText = ref('')

const filteredList = computed(() => {
  const kw = keyword.value.trim()
  if (!kw) {
    return list.value
  }
  return list.value.filter(word => word.includes(kw))
})

const tableRows = computed(() => filteredList.value.map(word => ({ word })))

async function loadList() {
  loading.value = true
  try {
    const res: any = await getSensitiveWordList()
    if (res.code === ResultCode.SUCCESS && res.data) {
      list.value = res.data.list ?? []
    }
  }
  finally {
    loading.value = false
  }
}

function openAdd() {
  wordsText.value = ''
  dialogVisible.value = true
}

async function saveWords() {
  const text = wordsText.value.trim()
  if (!text) {
    msg.warning('请输入敏感词')
    return
  }
  saving.value = true
  try {
    const res: any = await addSensitiveWord(text)
    if (res.code === ResultCode.SUCCESS) {
      list.value = res.data?.list ?? list.value
      msg.success('保存成功')
      dialogVisible.value = false
    }
  }
  finally {
    saving.value = false
  }
}

async function removeWord(word: string) {
  try {
    await ElMessageBox.confirm(`确认删除敏感词「${word}」吗？`, '提示', { type: 'warning' })
  }
  catch {
    return
  }
  const res: any = await deleteSensitiveWord(word)
  if (res.code === ResultCode.SUCCESS) {
    list.value = res.data?.list ?? list.value.filter(item => item !== word)
    msg.success('已删除')
  }
}

onMounted(loadList)
</script>

<template>
  <div v-loading="loading" class="mine-layout p-3">
    <div class="mine-card setting-page">
      <el-alert
        type="warning"
        :closable="false"
        show-icon
        class="mb-4"
        title="敏感词在以下功能中过滤：自动回复关键字、微官网文章标题和内容"
      />

      <div class="setting-toolbar">
        <el-input
          v-model="keyword"
          clearable
          placeholder="输入要搜索的敏感词汇"
          class="setting-toolbar__search"
        >
          <template #append>
            <el-button>
              搜索
            </el-button>
          </template>
        </el-input>
        <el-button type="primary" @click="openAdd">
          添加敏感词
        </el-button>
      </div>

      <el-table :data="tableRows" stripe empty-text="暂无敏感词">
        <el-table-column prop="word" label="敏感词汇" min-width="200" />
        <el-table-column label="操作" width="120" fixed="right">
          <template #default="{ row }">
            <el-button link type="danger" @click="removeWord(row.word)">
              删除
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <el-dialog v-model="dialogVisible" title="添加敏感词汇" width="520px">
      <p class="dialog-hint">
        请输入要过滤的敏感词汇（按回车添加多个）
      </p>
      <el-input
        v-model="wordsText"
        type="textarea"
        :rows="5"
        placeholder="每行一个敏感词"
      />
      <template #footer>
        <el-button @click="dialogVisible = false">
          取消
        </el-button>
        <el-button type="primary" :loading="saving" @click="saveWords">
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

.dialog-hint {
  margin: 0 0 12px;
  font-size: 13px;
  color: var(--el-text-color-secondary);
}
</style>
