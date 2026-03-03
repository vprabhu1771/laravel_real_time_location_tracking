<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <!-- ✅ FIXED CSRF -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css"/>

    <title>Location Tracker</title>

    @vite(['resources/js/app.js'])

    <style>
        * { margin:0; padding:0; box-sizing:border-box; }

        html, body { width:100%; height:100%; }

        #map { width:100%; height:100%; }

        header {
            padding:15px;
            background:#f0f0f0;
            font-size:1.2rem;
            position:absolute;
            width:100%;
            z-index:1000;
        }

        .info {
            position:absolute;
            top:70px;
            right:10px;
            background:#fff;
            padding:10px;
            border-radius:6px;
            z-index:1000;
            font-size:14px;
        }

        .loading {
            position:absolute;
            top:50%;
            left:50%;
            transform:translate(-50%,-50%);
            background:#fff;
            padding:15px;
            border-radius:6px;
            z-index:2000;
        }

        #error {
            position:absolute;
            top:60px;
            left:10px;
            background:#ffdddd;
            padding:10px;
            border-radius:6px;
            display:none;
            z-index:2000;
        }

        #error.show { display:block; }
    </style>
</head>
<body>

<header>Real Time Location Tracker</header>

<div id="error"></div>
<div id="map"></div>

<div class="info">
    <strong>Active:</strong> <span id="active-users">0</span><br>
    <strong>You:</strong> <span id="your-location">0</span><br>
    <strong>Status:</strong> <span id="status">0</span>
</div>

<div class="loading">
    Initializing Map...
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {

    const map = L.map('map').setView([0, 0], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19
    }).addTo(map);

    const markers = {};

    const updateMarker = (id, lat, lng) => {

        const isMe = id === window.sessionId;

        if (!markers[id]) {

            const icon = L.divIcon({
                html: `
                    <div style="
                        background:${isMe ? '#667eea' : '#10b981'};
                        width:18px;
                        height:18px;
                        border-radius:50%;
                        border:2px solid #fff;
                        box-shadow:0 1px 4px #000;
                    "></div>
                `,
                iconSize: [18,18],
                iconAnchor: [9,9]
            });

            markers[id] = L.marker([lat, lng], {icon})
                .addTo(map)
                .bindPopup(`${isMe ? 'You' : 'User'}<br>${lat.toFixed(4)}, ${lng.toFixed(4)}`);

        } else {
            markers[id].setLatLng([lat,lng]);
        }

        document.getElementById('active-users').textContent =
            Object.keys(markers).length;

        if (isMe) {
            map.setView([lat,lng], 16);
            document.getElementById('your-location').textContent =
                `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
        }
    };

    const showError = (msg) => {
        const e = document.getElementById('error');
        e.textContent = msg;
        e.classList.add('show');
        setTimeout(() => e.classList.remove('show'), 5000);
    };

    const sendLocation = (lat, lng) => {
        fetch('{{ route('location.update') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                latitude: lat,
                longitude: lng
            })
        }).catch(() => showError('Send failed'));
    };

    if (navigator.geolocation) {

        document.getElementById('status').textContent = 'Getting location...';

        navigator.geolocation.watchPosition(
            p => {
                sendLocation(p.coords.latitude, p.coords.longitude);
                document.getElementById('status').textContent = 'Tracking';
            },
            () => {
                showError('Location denied');
                document.getElementById('status').textContent = 'Denied';
            },
            { enableHighAccuracy:false, timeout:20000, maximumAge:10000 }
        );

    } else {
        showError('Geolocation not supported');
    }

    if (window.Echo) {
        window.Echo.channel('location-tracking')
            .listen('.location.updated',
                e => updateMarker(e.userId, e.latitude, e.longitude)
            );
    } else {
        showError('Realtime connection failed');
    }

    // ✅ FIXED LOADING HIDE
    document.querySelector('.loading').style.display = 'none';

});
</script>

</body>
</html>