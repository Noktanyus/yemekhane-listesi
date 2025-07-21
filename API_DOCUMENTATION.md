# Mobil Uygulama API Dökümantasyonu (v5.0 - Koda Dayalı Sürüm)

## 1. Genel Bilgiler

Bu doküman, Akdeniz Üniversitesi Yemekhane mobil uygulamasının backend ile iletişim kurması için gereken tüm API endpoint'lerini, parametreleri, kimlik doğrulama akışını ve hata yönetimini **doğrudan kaynak kod analiziyle** detaylı bir şekilde açıklamaktadır.

### 1.1. API Ana Adresi (Base URL)

Tüm API istekleri aşağıdaki ana adres üzerinden yapılmalıdır. Örneklerde `http://localhost` kullanılmıştır, bunu canlı sunucu adresiyle değiştirin.

`http://localhost`

### 1.2. API Modeli: Halka Açık ve Ağ Geçidi (Gateway)

API iki temel modelde çalışır:

1.  **Halka Açık Endpoint'ler:** Kimlik doğrulama gerektirmeyen, herkesin erişebileceği basit endpoint'lerdir.
    -   Örnek: `POST /api/submit_feedback.php`
2.  **Ağ Geçidi (Gateway) Modeli:** Kimlik doğrulama gerektiren **tüm** işlemler, tek bir API ağ geçidi dosyası olan `mobile_gateway.php` üzerinden yönlendirilir. Bu, güvenliği ve yönetimi merkezileştirir.
    -   **Gateway Adresi:** `/api/mobile_gateway.php`
    -   Hangi işlemin yapılacağı, `?endpoint=` query parametresi ile belirtilir.
    -   **Örnek:** `GET /api/mobile_gateway.php?endpoint=get_logs`

### 1.3. Kimlik Doğrulama: JWT ve `X-Authorization` Başlığı

API, **JWT (JSON Web Token)** tabanlı bir kimlik doğrulama sistemi kullanır.

-   **Token Alma:** `POST /api/mobile_auth.php` endpoint'i kullanılarak alınır.
-   **Token Gönderme:** Alınan token, yetki gerektiren tüm isteklerde **`X-Authorization`** HTTP başlığı ile gönderilmelidir.

**ÖNEMLİ: Neden `X-Authorization`?**
Bazı sunucu yapılandırmaları (özellikle MAMP gibi yerel geliştirme ortamları), standart `Authorization` başlığını güvenlik nedeniyle PHP betiklerine doğru bir şekilde iletmeyebilir. Bu sorunu tamamen ortadan kaldırmak için, tüm isteklerde standart olmayan `X-Authorization` başlığının kullanılması **zorunludur**.

**Örnek Başlık:**
`X-Authorization: Bearer <ALINAN_TOKEN>`

---

## 2. Genel Hata Yönetimi

Tüm API yanıtları, işlemin sonucunu belirten bir JSON nesnesi döndürür. Başarılı istekler `200 OK` durum kodu ile döner. Hata durumlarında ise aşağıdaki HTTP durum kodları ve formatlar beklenmelidir.

| Kod | Durum | Açıklama ve İstemci Tarafında Yapılması Gereken | Örnek Yanıt |
| :-- | :--- | :--- | :--- |
| 400 | Bad Request | İstemcinin gönderdiği verilerde bir hata var (eksik parametre, geçersiz format vb.). `message` alanı hatanın nedenini açıklar. İstemci, kullanıcıya bu mesajı göstermeli ve girişi düzeltmesini istemelidir. | `{"success": false, "message": "Geçersiz e-posta formatı."}` |
| 401 | Unauthorized | JWT token'ı geçersiz veya süresi dolmuş. `mobile_auth.php` bu hatayı döndürürse, kullanıcı adı/şifre yanlıştır. | `{"success": false, "message": "Geçersiz kullanıcı adı veya şifre."}` |
| 403 | Forbidden | İstemcinin bu işlemi yapma yetkisi yok. Genellikle token gönderilmemiş veya kullanıcının rolü yetersiz olduğunda döner. İstemci, kullanıcıya "Yetkiniz yok" mesajı göstermelidir. | `{"success": false, "message": "Bu işlemi yapmak için yetkiniz yok."}` |
| 404 | Not Found | İstenen kaynak (endpoint, dosya, veri) sunucuda bulunamadı. | `{"success": false, "message": "Geri bildirim bulunamadı."}` |
| 405 | Method Not Allowed | Endpoint'e yanlış HTTP metodu ile istek yapıldı (örn: `GET` beklenen yere `POST` göndermek). | `{"success": false, "message": "Geçersiz istek metodu."}` |
| 500 | Internal Server Error | Sunucu tarafında beklenmedik bir kodlama hatası oluştu (örn: veritabanı sorgu hatası). Bu, istemcinin çözebileceği bir sorun değildir. Kullanıcıya genel bir "Sunucuda bir hata oluştu, lütfen daha sonra tekrar deneyin." mesajı gösterilmelidir. | `{"success": false, "message": "İşlem sırasında bir hata oluştu: SQLSTATE[...]"}` |

