/**
 * ZZZCMS 前端可视化编辑器
 * 提供拖拽式内容编辑、所见即所得的编辑体验
 */
;(function(window, $, undefined) {
    // 默认配置
    var defaultConfig = {
        container: '#content',
        mode: 'edit', // 'edit' 或 'view'
        historyEnabled: true,
        autoSave: false,
        autoSaveInterval: 30000, // 30秒
        maxHistory: 30,
        theme: {
            backgroundColor: '#f8f8f8',
            primaryColor: '#337ab7'
        },
        components: {
            available: []
        }
    };
    
    // VisualEditor构造函数
    function VisualEditor(options) {
        // 合并配置
        this.userOptions = options || {};
        this.config = $.extend(true, {}, defaultConfig, 
                           typeof VisualEditorConfig !== 'undefined' ? VisualEditorConfig : {}, 
                           options);
        
        // 初始化属性
        this.container = $(this.config.container);
        this.isEditMode = this.config.mode === 'edit';
        this.history = [];
        this.historyIndex = -1;
        this.lastSaveContent = '';
        this.isDirty = false;
        this.autoSaveInterval = null;
        this.currentImageUploadCallback = null;
        this.events = {};
        this.toolbar = null;
        
        // 初始化编辑器
        this.init();
        
        // 返回实例，支持链式调用
        return this;
    }
    
    // 原型方法
    VisualEditor.prototype = {
        // 初始化编辑器
        init: function() {
            var self = this;
            
            // 验证容器
            if (this.container.length === 0) {
                console.error('VisualEditor: 找不到容器元素', this.config.container);
                return this;
            }
            
            // 初始化编辑模式
            if (this.isEditMode) {
                this.initEditMode();
            }
            
            // 启动自动保存
            if (this.config.autoSave) {
                this.startAutoSave();
            }
            
            // 监听窗口关闭事件，提示未保存的内容
            $(window).on('beforeunload.visual-editor-' + this.getUniqueId(), function(e) {
                if (self.isDirty) {
                    var message = self.getMessage('editor.confirm.leave');
                    e.returnValue = message;
                    return message;
                }
            });
            
            // 触发初始化完成事件
            this.trigger('init', this);
            
            return this;
        },
        
        // 获取唯一ID
        getUniqueId: function() {
            return this._uniqueId || (this._uniqueId = 'visual-editor-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9));
        },
        
        // 生成组件ID
        generateComponentId: function() {
            return 'visual-component-' + Date.now() + '-' + Math.random().toString(36).substr(2, 6);
        },
        
        // 获取消息文本
        getMessage: function(key, defaultValue) {
            defaultValue = defaultValue || '';
            if (this.config.getMessage && typeof this.config.getMessage === 'function') {
                return this.config.getMessage(key) || defaultValue;
            }
            
            // 简单的消息映射
            var messages = {
                'editor.ready': '编辑器已就绪',
                'editor.save.success': '保存成功',
                'editor.save.error': '保存失败，请重试',
                'editor.confirm.leave': '内容尚未保存，确定要离开吗？',
                'property.general': '属性编辑',
                'property.text': '文本样式',
                'property.url': '链接地址',
                'property.target': '打开方式'
            };
            
            return messages[key] || defaultValue;
        },
    
        // 初始化编辑模式
        initEditMode: function() {
        var self = this;
        
        // 创建工具栏
        if (this.config.toolbar && this.config.toolbar.show !== false) {
            this.createToolbar();
        }
        
        // 为内容区域添加可编辑特性
        this.container.attr('contenteditable', 'true').addClass('visual-editor-active');
        
        // 初始化拖拽功能
        if (this.config.behavior && this.config.behavior.enableDrag !== false) {
            this.initDragDrop();
        }
        
        // 绑定点击事件
        this.container.on('click', '*', function(e) {
            e.stopPropagation();
            self.selectElement(this);
        });
        
        // 点击空白区域取消选中
        this.container.on('click', function() {
            self.deselectAll();
        });
        
        // 监听键盘事件
        this.container.on('keydown', function(e) {
            // 处理特殊键组合
            if (e.ctrlKey || e.metaKey) {
                switch (e.key.toLowerCase()) {
                    case 'z':
                        if (e.shiftKey) {
                            self.redo();
                        } else {
                            self.undo();
                        }
                        e.preventDefault();
                        break;
                    case 's':
                        self.saveContent();
                        e.preventDefault();
                        break;
                }
            } else {
                // 处理删除、回车等操作
                self.handleKeyDown(e);
            }
        });
        
        // 监听内容变化
        this.container.on('input', function() {
            self.onContentChange();
        });
        
        // 保存历史记录
        if (this.config.historyEnabled) {
            this.saveHistory();
        }
    },
    
        // 创建工具栏
        createToolbar: function() {
        var self = this;
        var toolbarHtml = '<div class="visual-editor-toolbar" style="background-color: ' + 
            (this.config.theme && this.config.theme.backgroundColor ? this.config.theme.backgroundColor : '#f8f8f8') + ';">' +
            '<div class="toolbar-title">' + (this.config.getMessage ? this.config.getMessage('editor.ready') : '编辑器已就绪') + '</div>' +
            '<div class="toolbar-components">';
        
        // 添加组件按钮
        var components = this.config.components && this.config.components.available ? 
            this.config.components.available : this.config.components;
        
        if (components) {
            $.each(components, function(index, component) {
                if (component.type === 'separator') {
                    toolbarHtml += '<span class="toolbar-separator"></span>';
                } else {
                    toolbarHtml += '<button class="component-btn" data-type="' + component.name + '">' + 
                        '<i class="fa ' + (component.icon || '') + '"></i> ' + 
                        (component.title || component.name) + 
                        '</button>';
                }
            });
        }
        
        toolbarHtml += '</div>' +
            '<div class="toolbar-actions">';
        
        // 添加右侧按钮（撤销/重做/保存等）
        if (this.config.toolbar && this.config.toolbar.rightButtons) {
            $.each(this.config.toolbar.rightButtons, function(index, button) {
                toolbarHtml += '<button class="toolbar-btn ' + (button.name || '') + '-btn" data-action="' + (button.action || button.command) + '">' + 
                    '<i class="fa ' + (button.icon || '') + '"></i> ' + 
                    (button.title || button.name) + 
                    '</button>';
            });
        } else {
            // 默认按钮
            toolbarHtml += '<button class="toolbar-btn undo-btn" data-action="undo">' +
                '<i class="fa fa-undo"></i> 撤销' +
                '</button>' +
                '<button class="toolbar-btn redo-btn" data-action="redo">' +
                '<i class="fa fa-repeat"></i> 重做' +
                '</button>' +
                '<button class="toolbar-btn save-btn" data-action="save">' +
                '<i class="fa fa-save"></i> 保存' +
                '</button>' +
                '<button class="toolbar-btn view-btn" data-action="toggleView">' +
                '<i class="fa fa-eye"></i> 预览' +
                '</button>';
        }
        
        toolbarHtml += '</div>' +
            '</div>';
        
        // 将工具栏添加到页面顶部
        $('body').prepend(toolbarHtml);
        
        // 设置主题颜色
        if (this.config.theme && this.config.theme.primaryColor) {
            $('.component-btn:hover, .toolbar-btn:hover').css('background-color', this.config.theme.hoverColor || '#e8e8e8');
            $('.save-btn').css('background-color', this.config.theme.primaryColor);
        }
        
        // 绑定组件按钮点击事件
        $('.component-btn').on('click', function() {
            var type = $(this).data('type');
            self.insertComponent(type);
        });
        
        // 绑定工具栏动作按钮点击事件
        $('.toolbar-btn').on('click', function() {
            var action = $(this).data('action');
            if (typeof self[action] === 'function') {
                self[action]();
            }
        });
    },
    
        // 插入组件
        insertComponent: function(type) {
        var self = this;
        var componentHtml = '';
        var component = this.config.getComponent ? this.config.getComponent(type) : null;
        
        if (component) {
            if (component.action && typeof self[component.action] === 'function') {
                self[component.action]();
                return;
            } else if (component.template) {
                componentHtml = component.template;
            }
        }
        
        // 如果没有配置组件，使用默认组件
        if (!componentHtml) {
            switch(type) {
                case 'text':
                    componentHtml = '<div class="visual-component text-component" data-type="text">' +
                        '<p>点击编辑文本内容</p>' +
                        '</div>';
                    break;
                case 'image':
                    this.openImageUploader(function(imageUrl) {
                        var defaultProps = self.config.components && self.config.components.defaultProps && self.config.components.defaultProps.image ? 
                            self.config.components.defaultProps.image : {};
                        
                        componentHtml = '<div class="visual-component image-component" data-type="image">' +
                            '<img src="' + imageUrl + '" alt="图片" class="editable-image" style="width: ' + 
                            (defaultProps.width || 'auto') + '; height: ' + (defaultProps.height || 'auto') + ';">' +
                            '<div class="image-caption">' + (defaultProps.caption || '图片描述') + '</div>' +
                            '</div>';
                        self.insertHtml(componentHtml);
                    });
                    return;
                case 'video':
                    var videoUrl = prompt('请输入视频URL：');
                    if (videoUrl) {
                        var defaultProps = self.config.components && self.config.components.defaultProps && self.config.components.defaultProps.video ? 
                            self.config.components.defaultProps.video : {};
                        
                        componentHtml = '<div class="visual-component video-component" data-type="video">' +
                            '<video src="' + videoUrl + '" controls style="width: ' + 
                            (defaultProps.width || '100%') + '; height: ' + (defaultProps.height || '360px') + ';"></video>' +
                            '</div>';
                    }
                    break;
                case 'divider':
                    componentHtml = '<div class="visual-component divider-component" data-type="divider">' +
                        '<hr class="editable-divider">' +
                        '</div>';
                    break;
                case 'button':
                    var defaultProps = self.config.components && self.config.components.defaultProps && self.config.components.defaultProps.button ? 
                        self.config.components.defaultProps.button : {};
                    
                    componentHtml = '<div class="visual-component button-component" data-type="button">' +
                        '<button class="editable-button btn btn-' + (defaultProps.type || 'primary') + ' btn-' + 
                        (defaultProps.size || 'default') + '" onclick="window.open(\'' + 
                        (defaultProps.url || '#') + '\',\'' + (defaultProps.target || '_self') + '\')">' + 
                        (defaultProps.text || '按钮') + '</button>' +
                        '</div>';
                    break;
                case 'gallery':
                    this.insertGallery();
                    return;
                case 'code':
                    componentHtml = '<div class="visual-component code-component" data-type="code">' +
                        '<pre><code>点击编辑代码内容</code></pre>' +
                        '</div>';
                    break;
            }
        }
        
        if (componentHtml) {
            this.insertHtml(componentHtml);
        }
    },
    
        // 插入HTML到当前光标位置
        insertHtml: function(html) {
        var range, node;
        if (window.getSelection && window.getSelection().getRangeAt) {
            range = window.getSelection().getRangeAt(0);
            node = range.createContextualFragment(html);
            range.insertNode(node);
            // 使用标准的collapse方法，参数true表示折叠到范围末尾，这样光标会在插入内容之后
            range.collapse(true);
            
            var selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
        } else if (document.selection && document.selection.createRange) {
            document.selection.createRange().pasteHTML(html);
        }
        
        // 触发内容变化事件
        this.onContentChange();
        
        return this;
    },
    
        // 选择元素
        selectElement: function(element) {
        this.deselectAll();
        $(element).addClass('visual-component-selected');
        
        // 创建属性编辑面板
        this.createPropertyPanel(element);
        
        return this;
    },
    
        // 取消所有选中
        deselectAll: function() {
        $('.visual-component-selected').removeClass('visual-component-selected');
        $('.visual-property-panel').remove();
        
        return this;
    },
    
        // 事件处理系统
        on: function(event, callback) {
            if (!this.events[event]) {
                this.events[event] = [];
            }
            this.events[event].push(callback);
            return this;
        },
        
        off: function(event, callback) {
            if (event && this.events[event]) {
                if (callback) {
                    this.events[event] = this.events[event].filter(function(cb) {
                        return cb !== callback;
                    });
                } else {
                    delete this.events[event];
                }
            }
            return this;
        },
        
        trigger: function(event, data) {
            if (this.events[event]) {
                this.events[event].forEach(function(callback) {
                    try {
                        callback(data);
                    } catch (e) {
                        console.error('VisualEditor event handler error:', e);
                    }
                });
            }
            return this;
        },
        
        // 创建属性编辑面板
        createPropertyPanel: function(element) {
        var self = this;
        var $element = $(element);
        var type = $element.data('type') || $element.attr('data-type');
        
        var panelHtml = '<div class="visual-property-panel">' +
            '<div class="panel-title">' + (this.config.getMessage ? this.config.getMessage('property.general') : '属性编辑') + '</div>' +
            '<div class="panel-content">';
        
        // 根据元素类型添加不同的属性编辑器
        switch(type) {
            case 'text':
                panelHtml += '<div class="property-group">' +
                    '<label>' + (this.config.getMessage ? this.config.getMessage('property.text') : '文本样式') + '</label>' +
                    '<select class="text-style">' +
                    '<option value="p">段落</option>' +
                    '<option value="h1">标题1</option>' +
                    '<option value="h2">标题2</option>' +
                    '<option value="h3">标题3</option>' +
                    '<option value="blockquote">引用</option>' +
                    '</select>' +
                    '</div>';
                break;
            case 'image':
                var imgSrc = $element.find('img').attr('src');
                var caption = $element.find('.image-caption').text();
                
                panelHtml += '<div class="property-group">' +
                    '<label>' + (this.config.getMessage ? this.config.getMessage('property.url') : '图片URL') + '</label>' +
                    '<input type="text" class="image-url" value="' + imgSrc + '">' +
                    '</div>' +
                    '<div class="property-group">' +
                    '<label>' + (this.config.getMessage ? this.config.getMessage('property.text') : '图片描述') + '</label>' +
                    '<input type="text" class="image-caption" value="' + caption + '">' +
                    '</div>';
                break;
            case 'button':
                var btnText = $element.find('button').text();
                var btnUrl = $element.find('button').attr('onclick').match(/\'([^\']+)\'/)[1];
                var btnTarget = $element.find('button').attr('onclick').match(/\'([^\']+)\'\s*,\s*\'([^\']+)\'/)[2];
                var btnClass = $element.find('button').attr('class');
                var btnType = 'primary';
                var btnSize = 'default';
                
                if (btnClass.match(/btn-(success|info|warning|danger)/)) {
                    btnType = btnClass.match(/btn-(success|info|warning|danger)/)[1];
                }
                
                if (btnClass.match(/btn-(lg|sm|xs)/)) {
                    btnSize = btnClass.match(/btn-(lg|sm|xs)/)[1];
                }
                
                panelHtml += '<div class="property-group">' +
                    '<label>' + this.getMessage('property.text') + '</label>' +
                    '<input type="text" class="button-text" value="' + btnText + '">' +
                    '</div>' +
                    '<div class="property-group">' +
                    '<label>' + (this.config.getMessage ? this.config.getMessage('property.url') : '链接地址') + '</label>' +
                    '<input type="text" class="button-url" value="' + btnUrl + '">' +
                    '</div>' +
                    '<div class="property-group">' +
                    '<label>' + (this.config.getMessage ? this.config.getMessage('property.target') : '打开方式') + '</label>' +
                    '<select class="button-target">' +
                    '<option value="_self"' + (btnTarget === '_self' ? ' selected' : '') + '>当前窗口</option>' +
                    '<option value="_blank"' + (btnTarget === '_blank' ? ' selected' : '') + '>新窗口</option>' +
                    '</select>' +
                    '</div>' +
                    '<div class="property-group">' +
                    '<label>按钮类型</label>' +
                    '<select class="button-type">' +
                    '<option value="primary"' + (btnType === 'primary' ? ' selected' : '') + '>主要</option>' +
                    '<option value="success"' + (btnType === 'success' ? ' selected' : '') + '>成功</option>' +
                    '<option value="info"' + (btnType === 'info' ? ' selected' : '') + '>信息</option>' +
                    '<option value="warning"' + (btnType === 'warning' ? ' selected' : '') + '>警告</option>' +
                    '<option value="danger"' + (btnType === 'danger' ? ' selected' : '') + '>危险</option>' +
                    '</select>' +
                    '</div>' +
                    '<div class="property-group">' +
                    '<label>按钮大小</label>' +
                    '<select class="button-size">' +
                    '<option value="default"' + (btnSize === 'default' ? ' selected' : '') + '>默认</option>' +
                    '<option value="lg"' + (btnSize === 'lg' ? ' selected' : '') + '>大型</option>' +
                    '<option value="sm"' + (btnSize === 'sm' ? ' selected' : '') + '>小型</option>' +
                    '<option value="xs"' + (btnSize === 'xs' ? ' selected' : '') + '>超小</option>' +
                    '</select>' +
                    '</div>';
                break;
        }
        
        panelHtml += '</div>' +
            '</div>';
        
        // 将属性面板添加到页面
        $('body').append(panelHtml);
        
        // 绑定属性变更事件
        $('.visual-property-panel input, .visual-property-panel select').on('change', function() {
            self.updateElementProperties(element);
        });
    },
    
        // 更新元素属性
        updateElementProperties: function(element) {
        var $element = $(element);
        var type = $element.data('type') || $element.attr('data-type');
        
        switch(type) {
            case 'text':
                var style = $('.text-style').val();
                var content = $element.html();
                $element.replaceWith('<div class="visual-component text-component" data-type="text">' +
                    '<' + style + '>' + content + '</' + style + '>' +
                    '</div>');
                break;
            case 'image':
                var imageUrl = $('.image-url').val();
                var caption = $('.image-caption').val();
                $element.find('img').attr('src', imageUrl);
                $element.find('.image-caption').text(caption);
                break;
            case 'button':
                var btnText = $('.button-text').val();
                var btnUrl = $('.button-url').val();
                var btnTarget = $('.button-target').val();
                var btnType = $('.button-type').val();
                var btnSize = $('.button-size').val();
                
                $element.find('button').text(btnText);
                $element.find('button').attr('onclick', 'window.open(\'' + btnUrl + '\',\'' + btnTarget + '\')');
                $element.find('button').removeClass('btn-primary btn-success btn-info btn-warning btn-danger btn-lg btn-sm btn-xs').addClass('btn-' + btnType + (btnSize !== 'default' ? ' btn-' + btnSize : ''));
                break;
        }
        
        // 触发内容变化事件
        this.onContentChange();
    },
    
        // 初始化拖拽功能
        initDragDrop: function() {
        var self = this;
        
        // 使用HTML5拖拽API
        $('.visual-component').attr('draggable', 'true');
        
        this.container.on('dragstart', '.visual-component', function(e) {
            e.originalEvent.dataTransfer.setData('text/plain', $(this).attr('data-id') || 'visual-component');
            $(this).addClass('dragging');
        });
        
        this.container.on('dragend', '.visual-component', function() {
            $(this).removeClass('dragging');
        });
        
        this.container.on('dragover', function(e) {
            e.preventDefault();
        });
        
        this.container.on('drop', function(e) {
            e.preventDefault();
            // 实现拖拽放置逻辑（简化版）
        });
    },
    
        // 处理键盘事件
        handleKeyDown: function(e) {
        // 处理删除、回车等操作
        // 可以根据需要扩展
    },
    
        // 打开图片上传器
        openImageUploader: function(callback) {
        // 保存回调函数，供扩展使用
        this.currentImageUploadCallback = callback;
        
        // 这里可以调用CMS现有的图片上传功能
        if (typeof open_upload === 'function') {
            open_upload('image', '1', 'content', 'temp_image');
            
            // 监听临时输入框变化
            var interval = setInterval(function() {
                var imageUrl = $('#temp_image').val();
                if (imageUrl) {
                    clearInterval(interval);
                    callback(imageUrl);
                    $('#temp_image').val('');
                }
            }, 500);
        } else {
            // 简化实现
            var imageUrl = prompt('请输入图片URL：');
            if (imageUrl) {
                callback(imageUrl);
            }
        }
    },
    
        // 插入相册
        insertGallery: function() {
        var self = this;
        
        // 简化实现，实际应该打开多图上传界面
        var images = [];
        var count = prompt('请输入相册图片数量：', 3);
        
        if (count && !isNaN(count)) {
            for (var i = 0; i < count; i++) {
                var imageUrl = prompt('请输入图片URL ' + (i + 1) + '：');
                if (imageUrl) {
                    images.push({url: imageUrl});
                }
            }
            
            if (images.length > 0) {
                var galleryHtml = '<div class="visual-component gallery-component" data-type="gallery">' +
                    '<div class="gallery-container">';
                
                $.each(images, function(index, image) {
                    galleryHtml += '<div class="gallery-item"><img src="' + image.url + '" alt="相册图片"></div>';
                });
                
                galleryHtml += '</div></div>';
                self.insertHtml(galleryHtml);
            }
        }
    },
    
        // 保存内容
        saveContent: function() {
        var content = this.container.html();
        
        // 预处理内容
        if (this.config.cms && this.config.cms.preprocessContent && typeof this.config.cms.preprocessContent === 'function') {
            content = this.config.cms.preprocessContent(content);
        }
        // 调用保存回调
        if (typeof this.config.onSave === 'function') {
            this.config.onSave(content);
        } else {
            // 默认保存逻辑
            alert(this.config.getMessage ? this.config.getMessage('editor.save.success') : '内容已保存！');
        }
        
        return this;
        
        // 更新保存状态
        this.isDirty = false;
        this.lastSaveContent = content;
        
        // 更新工具栏标题
        $('.toolbar-title').text(this.config.getMessage ? this.config.getMessage('editor.save.success') : '保存成功');
        setTimeout(function() {
            $('.toolbar-title').text(VisualEditor.config.getMessage ? VisualEditor.config.getMessage('editor.ready') : '编辑器已就绪');
        }, 2000);
    },
    
        // 切换到预览模式
        toggleView: function() {
            try {
                if (this.isEditMode) {
                    this.container.attr('contenteditable', 'false').removeClass('visual-editor-active');
                    $('.visual-editor-toolbar').addClass('preview-mode');
                    $('.view-btn i').removeClass('fa-eye').addClass('fa-pencil');
                    $('.view-btn').text(' 编辑');
                } else {
                    this.container.attr('contenteditable', 'true').addClass('visual-editor-active');
                    $('.visual-editor-toolbar').removeClass('preview-mode');
                    $('.view-btn i').removeClass('fa-pencil').addClass('fa-eye');
                    $('.view-btn').text(' 预览');
                }
                
                this.isEditMode = !this.isEditMode;
                return this; // 支持链式调用
            } catch (error) {
                console.error('VisualEditor: toggleView failed:', error);
                return this; // 即使出错也返回this以支持链式调用
            }
        },
        
        // 撤销
        undo: function() {
            try {
                if (this.config.historyEnabled && this.historyIndex > 0) {
                    this.historyIndex--;
                    this.container.html(this.history[this.historyIndex]);
                }
                return this; // 支持链式调用
            } catch (error) {
                console.error('VisualEditor: undo failed:', error);
                return this; // 即使出错也返回this以支持链式调用
            }
        },
        
        // 重做
        redo: function() {
            try {
                if (this.config.historyEnabled && this.historyIndex < this.history.length - 1) {
                    this.historyIndex++;
                    this.container.html(this.history[this.historyIndex]);
                }
                return this; // 支持链式调用
            } catch (error) {
                console.error('VisualEditor: redo failed:', error);
                return this; // 即使出错也返回this以支持链式调用
            }
        },
    
        // 触发内容变化事件
        onContentChange: function() {
        // 标记内容已修改
        this.isDirty = true;
        
        // 保存历史记录
        if (this.config.historyEnabled) {
            this.saveHistory();
        }
        
        // 调用内容变化回调
        if (typeof this.config.onContentChange === 'function') {
            this.config.onContentChange(this.container.html());
        }
    },
    
        // 保存历史记录
        saveHistory: function() {
        var content = this.container.html();
        
        // 如果内容与上次相同，不保存
        if (this.history.length > 0 && this.history[this.historyIndex] === content) {
            return;
        }
        
        // 移除当前索引之后的历史记录
        if (this.historyIndex < this.history.length - 1) {
            this.history = this.history.slice(0, this.historyIndex + 1);
        }
        
        // 添加新的历史记录
        this.history.push(content);
        this.historyIndex = this.history.length - 1;
        
        // 限制历史记录数量
        var maxHistory = this.config.behavior && this.config.behavior.maxHistory ? 
            this.config.behavior.maxHistory : 30;
        
        if (this.history.length > maxHistory) {
            this.history.shift();
            this.historyIndex--;
        }
    },
    
        // 撤销
        undo: function() {
        if (this.config.historyEnabled && this.historyIndex > 0) {
            this.historyIndex--;
            this.container.html(this.history[this.historyIndex]);
        }
        return this; // 支持链式调用
    },
    
        // 重做
        redo: function() {
        if (this.config.historyEnabled && this.historyIndex < this.history.length - 1) {
            this.historyIndex++;
            this.container.html(this.history[this.historyIndex]);
        }
        return this; // 支持链式调用
    },
    
        // 开始自动保存
        startAutoSave: function() {
        var self = this;
        this.autoSaveInterval = setInterval(function() {
            if (self.isDirty) {
                // 这里可以实现自动保存到本地存储
                localStorage.setItem('visual-editor-auto-save', self.container.html());
            }
        }, this.config.autoSaveInterval);
    },
    
        // 停止自动保存
        stopAutoSave: function() {
        if (this.autoSaveInterval) {
            clearInterval(this.autoSaveInterval);
        }
    },
    
        // 获取编辑器内容
        getContent: function() {
        var content = this.container.html();
        
        // 预处理内容
        if (this.config.cms && this.config.cms.preprocessContent && typeof this.config.cms.preprocessContent === 'function') {
            content = this.config.cms.preprocessContent(content);
        }
        
        return content;
    },
    
        // 设置编辑器内容
        setContent: function(content) {
        // 后处理内容
        if (this.config.cms && this.config.cms.postprocessContent && typeof this.config.cms.postprocessContent === 'function') {
            content = this.config.cms.postprocessContent(content);
        }
        
        this.container.html(content);
        this.onContentChange();
        
        return this;
        
        // 保存历史记录
        if (this.config.historyEnabled) {
            this.saveHistory();
        }
    },
    
        // 销毁编辑器
        destroy: function() {
        // 停止自动保存
        this.stopAutoSave();
        
        // 移除事件监听
        $(window).off('beforeunload');
        
        // 移除工具栏和属性面板
        $('.visual-editor-toolbar, .visual-property-panel').remove();
        
        // 清理内容区域
        this.container.attr('contenteditable', 'false').removeClass('visual-editor-active');
        
        // 重置属性
        this.isEditMode = false;
        this.isDirty = false;
    }
    };
    
    // 创建编辑器实例的工厂方法
    VisualEditor.create = function(options) {
        return new VisualEditor(options);
    };
    
    // 导出到全局
    window.VisualEditor = VisualEditor;
})(window, jQuery);

// 初始化可视化编辑器
$(document).ready(function() {
    // 检查是否在编辑页面且存在#content元素
    if ($('#content').length > 0 && window.location.pathname.indexOf('qiye126nethoutai') > -1) {
        // 初始化可视化编辑器
        VisualEditor.create({
            container: '#content',
            onSave: function(content) {
                // 调用CMS的保存接口
                $.ajax({
                    url: 'save.php?act=content',
                    type: 'POST',
                    data: {
                        c_content: content,
                        cid: $('#cid').val(),
                        c_sid: $('select[name="c_sid"]').val(),
                        c_title: $('#title').val(),
                        // 添加其他需要的字段
                        token: $('input[name="token"]').val()
                    },
                    success: function(response) {
                        // 处理保存成功
                        var editor = this;
                        alert(editor.getMessage('editor.save.success'));
                    },
                    error: function() {
                        // 处理保存失败
                        var editor = this;
                        alert(editor.getMessage('editor.save.error'));
                    }
                });
            }
        });
    }
});