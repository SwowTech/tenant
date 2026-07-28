<script setup lang="ts">
import type { FormInstance, FormRules } from 'element-plus'
import { getAccount, refreshAccessToken, saveAccount, type WechatAccountVo } from '~/base/api/wechat'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'

defineOptions({ name: 'wechat:account' })

const msg = useMessage()
const formRef = ref<FormInstance>()
const loading = ref(false)
const saving = ref(false)
const testing = ref(false)

const form = reactive<WechatAccountVo>({
  name: '',
  app_id: '',
  app_secret: '',
  app_secret_set: false,
  token: '',
  encoding_aes_key: '',
  level: 1,
  status: 1,
  callback_url: '',
})

const rules: FormRules = {
  name: [{ required: true, message: '请输入公众号名称', trigger: 'blur' }],
  app_id: [{ required: true, message: '请输入 AppID', trigger: 'blur' }],
  token: [{ required: true, message: '请输入 Token', trigger: 'blur' }],
}

async function load() {
  loading.value = true
  try {
    const res: any = await getAccount()
    if (res.code === ResultCode.SUCCESS && res.data) {
      Object.assign(form, res.data)
      form.app_secret = ''
    }
  }
  finally {
    loading.value = false
  }
}

async function handleSave() {
  await formRef.value?.validate()
  saving.value = true
  try {
    const payload = { ...form }
    if (!payload.app_secret) {
      delete (payload as any).app_secret
    }
    const res: any = await saveAccount(payload)
    if (res.code === ResultCode.SUCCESS) {
      msg.success('保存成功')
      Object.assign(form, res.data)
      form.app_secret = ''
    }
  }
  finally {
    saving.value = false
  }
}

async function handleRefreshToken() {
  testing.value = true
  try {
    const res: any = await refreshAccessToken()
    if (res.code === ResultCode.SUCCESS) {
      msg.success(`AccessToken 有效，约 ${res.data?.expires_in ?? '-'} 秒后过期`)
    }
  }
  finally {
    testing.value = false
  }
}

async function copyCallbackUrl() {
  if (!form.callback_url) {
    return
  }
  try {
    await navigator.clipboard.writeText(form.callback_url)
    msg.success('已复制服务器 URL')
  }
  catch {
    msg.error('复制失败，请手动选择')
  }
}

onMounted(load)
</script>

<template>
  <div v-loading="loading" class="mine-layout mine-card">
    <el-alert
      class="mb-4"
      type="info"
      show-icon
      :closable="false"
      title="将下方「服务器 URL」与 Token 填到微信公众平台 → 开发 → 基本配置。保存后可点「测试 AccessToken」。"
    />
    <el-form
      ref="formRef"
      :model="form"
      :rules="rules"
      label-width="140px"
      class="max-w-3xl"
    >
      <el-form-item label="公众号名称" prop="name">
        <el-input v-model="form.name" placeholder="便于识别的名称" />
      </el-form-item>
      <el-form-item label="AppID" prop="app_id">
        <el-input v-model="form.app_id" placeholder="微信公众平台 AppID" />
      </el-form-item>
      <el-form-item label="AppSecret" prop="app_secret">
        <el-input
          v-model="form.app_secret"
          type="password"
          show-password
          :placeholder="form.app_secret_set ? '已配置，留空则不修改' : '微信公众平台 AppSecret'"
        />
      </el-form-item>
      <el-form-item label="Token" prop="token">
        <el-input v-model="form.token" placeholder="与公众平台服务器配置一致" />
      </el-form-item>
      <el-form-item label="EncodingAESKey">
        <el-input v-model="form.encoding_aes_key" placeholder="可选，消息加解密密钥" />
      </el-form-item>
      <el-form-item label="账号类型">
        <el-select v-model="form.level" class="w-full">
          <el-option :value="1" label="订阅号" />
          <el-option :value="2" label="服务号" />
          <el-option :value="3" label="认证订阅号" />
          <el-option :value="4" label="认证服务号" />
        </el-select>
      </el-form-item>
      <el-form-item label="启用">
        <el-switch v-model="form.status" :active-value="1" :inactive-value="0" />
      </el-form-item>
      <el-form-item label="服务器 URL">
        <div class="flex w-full gap-2">
          <el-input v-model="form.callback_url" readonly />
          <el-button @click="copyCallbackUrl">
            复制
          </el-button>
        </div>
      </el-form-item>
      <el-form-item>
        <el-button type="primary" :loading="saving" @click="handleSave">
          保存
        </el-button>
        <el-button :loading="testing" @click="handleRefreshToken">
          测试 AccessToken
        </el-button>
      </el-form-item>
    </el-form>
  </div>
</template>
