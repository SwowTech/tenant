# swow.tech Tenant（租户站点）

[English](./README.md) | [中文](./README.zh-CN.md)

<p align="center">
  <img src="./docs/images/logo-horizontal.png" alt="swow.tech" width="320" />
</p>

[swow.tech](https://swow.tech) 开源 **租户站点** 栈：后台 + 管理端，支持租户隔离与独立应用网关。

| 目录 | 作用 | 默认端口 |
|---|---|---|
| `tenant-php` | 租户后端（Hyperf + Swow） | **9501** |
| `tenant-vue` | 租户管理端 | **2888** |

> 由历史目录名 `user-php` / `user-vue` 更名而来。

## 架构一览

```mermaid
flowchart LR
  Browser[浏览器] --> Vue["tenant-vue :2888"]
  Browser --> Php["tenant-php :9501"]
  Vue -->|API| Php
  Php --> Apps["apps/厂商/应用"]
  Php --> DB[(MySQL)]
  Php --> Redis[(Redis)]
```

## 环境要求

- PHP ≥ 8.1（推荐 Swow）
- MySQL ≥ 5.7（推荐 8.0）
- Redis（推荐）
- Node.js ≥ 18

## 快速开始

### 后端

```bash
cd tenant-php
cp .env.example .env
# 配置数据库、APP_URL、SERVER_PORT 等
composer install
php bin/hyperf.php start
```

### 前端

```bash
cd tenant-vue
npm install
npm run dev
```

打开 Vite 打印的地址（常见为 `http://localhost:2888`）。

本地租户示例：`http://{租户标识}.localhost:2888/login`。

## 截图说明（如何加图片）

1. 把图片放进仓库目录 **`docs/images/`**（已提交的 logo 也在这里）。
2. 在 Markdown 里用相对路径引用：

```markdown
![登录页](./docs/images/screenshot-login.png)

<!-- 也可控制宽度（GitHub 支持 HTML） -->
<p align="center">
  <img src="./docs/images/screenshot-welcome.png" alt="欢迎页" width="720" />
</p>
```

3. 建议文件名：

| 文件 | 内容 |
|---|---|
| `docs/images/screenshot-login.png` | 登录页 |
| `docs/images/screenshot-welcome.png` | 欢迎页 / 我的应用 |
| `docs/images/screenshot-app-mgmt.png` | 应用管理 |

4. 提交并推送后，GitHub 会直接渲染这些图片。

> 暂无截图时，可先只保留上方 logo；有界面后再补 `screenshot-*.png` 并在本段取消注释或补上 `![...](...)`。

## 仓库与文档

- GitHub：https://github.com/SwowTech/tenant
- 产品文档：[swow.tech](https://swow.tech)

## 许可证

各子目录下如有 `LICENSE` 文件，以其为准。
