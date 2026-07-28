<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 - Please view the LICENSE file that was distributed with this source code,
 - For the full copyright and license information.
 - Thank you very much for using MineAdmin.
 -
 - @Author X.Mo<root@imoi.cn>
 - @Link   https://github.com/mineadmin
-->
<script setup lang="ts">
import { create, save } from '$/mine-admin/crontab/api/crontab.ts'
import getFormItems from './components/getFormItems.tsx'
import type { MaFormExpose } from '@mineadmin/form'
import useForm from '@/hooks/useForm.ts'
import { ResultCode } from '@/utils/ResultCode.ts'

const { formType = 'add', crontabData = null } = defineProps<{
  formType: 'add' | 'edit'
  crontabData?: any
}>()

const t = useTrans().globalTrans
const crontabFrom = ref<MaFormExpose>()
const model = ref<any>({})

useForm('crontabFrom').then((form: MaFormExpose) => {
  if (formType === 'edit' && crontabData) {
    Object.keys(crontabData).map((key: string) => {
      model.value[key] = crontabData[key]
    })
  }
  form.setItems(getFormItems(formType, t, model.value))
  form.setOptions({
    labelWidth: '95px',
  })
})

// 创建操作
function add(): Promise<any> {
  return new Promise((resolve, reject) => {
    create(model.value).then((res: any) => {
      res.code === ResultCode.SUCCESS ? resolve(res) : reject(res)
    }).catch((err) => {
      reject(err)
    })
  })
}

// 更新操作
function edit(): Promise<any> {
  return new Promise((resolve, reject) => {
    save(model.value.id as number, model.value).then((res: any) => {
      res.code === ResultCode.SUCCESS ? resolve(res) : reject(res)
    }).catch((err) => {
      reject(err)
    })
  })
}

defineExpose({
  add,
  edit,
  maForm: crontabFrom,
})
</script>

<template>
  <ma-form ref="crontabFrom" v-model="model" />
</template>

<style scoped lang="scss">

</style>
