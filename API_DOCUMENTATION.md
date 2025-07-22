# Akdeniz Üniversitesi Yemekhane API Dokümantasyonu

Bu dokümantasyon, Akdeniz Üniversitesi Yemekhane Menü Sistemi'nin tüm API endpoint'lerini detaylı olarak açıklar.

## Genel Bilgiler

- **Base URL**: `http://localhost/api/`
- **Content-Type**: `application/json` (response), `application/x-www-form-urlencoded` veya `multipart/form-data` (request)
- **Authentication**: Session-based authentication (admin endpoints için)
- **CSRF Protection**: Admin endpoints'lerde CSRF token gerekli
- **Encoding**: UTF-8
- **Timezone**: Europe/Istanbul

## Güvenlik

### Admin Endpoints
Admin paneli API'leri için:
- Session'da `admin_logged_in = true` olmalı
- POST isteklerinde `csrf_token` parametresi gerekli
- IP adresi ve işlem logları tutulur

### Public Endpoints
Herkese açık API'ler için özel güvenlik gereksinimi yok.

### Mobil API Erişimi
Mobil uygulamalar için:
- JWT token tabanlı authentication
- `mobile_gateway.php` üzerinden erişim
- `X-Authorization: Bearer {token}` header'ı gerekli
- Tüm admin API'leri mobil destekli
- CSRF token gereksinimleri JWT ile bypass edilir

---

## � MOBİL öUYGULAMA GELİŞTİRİCİLERİ İÇİN HIZLI BAŞLANGIÇ

### 🚀 Mobil API Kullanımı - Adım Adım

#### 1. Kimlik Doğrulama
```bash
# İlk olarak JWT token alın
curl -X POST "http://localhost/api/mobile_auth.php" \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password123"}'

# Response:
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "expires_in": 3600
}
```

#### 2. Token ile API Çağrısı
```bash
# Token'ı her istekte X-Authorization header'ında gönderin
curl -X GET "http://localhost/api/mobile_gateway.php?endpoint=get_menu_events&date=2024-11-25" \
  -H "X-Authorization: Bearer YOUR_JWT_TOKEN"
```

#### 3. Mobil API Endpoint'leri
Tüm API'ler `mobile_gateway.php` üzerinden erişilebilir:

| Endpoint | Açıklama | Admin Gerekli |
|----------|----------|---------------|
| `get_menu_events` | Menü ve etkinlikleri getir | ❌ |
| `get_meal_prices` | Yemek ücretlerini getir | ❌ |
| `get_site_info` | Site bilgilerini getir | ❌ |
| `submit_feedback` | Geri bildirim gönder | ❌ |
| `get_week_overview` | Haftalık menü görünümü | ✅ |
| `manage_date` | Tarih yönetimi | ✅ |
| `manage_meal` | Yemek yönetimi | ✅ |
| `get_feedback` | Geri bildirimleri getir | ✅ |
| `mark_feedback` | Geri bildirim işaretle | ✅ |
| `reply_feedback` | Geri bildirime cevap ver | ✅ |
| `get_report_data` | Rapor verilerini getir | ✅ |
| `get_logs` | İşlem kayıtlarını getir | ✅ |
| `manage_officials` | Yetkili yönetimi | ✅ |
| `copy_menu` | Menü kopyala | ✅ |
| `view_image` | Görsel görüntüle | ✅ |

#### 4. Mobil API Özellikleri
- ✅ **CSRF Token Gereksiz**: JWT token yeterli
- ✅ **Session Gereksiz**: Stateless authentication
- ✅ **JSON Response**: Tüm yanıtlar JSON formatında
- ✅ **Error Handling**: Standart hata kodları
- ✅ **Rate Limiting**: Otomatik hız sınırlaması

#### 5. Örnek Mobil Uygulama Akışı
```javascript
// 1. Login
const loginResponse = await fetch('/api/mobile_auth.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ username: 'admin', password: 'pass' })
});
const { token } = await loginResponse.json();

// 2. API çağrısı
const menuResponse = await fetch('/api/mobile_gateway.php?endpoint=get_menu_events&date=2024-11-25', {
  headers: { 'X-Authorization': `Bearer ${token}` }
});
const menuData = await menuResponse.json();
```

---

## 📋 Menü Yönetimi API'leri

### 1. Haftalık Menü Görünümü
**Endpoint**: `GET /api/get_week_overview.php`  
**Method**: GET  
**Admin Gerekli**: ✅  
**Açıklama**: Belirtilen tarihin bulunduğu haftanın tüm günlerinin menü özetini getirir.

**Parametreler**:
- `date` (string, optional): YYYY-MM-DD formatında tarih. Belirtilmezse bugünün tarihi kullanılır.

**Web Kullanım Örneği**:
```bash
curl -X GET "http://localhost/api/get_week_overview.php?date=2024-11-25" \
  -H "Cookie: PHPSESSID=your_session_id"
```

**📱 Mobil Kullanım Örneği**:
```bash
curl -X GET "http://localhost/api/mobile_gateway.php?endpoint=get_week_overview&date=2024-11-25" \
  -H "X-Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
```

**Başarılı Response**:
```json
{
  "start_of_week_formatted": "25 Kasım",
  "end_of_week_formatted": "1 Aralık 2024",
  "days": [
    {
      "date_sql": "2024-11-25",
      "date_formatted": "25 Kasım, Pazartesi",
      "summary": "Mercimek Çorbası, Izgara Köfte, Pilav",
      "is_special": false
    },
    {
      "date_sql": "2024-11-29",
      "date_formatted": "29 Kasım, Cuma",
      "summary": "Cumhuriyet Bayramı - Yemekhane Kapalı",
      "is_special": true
    }
  ]
}
```

**Hata Response Örnekleri**:
```json
{
  "success": false,
  "message": "Yetkisiz erişim."
}
```

```json
{
  "success": false,
  "message": "Haftalık veri alınırken bir sunucu hatası oluştu."
}
```

**Dikkat Edilmesi Gerekenler**:
- Admin oturumu gereklidir
- Hafta Pazartesi günü başlar
- Özel günler `is_special: true` ile işaretlenir

---

### 2. Menü ve Etkinlikleri Getir
**Endpoint**: `GET /api/get_menu_events.php`  
**Method**: GET  
**Admin Gerekli**: ❌ (Public)  
**Açıklama**: Aylık takvim görünümü için menü verilerini veya tek bir günün detaylarını getirir.

**Parametreler (Aylık Veri)**:
- `year` (int): Yıl (örn: 2024)
- `month` (int): Ay (1-12)

**Parametreler (Günlük Detay)**:
- `date` (string): YYYY-MM-DD formatında tarih

**Web Kullanım Örnekleri**:
```bash
# Aylık veri
curl -X GET "http://localhost/api/get_menu_events.php?year=2024&month=11"

# Günlük detay
curl -X GET "http://localhost/api/get_menu_events.php?date=2024-11-25"
```

**📱 Mobil Kullanım Örnekleri**:
```bash
# Aylık veri
curl -X GET "http://localhost/api/mobile_gateway.php?endpoint=get_menu_events&year=2024&month=11"

# Günlük detay
curl -X GET "http://localhost/api/mobile_gateway.php?endpoint=get_menu_events&date=2024-11-25"
```

