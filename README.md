1. Giriş Formu (gir.php)
Amaç: Kullanıcıların sistemlerine giriş yapmasını sağlar. 
Seçenler:
•	Kullanıcılar, kayıt sırasında seçtikleri e-posta adresini ve şifre ile giriş yapabilirler.
•	Giriş bilgileri doğrulandıktan sonra kullanıcı türüne göre (Normal, Premium, Restoran Premium, Admin) farklı sayfalara yönlendirilir.
•	Hatalı giriş durumunda, kişisel bilgilendirme mesajı görüntülenir.
________________________________________
2. Üye Olma Formu (uyeol.php)
Amaç: Yeni kullanıcıların sistemlerine kayıt olmasını sağlar. 
Seçenler:
•	Kullanıcıların reklam e-posta, şifre gibi bilgilerini doldurarak hesaplarını doldururlar.
•	Şifre güvenliği için şifreler hashlenerek saklanır.
•	Kayıt sırasında e-posta adresinin benzersiz olması kontrol edilir
3. Anasayfa Formu (anasayfa.php)
Amaç: Kullanıcının üye olmadan giriş yapabileceği ve rota oluşturabileceği bir alan oluşturmak. Bu form, kullanıcıların seyahat rotalarını planlamalarına olanak tanırken, aynı zamanda temel kullanıcı bilgilerinin genişletilmesine da imkan tanır.
Seçenekler:
•	Kullanıcılar e-posta ve şifre gibi birimleri doldurarak hesaplarını canlandırırlar.
•	Harita üzerinde mevcut konumlarını belirlerler ve şehir isimlerini kapsayan hedeflerini kapsarlar.
•	Kullanıcıların rota gösterileri sırasında yürüyüş, bisiklet, araç veya toplu taşıma gibi ulaşım türlerini seçebilecek seçenekler sunulur.
•	Gece modu özelliği ile kullanıcılar, harita ve görünüm görünümleri karanlık modda kullanılabilir.
•	Giriş yapmadan rota oluşturma özelliği, kullanıcıların hızlı ve kolay bir şekilde uygulamadan yararlanmalarını sağlar.
•	Kayıt işlemleri sırasında e-posta adreslerinin benzersizliği kontrol edilerek kullanıcı güvenliği sağlanır.

Amaç: Bu uygulama, kullanıcıların mevcut konumlarından başlayarak restoran seçebilmesine ve ardından seçilen yetenekleri ara nokta olarak kullanarak bir varış noktası döndürme oluşturma olanakları tanımaktadır. Ayrıca kullanıcılar, restoranları puanlayabilir ve yorum yapabilir.
Seçenekler ve Özellikler:map
1.	Kullanıcı Yönetimi:
o	Kullanıcı kayıtları PHP dosyası kontrol edildi. Giriş yapmayan kullanıcılar, otomatik olarak giriş sayfasına yönlendirilir.
o	Kullanıcının kayıt durumu (normal veya premium) ve oturum açma bilgileri üst panelde uygulanır.
2.	Harita ve Konum Yönetimi:
o	Kullanıcının mevcut kalış süresini haritada gösterebilmesi. Mevcut konum izni tarayıcı üzerinden alınır.
o	OpenStreetMap tabanlı harita paketleri kullanılmıştır. Harita, kullanıcının yapılandırması ve görselleştirilmiştir.
3.	Restoran Seçimi:
o	Restoranlar, veri tabanından dinamik olarak çekilerek ve kullanıcıların bir seçimiyle kullanılabilir.
o	Restoranlar, isim ve şehir bilgileriyle listelenir. Kullanıcı, seçilen restoranın koordinat ölçümleri.
4.	Rota Yaratma:
o	Kullanıcı, mevcut durumdaki güncelleme güncellemelerini ara nokta olarak kullanarak, hedef varış noktasından başlayarak bir rota belirleyebilir.
o	Rota, Broşür Yönlendirme Makinesi kullanılarak haritada çizilir ve otomatik olarak kullanıcı tarafından gösterilir.
o	Rotadaki ara noktalar, kullanıcıların daha doğru ve planlı seyahat etmelerini sağlar.
5.	Yıldızlama ve Yorumlama:
o	Kullanıcılar, seçtikleri yıldızlama için (1-5 arası) yapabilir ve kayıtları yorum olarak iletebilirler.
o	Yıldız ve veri kayıtlarının saklanmasına, kullanıcı geri bildirimleri saklanmasına.
6.	Gece Modu ve Tema Yönetimi:
o	Gece modu özelliğiyle kullanıcılar, şehrinin karanlık bir temasını kullanabilirler. Bu özelliği, uzun süreli kullanımda göz yorgunluğunu azaltmayı hedefler.
7.	Kontrol Paneli:
o	Kullanıcılar, kontrol panelinin açılıp dönebildiğini, restoranın yayıldığını, varış noktasının girilebildiği ve gece modülünün yönetilebildiğini yönetebilir.
o	Kontrol paneli gizlenebilir ve açılabilir bir yapıdadır, bu sayede ekran üzerinde daha fazla alan sağlanır.

