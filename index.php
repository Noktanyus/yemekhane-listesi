<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akdeniz Üniversitesi Yemekhane Menüsü</title>
    <link rel="icon" href="assets/logo.png">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Merriweather:wght@400;700;900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="calendar-app">
    <div class="app-header">
        <div class="app-header-left">
            <img src="assets/logo.png" alt="Akdeniz Üniversitesi Logo" class="app-logo">
            <div class="header-text">
                <p class="app-name">Akdeniz Üniversitesi</p>
                <p class="app-subname">Sağlık, Kültür ve Spor Dairesi Başkanlığı Merkezi Yemekhane</p>
            </div>
        </div>
        <div class="app-header-right">
            <button id="feedback-btn" class="action-btn">Geri Bildirim</button>
            <button id="print-btn" class="action-btn">Yazdır</button>
            <a href="admin/index.php" class="action-btn-primary">Admin Paneli</a>
        </div>
    </div>
    <div class="app-content">
        <div class="calendar-controls">
            <h2 id="calendar-month-year" title="Tarih Seçmek İçin Tıklayın"></h2>
            <div class="nav-buttons">
                <button id="prev-month-btn" class="nav-btn">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <button id="go-to-today-btn" class="nav-btn today-btn">Bugün</button>
                <button id="next-month-btn" class="nav-btn">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
        </div>
        <h3 class="meal-type-header">Öğle Yemeği</h3>
        <div id="calendar-grid-wrapper">
            <div id="calendar-grid"></div>
        </div>
        <div id="loading-spinner" class="hidden"><div class="spinner"></div></div>
    </div>
    <footer class="app-footer">
        <div id="officials-info" class="officials-info">
            <!-- JS ile dolacak -->
        </div>
        <div class="footer-bottom">
            Akdeniz Üniversitesi Bilgi İşlem Daire Başkanlığı
        </div>
    </footer>
</div>

<!-- Geri Bildirim Modalı -->
<div id="feedback-modal" class="modal hidden">
    <div class="modal-content">
        <button class="modal-close">&times;</button>
        <h3>Geri Bildirim Gönder</h3>
        <form id="feedback-form" enctype="multipart/form-data">
            <p>Yemekhane hizmetleri hakkındaki düşüncelerinizi bizimle paylaşın.</p>
            <div class="form-group">
                <label for="feedback-name">Adınız Soyadınız</label>
                <input type="text" id="feedback-name" name="name" required>
            </div>
            <div class="form-group">
                <label for="feedback-email">E-posta Adresiniz</label>
                <input type="email" id="feedback-email" name="email" required placeholder="@akdeniz.edu.tr veya @ogr.akdeniz.edu.tr">
            </div>
            <div class="form-group">
                <label>Genel Değerlendirme</label>
                <div class="rating-stars">
                    <input type="radio" id="star5" name="rating" value="5" required><label for="star5" title="Mükemmel">★</label>
                    <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="İyi">★</label>
                    <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="Orta">★</label>
                    <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="Kötü">★</label>
                    <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="Çok Kötü">★</label>
                </div>
            </div>
            <div class="form-group">
                <label for="feedback-comment">Yorumunuz</label>
                <textarea id="feedback-comment" name="comment" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label for="feedback-image">Görsel Yükle (İsteğe Bağlı, Maks 10MB)</label>
                <input type="file" id="feedback-image" name="image" accept="image/png, image/jpeg, image/gif, image/webp">
            </div>
            <!-- Cloudflare Turnstile Widget -->
            <div class="form-group">
                <div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars(CLOUDFLARE_SITE_KEY); ?>"></div>
            </div>
            <button type="submit" class="btn-submit">Gönder</button>
        </form>
    </div>
</div>

<!-- Yemek Detayları Modalı -->
<div id="meal-details-modal" class="modal hidden">
    <div class="modal-content">
        <button class="modal-close">&times;</button>
        <h3 id="modal-title">Yemek Detayları</h3>
        <div id="modal-body"></div>
        <div id="modal-footer">
            <div id="modal-legend">
                <span class="legend-item">🌿 Vejetaryen</span>
                <span class="legend-item">🚫🌾 Glütensiz</span>
                <span class="legend-item">⚠️ Alerjen</span>
            </div>
            <strong>Toplam Kalori: <span id="total-calories">0</span></strong>
        </div>
    </div>
</div>

<!-- Hızlı Tarih Seçici Modalı -->
<div id="date-picker-modal" class="modal hidden">
    <div class="modal-content">
        <button class="modal-close">&times;</button>
        <h3>Hızlı Tarih Seçimi</h3>
        <form id="date-picker-form">
            <div class="form-group">
                <label for="select-month">Ay Seçin:</label>
                <select id="select-month">
                    <option value="0">Ocak</option><option value="1">Şubat</option><option value="2">Mart</option>
                    <option value="3">Nisan</option><option value="4">Mayıs</option><option value="5">Haziran</option>
                    <option value="6">Temmuz</option><option value="7">Ağustos</option><option value="8">Eylül</option>
                    <option value="9">Ekim</option><option value="10">Kasım</option><option value="11">Aralık</option>
                </select>
            </div>
            <div class="form-group">
                <label for="select-year">Yıl Seçin:</label>
                <select id="select-year"></select>
            </div>
            <button type="submit" class="btn-submit">Git</button>
        </form>
    </div>
</div>

<!-- Cloudflare Turnstile & Custom JS -->
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<script src="assets/js/script.js?v=<?php echo time(); ?>"></script>
</body>
</html>