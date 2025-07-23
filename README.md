# 🍽️ Akdeniz Üniversitesi Yemekhane Menü Sistemi

Modern, kullanıcı dostu ve responsive bir yemekhane menü yönetim sistemi. Öğrenciler ve personel için aylık yemek menülerini görüntüleme, yöneticiler için menü yönetimi ve geri bildirim sistemi.

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)

## 📋 İçindekiler

- [Özellikler](#-özellikler)
- [Teknolojiler](#-teknolojiler)
- [Kurulum](#-kurulum)
- [Kullanım](#-kullanım)
- [API Dokümantasyonu](#-api-dokümantasyonu)
- [Veritabanı Yapısı](#-veritabanı-yapısı)
- [Güvenlik](#-güvenlik)
- [Katkıda Bulunma](#-katkıda-bulunma)
- [Lisans](#-lisans)

## ✨ Özellikler

### 👥 Kullanıcı Özellikleri
- **Modern Takvim Arayüzü**: Responsive ve kullanıcı dostu tasarım
- **Dinamik Ay Navigasyonu**: Aylar arası kolay geçiş
- **Detaylı Yemek Bilgisi**: Kalori, içerik ve alerjen bilgileri
- **Özel Gün Gösterimi**: Tatil ve özel günlerin belirtilmesi
- **Geri Bildirim Sistemi**: Yemekler hakkında değerlendirme ve yorum
- **Mobil Uyumlu**: Tüm cihazlarda mükemmel görünüm

### 🔧 Yönetici Özellikleri
- **Güvenli Admin Paneli**: JWT tabanlı kimlik doğrulama
- **Menü Yönetimi**: Günlük menü ekleme, düzenleme, silme
- **Toplu Veri Yükleme**: CSV dosyası ile menü içe aktarma
- **Geri Bildirim Yönetimi**: Kullanıcı yorumlarını görüntüleme ve cevaplama
- **Raporlama**: Detaylı istatistikler ve analizler
- **Log Sistemi**: Tüm admin işlemlerinin kaydı
- **Yemek Ücret Yönetimi**: Fiyat listesi güncelleme

### 📱 Mobil API
- **RESTful API**: Mobil uygulamalar için tam API desteği
- **JWT Authentication**: Güvenli token tabanlı kimlik doğrulama
- **Kapsamlı Endpoint'ler**: Tüm işlevler için API desteği

## 🛠️ Teknolojiler

- **Backend**: PHP 7.4+ / 8.x
- **Veritabanı**: MySQL 5.7+ / MariaDB 10.3+
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Bağımlılıklar**: 
  - Firebase JWT (PHP-JWT)
  - PHPMailer (E-posta gönderimi)
  - Chart.js (Grafikler)
- **Güvenlik**: 
  - Cloudflare Turnstile (CAPTCHA)
  - CSRF Protection
  - SQL Injection koruması
  - XSS koruması

## 🚀 Kurulum

### Gereksinimler
- PHP 7.4 veya üzeri
- MySQL 5.7 veya MariaDB 10.3+
- Composer
- Web sunucusu (Apache/Nginx)

### Adım 1: Projeyi İndirin
```bash
git clone https://github.com/Noktanyus/yemekhane-listesi.git
cd yemekhane-listesi
```

### Adım 2: Bağımlılıkları Yükleyin
```bash
composer install
```

### Adım 3: Veritabanını Kurun
```bash
# MySQL/MariaDB'ye bağlanın
mysql -u root -p

# Veritabanını oluşturun
CREATE DATABASE yemekhane_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# SQL dosyasını içe aktarın
mysql -u root -p yemekhane_db < setup/database.sql
```

### Adım 4: Yapılandırma
```bash
# Yapılandırma dosyasını oluşturun
cp config.example.php config.php
```

`config.php` dosyasını düzenleyin:
```php
<?php
// Veritabanı ayarları
define('DB_HOST', 'localhost');
define('DB_NAME', 'yemekhane_db');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');

// JWT ayarları
define('JWT_SECRET_KEY', 'your-secret-key-here');
define('JWT_EXPIRATION_TIME', 3600); // 1 saat

// E-posta ayarları (SMTP)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'your-email@gmail.com');
define('SMTP_FROM_NAME', 'Yemekhane Sistemi');

// Cloudflare Turnstile
define('CLOUDFLARE_SECRET_KEY', 'your-turnstile-secret-key');
?>
```

### Adım 5: Dizin İzinleri
```bash
chmod 755 uploads/
chmod 755 uploads/feedback/
chmod 755 uploads/temp_csv/
```

### Adım 6: Admin Hesabı Oluşturun
```bash
php reset_admin_password.php
```

## 📖 Kullanım

### Kullanıcı Arayüzü
1. Ana sayfayı ziyaret edin: `http://yourdomain.com/`
2. Takvim üzerinde istediğiniz güne tıklayın
3. Yemek detaylarını görüntüleyin
4. Geri bildirim bırakın (isteğe bağlı)

### Admin Paneli
1. Admin paneline giriş yapın: `http://yourdomain.com/admin/`
2. Kullanıcı adı ve şifrenizi girin
3. Dashboard'dan sistem durumunu görüntüleyin
4. Menü yönetimi için ilgili bölümleri kullanın

### CSV ile Toplu Menü Yükleme
1. `ornek_menu_noktalivirgul.csv` dosyasını referans alın
2. Menü verilerinizi hazırlayın
3. Admin panelinden "CSV Yükle" bölümünü kullanın
4. Dosyayı seçin ve önizlemeyi kontrol edin
5. Verileri onaylayın

## 📚 API Dokümantasyonu

Detaylı API dokümantasyonu için [API_DOCUMENTATION.md](API_DOCUMENTATION.md) dosyasını inceleyiniz.

### Temel Endpoint'ler

#### Kimlik Doğrulama
```http
POST /api/mobile_auth.php
Content-Type: application/json

{
    "username": "admin",
    "password": "password"
}
```

#### Menü Verilerini Getir
```http
GET /api/mobile_gateway.php?endpoint=get_menu_events&year=2024&month=3
```

#### Geri Bildirim Gönder
```http
POST /api/submit_feedback.php
Content-Type: multipart/form-data

name=Ahmet&email=ahmet@akdeniz.edu.tr&rating=5&comment=Harika!
```

## 🗄️ Veritabanı Yapısı

### Ana Tablolar
- **meals**: Yemek bilgileri (ad, kalori, içerik, alerjen bilgisi)
- **menus**: Günlük menü atamaları
- **special_days**: Özel günler ve tatiller
- **feedback**: Kullanıcı geri bildirimleri
- **admins**: Yönetici hesapları
- **logs**: Sistem aktivite kayıtları
- **meal_prices**: Yemek ücret listesi
- **site_settings**: Site ayarları

### İlişkiler
```sql
menus.meal_id → meals.id
feedback.replied_by → admins.id
```

## 🔒 Güvenlik

### Uygulanan Güvenlik Önlemleri
- **SQL Injection**: Prepared Statements kullanımı
- **XSS**: HTML çıktılarında filtreleme
- **CSRF**: Token tabanlı koruma
- **JWT**: Güvenli API kimlik doğrulama
- **File Upload**: Dosya türü ve boyut kontrolü
- **Rate Limiting**: Aşırı istek koruması
- **HTTPS**: SSL/TLS şifreleme (önerilir)

### Güvenlik Önerileri
1. Güçlü şifreler kullanın
2. JWT secret key'i güvenli tutun
3. HTTPS kullanın
4. Düzenli güvenlik güncellemeleri yapın
5. Log dosyalarını izleyin

## 🤝 Katkıda Bulunma

1. Bu repository'yi fork edin
2. Feature branch oluşturun (`git checkout -b feature/amazing-feature`)
3. Değişikliklerinizi commit edin (`git commit -m 'Add amazing feature'`)
4. Branch'inizi push edin (`git push origin feature/amazing-feature`)
5. Pull Request oluşturun

### Geliştirme Ortamı
```bash
# PHP CS Fixer ile kod standardını kontrol edin
./php-cs-fixer.phar fix --dry-run --diff

# Kod standardını uygulayın
./php-cs-fixer.phar fix
```

## 📝 Changelog

### v2.0.0 (2024-03-XX)
- ✨ Mobil API desteği eklendi
- ✨ JWT tabanlı kimlik doğrulama
- ✨ Responsive tasarım iyileştirmeleri
- 🐛 Güvenlik açıkları giderildi
- 📚 Kapsamlı API dokümantasyonu

### v1.0.0 (2024-02-XX)
- 🎉 İlk sürüm yayınlandı
- ✨ Temel menü yönetimi
- ✨ Geri bildirim sistemi
- ✨ CSV içe aktarma

## 📄 Lisans

Bu proje MIT lisansı altında lisanslanmıştır. Detaylar için [LICENSE](LICENSE) dosyasını inceleyiniz.

## 👨‍💻 Geliştirici

**Noktanyus**
- GitHub: [@Noktanyus](https://github.com/Noktanyus)
- Email: [yunustughan0@gmail.com](mailto:yunustughan0@gmail.com)

## 🙏 Teşekkürler

- Akdeniz Üniversitesi Bilgi İşlem Daire Başkanlığı
- Tüm katkıda bulunan geliştiriciler
- Geri bildirim sağlayan kullanıcılar

---

⭐ Bu projeyi beğendiyseniz yıldız vermeyi unutmayın!

## 📞 Destek

Herhangi bir sorun yaşarsanız:
1. [Issues](https://github.com/Noktanyus/yemekhane-listesi/issues) bölümünde arama yapın
2. Yeni bir issue oluşturun
3. Detaylı açıklama ve hata mesajları ekleyin

## 🔄 Güncellemeler

Bu projeyi güncel tutmak için:
```bash
git pull origin main
composer update
```

Veritabanı güncellemeleri için `setup/migrations/` klasörünü kontrol edin.