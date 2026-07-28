# ZZZCMS 可视化编辑器插件

这是为ZZZCMS系统开发的可视化编辑器插件，提供所见即所得的内容编辑体验。

## 功能特性

- 拖拽式内容编辑
- 丰富的内容组件（文本、图片、视频、分隔线、按钮、相册、代码块等）
- 可视化属性编辑面板
- 撤销/重做功能
- 自动保存功能
- 预览模式
- 完全可自定义的配置系统
- 与CMS原生功能无缝集成

## 安装方法

1. 将本插件复制到CMS的`plugins`目录下
2. 插件会自动被识别，无需额外配置

## 使用方法

### 内容编辑

1. 登录CMS后台
2. 进入内容编辑页面
3. 默认情况下，可视化编辑器会自动加载
4. 使用顶部工具栏添加和编辑内容组件
5. 点击任意组件可以打开属性编辑面板
6. 编辑完成后，点击"保存"按钮保存内容

### 模板编辑

1. 登录CMS后台
2. 进入模板管理页面
3. 选择需要编辑的模板文件
4. 在模板编辑页面，您可以看到两种编辑模式：代码编辑和可视化编辑
5. 点击"可视化编辑"按钮切换到可视化编辑模式
6. 使用可视化编辑器编辑模板内容
7. 编辑完成后，点击"保存内容"按钮保存模板更改

## 配置方法

可以通过修改`visualeditor_config.js`文件来自定义编辑器行为：

```javascript
// 修改主题颜色
VisualEditorConfig.theme.primaryColor = '#337ab7';

// 配置可用组件
VisualEditorConfig.components.available = [
    {name: 'text', title: '文本', icon: 'fa-font'},
    {name: 'image', title: '图片', icon: 'fa-image'},
    {name: 'video', title: '视频', icon: 'fa-video-camera'}
    // 更多组件...
];

// 启用自动保存
VisualEditorConfig.behavior.autoSave = true;
VisualEditorConfig.behavior.autoSaveInterval = 30000; // 30秒
```

## 高级功能

### 扩展组件

可以通过`visualeditor_extend.js`文件扩展编辑器功能：

```javascript
// 添加自定义组件
VisualEditorConfig.components.available.push({
    name: 'custom-component',
    title: '自定义组件',
    icon: 'fa-cube',
    template: '<div class="custom-component">这是一个自定义组件</div>'
});
```

### 内容预处理/后处理

可以添加内容预处理和后处理函数：

```javascript
// 预处理内容（保存前）
VisualEditorConfig.cmsIntegration.preprocessContent = function(content) {
    // 处理或过滤内容
    return processedContent;
};

// 后处理内容（加载后）
VisualEditorConfig.cmsIntegration.postprocessContent = function(content) {
    // 处理或转换内容
    return processedContent;
};
```

## 兼容性

- 支持主流现代浏览器（Chrome, Firefox, Safari, Edge）
- IE浏览器部分功能可能受限

## 注意事项

1. 使用可视化编辑器时，原有的UEditor编辑器将被禁用
2. 请确保已经正确配置了CMS的图片上传功能
3. 如果遇到任何问题，请先检查浏览器控制台是否有错误信息

## 更新日志

### 1.0.0
- 初始版本
- 基本编辑功能实现
- 支持文本、图片、视频、分隔线、按钮、相册、代码块等组件
- 可视化属性编辑面板
- 撤销/重做功能
- 自动保存功能
- 预览模式

## 版权信息

本插件基于MIT许可证开源

© " + new Date().getFullYear() + " ZZZCMS Team