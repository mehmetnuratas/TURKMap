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
    header('Location: http://localhost/gir.php?error=1');
    exit(); // Yönlendirme sonrasında kodun çalışmasını durdur
}

$user = $_SESSION['user'];
$userId = $user['id'];

// Kullanıcı bilgilerini kontrol et
$sql = "SELECT * FROM kullanicilar WHERE id = $userId";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    // Eğer kullanıcı bilgileri veritabanında eşleşmiyorsa
    header('Location: http://localhost/gir.php?error=2');
    exit(); // Yönlendirme sonrasında kodun çalışmasını durdur
}

// Premium kullanıcı kontrolü
if ($user['is_premium'] != 2) {
    die("Bu işlem sadece premium kullanıcılar için geçerlidir.");
}

// Kullanıcı restoranlarını al
$restaurants = [];
$sql = "SELECT id, name, latitude, longitude, city FROM restaurants WHERE kullanicilar_id=$userId";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $restaurants[] = $row;
    }
}
// Restoran ekleme işlemi
if (isset($_POST['add_restaurant'])) {
    $name = $_POST['name'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $city = $_POST['city'];

    // SQL sorgusu ile veriyi ekleme
    $sql = "INSERT INTO restaurants (name, latitude, longitude, city, kullanicilar_id) 
            VALUES ('$name', '$latitude', '$longitude', '$city', '$userId')";
    
    if ($conn->query($sql) === TRUE) {
        echo "Restoran başarıyla eklendi.";
    } else {
        echo "Hata: " . $sql . "<br>" . $conn->error;
    }
}

// İndirim ekleme işlemi
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_discount'])) {
    $restoran_id = $_POST['restoran_id'];
    $aciklama = $_POST['aciklama'];
    $baslangic_tarihi = $_POST['baslangic_tarihi'];
    $bitis_tarihi = $_POST['bitis_tarihi'];
    $yuzde = $_POST['yuzde'];

    // Restoranın kullanıcıya ait olup olmadığını kontrol et
    $checkSql = "SELECT name FROM restaurants WHERE id = $restoran_id AND kullanicilar_id = $userId";
    $checkResult = $conn->query($checkSql);

    if ($checkResult->num_rows > 0) {
        $restoran = $checkResult->fetch_assoc();
        $restoran_adi = $restoran['name'];

        // İndirim ekle
        $insertSql = "INSERT INTO indirimler (restoran_id, restoran_adi, aciklama, baslangic_tarihi, bitis_tarihi, yuzde)
                      VALUES ('$restoran_id', '$restoran_adi', '$aciklama', '$baslangic_tarihi', '$bitis_tarihi', '$yuzde')";
        if ($conn->query($insertSql) === TRUE) {
            echo "İndirim başarıyla eklendi.";
        } else {
            echo "İndirim eklenirken hata oluştu: " . $conn->error;
        }
    } else {
        echo "Bu restorana indirim ekleme yetkiniz yok.";
    }
}