**Aylık Veri Response**:
```json
{
  "menus": {
    "2024-11-25": {
      "meals": [
        {"name": "Mercimek Çorbası"},
        {"name": "Izgara Köfte"}
      ],
      "total_calories": 450
    }
  },
  "special_days": {
    "2024-11-29": "Cumhuriyet Bayramı - Yemekhane Kapalı"
  }
}
```

**Günlük Detay Response**:
```json
{
  "is_special": false,
  "message": "",
  "menu": [
    {
      "id": 1,
      "name": "Mercimek Çorbası",
      "calories": 150,
      "ingredients": "Mercimek, soğan, havuç",
      "is_vegetarian": 1,
      "is_gluten_free": 1,
      "has_allergens": 0
    }
  ]
}
```

**Hata Response Örnekleri**:
```json
{
  "success": false,
  "message": "Eksik veya geçersiz parametreler. Yıl/ay veya tek bir tarih gereklidir."
}
```

```json
{
  "success": false,
  "message": "Aylık menü verileri alınırken sunucu hatası oluştu."
}
```

```json
{
  "success": false,
  "message": "Günlük menü detayı alınırken sunucu hatası oluştu."
}
```

**Dikkat Edilmesi Gerekenler**:
- Parametreler eksikse 400 Bad Request döner
- Özel günlerde `menu` array'i boş olur
- Kalori bilgileri null olabilir

---

### 3. Tarih Yönetimi
**Endpoint**: `POST /api/manage_date.php`  
**Method**: POST  
**Admin Gerekli**: ✅  
**Content-Type**: `application/x-www-form-urlencoded`  
**Açıklama**: Belirli bir tarihe menü ekler veya özel gün olarak işaretler.

**Parametreler**:
- `csrf_token` (string): CSRF güvenlik token'ı
- `menu_date` (string): YYYY-MM-DD formatında tarih
- `is_special_day` (checkbox): Özel gün işaretlemesi
- `meal_names[]` (array): Yemek isimleri (normal gün için)
- `special_day_message` (string): Özel gün mesajı

**Web Kullanım Örneği (Normal Gün)**:
```bash
curl -X POST "http://localhost/api/manage_date.php" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d "csrf_token=abc123&menu_date=2024-11-25&meal_names[]=Mercimek Çorbası&meal_names[]=Izgara Köfte"
```

**📱 Mobil Kullanım Örneği (Normal Gün)**:
```bash
curl -X POST "http://localhost/api/mobile_gateway.php?endpoint=manage_date" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "X-Authorization: Bearer YOUR_JWT_TOKEN" \
  -d "menu_date=2024-11-25&meal_names[]=Mercimek Çorbası&meal_names[]=Izgara Köfte"
```

**Web Kullanım Örneği (Özel Gün)**:
```bash
curl -X POST "http://localhost/api/manage_date.php" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d "csrf_token=abc123&menu_date=2024-11-29&is_special_day=1&special_day_message=Cumhuriyet Bayramı - Yemekhane Kapalı"
```

**📱 Mobil Kullanım Örneği (Özel Gün)**:
```bash
curl -X POST "http://localhost/api/mobile_gateway.php?endpoint=manage_date" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "X-Authorization: Bearer YOUR_JWT_TOKEN" \
  -d "menu_date=2024-11-29&is_special_day=1&special_day_message=Cumhuriyet Bayramı - Yemekhane Kapalı"
```

**Başarılı Response**:
```json
{
  "success": true,
  "message": "Tarih başarıyla kaydedildi."
}
```

**Hata Response Örnekleri**:
```json
{
  "success": false,
  "message": "Tarih alanı zorunludur."
}
```

```json
{
  "success": false,
  "message": "Yetkisiz erişim."
}
```

```json
{
  "success": false,
  "message": "Geçersiz istek metodu."
}
```

```json
{
  "success": false,
  "message": "Özel gün mesajı boş olamaz."
}
```

```json
{
  "success": false,
  "message": "İşlem sırasında bir hata oluştu: Veritabanı bağlantı hatası"
}
```

**Dikkat Edilmesi Gerekenler**:
- CSRF token zorunludur
- Önceki menü kayıtları silinir ve yeniden oluşturulur
- Yemek isimleri `meals` tablosunda mevcut olmalı
- Özel gün mesajı boş olamaz

---

### 4. Menü Kopyalama
**Endpoint**: `POST /api/copy_menu.php`  
**Method**: POST  
**Admin Gerekli**: ✅  
**Content-Type**: `application/x-www-form-urlencoded`  
**Açıklama**: Bir tarihteki menüyü başka bir tarihe kopyalar.

**Parametreler**:
- `csrf_token` (string): CSRF güvenlik token'ı
- `source_date` (string): Kaynak tarih (YYYY-MM-DD)
- `target_date` (string): Hedef tarih (YYYY-MM-DD)

**Web Kullanım Örneği**:
```bash
curl -X POST "http://localhost/api/copy_menu.php" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d "csrf_token=abc123&source_date=2024-11-25&target_date=2024-12-02"
```

**📱 Mobil Kullanım Örneği**:
```bash
curl -X POST "http://localhost/api/mobile_gateway.php?endpoint=copy_menu" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "X-Authorization: Bearer YOUR_JWT_TOKEN" \
  -d "source_date=2024-11-25&target_date=2024-12-02"
```

**Başarılı Response**:
```json
{
  "success": true,
  "message": "Menü başarıyla kopyalandı!"
}
```

**Hata Response Örnekleri**:
```json
{
  "success": false,
  "message": "Geçersiz istek metodu."
}
```

```json
{
  "success": false,
  "message": "Kaynak ve hedef tarihler boş olamaz."
}
```

```json
{
  "success": false,
  "message": "Kaynak ve hedef tarihler aynı olamaz."
}
```

```json
{
  "success": false,
  "message": "Kaynak tarihte kopyalanacak menü bulunamadı."
}
```

```json
{
  "success": false,
  "message": "Veritabanı hatası nedeniyle menü kopyalanamadı."
}
```

```json
{
  "success": false,
  "message": "Yetkisiz erişim. Lütfen tekrar giriş yapın."
}
```

**Dikkat Edilmesi Gerekenler**:
- Kaynak ve hedef tarihler farklı olmalı
- Hedef tarihteki mevcut menü silinir
- Kaynak tarihte menü yoksa işlem başarısız olur
- İşlem transaction içinde yapılır

---

## 🍽️ Yemek Yönetimi API'leri

### 1. Yemek Yönetimi
**Endpoint**: `GET/POST /api/manage_meal.php`  
**Method**: GET (listeleme) / POST (ekleme/güncelleme/silme)  
**Admin Gerekli**: ✅  
**Açıklama**: Yemek veritabanı yönetimi için CRUD işlemleri.

#### A. Tüm Yemekleri Getir
**Method**: GET  
**Parametreler**:
- `action=get_all`

**Web Kullanım Örneği**:
```bash
curl -X GET "http://localhost/api/manage_meal.php?action=get_all" \
  -H "Cookie: PHPSESSID=your_session_id"
```

**📱 Mobil Kullanım Örneği**:
```bash
curl -X GET "http://localhost/api/mobile_gateway.php?endpoint=manage_meal&action=get_all" \
  -H "X-Authorization: Bearer YOUR_JWT_TOKEN"
```

