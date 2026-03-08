<?php
/**
 * MikroTik Geo-Dashboard - Single File Solution
 * Nécessite RouterOS v7.1+ (REST API)
 */

// ==========================================
// 1. CONFIGURATION
// ==========================================
$MK_IP = '192.168.88.1'; // IP du MikroTik
$MK_USER = 'admin';      // Utilisateur API
$MK_PASS = 'admin';      // Mot de passe
$USE_HTTPS = false;      // Mettre true si www-ssl est configuré

// ==========================================
// 2. BACKEND (API MIKROTIK)
// ==========================================
if (isset($_GET['fetch_data'])) {
    header('Content-Type: application/json');

    function fetchMikrotik($endpoint) {
        global $MK_IP, $MK_USER, $MK_PASS, $USE_HTTPS;
        $protocol = $USE_HTTPS ? 'https' : 'http';
        $ch = curl_init("$protocol://$MK_IP/rest/$endpoint");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "$MK_USER:$MK_PASS");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result ? json_decode($result, true) : [];
    }

    $endpoints = [
        'block_v4' => 'ip/firewall/raw',
        'block_v6' => 'ipv6/firewall/raw',
        'obs_v4'   => 'ip/firewall/mangle',
        'obs_v6'   => 'ipv6/firewall/mangle'
    ];

    $data = [];
    foreach ($endpoints as $key => $uri) {
        $rules = fetchMikrotik($uri);
        if (!is_array($rules)) continue;
        
        foreach ($rules as $rule) {
            if (empty($rule['comment'])) continue;
            $comment = $rule['comment'];
            
            if (preg_match('/^geo-(block|ip)-([a-z]{2})-(v4|v6)\s+(.*)$/', $comment, $matches)) {
                $action = $matches[1] === 'block' ? 'blocked' : 'observed';
                $cc = strtoupper($matches[2]);
                $ip_version = $matches[3];
                $country_name = $matches[4];
                $bytes = (int)($rule['bytes'] ?? 0);
                $packets = (int)($rule['packets'] ?? 0);
                
                if (!isset($data[$cc])) {
                    $data[$cc] = [
                        'name' => $country_name, 
                        'blocked_v4_b' => 0, 'blocked_v6_b' => 0, 
                        'observed_v4_b' => 0, 'observed_v6_b' => 0,
                        'blocked_v4_p' => 0, 'blocked_v6_p' => 0, 
                        'observed_v4_p' => 0, 'observed_v6_p' => 0,
                        'total_bytes' => 0, 'total_packets' => 0
                    ];
                }
                
                $data[$cc]["{$action}_{$ip_version}_b"] += $bytes;
                $data[$cc]["{$action}_{$ip_version}_p"] += $packets;
                $data[$cc]['total_bytes'] += $bytes;
                $data[$cc]['total_packets'] += $packets;
            }
        }
    }
    
    echo json_encode(['timestamp' => time(), 'countries' => $data]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MikroTik Geo-Traffic Analyzer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { colors: { luxBg: '#0f172a', luxPanel: '#1e293b', luxAccent: '#3b82f6' } } }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsvectormap/dist/css/jsvectormap.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/jsvectormap"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsvectormap/dist/maps/world.js"></script>
    
    <style>
        body { background-color: #0f172a; color: #e2e8f0; font-family: 'Inter', sans-serif; }
        .glass-panel { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1rem; }
        #map { width: 100%; height: 400px; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #1e293b; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #64748b; }
    </style>
</head>
<body class="p-6 relative">

    <div class="absolute top-4 right-6 z-10">
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
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-emerald-400">Geo-Traffic Overview</h1>
                <p data-i18n="subtitle" class="text-gray-400 text-sm mt-1">Analyse du trafic global MikroTik (Live 5s)</p>
            </div>
            <div class="flex items-center gap-3">
                <span id="sync-status" class="flex items-center text-emerald-400 text-sm font-medium"></span>
                <button onclick="loadData()" data-i18n="btn_refresh" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 rounded-lg text-white font-semibold transition shadow-lg shadow-blue-500/30">
                    Force Refresh
                </button>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="glass-panel p-6">
                <h3 data-i18n="kpi_block_title" class="text-gray-400 text-sm uppercase tracking-wider">Trafic Bloqué Total</h3>
                <p class="text-3xl font-bold text-red-400 mt-2" id="kpi-blocked">0 B</p>
                <p class="text-xs text-gray-500 mt-1" id="kpi-blocked-pkts"><span class="val">0</span> <span data-i18n="pkts">paquets</span></p>
            </div>
            <div class="glass-panel p-6">
                <h3 data-i18n="kpi_obs_title" class="text-gray-400 text-sm uppercase tracking-wider">Trafic Autorisé Total</h3>
                <p class="text-3xl font-bold text-emerald-400 mt-2" id="kpi-observed">0 B</p>
                <p class="text-xs text-gray-500 mt-1" id="kpi-observed-pkts"><span class="val">0</span> <span data-i18n="pkts">paquets</span></p>
            </div>
            <div class="glass-panel p-6">
                <h3 data-i18n="kpi_cc_title" class="text-gray-400 text-sm uppercase tracking-wider">Pays Identifiés</h3>
                <p class="text-3xl font-bold text-blue-400 mt-2" id="kpi-countries">0</p>
            </div>
            <div class="glass-panel p-6">
                <h3 data-i18n="kpi_ratio_title" class="text-gray-400 text-sm uppercase tracking-wider">Ratio IPv4 / IPv6</h3>
                <p class="text-3xl font-bold text-purple-400 mt-2" id="kpi-ratio">0% / 0%</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="glass-panel p-6 lg:col-span-2">
                <h2 data-i18n="map_title" class="text-xl font-semibold mb-4 text-white">Densité de Trafic Global</h2>
                <div id="map"></div>
            </div>

            <div class="glass-panel p-6 flex flex-col items-center justify-center">
                <h2 data-i18n="pie_title" class="text-xl font-semibold mb-4 text-white w-full">Répartition Protocole</h2>
                <div class="relative w-full max-w-[250px]">
                    <canvas id="protocolChart"></canvas>
                </div>
            </div>
        </div>

        <div class="glass-panel p-6 mb-8">
            <h2 data-i18n="bar_title" class="text-xl font-semibold mb-4 text-white">Top 10 Pays (Bloqués vs Autorisés)</h2>
            <div class="relative w-full h-80">
                <canvas id="topCountriesChart"></canvas>
            </div>
        </div>

        <div class="glass-panel p-6 mb-8">
            <h2 class="text-xl font-semibold mb-1 text-white flex items-center">
                <span data-i18n="line_title">Bande Passante en Temps Réel</span>
                <span data-i18n="line_badge" class="ml-3 text-xs bg-blue-500/20 text-blue-400 px-2 py-1 rounded">Mise à jour 5s</span>
            </h2>
            <p data-i18n="line_desc" class="text-gray-400 text-sm mb-4">Vitesse du trafic traité par les règles (Kbps)</p>
            <div class="relative w-full h-64">
                <canvas id="liveBandwidthChart"></canvas>
            </div>
        </div>

        <div class="glass-panel p-6 mb-8">
            <h2 data-i18n="table_title" class="text-xl font-semibold mb-4 text-white">Données Détaillées par Pays</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-700 text-gray-400 text-sm uppercase tracking-wider">
                            <th data-i18n="th_cc" class="py-3 px-4">Pays</th>
                            <th data-i18n="th_b_bytes" class="py-3 px-4 text-right text-red-400">Bloqué (Octets)</th>
                            <th data-i18n="th_b_pkts" class="py-3 px-4 text-right text-red-400">Bloqué (Pkt)</th>
                            <th data-i18n="th_o_bytes" class="py-3 px-4 text-right text-emerald-400">Autorisé (Octets)</th>
                            <th data-i18n="th_o_pkts" class="py-3 px-4 text-right text-emerald-400">Autorisé (Pkt)</th>
                            <th data-i18n="th_ratio" class="py-3 px-4 w-48 text-center text-purple-400">Ratio IPv4 / IPv6</th>
                            <th data-i18n="th_total" class="py-3 px-4 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody id="table-body" class="text-sm">
                        </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        // --- DICTIONNAIRE MULTILINGUE ---
        const i18n = {
            fr: {
                subtitle: "Analyse du trafic global MikroTik (Live 5s)", btn_refresh: "Rafraîchir", sync_live: "Live Sync", sync_err: "Erreur Sync",
                kpi_block_title: "Trafic Bloqué Total", kpi_obs_title: "Trafic Autorisé Total", kpi_cc_title: "Pays Identifiés", kpi_ratio_title: "Ratio IPv4 / IPv6", pkts: "paquets",
                map_title: "Densité de Trafic Global", pie_title: "Répartition Protocole", 
                bar_title: "Top 10 Pays (Bloqués vs Autorisés)", chart_blocked: "Bloqué", chart_obs: "Autorisé",
                line_title: "Bande Passante en Temps Réel", line_badge: "Mise à jour 5s", line_desc: "Vitesse du trafic traité par les règles (Kbps)",
                table_title: "Données Détaillées par Pays", th_cc: "Pays", th_b_bytes: "Bloqué (Octets)", th_b_pkts: "Bloqué (Pkt)", th_o_bytes: "Autorisé (Octets)", th_o_pkts: "Autorisé (Pkt)", th_ratio: "Ratio IPv4 / IPv6", th_total: "Total"
            },
            en: {
                subtitle: "MikroTik Global Traffic Analysis (Live 5s)", btn_refresh: "Force Refresh", sync_live: "Live Sync", sync_err: "Sync Error",
                kpi_block_title: "Total Blocked Traffic", kpi_obs_title: "Total Allowed Traffic", kpi_cc_title: "Countries Identified", kpi_ratio_title: "IPv4 / IPv6 Ratio", pkts: "packets",
                map_title: "Global Traffic Density", pie_title: "Protocol Distribution", 
                bar_title: "Top 10 Countries (Blocked vs Allowed)", chart_blocked: "Blocked", chart_obs: "Allowed",
                line_title: "Real-Time Bandwidth", line_badge: "5s Update", line_desc: "Traffic speed processed by rules (Kbps)",
                table_title: "Detailed Data by Country", th_cc: "Country", th_b_bytes: "Blocked (Bytes)", th_b_pkts: "Blocked (Pkt)", th_o_bytes: "Allowed (Bytes)", th_o_pkts: "Allowed (Pkt)", th_ratio: "IPv4 / IPv6 Ratio", th_total: "Total"
            },
            de: {
                subtitle: "MikroTik Globale Verkehrsanalyse (Live 5s)", btn_refresh: "Aktualisieren", sync_live: "Live Sync", sync_err: "Sync Fehler",
                kpi_block_title: "Gesamter blockierter Verkehr", kpi_obs_title: "Gesamter erlaubter Verkehr", kpi_cc_title: "Identifizierte Länder", kpi_ratio_title: "IPv4 / IPv6 Verhältnis", pkts: "Pakete",
                map_title: "Globale Verkehrsdichte", pie_title: "Protokollverteilung", 
                bar_title: "Top 10 Länder (Blockiert vs Erlaubt)", chart_blocked: "Blockiert", chart_obs: "Erlaubt",
                line_title: "Echtzeit-Bandbreite", line_badge: "5s Update", line_desc: "Verarbeitete Verkehrsgeschwindigkeit (Kbps)",
                table_title: "Detaillierte Daten nach Land", th_cc: "Land", th_b_bytes: "Blockiert (Bytes)", th_b_pkts: "Blockiert (Pkt)", th_o_bytes: "Erlaubt (Bytes)", th_o_pkts: "Erlaubt (Pkt)", th_ratio: "IPv4 / IPv6 Verhältnis", th_total: "Gesamt"
            },
            es: {
                subtitle: "Análisis de tráfico global MikroTik (En vivo 5s)", btn_refresh: "Actualizar", sync_live: "Sincronizado", sync_err: "Error Sync",
                kpi_block_title: "Tráfico Total Bloqueado", kpi_obs_title: "Tráfico Total Permitido", kpi_cc_title: "Países Identificados", kpi_ratio_title: "Ratio IPv4 / IPv6", pkts: "paquetes",
                map_title: "Densidad de Tráfico Global", pie_title: "Distribución de Protocolo", 
                bar_title: "Top 10 Países (Bloqueados vs Permitidos)", chart_blocked: "Bloqueado", chart_obs: "Permitido",
                line_title: "Ancho de Banda en Tiempo Real", line_badge: "Actualización 5s", line_desc: "Velocidad de tráfico procesado por reglas (Kbps)",
                table_title: "Datos Detallados por País", th_cc: "País", th_b_bytes: "Bloqueado (Bytes)", th_b_pkts: "Bloqueado (Pkt)", th_o_bytes: "Permitido (Bytes)", th_o_pkts: "Permitido (Pkt)", th_ratio: "Ratio IPv4 / IPv6", th_total: "Total"
            },
            ru: {
                subtitle: "Глобальный анализ трафика MikroTik (Live 5s)", btn_refresh: "Обновить", sync_live: "Синхр.", sync_err: "Ошибка Синхр.",
                kpi_block_title: "Всего заблокировано", kpi_obs_title: "Всего разрешено", kpi_cc_title: "Страны", kpi_ratio_title: "Соотношение IPv4/IPv6", pkts: "пакетов",
                map_title: "Глобальная плотность трафика", pie_title: "Распределение протоколов", 
                bar_title: "Топ 10 стран (Блок. vs Разреш.)", chart_blocked: "Заблокировано", chart_obs: "Разрешено",
                line_title: "Пропускная способность онлайн", line_badge: "Обновление 5с", line_desc: "Скорость обработки трафика правилами (Кбит/с)",
                table_title: "Подробные данные по странам", th_cc: "Страна", th_b_bytes: "Заблок. (Байт)", th_b_pkts: "Заблок. (Пакеты)", th_o_bytes: "Разреш. (Байт)", th_o_pkts: "Разреш. (Пакеты)", th_ratio: "Соотношение IPv4/IPv6", th_total: "Итого"
            },
            zh: {
                subtitle: "MikroTik 全球流量分析 (实时 5 秒)", btn_refresh: "强制刷新", sync_live: "实时同步", sync_err: "同步错误",
                kpi_block_title: "总阻止流量", kpi_obs_title: "总允许流量", kpi_cc_title: "识别国家数", kpi_ratio_title: "IPv4 / IPv6 比例", pkts: "数据包",
                map_title: "全球流量密度", pie_title: "协议分布", 
                bar_title: "前 10 个国家 (阻止 vs 允许)", chart_blocked: "已阻止", chart_obs: "已允许",
                line_title: "实时带宽", line_badge: "5秒更新", line_desc: "规则处理的流量速度 (Kbps)",
                table_title: "按国家分类的详细数据", th_cc: "国家", th_b_bytes: "阻止 (字节)", th_b_pkts: "阻止 (包)", th_o_bytes: "允许 (字节)", th_o_pkts: "允许 (包)", th_ratio: "IPv4 / IPv6 比例", th_total: "总计"
            }
        };

        let currentLang = 'fr';
        function t(key) { return i18n[currentLang][key] || key; }

        function changeLang(lang) {
            currentLang = lang;
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const key = el.getAttribute('data-i18n');
                if (i18n[lang][key]) el.innerHTML = i18n[lang][key];
            });
            // Update Packets label for KPIs without destroying the value
            document.querySelectorAll('#kpi-blocked-pkts span[data-i18n], #kpi-observed-pkts span[data-i18n]').forEach(el => {
                 el.innerText = t('pkts');
            });
            // Force redraw charts to translate labels
            if(topCountriesChart) topCountriesChart.update();
            if(liveBandwidthChart) liveBandwidthChart.update();
        }
        // --- FIN TRADUCTIONS ---

        let protocolChart, topCountriesChart, liveBandwidthChart, worldMap;
        let lastTimestamp = 0; let lastTotalBlockedBytes = 0; let lastTotalObservedBytes = 0;
        let timeLabels = []; let bpsBlockedData = []; let bpsObservedData = [];
        const MAX_HISTORY = 12;

        function formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024; const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function formatNumber(num) { return new Intl.NumberFormat('fr-FR').format(num); }

        async function loadData() {
            try {
                const response = await fetch('?fetch_data=1');
                const data = await response.json();
                renderDashboard(data.countries, data.timestamp);
            } catch (error) {
                console.error("Erreur API :", error);
                document.getElementById('sync-status').innerHTML = `<span class="text-red-400">${t('sync_err')}</span>`;
            }
        }

        function renderDashboard(countriesData, currentTimestamp) {
            document.getElementById('sync-status').innerHTML = `
                <span class="relative flex h-3 w-3 mr-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                </span>
                ${t('sync_live')}
            `;

            let totalBlockedBytes = 0, totalObservedBytes = 0, totalBlockedPkts = 0, totalObservedPkts = 0, totalV4 = 0, totalV6 = 0;
            let mapData = {}; let countryList = [];

            for (const [cc, stats] of Object.entries(countriesData)) {
                let blockedBytes = stats.blocked_v4_b + stats.blocked_v6_b;
                let observedBytes = stats.observed_v4_b + stats.observed_v6_b;
                let blockedPkts = stats.blocked_v4_p + stats.blocked_v6_p;
                let observedPkts = stats.observed_v4_p + stats.observed_v6_p;
                let v4Bytes = stats.blocked_v4_b + stats.observed_v4_b;
                let v6Bytes = stats.blocked_v6_b + stats.observed_v6_b;

                totalBlockedBytes += blockedBytes; totalObservedBytes += observedBytes;
                totalBlockedPkts += blockedPkts; totalObservedPkts += observedPkts;
                totalV4 += v4Bytes; totalV6 += v6Bytes;
                mapData[cc] = stats.total_bytes;

                countryList.push({ cc: cc, name: stats.name, blockedBytes, observedBytes, blockedPkts, observedPkts, v4Bytes, v6Bytes, totalBytes: stats.total_bytes });
            }

            document.getElementById('kpi-blocked').innerText = formatBytes(totalBlockedBytes);
            document.querySelector('#kpi-blocked-pkts .val').innerText = formatNumber(totalBlockedPkts);
            document.getElementById('kpi-observed').innerText = formatBytes(totalObservedBytes);
            document.querySelector('#kpi-observed-pkts .val').innerText = formatNumber(totalObservedPkts);
            document.getElementById('kpi-countries').innerText = Object.keys(countriesData).length;
            
            let sumIp = totalV4 + totalV6;
            document.getElementById('kpi-ratio').innerText = sumIp ? `${Math.round((totalV4/sumIp)*100)}% / ${Math.round((totalV6/sumIp)*100)}%` : '0% / 0%';

            if (lastTimestamp > 0) {
                let timeDiff = currentTimestamp - lastTimestamp;
                if (timeDiff > 0) {
                    let blockedBps = ((totalBlockedBytes - lastTotalBlockedBytes) * 8) / timeDiff;
                    let observedBps = ((totalObservedBytes - lastTotalObservedBytes) * 8) / timeDiff;
                    
                    timeLabels.push(`${new Date().getHours()}:${new Date().getMinutes().toString().padStart(2, '0')}:${new Date().getSeconds().toString().padStart(2, '0')}`);
                    bpsBlockedData.push((blockedBps / 1000).toFixed(2));
                    bpsObservedData.push((observedBps / 1000).toFixed(2));

                    if (timeLabels.length > MAX_HISTORY) { timeLabels.shift(); bpsBlockedData.shift(); bpsObservedData.shift(); }
                    drawLiveBandwidthChart();
                }
            }
            lastTimestamp = currentTimestamp; lastTotalBlockedBytes = totalBlockedBytes; lastTotalObservedBytes = totalObservedBytes;

            drawProtocolChart(totalV4, totalV6); drawTopCountriesChart(countryList); drawMap(mapData); updateTable(countryList);
        }

        function drawProtocolChart(v4, v6) {
            if (!protocolChart) {
                const ctx = document.getElementById('protocolChart').getContext('2d');
                protocolChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: { labels: ['IPv4', 'IPv6'], datasets: [{ data: [v4, v6], backgroundColor: ['#3b82f6', '#8b5cf6'], borderWidth: 0 }] },
                    options: { plugins: { legend: { labels: { color: '#cbd5e1' } } }, cutout: '70%', animation: { duration: 0 } }
                });
            } else {
                protocolChart.data.datasets[0].data = [v4, v6]; protocolChart.update();
            }
        }

        function drawTopCountriesChart(countries) {
            let sorted = [...countries].sort((a, b) => b.totalBytes - a.totalBytes).slice(0, 10);
            if (!topCountriesChart) {
                const ctx = document.getElementById('topCountriesChart').getContext('2d');
                topCountriesChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: sorted.map(c => c.name),
                        datasets: [
                            { label: 'Temp', data: sorted.map(c => c.blockedBytes), backgroundColor: '#f87171' },
                            { label: 'Temp', data: sorted.map(c => c.observedBytes), backgroundColor: '#34d399' }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false, animation: { duration: 0 },
                        scales: {
                            y: { stacked: true, ticks: { color: '#94a3b8', callback: v => formatBytes(v) } },
                            x: { stacked: true, ticks: { color: '#94a3b8' } }
                        },
                        plugins: { legend: { labels: { color: '#cbd5e1' } }, tooltip: { callbacks: { label: (ctx) => formatBytes(ctx.raw) } } }
                    }
                });
            }
            topCountriesChart.data.labels = sorted.map(c => c.name);
            topCountriesChart.data.datasets[0].data = sorted.map(c => c.blockedBytes);
            topCountriesChart.data.datasets[0].label = t('chart_blocked');
            topCountriesChart.data.datasets[1].data = sorted.map(c => c.observedBytes);
            topCountriesChart.data.datasets[1].label = t('chart_obs');
            topCountriesChart.update();
        }

        function drawLiveBandwidthChart() {
            if (!liveBandwidthChart) {
                const ctx = document.getElementById('liveBandwidthChart').getContext('2d');
                liveBandwidthChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: timeLabels,
                        datasets: [
                            { label: 'Temp', data: bpsBlockedData, borderColor: '#f87171', backgroundColor: 'rgba(248, 113, 113, 0.1)', fill: true, tension: 0.4 },
                            { label: 'Temp', data: bpsObservedData, borderColor: '#34d399', backgroundColor: 'rgba(52, 211, 153, 0.1)', fill: true, tension: 0.4 }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false, animation: { duration: 0 },
                        scales: { y: { beginAtZero: true, ticks: { color: '#94a3b8' }, grid: { color: '#334155' } }, x: { ticks: { color: '#94a3b8' }, grid: { display: false } } },
                        plugins: { legend: { labels: { color: '#cbd5e1' } } }
                    }
                });
            }
            liveBandwidthChart.data.labels = timeLabels;
            liveBandwidthChart.data.datasets[0].data = bpsBlockedData;
            liveBandwidthChart.data.datasets[0].label = t('chart_blocked') + " (Kbps)";
            liveBandwidthChart.data.datasets[1].data = bpsObservedData;
            liveBandwidthChart.data.datasets[1].label = t('chart_obs') + " (Kbps)";
            liveBandwidthChart.update();
        }

        function drawMap(mapData) {
            if (worldMap) document.getElementById('map').innerHTML = ""; 
            worldMap = new jsVectorMap({
                selector: '#map', map: 'world', backgroundColor: 'transparent',
                regionStyle: { initial: { fill: '#334155', stroke: '#0f172a', strokeWidth: 0.5 }, hover: { fill: '#3b82f6' } },
                visualizeData: { scale: ['#3b82f6', '#ef4444'], values: mapData },
                onRegionTooltipShow(event, tooltip, code) {
                    let bytes = mapData[code] ? mapData[code] : 0;
                    tooltip.text(`${tooltip.text()} - ${formatBytes(bytes)}`);
                }
            });
        }

        function updateTable(countries) {
            const tbody = document.getElementById('table-body');
            tbody.innerHTML = '';
            countries.sort((a, b) => b.totalBytes - a.totalBytes);

            countries.forEach(c => {
                let pctV4 = c.totalBytes ? Math.round((c.v4Bytes / c.totalBytes) * 100) : 0;
                let pctV6 = c.totalBytes ? Math.round((c.v6Bytes / c.totalBytes) * 100) : 0;

                const tr = document.createElement('tr');
                tr.className = 'border-b border-gray-700/50 hover:bg-gray-800/30 transition';
                
                let ratioHtml = `
                    <td class="py-3 px-4 align-middle w-48">
                        <div class="flex items-center justify-between text-xs mb-1 font-medium">
                            <span class="text-blue-400">${pctV4}% v4</span>
                            <span class="text-purple-400">${pctV6}% v6</span>
                        </div>
                        <div class="w-full bg-gray-700 rounded-full h-1.5 flex overflow-hidden">
                            <div class="bg-blue-500 h-1.5" style="width: ${pctV4}%"></div>
                            <div class="bg-purple-500 h-1.5" style="width: ${pctV6}%"></div>
                        </div>
                    </td>
                `;

                tr.innerHTML = `
                    <td class="py-3 px-4 font-medium flex items-center gap-2">
                        <img src="https://flagcdn.com/20x15/${c.cc.toLowerCase()}.png" alt="${c.cc}" class="rounded-sm opacity-80" onerror="this.style.display='none'">
                        ${c.name}
                    </td>
                    <td class="py-3 px-4 text-right text-red-300">${formatBytes(c.blockedBytes)}</td>
                    <td class="py-3 px-4 text-right text-red-300/70">${formatNumber(c.blockedPkts)}</td>
                    <td class="py-3 px-4 text-right text-emerald-300">${formatBytes(c.observedBytes)}</td>
                    <td class="py-3 px-4 text-right text-emerald-300/70">${formatNumber(c.observedPkts)}</td>
                    ${ratioHtml}
                    <td class="py-3 px-4 text-right font-semibold text-gray-200">${formatBytes(c.totalBytes)}</td>
                `;
                tbody.appendChild(tr);
            });
        }

        changeLang('fr'); // Init translation on load
        loadData(); setInterval(loadData, 5000);
    </script>
</body>
</html>