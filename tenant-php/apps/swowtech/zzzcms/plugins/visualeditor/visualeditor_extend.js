/**
 * ZZZCMS 可视化编辑器扩展功能
 * 补充基础编辑器的功能，适配CMS现有功能
 */

// 等待页面加载完成
$(document).ready(function() {
    // 扩展VisualEditor对象
    $.extend(VisualEditor, {
        // 初始化CMS特定功能
        initCMSFeatures: function() {
            this.setupImageUpload();
            this.setupContentSave();
            this.setupVisualModeToggle();
        },
        
        // 设置图片上传功能
        setupImageUpload: function() {
            var self = this;
            
            // 监听CMS原生图片上传完成事件
            if (typeof window.uploadCallback === 'undefined') {
                window.uploadCallback = function() {
                    // 图片上传完成后，通知可视化编辑器
                    setTimeout(function() {
                        if ($('#temp_image').val()) {
                            if (self.currentImageUploadCallback) {
                                self.currentImageUploadCallback($('#temp_image').val());
                                $('#temp_image').val('');
                                self.currentImageUploadCallback = null;
                            }
                        }
                    }, 500);
                };
            }
        },
        
        // 设置内容保存功能
        setupContentSave: function() {
            var self = this;
            
            // 覆盖默认的保存方法
            VisualEditor.saveContent = function() {
                var content = self.container.html();
                
                // 检测是否在模板编辑页面
                var isTemplateEdit = window.location.href.indexOf('act=templateedit') !== -1;
                
                // 获取配置中的内容预处理函数
                var preprocessFunction = VisualEditorConfig && VisualEditorConfig.cmsIntegration && typeof VisualEditorConfig.cmsIntegration.preprocessContent === 'function' ? 
                    VisualEditorConfig.cmsIntegration.preprocessContent : self.preprocessContent;
                
                // 保存前处理
                content = preprocessFunction(content, isTemplateEdit);
                
                // 填充表单
                if (isTemplateEdit) {
                    // 在模板编辑页面，更新CodeMirror文本框
                    if ($('#CodeMirror').length > 0) {
                        $('#CodeMirror').val(content);
                    }
                } else {
                    // 在内容编辑页面，更新常规输入框
                    $('#content').val(content);
                    
                    // 检查是否有UEditor实例，如果有则更新
                    if (typeof UE !== 'undefined' && typeof $ZZZEditor !== 'undefined') {
                        $ZZZEditor.setContent(content);
                    }
                }
                
                // 获取保存URL，优先使用模板编辑特定的URL
                var saveConfig = isTemplateEdit && VisualEditorConfig && VisualEditorConfig.cmsIntegration && VisualEditorConfig.cmsIntegration.templateEdit ? 
                    VisualEditorConfig.cmsIntegration.templateEdit : 
                    (VisualEditorConfig && VisualEditorConfig.cmsIntegration || {});
                
                var saveUrl = saveConfig.saveUrl || '';
                
                if (typeof self.options.onSave === 'function') {
                    self.options.onSave(content);
                } else if (saveUrl) {
                    // 如果配置了保存URL，使用AJAX保存
                    var formData = isTemplateEdit ? 
                        { filepath: $('input[name="filepath"]').val(), filecontent: content } : 
                        { content: content };
                    
                    $.ajax({
                        url: saveUrl,
                        type: 'POST',
                        data: formData,
                        success: function(response) {
                            // 处理保存成功
                            if (saveConfig.onSaveSuccess && typeof saveConfig.onSaveSuccess === 'function') {
                                saveConfig.onSaveSuccess(response);
                            } else if (VisualEditorConfig && VisualEditorConfig.cmsIntegration && typeof VisualEditorConfig.cmsIntegration.onSaveSuccess === 'function') {
                                VisualEditorConfig.cmsIntegration.onSaveSuccess(response);
                            } else {
                                alert('保存成功');
                            }
                        },
                        error: function(xhr) {
                            // 处理保存失败
                            if (saveConfig.onSaveError && typeof saveConfig.onSaveError === 'function') {
                                saveConfig.onSaveError(xhr);
                            } else if (VisualEditorConfig && VisualEditorConfig.cmsIntegration && typeof VisualEditorConfig.cmsIntegration.onSaveError === 'function') {
                                VisualEditorConfig.cmsIntegration.onSaveError(xhr);
                            } else {
                                alert('保存失败');
                            }
                        }
                    });
                } else {
                    // 触发原表单的保存
                    $('#contentform').submit();
                }
            };
        },
        
        // 设置可视化模式切换
        setupVisualModeToggle: function() {
            var self = this;
            
            // 添加可视化编辑模式切换按钮
            if ($('.visual-editor-toggle').length === 0) {
                // 从配置中获取编辑模式切换按钮的HTML
                var toggleButtonsHTML = VisualEditorConfig && VisualEditorConfig.cmsIntegration && VisualEditorConfig.cmsIntegration.toggleButtonsHTML || 
                    '<div class="form-group">' +
                    '    <label class="col-sm-1 control-label">编辑模式</label>' +
                    '    <div class="col-sm-11">' +
                    '        <div class="btn-group">' +
                    '            <button type="button" class="btn btn-default visual-editor-toggle" data-mode="visual">可视化编辑</button>' +
                    '            <button type="button" class="btn btn-default ueditor-toggle" data-mode="ueditor">源码编辑</button>' +
                    '        </div>' +
                    '    </div>' +
                    '</div>';
                
                $('#contentform .ibox-content').prepend(toggleButtonsHTML);
                
                // 绑定切换按钮事件
                $('.visual-editor-toggle').on('click', function() {
                    self.switchToVisualMode();
                });
                
                $('.ueditor-toggle').on('click', function() {
                    self.switchToUEditorMode();
                });
            }
        },
        
        // 切换到可视化编辑模式
        switchToVisualMode: function() {
            if (typeof UE !== 'undefined' && typeof $ZZZEditor !== 'undefined') {
                // 先获取UEditor的内容
                var content = $ZZZEditor.getContent();
                
                // 销毁UEditor
                $ZZZEditor.destroy();
                
                // 填充内容到可视化编辑器
                this.container.html(content);
                
                // 初始化可视化编辑器
                this.initEditMode();
            }
            
            // 更新按钮状态
            $('.visual-editor-toggle').addClass('active');
            $('.ueditor-toggle').removeClass('active');
        },
        
        // 切换到UEditor编辑模式
        switchToUEditorMode: function() {
            // 先获取可视化编辑器的内容
            var content = this.container.html();
            
            // 清理可视化编辑器相关元素
            $('.visual-editor-toolbar, .visual-property-panel').remove();
            this.container.removeClass('visual-editor-active');
            this.container.attr('contenteditable', 'false');
            
            // 重新初始化UEditor
            if (typeof UE !== 'undefined') {
                $ZZZEditor = new UE.getEditor('content', '');
                $ZZZEditor.ready(function() {
                    $ZZZEditor.setContent(content);
                });
            }
            
            // 更新按钮状态
            $('.visual-editor-toggle').removeClass('active');
            $('.ueditor-toggle').addClass('active');
        },
        
        // 内容预处理
        preprocessContent: function(content, isTemplateEdit) {
            // 检测是否在模板编辑页面（如果未传入参数）
            if (typeof isTemplateEdit === 'undefined') {
                isTemplateEdit = window.location.href.indexOf('act=templateedit') !== -1;
            }
            
            // 获取配置中的预处理选项，优先使用模板编辑特定的选项
            var preprocessOptions;
            if (isTemplateEdit && VisualEditorConfig && VisualEditorConfig.cmsIntegration && VisualEditorConfig.cmsIntegration.templateEdit) {
                preprocessOptions = VisualEditorConfig.cmsIntegration.templateEdit.preprocessOptions || {};
            } else {
                preprocessOptions = VisualEditorConfig && VisualEditorConfig.cmsIntegration && VisualEditorConfig.cmsIntegration.preprocessOptions || {};
            }
            
            var removeEditorClasses = typeof preprocessOptions.removeEditorClasses !== 'undefined' ? preprocessOptions.removeEditorClasses : true;
            var removeEditorAttributes = typeof preprocessOptions.removeEditorAttributes !== 'undefined' ? preprocessOptions.removeEditorAttributes : true;
            var removeDraggableAttr = typeof preprocessOptions.removeDraggableAttr !== 'undefined' ? preprocessOptions.removeDraggableAttr : true;
            var preserveComments = typeof preprocessOptions.preserveComments !== 'undefined' ? preprocessOptions.preserveComments : false;
            
            // 保存HTML注释（如果需要）
            var comments = [];
            var commentCount = 0;
            var contentWithPlaceholders = content;
            
            if (preserveComments) {
                // 保存注释到数组，并替换为占位符
                contentWithPlaceholders = content.replace(/<!--([\s\S]*?)-->/g, function(match) {
                    var placeholder = '<!--COMMENT_PLACEHOLDER_' + (commentCount++) + '-->';
                    comments.push({ placeholder: placeholder, content: match });
                    return placeholder;
                });
            }
            
            // 清理编辑器特定的样式和类
            var temp = $('<div>' + contentWithPlaceholders + '</div>');
            
            // 移除可视化编辑器特定的类和属性
            if (removeEditorClasses || removeEditorAttributes) {
                temp.find('.visual-component, .visual-component-selected').each(function() {
                    if (removeEditorClasses) {
                        $(this).removeAttr('class');
                    }
                    if (removeEditorAttributes) {
                        $(this).removeAttr('data-type').removeAttr('data-id');
                    }
                });
            }
            
            // 移除其他不必要的属性
            if (removeDraggableAttr) {
                temp.find('[draggable]').removeAttr('draggable');
            }
            
            // 应用配置中的自定义清理函数
            if (typeof preprocessOptions.customCleanup === 'function') {
                temp = preprocessOptions.customCleanup(temp);
            }
            
            // 获取处理后的内容
            var processedContent = temp.html();
            
            // 恢复HTML注释（如果需要）
            if (preserveComments) {
                for (var i = 0; i < comments.length; i++) {
                    processedContent = processedContent.replace(comments[i].placeholder, comments[i].content);
                }
            }
            
            // 返回处理后的内容
            return processedContent;
        },
        
        // 打开图片上传器（适配CMS现有功能）
        openImageUploader: function(callback) {
            var self = this;
            
            // 保存回调函数
            self.currentImageUploadCallback = callback;
            
            // 检测是否在模板编辑页面
            var isTemplateEdit = window.location.href.indexOf('act=templateedit') !== -1;
            
            // 获取配置中的上传设置，优先使用模板编辑特定的上传设置
            var uploadConfig;
            if (isTemplateEdit && VisualEditorConfig && VisualEditorConfig.cmsIntegration && VisualEditorConfig.cmsIntegration.templateEdit) {
                uploadConfig = VisualEditorConfig.cmsIntegration.templateEdit.upload || {};
            } else {
                uploadConfig = VisualEditorConfig && VisualEditorConfig.cmsIntegration && VisualEditorConfig.cmsIntegration.upload || {};
            }
            
            var uploadUrl = uploadConfig.url || '';
            var useNativeUploader = typeof uploadConfig.useNative !== 'undefined' ? uploadConfig.useNative : true;
            
            // 如果配置了自定义上传函数，则使用它
            if (typeof uploadConfig.customFunction === 'function') {
                uploadConfig.customFunction(callback);
            } 
            // 调用CMS现有的图片上传功能
            else if (useNativeUploader && typeof open_upload === 'function') {
                // 创建临时输入框
                var uploadInputId = uploadConfig.inputId || (isTemplateEdit ? 'temp_template_image' : 'temp_image');
                if ($('#' + uploadInputId).length === 0) {
                    $('body').append('<input type="hidden" id="' + uploadInputId + '">');
                }
                
                // 打开上传窗口，使用配置中的参数或默认值
                var uploadType = uploadConfig.type || 'image';
                var uploadNum = uploadConfig.num || '1';
                var uploadStype = uploadConfig.stype || (isTemplateEdit ? 'template' : 'content');
                
                open_upload(uploadType, uploadNum, uploadStype, uploadInputId);
            }
            // 使用AJAX上传（如果配置了上传URL）
            else if (uploadUrl) {
                // 这里可以实现一个自定义的AJAX上传对话框
                // 为简化起见，我们仍然使用prompt方式
                var imageUrl = prompt('请输入图片URL：');
                if (imageUrl) {
                    callback(imageUrl);
                }
            }
            else {
                // 如果没有现成的上传函数，使用简化的方式
                var imageUrl = prompt('请输入图片URL：');
                if (imageUrl) {
                    callback(imageUrl);
                }
            }
        }
    });
    
    // 扩展原生表单提交功能
    $('#contentform').submit(function(e) {
        // 检查是否在可视化编辑模式
        if ($('#content').hasClass('visual-editor-active')) {
            // 获取可视化编辑器的内容
            var content = $('#content').html();
            
            // 预处理内容
            content = VisualEditor.preprocessContent(content);
            
            // 更新表单字段
            $('#content').val(content);
        }
        
        // 继续提交表单
        return true;
    });
    
    // 初始化CMS特定功能
    if (typeof VisualEditor !== 'undefined') {
        VisualEditor.initCMSFeatures();
    }
});

