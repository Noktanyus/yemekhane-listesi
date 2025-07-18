<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akdeniz Üniversitesi Yemekhane Menüsü</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="calendar-app">
    <div class="app-header">
        <div class="app-header-left">
            <span class="app-icon"></span>
            <p class="app-name">Yemek Menüsü</p>
        </div>
        <div class="app-header-right">
            <button id="print-btn" class="action-btn">Yazdır</button>
            <a href="admin.php" class="action-btn-primary">Admin Paneli</a>
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
        <div id="calendar-grid-wrapper">
            <div id="calendar-grid"></div>
        </div>
        <div id="loading-spinner" class="hidden"><div class="spinner"></div></div>
    </div>
    <footer class="app-footer">
        Akdeniz Üniversitesi Bilgi İşlem Daire Başkanlığı
    </footer>
</div>

<!-- Yemek Detayları Modalı -->
<div id="meal-details-modal" class="modal hidden">
    <div class="modal-content">
        <button class="modal-close">&times;</button>
        <h3 id="modal-title">Yemek Detayları</h3>
        <div id="modal-body"></div>
        <div id="modal-footer">
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

<!-- Custom JS -->
<script src="assets/js/script.js"></script>
</body>
</html>
