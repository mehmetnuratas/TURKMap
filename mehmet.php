<?php
// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "php_veri_tabanım";

$conn = new mysqli($servername, $username, $password, $dbname);

// Bağlantıyı kontrol et
if ($conn->connect_error) {
    die("Bağlantı başarısız: " . $conn->connect_error);
}

// OpenWeatherMap API ayarları
$api_key = '1da854551e00aba995e658ff7d98ee45';
$url = 'https://api.openweathermap.org/data/2.5/forecast';

// Hava durumu bilgilerini alma fonksiyonu
function get_weather($latitude, $longitude) {
    global $api_key, $url;
    $params = http_build_query(['lat' => $latitude, 'lon' => $longitude, 'appid' => $api_key, 'lang' => 'tr']);
    $response = @file_get_contents("{$url}?{$params}");
    
    if ($response === FALSE) {
        return null;
    }
    
    $data = json_decode($response, true);
    
    if ($data['cod'] == 200) {
        $weather_data = [];
        foreach ($data['list'] as $hour) {
            $date = date('Y-m-d H:i', $hour['dt']);
            $temp = round($hour['main']['temp'] - 273.15, 1);
            $condition = $hour['weather'][0]['description'];
            $icon = $hour['weather'][0]['icon'];

            $weather_data[] = [
                'datetime' => $date,
                'temperature' => $temp,
                'condition' => $condition,
                'icon' => $icon
            ];
        }

        return $weather_data;
    } else {
        return null;
    }
}

$currentWeather = null;
$searchedWeather = null;
$searchedCity = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mevcut konumdan hava durumu alma
    if (isset($_POST['location'])) {
        $location = $_POST['location']; 
        $locationData = json_decode($location, true);
        
        if (isset($locationData['latitude']) && isset($locationData['longitude'])) {
            $latitude = $locationData['latitude'];
            $longitude = $locationData['longitude'];

            $currentWeather = get_weather($latitude, $longitude);
        }
    }

    // Şehir adı ile hava durumu alma
    $searchedCity = $_POST['city_name'] ?? '';
    
    if (!empty($searchedCity)) {
        // Veritabanından şehir koordinatlarını alma
        $stmt = $conn->prepare("SELECT latitude, longitude FROM sehirler WHERE sehir_adi = ?");
        $stmt->bind_param("s", $searchedCity);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $latitude = $row['latitude'];
            $longitude = $row['longitude'];

            $searchedWeather = get_weather($latitude, $longitude);
        } else {
            $searchedWeather = null; 
        }
    }
}

// Mevcut konumdan sonra hava durumu alacak şehirleri belirleme
$cityCoordinates = [];
if ($currentWeather) {
    // Veritabanından mevcut konuma en yakın 3 şehri al
    $stmt = $conn->prepare("SELECT sehir_adi, latitude, longitude FROM sehirler WHERE NOT sehir_adi = (SELECT sehir_adi FROM sehirler WHERE latitude = ? AND longitude = ?) LIMIT 3");
    $stmt->bind_param("dd", $latitude, $longitude);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $cityCoordinates[] = [
            'name' => $row['sehir_adi'],
            'latitude' => $row['latitude'],
            'longitude' => $row['longitude']
        ];
    }
}

// Toplamda 5 şehir için hava durumu bilgisi alma
$weatherData = [];
if ($currentWeather) {
    $weatherData[] = ['city' => 'Mevcut Konum', 'data' => $currentWeather];
}

foreach ($cityCoordinates as $city) {
    $weather = get_weather($city['latitude'], $city['longitude']);
    if ($weather) {
        $weatherData[] = ['city' => $city['name'], 'data' => $weather];
    }
}

