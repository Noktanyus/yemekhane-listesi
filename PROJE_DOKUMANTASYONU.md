### Akdeniz Üniversitesi Yemekhane Menü Sistemi - Proje Dokümantasyonu

#### 1. Projeye Genel Bakış

Bu proje, Akdeniz Üniversitesi öğrencileri ve personeli için aylık yemek menüsünü sunan dijital bir takvim sistemidir. Projenin temel amacı, geleneksel listeleme yöntemleri yerine, kullanıcıların yemek menüsünü kolayca görüntüleyebileceği modern, interaktif ve estetik bir arayüz sağlamaktır.

**Yapılan Temel İyileştirmeler:**
Proje, statik ve eski görünümlü bir yapıdan, referans alınan modern takvim uygulamalarına benzer, dinamik ve kullanıcı dostu bir tasarıma kavuşturulmuştur. Bu süreçte kullanıcı deneyimi (UX) ve arayüz (UI) tamamen yenilenmiştir. Ayrıca, kod tabanının okunabilirliği, tutarlılığı ve sürdürülebilirliği artırılmıştır.

#### 2. Kullanılan Teknolojiler

*   **Backend:** PHP 7.x / 8.x
    *   Sunucu tarafı işlemler ve veritabanı etkileşimi için kullanılır.
    *   Kod kalitesi ve tutarlılığı için [PHP-CS-Fixer](https://cs.symfony.com/) aracı ile PSR-12 kod standardı uygulanmıştır.
*   **Veritabanı:** MySQL / MariaDB
    *   Yemek menüleri, özel günler, yönetici bilgileri, geri bildirimler ve log kayıtları gibi tüm proje verileri bu ilişkisel veritabanında saklanır.
    *   Veritabanı şeması, tutarlılığı ve yönetilebilirliği artırmak amacıyla yinelenen tablo tanımlarından arındırılmıştır.
*   **Frontend:**
    *   **HTML5:** Sayfa yapısı ve içeriğin semantik olarak düzenlenmesi için kullanılır.
    *   **CSS3:** Modern web standartlarına uygun olarak, Flexbox ve Grid gibi gelişmiş düzenleme teknikleri kullanılarak responsive ve estetik bir arayüz sağlanmıştır. Biçimlendirme ve tekrar eden CSS kuralları optimize edilerek kod okunabilirliği artırılmıştır.
    *   **JavaScript (Vanilla JS):** Herhangi bir harici kütüphaneye bağımlılık olmadan, dinamik takvim oluşturma, API etkileşimleri, modal pencerelerin yönetimi ve kullanıcı arayüzü etkileşimleri gibi tüm interaktif özellikler saf JavaScript ile geliştirilmiştir. AJAX (Asynchronous JavaScript and XML) tabanlı `fetch` API'si kullanılarak sunucu ile asenkron veri alışverişi sağlanır, bu da sayfa yenilemeden dinamik içerik yüklemesine olanak tanır.
*   **Veri Formatı:** CSV (Comma Separated Values)
    *   Yönetim panelinde toplu yemek listesi yüklemesi için standart bir veri formatı olarak kullanılır.
*   **Grafikleme:** Canvas tabanlı grafikler için (örn: Puan Dağılımı, Popüler Yemekler) istemci tarafında bir JavaScript grafik kütüphanesi ([Chart.js](https://www.chartjs.org/)) kullanılmıştır.

#### 3. Temel Özellikler

##### 3.1. Kullanıcı Arayüzü Özellikleri

*   **Modern Takvim Arayüzü:** Uygulama benzeri, temiz ve minimalist bir tasarıma sahiptir.
*   **Dinamik Ay Navigasyonu:** Kullanıcılar aylar arasında kolayca geçiş yapabilir ve tek tuşla mevcut güne dönebilir.
*   **Dinamik Sütun Yapısı:** Hafta içi günlerine daha fazla alan ayrılarak, hafta sonu sütunları daha dar tutulmuş ve böylece dolu günlerin okunurluğu artırılmıştır.
*   **İçerik Belirteçleri:** Her yemek, sol tarafında bulunan ince, renkli bir kenarlık ile belirtilerek hem estetik bir görünüm sunar hem de okunurluğu artırır.
*   **Özel Gün Gösterimi:** Resmi tatiller veya özel notlar, takvim üzerinde açık sarı bir arka planla vurgulanır.
*   **Detaylı Yemek Bilgisi (Modal):** Kullanıcılar, menüsü olan bir güne tıkladığında açılan pencerede yemeklerin kalori ve içerik bilgilerini (eğer girilmişse) görebilir.

##### 3.2. Yönetim Paneli Özellikleri

*   **Güvenli Yönetici Girişi:** Yönetim paneline sadece yetkili kullanıcıların erişebilmesi için bir giriş sistemi mevcuttur.
*   **Yemek Yönetimi:** Belirli bir tarihe yemek ekleme, mevcut yemeği düzenleme ve silme işlemleri yapılabilir.
*   **Özel Gün Yönetimi:** Belirli tarihleri "Resmi Tatil" gibi özel notlarla işaretleme imkanı vardır.
*   **Toplu Veri Yükleme (CSV):** Yönetim panelinin en güçlü özelliklerinden biridir. `ornek_menu_noktalivirgul.csv` formatına uygun olarak hazırlanan dosyalar sayesinde, bir ayın veya daha uzun bir sürenin tüm yemek menüsü tek bir işlemle sisteme yüklenebilir. Bu, manuel veri girişini ortadan kaldırarak büyük bir zaman tasarrufu sağlar.

#### 4. Güvenlik Önlemleri ve Öneriler

##### 4.1. Mevcut Güvenlik Önlemleri

*   **SQL Injection Koruması:** Veritabanı sorgularında **Hazırlanmış İfadeler (Prepared Statements)** kullanılarak, SQL enjeksiyonu gibi yaygın ve tehlikeli saldırıların önüne geçilmiştir. Bu, kullanıcı girdilerinin doğrudan SQL sorgularına dahil edilmesini engelleyerek veritabanı güvenliğini sağlar.
*   **Cross-Site Scripting (XSS) Koruması:** Kullanıcı tarafından girilen veriler (yemek isimleri, içerikler, geri bildirim yorumları vb.) ekrana yazdırılırken `htmlspecialchars()` gibi PHP fonksiyonları ile uygun şekilde filtrelenerek, kötü niyetli betiklerin çalıştırılması engellenmektedir.
*   **Veritabanı Erişim Güvenliği:** Veritabanı bağlantı bilgileri, ana kod dosyalarından ayrı bir `config.php` dosyasında tutularak yetkisiz erişime karşı korunmaktadır. Bu, hassas bilgilerin doğrudan kod içinde bulunmasını engeller.
*   **CSRF (Cross-Site Request Forgery) Koruması:** `includes/csrf.php` dosyasında tanımlanan `generate_csrf_token()` ve `validate_csrf_token()` fonksiyonları aracılığıyla CSRF saldırılarına karşı koruma sağlanmaktadır. Yönetim panelindeki kritik POST istekleri, sunucu tarafında bu token'ın doğrulanmasını gerektirir, böylece kullanıcının isteği dışında işlem yapılması engellenir.
*   **Kapsamlı İşlem Loglama:** Yönetim panelinde gerçekleştirilen her önemli işlem (`logs` tablosuna) detaylı olarak kaydedilir. Bu loglar, işlemi yapan yöneticinin kullanıcı adını, eylem türünü (örn: `csv_upload`, `meal_add`, `feedback_reply`), eylemin detaylarını, IP adresini ve zaman damgasını içerir. Bu sayede sistemdeki tüm yönetici aktiviteleri izlenebilir ve denetlenebilir.

##### 4.2. Olası Saldırılar ve Engelleme Yöntemleri (Öneriler)

*   **Dosya Yükleme Zafiyetleri (CSV Upload):**
    *   **Mevcut Durum:** Yüklenen CSV dosyaları geçici olarak `uploads/temp_csv/` dizininde benzersiz adlarla saklanır ve CSV içeriği doğrulanır.
    *   **Risk:** Mevcut durumda, yüklenen dosyanın sadece uzantısı değil, MIME tipi de sunucu tarafında tam olarak doğrulanmamaktadır. Bu durum, kötü niyetli bir kullanıcının zararlı bir betik dosyasını (örn: `.php` uzantılı) `.csv` gibi göstererek yüklemesine ve potansiyel olarak çalıştırmasına olanak tanıyabilir.
    *   **Çözüm:** Yüklenen dosyanın MIME tipi (örn: `text/csv`) ve dosya uzantısı sunucu tarafında kesinlikle doğrulanmalıdır. Ayrıca, dosya boyutu sınırlandırılmalı ve yüklenen dosyalar, web sunucusunun doğrudan erişemeyeceği güvenli bir dizinde saklanmalıdır.
*   **Brute Force Saldırıları (Admin Girişi):**
    *   **Risk:** Saldırganlar, admin paneli girişini binlerce parola deneyerek kırmaya çalışabilir.
    *   **Çözüm:** Belirli sayıda (örn: 5) başarısız giriş denemesinden sonra kullanıcının hesabı geçici olarak kilitlenmeli veya bir CAPTCHA doğrulaması istenmelidir.

#### 5. İstatistik ve Raporlama (Mevcut Özellikler)

Proje, yemekhane yönetimine değerli bilgiler sunan kapsamlı bir istatistik ve raporlama modülü içermektedir:
*   **Genel İstatistikler:** Toplam geri bildirim sayısı, ortalama geri bildirim puanı ve okunmamış yeni geri bildirim sayısı gibi genel metrikler sunulur.
*   **Ayın Popüler Yemekleri:** Mevcut ayda menülerde en çok yer alan ilk 10 yemeği listeler. Bu, menü planlaması için popüler yemekleri belirlemeye yardımcı olur.
*   **Puan Dağılımı:** Geri bildirimlerin 1'den 5'e kadar olan puanlara göre dağılımını gösterir. Bu, genel kullanıcı memnuniyetini anlamak için kritik bir veridir.
*   **Şikayetlerde Öne Çıkan Kelimeler:** Özellikle düşük puanlı (1 ve 2 yıldızlı) geri bildirim yorumlarından en sık geçen kelimeleri (durak kelimeler ve kısa kelimeler hariç) analiz eder. Bu özellik, kullanıcıların en çok hangi konularda şikayetçi olduğunu hızlıca tespit etmeye ve iyileştirme alanlarını belirlemeye olanak tanır.
Bu raporlar, yemekhane yönetiminin menüleri optimize etmesine, kullanıcı geri bildirimlerini daha etkin analiz etmesine ve genel hizmet kalitesini artırmasına yardımcı olur.

#### 6. Gelecek İçin Öneriler ve Geliştirmeler

*   **Kullanıcı Geri Bildirim Sistemi:** Kullanıcıların yemekleri 1-5 arası puanlayabileceği veya yorum yapabileceği bir sistem eklenebilir.
*   **Alerjen Filtreleme:** Kullanıcıların, takvim üzerinde belirli alerjenleri (gluten, laktoz vb.) içeren günleri filtreleyebilmesi sağlanabilir.
*   **RESTful API Geliştirmesi:** Projenin verilerini dışarıya açan bir API yazılarak, gelecekte geliştirilebilecek bir mobil uygulama için altyapı hazırlanabilir.
*   **Test Otomasyonu:** Projenin PHP tarafındaki fonksiyonları için PHPUnit gibi araçlarla birim testleri yazılarak, gelecekteki değişikliklerin mevcut sistemi bozmamasının önüne geçilebilir.
