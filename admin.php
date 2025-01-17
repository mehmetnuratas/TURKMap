<?php
// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "php_veri_tabanım";

$conn = new mysqli($servername, $username, $password, $dbname);

// Bağlantıyı kontrol et
if ($conn->connect_error) {
    die("Bağlantı hatası: " . $conn->connect_error);
}

// Kullanıcıyı doğrula
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: http://localhost/gir.php");
    exit();
}

$user = $_SESSION['user'];
$userId = $user['id'];

// Kullanıcı verilerini kontrol et
$sql_user_check = "SELECT id, email, password, created_at, is_premium FROM kullanicilar WHERE id = $userId";

try {
    $result = $conn->query($sql_user_check);

    if ($result && $result->num_rows === 1) {
        $user_data = $result->fetch_assoc();
        if ($user_data['is_premium'] != 2) {
            header("Location: http://localhost/gir.php");
            exit();
        }
    } else {
        header("Location: http://localhost/gir.php");
        exit();
    }
} catch (mysqli_sql_exception $e) {
    die("Sorgu hatası: " . $e->getMessage());
}

// Restoran CRUD işlemleri
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_restaurant'])) {
        // Restoran ekleme işlemi
        $name = htmlspecialchars($conn->real_escape_string($_POST['name']));
        $latitude = htmlspecialchars($conn->real_escape_string($_POST['latitude']));
        $longitude = htmlspecialchars($conn->real_escape_string($_POST['longitude']));
        $city = htmlspecialchars($conn->real_escape_string($_POST['city']));
        
        $sql = "INSERT INTO restaurants (name, latitude, longitude, city) VALUES ('$name', '$latitude', '$longitude', '$city')";
        if ($conn->query($sql) === TRUE) {
            $successMessage = "Restoran başarıyla eklendi!";
        } else {
            $errorMessage = "Hata: " . $conn->error;
        }
    } elseif (isset($_POST['update_restaurant'])) {
        // ID kontrolü ekleyelim
        if (isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $name = htmlspecialchars($conn->real_escape_string($_POST['name']));
            $latitude = htmlspecialchars($conn->real_escape_string($_POST['latitude']));
            $longitude = htmlspecialchars($conn->real_escape_string($_POST['longitude']));
            $city = htmlspecialchars($conn->real_escape_string($_POST['city']));
            
            $sql = "UPDATE restaurants SET name='$name', latitude='$latitude', longitude='$longitude', city='$city' WHERE id=$id";
            if ($conn->query($sql) === TRUE) {
                $successMessage = "Restoran başarıyla güncellendi!";
            } else {
                $errorMessage = "Hata: " . $conn->error;
            }
        } else {
            $errorMessage = "ID değeri eksik.";
        }
    } elseif (isset($_POST['delete_restaurant'])) {
        // ID kontrolü ekleyelim
        if (isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $sql = "DELETE FROM restaurants WHERE id=$id";
            if ($conn->query($sql) === TRUE) {
                $successMessage = "Restoran başarıyla silindi!";
            } else {
                $errorMessage = "Hata: " . $conn->error;
            }
        } else {
            $errorMessage = "ID değeri eksik.";
        }
    }
}

// Restoran arama işlemi
$search_query = '';
if (isset($_GET['search'])) {
    $search_query = htmlspecialchars($conn->real_escape_string($_GET['search']));
}

// Restoranları al
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

$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restoran Yönetimi</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
    <style>
     /* Ana kaydırma çubuğunu kaldır */
html, body {
    margin: 0;
    padding: 0;
    height: 100%;
    overflow: hidden;
    background-color: #212529;
    font-family: 'Inter', sans-serif;
    color: #e1e4e8;
}

