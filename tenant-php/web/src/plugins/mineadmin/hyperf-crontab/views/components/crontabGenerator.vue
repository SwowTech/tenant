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
defineOptions({ name: 'CrontabGenerator' })

const model = defineModel<string>()
</script>

<template>
  <el-input v-model="model">
    <template #append>
      <el-select
        class="w-120px" @change="(v) => model = v"
      >
        <el-option label="每分钟" value="0 * * * * *" />
        <el-option label="每小时" value="0 0 * * * *" />
        <el-option label="每天零点" value="0 0 0 * * *" />
        <el-option label="每月一号零点" value="0 0 0 1 * *" />
        <el-option label="每周星期日零点" value="0 0 0 * * 0" />
      </el-select>
    </template>
  </el-input>
</template>

<style scoped lang="scss">

</style>