---

## 3. Halka Açık Endpoint'ler (Yetki Gerekmez)

Bu endpoint'ler için `X-Authorization` başlığına gerek yoktur.

### `POST /api/submit_feedback.php`
-   **Açıklama:** Kullanıcıların geri bildirim göndermesini sağlar. Cloudflare Turnstile (CAPTCHA) ile korunmaktadır.
-   **İstek Tipi:** `multipart/form-data`
-   **Parametreler:**
    | Parametre | Tip | Zorunlu? | Açıklama |
    | :--- | :--- | :--- | :--- |
    | `name` | string | Evet | Gönderenin adı. |
    | `email` | string | Evet | Gönderenin e-postası (`@akdeniz.edu.tr` veya `@ogr.akdeniz.edu.tr` uzantılı olmalı). |
    | `rating` | integer | Evet | 1-5 arası puan. |
    | `comment` | string | Evet | Geri bildirim metni. |
    | `image` | file | Hayır | Opsiyonel resim dosyası (JPG, PNG, GIF, WEBP, max 10MB). |
    | `cf-turnstile-response` | string | **Evet** | İstemcinin Cloudflare'den aldığı tek kullanımlık CAPTCHA token'ı. |
-   **Örnek İstek (cURL):**
    ```bash
    curl -X POST 'http://localhost/api/submit_feedback.php' \
    -F 'name=Ayşe Yılmaz' \
    -F 'email=ayse@ogr.akdeniz.edu.tr' \
    -F 'rating=5' \
    -F 'comment=Yemekler harikaydı!' \
    -F 'image=@/path/to/image.jpg' \
    -F 'cf-turnstile-response=CAPTCHA_TOKEN_FROM_WEBVIEW'
    ```
-   **Başarılı Yanıt (200 OK):** `{"success": true, "message": "Geri bildiriminiz başarıyla gönderildi."}`

### `GET /api/get_menu_events.php`
-   **Açıklama:** Aylık veya günlük menü verilerini halka açık olarak döndürür.
-   **Parametreler (Query String):**
    -   **Mod 1: Aylık Veri**
        - `year` (integer, zorunlu): İstenen yıl (örn: `2025`).
        - `month` (integer, zorunlu): İstenen ay (örn: `7`).
    -   **Mod 2: Günlük Veri**
        - `date` (string, `YYYY-MM-DD`, zorunlu): İstenen gün (örn: `2025-07-21`).
-   **Örnek İstek (Aylık):** `curl -X GET 'http://localhost/api/get_menu_events.php?year=2025&month=7'`
-   **Başarılı Yanıt (Aylık):**
    ```json
    {
      "menus": {
        "2025-07-21": {
          "meals": [ { "name": "Mercimek Çorbası" }, { "name": "Izgara Tavuk" } ],
          "total_calories": 850
        }
      },
      "special_days": {
        "2025-07-29": "Resmi Tatil"
      }
    }
    ```
