# 🌍 MikroTik Geo IP script Generator & Live Dashboard

**__Readme Languages__** [![Français](https://img.shields.io/badge/lang-Français-blue.svg)](README.md)
[![English](https://img.shields.io/badge/lang-English-lightgrey.svg)](README.en.md)
[![Deutsch](https://img.shields.io/badge/lang-Deutsch-lightgrey.svg)](README.de.md)
[![Español](https://img.shields.io/badge/lang-Español-lightgrey.svg)](README.es.md)
[![Русский](https://img.shields.io/badge/lang-Русский-lightgrey.svg)](README.ru.md)
[![中文](https://img.shields.io/badge/lang-中文-lightgrey.svg)](README.zh.md)


![RouterOS](https://img.shields.io/badge/RouterOS-v7.1%2B-blue?style=flat-square&logo=mikrotik)
![PHP](https://img.shields.io/badge/PHP-7.4%20%7C%208.x-777BB4?style=flat-square&logo=php)
![License](https://img.shields.io/badge/License-MIT-success?style=flat-square)

Une suite d'outils complète pour générer des règles de blocage géographique (Geo-IP) sur les routeurs MikroTik (IPv4 & IPv6) et superviser le trafic bloqué/autorisé en temps réel grâce à un tableau de bord accessible.



## ✨ Fonctionnalités

* **Générateur de Script (.rsc) :** Créez automatiquement des *Address Lists* et des règles Firewall (`raw` pour bloquer, `mangle` pour observer) basées sur les bases de données IP à jour.
* **Double Approche :** Utilisez l'interface Web conviviale (`generator.php`) ou le script CLI (`bash-cli`) pour vos automatisations (cron).
* **Live Dashboard :** Une page unique PHP/JS sans dépendances pour visualiser votre trafic.
  * ⏱️ Rafraîchissement en temps réel (5 secondes).
  * 🗺️ Carte d'intensité mondiale.
  * 📈 Graphiques de bande passante (Kbps) et de répartition des protocoles (IPv4 vs IPv6).
* **Multilingue :** L'interface Web et le Dashboard sont disponibles en 6 langues (🇫🇷, 🇬🇧, 🇩🇪, 🇪🇸, 🇷🇺, 🇨🇳).
* **Sécurité Anti-Faux Positifs :** Exclusion par défaut des pays hébergeant des infrastructures critiques (ex: Cloudflare 1.1.1.1 en Australie, hubs AWS/Azure).

## 🚀 Installation & Déploiement

### Prérequis
* Un routeur MikroTik sous **RouterOS v7.1 ou supérieur** (requis pour l'API REST).
* Le service `www` ou `www-ssl` activé sur le routeur (`/ip service`).
* Un serveur Web avec PHP et l'extension `cURL` activée.

### Étape 1 : Générer le script de filtrage

1. Uploadez le dossier `web-gui` sur votre serveur web.
2. Accédez à `generator.php` via votre navigateur.
3. Sélectionnez votre interface WAN et les pays à bloquer.
4. Cliquez sur **Générer le Script .rsc**. L'outil téléchargera les IP depuis [ipverse](https://github.com/ipverse/country-ip-blocks) et compilera la syntaxe MikroTik.

> **💡 Note Matérielle :** Si vous utilisez un Switch (série CRS/CSS) pour du routage logiciel, limitez le nombre de pays à observer pour éviter de surcharger le processeur. Idéalement, utilisez un routeur matériel (série CCR, RB, CHR).

### Étape 2 : Importer sur le MikroTik
1. Transférez le fichier `geo-policy.rsc` généré sur votre routeur via Winbox (Glisser-Déposer dans *Files*).
2. Ouvrez le terminal MikroTik et importez le fichier. 
   **Attention :** Spécifiez le bon support de stockage (ex: `flash/`) et utilisez le mode `verbose` pour repérer toute erreur :
   ```routeros
   /import file-name=flash/geo-policy.rsc verbose=yes

### Étape 3 : Configurer le Dashboard Live

1. Depuis `generator.php`, téléchargez le fichier de monitoring en cliquant sur **Fichier Dashboard PHP**. (Il s'agit du fichier `mikrotik-monitor-default.php`).
2. Ouvrez ce fichier avec un éditeur de texte et configurez vos identifiants tout en haut :
```php
$MK_IP = '192.168.88.1'; // IP de votre routeur
$MK_USER = 'api_user';   // Utilisateur (lecture seule recommandée)
$MK_PASS = 'password';   // Mot de passe
$USE_HTTPS = false;      // Passez à true si vous utilisez un certificat SSL sur RouterOS

```

3. Hébergez ce fichier sur votre serveur et profitez du spectacle !

## 🛡️ À propos des Pays Sensibles

Bloquer "tout le monde sauf son pays" est souvent une mauvaise idée. Par défaut, le générateur exclut certains pays pour éviter de casser des services légitimes :

* **AU (Australie) :** IP Cloudflare / APNIC.
* **IN (Inde) & IL (Israël) :** Hubs IT, Cyber et Outsourcing mondiaux.
* **AE (Émirats) :** Nœuds majeurs AWS / Azure.
* **EE (Estonie) & BG (Bulgarie) :** Fortement intégrés dans l'IT européenne.

## 🤝 Crédits

* Listes d'IP maintenues par le projet [ipverse/country-ip-blocks](https://github.com/ipverse/country-ip-blocks).
* UI construite avec TailwindCSS, Chart.js et jsVectorMap.

## 📄 Licence

Ce projet est sous licence MIT. Sentez-vous libre de le forker, de l'améliorer et de l'utiliser dans vos infrastructures en me men
