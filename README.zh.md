# 🌍 MikroTik Geo IP script Generator & Live Dashboard

**__Readme Languages__** [![Français](https://img.shields.io/badge/lang-Français-lightgrey.svg)](README.md)
[![English](https://img.shields.io/badge/lang-English-lightgrey.svg)](README.en.md)
[![Deutsch](https://img.shields.io/badge/lang-Deutsch-lightgrey.svg)](README.de.md)
[![Español](https://img.shields.io/badge/lang-Español-lightgrey.svg)](README.es.md)
[![Русский](https://img.shields.io/badge/lang-Русский-lightgrey.svg)](README.ru.md)
[![中文](https://img.shields.io/badge/lang-中文-blue.svg)](README.zh.md)

![RouterOS](https://img.shields.io/badge/RouterOS-v7.1%2B-blue?style=flat-square&logo=mikrotik)
![PHP](https://img.shields.io/badge/PHP-7.4%20%7C%208.x-777BB4?style=flat-square&logo=php)
![License](https://img.shields.io/badge/License-MIT-success?style=flat-square)

一个完整的工具包，用于在 MikroTik 路由器（IPv4 和 IPv6）上生成 Geo-IP 阻止规则，并通过一个易于访问的仪表板实时监控被阻止/允许的流量。

## ✨ 功能特点

* **脚本生成器 (.rsc)：** 基于最新的 IP 数据库自动创建 *Address Lists* 和防火墙规则（`raw` 用于阻止，`mangle` 用于观察）。
* **双重方法：** 使用用户友好的 Web 界面 (`generator.php`) 或 CLI 脚本 (`bash-cli`) 进行自动化 (cron)。
* **实时仪表板：** 一个无依赖的单页 PHP/JS 解决方案，用于可视化您的流量。
  * ⏱️ 实时刷新（5 秒）。
  * 🗺️ 全球强度地图。
  * 📈 带宽图表 (Kbps) 和协议分布 (IPv4 vs IPv6)。
* **多语言：** Web UI 和仪表板提供 6 种语言版本 (🇫🇷, 🇬🇧, 🇩🇪, 🇪🇸, 🇷🇺, 🇨🇳)。
* **防误报安全：** 默认排除托管关键基础设施的国家（例如，澳大利亚的 Cloudflare 1.1.1.1，AWS/Azure 节点）。

## 🚀 安装与部署

### 先决条件
* 运行 **RouterOS v7.1 或更高版本** 的 MikroTik 路由器（REST API 必需）。
* 路由器上启用了 `www` 或 `www-ssl` 服务 (`/ip service`)。
* 一个启用了 PHP 和 `cURL` 扩展的 Web 服务器。

### 第 1 步：生成过滤脚本

1. **在线方式：** 使用[托管生成器](https://jbsan.fr/mikrotik-geo-counrty-generator.php)无需安装即可生成文件。
2. **自托管方式：** 将 `web-gui` 文件夹上传到 Web 服务器，通过浏览器访问 `generator.php`。
3. 选择 WAN 接口和要阻止的国家。
4. 点击 **生成 .rsc 脚本**。该工具将从 [ipverse](https://github.com/ipverse/country-ip-blocks) 下载 IP 并编译 MikroTik 语法。

> **💡 硬件说明：** 如果您使用交换机（CRS/CSS 系列）进行软件路由，请限制观察国家的数量以避免 CPU 过载。理想情况下，请使用硬件路由器（CCR、RB、CHR 系列）。

### 第 2 步：导入到 MikroTik
1. 通过 Winbox 将生成的 `geo-policy.rsc` 文件传输到您的路由器（拖放到 *Files* 中）。
2. 打开 MikroTik 终端并导入文件。
   **注意：** 指定正确的存储路径（例如 `flash/`）并使用 `verbose` 模式以发现任何错误：
   ```routeros
   /import file-name=flash/geo-policy.rsc verbose=yes
   ```

### 第 3 步：配置实时仪表板

1. 在 `generator.php` 中，点击 **仪表板 PHP 文件** 下载监控文件。
2. 使用文本编辑器打开此文件并在顶部配置您的凭据：

```php
$MK_IP = '192.168.88.1'; // 您路由器的 IP
$MK_USER = 'api_user';   // 用户（建议只读）
$MK_PASS = 'password';   // 密码
$USE_HTTPS = false;      // 如果在 RouterOS 上使用 SSL 证书，请设置为 true

```

3. 将此文件托管在您的服务器上并享受数据大屏！

## 🛡️ 关于敏感国家

阻止“除了您自己国家之外的所有人”通常是个坏主意。默认情况下，生成器会排除某些国家以避免破坏合法服务：

* **AU (澳大利亚)：** Cloudflare / APNIC IP。
* **IN (印度) & IL (以色列)：** 全球 IT、网络和外包中心。
* **AE (阿联酋)：** 主要的 AWS / Azure 节点。
* **EE (爱沙尼亚) & BG (保加利亚)：** 高度融入欧洲 IT。

## 🔗 有用链接

* **在线生成器**：[MikroTik Geo-Policy Generator（托管版本）](https://jbsan.fr/mikrotik-geo-counrty-generator.php)
* **GitHub 项目**：[sannier3/mikrotik-geoip-block-manager](https://github.com/sannier3/mikrotik-geoip-block-manager)

## 🤝 鸣谢

* IP 列表由 [ipverse/country-ip-blocks](https://github.com/ipverse/country-ip-blocks) 项目维护。
* UI 使用 TailwindCSS、Chart.js 和 jsVectorMap 构建。

## 📄 许可证

本项目基于 MIT 许可证。请随意复刻、改进并在您的基础架构中使用。
