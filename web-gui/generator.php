<?php
/**
 * MikroTik Geo-Policy Generator
 * Interface Web avancée avec traitement côté client (JS) et Multilingue
 */

// ==========================================
// 1. TÉLÉCHARGEMENT DU DASHBOARD DE MONITORING
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'download_dashboard') {
    $filepath = './mikrotik-monitor-default.php';
    
    if (file_exists($filepath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="mikrotik-dashboard.php"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    } else {
        die("Erreur : Le fichier de monitoring source ('mikrotik-monitor-default.php') est introuvable sur le serveur.");
    }
}
?>

<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MikroTik Geo-Block Generator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { colors: { luxBg: '#0f172a', luxPanel: '#1e293b' } } }
        }
    </script>
    <style>
        body { background-color: #0f172a; color: #e2e8f0; font-family: 'Inter', sans-serif; }
        .glass-panel { background: rgba(30, 41, 59, 0.7); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1rem; }
        .country-list::-webkit-scrollbar { width: 6px; }
        .country-list::-webkit-scrollbar-track { background: #1e293b; }
        .country-list::-webkit-scrollbar-thumb { background: #475569; border-radius: 3px; }
        textarea::-webkit-scrollbar { width: 6px; }
        textarea::-webkit-scrollbar-track { background: #1e293b; border-radius: 0.5rem; }
        textarea::-webkit-scrollbar-thumb { background: #3b82f6; border-radius: 0.5rem; }
    </style>
</head>
<body class="p-6 relative">
    
    <div class="absolute top-4 right-6 z-10 flex items-center gap-3">
        <a href="https://github.com/sannier3/mikrotik-geoip-block-manager" target="_blank" rel="noopener noreferrer" class="hidden sm:inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-600 bg-gray-800 text-xs text-gray-200 hover:bg-gray-700 hover:border-blue-500 transition shadow-lg">
            <svg class="w-4 h-4 mr-1.5" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M8 0C3.58 0 0 3.58 0 8a8 8 0 0 0 5.47 7.59c.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82a7.49 7.49 0 0 1 2-.27 7.49 7.49 0 0 1 2 .27c1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.19 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8Z"></path>
            </svg>
            <span>GitHub</span>
        </a>
        <select id="lang_selector" onchange="changeLang(this.value)" class="bg-gray-800 border border-gray-600 rounded-lg px-3 py-1.5 text-sm text-white focus:outline-none focus:border-blue-500 cursor-pointer shadow-lg">
            <option value="fr" selected>🇫🇷 Français</option>
            <option value="en">🇬🇧 English</option>
            <option value="de">🇩🇪 Deutsch</option>
            <option value="es">🇪🇸 Español</option>
            <option value="ru">🇷🇺 Русский</option>
            <option value="zh">🇨🇳 中文</option>
        </select>
    </div>

    <div class="max-w-7xl mx-auto mt-6">
        
        <header class="mb-8 text-center">
            <h1 class="text-4xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-emerald-400">MikroTik Geo-Policy Generator</h1>
            <p class="text-gray-400 mt-2"><span data-i18n="subtitle">Traitement client-side • Source IP par pays:</span> <a href="https://github.com/ipverse/country-ip-blocks" target="_blank" class="text-blue-400 hover:underline">ipverse/country-ip-blocks</a></p>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <div class="glass-panel p-6 shadow-xl flex flex-col h-[800px]">
                <h2 class="text-xl font-semibold mb-4 text-white flex items-center justify-between">
                    <span data-i18n="block_title">Sélection des Pays à Bloquer</span>
                    <span data-i18n="sync_badge" class="text-xs font-normal text-gray-400 bg-gray-800 px-2 py-1 rounded-full border border-gray-700">Synchronisé en direct</span>
                </h2>
                
                <div class="mb-4">
                    <label data-i18n="iso_label" class="block text-sm font-medium text-gray-400 mb-2">Codes ISO (Modifiez pour cocher/décocher automatiquement) :</label>
                    <textarea id="selected_codes" rows="3" class="w-full bg-gray-900 border border-gray-600 rounded-lg px-3 py-2 text-blue-300 text-sm font-mono focus:outline-none focus:border-blue-500 leading-relaxed resize-none shadow-inner" spellcheck="false"></textarea>
                </div>

                <div class="mb-4 bg-blue-900/20 border border-blue-800/50 rounded-lg p-3 text-sm text-blue-200/90 shadow-inner">
                    <p class="font-semibold text-blue-400 mb-1 flex items-center">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span data-i18n="info_title">Info : Pays sensibles et faux positifs</span>
                    </p>
                    <p data-i18n="info_text" class="text-xs leading-relaxed">
                        Certains pays hébergent des infrastructures mondiales. Les bloquer peut casser des services légitimes :<br>
                        • <strong>AU</strong> (Australie) : Héberge les IP de Cloudflare/APNIC (ex: DNS 1.1.1.1).<br>
                        • <strong>IN</strong> (Inde) : Hub IT mondial et outsourcing.<br>
                        • <strong>IL</strong> (Israël) : Hub mondial de Cybersécurité et SaaS.<br>
                        • <strong>EE</strong> (Estonie) & <strong>BG</strong> (Bulgarie) : Très intégrés à l'IT de l'UE.<br>
                        • <strong>AE</strong> (Émirats) : Gros hub AWS/Azure pour le Moyen-Orient.<br>
                        <span class="italic text-blue-400/80">Ces pays ont été volontairement exclus de la sélection par défaut.</span>
                    </p>
                </div>

                <input type="text" id="country_search" data-i18n-ph="search_ph" placeholder="Rechercher un pays dans la liste ci-dessous..." class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-2 mb-4 text-white focus:outline-none focus:border-blue-500">
                
                <div class="flex justify-between text-xs text-gray-400 mb-2 px-1">
                    <button onclick="selectAll(true)" data-i18n="btn_sel_all" class="hover:text-blue-400 transition">Tout sélectionner</button>
                    <button onclick="selectAll(false)" data-i18n="btn_desel_all" class="hover:text-red-400 transition">Tout désélectionner</button>
                </div>

                <div id="country_container" class="country-list flex-1 overflow-y-auto space-y-1 pr-2 bg-gray-800/30 rounded-lg p-2 border border-gray-700/50">
                    </div>
            </div>

            <div class="space-y-6">
                
                <div class="glass-panel p-6 shadow-xl">
                    <h2 data-i18n="wan_title" class="text-xl font-semibold mb-4 text-white">Interface WAN</h2>
                    <div class="flex gap-4 mb-3">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="radio" name="wan_type" value="in-interface-list" checked class="text-blue-600 bg-gray-800 border-gray-600 focus:ring-blue-500">
                            <span data-i18n="wan_list" class="text-sm text-gray-300">Interface List (ex: WAN)</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="radio" name="wan_type" value="in-interface" class="text-blue-600 bg-gray-800 border-gray-600 focus:ring-blue-500">
                            <span data-i18n="wan_single" class="text-sm text-gray-300">Interface simple (ex: ether1)</span>
                        </label>
                    </div>
                    <input type="text" id="wan_name" value="WAN" data-i18n-ph="wan_ph" class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-blue-500" placeholder="Nom de l'interface ou liste">
                </div>

                <div class="glass-panel p-6 shadow-xl">
                    <h2 data-i18n="opt_title" class="text-xl font-semibold mb-4 text-white">Options Globales</h2>
                    
                    <div class="mb-5">
                        <label data-i18n="opt_merge_label" class="block text-sm font-medium text-gray-300 mb-1">Comportement des Listes d'IP</label>
                        <select id="merge_mode" class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-blue-500">
                            <option value="replace" data-i18n="opt_replace" selected>Replace (Supprime l'ancienne liste et recrée, recommandé)</option>
                            <option value="keep" data-i18n="opt_keep">Keep (Conserve l'existant, ajoute les nouvelles IP)</option>
                        </select>
                    </div>

                    <div class="mb-5">
                        <label class="block text-sm font-medium text-gray-300 mb-2" data-i18n="opt_family_label">Familles IP à inclure</label>
                        <div class="space-y-2">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" id="use_ipv4" checked class="w-5 h-5 text-blue-600 bg-gray-800 border-gray-600 rounded focus:ring-blue-500">
                                <span class="text-sm text-gray-300" data-i18n="opt_ipv4">IPv4 (recommandé)</span>
                            </label>
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" id="use_ipv6" class="w-5 h-5 text-blue-600 bg-gray-800 border-gray-600 rounded focus:ring-blue-500">
                                <span class="text-sm text-gray-300" data-i18n="opt_ipv6">IPv6 (à activer si besoin)</span>
                            </label>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <label class="flex items-start space-x-3 cursor-pointer group">
                            <div class="flex items-center h-5">
                                <input type="checkbox" id="observe_all" checked class="w-5 h-5 text-blue-600 bg-gray-800 border-gray-600 rounded focus:ring-blue-500">
                            </div>
                            <div class="text-sm">
                                <span data-i18n="opt_obs" class="text-gray-200 block font-medium group-hover:text-blue-400 transition">Observer tous les autres pays</span>
                                <span data-i18n="opt_obs_desc" class="text-gray-400 text-xs">Crée des règles "Mangle Passthrough" pour comptabiliser le trafic légitime dans le Dashboard.</span>
                            </div>
                        </label>
                        
                        <label class="flex items-start space-x-3 cursor-pointer group">
                            <div class="flex items-center h-5">
                                <input type="checkbox" id="purge_rules" checked class="w-5 h-5 text-blue-600 bg-gray-800 border-gray-600 rounded focus:ring-blue-500">
                            </div>
                            <div class="text-sm">
                                <span data-i18n="opt_purge" class="text-gray-200 block font-medium group-hover:text-blue-400 transition">Purger les anciennes règles Firewall</span>
                                <span data-i18n="opt_purge_desc" class="text-gray-400 text-xs">Supprime les anciennes règles Mangle/Raw commençant par "geo-" avant d'appliquer les nouvelles.</span>
                            </div>
                        </label>
                    </div>

                    <div class="mt-5 bg-yellow-900/30 border border-yellow-700/50 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0"><svg class="h-5 w-5 text-yellow-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg></div>
                            <div class="ml-3">
                                <h3 data-i18n="alert_title" class="text-sm font-medium text-yellow-400">Alerte Performance Matérielle</h3>
                                <p data-i18n="alert_text" class="text-xs text-yellow-200/80 mt-1">Si vous utilisez un Switch (série CRS/CSS) pour du routage logiciel, cocher "Observer tous les pays" risque de saturer le CPU. Privilégiez un routeur matériel (CCR, RB, CHR).</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="glass-panel p-6 shadow-xl">
                    <button id="btn_generate" onclick="startGeneration()" class="w-full bg-gradient-to-r from-blue-600 to-emerald-500 hover:from-blue-500 hover:to-emerald-400 text-white font-bold py-3 px-4 rounded-lg shadow-lg shadow-blue-500/30 transition duration-200 text-lg">
                        <span data-i18n="btn_gen">▶ Générer le Script .rsc</span>
                    </button>
                    
                    <div id="progress_area" class="hidden mt-6 space-y-3">
                        <div class="flex justify-between text-sm text-gray-300">
                            <span id="prog_status" data-i18n="prog_init">Initialisation...</span>
                            <span id="prog_percent">0%</span>
                        </div>
                        <div class="w-full bg-gray-800 rounded-full h-2.5 overflow-hidden">
                            <div id="prog_bar" class="bg-blue-500 h-2.5 transition-all duration-300" style="width: 0%"></div>
                        </div>
                        <div class="text-xs text-gray-400 bg-gray-900/50 p-3 rounded font-mono h-24 overflow-y-auto flex flex-col-reverse" id="prog_log"></div>
                    </div>
                </div>

                <div class="glass-panel p-6 shadow-xl">
                    <h2 class="text-lg font-semibold mb-3 text-white flex items-center justify-between">
                        <span data-i18n="deploy_title">Déploiement</span>
                        <a href="?action=download_dashboard" class="text-sm bg-gray-700 hover:bg-gray-600 text-white py-1.5 px-3 rounded transition flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            <span data-i18n="deploy_btn">Fichier Dashboard PHP</span>
                        </a>
                    </h2>
                    <div class="text-sm text-gray-300 space-y-2">
                        <p data-i18n="deploy_1">1. Transférez <code>geo-policy.rsc</code> sur votre MikroTik via Winbox.</p>
                        <p data-i18n="deploy_2">2. Ouvrez le terminal. <strong>Attention : le chemin d'importation dépend de votre support de stockage (ex: flash/, disk1/, ou usb1/).</strong> Utilisez <code>verbose=yes</code> pour éviter un échec silencieux :</p>
                        <div class="bg-gray-900 border border-gray-700 p-2 rounded font-mono text-emerald-300 mt-1 text-xs">
                            /import file-name=flash/geo-policy.rsc verbose=yes
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        // --- DICTIONNAIRE DE TRADUCTION ---
        const i18n = {
            fr: {
                subtitle: "Traitement client-side • Source IP par pays:",
                block_title: "Sélection des Pays à Bloquer", sync_badge: "Synchronisé en direct",
                iso_label: "Codes ISO (Modifiez pour cocher/décocher automatiquement) :",
                info_title: "Info : Pays sensibles et faux positifs",
                info_text: "Certains pays hébergent des infrastructures mondiales. Les bloquer peut casser des services légitimes :<br>• <strong>AU</strong> (Australie) : Héberge les IP de Cloudflare/APNIC (ex: DNS 1.1.1.1).<br>• <strong>IN</strong> (Inde) : Hub IT mondial et outsourcing.<br>• <strong>IL</strong> (Israël) : Hub mondial de Cybersécurité et SaaS.<br>• <strong>EE</strong> (Estonie) & <strong>BG</strong> (Bulgarie) : Très intégrés à l'IT de l'UE.<br>• <strong>AE</strong> (Émirats) : Gros hub AWS/Azure pour le Moyen-Orient.<br><span class='italic text-blue-400/80'>Ces pays ont été volontairement exclus de la sélection par défaut.</span>",
                search_ph: "Rechercher un pays dans la liste ci-dessous...",
                btn_sel_all: "Tout sélectionner", btn_desel_all: "Tout désélectionner",
                wan_title: "Interface WAN", wan_list: "Interface List (ex: WAN)", wan_single: "Interface simple (ex: ether1)", wan_ph: "Nom de l'interface ou liste",
                opt_title: "Options Globales", opt_merge_label: "Comportement des Listes d'IP",
                opt_replace: "Replace (Supprime l'ancienne liste et recrée, recommandé)", opt_keep: "Keep (Conserve l'existant, ajoute les nouvelles IP)",
                opt_family_label: "Familles IP à inclure",
                opt_ipv4: "IPv4 (recommandé)",
                opt_ipv6: "IPv6 (à activer si besoin)",
                opt_obs: "Observer tous les autres pays", opt_obs_desc: "Crée des règles 'Mangle Passthrough' pour comptabiliser le trafic légitime dans le Dashboard.",
                opt_purge: "Purger les anciennes règles Firewall", opt_purge_desc: "Supprime les anciennes règles Mangle/Raw commençant par 'geo-' avant d'appliquer les nouvelles.",
                alert_title: "Alerte Performance Matérielle", alert_text: "Si vous utilisez un Switch (série CRS/CSS) pour du routage logiciel, cocher 'Observer tous les pays' risque de saturer le CPU. Privilégiez un routeur matériel (CCR, RB, CHR).",
                btn_gen: "▶ Générer le Script .rsc", prog_init: "Initialisation...",
                deploy_title: "Déploiement", deploy_btn: "Fichier Dashboard PHP",
                deploy_1: "1. Transférez <code>geo-policy.rsc</code> sur votre MikroTik via Winbox.",
                deploy_2: "2. Ouvrez le terminal. <strong>Attention : le chemin d'importation dépend de votre support de stockage (ex: flash/, disk1/, ou usb1/).</strong> Utilisez <code>verbose=yes</code> pour éviter un échec silencieux :",
                log_start: "Démarrage. WAN=", log_block: "Pays à bloquer : ", log_obs: " Pays à observer : ", log_purge: "Ajout des commandes de purge des anciennes règles...", log_dl: "Téléchargement : ", log_done: "Terminé ! Téléchargement en cours...", log_finish: "Génération terminée. Création du fichier rsc.", log_ready: "Prêt.", log_mode: " • Mode listes: "
            },
            en: {
                subtitle: "Client-side processing • Source IPs by country:",
                block_title: "Select Countries to Block", sync_badge: "Live Synced",
                iso_label: "ISO Codes (Edit to check/uncheck automatically):",
                info_title: "Info: Sensitive countries & false positives",
                info_text: "Some countries host global infrastructure. Blocking them might break legitimate services:<br>• <strong>AU</strong> (Australia): Hosts Cloudflare/APNIC IPs (e.g. DNS 1.1.1.1).<br>• <strong>IN</strong> (India): Global IT & outsourcing hub.<br>• <strong>IL</strong> (Israel): Global Cybersecurity & SaaS hub.<br>• <strong>EE</strong> (Estonia) & <strong>BG</strong> (Bulgaria): Highly integrated into EU IT.<br>• <strong>AE</strong> (UAE): Major AWS/Azure hub for the Middle East.<br><span class='italic text-blue-400/80'>These countries were intentionally excluded from the default selection.</span>",
                search_ph: "Search a country in the list below...",
                btn_sel_all: "Select All", btn_desel_all: "Deselect All",
                wan_title: "WAN Interface", wan_list: "Interface List (e.g. WAN)", wan_single: "Single Interface (e.g. ether1)", wan_ph: "Interface or list name",
                opt_title: "Global Options", opt_merge_label: "IP Lists Behavior",
                opt_replace: "Replace (Deletes old list and recreates, recommended)", opt_keep: "Keep (Keeps existing, appends new IPs)",
                opt_family_label: "IP families to include",
                opt_ipv4: "IPv4 (recommended)",
                opt_ipv6: "IPv6 (enable if needed)",
                opt_obs: "Observe all other countries", opt_obs_desc: "Creates 'Mangle Passthrough' rules to count legitimate traffic in the Dashboard.",
                opt_purge: "Purge old Firewall rules", opt_purge_desc: "Deletes old Mangle/Raw rules starting with 'geo-' before applying new ones.",
                alert_title: "Hardware Performance Alert", alert_text: "If you use a Switch (CRS/CSS series) for software routing, checking 'Observe all countries' may max out CPU. Use a hardware router (CCR, RB, CHR).",
                btn_gen: "▶ Generate .rsc Script", prog_init: "Initializing...",
                deploy_title: "Deployment", deploy_btn: "Dashboard PHP File",
                deploy_1: "1. Transfer <code>geo-policy.rsc</code> to your MikroTik via Winbox.",
                deploy_2: "2. Open the terminal. <strong>Warning: import path depends on your storage (e.g. flash/, disk1/, usb1/).</strong> Use <code>verbose=yes</code> to prevent silent failures:",
                log_start: "Starting. WAN=", log_block: "Countries to block: ", log_obs: " Countries to observe: ", log_purge: "Adding purge commands for old rules...", log_dl: "Downloading: ", log_done: "Done! Downloading file...", log_finish: "Generation complete. .rsc file created.", log_ready: "Ready.", log_mode: " • List mode: "
            },
            de: {
                subtitle: "Client-seitige Verarbeitung • Quell-IPs nach Land:",
                block_title: "Zu blockierende Länder auswählen", sync_badge: "Live synchronisiert",
                iso_label: "ISO-Codes (Bearbeiten für auto. Auswahl):",
                info_title: "Info: Sensible Länder & falsche Alarme",
                info_text: "Einige Länder hosten globale Infrastruktur. Das Blockieren kann legitime Dienste unterbrechen:<br>• <strong>AU</strong> (Australien): Cloudflare/APNIC IPs (z.B. DNS 1.1.1.1).<br>• <strong>IN</strong> (Indien): Globales IT/Outsourcing-Zentrum.<br>• <strong>IL</strong> (Israel): Cybersicherheits-/SaaS-Zentrum.<br>• <strong>EE</strong> (Estland) & <strong>BG</strong> (Bulgarien): Stark in die EU-IT integriert.<br>• <strong>AE</strong> (VAE): Großer AWS/Azure-Knoten für den Nahen Osten.<br><span class='italic text-blue-400/80'>Diese Länder wurden bewusst aus der Standardauswahl ausgeschlossen.</span>",
                search_ph: "Land in der Liste unten suchen...",
                btn_sel_all: "Alle auswählen", btn_desel_all: "Alle abwählen",
                wan_title: "WAN-Schnittstelle", wan_list: "Interface List (z.B. WAN)", wan_single: "Einzelnes Interface (z.B. ether1)", wan_ph: "Schnittstellen- oder Listenname",
                opt_title: "Globale Optionen", opt_merge_label: "Verhalten der IP-Listen",
                opt_replace: "Ersetzen (Löscht alte Liste und erstellt neu, empfohlen)", opt_keep: "Behalten (Behält bestehende, fügt neue IPs hinzu)",
                opt_family_label: "Einzubeziehende IP-Familien",
                opt_ipv4: "IPv4 (empfohlen)",
                opt_ipv6: "IPv6 (bei Bedarf aktivieren)",
                opt_obs: "Alle anderen Länder beobachten", opt_obs_desc: "Erstellt 'Mangle Passthrough'-Regeln, um legitimen Verkehr im Dashboard zu zählen.",
                opt_purge: "Alte Firewall-Regeln bereinigen", opt_purge_desc: "Löscht alte Mangle/Raw-Regeln, die mit 'geo-' beginnen, bevor neue angewendet werden.",
                alert_title: "Hardware-Leistungswarnung", alert_text: "Wenn Sie einen Switch (CRS/CSS-Serie) für Software-Routing verwenden, kann 'Alle Länder beobachten' die CPU auslasten. Verwenden Sie einen Hardware-Router (CCR, RB, CHR).",
                btn_gen: "▶ .rsc Skript generieren", prog_init: "Initialisierung...",
                deploy_title: "Bereitstellung", deploy_btn: "Dashboard PHP-Datei",
                deploy_1: "1. Übertragen Sie <code>geo-policy.rsc</code> über Winbox auf Ihren MikroTik.",
                deploy_2: "2. Öffnen Sie das Terminal. <strong>Achtung: Der Importpfad hängt vom Speicher ab (z.B. flash/, disk1/).</strong> Verwenden Sie <code>verbose=yes</code>:",
                log_start: "Start. WAN=", log_block: "Zu blockierende Länder: ", log_obs: " Zu beobachtende Länder: ", log_purge: "Löschbefehle für alte Regeln hinzugefügt...", log_dl: "Herunterladen: ", log_done: "Fertig! Datei wird heruntergeladen...", log_finish: "Generierung abgeschlossen. .rsc-Datei erstellt.", log_ready: "Bereit.", log_mode: " • Listenmodus: "
            },
            es: {
                subtitle: "Procesamiento en el cliente • IPs de origen por país:",
                block_title: "Seleccionar países para bloquear", sync_badge: "Sincronizado en vivo",
                iso_label: "Códigos ISO (Editar para marcar/desmarcar automáticamente):",
                info_title: "Info: Países sensibles y falsos positivos",
                info_text: "Algunos países alojan infraestructura global. Bloquearlos puede afectar servicios legítimos:<br>• <strong>AU</strong> (Australia): IPs de Cloudflare/APNIC (ej. DNS 1.1.1.1).<br>• <strong>IN</strong> (India): Centro global de TI y outsourcing.<br>• <strong>IL</strong> (Israel): Centro de Ciberseguridad y SaaS.<br>• <strong>EE</strong> (Estonia) & <strong>BG</strong> (Bulgaria): Muy integrados en la TI de la UE.<br>• <strong>AE</strong> (Emiratos): Gran nodo AWS/Azure para Medio Oriente.<br><span class='italic text-blue-400/80'>Estos países han sido excluidos deliberadamente de la selección por defecto.</span>",
                search_ph: "Buscar un país en la lista de abajo...",
                btn_sel_all: "Seleccionar todo", btn_desel_all: "Deseleccionar todo",
                wan_title: "Interfaz WAN", wan_list: "Interface List (ej. WAN)", wan_single: "Interfaz única (ej. ether1)", wan_ph: "Nombre de interfaz o lista",
                opt_title: "Opciones Globales", opt_merge_label: "Comportamiento de listas de IP",
                opt_replace: "Reemplazar (Elimina lista antigua y recrea, recomendado)", opt_keep: "Mantener (Conserva existente, añade nuevas IPs)",
                opt_family_label: "Familias IP a incluir",
                opt_ipv4: "IPv4 (recomendado)",
                opt_ipv6: "IPv6 (activar si es necesario)",
                opt_obs: "Observar todos los demás países", opt_obs_desc: "Crea reglas 'Mangle Passthrough' para contar el tráfico legítimo en el Dashboard.",
                opt_purge: "Purgar reglas antiguas de Firewall", opt_purge_desc: "Elimina reglas antiguas Mangle/Raw que empiezan por 'geo-' antes de aplicar las nuevas.",
                alert_title: "Alerta de rendimiento de hardware", alert_text: "Si usas un Switch (serie CRS/CSS) para enrutamiento por software, marcar 'Observar todos los países' puede saturar la CPU. Usa un router por hardware (CCR, RB, CHR).",
                btn_gen: "▶ Generar Script .rsc", prog_init: "Inicializando...",
                deploy_title: "Despliegue", deploy_btn: "Archivo Dashboard PHP",
                deploy_1: "1. Transfiere <code>geo-policy.rsc</code> a tu MikroTik vía Winbox.",
                deploy_2: "2. Abre la terminal. <strong>Atención: la ruta de importación depende de tu almacenamiento (ej. flash/, disk1/).</strong> Usa <code>verbose=yes</code> para evitar fallos silenciosos:",
                log_start: "Iniciando. WAN=", log_block: "Países a bloquear: ", log_obs: " Países a observar: ", log_purge: "Añadiendo comandos de purga de reglas antiguas...", log_dl: "Descargando: ", log_done: "¡Hecho! Descargando archivo...", log_finish: "Generación completa. Archivo .rsc creado.", log_ready: "Listo.", log_mode: " • Modo de listas: "
            },
            ru: {
                subtitle: "Обработка на стороне клиента • IP-адреса по странам:",
                block_title: "Выберите страны для блокировки", sync_badge: "Синхронизация онлайн",
                iso_label: "ISO коды (Измените для авто-отметки):",
                info_title: "Инфо: Чувствительные страны и ложные срабатывания",
                info_text: "Некоторые страны размещают глобальную инфраструктуру. Их блокировка может нарушить работу сервисов:<br>• <strong>AU</strong> (Австралия): IP Cloudflare/APNIC (напр. DNS 1.1.1.1).<br>• <strong>IN</strong> (Индия): Глобальный IT-хаб.<br>• <strong>IL</strong> (Израиль): Хаб кибербезопасности и SaaS.<br>• <strong>EE</strong> (Эстония) & <strong>BG</strong> (Болгария): Сильно интегрированы в IT ЕС.<br>• <strong>AE</strong> (ОАЭ): Крупный хаб AWS/Azure для Ближнего Востока.<br><span class='italic text-blue-400/80'>Эти страны намеренно исключены из выбора по умолчанию.</span>",
                search_ph: "Поиск страны в списке ниже...",
                btn_sel_all: "Выбрать все", btn_desel_all: "Снять выбор",
                wan_title: "WAN Интерфейс", wan_list: "Список интерфейсов (напр. WAN)", wan_single: "Одиночный интерфейс (напр. ether1)", wan_ph: "Имя интерфейса или списка",
                opt_title: "Глобальные параметры", opt_merge_label: "Поведение списков IP",
                opt_replace: "Заменить (Удаляет старый и создает новый, рекомендуется)", opt_keep: "Оставить (Сохраняет существующий, добавляет новые IP)",
                opt_family_label: "Включаемые IP-семейства",
                opt_ipv4: "IPv4 (рекомендуется)",
                opt_ipv6: "IPv6 (включайте при необходимости)",
                opt_obs: "Наблюдать за остальными странами", opt_obs_desc: "Создает правила 'Mangle Passthrough' для подсчета легитимного трафика в Дашборде.",
                opt_purge: "Очистить старые правила Firewall", opt_purge_desc: "Удаляет старые правила Mangle/Raw, начинающиеся с 'geo-', перед применением новых.",
                alert_title: "Предупреждение о производительности", alert_text: "Если вы используете коммутатор (серии CRS/CSS) для программной маршрутизации, включение 'Наблюдать за всеми странами' может загрузить ЦП на 100%. Используйте аппаратный маршрутизатор (CCR, RB, CHR).",
                btn_gen: "▶ Сгенерировать .rsc скрипт", prog_init: "Инициализация...",
                deploy_title: "Развертывание", deploy_btn: "PHP Дашборд",
                deploy_1: "1. Перенесите <code>geo-policy.rsc</code> на ваш MikroTik через Winbox.",
                deploy_2: "2. Откройте терминал. <strong>Внимание: путь импорта зависит от вашего накопителя (напр. flash/, disk1/).</strong> Используйте <code>verbose=yes</code>:",
                log_start: "Запуск. WAN=", log_block: "Страны для блокировки: ", log_obs: " Страны для наблюдения: ", log_purge: "Добавление команд очистки старых правил...", log_dl: "Загрузка: ", log_done: "Готово! Скачивание файла...", log_finish: "Генерация завершена. Файл .rsc создан.", log_ready: "Готов.", log_mode: " • Режим списков: "
            },
            zh: {
                subtitle: "客户端处理 • 按国家/地区分类的源 IP:",
                block_title: "选择要阻止的国家", sync_badge: "实时同步",
                iso_label: "ISO 代码（修改以自动勾选/取消勾选）：",
                info_title: "信息：敏感国家与误报",
                info_text: "一些国家托管着全球基础设施。阻止它们可能会破坏合法服务：<br>• <strong>AU</strong> (澳大利亚)：托管 Cloudflare/APNIC IP（如 DNS 1.1.1.1）。<br>• <strong>IN</strong> (印度)：全球 IT 和外包中心。<br>• <strong>IL</strong> (以色列)：全球网络安全和 SaaS 中心。<br>• <strong>EE</strong> (爱沙尼亚) & <strong>BG</strong> (保加利亚)：高度融入欧盟 IT。<br>• <strong>AE</strong> (阿联酋)：中东的主要 AWS/Azure 中心。<br><span class='italic text-blue-400/80'>这些国家/地区已从默认选择中故意排除。</span>",
                search_ph: "在下面的列表中搜索国家...",
                btn_sel_all: "全选", btn_desel_all: "取消全选",
                wan_title: "WAN 接口", wan_list: "接口列表 (如: WAN)", wan_single: "单一接口 (如: ether1)", wan_ph: "接口或列表名称",
                opt_title: "全局选项", opt_merge_label: "IP 列表行为",
                opt_replace: "替换 (删除旧列表并重新创建，推荐)", opt_keep: "保留 (保留现有列表，追加新 IP)",
                opt_family_label: "要包含的 IP 协议族",
                opt_ipv4: "IPv4（推荐）",
                opt_ipv6: "IPv6（按需启用）",
                opt_obs: "观察所有其他国家", opt_obs_desc: "创建 'Mangle Passthrough' 规则，以便在仪表板中计算合法流量。",
                opt_purge: "清除旧的防火墙规则", opt_purge_desc: "在应用新规则之前，删除以 'geo-' 开头的旧 Mangle/Raw 规则。",
                alert_title: "硬件性能警告", alert_text: "如果您使用交换机（CRS/CSS 系列）进行软件路由，勾选“观察所有国家”可能会使 CPU 满载。请使用硬件路由器 (CCR, RB, CHR)。",
                btn_gen: "▶ 生成 .rsc 脚本", prog_init: "初始化中...",
                deploy_title: "部署", deploy_btn: "仪表板 PHP 文件",
                deploy_1: "1. 通过 Winbox 将 <code>geo-policy.rsc</code> 传输到您的 MikroTik。",
                deploy_2: "2. 打开终端。<strong>警告：导入路径取决于您的存储空间（例如 flash/, disk1/）。</strong> 使用 <code>verbose=yes</code> 以防止无提示失败：",
                log_start: "开始。 WAN=", log_block: "要阻止的国家：", log_obs: " 要观察的国家：", log_purge: "添加旧规则的清除命令...", log_dl: "下载中：", log_done: "完成！正在下载文件...", log_finish: "生成完毕。.rsc 文件已创建。", log_ready: "准备就绪。", log_mode: " • 列表模式: "
            }
        };

        let currentLang = 'fr';

        function t(key) {
            return i18n[currentLang][key] || key;
        }

        function changeLang(lang) {
            currentLang = lang;
            
            // Text Content Translation
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const key = el.getAttribute('data-i18n');
                if (i18n[lang][key]) el.innerHTML = i18n[lang][key];
            });

            // Placeholder Translation
            document.querySelectorAll('[data-i18n-ph]').forEach(el => {
                const key = el.getAttribute('data-i18n-ph');
                if (i18n[lang][key]) el.setAttribute('placeholder', i18n[lang][key]);
            });

            // Update Prog Status if not generating
            if (document.getElementById('btn_generate').disabled === false) {
                document.getElementById('prog_status').innerText = t('log_ready');
            }

            // Appliquer le preset de blocage associé à la langue
            applyPresetForLang(lang);
        }
        // --- FIN DU DICTIONNAIRE ---

        // Presets de pays bloqués par langue
        const presetsByLang = {
            fr: ["af","dz","ao","ag","ar","am","az","bh","bd","by","bj","bt","bo","bw","br","bn","bf","bi","kh","cm","cv","cf","td","cl","cn","co","ci","cu","cy","cd","dj","do","ec","ee","eg","sv","gq","er","sz","et","fj","ga","gm","ge","gh","gr","gt","gn","gw","gy","hk","id","ir","iq","jo","kz","ke","kw","kg","la","lb","ls","lr","lu","ly","mg","mw","ml","mr","mu","mx","mn","ma","mz","mm","na","np","ni","ne","ng","kp","om","pk","pa","py","pe","ph","qa","cg","ru","rw","sa","sn","sl","so","za","ss","lk","sd","sr","sy","tj","tz","tg","tn","tm","ug","uy","uz","ve","vn","ye","zm","zw"],
            en: ["af","dz","ad","ao","ar","am","az","by","bj","bt","bo","br","bn","bf","bi","kh","cv","cf","td","cl","cn","co","cu","cy","cd","dj","do","ec","eg","sv","gq","er","sz","et","ga","ge","gr","gt","gn","gw","gy","id","ir","iq","jo","kz","kg","la","lb","ls","lr","ly","mg","mw","ml","mr","mu","mx","mn","ma","mz","mm","ne","kp","om","pa","py","pe","qa","cg","ru","rw","sa","sn","sl","so","ss","lk","sd","sr","sy","tj","tz","tg","tn","tm","uz","ve","vn","ye","zm","zw"],
            es: ["af","dz","ad","ao","am","az","bh","bd","by","bj","bt","bn","bf","bi","kh","cm","cv","cf","td","cn","ci","cy","cd","dj","eg","gq","er","sz","et","fj","ga","gm","ge","gh","gr","gn","gw","gy","hk","id","ir","iq","jo","kz","ke","kw","kg","la","lb","ls","lr","ly","mg","mw","ml","mr","mu","mn","ma","mz","mm","na","np","ne","ng","kp","om","pk","ph","qa","cg","ru","rw","sa","sn","sl","so","za","ss","lk","sd","sr","sy","tj","tz","tg","tn","tm","ug","uz","vn","ye","zm","zw"],
            de: ["af","dz","ao","ag","ar","am","az","bh","bd","by","bj","bt","bo","bw","br","bn","bf","bi","kh","cm","cv","cf","td","cl","cn","co","ci","cu","cy","cd","dj","do","ec","eg","sv","gq","er","sz","et","fj","ga","gm","ge","gh","gr","gt","gn","gw","gy","hk","id","ir","iq","jo","kz","ke","kw","kg","la","lb","ls","lr","ly","mg","mw","ml","mr","mu","mx","mn","ma","mz","mm","na","np","ni","ne","ng","kp","om","pk","pa","py","pe","ph","qa","cg","ru","rw","sa","sn","sl","so","za","ss","lk","sd","sr","sy","tj","tz","tg","tn","tm","ug","uy","uz","ve","vn","ye","zm","zw"],
            zh: ["af","dz","ad","ao","ag","ar","am","az","bh","bd","by","bj","bt","bo","bw","br","bn","bf","bi","kh","cm","cv","cf","td","cl","co","ci","cu","cy","cd","dj","do","ec","eg","sv","gq","er","sz","et","fj","ga","gm","ge","gh","gr","gt","gn","gw","gy","id","ir","iq","jo","kz","ke","kw","kg","la","lb","ls","lr","ly","mg","mw","ml","mr","mu","mx","mn","ma","mz","mm","na","np","ni","ne","ng","kp","om","pk","pa","py","pe","ph","qa","cg","ru","rw","sa","sn","sl","so","za","ss","lk","sd","sr","sy","tj","tz","tg","tn","tm","ug","uy","uz","ve","vn","ye","zm","zw"],
            ru: ["af","dz","ad","ao","ag","ar","bh","bd","bj","bt","bo","bw","br","bn","bf","bi","kh","cm","cv","cf","td","cl","cn","co","ci","cu","cy","cd","dj","do","ec","eg","sv","gq","er","sz","et","fj","ga","gm","ge","gh","gr","gt","gn","gw","gy","hk","id","ir","iq","jo","ke","kw","la","lb","ls","lr","ly","mg","mw","ml","mr","mu","mx","mn","ma","mz","mm","na","np","ni","ne","ng","kp","om","pk","pa","py","pe","ph","qa","cg","rw","sa","sn","sl","so","za","ss","lk","sd","sr","sy","tz","tg","tn","ug","uy","ve","vn","ye","zm","zw"]
        };

        const countriesDict = { "ad":"Andorra", "ae":"United Arab Emirates", "af":"Afghanistan", "ag":"Antigua and Barbuda", "al":"Albania", "am":"Armenia", "ao":"Angola", "ar":"Argentina", "at":"Austria", "au":"Australia", "az":"Azerbaijan", "ba":"Bosnia and Herzegovina", "bb":"Barbados", "bd":"Bangladesh", "be":"Belgium", "bf":"Burkina Faso", "bg":"Bulgaria", "bh":"Bahrain", "bi":"Burundi", "bj":"Benin", "bn":"Brunei", "bo":"Bolivia", "br":"Brazil", "bs":"Bahamas", "bt":"Bhutan", "bw":"Botswana", "by":"Belarus", "bz":"Belize", "ca":"Canada", "cd":"Democratic Republic of the Congo", "cf":"Central African Republic", "cg":"Republic of the Congo", "ch":"Switzerland", "ci":"Cote d'Ivoire", "cl":"Chile", "cm":"Cameroon", "cn":"China", "co":"Colombia", "cr":"Costa Rica", "cu":"Cuba", "cv":"Cape Verde", "cy":"Cyprus", "cz":"Czechia", "de":"Germany", "dj":"Djibouti", "dk":"Denmark", "dm":"Dominica", "do":"Dominican Republic", "dz":"Algeria", "ec":"Ecuador", "ee":"Estonia", "eg":"Egypt", "er":"Eritrea", "es":"Spain", "et":"Ethiopia", "fi":"Finland", "fj":"Fiji", "fr":"France", "ga":"Gabon", "gb":"United Kingdom", "ge":"Georgia", "gh":"Ghana", "gm":"Gambia", "gn":"Guinea", "gq":"Equatorial Guinea", "gr":"Greece", "gt":"Guatemala", "gw":"Guinea-Bissau", "gy":"Guyana", "hk":"Hong Kong", "hn":"Honduras", "hr":"Croatia", "ht":"Haiti", "hu":"Hungary", "id":"Indonesia", "ie":"Ireland", "il":"Israel", "in":"India", "iq":"Iraq", "ir":"Iran", "is":"Iceland", "it":"Italy", "jm":"Jamaica", "jo":"Jordan", "jp":"Japan", "ke":"Kenya", "kg":"Kyrgyzstan", "kh":"Cambodia", "kp":"North Korea", "kr":"South Korea", "kw":"Kuwait", "kz":"Kazakhstan", "la":"Laos", "lb":"Lebanon", "lk":"Sri Lanka", "lr":"Liberia", "ls":"Lesotho", "lt":"Lithuania", "lu":"Luxembourg", "lv":"Latvia", "ly":"Libya", "ma":"Morocco", "md":"Moldova", "me":"Montenegro", "mg":"Madagascar", "mk":"North Macedonia", "ml":"Mali", "mm":"Myanmar", "mn":"Mongolia", "mr":"Mauritania", "mt":"Malta", "mu":"Mauritius", "mv":"Maldives", "mw":"Malawi", "mx":"Mexico", "my":"Malaysia", "mz":"Mozambique", "na":"Namibia", "ne":"Niger", "ng":"Nigeria", "ni":"Nicaragua", "nl":"Netherlands", "no":"Norway", "np":"Nepal", "nz":"New Zealand", "om":"Oman", "pa":"Panama", "pe":"Peru", "ph":"Philippines", "pk":"Pakistan", "pl":"Poland", "pt":"Portugal", "py":"Paraguay", "qa":"Qatar", "ro":"Romania", "rs":"Serbia", "ru":"Russia", "rw":"Rwanda", "sa":"Saudi Arabia", "sd":"Sudan", "se":"Sweden", "sg":"Singapore", "si":"Slovenia", "sk":"Slovakia", "sl":"Sierra Leone", "sn":"Senegal", "so":"Somalia", "sr":"Suriname", "ss":"South Sudan", "sv":"El Salvador", "sy":"Syria", "sz":"Eswatini", "td":"Chad", "tg":"Togo", "th":"Thailand", "tj":"Tajikistan", "tm":"Turkmenistan", "tn":"Tunisia", "tr":"Turkey", "tt":"Trinidad and Tobago", "tw":"Taiwan", "tz":"Tanzania", "ua":"Ukraine", "ug":"Uganda", "us":"United States", "uy":"Uruguay", "uz":"Uzbekistan", "ve":"Venezuela", "vn":"Vietnam", "ye":"Yemen", "za":"South Africa", "zm":"Zambia", "zw":"Zimbabwe" };
        
        let countryCheckboxes = {};
        const container = document.getElementById('country_container');
        const textInput = document.getElementById('selected_codes');

        Object.keys(countriesDict).sort((a,b) => countriesDict[a].localeCompare(countriesDict[b])).forEach(cc => {
            const name = countriesDict[cc];
            const div = document.createElement('div');
            div.className = 'flex items-center p-2 hover:bg-gray-700/50 rounded cursor-pointer transition';
            
            div.innerHTML = `
                <input type="checkbox" id="chk_${cc}" value="${cc}" class="w-4 h-4 text-red-500 bg-gray-900 border-gray-600 rounded focus:ring-red-500 mr-3 pointer-events-none">
                <img src="https://flagcdn.com/20x15/${cc}.png" class="w-5 mr-2 rounded-sm opacity-80" onerror="this.style.display='none'">
                <label class="text-sm text-gray-300 flex-1 cursor-pointer select-none">${name} <span class="text-gray-500 ml-1">(${cc.toUpperCase()})</span></label>
            `;
            
            div.onclick = () => { 
                const cb = document.getElementById(`chk_${cc}`); 
                cb.checked = !cb.checked; 
                syncTextFromCheckboxes(); 
            };
            container.appendChild(div);
            countryCheckboxes[cc] = div;
        });

        function applyPresetForLang(lang) {
            const preset = presetsByLang[lang];
            if (!preset) return;
            const codes = new Set(preset);
            Object.keys(countryCheckboxes).forEach(cc => {
                const cb = document.getElementById(`chk_${cc}`);
                cb.checked = codes.has(cc);
            });
            syncTextFromCheckboxes();
        }

        function syncTextFromCheckboxes() {
            const selected = [];
            Object.keys(countryCheckboxes).forEach(cc => {
                if (document.getElementById(`chk_${cc}`).checked) selected.push(cc);
            });
            textInput.value = selected.join(', ');
        }

        textInput.addEventListener('input', (e) => {
            const codes = e.target.value.toLowerCase().split(/[,\s]+/).map(c => c.trim()).filter(c => c.length === 2);
            Object.keys(countryCheckboxes).forEach(cc => {
                const cb = document.getElementById(`chk_${cc}`);
                cb.checked = codes.includes(cc);
            });
        });

        applyPresetForLang(currentLang);

        document.getElementById('country_search').addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            Object.keys(countryCheckboxes).forEach(cc => {
                const div = countryCheckboxes[cc];
                const name = countriesDict[cc].toLowerCase();
                if (name.includes(term) || cc.includes(term)) div.style.display = 'flex';
                else div.style.display = 'none';
            });
        });

        function selectAll(check) {
            Object.keys(countryCheckboxes).forEach(cc => {
                if(countryCheckboxes[cc].style.display !== 'none') {
                    document.getElementById(`chk_${cc}`).checked = check;
                }
            });
            syncTextFromCheckboxes();
        }

        function logProgress(msg, color = 'text-gray-400') {
            const logBox = document.getElementById('prog_log');
            const div = document.createElement('div');
            div.className = color;
            div.innerText = `[${new Date().toLocaleTimeString()}] ${msg}`;
            logBox.prepend(div);
        }

        async function fetchPrefixes(cc, family) {
            try {
                const res = await fetch(`https://raw.githubusercontent.com/ipverse/country-ip-blocks/master/country/${cc}/${family}-aggregated.txt`);
                if (!res.ok) return [];
                const text = await res.text();
                return text.split('\n').filter(line => line.trim() && !line.startsWith('#')).map(line => line.trim());
            } catch (e) { return []; }
        }

        async function startGeneration() {
            document.getElementById('btn_generate').disabled = true;
            document.getElementById('btn_generate').classList.add('opacity-50', 'cursor-not-allowed');
            document.getElementById('progress_area').classList.remove('hidden');
            document.getElementById('prog_log').innerHTML = '';
            
            const wanType = document.querySelector('input[name="wan_type"]:checked').value;
            const wanName = document.getElementById('wan_name').value.trim() || 'WAN';
            const mode = document.getElementById('merge_mode').value;
            const doPurge = document.getElementById('purge_rules').checked;
            const doObserve = document.getElementById('observe_all').checked;
            const useIPv4 = document.getElementById('use_ipv4').checked;
            const useIPv6 = document.getElementById('use_ipv6').checked;

            if (!useIPv4 && !useIPv6) {
                alert('Veuillez sélectionner au moins une famille IP (IPv4 ou IPv6).');
                document.getElementById('btn_generate').disabled = false;
                document.getElementById('btn_generate').classList.remove('opacity-50', 'cursor-not-allowed');
                return;
            }

            const blockSet = [];
            Object.keys(countriesDict).forEach(cc => {
                if (document.getElementById(`chk_${cc}`).checked) blockSet.push(cc);
            });

            const obsSet = doObserve ? Object.keys(countriesDict).filter(cc => !blockSet.includes(cc)) : [];
            const totalTasks = blockSet.length + obsSet.length;

            logProgress(`${t('log_start')}${wanType} "${wanName}"${t('log_mode')}${mode}`);
            logProgress(`${t('log_block')}${blockSet.length}${t('log_obs')}${obsSet.length}`);

            let rsc = "# ============================================================\n";
            rsc += "# Generated MikroTik script - Source: ipverse country-ip-blocks\n";
            rsc += "# ============================================================\n\n";
            rsc += ":put \"Generated geo policy import starting\"\n\n";

            if (doPurge) {
                logProgress(t('log_purge'));
                rsc += ":put \"Purging old geo-* rules\"\n";
                rsc += "/ip firewall raw remove [find where comment~\"^geo-\"]\n";
                rsc += "/ipv6 firewall raw remove [find where comment~\"^geo-\"]\n";
                rsc += "/ip firewall mangle remove [find where comment~\"^geo-\"]\n";
                rsc += "/ipv6 firewall mangle remove [find where comment~\"^geo-\"]\n\n";
            }

            let processed = 0;
            const groups = [{ type: 'BLOCK', list: blockSet }, { type: 'OBSERVE', list: obsSet }];

            for (const group of groups) {
                for (const cc of group.list) {
                    const cname = countriesDict[cc].toUpperCase();
                    document.getElementById('prog_status').innerText = `${t('log_dl')}${cname}...`;
                    const v4 = useIPv4 ? await fetchPrefixes(cc, 'ipv4') : [];
                    const v6 = useIPv6 ? await fetchPrefixes(cc, 'ipv6') : [];
                    
                    logProgress(`${group.type} ${cname} : ${v4.length} IP(v4) / ${v6.length} IP(v6)`, group.type==='BLOCK' ? 'text-red-400' : 'text-emerald-400');

                    rsc += `:put "${group.type} ${cname} : IPv4=${v4.length} IPv6=${v6.length}"\n`;

                    if (v4.length > 0) {
                        const listName = `geo-country-${cc}-v4`;
                        if (mode === 'replace') rsc += `/ip firewall address-list remove [find where list="${listName}"]\n`;
                        v4.forEach(ip => {
                            if (mode === 'replace') rsc += `/ip firewall address-list add list="${listName}" address=${ip} comment="${cname}"\n`;
                            else rsc += `:if ([:len [/ip firewall address-list find where list="${listName}" and address=${ip}]] = 0) do={ /ip firewall address-list add list="${listName}" address=${ip} comment="${cname}" }\n`;
                        });
                        
                        const comment = `geo-${group.type==='BLOCK'?'block':'ip'}-${cc}-v4 ${cname}`;
                        if (group.type === 'BLOCK') rsc += `:if ([:len [/ip firewall raw find where comment="${comment}"]] = 0) do={ /ip firewall raw add chain=prerouting ${wanType}="${wanName}" src-address-list="${listName}" action=drop comment="${comment}" }\n`;
                        else rsc += `:if ([:len [/ip firewall mangle find where comment="${comment}"]] = 0) do={ /ip firewall mangle add chain=prerouting ${wanType}="${wanName}" connection-state=new src-address-list="${listName}" action=passthrough comment="${comment}" }\n`;
                        rsc += "\n";
                    }

                    if (v6.length > 0) {
                        const listName = `geo-country-${cc}-v6`;
                        if (mode === 'replace') rsc += `/ipv6 firewall address-list remove [find where list="${listName}"]\n`;
                        v6.forEach(ip => {
                            if (mode === 'replace') rsc += `/ipv6 firewall address-list add list="${listName}" address=${ip} comment="${cname}"\n`;
                            else rsc += `:if ([:len [/ipv6 firewall address-list find where list="${listName}" and address=${ip}]] = 0) do={ /ipv6 firewall address-list add list="${listName}" address=${ip} comment="${cname}" }\n`;
                        });
                        
                        const comment = `geo-${group.type==='BLOCK'?'block':'ip'}-${cc}-v6 ${cname}`;
                        if (group.type === 'BLOCK') rsc += `:if ([:len [/ipv6 firewall raw find where comment="${comment}"]] = 0) do={ /ipv6 firewall raw add chain=prerouting ${wanType}="${wanName}" src-address-list="${listName}" action=drop comment="${comment}" }\n`;
                        else rsc += `:if ([:len [/ipv6 firewall mangle find where comment="${comment}"]] = 0) do={ /ipv6 firewall mangle add chain=prerouting ${wanType}="${wanName}" connection-state=new src-address-list="${listName}" action=passthrough comment="${comment}" }\n`;
                        rsc += "\n";
                    }

                    processed++;
                    const pct = Math.round((processed / totalTasks) * 100);
                    document.getElementById('prog_percent').innerText = `${pct}%`;
                    document.getElementById('prog_bar').style.width = `${pct}%`;
                }
            }

            rsc += ":put \"Generated geo policy import done\"\n";
            document.getElementById('prog_status').innerText = t('log_done');
            logProgress(t('log_finish'), "text-blue-400 font-bold");

            const blob = new Blob([rsc], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = 'geo-policy.rsc';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);

            setTimeout(() => {
                document.getElementById('btn_generate').disabled = false;
                document.getElementById('btn_generate').classList.remove('opacity-50', 'cursor-not-allowed');
                document.getElementById('prog_status').innerText = t('log_ready');
            }, 2000);
        }

        // Init language on load to replace placeholders and default text cleanly if needed
        changeLang('fr');
    </script>
</body>
</html>