**Response**:
```json
[
  {
    "id": 1,
    "name": "Mercimek Çorbası",
    "calories": 150,
    "is_vegetarian": 1,
    "is_gluten_free": 1,
    "has_allergens": 0
  },
  {
    "id": 2,
    "name": "Izgara Köfte",
    "calories": 300,
    "is_vegetarian": 0,
    "is_gluten_free": 0,
    "has_allergens": 1
  }
]
```

#### B. Tek Yemek Detayı Getir
**Method**: GET  
**Parametreler**:
- `action=get_single`
- `id` (int): Yemek ID'si

**Kullanım Örneği**:
```bash
curl -X GET "http://localhost/api/manage_meal.php?action=get_single&id=1" \
  -H "Cookie: PHPSESSID=your_session_id"
```

**Response**:
```json
{
  "id": 1,
  "name": "Mercimek Çorbası",
  "calories": 150,
  "ingredients": "Mercimek, soğan, havuç, tereyağı",
  "is_vegetarian": 1,
  "is_gluten_free": 1,
  "has_allergens": 0
}
```

#### C. Yeni Yemek Ekle
**Method**: POST  
**Content-Type**: `application/x-www-form-urlencoded`

**Parametreler**:
- `action=create`
- `csrf_token` (string): CSRF token
- `name` (string): Yemek adı (zorunlu)
- `calories` (int, optional): Kalori değeri
- `ingredients` (string, optional): Malzemeler
- `is_vegetarian` (checkbox): Vejetaryen mi?
- `is_gluten_free` (checkbox): Glütensiz mi?
- `has_allergens` (checkbox): Alerjen içeriyor mu?

**Kullanım Örneği**:
```bash
curl -X POST "http://localhost/api/manage_meal.php" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d "action=create&csrf_token=abc123&name=Domates Çorbası&calories=120&ingredients=Domates, soğan, fesleğen&is_vegetarian=1&is_gluten_free=1"
```

**Başarılı Response**:
```json
{
  "success": true,
  "message": "Yemek başarıyla kaydedildi."
}
```

#### D. Yemek Güncelle
**Method**: POST  
**Content-Type**: `application/x-www-form-urlencoded`

**Parametreler**:
- `action=update`
- `csrf_token` (string): CSRF token
- `meal_id` (int): Güncellenecek yemek ID'si
- `name` (string): Yemek adı (zorunlu)
- `calories` (int, optional): Kalori değeri
- `ingredients` (string, optional): Malzemeler
- `is_vegetarian` (checkbox): Vejetaryen mi?
- `is_gluten_free` (checkbox): Glütensiz mi?
- `has_allergens` (checkbox): Alerjen içeriyor mu?

**Kullanım Örneği**:
```bash
curl -X POST "http://localhost/api/manage_meal.php" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d "action=update&csrf_token=abc123&meal_id=1&name=Mercimek Çorbası (Güncellenmiş)&calories=160"
```

**Başarılı Response**:
```json
{
  "success": true,
  "message": "Yemek başarıyla kaydedildi."
}
```

#### E. Yemek Sil
**Method**: POST  
**Content-Type**: `application/x-www-form-urlencoded`

**Parametreler**:
- `action=delete`
- `csrf_token` (string): CSRF token
- `id` (int): Silinecek yemek ID'si

**Kullanım Örneği**:
```bash
curl -X POST "http://localhost/api/manage_meal.php" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d "action=delete&csrf_token=abc123&id=1"
```

**Başarılı Response**:
```json
{
  "success": true,
  "message": "Yemek başarıyla silindi."
}
```

**Hata Response Örnekleri**:
```json
{
  "success": false,
  "message": "Yetkisiz erişim."
}
```

```json
{
  "success": false,
  "message": "Geçersiz GET eylemi."
}
```

```json
{
  "success": false,
  "message": "Yemek ID'si bulunamadı."
}
```

```json
{
  "success": false,
  "message": "Yemek adı boş olamaz."
}
```

```json
{
  "success": false,
  "message": "Bu isimde bir yemek zaten mevcut."
}
```

```json
{
  "success": false,
  "message": "Güncellenecek yemek ID'si bulunamadı."
}
```

```json
{
  "success": false,
  "message": "Silinecek yemek ID'si bulunamadı."
}
```

```json
{
  "success": false,
  "message": "Geçersiz POST eylemi."
}
```

```json
{
  "success": false,
  "message": "Geçersiz istek metodu."
}
```

**Dikkat Edilmesi Gerekenler**:
- Yemek adı benzersiz olmalı
- Checkbox alanları gönderilmezse 0 (false) kabul edilir
- Kalori değeri boşsa null olarak kaydedilir
- Silme işlemi geri alınamaz
- Menülerde kullanılan yemekler silinebilir (foreign key constraint yok)

---

## 💰 Yemek Ücretleri API'leri

### 1. Yemek Ücretlerini Getir (Public)
**Endpoint**: `GET /api/get_meal_prices.php`  
**Method**: GET  
**Admin Gerekli**: ❌ (Public)  
**Açıklama**: Ana sayfadaki yemek ücretleri popup'ı için aktif ücret listesini getirir.

**Parametreler**: Yok

**Kullanım Örneği**:
```bash
curl -X GET "http://localhost/api/get_meal_prices.php"
```

**Başarılı Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "group_name": "ÖĞRENCİ YEMEK ÜCRETİ",
      "description": null,
      "price": "40.00",
      "is_active": 1,
      "sort_order": 1
    },
    {
      "id": 2,
      "group_name": "SÖZL. PERSONEL (4/B) 0-600 EK GÖSTERGE",
      "description": "PERSONEL",
      "price": "80.00",
      "is_active": 1,
      "sort_order": 3
    }
  ]
}
```

**Hata Response Örnekleri**:
```json
{
  "success": false,
  "message": "Yemek ücretleri alınırken bir hata oluştu."
}
```

**Dikkat Edilmesi Gerekenler**:
- Sadece aktif ücretler (`is_active = 1`) döner
- Sıralama `sort_order` alanına göre yapılır
- `description` alanı null olabilir

---

### 2. Yemek Ücretleri Yönetimi (Admin)
**Endpoint**: `POST /api/manage_meal_prices.php`  
**Method**: POST  
**Admin Gerekli**: ✅  
**Content-Type**: `application/x-www-form-urlencoded`  
**Açıklama**: Admin panelinde yemek ücretleri CRUD işlemleri.

#### A. Tüm Ücretleri Getir
**Parametreler**:
- `action=get_all`
- `csrf_token` (string): CSRF token

**Kullanım Örneği**:
```bash
curl -X POST "http://localhost/api/manage_meal_prices.php" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d "action=get_all&csrf_token=abc123"
```

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "group_name": "ÖĞRENCİ YEMEK ÜCRETİ",
      "description": null,
      "price": "40.00",
      "is_active": 1,
      "sort_order": 1,
      "created_at": "2024-11-25 10:00:00",
      "updated_at": "2024-11-25 10:00:00"
    }
  ]
}
```

#### B. Yeni Ücret Ekle
**Parametreler**:
- `action=add`
- `csrf_token` (string): CSRF token
- `group_name` (string): Grup adı (zorunlu)
- `description` (string, optional): Açıklama
- `price` (float): Ücret (zorunlu, > 0)
- `is_active` (checkbox): Aktif mi?
- `sort_order` (int): Sıralama numarası