Rota Hesaplayıcı Uygulamasıpreminyum
Amaç:
Bu uygulama, kullanıcıların mevcut konumlarından başlayarak restoran seçimlerini yapmalarını, indirimlerini görüntülemelerini ve kaydedilen varış noktası rota oluşturmalarını sağlamayı amaçlamaktadır. Kullanıcılar ayrıca bilgi edinmek için yorum yapabilir ve yıldızlama yapabilir. Sistem, kullanıcı kayıt yönetimi ve dinamik içerik sunma özellikleriyle hem kullanıcı dostu hem de etkileşimli bir deneyim sunar.
________________________________________
Seçenekler ve Özellikler:
1.	Kullanıcı Yönetimi:
o	PHP ile kayıt yönetimi yapılmıştır. Giriş yapmayan kullanıcılar, otomatik olarak giriş sayfasına yönlendirilir.
o	Giriş yapan kullanıcıların adı ve kayıt türü (normal/premium) üst panelde gösterilir.
2.	Harita ve Konum Yönetimi:
o	Kullanıcının mevcut haritasında görülebilir. Bu işlem, tarayıcıdan alınan izinle devam eder.
o	OpenStreetMap tabanlı harita yazılımları kullanılmış ve dinamik görselleştirme sağlanmıştır.
3.	Restoran Seçimi:
o	Restoran bilgileri veri tabanından dinamik olarak değiştirilir ve seçimler listelenir.
o	Kullanıcı, restoranların isimlerini, şehir birimlerini ve koordinatlarını görüntüleyebilir.
4.	Rota Hesaplama:
o	Kullanıcı mevcut başlangıç noktası olarak seçer, bir restoran belirler ve hedef varış noktasına başlayarak bir rota çizer.
o	Rota, Broşür Yönlendirme Makinesi ile çizilir ve harita üzerinde görselleştirilir.
5.	Yıldızlama ve Yorumlama:
o	Kullanıcılar, toplamda 1-5 arasında yıldızlama yapabilir ve görüntüleri yorum olarak iletebilirler.
o	Gönderilen yıldız ve veriler veri tabanına depolanır.
6.	İndirimler:
o	Restoranlara ait aktif indirimler, "İndirimleri Göster" butonuyla listelenir.
o	Liste restoran adı, açıklama, başlangıç ve bitiş tarihleri ile indirim yüzdesi gibi detaylar yer İndirimi içerir.
7.	Gece Modu:
o	Gece modu özelliğiyle dağıtım karanlık bir tema ile kullanılabilir. Bu özelliği, uzun süreli kullanımda göz yorgunluğunu azaltmayı hedefler.
8.	Dinamik Kontrol Paneli:
o	Kontrol paneli, açılıp kapanabilir bir yapıya sahiptir. Bu sayede ekran üzerindeki alan etkin bir şekilde kullanılabilir.
o	Panelde restoran seçimi, varış noktası girişi, rota programlama ve diğer işlemler yapılabilir.
Restoran Yönetici Formu(restoranpreminyum.php)
Amaç:
Site üzerinde restoran sahiplerine ait işletmelerini yönetebilecekleri bir platform sunmak. Bu form sayesinde işletme sahiplerinin restoranlarını sunar, güncelleyebilir ve özel indirim kampanyaları sunar.
Seçenekler ve Özellikler:
1.	Restoran Ekleme:
Kullanıcılar, işletmelerini sistem kurmak için etkileşimli bir harita kullanabilirler.
o	Konum Belirleme: Harita üzerindeki konumu seçilerek enlem ve boylam bilgileri otomatik olarak doldurulur.
o	Bilgi Girişi: İşletmenin adı ve içerdiği şehir bilgilerinin sisteme girilmesiyle kolayca sistemler kurulabilir.
2.	İndirim Ekleme:
Kullanıcılar, ekledikleri zenginlikler için özel kampanya kampanyaları içerir.
o	İndirim Detayları: Seçilen restoran için indirim yüzdesi, açıklama ve başlangıç-bitiş eklenir.
o	Kampanya Yönetimi: Eklenen indirimler, sunulan avantaj sunarak restoranın popülerliğini artırıyor.
3.	Restoran Silme:
Kullanıcılar, kayıtlı olan restoranlarını listeleyebilir ve istemedikleri restoranları sistemden kaldırabilir.
o	Silme işlemi yalnızca restoranın sahibi tarafından gerçekleştirilebilir.
Yönetici Ekranı(admin.php )
Amaç:
Tüm restoranları merkezi bir ekranda bulundurur, kullanıcıların depolamalarını düzenler ve bunların erişimini sağlar. Yönetici ekranı, site üzerindeki işletmeler ve kullanıcılar etkili bir şekilde kontrol etme genellemeleri.
Seçenekler ve Özellikler:
1.	Restoran Yönetimi:
o	Restoran Ekleme: Yöneticiler, harita üzerinden bir restoranın sistemini belirleyerek sistemin yeni özelliklerini barındırabilirler.
o	Restoran Düzenleme: Var olan restoranların alınması (isim, konum, şehir, vb.) güncellenebilir.
o	Restoran Silme: Sistemde kayıtlı restoranları silinebilir.
2.	Kullanıcı Yönetimi:
o	Kullanıcı Durumu: Kullanıcıların hesap durumlarını görüntüleyebilir ve yönetebilir. (Örneğin, admin yetkisinin tanımlanması veya kaldırılması.)
o	Kullanıcı İstatistikleri: Kullanıcıların sistem üzerindeki faaliyetlerini takip edebilir (ekledikleri, indirimler, yorumlar, vb.).
3.	İşletme İstatistikleri:
o	Tüm ayrıntılara ait genel ve ayrıntılı istatistiklere erişim sağlanır.
o	Veri Gösterimi: Restoran ziyaret aboneliği, kullanıcı geri bildirimleri (yorum ve yıldızlar), indirim başarı oranları gibi verilerle sunulabilir.
4.	Sistem Genel Yönetimi:
o	Kullanıcı ve restoran listelerine genel bir bakış.
o	Önemli verileri düzenleme veya silme yetkisi yoktur.
o	Site üzerinde yapılan işlemler ve aktiviteleri denetleyebilme.
 
