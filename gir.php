<?php
// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "php_veri_tabanım";

// Bağlantıyı oluştur
$conn = new mysqli($servername, $username, $password, $dbname);

// Bağlantı hatası kontrolü
if ($conn->connect_error) {
    die("Bağlantı başarısız: " . $conn->connect_error);
}

// Form gönderildiğinde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // Kullanıcı doğrulama sorgusu
    $sql = $conn->prepare("SELECT id, email, password, credit_card, security_answer, created_at, is_premium FROM kullanicilar WHERE email = ?");
    $sql->bind_param("s", $email);
    $sql->execute();
    $result = $sql->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();

        // Şifre kontrolü
        if (password_verify($password, $row['password'])) {
            session_start();
            $_SESSION['user'] = $row; // Kullanıcı bilgilerini session'da tut

            // Kullanıcı türüne göre yönlendirme
            if ($row['is_premium'] == 0) { // Normal kullanıcı
                header("Location: http://localhost/map.php");
            } elseif ($row['is_premium'] == 1) { // Premium kullanıcı
                header("Location:  http://localhost/preminyum.php");
            } elseif ($row['is_premium'] == 2) { // Restoran Premium kullanıcı
                header("Location: http://localhost/restoranpreminyum.php");
            } elseif ($row['is_premium'] == 3) { // Admin kullanıcı
                header("Location: http://localhost/admin.php");
            } else {
                echo "Geçersiz kullanıcı türü.";
            }
            exit;
        } else {
            $message = "Hatalı şifre.";
        }
    } else {
        $message = "Kullanıcı bulunamadı.";
    }
}

// Bağlantıyı kapat
$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Giriş Ekranı</title>
    <style>
 body {
    font-family: 'Poppins', sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    background: linear-gradient(180deg, #0f0f0, #58006d); /* Koyu siyah ve daha belirgin mor geçişi */
    background-size: cover;
    background-position: center;
    height: 100vh;
    margin: 0;
    position: relative;
    overflow: hidden;
}

body::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8); /* Hafif kararma efekti */
    z-index: -1;
    pointer-events: none; /* Yıldızlar formu etkilemesin */
    animation: twinkle 1.5s infinite alternate;
}

body::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1), rgba(0, 0, 0, 0.9)); /* Yıldız efekti için */
    pointer-events: none;
    z-index: -1;
    box-shadow: 
        100px 50px 0px 10px rgba(255, 255, 0, 1), 
        250px 150px 0px 10px rgba(255, 255, 0, 1),
        380px 200px 0px 10px rgba(255, 255, 0, 1),
        500px 300px 0px 10px rgba(255, 255, 0, 1),
        150px 350px 0px 10px rgba(255, 255, 0, 1),
        600px 500px 0px 10px rgba(255, 255, 0, 1),
        700px 100px 0px 10px rgba(255, 255, 0, 1),
        400px 50px 0px 10px rgba(255, 255, 0, 1),
        600px 300px 0px 10px rgba(255, 255, 0, 1),
        200px 500px 0px 10px rgba(255, 255, 0, 1);
}

@keyframes twinkle {
    0% {
        opacity: 0.6;
    }
    100% {
        opacity: 1;
    }
}

.form-container {
    background: rgba(255, 255, 255, 0.9); /* Hafif beyaz opak arka plan */
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    width: 100%;
    max-width: 500px;
    position: relative;
    overflow: hidden;
    z-index: 1;
    backdrop-filter: blur(10px); /* Bulanık arka plan efekti */
}

.form-container::before {
    content: '';
    position: absolute;
    top: -70px;
    left: -70px;
    width: calc(100% + 140px);
    height: calc(100% + 140px);
    border: 3px solid rgba(0, 0, 0, 0.7);
    border-radius: 20px;
    animation: rotate-border 8s linear infinite;
    z-index: -1;
}

@keyframes rotate-border {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

h2 {
    text-align: center;
    color: #000; /* Yazıyı siyaha çevirdim */
    font-size: 32px; /* Yazı boyutunu büyüttüm */
    font-weight: bold;
    margin-bottom: 30px;
}

label {
    display: block;
    margin-bottom: 12px;
    font-weight: 600;
    color: #000; /* Yazıyı siyaha çevirdim */
}

input[type="email"],
input[type="password"] {
    width: 100%;
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid #ccc;
    border-radius: 10px;
    font-size: 16px;
    box-shadow: inset 0 3px 6px rgba(0, 0, 0, 0.1);
    transition: border 0.3s, box-shadow 0.3s;
    background-color: #f7f7f7; /* Giriş kutusunu daha belirgin yapmak için arka plan rengi ekledim */
}

input[type="email"]:focus,
input[type="password"]:focus {
    border-color: #ff758c;
    outline: none;
    box-shadow: 0 0 8px rgba(255, 117, 140, 0.7);
}

button {
    width: 100%;
    padding: 15px;
    background: linear-gradient(90deg, #ff7eb3, #ff758c);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 18px;
    font-weight: bold;
    text-transform: uppercase;
    cursor: pointer;
    transition: background 0.3s, transform 0.2s;
}

button:hover {
    background: linear-gradient(90deg, #ff758c, #ff7eb3);
    transform: scale(1.05);
}

.message {
    text-align: center;
    color: red;
    font-size: 16px;
    margin-bottom: 15px;
}

    </style>
</head>
<body>
    <div class="form-container">
        <h2>Giriş Yap</h2> 
        <?php if (isset($message)) echo "<div class='message'>$message</div>"; ?>
        <form method="POST">
            <label for="email">E-posta:</label>
            <input type="email" id="email" name="email" required>
            <label for="password">Şifre:</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Giriş Yap</button>
			<div class="button-container">
                    <a href="uyeol.php" class="btn">Üye Ol</a>
					<br>
					 <a href="anasayfa.php" class="btn">Anasyfaya dön</a>
                    </div>
        </form>
    </div>

</body>
</html>
