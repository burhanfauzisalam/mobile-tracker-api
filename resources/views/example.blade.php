@extends('layouts.app')

@section('title', 'Realtime Tracking Dashboard')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        :root {
            --bg-color: #f3f6fb;
            --panel-color: #ffffff;
            --text-color: #1f2933;
            --muted-color: #6b7280;
            --primary-color: #2563eb;
            --accent-color: #0ea5e9;
            --success-color: #16a34a;
            --warning-color: #f97316;
            --danger-color: #dc2626;
            --border-color: #e2e8f0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
        }

        .dashboard {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .top-bar {
            padding: 1.5rem 2rem;
            background: var(--panel-color);
            box-shadow: 0 4px 30px rgba(15, 23, 42, 0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .top-bar h1 {
            margin: 0;
            font-size: 1.5rem;
        }

        .top-bar p {
            margin: 0.25rem 0 0;
            color: var(--muted-color);
        }

        .status-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .badge {
            padding: 0.35rem 0.8rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #fff;
            background: var(--muted-color);
        }

        .badge-online { background: var(--success-color); }
        .badge-offline { background: var(--danger-color); }
        .badge-warning { background: var(--warning-color); }

        .content {
            flex: 1;
            display: flex;
            gap: 1.5rem;
            padding: 1.5rem 2rem 2.5rem;
        }

        .sidebar {
            width: 320px;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .main-panel {
            flex: 1;
            display: flex;
        }

        .card,
        .map-card {
            background: var(--panel-color);
            border-radius: 1rem;
            padding: 1.25rem;
            box-shadow: 0 20px 30px rgba(15, 23, 42, 0.08);
        }

        .map-card {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .map-card h2,
        .card h2 {
            margin-top: 0;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        #map {
            flex: 1;
            border-radius: 0.75rem;
            min-height: 450px;
            border: 1px solid var(--border-color);
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .stat-label {
            margin: 0;
            font-size: 0.85rem;
            color: var(--muted-color);
        }

        .stat-value {
            margin: 0.2rem 0 0;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .stat-value.small {
            font-size: 0.95rem;
            word-break: break-word;
        }

        .user-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            max-height: 200px;
            overflow-y: auto;
        }

        .user-list li {
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 0.8rem;
            background: #f8fafc;
        }

        .user-list .user-name {
            font-weight: 600;
        }

        .user-coords {
            font-size: 0.85rem;
            color: var(--muted-color);
        }

        .user-time {
            font-size: 0.8rem;
            color: var(--muted-color);
            margin-top: 0.25rem;
        }

        .user-battery {
            font-size: 0.85rem;
            color: var(--muted-color);
            margin-top: 0.25rem;
        }

        .user-list .empty {
            text-align: center;
            font-size: 0.9rem;
            color: var(--muted-color);
            background: transparent;
            border-style: dashed;
        }

        .log {
            border: 1px solid var(--border-color);
            border-radius: 0.8rem;
            padding: 0.75rem;
            max-height: 200px;
            overflow-y: auto;
            background: #0f172a;
            color: #e2e8f0;
            font-size: 0.85rem;
        }

        .log-entry {
            margin-bottom: 0.6rem;
        }

        .log-entry:last-child {
            margin-bottom: 0;
        }

        .log-entry span {
            font-size: 0.75rem;
            color: #94a3b8;
            display: block;
        }

        .log-entry p {
            margin: 0.15rem 0 0;
        }

        @media (max-width: 1100px) {
            .content {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                flex-direction: row;
                flex-wrap: wrap;
            }
            .sidebar > .card {
                flex: 1 1 300px;
            }
        }

        @media (max-width: 700px) {
            .top-bar {
                padding: 1rem;
            }
            .content {
                padding: 1rem;
            }
            .sidebar {
                flex-direction: column;
            }
        }
    </style>
@endpush

@section('content')
    <div class="dashboard">
        <header class="top-bar">
            <div>
                <h1>Realtime Tracking Dashboard</h1>
                <p>Monitoring lokasi perangkat Android secara langsung</p>
            </div>
            <div class="status-group">
                <span>Status Broker:</span>
                <span id="connection-status" class="badge badge-warning">Menghubungkan...</span>
            </div>
        </header>

        <div class="content">
            <aside class="sidebar">
                <section class="card">
                    <h2>Ringkasan</h2>
                    <div class="stat-grid">
                        <div>
                            <p class="stat-label">User Aktif</p>
                            <p class="stat-value" id="user-count">0</p>
                        </div>
                        <div>
                            <p class="stat-label">Topic Dipantau</p>
                            <p class="stat-value small">tracking/android/+/location</p>
                        </div>
                    </div>
                </section>

                <section class="card">
                    <h2>Daftar User</h2>
                    <ul id="user-list" class="user-list">
                        <li class="empty">Belum ada data</li>
                    </ul>
                </section>

                <section class="card">
                    <h2>Aktivitas</h2>
                    <div id="activity-log" class="log"></div>
                </section>
            </aside>

            <main class="main-panel">
                <div class="map-card">
                    <h2>Peta Lokasi</h2>
                    <div id="map"></div>
                </div>
            </main>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>
    <script>
$(function () {
    const map = L.map('map').setView([-7, 112], 5);
    const userMarkers = {};
    const userData = {};

    const mqttProtocol = window.location.protocol === 'https:' ? 'wss' : 'wss';
    const mqttPort = mqttProtocol === 'wss' ? 8884 : 8884;
    const mqttUrl = `${mqttProtocol}://portal.ptbmi.com/mqtt:${mqttPort}`;

    const $connectionStatus = $('#connection-status');
    const $userCount = $('#user-count');
    const $userList = $('#user-list');
    const $log = $('#activity-log');

    function setConnectionStatus(text, status = 'warning') {
        $connectionStatus.text(text);
        $connectionStatus.attr('class', `badge badge-${status}`);
    }

    function logActivity(message) {
        const time = new Date().toLocaleTimeString('id-ID', { hour12: false });
        const $entry = $('<div>').addClass('log-entry');
        $entry.append($('<span>').text(time));
        $entry.append($('<p>').text(message));

        $log.prepend($entry);

        const maxEntries = 20;
        while ($log.children().length > maxEntries) {
            $log.children().last().remove();
        }
    }

    function renderUserList() {
        $userList.empty();
        const entries = Object.entries(userData);

        if (!entries.length) {
            $('<li>')
                .addClass('empty')
                .text('Belum ada data')
                .appendTo($userList);
            return;
        }

        entries.sort((a, b) => b[1].updatedAt - a[1].updatedAt);
        entries.forEach(([name, info]) => {
            const $li = $('<li>');
            $('<div>').addClass('user-name').text(name).appendTo($li);
            $('<div>')
                .addClass('user-coords')
                .text(`Lat: ${info.lat.toFixed(5)}, Lng: ${info.lng.toFixed(5)}`)
                .appendTo($li);
            const batteryText = info.battery === null || info.battery === undefined
                ? 'Tidak diketahui'
                : `${info.battery}%`;
            $('<div>')
                .addClass('user-battery')
                .text(`Baterai: ${batteryText}`)
                .appendTo($li);
            $('<div>')
                .addClass('user-time')
                .text(new Date(info.updatedAt).toLocaleString('id-ID'))
                .appendTo($li);
            $userList.append($li);
        });
    }

    function updateUserCount() {
        $userCount.text(Object.keys(userMarkers).length);
    }

    function parseTimestamp(value) {
        if (value === undefined || value === null || value === '') {
            return new Date();
        }
        if (typeof value === 'number') {
            return new Date(value < 1e12 ? value * 1000 : value);
        }
        const numeric = Number(value);
        if (!Number.isNaN(numeric)) {
            return new Date(numeric < 1e12 ? numeric * 1000 : numeric);
        }
        const parsed = new Date(value);
        return Number.isNaN(parsed.getTime()) ? new Date() : parsed;
    }

    function parseBattery(value) {
        if (value === undefined || value === null || value === '') {
            return null;
        }
        const numeric = Number(value);
        if (Number.isNaN(numeric)) {
            return null;
        }
        return Math.min(100, Math.max(0, Math.round(numeric)));
    }

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: 'OpenStreetMap'
    }).addTo(map);

    const client = mqtt.connect(mqttUrl);

    client.on("connect", () => {
        setConnectionStatus("Online", "online");
        logActivity("Terhubung ke broker MQTT.");
        client.subscribe("tracking/android/+/location", { qos: 0 }, err => {
            if (err) {
                logActivity("Gagal subscribe: " + err.message);
                return;
            }
            logActivity("Subscribe ke topic tracking/android/+/location.");
        });
    });

    client.on("reconnect", () => {
        setConnectionStatus("Menyambung ulang...", "warning");
        logActivity("Mencoba menyambung kembali ke broker.");
    });

    client.on("close", () => {
        setConnectionStatus("Offline", "offline");
        logActivity("Koneksi MQTT terputus.");
    });

    client.on("error", err => {
        logActivity("Error MQTT: " + (err.message || err));
    });

    client.on("message", (topic, message) => {
        try {
            const data = JSON.parse(message.toString());
            const lat = Number(data.lat ?? data.latitude);
            const lng = Number(data.lng ?? data.longitude);
            const userFromTopic = topic.split("/")[2] || "unknown";
            const user = data.user ?? data.username ?? userFromTopic;
            const timestamp = parseTimestamp(data.ts ?? data.timestamp);
            const battery = parseBattery(data.battery ?? data.batt ?? data.battery_level);

            if (Number.isFinite(lat) && Number.isFinite(lng)) {
                const marker = userMarkers[user] ?? L.marker([lat, lng], { title: user }).addTo(map);
                userMarkers[user] = marker;
                userData[user] = { lat, lng, battery, updatedAt: timestamp.getTime() };

                const batteryText = battery === null ? 'Tidak diketahui' : `${battery}%`;

                marker.setLatLng([lat, lng]);
                marker.bindPopup(`
                    <b>User:</b> ${user}<br>
                    <b>Lat:</b> ${lat}<br>
                    <b>Lng:</b> ${lng}<br>
                    <b>Baterai:</b> ${batteryText}<br>
                    <b>Waktu:</b> ${timestamp.toLocaleString('id-ID')}
                `);

                const markers = Object.values(userMarkers);
                if (markers.length === 1) {
                    map.setView([lat, lng], 16);
                } else {
                    const bounds = L.latLngBounds(markers.map(m => m.getLatLng()));
                    map.fitBounds(bounds.pad(0.2));
                }

                updateUserCount();
                renderUserList();
                logActivity(`Posisi ${user} diperbarui (baterai: ${batteryText}).`);
            } else {
                logActivity("Data lokasi tidak valid: " + JSON.stringify(data));
            }
        } catch (err) {
            logActivity("JSON parse error: " + err.message);
        }
    });
});
    </script>
@endpush
