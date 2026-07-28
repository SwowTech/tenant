<?php
// AI配置文件
$ai_conf = array (
  'enable_ai' => '0',
  'default_ai' => '',
  'baidu' => 
  array (
    'enable' => '0',
    'api_key' => '',
    'secret_key' => '',
    'api_version' => 'v3',
    'timeout' => '90',
    'model' => 'response',
    'url' => 'https://qianfan.baidubce.com/v2/chat/completions',
    'models' => '',
  ),
  'aliyun' => 
  array (
    'enable' => '0',
    'api_key' => '',
    'timeout' => '90',
    'model' => 'qwen3-max',
    'url' => 'https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions',
    'models' => '',
  ),
  'tencent' => 
  array (
    'enable' => '0',
    'api_key' => '',
    'timeout' => '90',
    'model' => 'hunyuan-turbos-latest',
    'url' => 'https://api.hunyuan.cloud.tencent.com/v1/chat/completions',
    'models' => '',
  ),
  'zhipu' => 
  array (
    'enable' => '0',
    'api_key' => '',
    'timeout' => '90',
    'model' => 'glm-4.5-flash',
    'url' => 'https://open.bigmodel.cn/api/paas/v4/chat/completions',
    'models' => '',
  ),
  'doubao' => 
  array (
    'enable' => '0',
    'api_key' => '',
    'secret_key' => '',
    'timeout' => '90',
    'model' => 'doubao-seed-1-6-vision-250815',
    'url' => 'https://ark.cn-beijing.volces.com/api/v3/chat/completions',
    'models' => '',
  ),
  'other' => 
  array (
    'enable' => '0',
    'api_name' => '自定义AI',
    'api_key' => '',
    'secret_key' => '',
    'timeout' => '90',
    'model' => '',
    'url' => '',
    'models' => '',
  ),
  'max_tokens' => '5000',
  'temperature' => '0.7',
  'top_p' => '0.1',
  'frequency_penalty' => '',
  'presence_penalty' => '',
  'cache_ttl' => '3600',
  'log_level' => 'debug',
  'prompt' => 
  array (
    'polish' => '请对<标题><内容>进行润色要求专业,商务,符合SEO标准',
    'create' => '请根据<标题>创作一篇高质量文章要求结构清晰<内容>丰富原创度高',
    'translate' => '请将<内容>翻译成英语确保专业术语准确',
    'security' => '请检查<内容>是否存在安全风险或敏感信息泄露问题',
    'faq' => '请根据<内容>创建常见问题解答FAQ列表包含可能的用户疑问及详细回答',
    'art' => '请为<标题><内容>创作一段富有艺术感染力的描述或文案',
    'format' => '请对<内容>进行排版优化使其结构清晰易于阅读',
    'seo' => '请对<内容>进行SEO优化添加合适的关键词提高搜索引擎排名',
  ),
);
