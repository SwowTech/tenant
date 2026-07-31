# ZZZCMS（swowtech/zzzcms）

由 `zzzphp`（ZZZCMS）包装的独立应用。

- 访问：`/{tenant-host}/swowtech/zzzcms/`
- 后台：`/swowtech/zzzcms/admin/`
- 安装向导：`/swowtech/zzzcms/install/`（未安装时）

启用到租户：

```bash
cd user-php
..\tools\php82-portable\php.exe scripts\ensure-zzzcms-app.php --tenant=ID
```

本机冒烟（需 user-php 已启动）：

```bash
curl -H "X-Tenant-Id: ID" http://127.0.0.1:9501/swowtech/zzzcms/
```