**Kullanım Örneği**:
```bash
curl -X POST "http://localhost/api/manage_meal_prices.php" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d "action=add&csrf_token=abc123&group_name=YENİ GRUP&description=Test&price=50.00&is_active=1&sort_order=10"
```

**Başarılı Response**:
```json
{
  "success": true,
  "message": "Yemek ücreti başarıyla eklendi."
}
```

#### C. Ücret Güncelle
**Parametreler**:
- `action=update`
- `csrf_token` (string): CSRF token
- `price_id` (int): Güncellenecek ücret ID'si
- `group_name` (string): Grup adı (zorunlu)
- `description` (string, optional): Açıklama
- `price` (float): Ücret (zorunlu, > 0)
- `is_active` (checkbox): Aktif mi?
- `sort_order` (int): Sıralama numarası

**Kullanım Örneği**:
```bash
curl -X POST "http://localhost/api/manage_meal_prices.php" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d "action=update&csrf_token=abc123&price_id=1&group_name=GÜNCELLENMIŞ GRUP&price=55.00&is_active=1&sort_order=5"
```

**Başarılı Response**:
```json
{
  "success": true,
  "message": "Yemek ücreti başarıyla güncellendi."
}
```

#### D. Ücret Sil
**Parametreler**:
- `action=delete`
- `csrf_token` (string): CSRF token
- `price_id` (int): Silinecek ücret ID'si

**Kullanım Örneği**:
```bash
curl -X POST "http://localhost/api/manage_meal_prices.php" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d "action=delete&csrf_token=abc123&price_id=1"
```

**Başarılı Response**:
```json
{
  "success": true,
  "message": "Yemek ücreti başarıyla silindi."
}
```

**Hata Response Örnekleri**:
```json
{
  "success": false,
  "message": "Yetkisiz erişim."
}
```

```json
{
  "success": false,
  "message": "Güvenlik hatası."
}
```

```json
{
  "success": false,
  "message": "Grup adı ve geçerli bir ücret girilmelidir."
}
```

```json
{
  "success": false,
  "message": "Geçersiz veri."
}
```

```json
{
  "success": false,
  "message": "Geçersiz ID."
}
```

```json
{
  "success": false,
  "message": "Ücret bulunamadı."
}
```

```json
{
  "success": false,
  "message": "Geçersiz işlem."
}
```

```json
{
  "success": false,
  "message": "Veritabanı hatası oluştu."
}
```

**Dikkat Edilmesi Gerekenler**:
- Ücret değeri 0'dan büyük olmalı
- Checkbox alanları gönderilmezse 0 (false) kabul edilir
- Silme işlemi geri alınamaz
- Tüm işlemler loglanır

---

## 💬 Geri Bildirim API'leri

### 1. Geri Bildirim Gönder
**Endpoint**: `POST /api/submit_feedback.php`  
**Method**: POST  
**Admin Gerekli**: ❌ (Public)  
**Content-Type**: `multipart/form-data`  
**Açıklama**: Kullanıcıların yemekhane hakkında geri bildirim göndermesi için.

**Parametreler**:
- `name` (string): Ad soyad (zorunlu)
- `email` (string): E-posta adresi (zorunlu, @akdeniz.edu.tr veya @ogr.akdeniz.edu.tr)
- `rating` (int): 1-5 arası puan (zorunlu)
- `comment` (string): Yorum (zorunlu)
- `image` (file, optional): Görsel dosyası (max 10MB, JPG/PNG/GIF/WEBP)
- `cf-turnstile-response` (string): Cloudflare Turnstile CAPTCHA token (zorunlu)

**Kullanım Örneği**:
```bash
curl -X POST "http://localhost/api/submit_feedback.php" \
  -F "name=Ahmet Yılmaz" \
  -F "email=ahmet@akdeniz.edu.tr" \
  -F "rating=4" \
  -F "comment=Yemekler çok lezzetli!" \
  -F "image=@photo.jpg" \
  -F "cf-turnstile-response=captcha_token_here"
```

**Başarılı Response**:
```json
{
  "success": true,
  "message": "Geri bildiriminiz başarıyla gönderildi."
}
```

**Hata Response Örnekleri**:
```json
{
  "success": false,
  "message": "Geçersiz istek metodu."
}
```

```json
{
  "success": false,
  "message": "CAPTCHA doğrulaması eksik. Lütfen sayfayı yenileyip tekrar deneyin."
}
```

```json
{
  "success": false,
  "message": "CAPTCHA doğrulaması başarısız. Lütfen tekrar deneyin."
}
```

```json
{
  "success": false,
  "message": "Lütfen tüm zorunlu alanları (ad, e-posta, değerlendirme, yorum) doldurun."
}
```

```json
{
  "success": false,
  "message": "Lütfen geçerli bir e-posta adresi girin."
}
```

```json
{
  "success": false,
  "message": "Sadece @akdeniz.edu.tr ve @ogr.akdeniz.edu.tr uzantılı e-posta adresleri kabul edilmektedir."
}
```

```json
{
  "success": false,
  "message": "Dosya boyutu 10 MB'ı aşamaz."
}
```

```json
{
  "success": false,
  "message": "Geçersiz dosya türü. Sadece JPG, PNG, GIF ve WEBP dosyalarına izin verilir."
}
```

```json
{
  "success": false,
  "message": "Dosya yüklenirken bir sunucu hatası oluştu."
}
```

```json
{
  "success": false,
  "message": "Veritabanı hatası nedeniyle geri bildirim gönderilemedi."
}
```

**Dikkat Edilmesi Gerekenler**:
- CAPTCHA doğrulaması zorunlu
- E-posta domain kontrolü yapılır
- Dosya boyutu ve türü kontrol edilir
- Yüklenen dosyalar güvenli isimlerle kaydedilir

---

### 2. Geri Bildirimleri Getir (Admin)
**Endpoint**: `GET /api/get_feedback.php`  
**Method**: GET  
**Admin Gerekli**: ✅  
**Açıklama**: Admin panelinde geri bildirimleri filtreleyerek listeler.

**Parametreler**:
- `status` (string, optional): 'yeni', 'okundu', 'cevaplandı', 'arsivlendi', 'all' (default: 'all')
- `search` (string, optional): Ad veya yorum içinde arama
- `start_date` (string, optional): Başlangıç tarihi (YYYY-MM-DD)
- `end_date` (string, optional): Bitiş tarihi (YYYY-MM-DD)
- `page` (int, optional): Sayfa numarası (default: 1)
- `limit` (int, optional): Sayfa başına kayıt (default: 25)

**Kullanım Örneği**:
```bash
curl -X GET "http://localhost/api/get_feedback.php?status=yeni&page=1&limit=10&search=ahmet" \
  -H "Cookie: PHPSESSID=your_session_id"
```

