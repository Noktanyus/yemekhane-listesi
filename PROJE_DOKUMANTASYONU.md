### Akdeniz Üniversitesi Yemekhane Menü Sistemi - Proje Dokümantasyonu

#### 1. Projeye Genel Bakış

Bu proje, Akdeniz Üniversitesi öğrencileri ve personeli için aylık yemek menüsünü sunan dijital bir takvim sistemidir. Projenin temel amacı, geleneksel listeleme yöntemleri yerine, kullanıcıların yemek menüsünü kolayca görüntüleyebileceği modern, interaktif ve estetik bir arayüz sağlamaktır.

**Yapılan Temel İyileştirmeler:**
Proje, statik ve eski görünümlü bir yapıdan, referans alınan modern takvim uygulamalarına benzer, dinamik ve kullanıcı dostu bir tasarıma kavuşturulmuştur. Bu süreçte kullanıcı deneyimi (UX) ve arayüz (UI) tamamen yenilenmiştir.

#### 2. Kullanılan Teknolojiler

*   **Backend:** PHP 7.x / 8.x
*   **Veritabanı:** MySQL / MariaDB
*   **Frontend:**
    *   HTML5
    *   CSS3 (Modern Grid/Flexbox yapıları ile)
    *   JavaScript (Vanilla JS - Herhangi bir kütüphaneye bağımlılık olmadan)
*   **Veri Formatı:** CSV (Toplu yemek listesi yüklemesi için)

#### 3. Temel Özellikler

##### 3.1. Kullanıcı Arayüzü Özellikleri

*   **Modern Takvim Arayüzü:** Uygulama benzeri, temiz ve minimalist bir tasarıma sahiptir.
*   **Dinamik Ay Navigasyonu:** Kullanıcılar aylar arasında kolayca geçiş yapabilir ve tek tuşla mevcut güne dönebilir.
*   **Dinamik Sütun Yapısı:** Hafta içi günlerine daha fazla alan ayrılarak, hafta sonu sütunları daha dar tutulmuş ve böylece dolu günlerin okunurluğu artırılmıştır.
*   **İçerik Belirteçleri:** Her yemek, sol tarafında bulunan ince, renkli bir kenarlık ile belirtilerek hem estetik bir görünüm sunar hem de okunurluğu artırır.
*   **Özel Gün Gösterimi:** Resmi tatiller veya özel notlar, takvim üzerinde açık sarı bir arka planla vurgulanır.
*   **Detaylı Yemek Bilgisi (Modal):** Kullanıcılar, menüsü olan bir güne tıkladığında açılan pencerede yemeklerin kalori ve içerik bilgilerini (eğer girilmişse) görebilir.
*   **Yazdırılabilir Versiyon:** Tek tuşla, o an görüntülenen ayın menüsünü A4 formatında, sade ve mürekkep dostu bir tasarımla yazdırma imkanı sunar.

##### 3.2. Yönetim Paneli Özellikleri

*   **Güvenli Yönetici Girişi:** Yönetim paneline sadece yetkili kullanıcıların erişebilmesi için bir giriş sistemi mevcuttur.
*   **Yemek Yönetimi:** Belirli bir tarihe yemek ekleme, mevcut yemeği düzenleme ve silme işlemleri yapılabilir.
*   **Özel Gün Yönetimi:** Belirli tarihleri "Resmi Tatil" gibi özel notlarla işaretleme imkanı vardır.
*   **Toplu Veri Yükleme (CSV):** Yönetim panelinin en güçlü özelliklerinden biridir. `ornek_menu_noktalivirgul.csv` formatına uygun olarak hazırlanan dosyalar sayesinde, bir ayın veya daha uzun bir sürenin tüm yemek menüsü tek bir işlemle sisteme yüklenebilir. Bu, manuel veri girişini ortadan kaldırarak büyük bir zaman tasarrufu sağlar.