/* Header stili */
.header {
    background: linear-gradient(145deg, #007bff, #004085);
    color: white;
    padding: 20px 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    border-bottom: 2px solid transparent;
    border-image: linear-gradient(to right, #00d2ff, #004085);
    border-image-slice: 1;
    border-radius: 8px 8px 0 0;
    transition: all 0.3s ease-in-out;
    position: relative;
    overflow: hidden;
}

/* Profil bölmesi */
.header .profile-section {
    display: flex;
    align-items: center;
    gap: 25px;
}

.header .profile-section img {
    width: 55px;
    height: 55px;
    border-radius: 50%;
    border: 3px solid white;
}

.header .profile-section button {
    background-color: #00d2ff;
    color: #212529;
    padding: 12px 18px;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 600;
    transition: background-color 0.3s, transform 0.3s, box-shadow 0.3s;
}

.header .profile-section button:hover {
    background-color: #00b2cc;
    transform: scale(1.05);
    box-shadow: 0 0 15px rgba(0, 210, 255, 0.3);
}

/* Genel container düzeni */
.container {
    display: flex;
    flex-wrap: wrap;
    padding: 30px;
    gap: 30px;
    height: calc(100% - 120px);
    background-color: #2c2f33;
}

/* Section stili */
section {
    background-color: #343a40;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.3s ease, transform 0.3s;
}

section:hover {
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
    transform: translateY(-8px);
}

/* Başlıklar */
h1, h2 {
    color: #ffffff;
    font-size: 28px;
    font-weight: 700;
    letter-spacing: 1px;
    margin-bottom: 20px;
}

/* Button stili */
button {
    padding: 12px 18px;
    background-color: #28a745;
    color: white;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.3s ease-in-out;
    margin-top: 15px;
    font-weight: 600;
}

button:hover {
    background-color: #218838;
    transform: scale(1.05);
}

button[name="delete_restaurant"] {
    background-color: #dc3545;
}

button[name="delete_restaurant"]:hover {
    background-color: #a71d2a;
}

/* Input ve Form stil */
input {
    padding: 12px;
    margin: 8px 0;
    width: 100%;
    border: 1px solid #495057;
    border-radius: 12px;
    font-size: 16px;
    transition: all 0.3s ease;
}

input:focus {
    border-color: #28a745;
    outline: none;
    box-shadow: 0 0 10px rgba(40, 167, 69, 0.3);
}

/* Map */
#map {
    height: 400px;
    width: 100%;
    border-radius: 12px;
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
}

/* Form arama stil */
.search-form {
    margin-bottom: 25px;
    display: flex;
    gap: 20px;
    align-items: center;
}

.search-form input {
    flex: 1;
    padding: 12px;
    border-radius: 12px;
    font-size: 16px;
    background-color: #495057;
    color: #e1e4e8;
}

.search-form button {
    padding: 12px 18px;
    background-color: #17a2b8;
    color: white;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.search-form button:hover {
    background-color: #138496;
}

/* Tablo stili */
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
    margin-top: 20px;
}

table, th, td {
    border: 1px solid #444;
}

th, td {
    padding: 12px;
    text-align: left;
}

th {
    background-color: #495057;
    color: #e1e4e8;
    font-weight: 600;
}

/* Layout düzenlemeleri */
.map-section {
    flex: 2;
    min-width: 55%;
}

.form-section {
    flex: 1;
    min-width: 30%;
}

.list-section {
    flex: 1;
    min-width: 30%;
    max-height: 500px;
    overflow-y: auto;
    padding-right: 15px;
}

/* Responsive tasarım */
@media (max-width: 1024px) {
    .container {
        flex-direction: column;
        padding: 20px;
    }

    .map-section {
        order: 1;
    }

    .form-section, .list-section {
        order: 2;
    }

    .header {
        flex-direction: column;
        align-items: flex-start;
    }
}

/* Dalgalanma ve Dağılma Efekti */
@keyframes rippleEffect {
    0% {
        transform: scale(1);
        box-shadow: 0 0 20px rgba(0, 210, 255, 0.5), 0 0 30px rgba(0, 0, 255, 0.3);
    }
    50% {
        transform: scale(1.2);
        box-shadow: 0 0 35px rgba(0, 210, 255, 0.6), 0 0 50px rgba(0, 0, 255, 0.4);
    }
    100% {
        transform: scale(1);
        box-shadow: 0 0 20px rgba(0, 210, 255, 0.5), 0 0 30px rgba(0, 0, 255, 0.3);
    }
}

/* Header üzerine gelindiğinde dalgalanma ve dağılma efekti */
.header:hover {
    animation: rippleEffect 0.5s ease-in-out forwards;
}


    </style>
</head>
<body>

<!-- Kullanıcı Profili Bölümü -->
<div class="header">
    <div class="profile-section">
        <img src="path_to_user_image.jpg" alt="Profil Resmi">
        <div>
            <span>Hoşgeldiniz, <?= htmlspecialchars($user['email']) ?></span><br>
            <span>Üyelik: <?= $user['is_premium'] ? "Premium" : "Normal" ?></span>
        </div>
        <button onclick="window.location.href='grafikler.php';">GRAFİKLER</button>
    </div>
</div>

<div class="container">
    <!-- Restoran Ekleme Bölümü -->
    <div class="form-section">
        <section>
            <h2>Yeni Restoran Ekle</h2>
            <form method="POST" action="">
                <input type="text" name="name" placeholder="Restoran Adı" required>
                <input type="text" name="latitude" id="latitude" placeholder="Enlem" readonly required>
                <input type="text" name="longitude" id="longitude" placeholder="Boylam" readonly required>
                <input type="text" name="city" placeholder="Şehir" required>
                <button type="submit" name="add_restaurant">Ekle</button>
            </form>
            <p>Haritaya tıklayarak enlem ve boylam değerlerini seçin.</p>
        </section>
    </div>

    <!-- Harita Bölümü -->
    <div class="map-section">
        <section>
            <h2>Harita</h2>
            <div id="map"></div>

            <!-- Harita görünümü seçimi için bir form ekliyoruz -->
            <form id="map-style-form">
                <label for="map-style">Harita Görünümü:</label>
                <select id="map-style" name="map-style">
                    <option value="streets">Sokak Görünümü</option>
                    <option value="satellite">Uydu Görünümü</option>
                    <option value="light">Aydınlık Modu</option>
                    <option value="dark">Karanlık Modu</option>
                </select>
            </form>
        </section>
    </div>

    <!-- Mevcut Restoranlar Listesi Bölümü -->
    <div class="list-section">
        <section>
            <h2>Mevcut Restoranlar</h2>
            <form method="GET" action="" class="search-form">
                <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>" placeholder="Restoran Ara">
                <button type="submit">Ara</button>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>Adı</th>
                        <th>Şehir</th>
                        <th>Enlem</th>
                        <th>Boylam</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($restaurants as $restaurant): ?>
                        <tr>
                            <td><?= htmlspecialchars($restaurant['name']) ?></td>
                            <td><?= htmlspecialchars($restaurant['city']) ?></td>
                            <td><?= htmlspecialchars($restaurant['latitude']) ?></td>
                            <td><?= htmlspecialchars($restaurant['longitude']) ?></td>
                            <td>
                               <form method="POST" action="" style="display: inline;">
                               <input type="hidden" name="id" value="<?= $restaurant['id'] ?>"> <!-- ID'yi gizli olarak gönderiyoruz -->
                               <button type="submit" name="delete_restaurant">Sil</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>
</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
    const map = L.map('map').setView([39.92077, 32.85411], 6); // Başlangıç olarak Türkiye konumu

    // Harita katmanları
    const streetsLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
    });
    const satelliteLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
    });
    const lightLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
    });
    const darkLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
    });

    // Harita başlangıçta sokak görünümünü gösteriyor
    streetsLayer.addTo(map);

    // Harita görünümü değiştirme işlemi
    document.getElementById('map-style').addEventListener('change', function(event) {
        const selectedStyle = event.target.value;

        // Mevcut katmanı haritadan kaldır
        map.eachLayer(function(layer) {
            map.removeLayer(layer);
        });

        // Yeni seçilen katmanı haritaya ekle
        switch (selectedStyle) {
            case 'streets':
                streetsLayer.addTo(map);
                break;
            case 'satellite':
                satelliteLayer.addTo(map);
                break;
            case 'light':
                lightLayer.addTo(map);
                break;
            case 'dark':
                darkLayer.addTo(map);
                break;
        }

        // Yerel depolama'ya kaydet
        localStorage.setItem('mapStyle', selectedStyle);
    });

    map.on('click', function(e) {
        const { lat, lng } = e.latlng;
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;
    });

    // Yerel depolamadan harita görünümünü al ve uygula
    window.onload = function() {
        const savedStyle = localStorage.getItem('mapStyle') || 'streets';
        document.getElementById('map-style').value = savedStyle;
        const event = new Event('change');
        document.getElementById('map-style').dispatchEvent(event);
    };
</script>
</body>
</html>
