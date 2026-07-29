<?php
// 全局中间件定义文件
return [
    // 宿主 DB_* + 租户前缀（必须最先）
    \app\http\middleware\ApplyHostDb::class,
    // Session初始化
    \think\middleware\SessionInit::class,
];