// Kullanıcının aradığı şehir için hava durumu alma
if (!empty($searchedCity)) {
    $searchedWeather = get_weather($latitude, $longitude);
    if ($searchedWeather) {
        $weatherData[] = ['city' => $searchedCity, 'data' => $searchedWeather];
    }
}

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hava Durumu Uygulaması</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0; /* Tüm boşlukları sıfırla */
            padding: 0; /* Tüm boşlukları sıfırla */
            box-sizing: border-box; /* Kutu boyutlandırmayı standart hale getir */
        }

        body { 
            font-family: 'Roboto', sans-serif; 
            background-color: #e3f2fd; 
            color: #333; 
            height: 100vh; /* Yüksekliği ayarladım */
            overflow: hidden; /* Kaydırma çubuğunu kaldır */
        }
        form { 
            display: flex; 
            justify-content: flex-end; 
            margin-bottom: 0; /* Yükseklik boşluğunu 0 yaptım */
        }
        input[type="text"] {
            padding: 5px; /* Boyutu küçülttüm */
            border: 1px solid #ccc; 
            border-radius: 5px; 
            width: 150px; /* Boyutu küçülttüm */
            margin-right: 5px; /* Boşluğu küçülttüm */
        }
        button {
            padding: 5px 10px; /* Boyutu küçülttüm */
            border: none; 
            border-radius: 5px; 
            background-color: #2196f3; 
            color: white; 
            cursor: pointer; 
            transition: background-color 0.3s; 
        }
        button:hover {
            background-color: #1976d2; 
        }
        .weather-container {
            display: flex; 
            justify-content: flex-start; 
            flex-wrap: nowrap; /* Kartların aynı hizada olmasını sağladım */
            margin-top: 20px; 
            overflow: hidden; /* Taşmayı önlemek için */
            position: relative; /* Animasyon için pozisyon ayarı */
        }
        .weather-card { 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            padding: 10px; /* Boyutu küçülttüm */
            margin: 10px; 
            width: 250px; /* Boyutu küçülttüm */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2); 
            background-color: white; 
            text-align: center; 
            overflow: hidden; /* Taşmayı önlemek için */
            max-height: 150px; /* Yüksekliği 200 piksel yapıldı */
        }
        .weather-card img { 
            width: 100%; /* Simgeyi kartın genişliğine uyacak şekilde ayarladım */
            height: auto; 
            max-height: 100px; /* Simge yüksekliği 100 piksel yapıldı */
        }
        .weather-info {
            font-size: 1em; /* Boyutu küçülttüm */
        }
        .temperature {
            font-size: 1.5em; /* Boyutu küçülttüm */
            color: red; 
        }
    </style>
</head>
<body onload="getLocation()">

    <form method="post">
        <input type="hidden" id="location" name="location">
        <input type="text" name="city_name" placeholder="Şehir adı" required>
        <button type="submit">Göster</button>
    </form>

    <div class="weather-container" id="weatherContainer">
        <?php foreach ($weatherData as $weather): ?>
            <div class="weather-card">
                <h2><?php echo htmlspecialchars($weather['city']); ?>:</h2>
                <?php foreach ($weather['data'] as $hour): ?>
                    <div class="weather-info">
                        <div class="temperature"><?php echo htmlspecialchars($hour['temperature']); ?>°C</div>
                        <div><?php echo htmlspecialchars($hour['condition']); ?></div>
                        <img src="http://openweathermap.org/img/wn/<?php echo htmlspecialchars($hour['icon']); ?>.png" alt="Hava durumu simgesi">
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    var lat = position.coords.latitude;
                    var lon = position.coords.longitude;
                    document.getElementById("location").value = JSON.stringify({ latitude: lat, longitude: lon });
                });
            } else {
                alert("Geolokalizasyon desteklenmiyor.");
            }
        }

        // Sağdan sola kaydırma animasyonu
        let scrollContainer = document.getElementById("weatherContainer");
        let scrollPosition = 0;

        function scrollWeather() {
            scrollContainer.style.transform = `translateX(-${scrollPosition}px)`;
            scrollPosition += 1;

            // Eğer 30 saniye geçtiyse, kaydırmayı durdur
            if (scrollPosition > (scrollContainer.scrollWidth - scrollContainer.clientWidth)) {
                setTimeout(() => {
                    scrollPosition = 0; // Başlangıç noktasına dön
                }, 30000); // 30 saniye dur
            }

            requestAnimationFrame(scrollWeather); // Sonsuz döngü
        }

        requestAnimationFrame(scrollWeather); // Animasyonu başlat
    </script>
</body>
</html>

<?php
$conn->close();
?>
 