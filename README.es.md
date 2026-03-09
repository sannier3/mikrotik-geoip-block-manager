# 🌍 MikroTik Geo IP script Generator & Live Dashboard

**__Readme Languages__** [![Français](https://img.shields.io/badge/lang-Français-lightgrey.svg)](README.md)
[![English](https://img.shields.io/badge/lang-English-lightgrey.svg)](README.en.md)
[![Deutsch](https://img.shields.io/badge/lang-Deutsch-lightgrey.svg)](README.de.md)
[![Español](https://img.shields.io/badge/lang-Español-blue.svg)](README.es.md)
[![Русский](https://img.shields.io/badge/lang-Русский-lightgrey.svg)](README.ru.md)
[![中文](https://img.shields.io/badge/lang-中文-lightgrey.svg)](README.zh.md)

![RouterOS](https://img.shields.io/badge/RouterOS-v7.1%2B-blue?style=flat-square&logo=mikrotik)
![PHP](https://img.shields.io/badge/PHP-7.4%20%7C%208.x-777BB4?style=flat-square&logo=php)
![License](https://img.shields.io/badge/License-MIT-success?style=flat-square)

Un conjunto completo de herramientas para generar reglas de bloqueo geográfico (Geo-IP) en routers MikroTik (IPv4 e IPv6) y supervisar el tráfico bloqueado/permitido en tiempo real mediante un panel de control accesible.

## ✨ Características

* **Generador de Scripts (.rsc):** Crea automáticamente *Address Lists* y reglas de Firewall (`raw` para bloquear, `mangle` para observar) basadas en bases de datos de IP actualizadas.
* **Doble Enfoque:** Utiliza la interfaz web intuitiva (`generator.php`) o el script CLI (`bash-cli`) para tus automatizaciones (cron).
* **Live Dashboard:** Una solución de un solo archivo PHP/JS sin dependencias para visualizar tu tráfico.
  * ⏱️ Actualización en tiempo real (5 segundos).
  * 🗺️ Mapa de intensidad global.
  * 📈 Gráficos de ancho de banda (Kbps) y distribución de protocolos (IPv4 vs IPv6).
* **Multilingüe:** La interfaz web y el Dashboard están disponibles en 6 idiomas (🇫🇷, 🇬🇧, 🇩🇪, 🇪🇸, 🇷🇺, 🇨🇳).
* **Seguridad Anti-Falsos Positivos:** Exclusión por defecto de países que alojan infraestructuras críticas (ej: Cloudflare 1.1.1.1 en Australia, nodos AWS/Azure).

## 🚀 Instalación y Despliegue

### Requisitos previos
* Un router MikroTik con **RouterOS v7.1 o superior** (requerido para la API REST).
* El servicio `www` o `www-ssl` activado en el router (`/ip service`).
* Un servidor web con PHP y la extensión `cURL` activada.

### Paso 1: Generar el script de filtrado

1. **Opción en línea:** usa el [generador alojado](https://jbsan.fr/mikrotik-geo-counrty-generator.php) para generar el archivo sin instalar nada.
2. **Opción self-hosted:** sube la carpeta `web-gui` a tu servidor web y accede a `generator.php` desde tu navegador.
3. Selecciona tu interfaz WAN y los países a bloquear.
4. Haz clic en **Generar Script .rsc**. La herramienta descargará las IPs desde [ipverse](https://github.com/ipverse/country-ip-blocks) y compilará la sintaxis de MikroTik.

> **💡 Nota de Hardware:** Si utilizas un Switch (serie CRS/CSS) para enrutamiento por software, limita el número de países observados para evitar sobrecargar la CPU. Idealmente, usa un router de hardware (serie CCR, RB, CHR).

### Paso 2: Importar en MikroTik
1. Transfiere el archivo `geo-policy.rsc` generado a tu router vía Winbox (Arrastrar y Soltar en *Files*).
2. Abre la terminal de MikroTik e importa el archivo. 
   **Atención:** Especifica la ruta de almacenamiento correcta (ej: `flash/`) y usa el modo `verbose` para detectar cualquier error:
   ```routeros
   /import file-name=flash/geo-policy.rsc verbose=yes
   ```

### Paso 3: Configurar el Dashboard Live

1. Desde `generator.php`, descarga el archivo de monitorización haciendo clic en **Archivo Dashboard PHP**.
2. Abre este archivo con un editor de texto y configura tus credenciales en la parte superior:

```php
$MK_IP = '192.168.88.1'; // IP de tu router
$MK_USER = 'api_user';   // Usuario (se recomienda solo lectura)
$MK_PASS = 'password';   // Contraseña
$USE_HTTPS = false;      // Cambiar a true si usas un certificado SSL en RouterOS

```

3. ¡Aloja este archivo en tu servidor y disfruta del panel!

## 🛡️ Acerca de los Países Sensibles

Bloquear "a todo el mundo excepto a tu país" suele ser una mala idea. Por defecto, el generador excluye ciertos países para evitar romper servicios legítimos:

* **AU (Australia):** IPs de Cloudflare / APNIC.
* **IN (India) e IL (Israel):** Centros globales de TI, Ciberseguridad y Outsourcing.
* **AE (Emiratos Árabes Unidos):** Nodos principales de AWS / Azure.
* **EE (Estonia) y BG (Bulgaria):** Fuertemente integrados en la TI europea.

## 🔗 Enlaces útiles

* **Generador en línea**: [MikroTik Geo-Policy Generator (hospedado)](https://jbsan.fr/mikrotik-geo-counrty-generator.php)
* **Proyecto GitHub**: [sannier3/mikrotik-geoip-block-manager](https://github.com/sannier3/mikrotik-geoip-block-manager)

## 🤝 Créditos

* Listas de IP mantenidas por el proyecto [ipverse/country-ip-blocks](https://github.com/ipverse/country-ip-blocks).
* Interfaz construida con TailwindCSS, Chart.js y jsVectorMap.

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Siéntete libre de bifurcarlo, mejorarlo y usarlo en tus infraestructuras.
