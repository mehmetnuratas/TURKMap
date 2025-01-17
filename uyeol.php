<?php
// Veritabanı bağlantısı
$servername = "localhost";
$username = "root"; // Kullanıcı adınızı buraya girin
$password = ""; // Şifrenizi buraya girin
$dbname = "php_veri_tabanım"; // Veritabanı adınızı buraya girin

// Bağlantıyı oluştur
$conn = new mysqli($servername, $username, $password, $dbname);

// Bağlantı hatası kontrolü
if ($conn->connect_error) {
    die("Bağlantı başarısız: " . $conn->connect_error);
}

// Form gönderildiğinde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['next_step'])) {
        // Kullanıcı tipi seçimi
        $user_type = $_POST['user_type'];

        // E-posta kontrolü
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password']; // Şifreyi geçici olarak saklayacağız

        // E-posta kontrolü
        $email_check = $conn->prepare("SELECT * FROM kullanicilar WHERE email = ?");
        $email_check->bind_param("s", $email);
        $email_check->execute();
        $result = $email_check->get_result();

        if ($result->num_rows > 0) {
            $message = "<p style='color: red; text-align: center;'>Bu e-posta adresi zaten kayıtlı.</p>";
        } else {
            // Kullanıcı tipi belirleme
            $is_premium = 0; // Varsayılan olarak normal kullanıcı

            if ($user_type == "premium") {
                $is_premium = 1; // Premium kullanıcı
            } elseif ($user_type == "restaurant") {
                $is_premium = 2; // Restoran sahibi
            }

            // Form verilerini session ile geçici sakla
            session_start();
            $_SESSION['email'] = $email;
            $_SESSION['password'] = password_hash($password, PASSWORD_DEFAULT);
            $_SESSION['user_type'] = $user_type; // Kullanıcı tipini session'a kaydet

            // Eğer normal kullanıcı ise kredi kartı bilgisi sormayacağız
            if ($user_type == "normal") {
                // Kredi kartı bilgileri istenmeyecek, direkt olarak kullanıcıyı kaydediyoruz
                $sql_user = $conn->prepare("INSERT INTO kullanicilar (email, password, is_premium) VALUES (?, ?, ?)");
                $sql_user->bind_param("ssi", $email, $_SESSION['password'], $is_premium);
                if ($sql_user->execute()) {
                    $message = "<p style='color: green; text-align: center;'>Kullanıcı başarıyla kaydedildi.</p>";
                } else {
                    $message = "<p style='color: red; text-align: center;'>Hata: " . $conn->error . "</p>";
                }

                // Oturumu temizle
                session_destroy();
            } else {
                // Premium veya restoran kullanıcıları için kredi kartı bilgileri formu göster
                $show_card_form = true;
            }
        }
    } elseif (isset($_POST['save'])) {
        // Kredi kartı bilgilerini kaydet
        session_start();
        $email = $_SESSION['email'];
        $password = $_SESSION['password'];
        $user_type = $_SESSION['user_type']; // Kullanıcı tipini al
        
        $card_number = preg_replace('/\s+/', '', $_POST['card_number']); // Boşlukları kaldır
        $expiry = $_POST['expiry'];
        $cvv = $_POST['cvv'];
        $kart_sahibi_adı = $_POST['kart_sahibi_adı'];

        // Kullanıcı türüne göre is_premium değerini ayarlama
        $is_premium = 0;
        if ($user_type == "premium") {
            $is_premium = 1; // Premium kullanıcı
        } elseif ($user_type == "restaurant") {
            $is_premium = 2; // Restoran sahibi
        }

        // Kart numarasını doğrulama (sadece rakam ve 16 haneli)
        if (!preg_match('/^\d{16}$/', $card_number)) {
            $message = "<p style='color: red; text-align: center;'>Geçersiz kart numarası. Lütfen 16 haneli bir kart numarası girin.</p>";
        }
        // Son kullanma tarihini doğrulama (MM/YY formatı)
        elseif (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) {
            $message = "<p style='color: red; text-align: center;'>Geçersiz tarih. Lütfen MM/YY formatında bir tarih girin.</p>";
        }
        // CVV doğrulaması (3 haneli)
        elseif (!preg_match('/^\d{3}$/', $cvv)) {
            $message = "<p style='color: red; text-align: center;'>Geçersiz CVV. Lütfen 3 haneli bir CVV girin.</p>";
        }
        // Kart sahibi adı doğrulaması
        elseif (!preg_match('/^[a-zA-Z\s]+$/', $kart_sahibi_adı)) {
            $message = "<p style='color: red; text-align: center;'>Geçersiz kart sahibi adı. Lütfen sadece harfler ve boşluk girin.</p>";
        } else {
            // Kullanıcıyı veritabanına ekleme
            $sql_user = $conn->prepare("INSERT INTO kullanicilar (email, password, is_premium) VALUES (?, ?, ?)");
            $sql_user->bind_param("ssi", $email, $password, $is_premium);

            if ($sql_user->execute()) {
                $kullanici_id = $conn->insert_id; // Yeni eklenen kullanıcının ID'sini al

                // Kredi kartı bilgisini veritabanına ekleme
                $sql_card = $conn->prepare("INSERT INTO kredi_kartlari (kullanici_id, kart_numarasi, son_kullanma_tarihi, cvv, kart_sahibi_adı) VALUES (?, ?, ?, ?, ?)");
                $sql_card->bind_param("issss", $kullanici_id, $card_number, $expiry, $cvv, $kart_sahibi_adı);
                
                if ($sql_card->execute()) {
                    $message = "<p style='color: green; text-align: center;'>Kredi kartı bilgileri başarıyla kaydedildi.</p>";
                } else {
                    $message = "<p style='color: red; text-align: center;'>Hata: " . $conn->error . "</p>";
                }
            } else {
                $message = "<p style='color: red; text-align: center;'>Hata: " . $conn->error . "</p>";
            }

            // Oturumu temizle
            session_destroy();
        }
    }

    // Bağlantıyı kapat
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödeme Onay Ekranı</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { ... } /* Yukarıdaki CSS korundu */
        input[name="next_step"] {
            display: none; /* Başlangıçta görünmez */
        }
		body {
    font-family: 'Poppins', sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    background: linear-gradient(180deg, #0f0f0f, #58006d); /* Koyu siyah ve mor geçişi */
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
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1), rgba(0, 0, 0, 0.9));
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

