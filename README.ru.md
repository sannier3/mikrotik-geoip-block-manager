# 🌍 MikroTik Geo IP script Generator & Live Dashboard

**__Readme Languages__** [![Français](https://img.shields.io/badge/lang-Français-lightgrey.svg)](README.md)
[![English](https://img.shields.io/badge/lang-English-lightgrey.svg)](README.en.md)
[![Deutsch](https://img.shields.io/badge/lang-Deutsch-lightgrey.svg)](README.de.md)
[![Español](https://img.shields.io/badge/lang-Español-lightgrey.svg)](README.es.md)
[![Русский](https://img.shields.io/badge/lang-Русский-blue.svg)](README.ru.md)
[![中文](https://img.shields.io/badge/lang-中文-lightgrey.svg)](README.zh.md)

![RouterOS](https://img.shields.io/badge/RouterOS-v7.1%2B-blue?style=flat-square&logo=mikrotik)
![PHP](https://img.shields.io/badge/PHP-7.4%20%7C%208.x-777BB4?style=flat-square&logo=php)
![License](https://img.shields.io/badge/License-MIT-success?style=flat-square)

Полный набор инструментов для генерации правил гео-блокировки (Geo-IP) на маршрутизаторах MikroTik (IPv4 и IPv6) и мониторинга заблокированного/разрешенного трафика в реальном времени с помощью удобного дашборда.

## ✨ Функции

* **Генератор скриптов (.rsc):** Автоматическое создание *Address Lists* и правил брандмауэра (`raw` для блокировки, `mangle` для наблюдения) на основе актуальных баз данных IP.
* **Двойной подход:** Используйте удобный веб-интерфейс (`generator.php`) или скрипт командной строки (`bash-cli`) для автоматизации (cron).
* **Live Dashboard:** Единый PHP/JS файл без зависимостей для визуализации вашего трафика.
  * ⏱️ Обновление в реальном времени (5 секунд).
  * 🗺️ Глобальная карта интенсивности.
  * 📈 Графики пропускной способности (Kbps) и распределения протоколов (IPv4 vs IPv6).
* **Многоязычность:** Веб-интерфейс и дашборд доступны на 6 языках (🇫🇷, 🇬🇧, 🇩🇪, 🇪🇸, 🇷🇺, 🇨🇳).
* **Защита от ложных срабатываний:** По умолчанию исключены страны, в которых размещена критически важная инфраструктура (например, Cloudflare 1.1.1.1 в Австралии, узлы AWS/Azure).

## 🚀 Установка и развертывание

### Требования
* Маршрутизатор MikroTik с **RouterOS v7.1 или выше** (требуется для REST API).
* Включенный сервис `www` или `www-ssl` на маршрутизаторе (`/ip service`).
* Веб-сервер с PHP и включенным расширением `cURL`.

### Шаг 1: Генерация скрипта фильтрации

1. Загрузите папку `web-gui` на ваш веб-сервер.
2. Откройте `generator.php` в браузере.
3. Выберите ваш WAN-интерфейс и страны для блокировки.
4. Нажмите **Сгенерировать .rsc скрипт**. Инструмент загрузит IP-адреса из [ipverse](https://github.com/ipverse/country-ip-blocks) и скомпилирует синтаксис MikroTik.

> **💡 Примечание по оборудованию:** Если вы используете коммутатор (серии CRS/CSS) для программной маршрутизации, ограничьте количество наблюдаемых стран, чтобы избежать перегрузки процессора. В идеале используйте аппаратный маршрутизатор (серии CCR, RB, CHR).

### Шаг 2: Импорт в MikroTik
1. Перенесите сгенерированный файл `geo-policy.rsc` на ваш маршрутизатор через Winbox (Перетащите в *Files*).
2. Откройте терминал MikroTik и импортируйте файл. 
   **Внимание:** Укажите правильный путь к хранилищу (например, `flash/`) и используйте режим `verbose` для обнаружения ошибок:
   ```routeros
   /import file-name=flash/geo-policy.rsc verbose=yes
   ```

### Шаг 3: Настройка Live Dashboard

1. На странице `generator.php` скачайте файл мониторинга, нажав **PHP Дашборд**.
2. Откройте этот файл в текстовом редакторе и настройте учетные данные в самом верху:

```php
$MK_IP = '192.168.88.1'; // IP вашего маршрутизатора
$MK_USER = 'api_user';   // Пользователь (рекомендуется только для чтения)
$MK_PASS = 'password';   // Пароль
$USE_HTTPS = false;      // Установите true, если используете SSL-сертификат в RouterOS

```

3. Разместите этот файл на вашем сервере и наслаждайтесь аналитикой!

## 🛡️ О чувствительных странах

Блокировать "всех, кроме своей страны" часто бывает плохой идеей. По умолчанию генератор исключает определенные страны, чтобы не нарушить работу легитимных сервисов:

* **AU (Австралия):** IP Cloudflare / APNIC.
* **IN (Индия) и IL (Израиль):** Глобальные IT и кибер-хабы.
* **AE (ОАЭ):** Крупные узлы AWS / Azure.
* **EE (Эстония) и BG (Болгария):** Сильно интегрированы в европейскую IT-инфраструктуру.

## 🔗 Полезные ссылки

* **Онлайн-генератор**: [MikroTik Geo-Policy Generator (хостинг)](https://jbsan.fr/mikrotik-geo-counrty-generator.php)
* **Проект на GitHub**: [sannier3/mikrotik-geoip-block-manager](https://github.com/sannier3/mikrotik-geoip-block-manager)

## 🤝 Благодарности

* Списки IP поддерживаются проектом [ipverse/country-ip-blocks](https://github.com/ipverse/country-ip-blocks).
* Интерфейс создан с использованием TailwindCSS, Chart.js и jsVectorMap.

## 📄 Лицензия

Этот проект распространяется под лицензией MIT. Вы можете свободно форкать, улучшать и использовать его в своей инфраструктуре.
