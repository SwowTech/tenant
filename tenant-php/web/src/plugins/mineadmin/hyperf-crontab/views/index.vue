<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 - Please view the LICENSE file that was distributed with this source code,
 - For the full copyright and license information.
 - Thank you very much for using MineAdmin.
 -
 - @Author X.Mo<root@imoi.cn>
 - @Link   https://github.com/mineadmin
-->
<script setup lang="tsx">
import { deleted, execute, list, save } from '$/mine-admin/crontab/api/crontab.ts'
import useDialog from '@/hooks/useDialog.ts'
import type { UseDialogExpose } from '@/hooks/useDialog.ts'
import { useMessage } from '@/hooks/useMessage.ts'
import { ResultCode } from '@/utils/ResultCode.ts'

import CrontabFrom from './form.vue'
import Logs from './logs.vue'
import hasAuth from '@/utils/permission/hasAuth.ts'
import type { UseDrawerExpose } from '@/hooks/useDrawer.ts'
import useDrawer from '@/hooks/useDrawer.ts'
import ToolsPageBar from '$/mine-admin/crontab/views/components/toolsPageBar.vue'
import getSearchItems from '$/mine-admin/crontab/views/components/getSearchItems.tsx'

defineOptions({ name: 'plugin:mine-admin:crontab' })

const msg = useMessage()
const dictStore = useDictStore()
const formRef = ref()
const t = useTrans().globalTrans
const loading = ref(true)

const pagination = ref({
  page: 1,
  page_size: 10,
})

const data = ref<{ total: number, list: any[] }>({
  total: 0,
  list: [],
})

const formItems = getSearchItems(t)

function getCrontabList(params: Record<string, any> = {}) {
  loading.value = true
  list({ ...pagination.value, ...params }).then((res: any) => {
    data.value.total = res.data.total
    data.value.list = res.data.list as any[]
  }).catch((err) => {
    throw new Error(err)
  }).finally(() => {
    loading.value = false
  })
}

// 更新定时任务
function enableOrDisable(item: any) {
  item.status = !item.status
  save(item.id as number, item).then((res: any) => {
    res.code === ResultCode.SUCCESS ? msg.success(t('crud.updateSuccess')) : msg.error(res.message)
    getCrontabList()
  }).catch((err) => {
    throw new Error(err)
  })
}

// 执行定时任务
function executeTask(id: number) {
  execute([id]).then((res: any) => {
    if (res.code === ResultCode.SUCCESS) {
      msg.success(t('mineCrontab.op.executeSuccess'))
    }
    else {
      msg.error(t('mineCrontab.op.executeFail'))
    }
  })
}

// 删除
function handleDelete(id: number) {
  msg.delConfirm(t('crud.delDataMessage')).then(async () => {
    const response: any = await deleted([id])
    if (response.code === ResultCode.SUCCESS) {
      msg.success(t('crud.delSuccess'))
      getCrontabList()
    }
  })
}

// 抽屉配置
const maDrawer: UseDrawerExpose = useDrawer({
  size: '50%',
})

// 弹窗配置
const maDialog: UseDialogExpose = useDialog({
  alignCenter: true,
  // 保存数据
  ok: ({ formType }, okLoadingState: (state: boolean) => void) => {
    okLoadingState(true)
    if (['add', 'edit'].includes(formType)) {
      const elForm = formRef.value.maForm.getElFormRef()
      // 验证通过后
      elForm.validate().then(() => {
        switch (formType) {
          // 新增
          case 'add':
            formRef.value.add().then((res: any) => {
              res.code === ResultCode.SUCCESS ? msg.success(t('crud.createSuccess')) : msg.error(res.message)
              maDialog.close()
              getCrontabList()
            }).catch((err: any) => {
              msg.alertError(err)
            })
            break
            // 修改
          case 'edit':
            formRef.value.edit().then((res: any) => {
              res.code === 200 ? msg.success(t('crud.updateSuccess')) : msg.error(res.message)
              maDialog.close()
              getCrontabList()
            }).catch((err: any) => {
              msg.alertError(err)
            })
            break
        }
      }).catch()
    }
    else {
      maDialog.close()
    }
    okLoadingState(false)
  },
})