.container {
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

.container::before {
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
input[type="password"],
input[type="text"] {
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
input[type="password"]:focus,
input[type="text"]:focus {
    border-color: #ff758c;
    outline: none;
    box-shadow: 0 0 8px rgba(255, 117, 140, 0.7);
}

input[type="radio"] {
    margin-right: 10px;
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

.btn {
    display: inline-block;
    text-decoration: none;
    text-align: center;
    background: #ff758c;
    color: #fff;
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: bold;
    transition: background 0.3s, transform 0.2s;
}

.btn:hover {
    background: #ff7eb3;
    transform: scale(1.05);
}
input[type="submit"] {
    width: 100%;
    padding: 15px;
    background: linear-gradient(90deg, #ff758c, #ff7eb3); /* Pembe ve mor geçişli bir renk */
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

input[type="submit"]:hover {
    background: linear-gradient(90deg, #ff7eb3, #ff758c); /* Renk geçişinin ters versiyonu */
    transform: translateY(-2px);
    box-shadow: 0 6px 10px rgba(0, 0, 0, 0.2);
}

input[type="submit"]:active {
    background: linear-gradient(90deg, #d04770, #a83c85); /* Daha koyu pembe ve mor tonları */
    transform: translateY(2px);
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
}

    </style>
</head>
<body>
    <div class="container">
        <h2><?php echo isset($show_card_form) ? "Kredi Kartı Bilgilerinizi Girin" : "Kişisel Bilgilerinizi Girin"; ?></h2>
        <?php if (isset($message)) echo "<div class='message'>$message</div>"; ?>

        <form method="POST">
            <?php if (!isset($show_card_form)) { ?>
                <div class="section">
                    <label for="email">E-posta:</label>
                    <input type="email" id="email" name="email" required placeholder="E-posta adresinizi girin">
                    
                    <label for="password">Şifre:</label>
                    <input type="password" id="password" name="password" required placeholder="Şifrenizi girin">

                    <label>Kullanıcı Tipi:</label>
                    <input type="radio" id="normal" name="user_type" value="normal" checked>
                    <label for="normal">Normal Kullanıcı</label><br>
                    <input type="radio" id="premium" name="user_type" value="premium">
                    <label for="premium">Premium Kullanıcı</label><br>
                    <input type="radio" id="restaurant" name="user_type" value="restaurant">
                    <label for="restaurant">Restoran Sahibi</label>
                </div>
                <input type="submit" name="next_step" value="Devam Et">
				<br>
                <div class="button-container">
                    <a href="gir.php" class="btn">Giriş Yap</a>
                </div>
                <br>
                <div class="button-container">
                    <a href="anasayfa.php" class="btn">Anasayfaya Dön</a>
                </div>
            <?php } else { ?>
                <div class="section">
                    <label for="card_number">Kart Numarası:</label>
                    <input type="text" id="card_number" name="card_number" required pattern="\d{16}" maxlength="16" placeholder="1234 5678 9876 5432">
                    
                    <label for="expiry">Son Kullanma Tarihi (MM/YY):</label>
                    <input type="text" id="expiry" name="expiry" required pattern="^(0[1-9]|1[0-2])\/\d{2}$" maxlength="5" placeholder="MM/YY">
                    
                    <label for="cvv">CVV:</label>
                    <input type="text" id="cvv" name="cvv" required pattern="\d{3}" maxlength="3" placeholder="123">
                    
                    <label for="kart_sahibi_adı">Kart Sahibi Adı:</label>
                    <input type="text" id="kart_sahibi_adı" name="kart_sahibi_adı" required pattern="^[a-zA-Z\s]+$" placeholder="Ad Soyad">
                </div>
                <input type="submit" name="save" value="Kaydet">
            <?php } ?>
        </form>
    </div>
    <script>
          const premiumRadio = document.getElementById('premium');
        const restaurantRadio = document.getElementById('restaurant');
        const submitButton = document.querySelector('input[name="next_step"]');

        [premiumRadio, restaurantRadio].forEach(radio => {
            radio.addEventListener('change', () => {
                submitButton.style.display = premiumRadio.checked || restaurantRadio.checked ? 'block' : 'none';
            });
        });

        document.querySelectorAll('input[name="user_type"]').forEach(radio => {
            radio.addEventListener('change', () => {
                if (!premiumRadio.checked && !restaurantRadio.checked) {
                    submitButton.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>