-   **Örnek İstek (Günlük):** `curl -X GET 'http://localhost/api/get_menu_events.php?date=2025-07-21'`
-   **Başarılı Yanıt (Günlük Menü):**
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
          "is_vegetarian": "1",
          "is_gluten_free": "0",
          "has_allergens": "0"
        }
      ]
    }
    ```

### `GET /api/get_site_info.php`
-   **Açıklama:** Sitede tanımlı olan yetkili bilgilerini halka açık, formatlanmış başlıklarla döndürür.
-   **Başarılı Yanıt (200 OK):**
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

---

## 4. Kimlik Doğrulama Endpoint'i

### `POST /api/mobile_auth.php`
-   **Açıklama:** Yönetici kullanıcı adı ve şifresi ile kimlik doğrulaması yaparak bir JWT döndürür.
-   **Yetki:** Gerekmez.
-   **İstek Gövdesi (Request Body):** `application/json`
    -   `username` (string, zorunlu): Yönetici kullanıcı adı.
    -   `password` (string, zorunlu): Yönetici şifresi.
-   **Örnek İstek (cURL):**
    ```bash
    curl -X POST 'http://localhost/api/mobile_auth.php' \
    -H 'Content-Type: application/json' \
    -d '{"username": "admin", "password": "admin123"}'
    ```
-   **Başarılı Yanıt (200 OK):**
    ```json
    {
      "success": true,
      "message": "Giriş başarılı.",
      "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
      "expires_in": 3600
    }
    ```
-   **Hatalı Yanıtlar:**
    -   `400 Bad Request`: `{"success": false, "message": "Kullanıcı adı ve şifre boş olamaz."}`
    -   `401 Unauthorized`: `{"success": false, "message": "Geçersiz kullanıcı adı veya şifre."}`

---

## 5. Ağ Geçidi (Gateway) Endpoint'leri

Bu endpoint'lerin tamamı `/api/mobile_gateway.php?endpoint=<eylem>` adresini kullanır ve **Yetki Gerektirir** (`X-Authorization` başlığı zorunludur).

### 5.1. Geri Bildirim (Feedback) Yönetimi

#### `GET ...?endpoint=get_feedback`
-   **Açıklama:** Geri bildirimleri filtreleyerek ve sayfalı olarak listeler.
-   **Parametreler (Query String):**
    | Parametre | Tip | Zorunlu? | Açıklama |
    | :--- | :--- | :--- | :--- |
    | `status` | string | Hayır | `yeni`, `okundu`, `cevaplandı`, `arsivlendi`, `all` (varsayılan). |
    | `search` | string | Hayır | İsim veya yorum içinde arama yapar. |
    | `start_date`| string | Hayır | `YYYY-MM-DD` formatında başlangıç tarihi. |
    | `end_date` | string | Hayır | `YYYY-MM-DD` formatında bitiş tarihi. |
    | `page` | integer | Hayır | Sayfa numarası (varsayılan: `1`). |
    | `limit` | integer | Hayır | Sayfa başına kayıt sayısı (varsayılan: `25`). |
-   **Örnek İstek:** `curl -X GET 'http://localhost/api/mobile_gateway.php?endpoint=get_feedback&status=yeni&page=1' -H 'X-Authorization: Bearer <TOKEN>'`
-   **Başarılı Yanıt (200 OK):**
    ```json
    {
      "success": true,
      "data": [
        {
          "id": 123,
          "name": "Ali Veli",
          "email": "ali@ogr.akdeniz.edu.tr",
          "rating": "4",
          "comment": "Porsiyonlar biraz daha büyük olabilir.",
          "image_path": "687b7dd5cb4010.28808283_7c645e04f4a6a717.jpg",
          "is_read": "0",
          "is_archived": "0",
          "created_at": "2025-07-21 14:30:00",
          "reply_message": null,
          "replied_at": null,
          "replied_by_username": null,
          "status": "yeni",
          "created_at_formatted": "21.07.2025 14:30"
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

#### `POST ...?endpoint=mark_feedback`
-   **Açıklama:** Bir geri bildirimin durumunu değiştirir (okundu, arşivlendi, arşivden çıkar).
-   **İstek Gövdesi:** `application/x-www-form-urlencoded`
-   **Parametreler:**
    | Parametre | Tip | Zorunlu? | Açıklama |
    | :--- | :--- | :--- | :--- |
    | `id` | integer | Evet | Geri bildirim ID'si. |
    | `action` | string | Evet | `mark_read`, `archive`, `unarchive` değerlerinden biri. |
-   **Örnek İstek:** `curl -X POST 'http://localhost/api/mobile_gateway.php?endpoint=mark_feedback' -H 'X-Authorization: Bearer <TOKEN>' -d 'id=123&action=archive'`
-   **Başarılı Yanıt (200 OK):** `{"success": true, "message": "...", "feedback": { ... } }` (Güncellenmiş geri bildirim nesnesini döndürür).

#### `POST ...?endpoint=reply_feedback`
-   **Açıklama:** Bir geri bildirime e-posta ile yanıt gönderir ve veritabanına kaydeder.
-   **İstek Gövdesi:** `application/x-www-form-urlencoded`
-   **Parametreler:**
    | Parametre | Tip | Zorunlu? | Açıklama |
    | :--- | :--- | :--- | :--- |
    | `id` | integer | Evet | Geri bildirim ID'si. |
    | `email` | string | Evet | Yanıt gönderilecek kullanıcı e-postası. |
    | `reply_text`| string | Evet | Gönderilecek yanıt metni. |
-   **Örnek İstek:** `curl -X POST '...' -H 'X-Authorization: Bearer <TOKEN>' -d 'id=123&email=user@ogr.akdeniz.edu.tr&reply_text=Dikkate alacağız.'`
-   **Başarılı Yanıt (200 OK):** `{"success": true, "message": "Cevap başarıyla gönderildi ve kaydedildi."}`

#### `GET ...?endpoint=view_image`
-   **Açıklama:** Geri bildirime eklenmiş bir resmi görüntüler. **JSON döndürmez, doğrudan resim dosyası döndürür.**
-   **Parametreler (Query String):**
    | Parametre | Tip | Zorunlu? | Açıklama |
    | :--- | :--- | :--- | :--- |
    | `file` | string | Evet | Görüntülenecek dosyanın adı (örn: `687b7...jpg`). |
    | `download`| - | Hayır | Bu parametre eklenirse, resim tarayıcıda görüntülenmek yerine indirilir. |
-   **Örnek İstek:** `curl -X GET '...?endpoint=view_image&file=...' -H 'X-Authorization: Bearer <TOKEN>' --output image.jpg`

### 5.2. Menü ve Tarih Yönetimi

#### `GET ...?endpoint=get_week_overview`
-   **Açıklama:** Belirtilen bir tarihi içeren haftanın özetini (Pazartesi-Pazar) döndürür.
-   **Parametreler (Query String):**
    | Parametre | Tip | Zorunlu? | Açıklama |
    | :--- | :--- | :--- | :--- |
    | `date` | string | Hayır | `YYYY-MM-DD` formatında tarih. Varsayılan: bugün. |
-   **Örnek İstek:** `curl -X GET '...?endpoint=get_week_overview&date=2025-07-21' -H 'X-Authorization: Bearer <TOKEN>'`
-   **Başarılı Yanıt (200 OK):**
    ```json
    {
      "start_of_week_formatted": "21 Temmuz",
      "end_of_week_formatted": "27 Temmuz 2025",
      "days": [
        { "date_sql": "2025-07-21", "date_formatted": "21 Temmuz, Pazartesi", "summary": "Çorba, Kebap...", "is_special": false },
        { "date_sql": "2025-07-22", "date_formatted": "22 Temmuz, Salı", "summary": "Tatil", "is_special": true }
      ]
    }
    ```

#### `POST ...?endpoint=manage_date`
-   **Açıklama:** Belirli bir tarihin menüsünü günceller veya o tarihi özel gün olarak ayarlar.
-   **İstek Gövdesi:** `application/x-www-form-urlencoded`
-   **Parametreler:**
    | Parametre | Tip | Zorunlu? | Açıklama |
    | :--- | :--- | :--- | :--- |
    | `menu_date` | string | Evet | `YYYY-MM-DD` formatında tarih. |
    | `is_special_day` | `1` | Hayır | Gönderilirse, tarihi özel gün yapar. `meal_names` yoksayılır. |
    | `special_day_message` | string | `is_special_day` varsa evet | Özel günün açıklaması. |
    | `meal_names[]` | array | `is_special_day` yoksa evet | Yemek isimlerinin dizisi. Örn: `meal_names[]=Çorba&meal_names[]=Köfte` |
-   **Örnek İstek (Menü Güncelle):** `curl -X POST '...' -H 'X-Authorization: Bearer <TOKEN>' -d 'menu_date=2025-08-01&meal_names[]=Mercimek Çorbası&meal_names[]=Izgara Köfte'`
-   **Örnek İstek (Özel Gün Yap):** `curl -X POST '...' -H 'X-Authorization: Bearer <TOKEN>' -d 'menu_date=2025-08-02&is_special_day=1&special_day_message=Resmi Tatil'`
-   **Başarılı Yanıt (200 OK):** `{"success": true, "message": "Tarih başarıyla kaydedildi."}`

#### `POST ...?endpoint=copy_menu`
-   **Açıklama:** Bir tarihteki menüyü başka bir tarihe kopyalar.
-   **İstek Gövdesi:** `application/x-www-form-urlencoded`
-   **İpucu:** Dokümantasyonun önceki sürümünde istek gövdesinin JSON olduğu belirtilmişti, ancak kod `x-www-form-urlencoded` beklemektedir.
-   **Parametreler:**
    | Parametre | Tip | Zorunlu? | Açıklama |
    | :--- | :--- | :--- | :--- |
    | `source_date` | string | Evet | `YYYY-MM-DD` formatında kaynak tarih. |
    | `target_date` | string | Evet | `YYYY-MM-DD` formatında hedef tarih. |
-   **Örnek İstek:** `curl -X POST '...' -H 'X-Authorization: Bearer <TOKEN>' -d 'source_date=2025-08-01&target_date=2025-08-05'`
-   **Başarılı Yanıt (200 OK):** `{"success": true, "message": "Menü başarıyla kopyalandı!"}`
-   **Hatalı Yanıt (404 Not Found):** `{"success": false, "message": "Kaynak tarihte kopyalanacak menü bulunamadı."}`

### 5.3. Yemek (Meal) Yönetimi

#### `GET / POST ...?endpoint=manage_meal`
-   **Açıklama:** Bu endpoint, `action` parametresine göre birden fazla işlemi yönetir. Hem `GET` hem de `POST` metotlarını kullanır.
-   **İpucu:** Tek bir endpoint üzerinden CRUD (Oluşturma, Okuma, Güncelleme, Silme) işlemleri `action` parametresi ile yönetilmektedir.

-   **Eylem: `get_all` (GET)**
    -   **Açıklama:** Tüm yemekleri listeler.
    -   **Örnek İstek:** `curl -X GET '...?endpoint=manage_meal&action=get_all' -H 'X-Authorization: Bearer <TOKEN>'`
    -   **Başarılı Yanıt:** `[ { "id": 1, "name": "Mercimek Çorbası", "calories": "150", ... } ]`

-   **Eylem: `get_single` (GET)**
    -   **Açıklama:** Tek bir yemeğin detaylarını getirir.
    -   **Parametre:** `id` (integer, zorunlu).
    -   **Örnek İstek:** `curl -X GET '...?endpoint=manage_meal&action=get_single&id=1' -H 'X-Authorization: Bearer <TOKEN>'`
    -   **Başarılı Yanıt:** `{ "id": 1, "name": "Mercimek Çorbası", ... }`

-   **Eylem: `create` / `update` (POST)**
    -   **Açıklama:** Yeni yemek oluşturur veya mevcut bir yemeği günceller.
    -   **İstek Gövdesi:** `application/x-www-form-urlencoded`
    -   **Parametreler:**
        | Parametre | Tip | Zorunlu? | Açıklama |
        | :--- | :--- | :--- | :--- |
        | `action` | string | Evet | `create` veya `update`. |
        | `meal_id`| integer | `update` için evet | Güncellenecek yemeğin ID'si. |
        | `name` | string | Evet | Yemeğin adı. |
        | `calories` | integer | Hayır | Kalori değeri. |
        | `ingredients`| string | Hayır | İçindekiler. |
        | `is_vegetarian`| `1` | Hayır | Vejetaryen ise `1` gönderilir. |
        | `is_gluten_free`| `1` | Hayır | Glutensiz ise `1` gönderilir. |
        | `has_allergens`| `1` | Hayır | Alerjen içeriyorsa `1` gönderilir. |
    -   **Örnek (Create):** `curl -X POST '...' -d 'action=create&name=Yeni Yemek&calories=300'`
    -   **Örnek (Update):** `curl -X POST '...' -d 'action=update&meal_id=5&name=Güncel Yemek'`
    -   **Başarılı Yanıt:** `{"success": true, "message": "Yemek başarıyla kaydedildi."}`

-   **Eylem: `delete` (POST)**
    -   **Açıklama:** Bir yemeği siler.
    -   **İstek Gövdesi:** `application/x-www-form-urlencoded`
    -   **Parametre:** `id` (integer, zorunlu).
    -   **Örnek:** `curl -X POST '...' -d 'action=delete&id=5'`
    -   **Başarılı Yanıt:** `{"success": true, "message": "Yemek başarıyla silindi."}`

### 5.4. Site ve Rapor Yönetimi

#### `GET / POST ...?endpoint=manage_officials`
-   **Açıklama:** Yetkili listesini okur (`GET`) veya günceller (`POST`).
-   **GET İsteği (Yetki Gerekmez - Gateway üzerinden çağrılırsa gerekir):**
    -   **Açıklama:** Veritabanı anahtarlarıyla (`sks_daire_baskani`) yetkili listesini döner. Halka açık `get_site_info.php`'den farklı olarak ham veri sunar.
    -   **Örnek İstek:** `curl -X GET '...?endpoint=manage_officials' -H 'X-Authorization: Bearer <TOKEN>'`
    -   **Başarılı Yanıt:** `{"success": true, "data": {"sks_daire_baskani": "Doç. Dr. Veli ÇELİK", ...}}`
-   **POST İsteği (Yetki Gerekli):**
    -   **Açıklama:** Yetkili bilgilerini günceller.
    -   **İstek Gövdesi:** `application/x-www-form-urlencoded`
    -   **Parametreler:** `sks_daire_baskani`, `yemekhane_mudur_yrd`, `diyetisyen`, `yemekhane_email`.
    -   **Örnek İstek:** `curl -X POST '...' -H 'X-Authorization: Bearer <TOKEN>' -d 'diyetisyen=Prof. Dr. Yeni Diyetisyen'`
    -   **Başarılı Yanıt:** `{"success": true, "message": "Yetkili bilgileri başarıyla güncellendi."}`

#### `GET ...?endpoint=get_logs`
-   **Açıklama:** Yönetici işlem kayıtlarını (logları) listeler.
-   **Parametreler (Query String):** `limit` (integer, opsiyonel, varsayılan: 50).
-   **Örnek İstek:** `curl -X GET '...?endpoint=get_logs&limit=10' -H 'X-Authorization: Bearer <TOKEN>'`
-   **Başarılı Yanıt (200 OK):** `{"success": true, "data": [ { "id": 1, "admin_username": "admin", "action_type": "login", "details": "...", "ip_address": "::1", "action_time_formatted": "21.07.2025 10:00:00" } ]}`

#### `GET ...?endpoint=get_report_data`
-   **Açıklama:** Yönetim paneli anasayfası için istatistiksel rapor verilerini toplar ve döndürür.
-   **Örnek İstek:** `curl -X GET '...?endpoint=get_report_data' -H 'X-Authorization: Bearer <TOKEN>'`
-   **Başarılı Yanıt (200 OK):**
    ```json
    {
      "success": true,
      "data": {
        "general_stats": { "total_feedback": 50, "average_rating": 4.2, "new_feedback_count": 5 },
        "top_meals_chart": { "labels": ["Köfte", "Çorba"], "datasets": [...] },
        "ratings_chart": { "labels": ["5 Yıldız", "4 Yıldız"], "datasets": [...] },
        "complaint_words": { "soğuk": 10, "tuzlu": 5 }
      }
    }
    ```

---

## 6. Mobil Uyumluluk Notları ve İpuçları

### `upload_csv` Endpoint'i
-   **ÇOK ÖNEMLİ:** `upload_csv.php` dosyası, `analyze` ve `commit` adımları arasında dosya yolunu sunucuda **PHP session (`$_SESSION`)** içinde saklar.
-   Stateless (durumsuz) bir yapıya sahip olan mobil uygulama API'ları için session tabanlı bu yöntem **doğrudan uyumlu değildir**.
-   Bu endpoint'in mobil uygulamada kullanılması gerekiyorsa, iki adımlı süreci yönetecek farklı bir akış tasarlanmalıdır (örneğin, `analyze` adımından dönen dosya referansını `commit` adımında geri göndermek gibi). Mevcut haliyle bu endpoint, web arayüzü için tasarlanmıştır.

### Genel İpuçları
-   **Hata Mesajları:** API'den dönen `message` alanları genellikle kullanıcıya gösterilmeye uygundur.
-   **Token Yönetimi:** Alınan JWT token'ı güvenli bir şekilde saklayın ve süresi dolduğunda (`401 Unauthorized` hatası alındığında) kullanıcıyı tekrar giriş ekranına yönlendirin.
-   **Resim Yükleme/Görüntüleme:** Resimler `multipart/form-data` olarak gönderilir ve `view_image` ile ham veri olarak alınır. Aldığınız veriyi doğrudan bir resim komponentinde kullanabilirsiniz.
