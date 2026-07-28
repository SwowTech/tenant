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
defineOptions({ name: 'ToolsPageBar' })
// 定义组件的属性和事件
// eslint-disable-next-line unused-imports/no-unused-vars
const props = defineProps<{
  title?: string // 页面标题
  description?: string // 页面描述
  showButtons?: boolean // 是否显示按钮组
  buttons?: Array<{ // 按钮配置数组
    text?: string // 按钮文本
    type?: string // 按钮类型，默认 'primary'
    show?: boolean // 按钮是否显示
    action?: () => void // 按钮点击事件
  }>
}>()

// 处理按钮点击事件
function handleButtonClick(action?: () => void): void {
  action?.() // 使用可选链安全调用 action
}
</script>

<template>
  <div class="mine-layout">
    <!-- 头部 -->
    <div class="align-center nmPageBar relative mx-3 justify-between rounded bg-white/40 p-[12px] lg:flex">
      <div class="flex items-center gap-x-4">
        <div class="text-base">
          {{ title }}
        </div>
        <div class="text-sm text-gray-600 text-opacity-100">
          {{ description }}
        </div>
        <!--  增加一个插槽 -->
        <slot name="left" />
      </div>
      <div class="flex items-center gap-x-4">
        <!-- 动态控制按钮的显示与隐藏 -->
        <template v-if="showButtons">
          <el-button
            v-for="(button, index) in buttons"
            :key="index"
            :type="button.type || 'primary'"
            class="!m-0"
            @click="handleButtonClick(button.action)"
          >
            {{ button.text }}
          </el-button>
        </template>
        <slot name="right" />
      </div>
    </div>
  </div>
</template>

<style scoped>
.nmPageBar{
  --tw-border-opacity: 1;
  background: rgba(255, 255, 255, 0.4);
  border-width: 1px;
  border-style: solid;
  border-color: rgb(255, 255, 255, var(--tw-border-opacity));
}
.dark .nmPageBar {
  --tw-border-opacity: 0.1;
  background: transparent;
  border-width: 1px;
  border-style: solid;
  border-color: rgb(255, 255, 255, var(--tw-border-opacity));
}
</style>