Grafikler ve İstatistikler Formülü(grafikler.php)
Amaç:
Yöneticilere platform üzerindeki tüm istatistikler, bültenler ve listeler halinde sunarak daha etkin bir analiz ve yönetim olanağı sağlamak. Bu form, platformun genel özelliklerini ve kullanıcı tarafından gerçekleştirilebilmesi için ayrıntılı veriler sunar.
Seçenekler ve Özellikler:
1.	Kullanıcı İstatistikleri:
o	Toplam Kullanıcı Sayısı: Platformda kayıtlı tüm kullanıcıların toplamı.
o	Üyelik Durumları: Normal ve premium üyelik durumlarına göre kullanıcı özellikleri.
o	Kullanıcı Aktiviteleri: Kullanıcıların yaptığı işlemler (restoran ekleme, indirim oluşturma, yorum yapma gibi).
2.	Restoran İstatistikleri:
o	Toplam Restoran Sayısı: Sisteme kayıtlı tüm restoranların sayısı.
o	Yıldızlama ortalamaları: Restoranların kullanıcıları tarafından verilen yıldız ortalamaları.
o	Restoranlara Göre Yorumlar: Restoranların içerdiği toplam yorumlar ve içerik ayrıntıları.
3.	İndirim İstatistikleri:
o	Toplam İndirim Sayısı: Aktif ve geçmiş indirimlerin toplamı.
o	İndirim Başarı Oranları: Kullanıcıların indirimlerinden yararlanma yüzdesi.
o	İndirimlerin Tarihsel Dağılımı: Esnek bir zaman dilimindeki indirim Hareketleri.
4.	Yorum ve Yıldızlama Verileri:
o	En Çok Yorum Alan Restoranlar: Yorumları göre sıralanıyor.
o	En Yüksek Yıldızlı Restoranlar: Kullanıcıların verdiği yıldızların baz sıralaması.
o	Yorumların İçerik Analizi: olumsuz ve olumsuz geri bildirim oranları.
5.	Grafiksel Gösterimler:
o	Kullanıcı Dağılımı: Makarna grafik veya çubuk grafik ile üyelik türlerinin mevcut.
o	Restoran Performansı: Restoranların ortalama yıldızlarını ve yorum sayılarını gösteren raporlardır.
o	Tarihsel İndirim Kullanımı: İndirimlerin zaman çalışmasını gösteren çizgi grafikleri.