**Başarılı Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Ahmet Yılmaz",
      "email": "ahmet@akdeniz.edu.tr",
      "rating": 4,
      "comment": "Yemekler çok lezzetli!",
      "image_path": "unique_filename.jpg",
      "is_read": 0,
      "is_archived": 0,
      "created_at": "2024-11-25 14:30:00",
      "created_at_formatted": "25.11.2024 14:30",
      "reply_message": null,
      "replied_at": null,
      "replied_by_username": null,
      "status": "yeni"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 5,
    "total_results": 123,
    "limit": 25
  }
}
```

**Hata Response Örnekleri**:
```json
{
  "success": false,
  "message": "Yetkisiz erişim."
}
```

```json
{
  "success": false,
  "message": "Geri bildirimler alınırken bir veritabanı hatası oluştu."
}
```

**Durum Açıklamaları**:
- `yeni`: Okunmamış, arşivlenmemiş, cevaplandırılmamış
- `okundu`: Okunmuş, arşivlenmemiş, cevaplandırılmamış
- `cevaplandı`: Cevaplandırılmış, arşivlenmemiş
- `arsivlendi`: Arşivlenmiş

---

### 3. Geri Bildirim İşaretle (Admin)
**Endpoint**: `POST /api/mark_feedback.php`  
**Method**: POST  
**Admin Gerekli**: ✅  
**Content-Type**: `application/x-www-form-urlencoded`  
**Açıklama**: Geri bildirim durumunu değiştirir.

**Parametreler**:
- `csrf_token` (string): CSRF token
- `id` (int): Geri bildirim ID'si
- `action` (string): 'mark_read', 'archive', 'unarchive'

**Kullanım Örneği**:
```bash
curl -X POST "http://localhost/api/mark_feedback.php" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d "csrf_token=abc123&id=1&action=mark_read"
```

**Başarılı Response**:
```json
{
  "success": true,
  "message": "Geri bildirim durumu başarıyla güncellendi.",
  "feedback": {
    "id": 1,
    "name": "Ahmet Yılmaz",
    "is_read": 1,
    "is_archived": 0,
    "status": "okundu",
    "created_at_formatted": "25.11.2024 14:30"
  }
}
```

**Hata Response Örnekleri**:
```json
{
  "success": false,
  "message": "Yetkisiz erişim."
}
```

```json
{
  "success": false,
  "message": "Geri bildirim ID'si veya eylem eksik."
}
```

```json
{
  "success": false,
  "message": "Geçersiz eylem."
}
```

```json
{
  "success": false,
  "message": "Geri bildirim bulunamadı veya durum zaten günceldi."
}
```

```json
{
  "success": false,
  "message": "Durum güncellenirken bir veritabanı hatası oluştu."
}
```

---

### 4. Geri Bildirime Cevap Ver (Admin)
**Endpoint**: `POST /api/reply_feedback.php`  
**Method**: POST  
**Admin Gerekli**: ✅  
**Content-Type**: `application/x-www-form-urlencoded`  
**Açıklama**: Geri bildirime e-posta ile cevap gönderir ve veritabanını günceller.

**Parametreler**:
- `csrf_token` (string): CSRF token
- `id` (int): Geri bildirim ID'si
- `reply_text` (string): Cevap metni
- `email` (string): Kullanıcının e-posta adresi

**Kullanım Örneği**:
```bash
curl -X POST "http://localhost/api/reply_feedback.php" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d "csrf_token=abc123&id=1&reply_text=Geri bildiriminiz için teşekkürler&email=ahmet@akdeniz.edu.tr"
```

**Başarılı Response**:
```json
{
  "success": true,
  "message": "Cevap başarıyla gönderildi ve kaydedildi."
}
```

**Hata Response**:
```json
{
  "success": false,
  "message": "E-posta gönderilirken bir hata oluştu: SMTP connection failed"
}
```

**Dikkat Edilmesi Gerekenler**:
- SMTP ayarları doğru yapılandırılmalı
- Cevap gönderildikten sonra geri bildirim otomatik okundu işaretlenir
- E-posta HTML formatında gönderilir
- İşlem loglanır

---

## 📊 Raporlama API'leri

### 1. Rapor Verilerini Getir
**Endpoint**: `GET /api/get_report_data.php`  
**Method**: GET  
**Admin Gerekli**: ✅  
**Açıklama**: Admin paneli dashboard'u için istatistik verileri sağlar.

**Parametreler**: Yok (sabit raporlar)

**Kullanım Örneği**:
```bash
curl -X GET "http://localhost/api/get_report_data.php" \
  -H "Cookie: PHPSESSID=your_session_id"
```

**Başarılı Response**:
```json
{
  "success": true,
  "data": {
    "general_stats": {
      "total_feedback": 156,
      "average_rating": 4.2,
      "new_feedback_count": 12
    },
    "top_meals_chart": {
      "labels": ["Mercimek Çorbası", "Izgara Köfte", "Pilav"],
      "datasets": [{
        "label": "Servis Sayısı",
        "data": [15, 12, 10],
        "backgroundColor": "#007bff"
      }]
    },
    "ratings_chart": {
      "labels": ["1 Yıldız", "2 Yıldız", "3 Yıldız", "4 Yıldız", "5 Yıldız"],
      "datasets": [{
        "label": "Puan Sayısı",
        "data": [5, 8, 25, 45, 73],
        "backgroundColor": ["#dc3545", "#ffc107", "#fd7e14", "#28a745", "#007bff"]
      }]
    },
    "complaint_words": {
      "soğuk": 15,
      "tuzlu": 12,
      "geç": 8,
      "kalitesiz": 6
    }
  }
}
```

**Rapor İçeriği**:
- **Genel İstatistikler**: Toplam geri bildirim, ortalama puan, yeni geri bildirim sayısı
- **En Popüler Yemekler**: Bu aydaki en çok servis edilen yemekler (Chart.js formatında)
- **Puan Dağılımı**: 1-5 yıldız dağılımı (Chart.js formatında)
- **Şikayet Kelimeleri**: 1-2 yıldızlı yorumlardaki en sık geçen kelimeler

**Dikkat Edilmesi Gerekenler**:
- Veriler gerçek zamanlı hesaplanır
- Şikayet kelimesi analizi Türkçe stopword'leri filtreler
- Chart.js kütüphanesi ile uyumlu format
- Sadece aktif (arşivlenmemiş) geri bildirimler dahil edilir

---

## 📝 Log API'leri

### 1. İşlem Kayıtlarını Getir
**Endpoint**: `GET /api/get_logs.php`  
**Method**: GET  
**Admin Gerekli**: ✅  
**Açıklama**: Sistem üzerinde yapılan işlemlerin kayıtlarını listeler.

**Parametreler**:
- `limit` (int, optional): Kayıt sayısı limiti (default: 50)

**Kullanım Örneği**:
```bash
curl -X GET "http://localhost/api/get_logs.php?limit=100" \
  -H "Cookie: PHPSESSID=your_session_id"
```

**Başarılı Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "admin_username": "admin",
      "action_type": "menu_update",
      "details": "2024-11-25 tarihi için menü güncellendi: Mercimek Çorbası, Izgara Köfte",
      "ip_address": "192.168.1.100",
      "action_time_formatted": "25.11.2024 14:30:15"
    },
    {
      "id": 2,
      "admin_username": "admin",
      "action_type": "feedback_replied",
      "details": "Geri bildirim (ID: 5) cevaplandı.",
      "ip_address": "192.168.1.100",
      "action_time_formatted": "25.11.2024 14:25:30"
    }
  ]
}
```

