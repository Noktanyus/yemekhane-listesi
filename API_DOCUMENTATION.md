# Yemekhane Mobil API Dokümantasyonu

Bu dokümantasyon, yemekhane uygulamasının mobil geliştiriciler için hazırlanmış API referansıdır.

## Genel Bilgiler

- **Base URL**: `https://yemekmenu.akdeniz.edu.tr/api/`
- **Content-Type**: `application/json`
- **Karakter Kodlaması**: UTF-8
- **Mobil Gateway**: Tüm mobil istekler `mobile_gateway.php` üzerinden yönlendirilir

## Kimlik Doğrulama

### JWT Token Alma
Admin işlemleri için JWT token gereklidir.

**Endpoint**: `mobile_auth.php`  
**Method**: `POST`  
**Content-Type**: `application/json`

**İstek Parametreleri**:
```json
{
    "username": "admin_kullanici_adi",
    "password": "admin_sifresi"
}
```

**Başarılı Yanıt** (200):
```json
{
    "success": true,
    "message": "Giriş başarılı.",
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "expires_in": 3600
}
```

**Hata Yanıtları**:
- **401**: Geçersiz kullanıcı adı veya şifre
- **400**: Eksik parametreler
- **500**: Sunucu hatası

### Token Kullanımı
Admin gerektiren endpoint'ler için header'da token gönderilmelidir:
```
X-Authorization: Bearer YOUR_JWT_TOKEN
```

---

## HESAP GEREKTİRMEYEN API'LER

### 1. Menü Olaylarını Getir
Belirli bir ay veya günün menü bilgilerini getirir.

**Endpoint**: `mobile_gateway.php?endpoint=get_menu_events`  
**Method**: `GET`  
**Admin Gerekli**: Hayır

**Parametreler**:
- `year` (int): Yıl (aylık veri için)
- `month` (int): Ay (aylık veri için)
- `date` (string): Tek gün için tarih (YYYY-MM-DD formatında)

**Aylık Veri İsteği**:
```
GET mobile_gateway.php?endpoint=get_menu_events&year=2024&month=3
```

**Başarılı Yanıt** (200):
```json
{
    "menus": {
        "2024-03-01": {
            "meals": [
                {"name": "Mercimek Çorbası"},
                {"name": "Tavuk Sote"}
            ],
            "total_calories": 650
        }
    },
    "special_days": {
        "2024-03-15": "Çanakkale Zaferi - Yemekhane Kapalı"
    }
}
```

**Tek Gün İsteği**:
```
GET mobile_gateway.php?endpoint=get_menu_events&date=2024-03-01
```

**Başarılı Yanıt** (200):
```json
{
    "is_special": false,
    "message": "",
    "menu": [
        {
            "id": 1,
            "name": "Mercimek Çorbası",
            "calories": 120,
            "ingredients": "Mercimek, soğan, havuç",
            "is_vegetarian": 1,
            "is_gluten_free": 0,
            "has_allergens": 0
        }
    ]
}
```

**Özel Gün Yanıtı**:
```json
{
    "is_special": true,
    "message": "Çanakkale Zaferi - Yemekhane Kapalı",
    "menu": []
}
```

**Hata Yanıtları**:
- **400**: Eksik veya geçersiz parametreler
- **500**: Sunucu hatası

### 2. Yemek Ücretlerini Getir
Aktif yemek ücret listesini getirir.

**Endpoint**: `mobile_gateway.php?endpoint=get_meal_prices`  
**Method**: `GET`  
**Admin Gerekli**: Hayır

**İstek**:
```
GET mobile_gateway.php?endpoint=get_meal_prices
```