// 定义按钮配置数组
const buttons = [
  {
    text: t('mineCrontab.menu.save'),
    type: 'primary', // 按钮类型
    show: true, // 是否显示
    action: () => {
      maDialog.setTitle(t('mineCrontab.menu.save'))
      maDialog.open({ formType: 'add' })
    },
  },
]

function handlePageChange(page: number) {
  pagination.value.page = page
  getCrontabList()
}

function handleSizeChange(size: number) {
  pagination.value.page_size = size
  pagination.value.page = 1 // 切换每页条数时通常回到第一页
  getCrontabList()
}

onMounted(() => {
  getCrontabList()
})
</script>

<template>
  <div class="mine-layout">
    <!-- 标题 -->
    <ToolsPageBar :title="t('mineCrontab.menu.index')" :show-buttons="true" :buttons="buttons" />
    <!-- 搜索框 -->
    <div class="mine-card">
      <ma-search
        :search-items="formItems"
        :options="{ cols: { xs: 1, sm: 2, md: 2, lg: 4, xl: 5 }, foldButtonShow: false }" @search="getCrontabList"
        @reset="getCrontabList"
      />
    </div>
    <!-- 表格 -->
    <div>
      <!-- 判断data.list数组 -->
      <div v-if="data.list.length === 0" class="mine-card">
        <el-empty>
          <el-button
            v-auth="['plugin:mine-admin:crontab:create']" type="primary" @click="() => {
              maDialog.setTitle(t('mineCrontab.menu.save'))
              maDialog.open({ formType: 'add' })
            }"
          >
            {{ t('mineCrontab.menu.save') }}
          </el-button>
        </el-empty>
      </div>
      <div v-else v-loading="loading" class="p-3">
        <div class="grid grid-cols-1 w-full gap-[10px] lg:grid-cols-5 md:grid-cols-3 sm:grid-cols-2">
          <template v-for="item in data.list">
            <div
              class="crontab_box relative max-h-[240px] min-h-[220px] rounded-md bg-[position:100%_0px] bg-no-repeat p-4"
            >
              <div class="relative z-2">
                <div class="flex justify-between">
                  <span>{{ item.id }} - {{ item.name }}</span>
                  <el-text :type="item.status ? 'primary' : 'danger'">
                    <ma-svg-icon size="1.5em" name="i-ph:dot-outline-fill" />
                    <span>{{ item.status ? t('mineCrontab.status.running') : t('mineCrontab.status.stopped')
                    }}</span>
                  </el-text>
                </div>
                <div class="text-sm">
                  <div class="flex items-center">
                    <ElText :type="dictStore.t('crontab-type', item.type, 'color')">
                      {{ t(dictStore.t('crontab-type', item.type, 'i18n')) }}
                    </ElText>
                    <el-divider direction="vertical" />
                    <div class="memo">
                      {{ item.memo ?? '-' }}
                    </div>
                  </div>
                  <div class="mt-5 flex flex-col text-gray-400 dark-text-gray-3">
                    <div class="w-full flex justify-between">
                      <div>{{ t('mineCrontab.cols.rule') }}：</div>
                      <el-text truncated>
                        {{ item.rule }}
                      </el-text>
                    </div>
                    <div class="w-full flex justify-between">
                      <div class="inline-block w-400px">
                        {{ t('mineCrontab.cols.value') }}：
                      </div>
                      <el-text truncated>
                        {{ item.value }}
                      </el-text>
                    </div>
                    <div class="w-full flex justify-between">
                      {{ t('mineCrontab.cols.singleton') }}：
                      <ma-svg-icon
                        :name="item.is_singleton ? 'heroicons:check-16-solid' : 'heroicons:x-mark-16-solid'"
                        class="relative top-[4px]" :size="18"
                      />
                    </div>
                    <div class="w-full flex justify-between">
                      {{ t('mineCrontab.cols.onOneServer') }}：
                      <ma-svg-icon
                        :name="item.is_on_one_server ? 'heroicons:check-16-solid' : 'heroicons:x-mark-16-solid'"
                        class="relative top-[4px]" :size="18"
                      />
                    </div>
                  </div>
                </div>
              </div>

              <div class="mt-[10px] w-full flex justify-between">
                <div>
                  <el-button v-auth="['plugin:mine-admin:crontab:save']" circle>
                    <ma-svg-icon
                      :name="item.status ? 'material-symbols:pause-rounded' : 'material-symbols:play-arrow-rounded'"
                      :size="20" @click="enableOrDisable(item)"
                    />
                  </el-button>
                </div>
                <el-dropdown>
                  <el-button text>
                    <ma-svg-icon name="i-si:more-square-horiz-fill" />
                  </el-button>
                  <template #dropdown>
                    <el-dropdown-menu>
                      <el-dropdown-item
                        key="executeOnce" v-auth="['plugin:mine-admin:crontab:execute']"
                        @click="executeTask(item.id)"
                      >
                        {{ t('mineCrontab.op.executeOnce') }}
                      </el-dropdown-item>
                      <el-dropdown-item
                        key="runLogs" divided @click="() => {
                          maDrawer.setTitle(`${item.name} - ${t('mineCrontab.op.runLogs')}`)
                          maDrawer.open({ formType: null, data: item })
                        }"
                      >
                        {{ t('mineCrontab.op.runLogs') }}
                      </el-dropdown-item>
                      <el-dropdown-item
                        v-if="hasAuth('plugin:mine-admin:crontab:save')" key="edit" @click="() => {
                          maDialog.setTitle((`${t('mineCrontab.op.edit')} - ${item.name}`))
                          maDialog.open({ formType: 'edit', crontabData: item })
                        }"
                      >
                        {{ t('mineCrontab.op.edit') }}
                      </el-dropdown-item>
                      <el-dropdown-item
                        v-if="hasAuth('plugin:mine-admin:crontab:delete')" key="delete"
                        @click="handleDelete(item.id)"
                      >
                        {{ t('mineCrontab.op.delete') }}
                      </el-dropdown-item>
                    </el-dropdown-menu>
                  </template>
                </el-dropdown>
              </div>
            </div>
          </template>
        </div>
        <div class="mt-10 flex justify-end">
          <el-pagination
            background layout="total, sizes, prev, pager, next, jumper" :total="data.total"
            @current-change="handlePageChange" @size-change="handleSizeChange"
          />
        </div>
      </div>

      <component :is="maDialog.Dialog">
        <template #default="{ formType, crontabData }">
          <!-- 新增、编辑任务表单 -->
          <CrontabFrom v-if="formType" ref="formRef" :form-type="formType" :crontab-data="crontabData" />
        </template>
      </component>
      <component :is="maDrawer.Drawer">
        <template #default="{ data }">
          <!-- 运行日志 -->
          <Logs :crontab-id="data.id" />
        </template>
      </component>
    </div>
  </div>
</template>

<style scoped lang="scss">
.run-state {
  @apply -top-25 text-green-8 -right-25 !absolute opacity-[0.1];
}

.pause-state {
  @apply -top-25 text-red-5 -right-27 !absolute opacity-[0.1];
}

.memo {
  @apply text-gray-4;
}

.add-crontab {
  @apply flex justify-center items-center b-1 b-solid b-gray-3 hover:b-[rgb(var(--ui-primary))] rounded transition-all duration-200 ease-in-out cursor-pointer dark-b-dark-1 dark-hover:b-[rgb(var(--ui-primary))] text-gray-5 hover:text-[rgb(var(--ui-primary))];
}

.crontab_box {
  background-color: white;
  background-image: url("$/mine-admin/crontab/assets/bg.png");
  background-repeat: no-repeat;
}

.dark .crontab_box {
  --un-bg-opacity: 1;
  background-color: rgb(24 24 24 / var(--un-bg-opacity));
}
</style>
