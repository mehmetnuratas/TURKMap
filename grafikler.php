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

// Kullanıcı Verisini Çek
$sql_users = "
    SELECT DATE(created_at) AS tarih, is_premium, COUNT(*) AS kullanici_sayisi
    FROM kullanicilar
    GROUP BY DATE(created_at), is_premium
    ORDER BY DATE(created_at)
";
$result_users = $conn->query($sql_users);

// Kullanıcı Verilerini İşle
$tarih = [];
$user_data = [0 => [], 1 => [], 2 => [], 3 => []];
$unique_dates = [];

if ($result_users->num_rows > 0) {
    while ($row = $result_users->fetch_assoc()) {
        $date = $row['tarih'];
        $type = (int)$row['is_premium'];
        $count = (int)$row['kullanici_sayisi'];

        if (!in_array($date, $unique_dates)) {
            $unique_dates[] = $date;
        }

        $user_data[$type][$date] = $count;
    }
}

// Tüm tarihleri için eksik verileri 0 olarak doldur
foreach ($user_data as $type => &$counts) {
    foreach ($unique_dates as $date) {
        if (!isset($counts[$date])) {
            $counts[$date] = 0;
        }
    }
    ksort($counts);
}

// JSON Formatında Veriyi Dönüştür
$unique_dates_json = json_encode($unique_dates);
$user_data_json = json_encode($user_data);

// Restoran Yıldız ve Yorumları
$sql = "
    SELECT r.name AS restoran_adi, AVG(y.yildiz_sayisi) AS ortalama_yildiz
    FROM yildizlar y
    JOIN restaurants r ON y.restoran_id = r.id
    GROUP BY y.restoran_id, r.name
";
$result = $conn->query($sql);

// Tom Yıldızlaması İçin SQL Sorgusu
$tom_sql = "SELECT AVG(yildiz_sayisi) AS tom_yildiz FROM yildizlar WHERE kullanici_id = 1"; // Tom için kullanıcı_id = 1
$tom_result = $conn->query($tom_sql);
$tom_yildiz = 0; // Varsayılan değer
if ($tom_result && $tom_result->num_rows > 0) {
    $row = $tom_result->fetch_assoc();
    $tom_yildiz = round($row["tom_yildiz"], 2); // Tom'un yıldızlamasını al
}

// Veriyi İşle
$restoran_adlari = [];
$ortalama_yildizlar = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $restoran_adlari[] = $row["restoran_adi"];
        $ortalama_yildizlar[] = round($row["ortalama_yildiz"], 2);
    }
}

// JSON Formatında Veriyi Dönüştür
$restoran_adlari_json = json_encode($restoran_adlari);
$ortalama_yildizlar_json = json_encode($ortalama_yildizlar);

// Kullanıcı Tiplerinin Toplam Sayısı
$total_users_sql = "
    SELECT is_premium, COUNT(*) AS toplam
    FROM kullanicilar
    GROUP BY is_premium
";
$result_totals = $conn->query($total_users_sql);

$total_users = [];
while ($row = $result_totals->fetch_assoc()) {
    $total_users[$row['is_premium']] = $row['toplam'];
}

// Restoranlar Listesini Al
$restoran_sql = "SELECT id, name FROM restaurants";
$restoran_result = $conn->query($restoran_sql);

$restoranlar = [];
if ($restoran_result->num_rows > 0) {
    while ($row = $restoran_result->fetch_assoc()) {
        $restoranlar[] = $row;
    }
}

// Seçilen Restoranın Yorumlarını Al (Form ile seçildiyse)
$selected_restaurant = isset($_GET['restoran_id']) ? (int)$_GET['restoran_id'] : 0;
$yorumlar_by_restaurant = [];

if ($selected_restaurant > 0) {
    $yorum_sql = "
        SELECT r.name AS restoran_adi, y.yorum
        FROM yildizlar y
        JOIN restaurants r ON y.restoran_id = r.id
        WHERE r.id = $selected_restaurant
    ";
    $yorum_result = $conn->query($yorum_sql);

    if ($yorum_result->num_rows > 0) {
        while ($yorum_row = $yorum_result->fetch_assoc()) {
            $yorumlar_by_restaurant[] = $yorum_row['yorum'];
        }
    }
}

// JSON Formatında Veriyi Dönüştür
$total_users_json = json_encode($total_users);

