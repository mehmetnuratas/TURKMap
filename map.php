<?php
// Oturum başlat
session_start();

// Veritabanı bağlantısı
$servername = "localhost";
$username = "root"; // Kullanıcı adınızı girin
$password = ""; // Şifrenizi girin
$dbname = "php_veri_tabanım"; // Veritabanı adınızı girin

$conn = new mysqli($servername, $username, $password, $dbname);

// Bağlantıyı kontrol et
if ($conn->connect_error) {
    die("Bağlantı hatası: " . $conn->connect_error);
}

// Kullanıcı giriş yapmış mı kontrol et
if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
    // Eğer kullanıcı giriş yapmamışsa, 'gir.php' sayfasına yönlendir
    header('Location: http://localhost/gir.php');
    exit(); // Yönlendirme sonrasında kodun çalışmasını durdur
}

$user = $_SESSION['user'];
$userId = $user['id'];

// Restoranları al
$search_query = isset($_GET['search_query']) ? $_GET['search_query'] : ''; // Arama sorgusu
$sql = "SELECT id, name, latitude, longitude, city FROM restaurants WHERE name LIKE '%$search_query%'";
$result = $conn->query($sql);
$restaurants = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $restaurants[] = $row;
    }
} else {
    $errorMessage = "Hiç restoran bulunamadı.";
}

// Kullanıcının yıldız ve yorum gönderme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restaurant_id'], $_POST['rating'], $_POST['comment'])) {
    $restaurant_id = isset($_POST['restaurant_id']) ? (int)$_POST['restaurant_id'] : 0;
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

    // Boş veri kontrolü
    if ($restaurant_id <= 0 || $rating <= 0 || empty($comment)) {
        echo "<script>alert('Lütfen geçerli bir restoran, yıldız sayısı ve yorum girin.');</script>";
    } else {
        // SQL Injection koruması için parametreli sorgu kullanımı
        $comment = $conn->real_escape_string($comment);
        $date = date('Y-m-d H:i:s');

        $insert_sql = "INSERT INTO yildizlar (kullanici_id, restoran_id, yildiz_sayisi, yorum, tarih) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("iiiss", $userId, $restaurant_id, $rating, $comment, $date);

        if ($stmt->execute()) {
            echo "<script>alert('Yorumunuz başarıyla kaydedildi!');</script>";
            // Başarı durumunda yönlendirme veya başka bir işlem
            // header('Location: yorumlar.php'); // Yorumlar sayfasına yönlendirebilirsiniz
        } else {
            echo "<script>alert('Hata: " . $conn->error . "');</script>";
        }

        $stmt->close();
    }
}

$conn->close();
?>




$conn->close();
?><!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rota Hesaplayıcı Uygulaması</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
    <style>
       /* Türkçe dil desteği */
L.Routing.language = 'tr'; // Türkçe dil desteği ekler

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body, html {
    height: 100%;
    font-family: 'Poppins', Arial, sans-serif;
    background-color: #f4f4f9;
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
    background: rgba(255, 255, 255, 0.95);
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    width: 400px;
    font-size: 16px;
    display: none; /* Başlangıçta paneli gizli tut */
    animation: fadeIn 0.3s ease-in-out;
}

#panel select, #panel input, #panel button, #panel textarea {
    width: calc(100% - 20px);
    margin: 10px 10px 0;
    padding: 12px;
    font-size: 16px;
    border-radius: 8px;
    border: 1px solid #007bff;
    transition: border-color 0.3s, box-shadow 0.3s;
}

#panel select:focus, #panel input:focus, #panel button:focus, #panel textarea:focus {
    border-color: #00d2ff;
    box-shadow: 0 0 10px rgba(0, 210, 255, 0.5);
}

#panel button {
    background-color: #007bff;
    color: white;
    cursor: pointer;
    transition: background-color 0.3s, transform 0.2s;
}

