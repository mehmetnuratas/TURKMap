<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rota Hesaplayıcı Uygulaması</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <style>
        /* Genel Stil */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, html {
            height: 100%;
            font-family: Arial, sans-serif;
            overflow: hidden;
        }

        #map {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
        }

        #panel {
            position: absolute;
            top: 15px;
            left: 15px;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.9);
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            width: 300px;
        }

        #panel h4 {
            margin-bottom: 10px;
            font-size: 18px;
            text-align: center;
        }

        #panel input, #panel button, #panel select {
            width: calc(100% - 20px);
            margin: 10px 10px 0;
            padding: 10px;
            font-size: 16px;
            border-radius: 6px;
            border: 1px solid #007bff;
            outline: none;
            transition: background-color 0.3s ease;
        }

        #panel button:hover {
            background-color: #28a745;
            color: white;
        }

        /* Gece Modu Stili */
        .night-mode {
            background-color: #2f2f2f; /* Arka plan rengi koyulaştırıldı */
            color: white; /* Yazılar beyaza çevrildi */
        }

        .night-mode #panel {
            background: rgba(0, 0, 0, 0.7); /* Panel arka planı şeffaf koyu renge dönüştü */
            color: white; /* Panel yazıları beyaz oldu */
        }

        .night-mode #map {
            filter: brightness(0.8); /* Harita parlaklığı azaltıldı */
        }
    </style>
</head>
<body>
    <div id="map"></div>

    <div id="panel">
        <h4>Kontrol Paneli</h4>
        <!-- Kullanıcının mevcut konumunu kullanmasını sağlayan buton -->
        <button onclick="useCurrentLocation()">📍 Mevcut Konumu Kullan</button>
        <!-- Şehir ismini yazmak için kullanılan giriş kutusu -->
        <input type="text" id="city" placeholder="Şehir Adı (örn. İstanbul)" oninput="autocompleteCity()">
        <!-- Kullanıcı ulaşım türünü seçebilir -->
        <select id="transport">
            <option value="walking">🚶‍♂️ Yürüyerek</option>
            <option value="cycling">🚴‍♀️ Bisikletle</option>
            <option value="driving">🚗 Araç ile</option>
            <option value="transit">🚇 Toplu Taşıma</option>
        </select>
        <!-- Ara noktalar için seçenekler -->
        <select id="waypoints">
            <option value="">Ara Nokta Seçin</option>
        </select>
        <!-- Rota oluşturma, temizleme ve gece modu için butonlar -->
        <button onclick="calculateRoute()">🛣️ Rota Oluştur</button>
        <button onclick="clearRoute()">❌ Temizle</button>
        <button onclick="toggleNightMode()">🌙 Gece Modu</button>
		<button onclick="window.location.href='gir.php';">Giriş Yap</button>
    </div>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>
    <script>
        // Harita ve başlangıç görünümü
        const map = L.map('map').setView([38.667237, 27.302679], 13);

        // Harita katmanı ekleniyor
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        let routeControl = null; // Rota kontrol değişkeni
        let userLocation = null; // Kullanıcı konumu

        // Kullanıcının mevcut konumunu kullanmasını sağlayan fonksiyon
        function useCurrentLocation() {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    userLocation = L.latLng(lat, lng);

                    L.marker(userLocation, {icon: createCustomIcon('📍')})
                        .addTo(map)
                        .bindPopup("Mevcut Konumunuz")
                        .openPopup();

                    map.setView(userLocation, 15);
                },
                (error) => {
                    switch (error.code) {
                        case error.PERMISSION_DENIED:
                            alert("Konum izni verilmedi.");
                            break;
                        case error.POSITION_UNAVAILABLE:
                            alert("Konum bilgisi mevcut değil.");
                            break;
                        case error.TIMEOUT:
                            alert("Konum alma süresi doldu.");
                            break;
                        default:
                            alert("Bilinmeyen bir hata oluştu.");
                    }
                }
            );
        }

        // Şehir adını otomatik tamamlama fonksiyonu
        function autocompleteCity() {
            const city = document.getElementById('city').value;
            if (city.length < 3) return;

            const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(city)}&limit=1`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        const cityLatLng = [data[0].lat, data[0].lon];
                        map.setView(cityLatLng, 12);

                        L.marker(cityLatLng, {icon: createCustomIcon('🏙️')})
                            .addTo(map)
                            .bindPopup(`Şehir: ${city}`)
                            .openPopup();
                    } else {
                        alert("Şehir bulunamadı!");
                    }
                })
                .catch(() => alert("Bir hata oluştu, lütfen tekrar deneyin."));
        }

        // Rota oluşturma fonksiyonu
        function calculateRoute() {
            const destination = document.getElementById('city').value;
            const waypoint = document.getElementById('waypoints').value;

            if (!userLocation) {
                alert("Lütfen mevcut konumunuzu seçin.");
                return;
            }

            const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(destination)}&limit=1`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        const destinationLatLng = L.latLng(data[0].lat, data[0].lon);

                        const waypoints = [
                            L.latLng(userLocation.lat, userLocation.lng),
                        ];

                        if (waypoint) {
                            const [wayLat, wayLng] = waypoint.split(',');
                            waypoints.push(L.latLng(wayLat, wayLng));
                        }

                        waypoints.push(destinationLatLng);

                        if (routeControl) {
                            routeControl.setWaypoints(waypoints);
                        } else {
                            routeControl = L.Routing.control({
                                waypoints,
                                routeWhileDragging: true
                            }).addTo(map);
                        }
                    } else {
                        alert("Hedef bulunamadı!");
                    }
                });
        }

        // Rota temizleme fonksiyonu
        function clearRoute() {
            if (routeControl) {
                map.removeControl(routeControl);
                routeControl = null;
            }
        }

        // Gece modu açma/kapatma fonksiyonu
        function toggleNightMode() {
            document.body.classList.toggle('night-mode');
        }

        // Özel ikon oluşturma fonksiyonu
        function createCustomIcon(icon) {
            return L.divIcon({
                html: `<div style="font-size: 20px; text-align: center;">${icon}</div>`,
                iconSize: [30, 30],
                iconAnchor: [15, 30]
            });
        }
    </script>
</body>
</html>