**Log Türleri**:
- `menu_update`: Menü güncelleme
- `menu_copy`: Menü kopyalama
- `meal_management`: Yemek ekleme/güncelleme/silme
- `feedback_replied`: Geri bildirim cevaplama
- `feedback_status_change`: Geri bildirim durum değişikliği
- `csv_upload`: CSV dosyası yükleme
- `settings_update`: Site ayarları güncelleme

**Dikkat Edilmesi Gerekenler**:
- Loglar en yeniden eskiye doğru sıralanır
- IP adresi otomatik kaydedilir
- Tarih formatı Türkçe yerel ayarlara göre
- Hassas bilgiler loglanmaz

---

## 👥 Yetkili Yönetimi API'leri

### 1. Site Bilgilerini Getir
**Endpoint**: `GET /api/get_site_info.php`  
**Method**: GET  
**Admin Gerekli**: ❌ (Public)  
**Açıklama**: Ana sayfada gösterilecek yetkili bilgilerini getirir.

**Parametreler**: Yok

**Kullanım Örneği**:
```bash
curl -X GET "http://localhost/api/get_site_info.php"
```

**Başarılı Response**:
```json
{
  "success": true,
  "data": {
    "S.K.S Daire Başkanı": "Doç. Dr. Veli ÇELİK",
    "Yemekhane Müdür Yrd.": "Öğr. Gör. Ayşe YILMAZ",
    "Diyetisyen": "Uzm. Dyt. Fatma ÖZTÜRK",
    "Yemekhane E-posta": "yemekhane@akdeniz.edu.tr"
  }
}
```

**Dikkat Edilmesi Gerekenler**:
- Herkese açık API
- Boş değerler boş string olarak döner
- Anahtarlar Türkçe formatında

---

### 2. Yetkilileri Yönet
**Endpoint**: `GET/POST /api/manage_officials.php`  
**Method**: GET (listeleme) / POST (güncelleme)  
**Admin Gerekli**: POST için ✅, GET için ❌  
**Açıklama**: Yetkili bilgilerini getirme ve güncelleme işlemleri.

#### A. Yetkili Bilgilerini Getir
**Method**: GET  
**Parametreler**: Yok

**Kullanım Örneği**:
```bash
curl -X GET "http://localhost/api/manage_officials.php"
```

**Response**:
```json
{
  "success": true,
  "data": {
    "sks_daire_baskani": "Doç. Dr. Veli ÇELİK",
    "yemekhane_mudur_yrd": "Öğr. Gör. Ayşe YILMAZ",
    "diyetisyen": "Uzm. Dyt. Fatma ÖZTÜRK",
    "yemekhane_email": "yemekhane@akdeniz.edu.tr"
  }
}
```

#### B. Yetkili Bilgilerini Güncelle
**Method**: POST  
**Admin Gerekli**: ✅  
**Content-Type**: `application/x-www-form-urlencoded`

**Parametreler**:
- `csrf_token` (string): CSRF güvenlik token'ı
- `sks_daire_baskani` (string, optional): S.K.S Daire Başkanı
- `yemekhane_mudur_yrd` (string, optional): Yemekhane Müdür Yardımcısı
- `diyetisyen` (string, optional): Diyetisyen
- `yemekhane_email` (string, optional): Yemekhane e-posta adresi

**Kullanım Örneği**:
```bash
curl -X POST "http://localhost/api/manage_officials.php" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d "csrf_token=abc123&sks_daire_baskani=Doç. Dr. Yeni İsim&yemekhane_email=yeni@akdeniz.edu.tr"
```

**Başarılı Response**:
```json
{
  "success": true,
  "message": "Yetkili bilgileri başarıyla güncellendi."
}
```

**Hata Response**:
```json
{
  "success": false,
  "message": "Bu işlemi yapmak için yetkiniz yok."
}
```

**Dikkat Edilmesi Gerekenler**:
- Sadece belirtilen alanlar güncellenebilir
- Boş değerler de kaydedilebilir
- İşlem loglanır
- Mobil API desteği mevcut

---

## 📱 Mobil API'ler

### 1. Mobil Authentication
**Endpoint**: `POST /api/mobile_auth.php`  
**Method**: POST  
**Admin Gerekli**: ❌  
**Content-Type**: `application/json`  
**Açıklama**: Mobil uygulamalar için JWT tabanlı kimlik doğrulama.

**Parametreler**:
- `username` (string): Admin kullanıcı adı (zorunlu)
- `password` (string): Admin şifresi (zorunlu)

**Kullanım Örneği**:
```bash
curl -X POST "http://localhost/api/mobile_auth.php" \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password123"}'
```

**Başarılı Response**:
```json
{
  "success": true,
  "message": "Giriş başarılı.",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "expires_in": 3600
}
```

**Hata Response Örnekleri**:
```json
{
  "success": false,
  "message": "Geçersiz kullanıcı adı veya şifre."
}
```

```json
{
  "success": false,
  "message": "Kullanıcı adı ve şifre boş olamaz."
}
```

**Dikkat Edilmesi Gerekenler**:
- JWT token 1 saat geçerli (3600 saniye)
- Token'ı `X-Authorization: Bearer {token}` header'ında kullanın
- Hatalı giriş denemeleri loglanır
- HTTPS kullanımı önerilir

---