**Başarılı Yanıt** (200):
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "group_name": "Öğrenci",
            "description": "Öğrenci yemek ücreti",
            "price": "15.50",
            "is_active": 1,
            "sort_order": 1
        },
        {
            "id": 2,
            "group_name": "Personel",
            "description": "Personel yemek ücreti",
            "price": "25.00",
            "is_active": 1,
            "sort_order": 2
        }
    ]
}
```

**Hata Yanıtları**:
- **500**: Sunucu hatası

### 3. Site Bilgilerini Getir
Yemekhane yetkilileri ve iletişim bilgilerini getirir.

**Endpoint**: `mobile_gateway.php?endpoint=get_site_info`  
**Method**: `GET`  
**Admin Gerekli**: Hayır

**İstek**:
```
GET mobile_gateway.php?endpoint=get_site_info
```

**Başarılı Yanıt** (200):
```json
{
    "success": true,
    "data": {
        "S.K.S Daire Başkanı": "Prof. Dr. Ahmet Yılmaz",
        "Yemekhane Müdür Yrd.": "Dr. Mehmet Demir",
        "Diyetisyen": "Dyt. Ayşe Kaya",
        "Yemekhane E-posta": "yemekhane@akdeniz.edu.tr"
    }
}
```

**Hata Yanıtları**:
- **500**: Sunucu hatası

### 4. Geri Bildirim Gönder
Kullanıcıların geri bildirim göndermesini sağlar.

**Endpoint**: `submit_feedback.php`  
**Method**: `POST`  
**Admin Gerekli**: Hayır  
**Content-Type**: `multipart/form-data`

**İstek Parametreleri**:
- `name` (string, zorunlu): Kullanıcı adı
- `email` (string, zorunlu): E-posta (@akdeniz.edu.tr veya @ogr.akdeniz.edu.tr)
- `rating` (int, zorunlu): Puan (1-5 arası)
- `comment` (string, zorunlu): Yorum metni
- `image` (file, opsiyonel): Resim dosyası (max 10MB, JPG/PNG/GIF/WEBP)
- `cf-turnstile-response` (string, zorunlu): Cloudflare Turnstile CAPTCHA yanıtı

**İstek Örneği**:
```javascript
const formData = new FormData();
formData.append('name', 'Ahmet Yılmaz');
formData.append('email', 'ahmet@ogr.akdeniz.edu.tr');
formData.append('rating', '4');
formData.append('comment', 'Yemekler çok lezzetliydi!');
formData.append('cf-turnstile-response', 'CAPTCHA_RESPONSE');
// formData.append('image', imageFile); // Opsiyonel

fetch('submit_feedback.php', {
    method: 'POST',
    body: formData
});
```

**Başarılı Yanıt** (200):
```json
{
    "success": true,
    "message": "Geri bildiriminiz başarıyla gönderildi."
}
```

**Hata Yanıtları**:
- **400**: Eksik parametreler, geçersiz e-posta domain'i, geçersiz dosya türü
- **500**: Sunucu hatası

### 5. Resim Görüntüle
Geri bildirim resimlerini görüntüler.

**Endpoint**: `mobile_gateway.php?endpoint=view_image`  
**Method**: `GET`  
**Admin Gerekli**: Evet (Geri bildirim resimleri sadece adminler tarafından görülebilir)

**Parametreler**:
- `file` (string, zorunlu): Resim dosya adı
- `download` (opsiyonel): İndirme için

**İstek**:
```
GET mobile_gateway.php?endpoint=view_image&file=image_filename.jpg
```

**Başarılı Yanıt**: Resim dosyası (binary)

**Hata Yanıtları**:
- **400**: Geçersiz dosya adı
- **403**: Yetkisiz erişim
- **404**: Dosya bulunamadı
- **415**: Desteklenmeyen dosya türü

---

## ADMİN HESAP GEREKTİREN API'LER

Aşağıdaki API'ler JWT token ile kimlik doğrulama gerektirir.

### 1. Geri Bildirimleri Getir
Geri bildirimları filtreli olarak getirir.

**Endpoint**: `mobile_gateway.php?endpoint=get_feedback`  
**Method**: `GET`  
**Admin Gerekli**: Evet

**Parametreler**:
- `status` (string): Durum filtresi ('all', 'yeni', 'okundu', 'cevaplandı', 'arsivlendi')
- `search` (string): Arama terimi
- `start_date` (string): Başlangıç tarihi (YYYY-MM-DD)
- `end_date` (string): Bitiş tarihi (YYYY-MM-DD)
- `page` (int): Sayfa numarası (varsayılan: 1)
- `limit` (int): Sayfa başına kayıt (varsayılan: 25)

**İstek**:
```
GET mobile_gateway.php?endpoint=get_feedback&status=yeni&page=1&limit=10
```

**Başarılı Yanıt** (200):
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Ahmet Yılmaz",
            "email": "ahmet@ogr.akdeniz.edu.tr",
            "rating": 4,
            "comment": "Yemekler güzeldi",
            "image_path": "image123.jpg",
            "is_read": 0,
            "is_archived": 0,
            "created_at": "2024-03-01 12:30:00",
            "reply_message": null,
            "replied_at": null,
            "replied_by_username": null,
            "status": "yeni",
            "created_at_formatted": "01.03.2024 12:30"
        }
    ],
    "pagination": {
        "current_page": 1,
        "total_pages": 5,
        "total_results": 125,
        "limit": 25
    }
}
```

