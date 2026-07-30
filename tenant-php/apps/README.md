# Apps 目录

独立应用物理包根目录：`user-php/apps/{vendor}/{app}/`。

## 包结构

```
apps/{vendor}/{app}/
  app.json
  config/
  app/
  bin/start.php      # 进程入口（宿主设 APP_LISTEN）
  public/index.php   # HTTP 路由
  web/               # 已构建前端
  vendor/            # 发布方打进 zip（可选）
```

## 约定

- `app.json` 可选声明 **`edition`**（开放档位名，如 `community` / `pro`）与 **`family`**（同族聚合标识；缺省视为 `name`）。示例见 `mineadmin/demo/app.json`。
- 发布 zip 应包含已构建 `web/`；用户侧**无需** npm build / composer install
- 访问：`https://{tenant-domain|custom}/{vendor}/{app}/`
- API：`.../{vendor}/{app}/api/...`
- 管理端由应用自行定义
- 商城安装：zip 含 `app.json` → 解压到本目录；旧 `mine.json` 插件仍走 `plugin/`

## 进程

宿主 `AppProcessManager` 优先执行 `process.entrypoint`（默认 `bin/start.php`），注入：

- `APP_LISTEN=127.0.0.1:port`
- `APP_GATEWAY_SECRET=...`

生产可将 `bin/start.php` 换成 Hyperf/Swoole 启动脚本，协议不变。

示例：`mineadmin/demo`

