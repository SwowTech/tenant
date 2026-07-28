/**
 * ZZZCMS 可视化编辑器配置示例
 * 复制此文件并重命名为 visualeditor_config.js 以自定义编辑器行为
 */
var VisualEditorConfig = {
    // 主题配置
    theme: {
        // 主色调
        primaryColor: '#1890ff',
        // 次色调
        secondaryColor: '#52c41a',
        // 警告色
        warningColor: '#faad14',
        // 错误色
        errorColor: '#f5222d',
        // 文本颜色
        textColor: '#333333',
        // 背景颜色
        backgroundColor: '#ffffff'
    },
    
    // 工具栏配置
    toolbar: {
        // 是否显示工具栏
        show: true,
        // 左侧按钮
        leftButtons: [
            { name: 'bold', icon: 'fa-bold', title: '加粗', command: 'bold' },
            { name: 'italic', icon: 'fa-italic', title: '斜体', command: 'italic' },
            { name: 'underline', icon: 'fa-underline', title: '下划线', command: 'underline' },
            { name: '|' },
            { name: 'alignLeft', icon: 'fa-align-left', title: '左对齐', command: 'justifyLeft' },
            { name: 'alignCenter', icon: 'fa-align-center', title: '居中对齐', command: 'justifyCenter' },
            { name: 'alignRight', icon: 'fa-align-right', title: '右对齐', command: 'justifyRight' }
        ],
        // 右侧按钮
        rightButtons: [
            { name: 'undo', icon: 'fa-undo', title: '撤销', command: 'undo' },
            { name: 'redo', icon: 'fa-repeat', title: '重做', command: 'redo' },
            { name: '|' },
            { name: 'save', icon: 'fa-save', title: '保存', command: 'save' }
        ]
    },
    
    // 组件配置
    components: {
        // 可用组件列表
        available: ['text', 'image', 'video', 'divider', 'button', 'gallery'],
        // 各组件的默认属性
        defaults: {
            text: {
                content: '点击编辑文本',
                style: {}
            },
            image: {
                src: '',
                alt: '',
                style: { maxWidth: '100%' }
            },
            video: {
                src: '',
                controls: true,
                style: { maxWidth: '100%' }
            },
            button: {
                text: '按钮',
                url: '#',
                style: {}
            }
        }
    },
    
    // 编辑器行为配置
    behavior: {
        // 是否启用拖拽功能
        enableDragDrop: true,
        // 是否启用撤销/重做功能
        enableUndo: true,
        // 是否启用自动保存
        autoSave: true,
        // 自动保存间隔（毫秒）
        autoSaveInterval: 30000 // 30秒
    },
    
    // 尺寸配置
    dimensions: {
        // 编辑器最小高度
        minHeight: '300px',
        // 工具栏高度
        toolbarHeight: '44px',
        // 属性面板宽度
        propertyPanelWidth: '280px'
    },
    
    // 语言配置
    language: {
        // 当前语言
        current: 'zh',
        // 语言包
        packages: {
            'zh': {
                'bold': '加粗',
                'italic': '斜体',
                'underline': '下划线',
                'alignLeft': '左对齐',
                'alignCenter': '居中对齐',
                'alignRight': '右对齐',
                'undo': '撤销',
                'redo': '重做',
                'save': '保存',
                'text': '文本',
                'image': '图片',
                'video': '视频',
                'divider': '分隔线',
                'button': '按钮',
                'gallery': '相册',
                'editContent': '编辑内容'
            },
            'en': {
                'bold': 'Bold',
                'italic': 'Italic',
                'underline': 'Underline',
                'alignLeft': 'Align Left',
                'alignCenter': 'Align Center',
                'alignRight': 'Align Right',
                'undo': 'Undo',
                'redo': 'Redo',
                'save': 'Save',
                'text': 'Text',
                'image': 'Image',
                'video': 'Video',
                'divider': 'Divider',
                'button': 'Button',
                'gallery': 'Gallery',
                'editContent': 'Edit Content'
            }
        }
    },
    
    // CMS集成配置
    cmsIntegration: {
        // 保存URL
        saveUrl: '',
        // 保存成功回调
        onSaveSuccess: function(response) {
            // 这里可以处理保存成功的逻辑
            console.log('保存成功', response);
            alert('保存成功！');
        },
        // 保存失败回调
        onSaveError: function(xhr) {
            // 这里可以处理保存失败的逻辑
            console.error('保存失败', xhr);
            alert('保存失败，请重试！');
        },
        // 上传配置
        upload: {
            // 上传URL
            url: '',
            // 是否使用原生上传器
            useNative: true,
            // 上传类型
            type: 'image',
            // 上传数量
            num: '1',
            // 上传模块类型
            stype: 'content',
            // 输入框ID
            inputId: 'temp_image',
            // 自定义上传函数（可选）
            /*
            customFunction: function(callback) {
                // 自定义上传逻辑
                // 上传完成后调用callback(url)
            }
            */
        },
        // 内容预处理函数（保存前）
        preprocessContent: function(content) {
            // 在这里对内容进行处理
            return content;
        },
        // 内容后处理函数（加载后）
        postprocessContent: function(content) {
            // 在这里对内容进行处理
            return content;
        },
        // 预处理选项
        preprocessOptions: {
            // 是否移除编辑器特定的类
            removeEditorClasses: true,
            // 是否移除编辑器特定的属性
            removeEditorAttributes: true,
            // 是否移除draggable属性
            removeDraggableAttr: true,
            // 自定义清理函数（可选）
            /*
            customCleanup: function(temp) {
                // 在这里对临时DOM对象进行清理
                return temp;
            }
            */
        },
        // 是否允许重写open_upload函数
        allowRewriteUpload: true,
        // 轮询间隔（毫秒）
        pollInterval: 500,
        // 编辑模式切换按钮HTML
        toggleButtonsHTML: ''
    },
    
    // 获取消息文本（根据当前语言）
    getMessage: function(key) {
        var lang = this.language.current || 'zh';
        var packages = this.language.packages || {};
        var langPackage = packages[lang] || packages['zh'] || {};
        return langPackage[key] || key;
    },
    
    // 设置语言
    setLanguage: function(lang) {
        this.language.current = lang;
    },
    
    // 获取主题颜色
    getThemeColor: function(type) {
        return this.theme[type] || '#1890ff';
    }
};

// 确保在Node.js环境中也能正常工作
if (typeof module !== 'undefined' && module.exports) {
    module.exports = VisualEditorConfig;
}