**Hata Yanıtları**:
- **403**: Yetkisiz erişim
- **500**: Sunucu hatası

### 2. Geri Bildirim Durumu Güncelle
Geri bildirim durumunu günceller.

**Endpoint**: `mobile_gateway.php?endpoint=mark_feedback`  
**Method**: `POST`  
**Admin Gerekli**: Evet

**İstek Parametreleri**:
- `id` (int, zorunlu): Geri bildirim ID'si
- `action` (string, zorunlu): Eylem ('mark_read', 'archive', 'unarchive')

**İstek Örneği**:
```json
{
    "id": 1,
    "action": "mark_read"
}
```

**Başarılı Yanıt** (200):
```json
{
    "success": true,
    "message": "Geri bildirim durumu başarıyla güncellendi.",
    "feedback": {
        "id": 1,
        "status": "okundu",
        "is_read": 1,
        "is_archived": 0
    }
}
```

**Hata Yanıtları**:
- **400**: Eksik parametreler, geçersiz eylem
- **403**: Yetkisiz erişim
- **404**: Geri bildirim bulunamadı
- **500**: Sunucu hatası

### 3. Geri Bildirime Cevap Ver
Geri bildirime e-posta ile cevap gönderir.

**Endpoint**: `mobile_gateway.php?endpoint=reply_feedback`  
**Method**: `POST`  
**Admin Gerekli**: Evet

**İstek Parametreleri**:
- `id` (int, zorunlu): Geri bildirim ID'si
- `reply_text` (string, zorunlu): Cevap metni
- `email` (string, zorunlu): Kullanıcı e-postası

**İstek Örneği**:
```json
{
    "id": 1,
    "reply_text": "Geri bildiriminiz için teşekkürler. Konuyu değerlendireceğiz.",
    "email": "ahmet@ogr.akdeniz.edu.tr"
}
```

**Başarılı Yanıt** (200):
```json
{
    "success": true,
    "message": "Cevap başarıyla gönderildi ve kaydedildi."
}
```

**Hata Yanıtları**:
- **400**: Eksik parametreler
- **403**: Yetkisiz erişim
- **500**: E-posta gönderme hatası

### 4. Haftalık Genel Bakış
Belirtilen tarihin bulunduğu haftanın menü özetini getirir.

**Endpoint**: `mobile_gateway.php?endpoint=get_week_overview`  
**Method**: `GET`  
**Admin Gerekli**: Evet

**Parametreler**:
- `date` (string): Referans tarih (YYYY-MM-DD, varsayılan: bugün)

**İstek**:
```
GET mobile_gateway.php?endpoint=get_week_overview&date=2024-03-15
```

**Başarılı Yanıt** (200):
```json
{
    "start_of_week_formatted": "11 Mart",
    "end_of_week_formatted": "17 Mart 2024",
    "days": [
        {
            "date_sql": "2024-03-11",
            "date_formatted": "11 Mart, Pazartesi",
            "summary": "Mercimek Çorbası, Tavuk Sote, Pilav",
            "is_special": false
        },
        {
            "date_sql": "2024-03-15",
            "date_formatted": "15 Mart, Cuma",
            "summary": "Çanakkale Zaferi - Yemekhane Kapalı",
            "is_special": true
        }
    ]
}
```

**Hata Yanıtları**:
- **403**: Yetkisiz erişim
- **500**: Sunucu hatası

### 5. Rapor Verilerini Getir
Dashboard için istatistik verilerini getirir.

**Endpoint**: `mobile_gateway.php?endpoint=get_report_data`  
**Method**: `GET`  
**Admin Gerekli**: Evet

**İstek**:
```
GET mobile_gateway.php?endpoint=get_report_data
```

**Başarılı Yanıt** (200):
```json
{
    "success": true,
    "data": {
        "general_stats": {
            "total_feedback": 150,
            "average_rating": 4.2,
            "new_feedback_count": 12
        },
        "top_meals_chart": {
            "labels": ["Mercimek Çorbası", "Tavuk Sote", "Pilav"],
            "datasets": [{
                "label": "Servis Sayısı",
                "data": [25, 20, 18],
                "backgroundColor": "#007bff"
            }]
        },
        "ratings_chart": {
            "labels": ["1 Yıldız", "2 Yıldız", "3 Yıldız", "4 Yıldız", "5 Yıldız"],
            "datasets": [{
                "label": "Puan Sayısı",
                "data": [5, 10, 25, 60, 50],
                "backgroundColor": ["#dc3545", "#ffc107", "#fd7e14", "#28a745", "#007bff"]
            }]
        },
        "complaint_words": {
            "soğuk": 15,
            "tuzlu": 12,
            "lezzetsiz": 8
        }
    }
}
```