### 2. Mobil Gateway
**Endpoint**: `GET/POST /api/mobile_gateway.php`  
**Method**: GET/POST (endpoint'e göre değişir)  
**Admin Gerekli**: Endpoint'e bağlı  
**Açıklama**: Mobil uygulamalar için tüm API'lere tek noktadan erişim sağlar.

**Parametreler**:
- `endpoint` (string): Çağrılacak API endpoint'i (zorunlu)
- Diğer parametreler çağrılan endpoint'e göre değişir

**Desteklenen Endpoint'ler**:
- `get_feedback` - Geri bildirimleri getir
- `get_logs` - İşlem kayıtlarını getir  
- `get_menu_events` - Menü ve etkinlikleri getir
- `get_report_data` - Rapor verilerini getir
- `get_site_info` - Site bilgilerini getir
- `get_week_overview` - Haftalık menü görünümü
- `manage_date` - Tarih yönetimi
- `manage_meal` - Yemek yönetimi
- `manage_officials` - Yetkili yönetimi
- `mark_feedback` - Geri bildirim işaretle
- `reply_feedback` - Geri bildirime cevap ver
- `copy_menu` - Menü kopyala
- `view_image` - Görsel görüntüle

**Kullanım Örneği**:
```bash
# JWT token ile menü getirme
curl -X GET "http://localhost/api/mobile_gateway.php?endpoint=get_menu_events&date=2024-11-25" \
  -H "X-Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."

# JWT token ile yemek ekleme
curl -X POST "http://localhost/api/mobile_gateway.php?endpoint=manage_meal" \
  -H "X-Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "action=create&name=Yeni Yemek&calories=200"
```

**Başarılı Response**:
```json
{
  "success": true,
  "data": "Çağrılan endpoint'in response'u"
}
```

**Hata Response Örnekleri**:
```json
{
  "success": false,
  "message": "Geçersiz veya eksik endpoint."
}
```

```json
{
  "success": false,
  "message": "Token geçersiz veya süresi dolmuş."
}
```

**Dikkat Edilmesi Gerekenler**:
- Güvenlik için beyaz liste kullanılır
- JWT token doğrulaması otomatik yapılır
- Token geçersizse misafir olarak işlem yapılır
- Her endpoint kendi yetki kontrolünü yapar
- `upload_csv` gibi session bağımlı endpoint'ler desteklenmez

---

## 📤 Dosya Yükleme API'leri

### 1. CSV Menü Yükleme
**Endpoint**: `POST /api/upload_csv.php`  
**Method**: POST  
**Admin Gerekli**: ✅  
**Content-Type**: `multipart/form-data`  
**Açıklama**: Toplu menü verilerini CSV dosyasından içe aktarır. İki aşamalı işlem: önce analiz, sonra kaydetme.

#### A. CSV Dosyası Analizi
**Parametreler**:
- `action=analyze`
- `csrf_token` (string): CSRF güvenlik token'ı
- `csv_file` (file): CSV dosyası (max 5MB, .csv uzantılı)

**CSV Formatı**:
```
Tarih;Yemek 1;Yemek 2;Yemek 3;Yemek 4;Yemek 5;Yemek 6;Özel Gün
25.11.2024;Mercimek Çorbası;Izgara Köfte;Pilav;Salata;Ayran;Tatlı;0
29.11.2024;Cumhuriyet Bayramı - Yemekhane Kapalı;;;;;;;1
```

**Kullanım Örneği**:
```bash
curl -X POST "http://localhost/api/upload_csv.php" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -F "action=analyze" \
  -F "csrf_token=abc123" \
  -F "csv_file=@menu_data.csv"
```

**Başarılı Response**:
```json
{
  "success": true,
  "data": [
    {
      "date": "25.11.2024",
      "is_special": false,
      "meals": [
        {"name": "Mercimek Çorbası", "is_new": false},
        {"name": "Izgara Köfte", "is_new": true},
        {"name": "Pilav", "is_new": false}
      ]
    },
    {
      "date": "29.11.2024",
      "is_special": true,
      "meals": [
        {"name": "Cumhuriyet Bayramı - Yemekhane Kapalı", "is_new": false}
      ]
    }
  ]
}
```

#### B. Verileri Kaydetme
**Parametreler**:
- `action=commit`
- `csrf_token` (string): CSRF güvenlik token'ı

**Kullanım Örneği**:
```bash
curl -X POST "http://localhost/api/upload_csv.php" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d "action=commit&csrf_token=abc123"
```

**Başarılı Response**:
```json
{
  "success": true,
  "message": "15 günlük menü başarıyla veritabanına kaydedildi."
}
```

**Hata Response Örnekleri**:
```json
{
  "success": false,
  "message": "Dosya boyutu çok büyük. Lütfen 5MB'dan küçük bir dosya yükleyin."
}
```

```json
{
  "success": false,
  "message": "3. satırda geçersiz tarih formatı: '25/11/2024'. (GG.AA.YYYY olmalı)"
}
```

**Dikkat Edilmesi Gerekenler**:
- CSV dosyası noktalı virgül (;) ile ayrılmalı
- Tarih formatı: GG.AA.YYYY (örn: 25.11.2024)
- UTF-8 BOM otomatik temizlenir
- Özel gün: son sütun 1, normal gün: 0
- Mevcut menüler silinir ve yeniden oluşturulur
- Yeni yemekler otomatik `meals` tablosuna eklenir
- İşlem transaction içinde yapılır
- Geçici dosyalar otomatik temizlenir

---

### 2. Görsel Görüntüleme
**Endpoint**: `GET /api/view_image.php`  
**Method**: GET  
**Admin Gerekli**: ✅ (Web için), Mobil API için JWT token  
**Açıklama**: Geri bildirim görsellerini güvenli şekilde görüntüler.

**Parametreler**:
- `file` (string): Dosya adı (zorunlu)
- `download` (flag, optional): İndirme modunda aç

**Kullanım Örneği**:
```bash
# Görseli tarayıcıda görüntüle
curl -X GET "http://localhost/api/view_image.php?file=unique_filename.jpg" \
  -H "Cookie: PHPSESSID=your_session_id"

# Görseli indir
curl -X GET "http://localhost/api/view_image.php?file=unique_filename.jpg&download=1" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -o "downloaded_image.jpg"
```

**Başarılı Response**:
- Content-Type: image/jpeg, image/png, image/gif, image/webp
- Binary image data

**Hata Response Örnekleri**:
```
HTTP 400 Bad Request: Invalid filename.
HTTP 403 Forbidden: Access denied.
HTTP 404 Not Found: Image does not exist.
HTTP 415 Unsupported Media Type: File is not an image.
```

**Güvenlik Özellikleri**:
- Dosya adında dizin değiştirme (`../`, `/`, `\`) engellenir
- Sadece `uploads/feedback/` klasöründeki dosyalara erişim
- MIME type kontrolü (sadece resim dosyaları)
- Admin oturumu veya geçerli JWT token gerekli
- Mobil API desteği mevcut

**Dikkat Edilmesi Gerekenler**:
- Dosya adı güvenlik kontrolünden geçer
- Sadece resim dosyaları görüntülenebilir
- İndirme modu için `download` parametresi ekleyin
- Mobil API'de `X-Authorization` header'ı kullanın

---

## 🔒 Güvenlik ve Kimlik Doğrulama

### Session Tabanlı Kimlik Doğrulama (Web)
Web arayüzü için session tabanlı kimlik doğrulama kullanılır:

```php
// Session kontrolü
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']));
}
```

**Gereksinimler**:
- `PHPSESSID` cookie'si
- `$_SESSION['admin_logged_in'] = true`
- `$_SESSION['admin_username']` değeri

### JWT Tabanlı Kimlik Doğrulama (Mobil)
Mobil uygulamalar için JWT token kullanılır:

```bash
# Token alma
curl -X POST "/api/mobile_auth.php" \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}'

# Token kullanma
curl -X GET "/api/mobile_gateway.php?endpoint=get_menu_events" \
  -H "X-Authorization: Bearer YOUR_JWT_TOKEN"
```

**Token Özellikleri**:
- Geçerlilik süresi: 1 saat (3600 saniye)
- Algoritma: HS256
- Header formatı: `X-Authorization: Bearer {token}`

### CSRF Koruması
POST istekleri için CSRF token zorunludur:

```html
<input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
```

**Kontrol**:
```php
verify_csrf_token_and_exit(); // Geçersizse işlemi sonlandırır
```

### Rate Limiting
API istekleri için hız sınırlaması:
- Genel API'ler: 100 istek/dakika
- Geri bildirim gönderme: 5 istek/dakika
- Dosya yükleme: 10 istek/dakika

---

## 📊 HTTP Durum Kodları

| HTTP Kodu | Açıklama | Kullanım Alanı |
|-----------|----------|----------------|
| 200 | OK | Başarılı işlemler |
| 201 | Created | Yeni kayıt oluşturma |
| 400 | Bad Request | Geçersiz parametreler |
| 401 | Unauthorized | Kimlik doğrulama hatası |
| 403 | Forbidden | Yetkisiz erişim |
| 404 | Not Found | Kaynak bulunamadı |
| 405 | Method Not Allowed | Geçersiz HTTP metodu |
| 413 | Payload Too Large | Dosya boyutu aşımı |
| 415 | Unsupported Media Type | Geçersiz dosya türü |
| 422 | Unprocessable Entity | Validasyon hatası |
| 429 | Too Many Requests | Rate limit aşımı |
| 500 | Internal Server Error | Sunucu hatası |

## 📝 Standart Response Formatları

### Başarılı Response
```json
{
  "success": true,
  "message": "İşlem başarılı.",
  "data": {
    // Veri içeriği
  }
}
```

### Hata Response
```json
{
  "success": false,
  "message": "Hata açıklaması",
  "error_code": "VALIDATION_ERROR",
  "details": {
    "field": "Geçersiz değer"
  }
}
```

### Sayfalama Response
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "current_page": 1,
    "total_pages": 10,
    "total_results": 250,
    "limit": 25,
    "has_next": true,
    "has_prev": false
  }
}
```

