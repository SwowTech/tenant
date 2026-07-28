# 易企CMS（swowtech/yiqicms）

由 `zzzphp`（ZZZCMS）包装的独立应用。

- 访问：`/{tenant-host}/swowtech/yiqicms/`
- 后台：`/swowtech/yiqicms/admin/`
- 安装向导：`/swowtech/yiqicms/install/`（未安装时）

启用到租户：

```bash
cd user-php
..\tools\php82-portable\php.exe scripts\ensure-yiqicms-app.php --tenant=ID
```

本机冒烟（需 user-php 已启动）：

```bash
curl -H "X-Tenant-Id: ID" http://127.0.0.1:9501/swowtech/yiqicms/
```