$conn->close();
?>
<!DOCTYPE html><html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restoran Yıldız ve Kullanıcı Grafikleri</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #212529;
            font-family: 'Inter', sans-serif;
            color: #e1e4e8;
        }

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
        }

        .profile-section {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .profile-section img {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            border: 3px solid white;
        }

        .profile-section button {
            background-color: #00d2ff;
            color: #212529;
            padding: 12px 18px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
        }

        .container {
            padding: 30px;
            background-color: #2c2f33;
        }

        h1, h2 {
            color: #ffffff;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }

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

        .charts-container {
            display: flex;
            justify-content: space-between;
            gap: 30px;
            margin-top: 40px;
        }

        .chart-small {
            max-width: 600px;
            max-height: 550px;
            margin: 15px;
            box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
            background-color: #ffffff;
        }

        .chart-medium {
            max-width: 900px;
            max-height: 500px;
            margin: 20px;
            box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
            background-color: #ffffff;
        }

        .chart-large {
            max-width: 600px;
            max-height: 550px;
            margin: 25px;
            box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
            background-color: #ffffff;
        }

        .comments-container {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 20px;
            box-shadow: 0px 8px 35px rgba(0, 0, 0, 0.12);
            margin-top: 40px;
        }

        .comments-container h3 {
            margin-bottom: 20px;
            color: #2980b9;
        }

        .restaurant-comments {
            margin-top: 15px;
            padding: 10px;
            background-color: #fafafa;
            border-radius: 10px;
            box-shadow: 0px 3px 8px rgba(0, 0, 0, 0.1);
        }

        .comment {
            padding: 10px;
            margin: 10px 0;
            background-color: #f1f1f1;
            border-radius: 8px;
            color: #555;
            box-shadow: 0px 3px 5px rgba(0, 0, 0, 0.1);
        }

        .select-form {
            margin-top: 40px;
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        select, button {
            padding: 10px 15px;
            font-size: 1.1rem;
            border-radius: 8px;
            border: 1px solid #ddd;
            transition: all 0.3s ease;
        }

        button {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        @media (max-width: 768px) {
            .charts-container {
                flex-direction: column;
                align-items: center;
            }
            .charts-container canvas {
                max-width: 80%;
            }
        }
    </style>
</head>
<body>
<div class="header">
    <div class="profile-section">
        <img src="path_to_user_image.jpg" alt="Profil Resmi">
        <div>
            <span>Hoşgeldiniz, <?= htmlspecialchars($user['email']) ?></span><br>
            <span>Üyelik: <?= $user['is_premium'] ? "Premium" : "Normal" ?></span>
        </div>
        <button onclick="window.location.href='admin.php';">YÖNETİCİ EKRANI</button>
		<button onclick="window.location.href='anasayfa.php';">Çıkış Yap </button>
    </div>
</div>

<div class="container">
    <h1>Restoran Yıldız ve Kullanıcı Grafikleri</h1>
    <p>Tom Yıldızlaması: <?php echo $tom_yildiz; ?> Yıldız</p>

    <div class="charts-container">
        <canvas id="userDistributionChart" class="chart-small"></canvas> <!-- Küçük Grafik -->
        <canvas id="starChart" class="chart-medium"></canvas>         <!-- Orta Grafik -->
    </div>

    <div class="charts-container">
        <canvas id="starDistributionChart" class="chart-large"></canvas> <!-- Büyük Grafik -->
        <canvas id="userTimelineChart" class="chart-medium"></canvas> <!-- Orta Grafik -->
    </div>

    <form class="select-form" method="get" action="">
        <label for="restoran_id">Bir Restoran Seçin:</label>
        <select name="restoran_id" id="restoran_id">
            <option value="">-- Restoran Seçin --</option>
            <?php foreach ($restoranlar as $restoran): ?>
                <option value="<?php echo $restoran['id']; ?>" <?php echo ($restoran['id'] == $selected_restaurant) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($restoran['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Yorumları Göster</button>
    </form>

    <?php if ($selected_restaurant > 0 && count($yorumlar_by_restaurant) > 0): ?>
        <div class="comments-container">
            <h3>Yorumlar:</h3>
            <div class="restaurant-comments">
                <h4><?php echo htmlspecialchars($restoranlar[array_search($selected_restaurant, array_column($restoranlar, 'id'))]['name']); ?></h4>
                <?php foreach ($yorumlar_by_restaurant as $yorum): ?>
                    <div class="comment"><?php echo htmlspecialchars($yorum); ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php elseif ($selected_restaurant > 0): ?>
        <p>Bu restorana ait henüz yorum yapılmamış.</p>
    <?php endif; ?>
</div>

<script>
// JavaScript Grafik Verisi ve Yapılandırma
const restoranAdlari = <?php echo $restoran_adlari_json; ?>;
const ortalamaYildizlar = <?php echo $ortalama_yildizlar_json; ?>;
const uniqueDates = <?php echo $unique_dates_json; ?>;
const userData = <?php echo $user_data_json; ?>;
const totalUsers = <?php echo $total_users_json; ?>;

// Kullanıcı Türleri Yüzde Dağılımı (Pie Chart)
const userTypes = {
    0: 'Normal Kullanıcı',
    1: 'Premium Kullanıcı',
    2: 'Restoran Premium Kullanıcı',
    3: 'Admin Kullanıcı'
};

const userLabels = Object.keys(totalUsers).map(key => userTypes[key]);
const userCounts = Object.values(totalUsers);

const ctx0 = document.getElementById('userDistributionChart').getContext('2d');
new Chart(ctx0, {
    type: 'pie',
    data: {
        labels: userLabels,
        datasets: [{
            data: userCounts,
            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#FF5733'],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: true, position: 'bottom' }
        }
    }
});

// Ortalamalar (Bar Chart)
const ctx1 = document.getElementById('starChart').getContext('2d');
new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: restoranAdlari,
        datasets: [{
            label: 'Ortalama Yıldız Sayısı',
            data: ortalamaYildizlar,
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true },
            x: { beginAtZero: true }
        }
    }
});

// Yıldız Dağılımı (Pie Chart)
const ctx2 = document.getElementById('starDistributionChart').getContext('2d');
new Chart(ctx2, {
    type: 'pie',
    data: {
        labels: restoranAdlari,
        datasets: [{
            data: ortalamaYildizlar,
            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#FF5733', '#DAF7A6'],
        }]
    },
});

// Kullanıcı Türleri Zaman Grafiği (Line Chart)
const ctx3 = document.getElementById('userTimelineChart').getContext('2d');
const datasets = Object.keys(userData).map(type => ({
    label: userTypes[type],
    data: uniqueDates.map(date => userData[type][date]),
    borderColor: ['#FF6384', '#36A2EB', '#FFCE56', '#FF5733'][type],
    fill: false,
    tension: 0.1
}));

new Chart(ctx3, {
    type: 'line',
    data: {
        labels: uniqueDates,
        datasets: datasets
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: true, position: 'bottom' }
        }
    }
});
</script>

</body>
</html> 
