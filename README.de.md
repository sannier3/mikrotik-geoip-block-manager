# 🌍 MikroTik Geo IP script Generator & Live Dashboard

**__Readme Languages__** [![Français](https://img.shields.io/badge/lang-Français-lightgrey.svg)](README.md)
[![English](https://img.shields.io/badge/lang-English-lightgrey.svg)](README.en.md)
[![Deutsch](https://img.shields.io/badge/lang-Deutsch-blue.svg)](README.de.md)
[![Español](https://img.shields.io/badge/lang-Español-lightgrey.svg)](README.es.md)
[![Русский](https://img.shields.io/badge/lang-Русский-lightgrey.svg)](README.ru.md)
[![中文](https://img.shields.io/badge/lang-中文-lightgrey.svg)](README.zh.md)

![RouterOS](https://img.shields.io/badge/RouterOS-v7.1%2B-blue?style=flat-square&logo=mikrotik)
![PHP](https://img.shields.io/badge/PHP-7.4%20%7C%208.x-777BB4?style=flat-square&logo=php)
![License](https://img.shields.io/badge/License-MIT-success?style=flat-square)

Ein umfassendes Toolkit zur Generierung von Geo-IP-Blockierregeln für MikroTik-Router (IPv4 & IPv6) und zur Überwachung des blockierten/erlaubten Verkehrs in Echtzeit über ein zugängliches Dashboard.

## ✨ Funktionen

* **Skript-Generator (.rsc):** Erstellen Sie automatisch *Address Lists* und Firewall-Regeln (`raw` zum Blockieren, `mangle` zum Beobachten) basierend auf aktuellen IP-Datenbanken.
* **Zweifacher Ansatz:** Nutzen Sie die benutzerfreundliche Web-Oberfläche (`generator.php`) oder das CLI-Skript (`bash-cli`) für Ihre Automatisierungen (Cron).
* **Live Dashboard:** Eine einzige PHP/JS-Datei ohne Abhängigkeiten zur Visualisierung Ihres Traffics.
  * ⏱️ Echtzeit-Aktualisierung (5 Sekunden).
  * 🗺️ Weltweite Intensitätskarte.
  * 📈 Bandbreiten-Diagramme (Kbps) und Protokollverteilung (IPv4 vs IPv6).
* **Mehrsprachig:** Die Web-UI und das Dashboard sind in 6 Sprachen verfügbar (🇫🇷, 🇬🇧, 🇩🇪, 🇪🇸, 🇷🇺, 🇨🇳).
* **Anti-False-Positive-Sicherheit:** Standardmäßiger Ausschluss von Ländern, die kritische Infrastrukturen hosten (z.B. Cloudflare 1.1.1.1 in Australien, AWS/Azure-Knoten).

## 🚀 Installation & Bereitstellung

### Voraussetzungen
* Ein MikroTik-Router mit **RouterOS v7.1 oder höher** (erforderlich für die REST-API).
* Der Dienst `www` oder `www-ssl` muss auf dem Router aktiviert sein (`/ip service`).
* Ein Webserver mit PHP und aktivierter `cURL`-Erweiterung.

### Schritt 1: Filter-Skript generieren

1. Laden Sie den Ordner `web-gui` auf Ihren Webserver hoch.
2. Rufen Sie `generator.php` über Ihren Browser auf.
3. Wählen Sie Ihre WAN-Schnittstelle und die zu blockierenden Länder aus.
4. Klicken Sie auf **.rsc Skript generieren**. Das Tool lädt die IPs von [ipverse](https://github.com/ipverse/country-ip-blocks) herunter und kompiliert die MikroTik-Syntax.

> **💡 Hardware-Hinweis:** Wenn Sie einen Switch (CRS/CSS-Serie) für Software-Routing verwenden, begrenzen Sie die Anzahl der beobachteten Länder, um eine CPU-Überlastung zu vermeiden. Verwenden Sie idealerweise einen Hardware-Router (CCR, RB, CHR-Serie).

### Schritt 2: In MikroTik importieren
1. Übertragen Sie die generierte Datei `geo-policy.rsc` über Winbox auf Ihren Router (Drag & Drop in *Files*).
2. Öffnen Sie das MikroTik-Terminal und importieren Sie die Datei. 
   **Achtung:** Geben Sie den richtigen Speicherpfad an (z.B. `flash/`) und verwenden Sie den `verbose`-Modus, um Fehler zu erkennen:
   ```routeros
   /import file-name=flash/geo-policy.rsc verbose=yes
   ```

### Schritt 3: Live Dashboard konfigurieren

1. Laden Sie von `generator.php` die Überwachungsdatei herunter, indem Sie auf **Dashboard PHP-Datei** klicken.
2. Öffnen Sie diese Datei mit einem Texteditor und konfigurieren Sie ganz oben Ihre Zugangsdaten:

```php
$MK_IP = '192.168.88.1'; // IP Ihres Routers
$MK_USER = 'api_user';   // Benutzer (Nur-Lese-Rechte empfohlen)
$MK_PASS = 'password';   // Passwort
$USE_HTTPS = false;      // Auf true setzen, wenn ein SSL-Zertifikat auf RouterOS verwendet wird

```

3. Hosten Sie diese Datei auf Ihrem Server und genießen Sie die Übersicht!

## 🛡️ Über sensible Länder

"Alle außer dem eigenen Land" zu blockieren, ist oft eine schlechte Idee. Standardmäßig schließt der Generator bestimmte Länder aus, um legitime Dienste nicht zu stören:

* **AU (Australien):** Cloudflare / APNIC IPs.
* **IN (Indien) & IL (Israel):** Globale IT-, Cyber- und Outsourcing-Zentren.
* **AE (VAE):** Wichtige AWS / Azure-Knoten.
* **EE (Estland) & BG (Bulgarien):** Stark in die europäische IT integriert.

## 🔗 Nützliche Links

* **Online-Generator**: [MikroTik Geo-Policy Generator (gehostet)](https://jbsan.fr/mikrotik-geo-counrty-generator.php)
* **GitHub-Projekt**: [sannier3/mikrotik-geoip-block-manager](https://github.com/sannier3/mikrotik-geoip-block-manager)

## 🤝 Credits

* IP-Listen gepflegt durch das [ipverse/country-ip-blocks](https://github.com/ipverse/country-ip-blocks) Projekt.
* UI erstellt mit TailwindCSS, Chart.js und jsVectorMap.

## 📄 Lizenz

Dieses Projekt ist unter der MIT-Lizenz lizenziert. Fühlen Sie sich frei, es zu forken, zu verbessern und in Ihren Infrastrukturen zu verwenden.