#panel button:hover {
    background-color: #0056b3;
    transform: scale(1.05);
}

#weather-container, #discounts-container {
    margin-top: 20px;
    text-align: center;
    padding: 20px;
    background-color: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    width: 90%;
    max-width: 600px;
    font-size: 14px;
    line-height: 1.6;
}

#discounts-list {
    max-height: 300px;
    overflow-y: auto;
    padding: 0;
    list-style-type: none;
}

#discounts-list li {
    padding: 12px;
    border-bottom: 1px solid #ddd;
    background-color: #f1f1f1;
    margin-bottom: 5px;
    border-radius: 8px;
    transition: background-color 0.3s;
}

#discounts-list li:hover {
    background-color: #e0e0e0;
    cursor: pointer;
}

/* Kontrol Paneli açma butonu */
#togglePanelBtn {
    position: absolute;
    top: 15px;
    right: 15px;
    background: linear-gradient(45deg, #007bff, #00d2ff);
    color: white;
    padding: 15px 25px;
    border-radius: 50px;
    cursor: pointer;
    z-index: 1000;
    font-size: 16px;
    font-weight: bold;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    transition: background 0.3s, transform 0.2s ease-in-out, box-shadow 0.3s;
}

#togglePanelBtn:hover {
    background: linear-gradient(45deg, #0056b3, #00b2cc);
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
}

#togglePanelBtn:focus {
    outline: none;
    box-shadow: 0 0 15px rgba(0, 210, 255, 0.5);
}

/* Header ve Profil Bölümü */
.header {
    background-color: #ffffff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    text-align: center;
    animation: fadeIn 0.5s ease-in-out;
}

.profile-section {
    display: flex;
    align-items: center;
    gap: 20px;
}

