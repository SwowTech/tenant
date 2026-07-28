# 溯源防伪（swowtech/xunlian-trace）

用 MineAdmin 现行应用模型重做的溯源防伪管理端（参考旧项目 `xunlian-trace-1` 业务，不运行 ThinkPHP/Vue2）。

## 访问

- 打开：`http://{tenant-host}:9501/swowtech/xunlian-trace/`
- API：`/swowtech/xunlian-trace/api/...`（经宿主网关反代，需 `X-Tenant-Id` + gateway secret）

## 启用到租户

```bash
cd user-php
..\tools\php82-portable\php.exe scripts\ensure-xunlian-trace-app.php --tenant=ID
```

首次 API 请求会按租户前缀自动建表，例如租户 33：

- `cy_33_trace_product`
- `cy_33_trace_batch`
- `cy_33_trace_code`
- `cy_33_trace_writeoff`
- `cy_33_trace_scan_log`

前缀来自网关头 `X-Tenant-Prefix`（缺省 `cy_{id}_`），与宿主租户表隔离方式一致。

## 一期功能

- 工作台统计
- 商品 CRUD
- 码批次生成 / 查看码
- 码查询与核销

## 技术说明

- 形态对齐 `mineadmin/demo`：`web/` 静态管理端 + `public/` API 子进程
- 管理端：Vue 3 + Element Plus（CDN，开箱可用）
- 数据库：宿主 `DB_*`；**按租户分表**，不再用 `tenant_id` 行级过滤