**Hata Yanıtları**:
- **403**: Yetkisiz erişim
- **500**: Sunucu hatası

### 6. Sistem Loglarını Getir
Sistem aktivite loglarını getirir.

**Endpoint**: `mobile_gateway.php?endpoint=get_logs`  
**Method**: `GET`  
**Admin Gerekli**: Evet

**Parametreler**:
- `limit` (int): Kayıt sayısı limiti (varsayılan: 50)

**İstek**:
```
GET mobile_gateway.php?endpoint=get_logs&limit=100
```

**Başarılı Yanıt** (200):
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "admin_username": "admin",
            "action_type": "menu_update",
            "details": "2024-03-15 tarihi için menü güncellendi",
            "ip_address": "192.168.1.1",
            "action_time_formatted": "15.03.2024 14:30:25"
        }
    ]
}
```

**Hata Yanıtları**:
- **403**: Yetkisiz erişim
- **500**: Sunucu hatası

### 7. Yemek Yönetimi
Yemek ekleme, güncelleme, silme ve listeleme işlemleri.

**Endpoint**: `mobile_gateway.php?endpoint=manage_meal`  
**Method**: `GET` (listeleme), `POST` (ekleme/güncelleme/silme)  
**Admin Gerekli**: Evet

#### Tüm Yemekleri Listele
**İstek**:
```
GET mobile_gateway.php?endpoint=manage_meal&action=get_all
```

**Başarılı Yanıt** (200):
```json
[
    {
        "id": 1,
        "name": "Mercimek Çorbası",
        "calories": 120,
        "is_vegetarian": 1,
        "is_gluten_free": 0,
        "has_allergens": 0
    }
]
```

#### Tek Yemek Getir
**İstek**:
```
GET mobile_gateway.php?endpoint=manage_meal&action=get_single&id=1
```

#### Yemek Ekle/Güncelle
**İstek Parametreleri**:
- `action` (string): 'create' veya 'update'
- `name` (string, zorunlu): Yemek adı
- `calories` (int): Kalori değeri
- `ingredients` (string): İçerikler
- `is_vegetarian` (checkbox): Vejetaryen mi
- `is_gluten_free` (checkbox): Glutensiz mi
- `has_allergens` (checkbox): Alerjen içeriyor mu
- `meal_id` (int): Güncelleme için yemek ID'si

**Başarılı Yanıt** (200):
```json
{
    "success": true,
    "message": "Yemek başarıyla kaydedildi."
}
```

#### Yemek Sil
**İstek Parametreleri**:
- `action` (string): 'delete'
- `id` (int, zorunlu): Silinecek yemek ID'si

**Hata Yanıtları**:
- **400**: Eksik parametreler, geçersiz eylem
- **403**: Yetkisiz erişim
- **500**: Sunucu hatası

### 8. Tarih/Menü Yönetimi
Belirli bir tarihin menüsünü veya özel gün mesajını yönetir.

**Endpoint**: `mobile_gateway.php?endpoint=manage_date`  
**Method**: `POST`  
**Admin Gerekli**: Evet

**İstek Parametreleri**:
- `menu_date` (string, zorunlu): Tarih (YYYY-MM-DD)
- `is_special_day` (checkbox): Özel gün mü
- `special_day_message` (string): Özel gün mesajı
- `meal_names[]` (array): Yemek adları listesi

**Normal Menü İsteği**:
```json
{
    "menu_date": "2024-03-15",
    "meal_names": ["Mercimek Çorbası", "Tavuk Sote", "Pilav"]
}
```

**Özel Gün İsteği**:
```json
{
    "menu_date": "2024-03-15",
    "is_special_day": true,
    "special_day_message": "Çanakkale Zaferi - Yemekhane Kapalı"
}
```

**Başarılı Yanıt** (200):
```json
{
    "success": true,
    "message": "Tarih başarıyla kaydedildi."
}
```

**Hata Yanıtları**:
- **400**: Eksik parametreler
- **403**: Yetkisiz erişim
- **500**: Sunucu hatası

### 9. Menü Kopyalama
Bir tarihin menüsünü başka bir tarihe kopyalar.

**Endpoint**: `mobile_gateway.php?endpoint=copy_menu`  
**Method**: `POST`  
**Admin Gerekli**: Evet

**İstek Parametreleri**:
- `source_date` (string, zorunlu): Kaynak tarih (YYYY-MM-DD)
- `target_date` (string, zorunlu): Hedef tarih (YYYY-MM-DD)

**İstek Örneği**:
```json
{
    "source_date": "2024-03-15",
    "target_date": "2024-03-22"
}
```

**Başarılı Yanıt** (200):
```json
{
    "success": true,
    "message": "Menü başarıyla kopyalandı!"
}
```

**Hata Yanıtları**:
- **400**: Eksik parametreler, aynı tarihler
- **403**: Yetkisiz erişim
- **404**: Kaynak tarihte menü bulunamadı
- **500**: Sunucu hatası

### 10. Yetkili Bilgileri Yönetimi
Site yetkili bilgilerini günceller.

**Endpoint**: `mobile_gateway.php?endpoint=manage_officials`  
**Method**: `POST`  
**Admin Gerekli**: Evet

**İstek Parametreleri**:
- `sks_daire_baskani` (string): S.K.S Daire Başkanı
- `yemekhane_mudur_yrd` (string): Yemekhane Müdür Yrd.
- `diyetisyen` (string): Diyetisyen
- `yemekhane_email` (string): Yemekhane E-posta

**İstek Örneği**:
```json
{
    "sks_daire_baskani": "Prof. Dr. Ahmet Yılmaz",
    "yemekhane_mudur_yrd": "Dr. Mehmet Demir",
    "diyetisyen": "Dyt. Ayşe Kaya",
    "yemekhane_email": "yemekhane@akdeniz.edu.tr"
}
```

**Başarılı Yanıt** (200):
```json
{
    "success": true,
    "message": "Yetkili bilgileri başarıyla güncellendi."
}
```

**Hata Yanıtları**:
- **403**: Yetkisiz erişim
- **500**: Sunucu hatası

---

## Hata Kodları ve Mesajları

### HTTP Durum Kodları
- **200**: Başarılı
- **400**: Hatalı istek (eksik/geçersiz parametreler)
- **401**: Kimlik doğrulama hatası
- **403**: Yetkisiz erişim
- **404**: Kaynak bulunamadı
- **405**: Geçersiz HTTP metodu
- **415**: Desteklenmeyen medya türü
- **500**: Sunucu hatası

### Genel Hata Yanıt Formatı
```json
{
    "success": false,
    "message": "Hata açıklaması",
    "error_details": "Detaylı hata bilgisi (sadece debug modunda)"
}
```

## Güvenlik Notları

1. **JWT Token**: Admin işlemleri için gerekli, header'da gönderilmeli
2. **HTTPS**: Tüm istekler HTTPS üzerinden yapılmalı
3. **Rate Limiting**: Aşırı istek gönderimini önlemek için rate limiting uygulanabilir
4. **CAPTCHA**: Geri bildirim gönderimi için Cloudflare Turnstile gerekli
5. **Dosya Yükleme**: Sadece belirtilen dosya türleri ve boyut limitleri kabul edilir
6. **E-posta Domaini**: Sadece @akdeniz.edu.tr ve @ogr.akdeniz.edu.tr kabul edilir

## Örnek Kullanım Senaryoları

### Mobil Uygulama Giriş Akışı
1. Kullanıcı admin bilgilerini girer
2. `mobile_auth.php` ile token alınır
3. Token, sonraki isteklerde header'da gönderilir
4. Token süresi dolduğunda yeniden giriş yapılır

### Menü Görüntüleme Akışı
1. `get_menu_events` ile aylık menü verisi alınır
2. Kullanıcı belirli bir güne tıkladığında tek gün detayı alınır
3. Özel günler farklı UI ile gösterilir

### Geri Bildirim Yönetimi Akışı
1. `get_feedback` ile geri bildirimler listelenir
2. `mark_feedback` ile okundu/arşivlendi işaretlenir
3. `reply_feedback` ile kullanıcıya cevap gönderilir

Bu dokümantasyon, mobil uygulama geliştiricilerinin API'yi etkili bir şekilde kullanabilmesi için gerekli tüm bilgileri içermektedir.