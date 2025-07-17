<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akdeniz Üniversitesi Yemekhane Menüsü</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="container">
            <h1>Akdeniz Üniversitesi Yemekhane</h1>
            <p>Aylık Yemek Menüsü</p>
        </div>
    </header>

    <main class="container">
        <div class="main-controls">
            <button id="go-to-today-btn" class="control-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M11 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1z"/><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM2 2a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1H2z"/><path d="M2.5 4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5H3a.5.5 0 0 1-.5-.5V4z"/></svg>
                Bugüne Git
            </button>
            <button id="print-btn" class="control-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
                Yazdır
            </button>
        </div>
        <div id="calendar-container">
            <div class="calendar-header">
                <button id="prev-month-btn">&lt; Önceki Ay</button>
                <h2 id="calendar-month-year" title="Tarih Seçmek İçin Tıklayın"></h2>
                <button id="next-month-btn">Sonraki Ay &gt;</button>
            </div>
            <div id="calendar-grid"></div>
            <div id="loading-spinner" class="hidden"><div class="spinner"></div></div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 Akdeniz Üniversitesi Bilgi İşlem Daire Başkanlığı</p>
        </div>
    </footer>

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

    <script src="assets/js/script.js"></script>
</body>
</html>
