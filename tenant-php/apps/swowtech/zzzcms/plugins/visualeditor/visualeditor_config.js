/**
 * ZZZCMS 可视化编辑器配置文件
 * 用户可以通过修改此文件自定义编辑器的行为和外观
 */

var VisualEditorConfig = {
    // 编辑器主题
    theme: {
        // 主题颜色
        primaryColor: '#1ab394',
        secondaryColor: '#f8ac59',
        backgroundColor: '#ffffff',
        textColor: '#333333',
        borderColor: '#e5e6e7',
        hoverColor: '#f3f3f4'
    },
    
    // 编辑器工具栏配置
    toolbar: {
        // 是否显示工具栏
        show: false,
        
        // 工具栏按钮配置（左侧）
        leftButtons: [
            {
                name: 'bold',
                icon: 'fa-bold',
                title: '加粗',
                command: 'bold'
            },
            {
                name: 'italic',
                icon: 'fa-italic',
                title: '斜体',
                command: 'italic'
            },
            {
                name: 'underline',
                icon: 'fa-underline',
                title: '下划线',
                command: 'underline'
            },
            {
                name: 'strikeThrough',
                icon: 'fa-strikethrough',
                title: '删除线',
                command: 'strikeThrough'
            },
            {
                name: 'separator',
                type: 'separator'
            },
            {
                name: 'alignLeft',
                icon: 'fa-align-left',
                title: '左对齐',
                command: 'justifyLeft'
            },
            {
                name: 'alignCenter',
                icon: 'fa-align-center',
                title: '居中对齐',
                command: 'justifyCenter'
            },
            {
                name: 'alignRight',
                icon: 'fa-align-right',
                title: '右对齐',
                command: 'justifyRight'
            },
            {
                name: 'alignJustify',
                icon: 'fa-align-justify',
                title: '两端对齐',
                command: 'justifyFull'
            }
        ],
        
        // 工具栏按钮配置（右侧）
        rightButtons: [
            {
                name: 'undo',
                icon: 'fa-undo',
                title: '撤销',
                command: 'undo'
            },
            {
                name: 'redo',
                icon: 'fa-repeat',
                title: '重做',
                command: 'redo'
            },
            {
                name: 'save',
                icon: 'fa-save',
                title: '保存',
                action: 'saveContent'
            }
        ]
    },
    
    // 组件配置
    components: {
        // 可用组件列表
        available: [
            {
                name: 'text',
                icon: 'fa-paragraph',
                title: '文本',
                type: 'text',
                template: '<div class="visual-component" data-type="text">点击编辑文本内容</div>'
            },
            {
                name: 'image',
                icon: 'fa-picture-o',
                title: '图片',
                type: 'image',
                action: 'insertImage'
            },
            {
                name: 'video',
                icon: 'fa-film',
                title: '视频',
                type: 'video',
                action: 'insertVideo'
            },
            {
                name: 'divider',
                icon: 'fa-minus',
                title: '分隔线',
                type: 'divider',
                template: '<hr class="visual-component" data-type="divider">'
            },
            {
                name: 'button',
                icon: 'fa-hand-pointer-o',
                title: '按钮',
                type: 'button',
                template: '<button class="visual-component btn btn-primary" data-type="button">按钮</button>'
            },
            {
                name: 'gallery',
                icon: 'fa-th',
                title: '相册',
                type: 'gallery',
                action: 'insertGallery'
            },
            {
                name: 'code',
                icon: 'fa-code',
                title: '代码',
                type: 'code',
                template: '<pre class="visual-component" data-type="code"><code>点击编辑代码内容</code></pre>'
            }
        ],
        
        // 默认组件属性
        defaultProps: {
            // 图片组件默认属性
            image: {
                width: 'auto',
                height: 'auto',
                align: 'center',
                caption: ''
            },
            
            // 视频组件默认属性
            video: {
                width: '100%',
                height: '360px',
                align: 'center'
            },
            
            // 按钮组件默认属性
            button: {
                text: '按钮',
                type: 'primary', // primary, success, info, warning, danger
                size: 'default', // default, lg, sm, xs
                url: '#',
                target: '_self'
            },
            
            // 文本组件默认属性
            text: {
                fontSize: '16px',
                lineHeight: '1.6',
                color: '#333333',
                backgroundColor: 'transparent',
                padding: '10px',
                textAlign: 'left'
            }
        }
    },
    
    // 编辑器行为配置
    behavior: {
        // 是否启用拖拽功能
        enableDrag: true,
        
        // 是否启用撤销/重做功能
        enableUndo: true,
        
        // 撤销/重做历史记录最大数量
        maxHistory: 30,
        
        // 自动保存间隔（毫秒），0表示不自动保存
        autoSaveInterval: 60000,
        
        // 是否显示操作提示
        showTips: true
    },
    
    // 编辑器尺寸配置
    size: {
        // 编辑器最小高度
        minHeight: '400px',
        
        // 工具栏高度
        toolbarHeight: '40px',
        
        // 属性面板宽度
        propertyPanelWidth: '300px',
        
        // 组件选择器宽度
        componentSelectorWidth: '200px'
    },
    
    // 编辑器语言配置
    language: {
        // 当前语言
        current: 'zh-CN',
        
        // 语言包
        messages: {
            'zh-CN': {
                'editor.loading': '编辑器加载中...',
                'editor.ready': '编辑器已就绪',
                'editor.save.success': '保存成功',
                'editor.save.error': '保存失败',
                'editor.confirm.leave': '内容尚未保存，确定要离开吗？',
                'component.text': '文本',
                'component.image': '图片',
                'component.video': '视频',
                'component.divider': '分隔线',
                'component.button': '按钮',
                'component.gallery': '相册',
                'component.code': '代码',
                'property.general': '基本属性',
                'property.style': '样式属性',
                'property.text': '文本内容',
                'property.url': '链接地址',
                'property.target': '打开方式',
                'property.width': '宽度',
                'property.height': '高度',
                'property.align': '对齐方式',
                'property.color': '文字颜色',
                'property.backgroundColor': '背景颜色',
                'property.fontSize': '字体大小',
                'property.lineHeight': '行高',
                'property.padding': '内边距',
                'property.margin': '外边距',
                'property.border': '边框',
                'property.borderRadius': '圆角'
            },
            'en-US': {
                'editor.loading': 'Editor loading...',
                'editor.ready': 'Editor ready',
                'editor.save.success': 'Save success',
                'editor.save.error': 'Save error',
                'editor.confirm.leave': 'Content not saved, are you sure to leave?',
                'component.text': 'Text',
                'component.image': 'Image',
                'component.video': 'Video',
                'component.divider': 'Divider',
                'component.button': 'Button',
                'component.gallery': 'Gallery',
                'component.code': 'Code',
                'property.general': 'General',
                'property.style': 'Style',
                'property.text': 'Text',
                'property.url': 'URL',
                'property.target': 'Target',
                'property.width': 'Width',
                'property.height': 'Height',
                'property.align': 'Align',
                'property.color': 'Color',
                'property.backgroundColor': 'Background',
                'property.fontSize': 'Font Size',
                'property.lineHeight': 'Line Height',
                'property.padding': 'Padding',
                'property.margin': 'Margin',
                'property.border': 'Border',
                'property.borderRadius': 'Border Radius'
            }
        }
    },
    
    // CMS集成配置
    cmsIntegration: {
        // 保存URL
        saveUrl: '',
        // 保存成功回调
        onSaveSuccess: null,
        // 保存失败回调
        onSaveError: null,
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
            // 自定义上传函数
            customFunction: null
        },
        // 内容预处理函数
        preprocessContent: null,
        // 内容后处理函数
        postprocessContent: null,
        // 预处理选项
        preprocessOptions: {
            // 是否移除编辑器特定的类
            removeEditorClasses: true,
            // 是否移除编辑器特定的属性
            removeEditorAttributes: true,
            // 是否移除draggable属性
            removeDraggableAttr: true,
            // 自定义清理函数
            customCleanup: null
        },
        // 是否允许重写open_upload函数
        allowRewriteUpload: true,
        // 轮询间隔（毫秒）
        pollInterval: 500,
        // 编辑模式切换按钮HTML
        toggleButtonsHTML: '',
        
        // 模板编辑特定配置
        templateEdit: {
            // 模板保存URL
            saveUrl: '../index.php?act=editfile',
            // 模板上传配置
            upload: {
                // 上传URL
                url: '../index.php?act=upload',
                // 是否使用原生上传器
                useNative: true,
                // 上传类型
                type: 'image',
                // 上传数量
                num: '1',
                // 上传模块类型
                stype: 'template',
                // 输入框ID
                inputId: 'temp_template_image',
                // 自定义上传函数
                customFunction: null
            },
            // 模板内容预处理选项
            preprocessOptions: {
                // 是否移除编辑器特定的类
                removeEditorClasses: true,
                // 是否移除编辑器特定的属性
                removeEditorAttributes: true,
                // 是否移除draggable属性
                removeDraggableAttr: true,
                // 是否保留HTML注释
                preserveComments: true,
                // 自定义清理函数
                customCleanup: null
            }
        }
    },
    
    // 获取语言消息
    getMessage: function(key) {
        var lang = this.language.current || 'zh-CN';
        var messages = this.language.messages[lang] || this.language.messages['zh-CN'];
        return messages[key] || key;
    },
    
    // 设置语言
    setLanguage: function(lang) {
        if (this.language.messages[lang]) {
            this.language.current = lang;
        }
    },
    
    // 获取主题颜色
    getThemeColor: function(key) {
        return this.theme[key] || '#333333';
    },
    
    // 设置主题颜色
    setThemeColor: function(key, value) {
        if (this.theme.hasOwnProperty(key)) {
            this.theme[key] = value;
        }
    },
    
    // 添加自定义组件
    addComponent: function(component) {
        if (component && component.name && component.type) {
            this.components.available.push(component);
        }
    },
    
    // 移除组件
    removeComponent: function(componentName) {
        this.components.available = this.components.available.filter(function(component) {
            return component.name !== componentName;
        });
    },
    
    // 获取组件配置
    getComponent: function(componentName) {
        for (var i = 0; i < this.components.available.length; i++) {
            if (this.components.available[i].name === componentName) {
                return this.components.available[i];
            }
        }
        return null;
    }
};

// 导出配置（如果支持模块）
if (typeof module !== 'undefined' && module.exports) {
    module.exports = VisualEditorConfig;
}