// 重写原有的open_upload函数，增加回调支持
// 检查配置是否允许重写open_upload函数
var allowRewriteUpload = VisualEditorConfig && typeof VisualEditorConfig.cmsIntegration !== 'undefined' && 
    typeof VisualEditorConfig.cmsIntegration.allowRewriteUpload !== 'undefined' ? 
    VisualEditorConfig.cmsIntegration.allowRewriteUpload : true;
    
if (allowRewriteUpload && typeof window.original_open_upload === 'undefined' && typeof window.open_upload !== 'undefined') {
    window.original_open_upload = window.open_upload;
    
    window.open_upload = function(type, num, stype, inputid) {
        // 获取配置中的轮询间隔
        var pollInterval = VisualEditorConfig && VisualEditorConfig.cmsIntegration && VisualEditorConfig.cmsIntegration.pollInterval || 500;
        
        // 调用原始函数
        original_open_upload(type, num, stype, inputid);
        
        // 添加监听
        if (inputid && window.uploadCallback) {
            var interval = setInterval(function() {
                var input = $('#' + inputid);
                if (input.length > 0 && input.val()) {
                    clearInterval(interval);
                    // 触发回调
                    if (typeof window.uploadCallback === 'function') {
                        window.uploadCallback();
                    }
                }
            }, pollInterval);
        }
    };
}