#### 4. Güvenlik Önlemleri ve Öneriler

##### 4.1. Mevcut Güvenlik Önlemleri

*   **SQL Injection Koruması:** Veritabanı sorgularında **Hazırlanmış İfadeler (Prepared Statements)** kullanılarak, SQL enjeksiyonu gibi yaygın ve tehlikeli saldırıların önüne geçilmiştir.
*   **Cross-Site Scripting (XSS) Koruması:** Kullanıcı tarafından girilen veriler (yemek isimleri, içerikler vb.) ekrana yazdırılırken `htmlspecialchars()` gibi fonksiyonlarla filtrelenerek, XSS saldırıları engellenmektedir.
*   **Veritabanı Erişim Güvenliği:** Veritabanı bağlantı bilgileri, ana kod dosyalarından ayrı bir `config.php` dosyasında tutularak yetkisiz erişime karşı korunmaktadır.

##### 4.2. Olası Saldırılar ve Engelleme Yöntemleri (Öneriler)

*   **CSRF (Cross-Site Request Forgery):**
    *   **Risk:** Yönetici oturumu açıkken, kötü niyetli bir web sitesi üzerinden yöneticinin isteği dışında işlem (örn: yemek silme) yaptırılabilir.
    *   **Çözüm:** Yönetim panelindeki tüm formlara (yemek ekleme, silme vb.) CSRF token'ları eklenmelidir. Sunucu, her işlem öncesi bu token'ı doğrulamalıdır.
*   **Dosya Yükleme Zafiyetleri (CSV Upload):**
    *   **Risk:** Sadece `.csv` dosyası beklenirken, zararlı bir PHP veya başka bir betik dosyası yüklenebilir.
    *   **Çözüm:** Yüklenen dosyanın sadece uzantısı değil, MIME tipi de sunucu tarafında doğrulanmalıdır. Dosya boyutu sınırlandırılmalı ve yüklenen dosyalar, web sunucusunun erişemeyeceği bir dizinde saklanmalıdır.
*   **Brute Force Saldırıları (Admin Girişi):**
    *   **Risk:** Saldırganlar, admin paneli girişini binlerce parola deneyerek kırmaya çalışabilir.
    *   **Çözüm:** Belirli sayıda (örn: 5) başarısız giriş denemesinden sonra kullanıcının hesabı geçici olarak kilitlenmeli veya bir CAPTCHA doğrulaması istenmelidir.

#### 5. İstatistik ve Raporlama (Öneri)

Proje, gelecekte bir istatistik modülü eklenerek daha da güçlendirilebilir. Bu modül ile:
*   En çok görüntülenen günler veya aylar takip edilebilir.
*   Yemeklerin ortalama kalori değerleri üzerine raporlar oluşturulabilir.
*   Kullanıc��ların (eğer bir geri bildirim sistemi eklenirse) en çok beğendiği yemekler listelenebilir.
Bu veriler, yemekhane yönetiminin gelecekteki menüleri planlamasına yardımcı olabilir.

#### 6. Gelecek İçin Öneriler ve Geliştirmeler

*   **Kullanıcı Geri Bildirim Sistemi:** Kullanıcıların yemekleri 1-5 arası puanlayabileceği veya yorum yapabileceği bir sistem eklenebilir.
*   **Alerjen Filtreleme:** Kullanıcıların, takvim üzerinde belirli alerjenleri (gluten, laktoz vb.) içeren günleri filtreleyebilmesi sağlanabilir.
*   **RESTful API Geliştirmesi:** Projenin verilerini dışarıya açan bir API yazılarak, gelecekte geliştirilebilecek bir mobil uygulama için altyapı hazırlanabilir.
*   **Test Otomasyonu:** Projenin PHP tarafındaki fonksiyonları için PHPUnit gibi araçlarla birim testleri yazılarak, gelecekteki değişikliklerin mevcut sistemi bozmamasının önüne geçilebilir.