// Restoran silme işlemi
if (isset($_POST['delete_restaurant'])) {
    $restaurant_id = $_POST['restaurant_id'];
    $sql = "DELETE FROM restaurants WHERE id=$restaurant_id AND kullanicilar_id=$userId";
    
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Restoran başarıyla silindi.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Hata: ' . $conn->error]);
    }
    exit; // Ajax isteği olduğu için burada işlemi sonlandırıyoruz
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
        /* Genel Stil Ayarları */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        header {
            background-color: #007bff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }
        /* Profil Kartı Tasarımı */
        .profile-card {
            display: flex;
            align-items: center;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 20px auto;
            display: none; /* Profil kartı başlangıçta gizli */
        }
        .profile-image {
            margin-right: 20px;
        }
        .profile-image img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid #007bff;
        }
        .profile-info h2 {
            margin: 0;
            font-size: 24px;
            color: #007bff;
        }
        .profile-info p {
            font-size: 16px;
            margin: 5px 0;
            color: #333;
        }
        .profile-info strong {
            color: #007bff;
        }
        header .profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        header .profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid white;
        }
        header .profile .info {
            display: flex;
            flex-direction: column;
        }
        header .profile .info span {
            font-size: 14px;
            line-height: 1.2;
        }
        header button {
            background-color: white;
            color: #007bff;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        .container {
            padding: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .form-section, .map-section {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .form-section {
            flex: 1;
            min-width: 300px;
        }
        .map-section {
            flex: 2;
            min-width: 500px;
        }
        .table-section {
            width: 100%;
            margin-top: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        h2 {
            margin: 0 0 15px 0;
            color: #333;
        }
        input, button {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background-color: #007bff;
            color: white;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background-color: #0056b3;
        }
        #map {
            height: 400px;
            border-radius: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        table th {
            background-color: #f4f4f4;
        }
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
        }
		 body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }

        /* Modal pencere tasarımı */
        .modal {
            display: none; /* Başlangıçta gizli */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            width: 500px;
            height: 800px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            padding: 20px;
            text-align: center;
            position: relative;
        }

        .modal-content h2 {
            margin: 0;
            color: #007bff;
        }

        .modal-content p {
            margin: 15px 0;
            font-size: 16px;
            color: #555;
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #ff5e57;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 50%;
            cursor: pointer;
        }

        .close-btn:hover {
            background-color: #ff3b30;
        }

        button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
		

        button:hover {
            background-color: #0056b3;
        }
		/* Modal başlık rengi */
.modal-content h2 {
    color: #ff5733; /* Başlık için yeni renk */
}

/* Modal içerik yazıları rengi */
.modal-content p {
    color: #333; /* İçerik yazıları için yeni renk */
}

/* İndirim ekleme formundaki yazıların rengini değiştirme */
.discount-form label {
    color: #007bff; /* Etiketler için yeni renk */
}

.discount-form input, .discount-form select {
    border-color: #007bff; /* Input ve select elemanları için yeni kenarlık rengi */
}

.discount-form button {
    background-color: #28a745; /* Buton arka plan rengi */
    color: white; /* Buton yazı rengi */
}

.discount-form button:hover {
    background-color: #218838; /* Hover durumu için buton arka plan rengi */
}

		
    </style>
</head>
<body>
<header>
    <div class="profile">
        <img src="path_to_user_image.jpg" alt="Profil Resmi">
        <div class="info">
            <span>Hoşgeldiniz, <?php echo htmlspecialchars($user['email']); ?></span>
            <span><button onclick="toggleProfile()">Profilim</button></span>
			<button onclick="window.location.href='anasayfa.php';">Çıkış Yap </button>
        </div>
    </div>
	 <h1>İndirimler Sayfası</h1>
    <p>Aşağıdaki butona tıklayarak İndirimler penceresini açabilirsiniz:</p>
    <button id="openModalBtn">İndirimler Penceresini Aç</button>
	

    <!-- Modal pencere -->
   <div id="indirimlerModal" class="modal">
    <div class="modal-content">
        <button class="close-btn" id="closeModalBtn">&times;</button>
        <h2>İndirimler</h2>
        <p>İndirim Ekle</p>

        <h1>İndirim Ekle</h1>
        <form method="POST" class="discount-form">
            <label for="restoran_id">Restoran:</label>
            <select name="restoran_id" id="restoran_id" required>
                <option value="">Restoran Seçin</option>
                <?php foreach ($restaurants as $restaurant): ?>
                    <option value="<?= $restaurant['id'] ?>"><?= $restaurant['name'] ?></option>
                <?php endforeach; ?>
            </select>

            <label for="aciklama">Açıklama:</label>
            <input type="text" name="aciklama" id="aciklama" required>

            <label for="baslangic_tarihi">Başlangıç Tarihi:</label>
            <input type="date" name="baslangic_tarihi" id="baslangic_tarihi" required>

            <label for="bitis_tarihi">Bitiş Tarihi:</label>
            <input type="date" name="bitis_tarihi" id="bitis_tarihi" required>

            <label for="yuzde">İndirim Yüzdesi (%):</label>
            <input type="number" name="yuzde" id="yuzde" min="1" max="100" required>

            <button type="submit" name="add_discount" class="submit-btn">İndirim Ekle</button>
			
			
        </form>
    </div>
</div>

	 <script>
        // Modal ve butonlar
        const modal = document.getElementById('indirimlerModal');
        const openModalBtn = document.getElementById('openModalBtn');
        const closeModalBtn = document.getElementById('closeModalBtn');

        // Modal açma işlemi
        openModalBtn.addEventListener('click', () => {
            modal.style.display = 'flex';
        });

        // Modal kapatma işlemi
        closeModalBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        // Pencere dışında bir yere tıklayınca modalı kapatma
        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    </script>
</header>

<!-- Profil Kartı -->
<div class="profile-card" id="profileCard">
    <div class="profile-image">
        <img src="path_to_user_image.jpg" alt="Profil Resmi">
    </div>
    <div class="profile-info">
        <h2>Profil Bilgileri</h2>
        <p><strong>Adı:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
        <p><strong>Üyelik Türü:</strong> <?php echo $user['is_premium'] ? "Premium" : "Normal"; ?></p>
        <p><strong>Hesap Oluşturma Tarihi:</strong> <?php echo $user['created_at']; ?></p>
		<button onclick="window.location.href='http://localhost/onay.php';">Restoran Ekstra Özellik</button>

    </div>
</div>

<div class="container">
    <!-- Form Section -->
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

    <!-- Map Section -->
    <div class="map-section">
        <div id="map"></div>
    </div>
	
</div>

<div class="table-section">
    <h2>Restoranlar</h2>
    <table>
        <thead>
            <tr>
                <th>Adı</th>
                <th>Şehir</th>
                <th>Enlem</th>
                <th>Boylam</th>
                <th>Sil</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($restaurants as $restaurant): ?>
            <tr>
                <td><?php echo htmlspecialchars($restaurant['name']); ?></td>
                <td><?php echo htmlspecialchars($restaurant['city']); ?></td>
                <td><?php echo htmlspecialchars($restaurant['latitude']); ?></td>
                <td><?php echo htmlspecialchars($restaurant['longitude']); ?></td>
                <td><button onclick="deleteRestaurant(<?php echo $restaurant['id']; ?>)">Sil</button></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
    // Leaflet Harita Başlatma
    var map = L.map('map').setView([41.0082, 28.9784], 13); // Başlangıç konumu (İstanbul)

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // Harita tıklama fonksiyonu
    var marker;
    map.on('click', function(e) {
        if (marker) {
            map.removeLayer(marker); // Önceki marker'ı sil
        }
        marker = L.marker(e.latlng).addTo(map);
        document.getElementById('latitude').value = e.latlng.lat;
        document.getElementById('longitude').value = e.latlng.lng;
    });

    // Profil Kartı Gösterme/Fonksiyonu
    function toggleProfile() {
        var profileCard = document.getElementById('profileCard');
        profileCard.style.display = (profileCard.style.display === 'block') ? 'none' : 'block';
    }

    // Restoran Silme Fonksiyonu
    function deleteRestaurant(restaurantId) {
        if (confirm('Bu restoranı silmek istediğinizden emin misiniz?')) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                var response = JSON.parse(xhr.responseText);
                alert(response.message);
                if (response.status === 'success') {
                    location.reload(); // Sayfayı yeniden yükle
                }
            };
            xhr.send('delete_restaurant=true&restaurant_id=' + restaurantId);
        }
    }
</script>
</body>
</html>
