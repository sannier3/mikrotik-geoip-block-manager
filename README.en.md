# 🌍 MikroTik Geo IP script Generator & Live Dashboard

**__Readme Languages__** [![Français](https://img.shields.io/badge/lang-Français-lightgrey.svg)](README.md)
[![English](https://img.shields.io/badge/lang-English-blue.svg)](README.en.md)
[![Deutsch](https://img.shields.io/badge/lang-Deutsch-lightgrey.svg)](README.de.md)
[![Español](https://img.shields.io/badge/lang-Español-lightgrey.svg)](README.es.md)
[![Русский](https://img.shields.io/badge/lang-Русский-lightgrey.svg)](README.ru.md)
[![中文](https://img.shields.io/badge/lang-中文-lightgrey.svg)](README.zh.md)

![RouterOS](https://img.shields.io/badge/RouterOS-v7.1%2B-blue?style=flat-square&logo=mikrotik)
![PHP](https://img.shields.io/badge/PHP-7.4%20%7C%208.x-777BB4?style=flat-square&logo=php)
![License](https://img.shields.io/badge/License-MIT-success?style=flat-square)

A complete toolkit to generate Geo-IP blocking rules for MikroTik routers (IPv4 & IPv6) and monitor blocked/allowed traffic in real-time through an accessible dashboard.

## ✨ Features

* **Script Generator (.rsc):** Automatically create *Address Lists* and Firewall rules (`raw` to block, `mangle` to observe) based on up-to-date IP databases.
* **Dual Approach:** Use the user-friendly Web interface (`generator.php`) or the CLI script (`bash-cli`) for your automations (cron).
* **Live Dashboard:** A single-page PHP/JS solution with no dependencies to visualize your traffic.
  * ⏱️ Real-time refresh (5 seconds).
  * 🗺️ Global intensity map.
  * 📈 Bandwidth charts (Kbps) and protocol distribution (IPv4 vs IPv6).
* **Multilingual:** The Web UI and Dashboard are available in 6 languages (🇫🇷, 🇬🇧, 🇩🇪, 🇪🇸, 🇷🇺, 🇨🇳).
* **Anti-False Positive Security:** Default exclusion of countries hosting critical infrastructure (e.g., Cloudflare 1.1.1.1 in Australia, AWS/Azure hubs).

## 🚀 Installation & Deployment

### Prerequisites
* A MikroTik router running **RouterOS v7.1 or higher** (required for REST API).
* The `www` or `www-ssl` service enabled on the router (`/ip service`).
* A Web server with PHP and the `cURL` extension enabled.

### Step 1: Generate the Filter Script

1. **Online option:** use the [hosted generator](https://jbsan.fr/mikrotik-geo-counrty-generator.php) to generate the file without installing anything.
2. **Self-hosted option:** upload the `web-gui` folder to your web server and access `generator.php` via your browser.
3. Select your WAN interface and the countries to block.
4. Click **Generate .rsc Script**. The tool will download IPs from [ipverse](https://github.com/ipverse/country-ip-blocks) and compile the MikroTik syntax.

> **💡 Hardware Note:** If you use a Switch (CRS/CSS series) for software routing, limit the number of observed countries to avoid CPU overload. Ideally, use a hardware router (CCR, RB, CHR series).

### Step 2: Import to MikroTik
1. Transfer the generated `geo-policy.rsc` file to your router via Winbox (Drag & Drop into *Files*).
2. Open the MikroTik terminal and import the file. 
   **Warning:** Specify the correct storage path (e.g., `flash/`) and use the `verbose` mode to spot any errors:
   ```routeros
   /import file-name=flash/geo-policy.rsc verbose=yes
   ```

### Step 3: Configure the Live Dashboard

1. From `generator.php`, download the monitoring file by clicking **Dashboard PHP File** (This is the `mikrotik-monitor-default.php` file).
2. Open this file with a text editor and configure your credentials at the top:

```php
$MK_IP = '192.168.88.1'; // Your router's IP
$MK_USER = 'api_user';   // User (read-only recommended)
$MK_PASS = 'password';   // Password
$USE_HTTPS = false;      // Set to true if using an SSL certificate on RouterOS
```

3. Host this file on your server and enjoy the show!

## 🛡️ About Sensitive Countries

Blocking "everyone except your country" is often a bad idea. By default, the generator excludes certain countries to avoid breaking legitimate services:

* **AU (Australia):** Cloudflare / APNIC IPs.
* **IN (India) & IL (Israel):** Global IT, Cyber, and Outsourcing hubs.
* **AE (UAE):** Major AWS / Azure nodes.
* **EE (Estonia) & BG (Bulgaria):** Highly integrated into European IT.

## 🔗 Useful Links

* **Online generator**: [MikroTik Geo-Policy Generator (hosted)](https://jbsan.fr/mikrotik-geo-counrty-generator.php)
* **GitHub project**: [sannier3/mikrotik-geoip-block-manager](https://github.com/sannier3/mikrotik-geoip-block-manager)

## 🤝 Credits

* IP lists maintained by the [ipverse/country-ip-blocks](https://github.com/ipverse/country-ip-blocks) project.
* UI built with TailwindCSS, Chart.js, and jsVectorMap.

## 📄 License

This project is licensed under the MIT License. Feel free to fork, improve, and use it in your infrastructures (credit is appreciated).