3.3 Hangi veritabanı yapısını kullandınız? Veritabanı bağlantısını hangi yazılımla sağladınız? Veritabanınız kaç tablodan oluşuyor? Bu veritabanını başka hangi projede kullandınız?
Projemizde MySQL veritabanı yapısı kullanılmıştır. MySQL, yüksek performans, ölçeklenebilirlik ve kolay kullanım özellikleriyle projemiz için uygun bir seçim olmuştur. Veritabanı bağlantısı, PHP'nin dahili MySQLi (MySQL Improved) kütüphanesi sayesinde sağlanır. Bu dosyalama, hem güvenli hem de esnek parçaları kurmamızı sağlamış ve dinamik sorgularımızı yönetmemize yardımcı olmuştur.
Veritabanı Yapısı ve Tablolar:
Bu projede toplamda 5 tablo bulunmaktadır:
1.	kullanicilar : Sisteme kayıtlı kullanıcıların bilgilerini içerir.
2.	restaurants : Kullanıcıların eklediği işletmelere  ait bilgiler saklar.
3.	indirimlerr : Restoranlara ait aktif ve geçmiş indirimleri içerir.
4.	yildizlar: Kullanıcıların işletmelere  yaptıkları  yıldızlar ve yorumlar  saklanır.
5.	kredi_kartlari Premium kullanıcılar ve restoran sahiplerinin kredi kartı bilgilerini tutan tablomuz.
Not: Bu veritabanında daha önce şehirler, bölge ve rota oluşturma işlemleri için kullanılan tablolar, yapılan son düzenlemeler ve sunumda kod güncellemeleri ile birlikte OpenStreetMap üzerinden alınan verilerle güncellenmiştir. OpenStreetMap'in ayrıntılı ve özet veri seti, sistemin daha iyi ve verimli çalışmasını sağlar. Bu nedenle rota oluşturma oranları OpenStreetMap verileri kullanılmaktadır.