.profile-section img {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: 4px solid #00d2ff;
    object-fit: cover;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

.profile-section .user-info {
    text-align: left;
}

.profile-section .user-info span {
    display: block;
    font-size: 16px;
    color: #333;
}

.profile-section .user-info span:first-child {
    font-weight: bold;
    font-size: 18px;
    color: #007bff;
}

.profile-section button {
    background: linear-gradient(45deg, #00d2ff, #007bff);
    color: white;
    padding: 12px 18px;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.3s, transform 0.3s, box-shadow 0.3s;
}

.profile-section button:hover {
    background: linear-gradient(45deg, #00b2cc, #0056b3);
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(0, 210, 255, 0.3);
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
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
    
           
		<button onclick="window.location.href='http://localhost/onay.php';">Restoran Ekstra Özellik</button>

    <!-- Kontrol Paneli açma butonu -->
    <button id="togglePanelBtn">Kontrol Panelini Aç</button>

    <div id="panel">
	
          <div class="header">
        <div class="profile-section">
            <img src="path_to_user_image.jpg" alt="Profil Resmi">
            <div class="user-info">
                <span>Hoşgeldiniz, <?= htmlspecialchars($user['email']) ?></span>
                <span>Üyelik: <?= $user['is_premium'] ? "Premium" : "Normal" ?></span>
            </div>
            <button onclick="window.location.href='http://localhost/anasayfa.php';">Çıkış Yap</button>
        </div>
    </div>

        <select id="restaurantSelect">
            <option value="">Restoran Seç</option>
            <?php foreach ($restaurants as $restaurant): ?>
                <option value="<?= $restaurant['id'] ?>" data-lat="<?= $restaurant['latitude'] ?>" data-lng="<?= $restaurant['longitude'] ?>">
                    <?= $restaurant['name'] ?> (<?= $restaurant['city'] ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <input type="text" id="destination" placeholder="Varış Noktası (Şehir Adı)">
        <button onclick="calculateRoute()">🛣️ Rota Oluştur</button>
        <button onclick="clearRoute()">❌ Temizle</button>
        <button onclick="toggleNightMode()">🌙 Gece Modu</button>
		<form method="POST">
             <form method="POST" action="yorum_ekle.php">
    <h4>Yıldızlama ve Yorumlama</h4>
    <select name="restaurant_id">
        <option value="">Restoran Seç</option>
        <?php foreach ($restaurants as $restaurant): ?>
            <option value="<?= $restaurant['id'] ?>">
                <?= $restaurant['name'] ?> (<?= $restaurant['city'] ?>)
            </option>
        <?php endforeach; ?>
    </select>
    <select name="rating" required>
        <option value="">Yıldız Sayısı</option>
        <option value="1">1</option>
        <option value="2">2</option>
        <option value="3">3</option>
        <option value="4">4</option>
        <option value="5">5</option>
    </select>
    <textarea name="comment" placeholder="Yorumunuz" required></textarea>
    <button type="submit">Yorum Ekle</button>
</form>

<script>
// JavaScript ile giriş kontrolü yapılabilir
document.querySelector('form').addEventListener('submit', function(e) {
    // Formu göndermeden önce giriş yapılmış mı kontrol et
    fetch('giris_kontrol.php') // Giriş kontrolü için PHP dosyasına istek gönder
        .then(response => response.json())
        .then(data => {
            if (data.status === 'error') {
                e.preventDefault(); // Formu gönderme
                alert(data.message); // Hata mesajını göster
            }
        });
});
</script>

        
       


    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>
    <script>
        const map = L.map('map').setView([38.667237, 27.302679], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        let routeControl = null;
        let userLocation = null;

        // Mevcut Konumu Al
        navigator.geolocation.getCurrentPosition(
            (position) => {
                userLocation = L.latLng(position.coords.latitude, position.coords.longitude);
                L.marker(userLocation).addTo(map).bindPopup("Mevcut Konumunuz").openPopup();
                map.setView(userLocation, 15);
            },
            () => alert("Konum alınamadı. Lütfen konum izni verdiğinizden emin olun.")
        );

        // Rota Hesaplama
        function calculateRoute() {
            const selectedRestaurant = document.getElementById('restaurantSelect');
            const destination = document.getElementById('destination').value;

            if (!userLocation) {
                alert("Mevcut konum alınamadı.");
                return;
            }

            if (!selectedRestaurant.value || !destination) {
                alert("Lütfen restoran ve varış noktasını seçin.");
                return;
            }

            const lat = selectedRestaurant.options[selectedRestaurant.selectedIndex].getAttribute('data-lat');
            const lng = selectedRestaurant.options[selectedRestaurant.selectedIndex].getAttribute('data-lng');
            const restaurantLatLng = L.latLng(lat, lng);

            const destinationUrl = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(destination)}&limit=1`;

            fetch(destinationUrl)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        const destinationLatLng = L.latLng(data[0].lat, data[0].lon);
                        const waypoints = [userLocation, restaurantLatLng, destinationLatLng];

                        if (routeControl) {
                            routeControl.setWaypoints(waypoints);
                        } else {
                            routeControl = L.Routing.control({
                                waypoints,
                                routeWhileDragging: true
                            }).addTo(map);
                        }
                        map.fitBounds(L.latLngBounds(waypoints));
                    } else {
                        alert("Varış noktası bulunamadı!");
                    }
                })
                .catch(() => alert("Hata oluştu, lütfen tekrar deneyin."));
        }

        // Rotayı Temizle
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

        
        // Kontrol panelini açıp kapatmak için fonksiyon
        document.getElementById('togglePanelBtn').addEventListener('click', function () {
            const panel = document.getElementById('panel');
            if (panel.style.display === 'none') {
                panel.style.display = 'block';
                this.textContent = 'Kontrol Panelini Kapat'; // Buton metnini değiştir
            } else {
                panel.style.display = 'none';
                this.textContent = 'Kontrol Panelini Aç'; // Buton metnini geri değiştir
            }
        });
		
    </script>
</body>
</html>