---

## 🧪 Test Örnekleri

### Postman Collection
API'leri test etmek için Postman collection örneği:

```json
{
  "info": {
    "name": "Yemekhane API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Auth",
      "item": [
        {
          "name": "Mobile Login",
          "request": {
            "method": "POST",
            "header": [
              {
                "key": "Content-Type",
                "value": "application/json"
              }
            ],
            "body": {
              "mode": "raw",
              "raw": "{\"username\":\"admin\",\"password\":\"password\"}"
            },
            "url": {
              "raw": "{{base_url}}/api/mobile_auth.php",
              "host": ["{{base_url}}"],
              "path": ["api", "mobile_auth.php"]
            }
          }
        }
      ]
    }
  ],
  "variable": [
    {
      "key": "base_url",
      "value": "http://localhost"
    }
  ]
}
```

### cURL Test Komutları
```bash
# Menü getirme testi
curl -X GET "http://localhost/api/get_menu_events.php?date=2024-11-25" \
  -H "Accept: application/json"

# Admin giriş testi
curl -X POST "http://localhost/api/mobile_auth.php" \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"test123"}' \
  -v

# Geri bildirim gönderme testi
curl -X POST "http://localhost/api/submit_feedback.php" \
  -F "name=Test User" \
  -F "email=test@akdeniz.edu.tr" \
  -F "rating=5" \
  -F "comment=Test feedback" \
  -F "cf-turnstile-response=test_token"
```

---

## 🔧 Geliştirici Notları

### Veritabanı Bağlantısı
```php
// PDO bağlantısı otomatik olarak $pdo değişkeninde hazır
// Tüm API dosyalarında kullanılabilir
$stmt = $pdo->prepare("SELECT * FROM table WHERE id = ?");
$stmt->execute([$id]);
```

### Log Kaydetme
```php
// İşlem logları otomatik kaydedilir
log_action('action_type', $admin_username, 'Detay açıklama');
```

### Hata Yönetimi
```php
try {
    // API işlemleri
} catch (Exception $e) {
    http_response_code(500);
    error_log("API Error: " . $e->getMessage());
    die(json_encode(['success' => false, 'message' => $e->getMessage()]));
}
```

### Dosya Yükleme Güvenliği
```php
// Dosya türü kontrolü
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime_type = $finfo->file($file['tmp_name']);

// Dosya boyutu kontrolü
if ($file['size'] > $max_size) {
    throw new Exception('Dosya çok büyük');
}

// Güvenli dosya adı
$safe_filename = uniqid('prefix_', true) . '.' . $extension;
```

---

## 📈 Performans ve Optimizasyon

### Veritabanı Optimizasyonu
- Tüm sorgular prepared statement kullanır
- İndeksler kritik alanlarda tanımlı
- Transaction kullanımı veri tutarlılığı için
- Connection pooling aktif

### Önbellekleme
```php
// Menü verileri için basit önbellekleme
$cache_key = "menu_" . $date;
if ($cached_data = get_cache($cache_key)) {
    return $cached_data;
}
```

### Dosya Yönetimi
- Yüklenen dosyalar güvenli dizinlerde saklanır
- Geçici dosyalar otomatik temizlenir
- Dosya boyutu ve türü kontrolleri aktif

---

## 🚀 Deployment Notları

### Gereksinimler
- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+
- Composer
- JWT kütüphanesi (firebase/php-jwt)
- GD/ImageMagick (görsel işleme için)

### Kurulum
```bash
# Composer bağımlılıklarını yükle
composer install

# Veritabanı yapılandırması
cp config.example.php config.php
# config.php dosyasını düzenle

# Dizin izinleri
chmod 755 uploads/
chmod 755 uploads/feedback/
chmod 755 uploads/temp_csv/
```

### Güvenlik Ayarları
```apache
# .htaccess - uploads klasörü için
<Files "*.php">
    Order Deny,Allow
    Deny from all
</Files>
```

### SSL Sertifikası
Canlı ortamda HTTPS kullanımı zorunludur:
```nginx
server {
    listen 443 ssl;
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
}
```

---

## 📋 Changelog

### v3.0 (Aralık 2024)
- ✅ **YENİ**: Kapsamlı API dokümantasyonu
- ✅ **YENİ**: Mobil API gateway sistemi
- ✅ **YENİ**: JWT tabanlı kimlik doğrulama
- ✅ **YENİ**: CSV toplu menü yükleme
- ✅ **YENİ**: Görsel görüntüleme API'si
- ✅ **YENİ**: Detaylı güvenlik kontrolleri
- ✅ **YENİ**: Rate limiting desteği
- ✅ **İyileştirme**: Hata yönetimi standardizasyonu
- ✅ **İyileştirme**: Response formatları birleştirildi
- ✅ **İyileştirme**: Test örnekleri eklendi

### v2.1 (Kasım 2024)
- ✅ **YENİ**: Yemek Ücretleri API'leri eklendi
- ✅ **YENİ**: Ana sayfada yemek ücretleri popup'ı
- ✅ **YENİ**: Admin panelinde yemek ücretleri yönetimi
- ✅ **İyileştirme**: Modal sistemleri güncellendi
- ✅ **İyileştirme**: CSS stilleri optimize edildi

### v2.0 (Ekim 2024)
- ✅ **YENİ**: Temel API yapısı oluşturuldu
- ✅ **YENİ**: Admin paneli API'leri
- ✅ **YENİ**: Geri bildirim sistemi
- ✅ **YENİ**: Menü yönetimi CRUD işlemleri
- ✅ **YENİ**: Raporlama API'leri
- ✅ **YENİ**: Log sistemi

### v1.0 (Eylül 2024)
- ✅ **YENİ**: İlk sürüm
- ✅ **YENİ**: Temel menü görüntüleme
- ✅ **YENİ**: Admin paneli
- ✅ **YENİ**: Veritabanı yapısı

---

## 🤝 Katkıda Bulunma

### API Geliştirme Kuralları
1. Tüm API'ler JSON response döner
2. HTTP durum kodları doğru kullanılır
3. Hata mesajları kullanıcı dostu olur
4. Güvenlik kontrolleri atlanmaz
5. İşlemler loglanır

### Test Gereksinimleri
- Unit testler yazılmalı
- API endpoint'leri test edilmeli
- Güvenlik testleri yapılmalı
- Performance testleri çalıştırılmalı

### Dokümantasyon
- Yeni API'ler dokümante edilmeli
- Örnek kullanımlar eklenmeli
- Hata durumları açıklanmalı
- Changelog güncellenmelidir

*Bu dokümantasyon sürekli güncellenmektedir.*  
*Son güncellenme: 22 Temmuz 2025*  
*Versiyon: 3.0*