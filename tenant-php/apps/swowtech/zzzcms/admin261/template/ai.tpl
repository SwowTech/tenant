<!doctype html public "-//w3c//dtd html 4.01 transitional//en" "http://www.w3c.org/tr/1999/rec-html401-19991224/loose.dtd">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AI助手</title>
  <link href="../plugins/bootstrap/bootstrap.min.css" rel="stylesheet">
  <link href="../plugins/bootstrap/font-awesome.min.css?t=20230419" rel="stylesheet">
  <link href="../plugins/bootstrap/animate.min.css" rel="stylesheet">
  <link href="../plugins/bootstrap/style.min.css" rel="stylesheet">
  <link href="css/adminstyle.css" rel="stylesheet">
  <link href="../plugins/checkbox/checkbox.css" rel="stylesheet">
  <script src="../js/jquery.min.js"></script>
  <style>
    /* Markdown 基础样式 */
    .markdown-container {
      max-width: 800px;
      padding: 2rem 1.5rem;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
      line-height: 1.7;
      color: #333;
      background-color: #fff;
    }

    /* 标题样式 */
    .markdown-container h1,
    .markdown-container h2,
    .markdown-container h3,
    .markdown-container h4,
    .markdown-container h5,
    .markdown-container h6 {
      margin-top: 2rem;
      margin-bottom: 1rem;
      font-weight: 600;
      color: #2d3748;
      line-height: 1.3;
    }

    .markdown-container h1 {
      font-size: 2rem;
      border-bottom: 1px solid #e2e8f0;
      padding-bottom: 0.5rem;
    }

    .markdown-container h2 {
      font-size: 1.5rem;
      border-bottom: 1px solid #e2e8f0;
      padding-bottom: 0.5rem;
    }

    .markdown-container h3 {
      font-size: 1.25rem;
    }

    /* 段落样式 */
    .markdown-container p {
      margin-bottom: 1.2rem;
    }

    /* 链接样式 */
    .markdown-container a {
      color: #3182ce;
      text-decoration: none;
      transition: color 0.2s ease;
    }

    .markdown-container a:hover {
      color: #2c5282;
      text-decoration: underline;
    }

    .markdown-container .anchor-link {
      color: #cbd5e0;
      margin-left: 0.5rem;
    }

    .markdown-container h1:hover .anchor-link,
    .markdown-container h2:hover .anchor-link,
    .markdown-container h3:hover .anchor-link {
      color: #718096;
    }

    /* 列表样式 */
    .markdown-container ul,
    .markdown-container ol {
      margin-bottom: 1.2rem;
      padding-left: 1.8rem;
    }

    .markdown-container ul {
      list-style-type: disc;
    }

    .markdown-container ol {
      list-style-type: decimal;
    }

    .markdown-container li {
      margin-bottom: 0.5rem;
    }

    .markdown-container li p {
      margin-bottom: 0.5rem;
    }

    /* 任务列表 */
    .markdown-container .task-list {
      list-style-type: none;
      padding-left: 1.5rem;
    }

    .markdown-container .task-list li {
      position: relative;
      padding-left: 1.8rem;
    }

    .markdown-container .task-list input[type="checkbox"] {
      position: absolute;
      left: 0;
      top: 0.3rem;
      width: 1rem;
      height: 1rem;
      border-radius: 3px;
      background-color: #f7fafc;
      border: 1px solid #cbd5e0;
      cursor: default;
    }

    /* 代码块样式 */
    .markdown-container pre {
      margin-bottom: 1.5rem;
      padding: 1rem;
      border-radius: 6px;
      background-color: #f7fafc;
      overflow-x: auto;
      font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
    }

    .markdown-container code {
      padding: 0.2rem 0.4rem;
      border-radius: 3px;
      background-color: #f7fafc;
      font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
      font-size: 0.9rem;
    }

    .markdown-container pre code {
      padding: 0;
      background: transparent;
      font-size: 0.875rem;
    }

    /* 引用块样式 */
    .markdown-container blockquote {
      margin-bottom: 1.5rem;
      padding: 1rem 1.5rem;
      border-left: 4px solid #e2e8f0;
      background-color: #f7fafc;
      color: #718096;
      border-radius: 0 4px 4px 0;
    }

    .markdown-container blockquote p {
      margin-bottom: 0;
    }

    /* 表格样式 */
    .markdown-container .markdown-table {
      width: 100%;
      margin-bottom: 1.5rem;
      border-collapse: collapse;
      border-spacing: 0;
    }

    .markdown-container .markdown-table th,
    .markdown-container .markdown-table td {
      padding: 0.75rem 1rem;
      border: 1px solid #e2e8f0;
      text-align: left;
    }

    .markdown-container .markdown-table th {
      background-color: #f7fafc;
      font-weight: 600;
    }

    .markdown-container .markdown-table tr:nth-child(even) {
      background-color: #f7fafc;
    }

    .markdown-container .markdown-table .text-center {
      text-align: center;
    }

    .markdown-container .markdown-table .text-right {
      text-align: right;
    }

    /* 水平线样式 */
    .markdown-container hr {
      margin: 2rem 0;
      border: 0;
      border-top: 1px solid #e2e8f0;
    }

    /* 图片样式 */
    .markdown-container img {
      max-width: 100%;
      height: auto;
      margin: 1.5rem 0;
      border-radius: 6px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    /* 脚注样式 */
    .markdown-container .footnotes {
      margin-top: 2rem;
      padding-top: 1rem;
      border-top: 1px solid #e2e8f0;
      font-size: 0.9rem;
      color: #718096;
    }

    .markdown-container .footnotes ol {
      padding-left: 1.5rem;
    }

    .markdown-container .footnotes li {
      margin-bottom: 0.75rem;
    }

    .markdown-container .footnotes a {
      color: #718096;
    }

    .markdown-container .footnotes a:hover {
      color: #2d3748;
    }

    /* 响应式调整 */
    @media (max-width: 640px) {
      .markdown-container {
        padding: 1.5rem 1rem;
      }

      .markdown-container h1 {
        font-size: 1.75rem;
      }

      .markdown-container h2 {
        font-size: 1.4rem;
      }

      .markdown-container .markdown-table {
        display: block;
        overflow-x: auto;
      }
    }

    .form-generate {
      display: none;
    }
  </style>
</head>
<?php
$max_execution_time = ini_get("max_execution_time");
if ($max_execution_time < 60) {
  echo "<span class='text-danger'>注意：当前超时时间为{$max_execution_time}秒，建议设置为60秒以上，否则可能会导致生成内容失败。</span><br/>";
}

$type = safe_word(getform('type', 'get'));
$table = safe_word(getform('table', 'get'));
$id = safe_word(getform('id', 'get'));
$ids = safe_key(getform('ids', 'get'));
$sid = safe_word(getform('sid', 'get'));
$stype = safe_word(getform('stype', 'get'));
$folder = safe_key(getform('folder', 'get'));
$langauge = G('language');
?>

<body class="gray-bg">
  <div class="wrapper wrapper-content">
    <div class="row">
      <!-- AI对话框 -->
      <div class="col-sm-12 ">
        <div class="ibox-content">
          <div class="form-horizontal">
            <div class="form-group">
              <label class="control-label col-sm-2" id="ai-label">
                <h4>AI对话框</h4>
                <p>
                  <标题>和<内容>，会自动替换。
                </p>
                <button type="button" class="btn btn-primary" id="polish-btn" data-polish="{$ai_conf['prompt']['polish']}">润色</button>
                <button type="button" class="btn btn-primary" id="extend-btn" data-polish="{$ai_conf['prompt']['create']}">创造</button>
                <button type="button" class="btn btn-primary" id="layout-btn" data-polish="{$ai_conf['prompt']['format']}">排版</button>
                <button type="button" class="btn btn-primary" id="translate-btn" data-polish="{$ai_conf['prompt']['translate']}">翻译</button>
                <br /><br />
                <button type="button" class="btn btn-primary" id="safe-btn" data-polish="{$ai_conf['prompt']['security']}">安全</button>
                <button type="button" class="btn btn-primary" id="art-btn" data-polish="{$ai_conf['prompt']['art']}">艺术</button>
                <button type="button" class="btn btn-primary" id="seo-btn" data-polish="{$ai_conf['prompt']['seo']}">SEO</button>
                <button type="button" class="btn btn-primary" id="faq-btn" data-polish="{$ai_conf['prompt']['faq']}">FAQ</button>
              </label>
              <div class="col-sm-10">
                <textarea id="ai-prompt" class="form-control" rows="5" style="min-height: 50px; resize: vertical;"></textarea>
              </div>
            </div>
            <!-- 提交按钮 -->
            <div class="form-group">
              <div class="col-sm-2 col-sm-offset-2">
                <button type="button" id="submit-ai" class="btn btn-success btn-block"><i class="fa fa-send-o"></i> 提交</button>
              </div>
              <div class="col-sm-2">
                <button type="button" id="pause-ai" class="btn btn-warning ml-2 hide"><i class="fa fa-pause"></i> 暂停</button>
                <button type="button" class="btn btn-default ml-2 close-btn"><i class="fa fa-times"></i> 关闭</button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-sm-12 m-t-20">
        <div class="ibox-content">
          <div class="form-horizontal">
            <?php

            if ($type == 'generate' && $table == 'content') {
              $type_name = 'AI内容创作';
              $company = $langauge['companyname'];
              $keywords = $langauge['sitekeys'];
              $prompt = "请创作一个关于[ $company]的内容，要包含[$keywords]关键词，字数500字左右";
              if ($sid) {
                $stype =  $stype ?: db_select('sort', 's_type', ['sid' => $sid]);
                echo "  <div class='form-group'> <label class='col-sm-2 control-label'>请选择分类：</label>
               <div class='col-sm-2'><select name='c_sid' data-required='num' class='form-control'>" . select_sort_content($stype, $sid) . " </select></div><div class='col-sm-8'></div>  </div>";
              } else if ($stype) {
                echo "  <div class='form-group'> <label class='col-sm-2 control-label'>请选择分类：</label>
               <div class='col-sm-2'><select name='c_sid' data-required='num' class='form-control'>" . select_sort_content($stype, 0) . " </select></div><div class='col-sm-8'></div>  </div>";
              }
            } else if ($type == 'createhtml') {
              $type_name = 'AI创建模板页';
              $prompt = "请设计一个网页，文件名：about.html，文字内容如下：";
            } else {
              $type_name = 'AI润色优化';
              $prompt = "请对以下内容进行润色优化";
            }
            ?>
            <!-- 内容ID选择 -->
            <div class="form-group form-<?= $type ?>">
              <!-- 任务类型选择 -->
              <label class="col-sm-2 control-label text-danger">待处理内容</label>
              <div class="col-sm-10">
                <div style="max-height: 300px; overflow-y: auto;">

                  <?php
                  if ($table == 'content') {
                    if ($type != 'generate') {
                      // 获取当前分类的内容列表
                      if (!$ids && !$id) {
                        returnmsg('json', 0, '请选择内容');
                      }
                      $id_arr = $ids ? explode(',', $ids) : $id;

                      $data = db_load("content", ['cid' => $id_arr]);
                      $tr_html = '';
                      foreach ($data as $row) {
                        $tr_html .= "<tr id='row-$row[cid]'>
                              <td>$row[cid]</td>
                               <td>$row[c_title]</td>
                               <td id='status-$row[cid]'><span class='label label-default' >未开始</span></td>
                               <td><button type='button' class='btn btn-sm btn-danger delete-row' data-id='$row[cid]'><i class='fa fa-trash'></i></button></td>
                            </tr>";
                      }
                      echo '<table class="table table-striped table-bordered table-hover"><thead><tr><th style="width: 10%;">ID</th><th style="width: 50%;">标题</th><th style="width: 30%;">任务进程</th><th style="width: 10%;">操作</th></tr></thead><tbody id="content-ids-table">' . $tr_html . '</tbody> </table> <p class="text-muted" style="margin-top: 10px;">提示：最多支持多选50条内容，<strong>注意：生成后，任务进程 中手动点击【保存】</strong></p>';
                    }
                  } else {
                    $data = db_load_one($table, $id);
                    switch ($table) {
                      case 'about':
                        $html = $data['a_content'];
                        break;
                      case 'brand':
                        $html = $data['b_content'];
                        break;
                      case 'content':
                        $html = $data['c_content'];
                        break;
                      case 'labels':
                        $html = $data['label_content'];
                        break;
                      default:
                        $html = '';
                        break;
                    }
                    echo " <textarea id='" . $table . "_content' class='form-control' readonly='readonly' rows='5' style='height: 100px;'>" . html_AI(decode($html)) . "</textarea> ";
                  }
                  ?>
                </div>

              </div>
            </div>

          </div>
        </div>
      </div>
      <div class="col-sm-12 m-t-20" id="ai-result-container" style="display: none;">
        <div class="ibox-content">
          <div class="form-horizontal">
            <!-- AI返回结果显示 -->
            <div class="form-group">
              <label class="col-sm-2 control-label text-danger">AI结果预览</label>
              <div class="col-sm-10">
                <div id="ai-result" class="well markdown-container" style="min-height: 50px; white-space: pre-wrap; word-break: break-word;"> </div>
              </div>
            </div>

            <!-- 后续操作按钮 -->
            <div class="form-group">
              <label class="col-sm-2 control-label text-danger">操作</label>
              <div class="col-sm-10" id="action-buttons">
                <button type="button" class="btn btn-default ml-2 close-btn"><i class="fa fa-times"></i> 关闭</button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- 运行结果 -->
      <div class="col-sm-12 m-t-20">
        <div class="ibox-content">
          <div class="form-horizontal">
            <div class="form-group">
              <label class="col-sm-2 control-label">运行结果</label>
              <div class="col-sm-10">
                <div id="result-message" style="margin-bottom: 10px;"></div>
              </div>
            </div>
            <!-- 进度条1：总体进度 -->
            <div class="form-group" style="margin-bottom: 10px;">
              <label class="col-sm-2 control-label text-info">总体进度</label>
              <div class="col-sm-10">
                <div class="progress" style="margin-bottom: 5px;">
                  <div id="progress-bar-1" class="progress-bar progress-bar-info progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                    0%
                  </div>
                </div>
              </div>
            </div>

            <!-- 进度条2：当前任务进度 -->
            <div class="form-group" style="margin-bottom: 10px;">
              <label class="col-sm-2 control-label text-warning">当前任务进度</label>
              <div class="col-sm-10">
                <div class="progress" style="margin-bottom: 5px;">
                  <div id="progress-bar-2" class="progress-bar progress-bar-warning progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                    0%
                  </div>
                </div>
              </div>
            </div>
            <div id="time-estimate" class="text-muted text-sm"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
<script>
  $(function() {
    var prompt = '<?= $prompt; ?>';
    var type = '<?= $type; ?>';
    var return_url = '';
    var sid = '<?= $sid; ?>';
    var id = '<?= $id; ?>';
    var table = '<?= $table ?>';
    $('#ai-prompt').val(prompt);
    parent.$(".layui-layer-title")[0].innerText = '<?= $type_name ?>';
    // 初始化禁用操作按钮
    if (type == 'generate') {
      $('#ai-label button').remove();
      $('#action-buttons').html('<button type="button" id="action-new" disabled="disabled" class="btn btn-success btn-sm ml-2"><i class="fa fa-plus"></i> 保存生成内容</button>');
    } else if (type == 'createhtml') {
      $('#ai-label button').hide();
    } else if (table != 'content') {
      $('#action-buttons').html('<button type="button" id="action-overwrite" disabled="disabled" class="btn btn-success btn-sm ml-2"><i class="fa fa-plus"></i> 保存生成内容</button>');
    } else if (type) {
      $('#ai-prompt').val($("#" + type + "-btn").data('polish'));
    }
    $('#ai-label button').click(function() {
      var polish = $(this).data('polish');
      $('#ai-prompt').val(polish);
    })

    $('#action-new').click(function() {
      // 检查分类是否选择
      sid = $("select[name='c_sid']").val();
      $.post('AI.php?act=save', {
        table: 'content',
        url: return_url,
        sid: sid,
        actionType: 'generate'
      }, function(data) {
        if (data.return_code == 1) {
          $('#result-message').html('<span class="text-success">保存成功：' + data.return_msg + '</span>');
        } else {
          $('#result-message').html('<span class="text-danger">操作失败: ' + (data.return_msg || '未知错误') + '</span>');
        }
      }, 'json').fail(function() {
        $('#result-message').html('<span class="text-danger">请求失败，请重试</span>');
      });
    });

    $('#action-overwrite').click(function() {
      $.post('AI.php?act=save', {
        url: return_url,
        table: table,
        id: id,
        actionType: 'overwrite'
      }, function(data) {
        if (data.return_code == 1) {
          $('#result-message').html('<span class="text-success">保存成功：' + data.return_msg + '</span>');
        } else {
          $('#result-message').html('<span class="text-danger">操作失败: ' + (data.return_msg || '未知错误') + '</span>');
        }
      }, 'json').fail(function() {
        $('#result-message').html('<span class="text-danger">请求失败，请重试</span>');
      });
    });


    // 删除行功能
    $('.delete-row').click(function() {
      var id = $(this).data('id');
      $('#row-' + id).remove();
    });

    // 预览内容函数
    window.previewContent = function(cid, return_url) {
      // 显示加载状态
      $('#result-message').html('<span class="text-info">正在获取内容预览...</span>');

      $.post('AI.php?act=preview', {
        cid: cid,
        url: return_url
      }, function(data) {
        if (data.return_code == 1) {
          $('#ai-result').html(data.return_msg || '');
          // 简单预览：在消息区域显示内容标题，实际项目中可扩展为弹窗或其他预览方式
          $('#result-message').html('<span class="text-success">预览成功：' + data.return_msg + '</span>');
        } else {
          $('#result-message').html('<span class="text-danger">预览失败: ' + (data.return_msg || '未知错误') + '</span>');
        }
      }, 'json').fail(function() {
        $('#result-message').html('<span class="text-danger">请求失败，请重试</span>');
      });
    };

    // 保存内容函数
    window.saveContent = function(cid, return_url) {
      // 显示加载状态
      $('#result-message').html('<span class="text-info">正在保存内容...</span>');
      $.post('AI.php?act=save', {
        id: cid,
        table: 'content',
        url: return_url,
        actionType: 'overwrite'
      }, function(data) {
        if (data.return_code == 1) {
          $('#result-message').html('<span class="text-success">' + data.return_msg + '</span>');
        } else {
          $('#result-message').html('<span class="text-danger">保存失败: ' + (data.return_msg || '未知错误') + '</span>');
        }
      }, 'json').fail(function() {
        $('#result-message').html('<span class="text-danger">请求失败，请重试</span>');
      });
    };

    // 定义全局变量用于控制AJAX请求
    var currentXhr = null;
    var processingIds = [];
    var isProcessing = false;
    var isPaused = false;
    var taskType = '<?= $type ?>';
    var startTime = 0;
    var processingTime = 30; // 默认按30秒一条计算
    var currentTaskProgress = 0; // 当前任务进度
    var progressInterval; // 当前任务进度计时器
    // 提交表单
    $('#submit-ai').click(function() {
      if (table == 'content') {
        if (isProcessing) return; // 防止重复提交
        var contentIds = [];
        // 如果是继续处理，使用之前保存的ID列表，否则重新收集
        if ($(this).text() === '继续处理') {
          contentIds = processingIds;
        } else {
          // 新的处理任务
          if (taskType == 'generate') {
            contentIds = [1];
          }
          // 收集所有选中的ID
          $('#content-ids-table tr').each(function() {
            contentIds.push($(this).find('td:eq(0)').text());
          });
          // 保存当前处理的ID列表
          processingIds = contentIds;
        }

        isProcessing = true;
        isPaused = false;

        // 如果是继续处理，保留之前的prompt，否则重新获取
        if ($(this).text() !== '继续处理') {
          prompt = $('#ai-prompt').val();
          // 获取后续操作选项
        }
        if (processingIds.length > 1) {
          $('#pause-ai').removeClass('hide'); // 显示暂停按钮
        }
        // 显示加载状态
        $(this).prop('disabled', true).text('处理中...');
        if ($(this).text() === '继续处理') {
          $('#result-message').html('<span class="text-info">继续处理中...</span>');
          // 继续进度条动画
          $('#progress-bar-1').addClass('active');
          $('#progress-bar-2').addClass('active');
        } else {
          $('#result-message').html('<span class="text-info">正在调用AI服务，请稍候...</span>');
          // 初始化进度条
          $('#progress-bar-1').width('0%').text('0%').attr('aria-valuenow', 0);
          $('#progress-bar-2').width('0%').text('0%').attr('aria-valuenow', 0);
          // 记录开始时间
          startTime = Date.now();
        }
      } else {
        contentIds = [1];
      }

      // 显示预计时间
      updateTimeEstimate(0, contentIds.length);

      // 顺序执行AJAX请求
      // 找到第一个未处理完成的ID并继续处理
      var startIndex = 0;
      for (var i = 0; i < contentIds.length; i++) {
        if ($('#status-' + contentIds[i] + ' span').hasClass('label-default') ||
          $('#status-' + contentIds[i] + ' span').hasClass('label-warning-paused')) {
          startIndex = i;
          break;
        }
      }
      processNextId(startIndex, contentIds, taskType);
    });

    // 更新时间估计
    function updateTimeEstimate(completedCount, totalCount) {
      if (totalCount === 0) return;

      var remainingCount = totalCount - completedCount;
      var remainingSeconds = remainingCount * processingTime;

      // 计算小时、分钟、秒
      var hours = Math.floor(remainingSeconds / 3600);
      var minutes = Math.floor((remainingSeconds % 3600) / 60);
      var seconds = Math.floor(remainingSeconds % 60);

      var timeStr = '';
      if (hours > 0) timeStr += hours + '小时';
      if (minutes > 0 || hours > 0) timeStr += minutes + '分钟';
      timeStr += seconds + '秒';

      $('#time-estimate').text('预计剩余时间: ' + timeStr);
    }

    // 顺序处理每个ID
    function processNextId(index, contentIds, taskType) {
      // console.log(index, contentIds, prompt);

      if (isPaused || index >= contentIds.length) {
        if (index >= contentIds.length) {
          // 全部处理完成
          $('#submit-ai').prop('disabled', true).text('提交完成');
          $('#pause-ai').prop('disabled', true);
          $('#result-message').html('<span class="text-success">全部处理完成</span>');
          // 更新进度条为100%
          $('#progress-bar-1').width('100%').text('100%').attr('aria-valuenow', 100);
          $('#progress-bar-2').width('100%').text('100%').attr('aria-valuenow', 100);
          // 停止当前任务进度条动画
          stopCurrentTaskProgress();
          $('#time-estimate').text('处理完成！');
        }
        isProcessing = false;
        return;
      }

      var currentId = contentIds[index];

      // 重置当前任务进度条
      currentTaskProgress = 0;
      $('#progress-bar-2').width('0%').text('0%').attr('aria-valuenow', 0);

      // 启动当前任务进度条动画
      startCurrentTaskProgress();

      // 更新当前ID的状态为处理中
      $('#status-' + currentId + ' span').removeClass('label-default label-warning-paused label-success label-danger').addClass('label-warning').text('处理中');

      // 显示当前处理的ID信息
      $('#result-message').html('<span class="text-info">正在处理ID: ' + currentId + ' ( ' + (index + 1) + '/' + contentIds.length + ' )</span>');

      // 计算并更新总体进度条
      var progress = Math.floor((index / contentIds.length) * 100);
      $('#progress-bar-1').width(progress + '%').text(progress + '%').attr('aria-valuenow', progress);

      // AJAX提交单个ID
      currentXhr = $.post('AI.php?act=' + taskType, {
        table: table,
        id: currentId,
        prompt: $('#ai-prompt').val(),
        folder: '<?= $folder ?>'
      }, function(data) {
        // 处理单个ID的响应
        if (data.return_code == 1) {
          // 显示AI返回的markdown格式结果
          $('#ai-result').html(data.return_msg || '');
          return_url = data.return_url || '';
          // 滚动到底部
          $(document).scrollTop($('#ai-result')[0].scrollHeight);
          // 更新状态为完成
          $('#status-' + currentId).html('<span class="text-success">已完成</span> <button type="button" class="btn btn-primary btn-sm" onclick="previewContent(\'' + currentId + '\',\'' + return_url + '\')" >预览</button> <button type="button" class="btn btn-info btn-sm" href="javascript:void(0);" onclick="saveContent(\'' + currentId + '\',\'' + return_url + '\')">保存</button>');
          $('#row-' + currentId).attr('class', 'text-success');
          // 启用操作按钮
          $('#action-overwrite, #action-new').prop('disabled', false);
        } else {
          // 显示错误信息
          $('#ai-result').html('<div class="text-danger">处理失败: ' + (data.return_msg || '未知错误') + '</div>');
          // 滚动到底部
          $(document).scrollTop($('#ai-result')[0].scrollHeight);
          $('#row-' + currentId).attr('class', 'text-danger');

          // 更新状态为失败
          $('#status-' + currentId + ' span').removeClass('label-warning label-warning-paused').addClass('label-danger').text('处理失败');
        }
        // 显示AI返回结果容器
        $("#ai-result-container").show();
        // 处理下一个ID
        if (!isPaused) {
          // 更新进度和预计时间
          updateTimeEstimate(index + 1, contentIds.length);

          // 停止当前任务进度条动画
          stopCurrentTaskProgress();

          setTimeout(function() {
            processNextId(index + 1, contentIds, taskType);
          }, 100); // 短暂延迟，避免请求过于密集
        }
      }, 'json').fail(function() {
        // 错误处理
        $('#result-message').html('<span class="text-danger">请求失败: 网络或服务器错误</span>');
        $('#ai-result').html('<div class="text-danger">请求失败: 网络或服务器错误</div>');
        $('#status-' + currentId + ' span').removeClass('label-warning label-warning-paused').addClass('label-danger').text('请求失败');

        // 继续处理下一个
        if (!isPaused) {
          // 更新进度和预计时间
          updateTimeEstimate(index + 1, contentIds.length);

          // 停止当前任务进度条动画
          stopCurrentTaskProgress();

          setTimeout(function() {
            processNextId(index + 1, contentIds, taskType);
          }, 100);
        }
      });
    }

    // 暂停按钮功能
    $('#pause-ai').click(function() {
      if (isProcessing) {
        isPaused = true;

        if (currentXhr) {
          // 中止当前AJAX请求
          currentXhr.abort();
        }

        $('#result-message').html('<span class="text-warning">处理已暂停</span>');
        $('#time-estimate').text('处理已暂停');
        // 暂停进度条动画
        $('#progress-bar-1').removeClass('active');
        $('#progress-bar-2').removeClass('active');
        // 停止当前任务进度条动画
        stopCurrentTaskProgress();
        $('#submit-ai').prop('disabled', false).text('继续处理');
        $(this).prop('disabled', true);

        // 更新当前处理中的项目状态为暂停
        processingIds.forEach(function(id) {
          if ($('#status-' + id + ' span').hasClass('label-warning')) {
            $('#status-' + id + ' span').removeClass('label-warning').addClass('label-warning-paused').text('已暂停');
          }
        });

        // 添加CSS样式用于暂停状态
        if (!$('.label-warning-paused').length) {
          var style = $('<style type="text/css">.label-warning-paused { background-color: #f0ad4e; color: #fff; border: 1px dashed #fff; }</style>');
          $('head').append(style);
        }

        currentXhr = null;
        isProcessing = false;
      }
    });

    // 当前任务进度条动画函数
    function startCurrentTaskProgress() {
      // 清除可能存在的计时器
      clearInterval(progressInterval);

      // 设置进度条动画，模拟当前任务的进度
      progressInterval = setInterval(function() {
        if (currentTaskProgress < 95) { // 最多到95%，留一点给完成时
          currentTaskProgress += 0.5;
          $('#progress-bar-2').width(currentTaskProgress + '%').text(Math.round(currentTaskProgress) + '%').attr('aria-valuenow', currentTaskProgress);
        }
      }, processingTime * 10); // 根据处理时间调整更新频率
    }

    // 停止当前任务进度条动画
    function stopCurrentTaskProgress() {
      clearInterval(progressInterval);
      // 完成时设为100%
      $('#progress-bar-2').width('100%').text('100%').attr('aria-valuenow', 100);
    }
  });

  // 关闭按钮
  $('.close-btn').click(function() {
    parent.layer.closeAll();
  });
</script>

</html>